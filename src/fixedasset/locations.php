<?php 
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}	
include 'config.php'; 
include 'template/header.php'; 
?>

<h4>Location</h4>

<?php
// Handle update
if (isset($_POST['update_id'])) {
    $id = intval($_POST['update_id']);
    $name = $conn->real_escape_string($_POST['name']);
    $conn->query("UPDATE locations SET name='$name' WHERE id=$id");
    header("Location: locations.php");
    exit;
}

// Handle insert
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add'])) {
    $name = $conn->real_escape_string($_POST['name']);
    $conn->query("INSERT INTO locations (name) VALUES ('$name')");
}

// Handle delete
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $conn->query("DELETE FROM locations WHERE id=$id");
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
?>

<form method="post" class="mb-3">
    <?php if ($edit_row): ?>
        <input type="hidden" name="update_id" value="<?= $edit_row['id'] ?>">
        <input name="name" value="<?= htmlspecialchars($edit_row['name']) ?>" required>
        <button type="submit" class="btn btn-warning btn-sm">Update</button>
        <a href="locations.php" class="btn btn-secondary btn-sm">Cancel</a>
    <?php else: ?>
        <input name="name" placeholder="New location" required>
        <button type="submit" name="add" class="btn btn-success btn-sm">Add</button>
    <?php endif; ?>
</form>

<table class="table table-bordered table-sm">
<tr>
    <th>ID</th><th>Name</th><th>Actions</th>
</tr>
<?php
$res = $conn->query("SELECT * FROM locations");
while ($row = $res->fetch_assoc()) {
    echo "<tr>
        <td>{$row['id']}</td>
        <td>{$row['name']}</td>
        <td>
            <a href='?edit={$row['id']}' class='btn btn-sm btn-warning'>Edit</a>
            <a href='?delete={$row['id']}' class='btn btn-sm btn-danger' onclick='return confirm(\"Delete this location?\")'>Delete</a>
        </td>
    </tr>";
}
?>
</table>

<?php include 'template/footer.php'; ?>
