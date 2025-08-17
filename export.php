<?php
// Start output buffering to prevent any accidental output
ob_start();

session_start();

// Include required files
require_once('db.php');

// Fix for TCPDF - proper inclusion
if (!class_exists('TCPDF')) {
    require_once('tcpdf/tcpdf.php');
}

// Get export parameters
$format = isset($_GET['format']) ? $_GET['format'] : 'excel';
$searchQuery = isset($_GET['search']) ? trim($_GET['search']) : '';

// Get the earliest attendance date for the filename
$startDateQuery = "SELECT MIN(date) FROM attendance";
$startDateStmt = $conn->prepare($startDateQuery);
$startDateStmt->execute();
$startDate = $startDateStmt->fetchColumn();
$startDateFormatted = $startDate ? date("Y-m-d", strtotime($startDate)) : date("Y-m-d");

// Get total number of classes conducted
$totalClassesQuery = "SELECT COUNT(DISTINCT date) FROM attendance";
$totalClassesStmt = $conn->prepare($totalClassesQuery);
$totalClassesStmt->execute();
$totalClasses = (int) $totalClassesStmt->fetchColumn();

// Fetch students with attendance count based on search
$studentsQuery = "
    SELECT s.*, 
        COALESCE(a.attendance_count, 0) AS present_count
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

// Generate filename based on search query
$filename = 'attendance_report_' . $startDateFormatted;
if (!empty($searchQuery)) {
    $filename .= '_search_' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $searchQuery);
}

switch ($format) {
    case 'excel':
        exportExcel($students, $totalClasses, $filename);
        break;
    case 'csv':
        exportCSV($students, $totalClasses, $filename);
        break;
    case 'pdf':
        exportPDF($students, $totalClasses, $filename, $startDateFormatted);
        break;
    case 'json':
        exportJSON($students, $totalClasses, $filename);
        break;
    case 'xml':
        exportXML($students, $totalClasses, $filename);
        break;
    case 'html':
        exportHTML($students, $totalClasses, $filename, $startDateFormatted);
        break;
    default:
        exportExcel($students, $totalClasses, $filename);
}

function exportExcel($students, $totalClasses, $filename) {
    // Clear any previous output
    if (ob_get_length()) ob_end_clean();
    
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="' . $filename . '.xlsx"');
    header('Cache-Control: max-age=0');
    
    echo "\xEF\xBB\xBF"; // UTF-8 BOM
    ?>
    <html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
        <style>
            table { 
                border-collapse: collapse; 
                width: 100%; 
                font-family: Arial, sans-serif;
            }
            th, td { 
                border: 1px solid #000; 
                padding: 8px; 
                vertical-align: middle;
            }
            th { 
                background-color: #4CAF50; 
                color: white; 
                font-weight: bold; 
                text-align: center;
            }
            .header { 
                background-color: #E8F5E8; 
                font-weight: bold; 
            }
            /* Column-specific alignment */
            .serial-no { text-align: center; }
            .student-id { text-align: center; font-weight: bold; }
            .section { text-align: center; }
            .student-name { text-align: left; padding-left: 10px; }
            .present-days { text-align: center; }
            .total-classes { text-align: center; }
            .attendance-percent { text-align: center; font-weight: bold; }
        </style>
    </head>
    <body>
        <h2>Student Attendance Report</h2>
        <p><strong>Total Classes Conducted:</strong> <?= $totalClasses ?></p>
        <p><strong>Generated on:</strong> <?= date('Y-m-d H:i:s') ?></p>
        
        <table>
            <thead>
                <tr>
                    <th style="width: 8%;">Serial No</th>
                    <th style="width: 15%;">Student ID</th>
                    <th style="width: 12%;">Section</th>
                    <th style="width: 35%;">Student Name</th>
                    <th style="width: 12%;">Present Days</th>
                    <th style="width: 12%;">Total Classes</th>
                    <th style="width: 14%;">Attendance %</th>
                </tr>
            </thead>
            <tbody>
                <?php $count = 1; foreach ($students as $student): ?>
                    <?php
                    $presentCount = (int) $student['present_count'];
                    $attendancePercentage = ($totalClasses > 0) ? round(($presentCount / $totalClasses) * 100, 2) : 0;
                    ?>
                    <tr>
                        <td class="serial-no"><?= $count++ ?></td>
                        <td class="student-id"><?= htmlspecialchars($student['student_id'] ?? '') ?></td>
                        <td class="section"><?= htmlspecialchars($student['section'] ?? '') ?></td>
                        <td class="student-name"><?= htmlspecialchars($student['name'] ?? '') ?></td>
                        <td class="present-days"><?= $presentCount ?></td>
                        <td class="total-classes"><?= $totalClasses ?></td>
                        <td class="attendance-percent"><?= $attendancePercentage ?>%</td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </body>
    </html>
    <?php
    exit();
}

