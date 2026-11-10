<?php
use App\Core\Auth;
use App\Helpers\Icon;
use App\Models\Permission;

$currentUser = Auth::user();
$isSuperAdmin = ($currentUser['role_name'] ?? '') === 'super_admin';
$permModel = new Permission();
$userId = Auth::id();
$roleId = (int)($currentUser['role_id'] ?? 0);

$firstActiveSet = false;
$getActiveClass = function(string $tabId) use (&$firstActiveSet, $permModel, $roleId, $userId) {
    if (!$firstActiveSet && $permModel->canAccessTab($tabId, $roleId, $userId)) {
        $firstActiveSet = true;
        return 'active';
    }
    return '';
};
?>
<aside class="desk-sidebar">
<?php if ($isSuperAdmin): ?>
  <!-- ============================================================
       ADMINISTRATION ROLE SIDEBAR NAVIGATION
       ============================================================ -->
  <div class="sidebar-section-title">Administration Hub</div>
  <button type="button" class="sidebar-nav-item active" data-tab="tab-admin" data-admin-sub="admin-sub-locations">
    <span class="sidebar-icon"><?= Icon::get('zap') ?></span>
    <span>Power Stations &amp; Locations</span>
  </button>
  <button type="button" class="sidebar-nav-item" data-tab="tab-admin" data-admin-sub="admin-sub-departments">
    <span class="sidebar-icon"><?= Icon::get('layers') ?></span>
    <span>Departments &amp; Stores</span>
  </button>
  <button type="button" class="sidebar-nav-item" data-tab="tab-admin" data-admin-sub="admin-sub-users">
    <span class="sidebar-icon"><?= Icon::get('user') ?></span>
    <span>User Accounts</span>
  </button>
  <button type="button" class="sidebar-nav-item" data-tab="tab-admin" data-admin-sub="admin-sub-roles">
    <span class="sidebar-icon"><?= Icon::get('shield') ?></span>
    <span>User Roles &amp; Codes</span>
  </button>
  <button type="button" class="sidebar-nav-item" data-tab="tab-admin" data-admin-sub="admin-sub-matrix">
    <span class="sidebar-icon"><?= Icon::get('check-square') ?></span>
    <span>Permissions Matrix</span>
  </button>

