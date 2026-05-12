<?php
session_start();
include 'config.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $username = $_POST['username'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT * FROM users WHERE username=?");
    $stmt->bind_param("s", $username);
    $stmt->execute();

    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    // [FIX] Verify password hash
    if ($user && password_verify($password, $user['password'])) {

        $_SESSION['user'] = [
            'id' => $user['id'],
            'username' => $user['username'],
            'role' => $user['role'],
            'can_edit' => $user['can_edit'],
            'can_delete' => $user['can_delete']
        ];

        header("Location: index.php");
        exit;

    } else {

        $error = 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง';

    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>

    <meta charset="UTF-8">
    <title>เข้าสู่ระบบ</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>

        body {
            background: url("img/nara.png") repeat;
            background-size: 100px;
        }

        .login-card {
            max-width: 400px;
            margin: 80px auto;
            padding: 30px;
            background: #ffffffcc;
            border-radius: 15px;
            box-shadow: 0 8px 20px rgba(0,0,0,0.2);
        }

        .login-logo img {
            max-width: 160px;
            margin-bottom: 20px;
        }

    </style>

</head>

<body>

<div class="login-card text-center">

    <div class="login-logo">
        <img src="img/NARA LOGO.png" alt="Company Logo">
    </div>

    <?php if ($error): ?>

        <div class="alert alert-danger">
            <?= $error ?>
        </div>

    <?php endif; ?>

    <form method="post">

        <div class="mb-3">
            <input
                name="username"
                class="form-control"
                placeholder="Username"
                required
            >
        </div>

        <div class="mb-3">
            <input
                type="password"
                name="password"
                class="form-control"
                placeholder="Password"
                required
            >
        </div>

        <button class="btn btn-primary w-100">
            Login
        </button>

    </form>

</div>

</body>
</html>