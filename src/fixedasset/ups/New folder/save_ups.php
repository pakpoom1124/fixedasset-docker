<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../login.php");
    exit;
}
require_once '../config.php';

$id = $_POST['id'] ?? '';
$fields = ['code_id', 'location_id', 'model', 'serial_no', 'remark'];
$data = [];
foreach ($fields as $f) {
    $data[$f] = $_POST[$f] ?? '';
}

if ($id) {
    $sql = "UPDATE ups SET code_id=?, location_id=?, model=?, serial_no=?, remark=? WHERE id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sisssi", $data['code_id'], $data['location_id'], $data['model'], $data['serial_no'], $data['remark'], $id);
} else {
    $sql = "INSERT INTO ups (code_id, location_id, model, serial_no, remark)
            VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sisss", $data['code_id'], $data['location_id'], $data['model'], $data['serial_no'], $data['remark']);
}
$stmt->execute();
header("Location: ups.php");
exit;
?>
