  <div class="modal-backdrop" id="viewItemDetailsModal" style="display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.55); align-items: center; justify-content: center; z-index: 10050;" onclick="if (event.target === this) window.closeViewItemModal();">
    <div class="modal-dialog" style="width: 860px; max-width: 95vw; max-height: 92vh; background: #ffffff; border-radius: 10px; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.35); display: flex; flex-direction: column; overflow: hidden; border: 1px solid #cbd5e1;">
      
      <!-- Modal Header -->
      <div class="modal-header" style="background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%); border-bottom: 1px solid #cbd5e1; padding: 12px 18px; display: flex; justify-content: space-between; align-items: center;">
        <div style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">
          <span style="font-size: 0.74rem; font-weight: 800; color: #64748b; text-transform: uppercase; letter-spacing: 0.8px;">
            CATALOG ITEM SPECIFICATION
          </span>
          <span id="viewModalFormattedCode" style="font-family: monospace; font-size: 1.15rem; font-weight: 800; color: #650b2b; background: #ffffff; border: 1.5px solid #650b2b; padding: 3px 12px; border-radius: 4px; letter-spacing: 1px;">
            --
          </span>
          <span id="viewModalStatusBadge" style="font-size: 0.72rem; font-weight: 700; padding: 3px 10px; border-radius: 12px; white-space: nowrap;">
            --
          </span>
        </div>
        <button type="button" class="btn-close" data-close-modal onclick="window.closeViewItemModal();" style="background: none; border: none; font-size: 1.4rem; color: #64748b; cursor: pointer; line-height: 1; padding: 2px 6px;" title="Close Window">&times;</button>
      </div>

      <!-- Modal Body (Scrollable) -->
      <div class="modal-body" style="padding: 18px 20px; overflow-y: auto; background: #f8fafc; flex: 1; display: flex; flex-direction: column; gap: 16px;">
        
        <!-- TOP SECTION: MAXIMUM TWO IMAGES SHOWCASE -->
        <div>
          <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
            <label style="font-size: 0.74rem; font-weight: 800; color: #0f172a; text-transform: uppercase; letter-spacing: 0.5px; margin: 0; display: inline-flex; align-items: center; gap: 6px;">
              <span>🖼️ Item Images (Maximum 2 Photos)</span>
            </label>
            <span style="font-size: 0.7rem; color: #64748b;">Primary Photo &amp; Technical Diagram</span>
          </div>

          <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
            
            <!-- IMAGE 1 SLOT -->
            <div id="viewModalImg1Slot" style="background: #ffffff; border: 1.5px dashed #cbd5e1; border-radius: 8px; min-height: 190px; padding: 12px; display: flex; flex-direction: column; align-items: center; justify-content: center; position: relative; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.03);">
              <!-- Rendered via JS -->
            </div>

            <!-- IMAGE 2 SLOT -->
            <div id="viewModalImg2Slot" style="background: #ffffff; border: 1.5px dashed #cbd5e1; border-radius: 8px; min-height: 190px; padding: 12px; display: flex; flex-direction: column; align-items: center; justify-content: center; position: relative; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.03);">
              <!-- Rendered via JS -->
            </div>

          </div>
        </div>

        <!-- MIDDLE SECTION: DESCRIPTION & FULL HIERARCHY -->
        <div style="background: #ffffff; border: 1px solid #cbd5e1; border-radius: 8px; padding: 14px 16px; box-shadow: 0 1px 3px rgba(0,0,0,0.03);">
          
          <div style="margin-bottom: 12px;">
            <span style="font-size: 0.7rem; font-weight: 800; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px;">ITEM DESCRIPTION / NAME</span>
            <div id="viewModalItemName" style="font-size: 1.05rem; font-weight: 800; color: #0f172a; text-transform: uppercase; margin-top: 2px; line-height: 1.35;">
              --
            </div>
          </div>

          <div id="viewModalSpecsContainer" style="margin-bottom: 14px;">
            <span style="font-size: 0.7rem; font-weight: 800; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px;">SPECIFICATIONS &amp; PART NUMBER</span>
            <div id="viewModalSpecs" style="font-size: 0.82rem; color: #334155; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 4px; padding: 8px 10px; margin-top: 3px; font-weight: 500;">
              No additional technical specifications provided.
            </div>
          </div>

          <!-- Classification Hierarchy Stack -->
          <div>
            <span style="font-size: 0.7rem; font-weight: 800; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 6px; display: block;">CLASSIFICATION PATHWAY</span>
            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px;">
              <div style="background: #fdf2f5; border: 1px solid #fbcfe8; border-radius: 4px; padding: 6px 10px;">
                <span style="font-size: 0.65rem; font-weight: 800; color: #9f1239; text-transform: uppercase; display: block;">1ST TIER (MAIN)</span>
                <span id="viewModalTier1" style="font-size: 0.78rem; font-weight: 700; color: #650b2b; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; display: block;">--</span>
              </div>
              <div style="background: #f1f5f9; border: 1px solid #cbd5e1; border-radius: 4px; padding: 6px 10px;">
                <span style="font-size: 0.65rem; font-weight: 800; color: #475569; text-transform: uppercase; display: block;">2ND TIER (SUB-GROUP)</span>
                <span id="viewModalTier2" style="font-size: 0.78rem; font-weight: 700; color: #334155; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; display: block;">--</span>
              </div>
              <div style="background: #e0f2fe; border: 1px solid #bae6fd; border-radius: 4px; padding: 6px 10px;">
                <span style="font-size: 0.65rem; font-weight: 800; color: #0369a1; text-transform: uppercase; display: block;">3RD TIER (SPECIFIC)</span>
                <span id="viewModalTier3" style="font-size: 0.78rem; font-weight: 700; color: #0284c7; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; display: block;">--</span>
              </div>
            </div>
          </div>

        </div>

        <!-- BOTTOM SECTION: UNIT OF MEASUREMENT & PRICE (WITH DARK TYPE IF NO PRICE) -->
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
          
          <!-- UOM CARD -->
          <div style="background: #ffffff; border: 1px solid #cbd5e1; border-radius: 8px; padding: 12px 16px; display: flex; flex-direction: column; justify-content: center;">
            <span style="font-size: 0.7rem; font-weight: 800; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px;">UNIT OF MEASUREMENT (UOM)</span>
            <div style="margin-top: 4px; display: flex; align-items: baseline; gap: 8px;">
              <span id="viewModalUom" style="font-size: 1.25rem; font-weight: 800; color: #0f172a; text-transform: uppercase;">--</span>
              <span style="font-size: 0.75rem; color: #64748b;">(Standard Inventory Metric)</span>
            </div>
          </div>

          <!-- PRICE CARD (DARK TYPE IF NO PRICE ADDED) -->
          <div style="background: #ffffff; border: 1px solid #cbd5e1; border-radius: 8px; padding: 12px 16px; display: flex; flex-direction: column; justify-content: center;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px;">
              <span style="font-size: 0.7rem; font-weight: 800; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px;">CATALOG UNIT PRICE</span>
              <button type="button" id="viewModalEditPriceBtn" onclick="toggleViewItemPriceInput();" style="display: none; background: none; border: none; font-size: 0.72rem; font-weight: 700; color: #0284c7; cursor: pointer; padding: 0;">
                ✏️ Edit Price
              </button>
            </div>

            <!-- Price Display Area -->
            <div id="viewModalPriceContainer" style="margin-top: 2px;">
              <!-- Either active price or DARK TYPE badge rendered via JS -->
            </div>

            <!-- Inline Price Edit Form (Hidden by default, shown for Code Admin) -->
            <div id="viewModalPriceEditForm" style="display: none; margin-top: 8px; gap: 6px;">
              <div style="display: flex; gap: 6px; align-items: center;">
                <input type="number" step="0.01" min="0" id="viewModalPriceInput" class="form-control" placeholder="Enter Price (LKR)" style="height: 30px; font-size: 0.8rem; padding: 2px 8px;">
                <button type="button" onclick="saveViewItemPrice();" class="btn btn-sm" style="background: #650b2b; color: #ffffff; font-size: 0.72rem; font-weight: 700; height: 30px; padding: 0 10px; border: 1px solid #4a071e; white-space: nowrap;">
                  Save
                </button>
                <button type="button" onclick="toggleViewItemPriceInput(false);" class="btn btn-sm btn-secondary" style="height: 30px; font-size: 0.72rem; padding: 0 8px;">
                  Cancel
                </button>
              </div>
            </div>
          </div>

        </div>

      </div>

      <!-- Modal Footer -->
      <div class="modal-footer" style="background: #ffffff; border-top: 1px solid #cbd5e1; padding: 10px 18px; display: flex; justify-content: space-between; align-items: center;">
        <button type="button" id="viewModalGoogleBtn" class="btn btn-sm" style="background: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe; font-size: 0.76rem; font-weight: 700; padding: 6px 12px; border-radius: 4px; cursor: pointer; display: inline-flex; align-items: center; gap: 5px;">
          🌐 Search Datasheet on Google
        </button>

        <div style="display: flex; gap: 8px; align-items: center;">
          <button type="button" id="viewModalActionBtn" class="btn btn-sm" style="display: none; font-size: 0.76rem; font-weight: 700; padding: 6px 14px; border-radius: 4px;">
            --
          </button>
          <button type="button" class="btn btn-secondary btn-sm" data-close-modal onclick="window.closeViewItemModal();" style="padding: 6px 14px; font-weight: 600; font-size: 0.78rem;">
            Close
          </button>
        </div>
      </div>

    </div>
  </div>

  <!-- Hidden File Input for uploading Image 1 & Image 2 -->
  <input type="file" id="viewItemHiddenFileInput1" accept="image/jpeg,image/png,image/webp,image/gif" style="display: none;" onchange="handleItemImageFileSelected(1, this);">
  <input type="file" id="viewItemHiddenFileInput2" accept="image/jpeg,image/png,image/webp,image/gif" style="display: none;" onchange="handleItemImageFileSelected(2, this);">