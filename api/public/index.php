<?php
namespace public;

// Autoloading classes
require_once __DIR__ . '/../vendor/autoload.php';  // Ensure you include the autoloader if using Composer

use \core\Router;

// Load .env file
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

include_once '../core/Router.php';
include_once '../routes/attendanceRoutes.php';

$router = new Router();
defineRoutes($router);
$router->dispatch();
?>
