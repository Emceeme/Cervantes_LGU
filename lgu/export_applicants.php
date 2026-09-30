<?php
session_start();
require_once '../config/security.php';
require_once '../config/db.php';
require_once '../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Cell\DataType;

// Security check
$department = html_entity_decode($_SESSION['department'] ?? '', ENT_QUOTES);
if (!isset($_SESSION['role']) || ($department !== "Mayor's Office" && $department !== 'Mayor Office' && $department !== 'LGU' && $_SESSION['role'] !== 'SUPER_ADMIN')) {
    logSecurityEvent('unauthorized_access', $_SESSION['id'] ?? null, ['endpoint' => 'export_applicants']);
    header('Location: /login.php?unauthorized=1');
    exit();
}

// Fetch applicants
$applicants_stmt = $conn->prepare("
    SELECT 
        a.full_name,
        a.email,
        a.phone,
        j.job_title,
        a.message,
        a.created_at
    FROM applicants a
    LEFT JOIN jobs j ON a.job_id = j.id
    ORDER BY a.id DESC
");

if ($conn instanceof PDO) {
    $applicants_stmt->execute();
    $applicants = $applicants_stmt->fetchAll();
} else {
    $applicants_stmt->execute();
    $result = $applicants_stmt->get_result();
    $applicants = array();
    while ($row = $result->fetch_assoc()) {
        $applicants[] = $row;
    }
    $applicants_stmt->close();
}

// Create spreadsheet
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Set column headers
$headers = array('Full Name', 'Email', 'Phone', 'Job Applied For', 'Message', 'Application Date');
$col = 1;
foreach ($headers as $header) {
    $sheet->setCellValue(chr(64 + $col) . '1', $header);
    $col++;
}

// Style header row
$headerStyle = array(
    'font' => array('bold' => true, 'color' => array('rgb' => 'FFFFFF')),
    'fill' => array('fillType' => Fill::FILL_SOLID, 'startColor' => array('rgb' => '4472C4')),
    'alignment' => array('horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER),
    'borders' => array('allBorders' => array('borderStyle' => Border::BORDER_THIN))
);

$sheet->getStyle('A1:F1')->applyFromArray($headerStyle);

// Add data
$row = 2;
foreach ($applicants as $app) {
    $sheet->setCellValueExplicit('A' . $row, $app['full_name'], DataType::TYPE_STRING);
    $sheet->setCellValueExplicit('B' . $row, $app['email'], DataType::TYPE_STRING);
    $sheet->setCellValueExplicit('C' . $row, $app['phone'], DataType::TYPE_STRING);
    $sheet->setCellValueExplicit('D' . $row, $app['job_title'], DataType::TYPE_STRING);
    $sheet->setCellValueExplicit('E' . $row, $app['message'], DataType::TYPE_STRING);
    $sheet->setCellValue('F' . $row, date('Y-m-d', strtotime($app['created_at'])));
    $row++;
}

// Auto-size columns
foreach (range('A', 'F') as $col) {
    $sheet->getColumnDimension($col)->setAutoSize(true);
}

// Add borders to data
if ($row > 2) {
    $sheet->getStyle('A2:F' . ($row - 1))->applyFromArray(array(
        'borders' => array('allBorders' => array('borderStyle' => Border::BORDER_THIN))
    ));
}

// Generate filename
$filename = 'job_applicants_' . date('Y-m-d_H-i-s') . '.xlsx';

// Set headers
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="' . $filename . '"');
header('Cache-Control: max-age=0');

// Save file
$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit();
?>
