/* ========================================================================== */
/* == GUS IMPORTER (Wersja 2 - Kompatybilna z BB_CHECKOUT) == */
/* ========================================================================== */

(function() {
  'use strict';

  var debug = false;
  try {
    debug = !!window.gusImporterDebug;
  } catch (e) {
    debug = false;
  }
  
  // Funkcja pomocnicza do znajdowania pól w kontekście (w formularzu)
  function findField(selectorCandidates, context) {
    context = context || document;
    for (var i = 0; i < selectorCandidates.length; i++) {
      var el = context.querySelector(selectorCandidates[i]);
      if (el) {
        if (el.tagName === 'DIV' && el.querySelector('input')) {
          return el.querySelector('input');
        }
        if (el.tagName === 'INPUT' || el.tagName === 'SELECT') {
          return el;
        }
      }
    }
    return null;
  }
  
  // Funkcja pomocnicza do wywoływania eventu
  function triggerChange(input) {
    if (!input) return;
    var evt = new Event('change', { bubbles: true });
    input.dispatchEvent(evt);
    var evtInput = new Event('input', { bubbles: true });
    input.dispatchEvent(evtInput);
  }

  // Główna logika pobierania danych z GUS
  function onNipBlur(event) {
    var nipInput = event.target;
    var raw = nipInput.value || '';
    var nip = raw.replace(/\D+/g, '');

    if (nip.length !== 10) {
      if (debug && window.console && console.warn) {
        console.warn('GUS Importer: NIP ma nieprawidłową długość:', nip);
      }
      return;
    }
    
    // Znajdź formularz, w którym jest to pole NIP
    var form = nipInput.closest('form');
    if (!form) {
       if (debug && window.console && console.warn) {
        console.warn('GUS Importer: nie znaleziono nadrzędnego formularza dla pola NIP.');
      }
      return;
    }

    var companyField = findField([
      'input[name*="company"]'
    ], form);

    // Nie nadpisuj, jeśli firma jest już wpisana
    if (companyField && companyField.value && companyField.value.trim() !== '') {
      if (debug && window.console && console.log) {
        console.log('GUS Importer: pole Firma nie jest puste, nie nadpisuję.');
      }
      return;
    }
    
    // Pokaż loader (jeśli mamy jak)
    nipInput.classList.add('gus-loading');

    var body = JSON.stringify({ nip: nip });

    if (debug && window.console && console.log) {
      console.log('GUS Importer: wysyłam zapytanie dla NIP:', nip, 'do', window.gusImporterApiUrl);
    }

    fetch(window.gusImporterApiUrl, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json'
      },
      body: body,
      credentials: 'same-origin'
    })
    .then(function (response) {
      nipInput.classList.remove('gus-loading');
      return response.json();
    })
    .then(function (data) {
      if (debug && window.console && console.log) {
        console.log('GUS Importer: odpowiedź:', data);
      }
      
      if (!data || !data.success) {
        if (debug && window.console && console.warn) {
          console.warn('GUS Importer: błąd API:', data && data.error, data && data.debug);
        }
        return;
      }

      // Znajdź pola W KONTEKŚCIE TEGO FORMULARZA
      var companyField = findField(['input[name*="company"]'], form);
      var postcodeField = findField(['input[name*="postcode"]'], form);
      var cityField = findField(['input[name*="city"]'], form);
      
      // === POPRAWKA DLA NASZEGO CHECKOUTU ===
      var streetField = findField(['.address-field-part[id^="street_name"]'], form);
      var houseNumberField = findField(['.address-field-part[id^="house_number"]'], form);
      // === KONIEC POPRAWKI ===

      var street = data.street || '';
      var propertyNumber = data.propertyNumber || '';
      var apartmentNumber = data.apartmentNumber || '';
      var zip = data.zip || '';
      var city = data.city || '';

      var house = propertyNumber;
      if (apartmentNumber) {
        house += '/' + apartmentNumber;
      }

      if (companyField && data.name) {
        companyField.value = data.name;
        triggerChange(companyField);
      }
      if (postcodeField && zip) {
        postcodeField.value = zip;
        triggerChange(postcodeField);
      }
      if (cityField && city) {
        cityField.value = city;
        triggerChange(cityField);
      }
      
      // === POPRAWKA DLA NASZEGO CHECKOUTU ===
      if (streetField && street) {
        streetField.value = street;
        triggerChange(streetField);
      }
      if (houseNumberField && house) {
        houseNumberField.value = house;
        triggerChange(houseNumberField);
      }
      // === KONIEC POPRAWKI ===

      if (debug && window.console && console.log) {
        console.log('GUS Importer: dane zostały uzupełnione.');
      }
      
      // Specjalne wywołanie dla PrestaShop 1.7+ do odświeżenia listy miast (jeśli istnieje)
      if (typeof prestashop !== 'undefined' && prestashop.emit) {
          prestashop.emit('blurAddressInput', {
              target: postcodeField
          });
      }

    })
    .catch(function (error) {
      nipInput.classList.remove('gus-loading');
      if (window.console && console.error) {
        console.error('GUS Importer: błąd fetch:', error);
      }
    });
  }

  // --- NOWA LOGIKA INICJALIZACJI (AJAX-COMPATIBLE) ---
  
  /**
   * Znajdź *wszystkie* pola NIP na stronie i podepnij do nich logikę 'blur'.
   * Upewnij się, że nie podpinasz dwa razy.
   */
  function initGusImporter() {
    if (typeof window.gusImporterApiUrl === 'undefined' || !window.gusImporterApiUrl) {
      return;
    }
    
    var nipInputs = document.querySelectorAll(
      '#field-vat_number input, ' +
      'input#vat_number, ' +
      'input[name*="vat_number"]'
    );
    
    nipInputs.forEach(function(nipInput) {
      if (nipInput.dataset.gusImporterAttached) {
        return; // Już podpięte
      }
      nipInput.dataset.gusImporterAttached = true;
      nipInput.addEventListener('blur', onNipBlur);
      
      if (debug && window.console && console.log) {
        console.log('GUS Importer: podpięto logikę do pola NIP:', nipInput);
      }
    });
  }
  
  // Uruchom przy ładowaniu strony
  document.addEventListener('DOMContentLoaded', initGusImporter);

  // Uruchom ponownie po AJAX PrestaShop (kluczowe!)
  if (typeof prestashop !== 'undefined' && prestashop.on) {
      
      const reInitGus = () => {
          setTimeout(initGusImporter, 250); // Dajemy chwilę na renderowanie
      };

      prestashop.on('updatedAddressForm', reInitGus);
      prestashop.on('updateCheckout', reInitGus);
  }

})();