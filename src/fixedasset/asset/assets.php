<meta charset="UTF-8">
<?php
session_start();
require_once 'config.php';
require_once 'vendor/autoload.php';
include 'template/header.php';

use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\Writer\PngWriter;

if (!isset($_SESSION['user']) || !is_array($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}
$user = $_SESSION['user'];
$can_edit = $user['can_edit'] ?? 0;
$can_delete = $user['can_delete'] ?? 0;

// เพิ่ม / แก้ไข
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $can_edit) {
    $id = $_POST['id'] ?? 0;
    $fields = ['code_id','name','serialno','model','compname','startdate','expdate','status','remark','location_id','type_id','position_id'];
    $data = [];
    foreach ($fields as $f) {
        $data[$f] = $_POST[$f] ?? '';
    }

    if ($id) {
        $sql = "UPDATE assets SET code_id=?, name=?, serialno=?, model=?, compname=?, startdate=?, expdate=?, status=?, remark=?, location_id=?, type_id=?, position_id=? WHERE id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssssssssiiii", $data['code_id'], $data['name'], $data['serialno'], $data['model'], $data['compname'], $data['startdate'], $data['expdate'], $data['status'], $data['remark'], $data['location_id'], $data['type_id'], $data['position_id'], $id);
    } else {
        $sql = "INSERT INTO assets (code_id, name, serialno, model, compname, startdate, expdate, status, remark, location_id, type_id, position_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssssssssiii", $data['code_id'], $data['name'], $data['serialno'], $data['model'], $data['compname'], $data['startdate'], $data['expdate'], $data['status'], $data['remark'], $data['location_id'], $data['type_id'], $data['position_id']);
    }
    $stmt->execute();
    header("Location: assets.php");
    exit;
}

// ลบ
if (isset($_GET['delete']) && $can_delete) {
    $id = (int)$_GET['delete'];
    $conn->query("DELETE FROM assets WHERE id=$id");
    header("Location: assets.php");
    exit;
}

