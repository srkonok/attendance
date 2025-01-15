<?php
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cloud Computing Attendance</title>
    <link rel="stylesheet" href="style.css">

</head>
<body>
    <div class="students-list">
        <h2>All Students</h2>

        <!-- Search Bar -->
        <div class="search-form">
            <form method="GET" action="">
                <input 
                    type="text" 
                    name="search" 
                    value="<?= htmlspecialchars($searchQuery) ?>" 
                    placeholder="Search by name, ID, or section"
                >
                <button type="submit">Search</button>
            </form>
        </div>



        <table class="students-table">
            <thead>
                <tr>
                    <th>Serial No</th>
                    <th>Student ID</th>
                    <th>
                        <a href="?sort=section&order=<?= $newSortOrder ?>&search=<?= htmlspecialchars($searchQuery) ?>" class="sortable">
                            <span class="section-title">Section</span>
                            <span class="arrow">
                                <?= ($sortColumn === 'section' ? ($sortOrder === 'asc' ? '▲' : '▼') : '') ?>
                            </span>
                        </a>
                    </th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($students)): ?>
                    <tr>
                        <td colspan="6" class="no-data">No students found.</td>
                    </tr>
                <?php else: ?>
                    <?php $count = $offset + 1; ?>
                    <?php foreach ($students as $student): ?>
                        <tr>
                            <td><?= $count++ ?></td>
                            <td><?= htmlspecialchars($student['student_id'] ?? '') ?></td>
                            <td><?= htmlspecialchars($student['section'] ?? '') ?></td>
                            <td><?= htmlspecialchars($student['name'] ?? '') ?></td>
                            <td>
                                <a href="mailto:<?= htmlspecialchars($student['email'] ?? '') ?>">
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
                                    <a href="https://wa.me/<?= ltrim($phoneNumber, '0') ?>" target="_blank">
                                        <?= $phoneNumber ?>
                                    </a>
                                <?php else: ?>
                                    N/A
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- Pagination -->
        <div class="pagination">
            <a href="?page=1&sort=<?= $sortColumn ?>&order=<?= $sortOrder ?>&search=<?= htmlspecialchars($searchQuery) ?>" class="<?= $page === 1 ? 'disabled' : '' ?>">First</a>
            <a href="?page=<?= max(1, $page - 1) ?>&sort=<?= $sortColumn ?>&order=<?= $sortOrder ?>&search=<?= htmlspecialchars($searchQuery) ?>" class="<?= $page === 1 ? 'disabled' : '' ?>">Previous</a>
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <a href="?page=<?= $i ?>&sort=<?= $sortColumn ?>&order=<?= $sortOrder ?>&search=<?= htmlspecialchars($searchQuery) ?>" class="<?= $i === $page ? 'active' : '' ?>"><?= $i ?></a>
            <?php endfor; ?>
            <a href="?page=<?= min($totalPages, $page + 1) ?>&sort=<?= $sortColumn ?>&order=<?= $sortOrder ?>&search=<?= htmlspecialchars($searchQuery) ?>" class="<?= $page === $totalPages ? 'disabled' : '' ?>">Next</a>
            <a href="?page=<?= $totalPages ?>&sort=<?= $sortColumn ?>&order=<?= $sortOrder ?>&search=<?= htmlspecialchars($searchQuery) ?>" class="<?= $page === $totalPages ? 'disabled' : '' ?>">Last</a>
        </div>
    </div>
</body>
</html>
