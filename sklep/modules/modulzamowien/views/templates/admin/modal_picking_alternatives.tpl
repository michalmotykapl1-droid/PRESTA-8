{* 1. MODAL: WYBÓR LISTY ZMIANY LOKALIZACJI *}
<div id="modal_picking_swap" class="modal fade" tabindex="-1" role="dialog" style="z-index: 99999;">
    <div class="modal-dialog" role="document" style="max-width: 500px;">
        <div class="modal-content" style="border: 2px solid #2e7d32; box-shadow: 0 5px 15px rgba(0,0,0,0.3);">
            <div class="modal-header" style="background-color: #2e7d32; color: white; padding: 15px;">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="color:white; opacity:0.8;"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" style="margin:0; font-weight:bold;"><i class="icon-refresh"></i> ZMIEŃ LOKALIZACJĘ</h4>
            </div>
            <div class="modal-body" style="padding: 20px; background-color: #f8f8f8;">
                <p style="font-size:14px; color:#555; margin-bottom:15px;">Obecna lokalizacja jest pusta lub niedostępna?<br><b>Wybierz inną z listy poniżej:</b></p>
                <input type="hidden" id="swap_target_sku" value="">
                <div id="swap_list_container" style="background:#fff; border:1px solid #ddd; border-radius:4px; max-height:300px; overflow-y:auto;">
                    <div class="text-center" style="padding:20px; color:#999;">Ładowanie...</div>
                </div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-default" data-dismiss="modal">Anuluj</button></div>
        </div>
    </div>
</div>

{* 2. MODAL: SMART CORRECTION (CZERWONY - KOREKTA) *}
<div id="modal_smart_correction" class="modal fade" tabindex="-1" role="dialog" style="z-index: 100000;">
    <div class="modal-dialog" role="document" style="margin-top: 10%;">
        <div class="modal-content" style="border: 2px solid #d9534f; box-shadow: 0 5px 25px rgba(0,0,0,0.4); border-radius: 6px;">
            <div class="modal-header" style="background-color: #d9534f; color: white; padding: 15px; border-radius: 4px 4px 0 0;">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="color:white; opacity:0.8;"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" style="font-weight:bold; display:flex; align-items:center;"><i class="icon-warning-sign" style="font-size:1.2em; margin-right:10px;"></i> BRAK TOWARU NA PÓŁCE?</h4>
            </div>
            <div class="modal-body" style="padding: 25px; text-align: center; font-size: 1.1em;">
                <i class="icon-dropbox" style="font-size: 4em; color: #d9534f; margin-bottom: 15px;"></i>
                <div id="smart_correction_msg" style="margin-bottom: 20px; font-weight:bold; color:#333;"></div>
                <p class="text-muted" style="font-size: 0.9em; background: #fff3f3; padding: 10px; border-radius: 5px; border: 1px solid #ffcccc;">
                    <strong>Co się stanie?</strong><br>1. Stan WMS w obecnej lokalizacji zostanie ustawiony na <strong>0</strong>.<br>2. Brakująca ilość zostanie przeniesiona do nowej lokalizacji.
                </p>
            </div>
            <div class="modal-footer" style="background: #f9f9f9; padding: 15px; text-align: center;">
                <div class="row">
                    <div class="col-md-6"><button type="button" id="btn_deny_correction" class="btn btn-default btn-lg btn-block" data-dismiss="modal">NIE, TYLKO ZMIEŃ</button></div>
                    <div class="col-md-6"><button type="button" id="btn_confirm_correction" class="btn btn-danger btn-lg btn-block" style="font-weight:bold;"><i class="icon-check"></i> TAK, WYZERUJ I ZMIEŃ</button></div>
                </div>
            </div>
        </div>
    </div>
</div>

{* 3. MODAL: RESET CONFIRMATION (NIEBIESKI - POWRÓT) *}
<div id="modal_confirm_reset" class="modal fade" tabindex="-1" role="dialog" style="z-index: 100000;">
    <div class="modal-dialog" role="document" style="margin-top: 15%;">
        <div class="modal-content" style="border: 2px solid #007aff; box-shadow: 0 5px 25px rgba(0,0,0,0.3); border-radius: 6px;">
            <div class="modal-header" style="background-color: #007aff; color: white; padding: 15px; border-radius: 4px 4px 0 0;">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="color:white; opacity:0.8;"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" style="font-weight:bold;"><i class="icon-undo"></i> POWRÓT DO ORYGINAŁU</h4>
            </div>
            <div class="modal-body" style="padding: 30px; text-align: center; font-size: 1.2em;">
                <p>Czy na pewno chcesz wrócić do oryginalnej lokalizacji z magazynu?</p>
                <p style="font-size: 0.8em; color: #777;">Zmiany zostaną cofnięte (tylko wizualnie).</p>
            </div>
            <div class="modal-footer" style="background: #f9f9f9; padding: 15px;">
                <div class="row">
                    <div class="col-md-6"><button type="button" class="btn btn-default btn-lg btn-block" data-dismiss="modal">ANULUJ</button></div>
                    <div class="col-md-6"><button type="button" id="btn_confirm_reset_action" class="btn btn-primary btn-lg btn-block" style="font-weight:bold; background-color: #007aff;">TAK, WRÓĆ</button></div>
                </div>
            </div>
        </div>
    </div>
</div>