<?php
require_once '../vendor/autoload.php';
require_once '../config.php';
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\Writer\PngWriter;

$id = $_GET['id'] ?? 0;
$result = $conn->query("SELECT * FROM notebook_rentals WHERE id=$id");
$data = $result->fetch_assoc();
$content = "Notebook: {$data['item_no']}\nSerial: {$data['serial_no']}\nReceiver: {$data['receiver_name']}";

$qr = Builder::create()
    ->writer(new PngWriter())
    ->data($content)
    ->encoding(new Encoding('UTF-8'))
    ->size(300)
    ->margin(10)
    ->build();
?>
<!DOCTYPE html>
<html>
<head><meta charset="utf-8"><title>Print QR</title></head>
<body style="text-align:center; margin-top:50px;">
<h4>QR Code for Notebook ID <?= $data['id'] ?></h4>
<img src="<?= $qr->getDataUri() ?>" alt="QR Code"><br><br>
<div>Item No: <?= $data['item_no'] ?></div>
<div>Serial: <?= $data['serial_no'] ?></div>
<div>ผู้รับ: <?= $data['receiver_name'] ?></div>
</body>
</html>
