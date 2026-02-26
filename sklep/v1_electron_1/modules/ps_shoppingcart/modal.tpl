{**
 * 2007-2025 PrestaShop
 * ... (Nagłówek bez zmian) ...
 *}
{strip}
{* --- PEŁNA AUTOMATYKA: Pobieranie progu darmowej dostawy z ustawień PrestaShop --- *}
{assign var="fs_threshold" value=Configuration::get('PS_SHIPPING_FREE_PRICE')}

{* Przeliczanie waluty *}
{assign var="context" value=Context::getContext()}
{if isset($context->currency->conversion_rate) && $context->currency->conversion_rate}
    {assign var="fs_threshold" value=$fs_threshold * $context->currency->conversion_rate}
{/if}

{* --- Logika obliczeń --- *}
{assign var="cart_total" value=$cart.subtotals.products.amount}
{assign var="fs_missing" value=$fs_threshold - $cart_total}

{* Obliczanie procentu paska *}
{if $fs_threshold > 0 && $fs_missing > 0}
    {assign var="fs_percent" value=($cart_total / $fs_threshold) * 100}
{else}
    {assign var="fs_percent" value=100}
    {assign var="fs_missing" value=0}
{/if}


<style>
    /* --- PASEK DARMOWEJ DOSTAWY --- */
    .fs-modal-banner {
        background: #fdfdfd;
        padding: 12px 20px;
        border-bottom: 1px solid #f0f0f0;
        text-align: center;
        width: 100%;
    }
    
    .fs-info-row {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        margin-bottom: 8px;
        font-size: 14px;
        color: #444;
    }
    
    .fs-icon {
        color: #ea7404;
        font-size: 18px !important;
    }
    
    .fs-highlight {
        color: #ea7404;
        font-weight: 700;
    }
    
    .fs-progress-bg {
        background: #eee;
        height: 6px;
        width: 100%;
        max-width: 400px;
        margin: 0 auto;
        border-radius: 3px;
        overflow: hidden;
    }
    
    .fs-progress-fill {
        background: #ea7404;
        height: 100%;
        width: 0%;
        border-radius: 3px;
        transition: width 0.5s ease;
    }

    /* --- GŁÓWNY KONTENER --- */
    #blockcart-modal {
        padding-right: 0 !important;
        pointer-events: auto !important;
        z-index: 1050;
    }

    /* DESKTOP: Flex dla wyśrodkowania */
    #blockcart-modal.in, 
    #blockcart-modal.show {
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        height: 100% !important;
    }

    /* --- OKNO DIALOGOWE (DESKTOP) --- */
    #blockcart-modal .modal-dialog {
        margin: 0 auto !important;
        max-width: 800px !important;
        width: 100% !important;
        pointer-events: auto !important; 
        transform: none !important;
        position: relative !important;
        top: auto !important;
        left: auto !important;
    }

    #blockcart-modal .modal-content {
        border: none;
        border-radius: 4px;
        box-shadow: 0 15px 50px rgba(0,0,0,0.15);
        background: #fff;
        max-height: 95vh;
        overflow-y: auto;
    }

    /* HEADER */
    #blockcart-modal .modal-header {
        background: #fff;
        border-bottom: 1px solid #f9f9f9;
        padding: 15px 20px 10px;
        text-align: center;
        display: block;
        position: relative;
    }

    #blockcart-modal .modal-title {
        font-size: 16px;
        font-weight: 600;
        color: #222;
        text-transform: none;
        display: flex !important;
        flex-direction: row;
        align-items: center;
        justify-content: center;
        column-gap: 8px !important;
        width: 100%;
        margin: 0 auto;
        
        /* ZMIANA: Wymuszamy jedną linię */
        flex-wrap: nowrap !important;
        white-space: nowrap !important;
    }

    #blockcart-modal .fbt-success-icon-large {
        width: auto !important;
        height: auto !important;
        background: transparent;
        color: #ea7404;
        display: flex !important;
        align-items: center;
        justify-content: center;
        font-size: 24px;
        box-shadow: none;
        margin: 0 !important;
        margin-right: 0 !important;
        padding: 0 !important;
        line-height: 1;
    }

    #blockcart-modal .fbt-success-icon-large i {
        margin: 0 !important;
        vertical-align: middle;
    }

    #blockcart-modal .close {
        position: absolute;
        right: 15px;
        top: 15px;
        opacity: 0.3;
        font-size: 22px;
        z-index: 10;
        font-weight: 300;
        color: #000;
        background: none;
        border: none;
        cursor: pointer;
    }
    #blockcart-modal .close:hover { opacity: 1; }

    /* BODY */
    #blockcart-modal .modal-body {
        padding: 0;
        background: #fff;
    }

    #blockcart-modal .fbt-modal-products-list {
        padding: 5px 40px 10px;
    }

    #blockcart-modal .fbt-modal-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 10px 0;
        border-bottom: 1px solid #f5f5f5;
    }

    #blockcart-modal .fbt-modal-item:last-child {
        border-bottom: none;
    }

    #blockcart-modal .fbt-modal-item-left {
        display: flex;
        align-items: center;
        gap: 25px;
    }

    #blockcart-modal .fbt-modal-img {
        width: 70px;
        height: 70px;
        object-fit: contain;
        border: none;
        padding: 0;
        background: #fff;
        
        /* ZMIANA: Centrowanie obrazka głównego */
        margin: 0 auto !important;
        align-self: center !important;
    }

    #blockcart-modal .fbt-modal-name {
        font-size: 13px;
        font-weight: 400;
        color: #333;
        line-height: 1.4;
        text-transform: none !important; 
        max-width: 350px;
        display: block;
    }

    #blockcart-modal .product-attributes {
        font-size: 11px;
        color: #888;
        margin-top: 3px;
    }

    #blockcart-modal .fbt-modal-qty {
        font-size: 12px;
        color: #555;
        margin-top: 4px;
    }

    #blockcart-modal .fbt-modal-price {
        font-size: 14px;
        font-weight: 500;
        color: #555;
        white-space: nowrap;
        margin-left: 20px;
    }

    /* PODSUMOWANIE */
    #blockcart-modal .fbt-modal-total-info {
        background: #fff;
        border-top: 1px solid #f0f0f0;
        padding: 15px 40px; 
        display: flex;
        flex-direction: column;
        gap: 8px; 
        margin-top: 0;
        width: 100%;
        box-sizing: border-box;
    }

    #blockcart-modal .fbt-total-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        width: 100%;
    }

    #blockcart-modal .fbt-modal-total-label {
        font-size: 12px;
        color: #777;
        text-transform: none;
        font-weight: 500;
        letter-spacing: 0.5px;
        text-align: left;
    }
    
    #blockcart-modal .total-price .fbt-modal-total-label {
        text-transform: uppercase;
        font-weight: 600; 
    }

    #blockcart-modal .fbt-modal-total-value {
        font-size: 20px;
        font-weight: 700;
        color: #ea7404;
        text-align: right;
    }
    
    /* FOOTER */
    #blockcart-modal .modal-footer {
        background: #fff;
        border-top: none;
        padding: 10px 6px 20px; 
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 20px;
    }

    #blockcart-modal .btn-secondary {
        background: #fff;
        border: 1px solid #e0e0e0;
        color: #666;
        text-transform: uppercase;
        font-size: 11px;
        font-weight: 600;
        padding: 10px 20px;
        border-radius: 4px;
        transition: all 0.2s;
        cursor: pointer;
    }
    #blockcart-modal .btn-secondary:hover {
        border-color: #ccc;
        color: #222;
    }

    #blockcart-modal .btn-primary {
        background: #ea7404;
        border: none;
        color: #fff;
        text-transform: uppercase;
        font-size: 12px;
        font-weight: 700;
        padding: 12px 30px;
        border-radius: 4px;
        box-shadow: 0 4px 15px rgba(234, 116, 4, 0.25);
        display: inline-flex;
        align-items: center;
        gap: 10px;
        transition: all 0.2s;
        cursor: pointer;
        text-decoration: none;
    }
    #blockcart-modal .btn-primary:hover {
        background: #d36a04;
        transform: translateY(-1px);
        box-shadow: 0 6px 20px rgba(234, 116, 4, 0.35);
        color: #fff;
    }

    #blockcart-modal .modal-footer-cross-sell {
        width: 100%;
        background: #f7f8fa;
        border-top: 1px solid #eee;
        padding: 10px 15px; 
    }

    /* --- MOBILE FIX (NAPRAWIONE) --- */
    @media (max-width: 768px) {
        
        /* 1. DOMYŚLNIE UKRYJ KONTENER - TO NAPRAWI PRZYCISKI */
        #blockcart-modal {
            display: none !important;
            padding: 0 !important;
            overflow-y: auto !important;
            z-index: 1050;
        }

        /* 2. POKAŻ TYLKO GDY AKTYWNY */
        #blockcart-modal.in,
        #blockcart-modal.show { 
            display: block !important;
            /* Nadpisuje flex z desktopu */
        }
        
        #blockcart-modal .modal-dialog { 
            width: 92% !important;
            max-width: 92% !important;
            margin: 10px auto 20px auto !important; 
            left: 0 !important;
            right: 0 !important;
            height: auto !important;
            transform: none !important;
        }

        #blockcart-modal .modal-content {
            max-height: none !important;
            overflow-y: visible !important;
            height: auto !important;
        }

        #blockcart-modal .modal-footer { 
            flex-direction: column;
            padding: 10px 15px 20px; 
        }
        #blockcart-modal .btn-primary, #blockcart-modal .btn-secondary { width: 100%;
            justify-content: center; }
        #blockcart-modal .fbt-modal-total-info { padding: 15px;
        }
        
        /* ZMIANA: Mniejsza czcionka nagłówka na mobile, żeby zmieścił się w 1 linii */
        #blockcart-modal .modal-title {
            font-size: 13px !important;
            flex-wrap: nowrap !important;
            white-space: nowrap !important;
        }
        #blockcart-modal .modal-title span {
            overflow: hidden;
            text-overflow: ellipsis; /* Na wypadek gdyby nadal było za szeroko */
        }
        #blockcart-modal .fbt-success-icon-large {
            font-size: 20px !important; /* Mniejsza ikona */
        }
    }
