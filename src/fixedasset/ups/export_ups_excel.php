<?php
require_once '../config.php';
require_once '../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// ดึงข้อมูล
$sql = "
    SELECT c.id, c.code_id, l.name AS location_name,
           c.model, c.serial_no, c.remark
    FROM ups c
    LEFT JOIN locations l ON c.location_id = l.id
";
$result = $conn->query($sql);

// สร้าง Spreadsheet
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// ตั้งชื่อคอลัมน์หัวตาราง
$headers = ['ID', 'Code', 'Location', 'Model', 'Serial No', 'Remark'];
$sheet->fromArray($headers, NULL, 'A1');

// เติมข้อมูลจากฐานข้อมูล
$rowIndex = 2;
while ($row = $result->fetch_assoc()) {
    $sheet->fromArray([
        $row['id'],
        $row['code_id'],
        $row['location_name'],
        $row['model'],
        $row['serial_no'],
        $row['remark']
    ], NULL, 'A' . $rowIndex);
    $rowIndex++;
}

// ตั้งชื่อไฟล์และ header
$filename = "ups_export_excel.xlsx";
header("Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet");
header("Content-Disposition: attachment; filename=\"$filename\"");
header("Cache-Control: max-age=0");

// สร้างและส่งออกไฟล์
$writer = new Xlsx($spreadsheet);
$writer->save("php://output");
exit;
?>
