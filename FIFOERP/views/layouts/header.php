<?php
use App\Core\Auth;
use App\Helpers\Icon;

$currentUser = Auth::user();
$isSuperAdmin = ($currentUser['role_name'] ?? '') === 'super_admin';

// Power station code and role display
$locationCode = $currentUser['location_code'] ?? null;
if (!$locationCode && !empty($currentUser['location_id']) && !$isSuperAdmin) {
    try {
        $db = \App\Config\Database::getConnection();
        $stmt = $db->prepare("SELECT code FROM locations WHERE id = :lid LIMIT 1");
        $stmt->execute([':lid' => $currentUser['location_id']]);
        $locationCode = $stmt->fetchColumn() ?: '';
    } catch (\Throwable $e) {}
}

$roleDisplay = $currentUser['role_display'] ?? $currentUser['role_display_name'] ?? $currentUser['role_name'] ?? 'User';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= APP_SHORT_NAME ?> | <?= APP_SYSTEM_NAME ?> - Enterprise FIFO Portal</title>
  <meta name="csrf-token" content="<?= htmlspecialchars(\App\Core\Session::csrfToken()) ?>">
  <meta name="base-url" content="<?= BASE_PATH ?>">
  <link rel="stylesheet" href="<?= BASE_PATH ?>/assets/css/erp-desk.css?v=<?= time() ?>">
  <link rel="icon" type="image/png" href="<?= BASE_PATH ?>/assets/images/EGL-NEW.png">
</head>
<body>

<!-- Desk Top Navbar -->
<header class="desk-navbar">
  <a href="<?= BASE_PATH ?>/index.php" class="desk-navbar-brand">
    <img src="<?= BASE_PATH ?>/assets/images/EGL-NEW.png" alt="EGL Logo">
    <div>
      <span class="desk-brand-text">EGL FIFOERP</span>
    </div>
  </a>

  <!-- Navbar Actions -->
  <div class="desk-navbar-actions">
    <?php if ($isSuperAdmin): ?>
      <div class="user-profile-badge" title="Administrator">
        <div class="user-avatar" style="background: #650b2b;">
          A
        </div>
        <div style="font-size: 13px; font-weight: 700; color: #0f172a; padding-right: 4px;">
          Admin
        </div>
      </div>
    <?php else: ?>
      <div class="user-profile-badge" title="Station &amp; Role">
        <?php if (!empty($locationCode)): ?>
          <span style="font-family: monospace; font-size: 11px; font-weight: 700; padding: 2px 7px; border-radius: 10px; background: #e0f2fe; color: #0284c7; border: 1px solid #bae6fd; letter-spacing: 0.5px;">
            <?= htmlspecialchars($locationCode) ?>
          </span>
        <?php endif; ?>
        <div style="font-size: 13px; font-weight: 600; color: #0f172a; padding-right: 4px;">
          <?= htmlspecialchars($roleDisplay) ?>
        </div>
      </div>
    <?php endif; ?>

    <a href="<?= BASE_PATH ?>/logout.php" class="desk-btn desk-btn-default desk-btn-sm" title="Sign Out">
      Logout
    </a>
  </div>
</header>
