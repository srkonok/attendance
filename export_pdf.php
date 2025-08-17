<?php
// This file handles PDF export specifically
include 'db.php';

// Get parameters from URL
$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$endDate = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-t');
$searchQuery = isset($_GET['search']) ? trim($_GET['search']) : '';

// If no dates provided, get last 30 days of classes
if (empty($startDate) || empty($endDate)) {
    $dateRangeQuery = "SELECT MIN(date) as min_date, MAX(date) as max_date FROM attendance";
    $dateRangeStmt = $conn->prepare($dateRangeQuery);
    $dateRangeStmt->execute();
    $dateRange = $dateRangeStmt->fetch();
    
    if ($dateRange['max_date']) {
        $endDate = $dateRange['max_date'];
        $startDate = max($dateRange['min_date'], date('Y-m-d', strtotime($endDate . ' -29 days')));
    } else {
        $startDate = date('Y-m-01');
        $endDate = date('Y-m-t');
    }
}

// Get all class dates within the range
$classesQuery = "SELECT DISTINCT date FROM attendance WHERE date BETWEEN :start_date AND :end_date ORDER BY date ASC";
$classesStmt = $conn->prepare($classesQuery);
$classesStmt->bindParam(':start_date', $startDate);
$classesStmt->bindParam(':end_date', $endDate);
$classesStmt->execute();
$classDates = $classesStmt->fetchAll(PDO::FETCH_COLUMN);

// Fetch students with search applied
$studentsQuery = "SELECT student_id, name, section FROM students 
    WHERE name LIKE :search OR student_id LIKE :search OR section LIKE :search
    ORDER BY section ASC, student_id ASC";
$studentsStmt = $conn->prepare($studentsQuery);
$studentsStmt->bindValue(':search', '%' . $searchQuery . '%', PDO::PARAM_STR);
$studentsStmt->execute();
$students = $studentsStmt->fetchAll();

// Fetch attendance data
$attendanceData = [];
if (!empty($students) && !empty($classDates)) {
    $studentIds = array_column($students, 'student_id');
    $placeholders = str_repeat('?,', count($studentIds) - 1) . '?';
    
    $attendanceQuery = "SELECT student_id, date FROM attendance 
        WHERE student_id IN ($placeholders) 
        AND date BETWEEN ? AND ?";
    
    $params = array_merge($studentIds, [$startDate, $endDate]);
    $attendanceStmt = $conn->prepare($attendanceQuery);
    $attendanceStmt->execute($params);
    
    while ($row = $attendanceStmt->fetch()) {
        $attendanceData[$row['student_id']][$row['date']] = true;
    }
}

// Calculate statistics
$totalClasses = count($classDates);
$totalPresent = 0;
$totalPossible = $totalClasses * count($students);

foreach ($students as $student) {
    $studentId = $student['student_id'];
    foreach ($classDates as $date) {
        if (isset($attendanceData[$studentId][$date])) {
            $totalPresent++;
        }
    }
}

$overallAttendanceRate = $totalPossible > 0 ? round(($totalPresent / $totalPossible) * 100, 1) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance Grid - PDF Export</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Arial', sans-serif;
            font-size: 10px;
            color: #333;
            background: white;
            padding: 15px;
        }
        
        .pdf-header {
            text-align: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #059669;
        }
        
        .pdf-header h1 {
            color: #059669;
            font-size: 18px;
            margin-bottom: 5px;
            font-weight: bold;
        }
        
        .pdf-header p {
            color: #666;
            font-size: 12px;
        }
        
        .stats-section {
            display: flex;
            justify-content: space-around;
            margin: 15px 0;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 8px;
        }
        
        .stat-item {
            text-align: center;
        }
        
        .stat-item .number {
            font-size: 16px;
            font-weight: bold;
            color: #059669;
        }
        
        .stat-item .label {
            font-size: 10px;
            color: #666;
            margin-top: 2px;
        }
        
        .attendance-table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
            font-size: 8px;
        }
        
        .attendance-table th,
        .attendance-table td {
            border: 1px solid #ddd;
            padding: 4px;
            text-align: center;
            vertical-align: middle;
        }
        
        .attendance-table th {
            background: #059669;
            color: white;
            font-weight: bold;
            font-size: 9px;
        }
        
        .attendance-table th.student-info {
            text-align: left;
            padding: 6px;
        }
        
        .attendance-table td.student-info {
            text-align: left;
            padding: 5px;
            font-weight: 500;
        }
        
        .attendance-table td.student-id {
            font-family: 'Courier New', monospace;
            font-weight: bold;
            color: #059669;
        }
        
        .attendance-status {
            display: inline-block;
            width: 18px;
            height: 18px;
            border-radius: 50%;
            line-height: 18px;
            font-size: 10px;
            font-weight: bold;
            color: white;
        }
        
        .attendance-status.present {
            background: #10b981;
        }
        
        .attendance-status.absent {
            background: #ef4444;
        }
        
        .date-header {
            writing-mode: vertical-rl;
            text-orientation: mixed;
            min-width: 25px;
            max-width: 25px;
            height: 60px;
            font-size: 7px;
        }
        
        .legend {
            margin-top: 15px;
            text-align: center;
            font-size: 10px;
        }
        
        .legend-item {
            display: inline-block;
            margin: 0 15px;
            vertical-align: middle;
        }
        
        .legend .attendance-status {
            width: 15px;
            height: 15px;
            line-height: 15px;
            font-size: 8px;
            margin-right: 5px;
            vertical-align: middle;
        }
        
        .footer {
            margin-top: 20px;
            text-align: center;
            font-size: 9px;
            color: #666;
            border-top: 1px solid #ddd;
            padding-top: 10px;
        }
    </style>
