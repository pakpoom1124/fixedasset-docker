<?php
session_start();
include 'db.php';

if (!isset($_GET['id'])) {
    echo "ไม่พบรหัสสินทรัพย์";
    exit;
}

$id = $_GET['id'];
$stmt = $conn->prepare("DELETE FROM assets WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();

header("Location: assets.php");
exit;
?>
