<?php
namespace controllers;

use models\AttendanceModel;
use config\Database;

class AttendanceController {

    private $attendanceModel;

    public function __construct() {
        $db = new Database();
        $this->attendanceModel = new AttendanceModel($db->getConnection());
    }

    // Endpoint to submit attendance
    public function submitAttendance($data) {
        $studentId = $data['student_id'];
        $ipAddress = $_SERVER['REMOTE_ADDR'];
        $date = date('Y-m-d');

        $existingAttendance = $this->attendanceModel->getLatestAttendance($studentId);

        if ($existingAttendance && $existingAttendance['date'] == $date) {
            echo json_encode(['message' => 'Attendance already submitted for today']);
        } else {
            $result = $this->attendanceModel->submitAttendance($studentId, $ipAddress, $date);
            if ($result) {
                echo json_encode(['message' => 'Attendance submitted successfully']);
            } else {
                echo json_encode(['message' => 'Failed to submit attendance']);
            }
        }
    }

    // Endpoint to get attendance for a specific date
    public function getAttendanceByDate($date) {
        // Validate date format (optional)
        if (DateTime::createFromFormat('Y-m-d', $date) === false) {
            echo json_encode(['message' => 'Invalid date format. Use YYYY-MM-DD.']);
            return;
        }

        $attendanceRecords = $this->attendanceModel->getAttendanceByDate($date);

        // If no records are found for the date, return an empty array or an appropriate message
        if (empty($attendanceRecords)) {
            echo json_encode(['message' => 'No attendance records found for this date']);
        } else {
            echo json_encode($attendanceRecords);
        }
    }
}
?>
