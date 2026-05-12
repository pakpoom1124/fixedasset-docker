<?php
require_once '../config.php';
require_once '../vendor/autoload.php';

use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\Writer\PngWriter;

$id = intval($_GET['id'] ?? 0);

// ดึงข้อมูล
$stmt = $conn->prepare("
    SELECT u.id, u.model, u.serial_no, l.name AS location_name 
    FROM mobile u 
    LEFT JOIN locations l ON u.location_id = l.id 
    WHERE u.id = ?
");
$stmt->bind_param("i", $id);
$stmt->execute();
$data = $stmt->get_result()->fetch_assoc();

if (!$data) {
    echo "ไม่พบข้อมูล";
    exit;
}

// สร้าง QR Code จากข้อมูลที่ใช้
$qr_text = "ID:{$data['id']}\nModel:{$data['model']}\nSerial:{$data['serial_no']}";
$qr = Builder::create()
    ->writer(new PngWriter())
    ->data($qr_text)
    ->encoding(new Encoding('UTF-8'))
    ->size(200)
    ->margin(10)
    ->build();

$qr_image = $qr->getDataUri();
?>
<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <title>พิมพ์ QR - Mobile</title>
  <style>
    body { font-family: sans-serif; text-align: center; margin-top: 50px; }
    .qr-box { display: inline-block; border: 1px solid #ccc; padding: 20px; }
    .info { margin-top: 10px; font-size: 14px; }
    @media print {
      button { display: none; }
    }
  </style>
</head>
<body>
  <div class="qr-box">
    <img src="<?= $qr_image ?>" alt="QR Code"><br>
    <div class="info">
      สถานที่: <?= htmlspecialchars($data['location_name']) ?><br>
      รุ่น: <?= htmlspecialchars($data['model']) ?><br>
      Serial: <?= htmlspecialchars($data['serial_no']) ?><br>
    </div>
  </div>
  <br><br>
  <button onclick="window.print()" class="btn btn-primary">พิมพ์</button>
</body>
</html>
