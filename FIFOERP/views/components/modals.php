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
        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 12px; margin-bottom: 12px;">
          <div class="desk-form-group">
            <label class="desk-form-label">Select Material (SKU) *</label>
            <select id="issueSku" class="desk-form-control" onchange="Desk.checkIssueStockAvailability(this.value)" required>
              <option value="LUB-VG46-200L">LUB-VG46-200L - Turbine Lubricating Oil Shell Turbo T 46</option>
              <option value="HYD-HOSE-2IN">HYD-HOSE-2IN - High Pressure Hydraulic Hose 2" 350 Bar</option>
              <option value="GRS-SHL-18KG">GRS-SHL-18KG - Generator Bearing Grease Shell Gadus S2</option>
              <option value="FAN-TR-415V">FAN-TR-415V - Transformer Cooling Fan Motor 415V</option>
              <option value="BRU-ALT-SET">BRU-ALT-SET - Carbon Brushes Set for Alternator</option>
              <option value="VAL-PRV-100B">VAL-PRV-100B - Pressure Relief Valve 100 Bar</option>
            </select>
          </div>
          <div class="desk-form-group">
            <label class="desk-form-label">Quantity to Issue *</label>
            <input type="number" id="issueQty" class="desk-form-control" min="1" value="1" oninput="Desk.validateIssueQty()" required>
          </div>
        </div>

        <!-- Live Three-Tier Stock Availability & Reservation Card -->
        <div id="issueStockStatusCard" style="background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 8px; padding: 10px 14px; margin-bottom: 14px; display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 8px; text-align: center;">
          <div>
            <div style="font-size: 0.72rem; color: #64748b; font-weight: 700; text-transform: uppercase;">1. Physical On-Hand</div>
            <div id="issueStockPhysical" style="font-size: 1.15rem; font-weight: 800; color: #0f172a; margin-top: 2px;">--</div>
          </div>
          <div style="border-left: 1px solid #e2e8f0;">
            <div style="font-size: 0.72rem; color: #dc2626; font-weight: 700; text-transform: uppercase;">2. Pending Reserved</div>
            <div id="issueStockAllocated" style="font-size: 1.15rem; font-weight: 800; color: #dc2626; margin-top: 2px;">--</div>
          </div>
          <div style="border-left: 1px solid #e2e8f0;">
            <div style="font-size: 0.72rem; color: #059669; font-weight: 700; text-transform: uppercase;">3. Available to Issue</div>
            <div id="issueStockAvailable" style="font-size: 1.15rem; font-weight: 800; color: #059669; margin-top: 2px;">--</div>
          </div>
        </div>

        <div id="issueStockAlert" style="display: none; padding: 8px 12px; background: #fff1f2; border: 1px solid #fecdd3; border-radius: 6px; color: #be123c; font-size: 0.8rem; margin-bottom: 12px; font-weight: 600;"></div>
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
     MODAL: Edit Unposted Goods Received Note (GRN)
     ============================================================== -->
<div id="modalEditReceipt" class="desk-modal-backdrop">
  <div class="desk-modal">
    <div class="desk-modal-header">
      <h3 id="editReceiptModalTitle">Edit Unposted Goods Received Note</h3>
      <button type="button" class="desk-modal-close" onclick="Desk.closeModal('modalEditReceipt')">&times;</button>
    </div>
    <div class="desk-modal-body">
      <form id="formEditReceipt" onsubmit="event.preventDefault(); submitEditReceipt();">
        <input type="hidden" id="editReceiptDocId">
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
          <div class="desk-form-group">
            <label class="desk-form-label">Document Number</label>
            <input type="text" id="editReceiptDocNo" class="desk-form-control" readonly style="background: #f1f5f9; font-weight: 600;">
          </div>
          <div class="desk-form-group">
            <label class="desk-form-label">Current Status</label>
            <input type="text" id="editReceiptDocStatus" class="desk-form-control" readonly style="background: #f1f5f9;">
          </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
          <div class="desk-form-group">
            <label class="desk-form-label">Supplier / Source</label>
            <input type="text" id="editReceiptSupplier" class="desk-form-control" required>
          </div>
          <div class="desk-form-group">
            <label class="desk-form-label">Warehouse Bay</label>
            <input type="text" id="editReceiptWarehouse" class="desk-form-control" required>
          </div>
        </div>

        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 12px;">
          <div class="desk-form-group">
            <label class="desk-form-label">Material Line Item</label>
            <input type="text" id="editReceiptItemName" class="desk-form-control" readonly style="background: #f1f5f9;">
          </div>
          <div class="desk-form-group">
            <label class="desk-form-label">Received Quantity</label>
            <input type="number" id="editReceiptQty" class="desk-form-control" min="1" required>
          </div>
        </div>

        <div class="desk-form-group">
          <label class="desk-form-label">Delivery Notes / Remarks</label>
          <textarea id="editReceiptRemarks" class="desk-form-control" rows="3"></textarea>
        </div>

        <div class="desk-modal-footer">
          <button type="button" class="desk-btn desk-btn-default" onclick="Desk.closeModal('modalEditReceipt')">Cancel</button>
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

