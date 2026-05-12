<?php
require_once '../config.php';
require_once '../vendor/autoload.php';

use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\Writer\PngWriter;

// รับ ID จาก query string
$id = $_GET['id'] ?? 0;
$id = intval($id);

// ดึงข้อมูลจากฐานข้อมูล
$stmt = $conn->prepare("
    SELECT n.*, l.name AS location_name, t.name AS type_name 
    FROM printer n 
    LEFT JOIN locations l ON n.location_id = l.id 
    LEFT JOIN asset_types t ON n.asset_type_id = t.id 
    WHERE n.id = ?
");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$data = $result->fetch_assoc();

if (!$data) {
    echo "ไม่พบข้อมูล";
    exit;
}

// ✅ สร้าง QR Code พร้อม Model และ Serial
$qr_text = "ID:{$data['id']}\nCode:{$data['code_id']}\nModel:{$data['model']}\nSerial:{$data['serial_no']}\nIP:{$data['ip_address']}";
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
  <title>Print QR - Printer</title>
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
      <strong><?= htmlspecialchars($data['name']) ?></strong><br>
      รหัส: <?= htmlspecialchars($data['code_id']) ?><br>
      ประเภท: <?= htmlspecialchars($data['type_name']) ?><br>
      สถานที่: <?= htmlspecialchars($data['location_name']) ?><br>
      IP: <?= htmlspecialchars($data['ip_address']) ?><br>
      รุ่น: <?= htmlspecialchars($data['model']) ?><br>
      Serial: <?= htmlspecialchars($data['serial_no']) ?>
    </div>
  </div>
  <br><br>
  <button onclick="window.print()" class="btn btn-primary">พิมพ์</button>
</body>
</html>
