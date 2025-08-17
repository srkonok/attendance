<?php
session_start();

if (!isset($_SESSION["user"])) {
    header("Location: login.php");
    exit();
}

include 'db.php';

// Pagination settings
$limit = 20; // Number of students per page
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Search settings
$searchQuery = isset($_GET['search']) ? trim($_GET['search']) : '';

// Date range settings
$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$endDate = isset($_GET['end_date']) ? $_GET['end_date'] : '';

// If no dates provided, show from first attendance to last (instead of just last 30 days)
if (empty($startDate) || empty($endDate)) {
    $dateRangeQuery = "SELECT MIN(date) as min_date, MAX(date) as max_date FROM attendance";
    $dateRangeStmt = $conn->prepare($dateRangeQuery);
    $dateRangeStmt->execute();
    $dateRange = $dateRangeStmt->fetch();
    
    if ($dateRange['min_date'] && $dateRange['max_date']) {
        $startDate = $dateRange['min_date']; // Show from first attendance
        $endDate = $dateRange['max_date'];   // Show to last attendance
    } else {
        // Default to current month if no attendance data
        $startDate = date('Y-m-01');
        $endDate = date('Y-m-t');
    }
}

// Get all class dates within the range
$classesQuery = "SELECT DISTINCT date FROM attendance WHERE date BETWEEN :start_date AND :end_date ORDER BY date ASC";
$classesStmt = $conn->prepare($classesQuery);
$classesStmt->bindParam(':start_date', $startDate);
$classesStmt->bindParam(':end_date', $endDate);
$classesStmt->execute();
$classDates = $classesStmt->fetchAll(PDO::FETCH_COLUMN);

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

// Fetch students with pagination
$studentsQuery = "SELECT student_id, name, section FROM students 
    WHERE name LIKE :search OR student_id LIKE :search OR section LIKE :search
    ORDER BY section ASC, student_id ASC 
    LIMIT :limit OFFSET :offset";
$studentsStmt = $conn->prepare($studentsQuery);
$studentsStmt->bindValue(':search', '%' . $searchQuery . '%', PDO::PARAM_STR);
$studentsStmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$studentsStmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$studentsStmt->execute();
$students = $studentsStmt->fetchAll();

// Fetch attendance data for the grid
$attendanceData = [];
if (!empty($students) && !empty($classDates)) {
    $studentIds = array_column($students, 'student_id');
    $placeholders = str_repeat('?,', count($studentIds) - 1) . '?';
    
    $attendanceQuery = "SELECT student_id, date FROM attendance 
        WHERE student_id IN ($placeholders) 
        AND date BETWEEN ? AND ?";
    
    $params = array_merge($studentIds, [$startDate, $endDate]);
    $attendanceStmt = $conn->prepare($attendanceQuery);
    $attendanceStmt->execute($params);
    
    while ($row = $attendanceStmt->fetch()) {
        $attendanceData[$row['student_id']][$row['date']] = true;
    }
}

// Calculate statistics
$totalClasses = count($classDates);
$totalPresent = 0;
$totalPossible = $totalClasses * count($students);

foreach ($students as $student) {
    $studentId = $student['student_id'];
    foreach ($classDates as $date) {
        if (isset($attendanceData[$studentId][$date])) {
            $totalPresent++;
        }
    }
}

