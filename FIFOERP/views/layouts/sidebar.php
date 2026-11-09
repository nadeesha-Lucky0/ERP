<?php
use App\Helpers\Icon;
?>
<aside class="desk-sidebar">
  <div class="sidebar-section-title">Workspaces</div>
  <button type="button" class="sidebar-nav-item active" data-tab="tab-dashboard">
    <span class="sidebar-icon"><?= Icon::get('home') ?></span>
    <span>Home Desk</span>
  </button>

  <div class="sidebar-section-title">Inventory & FIFO</div>
  <button type="button" class="sidebar-nav-item" data-tab="tab-batches">
    <span class="sidebar-icon"><?= Icon::get('package') ?></span>
    <span>Active Batches (QOH)</span>
  </button>
  <button type="button" class="sidebar-nav-item" data-tab="tab-receipts">
    <span class="sidebar-icon"><?= Icon::get('file-text') ?></span>
    <span>Receipts (GRN)</span>
  </button>
  <button type="button" class="sidebar-nav-item" data-tab="tab-issues">
    <span class="sidebar-icon"><?= Icon::get('repeat') ?></span>
    <span>Material Issues (FIFO)</span>
  </button>

  <div class="sidebar-section-title">Physical Verification</div>
  <button type="button" class="sidebar-nav-item" data-tab="tab-pv-workflow">
    <span class="sidebar-icon"><?= Icon::get('check-square') ?></span>
    <span>Verification Workflow</span>
  </button>
  <button type="button" class="sidebar-nav-item" data-tab="tab-pv-reports">
    <span class="sidebar-icon"><?= Icon::get('file-check') ?></span>
    <span>PHV Reports (AV 7)</span>
  </button>

  <div class="sidebar-section-title">Cash Book & Payroll</div>
  <button type="button" class="sidebar-nav-item" data-tab="tab-cashbook">
    <span class="sidebar-icon"><?= Icon::get('wallet') ?></span>
    <span>Cash Book Vouchers</span>
  </button>
  <button type="button" class="sidebar-nav-item" data-tab="tab-payslips">
    <span class="sidebar-icon"><?= Icon::get('file-x') ?></span>
    <span>Payslip Cancellations</span>
  </button>

  <div class="sidebar-section-title">Reports</div>
  <button type="button" class="sidebar-nav-item" data-tab="tab-report-qoh-material">
    <span class="sidebar-icon"><?= Icon::get('layers') ?></span>
    <span>Material-wise Balance</span>
  </button>
  <button type="button" class="sidebar-nav-item" data-tab="tab-report-cpc">
    <span class="sidebar-icon"><?= Icon::get('droplet') ?></span>
    <span>CPC Procurements</span>
  </button>
  <button type="button" class="sidebar-nav-item" data-tab="tab-report-diesel">
    <span class="sidebar-icon"><?= Icon::get('trending-up') ?></span>
    <span>Average Diesel Price</span>
  </button>
  <button type="button" class="sidebar-nav-item" data-tab="tab-report-transfers">
    <span class="sidebar-icon"><?= Icon::get('repeat') ?></span>
    <span>Monthly Transfers</span>
  </button>
  <button type="button" class="sidebar-nav-item" data-tab="tab-report-jobs">
    <span class="sidebar-icon"><?= Icon::get('briefcase') ?></span>
    <span>Job-wise Issues</span>
  </button>
  <button type="button" class="sidebar-nav-item" data-tab="tab-report-inventory-inquiry">
    <span class="sidebar-icon"><?= Icon::get('search') ?></span>
    <span>Inventory Inquiry Report</span>
  </button>
  <button type="button" class="sidebar-nav-item" data-tab="tab-report-monthly-tx">
    <span class="sidebar-icon"><?= Icon::get('file-text') ?></span>
    <span>Monthly Transactions</span>
  </button>
  <button type="button" class="sidebar-nav-item" data-tab="tab-report-grn-issue-summary">
    <span class="sidebar-icon"><?= Icon::get('clipboard') ?></span>
    <span>GRN - Issue Summary</span>
  </button>
  <button type="button" class="sidebar-nav-item" data-tab="tab-report-material-flow">
    <span class="sidebar-icon"><?= Icon::get('repeat') ?></span>
    <span>Material Flow Report</span>
  </button>

  <div class="sidebar-section-title">Authorized Tasks</div>
  <button type="button" class="sidebar-nav-item" data-tab="tab-cost-adjust">
    <span class="sidebar-icon"><?= Icon::get('sliders') ?></span>
    <span>Cost Adjustments</span>
  </button>
  <button type="button" class="sidebar-nav-item" data-tab="tab-qty-correct">
    <span class="sidebar-icon"><?= Icon::get('hash') ?></span>
    <span>Allocated Qty Correction</span>
  </button>
  <button type="button" class="sidebar-nav-item" data-tab="tab-dest-codes">
    <span class="sidebar-icon"><?= Icon::get('tag') ?></span>
    <span>Destination Codes</span>
  </button>
</aside>
