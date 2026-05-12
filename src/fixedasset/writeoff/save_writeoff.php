<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../login.php");
    exit;
}
require_once '../config.php';

$id = $_POST['id'] ?? '';

// ✅ 1. นำ expdate ออกจากรายการ fields
$fields = ['code_id', 'location_id', 'asset_type_id', 'name', 'monitor', 'model', 'serial_no', 'remark', 'startdate'];
$data = [];
foreach ($fields as $f) {
    $data[$f] = $_POST[$f] ?? '';
}

// ✅ 2. ลบตัวแปร $expdate ออก
$startdate = !empty($data['startdate']) ? $data['startdate'] : null;

$image_name = '';
$new_image_uploaded = false;

if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
    $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'gif'];

    if (in_array($ext, $allowed)) {
        $image_name = time() . '_' . uniqid() . '.' . $ext;
        $target = __DIR__ . '/../img/' . $image_name;

        if (move_uploaded_file($_FILES['image']['tmp_name'], $target)) {
            $new_image_uploaded = true;
        }
    }
}

if ($id) {
    // กรณีแก้ไขข้อมูล (UPDATE)
    if ($new_image_uploaded) {
        // ✅ 3. ลบ expdate=? ออกจาก SQL (เหลือ 11 parameters)
        $sql = "UPDATE writeoff 
                   SET code_id=?, name=?, location_id=?, asset_type_id=?, 
                       monitor=?, model=?, serial_no=?, remark=?, image=?, startdate=?
                 WHERE id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param(
            "ssiissssssi", // s(1),s(2),i(3),i(4),s(5),s(6),s(7),s(8),s(9),s(10),i(11)
            $data['code_id'],
            $data['name'],
            $data['location_id'],
            $data['asset_type_id'],
            $data['monitor'],
            $data['model'],
            $data['serial_no'],
            $data['remark'],
            $image_name,
            $startdate,
            $id
        );
    } else {
        // ✅ 4. ลบ expdate=? ออกจาก SQL (เหลือ 10 parameters)
        $sql = "UPDATE writeoff 
                   SET code_id=?, name=?, location_id=?, asset_type_id=?, 
                       monitor=?, model=?, serial_no=?, remark=?, startdate=?
                 WHERE id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param(
            "ssiisssssi", // s(1),s(2),i(3),i(4),s(5),s(6),s(7),s(8),s(9),i(10)
            $data['code_id'],
            $data['name'],
            $data['location_id'],
            $data['asset_type_id'],
            $data['monitor'],
            $data['model'],
            $data['serial_no'],
            $data['remark'],
            $startdate,
            $id
        );
    }
} else {
    // กรณีเพิ่มข้อมูลใหม่ (INSERT)
    // ✅ 5. ลบคอลัมน์ expdate และเครื่องหมาย ? ออก (เหลือ 10 parameters)
    $sql = "INSERT INTO writeoff 
                (code_id, location_id, asset_type_id, name, monitor, model, serial_no, remark, image, startdate)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param(
        "siisssssss",
        $data['code_id'],
        $data['location_id'],
        $data['asset_type_id'],
        $data['name'],
        $data['monitor'],
        $data['model'],
        $data['serial_no'],
        $data['remark'],
        $image_name,
        $startdate
    );
}

if ($stmt->execute()) {
    header("Location: writeoff.php?msg=success");
} else {
    echo "Error: " . $stmt->error;
}
?>