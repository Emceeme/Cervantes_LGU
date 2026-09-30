<?php
session_start();
require_once '../../config/security.php';
require_once '../../config/db.php';
require_once '../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Cell\DataType;

// Security check
$department = html_entity_decode($_SESSION['department'] ?? '', ENT_QUOTES);
if (!isset($_SESSION['role']) || ($_SESSION['role'] !== 'MSWD_WORKER' && $department !== 'MSWD' && $_SESSION['role'] !== 'SUPER_ADMIN')) {
    logSecurityEvent('unauthorized_access', $_SESSION['id'] ?? null, ['endpoint' => 'export_mswd_applications']);
    header('Location: /login.php?unauthorized=1');
    exit();
}

// Get filter parameters
$status_filter = $_GET['status'] ?? '';
$type_filter = $_GET['type'] ?? '';

// Build query
$sql = "SELECT 
    a.tracking_number,
    a.first_name,
    a.middle_name,
    a.last_name,
    a.contact_number,
    a.email,
    a.barangay,
    a.street_address,
    at.name as assistance_type,
    a.status,
    a.submitted_at
FROM applications a
LEFT JOIN assistance_types at ON a.assistance_type_id = at.id";

$params = array();
$where_clauses = array();

if (!empty($status_filter)) {
    $where_clauses[] = "a.status = ?";
    $params[] = $status_filter;
}

if (!empty($type_filter)) {
    $where_clauses[] = "a.assistance_type_id = ?";
    $params[] = $type_filter;
}

if (!empty($where_clauses)) {
    $sql .= " WHERE " . implode(" AND ", $where_clauses);
}

$sql .= " ORDER BY a.submitted_at DESC";

$stmt = $conn->prepare($sql);

if ($conn instanceof PDO) {
    if (!empty($params)) {
        $stmt->execute($params);
    } else {
        $stmt->execute();
    }
    $applications = $stmt->fetchAll();
} else {
    if (!empty($params)) {
        $types = str_repeat('s', count($params));
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $applications = array();
    while ($row = $result->fetch_assoc()) {
        $applications[] = $row;
    }
    $stmt->close();
}

// Create spreadsheet
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Set column headers
$headers = array('Tracking Number', 'First Name', 'Middle Name', 'Last Name', 'Phone', 'Email', 'Barangay', 'Address', 'Assistance Type', 'Status', 'Application Date');
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

$sheet->getStyle('A1:K1')->applyFromArray($headerStyle);

// Add data
$row = 2;
foreach ($applications as $app) {
    $sheet->setCellValueExplicit('A' . $row, $app['tracking_number'], DataType::TYPE_STRING);
    $sheet->setCellValueExplicit('B' . $row, $app['first_name'], DataType::TYPE_STRING);
    $sheet->setCellValueExplicit('C' . $row, $app['middle_name'], DataType::TYPE_STRING);
    $sheet->setCellValueExplicit('D' . $row, $app['last_name'], DataType::TYPE_STRING);
    $sheet->setCellValueExplicit('E' . $row, $app['contact_number'], DataType::TYPE_STRING);
    $sheet->setCellValueExplicit('F' . $row, $app['email'], DataType::TYPE_STRING);
    $sheet->setCellValueExplicit('G' . $row, $app['barangay'], DataType::TYPE_STRING);
    $sheet->setCellValueExplicit('H' . $row, $app['street_address'], DataType::TYPE_STRING);
    $sheet->setCellValueExplicit('I' . $row, $app['assistance_type'], DataType::TYPE_STRING);
    $sheet->setCellValueExplicit('J' . $row, $app['status'], DataType::TYPE_STRING);
    $sheet->setCellValue('K' . $row, date('Y-m-d', strtotime($app['submitted_at'])));
    $row++;
}

// Auto-size columns
foreach (range('A', 'K') as $col) {
    $sheet->getColumnDimension($col)->setAutoSize(true);
}

// Add borders to data
if ($row > 2) {
    $sheet->getStyle('A2:K' . ($row - 1))->applyFromArray(array(
        'borders' => array('allBorders' => array('borderStyle' => Border::BORDER_THIN))
    ));
}

// Generate filename
$filename = 'mswd_applications_' . date('Y-m-d_H-i-s') . '.xlsx';

// Set headers
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="' . $filename . '"');
header('Cache-Control: max-age=0');

// Save file
$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit();
?>
