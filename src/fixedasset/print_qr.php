<?php
require_once 'config.php';
require_once 'vendor/autoload.php';

use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\Writer\PngWriter;

$id = (int)($_GET['id'] ?? 0);
$result = $conn->query("SELECT * FROM assets WHERE id = $id");
$asset = $result->fetch_assoc();

$qrText = "AssetID:{$asset['id']}; Name:{$asset['model']}; Serial:{$asset['serialno']}";
$qrImage = Builder::create()
    ->writer(new PngWriter())
    ->data($qrText)
    ->encoding(new Encoding('UTF-8'))
    ->size(300)
    ->margin(10)
    ->build()
    ->getDataUri();
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>พิมพ์ QR</title>
  <style>
    body { text-align: center; font-family: sans-serif; padding: 40px; }
    .btn-print { margin-top: 20px; }
    @media print { .btn-print { display: none; } }
  </style>
</head>
<body>
  <h2><?= htmlspecialchars($asset['model']) ?></h2>
  <img src="<?= $qrImage ?>" width="250">
  <p>Serial: <?= $asset['serialno'] ?></p>
  <button onclick="window.print()" class="btn-print">พิมพ์</button>
</body>
</html>
