<?php
session_start();
include 'db.php';

// Check if the user is authenticated
if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
    header("Location: manual_attendance.php");
    exit();
}

// Get search and sorting query
$searchQuery = isset($_GET['search']) ? trim($_GET['search']) : '';
$sortColumn = isset($_GET['sort']) && in_array($_GET['sort'], ['section']) ? $_GET['sort'] : 'student_id';
$sortOrder = isset($_GET['order']) && in_array($_GET['order'], ['asc', 'desc']) ? $_GET['order'] : 'asc';
$newSortOrder = ($sortOrder === 'asc') ? 'desc' : 'asc';

// Get today's date
$today = date("Y-m-d");

// Fetch students who have **not** marked attendance today
$condition = "s.name LIKE :search OR s.student_id LIKE :search"; // Default condition

// Modify the condition if the search query is 'A' or 'B'
if ($searchQuery == 'A' || $searchQuery == 'B') {
    $condition = "s.section LIKE :search";
}

// Build the query
$query = "SELECT s.student_id, s.name, s.section 
          FROM students s
          LEFT JOIN attendance a ON s.student_id = a.student_id AND a.date = :today
          WHERE ($condition)
          AND a.student_id IS NULL
          ORDER BY $sortColumn $sortOrder";

$stmt = $conn->prepare($query);
$stmt->bindValue(':search', '%' . $searchQuery . '%', PDO::PARAM_STR);
$stmt->bindValue(':today', $today, PDO::PARAM_STR);
$stmt->execute();
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle attendance submission
$successMessage = "";
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['student_ids'])) {
    $date = date("Y-m-d");
    $ip_address = $_SERVER['REMOTE_ADDR'];

    foreach ($_POST['student_ids'] as $student_id) {
        $stmt = $conn->prepare("INSERT INTO attendance (student_id, ip_address, date) VALUES (?, ?, ?)");
        $stmt->execute([$student_id, $ip_address, $date]);
    }

    $successMessage = "<p style='color:green;'>Attendance marked successfully for selected students.</p>";

    // Refresh the page to update the student list
    header("Location: mark_attendance.php");
    exit();
}

// Fetch today's attendance with sorting
$attendanceQuery = "SELECT s.student_id, s.name, s.section FROM attendance a
                    JOIN students s ON a.student_id = s.student_id
                    WHERE a.date = :today
                    ORDER BY $sortColumn $sortOrder";
$attendanceStmt = $conn->prepare($attendanceQuery);
$attendanceStmt->bindValue(':today', $today, PDO::PARAM_STR);
$attendanceStmt->execute();
$todayAttendance = $attendanceStmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Mark Attendance</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="students-container">
        <!-- Right: Mark Attendance -->
        <div class="students-list">
            <h1>Mark Attendance</h1>

            <?= $successMessage ?>

            <!-- Search Bar -->
            <div class="search-form">
                <form method="GET" action="">
                    <input 
                        type="text" 
                        name="search" 
                        value="<?= htmlspecialchars($searchQuery) ?>" 
                        placeholder="Search by Name, ID, or Section"
                    >
                    <button type="submit">Search</button>
                </form>
            </div>

            <!-- Attendance Form -->
            <form method="POST">
                <table class="students-table" border="1">
                    <thead>
                        <tr>
                            <th>S.No</th>
                            <th>Student ID</th>
                            <th>Name</th>
                            <th>
                                <a href="?sort=section&order=<?= $newSortOrder ?>&search=<?= htmlspecialchars($searchQuery) ?>" style="color: white; text-decoration: none;">Section</a>
                            </th>
                            <th>Select</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($students)): ?>
                            <tr><td colspan="5">All students have marked attendance.</td></tr>
                        <?php else: ?>
                            <?php $serial = 1; ?>
                            <?php foreach ($students as $student): ?>
                                <tr>
                                    <td><?= $serial++ ?></td>
                                    <td><?= htmlspecialchars($student['student_id']) ?></td>
                                    <td><?= htmlspecialchars($student['name']) ?></td>
                                    <td><?= htmlspecialchars($student['section']) ?></td>
                                    <td><input type="checkbox" name="student_ids[]" value="<?= $student['student_id'] ?>"></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
                <button type="submit">Submit Attendance</button>
            </form>
        </div>

        <!-- Left: Today's Attendance -->
        <div class="attendance-list">
            <h2>Today's Attendance (<?= $today ?>)</h2>
            <table class="students-table" border="1">
                <thead>
                    <tr>
                        <th>S.No</th>
                        <th>Student ID</th>
                        <th>Name</th>
                        <th>
                            <a href="?sort=section&order=<?= $newSortOrder ?>&search=<?= htmlspecialchars($searchQuery) ?>"style="color: white; text-decoration: none;">Section</a>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($todayAttendance)): ?>
                        <tr><td colspan="4">No attendance recorded today.</td></tr>
                    <?php else: ?>
                        <?php $serial = 1; ?>
                        <?php foreach ($todayAttendance as $student): ?>
                            <tr>
                                <td><?= $serial++ ?></td>
                                <td><?= htmlspecialchars($student['student_id']) ?></td>
                                <td><?= htmlspecialchars($student['name']) ?></td>
                                <td><?= htmlspecialchars($student['section']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
