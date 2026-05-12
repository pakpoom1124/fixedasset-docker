<?php
session_start(); //ต้องอยู่บรรทัดแรกสุดของไฟล์เพื่อให้แน่ใจว่า session ถูกเริ่มต้นก่อนใช้งาน
include '../config.php';
include '../template/header2.php';
require_once '../vendor/autoload.php';


$user = $_SESSION['user'] ?? ['can_edit' => 0, 'can_delete' => 0];
$can_edit = $user['can_edit'] ?? 0;
$can_delete = $user['can_delete'] ?? 0;

// Filters
$search = $_GET['search'] ?? '';
$location_id = $_GET['location_id'] ?? '';
$asset_type_id = $_GET['asset_type_id'] ?? '';

$where = "WHERE 1=1";
if ($search !== '') {
    $safe = $conn->real_escape_string($search);
    $where .= " AND (
        n.code_id LIKE '%$safe%' OR n.name LIKE '%$safe%' OR n.ip_address LIKE '%$safe%' OR
        n.model LIKE '%$safe%' OR n.serial_no LIKE '%$safe%' OR l.name LIKE '%$safe%' OR
        t.name LIKE '%$safe%' OR n.remark LIKE '%$safe%'
    )";
}
if ($location_id !== '') {
    $where .= " AND n.location_id = " . intval($location_id);
}
if ($asset_type_id !== '') {
    $where .= " AND n.asset_type_id = " . intval($asset_type_id);
}

// Pagination
$limit = 50;
$page = max(1, intval($_GET['page'] ?? 1));
$offset = ($page - 1) * $limit;
$total = $conn->query("SELECT COUNT(*) AS c FROM network_assets n LEFT JOIN locations l ON n.location_id=l.id LEFT JOIN asset_types t ON n.asset_type_id=t.id $where")->fetch_assoc()['c'];
$total_pages = ceil($total / $limit);

