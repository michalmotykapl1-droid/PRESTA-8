{**
 * 2007-2025 PrestaShop
 * ...
 *}
{strip}
{extends file=$layout}

{block name='content'}

{* --- STYLE: DARMOWA DOSTAWA + DATA DOSTAWY (WYMUSZONE ROZMIARY) --- *}
{literal}
<style>
    /* 1. Pasek darmowej dostawy */
    .fs-cart-banner {
        background: #fdfdfd;
        padding: 15px 20px 10px 20px;
        text-align: center;
        width: 100%;
        box-sizing: border-box;
        margin-bottom: 0; 
        border-radius: 4px 4px 0 0;
    }
    
    .fs-info-row {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        margin-bottom: 10px;
        font-size: 13px;
        /* Wzorzec wielkości */
        color: #444;
        line-height: 1.4;
        flex-wrap: nowrap;
    }
    
    .fs-icon {
        color: #ea7404;
        font-size: 20px !important;
        margin: 0;
        line-height: 1;
        flex-shrink: 0;
    }
    
    .fs-text-content { text-align: left; }
    .fs-highlight { color: #ea7404; font-weight: 700; font-size: 13px; }
    
    .fs-progress-bg {
        background: #eee;
        height: 6px;
        width: 100%;
        margin: 0 auto;
        border-radius: 4px;
        overflow: hidden;
    }
    
    .fs-progress-fill {
        background: #ea7404;
        height: 100%;
        width: 0%;
        border-radius: 4px;
        transition: width 0.5s ease;
        background: linear-gradient(90deg, #ea7404 0%, #ff9d42 100%);
    }

    /* 2. STICKY SIDEBAR */
    @media (min-width: 992px) {
        .cart-grid {
            display: flex !important;
            flex-wrap: wrap !important;
            align-items: stretch !important;
        }
        .cart-grid-body, .cart-grid-right {
            float: none !important;
            display: block !important;
            height: auto !important;
        }
        .bb-sticky-right {
            position: -webkit-sticky;
            position: sticky;
            top: 140px;
            z-index: 0;
            width: 100%;
        }
    }

    /* 3. STYL DATA DOSTAWY - WYMUSZONA WIELKOŚĆ */
    .delivery-promise-container {
        display: flex;
        align-items: center; 
        background-color: #fdfdfd;
        
        border-bottom: 1px solid #f0f0f0; 
        border-top: 1px dashed #e0e0e0;
        border-radius: 0 0 4px 4px;

        padding: 12px 20px;
        margin-bottom: 15px;
    }
    .dp-icon {
        color: #ea7404;
        font-size: 20px !important;
        /* Identiko jak ciężarówka */
        margin-right: 12px;
        display: flex;
        align-items: center;
        flex-shrink: 0;
    }
    .dp-content {
        font-size: 12px !important;
        /* WYMUSZONE 13PX */
        color: #444 !important;
        /* Ten sam kolor co wyżej */
        line-height: 1.4 !important;
        text-align: left;
    }
    .dp-date {
        display: block;
        color: #ea7404;
        font-weight: 700;
        font-size: 13px !important; /* WYMUSZONE 13PX */
        margin-top: 2px;
    }
</style>
{/literal}

  <div id="main">
    <div class="cart-grid row">

      <div class="cart-grid-body col-xs-12 col-lg-8">

        <div class="card cart-container">
          <div class="card-block">
            <h1 class="h1">{l s='Shopping Cart' d='Shop.Theme.Checkout'}</h1>
           </div>
          <hr class="separator">
          {block name='cart_overview'}
            {include file='checkout/_partials/cart-detailed.tpl' cart=$cart}
          {/block}
        </div>

        {* --- STREFA KOSZYKA --- *}
        <div class="cart-zone-wrapper">
            {hook h='displayBBCartZone'}
        </div>
        {* ----------------------- *}

        {* --- PRZYCISK 'KONTYNUUJ ZAKUPY' WYŁĄCZONY --- *}
        {* {block name='continue_shopping'}
          <a class="tv-continue-shopping-btn tvall-inner-btn" href="{$urls.pages.index}">
             <i class="material-icons">chevron_left</i>
             <span>{l s='Continue shopping' d='Shop.Theme.Actions'}</span>
          </a>
        {/block}
        *}
        {* --------------------------------------------- *}

         {block name='hook_shopping_cart_footer'}
          {hook h='displayShoppingCartFooter'}
        {/block}
      </div>

      <div class="cart-grid-right col-xs-12 col-lg-4">
        
        <div class="bb-sticky-right">

            {block name='cart_summary'}
              <div class="card cart-summary">

                {block name='hook_shopping_cart'}
                 
                 {* --- 1. PASEK DARMOWEJ DOSTAWY --- *}
                 {block name='bb_free_shipping_bar'}
                  
                  {capture name='bb_free_ship_cfg'}{Configuration::get('PS_SHIPPING_FREE_PRICE')}{/capture}
                  {assign var='free_shipping_threshold' value=$smarty.capture.bb_free_ship_cfg}
           
                  <div id="js-cart-page-shipping-bar" class="fs-cart-banner" data-free-shipping-threshold="{$free_shipping_threshold}">
                    
                    {if $free_shipping_threshold <= 0}
                      <div class="fs-info-row">
                          <span>Konfiguracja darmowej wysyłki nie jest ustawiona.</span>
                      </div>
                    {else}
                      {assign var='current_total' value=$cart.subtotals.products.amount}
                      {math equation="x - y" x=$free_shipping_threshold y=$current_total assign="remaining"}
            
                      {if $remaining < 0}
                        {assign var='remaining' value=0}
                      {/if}
                      {math equation="(x * 100) / y" x=$current_total y=$free_shipping_threshold assign="percent"}
         
                      {if $percent > 100}
                        {assign var='percent' value=100}
                      {/if}
                      {if $percent < 0}
                        {assign var='percent' value=0}
                      {/if}
                      
                      <div class="fs-info-row">
                          <i class="material-icons fs-icon">&#xE558;</i>
                          <div class="fs-text-content">
                              {if $remaining > 0}
                                Pozostało Ci tylko&nbsp;<span class="fs-highlight">{$remaining|number_format:2:',':' '} zł</span>&nbsp;do darmowej dostawy!
                              {else}
                                <span class="fs-highlight">Gratulacje! Twoje zamówienie kwalifikuje się do darmowej dostawy.</span>
                              {/if}
                          </div>
                      </div>
                      
                      <div class="fs-progress-bg">
                        <div class="fs-progress-fill" style="width:{$percent|intval}%"></div>
                      </div>
                    
                    {/if}
                  </div>
                {/block}
                
                {* --- 2. DATA DOSTAWY (NOWA SEKCJA) --- *}
                {* LOGIKA SMARTY *}
                {assign var="cutoff_hour" value=10} {* Godzina graniczna *}
                {assign var="now_ts" value=$smarty.now}
                {assign var="current_hour" value=$now_ts|date_format:"%H"}
                
                {* Ustalamy wysyłkę *}
                {if $current_hour >= $cutoff_hour}
                    {assign var="shipping_start_ts" value=$now_ts + 86400}
                    {assign var="order_msg" value="do godziny <strong>10:00</strong> następnego dnia"}
                {else}
                    {assign var="shipping_start_ts" value=$now_ts}
                    {assign var="order_msg" value="dzisiaj"}
                {/if}

                {* Przesuwamy wysyłkę z weekendu na poniedziałek *}
                {assign var="ship_day_num" value=$shipping_start_ts|date_format:"%u"}
                {if $ship_day_num == 6} {* Sobota -> Pon +2 dni *}
                    {assign var="shipping_start_ts" value=$shipping_start_ts + (2 * 86400)}
                {elseif $ship_day_num == 7} {* Niedziela -> Pon +1 dzień *}
                    {assign var="shipping_start_ts" value=$shipping_start_ts + 86400}
                {/if}

                {* Dostawa = Wysyłka + 2 dni *}
                {assign var="delivery_ts" value=$shipping_start_ts + (2 * 86400)}
                
                {* Korekta weekendowa dostawy *}
                {assign var="del_day_num" value=$delivery_ts|date_format:"%u"}
                {if $del_day_num == 6}
                    {assign var="delivery_ts" value=$delivery_ts + (2 * 86400)}
                {elseif $del_day_num == 7}
                    {assign var="delivery_ts" value=$delivery_ts + 86400}
                {/if}

                {* Tłumaczenia i indeksy *}
                {assign var="days_pl" value=['', 'Poniedziałek', 'Wtorek', 'Środa', 'Czwartek', 'Piątek', 'Sobota', 'Niedziela']}
                {assign var="months_pl" value=['', 'stycznia', 'lutego', 'marca', 'kwietnia', 'maja', 'czerwca', 'lipca', 'sierpnia', 'września', 'października', 'listopada', 'grudnia']}
               
                {assign var="f_day_idx" value=$delivery_ts|date_format:"%u"|intval}
                {assign var="f_mon_idx" value=$delivery_ts|date_format:"%m"|intval}
                {assign var="f_day" value=$delivery_ts|date_format:"%e"}

                {* WIDOK HTML *}
                <div class="delivery-promise-container">
                    <div class="dp-icon">
                        <i class="material-icons">schedule</i>
                    </div>
                    <div class="dp-content">
                        Zamów {$order_msg nofilter}, a przewidywana dostawa:<br>
                        <span class="dp-date">
                            {$days_pl[$f_day_idx]}, {$f_day|trim} {$months_pl[$f_mon_idx]}
                        </span>
                    </div>
                </div>
                {* ------------------------------------- *}

                  {hook h='displayShoppingCart'} 
                {/block}

                {block name='cart_voucher'}
                   {include file='checkout/_partials/cart-voucher.tpl'}
                {/block}

                {block name='cart_totals'}
                  {include file='checkout/_partials/cart-detailed-totals.tpl' cart=$cart} 
                {/block}

                {block name='cart_actions'}
                   {include file='checkout/_partials/cart-detailed-actions.tpl' cart=$cart}
                {/block}

              </div>
            {/block}

            {block name='hook_reassurance'}
               {hook h='displayReassurance'}
            {/block}

            {block name='payment_icons'}
            <div class="payment-icons-block block-reassurance-item">
                <span class="reassurance-icon">
                    <i class="material-icons">credit_card</i>
                </span>
                <div class="reassurance-text">
                    <span class="payment-icons-list-text">BLIK, Przelewy24, Visa, Mastercard</span>
                </div>
            </div>
            {/block}

        </div>

      </div>

    </div>
  </div>

  <script>
    (function () {
      function syncShippingBars() {
        try {
          var sourceBar = document.querySelector('#_desktop_cart .fs-cart-banner');
          var targetBar = document.getElementById('js-cart-page-shipping-bar');

          if (!sourceBar || !targetBar) return;

          var sourceText = sourceBar.querySelector('.fs-text-content');
          var sourceFill = sourceBar.querySelector('.fs-progress-fill');
          
          var targetText = targetBar.querySelector('.fs-text-content');
          var targetFill = targetBar.querySelector('.fs-progress-fill');

          if (sourceText && targetText) {
            targetText.innerHTML = sourceText.innerHTML;
          }

          if (sourceFill && targetFill) {
            targetFill.style.width = sourceFill.style.width;
          }

        } catch (e) {
          console.error('BB Free Shipping bar sync error', e);
        }
      }

      if (typeof prestashop !== 'undefined' && prestashop.on) {
        prestashop.on('updateCart', function () {
          setTimeout(syncShippingBars, 500);
        });
      }

      document.addEventListener('DOMContentLoaded', function () {
        setTimeout(syncShippingBars, 500);
      });
    })();
  </script>

{/block}
{/strip}