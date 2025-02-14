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
    <title>Login</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }
        .login-container {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.1);
            width: 300px;
            text-align: center;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .login-container h2 {
            margin-bottom: 20px;
        }
        .input-container {
            width: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        input[type="email"], input[type="password"] {
            width: 90%;
            padding: 10px;
            margin: 10px 0;
            border: 1px solid #ccc;
            border-radius: 5px;
        }
        button {
            width: 100%;
            padding: 10px;
            background: #28a745;
            border: none;
            color: white;
            font-size: 16px;
            border-radius: 5px;
            cursor: pointer;
        }
        button:hover {
            background: #218838;
        }
        .error {
            color: red;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h2>Login</h2>
        <?php if (!empty($error)) echo "<p class='error'>$error</p>"; ?>
        <form method="POST" class="input-container">
            <input type="email" name="username" placeholder="Email" required>
            <input type="password" name="password" placeholder="Student ID" required>
            <button type="submit">Login</button>
        </form>
    </div>
</body>
</html>
