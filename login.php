<?php
session_start();
include 'db.php'; // Include database connection

$admin_email = $_ENV['MAIL_FROM_ADDRESS'] ?? 'admin@example.com';
$admin_password = $_ENV['MANUAL_ATTENDANCE_PASSWORD'] ?? '2245';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST["username"];
    $password = $_POST["password"];
    
    try {
        if ($username === $admin_email && $password === $admin_password) {
            session_destroy(); // Clear previous session
            session_start(); // Start a new session
            $_SESSION["user"] = "admin";
            
            header("Location: index.php"); // Redirect to admin dashboard
            exit();
        }
        
        $stmt = $conn->prepare("SELECT * FROM students WHERE email = :email AND student_id = :student_id");
        $stmt->bindParam(":email", $username);
        $stmt->bindParam(":student_id", $password);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result) {
            $_SESSION["user"] = $username;
            $_SESSION['student_id'] = $password;
            header("Location: index.php"); // Redirect to dashboard
            exit();
        } else {
            $error = "Invalid email or student ID";
        }
    } catch (PDOException $e) {
        $error = "Database error: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CSE 4276: Class - Login</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #16a34a;
            --primary-light: #22c55e;
            --primary-dark: #15803d;
            --secondary-color: #166534;
            --accent-color: #059669;
            --success-color: #047857;
            --danger-color: #dc2626;
            --text-primary: #1f2937;
            --text-secondary: #374151;
            --text-light: #6b7280;
            --bg-primary: #ecfdf5;
            --bg-secondary: #ffffff;
            --border-color: #bbf7d0;
            --border-focus: #16a34a;
            --shadow-sm: 0 1px 2px 0 rgb(22 163 74 / 0.05);
            --shadow-md: 0 4px 6px -1px rgb(22 163 74 / 0.1);
            --shadow-lg: 0 10px 15px -3px rgb(22 163 74 / 0.1);
            --shadow-xl: 0 20px 25px -5px rgb(22 163 74 / 0.1);
            --border-radius: 12px;
            --border-radius-lg: 16px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #22c55e 0%, #16a34a 25%, #15803d 50%, #166534 75%, #14532d 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            position: relative;
            overflow: hidden;
        }

        /* Animated Background Elements */
        .bg-animation {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            overflow: hidden;
            z-index: 0;
        }

        .floating-shapes {
            position: absolute;
            width: 100%;
            height: 100%;
        }

        .shape {
            position: absolute;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.1);
            animation: float 15s infinite linear;
        }

        .shape:nth-child(1) {
            width: 80px;
            height: 80px;
            top: 10%;
            left: 10%;
            animation-delay: 0s;
            animation-duration: 20s;
        }

        .shape:nth-child(2) {
            width: 120px;
            height: 120px;
            top: 20%;
            right: 10%;
            animation-delay: 2s;
            animation-duration: 25s;
            animation-direction: reverse;
        }

        .shape:nth-child(3) {
            width: 60px;
            height: 60px;
            bottom: 30%;
            left: 20%;
            animation-delay: 4s;
            animation-duration: 18s;
        }

        .shape:nth-child(4) {
            width: 100px;
            height: 100px;
            bottom: 20%;
            right: 20%;
            animation-delay: 1s;
            animation-duration: 22s;
            animation-direction: reverse;
        }

        .shape:nth-child(5) {
            width: 140px;
            height: 140px;
            top: 50%;
            left: -70px;
            animation-delay: 3s;
            animation-duration: 30s;
        }

        .shape:nth-child(6) {
            width: 90px;
            height: 90px;
            top: 60%;
            right: -45px;
            animation-delay: 5s;
            animation-duration: 16s;
            animation-direction: reverse;
        }

        @keyframes float {
            0% {
                transform: translateY(0px) rotate(0deg);
                opacity: 0.7;
            }
            25% {
                transform: translateY(-20px) rotate(90deg);
                opacity: 1;
            }
            50% {
                transform: translateY(-40px) rotate(180deg);
                opacity: 0.8;
            }
            75% {
                transform: translateY(-20px) rotate(270deg);
                opacity: 0.9;
            }
            100% {
                transform: translateY(0px) rotate(360deg);
                opacity: 0.7;
            }
        }

        /* Wave Animation */
        .wave-container {
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 100px;
            overflow: hidden;
        }

        .wave {
            position: absolute;
            bottom: 0;
            left: 0;
            width: 200%;
            height: 100px;
            background: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 1000 100'%3E%3Cpath d='M0,50 Q250,0 500,50 T1000,50 L1000,100 L0,100 Z' fill='rgba(255,255,255,0.1)'/%3E%3C/svg%3E") repeat-x;
            animation: wave 10s linear infinite;
        }

        .wave:nth-child(2) {
            animation-delay: -2s;
            animation-duration: 12s;
            opacity: 0.7;
        }

        .wave:nth-child(3) {
            animation-delay: -4s;
            animation-duration: 8s;
            opacity: 0.5;
        }

        @keyframes wave {
            0% { transform: translateX(0); }
            100% { transform: translateX(-50%); }
        }

        /* Particle Animation */
        .particles {
            position: absolute;
            width: 100%;
            height: 100%;
        }

        .particle {
            position: absolute;
            width: 4px;
            height: 4px;
            background: rgba(255, 255, 255, 0.8);
            border-radius: 50%;
            animation: particle-float 15s infinite linear;
        }

        .particle:nth-child(odd) {
            background: rgba(22, 163, 74, 0.8);
        }

        @keyframes particle-float {
            0% {
                transform: translateY(100vh) rotate(0deg);
                opacity: 1;
            }
            100% {
                transform: translateY(-100px) rotate(360deg);
                opacity: 0;
            }
        }

        /* Generate particles */
        .particle:nth-child(1) { left: 10%; animation-delay: 0s; animation-duration: 12s; }
        .particle:nth-child(2) { left: 20%; animation-delay: 2s; animation-duration: 16s; }
        .particle:nth-child(3) { left: 30%; animation-delay: 4s; animation-duration: 14s; }
        .particle:nth-child(4) { left: 40%; animation-delay: 1s; animation-duration: 18s; }
        .particle:nth-child(5) { left: 50%; animation-delay: 3s; animation-duration: 15s; }
        .particle:nth-child(6) { left: 60%; animation-delay: 5s; animation-duration: 13s; }
        .particle:nth-child(7) { left: 70%; animation-delay: 2.5s; animation-duration: 17s; }
        .particle:nth-child(8) { left: 80%; animation-delay: 4.5s; animation-duration: 11s; }
        .particle:nth-child(9) { left: 90%; animation-delay: 1.5s; animation-duration: 19s; }

        .login-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: var(--border-radius-lg);
            padding: 3rem;
            box-shadow: var(--shadow-xl);
            border: 2px solid rgba(22, 163, 74, 0.3);
            width: 100%;
            max-width: 420px;
            position: relative;
            z-index: 10;
            animation: slideUp 0.8s cubic-bezier(0.4, 0, 0.2, 1);
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .login-header {
            text-align: center;
            margin-bottom: 2.5rem;
        }

        .logo-container {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, var(--primary-color), var(--primary-light));
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            box-shadow: var(--shadow-lg);
            animation: pulse 2s infinite;
            position: relative;
            overflow: hidden;
        }

        .logo-container::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.3) 0%, transparent 70%);
            animation: rotate 4s linear infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }

        @keyframes rotate {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .logo-container i {
            font-size: 2rem;
            color: white;
            z-index: 1;
            position: relative;
        }

        .login-title {
            font-size: 1.75rem;
            font-weight: 700;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 0.5rem;
        }

        .login-subtitle {
            font-size: 1rem;
            color: var(--text-secondary);
            font-weight: 400;
        }

        .form-container {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .input-group {
            position: relative;
        }

        .input-group label {
            display: block;
            font-size: 0.875rem;
            font-weight: 500;
            color: var(--text-primary);
            margin-bottom: 0.5rem;
        }

        .input-wrapper {
            position: relative;
        }

        .input-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-light);
            font-size: 1.125rem;
            transition: color 0.3s ease;
            z-index: 1;
        }

        input[type="email"],
        input[type="password"] {
            width: 100%;
            padding: 1rem 1rem 1rem 3rem;
            border: 2px solid var(--border-color);
            border-radius: var(--border-radius);
            font-size: 1rem;
            font-family: inherit;
            color: var(--text-primary);
            background: var(--bg-secondary);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            outline: none;
        }

        input[type="email"]:focus,
        input[type="password"]:focus {
            border-color: var(--border-focus);
            box-shadow: 0 0 0 3px rgba(22, 163, 74, 0.15);
            background: rgba(236, 253, 245, 0.7);
        }

        input[type="email"]:focus + .input-icon,
        input[type="password"]:focus + .input-icon {
            color: var(--primary-color);
        }

        .login-button {
            width: 100%;
            padding: 1rem;
            background: linear-gradient(135deg, var(--primary-color), var(--primary-light));
            border: none;
            border-radius: var(--border-radius);
            color: white;
            font-size: 1rem;
            font-weight: 600;
            font-family: inherit;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }

        .login-button::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s;
        }

        .login-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(22, 163, 74, 0.4);
            background: linear-gradient(135deg, var(--primary-light), var(--accent-color));
        }

        .login-button:hover::before {
            left: 100%;
        }

        .login-button:active {
            transform: translateY(0);
        }

        .error-message {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.2);
            color: var(--danger-color);
            padding: 1rem;
            border-radius: var(--border-radius);
            font-size: 0.875rem;
            font-weight: 500;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            animation: shake 0.5s ease-in-out;
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }

        .divider {
            display: flex;
            align-items: center;
            margin: 2rem 0 1.5rem;
            color: var(--text-light);
            font-size: 0.875rem;
        }

        .divider::before,
        .divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: linear-gradient(90deg, transparent, var(--border-color), transparent);
        }

        .divider span {
            padding: 0 1rem;
            background: var(--bg-secondary);
            color: var(--primary-color);
            font-weight: 500;
        }

        .login-footer {
            text-align: center;
            color: var(--text-secondary);
            font-size: 0.875rem;
        }

        .login-footer a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
        }

        .login-footer a:hover {
            color: var(--primary-dark);
        }

        /* Responsive Design */
        @media (max-width: 480px) {
            body {
                padding: 15px;
            }

            .login-container {
                padding: 2rem 1.5rem;
            }

            .login-title {
                font-size: 1.5rem;
            }

            .logo-container {
                width: 70px;
                height: 70px;
            }

            .logo-container i {
                font-size: 1.75rem;
            }

            input[type="email"],
            input[type="password"] {
                padding: 0.875rem 0.875rem 0.875rem 2.75rem;
                font-size: 0.9rem;
            }

            .input-icon {
                left: 0.875rem;
                font-size: 1rem;
            }
        }

        /* Loading state for button */
        .login-button.loading {
            pointer-events: none;
            opacity: 0.7;
        }

        .login-button.loading::after {
            content: '';
            position: absolute;
            width: 16px;
            height: 16px;
            margin: auto;
            border: 2px solid transparent;
            border-top-color: #ffffff;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
        }

        ::-webkit-scrollbar-track {
            background: var(--bg-primary);
        }

        ::-webkit-scrollbar-thumb {
            background: var(--primary-color);
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: var(--primary-dark);
        }
    </style>
