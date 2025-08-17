<?php
session_start();

if (!isset($_SESSION["user"])) {
    header("Location: login.php");
    exit();
}

// Check if format is Excel
if (!isset($_GET['format']) || $_GET['format'] !== 'excel') {
    header("Location: attendance_grid.php");
    exit();
}

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

// Generate Excel-compatible HTML
$filename = 'attendance-grid-' . date('Y-m-d', strtotime($startDate)) . '-to-' . date('Y-m-d', strtotime($endDate)) . '.xls';

header('Content-Type: application/vnd.ms-excel; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

// Start Excel content
echo '<?xml version="1.0" encoding="UTF-8"?>';
?>
<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta name="ProgId" content="Excel.Sheet">
    <meta name="Generator" content="Cloud Computing Attendance System">
    <!--[if gte mso 9]>
    <xml>
        <x:ExcelWorkbook>
            <x:ExcelWorksheets>
                <x:ExcelWorksheet>
                    <x:Name>Attendance Grid</x:Name>
                    <x:WorksheetOptions>
                        <x:Print>
                            <x:ValidPrinterInfo/>
                        </x:Print>
                    </x:WorksheetOptions>
                </x:ExcelWorksheet>
            </x:ExcelWorksheets>
        </x:ExcelWorkbook>
    </xml>
    <![endif]-->
    <style>
        .header { background-color: #059669; color: white; font-weight: bold; text-align: center; }
        .student-info { background-color: #f0f9ff; font-weight: bold; }
        .present { background-color: #10b981; color: white; text-align: center; font-weight: bold; }
        .absent { background-color: #ef4444; color: white; text-align: center; font-weight: bold; }
        .summary { background-color: #fef3c7; font-weight: bold; }
        .center { text-align: center; }
        table { border-collapse: collapse; }
        td, th { border: 1px solid #000; padding: 5px; }
    </style>
</head>
<body>
    <table>
        <!-- Title and metadata -->
        <tr>
            <td colspan="<?= 3 + count($classDates) + 3 ?>" class="header" style="font-size: 16px; padding: 10px;">
                Cloud Computing - Attendance Grid Report
            </td>
        </tr>
        <tr>
            <td colspan="<?= 3 + count($classDates) + 3 ?>" class="center">
                Generated on: <?= date('Y-m-d H:i:s') ?>
            </td>
        </tr>
        <tr>
            <td colspan="<?= 3 + count($classDates) + 3 ?>" class="center">
                Date Range: <?= date('M j, Y', strtotime($startDate)) ?> to <?= date('M j, Y', strtotime($endDate)) ?>
            </td>
        </tr>
        <?php if (!empty($searchQuery)): ?>
        <tr>
            <td colspan="<?= 3 + count($classDates) + 3 ?>" class="center">
                Search Filter: <?= htmlspecialchars($searchQuery) ?>
            </td>
        </tr>
        <?php endif; ?>
        <tr>
            <td colspan="<?= 3 + count($classDates) + 3 ?>" class="center">
                Total Students: <?= count($students) ?> | Total Class Days: <?= count($classDates) ?>
            </td>
        </tr>
        <tr><td colspan="<?= 3 + count($classDates) + 3 ?>"></td></tr> <!-- Empty row -->
        
        <!-- Column headers -->
        <tr>
            <td class="header">Student ID</td>
            <td class="header">Student Name</td>
            <td class="header">Section</td>
            <?php foreach ($classDates as $date): ?>
                <td class="header center" style="writing-mode: vertical-rl; text-orientation: mixed;">
                    <?= date('M j, Y', strtotime($date)) ?><br><?= date('(D)', strtotime($date)) ?>
                </td>
            <?php endforeach; ?>
            <td class="header">Total Present</td>
            <td class="header">Total Classes</td>
            <td class="header">Attendance %</td>
        </tr>
        
        <!-- Student data rows -->
        <?php foreach ($students as $student): ?>
        <tr>
            <td class="student-info"><?= htmlspecialchars($student['student_id']) ?></td>
            <td class="student-info"><?= htmlspecialchars($student['name']) ?></td>
            <td class="student-info center"><?= htmlspecialchars($student['section']) ?></td>
            <?php 
            $presentCount = 0;
            foreach ($classDates as $date): 
                $isPresent = isset($attendanceData[$student['student_id']][$date]);
                if ($isPresent) $presentCount++;
            ?>
                <td class="<?= $isPresent ? 'present' : 'absent' ?>">
                    <?= $isPresent ? 'P' : 'A' ?>
                </td>
            <?php endforeach; ?>
            <td class="center summary"><?= $presentCount ?></td>
            <td class="center summary"><?= count($classDates) ?></td>
            <td class="center summary">
                <?= count($classDates) > 0 ? round(($presentCount / count($classDates)) * 100, 1) . '%' : '0%' ?>
            </td>
        </tr>
        <?php endforeach; ?>
        
        <!-- Summary section -->
        <tr><td colspan="<?= 3 + count($classDates) + 3 ?>"></td></tr> <!-- Empty row -->
        <tr>
            <td colspan="3" class="summary">SUMMARY STATISTICS</td>
            <?php
            $totalPresent = 0;
            foreach ($students as $student) {
                foreach ($classDates as $date) {
                    if (isset($attendanceData[$student['student_id']][$date])) {
                        $totalPresent++;
                    }
                }
            }
            $totalPossible = count($students) * count($classDates);
            $overallRate = $totalPossible > 0 ? round(($totalPresent / $totalPossible) * 100, 1) : 0;
            ?>
            <td colspan="<?= count($classDates) ?>" class="summary center">
                Overall Attendance Rate: <?= $overallRate ?>%
            </td>
            <td class="summary center"><?= $totalPresent ?></td>
            <td class="summary center"><?= $totalPossible ?></td>
            <td class="summary center"><?= $overallRate ?>%</td>
        </tr>
        <tr>
            <td colspan="3" class="summary">Total Present Instances:</td>
            <td colspan="<?= count($classDates) + 3 ?>" class="summary"><?= $totalPresent ?></td>
        </tr>
        <tr>
            <td colspan="3" class="summary">Total Possible Instances:</td>
            <td colspan="<?= count($classDates) + 3 ?>" class="summary"><?= $totalPossible ?></td>
        </tr>
        
        <!-- Legend -->
        <tr><td colspan="<?= 3 + count($classDates) + 3 ?>"></td></tr> <!-- Empty row -->
        <tr>
            <td colspan="<?= 3 + count($classDates) + 3 ?>" class="center">
                <strong>Legend:</strong> P = Present | A = Absent
            </td>
        </tr>
        <tr>
            <td colspan="<?= 3 + count($classDates) + 3 ?>" class="center" style="font-size: 10px; color: #666;">
                Generated by Cloud Computing Attendance Management System
            </td>
        </tr>
    </table>
</body>
</html>
<?php
exit();
?>