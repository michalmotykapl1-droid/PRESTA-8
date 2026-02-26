{* MODAL: SKANER PRZYJĘĆ (WMS -> WYPRZEDAŻ) *}
{* Wersja: FIX CSS PRZYCISKÓW (Centrowanie Flexbox) + LISTY WYBORU *}

<div id="reception_modal" class="modal fade" tabindex="-1" role="dialog" data-backdrop="static">
    <div class="modal-dialog" role="document" style="width: 700px; max-width: 95%;">
        <div class="modal-content" style="border: 2px solid #007aff; box-shadow: 0 5px 15px rgba(0,0,0,0.3);">
            
            {* NAGŁÓWEK *}
            <div class="modal-header" style="background-color: #007aff; color: white; padding: 15px;">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="color:white; opacity:0.8; font-size: 30px;">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h3 class="modal-title" style="margin:0; font-weight:bold; font-size: 20px;">
                    <i class="icon-barcode"></i> PRZYJĘCIE TOWARU NA MAGAZYN
                </h3>
            </div>
            
            <div class="modal-body" style="padding: 20px; background-color: #f8f8f8;">
                
                {* 1. INFO O PRODUKCIE *}
                <div class="well" style="background: white; border-color: #ddd; margin-bottom: 20px;">
                    <div class="row">
                        <div class="col-md-12 text-center">
                            <h2 id="rec_prod_name" style="margin-top:0; margin-bottom: 5px; font-weight:bold; color:#333; font-size: 22px;">
                                Nazwa Produktu
                            </h2>
                            <div style="font-size: 16px; color: #666;">
                                <span class="label label-primary" style="font-size: 14px; padding: 5px 10px;" id="rec_prod_ean">EAN: ---</span>
                                &nbsp;&nbsp;
                                Dostępne na stole: <b id="rec_prod_max" style="color:#2e7d32; font-size: 18px;">0</b> szt.
                            </div>
                        </div>
                    </div>
                </div>

                {* 2. WYBÓR LOKALIZACJI (TOGGLE) - POPRAWIONY CSS *}
                <div class="row text-center" style="margin-bottom: 25px;">
                    <div class="col-md-12">
                        <div class="btn-group" data-toggle="buttons" style="width: 100%; display: flex;">
                            {* PRZYCISK REGAŁ *}
                            <label class="btn btn-default active" id="btn_type_regal" 
                                   style="flex: 1; height: 50px; display: flex; align-items: center; justify-content: center; font-size: 18px; font-weight:bold; border-radius: 4px 0 0 4px; border: 1px solid #ccc;">
                                <input type="radio" name="rec_type" value="regal" checked> 
                                <i class="icon-columns" style="margin-right: 8px;"></i> REGAŁ
                            </label>
                            
                            {* PRZYCISK KOSZ *}
                            <label class="btn btn-default" id="btn_type_kosz" 
                                   style="flex: 1; height: 50px; display: flex; align-items: center; justify-content: center; font-size: 18px; font-weight:bold; border-radius: 0 4px 4px 0; border: 1px solid #ccc; border-left: 0;">
                                <input type="radio" name="rec_type" value="kosz"> 
                                <i class="icon-shopping-cart" style="margin-right: 8px;"></i> KOSZ
                            </label>
                        </div>
                    </div>
                </div>

                {* 3. FORMULARZ DANYCH *}
                <div class="row">
                    
                    {* Pole: REGAŁ (A-Z) - LISTA ROZWIJANA *}
                    <div id="fg-regal" class="col-md-6 form-group">
                        <label style="font-size:14px; display:block; font-weight:bold; color:#555;">REGAŁ (A-Z)</label>
                        <select id="rec_regal" class="form-control input-lg" style="height: 50px; font-size: 20px; font-weight: bold; text-align-last: center;">
                            <option value="">-- Wybierz --</option>
                            {section name=alpha loop=26}
                                {assign var="char" value=$smarty.section.alpha.index+65}
                                <option value="{$char|chr}">{$char|chr}</option>
                            {/section}
                        </select>
                    </div>

                    {* Pole: PÓŁKA (1-10) - LISTA ROZWIJANA *}
                    <div id="fg-polka" class="col-md-6 form-group">
                        <label style="font-size:14px; display:block; font-weight:bold; color:#555;">PÓŁKA (1-10)</label>
                        <select id="rec_polka" class="form-control input-lg" style="height: 50px; font-size: 20px; font-weight: bold; text-align-last: center;">
                            <option value="">-- Wybierz --</option>
                            {section name=num loop=10}
                                <option value="{$smarty.section.num.iteration}">{$smarty.section.num.iteration}</option>
                            {/section}
                        </select>
                    </div>

                    {* Pole: NUMER KOSZA (1-30) - Domyślnie ukryte - LISTA ROZWIJANA *}
                    <div id="fg-kosz-nr" class="col-md-12 form-group" style="display:none;">
                        <div style="width: 60%; margin: 0 auto;">
                            <label style="font-size:14px; display:block; font-weight:bold; color:#555; text-align:center;">NUMER KOSZA (1-30)</label>
                            <select id="rec_kosz_nr" class="form-control input-lg" style="height: 50px; font-size: 20px; font-weight: bold; text-align-last: center;">
                                <option value="">-- Wybierz Kosz --</option>
                                {section name=kosz loop=30}
                                    <option value="{$smarty.section.kosz.iteration}">{$smarty.section.kosz.iteration}</option>
                                {/section}
                            </select>
                        </div>
                    </div>
                </div>

                <hr style="border-top: 1px solid #ddd; margin: 20px 0;">

                <div class="row">
                    <div class="col-md-6 form-group">
                        <label style="font-size:14px; font-weight:bold; color:#555;">DATA WAŻNOŚCI:</label>
                        <div class="input-group">
                            <input type="date" id="rec_expiry" class="form-control input-lg" style="height: 50px; font-size: 16px; text-align: center;">
                            <span class="input-group-addon" style="background:white;"><i class="icon-calendar"></i></span>
                        </div>
                        <p class="help-block" id="rec_expiry_help" style="font-size:12px; color:#888; margin-top:5px; margin-bottom:0;"></p>
                    </div>
                    
                    <div class="col-md-6 form-group">
                        <label style="font-size:14px; font-weight:bold; color:#555;">ILOŚĆ DO PRZYJĘCIA:</label>
                        <input type="number" id="rec_qty" class="form-control input-lg" style="height: 50px; text-align:center; font-weight:bold; font-size:28px; color:#2e7d32; border: 2px solid #4caf50;" max="999999">
                    </div>
                </div>
            </div>

            <div class="modal-footer" style="background: #fff; padding: 15px; border-top: 1px solid #ddd;">
                <div class="row">
                    <div class="col-md-4">
                        <button type="button" class="btn btn-default btn-lg btn-block" data-dismiss="modal" style="height: 50px;">
                            <i class="icon-remove"></i> Anuluj
                        </button>
                    </div>
                    <div class="col-md-8">
                        <button type="button" id="btn_save_reception" class="btn btn-success btn-lg btn-block" style="height: 50px; font-weight: bold; background-color: #4caf50; border-color: #4caf50;">
                            <i class="icon-check"></i> PRZYJMIJ NA STAN
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{* UKRYTE POLE DO STEROWANIA SKANEREM *}
<input type="text" id="main_reception_scanner" style="position:fixed; top:-500px;" autocomplete="off">