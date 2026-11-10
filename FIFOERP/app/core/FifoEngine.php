<?php
namespace App\Core;

use App\Config\Database;
use PDO;
use Exception;

class FifoEngine {
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
        $db = Database::getConnection();
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
        $db = Database::getConnection();
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

