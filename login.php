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
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <title>CSE 4267: Cloud Computing - Login</title>
    <link rel="icon" href="images/favicon_io/favicon.ico" type="image/x-icon">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        /* Use the same base styles from index.css */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html {
            font-size: 16px;
            -webkit-text-size-adjust: 100%;
            -webkit-tap-highlight-color: transparent;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(-45deg, #059669, #10b981, #34d399, #6ee7b7);
            background-size: 400% 400%;
            animation: gradientShift 15s ease infinite;
            min-height: 100vh;
            color: #1f2937;
            overflow-x: hidden;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        @keyframes gradientShift {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        /* Animated background particles - same as index.css */
        .bg-particles {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: -1;
        }

        .particle {
            position: absolute;
            width: 4px;
            height: 4px;
            background: rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            animation: float 20s infinite linear;
        }

        @keyframes float {
            0% {
                transform: translateY(100vh) rotate(0deg);
                opacity: 0;
            }
            10% { opacity: 1; }
            90% { opacity: 1; }
            100% {
                transform: translateY(-100px) rotate(360deg);
                opacity: 0;
            }
        }

        /* Login Container */
        .login-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border: 2px solid rgba(5, 150, 105, 0.2);
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 20px 40px rgba(5, 150, 105, 0.15);
            width: 100%;
            max-width: 420px;
            animation: fadeInUp 0.8s ease-out;
            position: relative;
        }

        @keyframes fadeInUp {
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
            margin-bottom: 30px;
        }

        .logo-container {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #10b981, #059669);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            box-shadow: 0 10px 25px rgba(5, 150, 105, 0.3);
            animation: glow 2s ease-in-out infinite alternate;
            position: relative;
            overflow: hidden;
        }

        @keyframes glow {
            from { 
                filter: drop-shadow(0 0 10px rgba(5, 150, 105, 0.3));
                transform: scale(1);
            }
            to { 
                filter: drop-shadow(0 0 20px rgba(5, 150, 105, 0.6));
                transform: scale(1.05);
            }
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

        @keyframes rotate {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .logo-container i {
            font-size: 2.2rem;
            color: white;
            z-index: 1;
            position: relative;
        }

        .login-title {
            font-size: 2rem;
            font-weight: 700;
            background: linear-gradient(135deg, #059669, #10b981);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .login-subtitle {
            color: #6b7280;
            font-size: 1rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        /* Form Styles */
        .form-container {
            display: flex;
            flex-direction: column;
            gap: 20px;
            margin-bottom: 25px;
        }

        .input-group {
            position: relative;
        }

        .input-group label {
            display: block;
            font-size: 14px;
            font-weight: 600;
            color: #059669;
            margin-bottom: 8px;
        }

        .input-wrapper {
            position: relative;
        }

        .input-icon {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: #6b7280;
            font-size: 1.1rem;
            transition: all 0.3s ease;
            z-index: 2;
        }

        input[type="email"],
        input[type="password"] {
            width: 100%;
            padding: 15px 20px 15px 50px;
            background: rgba(255, 255, 255, 0.9);
            border: 2px solid rgba(5, 150, 105, 0.2);
            border-radius: 12px;
            color: #374151;
            font-size: 16px;
            font-family: inherit;
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
            outline: none;
        }

        input[type="email"]:focus,
        input[type="password"]:focus {
            border-color: #10b981;
            background: rgba(255, 255, 255, 0.95);
            box-shadow: 0 0 0 4px rgba(5, 150, 105, 0.1);
        }

        input[type="email"]:focus + .input-icon,
        input[type="password"]:focus + .input-icon {
            color: #10b981;
            transform: translateY(-50%) scale(1.1);
        }

        input::placeholder {
            color: #9ca3af;
        }

        .login-button {
            width: 100%;
            padding: 15px 25px;
            background: linear-gradient(135deg, #10b981, #059669);
            border: none;
            border-radius: 12px;
            color: #ffffff;
            font-size: 16px;
            font-weight: 600;
            font-family: inherit;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            min-height: 54px;
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
            background: linear-gradient(135deg, #059669, #047857);
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(5, 150, 105, 0.3);
        }

        .login-button:hover::before {
            left: 100%;
        }

        .login-button:active {
            transform: translateY(0);
        }

        /* Error Message */
        .error-message {
            padding: 16px 24px;
            background: rgba(239, 68, 68, 0.1);
            border: 2px solid rgba(239, 68, 68, 0.3);
            border-radius: 12px;
            color: #dc2626;
            font-weight: 500;
            margin-bottom: 20px;
            animation: slideIn 0.5s ease-out;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .error-message::before {
            content: '✕';
            font-weight: bold;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateX(-20px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        /* Divider */
        .divider {
            display: flex;
            align-items: center;
            margin: 25px 0 20px;
            color: #6b7280;
            font-size: 14px;
        }

        .divider::before,
        .divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: linear-gradient(90deg, transparent, rgba(5, 150, 105, 0.2), transparent);
        }

        .divider span {
            padding: 0 15px;
            background: rgba(255, 255, 255, 0.95);
            color: #10b981;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .login-footer {
            text-align: center;
            color: #6b7280;
            font-size: 14px;
        }

        .login-footer a {
            color: #10b981;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
        }

        .login-footer a:hover {
            color: #059669;
        }

        /* Loading state */
        .login-button.loading {
            pointer-events: none;
            opacity: 0.7;
        }

        .login-button.loading::after {
            content: '';
            position: absolute;
            width: 16px;
            height: 16px;
            border: 2px solid transparent;
            border-top-color: #ffffff;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Mobile Responsive */
        @media (max-width: 575.98px) {
            body {
                padding: 15px;
            }

            .login-container {
                padding: 30px 20px;
                border-radius: 15px;
            }

            .login-title {
                font-size: 1.6rem;
                flex-direction: column;
                gap: 5px;
            }

            .logo-container {
                width: 70px;
                height: 70px;
                margin-bottom: 15px;
            }

            .logo-container i {
                font-size: 1.8rem;
            }

            input[type="email"],
            input[type="password"] {
                padding: 12px 15px 12px 45px;
                font-size: 16px; /* Prevent zoom on iOS */
            }

            .input-icon {
                left: 12px;
                font-size: 1rem;
            }

            .login-button {
                padding: 12px 20px;
                min-height: 48px;
            }
        }

        /* Touch optimizations */
        @media (hover: none) and (pointer: coarse) {
            .login-button {
                min-height: 48px;
            }

            input[type="email"],
            input[type="password"] {
                min-height: 48px;
            }

            .login-button:hover {
                transform: none;
            }
        }

        /* Accessibility */
        @media (prefers-reduced-motion: reduce) {
            * {
                animation-duration: 0.01ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: 0.01ms !important;
            }

            .bg-particles {
                display: none;
            }
        }
    </style>
</head>
<body>
    <!-- Animated Background Particles - same as index.php -->
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

    <div class="login-container">
        <div class="login-header">
            <div class="logo-container">
                <i class="fas fa-cloud"></i>
            </div>
            <h1 class="login-title">
                <i class="fas fa-graduation-cap"></i>
                Welcome Back
            </h1>
            <p class="login-subtitle">
                <i class="fas fa-calendar"></i>
                CSE 4267: Cloud Computing
            </p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="error-message">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="form-container" id="loginForm">
            <div class="input-group">
                <label for="username">
                    <i class="fas fa-envelope"></i> Email Address
                </label>
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
                <label for="password">
                    <i class="fas fa-id-card"></i> Student ID
                </label>
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
                <i class="fas fa-sign-in-alt"></i>
                <span>Sign In to Dashboard</span>
            </button>
        </form>

        <div class="divider">
            <span>
                <i class="fas fa-shield-alt"></i>
                Secure Login
            </span>
        </div>

        <div class="login-footer">
            <p>Having trouble? <a href="mailto:shahr.rahm@gmail.com">
                <i class="fas fa-envelope"></i> Contact Support
            </a></p>
        </div>
    </div>

    <script>
        // Add loading state to button
        document.getElementById('loginForm').addEventListener('submit', function() {
            const btn = document.getElementById('loginBtn');
            btn.classList.add('loading');
            btn.innerHTML = '<span>Signing In...</span>';
        });

        // Add smooth focus animations (without scaling)
        const inputs = document.querySelectorAll('input');
        inputs.forEach(input => {
            // Remove the problematic scaling animation
            // Just keep the CSS transitions for border and background changes
            
            // Add ripple effect on click (optional - can be removed if not needed)
            input.addEventListener('click', function(e) {
                // Simple click feedback without expanding the form
                this.style.transform = 'translateY(-1px)';
                setTimeout(() => {
                    this.style.transform = 'translateY(0)';
                }, 150);
            });
        });

        // Auto-focus first input
        document.getElementById('username').focus();

        // Enhanced keyboard navigation
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                const activeElement = document.activeElement;
                if (activeElement.tagName === 'INPUT') {
                    const inputs = Array.from(document.querySelectorAll('input'));
                    const currentIndex = inputs.indexOf(activeElement);
                    if (currentIndex < inputs.length - 1) {
                        inputs[currentIndex + 1].focus();
                    } else {
                        document.getElementById('loginBtn').click();
                    }
                }
            }
        });
    </script>
</body>
</html>