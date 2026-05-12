<?php
session_start();
include 'db.php'; // สมมติว่าไฟล์นี้ยังอยู่ที่เดิม

if (!isset($_GET['id'])) {
    echo "ไม่พบ Asset ID";
    exit;
}

$id = $_GET['id'];
$stmt = $conn->prepare("SELECT * FROM mercado WHERE id = ?"); // เปลี่ยนเป็น mercado
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$asset = $result->fetch_assoc();

if (!$asset) {
    echo "ไม่พบข้อมูลสินทรัพย์";
    exit;
}

if (isset($_POST['update'])) {
    // เพิ่ม 'image' เข้าไปใน fields และเอา 'nameen', 'ip', 'receiptdate' ออกเพื่อให้สอดคล้องกับ assets.php เดิม + image
    $fields = ['code_id', 'name', 'serialno', 'model', 'compname', 'startdate', 'expdate', 'status', 'remark', 'location_id', 'type_id', 'position_id', 'image']; 
    $data = [];
    foreach ($fields as $field) {
        $data[$field] = $_POST[$field] ?? null;
    }

    // เตรียมชนิดตัวแปร: sssssssssiiiis (12 strings, 3 integers)
    $stmt = $conn->prepare("UPDATE mercado SET code_id=?, name=?, serialno=?, model=?, compname=?, startdate=?, expdate=?, status=?, remark=?, location_id=?, type_id=?, position_id=?, image=? WHERE id=?"); // เปลี่ยนเป็น mercado และเพิ่ม image
    $stmt->bind_param(
        "sssssssssiiiisi",
        $data['code_id'], $data['name'], $data['serialno'], $data['model'], $data['compname'],
        $data['startdate'], $data['expdate'],
        $data['status'], $data['remark'],
        $data['location_id'], $data['type_id'], $data['position_id'],
        $data['image'], // เพิ่ม image
        $id
    );
    $stmt->execute();
    header("Location: mercado.php"); // เปลี่ยนเป็น mercado.php
    exit;
}

// ดึง dropdown
$locations = $conn->query("SELECT id, name FROM locations");
$types = $conn->query("SELECT id, name FROM asset_types");
$positions = $conn->query("SELECT id, name FROM positions");
?>

<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"><title>Edit Asset</title></head>
<body>
<h2>แก้ไขข้อมูลสินทรัพย์</h2>
<form method="post">
    <?php 
    // ฟิลด์หลักที่ต้องการแสดงในฟอร์ม
    $form_fields = ['code_id', 'name', 'serialno', 'model', 'compname', 'startdate', 'expdate', 'status', 'remark', 'image']; 
    foreach ($form_fields as $key): 
        // เช็คว่า $asset มี key นั้นหรือไม่ เพื่อป้องกัน error
        $value = $asset[$key] ?? ''; 
    ?>
        <label><?= $key ?>:
            <input type="text" name="<?= $key ?>" value="<?= htmlspecialchars($value) ?>">
        </label><br>
    <?php endforeach; ?>

    <label>Location:
        <select name="location_id">
            <?php mysqli_data_seek($locations, 0); while ($row = $locations->fetch_assoc()): ?>
                <option value="<?= $row['id'] ?>" <?= ($row['id'] == $asset['location_id']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($row['name']) ?>
                </option>
            <?php endwhile; ?>
        </select>
    </label><br>

    <label>Type:
        <select name="type_id">
            <?php mysqli_data_seek($types, 0); while ($row = $types->fetch_assoc()): ?>
                <option value="<?= $row['id'] ?>" <?= ($row['id'] == $asset['type_id']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($row['name']) ?>
                </option>
            <?php endwhile; ?>
        </select>
    </label><br>

    <label>Position:
        <select name="position_id">
            <?php mysqli_data_seek($positions, 0); while ($row = $positions->fetch_assoc()): ?>
                <option value="<?= $row['id'] ?>" <?= ($row['id'] == $asset['position_id']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($row['name']) ?>
                </option>
            <?php endwhile; ?>
        </select>
    </label><br><br>

    <button type="submit" name="update">บันทึก</button>
</form>
</body>
</html>