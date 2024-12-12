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
$searchStudentId = ""; // Variable to hold searched student ID
$limit = 10; // Number of records per page

// Get current page number, default to 1 if not set
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
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
    }
}

// Fetch total attendance records for pagination
$totalQuery = "SELECT COUNT(*) FROM attendance WHERE date = :date";
$params = ['date' => date('Y-m-d')];

$totalStmt = $conn->prepare($totalQuery);
$totalStmt->execute($params);
$totalRecords = $totalStmt->fetchColumn();
$totalPages = ceil($totalRecords / $limit);

// Fetch today's attendance records with student names
$query = "
    SELECT a.student_id, a.date, s.name
    FROM attendance a
    JOIN students s ON a.student_id = s.student_id
    WHERE a.date = :date
    LIMIT " . (int)$limit . " OFFSET " . (int)$offset;
$params['date'] = date('Y-m-d'); // Make sure to bind the date parameter

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
    <link rel="stylesheet" href="style.css">
    <script>
        // JavaScript function to filter the attendance list based on student ID
        function filterAttendance() {
            var input, filter, table, tr, td, i, txtValue;
            input = document.getElementById("search_student_id");
            filter = input.value.toUpperCase();
            table = document.getElementById("attendance_table");
            tr = table.getElementsByTagName("tr");

            for (i = 0; i < tr.length; i++) {
                td = tr[i].getElementsByTagName("td")[1]; // Column 1 is Student ID
                if (td) {
                    txtValue = td.textContent || td.innerText;
                    if (txtValue.toUpperCase().indexOf(filter) > -1) {
                        tr[i].style.display = "";
                    } else {
                        tr[i].style.display = "none";
                    }
                }
            }
        }
    </script>
</head>
<body>

    <!-- Cloud Computing Header -->
    <div class="header-container">
        <h1>CSE 4267: Cloud Computing</h1>
        <p><?php echo date('l, F j, Y'); ?></p>
    </div>

    <!-- Attendance Form -->
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

    <!-- Today's Attendance List -->
    <div class="attendance-list">
        <h2>Today's Attendance</h2>

        <!-- Search Form -->
        <div class="search-container">
            <input type="text" id="search_student_id" placeholder="Search by Student ID">
            <button type="button" onclick="filterAttendance()">Search</button>
        </div>

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

        <!-- Pagination Links -->
        <div class="pagination">
            <div class="page-number">
                <p>Page <?= $page ?> of <?= $totalPages ?></p>
            </div>
            <!-- Previous Page Link -->
            <?php if ($page > 1): ?>
                <a href="?page=<?= ($page - 1); ?>" class="pagination-link">Previous</a>
            <?php endif; ?>

            <!-- Next Page Link -->
            <?php if ($page < $totalPages): ?>
                <a href="?page=<?= ($page + 1); ?>" class="pagination-link">Next</a>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
