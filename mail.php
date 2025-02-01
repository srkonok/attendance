<?php
ini_set('max_execution_time', 300);
include 'db.php';
require 'vendor/autoload.php';
require 'email_template.php';

use Dotenv\Dotenv;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;

header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');

ob_implicit_flush(true);
@ob_end_flush();

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

$date = date('Y-m-d');

// Fetch absent students
$absentQuery = "
    SELECT s.student_id, s.name, s.email, s.phone_number
    FROM students s
    LEFT JOIN attendance a ON s.student_id = a.student_id AND a.date = :date
    WHERE a.student_id IS NULL";
$absentStmt = $conn->prepare($absentQuery);
$absentStmt->bindValue(':date', $date, PDO::PARAM_STR);
$absentStmt->execute();
$absentStudents = $absentStmt->fetchAll();

$totalStudents = count($absentStudents);

// Send total students count first
if ($totalStudents === 0) {
    echo "data: " . json_encode(["status" => "no_students", "message" => "No absent students to email."]) . "\n\n";
    flush();
    exit;
} else {
    echo "data: " . json_encode(["status" => "total_students", "total" => $totalStudents]) . "\n\n";
    flush();
}

$mail = new PHPMailer(true);

try {
    $mail->isSMTP();
    $mail->SMTPAuth   = true;
    $mail->SMTPSecure = $_ENV['MAIL_ENCRYPTION'];
    $mail->Port       = $_ENV['MAIL_PORT'];
    $mail->Host       = $_ENV['MAIL_HOST'];
    $mail->Username   = $_ENV['MAIL_USERNAME'];
    $mail->Password   = $_ENV['MAIL_PASSWORD'];
    $mail->setFrom($_ENV['MAIL_FROM_ADDRESS'], $_ENV['MAIL_FROM_NAME']);

    $count = 0;

    foreach ($absentStudents as $student) {
        try {
            $mail->clearAddresses();
            $mail->addAddress($student['email'], $student['name']);

            $mail->isHTML(true);
            $mail->Subject = 'Class Attendance Reminder';
            $mail->Body    = getEmailTemplate($student['name'], $date);

            // Uncomment in production
            // $mail->send(); 

            sleep(1); // Simulating email sending delay

            $count++;
            echo "data: " . json_encode([
                "status"  => "success",
                "count"   => $count,
                "name"    => $student['name'],
                "email"   => $student['email']
            ]) . "\n\n";
            flush();
        } catch (Exception $e) {
            echo "data: " . json_encode([
                "status"  => "failed",
                "name"    => $student['name'],
                "email"   => $student['email'],
                "error"   => $mail->ErrorInfo
            ]) . "\n\n";
            flush();
        }
    }

    echo "data: " . json_encode(["status" => "completed", "message" => "All emails processed successfully."]) . "\n\n";
    flush();
} catch (Exception $e) {
    echo "data: " . json_encode(["status" => "error", "message" => "Mailer configuration error: " . $mail->ErrorInfo]) . "\n\n";
    flush();
}
?>
