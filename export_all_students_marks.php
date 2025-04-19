<?php
include 'db.php';

$exportAll   = isset($_GET['export']) ? true : false;
$searchQuery = isset($_GET['search']) ? trim($_GET['search']) : '';
$page        = isset($_GET['page']) && is_numeric($_GET['page']) ? (int) $_GET['page'] : 1;
$limit       = $exportAll ? null : 15;
$offset      = ($page - 1) * ($limit ?? 0);

$startDateFormatted = "Unknown";
try {
    $startDateStmt = $conn->prepare("SELECT MIN(date) FROM attendance");
    $startDateStmt->execute();
    $startDate = $startDateStmt->fetchColumn();
    if ($startDate) {
        $startDateFormatted = date("d M Y", strtotime($startDate));
    }
} catch (PDOException $e) {
    error_log("Error fetching start date: " . $e->getMessage());
}

$totalClasses = 0;
try {
    $totalClassesStmt = $conn->prepare("SELECT COUNT(DISTINCT date) FROM attendance");
    $totalClassesStmt->execute();
    $totalClasses = (int) $totalClassesStmt->fetchColumn();
} catch (PDOException $e) {
    error_log("Error fetching total classes: " . $e->getMessage());
}

$students = [];
try {
    $query = "SELECT s.*,
        COALESCE(a.attendance_count, 0) AS present_count,
        COALESCE(m.class_test_1, 0) AS class_test_1,
        COALESCE(m.class_test_2, 0) AS class_test_2,
        COALESCE(m.class_test_3, 0) AS class_test_3,
        COALESCE(m.assignment_1, 0) AS assignment_1,
        COALESCE(m.assignment_2, 0) AS assignment_2
    FROM students s
    LEFT JOIN (
        SELECT student_id, COUNT(*) AS attendance_count
        FROM attendance
        GROUP BY student_id
    ) a ON s.student_id = a.student_id
    LEFT JOIN student_marks m ON s.student_id = m.student_id
    WHERE s.name LIKE :search OR s.student_id LIKE :search OR s.section LIKE :search
    ORDER BY student_id ASC";

    if ($limit !== null) {
        $query .= " LIMIT :limit OFFSET :offset";
    }

    $stmt = $conn->prepare($query);
    $stmt->bindValue(':search', '%' . $searchQuery . '%');
    if ($limit !== null) {
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    }
    $stmt->execute();
    $students = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Error fetching students: " . $e->getMessage());
}

$totalStudents = 0;
if (! $exportAll) {
    try {
        $totalStmt = $conn->prepare("SELECT COUNT(*) FROM students WHERE name LIKE :search OR student_id LIKE :search OR section LIKE :search");
        $totalStmt->bindValue(':search', '%' . $searchQuery . '%');
        $totalStmt->execute();
        $totalStudents = $totalStmt->fetchColumn();
    } catch (PDOException $e) {
        error_log("Error counting students: " . $e->getMessage());
    }
}
$totalPages = $limit ? ceil($totalStudents / $limit) : 1;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Cloud Computing Attendance</title>
    <link rel="stylesheet" href="style.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.25/jspdf.plugin.autotable.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.17.0/xlsx.full.min.js"></script>