$overallAttendanceRate = $totalPossible > 0 ? round(($totalPresent / $totalPossible) * 100, 1) : 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <title>Cloud Computing - Attendance Grid</title>
    <link rel="icon" href="images/favicon_io/favicon.ico" type="image/x-icon">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="index.css">
    <style>
        /* Additional styles for attendance grid */
        .controls-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border: 2px solid rgba(5, 150, 105, 0.2);
            border-radius: 16px;
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: 0 15px 35px rgba(5, 150, 105, 0.1);
        }

        .controls-grid {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr auto;
            gap: 15px;
            align-items: end;
            margin-bottom: 20px;
        }

        .date-input-group, .search-input-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .date-input-group label, .search-input-group label {
            font-size: 14px;
            font-weight: 600;
            color: #059669;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .date-input-group input[type="date"], .search-input-group input {
            padding: 12px 15px;
            background: rgba(255, 255, 255, 0.9);
            border: 2px solid rgba(5, 150, 105, 0.2);
            border-radius: 10px;
            color: #374151;
            font-size: 15px;
            transition: all 0.3s ease;
        }

        .date-input-group input[type="date"]:focus, .search-input-group input:focus {
            outline: none;
            border-color: #10b981;
            box-shadow: 0 0 0 3px rgba(5, 150, 105, 0.1);
        }

        .controls-buttons {
            display: flex;
            gap: 10px;
            flex-direction: column;
        }

        .btn-filter, .btn-reset {
            padding: 12px 20px;
            border: none;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            text-decoration: none;
            min-height: 44px;
        }

        .btn-filter {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
        }

        .btn-filter:hover {
            background: linear-gradient(135deg, #059669, #047857);
            transform: translateY(-1px);
            box-shadow: 0 5px 15px rgba(5, 150, 105, 0.3);
        }

        .btn-reset {
            background: rgba(107, 114, 128, 0.1);
            color: #6b7280;
            border: 1px solid rgba(107, 114, 128, 0.3);
        }

        .btn-reset:hover {
            background: rgba(107, 114, 128, 0.2);
            color: #374151;
        }

        /* Stats cards */
        .stats-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }

        .stat-mini-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(15px);
            border: 2px solid rgba(5, 150, 105, 0.2);
            border-radius: 12px;
            padding: 15px;
            text-align: center;
            box-shadow: 0 8px 20px rgba(5, 150, 105, 0.1);
            transition: all 0.3s ease;
        }

        .stat-mini-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 25px rgba(5, 150, 105, 0.15);
        }

        .stat-mini-card .stat-icon {
            font-size: 1.5rem;
            color: #10b981;
            margin-bottom: 5px;
        }

        .stat-mini-card .stat-number {
            font-size: 1.3rem;
            font-weight: 700;
            color: #059669;
            display: block;
            margin-bottom: 2px;
        }

        .stat-mini-card .stat-label {
            color: #6b7280;
            font-size: 0.8rem;
            font-weight: 500;
        }

        /* Attendance Grid */
        .grid-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border: 2px solid rgba(5, 150, 105, 0.2);
            border-radius: 16px;
            padding: 20px;
            box-shadow: 0 20px 40px rgba(5, 150, 105, 0.15);
            overflow: hidden;
        }

        .grid-scroll {
            overflow-x: auto;
            overflow-y: visible;
            -webkit-overflow-scrolling: touch;
        }

        .attendance-grid {
            min-width: fit-content;
            border-collapse: separate;
            border-spacing: 0;
            width: 100%;
        }

        .attendance-grid th,
        .attendance-grid td {
            border: 1px solid rgba(5, 150, 105, 0.1);
            text-align: center;
            vertical-align: middle;
            position: relative;
        }

        /* Sticky student info columns */
        .attendance-grid th:first-child,
        .attendance-grid th:nth-child(2),
        .attendance-grid th:nth-child(3),
        .attendance-grid td:first-child,
        .attendance-grid td:nth-child(2),
        .attendance-grid td:nth-child(3) {
            position: sticky;
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(20px);
            z-index: 10;
        }

        .attendance-grid th:first-child,
        .attendance-grid td:first-child {
            left: 0;
            border-right: 2px solid rgba(5, 150, 105, 0.3);
        }

        .attendance-grid th:nth-child(2),
        .attendance-grid td:nth-child(2) {
            left: 80px;
            border-right: 2px solid rgba(5, 150, 105, 0.3);
        }

        .attendance-grid th:nth-child(3),
        .attendance-grid td:nth-child(3) {
            left: 210px;
            border-right: 2px solid rgba(5, 150, 105, 0.3);
        }

        /* Header styles */
        .attendance-grid thead th {
            background: rgba(5, 150, 105, 0.1);
            color: #059669;
            font-weight: 600;
            padding: 12px 8px;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 2px solid rgba(5, 150, 105, 0.3);
        }

        .attendance-grid thead th.student-info {
            font-size: 13px;
            padding: 12px 10px;
        }

        .attendance-grid thead th.date-header {
            writing-mode: vertical-rl;
            text-orientation: mixed;
            min-width: 40px;
            max-width: 40px;
            height: 120px;
            font-size: 11px;
            white-space: nowrap;
        }

        /* Student row styles */
        .attendance-grid tbody td {
            padding: 8px;
            font-size: 13px;
            transition: all 0.2s ease;
        }

        .attendance-grid tbody td.student-info {
            text-align: left;
            padding: 10px 12px;
            font-weight: 500;
            color: #374151;
        }

        .attendance-grid tbody td.student-id {
            font-family: 'Courier New', monospace;
            font-weight: 600;
            color: #059669;
        }

        .attendance-grid tbody td.student-section {
            color: #6b7280;
            font-size: 12px;
            text-align: center;
        }

        /* Attendance cell styles */
        .attendance-cell {
            width: 40px;
            height: 40px;
            min-width: 40px;
            max-width: 40px;
            position: relative;
            cursor: pointer;
        }

        .attendance-status {
            display: inline-block;
            width: 28px;
            height: 28px;
            border-radius: 50%;
            font-weight: 700;
            font-size: 14px;
            line-height: 28px;
            text-align: center;
            margin: auto;
            transition: all 0.3s ease;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .attendance-status.present {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
        }

        .attendance-status.absent {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: white;
        }

        .attendance-cell:hover .attendance-status {
            transform: scale(1.1);
        }

        /* Row hover effect */
        .attendance-grid tbody tr:hover {
            background: rgba(5, 150, 105, 0.03);
        }

        .attendance-grid tbody tr:hover td:first-child,
        .attendance-grid tbody tr:hover td:nth-child(2),
        .attendance-grid tbody tr:hover td:nth-child(3) {
            background: rgba(5, 150, 105, 0.05);
        }

        /* Empty state */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #6b7280;
        }

        .empty-state i {
            font-size: 3rem;
            margin-bottom: 15px;
            display: block;
            opacity: 0.5;
        }

        .empty-state h3 {
            font-size: 1.2rem;
            margin-bottom: 8px;
            color: #374151;
        }

        .empty-state p {
            font-size: 0.9rem;
        }

        /* Legend */
        .legend {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 30px;
            margin-top: 20px;
            padding: 15px;
            background: rgba(5, 150, 105, 0.05);
            border-radius: 12px;
        }

        .legend-item {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            font-weight: 500;
        }

        .legend-item .attendance-status {
            width: 20px;
            height: 20px;
            line-height: 20px;
            font-size: 12px;
            margin: 0;
        }

        /* Export container styles */
        .export-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border: 2px solid rgba(5, 150, 105, 0.2);
            border-radius: 16px;
            padding: 20px;
            margin-top: 25px;
            box-shadow: 0 15px 35px rgba(5, 150, 105, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
            flex-wrap: wrap;
        }

        .export-container select {
            padding: 12px 15px;
            background: rgba(255, 255, 255, 0.9);
            border: 2px solid rgba(5, 150, 105, 0.2);
            border-radius: 10px;
            color: #374151;
            font-size: 15px;
            min-width: 180px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .export-container select:focus {
            outline: none;
            border-color: #10b981;
            box-shadow: 0 0 0 3px rgba(5, 150, 105, 0.1);
        }

        .export-container button {
            padding: 12px 25px;
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

        .export-container button:hover {
            background: linear-gradient(135deg, #059669, #047857);
            transform: translateY(-1px);
            box-shadow: 0 5px 15px rgba(5, 150, 105, 0.3);
        }

        /* Mobile responsiveness */
        @media (max-width: 767px) {
            .controls-grid {
                grid-template-columns: 1fr;
                gap: 15px;
            }

            .controls-buttons {
                flex-direction: row;
                justify-content: center;
            }

            .btn-filter, .btn-reset {
                flex: 1;
                justify-content: center;
            }

            .stats-row {
                grid-template-columns: repeat(2, 1fr);
                gap: 10px;
                margin-bottom: 15px;
            }

            .stat-mini-card {
                padding: 12px;
            }

            .stat-mini-card .stat-icon {
                font-size: 1.2rem;
            }

            .stat-mini-card .stat-number {
                font-size: 1.1rem;
            }

            .stat-mini-card .stat-label {
                font-size: 0.75rem;
            }

            .grid-container {
                padding: 15px;
            }

            .attendance-grid th:first-child,
            .attendance-grid td:first-child {
                left: 0;
                min-width: 50px;
            }

            .attendance-grid th:nth-child(2),
            .attendance-grid td:nth-child(2) {
                left: 50px;
                min-width: 100px;
            }

            .attendance-grid th:nth-child(3),
            .attendance-grid td:nth-child(3) {
                left: 150px;
                min-width: 60px;
            }

            .attendance-grid thead th.date-header {
                font-size: 10px;
                min-width: 35px;
                max-width: 35px;
                height: 100px;
            }

            .attendance-cell {
                width: 35px;
                min-width: 35px;
                max-width: 35px;
            }

            .attendance-status {
                width: 24px;
                height: 24px;
                line-height: 24px;
                font-size: 12px;
            }

            .legend {
                flex-wrap: wrap;
                gap: 15px;
            }

            .legend-item {
                font-size: 12px;
            }

            .export-container {
                flex-direction: column;
                gap: 10px;
            }

            .export-container select,
            .export-container button {
                width: 100%;
                justify-content: center;
            }
        }

        /* Print styles */
        @media print {
            .controls-container,
            .pagination,
            .menu-container,
            .bg-particles,
            .export-container {
                display: none !important;
            }

            .attendance-grid {
                font-size: 8px !important;
            }

            .attendance-grid th,
            .attendance-grid td {
                padding: 2px !important;
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
                <h1><i class="fas fa-th"></i> Attendance Grid View</h1>
                <p><i class="fas fa-calendar"></i> <?= htmlspecialchars(date('M j, Y', strtotime($startDate))) ?> - <?= htmlspecialchars(date('M j, Y', strtotime($endDate))) ?></p>
            </div>
            <div class="menu-container" id="menuContainer">
                <button class="menu-button" id="menuButton" aria-label="Menu" aria-haspopup="true" aria-expanded="false">
                    <i class="fas fa-bars"></i>
                </button>
                <div class="dropdown-menu" id="dropdownMenu" role="menu">
                    <a href="" role="menuitem"><i class="fas fa-home"></i> Dashboard</a>
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

    <div class="container">
        <!-- Controls -->
        <div class="controls-container">
            <form method="GET" action="" class="controls-grid">
                <div class="date-input-group">
                    <label for="start_date"><i class="fas fa-calendar-alt"></i> Start Date</label>
                    <input type="date" id="start_date" name="start_date" value="<?= htmlspecialchars($startDate) ?>">
                </div>
                <div class="date-input-group">
                    <label for="end_date"><i class="fas fa-calendar-alt"></i> End Date</label>
                    <input type="date" id="end_date" name="end_date" value="<?= htmlspecialchars($endDate) ?>">
                </div>
                <div class="search-input-group">
                    <label for="search"><i class="fas fa-search"></i> Search Students</label>
                    <input type="text" id="search" name="search" value="<?= htmlspecialchars($searchQuery) ?>" placeholder="Name, ID, or Section">
                </div>
                <div class="controls-buttons">
                    <button type="submit" class="btn-filter">
                        <i class="fas fa-filter"></i> Filter
                    </button>
                    <a href="?" class="btn-reset">
                        <i class="fas fa-undo"></i> Reset
                    </a>
                </div>
            </form>

            <!-- Statistics -->
            <div class="stats-row">
                <div class="stat-mini-card">
                    <i class="fas fa-users stat-icon"></i>
                    <span class="stat-number"><?= count($students) ?></span>
                    <span class="stat-label">Students Shown</span>
                </div>
                <div class="stat-mini-card">
                    <i class="fas fa-calendar-day stat-icon"></i>
                    <span class="stat-number"><?= $totalClasses ?></span>
                    <span class="stat-label">Class Days</span>
                </div>
                <div class="stat-mini-card">
                    <i class="fas fa-check-circle stat-icon"></i>
                    <span class="stat-number"><?= $totalPresent ?></span>
                    <span class="stat-label">Total Present</span>
                </div>
                <div class="stat-mini-card">
                    <i class="fas fa-percentage stat-icon"></i>
                    <span class="stat-number"><?= $overallAttendanceRate ?>%</span>
                    <span class="stat-label">Overall Rate</span>
                </div>
            </div>
        </div>


        <!-- Attendance Grid -->
        <div class="grid-container">
            <?php if (empty($students)): ?>
                <div class="empty-state">
                    <i class="fas fa-search"></i>
                    <h3>No Students Found</h3>
                    <p>Try adjusting your search criteria or date range.</p>
                </div>
            <?php elseif (empty($classDates)): ?>
                <div class="empty-state">
                    <i class="fas fa-calendar-times"></i>
                    <h3>No Classes Found</h3>
                    <p>No attendance records found for the selected date range.</p>
                </div>
            <?php else: ?>
                <div class="grid-scroll">
                    <table class="attendance-grid">
                        <thead>
                            <tr>
                                <th class="student-info">ID</th>
                                <th class="student-info">Name</th>
                                <th class="student-info">Section</th>
                                <?php foreach ($classDates as $date): ?>
                                    <th class="date-header" title="<?= date('l, F j, Y', strtotime($date)) ?>">
                                        <?= date('M j', strtotime($date)) ?><br>
                                        <small><?= date('D', strtotime($date)) ?></small>
                                    </th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($students as $student): ?>
                                <tr>
                                    <td class="student-info student-id"><?= htmlspecialchars($student['student_id']) ?></td>
                                    <td class="student-info"><?= htmlspecialchars($student['name']) ?></td>
                                    <td class="student-info student-section"><?= htmlspecialchars($student['section']) ?></td>
                                    <?php foreach ($classDates as $date): ?>
                                        <td class="attendance-cell">
                                            <?php
                                            $isPresent = isset($attendanceData[$student['student_id']][$date]);
                                            if ($isPresent) {
                                                echo '<span class="attendance-status present" title="Present on ' . date('M j, Y', strtotime($date)) . '">P</span>';
                                            } else {
                                                echo '<span class="attendance-status absent" title="Absent on ' . date('M j, Y', strtotime($date)) . '">A</span>';
                                            }
                                            ?>
                                        </td>
                                    <?php endforeach; ?>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Legend -->
                <div class="legend">
                    <div class="legend-item">
                        <span class="attendance-status present">P</span>
                        <span>Present</span>
                    </div>
                    <div class="legend-item">
                        <span class="attendance-status absent">A</span>
                        <span>Absent</span>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
        <div class="pagination">
            <?php if ($page > 1): ?>
                <a href="?page=<?= ($page - 1) ?>&start_date=<?= urlencode($startDate) ?>&end_date=<?= urlencode($endDate) ?>&search=<?= urlencode($searchQuery) ?>" class="pagination-link">
                    <i class="fas fa-chevron-left"></i> Previous
                </a>
            <?php endif; ?>
            
            <p class="page-number">Page <?= $page ?> of <?= $totalPages ?></p>
            
            <?php if ($page < $totalPages): ?>
                <a href="?page=<?= ($page + 1) ?>&start_date=<?= urlencode($startDate) ?>&end_date=<?= urlencode($endDate) ?>&search=<?= urlencode($searchQuery) ?>" class="pagination-link">
                    Next <i class="fas fa-chevron-right"></i>
                </a>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- Export Options -->
        <div class="export-container" style="margin-top: 30px;">
            <select id="export-format">
                <option value="excel">📊 Excel (.xlsx)</option>
                <option value="pdf">📄 PDF (.pdf)</option>
                <option value="csv">📋 CSV (.csv)</option>
            </select>
            <button id="export-btn" onclick="exportGrid()">
                <i class="fas fa-download"></i> Export Grid
            </button>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="index.js"></script>
    <script>
        // Export functionality
        function exportGrid() {
            const format = document.getElementById('export-format').value;
            const startDate = '<?= $startDate ?>';
            const endDate = '<?= $endDate ?>';
            const search = '<?= urlencode($searchQuery) ?>';
            
            // You can implement export functionality here
            // For now, show a message
            Swal.fire({
                icon: 'info',
                title: 'Export Feature',
                text: `Exporting attendance grid as ${format.toUpperCase()} format...`,
                confirmButtonColor: '#10b981',
                background: 'rgba(255, 255, 255, 0.95)',
                backdrop: 'rgba(5, 150, 105, 0.1)'
            });
            
            // Uncomment below to implement actual export
            // window.location.href = `export_grid.php?format=${format}&start_date=${startDate}&end_date=${endDate}&search=${search}`;
        }

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

        // Smooth scrolling for horizontal scroll
        document.querySelector('.grid-scroll').addEventListener('wheel', function(e) {
            if (e.deltaY !== 0) {
                e.preventDefault();
                this.scrollLeft += e.deltaY;
            }
        });

        // Enhanced date input validation
        document.getElementById('start_date').addEventListener('change', function() {
            const startDate = this.value;
            const endDateInput = document.getElementById('end_date');
            const endDate = endDateInput.value;
            
            if (startDate && endDate && startDate > endDate) {
                endDateInput.value = startDate;
            }
        });

        document.getElementById('end_date').addEventListener('change', function() {
            const endDate = this.value;
            const startDateInput = document.getElementById('start_date');
            const startDate = startDateInput.value;
            
            if (startDate && endDate && endDate < startDate) {
                startDateInput.value = endDate;
            }
        });

        // Keyboard navigation for the grid
        document.addEventListener('keydown', function(e) {
            const gridScroll = document.querySelector('.grid-scroll');
            if (!gridScroll) return;

            switch(e.key) {
                case 'ArrowLeft':
                    if (e.ctrlKey) {
                        e.preventDefault();
                        gridScroll.scrollLeft -= 100;
                    }
                    break;
                case 'ArrowRight':
                    if (e.ctrlKey) {
                        e.preventDefault();
                        gridScroll.scrollLeft += 100;
                    }
                    break;
                case 'Home':
                    if (e.ctrlKey) {
                        e.preventDefault();
                        gridScroll.scrollLeft = 0;
                    }
                    break;
                case 'End':
                    if (e.ctrlKey) {
                        e.preventDefault();
                        gridScroll.scrollLeft = gridScroll.scrollWidth;
                    }
                    break;
            }
        });

        // Touch/swipe support for mobile
        let isDown = false;
        let startX;
        let scrollLeft;
        const gridScroll = document.querySelector('.grid-scroll');

        if (gridScroll) {
            gridScroll.addEventListener('mousedown', (e) => {
                isDown = true;
                gridScroll.classList.add('active');
                startX = e.pageX - gridScroll.offsetLeft;
                scrollLeft = gridScroll.scrollLeft;
            });

            gridScroll.addEventListener('mouseleave', () => {
                isDown = false;
                gridScroll.classList.remove('active');
            });

            gridScroll.addEventListener('mouseup', () => {
                isDown = false;
                gridScroll.classList.remove('active');
            });

            gridScroll.addEventListener('mousemove', (e) => {
                if (!isDown) return;
                e.preventDefault();
                const x = e.pageX - gridScroll.offsetLeft;
                const walk = (x - startX) * 2;
                gridScroll.scrollLeft = scrollLeft - walk;
            });
        }

        // Tooltip enhancement for attendance cells
        document.querySelectorAll('.attendance-status').forEach(cell => {
            cell.addEventListener('mouseenter', function() {
                this.style.transform = 'scale(1.2)';
                this.style.zIndex = '20';
            });
            
            cell.addEventListener('mouseleave', function() {
                this.style.transform = 'scale(1)';
                this.style.zIndex = '1';
            });
        });

        // Print functionality
        function printGrid() {
            window.print();
        }

        // Add print button to export container
        document.addEventListener('DOMContentLoaded', function() {
            const exportContainer = document.querySelector('.export-container');
            if (exportContainer) {
                const printBtn = document.createElement('button');
                printBtn.innerHTML = '<i class="fas fa-print"></i> Print Grid';
                printBtn.onclick = printGrid;
                printBtn.style.cssText = `
                    padding: 12px 25px;
                    background: linear-gradient(135deg, #6366f1, #4f46e5);
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
                `;
                exportContainer.appendChild(printBtn);
                
                printBtn.addEventListener('mouseover', function() {
                    this.style.background = 'linear-gradient(135deg, #4f46e5, #4338ca)';
                    this.style.transform = 'translateY(-1px)';
                    this.style.boxShadow = '0 5px 15px rgba(79, 70, 229, 0.3)';
                });
                
                printBtn.addEventListener('mouseout', function() {
                    this.style.background = 'linear-gradient(135deg, #6366f1, #4f46e5)';
                    this.style.transform = 'translateY(0)';
                    this.style.boxShadow = 'none';
                });
            }
        });
    </script>
</body>
</html>