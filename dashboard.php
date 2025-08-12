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

// Fetch day-wise attendance data for graph (last 14 days)
$graphQuery = "SELECT date, COUNT(DISTINCT student_id) as count FROM attendance WHERE date >= DATE_SUB(CURDATE(), INTERVAL 90 DAY) GROUP BY date ORDER BY date ASC";
$graphStmt = $conn->prepare($graphQuery);
$graphStmt->execute();
$graphData = $graphStmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate absent students for today
$absentStudents = $totalStudents - $presentStudents;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <title>Attendance Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>

<style>
/* Mobile-First CSS Reset */
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
    min-height: 100vh;
    color: #1e293b;
    -webkit-font-smoothing: antialiased;
    -moz-osx-font-smoothing: grayscale;
    overflow-x: hidden;
}

/* Dashboard Container - Mobile First */
.dashboard-wrapper {
    width: 100%;
    max-width: 100vw;
    padding: 10px;
    margin: 0;
    animation: fadeInUp 0.6s ease-out;
}

/* Dashboard Title - Mobile Optimized */
.dashboard-title {
    text-align: center;
    font-size: 1.3rem;
    font-weight: 700;
    color: #059669;
    margin-bottom: 15px;
    padding: 0 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    line-height: 1.3;
}

.dashboard-title i {
    font-size: 1.2rem;
    color: #10b981;
}

/* Mobile-First Stats Grid */
.stats-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10px;
    margin-bottom: 20px;
    width: 100%;
}

.stat-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 12px 8px;
    text-align: center;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
    position: relative;
    overflow: hidden;
    min-height: 80px;
    display: flex;
    flex-direction: column;
    justify-content: center;
    touch-action: manipulation;
}

.stat-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 3px;
    background: #10b981;
}

.stat-card:active {
    transform: scale(0.98);
}

.stat-card h3 {
    font-size: 0.7rem;
    color: #64748b;
    margin-bottom: 6px;
    font-weight: 600;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 4px;
    line-height: 1.2;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.stat-card h3 i {
    font-size: 0.8rem;
    color: #10b981;
}

.stat-card .stat-number {
    font-size: 1.4rem;
    font-weight: 700;
    color: #059669;
    margin-bottom: 4px;
    line-height: 1;
}

.stat-card .stat-subtitle {
    font-size: 0.6rem;
    color: #94a3b8;
    margin: 0;
    line-height: 1.2;
}

.stat-card.percentage .stat-number {
    color: #10b981;
}

/* Mobile Chart Section */
.chart-section {
    display: flex;
    flex-direction: column;
    gap: 15px;
    margin-top: 20px;
}

.chart-container {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 15px;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    order: 2;
}

.chart-container h3 {
    color: #059669;
    font-size: 1rem;
    margin-bottom: 10px;
    display: flex;
    align-items: center;
    gap: 6px;
    font-weight: 700;
}

.chart-container h3 i {
    color: #10b981;
}

.mini-chart-container {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 15px;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    order: 1;
}

.mini-chart-container h3 {
    color: #059669;
    font-size: 1rem;
    margin-bottom: 10px;
    display: flex;
    align-items: center;
    gap: 6px;
    text-align: center;
    justify-content: center;
    font-weight: 700;
}

.mini-chart-container h3 i {
    color: #10b981;
}

/* Mobile Progress Circle */
.progress-circle {
    position: relative;
    width: 100px;
    height: 100px;
    margin: 10px auto;
}

.progress-circle svg {
    transform: rotate(-90deg);
    width: 100%;
    height: 100%;
}

.progress-circle .progress-bg {
    fill: none;
    stroke: #e2e8f0;
    stroke-width: 8;
}

.progress-circle .progress-fill {
    fill: none;
    stroke: #10b981;
    stroke-width: 8;
    stroke-linecap: round;
    stroke-dasharray: 314;
    stroke-dashoffset: 314;
    transition: stroke-dashoffset 1.5s ease-out;
}

.progress-text {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    font-size: 1.2rem;
    font-weight: 700;
    color: #059669;
}

/* Mobile Quick Stats */
.quick-stats {
    margin-top: 15px;
}

.quick-stat-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px 8px;
    border-bottom: 1px solid #f1f5f9;
    transition: background 0.2s ease;
    touch-action: manipulation;
}

.quick-stat-item:active {
    background: #f0fdf4;
}

.quick-stat-item:last-child {
    border-bottom: none;
}

.quick-stat-item .label {
    color: #64748b;
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 0.8rem;
    font-weight: 500;
}

.quick-stat-item .label i {
    font-size: 0.7rem;
    color: #10b981;
}

.quick-stat-item .value {
    color: #059669;
    font-weight: 700;
    font-size: 0.9rem;
}

/* Chart Canvas Sizing for Mobile */
#attendanceChart {
    height: 200px !important;
    max-height: 200px !important;
}

/* Loading Animation */
.chart-loading {
    display: flex;
    align-items: center;
    justify-content: center;
    height: 200px;
    color: #64748b;
    font-size: 0.8rem;
}

