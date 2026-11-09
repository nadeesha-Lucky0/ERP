# EGL FIFO ERP — Legacy System to New Modern ERP Complete Navigation & Mapping Guide
> **Document Purpose**: A comprehensive, tab-by-tab cross-reference guide mapping the legacy "Real FIFO" system menus, sub-menus, inputs, and reports directly to their exact locations in the new **FIFOERP** platform. Designed for power station staff, stores officers, accountants, and system auditors to easily transition and locate every feature.

---

## 📌 Executive Summary of the Architectural Upgrade

In the legacy system, navigation was divided across nested hover dropdown menus (`Inventory - FIFO`, `Cash Book`, `Reports`, `Authorized Tasks`, `Code`). In the new **FIFOERP**, the interface has been upgraded to a modern, fast, responsive **Workspace Desk layout** (inspired by ERPNext / Frappe):

1. **Persistent Sidebar Navigation**: Every major module is grouped logically in the left sidebar for instant 1-click access.
2. **Tabbed Sub-Workflows**: Multi-step workflows (such as Physical Stock Verification Form AV 7 and Material Issues) are housed within clean horizontal sub-tabs with zero scrolling or flyout menu hunting.
3. **Instant Live Search & Filters**: Built-in dynamic search bars, date range filters, and status pills (Draft, Validated, Approved, Posted, Cancelled) replace old reload-heavy pages.
4. **100% Functional Parity**: All forms, text boxes, default values, validations, calculations, and official reports from the legacy system are preserved and enhanced.

---

## 📊 Table 1: Inventory - FIFO &rarr; Transaction Menu (Screenshot 1)

This section covers Goods Receipts (GRN) and the 6-stage Material Issue requisition lifecycle.

| # | Real Legacy FIFO Menu | New FIFOERP Location | Navigation Path / Component | Key Fields, Textboxes & Defaults | Parity Status |
|---|----------------------|----------------------|-----------------------------|----------------------------------|:-------------:|
| 1.1 | **Transaction &rarr; Reciepts** | **Receipts (GRN)**<br>`tab-receipts` | Sidebar &rarr; `Inventory & FIFO` &rarr; `Receipts (GRN)` &rarr; `New GRN Receipt` Button | **Fields**: Document Profile (`GRN`, `GRSP`, `MRA`), Status (`New`), Receipt From (`PO`, `Cost Center`, `Section`), Delivery Date (`Current Date`), Acc Date (`Current Date`), Source Dept (`832.00` readonly), Due Date, Warehouse dropdown, Base On (`Invoice`, `Measurement Report`), Invoice/PO No, Supplier Name.<br>**Item Grid**: Material Code, Description, Bin Location, Quantity, Unit Price, Total.<br>**Valuation Panel**: NBT, VAT, Freight, Customs Cess, Customs Duty. | ✅ **100% Match** |
| 1.2 | **Transaction &rarr; Issue &rarr; Entry** | **Material Issues (FIFO)**<br>`tab-issues` | Sidebar &rarr; `Material Issues (FIFO)` &rarr; `+ New Material Issue` Modal (`modalNewIssue`) | **Fields**: Issue Requisition No, Destination Code / Job Area dropdown, Requesting Dept (`Mechanical`, `Electrical`, `Civil`, `Operations`), Issue Date (`Current Date`), Target Station (`Victoria 831`).<br>**Item Picker**: SKU / Material Code, Item Description, Current QOH, Requested Qty, Auto FIFO Batch Lot Assignment, Estimated Unit Cost, Total Cost. | ✅ **100% Match** |
| 1.3 | **Transaction &rarr; Issue &rarr; Edit** | **Material Issues (FIFO)**<br>`tab-issues` | Filter Pill `Draft / In Entry` &rarr; Action `Edit` Button (`modalEditIssue`) | **Fields**: Re-open draft requisition, edit quantity, modify target destination code or requesting department, recalculate estimated FIFO allocation. | ✅ **100% Match** |
| 1.4 | **Transaction &rarr; Issue &rarr; Validation** | **Material Issues (FIFO)**<br>`tab-issues` | Filter Pill `Validated` &rarr; Action `Validate` Button | **Fields**: Technical Store Officer validation stamp, allocation freeze, check availability against active FIFO lots, validation timestamp. | ✅ **100% Match** |
| 1.5 | **Transaction &rarr; Issue &rarr; Approval** | **Material Issues (FIFO)**<br>`tab-issues` | Filter Pill `Approved & Ready to Post` &rarr; Action `Approve` Button | **Fields**: Chief Engineer / Authorized Officer electronic sign-off, authorized quantity approval, status lock for ledger posting. | ✅ **100% Match** |
| 1.6 | **Transaction &rarr; Issue &rarr; Posted Cancellation** | **Material Issues (FIFO)**<br>`tab-issues` | Filter Pill `Posted to FIFO` &rarr; Action `Cancel / Reverse` Button | **Fields**: Reversal of issued stock back into original FIFO lot, reversal journal voucher generation, mandatory cancellation justification textarea. | ✅ **100% Match** |
| 1.7 | **Transaction &rarr; Issue &rarr; UnPosted Cancellation** | **Material Issues (FIFO)**<br>`tab-issues` | Filter Pill `Draft` or `Validated` &rarr; Action `Cancel Requisition` Button | **Fields**: Direct cancellation of unposted/draft requisitions, releases any reserved stock balance back to free available QOH, cancellation audit log. | ✅ **100% Match** |
| 1.8 | **Transaction &rarr; Posting** | **Material Issues & Ledger**<br>`tab-issues` | Action `Post to FIFO Ledger` Button & Batch Document Profile Posting | **Fields**: Document Profile (`TV-ISSUE`, `MAT-REQ`, `ISSUE`), Batch consumption posting, lot remaining quantity deduction, stock ledger entry. | ✅ **100% Match** |

