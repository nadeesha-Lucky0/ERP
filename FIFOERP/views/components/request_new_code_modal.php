  <div class="modal-backdrop" id="requestNewCodeModal" style="display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.55); z-index: 10050; align-items: center; justify-content: center;">
    <div class="modal-dialog" style="max-width: 96vw; width: 96vw; height: 92vh; max-height: 92vh; display: flex; flex-direction: column; border-radius: 6px; box-shadow: 0 10px 40px rgba(0,0,0,0.25); border: 1px solid #cbd5e1; overflow: hidden; margin: auto;">
      <div class="modal-header" style="background: #f8fafc; border-bottom: 1px solid #cbd5e1; padding: 10px 18px; display: flex; justify-content: space-between; align-items: center;">
        <div style="display: flex; align-items: center; gap: 10px;">
          <div>
            <h3 class="modal-title" style="font-size: 1.05rem; font-weight: 800; color: #0f172a; margin: 0;">Request Item Code Creation &amp; Live Duplicate Checker</h3>
            <span style="font-size: 0.74rem; color: #64748b;">Live word-matching across Master Added (Orange) &amp; Requested items prevents duplicate codes</span>
          </div>
        </div>
        <button type="button" class="modal-close" data-close-modal style="font-size: 1.35rem; line-height: 1; color: #64748b; border: none; background: none; cursor: pointer;">&times;</button>
      </div>

      <form id="simpleRequestCodeForm" onsubmit="executeSimpleCodeRequest(event);" style="display: flex; flex-direction: column; flex: 1; min-height: 0; overflow: hidden;">
        <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars(\App\Core\Session::csrfToken()) ?>">
        <input type="hidden" name="origin_station_id" value="<?= (int)($currentUser['location_id'] ?? 1) ?>">
        <input type="hidden" name="tier1" id="modalReqTier1" value="1">
        <input type="hidden" name="tier2" id="modalReqTier2" value="1">
        <input type="hidden" name="tier3" id="modalReqTier3" value="1">
        <input type="hidden" name="suggested_tier4_seq" id="modalReqTier4" value="1">

        <div class="modal-body modal-body-split" style="flex: 1; min-height: 0; display: grid; grid-template-columns: 390px 1fr; gap: 16px; padding: 16px; overflow: hidden; background: #f1f5f9;">
          
          <!-- LEFT PANEL: Request Details Form -->
          <div style="background: #ffffff; border: 1px solid #cbd5e1; border-radius: 6px; padding: 16px; display: flex; flex-direction: column; gap: 12px; overflow-y: auto;">
            
            <!-- Beautiful Target Classification Card -->
            <div style="background: linear-gradient(135deg, #fdf2f5 0%, #ffffff 100%); border: 1px solid #fbcfe8; border-radius: 8px; padding: 12px 14px; box-shadow: 0 1px 3px rgba(101, 11, 43, 0.05);">
              <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                <span style="font-size: 0.72rem; font-weight: 800; color: #9f1239; text-transform: uppercase; letter-spacing: 0.6px; display: inline-flex; align-items: center; gap: 6px;">
                  <span style="display: inline-block; width: 6px; height: 6px; border-radius: 50%; background: #9f1239;"></span>
                  TARGET CLASSIFICATION
                </span>
                <span style="font-size: 0.7rem; font-weight: 700; color: #0284c7; background: #e0f2fe; border: 1px solid #bae6fd; padding: 2px 8px; border-radius: 12px;" id="modalReqAssignBadge">Assigned on Approval</span>
              </div>
              
              <div style="margin-bottom: 10px;">
                <span id="modalReqFormattedCode" style="font-family: monospace; font-size: 1.25rem; font-weight: 800; color: #650b2b; background: #ffffff; border: 1.5px solid #650b2b; padding: 4px 14px; border-radius: 5px; letter-spacing: 1.5px; white-space: nowrap; display: inline-block; box-shadow: 0 1px 2px rgba(0,0,0,0.06);">
                  1-01-01-XXX
                </span>
              </div>

              <!-- Clean 1st / 2nd / 3rd Category Breadcrumb Stack -->
              <div style="display: flex; flex-direction: column; gap: 5px; font-size: 0.76rem; background: #ffffff; border: 1px solid #f1f5f9; border-radius: 5px; padding: 7px 10px;">
                <div style="display: flex; align-items: center; gap: 8px; overflow: hidden;">
                  <span style="font-size: 0.68rem; font-weight: 800; color: #650b2b; background: #fdf2f5; padding: 1px 6px; border-radius: 3px; min-width: 28px; text-align: center;">1st</span>
                  <span id="modalReqPathT1" style="font-weight: 700; color: #0f172a; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">-</span>
                </div>
                <div style="display: flex; align-items: center; gap: 8px; overflow: hidden;">
                  <span style="font-size: 0.68rem; font-weight: 800; color: #475569; background: #f1f5f9; padding: 1px 6px; border-radius: 3px; min-width: 28px; text-align: center;">2nd</span>
                  <span id="modalReqPathT2" style="font-weight: 600; color: #334155; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">-</span>
                </div>
                <div style="display: flex; align-items: center; gap: 8px; overflow: hidden;">
                  <span style="font-size: 0.68rem; font-weight: 800; color: #0284c7; background: #e0f2fe; padding: 1px 6px; border-radius: 3px; min-width: 28px; text-align: center;">3rd</span>
                  <span id="modalReqPathT3" style="font-weight: 600; color: #0369a1; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">-</span>
                </div>
              </div>
            </div>

            <!-- Item code (4th segment) kept hidden in DOM for AddItemFlow compatibility -->
            <div id="modalReqSeqWrap" style="display: none !important; margin-bottom: 0;">
              <label for="modalReqSeqInput" style="display: block; font-size: 0.72rem; font-weight: 800; color: #475569; text-transform: uppercase; margin-bottom: 4px;">Item code (4th segment) *</label>
              <div style="display: flex; align-items: center; gap: 8px; background: #f1f5f9; border-radius: 6px; padding: 8px 10px;">
                <span style="font-family: monospace; font-size: 0.95rem; font-weight: 800; color: #64748b;"><span id="modalReqSeqPrefix"></span></span>
                <input type="text" id="modalReqSeqInput" inputmode="numeric" maxlength="3" autocomplete="off" aria-label="4th code segment"
                  oninput="this.value = this.value.replace(/\D/g, '').slice(0, 3); if (window.AddItemFlow) window.AddItemFlow.onInput();"
                  onblur="if (this.value !== '') this.value = this.value.padStart(3, '0');"
                  style="width: 70px; text-align: center; font-family: monospace; font-size: 1.1rem; font-weight: 800; height: 34px; border: 1px solid #71717a; border-radius: 4px;">
                <span id="modalReqSeqSuggest" style="font-size: 0.72rem; color: #64748b;"></span>
              </div>
              <div id="modalReqCodeNote" aria-live="polite"></div>
              <label id="modalReqConfirmRow" style="display: none; margin-top: 6px; font-size: 0.78rem; font-weight: 600; color: #92400e; cursor: pointer;">
                <input type="checkbox" id="modalReqConfirm" onchange="if (window.AddItemFlow) window.AddItemFlow.updateSubmit();"> It is a different item, create anyway
              </label>
            </div>

            <!-- Item Name / Description Input -->
            <div class="form-group" style="margin-bottom: 0;">
              <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px;">
                <label style="font-size: 0.76rem; font-weight: 800; color: #0f172a; text-transform: uppercase; margin: 0;">
                  Item Description / Name *
                </label>
                <button type="button" onclick="SmartSpellChecker.autoFixAll('modalReqItemName'); if(typeof onModalReqNameInput === 'function') onModalReqNameInput();" style="background: none; border: none; color: #0284c7; font-size: 0.72rem; font-weight: 700; cursor: pointer; padding: 0; display: inline-flex; align-items: center; gap: 4px;" title="Auto-detect & fix typos">
                  <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m12 3-1.912 5.813a2 2 0 0 1-1.275 1.275L3 12l5.813 1.912a2 2 0 0 1 1.275 1.275L12 21l1.912-5.813a2 2 0 0 1 1.275-1.275L21 12l-5.813-1.912a2 2 0 0 1-1.275-1.275L12 3Z"/></svg>
                  Auto-Fix Spelling
                </button>
              </div>
              <textarea name="item_name" id="modalReqItemName" class="form-control" rows="3"
                placeholder="E.G. DEEP GROOVE BALL BEARING 6205 2RS C3" required spellcheck="true" autocomplete="off" lang="en"
                oninput="this.value = this.value.toUpperCase(); if(typeof onModalReqNameInput === 'function') onModalReqNameInput();"
                style="font-size: 0.88rem; font-weight: 600; padding: 8px 10px; border: 1px solid #71717a; border-radius: 4px; text-transform: uppercase; width: 100%; resize: vertical; line-height: 1.4;"></textarea>
              <div id="spellSuggestReqItemName" style="margin-top: 6px; font-size: 0.73rem; min-height: 18px; display: none; align-items: center; gap: 6px; flex-wrap: wrap;"></div>
            </div>

            <!-- Hidden specs field to maintain backward compatibility -->
            <input type="hidden" name="specifications" id="modalReqSpecs" value="">

            <!-- Amount & UoM Row -->
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
              <div class="form-group" style="margin-bottom: 0;">
                <label style="font-size: 0.72rem; font-weight: 700; color: #475569; text-transform: uppercase; margin-bottom: 3px; display: flex; justify-content: space-between; align-items: center;">
                  <span>Amount (LKR)</span>
                  <span style="font-size: 0.68rem; color: #64748b; font-weight: 500;">(Optional)</span>
                </label>
                <div style="position: relative;">
                  <span style="position: absolute; left: 10px; top: 50%; transform: translateY(-50%); font-size: 0.76rem; font-weight: 800; color: #64748b; pointer-events: none;">LKR</span>
                  <input type="number" step="0.01" min="0" name="unit_price" id="modalReqUnitPrice" class="form-control" placeholder="0.00"
                    style="font-size: 0.84rem; font-weight: 700; padding: 6px 10px 6px 42px; border: 1px solid #cbd5e1; border-radius: 4px; height: 34px; width: 100%;">
                </div>
              </div>

              <div class="form-group" style="margin-bottom: 0;">
                <label style="font-size: 0.72rem; font-weight: 700; color: #475569; text-transform: uppercase; margin-bottom: 3px; display: block;">Unit of Measure *</label>
                <select name="uom" id="modalReqUom" class="form-select" required style="font-size: 0.82rem; padding: 6px 8px; border: 1px solid #cbd5e1; border-radius: 4px; height: 34px;">
                  <option value="Nos" selected>Nos (Numbers)</option>
                  <option value="Mtr">Mtr (Meters)</option>
                  <option value="Kg">Kg (Kilograms)</option>
                  <option value="Ltr">Ltr (Liters)</option>
                  <option value="Set">Set</option>
                  <option value="Pair">Pair</option>
                  <option value="Roll">Roll</option>
                  <option value="Box">Box</option>
                </select>
              </div>
            </div>

            <!-- Add Two Images (Max 2 Photos) - Title front camera removed, camera icon only in both slots -->
            <div class="form-group" style="margin-bottom: 0;">
              <label style="font-size: 0.72rem; font-weight: 700; color: #475569; text-transform: uppercase; margin-bottom: 4px; display: flex; justify-content: space-between; align-items: center;">
                <span>Item Photos (Maximum 2 Images)</span>
                <span style="font-size: 0.68rem; color: #64748b; font-weight: 500;">JPG, PNG, WEBP</span>
              </label>
              <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                <!-- Image 1 Slot -->
                <div id="modalReqImg1Box" style="border: 1.5px dashed #cbd5e1; border-radius: 6px; padding: 10px 8px; background: #f8fafc; text-align: center; cursor: pointer; position: relative; transition: all 0.15s ease;"
                     onclick="document.getElementById('modalReqImg1Input').click();"
                     onmouseover="this.style.borderColor='#650b2b'; this.style.background='#fdf2f5';"
                     onmouseout="this.style.borderColor='#cbd5e1'; this.style.background='#f8fafc';"
                     ondragover="event.preventDefault(); this.style.borderColor='#0d9488'; this.style.background='#f0fdfa';"
                     ondragleave="this.style.borderColor='#cbd5e1'; this.style.background='#f8fafc';"
                     ondrop="event.preventDefault(); this.style.borderColor='#cbd5e1'; this.style.background='#f8fafc'; if(event.dataTransfer.files && event.dataTransfer.files.length){ const inp=document.getElementById('modalReqImg1Input'); const dt=new DataTransfer(); dt.items.add(event.dataTransfer.files[0]); inp.files=dt.files; if(typeof previewAddCodeImage==='function') previewAddCodeImage(1, inp); }">
                  <input type="file" name="image_1" id="modalReqImg1Input" accept="image/jpeg,image/png,image/webp" style="display: none;" onchange="previewAddCodeImage(1, this);">
                  <div id="modalReqImg1Placeholder">
                    <div style="display: flex; justify-content: center; margin-bottom: 4px; color: #475569;">
                      <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M14.5 4h-5L7 7H4a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2h-3l-2.5-3z"/><circle cx="12" cy="13" r="3"/></svg>
                    </div>
                    <div style="font-size: 0.74rem; font-weight: 700; color: #334155;">Image 1</div>
                    <div style="font-size: 0.64rem; color: #94a3b8;">Drag &amp; drop or click</div>
                  </div>
                  <div id="modalReqImg1Preview" style="display: none; position: relative;">
                    <img id="modalReqImg1Tag" src="" alt="Image 1" style="max-height: 65px; max-width: 100%; object-fit: contain; border-radius: 4px;">
                    <button type="button" onclick="event.stopPropagation(); removeAddCodeImage(1);" title="Remove Image"
                      style="position: absolute; top: -6px; right: -6px; background: #dc2626; color: #ffffff; border: 1px solid #b91c1c; border-radius: 50%; width: 20px; height: 20px; font-size: 12px; cursor: pointer; display: flex; align-items: center; justify-content: center; line-height: 1;">&times;</button>
                  </div>
                </div>

                <!-- Image 2 Slot -->
                <div id="modalReqImg2Box" style="border: 1.5px dashed #cbd5e1; border-radius: 6px; padding: 10px 8px; background: #f8fafc; text-align: center; cursor: pointer; position: relative; transition: all 0.15s ease;"
                     onclick="document.getElementById('modalReqImg2Input').click();"
                     onmouseover="this.style.borderColor='#650b2b'; this.style.background='#fdf2f5';"
                     onmouseout="this.style.borderColor='#cbd5e1'; this.style.background='#f8fafc';"
                     ondragover="event.preventDefault(); this.style.borderColor='#0d9488'; this.style.background='#f0fdfa';"
                     ondragleave="this.style.borderColor='#cbd5e1'; this.style.background='#f8fafc';"
                     ondrop="event.preventDefault(); this.style.borderColor='#cbd5e1'; this.style.background='#f8fafc'; if(event.dataTransfer.files && event.dataTransfer.files.length){ const inp=document.getElementById('modalReqImg2Input'); const dt=new DataTransfer(); dt.items.add(event.dataTransfer.files[0]); inp.files=dt.files; if(typeof previewAddCodeImage==='function') previewAddCodeImage(2, inp); }">
                  <input type="file" name="image_2" id="modalReqImg2Input" accept="image/jpeg,image/png,image/webp" style="display: none;" onchange="previewAddCodeImage(2, this);">
                  <div id="modalReqImg2Placeholder">
                    <div style="display: flex; justify-content: center; margin-bottom: 4px; color: #475569;">
                      <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M14.5 4h-5L7 7H4a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2h-3l-2.5-3z"/><circle cx="12" cy="13" r="3"/></svg>
                    </div>
                    <div style="font-size: 0.74rem; font-weight: 700; color: #334155;">Image 2</div>
                    <div style="font-size: 0.64rem; color: #94a3b8;">Drag &amp; drop or click</div>
                  </div>
                  <div id="modalReqImg2Preview" style="display: none; position: relative;">
                    <img id="modalReqImg2Tag" src="" alt="Image 2" style="max-height: 65px; max-width: 100%; object-fit: contain; border-radius: 4px;">
                    <button type="button" onclick="event.stopPropagation(); removeAddCodeImage(2);" title="Remove Image"
                      style="position: absolute; top: -6px; right: -6px; background: #dc2626; color: #ffffff; border: 1px solid #b91c1c; border-radius: 50%; width: 20px; height: 20px; font-size: 12px; cursor: pointer; display: flex; align-items: center; justify-content: center; line-height: 1;">&times;</button>
                  </div>
                </div>
              </div>
            </div>


            <div style="margin-top: auto; position: sticky; bottom: -16px; background: #ffffff; z-index: 2; padding: 10px 0 16px; margin-bottom: -16px; border-top: 1px solid #e2e8f0; display: flex; gap: 8px;">
              <button type="button" class="btn btn-secondary btn-sm" data-close-modal style="flex: 1; padding: 8px; font-weight: 600;">Cancel</button>
              <button type="submit" class="btn btn-primary btn-sm" id="btnSubmitSimpleReq" style="flex: 2; padding: 8px; font-weight: 700; background: #650b2b; border: 1px solid #4a071e; color: #ffffff;">
                Submit Code Request
              </button>
            </div>
          </div>

          <!-- RIGHT PANEL: Live Word Matching & Existing Codes Suggestions -->
          <div style="background: #ffffff; border: 1px solid #cbd5e1; border-radius: 6px; padding: 14px 16px; display: flex; flex-direction: column; overflow: hidden; min-height: 0;">
            
            <!-- Suggestions Toolbar -->
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; flex-wrap: wrap; gap: 8px; border-bottom: 1px solid #e2e8f0; padding-bottom: 10px;">
              <div>
                <h4 style="margin: 0; font-size: 0.95rem; font-weight: 800; color: #0f172a; display: flex; align-items: center; gap: 8px;">
                  <span>ðŸ” System-Wide Related Codes &amp; Word Suggestions</span>
                  <span id="modalReqSuggestionsCount" style="font-size: 0.72rem; font-weight: 700; padding: 2px 10px; border-radius: 12px; background: #e0f2fe; color: #0369a1; border: 1px solid #bae6fd;">Ready</span>
                </h4>
                <p style="margin: 2px 0 0 0; font-size: 0.74rem; color: #64748b;">
                  Live search across all 10,960+ system codes in all categories &bull; Matches both <strong>Master Added (Orange)</strong> and <strong>Requested / Staged</strong> items.
                </p>
              </div>
            </div>

            <!-- Suggestions Results Table -->
            <div style="flex: 1; overflow-y: auto; overflow-x: auto; border: 1px solid #e2e8f0; border-radius: 4px;">
              <table style="width: 100%; border-collapse: collapse; font-size: 0.8rem;">
                <thead style="position: sticky; top: 0; background: #000000; color: #ffffff; z-index: 2;">
                  <tr>
                    <th style="padding: 6px 8px; text-align: left; font-size: 0.72rem; font-weight: 800; color: #ffffff; width: 130px; white-space: nowrap;">ITEM CODE</th>
                    <th style="padding: 6px 8px; text-align: center; font-size: 0.72rem; font-weight: 800; color: #ffffff; width: 85px; white-space: nowrap;">BRANCH</th>
                    <th style="padding: 6px 10px; text-align: left; font-size: 0.72rem; font-weight: 800; color: #ffffff;">DESCRIPTION &amp; SPECS</th>
                    <th style="padding: 6px 8px; text-align: center; font-size: 0.72rem; font-weight: 800; color: #ffffff; width: 60px; white-space: nowrap;">UOM</th>
                    <th style="padding: 6px 8px; text-align: center; font-size: 0.72rem; font-weight: 800; color: #ffffff; width: 140px; white-space: nowrap;">CATALOG STATUS</th>
                    <th style="padding: 6px 8px; text-align: center; font-size: 0.72rem; font-weight: 800; color: #ffffff; width: 150px; white-space: nowrap;">ACTION</th>
                  </tr>
                </thead>
                <tbody id="modalReqSuggestionsBody">
                  <tr>
                    <td colspan="6" style="text-align: center; padding: 30px; color: #64748b; font-size: 0.8125rem;">
                      Type in the item description box on the left to see live matching codes.
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

          </div>

        </div>
      </form>
    </div>
  </div>

  <!-- =======================================================================
       10. EDIT CODE CREATION REQUEST MODAL (PENDING REQUESTS ONLY)
       ======================================================================= -->
  <div class="modal-backdrop" id="editCodeRequestModal" data-keep-values="true" style="display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.55); z-index: 10040; align-items: center; justify-content: center;" onclick="if(event.target === this) window.closeModal('editCodeRequestModal');">
    <div class="modal-dialog" style="max-width: 620px; width: 95vw; max-height: 90vh; display: flex; flex-direction: column; border-radius: 8px; box-shadow: 0 15px 40px rgba(0,0,0,0.25); border: 1px solid #cbd5e1; overflow: hidden; margin: auto; background: #ffffff;">
      
      <div class="modal-header" style="background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%); border-bottom: 1px solid #cbd5e1; padding: 12px 18px; display: flex; justify-content: space-between; align-items: center;">
        <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
          <div>
            <h3 class="modal-title" style="font-size: 1.05rem; font-weight: 800; color: #0f172a; margin: 0;">Edit Code Creation Request</h3>
            <span style="font-size: 0.72rem; color: #64748b;">Update pending item details before administrator approval</span>
          </div>
          <span id="editReqStatusBadge" style="font-size: 0.7rem; font-weight: 700; color: #c2410c; background: #fff7ed; border: 1px solid #fed7aa; padding: 2px 8px; border-radius: 12px;">PENDING</span>
        </div>
        <button type="button" class="btn-close" data-close-modal onclick="window.closeModal('editCodeRequestModal');" style="background: none; border: none; font-size: 1.4rem; color: #64748b; cursor: pointer; line-height: 1; padding: 2px 6px;" title="Close Window">&times;</button>
      </div>

      <form id="editCodeRequestForm" onsubmit="executeEditCodeRequest(event);" style="display: flex; flex-direction: column; flex: 1; min-height: 0; overflow: hidden;" enctype="multipart/form-data">
        <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars(\App\Core\Session::csrfToken()) ?>">
        <input type="hidden" name="request_id" id="editReqId" value="">
        <input type="hidden" name="delete_image_1" id="editReqDeleteImg1" value="0">
        <input type="hidden" name="delete_image_2" id="editReqDeleteImg2" value="0">

        <div class="modal-body" style="padding: 16px 20px; overflow-y: auto; background: #ffffff; flex: 1; display: flex; flex-direction: column; gap: 14px;">
          
          <!-- Code Info Banner -->
          <div style="background: #fdf2f5; border: 1px solid #fbcfe8; border-radius: 6px; padding: 10px 14px; display: flex; justify-content: space-between; align-items: center; gap: 10px; flex-wrap: wrap;">
            <div>
              <span style="font-size: 0.68rem; font-weight: 800; color: #9f1239; text-transform: uppercase;">REQUEST NUMBER</span>
              <div id="editReqNo" style="font-family: monospace; font-size: 0.95rem; font-weight: 800; color: #650b2b;">REQ-0000</div>
            </div>
            <div style="text-align: right;">
              <span style="font-size: 0.68rem; font-weight: 800; color: #9f1239; text-transform: uppercase;">REQUESTED CODE</span>
              <div id="editReqDisplayCode" style="font-family: monospace; font-size: 1.05rem; font-weight: 800; color: #650b2b;">1-01-01-001</div>
            </div>
          </div>

          <!-- Notice Alert -->
          <div style="font-size: 0.73rem; color: #0369a1; background: #e0f2fe; border: 1px solid #bae6fd; padding: 6px 12px; border-radius: 4px; display: flex; align-items: center; gap: 6px;">
            <span>â„¹ï¸</span>
            <span>You can edit this request while in <strong>PENDING</strong> or <strong>REJECTED</strong> status. Saving will resubmit it for Administrator Approval. Once approved, editing is disabled.</span>
          </div>

          <!-- Item Description / Name -->
          <div class="form-group" style="margin-bottom: 0;">
            <label style="font-size: 0.74rem; font-weight: 800; color: #0f172a; text-transform: uppercase; margin-bottom: 4px; display: block;">
              Item Description / Name *
            </label>
            <textarea name="item_name" id="editReqItemName" class="form-control" rows="2" required spellcheck="true" autocomplete="off"
              oninput="this.value = this.value.toUpperCase();"
              style="font-size: 0.88rem; font-weight: 600; padding: 8px 10px; border: 1px solid #71717a; border-radius: 4px; text-transform: uppercase; width: 100%; resize: vertical; line-height: 1.4;"></textarea>
          </div>

          <!-- Amount & UoM Row -->
          <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
            <div class="form-group" style="margin-bottom: 0;">
              <label style="font-size: 0.72rem; font-weight: 700; color: #475569; text-transform: uppercase; margin-bottom: 3px; display: flex; justify-content: space-between; align-items: center;">
                <span>Amount (LKR)</span>
                <span style="font-size: 0.68rem; color: #64748b; font-weight: 500;">(Optional)</span>
              </label>
              <div style="position: relative;">
                <span style="position: absolute; left: 10px; top: 50%; transform: translateY(-50%); font-size: 0.76rem; font-weight: 800; color: #64748b; pointer-events: none;">LKR</span>
                <input type="number" step="0.01" min="0" name="unit_price" id="editReqUnitPrice" class="form-control" placeholder="0.00"
                  style="font-size: 0.84rem; font-weight: 700; padding: 6px 10px 6px 42px; border: 1px solid #cbd5e1; border-radius: 4px; height: 34px; width: 100%;">
              </div>
            </div>

            <div class="form-group" style="margin-bottom: 0;">
              <label style="font-size: 0.72rem; font-weight: 700; color: #475569; text-transform: uppercase; margin-bottom: 3px; display: block;">Unit of Measure *</label>
              <select name="uom" id="editReqUom" class="form-select" required style="font-size: 0.82rem; padding: 6px 8px; border: 1px solid #cbd5e1; border-radius: 4px; height: 34px;">
                <option value="Nos">Nos (Numbers)</option>
                <option value="Mtr">Mtr (Meters)</option>
                <option value="Kg">Kg (Kilograms)</option>
                <option value="Ltr">Ltr (Liters)</option>
                <option value="Set">Set</option>
                <option value="Pair">Pair</option>
                <option value="Roll">Roll</option>
                <option value="Box">Box</option>
              </select>
            </div>
          </div>

          <!-- Add Two Images -->
          <div class="form-group" style="margin-bottom: 0;">
            <label style="font-size: 0.72rem; font-weight: 700; color: #475569; text-transform: uppercase; margin-bottom: 4px; display: flex; justify-content: space-between; align-items: center;">
              <span>Item Photos (Maximum 2 Images)</span>
              <span style="font-size: 0.68rem; color: #64748b; font-weight: 500;">JPG, PNG, WEBP</span>
            </label>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
              <!-- Image 1 Slot -->
              <div id="editReqImg1Box" style="border: 1.5px dashed #cbd5e1; border-radius: 6px; padding: 10px 8px; background: #f8fafc; text-align: center; cursor: pointer; position: relative; transition: all 0.15s ease;"
                   onclick="document.getElementById('editReqImg1Input').click();">
                <input type="file" name="image_1" id="editReqImg1Input" accept="image/jpeg,image/png,image/webp" style="display: none;" onchange="previewEditCodeImage(1, this);">
                <div id="editReqImg1Placeholder">
                  <div style="font-size: 1.3rem; line-height: 1; margin-bottom: 3px;">ðŸ“·</div>
                  <div style="font-size: 0.74rem; font-weight: 700; color: #334155;">Image 1</div>
                  <div style="font-size: 0.64rem; color: #94a3b8;">Click to upload</div>
                </div>
                <div id="editReqImg1Preview" style="display: none; position: relative;">
                  <img id="editReqImg1Tag" src="" alt="Image 1" style="max-height: 70px; max-width: 100%; object-fit: contain; border-radius: 4px;">
                  <button type="button" onclick="event.stopPropagation(); removeEditCodeImage(1);" title="Remove Image"
                    style="position: absolute; top: -6px; right: -6px; background: #dc2626; color: #ffffff; border: 1px solid #b91c1c; border-radius: 50%; width: 20px; height: 20px; font-size: 12px; cursor: pointer; display: flex; align-items: center; justify-content: center; line-height: 1;">&times;</button>
                </div>
              </div>

              <!-- Image 2 Slot -->
              <div id="editReqImg2Box" style="border: 1.5px dashed #cbd5e1; border-radius: 6px; padding: 10px 8px; background: #f8fafc; text-align: center; cursor: pointer; position: relative; transition: all 0.15s ease;"
                   onclick="document.getElementById('editReqImg2Input').click();">
                <input type="file" name="image_2" id="editReqImg2Input" accept="image/jpeg,image/png,image/webp" style="display: none;" onchange="previewEditCodeImage(2, this);">
                <div id="editReqImg2Placeholder">
                  <div style="font-size: 1.3rem; line-height: 1; margin-bottom: 3px;">ðŸ“·</div>
                  <div style="font-size: 0.74rem; font-weight: 700; color: #334155;">Image 2</div>
                  <div style="font-size: 0.64rem; color: #94a3b8;">Click to upload</div>
                </div>
                <div id="editReqImg2Preview" style="display: none; position: relative;">
                  <img id="editReqImg2Tag" src="" alt="Image 2" style="max-height: 70px; max-width: 100%; object-fit: contain; border-radius: 4px;">
                  <button type="button" onclick="event.stopPropagation(); removeEditCodeImage(2);" title="Remove Image"
                    style="position: absolute; top: -6px; right: -6px; background: #dc2626; color: #ffffff; border: 1px solid #b91c1c; border-radius: 50%; width: 20px; height: 20px; font-size: 12px; cursor: pointer; display: flex; align-items: center; justify-content: center; line-height: 1;">&times;</button>
                </div>
              </div>
            </div>
          </div>

        </div>

        <div class="modal-footer" style="background: #f8fafc; border-top: 1px solid #cbd5e1; padding: 10px 18px; display: flex; justify-content: flex-end; gap: 8px;">
          <button type="button" class="btn btn-secondary btn-sm" data-close-modal onclick="window.closeModal('editCodeRequestModal');" style="padding: 6px 14px; font-weight: 600; font-size: 0.78rem;">Cancel</button>
          <button type="submit" class="btn btn-primary btn-sm" id="btnSubmitEditReq" style="padding: 6px 18px; font-weight: 700; font-size: 0.78rem; background: #0284c7; border: 1px solid #0369a1; color: #ffffff;">
            Save Changes
          </button>
        </div>
      </form>

    </div>
  </div>

  <!-- =======================================================================
       ITEM SPECIFICATION & IMAGE VIEW POP-UP MODAL (DUAL IMAGES + PRICE + SPECS)
