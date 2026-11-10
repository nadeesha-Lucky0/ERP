<?php
/**
 * FIFOERP - Global Configuration
 */

// Define Root & App Paths
define('ROOT_PATH', dirname(__DIR__, 2));
define('APP_PATH', ROOT_PATH . DIRECTORY_SEPARATOR . 'app');
define('DATABASE_PATH', ROOT_PATH . DIRECTORY_SEPARATOR . 'database');

// Timezone
date_default_timezone_set('Asia/Colombo');

// Application Metadata
define('APP_NAME', 'Electricity Generation Lanka (Pvt) Ltd');
define('APP_SHORT_NAME', 'EGL');
define('APP_SYSTEM_NAME', 'FIFOERP');
define('APP_TAGLINE', 'Enterprise FIFO Inventory & Power Generation Operations Platform');
define('APP_VERSION', '2.0.0');

// Dynamic Base URL detection (supports localhost/FIFOERP, subdomains, and custom ports)
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)) ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';

$docRoot = str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT'] ?? '');
$realRoot = str_replace('\\', '/', ROOT_PATH);
$subPath = '';
if ($docRoot && strpos($realRoot, $docRoot) === 0) {
    $subPath = substr($realRoot, strlen($docRoot));
}
$subPath = '/' . trim($subPath, '/');
if ($subPath === '/') {
    $subPath = '';
}

define('BASE_PATH', $subPath);
define('BASE_URL', rtrim($protocol . $host . $subPath, '/'));

// Database configuration for central 'fifo' platform
define('DB_DRIVER', 'mysql');
define('DB_HOST', 'localhost');
define('DB_PORT', '3306');
define('DB_NAME', 'fifo');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// PSR-4 Autoloader for 'App\' namespace
spl_autoload_register(function ($class) {
    $prefixes = [
        'App\\Core\\' => APP_PATH . '/core/',
        'App\\Models\\' => APP_PATH . '/models/',
        'App\\Controllers\\' => APP_PATH . '/controllers/',
        'App\\Config\\' => APP_PATH . '/config/',
        'App\\Helpers\\' => APP_PATH . '/helpers/',
    ];

    foreach ($prefixes as $prefix => $baseDir) {
        if (strpos($class, $prefix) === 0) {
            $relativeClass = substr($class, strlen($prefix));
            $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
            if (file_exists($file)) {
                require_once $file;
                return;
            }
        }
    }
});

// Start session if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
