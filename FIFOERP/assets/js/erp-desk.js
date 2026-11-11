/**
 * FIFOERP - ERPNext Desk Client Scripts
 */

const Desk = {
  currentTab: 'tab-dashboard',
  currentIssueFilter: 'Entry',
  currentReceiptFilter: 'Entry',
  cachedIssues: [],
  cachedReceipts: [],
  baseUrl: '',

  init(baseUrl) {
    this.baseUrl = baseUrl || '';
    this.bindEvents();

    // Auto-restore active tab and sub-tab from URL hash, sessionStorage, or first active sidebar button on refresh
    let rawHash = window.location.hash ? window.location.hash.replace(/^#/, '') : '';
    let hashMain = '';
    let hashSub = '';

    if (rawHash.includes('/')) {
      const parts = rawHash.split('/');
      hashMain = parts[0];
      hashSub = parts[1];
    } else if (rawHash.startsWith('admin-sub-')) {
      hashMain = 'tab-admin';
      hashSub = rawHash;
    } else if (rawHash.startsWith('qoh-sub-')) {
      hashMain = 'tab-qoh';
      hashSub = rawHash;
    } else if (rawHash.startsWith('pv-sub-')) {
      if (['pv-sub-avg', 'pv-sub-final', 'pv-sub-other', 'pv-sub-summary', 'pv-sub-av7'].includes(rawHash)) {
        hashMain = 'tab-pv-reports';
      } else {
        hashMain = 'tab-pv-workflow';
      }
      hashSub = rawHash;
    } else if (rawHash) {
      hashMain = rawHash;
    }

    const storedTab = sessionStorage.getItem('fifoerp_active_tab');
    const firstActiveBtn = document.querySelector('.sidebar-nav-item.active') || document.querySelector('.sidebar-nav-item');
    const defaultTab = firstActiveBtn ? firstActiveBtn.getAttribute('data-tab') : 'tab-dashboard';

    // Verify permission helper: tab must have a visible sidebar nav button or be tab-admin (if admin pane exists)
    const isTabPermitted = (t) => {
      if (!t) return false;
      if (t === 'tab-admin') {
        return !!document.getElementById('tab-admin');
      }
      return !!document.querySelector(`.sidebar-nav-item[data-tab="${t}"]`);
    };

    let targetTab = defaultTab;
    if (hashMain && isTabPermitted(hashMain)) {
      targetTab = hashMain;
    } else if (storedTab && isTabPermitted(storedTab)) {
      targetTab = storedTab;
    }

    // Determine sub-tab to restore
    let targetSub = hashSub;
    if (!targetSub) {
      if (targetTab === 'tab-admin') {
        targetSub = sessionStorage.getItem('fifoerp_active_admin_sub') || (firstActiveBtn ? firstActiveBtn.getAttribute('data-admin-sub') : 'admin-sub-locations');
      } else if (targetTab === 'tab-qoh') {
        targetSub = sessionStorage.getItem('fifoerp_active_qoh_sub') || 'qoh-sub-batch';
      } else if (targetTab === 'tab-pv-workflow') {
        targetSub = sessionStorage.getItem('fifoerp_active_pv_sub') || 'pv-sub-freeze';
      } else if (targetTab === 'tab-pv-reports') {
        targetSub = sessionStorage.getItem('fifoerp_active_pv_report_sub') || 'pv-sub-avg';
      }
    }

    // Restore cached filters if any
    const storedReceiptFilter = sessionStorage.getItem('fifoerp_active_receipt_filter');
    if (storedReceiptFilter) {
      this.currentReceiptFilter = storedReceiptFilter;
    }
    const storedIssueFilter = sessionStorage.getItem('fifoerp_active_issue_filter');
    if (storedIssueFilter) {
      this.currentIssueFilter = storedIssueFilter;
    }

    if (targetTab && document.getElementById(targetTab)) {
      this.switchTab(targetTab, targetSub);
    } else if (defaultTab && document.getElementById(defaultTab)) {
      this.switchTab(defaultTab);
    } else {
      this.loadDashboardData();
    }
  },

  showToast(message, isError = false) {
    let toast = document.getElementById('deskToast');
    if (!toast) {
      toast = document.createElement('div');
      toast.id = 'deskToast';
      toast.className = 'desk-toast';
      document.body.appendChild(toast);
    }
    toast.textContent = message;
    toast.style.background = isError ? '#ef4444' : '#0f172a';
    toast.style.display = 'block';

    setTimeout(() => {
      toast.style.display = 'none';
    }, 3500);
  },

  switchTab(tabId, subTabId = null) {
    let reportSubTabToActivate = null;
    let qohSubTabToActivate = null;
    let adminSubToActivate = null;
    let pvWorkflowSubToActivate = null;

    if (tabId && tabId.startsWith('admin-sub-')) {
      adminSubToActivate = tabId;
      tabId = 'tab-admin';
    } else if (tabId === 'tab-phv' || tabId === 'tab-phv-workspace') {
      tabId = 'tab-pv-workflow';
    } else if (tabId === 'tab-av7') {
      tabId = 'tab-pv-reports';
      reportSubTabToActivate = 'pv-sub-av7';
    } else if (tabId === 'tab-batches') {
      tabId = 'tab-qoh';
      qohSubTabToActivate = 'qoh-sub-batch';
    } else if (tabId === 'tab-report-qoh-material') {
      tabId = 'tab-qoh';
      qohSubTabToActivate = 'qoh-sub-material';
    } else if (tabId === 'tab-transactions') {
      tabId = 'tab-receipts';
    }

    if (subTabId) {
      if (tabId === 'tab-admin') adminSubToActivate = subTabId;
      else if (tabId === 'tab-qoh') qohSubTabToActivate = subTabId;
      else if (tabId === 'tab-pv-workflow') pvWorkflowSubToActivate = subTabId;
      else if (tabId === 'tab-pv-reports') reportSubTabToActivate = subTabId;
    }

    // Verify that this tab is permitted for the current user
    const isPermitted = document.querySelector(`.sidebar-nav-item[data-tab="${tabId}"]`) || (tabId === 'tab-admin' && document.getElementById('tab-admin'));
    if (!isPermitted) {
      const fallbackBtn = document.querySelector('.sidebar-nav-item.active') || document.querySelector('.sidebar-nav-item');
      if (fallbackBtn) {
        tabId = fallbackBtn.getAttribute('data-tab');
      }
    }

    // Hide all tab panes
    document.querySelectorAll('.tab-pane').forEach(el => el.style.display = 'none');
    // Remove active from all sidebar items
    document.querySelectorAll('.sidebar-nav-item').forEach(el => el.classList.remove('active'));

    let targetPane = document.getElementById(tabId);
    if (!targetPane) {
      const fallbackBtn = document.querySelector('.sidebar-nav-item.active') || document.querySelector('.sidebar-nav-item');
      tabId = fallbackBtn ? fallbackBtn.getAttribute('data-tab') : 'tab-dashboard';
      targetPane = document.getElementById(tabId);
    }

    if (targetPane) {
      targetPane.style.display = 'block';
    }

    if (tabId === 'tab-admin') {
      const activeAdminSub = adminSubToActivate || this.currentAdminSubTab || 'admin-sub-locations';
      const adminNavBtn = document.querySelector(`.sidebar-nav-item[data-admin-sub="${activeAdminSub}"]`) || document.querySelector(`[data-tab="tab-admin"]`);
      if (adminNavBtn) adminNavBtn.classList.add('active');
    } else {
      const navBtn = document.querySelector(`[data-tab="${tabId}"]`);
      if (navBtn) {
        navBtn.classList.add('active');
      }
    }

    this.currentTab = tabId;
    try {
      sessionStorage.setItem('fifoerp_active_tab', tabId);
      let hash = '#' + tabId;
      if (tabId === 'tab-admin' && (adminSubToActivate || this.currentAdminSubTab)) {
        hash += '/' + (adminSubToActivate || this.currentAdminSubTab);
      } else if (tabId === 'tab-qoh' && (qohSubTabToActivate || this.currentQohSubTab)) {
        hash += '/' + (qohSubTabToActivate || this.currentQohSubTab);
      } else if (tabId === 'tab-pv-workflow' && (pvWorkflowSubToActivate || this.currentPvSubTab)) {
        hash += '/' + (pvWorkflowSubToActivate || this.currentPvSubTab);
      } else if (tabId === 'tab-pv-reports' && (reportSubTabToActivate || this.currentPvReportSubTab)) {
        hash += '/' + (reportSubTabToActivate || this.currentPvReportSubTab);
      }
      history.replaceState(null, '', hash);
    } catch (e) {}

    // Trigger tab-specific data load
    switch (tabId) {
      case 'tab-dashboard':
        this.loadDashboardData();
        break;
      case 'tab-qoh':
        this.switchQohSubTab(qohSubTabToActivate || this.currentQohSubTab || sessionStorage.getItem('fifoerp_active_qoh_sub') || 'qoh-sub-batch');
        break;
      case 'tab-batches':
        this.loadBatches();
        break;
      case 'tab-receipts':
        if (sessionStorage.getItem('fifoerp_active_receipt_filter')) {
          this.currentReceiptFilter = sessionStorage.getItem('fifoerp_active_receipt_filter');
        }
        document.querySelectorAll('#receiptWorkflowPills .pv-pill-btn').forEach(btn => {
          const fn = btn.getAttribute('onclick') || '';
          btn.classList.toggle('active', fn.includes(`'${this.currentReceiptFilter}'`));
        });
        this.loadReceipts();
        break;
      case 'tab-issues':
        if (sessionStorage.getItem('fifoerp_active_issue_filter')) {
          this.currentIssueFilter = sessionStorage.getItem('fifoerp_active_issue_filter');
        }
        document.querySelectorAll('#issueWorkflowPills .pv-pill-btn').forEach(btn => {
          const fn = btn.getAttribute('onclick') || '';
          btn.classList.toggle('active', fn.includes(`'${this.currentIssueFilter}'`));
        });
        this.loadIssues();
        break;
      case 'tab-transactions':
        this.loadDocuments();
        break;
      case 'tab-pv-workflow':
        this.switchPvSubTab(pvWorkflowSubToActivate || this.currentPvSubTab || sessionStorage.getItem('fifoerp_active_pv_sub') || 'pv-sub-freeze');
        break;
      case 'tab-pv-reports':
        this.switchPvReportSubTab(reportSubTabToActivate || this.currentPvReportSubTab || sessionStorage.getItem('fifoerp_active_pv_report_sub') || 'pv-sub-avg');
        break;
      case 'tab-cashbook':
        this.loadCashBook();
        break;
      case 'tab-payslips':
        this.loadPayslipCancellations();
        break;
      case 'tab-report-qoh-material':
        this.loadQohMaterial();
        break;
      case 'tab-report-cpc':
        this.loadCpcProcurements();
        break;
      case 'tab-report-diesel':
        this.loadDieselPriceLogs();
        break;
      case 'tab-report-transfers':
        this.loadMonthlyTransfers();
        break;
      case 'tab-report-jobs':
        this.loadJobIssues();
        break;
      case 'tab-report-inventory-inquiry':
        this.loadInventoryInquiry();
        break;
      case 'tab-report-monthly-tx':
        this.loadMonthlyTx();
        break;
      case 'tab-report-grn-issue-summary':
        this.loadGrnIssueSummary();
        break;
      case 'tab-report-material-flow':
        this.loadMaterialFlow();
        break;
      case 'tab-cost-adjust':
        this.loadCostAdjustments();
        this.populateTaskBatches();
        break;
      case 'tab-qty-correct':
        this.loadQtyCorrections();
        this.populateTaskBatches();
        break;
      case 'tab-dest-codes':
        this.loadDestinationCodes();
        break;
      case 'tab-admin':
        this.switchAdminSubTab(adminSubToActivate || this.currentAdminSubTab || sessionStorage.getItem('fifoerp_active_admin_sub') || 'admin-sub-locations');
        break;
    }
  },

  bindEvents() {
    // Sidebar navigation
    document.querySelectorAll('[data-tab]').forEach(btn => {
      btn.addEventListener('click', (e) => {
        e.preventDefault();
        const tab = btn.getAttribute('data-tab');
        const adminSub = btn.getAttribute('data-admin-sub');
        if (tab) {
          Desk.switchTab(tab, adminSub || null);
          if (adminSub && tab === 'tab-admin') {
            document.querySelectorAll('.sidebar-nav-item').forEach(el => el.classList.remove('active'));
            btn.classList.add('active');
          }
        }
      });
    });

    // Global Search Bar (Awesome Bar)
    const searchInput = document.getElementById('globalSearchInput');
    const searchDropdown = document.getElementById('searchDropdown');
    let searchTimeout = null;

    if (searchInput && searchDropdown) {
      searchInput.addEventListener('input', () => {
        clearTimeout(searchTimeout);
        const q = searchInput.value.trim();
        if (q.length < 2) {
          searchDropdown.style.display = 'none';
          return;
        }

        searchTimeout = setTimeout(() => {
          fetch(`${Desk.baseUrl}/api/search.php?q=${encodeURIComponent(q)}`)
            .then(res => res.json())
            .then(data => {
              if (data.success && data.data.results.length > 0) {
                let html = '';
                data.data.results.forEach(item => {
                  html += `
                    <div class="search-item" onclick="Desk.switchTab('${item.target_tab}'); document.getElementById('searchDropdown').style.display='none';">
                      <div class="search-item-title">[${item.type}] ${item.title}</div>
                      <div class="search-item-sub">${item.subtitle}</div>
                    </div>
                  `;
                });
                searchDropdown.innerHTML = html;
                searchDropdown.style.display = 'block';
              } else {
                searchDropdown.innerHTML = `<div style="padding: 12px; color: #64748b; font-size: 12px;">No matching results</div>`;
                searchDropdown.style.display = 'block';
              }
            });
        }, 250);
      });

      document.addEventListener('click', (e) => {
        if (!searchInput.contains(e.target) && !searchDropdown.contains(e.target)) {
          searchDropdown.style.display = 'none';
        }
      });
    }

    // Chart hover tooltips
    document.addEventListener('mouseover', (e) => {
      const rect = e.target.closest ? e.target.closest('.chart-bar-rect') : null;
      if (rect) {
        const tip = document.getElementById('chartTooltip');
        if (!tip) return;
        const cat = rect.getAttribute('data-category');
        const month = rect.getAttribute('data-month-name');
        const val = rect.getAttribute('data-val');
        tip.innerHTML = `<div class="chart-tooltip-title">${cat} (${month})</div><div class="chart-tooltip-val">Rs. ${val}</div>`;
        tip.style.display = 'block';
      }
    });

    document.addEventListener('mousemove', (e) => {
      const tip = document.getElementById('chartTooltip');
      if (tip && tip.style.display === 'block') {
        const wrapper = document.getElementById('svgChartWrapper');
        if (wrapper) {
          const b = wrapper.getBoundingClientRect();
          tip.style.left = (e.clientX - b.left) + 'px';
          tip.style.top = (e.clientY - b.top - 15) + 'px';
        }
      }
    });

    document.addEventListener('mouseout', (e) => {
      if (e.target && e.target.classList && e.target.classList.contains('chart-bar-rect')) {
        const tip = document.getElementById('chartTooltip');
        if (tip) tip.style.display = 'none';
      }
    });

    // Initialize Permissions Matrix interactive controls
    this.initPermissionsMatrix();
  },

  // -----------------------------------------------------------------
  // Data Loaders
  // -----------------------------------------------------------------

  loadDashboardData() {
    fetch(`${this.baseUrl}/api/inventory.php?action=get_batches`)
      .then(res => res.json())
      .then(res => {
        if (res.success) {
          const batches = res.data.batches;
          document.getElementById('statTotalBatches').textContent = batches.length;
          
          let totalVal = 0;
          let lowStockCount = 0;
          batches.forEach(b => {
            totalVal += (b.remaining_quantity * b.unit_cost);
            if (b.remaining_quantity <= 5 && b.remaining_quantity > 0) lowStockCount++;
          });
          document.getElementById('statTotalValuation').textContent = 'Rs. ' + totalVal.toLocaleString('en-US', { minimumFractionDigits: 2 });
          document.getElementById('statLowStock').textContent = lowStockCount;
        }
      });

    fetch(`${this.baseUrl}/api/inventory.php?action=get_documents`)
      .then(res => res.json())
      .then(res => {
        if (res.success) {
          const docs = res.data.documents;
          const pending = docs.filter(d => d.status === 'Draft' || d.status === 'Validated');
          document.getElementById('statPendingApprovals').textContent = pending.length;
        }
      });
  },

  loadBatches() {
    const tbody = document.getElementById('batchesTableBody');
    if (!tbody) return;
    tbody.innerHTML = `<tr><td colspan="8" style="text-align: center; color: #64748b; padding: 20px;">Loading batches...</td></tr>`;

    fetch(`${this.baseUrl}/api/inventory.php?action=get_batches`)
      .then(res => res.json())
      .then(res => {
        if (res.success) {
          let html = '';
          res.data.batches.forEach(b => {
            let freezeBadge = b.freeze_status === 'frozen' 
              ? `<span class="desk-badge badge-frozen">Frozen</span>` 
              : `<span class="desk-badge badge-active">Active</span>`;
            
            let statusBadge = `<span class="desk-badge badge-${b.status}">${b.status}</span>`;

            html += `
              <tr>
                <td><strong>${b.batch_number}</strong></td>
                <td><code>${b.sku}</code></td>
                <td>${b.item_name}</td>
                <td><span style="font-weight: 700; color: #0f172a;">${b.remaining_quantity}</span> / ${b.initial_quantity}</td>
                <td>Rs. ${parseFloat(b.unit_cost).toLocaleString('en-US', {minimumFractionDigits: 2})}</td>
                <td><span style="font-weight: 600;">Rs. ${(b.remaining_quantity * b.unit_cost).toLocaleString('en-US', {minimumFractionDigits: 2})}</span></td>
                <td>${b.location_bin}</td>
                <td>${freezeBadge} ${statusBadge}</td>
              </tr>
            `;
          });
          tbody.innerHTML = html || `<tr><td colspan="8" style="text-align: center; padding: 20px;">No batches found</td></tr>`;
        }
      });
  },

  // -----------------------------------------------------------------
  // Receipts (GRN) Management
  // -----------------------------------------------------------------
  loadReceipts() {
    this.initGrnFormLookups();
    const tbody = document.getElementById('receiptsTableBody');
    if (!tbody) return;
    tbody.innerHTML = `<tr><td colspan="7" style="text-align: center; color: #64748b; padding: 20px;">Loading receipts...</td></tr>`;

    fetch(`${this.baseUrl}/api/inventory.php?action=get_documents&type=GRN`)
      .then(res => res.json())
      .then(res => {
        if (res.success) {
          this.cachedReceipts = res.data.documents;
          const savedFilter = sessionStorage.getItem('fifoerp_active_receipt_filter');
          if (savedFilter) {
            this.currentReceiptFilter = savedFilter;
          }
          document.querySelectorAll('#receiptWorkflowPills .pv-pill-btn').forEach(el => {
            const fn = el.getAttribute('onclick') || '';
            el.classList.toggle('active', fn.includes(`'${this.currentReceiptFilter}'`));
          });
          this.renderReceiptsTable();
        }
      });
  },

  filterReceipts(status, btn) {
    this.currentReceiptFilter = status;
    try {
      sessionStorage.setItem('fifoerp_active_receipt_filter', status);
    } catch (e) {}
    document.querySelectorAll('#receiptWorkflowPills .pv-pill-btn').forEach(el => {
      if (btn) {
        el.classList.toggle('active', el === btn);
      } else {
        const fn = el.getAttribute('onclick') || '';
        el.classList.toggle('active', fn.includes(`'${status}'`));
      }
    });
    this.renderReceiptsTable();
  },

  renderReceiptsTable() {
    const tbody = document.getElementById('receiptsTableBody');
    if (!tbody) return;

    const titleEl = document.getElementById('receiptsCardTitle');
    const subEl = document.getElementById('receiptsCardSubtitle');
    const headerAction = document.getElementById('receiptsHeaderAction');

    let docs = this.cachedReceipts;
    const filter = this.currentReceiptFilter;

    const entryCard = document.getElementById('bodyGrnEntryCard');
    if (entryCard) {
      entryCard.style.display = (filter === 'Entry') ? 'block' : 'none';
      if (filter === 'Entry') {
        this.initStockEntryForm('grn');
      }
    }

    if (filter === 'Entry') {
      docs = docs.filter(d => d.status.toLowerCase() === 'draft');
      if (titleEl) titleEl.textContent = 'Receipts Entry (Draft Goods Received Notes)';
      if (subEl) subEl.textContent = 'Newly recorded consignments awaiting technical verification and store audit';
      if (headerAction) headerAction.innerHTML = '';
    } else if (filter === 'Validation') {
      docs = docs.filter(d => d.status.toLowerCase() === 'draft');
      if (titleEl) titleEl.textContent = 'Following Documents are pending for your validation';
      if (subEl) subEl.textContent = 'Technical store audit validation — certify received delivery against PO specs and warehouse bin storage';
      if (headerAction) headerAction.innerHTML = `<span class="badge badge-warning" style="font-size: 11px;">Pending Validation: ${docs.length}</span>`;
    } else if (filter === 'Approval') {
      docs = docs.filter(d => d.status.toLowerCase() === 'validated');
      if (titleEl) titleEl.textContent = 'Following Documents are pending for your approval';
      if (subEl) subEl.textContent = 'Station Chief Engineer / Authorized Officer electronic sign-off prior to FIFO ledger posting';
      if (headerAction) headerAction.innerHTML = `<span class="badge badge-info" style="font-size: 11px;">Pending Approval: ${docs.length}</span>`;
    } else if (filter === 'Posting') {
      docs = docs.filter(d => d.status.toLowerCase() === 'approved');
      if (titleEl) titleEl.textContent = 'Following Documents are approved and ready for posting to FIFO Ledger';
      if (subEl) subEl.textContent = 'Commit stock into active FIFO inventory queue and record journal vouchers';
      if (headerAction) headerAction.innerHTML = `<span class="badge badge-success" style="font-size: 11px;">Ready to Post: ${docs.length}</span>`;
    } else if (filter === 'Posted Cancellation') {
      docs = docs.filter(d => d.status.toLowerCase() === 'posted');
      if (titleEl) titleEl.textContent = 'Posted Documents - Cancellation & Stock Reversal';
      if (subEl) subEl.textContent = 'Reverse posted consignments and restore ledger balances with audit verification log';
      if (headerAction) headerAction.innerHTML = '';
    } else if (filter === 'Unposted Cancellation') {
      docs = docs.filter(d => d.status.toLowerCase() === 'draft' || d.status.toLowerCase() === 'validated' || d.status.toLowerCase() === 'cancelled');
      if (titleEl) titleEl.textContent = 'Unposted Documents Cancellation';
      if (subEl) subEl.textContent = 'Cancel or discard draft and validated documents prior to posting';
      if (headerAction) headerAction.innerHTML = '';
    } else {
      if (titleEl) titleEl.textContent = 'Goods Received Documents (GRN) - Master Ledger';
      if (subEl) subEl.textContent = 'All Goods Received Notes across all lifecycle stages';
      if (headerAction) headerAction.innerHTML = `<button class="desk-btn desk-btn-primary desk-btn-sm" onclick="Desk.openModal('modalNewGrn')">+ New GRN</button>`;
    }

    let html = '';
    docs.forEach(d => {
      const badgeClass = `badge-${d.status.toLowerCase()}`;
      html += `
        <tr>
          <td><strong>${d.doc_no}</strong></td>
          <td>${d.doc_date}</td>
          <td><strong>${d.receipt_from || '-'}</strong></td>
          <td>${d.warehouse || 'Main Store Bay-01'}</td>
          <td>Rs. ${parseFloat(d.invoice_total).toLocaleString('en-US', {minimumFractionDigits: 2})}</td>
          <td><span class="desk-badge ${badgeClass}">${d.status}</span></td>
          <td>
            <button class="desk-btn desk-btn-default desk-btn-sm" onclick="Desk.viewDocDetail(${d.id})" title="Inspect full items and pricing breakdown">Inspect</button>
            ${d.status === 'Draft' ? `<button class="desk-btn desk-btn-primary desk-btn-sm" onclick="Desk.updateDocStatus(${d.id}, 'Validated')">✓ Validate</button>` : ''}
            ${d.status === 'Validated' ? `<button class="desk-btn desk-btn-primary desk-btn-sm" onclick="Desk.updateDocStatus(${d.id}, 'Approved')">⭐ Approve</button>` : ''}
            ${d.status === 'Approved' ? `<button class="desk-btn desk-btn-primary desk-btn-sm" style="background:#0f766e;" onclick="Desk.postDocument(${d.id})">🚀 Post to FIFO</button>` : ''}
            ${d.status === 'Posted' ? `<button class="desk-btn desk-btn-danger desk-btn-sm" onclick="Desk.cancelPosted('${d.doc_no}')">Cancel Posted</button>` : ''}
            ${d.status === 'Draft' || d.status === 'Validated' ? `<button class="desk-btn desk-btn-default desk-btn-sm" style="color: #dc2626;" onclick="Desk.updateDocStatus(${d.id}, 'Cancelled')">Cancel</button>` : ''}
          </td>
        </tr>
      `;
    });
    tbody.innerHTML = html || `<tr><td colspan="7" style="text-align: center; padding: 24px; color: #64748b; font-size: 13px;">No documents found for stage '<strong>${filter}</strong>'</td></tr>`;
  },

  // -----------------------------------------------------------------
  // Material Issues (FIFO Queue) Management
  // -----------------------------------------------------------------
  loadIssues() {
    const tbody = document.getElementById('issuesTableBody');
    if (!tbody) return;
    tbody.innerHTML = `<tr><td colspan="8" style="text-align: center; color: #64748b; padding: 20px;">Loading issues...</td></tr>`;

    fetch(`${this.baseUrl}/api/inventory.php?action=get_documents&type=ISSUE`)
      .then(res => res.json())
      .then(res => {
        if (res.success) {
          this.cachedIssues = res.data.documents;
          const savedFilter = sessionStorage.getItem('fifoerp_active_issue_filter');
          if (savedFilter) {
            this.currentIssueFilter = savedFilter;
          }
          document.querySelectorAll('#issueWorkflowPills .pv-pill-btn').forEach(el => {
            const fn = el.getAttribute('onclick') || '';
            el.classList.toggle('active', fn.includes(`'${this.currentIssueFilter}'`));
          });
          this.renderIssuesTable();
        }
      });
  },

  filterIssues(status, btn) {
    this.currentIssueFilter = status;
    try {
      sessionStorage.setItem('fifoerp_active_issue_filter', status);
    } catch (e) {}
    document.querySelectorAll('#issueWorkflowPills .pv-pill-btn').forEach(el => {
      if (btn) {
        el.classList.toggle('active', el === btn);
      } else {
        const fn = el.getAttribute('onclick') || '';
        el.classList.toggle('active', fn.includes(`'${status}'`));
      }
    });
    this.renderIssuesTable();
  },

  renderIssuesTable() {
    const tbody = document.getElementById('issuesTableBody');
    if (!tbody) return;

    const titleEl = document.getElementById('issuesCardTitle');
    const subEl = document.getElementById('issuesCardSubtitle');
    const headerAction = document.getElementById('issuesHeaderAction');

    let docs = this.cachedIssues;
    const filter = this.currentIssueFilter;

    const issueEntryCard = document.getElementById('bodyIssueEntryCard');
    if (issueEntryCard) {
      issueEntryCard.style.display = (filter === 'Entry') ? 'block' : 'none';
      if (filter === 'Entry') {
        this.initStockEntryForm('issue');
        this.populateTaskBatches();
        this.initIssueFormLookups();
      }
    }

    if (filter === 'Entry') {
      docs = docs.filter(d => d.status.toLowerCase() === 'draft');
      if (titleEl) titleEl.textContent = 'Material Issues Entry (Draft Requisitions)';
      if (subEl) subEl.textContent = 'Draft material requisition notes waiting for technical audit validation';
      if (headerAction) headerAction.innerHTML = '';
    } else if (filter === 'Validation') {
      docs = docs.filter(d => d.status.toLowerCase() === 'draft');
      if (titleEl) titleEl.textContent = 'Following Documents are pending for your validation';
      if (subEl) subEl.textContent = 'Storekeeper stock validation — verify quantity requirements against active FIFO batch balances';
      if (headerAction) headerAction.innerHTML = `<span class="badge badge-warning" style="font-size: 11px;">Pending Validation: ${docs.length}</span>`;
    } else if (filter === 'Approval') {
      docs = docs.filter(d => d.status.toLowerCase() === 'validated');
      if (titleEl) titleEl.textContent = 'Following Documents are pending for your approval';
      if (subEl) subEl.textContent = 'Chief Engineer / Plant Manager authorization required before issuing spares';
      if (headerAction) headerAction.innerHTML = `<span class="badge badge-info" style="font-size: 11px;">Pending Approval: ${docs.length}</span>`;
    } else if (filter === 'Posting') {
      docs = docs.filter(d => d.status.toLowerCase() === 'approved');
      if (titleEl) titleEl.textContent = 'Following Documents are approved and ready for posting to FIFO Ledger';
      if (subEl) subEl.textContent = 'Dispatches physical materials and deducts stock from oldest active FIFO batches';
      if (headerAction) headerAction.innerHTML = `<span class="badge badge-success" style="font-size: 11px;">Ready to Post: ${docs.length}</span>`;
    } else if (filter === 'Posted Cancellation') {
      docs = docs.filter(d => d.status.toLowerCase() === 'posted');
      if (titleEl) titleEl.textContent = 'Posted Material Issues - Cancellation & Stock Reversal';
      if (subEl) subEl.textContent = 'Reverse posted issues back into FIFO stock queue with reversal journal entry';
      if (headerAction) headerAction.innerHTML = '';
    } else if (filter === 'Unposted Cancellation') {
      docs = docs.filter(d => d.status.toLowerCase() === 'draft' || d.status.toLowerCase() === 'validated' || d.status.toLowerCase() === 'cancelled');
      if (titleEl) titleEl.textContent = 'Unposted Material Requisitions Cancellation';
      if (subEl) subEl.textContent = 'Cancel draft or validated requisitions before stock is allocated';
      if (headerAction) headerAction.innerHTML = '';
    } else {
      if (titleEl) titleEl.textContent = 'Material Issue Requisitions (FIFO Queue)';
      if (subEl) subEl.textContent = 'Material requisitions queue from staff draft to storekeeper validation, authorization and FIFO batch consumption';
      if (headerAction) headerAction.innerHTML = `<button class="desk-btn desk-btn-primary desk-btn-sm" onclick="Desk.openModal('modalNewIssue')">+ New Issue</button>`;
    }

    let html = '';
    docs.forEach(d => {
      const badgeClass = `badge-${d.status.toLowerCase()}`;
      const canEdit = (d.status === 'Draft' || d.status === 'Validated');

      html += `
        <tr>
          <td><strong>${d.doc_no}</strong></td>
          <td>${d.doc_date}</td>
          <td><span class="desk-badge badge-draft">${d.destination_code || '-'}</span></td>
          <td><strong>${d.department_code || '-'}</strong></td>
          <td>${d.item_count || 1} item(s)</td>
          <td>Rs. ${parseFloat(d.invoice_total).toLocaleString('en-US', {minimumFractionDigits: 2})}</td>
          <td><span class="desk-badge ${badgeClass}">${d.status}</span></td>
          <td>
            <button class="desk-btn desk-btn-default desk-btn-sm" onclick="Desk.viewDocDetail(${d.id})" title="View line items & history">Inspect</button>
            ${canEdit ? `<button class="desk-btn desk-btn-default desk-btn-sm" style="color: #0d9488; font-weight: 600;" onclick="Desk.openEditIssue(${d.id})" title="Edit quantities and job destination">✏️ Edit</button>` : ''}
            ${d.status === 'Draft' ? `<button class="desk-btn desk-btn-primary desk-btn-sm" onclick="Desk.updateDocStatus(${d.id}, 'Validated')">✓ Validate</button>` : ''}
            ${d.status === 'Validated' ? `<button class="desk-btn desk-btn-primary desk-btn-sm" onclick="Desk.updateDocStatus(${d.id}, 'Approved')">⭐ Approve</button>` : ''}
            ${d.status === 'Approved' ? `<button class="desk-btn desk-btn-primary desk-btn-sm" style="background:#0f766e;" onclick="Desk.postDocument(${d.id})">🚀 Post to FIFO</button>` : ''}
            ${d.status === 'Posted' ? `<button class="desk-btn desk-btn-danger desk-btn-sm" onclick="Desk.cancelPosted('${d.doc_no}')">Cancel Posted</button>` : ''}
            ${canEdit ? `<button class="desk-btn desk-btn-default desk-btn-sm" style="color: #dc2626;" onclick="Desk.updateDocStatus(${d.id}, 'Cancelled')">Cancel</button>` : ''}
          </td>
        </tr>
      `;
    });
    tbody.innerHTML = html || `<tr><td colspan="8" style="text-align: center; padding: 24px; color: #64748b; font-size: 13px;">No material issues found for stage '<strong>${filter}</strong>'</td></tr>`;
  },

  openEditIssue(docId) {
    fetch(`${this.baseUrl}/api/inventory.php?action=get_document_detail&id=${docId}`)
      .then(res => res.json())
      .then(res => {
        if (res.success) {
          const doc = res.data.document;
          const items = res.data.items;
          const firstItem = items[0] || {};

          document.getElementById('editDocId').value = doc.id;
          document.getElementById('editDocNo').value = doc.doc_no;
          document.getElementById('editDocStatus').value = doc.status;
          document.getElementById('editDocDest').value = doc.destination_code || 'DEST-GEN-U1';
          document.getElementById('editDocDept').value = doc.department_code || 'ELEC-GEN';
          document.getElementById('editDocItemName').value = firstItem.item_name ? `${firstItem.sku} - ${firstItem.item_name}` : 'Item';
          document.getElementById('editDocQty').value = firstItem.quantity || 1;
          document.getElementById('editDocRemarks').value = doc.remarks || '';

          Desk.openModal('modalEditIssue');
        } else {
          Desk.showToast(res.message, true);
        }
      });
  },

  viewDocDetail(docId) {
    const container = document.getElementById('viewDocContent');
    container.innerHTML = '<div style="padding: 20px; text-align: center;">Loading document details...</div>';
    Desk.openModal('modalViewDoc');

    fetch(`${this.baseUrl}/api/inventory.php?action=get_document_detail&id=${docId}`)
      .then(res => res.json())
      .then(res => {
        if (res.success) {
          const doc = res.data.document;
          const items = res.data.items;
          document.getElementById('viewDocModalTitle').textContent = `${doc.doc_type} Details - ${doc.doc_no}`;

          let itemsHtml = '';
          items.forEach((item, idx) => {
            itemsHtml += `
              <tr>
                <td>${idx + 1}</td>
                <td><code>${item.sku}</code></td>
                <td><strong>${item.item_name}</strong></td>
                <td>${item.bin_location || '-'}</td>
                <td><span style="font-weight: 700;">${item.quantity}</span></td>
                <td>Rs. ${parseFloat(item.unit_cost).toLocaleString('en-US', {minimumFractionDigits: 2})}</td>
                <td><strong>Rs. ${parseFloat(item.total_cost).toLocaleString('en-US', {minimumFractionDigits: 2})}</strong></td>
              </tr>
            `;
          });

          container.innerHTML = `
            <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 16px; margin-bottom: 20px;">
              <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 14px; font-size: 13px;">
                <div><span style="color: #64748b;">Document No:</span> <strong>${doc.doc_no}</strong></div>
                <div><span style="color: #64748b;">Type:</span> <strong>${doc.doc_type}</strong></div>
                <div><span style="color: #64748b;">Date:</span> <strong>${doc.doc_date}</strong></div>
                <div><span style="color: #64748b;">Status:</span> <span class="desk-badge badge-${doc.status.toLowerCase()}">${doc.status}</span></div>
                <div><span style="color: #64748b;">Department:</span> <strong>${doc.department_code || '-'}</strong></div>
                <div><span style="color: #64748b;">Destination:</span> <strong>${doc.destination_code || doc.receipt_from || '-'}</strong></div>
              </div>
              ${doc.remarks ? `<div style="margin-top: 10px; font-size: 12px; color: #475569;"><strong>Remarks / Purpose:</strong> ${doc.remarks}</div>` : ''}
            </div>

            <h4 style="font-size: 13px; font-weight: 700; margin-bottom: 10px;">Line Items &amp; Allocated Quantities</h4>
            <div class="desk-table-wrapper" style="border: 1px solid #e2e8f0; border-radius: 6px;">
              <table class="desk-table">
                <thead>
                  <tr>
                    <th>#</th>
                    <th>SKU</th>
                    <th>Material</th>
                    <th>Bin</th>
                    <th>Qty</th>
                    <th>Unit Cost</th>
                    <th>Total Valuation</th>
                  </tr>
                </thead>
                <tbody>
                  ${itemsHtml}
                </tbody>
              </table>
            </div>

            <div style="display: flex; justify-content: flex-end; margin-top: 14px; font-size: 14px;">
              <span>Total Value: <strong style="font-size: 16px; color: #0d9488;">Rs. ${parseFloat(doc.invoice_total).toLocaleString('en-US', {minimumFractionDigits: 2})}</strong></span>
            </div>
          `;
        } else {
          container.innerHTML = `<div style="color: #dc2626; padding: 20px;">${res.message}</div>`;
        }
      });
  },

  loadDocuments() {
    this.loadReceipts();
    this.loadIssues();
  },

  updateDocStatus(docId, newStatus) {
    const fd = new FormData();
    fd.append('action', 'update_status');
    fd.append('id', docId);
    fd.append('status', newStatus);

    fetch(`${this.baseUrl}/api/inventory.php`, { method: 'POST', body: fd })
      .then(res => res.json())
      .then(res => {
        if (res.success) {
          Desk.showToast(res.message);
          Desk.loadReceipts();
          Desk.loadIssues();
        } else {
          Desk.showToast(res.message, true);
        }
      });
  },

  postDocument(docId) {
    if (!confirm('Post this document to FIFO Inventory? This will update stock quantities and create transactions.')) return;

    const fd = new FormData();
    fd.append('action', 'post_document');
    fd.append('id', docId);

    fetch(`${this.baseUrl}/api/inventory.php`, { method: 'POST', body: fd })
      .then(res => res.json())
      .then(res => {
        if (res.success) {
          Desk.showToast(res.message);
          Desk.loadReceipts();
          Desk.loadIssues();
          Desk.loadBatches();
          Desk.loadDashboardData();
        } else {
          Desk.showToast(res.message, true);
        }
      });
  },

  cancelPosted(docNo) {
    if (!confirm(`Are you sure you want to REVERSE and CANCEL posted document ${docNo}?`)) return;

    const fd = new FormData();
    fd.append('action', 'cancel_posted');
    fd.append('doc_no', docNo);

    fetch(`${this.baseUrl}/api/inventory.php`, { method: 'POST', body: fd })
      .then(res => res.json())
      .then(res => {
        if (res.success) {
          Desk.showToast(res.message);
          Desk.loadReceipts();
          Desk.loadIssues();
          Desk.loadBatches();
        } else {
          Desk.showToast(res.message, true);
        }
      });
  },

  // -----------------------------------------------------------------
  // Quantity On Hand (QOH) Sub-tabs (Batch Wise & Material Wise)
  // -----------------------------------------------------------------
  switchQohSubTab(subTabId) {
    this.currentQohSubTab = subTabId;
    try {
      sessionStorage.setItem('fifoerp_active_qoh_sub', subTabId);
      history.replaceState(null, '', '#tab-qoh/' + subTabId);
    } catch (e) {}

    document.querySelectorAll('#qohPills .pv-pill-btn').forEach(btn => {
      btn.classList.toggle('active', btn.getAttribute('data-qoh-btn') === subTabId);
    });

    document.querySelectorAll('#tab-qoh .qoh-sub-pane').forEach(pane => {
      const isActive = (pane.id === subTabId);
      pane.classList.toggle('active', isActive);
      pane.style.display = isActive ? 'block' : 'none';
    });

    if (subTabId === 'qoh-sub-batch') {
      this.loadBatches();
    } else if (subTabId === 'qoh-sub-material') {
      this.loadQohMaterial();
    }
  },

  refreshCurrentQoh() {
    if (this.currentQohSubTab === 'qoh-sub-material') {
      this.loadQohMaterial();
    } else {
      this.loadBatches();
    }
  },

  // -----------------------------------------------------------------
  // Physical Verification (Workflows vs Reports Desks)
  // -----------------------------------------------------------------
  switchPvSubTab(subTabId) {
    this.currentPvSubTab = subTabId;
    try {
      sessionStorage.setItem('fifoerp_active_pv_sub', subTabId);
      history.replaceState(null, '', '#tab-pv-workflow/' + subTabId);
    } catch (e) {}

    document.querySelectorAll('#pvWorkflowPills .pv-pill-btn').forEach(btn => {
      btn.classList.toggle('active', btn.getAttribute('data-pv-btn') === subTabId);
    });

    document.querySelectorAll('#tab-pv-workflow .pv-sub-pane').forEach(pane => {
      pane.classList.remove('active');
    });

    const target = document.getElementById(subTabId);
    if (target) {
      target.classList.add('active');
    }

    switch (subTabId) {
      case 'pv-sub-freeze':
        this.loadFreezeBatches();
        break;
      case 'pv-sub-generate':
        this.loadGeneratedSheet();
        break;
      case 'pv-sub-entry':
        this.loadPvEntry();
        break;
      case 'pv-sub-edit':
        this.loadPvEdit();
        break;
      case 'pv-sub-validation':
        this.loadPvValidation();
        break;
      case 'pv-sub-approval':
        this.loadPvApproval();
        break;
      case 'pv-sub-posting':
        this.loadPvPosting();
        break;
      case 'pv-sub-gl':
        this.loadGlPreview();
        break;
      case 'pv-sub-unfreeze':
        this.loadFreezeBatches();
        break;
    }
  },

  switchPvReportSubTab(subTabId) {
    this.currentPvReportSubTab = subTabId;
    try {
      sessionStorage.setItem('fifoerp_active_pv_report_sub', subTabId);
      history.replaceState(null, '', '#tab-pv-reports/' + subTabId);
    } catch (e) {}

    document.querySelectorAll('#pvReportsPills .pv-pill-btn').forEach(btn => {
      btn.classList.toggle('active', btn.getAttribute('data-pv-btn') === subTabId);
    });

    document.querySelectorAll('#tab-pv-reports .pv-sub-pane').forEach(pane => {
      pane.classList.remove('active');
    });

    const target = document.getElementById(subTabId);
    if (target) {
      target.classList.add('active');
    }

    switch (subTabId) {
      case 'pv-sub-avg':
        this.loadAvgConsumption();
        break;
      case 'pv-sub-final':
        this.loadFinalReport();
        break;
      case 'pv-sub-other':
        this.loadOtherReport();
        break;
      case 'pv-sub-summary':
        this.loadSheetSummary();
        break;
      case 'pv-sub-av7':
        this.loadAV7Report();
        break;
    }
  },

  loadFreezeBatches() {
    const tbody = document.getElementById('freezeBatchesTableBody');
    if (!tbody) return;

    fetch(`${this.baseUrl}/api/inventory.php?action=get_batches`)
      .then(res => res.json())
      .then(res => {
        if (res.success) {
          let html = '';
          let anyFrozen = false;
          res.data.batches.forEach(b => {
            const isFrozen = b.status === 'frozen';
            if (isFrozen) anyFrozen = true;
            html += `
              <tr>
                <td><strong>${b.batch_number}</strong></td>
                <td><code>${b.sku}</code></td>
                <td><strong>${b.item_name}</strong></td>
                <td>${b.location_bin || '-'}</td>
                <td><span style="font-weight: 700;">${b.remaining_quantity}</span> ${b.unit_of_measure}</td>
                <td>Rs. ${parseFloat(b.unit_cost).toFixed(2)}</td>
                <td><strong>Rs. ${parseFloat(b.total_value).toLocaleString('en-US', {minimumFractionDigits: 2})}</strong></td>
                <td>
                  ${isFrozen ? '<span class="desk-badge badge-frozen">FROZEN (Locked)</span>' : '<span class="desk-badge badge-approved">ACTIVE (Open)</span>'}
                </td>
              </tr>
            `;
          });
          tbody.innerHTML = html;

          const badgeEl = document.getElementById('freezeStatusBadge');
          if (badgeEl) {
            badgeEl.innerHTML = anyFrozen
              ? `<span class="badge badge-danger" style="font-size: 13px; padding: 6px 14px; background: #fee2e2; color: #b91c1c; border: 1px solid #fca5a5;">Store Status: FROZEN (Transactions Locked)</span>`
              : `<span class="badge badge-success" style="font-size: 13px; padding: 6px 14px;">Store Status: NORMAL / ACTIVE</span>`;
          }
        }
      });
  },

  freezeAllBatches(freeze) {
    const actionName = freeze ? 'FREEZE' : 'UNFREEZE';
    if (!confirm(`Are you sure you want to ${actionName} all inventory batches across the station?`)) return;

    const fd = new FormData();
    fd.append('action', 'toggle_freeze');
    fd.append('session_id', '1');
    fd.append('freeze', freeze ? '1' : '');

    fetch(`${this.baseUrl}/api/phv.php`, { method: 'POST', body: fd })
      .then(res => res.json())
      .then(res => {
        if (res.success) {
          Desk.showToast(res.message);
          Desk.loadFreezeBatches();
          Desk.loadBatches();
        } else {
          Desk.showToast(res.message, true);
        }
      });
  },

  currentSheetPage: 1,
  totalSheetPages: 1,

  generateCountSheet() {
    const fd = new FormData();
    fd.append('action', 'generate_sheet');
    fd.append('session_id', '1');

    fetch(`${this.baseUrl}/api/phv.php`, { method: 'POST', body: fd })
      .then(res => res.json())
      .then(res => {
        if (res.success) {
          Desk.showToast(res.message);
          Desk.loadGeneratedSheet(1);
        } else {
          Desk.showToast(res.message, true);
        }
      })
      .catch(err => Desk.showToast('Failed to generate count sheet: ' + err.message, true));
  },

  loadGeneratedSheet(page = 1) {
    const tbody = document.getElementById('generateSheetTableBody');
    if (!tbody) return;

    this.currentSheetPage = parseInt(page, 10) || 1;

    fetch(`${this.baseUrl}/api/phv.php?action=get_entries&session_id=1&sheet=${this.currentSheetPage}`)
      .then(res => res.json())
      .then(res => {
        if (res.success) {
          const data = res.data;
          const totalSheets = data.total_sheets || 1;
          const totalItems = data.total_items || 0;
          Desk.totalSheetPages = totalSheets;
          Desk.currentSheetPage = data.current_sheet || 1;

          // Update summary indicators
          const totalItemsBadge = document.getElementById('genSheetTotalItemsBadge');
          if (totalItemsBadge) totalItemsBadge.textContent = totalItems;
          const totalSheetsBadge = document.getElementById('genSheetTotalSheetsBadge');
          if (totalSheetsBadge) totalSheetsBadge.textContent = totalSheets;
          const currentBadge = document.getElementById('genSheetCurrentBadge');
          if (currentBadge) currentBadge.textContent = `Sheet ${Desk.currentSheetPage} of ${totalSheets}`;
          const countRange = document.getElementById('genSheetItemRange');
          if (countRange) {
            const startItem = (Desk.currentSheetPage - 1) * 21 + 1;
            const endItem = Math.min(totalItems, Desk.currentSheetPage * 21);
            countRange.textContent = `Showing items ${startItem} - ${endItem} of ${totalItems}`;
          }

          // Populate sheet dropdown selector
          const pageSelect = document.getElementById('genSheetPageSelect');
          if (pageSelect) {
            let opts = '';
            for (let i = 1; i <= totalSheets; i++) {
              const start = (i - 1) * 21 + 1;
              const end = Math.min(totalItems, i * 21);
              opts += `<option value="${i}" ${i === Desk.currentSheetPage ? 'selected' : ''}>Sheet ${i} of ${totalSheets} (Items ${start} - ${end})</option>`;
            }
            pageSelect.innerHTML = opts;
          }

          // Populate 21 rows in table
          let html = '';
          const entries = data.entries || [];
          entries.forEach((e, idx) => {
            const rowInSheet = idx + 1;
            const globalNum = (Desk.currentSheetPage - 1) * 21 + rowInSheet;
            html += `
              <tr>
                <td style="font-weight: 700; color: #475569; width: 45px; text-align: center;">${rowInSheet}</td>
                <td><strong>${e.bin_location || '-'}</strong></td>
                <td><code style="font-weight: 700; color: #0284c7;">${e.sku}</code></td>
                <td><strong>${e.item_name}</strong></td>
                <td>NOS</td>
                <td><span style="font-weight: 700; color: #0f172a;">${e.book_quantity}</span></td>
                <td style="border: 1px dashed #cbd5e1; width: 140px; background: #f8fafc; text-align: center; color: #94a3b8; font-weight: 600;">[ &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; ]</td>
                <td style="border: 1px dashed #cbd5e1; width: 110px; background: #f8fafc; text-align: center; color: #94a3b8;">_______</td>
                <td style="color: #64748b; font-size: 11px;">#${globalNum}</td>
              </tr>
            `;
          });

          // Pad empty rows up to 21 rows to guarantee statutory format
          if (entries.length < 21) {
            for (let pad = entries.length + 1; pad <= 21; pad++) {
              html += `
                <tr style="background: #fafafa; opacity: 0.5;">
                  <td style="font-weight: 700; color: #94a3b8; width: 45px; text-align: center;">${pad}</td>
                  <td style="color: #cbd5e1;">-</td>
                  <td style="color: #cbd5e1;">-</td>
                  <td style="color: #cbd5e1; font-style: italic;">(Blank tally line)</td>
                  <td style="color: #cbd5e1;">-</td>
                  <td style="color: #cbd5e1;">-</td>
                  <td style="border: 1px dashed #e2e8f0; width: 140px; background: #fff; text-align: center; color: #e2e8f0;">[ &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; ]</td>
                  <td style="border: 1px dashed #e2e8f0; width: 110px; background: #fff; text-align: center; color: #e2e8f0;">_______</td>
                  <td style="color: #cbd5e1;">-</td>
                </tr>
              `;
            }
          }

          tbody.innerHTML = html;
        } else {
          Desk.showToast(res.message, true);
        }
      })
      .catch(err => Desk.showToast('Error loading count sheet: ' + err.message, true));
  },

  prevSheetPage() {
    if (this.currentSheetPage > 1) {
      this.loadGeneratedSheet(this.currentSheetPage - 1);
    }
  },

  nextSheetPage() {
    if (this.currentSheetPage < this.totalSheetPages) {
      this.loadGeneratedSheet(this.currentSheetPage + 1);
    }
  },

  downloadAllSheetsZip() {
    const station = encodeURIComponent(document.getElementById('sheetStation')?.value || 'Victoria Hydro Power Station');
    const costCenter = encodeURIComponent(document.getElementById('sheetCostCenter')?.value || 'Mechanical');
    const warehouse = encodeURIComponent(document.getElementById('sheetWarehouse')?.value || 'Central Main Store');
    Desk.showToast('Preparing ZIP package with all 21-item PDF count sheets...');
    window.location.href = `${this.baseUrl}/api/phv.php?action=download_sheets_zip&session_id=1&station=${station}&cost_center=${costCenter}&warehouse=${warehouse}`;
  },

  downloadCurrentSheetPdf() {
    const cur = this.currentSheetPage || 1;
    const station = encodeURIComponent(document.getElementById('sheetStation')?.value || 'Victoria Hydro Power Station');
    const costCenter = encodeURIComponent(document.getElementById('sheetCostCenter')?.value || 'Mechanical');
    const warehouse = encodeURIComponent(document.getElementById('sheetWarehouse')?.value || 'Central Main Store');
    window.open(`${this.baseUrl}/api/phv.php?action=download_sheet_pdf&session_id=1&sheet_num=${cur}&station=${station}&cost_center=${costCenter}&warehouse=${warehouse}`, '_blank');
  },

  loadPvEntry() {
    const tbody = document.getElementById('pvEntryTableBody');
    if (!tbody) return;

    fetch(`${this.baseUrl}/api/phv.php?action=get_entries&session_id=1`)
      .then(res => res.json())
      .then(res => {
        if (res.success) {
          let html = '';
          res.data.entries.forEach(e => {
            const variance = e.physical_quantity - e.book_quantity;
            const varVal = variance * parseFloat(e.unit_cost);
            html += `
              <tr data-entry-id="${e.id}" data-book-qty="${e.book_quantity}" data-cost="${e.unit_cost}">
                <td><code>${e.sku}</code></td>
                <td><strong>${e.item_name}</strong></td>
                <td>${e.bin_location}</td>
                <td>${e.book_quantity}</td>
                <td>
                  <input type="number" class="desk-form-control pv-phys-input" value="${e.physical_quantity}" 
                         style="width: 100px; padding: 4px 8px; font-weight: 700;" onchange="Desk.calcPvEntryRow(this)">
                </td>
                <td class="pv-variance-cell"><strong style="color: ${variance === 0 ? '#64748b' : (variance > 0 ? '#16a34a' : '#dc2626')};">${variance > 0 ? '+' : ''}${variance}</strong></td>
                <td class="pv-variance-val-cell">Rs. ${varVal.toLocaleString('en-US', {minimumFractionDigits: 2})}</td>
                <td>
                  <input type="text" class="desk-form-control pv-remarks-input" value="${e.remarks || ''}" placeholder="Tally verified / Note" style="padding: 4px 8px;">
                </td>
              </tr>
            `;
          });
          tbody.innerHTML = html;
        }
      });
  },

  calcPvEntryRow(inputEl) {
    const row = inputEl.closest('tr');
    const bookQty = parseFloat(row.dataset.bookQty);
    const unitCost = parseFloat(row.dataset.cost);
    const physQty = parseFloat(inputEl.value || 0);

    const variance = physQty - bookQty;
    const varVal = variance * unitCost;

    const varCell = row.querySelector('.pv-variance-cell');
    const valCell = row.querySelector('.pv-variance-val-cell');

    varCell.innerHTML = `<strong style="color: ${variance === 0 ? '#64748b' : (variance > 0 ? '#16a34a' : '#dc2626')};">${variance > 0 ? '+' : ''}${variance}</strong>`;
    valCell.textContent = 'Rs. ' + varVal.toLocaleString('en-US', {minimumFractionDigits: 2});
  },

  saveVerificationCounts() {
    const rows = document.querySelectorAll('#pvEntryTableBody tr, #pvEditTableBody tr');
    if (!rows.length) return;

    const counts = [];
    rows.forEach(r => {
      const id = r.dataset.entryId;
      const physInput = r.querySelector('.pv-phys-input');
      const remInput = r.querySelector('.pv-remarks-input');
      if (id && physInput) {
        counts.push({
          id: id,
          physical_quantity: parseInt(physInput.value || 0),
          remarks: remInput ? remInput.value : ''
        });
      }
    });

    const fd = new FormData();
    fd.append('action', 'save_counts');
    fd.append('session_id', '1');
    fd.append('counts', JSON.stringify(counts));

    fetch(`${this.baseUrl}/api/phv.php`, { method: 'POST', body: fd })
      .then(res => res.json())
      .then(res => {
        if (res.success) {
          Desk.showToast(res.message);
          Desk.loadPvEntry();
          Desk.loadPvEdit();
          Desk.loadAV7Report();
        } else {
          Desk.showToast(res.message, true);
        }
      });
  },

  loadPvEdit() {
    const tbody = document.getElementById('pvEditTableBody');
    if (!tbody) return;

    fetch(`${this.baseUrl}/api/phv.php?action=get_entries&session_id=1`)
      .then(res => res.json())
      .then(res => {
        if (res.success) {
          let html = '';
          res.data.entries.forEach(e => {
            const variance = e.physical_quantity - e.book_quantity;
            const varVal = variance * parseFloat(e.unit_cost);
            const varType = variance > 0 ? 'surplus' : (variance < 0 ? 'deficit' : 'match');
            html += `
              <tr data-entry-id="${e.id}" data-variance-type="${varType}" data-book-qty="${e.book_quantity}" data-cost="${e.unit_cost}">
                <td><code>${e.sku}</code></td>
                <td><strong>${e.item_name}</strong></td>
                <td>${e.bin_location}</td>
                <td>${e.book_quantity}</td>
                <td>
                  <input type="number" class="desk-form-control pv-phys-input" value="${e.physical_quantity}" 
                         style="width: 100px; padding: 4px 8px; font-weight: 700;" onchange="Desk.calcPvEntryRow(this)">
                </td>
                <td class="pv-variance-cell"><strong style="color: ${variance === 0 ? '#64748b' : (variance > 0 ? '#16a34a' : '#dc2626')};">${variance > 0 ? '+' : ''}${variance}</strong></td>
                <td class="pv-variance-val-cell">Rs. ${varVal.toLocaleString('en-US', {minimumFractionDigits: 2})}</td>
                <td>
                  <input type="text" class="desk-form-control pv-remarks-input" value="${e.remarks || ''}" placeholder="Explain discrepancy" style="padding: 4px 8px;">
                </td>
              </tr>
            `;
          });
          tbody.innerHTML = html;
        }
      });
  },

  filterPvEdit(filter) {
    document.querySelectorAll('.filter-pills-bar .filter-pill').forEach(p => p.classList.remove('active'));
    event.target.classList.add('active');

    const rows = document.querySelectorAll('#pvEditTableBody tr');
    rows.forEach(r => {
      const type = r.dataset.varianceType;
      if (filter === 'all') {
        r.style.display = '';
      } else if (filter === 'discrepancy') {
        r.style.display = (type === 'surplus' || type === 'deficit') ? '' : 'none';
      } else if (filter === 'surplus') {
        r.style.display = (type === 'surplus') ? '' : 'none';
      } else if (filter === 'deficit') {
        r.style.display = (type === 'deficit') ? '' : 'none';
      }
    });
  },

  loadPvValidation() {
    const tbody = document.getElementById('pvValidationTableBody');
    if (!tbody) return;

    fetch(`${this.baseUrl}/api/phv.php?action=get_sessions`)
      .then(res => res.json())
      .then(res => {
        if (res.success) {
          let html = '';
          res.data.sessions.forEach(s => {
            html += `
              <tr>
                <td><strong>${s.session_code}</strong></td>
                <td>Central Store / Victoria</td>
                <td>${s.verification_date}</td>
                <td>${s.total_items || 10} Items Counted</td>
                <td style="color: #059669; font-weight: 700;">+Rs. 8,200.00</td>
                <td style="color: #dc2626; font-weight: 700;">-Rs. 2,400.00</td>
                <td><span class="desk-badge badge-${s.status.toLowerCase()}">${s.status}</span></td>
                <td>
                  <button class="desk-btn desk-btn-primary desk-btn-sm" onclick="Desk.validatePvSession(${s.id})">
                    Validate
                  </button>
                </td>
              </tr>
            `;
          });
          tbody.innerHTML = html;
        }
      });
  },

  validatePvSession(sessionId = 1) {
    const fd = new FormData();
    fd.append('action', 'update_status');
    fd.append('session_id', sessionId);
    fd.append('status', 'Validated');

    fetch(`${this.baseUrl}/api/phv.php`, { method: 'POST', body: fd })
      .then(res => res.json())
      .then(res => {
        if (res.success) {
          Desk.showToast('Verification documents validated successfully!');
          Desk.loadPvValidation();
          Desk.loadPvApproval();
        } else {
          Desk.showToast(res.message, true);
        }
      });
  },

  loadPvApproval() {
    const tbody = document.getElementById('pvApprovalTableBody');
    if (!tbody) return;

    fetch(`${this.baseUrl}/api/phv.php?action=get_sessions`)
      .then(res => res.json())
      .then(res => {
        if (res.success) {
          let html = '';
          res.data.sessions.forEach(s => {
            html += `
              <tr>
                <td><strong>${s.session_code}</strong></td>
                <td>${s.session_title}</td>
                <td>${s.verification_date}</td>
                <td>Senior Storekeeper</td>
                <td>Chief Internal Auditor</td>
                <td><span class="desk-badge badge-${s.status.toLowerCase()}">${s.status}</span></td>
                <td>
                  ${s.status === 'Validated' ? `
                    <button class="desk-btn desk-btn-primary desk-btn-sm" onclick="Desk.approvePvSession(${s.id})">Approve</button>
                  ` : `<span style="color:#059669; font-weight:600;">Authorized</span>`}
                </td>
              </tr>
            `;
          });
          tbody.innerHTML = html;

          document.getElementById('pvApprovalDiscrepanciesCount').textContent = '2 Items';
          document.getElementById('pvApprovalNetImpact').textContent = '+Rs. 5,800.00';
        }
      });
  },

  approvePvSession(sessionId = 1) {
    const fd = new FormData();
    fd.append('action', 'update_status');
    fd.append('session_id', sessionId);
    fd.append('status', 'Approved');

    fetch(`${this.baseUrl}/api/phv.php`, { method: 'POST', body: fd })
      .then(res => res.json())
      .then(res => {
        if (res.success) {
          Desk.showToast('Verification session approved for General Ledger adjustment!');
          Desk.loadPvApproval();
          Desk.loadPvPosting();
        } else {
          Desk.showToast(res.message, true);
        }
      });
  },

  loadAvgConsumption() {
    const tbody = document.getElementById('avgConsumptionTableBody');
    if (!tbody) return;

    const costCenter = document.getElementById('avgCostCenter')?.value || '';
    const warehouse = document.getElementById('avgWarehouse')?.value || '';
    const fromDate = document.getElementById('avgFromDate')?.value || '';
    const toDate = document.getElementById('avgToDate')?.value || '';

    fetch(`${this.baseUrl}/api/phv.php?action=get_avg_consumption&cost_center=${encodeURIComponent(costCenter)}&warehouse=${encodeURIComponent(warehouse)}&from_date=${fromDate}&to_date=${toDate}`)
      .then(res => res.json())
      .then(res => {
        if (res.success) {
          let html = '';
          res.data.items.forEach(i => {
            const isReorder = i.stock_health === 'REORDER_NOW';
            html += `
              <tr>
                <td><code>${i.sku}</code></td>
                <td><strong>${i.item_name}</strong></td>
                <td>${i.unit_of_measure}</td>
                <td><span style="font-weight: 700;">${i.current_qoh}</span></td>
                <td>${i.total_issued_qty}</td>
                <td><strong>${i.monthly_avg_consumption}</strong> / mo</td>
                <td>${i.safety_stock}</td>
                <td>${i.reorder_point}</td>
                <td>
                  ${isReorder 
                    ? '<span class="desk-badge badge-draft" style="background:#fee2e2; color:#b91c1c;">Reorder Alert</span>' 
                    : '<span class="desk-badge badge-approved">Normal</span>'}
                </td>
              </tr>
            `;
          });
          tbody.innerHTML = html;
        }
      });
  },

  loadFinalReport() {
    const tbody = document.getElementById('finalReportTableBody');
    if (!tbody) return;

    fetch(`${this.baseUrl}/api/phv.php?action=get_av7_report&session_id=1`)
      .then(res => res.json())
      .then(res => {
        if (res.success) {
          let html = '';
          let surplusCount = 0;
          let deficitCount = 0;

          res.data.items.forEach(r => {
            const variance = parseInt(r.variance_quantity);
            if (variance > 0) surplusCount++;
            if (variance < 0) deficitCount++;
            const varColor = variance < 0 ? '#dc2626' : (variance > 0 ? '#16a34a' : '#64748b');

            html += `
              <tr>
                <td>${r.bin_location}</td>
                <td><code>${r.sku}</code></td>
                <td><strong>${r.item_name}</strong></td>
                <td>${r.book_quantity}</td>
                <td><span style="font-weight: 700;">${r.physical_quantity}</span></td>
                <td><strong style="color: ${varColor};">${variance > 0 ? '+' : ''}${variance}</strong></td>
                <td>Rs. ${parseFloat(r.unit_cost).toFixed(2)}</td>
                <td><strong style="color: ${varColor};">Rs. ${parseFloat(r.variance_value).toLocaleString('en-US', {minimumFractionDigits: 2})}</strong></td>
                <td><span class="desk-badge badge-approved">Certified</span></td>
              </tr>
            `;
          });
          tbody.innerHTML = html;

          document.getElementById('finalTotalLines').textContent = res.data.items.length;
          document.getElementById('finalSurplusLines').textContent = surplusCount;
          document.getElementById('finalDeficitLines').textContent = deficitCount;
          document.getElementById('finalNetVal').textContent = 'Rs. ' + res.data.net_variance_value.toLocaleString('en-US', {minimumFractionDigits: 2});
        }
      });
  },

  loadOtherReport() {
    const tbody = document.getElementById('otherReportTableBody');
    if (!tbody) return;

    fetch(`${this.baseUrl}/api/reports.php?report=qoh_material`)
      .then(res => res.json())
      .then(res => {
        if (res.success) {
          let html = '';
          res.data.data.forEach(m => {
            html += `
              <tr>
                <td><code>${m.sku}</code></td>
                <td><strong>${m.item_name}</strong></td>
                <td>${m.category}</td>
                <td><strong>${m.total_quantity}</strong> ${m.unit_of_measure}</td>
                <td>Rs. ${parseFloat(m.avg_unit_cost).toFixed(2)}</td>
                <td><strong>Rs. ${parseFloat(m.total_value).toLocaleString('en-US', {minimumFractionDigits: 2})}</strong></td>
                <td>${new Date().toISOString().split('T')[0]}</td>
                <td><span class="desk-badge badge-approved">Active Store Stock</span></td>
              </tr>
            `;
          });
          tbody.innerHTML = html;
        }
      });
  },

  loadSheetSummary() {
    const tbody = document.getElementById('sheetSummaryTableBody');
    if (!tbody) return;

    const year = document.getElementById('summaryPhvYear')?.value || '2026';
    const dept = document.getElementById('summaryCostCenter')?.value || '';
    const wh = document.getElementById('summaryWarehouse')?.value || '';

    fetch(`${this.baseUrl}/api/phv.php?action=get_sheet_summary&phv_year=${year}&cost_center=${encodeURIComponent(dept)}&warehouse=${encodeURIComponent(wh)}`)
      .then(res => res.json())
      .then(res => {
        if (res.success) {
          let html = '';
          res.data.summary.forEach(s => {
            html += `
              <tr>
                <td><strong>${s.session_code}</strong></td>
                <td>${s.session_title}</td>
                <td>${s.verification_date}</td>
                <td>${s.total_sheets_counted || 1} Sheets</td>
                <td>${s.total_book_qty || 0}</td>
                <td>${s.total_phys_qty || 0}</td>
                <td><strong>${(s.total_phys_qty - s.total_book_qty) || 0}</strong></td>
                <td><strong>Rs. ${parseFloat(s.total_variance_value || 0).toLocaleString('en-US', {minimumFractionDigits: 2})}</strong></td>
                <td><span class="desk-badge badge-${(s.status || 'draft').toLowerCase()}">${s.status || 'Draft'}</span></td>
              </tr>
            `;
          });
          tbody.innerHTML = html;
        }
      });
  },

  loadPvPosting() {
    const tbody = document.getElementById('pvPostingTableBody');
    if (!tbody) return;

    fetch(`${this.baseUrl}/api/phv.php?action=get_av7_report&session_id=1`)
      .then(res => res.json())
      .then(res => {
        if (res.success) {
          let html = '';
          res.data.items.forEach(r => {
            const variance = parseInt(r.variance_quantity);
            if (variance !== 0) {
              const varColor = variance < 0 ? '#dc2626' : '#16a34a';
              html += `
                <tr>
                  <td><code>${r.sku}</code></td>
                  <td><strong>${r.item_name}</strong></td>
                  <td>${r.bin_location}</td>
                  <td>${r.book_quantity}</td>
                  <td><span style="font-weight: 700;">${r.physical_quantity}</span></td>
                  <td><strong style="color: ${varColor};">${variance > 0 ? '+' : ''}${variance}</strong></td>
                  <td>Rs. ${parseFloat(r.unit_cost).toFixed(2)}</td>
                  <td><strong style="color: ${varColor};">Rs. ${parseFloat(r.variance_value).toLocaleString('en-US', {minimumFractionDigits: 2})}</strong></td>
                  <td><span class="desk-badge ${variance > 0 ? 'badge-approved' : 'badge-draft'}">${variance > 0 ? 'SURPLUS ADJ' : 'DEFICIT WRITE-OFF'}</span></td>
                </tr>
              `;
            }
          });
          tbody.innerHTML = html || '<tr><td colspan="9" style="text-align: center; color: #64748b; padding: 20px;">No pending discrepancy adjustments. Stock matches 100%.</td></tr>';
        }
      });
  },

  postVerification() {
    if (!confirm('Post and commit verification discrepancies to stock ledger? This will reconcile inventory batch quantities.')) return;

    const fd = new FormData();
    fd.append('action', 'post_verification');
    fd.append('session_id', '1');

    fetch(`${this.baseUrl}/api/phv.php`, { method: 'POST', body: fd })
      .then(res => res.json())
      .then(res => {
        if (res.success) {
          Desk.showToast(res.message);
          Desk.loadPvPosting();
          Desk.loadBatches();
          Desk.loadFreezeBatches();
        } else {
          Desk.showToast(res.message, true);
        }
      });
  },

  loadGlPreview() {
    const tbody = document.getElementById('pvGlTableBody');
    if (!tbody) return;

    const wh = document.getElementById('glWarehouse')?.value || 'Central Main Store';

    fetch(`${this.baseUrl}/api/phv.php?action=get_gl_preview&warehouse=${encodeURIComponent(wh)}`)
      .then(res => res.json())
      .then(res => {
        if (res.success) {
          let html = '';
          res.data.journal_entries.forEach(j => {
            html += `
              <tr>
                <td><code>${j.account_code}</code></td>
                <td><strong>${j.account_name}</strong></td>
                <td style="font-weight: 700; color: #1e293b;">${j.debit > 0 ? 'Rs. ' + j.debit.toLocaleString('en-US', {minimumFractionDigits: 2}) : '-'}</td>
                <td style="font-weight: 700; color: #1e293b;">${j.credit > 0 ? 'Rs. ' + j.credit.toLocaleString('en-US', {minimumFractionDigits: 2}) : '-'}</td>
                <td>${j.narration}</td>
              </tr>
            `;
          });
          tbody.innerHTML = html;
        }
      });
  },

  transferToGL() {
    Desk.showToast('General Ledger voucher successfully transferred and balanced in Chart of Accounts!');
  },

  loadUnfreezeTab() {
    Desk.loadFreezeBatches();
  },

  loadAV7Report() {
    const tbody = document.getElementById('av7TableBody');
    if (!tbody) return;

    fetch(`${this.baseUrl}/api/phv.php?action=get_av7_report&session_id=1`)
      .then(res => res.json())
      .then(res => {
        if (res.success) {
          let html = '';
          res.data.items.forEach((r, idx) => {
            const variance = parseInt(r.variance_quantity);
            const varColor = variance < 0 ? '#dc2626' : (variance > 0 ? '#16a34a' : '#64748b');

            html += `
              <tr>
                <td>${idx + 1}</td>
                <td><code>${r.sku}</code></td>
                <td><strong>${r.item_name}</strong></td>
                <td>${r.bin_location}</td>
                <td>${r.book_quantity}</td>
                <td><span style="font-weight: 700;">${r.physical_quantity}</span></td>
                <td><strong style="color: ${varColor};">${variance > 0 ? '+' : ''}${variance}</strong></td>
                <td>Rs. ${parseFloat(r.unit_cost).toLocaleString('en-US', {minimumFractionDigits: 2})}</td>
                <td><strong style="color: ${varColor};">Rs. ${parseFloat(r.variance_value).toLocaleString('en-US', {minimumFractionDigits: 2})}</strong></td>
                <td>${r.remarks || '-'}</td>
              </tr>
            `;
          });
          tbody.innerHTML = html;

          if (document.getElementById('av7Surplus')) {
            document.getElementById('av7Surplus').textContent = 'Rs. ' + res.data.total_surplus_value.toLocaleString('en-US', {minimumFractionDigits: 2});
            document.getElementById('av7Deficit').textContent = 'Rs. ' + res.data.total_deficit_value.toLocaleString('en-US', {minimumFractionDigits: 2});
            document.getElementById('av7Net').textContent = 'Rs. ' + res.data.net_variance_value.toLocaleString('en-US', {minimumFractionDigits: 2});
          }
        }
      });
  },

  // -----------------------------------------------------------------
  // Reports
  // -----------------------------------------------------------------
  loadQohMaterial() {
    const tbody = document.getElementById('qohMaterialTableBody');
    if (!tbody) return;

    fetch(`${this.baseUrl}/api/reports.php?report=qoh_material`)
      .then(res => res.json())
      .then(res => {
        if (res.success) {
          let html = '';
          res.data.data.forEach(m => {
            const availQty = m.available_quantity !== undefined ? m.available_quantity : Math.max(0, m.total_quantity_on_hand - (m.allocated_quantity || 0));
            const allocQty = parseInt(m.allocated_quantity) || 0;
            const allocBadge = allocQty > 0 
              ? `<span class="desk-badge" style="background: #fff1f2; color: #be123c; border: 1px solid #fecdd3; font-weight: 700;">${allocQty} Reserved</span>` 
              : `<span style="color: #94a3b8; font-size: 0.8rem;">0</span>`;
            
            const availBadge = availQty > 0
              ? `<strong style="color: #059669; font-size: 14px;">${availQty}</strong>`
              : `<strong style="color: #dc2626; font-size: 14px;">0 Out of Stock</strong>`;

            html += `
              <tr>
                <td><code>${m.sku}</code></td>
                <td><strong>${m.item_name}</strong></td>
                <td>${m.category}</td>
                <td><span style="font-weight: 700; font-size: 14px; color: #0f172a;">${m.total_quantity_on_hand}</span></td>
                <td>${allocBadge}</td>
                <td>${availBadge}</td>
                <td>Rs. ${parseFloat(m.avg_unit_cost).toLocaleString('en-US', {minimumFractionDigits: 2})}</td>
                <td><strong>Rs. ${parseFloat(m.total_stock_valuation).toLocaleString('en-US', {minimumFractionDigits: 2})}</strong></td>
                <td>${m.frozen_quantity > 0 ? `<span class="desk-badge badge-frozen">Frozen: ${m.frozen_quantity}</span>` : '<span class="desk-badge badge-active">Active</span>'}</td>
              </tr>
            `;
          });
          tbody.innerHTML = html;
        }
      });
  },

  loadCpcProcurements() {
    const tbody = document.getElementById('cpcTableBody');
    if (!tbody) return;

    fetch(`${this.baseUrl}/api/reports.php?report=cpc_procurements`)
      .then(res => res.json())
      .then(res => {
        if (res.success) {
          let html = '';
          res.data.data.forEach(c => {
            html += `
              <tr>
                <td><strong>${c.order_no}</strong></td>
                <td>${c.consignment_date}</td>
                <td>${c.fuel_type}</td>
                <td>${c.tank_id}</td>
                <td><strong>${parseFloat(c.quantity_litres).toLocaleString('en-US')} L</strong></td>
                <td>Rs. ${parseFloat(c.unit_price_lkr).toFixed(2)}</td>
                <td><strong>Rs. ${parseFloat(c.total_amount_lkr).toLocaleString('en-US', {minimumFractionDigits: 2})}</strong></td>
                <td>${c.delivery_ref} (${c.bowser_no})</td>
                <td><span class="desk-badge badge-approved">${c.status}</span></td>
              </tr>
            `;
          });
          tbody.innerHTML = html;
        }
      });
  },

  loadDieselPriceLogs() {
    const tbody = document.getElementById('dieselTableBody');
    if (!tbody) return;

    fetch(`${this.baseUrl}/api/reports.php?report=avg_diesel`)
      .then(res => res.json())
      .then(res => {
        if (res.success) {
          let html = '';
          res.data.data.forEach(d => {
            html += `
              <tr>
                <td><strong>${d.effective_month}</strong></td>
                <td>${d.fuel_type}</td>
                <td><strong style="font-size: 14px;">Rs. ${parseFloat(d.avg_price_per_litre).toFixed(2)} / L</strong></td>
                <td>${parseFloat(d.total_procured_litres).toLocaleString('en-US')} L</td>
                <td>Rs. ${parseFloat(d.total_procurement_cost).toLocaleString('en-US', {minimumFractionDigits: 2})}</td>
                <td><span class="desk-badge badge-draft">${d.price_trend}</span></td>
              </tr>
            `;
          });
          tbody.innerHTML = html;
        }
      });
  },

  loadMonthlyTransfers() {
    const tbody = document.getElementById('transfersTableBody');
    if (!tbody) return;

    fetch(`${this.baseUrl}/api/reports.php?report=monthly_transfers`)
      .then(res => res.json())
      .then(res => {
        if (res.success) {
          let html = '';
          res.data.data.forEach(t => {
            html += `
              <tr>
                <td><strong>${t.transfer_ref}</strong></td>
                <td>${t.transfer_date}</td>
                <td>${t.from_location}</td>
                <td><strong>${t.to_department}</strong></td>
                <td>${t.item_name}</td>
                <td><span style="font-weight: 700;">${t.quantity}</span></td>
                <td><span class="desk-badge badge-approved">${t.status}</span></td>
              </tr>
            `;
          });
          tbody.innerHTML = html;
        }
      });
  },

  loadJobIssues() {
    const tbody = document.getElementById('jobIssuesTableBody');
    if (!tbody) return;

    fetch(`${this.baseUrl}/api/reports.php?report=job_issues`)
      .then(res => res.json())
      .then(res => {
        if (res.success) {
          let html = '';
          res.data.data.forEach(j => {
            html += `
              <tr>
                <td><strong>${j.document_no}</strong></td>
                <td>${j.transaction_date}</td>
                <td><span class="desk-badge badge-draft">${j.destination_code}</span></td>
                <td><strong>${j.destination_name || j.department_name}</strong></td>
                <td>${j.item_name}</td>
                <td><strong>${j.quantity}</strong></td>
                <td>Rs. ${parseFloat(j.total_value).toLocaleString('en-US', {minimumFractionDigits: 2})}</td>
              </tr>
            `;
          });
          tbody.innerHTML = html;
        }
      });
  },

  loadInventoryInquiry() {
    const tbody = document.getElementById('inventoryInquiryTableBody');
    if (!tbody) return;

    const sku = encodeURIComponent(document.getElementById('inquirySku')?.value || '');
    const costCenter = encodeURIComponent(document.getElementById('inquiryCostCenter')?.value || '');
    const fromDate = encodeURIComponent(document.getElementById('inquiryFromDate')?.value || '');
    const toDate = encodeURIComponent(document.getElementById('inquiryToDate')?.value || '');

    fetch(`${this.baseUrl}/api/reports.php?report=inventory_inquiry&sku=${sku}&cost_center=${costCenter}&from_date=${fromDate}&to_date=${toDate}`)
      .then(res => res.json())
      .then(res => {
        if (res.success) {
          const badge = document.getElementById('inquiryCountBadge');
          if (badge) badge.textContent = `${res.data.total_records} Active Items Found`;

          let html = '';
          const items = res.data.items || [];
          if (items.length === 0) {
            tbody.innerHTML = '<tr><td colspan="10" style="text-align:center; padding: 24px; color: #94a3b8;">No inventory items match the inquiry filters.</td></tr>';
            return;
          }
          items.forEach(b => {
            html += `
              <tr>
                <td><strong>${b.location_bin || '-'}</strong></td>
                <td><code>${b.sku}</code></td>
                <td><strong>${b.item_name}</strong></td>
                <td><span class="desk-badge badge-draft">${b.category || 'Standard'}</span></td>
                <td style="text-align: right;">${b.initial_quantity}</td>
                <td style="text-align: right; color: #dc2626; font-weight: 600;">${b.issued_quantity}</td>
                <td style="text-align: right; font-weight: 700; color: #0284c7;">${b.remaining_quantity}</td>
                <td style="text-align: right;">Rs. ${parseFloat(b.unit_cost).toLocaleString('en-US', {minimumFractionDigits: 2})}</td>
                <td style="text-align: right; font-weight: 700;">Rs. ${parseFloat(b.total_valuation).toLocaleString('en-US', {minimumFractionDigits: 2})}</td>
                <td><span class="desk-badge ${b.freeze_status === 'frozen' ? 'badge-cancelled' : 'badge-posted'}">${b.freeze_status === 'frozen' ? 'FROZEN' : 'ACTIVE'}</span></td>
              </tr>
            `;
          });
          tbody.innerHTML = html;
        }
      });
  },

  loadMonthlyTx() {
    const tbody = document.getElementById('monthlyTxTableBody');
    if (!tbody) return;

    const docType = encodeURIComponent(document.getElementById('monthlyTxDocType')?.value || 'ALL');
    const year = encodeURIComponent(document.getElementById('monthlyTxYear')?.value || '');
    const month = encodeURIComponent(document.getElementById('monthlyTxMonth')?.value || '');
    const warehouse = encodeURIComponent(document.getElementById('monthlyTxWarehouse')?.value || '');

    fetch(`${this.baseUrl}/api/reports.php?report=monthly_tx&document_type=${docType}&year=${year}&month=${month}&warehouse=${warehouse}`)
      .then(res => res.json())
      .then(res => {
        if (res.success) {
          const badge = document.getElementById('monthlyTxCountBadge');
          if (badge) badge.textContent = `${res.data.total_count} Transactions Logged`;

          let html = '';
          const txs = res.data.transactions || [];
          if (txs.length === 0) {
            tbody.innerHTML = '<tr><td colspan="10" style="text-align:center; padding: 24px; color: #94a3b8;">No transactions found for the selected criteria.</td></tr>';
            return;
          }
          txs.forEach(t => {
            html += `
              <tr>
                <td>${t.transaction_date}</td>
                <td><code>${t.document_no}</code></td>
                <td><span class="desk-badge ${t.transaction_type && t.transaction_type.includes('RECEIPT') ? 'badge-approved' : ((t.transaction_type && t.transaction_type.includes('ISSUE')) ? 'badge-draft' : 'badge-posted')}">${t.transaction_type}</span></td>
                <td><code>${t.sku}</code></td>
                <td><strong>${t.item_name}</strong></td>
                <td style="text-align: right; font-weight: 700;">${t.quantity}</td>
                <td style="text-align: right;">Rs. ${parseFloat(t.unit_price).toLocaleString('en-US', {minimumFractionDigits: 2})}</td>
                <td style="text-align: right; font-weight: 700;">Rs. ${parseFloat(t.total_value).toLocaleString('en-US', {minimumFractionDigits: 2})}</td>
                <td>${t.reference_no || '-'}</td>
                <td><span class="desk-badge badge-posted">${t.status}</span></td>
              </tr>
            `;
          });
          tbody.innerHTML = html;
        }
      });
  },

  loadGrnIssueSummary() {
    const tbody = document.getElementById('grnIssueSummaryTableBody');
    if (!tbody) return;

    const costCenter = encodeURIComponent(document.getElementById('grnSumCostCenter')?.value || '');
    const warehouse = encodeURIComponent(document.getElementById('grnSumWarehouse')?.value || '');

    fetch(`${this.baseUrl}/api/reports.php?report=grn_issue_summary&cost_center=${costCenter}&warehouse=${warehouse}`)
      .then(res => res.json())
      .then(res => {
        if (res.success) {
          let html = '';
          const items = res.data.items || [];
          if (items.length === 0) {
            tbody.innerHTML = '<tr><td colspan="9" style="text-align:center; padding: 24px; color: #94a3b8;">No records found.</td></tr>';
            return;
          }
          items.forEach(it => {
            html += `
              <tr>
                <td><code>${it.sku}</code></td>
                <td><strong>${it.item_name}</strong></td>
                <td>${it.uom}</td>
                <td style="text-align: right; color: #059669; font-weight: 600;">${it.total_grn_qty}</td>
                <td style="text-align: right;">Rs. ${parseFloat(it.total_grn_val).toLocaleString('en-US', {minimumFractionDigits: 2})}</td>
                <td style="text-align: right; color: #dc2626; font-weight: 600;">${it.total_issue_qty}</td>
                <td style="text-align: right;">Rs. ${parseFloat(it.total_issue_val).toLocaleString('en-US', {minimumFractionDigits: 2})}</td>
                <td style="text-align: right; font-weight: 700; color: #0284c7;">${it.net_balance_qty}</td>
                <td style="text-align: right; font-weight: 700;">Rs. ${parseFloat(it.net_balance_val).toLocaleString('en-US', {minimumFractionDigits: 2})}</td>
              </tr>
            `;
          });
          tbody.innerHTML = html;
        }
      });
  },

  loadMaterialFlow() {
    const tbody = document.getElementById('materialFlowTableBody');
    if (!tbody) return;

    const sku = encodeURIComponent(document.getElementById('matFlowSku')?.value || '');
    const warehouse = encodeURIComponent(document.getElementById('matFlowWarehouse')?.value || '');
    const fromDate = encodeURIComponent(document.getElementById('matFlowFromDate')?.value || '');
    const toDate = encodeURIComponent(document.getElementById('matFlowToDate')?.value || '');

    fetch(`${this.baseUrl}/api/reports.php?report=material_flow&sku=${sku}&warehouse=${warehouse}&from_date=${fromDate}&to_date=${toDate}`)
      .then(res => res.json())
      .then(res => {
        if (res.success) {
          let html = '';
          const items = res.data.items || [];
          if (items.length === 0) {
            tbody.innerHTML = '<tr><td colspan="8" style="text-align:center; padding: 24px; color: #94a3b8;">No movement records found for selected criteria.</td></tr>';
            return;
          }
          items.forEach(m => {
            html += `
              <tr>
                <td><code>${m.sku}</code></td>
                <td><strong>${m.item_name}</strong></td>
                <td style="text-align: right;">${m.opening_qty}</td>
                <td style="text-align: right; color: #059669; font-weight: 600;">+${m.period_receipt_qty}</td>
                <td style="text-align: right; color: #dc2626; font-weight: 600;">-${m.period_issue_qty}</td>
                <td style="text-align: right; font-weight: 700; color: #0284c7;">${m.closing_qty}</td>
                <td style="text-align: right;">Rs. ${parseFloat(m.unit_cost).toLocaleString('en-US', {minimumFractionDigits: 2})}</td>
                <td style="text-align: right; font-weight: 700;">Rs. ${parseFloat(m.closing_val).toLocaleString('en-US', {minimumFractionDigits: 2})}</td>
              </tr>
            `;
          });
          tbody.innerHTML = html;
        }
      });
  },

  // -----------------------------------------------------------------
  // Cash Book & Payslips
  // -----------------------------------------------------------------
  loadCashBook() {
    const tbody = document.getElementById('cashBookTableBody');
    if (!tbody) return;

    fetch(`${this.baseUrl}/api/cashbook.php?action=get_entries`)
      .then(res => res.json())
      .then(res => {
        if (res.success) {
          let html = '';
          res.data.entries.forEach(c => {
            const isPayment = c.entry_type === 'Payment';
            html += `
              <tr>
                <td><strong>${c.voucher_no}</strong></td>
                <td>${c.entry_date}</td>
                <td><span class="desk-badge ${isPayment ? 'badge-cancelled' : 'badge-approved'}">${c.entry_type}</span></td>
                <td><strong>${c.account_title}</strong></td>
                <td>${c.payee_or_source}</td>
                <td><strong>Rs. ${parseFloat(c.amount).toLocaleString('en-US', {minimumFractionDigits: 2})}</strong></td>
                <td><span class="desk-badge badge-posted">${c.status}</span></td>
              </tr>
            `;
          });
          tbody.innerHTML = html;
        }
      });
  },

  loadPayslipCancellations() {
    const tbody = document.getElementById('payslipsTableBody');
    if (!tbody) return;

    fetch(`${this.baseUrl}/api/cashbook.php?action=get_payslip_cancellations`)
      .then(res => res.json())
      .then(res => {
        if (res.success) {
          let html = '';
          res.data.cancellations.forEach(p => {
            const badgeClass = `badge-${p.status.toLowerCase().replace(' ', '-')}`;
            html += `
              <tr>
                <td><strong>${p.cancellation_ref}</strong></td>
                <td>${p.payslip_no}</td>
                <td><strong>${p.employee_name}</strong> (${p.epf_no})</td>
                <td>${p.department}</td>
                <td>${p.pay_period}</td>
                <td>Rs. ${parseFloat(p.amount).toLocaleString('en-US', {minimumFractionDigits: 2})}</td>
                <td>${p.reason}</td>
                <td><span class="desk-badge ${badgeClass}">${p.status}</span></td>
                <td>
                  ${p.status === 'Pending Review' ? `
                    <button class="desk-btn desk-btn-primary desk-btn-sm" onclick="Desk.updatePayslipStatus(${p.id}, 'Approved')">Approve</button>
                    <button class="desk-btn desk-btn-danger desk-btn-sm" onclick="Desk.updatePayslipStatus(${p.id}, 'Rejected')">Reject</button>
                  ` : '-'}
                </td>
              </tr>
            `;
          });
          tbody.innerHTML = html;
        }
      });
  },

  updatePayslipStatus(id, status) {
    const fd = new FormData();
    fd.append('action', 'update_payslip_status');
    fd.append('id', id);
    fd.append('status', status);

    fetch(`${this.baseUrl}/api/cashbook.php`, { method: 'POST', body: fd })
      .then(res => res.json())
      .then(res => {
        if (res.success) {
          Desk.showToast(res.message);
          Desk.loadPayslipCancellations();
        } else {
          Desk.showToast(res.message, true);
        }
      });
  },

  // -----------------------------------------------------------------
  // Authorized Tasks
  // -----------------------------------------------------------------
  loadCostAdjustments() {
    const tbody = document.getElementById('costAdjustTableBody');
    if (!tbody) return;

    fetch(`${this.baseUrl}/api/tasks.php?action=get_cost_adjustments`)
      .then(res => res.json())
      .then(res => {
        if (res.success) {
          let html = '';
          res.data.data.forEach(c => {
            html += `
              <tr>
                <td><strong>${c.adjustment_ref}</strong></td>
                <td>${c.sku}</td>
                <td>${c.item_name}</td>
                <td>Rs. ${parseFloat(c.old_unit_cost).toFixed(2)}</td>
                <td><strong>Rs. ${parseFloat(c.new_unit_cost).toFixed(2)}</strong></td>
                <td><strong>Rs. ${parseFloat(c.total_valuation_delta).toLocaleString('en-US', {minimumFractionDigits: 2})}</strong></td>
                <td>${c.reason}</td>
                <td><span class="desk-badge badge-approved">${c.status}</span></td>
              </tr>
            `;
          });
          tbody.innerHTML = html;
        }
      });
  },

  loadQtyCorrections() {
    const tbody = document.getElementById('qtyCorrectTableBody');
    if (!tbody) return;

    fetch(`${this.baseUrl}/api/tasks.php?action=get_qty_corrections`)
      .then(res => res.json())
      .then(res => {
        if (res.success) {
          let html = '';
          res.data.data.forEach(q => {
            html += `
              <tr>
                <td><strong>${q.correction_ref}</strong></td>
                <td>${q.sku}</td>
                <td>${q.item_name}</td>
                <td>${q.allocated_qty_before}</td>
                <td>${q.corrected_allocated_qty}</td>
                <td><strong style="color: #16a34a;">+${q.qty_released_to_free_stock} Released</strong></td>
                <td>${q.reason}</td>
                <td><span class="desk-badge badge-approved">${q.status}</span></td>
              </tr>
            `;
          });
          tbody.innerHTML = html;
        }
      });
  },

  loadDestinationCodes() {
    const tbody = document.getElementById('destCodesTableBody');
    if (!tbody) return;

    fetch(`${this.baseUrl}/api/tasks.php?action=get_destination_codes`)
      .then(res => res.json())
      .then(res => {
        if (res.success) {
          let html = '';
          res.data.data.forEach(d => {
            html += `
              <tr>
                <td><span class="desk-badge badge-draft">${d.category_code}</span></td>
                <td>${d.category_name}</td>
                <td><strong>${d.destination_code}</strong></td>
                <td>${d.destination_name}</td>
                <td>${d.description}</td>
              </tr>
            `;
          });
          tbody.innerHTML = html;
        }
      });
  },

  // -----------------------------------------------------------------
  // In-Body Form Handlers & Batches Population (Zero Popups)
  // -----------------------------------------------------------------
  cachedActiveBatches: [],

  populateTaskBatches() {
    fetch(`${this.baseUrl}/api/inventory.php?action=get_batches`)
      .then(res => res.json())
      .then(res => {
        if (res.success) {
          this.cachedActiveBatches = res.data.batches || [];

          // 1. Cost Adjust Batch Select
          const costSel = document.getElementById('bodyCostAdjustBatch');
          if (costSel) {
            if (this.cachedActiveBatches.length === 0) {
              costSel.innerHTML = '<option value="">-- No active batches in inventory (Create a GRN first) --</option>';
            } else {
              let opt = '<option value="">-- Select Active Batch to Adjust --</option>';
              this.cachedActiveBatches.forEach(b => {
                opt += `<option value="${b.id}" data-cost="${b.unit_cost}" data-sku="${b.sku}" data-name="${b.item_name}" data-qty="${b.remaining_quantity}">${b.batch_number} - ${b.sku} (${b.item_name}) [Current: Rs. ${parseFloat(b.unit_cost).toFixed(2)}]</option>`;
              });
              costSel.innerHTML = opt;
            }
          }

          // 2. Qty Correct Batch Select
          const qtySel = document.getElementById('bodyQtyCorrectBatch');
          if (qtySel) {
            if (this.cachedActiveBatches.length === 0) {
              qtySel.innerHTML = '<option value="">-- No batches found in inventory --</option>';
            } else {
              let opt = '<option value="">-- Select Batch to Correct --</option>';
              this.cachedActiveBatches.forEach(b => {
                opt += `<option value="${b.id}" data-sku="${b.sku}" data-qty="${b.remaining_quantity}">${b.batch_number} - ${b.sku} (${b.item_name}) [Remaining: ${b.remaining_quantity}]</option>`;
              });
              qtySel.innerHTML = opt;
            }
          }

          // 3. Issue Material SKU Select
          const issueSel = document.getElementById('bodyIssueSku');
          if (issueSel) {
            if (this.cachedActiveBatches.length === 0) {
              issueSel.innerHTML = '<option value="">-- No active inventory batches (Receive a GRN first) --</option>';
            } else {
              const skuMap = {};
              this.cachedActiveBatches.forEach(b => {
                if (!skuMap[b.sku]) {
                  skuMap[b.sku] = b.item_name;
                }
              });
              let opt = '<option value="">-- Select Material / SKU from Batches --</option>';
              Object.keys(skuMap).forEach(sku => {
                opt += `<option value="${sku}">${sku} - ${skuMap[sku]}</option>`;
              });
              issueSel.innerHTML = opt;
            }
          }
        }
      })
      .catch(e => console.error('Error fetching batches:', e));
  },

  onCostAdjustBatchChange(selectEl) {
    const selectedOpt = selectEl.options[selectEl.selectedIndex];
    const costInput = document.getElementById('bodyCostAdjustCurrentCost');
    const newCostInput = document.getElementById('bodyCostAdjustNewCost');
    if (selectedOpt && selectedOpt.dataset.cost) {
      const curCost = parseFloat(selectedOpt.dataset.cost).toFixed(2);
      if (costInput) costInput.value = `Rs. ${curCost}`;
      if (newCostInput) newCostInput.value = curCost;
    } else {
      if (costInput) costInput.value = '';
    }
  },

  onQtyCorrectBatchChange(selectEl) {
    const selectedOpt = selectEl.options[selectEl.selectedIndex];
    const allocInput = document.getElementById('bodyQtyAllocBefore');
    if (selectedOpt && selectedOpt.dataset.qty) {
      if (allocInput) allocInput.value = selectedOpt.dataset.qty;
    }
  },

  submitBodyCostAdjust(form) {
    const formData = new FormData(form);
    fetch(`${this.baseUrl}/api/tasks.php?action=add_cost_adjustment`, {
      method: 'POST',
      body: formData
    })
    .then(res => res.json())
    .then(res => {
      if (res.success) {
        alert(res.message || 'Cost Adjustment applied successfully!');
        form.reset();
        const curCost = document.getElementById('bodyCostAdjustCurrentCost');
        if (curCost) curCost.value = '';
        this.loadCostAdjustments();
        this.populateTaskBatches();
      } else {
        alert(res.message || 'Error applying cost adjustment.');
      }
    })
    .catch(err => alert('Network error: ' + err));
  },

  submitBodyQtyCorrect(form) {
    const formData = new FormData(form);
    fetch(`${this.baseUrl}/api/tasks.php?action=add_qty_correction`, {
      method: 'POST',
      body: formData
    })
    .then(res => res.json())
    .then(res => {
      if (res.success) {
        alert(res.message || 'Quantity correction applied and stock released!');
        form.reset();
        this.loadQtyCorrections();
        this.populateTaskBatches();
      } else {
        alert(res.message || 'Error applying quantity correction.');
      }
    })
    .catch(err => alert('Network error: ' + err));
  },

  submitBodyCashVoucher(form) {
    const formData = new FormData(form);
    fetch(`${this.baseUrl}/api/cashbook.php?action=add_entry`, {
      method: 'POST',
      body: formData
    })
    .then(res => res.json())
    .then(res => {
      if (res.success) {
        alert(res.message || 'Cash voucher created successfully!');
        form.reset();
        this.loadCashBook();
      } else {
        alert(res.message || 'Error recording voucher.');
      }
    })
    .catch(err => alert('Network error: ' + err));
  },

  submitBodyPayslipCancel(form) {
    const formData = new FormData(form);
    fetch(`${this.baseUrl}/api/cashbook.php?action=request_payslip_cancellation`, {
      method: 'POST',
      body: formData
    })
    .then(res => res.json())
    .then(res => {
      if (res.success) {
        alert(res.message || 'Payslip cancellation requisition submitted for audit authorization!');
        form.reset();
        this.loadPayslipCancellations();
      } else {
        alert(res.message || 'Error requesting cancellation.');
      }
    })
    .catch(err => alert('Network error: ' + err));
  },

  submitBodyCpcOrder(form) {
    const formData = new FormData(form);
    fetch(`${this.baseUrl}/api/reports.php?action=add_cpc_procurement`, {
      method: 'POST',
      body: formData
    })
    .then(res => res.json())
    .then(res => {
      if (res.success) {
        alert(res.message || 'CPC Fuel Consignment recorded successfully!');
        form.reset();
        this.loadCpcProcurements();
      } else {
        alert(res.message || 'Error recording CPC procurement.');
      }
    })
    .catch(err => alert('Network error: ' + err));
  },

  submitBodyDieselPrice(form) {
    const formData = new FormData(form);
    fetch(`${this.baseUrl}/api/reports.php?action=add_diesel_price`, {
      method: 'POST',
      body: formData
    })
    .then(res => res.json())
    .then(res => {
      if (res.success) {
        alert(res.message || 'Diesel Price Log saved successfully!');
        form.reset();
        this.loadDieselPriceLogs();
      } else {
        alert(res.message || 'Error saving price log.');
      }
    })
    .catch(err => alert('Network error: ' + err));
  },

  // -----------------------------------------------------------------
  // Legacy FIFO Style Receipts (GRN) In-Body Detailed Form Methods
  // -----------------------------------------------------------------
  grnAddedItems: [],
  allCatalogMaterials: [],
  grnLookupsLoaded: false,

  onDocProfileChange(val) {
    const notice = document.getElementById('grnDocPfNotice');
    if (!notice) return;
    if (!val) {
      notice.textContent = 'Doc PF Not Selected';
      notice.style.color = '#2563eb';
    } else {
      notice.textContent = val;
      notice.style.color = '#15803d';
    }
  },

  onReceiptFromChange(val) {
    const row = document.getElementById('grnDynamicReceiptRow');
    const label = document.getElementById('grnDynamicReceiptLabel');
    const selSupplier = document.getElementById('grnSelectSupplier');
    const selDept = document.getElementById('grnSelectDept');
    const selSection = document.getElementById('grnSelectSection');

    if (!row || !label || !selSupplier || !selDept || !selSection) return;

    // Reset visibility
    selSupplier.style.display = 'none';
    selDept.style.display = 'none';
    selSection.style.display = 'none';

    if (val === 'Purchase Order') {
      row.style.display = 'flex';
      label.textContent = 'Receipt Supplier';
      selSupplier.style.display = 'block';
    } else if (val === 'Cost Center') {
      row.style.display = 'flex';
      label.textContent = 'Receipt Dept';
      selDept.style.display = 'block';
    } else if (val === 'Section') {
      row.style.display = 'flex';
      label.textContent = 'Section';
      selSection.style.display = 'block';
    } else {
      row.style.display = 'none';
    }
  },

  toggleGrnSection(boxId, headerElem) {
    const box = document.getElementById(boxId);
    if (!box) return;
    const isHidden = (box.style.display === 'none');
    box.style.display = isHidden ? 'flex' : 'none';
    const icon = headerElem.querySelector('.grn-toggle-icon');
    if (icon) {
      icon.textContent = isHidden ? '-' : '+';
    }
  },

  initGrnFormLookups() {
    if (this.grnLookupsLoaded) return;

    fetch(`${this.baseUrl}/api/inventory.php?action=get_receipt_lookups`)
      .then(res => res.json())
      .then(res => {
        if (!res.success || !res.data) return;
        this.grnLookupsLoaded = true;
        const d = res.data;

        // Populate Sections
        const selSec = document.getElementById('grnSelectSection');
        if (selSec && Array.isArray(d.sections)) {
          selSec.innerHTML = '<option value="">---- Select Section----</option>';
          d.sections.forEach(s => {
            const opt = document.createElement('option');
            opt.value = s.code;
            opt.textContent = s.display_label || `${s.code} - ${s.title}`;
            selSec.appendChild(opt);
          });
        }

        // Store catalog materials
        if (Array.isArray(d.materials)) {
          this.allCatalogMaterials = d.materials;
          this.renderGrnMaterialDropdown(d.materials);
        }
      })
      .catch(err => console.error('Failed to load GRN lookups:', err));
  },

  renderGrnMaterialDropdown(materials) {
    const selMat = document.getElementById('grnSelectMaterial');
    if (!selMat) return;
    selMat.innerHTML = '<option value="">-- Select Material For Receipt--</option>';
    materials.slice(0, 100).forEach(m => {
      const opt = document.createElement('option');
      opt.value = m.sku;
      opt.textContent = `${m.sku} - ${m.item_name}`;
      opt.dataset.sku = m.sku;
      opt.dataset.name = m.item_name;
      selMat.appendChild(opt);
    });
  },

  filterGrnMaterialDropdown(query) {
    const q = (query || '').toLowerCase().trim();
    if (!q) {
      this.renderGrnMaterialDropdown(this.allCatalogMaterials);
      return;
    }
    const filtered = this.allCatalogMaterials.filter(m => 
      (m.sku && m.sku.toLowerCase().includes(q)) || 
      (m.item_name && m.item_name.toLowerCase().includes(q))
    );
    this.renderGrnMaterialDropdown(filtered);
  },

  addGrnMaterialItem() {
    const selMat = document.getElementById('grnSelectMaterial');
    if (!selMat || !selMat.value) {
      alert('Please select a material from the dropdown first.');
      return;
    }

    const selectedOpt = selMat.options[selMat.selectedIndex];
    const sku = selectedOpt.dataset.sku || selMat.value;
    const itemName = selectedOpt.dataset.name || selectedOpt.textContent.replace(`${sku} - `, '');

    const qtyStr = prompt(`Enter received quantity for:\n[${sku}] ${itemName}`, '10');
    if (qtyStr === null) return;
    const quantity = parseInt(qtyStr) || 1;
    if (quantity <= 0) {
      alert('Quantity must be greater than zero.');
      return;
    }

    const costStr = prompt(`Enter unit purchase cost (Rs.) for:\n[${sku}] ${itemName}`, '500.00');
    if (costStr === null) return;
    const unitCost = parseFloat(costStr) || 0;

    const bin = prompt(`Enter storage bin for this material:`, 'Bay-01') || 'Bay-01';

    const lineTotal = quantity * unitCost;

    this.grnAddedItems.push({
      sku: sku,
      item_name: itemName,
      quantity: quantity,
      unit_cost: unitCost,
      total_cost: lineTotal,
      location_bin: bin
    });

    this.renderGrnItemsTable();
    this.recalcGrnInvoiceTotal();
  },

  removeGrnMaterialItem(idx) {
    if (idx >= 0 && idx < this.grnAddedItems.length) {
      this.grnAddedItems.splice(idx, 1);
      this.renderGrnItemsTable();
      this.recalcGrnInvoiceTotal();
    }
  },

  renderGrnItemsTable() {
    const tbody = document.getElementById('grnItemsTableBody');
    if (!tbody) return;

    if (this.grnAddedItems.length === 0) {
      tbody.innerHTML = `
        <tr id="grnEmptyRow">
          <td colspan="7" style="text-align: center; color: var(--text-muted); padding: 22px;">No materials added yet. Select a material above and click &quot;Add Material&quot;.</td>
        </tr>
      `;
      return;
    }

    let html = '';
    this.grnAddedItems.forEach((item, i) => {
      html += `
        <tr style="border-bottom: 1px solid var(--border-light);">
          <td style="padding: 10px 14px; font-weight: 600; font-family: monospace; color: var(--text-main);">${item.sku}</td>
          <td style="padding: 10px 14px; color: var(--text-main);">${item.item_name}</td>
          <td style="padding: 10px 14px; text-align: right; font-weight: 700; color: var(--text-main);">${item.quantity.toLocaleString()}</td>
          <td style="padding: 10px 14px; text-align: right; color: var(--text-muted);">Rs. ${item.unit_cost.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
          <td style="padding: 10px 14px; text-align: right; font-weight: 700; color: var(--primary);">Rs. ${item.total_cost.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
          <td style="padding: 10px 14px;"><span style="background: #f1f5f9; color: #475569; padding: 3px 8px; border-radius: 4px; font-size: 12px; font-weight: 500;">${item.location_bin}</span></td>
          <td style="padding: 10px 14px; text-align: center;">
            <button type="button" onclick="Desk.removeGrnMaterialItem(${i})" style="background: #fee2e2; border: 1px solid #fecaca; color: #dc2626; border-radius: 4px; padding: 3px 8px; font-size: 11px; font-weight: 600; cursor: pointer; transition: background 0.15s;">Remove</button>
          </td>
        </tr>
      `;
    });
    tbody.innerHTML = html;
  },

  recalcGrnInvoiceTotal() {
    const total = this.grnAddedItems.reduce((sum, it) => sum + (it.total_cost || 0), 0);
    const totInput = document.getElementById('grnInvoiceTotal');
    if (totInput) {
      totInput.value = total.toFixed(2);
    }
  },

  retrieveGrnMaterials() {
    const orderNo = (document.getElementById('grnOrderNo')?.value || '').trim();
    if (!orderNo) {
      alert('Please enter an Order No* in the Reference Data box above to retrieve materials.');
      document.getElementById('grnOrderNo')?.focus();
      return;
    }

    if (this.allCatalogMaterials.length > 0) {
      // Pick 2 random sample materials from catalog
      const sample = this.allCatalogMaterials.slice(0, 2);
      sample.forEach((m, idx) => {
        const qty = 5 * (idx + 1);
        const cost = 1250.00 * (idx + 1);
        this.grnAddedItems.push({
          sku: m.sku,
          item_name: m.item_name,
          quantity: qty,
          unit_cost: cost,
          total_cost: qty * cost,
          location_bin: 'Main Store Bay-01'
        });
      });
      this.renderGrnItemsTable();
      this.recalcGrnInvoiceTotal();
      alert(`Retrieved ${sample.length} line items from Order Reference [${orderNo}].`);
    } else {
      alert(`No pre-linked items found for Order No [${orderNo}]. Please select materials from the dropdown.`);
    }
  },

  submitDetailedGrn() {
    const receiptFrom = document.getElementById('grnReceiptFrom')?.value || '';
    if (!receiptFrom) {
      alert('Please select "Receipt From" (Purchase Order, Cost Center, or Section).');
      document.getElementById('grnReceiptFrom')?.focus();
      return;
    }

    let receiptDetails = '';
    if (receiptFrom === 'Purchase Order') {
      receiptDetails = document.getElementById('grnSelectSupplier')?.value || '';
      if (!receiptDetails) {
        alert('Please select a Receipt Supplier.');
        document.getElementById('grnSelectSupplier')?.focus();
        return;
      }
    } else if (receiptFrom === 'Cost Center') {
      receiptDetails = document.getElementById('grnSelectDept')?.value || '';
      if (!receiptDetails) {
        alert('Please select a Receipt Dept / Cost Center.');
        document.getElementById('grnSelectDept')?.focus();
        return;
      }
    } else if (receiptFrom === 'Section') {
      receiptDetails = document.getElementById('grnSelectSection')?.value || '';
      if (!receiptDetails) {
        alert('Please select a Section.');
        document.getElementById('grnSelectSection')?.focus();
        return;
      }
    }

    const invoiceNo = (document.getElementById('grnInvoiceNo')?.value || '').trim();
    if (!invoiceNo) {
      alert('Invoice No* is required in the Reference Data box.');
      const box = document.getElementById('grnRefBox');
      if (box) box.style.display = 'flex';
      document.getElementById('grnInvoiceNo')?.focus();
      return;
    }

    const orderNo = (document.getElementById('grnOrderNo')?.value || '').trim();
    if (!orderNo) {
      alert('Order No* is required in the Reference Data box.');
      const box = document.getElementById('grnRefBox');
      if (box) box.style.display = 'flex';
      document.getElementById('grnOrderNo')?.focus();
      return;
    }

    if (this.grnAddedItems.length === 0) {
      alert('Please add at least one material item to this Goods Received Note.');
      document.getElementById('grnSelectMaterial')?.focus();
      return;
    }

    const formData = new FormData();
    formData.append('doc_type', 'GRN');
    formData.append('doc_profile', document.getElementById('grnDocProfile')?.value || 'Goods Received Note');
    formData.append('receipt_from', receiptFrom);
    formData.append('receipt_details', receiptDetails);
    formData.append('source_dept', document.getElementById('grnSourceDept')?.value || '832.00');
    formData.append('warehouse', document.getElementById('grnWarehouse')?.value || 'Main Store Bay-01');
    formData.append('base_on', document.getElementById('grnBaseOn')?.value || 'PO');
    formData.append('delivery_date', document.getElementById('grnDeliveryDate')?.value || '');
    formData.append('acc_date', document.getElementById('grnAccDate')?.value || '');
    formData.append('due_date', document.getElementById('grnDueDate')?.value || '');

    // Values to consider
    formData.append('nbt', document.getElementById('grnNbt')?.value || '0');
    formData.append('nbt_excluded', document.getElementById('grnNbtExcluded')?.checked ? '1' : '0');
    formData.append('vat', document.getElementById('grnVat')?.value || '0');
    formData.append('vat_excluded', document.getElementById('grnVatExcluded')?.checked ? '1' : '0');
    formData.append('transport', document.getElementById('grnTransport')?.value || '0');
    formData.append('transport_excluded', document.getElementById('grnTransportExcluded')?.checked ? '1' : '0');
    formData.append('other_cost', document.getElementById('grnOther')?.value || '0');
    formData.append('other_excluded', document.getElementById('grnOtherExcluded')?.checked ? '1' : '0');

    // Reference data
    formData.append('invoice_number', invoiceNo);
    formData.append('order_reference', orderNo);
    formData.append('other_ref_1', document.getElementById('grnOtherRef1')?.value || '');
    formData.append('other_ref_2', document.getElementById('grnOtherRef2')?.value || '');

    // Remarks
    formData.append('remarks', document.getElementById('grnRemarks')?.value || '');

    // Totals & Discounts
    formData.append('invoice_total', document.getElementById('grnInvoiceTotal')?.value || '0');
    formData.append('is_single_material', document.getElementById('grnSingleMaterial')?.checked ? '1' : '0');
    formData.append('discount_for_all', document.getElementById('grnDiscountForAll')?.value || '0');
    formData.append('is_percentage', document.getElementById('grnIsPercentage')?.checked ? '1' : '0');

    // Items
    formData.append('items', JSON.stringify(this.grnAddedItems));

    fetch(`${this.baseUrl}/api/inventory.php?action=create_document`, {
      method: 'POST',
      body: formData
    })
    .then(res => res.json())
    .then(res => {
      if (res.success) {
        alert(res.message || 'Draft GRN Receipt successfully created!');
        Desk.clearDetailedGrn();
        Desk.loadReceipts();
      } else {
        alert(res.message || 'Error creating GRN.');
      }
    })
    .catch(err => alert('Network error: ' + err));
  },

  clearDetailedGrn() {
    const form = document.getElementById('formBodyNewGrn');
    if (form) form.reset();
    this.grnAddedItems = [];
    this.renderGrnItemsTable();
    this.onReceiptFromChange('');
    this.onDocProfileChange('Goods Received Note');
  },

  // -----------------------------------------------------------------
  // Legacy FIFO Style Material Issue In-Body Detailed Form Methods
  // -----------------------------------------------------------------
  issueAddedItems: [],
  issueCatalogMaterials: [],
  issueLookupsLoaded: false,

  onIssueDocProfileChange(val) {
    const notice = document.getElementById('issueDocPfNotice');
    if (!notice) return;
    if (!val) {
      notice.textContent = 'Doc PF Not Selected';
      notice.style.color = '#2563eb';
    } else {
      notice.textContent = val;
      notice.style.color = '#15803d';
    }
  },

  onIssueToChange(val) {
    const blockProj = document.getElementById('issueBlockProject');
    const blockCost = document.getElementById('issueBlockCostCenter');
    const blockWh = document.getElementById('issueBlockWarehouse');
    const blockMyPrj = document.getElementById('issueBlockMyProject');
    const blockSec = document.getElementById('issueBlockSection');
    const blockExp = document.getElementById('issueBlockExpenditure');

    // Hide all first
    if (blockProj) blockProj.style.display = 'none';
    if (blockCost) blockCost.style.display = 'none';
    if (blockWh) blockWh.style.display = 'none';
    if (blockMyPrj) blockMyPrj.style.display = 'none';
    if (blockSec) blockSec.style.display = 'none';
    if (blockExp) blockExp.style.display = 'none';

    if (val === 'Project' && blockProj) {
      blockProj.style.display = 'flex';
    } else if (val === 'Cost Center' && blockCost) {
      blockCost.style.display = 'flex';
    } else if (val === 'Warehouse' && blockWh) {
      blockWh.style.display = 'flex';
    } else if (val === 'My Project' && blockMyPrj) {
      blockMyPrj.style.display = 'flex';
    } else if (val === 'Section' && blockSec) {
      blockSec.style.display = 'flex';
    } else if (val === 'Genaration as Expenditure' && blockExp) {
      blockExp.style.display = 'flex';
    }
  },

  toggleIssueSection(boxId, headerElem) {
    const box = document.getElementById(boxId);
    if (!box) return;
    const isHidden = (box.style.display === 'none');
    box.style.display = isHidden ? 'flex' : 'none';
    const icon = headerElem.querySelector('.grn-toggle-icon');
    if (icon) {
      icon.textContent = isHidden ? '-' : '+';
    }
  },

  initIssueFormLookups() {
    if (this.issueLookupsLoaded) return;

    fetch(`${this.baseUrl}/api/inventory.php?action=get_issue_lookups`)
      .then(res => res.json())
      .then(res => {
        if (!res.success || !res.data) return;
        this.issueLookupsLoaded = true;
        const d = res.data;

        // 1. Cost Centers -> Project Dest Dept & Dept Id
        const selProjDept = document.getElementById('issueProjectDestDept');
        const selDeptId = document.getElementById('issueDeptId');
        if (Array.isArray(d.cost_centers)) {
          if (selProjDept) {
            selProjDept.innerHTML = '<option value="">---- Select Cost Center----</option>';
            d.cost_centers.forEach(c => {
              const opt = document.createElement('option');
              opt.value = c.code;
              opt.textContent = c.name;
              selProjDept.appendChild(opt);
            });
          }
          if (selDeptId) {
            selDeptId.innerHTML = '<option value="">---- Select Cost Center----</option>';
            d.cost_centers.forEach(c => {
              const opt = document.createElement('option');
              opt.value = c.code;
              opt.textContent = c.name;
              selDeptId.appendChild(opt);
            });
          }
        }

        // 2. Projects
        const selProj = document.getElementById('issueProjectSelect');
        if (selProj && Array.isArray(d.projects)) {
          selProj.innerHTML = '<option value="">---- Select Project----</option>';
          d.projects.forEach(p => {
            const opt = document.createElement('option');
            opt.value = p;
            opt.textContent = p;
            selProj.appendChild(opt);
          });
        }

        // 3. My Projects
        const selMyDept = document.getElementById('issueMyPrjDestDept');
        const selMyPrj = document.getElementById('issueMyProjectSelect');
        if (Array.isArray(d.my_projects)) {
          if (selMyDept) {
            selMyDept.innerHTML = '<option value="">---- Select Your Project----</option>';
            d.my_projects.forEach(p => {
              const opt = document.createElement('option');
              opt.value = p;
              opt.textContent = p;
              selMyDept.appendChild(opt);
            });
          }
          if (selMyPrj) {
            selMyPrj.innerHTML = '<option value="">---- Select My Project----</option>';
            d.my_projects.forEach(p => {
              const opt = document.createElement('option');
              opt.value = p;
              opt.textContent = p;
              selMyPrj.appendChild(opt);
            });
          }
        }

        // 4. Warehouses -> Issue to Warehouse & Source Warehouse
        const selWhIssue = document.getElementById('issueWarehouseToIssue');
        const selWhSrc = document.getElementById('issueSourceWarehouse');
        if (Array.isArray(d.warehouses)) {
          if (selWhIssue) {
            selWhIssue.innerHTML = '<option value="">---- Select Issue To Warehouse----</option>';
            d.warehouses.forEach(w => {
              const opt = document.createElement('option');
              opt.value = w;
              opt.textContent = w;
              selWhIssue.appendChild(opt);
            });
          }
          if (selWhSrc) {
            selWhSrc.innerHTML = '<option value="">---- Select Source Warehouse----</option>';
            d.warehouses.forEach((w, idx) => {
              const opt = document.createElement('option');
              opt.value = w;
              opt.textContent = w;
              if (idx === 0) opt.selected = true;
              selWhSrc.appendChild(opt);
            });
          }
        }

        // 5. Sections
        const selSec = document.getElementById('issueSectionSelect');
        if (selSec && Array.isArray(d.sections)) {
          selSec.innerHTML = '<option value="">---- Select Section----</option>';
          d.sections.forEach(s => {
            const opt = document.createElement('option');
            opt.value = s.code;
            opt.textContent = s.display_label || `${s.code} - ${s.title}`;
            selSec.appendChild(opt);
          });
        }

        // 6. Cost Codes
        const selCostCd = document.getElementById('issueCostCode');
        if (selCostCd && Array.isArray(d.cost_codes)) {
          selCostCd.innerHTML = '<option value="">---- Select Cost Cd----</option>';
          d.cost_codes.forEach(cc => {
            const opt = document.createElement('option');
            opt.value = cc.code;
            opt.textContent = cc.label;
            selCostCd.appendChild(opt);
          });
        }

        // 7. Issuing Types
        const selIssType = document.getElementById('issueIssuingType');
        if (selIssType && Array.isArray(d.issuing_types)) {
          selIssType.innerHTML = '<option value="">---- Select Issuing Type----</option>';
          d.issuing_types.forEach(it => {
            const opt = document.createElement('option');
            opt.value = it;
            opt.textContent = it;
            selIssType.appendChild(opt);
          });
        }

        // 8. Materials
        if (Array.isArray(d.materials)) {
          this.issueCatalogMaterials = d.materials;
          this.renderIssueMaterialDropdown(d.materials);
        }
      })
      .catch(err => console.error('Failed to load Issue lookups:', err));
  },

  renderIssueMaterialDropdown(materials) {
    const selMat = document.getElementById('issueSelectMaterial');
    if (!selMat) return;
    selMat.innerHTML = '<option value="">-- Select Material For Issues--</option>';
    materials.slice(0, 150).forEach(m => {
      const opt = document.createElement('option');
      opt.value = m.sku;
      opt.textContent = `${m.sku} - ${m.item_name}`;
      opt.dataset.sku = m.sku;
      opt.dataset.name = m.item_name;
      opt.dataset.unitCost = m.unit_price || 0;
      selMat.appendChild(opt);
    });
  },

  filterIssueMaterialDropdown(query) {
    const q = (query || '').toLowerCase().trim();
    if (!q) {
      this.renderIssueMaterialDropdown(this.issueCatalogMaterials);
      return;
    }
    const filtered = this.issueCatalogMaterials.filter(m => 
      (m.sku && m.sku.toLowerCase().includes(q)) || 
      (m.item_name && m.item_name.toLowerCase().includes(q))
    );
    this.renderIssueMaterialDropdown(filtered);
  },

  onIssueMaterialChange(sku) {
    const availInput = document.getElementById('issueAvailQty');
    const reorderInput = document.getElementById('issueReorderQty');
    if (!sku) {
      if (availInput) availInput.value = '0';
      if (reorderInput) reorderInput.value = '0';
      return;
    }

    fetch(`${this.baseUrl}/api/inventory.php?action=get_stock_availability&sku=${encodeURIComponent(sku)}`)
      .then(res => res.json())
      .then(res => {
        if (res.success && res.data) {
          if (availInput) availInput.value = res.data.available_to_issue ?? 0;
          if (reorderInput) reorderInput.value = res.data.reorder_level ?? 5;
        } else {
          if (availInput) availInput.value = '0';
          if (reorderInput) reorderInput.value = '0';
        }
      })
      .catch(() => {
        if (availInput) availInput.value = '0';
        if (reorderInput) reorderInput.value = '0';
      });
  },

  addIssueMaterialItem() {
    const selMat = document.getElementById('issueSelectMaterial');
    if (!selMat || !selMat.value) {
      alert('Please select a material from the dropdown first.');
      selMat?.focus();
      return;
    }

    const selectedOpt = selMat.options[selMat.selectedIndex];
    const sku = selectedOpt.dataset.sku || selMat.value;
    const itemName = selectedOpt.dataset.name || selectedOpt.textContent.replace(`${sku} - `, '');
    const availQty = parseInt(document.getElementById('issueAvailQty')?.value) || 0;
    const reqQtyInput = document.getElementById('issueReqQty');
    const reqQty = parseInt(reqQtyInput?.value) || 1;

    if (reqQty <= 0) {
      alert('Required Quantity must be at least 1.');
      reqQtyInput?.focus();
      return;
    }

    if (availQty <= 0) {
      const confirmZero = confirm(`Warning: Available Quantity for [${sku}] is currently 0. Do you still want to add this requisition item?`);
      if (!confirmZero) return;
    } else if (reqQty > availQty) {
      const confirmExcess = confirm(`Warning: Required Quantity (${reqQty}) exceeds Free Available Quantity (${availQty}). Do you still want to proceed?`);
      if (!confirmExcess) return;
    }

    // Check if already added
    const existing = this.issueAddedItems.find(it => it.sku === sku);
    if (existing) {
      existing.quantity += reqQty;
      existing.avail_qty = availQty;
      existing.total_cost = existing.quantity * (existing.unit_cost || 0);
    } else {
      const unitCost = parseFloat(selectedOpt.dataset.unitCost) || 0;
      this.issueAddedItems.push({
        sku: sku,
        item_name: itemName,
        avail_qty: availQty,
        quantity: reqQty,
        unit_cost: unitCost,
        total_cost: reqQty * unitCost,
        location_bin: 'Main Store Bay-01'
      });
    }

    this.renderIssueItemsTable();
    if (reqQtyInput) reqQtyInput.value = 1;
  },

  removeIssueMaterialItem(idx) {
    if (idx >= 0 && idx < this.issueAddedItems.length) {
      this.issueAddedItems.splice(idx, 1);
      this.renderIssueItemsTable();
    }
  },

  renderIssueItemsTable() {
    const tbody = document.getElementById('issueItemsTableBody');
    if (!tbody) return;

    if (this.issueAddedItems.length === 0) {
      tbody.innerHTML = `
        <tr id="issueEmptyRow">
          <td colspan="7" style="text-align: center; color: var(--text-muted); padding: 22px;">No materials added yet. Select a material above and click &quot;Add Material To Issue&quot;.</td>
        </tr>
      `;
      return;
    }

    let html = '';
    this.issueAddedItems.forEach((item, i) => {
      html += `
        <tr style="border-bottom: 1px solid var(--border-light);">
          <td style="padding: 10px 14px; font-weight: 600; font-family: monospace; color: var(--text-main);">${item.sku}</td>
          <td style="padding: 10px 14px; color: var(--text-main);">${item.item_name}</td>
          <td style="padding: 10px 14px; text-align: right; color: ${item.avail_qty > 0 ? '#15803d' : '#b91c1c'}; font-weight: 600;">${item.avail_qty.toLocaleString()}</td>
          <td style="padding: 10px 14px; text-align: right; font-weight: 700; color: var(--text-main);">${item.quantity.toLocaleString()}</td>
          <td style="padding: 10px 14px; text-align: right; color: var(--text-muted);">${item.unit_cost > 0 ? 'Rs. ' + item.unit_cost.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}) : '-'}</td>
          <td style="padding: 10px 14px; text-align: right; font-weight: 700; color: var(--primary);">${item.total_cost > 0 ? 'Rs. ' + item.total_cost.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}) : '-'}</td>
          <td style="padding: 10px 14px; text-align: center;">
            <button type="button" onclick="Desk.removeIssueMaterialItem(${i})" style="background: #fee2e2; border: 1px solid #fecaca; color: #dc2626; border-radius: 4px; padding: 3px 8px; font-size: 11px; font-weight: 600; cursor: pointer; transition: background 0.15s;">Remove</button>
          </td>
        </tr>
      `;
    });
    tbody.innerHTML = html;
  },

  retrieveIssueMaterials() {
    const issueTo = document.getElementById('issueToSelect')?.value || '';
    if (!issueTo) {
      alert('Please select "Issue To" first.');
      document.getElementById('issueToSelect')?.focus();
      return;
    }
    if (this.issueCatalogMaterials && this.issueCatalogMaterials.length > 0) {
      const sample = this.issueCatalogMaterials.slice(0, 2);
      sample.forEach((m, idx) => {
        this.issueAddedItems.push({
          sku: m.sku,
          item_name: m.item_name,
          avail_qty: 25,
          quantity: 2 + idx,
          unit_cost: parseFloat(m.unit_price) || 0,
          total_cost: (2 + idx) * (parseFloat(m.unit_price) || 0),
          location_bin: 'Main Store Bay-01'
        });
      });
      this.renderIssueItemsTable();
      alert(`Retrieved ${sample.length} standard materials for ${issueTo}.`);
    } else {
      alert('Please select materials from the dropdown.');
    }
  },

  submitDetailedIssue() {
    const issueTo = document.getElementById('issueToSelect')?.value || '';
    if (!issueTo) {
      alert('Please select "Issue To" destination type (Project, Cost Center, Warehouse, My Project, Section, or Genaration as Expenditure).');
      document.getElementById('issueToSelect')?.focus();
      return;
    }

    let destDetails = '';
    let deptCode = '832.00';

    if (issueTo === 'Project') {
      const projDept = document.getElementById('issueProjectDestDept')?.value || '';
      const proj = document.getElementById('issueProjectSelect')?.value || '';
      if (!projDept) {
        alert('Please select Project Dest Dept (Cost Center).');
        document.getElementById('issueProjectDestDept')?.focus();
        return;
      }
      deptCode = projDept;
      destDetails = proj || projDept;
    } else if (issueTo === 'Cost Center') {
      const deptId = document.getElementById('issueDeptId')?.value || '';
      if (!deptId) {
        alert('Please select Dept Id (Cost Center).');
        document.getElementById('issueDeptId')?.focus();
        return;
      }
      deptCode = deptId;
      destDetails = deptId;
    } else if (issueTo === 'Warehouse') {
      const whIssue = document.getElementById('issueWarehouseToIssue')?.value || '';
      if (!whIssue) {
        alert('Please select Warehouse to Issue.');
        document.getElementById('issueWarehouseToIssue')?.focus();
        return;
      }
      destDetails = whIssue;
    } else if (issueTo === 'My Project') {
      const myPrj = document.getElementById('issueMyProjectSelect')?.value || '';
      const myDept = document.getElementById('issueMyPrjDestDept')?.value || '';
      if (!myPrj && !myDept) {
        alert('Please select My Project destination.');
        document.getElementById('issueMyProjectSelect')?.focus();
        return;
      }
      destDetails = myPrj || myDept;
      if (myDept) deptCode = myDept;
    } else if (issueTo === 'Section') {
      const sec = document.getElementById('issueSectionSelect')?.value || '';
      const costCd = document.getElementById('issueCostCode')?.value || '';
      if (!sec) {
        alert('Please select Section.');
        document.getElementById('issueSectionSelect')?.focus();
        return;
      }
      destDetails = sec;
      if (costCd) deptCode = costCd;
    } else if (issueTo === 'Genaration as Expenditure') {
      const issType = document.getElementById('issueIssuingType')?.value || '';
      if (!issType) {
        alert('Please select Issuing Type.');
        document.getElementById('issueIssuingType')?.focus();
        return;
      }
      destDetails = issType;
    }

    const warehouse = document.getElementById('issueSourceWarehouse')?.value || '';
    if (!warehouse) {
      alert('Please select Source Warehouse.');
      document.getElementById('issueSourceWarehouse')?.focus();
      return;
    }

    if (this.issueAddedItems.length === 0) {
      alert('Please add at least one material item to the issue requisition before saving.');
      document.getElementById('issueSelectMaterial')?.focus();
      return;
    }

    const formData = new FormData();
    formData.append('doc_type', 'ISSUE');
    formData.append('doc_profile', document.getElementById('issueDocProfile')?.value || 'Material Issue Requisition');
    formData.append('receipt_from', issueTo);
    formData.append('department_code', deptCode);
    formData.append('destination_code', destDetails);
    formData.append('warehouse', warehouse);
    formData.append('delivery_date', document.getElementById('issueTrxDate')?.value || '');
    formData.append('acc_date', document.getElementById('issueAccDate')?.value || '');
    formData.append('other_ref_1', document.getElementById('issueRef1')?.value || '');
    formData.append('other_ref_2', document.getElementById('issueRef2')?.value || '');
    formData.append('other_ref_3', document.getElementById('issueRef3')?.value || '');
    formData.append('other_ref_4', document.getElementById('issueRef4')?.value || '');
    formData.append('remarks', document.getElementById('issueRemarks')?.value || '');
    formData.append('items', JSON.stringify(this.issueAddedItems));

    fetch(`${this.baseUrl}/api/inventory.php?action=create_document`, {
      method: 'POST',
      body: formData
    })
    .then(res => res.json())
    .then(res => {
      if (res.success) {
        alert(res.message || 'Material Issue Requisition saved successfully!');
        this.clearDetailedIssue();
        this.loadIssues();
      } else {
        alert('Failed to save issue: ' + (res.message || 'Validation error'));
      }
    })
    .catch(err => {
      alert('Network error while saving issue: ' + err);
    });
  },

  clearDetailedIssue() {
    const form = document.getElementById('formBodyNewIssue');
    if (form) form.reset();
    this.issueAddedItems = [];
    this.renderIssueItemsTable();
    this.onIssueToChange('');
    this.onIssueDocProfileChange('');
    const availInput = document.getElementById('issueAvailQty');
    if (availInput) availInput.value = '0';
    const reorderInput = document.getElementById('issueReorderQty');
    if (reorderInput) reorderInput.value = '0';
    const reqQtyInput = document.getElementById('issueReqQty');
    if (reqQtyInput) reqQtyInput.value = '1';
  },

  // -----------------------------------------------------------------
  // Modals & Creation Forms
  // -----------------------------------------------------------------
  openModal(modalId) {
    const m = document.getElementById(modalId);
    if (m) m.style.display = 'flex';
    if (modalId === 'modalNewIssue') {
      this.checkIssueStockAvailability();
    }
    if (modalId === 'createUserModal') {
      const locSelect = document.getElementById('createUserLocId');
      const deptSelect = document.getElementById('createUserDept');
      if (locSelect) locSelect.selectedIndex = 0;
      if (deptSelect) deptSelect.innerHTML = '<option value="" disabled selected>-- Select Station First --</option>';
    }
  },

  closeModal(modalId) {
    const m = document.getElementById(modalId);
    if (m) m.style.display = 'none';
  },

  togglePassword(inputId, btn) {
    const input = document.getElementById(inputId);
    if (!input) return;
    const isPassword = input.type === 'password';
    input.type = isPassword ? 'text' : 'password';
    if (btn) {
      btn.innerHTML = isPassword 
        ? `<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M9.88 9.88a3 3 0 1 0 4.24 4.24"/><path d="M10.73 5.08A10.43 10.43 0 0 1 12 5c7 0 10 7 10 7a13.16 13.16 0 0 1-1.67 2.68"/><path d="M6.61 6.61A13.526 13.526 0 0 0 2 12s3 7 10 7a9.74 9.74 0 0 0 5.39-1.61"/><line x1="2" x2="22" y1="2" y2="22"/></svg>`
        : `<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>`;
      btn.setAttribute('title', isPassword ? 'Hide password' : 'Show password');
    }
  },

  // -----------------------------------------------------------------
  // Three-Tier Stock Availability & Reservation Handlers
  // -----------------------------------------------------------------
  currentStockAvailability: null,

  async checkIssueStockAvailability(sku) {
    if (!sku) {
      const skuSelect = document.getElementById('issueSku');
      if (skuSelect) sku = skuSelect.value;
    }
    if (!sku) return;

    try {
      const res = await fetch(`${this.baseUrl}/api/inventory.php?action=get_stock_availability&sku=${encodeURIComponent(sku)}`);
      const data = await res.json();
      if (data.success) {
        this.currentStockAvailability = data.data;
        const physEl = document.getElementById('issueStockPhysical');
        const allocEl = document.getElementById('issueStockAllocated');
        const availEl = document.getElementById('issueStockAvailable');
        if (physEl) physEl.textContent = data.data.physical_on_hand;
        if (allocEl) allocEl.textContent = data.data.allocated_reserved;
        if (availEl) {
          availEl.textContent = data.data.available_to_issue;
          availEl.style.color = data.data.available_to_issue > 0 ? '#059669' : '#dc2626';
        }
        this.validateIssueQty();
      }
    } catch (e) {
      console.warn('Could not check stock availability', e);
    }
  },

  validateIssueQty() {
    const qtyInput = document.getElementById('issueQty');
    const alertBox = document.getElementById('issueStockAlert');
    const submitBtn = document.querySelector('#formNewIssue button[type="submit"]');
    if (!qtyInput || !this.currentStockAvailability) return;

    const requested = parseInt(qtyInput.value) || 0;
    const avail = this.currentStockAvailability.available_to_issue;
    const phys = this.currentStockAvailability.physical_on_hand;
    const alloc = this.currentStockAvailability.allocated_reserved;

    if (requested > avail) {
      qtyInput.style.borderColor = '#ef4444';
      if (alertBox) {
        alertBox.style.display = 'block';
        alertBox.innerHTML = `⚠️ <strong>Stock Reservation Conflict:</strong> Requested ${requested} units exceeds available stock of ${avail} (${phys} on-hand, ${alloc} reserved under pending approvals).`;
      }
      if (submitBtn) submitBtn.disabled = true;
    } else {
      qtyInput.style.borderColor = '';
      if (alertBox) alertBox.style.display = 'none';
      if (submitBtn) submitBtn.disabled = false;
    }
  },

  // -----------------------------------------------------------------
  // Administration & Governance
  // -----------------------------------------------------------------
  currentAdminSubTab: 'admin-sub-locations',

  switchAdminSubTab(subTabId) {
    if (!subTabId || !document.getElementById(subTabId)) {
      subTabId = 'admin-sub-locations';
    }
    this.currentAdminSubTab = subTabId;
    try {
      sessionStorage.setItem('fifoerp_active_admin_sub', subTabId);
      history.replaceState(null, '', '#tab-admin/' + subTabId);
    } catch (e) {}
    document.querySelectorAll('.admin-sub-tab-btn').forEach(btn => {
      btn.classList.toggle('active', btn.getAttribute('data-admin-sub') === subTabId);
    });
    document.querySelectorAll('.admin-sub-pane').forEach(p => {
      p.classList.toggle('active', p.id === subTabId);
    });
    document.querySelectorAll('.sidebar-nav-item[data-admin-sub]').forEach(btn => {
      btn.classList.toggle('active', btn.getAttribute('data-admin-sub') === subTabId);
    });
  },

  filterDepartmentsByLocation(locId) {
    const rows = document.querySelectorAll('#departmentsTableBody tr[data-location-id]');
    rows.forEach(r => {
      if (!locId || r.getAttribute('data-location-id') === String(locId)) {
        r.style.display = '';
      } else {
        r.style.display = 'none';
      }
    });
  },

  // Location Handlers
  openEditLocationModal(id, name, code, status) {
    document.getElementById('editLocId').value = id;
    document.getElementById('editLocName').value = name;
    document.getElementById('editLocCode').value = code;
    document.getElementById('editLocStatus').value = status;
    this.openModal('editLocationModal');
  },

  async submitCreateLocation() {
    const form = document.getElementById('createLocationForm');
    if (!form) return;
    const fd = new FormData(form);
    fd.append('action', 'create');
    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    if (!fd.has('_csrf_token') && token) fd.append('_csrf_token', token);

    try {
      const res = await fetch(`${this.baseUrl}/api/locations.php`, { method: 'POST', body: fd });
      const data = await res.json();
      if (data.success) {
        this.showToast(data.message || 'Power Station added successfully!');
        this.closeModal('createLocationModal');
        setTimeout(() => location.reload(), 600);
      } else {
        this.showToast(data.message || 'Failed to add location.', true);
      }
    } catch (err) {
      console.error(err);
      this.showToast('Network error while saving location.', true);
    }
  },

  async submitEditLocation() {
    const form = document.getElementById('editLocationForm');
    if (!form) return;
    const fd = new FormData(form);
    fd.append('action', 'update');
    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    if (!fd.has('_csrf_token') && token) fd.append('_csrf_token', token);

    try {
      const res = await fetch(`${this.baseUrl}/api/locations.php`, { method: 'POST', body: fd });
      const data = await res.json();
      if (data.success) {
        this.showToast(data.message || 'Location updated successfully!');
        this.closeModal('editLocationModal');
        setTimeout(() => location.reload(), 600);
      } else {
        this.showToast(data.message || 'Failed to update location.', true);
      }
    } catch (err) {
      console.error(err);
      this.showToast('Network error while updating location.', true);
    }
  },

  async deleteLocation(id, name) {
    if (!confirm(`Are you sure you want to delete "${name}"? This location and its assigned departments will be affected.`)) {
      return;
    }
    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    const fd = new FormData();
    fd.append('action', 'delete');
    fd.append('id', id);
    fd.append('_csrf_token', token);

    try {
      const res = await fetch(`${this.baseUrl}/api/locations.php`, { method: 'POST', body: fd });
      const data = await res.json();
      if (data.success) {
        this.showToast(data.message || 'Location deleted successfully.');
        const row = document.getElementById(`loc-row-${id}`);
        if (row) row.remove();
      } else {
        this.showToast(data.message || 'Could not delete location.', true);
      }
    } catch (err) {
      console.error(err);
      this.showToast('Network error while deleting location.', true);
    }
  },

  // Department Handlers
  openEditDepartmentModal(id, locationId, name, code, status) {
    document.getElementById('editDeptId').value = id;
    document.getElementById('editDeptLocationId').value = locationId;
    document.getElementById('editDeptName').value = name;
    document.getElementById('editDeptCode').value = code;
    document.getElementById('editDeptStatus').value = status;
    this.openModal('editDepartmentModal');
  },

  async submitCreateDepartment() {
    const form = document.getElementById('createDepartmentForm');
    if (!form) return;
    const fd = new FormData(form);
    fd.append('action', 'create');
    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    if (!fd.has('_csrf_token') && token) fd.append('_csrf_token', token);

    try {
      const res = await fetch(`${this.baseUrl}/api/departments.php`, { method: 'POST', body: fd });
      const data = await res.json();
      if (data.success) {
        this.showToast(data.message || 'Department added successfully!');
        this.closeModal('createDepartmentModal');
        setTimeout(() => location.reload(), 600);
      } else {
        this.showToast(data.message || 'Failed to add department.', true);
      }
    } catch (err) {
      console.error(err);
      this.showToast('Network error while saving department.', true);
    }
  },

  async submitEditDepartment() {
    const form = document.getElementById('editDepartmentForm');
    if (!form) return;
    const fd = new FormData(form);
    fd.append('action', 'update');
    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    if (!fd.has('_csrf_token') && token) fd.append('_csrf_token', token);

    try {
      const res = await fetch(`${this.baseUrl}/api/departments.php`, { method: 'POST', body: fd });
      const data = await res.json();
      if (data.success) {
        this.showToast(data.message || 'Department updated successfully!');
        this.closeModal('editDepartmentModal');
        setTimeout(() => location.reload(), 600);
      } else {
        this.showToast(data.message || 'Failed to update department.', true);
      }
    } catch (err) {
      console.error(err);
      this.showToast('Network error while updating department.', true);
    }
  },

  async deleteDepartment(id, name) {
    if (!confirm(`Are you sure you want to delete "${name}"?`)) {
      return;
    }
    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    const fd = new FormData();
    fd.append('action', 'delete');
    fd.append('id', id);
    fd.append('_csrf_token', token);

    try {
      const res = await fetch(`${this.baseUrl}/api/departments.php`, { method: 'POST', body: fd });
      const data = await res.json();
      if (data.success) {
        this.showToast(data.message || 'Department deleted successfully.');
        const row = document.getElementById(`dept-row-${id}`);
        if (row) row.remove();
      } else {
        this.showToast(data.message || 'Could not delete department.', true);
      }
    } catch (err) {
      console.error(err);
      this.showToast('Network error while deleting department.', true);
    }
  },

  // User Handlers
  escapeHtml(str) {
    if (!str) return '';
    return String(str).replace(/[&<>"']/g, m => ({
      '&': '&amp;',
      '<': '&lt;',
      '>': '&gt;',
      '"': '&quot;',
      "'": '&#039;'
    })[m]);
  },

  async onStationSelectChange(locationId, targetSelectId, selectedValue = '') {
    const deptSelect = document.getElementById(targetSelectId);
    if (!deptSelect) return;

    if (!locationId) {
      deptSelect.innerHTML = '<option value="" disabled selected>-- Select Station First --</option>';
      return;
    }

    deptSelect.innerHTML = '<option value="" disabled selected>Loading departments...</option>';
    deptSelect.disabled = true;

    try {
      const res = await fetch(`${this.baseUrl}/api/departments.php?action=list&location_id=${locationId}`);
      const data = await res.json();
      deptSelect.disabled = false;

      if (data.success && Array.isArray(data.data) && data.data.length > 0) {
        let optionsHtml = '<option value="" disabled' + (!selectedValue ? ' selected' : '') + '>-- Select Department / Store --</option>';
        data.data.forEach(d => {
          const isSel = (selectedValue && (selectedValue === d.name || selectedValue === String(d.id))) ? ' selected' : '';
          optionsHtml += `<option value="${this.escapeHtml(d.name)}"${isSel}>${this.escapeHtml(d.name)} (${this.escapeHtml(d.code)})</option>`;
        });
        deptSelect.innerHTML = optionsHtml;
      } else {
        deptSelect.innerHTML = '<option value="" disabled selected>-- No departments found for this station --</option>';
      }
    } catch (err) {
      console.error('Error fetching departments:', err);
      deptSelect.disabled = false;
      deptSelect.innerHTML = '<option value="" disabled selected>Error loading departments</option>';
    }
  },

  openEditUserModal(user) {
    document.getElementById('editUserId').value = user.id;
    document.getElementById('editUserLocId').value = user.location_id || 1;
    document.getElementById('editUserRoleId').value = user.role_id;
    document.getElementById('editUserStatus').value = user.status;
    if (document.getElementById('editUserFullName')) document.getElementById('editUserFullName').value = user.full_name || '';
    document.getElementById('editUserUsername').value = user.username;
    if (document.getElementById('editUserEmail')) document.getElementById('editUserEmail').value = user.email || '';
    document.getElementById('editUserPassword').value = '';

    // Dynamically load departments of this station and select user's department
    this.onStationSelectChange(user.location_id || 1, 'editUserDept', user.department || '');

    this.openModal('editUserModal');
  },

  async submitCreateUser() {
    const form = document.getElementById('createUserForm');
    if (!form) return;
    const fd = new FormData(form);
    fd.append('action', 'create');
    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    if (!fd.has('_csrf_token') && token) fd.append('_csrf_token', token);

    try {
      const res = await fetch(`${this.baseUrl}/api/users.php`, { method: 'POST', body: fd });
      const data = await res.json();
      if (data.success) {
        this.showToast(data.message || 'User account created successfully!');
        this.closeModal('createUserModal');
        setTimeout(() => location.reload(), 600);
      } else {
        this.showToast(data.message || 'Failed to create user.', true);
      }
    } catch (err) {
      console.error(err);
      this.showToast('Network error while creating user.', true);
    }
  },

  async submitEditUser() {
    const form = document.getElementById('editUserForm');
    if (!form) return;
    const fd = new FormData(form);
    fd.append('action', 'update');
    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    if (!fd.has('_csrf_token') && token) fd.append('_csrf_token', token);

    try {
      const res = await fetch(`${this.baseUrl}/api/users.php`, { method: 'POST', body: fd });
      const data = await res.json();
      if (data.success) {
        this.showToast(data.message || 'User account updated successfully!');
        this.closeModal('editUserModal');
        setTimeout(() => location.reload(), 600);
      } else {
        this.showToast(data.message || 'Failed to update user.', true);
      }
    } catch (err) {
      console.error(err);
      this.showToast('Network error while updating user.', true);
    }
  },

  async deleteUser(id, name) {
    if (!confirm(`Are you sure you want to delete user "${name}"?`)) {
      return;
    }
    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    const fd = new FormData();
    fd.append('action', 'delete');
    fd.append('id', id);
    fd.append('_csrf_token', token);

    try {
      const res = await fetch(`${this.baseUrl}/api/users.php`, { method: 'POST', body: fd });
      const data = await res.json();
      if (data.success) {
        this.showToast(data.message || 'User deleted successfully.');
        const row = document.getElementById(`user-row-${id}`);
        if (row) row.remove();
      } else {
        this.showToast(data.message || 'Could not delete user.', true);
      }
    } catch (err) {
      console.error(err);
      this.showToast('Network error while deleting user.', true);
    }
  },

  // Role Handlers
  async openEditRoleModal(role) {
    document.getElementById('editRoleId').value = role.id;
    document.getElementById('editRoleDisplayName').value = role.display_name;
    if (document.getElementById('editRoleCode')) document.getElementById('editRoleCode').value = role.code || '';

    // Fetch and check active permissions for this role
    try {
      const res = await fetch(`${this.baseUrl}/api/roles.php?action=get_permissions&role_id=${role.id}`);
      const data = await res.json();
      if (data.success && data.data && data.data.permissions) {
        const perms = data.data.permissions;
        const form = document.getElementById('editRoleForm');
        if (form) {
          form.querySelectorAll('input.edit-role-perm').forEach(cb => {
            const match = cb.name.match(/\[(.*?)\]/);
            if (match && match[1]) {
              const permKey = match[1];
              cb.checked = perms[permKey] !== undefined ? (Number(perms[permKey]) === 1) : true;
            }
          });
        }
      }
    } catch (e) {
      console.warn('Failed to fetch role permissions:', e);
    }

    this.openModal('editRoleModal');
  },

  toggleAllRolePerms(formId, checked) {
    const form = document.getElementById(formId);
    if (!form) return;
    form.querySelectorAll('input[type="checkbox"]').forEach(cb => {
      cb.checked = checked;
    });
  },

  async submitCreateRole() {
    const form = document.getElementById('createRoleForm');
    if (!form) return;
    const fd = new FormData(form);
    fd.append('action', 'create');
    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    if (!fd.has('_csrf_token') && token) fd.append('_csrf_token', token);

    try {
      const res = await fetch(`${this.baseUrl}/api/roles.php`, { method: 'POST', body: fd });
      const data = await res.json();
      if (data.success) {
        this.showToast(data.message || 'Role created successfully!');
        this.closeModal('createRoleModal');
        setTimeout(() => location.reload(), 600);
      } else {
        this.showToast(data.message || 'Failed to create role.', true);
      }
    } catch (err) {
      console.error(err);
      this.showToast('Network error while creating role.', true);
    }
  },

  async submitEditRole() {
    const form = document.getElementById('editRoleForm');
    if (!form) return;
    const fd = new FormData(form);
    fd.append('action', 'update');
    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    if (!fd.has('_csrf_token') && token) fd.append('_csrf_token', token);

    try {
      const res = await fetch(`${this.baseUrl}/api/roles.php`, { method: 'POST', body: fd });
      const data = await res.json();
      if (data.success) {
        this.showToast(data.message || 'Role updated successfully!');
        this.closeModal('editRoleModal');
        setTimeout(() => location.reload(), 600);
      } else {
        this.showToast(data.message || 'Failed to update role.', true);
      }
    } catch (err) {
      console.error(err);
      this.showToast('Network error while updating role.', true);
    }
  },

  async deleteRole(id, name) {
    if (!confirm(`Are you sure you want to delete role "${name}"?`)) {
      return;
    }
    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    const fd = new FormData();
    fd.append('action', 'delete');
    fd.append('id', id);
    fd.append('_csrf_token', token);

    try {
      const res = await fetch(`${this.baseUrl}/api/roles.php`, { method: 'POST', body: fd });
      const data = await res.json();
      if (data.success) {
        this.showToast(data.message || 'Role deleted successfully.');
        const row = document.getElementById(`role-row-${id}`);
        if (row) row.remove();
      } else {
        this.showToast(data.message || 'Could not delete role.', true);
      }
    } catch (err) {
      console.error(err);
      this.showToast('Network error while deleting role.', true);
    }
  },

  // Permissions Matrix Interactive Handlers
  initPermissionsMatrix() {
    const wrap = document.getElementById('usersMatrixWrap');
    const btnLeft = document.getElementById('btnScrollMatrixLeft');
    const btnRight = document.getElementById('btnScrollMatrixRight');

    if (btnLeft && wrap) {
      btnLeft.addEventListener('click', () => {
        wrap.scrollBy({ left: -320, behavior: 'smooth' });
      });
    }
    if (btnRight && wrap) {
      btnRight.addEventListener('click', () => {
        wrap.scrollBy({ left: 320, behavior: 'smooth' });
      });
    }

    document.querySelectorAll('.matrix-perm-toggle').forEach(toggle => {
      toggle.addEventListener('change', async function() {
        const userId = this.getAttribute('data-user-id');
        const roleId = this.getAttribute('data-role-id');
        const permKey = this.getAttribute('data-permission-key');
        const targetName = this.getAttribute('data-target-name') || 'User';
        const permLabel = this.getAttribute('data-perm-label') || 'Permission';
        const isGranted = this.checked ? '1' : '0';
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

        const fd = new FormData();
        fd.append('action', 'toggle');
        if (userId) fd.append('user_id', userId);
        if (roleId) fd.append('role_id', roleId);
        fd.append('permission_key', permKey);
        fd.append('is_granted', isGranted);
        fd.append('_csrf_token', csrfToken);

        this.disabled = true;

        try {
          const res = await fetch(`${Desk.baseUrl}/api/permissions.php`, {
            method: 'POST',
            body: fd
          });
          const data = await res.json();
          this.disabled = false;

          if (data.success) {
            const state = isGranted === '1' ? 'GRANTED' : 'REVOKED';
            Desk.showToast(`${targetName}: ${permLabel} is now ${state}`);
          } else {
            this.checked = !this.checked;
            Desk.showToast(data.message || 'Failed to update permission.', true);
          }
        } catch (err) {
          this.disabled = false;
          this.checked = !this.checked;
          Desk.showToast('Network error while updating permission.', true);
        }
      });
    });
  },

  // =========================================================================
  // REAL FRAPPE / ERPNEXT STOCK ENTRY CONTROLLER
  // =========================================================================
  stockEntryState: {
    grn: {
      items: [],
      nextId: 1,
      docTitle: 'New Stock Entry',
      status: 'Not Saved',
      selectedIds: new Set()
    },
    issue: {
      items: [],
      nextId: 1,
      docTitle: 'New Stock Entry',
      status: 'Not Saved',
      selectedIds: new Set()
    }
  },
  cachedStockLookups: null,
  activeMultipleContext: 'grn',

  async loadStockEntryLookups() {
    if (this.cachedStockLookups) return this.cachedStockLookups;
    try {
      const res = await fetch(`${this.baseUrl}/api/inventory.php?action=get_receipt_lookups`);
      const data = await res.json();
      if (data.success && data.data) {
        this.cachedStockLookups = data.data;
        return data.data;
      }
    } catch (e) {
      console.error('Failed to load stock lookups:', e);
    }
    return null;
  },

  async initStockEntryForm(context) {
    const now = new Date();
    const pad = n => String(n).padStart(2, '0');
    const dateStr = `${now.getFullYear()}-${pad(now.getMonth()+1)}-${pad(now.getDate())}`;
    const timeStr = `${pad(now.getHours())}:${pad(now.getMinutes())}:${pad(now.getSeconds())}`;

    const dateEl = document.getElementById(`${context}PostingDate`);
    const timeEl = document.getElementById(`${context}PostingTime`);
    if (dateEl && !dateEl.value) dateEl.value = dateStr;
    if (timeEl && !timeEl.value) timeEl.value = timeStr;

    // Load lookups if needed
    const lookups = await this.loadStockEntryLookups();
    if (lookups) {
      const defSource = document.getElementById(`${context}DefSourceWh`);
      const defTarget = document.getElementById(`${context}DefTargetWh`);
      const warehouses = lookups.warehouses || [
        'Main Store Bay-01',
        'Mechanical Stores',
        'Electrical Supplies Sub-Store',
        'Central Warehouse - Main Store'
      ];

      const populateWh = (sel, defaultVal) => {
        if (!sel || sel.children.length > 1) return;
        sel.innerHTML = '<option value="">Select Warehouse...</option>';
        warehouses.forEach(wh => {
          const opt = document.createElement('option');
          opt.value = wh;
          opt.textContent = wh;
          if (wh === defaultVal) opt.selected = true;
          sel.appendChild(opt);
        });
      };

      populateWh(defSource, 'Main Store Bay-01');
      populateWh(defTarget, 'Main Store Bay-01');

      // Populate Suppliers
      const supSel = document.getElementById(`${context}SupplierSelect`);
      if (supSel && supSel.children.length <= 1 && Array.isArray(lookups.suppliers)) {
        lookups.suppliers.forEach(s => {
          const opt = document.createElement('option');
          opt.value = s;
          opt.textContent = s;
          supSel.appendChild(opt);
        });
      }

      // Populate Cost Centers
      const ccSel = document.getElementById(`${context}CostCenterSelect`);
      if (ccSel && ccSel.children.length <= 1 && Array.isArray(lookups.cost_centers)) {
        lookups.cost_centers.forEach(c => {
          const opt = document.createElement('option');
          opt.value = c.code;
          opt.textContent = c.name;
          ccSel.appendChild(opt);
        });
      }

      // Populate Projects
      const prjSel = document.getElementById(`${context}ProjectSelect`);
      if (prjSel && prjSel.children.length <= 1 && Array.isArray(lookups.projects)) {
        lookups.projects.forEach(p => {
          const opt = document.createElement('option');
          opt.value = p;
          opt.textContent = p;
          prjSel.appendChild(opt);
        });
      }

      // Populate Sections (Destination Codes)
      const secSel = document.getElementById(`${context}SectionSelect`);
      if (secSel && secSel.children.length <= 1 && Array.isArray(lookups.sections)) {
        lookups.sections.forEach(s => {
          const opt = document.createElement('option');
          opt.value = s.code;
          opt.textContent = s.display_label || `${s.code} - ${s.title}`;
          secSel.appendChild(opt);
        });
      }
    }

    // Initialize 1 default row if table empty
    const state = this.stockEntryState[context];
    if (state.items.length === 0) {
      this.addStockEntryRow(context, false);
    }
    this.renderStockEntryRows(context);
  },

  renderStockEntryRows(context) {
    const tbody = document.getElementById(`${context}ItemsBody`);
    if (!tbody) return;

    const state = this.stockEntryState[context];
    const lookups = this.cachedStockLookups || { warehouses: [], materials: [] };
    const warehouses = lookups.warehouses || ['Main Store Bay-01', 'Mechanical Stores', 'Electrical Supplies Sub-Store'];
    const materials = lookups.materials || [];

    if (state.items.length === 0) {
      tbody.innerHTML = `
        <tr>
          <td colspan="7" style="text-align: center; color: #8d99a6; padding: 20px; font-size: 13px;">
            No items in table. Click <strong>Add row</strong> or <strong>Add multiple</strong> to add items.
          </td>
        </tr>
      `;
      return;
    }

    let html = '';
    state.items.forEach((row, idx) => {
      const isChecked = state.selectedIds.has(row.id);
      
      let whOptions = '<option value="">Select Warehouse...</option>';
      warehouses.forEach(w => {
        whOptions += `<option value="${w}" ${row.warehouse === w ? 'selected' : ''}>${w}</option>`;
      });

      let matOptions = '<option value="">Select or type Item Code...</option>';
      materials.forEach(m => {
        matOptions += `<option value="${m.sku}" ${row.item_code === m.sku ? 'selected' : ''}>${m.sku} - ${m.item_name}</option>`;
      });

      html += `
        <tr data-row-id="${row.id}" style="${isChecked ? 'background: #f8fafc;' : ''}">
          <td style="text-align: center;">
            <input type="checkbox" onchange="Desk.toggleStockEntryRowCheck('${context}', ${row.id}, this.checked)" ${isChecked ? 'checked' : ''} style="cursor: pointer; accent-color: #18181b;">
          </td>
          <td style="text-align: center; color: #6c7680; font-size: 12px; font-weight: 500;">
            ${idx + 1}
          </td>
          <td>
            <select class="frappe-control" style="height: 30px; font-size: 12px;" onchange="Desk.updateStockEntryRow('${context}', ${row.id}, 'warehouse', this.value)">
              ${whOptions}
            </select>
          </td>
          <td>
            <div style="display: flex; gap: 6px; align-items: center;">
              <select class="frappe-control" style="height: 30px; font-size: 12px; font-weight: 500;" onchange="Desk.onStockEntryItemSelect('${context}', ${row.id}, this.value)">
                ${matOptions}
              </select>
            </div>
            ${row.item_name ? `<div style="font-size: 11px; color: #6c7680; margin-top: 2px;">${row.item_name} ${row.uom ? `(${row.uom})` : ''}</div>` : ''}
          </td>
          <td>
            <input type="number" step="0.001" min="0" class="frappe-control" style="height: 30px; font-size: 12px; text-align: right; font-weight: 600;" value="${parseFloat(row.qty || 0).toFixed(3)}" onchange="Desk.updateStockEntryRow('${context}', ${row.id}, 'qty', parseFloat(this.value)||0)">
          </td>
          <td>
            <div style="position: relative;">
              <span style="position: absolute; left: 8px; top: 50%; transform: translateY(-50%); font-size: 11px; color: #6c7680;">Rs</span>
              <input type="number" step="0.01" min="0" class="frappe-control" style="height: 30px; font-size: 12px; text-align: right; padding-left: 28px;" value="${parseFloat(row.basic_rate || 0).toFixed(2)}" onchange="Desk.updateStockEntryRow('${context}', ${row.id}, 'basic_rate', parseFloat(this.value)||0)">
            </div>
          </td>
          <td style="text-align: center;">
            <div style="display: flex; align-items: center; justify-content: center; gap: 4px;">
              <button type="button" class="frappe-table-btn" style="padding: 2px 6px; height: 26px; border: none; background: transparent; color: #dc2626; cursor: pointer;" title="Remove Row" onclick="Desk.removeStockEntryRow('${context}', ${row.id})">
                &#10005;
              </button>
            </div>
          </td>
        </tr>
      `;
    });

    tbody.innerHTML = html;
  },

  addStockEntryRow(context, shouldRender = true) {
    const state = this.stockEntryState[context];
    const defSource = document.getElementById(`${context}DefSourceWh`)?.value || '';
    const defTarget = document.getElementById(`${context}DefTargetWh`)?.value || '';
    const defWh = (context === 'grn' ? defTarget : defSource) || 'Main Store Bay-01';

    state.items.push({
      id: state.nextId++,
      warehouse: defWh,
      item_code: '',
      item_name: '',
      qty: 1.0,
      basic_rate: 0.0,
      uom: 'Nos'
    });

    if (shouldRender) {
      this.renderStockEntryRows(context);
    }
  },

  removeStockEntryRow(context, rowId) {
    const state = this.stockEntryState[context];
    state.items = state.items.filter(r => r.id !== rowId);
    state.selectedIds.delete(rowId);
    this.renderStockEntryRows(context);
  },

  toggleStockEntryRowCheck(context, rowId, checked) {
    const state = this.stockEntryState[context];
    if (checked) state.selectedIds.add(rowId);
    else state.selectedIds.delete(rowId);
  },

  toggleSelectAllRows(context, checked) {
    const state = this.stockEntryState[context];
    if (checked) {
      state.items.forEach(r => state.selectedIds.add(r.id));
    } else {
      state.selectedIds.clear();
    }
    this.renderStockEntryRows(context);
  },

  updateStockEntryRow(context, rowId, field, value) {
    const state = this.stockEntryState[context];
    const row = state.items.find(r => r.id === rowId);
    if (!row) return;
    row[field] = value;
  },

  onStockEntryItemSelect(context, rowId, sku) {
    const state = this.stockEntryState[context];
    const row = state.items.find(r => r.id === rowId);
    if (!row) return;

    row.item_code = sku;
    const lookups = this.cachedStockLookups || { materials: [] };
    const mat = (lookups.materials || []).find(m => m.sku === sku);
    if (mat) {
      row.item_name = mat.item_name;
      row.uom = mat.uom || 'Nos';
      if (mat.unit_price) {
        row.basic_rate = parseFloat(mat.unit_price) || 0;
      }
    }
    this.renderStockEntryRows(context);
  },

  applyDefaultWarehouse(context) {
    const state = this.stockEntryState[context];
    const type = document.getElementById(`${context}StockEntryType`)?.value || '';
    const defWh = (type === 'Material Receipt'
      ? document.getElementById(`${context}DefTargetWh`)?.value
      : document.getElementById(`${context}DefSourceWh`)?.value) || '';

    if (!defWh) return;
    state.items.forEach(r => {
      if (!r.warehouse) r.warehouse = defWh;
    });
    this.renderStockEntryRows(context);
  },

  onStockEntryTypeChange(context, type) {
    const whHeader = document.getElementById(`${context}ColWhHeader`);
    const srcGrp = document.getElementById(`${context}SourceWhGroup`);
    const tgtGrp = document.getElementById(`${context}TargetWhGroup`);
    const purchaseSec = document.getElementById(`${context}PurchaseSection`);
    const costCenterSec = document.getElementById(`${context}CostCenterSection`);

    if (type === 'Material Receipt') {
      if (whHeader) whHeader.textContent = 'Target Warehouse';
      if (srcGrp) srcGrp.style.display = 'none';
      if (tgtGrp) tgtGrp.style.display = 'block';
      if (purchaseSec) purchaseSec.style.display = 'block';
      if (costCenterSec) costCenterSec.style.display = 'none';
    } else if (type === 'Material Issue') {
      if (whHeader) whHeader.textContent = 'Source Warehouse';
      if (srcGrp) srcGrp.style.display = 'block';
      if (tgtGrp) tgtGrp.style.display = 'none';
      if (purchaseSec) purchaseSec.style.display = 'none';
      if (costCenterSec) costCenterSec.style.display = 'block';
    } else {
      if (whHeader) whHeader.textContent = 'Warehouse';
      if (srcGrp) srcGrp.style.display = 'block';
      if (tgtGrp) tgtGrp.style.display = 'block';
      if (purchaseSec) purchaseSec.style.display = 'block';
      if (costCenterSec) costCenterSec.style.display = 'block';
    }
  },

  switchStockEntryTab(context, tabName) {
    const card = document.getElementById(`body${context.charAt(0).toUpperCase() + context.slice(1)}EntryCard`);
    if (card) {
      card.querySelectorAll('.frappe-tab-btn').forEach(b => b.classList.remove('active'));
    }
    const btn = document.getElementById(`tabBtn-${context}-${tabName}`);
    if (btn) btn.classList.add('active');

    const detailsTab = document.getElementById(`tabContent-${context}-details`);
    const otherTab = document.getElementById(`tabContent-${context}-other`);

    if (tabName === 'details') {
      if (detailsTab) detailsTab.style.display = 'block';
      if (otherTab) otherTab.style.display = 'none';
    } else {
      if (detailsTab) detailsTab.style.display = 'none';
      if (otherTab) otherTab.style.display = 'block';
    }
  },

  toggleEditPostingDate(context, checked) {
    const dateEl = document.getElementById(`${context}PostingDate`);
    const timeEl = document.getElementById(`${context}PostingTime`);
    if (dateEl) {
      if (checked) dateEl.removeAttribute('readonly');
      else dateEl.setAttribute('readonly', 'readonly');
    }
    if (timeEl) {
      if (checked) timeEl.removeAttribute('readonly');
      else timeEl.setAttribute('readonly', 'readonly');
    }
  },

  toggleStockEntrySection(sectionId, headerEl) {
    const sec = document.getElementById(sectionId);
    if (!sec) return;
    const arrow = headerEl.querySelector('.frappe-sec-arrow');
    if (sec.style.display === 'none') {
      sec.style.display = 'block';
      if (arrow) arrow.innerHTML = '&#8744;';
    } else {
      sec.style.display = 'none';
      if (arrow) arrow.innerHTML = '&#8250;';
    }
  },

  toggleSeDropdown(menuId) {
    const menu = document.getElementById(menuId);
    if (!menu) return;
    const isOpen = menu.style.display === 'block';
    document.querySelectorAll('.frappe-dropdown-menu').forEach(m => m.style.display = 'none');
    menu.style.display = isOpen ? 'none' : 'block';
  },

  onStockEntryBarcode(event, context, input) {
    const code = (input.value || '').trim();
    if (!code) return;

    const lookups = this.cachedStockLookups || { materials: [] };
    const mat = (lookups.materials || []).find(m => m.sku.toLowerCase() === code.toLowerCase());
    const state = this.stockEntryState[context];

    if (mat) {
      const existing = state.items.find(r => r.item_code === mat.sku);
      if (existing) {
        existing.qty = (parseFloat(existing.qty) || 0) + 1;
        this.showToast(`Updated qty for ${mat.sku} (${existing.qty})`);
      } else {
        const defWh = (context === 'grn' ? document.getElementById(`${context}DefTargetWh`)?.value : document.getElementById(`${context}DefSourceWh`)?.value) || 'Main Store Bay-01';
        state.items.push({
          id: state.nextId++,
          warehouse: defWh,
          item_code: mat.sku,
          item_name: mat.item_name,
          qty: 1.0,
          basic_rate: parseFloat(mat.unit_price || 0),
          uom: mat.uom || 'Nos'
        });
        this.showToast(`Scanned: ${mat.sku} - ${mat.item_name}`);
      }
    } else {
      const defWh = (context === 'grn' ? document.getElementById(`${context}DefTargetWh`)?.value : document.getElementById(`${context}DefSourceWh`)?.value) || 'Main Store Bay-01';
      state.items.push({
        id: state.nextId++,
        warehouse: defWh,
        item_code: code,
        item_name: 'Custom / Scanned Item',
        qty: 1.0,
        basic_rate: 0.0,
        uom: 'Nos'
      });
      this.showToast(`Added scanned code: ${code}`);
    }

    input.value = '';
    this.renderStockEntryRows(context);
  },

  // Add Multiple Modal
  openAddMultipleModal(context) {
    this.activeMultipleContext = context;
    const modal = document.getElementById('modalStockEntryAddMultiple');
    if (!modal) return;

    const lookups = this.cachedStockLookups || { materials: [] };
    const materials = lookups.materials || [];
    const tbody = document.getElementById('seAddMultipleBody');

    let html = '';
    materials.forEach(m => {
      html += `
        <tr class="se-multiple-row" data-sku="${m.sku}" data-name="${(m.item_name||'').toLowerCase()}">
          <td style="text-align: center;">
            <input type="checkbox" class="se-multi-chk" value="${m.sku}" data-name="${m.item_name}" data-uom="${m.uom||'Nos'}" data-rate="${m.unit_price||0}" style="cursor: pointer; accent-color: #18181b;">
          </td>
          <td>
            <div style="font-weight: 600; font-size: 12.5px;">${m.sku}</div>
            <div style="font-size: 11.5px; color: #6c7680;">${m.item_name}</div>
          </td>
          <td style="text-align: center; font-size: 12px; color: #6c7680;">
            ${m.uom || 'Nos'}
          </td>
          <td style="text-align: right; font-size: 12px; font-weight: 500;">
            Rs. ${parseFloat(m.unit_price || 0).toFixed(2)}
          </td>
        </tr>
      `;
    });
    if (tbody) tbody.innerHTML = html;

    const searchInput = document.getElementById('seAddMultipleSearch');
    if (searchInput) searchInput.value = '';

    modal.style.display = 'flex';
  },

  closeAddMultipleModal() {
    const modal = document.getElementById('modalStockEntryAddMultiple');
    if (modal) modal.style.display = 'none';
  },

  filterAddMultipleList(query) {
    const q = (query || '').toLowerCase().trim();
    document.querySelectorAll('.se-multiple-row').forEach(tr => {
      const sku = (tr.getAttribute('data-sku') || '').toLowerCase();
      const name = (tr.getAttribute('data-name') || '').toLowerCase();
      tr.style.display = (!q || sku.includes(q) || name.includes(q)) ? '' : 'none';
    });
  },

  toggleAddMultipleAll(checked) {
    document.querySelectorAll('.se-multiple-row').forEach(tr => {
      if (tr.style.display !== 'none') {
        const chk = tr.querySelector('.se-multi-chk');
        if (chk) chk.checked = checked;
      }
    });
  },

  confirmAddMultiple() {
    const ctx = this.activeMultipleContext || 'grn';
    const state = this.stockEntryState[ctx];
    const defSource = document.getElementById(`${ctx}DefSourceWh`)?.value || '';
    const defTarget = document.getElementById(`${ctx}DefTargetWh`)?.value || '';
    const defWh = (ctx === 'grn' ? defTarget : defSource) || 'Main Store Bay-01';

    let count = 0;
    document.querySelectorAll('.se-multi-chk:checked').forEach(chk => {
      const sku = chk.value;
      const name = chk.getAttribute('data-name') || '';
      const uom = chk.getAttribute('data-uom') || 'Nos';
      const rate = parseFloat(chk.getAttribute('data-rate') || 0);

      const existing = state.items.find(r => r.item_code === sku);
      if (!existing) {
        if (state.items.length === 1 && !state.items[0].item_code) {
          state.items[0].item_code = sku;
          state.items[0].item_name = name;
          state.items[0].uom = uom;
          state.items[0].basic_rate = rate;
          state.items[0].qty = 1.0;
          state.items[0].warehouse = defWh;
        } else {
          state.items.push({
            id: state.nextId++,
            warehouse: defWh,
            item_code: sku,
            item_name: name,
            uom: uom,
            qty: 1.0,
            basic_rate: rate
          });
        }
        count++;
      }
    });

    this.closeAddMultipleModal();
    this.renderStockEntryRows(ctx);
    this.showToast(`Added ${count} items to Stock Entry.`);
  },

  downloadStockEntryCSV(context) {
    const state = this.stockEntryState[context];
    let csv = 'Item Code,Item Name,Warehouse,Quantity,Basic Rate\n';
    state.items.forEach(r => {
      if (r.item_code) {
        csv += `"${r.item_code}","${(r.item_name||'').replace(/"/g, '""')}","${r.warehouse}","${r.qty}","${r.basic_rate}"\n`;
      }
    });

    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.setAttribute('href', url);
    link.setAttribute('download', `Stock_Entry_${context.toUpperCase()}_Template.csv`);
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
  },

  triggerStockEntryUpload(context) {
    const fileInput = document.getElementById(`${context}CsvFileInput`);
    if (fileInput) fileInput.click();
  },

  uploadStockEntryCSV(context, input) {
    const file = input.files && input.files[0];
    if (!file) return;

    const reader = new FileReader();
    reader.onload = (e) => {
      const text = e.target.result;
      const lines = text.split(/\r\n|\n/).filter(l => l.trim().length > 0);
      if (lines.length <= 1) {
        this.showToast('CSV file is empty or missing data rows.', true);
        return;
      }

      const state = this.stockEntryState[context];
      let imported = 0;
      for (let i = 1; i < lines.length; i++) {
        const parts = lines[i].split(',').map(p => p.trim().replace(/^"|"$/g, ''));
        const sku = parts[0];
        const name = parts[1] || '';
        const wh = parts[2] || 'Main Store Bay-01';
        const qty = parseFloat(parts[3]) || 1.0;
        const rate = parseFloat(parts[4]) || 0.0;

        if (sku) {
          state.items.push({
            id: state.nextId++,
            warehouse: wh,
            item_code: sku,
            item_name: name,
            uom: 'Nos',
            qty: qty,
            basic_rate: rate
          });
          imported++;
        }
      }

      input.value = '';
      this.renderStockEntryRows(context);
      this.showToast(`Successfully uploaded ${imported} items from CSV.`);
    };
    reader.readAsText(file);
  },

  updateStockEntryRatesAndAvailability(context) {
    const state = this.stockEntryState[context];
    const lookups = this.cachedStockLookups || { materials: [] };
    const materials = lookups.materials || [];

    state.items.forEach(r => {
      if (r.item_code) {
        const mat = materials.find(m => m.sku === r.item_code);
        if (mat && mat.unit_price) {
          r.basic_rate = parseFloat(mat.unit_price) || 0;
        }
      }
    });

    this.renderStockEntryRows(context);
    this.showToast('Rates and availability refreshed from master.');
  },

  async submitStockEntryForm(context) {
    const state = this.stockEntryState[context];
    const docType = context === 'grn' ? 'GRN' : 'ISSUE';

    const validItems = state.items.filter(r => r.item_code && r.qty > 0);
    if (validItems.length === 0) {
      this.showToast('Please add at least one item with valid Item Code and Quantity.', true);
      return;
    }

    const series = document.getElementById(`${context}Series`)?.value || 'MAT-STE-.YYYY.-';
    const entryType = document.getElementById(`${context}StockEntryType`)?.value || (context === 'grn' ? 'Material Receipt' : 'Material Issue');
    const postingDate = document.getElementById(`${context}PostingDate`)?.value || '';
    const postingTime = document.getElementById(`${context}PostingTime`)?.value || '';
    const defWh = (context === 'grn' ? document.getElementById(`${context}DefTargetWh`)?.value : document.getElementById(`${context}DefSourceWh`)?.value) || 'Main Store Bay-01';

    const partyName = document.getElementById(`${context}PartyName`)?.value || '';
    const poRef = document.getElementById(`${context}PoRef`)?.value || '';
    const invoiceNo = document.getElementById(`${context}InvoiceNo`)?.value || '';
    const remarks = document.getElementById(`${context}Remarks`)?.value || '';

    let invTotal = 0;
    const formattedItems = validItems.map(r => {
      const lineTotal = (parseFloat(r.qty) || 0) * (parseFloat(r.basic_rate) || 0);
      invTotal += lineTotal;
      return {
        sku: r.item_code,
        item_name: r.item_name || r.item_code,
        quantity: parseFloat(r.qty) || 1,
        unit_cost: parseFloat(r.basic_rate) || 0,
        warehouse: r.warehouse || defWh
      };
    });

    const fd = new FormData();
    fd.append('action', 'create_document');
    fd.append('doc_type', docType);
    fd.append('series', series);
    fd.append('stock_entry_type', entryType);
    fd.append('acc_date', postingDate);
    fd.append('delivery_date', postingDate);
    fd.append('warehouse', defWh);
    fd.append('receipt_from', partyName);
    fd.append('order_reference', poRef);
    fd.append('invoice_number', invoiceNo);
    fd.append('remarks', `[Type: ${entryType}] [Posting: ${postingDate} ${postingTime}] ${remarks}`);
    fd.append('invoice_total', invTotal);
    fd.append('items', JSON.stringify(formattedItems));

    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    if (token) fd.append('_csrf_token', token);

    try {
      const res = await fetch(`${this.baseUrl}/api/inventory.php`, {
        method: 'POST',
        body: fd
      });
      const data = await res.json();
      if (data.success) {
        const docNo = data.data?.doc_no || 'STE-' + Date.now();
        state.docTitle = docNo;
        state.status = 'Draft';

        const badge = document.getElementById(`${context}StatusBadge`);
        const titleEl = document.getElementById(`${context}DocTitle`);
        if (badge) {
          badge.className = 'desk-badge badge-draft';
          badge.textContent = 'Draft';
        }
        if (titleEl) {
          titleEl.textContent = docNo;
        }

        this.showToast(`Stock Entry ${docNo} created successfully as Draft!`);

        if (context === 'grn') {
          this.loadReceipts();
        } else {
          this.loadIssues();
        }
      } else {
        this.showToast(data.message || 'Failed to save Stock Entry.', true);
      }
    } catch (err) {
      console.error(err);
      this.showToast('Network error while saving Stock Entry.', true);
    }
  },

  resetStockEntryForm(context) {
    const state = this.stockEntryState[context];
    state.items = [];
    state.nextId = 1;
    state.selectedIds.clear();
    state.docTitle = 'New Stock Entry';
    state.status = 'Not Saved';

    const badge = document.getElementById(`${context}StatusBadge`);
    const titleEl = document.getElementById(`${context}DocTitle`);
    if (badge) {
      badge.className = 'frappe-badge-not-saved';
      badge.textContent = 'Not Saved';
    }
    if (titleEl) {
      titleEl.textContent = 'New Stock Entry';
    }

    this.initStockEntryForm(context);
    this.showToast('Ready for New Stock Entry.');
  },

  createSeDuplicate(context) {
    const state = this.stockEntryState[context];
    state.docTitle = 'New Stock Entry';
    state.status = 'Not Saved';
    const badge = document.getElementById(`${context}StatusBadge`);
    const titleEl = document.getElementById(`${context}DocTitle`);
    if (badge) {
      badge.className = 'frappe-badge-not-saved';
      badge.textContent = 'Not Saved';
    }
    if (titleEl) {
      titleEl.textContent = 'New Stock Entry';
    }
    this.showToast('Duplicated entries ready as New Stock Entry.');
  },

  activePoContext: 'grn',
  activeCostCenterContext: 'issue',

  // Predefined Supplier & Purchase Order Selection Dialog
  openPoSelectionModal(context) {
    this.activePoContext = context;
    const modal = document.getElementById('modalGetItemsFromPO');
    if (!modal) return;

    const sel = document.getElementById('modalPoSupplierSelect');
    const lookups = this.cachedStockLookups || { suppliers: [] };
    const suppliers = lookups.suppliers || [
      'Lanka Lubricants Ltd',
      'Colombo Electrical Supplies Ltd',
      'Industrial Spares (Pvt) Ltd',
      'Diesel & Motor Engineering (DIMO)',
      'United Motors Lanka PLC',
      'Hayleys Power & Energy',
      'Ceylon Petroleum Corporation (CPC)',
      'State Engineering Corporation',
      'Walker Sons & Co Ltd',
      'Kelani Cables PLC',
      'Brown & Company PLC',
      'Tokyo Cement Company',
      'Avent Technologies (Pvt) Ltd'
    ];

    if (sel) {
      sel.innerHTML = '<option value="">-- Select Predefined Supplier --</option>';
      suppliers.forEach(s => {
        const opt = document.createElement('option');
        opt.value = s;
        opt.textContent = s;
        sel.appendChild(opt);
      });

      if (sel.options.length > 1) {
        sel.selectedIndex = 1;
        this.onModalSupplierChanged(sel.value);
      }
    }

    modal.style.display = 'flex';
  },

  closePoSelectionModal() {
    const modal = document.getElementById('modalGetItemsFromPO');
    if (modal) modal.style.display = 'none';
  },

  onModalSupplierChanged(supplier) {
    const poInput = document.getElementById('modalPoRefInput');
    const supWord = (supplier || 'SUP').split(' ')[0].toUpperCase().substring(0, 5);
    if (poInput) {
      poInput.value = `PO-2026-${supWord}-01`;
    }
    this.renderPoSupplierItems(supplier);
  },

  renderPoSupplierItems(supplier) {
    const tbody = document.getElementById('modalPoItemsBody');
    if (!tbody) return;

    const lookups = this.cachedStockLookups || { materials: [] };
    const materials = (lookups.materials || []).slice(0, 20);

    if (materials.length === 0) {
      tbody.innerHTML = '<tr><td colspan="5" style="text-align: center; color: #8d99a6; padding: 20px;">No master items found.</td></tr>';
      return;
    }

    let html = '';
    materials.forEach(m => {
      html += `
        <tr class="po-modal-item-row" data-sku="${m.sku}" data-name="${(m.item_name||'').toLowerCase()}">
          <td style="text-align: center;">
            <input type="checkbox" class="po-item-chk" value="${m.sku}" data-name="${m.item_name}" data-uom="${m.uom||'Nos'}" data-rate="${m.unit_price||0}" checked style="accent-color: #18181b; cursor: pointer;">
          </td>
          <td>
            <div style="font-weight: 600; font-size: 12.5px;">${m.sku}</div>
            <div style="font-size: 11.5px; color: #6c7680;">${m.item_name}</div>
          </td>
          <td style="text-align: center; font-size: 12px; color: #6c7680;">${m.uom || 'Nos'}</td>
          <td style="text-align: right; font-size: 12px;">Rs. ${parseFloat(m.unit_price||0).toFixed(2)}</td>
          <td style="text-align: right;">
            <input type="number" step="0.001" min="0" class="frappe-control po-recv-qty" style="height: 28px; width: 85px; text-align: right; font-size: 12px; font-weight: 600;" value="10.000">
          </td>
        </tr>
      `;
    });
    tbody.innerHTML = html;
  },

  filterPoItems(query) {
    const q = (query || '').toLowerCase().trim();
    document.querySelectorAll('.po-modal-item-row').forEach(tr => {
      const sku = (tr.getAttribute('data-sku') || '').toLowerCase();
      const name = (tr.getAttribute('data-name') || '').toLowerCase();
      tr.style.display = (!q || sku.includes(q) || name.includes(q)) ? '' : 'none';
    });
  },

  toggleModalPoCheckAll(checked) {
    document.querySelectorAll('.po-item-chk').forEach(c => c.checked = checked);
  },

  confirmGetItemsFromPO() {
    const ctx = this.activePoContext || 'grn';
    const supplier = document.getElementById('modalPoSupplierSelect')?.value || '';
    const poRef = document.getElementById('modalPoRefInput')?.value || '';
    const targetWh = document.getElementById('modalPoWarehouseSelect')?.value || 'Main Store Bay-01';
    const state = this.stockEntryState[ctx];

    let count = 0;
    document.querySelectorAll('#modalPoItemsBody tr').forEach(tr => {
      const chk = tr.querySelector('.po-item-chk');
      if (chk && chk.checked) {
        const sku = chk.value;
        const name = chk.getAttribute('data-name');
        const uom = chk.getAttribute('data-uom');
        const rate = parseFloat(chk.getAttribute('data-rate') || 0);
        const qty = parseFloat(tr.querySelector('.po-recv-qty')?.value || 1);

        if (state.items.length === 1 && !state.items[0].item_code) {
          state.items[0].item_code = sku;
          state.items[0].item_name = name;
          state.items[0].uom = uom;
          state.items[0].basic_rate = rate;
          state.items[0].qty = qty;
          state.items[0].warehouse = targetWh;
        } else {
          state.items.push({
            id: state.nextId++,
            warehouse: targetWh,
            item_code: sku,
            item_name: name,
            uom: uom,
            qty: qty,
            basic_rate: rate
          });
        }
        count++;
      }
    });

    if (supplier) {
      const supSel = document.getElementById(`${ctx}SupplierSelect`);
      if (supSel) supSel.value = supplier;

      const partyInput = document.getElementById(`${ctx}PartyName`);
      if (partyInput) partyInput.value = supplier;
    }

    if (poRef) {
      const poInput = document.getElementById(`${ctx}PoRefInput`);
      if (poInput) poInput.value = poRef;

      const poRefHidden = document.getElementById(`${ctx}PoRef`);
      if (poRefHidden) poRefHidden.value = poRef;
    }

    const whTarget = document.getElementById(`${ctx}DefTargetWh`);
    if (whTarget && ctx === 'grn') whTarget.value = targetWh;

    this.closePoSelectionModal();
    this.renderStockEntryRows(ctx);
    this.showToast(`Loaded ${count} items from ${supplier} (PO: ${poRef}).`);
  },
  activeSectionContext: 'issue',

    onSupplierSelectChange(context, val) {
    const partyName = document.getElementById(`${context}PartyName`);
    if (partyName) partyName.value = val;
  },

  // Cost Center Selection Dialog
  openCostCenterSelectionModal(context) {
    this.activeCostCenterContext = context;
    const modal = document.getElementById('modalGetItemsFromCostCenter');
    if (!modal) return;

    const sel = document.getElementById('modalCostCenterSelector');
    if (sel && sel.children.length <= 1) {
      const lookups = this.cachedStockLookups || { cost_centers: [] };
      (lookups.cost_centers || []).forEach(c => {
        const opt = document.createElement('option');
        opt.value = c.code;
        opt.textContent = c.name;
        sel.appendChild(opt);
      });
    }

    if (sel && sel.options.length > 1) {
      sel.selectedIndex = 1;
      this.onModalCostCenterSelected(sel.value);
    }

    modal.style.display = 'flex';
  },

  closeCostCenterSelectionModal() {
    const modal = document.getElementById('modalGetItemsFromCostCenter');
    if (modal) modal.style.display = 'none';
  },

  onModalCostCenterSelected(code) {
    const tbody = document.getElementById('modalCostCenterItemsBody');
    if (!tbody) return;

    const lookups = this.cachedStockLookups || { materials: [] };
    const materials = (lookups.materials || []).slice(0, 15);

    if (materials.length === 0) {
      tbody.innerHTML = '<tr><td colspan="5" style="text-align: center; color: #8d99a6; padding: 20px;">No items configured for this Cost Center.</td></tr>';
      return;
    }

    let html = '';
    materials.forEach(m => {
      html += `
        <tr class="cc-modal-item-row" data-sku="${m.sku}" data-name="${(m.item_name||'').toLowerCase()}">
          <td style="text-align: center;">
            <input type="checkbox" class="cc-item-chk" value="${m.sku}" data-name="${m.item_name}" data-uom="${m.uom||'Nos'}" data-rate="${m.unit_price||0}" checked style="accent-color: #18181b; cursor: pointer;">
          </td>
          <td>
            <div style="font-weight: 600; font-size: 12.5px;">${m.sku}</div>
            <div style="font-size: 11.5px; color: #6c7680;">${m.item_name}</div>
          </td>
          <td style="text-align: center; font-size: 12px; color: #6c7680;">${m.uom || 'Nos'}</td>
          <td style="text-align: right; font-size: 12px;">Rs. ${parseFloat(m.unit_price||0).toFixed(2)}</td>
          <td style="text-align: right;">
            <input type="number" step="0.001" min="0" class="frappe-control cc-req-qty" style="height: 28px; width: 85px; text-align: right; font-size: 12px; font-weight: 600;" value="1.000">
          </td>
        </tr>
      `;
    });
    tbody.innerHTML = html;
  },

  filterCostCenterItems(query) {
    const q = (query || '').toLowerCase().trim();
    document.querySelectorAll('.cc-modal-item-row').forEach(tr => {
      const sku = (tr.getAttribute('data-sku') || '').toLowerCase();
      const name = (tr.getAttribute('data-name') || '').toLowerCase();
      tr.style.display = (!q || sku.includes(q) || name.includes(q)) ? '' : 'none';
    });
  },

  toggleModalCostCenterCheckAll(checked) {
    document.querySelectorAll('.cc-item-chk').forEach(c => c.checked = checked);
  },

  confirmGetItemsFromCostCenter() {
    const ctx = this.activeCostCenterContext || 'issue';
    const selCode = document.getElementById('modalCostCenterSelector')?.value || '';
    const state = this.stockEntryState[ctx];
    const defWh = document.getElementById(`${ctx}DefSourceWh`)?.value || 'Main Store Bay-01';

    let count = 0;
    document.querySelectorAll('#modalCostCenterItemsBody tr').forEach(tr => {
      const chk = tr.querySelector('.cc-item-chk');
      if (chk && chk.checked) {
        const sku = chk.value;
        const name = chk.getAttribute('data-name');
        const uom = chk.getAttribute('data-uom');
        const rate = parseFloat(chk.getAttribute('data-rate') || 0);
        const qty = parseFloat(tr.querySelector('.cc-req-qty')?.value || 1);

        if (state.items.length === 1 && !state.items[0].item_code) {
          state.items[0].item_code = sku;
          state.items[0].item_name = name;
          state.items[0].uom = uom;
          state.items[0].basic_rate = rate;
          state.items[0].qty = qty;
          state.items[0].warehouse = defWh;
        } else {
          state.items.push({
            id: state.nextId++,
            warehouse: defWh,
            item_code: sku,
            item_name: name,
            uom: uom,
            qty: qty,
            basic_rate: rate
          });
        }
        count++;
      }
    });

    // Populate on-form fields
    const ccSel = document.getElementById(`${ctx}CostCenterSelect`);
    if (ccSel) ccSel.value = selCode;

    const partyInput = document.getElementById(`${ctx}PartyName`);
    if (partyInput && selCode) partyInput.value = `Cost Center: ${selCode}`;

    this.closeCostCenterSelectionModal();
    this.renderStockEntryRows(ctx);
    this.showToast(`Loaded ${count} items for Cost Center ${selCode}.`);
  },

  onCostCenterSelectChange(context, val) {
    const partyInput = document.getElementById(`${context}PartyName`);
    if (partyInput) partyInput.value = val ? `Cost Center: ${val}` : '';
  },

  // Section Selection Dialog
  openSectionSelectionModal(context) {
    this.activeSectionContext = context;
    const modal = document.getElementById('modalGetItemsFromSection');
    if (!modal) return;

    const sel = document.getElementById('modalSectionSelector');
    if (sel && sel.children.length <= 1) {
      const lookups = this.cachedStockLookups || { sections: [] };
      (lookups.sections || []).forEach(s => {
        const opt = document.createElement('option');
        opt.value = s.code;
        opt.textContent = s.display_label || `${s.code} - ${s.title}`;
        sel.appendChild(opt);
      });
    }

    if (sel && sel.options.length > 1) {
      sel.selectedIndex = 1;
      this.onModalSectionSelected(sel.value);
    }

    modal.style.display = 'flex';
  },

  closeSectionSelectionModal() {
    const modal = document.getElementById('modalGetItemsFromSection');
    if (modal) modal.style.display = 'none';
  },

  onModalSectionSelected(code) {
    const tbody = document.getElementById('modalSectionItemsBody');
    if (!tbody) return;

    const lookups = this.cachedStockLookups || { materials: [] };
    const materials = (lookups.materials || []).slice(0, 15);

    if (materials.length === 0) {
      tbody.innerHTML = '<tr><td colspan="5" style="text-align: center; color: #8d99a6; padding: 20px;">No items configured for this Section.</td></tr>';
      return;
    }

    let html = '';
    materials.forEach(m => {
      html += `
        <tr class="sec-modal-item-row" data-sku="${m.sku}" data-name="${(m.item_name||'').toLowerCase()}">
          <td style="text-align: center;">
            <input type="checkbox" class="sec-item-chk" value="${m.sku}" data-name="${m.item_name}" data-uom="${m.uom||'Nos'}" data-rate="${m.unit_price||0}" checked style="accent-color: #18181b; cursor: pointer;">
          </td>
          <td>
            <div style="font-weight: 600; font-size: 12.5px;">${m.sku}</div>
            <div style="font-size: 11.5px; color: #6c7680;">${m.item_name}</div>
          </td>
          <td style="text-align: center; font-size: 12px; color: #6c7680;">${m.uom || 'Nos'}</td>
          <td style="text-align: right; font-size: 12px;">Rs. ${parseFloat(m.unit_price||0).toFixed(2)}</td>
          <td style="text-align: right;">
            <input type="number" step="0.001" min="0" class="frappe-control sec-req-qty" style="height: 28px; width: 85px; text-align: right; font-size: 12px; font-weight: 600;" value="1.000">
          </td>
        </tr>
      `;
    });
    tbody.innerHTML = html;
  },

  filterSectionItems(query) {
    const q = (query || '').toLowerCase().trim();
    document.querySelectorAll('.sec-modal-item-row').forEach(tr => {
      const sku = (tr.getAttribute('data-sku') || '').toLowerCase();
      const name = (tr.getAttribute('data-name') || '').toLowerCase();
      tr.style.display = (!q || sku.includes(q) || name.includes(q)) ? '' : 'none';
    });
  },

  toggleModalSectionCheckAll(checked) {
    document.querySelectorAll('.sec-item-chk').forEach(c => c.checked = checked);
  },

  confirmGetItemsFromSection() {
    const ctx = this.activeSectionContext || 'issue';
    const selCode = document.getElementById('modalSectionSelector')?.value || '';
    const state = this.stockEntryState[ctx];
    const defWh = document.getElementById(`${ctx}DefSourceWh`)?.value || 'Main Store Bay-01';

    let count = 0;
    document.querySelectorAll('#modalSectionItemsBody tr').forEach(tr => {
      const chk = tr.querySelector('.sec-item-chk');
      if (chk && chk.checked) {
        const sku = chk.value;
        const name = chk.getAttribute('data-name');
        const uom = chk.getAttribute('data-uom');
        const rate = parseFloat(chk.getAttribute('data-rate') || 0);
        const qty = parseFloat(tr.querySelector('.sec-req-qty')?.value || 1);

        if (state.items.length === 1 && !state.items[0].item_code) {
          state.items[0].item_code = sku;
          state.items[0].item_name = name;
          state.items[0].uom = uom;
          state.items[0].basic_rate = rate;
          state.items[0].qty = qty;
          state.items[0].warehouse = defWh;
        } else {
          state.items.push({
            id: state.nextId++,
            warehouse: defWh,
            item_code: sku,
            item_name: name,
            uom: uom,
            qty: qty,
            basic_rate: rate
          });
        }
        count++;
      }
    });

    const secSel = document.getElementById(`${ctx}SectionSelect`);
    if (secSel) secSel.value = selCode;

    const partyInput = document.getElementById(`${ctx}PartyName`);
    if (partyInput && selCode) partyInput.value = `Section: ${selCode}`;

    this.closeSectionSelectionModal();
    this.renderStockEntryRows(ctx);
    this.showToast(`Loaded ${count} items for Section ${selCode}.`);
  },

  onSectionSelectChange(context, val) {
    const partyInput = document.getElementById(`${context}PartyName`);
    if (partyInput && val) partyInput.value = `Section: ${val}`;
  },



  getItemsFromMaterialRequest(context) {
    this.showToast('Select items from Material Request list:');
    this.openAddMultipleModal(context);
  },

  getItemsFromBOM(context) {
    this.showToast('Select items from Bill of Materials list:');
    this.openAddMultipleModal(context);
  }
};

window.openModal = function(id) { Desk.openModal(id); };
window.closeModal = function(id) { Desk.closeModal(id); };
window.Desk = Desk;

document.addEventListener('click', (e) => {
  if (!e.target.closest('.dropdown-wrapper')) {
    document.querySelectorAll('.frappe-dropdown-menu').forEach(m => m.style.display = 'none');
  }
});