<!-- ==============================================================
     MODAL: Search & Select Master Item Code
     ============================================================== -->
<div id="modalSearchItemCode" class="desk-modal-backdrop">
  <div class="desk-modal" style="max-width: 750px; width: 95%;">
    <div class="desk-modal-header">
      <h3 id="searchItemModalTitle">Search Master Item Code</h3>
      <button type="button" class="desk-modal-close" onclick="Desk.closeModal('modalSearchItemCode')">&times;</button>
    </div>
    <div class="desk-modal-body" style="padding: 16px 20px;">
      <div style="display: flex; gap: 10px; margin-bottom: 12px;">
        <div style="position: relative; flex: 1;">
          <input type="text" id="masterItemSearchInput" class="desk-form-control" placeholder="Search by SKU / Item Code or Description..." oninput="Desk.filterMasterItemsModal(this.value)">
          <span style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); color: #94a3b8; pointer-events: none;">🔍</span>
        </div>
        <button type="button" class="desk-btn desk-btn-default" onclick="Desk.filterMasterItemsModal('')">Clear</button>
      </div>

      <div style="max-height: 380px; overflow-y: auto; border: 1px solid var(--border); border-radius: 6px;">
        <table class="desk-table" style="margin: 0; font-size: 12.5px;">
          <thead style="position: sticky; top: 0; background: #f8fafc; z-index: 2;">
            <tr>
              <th style="width: 130px;">Item Code</th>
              <th>Description / Specifications</th>
              <th style="width: 80px;">UOM</th>
              <th style="width: 120px; text-align: right;">Unit Price</th>
              <th style="width: 80px; text-align: center;">Action</th>
            </tr>
          </thead>
          <tbody id="masterItemSearchResultsBody">
            <tr><td colspan="5" style="text-align: center; color: #64748b; padding: 20px;">Loading master items...</td></tr>
          </tbody>
        </table>
      </div>
      <div style="margin-top: 10px; display: flex; justify-content: space-between; align-items: center; font-size: 12px; color: #64748b;">
        <span id="masterItemSearchCount"></span>
        <span>Item not found? <button type="button" class="desk-btn desk-btn-primary desk-btn-sm" onclick="Desk.closeModal('modalSearchItemCode'); Desk.openRequestItemCodeModal(Desk.searchItemTargetContext);">+ Request New Code</button></span>
      </div>
    </div>
    <div class="desk-modal-footer">
      <button type="button" class="desk-btn desk-btn-default" onclick="Desk.closeModal('modalSearchItemCode')">Close</button>
    </div>
  </div>
</div>

<!-- ============================================================
     ADMIN MODAL 1: CREATE LOCATION
     ============================================================ -->
