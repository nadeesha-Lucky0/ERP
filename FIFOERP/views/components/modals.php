<!-- ==============================================================
     MODAL 1: New GRN Receipt
     ============================================================== -->
<div id="modalNewGrn" class="desk-modal-backdrop">
  <div class="desk-modal">
    <div class="desk-modal-header">
      <h3>New Goods Receipt Note (GRN)</h3>
      <button type="button" class="desk-modal-close" onclick="Desk.closeModal('modalNewGrn')">&times;</button>
    </div>
    <div class="desk-modal-body">
      <form id="formNewGrn" onsubmit="event.preventDefault(); submitNewGrn();">
        <div class="desk-form-group">
          <label class="desk-form-label">Supplier / Receipt From</label>
          <input type="text" id="grnSupplier" class="desk-form-control" required placeholder="e.g. Lanka Industrial Lubricants Ltd">
        </div>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
          <div class="desk-form-group">
            <label class="desk-form-label">Material SKU</label>
            <input type="text" id="grnSku" class="desk-form-control" required placeholder="e.g. LUB-VG46-200L">
          </div>
          <div class="desk-form-group">
            <label class="desk-form-label">Material Description</label>
            <input type="text" id="grnName" class="desk-form-control" required placeholder="e.g. Turbine Oil VG 46 (200L Drum)">
          </div>
        </div>
        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 12px;">
          <div class="desk-form-group">
            <label class="desk-form-label">Quantity</label>
            <input type="number" id="grnQty" class="desk-form-control" min="1" value="10" required>
          </div>
          <div class="desk-form-group">
            <label class="desk-form-label">Unit Cost (Rs.)</label>
            <input type="number" id="grnCost" class="desk-form-control" step="0.01" min="0" value="95000" required>
          </div>
          <div class="desk-form-group">
            <label class="desk-form-label">Storage Bin</label>
            <input type="text" id="grnBin" class="desk-form-control" value="Bay-Oil-A1">
          </div>
        </div>
        <div class="desk-form-group">
          <label class="desk-form-label">Remarks / Consignment Ref</label>
          <textarea id="grnRemarks" class="desk-form-control" placeholder="Optional notes..."></textarea>
        </div>
        <div class="desk-modal-footer">
          <button type="button" class="desk-btn desk-btn-default" onclick="Desk.closeModal('modalNewGrn')">Cancel</button>
          <button type="submit" class="desk-btn desk-btn-primary">Create Draft GRN</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- ==============================================================
     MODAL 2: New Material Issue (FIFO Queue Consumption)
     ============================================================== -->
