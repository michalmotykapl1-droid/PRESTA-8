{**
 * 2007-2025 PrestaShop
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License 3.0 (AFL-3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future.
 * If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information.
 *
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2007-2025 PrestaShop SA
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 * International Registered Trademark & Property of PrestaShop SA
 *}
{strip}

{* --- NOWY BLOK CSS WBUDOWANY W PLIK TPL (WERSJA "FULL-WIDTH") --- *}
<style>
/* 1. Definiujemy wygląd bazowy dla naszej nowej klasy */
a.przycisk-kasy-custom {
    display: block !important; /* ZMIANA: Przycisk jako blok */
    width: 100% !important; /* ZMIANA: Pełna szerokość */
    text-align: center !important; /* ZMIANA: Wyśrodkowanie tekstu wewnątrz */
    
    vertical-align: middle;
    padding: 12px 15px !important; /* ZMIANA: Lepszy padding dla pełnej szerokości */
    border-radius: 3px;
    font-weight: 500;
    line-height: normal;
    cursor: pointer;
    margin-bottom: 5px;
    position: relative;
    text-decoration: none !important;
    outline: none !important;
    border: none !important;
    transition: background-color 0.3s ease !important;
    
    /* NASZE NOWE KOLORY */
    background-color: #f57c00 !important; /* Pomarańczowy */
    color: #ffffff !important;
}

/* 2. Style dla tekstu i ikony wewnątrz */
a.przycisk-kasy-custom span,
a.przycisk-kasy-custom i {
    color: #ffffff !important;
    position: relative;
    z-index: 2; /* Ważne dla animacji :after */
    transition: color 0.3s ease !important;
    vertical-align: middle;
}

/* 3. Re-implementacja animacji hover motywu, ale z prawidłowymi kolorami */
a.przycisk-kasy-custom:after {
    content: '';
    position: absolute;
    right: 0;
    left: 0;
    bottom: 0;
    width: 100%;
    height: 0;
    background-color: #e67000 !important; /* Ciemniejszy pomarańczowy dla hover */
    transition: all 0.4s ease-in-out;
    border-radius: 3px;
    overflow: hidden;
    z-index: 1;
}

a.przycisk-kasy-custom:hover:after {
    height: 100%;
    top: 0;
}

/* 4. Zabezpieczenie :hover na głównym elemencie */
a.przycisk-kasy-custom:hover {
    background-color: #e67000 !important;
    color: #ffffff !important;
}
</style>
{* --- KONIEC NOWEGO BLOKU CSS --- *}


{block name='cart_detailed_actions'}
  <div class="checkout cart-detailed-actions card-block">
    {if $cart.minimalPurchaseRequired}
      <div class="alert alert-warning" role="alert">
        {$cart.minimalPurchaseRequired}
      </div>
      <div class="text-sm-center">
        <button type="button" class="tvall-inner-btn-cancel disabled" disabled>
          <span>{l s='Proceed to checkout' d='Shop.Theme.Actions'}</span>
          <i class='material-icons'>&#xe5cc;</i>
        </button>
      </div>
    {elseif empty($cart.products) }
      <div class="text-sm-center">
        <button type="button" class="tvall-inner-btn-cancel disabled" disabled>
          <span>{l s='Proceed to checkout' d='Shop.Theme.Actions'}</span>
          <i class='material-icons'>&#xe5cc;</i>
        </button>
      </div>
    {else}
      {* ZMIANA: Usunięto klasę 'text-sm-center' z diva, aby przycisk 100% działał poprawnie *}
      <div class="">
        <a href="{$urls.pages.order}" class="przycisk-kasy-custom tvprocess-to-checkout">
          <span>{l s='Proceed to checkout' d='Shop.Theme.Actions'}</span>
          <i class='material-icons'>&#xe5cc;</i>
        </a>
        {hook h='displayExpressCheckout'}
      </div>
    {/if}
  </div>
{/block}
{/strip}