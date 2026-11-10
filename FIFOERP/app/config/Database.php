<?php
namespace App\Config;

use App\Core\StationDatabase;
use PDO;
use PDOException;
use Exception;

class Database {
    private static ?PDO $instance = null;
    private static array $stationInstances = [];

    /**
     * Get Central Platform Database Connection ('fifo')
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

            return self::$instance;
        } catch (\Throwable $e) {
            throw new Exception("MySQL Database Error: " . $e->getMessage());
        }
    }

    /**
     * Get Isolated Database Connection for a specific Power Station
     *
     * @param int|string|null $stationIdentifier (Location ID, Location Code, or DB name)
     * @return PDO
     */
    public static function getStationConnection(int|string|null $stationIdentifier = null): PDO {
        $dbName = self::resolveStationDbName($stationIdentifier);

        // Return cached pooled connection if already open
        if (isset(self::$stationInstances[$dbName])) {
            try {
                self::$stationInstances[$dbName]->query("SELECT 1");
                return self::$stationInstances[$dbName];
            } catch (\Throwable $e) {
                unset(self::$stationInstances[$dbName]);
            }
        }

        try {
            $dsn = sprintf(
                "mysql:host=%s;port=%s;dbname=%s;charset=%s",
                DB_HOST,
                DB_PORT,
                $dbName,
                DB_CHARSET
            );

            $stationPdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => true,
            ]);

            // Ensure station schema tables exist
            $stationPdo->exec(StationDatabase::getStationSchemaSql());

            self::$stationInstances[$dbName] = $stationPdo;
            return $stationPdo;
        } catch (PDOException $e) {
            // If the database does not exist, auto-create
            if ($e->getCode() == 1049 || str_contains($e->getMessage(), 'Unknown database')) {
                try {
                    $code = is_numeric($stationIdentifier) ? (string)$stationIdentifier : 'STATION';
                    StationDatabase::createStationDb($code, $dbName);

                    $dsn = sprintf(
                        "mysql:host=%s;port=%s;dbname=%s;charset=%s",
                        DB_HOST,
                        DB_PORT,
                        $dbName,
                        DB_CHARSET
                    );

                    $stationPdo = new PDO($dsn, DB_USER, DB_PASS, [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_EMULATE_PREPARES => true,
                    ]);

                    self::$stationInstances[$dbName] = $stationPdo;
                    return $stationPdo;
                } catch (\Throwable $inner) {
                    error_log("Failed to auto-provision station database [{$dbName}]: " . $inner->getMessage());
                }
            }

            error_log("Station Database Connection Error [{$dbName}]: " . $e->getMessage());
            // Fall back to main connection to prevent hard crash
            return self::getConnection();
        }
    }

    /**
     * Resolve the database name for a power station
     */
    public static function resolveStationDbName(int|string|null $identifier): string {
        if (!empty($identifier)) {
            if (is_string($identifier) && str_starts_with($identifier, 'fifo_')) {
                return $identifier;
            }

            try {
                $mainDb = self::getConnection();
                if (is_numeric($identifier)) {
                    $stmt = $mainDb->prepare("SELECT code, name, database_name FROM locations WHERE id = :id LIMIT 1");
                    $stmt->execute([':id' => (int)$identifier]);
                } else {
                    $stmt = $mainDb->prepare("SELECT code, name, database_name FROM locations WHERE code = :code LIMIT 1");
                    $stmt->execute([':code' => trim($identifier)]);
                }
                $loc = $stmt->fetch();
                if ($loc) {
                    if (!empty($loc['database_name'])) {
                        return $loc['database_name'];
                    }
                    return StationDatabase::getDbName($loc['code'], $loc['name']);
                }
            } catch (\Throwable $e) {}

            if (is_numeric($identifier)) {
                return "fifo_station_" . $identifier;
            }
        }

        // Check active session context
        if (isset($_SESSION['active_station_db']) && !empty($_SESSION['active_station_db'])) {
            return $_SESSION['active_station_db'];
        }

        if (isset($_SESSION['user']['location_id']) && (int)$_SESSION['user']['location_id'] > 0) {
            return self::resolveStationDbName((int)$_SESSION['user']['location_id']);
        }

        return 'fifo_831_victoria';
    }
}
