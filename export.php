<?php
include 'db.php';
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use TCPDF;

// Get export format
$format = isset($_GET['format']) ? $_GET['format'] : 'excel';
$searchQuery = isset($_GET['search']) ? trim($_GET['search']) : '';

// Get total number of classes conducted
$totalClassesQuery = "SELECT COUNT(DISTINCT date) FROM attendance";
$totalClassesStmt = $conn->prepare($totalClassesQuery);
$totalClassesStmt->execute();
$totalClasses = (int) $totalClassesStmt->fetchColumn();

// Fetch students with attendance count
$studentsQuery = "
    SELECT s.*, COALESCE(a.attendance_count, 0) AS present_count
    FROM students s
    LEFT JOIN (
        SELECT student_id, COUNT(*) AS attendance_count
        FROM attendance
        GROUP BY student_id
    ) a ON s.student_id = a.student_id
    WHERE s.name LIKE :search OR 
          s.student_id LIKE :search OR 
          s.section LIKE :search
    ORDER BY s.student_id ASC";

$studentsStmt = $conn->prepare($studentsQuery);
$studentsStmt->bindValue(':search', '%' . $searchQuery . '%', PDO::PARAM_STR);
$studentsStmt->execute();
$students = $studentsStmt->fetchAll();

// Calculate attendance percentage for each student
foreach ($students as &$student) {
    $presentCount = (int) $student['present_count'];
    $attendancePercentage = ($totalClasses > 0) ? round(($presentCount / $totalClasses) * 100, 2) : 0;
    $student['attendance_percentage'] = $attendancePercentage . '%';
}

// Export as Excel
if ($format === 'excel') {
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    // Headers
    $headers = ['Serial No', 'Student ID', 'Section', 'Name', 'Total Attendance', 'Present (%)'];
    $sheet->fromArray([$headers], NULL, 'A1');

    // Data
    $row = 2;
    $count = 1;
    foreach ($students as $student) {
        $sheet->fromArray([
            $count++, 
            $student['student_id'], 
            $student['section'], 
            $student['name'], 
            $student['present_count'],
            $student['attendance_percentage']
        ], NULL, 'A' . $row++);
    }

    // Output file
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="attendance.xlsx"');
    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit();
}

if ($format === 'pdf') {
    // Get the earliest attendance date
    $startDateQuery = "SELECT MIN(date) FROM attendance";
    $startDateStmt = $conn->prepare($startDateQuery);
    $startDateStmt->execute();
    $startDate = $startDateStmt->fetchColumn();
    $startDateFormatted = $startDate ? date("d M Y", strtotime($startDate)) : "Unknown";

    class MYPDF extends TCPDF {
        protected $startDateFormatted;

        public function setStartDate($date) {
            $this->startDateFormatted = $date;
        }

        // Custom Header
        public function Header() {
            $this->SetFont('helvetica', 'B', 14);
            $this->Cell(0, 10, 'Attendance from ' . $this->startDateFormatted, 0, 1, 'C'); // Centered title
            $this->Ln(5); // Line break
        }
    }

    $pdf = new MYPDF();
    $pdf->setStartDate($startDateFormatted); // Pass the date to the header
    $pdf->SetCreator(PDF_CREATOR);
    $pdf->SetAuthor('Attendance System');
    $pdf->SetTitle('Attendance Report');
    $pdf->SetMargins(10, 10, 10);
    $pdf->SetAutoPageBreak(true, 15);
    $pdf->AddPage();

    // Set Font
    $pdf->SetFont('helvetica', '', 10);

    // Define Column Widths
    $colWidths = [10, 30, 20, 60, 30, 30]; // Serial, Student ID, Section, Name, Attendance, Percentage
    $rowHeight = 6; // Fixed row height

    // Table Header
    $pdf->SetFillColor(200, 220, 255); // Light Blue Background
    $pdf->SetTextColor(0);
    $pdf->SetDrawColor(0, 0, 0);
    $pdf->SetLineWidth(0.2);

    $pdf->Cell($colWidths[0], 8, '#', 1, 0, 'C', true);
    $pdf->Cell($colWidths[1], 8, 'Student ID', 1, 0, 'C', true);
    $pdf->Cell($colWidths[2], 8, 'Section', 1, 0, 'C', true);
    $pdf->Cell($colWidths[3], 8, 'Name', 1, 0, 'C', true);
    $pdf->Cell($colWidths[4], 8, 'Total Attendance', 1, 0, 'C', true);
    $pdf->Cell($colWidths[5], 8, 'Present (%)', 1, 1, 'C', true);

    // Table Data
    $pdf->SetFillColor(255, 255, 255); // White background for rows
    $pdf->SetTextColor(0);

    $count = 1;
    foreach ($students as $student) {
        $pdf->Cell($colWidths[0], $rowHeight, $count++, 1, 0, 'C', true);
        $pdf->Cell($colWidths[1], $rowHeight, $student['student_id'], 1, 0, 'C', true);
        $pdf->Cell($colWidths[2], $rowHeight, $student['section'], 1, 0, 'C', true);
        $pdf->Cell($colWidths[3], $rowHeight, $student['name'], 1, 0, 'L', true);
        $pdf->Cell($colWidths[4], $rowHeight, $student['present_count'], 1, 0, 'C', true);
        $pdf->Cell($colWidths[5], $rowHeight, $student['attendance_percentage'], 1, 1, 'C', true);
    }

    // Output PDF
    $pdf->Output('attendance.pdf', 'D');
    exit();
}




