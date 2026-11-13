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
        // Lookups for Receipts Entry Form (Sections, Cost Centers, Suppliers, Warehouses)
        // -------------------------------------------------------------
        case 'get_receipt_lookups':
        case 'get_issue_lookups':
            $central = Database::getConnection();
            
            // Sections from issue_destination_codes
            $sections = [];
            try {
                $stSec = $central->query("SELECT code, title, display_label FROM issue_destination_codes WHERE status = 'active' ORDER BY sort_order ASC, code ASC");
                $sections = $stSec->fetchAll();
            } catch (\Throwable $e) {}

            // Cost Centers
            $costCenters = [
                ['code' => '831.00', 'name' => '831.00 - Victoria Operations & Capital'],
                ['code' => '831.10', 'name' => '831.10 - Mechanical Maintenance - Victoria PS'],
                ['code' => '831.20', 'name' => '831.20 - Electrical Maintenance - Victoria PS'],
                ['code' => '831.30', 'name' => '831.30 - Civil Engineering & Dam Safety - Victoria'],
                ['code' => '831.40', 'name' => '831.40 - Instrumentation & Control System - Victoria'],
                ['code' => '831.50', 'name' => '831.50 - Power House Auxiliary Stores - Victoria'],
                ['code' => '831.60', 'name' => '831.60 - Chemical & Lubricants Division - Victoria']
            ];

            // Projects
            $projects = [
                'PRJ-831-01 - Turbine Runner Replacement Project - Victoria PS',
                'PRJ-831-02 - Spillway Radial Gate Automation Project - Victoria',
                'PRJ-831-03 - 220kV Switchyard Transformer Refurbishment - Victoria',
                'PRJ-831-04 - Penstock Ultrasonic Thickness Survey & Recoating',
                'PRJ-831-05 - Power House Cooling Water System Modernization',
                'PRJ-GEN-CAP - Generation Division Capital Project'
            ];

            // My Projects
            $myProjects = [
                'MY-PRJ-01 - Unit 1 Annual Scheduled Overhaul',
                'MY-PRJ-02 - Excitation System Diagnostic Upgrade',
                'MY-PRJ-03 - Auxiliary Cooling Pump Overhaul',
                'MY-PRJ-04 - Stator Core Re-wedging & Winding Inspection'
            ];

            // Issuing Types for "Genaration as Expenditure"
            $issuingTypes = [
                'Routine Plant Maintenance',
                'Major Scheduled Overhaul',
                'Emergency Breakdown Repair',
                'Plant Operations & Shift Running',
                'General Store Consumption',
                'Station Civil & Dam Maintenance',
                'Capital Asset Maintenance'
            ];

            // Cost Codes
            $costCodes = [
                ['code' => '831.00', 'label' => '831.00 - Victoria Operations & Capital'],
                ['code' => 'A1001', 'label' => 'A1001 - Land & Civil Works'],
                ['code' => 'A1003', 'label' => 'A1003 - Buildings & Structures'],
                ['code' => 'A1113', 'label' => 'A1113 - Dams, Tunnels & Spillways'],
                ['code' => 'A1123', 'label' => 'A1123 - Turbine & Generator Sets'],
                ['code' => 'A1203', 'label' => 'A1203 - Switchyard & Transformers'],
                ['code' => 'M201', 'label' => 'M201 - Mechanical Spare Parts'],
                ['code' => 'E301', 'label' => 'E301 - Electrical & Electronic Spares'],
                ['code' => 'C401', 'label' => 'C401 - Lubricants, Greases & Consumables']
            ];

            // Approved Suppliers
            $suppliers = [
                'Lanka Lubricants Ltd',
                'Colombo Electrical Supplies Ltd',
                'Industrial Spares (Pvt) Ltd',
                'Diesel & Motor Engineering (DIMO)',
                'United Motors Lanka PLC',
                'Hayleys Power & Energy',
                'Ceylon Petroleum Corporation (CPC)',
                'State Engineering Corporation',
                'Walker Sons & Co Ltd',
                'Kelani Cables PLC',
                'Brown & Company PLC',
                'Tokyo Cement Company',
                'Avent Technologies (Pvt) Ltd'
            ];

            // Warehouses from Admin Panel (Operational Departments & Warehouses)
            $warehouses = [];
            try {
                $stWh = $central->query("SELECT code, name FROM departments WHERE status = 'active' ORDER BY name ASC");
                $depts = $stWh->fetchAll();
                foreach ($depts as $d) {
                    $code = trim((string)($d['code'] ?? ''));
                    $name = trim((string)($d['name'] ?? ''));
                    if ($code !== '' && $name !== '') {
                        $warehouses[] = $code . ' -- ' . $name;
                    } elseif ($name !== '') {
                        $warehouses[] = $name;
                    } elseif ($code !== '') {
                        $warehouses[] = $code;
                    }
                }
            } catch (\Throwable $e) {}

            // Common materials
            $materials = [];
            try {
                $stMat = $central->query("SELECT formatted_code as sku, item_name, uom, unit_price FROM fifo_master_items WHERE status = 'ACTIVE' LIMIT 500");
                $materials = $stMat->fetchAll();
            } catch (\Throwable $e) {}

            Response::success('Lookups loaded', [
                'sections' => $sections,
                'cost_centers' => $costCenters,
                'projects' => $projects,
                'my_projects' => $myProjects,
                'issuing_types' => $issuingTypes,
                'cost_codes' => $costCodes,
                'suppliers' => $suppliers,
                'warehouses' => $warehouses,
                'materials' => $materials
            ]);
            break;

        case 'search_materials':
            $q = trim($_GET['q'] ?? '');
            $central = Database::getConnection();
            $materials = [];
            if (!empty($q)) {
                $cleanQ = preg_replace('/[^a-zA-Z0-9]/', '', $q);
                $strippedQ = ltrim($cleanQ, '0');

                $whereParts = [
                    'formatted_code LIKE :rawQ',
                    'item_name LIKE :nameQ'
                ];
                $params = [
                    ':rawQ' => '%' . $q . '%',
                    ':nameQ' => '%' . $q . '%'
                ];

                if (!empty($cleanQ)) {
                    $whereParts[] = "REPLACE(REPLACE(REPLACE(formatted_code, '-', ''), ' ', ''), '.', '') LIKE :cleanQ";
                    $params[':cleanQ'] = '%' . $cleanQ . '%';
                }

                if (!empty($strippedQ)) {
                    $whereParts[] = "TRIM(LEADING '0' FROM REPLACE(REPLACE(REPLACE(formatted_code, '-', ''), ' ', ''), '.', '')) LIKE :strippedQ";
                    $params[':strippedQ'] = '%' . $strippedQ . '%';
                }

                $sql = "SELECT formatted_code as sku, item_name, uom, unit_price 
                        FROM fifo_master_items 
                        WHERE status = 'ACTIVE' AND (" . implode(' OR ', $whereParts) . ") 
                        LIMIT 100";

                $stmt = $central->prepare($sql);
                $stmt->execute($params);
                $materials = $stmt->fetchAll();
            } else {
                $materials = $central->query("SELECT formatted_code as sku, item_name, uom, unit_price FROM fifo_master_items WHERE status = 'ACTIVE' LIMIT 100")->fetchAll();
            }
            Response::success('Materials found', ['materials' => $materials]);
            break;

        case 'get_all_master_codes':
            $central = Database::getConnection();
            $sql = "SELECT id, formatted_code as sku, raw_code, item_name, specifications, uom, unit_price, status, created_at,
                           CASE tier1 
                             WHEN 1 THEN 'Mechanical'
                             WHEN 2 THEN 'Electrical'
                             WHEN 3 THEN 'Civil'
                             WHEN 4 THEN 'Consumable'
                             WHEN 5 THEN 'Lubricant'
                             WHEN 6 THEN 'Instrumentation'
                             ELSE 'General'
                           END as category
                    FROM fifo_master_items 
                    WHERE status = 'ACTIVE'
                    ORDER BY id DESC";
            $stmt = $central->query($sql);
            $items = $stmt->fetchAll();
            Response::success('Master codes loaded', ['codes' => $items]);
            break;

        // -------------------------------------------------------------
        // Request & Add New Material Item Code
        // -------------------------------------------------------------
        case 'request_add_material_code':
            $itemName = trim($_POST['item_name'] ?? '');
            if (empty($itemName)) {
                Response::error('Material Item Name is required.');
            }

            $userCode = trim($_POST['code'] ?? '');
            $uom = trim($_POST['uom'] ?? 'Nos');
            $unitPrice = (float)($_POST['unit_price'] ?? 0);
            $category = trim($_POST['category'] ?? 'Mechanical');
            $specifications = trim($_POST['specifications'] ?? '');

            $central = Database::getConnection();

            if (!empty($userCode)) {
                $formattedCode = $userCode;
                $rawCode = preg_replace('/[^A-Za-z0-9]/', '', $userCode);
                $tier1 = 1;
                $tier2 = 1;
                $tier3 = 1;
                $tier4Seq = 1;
            } else {
                $tier1 = match($category) {
                    'Electrical' => 2,
                    'Civil' => 3,
                    'Consumable' => 4,
                    'Lubricant' => 5,
                    'Instrumentation' => 6,
                    default => 1 // Mechanical
                };
                $tier2 = 1;
                $tier3 = 1;
                
                $maxSeqRow = $central->query("SELECT MAX(tier4_seq) as max_seq, MAX(id) as max_id FROM fifo_master_items WHERE tier1 = {$tier1}")->fetch();
                $tier4Seq = ((int)($maxSeqRow['max_seq'] ?? 0)) + 1;
                $formattedCode = sprintf("%d-%02d-%02d-%03d", $tier1, $tier2, $tier3, $tier4Seq);
                $rawCode = sprintf("%02d%02d%02d%03d", $tier1, $tier2, $tier3, $tier4Seq);
            }

            // Check if code collision
            $chk = $central->prepare("SELECT id FROM fifo_master_items WHERE formatted_code = :c OR raw_code = :r LIMIT 1");
            $chk->execute([':c' => $formattedCode, ':r' => $rawCode]);
            if ($chk->fetch()) {
                $suffix = substr((string)time(), -4);
                $formattedCode .= '-' . $suffix;
                $rawCode .= $suffix;
            }

            $stmt = $central->prepare("
                INSERT INTO fifo_master_items 
                (tier1, tier2, tier3, tier4_seq, formatted_code, raw_code, item_name, specifications, uom, unit_price, status, created_by, origin_station_id)
                VALUES 
                (:t1, :t2, :t3, :t4, :fcode, :rcode, :name, :spec, :uom, :price, 'ACTIVE', :uid, :station)
            ");
            $stmt->execute([
                ':t1' => $tier1,
                ':t2' => $tier2,
                ':t3' => $tier3,
                ':t4' => $tier4Seq,
                ':fcode' => $formattedCode,
                ':rcode' => $rawCode,
                ':name' => $itemName,
                ':spec' => $specifications,
                ':uom' => $uom ?: 'Nos',
                ':price' => $unitPrice > 0 ? $unitPrice : null,
                ':uid' => Auth::id() ?? 1,
                ':station' => $locationId
            ]);
            $newId = (int)$central->lastInsertId();

            Response::success('New material item code created successfully', [
                'item' => [
                    'id' => $newId,
                    'sku' => $formattedCode,
                    'item_name' => $itemName,
                    'uom' => $uom ?: 'Nos',
                    'unit_price' => $unitPrice
                ]
            ]);
            break;

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
                          AND ud.status IN ('Draft', 'Pending Validation', 'Validated', 'Approved')
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
                          AND ud.status IN ('Draft', 'Pending Validation', 'Validated', 'Approved')
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

            $docProfile = trim($_POST['doc_profile'] ?? 'Goods Received Note');
            $docProfileCode = trim($_POST['doc_profile_code'] ?? 'PF-GRN');
            $sourceDept = trim($_POST['source_dept'] ?? '831.00');
            $receiptDetails = trim($_POST['receipt_details'] ?? '');
            $deliveryDate = !empty($_POST['delivery_date']) ? date('Y-m-d', strtotime($_POST['delivery_date'])) : date('Y-m-d');
            $accDate = !empty($_POST['acc_date']) ? date('Y-m-d', strtotime($_POST['acc_date'])) : date('Y-m-d');
            $dueDate = !empty($_POST['due_date']) ? date('Y-m-d', strtotime($_POST['due_date'])) : null;
            $baseOn = trim($_POST['base_on'] ?? 'PO');
            $nbt = (float)($_POST['nbt'] ?? 0);
            $nbtExcluded = !empty($_POST['nbt_excluded']) ? 1 : 0;
            $vat = (float)($_POST['vat'] ?? 0);
            $vatExcluded = !empty($_POST['vat_excluded']) ? 1 : 0;
            $transport = (float)($_POST['transport'] ?? 0);
            $transportExcluded = !empty($_POST['transport_excluded']) ? 1 : 0;
            $otherCost = (float)($_POST['other_cost'] ?? 0);
            $otherExcluded = !empty($_POST['other_excluded']) ? 1 : 0;
            $invoiceNumber = trim($_POST['invoice_number'] ?? '');
            $orderReference = trim($_POST['order_reference'] ?? '');
            $otherRef1 = trim($_POST['other_ref_1'] ?? '');
            $otherRef2 = trim($_POST['other_ref_2'] ?? '');
            $otherRef3 = trim($_POST['other_ref_3'] ?? '');
            $otherRef4 = trim($_POST['other_ref_4'] ?? '');
            $invTotalManual = (float)($_POST['invoice_total'] ?? 0);
            $isSingleMaterial = !empty($_POST['is_single_material']) ? 1 : 0;
            $discountForAll = (float)($_POST['discount_for_all'] ?? 0);
            $isPercentage = !empty($_POST['is_percentage']) ? 1 : 0;
            $isDraft = (!empty($_POST['is_draft']) && (string)$_POST['is_draft'] === '1');
            $initialStatus = $isDraft ? 'Draft' : 'Pending Validation';

            $db->beginTransaction();
            $insDoc = $db->prepare("
                INSERT INTO unposted_documents
                (location_id, doc_type, doc_no, doc_date, doc_profile, doc_profile_code, source_dept, receipt_from, receipt_details, delivery_date, acc_date, due_date, department_code, destination_code, warehouse, base_on, status, invoice_total, vat, vat_excluded, transport, transport_excluded, nbt, nbt_excluded, other_cost, other_excluded, invoice_number, order_reference, other_ref_1, other_ref_2, other_ref_3, other_ref_4, is_single_material, discount_for_all, is_percentage, remarks, created_by)
                VALUES
                (:loc, :dtype, :dno, :acc_date, :doc_prof, :doc_prof_code, :s_dept, :from, :rec_det, :deliv_date, :acc_date2, :due_date, :dept, :dest, :wh, :base_on, :init_status, :inv_tot, :vat, :vat_ex, :trans, :trans_ex, :nbt, :nbt_ex, :other, :other_ex, :inv_no, :order_ref, :ref1, :ref2, :ref3, :ref4, :single_mat, :discount, :is_pct, :rem, :uid)
            ");
            $insDoc->execute([
                ':loc' => $locationId,
                ':dtype' => $docType,
                ':dno' => $docNo,
                ':acc_date' => $accDate,
                ':doc_prof' => $docProfile,
                ':doc_prof_code' => $docProfileCode,
                ':s_dept' => $sourceDept,
                ':from' => $receiptFrom,
                ':rec_det' => $receiptDetails,
                ':deliv_date' => $deliveryDate,
                ':acc_date2' => $accDate,
                ':due_date' => $dueDate,
                ':dept' => $deptCode,
                ':dest' => $destCode,
                ':wh' => $warehouse,
                ':base_on' => $baseOn,
                ':init_status' => $initialStatus,
                ':inv_tot' => $invTotalManual,
                ':vat' => $vat,
                ':vat_ex' => $vatExcluded,
                ':trans' => $transport,
                ':trans_ex' => $transportExcluded,
                ':nbt' => $nbt,
                ':nbt_ex' => $nbtExcluded,
                ':other' => $otherCost,
                ':other_ex' => $otherExcluded,
                ':inv_no' => $invoiceNumber,
                ':order_ref' => $orderReference,
                ':ref1' => $otherRef1,
                ':ref2' => $otherRef2,
                ':ref3' => $otherRef3,
                ':ref4' => $otherRef4,
                ':single_mat' => $isSingleMaterial,
                ':discount' => $discountForAll,
                ':is_pct' => $isPercentage,
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

            $allowed = ['Draft', 'Pending Validation', 'Validated', 'Approved', 'Cancelled'];
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

            $db->beginTransaction();

            // If full items array is provided (from in-body Stock Entry Form)
            if (isset($_POST['items'])) {
                $warehouse = trim($_POST['warehouse'] ?? $doc['warehouse']);
                $receiptFrom = trim($_POST['receipt_from'] ?? $doc['receipt_from']);
                $orderReference = trim($_POST['order_reference'] ?? $doc['order_reference']);
                $invoiceNumber = trim($_POST['invoice_number'] ?? $doc['invoice_number']);
                $otherRef1 = trim($_POST['other_ref_1'] ?? $doc['other_ref_1']);
                $remarks = trim($_POST['remarks'] ?? $doc['remarks']);
                $deliveryDate = !empty($_POST['delivery_date']) ? $_POST['delivery_date'] : $doc['delivery_date'];
                $accDate = !empty($_POST['acc_date']) ? $_POST['acc_date'] : $doc['acc_date'];
                $invTotalManual = (float)($_POST['invoice_total'] ?? 0);

                $isDraft = (!empty($_POST['is_draft']) && (string)$_POST['is_draft'] === '1');
                $newStatus = $isDraft ? 'Draft' : 'Pending Validation';

                $updDoc = $db->prepare("
                    UPDATE unposted_documents
                    SET warehouse = :wh,
                        receipt_from = :from,
                        order_reference = :order_ref,
                        invoice_number = :inv_no,
                        other_ref_1 = :ref1,
                        remarks = :rem,
                        delivery_date = :deliv_date,
                        acc_date = :acc_date,
                        status = :status,
                        invoice_total = :inv_tot
                    WHERE id = :id
                ");
                $updDoc->execute([
                    ':wh' => $warehouse,
                    ':from' => $receiptFrom,
                    ':order_ref' => $orderReference,
                    ':inv_no' => $invoiceNumber,
                    ':ref1' => $otherRef1,
                    ':rem' => $remarks,
                    ':deliv_date' => $deliveryDate,
                    ':acc_date' => $accDate,
                    ':status' => $newStatus,
                    ':inv_tot' => $invTotalManual,
                    ':id' => $docId
                ]);

                // Replace line items
                $delItems = $db->prepare("DELETE FROM unposted_document_items WHERE document_id = :id");
                $delItems->execute([':id' => $docId]);

                $items = json_decode($_POST['items'] ?? '[]', true) ?: [];
                $insItem = $db->prepare("
                    INSERT INTO unposted_document_items
                    (document_id, sku, item_name, requested_quantity, quantity, unit_cost, total_cost, bin_location)
                    VALUES
                    (:doc_id, :sku, :name, :req_qty, :qty, :cost, :total, :bin)
                ");

                $totalInvoice = 0.0;
                foreach ($items as $item) {
                    $sku = trim($item['sku'] ?? '');
                    $name = trim($item['item_name'] ?? '');
                    $qty = (float)($item['quantity'] ?? 1);
                    $cost = (float)($item['unit_cost'] ?? 0);
                    $bin = trim($item['bin_location'] ?? 'Bay A');
                    $subTotal = $qty * $cost;
                    $totalInvoice += $subTotal;

                    $insItem->execute([
                        ':doc_id' => $docId,
                        ':sku' => $sku,
                        ':name' => $name,
                        ':req_qty' => $qty,
                        ':qty' => $qty,
                        ':cost' => $cost,
                        ':total' => $subTotal,
                        ':bin' => $bin
                    ]);
                }

                if ($invTotalManual <= 0) {
                    $updTot = $db->prepare("UPDATE unposted_documents SET invoice_total = :tot WHERE id = :id");
                    $updTot->execute([':tot' => $totalInvoice, ':id' => $docId]);
                }

                $db->commit();
                Response::success("Draft {$doc['doc_no']} updated successfully.", ['doc_no' => $doc['doc_no'], 'status' => $newStatus]);
                break;
            }

            $deptCode = trim($_POST['department_code'] ?? $doc['department_code']);
            $destCode = trim($_POST['destination_code'] ?? $doc['destination_code']);
            $receiptFrom = trim($_POST['receipt_from'] ?? $doc['receipt_from']);
            $remarks = trim($_POST['remarks'] ?? $doc['remarks']);
            $qty = isset($_POST['quantity']) ? (int)$_POST['quantity'] : null;

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

