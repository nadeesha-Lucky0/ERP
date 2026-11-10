<?php
require_once dirname(__DIR__) . '/app/config/config.php';

use App\Core\Auth;
use App\Core\Response;
use App\Core\FifoEngine;
use App\Config\Database;

header('Content-Type: application/json; charset=utf-8');

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$locationId = (int)($_SESSION['location_id'] ?? 1);

try {
    $db = Database::getStationConnection($locationId);

    switch ($action) {
        // -------------------------------------------------------------
        // Batches List (Quantity On Hand) with Live Reservation Data
        // -------------------------------------------------------------
        case 'get_batches':
            $stmt = $db->prepare("
                SELECT b.*,
                    COALESCE((
                        SELECT SUM(udi.quantity)
                        FROM unposted_document_items udi
                        JOIN unposted_documents ud ON udi.document_id = ud.id
                        WHERE udi.sku = b.sku 
                          AND ud.location_id = b.location_id
                          AND ud.doc_type = 'ISSUE'
                          AND ud.status IN ('Draft', 'Validated', 'Approved')
                    ), 0) as sku_allocated_quantity
                FROM fifo_batches b 
                WHERE b.location_id = :loc 
                ORDER BY b.received_date DESC, b.id DESC
            ");
            $stmt->execute([':loc' => $locationId]);
            $batches = $stmt->fetchAll();
            Response::success('Batches loaded', ['batches' => $batches]);
            break;

        // -------------------------------------------------------------
        // Three-Tier Real-Time Stock Availability & Reservation Check
        // -------------------------------------------------------------
        case 'get_stock_availability':
            $sku = trim($_GET['sku'] ?? '');
            if (empty($sku)) {
                Response::error('SKU is required.');
            }

            // 1. Physical on-hand from active batches
            $stTotPhys = $db->prepare("
                SELECT COALESCE(SUM(remaining_quantity), 0) FROM fifo_batches
                WHERE sku = :sku AND location_id = :loc AND status = 'active'
            ");
            $stTotPhys->execute([':sku' => $sku, ':loc' => $locationId]);
            $physicalQty = (int)$stTotPhys->fetchColumn();

            $stmtPhysical = $db->prepare("
                SELECT item_name, unit_cost
                FROM fifo_batches 
                WHERE sku = :sku AND location_id = :loc AND status = 'active'
                ORDER BY received_date ASC
                LIMIT 1
            ");
            $stmtPhysical->execute([':sku' => $sku, ':loc' => $locationId]);
            $physRow = $stmtPhysical->fetch();
            $itemName = $physRow['item_name'] ?? $sku;
            $unitCost = (float)($physRow['unit_cost'] ?? 0);

            // 2. Allocated / Reserved quantity in pending unposted issues (Draft, Validated, Approved)
            $stmtAlloc = $db->prepare("
                SELECT COALESCE(SUM(udi.quantity), 0) as allocated_qty,
                       COUNT(DISTINCT ud.id) as pending_doc_count
                FROM unposted_document_items udi
                JOIN unposted_documents ud ON udi.document_id = ud.id
                WHERE udi.sku = :sku 
                  AND ud.location_id = :loc
                  AND ud.doc_type = 'ISSUE'
                  AND ud.status IN ('Draft', 'Validated', 'Approved')
            ");
            $stmtAlloc->execute([':sku' => $sku, ':loc' => $locationId]);
            $allocRow = $stmtAlloc->fetch();
            $allocatedQty = (int)($allocRow['allocated_qty'] ?? 0);
            $pendingDocsCount = (int)($allocRow['pending_doc_count'] ?? 0);

            // 3. True available stock to issue (Free Stock)
            $availableQty = max(0, $physicalQty - $allocatedQty);

            Response::success('Stock availability retrieved', [
                'sku' => $sku,
                'item_name' => $itemName,
                'unit_cost' => $unitCost,
                'physical_on_hand' => $physicalQty,
                'allocated_reserved' => $allocatedQty,
                'available_to_issue' => $availableQty,
                'pending_doc_count' => $pendingDocsCount
            ]);
            break;

        // -------------------------------------------------------------
        // Unposted / Active Documents (GRN & Issues)
        // -------------------------------------------------------------
        case 'get_documents':
            $docType = $_GET['type'] ?? '';
            $sql = "SELECT d.*, 
                    (SELECT COUNT(*) FROM unposted_document_items WHERE document_id = d.id) as item_count
                    FROM unposted_documents d 
                    WHERE d.location_id = :loc";
            $params = [':loc' => $locationId];

            if (!empty($docType)) {
                $sql .= " AND d.doc_type = :type";
                $params[':type'] = strtoupper($docType);
            }
            $sql .= " ORDER BY d.id DESC";

            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            $docs = $stmt->fetchAll();
            Response::success('Documents loaded', ['documents' => $docs]);
            break;

        // -------------------------------------------------------------
        // Single Document Detail with Items
        // -------------------------------------------------------------
        case 'get_document_detail':
            $docId = (int)($_GET['id'] ?? 0);
            $stmt = $db->prepare("SELECT * FROM unposted_documents WHERE id = :id AND location_id = :loc");
            $stmt->execute([':id' => $docId, ':loc' => $locationId]);
            $doc = $stmt->fetch();
            if (!$doc) {
                Response::error('Document not found');
            }

            $itemsStmt = $db->prepare("SELECT * FROM unposted_document_items WHERE document_id = :id");
            $itemsStmt->execute([':id' => $docId]);
            $items = $itemsStmt->fetchAll();

            Response::success('Document details', ['document' => $doc, 'items' => $items]);
            break;

        // -------------------------------------------------------------
        // Create Document (GRN or Issue)
        // -------------------------------------------------------------
        case 'create_document':
            $docType = strtoupper(trim($_POST['doc_type'] ?? 'GRN'));
            $receiptFrom = trim($_POST['receipt_from'] ?? '');
            $deptCode = trim($_POST['department_code'] ?? 'STORES-01');
            $destCode = trim($_POST['destination_code'] ?? 'DEST-ST-01');
            $warehouse = trim($_POST['warehouse'] ?? 'Main Store Bay-01');
            $remarks = trim($_POST['remarks'] ?? '');
            $itemsJson = $_POST['items'] ?? '[]';
            $items = is_string($itemsJson) ? json_decode($itemsJson, true) : $itemsJson;

            if (empty($items)) {
                Response::error('Please add at least one item to the document.');
            }

            // Strict Three-Tier Stock Allocation & Reservation Verification for Material Issues
            if ($docType === 'ISSUE') {
                foreach ($items as $chkItem) {
                    $itemSku = trim($chkItem['sku'] ?? '');
                    $reqQty = (int)($chkItem['quantity'] ?? 1);

                    // 1. Total physical on-hand
                    $sPhys = $db->prepare("
                        SELECT COALESCE(SUM(remaining_quantity), 0) FROM fifo_batches 
                        WHERE sku = :sku AND location_id = :loc AND status = 'active'
                    ");
                    $sPhys->execute([':sku' => $itemSku, ':loc' => $locationId]);
                    $phys = (int)$sPhys->fetchColumn();

                    // 2. Currently committed / reserved in pending unposted issues
                    $sAlloc = $db->prepare("
                        SELECT COALESCE(SUM(udi.quantity), 0) FROM unposted_document_items udi
                        JOIN unposted_documents ud ON udi.document_id = ud.id
                        WHERE udi.sku = :sku 
                          AND ud.location_id = :loc 
                          AND ud.doc_type = 'ISSUE' 
                          AND ud.status IN ('Draft', 'Validated', 'Approved')
                    ");
                    $sAlloc->execute([':sku' => $itemSku, ':loc' => $locationId]);
                    $alloc = (int)$sAlloc->fetchColumn();

                    $available = max(0, $phys - $alloc);

                    if ($reqQty > $available) {
                        Response::error(
                            "Stock Reservation Conflict: Cannot request {$reqQty} units for SKU '{$itemSku}'. Only {$available} units are free for issue ({$phys} physical on-hand, {$alloc} units reserved under pending approvals)."
                        );
                    }
                }
            }

            $prefix = ($docType === 'GRN') ? 'GRN' : 'ISS';
            $docNo = $prefix . '-' . date('Y') . '-' . str_pad((string)rand(100, 9999), 4, '0', STR_PAD_LEFT);

            $db->beginTransaction();
            $insDoc = $db->prepare("
                INSERT INTO unposted_documents
                (location_id, doc_type, doc_no, doc_date, receipt_from, department_code, destination_code, warehouse, status, invoice_total, remarks, created_by)
                VALUES
                (:loc, :dtype, :dno, CURDATE(), :from, :dept, :dest, :wh, 'Draft', 0, :rem, :uid)
            ");
            $insDoc->execute([
                ':loc' => $locationId,
                ':dtype' => $docType,
                ':dno' => $docNo,
                ':from' => $receiptFrom,
                ':dept' => $deptCode,
                ':dest' => $destCode,
                ':wh' => $warehouse,
                ':rem' => $remarks,
                ':uid' => Auth::id() ?? 1
            ]);
            $documentId = (int)$db->lastInsertId();

            $totalInvoice = 0.0;
            $insItem = $db->prepare("
                INSERT INTO unposted_document_items
                (document_id, sku, item_name, requested_quantity, quantity, unit_cost, total_cost, bin_location)
                VALUES
                (:doc_id, :sku, :name, :req_qty, :qty, :cost, :total, :bin)
            ");

            foreach ($items as $item) {
                $sku = trim($item['sku'] ?? '');
                $name = trim($item['item_name'] ?? '');
                $qty = (int)($item['quantity'] ?? 1);
                $cost = (float)($item['unit_cost'] ?? 0);
                $bin = trim($item['bin_location'] ?? 'Bay A');
                $subTotal = $qty * $cost;
                $totalInvoice += $subTotal;

                $insItem->execute([
                    ':doc_id' => $documentId,
                    ':sku' => $sku,
                    ':name' => $name,
                    ':req_qty' => $qty,
                    ':qty' => $qty,
                    ':cost' => $cost,
                    ':total' => $subTotal,
                    ':bin' => $bin
                ]);
            }

            // Update total
            $db->prepare("UPDATE unposted_documents SET invoice_total = :tot WHERE id = :id")
               ->execute([':tot' => $totalInvoice, ':id' => $documentId]);

            $db->commit();
            Response::success('Document created successfully', ['document_id' => $documentId, 'doc_no' => $docNo]);
            break;

        // -------------------------------------------------------------
        // Document Status Transition (Validate, Approve, Cancel)
        // -------------------------------------------------------------
        case 'update_status':
            $docId = (int)($_POST['id'] ?? 0);
            $newStatus = trim($_POST['status'] ?? ''); // Validated, Approved, Cancelled

            $allowed = ['Draft', 'Validated', 'Approved', 'Cancelled'];
            if (!in_array($newStatus, $allowed)) {
                Response::error('Invalid document status: ' . $newStatus);
            }

            $stmt = $db->prepare("UPDATE unposted_documents SET status = :st WHERE id = :id AND location_id = :loc");
            $stmt->execute([':st' => $newStatus, ':id' => $docId, ':loc' => $locationId]);

            Response::success("Document status updated to {$newStatus}");
            break;

        // -------------------------------------------------------------
        // Update / Edit Unposted Document Details
        // -------------------------------------------------------------
        case 'update_document':
            $docId = (int)($_POST['id'] ?? 0);
            $stmt = $db->prepare("SELECT * FROM unposted_documents WHERE id = :id AND location_id = :loc");
            $stmt->execute([':id' => $docId, ':loc' => $locationId]);
            $doc = $stmt->fetch();

            if (!$doc) {
                Response::error('Document not found');
            }
            if ($doc['status'] === 'Posted') {
                Response::error('Posted documents cannot be edited. Use Posted Cancellation instead.');
            }

            $deptCode = trim($_POST['department_code'] ?? $doc['department_code']);
            $destCode = trim($_POST['destination_code'] ?? $doc['destination_code']);
            $receiptFrom = trim($_POST['receipt_from'] ?? $doc['receipt_from']);
            $remarks = trim($_POST['remarks'] ?? $doc['remarks']);
            $qty = isset($_POST['quantity']) ? (int)$_POST['quantity'] : null;

            $db->beginTransaction();
            $upd = $db->prepare("
                UPDATE unposted_documents 
                SET department_code = :dept, destination_code = :dest, receipt_from = :from, remarks = :rem
                WHERE id = :id
            ");
            $upd->execute([
                ':dept' => $deptCode,
                ':dest' => $destCode,
                ':from' => $receiptFrom,
                ':rem' => $remarks,
                ':id' => $docId
            ]);

            // Update item quantity if specified
            if ($qty !== null && $qty > 0) {
                $updItem = $db->prepare("
                    UPDATE unposted_document_items 
                    SET quantity = :qty, requested_quantity = :qty, total_cost = (:qty * unit_cost)
                    WHERE document_id = :docId LIMIT 1
                ");
                $updItem->execute([':qty' => $qty, ':docId' => $docId]);

                // Recalculate document total
                $recalc = $db->prepare("
                    UPDATE unposted_documents 
                    SET invoice_total = (SELECT IFNULL(SUM(total_cost), 0) FROM unposted_document_items WHERE document_id = :docId)
                    WHERE id = :docId
                ");
                $recalc->execute([':docId' => $docId]);
            }

            $db->commit();
            Response::success("Document {$doc['doc_no']} updated successfully.");
            break;

        // -------------------------------------------------------------
        // Post Document (Executes FIFO movements and updates stock ledger)
        // -------------------------------------------------------------
        case 'post_document':
            $docId = (int)($_POST['id'] ?? 0);
            $stmt = $db->prepare("SELECT * FROM unposted_documents WHERE id = :id AND location_id = :loc");
            $stmt->execute([':id' => $docId, ':loc' => $locationId]);
            $doc = $stmt->fetch();

            if (!$doc) {
                Response::error('Document not found');
            }
            if ($doc['status'] !== 'Approved') {
                Response::error('Document must be "Approved" before posting.');
            }

            $itemsStmt = $db->prepare("SELECT * FROM unposted_document_items WHERE document_id = :id");
            $itemsStmt->execute([':id' => $docId]);
            $items = $itemsStmt->fetchAll();

            if (empty($items)) {
                Response::error('Document has no line items');
            }

            if ($doc['doc_type'] === 'GRN') {
                foreach ($items as $item) {
                    FifoEngine::receiveStock(
                        $locationId,
                        $item['sku'],
                        $item['item_name'],
                        'General Spares',
                        (int)$item['quantity'],
                        (float)$item['unit_cost'],
                        $doc['doc_no'],
                        $item['bin_location'] ?? 'Bay A',
                        $item['expiry_date'] ?? null,
                        Auth::id() ?? 1
                    );
                }
            } else if ($doc['doc_type'] === 'ISSUE') {
                foreach ($items as $item) {
                    FifoEngine::issueStock(
                        $locationId,
                        $item['sku'],
                        (int)$item['quantity'],
                        $doc['doc_no'],
                        $doc['destination_code'],
                        $doc['department_code'],
                        Auth::id() ?? 1
                    );
                }
            }

            // Mark document posted
            $upd = $db->prepare("UPDATE unposted_documents SET status = 'Posted', posted_by = :uid WHERE id = :id");
            $upd->execute([':uid' => Auth::id() ?? 1, ':id' => $docId]);

            Response::success("Document {$doc['doc_no']} successfully POSTED to FIFO Stock Ledger!");
            break;

        // -------------------------------------------------------------
        // Posted Cancellation (Reverse Transaction)
        // -------------------------------------------------------------
        case 'cancel_posted':
            $docNo = trim($_POST['doc_no'] ?? '');
            if (empty($docNo)) {
                Response::error('Document number required');
            }

            $stmt = $db->prepare("SELECT * FROM unposted_documents WHERE doc_no = :doc AND location_id = :loc");
            $stmt->execute([':doc' => $docNo, ':loc' => $locationId]);
            $doc = $stmt->fetch();

            if (!$doc || $doc['status'] !== 'Posted') {
                Response::error('Only POSTED documents can be reversed with Posted Cancellation.');
            }

            // Update status to Cancelled
            $db->prepare("UPDATE unposted_documents SET status = 'Cancelled' WHERE id = :id")->execute([':id' => $doc['id']]);

            Response::success("Posted document {$docNo} has been CANCELLED and reversal noted.");
            break;

        default:
            Response::error('Invalid inventory action: ' . $action);
    }
} catch (Exception $e) {
    Response::error($e->getMessage());
}

