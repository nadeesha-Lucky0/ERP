<?php
require_once dirname(__DIR__) . '/app/config/config.php';

use App\Core\Auth;
use App\Core\Response;
use App\Config\Database;

header('Content-Type: application/json; charset=utf-8');

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$locationId = $_POST['location_id'] ?? $_GET['location_id'] ?? ($_SESSION['user']['location_id'] ?? ($_SESSION['location_id'] ?? 1));

try {
    $db = Database::getStationConnection($locationId);

    switch ($action) {
        // -------------------------------------------------------------
        // Cost Adjustments
        // -------------------------------------------------------------
        case 'get_cost_adjustments':
            $stmt = $db->query("SELECT * FROM cost_adjustments ORDER BY id DESC");
            $rows = $stmt->fetchAll();
            Response::success('Cost adjustments loaded', ['data' => $rows]);
            break;

        case 'add_cost_adjustment':
            $batchId = (int)($_POST['batch_id'] ?? 0);
            $newCost = (float)($_POST['new_unit_cost'] ?? 0);
            $reason = trim($_POST['reason'] ?? '');

            if ($batchId <= 0 || $newCost <= 0 || empty($reason)) {
                Response::error('Valid Batch ID, New Unit Cost, and Reason required.');
            }

            $bStmt = $db->prepare("SELECT * FROM fifo_batches WHERE id = :bid");
            $bStmt->execute([':bid' => $batchId]);
            $b = $bStmt->fetch();
            if (!$b) Response::error('Batch not found');

            $oldCost = (float)$b['unit_cost'];
            $variance = $newCost - $oldCost;
            $remQty = (int)$b['remaining_quantity'];
            $totalDelta = $remQty * $variance;
            $ref = 'CADJ-' . date('Y') . '-' . str_pad((string)rand(10, 999), 3, '0', STR_PAD_LEFT);

            $db->beginTransaction();
            // Update batch unit cost
            $db->prepare("UPDATE fifo_batches SET unit_cost = :cost WHERE id = :bid")
               ->execute([':cost' => $newCost, ':bid' => $batchId]);

            // Insert log
            $ins = $db->prepare("
                INSERT INTO cost_adjustments 
                (adjustment_ref, batch_id, sku, item_name, old_unit_cost, new_unit_cost, variance_per_unit, remaining_qty, total_valuation_delta, reason, authorized_by, status)
                VALUES
                (:ref, :bid, :sku, :name, :old, :new, :var, :rem, :tot, :reason, :uid, 'Approved & Applied')
            ");
            $ins->execute([
                ':ref' => $ref,
                ':bid' => $batchId,
                ':sku' => $b['sku'],
                ':name' => $b['item_name'],
                ':old' => $oldCost,
                ':new' => $newCost,
                ':var' => $variance,
                ':rem' => $remQty,
                ':tot' => $totalDelta,
                ':reason' => $reason,
                ':uid' => Auth::id() ?? 1
            ]);
            $db->commit();

            Response::success('Cost Adjustment applied successfully', ['ref' => $ref, 'total_delta' => $totalDelta]);
            break;

        // -------------------------------------------------------------
        // Quantity Corrections
        // -------------------------------------------------------------
        case 'get_qty_corrections':
            $stmt = $db->query("SELECT * FROM qty_corrections ORDER BY id DESC");
            $rows = $stmt->fetchAll();
            Response::success('Quantity corrections loaded', ['data' => $rows]);
            break;

        case 'add_qty_correction':
            $batchId = (int)($_POST['batch_id'] ?? 0);
            $qtyBefore = (int)($_POST['allocated_qty_before'] ?? 0);
            $qtyAfter = (int)($_POST['corrected_allocated_qty'] ?? 0);
            $woRef = trim($_POST['work_order_ref'] ?? '');
            $reason = trim($_POST['reason'] ?? '');

            if ($batchId <= 0 || empty($reason)) {
                Response::error('Batch ID and Reason are required.');
            }

            $bStmt = $db->prepare("SELECT * FROM fifo_batches WHERE id = :bid");
            $bStmt->execute([':bid' => $batchId]);
            $b = $bStmt->fetch();
            if (!$b) Response::error('Batch not found');

            $released = max(0, $qtyBefore - $qtyAfter);
            $ref = 'QCOR-' . date('Y') . '-' . str_pad((string)rand(10, 999), 3, '0', STR_PAD_LEFT);

            $ins = $db->prepare("
                INSERT INTO qty_corrections 
                (correction_ref, batch_id, sku, item_name, allocated_qty_before, corrected_allocated_qty, qty_released_to_free_stock, work_order_ref, reason, authorized_by, status)
                VALUES
                (:ref, :bid, :sku, :name, :before, :after, :rel, :wo, :reason, :uid, 'Corrected & Unlocked')
            ");
            $ins->execute([
                ':ref' => $ref,
                ':bid' => $batchId,
                ':sku' => $b['sku'],
                ':name' => $b['item_name'],
                ':before' => $qtyBefore,
                ':after' => $qtyAfter,
                ':rel' => $released,
                ':wo' => $woRef,
                ':reason' => $reason,
                ':uid' => Auth::id() ?? 1
            ]);

            Response::success('Quantity correction logged and stock unlocked', ['ref' => $ref]);
            break;

        // -------------------------------------------------------------
        // Issue Categories & Destination Codes
        // -------------------------------------------------------------
        case 'get_destination_codes':
            $stmt = $db->query("SELECT * FROM issue_destination_codes ORDER BY category_name ASC, destination_name ASC");
            $rows = $stmt->fetchAll();
            Response::success('Destination codes loaded', ['data' => $rows]);
            break;

        case 'add_destination_code':
            $catCode = trim($_POST['category_code'] ?? 'GEN');
            $catName = trim($_POST['category_name'] ?? 'General');
            $destCode = trim($_POST['destination_code'] ?? '');
            $destName = trim($_POST['destination_name'] ?? '');
            $desc = trim($_POST['description'] ?? '');

            if (empty($destCode) || empty($destName)) {
                Response::error('Destination Code and Name are required.');
            }

            $stmt = $db->prepare("
                INSERT INTO issue_destination_codes (category_code, category_name, destination_code, destination_name, description)
                VALUES (:ccode, :cname, :dcode, :dname, :desc)
            ");
            $stmt->execute([
                ':ccode' => $catCode,
                ':cname' => $catName,
                ':dcode' => $destCode,
                ':dname' => $destName,
                ':desc' => $desc
            ]);

            Response::success('Destination Code added successfully');
            break;

        default:
            Response::error('Invalid task action: ' . $action);
    }
} catch (Exception $e) {
    Response::error($e->getMessage());
}

