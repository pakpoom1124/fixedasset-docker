<?php
include '../config.php';
$id = (int)($_GET['id'] ?? 0);
if ($id) $conn->query("DELETE FROM notebook_rentals WHERE id=$id");
header("Location: notebook_rental.php");
