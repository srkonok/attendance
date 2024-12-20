<?php
use controllers\AttendanceController;

function defineRoutes($router) {

    // Route to submit attendance
    $router->post('/api/attendance', function() {
        $controller = new AttendanceController();
        $data = json_decode(file_get_contents('php://input'), true);
        $controller->submitAttendance($data);
    });

    // Route to get attendance by date (modified to capture date parameter)
    $router->get('/api/attendance/{date}', function($date) {
        $controller = new AttendanceController();
        $controller->getAttendanceByDate($date);
    });
}
?>
