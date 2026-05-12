<?php
// [FIX] เริ่ม session และโหลด config ก่อนเท่านั้น
// ห้าม include header.php ตรงนี้ เพราะ header.php มี HTML output
// ถ้า include ก่อน header("Location: ...") จะเกิด error headers already sent
session_start();
include 'config.php';

if (!isset($_SESSION['user']) || !is_array($_SESSION['user']) || ($_SESSION['user']['role'] ?? '') !== 'admin') {
    die('เฉพาะผู้ดูแลระบบเท่านั้น');
}

// Save new user or update
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = $_POST['id'] ?? '';
    $username = $_POST['username'];
    $password = $_POST['password'];
    $can_edit = isset($_POST['can_edit']) ? 1 : 0;
    $can_delete = isset($_POST['can_delete']) ? 1 : 0;

    if ($id) {
        $sql = "UPDATE users SET username=?, can_edit=?, can_delete=?";
        $params = [$username, $can_edit, $can_delete];
        $types = "sii";

        if (!empty($password)) {
            $sql .= ", password=?";
            $params[] = password_hash($password, PASSWORD_DEFAULT);
            $types .= "s";
        }

        $sql .= " WHERE id=?";
        $params[] = $id;
        $types .= "i";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
    } else {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO users (username, password, can_edit, can_delete, role) VALUES (?, ?, ?, ?, 'user')");
        $stmt->bind_param("ssii", $username, $hash, $can_edit, $can_delete);
    }

    $stmt->execute();
    header("Location: users.php");
    exit;
}

// Delete user
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $conn->query("DELETE FROM users WHERE id=$id");
    header("Location: users.php");
    exit;
}

// Edit user
$edit = null;
if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    $edit = $conn->query("SELECT * FROM users WHERE id=$id")->fetch_assoc();
}

$users = $conn->query("SELECT * FROM users ORDER BY id ASC");

// [FIX] include header.php หลังจาก logic POST/DELETE/EDIT เสร็จแล้วเท่านั้น
// เพื่อไม่ให้กระทบ header("Location: users.php")
include 'template/header.php';
?>
<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="utf-8">
  <title>จัดการผู้ใช้</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body {
      margin: 0;
      padding: 0;
    }
    main.container {
      padding-top: 1.5rem;
    }
  </style>
</head>
<body>
<main class="container">
  <h3>จัดการผู้ใช้</h3>
  <form method="post" class="row g-2 mb-3">
    <input type="hidden" name="id" value="<?= $edit['id'] ?? '' ?>">
    <div class="col-md-3">
      <input name="username" class="form-control" placeholder="Username" value="<?= $edit['username'] ?? '' ?>" required>
    </div>
    <div class="col-md-3">
      <input type="password" name="password" class="form-control" placeholder="Password (เว้นว่างหากไม่เปลี่ยน)">
    </div>
    <div class="col-md-2">
      <div class="form-check">
        <input class="form-check-input" type="checkbox" name="can_edit" value="1" <?= !empty($edit['can_edit']) ? 'checked' : '' ?>>
        <label class="form-check-label">แก้ไขได้</label>
      </div>
      <div class="form-check">
        <input class="form-check-input" type="checkbox" name="can_delete" value="1" <?= !empty($edit['can_delete']) ? 'checked' : '' ?>>
        <label class="form-check-label">ลบได้</label>
      </div>
    </div>
    <div class="col-md-2">
      <button class="btn btn-success w-100">บันทึก</button>
    </div>
  </form>

  <table class="table table-bordered table-sm">
    <thead class="table-light">
      <tr>
        <th>ID</th>
        <th>Username</th>
        <th>Edit</th>
        <th>Delete</th>
        <th>จัดการ</th>
      </tr>
    </thead>
    <tbody>
      <?php while($u = $users->fetch_assoc()): ?>
      <tr>
        <td><?= $u['id'] ?></td>
        <td><?= htmlspecialchars($u['username']) ?></td>
        <td><?= $u['can_edit'] ? '✔' : '' ?></td>
        <td><?= $u['can_delete'] ? '✔' : '' ?></td>
        <td>
          <a href="?edit=<?= $u['id'] ?>" class="btn btn-sm btn-warning">แก้ไข</a>
          <a href="?delete=<?= $u['id'] ?>" onclick="return confirm('ลบผู้ใช้นี้?')" class="btn btn-sm btn-danger">ลบ</a>
        </td>
      </tr>
      <?php endwhile ?>
    </tbody>
  </table>
</main>
</body>
</html>