<div id="modalNewIssue" class="desk-modal-backdrop">
  <div class="desk-modal">
    <div class="desk-modal-header">
      <h3>New Material Issue (FIFO Auto-Queue)</h3>
      <button type="button" class="desk-modal-close" onclick="Desk.closeModal('modalNewIssue')">&times;</button>
    </div>
    <div class="desk-modal-body">
      <form id="formNewIssue" onsubmit="event.preventDefault(); submitNewIssue();">
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
          <div class="desk-form-group">
            <label class="desk-form-label">Select Material (SKU)</label>
            <select id="issueSku" class="desk-form-control" required>
              <option value="LUB-VG46-200L">LUB-VG46-200L - Turbine Lubricating Oil Shell Turbo T 46</option>
              <option value="HYD-HOSE-2IN">HYD-HOSE-2IN - High Pressure Hydraulic Hose 2" 350 Bar</option>
              <option value="GRS-SHL-18KG">GRS-SHL-18KG - Generator Bearing Grease Shell Gadus S2</option>
              <option value="FAN-TR-415V">FAN-TR-415V - Transformer Cooling Fan Motor 415V</option>
              <option value="BRU-ALT-SET">BRU-ALT-SET - Carbon Brushes Set for Alternator</option>
              <option value="VAL-PRV-100B">VAL-PRV-100B - Pressure Relief Valve 100 Bar</option>
            </select>
          </div>
          <div class="desk-form-group">
            <label class="desk-form-label">Quantity to Issue</label>
            <input type="number" id="issueQty" class="desk-form-control" min="1" value="2" required>
          </div>
        </div>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
          <div class="desk-form-group">
            <label class="desk-form-label">Destination Code / Job Area</label>
            <select id="issueDest" class="desk-form-control" required>
              <option value="DEST-GEN-U1">DEST-GEN-U1 - Unit 01 Francis Turbine & Generator</option>
              <option value="DEST-GEN-U2">DEST-GEN-U2 - Unit 02 Francis Turbine & Generator</option>
              <option value="DEST-PEN-01">DEST-PEN-01 - Main Penstock Intake Valve 01</option>
              <option value="DEST-SWG-220KV">DEST-SWG-220KV - 220kV Outdoor Switchyard Bay</option>
              <option value="DEST-DAM-SP01">DEST-DAM-SP01 - Radial Spillway Gate No. 01</option>
            </select>
          </div>
          <div class="desk-form-group">
            <label class="desk-form-label">Requesting Department</label>
            <select id="issueDept" class="desk-form-control">
              <option value="ELEC-GEN">Electrical Generation & Control</option>
              <option value="MECH-MAINT">Mechanical Maintenance</option>
              <option value="CIVIL-DAM">Civil & Dam Safety</option>
            </select>
          </div>
        </div>
        <div class="desk-form-group">
          <label class="desk-form-label">Work Order / Purpose</label>
          <input type="text" id="issueRemarks" class="desk-form-control" placeholder="e.g. Scheduled turbine bearing inspection overhaul">
        </div>
        <div class="desk-modal-footer">
          <button type="button" class="desk-btn desk-btn-default" onclick="Desk.closeModal('modalNewIssue')">Cancel</button>
          <button type="submit" class="desk-btn desk-btn-primary">Create Draft Issue</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- ==============================================================
     MODAL 3: New Cash Book Voucher
     ============================================================== -->
<div id="modalNewCashVoucher" class="desk-modal-backdrop">
  <div class="desk-modal">
    <div class="desk-modal-header">
      <h3>New Cash Voucher Entry</h3>
      <button type="button" class="desk-modal-close" onclick="Desk.closeModal('modalNewCashVoucher')">&times;</button>
    </div>
    <div class="desk-modal-body">
      <form id="formNewCash" onsubmit="event.preventDefault(); submitCashVoucher();">
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
          <div class="desk-form-group">
            <label class="desk-form-label">Voucher Type</label>
            <select id="cashType" class="desk-form-control">
              <option value="Payment">Payment Voucher (Cash Out)</option>
              <option value="Receipt">Receipt Voucher (Cash In)</option>
            </select>
          </div>
          <div class="desk-form-group">
            <label class="desk-form-label">Amount (Rs.)</label>
            <input type="number" id="cashAmount" class="desk-form-control" step="0.01" min="1" value="15000" required>
          </div>
        </div>
        <div class="desk-form-group">
          <label class="desk-form-label">Account Title</label>
          <input type="text" id="cashTitle" class="desk-form-control" placeholder="e.g. Emergency Mechanical Freight" required>
        </div>
        <div class="desk-form-group">
          <label class="desk-form-label">Payee / Source</label>
          <input type="text" id="cashPayee" class="desk-form-control" placeholder="e.g. Express Courier Services">
        </div>
        <div class="desk-form-group">
          <label class="desk-form-label">Description / Particulars</label>
          <textarea id="cashDesc" class="desk-form-control" placeholder="Reason for expenditure..."></textarea>
        </div>
        <div class="desk-modal-footer">
          <button type="button" class="desk-btn desk-btn-default" onclick="Desk.closeModal('modalNewCashVoucher')">Cancel</button>
          <button type="submit" class="desk-btn desk-btn-primary">Save Voucher</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- ==============================================================
     MODAL 4: Request Payslip Cancellation
     ============================================================== -->