---

## 📋 Table 2: Inventory - FIFO &rarr; Physical Verification Workflow (Screenshot 2)

Covers the Board of Survey store audit cycle from batch locking to general ledger posting.

| # | Real Legacy FIFO Menu | New FIFOERP Location | Navigation Path / Component | Key Fields, Textboxes & Defaults | Parity Status |
|---|----------------------|----------------------|-----------------------------|----------------------------------|:-------------:|
| 2.1 | **Physical Verification &rarr; Freeze Batches** | **Verification Workflow**<br>`tab-pv-workflow` | Sidebar &rarr; `Verification Workflow` &rarr; Sub-tab: `Freeze Batches` (`pv-sub-freeze`) | **Controls**: Store Transaction Lock Status Badge (`NORMAL / ACTIVE` vs `LOCKED / FROZEN`), Button: `Freeze All Batches Now`.<br>**Table**: Batch Lot #, Material Code, Item Description, Bin Location, Available QOH, Unit Cost (LKR), Total Valuation (LKR), Lock Status (`Active` / `Frozen`). | ✅ **100% Match** |
| 2.2 | **Physical Verification &rarr; Generate Sheet** | **Verification Workflow**<br>`tab-pv-workflow` | Sidebar &rarr; `Verification Workflow` &rarr; Sub-tab: `Generate Sheet` (`pv-sub-generate`) | **Filters**: Power Station dropdown (`Victoria Hydro`, `Kotmale`, `Randenigala`, etc.), Store / Warehouse dropdown (`Central Main Store`, `Mechanical`, `Electrical`, `Turbine Yard`, `Fuel Depot`), Category filter.<br>**Options**: Checkbox `Print Blank Lines for Unlisted Items` (Default: Checked).<br>**Actions**: `Generate Verification Sheet`, `Download PDF (21 items/sheet)`, `Export All Pages ZIP Bundle`. | ✅ **100% Match** |
| 2.3 | **Physical Verification &rarr; Verification Entry** | **Verification Workflow**<br>`tab-pv-workflow` | Sidebar &rarr; `Verification Workflow` &rarr; Sub-tab: `Count Entry` (`pv-sub-entry`) | **Controls**: Sheet Selector dropdown, Item Search textbox.<br>**Grid**: Item Code, Description, Location Bin, System Book Balance (QOH), Physical Counted Qty (editable input), Auto-calculated Variance (Surplus (+) / Deficit (-)), Verifier Officer initials. | ✅ **100% Match** |
| 2.4 | **Physical Verification &rarr; Verification Edit** | **Verification Workflow**<br>`tab-pv-workflow` | Sidebar &rarr; `Verification Workflow` &rarr; Sub-tab: `Verification Edit` (`pv-sub-edit`) | **Controls**: Re-open submitted sheet, edit recount figures, discrepancy justification textbox, update verification count before formal audit review. | ✅ **100% Match** |
| 2.5 | **Physical Verification &rarr; Verification Validation** | **Verification Workflow**<br>`tab-pv-workflow` | Sidebar &rarr; `Verification Workflow` &rarr; Sub-tab: `Validation` (`pv-sub-validation`) | **Controls**: Audit validation checklist, discrepancy threshold filter, verify variance valuations, internal audit team sign-off stamp. | ✅ **100% Match** |
| 2.6 | **Physical Verification &rarr; Verification Approval** | **Verification Workflow**<br>`tab-pv-workflow` | Sidebar &rarr; `Verification Workflow` &rarr; Sub-tab: `Approval` (`pv-sub-approval`) | **Controls**: Official Board of Survey approval committee panel, approval remarks textarea, sign-off certification by Chief Engineer / Store Controller. | ✅ **100% Match** |
| 2.7 | **Physical Verification &rarr; Verification Posting** | **Verification Workflow**<br>`tab-pv-workflow` | Sidebar &rarr; `Verification Workflow` &rarr; Sub-tab: `Posting` (`pv-sub-posting`) | **Controls**: Discrepancy Adjustment Voucher Profile, stock balance synchronization, adjustments to FIFO batch balances (surplus lots added / shortage lots deducted). | ✅ **100% Match** |
| 2.8 | **Physical Verification &rarr; Verification GL Posting** | **Verification Workflow**<br>`tab-pv-workflow` | Sidebar &rarr; `Verification Workflow` &rarr; Sub-tab: `GL Posting` (`pv-sub-gl`) | **Controls**: Financial Year & Period selector, GL Account Mapping (Inventory Adjustment Ledger: Debit Shortage / Credit Surplus), Journal Voucher Posting. | ✅ **100% Match** |
| 2.9 | **Physical Verification &rarr; Unfreeze Batches** | **Verification Workflow**<br>`tab-pv-workflow` | Sidebar &rarr; `Verification Workflow` &rarr; Sub-tab: `Unfreeze Batches` (`pv-sub-unfreeze`) | **Controls**: Store Unlock Authorization Button: `Unfreeze Store Batches`, restores system to normal transaction mode for Receipts and Issues. | ✅ **100% Match** |

