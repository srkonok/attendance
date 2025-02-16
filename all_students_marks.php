<?php
include 'db.php';

// Pagination settings
$limit = 15; // Students per page
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Get the earliest attendance date
$startDateQuery = "SELECT MIN(date) FROM attendance";
$startDateStmt = $conn->prepare($startDateQuery);
$startDateStmt->execute();
$startDate = $startDateStmt->fetchColumn();
$startDateFormatted = $startDate ? date("d M Y", strtotime($startDate)) : "Unknown";

// Sorting settings
$sortColumn = isset($_GET['sort']) && in_array($_GET['sort'], ['section', 'name']) ? $_GET['sort'] : 'student_id';
$sortOrder = isset($_GET['order']) && in_array($_GET['order'], ['asc', 'desc']) ? $_GET['order'] : 'asc';
$newSortOrder = ($sortOrder === 'asc') ? 'desc' : 'asc';

// Search settings
$searchQuery = isset($_GET['search']) ? trim($_GET['search']) : '';

// Fetch total students count with search applied
$totalQuery = "SELECT COUNT(*) FROM students WHERE 
    name LIKE :search OR 
    student_id LIKE :search OR 
    section LIKE :search";
$totalStmt = $conn->prepare($totalQuery);
$totalStmt->bindValue(':search', '%' . $searchQuery . '%', PDO::PARAM_STR);
$totalStmt->execute();
$totalStudents = $totalStmt->fetchColumn();
$totalPages = ceil($totalStudents / $limit);

// Get total number of classes conducted
$totalClassesQuery = "SELECT COUNT(DISTINCT date) FROM attendance";
$totalClassesStmt = $conn->prepare($totalClassesQuery);
$totalClassesStmt->execute();
$totalClasses = (int) $totalClassesStmt->fetchColumn();

// Fetch students with attendance count and marks
$studentsQuery = "
    SELECT s.*, 
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
    WHERE s.name LIKE :search OR 
          s.student_id LIKE :search OR 
          s.section LIKE :search
    ORDER BY $sortColumn $sortOrder 
    LIMIT :limit OFFSET :offset";

$studentsStmt = $conn->prepare($studentsQuery);
$studentsStmt->bindValue(':search', '%' . $searchQuery . '%', PDO::PARAM_STR);
$studentsStmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$studentsStmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$studentsStmt->execute();
$students = $studentsStmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cloud Computing Attendance</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="table-container">
    <div class="students-list">
        <h2>Attendance from <?= htmlspecialchars($startDateFormatted) ?></h2>

        <!-- Search Bar -->
        <div class="search-form">
            <form method="GET" action="">
                <input type="text" name="search" value="<?= htmlspecialchars($searchQuery) ?>" placeholder="Search by name, ID, or section">
                <button type="submit">Search</button>
            </form>
        </div>

        <div class="table-wrapper">
            <table class="students-table">
                <thead>
                    <tr>
                        <th>Serial No</th>
                        <th>Student ID</th>
                        <th>
                            Section
                        </th>
                        <th>Name</th>
                        <th>Total Attendance</th>
                        <th>Present (%)</th>
                        <th>Class Test 1</th>
                        <th>Class Test 2</th>
                        <th>Class Test 3</th>
                        <th>Assignment 1</th>
                        <th>Assignment 2</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($students)): ?>
                        <tr>
                            <td colspan="11" class="no-data">No students found.</td>
                        </tr>
                    <?php else: ?>
                        <?php $count = $offset + 1; ?>
                        <?php foreach ($students as $student): ?>
                            <?php
                            $presentCount = (int) $student['present_count'];
                            $attendancePercentage = ($totalClasses > 0) ? round(($presentCount / $totalClasses) * 100, 2) : 0;
                            ?>
                            <tr>
                                <td><?= $count++ ?></td>
                                <td><?= htmlspecialchars($student['student_id'] ?? '') ?></td>
                                <td><?= htmlspecialchars($student['section'] ?? '') ?></td>
                                <td><?= htmlspecialchars($student['name'] ?? '') ?></td>
                                <td><?= $presentCount ?></td>
                                <td><?= $attendancePercentage ?>%</td>
                                <td><?= htmlspecialchars($student['class_test_1'] ?? '0') ?></td>
                                <td><?= htmlspecialchars($student['class_test_2'] ?? '0') ?></td>
                                <td><?= htmlspecialchars($student['class_test_3'] ?? '0') ?></td>
                                <td><?= htmlspecialchars($student['assignment_1'] ?? '0') ?></td>
                                <td><?= htmlspecialchars($student['assignment_2'] ?? '0') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="pagination">
            <a href="?page=1" class="<?= $page === 1 ? 'disabled' : '' ?>">First</a>
            <a href="?page=<?= max(1, $page - 1) ?>" class="<?= $page === 1 ? 'disabled' : '' ?>">Previous</a>
            <a href="?page=<?= min($totalPages, $page + 1) ?>" class="<?= $page === $totalPages ? 'disabled' : '' ?>">Next</a>
            <a href="?page=<?= $totalPages ?>" class="<?= $page === $totalPages ? 'disabled' : '' ?>">Last</a>
        </div>
    </div>
</div>
</body>
</html>
<style>
    .table-container {
    width: 100%;
    overflow-x: auto;
}

.table-wrapper {
    overflow-x: auto;
}

.students-table {
    width: 100%;
    min-width: 900px;
    border-collapse: collapse;
}

.students-table th, .students-table td {
    padding: 10px;
    border-bottom: 1px solid #ddd;
    white-space: nowrap;
}

</style>
