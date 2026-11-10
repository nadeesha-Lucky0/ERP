<?php
require_once dirname(__DIR__) . '/app/config/config.php';

use App\Core\Auth;
use App\Core\Response;
use App\Config\Database;

header('Content-Type: application/json; charset=utf-8');

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$locationId = (int)($_SESSION['location_id'] ?? 1);

try {
    $db = Database::getStationConnection($locationId);

    switch ($action) {
        // -------------------------------------------------------------
        // Cash Book Vouchers
        // -------------------------------------------------------------
        case 'get_entries':
            $stmt = $db->prepare("SELECT * FROM cash_book_entries WHERE location_id = :loc ORDER BY entry_date DESC, id DESC");
            $stmt->execute([':loc' => $locationId]);
            $entries = $stmt->fetchAll();
            Response::success('Cash book loaded', ['entries' => $entries]);
            break;

        case 'add_entry':
            $type = trim($_POST['entry_type'] ?? 'Payment');
            $title = trim($_POST['account_title'] ?? '');
            $amount = (float)($_POST['amount'] ?? 0);
            $payee = trim($_POST['payee_or_source'] ?? '');
            $desc = trim($_POST['description'] ?? '');
            $vNo = 'CB-V-' . date('Y') . '-' . str_pad((string)rand(10, 999), 4, '0', STR_PAD_LEFT);

            if ($amount <= 0 || empty($title)) {
                Response::error('Valid amount and account title required.');
            }

            $stmt = $db->prepare("
                INSERT INTO cash_book_entries (location_id, entry_date, voucher_no, entry_type, account_title, amount, payee_or_source, description, status, created_by)
                VALUES (:loc, CURDATE(), :vno, :type, :title, :amt, :payee, :desc, 'Verified', :uid)
            ");
            $stmt->execute([
                ':loc' => $locationId,
                ':vno' => $vNo,
                ':type' => $type,
                ':title' => $title,
                ':amt' => $amount,
                ':payee' => $payee,
                ':desc' => $desc,
                ':uid' => Auth::id() ?? 1
            ]);

            Response::success('Cash voucher created', ['voucher_no' => $vNo]);
            break;

        // -------------------------------------------------------------
        // Payslip Cancellations
        // -------------------------------------------------------------
        case 'get_payslip_cancellations':
            $stmt = $db->prepare("SELECT * FROM payslip_cancellations WHERE location_id = :loc ORDER BY id DESC");
            $stmt->execute([':loc' => $locationId]);
            $cancellations = $stmt->fetchAll();
            Response::success('Payslip cancellations loaded', ['cancellations' => $cancellations]);
            break;

        case 'request_payslip_cancellation':
            $payslipNo = trim($_POST['payslip_no'] ?? '');
            $name = trim($_POST['employee_name'] ?? '');
            $epf = trim($_POST['epf_no'] ?? '');
            $dept = trim($_POST['department'] ?? 'Operations');
            $period = trim($_POST['pay_period'] ?? date('F Y'));
            $amount = (float)($_POST['amount'] ?? 0);
            $reason = trim($_POST['reason'] ?? '');
            $ref = 'PSC-' . date('Y') . '-' . str_pad((string)rand(10, 999), 3, '0', STR_PAD_LEFT);

            if (empty($payslipNo) || empty($name) || empty($reason)) {
                Response::error('Payslip No, Employee Name, and Reason are required.');
            }

            $stmt = $db->prepare("
                INSERT INTO payslip_cancellations 
                (location_id, cancellation_ref, payslip_no, employee_name, epf_no, department, pay_period, amount, reason, status, requested_by)
                VALUES
                (:loc, :ref, :pno, :name, :epf, :dept, :period, :amt, :reason, 'Pending Review', :uid)
            ");
            $stmt->execute([
                ':loc' => $locationId,
                ':ref' => $ref,
                ':pno' => $payslipNo,
                ':name' => $name,
                ':epf' => $epf,
                ':dept' => $dept,
                ':period' => $period,
                ':amt' => $amount,
                ':reason' => $reason,
                ':uid' => Auth::id() ?? 1
            ]);

            Response::success('Payslip cancellation requested', ['cancellation_ref' => $ref]);
            break;

        case 'update_payslip_status':
            $id = (int)($_POST['id'] ?? 0);
            $status = trim($_POST['status'] ?? 'Approved'); // Approved, Rejected

            $stmt = $db->prepare("UPDATE payslip_cancellations SET status = :st, approved_by = :uid WHERE id = :id AND location_id = :loc");
            $stmt->execute([':st' => $status, ':uid' => Auth::id() ?? 1, ':id' => $id, ':loc' => $locationId]);

            Response::success("Payslip cancellation {$status}");
            break;

        default:
            Response::error('Invalid cashbook action: ' . $action);
    }
} catch (Exception $e) {
    Response::error($e->getMessage());
}