---

## 📑 Table 3: Physical Verification &rarr; PHV Reports Flyout Menu (Screenshot 2 Flyout)

Statutory stores audit reports and Form AV 7 schedules.

| # | Real Legacy FIFO Menu | New FIFOERP Location | Navigation Path / Component | Key Fields, Textboxes & Defaults | Parity Status |
|---|----------------------|----------------------|-----------------------------|----------------------------------|:-------------:|
| 3.1 | **PHV Reports &rarr; Avg Consumption** | **PHV Reports (AV 7)**<br>`tab-pv-reports` | Sidebar &rarr; `PHV Reports (AV 7)` &rarr; Sub-tab: `Avg Consumption` (`pv-sub-avg`) | **Filters**: Cost Center dropdown (`Mechanical`, `Electrical`, `Civil`, `Operations`, `Admin`), Warehouse dropdown, From Date (`YYYY-01-01`), To Date (`Current Date`).<br>**Metrics**: Monthly usage average, safety stock minimum, reorder indicator, total value consumed. | ✅ **100% Match** |
| 3.2 | **PHV Reports &rarr; Final Report** | **PHV Reports (AV 7)**<br>`tab-pv-reports` | Sidebar &rarr; `PHV Reports (AV 7)` &rarr; Sub-tab: `Final Report` (`pv-sub-final`) | **Controls**: Verification Run selector, Comprehensive Final Audit Discrepancy Schedule, net surplus/deficit value, official certification block. | ✅ **100% Match** |
| 3.3 | **PHV Reports &rarr; Other Reports** | **PHV Reports (AV 7)**<br>`tab-pv-reports` | Sidebar &rarr; `PHV Reports (AV 7)` &rarr; Sub-tab: `Other Reports` (`pv-sub-other`) | **Filters**: Stock classification, Slow-moving (> 180 days), Dormant / Non-moving (> 365 days), Damaged / Scrap / Obsolete stock schedules. | ✅ **100% Match** |
| 3.4 | **PHV Reports &rarr; PHV Sheet Summary** | **PHV Reports (AV 7)**<br>`tab-pv-reports` | Sidebar &rarr; `PHV Reports (AV 7)` &rarr; Sub-tab: `PHV Sheet Summary` (`pv-sub-summary`) | **Metrics**: Total Sheet count generated, Total Verified Items count, Completed vs Pending Sheets progress bar, Total Discrepancy Value (LKR). | ✅ **100% Match** |
| 3.5 | **PHV Reports &rarr; AV 7 Summary** | **PHV Reports (AV 7)**<br>`tab-pv-reports` | Sidebar &rarr; `PHV Reports (AV 7)` &rarr; Sub-tab: `Form AV 7 Summary` (`pv-sub-av7`) | **Standard**: Official Sri Lanka Public Stores Form AV 7 layout, Item No, Description, Unit, Price, Ledger Qty, Verified Qty, Surplus Value, Deficit Value, Tri-party signature blocks (Verifier, Storekeeper, BOS Head). | ✅ **100% Match** |

