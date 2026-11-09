<?php
require_once dirname(__DIR__) . '/app/config/config.php';

use App\Core\Response;
use App\Config\Database;

header('Content-Type: application/json; charset=utf-8');

$query = trim($_GET['q'] ?? '');
if (strlen($query) < 2) {
    Response::success('Query too short', ['results' => []]);
}

try {
    $db = Database::getConnection();
    $locationId = (int)($_SESSION['location_id'] ?? 1);
    $qParam = "%{$query}%";

    $results = [];

    // 1. Search Batches & Materials
    $stmt = $db->prepare("
        SELECT 'Inventory Item' as type, CONCAT(sku, ' - ', item_name) as title, 
               CONCAT('Batch: ', batch_number, ' | Qty: ', remaining_quantity, ' | Bin: ', location_bin) as subtitle,
               'tab-inventory' as target_tab
        FROM fifo_batches
        WHERE location_id = :loc AND (sku LIKE :q1 OR item_name LIKE :q2 OR batch_number LIKE :q3)
        LIMIT 5
    ");
    $stmt->execute([':loc' => $locationId, ':q1' => $qParam, ':q2' => $qParam, ':q3' => $qParam]);
    $results = array_merge($results, $stmt->fetchAll());

    // 2. Search Documents (GRN & Issues)
    $stmt2 = $db->prepare("
        SELECT CONCAT(doc_type, ' Document') as type, doc_no as title,
               CONCAT('Status: ', status, ' | Total: Rs. ', FORMAT(invoice_total, 2)) as subtitle,
               'tab-transactions' as target_tab
        FROM unposted_documents
        WHERE location_id = :loc AND (doc_no LIKE :d1 OR receipt_from LIKE :d2 OR remarks LIKE :d3)
        LIMIT 5
    ");
    $stmt2->execute([':loc' => $locationId, ':d1' => $qParam, ':d2' => $qParam, ':d3' => $qParam]);
    $results = array_merge($results, $stmt2->fetchAll());

    // 3. Search CPC Fuel Orders
    $stmt3 = $db->prepare("
        SELECT 'CPC Fuel Order' as type, order_no as title,
               CONCAT(fuel_type, ' | ', FORMAT(quantity_litres, 0), ' L | ', delivery_ref) as subtitle,
               'tab-report-cpc' as target_tab
        FROM cpc_procurements
        WHERE location_id = :loc AND (order_no LIKE :c1 OR delivery_ref LIKE :c2 OR bowser_no LIKE :c3)
        LIMIT 5
    ");
    $stmt3->execute([':loc' => $locationId, ':c1' => $qParam, ':c2' => $qParam, ':c3' => $qParam]);
    $results = array_merge($results, $stmt3->fetchAll());

    Response::success('Search results', ['results' => $results]);
} catch (Exception $e) {
    Response::error($e->getMessage());
}
