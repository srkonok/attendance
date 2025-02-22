<?php
require_once 'db.php';

$data = json_decode(file_get_contents('php://input'), true);

try {
    $stmt = $conn->prepare("UPDATE review_requests 
                          SET status = :status, updated_at = NOW() 
                          WHERE id = :request_id");
    $stmt->bindParam(':status', $data['status']);
    $stmt->bindParam(':request_id', $data['request_id']);
    $stmt->execute();
    
    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}