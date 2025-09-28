<?php
session_start();

require_once 'db.php'; // Include database connection

if (!isset($_SESSION['user']) || $_SESSION['user'] !== 'admin') {
    header("Location: login.php");
    exit();
}

try {
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Handle AJAX requests for updating marks
    if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['action'])) {
        header('Content-Type: application/json');
        
        if ($_POST['action'] === 'update_mark') {
            $student_id = $_POST['student_id'] ?? '';
            $field = $_POST['field'] ?? '';
            $value = (int)($_POST['value'] ?? 0);
            
            // Validate field name to prevent SQL injection
            $allowed_fields = ['class_test_1', 'class_test_2', 'class_test_3', 'assignment_1', 'assignment_2'];
            if (!in_array($field, $allowed_fields)) {
                echo json_encode(['success' => false, 'message' => 'Invalid field']);
                exit;
            }
            
            // Validate mark range
            if ($value < 0 || $value > 100) {
                echo json_encode(['success' => false, 'message' => 'Marks must be between 0 and 100']);
                exit;
            }
            
            // Check if record exists
            $check_query = "SELECT COUNT(*) FROM student_marks WHERE student_id = :student_id";
            $check_stmt = $conn->prepare($check_query);
            $check_stmt->execute([':student_id' => $student_id]);
            $record_exists = $check_stmt->fetchColumn() > 0;
            
            if ($record_exists) {
                // Update existing record
                $update_query = "UPDATE student_marks SET $field = :value WHERE student_id = :student_id";
            } else {
                // Insert new record with default values
                $insert_query = "INSERT INTO student_marks (student_id, class_test_1, class_test_2, class_test_3, assignment_1, assignment_2) 
                               VALUES (:student_id, 0, 0, 0, 0, 0)";
                $insert_stmt = $conn->prepare($insert_query);
                $insert_stmt->execute([':student_id' => $student_id]);
                
                // Now update the specific field
                $update_query = "UPDATE student_marks SET $field = :value WHERE student_id = :student_id";
            }
            
            $stmt = $conn->prepare($update_query);
            $result = $stmt->execute([
                ':student_id' => $student_id,
                ':value' => $value
            ]);
            
            if ($result) {
                echo json_encode(['success' => true, 'message' => 'Mark updated successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to update mark']);
            }
            exit;
        }
        
        if ($_POST['action'] === 'delete_student_marks') {
            $student_id = $_POST['student_id'] ?? '';
            
            $delete_query = "DELETE FROM student_marks WHERE student_id = :student_id";
            $stmt = $conn->prepare($delete_query);
            $result = $stmt->execute([':student_id' => $student_id]);
            
            if ($result) {
                echo json_encode(['success' => true, 'message' => 'Student marks deleted successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to delete student marks']);
            }
            exit;
        }
    }

    // Fetch all students with their marks
    $sql = "SELECT 
                s.student_id, 
                s.name, 
                s.email,
                COALESCE(sm.class_test_1, 0) as class_test_1,
                COALESCE(sm.class_test_2, 0) as class_test_2,
                COALESCE(sm.class_test_3, 0) as class_test_3,
                COALESCE(sm.assignment_1, 0) as assignment_1,
                COALESCE(sm.assignment_2, 0) as assignment_2,
                ROUND((COALESCE(sm.class_test_1, 0) + COALESCE(sm.class_test_2, 0) + COALESCE(sm.class_test_3, 0) + COALESCE(sm.assignment_1, 0) + COALESCE(sm.assignment_2, 0)) / 5, 2) as average
            FROM students s
            LEFT JOIN student_marks sm ON s.student_id = sm.student_id
            ORDER BY s.name";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $students_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $error_message = "Database error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Marks Overview</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
            padding: 20px;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .header {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            padding: 25px 30px;
            text-align: center;
        }

        .header h1 {
            font-size: 2.5em;
            margin-bottom: 10px;
        }

        .header p {
            font-size: 1.1em;
            opacity: 0.9;
        }

        .controls {
            padding: 20px 30px;
            background: #f8f9fa;
            border-bottom: 1px solid #dee2e6;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }

        .search-box {
            flex: 1;
            max-width: 300px;
        }

        .search-box input {
            width: 100%;
            padding: 10px 15px;
            border: 1px solid #ddd;
            border-radius: 25px;
            font-size: 14px;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background: #007bff;
            color: white;
        }

        .btn-success {
            background: #28a745;
            color: white;
        }

        .btn-danger {
            background: #dc3545;
            color: white;
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }

        .table-container {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }

        th, td {
            padding: 12px 8px;
            text-align: center;
            border-bottom: 1px solid #dee2e6;
        }

        th {
            background: #f8f9fa;
            font-weight: 600;
            color: #495057;
            position: sticky;
            top: 0;
            z-index: 10;
        }

        tr:hover {
            background-color: #f8f9fa;
        }

        .editable {
            cursor: pointer;
            padding: 6px 8px;
            border-radius: 4px;
            transition: background-color 0.3s ease;
            min-width: 50px;
        }

        .editable:hover {
            background-color: #e9ecef;
        }

        .editing {
            background-color: #fff3cd !important;
            border: 2px solid #ffc107;
        }

        .edit-input {
            width: 60px;
            padding: 4px;
            border: 1px solid #ccc;
            border-radius: 3px;
            text-align: center;
            font-size: 14px;
        }

        .student-info {
            text-align: left !important;
        }

        .student-name {
            font-weight: 600;
            color: #495057;
        }

        .student-id {
            font-size: 12px;
            color: #6c757d;
            margin-top: 2px;
        }

        .student-email {
            font-size: 11px;
            color: #6c757d;
        }

        .average {
            font-weight: 600;
            padding: 8px;
            border-radius: 4px;
        }

        .grade-a { background-color: #d4edda; color: #155724; }
        .grade-b { background-color: #d1ecf1; color: #0c5460; }
        .grade-c { background-color: #fff3cd; color: #856404; }
        .grade-d { background-color: #f8d7da; color: #721c24; }

        .actions {
            display: flex;
            gap: 5px;
            justify-content: center;
        }

        .btn-sm {
            padding: 5px 10px;
            font-size: 12px;
        }

        .alert {
            padding: 15px;
            margin: 20px 30px;
            border-radius: 5px;
            display: none;
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .stats {
            display: flex;
            justify-content: space-around;
            padding: 20px;
            background: #f8f9fa;
            border-top: 1px solid #dee2e6;
        }

        .stat-item {
            text-align: center;
        }

        .stat-number {
            font-size: 2em;
            font-weight: bold;
            color: #28a745;
        }

        .stat-label {
            color: #6c757d;
            font-size: 14px;
        }

        .loading {
            display: none;
            text-align: center;
            padding: 20px;
        }

        @media (max-width: 768px) {
            .controls {
                flex-direction: column;
                align-items: stretch;
            }
            
            .search-box {
                max-width: none;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Student Marks Management</h1>
            <p>View and edit student marks with ease</p>
        </div>

        <div class="alert alert-success" id="successAlert"></div>
        <div class="alert alert-danger" id="errorAlert"></div>

        <div class="controls">
            <div class="search-box">
                <input type="text" id="searchInput" placeholder="Search students by name or ID...">
            </div>
            <div>
                <a href="marks_entry.php" class="btn btn-primary">Add New Entry</a>
                <button class="btn btn-secondary" onclick="exportData()">Export CSV</button>
                <button class="btn btn-success" onclick="location.reload()">Refresh</button>
            </div>
        </div>

        <div class="loading" id="loading">
            <p>Processing...</p>
        </div>

        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger" style="display: block;">
                <?= htmlspecialchars($error_message) ?>
            </div>
        <?php else: ?>
            <div class="table-container">
                <table id="marksTable">
                    <thead>
                        <tr>
                            <th>Student Information</th>
                            <th>Class Test 1</th>
                            <th>Class Test 2</th>
                            <th>Class Test 3</th>
                            <th>Assignment 1</th>
                            <th>Assignment 2</th>
                            <th>Average</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($students_data as $student): ?>
                            <tr data-student-id="<?= htmlspecialchars($student['student_id']) ?>">
                                <td class="student-info">
                                    <div class="student-name"><?= htmlspecialchars($student['name']) ?></div>
                                    <div class="student-id">ID: <?= htmlspecialchars($student['student_id']) ?></div>
                                    <div class="student-email"><?= htmlspecialchars($student['email']) ?></div>
                                </td>
                                <td class="editable" data-field="class_test_1"><?= htmlspecialchars($student['class_test_1']) ?></td>
                                <td class="editable" data-field="class_test_2"><?= htmlspecialchars($student['class_test_2']) ?></td>
                                <td class="editable" data-field="class_test_3"><?= htmlspecialchars($student['class_test_3']) ?></td>
                                <td class="editable" data-field="assignment_1"><?= htmlspecialchars($student['assignment_1']) ?></td>
                                <td class="editable" data-field="assignment_2"><?= htmlspecialchars($student['assignment_2']) ?></td>
                                <td class="average <?= $student['average'] >= 80 ? 'grade-a' : ($student['average'] >= 70 ? 'grade-b' : ($student['average'] >= 60 ? 'grade-c' : 'grade-d')) ?>">
                                    <?= number_format($student['average'], 2) ?>%
                                </td>
                                <td class="actions">
                                    <button class="btn btn-danger btn-sm" onclick="deleteStudentMarks('<?= htmlspecialchars($student['student_id']) ?>', '<?= htmlspecialchars($student['name']) ?>')">
                                        Clear All
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="stats">
                <div class="stat-item">
                    <div class="stat-number" id="totalStudents"><?= count($students_data) ?></div>
                    <div class="stat-label">Total Students</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number" id="avgScore">
                        <?= count($students_data) > 0 ? number_format(array_sum(array_column($students_data, 'average')) / count($students_data), 2) : '0.00' ?>%
                    </div>
                    <div class="stat-label">Class Average</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number" id="highScorers">
                        <?= count(array_filter($students_data, function($s) { return $s['average'] >= 80; })) ?>
                    </div>
                    <div class="stat-label">High Performers (≥80%)</div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // Global variables
        let isEditing = false;
        let currentEditCell = null;

        // Show alert messages
        function showAlert(message, type) {
            const alertId = type === 'success' ? 'successAlert' : 'errorAlert';
            const alert = document.getElementById(alertId);
            alert.textContent = message;
            alert.style.display = 'block';
            
            setTimeout(() => {
                alert.style.display = 'none';
            }, 5000);
        }

        // Search functionality
        document.getElementById('searchInput').addEventListener('keyup', function() {
            const searchTerm = this.value.toLowerCase();
            const rows = document.querySelectorAll('#marksTable tbody tr');
            
            rows.forEach(row => {
                const studentInfo = row.querySelector('.student-info').textContent.toLowerCase();
                if (studentInfo.includes(searchTerm)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });

        // Inline editing functionality
        document.querySelectorAll('.editable').forEach(cell => {
            cell.addEventListener('click', function() {
                if (isEditing) return;
                
                const currentValue = this.textContent.trim();
                const field = this.dataset.field;
                const studentId = this.closest('tr').dataset.studentId;
                
                // Create input element
                const input = document.createElement('input');
                input.type = 'number';
                input.className = 'edit-input';
                input.value = currentValue;
                input.min = '0';
                input.max = '100';
                
                // Replace cell content with input
                this.innerHTML = '';
                this.appendChild(input);
                this.classList.add('editing');
                
                input.focus();
                input.select();
                
                isEditing = true;
                currentEditCell = this;
                
                // Handle save on Enter or blur
                const saveEdit = () => {
                    const newValue = parseInt(input.value) || 0;
                    
                    if (newValue < 0 || newValue > 100) {
                        showAlert('Marks must be between 0 and 100', 'error');
                        input.focus();
                        return;
                    }
                    
                    if (newValue.toString() !== currentValue) {
                        updateMark(studentId, field, newValue, this);
                    } else {
                        // No change, just restore
                        this.textContent = currentValue;
                        this.classList.remove('editing');
                        isEditing = false;
                        currentEditCell = null;
                    }
                };
                
                input.addEventListener('blur', saveEdit);
                input.addEventListener('keypress', function(e) {
                    if (e.key === 'Enter') {
                        saveEdit();
                    }
                    if (e.key === 'Escape') {
                        this.textContent = currentValue;
                        this.classList.remove('editing');
                        isEditing = false;
                        currentEditCell = null;
                    }
                });
            });
        });

        // Update mark via AJAX
        function updateMark(studentId, field, value, cell) {
            document.getElementById('loading').style.display = 'block';
            
            const formData = new FormData();
            formData.append('action', 'update_mark');
            formData.append('student_id', studentId);
            formData.append('field', field);
            formData.append('value', value);
            
            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                document.getElementById('loading').style.display = 'none';
                
                if (data.success) {
                    cell.textContent = value;
                    cell.classList.remove('editing');
                    showAlert(data.message, 'success');
                    
                    // Update average
                    updateRowAverage(cell.closest('tr'));
                } else {
                    showAlert(data.message, 'error');
                    cell.textContent = cell.dataset.originalValue || '0';
                    cell.classList.remove('editing');
                }
                
                isEditing = false;
                currentEditCell = null;
            })
            .catch(error => {
                document.getElementById('loading').style.display = 'none';
                console.error('Error:', error);
                showAlert('Network error occurred', 'error');
                cell.textContent = cell.dataset.originalValue || '0';
                cell.classList.remove('editing');
                isEditing = false;
                currentEditCell = null;
            });
        }

        // Update row average
        function updateRowAverage(row) {
            const editableCells = row.querySelectorAll('.editable');
            let total = 0;
            editableCells.forEach(cell => {
                total += parseInt(cell.textContent) || 0;
            });
            
            const average = total / editableCells.length;
            const avgCell = row.querySelector('.average');
            avgCell.textContent = average.toFixed(2) + '%';
            
            // Update grade class
            avgCell.className = 'average ' + 
                (average >= 80 ? 'grade-a' : 
                 average >= 70 ? 'grade-b' : 
                 average >= 60 ? 'grade-c' : 'grade-d');
        }

        // Delete student marks
        function deleteStudentMarks(studentId, studentName) {
            if (!confirm(`Are you sure you want to clear all marks for ${studentName}?`)) {
                return;
            }
            
            document.getElementById('loading').style.display = 'block';
            
            const formData = new FormData();
            formData.append('action', 'delete_student_marks');
            formData.append('student_id', studentId);
            
            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                document.getElementById('loading').style.display = 'none';
                
                if (data.success) {
                    showAlert(data.message, 'success');
                    
                    // Reset all marks to 0
                    const row = document.querySelector(`tr[data-student-id="${studentId}"]`);
                    const editableCells = row.querySelectorAll('.editable');
                    editableCells.forEach(cell => {
                        cell.textContent = '0';
                    });
                    
                    updateRowAverage(row);
                } else {
                    showAlert(data.message, 'error');
                }
            })
            .catch(error => {
                document.getElementById('loading').style.display = 'none';
                console.error('Error:', error);
                showAlert('Network error occurred', 'error');
            });
        }

        // Export data to CSV
        function exportData() {
            const table = document.getElementById('marksTable');
            const rows = table.querySelectorAll('tr');
            let csv = [];
            
            // Header row
            const headerCells = rows[0].querySelectorAll('th');
            const header = Array.from(headerCells).map(cell => 
                cell.textContent.replace(/,/g, '')
            ).join(',');
            csv.push(header);
            
            // Data rows
            for (let i = 1; i < rows.length; i++) {
                if (rows[i].style.display === 'none') continue; // Skip hidden rows
                
                const row = rows[i];
                const cells = row.querySelectorAll('td');
                const rowData = [];
                
                cells.forEach((cell, index) => {
                    if (index === 0) { // Student info
                        const name = cell.querySelector('.student-name').textContent;
                        const id = cell.querySelector('.student-id').textContent.replace('ID: ', '');
                        const email = cell.querySelector('.student-email').textContent;
                        rowData.push(`"${name}"`, id, email);
                    } else if (index < 6) { // Marks
                        rowData.push(cell.textContent.trim());
                    } else if (index === 6) { // Average
                        rowData.push(cell.textContent.replace('%', ''));
                    }
                });
                
                csv.push(rowData.join(','));
            }
            
            // Download CSV
            const csvContent = csv.join('\n');
            const blob = new Blob([csvContent], { type: 'text/csv' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'student_marks_' + new Date().toISOString().split('T')[0] + '.csv';
            a.click();
            window.URL.revokeObjectURL(url);
        }

        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            // Escape key to cancel editing
            if (e.key === 'Escape' && isEditing && currentEditCell) {
                const input = currentEditCell.querySelector('.edit-input');
                if (input) {
                    currentEditCell.textContent = input.dataset.originalValue || '0';
                    currentEditCell.classList.remove('editing');
                    isEditing = false;
                    currentEditCell = null;
                }
            }
        });
    </script>
</body>
</html>