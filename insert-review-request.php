<?php
require_once 'db.php';

$data = json_decode(file_get_contents('php://input'), true);

try {
    $stmt = $conn->prepare("INSERT INTO review_requests 
                          (student_id, type, status) 
                          VALUES (:student_id, :type, 'pending')");
    $stmt->bindParam(':student_id', $data['student_id']);
    $stmt->bindParam(':type', $data['type']);
    $stmt->execute();
    
    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}