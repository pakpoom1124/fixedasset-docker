<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../login.php");
    exit;
}
require_once '../config.php';
$id = $_GET['id'] ?? '';
if ($id) {
    $conn->query("DELETE FROM network_assets WHERE id=" . intval($id));
}
header("Location: network.php");
exit;
?>
