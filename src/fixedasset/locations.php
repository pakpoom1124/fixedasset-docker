<?php 
// [FIX] ต้อง start session และจัดการ redirect ก่อน include header.php
session_start();

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

include 'config.php';

// Handle update
if (isset($_POST['update_id'])) {
    $id = intval($_POST['update_id']);
    $name = $conn->real_escape_string($_POST['name']);

    $conn->query("UPDATE locations SET name='$name' WHERE id=$id");

    // [FIX] redirect ก่อนมี HTML output
    header("Location: locations.php");
    exit;
}

// Handle insert
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add'])) {
    $name = $conn->real_escape_string($_POST['name']);

    $conn->query("INSERT INTO locations (name) VALUES ('$name')");

    // [FIX] เพิ่ม redirect หลัง insert เพื่อกันการ submit ซ้ำ
    header("Location: locations.php");
    exit;
}

// Handle delete
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);

    $conn->query("DELETE FROM locations WHERE id=$id");

    // [FIX] redirect ก่อน include header.php
    header("Location: locations.php");
    exit;
}

// Handle edit view
$edit_id = isset($_GET['edit']) ? intval($_GET['edit']) : 0;
$edit_row = null;

if ($edit_id) {
    $res = $conn->query("SELECT * FROM locations WHERE id=$edit_id");
    $edit_row = $res->fetch_assoc();
}

// [FIX] include header.php หลัง redirect logic ทั้งหมดเท่านั้น
include 'template/header.php'; 
?>

<h4>Location</h4>

<form method="post" class="mb-3">
    <?php if ($edit_row): ?>
        <input type="hidden" name="update_id" value="<?= htmlspecialchars($edit_row['id'] ?? '') ?>">

        <!-- [FIX] กันค่า NULL ก่อนเข้า htmlspecialchars -->
        <input name="name" value="<?= htmlspecialchars($edit_row['name'] ?? '') ?>" required>

        <button type="submit" class="btn btn-warning btn-sm">Update</button>
        <a href="locations.php" class="btn btn-secondary btn-sm">Cancel</a>
    <?php else: ?>
        <input name="name" placeholder="New location" required>
        <button type="submit" name="add" class="btn btn-success btn-sm">Add</button>
    <?php endif; ?>
</form>

<table class="table table-bordered table-sm">
<tr>
    <th>ID</th>
    <th>Name</th>
    <th>Actions</th>
</tr>

<?php
$res = $conn->query("SELECT * FROM locations");

while ($row = $res->fetch_assoc()) {
    // [FIX] escape output และกัน NULL
    $id = htmlspecialchars($row['id'] ?? '');
    $name = htmlspecialchars($row['name'] ?? '');

    echo "<tr>
        <td>{$id}</td>
        <td>{$name}</td>
        <td>
            <a href='?edit={$id}' class='btn btn-sm btn-warning'>Edit</a>
            <a href='?delete={$id}' class='btn btn-sm btn-danger' onclick='return confirm(\"Delete this location?\")'>Delete</a>
        </td>
    </tr>";
}
?>
</table>

<?php include 'template/footer.php'; ?>