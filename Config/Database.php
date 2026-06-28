<?php
namespace Config;

use PDO;
use PDOException;

class Database {
    private static $conn = null;

    public static function getConnection() {
        if (self::$conn === null) {
            try {
                $host = Env::get('DB_HOST', 'localhost');
                $dbName = Env::get('DB_NAME', 'didau');
                $username = Env::get('DB_USER', 'root');
                $password = Env::get('DB_PASSWORD', '');
                self::$conn = new PDO(
                    "mysql:host=" . $host . ";dbname=" . $dbName . ";charset=utf8mb4",
                    $username,
                    $password,
                    [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_EMULATE_PREPARES => false,
                    ]
                );
            } catch (PDOException $exception) {
                // If the connection fails, respond with proper HTTP status + JSON
                // so that frontend fetch() calls can parse the error instead of
                // throwing on unexpected plain-text/HTML output.
                http_response_code(500);
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode([
                    'error' => 'Lỗi kết nối cơ sở dữ liệu. Vui lòng kiểm tra cấu hình Config/Database.php và đảm bảo đã import schema.sql.',
                    'detail' => $exception->getMessage(),
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }
        }
        return self::$conn;
    }
}
