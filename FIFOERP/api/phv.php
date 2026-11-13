<?php
require_once dirname(__DIR__) . '/app/config/config.php';

use App\Core\Auth;
use App\Core\Response;
use App\Core\FifoEngine;
use App\Config\Database;

header('Content-Type: application/json; charset=utf-8');

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$locationId = (int)($_SESSION['location_id'] ?? 1);

if (in_array($action, ['download_sheets_zip', 'download_sheet_pdf'])) {
    // Binary responses: do not emit application/json
    header_remove('Content-Type');
}

try {
    $db = Database::getStationConnection($locationId);

    switch ($action) {
        // -------------------------------------------------------------
        // List PHV Sessions
        // -------------------------------------------------------------
        case 'get_sessions':
            $stmt = $db->prepare("
                SELECT s.*, 
                (SELECT COUNT(*) FROM phv_entries WHERE session_id = s.id) as total_items,
                (SELECT SUM(variance_value) FROM phv_entries WHERE session_id = s.id) as net_variance_value
                FROM phv_sessions s
                WHERE s.location_id = :loc
                ORDER BY s.id DESC
            ");
            $stmt->execute([':loc' => $locationId]);
            $sessions = $stmt->fetchAll();
            Response::success('PHV sessions loaded', ['sessions' => $sessions]);
            break;

        // -------------------------------------------------------------
        // Create New Session
        // -------------------------------------------------------------
        case 'create_session':
            $title = trim($_POST['session_title'] ?? 'Store Physical Verification');
            $date = $_POST['verification_date'] ?? date('Y-m-d');
            $remarks = trim($_POST['remarks'] ?? '');
            $code = 'PHV-' . date('Y') . '-S' . rand(10, 99);

            $stmt = $db->prepare("
                INSERT INTO phv_sessions (location_id, session_code, session_title, verification_date, status, remarks, created_by)
                VALUES (:loc, :code, :title, :vdate, 'Draft', :rem, :uid)
            ");
            $stmt->execute([
                ':loc' => $locationId,
                ':code' => $code,
                ':title' => $title,
                ':vdate' => $date,
                ':rem' => $remarks,
                ':uid' => Auth::id() ?? 1
            ]);
            $sessionId = (int)$db->lastInsertId();

            Response::success('PHV Session created successfully', ['session_id' => $sessionId, 'session_code' => $code]);
            break;

        // -------------------------------------------------------------
        // Freeze / Unfreeze Batches
        // -------------------------------------------------------------
        case 'toggle_freeze':
            $sessionId = (int)($_POST['session_id'] ?? 0);
            $freeze = (bool)($_POST['freeze'] ?? false);

            if ($sessionId <= 0) {
                Response::error('Invalid session ID');
            }

            FifoEngine::setBatchFreeze($locationId, $freeze, $sessionId, Auth::id() ?? 1);

            $msg = $freeze 
                ? "All active inventory batches have been FROZEN for session #{$sessionId}. No stock issues or receipts allowed until unfreezing."
                : "Inventory batches have been UNFROZEN. Normal stock transactions resumed.";

            Response::success($msg, ['is_frozen' => $freeze]);
            break;

        // -------------------------------------------------------------
        // Generate Count Sheet (Loads all active batches into session)
        // -------------------------------------------------------------
        case 'generate_sheet':
            $sessionId = (int)($_POST['session_id'] ?? 1);
            if ($sessionId <= 0) $sessionId = 1;

            // Fetch current active batches (ordered by SKU ASC)
            $stmt = $db->prepare("
                SELECT * FROM fifo_batches 
                WHERE location_id = :loc AND remaining_quantity > 0
                ORDER BY location_bin ASC, sku ASC
            ");
            $stmt->execute([':loc' => $locationId]);
            $batches = $stmt->fetchAll();

            $db->beginTransaction();
            // Clear existing entries for this session
            $db->prepare("DELETE FROM phv_entries WHERE session_id = :sid")->execute([':sid' => $sessionId]);

            $ins = $db->prepare("
                INSERT INTO phv_entries 
                (session_id, batch_id, sku, item_name, bin_location, book_quantity, physical_quantity, unit_cost, remarks)
                VALUES
                (:sid, :bid, :sku, :name, :bin, :bqty, :pqty, :cost, 'Auto-generated count sheet')
            ");

            foreach ($batches as $b) {
                $ins->execute([
                    ':sid' => $sessionId,
                    ':bid' => $b['id'],
                    ':sku' => $b['sku'],
                    ':name' => $b['item_name'],
                    ':bin' => $b['location_bin'],
                    ':bqty' => $b['remaining_quantity'],
                    ':pqty' => $b['remaining_quantity'], // default matching
                    ':cost' => $b['unit_cost']
                ]);
            }

            $db->prepare("UPDATE phv_sessions SET status = 'In_Counting' WHERE id = :sid")->execute([':sid' => $sessionId]);
            $db->commit();

            $totalItems = count($batches);
            $totalSheets = (int)ceil($totalItems / 21);

            Response::success("Count sheets generated: {$totalItems} items divided into {$totalSheets} sheets (21 items/sheet)", [
                'count' => $totalItems,
                'items_per_sheet' => 21,
                'total_sheets' => $totalSheets
            ]);
            break;

        // -------------------------------------------------------------
        // Get Entries for a Session (with 21 items/sheet pagination)
        // -------------------------------------------------------------
        case 'get_entries':
            $sessionId = (int)($_GET['session_id'] ?? 1);
            $sheetPage = isset($_GET['sheet']) ? (int)$_GET['sheet'] : 0;
            $perSheet = 21;

            $stmt = $db->prepare("SELECT * FROM phv_entries WHERE session_id = :sid ORDER BY bin_location ASC, sku ASC");
            $stmt->execute([':sid' => $sessionId]);
            $allEntries = $stmt->fetchAll();

            if (empty($allEntries)) {
                // If not yet generated into phv_entries, fall back to live active batches
                $bStmt = $db->prepare("
                    SELECT id as id, id as batch_id, sku, item_name, location_bin as bin_location, 
                           remaining_quantity as book_quantity, remaining_quantity as physical_quantity,
                           unit_cost, 'Active Inventory' as remarks
                    FROM fifo_batches 
                    WHERE location_id = :loc AND remaining_quantity > 0 
                    ORDER BY location_bin ASC, sku ASC
                ");
                $bStmt->execute([':loc' => $locationId]);
                $allEntries = $bStmt->fetchAll();
            }

            $totalItems = count($allEntries);
            $totalSheets = max(1, (int)ceil($totalItems / $perSheet));

            if ($sheetPage > 0) {
                $offset = ($sheetPage - 1) * $perSheet;
                $entries = array_slice($allEntries, $offset, $perSheet);
            } else {
                $entries = $allEntries;
            }

            Response::success('PHV entries loaded', [
                'entries' => $entries,
                'total_items' => $totalItems,
                'items_per_sheet' => $perSheet,
                'total_sheets' => $totalSheets,
                'current_sheet' => $sheetPage > 0 ? $sheetPage : 1
            ]);
            break;

        // -------------------------------------------------------------
        // Save Physical Counts (Verification Entry / Edit)
        // -------------------------------------------------------------
        case 'save_counts':
            $sessionId = (int)($_POST['session_id'] ?? 0);
            $counts = json_decode($_POST['counts'] ?? '[]', true);

            if (empty($counts)) Response::error('No count records received');

            $db->beginTransaction();
            $upd = $db->prepare("
                UPDATE phv_entries 
                SET physical_quantity = :pqty, remarks = :rem, verified_by = :uid
                WHERE id = :id AND session_id = :sid
            ");

            foreach ($counts as $c) {
                $upd->execute([
                    ':pqty' => (int)($c['physical_quantity'] ?? 0),
                    ':rem' => trim($c['remarks'] ?? ''),
                    ':uid' => Auth::id() ?? 1,
                    ':id' => (int)$c['id'],
                    ':sid' => $sessionId
                ]);
            }
            $db->commit();

            Response::success('Physical counts updated successfully');
            break;

        // -------------------------------------------------------------
        // Validation / Approval
        // -------------------------------------------------------------
        case 'update_status':
            $sessionId = (int)($_POST['session_id'] ?? 0);
            $status = trim($_POST['status'] ?? ''); // Validated, Approved, Posted

            $db->prepare("UPDATE phv_sessions SET status = :st WHERE id = :sid AND location_id = :loc")
               ->execute([':st' => $status, ':sid' => $sessionId, ':loc' => $locationId]);

            Response::success("PHV Session status set to {$status}");
            break;

        // -------------------------------------------------------------
        // Verification GL Posting (Adjust Stock Balances)
        // -------------------------------------------------------------
        case 'post_verification':
            $sessionId = (int)($_POST['session_id'] ?? 0);
            $stmt = $db->prepare("SELECT * FROM phv_sessions WHERE id = :sid AND location_id = :loc");
            $stmt->execute([':sid' => $sessionId, ':loc' => $locationId]);
            $session = $stmt->fetch();

            if (!$session || $session['status'] !== 'Approved') {
                Response::error('Verification session must be "Approved" by Auditor before Posting.');
            }

            // Fetch discrepancies
            $entriesStmt = $db->prepare("SELECT * FROM phv_entries WHERE session_id = :sid AND variance_quantity != 0");
            $entriesStmt->execute([':sid' => $sessionId]);
            $discrepancies = $entriesStmt->fetchAll();

            $db->beginTransaction();
            foreach ($discrepancies as $d) {
                // Adjust batch remaining_quantity to match physical_quantity
                $updBatch = $db->prepare("
                    UPDATE fifo_batches 
                    SET remaining_quantity = :pqty,
                        status = IF(:pqty <= 0, 'exhausted', 'active')
                    WHERE id = :bid
                ");
                $updBatch->execute([':pqty' => $d['physical_quantity'], ':bid' => $d['batch_id']]);

                // Create stock ledger adjustment entry
                $insTx = $db->prepare("
                    INSERT INTO inventory_transactions
                    (location_id, batch_id, transaction_type, document_no, sku, item_name, quantity, unit_price, total_value, transaction_date, reference_no, created_by, status)
                    VALUES
                    (:loc, :bid, 'RECONCILIATION', :doc, :sku, :name, :qty, :cost, :val, CURDATE(), :ref, :uid, 'POSTED')
                ");
                $insTx->execute([
                    ':loc' => $locationId,
                    ':bid' => $d['batch_id'],
                    ':doc' => $session['session_code'],
                    ':sku' => $d['sku'],
                    ':name' => $d['item_name'],
                    ':qty' => $d['variance_quantity'],
                    ':cost' => $d['unit_cost'],
                    ':val' => $d['variance_value'],
                    ':ref' => 'PHV Reconciliation Adjustment',
                    ':uid' => Auth::id() ?? 1
                ]);
            }

            // Unfreeze and close session
            FifoEngine::setBatchFreeze($locationId, false, $sessionId, Auth::id() ?? 1);
            $db->prepare("UPDATE phv_sessions SET status = 'Posted' WHERE id = :sid")->execute([':sid' => $sessionId]);

            $db->commit();
            Response::success('Physical verification posted! Stock ledgers and GL reconciliations updated.');
            break;

        // -------------------------------------------------------------
        // AV 7 Summary Report (Stores Board of Survey Sri Lanka Format)
        // -------------------------------------------------------------
        case 'get_av7_report':
            $sessionId = (int)($_GET['session_id'] ?? 1);
            $stmt = $db->prepare("
                SELECT e.*, b.batch_number, b.category
                FROM phv_entries e
                JOIN fifo_batches b ON e.batch_id = b.id
                WHERE e.session_id = :sid
                ORDER BY e.id ASC
            ");
            $stmt->execute([':sid' => $sessionId]);
            $rows = $stmt->fetchAll();

            $surplusTotal = 0.0;
            $deficitTotal = 0.0;
            foreach ($rows as $r) {
                $val = (float)$r['variance_value'];
                if ($val > 0) $surplusTotal += $val;
                if ($val < 0) $deficitTotal += abs($val);
            }

            Response::success('AV 7 Report Data', [
                'items' => $rows,
                'total_surplus_value' => $surplusTotal,
                'total_deficit_value' => $deficitTotal,
                'net_variance_value' => $surplusTotal - $deficitTotal
            ]);
            break;

        // -------------------------------------------------------------
        // Average Consumption Report
        // -------------------------------------------------------------
        case 'get_avg_consumption':
            $costCenter = trim($_GET['cost_center'] ?? '');
            $warehouse = trim($_GET['warehouse'] ?? '');
            $fromDate = $_GET['from_date'] ?? date('Y-01-01');
            $toDate = $_GET['to_date'] ?? date('Y-m-d');

            // Query items with issue history
            $stmt = $db->prepare("
                SELECT b.sku, b.item_name, 'NOS' as unit_of_measure, b.category, b.unit_cost,
                       b.remaining_quantity as current_qoh,
                       COALESCE(SUM(t.quantity), 0) as total_issued_qty,
                       COALESCE(SUM(t.total_value), 0) as total_issued_val
                FROM fifo_batches b
                LEFT JOIN inventory_transactions t 
                  ON b.id = t.batch_id AND t.transaction_type = 'ISSUE' 
                  AND t.transaction_date BETWEEN :fdate AND :tdate
                WHERE b.location_id = :loc
                GROUP BY b.sku, b.item_name, b.category, b.unit_cost, b.remaining_quantity
                ORDER BY total_issued_qty DESC, b.item_name ASC
            ");
            $stmt->execute([':loc' => $locationId, ':fdate' => $fromDate, ':tdate' => $toDate]);
            $items = $stmt->fetchAll();

            // Calculate months in range
            $m1 = new DateTime($fromDate);
            $m2 = new DateTime($toDate);
            $diffMonths = max(1, $m1->diff($m2)->m + ($m1->diff($m2)->y * 12));

            $reportData = [];
            foreach ($items as $item) {
                $totalIssued = (float)$item['total_issued_qty'];
                $monthlyAvg = round($totalIssued / $diffMonths, 2);
                $safetyStock = round($monthlyAvg * 1.5, 2);
                $reorderPoint = round($monthlyAvg * 2.0, 2);
                $status = ($item['current_qoh'] < $reorderPoint) ? 'REORDER_NOW' : 'NORMAL';

                $reportData[] = array_merge($item, [
                    'months_calculated' => $diffMonths,
                    'monthly_avg_consumption' => $monthlyAvg,
                    'safety_stock' => $safetyStock,
                    'reorder_point' => $reorderPoint,
                    'stock_health' => $status
                ]);
            }

            Response::success('Average consumption calculated', [
                'items' => $reportData,
                'period_months' => $diffMonths,
                'cost_center' => $costCenter ?: 'All Departments',
                'warehouse' => $warehouse ?: 'Central Main Store'
            ]);
            break;

        // -------------------------------------------------------------
        // PHV Sheet Summary
        // -------------------------------------------------------------
        case 'get_sheet_summary':
            $year = trim($_GET['phv_year'] ?? date('Y'));
            $costCenter = trim($_GET['cost_center'] ?? '');
            $warehouse = trim($_GET['warehouse'] ?? '');

            $stmt = $db->prepare("
                SELECT s.session_code, s.session_title, s.verification_date, s.status,
                       COUNT(e.id) as total_sheets_counted,
                       SUM(e.book_quantity) as total_book_qty,
                       SUM(e.physical_quantity) as total_phys_qty,
                       SUM(e.variance_quantity) as total_variance_qty,
                       SUM(e.variance_value) as total_variance_value
                FROM phv_sessions s
                LEFT JOIN phv_entries e ON s.id = e.session_id
                WHERE s.location_id = :loc
                GROUP BY s.id
                ORDER BY s.id DESC
            ");
            $stmt->execute([':loc' => $locationId]);
            $summary = $stmt->fetchAll();

            Response::success('PHV sheet summary generated', [
                'summary' => $summary,
                'phv_year' => $year,
                'cost_center' => $costCenter ?: 'All Departments',
                'warehouse' => $warehouse ?: 'Central Main Store'
            ]);
            break;

        // -------------------------------------------------------------
        // GL Transfer Preview & Details
        // -------------------------------------------------------------
        case 'get_gl_preview':
            $warehouse = trim($_GET['warehouse'] ?? 'Central Main Store');
            
            // Get discrepancies from latest session
            $stmt = $db->prepare("
                SELECT e.*, b.batch_number
                FROM phv_entries e
                JOIN fifo_batches b ON e.batch_id = b.id
                WHERE e.variance_quantity != 0
                ORDER BY e.id ASC
            ");
            $stmt->execute();
            $variances = $stmt->fetchAll();

            $totalDeficitVal = 0.0;
            $totalSurplusVal = 0.0;
            foreach ($variances as $v) {
                $val = (float)$v['variance_value'];
                if ($val < 0) $totalDeficitVal += abs($val);
                else $totalSurplusVal += $val;
            }

            $journalEntries = [
                [
                    'account_code' => '1020-STK-ADJ',
                    'account_name' => 'Store Inventory Adjustment Account (Surplus)',
                    'debit' => 0.0,
                    'credit' => $totalSurplusVal,
                    'narration' => "Credit surplus value from physical stock verification - $warehouse"
                ],
                [
                    'account_code' => '5030-STK-LOSS',
                    'account_name' => 'Loss on Stock Deficit / Shortage Account',
                    'debit' => $totalDeficitVal,
                    'credit' => 0.0,
                    'narration' => "Debit physical count shortage write-off - $warehouse"
                ],
                [
                    'account_code' => '1010-INV-CTRL',
                    'account_name' => 'Inventory Control General Ledger Asset',
                    'debit' => $totalSurplusVal,
                    'credit' => $totalDeficitVal,
                    'narration' => "Net inventory balance adjustment for $warehouse"
                ]
            ];

            Response::success('GL preview generated', [
                'warehouse' => $warehouse,
                'journal_entries' => $journalEntries,
                'total_surplus' => $totalSurplusVal,
                'total_deficit' => $totalDeficitVal,
                'discrepancy_count' => count($variances)
            ]);
            break;

        // -------------------------------------------------------------
        // Download All Count Sheets as a ZIP file (21 items per PDF sheet)
        // -------------------------------------------------------------
        case 'download_sheets_zip':
            $sessionId = (int)($_GET['session_id'] ?? 1);
            $station = trim($_GET['station'] ?? 'Victoria Hydro Power Station');
            $costCenter = trim($_GET['cost_center'] ?? 'Mechanical');
            $warehouse = trim($_GET['warehouse'] ?? 'Central Main Store');

            $stmt = $db->prepare("
                SELECT e.* 
                FROM phv_entries e 
                WHERE e.session_id = :sid 
                ORDER BY e.bin_location ASC, e.sku ASC
            ");
            $stmt->execute([':sid' => $sessionId]);
            $items = $stmt->fetchAll();

            if (empty($items)) {
                $stmt = $db->prepare("
                    SELECT id as batch_id, sku, item_name, location_bin as bin_location, 
                           remaining_quantity as book_quantity, unit_cost 
                    FROM fifo_batches 
                    WHERE location_id = :loc AND remaining_quantity > 0 
                    ORDER BY location_bin ASC, sku ASC
                ");
                $stmt->execute([':loc' => $locationId]);
                $items = $stmt->fetchAll();
            }

            if (empty($items)) {
                die('No inventory items found to generate count sheets.');
            }

            // Configurable items per sheet (default 21)
            $perPage = max(1, min(100, (int)($_GET['per_page'] ?? 21)));
            $chunks = array_chunk($items, $perPage);
            $totalSheets = count($chunks);

            $zipPath = tempnam(sys_get_temp_dir(), 'phv_zip_') . '.zip';
            $zip = new ZipArchive();
            if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                die('Could not initialize ZIP archive on server.');
            }

            require_once dirname(__DIR__) . '/app/helpers/PhvSheetPdf.php';

            // 1. Generate individual sheet PDFs (21 items each)
            foreach ($chunks as $index => $chunk) {
                $sheetNum = $index + 1;
                $pdfData = \App\Helpers\PhvSheetPdf::generateSheet(
                    $sheetNum, 
                    $totalSheets, 
                    $chunk, 
                    $station, 
                    $costCenter, 
                    $warehouse, 
                    date('Y-m-d')
                );
                $fileName = sprintf('Count_Sheet_%02d_of_%02d.pdf', $sheetNum, $totalSheets);
                $zip->addFromString($fileName, $pdfData);
            }

            // 2. Add Verification CSV Master Index
            $csv = "Sheet No,Row In Sheet,Global Index,Bin Location,Item Code,Item Description,Book Quantity,Physical Tally,Auditor Initial,Notes\r\n";
            $globalIdx = 1;
            foreach ($chunks as $sIdx => $chunk) {
                $sNum = $sIdx + 1;
                foreach ($chunk as $rIdx => $it) {
                    $rowInSheet = $rIdx + 1;
                    $bin = str_replace(['"', ','], '', $it['bin_location'] ?? '-');
                    $sku = str_replace(['"', ','], '', $it['sku'] ?? '');
                    $desc = '"' . str_replace('"', '""', $it['item_name'] ?? '') . '"';
                    $book = $it['book_quantity'] ?? $it['remaining_quantity'] ?? 0;
                    $csv .= "{$sNum},{$rowInSheet},{$globalIdx},{$bin},{$sku},{$desc},{$book},,,\r\n";
                    $globalIdx++;
                }
            }
            $zip->addFromString('00_Master_Verification_Index.csv', $csv);

            // 3. Add Instructions README
            $readme = "CEB / EGL BOARD OF SURVEY - PHYSICAL STOCK VERIFICATION\r\n";
            $readme .= "============================================================\r\n";
            $readme .= "Power Station   : " . $station . "\r\n";
            $readme .= "Warehouse       : " . $warehouse . "\r\n";
            $readme .= "Cost Center     : " . $costCenter . "\r\n";
            $readme .= "Generated Date  : " . date('Y-m-d H:i:s') . "\r\n";
            $readme .= "Total Items     : " . count($items) . "\r\n";
            $readme .= "Items / Sheet   : 21 (Statutory Standard)\r\n";
            $readme .= "Total Sheets    : " . $totalSheets . " PDF Sheets\r\n";
            $readme .= "============================================================\r\n\r\n";
            $readme .= "INSTRUCTIONS FOR VERIFICATION OFFICERS:\r\n";
            $readme .= "1. Print each PDF sheet on standard A4 paper.\r\n";
            $readme .= "2. Count physical stocks item by item matching the bin location.\r\n";
            $readme .= "3. Write the exact count in the 'PHYSICAL COUNT' box.\r\n";
            $readme .= "4. The Verifier, Supervising Officer, and Board of Survey Auditor must sign every sheet.\r\n";
            $readme .= "5. Return completed tally sheets for data entry in FIFO ERP.\r\n";
            $zip->addFromString('README_Audit_Instructions.txt', $readme);

            $zip->close();

            while (ob_get_level()) {
                ob_end_clean();
            }

            $cleanWh = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $warehouse);
            $zipDownloadName = "PHV_Count_Sheets_{$cleanWh}_" . date('Ymd_His') . ".zip";

            header('Content-Description: File Transfer');
            header('Content-Type: application/zip');
            header('Content-Disposition: attachment; filename="' . $zipDownloadName . '"');
            header('Content-Transfer-Encoding: binary');
            header('Expires: 0');
            header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
            header('Pragma: public');
            header('Content-Length: ' . filesize($zipPath));
            readfile($zipPath);
            @unlink($zipPath);
            exit;

        // -------------------------------------------------------------
        // Download Single Count Sheet as PDF
        // -------------------------------------------------------------
        case 'download_sheet_pdf':
            $sessionId = (int)($_GET['session_id'] ?? 1);
            $sheetNum = max(1, (int)($_GET['sheet_num'] ?? 1));
            $station = trim($_GET['station'] ?? 'Victoria Hydro Power Station');
            $costCenter = trim($_GET['cost_center'] ?? 'Mechanical');
            $warehouse = trim($_GET['warehouse'] ?? 'Central Main Store');

            $stmt = $db->prepare("
                SELECT e.* 
                FROM phv_entries e 
                WHERE e.session_id = :sid 
                ORDER BY e.bin_location ASC, e.sku ASC
            ");
            $stmt->execute([':sid' => $sessionId]);
            $items = $stmt->fetchAll();

            if (empty($items)) {
                $stmt = $db->prepare("
                    SELECT id as batch_id, sku, item_name, location_bin as bin_location, 
                           remaining_quantity as book_quantity, unit_cost 
                    FROM fifo_batches 
                    WHERE location_id = :loc AND remaining_quantity > 0 
                    ORDER BY location_bin ASC, sku ASC
                ");
                $stmt->execute([':loc' => $locationId]);
                $items = $stmt->fetchAll();
            }

            $perPage = max(1, min(100, (int)($_GET['per_page'] ?? 21)));
            $chunks = array_chunk($items, $perPage);
            $totalSheets = max(1, count($chunks));
            $chunk = $chunks[$sheetNum - 1] ?? [];

            require_once dirname(__DIR__) . '/app/helpers/PhvSheetPdf.php';
            $pdfData = \App\Helpers\PhvSheetPdf::generateSheet(
                $sheetNum, 
                $totalSheets, 
                $chunk, 
                $station, 
                $costCenter, 
                $warehouse, 
                date('Y-m-d')
            );

            while (ob_get_level()) {
                ob_end_clean();
            }

            header('Content-Type: application/pdf');
            header('Content-Disposition: inline; filename="Count_Sheet_' . sprintf('%02d', $sheetNum) . '_of_' . sprintf('%02d', $totalSheets) . '.pdf"');
            header('Content-Length: ' . strlen($pdfData));
            echo $pdfData;
            exit;

        case 'get_warehouse_freeze_status':
            $central = Database::getConnection();
            $central->exec("CREATE TABLE IF NOT EXISTS erp_warehouse_freeze (
                id INT AUTO_INCREMENT PRIMARY KEY,
                department_id INT NOT NULL UNIQUE,
                department_code VARCHAR(50) DEFAULT '',
                department_name VARCHAR(150) DEFAULT '',
                is_frozen TINYINT(1) DEFAULT 0,
                frozen_at DATETIME NULL,
                frozen_by VARCHAR(100) NULL
            )");
            $rows = $central->query("
                SELECT d.id, d.code, d.name, COALESCE(f.is_frozen, 0) as is_frozen, f.frozen_at, f.frozen_by
                FROM departments d
                LEFT JOIN erp_warehouse_freeze f ON f.department_id = d.id
                WHERE d.status = 'active'
                ORDER BY d.name ASC
            ")->fetchAll();
            Response::success('Warehouse freeze status loaded', ['warehouses' => $rows]);
            break;

        case 'freeze_warehouses':
        case 'unfreeze_warehouses':
            $central = Database::getConnection();
            $central->exec("CREATE TABLE IF NOT EXISTS erp_warehouse_freeze (
                id INT AUTO_INCREMENT PRIMARY KEY,
                department_id INT NOT NULL UNIQUE,
                department_code VARCHAR(50) DEFAULT '',
                department_name VARCHAR(150) DEFAULT '',
                is_frozen TINYINT(1) DEFAULT 0,
                frozen_at DATETIME NULL,
                frozen_by VARCHAR(100) NULL
            )");
            $rawIds = $_POST['department_ids'] ?? '';
            $ids = is_array($rawIds) ? $rawIds : (json_decode($rawIds, true) ?: array_filter(explode(',', $rawIds)));
            $ids = array_map('intval', $ids);
            $ids = array_filter($ids, fn($v) => $v > 0);
            if (empty($ids)) {
                Response::error('Please select at least one warehouse/department.');
            }
            $isFreeze = ($action === 'freeze_warehouses') ? 1 : 0;
            $user = $_SESSION['role_name'] ?? '';
            if ($user === '' && !empty($_SESSION['role_id'])) {
                try {
                    $stR = $central->prepare("SELECT display_name, name FROM roles WHERE id = ?");
                    $stR->execute([(int)$_SESSION['role_id']]);
                    $rRow = $stR->fetch();
                    if ($rRow) {
                        $user = $rRow['display_name'] ?: $rRow['name'];
                    }
                } catch (\Throwable $e) {}
            }
            if ($user === '') {
                $user = $_SESSION['username'] ?? 'User';
            }
            $stDept = $central->prepare("SELECT id, code, name FROM departments WHERE id = ?");
            $stUpsert = $central->prepare("
                INSERT INTO erp_warehouse_freeze (department_id, department_code, department_name, is_frozen, frozen_at, frozen_by)
                VALUES (?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    department_code = VALUES(department_code),
                    department_name = VALUES(department_name),
                    is_frozen = VALUES(is_frozen),
                    frozen_at = VALUES(frozen_at),
                    frozen_by = VALUES(frozen_by)
            ");
            foreach ($ids as $did) {
                $stDept->execute([$did]);
                $d = $stDept->fetch();
                if ($d) {
                    $stUpsert->execute([
                        $did,
                        $d['code'] ?? '',
                        $d['name'] ?? '',
                        $isFreeze,
                        $isFreeze ? date('Y-m-d H:i:s') : null,
                        $isFreeze ? $user : null
                    ]);
                }
            }
            Response::success($isFreeze ? 'Selected warehouses have been FROZEN for Physical Verification.' : 'Selected warehouses have been UNFROZEN.');
            break;

        default:
            Response::error('Invalid PHV action: ' . $action);
    }
} catch (Exception $e) {
    Response::error($e->getMessage());
}