.chart-loading .spinner {
    border: 3px solid #e2e8f0;
    border-left: 3px solid #10b981;
    border-radius: 50%;
    width: 25px;
    height: 25px;
    animation: spin 1s linear infinite;
    margin-right: 10px;
}

/* Tablet Styles */
@media (min-width: 768px) {
    .dashboard-wrapper {
        max-width: 1200px;
        margin: 20px auto;
        padding: 20px;
    }
    
    .dashboard-title {
        font-size: 2rem;
        margin-bottom: 25px;
        gap: 12px;
    }
    
    .dashboard-title i {
        font-size: 1.8rem;
    }
    
    .stats-grid {
        grid-template-columns: repeat(4, 1fr);
        gap: 15px;
        margin-bottom: 30px;
    }
    
    .stat-card {
        padding: 20px;
        min-height: 120px;
        border-radius: 16px;
    }
    
    .stat-card h3 {
        font-size: 0.9rem;
        margin-bottom: 10px;
        gap: 6px;
    }
    
    .stat-card .stat-number {
        font-size: 2rem;
        margin-bottom: 6px;
    }
    
    .stat-card .stat-subtitle {
        font-size: 0.75rem;
    }
    
    .chart-section {
        display: grid;
        grid-template-columns: 2fr 1fr;
        gap: 20px;
        margin-top: 30px;
    }
    
    .chart-container {
        padding: 20px;
        order: 1;
    }
    
    .mini-chart-container {
        padding: 20px;
        order: 2;
    }
    
    .chart-container h3,
    .mini-chart-container h3 {
        font-size: 1.2rem;
        margin-bottom: 15px;
        gap: 8px;
    }
    
    .progress-circle {
        width: 120px;
        height: 120px;
        margin: 15px auto;
    }
    
    .progress-text {
        font-size: 1.4rem;
    }
    
    #attendanceChart {
        height: 350px !important;
        max-height: 350px !important;
    }
    
    .quick-stat-item {
        padding: 12px 0;
    }
    
    .quick-stat-item .label {
        font-size: 0.85rem;
    }
    
    .quick-stat-item .value {
        font-size: 1rem;
    }
}

/* Desktop Styles */
@media (min-width: 1024px) {
    .stat-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
        border-color: #10b981;
    }
    
    .quick-stat-item:hover {
        background: #f0fdf4;
    }
}

/* Animation Keyframes */
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

/* Reduce motion for accessibility */
@media (prefers-reduced-motion: reduce) {
    *, *::before, *::after {
        animation-duration: 0.01ms !important;
        animation-iteration-count: 1 !important;
        transition-duration: 0.01ms !important;
    }
}
</style>

