<?php
// Include database connection
include 'db.php';

// Function to get client IP address
function getUserIP()
{
    return $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
}

$message = "";
$alertClass = "";
$formVisible = true;
$searchStudentId = isset($_GET['search_student_id']) ? $_GET['search_student_id'] : ""; // Variable to hold searched student ID
$searchDate =isset($_GET['search_date']) ? $_GET['search_date'] :  ""; // Variable to hold searched date
$limit = 10; // Number of records per page

// Get current page number, default to 1 if not set
$page = isset($_GET['page']) ? $_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Handle form submission for attendance
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['submit_attendance'])) {
        $studentId = $_POST['student_id'];
        $ipAddress = getUserIP();
        $date = date('Y-m-d');

        try {
            // Check if the IP address has already submitted attendance today
            $query = "SELECT * FROM attendance WHERE ip_address = :ip AND date = :date";
            $stmt = $conn->prepare($query);
            $stmt->execute(['ip' => $ipAddress, 'date' => $date]);
            $existingRecord = $stmt->fetch();

            if ($existingRecord) {
                $message = "Attendance already submitted.";
                $alertClass = "error";
                $formVisible = false;
            } else {
                // Insert attendance
                $insertQuery = "INSERT INTO attendance (student_id, ip_address, date) VALUES (:student_id, :ip_address, :date)";
                $stmt = $conn->prepare($insertQuery);
                $stmt->execute([
                    'student_id' => $studentId,
                    'ip_address' => $ipAddress,
                    'date' => $date,
                ]);
                $message = "Attendance submitted successfully.";
                $alertClass = "success";
                $formVisible = false;
            }
        } catch (PDOException $e) {
            $message = "Error: " . $e->getMessage();
            $alertClass = "error";
        }
    } elseif (isset($_POST['search_attendance'])) {
        $searchStudentId = $_POST['search_student_id'];
        $searchDate = $_POST['search_date'];
    }
}

// Default to today's date if no search date is provided
if (!$searchStudentId and empty($searchDate) ) {
    $searchDate = date('Y-m-d');
}

// Fetch total attendance records for pagination
$totalQuery = "SELECT COUNT(*) FROM attendance WHERE 1=1";
$params = [];

if (!empty($searchStudentId)) {
    $totalQuery .= " AND student_id = :student_id";
    $params['student_id'] = $searchStudentId;
}

if (!empty($searchDate)) {
    $totalQuery .= " AND date = :date";
    $params['date'] = $searchDate;
}

$totalStmt = $conn->prepare($totalQuery);
$totalStmt->execute($params);
$totalRecords = $totalStmt->fetchColumn();
$totalPages = ceil($totalRecords / $limit);

// Fetch filtered attendance records
$query = "
    SELECT a.student_id, a.date, s.name
    FROM attendance a
    JOIN students s ON a.student_id = s.student_id
    WHERE 1=1
";

// Add search conditions dynamically
$params = [];
if (!empty($searchStudentId)) {
    $query .= " AND a.student_id = :student_id";
    $params['student_id'] = $searchStudentId;
}
if (!empty($searchDate)) {
    $query .= " AND a.date = :date";
    $params['date'] = $searchDate;
}

// Add ordering and pagination
$query .= " ORDER BY a.date DESC LIMIT " . (int)$limit . " OFFSET " . (int)$offset;

$stmt = $conn->prepare($query);
$stmt->execute($params);
$attendees = $stmt->fetchAll();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cloud Computing Attendance</title>
    <link rel="icon" href="images/favicon_io/favicon.ico" type="image/x-icon">
    <link rel="stylesheet" href="style.css">
    <script src="attendance.js" defer></script>
</head>
<body>
    <div class="header-container">
        <div class="header-content">
            <h1>CSE 4267: Cloud Computing</h1>
            <p><?php echo date('l, F j, Y'); ?></p>
        </div>
        <!-- <button onclick="location.href='#studentListSection'" class="redirect-btn">View All Students</button> -->
    </div>

    <div class="container">
        <h2>Submit Attendance</h2>
        <?php if (!empty($message)): ?>
            <div class="message <?=$alertClass;?>">
                <?=$message;?>
            </div>
        <?php endif;?>
        <?php if ($formVisible): ?>
            <form method="post" action="">
                <div class="form-group">
                    <input type="text" id="student_id" name="student_id" required placeholder="Enter your student ID">
                </div>
                <button type="submit" name="submit_attendance" class="submit-btn">Submit Attendance</button>
            </form>
        <?php endif;?>
    </div>
    <div class="attendance-list">
        <h2>Today's Attendance</h2>
        <form method="post" action="">
            <div class="search-container">
                <input type="text" id="search_student_id" name="search_student_id" value="<?= htmlspecialchars($searchStudentId); ?>" placeholder="Search by Student ID">
                <input type="date" id="search_date" name="search_date" value="<?= htmlspecialchars($searchDate); ?>" placeholder="Search by Date">
                <button type="submit" name="search_attendance">Search</button>
            </div>
        </form>
        <table class="attendance-table" id="attendance_table">
            <thead>
                <tr>
                    <th>Serial No</th>
                    <th>Student ID</th>
                    <th>Student Name</th>
                    <th>Date</th>
                </tr>
            </thead>
            
            <tbody>
                <?php if (empty($attendees)): ?>
                    <tr>
                        <td colspan="4">No attendance records found.</td>
                    </tr>
                <?php else: ?>
                    <?php $count = $offset + 1; ?>
                    <?php foreach ($attendees as $attendee): ?>
                        <tr>
                            <td><?=$count++; ?></td>
                            <td><?=htmlspecialchars($attendee['student_id']);?></td>
                            <td><?=htmlspecialchars($attendee['name']);?></td>
                            <td><?=htmlspecialchars($attendee['date']);?></td>
                        </tr>
                    <?php endforeach;?>
                <?php endif;?>
            </tbody>
        </table>
        <div class="pagination">
            <p class="page-number">Page <?= $page ?> of <?= $totalPages ?></p>

            <?php if ($page > 1): ?>
                <a href="?page=<?= ($page - 1); ?>&search_date=<?= urlencode($searchDate); ?>&search_student_id=<?= urlencode($searchStudentId); ?>" class="pagination-link">Previous</a>
            <?php endif; ?>

            <?php if ($page < $totalPages): ?>
                <a href="?page=<?= ($page + 1); ?>&search_date=<?= urlencode($searchDate); ?>&search_student_id=<?= urlencode($searchStudentId); ?>" class="pagination-link">Next</a>
            <?php endif; ?>
        </div>

        <div class="button-container">
            <a href="/attendance/student-list.php" class="button">All Student List</a>
        </div>
        <div class="button-container">
            <a href="/attendance/manual_attendance.php" class="button">Manual Attendance</a>
        </div>

    </div>
</body>
</html>

