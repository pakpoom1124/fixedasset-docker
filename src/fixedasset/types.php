<?php 
// [FIX] ต้อง start session และ process redirect ก่อน include header.php
// เพราะ template/header.php มี HTML output ทำให้ header("Location: ...") ใช้งานไม่ได้
session_start();

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

include 'config.php';

// เพิ่มหรือแก้ไขประเภท
if (isset($_POST['save'])) {
    $id = intval($_POST['id']);
    $name = $conn->real_escape_string($_POST['name']);
    $category_id = intval($_POST['category_id']);

    if ($id > 0) {
        $conn->query("UPDATE asset_types SET name='$name', category_id=$category_id WHERE id=$id");
    } else {
        $conn->query("INSERT INTO asset_types (name, category_id) VALUES ('$name', $category_id)");
    }
    header("Location: types.php");
    exit;
}

// ลบประเภท
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $conn->query("DELETE FROM asset_types WHERE id=$id");
    header("Location: types.php");
    exit;
}

// แก้ไข
$edit_id = isset($_GET['edit']) ? intval($_GET['edit']) : 0;
$edit_row = null;
if ($edit_id) {
    $res = $conn->query("SELECT * FROM asset_types WHERE id=$edit_id");
    if ($res) {
        $edit_row = $res->fetch_assoc();
    }
}

// ดึงข้อมูลหมวดหมู่ทั้งหมด
$categories = $conn->query("SELECT * FROM category ORDER BY id ASC");

// [FIX] include header.php หลังจาก Add/Edit/Delete redirect logic เสร็จแล้วเท่านั้น
include 'template/header.php';
?>

<h4>Asset Types</h4>

<form method="post" class="mb-3">
    <input type="hidden" name="id" value="<?= $edit_row ? $edit_row['id'] : 0 ?>">
    
    <div class="mb-2">
        <input name="name" placeholder="Type name" value="<?= $edit_row ? htmlspecialchars($edit_row['name']) : '' ?>" required class="form-control">
    </div>

    <div class="mb-2">
        <select name="category_id" class="form-control" required>
            <option value="">-- Select Category --</option>
            <?php while ($cat = $categories->fetch_assoc()): ?>
                <option value="<?= $cat['id'] ?>" <?= ($edit_row && $edit_row['category_id'] == $cat['id']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($cat['cat_name']) ?>
                </option>
            <?php endwhile; ?>
        </select>
    </div>

    <button type="submit" name="save" class="btn btn-primary">
        <?= $edit_row ? 'Update' : 'Add' ?>
    </button>
    <?php if ($edit_row): ?>
        <a href="types.php" class="btn btn-secondary">Cancel</a>
    <?php endif; ?>
</form>

<table class="table table-bordered table-sm">
<tr><th>ID</th><th>Name</th><th>Category</th><th>Actions</th></tr>
<?php 
$result = $conn->query("
    SELECT asset_types.*, category.cat_name 
    FROM asset_types 
    LEFT JOIN category ON asset_types.category_id = category.id 
    ORDER BY asset_types.id ASC
");
while ($row = $result->fetch_assoc()): ?>
<tr>
    <td><?= $row['id'] ?></td>
    <td><?= htmlspecialchars($row['name']) ?></td>
    <td><?= htmlspecialchars($row['cat_name']) ?></td>
    <td>
        <a href="?edit=<?= $row['id'] ?>" class="btn btn-sm btn-warning">Edit</a>
        <a href="?delete=<?= $row['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this type?')">Delete</a>
    </td>
</tr>
<?php endwhile; ?>
</table>

<?php include 'template/footer.php'; ?>
