<?php
session_start();
require_once 'db.php'; // Include the database connection

if (!isset($_SESSION['student_id'])) {
    die("Access Denied: No Student ID Found");
}

$student_id = $_SESSION['student_id'];

// Fetch student details
$sql = "SELECT * FROM students WHERE student_id = :student_id";
$stmt = $conn->prepare($sql);
$stmt->bindParam(':student_id', $student_id, PDO::PARAM_STR);
$stmt->execute();
$student = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$student) {
    die("Student not found.");
}

// Fetch total number of distinct class dates
$sql_total_classes = "SELECT COUNT(DISTINCT date) AS total_classes FROM attendance";
$stmt_total_classes = $conn->prepare($sql_total_classes);
$stmt_total_classes->execute();
$total_classes = $stmt_total_classes->fetch(PDO::FETCH_ASSOC)['total_classes'] ?? 0;

// Fetch the number of attended classes for the student
$sql_attendance = "SELECT COUNT(DISTINCT date) AS attended FROM attendance WHERE student_id = :student_id ";
$stmt_attendance = $conn->prepare($sql_attendance);
$stmt_attendance->bindParam(':student_id', $student_id, PDO::PARAM_STR);
$stmt_attendance->execute();
$attended = $stmt_attendance->fetch(PDO::FETCH_ASSOC)['attended'] ?? 0;

// Calculate attendance percentage
$attendance_percentage = ($total_classes > 0) ? round(($attended / $total_classes) * 100, 2) : 0;

// Fetch marks
$sql_marks = "SELECT * FROM student_marks WHERE student_id = :student_id";
$stmt_marks = $conn->prepare($sql_marks);
$stmt_marks->bindParam(':student_id', $student_id, PDO::PARAM_STR);
$stmt_marks->execute();
$marks = $stmt_marks->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Profile</title>
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
    </style>
</head>
<body>
    <div class="container">
        <h2 style="">Student Profile</h2>
        <p style="text-align: left;"><strong>Name:</strong> <?= htmlspecialchars($student['name']) ?></p>
        <p style="text-align: left;"><strong>Student ID:</strong> <?= htmlspecialchars($student['student_id']) ?></p>
        <p style="text-align: left;"><strong>Email:</strong> <?= htmlspecialchars($student['email']) ?></p>
        <p style="text-align: left;"><strong>Phone:</strong> 0<?= htmlspecialchars($student['phone_number']) ?></p>
        
        <h3>Attendance</h3>
        <table>
            <tr>
                <th>Total Classes</th>
                <th>Attended</th>
                <th>Percentage</th>
            </tr>
            <tr>
                <td><?= htmlspecialchars($total_classes) ?></td>
                <td><?= htmlspecialchars($attended) ?></td>
                <td><?= htmlspecialchars($attendance_percentage) ?>%</td>
            </tr>
        </table>

        <h3>Class Test Marks</h3>
        <table>
            <tr>
                <th>Test</th>
                <th>Marks</th>
            </tr>
            <tr>
                <td>Quiz 1</td>
                <td><?= htmlspecialchars($marks['class_test_1'] ?? '0') ?>/20</td>
            </tr>
            <tr>
                <td>Quiz 2</td>
                <td><?= htmlspecialchars($marks['class_test_2'] ?? '0') ?>/20</td>
            </tr>
            <tr>
                <td>Quiz 3</td>
                <td><?= htmlspecialchars($marks['class_test_3'] ?? '0') ?>/20</td>
            </tr>
        </table>

        <h3>Assignment Marks</h3>
        <table>
            <tr>
                <th>Assignment</th>
                <th>Marks</th>
            </tr>
            <tr>
                <td>Assignment 1</td>
                <td><?= htmlspecialchars($marks['assignment_1'] ?? '0') ?>/10</td>
            </tr>
            <tr>
                <td>Assignment 2</td>
                <td><?= htmlspecialchars($marks['assignment_2'] ?? '0') ?>/10</td>
            </tr>
        </table>
        
    </div>
</body>
</html>
