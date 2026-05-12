<?php
// [FIX] Docker/Linux migration:
// session_start() ต้องอยู่ก่อน HTML, ช่องว่าง, echo, include template ทุกชนิด
session_start();

// [FIX] โหลด config.php เพียงครั้งเดียว
include 'config.php';

// [FIX] ตรวจสอบ Login ก่อนแสดงผลหน้าเว็บ
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Fixed Asset Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<?php
// [FIX] เรียก Header หลังจากตรวจสอบ Session แล้วเท่านั้น
include 'template/header.php';
?>

<div class="container mt-4">
    <h3>Welcome to NARA THAI CUISINE</h3>
    <hr>

    <div class="row g-3">

        <div class="col-md-4">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <h5 class="card-title">ประเภท (Type)</h5>
                    <p class="card-text">จัดการประเภททรัพย์สิน</p>
                    <a href="types.php" class="btn btn-primary">จัดการประเภท</a>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <h5 class="card-title">สถานที่ (Location)</h5>
                    <p class="card-text">จัดการสถานที่ตั้งของทรัพย์สิน</p>
                    <a href="locations.php" class="btn btn-primary">จัดการสถานที่</a>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <h5 class="card-title">แผนก (Department)</h5>
                    <p class="card-text">จัดการแผนก</p>
                    <a href="positions.php" class="btn btn-primary">จัดการตำแหน่ง</a>
                </div>
            </div>
        </div>

        <!--<div class="col-md-4">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <h5 class="card-title">รายงาน</h5>
                    <p class="card-text">แสดงรายงานทรัพย์สินพร้อมฟิลเตอร์</p>
                    <a href="report.php" class="btn btn-info">เปิดรายงาน</a>
                </div>
            </div>
        </div>-->

        <div class="col-md-4">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <h5 class="card-title">ผู้ใช้งาน (Users)</h5>
                    <p class="card-text">จัดการผู้ใช้งาน</p>
                    <a href="users.php" class="btn btn-secondary">จัดการผู้ใช้งาน</a>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <h5 class="card-title">ออกจากระบบ (Logout)</h5>
                    <p class="card-text">ออกจากระบบ</p>
                    <a href="logout.php" class="btn btn-secondary">ออกจากระบบ</a>
                </div>
            </div>
        </div>

    </div>

    <?php /* ?>
    <p>Welcome, <strong><?= htmlspecialchars($_SESSION['user']) ?></strong></p>
    <?php */ ?>

</div>

<?php include 'template/footer.php'; ?>

</body>
</html>