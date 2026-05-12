<?php
session_start();
require_once '../config.php';
require_once '../vendor/autoload.php';
include '../template/header2.php';

$user       = $_SESSION['user'] ?? ['can_edit' => 0, 'can_delete' => 0];
$can_edit   = $user['can_edit']   ?? 0;
$can_delete = $user['can_delete'] ?? 0;

/* ------------------------------------------------------------------ */
/* Filter & Search                                                     */
/* ------------------------------------------------------------------ */
$search        = trim($_GET['search']        ?? '');
$location_id   = trim($_GET['location_id']   ?? '');
$asset_type_id = trim($_GET['asset_type_id'] ?? '');

/* ใช้ Prepared Statement แทน real_escape_string + string concat        */
$conditions  = [];
$params      = [];
$paramTypes  = "";

if ($search !== '') {
    $conditions[] = "(n.code_id LIKE ? OR n.name LIKE ? OR n.serial_no LIKE ?
                      OR n.model LIKE ? OR n.monitor LIKE ? OR n.remark LIKE ?
                      OR l.name LIKE ? OR t.name LIKE ?)";
    for ($x = 0; $x < 8; $x++) {
        $params[]    = "%$search%";
        $paramTypes .= "s";
    }
}
if ($location_id !== '') {
    $conditions[] = "n.location_id = ?";
    $params[]     = (int)$location_id;
    $paramTypes  .= "i";
}
if ($asset_type_id !== '') {
    $conditions[] = "n.asset_type_id = ?";
    $params[]     = (int)$asset_type_id;
    $paramTypes  .= "i";
}

$whereSql = $conditions ? "WHERE " . implode(" AND ", $conditions) : "";

/* ------------------------------------------------------------------ */
/* Pagination                                                          */
/* ------------------------------------------------------------------ */
$limit  = 50;
$page   = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * $limit;

$count_sql = "SELECT COUNT(*) AS c
              FROM writeoff n
              LEFT JOIN locations   l ON n.location_id   = l.id
              LEFT JOIN asset_types t ON n.asset_type_id = t.id
              $whereSql";
$stmt = $conn->prepare($count_sql);
if ($stmt === false) { die("Prepare count failed: " . $conn->error); }
if ($params) { $stmt->bind_param($paramTypes, ...$params); }
$stmt->execute();
$total       = (int)$stmt->get_result()->fetch_assoc()['c'];
$total_pages = (int)ceil($total / $limit);
$stmt->close();

/* ------------------------------------------------------------------ */
/* Dropdown arrays (เก็บเป็น array เพื่อ rewind ได้ไม่จำกัดครั้ง)    */
/* MySQL 8.0 บังคับ ONLY_FULL_GROUP_BY → ใช้ subquery MIN(id) แทน    */
/* ------------------------------------------------------------------ */
$locations_arr = [];
$res = $conn->query("SELECT id, name FROM locations ORDER BY name ASC");
if ($res === false) {
    die("Query locations failed: " . $conn->error);
}
while ($row = $res->fetch_assoc()) { $locations_arr[] = $row; }

