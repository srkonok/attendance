<?php
session_start();
require_once 'db.php'; // Include database connection

$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); // Enable error reporting

// Fetch students for the dropdown
$sql_students = "SELECT student_id, name FROM students";
$stmt_students = $conn->prepare($sql_students);
$stmt_students->execute();
$students = $stmt_students->fetchAll(PDO::FETCH_ASSOC);

// Sanitize student_id input
$student_id = isset($_GET['student_id']) && $_GET['student_id'] !== '' ? htmlspecialchars($_GET['student_id']) : null;

// Initialize marks
$existing_marks = [
    'class_test_1' => 0,
    'class_test_2' => 0,
    'class_test_3' => 0,
    'assignment_1' => 0,
    'assignment_2' => 0
];

// Fetch existing marks if a student is selected
if ($student_id) {
    $sql_marks = "SELECT * FROM student_marks WHERE student_id = :student_id";
    $stmt_marks = $conn->prepare($sql_marks);
    $stmt_marks->execute([':student_id' => $student_id]);
    $existing_marks = $stmt_marks->fetch(PDO::FETCH_ASSOC) ?? $existing_marks;
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    try {
        $student_id = $_POST['student_id'] ?? null;

        if ($student_id) {
            // Ensure numeric values and prevent empty entries
            $class_tests = $_POST['class_tests'] ?? [0, 0, 0];
            $assignments = $_POST['assignments'] ?? [0, 0];

            // Use REPLACE INTO to ensure record updates
            $update_query = "REPLACE INTO student_marks (student_id, class_test_1, class_test_2, class_test_3, assignment_1, assignment_2) 
                             VALUES (:student_id, :class_test_1, :class_test_2, :class_test_3, :assignment_1, :assignment_2)";

            $stmt = $conn->prepare($update_query);
            $stmt->execute([
                ':student_id' => $student_id,
                ':class_test_1' => (int)$class_tests[0],
                ':class_test_2' => (int)$class_tests[1],
                ':class_test_3' => (int)$class_tests[2],
                ':assignment_1' => (int)$assignments[0],
                ':assignment_2' => (int)$assignments[1]
            ]);

            echo "<script>
                alert('Student data saved successfully!');
                window.location.href = 'marks_entry.php';
            </script>";
            exit;
        }
    } catch (PDOException $e) {
        echo "<script>alert('Error: " . $e->getMessage() . "');</script>";
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Entry Page</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            text-align: center;
            margin: 20px;
        }
        .container {
            max-width: 600px;
            background: #fff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            margin: auto;
        }
        h2 {
            color: #333;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        table, th, td {
            border: 1px solid #ddd;
        }
        th, td {
            padding: 10px;
            text-align: center;
        }
        th {
            background: #28a745;
            color: #fff;
        }
        input {
            width: 80%;
            padding: 5px;
            margin: 5px;
            text-align: center;
            border: 1px solid #ccc;
            border-radius: 5px;
        }
        button {
            background: #28a745;
            color: #fff;
            padding: 10px 15px;
            border: none;
            cursor: pointer;
            margin-top: 10px;
            border-radius: 5px;
        }
        button:hover {
            background: #218838;
        }
        .search-container {
            position: relative;
            text-align: left;
            margin: auto;
            max-width: 400px;
        }
        .search-container input {
            width: 100%;
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }
        .dropdown {
            position: absolute;
            width: 100%;
            background: white;
            border: 1px solid #ccc;
            display: none;
            max-height: 150px;
            overflow-y: auto;
            z-index: 10;
        }
        .dropdown div {
            padding: 8px;
            cursor: pointer;
        }
        .dropdown div:hover {
            background: #f0f0f0;
        }
    </style>
    <script>
        function filterStudents() {
            let input = document.getElementById("studentSearch").value.toLowerCase();
            let dropdown = document.getElementById("studentDropdown");
            dropdown.innerHTML = "";

            if (input.length === 0) {
                dropdown.style.display = "none";
                return;
            }

            let students = <?= json_encode($students); ?>;
            let matchedStudents = students.filter(s => 
                s.name.toLowerCase().includes(input) || s.student_id.includes(input)
            );

            if (matchedStudents.length === 0) {
                dropdown.style.display = "none";
                return;
            }

            matchedStudents.forEach(student => {
                let div = document.createElement("div");
                div.textContent = student.name + " (ID: " + student.student_id + ")";
                div.onclick = function() {
                    document.getElementById("studentSearch").value = student.name + " (ID: " + student.student_id + ")";
                    document.getElementById("studentIdInput").value = student.student_id;
                    document.getElementById("studentForm").submit();
                };
                dropdown.appendChild(div);
            });

            dropdown.style.display = "block";
        }

        document.addEventListener("click", function(event) {
            let dropdown = document.getElementById("studentDropdown");
            let searchBox = document.getElementById("studentSearch");

            if (event.target !== searchBox) {
                dropdown.style.display = "none";
            }
        });
    </script>
</head>
<body>
    <div class="container">
        <h2>Teacher Entry Page</h2>

        <form method="GET" id="studentForm">
            <div class="search-container">
                <input type="text" id="studentSearch" placeholder="Search student by name or ID" onkeyup="filterStudents()" autocomplete="off">
                <input type="hidden" name="student_id" id="studentIdInput">
                <div id="studentDropdown" class="dropdown"></div>
            </div>
        </form>

        <form method="POST">
            <input type="hidden" name="student_id" value="<?= htmlspecialchars($student_id) ?>">

            <h3>Class Test Marks</h3>
            <table>
                <tr><th>Test</th><th>Marks</th></tr>
                <tr><td>Class Test 1</td><td><input type="number" name="class_tests[]" value="<?= $existing_marks['class_test_1'] ?>"></td></tr>
                <tr><td>Class Test 2</td><td><input type="number" name="class_tests[]" value="<?= $existing_marks['class_test_2'] ?>"></td></tr>
                <tr><td>Class Test 3</td><td><input type="number" name="class_tests[]" value="<?= $existing_marks['class_test_3'] ?>"></td></tr>
            </table>

            <h3>Assignment Marks</h3>
            <table>
                <tr><td>Assignment 1</td><td><input type="number" name="assignments[]" value="<?= $existing_marks['assignment_1'] ?>"></td></tr>
                <tr><td>Assignment 2</td><td><input type="number" name="assignments[]" value="<?= $existing_marks['assignment_2'] ?>"></td></tr>
            </table>

            <button type="submit">Save Marks</button>
        </form>
    </div>
</body>
</html>
