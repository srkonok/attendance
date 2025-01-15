<?php
require_once 'vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

session_start();

$correct_password = $_ENV['MANUAL_ATTENDANCE_PASSWORD'] ?? 'password';

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['password']) && $_POST['password'] == $correct_password) {
        $_SESSION['authenticated'] = true; // Set session variable
        header("Location: mark_attendance.php"); // Redirect to attendance page
        exit();
    } else {
        $error = "Incorrect password!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manual Attendance Login</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <h2>Enter Password to Access Attendance Page</h2>
    <div class="search-form">


    <form method="POST">
        <input 
        type="password" name="password" required
        value="" 
        placeholder="Enter Password">

        <button type="submit">Login</button>
    </form>
    </div>
    <?php if (isset($error)) echo "<p style='color:red;'>$error</p>"; ?>
</body>
</html>
