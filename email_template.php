<?php
function getEmailTemplate($studentName, $date) {
    return "
    <div style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
        <div style='background-color: #f4f4f4; padding: 20px; border-radius: 8px; border: 1px solid #ddd;'>
            <h2 style='color:rgb(35, 36, 37); text-align: center;'>Cloud Computing Class Attendance</h2>
            <p>Dear <strong>{$studentName}</strong>,</p>
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
    </div>";
    
}
?>