function exportCSV($students, $totalClasses, $filename) {
    // Clear any previous output
    if (ob_get_length()) ob_end_clean();
    
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
    header('Cache-Control: max-age=0');
    
    echo "\xEF\xBB\xBF"; // UTF-8 BOM
    
    $output = fopen('php://output', 'w');
    
    // Add header information
    fputcsv($output, ['Student Attendance Report']);
    fputcsv($output, ['Total Classes Conducted: ' . $totalClasses]);
    fputcsv($output, ['Generated on: ' . date('Y-m-d H:i:s')]);
    fputcsv($output, []); // Empty row
    
    // Add column headers - Fixed and consistent
    fputcsv($output, [
        'Serial No', 
        'Student ID', 
        'Section', 
        'Student Name', 
        'Present Days', 
        'Total Classes', 
        'Attendance Percentage'
    ]);
    
    // Add data rows
    $count = 1;
    foreach ($students as $student) {
        $presentCount = (int) $student['present_count'];
        $attendancePercentage = ($totalClasses > 0) ? round(($presentCount / $totalClasses) * 100, 2) : 0;
        
        fputcsv($output, [
            $count++,
            $student['student_id'] ?? '',
            $student['section'] ?? '',
            $student['name'] ?? '',
            $presentCount,
            $totalClasses,
            $attendancePercentage . '%'
        ]);
    }
    
    fclose($output);
    exit();
}

