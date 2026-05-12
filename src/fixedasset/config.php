<?php
$host = 'db';
$db   = 'asset_db';
$user = 'root';
$pass = 'HiNara@123';

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}

$conn->set_charset('utf8mb4');