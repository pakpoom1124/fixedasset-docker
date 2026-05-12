<?php
require_once 'config.php';
require_once 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// ดึงข้อมูล
$sql = "
    SELECT a.id, a.code_id, a.name, a.nameen, a.serialno, a.model, a.compname,
           a.startdate, a.expdate, a.ip, a.receiptdate, a.status, a.remark,
           l.name AS location_name, t.name AS type_name, p.name AS position_name
    FROM assets a
    LEFT JOIN locations l ON a.location_id = l.id
    LEFT JOIN asset_types t ON a.type_id = t.id
    LEFT JOIN positions p ON a.position_id = p.id
";
$result = $conn->query($sql);

// สร้าง Spreadsheet
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// หัวตาราง
$headers = [
    'ID', 'Code ID', 'Name', 'Name (EN)', 'Serial No', 'Model', 'Computer Name',
    'Start Date', 'Expire Date', 'IP Address', 'Receipt Date', 'Status', 'Remark',
    'Location', 'Type', 'Position'
];
$sheet->fromArray($headers, NULL, 'A1');

// เติมข้อมูล
$rowIndex = 2;
while ($row = $result->fetch_assoc()) {
    $sheet->fromArray([
        $row['id'],
        $row['code_id'],
        $row['name'],
        $row['nameen'],
        $row['serialno'],
        $row['model'],
        $row['compname'],
        $row['startdate'],
        $row['expdate'],
        $row['ip'],
        $row['receiptdate'],
        $row['status'],
        $row['remark'],
        $row['location_name'],
        $row['type_name'],
        $row['position_name']
    ], NULL, 'A' . $rowIndex);
    $rowIndex++;
}

// ตั้งชื่อไฟล์
$filename = "assets_export_" . date("Ymd_His") . ".xlsx";
header("Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet");
header("Content-Disposition: attachment; filename=\"$filename\"");
header("Cache-Control: max-age=0");

// บันทึกไฟล์
$writer = new Xlsx($spreadsheet);
$writer->save("php://output");
exit;
?>
