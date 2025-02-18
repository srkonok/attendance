<?php
session_start();

if (!isset($_SESSION["user"])) {
    header("Location: login.php");
    exit();
}

include 'db.php'; 
// Do NOT include dashboard.php here; we will include it later in the layout.

// --- Attendance submission & search logic ---
$message = "";
$alertClass = "";
$formVisible = true;
$searchStudentId = isset($_GET['search_student_id']) ? $_GET['search_student_id'] : "";
$searchDate = isset($_GET['search_date']) ? $_GET['search_date'] : "";
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['submit_attendance'])) {
        $studentId = $_POST['student_id'];
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
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

if (!$searchStudentId && empty($searchDate)) {
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
$params = [];
if (!empty($searchStudentId)) {
    $query .= " AND a.student_id = :student_id";
    $params['student_id'] = $searchStudentId;
}
if (!empty($searchDate)) {
    $query .= " AND a.date = :date";
    $params['date'] = $searchDate;
}
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
  
  <div class="menu-container" style="position: absolute; right: 20px; top: 20px;">
    <button class="menu-button">☰</button>
    <div class="dropdown-menu">
      <a href="/attendance/profile.php">My Profile</a>
      <a href="/attendance/student-list.php">All Student List</a>
      <a href="/attendance/student_attendance.php">Attendance Report</a>
      <?php if (isset($_SESSION["user"]) && $_SESSION["user"] === "admin"): ?>
        <a href="/attendance/mark_attendance.php">Manual Attendance</a>
      <?php else: ?>
        <a href="#" onclick="showAccessDenied(); return false;">Manual Attendance❗</a>
      <?php endif; ?>
      <?php if (isset($_SESSION["user"]) && $_SESSION["user"] === "admin"): ?>
        <a href="/attendance/marks_entry.php">Enter Marks</a>
      <?php else: ?>
        <a href="#" onclick="showAccessDenied(); return false;">Enter Marks❗</a>
      <?php endif; ?>
      <?php if (isset($_SESSION["user"]) && $_SESSION["user"] === "admin"): ?>
        <a href="/attendance/all_students_marks.php?search=B">All Students Marks</a>
      <?php else: ?>
        <a href="#" onclick="showAccessDenied(); return false;">All Students Marks❗</a>
      <?php endif; ?>
      <a href="/attendance/logout.php?search=B" style="color: red;">Logout</a>
    </div>
  </div>
</div>

  <!-- <div class="container">
    <h2>Submit Attendance</h2>
    <?php if (!empty($message)): ?>
      <div class="message <?=$alertClass;?>">
        <?=$message;?>
      </div>
    <?php endif; ?>
    <?php if ($formVisible): ?>
      <form method="post" action="">
        <div class="form-group">
          <input type="text" id="student_id" name="student_id" value="<?= htmlspecialchars($_SESSION['student_id'] ?? ''); ?>" required readonly>
        </div>
        <button type="submit" name="submit_attendance" class="submit-btn">Submit Attendance</button>
      </form>
    <?php endif; ?>
  </div> -->

  <!-- Include the dashboard below the Submit Attendance section -->
  <div style='margin-top:100px'>
    <?php include 'dashboard.php'; ?>
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
              <td><?= $count++; ?></td>
              <td><?= htmlspecialchars($attendee['student_id']); ?></td>
              <td><?= htmlspecialchars($attendee['name']); ?></td>
              <td><?= htmlspecialchars($attendee['date']); ?></td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
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
    <!-- <div class="button-container">
      <a href="/attendance/student-list.php" class="button" style="margin-right: 10px;">All Student List</a>
      <a href="/attendance/student_attendance.php" class="button" style="margin-right: 10px;">Attendance Report</a>
      <?php if (isset($_SESSION["user"]) && $_SESSION["user"] === "admin"): ?>
        <a href="/attendance/mark_attendance.php" class="button">Manual Attendance</a>
      <?php else: ?>
        <a href="#" class="button" onclick="showAccessDenied(); return false;">Manual Attendance</a>
      <?php endif; ?>       
    </div> -->
  </div>

  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script>
    function showAccessDenied() {
      Swal.fire({
        icon: 'error',
        title: 'Access Denied',
        text: 'Admins Only!!',
        confirmButtonColor: '#d33',
        confirmButtonText: 'OK'
      });
    }
  </script>

<style>
  .menu-container {
    position: relative;
    display: inline-block;
  }

  .menu-button {
    background: none;
    border: none;
    font-size: 24px;
    cursor: pointer;
  }

  .dropdown-menu {
    display: none;
    position: absolute;
    right: 0;
    background-color: white;
    min-width: 200px;
    box-shadow: 0px 8px 16px rgba(0, 0, 0, 0.2);
    z-index: 1000;
  }

  .dropdown-menu a {
    color: black;
    padding: 12px 16px;
    text-decoration: none;
    display: block;
  }

  .dropdown-menu a:hover {
    background-color: #f1f1f1;
  }

  .menu-container:hover .dropdown-menu {
    display: block;
  }
</style>



</body>
</html>
