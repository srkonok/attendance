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
  <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
  <title>Cloud Computing Attendance</title>
  <link rel="icon" href="images/favicon_io/favicon.ico" type="image/x-icon">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
  <link rel="stylesheet" href="index.css">
</head>
<body>
  <!-- Animated Background Particles -->
  <div class="bg-particles">
    <div class="particle" style="left: 10%; animation-delay: 0s;"></div>
    <div class="particle" style="left: 20%; animation-delay: 2s;"></div>
    <div class="particle" style="left: 30%; animation-delay: 4s;"></div>
    <div class="particle" style="left: 40%; animation-delay: 6s;"></div>
    <div class="particle" style="left: 50%; animation-delay: 8s;"></div>
    <div class="particle" style="left: 60%; animation-delay: 10s;"></div>
    <div class="particle" style="left: 70%; animation-delay: 12s;"></div>
    <div class="particle" style="left: 80%; animation-delay: 14s;"></div>
    <div class="particle" style="left: 90%; animation-delay: 16s;"></div>
  </div>

  <!-- Menu Overlay -->
  <div class="menu-overlay" id="menuOverlay"></div>

  <div class="header-container">
    <div class="header-content">
      <div>
        <h1><i class="fas fa-cloud"></i> CSE 4267: Cloud Computing</h1>
        <p><i class="fas fa-calendar"></i> <?php echo date('l, F j, Y'); ?></p>
      </div>
      <div class="menu-container" id="menuContainer">
        <button class="menu-button" id="menuButton" aria-label="Menu" aria-haspopup="true" aria-expanded="false">
          <i class="fas fa-bars"></i>
        </button>
        <div class="dropdown-menu" id="dropdownMenu" role="menu">
          <a href="/attendance/profile.php" role="menuitem"><i class="fas fa-user"></i> My Profile</a>
          <a href="/attendance/student-list.php" role="menuitem"><i class="fas fa-users"></i> All Student List</a>
          <a href="/attendance/student_attendance.php" role="menuitem"><i class="fas fa-chart-bar"></i> Attendance Report</a>
          <?php if (isset($_SESSION["user"]) && $_SESSION["user"] === "admin"): ?>
            <a href="/attendance/mark_attendance.php" role="menuitem"><i class="fas fa-edit"></i> Manual Attendance</a>
          <?php else: ?>
            <a href="#" onclick="showAccessDenied(); return false;" role="menuitem"><i class="fas fa-edit"></i> Manual Attendance<span style="color: #f87171;">❗</span></a>
          <?php endif; ?>
          <?php if (isset($_SESSION["user"]) && $_SESSION["user"] === "admin"): ?>
            <a href="/attendance/marks_entry.php" role="menuitem"><i class="fas fa-pencil-alt"></i> Enter Marks</a>
          <?php else: ?>
            <a href="#" onclick="showAccessDenied(); return false;" role="menuitem"><i class="fas fa-pencil-alt"></i> Enter Marks<span style="color: #f87171;">❗</span></a>
          <?php endif; ?>
          <?php if (isset($_SESSION["user"]) && $_SESSION["user"] === "admin"): ?>
            <a href="/attendance/export_all_students_marks.php" role="menuitem"><i class="fas fa-download"></i> All Students Marks</a>
          <?php else: ?>
            <a href="#" onclick="showAccessDenied(); return false;" role="menuitem"><i class="fas fa-download"></i> All Students Marks<span style="color: #f87171;">❗</span></a>
          <?php endif; ?>
          <a href="/attendance/logout.php" style="color: #f87171;" role="menuitem"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
      </div>
      
    </div>
  </div>

  <div class="container">
    <!-- Attendance Submission Form (if needed) -->
    <?php if (!empty($message)): ?>
      <div class="message <?=$alertClass;?>">
        <?=$message;?>
      </div>
    <?php endif; ?>

    <!-- <?php if ($formVisible && isset($_SESSION['student_id'])): ?>
      <div class="card">
        <h2><i class="fas fa-check-circle"></i> Submit Attendance</h2>
        <form method="post" action="">
          <div class="form-group">
            <input type="text" id="student_id" name="student_id" value="<?= htmlspecialchars($_SESSION['student_id'] ?? ''); ?>" required readonly style="background: rgba(255, 255, 255, 0.05); color: #4ade80;">
          </div>
          <button type="submit" name="submit_attendance" class="btn">
            <i class="fas fa-paper-plane"></i> Submit Attendance
          </button>
        </form>
      </div>
    <?php endif; ?> -->

    <!-- Include the dashboard below the Submit Attendance section -->
    <div style='margin-top: 40px'>
      <?php 
      if (file_exists('dashboard.php')) {
          include 'dashboard.php'; 
      }
      ?>
    </div>
  </div>

  <div class="attendance-list" >
    <h2><i class="fas fa-list-check "></i> Today's Attendance</h2>
    
    <form method="post" action="">
      <div class="search-container">
        <div class="form-group">
          <input type="text" id="search_student_id" name="search_student_id" value="<?= htmlspecialchars($searchStudentId); ?>" placeholder="Search by Student ID">
        </div>
        <div class="form-group">
          <input type="date" id="search_date" name="search_date" value="<?= htmlspecialchars($searchDate); ?>">
        </div>
        <button type="submit" name="search_attendance">
          <i class="fas fa-search"></i> Search
        </button>
      </div>
    </form>

    <div class="table-container">
      <table class="attendance-table" id="attendance_table">
        <thead>
          <tr>
            <th><i class="fas fa-hashtag"></i> Serial No</th>
            <th><i class="fas fa-id-card"></i> Student ID</th>
            <th><i class="fas fa-user"></i> Student Name</th>
            <th><i class="fas fa-calendar-day"></i> Date</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($attendees)): ?>
            <tr>
              <td colspan="4" style="text-align: center; color: rgba(255, 255, 255, 0.6); padding: 40px;">
                <i class="fas fa-info-circle" style="font-size: 2rem; margin-bottom: 10px; display: block;"></i>
                No attendance records found.
              </td>
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

      <!-- Mobile Cards Container (will be populated by JavaScript) -->
      <div class="mobile-attendance-cards"></div>
    </div>

    <?php if ($totalPages > 1): ?>
    <div class="pagination">
      <?php if ($page > 1): ?>
        <a href="?page=<?= ($page - 1); ?>&search_date=<?= urlencode($searchDate); ?>&search_student_id=<?= urlencode($searchStudentId); ?>" class="pagination-link">
          <i class="fas fa-chevron-left"></i> Previous
        </a>
      <?php endif; ?>
      
      <p class="page-number">Page <?= $page ?> of <?= $totalPages ?></p>
      
      <?php if ($page < $totalPages): ?>
        <a href="?page=<?= ($page + 1); ?>&search_date=<?= urlencode($searchDate); ?>&search_student_id=<?= urlencode($searchStudentId); ?>" class="pagination-link">
          Next <i class="fas fa-chevron-right"></i>
        </a>
      <?php endif; ?>
    </div>
    <?php endif; ?>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script src="index.js"></script>
</body>
</html>