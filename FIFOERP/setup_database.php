<?php
/**
 * FIFOERP - One-Click Database Installer & Migrator for 'fifonew'
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

$host = 'localhost';
$port = '3306';
$user = 'root';
$pass = '';
$dbName = 'fifonew';
$sqlFile = __DIR__ . '/database/fifonew.sql';

$messages = [];
$success = false;

try {
    // 1. Connect without selecting database
    $pdo = new PDO("mysql:host={$host};port={$port};charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    $messages[] = "✓ Connected to MySQL server on {$host}:{$port}";

    // 2. Create database fifonew
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
    $messages[] = "✓ Database `{$dbName}` created or verified successfully";

    // 3. Select database
    $pdo->exec("USE `{$dbName}`;");
    $messages[] = "✓ Switched to database `{$dbName}`";

    // 4. Read and execute SQL schema
    if (!file_exists($sqlFile)) {
        throw new Exception("Schema file not found at: {$sqlFile}");
    }

    $sqlContent = file_get_contents($sqlFile);
    
    // Split queries by semicolon outside of strings
    // Or execute using multi-query
    $pdo->exec($sqlContent);
    $messages[] = "✓ Executed fifonew.sql schema & seed data successfully";

    // 5. Verify tables count
    $stmt = $pdo->query("SHOW TABLES;");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $messages[] = "✓ Verified " . count($tables) . " tables created in `{$dbName}` (" . implode(', ', array_slice($tables, 0, 8)) . "...)";

    $success = true;
} catch (Exception $e) {
    $messages[] = "✗ Error: " . $e->getMessage();
}

if (php_sapi_name() === 'cli') {
    echo "\n=== FIFOERP Database Setup ===\n";
    foreach ($messages as $msg) {
        echo $msg . "\n";
    }
    echo ($success ? "\n>>> Database `{$dbName}` is 100% READY!\n" : "\n>>> Setup Failed!\n");
    exit($success ? 0 : 1);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FIFOERP Database Setup (fifonew)</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: #f8fafc;
            color: #0f172a;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            margin: 0;
            padding: 20px;
        }
        .card {
            background: #ffffff;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.06);
            border: 1px solid #e2e8f0;
            max-width: 600px;
            width: 100%;
            padding: 32px;
        }
        .header {
            display: flex;
            align-items: center;
            gap: 14px;
            border-bottom: 1px solid #f1f5f9;
            padding-bottom: 20px;
            margin-bottom: 20px;
        }
        .header img {
            height: 40px;
        }
        h2 {
            margin: 0;
            font-size: 1.3rem;
            color: #0f172a;
        }
        .status-badge {
            display: inline-block;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            margin-bottom: 18px;
        }
        .status-success {
            background: #dcfce7;
            color: #15803d;
        }
        .status-error {
            background: #fee2e2;
            color: #b91c1c;
        }
        .log-box {
            background: #0f172a;
            color: #38bdf8;
            font-family: monospace;
            padding: 16px;
            border-radius: 8px;
            font-size: 0.85rem;
            line-height: 1.6;
            margin-bottom: 24px;
            max-height: 250px;
            overflow-y: auto;
        }
        .btn {
            display: inline-block;
            background: #0d9488;
            color: #ffffff;
            text-decoration: none;
            padding: 10px 24px;
            border-radius: 6px;
            font-weight: 600;
            font-size: 0.95rem;
            transition: background 0.2s;
        }
        .btn:hover {
            background: #0f766e;
        }
    </style>
</head>
<body>
    <div class="card">
        <div class="header">
            <img src="assets/images/EGL-NEW.png" alt="EGL Logo">
            <div>
                <h2>FIFOERP Database Setup</h2>
                <div style="font-size: 0.85rem; color: #64748b;">Target Database: <strong>fifonew</strong></div>
            </div>
        </div>

        <?php if ($success): ?>
            <div class="status-badge status-success">✓ Database Installed Successfully</div>
        <?php else: ?>
            <div class="status-badge status-error">✗ Setup Error Occurred</div>
        <?php endif; ?>

        <div class="log-box">
            <?php foreach ($messages as $msg): ?>
                <div><?= htmlspecialchars($msg) ?></div>
            <?php endforeach; ?>
        </div>

        <?php if ($success): ?>
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <span style="font-size: 0.88rem; color: #64748b;">Credentials: <strong>admin</strong> / <strong>admin123</strong></span>
                <a href="index.php" class="btn">Launch FIFOERP Portal &rarr;</a>
            </div>
        <?php else: ?>
            <button onclick="location.reload();" class="btn" style="background: #ef4444;">Retry Setup</button>
        <?php endif; ?>
    </div>
</body>
</html>

