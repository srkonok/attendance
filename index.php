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
  <style>
   * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    html {
      font-size: 16px;
      -webkit-text-size-adjust: 100%;
      -webkit-tap-highlight-color: transparent;
    }

    body {
      font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
      background: linear-gradient(-45deg, #059669, #10b981, #34d399, #6ee7b7);
      background-size: 400% 400%;
      animation: gradientShift 15s ease infinite;
      min-height: 100vh;
      color: #1f2937;
      overflow-x: hidden;
      -webkit-font-smoothing: antialiased;
      -moz-osx-font-smoothing: grayscale;
    }

    @keyframes gradientShift {
      0% { background-position: 0% 50%; }
      50% { background-position: 100% 50%; }
      100% { background-position: 0% 50%; }
    }

    /* Animated background particles */
    .bg-particles {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      pointer-events: none;
      z-index: -1;
    }

    .particle {
      position: absolute;
      width: 4px;
      height: 4px;
      background: rgba(255, 255, 255, 0.3);
      border-radius: 50%;
      animation: float 20s infinite linear;
    }

    @keyframes float {
      0% {
        transform: translateY(100vh) rotate(0deg);
        opacity: 0;
      }
      10% { opacity: 1; }
      90% { opacity: 1; }
      100% {
        transform: translateY(-100px) rotate(360deg);
        opacity: 0;
      }
    }

    /* Header Styles */
    .header-container {
      background: rgba(255, 255, 255, 0.95);
      backdrop-filter: blur(20px);
      border-bottom: 2px solid rgba(5, 150, 105, 0.2);
      padding: 20px 0;
      position: sticky;
      top: 0;
      z-index: 1000;
      box-shadow: 0 4px 20px rgba(5, 150, 105, 0.1);
      animation: slideDown 0.8s ease-out;
    }

    @keyframes slideDown {
      from {
        transform: translateY(-100%);
        opacity: 0;
      }
      to {
        transform: translateY(0);
        opacity: 1;
      }
    }

    .header-content {
      max-width: 1200px;
      margin: 0 auto;
      padding: 0 20px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      position: relative;
    }

    .header-content h1 {
      font-size: 2rem;
      font-weight: 700;
      background: linear-gradient(135deg, #059669, #10b981);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
      animation: glow 2s ease-in-out infinite alternate;
      display: flex;
      align-items: center;
      gap: 12px;
    }

    @keyframes glow {
      from { filter: drop-shadow(0 0 5px rgba(5, 150, 105, 0.3)); }
      to { filter: drop-shadow(0 0 15px rgba(5, 150, 105, 0.6)); }
    }

    .header-content p {
      color: #6b7280;
      font-size: 1rem;
      margin-top: 5px;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    /* FIXED Menu Styles */
    .menu-container {
      position: relative;
      display: inline-block;
    }

    .menu-button {
      background: rgba(255, 255, 255, 0.9);
      border: 2px solid #10b981;
      border-radius: 12px;
      padding: 15px 20px;
      color: #059669;
      font-size: 22px;
      font-weight: bold;
      cursor: pointer;
      transition: all 0.3s ease;
      backdrop-filter: blur(15px);
      box-shadow: 0 4px 15px rgba(5, 150, 105, 0.2);
      min-width: 60px;
      display: flex;
      align-items: center;
      justify-content: center;
      outline: none;
    }

    .menu-button:hover,
    .menu-button:focus {
      background: #10b981;
      border-color: #059669;
      color: #ffffff;
      transform: translateY(-3px) scale(1.05);
      box-shadow: 0 10px 30px rgba(5, 150, 105, 0.4);
    }

    .menu-button:active {
      transform: translateY(-1px) scale(1.02);
    }

    .dropdown-menu {
      position: absolute;
      right: 0;
      top: 100%;
      margin-top: 10px;
      background: rgba(255, 255, 255, 0.98);
      backdrop-filter: blur(25px);
      border: 2px solid rgba(5, 150, 105, 0.2);
      border-radius: 16px;
      min-width: 280px;
      box-shadow: 0 25px 50px rgba(5, 150, 105, 0.3);
      opacity: 0;
      visibility: hidden;
      transform: translateY(-15px) scale(0.95);
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
      z-index: 1001;
      overflow: hidden;
      max-height: 70vh;
      overflow-y: auto;
      overscroll-behavior: contain;
      -webkit-overflow-scrolling: touch;
    }

    /* This is the key fix - dropdown shows when menu has 'active' class */
    .menu-container.active .dropdown-menu {
      opacity: 1;
      visibility: visible;
      transform: translateY(0) scale(1);
    }

    /* Also show on hover for desktop */
    @media (hover: hover) and (pointer: fine) {
      .menu-container:hover .dropdown-menu {
        opacity: 1;
        visibility: visible;
        transform: translateY(0) scale(1);
      }
    }

    .dropdown-menu::before {
      content: '';
      position: absolute;
      top: -8px;
      right: 25px;
      width: 16px;
      height: 16px;
      background: rgba(255, 255, 255, 0.98);
      border: 2px solid rgba(5, 150, 105, 0.2);
      border-bottom: none;
      border-right: none;
      transform: rotate(45deg);
      backdrop-filter: blur(25px);
    }

    .dropdown-menu a {
      color: #374151;
      padding: 16px 24px;
      text-decoration: none;
      display: flex;
      align-items: center;
      transition: all 0.3s ease;
      font-weight: 500;
      position: relative;
      overflow: hidden;
      min-height: 48px;
      border-bottom: 1px solid rgba(5, 150, 105, 0.1);
    }

    .dropdown-menu a:last-child {
      border-bottom: none;
    }

    .dropdown-menu a::before {
      content: '';
      position: absolute;
      left: 0;
      top: 0;
      width: 0;
      height: 100%;
      background: linear-gradient(90deg, rgba(5, 150, 105, 0.1), rgba(16, 185, 129, 0.05));
      transition: width 0.3s ease;
      z-index: -1;
    }

    .dropdown-menu a:hover {
      background: rgba(5, 150, 105, 0.1);
      transform: translateX(8px);
      color: #059669;
      border-left: 3px solid #10b981;
    }

    .dropdown-menu a:hover::before {
      width: 100%;
    }

    .dropdown-menu a i {
      margin-right: 12px;
      width: 18px;
      text-align: center;
      font-size: 16px;
      transition: transform 0.3s ease;
    }

    .dropdown-menu a:hover i {
      transform: scale(1.1);
    }

    .dropdown-menu a[style*="red"] {
      border-top: 2px solid rgba(239, 68, 68, 0.2);
      margin-top: 5px;
    }

    .dropdown-menu a[style*="red"]:hover {
      background: rgba(239, 68, 68, 0.1);
      color: #dc2626;
      border-left-color: #ef4444;
    }

    .dropdown-menu a[style*="red"]:hover::before {
      background: linear-gradient(90deg, rgba(239, 68, 68, 0.1), rgba(239, 68, 68, 0.05));
    }

    /* Menu overlay for mobile */
    .menu-overlay {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(0, 0, 0, 0.5);
      backdrop-filter: blur(5px);
      opacity: 0;
      visibility: hidden;
      transition: all 0.3s ease;
      z-index: 1000;
    }

    .menu-overlay.active {
      opacity: 1;
      visibility: visible;
    }

    /* Container Styles */
    .container {
      max-width: 1200px;
      margin: 40px auto;
      padding: 0 20px;
    }

    .card {
      background: rgba(255, 255, 255, 0.95);
      backdrop-filter: blur(20px);
      border: 2px solid rgba(5, 150, 105, 0.2);
      border-radius: 20px;
      padding: 30px;
      margin-bottom: 30px;
      box-shadow: 0 20px 40px rgba(5, 150, 105, 0.15);
      animation: fadeInUp 0.8s ease-out;
    }

    @keyframes fadeInUp {
      from {
        opacity: 0;
        transform: translateY(30px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    .card h2 {
      font-size: 1.8rem;
      font-weight: 600;
      margin-bottom: 20px;
      color: #059669;
      display: flex;
      align-items: center;
    }

    .card h2 i {
      margin-right: 12px;
    }

    /* Message Styles */
    .message {
      padding: 16px 24px;
      border-radius: 12px;
      margin-bottom: 20px;
      font-weight: 500;
      animation: slideIn 0.5s ease-out;
      display: flex;
      align-items: center;
    }

    @keyframes slideIn {
      from {
        opacity: 0;
        transform: translateX(-20px);
      }
      to {
        opacity: 1;
        transform: translateX(0);
      }
    }

    .message.success {
      background: rgba(5, 150, 105, 0.1);
      border: 2px solid rgba(5, 150, 105, 0.3);
      color: #059669;
    }

    .message.success::before {
      content: '✓';
      margin-right: 10px;
      font-weight: bold;
    }

    .message.error {
      background: rgba(239, 68, 68, 0.1);
      border: 2px solid rgba(239, 68, 68, 0.3);
      color: #dc2626;
    }

    .message.error::before {
      content: '✕';
      margin-right: 10px;
      font-weight: bold;
    }

    /* Search Container */
    .search-container {
      display: grid;
      grid-template-columns: 1fr 1fr auto;
      gap: 15px;
      margin-bottom: 25px;
      align-items: end;
    }

    .form-group {
      position: relative;
    }

    .form-group input {
      width: 100%;
      padding: 15px 20px;
      background: rgba(255, 255, 255, 0.9);
      border: 2px solid rgba(5, 150, 105, 0.2);
      border-radius: 12px;
      color: #374151;
      font-size: 16px;
      transition: all 0.3s ease;
      backdrop-filter: blur(10px);
      min-height: 48px;
    }

    .form-group input:focus {
      outline: none;
      border-color: #10b981;
      background: rgba(255, 255, 255, 0.95);
      box-shadow: 0 0 0 4px rgba(5, 150, 105, 0.1);
    }

    .form-group input::placeholder {
      color: #9ca3af;
    }

    .btn, button[type="submit"] {
      padding: 15px 25px;
      background: linear-gradient(135deg, #10b981, #059669);
      border: none;
      border-radius: 12px;
      color: #ffffff;
      font-size: 16px;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.3s ease;
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      min-height: 54px;
      position: relative;
      overflow: hidden;
    }

    .btn:hover, button[type="submit"]:hover {
      background: linear-gradient(135deg, #059669, #047857);
      transform: translateY(-2px);
      box-shadow: 0 10px 25px rgba(5, 150, 105, 0.3);
    }

    .btn i, button[type="submit"] i {
      margin-right: 8px;
    }

    /* Table Styles */
    .attendance-list {
      max-width: 1200px;
      margin: 0 auto;
      padding: 20px;
    }

    .attendance-list h2 {
      font-size: 2rem;
      font-weight: 600;
      margin-bottom: 30px;
      color: #059669;
      display: flex;
      align-items: center;
      text-align: center;
      justify-content: center;
    }

    .attendance-list h2 i {
      margin-right: 15px;
    }

    .table-container {
      overflow: hidden;
      border-radius: 16px;
      background: rgba(255, 255, 255, 0.95);
      backdrop-filter: blur(10px);
      border: 2px solid rgba(5, 150, 105, 0.2);
      box-shadow: 0 20px 40px rgba(5, 150, 105, 0.15);
      position: relative;
    }

    .attendance-table {
      width: 100%;
      border-collapse: collapse;
    }

    .attendance-table th {
      background: rgba(5, 150, 105, 0.1);
      padding: 20px;
      text-align: left;
      font-weight: 600;
      color: #059669;
      border-bottom: 2px solid rgba(5, 150, 105, 0.2);
    }

    .attendance-table td {
      padding: 20px;
      border-bottom: 1px solid rgba(5, 150, 105, 0.1);
      transition: all 0.3s ease;
      color: #374151;
    }

    .attendance-table tbody tr {
      transition: all 0.3s ease;
    }

    .attendance-table tbody tr:hover {
      background: rgba(5, 150, 105, 0.05);
      transform: scale(1.01);
    }

    /* Mobile Cards (hidden by default) */
    .mobile-attendance-cards {
      display: none;
    }

    .attendance-card {
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

    .attendance-card:hover {
      transform: translateY(-3px);
      background: rgba(5, 150, 105, 0.05);
      box-shadow: 0 15px 35px rgba(5, 150, 105, 0.2);
    }

    .attendance-card-row {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 10px;
      padding-bottom: 8px;
      border-bottom: 1px solid rgba(5, 150, 105, 0.1);
    }

    .attendance-card-row:last-child {
      margin-bottom: 0;
      border-bottom: none;
    }

    .attendance-card-label {
      font-weight: 600;
      color: #059669;
      font-size: 0.9rem;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .attendance-card-value {
      color: #374151;
      font-size: 0.95rem;
      text-align: right;
      font-weight: 500;
    }

    /* Pagination */
    .pagination {
      display: flex;
      justify-content: center;
      align-items: center;
      gap: 20px;
      margin-top: 30px;
    }

    .pagination-link {
      padding: 12px 20px;
      background: rgba(255, 255, 255, 0.9);
      border: 2px solid rgba(5, 150, 105, 0.2);
      border-radius: 10px;
      color: #059669;
      text-decoration: none;
      transition: all 0.3s ease;
      backdrop-filter: blur(10px);
      min-height: 48px;
      display: flex;
      align-items: center;
    }

    .pagination-link:hover {
      background: rgba(5, 150, 105, 0.1);
      border-color: rgba(5, 150, 105, 0.3);
      transform: translateY(-2px);
    }

    .page-number {
      color: #6b7280;
      font-weight: 500;
    }

    /* Empty State */
    .empty-state {
      padding: 40px 20px;
      text-align: center;
    }

    .empty-state i {
      font-size: 2.5rem;
      margin-bottom: 15px;
      display: block;
      color: rgba(5, 150, 105, 0.5);
    }

    .empty-state p {
      font-size: 16px;
      color: #6b7280;
    }

    /* Loading animation */
    .loading {
      display: inline-block;
      width: 20px;
      height: 20px;
      border: 3px solid rgba(5, 150, 105, 0.3);
      border-radius: 50%;
      border-top-color: #10b981;
      animation: spin 1s ease-in-out infinite;
    }

    @keyframes spin {
      to { transform: rotate(360deg); }
    }

    /* Ripple Effect */
    .ripple {
      position: absolute;
      border-radius: 50%;
      background: rgba(255, 255, 255, 0.3);
      transform: scale(0);
      animation: ripple-animation 0.6s linear;
      pointer-events: none;
    }

    @keyframes ripple-animation {
      to {
        transform: scale(4);
        opacity: 0;
      }
    }

    /* Pull to Refresh */
    .refresh-indicator {
      position: fixed;
      top: -60px;
      left: 50%;
      transform: translateX(-50%);
      padding: 10px 20px;
      background: rgba(5, 150, 105, 0.9);
      color: white;
      border-radius: 0 0 15px 15px;
      font-size: 14px;
      font-weight: 500;
      transition: top 0.3s ease;
      z-index: 1001;
      display: flex;
      align-items: center;
      gap: 10px;
    }

    /* MOBILE RESPONSIVE STYLES */

    /* Extra small devices (phones, 576px and down) */
    @media (max-width: 575.98px) {
      html {
        font-size: 14px;
      }

      .header-content {
        flex-direction: column;
        gap: 10px;
        padding: 0 15px;
        text-align: center;
        position: relative;
      }

      .header-content h1 {
        font-size: 1.3rem;
        text-align: center;
        margin-bottom: 5px;
      }

      .header-content h1 i {
        margin-right: 8px;
      }

      .header-content p {
        font-size: 0.85rem;
        justify-content: center;
      }

      .menu-container {
        position: absolute;
        right: 15px;
        top: 15px;
      }

      .menu-button {
        padding: 12px 15px;
        font-size: 18px;
        min-width: 50px;
        min-height: 48px;
      }

      .dropdown-menu {
        min-width: 240px;
        right: -20px;
        top: 110%;
        max-height: 60vh;
        overflow-y: auto;
        background: rgba(255, 255, 255, 0.98);
      }

      .dropdown-menu a {
        padding: 14px 20px;
        font-size: 14px;
      }

      .container {
        margin: 20px auto;
        padding: 0 15px;
      }

      .card {
        padding: 20px 15px;
        margin-bottom: 20px;
        border-radius: 15px;
      }

      .card h2 {
        font-size: 1.4rem;
        margin-bottom: 15px;
        text-align: center;
      }

      .search-container {
        grid-template-columns: 1fr;
        gap: 12px;
        margin-bottom: 20px;
      }

      .form-group input {
        padding: 12px 15px;
        font-size: 16px; /* Prevent zoom on iOS */
        border-radius: 10px;
      }

      .btn, button[type="submit"] {
        padding: 12px 20px;
        font-size: 14px;
        width: 100%;
        min-height: 48px;
        border-radius: 10px;
      }

      .attendance-list {
        padding: 15px;
      }

      .attendance-list h2 {
        font-size: 1.5rem;
        margin-bottom: 20px;
        text-align: center;
      }

      .table-container {
        border-radius: 12px;
      }

      /* Hide table, show cards on mobile */
      .attendance-table {
        display: none;
      }

      .mobile-attendance-cards {
        display: block;
      }

      .message {
        padding: 12px 18px;
        border-radius: 10px;
        margin-bottom: 15px;
        font-size: 14px;
      }

      .pagination {
        flex-wrap: wrap;
        gap: 10px;
        margin-top: 20px;
      }

      .pagination-link {
        padding: 10px 15px;
        font-size: 14px;
        border-radius: 8px;
        flex: 1;
        text-align: center;
        min-width: 100px;
      }

      .page-number {
        order: -1;
        width: 100%;
        text-align: center;
        margin-bottom: 10px;
        font-size: 14px;
      }

      .empty-state {
        padding: 30px 20px;
      }

      .empty-state p {
        font-size: 14px;
      }
    }

    /* Small devices (landscape phones, 576px and up) */
    @media (min-width: 576px) and (max-width: 767.98px) {
      .header-content h1 {
        font-size: 1.6rem;
      }

      .search-container {
        grid-template-columns: 1fr 1fr;
        gap: 15px;
      }

      .search-container .btn {
        grid-column: 1 / -1;
      }

      .attendance-table {
        font-size: 13px;
      }

      .attendance-table th,
      .attendance-table td {
        padding: 12px 8px;
      }

      .card {
        padding: 25px 20px;
      }

      /* Still show mobile cards */
      .attendance-table {
        display: none;
      }

      .mobile-attendance-cards {
        display: block;
      }
    }

    /* Medium devices (tablets, 768px and up) */
    @media (min-width: 768px) and (max-width: 991.98px) {
      .header-content {
        flex-direction: row;
      }

      .header-content h1 {
        font-size: 1.8rem;
      }

      .search-container {
        grid-template-columns: 1fr 1fr auto;
      }

      .attendance-table th,
      .attendance-table td {
        padding: 15px 12px;
      }

      .dropdown-menu {
        min-width: 260px;
      }

      /* Show table on tablets and up */
      .attendance-table {
        display: table;
      }

      .mobile-attendance-cards {
        display: none;
      }
    }

    /* Large devices (desktops, 992px and up) */
    @media (min-width: 992px) {
      .container {
        max-width: 1200px;
      }

      .header-content h1 {
        font-size: 2rem;
      }

      .attendance-table th,
      .attendance-table td {
        padding: 20px;
      }

      .dropdown-menu {
        min-width: 280px;
      }
    }

    /* Touch device optimizations */
    @media (hover: none) and (pointer: coarse) {
      .btn, button[type="submit"], .pagination-link {
        min-height: 48px;
        padding: 14px 20px;
      }

      .menu-button {
        min-width: 48px;
        min-height: 48px;
      }

      .dropdown-menu a {
        padding: 16px 24px;
        min-height: 48px;
      }

      .form-group input {
        min-height: 48px;
      }

      .attendance-table tbody tr:hover {
        transform: none;
        background: transparent;
      }

      .btn:hover,
      button[type="submit"]:hover {
        transform: none;
      }

      /* Disable hover effects on touch devices */
      .menu-container:hover .dropdown-menu {
        opacity: 0;
        visibility: hidden;
        transform: translateY(-15px) scale(0.95);
      }
    }

    /* Accessibility improvements for mobile */
    @media (max-width: 767.98px) {
      .form-group input:focus,
      .btn:focus,
      button[type="submit"]:focus {
        outline: 3px solid rgba(34, 197, 94, 0.5);
        outline-offset: 2px;
      }

      .attendance-card-value {
        font-size: 1rem;
        font-weight: 500;
      }

      .attendance-card-label {
        font-size: 0.9rem;
      }

      .attendance-card {
        margin-bottom: 20px;
      }

      .attendance-card-row {
        padding-bottom: 12px;
        margin-bottom: 12px;
      }
    }

    /* Landscape phone adjustments */
    @media (max-width: 767.98px) and (orientation: landscape) {
      .header-container {
        padding: 15px 0;
      }

      .header-content h1 {
        font-size: 1.4rem;
      }

      .header-content p {
        font-size: 0.8rem;
      }

      .card {
        padding: 20px;
      }

      .attendance-list {
        padding: 15px 10px;
      }
    }

    /* High DPI displays */
    @media (-webkit-min-device-pixel-ratio: 2), (min-resolution: 192dpi) {
      .btn, button[type="submit"] {
        border-width: 0.5px;
      }

      .dropdown-menu {
        border-width: 0.5px;
      }

      .card {
        border-width: 0.5px;
      }
    }

    /* Print styles */
    @media print {
      .header-container,
      .menu-container,
      .bg-particles,
      .btn,
      button[type="submit"],
      .pagination,
      form {
        display: none !important;
      }

      body {
        background: white !important;
        color: black !important;
      }

      .attendance-table {
        color: black !important;
        background: white !important;
      }

      .attendance-table th {
        background: #f5f5f5 !important;
        color: black !important;
        border: 1px solid #ddd !important;
      }

      .attendance-table td {
        border: 1px solid #ddd !important;
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

    <?php if ($formVisible && isset($_SESSION['student_id'])): ?>
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
    <?php endif; ?>

    <!-- Include the dashboard below the Submit Attendance section -->
    <div style='margin-top: 40px'>
      <?php 
      if (file_exists('dashboard.php')) {
          include 'dashboard.php'; 
      }
      ?>
    </div>
  </div>

  <div class="attendance-list">
    <h2><i class="fas fa-list-check"></i> Today's Attendance</h2>
    
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
  <script>
    // FIXED: Enhanced Menu Toggle System
    class DropdownMenu {
      constructor() {
        this.menuButton = document.getElementById('menuButton');
        this.menuContainer = document.getElementById('menuContainer');
        this.dropdownMenu = document.getElementById('dropdownMenu');
        this.menuOverlay = document.getElementById('menuOverlay');
        this.isOpen = false;
        this.isMobile = window.innerWidth <= 767.98;
        
        this.init();
      }
      
      init() {
        // Remove any existing event listeners
        this.cleanup();
        
        // Add click event for menu button
        this.menuButton.addEventListener('click', this.toggleMenu.bind(this));
        
        // Add overlay click for mobile
        this.menuOverlay.addEventListener('click', this.closeMenu.bind(this));
        
        // Add document click to close menu when clicking outside
        document.addEventListener('click', this.handleDocumentClick.bind(this));
        
        // Add keyboard support
        this.menuButton.addEventListener('keydown', this.handleKeydown.bind(this));
        this.dropdownMenu.addEventListener('keydown', this.handleMenuKeydown.bind(this));
        
        // Add resize listener to detect mobile/desktop switches
        window.addEventListener('resize', this.handleResize.bind(this));
        
        // Add menu item clicks
        this.dropdownMenu.querySelectorAll('a').forEach(link => {
          link.addEventListener('click', () => {
            if (this.isMobile) {
              this.closeMenu();
            }
          });
        });
      }
      
      cleanup() {
        // Remove existing listeners (if any)
        this.menuButton.removeEventListener('click', this.toggleMenu);
        this.menuOverlay.removeEventListener('click', this.closeMenu);
        document.removeEventListener('click', this.handleDocumentClick);
      }
      
      toggleMenu(event) {
        event.stopPropagation();
        event.preventDefault();
        
        if (this.isOpen) {
          this.closeMenu();
        } else {
          this.openMenu();
        }
      }
      
      openMenu() {
        this.isOpen = true;
        this.menuContainer.classList.add('active');
        this.menuButton.setAttribute('aria-expanded', 'true');
        
        // Add active state styles
        this.dropdownMenu.style.opacity = '1';
        this.dropdownMenu.style.visibility = 'visible';
        this.dropdownMenu.style.transform = 'translateY(0) scale(1)';
        
        if (this.isMobile) {
          this.menuOverlay.classList.add('active');
          document.body.style.overflow = 'hidden';
        }
        
        // Focus first menu item for keyboard navigation
        const firstMenuItem = this.dropdownMenu.querySelector('a');
        if (firstMenuItem) {
          setTimeout(() => firstMenuItem.focus(), 100);
        }
        
        // Add haptic feedback if available
        if (navigator.vibrate) {
          navigator.vibrate(50);
        }
      }
      
      closeMenu() {
        this.isOpen = false;
        this.menuContainer.classList.remove('active');
        this.menuButton.setAttribute('aria-expanded', 'false');
        
        // Remove active state styles
        this.dropdownMenu.style.opacity = '0';
        this.dropdownMenu.style.visibility = 'hidden';
        this.dropdownMenu.style.transform = 'translateY(-15px) scale(0.95)';
        
        if (this.isMobile) {
          this.menuOverlay.classList.remove('active');
          document.body.style.overflow = '';
        }
      }
      
      handleDocumentClick(event) {
        // Close menu if clicking outside
        if (!this.menuContainer.contains(event.target)) {
          this.closeMenu();
        }
      }
      
      handleKeydown(event) {
        if (event.key === 'Enter' || event.key === ' ') {
          event.preventDefault();
          this.toggleMenu(event);
        } else if (event.key === 'ArrowDown') {
          event.preventDefault();
          this.openMenu();
        }
      }
      
      handleMenuKeydown(event) {
        const menuItems = Array.from(this.dropdownMenu.querySelectorAll('a'));
        const currentIndex = menuItems.indexOf(document.activeElement);
        
        switch (event.key) {
          case 'Escape':
            event.preventDefault();
            this.closeMenu();
            this.menuButton.focus();
            break;
          case 'ArrowUp':
            event.preventDefault();
            const prevIndex = currentIndex > 0 ? currentIndex - 1 : menuItems.length - 1;
            menuItems[prevIndex].focus();
            break;
          case 'ArrowDown':
            event.preventDefault();
            const nextIndex = currentIndex < menuItems.length - 1 ? currentIndex + 1 : 0;
            menuItems[nextIndex].focus();
            break;
          case 'Home':
            event.preventDefault();
            menuItems[0].focus();
            break;
          case 'End':
            event.preventDefault();
            menuItems[menuItems.length - 1].focus();
            break;
        }
      }
      
      handleResize() {
        const wasMobile = this.isMobile;
        this.isMobile = window.innerWidth <= 767.98;
        
        // If switching from mobile to desktop or vice versa, close menu
        if (wasMobile !== this.isMobile && this.isOpen) {
          this.closeMenu();
        }
      }
    }

    // Mobile Responsive JavaScript Functions
    function createMobileCards() {
      const table = document.querySelector('.attendance-table');
      const mobileContainer = document.querySelector('.mobile-attendance-cards');
      
      if (!table || !mobileContainer) return;

      // Clear existing mobile cards
      mobileContainer.innerHTML = '';

      // Get table data
      const rows = table.querySelectorAll('tbody tr');
      
      if (rows.length === 0 || (rows.length === 1 && rows[0].querySelector('td[colspan]'))) {
        // Handle empty state
        mobileContainer.innerHTML = `
          <div class="empty-state">
            <i class="fas fa-info-circle"></i>
            <p>No attendance records found.</p>
          </div>
        `;
        return;
      }

      // Create cards from table data
      rows.forEach((row, index) => {
        const cells = row.querySelectorAll('td');
        if (cells.length >= 4 && !cells[0].hasAttribute('colspan')) {
          const card = document.createElement('div');
          card.className = 'attendance-card';
          card.style.animationDelay = `${index * 0.1}s`;
          
          card.innerHTML = `
            <div class="attendance-card-row">
              <span class="attendance-card-label">
                <i class="fas fa-hashtag"></i> Serial No
              </span>
              <span class="attendance-card-value">${cells[0].textContent.trim()}</span>
            </div>
            <div class="attendance-card-row">
              <span class="attendance-card-label">
                <i class="fas fa-id-card"></i> Student ID
              </span>
              <span class="attendance-card-value">${cells[1].textContent.trim()}</span>
            </div>
            <div class="attendance-card-row">
              <span class="attendance-card-label">
                <i class="fas fa-user"></i> Student Name
              </span>
              <span class="attendance-card-value">${cells[2].textContent.trim()}</span>
            </div>
            <div class="attendance-card-row">
              <span class="attendance-card-label">
                <i class="fas fa-calendar-day"></i> Date
              </span>
              <span class="attendance-card-value">${cells[3].textContent.trim()}</span>
            </div>
          `;
          
          mobileContainer.appendChild(card);
        }
      });
    }

    // Function to handle responsive layout changes
    function handleResponsiveLayout() {
      const isMobile = window.innerWidth <= 767.98;
      
      if (isMobile) {
        createMobileCards();
      }
    }

    // Function to optimize touch interactions
    function optimizeTouchInteractions() {
      // Add touch-friendly scrolling for dropdowns on mobile
      const dropdown = document.querySelector('.dropdown-menu');
      if (dropdown && 'ontouchstart' in window) {
        dropdown.style.overscrollBehavior = 'contain';
        dropdown.style.webkitOverflowScrolling = 'touch';
      }

      // Prevent zoom on input focus for iOS
      const inputs = document.querySelectorAll('input[type="text"], input[type="date"]');
      inputs.forEach(input => {
        input.addEventListener('focus', function() {
          if (/iPhone|iPad|iPod/.test(navigator.userAgent)) {
            input.style.fontSize = '16px';
          }
        });
      });

      // Add haptic feedback for supported devices
      if ('vibrate' in navigator) {
        const buttons = document.querySelectorAll('.btn, button[type="submit"]');
        buttons.forEach(button => {
          button.addEventListener('click', function() {
            navigator.vibrate(50); // Short vibration
          });
        });
      }
    }

    // Function to setup form validation with mobile-friendly alerts
    function setupMobileFormValidation() {
      const searchForm = document.querySelector('form[method="post"]');
      if (!searchForm) return;

      searchForm.addEventListener('submit', function(e) {
        const studentId = document.getElementById('search_student_id');
        const searchDate = document.getElementById('search_date');
        
        if (studentId && searchDate) {
          const studentIdValue = studentId.value.trim();
          const searchDateValue = searchDate.value.trim();
          
          if (!studentIdValue && !searchDateValue) {
            e.preventDefault();
            
            // Mobile-friendly alert
            if (window.innerWidth <= 767.98) {
              // Simple alert for better mobile UX
              alert('Please enter either a Student ID or select a date to search.');
              studentId.focus();
            } else {
              // SweetAlert for desktop
              if (typeof Swal !== 'undefined') {
                Swal.fire({
                  icon: 'warning',
                  title: 'Search Criteria Required',
                  text: 'Please enter either a Student ID or select a date to search.',
                  confirmButtonColor: '#22c55e',
                  background: 'rgba(15, 23, 42, 0.95)',
                  color: '#ffffff'
                });
              }
            }
          }
        }
      });
    }

    // Function to add pull-to-refresh functionality
    function addPullToRefresh() {
      let startY = 0;
      let currentY = 0;
      let isRefreshing = false;
      
      const refreshThreshold = 80;
      const body = document.body;
      
      // Create refresh indicator
      let refreshIndicator = document.querySelector('.refresh-indicator');
      if (!refreshIndicator) {
        refreshIndicator = document.createElement('div');
        refreshIndicator.className = 'refresh-indicator';
        refreshIndicator.innerHTML = '<i class="fas fa-sync-alt"></i> Release to refresh';
        body.appendChild(refreshIndicator);
      }
      
      // Touch events for pull-to-refresh
      body.addEventListener('touchstart', function(e) {
        if (window.pageYOffset === 0) {
          startY = e.touches[0].clientY;
        }
      }, { passive: true });
      
      body.addEventListener('touchmove', function(e) {
        if (window.pageYOffset === 0 && !isRefreshing) {
          currentY = e.touches[0].clientY;
          const pullDistance = currentY - startY;
          
          if (pullDistance > 0 && pullDistance < refreshThreshold * 2) {
            const progress = Math.min(pullDistance / refreshThreshold, 1);
            refreshIndicator.style.top = `${-60 + (60 * progress)}px`;
            
            if (pullDistance >= refreshThreshold) {
              refreshIndicator.innerHTML = '<i class="fas fa-sync-alt"></i> Release to refresh';
              refreshIndicator.style.background = 'rgba(34, 197, 94, 0.9)';
            } else {
              refreshIndicator.innerHTML = '<i class="fas fa-arrow-down"></i> Pull to refresh';
              refreshIndicator.style.background = 'rgba(255, 255, 255, 0.2)';
            }
          }
        }
      }, { passive: true });
      
      body.addEventListener('touchend', function() {
        if (window.pageYOffset === 0 && !isRefreshing) {
          const pullDistance = currentY - startY;
          
          if (pullDistance >= refreshThreshold) {
            isRefreshing = true;
            refreshIndicator.style.top = '0px';
            refreshIndicator.innerHTML = '<i class="fas fa-sync-alt fa-spin"></i> Refreshing...';
            refreshIndicator.style.background = 'rgba(34, 197, 94, 0.9)';
            
            // Simulate refresh (reload page)
            setTimeout(() => {
              window.location.reload();
            }, 1000);
          } else {
            refreshIndicator.style.top = '-60px';
          }
        }
        
        startY = 0;
        currentY = 0;
      }, { passive: true });
    }

    // Access denied function with modern styling
    function showAccessDenied() {
      Swal.fire({
        icon: 'error',
        title: 'Access Denied',
        text: 'Admins Only!!',
        confirmButtonColor: '#22c55e',
        confirmButtonText: 'OK',
        background: 'rgba(15, 23, 42, 0.95)',
        backdrop: 'rgba(0, 0, 0, 0.8)',
        color: '#ffffff',
        customClass: {
          popup: 'swal-custom-popup'
        }
      });
    }

    // Add more floating particles dynamically
    function createParticles() {
      const particleContainer = document.querySelector('.bg-particles');
      
      const createParticle = () => {
        const particle = document.createElement('div');
        particle.className = 'particle';
        particle.style.left = Math.random() * 100 + '%';
        particle.style.animationDuration = (Math.random() * 10 + 15) + 's';
        particle.style.animationDelay = '0s';
        
        particleContainer.appendChild(particle);
        
        setTimeout(() => {
          if (particle.parentNode) {
            particle.remove();
          }
        }, 25000);
      };

      // Create particles at intervals
      setInterval(createParticle, 3000);
    }

    // Add ripple effect to buttons
    function createRipple(event) {
      const button = event.currentTarget;
      const circle = document.createElement('span');
      const diameter = Math.max(button.clientWidth, button.clientHeight);
      const radius = diameter / 2;

      circle.style.width = circle.style.height = `${diameter}px`;
      circle.style.left = `${event.clientX - button.offsetLeft - radius}px`;
      circle.style.top = `${event.clientY - button.offsetTop - radius}px`;
      circle.classList.add('ripple');

      const ripple = button.getElementsByClassName('ripple')[0];
      if (ripple) {
        ripple.remove();
      }

      button.appendChild(circle);
    }

    // Performance optimization: debounce function
    function debounce(func, wait) {
      let timeout;
      return function executedFunction(...args) {
        const later = () => {
          clearTimeout(timeout);
          func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
      };
    }

    // Main initialization function
    function initializeMobileResponsive() {
      // Initialize the dropdown menu system
      new DropdownMenu();
      
      // Initial setup
      handleResponsiveLayout();
      optimizeTouchInteractions();
      setupMobileFormValidation();
      
      // Add pull-to-refresh only on mobile devices
      if ('ontouchstart' in window && window.innerWidth <= 767.98) {
        addPullToRefresh();
      }
      
      // Handle resize events with debouncing
      const optimizedResize = debounce(() => {
        handleResponsiveLayout();
      }, 250);
      window.addEventListener('resize', optimizedResize);
      
      // Handle orientation change
      window.addEventListener('orientationchange', function() {
        setTimeout(function() {
          handleResponsiveLayout();
          // Force layout recalculation
          document.body.style.display = 'none';
          document.body.offsetHeight; // Trigger reflow
          document.body.style.display = '';
        }, 300);
      });

      