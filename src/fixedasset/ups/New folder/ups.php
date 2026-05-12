<?php
include '../config.php';
include '../template/header2.php';
require_once '../vendor/autoload.php';

session_start();
$user = $_SESSION['user'] ?? ['can_edit' => 0, 'can_delete' => 0];
$can_edit = $user['can_edit'] ?? 0;
$can_delete = $user['can_delete'] ?? 0;

// Filter & search
$search = $_GET['search'] ?? '';
$location_id = $_GET['location_id'] ?? '';

$where = "WHERE 1=1";
if ($search !== '') {
    $safe = $conn->real_escape_string($search);
    $where .= " AND (
        n.code_id LIKE '%$safe%' OR n.model LIKE '%$safe%' OR n.serial_no LIKE '%$safe%' OR 
        l.name LIKE '%$safe%' OR n.remark LIKE '%$safe%'
    )";
}
if ($location_id !== '') $where .= " AND n.location_id = " . intval($location_id);

// pagination
$limit = 50;
$page = max(1, intval($_GET['page'] ?? 1));
$offset = ($page - 1) * $limit;
$total = $conn->query("SELECT COUNT(*) AS c FROM ups n LEFT JOIN locations l ON n.location_id=l.id $where")->fetch_assoc()['c'];
$total_pages = ceil($total / $limit);

// Dropdown data
$locations = $conn->query("SELECT * FROM locations");

// Edit form
$id = $_GET['edit'] ?? '';
$edit = ['code_id'=>'','location_id'=>'','model'=>'','serial_no'=>'','note'=>''];
if ($id) {
    $res = $conn->query("SELECT * FROM ups WHERE id=" . intval($id));
    if ($res) $edit = $res->fetch_assoc();
    unset($edit['id']);
}

// Main query
$data = $conn->query("
    SELECT n.*, l.name AS location_name
    FROM ups n
    LEFT JOIN locations l ON n.location_id = l.id
    $where
    ORDER BY n.id ASC
    LIMIT $limit OFFSET $offset
");
?>
<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <title>UPS Assets</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body>
<div class="container py-3">
  <h3 class="mb-3">UPS</h3>

  <?php if ($can_edit): ?>
  <form method="post" action="save_ups.php" class="row g-2 mb-3">
    <input type="hidden" name="id" value="<?= $id ?>">
    <?php foreach ($edit as $k => $v): ?>
      <?php if ($k === 'location_id'): ?>
        <div class="col-md-3">
          <label class="form-label">Location</label>
          <select name="location_id" class="form-select" required>
            <option value="">-- เลือก --</option>
            <?php mysqli_data_seek($locations, 0); while($loc = $locations->fetch_assoc()): ?>
            <option value="<?= $loc['id'] ?>" <?= ($v == $loc['id']) ? 'selected' : '' ?>><?= $loc['name'] ?></option>
            <?php endwhile ?>
          </select>
        </div>
      <?php else: ?>
        <div class="col-md-3">
          <label class="form-label"><?= ucfirst(str_replace('_', ' ', $k)) ?></label>
          <input name="<?= $k ?>" class="form-control" value="<?= htmlspecialchars($v) ?>">
        </div>
      <?php endif ?>
    <?php endforeach ?>
    <div class="col-md-3 d-flex align-items-end">
      <button class="btn btn-success w-100">บันทึก</button>
    </div>
  </form>
  <?php endif; ?>

  <a href="export_ups_excel.php" class="btn btn-outline-primary mb-3">Export Excel</a>

  <!-- Filter -->
  <form method="get" class="row mb-3 g-2">
    <div class="col-md-3"><input type="text" name="search" value="<?= htmlspecialchars($search) ?>" class="form-control" placeholder="ค้นหาทุกช่อง..."></div>
    <div class="col-md-3">
      <select name="location_id" class="form-select">
        <option value="">-- สถานที่ --</option>
        <?php mysqli_data_seek($locations, 0); while($row = $locations->fetch_assoc()): ?>
        <option value="<?= $row['id'] ?>" <?= $location_id==$row['id']?'selected':'' ?>><?= $row['name'] ?></option>
        <?php endwhile ?>
      </select>
    </div>
    <div class="col-md-2"><button class="btn btn-primary w-100">ค้นหา</button></div>
    <div class="col-md-2"><a href="ups.php" class="btn btn-secondary w-100">ล้าง</a></div>
  </form>

  <!-- Pagination -->
  <nav><ul class="pagination">
    <?php for($i=1;$i<=$total_pages;$i++): ?>
      <li class="page-item <?= $i==$page?'active':'' ?>">
        <a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&location_id=<?= $location_id ?>&asset_type_id=<?= $asset_type_id ?>"><?= $i ?></a>
      </li>
    <?php endfor ?>
  </ul></nav>	
	
	
  <!-- Table -->
  <table class="table table-bordered table-sm align-middle">
    <thead class="table-light">
      <tr>
        <th>#</th><th>Code</th><th>Location</th><th>Model</th>
        <th>Serial no</th><th>Note</th><th width="130">Action</th>
      </tr>
    </thead>
    <tbody>
      <?php $i = ($page - 1) * $limit + 1; ?>
      <?php while($row = $data->fetch_assoc()): ?>
      <tr>
        <td><?= $i++ ?></td>
        <td><?= htmlspecialchars($row['code_id']) ?></td>
        <td><?= htmlspecialchars($row['location_name']) ?></td>
        <td><?= htmlspecialchars($row['model']) ?></td>
        <td><?= htmlspecialchars($row['serial_no']) ?></td>
        <td><?= htmlspecialchars($row['remark']) ?></td>
        <td>
          <?php if ($can_edit): ?>
            <a href="?edit=<?= $row['id'] ?>" class="btn btn-sm btn-warning">✏️</a>
          <?php endif ?>
          <?php if ($can_delete): ?>
            <a href="delete_ups.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('ลบหรือไม่?')">🗑️</a>
          <?php endif ?>
          <a href="print_qr_ups.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-secondary" target="_blank">QR</a>
        </td>
      </tr>
      <?php endwhile ?>
    </tbody>
  </table>

  <!-- Pagination -->
  <nav><ul class="pagination">
    <?php for($i=1;$i<=$total_pages;$i++): ?>
      <li class="page-item <?= $i==$page?'active':'' ?>">
        <a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&location_id=<?= $location_id ?>"><?= $i ?></a>
      </li>
    <?php endfor ?>
  </ul></nav>
</div>
<?php include '../template/footer.php'; ?>