<div id="modalPayslipCancel" class="desk-modal-backdrop">
  <div class="desk-modal">
    <div class="desk-modal-header">
      <h3>Request Payslip Cancellation</h3>
      <button type="button" class="desk-modal-close" onclick="Desk.closeModal('modalPayslipCancel')">&times;</button>
    </div>
    <div class="desk-modal-body">
      <form id="formPayslipCancel" onsubmit="event.preventDefault(); submitPayslipCancel();">
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
          <div class="desk-form-group">
            <label class="desk-form-label">Payslip Number</label>
            <input type="text" id="paySlipNo" class="desk-form-control" placeholder="e.g. PAY-2026-02-120" required>
          </div>
          <div class="desk-form-group">
            <label class="desk-form-label">EPF Number</label>
            <input type="text" id="payEpf" class="desk-form-control" placeholder="e.g. EPF-9021" required>
          </div>
        </div>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
          <div class="desk-form-group">
            <label class="desk-form-label">Employee Name</label>
            <input type="text" id="payEmpName" class="desk-form-control" placeholder="e.g. K. M. Bandara" required>
          </div>
          <div class="desk-form-group">
            <label class="desk-form-label">Gross Amount (Rs.)</label>
            <input type="number" id="payAmount" class="desk-form-control" step="0.01" value="135000" required>
          </div>
        </div>
        <div class="desk-form-group">
          <label class="desk-form-label">Cancellation Reason</label>
          <textarea id="payReason" class="desk-form-control" placeholder="Detailed explanation for cancellation..." required></textarea>
        </div>
        <div class="desk-modal-footer">
          <button type="button" class="desk-btn desk-btn-default" onclick="Desk.closeModal('modalPayslipCancel')">Cancel</button>
          <button type="submit" class="desk-btn desk-btn-danger">Submit Cancellation Request</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- ==============================================================
     MODAL 5: Cost Adjustment
     ============================================================== -->
