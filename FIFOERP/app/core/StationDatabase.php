<?php
namespace App\Core;

use App\Config\Database;
use App\Models\Location;
use PDO;
use PDOException;
use Exception;

class StationDatabase {

    /**
     * Generate safe, clean MySQL database name for a station using current platform prefix (fifonew)
     */
    public static function getDbName(string $code, string $name): string {
        $cleanCode = preg_replace('/[^a-zA-Z0-9]/', '', strtolower($code));
        $cleanName = strtolower($name);
        $cleanName = str_replace(['power station', 'power plant', 'station'], '', $cleanName);
        $cleanName = preg_replace('/[^a-zA-Z0-9]/', '_', trim($cleanName));
        $cleanName = trim(preg_replace('/_+/', '_', $cleanName), '_');

        $prefix = defined('DB_NAME') ? DB_NAME : 'fifonew';

        if (!empty($cleanCode) && !empty($cleanName)) {
            return "{$prefix}_{$cleanCode}_{$cleanName}";
        } elseif (!empty($cleanCode)) {
            return "{$prefix}_{$cleanCode}";
        }
        return "{$prefix}_station";
    }

    /**
     * DDL for Station-Specific Isolated Operational Tables
     */
    public static function getStationSchemaSql(): string {
        return <<<'SQL'
CREATE TABLE IF NOT EXISTS `authorized_tasks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `task_code` varchar(50) NOT NULL,
  `title` varchar(200) NOT NULL,
  `department` varchar(100) NOT NULL,
  `priority` varchar(20) DEFAULT 'Standard',
  `status` varchar(30) DEFAULT 'Pending Approval',
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `task_code` (`task_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `cash_book_entries` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `location_id` int(11) DEFAULT 1,
  `entry_date` date NOT NULL,
  `voucher_no` varchar(50) NOT NULL,
  `entry_type` varchar(20) NOT NULL,
  `account_title` varchar(150) NOT NULL,
  `payee_or_source` varchar(150) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `amount` decimal(12,2) NOT NULL,
  `status` varchar(30) DEFAULT 'Verified',
  `created_by` int(11) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `cost_adjustments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `adjustment_ref` varchar(50) NOT NULL,
  `batch_id` int(11) DEFAULT NULL,
  `sku` varchar(50) NOT NULL,
  `item_name` varchar(150) NOT NULL,
  `old_unit_cost` decimal(10,2) NOT NULL,
  `new_unit_cost` decimal(10,2) NOT NULL,
  `variance_per_unit` decimal(10,2) NOT NULL,
  `remaining_qty` int(11) NOT NULL,
  `total_valuation_delta` decimal(12,2) NOT NULL,
  `reason` text NOT NULL,
  `authorized_by` int(11) DEFAULT 1,
  `status` varchar(30) DEFAULT 'Approved & Applied',
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `adjustment_ref` (`adjustment_ref`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `cpc_procurements` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `location_id` int(11) DEFAULT 1,
  `order_no` varchar(50) NOT NULL,
  `fuel_type` varchar(100) NOT NULL,
  `tank_id` varchar(50) NOT NULL,
  `quantity_litres` decimal(12,2) NOT NULL,
  `unit_price_lkr` decimal(10,2) NOT NULL,
  `total_amount_lkr` decimal(14,2) NOT NULL,
  `consignment_date` date NOT NULL,
  `delivery_ref` varchar(60) NOT NULL,
  `bowser_no` varchar(50) DEFAULT NULL,
  `supplier_depot` varchar(100) DEFAULT NULL,
  `status` varchar(30) DEFAULT 'Delivered',
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `order_no` (`order_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `diesel_price_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `effective_month` varchar(20) NOT NULL,
  `fuel_type` varchar(100) NOT NULL,
  `avg_price_per_litre` decimal(10,2) NOT NULL,
  `total_procured_litres` decimal(14,2) NOT NULL,
  `total_procurement_cost` decimal(14,2) NOT NULL,
  `price_trend` varchar(20) DEFAULT 'Stable',
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `fifo_batches` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `location_id` int(11) DEFAULT 1,
  `batch_number` varchar(50) NOT NULL,
  `sku` varchar(50) NOT NULL,
  `item_name` varchar(150) NOT NULL,
  `category` varchar(100) DEFAULT 'Standard',
  `initial_quantity` int(11) NOT NULL,
  `remaining_quantity` int(11) NOT NULL,
  `unit_cost` decimal(12,2) NOT NULL DEFAULT 0.00,
  `received_date` date NOT NULL,
  `expiry_date` date DEFAULT NULL,
  `location_bin` varchar(50) DEFAULT 'Zone A-01',
  `status` varchar(20) DEFAULT 'active',
  `freeze_status` varchar(20) DEFAULT 'unfrozen',
  `freeze_session_id` int(11) DEFAULT NULL,
  `created_by` int(11) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `batch_number` (`batch_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `inventory_transactions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `location_id` int(11) DEFAULT 1,
  `batch_id` int(11) DEFAULT NULL,
  `transaction_type` varchar(20) NOT NULL,
  `quantity` int(11) NOT NULL,
  `unit_price` decimal(12,2) NOT NULL DEFAULT 0.00,
  `total_value` decimal(14,2) NOT NULL DEFAULT 0.00,
  `transaction_date` date NOT NULL,
  `reference_no` varchar(60) DEFAULT NULL,
  `document_no` varchar(60) DEFAULT NULL,
  `sku` varchar(50) DEFAULT NULL,
  `item_name` varchar(150) DEFAULT NULL,
  `destination_code` varchar(50) DEFAULT NULL,
  `department_name` varchar(100) DEFAULT NULL,
  `status` varchar(20) DEFAULT 'posted',
  `created_by` int(11) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_tx_batch` (`batch_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `payslip_cancellations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `location_id` int(11) DEFAULT 1,
  `cancellation_ref` varchar(50) NOT NULL,
  `payslip_no` varchar(50) NOT NULL,
  `employee_name` varchar(150) NOT NULL,
  `epf_no` varchar(50) DEFAULT NULL,
  `department` varchar(100) DEFAULT NULL,
  `pay_period` varchar(30) NOT NULL,
  `amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `reason` text NOT NULL,
  `requested_by` int(11) DEFAULT 1,
  `approved_by` int(11) DEFAULT NULL,
  `status` varchar(30) DEFAULT 'Pending Review',
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `cancellation_ref` (`cancellation_ref`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `phv_sessions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `location_id` int(11) DEFAULT 1,
  `session_code` varchar(50) NOT NULL,
  `session_title` varchar(150) NOT NULL,
  `verification_date` date NOT NULL,
  `freeze_status` varchar(20) DEFAULT 'unfrozen',
  `frozen_at` datetime DEFAULT NULL,
  `frozen_by` int(11) DEFAULT NULL,
  `unfrozen_at` datetime DEFAULT NULL,
  `unfrozen_by` int(11) DEFAULT NULL,
  `status` varchar(30) DEFAULT 'Draft',
  `remarks` text DEFAULT NULL,
  `created_by` int(11) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `session_code` (`session_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `phv_entries` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `session_id` int(11) NOT NULL,
  `batch_id` int(11) NOT NULL,
  `sku` varchar(50) NOT NULL,
  `item_name` varchar(150) NOT NULL,
  `bin_location` varchar(50) DEFAULT NULL,
  `book_quantity` int(11) NOT NULL DEFAULT 0,
  `physical_quantity` int(11) NOT NULL DEFAULT 0,
  `variance_quantity` int(11) GENERATED ALWAYS AS (`physical_quantity` - `book_quantity`) STORED,
  `unit_cost` decimal(12,2) NOT NULL DEFAULT 0.00,
  `variance_value` decimal(14,2) GENERATED ALWAYS AS ((`physical_quantity` - `book_quantity`) * `unit_cost`) STORED,
  `verified_by` int(11) DEFAULT NULL,
  `remarks` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_phv_session` (`session_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `qty_corrections` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `correction_ref` varchar(50) NOT NULL,
  `batch_id` int(11) DEFAULT NULL,
  `sku` varchar(50) NOT NULL,
  `item_name` varchar(150) NOT NULL,
  `allocated_qty_before` int(11) NOT NULL,
  `corrected_allocated_qty` int(11) NOT NULL,
  `qty_released_to_free_stock` int(11) NOT NULL,
  `work_order_ref` varchar(60) DEFAULT NULL,
  `reason` text NOT NULL,
  `authorized_by` int(11) DEFAULT 1,
  `status` varchar(30) DEFAULT 'Corrected & Unlocked',
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `correction_ref` (`correction_ref`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `stock_transfers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `transfer_ref` varchar(50) NOT NULL,
  `from_location` int(11) DEFAULT 1,
  `from_bay` varchar(100) NOT NULL,
  `to_department` varchar(100) NOT NULL,
  `sku` varchar(50) NOT NULL,
  `item_name` varchar(150) NOT NULL,
  `quantity` int(11) NOT NULL,
  `transfer_date` date NOT NULL,
  `status` varchar(30) DEFAULT 'Completed',
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `transfer_ref` (`transfer_ref`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `unposted_documents` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `location_id` int(11) DEFAULT 1,
  `doc_type` varchar(30) NOT NULL,
  `doc_no` varchar(60) NOT NULL,
  `doc_date` date NOT NULL,
  `receipt_from` varchar(150) DEFAULT NULL,
  `department_code` varchar(50) DEFAULT NULL,
  `destination_code` varchar(50) DEFAULT NULL,
  `warehouse` varchar(100) DEFAULT 'Main Store Bay-01',
  `status` varchar(30) DEFAULT 'Draft',
  `invoice_total` decimal(14,2) DEFAULT 0.00,
  `vat` decimal(10,2) DEFAULT 0.00,
  `transport` decimal(10,2) DEFAULT 0.00,
  `nbt` decimal(10,2) DEFAULT 0.00,
  `other_cost` decimal(10,2) DEFAULT 0.00,
  `invoice_number` varchar(60) DEFAULT NULL,
  `order_reference` varchar(60) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `created_by` int(11) DEFAULT 1,
  `validated_by` int(11) DEFAULT NULL,
  `approved_by` int(11) DEFAULT NULL,
  `posted_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `doc_no` (`doc_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `unposted_document_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `document_id` int(11) NOT NULL,
  `batch_id` int(11) DEFAULT NULL,
  `sku` varchar(50) NOT NULL,
  `item_name` varchar(150) NOT NULL,
  `requested_quantity` int(11) NOT NULL DEFAULT 0,
  `quantity` int(11) NOT NULL,
  `unit_cost` decimal(12,2) NOT NULL DEFAULT 0.00,
  `total_cost` decimal(14,2) NOT NULL DEFAULT 0.00,
  `bin_location` varchar(50) DEFAULT 'Bay A',
  `expiry_date` date DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_unposted_items_doc` (`document_id`),
  CONSTRAINT `fk_unposted_items_doc` FOREIGN KEY (`document_id`) REFERENCES `unposted_documents` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL;
    }

    /**
     * Create dedicated station database and its operational tables
     */
    public static function createStationDb(string $code, string $name, ?string $customDbName = null): string {
        $dbName = $customDbName ?: self::getDbName($code, $name);

        $hostPdo = new PDO(
            sprintf("mysql:host=%s;port=%s;charset=%s", DB_HOST, DB_PORT, DB_CHARSET),
            DB_USER,
            DB_PASS,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]
        );

        $hostPdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");

        $stationPdo = new PDO(
            sprintf("mysql:host=%s;port=%s;dbname=%s;charset=%s", DB_HOST, DB_PORT, $dbName, DB_CHARSET),
            DB_USER,
            DB_PASS,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => true,
            ]
        );

        $stationPdo->exec(self::getStationSchemaSql());

        // Update database_name in central locations table if location exists
        try {
            $central = Database::getConnection();
            $stmt = $central->prepare("UPDATE locations SET database_name = :dbname WHERE code = :code OR name = :name");
            $stmt->execute([
                ':dbname' => $dbName,
                ':code' => $code,
                ':name' => $name,
            ]);
        } catch (\Throwable $e) {
            // Non-blocking
        }

        return $dbName;
    }

    /**
     * Provision isolated databases for all known power stations in fifonew
     */
    public static function provisionAllStations(): array {
        $central = Database::getConnection();
        $stations = $central->query("SELECT id, code, name, database_name FROM locations ORDER BY id ASC")->fetchAll();

        $provisioned = [];
        foreach ($stations as $st) {
            $dbName = $st['database_name'] ?: self::getDbName($st['code'], $st['name']);
            self::createStationDb($st['code'], $st['name'], $dbName);
            $provisioned[$st['code']] = $dbName;
        }

        return $provisioned;
    }
}
