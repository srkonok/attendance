<?php
include 'db.php';
require 'vendor/autoload.php'; // Autoload dependencies

use Dotenv\Dotenv;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;

// Load environment variables from .env
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Define the date for attendance check
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

if (empty($absentStudents)) {
    echo "No absent students to email.";
    exit;
}

// Create the PHPMailer instance
$mail = new PHPMailer(true);

// echo json_encode($absentStudents);

try {
    // Configure SMTP settings from .env
    $mail->isSMTP();
    $mail->SMTPAuth   = true;
    $mail->SMTPSecure = $_ENV['MAIL_ENCRYPTION'];
    $mail->Port       = $_ENV['MAIL_PORT'];
    $mail->Host       = $_ENV['MAIL_HOST'];
    $mail->Username   = $_ENV['MAIL_USERNAME'];
    $mail->Password   = $_ENV['MAIL_PASSWORD'];
    $mail->setFrom($_ENV['MAIL_FROM_ADDRESS'], $_ENV['MAIL_FROM_NAME']);

    // foreach ($absentStudents as $student) {
        // Recipient
        $mail->addAddress('srkonok20@gmail.com', 'Shahriar');

        // Email content
        $mail->isHTML(true);
        $mail->Subject = 'Class Attendance Reminder';
        $mail->Body    = "
        <div style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
            <div style='background-color: #f4f4f4; padding: 20px; border-radius: 8px; border: 1px solid #ddd;'>
                <h2 style='color:rgb(35, 36, 37); text-align: center;'>Cloud Computing Class Attendance</h2>
                <p>Dear <strong>Shahriar</strong>,</p>
                <p>
                    We noticed that you did not attend the <strong>Cloud Computing</strong> class on
                    <span style='color: #d9534f;'>{$date}</span>. Regular attendance is crucial to ensure you
                    stay on track with the course content and activities.
                </p>
                <p>
                    If you have any concerns or need assistance, please feel free to reach out to me directly.
                    Your participation is vital for a successful learning experience.
                </p>
                <p style='margin-top: 20px;'>
                    Best regards,<br>
                    <strong>Shahriar Rahman</strong><br>
                    Adjunct Faculty, CSE<br>
                    Ahsanullah University of Science and Technology (AUST)
                </p>
            </div>
        </div>
    ";

    //     // Send the email
        $mail->send();
    //     $mail->clearAddresses(); // Clear addresses for the next email
    // }

    echo "Emails sent to all absent students.";
} catch (Exception $e) {
    echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
}
