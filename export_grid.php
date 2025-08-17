<?php
session_start();

if (!isset($_SESSION["user"])) {
    header("Location: login.php");
    exit();
}

// Get format parameter
$format = isset($_GET['format']) ? strtolower(trim($_GET['format'])) : '';

// Redirect to appropriate export handler based on format
switch ($format) {
    case 'pdf':
        include 'export_pdf.php';
        break;
    case 'csv':
        include 'export_csv.php';
        break;
    case 'excel':
        include 'export_excel.php';
        break;
    default:
        // Invalid format, redirect back to grid
        header("Location: attendance_grid.php?error=invalid_format");
        exit();
}
?>