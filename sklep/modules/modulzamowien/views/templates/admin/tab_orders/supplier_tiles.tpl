{* SEKCJA: KAFELKI DOSTAWCÓW (GÓRNY RZĄD) - MODERN CARDS + BRUTTO *}
<div class="row">
    {foreach from=$orders_grouped key=supplierKey item=group}
        {assign var="supplierName" value=$group.supplier_name}
        {assign var="products" value=$group.items}
        
        {if $supplierName|strip_tags|strstr:"BRAK" || $supplierName|strip_tags|strstr:"WERYFIKACJI"}{continue}{/if}

        {assign var="box_total_net" value=0}
        {assign var="box_total_gross" value=0}
        {assign var="box_total_qty" value=0}
        {assign var="copy_string" value=""}
        {assign var="json_items" value=[]}
        
        {assign var="is_ekowital" value=$supplierName|upper|strstr:"EKOWIT"}
        
        {foreach from=$products item=p}
            {assign var="row_val_net" value=$p.price * $p.qty_buy}
            {assign var="row_val_gross" value=$p.price_gross * $p.qty_buy}
            
            {assign var="box_total_net" value=$box_total_net + $row_val_net}
            {assign var="box_total_gross" value=$box_total_gross + $row_val_gross}
            {assign var="box_total_qty" value=$box_total_qty + $p.qty_buy}
            
            {assign var="cleanName" value=$p.name|replace:'[EXTRA] ':''}
            {assign var="ean_clipboard" value=$p.ean}
            {if $is_ekowital}{assign var="ean_clipboard" value=$p.ean|ltrim:'0'}{/if}

            {capture name=line}{$ean_clipboard}    {$p.qty_buy}{/capture}
            {assign var="copy_string" value=$copy_string|cat:$smarty.capture.line}
            
            {* POPRAWKA: Dodajemy 'sku'=>$p.sku do tablicy JSON *}
            {append var='json_items' value=['ean'=>$p.ean, 'sku'=>$p.sku, 'qty'=>$p.qty_buy, 'name'=>$p.name|escape:'html':'UTF-8', 'price'=>$p.price]}
        {/foreach}
        
        {assign var="json_string" value=$json_items|json_encode}
        {assign var="safeID" value=$supplierName|strip_tags|trim|md5}
        
        <div class="col-md-4 col-lg-3">
            <div class="mz-modern-card" id="panel_box_{$safeID}">
                <div class="mz-card-header" style="color: #333;">
                    <span title="{$supplierName|strip_tags}">
                        <i class="icon-truck" style="color:#007aff; margin-right:5px;"></i>
                        {$supplierName|strip_tags|truncate:18:".."}
                    </span>
                    <span class="badge" style="background:#f0f2f5; color:#555;">{$box_total_qty} szt.</span>
                </div>
                
                <div class="mz-card-body text-center">
                    <div style="font-size:11px; color:#888; margin-bottom:5px;">WARTOŚĆ ZAMÓWIENIA</div>
                    
                    <div class="mz-price-tag" style="color:#007aff; margin-bottom: 2px;">
                        {$box_total_net|string_format:"%.2f"} zł <small style="font-size:12px; font-weight:normal; color:#aaa;">netto</small>
                    </div>
                    <div style="font-size: 14px; font-weight: bold; color: #555;">
                        {$box_total_gross|string_format:"%.2f"} zł <small style="font-size:10px; font-weight:normal;">brutto</small>
                    </div>
                    
                    <textarea id="quick_copy_{$safeID}" style="position:absolute; left:-9999px;">{$copy_string}</textarea>
                    
                    <button type="button" class="mz-btn-copy btn-copy-archive" 
                            data-target-id="quick_copy_{$safeID}" 
                            data-supplier="{$supplierName|strip_tags}" 
                            data-cost="{$box_total_net}" 
                            data-items='{$json_string|escape:'html':'UTF-8'}' 
                            data-panel-id="panel_box_{$safeID}">
                        <i class="icon-copy"></i> SKOPIUJ KODY
                    </button>
                    
                    <div style="margin-top:10px;">
                        <a href="#anchor_{$safeID}" style="font-size:11px; color:#999; text-decoration:none;">
                            Zobacz listę <i class="icon-angle-down"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    {/foreach}
</div>