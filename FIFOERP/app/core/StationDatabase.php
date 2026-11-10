<?php
namespace App\Core;

use App\Config\Database;
use App\Models\Location;
use PDO;
use PDOException;
use Exception;

class StationDatabase {

    /**
     * Generate safe, clean MySQL database name for a station
     */
    public static function getDbName(string $code, string $name): string {
        $cleanCode = preg_replace('/[^a-zA-Z0-9]/', '', strtolower($code));
        $cleanName = strtolower($name);
        $cleanName = str_replace(['power station', 'power plant', 'station'], '', $cleanName);
        $cleanName = preg_replace('/[^a-zA-Z0-9]/', '_', trim($cleanName));
        $cleanName = trim(preg_replace('/_+/', '_', $cleanName), '_');

        if (!empty($cleanCode) && !empty($cleanName)) {
            return "fifo_{$cleanCode}_{$cleanName}";
        } elseif (!empty($cleanCode)) {
            return "fifo_{$cleanCode}";
        } elseif (!empty($cleanName)) {
            return "fifo_{$cleanName}";
        }
        return "fifo_station_" . time();
    }

    /**
     * Get the station-specific DDL schema for all operational tables
     */
    public static function getStationSchemaSql(): string {
        return "
            CREATE TABLE IF NOT EXISTS `fifo_batches` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `batch_number` VARCHAR(50) NOT NULL UNIQUE,
                `sku` VARCHAR(50) NOT NULL,
                `item_name` VARCHAR(150) NOT NULL,
                `category` VARCHAR(100) DEFAULT 'Standard',
                `initial_quantity` INT NOT NULL,
                `remaining_quantity` INT NOT NULL,
                `unit_cost` DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
                `received_date` DATE NOT NULL,
                `expiry_date` DATE DEFAULT NULL,
                `location_bin` VARCHAR(50) DEFAULT 'Zone A-01',
                `status` VARCHAR(20) DEFAULT 'active',
                `created_by` INT NOT NULL DEFAULT 1,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

            CREATE TABLE IF NOT EXISTS `inventory_transactions` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `batch_id` INT NULL,
                `transaction_type` VARCHAR(20) NOT NULL,
                `quantity` INT NOT NULL,
                `unit_price` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                `total_value` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
                `transaction_date` DATE NOT NULL,
                `reference_no` VARCHAR(60) NULL,
                `created_by` INT NULL DEFAULT 1,
                `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

            CREATE TABLE IF NOT EXISTS `unposted_documents` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `location_id` INT DEFAULT 1,
                `doc_type` VARCHAR(30) NOT NULL,
                `doc_no` VARCHAR(60) NOT NULL UNIQUE,
                `doc_date` DATE NOT NULL,
                `receipt_from` VARCHAR(150) DEFAULT NULL,
                `department_code` VARCHAR(50) DEFAULT NULL,
                `destination_code` VARCHAR(50) DEFAULT NULL,
                `warehouse` VARCHAR(100) DEFAULT 'Main Store Bay-01',
                `status` VARCHAR(30) DEFAULT 'Draft',
                `invoice_total` DECIMAL(14,2) DEFAULT 0.00,
                `vat` DECIMAL(10,2) DEFAULT 0.00,
                `transport` DECIMAL(10,2) DEFAULT 0.00,
                `nbt` DECIMAL(10,2) DEFAULT 0.00,
                `other_cost` DECIMAL(10,2) DEFAULT 0.00,
                `invoice_number` VARCHAR(60) DEFAULT NULL,
                `order_reference` VARCHAR(60) DEFAULT NULL,
                `remarks` TEXT DEFAULT NULL,
                `created_by` INT DEFAULT 1,
                `validated_by` INT DEFAULT NULL,
                `approved_by` INT DEFAULT NULL,
                `posted_by` INT DEFAULT NULL,
                `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
                `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

            CREATE TABLE IF NOT EXISTS `unposted_document_items` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `document_id` INT NOT NULL,
                `batch_id` INT DEFAULT NULL,
                `sku` VARCHAR(50) NOT NULL,
                `item_name` VARCHAR(150) NOT NULL,
                `requested_quantity` INT NOT NULL DEFAULT 0,
                `quantity` INT NOT NULL,
                `unit_cost` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
                `total_cost` DECIMAL(14,2) NOT NULL DEFAULT 0.00,
                `bin_location` VARCHAR(50) DEFAULT 'Bay A',
                `expiry_date` DATE DEFAULT NULL,
                CONSTRAINT `fk_unposted_items_doc` FOREIGN KEY (`document_id`) REFERENCES `unposted_documents` (`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

            CREATE TABLE IF NOT EXISTS `phv_sessions` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `location_id` INT DEFAULT 1,
                `session_code` VARCHAR(50) NOT NULL UNIQUE,
                `session_title` VARCHAR(150) NOT NULL,
                `verification_date` DATE NOT NULL,
                `freeze_status` VARCHAR(20) DEFAULT 'unfrozen',
                `frozen_at` DATETIME DEFAULT NULL,
                `frozen_by` INT DEFAULT NULL,
                `unfrozen_at` DATETIME DEFAULT NULL,
                `unfrozen_by` INT DEFAULT NULL,
                `status` VARCHAR(30) DEFAULT 'Draft',
                `remarks` TEXT DEFAULT NULL,
                `created_by` INT DEFAULT 1,
                `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

            CREATE TABLE IF NOT EXISTS `phv_entries` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `session_id` INT NOT NULL,
                `batch_id` INT NOT NULL,
                `sku` VARCHAR(50) NOT NULL,
                `item_name` VARCHAR(150) NOT NULL,
                `bin_location` VARCHAR(50) DEFAULT NULL,
                `book_quantity` INT NOT NULL DEFAULT 0,
                `physical_quantity` INT NOT NULL DEFAULT 0,
                `variance_quantity` INT GENERATED ALWAYS AS (`physical_quantity` - `book_quantity`) STORED,
                `unit_cost` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
                `variance_value` DECIMAL(14,2) GENERATED ALWAYS AS ((`physical_quantity` - `book_quantity`) * `unit_cost`) STORED,
                `verified_by` INT DEFAULT NULL,
                `remarks` VARCHAR(255) DEFAULT NULL,
                `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
                CONSTRAINT `fk_phv_session` FOREIGN KEY (`session_id`) REFERENCES `phv_sessions` (`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

            CREATE TABLE IF NOT EXISTS `cash_book_entries` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `entry_date` DATE NOT NULL,
                `voucher_no` VARCHAR(50) NOT NULL,
                `entry_type` VARCHAR(20) NOT NULL,
                `account_title` VARCHAR(150) NOT NULL,
                `amount` DECIMAL(12,2) NOT NULL,
                `status` VARCHAR(30) DEFAULT 'Verified',
                `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

            CREATE TABLE IF NOT EXISTS `authorized_tasks` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `task_code` VARCHAR(50) NOT NULL UNIQUE,
                `title` VARCHAR(200) NOT NULL,
                `department` VARCHAR(100) NOT NULL,
                `priority` VARCHAR(20) DEFAULT 'Standard',
                `status` VARCHAR(30) DEFAULT 'Pending Approval',
                `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

            CREATE TABLE IF NOT EXISTS `payslip_cancellations` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `cancellation_ref` VARCHAR(50) NOT NULL UNIQUE,
                `payslip_no` VARCHAR(50) NOT NULL,
                `employee_name` VARCHAR(150) NOT NULL,
                `epf_no` VARCHAR(50) DEFAULT NULL,
                `pay_period` VARCHAR(30) NOT NULL,
                `amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
                `reason` TEXT NOT NULL,
                `requested_by` INT NULL DEFAULT 1,
                `status` VARCHAR(30) DEFAULT 'Pending Review',
                `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

            CREATE TABLE IF NOT EXISTS `stock_transfers` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `transfer_ref` VARCHAR(50) NOT NULL UNIQUE,
                `from_bay` VARCHAR(100) NOT NULL,
                `to_department` VARCHAR(100) NOT NULL,
                `sku` VARCHAR(50) NOT NULL,
                `item_name` VARCHAR(150) NOT NULL,
                `quantity` INT NOT NULL,
                `transfer_date` DATE NOT NULL,
                `status` VARCHAR(30) DEFAULT 'Completed',
                `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

            CREATE TABLE IF NOT EXISTS `cpc_procurements` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `order_no` VARCHAR(50) NOT NULL UNIQUE,
                `fuel_type` VARCHAR(100) NOT NULL,
                `tank_id` VARCHAR(50) NOT NULL,
                `quantity_litres` DECIMAL(12,2) NOT NULL,
                `unit_price_lkr` DECIMAL(10,2) NOT NULL,
                `total_amount_lkr` DECIMAL(14,2) NOT NULL,
                `consignment_date` DATE NOT NULL,
                `delivery_ref` VARCHAR(60) NOT NULL,
                `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

            CREATE TABLE IF NOT EXISTS `diesel_price_logs` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `effective_month` VARCHAR(20) NOT NULL,
                `fuel_type` VARCHAR(100) NOT NULL,
                `avg_price_per_litre` DECIMAL(10,2) NOT NULL,
                `total_procured_litres` DECIMAL(14,2) NOT NULL,
                `total_procurement_cost` DECIMAL(14,2) NOT NULL,
                `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

            CREATE TABLE IF NOT EXISTS `cost_adjustments` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `adjustment_ref` VARCHAR(50) NOT NULL UNIQUE,
                `batch_id` INT NULL,
                `sku` VARCHAR(50) NOT NULL,
                `item_name` VARCHAR(150) NOT NULL,
                `old_unit_cost` DECIMAL(10,2) NOT NULL,
                `new_unit_cost` DECIMAL(10,2) NOT NULL,
                `variance_per_unit` DECIMAL(10,2) NOT NULL,
                `remaining_qty` INT NOT NULL,
                `total_valuation_delta` DECIMAL(12,2) NOT NULL,
                `reason` TEXT NOT NULL,
                `authorized_by` INT NULL DEFAULT 1,
                `status` VARCHAR(30) DEFAULT 'Approved & Applied',
                `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

            CREATE TABLE IF NOT EXISTS `qty_corrections` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `correction_ref` VARCHAR(50) NOT NULL UNIQUE,
                `batch_id` INT NULL,
                `sku` VARCHAR(50) NOT NULL,
                `item_name` VARCHAR(150) NOT NULL,
                `allocated_qty_before` INT NOT NULL,
                `corrected_allocated_qty` INT NOT NULL,
                `qty_released_to_free_stock` INT NOT NULL,
                `work_order_ref` VARCHAR(60) DEFAULT NULL,
                `reason` TEXT NOT NULL,
                `authorized_by` INT NULL DEFAULT 1,
                `status` VARCHAR(30) DEFAULT 'Corrected & Unlocked',
                `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ";
    }

    /**
     * Create and provision a dedicated database for a power station
     */
    public static function createStationDb(string $code, string $name): string {
        $dbName = self::getDbName($code, $name);

        if (DB_DRIVER === 'mysql') {
            $pdo = new PDO(
                sprintf("mysql:host=%s;port=%s;charset=%s", DB_HOST, DB_PORT, DB_CHARSET),
                DB_USER,
                DB_PASS,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );

            $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");

            $stationPdo = new PDO(
                sprintf("mysql:host=%s;port=%s;dbname=%s;charset=%s", DB_HOST, DB_PORT, $dbName, DB_CHARSET),
                DB_USER,
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]
            );

            $stationPdo->exec(self::getStationSchemaSql());
        }

        return $dbName;
    }

    /**
     * Safely delete a station database
     */
    public static function deleteStationDb(string $dbName): bool {
        $cleanDb = trim($dbName);
        if (empty($cleanDb) || $cleanDb === DB_NAME || !str_starts_with($cleanDb, 'fifo_')) {
            return false;
        }

        if (DB_DRIVER === 'mysql') {
            try {
                $pdo = new PDO(
                    sprintf("mysql:host=%s;port=%s;charset=%s", DB_HOST, DB_PORT, DB_CHARSET),
                    DB_USER,
                    DB_PASS,
                    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
                );
                $pdo->exec("DROP DATABASE IF EXISTS `{$cleanDb}`;");
                return true;
            } catch (Exception $e) {
                error_log("Failed to drop station database {$cleanDb}: " . $e->getMessage());
                return false;
            }
        }
        return false;
    }
}