<?php else: ?>
  <!-- ============================================================
       OPERATIONAL POWER STATIONS SIDEBAR NAVIGATION (STAFF / OPERATORS)
       FILTERED DYNAMICALLY BY PERMISSIONS MATRIX
       ============================================================ -->
  <?php if ($permModel->canAccessTab('tab-dashboard', $roleId, $userId)): ?>
    <div class="sidebar-section-title">Workspaces</div>
    <button type="button" class="sidebar-nav-item <?= $getActiveClass('tab-dashboard') ?>" data-tab="tab-dashboard">
      <span class="sidebar-icon"><?= Icon::get('home') ?></span>
      <span>Home Desk</span>
    </button>
  <?php endif; ?>

  <?php
  $canReceipts = $permModel->canAccessTab('tab-receipts', $roleId, $userId);
  $canIssues = $permModel->canAccessTab('tab-issues', $roleId, $userId);
  if ($canReceipts || $canIssues):
  ?>
    <div class="sidebar-section-title">Inventory - FIFO</div>
    <?php if ($canReceipts): ?>
      <button type="button" class="sidebar-nav-item <?= $getActiveClass('tab-receipts') ?>" data-tab="tab-receipts">
        <span class="sidebar-icon"><?= Icon::get('file-text') ?></span>
        <span>Receipts (GRN)</span>
      </button>
    <?php endif; ?>
    <?php if ($canIssues): ?>
      <button type="button" class="sidebar-nav-item <?= $getActiveClass('tab-issues') ?>" data-tab="tab-issues">
        <span class="sidebar-icon"><?= Icon::get('repeat') ?></span>
        <span>Material Issues (FIFO)</span>
      </button>
    <?php endif; ?>
  <?php endif; ?>

  <?php
  $canPvWorkflow = $permModel->canAccessTab('tab-pv-workflow', $roleId, $userId);
  $canPvReports = $permModel->canAccessTab('tab-pv-reports', $roleId, $userId);
  if ($canPvWorkflow || $canPvReports):
  ?>
    <div class="sidebar-section-title">Physical Verification</div>
    <?php if ($canPvWorkflow): ?>
      <button type="button" class="sidebar-nav-item <?= $getActiveClass('tab-pv-workflow') ?>" data-tab="tab-pv-workflow">
        <span class="sidebar-icon"><?= Icon::get('check-square') ?></span>
        <span>Verification Workflow</span>
      </button>
    <?php endif; ?>
    <?php if ($canPvReports): ?>
      <button type="button" class="sidebar-nav-item <?= $getActiveClass('tab-pv-reports') ?>" data-tab="tab-pv-reports">
        <span class="sidebar-icon"><?= Icon::get('file-check') ?></span>
        <span>PHV Reports (AV 7)</span>
      </button>
    <?php endif; ?>
  <?php endif; ?>

  <?php if ($permModel->canAccessTab('tab-payslips', $roleId, $userId)): ?>
    <div class="sidebar-section-title">Cash Book</div>
    <button type="button" class="sidebar-nav-item <?= $getActiveClass('tab-payslips') ?>" data-tab="tab-payslips">
      <span class="sidebar-icon"><?= Icon::get('file-x') ?></span>
      <span>Payslip Cancelation</span>
    </button>
  <?php endif; ?>

  <?php
  $reportTabs = [
      'tab-qoh' => ['icon' => 'layers', 'title' => 'Quantity On Hand'],
      'tab-report-transfers' => ['icon' => 'repeat', 'title' => 'Monthly Transfers'],
      'tab-report-diesel' => ['icon' => 'trending-up', 'title' => 'Average Diesel Price'],
      'tab-report-cpc' => ['icon' => 'droplet', 'title' => 'CPC Procurements report'],
      'tab-report-inventory-inquiry' => ['icon' => 'search', 'title' => 'Inventory Inquiry Report'],
      'tab-report-jobs' => ['icon' => 'briefcase', 'title' => 'Job wise material Issue Report'],
      'tab-report-monthly-tx' => ['icon' => 'file-text', 'title' => 'Monthly Transaction'],
      'tab-report-grn-issue-summary' => ['icon' => 'clipboard', 'title' => 'GRN - Issue Summary'],
      'tab-report-material-flow' => ['icon' => 'repeat', 'title' => 'Material Flow Report'],
  ];
  $accessibleReports = array_filter(array_keys($reportTabs), fn($t) => $permModel->canAccessTab($t, $roleId, $userId));
  if (!empty($accessibleReports)):
  ?>
    <div class="sidebar-section-title">Reports</div>
    <?php foreach ($reportTabs as $tId => $tMeta): ?>
      <?php if (in_array($tId, $accessibleReports)): ?>
        <button type="button" class="sidebar-nav-item <?= $getActiveClass($tId) ?>" data-tab="<?= $tId ?>">
          <span class="sidebar-icon"><?= Icon::get($tMeta['icon']) ?></span>
          <span><?= htmlspecialchars($tMeta['title']) ?></span>
        </button>
      <?php endif; ?>
    <?php endforeach; ?>
  <?php endif; ?>

  <?php
  $canCostAdjust = $permModel->canAccessTab('tab-cost-adjust', $roleId, $userId);
  $canQtyCorrect = $permModel->canAccessTab('tab-qty-correct', $roleId, $userId);
  if ($canCostAdjust || $canQtyCorrect):
  ?>
    <div class="sidebar-section-title">Authorized Tasks</div>
    <?php if ($canCostAdjust): ?>
      <button type="button" class="sidebar-nav-item <?= $getActiveClass('tab-cost-adjust') ?>" data-tab="tab-cost-adjust">
        <span class="sidebar-icon"><?= Icon::get('sliders') ?></span>
        <span>Cost Adjustments</span>
      </button>
    <?php endif; ?>
    <?php if ($canQtyCorrect): ?>
      <button type="button" class="sidebar-nav-item <?= $getActiveClass('tab-qty-correct') ?>" data-tab="tab-qty-correct">
        <span class="sidebar-icon"><?= Icon::get('hash') ?></span>
        <span>Allocated Qty Correction</span>
      </button>
    <?php endif; ?>
  <?php endif; ?>

  <?php if ($permModel->canAccessTab('tab-admin', $roleId, $userId)): ?>
    <div class="sidebar-section-title">Administration</div>
    <button type="button" class="sidebar-nav-item <?= $getActiveClass('tab-admin') ?>" data-tab="tab-admin" data-admin-sub="admin-sub-locations">
      <span class="sidebar-icon"><?= Icon::get('zap') ?></span>
      <span>Admin Console</span>
    </button>
  <?php endif; ?>

<?php endif; ?>
</aside>