</head>
<body>
<div class="table-container">
    <div class="students-list">
        <h2>Attendance from <?php echo htmlspecialchars($startDateFormatted); ?></h2>

        <!-- Search -->
        <div class="search-form">
            <form method="GET">
                <input type="text" name="search" value="<?php echo htmlspecialchars($searchQuery); ?>" placeholder="Search students...">
                <button type="submit">Search</button>
            </form>
        </div>

        <!-- Export -->
        <select onchange="handleExport(this.value)">
            <option value="">Export Format</option>
            <option value="pdf">PDF</option>
            <option value="csv">CSV</option>
            <option value="excel">Excel</option>
        </select>

        <!-- Table -->
        <div class="table-wrapper" style="overflow-x: auto;">
            <table id="exportTable" class="students-table" style="min-width: 1200px;">
            <thead>
                <tr>
                <th>#</th>
                <th>ID</th>
                <th>Name</th>
                <th>Section</th>
                <!-- <th>Present</th> -->
                <th>Attendance %</th>
                <th>Quiz 1</th>
                <th>Quiz 2</th>
                <th>Quiz 3</th>
                <th>Assign 1</th>
                <th>Assign 2</th>
                <th>Attendance(10)</th>
                <th>Quiz(20)</th>
                <th>Total (30)</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($students as $index => $student): 
                $present    = (int)($student['present_count'] ?? 0);
                $percentage = $totalClasses > 0 ? round(($present / $totalClasses) * 100, 2) : 0;
        
                $q1 = $student['class_test_1'] ?? 0;
                $q2 = $student['class_test_2'] ?? 0;
                $q3 = $student['class_test_3'] ?? 0;
                $a1 = $student['assignment_1'] ?? 0;
                $a2 = $student['assignment_2'] ?? 0;
        
                $assignmentSum = $a1 + $a2;
                $allScores = [$q1, $q2, $q3, $assignmentSum];
                rsort($allScores);
                $topThreeSum  = array_sum(array_slice($allScores, 0, 3));
                $academicMark = ceil($topThreeSum / 3);
                $attendanceMark = $percentage >= 80 ? 10 : floor(($percentage / 80) * 10);
                $totalMark = $academicMark + $attendanceMark;

            ?>
                <tr>
                <td><?= $index + 1 ?></td>
                <td><?= htmlspecialchars($student['student_id']) ?></td>
                <td><?= htmlspecialchars($student['name']) ?></td>
                <td><?= htmlspecialchars($student['section']) ?></td>
                <!-- <td><?= $present ?></td> -->
                <td><?= $percentage ?>%</td>
                <td><?= $q1 ?></td>
                <td><?= $q2 ?></td>
                <td><?= $q3 ?></td>
                <td><?= $a1 ?></td>
                <td><?= $a2 ?></td>
                <td><?= $attendanceMark ?></td>
                <td><?= $academicMark ?></td>
                <td><?= $totalMark ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if (! $exportAll && $totalPages > 1): ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="?page=1&search=<?= urlencode($searchQuery) ?>">First</a>
                    <a href="?page=<?= $page - 1 ?>&search=<?= urlencode($searchQuery) ?>">Previous</a>
                <?php endif; ?>
                <span>Page <?= $page ?> of <?= $totalPages ?></span>
                <?php if ($page < $totalPages): ?>
                    <a href="?page=<?= $page + 1 ?>&search=<?= urlencode($searchQuery) ?>">Next</a>
                    <a href="?page=<?= $totalPages ?>&search=<?= urlencode($searchQuery) ?>">Last</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function handleExport(type) {
    const url = new URL(window.location.href);
    url.searchParams.set('export', 'true');
    if (type === 'pdf') {
        url.searchParams.set('exportType', 'pdf');
        window.location.href = url.toString();
    } else if (type === 'csv') {
        exportToCSV();
    } else if (type === 'excel') {
        exportToExcel();
    }
}

window.addEventListener('load', () => {
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('exportType') === 'pdf') {
        exportToPDF();
        history.replaceState({}, '', window.location.pathname);
    }
});

function exportToPDF() {
    const { jsPDF } = window.jspdf;
    const doc = new jsPDF('landscape');
    doc.autoTable({ html: '#exportTable', theme: 'grid', headStyles: { fillColor: [41, 128, 185], textColor: 255 }, styles: { fontSize: 9 }, margin: { top: 20 } });
    doc.save('student-report.pdf');
}

function exportToCSV() {
    const rows = Array.from(document.querySelectorAll('#exportTable tr'));
    const csvContent = rows.map(row =>
        Array.from(row.querySelectorAll('th, td')).map(cell =>
            `"${cell.innerText.replace(/"/g, '""')}"`
        ).join(',')
    ).join('\n');

    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = 'student-report.csv';
    link.click();
}

function exportToExcel() {
    const wb = XLSX.utils.table_to_book(document.getElementById('exportTable'));
    XLSX.writeFile(wb, 'student-report.xlsx');
}
</script>
</body>
</html>