// ดึงข้อมูล
$edit = null;
if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    $edit = $conn->query("SELECT * FROM assets WHERE id=$id")->fetch_assoc();
}
$locations = $conn->query("SELECT * FROM locations");
$positions = $conn->query("SELECT * FROM positions");
$types = $conn->query("
    SELECT at.id, at.name FROM asset_types at 
    LEFT JOIN category c ON at.category_id = c.id 
    WHERE c.cat_name = 'Computer'
    ORDER BY at.name ASC
");

// Search & Filter
$search = $_GET['search'] ?? '';
$filter_location = $_GET['filter_location'] ?? '';
$filter_type = $_GET['filter_type'] ?? '';
$filter_position = $_GET['filter_position'] ?? '';

$where = [];
$params = [];
$paramTypes = "";

if ($filter_location !== '') {
    $where[] = "a.location_id = ?";
    $params[] = $filter_location;
    $paramTypes .= "i";
}
if ($filter_type !== '') {
    $where[] = "a.type_id = ?";
    $params[] = $filter_type;
    $paramTypes .= "i";
}
if ($filter_position !== '') {
    $where[] = "a.position_id = ?";
    $params[] = $filter_position;
    $paramTypes .= "i";
}
if ($search !== '') {
    $searchClause = "(a.code_id LIKE ? OR a.name LIKE ? OR a.serialno LIKE ? OR a.model LIKE ? OR a.compname LIKE ? OR a.status LIKE ? OR a.remark LIKE ?)";
    $where[] = $searchClause;
    for ($i = 0; $i < 7; $i++) {
        $params[] = "%$search%";
        $paramTypes .= "s";
    }
}

$whereSql = $where ? "WHERE " . implode(" AND ", $where) : "";

$limit = 50;
$page = max(1, intval($_GET['page'] ?? 1));
$offset = ($page - 1) * $limit;

$count_sql = "SELECT COUNT(*) as c FROM assets a $whereSql";
$stmt = $conn->prepare($count_sql);
if ($params) $stmt->bind_param($paramTypes, ...$params);
$stmt->execute();
$total = $stmt->get_result()->fetch_assoc()['c'];
$total_pages = ceil($total / $limit);

// Main data
$data_sql = "
    SELECT a.*, l.name AS location_name, t.name AS type_name, p.name AS position_name
    FROM assets a
    LEFT JOIN locations l ON a.location_id = l.id
    LEFT JOIN asset_types t ON a.type_id = t.id
    LEFT JOIN positions p ON a.position_id = p.id
    $whereSql
    ORDER BY a.id ASC
    LIMIT $limit OFFSET $offset
";
$stmt = $conn->prepare($data_sql);
if ($params) $stmt->bind_param($paramTypes, ...$params);
$stmt->execute();
$assets = $stmt->get_result();
?>

<div class="container mt-4">
    <h3>Computer</h3>

    <?php if ($can_edit): ?>
    <form method="post" class="row g-2 mb-3">
        <input type="hidden" name="id" value="<?= $edit['id'] ?? '' ?>">
        <?php
        $fields = [
            'code_id' => 'Code id', 'name' => 'ชื่อ', 'serialno' => 'Serial No.', 'model' => 'Model',
            'compname' => 'ชื่อเครื่อง', 'startdate' => 'วันเริ่มใช้', 'expdate' => 'วันหมดอายุ',
            'status' => 'สถานะ', 'remark' => 'หมายเหตุ'
        ];
        foreach ($fields as $f => $label): ?>
        <div class="col-md-3">
            <label><?= $label ?></label>
            <input type="<?= in_array($f, ['startdate','expdate']) ? 'date' : 'text' ?>" name="<?= $f ?>" class="form-control" value="<?= $edit[$f] ?? '' ?>">
        </div>
        <?php endforeach; ?>

        <div class="col-md-3">
            <label>แผนก</label>
            <select name="position_id" class="form-select" required>
                <option value="">-- เลือก --</option>
                <?php mysqli_data_seek($positions, 0); while($p = $positions->fetch_assoc()): ?>
                    <option value="<?= $p['id'] ?>" <?= ($edit['position_id'] ?? '') == $p['id'] ? 'selected' : '' ?>><?= $p['name'] ?></option>
                <?php endwhile; ?>
            </select>
        </div>

        <div class="col-md-3">
            <label>สถานที่</label>
            <select name="location_id" class="form-select" required>
                <option value="">-- เลือก --</option>
                <?php mysqli_data_seek($locations, 0); while($l = $locations->fetch_assoc()): ?>
                    <option value="<?= $l['id'] ?>" <?= ($edit['location_id'] ?? '') == $l['id'] ? 'selected' : '' ?>><?= $l['name'] ?></option>
                <?php endwhile; ?>
            </select>
        </div>

        <div class="col-md-3">
            <label>ประเภท</label>
            <select name="type_id" class="form-select" required>
                <option value="">-- เลือก --</option>
                <?php mysqli_data_seek($types, 0); while($t = $types->fetch_assoc()): ?>
                    <option value="<?= $t['id'] ?>" <?= ($edit['type_id'] ?? '') == $t['id'] ? 'selected' : '' ?>><?= $t['name'] ?></option>
                <?php endwhile; ?>
            </select>
        </div>

        <div class="col-md-3 d-flex align-items-end">
            <button class="btn btn-success w-100">บันทึก</button>
        </div>
    </form>
    <?php endif; ?>

    <!-- Filter -->
    <form class="row g-2 mb-3">
        <div class="col-md-3"><input type="text" name="search" value="<?= htmlspecialchars($search) ?>" class="form-control" placeholder="ค้นหา..."></div>
        <div class="col-md-3">
            <select name="filter_location" class="form-select">
                <option value="">-- สถานที่ --</option>
                <?php mysqli_data_seek($locations, 0); while($l = $locations->fetch_assoc()): ?>
                    <option value="<?= $l['id'] ?>" <?= $filter_location == $l['id'] ? 'selected' : '' ?>><?= $l['name'] ?></option>
                <?php endwhile; ?>
            </select>
        </div>
        <div class="col-md-3">
            <select name="filter_type" class="form-select">
                <option value="">-- ประเภท --</option>
                <?php mysqli_data_seek($types, 0); while($t = $types->fetch_assoc()): ?>
                    <option value="<?= $t['id'] ?>" <?= $filter_type == $t['id'] ? 'selected' : '' ?>><?= $t['name'] ?></option>
                <?php endwhile; ?>
            </select>
        </div>
        <div class="col-md-3">
            <select name="filter_position" class="form-select">
                <option value="">-- แผนก --</option>
                <?php mysqli_data_seek($positions, 0); while($p = $positions->fetch_assoc()): ?>
                    <option value="<?= $p['id'] ?>" <?= $filter_position == $p['id'] ? 'selected' : '' ?>><?= $p['name'] ?></option>
                <?php endwhile; ?>
            </select>
        </div>
        <div class="col-md-2 mt-2"><button class="btn btn-primary w-100">ค้นหา</button></div>
        <div class="col-md-2 mt-2"><a href="assets.php" class="btn btn-secondary w-100">ล้าง</a></div>
    </form>

    <table class="table table-bordered table-sm">
        <thead class="table-light">
            <tr>
                <th>#</th><th>Code</th><th>ชื่อ</th><th>แผนก</th><th>สถานที่</th><th>Serial</th><th>Model</th><th>ประเภท</th><th>เครื่อง</th><th>เริ่ม</th><th>หมดอายุ</th><th>สถานะ</th><th>หมายเหตุ</th><th width="130">จัดการ</th>
            </tr>
        </thead>
        <tbody>
            <?php $i = ($page - 1) * $limit + 1; ?>
            <?php while($row = $assets->fetch_assoc()): ?>
            <tr>
                <td><?= $i++ ?></td>
                <td><?= htmlspecialchars($row['code_id']) ?></td>
                <td><?= htmlspecialchars($row['name']) ?></td>
                <td><?= htmlspecialchars($row['position_name']) ?></td>
                <td><?= htmlspecialchars($row['location_name']) ?></td>
                <td><?= htmlspecialchars($row['serialno']) ?></td>
                <td><?= htmlspecialchars($row['model']) ?></td>
                <td><?= htmlspecialchars($row['type_name']) ?></td>
                <td><?= htmlspecialchars($row['compname']) ?></td>
                <td><?= $row['startdate'] ?></td>
                <td><?= $row['expdate'] ?></td>
                <td><?= $row['status'] ?></td>
                <td><?= $row['remark'] ?></td>
                <td>
                    <?php if ($can_edit): ?>
                        <a href="?edit=<?= $row['id'] ?>" class="btn btn-sm btn-warning">✏️</a>
                    <?php endif ?>
                    <?php if ($can_delete): ?>
                        <a href="?delete=<?= $row['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('ลบรายการนี้?')">🗑️</a>
                    <?php endif ?>
                    <a href="print_qr.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-secondary" target="_blank">QR</a>
                </td>
            </tr>
            <?php endwhile ?>
        </tbody>
    </table>

    <!-- Pagination -->
    <nav><ul class="pagination">
        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
            <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"><?= $i ?></a>
            </li>
        <?php endfor ?>
    </ul></nav>
</div>

<?php include 'template/footer.php'; ?>