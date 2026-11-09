<?php
require_once __DIR__ . '/app/config/config.php';

use App\Core\Auth;
use App\Helpers\Icon;

// Require authentication
Auth::requireLogin();

// Include Header
require_once __DIR__ . '/views/layouts/header.php';
?>

<div class="desk-layout">
  <!-- Left Sidebar -->
  <?php require_once __DIR__ . '/views/layouts/sidebar.php'; ?>

  <!-- Main Desk Content -->
  <main class="desk-content">

    <!-- ============================================================
         PANE 1: Home Dashboard Workspace
         ============================================================ -->
    <div id="tab-dashboard" class="tab-pane">
      <div class="workspace-header">
        <div class="workspace-title-box">
          <h1>Victoria Power Station - Home Desk</h1>
          <div class="workspace-subtitle">FIFO Inventory & Operations Central Workspace</div>
        </div>
        <div class="header-action-btns">
          <button class="desk-btn desk-btn-primary" onclick="Desk.openModal('modalNewGrn')">
            <?= Icon::get('plus', 13) ?> New GRN
          </button>
          <button class="desk-btn desk-btn-default" onclick="Desk.openModal('modalNewIssue')">
            <?= Icon::get('plus', 13) ?> New Issue
          </button>
        </div>
      </div>

      <!-- Stat Cards Grid -->
      <div class="workspace-cards-grid">
        <div class="desk-stat-card">
          <div class="stat-card-title">Total Active Batches</div>
          <div class="stat-card-value" id="statTotalBatches">10</div>
          <div class="stat-card-desc">Active FIFO queue stock items</div>
        </div>
        <div class="desk-stat-card">
          <div class="stat-card-title">Stock Valuation</div>
          <div class="stat-card-value" id="statTotalValuation" style="color: #0d9488;">Rs. 0.00</div>
          <div class="stat-card-desc">Total remaining inventory asset value</div>
        </div>
        <div class="desk-stat-card">
          <div class="stat-card-title">Pending Approvals</div>
          <div class="stat-card-value" id="statPendingApprovals" style="color: #0284c7;">0</div>
          <div class="stat-card-desc">GRN & Issues awaiting review</div>
        </div>
        <div class="desk-stat-card">
          <div class="stat-card-title">Low Stock Reorders</div>
          <div class="stat-card-value" id="statLowStock" style="color: #dc2626;">0</div>
          <div class="stat-card-desc">Critical power plant spares below threshold</div>
        </div>
      </div>

      <!-- Quick Shortcuts Grid -->
      <div class="desk-card">
        <div class="desk-card-header">
          <div class="desk-card-title">Quick Workspace Shortcuts</div>
        </div>
        <div style="padding: 20px; display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 14px;">
          <button class="desk-btn desk-btn-default" style="justify-content: flex-start; padding: 12px 16px; gap: 12px;" onclick="Desk.switchTab('tab-batches')">
            <span style="color: var(--primary);"><?= Icon::get('package', 18) ?></span>
            <div style="text-align: left;">
              <div style="font-weight: 600;">Active Batches</div>
              <div style="font-size: 11px; color: #64748b;">View FIFO stock queue</div>
            </div>
          </button>
          <button class="desk-btn desk-btn-default" style="justify-content: flex-start; padding: 12px 16px; gap: 12px;" onclick="Desk.switchTab('tab-transactions')">
            <span style="color: var(--primary);"><?= Icon::get('file-text', 18) ?></span>
            <div style="text-align: left;">
              <div style="font-weight: 600;">Receipts & Issues</div>
              <div style="font-size: 11px; color: #64748b;">Workflow & approvals</div>
            </div>
          </button>
          <button class="desk-btn desk-btn-default" style="justify-content: flex-start; padding: 12px 16px; gap: 12px;" onclick="Desk.switchTab('tab-phv')">
            <span style="color: var(--primary);"><?= Icon::get('check-square', 18) ?></span>
            <div style="text-align: left;">
              <div style="font-weight: 600;">Freeze & PHV</div>
              <div style="font-size: 11px; color: #64748b;">Store board of survey</div>
            </div>
          </button>
          <button class="desk-btn desk-btn-default" style="justify-content: flex-start; padding: 12px 16px; gap: 12px;" onclick="Desk.switchTab('tab-av7')">
            <span style="color: var(--primary);"><?= Icon::get('file-check', 18) ?></span>
            <div style="text-align: left;">
              <div style="font-weight: 600;">AV 7 Summary</div>
              <div style="font-size: 11px; color: #64748b;">Official stores format</div>
            </div>
          </button>
          <button class="desk-btn desk-btn-default" style="justify-content: flex-start; padding: 12px 16px; gap: 12px;" onclick="Desk.switchTab('tab-report-cpc')">
            <span style="color: var(--primary);"><?= Icon::get('droplet', 18) ?></span>
            <div style="text-align: left;">
              <div style="font-weight: 600;">CPC Procurements</div>
              <div style="font-size: 11px; color: #64748b;">Bulk fuel consignments</div>
            </div>
          </button>
          <button class="desk-btn desk-btn-default" style="justify-content: flex-start; padding: 12px 16px; gap: 12px;" onclick="Desk.switchTab('tab-cashbook')">
            <span style="color: var(--primary);"><?= Icon::get('wallet', 18) ?></span>
            <div style="text-align: left;">
              <div style="font-weight: 600;">Cash Book</div>
              <div style="font-size: 11px; color: #64748b;">Vouchers & petty cash</div>
            </div>
          </button>
        </div>
      </div>
    </div>

    <!-- ============================================================
         PANE 2: Active Batches (FIFO Stock Ledger)
         ============================================================ -->
    <div id="tab-batches" class="tab-pane" style="display: none;">
      <div class="workspace-header">
        <div class="workspace-title-box">
          <h1>Active Batches (FIFO Queue)</h1>
          <div class="workspace-subtitle">First-In, First-Out Stock Lots and Locations</div>
        </div>
        <div class="header-action-btns">
          <button class="desk-btn desk-btn-primary" onclick="Desk.openModal('modalNewGrn')">
            <?= Icon::get('plus', 13) ?> Receive New Batch (GRN)
          </button>
        </div>
      </div>

      <div class="desk-card">
        <div class="desk-card-header">
          <div class="desk-card-title">Inventory Batches List</div>
          <button class="desk-btn desk-btn-default desk-btn-sm" onclick="Desk.loadBatches()">
            <?= Icon::get('refresh', 13) ?> Refresh
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

    <!-- ============================================================
         PANE 3A: Receipts (GRN) Management
         ============================================================ -->
    <div id="tab-receipts" class="tab-pane" style="display: none;">
      <div class="workspace-header">
        <div class="workspace-title-box">
          <h1>Receipts (GRN) Management</h1>
          <div class="workspace-subtitle">Goods Received Notes &amp; Consignments (Entry &rarr; Validation &rarr; Approval &rarr; Post)</div>
        </div>
        <div class="header-action-btns">
          <button class="desk-btn desk-btn-primary" onclick="Desk.openModal('modalNewGrn')">
            <?= Icon::get('plus', 13) ?> New GRN Receipt
          </button>
          <button class="desk-btn desk-btn-default" onclick="Desk.loadReceipts()">
            <?= Icon::get('refresh', 13) ?> Refresh
          </button>
        </div>
      </div>

      <!-- Quick Filter Pills -->
      <div style="display: flex; gap: 8px; margin-bottom: 16px; flex-wrap: wrap;">
        <button type="button" class="desk-btn desk-btn-default desk-btn-sm active-receipt-filter" onclick="Desk.filterReceipts('all', this)">All Receipts</button>
        <button type="button" class="desk-btn desk-btn-default desk-btn-sm" onclick="Desk.filterReceipts('Draft', this)">Draft / Entry</button>
        <button type="button" class="desk-btn desk-btn-default desk-btn-sm" onclick="Desk.filterReceipts('Validated', this)">Validated</button>
        <button type="button" class="desk-btn desk-btn-default desk-btn-sm" onclick="Desk.filterReceipts('Approved', this)">Approved</button>
        <button type="button" class="desk-btn desk-btn-default desk-btn-sm" onclick="Desk.filterReceipts('Posted', this)">Posted</button>
        <button type="button" class="desk-btn desk-btn-default desk-btn-sm" onclick="Desk.filterReceipts('Cancelled', this)">Cancelled</button>
      </div>

      <div class="desk-card">
        <div class="desk-card-header">
          <div class="desk-card-title">Goods Received Documents (GRN)</div>
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
          <div class="workspace-subtitle">Spares &amp; Consumables Issue Workflow (Entry &rarr; Edit &rarr; Validation &rarr; Approval &rarr; FIFO Post)</div>
        </div>
        <div class="header-action-btns">
          <button class="desk-btn desk-btn-primary" onclick="Desk.openModal('modalNewIssue')">
            <?= Icon::get('plus', 13) ?> New Material Issue
          </button>
          <button class="desk-btn desk-btn-default" onclick="Desk.loadIssues()">
            <?= Icon::get('refresh', 13) ?> Refresh
          </button>
        </div>
      </div>

      <!-- Quick Filter Pills -->
      <div style="display: flex; gap: 8px; margin-bottom: 16px; flex-wrap: wrap;">
        <button type="button" class="desk-btn desk-btn-default desk-btn-sm active-issue-filter" onclick="Desk.filterIssues('all', this)">All Issues</button>
        <button type="button" class="desk-btn desk-btn-default desk-btn-sm" onclick="Desk.filterIssues('Draft', this)">Draft / In Entry</button>
        <button type="button" class="desk-btn desk-btn-default desk-btn-sm" onclick="Desk.filterIssues('Validated', this)">Validated</button>
        <button type="button" class="desk-btn desk-btn-default desk-btn-sm" onclick="Desk.filterIssues('Approved', this)">Approved &amp; Ready to Post</button>
        <button type="button" class="desk-btn desk-btn-default desk-btn-sm" onclick="Desk.filterIssues('Posted', this)">Posted to FIFO</button>
        <button type="button" class="desk-btn desk-btn-default desk-btn-sm" onclick="Desk.filterIssues('Cancelled', this)">Cancelled</button>
      </div>

      <div class="desk-card">
        <div class="desk-card-header">
          <div class="desk-card-title">Material Issue Requisitions (FIFO Queue)</div>
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
          <button class="desk-btn desk-btn-primary" onclick="Desk.openModal('modalNewCashVoucher')">
            <?= Icon::get('plus', 13) ?> Add Voucher
          </button>
        </div>
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
          <button class="desk-btn desk-btn-danger" onclick="Desk.openModal('modalPayslipCancel')">
            <?= Icon::get('plus', 13) ?> Request Cancellation
          </button>
        </div>
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
         PANE 8: Material-Wise Balance (QOH)
         ============================================================ -->
    <div id="tab-report-qoh-material" class="tab-pane" style="display: none;">
      <div class="workspace-header">
        <div class="workspace-title-box">
          <h1>Material-Wise Balance (QOH)</h1>
          <div class="workspace-subtitle">Consolidated Stock Summary Grouped by SKU</div>
        </div>
        <div class="header-action-btns">
          <button class="desk-btn desk-btn-default" onclick="Desk.loadQohMaterial()">
            <?= Icon::get('refresh', 13) ?> Refresh
          </button>
        </div>
      </div>

      <div class="desk-card">
        <div class="desk-table-wrapper">
          <table class="desk-table">
            <thead>
              <tr>
                <th>SKU</th>
                <th>Material Description</th>
                <th>Category</th>
                <th>Total QOH</th>
                <th>Avg Unit Cost</th>
                <th>Total Valuation</th>
                <th>Batches Count</th>
                <th>Availability</th>
              </tr>
            </thead>
            <tbody id="qohMaterialTableBody"></tbody>
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
          <button class="desk-btn desk-btn-primary" onclick="Desk.openModal('modalNewCpc')">
            <?= Icon::get('plus', 13) ?> New CPC Consignment
          </button>
          <button class="desk-btn desk-btn-default" onclick="Desk.loadCpcProcurements()">
            <?= Icon::get('refresh', 13) ?> Refresh
          </button>
        </div>
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
          <button class="desk-btn desk-btn-primary" onclick="Desk.openModal('modalNewDiesel')">
            <?= Icon::get('plus', 13) ?> Add Diesel Price Log
          </button>
          <button class="desk-btn desk-btn-default" onclick="Desk.loadDieselPriceLogs()">
            <?= Icon::get('refresh', 13) ?> Refresh
          </button>
        </div>
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
         PANE 13: Cost Adjustments
         ============================================================ -->
    <div id="tab-cost-adjust" class="tab-pane" style="display: none;">
      <div class="workspace-header">
        <div class="workspace-title-box">
          <h1>Cost & Valuation Adjustments</h1>
          <div class="workspace-subtitle">Freight, Customs Cess & Valuation Surcharges</div>
        </div>
        <div class="header-action-btns">
          <button class="desk-btn desk-btn-primary" onclick="Desk.openModal('modalCostAdjust')">
            <?= Icon::get('plus', 13) ?> New Cost Adjustment
          </button>
        </div>
      </div>

      <div class="desk-card">
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
      </div>

      <div class="desk-card">
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

  </main>
</div>

<!-- Include Modal Dialogs -->
<?php require_once __DIR__ . '/views/components/modals.php'; ?>

<!-- Include Footer -->
<?php require_once __DIR__ . '/views/layouts/footer.php'; ?>
