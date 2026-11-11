<?php
require_once __DIR__ . '/app/config/config.php';
use App\Config\Database;
$db = Database::getConnection();
$res = $db->query("SELECT code, title, display_label FROM issue_destination_codes LIMIT 20")->fetchAll(PDO::FETCH_ASSOC);
echo json_encode($res, JSON_PRETTY_PRINT);