<div id="createLocationModal" class="desk-modal-backdrop">
  <div class="desk-modal" style="max-width: 480px;">
    <div class="desk-modal-header">
      <h3>Add Power Station / Location</h3>
      <button type="button" class="desk-modal-close" onclick="Desk.closeModal('createLocationModal')">&times;</button>
    </div>
    <form id="createLocationForm" onsubmit="event.preventDefault(); Desk.submitCreateLocation();">
      <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars(\App\Core\Session::csrfToken()) ?>">
      <div class="desk-modal-body">
        <div class="desk-form-group">
          <label>Station / Location Name *</label>
          <input type="text" id="createLocName" name="name" class="desk-form-control" placeholder="e.g. Victoria Power Station" required>
        </div>
        <div class="desk-form-row">
          <div class="desk-form-group">
            <label>Station Code *</label>
            <input type="text" id="createLocCode" name="code" class="desk-form-control" placeholder="e.g. 833 / VIC-01" required>
          </div>
          <div class="desk-form-group">
            <label>Status</label>
            <select id="createLocStatus" name="status" class="desk-form-control">
              <option value="active" selected>Active</option>
              <option value="inactive">Inactive</option>
            </select>
          </div>
        </div>
      </div>
      <div class="desk-modal-footer">
        <button type="button" class="desk-btn desk-btn-default" onclick="Desk.closeModal('createLocationModal')">Cancel</button>
        <button type="submit" class="desk-btn desk-btn-primary">Save Location</button>
      </div>
    </form>
  </div>
</div>

<!-- ============================================================
     ADMIN MODAL 2: EDIT LOCATION
     ============================================================ -->
<div id="editLocationModal" class="desk-modal-backdrop">
  <div class="desk-modal" style="max-width: 480px;">
    <div class="desk-modal-header">
      <h3>Edit Power Station / Location</h3>
      <button type="button" class="desk-modal-close" onclick="Desk.closeModal('editLocationModal')">&times;</button>
    </div>
    <form id="editLocationForm" onsubmit="event.preventDefault(); Desk.submitEditLocation();">
      <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars(\App\Core\Session::csrfToken()) ?>">
      <input type="hidden" id="editLocId" name="id">
      <div class="desk-modal-body">
        <div class="desk-form-group">
          <label>Station / Location Name *</label>
          <input type="text" id="editLocName" name="name" class="desk-form-control" required>
        </div>
        <div class="desk-form-row">
          <div class="desk-form-group">
            <label>Station Code *</label>
            <input type="text" id="editLocCode" name="code" class="desk-form-control" required>
          </div>
          <div class="desk-form-group">
            <label>Status</label>
            <select id="editLocStatus" name="status" class="desk-form-control">
              <option value="active">Active</option>
              <option value="inactive">Inactive</option>
            </select>
          </div>
        </div>
      </div>
      <div class="desk-modal-footer">
        <button type="button" class="desk-btn desk-btn-default" onclick="Desk.closeModal('editLocationModal')">Cancel</button>
        <button type="submit" class="desk-btn desk-btn-primary">Update Location</button>
      </div>
    </form>
  </div>
</div>

<!-- ============================================================
     ADMIN MODAL 3: CREATE DEPARTMENT
     ============================================================ -->
<div id="createDepartmentModal" class="desk-modal-backdrop">
  <div class="desk-modal" style="max-width: 520px;">
    <div class="desk-modal-header">
      <h3>Add Department / Store</h3>
      <button type="button" class="desk-modal-close" onclick="Desk.closeModal('createDepartmentModal')">&times;</button>
    </div>
    <form id="createDepartmentForm" onsubmit="event.preventDefault(); Desk.submitCreateDepartment();">
      <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars(\App\Core\Session::csrfToken()) ?>">
      <div class="desk-modal-body">
        <div class="desk-form-group">
          <label>Power Station / Location *</label>
          <select id="createDeptLocationId" name="location_id" class="desk-form-control" required>
            <option value="" disabled selected>-- Select Station --</option>
            <?php foreach (($locations ?? []) as $loc): ?>
              <option value="<?= $loc['id'] ?>"><?= htmlspecialchars($loc['name']) ?> (<?= htmlspecialchars($loc['code']) ?>)</option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="desk-form-group">
          <label>Department / Store Name *</label>
          <input type="text" id="createDeptName" name="name" class="desk-form-control" placeholder="e.g. Electrical Generation" required>
        </div>
        <div class="desk-form-group">
          <label>Status</label>
          <select id="createDeptStatus" name="status" class="desk-form-control">
            <option value="active" selected>Active</option>
            <option value="inactive">Inactive</option>
          </select>
        </div>
      </div>
      <div class="desk-modal-footer">
        <button type="button" class="desk-btn desk-btn-default" onclick="Desk.closeModal('createDepartmentModal')">Cancel</button>
        <button type="submit" class="desk-btn desk-btn-primary">Save Department</button>
      </div>
    </form>
  </div>