---

## 💰 Table 4: Cash Book Menu (Screenshot 3)

Staff payroll adjustments and cash transactions.

| # | Real Legacy FIFO Menu | New FIFOERP Location | Navigation Path / Component | Key Fields, Textboxes & Defaults | Parity Status |
|---|----------------------|----------------------|-----------------------------|----------------------------------|:-------------:|
| 4.1 | **Cash Book &rarr; Payslip Cancelation** | **Payslip Cancellations**<br>`tab-payslips` | Sidebar &rarr; `Cash Book & Payroll` &rarr; `Payslip Cancellations` | **Filters**: Month/Year picker (`Current YYYY-MM`), Cost Center dropdown (`Mechanical`, `Electrical`, `Administration`, `Civil`), Status dropdown (`Active`, `Cancelled`), Search textbox (EPF or Name).<br>**Actions**: `Cancel Payslip` Modal (`modalPayslipCancel`) with cancellation reason, salary reversal voucher. | ✅ **100% Match** |
| 4.2 | **Cash Book &rarr; Vouchers** | **Cash Book Vouchers**<br>`tab-cashbook` | Sidebar &rarr; `Cash Book & Payroll` &rarr; `Cash Book Vouchers` | **Fields**: Voucher No, Voucher Type (`Payment`, `Receipt`, `Contra`), Payee / Beneficiary, Account Code, Amount (LKR), Check/Ref No, Description, Date filter. | ✅ **100% Match** |

---

## 📈 Table 5: Reports Menu (Screenshot 4)

Complete suite of 10 inventory, procurement, and stock valuation reports.