<div class="dashboard-wrapper">
    <h2 class="dashboard-title">
        <i class="fas fa-chart-line"></i>
        Attendance Dashboard
    </h2>

    <div class="stats-grid">
        <div class="stat-card">
            <h3><i class="fas fa-users"></i> Total Students</h3>
            <div class="stat-number" data-count="<?php echo $totalStudents; ?>">0</div>
            <p class="stat-subtitle">Registered</p>
        </div>

        <div class="stat-card">
            <h3><i class="fas fa-user-check"></i> Present Today</h3>
            <div class="stat-number" data-count="<?php echo $presentStudents; ?>">0</div>
            <p class="stat-subtitle"><?php echo date('M j'); ?></p>
        </div>

        <div class="stat-card percentage">
            <h3><i class="fas fa-percentage"></i> Rate</h3>
            <div class="stat-number" data-count="<?php echo number_format($attendancePercentage, 1); ?>">0</div>
            <p class="stat-subtitle">Percentage</p>
        </div>

        <div class="stat-card">
            <h3><i class="fas fa-calendar-alt"></i> Last Class</h3>
            <div class="stat-number" data-count="<?php echo $lastClassAttendance; ?>">0</div>
            <p class="stat-subtitle"><?php echo $lastClassDate !== 'N/A' ? date('M j', strtotime($lastClassDate)) : 'N/A'; ?></p>
        </div>
    </div>

    <div class="chart-section">
        <div class="mini-chart-container">
            <h3><i class="fas fa-chart-pie"></i> Today's Overview</h3>
            
            <div class="progress-circle">
                <svg>
                    <circle cx="50" cy="50" r="45" class="progress-bg"></circle>
                    <circle cx="50" cy="50" r="45" class="progress-fill"></circle>
                </svg>
                <div class="progress-text"><?php echo number_format($attendancePercentage, 1); ?>%</div>
            </div>

            <div class="quick-stats">
                <div class="quick-stat-item">
                    <span class="label"><i class="fas fa-user-plus"></i> Present</span>
                    <span class="value"><?php echo $presentStudents; ?></span>
                </div>
                <div class="quick-stat-item">
                    <span class="label"><i class="fas fa-user-minus"></i> Absent</span>
                    <span class="value"><?php echo $absentStudents; ?></span>
                </div>
                <div class="quick-stat-item">
                    <span class="label"><i class="fas fa-users"></i> Total</span>
                    <span class="value"><?php echo $totalStudents; ?></span>
                </div>
                <div class="quick-stat-item">
                    <span class="label"><i class="fas fa-history"></i> Previous</span>
                    <span class="value"><?php echo $lastClassAttendance; ?></span>
                </div>
            </div>
        </div>

        <div class="chart-container">
            <h3><i class="fas fa-chart-bar"></i> Daily Trend (Last 14 Days)</h3>
            <div class="chart-loading" id="chartLoading">
                <div class="spinner"></div>
                Loading chart...
            </div>
            <canvas id="attendanceChart" style="display: none;"></canvas>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Get chart data from PHP
    const chartData = <?php echo json_encode($graphData); ?>;
    const chartLabels = chartData.map(item => {
        const date = new Date(item.date);
        return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
    });
    const chartValues = chartData.map(item => parseInt(item.count));
    
    // Get actual percentage for progress circle
    const actualPercentage = <?php echo $attendancePercentage; ?>;

    // Animate numbers
    function animateValue(element, start, end, duration) {
        const startTime = performance.now();
        const isPercentage = element.closest('.percentage');
        
        function update(currentTime) {
            const elapsed = currentTime - startTime;
            const progress = Math.min(elapsed / duration, 1);
            
            // Use easing function
            const easeProgress = 1 - Math.pow(1 - progress, 3);
            const current = start + (end - start) * easeProgress;
            
            if (isPercentage) {
                element.textContent = current.toFixed(1) + '%';
            } else {
                element.textContent = Math.floor(current);
            }
            
            if (progress < 1) {
                requestAnimationFrame(update);
            }
        }
        
        requestAnimationFrame(update);
    }

    // Start number animations
    document.querySelectorAll('.stat-number').forEach(stat => {
        const target = parseFloat(stat.dataset.count);
        animateValue(stat, 0, target, 1500);
    });

    // Progress circle animation
    const circle = document.querySelector('.progress-fill');
    const radius = 45;
    const circumference = 2 * Math.PI * radius;
    
    circle.style.strokeDasharray = circumference;
    circle.style.strokeDashoffset = circumference;
    
    setTimeout(() => {
        const offset = circumference - (actualPercentage / 100) * circumference;
        circle.style.strokeDashoffset = offset;
    }, 500);

    // Chart.js initialization
    setTimeout(() => {
        document.getElementById('chartLoading').style.display = 'none';
        document.getElementById('attendanceChart').style.display = 'block';

        const ctx = document.getElementById('attendanceChart').getContext('2d');
        
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: chartLabels,
                datasets: [{
                    label: 'Daily Attendance',
                    data: chartValues,
                    backgroundColor: 'rgba(16, 185, 129, 0.1)',
                    borderColor: '#10b981',
                    borderWidth: window.innerWidth < 768 ? 3 : 4,
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: '#10b981',
                    pointBorderColor: '#ffffff',
                    pointBorderWidth: 2,
                    pointRadius: window.innerWidth < 768 ? 4 : 6,
                    pointHoverRadius: window.innerWidth < 768 ? 6 : 8,
                    pointHoverBackgroundColor: '#059669',
                    pointHoverBorderColor: '#ffffff',
                    pointHoverBorderWidth: 3
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    intersect: false,
                    mode: 'index'
                },
                plugins: {
                    legend: {
                        display: window.innerWidth >= 768,
                        labels: { 
                            color: '#64748b', 
                            font: { size: 12 },
                            usePointStyle: true
                        }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(255, 255, 255, 0.95)',
                        titleColor: '#1e293b',
                        bodyColor: '#059669',
                        borderColor: '#10b981',
                        borderWidth: 1,
                        cornerRadius: 8,
                        callbacks: {
                            title: function(context) {
                                return 'Date: ' + context[0].label;
                            },
                            label: function(context) {
                                return 'Students Present: ' + context.parsed.y;
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: { 
                            color: 'rgba(226, 232, 240, 0.5)',
                            drawOnChartArea: window.innerWidth >= 768
                        },
                        ticks: { 
                            color: '#64748b',
                            font: { size: window.innerWidth < 768 ? 10 : 12 },
                            maxTicksLimit: window.innerWidth < 768 ? 6 : 10
                        }
                    },
                    y: {
                        beginAtZero: true,
                        grid: { 
                            color: 'rgba(226, 232, 240, 0.5)',
                            drawOnChartArea: window.innerWidth >= 768
                        },
                        ticks: { 
                            color: '#64748b',
                            font: { size: window.innerWidth < 768 ? 10 : 12 }
                        }
                    }
                },
                elements: {
                    point: {
                        hitRadius: 15
                    }
                }
            }
        });
    }, 1000);

    // Handle orientation changes
    window.addEventListener('orientationchange', function() {
        setTimeout(() => {
            location.reload();
        }, 100);
    });
});
</script>

</body>
</html>