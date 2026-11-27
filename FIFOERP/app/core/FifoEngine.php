<?php
namespace App\Core;

use App\Config\Database;
use PDO;
use Exception;

class FifoEngine {
    private static function ensureAllocationsTable(PDO $db): void {
        $db->exec("CREATE TABLE IF NOT EXISTS issue_grn_allocations (
            id INT AUTO_INCREMENT PRIMARY KEY,
            location_id INT NOT NULL,
            document_no VARCHAR(80) NOT NULL,
            batch_id INT NOT NULL,
            sku VARCHAR(50) NOT NULL,
            qty_deducted INT NOT NULL,
            unit_cost DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            status ENUM('ACTIVE','CANCELLED') NOT NULL DEFAULT 'ACTIVE',
            created_by INT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            cancelled_by INT NULL,
            cancelled_at DATETIME NULL,
            INDEX idx_doc_loc (document_no, location_id, status),
            INDEX idx_batch (batch_id)
        )");
    }

    /**
     * Process Material Issue using strict First-In, First-Out (FIFO) queue
     */
    public static function issueStock(
        int $locationId,
        string $sku,
        int $requestedQty,
        string $documentNo,
        ?string $destinationCode = null,
        ?string $departmentName = null,
        ?int $userId = 1
    ): array {
        $db = Database::getStationConnection($locationId);
        self::ensureAllocationsTable($db);
        $db->beginTransaction();

        try {
            // 1. Check if batches for this SKU are currently frozen by PHV
            $chkFrozen = $db->prepare("
                SELECT COUNT(*) FROM fifo_batches
                WHERE sku = :sku AND location_id = :loc AND freeze_status = 'frozen' AND remaining_quantity > 0
            ");
            $chkFrozen->execute([':sku' => $sku, ':loc' => $locationId]);
            if ($chkFrozen->fetchColumn() > 0) {
                throw new Exception("Stock movement is BLOCKED: SKU '{$sku}' has batches locked under active Physical Verification (PHV Frozen).");
            }

            // 2. Fetch available active batches in strict FIFO order (Oldest received first)
            $stmt = $db->prepare("
                SELECT * FROM fifo_batches
                WHERE sku = :sku AND location_id = :loc AND remaining_quantity > 0 AND status = 'active'
                ORDER BY received_date ASC, id ASC
                FOR UPDATE
            ");
            $stmt->execute([':sku' => $sku, ':loc' => $locationId]);
            $batches = $stmt->fetchAll();

            $totalAvailable = array_sum(array_column($batches, 'remaining_quantity'));
            if ($totalAvailable < $requestedQty) {
                throw new Exception("Insufficient stock for SKU '{$sku}'. Available: {$totalAvailable}, Requested: {$requestedQty}");
            }

            $remainingToIssue = $requestedQty;
            $allocations = [];
            $totalIssueValue = 0.0;
            $itemName = '';

            foreach ($batches as $batch) {
                if ($remainingToIssue <= 0) break;

                $batchAvail = (int)$batch['remaining_quantity'];
                $deduct = min($remainingToIssue, $batchAvail);
                $newRemaining = $batchAvail - $deduct;
                $newStatus = ($newRemaining === 0) ? 'exhausted' : 'active';
                $unitCost = (float)$batch['unit_cost'];
                $subTotal = $deduct * $unitCost;

                $totalIssueValue += $subTotal;
                $itemName = $batch['item_name'];

                // Update batch remaining quantity
                $upd = $db->prepare("
                    UPDATE fifo_batches
                    SET remaining_quantity = :rem, status = :st
                    WHERE id = :id
                ");
                $upd->execute([':rem' => $newRemaining, ':st' => $newStatus, ':id' => $batch['id']]);

                // Record transaction in ledger
                $insTx = $db->prepare("
                    INSERT INTO inventory_transactions
                    (location_id, batch_id, transaction_type, document_no, sku, item_name, quantity, unit_price, total_value, transaction_date, destination_code, department_name, created_by, status)
                    VALUES
                    (:loc, :batch_id, 'MATERIAL_ISSUE', :doc_no, :sku, :name, :qty, :price, :val, CURDATE(), :dest, :dept, :uid, 'POSTED')
                ");
                $insTx->execute([
                    ':loc' => $locationId,
                    ':batch_id' => $batch['id'],
                    ':doc_no' => $documentNo,
                    ':sku' => $sku,
                    ':name' => $itemName,
                    ':qty' => $deduct,
                    ':price' => $unitCost,
                    ':val' => $subTotal,
                    ':dest' => $destinationCode,
                    ':dept' => $departmentName,
                    ':uid' => $userId
                ]);

                // Record batch-level allocation for exact FIFO reversal on cancellation
                $insAlloc = $db->prepare("
                    INSERT INTO issue_grn_allocations
                    (location_id, document_no, batch_id, sku, qty_deducted, unit_cost, status, created_by)
                    VALUES (:loc, :doc_no, :batch_id, :sku, :qty, :price, 'ACTIVE', :uid)
                ");
                $insAlloc->execute([
                    ':loc' => $locationId,
                    ':doc_no' => $documentNo,
                    ':batch_id' => $batch['id'],
                    ':sku' => $sku,
                    ':qty' => $deduct,
                    ':price' => $unitCost,
                    ':uid' => $userId
                ]);

                $allocations[] = [
                    'batch_id' => $batch['id'],
                    'batch_number' => $batch['batch_number'],
                    'quantity' => $deduct,
                    'unit_cost' => $unitCost,
                    'total_cost' => $subTotal
                ];

                $remainingToIssue -= $deduct;
            }

            $db->commit();

            return [
                'success' => true,
                'sku' => $sku,
                'item_name' => $itemName,
                'issued_qty' => $requestedQty,
                'total_value' => $totalIssueValue,
                'allocations' => $allocations
            ];
        } catch (Exception $e) {
            $db->rollBack();
            throw $e;
        }
    }

    /**
     * Cancel a Posted Issue and restore quantities back to their exact original FIFO batches
     */
    public static function cancelIssue(int $locationId, string $documentNo, int $userId): array {
        $db = Database::getStationConnection($locationId);
        self::ensureAllocationsTable($db);
        $db->beginTransaction();

        try {
            // Idempotency guard: prevent double cancellation
            $chkCancelled = $db->prepare("
                SELECT COUNT(*) FROM issue_grn_allocations
                WHERE document_no = :doc AND location_id = :loc AND status = 'CANCELLED'
            ");
            $chkCancelled->execute([':doc' => $documentNo, ':loc' => $locationId]);
            $chkRev = $db->prepare("
                SELECT COUNT(*) FROM inventory_transactions
                WHERE document_no = :doc AND location_id = :loc AND transaction_type = 'ISSUE_REVERSAL'
            ");
            $chkRev->execute([':doc' => $documentNo, ':loc' => $locationId]);
            if ((int)$chkCancelled->fetchColumn() > 0 || (int)$chkRev->fetchColumn() > 0) {
                throw new Exception("Issue {$documentNo} has already been cancelled.");
            }

            $stAlloc = $db->prepare("
                SELECT * FROM issue_grn_allocations
                WHERE document_no = :doc AND location_id = :loc AND status = 'ACTIVE'
                FOR UPDATE
            ");
            $stAlloc->execute([':doc' => $documentNo, ':loc' => $locationId]);
            $allocs = $stAlloc->fetchAll();

            // Fallback for legacy issues posted before issue_grn_allocations existed
            if (empty($allocs)) {
                $stLegacy = $db->prepare("
                    SELECT batch_id, sku, quantity AS qty_deducted, unit_price AS unit_cost
                    FROM inventory_transactions
                    WHERE document_no = :doc AND location_id = :loc AND transaction_type = 'MATERIAL_ISSUE' AND status = 'POSTED'
                    FOR UPDATE
                ");
                $stLegacy->execute([':doc' => $documentNo, ':loc' => $locationId]);
                $allocs = $stLegacy->fetchAll();
            }

            $restored = [];
            foreach ($allocs as $a) {
                $batchId = (int)$a['batch_id'];
                $qty = (int)$a['qty_deducted'];
                if ($qty <= 0) continue;

                // Lock exact original batch
                $stBatch = $db->prepare("SELECT id, item_name, remaining_quantity FROM fifo_batches WHERE id = :id FOR UPDATE");
                $stBatch->execute([':id' => $batchId]);
                $batch = $stBatch->fetch();
                if (!$batch) continue;

                $newRem = (int)$batch['remaining_quantity'] + $qty;
                $db->prepare("UPDATE fifo_batches SET remaining_quantity = :rem, status = 'active' WHERE id = :id")
                   ->execute([':rem' => $newRem, ':id' => $batchId]);

                // Audit trail in inventory_transactions
                $db->prepare("
                    INSERT INTO inventory_transactions
                    (location_id, batch_id, transaction_type, document_no, sku, item_name, quantity, unit_price, total_value, transaction_date, created_by, status)
                    VALUES (:loc, :bid, 'ISSUE_REVERSAL', :doc, :sku, :name, :qty, :price, :val, CURDATE(), :uid, 'POSTED')
                ")->execute([
                    ':loc' => $locationId,
                    ':bid' => $batchId,
                    ':doc' => $documentNo,
                    ':sku' => $a['sku'],
                    ':name' => $batch['item_name'],
                    ':qty' => $qty,
                    ':price' => (float)$a['unit_cost'],
                    ':val' => $qty * (float)$a['unit_cost'],
                    ':uid' => $userId
                ]);

                $restored[] = ['batch_id' => $batchId, 'sku' => $a['sku'], 'restored_qty' => $qty];
            }

            // Mark allocations as CANCELLED (idempotency)
            $db->prepare("
                UPDATE issue_grn_allocations
                SET status = 'CANCELLED', cancelled_by = :uid, cancelled_at = NOW()
                WHERE document_no = :doc AND location_id = :loc AND status = 'ACTIVE'
            ")->execute([':uid' => $userId, ':doc' => $documentNo, ':loc' => $locationId]);

            $db->commit();
            return ['success' => true, 'restored' => $restored];
        } catch (Exception $e) {
            $db->rollBack();
            throw $e;
        }
    }

    /**
     * Process Goods Receipt (GRN) into FIFO Queue
     */
    public static function receiveStock(
        int $locationId,
        string $sku,
        string $itemName,
        string $category,
        int $quantity,
        float $unitCost,
        string $documentNo,
        string $locationBin = 'Bay A',
        ?string $expiryDate = null,
        ?int $userId = 1
    ): int {
        $db = Database::getStationConnection($locationId);
        $db->beginTransaction();

        try {
            $batchNumber = 'B-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -5));

            // Create new FIFO batch
            $stmt = $db->prepare("
                INSERT INTO fifo_batches
                (location_id, batch_number, sku, item_name, category, initial_quantity, remaining_quantity, unit_cost, received_date, expiry_date, location_bin, status, freeze_status, created_by)
                VALUES
                (:loc, :b_no, :sku, :name, :cat, :init_qty, :rem_qty, :cost, CURDATE(), :exp, :bin, 'active', 'unfrozen', :uid)
            ");
            $stmt->execute([
                ':loc' => $locationId,
                ':b_no' => $batchNumber,
                ':sku' => $sku,
                ':name' => $itemName,
                ':cat' => $category,
                ':init_qty' => $quantity,
                ':rem_qty' => $quantity,
                ':cost' => $unitCost,
                ':exp' => $expiryDate,
                ':bin' => $locationBin,
                ':uid' => $userId
            ]);
            $batchId = (int)$db->lastInsertId();

            // Record transaction
            $insTx = $db->prepare("
                INSERT INTO inventory_transactions
                (location_id, batch_id, transaction_type, document_no, sku, item_name, quantity, unit_price, total_value, transaction_date, destination_code, department_name, created_by, status)
                VALUES
                (:loc, :batch_id, 'GRN_RECEIPT', :doc_no, :sku, :name, :qty, :price, :val, CURDATE(), 'DEST-ST-01', 'Central Stores', :uid, 'POSTED')
            ");
            $insTx->execute([
                ':loc' => $locationId,
                ':batch_id' => $batchId,
                ':doc_no' => $documentNo,
                ':sku' => $sku,
                ':name' => $itemName,
                ':qty' => $quantity,
                ':price' => $unitCost,
                ':val' => $quantity * $unitCost,
                ':uid' => $userId
            ]);

            $db->commit();
            return $batchId;
        } catch (Exception $e) {
            $db->rollBack();
            throw $e;
        }
    }

    /**
     * Freeze or Unfreeze all batches for Physical Verification session
     */
    public static function setBatchFreeze(int $locationId, bool $freeze, int $sessionId, int $userId): void {
        $db = Database::getConnection();
        $status = $freeze ? 'frozen' : 'unfrozen';
        $sessId = $freeze ? $sessionId : null;

        $stmt = $db->prepare("
            UPDATE fifo_batches
            SET freeze_status = :st, freeze_session_id = :sid
            WHERE location_id = :loc AND remaining_quantity > 0
        ");
        $stmt->execute([':st' => $status, ':sid' => $sessId, ':loc' => $locationId]);

        // Update session
        if ($freeze) {
            $upd = $db->prepare("
                UPDATE phv_sessions
                SET freeze_status = 'frozen', frozen_at = NOW(), frozen_by = :uid, status = 'Frozen'
                WHERE id = :id
            ");
        } else {
            $upd = $db->prepare("
                UPDATE phv_sessions
                SET freeze_status = 'unfrozen', unfrozen_at = NOW(), unfrozen_by = :uid
                WHERE id = :id
            ");
        }
        $upd->execute([':uid' => $userId, ':id' => $sessionId]);
    }
}

