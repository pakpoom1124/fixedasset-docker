<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../login.php");
    exit;
}
require_once '../config.php';

$id = $_POST['id'] ?? '';
$fields = ['code_id', 'location_id', 'name', 'asset_type_id', 'model', 'serial_no', 'remark'];
$data = [];
foreach ($fields as $f) {
    $data[$f] = $_POST[$f] ?? '';
}

if ($id) {
    $sql = "UPDATE cashdrawer SET code_id=?, location_id=?, name=?, asset_type_id=?, model=?, serial_no=?, remark=? WHERE id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sisisssi", $data['code_id'], $data['location_id'], $data['name'], $data['asset_type_id'], $data['model'], $data['serial_no'], $data['remark'], $id);
} else {
    $sql = "INSERT INTO cashdrawer (code_id, location_id, name, asset_type_id, model, serial_no, remark)
            VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sisisss", $data['code_id'], $data['location_id'], $data['name'], $data['asset_type_id'], $data['model'], $data['serial_no'], $data['remark']);
}
$stmt->execute();
header("Location: cashdrawer.php");
exit;
?>
