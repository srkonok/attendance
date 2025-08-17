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

// Fetch students for the current page with sorting and search
$studentsQuery = "SELECT * FROM students 
    WHERE name LIKE :search OR 
          student_id LIKE :search OR 
          section LIKE :search
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
  <title>Students List - Cloud Computing</title>
  <link rel="icon" href="images/favicon_io/favicon.ico" type="image/x-icon">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
  <link rel="stylesheet" href="index.css">
  <style>
    /* Additional specific styles for students list */
    .students-table {
      width: 100%;
      border-collapse: collapse;
      min-width: 800px;
    }

    .students-table th {
      background: linear-gradient(135deg, #059669, #10b981);
      color: white;
      padding: 20px;
      text-align: left;
      font-weight: 600;
      border: none;
      position: sticky;
      top: 0;
      z-index: 10;
    }

    .students-table th:first-child {
      border-radius: 20px 0 0 0;
    }

    .students-table th:last-child {
      border-radius: 0 20px 0 0;
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

    .students-table tbody tr:last-child td:first-child {
      border-radius: 0 0 0 20px;
    }

    .students-table tbody tr:last-child td:last-child {
      border-radius: 0 0 20px 0;
    }

    .sortable {
      color: white;
      text-decoration: none;
      display: flex;
      align-items: center;
      gap: 0.5rem;
      transition: opacity 0.3s ease;
    }

    .sortable:hover {
      opacity: 0.8;
    }

    .arrow {
      font-size: 0.8rem;
    }

    .no-data {
      text-align: center;
      color: #6b7280;
      font-style: italic;
      padding: 3rem !important;
      font-size: 1.1rem;
    }

    .email-link, .phone-link {
      color: #059669;
      text-decoration: none;
      font-weight: 500;
      transition: color 0.3s ease;
    }

    .email-link:hover, .phone-link:hover {
      color: #10b981;
      text-decoration: underline;
    }

    /* Stats Bar */
    .stats-bar {
      background: rgba(255, 255, 255, 0.95);
      backdrop-filter: blur(20px);
      border: 2px solid rgba(5, 150, 105, 0.2);
      border-radius: 20px;
      padding: 20px;
      margin-bottom: 30px;
      box-shadow: 0 20px 40px rgba(5, 150, 105, 0.15);
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 20px;
    }

    .stat-item {
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 8px;
      padding: 15px;
      border-radius: 15px;
      background: rgba(5, 150, 105, 0.05);
      transition: all 0.3s ease;
    }

    .stat-item:hover {
      background: rgba(5, 150, 105, 0.1);
      transform: translateY(-2px);
    }

    .stat-value {
      font-size: 2.5rem;
      font-weight: 700;
      color: #059669;
      line-height: 1;
    }

    .stat-label {
      font-size: 0.9rem;
      color: #6b7280;
      font-weight: 500;
      text-align: center;
    }

    /* Mobile Students Cards */
    .mobile-students-cards {
      display: none;
    }

    .student-card {
      background: rgba(255, 255, 255, 0.95);
      backdrop-filter: blur(15px);
      border: 2px solid rgba(5, 150, 105, 0.2);
      border-radius: 15px;
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
      margin-bottom: 12px;
      padding-bottom: 10px;
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

    /* Responsive styles for students table */
    @media (max-width: 767.98px) {
      .students-table {
        display: none;
      }

      .mobile-students-cards {
        display: block;
      }

      .stats-bar {
        grid-template-columns: repeat(2, 1fr);
        gap: 15px;
        padding: 15px;
        border-radius: 15px;
      }

      .stat-item {
        padding: 12px;
      }

      .stat-value {
        font-size: 2rem;
      }

      .stat-label {
        font-size: 0.8rem;
      }
    }

    @media (min-width: 576px) and (max-width: 767.98px) {
      .students-table {
        display: none;
      }

      .mobile-students-cards {
        display: block;
      }

      .stats-bar {
        grid-template-columns: repeat(2, 1fr);
      }
    }

    @media (min-width: 768px) {
      .students-table {
        display: table;
      }

      .mobile-students-cards {
        display: none;
      }
    }

    @media (min-width: 768px) and (max-width: 991.98px) {
      .students-table th,
      .students-table td {
        padding: 15px 12px;
      }
    }

    @media (hover: none) and (pointer: coarse) {
      .students-table tbody tr:hover {
        transform: none;
        background: transparent;
      }
    }

    /* Print styles */
    @media print {
      .students-table {
        color: black !important;
        background: white !important;
      }

      .students-table th {
        background: #f5f5f5 !important;
        color: black !important;
        border: 1px solid #ddd !important;
      }

      .students-table td {
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
        <h1><i class="fas fa-users"></i> Students Directory</h1>
        <p><i class="fas fa-graduation-cap"></i> Cloud Computing Course Management</p>
      </div>
      <div class="menu-container" id="menuContainer">
        <button class="menu-button" id="menuButton" aria-label="Menu" aria-haspopup="true" aria-expanded="false">
          <i class="fas fa-bars"></i>
        </button>
        <div class="dropdown-menu" id="dropdownMenu" role="menu">
          <a href="index.php" role="menuitem"><i class="fas fa-home"></i> Home</a>
          <a href="profile.php" role="menuitem"><i class="fas fa-user"></i> My Profile</a>
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
    <!-- Search Section -->
    <div class="card">
      <h2><i class="fas fa-search"></i> Search Students</h2>
      <div class="search-container">
        <div class="form-group">
          <input 
            type="text" 
            id="searchInput"
            value="<?= htmlspecialchars($searchQuery) ?>" 
            placeholder="Search by name, student ID, or section..."
          >
        </div>
        <button type="button" onclick="performSearch()" class="btn">
          <i class="fas fa-search"></i> Search
        </button>
      </div>
    </div>

    <!-- Stats Bar -->
    <div class="stats-bar">
      <div class="stat-item">
        <div class="stat-value"><?= $totalStudents ?></div>
        <div class="stat-label"><i class="fas fa-users"></i> Total Students</div>
      </div>
      <div class="stat-item">
        <div class="stat-value"><?= $page ?></div>
        <div class="stat-label"><i class="fas fa-file-alt"></i> Current Page</div>
      </div>
      <div class="stat-item">
        <div class="stat-value"><?= $totalPages ?></div>
        <div class="stat-label"><i class="fas fa-copy"></i> Total Pages</div>
      </div>
      <div class="stat-item">
        <div class="stat-value"><?= count($students) ?></div>
        <div class="stat-label"><i class="fas fa-eye"></i> Showing Results</div>
      </div>
    </div>

    <!-- Table Container -->
    <div class="card">
      <h2><i class="fas fa-table"></i> Students List</h2>
      <div class="table-container">
        <table class="students-table">
          <thead>
            <tr>
              <th><i class="fas fa-hashtag"></i> Serial No</th>
              <th><i class="fas fa-id-card"></i> Student ID</th>
              <th>
                <a href="?sort=section&order=<?= $newSortOrder ?>&search=<?= htmlspecialchars($searchQuery) ?>" class="sortable">
                  <i class="fas fa-layer-group"></i> Section
                  <span class="arrow">
                    <?= ($sortColumn === 'section' ? ($sortOrder === 'asc' ? '▲' : '▼') : '↕') ?>
                  </span>
                </a>
              </th>
              <th><i class="fas fa-user"></i> Name</th>
              <th><i class="fas fa-envelope"></i> Email</th>
              <th><i class="fas fa-phone"></i> Phone</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($students)): ?>
              <tr>
                <td colspan="6" class="no-data">
                  <i class="fas fa-info-circle" style="font-size: 2rem; margin-bottom: 10px; display: block;"></i>
                  No students found matching your search criteria.
                </td>
              </tr>
            <?php else: ?>
              <?php $count = $offset + 1; ?>
              <?php foreach ($students as $student): ?>
                <tr>
                  <td><strong><?= $count++ ?></strong></td>
                  <td><strong><?= htmlspecialchars($student['student_id'] ?? '') ?></strong></td>
                  <td><?= htmlspecialchars($student['section'] ?? '') ?></td>
                  <td><strong><?= htmlspecialchars($student['name'] ?? '') ?></strong></td>
                  <td>
                    <a href="mailto:<?= htmlspecialchars($student['email'] ?? '') ?>" class="email-link">
                      <?= htmlspecialchars($student['email'] ?? '') ?>
                    </a>
                  </td>
                  <td>
                    <?php 
                    $phoneNumber = isset($student['phone_number']) && !empty($student['phone_number']) 
                      ? '0' . ltrim(htmlspecialchars($student['phone_number']), '0') 
                      : 'N/A';
                    ?>
                    <?php if ($phoneNumber !== 'N/A'): ?>
                      <a href="https://wa.me/<?= ltrim($phoneNumber, '0') ?>" target="_blank" class="phone-link">
                        <?= $phoneNumber ?>
                      </a>
                    <?php else: ?>
                      <span style="color: #999;">N/A</span>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>

        <!-- Mobile Cards Container (will be populated by JavaScript) -->
        <div class="mobile-students-cards"></div>
      </div>
    </div>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
    <div class="pagination">
      <a href="?page=1&sort=<?= $sortColumn ?>&order=<?= $sortOrder ?>&search=<?= htmlspecialchars($searchQuery) ?>" class="pagination-link <?= $page === 1 ? 'disabled' : '' ?>">
        <i class="fas fa-angle-double-left"></i> First
      </a>
      <a href="?page=<?= max(1, $page - 1) ?>&sort=<?= $sortColumn ?>&order=<?= $sortOrder ?>&search=<?= htmlspecialchars($searchQuery) ?>" class="pagination-link <?= $page === 1 ? 'disabled' : '' ?>">
        <i class="fas fa-chevron-left"></i> Previous
      </a>
      
      <div class="page-number">Page <?= $page ?> of <?= $totalPages ?></div>
      
      <?php 
      $start = max(1, $page - 2);
      $end = min($totalPages, $page + 2);
      ?>
      
      <?php for ($i = $start; $i <= $end; $i++): ?>
        <a href="?page=<?= $i ?>&sort=<?= $sortColumn ?>&order=<?= $sortOrder ?>&search=<?= htmlspecialchars($searchQuery) ?>" class="pagination-link <?= $i === $page ? 'active' : '' ?>"><?= $i ?></a>
      <?php endfor; ?>
      
      <a href="?page=<?= min($totalPages, $page + 1) ?>&sort=<?= $sortColumn ?>&order=<?= $sortOrder ?>&search=<?= htmlspecialchars($searchQuery) ?>" class="pagination-link <?= $page === $totalPages ? 'disabled' : '' ?>">
        Next <i class="fas fa-chevron-right"></i>
      </a>
      <a href="?page=<?= $totalPages ?>&sort=<?= $sortColumn ?>&order=<?= $sortOrder ?>&search=<?= htmlspecialchars($searchQuery) ?>" class="pagination-link <?= $page === $totalPages ? 'disabled' : '' ?>">
        Last <i class="fas fa-angle-double-right"></i>
      </a>
    </div>
    <?php endif; ?>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script src="index.js"></script>
  <script>
    // Additional student-specific functions
    function createMobileStudentCards() {
      const table = document.querySelector('.students-table');
      const mobileContainer = document.querySelector('.mobile-students-cards');
      
      if (!table || !mobileContainer) return;

      mobileContainer.innerHTML = '';

      const rows = table.querySelectorAll('tbody tr');
      
      if (rows.length === 0 || (rows.length === 1 && rows[0].querySelector('td[colspan]'))) {
        mobileContainer.innerHTML = `
          <div class="empty-state">
            <i class="fas fa-info-circle"></i>
            <p>No students found matching your search criteria.</p>
          </div>
        `;
        return;
      }

      rows.forEach((row, index) => {
        const cells = row.querySelectorAll('td');
        if (cells.length >= 6 && !cells[0].hasAttribute('colspan')) {
          const card = document.createElement('div');
          card.className = 'student-card';
          card.style.animationDelay = `${index * 0.1}s`;
          
          card.innerHTML = `
            <div class="student-card-row">
              <span class="student-card-label">
                <i class="fas fa-hashtag"></i> Serial No
              </span>
              <span class="student-card-value">${cells[0].textContent.trim()}</span>
            </div>
            <div class="student-card-row">
              <span class="student-card-label">
                <i class="fas fa-id-card"></i> Student ID
              </span>
              <span class="student-card-value">${cells[1].textContent.trim()}</span>
            </div>
            <div class="student-card-row">
              <span class="student-card-label">
                <i class="fas fa-layer-group"></i> Section
              </span>
              <span class="student-card-value">${cells[2].textContent.trim()}</span>
            </div>
            <div class="student-card-row">
              <span class="student-card-label">
                <i class="fas fa-user"></i> Name
              </span>
              <span class="student-card-value">${cells[3].textContent.trim()}</span>
            </div>
            <div class="student-card-row">
              <span class="student-card-label">
                <i class="fas fa-envelope"></i> Email
              </span>
              <span class="student-card-value">${cells[4].innerHTML}</span>
            </div>
            <div class="student-card-row">
              <span class="student-card-label">
                <i class="fas fa-phone"></i> Phone
              </span>
              <span class="student-card-value">${cells[5].innerHTML}</span>
            </div>
          `;
          
          mobileContainer.appendChild(card);
        }
      });
    }

    // Search functionality
    function performSearch() {
      const searchValue = document.getElementById('searchInput').value;
      const currentUrl = new URL(window.location);
      currentUrl.searchParams.set('search', searchValue);
      currentUrl.searchParams.set('page', '1');
      window.location.href = currentUrl.toString();
    }

    // Override the mobile cards creation function from index.js
    window.createMobileCards = createMobileStudentCards;

    // Enable Enter key for search
    document.addEventListener('DOMContentLoaded', function() {
      document.getElementById('searchInput').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
          performSearch();
        }
      });
    });
  </script>
</body>
</html>