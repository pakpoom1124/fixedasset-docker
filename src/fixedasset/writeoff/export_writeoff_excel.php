<?php
require_once '../config.php';
require_once '../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;

/* ===============================
   รับค่า Filter จากหน้า writeoff.php
================================= */
$search = $_GET['search'] ?? '';
$location_id = $_GET['location_id'] ?? '';
$asset_type_id = $_GET['asset_type_id'] ?? '';

/* ===============================
   สร้าง WHERE เงื่อนไข
================================= */
$where = "WHERE 1=1";

if ($search !== '') {
    $safe = $conn->real_escape_string($search);
    $where .= " AND (
        n.code_id LIKE '%$safe%' OR
        n.name LIKE '%$safe%' OR
        n.serial_no LIKE '%$safe%' OR
        n.model LIKE '%$safe%' OR
        n.monitor LIKE '%$safe%' OR
        n.remark LIKE '%$safe%' OR
        l.name LIKE '%$safe%' OR
        t.name LIKE '%$safe%'
    )";
}

if ($location_id !== '') {
    $where .= " AND n.location_id = " . intval($location_id);
}

if ($asset_type_id !== '') {
    $where .= " AND n.asset_type_id = " . intval($asset_type_id);
}

/* ===============================
   Query ข้อมูล
================================= */
$sql = "
SELECT 
    n.*,
    l.name AS location_name,
    t.name AS asset_type_name
FROM writeoff n
LEFT JOIN locations l ON n.location_id = l.id
LEFT JOIN asset_types t ON n.asset_type_id = t.id
$where
ORDER BY n.id ASC
";

$result = $conn->query($sql);

/* ===============================
   สร้าง Excel
================================= */
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Writeoff Report');

/* Header */
$headers = [
    'ID',
    'Code',
    'Name',
    'Location',
    'Type',
    'Startdate',
    'Year',
    'Month',
    'เครื่องเก่าจาก',
    'Model',
    'Serial No',
    'Note',
    'Image'
];

$sheet->fromArray($headers, NULL, 'A1');

/* ขนาด column รูป */
$sheet->getColumnDimension('M')->setWidth(30);

/* ===============================
   Loop Data
================================= */
$rowIndex = 2;

while ($row = $result->fetch_assoc()) {

    $years = 0;
    $months = 0;

    if (!empty($row['startdate']) && $row['startdate'] != '0000-00-00') {
        $start = new DateTime($row['startdate']);
        $now = new DateTime();
        $diff = $start->diff($now);
        $years = $diff->y;
        $months = $diff->m;
    }

    $sheet->fromArray([
        $row['id'],
        $row['code_id'],
        $row['name'],
        $row['location_name'],
        $row['asset_type_name'],
        $row['startdate'],
        $years,
        $months,
        $row['monitor'],
        $row['model'],
        $row['serial_no'],
        $row['remark']
    ], NULL, 'A' . $rowIndex);

    /* รูปภาพ */
    if (!empty($row['image']) && file_exists('../img/' . $row['image'])) {

        $drawing = new Drawing();
        $drawing->setName('Asset Image');
        $drawing->setPath('../img/' . $row['image']);
        $drawing->setCoordinates('M' . $rowIndex);
        $drawing->setHeight(80);
        $drawing->setWorksheet($sheet);

        $sheet->getRowDimension($rowIndex)->setRowHeight(70);
    }

    $rowIndex++;
}

/* ===============================
   Download Excel
================================= */
$filename = "writeoff_report_" . date('Ymd_His') . ".xlsx";

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="'.$filename.'"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;