<div id="modalCostAdjust" class="desk-modal-backdrop">
  <div class="desk-modal">
    <div class="desk-modal-header">
      <h3>Apply Cost / Valuation Adjustment</h3>
      <button type="button" class="desk-modal-close" onclick="Desk.closeModal('modalCostAdjust')">&times;</button>
    </div>
    <div class="desk-modal-body">
      <form id="formCostAdjust" onsubmit="event.preventDefault(); submitCostAdjust();">
        <div class="desk-form-group">
          <label class="desk-form-label">Select Batch to Adjust</label>
          <select id="costAdjustBatch" class="desk-form-control" required>
            <option value="1">BATCH-2026-001 (Turbine Oil VG 46 - Old Cost: Rs. 92,500.00)</option>
            <option value="3">BATCH-2026-003 (Hydraulic Hose 2" - Old Cost: Rs. 48,500.00)</option>
            <option value="4">BATCH-2026-004 (Bearing Grease Shell - Old Cost: Rs. 38,200.00)</option>
          </select>
        </div>
        <div class="desk-form-group">
          <label class="desk-form-label">New Unit Cost (Rs.)</label>
          <input type="number" id="costAdjustNewCost" class="desk-form-control" step="0.01" value="94000" required>
        </div>
        <div class="desk-form-group">
          <label class="desk-form-label">Reason / Justification</label>
          <textarea id="costAdjustReason" class="desk-form-control" placeholder="e.g. Additional freight/customs cess surcharge allocation" required></textarea>
        </div>
        <div class="desk-modal-footer">
          <button type="button" class="desk-btn desk-btn-default" onclick="Desk.closeModal('modalCostAdjust')">Cancel</button>
          <button type="submit" class="desk-btn desk-btn-primary">Apply Valuation Adjustment</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- ==============================================================
     MODAL 6: Add Diesel Price Log
     ============================================================== -->
<div id="modalNewDiesel" class="desk-modal-backdrop">
  <div class="desk-modal">
    <div class="desk-modal-header">
      <h3>Add Diesel Price Log</h3>
      <button type="button" class="desk-modal-close" onclick="Desk.closeModal('modalNewDiesel')">&times;</button>
    </div>
    <div class="desk-modal-body">
      <form id="formNewDiesel" onsubmit="event.preventDefault(); submitNewDiesel();">
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
          <div class="desk-form-group">
            <label class="desk-form-label">Effective Month</label>
            <input type="month" id="dieselMonth" class="desk-form-control" value="<?= date('Y-m') ?>" required>
          </div>
          <div class="desk-form-group">
            <label class="desk-form-label">Fuel Type</label>
            <select id="dieselFuelType" class="desk-form-control">
              <option value="Auto Diesel">Auto Diesel (Standard)</option>
              <option value="Super Diesel">Super Diesel (10ppm)</option>
              <option value="Heavy Fuel Oil">Heavy Fuel Oil (Furnace Oil)</option>
            </select>
          </div>
        </div>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
          <div class="desk-form-group">
            <label class="desk-form-label">Average Price / Litre (LKR)</label>
            <input type="number" id="dieselPrice" class="desk-form-control" step="0.01" min="1" value="348.00" required>
          </div>
          <div class="desk-form-group">
            <label class="desk-form-label">Total Procured Volume (Litres)</label>
            <input type="number" id="dieselLitres" class="desk-form-control" step="0.01" min="0" value="33000" required>
          </div>
        </div>
        <div class="desk-form-group">
          <label class="desk-form-label">Price Trend</label>
          <select id="dieselTrend" class="desk-form-control">
            <option value="Stable">Stable</option>
            <option value="Increased">Increased (+)</option>
            <option value="Decreased">Decreased (-)</option>
          </select>
        </div>
        <div class="desk-modal-footer">
          <button type="button" class="desk-btn desk-btn-default" onclick="Desk.closeModal('modalNewDiesel')">Cancel</button>
          <button type="submit" class="desk-btn desk-btn-primary">Save Fuel Price Log</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- ==============================================================
     MODAL 7: New CPC Fuel Consignment
     ============================================================== -->
<div id="modalNewCpc" class="desk-modal-backdrop">
  <div class="desk-modal">
    <div class="desk-modal-header">
      <h3>New CPC Fuel Consignment</h3>
      <button type="button" class="desk-modal-close" onclick="Desk.closeModal('modalNewCpc')">&times;</button>
    </div>
    <div class="desk-modal-body">
      <form id="formNewCpc" onsubmit="event.preventDefault(); submitNewCpc();">
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
          <div class="desk-form-group">
            <label class="desk-form-label">CPC Order No</label>
            <input type="text" id="cpcOrderNo" class="desk-form-control" placeholder="e.g. CPC-PO-2026-035" required>
          </div>
          <div class="desk-form-group">
            <label class="desk-form-label">Consignment Date</label>
            <input type="date" id="cpcDate" class="desk-form-control" value="<?= date('Y-m-d') ?>" required>
          </div>
        </div>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
          <div class="desk-form-group">
            <label class="desk-form-label">Fuel Grade</label>
            <select id="cpcFuelType" class="desk-form-control">
              <option value="Auto Diesel 500ppm (Industrial)">Auto Diesel 500ppm (Industrial)</option>
              <option value="Super Diesel 10ppm">Super Diesel 10ppm</option>
            </select>
          </div>
          <div class="desk-form-group">
            <label class="desk-form-label">Station Storage Tank</label>
            <input type="text" id="cpcTankId" class="desk-form-control" value="TANK-01 (Underground Bulk)" required>
          </div>
        </div>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
          <div class="desk-form-group">
            <label class="desk-form-label">Quantity (Litres)</label>
            <input type="number" id="cpcLitres" class="desk-form-control" step="0.01" value="33000" required>
          </div>
          <div class="desk-form-group">
            <label class="desk-form-label">Unit Price / Litre (LKR)</label>
            <input type="number" id="cpcRate" class="desk-form-control" step="0.01" value="348.00" required>
          </div>
        </div>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
          <div class="desk-form-group">
            <label class="desk-form-label">Bowser Registration No</label>
            <input type="text" id="cpcBowserNo" class="desk-form-control" placeholder="e.g. WP-LY-9812">
          </div>
          <div class="desk-form-group">
            <label class="desk-form-label">Delivery Note / Invoice Ref</label>
            <input type="text" id="cpcDeliveryRef" class="desk-form-control" placeholder="e.g. CPC/DEL/81200" required>
          </div>
        </div>
        <div class="desk-modal-footer">
          <button type="button" class="desk-btn desk-btn-default" onclick="Desk.closeModal('modalNewCpc')">Cancel</button>
          <button type="submit" class="desk-btn desk-btn-primary">Record Fuel Consignment</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- ==============================================================
     MODAL 8: Edit Material Issue Document
     ============================================================== -->
<div id="modalEditIssue" class="desk-modal-backdrop">
  <div class="desk-modal">
    <div class="desk-modal-header">
      <h3 id="editIssueModalTitle">Edit Material Issue Document</h3>
      <button type="button" class="desk-modal-close" onclick="Desk.closeModal('modalEditIssue')">&times;</button>
    </div>
    <div class="desk-modal-body">
      <form id="formEditIssue" onsubmit="event.preventDefault(); submitEditIssue();">
        <input type="hidden" id="editDocId">
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
          <div class="desk-form-group">
            <label class="desk-form-label">Document Number</label>
            <input type="text" id="editDocNo" class="desk-form-control" readonly style="background: #f1f5f9; font-weight: 600;">
          </div>
          <div class="desk-form-group">
            <label class="desk-form-label">Current Status</label>
            <input type="text" id="editDocStatus" class="desk-form-control" readonly style="background: #f1f5f9;">
          </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
          <div class="desk-form-group">
            <label class="desk-form-label">Destination Code / Job Work Area</label>
            <select id="editDocDest" class="desk-form-control" required>
              <option value="DEST-GEN-U1">DEST-GEN-U1 - Unit 01 Francis Turbine & Generator</option>
              <option value="DEST-GEN-U2">DEST-GEN-U2 - Unit 02 Francis Turbine & Generator</option>
              <option value="DEST-GEN-U3">DEST-GEN-U3 - Unit 03 Francis Turbine & Generator</option>
              <option value="DEST-PEN-01">DEST-PEN-01 - Main Penstock Intake Valve 01</option>
              <option value="DEST-PEN-02">DEST-PEN-02 - Main Penstock Intake Valve 02</option>
              <option value="DEST-SWG-220KV">DEST-SWG-220KV - 220kV Outdoor Switchyard Bay</option>
              <option value="DEST-DAM-SP01">DEST-DAM-SP01 - Radial Spillway Gate No. 01</option>
            </select>
          </div>
          <div class="desk-form-group">
            <label class="desk-form-label">Requesting Department</label>
            <select id="editDocDept" class="desk-form-control" required>
              <option value="ELEC-GEN">Electrical Generation & Control</option>
              <option value="MECH-MAINT">Mechanical Maintenance</option>
              <option value="CIVIL-DAM">Civil & Dam Safety</option>
              <option value="STORES-01">Central Stores & Logistics</option>
            </select>
          </div>
        </div>

        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 12px;">
          <div class="desk-form-group">
            <label class="desk-form-label">Material Line Item</label>
            <input type="text" id="editDocItemName" class="desk-form-control" readonly style="background: #f1f5f9;">
          </div>
          <div class="desk-form-group">
            <label class="desk-form-label">Issue Quantity</label>
            <input type="number" id="editDocQty" class="desk-form-control" min="1" required>
          </div>
        </div>

        <div class="desk-form-group">
          <label class="desk-form-label">Work Order / Purpose / Remarks</label>
          <textarea id="editDocRemarks" class="desk-form-control" rows="3"></textarea>
        </div>

        <div class="desk-modal-footer">
          <button type="button" class="desk-btn desk-btn-default" onclick="Desk.closeModal('modalEditIssue')">Cancel</button>
          <button type="submit" class="desk-btn desk-btn-primary">Save Document Changes</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- ==============================================================
     MODAL 9: View / Inspect Document Details
     ============================================================== -->
<div id="modalViewDoc" class="desk-modal-backdrop">
  <div class="desk-modal" style="max-width: 750px;">
    <div class="desk-modal-header">
      <h3 id="viewDocModalTitle">Document Details</h3>
      <button type="button" class="desk-modal-close" onclick="Desk.closeModal('modalViewDoc')">&times;</button>
    </div>
    <div class="desk-modal-body">
      <div id="viewDocContent">Loading...</div>
    </div>
    <div class="desk-modal-footer">
      <button type="button" class="desk-btn desk-btn-default" onclick="Desk.closeModal('modalViewDoc')">Close</button>
    </div>
  </div>
</div>

<script>
// Modal Form Submit Handlers
function submitNewGrn() {
  const fd = new FormData();
  fd.append('action', 'create_document');
  fd.append('doc_type', 'GRN');
  fd.append('receipt_from', document.getElementById('grnSupplier').value);
  fd.append('remarks', document.getElementById('grnRemarks').value);
  
  const items = [{
    sku: document.getElementById('grnSku').value,
    item_name: document.getElementById('grnName').value,
    quantity: document.getElementById('grnQty').value,
    unit_cost: document.getElementById('grnCost').value,
    bin_location: document.getElementById('grnBin').value
  }];
  fd.append('items', JSON.stringify(items));

  fetch(`${Desk.baseUrl}/api/inventory.php`, { method: 'POST', body: fd })
    .then(r => r.json())
    .then(r => {
      if (r.success) {
        Desk.showToast(r.message);
        Desk.closeModal('modalNewGrn');
        Desk.switchTab('tab-receipts');
        Desk.loadReceipts();
      } else {
        Desk.showToast(r.message, true);
      }
    })
    .catch(err => {
      Desk.showToast('Network error while saving GRN', true);
    });
}

function submitNewIssue() {
  const skuSelect = document.getElementById('issueSku');
  const itemName = skuSelect.options[skuSelect.selectedIndex].text.split(' - ')[1] || 'Spare Item';

  const fd = new FormData();
  fd.append('action', 'create_document');
  fd.append('doc_type', 'ISSUE');
  fd.append('destination_code', document.getElementById('issueDest').value);
  fd.append('department_code', document.getElementById('issueDept').value);
  fd.append('remarks', document.getElementById('issueRemarks').value);
  
  const items = [{
    sku: skuSelect.value,
    item_name: itemName,
    quantity: document.getElementById('issueQty').value,
    unit_cost: 0
  }];
  fd.append('items', JSON.stringify(items));

  fetch(`${Desk.baseUrl}/api/inventory.php`, { method: 'POST', body: fd })
    .then(r => r.json())
    .then(r => {
      if (r.success) {
        Desk.showToast(r.message);
        Desk.closeModal('modalNewIssue');
        Desk.switchTab('tab-issues');
        Desk.loadIssues();
      } else {
        Desk.showToast(r.message, true);
      }
    })
    .catch(err => {
      Desk.showToast('Network error while saving Issue', true);
    });
}

function submitCashVoucher() {
  const fd = new FormData();
  fd.append('action', 'add_entry');
  fd.append('entry_type', document.getElementById('cashType').value);
  fd.append('amount', document.getElementById('cashAmount').value);
  fd.append('account_title', document.getElementById('cashTitle').value);
  fd.append('payee_or_source', document.getElementById('cashPayee').value);
  fd.append('description', document.getElementById('cashDesc').value);

  fetch(`${Desk.baseUrl}/api/cashbook.php`, { method: 'POST', body: fd })
    .then(r => r.json())
    .then(r => {
      if (r.success) {
        Desk.showToast(r.message);
        Desk.closeModal('modalNewCashVoucher');
        Desk.loadCashBook();
      } else {
        Desk.showToast(r.message, true);
      }
    });
}

function submitPayslipCancel() {
  const fd = new FormData();
  fd.append('action', 'request_payslip_cancellation');
  fd.append('payslip_no', document.getElementById('paySlipNo').value);
  fd.append('epf_no', document.getElementById('payEpf').value);
  fd.append('employee_name', document.getElementById('payEmpName').value);
  fd.append('amount', document.getElementById('payAmount').value);
  fd.append('reason', document.getElementById('payReason').value);

  fetch(`${Desk.baseUrl}/api/cashbook.php`, { method: 'POST', body: fd })
    .then(r => r.json())
    .then(r => {
      if (r.success) {
        Desk.showToast(r.message);
        Desk.closeModal('modalPayslipCancel');
        Desk.loadPayslipCancellations();
      } else {
        Desk.showToast(r.message, true);
      }
    });
}

function submitCostAdjust() {
  const fd = new FormData();
  fd.append('action', 'add_cost_adjustment');
  fd.append('batch_id', document.getElementById('costAdjustBatch').value);
  fd.append('new_unit_cost', document.getElementById('costAdjustNewCost').value);
  fd.append('reason', document.getElementById('costAdjustReason').value);

  fetch(`${Desk.baseUrl}/api/tasks.php`, { method: 'POST', body: fd })
    .then(r => r.json())
    .then(r => {
      if (r.success) {
        Desk.showToast(r.message);
        Desk.closeModal('modalCostAdjust');
        Desk.loadCostAdjustments();
      } else {
        Desk.showToast(r.message, true);
      }
    });
}

function submitNewDiesel() {
  const fd = new FormData();
  fd.append('action', 'add_diesel_price');
  fd.append('effective_month', document.getElementById('dieselMonth').value);
  fd.append('fuel_type', document.getElementById('dieselFuelType').value);
  fd.append('avg_price_per_litre', document.getElementById('dieselPrice').value);
  fd.append('total_procured_litres', document.getElementById('dieselLitres').value);
  fd.append('price_trend', document.getElementById('dieselTrend').value);

  fetch(`${Desk.baseUrl}/api/reports.php`, { method: 'POST', body: fd })
    .then(r => r.json())
    .then(r => {
      if (r.success) {
        Desk.showToast(r.message);
        Desk.closeModal('modalNewDiesel');
        Desk.loadDieselPriceLogs();
      } else {
        Desk.showToast(r.message, true);
      }
    });
}

function submitNewCpc() {
  const fd = new FormData();
  fd.append('action', 'add_cpc_procurement');
  fd.append('order_no', document.getElementById('cpcOrderNo').value);
  fd.append('consignment_date', document.getElementById('cpcDate').value);
  fd.append('fuel_type', document.getElementById('cpcFuelType').value);
  fd.append('tank_id', document.getElementById('cpcTankId').value);
  fd.append('quantity_litres', document.getElementById('cpcLitres').value);
  fd.append('unit_price_lkr', document.getElementById('cpcRate').value);
  fd.append('bowser_no', document.getElementById('cpcBowserNo').value);
  fd.append('delivery_ref', document.getElementById('cpcDeliveryRef').value);

  fetch(`${Desk.baseUrl}/api/reports.php`, { method: 'POST', body: fd })
    .then(r => r.json())
    .then(r => {
      if (r.success) {
        Desk.showToast(r.message);
        Desk.closeModal('modalNewCpc');
        Desk.loadCpcProcurements();
      } else {
        Desk.showToast(r.message, true);
      }
    });
}

function submitEditIssue() {
  const fd = new FormData();
  fd.append('action', 'update_document');
  fd.append('id', document.getElementById('editDocId').value);
  fd.append('destination_code', document.getElementById('editDocDest').value);
  fd.append('department_code', document.getElementById('editDocDept').value);
  fd.append('quantity', document.getElementById('editDocQty').value);
  fd.append('remarks', document.getElementById('editDocRemarks').value);

  fetch(`${Desk.baseUrl}/api/inventory.php`, { method: 'POST', body: fd })
    .then(r => r.json())
    .then(r => {
      if (r.success) {
        Desk.showToast(r.message);
        Desk.closeModal('modalEditIssue');
        Desk.loadIssues();
      } else {
        Desk.showToast(r.message, true);
      }
    });
}
</script>

