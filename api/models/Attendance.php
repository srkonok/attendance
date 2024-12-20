<?php
// models/Attendance.php

class Attendance {
    private $conn;
    private $table = 'attendance';

    public function __construct($db) {
        $this->conn = $db;
    }

    // Function to submit attendance
    public function submitAttendance($studentId, $ipAddress, $date) {
        $query = "SELECT * FROM " . $this->table . " WHERE ip_address = :ip AND date = :date";
        $stmt = $this->conn->prepare($query);
        $stmt->execute(['ip' => $ipAddress, 'date' => $date]);
        $existingRecord = $stmt->fetch();

        if ($existingRecord) {
            return ['status' => 'error', 'message' => 'Attendance already submitted.'];
        } else {
            $insertQuery = "INSERT INTO " . $this->table . " (student_id, ip_address, date) VALUES (:student_id, :ip_address, :date)";
            $stmt = $this->conn->prepare($insertQuery);
            $stmt->execute([
                'student_id' => $studentId,
                'ip_address' => $ipAddress,
                'date' => $date,
            ]);
            return ['status' => 'success', 'message' => 'Attendance submitted successfully.'];
        }
    }

    // Function to fetch attendance data
    public function getAttendance($searchStudentId, $searchDate, $limit, $offset) {
        $query = "SELECT a.student_id, a.date, s.name FROM " . $this->table . " a 
                  JOIN students s ON a.student_id = s.student_id 
                  WHERE 1=1 ";

        $params = [];
        if (!empty($searchStudentId)) {
            $query .= " AND a.student_id = :student_id";
            $params['student_id'] = $searchStudentId;
        }
        if (!empty($searchDate)) {
            $query .= " AND a.date = :date";
            $params['date'] = $searchDate;
        }

        $query .= " ORDER BY a.date DESC LIMIT :limit OFFSET :offset";

        $stmt = $this->conn->prepare($query);
        $params['limit'] = $limit;
        $params['offset'] = $offset;
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    // Function to count total records for pagination
    public function getTotalAttendanceCount($searchStudentId, $searchDate) {
        $query = "SELECT COUNT(*) FROM " . $this->table . " WHERE 1=1 ";
        $params = [];
        if (!empty($searchStudentId)) {
            $query .= " AND student_id = :student_id";
            $params['student_id'] = $searchStudentId;
        }
        if (!empty($searchDate)) {
            $query .= " AND date = :date";
            $params['date'] = $searchDate;
        }

        $stmt = $this->conn->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchColumn();
    }
}
?>
