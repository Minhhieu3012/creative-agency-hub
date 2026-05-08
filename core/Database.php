<?php

namespace Core;

use PDO;
use PDOException;

class Database {
    private static $instance = null;
    private $connection;

    private function __construct() {
        // Ưu tiên đọc từ $_ENV, nếu không có thì dùng getenv
        $host = $_ENV['DB_HOST'] ?? getenv('DB_HOST') ?: 'localhost';
        $port = $_ENV['DB_PORT'] ?? getenv('DB_PORT') ?: '3306';
        $db   = $_ENV['DB_DATABASE'] ?? getenv('DB_DATABASE') ?: 'creative_agency';
        $user = $_ENV['DB_USERNAME'] ?? getenv('DB_USERNAME') ?: 'root';
        $pass = $_ENV['DB_PASSWORD'] ?? getenv('DB_PASSWORD') ?: '';

        $dsn = "mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4";

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            $this->connection = new PDO($dsn, $user, $pass, $options);
        } catch (PDOException $e) {
            error_log("DB Connection Error: " . $e->getMessage());

            http_response_code(500);
            header('Content-Type: application/json; charset=utf-8');
            die(json_encode([
                "status" => "error",
                "message" => "Database connection failed: " . $e->getMessage()
            ]));
        }
    }

    public static function getConnection() {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance->connection;
    }
}