// Dropdowns
$locations = $conn->query("SELECT * FROM locations");
//$types = $conn->query("SELECT * FROM asset_types");
$types = $conn->query("
    SELECT at.id, at.name, c.cat_name 
    FROM asset_types at 
    LEFT JOIN category c ON at.category_id = c.id 
    WHERE c.cat_name = 'Network'
    ORDER BY at.name ASC
");


// Form (Edit)
$id = $_GET['edit'] ?? '';
$edit = ['code_id'=>'','location_id'=>'','name'=>'','asset_type_id'=>'','ip_address'=>'','model'=>'','serial_no'=>'','note'=>''];
if ($id) {
    $res = $conn->query("SELECT * FROM network_assets WHERE id=" . intval($id));
    if ($res) $edit = $res->fetch_assoc();
    unset($edit['id']);
}

// Main data
$data = $conn->query("
    SELECT n.*, l.name AS location_name, t.name AS type_name
    FROM network_assets n
    LEFT JOIN locations l ON n.location_id = l.id
    LEFT JOIN asset_types t ON n.asset_type_id = t.id
    $where
    ORDER BY n.id ASC
    LIMIT $limit OFFSET $offset
");
?>
<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <title>Network Assets</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body>
<div class="container pt-2">
  <h3 class="mt-2 mb-3">Network</h3>

  <!-- Form -->
  <form method="post" action="save_network.php" class="row g-2 mb-3">
    <input type="hidden" name="id" value="<?= $id ?>">
    <?php
    foreach ($edit as $k => $v):
      if ($k === 'location_id'):
    ?>
      <div class="col-md-3">
        <label class="form-label">Location</label>
        <select name="location_id" class="form-select" required>
          <option value="">-- เลือก --</option>
          <?php mysqli_data_seek($locations, 0); while($loc = $locations->fetch_assoc()): ?>
          <option value="<?= $loc['id'] ?>" <?= ($v == $loc['id']) ? 'selected' : '' ?>><?= $loc['name'] ?></option>
          <?php endwhile ?>
        </select>
      </div>
    <?php elseif ($k === 'asset_type_id'): ?>
      <div class="col-md-3">
        <label class="form-label">Type</label>
        <select name="asset_type_id" class="form-select" required>
          <option value="">-- เลือก --</option>
          <?php mysqli_data_seek($types, 0); while($t = $types->fetch_assoc()): ?>
          <option value="<?= $t['id'] ?>" <?= ($v == $t['id']) ? 'selected' : '' ?>><?= $t['name'] ?></option>
          <?php endwhile ?>
        </select>
      </div>
    <?php else: ?>
      <div class="col-md-3">
        <label class="form-label"><?= ucfirst(str_replace('_', ' ', $k)) ?></label>
        <input name="<?= $k ?>" class="form-control" value="<?= htmlspecialchars($v) ?>">
      </div>
    <?php endif; endforeach ?>
    <div class="col-md-3 d-flex align-items-end">
      <button class="btn btn-success w-100">บันทึก</button>
    </div>
  </form>

  <a href="export_network_excel.php" class="btn btn-outline-primary mb-3">Export Excel</a>

  <!-- Filter form -->
  <form method="get" class="row mb-3 g-2">
    <div class="col-md-3">
      <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" class="form-control" placeholder="ค้นหาทุกช่อง...">
    </div>
    <div class="col-md-2">
      <select name="location_id" class="form-select">
        <option value="">-- สถานที่ --</option>
        <?php mysqli_data_seek($locations, 0); while($row = $locations->fetch_assoc()): ?>
        <option value="<?= $row['id'] ?>" <?= $location_id==$row['id']?'selected':'' ?>><?= $row['name'] ?></option>
        <?php endwhile ?>
      </select>
    </div>
    <div class="col-md-2">
      <select name="asset_type_id" class="form-select">
        <option value="">-- ประเภท --</option>
        <?php mysqli_data_seek($types, 0); while($row = $types->fetch_assoc()): ?>
        <option value="<?= $row['id'] ?>" <?= $asset_type_id==$row['id']?'selected':'' ?>><?= $row['name'] ?></option>
        <?php endwhile ?>
      </select>
    </div>
    <div class="col-md-2"><button class="btn btn-primary w-100">ค้นหา</button></div>
    <div class="col-md-2"><a href="network.php" class="btn btn-secondary w-100">ล้าง</a></div>
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
  <table class="table table-bordered table-sm">
    <thead class="table-light">
      <tr>
        <th>#</th><th>Code</th><th>Location</th><th>Name</th><th>Type</th>
        <th>IP</th><th>Model</th><th>Serial no</th><th>Note</th><th width="130">Action</th>
      </tr>
    </thead>
    <tbody>
      <?php $i = ($page - 1) * $limit + 1; ?>
      <?php while($row = $data->fetch_assoc()): ?>
      <tr>
        <td><?= $i++ ?></td>
        <td><?= htmlspecialchars($row['code_id']) ?></td>
        <td><?= htmlspecialchars($row['location_name']) ?></td>
		<td><?= htmlspecialchars($row['name']) ?></td>  
		<td><?= htmlspecialchars($row['type_name']) ?></td>                  
        <td><?= htmlspecialchars($row['ip_address']) ?></td>
        <td><?= htmlspecialchars($row['model']) ?></td>
        <td><?= htmlspecialchars($row['serial_no']) ?></td>
        <td><?= htmlspecialchars($row['remark']) ?></td>
        <td>
		  <?php if ($can_edit): ?>	
          <a href="?edit=<?= $row['id'] ?>" class="btn btn-sm btn-warning">✏️</a>
		  <?php endif ?>
		  <?php if ($can_delete): ?>	
          <a href="delete_network.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('ลบหรือไม่?')">🗑️</a>
		  <?php endif ?>
		  <a href="print_qr_network.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-secondary" target="_blank">QR</a>	
        </td>
      </tr>
      <?php endwhile ?>
    </tbody>
  </table>

  <!-- Pagination -->
  <nav><ul class="pagination">
    <?php for($i=1;$i<=$total_pages;$i++): ?>
    <li class="page-item <?= $i==$page?'active':'' ?>">
      <a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&location_id=<?= $location_id ?>&asset_type_id=<?= $asset_type_id ?>"><?= $i ?></a>
    </li>
    <?php endfor ?>
  </ul></nav>
</div>

<?php include '../template/footer.php'; ?>
	
