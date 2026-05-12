<?php
session_start();
include 'db.php';

if (!isset($_GET['id'])) {
    echo "ไม่พบ Asset ID";
    exit;
}

$id = $_GET['id'];
$stmt = $conn->prepare("SELECT * FROM assets WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$asset = $result->fetch_assoc();

if (!$asset) {
    echo "ไม่พบข้อมูลสินทรัพย์";
    exit;
}

if (isset($_POST['update'])) {
    $fields = ['code_id', 'name', 'nameen', 'serialno', 'model', 'compname', 'startdate', 'expdate', 'ip', 'receiptdate', 'status', 'remark', 'location_id', 'type_id', 'position_id'];
    $data = [];
    foreach ($fields as $field) {
        $data[$field] = $_POST[$field];
    }

    $stmt = $conn->prepare("UPDATE assets SET code_id=?, name=?, nameen=?, serialno=?, model=?, compname=?, startdate=?, expdate=?, ip=?, receiptdate=?, status=?, remark=?, location_id=?, type_id=?, position_id=? WHERE id=?");
    $stmt->bind_param(
        "ssssssssssssiiii",
        $data['code_id'], $data['name'], $data['nameen'], $data['serialno'], $data['model'], $data['compname'],
        $data['startdate'], $data['expdate'], $data['ip'], $data['receiptdate'],
        $data['status'], $data['remark'],
        $data['location_id'], $data['type_id'], $data['position_id'], $id
    );
    $stmt->execute();
    header("Location: assets.php");
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
    <?php foreach ($asset as $key => $value): if (in_array($key, ['id'])) continue; ?>
        <?php if (in_array($key, ['location_id', 'type_id', 'position_id'])) continue; ?>
        <label><?= $key ?>:
            <input type="text" name="<?= $key ?>" value="<?= htmlspecialchars($value) ?>">
        </label><br>
    <?php endforeach; ?>

    <label>Location:
        <select name="location_id">
            <?php while ($row = $locations->fetch_assoc()): ?>
                <option value="<?= $row['id'] ?>" <?= $row['id'] == $asset['location_id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($row['name']) ?>
                </option>
            <?php endwhile; ?>
        </select>
    </label><br>

    <label>Type:
        <select name="type_id">
            <?php while ($row = $types->fetch_assoc()): ?>
                <option value="<?= $row['id'] ?>" <?= $row['id'] == $asset['type_id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($row['name']) ?>
                </option>
            <?php endwhile; ?>
        </select>
    </label><br>

    <label>Position:
        <select name="position_id">
            <?php while ($row = $positions->fetch_assoc()): ?>
                <option value="<?= $row['id'] ?>" <?= $row['id'] == $asset['position_id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($row['name']) ?>
                </option>
            <?php endwhile; ?>
        </select>
    </label><br><br>

    <button type="submit" name="update">บันทึก</button>
</form>
</body>
</html>
