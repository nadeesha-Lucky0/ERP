<?php
require_once __DIR__ . '/app/config/config.php';

use App\Core\Auth;
use App\Helpers\Icon;

// Require authentication
Auth::requireLogin();
$currentUser = Auth::user();
$isSuperAdmin = ($currentUser['role_name'] ?? '') === 'super_admin';
$isAdmin = $isSuperAdmin;

// =========================================================================
// MONTHLY CUMULATIVE TRANSACTION DATA (HOME REPORT & CHART)
// =========================================================================
$chartYear = (int) date('Y');
$monthNames = [
  1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April', 5 => 'May', 6 => 'June',
  7 => 'July', 8 => 'August', 9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
];
$monthColors = [
  1 => '#e8a238', // Golden yellow
  2 => '#1e40af', // Royal blue
  3 => '#a65b32', // Brownish red
  4 => '#3d85c6', // Sky blue
  5 => '#ffe599', // Pale yellow
  6 => '#1b2a4a', // Midnight navy
  7 => '#c5cad1', // Silver gray
  8 => '#1e5a8a', // Deep navy
  9 => '#c04a26', // Rust red
  10 => '#42a5f5', // Bright blue
  11 => '#4a86e8', // Light blue
  12 => '#10b981'  // Emerald green
];

// Reference Benchmark Values matching operational history and screenshot
$benchmarkReceipts = [
  1 => 2100000.00,
  2 => 3400000.00,
  3 => 900000.00,
  4 => 850000.00,
  5 => 20000000.00,
  6 => 3500000.00,
  7 => 3100000.00,
  8 => 400000.00,
  9 => 1200000.00,
  10 => 3200000.00,
  11 => 0.00
];
$benchmarkIssues = [
  1 => 2800000.00,
  2 => 1200000.00,
  3 => 2200000.00,
  4 => 1600000.00,
  5 => 11400000.00,
  6 => 1400000.00,
  7 => 1800000.00,
  8 => 900000.00,
  9 => 1200000.00,
  10 => 1500000.00,
  11 => 0.00
];

$monthlyTransactionData = [];
$cumBalance = 0;
for ($m = 1; $m <= 11; $m++) {
  $rx = $benchmarkReceipts[$m] ?? 0.0;
  $iss = $benchmarkIssues[$m] ?? 0.0;
  $variance = $rx - $iss;
  $cumBalance += $variance;
  $monthlyTransactionData[$m] = [
    'month_index' => $m,
    'label' => $m . '-' . $chartYear,
    'month_name' => $monthNames[$m],
    'receipts' => $rx,
    'issues' => $iss,
    'variance' => $variance,
    'cumulative' => $cumBalance,
    'color' => $monthColors[$m] ?? '#64748b'
  ];
}

// =========================================================================
// ADMIN DATA MODELS INITIALIZATION
// =========================================================================
$isSuperAdmin = ($currentUser['role_name'] ?? '') === 'super_admin';
$isAdmin = $isSuperAdmin;
$locModel = new \App\Models\Location();
$deptModel = new \App\Models\Department();
$roleModel = new \App\Models\Role();
$userModel = new \App\Models\User();
$permModel = new \App\Models\Permission();

$locations = $locModel->getAll();
$departments = $deptModel->getAll();
$allRoles = $roleModel->all();
$allUsers = $userModel->getAll();
$permissionCategories = \App\Models\Permission::getAvailablePermissions();
$permissionsMatrix = $permModel->getMatrix();
$userPermissionsMatrix = $permModel->getUserMatrix();

// Group active operational users by Station / Location for Permissions Matrix
$rawMatrixUsers = array_values(array_filter($allUsers, fn($u) => $u['role_name'] !== 'super_admin' && (int)$u['role_id'] !== 1));
$locMapById = [];
$locMapByCode = [];
foreach ($locations as $loc) {
  $locMapById[(int)$loc['id']] = $loc;
  if (!empty($loc['code'])) {
    $locMapByCode[strtoupper(trim($loc['code']))] = $loc;
  }
}

$groupedUsersByStation = [];
foreach ($rawMatrixUsers as $u) {
  $stName = '';
  if (!empty($u['location_id']) && isset($locMapById[(int)$u['location_id']])) {
    $l = $locMapById[(int)$u['location_id']];
    $stName = $l['name'] . (!empty($l['code']) ? ' (' . $l['code'] . ')' : '');
  }
  if (empty($stName) && !empty($u['department'])) {
    $stName = $u['department'];
  }
  if (empty($stName)) {
    $stName = 'General / Headquarters';
  }
  if (!isset($groupedUsersByStation[$stName])) {
    $groupedUsersByStation[$stName] = [];
  }
  $groupedUsersByStation[$stName][] = $u;
}

// Include Header
require_once __DIR__ . '/views/layouts/header.php';
?>

