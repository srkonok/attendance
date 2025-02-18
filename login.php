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
    <style>
        body {
            font-family: Arial, sans-serif;
            background: linear-gradient(to right, #28a745, #a8e063);
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        .login-container {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0px 5px 15px rgba(0, 0, 0, 0.2);
            width: 350px;
            text-align: center;
            position: relative;
            animation: fadeIn 1s ease-in-out;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .login-container h2 {
            color: #28a745;
            margin-bottom: 20px;
        }
        .input-container {
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        input[type="email"], input[type="password"] {
            width: 90%;
            padding: 12px;
            margin: 10px 0;
            border: 1px solid #ccc;
            border-radius: 8px;
            font-size: 16px;
        }
        button {
            width: 100%;
            padding: 12px;
            background: #28a745;
            border: none;
            color: white;
            font-size: 18px;
            border-radius: 8px;
            cursor: pointer;
            transition: background 0.3s ease;
        }
        button:hover {
            background: #218838;
        }
        .error {
            color: red;
            margin-top: 10px;
        }
        .cloud-icon {
            width: 60px;
            height: 60px;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <img src="https://cdn-icons-png.flaticon.com/512/2620/2620218.png" alt="Cloud Icon" class="cloud-icon">
        <h2>CSE 4267 Class Login</h2>
        <?php if (!empty($error)) echo "<p class='error'>$error</p>"; ?>
        <form method="POST" class="input-container">
            <input type="email" name="username" placeholder="Email" required>
            <input type="password" name="password" placeholder="Student ID" required>
            <button type="submit">Login</button>
        </form>
    </div>
</body>
</html>