// Export as HTML
if ($format === 'html') {
    header('Content-Type: text/html');
    echo "<!DOCTYPE html>
    <html lang='en'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>Attendance Report</title>
        <style>
            body { font-family: Arial, sans-serif; margin: 20px; }
            h2 { text-align: center; }
            table { width: 100%; border-collapse: collapse; margin-top: 20px; }
            th, td { border: 1px solid black; padding: 10px; text-align: center; }
            th { background-color: #f2f2f2; }
        </style>
    </head>
    <body>
        <h2>Attendance Report - " . date('Y-m-d') . "</h2>
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Student ID</th>
                    <th>Section</th>
                    <th>Name</th>
                    <th>Attendance</th>
                    <th>Present (%)</th>
                </tr>
            </thead>
            <tbody>";

    $count = 1;
    foreach ($students as $student) {
        echo "<tr>
                <td>{$count}</td>
                <td>{$student['student_id']}</td>
                <td>{$student['section']}</td>
                <td>{$student['name']}</td>
                <td>{$student['present_count']}</td>
                <td>{$student['attendance_percentage']}</td>
              </tr>";
        $count++;
    }

    echo "  </tbody>
        </table>
    </body>
    </html>";
    exit();
}

// Export as CSV
if ($format === 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="attendance.csv"');

    $output = fopen('php://output', 'w');
    fputcsv($output, ['Serial No', 'Student ID', 'Section', 'Name', 'Total Attendance', 'Present (%)']);

    $count = 1;
    foreach ($students as $student) {
        fputcsv($output, [
            $count++, 
            $student['student_id'], 
            $student['section'], 
            $student['name'], 
            $student['present_count'],
            $student['attendance_percentage']
        ]);
    }
    fclose($output);
    exit();
}

// Export as JSON
if ($format === 'json') {
    header('Content-Type: application/json');
    echo json_encode($students, JSON_PRETTY_PRINT);
    exit();
}

// Export as XML
if ($format === 'xml') {
    header('Content-Type: application/xml');
    $xml = new SimpleXMLElement('<students/>');

    foreach ($students as $student) {
        $studentNode = $xml->addChild('student');
        $studentNode->addChild('serial_no', $student['student_id']);
        $studentNode->addChild('student_id', $student['student_id']);
        $studentNode->addChild('section', $student['section']);
        $studentNode->addChild('name', $student['name']);
        $studentNode->addChild('total_attendance', $student['present_count']);
        $studentNode->addChild('attendance_percentage', $student['attendance_percentage']);
    }

    echo $xml->asXML();
    exit();
}

echo "Invalid export format!";
?>
