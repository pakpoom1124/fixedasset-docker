<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../login.php");
    exit;
}
require_once '../config.php';
$id = $_GET['id'] ?? '';
if ($id) {
    $conn->query("DELETE FROM cashdrawer WHERE id=" . intval($id));
}
header("Location: cashdrawer.php");
exit;
?>