</div>

<!-- ============================================================
     ADMIN MODAL 4: EDIT DEPARTMENT
     ============================================================ -->
<div id="editDepartmentModal" class="desk-modal-backdrop">
  <div class="desk-modal" style="max-width: 520px;">
    <div class="desk-modal-header">
      <h3>Edit Department / Store</h3>
      <button type="button" class="desk-modal-close" onclick="Desk.closeModal('editDepartmentModal')">&times;</button>
    </div>
    <form id="editDepartmentForm" onsubmit="event.preventDefault(); Desk.submitEditDepartment();">
      <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars(\App\Core\Session::csrfToken()) ?>">
      <input type="hidden" id="editDeptId" name="id">
      <div class="desk-modal-body">
        <div class="desk-form-group">
          <label>Power Station / Location *</label>
          <select id="editDeptLocationId" name="location_id" class="desk-form-control" required>
            <?php foreach (($locations ?? []) as $loc): ?>
              <option value="<?= $loc['id'] ?>"><?= htmlspecialchars($loc['name']) ?> (<?= htmlspecialchars($loc['code']) ?>)</option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="desk-form-group">
          <label>Department / Store Name *</label>
          <input type="text" id="editDeptName" name="name" class="desk-form-control" required>
        </div>
        <div class="desk-form-row">
          <div class="desk-form-group">
            <label>Department Code *</label>
            <input type="text" id="editDeptCode" name="code" class="desk-form-control" required>
          </div>
          <div class="desk-form-group">
            <label>Status</label>
            <select id="editDeptStatus" name="status" class="desk-form-control">
              <option value="active">Active</option>
              <option value="inactive">Inactive</option>
            </select>
          </div>
        </div>
      </div>
      <div class="desk-modal-footer">
        <button type="button" class="desk-btn desk-btn-default" onclick="Desk.closeModal('editDepartmentModal')">Cancel</button>
        <button type="submit" class="desk-btn desk-btn-primary">Update Department</button>
      </div>
    </form>
  </div>
</div>

<!-- ============================================================
     ADMIN MODAL 5: CREATE USER
     ============================================================ -->
<div id="createUserModal" class="desk-modal-backdrop">
  <div class="desk-modal" style="max-width: 540px;">
    <div class="desk-modal-header">
      <h3>Add New User Account</h3>
      <button type="button" class="desk-modal-close" onclick="Desk.closeModal('createUserModal')">&times;</button>
    </div>
    <form id="createUserForm" onsubmit="event.preventDefault(); Desk.submitCreateUser();" style="display: flex; flex-direction: column; flex: 1; min-height: 0; margin: 0;">
      <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars(\App\Core\Session::csrfToken()) ?>">
      <div class="desk-modal-body" style="overflow-y: auto; flex: 1; min-height: 0; padding: 20px;">
        <div class="desk-form-row">
          <div class="desk-form-group">
            <label>Power Station / Location *</label>
            <select id="createUserLocId" name="location_id" class="desk-form-control" required onchange="Desk.onStationSelectChange(this.value, 'createUserDept')">
              <option value="" disabled selected>-- Select Station --</option>
              <?php foreach (($locations ?? []) as $loc): ?>
                <option value="<?= $loc['id'] ?>"><?= htmlspecialchars($loc['name']) ?> (<?= htmlspecialchars($loc['code']) ?>)</option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="desk-form-group">
            <label>Department / Store *</label>
            <select id="createUserDept" name="department" class="desk-form-control" required>
              <option value="" disabled selected>-- Select Station First --</option>
            </select>
          </div>
        </div>

        <div class="desk-form-row">
          <div class="desk-form-group">
            <label>User Role / Designation *</label>
            <select id="createUserRoleId" name="role_id" class="desk-form-control" required>
              <option value="" disabled selected>-- Select Role --</option>
              <?php foreach (($allRoles ?? []) as $r): ?>
                <?php if ($r['name'] !== 'super_admin' && (int)$r['id'] !== 1): ?>
                  <option value="<?= $r['id'] ?>"><?= htmlspecialchars($r['display_name']) ?></option>
                <?php endif; ?>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="desk-form-group">
            <label>Account Status</label>
            <select id="createUserStatus" name="status" class="desk-form-control">
              <option value="active" selected>Active</option>
              <option value="pending">Pending</option>
              <option value="suspended">Suspended</option>
            </select>
          </div>
        </div>

        <div class="desk-form-row">
          <div class="desk-form-group">
            <label>Username *</label>
            <input type="text" id="createUserUsername" name="username" class="desk-form-control" placeholder="e.g. 831sk" required>
          </div>
          <div class="desk-form-group">
            <label>Initial Password *</label>
            <div class="password-input-wrap">
              <input type="password" id="createUserPassword" name="password" class="desk-form-control" placeholder="Minimum 6 characters" required>
              <button type="button" class="password-toggle-btn" title="Show password" onclick="Desk.togglePassword('createUserPassword', this)">
                <?= \App\Helpers\Icon::get('eye', 16) ?>
              </button>
            </div>
          </div>
        </div>
      </div>
      <div class="desk-modal-footer" style="flex-shrink: 0; padding: 14px 20px; display: flex; justify-content: flex-end; gap: 10px; background: #f8fafc; border-top: 1px solid #e2e8f0;">
        <button type="button" class="desk-btn desk-btn-default" onclick="Desk.closeModal('createUserModal')">Cancel</button>
        <button type="submit" class="desk-btn desk-btn-primary">+ Create User</button>
      </div>
    </form>
  </div>
