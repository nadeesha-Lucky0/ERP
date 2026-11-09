<?php
require_once __DIR__ . '/app/config/config.php';

use App\Core\Auth;

Auth::logout();
header("Location: " . BASE_PATH . "/login.php");
exit;

