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
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <title>Student Profile - Cloud Computing Attendance</title>
    <link rel="icon" href="images/favicon_io/favicon.ico" type="image/x-icon">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="index.css">
    <style>
        /* Profile-specific styles that extend the main theme */
        .profile-specific-styles {
            /* This ensures our custom styles work with the existing theme */
        }

        .main-container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .profile-header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border: 2px solid rgba(5, 150, 105, 0.2);
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 20px 40px rgba(5, 150, 105, 0.15);
            animation: fadeInUp 0.8s ease-out;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .profile-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #059669, #10b981, #34d399);
        }

        .profile-header h1 {
            font-size: 2.2rem;
            font-weight: 700;
            background: linear-gradient(135deg, #059669, #10b981);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
        }

        .profile-header p {
            color: #6b7280;
            font-size: 1.1rem;
            margin-bottom: 0;
        }

        .profile-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 30px;
        }

        .profile-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border: 2px solid rgba(5, 150, 105, 0.2);
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 20px 40px rgba(5, 150, 105, 0.15);
            animation: fadeInUp 0.8s ease-out;
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
            background: linear-gradient(90deg, #059669, #10b981, #34d399);
        }

        .profile-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 25px 50px rgba(5, 150, 105, 0.2);
        }

        .card-title {
            font-size: 1.6rem;
            font-weight: 600;
            color: #059669;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .info-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 0;
            border-bottom: 1px solid rgba(5, 150, 105, 0.1);
            transition: all 0.3s ease;
        }

        .info-item:last-child {
            border-bottom: none;
        }

        .info-item:hover {
            background: rgba(5, 150, 105, 0.05);
            margin: 0 -15px;
            padding: 15px 15px;
            border-radius: 10px;
        }

        .info-label {
            font-weight: 500;
            color: #6b7280;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .info-value {
            font-weight: 600;
            color: #374151;
        }

        .metric-badge {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            padding: 8px 16px;
            border-radius: 25px;
            font-weight: 600;
            font-size: 0.9rem;
            box-shadow: 0 4px 15px rgba(5, 150, 105, 0.3);
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }

        .attendance-circle {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: conic-gradient(#10b981 0deg <?= $attendance_percentage * 3.6 ?>deg, rgba(5, 150, 105, 0.2) <?= $attendance_percentage * 3.6 ?>deg);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            position: relative;
            animation: rotateIn 1s ease-out;
        }

        @keyframes rotateIn {
            from { transform: rotate(-180deg) scale(0); }
            to { transform: rotate(0deg) scale(1); }
        }

        .attendance-circle::before {
            content: '';
            width: 75px;
            height: 75px;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 50%;
            position: absolute;
            backdrop-filter: blur(10px);
        }

        .attendance-circle span {
            position: relative;
            font-weight: 700;
            background: linear-gradient(135deg, #059669, #10b981);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            font-size: 1.1rem;
            z-index: 1;
        }

        .academic-section {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border: 2px solid rgba(5, 150, 105, 0.2);
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 20px 40px rgba(5, 150, 105, 0.15);
            animation: fadeInUp 0.8s ease-out;
            position: relative;
            overflow: hidden;
        }

        .academic-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #059669, #10b981, #34d399);
        }

        .performance-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 25px;
            margin: 25px 0;
        }

        .performance-card {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(15px);
            border: 2px solid rgba(5, 150, 105, 0.2);
            border-radius: 15px;
            padding: 25px;
            transition: all 0.3s ease;
            box-shadow: 0 10px 25px rgba(5, 150, 105, 0.1);
        }

        .performance-card:hover {
            border-color: rgba(5, 150, 105, 0.4);
            transform: translateY(-3px);
            box-shadow: 0 15px 35px rgba(5, 150, 105, 0.2);
        }

        .performance-title {
            font-size: 1.3rem;
            font-weight: 600;
            color: #059669;
            margin-bottom: 20px;
            text-align: center;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .score-table {
            width: 100%;
            border-collapse: collapse;
            overflow: hidden;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(5, 150, 105, 0.1);
        }

        .score-table th {
            background: rgba(5, 150, 105, 0.1);
            color: #059669;
            padding: 15px;
            text-align: left;
            font-weight: 600;
            border-bottom: 2px solid rgba(5, 150, 105, 0.2);
        }

        .score-table td {
            padding: 15px;
            border-bottom: 1px solid rgba(5, 150, 105, 0.1);
            transition: all 0.3s ease;
            color: #374151;
        }

        .score-table tr:hover td {
            background: rgba(5, 150, 105, 0.05);
        }

        .marks-calculation {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(15px);
            border: 2px solid rgba(5, 150, 105, 0.3);
            border-radius: 20px;
            padding: 30px;
            margin: 25px 0;
            box-shadow: 0 15px 35px rgba(5, 150, 105, 0.15);
        }

        .calculation-title {
            font-size: 1.6rem;
            font-weight: 600;
            background: linear-gradient(135deg, #059669, #10b981);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 25px;
            text-align: center;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
        }

        .final-score {
            text-align: center;
            padding: 30px;
            background: rgba(255, 255, 255, 0.8);
            border-radius: 15px;
            margin: 20px 0;
            border: 2px solid rgba(5, 150, 105, 0.2);
            position: relative;
            overflow: hidden;
        }

        .final-score::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, #059669, #10b981, #34d399);
        }

        .final-score-value {
            font-size: 3rem;
            font-weight: 700;
            background: linear-gradient(135deg, #059669, #10b981);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 10px;
            animation: countUp 2s ease-out;
        }

        @keyframes countUp {
            from { transform: scale(0); }
            to { transform: scale(1); }
        }

        .final-score div:last-child {
            color: #6b7280;
            font-size: 1.1rem;
            font-weight: 500;
        }

        .best-scores {
            background: rgba(255, 255, 255, 0.8);
            border-radius: 12px;
            padding: 20px;
            margin: 20px 0;
            border-left: 4px solid #10b981;
            box-shadow: 0 5px 15px rgba(5, 150, 105, 0.1);
        }

        .best-scores strong {
            color: #059669;
            font-size: 1.1rem;
        }

        .action-button {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            padding: 15px 30px;
            border: none;
            border-radius: 50px;
            cursor: pointer;
            font-size: 1.1rem;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 10px 25px rgba(5, 150, 105, 0.3);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            position: relative;
            overflow: hidden;
        }

        .action-button::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s;
        }

        .action-button:hover::before {
            left: 100%;
        }

        .action-button:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 35px rgba(5, 150, 105, 0.4);
        }

        .button-container {
            text-align: center;
            margin-top: 30px;
        }

        .back-button {
            background: rgba(255, 255, 255, 0.9);
            color: #059669;
            border: 2px solid #10b981;
            margin-bottom: 20px;
        }

        .back-button:hover {
            background: #10b981;
            color: white;
        }

        /* Mobile Responsive */
        @media (max-width: 768px) {
            .profile-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }
            
            .profile-header h1 {
                font-size: 1.8rem;
                flex-direction: column;
                gap: 5px;
            }
            
            .performance-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }

            .info-item {
                flex-direction: column;
                align-items: flex-start;
                gap: 8px;
            }

            .info-value {
                align-self: flex-end;
                word-break: break-word;
            }

            .final-score-value {
                font-size: 2.5rem;
            }

            .main-container {
                margin: 20px auto;
                padding: 0 15px;
            }

            .profile-card, .academic-section, .marks-calculation {
                padding: 20px;
            }

            .attendance-circle {
                width: 80px;
                height: 80px;
            }

            .attendance-circle::before {
                width: 60px;
                height: 60px;
            }
        }

        @media (max-width: 480px) {
            .info-item {
                padding: 12px 0;
            }

            .card-title, .performance-title, .calculation-title {
                font-size: 1.3rem;
            }

            .score-table th, .score-table td {
                padding: 10px 8px;
                font-size: 0.9rem;
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

    <div class="main-container">
        <div class="button-container">
            <a href="index.php" class="action-button back-button">
                <i class="fas fa-arrow-left"></i>
                Back to Dashboard
            </a>
        </div>

        <header class="profile-header">
            <h1>
                <i class="fas fa-user-graduate"></i>
                Student Profile
            </h1>
            <!-- <p>Your comprehensive academic dashboard</p> -->
        </header>

        <div class="profile-grid">
            <div class="profile-card">
                <div class="card-title">
                    <i class="fas fa-id-card"></i>
                    Personal Information
                </div>
                <div class="info-item">
                    <span class="info-label">
                        <i class="fas fa-user"></i>
                        Full Name
                    </span>
                    <span class="info-value"><?= htmlspecialchars($student['name']) ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">
                        <i class="fas fa-id-badge"></i>
                        Student ID
                    </span>
                    <span class="info-value"><?= htmlspecialchars($student['student_id']) ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">
                        <i class="fas fa-envelope"></i>
                        Email Address
                    </span>
                    <span class="info-value"><?= htmlspecialchars($student['email']) ?></span>
                </div>
                <?php
                $phone = $student['phone_number'];
                if (strpos($phone, '0') !== 0) {
                    $phone = '0' . $phone;
                }
                ?>
                <div class="info-item">
                    <span class="info-label">
                        <i class="fas fa-phone"></i>
                        Phone Number
                    </span>
                    <span class="info-value"><?= htmlspecialchars($phone) ?></span>
                </div>
            </div>

            <div class="profile-card">
                <div class="card-title">
                    <i class="fas fa-calendar-check"></i>
                    Attendance Overview
                </div>
                <div class="attendance-circle">
                    <span><?= $attendance_percentage ?>%</span>
                </div>
                <div class="info-item">
                    <span class="info-label">
                        <i class="fas fa-chalkboard-teacher"></i>
                        Total Classes
                    </span>
                    <span class="info-value"><?= $total_classes ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">
                        <i class="fas fa-user-check"></i>
                        Classes Attended
                    </span>
                    <span class="info-value"><?= $attended ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">
                        <i class="fas fa-percentage"></i>
                        Attendance Rate
                    </span>
                    <span class="metric-badge"><?= $attendance_percentage ?>%</span>
                </div>
            </div>
        </div>

        <div class="academic-section">
            <div class="card-title">
                <i class="fas fa-chart-line"></i>
                Academic Performance
            </div>
            
            <div class="performance-grid">
                <div class="performance-card">
                    <div class="performance-title">
                        <i class="fas fa-clipboard-question"></i>
                        Class Tests
                    </div>
                    <table class="score-table">
                        <thead>
                            <tr>
                                <th><i class="fas fa-tasks"></i> Assessment</th>
                                <th><i class="fas fa-star"></i> Score</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr><td>Quiz 1</td><td><?= $marks['class_test_1'] ?? 0 ?>/20</td></tr>
                            <tr><td>Quiz 2</td><td><?= $marks['class_test_2'] ?? 0 ?>/20</td></tr>
                            <tr><td>Quiz 3</td><td><?= $marks['class_test_3'] ?? 0 ?>/20</td></tr>
                        </tbody>
                    </table>
                </div>

                <div class="performance-card">
                    <div class="performance-title">
                        <i class="fas fa-file-alt"></i>
                        Assignments
                    </div>
                    <table class="score-table">
                        <thead>
                            <tr>
                                <th><i class="fas fa-clipboard-list"></i> Assignment</th>
                                <th><i class="fas fa-star"></i> Score</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr><td>Assignment 1</td><td><?= $marks['assignment_1'] ?? 0 ?>/20</td></tr>
                            <tr><td>Assignment 2</td><td><?= $marks['assignment_2'] ?? 0 ?>/10</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="marks-calculation">
                <div class="calculation-title">
                    <i class="fas fa-calculator"></i>
                    Final Grade Calculation
                </div>
                
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
                    <span class="info-label">
                        <i class="fas fa-brain"></i>
                        Academic Performance (Best 3)
                    </span>
                    <span class="metric-badge"><?= $academicMark ?>/20</span>
                </div>
                <div class="info-item">
                    <span class="info-label">
                        <i class="fas fa-calendar-check"></i>
                        Attendance Marks
                    </span>
                    <span class="metric-badge"><?= $attendanceMark ?>/10</span>
                </div>

                <div class="final-score">
                    <div class="final-score-value"><?= $finalTotal ?>/30</div>
                    <div><i class="fas fa-trophy"></i> Total Grade</div>
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
                    <strong><i class="fas fa-medal"></i> Best 3 Scores Considered:</strong><br>
                    <?php echo implode(', ', array_map(function($name, $score) { 
                        return "$name: $score"; 
                    }, array_keys($bestThree), $bestThree)); ?>
                </div>
            </div>
        </div>

        <div class="button-container">
            <a href="review.php" class="action-button">
                <i class="fas fa-comment-dots"></i>
                Submit Course Review
            </a>
        </div>
    </div>
</body>
</html>