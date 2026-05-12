<?php
require_once 'config.php';
require_once 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// ดึงข้อมูล
$sql = "
    SELECT 
        a.*, 
        l.name AS location_name, 
        t.name AS type_name, 
        p.name AS position_name
    FROM assets a
    LEFT JOIN locations l ON a.location_id = l.id
    LEFT JOIN asset_types t ON a.type_id = t.id
    LEFT JOIN positions p ON a.position_id = p.id
    ORDER BY a.id ASC
";

$result = $conn->query($sql);

// สร้าง Spreadsheet
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Assets');

// ตั้งค่าหัวตาราง (ภาษาไทย)
$headers = ['#', 'รหัส', 'ชื่อ', 'แผนก', 'สถานที่', 'Serial', 'Model', 'ประเภท', 'ชื่อเครื่อง', 'เริ่มใช้', 'หมดอายุ', 'สถานะ', 'หมายเหตุ'];
$sheet->fromArray($headers, null, 'A1');

// เติมข้อมูลลงแถว
$rowIndex = 2;
$counter = 1;
while ($row = $result->fetch_assoc()) {
    $sheet->fromArray([
        $counter++,
        $row['code_id'],
        $row['name'],
        $row['position_name'],
        $row['location_name'],
        $row['serialno'],
        $row['model'],
        $row['type_name'],
        $row['compname'],
        $row['startdate'],
        $row['expdate'],
        $row['status'],
        $row['remark']
    ], null, 'A' . $rowIndex);
    $rowIndex++;
}


$filename = "assets_export_" . date('Ymd_His') . ".xlsx";
header("Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet");
header("Content-Disposition: attachment; filename=\"$filename\"");
header("Cache-Control: max-age=0");

$writer = new Xlsx($spreadsheet);
$writer->save("php://output");
exit;
