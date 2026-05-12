<?php
include '../config.php';
header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=notebook_rental_export.xlsx");
echo "<table border='1'><tr>
<th>Item No</th><th>Model</th><th>Serial No</th><th>Com Name</th><th>ชื่อผู้รับ</th>
<th>แผนก</th><th>วันที่เซ็นรับ</th><th>สถานะเอกสาร</th><th>วันที่คาดส่งคืน</th><th>วันที่ส่งจริง</th><th>หมายเหตุ</th></tr>";
$data = $conn->query("SELECT * FROM notebook_rentals");
while($r = $data->fetch_assoc()) {
  echo "<tr><td>{$r['item_no']}</td><td>{$r['model']}</td><td>{$r['serial_no']}</td><td>{$r['com_name']}</td>
  <td>{$r['receiver_name']}</td><td>{$r['positions']}</td><td>{$r['sign_date']}</td>
  <td>{$r['doc_status']}</td><td>{$r['expected_return']}</td><td>{$r['actual_return']}</td><td>{$r['note']}</td></tr>";
}
echo "</table>";
?>