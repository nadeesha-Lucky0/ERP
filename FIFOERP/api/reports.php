<?php
require_once dirname(__DIR__) . '/app/config/config.php';

use App\Core\Auth;
use App\Core\Response;
use App\Config\Database;

header('Content-Type: application/json; charset=utf-8');

$report = $_GET['report'] ?? '';
$locationId = (int)($_SESSION['location_id'] ?? 1);

try {
    $db = Database::getStationConnection($locationId);

    switch ($report) {
        // -------------------------------------------------------------
        // QOH: Batch-Wise
        // -------------------------------------------------------------
        case 'qoh_batch':
            $stmt = $db->prepare("
                SELECT b.*, (b.remaining_quantity * b.unit_cost) as total_valuation,
                DATEDIFF(CURDATE(), b.received_date) as days_in_stock,
                COALESCE((
                    SELECT SUM(udi.quantity) 
                    FROM unposted_document_items udi
                    JOIN unposted_documents ud ON udi.document_id = ud.id
                    WHERE udi.sku = b.sku 
                      AND ud.location_id = :loc1
                      AND ud.doc_type = 'ISSUE'
                      AND ud.status IN ('Draft', 'Validated', 'Approved')
                ), 0) as sku_allocated_quantity
                FROM fifo_batches b
                WHERE b.location_id = :loc2
                ORDER BY b.item_name ASC, b.received_date ASC
            ");
            $stmt->execute([':loc1' => $locationId, ':loc2' => $locationId]);
            $rows = $stmt->fetchAll();
            Response::success('QOH Batch wise report', ['data' => $rows]);
            break;

        // -------------------------------------------------------------
        // QOH: Material-Wise (Aggregated by SKU with Three-Tier Live Balances)
        // -------------------------------------------------------------
        case 'qoh_material':
            $stmt = $db->prepare("
                SELECT sku, item_name, category,
                SUM(remaining_quantity) as total_quantity_on_hand,
                COALESCE((
                    SELECT SUM(udi.quantity) 
                    FROM unposted_document_items udi
                    JOIN unposted_documents ud ON udi.document_id = ud.id
                    WHERE udi.sku = fifo_batches.sku 
                      AND ud.location_id = :loc1
                      AND ud.doc_type = 'ISSUE'
                      AND ud.status IN ('Draft', 'Validated', 'Approved')
                ), 0) as allocated_quantity,
                AVG(unit_cost) as avg_unit_cost,
                SUM(remaining_quantity * unit_cost) as total_stock_valuation,
                COUNT(id) as total_batches,
                SUM(IF(freeze_status = 'frozen', remaining_quantity, 0)) as frozen_quantity
                FROM fifo_batches
                WHERE location_id = :loc2
                GROUP BY sku, item_name, category
                ORDER BY item_name ASC
            ");
            $stmt->execute([':loc1' => $locationId, ':loc2' => $locationId]);
            $rows = $stmt->fetchAll();
            foreach ($rows as &$r) {
                $r['available_quantity'] = max(0, (int)$r['total_quantity_on_hand'] - (int)$r['allocated_quantity']);
            }
            unset($r);
            Response::success('QOH Material wise report', ['data' => $rows]);
            break;

        // -------------------------------------------------------------
        // CPC Procurements (Ceylon Petroleum Corporation Fuel Consignments)
        // -------------------------------------------------------------
        case 'cpc_procurements':
            $stmt = $db->prepare("
                SELECT * FROM cpc_procurements 
                WHERE location_id = :loc 
                ORDER BY consignment_date DESC
            ");
            $stmt->execute([':loc' => $locationId]);
            $rows = $stmt->fetchAll();
            Response::success('CPC Procurements report', ['data' => $rows]);
            break;

        // -------------------------------------------------------------
        // Average Diesel Price Logs
        // -------------------------------------------------------------
        case 'avg_diesel':
            $stmt = $db->query("SELECT * FROM diesel_price_logs ORDER BY effective_month DESC");
            $rows = $stmt->fetchAll();
            Response::success('Average Diesel Price report', ['data' => $rows]);
            break;

        case 'add_diesel_price':
            $month = trim($_POST['effective_month'] ?? date('Y-m'));
            $fuelType = trim($_POST['fuel_type'] ?? 'Auto Diesel');
            $avgPrice = (float)($_POST['avg_price_per_litre'] ?? 0);
            $litres = (float)($_POST['total_procured_litres'] ?? 0);
            $trend = trim($_POST['price_trend'] ?? 'Stable');
            $totalCost = $avgPrice * $litres;

            if ($avgPrice <= 0 || empty($month)) {
                Response::error('Effective Month and Average Price per Litre are required.');
            }

            $ins = $db->prepare("
                INSERT INTO diesel_price_logs 
                (effective_month, fuel_type, avg_price_per_litre, total_procured_litres, total_procurement_cost, price_trend)
                VALUES
                (:month, :fuel, :price, :litres, :cost, :trend)
            ");
            $ins->execute([
                ':month' => $month,
                ':fuel' => $fuelType,
                ':price' => $avgPrice,
                ':litres' => $litres,
                ':cost' => $totalCost,
                ':trend' => $trend
            ]);

            Response::success('Diesel Price Log saved successfully');
            break;

        case 'add_cpc_procurement':
            $orderNo = trim($_POST['order_no'] ?? '');
            $fuelType = trim($_POST['fuel_type'] ?? 'Auto Diesel 500ppm (Industrial)');
            $tankId = trim($_POST['tank_id'] ?? 'TANK-01');
            $litres = (float)($_POST['quantity_litres'] ?? 0);
            $rate = (float)($_POST['unit_price_lkr'] ?? 0);
            $deliveryRef = trim($_POST['delivery_ref'] ?? '');
            $bowserNo = trim($_POST['bowser_no'] ?? '');
            $depot = trim($_POST['supplier_depot'] ?? 'Peradeniya Bulk Depot');
            $date = $_POST['consignment_date'] ?? date('Y-m-d');
            $totalAmt = $litres * $rate;

            if (empty($orderNo) || $litres <= 0 || $rate <= 0) {
                Response::error('Order No, Quantity, and Unit Price are required.');
            }

            $insCPC = $db->prepare("
                INSERT INTO cpc_procurements 
                (location_id, order_no, fuel_type, tank_id, quantity_litres, unit_price_lkr, total_amount_lkr, consignment_date, delivery_ref, bowser_no, supplier_depot, status)
                VALUES
                (:loc, :ord, :fuel, :tank, :litres, :rate, :total, :cdate, :dref, :bowser, :depot, 'Received & Pumped')
            ");
            $insCPC->execute([
                ':loc' => $locationId,
                ':ord' => $orderNo,
                ':fuel' => $fuelType,
                ':tank' => $tankId,
                ':litres' => $litres,
                ':rate' => $rate,
                ':total' => $totalAmt,
                ':cdate' => $date,
                ':dref' => $deliveryRef,
                ':bowser' => $bowserNo,
                ':depot' => $depot
            ]);

            Response::success('CPC Fuel Consignment recorded successfully');
            break;

        // -------------------------------------------------------------
        // Monthly Transfers (Between Station Bays/Workshops)
        // -------------------------------------------------------------
        case 'monthly_transfers':
            $stmt = $db->query("SELECT * FROM stock_transfers ORDER BY transfer_date DESC");
            $rows = $stmt->fetchAll();
            Response::success('Monthly Stock Transfers', ['data' => $rows]);
            break;

        // -------------------------------------------------------------
        // Job-Wise Material Issue Report
        // -------------------------------------------------------------
        case 'job_issues':
            $stmt = $db->prepare("
                SELECT t.*, d.destination_name, d.category_name
                FROM inventory_transactions t
                LEFT JOIN issue_destination_codes d ON t.destination_code = d.destination_code
                WHERE t.location_id = :loc AND t.transaction_type = 'MATERIAL_ISSUE'
                ORDER BY t.transaction_date DESC, t.id DESC
            ");
            $stmt->execute([':loc' => $locationId]);
            $rows = $stmt->fetchAll();
            Response::success('Job-wise material issues', ['data' => $rows]);
            break;

        // -------------------------------------------------------------
        // Monthly Transaction Summary (GRN vs Issue)
        // -------------------------------------------------------------
        case 'monthly_summary':
            $stmt = $db->prepare("
                SELECT 
                    DATE_FORMAT(transaction_date, '%Y-%m') as month,
                    SUM(IF(transaction_type = 'GRN_RECEIPT', total_value, 0)) as grn_total_value,
                    SUM(IF(transaction_type = 'GRN_RECEIPT', quantity, 0)) as grn_total_qty,
                    SUM(IF(transaction_type = 'MATERIAL_ISSUE', total_value, 0)) as issue_total_value,
                    SUM(IF(transaction_type = 'MATERIAL_ISSUE', quantity, 0)) as issue_total_qty
                FROM inventory_transactions
                WHERE location_id = :loc
                GROUP BY DATE_FORMAT(transaction_date, '%Y-%m')
                ORDER BY month DESC
            ");
            $stmt->execute([':loc' => $locationId]);
            $rows = $stmt->fetchAll();
            Response::success('Monthly Transaction Summary', ['data' => $rows]);
            break;

        // -------------------------------------------------------------
        // Inventory Inquiry Report
        // -------------------------------------------------------------
        case 'inventory_inquiry':
            $costCenter = trim($_GET['cost_center'] ?? '');
            $fromDate = $_GET['from_date'] ?? '';
            $toDate = $_GET['to_date'] ?? '';
            $sku = trim($_GET['sku'] ?? '');

            $sql = "
                SELECT b.id, b.sku, b.item_name, b.category, b.location_bin, b.unit_cost,
                       b.initial_quantity, b.remaining_quantity,
                       (b.initial_quantity - b.remaining_quantity) as issued_quantity,
                       (b.remaining_quantity * b.unit_cost) as total_valuation,
                       b.freeze_status, b.status
                FROM fifo_batches b
                WHERE b.location_id = :loc
            ";
            $params = [':loc' => $locationId];

            if (!empty($sku)) {
                $sql .= " AND (b.sku LIKE :sku OR b.item_name LIKE :sku)";
                $params[':sku'] = '%' . $sku . '%';
            }
            if (!empty($fromDate)) {
                $sql .= " AND b.received_date >= :fdate";
                $params[':fdate'] = $fromDate;
            }
            if (!empty($toDate)) {
                $sql .= " AND b.received_date <= :tdate";
                $params[':tdate'] = $toDate;
            }

            $sql .= " ORDER BY b.location_bin ASC, b.sku ASC";
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            $rows = $stmt->fetchAll();

            Response::success('Inventory Inquiry report loaded', [
                'items' => $rows,
                'total_records' => count($rows),
                'cost_center' => $costCenter ?: 'All Cost Centers'
            ]);
            break;

        // -------------------------------------------------------------
        // Monthly Transaction Report
        // -------------------------------------------------------------
        case 'monthly_tx':
            $docType = trim($_GET['document_type'] ?? 'ALL');
            $year = trim($_GET['year'] ?? date('Y'));
            $month = trim($_GET['month'] ?? '');
            $warehouse = trim($_GET['warehouse'] ?? '');

            $sql = "
                SELECT t.*
                FROM inventory_transactions t
                WHERE t.location_id = :loc
            ";
            $params = [':loc' => $locationId];

            if ($docType !== 'ALL' && !empty($docType)) {
                if ($docType === 'GRN') {
                    $sql .= " AND t.transaction_type IN ('GRN_RECEIPT', 'RECEIPT')";
                } elseif ($docType === 'GIN') {
                    $sql .= " AND t.transaction_type IN ('MATERIAL_ISSUE', 'ISSUE')";
                } elseif ($docType === 'MTN') {
                    $sql .= " AND t.transaction_type = 'TRANSFER'";
                } elseif ($docType === 'ADJ') {
                    $sql .= " AND t.transaction_type IN ('RECONCILIATION', 'ADJUSTMENT')";
                }
            }

            if (!empty($year)) {
                $sql .= " AND YEAR(t.transaction_date) = :yr";
                $params[':yr'] = $year;
            }
            if (!empty($month) && (int)$month > 0) {
                $sql .= " AND MONTH(t.transaction_date) = :mo";
                $params[':mo'] = (int)$month;
            }

            $sql .= " ORDER BY t.transaction_date DESC, t.id DESC";
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            $rows = $stmt->fetchAll();

            Response::success('Monthly Transaction Report loaded', [
                'transactions' => $rows,
                'total_count' => count($rows),
                'year' => $year,
                'month' => $month
            ]);
            break;

        // -------------------------------------------------------------
        // GRN - Issue Summary Report
        // -------------------------------------------------------------
        case 'grn_issue_summary':
            $costCenter = trim($_GET['cost_center'] ?? '');
            $warehouse = trim($_GET['warehouse'] ?? '');
            $fromDate = $_GET['from_date'] ?? date('Y-01-01');
            $toDate = $_GET['to_date'] ?? date('Y-m-d');

            $stmt = $db->prepare("
                SELECT b.sku, b.item_name, b.category, 'NOS' as uom, b.unit_cost,
                       b.initial_quantity as total_grn_qty,
                       (b.initial_quantity * b.unit_cost) as total_grn_val,
                       (b.initial_quantity - b.remaining_quantity) as total_issue_qty,
                       ((b.initial_quantity - b.remaining_quantity) * b.unit_cost) as total_issue_val,
                       b.remaining_quantity as net_balance_qty,
                       (b.remaining_quantity * b.unit_cost) as net_balance_val
                FROM fifo_batches b
                WHERE b.location_id = :loc
                ORDER BY b.sku ASC
            ");
            $stmt->execute([':loc' => $locationId]);
            $items = $stmt->fetchAll();

            Response::success('GRN - Issue Summary generated', [
                'items' => $items,
                'cost_center' => $costCenter ?: 'All Departments',
                'warehouse' => $warehouse ?: 'Central Warehouse'
            ]);
            break;

        // -------------------------------------------------------------
        // Material Flow Report (Opening + Receipts - Issues = Closing)
        // -------------------------------------------------------------
        case 'material_flow':
            $costCenter = trim($_GET['cost_center'] ?? '');
            $warehouse = trim($_GET['warehouse'] ?? '');
            $sku = trim($_GET['sku'] ?? '');
            $fromDate = $_GET['from_date'] ?? date('Y-01-01');
            $toDate = $_GET['to_date'] ?? date('Y-m-d');

            $sql = "
                SELECT b.sku, b.item_name, b.category, b.unit_cost,
                       b.initial_quantity as opening_qty,
                       (b.initial_quantity * b.unit_cost) as opening_val,
                       b.initial_quantity as period_receipt_qty,
                       (b.initial_quantity * b.unit_cost) as period_receipt_val,
                       (b.initial_quantity - b.remaining_quantity) as period_issue_qty,
                       ((b.initial_quantity - b.remaining_quantity) * b.unit_cost) as period_issue_val,
                       b.remaining_quantity as closing_qty,
                       (b.remaining_quantity * b.unit_cost) as closing_val
                FROM fifo_batches b
                WHERE b.location_id = :loc
            ";
            $params = [':loc' => $locationId];
            if (!empty($sku)) {
                $sql .= " AND (b.sku = :sku OR b.sku LIKE :skulike)";
                $params[':sku'] = $sku;
                $params[':skulike'] = '%' . $sku . '%';
            }
            $sql .= " ORDER BY b.sku ASC";

            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            $rows = $stmt->fetchAll();

            Response::success('Material Flow Report generated', [
                'items' => $rows,
                'from_date' => $fromDate,
                'to_date' => $toDate,
                'warehouse' => $warehouse ?: 'All Warehouses'
            ]);
            break;

        default:
            Response::error('Invalid report requested: ' . $report);
    }
} catch (Exception $e) {
    Response::error($e->getMessage());
}