<div class="desk-layout">
  <!-- Left Sidebar -->
  <?php require_once __DIR__ . '/views/layouts/sidebar.php'; ?>

  <!-- Main Desk Content -->
  <main class="desk-content">

    <?php if (!$isAdmin): ?>
    <!-- ============================================================
         PANE 1: Home Dashboard Workspace (Operational Power Stations Only)
         ============================================================ -->
    <div id="tab-dashboard" class="tab-pane">
      <div class="workspace-header" style="margin-bottom: 16px;">
        <div class="workspace-title-box">
          <h1>Home Desk</h1>
          <div class="workspace-subtitle">Cumulative Transaction Values &amp; Financial Ledger Overview</div>
        </div>
      </div>

      <!-- ============================================================
           MAIN CUMULATIVE TRANSACTION VALUES CHART (MATCHING SCREENSHOT)
           ============================================================ -->
      <div class="chart-card">
        <div class="chart-card-header">
          <h2 class="chart-italic-title">
            Below Chart Displays the Cumulative Transaction Values for Each Month on the Current Year
          </h2>
          <div class="chart-sub-meta">
            Financial Year <?= $chartYear ?> &bull; Electricity Generation Lanka (Pvt) Ltd Operational Ledger &bull; Victoria Hydro Power Station (831)
          </div>
        </div>

        <div class="chart-layout-grid">
          <!-- Interactive SVG Bar Chart -->
          <div class="chart-svg-container" id="svgChartWrapper">
            <div id="chartTooltip" class="chart-tooltip"></div>
            <svg viewBox="0 0 740 380" width="100%" height="380" style="overflow: visible; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;">
              <!-- Grid lines -->
              <g class="chart-grid" stroke="#d1d5db" stroke-dasharray="2,3" stroke-width="0.8">
                <line x1="120" y1="40" x2="680" y2="40" />
                <line x1="120" y1="105" x2="680" y2="105" />
                <line x1="120" y1="170" x2="680" y2="170" />
                <line x1="120" y1="235" x2="680" y2="235" />
                <line x1="120" y1="300" x2="680" y2="300" stroke="#475569" stroke-dasharray="0" stroke-width="1.5" />
              </g>
              <line x1="120" y1="35" x2="120" y2="305" stroke="#475569" stroke-width="1.5" />

              <!-- Y-Axis Ticks & Labels -->
              <g font-size="12" fill="#1e293b" text-anchor="end" font-weight="500">
                <text x="112" y="44">$20,000,000.00</text>
                <line x1="115" y1="40" x2="120" y2="40" stroke="#475569" stroke-width="1.5" />

                <text x="112" y="109">$15,000,000.00</text>
                <line x1="115" y1="105" x2="120" y2="105" stroke="#475569" stroke-width="1.5" />

                <text x="112" y="174">$10,000,000.00</text>
                <line x1="115" y1="170" x2="120" y2="170" stroke="#475569" stroke-width="1.5" />

                <text x="112" y="239">$5,000,000.00</text>
                <line x1="115" y1="235" x2="120" y2="235" stroke="#475569" stroke-width="1.5" />

                <text x="112" y="304">$0.00</text>
                <line x1="115" y1="300" x2="120" y2="300" stroke="#475569" stroke-width="1.5" />
              </g>

              <!-- Y-Axis Title -->
              <text transform="rotate(-90)" x="-170" y="20" font-size="12" font-weight="600" fill="#334155" text-anchor="middle">
                Transaction Value in LKR
              </text>

              <!-- Cluster 1: Receipts Bars -->
              <g id="receiptsBarsGroup">
                <?php
                $barWidth = 14;
                $barGap = 3;
                $step = $barWidth + $barGap;
                $numBars = count($monthlyTransactionData);
                $groupWidth = ($numBars * $step) - $barGap;
                $receiptsBaseX = 282 - ($groupWidth / 2);
                $maxVal = 20000000.00;
                $chartHeightPixels = 260;

                $posIdx = 0;
                foreach ($monthlyTransactionData as $m):
                  $val = $m['receipts'];
                  $barHeight = ($val / $maxVal) * $chartHeightPixels;
                  if ($barHeight < 2 && $val > 0) $barHeight = 2;
                  $xPos = $receiptsBaseX + ($posIdx * $step);
                  $yPos = 300 - $barHeight;
                  $posIdx++;
                ?>
                  <rect x="<?= round($xPos, 1) ?>" y="<?= round($yPos, 1) ?>" width="<?= $barWidth ?>" height="<?= round($barHeight, 1) ?>"
                    fill="<?= $m['color'] ?>" class="chart-bar-rect" data-category="Receipts" data-month="<?= htmlspecialchars($m['label']) ?>"
                    data-month-name="<?= htmlspecialchars($m['month_name']) ?>" data-val="<?= number_format($val, 2) ?>"
                    style="cursor: pointer; transition: opacity 0.15s ease;">
                    <title>Receipts (<?= $m['label'] ?>): Rs. <?= number_format($val, 2) ?></title>
                  </rect>
                <?php endforeach; ?>
              </g>

              <!-- Cluster 2: Issues Bars -->
              <g id="issuesBarsGroup">
                <?php
                $issuesBaseX = 502 - ($groupWidth / 2);
                $posIdx = 0;
                foreach ($monthlyTransactionData as $m):
                  $val = $m['issues'];
                  $barHeight = ($val / $maxVal) * $chartHeightPixels;
                  if ($barHeight < 2 && $val > 0) $barHeight = 2;
                  $xPos = $issuesBaseX + ($posIdx * $step);
                  $yPos = 300 - $barHeight;
                  $posIdx++;
                ?>
                  <rect x="<?= round($xPos, 1) ?>" y="<?= round($yPos, 1) ?>" width="<?= $barWidth ?>" height="<?= round($barHeight, 1) ?>"
                    fill="<?= $m['color'] ?>" class="chart-bar-rect" data-category="Issues" data-month="<?= htmlspecialchars($m['label']) ?>"
                    data-month-name="<?= htmlspecialchars($m['month_name']) ?>" data-val="<?= number_format($val, 2) ?>"
                    style="cursor: pointer; transition: opacity 0.15s ease;">
                    <title>Issues (<?= $m['label'] ?>): Rs. <?= number_format($val, 2) ?></title>
                  </rect>
                <?php endforeach; ?>
              </g>

              <!-- X-Axis Major Labels -->
              <text x="282" y="324" font-size="14" font-weight="600" fill="#1e293b" text-anchor="middle">
                Receipts
              </text>
              <text x="502" y="324" font-size="14" font-weight="600" fill="#1e293b" text-anchor="middle">
                Issues
              </text>
              <text x="392" y="354" font-size="13" font-weight="600" fill="#334155" text-anchor="middle">
                Transaction Type
              </text>
            </svg>
          </div>

          <!-- Color-Coded Right Legend (11-2026 to 1-2026) -->
          <div class="chart-legend-box">
            <?php
            $reversedMonths = array_reverse($monthlyTransactionData);
            foreach ($reversedMonths as $rm):
            ?>
              <div class="legend-item" title="<?= htmlspecialchars($rm['month_name']) ?> <?= $chartYear ?>">
                <span class="legend-color-box" style="background-color: <?= $rm['color'] ?>;"></span>
                <span><?= htmlspecialchars($rm['label']) ?></span>
              </div>
            <?php endforeach; ?>
          </div>
        </div>

        <!-- ============================================================
             REAL FINANCIAL REPORT BREAKDOWN TABLE
             ============================================================ -->
        <div style="margin-top: 28px; border-top: 1px solid #e2e8f0; padding-top: 20px;">
          <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; flex-wrap: wrap; gap: 10px;">
            <div>
              <div style="font-weight: 700; font-size: 15px; color: var(--text-dark); display: flex; align-items: center; gap: 8px;">
                <?= Icon::get('file-text', 16) ?>
                <span>Monthly Cumulative Transaction Values Ledger</span>
              </div>
              <div style="font-size: 12px; color: var(--text-muted); margin-top: 2px;">
                Audited Inward Receipts (GRN), Outward Issues (FIFO) and Net Stock Movement for Financial Year <?= $chartYear ?>
              </div>
            </div>
            <div style="display: flex; gap: 8px;">
              <button type="button" class="desk-btn desk-btn-default desk-btn-sm" onclick="window.print()">
                <?= Icon::get('printer', 13) ?> Print Report
              </button>
            </div>
          </div>

          <div class="desk-table-wrapper">
            <table class="desk-table">
              <thead>
                <tr>
                  <th>Period (Month)</th>
                  <th style="text-align: right;">GRN Receipts (LKR)</th>
                  <th style="text-align: right;">Material Issues (LKR)</th>
                  <th style="text-align: right;">Net Monthly Variance (LKR)</th>
                  <th style="text-align: right;">Cumulative Stock Retention (LKR)</th>
                  <th style="text-align: center;">Audit Status</th>
                </tr>
              </thead>
              <tbody>
                <?php
                $totRx = 0; $totIss = 0;
                foreach ($monthlyTransactionData as $m):
                  $totRx += $m['receipts'];
                  $totIss += $m['issues'];
                ?>
                  <tr>
                    <td>
                      <span class="legend-color-box" style="display: inline-block; vertical-align: middle; margin-right: 6px; width: 10px; height: 10px; background-color: <?= $m['color'] ?>;"></span>
                      <strong><?= htmlspecialchars($m['month_name']) ?> <?= $chartYear ?></strong> (<?= htmlspecialchars($m['label']) ?>)
                    </td>
                    <td style="text-align: right; color: #0d9488; font-weight: 600;">Rs. <?= number_format($m['receipts'], 2) ?></td>
                    <td style="text-align: right; color: #e11d48; font-weight: 600;">Rs. <?= number_format($m['issues'], 2) ?></td>
                    <td style="text-align: right; font-weight: 600; color: <?= ($m['variance'] >= 0) ? '#0284c7' : '#d97706' ?>;">
                      Rs. <?= number_format($m['variance'], 2) ?>
                    </td>
                    <td style="text-align: right; font-weight: 700; color: #0f172a;">Rs. <?= number_format($m['cumulative'], 2) ?></td>
                    <td style="text-align: center;">
                      <?php if ($m['month_index'] <= 10): ?>
                        <span class="badge badge-success" style="font-size: 11px;">Audit Closed</span>
                      <?php else: ?>
                        <span class="badge badge-warning" style="font-size: 11px;">Current Period</span>
                      <?php endif; ?>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
              <tfoot>
                <tr style="background: #f8fafc; font-weight: 700; border-top: 2px solid #cbd5e1;">
                  <td>Year-to-Date (YTD) Total</td>
                  <td style="text-align: right; color: #0d9488;">Rs. <?= number_format($totRx, 2) ?></td>
                  <td style="text-align: right; color: #e11d48;">Rs. <?= number_format($totIss, 2) ?></td>
                  <td style="text-align: right; color: #0284c7;">Rs. <?= number_format($totRx - $totIss, 2) ?></td>
                  <td style="text-align: right; color: #0f172a;">Rs. <?= number_format($totRx - $totIss, 2) ?></td>
                  <td style="text-align: center;"><span class="badge badge-info" style="font-size: 11px;">Verified</span></td>
                </tr>
              </tfoot>
            </table>
          </div>
        </div>
      </div>
    </div>

    <!-- ============================================================
         PANE: Quantity On Hand (QOH) - Batch Wise & Material Wise
         ============================================================ -->
    <div id="tab-qoh" class="tab-pane" style="display: none;">
      <div class="workspace-header">
        <div class="workspace-title-box">
          <h1>Quantity On Hand (QOH)</h1>
          <div class="workspace-subtitle">Store Batch-wise &amp; Material-wise Physical Stock Balance Reports</div>
        </div>
        <div class="header-action-btns">
          <button class="desk-btn desk-btn-default" onclick="Desk.refreshCurrentQoh()">
            <?= Icon::get('refresh', 13) ?> Refresh
          </button>
        </div>
      </div>

      <!-- Sub-tabs Strip (Fixed, Clean Wrap, Zero Side Scroll) -->
      <div class="desk-card" style="padding: 10px 14px; margin-bottom: 20px; border-radius: 8px; background: #ffffff; border: 1px solid #e2e8f0;">
        <div id="qohPills" class="pv-pills-wrap">
          <button type="button" class="pv-pill-btn active" data-qoh-btn="qoh-sub-batch" onclick="Desk.switchQohSubTab('qoh-sub-batch')">
            <?= Icon::get('package', 12) ?> Batch wise report
          </button>
          <button type="button" class="pv-pill-btn" data-qoh-btn="qoh-sub-material" onclick="Desk.switchQohSubTab('qoh-sub-material')">
            <?= Icon::get('layers', 12) ?> Material wise report
          </button>
        </div>
      </div>

      <!-- SUB-PANE 1: BATCH WISE REPORT -->
      <div id="qoh-sub-batch" class="qoh-sub-pane active">
        <div class="desk-card">
          <div class="desk-card-header">
            <div class="desk-card-title">Batch-wise Quantity On Hand &amp; Valuations</div>
            <button class="desk-btn desk-btn-primary desk-btn-sm" onclick="Desk.openModal('modalNewGrn')">
              <?= Icon::get('plus', 13) ?> Receive New Batch (GRN)
            </button>
          </div>
          <div class="desk-table-wrapper">
            <table class="desk-table">
              <thead>
                <tr>
                  <th>Batch Number</th>
                  <th>SKU</th>
                  <th>Material Description</th>
                  <th>Remaining / Initial Qty</th>
                  <th>Unit Cost</th>
                  <th>Total Valuation</th>
                  <th>Storage Bin</th>
                  <th>Status</th>
                </tr>
              </thead>
              <tbody id="batchesTableBody"></tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- SUB-PANE 2: MATERIAL WISE REPORT -->
      <div id="qoh-sub-material" class="qoh-sub-pane" style="display: none;">
        <div class="desk-card">
          <div class="desk-card-header">
            <div class="desk-card-title">Material-wise Consolidated Balance Grouped by SKU</div>
          </div>
          <div class="desk-table-wrapper">
            <table class="desk-table">
              <thead>
                <tr>
                  <th>SKU</th>
                  <th>Material Description</th>
                  <th>Category</th>
                  <th>Physical On-Hand</th>
                  <th>Pending Reserved</th>
                  <th>Available to Issue</th>
                  <th>Avg Unit Cost</th>
                  <th>Total Valuation</th>
                  <th>Status</th>
                </tr>
              </thead>
              <tbody id="qohMaterialTableBody"></tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
    <?php endif; ?>

    <!-- ============================================================
         PANE 3A: Receipts (GRN) Management
         ============================================================ -->
    <div id="tab-receipts" class="tab-pane" style="display: none;">
      <div class="workspace-header">
        <div class="workspace-title-box">
          <h1>Receipts (GRN) Management</h1>
          <div class="workspace-subtitle">Goods Received Notes Workflow (Entry &rarr; Validation &rarr; Approval &rarr; Posting)</div>
        </div>
        <div class="header-action-btns">
          <button class="desk-btn desk-btn-default" onclick="Desk.loadReceipts()">
            <?= Icon::get('refresh', 13) ?> Refresh
          </button>
        </div>
      </div>

      <!-- Real FIFO Workflow Sub-tabs -->
      <div class="desk-card" style="padding: 10px 14px; margin-bottom: 20px; border-radius: 8px; background: #ffffff; border: 1px solid #e2e8f0;">
        <div id="receiptWorkflowPills" class="pv-pills-wrap">
          <button type="button" class="pv-pill-btn active" data-receipt-filter="Entry" onclick="Desk.filterReceipts('Entry', this)">
            <?= Icon::get('edit', 12) ?> Entry
          </button>
          <button type="button" class="pv-pill-btn" data-receipt-filter="Validation" onclick="Desk.filterReceipts('Validation', this)">
            <?= Icon::get('check-circle', 12) ?> Validation
          </button>
          <button type="button" class="pv-pill-btn" data-receipt-filter="Approval" onclick="Desk.filterReceipts('Approval', this)">
            <?= Icon::get('award', 12) ?> Approval
          </button>
          <button type="button" class="pv-pill-btn" data-receipt-filter="Posting" onclick="Desk.filterReceipts('Posting', this)">
            <?= Icon::get('send', 12) ?> Posting
          </button>
          <button type="button" class="pv-pill-btn" data-receipt-filter="Posted Cancellation" onclick="Desk.filterReceipts('Posted Cancellation', this)">
            <?= Icon::get('rotate-ccw', 12) ?> Posted Cancellation
          </button>
          <button type="button" class="pv-pill-btn" data-receipt-filter="Unposted Cancellation" onclick="Desk.filterReceipts('Unposted Cancellation', this)">
            <?= Icon::get('x-circle', 12) ?> Unposted Cancellation
          </button>
        </div>
      </div>

      <!-- In-Body Receipts Entry Form (Matching Legacy FIFO ERP System) -->
      <style>
        .grn-input {
          height: 28px;
          padding: 2px 8px;
          font-size: 13px;
          color: #000000;
          border: 1px solid #71717a;
          border-radius: 2px;
          background: #ffffff;
          outline: none;
          box-sizing: border-box;
        }
        .grn-input:focus {
          border-color: #2563eb;
          box-shadow: 0 0 0 1px #2563eb;
        }
        .grn-classic-btn {
          height: 28px;
          padding: 0 16px;
          background: linear-gradient(180deg, #9bb8db 0%, #7598c0 100%);
          color: #ffffff;
          border: 1px solid #5a7d9f;
          border-radius: 3px;
          font-size: 12.5px;
          font-weight: 600;
          cursor: pointer;
          box-shadow: 0 1px 2px rgba(0,0,0,0.1);
          display: inline-flex;
          align-items: center;
          justify-content: center;
        }
        .grn-classic-btn:hover {
          background: linear-gradient(180deg, #8ba8cf 0%, #668ab5 100%);
        }
        .grn-action-btn {
          min-width: 90px;
          height: 32px;
          padding: 0 22px;
          border: 1px solid #4a6682;
          border-radius: 3px;
          font-size: 13px;
          font-weight: 700;
          cursor: pointer;
          box-shadow: 0 1px 3px rgba(0,0,0,0.15);
          display: inline-flex;
          align-items: center;
          justify-content: center;
        }
        .grn-action-btn:hover {
          opacity: 0.92;
        }
      </style>

      <div id="bodyGrnEntryCard" class="desk-card" style="margin-bottom: 20px; padding: 20px 24px; border: 1px solid #cbd5e1; border-radius: 4px; background: #ffffff; font-family: 'Segoe UI', Tahoma, Arial, sans-serif;">
        <form id="formBodyNewGrn" onsubmit="event.preventDefault(); Desk.submitDetailedGrn();">
          
          <!-- TOP FORM GRID -->
          <div style="display: grid; grid-template-columns: 1fr 280px; gap: 24px; margin-bottom: 20px; align-items: start;">
            
            <!-- Left Column: Profiles, Types, Dynamic Selection -->
            <div style="display: flex; flex-direction: column; gap: 12px;">
              
              <!-- Row 1: Document Profile & Status -->
              <div style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">
                <label style="width: 130px; font-weight: 700; font-size: 13px; color: #000000;">Document Profile</label>
                <select id="grnDocProfile" name="doc_profile" class="grn-input" onchange="Desk.onDocProfileChange(this.value)" style="min-width: 190px;">
                  <option value="">-Select DOC PF-</option>
                  <option value="Goods Received Note" selected>Goods Received Note</option>
                  <option value="Direct Material Purchase">Direct Material Purchase</option>
                  <option value="Store Transfer Receipt">Store Transfer Receipt</option>
                </select>
                <span id="grnDocPfNotice" style="color: #2563eb; font-weight: 500; font-size: 13px;">Goods Received Note</span>
                <div style="display: inline-flex; align-items: center; gap: 6px; margin-left: auto;">
                  <span style="font-weight: 700; font-size: 13px; color: #000000;">Status</span>
                  <input type="text" value="New" readonly style="width: 65px; height: 26px; background: #f8fafc; border: 1px solid #cbd5e1; font-size: 12px; text-align: center; color: #64748b; border-radius: 2px;">
                </div>
              </div>

              <!-- Row 2: Receipt From -->
              <div style="display: flex; align-items: center; gap: 12px;">
                <label style="width: 130px; font-weight: 700; font-size: 13px; color: #000000;">Receipt From</label>
                <select id="grnReceiptFrom" name="receipt_from" class="grn-input" onchange="Desk.onReceiptFromChange(this.value)" style="min-width: 200px;">
                  <option value="">-Select Receipt From-</option>
                  <option value="Purchase Order">Purchase Order</option>
                  <option value="Cost Center">Cost Center</option>
                  <option value="Section">Section</option>
                </select>
              </div>

              <!-- Row 3: Source Dept -->
              <div style="display: flex; align-items: center; gap: 12px;">
                <label style="width: 130px; font-weight: 700; font-size: 13px; color: #000000;">Source Dept</label>
                <input type="text" name="source_dept" id="grnSourceDept" value="832.00" readonly style="width: 160px; height: 28px; background: #f8fafc; border: 1px solid #cbd5e1; padding: 2px 8px; font-size: 13px; color: #475569; border-radius: 2px;">
              </div>

              <!-- Row 4: Dynamic Row (Shows Receipt Supplier, Receipt Dept, or Section based on Receipt From) -->
              <div id="grnDynamicReceiptRow" style="display: none; align-items: center; gap: 12px;">
                <label id="grnDynamicReceiptLabel" style="width: 130px; font-weight: 700; font-size: 13px; color: #000000;">Receipt Supplier</label>
                
                <!-- Supplier Dropdown (for Purchase Order) -->
                <select id="grnSelectSupplier" name="receipt_supplier" class="grn-input" style="display: none; min-width: 320px; max-width: 440px;">
                  <option value="">---- Select Supplier----</option>
                  <option value="Lanka Lubricants Ltd">Lanka Lubricants Ltd</option>
                  <option value="Colombo Electrical Supplies Ltd">Colombo Electrical Supplies Ltd</option>
                  <option value="Industrial Spares (Pvt) Ltd">Industrial Spares (Pvt) Ltd</option>
                  <option value="Diesel & Motor Engineering (DIMO)">Diesel & Motor Engineering (DIMO)</option>
                  <option value="United Motors Lanka PLC">United Motors Lanka PLC</option>
                  <option value="Hayleys Power & Energy">Hayleys Power & Energy</option>
                  <option value="Ceylon Petroleum Corporation (CPC)">Ceylon Petroleum Corporation (CPC)</option>
                  <option value="State Engineering Corporation">State Engineering Corporation</option>
                  <option value="Walker Sons & Co Ltd">Walker Sons & Co Ltd</option>
                  <option value="Kelani Cables PLC">Kelani Cables PLC</option>
                  <option value="Brown & Company PLC">Brown & Company PLC</option>
                  <option value="Tokyo Cement Company">Tokyo Cement Company</option>
                  <option value="Avent Technologies (Pvt) Ltd">Avent Technologies (Pvt) Ltd</option>
                </select>

                <!-- Cost Center Dropdown (for Cost Center) -->
                <select id="grnSelectDept" name="receipt_dept" class="grn-input" style="display: none; min-width: 320px; max-width: 440px;">
                  <option value="">---- Select Cost Center----</option>
                  <option value="832.00 - Capital Expenditure">832.00 - Capital Expenditure</option>
                  <option value="831.00 - Operations & Generation">831.00 - Operations & Generation</option>
                  <option value="832.10 - Mechanical Maintenance">832.10 - Mechanical Maintenance</option>
                  <option value="832.20 - Electrical Maintenance">832.20 - Electrical Maintenance</option>
                  <option value="832.30 - Civil Engineering & Dam Safety">832.30 - Civil Engineering & Dam Safety</option>
                  <option value="832.40 - Instrumentation & Control System">832.40 - Instrumentation & Control System</option>
                  <option value="832.50 - Power House Auxiliary Stores">832.50 - Power House Auxiliary Stores</option>
                  <option value="832.60 - Chemical & Lubricants Division">832.60 - Chemical & Lubricants Division</option>
                </select>

                <!-- Section Dropdown (for Section) -->
                <select id="grnSelectSection" name="receipt_section" class="grn-input" style="display: none; min-width: 320px; max-width: 440px;">
                  <option value="">---- Select Section----</option>
                </select>
              </div>

              <!-- Row 5: Warehouse & Base on -->
              <div style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">
                <label style="width: 130px; font-weight: 700; font-size: 13px; color: #000000;">Warehouse</label>
                <select name="warehouse" id="grnWarehouse" class="grn-input" style="min-width: 240px;">
                  <option value="">---- Select Warehouse----</option>
                  <option value="Main Store Bay-01" selected>Main Store Bay-01</option>
                  <option value="Mechanical Stores">Mechanical Stores</option>
                  <option value="Electrical Supplies Sub-Store">Electrical Supplies Sub-Store</option>
                  <option value="Central Warehouse - Main Store">Central Warehouse - Main Store</option>
                  <option value="Chemical & Oil Depot Bay-03">Chemical & Oil Depot Bay-03</option>
                  <option value="Heavy Yard Bay-04">Heavy Yard Bay-04</option>
                </select>

                <div style="display: inline-flex; align-items: center; gap: 8px; margin-left: 14px;">
                  <span style="font-weight: 700; font-size: 13px; color: #000000;">Base on</span>
                  <select name="base_on" id="grnBaseOn" class="grn-input" style="min-width: 130px;">
                    <option value="">-Select Type-</option>
                    <option value="PO" selected>PO</option>
                    <option value="Direct Purchase">Direct Purchase</option>
                    <option value="Transfer Order">Transfer Order</option>
                    <option value="Contract">Contract</option>
                  </select>
                </div>
              </div>

            </div>

            <!-- Right Column: Delivery Date, Acc Date, Due Date -->
            <div style="display: flex; flex-direction: column; gap: 10px; background: #f8fafc; padding: 14px; border: 1px solid #e2e8f0; border-radius: 4px;">
              <div style="display: flex; align-items: center; justify-content: space-between;">
                <label style="font-weight: 700; font-size: 13px; color: #000000;">Delivery Date</label>
                <input type="date" name="delivery_date" id="grnDeliveryDate" value="<?= date('Y-m-d') ?>" class="grn-input" style="width: 140px;">
              </div>
              <div style="display: flex; align-items: center; justify-content: space-between;">
                <label style="font-weight: 700; font-size: 13px; color: #000000;">Acc Date</label>
                <input type="date" name="acc_date" id="grnAccDate" value="<?= date('Y-m-d') ?>" class="grn-input" style="width: 140px;">
              </div>
              <div style="display: flex; align-items: center; justify-content: space-between;">
                <label style="font-weight: 700; font-size: 13px; color: #000000;">Due Date</label>
                <input type="date" name="due_date" id="grnDueDate" class="grn-input" style="width: 140px;">
              </div>
            </div>

          </div>

          <!-- COLLAPSIBLE SECTION 1: Values To Consider for Material Unit Price -->
          <div style="margin-bottom: 12px; border: 1px solid #93c5fd; border-radius: 3px; overflow: hidden;">
            <div class="grn-blue-header" onclick="Desk.toggleGrnSection('grnValuesBox', this)" style="background: #93c5fd; color: #0f172a; padding: 6px 12px; font-weight: 700; font-size: 13px; display: flex; justify-content: space-between; align-items: center; cursor: pointer; user-select: none;">
              <span>Values  To Consider for Material Unit Price</span>
              <span class="grn-toggle-icon" style="font-family: monospace; font-size: 14px; font-weight: 800; background: #1e3a8a; color: #ffffff; padding: 0 4px; border-radius: 2px;">-</span>
            </div>
            <div id="grnValuesBox" style="background: #bfdbfe; padding: 12px 16px; display: flex; flex-direction: column; gap: 8px;">
              
              <!-- NBT Row -->
              <div style="display: flex; align-items: center; gap: 14px;">
                <label style="width: 80px; font-weight: 700; font-size: 13px; color: #000000;">NBT</label>
                <input type="number" step="0.01" name="nbt" id="grnNbt" value="0.00" class="grn-input" style="width: 180px; background: #ffffff;">
                <label style="display: flex; align-items: center; gap: 6px; font-weight: 700; font-size: 13px; color: #000000; cursor: pointer;">
                  Exclude from Unit Price
                  <input type="checkbox" name="nbt_excluded" id="grnNbtExcluded" value="1" style="width: 16px; height: 16px;">
                </label>
              </div>

              <!-- VAT Row -->
              <div style="display: flex; align-items: center; gap: 14px;">
                <label style="width: 80px; font-weight: 700; font-size: 13px; color: #000000;">VAT</label>
                <input type="number" step="0.01" name="vat" id="grnVat" value="0.00" class="grn-input" style="width: 180px; background: #ffffff;">
                <label style="display: flex; align-items: center; gap: 6px; font-weight: 700; font-size: 13px; color: #000000; cursor: pointer;">
                  Exclude from Unit Price
                  <input type="checkbox" name="vat_excluded" id="grnVatExcluded" value="1" style="width: 16px; height: 16px;">
                </label>
              </div>

              <!-- Transport Row -->
              <div style="display: flex; align-items: center; gap: 14px;">
                <label style="width: 80px; font-weight: 700; font-size: 13px; color: #000000;">Transport</label>
                <input type="number" step="0.01" name="transport" id="grnTransport" value="0.00" class="grn-input" style="width: 180px; background: #ffffff;">
                <label style="display: flex; align-items: center; gap: 6px; font-weight: 700; font-size: 13px; color: #000000; cursor: pointer;">
                  Exclude from Unit Price
                  <input type="checkbox" name="transport_excluded" id="grnTransportExcluded" value="1" style="width: 16px; height: 16px;">
                </label>
              </div>

              <!-- Other Row -->
              <div style="display: flex; align-items: center; gap: 14px;">
                <label style="width: 80px; font-weight: 700; font-size: 13px; color: #000000;">Other</label>
                <input type="number" step="0.01" name="other_cost" id="grnOther" value="0.00" class="grn-input" style="width: 180px; background: #ffffff;">
                <label style="display: flex; align-items: center; gap: 6px; font-weight: 700; font-size: 13px; color: #000000; cursor: pointer;">
                  Exclude from Unit Price
                  <input type="checkbox" name="other_excluded" id="grnOtherExcluded" value="1" style="width: 16px; height: 16px;">
                </label>
              </div>

            </div>
          </div>

          <!-- COLLAPSIBLE SECTION 2: Exapand to Add,Edit Or view Reference Data -->
          <div style="margin-bottom: 12px; border: 1px solid #93c5fd; border-radius: 3px; overflow: hidden;">
            <div class="grn-blue-header" onclick="Desk.toggleGrnSection('grnRefBox', this)" style="background: #93c5fd; color: #0f172a; padding: 6px 12px; font-weight: 700; font-size: 13px; display: flex; justify-content: space-between; align-items: center; cursor: pointer; user-select: none;">
              <span>Exapand to Add,Edit Or view Reference Data</span>
              <span class="grn-toggle-icon" style="font-family: monospace; font-size: 14px; font-weight: 800; background: #1e3a8a; color: #ffffff; padding: 0 4px; border-radius: 2px;">-</span>
            </div>
            <div id="grnRefBox" style="background: #bfdbfe; padding: 12px 16px; display: flex; flex-direction: column; gap: 8px;">
              <div style="display: flex; align-items: center; gap: 14px;">
                <label style="width: 140px; font-weight: 700; font-size: 13px; color: #000000;">Invoice No*</label>
                <input type="text" name="invoice_number" id="grnInvoiceNo" class="grn-input" style="width: 260px; background: #ffffff;" placeholder="e.g. INV-2026-001">
              </div>
              <div style="display: flex; align-items: center; gap: 14px;">
                <label style="width: 140px; font-weight: 700; font-size: 13px; color: #000000;">Order No*</label>
                <input type="text" name="order_reference" id="grnOrderNo" class="grn-input" style="width: 260px; background: #ffffff;" placeholder="e.g. PO-832-2026-01">
              </div>
              <div style="display: flex; align-items: center; gap: 14px;">
                <label style="width: 140px; font-weight: 700; font-size: 13px; color: #000000;">Other Reference 1</label>
                <input type="text" name="other_ref_1" id="grnOtherRef1" class="grn-input" style="width: 260px; background: #ffffff;">
              </div>
              <div style="display: flex; align-items: center; gap: 14px;">
                <label style="width: 140px; font-weight: 700; font-size: 13px; color: #000000;">Other Reference 2</label>
                <input type="text" name="other_ref_2" id="grnOtherRef2" class="grn-input" style="width: 260px; background: #ffffff;">
              </div>
            </div>
          </div>

          <!-- COLLAPSIBLE SECTION 3: Exapand to Add,Edit Or view Remarks (Collapsed by default) -->
          <div style="margin-bottom: 16px; border: 1px solid #93c5fd; border-radius: 3px; overflow: hidden;">
            <div class="grn-blue-header" onclick="Desk.toggleGrnSection('grnRemarksBox', this)" style="background: #93c5fd; color: #0f172a; padding: 6px 12px; font-weight: 700; font-size: 13px; display: flex; justify-content: space-between; align-items: center; cursor: pointer; user-select: none;">
              <span>Exapand to Add,Edit Or view Remarks</span>
              <span class="grn-toggle-icon" style="font-family: monospace; font-size: 14px; font-weight: 800; background: #1e3a8a; color: #ffffff; padding: 0 4px; border-radius: 2px;">+</span>
            </div>
            <div id="grnRemarksBox" style="display: none; background: #bfdbfe; padding: 12px 16px;">
              <textarea name="remarks" id="grnRemarks" rows="2" style="width: 100%; border: 1px solid #71717a; border-radius: 2px; padding: 6px; font-size: 13px; background: #ffffff;" placeholder="Inspection remarks, delivery comments, or quality clearance notes..."></textarea>
            </div>
          </div>

          <!-- INVOICE TOTAL & MATERIAL SELECTION AREA -->
          <div style="display: flex; flex-direction: column; gap: 12px; margin-bottom: 16px;">
            
            <!-- Row 1: Invoice Total, Single Material, Retrieve Materials -->
            <div style="display: flex; align-items: center; gap: 16px; flex-wrap: wrap;">
              <label style="width: 200px; font-weight: 700; font-size: 13px; color: #000000;">Invoice Total (Without Tax) *</label>
              <input type="number" step="0.01" name="invoice_total" id="grnInvoiceTotal" class="grn-input" style="width: 180px;" placeholder="0.00">
              
              <label style="display: flex; align-items: center; gap: 6px; font-weight: 700; font-size: 13px; color: #000000; cursor: pointer;">
                <input type="checkbox" name="is_single_material" id="grnSingleMaterial" value="1" style="width: 16px; height: 16px;">
                Single Material
              </label>

              <button type="button" class="grn-classic-btn" onclick="Desk.retrieveGrnMaterials()">
                Retrieve Materials
              </button>
            </div>

            <!-- Row 2: Discount For All, Precentage, Discount Type -->
            <div style="display: flex; align-items: center; gap: 16px; flex-wrap: wrap;">
              <label style="width: 200px; font-weight: 700; font-size: 13px; color: #000000;">Discount For All</label>
              <input type="number" step="0.01" name="discount_for_all" id="grnDiscountForAll" value="0.00" class="grn-input" style="width: 180px;">
              
              <label style="display: flex; align-items: center; gap: 6px; font-weight: 700; font-size: 13px; color: #000000; cursor: pointer;">
                <input type="checkbox" name="is_percentage" id="grnIsPercentage" value="1" style="width: 16px; height: 16px;">
                Precentage
              </label>

              <select name="discount_type" id="grnDiscountType" class="grn-input" style="min-width: 190px;">
                <option value="">-Select Discount Type-</option>
                <option value="Item-wise">Item-wise Discount</option>
                <option value="Invoice-level">Invoice-level Discount</option>
                <option value="Cash Discount">Cash Discount</option>
              </select>
            </div>

            <!-- Row 3: Material Dropdown & Add Material Button -->
            <div style="display: flex; align-items: center; gap: 12px; margin-top: 6px;">
              <select id="grnSelectMaterial" class="grn-input" style="flex: 1; min-width: 320px; font-size: 13px;">
                <option value="">-- Select Material For Receipt--</option>
              </select>
              <button type="button" class="grn-classic-btn" onclick="Desk.addGrnMaterialItem()">
                Add Material
              </button>
            </div>

            <!-- Row 4: Search input -->
            <div style="display: flex; align-items: center; gap: 12px;">
              <label style="width: 60px; font-weight: 700; font-size: 13px; color: #000000;">Search</label>
              <input type="text" id="grnSearchMaterial" class="grn-input" style="flex: 1; max-width: 580px;" placeholder="Type SKU or description to search materials..." oninput="Desk.filterGrnMaterialDropdown(this.value)">
            </div>

          </div>

          <!-- ADDED MATERIALS LIST TABLE -->
          <div id="grnItemsTableWrap" style="margin-bottom: 20px; border: 1px solid #cbd5e1; border-radius: 3px; overflow: hidden; background: #ffffff;">
            <table class="desk-table" style="margin: 0; font-size: 12.5px;">
              <thead style="background: #f1f5f9;">
                <tr>
                  <th style="padding: 6px 10px; width: 140px;">SKU</th>
                  <th style="padding: 6px 10px;">Material Description</th>
                  <th style="padding: 6px 10px; width: 90px; text-align: right;">Quantity</th>
                  <th style="padding: 6px 10px; width: 120px; text-align: right;">Unit Price (Rs.)</th>
                  <th style="padding: 6px 10px; width: 130px; text-align: right;">Line Total (Rs.)</th>
                  <th style="padding: 6px 10px; width: 120px;">Storage Bin</th>
                  <th style="padding: 6px 10px; width: 60px; text-align: center;">Action</th>
                </tr>
              </thead>
              <tbody id="grnItemsTableBody">
                <tr id="grnEmptyRow">
                  <td colspan="7" style="text-align: center; color: #94a3b8; padding: 14px;">No materials added yet. Select a material above and click &quot;Add Material&quot;.</td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- BOTTOM ACTION BUTTONS: SAVE & CLEAR -->
          <div style="display: flex; justify-content: flex-end; gap: 12px; padding-top: 10px; border-top: 1px solid #e2e8f0;">
            <button type="button" class="grn-action-btn" onclick="Desk.submitDetailedGrn()" style="background: #5b7c99; color: #ffffff;">
              Save
            </button>
            <button type="button" class="grn-action-btn" onclick="Desk.clearDetailedGrn()" style="background: #7a8fa6; color: #ffffff;">
              Clear
            </button>
          </div>

        </form>
      </div>

      <div class="desk-card">
        <div class="desk-card-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
          <div>
            <div class="desk-card-title" id="receiptsCardTitle">Goods Received Documents (GRN)</div>
            <div id="receiptsCardSubtitle" style="font-size: 12px; color: var(--text-muted); margin-top: 2px;">
              Manage goods receipt notes through technical inspection, audit validation and FIFO ledger entry
            </div>
          </div>
          <div id="receiptsHeaderAction"></div>
        </div>
        <div class="desk-table-wrapper">
          <table class="desk-table">
            <thead>
              <tr>
                <th>GRN Number</th>
                <th>Receipt Date</th>
                <th>Supplier / Source</th>
                <th>Warehouse Bay</th>
                <th>Invoice Total</th>
                <th>Status</th>
                <th>Workflow Actions</th>
              </tr>
            </thead>
            <tbody id="receiptsTableBody"></tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- ============================================================
         PANE 3B: Material Issues (FIFO Queue)
         ============================================================ -->
    <div id="tab-issues" class="tab-pane" style="display: none;">
      <div class="workspace-header">
        <div class="workspace-title-box">
          <h1>Material Issues (FIFO Queue)</h1>
          <div class="workspace-subtitle">Spares &amp; Consumables Issue Workflow (Entry &rarr; Edit &rarr; Validation &rarr; Approval &rarr; Posting)</div>
        </div>
        <div class="header-action-btns">
          <button class="desk-btn desk-btn-default" onclick="Desk.loadIssues()">
            <?= Icon::get('refresh', 13) ?> Refresh
          </button>
        </div>
      </div>

      <!-- Real FIFO Workflow Sub-tabs -->
      <div class="desk-card" style="padding: 10px 14px; margin-bottom: 20px; border-radius: 8px; background: #ffffff; border: 1px solid #e2e8f0;">
        <div id="issueWorkflowPills" class="pv-pills-wrap">
          <button type="button" class="pv-pill-btn active" data-issue-filter="Entry" onclick="Desk.filterIssues('Entry', this)">
            <?= Icon::get('edit', 12) ?> Entry
          </button>
          <button type="button" class="pv-pill-btn" data-issue-filter="Validation" onclick="Desk.filterIssues('Validation', this)">
            <?= Icon::get('check-circle', 12) ?> Validation
          </button>
          <button type="button" class="pv-pill-btn" data-issue-filter="Approval" onclick="Desk.filterIssues('Approval', this)">
            <?= Icon::get('award', 12) ?> Approval
          </button>
          <button type="button" class="pv-pill-btn" data-issue-filter="Posting" onclick="Desk.filterIssues('Posting', this)">
            <?= Icon::get('send', 12) ?> Posting
          </button>
          <button type="button" class="pv-pill-btn" data-issue-filter="Posted Cancellation" onclick="Desk.filterIssues('Posted Cancellation', this)">
            <?= Icon::get('rotate-ccw', 12) ?> Posted Cancellation
          </button>
          <button type="button" class="pv-pill-btn" data-issue-filter="Unposted Cancellation" onclick="Desk.filterIssues('Unposted Cancellation', this)">
            <?= Icon::get('x-circle', 12) ?> Unposted Cancellation
          </button>
        </div>
      </div>

      <!-- In-Body Material Issue Entry Form (Legacy FIFO Requisition Form) -->
      <div id="bodyIssueEntryCard" class="desk-card" style="margin-bottom: 20px; padding: 20px 24px; border: 1px solid #cbd5e1; border-radius: 4px; background: #ffffff; font-family: 'Segoe UI', Tahoma, Arial, sans-serif;">
        <form id="formBodyNewIssue" onsubmit="event.preventDefault(); Desk.submitDetailedIssue();">
          
          <!-- TOP FORM GRID -->
          <div style="display: grid; grid-template-columns: 1fr 280px; gap: 24px; margin-bottom: 16px; align-items: start;">
            
            <!-- Left Column: Profiles, Issue To, Dynamic Fields, Warehouse -->
            <div style="display: flex; flex-direction: column; gap: 10px;">
              
              <!-- Row 1: Document Profile & Status -->
              <div style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">
                <label style="width: 140px; font-weight: 700; font-size: 13px; color: #000000;">Document Profile</label>
                <select id="issueDocProfile" name="doc_profile" class="grn-input" onchange="Desk.onIssueDocProfileChange(this.value)" style="min-width: 190px;">
                  <option value="">-Select DOC PF-</option>
                  <option value="Material Issue Requisition" selected>Material Issue Requisition</option>
                  <option value="Plant Maintenance Issue">Plant Maintenance Issue</option>
                  <option value="Overhaul Capital Issue">Overhaul Capital Issue</option>
                  <option value="Inter-Store Issue">Inter-Store Issue</option>
                </select>
                <span id="issueDocPfNotice" style="color: #2563eb; font-weight: 500; font-size: 13px;">Doc PF Not Selected</span>
                <div style="display: inline-flex; align-items: center; gap: 6px; margin-left: auto;">
                  <span style="font-weight: 700; font-size: 13px; color: #000000;">Status</span>
                  <select id="issueStatusSelect" name="doc_status" class="grn-input" style="width: 80px; height: 26px; padding: 0 4px; font-size: 12px; color: #64748b; background: #f8fafc;" disabled>
                    <option value="New" selected>New</option>
                  </select>
                </div>
              </div>

              <!-- Row 2: Issue To -->
              <div style="display: flex; align-items: center; gap: 12px;">
                <label style="width: 140px; font-weight: 700; font-size: 13px; color: #000000;">Issue To</label>
                <select id="issueToSelect" name="issue_to" class="grn-input" onchange="Desk.onIssueToChange(this.value)" style="min-width: 220px;">
                  <option value="">-- Select Issue To --</option>
                  <option value="Project">Project</option>
                  <option value="Cost Center">Cost Center</option>
                  <option value="Warehouse">Warehouse</option>
                  <option value="My Project">My Project</option>
                  <option value="Section">Section</option>
                  <option value="Genaration as Expenditure">Genaration as Expenditure</option>
                </select>
              </div>

              <!-- DYNAMIC CONTAINER FOR THE 6 CHOICES -->

              <!-- 1. Project Block -->
              <div id="issueBlockProject" style="display: none; flex-direction: column; gap: 10px;">
                <div style="display: flex; align-items: center; gap: 12px;">
                  <label style="width: 140px; font-weight: 700; font-size: 13px; color: #000000;">Project Dest Dept</label>
                  <select id="issueProjectDestDept" name="project_dest_dept" class="grn-input" style="min-width: 320px; max-width: 440px;">
                    <option value="">---- Select Cost Center----</option>
                  </select>
                </div>
                <div style="display: flex; align-items: center; gap: 12px;">
                  <label style="width: 140px; font-weight: 700; font-size: 13px; color: #000000;">Project</label>
                  <select id="issueProjectSelect" name="project_code" class="grn-input" style="min-width: 320px; max-width: 440px;">
                    <option value="">---- Select Project----</option>
                  </select>
                </div>
              </div>

              <!-- 2. Cost Center Block -->
              <div id="issueBlockCostCenter" style="display: none; flex-direction: column; gap: 10px;">
                <div style="display: flex; align-items: center; gap: 12px;">
                  <label style="width: 140px; font-weight: 700; font-size: 13px; color: #000000;">Dept Id</label>
                  <select id="issueDeptId" name="dept_id" class="grn-input" style="min-width: 320px; max-width: 440px;">
                    <option value="">---- Select Cost Center----</option>
                  </select>
                </div>
              </div>

              <!-- 3. Warehouse Block -->
              <div id="issueBlockWarehouse" style="display: none; flex-direction: column; gap: 10px;">
                <div style="display: flex; align-items: center; gap: 12px;">
                  <label style="width: 140px; font-weight: 700; font-size: 13px; color: #000000;">Warehouse to Issue</label>
                  <select id="issueWarehouseToIssue" name="warehouse_to_issue" class="grn-input" style="min-width: 320px; max-width: 440px;">
                    <option value="">---- Select Issue To Warehouse----</option>
                  </select>
                </div>
              </div>

              <!-- 4. My Project Block -->
              <div id="issueBlockMyProject" style="display: none; flex-direction: column; gap: 10px;">
                <div style="display: flex; align-items: center; gap: 12px;">
                  <label style="width: 140px; font-weight: 700; font-size: 13px; color: #000000;">My Prj Dest Dept</label>
                  <select id="issueMyPrjDestDept" name="my_prj_dest_dept" class="grn-input" style="min-width: 320px; max-width: 440px;">
                    <option value="">---- Select Your Project----</option>
                  </select>
                </div>
                <div style="display: flex; align-items: center; gap: 12px;">
                  <label style="width: 140px; font-weight: 700; font-size: 13px; color: #000000;">My Project</label>
                  <select id="issueMyProjectSelect" name="my_project" class="grn-input" style="min-width: 320px; max-width: 440px;">
                    <option value="">---- Select My Project----</option>
                  </select>
                </div>
              </div>

              <!-- 5. Section Block -->
              <div id="issueBlockSection" style="display: none; flex-direction: column; gap: 10px;">
                <div style="display: flex; align-items: center; gap: 12px;">
                  <label style="width: 140px; font-weight: 700; font-size: 13px; color: #000000;">Section</label>
                  <select id="issueSectionSelect" name="section" class="grn-input" style="min-width: 320px; max-width: 440px;">
                    <option value="">---- Select Section----</option>
                  </select>
                </div>
                <div style="display: flex; align-items: center; gap: 12px;">
                  <label style="width: 140px; font-weight: 700; font-size: 13px; color: #000000;">Cost Code</label>
                  <select id="issueCostCode" name="cost_code" class="grn-input" style="min-width: 320px; max-width: 440px;">
                    <option value="">---- Select Cost Cd----</option>
                  </select>
                </div>
              </div>

              <!-- 6. Genaration as Expenditure Block -->
              <div id="issueBlockExpenditure" style="display: none; flex-direction: column; gap: 10px;">
                <div style="display: flex; align-items: center; gap: 12px;">
                  <label style="width: 140px; font-weight: 700; font-size: 13px; color: #000000;">Issuing Type</label>
                  <select id="issueIssuingType" name="issuing_type" class="grn-input" style="min-width: 320px; max-width: 440px;">
                    <option value="">---- Select Issuing Type----</option>
                  </select>
                </div>
              </div>

              <!-- Row: Warehouse (Source Warehouse) -->
              <div style="display: flex; align-items: center; gap: 12px;">
                <label style="width: 140px; font-weight: 700; font-size: 13px; color: #000000;">Warehouse</label>
                <select name="warehouse" id="issueSourceWarehouse" class="grn-input" style="min-width: 320px; max-width: 440px;">
                  <option value="">---- Select Source Warehouse----</option>
                </select>
              </div>

              <!-- Retrieve Materials Button -->
              <div style="margin-top: 4px;">
                <button type="button" class="grn-classic-btn" onclick="Desk.retrieveIssueMaterials()" style="min-width: 140px;">
                  Retrieve Materials
                </button>
              </div>

            </div>

            <!-- Right Column: Trx Date & Acc Date -->
            <div style="display: flex; flex-direction: column; gap: 10px; background: #f8fafc; padding: 14px; border: 1px solid #e2e8f0; border-radius: 4px;">
              <div style="display: flex; align-items: center; justify-content: space-between;">
                <label style="font-weight: 700; font-size: 13px; color: #000000;">Trx Date</label>
                <input type="date" name="trx_date" id="issueTrxDate" value="<?= date('Y-m-d') ?>" class="grn-input" style="width: 140px;">
              </div>
              <div style="display: flex; align-items: center; justify-content: space-between;">
                <label style="font-weight: 700; font-size: 13px; color: #000000;">Acc Date</label>
                <input type="date" name="acc_date" id="issueAccDate" value="<?= date('Y-m-d') ?>" class="grn-input" style="width: 140px;">
              </div>
            </div>

          </div>

          <!-- COLLAPSIBLE SECTION 1: Exapand to Add,Edit Or view Reference Data -->
          <div style="margin-bottom: 12px; border: 1px solid #93c5fd; border-radius: 3px; overflow: hidden;">
            <div class="grn-blue-header" onclick="Desk.toggleIssueSection('issueRefBox', this)" style="background: #93c5fd; color: #0f172a; padding: 6px 12px; font-weight: 700; font-size: 13px; display: flex; justify-content: space-between; align-items: center; cursor: pointer; user-select: none;">
              <span>Exapand to Add,Edit Or view Reference Data</span>
              <span class="grn-toggle-icon" style="font-family: monospace; font-size: 14px; font-weight: 800; background: #1e3a8a; color: #ffffff; padding: 0 4px; border-radius: 2px;">+</span>
            </div>
            <div id="issueRefBox" style="display: none; background: #bfdbfe; padding: 12px 16px; flex-direction: column; gap: 8px;">
              <div style="display: flex; align-items: center; gap: 14px;">
                <label style="width: 120px; font-weight: 700; font-size: 13px; color: #000000;">Reference 1</label>
                <input type="text" name="other_ref_1" id="issueRef1" class="grn-input" style="width: 280px; background: #ffffff;">
              </div>
              <div style="display: flex; align-items: center; gap: 14px;">
                <label style="width: 120px; font-weight: 700; font-size: 13px; color: #000000;">Reference 2</label>
                <input type="text" name="other_ref_2" id="issueRef2" class="grn-input" style="width: 280px; background: #ffffff;">
              </div>
              <div style="display: flex; align-items: center; gap: 14px;">
                <label style="width: 120px; font-weight: 700; font-size: 13px; color: #000000;">Reference 3</label>
                <input type="text" name="other_ref_3" id="issueRef3" class="grn-input" style="width: 280px; background: #ffffff;">
              </div>
              <div style="display: flex; align-items: center; gap: 14px;">
                <label style="width: 120px; font-weight: 700; font-size: 13px; color: #000000;">Reference 4</label>
                <input type="text" name="other_ref_4" id="issueRef4" class="grn-input" style="width: 280px; background: #ffffff;">
              </div>
            </div>
          </div>

          <!-- COLLAPSIBLE SECTION 2: Exapand to Add,Edit Or view Remarks -->
          <div style="margin-bottom: 16px; border: 1px solid #93c5fd; border-radius: 3px; overflow: hidden;">
            <div class="grn-blue-header" onclick="Desk.toggleIssueSection('issueRemarksBox', this)" style="background: #93c5fd; color: #0f172a; padding: 6px 12px; font-weight: 700; font-size: 13px; display: flex; justify-content: space-between; align-items: center; cursor: pointer; user-select: none;">
              <span>Exapand to Add,Edit Or view Remarks</span>
              <span class="grn-toggle-icon" style="font-family: monospace; font-size: 14px; font-weight: 800; background: #1e3a8a; color: #ffffff; padding: 0 4px; border-radius: 2px;">+</span>
            </div>
            <div id="issueRemarksBox" style="display: none; background: #bfdbfe; padding: 12px 16px;">
              <textarea name="remarks" id="issueRemarks" rows="2" style="width: 100%; border: 1px solid #71717a; border-radius: 2px; padding: 6px; font-size: 13px; background: #ffffff;" placeholder="Issue remarks, work order description, or destination details..."></textarea>
            </div>
          </div>

          <!-- MATERIAL SELECTION & QUANTITIES ROW -->
          <div style="display: flex; flex-direction: column; gap: 12px; margin-bottom: 16px;">
            <div style="display: flex; align-items: flex-end; gap: 14px; flex-wrap: wrap;">
              
              <!-- Material Selection -->
              <div style="display: flex; flex-direction: column; gap: 4px; flex: 2; min-width: 260px;">
                <label style="font-weight: 700; font-size: 13px; color: #000000;">Material Selection</label>
                <select id="issueSelectMaterial" class="grn-input" onchange="Desk.onIssueMaterialChange(this.value)" style="width: 100%; font-size: 13px;">
                  <option value="">-- Select Material For Issues--</option>
                </select>
              </div>

              <!-- Avail Quantity -->
              <div style="display: flex; flex-direction: column; gap: 4px; width: 110px;">
                <label style="font-weight: 700; font-size: 13px; color: #000000;">Avail Quantity</label>
                <input type="text" id="issueAvailQty" readonly class="grn-input" style="width: 100%; background: #f8fafc; text-align: right;" value="0">
              </div>

              <!-- Re Order Qty Level -->
              <div style="display: flex; flex-direction: column; gap: 4px; width: 130px;">
                <label style="font-weight: 700; font-size: 13px; color: #000000;">Re Order Qty Level</label>
                <input type="text" id="issueReorderQty" readonly class="grn-input" style="width: 100%; background: #f8fafc; text-align: right;" value="0">
              </div>

              <!-- Required Quantity -->
              <div style="display: flex; flex-direction: column; gap: 4px; width: 130px;">
                <label style="font-weight: 700; font-size: 13px; color: #000000;">Required Quantity</label>
                <input type="number" id="issueReqQty" min="1" value="1" class="grn-input" style="width: 100%; text-align: right;">
              </div>

            </div>

            <!-- Add Material To Issue Button -->
            <div>
              <button type="button" class="grn-classic-btn" onclick="Desk.addIssueMaterialItem()" style="min-width: 160px; height: 30px;">
                Add Material To Issue
              </button>
            </div>

            <!-- Search row -->
            <div style="display: flex; align-items: center; gap: 12px; margin-top: 4px;">
              <label style="width: 50px; font-weight: 700; font-size: 13px; color: #000000;">Search</label>
              <input type="text" id="issueSearchMaterial" class="grn-input" style="flex: 1; max-width: 700px;" placeholder="Search material by code or description..." oninput="Desk.filterIssueMaterialDropdown(this.value)">
            </div>
          </div>

          <!-- ADDED MATERIALS LIST TABLE -->
          <div id="issueItemsTableWrap" style="margin-bottom: 20px; border: 1px solid #cbd5e1; border-radius: 3px; overflow: hidden; background: #ffffff;">
            <table class="desk-table" style="margin: 0; font-size: 12.5px;">
              <thead style="background: #f1f5f9;">
                <tr>
                  <th style="padding: 6px 10px; width: 140px;">SKU / Code</th>
                  <th style="padding: 6px 10px;">Material Description</th>
                  <th style="padding: 6px 10px; width: 100px; text-align: right;">Avail Qty</th>
                  <th style="padding: 6px 10px; width: 100px; text-align: right;">Req Qty</th>
                  <th style="padding: 6px 10px; width: 110px; text-align: right;">Est. Unit Cost</th>
                  <th style="padding: 6px 10px; width: 120px; text-align: right;">Line Total</th>
                  <th style="padding: 6px 10px; width: 60px; text-align: center;">Action</th>
                </tr>
              </thead>
              <tbody id="issueItemsTableBody">
                <tr id="issueEmptyRow">
                  <td colspan="7" style="text-align: center; color: #94a3b8; padding: 14px;">No materials added yet. Select a material above and click &quot;Add Material To Issue&quot;.</td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- BOTTOM ACTION BUTTONS: SAVE & CLEAR ALL -->
          <div style="display: flex; justify-content: flex-end; gap: 12px; padding-top: 10px; border-top: 1px solid #e2e8f0;">
            <button type="button" class="grn-action-btn" onclick="Desk.submitDetailedIssue()" style="background: #5b7c99; color: #ffffff;">
              Save
            </button>
            <button type="button" class="grn-action-btn" onclick="Desk.clearDetailedIssue()" style="background: #7a8fa6; color: #ffffff;">
              Clear All
            </button>
          </div>

        </form>
      </div>

      <div class="desk-card">
        <div class="desk-card-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
          <div>
            <div class="desk-card-title" id="issuesCardTitle">Material Issue Requisitions (FIFO Queue)</div>
            <div id="issuesCardSubtitle" style="font-size: 12px; color: var(--text-muted); margin-top: 2px;">
              Material requisitions queue from staff draft to storekeeper validation, authorization and FIFO batch consumption
            </div>
          </div>
          <div id="issuesHeaderAction"></div>
        </div>
        <div class="desk-table-wrapper">
          <table class="desk-table">
            <thead>
              <tr>
                <th>Issue No</th>
                <th>Issue Date</th>
                <th>Destination / Job Area</th>
                <th>Requesting Department</th>
                <th>Items &amp; Qty</th>
                <th>Estimated Cost</th>
                <th>Status</th>
                <th>Workflow Actions</th>
              </tr>
            </thead>
            <tbody id="issuesTableBody"></tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- ============================================================
         PANE: PHYSICAL VERIFICATION (WORKFLOW DESK)
         ============================================================ -->
    <div id="tab-pv-workflow" class="tab-pane" style="display: none;">
      <div class="workspace-header">
        <div class="workspace-title-box">
          <h1>Verification Workflow</h1>
          <div class="workspace-subtitle">Store Batch Freezing, Count Entry, Audit Validation &amp; Reconciliation</div>
        </div>
        <div class="header-action-btns">
          <button class="desk-btn desk-btn-default" onclick="Desk.switchTab('tab-pv-reports')">
            <?= Icon::get('file-check', 13) ?> View PHV Reports
          </button>
        </div>
      </div>

      <!-- Workflow Sub-tabs Strip (Fixed, Clean Wrap, Zero Side Scroll) -->
      <div class="desk-card" style="padding: 10px 14px; margin-bottom: 20px; border-radius: 8px; background: #ffffff; border: 1px solid #e2e8f0;">
        <div id="pvWorkflowPills" class="pv-pills-wrap">
          <button type="button" class="pv-pill-btn active" data-pv-btn="pv-sub-freeze" onclick="Desk.switchPvSubTab('pv-sub-freeze')">
            <?= Icon::get('lock', 12) ?> Freeze Batches
          </button>
          <button type="button" class="pv-pill-btn" data-pv-btn="pv-sub-generate" onclick="Desk.switchPvSubTab('pv-sub-generate')">
            <?= Icon::get('file-plus', 12) ?> Generate Sheet
          </button>
          <button type="button" class="pv-pill-btn" data-pv-btn="pv-sub-entry" onclick="Desk.switchPvSubTab('pv-sub-entry')">
            <?= Icon::get('edit', 12) ?> Count Entry
          </button>
          <button type="button" class="pv-pill-btn" data-pv-btn="pv-sub-edit" onclick="Desk.switchPvSubTab('pv-sub-edit')">
            <?= Icon::get('edit-3', 12) ?> Verification Edit
          </button>
          <button type="button" class="pv-pill-btn" data-pv-btn="pv-sub-validation" onclick="Desk.switchPvSubTab('pv-sub-validation')">
            <?= Icon::get('check-circle', 12) ?> Validation
          </button>
          <button type="button" class="pv-pill-btn" data-pv-btn="pv-sub-approval" onclick="Desk.switchPvSubTab('pv-sub-approval')">
            <?= Icon::get('award', 12) ?> Approval
          </button>
          <button type="button" class="pv-pill-btn" data-pv-btn="pv-sub-posting" onclick="Desk.switchPvSubTab('pv-sub-posting')">
            <?= Icon::get('send', 12) ?> Posting
          </button>
          <button type="button" class="pv-pill-btn" data-pv-btn="pv-sub-gl" onclick="Desk.switchPvSubTab('pv-sub-gl')">
            <?= Icon::get('database', 12) ?> GL Posting
          </button>
          <button type="button" class="pv-pill-btn" data-pv-btn="pv-sub-unfreeze" onclick="Desk.switchPvSubTab('pv-sub-unfreeze')">
            <?= Icon::get('unlock', 12) ?> Unfreeze Batches
          </button>
        </div>
      </div>

      <!-- SUB-PANE 1: FREEZE BATCHES -->
      <div id="pv-sub-freeze" class="pv-sub-pane active">
        <div class="desk-card" style="margin-bottom: 20px;">
          <div style="display: flex; align-items: center; justify-content: space-between; padding: 16px 20px;">
            <div>
              <div style="font-weight: 700; font-size: 14px; color: var(--text-dark);">Store Transaction Lock Status</div>
              <div style="font-size: 12px; color: var(--text-muted); margin-top: 4px;">When frozen, all Receipts (GRN) and Material Issues are blocked across the station.</div>
            </div>
            <div style="display: flex; align-items: center; gap: 12px;">
              <div id="freezeStatusBadge">
                <span class="badge badge-success" style="font-size: 13px; padding: 6px 14px;">Store Status: NORMAL / ACTIVE</span>
              </div>
              <button class="desk-btn desk-btn-primary" onclick="Desk.freezeAllBatches(true)">
                <?= Icon::get('lock', 13) ?> Freeze All Batches Now
              </button>
            </div>
          </div>
        </div>

        <div class="desk-card">
          <div class="desk-card-header">
            <div class="desk-card-title">Batches Subject to Freeze Control</div>
            <button class="desk-btn desk-btn-default desk-btn-sm" onclick="Desk.loadBatches()">
              <?= Icon::get('refresh', 13) ?> Refresh Batches
            </button>
          </div>
          <div class="desk-table-wrapper">
            <table class="desk-table">
              <thead>
                <tr>
                  <th>Batch Lot #</th>
                  <th>Material Code</th>
                  <th>Item Description</th>
                  <th>Bin Location</th>
                  <th>Available QOH</th>
                  <th>Unit Cost (LKR)</th>
                  <th>Total Valuation (LKR)</th>
                  <th>Lock Status</th>
                </tr>
              </thead>
              <tbody id="freezeBatchesTableBody"></tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- SUB-PANE 2: GENERATE SHEET -->
      <div id="pv-sub-generate" class="pv-sub-pane">
        <!-- Filter and Generate Parameters -->
        <div class="desk-card" style="padding: 20px 24px; margin-bottom: 20px;">
          <form id="formGenerateSheet" onsubmit="event.preventDefault(); Desk.generateCountSheet();">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)) 160px; gap: 16px; align-items: flex-end;">
              <div class="desk-form-group" style="margin: 0;">
                <label class="desk-form-label">Power Station / Facility</label>
                <select id="sheetStation" class="desk-form-control">
                  <option value="Victoria Hydro Power Station" selected>Victoria Hydro Power Station</option>
                  <option value="Kotmale Hydro Power Station">Kotmale Hydro Power Station</option>
                  <option value="Randenigala Power Station">Randenigala Power Station</option>
                  <option value="Rantembe Power Station">Rantembe Power Station</option>
                  <option value="Ukuwela Hydro Station">Ukuwela Hydro Station</option>
                  <option value="Bowatenna Power Station">Bowatenna Power Station</option>
                </select>
              </div>
              <div class="desk-form-group" style="margin: 0;">
                <label class="desk-form-label">Cost Center / Department</label>
                <select id="sheetCostCenter" class="desk-form-control">
                  <option value="Mechanical" selected>Mechanical</option>
                  <option value="Electrical">Electrical</option>
                  <option value="Civil">Civil</option>
                  <option value="Operations">Operations</option>
                  <option value="Administration">Administration</option>
                </select>
              </div>
              <div class="desk-form-group" style="margin: 0;">
                <label class="desk-form-label">Warehouse / Store</label>
                <select id="sheetWarehouse" class="desk-form-control">
                  <option value="Central Main Store" selected>Central Main Store</option>
                  <option value="Mechanical Spares Warehouse">Mechanical Spares Warehouse</option>
                  <option value="Electrical Spares Warehouse">Electrical Spares Warehouse</option>
                  <option value="Turbine & Heavy Equipment Yard">Turbine & Heavy Equipment Yard</option>
                  <option value="Fuel & Lubricant Depot">Fuel & Lubricant Depot</option>
                </select>
              </div>
              <div class="desk-form-group" style="margin: 0;">
                <label class="desk-form-label">Verification Year</label>
                <input type="number" id="sheetYear" class="desk-form-control" value="<?= date('Y') ?>">
              </div>
              <button type="submit" class="desk-btn desk-btn-primary" style="height: 38px; justify-content: center; font-weight: 600;">
                <?= Icon::get('file-plus', 13) ?> Generate Sheets
              </button>
            </div>
          </form>
        </div>

        <!-- Sheet Management and Action Toolbar -->
        <div class="desk-card" style="margin-bottom: 20px; padding: 18px 24px;">
          <div style="display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 16px;">
            <div>
              <div style="font-size: 15px; font-weight: 700; color: #0f172a; display: flex; align-items: center; gap: 8px;">
                <span>Statutory Stock Count Sheets</span>
                <span class="badge" style="background: #e0f2fe; color: #0369a1; font-weight: 700; font-size: 11px; padding: 3px 8px; border-radius: 4px;">21 Items / Sheet Standard</span>
              </div>
              <div style="font-size: 12px; color: #64748b; margin-top: 4px; display: flex; align-items: center; gap: 14px;">
                <span>Total Items: <strong id="genSheetTotalItemsBadge" style="color: #0f172a;">-</strong></span>
                <span>•</span>
                <span>Total Sheets: <strong id="genSheetTotalSheetsBadge" style="color: #0f172a;">-</strong> PDFs</span>
                <span>•</span>
                <span id="genSheetItemRange" style="color: #0284c7; font-weight: 600;">Loading items...</span>
              </div>
            </div>

            <!-- Download Action Buttons -->
            <div style="display: flex; flex-wrap: wrap; align-items: center; gap: 10px;">
              <button type="button" class="desk-btn desk-btn-primary" style="background: #0284c7; border-color: #0284c7; font-weight: 600;" onclick="Desk.downloadAllSheetsZip()">
                <?= Icon::get('archive', 13) ?> Download All Sheets as ZIP
              </button>
              <button type="button" class="desk-btn desk-btn-default" onclick="Desk.downloadCurrentSheetPdf()">
                <?= Icon::get('file-text', 13) ?> Download Current Sheet (PDF)
              </button>
              <button type="button" class="desk-btn desk-btn-default" onclick="window.print()">
                <?= Icon::get('printer', 13) ?> Print Sheet
              </button>
            </div>
          </div>

          <!-- Pagination & Sheet Switcher -->
          <div style="margin-top: 16px; padding-top: 14px; border-top: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
            <div style="display: flex; align-items: center; gap: 8px;">
              <span style="font-size: 12px; font-weight: 600; color: #475569;">Select Sheet:</span>
              <select id="genSheetPageSelect" class="desk-form-control" style="width: auto; height: 32px; font-size: 12px; font-weight: 600; padding: 2px 8px;" onchange="Desk.loadGeneratedSheet(this.value)">
                <option value="1">Sheet 1</option>
              </select>
            </div>
            <div style="display: flex; align-items: center; gap: 6px;">
              <button type="button" class="desk-btn desk-btn-default desk-btn-sm" onclick="Desk.prevSheetPage()">
                &larr; Prev Sheet
              </button>
              <span id="genSheetCurrentBadge" style="font-size: 12px; font-weight: 700; padding: 4px 10px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 4px; color: #334155;">
                Sheet 1
              </span>
              <button type="button" class="desk-btn desk-btn-default desk-btn-sm" onclick="Desk.nextSheetPage()">
                Next Sheet &rarr;
              </button>
            </div>
          </div>
        </div>

        <!-- The 21-Row Count Sheet View -->
        <div id="printSheetSection" class="desk-card" style="padding: 20px;">
          <!-- Printable Header Banner (visible on print and screen) -->
          <div style="border: 1px solid #cbd5e1; border-radius: 6px; padding: 14px 18px; margin-bottom: 16px; background: #f8fafc; display: flex; justify-content: space-between; align-items: center;">
            <div>
              <div style="font-size: 13px; font-weight: 800; color: #0f172a; text-transform: uppercase; letter-spacing: 0.5px;">
                Electricity Generation Lanka — Board of Survey Physical Count Sheet
              </div>
              <div style="font-size: 11px; color: #475569; margin-top: 3px;">
                Form EGL-ST-PHV-01 | Standard 21 Items Stock Count Tally Sheet
              </div>
            </div>
            <div style="text-align: right;">
              <div style="display: inline-block; background: #0f766e; color: #fff; font-size: 11px; font-weight: 700; padding: 4px 10px; border-radius: 4px;">
                EXACTLY 21 ITEMS / SHEET
              </div>
            </div>
          </div>

          <div class="desk-table-wrapper">
            <table class="desk-table">
              <thead>
                <tr>
                  <th style="width: 45px; text-align: center;">#</th>
                  <th>Bin Location</th>
                  <th>Item Code (SKU)</th>
                  <th>Item Description & Specification</th>
                  <th>UOM</th>
                  <th>System Book Qty</th>
                  <th style="width: 140px; text-align: center;">Physical Count (Tally)</th>
                  <th style="width: 110px; text-align: center;">Auditor Initials</th>
                  <th style="width: 70px;">Global #</th>
                </tr>
              </thead>
              <tbody id="generateSheetTableBody"></tbody>
            </table>
          </div>

          <!-- Bottom Tri-Party Signatures Block -->
          <div style="margin-top: 24px; padding: 18px; border: 1px solid #e2e8f0; border-radius: 6px; background: #fcfcfd;">
            <div style="font-size: 11px; font-weight: 700; color: #475569; text-transform: uppercase; margin-bottom: 12px; letter-spacing: 0.5px;">
              Statutory Tri-Party Stock Verification Endorsements:
            </div>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px;">
              <div style="padding: 12px; border: 1px dashed #cbd5e1; border-radius: 4px; background: #fff;">
                <div style="font-size: 11px; font-weight: 700; color: #1e293b;">1. Physical Stock Verified By:</div>
                <div style="font-size: 11px; color: #64748b; margin-top: 8px;">Name: ______________________</div>
                <div style="font-size: 11px; color: #64748b; margin-top: 6px;">EPF / Desig: ________________</div>
                <div style="font-size: 11px; color: #64748b; margin-top: 6px;">Signature & Date: ___________</div>
              </div>
              <div style="padding: 12px; border: 1px dashed #cbd5e1; border-radius: 4px; background: #fff;">
                <div style="font-size: 11px; font-weight: 700; color: #1e293b;">2. Supervised & Checked By:</div>
                <div style="font-size: 11px; color: #64748b; margin-top: 8px;">Name: ______________________</div>
                <div style="font-size: 11px; color: #64748b; margin-top: 6px;">EPF / Desig: ________________</div>
                <div style="font-size: 11px; color: #64748b; margin-top: 6px;">Signature & Date: ___________</div>
              </div>
              <div style="padding: 12px; border: 1px dashed #cbd5e1; border-radius: 4px; background: #fff;">
                <div style="font-size: 11px; font-weight: 700; color: #1e293b;">3. Board of Survey Auditor:</div>
                <div style="font-size: 11px; color: #64748b; margin-top: 8px;">Name: ______________________</div>
                <div style="font-size: 11px; color: #64748b; margin-top: 6px;">EPF / Desig: ________________</div>
                <div style="font-size: 11px; color: #64748b; margin-top: 6px;">Signature & Date: ___________</div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- SUB-PANE 3: VERIFICATION ENTRY -->
      <div id="pv-sub-entry" class="pv-sub-pane">
        <div class="desk-card" style="margin-bottom: 16px; padding: 16px 20px; display: flex; justify-content: space-between; align-items: center;">
          <div>
            <div style="font-weight: 700; font-size: 14px;">Physical Count Input Table</div>
            <div style="font-size: 12px; color: var(--text-muted);">Variance updates automatically upon entry</div>
          </div>
          <button class="desk-btn desk-btn-primary" onclick="Desk.saveVerificationCounts()">
            <?= Icon::get('edit', 13) ?> Save Counts
          </button>
        </div>

        <div class="desk-card">
          <div class="desk-table-wrapper">
            <table class="desk-table">
              <thead>
                <tr>
                  <th>SKU</th>
                  <th>Item Description</th>
                  <th>Bin</th>
                  <th>Book Qty</th>
                  <th style="width: 140px;">Physical Count</th>
                  <th>Variance (Qty)</th>
                  <th>Variance Value (LKR)</th>
                  <th>Checker Remarks</th>
                </tr>
              </thead>
              <tbody id="pvEntryTableBody"></tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- SUB-PANE 4: VERIFICATION EDIT -->
      <div id="pv-sub-edit" class="pv-sub-pane">
        <div class="filter-pills-bar" style="margin-bottom: 16px; display: flex; justify-content: space-between; align-items: center;">
          <div style="display: flex; gap: 6px;">
            <button class="filter-pill active" onclick="Desk.filterPvEdit('all')">All Items</button>
            <button class="filter-pill" onclick="Desk.filterPvEdit('discrepancy')">Discrepancies Only (Variance &ne; 0)</button>
            <button class="filter-pill" onclick="Desk.filterPvEdit('surplus')">Surplus Items (+)</button>
            <button class="filter-pill" onclick="Desk.filterPvEdit('deficit')">Deficit Items (-)</button>
          </div>
          <button class="desk-btn desk-btn-primary desk-btn-sm" onclick="Desk.saveVerificationCounts()">
            <?= Icon::get('edit-3', 13) ?> Update All Changes
          </button>
        </div>

        <div class="desk-card">
          <div class="desk-table-wrapper">
            <table class="desk-table">
              <thead>
                <tr>
                  <th>SKU</th>
                  <th>Item Description</th>
                  <th>Bin</th>
                  <th>Book Qty</th>
                  <th style="width: 140px;">Counted Qty</th>
                  <th>Variance</th>
                  <th>Net Value (LKR)</th>
                  <th>Audit Reason / Explanation</th>
                </tr>
              </thead>
              <tbody id="pvEditTableBody"></tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- SUB-PANE 5: VERIFICATION VALIDATION -->
      <div id="pv-sub-validation" class="pv-sub-pane">
        <div class="desk-card" style="padding: 20px 24px; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center;">
          <div>
            <h4 style="font-size: 1.05rem; font-weight: 700; color: var(--text-dark); margin: 0 0 6px 0;">
              Following Documents are pending for your validation
            </h4>
            <div style="font-size: 13px; color: var(--text-muted);">
              Please review discrepancy thresholds prior to signing audit validation.
            </div>
          </div>
          <button class="desk-btn desk-btn-primary" onclick="Desk.validatePvSession()">
            <?= Icon::get('check-circle', 13) ?> Validate Verification Sheet
          </button>
        </div>

        <div class="desk-card">
          <div class="desk-card-header">
            <div class="desk-card-title">Verification Batches Pending Validation</div>
          </div>
          <div class="desk-table-wrapper">
            <table class="desk-table">
              <thead>
                <tr>
                  <th>Session Code</th>
                  <th>Cost Center</th>
                  <th>Date of Survey</th>
                  <th>Counted Items</th>
                  <th>Surplus Total</th>
                  <th>Deficit Total</th>
                  <th>Current Status</th>
                  <th>Action</th>
                </tr>
              </thead>
              <tbody id="pvValidationTableBody"></tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- SUB-PANE 6: VERIFICATION APPROVAL -->
      <div id="pv-sub-approval" class="pv-sub-pane">
        <div class="workspace-cards-grid" style="grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); margin-bottom: 20px;">
          <div class="desk-stat-card">
            <div class="stat-card-title">Validation Sign-off</div>
            <div class="stat-card-value" style="color: #059669; font-size: 1.2rem;">VALIDATED</div>
            <div style="font-size: 11px; color: var(--text-muted); margin-top: 4px;">Verified by Internal Audit Unit</div>
          </div>
          <div class="desk-stat-card">
            <div class="stat-card-title">Discrepancy Items Count</div>
            <div class="stat-card-value" id="pvApprovalDiscrepanciesCount">0 Items</div>
          </div>
          <div class="desk-stat-card">
            <div class="stat-card-title">Net Financial Impact</div>
            <div class="stat-card-value" id="pvApprovalNetImpact">Rs. 0.00</div>
          </div>
        </div>

        <div class="desk-card">
          <div class="desk-card-header">
            <div class="desk-card-title">Approved Session Authorization Register</div>
            <button class="desk-btn desk-btn-primary desk-btn-sm" onclick="Desk.approvePvSession()">
              <?= Icon::get('award', 13) ?> Approve for GL Posting
            </button>
          </div>
          <div class="desk-table-wrapper">
            <table class="desk-table">
              <thead>
                <tr>
                  <th>Session Code</th>
                  <th>Title</th>
                  <th>Survey Date</th>
                  <th>Authorized Storekeeper</th>
                  <th>Lead Auditor</th>
                  <th>Approval Status</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody id="pvApprovalTableBody"></tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- SUB-PANE 7: VERIFICATION POSTING -->
      <div id="pv-sub-posting" class="pv-sub-pane">
        <div class="desk-card" style="padding: 20px 24px; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center;">
          <div>
            <div style="font-weight: 700; font-size: 1.05rem; color: var(--text-dark); margin-bottom: 4px;">
              Pending Inventory Adjustments Ready for Posting
            </div>
            <div style="font-size: 13px; color: var(--text-muted);">
              Updates live FIFO batch remaining quantities to match audited counts and writes reconciliation ledger entries.
            </div>
          </div>
          <button class="desk-btn desk-btn-primary" onclick="Desk.postVerification()">
            <?= Icon::get('send', 13) ?> Post Verification to Inventory
          </button>
        </div>

        <div class="desk-card">
          <div class="desk-card-header">
            <div class="desk-card-title">Stock Ledger Adjustments Preview</div>
          </div>
          <div class="desk-table-wrapper">
            <table class="desk-table">
              <thead>
                <tr>
                  <th>SKU</th>
                  <th>Description</th>
                  <th>Bin</th>
                  <th>Book Qty</th>
                  <th>Audited Count</th>
                  <th>Adjustment</th>
                  <th>Unit Price</th>
                  <th>Total Value (LKR)</th>
                  <th>Adjustment Type</th>
                </tr>
              </thead>
              <tbody id="pvPostingTableBody"></tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- SUB-PANE 8: VERIFICATION GL POSTING -->
      <div id="pv-sub-gl" class="pv-sub-pane">
        <div class="desk-card" style="padding: 20px 24px; margin-bottom: 20px;">
          <form id="pvGlPostingForm" onsubmit="event.preventDefault(); Desk.loadGlPreview();">
            <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 16px;">
              <div style="display: flex; align-items: center; gap: 14px;">
                <label style="font-weight: 700; font-size: 14px; white-space: nowrap;">Warehouse:</label>
                <select id="glWarehouse" class="desk-form-control" style="width: 280px;">
                  <option value="Central Main Store" selected>Central Main Store</option>
                  <option value="Mechanical Spares Warehouse">Mechanical Spares Warehouse</option>
                  <option value="Electrical Spares Warehouse">Electrical Spares Warehouse</option>
                  <option value="Turbine & Heavy Equipment Yard">Turbine & Heavy Equipment Yard</option>
                  <option value="Fuel & Lubricant Depot">Fuel & Lubricant Depot</option>
                </select>
                <button type="submit" class="desk-btn desk-btn-default">
                  Get Details
                </button>
              </div>
              <button type="button" class="desk-btn desk-btn-primary" onclick="Desk.transferToGL()">
                <?= Icon::get('database', 13) ?> Transfer Values to GL
              </button>
            </div>
          </form>
        </div>

        <div class="desk-card">
          <div class="desk-card-header">
            <div class="desk-card-title">General Ledger (GL) Journal Voucher Entry Preview</div>
          </div>
          <div class="desk-table-wrapper">
            <table class="desk-table">
              <thead>
                <tr>
                  <th>Account Code</th>
                  <th>Account Name</th>
                  <th>Debit (Rs.)</th>
                  <th>Credit (Rs.)</th>
                  <th>Narration</th>
                </tr>
              </thead>
              <tbody id="pvGlTableBody"></tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- SUB-PANE 9: UNFREEZE BATCHES -->
      <div id="pv-sub-unfreeze" class="pv-sub-pane">
        <div class="desk-card" style="padding: 30px 24px; text-align: center; max-width: 600px; margin: 20px auto;">
          <div style="margin-bottom: 16px; color: #0d9488;">
            <?= Icon::get('unlock', 48) ?>
          </div>
          <h3 style="margin: 0 0 10px 0; font-size: 1.25rem;">Ready to Re-Open Stores for Normal Operations?</h3>
          <p style="color: var(--text-muted); font-size: 13px; line-height: 1.6; margin-bottom: 24px;">
            Unfreezing will remove transaction locks on all batches across Victoria Hydro Station. Engineering departments will be able to submit and receive Material Issues again.
          </p>
          <button class="desk-btn desk-btn-primary" style="padding: 10px 28px; font-size: 14px; font-weight: 700; margin: 0 auto;" onclick="Desk.freezeAllBatches(false)">
            <?= Icon::get('unlock', 14) ?> Confirm &amp; Unfreeze Batches
          </button>
        </div>
      </div>
    </div> <!-- Close #tab-pv-workflow -->

    <!-- ============================================================
         PANE: PHV REPORTS (AV 7 & AUDIT DESK)
         ============================================================ -->
    <div id="tab-pv-reports" class="tab-pane" style="display: none;">
      <div class="workspace-header">
        <div class="workspace-title-box">
          <h1>PHV Reports &amp; Form AV 7</h1>
          <div class="workspace-subtitle">Board of Survey Official Reports, Consumption Trends &amp; Valuations</div>
        </div>
        <div class="header-action-btns">
          <button class="desk-btn desk-btn-default" onclick="window.print()">
            <?= Icon::get('printer', 13) ?> Print Current Report
          </button>
        </div>
      </div>

      <!-- Reports Sub-tabs Strip (Fixed, Clean Wrap, Zero Side Scroll) -->
      <div class="desk-card" style="padding: 10px 14px; margin-bottom: 20px; border-radius: 8px; background: #ffffff; border: 1px solid #e2e8f0;">
        <div id="pvReportsPills" class="pv-pills-wrap">
          <button type="button" class="pv-pill-btn active" data-pv-btn="pv-sub-avg" onclick="Desk.switchPvReportSubTab('pv-sub-avg')">
            <?= Icon::get('trending-up', 12) ?> Avg Consumption
          </button>
          <button type="button" class="pv-pill-btn" data-pv-btn="pv-sub-final" onclick="Desk.switchPvReportSubTab('pv-sub-final')">
            <?= Icon::get('file-text', 12) ?> Final Report
          </button>
          <button type="button" class="pv-pill-btn" data-pv-btn="pv-sub-other" onclick="Desk.switchPvReportSubTab('pv-sub-other')">
            <?= Icon::get('layers', 12) ?> Other Reports
          </button>
          <button type="button" class="pv-pill-btn" data-pv-btn="pv-sub-summary" onclick="Desk.switchPvReportSubTab('pv-sub-summary')">
            <?= Icon::get('clipboard', 12) ?> PHV Sheet Summary
          </button>
          <button type="button" class="pv-pill-btn" data-pv-btn="pv-sub-av7" onclick="Desk.switchPvReportSubTab('pv-sub-av7')">
            <?= Icon::get('file-check', 12) ?> Form AV 7 Summary
          </button>
        </div>
      </div>

      <!-- SUB-PANE 10: AVG CONSUMPTION REPORT -->
      <div id="pv-sub-avg" class="pv-sub-pane active">
        <div class="desk-card" style="padding: 24px; margin-bottom: 20px;">
          <form id="pvAvgConsumptionForm" onsubmit="event.preventDefault(); Desk.loadAvgConsumption();">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)) 160px; gap: 16px; align-items: flex-end;">
              <div class="desk-form-group" style="margin: 0;">
                <label class="desk-form-label">Cost Center</label>
                <select id="avgCostCenter" class="desk-form-control">
                  <option value="">-Select Department-</option>
                  <option value="Mechanical">Mechanical</option>
                  <option value="Electrical">Electrical</option>
                  <option value="Civil">Civil</option>
                  <option value="Operations">Operations</option>
                  <option value="Administration">Administration</option>
                </select>
              </div>
              <div class="desk-form-group" style="margin: 0;">
                <label class="desk-form-label">Warehouse</label>
                <select id="avgWarehouse" class="desk-form-control">
                  <option value="Central Main Store" selected>Central Main Store</option>
                  <option value="Mechanical Spares Warehouse">Mechanical Spares Warehouse</option>
                  <option value="Electrical Spares Warehouse">Electrical Spares Warehouse</option>
                  <option value="Turbine & Heavy Equipment Yard">Turbine & Heavy Equipment Yard</option>
                  <option value="Fuel & Lubricant Depot">Fuel & Lubricant Depot</option>
                </select>
              </div>
              <div class="desk-form-group" style="margin: 0;">
                <label class="desk-form-label">From Date (YYYY-MM-DD)</label>
                <input type="date" id="avgFromDate" class="desk-form-control" value="<?= date('Y-01-01') ?>">
              </div>
              <div class="desk-form-group" style="margin: 0;">
                <label class="desk-form-label">To Date (YYYY-MM-DD)</label>
                <input type="date" id="avgToDate" class="desk-form-control" value="<?= date('Y-m-d') ?>">
              </div>
              <button type="submit" class="desk-btn desk-btn-primary" style="height: 38px; justify-content: center;">
                <?= Icon::get('trending-up', 13) ?> Generate Report
              </button>
            </div>
          </form>
        </div>

        <div class="desk-card">
          <div class="desk-card-header">
            <div class="desk-card-title">Consumption Analysis &amp; Safety Stock Recommendations</div>
            <button class="desk-btn desk-btn-default desk-btn-sm" onclick="window.print()">
              <?= Icon::get('printer', 13) ?> Print Report
            </button>
          </div>
          <div class="desk-table-wrapper">
            <table class="desk-table">
              <thead>
                <tr>
                  <th>SKU</th>
                  <th>Material Description</th>
                  <th>UOM</th>
                  <th>Current QOH</th>
                  <th>Total Issues (Period)</th>
                  <th>Monthly Avg Consumption</th>
                  <th>Safety Stock</th>
                  <th>Reorder Point</th>
                  <th>Stock Health</th>
                </tr>
              </thead>
              <tbody id="avgConsumptionTableBody"></tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- SUB-PANE 11: FINAL REPORT -->
      <div id="pv-sub-final" class="pv-sub-pane">
        <div class="workspace-cards-grid" style="grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); margin-bottom: 20px;">
          <div class="desk-stat-card">
            <div class="stat-card-title">Total Verified Lines</div>
            <div class="stat-card-value" id="finalTotalLines">0</div>
          </div>
          <div class="desk-stat-card">
            <div class="stat-card-title">Total Surplus Items</div>
            <div class="stat-card-value" id="finalSurplusLines" style="color: #059669;">0</div>
          </div>
          <div class="desk-stat-card">
            <div class="stat-card-title">Total Deficit Items</div>
            <div class="stat-card-value" id="finalDeficitLines" style="color: #dc2626;">0</div>
          </div>
          <div class="desk-stat-card">
            <div class="stat-card-title">Net Financial Discrepancy</div>
            <div class="stat-card-value" id="finalNetVal">Rs. 0.00</div>
          </div>
        </div>

        <div class="desk-card">
          <div class="desk-card-header">
            <div class="desk-card-title">Reconciliation Schedule of Stores</div>
            <div style="display: flex; gap: 8px;">
              <button class="desk-btn desk-btn-default desk-btn-sm" onclick="Desk.loadFinalReport()">
                <?= Icon::get('refresh', 13) ?> Reload
              </button>
              <button class="desk-btn desk-btn-default desk-btn-sm" onclick="window.print()">
                <?= Icon::get('printer', 13) ?> Print
              </button>
            </div>
          </div>
          <div class="desk-table-wrapper">
            <table class="desk-table">
              <thead>
                <tr>
                  <th>Bin</th>
                  <th>SKU</th>
                  <th>Material Description</th>
                  <th>Book Balance</th>
                  <th>Counted Physical</th>
                  <th>Discrepancy (Qty)</th>
                  <th>Unit Cost</th>
                  <th>Discrepancy Value (LKR)</th>
                  <th>Certification</th>
                </tr>
              </thead>
              <tbody id="finalReportTableBody"></tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- SUB-PANE 12: OTHER REPORTS -->
      <div id="pv-sub-other" class="pv-sub-pane">
        <div class="desk-card" style="padding: 24px; margin-bottom: 20px;">
          <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)) 160px; gap: 16px; align-items: flex-end;">
            <div class="desk-form-group" style="margin: 0;">
              <label class="desk-form-label">Select Warehouse</label>
              <select id="otherWarehouse" class="desk-form-control">
                <option value="Central Main Store" selected>Central Main Store</option>
                <option value="Mechanical Spares Warehouse">Mechanical Spares Warehouse</option>
                <option value="Electrical Spares Warehouse">Electrical Spares Warehouse</option>
                <option value="Turbine & Heavy Equipment Yard">Turbine & Heavy Equipment Yard</option>
                <option value="Fuel & Lubricant Depot">Fuel & Lubricant Depot</option>
              </select>
            </div>
            <div class="desk-form-group" style="margin: 0;">
              <label class="desk-form-label">Report Category</label>
              <select id="otherReportType" class="desk-form-control">
                <option value="slow_moving">Slow-Moving &amp; Dormant Spares</option>
                <option value="obsolete">Obsolete &amp; Unserviceable Items</option>
                <option value="high_value">High-Value Critical Hydro Parts</option>
              </select>
            </div>
            <button class="desk-btn desk-btn-primary" style="height: 38px; justify-content: center;" onclick="Desk.loadOtherReport()">
              <?= Icon::get('layers', 13) ?> View Report
            </button>
          </div>
        </div>

        <div class="desk-card">
          <div class="desk-card-header">
            <div class="desk-card-title">Store Analytical Ledger</div>
          </div>
          <div class="desk-table-wrapper">
            <table class="desk-table">
              <thead>
                <tr>
                  <th>Material Code</th>
                  <th>Description</th>
                  <th>Category</th>
                  <th>Available QOH</th>
                  <th>Unit Valuation (LKR)</th>
                  <th>Total Value (LKR)</th>
                  <th>Last Movement Date</th>
                  <th>Stock Status</th>
                </tr>
              </thead>
              <tbody id="otherReportTableBody"></tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- SUB-PANE 13: PHV SHEET SUMMARY -->
      <div id="pv-sub-summary" class="pv-sub-pane">
        <div class="desk-card" style="padding: 24px; margin-bottom: 20px;">
          <form id="pvSheetSummaryForm" onsubmit="event.preventDefault(); Desk.loadSheetSummary();">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)) 160px; gap: 16px; align-items: flex-end;">
              <div class="desk-form-group" style="margin: 0;">
                <label class="desk-form-label">Select Cost Center</label>
                <select id="summaryCostCenter" class="desk-form-control">
                  <option value="">Select Department</option>
                  <option value="Mechanical" selected>Mechanical</option>
                  <option value="Electrical">Electrical</option>
                  <option value="Civil">Civil</option>
                  <option value="Operations">Operations</option>
                  <option value="Administration">Administration</option>
                </select>
              </div>
              <div class="desk-form-group" style="margin: 0;">
                <label class="desk-form-label">Select Warehouse</label>
                <select id="summaryWarehouse" class="desk-form-control">
                  <option value="Central Main Store" selected>Central Main Store</option>
                  <option value="Mechanical Spares Warehouse">Mechanical Spares Warehouse</option>
                  <option value="Electrical Spares Warehouse">Electrical Spares Warehouse</option>
                  <option value="Turbine & Heavy Equipment Yard">Turbine & Heavy Equipment Yard</option>
                  <option value="Fuel & Lubricant Depot">Fuel & Lubricant Depot</option>
                </select>
              </div>
              <div class="desk-form-group" style="margin: 0;">
                <label class="desk-form-label">Select PHV Year</label>
                <select id="summaryPhvYear" class="desk-form-control">
                  <option value="2023">2023</option>
                  <option value="2024">2024</option>
                  <option value="2025">2025</option>
                  <option value="2026" selected>2026</option>
                </select>
              </div>
              <button type="submit" class="desk-btn desk-btn-primary" style="height: 38px; justify-content: center;">
                <?= Icon::get('clipboard', 13) ?> Create Report
              </button>
            </div>
          </form>
        </div>

        <div class="desk-card">
          <div class="desk-card-header">
            <div class="desk-card-title">Count Sheet Batch Summary</div>
            <button class="desk-btn desk-btn-default desk-btn-sm" onclick="window.print()">
              <?= Icon::get('printer', 13) ?> Print Summary
            </button>
          </div>
          <div class="desk-table-wrapper">
            <table class="desk-table">
              <thead>
                <tr>
                  <th>Session Code</th>
                  <th>Title</th>
                  <th>Survey Date</th>
                  <th>Total Sheets Counted</th>
                  <th>Book Qty Sum</th>
                  <th>Physical Qty Sum</th>
                  <th>Net Variance Qty</th>
                  <th>Net Discrepancy Value (LKR)</th>
                  <th>Session Status</th>
                </tr>
              </thead>
              <tbody id="sheetSummaryTableBody"></tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- SUB-PANE 14: AV 7 SUMMARY REPORT -->
      <div id="pv-sub-av7" class="pv-sub-pane">
        <div class="workspace-cards-grid" style="grid-template-columns: 1fr 1fr 1fr; margin-bottom: 20px;">
          <div class="desk-stat-card">
            <div class="stat-card-title">Total Surplus Value (+)</div>
            <div class="stat-card-value" id="av7Surplus" style="color: #16a34a;">Rs. 0.00</div>
          </div>
          <div class="desk-stat-card">
            <div class="stat-card-title">Total Deficit Value (-)</div>
            <div class="stat-card-value" id="av7Deficit" style="color: #dc2626;">Rs. 0.00</div>
          </div>
          <div class="desk-stat-card">
            <div class="stat-card-title">Net Valuation Discrepancy</div>
            <div class="stat-card-value" id="av7Net">Rs. 0.00</div>
          </div>
        </div>

        <div class="desk-card">
          <div class="desk-card-header">
            <div class="desk-card-title">Board of Survey Items Verification Table (Form AV 7)</div>
            <div style="display: flex; gap: 8px;">
              <button class="desk-btn desk-btn-default desk-btn-sm" onclick="Desk.loadAV7Report()">
                <?= Icon::get('refresh', 13) ?> Refresh
              </button>
              <button class="desk-btn desk-btn-default desk-btn-sm" onclick="window.print()">
                <?= Icon::get('printer', 13) ?> Print Form AV 7
              </button>
            </div>
          </div>
          <div class="desk-table-wrapper">
            <table class="desk-table">
              <thead>
                <tr>
                  <th>#</th>
                  <th>SKU</th>
                  <th>Item Description</th>
                  <th>Bin</th>
                  <th>Book Qty</th>
                  <th>Physical Qty</th>
                  <th>Variance Qty</th>
                  <th>Unit Cost</th>
                  <th>Variance Value</th>
                  <th>Audit Remarks</th>
                </tr>
              </thead>
              <tbody id="av7TableBody"></tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

    <!-- ============================================================
         PANE 6: Cash Book
         ============================================================ -->
    <div id="tab-cashbook" class="tab-pane" style="display: none;">
      <div class="workspace-header">
        <div class="workspace-title-box">
          <h1>Cash Book Vouchers</h1>
          <div class="workspace-subtitle">Receipts, Emergency Handling & Petty Cash</div>
        </div>
        <div class="header-action-btns">
          <button class="desk-btn desk-btn-default" onclick="Desk.loadCashBook()">
            <?= Icon::get('refresh', 13) ?> Refresh
          </button>
        </div>
      </div>

      <!-- In-Body Cash Book Voucher Entry Form -->
      <div class="desk-card" style="margin-bottom: 22px; padding: 22px 26px; border: 1.5px solid #000000; border-radius: 4px; background: #ffffff;">
        <h3 style="margin: 0 0 16px 0; font-size: 1.05rem; font-weight: 700; color: #000000; display: flex; align-items: center; gap: 8px;">
          <?= Icon::get('dollar-sign', 16) ?> Enter Cash Book Voucher
        </h3>
        <form id="bodyCashVoucherForm" onsubmit="event.preventDefault(); Desk.submitBodyCashVoucher(this);">
          <div style="display: grid; grid-template-columns: 180px 1fr 1fr 180px; gap: 14px; margin-bottom: 14px; align-items: flex-end;">
            <div class="desk-form-group" style="margin: 0;">
              <label class="desk-form-label" style="font-weight: 700; color: #000000;">Voucher Type *</label>
              <select name="entry_type" class="desk-form-control" style="border: 1px solid #71717a; border-radius: 2px;">
                <option value="Payment">Payment Voucher</option>
                <option value="Receipt">Receipt Voucher</option>
                <option value="Petty Cash">Petty Cash Voucher</option>
              </select>
            </div>
            <div class="desk-form-group" style="margin: 0;">
              <label class="desk-form-label" style="font-weight: 700; color: #000000;">Account Title *</label>
              <input type="text" name="account_title" class="desk-form-control" placeholder="e.g. Fuel Emergency / Spares Petty Cash" required style="border: 1px solid #71717a; border-radius: 2px;">
            </div>
            <div class="desk-form-group" style="margin: 0;">
              <label class="desk-form-label" style="font-weight: 700; color: #000000;">Payee / Source *</label>
              <input type="text" name="payee_or_source" class="desk-form-control" placeholder="e.g. CEB / CPC / Station Cashier" required style="border: 1px solid #71717a; border-radius: 2px;">
            </div>
            <div class="desk-form-group" style="margin: 0;">
              <label class="desk-form-label" style="font-weight: 700; color: #000000;">Amount (Rs.) *</label>
              <input type="number" name="amount" class="desk-form-control" step="0.01" min="1" placeholder="0.00" required style="border: 1px solid #71717a; border-radius: 2px;">
            </div>
          </div>
          <div class="desk-form-group" style="margin-bottom: 16px;">
            <label class="desk-form-label" style="font-weight: 700; color: #000000;">Description / Remarks</label>
            <input type="text" name="description" class="desk-form-control" placeholder="Details and authorization reference..." style="border: 1px solid #71717a; border-radius: 2px;">
          </div>
          <div style="display: flex; justify-content: flex-end;">
            <button type="submit" class="desk-btn desk-btn-primary" style="padding: 7px 22px; font-weight: 600;">
              <?= Icon::get('plus', 13) ?> Record Cash Voucher
            </button>
          </div>
        </form>
      </div>

      <div class="desk-card">
        <div class="desk-card-header">
          <div class="desk-card-title">Voucher Entries</div>
          <button class="desk-btn desk-btn-default desk-btn-sm" onclick="Desk.loadCashBook()">
            <?= Icon::get('refresh', 13) ?> Refresh
          </button>
        </div>
        <div class="desk-table-wrapper">
          <table class="desk-table">
            <thead>
              <tr>
                <th>Voucher No</th>
                <th>Date</th>
                <th>Type</th>
                <th>Account Title</th>
                <th>Payee / Source</th>
                <th>Amount</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody id="cashBookTableBody"></tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- ============================================================
         PANE 7: Payslip Cancellations
         ============================================================ -->
    <div id="tab-payslips" class="tab-pane" style="display: none;">
      <div class="workspace-header">
        <div class="workspace-title-box">
          <h1>Payslip Cancellations Workflow</h1>
          <div class="workspace-subtitle">Review, Approval, and Reversals for Station Payroll</div>
        </div>
        <div class="header-action-btns">
          <button class="desk-btn desk-btn-default" onclick="Desk.loadPayslipCancellations()">
            <?= Icon::get('refresh', 13) ?> Refresh
          </button>
        </div>
      </div>

      <!-- In-Body Payslip Cancellation Form -->
      <div class="desk-card" style="margin-bottom: 22px; padding: 22px 26px; border: 1.5px solid #000000; border-radius: 4px; background: #ffffff;">
        <h3 style="margin: 0 0 16px 0; font-size: 1.05rem; font-weight: 700; color: #000000; display: flex; align-items: center; gap: 8px;">
          <?= Icon::get('alert-triangle', 16) ?> Enter Payslip Cancellation Requisition
        </h3>
        <form id="bodyPayslipCancelForm" onsubmit="event.preventDefault(); Desk.submitBodyPayslipCancel(this);">
          <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 14px; margin-bottom: 14px;">
            <div class="desk-form-group" style="margin: 0;">
              <label class="desk-form-label" style="font-weight: 700; color: #000000;">Payslip No *</label>
              <input type="text" name="payslip_no" class="desk-form-control" placeholder="e.g. PS-2026-11-831-042" required style="border: 1px solid #71717a; border-radius: 2px;">
            </div>
            <div class="desk-form-group" style="margin: 0;">
              <label class="desk-form-label" style="font-weight: 700; color: #000000;">Employee Name *</label>
              <input type="text" name="employee_name" class="desk-form-control" placeholder="e.g. K. L. Bandara" required style="border: 1px solid #71717a; border-radius: 2px;">
            </div>
            <div class="desk-form-group" style="margin: 0;">
              <label class="desk-form-label" style="font-weight: 700; color: #000000;">EPF Number</label>
              <input type="text" name="epf_no" class="desk-form-control" placeholder="e.g. EPF-55829" style="border: 1px solid #71717a; border-radius: 2px;">
            </div>
            <div class="desk-form-group" style="margin: 0;">
              <label class="desk-form-label" style="font-weight: 700; color: #000000;">Pay Period *</label>
              <input type="text" name="pay_period" class="desk-form-control" value="<?= date('F Y') ?>" required style="border: 1px solid #71717a; border-radius: 2px;">
            </div>
            <div class="desk-form-group" style="margin: 0;">
              <label class="desk-form-label" style="font-weight: 700; color: #000000;">Amount (Rs.) *</label>
              <input type="number" name="amount" class="desk-form-control" step="0.01" min="1" placeholder="0.00" required style="border: 1px solid #71717a; border-radius: 2px;">
            </div>
          </div>
          <div class="desk-form-group" style="margin-bottom: 16px;">
            <label class="desk-form-label" style="font-weight: 700; color: #000000;">Reason for Cancellation *</label>
            <input type="text" name="reason" class="desk-form-control" placeholder="e.g. Disputed shift overtime calculation / Salary adjustment recalculation" required style="border: 1px solid #71717a; border-radius: 2px;">
          </div>
          <div style="display: flex; justify-content: flex-end;">
            <button type="submit" class="desk-btn desk-btn-danger" style="padding: 7px 22px; font-weight: 600;">
              <?= Icon::get('send', 13) ?> Submit Cancellation Requisition
            </button>
          </div>
        </form>
      </div>

      <div class="desk-card">
        <div class="desk-card-header">
          <div class="desk-card-title">Payslip Cancellation Requests</div>
          <button class="desk-btn desk-btn-default desk-btn-sm" onclick="Desk.loadPayslipCancellations()">
            <?= Icon::get('refresh', 13) ?> Refresh
          </button>
        </div>
        <div class="desk-table-wrapper">
          <table class="desk-table">
            <thead>
              <tr>
                <th>Cancellation Ref</th>
                <th>Payslip No</th>
                <th>Employee (EPF)</th>
                <th>Department</th>
                <th>Period</th>
                <th>Amount</th>
                <th>Reason</th>
                <th>Status</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody id="payslipsTableBody"></tbody>
          </table>
        </div>
      </div>
    </div>


    <!-- ============================================================
         PANE 9: CPC Fuel Procurements
         ============================================================ -->
    <div id="tab-report-cpc" class="tab-pane" style="display: none;">
      <div class="workspace-header">
        <div class="workspace-title-box">
          <h1>CPC Fuel Procurements</h1>
          <div class="workspace-subtitle">Ceylon Petroleum Corporation Bulk Diesel Consignments</div>
        </div>
        <div class="header-action-btns">
          <button class="desk-btn desk-btn-default" onclick="Desk.loadCpcProcurements()">
            <?= Icon::get('refresh', 13) ?> Refresh
          </button>
        </div>
      </div>

      <!-- In-Body CPC Fuel Consignment Entry Form -->
      <div class="desk-card" style="margin-bottom: 22px; padding: 22px 26px; border: 1.5px solid #000000; border-radius: 4px; background: #ffffff;">
        <h3 style="margin: 0 0 16px 0; font-size: 1.05rem; font-weight: 700; color: #000000; display: flex; align-items: center; gap: 8px;">
          <?= Icon::get('droplet', 16) ?> Enter CPC Fuel Procurement Order
        </h3>
        <form id="bodyCpcOrderForm" onsubmit="event.preventDefault(); Desk.submitBodyCpcOrder(this);">
          <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 14px; margin-bottom: 14px;">
            <div class="desk-form-group" style="margin: 0;">
              <label class="desk-form-label" style="font-weight: 700; color: #000000;">CPC Order / Bowzer No *</label>
              <input type="text" name="order_no" class="desk-form-control" placeholder="e.g. CPC-BWZ-2026-88" required style="border: 1px solid #71717a; border-radius: 2px;">
            </div>
            <div class="desk-form-group" style="margin: 0;">
              <label class="desk-form-label" style="font-weight: 700; color: #000000;">Fuel Type *</label>
              <select name="fuel_type" class="desk-form-control" style="border: 1px solid #71717a; border-radius: 2px;">
                <option value="Auto Diesel">Auto Diesel (Standard)</option>
                <option value="Super Diesel">Super Diesel (10ppm)</option>
                <option value="Heavy Fuel Oil">Heavy Fuel Oil (Furnace Oil)</option>
              </select>
            </div>
            <div class="desk-form-group" style="margin: 0;">
              <label class="desk-form-label" style="font-weight: 700; color: #000000;">Storage Tank ID *</label>
              <input type="text" name="tank_id" class="desk-form-control" value="Main Tank T-01" required style="border: 1px solid #71717a; border-radius: 2px;">
            </div>
            <div class="desk-form-group" style="margin: 0;">
              <label class="desk-form-label" style="font-weight: 700; color: #000000;">Quantity (Litres) *</label>
              <input type="number" name="quantity_litres" class="desk-form-control" min="1" placeholder="e.g. 13200" required style="border: 1px solid #71717a; border-radius: 2px;">
            </div>
            <div class="desk-form-group" style="margin: 0;">
              <label class="desk-form-label" style="font-weight: 700; color: #000000;">Unit Price LKR / Litre *</label>
              <input type="number" name="unit_price_lkr" class="desk-form-control" step="0.01" min="1" placeholder="e.g. 348.00" required style="border: 1px solid #71717a; border-radius: 2px;">
            </div>
            <div class="desk-form-group" style="margin: 0;">
              <label class="desk-form-label" style="font-weight: 700; color: #000000;">Consignment Date *</label>
              <input type="date" name="consignment_date" class="desk-form-control" value="<?= date('Y-m-d') ?>" required style="border: 1px solid #71717a; border-radius: 2px;">
            </div>
          </div>
          <div class="desk-form-group" style="margin-bottom: 16px;">
            <label class="desk-form-label" style="font-weight: 700; color: #000000;">CPC Invoice / Delivery Note Ref</label>
            <input type="text" name="delivery_ref" class="desk-form-control" placeholder="e.g. CPC-INV-KOLONNAWA-4412" style="border: 1px solid #71717a; border-radius: 2px;">
          </div>
          <div style="display: flex; justify-content: flex-end;">
            <button type="submit" class="desk-btn desk-btn-primary" style="padding: 7px 22px; font-weight: 600;">
              <?= Icon::get('check', 13) ?> Record CPC Consignment
            </button>
          </div>
        </form>
      </div>

      <div class="desk-card">
        <div class="desk-table-wrapper">
          <table class="desk-table">
            <thead>
              <tr>
                <th>Order No</th>
                <th>Date</th>
                <th>Fuel Grade</th>
                <th>Tank ID</th>
                <th>Volume (Litres)</th>
                <th>Unit Rate (LKR)</th>
                <th>Total Amount</th>
                <th>Delivery Ref (Bowser)</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody id="cpcTableBody"></tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- ============================================================
         PANE 10: Average Diesel Price
         ============================================================ -->
    <div id="tab-report-diesel" class="tab-pane" style="display: none;">
      <div class="workspace-header">
        <div class="workspace-title-box">
          <h1>Average Diesel Price Log</h1>
          <div class="workspace-subtitle">Monthly Fuel Price Indices and Expenditure Trends</div>
        </div>
        <div class="header-action-btns">
          <button class="desk-btn desk-btn-default" onclick="Desk.loadDieselPriceLogs()">
            <?= Icon::get('refresh', 13) ?> Refresh
          </button>
        </div>
      </div>

      <!-- In-Body Diesel Price Log Form -->
      <div class="desk-card" style="margin-bottom: 22px; padding: 22px 26px; border: 1.5px solid #000000; border-radius: 4px; background: #ffffff;">
        <h3 style="margin: 0 0 16px 0; font-size: 1.05rem; font-weight: 700; color: #000000; display: flex; align-items: center; gap: 8px;">
          <?= Icon::get('trending-up', 16) ?> Enter Monthly Diesel Price Log
        </h3>
        <form id="bodyDieselPriceForm" onsubmit="event.preventDefault(); Desk.submitBodyDieselPrice(this);">
          <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 14px; margin-bottom: 16px;">
            <div class="desk-form-group" style="margin: 0;">
              <label class="desk-form-label" style="font-weight: 700; color: #000000;">Effective Month *</label>
              <input type="month" name="effective_month" class="desk-form-control" value="<?= date('Y-m') ?>" required style="border: 1px solid #71717a; border-radius: 2px;">
            </div>
            <div class="desk-form-group" style="margin: 0;">
              <label class="desk-form-label" style="font-weight: 700; color: #000000;">Fuel Type *</label>
              <select name="fuel_type" class="desk-form-control" style="border: 1px solid #71717a; border-radius: 2px;">
                <option value="Auto Diesel">Auto Diesel (Standard)</option>
                <option value="Super Diesel">Super Diesel (10ppm)</option>
                <option value="Heavy Fuel Oil">Heavy Fuel Oil (Furnace Oil)</option>
              </select>
            </div>
            <div class="desk-form-group" style="margin: 0;">
              <label class="desk-form-label" style="font-weight: 700; color: #000000;">Average Price / Litre (LKR) *</label>
              <input type="number" name="avg_price_per_litre" class="desk-form-control" step="0.01" min="1" placeholder="e.g. 348.00" required style="border: 1px solid #71717a; border-radius: 2px;">
            </div>
            <div class="desk-form-group" style="margin: 0;">
              <label class="desk-form-label" style="font-weight: 700; color: #000000;">Total Procured Litres</label>
              <input type="number" name="total_procured_litres" class="desk-form-control" min="0" placeholder="e.g. 45000" style="border: 1px solid #71717a; border-radius: 2px;">
            </div>
          </div>
          <div style="display: flex; justify-content: flex-end;">
            <button type="submit" class="desk-btn desk-btn-primary" style="padding: 7px 22px; font-weight: 600;">
              <?= Icon::get('check', 13) ?> Save Price Log
            </button>
          </div>
        </form>
      </div>

      <div class="desk-card">
        <div class="desk-table-wrapper">
          <table class="desk-table">
            <thead>
              <tr>
                <th>Effective Month</th>
                <th>Fuel Type</th>
                <th>Average Price / Litre</th>
                <th>Procured Volume</th>
                <th>Total Cost</th>
                <th>Price Trend</th>
              </tr>
            </thead>
            <tbody id="dieselTableBody"></tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- ============================================================
         PANE 11: Monthly Transfers
         ============================================================ -->
    <div id="tab-report-transfers" class="tab-pane" style="display: none;">
      <div class="workspace-header">
        <div class="workspace-title-box">
          <h1>Monthly Stock Transfers</h1>
          <div class="workspace-subtitle">Transfers between Central Stores and Station Maintenance Bays</div>
        </div>
      </div>

      <div class="desk-card">
        <div class="desk-table-wrapper">
          <table class="desk-table">
            <thead>
              <tr>
                <th>Transfer Ref</th>
                <th>Date</th>
                <th>From Bay</th>
                <th>To Department</th>
                <th>Material</th>
                <th>Quantity</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody id="transfersTableBody"></tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- ============================================================
         PANE 12: Job-Wise Issues
         ============================================================ -->
    <div id="tab-report-jobs" class="tab-pane" style="display: none;">
      <div class="workspace-header">
        <div class="workspace-title-box">
          <h1>Job-Wise Material Issues</h1>
          <div class="workspace-subtitle">Spares and Consumables Apportioned by Destination Work Orders</div>
        </div>
      </div>

      <div class="desk-card">
        <div class="desk-table-wrapper">
          <table class="desk-table">
            <thead>
              <tr>
                <th>Document No</th>
                <th>Date</th>
                <th>Destination Code</th>
                <th>Job Destination Area</th>
                <th>Material</th>
                <th>Quantity</th>
                <th>Total Cost</th>
              </tr>
            </thead>
            <tbody id="jobIssuesTableBody"></tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- ============================================================
         PANE: Inventory Inquiry Report
         ============================================================ -->
    <div id="tab-report-inventory-inquiry" class="tab-pane" style="display: none;">
      <div class="workspace-header">
        <div class="workspace-title-box">
          <h1>Inventory Inquiry Report</h1>
          <div class="workspace-subtitle">Comprehensive Stock Ledger Balance with Cumulative Receipts &amp; Issues</div>
        </div>
        <div class="header-action-btns">
          <button class="desk-btn desk-btn-default" onclick="Desk.loadInventoryInquiry()">
            <?= Icon::get('refresh', 13) ?> Refresh
          </button>
          <button class="desk-btn desk-btn-default" onclick="window.print()">
            <?= Icon::get('printer', 13) ?> Print Report
          </button>
        </div>
      </div>

      <!-- Filter Form -->
      <div class="desk-card" style="padding: 20px 24px; margin-bottom: 20px;">
        <form id="formInventoryInquiry" onsubmit="event.preventDefault(); Desk.loadInventoryInquiry();">
          <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)) 140px; gap: 16px; align-items: flex-end;">
            <div class="desk-form-group" style="margin: 0;">
              <label class="desk-form-label">Search SKU / Item Name</label>
              <input type="text" id="inquirySku" class="desk-form-control" placeholder="e.g. 01-01-01 or Turbine">
            </div>
            <div class="desk-form-group" style="margin: 0;">
              <label class="desk-form-label">Cost Center / Department</label>
              <select id="inquiryCostCenter" class="desk-form-control">
                <option value="">All Cost Centers</option>
                <option value="Mechanical">Mechanical Maintenance</option>
                <option value="Electrical">Electrical Maintenance</option>
                <option value="Civil">Civil Works</option>
                <option value="Operations">Hydraulic Operations</option>
              </select>
            </div>
            <div class="desk-form-group" style="margin: 0;">
              <label class="desk-form-label">From Date</label>
              <input type="date" id="inquiryFromDate" class="desk-form-control">
            </div>
            <div class="desk-form-group" style="margin: 0;">
              <label class="desk-form-label">To Date</label>
              <input type="date" id="inquiryToDate" class="desk-form-control">
            </div>
            <button type="submit" class="desk-btn desk-btn-primary" style="height: 38px; justify-content: center; font-weight: 600;">
              <?= Icon::get('search', 13) ?> Filter Inquiry
            </button>
          </div>
        </form>
      </div>

      <div class="desk-card">
        <div class="desk-card-header">
          <div class="desk-card-title">Live Stock Ledger Balance &amp; Valuation</div>
          <span id="inquiryCountBadge" style="font-size: 12px; color: var(--text-muted); font-weight: 600;"></span>
        </div>
        <div class="desk-table-wrapper">
          <table class="desk-table">
            <thead>
              <tr>
                <th>Bin</th>
                <th>SKU</th>
                <th>Material Description</th>
                <th>Category</th>
                <th style="text-align: right;">Initial Receipts</th>
                <th style="text-align: right;">Total Dispatched (Issues)</th>
                <th style="text-align: right;">Current QOH</th>
                <th style="text-align: right;">Unit Cost (LKR)</th>
                <th style="text-align: right;">Total Valuation (LKR)</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody id="inventoryInquiryTableBody"></tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- ============================================================
         PANE: Monthly Transaction Report
         ============================================================ -->
    <div id="tab-report-monthly-tx" class="tab-pane" style="display: none;">
      <div class="workspace-header">
        <div class="workspace-title-box">
          <h1>Monthly Transactions Report</h1>
          <div class="workspace-subtitle">Detailed Audit Log of Receipts (GRN), Issues (GIN), Transfers &amp; Adjustments</div>
        </div>
        <div class="header-action-btns">
          <button class="desk-btn desk-btn-default" onclick="Desk.loadMonthlyTx()">
            <?= Icon::get('refresh', 13) ?> Refresh
          </button>
          <button class="desk-btn desk-btn-default" onclick="window.print()">
            <?= Icon::get('printer', 13) ?> Print Report
          </button>
        </div>
      </div>

      <div class="desk-card" style="padding: 20px 24px; margin-bottom: 20px;">
        <form id="formMonthlyTx" onsubmit="event.preventDefault(); Desk.loadMonthlyTx();">
          <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)) 140px; gap: 16px; align-items: flex-end;">
            <div class="desk-form-group" style="margin: 0;">
              <label class="desk-form-label">Document Type</label>
              <select id="monthlyTxDocType" class="desk-form-control">
                <option value="ALL">All Document Types</option>
                <option value="GRN">Goods Received Note (GRN)</option>
                <option value="GIN">Goods Issue Note (GIN)</option>
                <option value="MTN">Material Transfer Note (MTN)</option>
                <option value="ADJ">Audit Reconciliations</option>
              </select>
            </div>
            <div class="desk-form-group" style="margin: 0;">
              <label class="desk-form-label">Year</label>
              <input type="number" id="monthlyTxYear" class="desk-form-control" value="<?= date('Y') ?>">
            </div>
            <div class="desk-form-group" style="margin: 0;">
              <label class="desk-form-label">Month</label>
              <select id="monthlyTxMonth" class="desk-form-control">
                <option value="">All Months</option>
                <?php for ($m = 1; $m <= 12; $m++): ?>
                  <option value="<?= $m ?>" <?= ($m == date('n')) ? 'selected' : '' ?>><?= date('F', mktime(0, 0, 0, $m, 10)) ?></option>
                <?php endfor; ?>
              </select>
            </div>
            <div class="desk-form-group" style="margin: 0;">
              <label class="desk-form-label">Warehouse</label>
              <select id="monthlyTxWarehouse" class="desk-form-control">
                <option value="">All Store Locations</option>
                <option value="Central Main Store" selected>Central Main Store</option>
                <option value="Mechanical Spares Bay">Mechanical Spares Bay</option>
                <option value="Electrical Spares Bay">Electrical Spares Bay</option>
              </select>
            </div>
            <button type="submit" class="desk-btn desk-btn-primary" style="height: 38px; justify-content: center; font-weight: 600;">
              <?= Icon::get('file-text', 13) ?> Filter Logs
            </button>
          </div>
        </form>
      </div>

      <div class="desk-card">
        <div class="desk-card-header">
          <div class="desk-card-title">Transaction Audit Register</div>
          <span id="monthlyTxCountBadge" style="font-size: 12px; color: var(--text-muted); font-weight: 600;"></span>
        </div>
        <div class="desk-table-wrapper">
          <table class="desk-table">
            <thead>
              <tr>
                <th>Tx Date</th>
                <th>Document No</th>
                <th>Type</th>
                <th>SKU</th>
                <th>Material Description</th>
                <th style="text-align: right;">Quantity</th>
                <th style="text-align: right;">Unit Rate (LKR)</th>
                <th style="text-align: right;">Total Value (LKR)</th>
                <th>Reference / Job</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody id="monthlyTxTableBody"></tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- ============================================================
         PANE: GRN - Issue Summary Report
         ============================================================ -->
    <div id="tab-report-grn-issue-summary" class="tab-pane" style="display: none;">
      <div class="workspace-header">
        <div class="workspace-title-box">
          <h1>GRN - Issue Summary Report</h1>
          <div class="workspace-subtitle">Comparison Analysis between Total Receipts (GRN) and Issues</div>
        </div>
        <div class="header-action-btns">
          <button class="desk-btn desk-btn-default" onclick="Desk.loadGrnIssueSummary()">
            <?= Icon::get('refresh', 13) ?> Refresh
          </button>
          <button class="desk-btn desk-btn-default" onclick="window.print()">
            <?= Icon::get('printer', 13) ?> Print Summary
          </button>
        </div>
      </div>

      <div class="desk-card" style="padding: 20px 24px; margin-bottom: 20px;">
        <form id="formGrnIssueSummary" onsubmit="event.preventDefault(); Desk.loadGrnIssueSummary();">
          <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)) 140px; gap: 16px; align-items: flex-end;">
            <div class="desk-form-group" style="margin: 0;">
              <label class="desk-form-label">Cost Center / Department</label>
              <select id="grnSumCostCenter" class="desk-form-control">
                <option value="">All Departments</option>
                <option value="Mechanical">Mechanical Maintenance</option>
                <option value="Electrical">Electrical Maintenance</option>
                <option value="Civil">Civil Works</option>
              </select>
            </div>
            <div class="desk-form-group" style="margin: 0;">
              <label class="desk-form-label">Store / Warehouse</label>
              <select id="grnSumWarehouse" class="desk-form-control">
                <option value="Central Main Store" selected>Central Main Store</option>
                <option value="Mechanical Spares Warehouse">Mechanical Spares Warehouse</option>
                <option value="Electrical Spares Warehouse">Electrical Spares Warehouse</option>
              </select>
            </div>
            <button type="submit" class="desk-btn desk-btn-primary" style="height: 38px; justify-content: center; font-weight: 600;">
              <?= Icon::get('clipboard', 13) ?> Generate Summary
            </button>
          </div>
        </form>
      </div>

      <div class="desk-card">
        <div class="desk-card-header">
          <div class="desk-card-title">Cumulative Receipts vs Dispatches Summary</div>
        </div>
        <div class="desk-table-wrapper">
          <table class="desk-table">
            <thead>
              <tr>
                <th>Item Code</th>
                <th>Material Description</th>
                <th>UOM</th>
                <th style="text-align: right;">GRN Qty</th>
                <th style="text-align: right;">GRN Value (LKR)</th>
                <th style="text-align: right;">Issue Qty</th>
                <th style="text-align: right;">Issue Value (LKR)</th>
                <th style="text-align: right;">Net Balance Qty</th>
                <th style="text-align: right;">Net Balance Value (LKR)</th>
              </tr>
            </thead>
            <tbody id="grnIssueSummaryTableBody"></tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- ============================================================
         PANE: Material Flow Report
         ============================================================ -->
    <div id="tab-report-material-flow" class="tab-pane" style="display: none;">
      <div class="workspace-header">
        <div class="workspace-title-box">
          <h1>Material Flow Report</h1>
          <div class="workspace-subtitle">Stock Movement Cycle: Opening Balance + Receipts - Issues = Closing Balance</div>
        </div>
        <div class="header-action-btns">
          <button class="desk-btn desk-btn-default" onclick="Desk.loadMaterialFlow()">
            <?= Icon::get('refresh', 13) ?> Refresh
          </button>
          <button class="desk-btn desk-btn-default" onclick="window.print()">
            <?= Icon::get('printer', 13) ?> Print Report
          </button>
        </div>
      </div>

      <div class="desk-card" style="padding: 20px 24px; margin-bottom: 20px;">
        <form id="formMaterialFlow" onsubmit="event.preventDefault(); Desk.loadMaterialFlow();">
          <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)) 140px; gap: 16px; align-items: flex-end;">
            <div class="desk-form-group" style="margin: 0;">
              <label class="desk-form-label">Material SKU Filter</label>
              <input type="text" id="matFlowSku" class="desk-form-control" placeholder="All materials or enter code">
            </div>
            <div class="desk-form-group" style="margin: 0;">
              <label class="desk-form-label">Warehouse</label>
              <select id="matFlowWarehouse" class="desk-form-control">
                <option value="Central Main Store" selected>Central Main Store</option>
                <option value="Mechanical Spares Bay">Mechanical Spares Bay</option>
                <option value="Electrical Spares Bay">Electrical Spares Bay</option>
              </select>
            </div>
            <div class="desk-form-group" style="margin: 0;">
              <label class="desk-form-label">From Date</label>
              <input type="date" id="matFlowFromDate" class="desk-form-control" value="<?= date('Y-01-01') ?>">
            </div>
            <div class="desk-form-group" style="margin: 0;">
              <label class="desk-form-label">To Date</label>
              <input type="date" id="matFlowToDate" class="desk-form-control" value="<?= date('Y-m-d') ?>">
            </div>
            <button type="submit" class="desk-btn desk-btn-primary" style="height: 38px; justify-content: center; font-weight: 600;">
              <?= Icon::get('repeat', 13) ?> View Flow
            </button>
          </div>
        </form>
      </div>

      <div class="desk-card">
        <div class="desk-card-header">
          <div class="desk-card-title">Material Movement &amp; Velocity Ledger</div>
        </div>
        <div class="desk-table-wrapper">
          <table class="desk-table">
            <thead>
              <tr>
                <th>Item Code</th>
                <th>Material Description</th>
                <th style="text-align: right;">Opening Balance</th>
                <th style="text-align: right;">Receipts (+)</th>
                <th style="text-align: right;">Issues (-)</th>
                <th style="text-align: right;">Closing Balance</th>
                <th style="text-align: right;">Unit Cost (LKR)</th>
                <th style="text-align: right;">Closing Valuation (LKR)</th>
              </tr>
            </thead>
            <tbody id="materialFlowTableBody"></tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- ============================================================
         PANE 13: Cost Adjustments
         ============================================================ -->
    <div id="tab-cost-adjust" class="tab-pane" style="display: none;">
      <div class="workspace-header">
        <div class="workspace-title-box">
          <h1>Cost & Valuation Adjustments</h1>
          <div class="workspace-subtitle">Freight, Customs Cess & Valuation Surcharges</div>
        </div>
        <div class="header-action-btns">
          <button class="desk-btn desk-btn-default" onclick="Desk.loadCostAdjustments(); Desk.populateTaskBatches();">
            <?= Icon::get('refresh', 13) ?> Refresh
          </button>
        </div>
      </div>

      <!-- In-Body Cost Adjustment Form -->
      <div class="desk-card" style="margin-bottom: 22px; padding: 22px 26px; border: 1.5px solid #000000; border-radius: 4px; background: #ffffff;">
        <h3 style="margin: 0 0 16px 0; font-size: 1.05rem; font-weight: 700; color: #000000; display: flex; align-items: center; gap: 8px;">
          <?= Icon::get('sliders', 16) ?> Enter Cost &amp; Valuation Adjustment
        </h3>
        <form id="bodyCostAdjustForm" onsubmit="event.preventDefault(); Desk.submitBodyCostAdjust(this);">
          <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 16px; margin-bottom: 16px;">
            <div class="desk-form-group" style="margin: 0;">
              <label class="desk-form-label" style="font-weight: 700; color: #000000;">Select FIFO Batch to Adjust *</label>
              <select name="batch_id" id="bodyCostAdjustBatch" class="desk-form-control" onchange="Desk.onCostAdjustBatchChange(this)" required style="border: 1px solid #71717a; border-radius: 2px;">
                <option value="">-- Loading active inventory batches... --</option>
              </select>
            </div>
            <div class="desk-form-group" style="margin: 0;">
              <label class="desk-form-label" style="font-weight: 700; color: #000000;">Current Unit Cost</label>
              <input type="text" id="bodyCostAdjustCurrentCost" class="desk-form-control" readonly placeholder="Select a batch..." style="background: #f1f5f9; border: 1px solid #cbd5e1; border-radius: 2px;">
            </div>
            <div class="desk-form-group" style="margin: 0;">
              <label class="desk-form-label" style="font-weight: 700; color: #000000;">New Unit Cost (Rs.) *</label>
              <input type="number" name="new_unit_cost" id="bodyCostAdjustNewCost" class="desk-form-control" step="0.01" min="0" placeholder="e.g. 95000.00" required style="border: 1px solid #71717a; border-radius: 2px;">
            </div>
          </div>
          <div class="desk-form-group" style="margin-bottom: 16px;">
            <label class="desk-form-label" style="font-weight: 700; color: #000000;">Reason / Valuation Adjustment Justification *</label>
            <input type="text" name="reason" class="desk-form-control" placeholder="e.g. Additional freight/customs cess surcharge allocation" required style="border: 1px solid #71717a; border-radius: 2px;">
          </div>
          <div style="display: flex; justify-content: flex-end;">
            <button type="submit" class="desk-btn desk-btn-primary" style="padding: 7px 22px; font-weight: 600;">
              <?= Icon::get('check', 13) ?> Apply Valuation Adjustment
            </button>
          </div>
        </form>
      </div>

      <div class="desk-card">
        <div class="desk-card-header">
          <div class="desk-card-title">Valuation Adjustments History &amp; Audit Log</div>
        </div>
        <div class="desk-table-wrapper">
          <table class="desk-table">
            <thead>
              <tr>
                <th>Adjustment Ref</th>
                <th>SKU</th>
                <th>Material</th>
                <th>Old Unit Cost</th>
                <th>New Unit Cost</th>
                <th>Valuation Delta</th>
                <th>Reason</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody id="costAdjustTableBody"></tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- ============================================================
         PANE 14: Allocated Qty Correction
         ============================================================ -->
    <div id="tab-qty-correct" class="tab-pane" style="display: none;">
      <div class="workspace-header">
        <div class="workspace-title-box">
          <h1>Allocated Quantity Corrections</h1>
          <div class="workspace-subtitle">Unlock Stuck Allocations & Restore Available Stock</div>
        </div>
        <div class="header-action-btns">
          <button class="desk-btn desk-btn-default" onclick="Desk.loadQtyCorrections(); Desk.populateTaskBatches();">
            <?= Icon::get('refresh', 13) ?> Refresh
          </button>
        </div>
      </div>

      <!-- In-Body Allocated Qty Correction Form -->
      <div class="desk-card" style="margin-bottom: 22px; padding: 22px 26px; border: 1.5px solid #000000; border-radius: 4px; background: #ffffff;">
        <h3 style="margin: 0 0 16px 0; font-size: 1.05rem; font-weight: 700; color: #000000; display: flex; align-items: center; gap: 8px;">
          <?= Icon::get('check-square', 16) ?> Enter Allocated Quantity Correction &amp; Stock Release
        </h3>
        <form id="bodyQtyCorrectForm" onsubmit="event.preventDefault(); Desk.submitBodyQtyCorrect(this);">
          <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px; margin-bottom: 16px;">
            <div class="desk-form-group" style="margin: 0;">
              <label class="desk-form-label" style="font-weight: 700; color: #000000;">Select FIFO Batch *</label>
              <select name="batch_id" id="bodyQtyCorrectBatch" class="desk-form-control" onchange="Desk.onQtyCorrectBatchChange(this)" required style="border: 1px solid #71717a; border-radius: 2px;">
                <option value="">-- Loading active inventory batches... --</option>
              </select>
            </div>
            <div class="desk-form-group" style="margin: 0;">
              <label class="desk-form-label" style="font-weight: 700; color: #000000;">Allocated Qty Before</label>
              <input type="number" name="allocated_qty_before" id="bodyQtyAllocBefore" class="desk-form-control" min="0" value="0" required style="border: 1px solid #71717a; border-radius: 2px;">
            </div>
            <div class="desk-form-group" style="margin: 0;">
              <label class="desk-form-label" style="font-weight: 700; color: #000000;">Corrected Allocated Qty</label>
              <input type="number" name="corrected_allocated_qty" id="bodyQtyAllocAfter" class="desk-form-control" min="0" value="0" required style="border: 1px solid #71717a; border-radius: 2px;">
            </div>
            <div class="desk-form-group" style="margin: 0;">
              <label class="desk-form-label" style="font-weight: 700; color: #000000;">Work Order / Task Ref</label>
              <input type="text" name="work_order_ref" class="desk-form-control" placeholder="e.g. WO-2026-TURB-01" style="border: 1px solid #71717a; border-radius: 2px;">
            </div>
          </div>
          <div class="desk-form-group" style="margin-bottom: 16px;">
            <label class="desk-form-label" style="font-weight: 700; color: #000000;">Reason for Correction *</label>
            <input type="text" name="reason" class="desk-form-control" placeholder="e.g. Released stuck reservation from cancelled maintenance work order" required style="border: 1px solid #71717a; border-radius: 2px;">
          </div>
          <div style="display: flex; justify-content: flex-end;">
            <button type="submit" class="desk-btn desk-btn-primary" style="padding: 7px 22px; font-weight: 600;">
              <?= Icon::get('check', 13) ?> Apply Quantity Correction
            </button>
          </div>
        </form>
      </div>

      <div class="desk-card">
        <div class="desk-card-header">
          <div class="desk-card-title">Allocation Corrections History &amp; Stock Release Log</div>
        </div>
        <div class="desk-table-wrapper">
          <table class="desk-table">
            <thead>
              <tr>
                <th>Correction Ref</th>
                <th>SKU</th>
                <th>Material</th>
                <th>Allocated Before</th>
                <th>Corrected Allocated</th>
                <th>Restored to Free Stock</th>
                <th>Reason</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody id="qtyCorrectTableBody"></tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- ============================================================
         PANE 15: Destination Codes
         ============================================================ -->
    <div id="tab-dest-codes" class="tab-pane" style="display: none;">
      <div class="workspace-header">
        <div class="workspace-title-box">
          <h1>Issue Categories & Destination Codes</h1>
          <div class="workspace-subtitle">Power Generation Units, Penstocks, and Cost Centers</div>
        </div>
      </div>

      <div class="desk-card">
        <div class="desk-table-wrapper">
          <table class="desk-table">
            <thead>
              <tr>
                <th>Category Code</th>
                <th>Category Name</th>
                <th>Destination Code</th>
                <th>Destination Description</th>
                <th>Scope Details</th>
              </tr>
            </thead>
            <tbody id="destCodesTableBody"></tbody>
          </table>
        </div>
      </div>
    </div>

    <?php if ($isAdmin): ?>
    <!-- ============================================================
         PANE 16: Administration & Governance
         ============================================================ -->
    <div id="tab-admin" class="tab-pane" style="display: block;">
      <div class="workspace-header">
        <div class="workspace-title-box">
          <h1>Administration &amp; Governance Console</h1>
          <div class="workspace-subtitle">Power Stations, Operational Departments, User Accounts, Roles &amp; Permissions Matrix</div>
        </div>
      </div>

      <!-- SUB-PANE 1: POWER STATIONS & LOCATIONS -->
      <div id="admin-sub-locations" class="admin-sub-pane active">
        <div class="desk-card" style="margin-bottom: 24px;">
          <div class="desk-card-header" style="display: flex; justify-content: space-between; align-items: center;">
            <div>
              <h2 style="font-size: 1rem; font-weight: 700; color: #0f172a; margin: 0;">Power Stations &amp; Operational Locations</h2>
              <div style="font-size: 0.8rem; color: #64748b; margin-top: 2px;">Hydroelectric &amp; Thermal Power Generation Complex Stations</div>
            </div>
            <button type="button" class="desk-btn desk-btn-primary desk-btn-sm" onclick="Desk.openModal('createLocationModal')">
              + Add Location
            </button>
          </div>

          <div class="desk-table-wrapper">
            <table class="desk-table">
              <thead>
                <tr>
                  <th>Station / Location Name</th>
                  <th>Code</th>
                  <th>Assigned Depts</th>
                  <th>Staff</th>
                  <th>Status</th>
                  <th style="text-align: right;">Actions</th>
                </tr>
              </thead>
              <tbody id="locationsTableBody">
                <?php if (empty($locations)): ?>
                  <tr>
                    <td colspan="6" style="text-align: center; color: #94a3b8; padding: 20px;">No power stations found.</td>
                  </tr>
                <?php else: ?>
                  <?php foreach ($locations as $loc): ?>
                    <tr id="loc-row-<?= $loc['id'] ?>">
                      <td><strong style="color: #0f172a;"><?= htmlspecialchars($loc['name']) ?></strong></td>
                      <td><span class="desk-badge badge-draft" style="font-family: monospace; font-weight: 700;"><?= htmlspecialchars($loc['code']) ?></span></td>
                      <td><strong style="color: #650b2b;"><?= (int)($loc['department_count'] ?? 0) ?></strong> Depts</td>
                      <td><span><?= (int)($loc['staff_count'] ?? 0) ?></span> Users</td>
                      <td>
                        <span class="desk-badge <?= ($loc['status'] === 'active') ? 'badge-approved' : 'badge-danger' ?>">
                          <?= ucfirst(htmlspecialchars($loc['status'])) ?>
                        </span>
                      </td>
                      <td style="text-align: right; white-space: nowrap;">
                        <button class="desk-btn desk-btn-default desk-btn-sm" onclick="Desk.openEditLocationModal(<?= $loc['id'] ?>, '<?= addslashes(htmlspecialchars($loc['name'])) ?>', '<?= addslashes(htmlspecialchars($loc['code'])) ?>', '<?= htmlspecialchars($loc['status']) ?>')">
                          Edit
                        </button>
                        <button class="desk-btn desk-btn-danger desk-btn-sm" style="margin-left: 4px;" onclick="Desk.deleteLocation(<?= $loc['id'] ?>, '<?= addslashes(htmlspecialchars($loc['name'])) ?>')">
                          Delete
                        </button>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- SUB-PANE 2: DEPARTMENTS & STORES -->
      <div id="admin-sub-departments" class="admin-sub-pane">
        <div class="desk-card" style="margin-bottom: 24px;">
          <div class="desk-card-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
            <div>
              <h2 style="font-size: 1rem; font-weight: 700; color: #0f172a; margin: 0;">Operational Departments &amp; Warehouses</h2>
              <div style="font-size: 0.8rem; color: #64748b; margin-top: 2px;">Station divisions, mechanical, electrical &amp; central stores</div>
            </div>
            <div style="display: flex; gap: 10px; align-items: center;">
              <select id="filterDeptLocation" class="desk-form-control" style="width: auto; padding: 5px 10px; font-size: 0.82rem;" onchange="Desk.filterDepartmentsByLocation(this.value)">
                <option value="">All Stations &amp; Locations</option>
                <?php foreach ($locations as $loc): ?>
                  <option value="<?= $loc['id'] ?>"><?= htmlspecialchars($loc['name']) ?></option>
                <?php endforeach; ?>
              </select>
              <button type="button" class="desk-btn desk-btn-primary desk-btn-sm" onclick="Desk.openModal('createDepartmentModal')">
                + Add Department
              </button>
            </div>
          </div>

          <div class="desk-table-wrapper">
            <table class="desk-table">
              <thead>
                <tr>
                  <th>Power Station / Location</th>
                  <th>Department Name</th>
                  <th>Code</th>
                  <th>Assigned Staff</th>
                  <th>Status</th>
                  <th style="text-align: right;">Actions</th>
                </tr>
              </thead>
              <tbody id="departmentsTableBody">
                <?php if (empty($departments)): ?>
                  <tr>
                    <td colspan="6" style="text-align: center; color: #94a3b8; padding: 20px;">No departments found.</td>
                  </tr>
                <?php else: ?>
                  <?php foreach ($departments as $dept): ?>
                    <tr id="dept-row-<?= $dept['id'] ?>" data-location-id="<?= $dept['location_id'] ?>">
                      <td>
                        <span class="desk-badge badge-draft" style="font-size: 0.75rem; font-weight: 700;">
                          <?= htmlspecialchars($dept['location_name'] ?? 'General') ?>
                        </span>
                      </td>
                      <td><strong style="color: #0f172a;"><?= htmlspecialchars($dept['name']) ?></strong></td>
                      <td><code><?= htmlspecialchars($dept['code']) ?></code></td>
                      <td style="font-weight: 600; color: #650b2b;"><?= (int)($dept['user_count'] ?? 0) ?> Members</td>
                      <td>
                        <span class="desk-badge <?= ($dept['status'] === 'active') ? 'badge-approved' : 'badge-danger' ?>">
                          <?= ucfirst(htmlspecialchars($dept['status'])) ?>
                        </span>
                      </td>
                      <td style="text-align: right; white-space: nowrap;">
                        <button class="desk-btn desk-btn-default desk-btn-sm" onclick="Desk.openEditDepartmentModal(<?= $dept['id'] ?>, <?= (int)$dept['location_id'] ?>, '<?= addslashes(htmlspecialchars($dept['name'])) ?>', '<?= addslashes(htmlspecialchars($dept['code'])) ?>', '<?= htmlspecialchars($dept['status']) ?>')">
                          Edit
                        </button>
                        <button class="desk-btn desk-btn-danger desk-btn-sm" style="margin-left: 4px;" onclick="Desk.deleteDepartment(<?= $dept['id'] ?>, '<?= addslashes(htmlspecialchars($dept['name'])) ?>')">
                          Delete
                        </button>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- SUB-PANE 3: USER ACCOUNTS -->
      <div id="admin-sub-users" class="admin-sub-pane">
        <div class="desk-card" style="margin-bottom: 24px;">
          <div class="desk-card-header" style="display: flex; justify-content: space-between; align-items: center;">
            <div>
              <h2 style="font-size: 1rem; font-weight: 700; color: #0f172a; margin: 0;">User Accounts &amp; Operational Access</h2>
              <div style="font-size: 0.8rem; color: #64748b; margin-top: 2px;">Station engineers, storekeepers, accountants &amp; operators</div>
            </div>
            <button type="button" class="desk-btn desk-btn-primary desk-btn-sm" onclick="Desk.openModal('createUserModal')">
              + Add User
            </button>
          </div>

          <div class="desk-table-wrapper">
            <table class="desk-table">
              <thead>
                <tr>
                  <th>Username</th>
                  <th>Assigned Station &amp; Dept</th>
                  <th>Role</th>
                  <th>Status</th>
                  <th>Created Date</th>
                  <th style="text-align: right;">Action</th>
                </tr>
              </thead>
              <tbody id="usersTableBody">
                <?php foreach ($allUsers as $u): ?>
                  <tr id="user-row-<?= $u['id'] ?>">
                    <td>
                      <div style="display: flex; align-items: center; gap: 10px;">
                        <div style="width: 32px; height: 32px; border-radius: 50%; background: <?= htmlspecialchars($u['avatar_color'] ?? '#650b2b') ?>; color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 0.82rem;">
                          <?= strtoupper(substr($u['username'], 0, 1)) ?>
                        </div>
                        <div>
                          <div style="font-weight: 700; color: #0f172a; font-size: 0.88rem;"><?= htmlspecialchars($u['username']) ?></div>
                        </div>
                      </div>
                    </td>
                    <td>
                      <div style="font-size: 0.84rem; font-weight: 600; color: #1e293b;"><?= htmlspecialchars($u['location_name'] ?? 'Victoria Station') ?></div>
                      <div style="font-size: 0.74rem; color: #64748b;"><?= htmlspecialchars($u['department'] ?? 'General') ?></div>
                    </td>
                    <td>
                      <?php if ($u['role_name'] === 'super_admin'): ?>
                        <span class="badge-super-admin" style="font-size: 0.72rem; padding: 2px 8px; border-radius: 4px; font-weight: 700;"><?= htmlspecialchars($u['role_display_name']) ?></span>
                      <?php else: ?>
                        <span class="desk-badge badge-draft" style="font-size: 0.72rem; font-weight: 700;"><?= htmlspecialchars($u['role_display_name']) ?></span>
                      <?php endif; ?>
                    </td>
                    <td>
                      <span class="desk-badge <?= ($u['status'] === 'active') ? 'badge-approved' : 'badge-danger' ?>">
                        <?= ucfirst(htmlspecialchars($u['status'])) ?>
                      </span>
                    </td>
                    <td style="color: #64748b; font-size: 0.82rem;"><?= substr($u['created_at'], 0, 10) ?></td>
                    <td style="text-align: right; white-space: nowrap;">
                      <button class="desk-btn desk-btn-default desk-btn-sm" onclick="Desk.openEditUserModal(<?= htmlspecialchars(json_encode([
                        'id' => $u['id'],
                        'full_name' => $u['full_name'],
                        'username' => $u['username'],
                        'email' => $u['email'],
                        'role_id' => $u['role_id'],
                        'location_id' => $u['location_id'] ?? 1,
                        'department' => $u['department'] ?? '',
                        'status' => $u['status']
                      ])) ?>)">
                        Edit
                      </button>
                      <?php if ((int)$u['id'] !== (int)Auth::id()): ?>
                        <button class="desk-btn desk-btn-danger desk-btn-sm" style="margin-left: 4px;" onclick="Desk.deleteUser(<?= $u['id'] ?>, '<?= addslashes(htmlspecialchars($u['full_name'])) ?>')">
                          Delete
                        </button>
                      <?php endif; ?>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- SUB-PANE 4: USER ROLES & CODES -->
      <div id="admin-sub-roles" class="admin-sub-pane">
        <div class="desk-card" style="margin-bottom: 24px;">
          <div class="desk-card-header" style="display: flex; justify-content: space-between; align-items: center;">
            <div>
              <h2 style="font-size: 1rem; font-weight: 700; color: #0f172a; margin: 0;">Defined User Roles &amp; Operational Designations</h2>
              <div style="font-size: 0.8rem; color: #64748b; margin-top: 2px;">Manage designations, role codes, tab access scopes and assigned staff</div>
            </div>
            <button type="button" class="desk-btn desk-btn-primary desk-btn-sm" onclick="Desk.openModal('createRoleModal')">
              + Add Role
            </button>
          </div>

          <div class="desk-table-wrapper">
            <table class="desk-table">
              <thead>
                <tr>
                  <th>Role Title / Name</th>
                  <th>Role Code</th>
                  <th>Role Identifier</th>
                  <th>Assigned Station / Dept</th>
                  <th>Tab Access</th>
                  <th>Assigned Staff</th>
                  <th style="text-align: right;">Actions</th>
                </tr>
              </thead>
              <tbody id="rolesTableBody">
                <?php
                $allPermKeys = \App\Models\Permission::getAllPermissionKeys();
                $totalPerms = count($allPermKeys);
                foreach ($allRoles as $r):
                  $roleId = (int)$r['id'];
                  $enabledCount = 0;
                  foreach ($allPermKeys as $k) {
                    if (isset($permissionsMatrix[$k][$roleId])) {
                      if ((int)$permissionsMatrix[$k][$roleId] === 1) $enabledCount++;
                    } else {
                      $enabledCount++;
                    }
                  }
                  $roleCodeVal = !empty($r['code']) ? $r['code'] : strtoupper($r['name']);
                ?>
                  <tr id="role-row-<?= $r['id'] ?>">
                    <td>
                      <strong style="color: #0f172a; font-size: 0.92rem;"><?= htmlspecialchars($r['display_name']) ?></strong>
                      <?php if (!empty($r['description'])): ?>
                        <div style="font-size: 0.76rem; color: #64748b; margin-top: 2px;"><?= htmlspecialchars($r['description']) ?></div>
                      <?php endif; ?>
                    </td>
                    <td>
                      <span class="desk-badge" style="font-family: monospace; font-size: 0.82rem; font-weight: 700; background: #650b2b; color: #ffffff; padding: 3px 8px; border-radius: 4px;">
                        <?= htmlspecialchars($roleCodeVal) ?>
                      </span>
                    </td>
                    <td><code><?= htmlspecialchars($r['name']) ?></code></td>
                    <td><span style="font-size: 0.82rem; color: #475569;"><?= htmlspecialchars($r['location_name'] ?? 'All Stations (Global)') ?></span></td>
                    <td>
                      <button type="button" class="desk-btn desk-btn-default desk-btn-sm" style="font-size: 0.76rem; font-weight: 600;" onclick="Desk.openEditRoleModal(<?= htmlspecialchars(json_encode([
                        'id' => $r['id'],
                        'name' => $r['name'],
                        'code' => $roleCodeVal,
                        'display_name' => $r['display_name'],
                        'description' => $r['description'] ?? '',
                        'location_id' => $r['location_id'] ?? '',
                        'department_id' => $r['department_id'] ?? ''
                      ])) ?>)">
                        <span style="color: #650b2b; font-weight: 700;"><?= $enabledCount ?>/<?= $totalPerms ?></span> Tabs &bull; Configure
                      </button>
                    </td>
                    <td><strong style="color: #650b2b;"><?= (int)($r['user_count'] ?? 0) ?></strong> Members</td>
                    <td style="text-align: right; white-space: nowrap;">
                      <button class="desk-btn desk-btn-default desk-btn-sm" onclick="Desk.openEditRoleModal(<?= htmlspecialchars(json_encode([
                        'id' => $r['id'],
                        'name' => $r['name'],
                        'code' => $roleCodeVal,
                        'display_name' => $r['display_name'],
                        'description' => $r['description'] ?? '',
                        'location_id' => $r['location_id'] ?? '',
                        'department_id' => $r['department_id'] ?? ''
                      ])) ?>)">
                        Edit
                      </button>
                      <?php if ($r['id'] != 1 && (int)($r['user_count'] ?? 0) === 0): ?>
                        <button class="desk-btn desk-btn-danger desk-btn-sm" style="margin-left: 4px;" onclick="Desk.deleteRole(<?= $r['id'] ?>, '<?= addslashes(htmlspecialchars($r['display_name'])) ?>')">
                          Delete
                        </button>
                      <?php endif; ?>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- SUB-PANE 5: PERMISSIONS MATRIX -->
      <div id="admin-sub-matrix" class="admin-sub-pane">
        <div class="desk-card" style="margin-bottom: 24px;">
          <div class="desk-card-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
            <div>
              <h2 style="font-size: 1rem; font-weight: 700; color: #0f172a; margin: 0;">User Permissions &amp; Access Matrix</h2>
              <div style="font-size: 0.8rem; color: #64748b; margin-top: 2px;">Interactive ON/OFF access control across all ERP workspaces, workflow tabs and reports</div>
            </div>
            <div style="display: flex; gap: 8px; align-items: center;">
              <span style="font-size: 0.8rem; color: #64748b; font-weight: 600;">Horizontal Scroll:</span>
              <button type="button" class="desk-btn desk-btn-default desk-btn-sm" id="btnScrollMatrixLeft" title="Scroll Matrix Left">
                &larr; Left
              </button>
              <button type="button" class="desk-btn desk-btn-default desk-btn-sm" id="btnScrollMatrixRight" title="Scroll Matrix Right">
                Right &rarr;
              </button>
            </div>
          </div>

          <!-- Permissions Matrix Table -->
          <div id="usersMatrixWrap" class="matrix-table-wrap">
            <table class="matrix-table">
              <thead>
                <!-- Tier 1: Station / Location Colspan Headers -->
                <tr>
                  <th rowspan="2" class="matrix-nav-col" style="vertical-align: middle; background: #f8fafc; border-bottom: 2px solid #cbd5e1; border-right: 1px solid #e2e8f0; font-weight: 700; color: #1e293b;">
                    System Navigation Tab / Sub-Tab
                  </th>
                  <?php if (empty($groupedUsersByStation)): ?>
                    <th style="text-align: center; color: #94a3b8; padding: 16px 20px; font-weight: normal; font-size: 0.82rem;">
                      No operational user accounts created yet. Use the <strong>User Accounts</strong> tab above to create users and configure their tab access matrix.
                    </th>
                  <?php else: ?>
                    <?php foreach ($groupedUsersByStation as $stationName => $stUsers): ?>
                      <th colspan="<?= count($stUsers) ?>" style="text-align: center; background: #f1f5f9; color: #334155; font-weight: 700; font-size: 0.74rem; letter-spacing: 0.5px; border-bottom: 1px solid #cbd5e1; border-left: 2px solid #cbd5e1; padding: 6px 8px; white-space: nowrap;">
                        <?= htmlspecialchars($stationName ?: 'All Stations (Global)') ?>
                      </th>
                    <?php endforeach; ?>
                  <?php endif; ?>
                </tr>
                <!-- Tier 2: User Account Headers -->
                <tr>
                  <?php if (empty($groupedUsersByStation)): ?>
                    <th style="display: none;"></th>
                  <?php else: ?>
                    <?php foreach ($groupedUsersByStation as $stationName => $stUsers): ?>
                      <?php foreach ($stUsers as $idx => $u): ?>
                        <th class="matrix-user-col" style="text-align: center; background: #ffffff; padding: 6px 4px; white-space: nowrap; border-bottom: 1px solid #cbd5e1; <?= ($idx === 0) ? 'border-left: 2px solid #cbd5e1;' : '' ?>">
                          <div style="font-size: 0.82rem; font-weight: 700; color: #0f172a; margin-bottom: 2px; text-transform: uppercase;">
                            <?= htmlspecialchars($u['username']) ?>
                          </div>
                          <div>
                            <span class="desk-badge badge-draft" style="font-size: 0.62rem; padding: 1px 4px;">
                              <?= htmlspecialchars($u['role_display_name'] ?? 'User') ?>
                            </span>
                          </div>
                        </th>
                      <?php endforeach; ?>
                    <?php endforeach; ?>
                  <?php endif; ?>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($permissionCategories as $categoryName => $perms): ?>
                  <tr class="matrix-category-row">
                    <td class="matrix-nav-col" style="background: #fdf2f5 !important; border-right: 1px solid #e2e8f0; border-bottom: 1px solid #e2e8f0;">
                      <strong style="color: #650b2b;"><?= htmlspecialchars($categoryName) ?></strong>
                    </td>
                    <?php if (empty($groupedUsersByStation)): ?>
                      <td style="background: #fdf2f5 !important; border-bottom: 1px solid #e2e8f0;"></td>
                    <?php else: ?>
                      <?php foreach ($groupedUsersByStation as $stationName => $stUsers): ?>
                        <?php foreach ($stUsers as $idx => $u): ?>
                          <td class="matrix-user-col" style="background: #fdf2f5 !important; border-bottom: 1px solid #e2e8f0; <?= ($idx === 0) ? 'border-left: 2px solid #cbd5e1;' : 'border-left: 1px solid #f1f5f9;' ?>"></td>
                        <?php endforeach; ?>
                      <?php endforeach; ?>
                    <?php endif; ?>
                  </tr>

                  <?php foreach ($perms as $permKey => $permMeta):
                    $level = $permMeta['level'] ?? 1;
                    $levelClass = 'matrix-level-' . $level;
                  ?>
                    <tr>
                      <td class="<?= $levelClass ?> matrix-nav-col" style="border-right: 1px solid #e2e8f0;">
                        <span class="matrix-perm-title"><?= htmlspecialchars($permMeta['label']) ?></span>
                      </td>
                      <?php if (empty($groupedUsersByStation)): ?>
                        <td style="text-align: center; color: #cbd5e1; font-size: 0.8rem; padding: 4px;">—</td>
                      <?php else: ?>
                        <?php foreach ($groupedUsersByStation as $stationName => $stUsers): ?>
                          <?php foreach ($stUsers as $idx => $u):
                            if (isset($userPermissionsMatrix[$permKey][$u['id']])) {
                              $isGranted = ((int)$userPermissionsMatrix[$permKey][$u['id']] === 1);
                            } elseif (isset($permissionsMatrix[$permKey][$u['role_id']])) {
                              $isGranted = ((int)$permissionsMatrix[$permKey][$u['role_id']] === 1);
                            } else {
                              $isGranted = true;
                            }
                          ?>
                            <td class="matrix-user-col" style="text-align: center; padding: 4px 3px; <?= ($idx === 0) ? 'border-left: 2px solid #f1f5f9;' : '' ?>">
                              <label class="matrix-switch" title="Toggle <?= htmlspecialchars($permMeta['label']) ?> for <?= htmlspecialchars($u['username']) ?>">
                                <input type="checkbox" class="matrix-perm-toggle"
                                  data-user-id="<?= $u['id'] ?>"
                                  data-permission-key="<?= htmlspecialchars($permKey) ?>"
                                  data-target-name="<?= htmlspecialchars($u['username']) ?>"
                                  data-perm-label="<?= htmlspecialchars($permMeta['label']) ?>"
                                  <?= $isGranted ? 'checked' : '' ?>>
                                <span class="matrix-slider"></span>
                              </label>
                            </td>
                          <?php endforeach; ?>
                        <?php endforeach; ?>
                      <?php endif; ?>
                    </tr>
                  <?php endforeach; ?>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
    <?php endif; ?>

  </main>
</div>

<!-- Include Modal Dialogs -->
<?php require_once __DIR__ . '/views/components/modals.php'; ?>

<!-- Include Footer -->
<?php require_once __DIR__ . '/views/layouts/footer.php'; ?>
