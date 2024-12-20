<?php
namespace config;

use \PDO;
use Dotenv\Dotenv;

class Database {
    private $host = '';
    private $dbName = '';
    private $username = '';
    private $password = '';
    private $conn;

    public function __construct() {
        // Load environment variables from .env
        $dotenv = Dotenv::createImmutable(__DIR__ . '/../');
        $dotenv->load();

        // Set the values from the .env file
        $this->host = $_ENV['DB_HOST'];
        $this->dbName = $_ENV['DB_NAME'];
        $this->username = $_ENV['DB_USER'];
        $this->password = $_ENV['DB_PASSWORD'];
    }

    public function getConnection() {
        if ($this->conn === null) {
            try {
                // Establish the database connection
                $dsn = "mysql:host=" . $this->host . ";dbname=" . $this->dbName;
                $this->conn = new PDO($dsn, $this->username, $this->password);
                $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            } catch (PDOException $e) {
                echo "Connection failed: " . $e->getMessage();
            }
        }
        return $this->conn;
    }
}
?>
