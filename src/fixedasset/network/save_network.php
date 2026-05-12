<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../login.php");
    exit;
}
require_once '../config.php';

$id = $_POST['id'] ?? '';
$fields = ['code_id', 'location_id', 'name', 'asset_type_id', 'ip_address', 'model', 'serial_no', 'remark'];
$data = [];
foreach ($fields as $f) {
    $data[$f] = $_POST[$f] ?? '';
}

if ($id) {
    $sql = "UPDATE network_assets SET code_id=?, location_id=?, name=?, asset_type_id=?, ip_address=?, model=?, serial_no=?, remark=? WHERE id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sisissssi", $data['code_id'], $data['location_id'], $data['name'], $data['asset_type_id'], $data['ip_address'], $data['model'], $data['serial_no'], $data['remark'], $id);
} else {
    $sql = "INSERT INTO network_assets (code_id, location_id, name, asset_type_id, ip_address, model, serial_no, remark)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sisissss", $data['code_id'], $data['location_id'], $data['name'], $data['asset_type_id'], $data['ip_address'], $data['model'], $data['serial_no'], $data['remark']);
}
$stmt->execute();
header("Location: network.php");
exit;
?>
