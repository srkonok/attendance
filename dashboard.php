<?php
// Include database connection
include 'db.php';

if (!isset($_SESSION["user"])) {
    header("Location: login.php");
    exit();
}

// Get total number of students
$totalStudentsQuery = "SELECT COUNT(*) FROM students";
$totalStudentsStmt = $conn->prepare($totalStudentsQuery);
$totalStudentsStmt->execute();
$totalStudents = $totalStudentsStmt->fetchColumn();

// Get today's present students
$todayDate = date('Y-m-d');
$presentStudentsQuery = "SELECT COUNT(DISTINCT student_id) FROM attendance WHERE date = :date";
$presentStudentsStmt = $conn->prepare($presentStudentsQuery);
$presentStudentsStmt->execute(['date' => $todayDate]);
$presentStudents = $presentStudentsStmt->fetchColumn();

// Calculate attendance percentage
$attendancePercentage = ($totalStudents > 0) ? ($presentStudents / $totalStudents) * 100 : 0;

// Get last class attendance (attendance for the most recent date before today)
$lastClassQuery = "SELECT date, COUNT(DISTINCT student_id) as count FROM attendance WHERE date < :today GROUP BY date ORDER BY date DESC LIMIT 1";
$lastClassStmt = $conn->prepare($lastClassQuery);
$lastClassStmt->execute(['today' => $todayDate]);
$lastClass = $lastClassStmt->fetch(PDO::FETCH_ASSOC);

$lastClassDate = $lastClass ? $lastClass['date'] : 'N/A';
$lastClassAttendance = $lastClass ? $lastClass['count'] : 0;

// Fetch day-wise attendance data for graph
$graphQuery = "SELECT date, COUNT(DISTINCT student_id) as count FROM attendance GROUP BY date ORDER BY date ASC";
$graphStmt = $conn->prepare($graphQuery);
$graphStmt->execute();
$graphData = $graphStmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Attendance Dashboard</title>
  <link rel="stylesheet" href="style.css" />
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <style>
    body {
      font-family: Arial, sans-serif;
      background-color: #f4f4f4;
      margin: 0;
      padding: 20px;
    }
    /* Wrapper for consistent width */
    .wrapper {
      max-width: 600px; /* Sum of two card widths */
      margin: 0 auto;
      padding: 0 10px;
    }
    /* 2x2 Grid for cards */
    .dashboard {
      display: grid;
      grid-template-columns: repeat(2, 1fr);
      gap: 20px;
      margin-bottom: 20px;
    }
    .card {
      background: #fff;
      padding: 20px;
      border-radius: 8px;
      box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
      text-align: center;
    }
    .card h3 {
      margin-bottom: 10px;
      color: #333;
      font-size: 1.2em;
    }
    .card p {
      font-size: 1.4em;
      font-weight: bold;
      margin: 0;
    }
    .card small {
      display: block;
      margin-top: 5px;
      color: #666;
      font-size: 0.9em;
    }
    /* Chart container matching the wrapper width */
    .chart-container {
      background: #fff;
      padding: 10px;
      border-radius: 8px;
      box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
      width: 100%; /* Same as wrapper (600px max) */
      height: 300px; /* Fixed height for desktop */
      margin: 20px auto;
    }
    canvas {
      max-width: 100%;
      height: 100%;
    }
    /* Mobile adjustments */
    @media (max-width: 600px) {
      .dashboard {
        grid-template-columns: repeat(2, 1fr);
      }
      .card h3 {
        font-size: 1em;
      }
      .card p {
        font-size: 1.2em;
      }
      .chart-container {
        height: 250px;
      }
    }
  </style>
</head>
<body>
  <div class="wrapper" style="margin-top: 20px;">
    <div class="dashboard">
      <div class="card">
        <h3>Total Students</h3>
        <p><?php echo $totalStudents; ?></p>
      </div>
      <div class="card">
        <h3>Today's Present</h3>
        <p><?php echo $presentStudents; ?></p>
      </div>
      <div class="card">
        <h3>Attendance Percentage</h3>
        <p><?php echo number_format($attendancePercentage, 2); ?>%</p>
      </div>
      <div class="card">
        <h3>Last Class Attendance</h3>
        <p><?php echo $lastClassAttendance; ?></p>
        <?php if ($lastClassDate !== 'N/A'): ?>
           <small><?php echo date('F j, Y', strtotime($lastClassDate)); ?></small>
        <?php endif; ?>
      </div>
    </div>
  
    <div class="chart-container">
      <canvas id="attendanceChart"></canvas>
    </div>
  </div>
  
  <script>
    const ctx = document.getElementById('attendanceChart').getContext('2d');
    const attendanceChart = new Chart(ctx, {
      type: 'bar',
      data: {
        labels: <?php echo json_encode(array_column($graphData, 'date')); ?>,
        datasets: [{
          label: 'Daily Attendance',
          data: <?php echo json_encode(array_column($graphData, 'count')); ?>,
          backgroundColor: 'rgba(48, 150, 48, 0.8)',
          borderColor: 'rgba(0, 128, 0, 1)',
          borderWidth: 1
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
          y: {
            beginAtZero: true
          }
        }
      }
    });
  </script>
</body>
</html>