$asset_types_arr = [];
$res = $conn->query("
    SELECT id, name
    FROM asset_types
    WHERE id IN (
        SELECT MIN(id) FROM asset_types GROUP BY name
    )
    ORDER BY name ASC
");
if ($res === false) {
    die("Query asset_types failed: " . $conn->error);
}
while ($row = $res->fetch_assoc()) { $asset_types_arr[] = $row; }

/* ------------------------------------------------------------------ */
/* Edit form data                                                      */
/* ------------------------------------------------------------------ */
$edit_id = (int)($_GET['edit'] ?? 0);
$edit = [
    'code_id'       => '',
    'name'          => '',
    'location_id'   => '',
    'asset_type_id' => '',
    'startdate'     => '',
    'monitor'       => '',
    'model'         => '',
    'serial_no'     => '',
    'remark'        => '',
    'image'         => '',
];
if ($edit_id) {
    $stmt = $conn->prepare("SELECT * FROM writeoff WHERE id = ?");
    $stmt->bind_param("i", $edit_id);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if ($res) {
        foreach ($edit as $k => $_) {
            if (isset($res[$k])) { $edit[$k] = $res[$k]; }
        }
    }
}

/* ------------------------------------------------------------------ */
/* Main data query                                                     */
/* ------------------------------------------------------------------ */
$data_sql = "
    SELECT n.*, l.name AS location_name, t.name AS asset_type_name
    FROM writeoff n
    LEFT JOIN locations   l ON n.location_id   = l.id
    LEFT JOIN asset_types t ON n.asset_type_id = t.id
    $whereSql
    ORDER BY n.id ASC
    LIMIT $limit OFFSET $offset
";
$stmt = $conn->prepare($data_sql);
if ($stmt === false) { die("Prepare data failed: " . $conn->error); }
if ($params) { $stmt->bind_param($paramTypes, ...$params); }
$stmt->execute();
$data = $stmt->get_result();
/* $stmt จะ close หลัง fetch ด้านล่าง */

/* ------------------------------------------------------------------ */
/* Success message                                                     */
/* ------------------------------------------------------------------ */
$msg = $_GET['msg'] ?? '';
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>Writeoff Assets</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
        .table th, .table td { text-align: center; vertical-align: middle; }
    </style>
</head>
<body>
<div class="container py-3">
    <h3 class="mb-3">WriteOff</h3>    

    <!-- ===== Add / Edit Form ===== -->
    <?php if ($can_edit): ?>
    <form method="post" action="save_writeoff.php" enctype="multipart/form-data" class="row g-2 mb-3">
        <input type="hidden" name="id" value="<?= $edit_id ?>">

        <!-- code_id -->
        <div class="col-md-3">
            <label class="form-label">Code ID</label>
            <input name="code_id" class="form-control" value="<?= htmlspecialchars($edit['code_id']) ?>">
        </div>

        <!-- name -->
        <div class="col-md-3">
            <label class="form-label">Name</label>
            <input name="name" class="form-control" value="<?= htmlspecialchars($edit['name']) ?>">
        </div>

        <!-- location_id -->
        <div class="col-md-3">
            <label class="form-label">Location</label>
            <select name="location_id" class="form-select" required>
                <option value="">-- Choose --</option>
                <?php foreach ($locations_arr as $loc): ?>
                    <option value="<?= (int)$loc['id'] ?>"
                        <?= ($edit['location_id'] == $loc['id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($loc['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- asset_type_id -->
        <div class="col-md-3">
            <label class="form-label">Type</label>
            <select name="asset_type_id" class="form-select" required>
                <option value="">-- Choose --</option>
                <?php foreach ($asset_types_arr as $t): ?>
                    <option value="<?= (int)$t['id'] ?>"
                        <?= ($edit['asset_type_id'] == $t['id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($t['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- startdate -->
        <div class="col-md-3">
            <label class="form-label">Start Date</label>
            <?php
            $sd_val = (!empty($edit['startdate']) && $edit['startdate'] !== '0000-00-00')
                      ? date('Y-m-d', strtotime($edit['startdate'])) : '';
            ?>
            <input type="date" name="startdate" class="form-control" value="<?= $sd_val ?>">
        </div>

        <!-- monitor -->
        <div class="col-md-3">
            <label class="form-label">เครื่องเก่าจาก</label>
            <input name="monitor" class="form-control" value="<?= htmlspecialchars($edit['monitor']) ?>">
        </div>

        <!-- model -->
        <div class="col-md-3">
            <label class="form-label">Model</label>
            <input name="model" class="form-control" value="<?= htmlspecialchars($edit['model']) ?>">
        </div>

        <!-- serial_no -->
        <div class="col-md-3">
            <label class="form-label">Serial No</label>
            <input name="serial_no" class="form-control" value="<?= htmlspecialchars($edit['serial_no']) ?>">
        </div>

        <!-- remark -->
        <div class="col-md-3">
            <label class="form-label">Note</label>
            <input name="remark" class="form-control" value="<?= htmlspecialchars($edit['remark']) ?>">
        </div>

        <!-- image -->
        <div class="col-md-3">
            <label class="form-label">Upload Photo</label>
            <input type="file" name="image" class="form-control">
            <?php if (!empty($edit['image'])): ?>
                <a href="javascript:void(0)"
                   onclick="showImage('../img/<?= htmlspecialchars($edit['image']) ?>')">
                    <img src="../img/<?= htmlspecialchars($edit['image']) ?>"
                         class="mt-1" style="max-width:100px; max-height:80px; cursor:pointer;">
                </a>
            <?php endif; ?>
        </div>

        <div class="col-md-3 d-flex align-items-end">
            <button type="submit" class="btn btn-success w-100">บันทึก</button>
        </div>
    </form>
    <?php endif; ?>

    <!-- Export -->
    <a href="export_writeoff_excel.php?search=<?= urlencode($search) ?>&location_id=<?= (int)$location_id ?>&asset_type_id=<?= (int)$asset_type_id ?>"
       class="btn btn-outline-primary mb-3">Export Excel</a>

    <!-- ===== Filter Form ===== -->
    <form method="get" class="row mb-3 g-2">
        <div class="col-md-3">
            <input type="text" name="search"
                   value="<?= htmlspecialchars($search) ?>"
                   class="form-control" placeholder="ค้นหาทุกช่อง...">
        </div>

        <div class="col-md-2">
            <select name="location_id" class="form-select">
                <option value="">-- สถานที่ --</option>
                <?php foreach ($locations_arr as $loc): ?>
                    <option value="<?= (int)$loc['id'] ?>"
                        <?= $location_id == $loc['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($loc['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-md-2">
            <select name="asset_type_id" class="form-select">
                <option value="">-- ประเภททรัพย์สิน --</option>
                <?php foreach ($asset_types_arr as $t): ?>
                    <option value="<?= (int)$t['id'] ?>"
                        <?= $asset_type_id == $t['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($t['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-md-2">
            <button type="submit" class="btn btn-primary w-100">ค้นหา</button>
        </div>
        <div class="col-md-2">
            <a href="writeoff.php" class="btn btn-secondary w-100">ล้าง</a>
        </div>
    </form>

    <!-- Pagination (top) -->
    <?php if ($total_pages > 1): ?>
    <nav><ul class="pagination flex-wrap">
        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
            <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                <a class="page-link"
                   href="?<?= htmlspecialchars(http_build_query(array_merge($_GET, ['page' => $i]))) ?>">
                    <?= $i ?>
                </a>
            </li>
        <?php endfor; ?>
    </ul></nav>
    <?php endif; ?>

    <!-- ===== Data Table ===== -->
    <table class="table table-bordered table-sm align-middle">
        <thead class="table-light">
            <tr>
                <th>#</th>
                <th>Code</th>
                <th>Name</th>
                <th>Location</th>
                <th>Type</th>
                <th>Start Date</th>
                <th>Year</th>
                <th>Month</th>
                <th>เครื่องเก่าจาก</th>
                <th>Model</th>
                <th>Serial No</th>
                <th>Note</th>
                <th>Image</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $rowNo = ($page - 1) * $limit + 1;
            while ($row = $data->fetch_assoc()):
                $years  = '-';
                $months = '-';
                if (!empty($row['startdate']) && $row['startdate'] !== '0000-00-00') {
                    $start  = new DateTime($row['startdate']);
                    $end    = new DateTime();
                    $diff   = $start->diff($end);
                    $years  = $diff->y;
                    $months = $diff->m;
                }
            ?>
            <tr>
                <td><?= $rowNo++ ?></td>
                <td><?= htmlspecialchars($row['code_id']) ?></td>
                <td><?= htmlspecialchars($row['name']) ?></td>
                <td><?= htmlspecialchars($row['location_name']    ?? '') ?></td>
                <td><?= htmlspecialchars($row['asset_type_name']  ?? '') ?></td>
                <td><?= (!empty($row['startdate']) && $row['startdate'] !== '0000-00-00')
                        ? date('d-m-Y', strtotime($row['startdate'])) : '-' ?></td>
                <td><?= $years ?></td>
                <td><?= $months ?></td>
                <td><?= htmlspecialchars($row['monitor'])   ?></td>
                <td><?= htmlspecialchars($row['model'])     ?></td>
                <td><?= htmlspecialchars($row['serial_no']) ?></td>
                <td><?= htmlspecialchars($row['remark'])    ?></td>
                <td>
                    <?php if (!empty($row['image'])): ?>
                        <a href="javascript:void(0)"
                           onclick="showImage('../img/<?= htmlspecialchars($row['image']) ?>')">
                            <img src="../img/<?= htmlspecialchars($row['image']) ?>"
                                 style="max-width:120px; max-height:90px; cursor:pointer;">
                        </a>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if ($can_edit): ?>
                        <a href="?edit=<?= (int)$row['id'] ?>" class="btn btn-sm btn-warning">✏️</a>
                    <?php endif; ?>
                    <?php if ($can_delete): ?>
                        <a href="delete_writeoff.php?id=<?= (int)$row['id'] ?>"
                           class="btn btn-sm btn-danger"
                           onclick="return confirm('ลบหรือไม่?')">🗑️</a>
                    <?php endif; ?>
                    <a href="print_qr_writeoff.php?id=<?= (int)$row['id'] ?>"
                       class="btn btn-sm btn-secondary" target="_blank">QR</a>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>

    <!-- Pagination (bottom) -->
    <?php if ($total_pages > 1): ?>
    <nav><ul class="pagination flex-wrap">
        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
            <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                <a class="page-link"
                   href="?<?= htmlspecialchars(http_build_query(array_merge($_GET, ['page' => $i]))) ?>">
                    <?= $i ?>
                </a>
            </li>
        <?php endfor; ?>
    </ul></nav>
    <?php endif; ?>
</div>

<!-- Image Modal -->
<div class="modal fade" id="imageModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered" style="max-width:1024px;">
        <div class="modal-content" style="width:1024px; height:768px;">
            <div class="modal-body d-flex justify-content-center align-items-center p-0">
                <img id="modalImage" src="" style="width:100%; height:100%; object-fit:contain;">
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function showImage(src) {
    document.getElementById('modalImage').src = src;
    new bootstrap.Modal(document.getElementById('imageModal')).show();
}
</script>

<?php include '../template/footer.php'; ?>
