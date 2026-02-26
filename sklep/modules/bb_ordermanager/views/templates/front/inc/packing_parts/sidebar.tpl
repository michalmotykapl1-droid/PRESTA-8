<div class="sidebar">
    <div class="sidebar-header">
        <i class="fa-solid fa-list-check"></i> Kolejka do spakowania
    </div>
    <div class="order-list" id="sidebar-list">
        {foreach from=$orders_queue item=q_order}
            <a href="{$base_link}&id_order={$q_order.id_order}" 
                id="sidebar-order-{$q_order.id_order}"
               class="order-item {if $q_order.id_order == $current_order_id}active{elseif $q_order.pack_status == 2}status-done{elseif $q_order.pack_status == 1}status-partial{else}status-new{/if}"
               data-id="{$q_order.id_order}"
               data-status="{$q_order.pack_status}"
               data-total="{$q_order.total_items}"
               data-packed="{$q_order.packed_items}">
               
                <div class="order-ref">
                    <span>{$q_order.reference}</span>
                    <span class="order-id">#{$q_order.id_order}</span>
                </div>
                <div class="order-customer">
                    <span><i class="fa-regular fa-user mr-1"></i> {$q_order.customer}</span>
                    <span class="sidebar-counter">
                        <span class="s-packed">{$q_order.packed_items}</span>/<span class="s-total">{$q_order.total_items}</span>
                    </span>
                </div>
            </a>
        {/foreach}
    </div>
</div>