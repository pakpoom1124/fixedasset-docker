<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../login.php");
    exit;
}
require_once '../config.php';
$id = $_GET['id'] ?? '';
if ($id) {
    $conn->query("DELETE FROM mobile WHERE id=" . intval($id));
}
header("Location: mobile.php");
exit;
?>