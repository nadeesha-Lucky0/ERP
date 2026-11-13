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
  <button type="button" class="sidebar-nav-item" data-tab="tab-admin" data-admin-sub="admin-sub-lookups">
    <span class="sidebar-icon"><?= Icon::get('layers') ?></span>
    <span>Sections, Suppliers &amp; Cost Centers</span>
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
      <div class="sidebar-nav-group <?= ($getActiveClass('tab-receipts') ? 'open' : '') ?>" id="sidebarGroupReceipts">
        <button type="button" class="sidebar-nav-item sidebar-parent-item <?= $getActiveClass('tab-receipts') ?>" data-tab="tab-receipts" data-toggle-group="sidebarGroupReceipts">
          <span class="sidebar-icon"><?= Icon::get('file-text') ?></span>
          <span class="sidebar-label">Receipts (GRN)</span>
          <span class="sidebar-chevron"><?= Icon::get('chevron-down', 13) ?></span>
        </button>
        <div class="sidebar-submenu" id="submenu-receipts">
          <?php if ($permModel->hasPermission($roleId, 'tab-user-receipts-entry', $userId)): ?>
            <button type="button" class="sidebar-sub-item active" data-parent-tab="tab-receipts" data-workflow-filter="Entry" onclick="Desk.navigateWorkflow('tab-receipts', 'Entry', this)">
              <span class="sidebar-sub-icon"><?= Icon::get('edit', 12) ?></span>
              <span>Entry</span>
            </button>
          <?php endif; ?>
          <?php if ($permModel->hasPermission($roleId, 'tab-user-receipts-validation', $userId)): ?>
            <button type="button" class="sidebar-sub-item" data-parent-tab="tab-receipts" data-workflow-filter="Validation" onclick="Desk.navigateWorkflow('tab-receipts', 'Validation', this)">
              <span class="sidebar-sub-icon"><?= Icon::get('check-circle', 12) ?></span>
              <span>Validation</span>
            </button>
          <?php endif; ?>
          <?php if ($permModel->hasPermission($roleId, 'tab-user-receipts-approval', $userId)): ?>
            <button type="button" class="sidebar-sub-item" data-parent-tab="tab-receipts" data-workflow-filter="Approval" onclick="Desk.navigateWorkflow('tab-receipts', 'Approval', this)">
              <span class="sidebar-sub-icon"><?= Icon::get('award', 12) ?></span>
              <span>Approval</span>
            </button>
          <?php endif; ?>
          <?php if ($permModel->hasPermission($roleId, 'tab-user-receipts-edit', $userId)): ?>
            <button type="button" class="sidebar-sub-item" data-parent-tab="tab-receipts" data-workflow-filter="Unposted Document Edit" onclick="Desk.navigateWorkflow('tab-receipts', 'Unposted Document Edit', this)">
              <span class="sidebar-sub-icon"><?= Icon::get('edit-3', 12) ?></span>
              <span>Unposted Document Edit</span>
            </button>
          <?php endif; ?>
          <?php if ($permModel->hasPermission($roleId, 'tab-user-receipts-posted-cancel', $userId)): ?>
            <button type="button" class="sidebar-sub-item" data-parent-tab="tab-receipts" data-workflow-filter="Posted Cancellation" onclick="Desk.navigateWorkflow('tab-receipts', 'Posted Cancellation', this)">
              <span class="sidebar-sub-icon"><?= Icon::get('rotate-ccw', 12) ?></span>
              <span>Posted Cancellation</span>
            </button>
          <?php endif; ?>
          <?php if ($permModel->hasPermission($roleId, 'tab-user-receipts-unposted-cancel', $userId)): ?>
            <button type="button" class="sidebar-sub-item" data-parent-tab="tab-receipts" data-workflow-filter="Unposted Cancellation" onclick="Desk.navigateWorkflow('tab-receipts', 'Unposted Cancellation', this)">
              <span class="sidebar-sub-icon"><?= Icon::get('x-circle', 12) ?></span>
              <span>Unposted Cancellation</span>
            </button>
          <?php endif; ?>
        </div>
      </div>
    <?php endif; ?>
    <?php if ($canIssues): ?>
      <div class="sidebar-nav-group <?= ($getActiveClass('tab-issues') ? 'open' : '') ?>" id="sidebarGroupIssues">
        <button type="button" class="sidebar-nav-item sidebar-parent-item <?= $getActiveClass('tab-issues') ?>" data-tab="tab-issues" data-toggle-group="sidebarGroupIssues">
          <span class="sidebar-icon"><?= Icon::get('repeat') ?></span>
          <span class="sidebar-label">Material Issues (FIFO)</span>
          <span class="sidebar-chevron"><?= Icon::get('chevron-down', 13) ?></span>
        </button>
        <div class="sidebar-submenu" id="submenu-issues">
          <?php if ($permModel->hasPermission($roleId, 'tab-user-issue-entry', $userId)): ?>
            <button type="button" class="sidebar-sub-item active" data-parent-tab="tab-issues" data-workflow-filter="Entry" onclick="Desk.navigateWorkflow('tab-issues', 'Entry', this)">
              <span class="sidebar-sub-icon"><?= Icon::get('edit', 12) ?></span>
              <span>Entry</span>
            </button>
          <?php endif; ?>
          <?php if ($permModel->hasPermission($roleId, 'tab-user-issue-validation', $userId)): ?>
            <button type="button" class="sidebar-sub-item" data-parent-tab="tab-issues" data-workflow-filter="Validation" onclick="Desk.navigateWorkflow('tab-issues', 'Validation', this)">
              <span class="sidebar-sub-icon"><?= Icon::get('check-circle', 12) ?></span>
              <span>Validation</span>
            </button>
          <?php endif; ?>
          <?php if ($permModel->hasPermission($roleId, 'tab-user-issue-approval', $userId)): ?>
            <button type="button" class="sidebar-sub-item" data-parent-tab="tab-issues" data-workflow-filter="Approval" onclick="Desk.navigateWorkflow('tab-issues', 'Approval', this)">
              <span class="sidebar-sub-icon"><?= Icon::get('award', 12) ?></span>
              <span>Approval</span>
            </button>
          <?php endif; ?>
          <?php if ($permModel->hasPermission($roleId, 'tab-user-issue-edit', $userId)): ?>
            <button type="button" class="sidebar-sub-item" data-parent-tab="tab-issues" data-workflow-filter="Unposted Document Edit" onclick="Desk.navigateWorkflow('tab-issues', 'Unposted Document Edit', this)">
              <span class="sidebar-sub-icon"><?= Icon::get('edit-3', 12) ?></span>
              <span>Unposted Document Edit</span>
            </button>
          <?php endif; ?>
          <?php if ($permModel->hasPermission($roleId, 'tab-user-issue-posted-cancel', $userId)): ?>
            <button type="button" class="sidebar-sub-item" data-parent-tab="tab-issues" data-workflow-filter="Posted Cancellation" onclick="Desk.navigateWorkflow('tab-issues', 'Posted Cancellation', this)">
              <span class="sidebar-sub-icon"><?= Icon::get('rotate-ccw', 12) ?></span>
              <span>Posted Cancellation</span>
            </button>
          <?php endif; ?>
          <?php if ($permModel->hasPermission($roleId, 'tab-user-issue-unposted-cancel', $userId)): ?>
            <button type="button" class="sidebar-sub-item" data-parent-tab="tab-issues" data-workflow-filter="Unposted Cancellation" onclick="Desk.navigateWorkflow('tab-issues', 'Unposted Cancellation', this)">
              <span class="sidebar-sub-icon"><?= Icon::get('x-circle', 12) ?></span>
              <span>Unposted Cancellation</span>
            </button>
          <?php endif; ?>
        </div>
      </div>
    <?php endif; ?>
    <?php
    $canPosting = $permModel->canAccessTab('tab-posting', $roleId, $userId) || $permModel->canAccessTab('tab-user-posting', $roleId, $userId) || $canReceipts || $canIssues;
    if ($canPosting):
    ?>
      <button type="button" class="sidebar-nav-item <?= $getActiveClass('tab-posting') ?>" data-tab="tab-posting">
        <span class="sidebar-icon"><?= Icon::get('send') ?></span>
        <span>Posting</span>
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
      <div class="sidebar-nav-group <?= ($getActiveClass('tab-pv-workflow') ? 'open' : '') ?>" id="sidebarGroupPvWorkflow">
        <button type="button" class="sidebar-nav-item sidebar-parent-item <?= $getActiveClass('tab-pv-workflow') ?>" data-tab="tab-pv-workflow" data-toggle-group="sidebarGroupPvWorkflow">
          <span class="sidebar-icon"><?= Icon::get('check-square') ?></span>
          <span class="sidebar-label">Verification Workflow</span>
          <span class="sidebar-chevron"><?= Icon::get('chevron-down', 13) ?></span>
        </button>
        <div class="sidebar-submenu" id="submenu-pv-workflow">
          <button type="button" class="sidebar-sub-item active" data-parent-tab="tab-pv-workflow" data-pv-sub="pv-sub-freeze" onclick="Desk.navigatePvSub('pv-sub-freeze', this)">
            <span class="sidebar-sub-icon"><?= Icon::get('lock', 12) ?></span>
            <span>Freeze Batches</span>
          </button>
          <button type="button" class="sidebar-sub-item" data-parent-tab="tab-pv-workflow" data-pv-sub="pv-sub-generate" onclick="Desk.navigatePvSub('pv-sub-generate', this)">
            <span class="sidebar-sub-icon"><?= Icon::get('file-plus', 12) ?></span>
            <span>Generate Sheet</span>
          </button>
          <button type="button" class="sidebar-sub-item" data-parent-tab="tab-pv-workflow" data-pv-sub="pv-sub-entry" onclick="Desk.navigatePvSub('pv-sub-entry', this)">
            <span class="sidebar-sub-icon"><?= Icon::get('edit', 12) ?></span>
            <span>Count Entry</span>
          </button>
          <button type="button" class="sidebar-sub-item" data-parent-tab="tab-pv-workflow" data-pv-sub="pv-sub-edit" onclick="Desk.navigatePvSub('pv-sub-edit', this)">
            <span class="sidebar-sub-icon"><?= Icon::get('edit-3', 12) ?></span>
            <span>Verification Edit</span>
          </button>
          <button type="button" class="sidebar-sub-item" data-parent-tab="tab-pv-workflow" data-pv-sub="pv-sub-validation" onclick="Desk.navigatePvSub('pv-sub-validation', this)">
            <span class="sidebar-sub-icon"><?= Icon::get('check-circle', 12) ?></span>
            <span>Validation</span>
          </button>
          <button type="button" class="sidebar-sub-item" data-parent-tab="tab-pv-workflow" data-pv-sub="pv-sub-approval" onclick="Desk.navigatePvSub('pv-sub-approval', this)">
            <span class="sidebar-sub-icon"><?= Icon::get('award', 12) ?></span>
            <span>Approval</span>
          </button>
          <button type="button" class="sidebar-sub-item" data-parent-tab="tab-pv-workflow" data-pv-sub="pv-sub-posting" onclick="Desk.navigatePvSub('pv-sub-posting', this)">
            <span class="sidebar-sub-icon"><?= Icon::get('send', 12) ?></span>
            <span>Posting</span>
          </button>
          <button type="button" class="sidebar-sub-item" data-parent-tab="tab-pv-workflow" data-pv-sub="pv-sub-gl" onclick="Desk.navigatePvSub('pv-sub-gl', this)">
            <span class="sidebar-sub-icon"><?= Icon::get('database', 12) ?></span>
            <span>GL Posting</span>
          </button>
          <button type="button" class="sidebar-sub-item" data-parent-tab="tab-pv-workflow" data-pv-sub="pv-sub-unfreeze" onclick="Desk.navigatePvSub('pv-sub-unfreeze', this)">
            <span class="sidebar-sub-icon"><?= Icon::get('unlock', 12) ?></span>
            <span>Unfreeze Batches</span>
          </button>
        </div>
      </div>
    <?php endif; ?>
    <?php if ($canPvReports): ?>
      <div class="sidebar-nav-group <?= ($getActiveClass('tab-pv-reports') ? 'open' : '') ?>" id="sidebarGroupPvReports">
        <button type="button" class="sidebar-nav-item sidebar-parent-item <?= $getActiveClass('tab-pv-reports') ?>" data-tab="tab-pv-reports" data-toggle-group="sidebarGroupPvReports">
          <span class="sidebar-icon"><?= Icon::get('file-check') ?></span>
          <span class="sidebar-label">PHV Reports (AV 7)</span>
          <span class="sidebar-chevron"><?= Icon::get('chevron-down', 13) ?></span>
        </button>
        <div class="sidebar-submenu" id="submenu-pv-reports">
          <button type="button" class="sidebar-sub-item active" data-parent-tab="tab-pv-reports" data-pv-report-sub="pv-sub-avg" onclick="Desk.navigatePvReportSub('pv-sub-avg', this)">
            <span class="sidebar-sub-icon"><?= Icon::get('trending-up', 12) ?></span>
            <span>Avg Consumption</span>
          </button>
          <button type="button" class="sidebar-sub-item" data-parent-tab="tab-pv-reports" data-pv-report-sub="pv-sub-final" onclick="Desk.navigatePvReportSub('pv-sub-final', this)">
            <span class="sidebar-sub-icon"><?= Icon::get('file-text', 12) ?></span>
            <span>Final Report</span>
          </button>
          <button type="button" class="sidebar-sub-item" data-parent-tab="tab-pv-reports" data-pv-report-sub="pv-sub-other" onclick="Desk.navigatePvReportSub('pv-sub-other', this)">
            <span class="sidebar-sub-icon"><?= Icon::get('layers', 12) ?></span>
            <span>Other Reports</span>
          </button>
          <button type="button" class="sidebar-sub-item" data-parent-tab="tab-pv-reports" data-pv-report-sub="pv-sub-summary" onclick="Desk.navigatePvReportSub('pv-sub-summary', this)">
            <span class="sidebar-sub-icon"><?= Icon::get('clipboard', 12) ?></span>
            <span>PHV Sheet Summary</span>
          </button>
          <button type="button" class="sidebar-sub-item" data-parent-tab="tab-pv-reports" data-pv-report-sub="pv-sub-av7" onclick="Desk.navigatePvReportSub('pv-sub-av7', this)">
            <span class="sidebar-sub-icon"><?= Icon::get('file-check', 12) ?></span>
            <span>Form AV 7 Summary</span>
          </button>
        </div>
      </div>
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

  <div class="sidebar-section-title">Master Data</div>
  <button type="button" class="sidebar-nav-item <?= $getActiveClass('tab-codes') ?>" data-tab="tab-codes">
    <span class="sidebar-icon"><?= Icon::get('tag') ?></span>
    <span>Code</span>
  </button>

<?php endif; ?>
</aside>
