<?php
// Load environment variables from .env
date_default_timezone_set('Asia/Dhaka');

require_once __DIR__ . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$host = $_ENV['DB_HOST'];
$dbname = $_ENV['DB_NAME'];
$username = $_ENV['DB_USERNAME'];
$password = $_ENV['DB_PASSWORD'];

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Set time zone to 'Asia/Dhaka'
    $conn->exec("SET time_zone = '+06:00'");
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
