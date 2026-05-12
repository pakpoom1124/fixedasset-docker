<?php
session_start();
include 'config.php';
include 'template/header.php';

// Filters
$q = $_GET['q'] ?? '';
$location_id = $_GET['location_id'] ?? '';
$type_id = $_GET['type_id'] ?? '';
$position_id = $_GET['position_id'] ?? '';

// Sorting and pagination
$sort = $_GET['sort'] ?? 'id';
$order = $_GET['order'] ?? 'asc';
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 50;
$offset = ($page - 1) * $limit;

$allowed_sort = ['id', 'name', 'serialno', 'ip'];
$allowed_order = ['asc', 'desc'];
$sort = in_array($sort, $allowed_sort) ? $sort : 'id';
$order = in_array($order, $allowed_order) ? $order : 'desc';

// Build WHERE clause
$where = "WHERE (a.name LIKE ? OR a.serialno LIKE ? OR a.ip LIKE ?)";
$params = ["%$q%", "%$q%", "%$q%"];
$types = "sss";

if ($location_id) { $where .= " AND a.location_id=?"; $params[] = $location_id; $types .= "i"; }
if ($type_id) { $where .= " AND a.type_id=?"; $params[] = $type_id; $types .= "i"; }
if ($position_id) { $where .= " AND a.position_id=?"; $params[] = $position_id; $types .= "i"; }

// Count total
$count_sql = "SELECT COUNT(*) FROM assets a $where";
$count_stmt = $conn->prepare($count_sql);
$count_stmt->bind_param($types, ...$params);
$count_stmt->execute();
$count_stmt->bind_result($total);
$count_stmt->fetch();
$count_stmt->close();
$total_pages = ceil($total / $limit);

// Main query
$sql = "SELECT a.*, l.name as location_name, t.name as type_name, p.name as position_name 
        FROM assets a
        LEFT JOIN locations l ON a.location_id = l.id
        LEFT JOIN asset_types t ON a.type_id = t.id
        LEFT JOIN positions p ON a.position_id = p.id
        $where
        ORDER BY a.$sort $order
        LIMIT $limit OFFSET $offset";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$assets = $stmt->get_result();

// Load dropdown options
$locations = $conn->query("SELECT * FROM locations");
$types_list = $conn->query("SELECT * FROM asset_types");
$positions = $conn->query("SELECT * FROM positions");
?>
<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <title>รายงานครุภัณฑ์</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="container mt-4">
  <h3>รายงานครุภัณฑ์</h3>
  <form method="get" class="row g-2 mb-3">
    <div class="col-md-3"><input type="text" name="q" value="<?= htmlspecialchars($q) ?>" class="form-control" placeholder="ค้นหา (ชื่อ, Serial, IP)"></div>
    <div class="col-md-2">
      <select name="location_id" class="form-select">
        <option value="">-- สถานที่ --</option>
        <?php while($r = $locations->fetch_assoc()): ?>
          <option value="<?= $r['id'] ?>" <?= $location_id == $r['id'] ? 'selected' : '' ?>><?= $r['name'] ?></option>
        <?php endwhile ?>
      </select>
    </div>
    <div class="col-md-2">
      <select name="type_id" class="form-select">
        <option value="">-- ประเภท --</option>
        <?php while($r = $types_list->fetch_assoc()): ?>
          <option value="<?= $r['id'] ?>" <?= $type_id == $r['id'] ? 'selected' : '' ?>><?= $r['name'] ?></option>
        <?php endwhile ?>
      </select>
    </div>
    <div class="col-md-2">
      <select name="position_id" class="form-select">
        <option value="">-- ตำแหน่ง --</option>
        <?php while($r = $positions->fetch_assoc()): ?>
          <option value="<?= $r['id'] ?>" <?= $position_id == $r['id'] ? 'selected' : '' ?>><?= $r['name'] ?></option>
        <?php endwhile ?>
      </select>
    </div>
    <div class="col-md-3 d-flex">
      <button class="btn btn-primary me-2">ค้นหา</button>
      <a href="report_export.php?<?= http_build_query($_GET) ?>" class="btn btn-success">Export Excel</a>
      <a href="report_print.php?<?= http_build_query($_GET) ?>" target="_blank" class="btn btn-outline-secondary ms-2">พิมพ์</a>
    </div>
  </form>

  <table class="table table-bordered table-sm">
    <thead class="table-light">
      <tr>
        <th><a href="?<?= http_build_query(array_merge($_GET, ['sort'=>'id','order'=>$order==='asc'?'desc':'asc'])) ?>">ID</a></th>
        <th><a href="?<?= http_build_query(array_merge($_GET, ['sort'=>'name','order'=>$order==='asc'?'desc':'asc'])) ?>">ชื่อ</a></th>
        <th><a href="?<?= http_build_query(array_merge($_GET, ['sort'=>'serialno','order'=>$order==='asc'?'desc':'asc'])) ?>">Serial</a></th>
        <?php /*?><th><a href="?<?= http_build_query(array_merge($_GET, ['sort'=>'ip','order'=>$order==='asc'?'desc':'asc'])) ?>">IP</a></th><?php */?>
        <th>Model</th><th>เครื่อง</th><th>สถานะ</th><th>สถานที่</th><th>ประเภท</th><th>ตำแหน่ง</th><th>หมายเหตุ</th>
      </tr>
    </thead>
    <tbody>
      <?php while($row = $assets->fetch_assoc()): ?>
      <tr>
        <td><?= $row['id'] ?></td>
        <td><?= $row['name'] ?></td>
        <td><?= $row['serialno'] ?></td>
        <?php /*?><td><?= $row['ip'] ?></td><?php */?>
        <td><?= $row['model'] ?></td>
        <td><?= $row['compname'] ?></td>
        <td><?= $row['status'] ?></td>
        <td><?= $row['location_name'] ?></td>
        <td><?= $row['type_name'] ?></td>
        <td><?= $row['position_name'] ?></td>
        <td><?= $row['remark'] ?></td>
      </tr>
      <?php endwhile ?>
    </tbody>
  </table>

  <!-- Pagination -->
  <nav>
    <ul class="pagination">
      <?php for ($i = 1; $i <= $total_pages; $i++): ?>
      <li class="page-item <?= $i == $page ? 'active' : '' ?>">
        <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page'=>$i])) ?>"><?= $i ?></a>
      </li>
      <?php endfor ?>
    </ul>
  </nav>
</body>
</html>
