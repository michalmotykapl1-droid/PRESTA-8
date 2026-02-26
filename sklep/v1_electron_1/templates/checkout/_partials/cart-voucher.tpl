{**
 * 2007-2025 PrestaShop
 * ...
 *}
{strip}
{if $cart.vouchers.allowed}
  {block name='cart_voucher'}
    <div class="block-promo">
      <div class="cart-voucher">
        
        {* 1. ROZWIJANE POLE "MASZ KOD RABATOWY?" *}
        <p>
          <a class="collapse-button promo-code-button" data-toggle="collapse" href="#promo-code" aria-expanded="false" aria-controls="promo-code">
            Masz kod rabatowy?
          </a>
        </p>

        <div class="promo-code collapse{if $cart.discounts|count > 0} in{/if}" id="promo-code">
          {block name='cart_voucher_form'}
            <form action="{$urls.pages.cart}" data-link-action="add-voucher" method="post">
              <input type="hidden" name="token" value="{$static_token}">
              <input type="hidden" name="addDiscount" value="1">
              <input class="promo-input" type="text" name="discount_name" placeholder="Kod rabatowy">
              <button type="submit" class="promo-btn-custom"><span>{l s='Add' d='Shop.Theme.Actions'}</span></button>
            </form>
          {/block}

          {block name='cart_voucher_notifications'}
            <div class="alert alert-danger js-error" role="alert">
              <i class="material-icons">&#xE001;</i>
              <span class="ml-1">{l s='Wprowadzony kod jest nieprawidłowy lub stracił ważność.' d='Shop.Theme.Checkout'}</span>
            </div>
          {/block}
        </div>

        {* 2. LISTA AKTYWNYCH KUPONÓW *}
        {if $cart.vouchers.added}
          {block name='cart_voucher_list'}
            <div class="active-vouchers-list">
              {foreach from=$cart.vouchers.added item=voucher}
                <div class="active-voucher-item">
                    
                    {* IKONA *}
                    <div class="voucher-icon">
                        <i class="material-icons">local_offer</i>
                    </div>
                    
                    {* TREŚĆ *}
                    <div class="voucher-details">
                        <div class="voucher-name">{$voucher.name}</div>
                        <div class="voucher-value-row">
                            <span class="voucher-label">Rabat:</span>
                            <span class="voucher-discount">
                                {if isset($voucher.reduction_percent) && $voucher.reduction_percent > 0}
                                    -{$voucher.reduction_percent|floatval}%
                                {else}
                                    {$voucher.reduction_formatted}
                                {/if}
                            </span>
                        </div>
                    </div>

                    {* USUWANIE *}
                    <div class="voucher-delete">
                        <a href="{$voucher.delete_url}" data-link-action="remove-voucher" title="{l s='Usuń kod' d='Shop.Theme.Actions'}">
                            <i class="material-icons">close</i>
                        </a>
                    </div>
                </div>
              {/foreach}
            </div>
          {/block}
        {/if}

        {if $cart.discounts|count > 0}
          <p class="block-promo promo-highlighted">
            {l s='Take advantage of our exclusive offers:' d='Shop.Theme.Actions'}
          </p>
          <ul class="js-discount card-block promo-discounts">
          {foreach from=$cart.discounts item=discount}
            <li class="cart-summary-line">
              <span class="label"><span class="code">{$discount.code}</span> - {$discount.name}</span>
            </li>
          {/foreach}
          </ul>
        {/if}
     </div>
    </div>

    {* --- STYLE CSS --- *}
    {literal}
    <style>
        .active-vouchers-list {
            margin-top: 15px;
        }
        .active-voucher-item {
            display: flex;
            align-items: center;
            background-color: transparent;
            /* TUTAJ ZMIANA: Dodany padding 20px po bokach */
            padding: 12px 20px; 
            border-bottom: 1px solid #f1f1f1;
        }
        .active-voucher-item:last-child {
            border-bottom: none;
        }

        /* IKONA */
        .voucher-icon {
            color: #ea7404;
            margin-right: 15px;
            font-size: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 24px;
        }
        .voucher-icon i {
            font-size: 20px;
        }

        /* TREŚĆ */
        .voucher-details {
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        
        .voucher-name {
            font-weight: 600;
            color: #333;
            font-size: 14px;
            line-height: 1.2;
            margin-bottom: 2px;
        }

        .voucher-value-row {
            font-size: 12px;
            color: #777;
            display: flex;
            align-items: center;
        }
        .voucher-label {
            margin-right: 4px;
        }
        .voucher-discount {
            font-weight: 500;
            color: #ea7404;
        }

        /* USUWANIE */
        .voucher-delete a {
            color: #ccc;
            padding: 5px;
            display: flex;
            align-items: center;
            transition: color 0.2s;
            /* Odsunięcie od prawej krawędzi jest realizowane przez padding rodzica */
        }
        .voucher-delete a:hover {
            color: #333;
            cursor: pointer;
        }
    </style>
    {/literal}

  {/block}
{/if}
{/strip}