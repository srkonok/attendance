<?php
// teacher-review-requests.php
session_start();
require_once 'db.php';

// Check if teacher is logged in
if (!isset($_SESSION['user']) || $_SESSION['user'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Fetch all review requests
$stmt = $conn->prepare("SELECT r.*, s.name AS student_name 
                       FROM review_requests r
                       JOIN students s ON r.student_id = s.student_id");
$stmt->execute();
$requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Review Requests</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .status-select { padding: 5px; border-radius: 4px; }
    </style>
</head>
<body>
    <div class="container mt-5">
        <h2 class="mb-4">Review Requests</h2>
        <table class="table table-bordered">
            <thead class="bg-success text-white">
                <tr>
                    <th>ID</th>
                    <th>Student</th>
                    <th>Type</th>
                    <th>Current Status</th>
                    <th>Request Date</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($requests as $request): ?>
                <tr>
                    <td><?= htmlspecialchars($request['student_id']) ?></td>
                    <td><?= htmlspecialchars($request['student_name']) ?></td>
                    <td><?= htmlspecialchars(str_replace('_', ' ', $request['type'])) ?></td>
                    <td class="status <?= htmlspecialchars($request['status']) ?>">
                        <?= htmlspecialchars(ucfirst($request['status'])) ?>
                    </td>
                    <td><?= htmlspecialchars($request['created_at']) ?></td>
                    <td>
                        <select class="status-select form-control" data-request-id="<?= $request['id'] ?>">
                            <option value="pending" <?= $request['status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                            <option value="reviewed" <?= $request['status'] === 'reviewed' ? 'selected' : '' ?>>Reviewed</option>
                        </select>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script>
        document.querySelectorAll('.status-select').forEach(select => {
            select.addEventListener('change', function() {
                const requestId = this.dataset.requestId;
                const newStatus = this.value;

                fetch('update-review-status.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        request_id: requestId,
                        status: newStatus
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (!data.success) {
                        alert('Error updating status');
                    }
                })
                .catch(error => console.error('Error:', error));
            });
        });
    </script>
</body>
</html>