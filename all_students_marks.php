<?php
    include 'db.php';

    // Initialize variables to avoid undefined warnings
    $exportAll   = isset($_GET['export']) ? true : false;
    $searchQuery = isset($_GET['search']) ? trim($_GET['search']) : '';
    $page        = isset($_GET['page']) && is_numeric($_GET['page']) ? (int) $_GET['page'] : 1;
    $limit       = $exportAll ? null : 15; // Show all records for exports
    $offset      = ($page - 1) * ($limit ?? 0);

    // Get earliest attendance date
    $startDateFormatted = "Unknown";
    try {
        $startDateStmt = $conn->prepare("SELECT MIN(date) FROM attendance");
        $startDateStmt->execute();
        $startDate = $startDateStmt->fetchColumn();
        if ($startDate) {
            $startDateFormatted = date("d M Y", strtotime($startDate));
        }

    } catch (PDOException $e) {
        error_log("Error fetching start date: " . $e->getMessage());
    }

    // Total classes conducted
    $totalClasses = 0;
    try {
        $totalClassesStmt = $conn->prepare("SELECT COUNT(DISTINCT date) FROM attendance");
        $totalClassesStmt->execute();
        $totalClasses = (int) $totalClassesStmt->fetchColumn();
    } catch (PDOException $e) {
        error_log("Error fetching total classes: " . $e->getMessage());
    }

    // Fetch students data
    $students = [];
    try {
        $query = "SELECT s.*,
        COALESCE(a.attendance_count, 0) AS present_count,
        COALESCE(m.class_test_1, 0) AS class_test_1,
        COALESCE(m.class_test_2, 0) AS class_test_2,
        COALESCE(m.class_test_3, 0) AS class_test_3,
        COALESCE(m.assignment_1, 0) AS assignment_1,
        COALESCE(m.assignment_2, 0) AS assignment_2
    FROM students s
    LEFT JOIN (
        SELECT student_id, COUNT(*) AS attendance_count
        FROM attendance
        GROUP BY student_id
    ) a ON s.student_id = a.student_id
    LEFT JOIN student_marks m ON s.student_id = m.student_id
    WHERE s.name LIKE :search OR s.student_id LIKE :search OR s.section LIKE :search
    ORDER BY student_id ASC";

        if ($limit !== null) {
            $query .= " LIMIT :limit OFFSET :offset";
        }

        $stmt = $conn->prepare($query);
        $stmt->bindValue(':search', '%' . $searchQuery . '%');

        if ($limit !== null) {
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        }

        $stmt->execute();
        $students = $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Error fetching students: " . $e->getMessage());
    }

    // Pagination calculations
    $totalStudents = 0;
    if (! $exportAll) {
        try {
            $totalStmt = $conn->prepare("SELECT COUNT(*) FROM students WHERE
            name LIKE :search OR student_id LIKE :search OR section LIKE :search");
            $totalStmt->bindValue(':search', '%' . $searchQuery . '%');
            $totalStmt->execute();
            $totalStudents = $totalStmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("Error counting students: " . $e->getMessage());
        }
    }
    $totalPages = $limit ? ceil($totalStudents / $limit) : 1;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">


    <title>Cloud Computing Attendance</title>
    <link rel="stylesheet" href="style.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.25/jspdf.plugin.autotable.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.17.0/xlsx.full.min.js"></script>
</head>
<body>
<div class="table-container">
    <div class="students-list">
        <h2>Attendance from                            <?php echo htmlspecialchars($startDateFormatted) ?></h2>

        <!-- Search Form -->
        <div class="search-form">
            <form method="GET">
                <input type="text" name="search" value="<?php echo htmlspecialchars($searchQuery) ?>"
                    placeholder="Search students...">
                <button type="submit">Search</button>
            </form>
        </div>


        <!-- Data Table -->
        <div class="table-wrapper">
         <table class="students-table" id="exportTable">
            <thead>
                <tr>
                    <th>#</th><th>Student ID</th><th>Name</th><th>Section</th>
                    <th>Attendance (<?php echo $totalClasses ?>)</th><th>Percentage</th>
                    <th>Quiz 1</th><th>Quiz 2</th><th>Quiz 3</th>
                    <th>Assignment_1</th><th>Assignment_2</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($students as $index => $student):
                        $present    = (int) ($student['present_count'] ?? 0);
                        $percentage = $totalClasses > 0 ? round(($present / $totalClasses) * 100, 2) : 0;
                        $serial = ($page - 1) * $limit + $index + 1;  // Add this line

                    ?>
		                <tr>
		                    <td><?php echo $serial ?></td>
		                    <td><?php echo htmlspecialchars($student['student_id']) ?></td>
		                    <td><?php echo htmlspecialchars($student['name']) ?></td>
		                    <td><?php echo htmlspecialchars($student['section']) ?></td>
		                    <td><?php echo $present ?></td>
		                    <td><?php echo $percentage ?>%</td>
		                    <td><?php echo $student['class_test_1'] ?? 0 ?></td>
		                    <td><?php echo $student['class_test_2'] ?? 0 ?></td>
		                    <td><?php echo $student['class_test_3'] ?? 0 ?></td>
		                    <td><?php echo $student['assignment_1'] ?? 0 ?></td>
		                    <td><?php echo $student['assignment_2'] ?? 0 ?></td>
		                </tr>
		                <?php endforeach; ?>
            </tbody>
        </table>
        </div>

        <!-- Pagination -->
        <?php if (! $exportAll && $totalPages > 1): ?>
        <div class="pagination">
            <?php if ($page > 1): ?>
                <a href="?page=1&search=<?php echo urlencode($searchQuery) ?>">First</a>
                <a href="?page=<?php echo $page - 1 ?>&search=<?php echo urlencode($searchQuery) ?>">Previous</a>
            <?php endif; ?>

            <span>Page                       <?php echo $page ?> of <?php echo $totalPages ?></span>

            <?php if ($page < $totalPages): ?>
                <a href="?page=<?php echo $page + 1 ?>&search=<?php echo urlencode($searchQuery) ?>">Next</a>
                <a href="?page=<?php echo $totalPages ?>&search=<?php echo urlencode($searchQuery) ?>">Last</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- Export Controls -->
        <div class="export-controls" style="display: flex; justify-content: flex-end; margin-top: 1.5rem;">
            <label for="exportFormat" style="margin-right: 10px; margin-top: 15px;">Export as:</label>
            <select id="exportFormat" onchange="handleExport(this.value)">
            <option value="">Select Format</option>
            <option value="pdf">PDF</option>
            <option value="csv">CSV</option>
            <option value="excel">Excel</option>
            </select>
        </div>
        <!-- </div> -->
    </div>

    <script>
    // Export Handler
    function handleExport(type) {
        const url = new URL(window.location.href);
        url.searchParams.set('export', 'true');

        // Trigger page reload with export param
        if (type === 'pdf') {
            url.searchParams.set('exportType', 'pdf');
            window.location.href = url.toString();
        } else if (type === 'csv') {
            exportToCSV();
        } else if (type === 'excel') {
            exportToExcel();
        }
    }

    // Auto-trigger PDF export after page reload
    window.addEventListener('load', () => {
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('exportType') === 'pdf') {
            exportToPDF();
            // Clean URL after export
            history.replaceState({}, '', window.location.pathname);
        }
    });

    // PDF Export
    function exportToPDF() {
        const { jsPDF } = window.jspdf;
        const doc = new jsPDF('landscape');

        doc.autoTable({
            html: '#exportTable',
            theme: 'grid',
            headStyles: { fillColor: [41, 128, 185], textColor: 255 },
            styles: { fontSize: 9 },
            margin: { top: 20 }
        });

        doc.save('student-report.pdf');
    }

    // CSV Export
    function exportToCSV() {
        const rows = Array.from(document.querySelectorAll('#exportTable tr'));
        const csvContent = rows.map(row =>
            Array.from(row.querySelectorAll('th, td')).map(cell =>
                `"${cell.innerText.replace(/"/g, '""')}"`
            ).join(',')
        ).join('\n');

        const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = 'student-report.csv';
        link.click();
    }

    // Excel Export
    function exportToExcel() {
        const wb = XLSX.utils.table_to_book(document.getElementById('exportTable'));
        XLSX.writeFile(wb, 'student-report.xlsx');
    }
    </script>