</div>

<!-- ============================================================
     ADMIN MODAL 6: EDIT USER
     ============================================================ -->
<div id="editUserModal" class="desk-modal-backdrop">
  <div class="desk-modal" style="max-width: 540px;">
    <div class="desk-modal-header">
      <h3>Edit User Account</h3>
      <button type="button" class="desk-modal-close" onclick="Desk.closeModal('editUserModal')">&times;</button>
    </div>
    <form id="editUserForm" onsubmit="event.preventDefault(); Desk.submitEditUser();" style="display: flex; flex-direction: column; flex: 1; min-height: 0; margin: 0;">
      <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars(\App\Core\Session::csrfToken()) ?>">
      <input type="hidden" id="editUserId" name="id">
      <div class="desk-modal-body" style="overflow-y: auto; flex: 1; min-height: 0; padding: 20px;">
        <div class="desk-form-row">
          <div class="desk-form-group">
            <label>Power Station / Location *</label>
            <select id="editUserLocId" name="location_id" class="desk-form-control" required onchange="Desk.onStationSelectChange(this.value, 'editUserDept')">
              <?php foreach (($locations ?? []) as $loc): ?>
                <option value="<?= $loc['id'] ?>"><?= htmlspecialchars($loc['name']) ?> (<?= htmlspecialchars($loc['code']) ?>)</option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="desk-form-group">
            <label>Department / Store *</label>
            <select id="editUserDept" name="department" class="desk-form-control" required>
              <option value="" disabled selected>-- Select Department --</option>
            </select>
          </div>
        </div>

        <div class="desk-form-row">
          <div class="desk-form-group">
            <label>User Role / Designation *</label>
            <select id="editUserRoleId" name="role_id" class="desk-form-control" required>
              <?php foreach (($allRoles ?? []) as $r): ?>
                <option value="<?= $r['id'] ?>"><?= htmlspecialchars($r['display_name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="desk-form-group">
            <label>Account Status</label>
            <select id="editUserStatus" name="status" class="desk-form-control">
              <option value="active">Active</option>
              <option value="pending">Pending</option>
              <option value="suspended">Suspended</option>
            </select>
          </div>
        </div>

        <div class="desk-form-row">
          <div class="desk-form-group">
            <label>Username</label>
            <input type="text" id="editUserUsername" name="username" class="desk-form-control" readonly style="background: #f1f5f9; cursor: not-allowed;">
          </div>
          <div class="desk-form-group">
            <label>Reset Password (leave empty to keep)</label>
            <div class="password-input-wrap">
              <input type="password" id="editUserPassword" name="password" class="desk-form-control" placeholder="New password">
              <button type="button" class="password-toggle-btn" title="Show password" onclick="Desk.togglePassword('editUserPassword', this)">
                <?= \App\Helpers\Icon::get('eye', 16) ?>
              </button>
            </div>
          </div>
        </div>
      </div>
      <div class="desk-modal-footer" style="flex-shrink: 0; padding: 14px 20px; display: flex; justify-content: flex-end; gap: 10px; background: #f8fafc; border-top: 1px solid #e2e8f0;">
        <button type="button" class="desk-btn desk-btn-default" onclick="Desk.closeModal('editUserModal')">Cancel</button>
        <button type="submit" class="desk-btn desk-btn-primary">Save Changes</button>
      </div>
    </form>
  </div>
</div>

<!-- ============================================================
     ADMIN MODAL 7: CREATE ROLE
     ============================================================ -->
<div id="createRoleModal" class="desk-modal-backdrop">
  <div class="desk-modal" style="max-width: 680px; max-height: 90vh; display: flex; flex-direction: column;">
    <div class="desk-modal-header">
      <h3>Define New Role &amp; Permissions</h3>
      <button type="button" class="desk-modal-close" onclick="Desk.closeModal('createRoleModal')">&times;</button>
    </div>
    <form id="createRoleForm" onsubmit="event.preventDefault(); Desk.submitCreateRole();" style="display: flex; flex-direction: column; overflow: hidden; flex: 1;">
      <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars(\App\Core\Session::csrfToken()) ?>">
      <input type="hidden" name="_save_permissions" value="1">
      <div class="desk-modal-body" style="overflow-y: auto; max-height: calc(90vh - 140px);">
        <div class="desk-form-group">
          <label>Role Name / Designation *</label>
          <input type="text" id="createRoleDisplayName" name="display_name" class="desk-form-control" placeholder="e.g. Assistant Storekeeper" required>
        </div>

        <div style="margin: 16px 0 10px; display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #e2e8f0; padding-bottom: 8px;">
          <div>
            <strong style="font-size: 0.88rem; color: #0f172a;">Fixed Tab Access Permissions</strong>
            <div style="font-size: 0.74rem; color: #64748b;">Set which navigation tabs are enabled (ON) for this role</div>
          </div>
          <div style="display: flex; gap: 8px;">
            <button type="button" class="desk-btn desk-btn-default desk-btn-sm" onclick="Desk.toggleAllRolePerms('createRoleForm', true)">Select All</button>
            <button type="button" class="desk-btn desk-btn-default desk-btn-sm" onclick="Desk.toggleAllRolePerms('createRoleForm', false)">Deselect All</button>
          </div>
        </div>

        <div style="display: flex; flex-direction: column; gap: 12px;">
          <?php foreach (($permissionCategories ?? []) as $categoryName => $perms): ?>
            <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 10px 12px;">
              <div style="font-size: 0.78rem; font-weight: 700; color: #650b2b; text-transform: uppercase; margin-bottom: 8px;"><?= htmlspecialchars($categoryName) ?></div>
              <div style="display: grid; grid-template-columns: 1fr; gap: 6px;">
                <?php foreach ($perms as $permKey => $permMeta): ?>
                  <div style="display: flex; justify-content: space-between; align-items: center; background: #ffffff; padding: 6px 10px; border-radius: 4px; border: 1px solid #e2e8f0;">
                    <div>
                      <div style="font-weight: 600; font-size: 0.82rem; color: #1e293b;"><?= htmlspecialchars($permMeta['label']) ?></div>
                    </div>
                    <label class="matrix-switch" style="margin: 0; flex-shrink: 0;">
                      <input type="checkbox" name="permissions[<?= htmlspecialchars($permKey) ?>]" value="1" class="create-role-perm" checked>
                      <span class="matrix-slider"></span>
                    </label>
                  </div>
                <?php endforeach; ?>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
      <div class="desk-modal-footer">
        <button type="button" class="desk-btn desk-btn-default" onclick="Desk.closeModal('createRoleModal')">Cancel</button>
        <button type="submit" class="desk-btn desk-btn-primary">Define Role</button>
      </div>
    </form>
  </div>
</div>

<!-- ============================================================
     ADMIN MODAL 8: EDIT ROLE
     ============================================================ -->
<div id="editRoleModal" class="desk-modal-backdrop">
  <div class="desk-modal" style="max-width: 680px; max-height: 90vh; display: flex; flex-direction: column;">
    <div class="desk-modal-header">
      <h3>Edit Role &amp; Tab Permissions</h3>
      <button type="button" class="desk-modal-close" onclick="Desk.closeModal('editRoleModal')">&times;</button>
    </div>
    <form id="editRoleForm" onsubmit="event.preventDefault(); Desk.submitEditRole();" style="display: flex; flex-direction: column; overflow: hidden; flex: 1;">
      <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars(\App\Core\Session::csrfToken()) ?>">
      <input type="hidden" name="_save_permissions" value="1">
      <input type="hidden" id="editRoleId" name="id">
      <div class="desk-modal-body" style="overflow-y: auto; max-height: calc(90vh - 140px);">
        <div class="desk-form-group">
          <label>Role Name / Designation *</label>
          <input type="text" id="editRoleDisplayName" name="display_name" class="desk-form-control" required>
        </div>

        <div style="margin: 16px 0 10px; display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #e2e8f0; padding-bottom: 8px;">
          <div>
            <strong style="font-size: 0.88rem; color: #0f172a;">Fixed Tab Access Permissions</strong>
            <div style="font-size: 0.74rem; color: #64748b;">Configure which tabs are enabled (ON) for this role</div>
          </div>
          <div style="display: flex; gap: 8px;">
            <button type="button" class="desk-btn desk-btn-default desk-btn-sm" onclick="Desk.toggleAllRolePerms('editRoleForm', true)">Select All</button>
            <button type="button" class="desk-btn desk-btn-default desk-btn-sm" onclick="Desk.toggleAllRolePerms('editRoleForm', false)">Deselect All</button>
          </div>
        </div>

        <div style="display: flex; flex-direction: column; gap: 12px;">
          <?php foreach (($permissionCategories ?? []) as $categoryName => $perms): ?>
            <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 10px 12px;">
              <div style="font-size: 0.78rem; font-weight: 700; color: #650b2b; text-transform: uppercase; margin-bottom: 8px;"><?= htmlspecialchars($categoryName) ?></div>
              <div style="display: grid; grid-template-columns: 1fr; gap: 6px;">
                <?php foreach ($perms as $permKey => $permMeta): ?>
                  <div style="display: flex; justify-content: space-between; align-items: center; background: #ffffff; padding: 6px 10px; border-radius: 4px; border: 1px solid #e2e8f0;">
                    <div>
                      <div style="font-weight: 600; font-size: 0.82rem; color: #1e293b;"><?= htmlspecialchars($permMeta['label']) ?></div>
                    </div>
                    <label class="matrix-switch" style="margin: 0; flex-shrink: 0;">
                      <input type="checkbox" name="permissions[<?= htmlspecialchars($permKey) ?>]" data-perm-key="<?= htmlspecialchars($permKey) ?>" value="1" class="edit-role-perm" checked>
                      <span class="matrix-slider"></span>
                    </label>
                  </div>
                <?php endforeach; ?>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
      <div class="desk-modal-footer">
        <button type="button" class="desk-btn desk-btn-default" onclick="Desk.closeModal('editRoleModal')">Cancel</button>
        <button type="submit" class="desk-btn desk-btn-primary">Update Role</button>
      </div>
    </form>
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

function submitEditReceipt() {
  const fd = new FormData();
  fd.append('action', 'update_document');
  fd.append('id', document.getElementById('editReceiptDocId').value);
  fd.append('receipt_from', document.getElementById('editReceiptSupplier').value);
  fd.append('destination_code', document.getElementById('editReceiptWarehouse').value);
  fd.append('quantity', document.getElementById('editReceiptQty').value);
  fd.append('remarks', document.getElementById('editReceiptRemarks').value);

  fetch(`${Desk.baseUrl}/api/inventory.php`, { method: 'POST', body: fd })
    .then(r => r.json())
    .then(r => {
      if (r.success) {
        Desk.showToast(r.message);
        Desk.closeModal('modalEditReceipt');
        Desk.loadReceipts();
      } else {
        Desk.showToast(r.message, true);
      }
    });
}
</script>

