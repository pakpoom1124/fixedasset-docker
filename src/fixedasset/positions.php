<?php 
// [FIX] Start session และ process redirect ก่อน include header.php
// เพราะ header.php มี HTML output
session_start();

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

include 'config.php';

// [FIX] กำหนดค่าเริ่มต้นให้ตัวแปร edit
// ถ้าเปิดหน้า Add ปกติ จะยังไม่มี $_GET['edit']
// ถ้าไม่กำหนดไว้ PHP 8 จะแจ้ง Warning: Undefined variable
$edit_id = isset($_GET['edit']) ? intval($_GET['edit']) : 0;
$edit_row = null;

// บันทึกข้อมูลเมื่อกด Save
if (isset($_POST['save'])) {
    $id = intval($_POST['id']);
    $name = $conn->real_escape_string($_POST['name']);
    if ($id > 0) {
        $conn->query("UPDATE positions SET name='$name' WHERE id=$id");
    } else {
        $conn->query("INSERT INTO positions (name) VALUES ('$name')");
    }
    header("Location: positions.php");
    exit;
}

// ลบข้อมูล
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $conn->query("DELETE FROM positions WHERE id=$id");
    header("Location: positions.php");
    exit;
}

// ดึงข้อมูลสำหรับแก้ไข
if ($edit_id > 0) {
    $res = $conn->query("SELECT * FROM positions WHERE id=$edit_id");
    if ($res && $res->num_rows > 0) {
        $edit_row = $res->fetch_assoc();
    }
}

// [FIX] Include header หลังจาก Add/Edit/Delete logic เสร็จแล้ว
include 'template/header.php';
?>

<div class="container mt-4">
    <h4>Department</h4>

    <!-- ฟอร์มเพิ่ม/แก้ไข -->
    <form method="post" class="mb-4">
        <input type="hidden" name="id" value="<?= $edit_row ? $edit_row['id'] : 0 ?>">
        <div class="mb-2">
            <input name="name" placeholder="ชื่อแผนก" value="<?= $edit_row ? htmlspecialchars($edit_row['name']) : '' ?>" required class="form-control">
        </div>
        <button type="submit" name="save" class="btn btn-primary">
            <?= $edit_row ? 'อัปเดต' : 'เพิ่ม' ?>
        </button>
        <?php if ($edit_row): ?>
            <a href="positions.php" class="btn btn-secondary">ยกเลิก</a>
        <?php endif; ?>
    </form>

    <!-- ตารางข้อมูลตำแหน่ง -->
    <table class="table table-bordered table-sm">
        <thead>
            <tr>
                <th>ID</th>
                <th>แผนก</th>
                <th>การจัดการ</th>
            </tr>
        </thead>
        <tbody>
        <?php 
        $result = $conn->query("SELECT * FROM positions ORDER BY id ASC");
        while ($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?= $row['id'] ?></td>
                <td><?= htmlspecialchars($row['name']) ?></td>
                <td>
                    <!--<a href="" class="btn btn-sm btn-warning">แก้ไข</a>
                    <a href="" class="btn btn-sm btn-danger" onclick="return confirm('ลบตำแหน่งนี้หรือไม่?')">ลบ</a>-->
					
					<a href="?edit=<?= $row['id'] ?>" class="btn btn-sm btn-warning">Edit</a>
        			<a href="?delete=<?= $row['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this type?')">Delete</a>
                </td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
</div>

<?php include 'template/footer.php'; ?>
