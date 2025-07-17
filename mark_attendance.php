<?php
session_start();
include 'db.php';

// Check if the user is authenticated
if (!isset($_SESSION['user']) || $_SESSION['user'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Get search, date, and sorting query
$searchQuery = isset($_GET['search']) ? trim($_GET['search']) : '';
$dateQuery = isset($_GET['date']) ? $_GET['date'] : date("Y-m-d"); // Default to today if not provided
$sortColumn = isset($_GET['sort']) && in_array($_GET['sort'], ['section']) ? $_GET['sort'] : 'student_id';
$sortOrder = isset($_GET['order']) && in_array($_GET['order'], ['asc', 'desc']) ? $_GET['order'] : 'asc';
$newSortOrder = ($sortOrder === 'asc') ? 'desc' : 'asc';

// Fetch students who have **not** marked attendance on the selected date
$condition = "s.name LIKE :search OR s.student_id LIKE :search"; // Default condition

// Modify the condition if the search query is 'A' or 'B' (for section-based filtering)
if ($searchQuery == 'A' || $searchQuery == 'B' || $searchQuery == 'C') {
    $condition = "s.section LIKE :search";
}

// Query to get students who haven't marked attendance yet
$query = "SELECT s.student_id, s.name, s.section 
          FROM students s
          LEFT JOIN attendance a ON s.student_id = a.student_id AND a.date = :date
          WHERE ($condition)
          AND a.student_id IS NULL
          ORDER BY $sortColumn $sortOrder";

$stmt = $conn->prepare($query);
$stmt->bindValue(':search', '%' . $searchQuery . '%', PDO::PARAM_STR);
$stmt->bindValue(':date', $dateQuery, PDO::PARAM_STR);
$stmt->execute();
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle attendance submission
$successMessage = "";
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['student_ids']) && isset($_POST['selected_date'])) {
    $date = $_POST['selected_date']; // Use selected date from the form
    $ip_address = $_SERVER['REMOTE_ADDR'];

    foreach ($_POST['student_ids'] as $student_id) {
        $stmt = $conn->prepare("INSERT INTO attendance (student_id, ip_address, date) VALUES (?, ?, ?)");
        $stmt->execute([$student_id, $ip_address, $date]);
    }

    $successMessage = "<p style='color:green;'>Attendance marked successfully for selected students on $date.</p>";

    // Refresh the page to update the student list
    header("Location: mark_attendance.php?date=$date");
    exit();
}

// Fetch attendance for the selected date
$attendanceQuery = "SELECT s.student_id, s.name, s.section FROM attendance a
                    JOIN students s ON a.student_id = s.student_id
                    WHERE a.date = :date
                    ORDER BY $sortColumn $sortOrder";
$attendanceStmt = $conn->prepare($attendanceQuery);
$attendanceStmt->bindValue(':date', $dateQuery, PDO::PARAM_STR);
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
                    <!-- Date Picker -->
                    <input 
                        type="date" 
                        name="date" 
                        value="<?= htmlspecialchars($dateQuery) ?>" 
                        max="<?= date('Y-m-d') ?>" 
                    >
                    <button type="submit">Search</button>
                </form>
            </div>

            <!-- Attendance Form -->
            <form method="POST">
                <!-- Ensure the selected date is carried over -->
                <input type="hidden" name="selected_date" value="<?= htmlspecialchars($dateQuery) ?>">

                <table class="students-table" border="1">
                    <thead>
                        <tr>
                            <th>S.No</th>
                            <th>Student ID</th>
                            <th>Name</th>
                            <th>
                                <a href="?sort=section&order=<?= $newSortOrder ?>&search=<?= htmlspecialchars($searchQuery) ?>&date=<?= htmlspecialchars($dateQuery) ?>" style="color: white; text-decoration: none;">Section</a>
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
        <div class="students-list">
            <h2>Attendance for Date: <?= htmlspecialchars($dateQuery) ?></h2>

            <table class="students-table" border="1">
                <thead>
                    <tr>
                        <th>S.No</th>
                        <th>Student ID</th>
                        <th>Name</th>
                        <th>
                            <a href="?sort=section&order=<?= $newSortOrder ?>&search=<?= htmlspecialchars($searchQuery) ?>&date=<?= htmlspecialchars($dateQuery) ?>" style="color: white; text-decoration: none;">Section</a>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($todayAttendance)): ?>
                        <tr><td colspan="4">No attendance recorded for this date.</td></tr>
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

        <!-- Send Attendance Emails Button -->
        <div style="margin-bottom: 15px;">
            <a href="mail_send.php?date=<?= htmlspecialchars($dateQuery) ?>">
                <button type="button" style="background-color:rgb(178, 67, 12); color: white; padding: 10px 15px; border: none; cursor: pointer; border-radius: 5px;">
                    Send Attendance Emails
                </button>
            </a>
        </div>
    </div>
</body>
</html>
