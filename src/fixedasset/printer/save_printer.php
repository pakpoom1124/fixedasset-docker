<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../login.php");
    exit;
}
require_once '../config.php';

$id = $_POST['id'] ?? '';
$fields = ['code_id', 'location_id', 'name', 'asset_type_id', 'firstname', 'ip_address', 'model', 'serial_no', 'remark'];
$data = [];
foreach ($fields as $f) {
    $data[$f] = $_POST[$f] ?? '';
}

if ($id) {
    $sql = "UPDATE printer SET code_id=?, location_id=?, name=?, asset_type_id=?, firstname=?, ip_address=?, model=?, serial_no=?, remark=? WHERE id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sisisssssi", $data['code_id'], $data['location_id'], $data['name'], $data['asset_type_id'], $data['firstname'], $data['ip_address'], $data['model'], $data['serial_no'], $data['remark'], $id);
} else {
    $sql = "INSERT INTO printer (code_id, location_id, name, asset_type_id, firstname, ip_address, model, serial_no, remark)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sisisssss", $data['code_id'], $data['location_id'], $data['name'], $data['asset_type_id'], $data['firstname'], $data['ip_address'], $data['model'], $data['serial_no'], $data['remark']);
}
$stmt->execute();
header("Location: printer.php");
exit;
?>
