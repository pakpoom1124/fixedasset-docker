<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../login.php");
    exit;
}
require_once '../config.php';

$id = $_POST['id'] ?? '';

$fields = ['code_id', 'location_id', 'asset_type_id', 'model', 'serial_no', 'remark'];
$data = [];
foreach ($fields as $f) {
    $data[$f] = $_POST[$f] ?? '';
}

$image_name = '';
if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
    $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'gif'];

    if (in_array($ext, $allowed)) {
        $image_name = time() . '_' . uniqid() . '.' . $ext;
        $target = __DIR__ . '/../img/' . $image_name;

        if (!move_uploaded_file($_FILES['image']['tmp_name'], $target)) {
            $image_name = '';
        }
    }
}


if ($id) {
    if ($image_name) {
        $sql = "UPDATE mobile 
                   SET code_id=?, location_id=?, asset_type_id=?, model=?, serial_no=?, remark=?, image=? 
                 WHERE id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param(
            "siissssi",
            $data['code_id'],
            $data['location_id'],
            $data['asset_type_id'],
            $data['model'],
            $data['serial_no'],
            $data['remark'],
            $image_name,
            $id
        );
    } else {
        $sql = "UPDATE mobile 
                   SET code_id=?, location_id=?, asset_type_id=?, model=?, serial_no=?, remark=? 
                 WHERE id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param(
            "siisssi",
            $data['code_id'],
            $data['location_id'],
            $data['asset_type_id'],
            $data['model'],
            $data['serial_no'],
            $data['remark'],
            $id
        );
    }
} else {
    $sql = "INSERT INTO mobile 
                (code_id, location_id, asset_type_id, model, serial_no, remark, image)
            VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param(
        "siissss",
        $data['code_id'],
        $data['location_id'],
        $data['asset_type_id'],
        $data['model'],
        $data['serial_no'],
        $data['remark'],
        $image_name
    );
}

$stmt->execute();
header("Location: mobile.php");
exit;
?>