</head>
<body>
    <!-- Animated Background -->
    <div class="bg-animation">
        <div class="floating-shapes">
            <div class="shape"></div>
            <div class="shape"></div>
            <div class="shape"></div>
            <div class="shape"></div>
            <div class="shape"></div>
            <div class="shape"></div>
        </div>
        
        <div class="wave-container">
            <div class="wave"></div>
            <div class="wave"></div>
            <div class="wave"></div>
        </div>
        
        <div class="particles">
            <div class="particle"></div>
            <div class="particle"></div>
            <div class="particle"></div>
            <div class="particle"></div>
            <div class="particle"></div>
            <div class="particle"></div>
            <div class="particle"></div>
            <div class="particle"></div>
            <div class="particle"></div>
        </div>
    </div>

    <div class="login-container">
        <div class="login-header">
            <div class="logo-container">
                <i class="fas fa-graduation-cap"></i>
            </div>
            <h1 class="login-title">Welcome Back</h1>
            <p class="login-subtitle">CSE 4267 Class Attendance System</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="form-container" id="loginForm">
            <div class="input-group">
                <label for="username">Email Address</label>
                <div class="input-wrapper">
                    <input 
                        type="email" 
                        id="username"
                        name="username" 
                        placeholder="Enter your email address"
                        required
                        value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>"
                    >
                    <i class="fas fa-envelope input-icon"></i>
                </div>
            </div>

            <div class="input-group">
                <label for="password">Student ID</label>
                <div class="input-wrapper">
                    <input 
                        type="password" 
                        id="password"
                        name="password" 
                        placeholder="Enter your student ID"
                        required
                    >
                    <i class="fas fa-id-card input-icon"></i>
                </div>
            </div>

            <button type="submit" class="login-button" id="loginBtn">
                <span>Sign In to Dashboard</span>
            </button>
        </form>

        <div class="divider">
            <span>Secure Login</span>
        </div>

        <div class="login-footer">
            <p>Having trouble? <a href="mailto:shahr.rahm@gmail.com">Contact Support</a></p>
        </div>
    </div>

    <script>
        // Add loading state to button
        document.getElementById('loginForm').addEventListener('submit', function() {
            const btn = document.getElementById('loginBtn');
            btn.classList.add('loading');
            btn.innerHTML = '<span>Signing In...</span>';
        });

        // Add smooth focus animations
        const inputs = document.querySelectorAll('input');
        inputs.forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.style.transform = 'scale(1.02)';
            });
            
            input.addEventListener('blur', function() {
                this.parentElement.style.transform = 'scale(1)';
            });
        });

        // Add enter key support
        document.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                document.getElementById('loginForm').dispatchEvent(new Event('submit'));
            }
        });

        // Auto-focus first input
        document.getElementById('username').focus();
    </script>
</body>
</html>