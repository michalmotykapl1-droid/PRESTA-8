{**
 * 2007-2025 PrestaShop
 * ... (licencja) ...
 *}
{strip}
{block name='cart_detailed_totals'}
<div class="cart-detailed-totals">

  {* --- STYLE: PEŁNA INTEGRACJA Z WYGLĄDEM KOSZYKA --- *}
  {literal}
  <style>
      .cart-detailed-totals .separator, .cart-summary .separator { display: none !important; border: none !important; }
      .cart-detailed-totals .card-block, .cart-summary .card-block { border: none !important; }
      .cart-summary-line { border: none !important; }
      
      /* Styl dla wiersza rabatu - taki sam jak inne wiersze */
      .cart-summary-line.cart-total-savings {
          display: flex;
          justify-content: space-between;
          align-items: center;
          margin-bottom: 0.5rem;
          background-color: transparent;
          padding: 0;
      }
      
      /* Etykieta "Rabat" - dziedziczy styl z motywu */
      .cart-summary-line.cart-total-savings .label {
          color: inherit; 
      }

      /* Wartość kwotowa RABATU - TYLKO KOLOR POMARAŃCZOWY */
      .cart-summary-line.cart-total-savings .value {
          color: #ea7404 !important; /* Pomarańczowy */
          font-weight: 600;
      }
  </style>
  {/literal}

  <div class="card-block">
    
    {* ZMIENNA DO PRZECHOWANIA WARTOŚCI KODU RABATOWEGO (VOUCHERA) *}
    {assign var="voucher_amount_cached" value=0}

    {* 1. STANDARDOWE WIERSZE *}
    {foreach from=$cart.subtotals item="subtotal"}
      {if isset($subtotal.value) && $subtotal.type !== 'tax'}
        
        {* JEŚLI TO RABAT (KOD RABATOWY) - UKRYWAMY I ZAPISUJEMY KWOTĘ *}
        {if $subtotal.type === 'discount'}
            {if isset($subtotal.amount)}
                {assign var="voucher_amount_cached" value=$subtotal.amount}
            {/if}
            {continue}
        {/if}

        <div class="cart-summary-line" id="cart-subtotal-{$subtotal.type}">
          <span class="label{if 'products' === $subtotal.type} js-subtotal{/if}">
            {if 'products' == $subtotal.type}
                Wartość zamówienia
            {else}
               {$subtotal.label|default:''}
            {/if}
          </span>
          <span class="value">{$subtotal.value|default:''}</span>
          {if $subtotal.type === 'shipping'}
              <div><small class="value">{hook h='displayCheckoutSubtotalDetails' subtotal=$subtotal}</small></div>
           {/if}
        </div>
      {/if}
    {/foreach}


    {* 2. OBLICZENIA *}
    
    {assign var="product_savings_total" value=0}

    {* A. Zliczamy różnicę w cenie produktów (Przekreślone ceny) *}
    {foreach from=$cart.products item=product}
        {if $product.has_discount}
            {assign var="saving_per_unit" value=$product.regular_price_amount - $product.price_amount}
            {assign var="saving_total" value=$saving_per_unit * $product.quantity}
            {assign var="product_savings_total" value=$product_savings_total + $saving_total}
        {/if}
    {/foreach}

    {* B. Sumujemy: Oszczędność na produktach + Oszczędność z kodu rabatowego *}
    {assign var="total_savings_combined" value=$product_savings_total + $voucher_amount_cached}

    {* 3. WYŚWIETLANIE WYNIKU *}
    {if $total_savings_combined > 0}
        <div class="cart-summary-line cart-total-savings">
          <span class="label">Rabat</span>
          <span class="value">
              - {Tools::displayPrice($total_savings_combined)}
          </span>
        </div>
    {/if}

  </div>

  <div class="card-block">
    <div class="cart-summary-line cart-total">
      {* ZMIANA: Zmieniono etykietę na "Do zapłaty", styl kwoty domyślny *}
      <span class="label">RAZEM Do zapłaty</span>
      <span class="value">{$cart.totals.total.value|default:''}</span>
    </div>

    <div class="cart-summary-line">
      <small class="label">w tym VAT</small>
      <small class="value">{$cart.subtotals.tax.value|default:''}</small>
    </div>
  </div>

</div>
{/block}
{/strip}