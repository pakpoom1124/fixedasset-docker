<?php
include 'config.php';

$q = $_GET['q'] ?? '';
$location_id = $_GET['location_id'] ?? '';
$type_id = $_GET['type_id'] ?? '';
$position_id = $_GET['position_id'] ?? '';

$where = "WHERE (a.name LIKE ? OR a.serialno LIKE ? OR a.ip LIKE ?)";
$params = ["%$q%", "%$q%", "%$q%"];
$types = "sss";

if ($location_id) { $where .= " AND a.location_id=?"; $params[] = $location_id; $types .= "i"; }
if ($type_id) { $where .= " AND a.type_id=?"; $params[] = $type_id; $types .= "i"; }
if ($position_id) { $where .= " AND a.position_id=?"; $params[] = $position_id; $types .= "i"; }

$sql = "SELECT a.*, l.name as location_name, t.name as type_name, p.name as position_name
        FROM assets a
        LEFT JOIN locations l ON a.location_id = l.id
        LEFT JOIN asset_types t ON a.type_id = t.id
        LEFT JOIN positions p ON a.position_id = p.id
        $where";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=report_export.xls");
echo "<table border='1'><tr>
<th>ID</th><th>ชื่อ</th><th>Serial</th><th>IP</th><th>Model</th><th>เครื่อง</th><th>สถานะ</th><th>สถานที่</th><th>ประเภท</th><th>ตำแหน่ง</th><th>หมายเหตุ</th></tr>";
while($row = $result->fetch_assoc()) {
  echo "<tr>
  <td>{$row['id']}</td>
  <td>{$row['name']}</td>
  <td>{$row['serialno']}</td>
  <td>{$row['ip']}</td>
  <td>{$row['model']}</td>
  <td>{$row['compname']}</td>
  <td>{$row['status']}</td>
  <td>{$row['location_name']}</td>
  <td>{$row['type_name']}</td>
  <td>{$row['position_name']}</td>
  <td>{$row['remark']}</td>
  </tr>";
}
echo "</table>";
?>