</head>
<body>
    <div id="pdf-content">
        <!-- Header -->
        <div class="pdf-header">
            <h1>📊 Cloud Computing - Attendance Grid</h1>
            <p>📅 <?= date('M j, Y', strtotime($startDate)) ?> - <?= date('M j, Y', strtotime($endDate)) ?></p>
            <?php if (!empty($searchQuery)): ?>
            <p>🔍 Search: "<?= htmlspecialchars($searchQuery) ?>"</p>
            <?php endif; ?>
            <p>Generated on <?= date('M j, Y \a\t g:i A') ?></p>
        </div>
        
        <!-- Statistics -->
        <div class="stats-section">
            <div class="stat-item">
                <div class="number"><?= count($students) ?></div>
                <div class="label">Students</div>
            </div>
            <div class="stat-item">
                <div class="number"><?= $totalClasses ?></div>
                <div class="label">Class Days</div>
            </div>
            <div class="stat-item">
                <div class="number"><?= $totalPresent ?></div>
                <div class="label">Total Present</div>
            </div>
            <div class="stat-item">
                <div class="number"><?= $overallAttendanceRate ?>%</div>
                <div class="label">Overall Rate</div>
            </div>
        </div>
        
        <?php if (!empty($students) && !empty($classDates)): ?>
        <!-- Attendance Grid -->
        <table class="attendance-table">
            <thead>
                <tr>
                    <th class="student-info">Student ID</th>
                    <th class="student-info">Name</th>
                    <th class="student-info">Section</th>
                    <?php foreach ($classDates as $date): ?>
                        <th class="date-header" title="<?= date('M j, Y', strtotime($date)) ?>">
                            <?= date('M\nj', strtotime($date)) ?>
                        </th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($students as $student): ?>
                    <tr>
                        <td class="student-info student-id"><?= htmlspecialchars($student['student_id']) ?></td>
                        <td class="student-info"><?= htmlspecialchars($student['name']) ?></td>
                        <td class="student-info"><?= htmlspecialchars($student['section']) ?></td>
                        <?php foreach ($classDates as $date): ?>
                            <td>
                                <?php
                                $isPresent = isset($attendanceData[$student['student_id']][$date]);
                                if ($isPresent) {
                                    echo '<span class="attendance-status present">P</span>';
                                } else {
                                    echo '<span class="attendance-status absent">A</span>';
                                }
                                ?>
                            </td>
                        <?php endforeach; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <!-- Legend -->
        <div class="legend">
            <div class="legend-item">
                <span class="attendance-status present">P</span>
                Present
            </div>
            <div class="legend-item">
                <span class="attendance-status absent">A</span>
                Absent
            </div>
        </div>
        <?php else: ?>
        <div style="text-align: center; padding: 40px; color: #666;">
            <h3>No Data Available</h3>
            <p>No students or class dates found for the selected criteria.</p>
        </div>
        <?php endif; ?>
        
        <!-- Footer -->
        <div class="footer">
            <p>Cloud Computing Attendance Management System</p>
            <p>This report contains <?= count($students) ?> students across <?= $totalClasses ?> class days</p>
        </div>
    </div>
    
    <script>
        // Auto-generate PDF when page loads
        window.onload = function() {
            const element = document.getElementById('pdf-content');
            const opt = {
                margin: 0.5,
                filename: 'attendance-grid-<?= date('Y-m-d', strtotime($startDate)) ?>-to-<?= date('Y-m-d', strtotime($endDate)) ?>.pdf',
                image: { type: 'jpeg', quality: 0.98 },
                html2canvas: { 
                    scale: 2,
                    useCORS: true,
                    letterRendering: true,
                    logging: false
                },
                jsPDF: { 
                    unit: 'in', 
                    format: 'a4', 
                    orientation: 'landscape' // Use landscape for better grid view
                }
            };
            
            // Generate PDF and download
            html2pdf().set(opt).from(element).save().then(function() {
                // Close the window after PDF is generated
                setTimeout(function() {
                    window.close();
                    // If window.close() doesn't work (some browsers block it)
                    if (!window.closed) {
                        window.location.href = 'attendance_grid.php';
                    }
                }, 1000);
            });
        };
    </script>
</body>
</html>