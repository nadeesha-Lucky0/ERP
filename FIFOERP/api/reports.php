<?php
require_once dirname(__DIR__) . '/app/config/config.php';

use App\Core\Auth;
use App\Core\Response;
use App\Config\Database;

header('Content-Type: application/json; charset=utf-8');

$report = $_GET['report'] ?? '';
$locationId = (int)($_SESSION['location_id'] ?? 1);

try {
    $db = Database::getConnection();

    switch ($report) {
        // -------------------------------------------------------------
        // QOH: Batch-Wise
        // -------------------------------------------------------------
        case 'qoh_batch':
            $stmt = $db->prepare("
                SELECT b.*, (b.remaining_quantity * b.unit_cost) as total_valuation,
                DATEDIFF(CURDATE(), b.received_date) as days_in_stock
                FROM fifo_batches b
                WHERE b.location_id = :loc
                ORDER BY b.item_name ASC, b.received_date ASC
            ");
            $stmt->execute([':loc' => $locationId]);
            $rows = $stmt->fetchAll();
            Response::success('QOH Batch wise report', ['data' => $rows]);
            break;

        // -------------------------------------------------------------
        // QOH: Material-Wise (Aggregated by SKU)
        // -------------------------------------------------------------
        case 'qoh_material':
            $stmt = $db->prepare("
                SELECT sku, item_name, category,
                SUM(remaining_quantity) as total_quantity_on_hand,
                AVG(unit_cost) as avg_unit_cost,
                SUM(remaining_quantity * unit_cost) as total_stock_valuation,
                COUNT(id) as total_batches,
                SUM(IF(freeze_status = 'frozen', remaining_quantity, 0)) as frozen_quantity
                FROM fifo_batches
                WHERE location_id = :loc
                GROUP BY sku, item_name, category
                ORDER BY item_name ASC
            ");
            $stmt->execute([':loc' => $locationId]);
            $rows = $stmt->fetchAll();
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

        default:
            Response::error('Invalid report requested: ' . $report);
    }
} catch (Exception $e) {
    Response::error($e->getMessage());
}

