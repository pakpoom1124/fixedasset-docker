<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../login.php");
    exit;
}

require_once '../config.php';

$id = $_POST['id'] ?? '';
$item_no = $_POST['item_no'] ?? '';
$model = $_POST['model'] ?? '';
$serial_no = $_POST['serial_no'] ?? '';
$com_name = $_POST['com_name'] ?? '';
$receiver_name = $_POST['receiver_name'] ?? '';
$location_id = $_POST['location_id'] ?? null;
$asset_type_id = $_POST['asset_type_id'] ?? null;
$sign_date = $_POST['sign_date'] ?? null;
$doc_status = $_POST['doc_status'] ?? '';
$expected_return = $_POST['expected_return'] ?? null;
$actual_return = $_POST['actual_return'] ?? null;
$note = $_POST['note'] ?? '';

if ($id) {
    $sql = "UPDATE notebook_rentals SET 
        item_no=?, model=?, serial_no=?, com_name=?, receiver_name=?, location_id=?, asset_type_id=?,
        sign_date=?, doc_status=?, expected_return=?, actual_return=?, note=?
        WHERE id=?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        die("Prepare failed: " . $conn->error);
    }
    $stmt->bind_param("ssssssssssssi", $item_no, $model, $serial_no, $com_name, $receiver_name, $location_id, $asset_type_id, $sign_date, $doc_status, $expected_return, $actual_return, $note, $id);
} else {
    $sql = "INSERT INTO notebook_rentals 
        (item_no, model, serial_no, com_name, receiver_name, location_id, asset_type_id, sign_date, doc_status, expected_return, actual_return, note)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        die("Prepare failed: " . $conn->error);
    }
    $stmt->bind_param("ssssssssssss", $item_no, $model, $serial_no, $com_name, $receiver_name, $location_id, $asset_type_id, $sign_date, $doc_status, $expected_return, $actual_return, $note);
}

$stmt->execute();
header("Location: notebook_rental.php");
exit;
?>