</body>

</html>
<style>
    /* Base container styling */
    .table-container {
        max-width: 100%;
        margin: 20px 15px;
        padding: 0 15px;
        box-sizing: border-box;
    }

    /* Table wrapper for scrolling */
    .table-wrapper {
        overflow-x: auto;
        background: white;
        border: 1px solid #e0e0e0;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        -webkit-overflow-scrolling: touch;
    }

    /* Table styling */
    .students-table {
        width: 100%;
        min-width: 1000px;
        border-collapse: collapse;
        background: white;
    }

    .students-table th,
    .students-table td {
        padding: 12px 15px;
        text-align: left;
        border-bottom: 1px solid #e0e0e0;
        white-space: nowrap;
        font-size: 0.95rem;
        line-height: 1.5;
    }

    .students-table th {
        background-color: #f8f9fa;
        font-weight: 600;
        color: #2c3e50;
        position: sticky;
        top: 0;
        z-index: 2;
    }

    .students-table tr:hover {
        background-color: #fafafa;
    }

    /* Header styling */
    .students-list h2 {
        color: #2c3e50;
        margin: 0 0 1.5rem 0;
        font-size: 1.5rem;
        font-weight: 600;
    }

    /* Search form styling */
    .search-form {
        margin-bottom: 1.5rem;
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
    }

    .search-form input[type="text"] {
        flex: 1 1 300px;
        padding: 0.75rem;
        border: 1px solid #e0e0e0;
        border-radius: 6px;
        font-size: 1rem;
    }

    .search-form button {
        padding: 0.75rem 1.5rem;
        background: #4CAF50;
        color: white;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        transition: background 0.2s ease;
    }

    .search-form button:hover {
        background: #388E3C;
    }

    /* Export controls */
    select {
        width: 100%;
        max-width: 200px;
        padding: 0.75rem;
        margin-bottom: 1.5rem;
        border: 1px solid #e0e0e0;
        border-radius: 6px;
        background: white;
        font-size: 1rem;
    }

    /* Pagination styling */
    .pagination {
        margin-top: 1.5rem;
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
        justify-content: center;
    }

    .pagination a {
        padding: 0.6rem 1rem;
        background: #f8f9fa;
        border: 1px solid #e0e0e0;
        border-radius: 6px;
        color: #4CAF50;
        text-decoration: none;
        transition: all 0.2s ease;
    }

    .pagination a:hover {
        background: #4CAF50;
        color: white;
        border-color: #4CAF50;
    }

    .pagination span {
        padding: 0.6rem 1rem;
        color: #757575;
    }

    /* Responsive design */
    @media (max-width: 768px) {
        .table-container {
            margin: 15px 10px;
            padding: 0 10px;
        }

        .students-table th,
        .students-table td {
            padding: 10px 12px;
            font-size: 0.9rem;
        }

        .search-form input[type="text"] {
            flex: 1 1 100%;
        }

        .search-form button {
            width: 100%;
        }

        select {
            max-width: 100%;
        }
    }
</style>