function exportPDF($students, $totalClasses, $filename, $startDateFormatted) {
    // Clear any previous output and start fresh buffer
    while (ob_get_level()) {
        ob_end_clean();
    }
    ob_start();
    
    try {
        // Create new PDF document
        $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
        
        // Set document information
        $pdf->SetCreator('Cloud Computing Attendance System');
        $pdf->SetAuthor('CSE 4267: Cloud Computing');
        $pdf->SetTitle('Student Attendance Report');
        $pdf->SetSubject('Attendance Analytics Report');
        $pdf->SetKeywords('attendance, students, cloud computing, analytics');
        
        // Remove default header/footer
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        
        // Set margins
        $pdf->SetMargins(15, 15, 15);
        $pdf->SetAutoPageBreak(TRUE, 15);
        
        // Add a page
        $pdf->AddPage();
        
        // Calculate statistics
        $totalStudents = count($students);
        $excellentCount = 0;
        $goodCount = 0;
        $averageCount = 0;
        $poorCount = 0;
        $totalAttendancePercentage = 0;
        
        foreach ($students as $student) {
            $presentCount = (int) $student['present_count'];
            $attendancePercentage = ($totalClasses > 0) ? round(($presentCount / $totalClasses) * 100, 1) : 0;
            $totalAttendancePercentage += $attendancePercentage;
            
            if ($attendancePercentage >= 90) $excellentCount++;
            elseif ($attendancePercentage >= 80) $goodCount++;
            elseif ($attendancePercentage >= 60) $averageCount++;
            else $poorCount++;
        }
        
        $overallAverage = $totalStudents > 0 ? round($totalAttendancePercentage / $totalStudents, 1) : 0;
        
        // === HEADER SECTION ===
        // Background gradient rectangle for header
        $pdf->SetFillColor(5, 150, 105); // Dark green
        $pdf->Rect(0, 0, 210, 35, 'F');
        
        $pdf->SetFillColor(16, 185, 129); // Light green
        $pdf->Rect(0, 35, 210, 15, 'F');
        
        // University/Course Info
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFont('helvetica', 'B', 20);
        $pdf->SetXY(20, 12);
        $pdf->Cell(0, 8, 'CSE 4267: CLOUD COMPUTING', 0, 1, 'L');
        
        $pdf->SetFont('helvetica', '', 12);
        $pdf->SetXY(20, 22);
        $pdf->Cell(0, 6, 'Student Attendance Analytics Report', 0, 1, 'L');
        
        // Date and time info
        $pdf->SetFont('helvetica', '', 10);
        $pdf->SetXY(20, 37);
        $pdf->Cell(0, 5, 'Generated: ' . date('l, F j, Y \a\t g:i A'), 0, 1, 'L');
        
        $pdf->SetXY(130, 37);
        $pdf->Cell(0, 5, 'Period: From ' . date('M j, Y', strtotime($startDateFormatted)), 0, 1, 'L');
        
        // Reset text color
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetY(55);
        
        // === STATISTICS DASHBOARD ===
        $pdf->SetFont('helvetica', 'B', 14);
        $pdf->Cell(0, 8, 'ATTENDANCE OVERVIEW', 0, 1, 'C');
        $pdf->Ln(5);
        
        // Statistics boxes
        $boxWidth = 45;
        $boxHeight = 25;
        $startX = 15;
        $startY = $pdf->GetY();
        
        // Box 1: Total Students
        $pdf->SetFillColor(240, 253, 250);
        $pdf->SetDrawColor(5, 150, 105);
        $pdf->SetLineWidth(0.5);
        $pdf->RoundedRect($startX, $startY, $boxWidth, $boxHeight, 3, '1111', 'FD');
        
        $pdf->SetFont('helvetica', 'B', 18);
        $pdf->SetTextColor(5, 150, 105);
        $pdf->SetXY($startX, $startY + 5);
        $pdf->Cell($boxWidth, 8, $totalStudents, 0, 0, 'C');
        
        $pdf->SetFont('helvetica', '', 9);
        $pdf->SetTextColor(75, 85, 99);
        $pdf->SetXY($startX, $startY + 15);
        $pdf->Cell($boxWidth, 5, 'Total Students', 0, 0, 'C');
        
        // Box 2: Classes Conducted
        $pdf->SetFillColor(254, 249, 195);
        $pdf->SetDrawColor(245, 158, 11);
        $pdf->RoundedRect($startX + 47, $startY, $boxWidth, $boxHeight, 3, '1111', 'FD');
        
        $pdf->SetFont('helvetica', 'B', 18);
        $pdf->SetTextColor(245, 158, 11);
        $pdf->SetXY($startX + 47, $startY + 5);
        $pdf->Cell($boxWidth, 8, $totalClasses, 0, 0, 'C');
        
        $pdf->SetFont('helvetica', '', 9);
        $pdf->SetTextColor(75, 85, 99);
        $pdf->SetXY($startX + 47, $startY + 15);
        $pdf->Cell($boxWidth, 5, 'Classes Held', 0, 0, 'C');
        
        // Box 3: Average Attendance
        $pdf->SetFillColor(239, 246, 255);
        $pdf->SetDrawColor(59, 130, 246);
        $pdf->RoundedRect($startX + 94, $startY, $boxWidth, $boxHeight, 3, '1111', 'FD');
        
        $pdf->SetFont('helvetica', 'B', 18);
        $pdf->SetTextColor(59, 130, 246);
        $pdf->SetXY($startX + 94, $startY + 5);
        $pdf->Cell($boxWidth, 8, $overallAverage . '%', 0, 0, 'C');
        
        $pdf->SetFont('helvetica', '', 9);
        $pdf->SetTextColor(75, 85, 99);
        $pdf->SetXY($startX + 94, $startY + 15);
        $pdf->Cell($boxWidth, 5, 'Avg Attendance', 0, 0, 'C');
        
        // Box 4: Report Status
        $statusColor = $overallAverage >= 75 ? [34, 197, 94] : ($overallAverage >= 60 ? [245, 158, 11] : [239, 68, 68]);
        $statusText = $overallAverage >= 75 ? 'EXCELLENT' : ($overallAverage >= 60 ? 'GOOD' : 'NEEDS WORK');
        
        $pdf->SetFillColor(254, 242, 242);
        $pdf->SetDrawColor($statusColor[0], $statusColor[1], $statusColor[2]);
        $pdf->RoundedRect($startX + 141, $startY, $boxWidth, $boxHeight, 3, '1111', 'FD');
        
        $pdf->SetFont('helvetica', 'B', 11);
        $pdf->SetTextColor($statusColor[0], $statusColor[1], $statusColor[2]);
        $pdf->SetXY($startX + 141, $startY + 8);
        $pdf->Cell($boxWidth, 8, $statusText, 0, 0, 'C');
        
        $pdf->SetFont('helvetica', '', 9);
        $pdf->SetTextColor(75, 85, 99);
        $pdf->SetXY($startX + 141, $startY + 16);
        $pdf->Cell($boxWidth, 5, 'Overall Status', 0, 0, 'C');
        
        $pdf->SetY($startY + 35);
        
        // === PERFORMANCE BREAKDOWN ===
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->Cell(0, 8, 'PERFORMANCE BREAKDOWN', 0, 1, 'C');
        $pdf->Ln(3);
        
        // Performance bars
        $barStartY = $pdf->GetY();
        $barHeight = 6;
        $barSpacing = 10;
        
        $categories = [
            ['label' => 'Excellent (90%+)', 'count' => $excellentCount, 'color' => [34, 197, 94], 'icon' => 'E'],
            ['label' => 'Good (80-89%)', 'count' => $goodCount, 'color' => [59, 130, 246], 'icon' => 'G'],
            ['label' => 'Average (60-79%)', 'count' => $averageCount, 'color' => [245, 158, 11], 'icon' => 'A'],
            ['label' => 'Poor (<60%)', 'count' => $poorCount, 'color' => [239, 68, 68], 'icon' => 'P']
        ];
        
        foreach ($categories as $i => $category) {
            $y = $barStartY + ($i * $barSpacing);
            $percentage = $totalStudents > 0 ? ($category['count'] / $totalStudents) * 100 : 0;
            $barWidth = ($percentage / 100) * 120; // Max width 120mm
            
            // Label
            $pdf->SetFont('helvetica', '', 9);
            $pdf->SetTextColor(75, 85, 99);
            $pdf->SetXY(20, $y);
            $pdf->Cell(50, $barHeight, $category['icon'] . ' ' . $category['label'], 0, 0, 'L');
            
            // Background bar
            $pdf->SetFillColor(240, 240, 240);
            $pdf->Rect(75, $y + 1, 120, $barHeight - 2, 'F');
            
            // Colored bar
            if ($barWidth > 0) {
                $pdf->SetFillColor($category['color'][0], $category['color'][1], $category['color'][2]);
                $pdf->Rect(75, $y + 1, $barWidth, $barHeight - 2, 'F');
            }
            
            // Count and percentage
            $pdf->SetFont('helvetica', 'B', 8);
            $pdf->SetTextColor($category['color'][0], $category['color'][1], $category['color'][2]);
            $pdf->SetXY(80, $y + 1);
            $pdf->Cell(0, $barHeight - 2, $category['count'] . ' students (' . round($percentage, 1) . '%)', 0, 0, 'R');
        }
        
        $pdf->SetY($barStartY + 45);
        
        // === STUDENT DETAILS TABLE ===
        $pdf->SetFont('helvetica', 'B', 14);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->Cell(0, 8, 'DETAILED STUDENT RECORDS', 0, 1, 'C');
        $pdf->Ln(5);
        
        // Enhanced table with better styling and proper alignment
        $html = '<style>
            table { 
                border-collapse: collapse; 
                width: 100%; 
                font-family: helvetica;
                margin-top: 10px;
            }
            
            .header-row { 
                background: linear-gradient(135deg, #059669, #10b981);
                color: white;
                font-weight: bold;
            }
            
            .header-row th {
                padding: 12px 6px;
                text-align: center;
                font-size: 9px;
                border: 1px solid #047857;
                vertical-align: middle;
            }
            
            .data-row {
                border-bottom: 1px solid #e5e7eb;
            }
            
            .data-row:nth-child(even) {
                background-color: #f9fafb;
            }
            
            .data-row:nth-child(odd) {
                background-color: #ffffff;
            }
            
            .data-row td {
                padding: 8px 4px;
                font-size: 8px;
                border: 1px solid #e5e7eb;
                vertical-align: middle;
            }
            
            .col-serial { 
                text-align: center; 
                font-weight: bold;
                color: #6b7280;
                width: 8%;
            }
            
            .col-student-id {
                text-align: center;
                font-weight: bold;
                color: #374151;
                width: 15%;
            }
            
            .col-section {
                text-align: center;
                width: 10%;
                color: #1f2937;
            }
            
            .col-name {
                text-align: left;
                font-weight: 500;
                color: #1f2937;
                width: 32%;
                padding-left: 8px;
            }
            
            .col-present {
                text-align: center;
                font-weight: bold;
                width: 12%;
                color: #1f2937;
            }
            
            .col-total {
                text-align: center;
                width: 11%;
                color: #1f2937;
            }
            
            .col-percentage {
                text-align: center;
                font-weight: bold;
                width: 12%;
                color: #1f2937;
            }
            
            .excellent { color: #059669; }
            .good { color: #3b82f6; }
            .average { color: #f59e0b; }
            .poor { color: #ef4444; }
        </style>';
        
        $html .= '<table cellpadding="0" cellspacing="0">
                    <thead>
                        <tr class="header-row">
                            <th class="col-serial">S.No</th>
                            <th class="col-student-id">Student ID</th>
                            <th class="col-section">Section</th>
                            <th class="col-name">Student Name</th>
                            <th class="col-present">Present</th>
                            <th class="col-total">Total</th>
                            <th class="col-percentage">Percentage</th>
                        </tr>
                    </thead>
                    <tbody>';
        
        $count = 1;
        foreach ($students as $student) {
            $presentCount = (int) $student['present_count'];
            $attendancePercentage = ($totalClasses > 0) ? round(($presentCount / $totalClasses) * 100, 1) : 0;
            
            // Determine performance class
            $performanceClass = '';
            $performanceIcon = '';
            if ($attendancePercentage >= 90) { 
                $performanceClass = 'excellent'; 
                $performanceIcon = 'E';
            } elseif ($attendancePercentage >= 80) { 
                $performanceClass = 'good'; 
                $performanceIcon = 'G';
            } elseif ($attendancePercentage >= 60) { 
                $performanceClass = 'average'; 
                $performanceIcon = 'A';
            } else { 
                $performanceClass = 'poor'; 
                $performanceIcon = 'P';
            }
            
            $html .= '<tr class="data-row">
                        <td class="col-serial">' . $count++ . '</td>
                        <td class="col-student-id">' . htmlspecialchars($student['student_id'] ?? '') . '</td>
                        <td class="col-section">' . htmlspecialchars($student['section'] ?? '') . '</td>
                        <td class="col-name">' . htmlspecialchars($student['name'] ?? '') . '</td>
                        <td class="col-present">' . $presentCount . '</td>
                        <td class="col-total">' . $totalClasses . '</td>
                        <td class="col-percentage ' . $performanceClass . '">' .' ' . $attendancePercentage . '%</td>
                    </tr>';
        }
        
        $html .= '</tbody></table>';
        
        // Write the HTML table
        $pdf->SetFont('helvetica', '', 8);
        $pdf->writeHTML($html, true, false, true, false, '');
        
        // === FOOTER SECTION ===
        $pdf->Ln(10);
        
        // Footer with summary
        $pdf->SetFillColor(248, 250, 252);
        $pdf->SetDrawColor(203, 213, 224);
        $pdf->Rect(15, $pdf->GetY(), 180, 25, 'FD');
        
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->SetTextColor(71, 85, 105);
        $pdf->SetY($pdf->GetY() + 5);
        $pdf->Cell(0, 6, 'REPORT SUMMARY', 0, 1, 'C');
        
        $pdf->SetFont('helvetica', '', 8);
        $pdf->SetTextColor(100, 116, 139);
        
        $summaryText = sprintf(
            "This comprehensive attendance report covers %d students across %d classes. " .
            "Overall class performance shows an average attendance rate of %s%%, with %d students achieving excellent attendance (90%%+), " .
            "%d students with good attendance (80-89%%), %d students with average attendance (60-79%%), and %d students requiring attention (<60%%).",
            $totalStudents, $totalClasses, $overallAverage, $excellentCount, $goodCount, $averageCount, $poorCount
        );
        
        $pdf->SetXY(20, $pdf->GetY() + 2);
        $pdf->MultiCell(170, 4, $summaryText, 0, 'J');
        
        $pdf->SetY($pdf->GetY() + 3);
        $pdf->SetFont('helvetica', 'I', 7);
        $pdf->SetTextColor(156, 163, 175);
        $pdf->Cell(0, 4, 'Generated by CSE 4267 Cloud Computing Attendance System | ' . date('Y-m-d H:i:s T'), 0, 1, 'C');
        
        // Clear output buffer before sending PDF
        if (ob_get_length()) ob_end_clean();
        
        // Output PDF
        $pdf->Output($filename . '.pdf', 'D');
        
    } catch (Exception $e) {
        // Clear any output
        if (ob_get_length()) ob_end_clean();
        
        // Show error message with better styling
        header('Content-Type: text/html; charset=utf-8');
        echo '<!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>PDF Export Error</title>
            <style>
                body { 
                    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
                    background: linear-gradient(-45deg, #059669, #10b981, #34d399, #6ee7b7);
                    background-size: 400% 400%;
                    animation: gradient 15s ease infinite;
                    min-height: 100vh;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    margin: 0;
                }
                
                @keyframes gradient {
                    0% { background-position: 0% 50%; }
                    50% { background-position: 100% 50%; }
                    100% { background-position: 0% 50%; }
                }
                
                .error-container {
                    background: rgba(255, 255, 255, 0.95);
                    backdrop-filter: blur(20px);
                    border-radius: 20px;
                    padding: 40px;
                    text-align: center;
                    box-shadow: 0 25px 50px rgba(0, 0, 0, 0.1);
                    border: 2px solid rgba(255, 255, 255, 0.2);
                    max-width: 500px;
                    margin: 20px;
                }
                
                .error-icon {
                    font-size: 4rem;
                    margin-bottom: 20px;
                    color: #dc2626;
                }
                
                h2 { 
                    color: #dc2626; 
                    margin-bottom: 15px;
                    font-size: 1.5rem;
                }
                
                p { 
                    color: #6b7280; 
                    margin-bottom: 15px; 
                    line-height: 1.6;
                }
                
                .error-details {
                    background: #fef2f2;
                    border: 1px solid #fecaca;
                    border-radius: 8px;
                    padding: 15px;
                    margin: 20px 0;
                    color: #991b1b;
                    font-family: monospace;
                    font-size: 0.9rem;
                    text-align: left;
                }
                
                .back-button {
                    display: inline-flex;
                    align-items: center;
                    gap: 8px;
                    background: linear-gradient(135deg, #10b981, #059669);
                    color: white;
                    text-decoration: none;
                    padding: 12px 24px;
                    border-radius: 10px;
                    font-weight: 600;
                    transition: all 0.3s ease;
                    margin-top: 20px;
                }
                
                .back-button:hover {
                    transform: translateY(-2px);
                    box-shadow: 0 10px 25px rgba(16, 185, 129, 0.3);
                }
            </style>
        </head>
        <body>
            <div class="error-container">
                <div class="error-icon">PDF ERROR</div>
                <h2>PDF Export Error</h2>
                <p>We encountered an issue while generating your PDF report. This might be due to a temporary system issue or configuration problem.</p>
                <div class="error-details">
                    <strong>Technical Details:</strong><br>
                    ' . htmlspecialchars($e->getMessage()) . '
                </div>
                <p>Please try again in a few moments. If the problem persists, try exporting in a different format like Excel or CSV.</p>
                <a href="javascript:history.back()" class="back-button">
                    Go Back & Try Again
                </a>
            </div>
        </body>
        </html>';
    }
    exit();
}

function exportJSON($students, $totalClasses, $filename) {
    // Clear any previous output
    if (ob_get_length()) ob_end_clean();
    
    header('Content-Type: application/json; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '.json"');
    header('Cache-Control: max-age=0');
    
    $data = [
        'report_metadata' => [
            'title' => 'Student Attendance Report',
            'generated_timestamp' => date('c'), // ISO 8601 format
            'generated_on_readable' => date('Y-m-d H:i:s'),
            'total_classes_conducted' => $totalClasses,
            'total_students' => count($students),
            'average_attendance_percentage' => calculateAverageAttendance($students, $totalClasses)
        ],
        'column_definitions' => [
            'serial_number' => 'Sequential number for each student',
            'student_id' => 'Unique identifier for the student',
            'section' => 'Academic section/class of the student',
            'student_name' => 'Full name of the student',
            'days_present' => 'Number of classes attended',
            'total_classes' => 'Total number of classes conducted',
            'attendance_percentage' => 'Percentage of classes attended'
        ],
        'student_records' => []
    ];
    
    $count = 1;
    foreach ($students as $student) {
        $presentCount = (int) $student['present_count'];
        $attendancePercentage = ($totalClasses > 0) ? round(($presentCount / $totalClasses) * 100, 2) : 0;
        
        // Determine performance category
        $performanceCategory = '';
        if ($attendancePercentage >= 90) $performanceCategory = 'excellent';
        elseif ($attendancePercentage >= 80) $performanceCategory = 'good';
        elseif ($attendancePercentage >= 60) $performanceCategory = 'average';
        else $performanceCategory = 'poor';
        
        $data['student_records'][] = [
            'serial_number' => $count++,
            'student_id' => $student['student_id'] ?? '',
            'section' => $student['section'] ?? '',
            'student_name' => $student['name'] ?? '',
            'days_present' => $presentCount,
            'total_classes' => $totalClasses,
            'attendance_percentage' => $attendancePercentage,
            'performance_category' => $performanceCategory
        ];
    }
    
    echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit();
}

function exportXML($students, $totalClasses, $filename) {
    // Clear any previous output
    if (ob_get_length()) ob_end_clean();
    
    header('Content-Type: application/xml; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '.xml"');
    header('Cache-Control: max-age=0');
    
    echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    echo '<attendance_report>' . "\n";
    echo '  <report_metadata>' . "\n";
    echo '    <title>Student Attendance Report</title>' . "\n";
    echo '    <generated_timestamp>' . date('c') . '</generated_timestamp>' . "\n";
    echo '    <generated_on_readable>' . date('Y-m-d H:i:s') . '</generated_on_readable>' . "\n";
    echo '    <total_classes_conducted>' . $totalClasses . '</total_classes_conducted>' . "\n";
    echo '    <total_students>' . count($students) . '</total_students>' . "\n";
    echo '    <average_attendance_percentage>' . calculateAverageAttendance($students, $totalClasses) . '</average_attendance_percentage>' . "\n";
    echo '  </report_metadata>' . "\n";
    echo '  <student_records>' . "\n";
    
    $count = 1;
    foreach ($students as $student) {
        $presentCount = (int) $student['present_count'];
        $attendancePercentage = ($totalClasses > 0) ? round(($presentCount / $totalClasses) * 100, 2) : 0;
        
        // Determine performance category
        $performanceCategory = '';
        if ($attendancePercentage >= 90) $performanceCategory = 'excellent';
        elseif ($attendancePercentage >= 80) $performanceCategory = 'good';
        elseif ($attendancePercentage >= 60) $performanceCategory = 'average';
        else $performanceCategory = 'poor';
        
        echo '    <student>' . "\n";
        echo '      <serial_number>' . $count++ . '</serial_number>' . "\n";
        echo '      <student_id>' . htmlspecialchars($student['student_id'] ?? '') . '</student_id>' . "\n";
        echo '      <section>' . htmlspecialchars($student['section'] ?? '') . '</section>' . "\n";
        echo '      <student_name>' . htmlspecialchars($student['name'] ?? '') . '</student_name>' . "\n";
        echo '      <days_present>' . $presentCount . '</days_present>' . "\n";
        echo '      <total_classes>' . $totalClasses . '</total_classes>' . "\n";
        echo '      <attendance_percentage>' . $attendancePercentage . '</attendance_percentage>' . "\n";
        echo '      <performance_category>' . $performanceCategory . '</performance_category>' . "\n";
        echo '    </student>' . "\n";
    }
    
    echo '  </student_records>' . "\n";
    echo '</attendance_report>';
    exit();
}

function exportHTML($students, $totalClasses, $filename, $startDateFormatted) {
    // Clear any previous output
    if (ob_get_length()) ob_end_clean();
    
    header('Content-Type: text/html; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '.html"');
    header('Cache-Control: max-age=0');
    
    echo '<!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Student Attendance Report</title>
        <style>
            body { 
                font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif; 
                margin: 20px; 
                background: #f5f5f5; 
                color: #333;
            }
            .container { 
                max-width: 1200px; 
                margin: 0 auto; 
                background: white; 
                padding: 30px; 
                border-radius: 12px; 
                box-shadow: 0 4px 20px rgba(0,0,0,0.1); 
            }
            h1 { 
                text-align: center; 
                color: #059669; 
                margin-bottom: 10px; 
                font-size: 2.2rem;
            }
            .info { 
                text-align: center; 
                margin-bottom: 30px; 
                color: #666; 
                background: #f8fafc;
                padding: 15px;
                border-radius: 8px;
                border-left: 4px solid #10b981;
            }
            table { 
                width: 100%; 
                border-collapse: collapse; 
                margin-top: 20px; 
                font-size: 14px;
            }
            th, td { 
                border: 1px solid #e2e8f0; 
                padding: 12px 8px; 
                vertical-align: middle;
            }
            th { 
                background: linear-gradient(135deg, #10b981, #059669); 
                color: white; 
                font-weight: 600; 
                text-align: center;
                font-size: 13px;
            }
            tbody tr:nth-child(even) { 
                background-color: #f8fafc; 
            }
            tbody tr:hover { 
                background-color: #ecfdf5; 
                transition: background-color 0.2s ease;
            }
            
            /* Column-specific alignment */
            .col-serial { 
                text-align: center; 
                font-weight: bold; 
                color: #6b7280;
                width: 8%;
            }
            .col-student-id { 
                text-align: center; 
                font-weight: bold; 
                font-family: monospace;
                width: 15%;
            }
            .col-section { 
                text-align: center; 
                width: 10%;
            }
            .col-name { 
                text-align: left; 
                font-weight: 500;
                width: 35%;
                padding-left: 12px;
            }
            .col-present { 
                text-align: center; 
                font-weight: bold;
                width: 12%;
            }
            .col-total { 
                text-align: center; 
                width: 12%;
            }
            .col-percentage { 
                text-align: center; 
                font-weight: bold;
                width: 14%;
            }
            
            /* Performance-based colors */
            .excellent { color: #10b981; }
            .good { color: #3b82f6; }
            .average { color: #f59e0b; }
            .poor { color: #ef4444; }
            
            .stats { 
                display: flex; 
                justify-content: space-around; 
                margin-bottom: 30px; 
                flex-wrap: wrap;
                gap: 15px;
            }
            .stat { 
                text-align: center; 
                padding: 20px; 
                background: linear-gradient(135deg, #ecfdf5, #d1fae5); 
                border-radius: 12px; 
                flex: 1;
                min-width: 150px;
                border: 2px solid #a7f3d0;
            }
            .stat-number { 
                font-size: 28px; 
                font-weight: bold; 
                color: #059669; 
                display: block;
            }
            .stat-label { 
                color: #374151; 
                font-size: 14px; 
                font-weight: 500;
                margin-top: 5px;
            }
            
            @media print { 
                body { background: white; } 
                .container { box-shadow: none; } 
                .stats { display: none; }
            }
            
            @media (max-width: 768px) {
                .container { padding: 15px; }
                table { font-size: 12px; }
                th, td { padding: 8px 4px; }
                .stats { flex-direction: column; }
            }
        </style>
    </head>
    <body>
        <div class="container">
            <h1>Student Attendance Report</h1>
            <div class="info">
                <p><strong>Attendance Period:</strong> From ' . htmlspecialchars($startDateFormatted) . '</p>
                <p><strong>Generated on:</strong> ' . date('l, F j, Y \a\t g:i A') . '</p>
            </div>
            
            <div class="stats">
                <div class="stat">
                    <span class="stat-number">' . count($students) . '</span>
                    <div class="stat-label">Total Students</div>
                </div>
                <div class="stat">
                    <span class="stat-number">' . $totalClasses . '</span>
                    <div class="stat-label">Classes Conducted</div>
                </div>
                <div class="stat">
                    <span class="stat-number">' . calculateAverageAttendance($students, $totalClasses) . '%</span>
                    <div class="stat-label">Average Attendance</div>
                </div>
            </div>
            
            <table>
                <thead>
                    <tr>
                        <th class="col-serial">Serial No</th>
                        <th class="col-student-id">Student ID</th>
                        <th class="col-section">Section</th>
                        <th class="col-name">Student Name</th>
                        <th class="col-present">Present Days</th>
                        <th class="col-total">Total Classes</th>
                        <th class="col-percentage">Attendance %</th>
                    </tr>
                </thead>
                <tbody>';
    
    $count = 1;
    foreach ($students as $student) {
        $presentCount = (int) $student['present_count'];
        $attendancePercentage = ($totalClasses > 0) ? round(($presentCount / $totalClasses) * 100, 1) : 0;
        
        // Determine performance class and icon
        $performanceClass = '';
        $performanceIcon = '';
        if ($attendancePercentage >= 90) { 
            $performanceClass = 'excellent'; 
            $performanceIcon = 'E';
        } elseif ($attendancePercentage >= 80) { 
            $performanceClass = 'good'; 
            $performanceIcon = 'G';
        } elseif ($attendancePercentage >= 60) { 
            $performanceClass = 'average'; 
            $performanceIcon = 'A';
        } else { 
            $performanceClass = 'poor'; 
            $performanceIcon = 'P';
        }
        
        echo '<tr>
                <td class="col-serial">' . $count++ . '</td>
                <td class="col-student-id">' . htmlspecialchars($student['student_id'] ?? '') . '</td>
                <td class="col-section">' . htmlspecialchars($student['section'] ?? '') . '</td>
                <td class="col-name">' . htmlspecialchars($student['name'] ?? '') . '</td>
                <td class="col-present">' . $presentCount . '</td>
                <td class="col-total">' . $totalClasses . '</td>
                <td class="col-percentage ' . $performanceClass . '">' . $performanceIcon . ' ' . $attendancePercentage . '%</td>
            </tr>';
    }
    
    echo '      </tbody>
            </table>
            
            <div style="margin-top: 30px; padding: 20px; background: #f8fafc; border-radius: 8px; border-left: 4px solid #10b981;">
                <h3 style="color: #059669; margin-bottom: 10px;">Report Legend</h3>
                <div style="display: flex; gap: 20px; flex-wrap: wrap;">
                    <span style="color: #10b981;">E = Excellent (90%+)</span>
                    <span style="color: #3b82f6;">G = Good (80-89%)</span>
                    <span style="color: #f59e0b;">A = Average (60-79%)</span>
                    <span style="color: #ef4444;">P = Poor (<60%)</span>
                </div>
            </div>
        </div>
    </body>
    </html>';
    exit();
}

function calculateAverageAttendance($students, $totalClasses) {
    if (empty($students) || $totalClasses <= 0) return 0;
    
    $totalPercentage = 0;
    foreach ($students as $student) {
        $presentCount = (int) $student['present_count'];
        $attendancePercentage = ($presentCount / $totalClasses) * 100;
        $totalPercentage += $attendancePercentage;
    }
    
    return round($totalPercentage / count($students), 1);
}
?>