<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

// Determine student ID based on session role
if ($_SESSION['user'] === 'admin') {
    if (!isset($_SESSION['student_id'])) {
        die("Access Denied: No Student ID Specified for Admin View");
    }
    $student_id = $_SESSION['student_id'];
} else {
    if (!isset($_SESSION['student_id'])) {
        die("Access Denied: Invalid Student Session");
    }
    $student_id = $_SESSION['student_id'];
}

// Fetch student details
$stmt = $conn->prepare("SELECT * FROM students WHERE student_id = :student_id");
$stmt->bindParam(':student_id', $student_id, PDO::PARAM_STR);
$stmt->execute();
$student = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$student) {
    die("Student Record Not Found");
}

// Fetch attendance data
$total_classes_stmt = $conn->query("SELECT COUNT(DISTINCT date) FROM attendance");
$total_classes = $total_classes_stmt->fetchColumn();

$attended_stmt = $conn->prepare("SELECT COUNT(DISTINCT date) FROM attendance WHERE student_id = ?");
$attended_stmt->execute([$student_id]);
$attended = $attended_stmt->fetchColumn();

$attendance_percentage = $total_classes ? round(($attended / $total_classes) * 100, 2) : 0;

// Fetch marks
$marks_stmt = $conn->prepare("SELECT * FROM student_marks WHERE student_id = ?");
$marks_stmt->execute([$student_id]);
$marks = $marks_stmt->fetch(PDO::FETCH_ASSOC) ?: [];
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Profile</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
            margin: 0;
            padding: 20px;
        }
        .profile-container {
            max-width: 800px;
            margin: 20px auto;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            padding: 30px;
        }
        h2, h3 {
            color: #2c3e50;
            border-bottom: 2px solid #28a745;
            padding-bottom: 10px;
        }
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }
        .info-card {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #dee2e6;
        }
        th {
            background-color: #28a745;
            color: white;
        }
        .metric-badge {
            background: #28a745;
            color: white;
            padding: 8px 15px;
            border-radius: 20px;
            font-weight: 500;
        }
        button {
            background: #28a745;
            color: white;
            padding: 12px 25px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            transition: background 0.3s ease;
        }
        button:hover {
            background: #28a745;
        }
    </style>
</head>
<body>
    <div class="profile-container">
        <h2>Student Profile</h2>
        
        <div class="info-grid">
            <div class="info-card">
                <h3>Personal Information</h3>
                <p><strong>Name:</strong> <?= htmlspecialchars($student['name']) ?></p>
                <p><strong>Student ID:</strong> <?= htmlspecialchars($student['student_id']) ?></p>
                <p><strong>Email:</strong> <?= htmlspecialchars($student['email']) ?></p>
                <p><strong>Phone:</strong> <?= htmlspecialchars($student['phone_number']) ?></p>
            </div>

            <div class="info-card">
                <h3>Attendance Summary</h3>
                <p><strong>Total Classes:</strong> <?= $total_classes ?></p>
                <p><strong>Attended:</strong> <?= $attended ?></p>
                <p><strong>Percentage:</strong> 
                    <span class="metric-badge"><?= $attendance_percentage ?>%</span>
                </p>
            </div>
        </div>

        <h3>Academic Performance</h3>
        <div class="info-grid">
            <div class="info-card">
                <h4>Class Tests</h4>
                <table>
                    <tr><th>Test</th><th>Score</th></tr>
                    <tr><td>Quiz 1</td><td><?= $marks['class_test_1'] ?? 0 ?>/20</td></tr>
                    <tr><td>Quiz 2</td><td><?= $marks['class_test_2'] ?? 0 ?>/20</td></tr>
                    <tr><td>Quiz 3</td><td><?= $marks['class_test_3'] ?? 0 ?>/20</td></tr>
                </table>
            </div>

            <div class="info-card">
                <h4>Assignments</h4>
                <table>
                    <tr><th>Assignment</th><th>Score</th></tr>
                    <tr><td>Assignment 1</td><td><?= $marks['assignment_1'] ?? 0 ?>/10</td></tr>
                    <tr><td>Assignment 2</td><td><?= $marks['assignment_2'] ?? 0 ?>/10</td></tr>
                </table>
            </div>
        </div>
        <div class="info-card" style="margin-top: 25px;">
            <h3>Marks Calculation</h3>
            <?php
            // Calculate best three scores from class tests and assignments
            $ct1 = $marks['class_test_1'] ?? 0;
            $ct2 = $marks['class_test_2'] ?? 0;
            $ct3 = $marks['class_test_3'] ?? 0;
            $asn = ($marks['assignment_1'] ?? 0) + ($marks['assignment_2'] ?? 0);
            
            $scores = [$ct1, $ct2, $ct3, $asn];
            rsort($scores);
            $bestThree = array_sum(array_slice($scores, 0, 3));
            
            // Scale best three scores to a total of 20 marks
            // Maximum bestThree is 60, so we divide by 3 and round (6.5 becomes 7)
            $academicMark = round($bestThree / 3, 0, PHP_ROUND_HALF_UP);
            
            // Calculate attendance marks: each 10% attendance gives 1 mark (using floor)
            $attendanceMark = ($attendance_percentage >= 80) ? 10 : floor(($attendance_percentage / 80) * 10);
            
            // Final total marks combining academic (out of 20) and attendance (out of 10)
            $finalTotal = $academicMark + $attendanceMark;
            ?>
            <p>Academic Performance (Best Three Scores): <span class=""><?= $academicMark ?>/20</span></p>
            <p>Attendance Marks: <span class=""><?= $attendanceMark ?>/10</span></p>
            <p>Total Marks: <span class="metric-badge"><?= $finalTotal ?>/30</span></p>
            <?php
            $scoresAssoc = [
                'Quiz 1'      => $ct1,
                'Quiz 2'      => $ct2,
                'Quiz 3'      => $ct3,
                'Assignments' => $asn,
            ];
            arsort($scoresAssoc);
            $bestThree = array_slice($scoresAssoc, 0, 3, true);
            ?>
            <p><em><strong>Best 3 Scores Considered:</strong> <br><?php echo implode(', ', array_map(function($name, $score) { 
                return "$name: $score"; 
            }, array_keys($bestThree), $bestThree)); ?></em></p>
        </div>

        <div style="text-align: center; margin-top: 30px;">
            <button onclick="window.location.href='review.php'">Submit Course Review</button>
        </div>
    </div>
</body>
</html>