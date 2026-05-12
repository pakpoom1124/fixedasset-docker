<?php
session_start();
require_once '../config.php';

$id = intval($_GET['id'] ?? 0);

if ($id) {
    // หาไฟล์รูปก่อน
    $res = $conn->query("SELECT image FROM writeoff WHERE id=$id");
    if ($res && $row = $res->fetch_assoc()) {
        $file = __DIR__ . '/../img/' . $row['image'];
        if ($row['image'] && file_exists($file)) {
            @unlink($file);
        }
    }
    $conn->query("DELETE FROM writeoff WHERE id=$id");
}

header("Location: writeoff.php");
exit;
