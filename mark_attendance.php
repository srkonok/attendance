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
$errorMessage = "";
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['student_ids']) && isset($_POST['selected_date'])) {
    $date = $_POST['selected_date']; // Use selected date from the form
    $ip_address = $_SERVER['REMOTE_ADDR'];

    try {
        foreach ($_POST['student_ids'] as $student_id) {
            $stmt = $conn->prepare("INSERT INTO attendance (student_id, ip_address, date) VALUES (?, ?, ?)");
            $stmt->execute([$student_id, $ip_address, $date]);
        }
        $successMessage = "Attendance marked successfully for selected students on $date.";
        
        // Refresh the page to update the student list
        header("Location: mark_attendance.php?date=$date&success=1");
        exit();
    } catch (Exception $e) {
        $errorMessage = "Error marking attendance: " . $e->getMessage();
    }
}

// Check for success message from redirect
if (isset($_GET['success']) && $_GET['success'] == 1) {
    $successMessage = "Attendance marked successfully!";
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mark Attendance - Cloud Computing Attendance System</title>
    <link rel="stylesheet" href="index.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/sweetalert2/11.7.3/sweetalert2.all.min.js"></script>
</head>
<body>
    <!-- Background Particles -->
    <div class="bg-particles">
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
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
          <a href="profile.php" role="menuitem"><i class="fas fa-user"></i> My Profile</a>
          <a href="student-list.php" role="menuitem"><i class="fas fa-users"></i> All Student List</a>
          <a href="student_attendance.php" role="menuitem"><i class="fas fa-chart-bar"></i> Attendance Report</a>
          <?php if (isset($_SESSION["user"]) && $_SESSION["user"] === "admin"): ?>
            <a href="mark_attendance.php" role="menuitem"><i class="fas fa-edit"></i> Manual Attendance</a>
          <?php else: ?>
            <a href="#" onclick="showAccessDenied(); return false;" role="menuitem"><i class="fas fa-edit"></i> Manual Attendance<span style="color: #f87171;">❗</span></a>
          <?php endif; ?>
          <?php if (isset($_SESSION["user"]) && $_SESSION["user"] === "admin"): ?>
            <a href="marks_entry.php" role="menuitem"><i class="fas fa-pencil-alt"></i> Enter Marks</a>
          <?php else: ?>
            <a href="#" onclick="showAccessDenied(); return false;" role="menuitem"><i class="fas fa-pencil-alt"></i> Enter Marks<span style="color: #f87171;">❗</span></a>
          <?php endif; ?>
          <?php if (isset($_SESSION["user"]) && $_SESSION["user"] === "admin"): ?>
            <a href="export_all_students_marks.php" role="menuitem"><i class="fas fa-download"></i> All Students Marks</a>
          <?php else: ?>
            <a href="#" onclick="showAccessDenied(); return false;" role="menuitem"><i class="fas fa-download"></i> All Students Marks<span style="color: #f87171;">❗</span></a>
          <?php endif; ?>
          <a href="logout.php" style="color: #f87171;" role="menuitem"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
      </div>
      
    </div>
  </div>
    <!-- Main Container -->
    <div class="container">
        <!-- Success/Error Messages -->
        <?php if ($successMessage): ?>
            <div class="message success" >
                <i class="fas fa-check-circle"></i>
                <?= htmlspecialchars($successMessage) ?>
            </div>
        <?php endif; ?>

        <?php if ($errorMessage): ?>
            <div class="message error">
                <i class="fas fa-exclamation-circle"></i>
                <?= htmlspecialchars($errorMessage) ?>
            </div>
        <?php endif; ?>

        <!-- Search and Date Selection -->
        <div class="card">
            <h2><i class="fas fa-search"></i> Search Students</h2>
            <form method="GET" action="" class="search-form">
                <div class="search-container">
                    <div class="form-group">
                        <input 
                            type="text" 
                            name="search" 
                            value="<?= htmlspecialchars($searchQuery) ?>" 
                            placeholder="Search by Name, ID, or Section (A/B/C)"
                            id="search_student"
                        >
                    </div>
                    <div class="form-group">
                        <input 
                            type="date" 
                            name="date" 
                            value="<?= htmlspecialchars($dateQuery) ?>" 
                            max="<?= date('Y-m-d') ?>"
                            id="search_date"
                        >
                    </div>
                    <button type="submit" class="btn">
                        <i class="fas fa-search"></i> Search
                    </button>
                </div>
            </form>
        </div>

        <!-- Mark Attendance Section -->
        <div class="card">
            <h2><i class="fas fa-user-check"></i> Students to Mark Attendance (<?= htmlspecialchars($dateQuery) ?>)</h2>
            
            <form method="POST" id="attendanceForm">
                <input type="hidden" name="selected_date" value="<?= htmlspecialchars($dateQuery) ?>">
                
                <div class="table-container">
                    <table class="attendance-table">
                        <thead>
                            <tr>
                                <th>S.No</th>
                                <th>Student ID</th>
                                <th>Student Name</th>
                                <th>
                                    <a href="?sort=section&order=<?= $newSortOrder ?>&search=<?= htmlspecialchars($searchQuery) ?>&date=<?= htmlspecialchars($dateQuery) ?>" 
                                       style="color: inherit; text-decoration: none;">
                                        Section <i class="fas fa-sort"></i>
                                    </a>
                                </th>
                                <th>
                                    <input type="checkbox" id="selectAll" style="transform: scale(1.2);"> Select All
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($students)): ?>
                                <tr>
                                    <td colspan="5" class="empty-state">
                                        <i class="fas fa-check-circle"></i>
                                        <p>All students have marked attendance for this date.</p>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php $serial = 1; ?>
                                <?php foreach ($students as $student): ?>
                                    <tr>
                                        <td><?= $serial++ ?></td>
                                        <td><?= htmlspecialchars($student['student_id']) ?></td>
                                        <td><?= htmlspecialchars($student['name']) ?></td>
                                        <td><?= htmlspecialchars($student['section']) ?></td>
                                        <td>
                                            <input type="checkbox" 
                                                   name="student_ids[]" 
                                                   value="<?= $student['student_id'] ?>"
                                                   class="student-checkbox"
                                                   style="transform: scale(1.2);">
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>

                    <!-- Mobile Cards -->
                    <div class="mobile-attendance-cards">
                        <?php if (empty($students)): ?>
                            <div class="empty-state">
                                <i class="fas fa-check-circle"></i>
                                <p>All students have marked attendance for this date.</p>
                            </div>
                        <?php else: ?>
                            <?php $serial = 1; ?>
                            <?php foreach ($students as $student): ?>
                                <div class="attendance-card">
                                    <div class="attendance-card-row">
                                        <span class="attendance-card-label">
                                            <i class="fas fa-hashtag"></i> Serial No
                                        </span>
                                        <span class="attendance-card-value"><?= $serial++ ?></span>
                                    </div>
                                    <div class="attendance-card-row">
                                        <span class="attendance-card-label">
                                            <i class="fas fa-id-card"></i> Student ID
                                        </span>
                                        <span class="attendance-card-value"><?= htmlspecialchars($student['student_id']) ?></span>
                                    </div>
                                    <div class="attendance-card-row">
                                        <span class="attendance-card-label">
                                            <i class="fas fa-user"></i> Student Name
                                        </span>
                                        <span class="attendance-card-value"><?= htmlspecialchars($student['name']) ?></span>
                                    </div>
                                    <div class="attendance-card-row">
                                        <span class="attendance-card-label">
                                            <i class="fas fa-graduation-cap"></i> Section
                                        </span>
                                        <span class="attendance-card-value"><?= htmlspecialchars($student['section']) ?></span>
                                    </div>
                                    <div class="attendance-card-row">
                                        <span class="attendance-card-label">
                                            <i class="fas fa-check-square"></i> Select
                                        </span>
                                        <span class="attendance-card-value">
                                            <input type="checkbox" 
                                                   name="student_ids[]" 
                                                   value="<?= $student['student_id'] ?>"
                                                   class="student-checkbox"
                                                   style="transform: scale(1.5);">
                                        </span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if (!empty($students)): ?>
                    <div style="margin-top: 20px; text-align: center;">
                        <button type="submit" class="btn" style="font-size: 1.1rem; padding: 15px 30px;">
                            <i class="fas fa-check"></i> Submit Attendance
                        </button>
                    </div>
                <?php endif; ?>
            </form>
        </div>

        <!-- Today's Attendance Section -->
        <div class="card">
            <h2><i class="fas fa-list-check"></i> Students Present on <?= htmlspecialchars($dateQuery) ?></h2>
            
            <div class="table-container">
                <table class="attendance-table">
                    <thead>
                        <tr>
                            <th>S.No</th>
                            <th>Student ID</th>
                            <th>Student Name</th>
                            <th>
                                <a href="?sort=section&order=<?= $newSortOrder ?>&search=<?= htmlspecialchars($searchQuery) ?>&date=<?= htmlspecialchars($dateQuery) ?>" 
                                   style="color: inherit; text-decoration: none;">
                                    Section <i class="fas fa-sort"></i>
                                </a>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($todayAttendance)): ?>
                            <tr>
                                <td colspan="4" class="empty-state">
                                    <i class="fas fa-info-circle"></i>
                                    <p>No attendance recorded for this date.</p>
                                </td>
                            </tr>
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

                <!-- Mobile Cards for Present Students -->
                <div class="mobile-attendance-cards">
                    <?php if (empty($todayAttendance)): ?>
                        <div class="empty-state">
                            <i class="fas fa-info-circle"></i>
                            <p>No attendance recorded for this date.</p>
                        </div>
                    <?php else: ?>
                        <?php $serial = 1; ?>
                        <?php foreach ($todayAttendance as $student): ?>
                            <div class="attendance-card">
                                <div class="attendance-card-row">
                                    <span class="attendance-card-label">
                                        <i class="fas fa-hashtag"></i> Serial No
                                    </span>
                                    <span class="attendance-card-value"><?= $serial++ ?></span>
                                </div>
                                <div class="attendance-card-row">
                                    <span class="attendance-card-label">
                                        <i class="fas fa-id-card"></i> Student ID
                                    </span>
                                    <span class="attendance-card-value"><?= htmlspecialchars($student['student_id']) ?></span>
                                </div>
                                <div class="attendance-card-row">
                                    <span class="attendance-card-label">
                                        <i class="fas fa-user"></i> Student Name
                                    </span>
                                    <span class="attendance-card-value"><?= htmlspecialchars($student['name']) ?></span>
                                </div>
                                <div class="attendance-card-row">
                                    <span class="attendance-card-label">
                                        <i class="fas fa-graduation-cap"></i> Section
                                    </span>
                                    <span class="attendance-card-value"><?= htmlspecialchars($student['section']) ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Send Attendance Emails Section -->
        <?php if (!empty($todayAttendance)): ?>
            <div class="card" style="text-align: center;">
                <h2><i class="fas fa-envelope"></i> Email Notifications</h2>
                <p>Send attendance summary email to administrators and absent students.</p>
                <a href="mail_send.php?date=<?= htmlspecialchars($dateQuery) ?>" class="btn" style="background: linear-gradient(135deg, #f59e0b, #d97706); margin-top: 15px;">
                    <i class="fas fa-paper-plane"></i> Send Attendance Emails
                </a>
            </div>
        <?php endif; ?>
    </div>

    <!-- JavaScript -->
    <script src="script.js"></script>
    <script>
        // Select All functionality
        document.addEventListener('DOMContentLoaded', function() {
            const selectAllCheckbox = document.getElementById('selectAll');
            const studentCheckboxes = document.querySelectorAll('.student-checkbox');
            
            if (selectAllCheckbox && studentCheckboxes.length > 0) {
                selectAllCheckbox.addEventListener('change', function() {
                    studentCheckboxes.forEach(checkbox => {
                        checkbox.checked = this.checked;
                    });
                });

                // Update select all checkbox when individual checkboxes change
                studentCheckboxes.forEach(checkbox => {
                    checkbox.addEventListener('change', function() {
                        const allChecked = Array.from(studentCheckboxes).every(cb => cb.checked);
                        const someChecked = Array.from(studentCheckboxes).some(cb => cb.checked);
                        
                        selectAllCheckbox.checked = allChecked;
                        selectAllCheckbox.indeterminate = someChecked && !allChecked;
                    });
                });
            }

            // Form validation
            const attendanceForm = document.getElementById('attendanceForm');
            if (attendanceForm) {
                attendanceForm.addEventListener('submit', function(e) {
                    const checkedBoxes = document.querySelectorAll('.student-checkbox:checked');
                    
                    if (checkedBoxes.length === 0) {
                        e.preventDefault();
                        
                        if (typeof Swal !== 'undefined') {
                            Swal.fire({
                                icon: 'warning',
                                title: 'No Students Selected',
                                text: 'Please select at least one student to mark attendance.',
                                confirmButtonColor: '#22c55e'
                            });
                        } else {
                            alert('Please select at least one student to mark attendance.');
                        }
                    }
                });
            }
        });
    </script>
</body>
</html>