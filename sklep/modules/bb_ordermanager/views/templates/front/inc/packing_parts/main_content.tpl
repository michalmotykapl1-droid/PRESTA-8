<div class="main-content">
    <div class="top-bar">
        <div class="order-title">
            <span>Pakowanie</span>
            <a href="{$manager_url}" target="_blank" class="ref-link" title="Otwórz szczegóły w nowym oknie">
                #{$order->reference} <i class="fa-solid fa-arrow-up-right-from-square ref-icon"></i>
            </a>
        </div>
        <a href="{$manager_url}" class="close-btn">
            <i class="fa-solid fa-arrow-left"></i> Wróć do Managera
        </a>
    </div>

    <input type="text" id="scanner-input" autocomplete="off">

    <div class="packing-area">
        <div class="products-card" id="products-list">
            {foreach from=$products item=p}
                <div class="product-row {if $p.is_fully_packed}packed-done{elseif $p.packed_qty > 0}packed-partial{/if}" 
                     id="row-{$p.id_order_detail}"
                     data-id-detail="{$p.id_order_detail}"
                     data-ean="{$p.ean13}" 
                     data-id="{$p.product_id}" 
                     data-attr="{$p.product_attribute_id}"
                     data-needed="{$p.product_quantity}"
                     data-packed="{$p.packed_qty}">
                    
                    {if $p.image_url}
                        <img src="{$p.image_url}" class="prod-img">
                    {else}
                        <div class="prod-img" style="display:flex;align-items:center;justify-content:center;color:#cbd5e1"><i class="fa-regular fa-image fa-lg"></i></div>
                    {/if}

                    <div class="prod-info">
                        <div class="prod-name">{$p.product_name}</div>
                        <div class="prod-meta">
                            {if $p.reference}<div class="tag">SKU: {$p.reference}</div>{/if}
                            {if $p.ean13}<div class="tag">EAN: {$p.ean13}</div>{/if}
                            {if $p.product_attribute_id > 0}<div class="tag" style="color:#f59e0b;background:#fffbeb;">Wariant</div>{/if}
                        </div>
                    </div>

                    <div class="counter-box">
                        <button class="cnt-btn minus" onclick="manualUpdate({$p.id_order_detail}, -1)">
                            <i class="fa-solid fa-minus"></i>
                        </button>
                        <div class="cnt-val">
                            <span class="qty-packed {if $p.packed_qty >= $p.product_quantity}done{/if}">{$p.packed_qty}</span>
                            <span style="color:#ccc;font-weight:400; font-size:14px; margin:0 5px;">/</span>
                            {$p.product_quantity}
                        </div>
                        <button class="cnt-btn plus" onclick="manualUpdate({$p.id_order_detail}, 1)">
                            <i class="fa-solid fa-plus"></i>
                        </button>
                    </div>

                    <div class="status-icon">
                        <div class="status-btn {if $p.is_fully_packed}checked{/if}" 
                             onclick="markFullyPacked({$p.id_order_detail})"
                             title="Kliknij, aby spakować całość">
                            <i class="fa-solid fa-check"></i>
                        </div>
                    </div>
                </div>
            {/foreach}
        </div>
    </div>
    
    {include file='module:bb_ordermanager/views/templates/front/inc/packing_parts/modals.tpl'}
</div>