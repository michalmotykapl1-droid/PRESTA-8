/**
 * 2007-2025 PrestaShop
 * ...
 */
$(document).ready(function(){

  // ===== Ustawienia interwałów =====
  const DISPLAY_INTERVAL_MINUTES     = 5;    // co 5 min
  const HIDE_AFTER_SECONDS           = 8;    // pokaz przez 8 s
  const PAUSE_BETWEEN_PRODUCTS_SEC   = 120;  // w pętli co 2 min

  // ===== Funkcja pokazująca kolejne popupy =====
  function showNextProductPopup(idx) {
    const $popups = $('.tvcms-prod-popup');
    if ($popups.length === 0) return;

    $popups.removeClass('show').eq(idx).addClass('show');

    // chowaj po HIDE_AFTER_SECONDS
    setTimeout(() => {
      $popups.removeClass('show');
    }, HIDE_AFTER_SECONDS * 1000);

    // planuj kolejny popup
    setTimeout(() => {
      showNextProductPopup((idx + 1) % $popups.length);
    }, PAUSE_BETWEEN_PRODUCTS_SEC * 1000);
  }

  // ===== Logika inicjalizacji =====
  function initProductPopupLogic() {
    // nie pokazuj na stronie produktu
    if ($('body').hasClass('product-page')) return;

    // jeśli użytkownik ręcznie zamknął – nie pokazuj ponownie
    if (localStorage.getItem('tvcmsProductPopupClosed') === 'true') {
      return;
    }

    const last = localStorage.getItem('tvcmsLastProductPopupTime');
    const now  = Date.now();
    const intervalMs = DISPLAY_INTERVAL_MINUTES * 60 * 1000;

    if (!last || (now - parseInt(last, 10) > intervalMs)) {
      // pierwszy pokaz lub minął interwał
      localStorage.setItem('tvcmsLastProductPopupTime', now.toString());
      showNextProductPopup(0);
    } else {
      // odczekaj pozostały czas
      const remaining = intervalMs - (now - parseInt(last, 10));
      setTimeout(() => {
        localStorage.setItem('tvcmsLastProductPopupTime', Date.now().toString());
        showNextProductPopup(0);
      }, remaining);
    }
  }

  // ===== Obsługa zamknięcia =====
  $('.tvprodpopup-close').on('click', function(){
    $('.tvcms-prod-popup').removeClass('show');
    localStorage.setItem('tvcmsProductPopupClosed', 'true');
  });

  // ===== Uruchom wszystko =====
  initProductPopupLogic();

});
