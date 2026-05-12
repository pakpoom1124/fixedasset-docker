<?php
require_once '../config.php';
header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=network_assets_export.xlsx");

echo "ID	Code	Location	Name	Type	IP	Model	Serial	Remark\n";

$result = $conn->query("
    SELECT n.*, l.name as location_name, t.name as type_name
    FROM network_assets n
    LEFT JOIN locations l ON n.location_id = l.id
    LEFT JOIN asset_types t ON n.asset_type_id = t.id
");
while ($row = $result->fetch_assoc()) {
    echo "{$row['id']}	{$row['code_id']}	{$row['location_name']}	{$row['name']}	{$row['type_name']}	{$row['ip_address']}	{$row['model']}	{$row['serial_no']}	{$row['remark']}\n";
}
?>