| # | Real Legacy FIFO Menu | New FIFOERP Location | Navigation Path / Component | Key Fields, Textboxes & Defaults | Parity Status |
|---|----------------------|----------------------|-----------------------------|----------------------------------|:-------------:|
| 5.1 | **Quantity On Hand &rarr; Batch wise report** | **Active Batches (QOH)**<br>`tab-batches` | Sidebar &rarr; `Inventory & FIFO` &rarr; `Active Batches (QOH)` | **Filters**: Live Search (Batch #, Material Code, Item Name), Bin Location filter, Warehouse filter.<br>**Columns**: Batch Lot #, Material SKU, Description, Bin Location, Available QOH, Unit Cost (LKR), Total Valuation (LKR), Expiry Date, Status. | ✅ **100% Match** |
| 5.2 | **Quantity On Hand &rarr; Material wise report** | **Material-wise Balance**<br>`tab-report-qoh-material` | Sidebar &rarr; `Reports` &rarr; `Material-wise Balance` | **Filters**: Material SKU / Name Search, Category dropdown, Reorder Level alert filter.<br>**Columns**: Item Code, Description, Category, Total Available QOH across all batches, Weighted Average Cost, Total Stock Valuation, Reorder Status. | ✅ **100% Match** |
| 5.3 | **Monthly Transfers** | **Monthly Transfers**<br>`tab-report-transfers` | Sidebar &rarr; `Reports` &rarr; `Monthly Transfers` | **Filters**: From Power Station, To Power Station, Transfer Month (`YYYY-MM`).<br>**Columns**: Transfer Voucher No, Date, Source Station, Destination Station, Material Items list, Total Transfer Value (LKR), Dispatch Status. | ✅ **100% Match** |
| 5.4 | **Average Diesel Price** | **Average Diesel Price**<br>`tab-report-diesel` | Sidebar &rarr; `Reports` &rarr; `Average Diesel Price` | **Filters**: Financial Year dropdown, Fuel Depot selector.<br>**Columns**: Month, Total Inward Litres received, Total Procurement Cost, Weighted Average Diesel Price per Litre (LKR), Generator consumption trend. | ✅ **100% Match** |
| 5.5 | **CPC Procurements report** | **CPC Procurements**<br>`tab-report-cpc` | Sidebar &rarr; `Reports` &rarr; `CPC Procurements` | **Filters**: CPC Order No, Supplier Invoice, Date Range (From/To).<br>**Columns**: Procurement Order No, Delivery Date, Fuel Type (`Diesel`, `Heavy Fuel Oil`, `Transformer Oil`), Quantity in Litres, CPC Official Tariff, Total LKR, Delivery Ref. | ✅ **100% Match** |
| 5.6 | **Inventory Inquiry Report** | **Inventory Inquiry Report**<br>`tab-report-inventory-inquiry` | Sidebar &rarr; `Reports` &rarr; `Inventory Inquiry Report` | **Filters**: Item Search box (SKU, Item Name, Bin Location), Category filter dropdown (`Spares`, `Consumables`, `Lubricants`, `Electrical`, `Hardware`), Stock Level Status.<br>**Columns**: Material Code, Description, Category, Bin, Current QOH, Allocated Qty, Free Available, Reorder Level, Unit Cost, Valuation. | ✅ **100% Match** |
| 5.7 | **Job wise material Issue Report** | **Job-wise Issues**<br>`tab-report-jobs` | Sidebar &rarr; `Reports` &rarr; `Job-wise Issues` | **Filters**: Destination Code / Work Order # dropdown, Department, Date Range.<br>**Columns**: Job Area / Destination Code, Work Order Description, Material Code, Item Name, Quantity Issued, FIFO Valuation Total, Requesting Dept, Issue Date. | ✅ **100% Match** |
| 5.8 | **Monthly Transaction** | **Monthly Transactions**<br>`tab-report-monthly-tx` | Sidebar &rarr; `Reports` &rarr; `Monthly Transactions` | **Filters**: Year selector (`Current Year`), Transaction Type filter (`All`, `GRN Receipts`, `Material Issues`, `Returns`, `Adjustments`).<br>**Columns**: Month, Transaction Type, Transaction Count, Inward Qty, Outward Qty, Total Transaction Value (LKR), Net Stock Movement. | ✅ **100% Match** |
| 5.9 | **GRN - Issue Summary** | **GRN - Issue Summary**<br>`tab-report-grn-issue-summary` | Sidebar &rarr; `Reports` &rarr; `GRN - Issue Summary` | **Filters**: Date Range From (`YYYY-MM-DD`) & To (`YYYY-MM-DD`), Department filter dropdown.<br>**Columns**: Material Code, Description, Total Inward Receipts (GRN Qty & Value), Total Outward Issues (Issue Qty & Value), Net Balance Variance for the period. | ✅ **100% Match** |
| 5.10 | **Material Flow Report** | **Material Flow Report**<br>`tab-report-material-flow` | Sidebar &rarr; `Reports` &rarr; `Material Flow Report` | **Filters**: Material Code Search, Movement Period filter (`From Date` - `To Date`).<br>**Columns**: Material Code, Description, Opening Stock, Total Receipts (+), Total Issues (-), Physical Discrepancy Adjustments (+/-), Closing Stock Balance, Valuation (LKR). | ✅ **100% Match** |

---

## ⚙️ Table 6: Authorized Tasks Menu (Screenshot 5)

Restricted administrative and supervisory adjustment tools.

| # | Real Legacy FIFO Menu | New FIFOERP Location | Navigation Path / Component | Key Fields, Textboxes & Defaults | Parity Status |
|---|----------------------|----------------------|-----------------------------|----------------------------------|:-------------:|
| 6.1 | **Cost Adjustments** | **Cost Adjustments**<br>`tab-cost-adjust` | Sidebar &rarr; `Authorized Tasks` &rarr; `Cost Adjustments` &rarr; `+ New Cost Adjustment` Modal (`modalCostAdjust`) | **Inputs**: Batch Lot # selector (searchable), Material Code & Name (auto-populated), Current Unit Cost (readonly), New Adjusted Unit Cost (number input), Adjustment Reason dropdown (`Customs Cess Surcharge`, `Freight Variation`, `Excise Revision`, `Manual Valuation Correction`), Effective Date (`Current Date`), Approval Justification textarea.<br>**Audit Table**: Adjustment Ref, Batch #, SKU, Old Cost, New Cost, Variance, Reason, Authorizing Officer, Timestamp. | ✅ **100% Match** |
| 6.2 | **Allocated Qty Correction** | **Allocated Qty Correction**<br>`tab-qty-correct` | Sidebar &rarr; `Authorized Tasks` &rarr; `Allocated Qty Correction` | **Inputs**: Search by Material Code or Job.<br>**Table**: Batch Lot #, Material SKU, Description, Total Physical Stock, Stuck Allocated Qty, Free Available Qty.<br>**Action**: `Unlock / Reset Allocation` Button (with confirmation prompt) to release stuck reservations back to free stock. | ✅ **100% Match** |
| 6.3 | **Destination Codes** | **Destination Codes**<br>`tab-dest-codes` | Sidebar &rarr; `Authorized Tasks` &rarr; `Destination Codes` | **Inputs**: Destination Code, Description, Associated Work Order / Department (`Mechanical`, `Electrical`, `Civil`, `Operations`), Active/Inactive status toggle.<br>**Table**: Destination Code Registry with Add/Edit actions. | ✅ **100% Match** |

---

## 🏷️ Table 7: Code Management Menu (Legacy Top Bar Item 6)

Master catalog search and station item requisition tracking.

| # | Real Legacy FIFO Menu | New FIFOERP Location | Navigation Path / Component | Key Fields, Textboxes & Defaults | Parity Status |
|---|----------------------|----------------------|-----------------------------|----------------------------------|:-------------:|
| 7.1 | **Code &rarr; Item Search** | **Top Header Universal Search & Master Catalog** | Header Search Bar & `Inventory Inquiry Report` (`tab-report-inventory-inquiry`) | **Inputs**: Universal Quick-Search bar (hotkey ready), queries across Material Master, SKUs, Item Descriptions, Batch Lots, Documents, and Fuel Orders with instant live autocomplete dropdown. | ✅ **100% Match** |
| 7.2 | **Code &rarr; Station Requests** | **Material Issues & Code Registry** | `tab-dest-codes` & `tab-issues` | **Inputs**: Station Code Requisitions, Tracking requested item numbers submitted by Power Station (e.g. `Victoria Power Station 831`), approval status tracking. | ✅ **100% Match** |

---

## 🧭 Quick Guide for Power Station Staff (සිංහල මාර්ගෝපදේශය)

පරණ FIFO පද්ධතියේ සහ නව FIFOERP පද්ධතියේ වැඩ කරන නිලධාරීන්ට පහසුවෙන් භාවිත කිරීමට පහත කරුණු සටහන් කරගන්න:

1. **Left Sidebar (වම්පස පාලක පුවරුව)**:
   * කලින් උඩ තිබූ Dropdowns වෙනුවට සියලුම ප්‍රධාන අංශ (Inventory & FIFO, Physical Verification, Cash Book, Reports, Authorized Tasks) වම්පස Sidebar එකෙන් එක ක්ලික් එකකින් ලබා ගත හැක.
2. **Physical Verification (තොග ගණනය කිරීම් සහ AV 7)**:
   * `Verification Workflow` ක්ලික් කළ විට Freeze Batches, Generate Sheet, Count Entry, Edit, Validation, Approval, Posting, GL Posting, Unfreeze යන පියවර 9 ම පිළිවෙළින් horizontal tabs ලෙස එකම තිරයේ දිස්වේ.
   * `PHV Reports (AV 7)` ක්ලික් කළ විට Avg Consumption, Final Report, Other Reports, Sheet Summary සහ AV 7 Summary යන වාර්තා 5 ම පහසුවෙන් විවෘත වේ.
3. **Material Issues (ද්‍රව්‍ය නිකුත් කිරීමේ අදියර)**:
   * `Material Issues (FIFO)` තෝරා ගත් විට ඉහළ ඇති Quick Filter Pills (`Draft`, `Validated`, `Approved`, `Posted`, `Cancelled`) මඟින් එක් එක් අදියරේ ඇති ඉල්ලීම් ක්ෂණිකව පෙරහන් (filter) කරගත හැක.
4. **Instant Reports (වාර්තා 10)**:
   * Reports යටතේ ඇති සියලුම වාර්තා 10 හිම Live Search Bar, Date Range Pickers, සහ Excel / Print Preview පහසුකම් සක්‍රිය කර ඇත.
