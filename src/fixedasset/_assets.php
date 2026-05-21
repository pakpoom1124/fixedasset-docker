<?php
session_start();
require_once 'config.php';
require_once 'vendor/autoload.php';

if (!isset($_SESSION['user']) || !is_array($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}
$user       = $_SESSION['user'];
$can_edit   = $user['can_edit']   ?? 0;
$can_delete = $user['can_delete'] ?? 0;

function nullableDate(string $v): ?string {
    return ($v === '' || $v === '0000-00-00') ? null : $v;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $can_edit) {
    $id     = (int)($_POST['id'] ?? 0);
    $fields = [
        'code_id','name','serialno','model','compname',
        'startdate','expdate','status','remark',
        'location_id','type_id','position_id'
    ];
    $data = [];
    foreach ($fields as $f) {
        $data[$f] = $_POST[$f] ?? '';
    }

    $startdate = nullableDate($data['startdate']);
    $expdate   = nullableDate($data['expdate']);

    if ($id) {
        $sql  = "UPDATE assets
                 SET code_id=?, name=?, serialno=?, model=?, compname=?,
                     startdate=?, expdate=?, status=?, remark=?,
                     location_id=?, type_id=?, position_id=?
                 WHERE id=?";
        $stmt = $conn->prepare($sql);
        if (!$stmt) { die("Prepare UPDATE failed: " . $conn->error); }
        $stmt->bind_param(
            "sssssssssiiii",
            $data['code_id'], $data['name'],    $data['serialno'],
            $data['model'],   $data['compname'],
            $startdate,       $expdate,
            $data['status'],  $data['remark'],
            $data['location_id'], $data['type_id'], $data['position_id'],
            $id
        );
    } else {
        $sql  = "INSERT INTO assets
                    (code_id, name, serialno, model, compname,
                     startdate, expdate, status, remark,
                     location_id, type_id, position_id)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        if (!$stmt) { die("Prepare INSERT failed: " . $conn->error); }
        $stmt->bind_param(
            "sssssssssiii",
            $data['code_id'], $data['name'],    $data['serialno'],
            $data['model'],   $data['compname'],
            $startdate,       $expdate,
            $data['status'],  $data['remark'],
            $data['location_id'], $data['type_id'], $data['position_id']
        );
    }

    if (!$stmt->execute()) {
        die("Execute failed: " . $stmt->error);
    }
    $stmt->close();
    header("Location: assets.php");
    exit;
}

if (isset($_GET['delete']) && $can_delete) {
    $id   = (int)$_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM assets WHERE id=?");
    if (!$stmt) { die("Prepare DELETE failed: " . $conn->error); }
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
    header("Location: assets.php");
    exit;
}

$edit    = null;
$edit_id = (int)($_GET['edit'] ?? 0);
if ($edit_id) {
    $stmt = $conn->prepare("SELECT * FROM assets WHERE id=?");
    if (!$stmt) { die("Prepare SELECT failed: " . $conn->error); }
    $stmt->bind_param("i", $edit_id);
    $stmt->execute();
    $edit = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

$locations_arr = [];
$res = $conn->query("SELECT id, name FROM locations ORDER BY name ASC");
if ($res === false) { die("Query locations failed: " . $conn->error); }
while ($row = $res->fetch_assoc()) { $locations_arr[] = $row; }

$positions_arr = [];
$res = $conn->query("SELECT id, name FROM positions ORDER BY name ASC");
if ($res === false) { die("Query positions failed: " . $conn->error); }
while ($row = $res->fetch_assoc()) { $positions_arr[] = $row; }

$types_arr = [];
$res = $conn->query("
    SELECT at.id, at.name
    FROM asset_types at
    LEFT JOIN category c ON at.category_id = c.id
    WHERE c.cat_name = 'Computer'
    ORDER BY at.name ASC
");
if ($res === false) { die("Query types failed: " . $conn->error); }
while ($row = $res->fetch_assoc()) { $types_arr[] = $row; }

$search          = trim($_GET['search']          ?? '');
$filter_location = trim($_GET['filter_location'] ?? '');
$filter_type     = trim($_GET['filter_type']     ?? '');
$filter_position = trim($_GET['filter_position'] ?? '');

$conditions = [];
$params     = [];
$paramTypes = "";

if ($filter_location !== '') {
    $conditions[] = "a.location_id = ?";
    $params[]     = (int)$filter_location;
    $paramTypes  .= "i";
}
if ($filter_type !== '') {
    $conditions[] = "a.type_id = ?";
    $params[]     = (int)$filter_type;
    $paramTypes  .= "i";
}
if ($filter_position !== '') {
    $conditions[] = "a.position_id = ?";
    $params[]     = (int)$filter_position;
    $paramTypes  .= "i";
}
if ($search !== '') {
    $conditions[] = "(a.code_id LIKE ? OR a.name LIKE ? OR a.serialno LIKE ?
                      OR a.model LIKE ? OR a.compname LIKE ? OR a.status LIKE ? OR a.remark LIKE ?)";
    for ($x = 0; $x < 7; $x++) {
        $params[]    = "%$search%";
        $paramTypes .= "s";
    }
}

$whereSql = $conditions ? "WHERE " . implode(" AND ", $conditions) : "";

$limit  = 50;
$page   = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * $limit;

$stmt = $conn->prepare("SELECT COUNT(*) AS c FROM assets a $whereSql");
if (!$stmt) { die("Prepare COUNT failed: " . $conn->error); }
if ($params) { $stmt->bind_param($paramTypes, ...$params); }
$stmt->execute();
$total       = (int)$stmt->get_result()->fetch_assoc()['c'];
$total_pages = (int)ceil($total / $limit);
$stmt->close();

$data_sql = "
    SELECT a.*,
           l.name AS location_name,
           t.name AS type_name,
           p.name AS position_name
    FROM assets a
    LEFT JOIN locations   l ON a.location_id = l.id
    LEFT JOIN asset_types t ON a.type_id     = t.id
    LEFT JOIN positions   p ON a.position_id = p.id
    $whereSql
    ORDER BY a.id ASC
    LIMIT $limit OFFSET $offset
";
$stmt = $conn->prepare($data_sql);
if (!$stmt) { die("Prepare data failed: " . $conn->error); }
if ($params) { $stmt->bind_param($paramTypes, ...$params); }
$stmt->execute();
$assets_result = $stmt->get_result();

include 'template/header.php';
?>

<div class="container mt-4">
    <h3>Computer</h3>

    <?php if ($can_edit): ?>
    <form method="post" class="row g-2 mb-3">
        <input type="hidden" name="id" value="<?= $edit_id ?>">

        <?php
        $form_fields = [
            'code_id'   => 'Code ID',
            'name'      => 'Name',
            'serialno'  => 'Serial No',
            'model'     => 'Model',
            'compname'  => 'Comp Name',
            'startdate' => 'Start Date',
            'expdate'   => 'Expire Date',
            'status'    => 'Status',
            'remark'    => 'Note',
        ];
        foreach ($form_fields as $f => $label): ?>
        <div class="col-md-3">
            <label class="form-label"><?= htmlspecialchars($label) ?></label>
            <?php if ($f === 'status'): ?>
                <select name="status" class="form-select">
                    <?php foreach (['', 'ใช้งาน', 'เสีย', 'รอซ่อม', 'รอ WriteOff', 'Spare'] as $opt): ?>
                        <option value="<?= htmlspecialchars($opt) ?>"
                            <?= ($edit['status'] ?? '') === $opt ? 'selected' : '' ?>>
                            <?= $opt === '' ? '-- เลือก --' : htmlspecialchars($opt) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            <?php else: ?>
                <?php
                $isDate = in_array($f, ['startdate', 'expdate']);
                $rawVal = $edit[$f] ?? '';
                $dispVal = ($isDate && ($rawVal === null || $rawVal === '0000-00-00' || $rawVal === ''))
                           ? '' : $rawVal;
                ?>
                <input type="<?= $isDate ? 'date' : 'text' ?>"
                       name="<?= $f ?>"
                       class="form-control"
                       value="<?= htmlspecialchars((string)$dispVal) ?>">
            <?php endif; ?>
        </div>
        <?php endforeach; ?>

        <div class="col-md-3">
            <label class="form-label">Department</label>
            <select name="position_id" class="form-select">
                <option value="">-- Choose --</option>
                <?php foreach ($positions_arr as $p): ?>
                    <option value="<?= (int)$p['id'] ?>"
                        <?= ($edit['position_id'] ?? '') == $p['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($p['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-md-3">
            <label class="form-label">Location</label>
            <select name="location_id" class="form-select">
                <option value="">-- Choose --</option>
                <?php foreach ($locations_arr as $l): ?>
                    <option value="<?= (int)$l['id'] ?>"
                        <?= ($edit['location_id'] ?? '') == $l['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($l['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-md-3">
            <label class="form-label">Type</label>
            <select name="type_id" class="form-select">
                <option value="">-- Choose --</option>
                <?php foreach ($types_arr as $t): ?>
                    <option value="<?= (int)$t['id'] ?>"
                        <?= ($edit['type_id'] ?? '') == $t['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($t['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-md-3 d-flex align-items-end">
            <button type="submit" class="btn btn-success w-100">บันทึก</button>
        </div>
    </form>
    <?php endif; ?>

    <div class="mb-2">
        <a href="assets_export.php" class="btn btn-outline-success">Export Excel</a>
    </div>

    <form method="get" class="row g-2 mb-3">
        <div class="col-md-3">
            <input type="text" name="search"
                   value="<?= htmlspecialchars($search) ?>"
                   class="form-control" placeholder="ค้นหา...">
        </div>
        <div class="col-md-3">
            <select name="filter_location" class="form-select">
                <option value="">-- สถานที่ --</option>
                <?php foreach ($locations_arr as $l): ?>
                    <option value="<?= (int)$l['id'] ?>"
                        <?= $filter_location == $l['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($l['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3">
            <select name="filter_type" class="form-select">
                <option value="">-- ประเภท --</option>
                <?php foreach ($types_arr as $t): ?>
                    <option value="<?= (int)$t['id'] ?>"
                        <?= $filter_type == $t['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($t['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3">
            <select name="filter_position" class="form-select">
                <option value="">-- แผนก --</option>
                <?php foreach ($positions_arr as $p): ?>
                    <option value="<?= (int)$p['id'] ?>"
                        <?= $filter_position == $p['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($p['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2 mt-2">
            <button type="submit" class="btn btn-primary w-100">ค้นหา</button>
        </div>
        <div class="col-md-2 mt-2">
            <a href="assets.php" class="btn btn-secondary w-100">ล้าง</a>
        </div>
    </form>

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

    <table class="table table-bordered table-sm">
        <thead class="table-light">
            <tr>
                <th>#</th>
                <th>Code</th>
                <th>Name</th>
                <th>Department</th>
                <th>Location</th>
                <th>Serial No</th>
                <th>Model</th>
                <th>Type</th>
                <th>Comp Name</th>
                <th>Start Date</th>
                <th>Expire Date</th>
                <th>Status</th>
                <th>Note</th>
                <th width="130">Action</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $rowNo = ($page - 1) * $limit + 1;
            while ($row = $assets_result->fetch_assoc()):
            ?>
            <tr>
                <td><?= $rowNo++ ?></td>
                <td><?= htmlspecialchars($row['code_id']) ?></td>
                <td><?= htmlspecialchars($row['name']) ?></td>
                <td><?= htmlspecialchars($row['position_name'] ?? '') ?></td>
                <td><?= htmlspecialchars($row['location_name'] ?? '') ?></td>
                <td><?= htmlspecialchars($row['serialno']) ?></td>
                <td><?= htmlspecialchars($row['model']) ?></td>
                <td><?= htmlspecialchars($row['type_name'] ?? '') ?></td>
                <td><?= htmlspecialchars($row['compname']) ?></td>
                <td><?= (!empty($row['startdate']) && $row['startdate'] !== '0000-00-00')
                        ? date('d-m-Y', strtotime($row['startdate'])) : '' ?></td>
                <td><?= (!empty($row['expdate'])   && $row['expdate']   !== '0000-00-00')
                        ? date('d-m-Y', strtotime($row['expdate']))   : '' ?></td>
                <td><?= htmlspecialchars($row['status']) ?></td>
                <td><?= htmlspecialchars($row['remark']) ?></td>
                <td>
                    <?php if ($can_edit): ?>
                        <a href="?edit=<?= (int)$row['id'] ?>"
                           class="btn btn-sm btn-warning">✏️</a>
                    <?php endif; ?>
                    <?php if ($can_delete): ?>
                        <a href="?delete=<?= (int)$row['id'] ?>"
                           class="btn btn-sm btn-danger"
                           onclick="return confirm('ลบรายการนี้?')">🗑️</a>
                    <?php endif; ?>
                    <a href="print_qr.php?id=<?= (int)$row['id'] ?>"
                       class="btn btn-sm btn-secondary" target="_blank">QR</a>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>

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

<?php include 'template/footer.php'; ?>
