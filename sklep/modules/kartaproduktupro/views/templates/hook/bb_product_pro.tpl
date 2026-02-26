{*
 * KARTA PRODUKTU PRO – Wersja: Horizontal Light
 *}
<div class="kpp-container">
  
  {* 1. BEZPIECZEŃSTWO *}
  <div class="kpp-item">
    <div class="kpp-icon">
      <i class="material-icons">&#xE899;</i> {* Icon: lock *}
    </div>
    <div class="kpp-content">
      <div class="kpp-title">BEZPIECZNE ZAKUPY</div>
      <div class="kpp-desc">Szyfrowanie SSL</div>
    </div>
  </div>

  {* 2. PŁATNOŚCI *}
  <div class="kpp-item">
    <div class="kpp-icon">
      <i class="material-icons">&#xE870;</i> {* Icon: payment card *}
    </div>
    <div class="kpp-content">
      <div class="kpp-title">SZYBKIE PŁATNOŚCI</div>
      <div class="kpp-desc">BLIK, PayPo, Karty</div>
    </div>
  </div>

  {* 3. ZWROTY *}
  <div class="kpp-item">
    <div class="kpp-icon">
      <i class="material-icons">&#xE863;</i> {* Icon: loop/refresh *}
    </div>
    <div class="kpp-content">
      {* Pobieranie ustawienia dni zwrotu z PrestaShop *}
      {assign var="return_days" value=Configuration::get('PS_ORDER_RETURN_NB_DAYS')}
      {if !$return_days}{assign var="return_days" value=14}{/if}
      
      <div class="kpp-title">{$return_days} DNI NA ZWROT</div>
      <div class="kpp-desc">Wygodny zwrot towaru</div>
    </div>
  </div>

</div>