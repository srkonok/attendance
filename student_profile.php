<?php
// student-profile.php
session_start();
require_once 'db.php';

if (!isset($_SESSION['student_id'])) {
    die("Access Denied: No Student ID Found");
}

$student_id = $_SESSION['student_id'];

// Fetch student details
$stmt = $conn->prepare("SELECT * FROM students WHERE student_id = :student_id");
$stmt->bindParam(':student_id', $student_id);
$stmt->execute();
$student = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$student) die("Student not found.");

// Fetch attendance data
$stmt = $conn->prepare("SELECT COUNT(DISTINCT date) AS total FROM attendance");
$stmt->execute();
$total_classes = $stmt->fetchColumn() ?: 0;

$stmt = $conn->prepare("SELECT COUNT(DISTINCT date) AS attended FROM attendance WHERE student_id = :student_id");
$stmt->bindParam(':student_id', $student_id);
$stmt->execute();
$attended = $stmt->fetchColumn() ?: 0;

$attendance_percentage = $total_classes ? round(($attended / $total_classes) * 100, 2) : 0;

// Fetch marks
$stmt = $conn->prepare("SELECT * FROM student_marks WHERE student_id = :student_id");
$stmt->bindParam(':student_id', $student_id);
$stmt->execute();
$marks = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

// Fetch review requests
$stmt = $conn->prepare("SELECT * FROM review_requests WHERE student_id = :student_id");
$stmt->bindParam(':student_id', $student_id);
$stmt->execute();
$requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Profile</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f4f4; margin: 20px; }
        .container { max-width: 800px; background: white; padding: 25px; border-radius: 10px; box-shadow: 0 0 15px rgba(0,0,0,0.1); margin: 0 auto; }
        table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        th, td { padding: 12px; border: 1px solid #ddd; text-align: center; }
        th { background: #28a745; color: white; }
        .status.pending { color: #dc3545; font-weight: bold; }
        .status.reviewed { color: #28a745; font-weight: bold; }
        form { margin: 20px 0; padding: 15px; background: #f8f9fa; border-radius: 5px; }
        select, button { padding: 8px 15px; margin: 5px; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Student Profile</h2>
        <p><strong>Name:</strong> <?= htmlspecialchars($student['name']) ?></p>
        <p><strong>ID:</strong> <?= htmlspecialchars($student['student_id']) ?></p>

        <!-- Attendance and Marks Tables (same as before) -->

        <h3>Review Requests</h3>
        <table>
            <tr>
                <th>Type</th>
                <th>Status</th>
                <th>Request Date</th>
                <th>Last Updated</th>
            </tr>
            <?php foreach ($requests as $request): ?>
            <tr>
                <td><?= htmlspecialchars(str_replace('_', ' ', $request['type'])) ?></td>
                <td class="status <?= htmlspecialchars($request['status']) ?>">
                    <?= htmlspecialchars(ucfirst($request['status'])) ?>
                </td>
                <td><?= htmlspecialchars(date('m/d/Y h:i A', strtotime($request['created_at']))) ?></td>
                <td><?= htmlspecialchars(date('m/d/Y h:i A', strtotime($request['updated_at']))) ?></td>
            </tr>
            <?php endforeach; ?>
        </table>

        <h3>Request Review</h3>
        <form id="reviewRequestForm">
            <select id="type" name="type" required>
                <option value="">Select Review Type</option>
                <option value="Quiz_1">Quiz 1</option>
                <option value="Quiz_2">Quiz 2</option>
                <option value="Quiz_3">Quiz 3</option>
                <option value="Assignment_1">Assignment 1</option>
                <option value="Assignment_2">Assignment 2</option>
                <option value="Attendance">Attendance</option>
            </select>
            <button type="submit">Submit Request</button>
        </form>
    </div>

    <script>
        document.getElementById('reviewRequestForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            fetch('insert-review-request.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    student_id: '<?= $student_id ?>',
                    type: document.getElementById('type').value
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Review request submitted successfully!');
                    location.reload();
                } else {
                    alert('Error: ' + (data.error || 'Unknown error'));
                }
            })
            .catch(error => console.error('Error:', error));
        });
    </script>
</body>
</html>