<?php
session_start();

if (!isset($_SESSION["user"])) {
    header("Location: login.php");
    exit();
}

include 'db.php';

// Pagination settings
$limit = 15; // Number of students per page
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Get the earliest attendance date
$startDateQuery = "SELECT MIN(date) FROM attendance";
$startDateStmt = $conn->prepare($startDateQuery);
$startDateStmt->execute();
$startDate = $startDateStmt->fetchColumn();
$startDateFormatted = $startDate ? date("d M Y", strtotime($startDate)) : "Unknown";

// Sorting settings
$sortColumn = isset($_GET['sort']) && in_array($_GET['sort'], ['section']) ? $_GET['sort'] : 'student_id';
$sortOrder = isset($_GET['order']) && in_array($_GET['order'], ['asc', 'desc']) ? $_GET['order'] : 'desc';
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

// Fetch students with attendance count
$studentsQuery = "
    SELECT s.*, 
        COALESCE(a.attendance_count, 0) AS present_count
    FROM students s
    LEFT JOIN (
        SELECT student_id, COUNT(*) AS attendance_count
        FROM attendance
        GROUP BY student_id
    ) a ON s.student_id = a.student_id
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <title>Cloud Computing Attendance - Student Reports</title>
    <link rel="icon" href="images/favicon_io/favicon.ico" type="image/x-icon">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="index.css">
    <style>
        /* Additional styles for student attendance page */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border: 2px solid rgba(5, 150, 105, 0.2);
            border-radius: 16px;
            padding: 25px;
            text-align: center;
            box-shadow: 0 15px 35px rgba(5, 150, 105, 0.1);
            transition: all 0.3s ease;
            animation: fadeInUp 0.6s ease-out;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 25px 50px rgba(5, 150, 105, 0.2);
            border-color: rgba(5, 150, 105, 0.4);
        }

        .stat-card .stat-icon {
            font-size: 2.5rem;
            color: #10b981;
            margin-bottom: 10px;
            display: block;
        }

        .stat-card .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: #059669;
            margin-bottom: 8px;
            display: block;
        }

        .stat-card .stat-label {
            color: #6b7280;
            font-size: 0.9rem;
            font-weight: 500;
        }

        .search-form {
            margin-bottom: 25px;
        }

        .search-form form {
            display: flex;
            gap: 15px;
            align-items: center;
            flex-wrap: wrap;
        }

        .search-form input {
            flex: 1;
            min-width: 250px;
            padding: 15px 20px;
            background: rgba(255, 255, 255, 0.9);
            border: 2px solid rgba(5, 150, 105, 0.2);
            border-radius: 12px;
            color: #374151;
            font-size: 16px;
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
        }

        .search-form input:focus {
            outline: none;
            border-color: #10b981;
            background: rgba(255, 255, 255, 0.95);
            box-shadow: 0 0 0 4px rgba(5, 150, 105, 0.1);
        }

        .search-form button {
            padding: 15px 25px;
            background: linear-gradient(135deg, #10b981, #059669);
            border: none;
            border-radius: 12px;
            color: white;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .search-form button:hover {
            background: linear-gradient(135deg, #059669, #047857);
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(5, 150, 105, 0.3);
        }

        .students-table {
            width: 100%;
            border-collapse: collapse;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 20px 40px rgba(5, 150, 105, 0.15);
            border: 2px solid rgba(5, 150, 105, 0.2);
        }

        .students-table th {
            background: rgba(5, 150, 105, 0.1);
            padding: 20px;
            text-align: left;
            font-weight: 600;
            color: #059669;
            border-bottom: 2px solid rgba(5, 150, 105, 0.2);
            position: relative;
        }

        .students-table th .sortable {
            color: #059669;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: space-between;
            transition: all 0.3s ease;
        }

        .students-table th .sortable:hover {
            color: #10b981;
        }

        .students-table th .arrow {
            margin-left: 8px;
            font-size: 0.8rem;
            opacity: 0.7;
        }

        .students-table td {
            padding: 20px;
            border-bottom: 1px solid rgba(5, 150, 105, 0.1);
            transition: all 0.3s ease;
            color: #374151;
        }

        .students-table tbody tr {
            transition: all 0.3s ease;
        }

        .students-table tbody tr:hover {
            background: rgba(5, 150, 105, 0.05);
            transform: scale(1.01);
        }

        .students-table .no-data {
            text-align: center;
            color: #6b7280;
            padding: 40px;
            font-style: italic;
        }

        /* Mobile responsive table */
        .mobile-students-cards {
            display: none;
        }

        .student-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(15px);
            border: 2px solid rgba(5, 150, 105, 0.2);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 15px;
            transition: all 0.3s ease;
            box-shadow: 0 10px 25px rgba(5, 150, 105, 0.1);
            animation: fadeInUp 0.6s ease-out;
        }

        .student-card:hover {
            transform: translateY(-3px);
            background: rgba(5, 150, 105, 0.05);
            box-shadow: 0 15px 35px rgba(5, 150, 105, 0.2);
        }

        .student-card-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
            padding-bottom: 8px;
            border-bottom: 1px solid rgba(5, 150, 105, 0.1);
        }

        .student-card-row:last-child {
            margin-bottom: 0;
            border-bottom: none;
        }

        .student-card-label {
            font-weight: 600;
            color: #059669;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .student-card-value {
            color: #374151;
            font-size: 0.95rem;
            text-align: right;
            font-weight: 500;
        }

        .export-container {
            display: flex;
            gap: 15px;
            align-items: center;
            margin-top: 30px;
            justify-content: center;
            flex-wrap: wrap;
        }

        .export-container select {
            padding: 12px 20px;
            background: rgba(255, 255, 255, 0.9);
            border: 2px solid rgba(5, 150, 105, 0.2);
            border-radius: 12px;
            color: #374151;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
            min-width: 150px;
        }

        .export-container select:focus {
            outline: none;
            border-color: #10b981;
            box-shadow: 0 0 0 4px rgba(5, 150, 105, 0.1);
        }

        .export-container button {
            padding: 12px 25px;
            background: linear-gradient(135deg, #f59e0b, #d97706);
            border: none;
            border-radius: 12px;
            color: white;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .export-container button:hover {
            background: linear-gradient(135deg, #d97706, #b45309);
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(245, 158, 11, 0.3);
        }

        .export-container button::before {
            content: '\f019';
            font-family: 'Font Awesome 6 Free';
            font-weight: 900;
        }

        /* Mobile responsiveness */
        @media (max-width: 767px) {
            .stats-grid {
                grid-template-columns: 1fr;
                gap: 15px;
                margin-bottom: 25px;
            }

            .stat-card {
                padding: 20px;
            }

            .stat-card .stat-icon {
                font-size: 2rem;
            }

            .stat-card .stat-number {
                font-size: 1.5rem;
            }

            .search-form form {
                flex-direction: column;
                gap: 12px;
            }

            .search-form input {
                min-width: 100%;
            }

            .search-form button {
                width: 100%;
                justify-content: center;
            }

            .students-table {
                display: none;
            }

            .mobile-students-cards {
                display: block;
            }

            .export-container {
                flex-direction: column;
                gap: 12px;
            }

            .export-container select,
            .export-container button {
                width: 100%;
            }
        }

        @media (min-width: 768px) and (max-width: 991px) {
            .students-table th,
            .students-table td {
                padding: 15px 12px;
                font-size: 14px;
            }
        }
    </style>
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
                <h1><i class="fas fa-chart-line"></i> Student Attendance Report</h1>
                <p><i class="fas fa-calendar"></i> Attendance from <?= htmlspecialchars($startDateFormatted) ?></p>
            </div>
            <div class="menu-container" id="menuContainer">
                <button class="menu-button" id="menuButton" aria-label="Menu" aria-haspopup="true" aria-expanded="false">
                    <i class="fas fa-bars"></i>
                </button>
                <div class="dropdown-menu" id="dropdownMenu" role="menu">
                    <a href="/attendance/" role="menuitem"><i class="fas fa-home"></i> Dashboard</a>
                    <a href="/attendance/profile.php" role="menuitem"><i class="fas fa-user"></i> My Profile</a>
                    <a href="/attendance/student-list.php" role="menuitem"><i class="fas fa-users"></i> All Student List</a>
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
        <!-- Statistics Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <i class="fas fa-users stat-icon"></i>
                <span class="stat-number"><?= $totalStudents ?></span>
                <span class="stat-label">Total Students</span>
            </div>
            <div class="stat-card">
                <i class="fas fa-calendar-check stat-icon"></i>
                <span class="stat-number"><?= $totalClasses ?></span>
                <span class="stat-label">Classes Conducted</span>
            </div>
            <div class="stat-card">
                <i class="fas fa-percentage stat-icon"></i>
                <span class="stat-number">
                    <?php 
                    $avgAttendance = 0;
                    if (!empty($students) && $totalClasses > 0) {
                        $totalPercentage = 0;
                        foreach ($students as $student) {
                            $presentCount = (int) $student['present_count'];
                            $attendancePercentage = ($presentCount / $totalClasses) * 100;
                            $totalPercentage += $attendancePercentage;
                        }
                        $avgAttendance = round($totalPercentage / count($students), 1);
                    }
                    echo $avgAttendance;
                    ?>%
                </span>
                <span class="stat-label">Average Attendance</span>
            </div>
        </div>

        <div class="card">
            <!-- Search Bar -->
            <div class="search-form">
                <form method="GET" action="">
                    <input 
                        type="text" 
                        name="search" 
                        value="<?= htmlspecialchars($searchQuery) ?>" 
                        placeholder="🔍 Search by name, student ID, or section..."
                    >
                    <button type="submit">
                        <i class="fas fa-search"></i> Search
                    </button>
                </form>
            </div>

            <!-- Desktop Table -->
            <div class="table-container">
                <table class="students-table" id="students_table">
                    <thead>
                        <tr>
                            <th><i class="fas fa-hashtag"></i> Serial No</th>
                            <th><i class="fas fa-id-card"></i> Student ID</th>
                            <th>
                                <a href="?sort=section&order=<?= $newSortOrder ?>&search=<?= htmlspecialchars($searchQuery) ?>&page=<?= $page ?>" class="sortable">
                                    <span class="section-title"><i class="fas fa-layer-group"></i> Section</span>
                                    <span class="arrow">
                                        <?= ($sortColumn === 'section' ? ($sortOrder === 'asc' ? '▲' : '▼') : '↕') ?>
                                    </span>
                                </a>
                            </th>
                            <th><i class="fas fa-user"></i> Name</th>
                            <th><i class="fas fa-check-circle"></i> Present Days</th>
                            <th><i class="fas fa-chart-pie"></i> Attendance (%)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($students)): ?>
                            <tr>
                                <td colspan="6" class="no-data">
                                    <i class="fas fa-search" style="font-size: 2rem; margin-bottom: 10px; display: block; opacity: 0.5;"></i>
                                    No students found matching your search.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php $count = $offset + 1; ?>
                            <?php foreach ($students as $student): ?>
                                <?php
                                $presentCount = (int) $student['present_count'];
                                $attendancePercentage = ($totalClasses > 0) ? round(($presentCount / $totalClasses) * 100, 1) : 0;
                                $percentageClass = '';
                                if ($attendancePercentage >= 80) $percentageClass = 'style="color: #10b981; font-weight: 600;"';
                                elseif ($attendancePercentage >= 60) $percentageClass = 'style="color: #f59e0b; font-weight: 600;"';
                                else $percentageClass = 'style="color: #ef4444; font-weight: 600;"';
                                ?>
                                <tr>
                                    <td><?= $count++ ?></td>
                                    <td><strong><?= htmlspecialchars($student['student_id'] ?? '') ?></strong></td>
                                    <td><?= htmlspecialchars($student['section'] ?? '') ?></td>
                                    <td><?= htmlspecialchars($student['name'] ?? '') ?></td>
                                    <td><strong><?= $presentCount ?></strong>/<?= $totalClasses ?></td>
                                    <td <?= $percentageClass ?>><?= $attendancePercentage ?>%</td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>

                <!-- Mobile Cards -->
                <div class="mobile-students-cards">
                    <?php if (empty($students)): ?>
                        <div class="empty-state">
                            <i class="fas fa-search"></i>
                            <p>No students found matching your search.</p>
                        </div>
                    <?php else: ?>
                        <?php $count = $offset + 1; ?>
                        <?php foreach ($students as $student): ?>
                            <?php
                            $presentCount = (int) $student['present_count'];
                            $attendancePercentage = ($totalClasses > 0) ? round(($presentCount / $totalClasses) * 100, 1) : 0;
                            $percentageColor = '';
                            if ($attendancePercentage >= 80) $percentageColor = '#10b981';
                            elseif ($attendancePercentage >= 60) $percentageColor = '#f59e0b';
                            else $percentageColor = '#ef4444';
                            ?>
                            <div class="student-card">
                                <div class="student-card-row">
                                    <span class="student-card-label"><i class="fas fa-hashtag"></i> Serial No</span>
                                    <span class="student-card-value"><?= $count++ ?></span>
                                </div>
                                <div class="student-card-row">
                                    <span class="student-card-label"><i class="fas fa-id-card"></i> Student ID</span>
                                    <span class="student-card-value"><strong><?= htmlspecialchars($student['student_id'] ?? '') ?></strong></span>
                                </div>
                                <div class="student-card-row">
                                    <span class="student-card-label"><i class="fas fa-layer-group"></i> Section</span>
                                    <span class="student-card-value"><?= htmlspecialchars($student['section'] ?? '') ?></span>
                                </div>
                                <div class="student-card-row">
                                    <span class="student-card-label"><i class="fas fa-user"></i> Name</span>
                                    <span class="student-card-value"><?= htmlspecialchars($student['name'] ?? '') ?></span>
                                </div>
                                <div class="student-card-row">
                                    <span class="student-card-label"><i class="fas fa-check-circle"></i> Present Days</span>
                                    <span class="student-card-value"><strong><?= $presentCount ?></strong>/<?= $totalClasses ?></span>
                                </div>
                                <div class="student-card-row">
                                    <span class="student-card-label"><i class="fas fa-chart-pie"></i> Attendance</span>
                                    <span class="student-card-value" style="color: <?= $percentageColor ?>; font-weight: 600;"><?= $attendancePercentage ?>%</span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="?page=<?= ($page - 1) ?>&sort=<?= $sortColumn ?>&order=<?= $sortOrder ?>&search=<?= urlencode($searchQuery) ?>" class="pagination-link">
                        <i class="fas fa-chevron-left"></i> Previous
                    </a>
                <?php endif; ?>
                
                <p class="page-number">Page <?= $page ?> of <?= $totalPages ?></p>
                
                <?php if ($page < $totalPages): ?>
                    <a href="?page=<?= ($page + 1) ?>&sort=<?= $sortColumn ?>&order=<?= $sortOrder ?>&search=<?= urlencode($searchQuery) ?>" class="pagination-link">
                        Next <i class="fas fa-chevron-right"></i>
                    </a>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <!-- Export Container -->
            <div class="export-container">
                <select id="export-format">
                    <option value="excel">📊 Excel (.xlsx)</option>
                    <option value="pdf">📄 PDF (.pdf)</option>
                    <option value="csv">📋 CSV (.csv)</option>
                    <option value="json">🔧 JSON (.json)</option>
                    <option value="xml">📋 XML (.xml)</option>
                    <option value="html">🌐 HTML (.html)</option>
                </select>
                <button id="export-btn">Export Data</button>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="index.js"></script>
    <script>
        document.getElementById("export-btn").addEventListener("click", function() {
            var format = document.getElementById("export-format").value;
            var searchQuery = "<?= urlencode($searchQuery) ?>";
            window.location.href = "export.php?format=" + format + "&search=" + searchQuery;
        });

        // Access denied function for restricted menu items
        function showAccessDenied() {
            Swal.fire({
                icon: 'warning',
                title: 'Access Denied',
                text: 'This feature is only available for administrators.',
                confirmButtonColor: '#10b981',
                background: 'rgba(255, 255, 255, 0.95)',
                backdrop: 'rgba(5, 150, 105, 0.1)'
            });
        }
    </script>
</body>
</html>