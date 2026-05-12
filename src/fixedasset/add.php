<?php include 'config.php'; ?>
<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = $_POST['asset_code'];
    $name = $_POST['asset_name'];
    $location = $_POST['location'];
    $status = $_POST['status'];
    $date = $_POST['last_counted'];
    $stmt = $conn->prepare("INSERT INTO assets (asset_code, asset_name, location, status, last_counted) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $code, $name, $location, $status, $date);
    $stmt->execute();
    header("Location: index.php");
    exit;
}
?>
<form method="post">
    Code: <input name="asset_code" required><br>
    Name: <input name="asset_name" required><br>
    Location: <input name="location"><br>
    Status:
    <select name="status">
        <option value="available">Available</option>
        <option value="missing">Missing</option>
        <option value="broken">Broken</option>
    </select><br>
    Last Counted: <input type="date" name="last_counted"><br>
    <button type="submit">Save</button>
</form>