</style>

<div id="blockcart-modal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content" id="fbt-modal">
      
      {* --- PASEK DARMOWEJ DOSTAWY (AUTOMATYCZNY) --- *}
      {if $fs_threshold > 0}
          <div class="fs-modal-banner">
              <div class="fs-info-row">
                  <i class="material-icons fs-icon">&#xE558;</i> {* Ikona ciężarówki *}
                  {if $fs_missing > 0}
                      <span>Pozostało Ci tylko <span class="fs-highlight">{$fs_missing|string_format:"%.2f"} zł</span> do darmowej dostawy!</span>
                  {else}
                      <span class="fs-highlight">Świetnie! Masz DARMOWĄ DOSTAWĘ!</span>
                  {/if}
              </div>
              <div class="fs-progress-bg">
                  <div class="fs-progress-fill" style="width: {$fs_percent}%;"></div>
              </div>
          </div>
      {/if}

      {* --- HEADER (STANDARD) --- *}
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
        <div class="modal-title">
            <div class="fbt-success-icon-large">
                <i class="material-icons">&#xE876;</i>
            </div>
            <span>{l s='Produkt został poprawnie dodany do koszyka' d='Shop.Theme.Checkout'}</span>
        </div>
      </div>

      {* --- BODY --- *}
      <div class="modal-body">
         <div class="fbt-modal-products-list">
            {* Pojedynczy produkt dodany do koszyka *}
            <div class="fbt-modal-item">
         
                <div class="fbt-modal-item-left">
                    {* ZDJĘCIE *}
                    {if $product.default_image}
                        <img src="{$product.default_image.medium.url}" 
                             alt="{$product.default_image.legend}" 
                             loading="lazy" 
                             class="fbt-modal-img">
                    {else}
                         <img src="{$urls.no_picture_image.bySize.medium_default.url}" 
                           loading="lazy" 
                           class="fbt-modal-img">
                    {/if}

                    {* INFO O PRODUKCIE *}
                    <div>
                        <span class="fbt-modal-name">{$product.name}</span>
                        {if isset($product.attributes) && $product.attributes}
                            <div class="product-attributes">
                                {foreach from=$product.attributes item="property_value" key="property"}
                                    <span><strong>{$property}</strong>: {$property_value}</span><br>
                                {/foreach}
                            </div>
                        {/if}
                        
                        {* --- DODANO WYŚWIETLANIE ILOŚCI TUTAJ --- *}
                        <div class="fbt-modal-qty">
                            {l s='Quantity' d='Shop.Theme.Checkout'}: <strong>{$product.cart_quantity}</strong>
                        </div>
                        {* ---------------------------------------- *}
                        
                    </div>
                </div>
               
                {* CENA *}
                <div class="fbt-modal-price">{$product.price}</div>
            </div>
         </div>
         
         {* --- PODSUMOWANIE KOSZYKA --- *}
         <div class="fbt-modal-total-info">
             <div class="fbt-total-row">
                 <span class="fbt-modal-total-label">
                     {l s='Ilość produktów w koszyku' d='Shop.Theme.Checkout'} ({$cart.products_count})
                 </span>
             </div>
        
             <div class="fbt-total-row total-price">
                 <span class="fbt-modal-total-label">{l s='Wartość zamówienia' d='Shop.Theme.Checkout'}:</span>
                 {* ZMIANA TUTAJ: Używamy subtotals.products.value zamiast totals.total.value *}
                 <span class="fbt-modal-total-value">{$cart.subtotals.products.value}</span>
             </div>
         </div>
      </div>

      {* --- FOOTER --- *}
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">
            {l s='Continue shopping' d='Shop.Theme.Actions'}
        </button>
        <a href="{$cart_url}" class="btn btn-primary">
            {l s='Proceed to checkout' d='Shop.Theme.Actions'} <i class="material-icons">&#xE315;</i>
        </a>
       </div>

      {* --- CZĘSTO KUPOWANE RAZEM (MODUŁ NA DOLE) --- *}
      <div class="modal-footer-cross-sell">
          {hook h='displayCartModalFooter' product=$product}
      </div>

    </div>
  </div>
</div>

<script>
  (function($) {
      /* 1. Najpierw usuwamy stare zdarzenia, żeby się nie dublowały */
      $('body').off('hidden.bs.modal', '#blockcart-modal');
      $('body').off('click', '#blockcart-modal');

      /* 2. Obsługa kliknięcia w szare tło (zamykanie modala) */
      $('body').on('click', '#blockcart-modal', function(e) {
        if (e.target === this) {
          $(this).modal('hide');
        }
      });
      /* 3. TO JEST KLUCZOWE: Wykrycie momentu zniknięcia okienka */
      $('body').on('hidden.bs.modal', '#blockcart-modal', function () {
          /* Jeśli jesteśmy na karcie produktu (body id="product") -> odświeżamy */
          if ($('body#product').length > 0) {
              window.location.reload();
          }
      });
  })(jQuery);
</script>
{/strip}