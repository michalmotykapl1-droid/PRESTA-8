/**
 * 2007-2025 PrestaShop
 * AFL 3.0
 */
var storage;
var langId = document.getElementsByTagName("html")[0].getAttribute("lang");
var currentMegaMenuModule = (typeof currentThemeName !== 'undefined' ? currentThemeName : 'theme') + "_mega_menu_" + langId;
var dataCachem;

// ===== START POPRAWKI =====
// Przeniesiono logikę akordeonu desktop do oddzielnej funkcji,
// aby można ją było wywołać po załadowaniu z cache LUB z AJAX.

function isDesktop() { return window.innerWidth >= 992; }

/**
 * Inicjalizuje logikę akordeonu na desktopie.
 * Szuka nagłówków (.item-header), ukrywa linki (.item-line) pod nimi
 * i dodaje obsługę kliknięcia.
 */
function initDesktopAccordion() {
  // Jeśli nie desktop, wyjdź (chociaż ta funkcja jest wołana tylko dla desktop)
  if (!isDesktop()) {
    $('.tv-menu-horizontal').off('click.megamenu');
    $('.tv-menu-horizontal .item-header > a').removeClass('is-expandable');
    return;
  }

  // Logika JS dla desktopa (przeniesiona z ajaxComplete)
  // 1. Znajdź wszystkie nagłówki, które mają pod sobą linki
  $('.tv-menu-horizontal .item-header').each(function() {
    var $header = $(this);
    // Sprawdź, czy NASTĘPNE elementy (aż do kolejnego .item-header) zawierają .item-line
    if ($header.nextUntil('.item-header').filter('.item-line').length > 0) {
      $header.find('> a').addClass('is-expandable'); // Dodaj klasę do strzałki
    }
  });

  // 2. Ukryj wszystkie linki pod-menu (teraz będą widoczne tylko nagłówki)
  // POPRAWKA: Ukryj tylko te, które są pod aktywnym nagłówkiem, jeśli nie jest aktywny
  $('.tv-menu-horizontal .item-header:not(.header-active)').each(function() {
      $(this).nextUntil('.item-header').filter('.item-line').hide();
  });


  // 3. Dodaj obsługę kliknięcia (tylko do nagłówków z klasą .is-expandable)
  $('.tv-menu-horizontal')
    .off('click.megamenu') // Usuń stare handlery (na wszelki wypadek)
    .on('click.megamenu', 'a.is-expandable', function(e) {
        
        // ===== START POPRAWKI (KLIKNIĘCIE STRZAŁKI vs TEKST) =====
        var $a = $(this);
        // Obliczamy prawą krawędź linku
        var aRightEdge = $a.offset().left + $a.outerWidth();
        // Pobieramy pozycję X kliknięcia
        var clickX = e.pageX;
        // Definiujemy "strefę kliknięcia" strzałki (np. 45px od prawej krawędzi)
        var arrowClickableWidth = 45; 

        // Sprawdź, czy kliknięcie NIE JEST na obszarze strzałki
        if (clickX < (aRightEdge - arrowClickableWidth)) {
            // To jest kliknięcie na TEKST.
            // Nie robimy nic (nie blokujemy linku), pozwalamy mu normalnie działać.
            return;
        }

        // To jest kliknięcie na STRZAŁKĘ (obszar 45px po prawej).
        // Blokujemy domyślną akcję (przejście do linku) i rozwijamy menu.
        e.preventDefault(); // Nie idź do linku
        e.stopPropagation();
        
        var $header = $(this).parent('.item-header');
        $header.toggleClass('header-active'); // Zmienia wygląd strzałki
        // Pokaż/ukryj wszystkie elementy .item-line aż do następnego nagłówka
        $header.nextUntil('.item-header').filter('.item-line').slideToggle(250);
        // ===== KONIEC POPRAWKI (KLIKNIĘCIE STRZAŁKI vs TEKST) =====
    });
}
// ===== KONIEC POPRAWKI =====


