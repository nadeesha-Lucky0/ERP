<?php
use App\Core\Auth;
use App\Helpers\Icon;
$currentUser = Auth::user();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= APP_SHORT_NAME ?> | <?= APP_SYSTEM_NAME ?> - Enterprise FIFO Portal</title>
  <meta name="csrf-token" content="<?= htmlspecialchars(\App\Core\Session::csrfToken()) ?>">
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
      <span class="desk-brand-badge">Desk v16</span>
    </div>
  </a>

  <!-- Awesome Search Bar -->
  <div class="desk-search-container">
    <span class="desk-search-icon"><?= Icon::get('search', 14) ?></span>
    <input type="text" id="globalSearchInput" class="desk-search-input" placeholder="Search materials, batches, GRN, issues, CPC fuel, reports... (Ctrl+K)" autocomplete="off">
    <div id="searchDropdown" class="search-dropdown"></div>
  </div>

  <!-- Navbar Actions -->
  <div class="desk-navbar-actions">
    <div class="location-selector" title="Active Power Station">
      <span style="display: flex; align-items: center; color: var(--primary);"><?= Icon::get('zap', 14) ?></span>
      <span>Victoria Hydro (VIC-01)</span>
    </div>

    <div class="user-profile-badge" title="Logged in user">
      <div class="user-avatar" style="background: <?= htmlspecialchars($currentUser['avatar_color'] ?? '#0d9488') ?>;">
        <?= strtoupper(substr($currentUser['full_name'] ?? 'A', 0, 1)) ?>
      </div>
      <div style="font-size: 13px; font-weight: 600;">
        <?= htmlspecialchars($currentUser['full_name'] ?? 'Administrator') ?>
      </div>
    </div>

    <a href="<?= BASE_PATH ?>/logout.php" class="desk-btn desk-btn-default desk-btn-sm" title="Sign Out">
      Logout
    </a>
  </div>
</header>
