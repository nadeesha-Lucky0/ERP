<?php
namespace App\Config;

use PDO;
use PDOException;
use Exception;

class Database {
    private static ?PDO $instance = null;

    /**
     * Get Central Database Connection for 'fifonew' with silent auto-initialization
     */
    public static function getConnection(): ?PDO {
        if (self::$instance !== null) {
            try {
                self::$instance->query("SELECT 1");
                return self::$instance;
            } catch (\Throwable $e) {
                self::$instance = null;
            }
        }

        try {
            // First ensure database 'fifonew' exists silently
            $hostPdo = new PDO(
                sprintf("mysql:host=%s;port=%s;charset=%s", DB_HOST, DB_PORT, DB_CHARSET),
                DB_USER,
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => true
                ]
            );
            $hostPdo->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");

            // Connect to fifonew
            $dsn = sprintf(
                "mysql:host=%s;port=%s;dbname=%s;charset=%s",
                DB_HOST,
                DB_PORT,
                DB_NAME,
                DB_CHARSET
            );

            self::$instance = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => true,
            ]);

            // Silently verify if tables exist; if not, execute fifonew.sql schema automatically
            $chk = self::$instance->query("SHOW TABLES LIKE 'users'")->fetch();
            if (!$chk) {
                $sqlFile = DATABASE_PATH . DIRECTORY_SEPARATOR . 'fifonew.sql';
                if (file_exists($sqlFile)) {
                    self::$instance->exec(file_get_contents($sqlFile));
                }
            }

            return self::$instance;
        } catch (\Throwable $e) {
            throw new Exception("MySQL Database Error: " . $e->getMessage());
        }
    }
}