jQuery(document).ready(function($) {
  storage = $.localStorage;

  function storageGet(key) { return "" + storage.get(currentMegaMenuModule + key); }
  function storageSet(key, value) { storage.set(currentMegaMenuModule + key, value); }
  function storageClear(key) { storage.remove(currentMegaMenuModule + key); }

  // isDesktop() jest teraz na górze pliku
  function isMobile()  { return window.innerWidth <= 991; }

  var isCallMenu = false;

  // ---- Ładowanie HTML (cache + AJAX) – jak w Twojej wersji ----
  var getMegaMenuAjax = function() {
    if (!isCallMenu) {
      // cache
      var data = storageGet("");
      dataCachem = data;
      storageClear("");
      if (data !== '' && data !== 'null' && data !== 'undefined') {
        if (isMobile()) {
          $('#tvmobile-megamenu').html(data);
        } else {
          $('#tvdesktop-megamenu').html(data);
          // ===== POPRAWKA: Odpal akordeon po wstawieniu z cache =====
          initDesktopAccordion();
        }
        megaMenuSlider();
      }
      // ajax
      $.ajax({
        type: 'POST',
        url: gettvcmsmegamenulink,
        success: function(resp) {
          // Anty-mig: jeśli user dotknął strzałki (body.mm-touched) – nie podmieniaj DOM na mobile
          if (isMobile() && $('body').hasClass('mm-touched')) {
            dataCachem = resp;
            return;
          }
          if (dataCachem === '' || dataCachem === 'null' || dataCachem === 'undefined') {
            if (isMobile()) {
              $('#tvmobile-megamenu').html(resp);
            } else {
              $('#tvdesktop-megamenu').html(resp);
              // ===== POPRAWKA: Odpal akordeon po wstawieniu z AJAX =====
              initDesktopAccordion();
            }
            megaMenuSlider();
          }
          dataCachem = resp;
        },
        error: function(jqXHR, textStatus, errorThrown) {
          if (window && window.console) console.log(textStatus, errorThrown);
        }
      });
    }
    isCallMenu = true;
  };

  $(window).on('resize', function() { megaMenuSlider(); });

  // Ładuj zawsze
  getMegaMenuAjax();

  window.addEventListener("beforeunload", function () {
    storageSet("", dataCachem);
    return '';
  });

  function responsiveMenuPopup($this) {
    if (isDesktop()) {
      // oryginalnie puste
    }
  }

  $(document).on('touchstart mouseover', '.container_tv_megamenu ul.menu-content li.level-1', function() {
    responsiveMenuPopup(this);
  });

  function megaMenuSlider() {
    // oryginalnie puste
  }

  // --- AKORDEON Z AJAX: ZASTĄPIONY NOWĄ FUNKCJĄ ---
  // Stara logika `ajaxComplete` została przeniesiona do funkcji initDesktopAccordion()
  // i jest teraz wołana poprawnie po wstawieniu HTML.
  // Poniższy blok nie jest już potrzebny.
  /*
  $(document).ajaxComplete(function(event, xhr, settings) {
    if (settings.url === gettvcmsmegamenulink) {
      // ... (stary kod) ...
    }
  });
  */
  // --- KONIEC: AKORDEON DESKTOP ---
});

/* --- MOBILE: tylko strzałką; akordeon + twarde show/hide + auto-scroll --- */
(function () {
  function isMobile() { return window.innerWidth <= 991; }

  // anty-duplikat: click po touchstart
  function dedupe($el) {
    var now = Date.now();
    var last = $el.data('mmTs') || 0;
    if (now - last < 250) return false;
    $el.data('mmTs', now);
    return true;
  }

  // znajdź scrollowalny kontener panelu (nie całej strony)
  function findScrollContainer($start) {
    var $node = $start.closest('#tvmobile-megamenu, .tv-menu-horizontal');
    while ($node.length) {
      var el = $node[0];
      var oy = getComputedStyle(el).overflowY;
      if ((oy === 'auto' || oy === 'scroll') && el.scrollHeight > el.clientHeight) return $node;
      $node = $node.parent();
    }
    return $('html, body'); // fallback
  }

  // przewiń tak, aby li.level-1 był przy górnej krawędzi kontenera
  function scrollL1IntoView($li) {
    var $container = findScrollContainer($li);
    if (!$container.length) return;
    var pad = 12;
    var containerTop = ($container.is('html, body')) ? 0 : $container.offset().top;
    var current = ($container.is('html, body')) ? $(window).scrollTop() : $container.scrollTop();
    var target = current + ($li.offset().top - containerTop) - pad;
    $container.stop(true).animate({ scrollTop: Math.max(0, target) }, 250);
  }

  // zdejmij stare i ustaw nowe handlery
  $(document).off('touchstart.mm click.mm', '#tvmobile-megamenu .icon-drop-mobile');
  $(document).on('touchstart.mm click.mm', '#tvmobile-megamenu .icon-drop-mobile', function (e) {
    if (!isMobile()) return;

    var $li = $(this).closest('li');                 // aktualny element menu
    if (!dedupe($li)) { e.preventDefault(); return; }

    $('body').addClass('mm-touched');                // blokada podmiany DOM przez AJAX
    e.preventDefault();
    e.stopPropagation();
    e.stopImmediatePropagation();

    // najczęstsze kontenery submenu
    var $submenu = $li.children('.menu-dropdown, .tv-sub-menu, ul, .item-content, .menu-content').first();
    if (!$submenu.length) return;

    var willOpen = !$li.hasClass('opened');

    // --- AKORDEON: zamknij rodzeństwo na tym samym poziomie ---
    var $siblings = $li.siblings('li');
    $siblings.each(function () {
      var $s = $(this);
      $s.removeClass('opened')
        .children('.menu-dropdown, .tv-sub-menu, ul, .item-content, .menu-content')
        .stop(true, true).css('display', 'none');
    });

    // --- przełącz bieżący ---
    if (willOpen) {
      $li.addClass('opened');
      $submenu.stop(true, true).css('display', 'block');

      // auto-scroll tylko dla głównych kategorii
      if ($li.hasClass('level-1')) {
        scrollL1IntoView($li);
      }

      // jeżeli to sekcja DIETY – przewiń listę/kafelki do początku
      var $dietRow = $li.find('.diet-icon-container').first();
      if ($dietRow.length) $dietRow.scrollLeft(0);

    } else {
      $li.removeClass('opened');
      $submenu.stop(true, true).css('display', 'none');
    }
  });

  // UWAGA: klik w tytuł prowadzi do kategorii – nie dodajemy handlera na link
})();