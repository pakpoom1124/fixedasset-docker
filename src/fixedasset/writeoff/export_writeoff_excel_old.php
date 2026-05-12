<?php
require_once '../config.php';
require_once '../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;

$sql = "
    SELECT c.id, c.code_id, c.name, l.name AS location_name, a.name AS asset_type_name,
	c.monitor, c.model, c.serial_no, c.remark, c.startdate, c.image
    FROM writeoff c
    LEFT JOIN locations l ON c.location_id = l.id
	LEFT JOIN asset_types a ON c.asset_type_id = a.id
";
$result = $conn->query($sql);

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

$headers = ['ID', 'Code', 'Name', 'Location', 'Type', 'Startdate', 'Year', 'Month', 'เครื่องเก่าจาก', 'Model', 'Serial No', 'Note', 'Image'];
$sheet->fromArray($headers, NULL, 'A1');

$sheet->getColumnDimension('M')->setWidth(30);

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

    if (!empty($row['image']) && file_exists('../img/' . $row['image'])) {
        $drawing = new Drawing();
        $drawing->setName('Asset Image');
        $drawing->setDescription('Asset Image');
        $drawing->setPath('../img/' . $row['image']);
        $drawing->setCoordinates('M' . $rowIndex);
        
        $drawing->setWidth(200);
        $drawing->setHeight(170);
        
        $drawing->setWorksheet($sheet);

        $sheet->getRowDimension($rowIndex)->setRowHeight(130);
    } else {
        $sheet->setCellValue('M' . $rowIndex, 'No Image');
    }

    $rowIndex++;
}

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="writeoff_report_' . date('Ymd_His') . '.xlsx"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;