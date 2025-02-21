<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cloud Computing Attendance</title>
    <link rel="stylesheet" href="style.css">
    <!-- Include jsPDF and jspdf-autotable -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.25/jspdf.plugin.autotable.min.js"></script>
</head>
<body>
<div class="table-container">
    <div class="students-list">
        <h2>Attendance from <?= htmlspecialchars($startDateFormatted) ?></h2>
        <div class="search-form">
            <form method="GET" action="">
                <input type="text" name="search" value="<?= htmlspecialchars($searchQuery) ?>" placeholder="Search by name, ID, or section">
                <button type="submit">Search</button>
            </form>
        </div>

        <div class="export-dropdown">
            <select onchange="handleExport(this.value)">
                <option value="">Export Options</option>
                <option value="csv">Export as CSV</option>
                <option value="excel">Export as Excel</option>
                <option value="pdf">Export as PDF</option>
            </select>
        </div>

        <div class="table-wrapper">
            <table class="students-table" id="studentsTable">
                <thead>
                    <tr>
                        <th>Serial No</th>
                        <th>Student ID</th>
                        <th>Section</th>
                        <th>Name</th>
                        <th>Total Attendance</th>
                        <th>Present (%)</th>
                        <th>Class Test 1</th>
                        <th>Class Test 2</th>
                        <th>Class Test 3</th>
                        <th>Assignment 1</th>
                        <th>Assignment 2</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($students)): ?>
                        <tr>
                            <td colspan="11" class="no-data">No students found.</td>
                        </tr>
                    <?php else: ?>
                        <?php $count = 1; ?>
                        <?php foreach ($students as $student): ?>
                            <?php
                            $presentCount = (int) $student['present_count'];
                            $attendancePercentage = ($totalClasses > 0) ? round(($presentCount / $totalClasses) * 100, 2) : 0;
                            ?>
                            <tr>
                                <td><?= $count++ ?></td>
                                <td><?= htmlspecialchars($student['student_id'] ?? '') ?></td>
                                <td><?= htmlspecialchars($student['section'] ?? '') ?></td>
                                <td><?= htmlspecialchars($student['name'] ?? '') ?></td>
                                <td><?= $presentCount ?></td>
                                <td><?= $attendancePercentage ?>%</td>
                                <td><?= htmlspecialchars($student['class_test_1'] ?? '0') ?></td>
                                <td><?= htmlspecialchars($student['class_test_2'] ?? '0') ?></td>
                                <td><?= htmlspecialchars($student['class_test_3'] ?? '0') ?></td>
                                <td><?= htmlspecialchars($student['assignment_1'] ?? '0') ?></td>
                                <td><?= htmlspecialchars($student['assignment_2'] ?? '0') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if (!$exportAll): ?>
        <div class="pagination">
            <a href="?page=1" class="<?= $page === 1 ? 'disabled' : '' ?>">First</a>
            <a href="?page=<?= max(1, $page - 1) ?>" class="<?= $page === 1 ? 'disabled' : '' ?>">Previous</a>
            <span>Page <?= $page ?> of <?= $totalPages ?></span>
            <a href="?page=<?= min($totalPages, $page + 1) ?>" class="<?= $page === $totalPages ? 'disabled' : '' ?>">Next</a>
            <a href="?page=<?= $totalPages ?>" class="<?= $page === $totalPages ? 'disabled' : '' ?>">Last</a>
        </div>
        <?php endif; ?>
    </div>
</div>
<style>
    .table-container {
        width: 100%;
        overflow-x: auto;
    }

    .table-wrapper {
        overflow-x: auto;
    }

    .students-table {
        width: 100%;
        min-width: 900px;
        border-collapse: collapse;
    }

    .students-table th, .students-table td {
        padding: 10px;
        border-bottom: 1px solid #ddd;
        white-space: nowrap;
    }
</style>

<script>
// Handle Export Functionality
function handleExport(type) {
    if (type === 'pdf') {
        exportTableToPDF();
    } else if (type === 'csv') {
        exportTableToCSV('students_results.csv');
    } else if (type === 'excel') {
        exportTableToExcel('students_results.xls');
    }
}

// Export Table to PDF
function exportTableToPDF() {
    const { jsPDF } = window.jspdf;
    const doc = new jsPDF('l', 'mm', 'a4'); // Landscape mode, millimeters, A4 size

    // Get table data
    const table = document.getElementById('studentsTable');
    const headers = [];
    const rows = [];

    // Extract headers
    table.querySelectorAll('thead th').forEach(th => {
        headers.push(th.innerText);
    });

    // Extract rows
    table.querySelectorAll('tbody tr').forEach(tr => {
        const row = [];
        tr.querySelectorAll('td').forEach(td => {
            row.push(td.innerText);
        });
        rows.push(row);
    });

    // Add table to PDF
    doc.autoTable({
        head: [headers],
        body: rows,
        startY: 20, // Start 20mm from the top
        margin: { top: 20 },
        styles: { fontSize: 10, cellPadding: 2 },
        headerStyles: { fillColor: [22, 160, 133] }, // Green header
        alternateRowStyles: { fillColor: [245, 245, 245] }, // Alternate row color
    });

    // Save the PDF
    doc.save('students_results.pdf');
}

// Export Table to CSV
function exportTableToCSV(filename) {
    const rows = document.querySelectorAll('table tr');
    const csv = [];
    for (let i = 0; i < rows.length; i++) {
        const row = [], cols = rows[i].querySelectorAll('td, th');
        for (let j = 0; j < cols.length; j++) {
            row.push(cols[j].innerText);
        }
        csv.push(row.join(','));
    }
    const csvFile = new Blob([csv.join('\n')], { type: 'text/csv' });
    const downloadLink = document.createElement('a');
    downloadLink.download = filename;
    downloadLink.href = window.URL.createObjectURL(csvFile);
    downloadLink.style.display = 'none';
    document.body.appendChild(downloadLink);
    downloadLink.click();
}

// Export Table to Excel
function exportTableToExcel(filename) {
    const table = document.getElementById('studentsTable');
    const wb = XLSX.utils.table_to_book(table, { sheet: 'Sheet 1' });
    XLSX.writeFile(wb, filename);
}
</script>
</body>
</html>