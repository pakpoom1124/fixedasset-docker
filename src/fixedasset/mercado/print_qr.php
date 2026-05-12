<?php
require_once '../config.php';
require_once '../vendor/autoload.php';

use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\Writer\PngWriter;

$id = $_GET['id'] ?? 0;
$id = intval($id);

$stmt = $conn->prepare("
    SELECT 
        m.*, 
        l.name AS location_name, 
        t.name AS type_name, 
        p.name AS position_name
    FROM mercado m 
    LEFT JOIN locations l ON m.location_id = l.id
    LEFT JOIN asset_types t ON m.type_id = t.id
    LEFT JOIN positions p ON m.position_id = p.id
    WHERE m.id = ?
");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$data = $result->fetch_assoc();

if (!$data) {
    echo "ไม่พบข้อมูล";
    exit;
}

$qr_text = "ID:{$data['id']}\nCode:{$data['code_id']}\nName:{$data['name']}\nLocation:{$data['location_name']}\nModel:{$data['model']}\nSerial:{$data['serialno']}";
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
  <title>Asset QR</title>
  <style>
    body { font-family: sans-serif; text-align: center; margin-top: 50px; }
    .qr-box { display: inline-block; border: 1px solid #ccc; padding: 20px; }
    .info { margin-top: 10px; font-size: 14px; text-align: left; } /* จัดให้ชิดซ้าย */
    .info strong { display: block; margin-bottom: 5px; } /* ให้รหัสเด่นขึ้นมา */
    @media print {
      button { display: none; }
    }
  </style>
</head>
<body>
  <div class="qr-box">
    <img src="<?= $qr_image ?>" alt="QR Code"><br>
    <div class="info">		
      <strong>รหัส: <?= htmlspecialchars($data['code_id']) ?></strong><br>
      ชื่อ: <?= htmlspecialchars($data['name']) ?><br>
      แผนก: <?= htmlspecialchars($data['position_name'] ?? '-') ?><br>
      สถานที่: <?= htmlspecialchars($data['location_name']) ?><br>
      ประเภท: <?= htmlspecialchars($data['type_name'] ?? '-') ?><br>
      รุ่น: <?= htmlspecialchars($data['model']) ?><br>
      Serial: <?= htmlspecialchars($data['serialno']) ?><br>
    </div>
  </div>
  <br><br>
  <button onclick="window.print()" class="btn btn-primary">พิมพ์</button>
</body>
</html>