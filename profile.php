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
    <title>Student Profile - Elegant Dashboard</title>
    <link rel="stylesheet" href="index.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', 'Segoe UI', system-ui, -apple-system, sans-serif;
            background: linear-gradient(135deg, #f8fffe 0%, #e8f5f3 50%, #d1f2eb 100%);
            color: #2d5a52;
            line-height: 1.6;
            min-height: 100vh;
            padding: 20px;
        }

        .main-container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .header {
            background: linear-gradient(135deg, #27ae60 0%, #2ecc71 100%);
            color: white;
            padding: 2rem;
            border-radius: 20px;
            margin-bottom: 2rem;
            box-shadow: 0 10px 30px rgba(39, 174, 96, 0.3);
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .header::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            animation: shimmer 8s ease-in-out infinite;
        }

        @keyframes shimmer {
            0%, 100% { transform: translate(-50%, -50%) rotate(0deg); }
            50% { transform: translate(-50%, -50%) rotate(180deg); }
        }

        .header h1 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            position: relative;
            z-index: 1;
        }

        .header p {
            font-size: 1.1rem;
            opacity: 0.9;
            position: relative;
            z-index: 1;
        }

        .profile-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            margin-bottom: 2rem;
        }
       @media (max-width: 768px) {
        .info-value {
            word-break: break-word;
            overflow-wrap: anywhere;
            display: block;
        }
        }


        .profile-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 8px 25px rgba(45, 90, 82, 0.1);
            border: 1px solid rgba(46, 204, 113, 0.2);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .profile-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #27ae60, #2ecc71, #58d68d);
        }

        .profile-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(45, 90, 82, 0.15);
        }

        .card-title {
            font-size: 1.4rem;
            font-weight: 600;
            color: #2d5a52;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .card-title::before {
            content: '';
            width: 6px;
            height: 6px;
            background: #27ae60;
            border-radius: 50%;
        }

        .info-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.8rem 0;
            border-bottom: 1px solid rgba(46, 204, 113, 0.1);
        }

        .info-item.email {
            flex-direction: column;
            align-items: flex-start;
            gap: 0.5rem;
        }

        .info-item:last-child {
            border-bottom: none;
        }

        .info-label {
            font-weight: 500;
            color: #5a8a82;
        }

        .info-value {
            font-weight: 600;
            color: #2d5a52;
        }

        .metric-badge {
            background: linear-gradient(135deg, #27ae60, #2ecc71);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 25px;
            font-weight: 600;
            font-size: 0.9rem;
            box-shadow: 0 4px 15px rgba(39, 174, 96, 0.3);
        }

        .academic-section {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 8px 25px rgba(45, 90, 82, 0.1);
            border: 1px solid rgba(46, 204, 113, 0.2);
        }

        .performance-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            margin: 2rem 0;
        }

        .performance-card {
            background: linear-gradient(135deg, #f8fff8 0%, #e8f8e8 100%);
            border-radius: 15px;
            padding: 1.5rem;
            border: 2px solid rgba(46, 204, 113, 0.2);
            transition: all 0.3s ease;
        }

        .performance-card:hover {
            border-color: rgba(46, 204, 113, 0.4);
            transform: scale(1.02);
        }

        .performance-title {
            font-size: 1.2rem;
            font-weight: 600;
            color: #2d5a52;
            margin-bottom: 1rem;
            text-align: center;
        }

        .score-table {
            width: 100%;
            border-collapse: collapse;
        }

        .score-table th {
            background: linear-gradient(135deg, #27ae60, #2ecc71);
            color: white;
            padding: 0.8rem;
            text-align: left;
            border-radius: 8px 8px 0 0;
            font-weight: 600;
        }

        .score-table td {
            padding: 0.8rem;
            border-bottom: 1px solid rgba(46, 204, 113, 0.1);
            transition: background 0.3s ease;
        }

        .score-table tr:hover td {
            background: rgba(46, 204, 113, 0.05);
        }

        .marks-calculation {
            background: linear-gradient(135deg, #e8f8f5 0%, #d5f4e6 100%);
            border-radius: 15px;
            padding: 2rem;
            margin: 2rem 0;
            border: 2px solid rgba(39, 174, 96, 0.3);
        }

        .calculation-title {
            font-size: 1.3rem;
            font-weight: 600;
            color: #27ae60;
            margin-bottom: 1.5rem;
            text-align: center;
        }

        .final-score {
            text-align: center;
            padding: 1.5rem;
            background: rgba(255, 255, 255, 0.8);
            border-radius: 10px;
            margin: 1rem 0;
        }

        .final-score-value {
            font-size: 2.5rem;
            font-weight: 700;
            color: #27ae60;
            margin-bottom: 0.5rem;
        }

        .best-scores {
            background: rgba(255, 255, 255, 0.6);
            border-radius: 10px;
            padding: 1rem;
            margin: 1rem 0;
            border-left: 4px solid #27ae60;
        }

        .action-button {
            background: linear-gradient(135deg, #27ae60, #2ecc71);
            color: white;
            padding: 1rem 2rem;
            border: none;
            border-radius: 50px;
            cursor: pointer;
            font-size: 1.1rem;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 6px 20px rgba(39, 174, 96, 0.3);
            text-decoration: none;
            display: inline-block;
            margin: 2rem auto;
        }

        .action-button:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(39, 174, 96, 0.4);
        }

        .button-container {
            text-align: center;
            margin-top: 2rem;
        }

        @media (max-width: 768px) {
            .profile-grid {
                grid-template-columns: 1fr;
            }
            
            .header h1 {
                font-size: 2rem;
            }
            
            .performance-grid {
                grid-template-columns: 1fr;
            }
        }

        .attendance-circle {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: conic-gradient(#27ae60 0deg <?= $attendance_percentage * 3.6 ?>deg, #e8f8f5 <?= $attendance_percentage * 3.6 ?>deg);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            position: relative;
        }

        .attendance-circle::before {
            content: '';
            width: 60px;
            height: 60px;
            background: white;
            border-radius: 50%;
            position: absolute;
        }

        .attendance-circle span {
            position: relative;
            font-weight: 700;
            color: #27ae60;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <div class="main-container">
        <header class="header">
            <h2>Student Dashboard</h2>
            <!-- <p>Welcome to your elegant academic profile</p> -->
        </header>

        <div class="profile-grid info-value">
            <div class="profile-card">
                <div class="card-title">Personal Information</div>
                <div class="info-item">
                    <span class="info-label">Full Name</span>
                    <span class="info-value"><?= htmlspecialchars($student['name']) ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Student ID</span>
                    <span class="info-value"><?= htmlspecialchars($student['student_id']) ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Email Address</span>
                    <span class="info-value"><?= htmlspecialchars($student['email']) ?></span>
                </div>
                <?php
                $phone = $student['phone_number'];
                if (strpos($phone, '0') !== 0) { // if first char is not '0'
                    $phone = '0' . $phone;
                }
                ?>
                <div class="info-item">
                    <span class="info-label">Phone Number</span>
                    <span class="info-value"><?= htmlspecialchars($phone) ?></span>
                </div>

            </div>

            <div class="profile-card">
                <div class="card-title">Attendance Overview</div>
                <div class="attendance-circle">
                    <span><?= $attendance_percentage ?>%</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Total Classes</span>
                    <span class="info-value"><?= $total_classes ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Classes Attended</span>
                    <span class="info-value"><?= $attended ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Attendance Rate</span>
                    <span class="metric-badge"><?= $attendance_percentage ?>%</span>
                </div>
            </div>
        </div>

        <div class="academic-section">
            <div class="card-title">Academic Performance</div>
            
            <div class="performance-grid">
                <div class="performance-card">
                    <div class="performance-title">Class Tests</div>
                    <table class="score-table">
                        <thead>
                            <tr><th>Assessment</th><th>Score</th></tr>
                        </thead>
                        <tbody>
                            <tr><td>Quiz 1</td><td><?= $marks['class_test_1'] ?? 0 ?>/20</td></tr>
                            <tr><td>Quiz 2</td><td><?= $marks['class_test_2'] ?? 0 ?>/20</td></tr>
                            <tr><td>Quiz 3</td><td><?= $marks['class_test_3'] ?? 0 ?>/20</td></tr>
                        </tbody>
                    </table>
                </div>

                <div class="performance-card">
                    <div class="performance-title">Assignments</div>
                    <table class="score-table">
                        <thead>
                            <tr><th>Assignment</th><th>Score</th></tr>
                        </thead>
                        <tbody>
                            <tr><td>Assignment 1</td><td><?= $marks['assignment_1'] ?? 0 ?>/20</td></tr>
                            <tr><td>Assignment 2</td><td><?= $marks['assignment_2'] ?? 0 ?>/10</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="marks-calculation">
                <div class="calculation-title">Final Grade Calculation</div>
                
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
                $academicMark = ceil($bestThree / 3);
                
                // Calculate attendance marks
                $attendanceMark = ($attendance_percentage >= 80) ? 10 : floor(($attendance_percentage / 80) * 10);
                
                // Final total marks
                $finalTotal = $academicMark + $attendanceMark;
                ?>

                <div class="info-item">
                    <span class="info-label">Academic Performance (Best 3)</span>
                    <span class="metric-badge"><?= $academicMark ?>/20</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Attendance Marks</span>
                    <span class="metric-badge"><?= $attendanceMark ?>/10</span>
                </div>

                <div class="final-score">
                    <div class="final-score-value"><?= $finalTotal ?>/30</div>
                    <div>Total Grade</div>
                </div>

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
                
                <div class="best-scores">
                    <strong>Best 3 Scores Considered:</strong><br>
                    <?php echo implode(', ', array_map(function($name, $score) { 
                        return "$name: $score"; 
                    }, array_keys($bestThree), $bestThree)); ?>
                </div>
            </div>
        </div>

        <div class="button-container">
            <a href="review.php" class="action-button">Submit Course Review</a>
        </div>
    </div>
</body>
</html>