/* [BB_CHECKOUT LIVE] file loaded */
console.log('[BB_CHECKOUT][JS] LIVE checkout loaded. Flow control implemented (Dynamic button visibility + Custom validation on submit).');

// ============================================================================
// == BB_CHECKOUT UTILS (Zawsze na górze)
// ============================================================================
(function () {
    'use strict';
    window.BB_UTILS = window.BB_UTILS || {};
    window.BB_UTILS.q = function (s, r) { return (r || document).querySelector(s) };
    window.BB_UTILS.qa = function (s, r) { return Array.prototype.slice.call((r || document).querySelectorAll(s)) };
    window.BB_UTILS.show = function (el) { if (el) { el.style.display = ''; el.classList.remove('is-hidden'); } };
    window.BB_UTILS.hide = function (el) { if (el) { el.style.display = 'none'; el.classList.add('is-hidden'); } };
    // Helper function to find the closest ancestor matching the selector
    window.BB_UTILS.closest = function (el, sel) {
        if (!el) return null;
        if (Element.prototype.closest) {
            return el.closest(sel);
        }
        // Fallback
        while (el && el.nodeType === 1) {
            if (el.matches(sel)) return el;
            el = el.parentElement;
        }
        return null;
    };
    window.BB_UTILS.pickRadio = function (radios) { for (var i = 0; i < radios.length; i++) { if (radios[i].checked) return radios[i]; } return radios[0] || null; };
})();


// ============================================================================
// == GŁÓWNA LOGIKA CHECKOUT (Walidacje, Formularze)
// ============================================================================
document.addEventListener('DOMContentLoaded', function() {
    // Użycie helperów
    const q = window.BB_UTILS.q;
    const qa = window.BB_UTILS.qa;
    const closest = window.BB_UTILS.closest;

    //
    // 0. Czyszczenie stanu błędu przy interakcji (Globalny nasłuchiwacz)
    //
    function initErrorClearing() {
         // Zapobiega wielokrotnej inicjalizacji
        if (window.__bbErrorClearingInitialized) return;
        window.__bbErrorClearingInitialized = true;

        // Funkcja pomocnicza do czyszczenia stanu błędu
        const clearErrorState = function(target) {
            if (!target || !target.classList) return;

            if (target.classList.contains('has-error')) {
                target.classList.remove('has-error');
            }

            // Specjalna obsługa dla checkboxów (czyszczenie kontenera)
            if (target.type === 'checkbox') {
                const checkboxContainer = closest(target, '.custom-checkbox.has-error-highlight');
                if (checkboxContainer) {
                    checkboxContainer.classList.remove('has-error-highlight');
                }
            }
        };

        // Nasłuchujemy zdarzeń 'input' i 'change' w fazie capture (true).
        document.addEventListener('input', function(e) {
            clearErrorState(e.target);
        }, true);

        document.addEventListener('change', function(e) {
            clearErrorState(e.target);
        }, true);
    }
    initErrorClearing();


    //
    // 1. Logika hasła (rejestracja na checkout)
    // (Zostaje)
    const createAccountCheckbox = document.getElementById('create_account_checkbox');
    const passwordContainer = document.getElementById('password-container');
    const passwordInputReg = q('#checkout-guest-form input[name="password"]');

    if (createAccountCheckbox && passwordContainer) {
        const togglePassword = function () {
            if (createAccountCheckbox.checked) {
                passwordContainer.style.display = 'block';
                passwordContainer.style.opacity = 0;
                setTimeout(function () {
                    passwordContainer.style.opacity = 1;
                }, 10);
                if (passwordInputReg) {
                    passwordInputReg.required = true;
                }
            } else {
                passwordContainer.style.display = 'none';
                if (passwordInputReg) {
                    passwordInputReg.required = false;
                }
            }
        };
        togglePassword();
        createAccountCheckbox.addEventListener('change', togglePassword);
    }

    qa('.input-group-btn button').forEach(function (btn) {
        btn.addEventListener('click', function () {
            btn.classList.toggle('is-visible');
        });
    });

    //
    // 2. Logika typu klienta + NIP
    // (Zostaje)
    function initAddressLogic() {
        // Targetujemy formularze adresowe, ale także formularz w kroku 1 (dla gości)
        qa('.js-address-form form, #checkout-personal-information-step form').forEach(function (form) {
            const clientTypeInputs = qa('input[name^="client_type"]', form);
            const businessRows = qa('[data-js-type="business-field"]', form);
            const vatInput = q('input[name="vat_number"]', form);

            if (!businessRows.length) return;

            function validateVat(input) {
                if (!input || !input.parentNode) return true;

                let errorMsg = q('.js-vat-error-msg', input.parentNode);
                if (!errorMsg) {
                    errorMsg = document.createElement('div');
                    errorMsg.className = 'js-vat-error-msg js-phone-error-msg';
                    input.parentNode.appendChild(errorMsg);
                }

                if (!input.required || input.value.length === 0) {
                    input.classList.remove('has-error');
                    errorMsg.style.display = 'none';
                    return true;
                }

                const value = input.value.replace(/\D/g, '').substring(0, 10);
                input.value = value;

                if (value.length !== 10) {
                    input.classList.add('has-error');
                    errorMsg.innerText = 'NIP musi składać się z 10 cyfr.';
                    errorMsg.style.display = 'block';
                    return false;
                } else {
                    input.classList.remove('has-error');
                    errorMsg.style.display = 'none';
                    return true;
                }
            }

            if (vatInput) {
                vatInput.setAttribute('maxlength', 10);
                vatInput.addEventListener('input', function () { validateVat(vatInput); });
                vatInput.addEventListener('blur', function () { validateVat(vatInput); });
            }

            function toggleBusinessFields(type) {
                businessRows.forEach(function (row) {
                    const input = q('input', row);
                    const label = q('.form-control-label', row);

                    if (type === 'business') {
                        row.style.display = 'block';
                        if (input) {
                            input.required = true;
                            if (label) label.classList.add('required');
                        }
                        if (input && input.value.length === 0) {
                             input.classList.remove('has-error');
                             const msgEl = q('.js-vat-error-msg', input.parentNode);
                             if (msgEl) msgEl.style.display = 'none';
                        }
                    } else {
                        row.style.display = 'none';
                        if (input) {
                            input.required = false;
                            input.classList.remove('has-error');
                            const msgEl = q('.js-vat-error-msg', input.parentNode);
                            if (msgEl) msgEl.style.display = 'none';
                        }
                        if (label) label.classList.remove('required');
                    }
                });
            }

            clientTypeInputs.forEach(function (input) {
                input.addEventListener('change', function () {
                    toggleBusinessFields(this.value);
                });
            });

            let checkedInput = window.BB_UTILS.pickRadio(clientTypeInputs);
            if (checkedInput) {
                 toggleBusinessFields(checkedInput.value);
            }
        });
    }
    initAddressLogic();

    //
    // 3. Walidacja telefonu
    // (Zostaje)
    function initPhoneValidation() {
        qa('input[name="phone"]').forEach(function (phoneInput) {
            if (!phoneInput || !phoneInput.parentNode) return;

            let errorMsg = q('.js-phone-error-msg', phoneInput.parentNode);
            if (!errorMsg) {
                errorMsg = document.createElement('div');
                errorMsg.className = 'js-phone-error-msg';
                errorMsg.style.color = '#d0021b';
                errorMsg.style.fontSize = '12px';
                errorMsg.style.marginTop = '5px';
                errorMsg.style.display = 'none';
                phoneInput.parentNode.appendChild(errorMsg);
            }

            const updateErrorState = function () {
                if (phoneInput.value.length > 0 && phoneInput.value.length !== 9) {
                    errorMsg.style.display = 'block';
                    errorMsg.innerText = 'Numer telefonu musi składać się z 9 cyfr.';
                    phoneInput.classList.add('has-error');
                } else {
                    errorMsg.style.display = 'none';
                    phoneInput.classList.remove('has-error');
                }
            };

            phoneInput.addEventListener('input', function () {
                this.value = this.value.replace(/\D/g, '').substring(0, 9);
                updateErrorState();
            });
            phoneInput.addEventListener('blur', updateErrorState);
        });
    }
    
    function initCitySelectFix() {
        qa('select[name="city"]').forEach(function (select) {
            select.setAttribute('size', '1');
            select.removeAttribute('multiple');
             if (document.activeElement === select) {
                select.blur();
            }
        });
    }
    initPhoneValidation();
    initCitySelectFix();

    //
    // 4. Łączenie adresu
    // (Zostaje)
    function initAddressMerge() {
        qa('.address-field-part[id^="street_name"]').forEach(function (streetInput) {
            const wrapper = closest(streetInput, '.address-split-wrapper');
            if (!wrapper) return;

            const houseNumberInput = q('.address-field-part[id^="house_number"]', wrapper);
            const targetInputId = streetInput.getAttribute('data-target-input');
            const targetInput = document.getElementById(targetInputId);

            if (!houseNumberInput || !targetInput) return;

            const merge = function () {
                targetInput.value = (streetInput.value.trim() + ' ' + houseNumberInput.value.trim()).trim();
            };

            if (targetInput.value) {
                const regex = /(\s|^)([\d]+[a-zA-Z]?\/?[a-zA-Z\d]*)$/;
                const match = targetInput.value.match(regex);
                if (match) {
                    houseNumberInput.value = match[2].trim();
                    streetInput.value = targetInput.value.replace(match[0], '').trim();
                } else {
                    streetInput.value = targetInput.value;
                }
            }

            streetInput.addEventListener('input', merge);
            houseNumberInput.addEventListener('input', merge);
        });
    }
    initAddressMerge();

    //
    // 4.1. Niestandardowa obsługa walidacji formularza przy wysyłce
    // (Zostaje)
    function initCustomValidation() {
        const formsToValidate = [];
        
        const selectors = [
            '.js-address-form form', 
            'button[name="confirm-addresses"]',
            'button[name="confirmDeliveryOption"]',
            '#checkout-personal-information-step form',
            '#login-form'
        ];
        
        selectors.forEach(selector => {
            qa(selector).forEach(element => {
                const form = element.tagName === 'FORM' ? element : closest(element, 'form');
                if (form && formsToValidate.indexOf(form) === -1) {
                    formsToValidate.push(form);
                }
            });
        });

        formsToValidate.forEach(form => {
            if (!form) return;
            form.setAttribute('novalidate', 'novalidate');
            if (form.__bbCustomValidationBound) return;
            form.__bbCustomValidationBound = true;

            // --- POCZĄTEK POPRAWKI (Wersja 6) ---
            // Zmieniamy nasłuchiwanie z 'submit' na 'click' na przyciskach,
            // aby wyprzedzić skrypty motywu.
            
            const submitButtons = qa('button[type="submit"]', form);

            submitButtons.forEach(btn => {
                // Nasłuchujemy w fazie "capture" (true), aby uruchomić się PRZED innymi skryptami
                btn.addEventListener('click', function(e) {
                    
                    console.log('[BB_DEBUG] -----------------------------------');
                    console.log('[BB_DEBUG] Przycisk "click" wykryty (faza capture).');

                    // 1. Czyszczenie starych błędów
                    qa('.has-error', form).forEach(el => el.classList.remove('has-error'));
                    qa('.has-error-highlight', form).forEach(el => el.classList.remove('has-error-highlight'));
                    qa('.js-phone-error-msg', form).forEach(el => { if (el.style) el.style.display = 'none'; });
                    qa('.js-vat-error-msg', form).forEach(el => { if (el.style) el.style.display = 'none'; });

                    let hasError = false;

                    // 2. Logika walidacji (skopiowana z 'submit')
                    qa('input[name="phone"]', form).forEach(phoneInput => {
                        if (phoneInput.offsetParent !== null && !phoneInput.required) {
                            phoneInput.required = true;
                            const label = q('label[for="'+ phoneInput.id +'"]');
                            if (label && label.classList && !label.classList.contains('required')) {
                                label.classList.add('required');
                            }
                        }
                    });

                    const standardRequiredFields = qa('input[required], select[required], textarea[required]', form);
                    standardRequiredFields.forEach(field => {
                        if (field.offsetParent === null) return;
                        let isInvalid = false;
                        if (field.type !== 'radio' && field.type !== 'checkbox' && field.value.trim() === '') isInvalid = true;
                        if (field.type === 'checkbox' && field.required && !field.checked) isInvalid = true;
                        
                        if (isInvalid) {
                            hasError = true;
                            field.classList.add('has-error');
                            if (field.type === 'checkbox') {
                                const checkboxContainer = closest(field, '.custom-checkbox');
                                if (checkboxContainer) checkboxContainer.classList.add('has-error-highlight');
                            }
                        }
                    });

                    qa('input[name="phone"]', form).forEach(phoneInput => {
                        if (phoneInput.offsetParent === null) return; 
                        if (phoneInput.value.length > 0 && phoneInput.value.length !== 9) {
                            hasError = true;
                            const msgEl = q('.js-phone-error-msg', phoneInput.parentNode);
                            if (msgEl) {
                                msgEl.style.display = 'block';
                                msgEl.innerText = 'Numer telefonu musi składać się z 9 cyfr.';
                            }
                            phoneInput.classList.add('has-error');
                        }
                    });

                    qa('.address-split-wrapper', form).forEach(wrapper => {
                        if (wrapper.offsetParent === null) return;
                        const visibleStreet = q('.address-field-part[id^="street_name"]', wrapper);
                        const visibleNumber = q('.address-field-part[id^="house_number"]', wrapper);
                        if (closest(form, '.js-address-form')) {
                            if (visibleStreet && visibleStreet.value.trim() === '') {
                                hasError = true;
                                visibleStreet.classList.add('has-error');
                            }
                            if (visibleNumber && visibleNumber.value.trim() === '') {
                                hasError = true;
                                visibleNumber.classList.add('has-error');
                            }
                        }
                    });

                    qa('input[name="vat_number"]', form).forEach(visibleVat => {
                        if (visibleVat.offsetParent === null) return;
                        if (visibleVat.required && visibleVat.value.length > 0) {
                            if (visibleVat.value.length !== 10) {
                                hasError = true;
                                visibleVat.classList.add('has-error');
                                const msgEl = q('.js-vat-error-msg', visibleVat.parentNode);
                                if (msgEl) {
                                    msgEl.style.display = 'block';
                                    msgEl.innerText = 'NIP musi składać się z 10 cyfr.';
                                }
                            }
                        }
                    });
                    
                    if (!hasError && btn.name === 'confirm-addresses') {
                        const invoiceContainer = document.getElementById('invoice-addresses');
                        if (invoiceContainer && invoiceContainer.offsetParent !== null) {
                            const invoiceRadios = qa('input[name="id_address_invoice"]', invoiceContainer);
                            const checkedInvoice = q('input[name="id_address_invoice"]:checked', invoiceContainer);
                            if (invoiceRadios.length > 0 && !checkedInvoice) {
                                hasError = true;
                                alert('Proszę wybrać adres do faktury z listy albo zaznaczyć, że adres do faktury jest taki sam jak dostawy.');
                                invoiceContainer.scrollIntoView({ behavior: 'smooth', block: 'center' });
                            }
                        }
                    }

                    // 3. Logika blokowania
                    console.log('[BB_DEBUG] Walidacja zakończona. Końcowa wartość hasError = ' + hasError);

                    if (hasError) {
                        console.error('[BB_DEBUG] BŁĄD: Znaleziono błąd. Blokowanie formularza (e.preventDefault()) ORAZ zatrzymywanie innych skryptów (e.stopImmediatePropagation()).');
                        e.preventDefault();
                        e.stopImmediatePropagation(); // Kluczowy element: zatrzymuje skrypt motywu przed wyłączeniem przycisku

                        const firstError = q('.has-error, .has-error-highlight', form);
                        if (firstError) {
                            firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        }
                        
                        // Upewniamy się, że przycisk jest na 100% klikalny (gdyby 'stopImmediatePropagation' z jakiegoś powodu zawiodło)
                        btn.disabled = false;
                        btn.classList.remove('disabled');

                    } else {
                        console.log('%c[BB_DEBUG] SUKCES: Brak błędów. Pozwolenie na kontynuację zdarzenia "click".', 'color: green; font-weight: bold;');
                        // Nie robimy nic. Pozwalamy zdarzeniu 'click' biec dalej.
                        // Teraz uruchomi się skrypt motywu, który wyłączy przycisk i wyśle formularz.
                    }
                    console.log('[BB_DEBUG] -----------------------------------');
                
                }, true); // <-- 'true' (faza capture) jest kluczowe!
            });
            // --- KONIEC POPRAWKI (Wersja 6) ---
        });
    }
    initCustomValidation();


    //
    // 5. Kopiowanie danych z adresu dostawy do faktury
    // (POPRAWKA - naprawiono "przeskakiwanie")
    function initCopyAddressLogic() {
        if (window._bbCopyHandler) {
            document.removeEventListener('click', window._bbCopyHandler, true);
        }
        window._bbCopyHandler = function (e) {
            const btn = closest(e.target, '.js-copy-delivery-address');
            if (!btn) return;

            e.preventDefault(); e.stopPropagation(); e.stopImmediatePropagation();

            const storedDataEl = document.getElementById('js-delivery-address-data');
            let data = {};
            if (storedDataEl) {
                data = {
                    firstname: storedDataEl.dataset.firstname || '',
                    lastname: storedDataEl.dataset.lastname || '',
                    company: storedDataEl.dataset.company || '',
                    vat_number: storedDataEl.dataset.vatNumber || '',
                    address1: storedDataEl.dataset.address1 || '',
                    postcode: storedDataEl.dataset.postcode || '',
                    city: storedDataEl.dataset.city || '',
                    phone: storedDataEl.dataset.phone || '',
                    id_country: storedDataEl.dataset.idCountry || ''
                };
            }

            const deliveryForm = q('#delivery-address');
            if (deliveryForm && deliveryForm.offsetParent !== null && deliveryForm.querySelector('input[name="address1"]')) {
                const getVal = (name) => { const inp = q('input[name="' + name + '"]', deliveryForm); return inp ? inp.value : null; };
                const f = getVal('firstname');  if (f !== null) data.firstname  = f;
                const l = getVal('lastname');   if (l !== null) data.lastname   = l;
                const c = getVal('company');    if (c !== null) data.company    = c;
                const v = getVal('vat_number'); if (v !== null) data.vat_number = v;
                const a = getVal('address1');   if (a !== null) data.address1   = a;
                const p = getVal('postcode');   if (p !== null) data.postcode   = p;
                const ci = getVal('city');      if (ci !== null) data.city      = ci;
                const ph = getVal('phone');     if (ph !== null) data.phone     = ph;
                const str = q('input[id^="street_name"]', deliveryForm);
                const num = q('input[id^="house_number"]', deliveryForm);
                if (str && num) data.address1 = (str.value + ' ' + num.value).trim();
            }

            const invoiceForm = document.getElementById('invoice-address');
            if (!invoiceForm) return;

            const setVal = (name, val) => {
                if (typeof val === 'undefined' || val === null) return;
                const inp = q('input[name="' + name + '"]', invoiceForm);
                if (inp) {
                    inp.value = val;
                    inp.dispatchEvent(new Event('input', { bubbles: true }));
                }
            };
            setVal('firstname', data.firstname);
            setVal('lastname',  data.lastname);
            setVal('phone',     data.phone);
            setVal('postcode',  data.postcode);
            setVal('city',      data.city);

            if (data.address1) {
                const invoiceStreetInput = q('input[id^="street_name"]', invoiceForm);
                const invoiceNumberInput = q('input[id^="house_number"]', invoiceForm);
                if (invoiceStreetInput && invoiceNumberInput) {
                    const regex = /(\s|^)([\d]+[a-zA-Z]?\/?[a-zA-Z\d]*)$/;
                    const match = data.address1.match(regex);
                    if (match) {
                        invoiceNumberInput.value = match[2].trim();
                        invoiceStreetInput.value = data.address1.replace(match[0], '').trim();
                    } else {
                        invoiceStreetInput.value = data.address1;
                        invoiceNumberInput.value = '';
                    }
                    invoiceStreetInput.dispatchEvent(new Event('input', { bubbles: true }));
                    invoiceNumberInput.dispatchEvent(new Event('input', { bubbles: true }));
                } else {
                     setVal('address1', data.address1);
                }
            }
            if (data.company || data.vat_number) {
                const businessRadio = q('input[name^="client_type"][value="business"]', invoiceForm);
                if (businessRadio) {
                    businessRadio.checked = true;
                    businessRadio.dispatchEvent(new Event('change', { bubbles: true }));
                }
                setVal('company',    data.company);
                setVal('vat_number', data.vat_number);
            } else {
                const personRadio = q('input[name^="client_type"][value="individual"]', invoiceForm);
                if (personRadio) {
                    personRadio.checked = true;
                    personRadio.dispatchEvent(new Event('change', { bubbles: true }));
                }
            }
            const targetCountry = q('select[name="id_country"]', invoiceForm);
            if (targetCountry && data.id_country) {
                targetCountry.value = data.id_country;
                
                // --- POPRAWKA BŁĘDU "PRZESKAKIWANIA" ---
                // Usunięto: targetCountry.dispatchEvent(new Event('change', { bubbles: true }));
                // Zastępujemy bezpieczniejszym eventem 'input' do czyszczenia błędów.
                targetCountry.dispatchEvent(new Event('input', { bubbles: true }));
                // --- KONIEC POPRAWKI ---
            }
            invoiceForm.scrollIntoView({ behavior: 'smooth', block: 'center' });
        };
        document.addEventListener('click', window._bbCopyHandler, true);
    }
    initCopyAddressLogic();

    //
    // 6. Przenoszenie komunikatu "Faktura zostanie wystawiona na te dane"
    // (Zostaje)
    function findInvoiceInfoBadge() {
        const text = 'Faktura zostanie wystawiona na te dane';
        let badge = q('.js-invoice-info-alert');
        if (badge) return badge;
        const candidates = qa('.alert.alert-success, .alert-success, .alert');
        for (let i = 0; i < candidates.length; i++) {
            const el = candidates[i];
            if (el.textContent && el.textContent.indexOf(text) !== -1) {
                el.classList.add('js-invoice-info-alert');
                return el;
            }
        }
        return null;
    }
    function updateInvoiceInfoLabel() {
        const badge = findInvoiceInfoBadge();
        if (!badge) return;
        let targetCard = null;
        const invoiceContainer = document.getElementById('invoice-addresses');
        if (invoiceContainer && invoiceContainer.offsetHeight > 0) {
            const checkedInvoice = q('input[type="radio"]:checked', invoiceContainer);
            const invoiceRadio = checkedInvoice || q('input[type="radio"]', invoiceContainer);
            if (invoiceRadio) targetCard = closest(invoiceRadio, 'article, .address-item, .js-address-item, .address, .card, .box');
        }
        if (!targetCard) {
            const deliveryContainer = document.getElementById('delivery-addresses');
            if (deliveryContainer) {
                const checkedDelivery = q('input[type="radio"]:checked', deliveryContainer);
                const deliveryRadio = checkedDelivery || q('input[type="radio"]', deliveryContainer);
                if (deliveryRadio) targetCard = closest(deliveryRadio, 'article, .address-item, .js-address-item, .address, .card, .box');
            }
        }
        if (!targetCard) return;
        const footer = q('.address-footer, .address-footer-content, .address-actions, footer', targetCard);
        if (footer && footer.parentNode) {
            if (badge.parentNode !== footer.parentNode || badge.nextSibling !== footer) {
                if (badge.parentNode) badge.parentNode.removeChild(badge);
                footer.parentNode.insertBefore(badge, footer);
            }
        } else if (badge.parentNode !== targetCard) {
            if (badge.parentNode) badge.parentNode.removeChild(badge);
            targetCard.appendChild(badge);
        }
    }
    updateInvoiceInfoLabel();
    document.addEventListener('change', (e) => {
        if (e.target.type === 'radio' && (closest(e.target, '#delivery-addresses') || closest(e.target, '#invoice-addresses'))) {
            updateInvoiceInfoLabel();
        }
    }, true);


    //
    // 7. Re-init po ajaxowym odświeżeniu adresów
    // (Zostaje)
    if (typeof prestashop !== 'undefined' && prestashop.on) {
        const events = ['updatedAddressForm', 'updatedDeliveryForm', 'updateCheckout'];
        events.forEach(eventName => {
            prestashop.on(eventName, () => {
                setTimeout(() => {
                    initAddressLogic();
                    initPhoneValidation();
                    initCitySelectFix();
                    initAddressMerge();
                    initCustomValidation();
                    initCopyAddressLogic();
                    updateInvoiceInfoLabel();
                }, 200);
            });
        });
    }
});


// ============================================================================
// == [BB_CHECKOUT] MODUŁ PRZEŁĄCZNIKA FAKTURY (TAK/NIE)
// == (USUNIĘTY)
// ============================================================================
/* --- CAŁY MODUŁ USUNIĘTY, LOGIKA PRESTY (CHECKBOX + LINK) PRZEJMUJE KONTROLĘ --- */


// ============================================================================
// == [BB_CHECKOUT] DYNAMIC BUTTON VISIBILITY (Kontrola przepływu)
// == (ZMODYFIKOWANY: Nasłuchuje na linki Presty)
// ============================================================================
(function(){
    'use strict';
    const { q, show, hide, closest } = window.BB_UTILS;

    function updateStepButtonVisibility() {
        const stepContainer = q('#checkout-addresses-step');
        if (!stepContainer || !stepContainer.classList.contains('js-current-step')) return;

        const mainSubmitBtn = q('button[name="confirm-addresses"]');
        if (!mainSubmitBtn) return;

        const deliveryFormContainer = q('#delivery-address');
        const invoiceWrapper = q('.js-invoice-wrapper');
        const invoiceFormContainer = q('#invoice-address');
        let formsAreActive = false;

        if (deliveryFormContainer && deliveryFormContainer.offsetParent !== null && deliveryFormContainer.querySelector('input[name="address1"]')) {
            formsAreActive = true;
        }
        if (invoiceWrapper && invoiceWrapper.offsetParent !== null && 
            invoiceFormContainer && invoiceFormContainer.offsetParent !== null && invoiceFormContainer.querySelector('input[name="address1"]')) {
            formsAreActive = true;
        }

        if (formsAreActive) {
            hide(mainSubmitBtn);
        } else {
            show(mainSubmitBtn);
        }
    }

    document.addEventListener('DOMContentLoaded', updateStepButtonVisibility);
    if(window.prestashop && prestashop.on){
        prestashop.on('updatedAddressForm', () => {
            setTimeout(updateStepButtonVisibility, 150);
        });
         
        // --- POPRAWKA: Nasłuchujemy teraz kliknięć na linki Presty, a nie przełącznika TAK/NIE ---
        document.addEventListener('click', (e) => {
            if (closest(e.target, 'a[data-link-action="different-invoice-address"]') || closest(e.target, 'a[data-link-action="same-invoice-address"]')) {
                // Poczekaj na zakończenie AJAX
                setTimeout(updateStepButtonVisibility, 250);
            }
        });
        // --- KONIEC POPRAWKI ---
        
         prestashop.on('updateCheckout', updateStepButtonVisibility);
    }
})();


// ============================================================================
// == [BB_CHECKOUT] MODUŁ RELOKACJI I CZYSZCZENIA STRUKTURY FORMULARZA
// == (Zmodyfikowano)
// ============================================================================
(function(){'use strict';

  const { q, closest } = window.BB_UTILS;

  function findMainAddressForm() {
    const confirmBtn = q('button[name="confirm-addresses"]');
    if (confirmBtn) {
        const form = closest(confirmBtn, 'form');
        if (form) return form;
    }
    return q('#checkout-addresses-step form') || q('.js-address-form form');
  }

  // --- POPRAWKA: Ta funkcja nie jest już potrzebna (logika TAK/NIE usunięta) ---
  // function relocateInvoice() { ... }
  // --- KONIEC POPRAWKI ---

  function forceFooterToEnd() {
    var parentForm = findMainAddressForm(); 
    if(!parentForm) return;

    const confirmBtn = q('button[name="confirm-addresses"]');
    let footer = null;

    if (confirmBtn) {
        footer = closest(confirmBtn, '.form-footer, footer, .checkout-step-footer');
    }

    if (footer && parentForm.lastElementChild !== footer) {
        parentForm.appendChild(footer);
    }
  }

  function runFixes() {
    // relocateInvoice(); // USUNIĘTE
    forceFooterToEnd();
  }

  document.addEventListener('DOMContentLoaded', runFixes);
  if(window.prestashop && prestashop.on){
      prestashop.on('updatedAddressForm', () => {
          setTimeout(runFixes, 50);
      });
  }
})();


// ============================================================================
// == [BB_POSTCODE] MODUŁ OPTYMALIZACJI KODÓW POCZTOWYCH
// == (Zostaje)
// ============================================================================
(function(){'use strict';
  var cache = new Map(); var inflight = new Map();
  function debounce(fn, delay){ var t; return function(){ var a=arguments, ctx=this; clearTimeout(t); t=setTimeout(function(){ fn.apply(ctx,a); }, delay||600); }; }
  function patch(){
    if(window.handlePostcodeChange && !window.handlePostcodeChange.__bb){
      window.handlePostcodeChange = debounce(window.handlePostcodeChange, 600);
      window.handlePostcodeChange.__bb = true;
    }
    if(window.getCityDataFromApi && !window.getCityDataFromApi.__bb){
      var orig = window.getCityDataFromApi;
      window.getCityDataFromApi = function(pc){
        if(!pc) return Promise.resolve(null);
        if(cache.has(pc)) return Promise.resolve(cache.get(pc));
        if(inflight.has(pc)) return inflight.get(pc);
        var p = Promise.resolve().then(() => { return orig(pc); }).then((res) => { cache.set(pc,res); return res; })
          .catch((err) => {
             // --- POPRAWKA LITERÓWKI ---
             try{ if(err && (err.status===429 || err.statusCode===429)){ console.warn('[BB Postcode] 429 Too Many Requests – cached/skip'); return cache.get(pc)||null; } }catch(e){}
             // --- KONIEC POPRAWKI LITERÓWKI ---
            console.warn('[BB Postcode] API error suppressed', err); return null;
          }).finally(() => { inflight.delete(pc); });
        inflight.set(pc,p);
        return p;
      };
      window.getCityDataFromApi.__bb = true;
    }
  }
  document.addEventListener('DOMContentLoaded', patch);
})();




/* ========================================================================== */
/* == KROK 3: SPOSÓB DOSTAWY (Wersja Ostateczna - BEZ UKRYWANIA) == */
/* ========================================================================== */

// --- MODUŁ KART DOSTAWY (Wersja Ostateczna) ---
(function() {
    'use strict';
    
    // Funkcja do aktualizacji klas .is-selected na wrapperze
    function updateDeliveryCardSelection() {
        const { qa, closest } = window.BB_UTILS;
        const wrappers = qa('.delivery-option-card-wrapper');
        
        wrappers.forEach(wrapper => {
            const radio = wrapper.querySelector('input[type="radio"]');
            if (radio && radio.checked) {
                wrapper.classList.add('is-selected');
            } else {
                wrapper.classList.remove('is-selected');
            }
        });
    }

    // Funkcja inicjująca
    function initDeliveryCardLogic() {
        const { qa, closest } = window.BB_UTILS;
        const cards = qa('.delivery-option-card'); // Nadal celujemy w karty
        const wrappers = qa('.delivery-option-card-wrapper');
        
        cards.forEach(card => {
            if (card.dataset.bbCardLogic) return;
            card.dataset.bbCardLogic = true;
            
            card.addEventListener('click', function(e) {
                
                // 1. Natychmiast ukryj WSZYSTKIE bloki 'extra-content'
                const allExtraContents = qa('.carrier-extra-content');
                allExtraContents.forEach(content => {
                    content.style.display = 'none';
                });
                
                // 2. Znajdź i pokaż blok powiązany z TĄ klikniętą kartą
                const parentWrapper = closest(this, '.delivery-option-card-wrapper');
                if (!parentWrapper) return;
                
                const associatedExtraContent = parentWrapper.querySelector('.carrier-extra-content');
                
                // === POPRAWKA: Usunięto sprawdzanie, czy blok jest pusty ===
                // Blok pokaże się teraz zawsze (nawet jeśli jest pusty, jak u Kuriera)
                if (associatedExtraContent) {
                    associatedExtraContent.style.display = 'block';
                }
                // === KONIEC POPRAWKI ===
                
                // 3. Zaktualizuj klasy .is-selected na WRAPPERACH
                wrappers.forEach(w => w.classList.remove('is-selected'));
                parentWrapper.classList.add('is-selected');
            });
        });

        // Upewnij się, że stan jest poprawny przy załadowaniu
        updateDeliveryCardSelection();

        // Upewnij się, że poprawny blok extra jest widoczny przy załadowaniu
        const checkedRadio = qa('input[name^="delivery_option"]:checked');
        if (checkedRadio.length) {
            const wrapper = closest(checkedRadio[0], '.delivery-option-card-wrapper');
            if (wrapper) {
                const extraContent = wrapper.querySelector('.carrier-extra-content');
                // === POPRAWKA: Usunięto sprawdzanie, czy blok jest pusty ===
                if (extraContent) {
                    extraContent.style.display = 'block';
                }
                // === KONIEC POPRAWKI ===
            }
        }
    }

    // Uruchom przy ładowaniu strony
    document.addEventListener('DOMContentLoaded', initDeliveryCardLogic);

    // Uruchom ponownie po AJAX PrestaShop (kluczowe!)
    if (typeof prestashop !== 'undefined' && prestashop.on) {
        
        const reInit = () => {
            setTimeout(initDeliveryCardLogic, 100);
        };

        prestashop.on('updatedDeliveryForm', reInit);
        prestashop.on('updateCheckout', reInit);
    }
})();

// --- MODUŁ POPRAWEK UX DLA INPOST ---
(function() {
    'use strict';

    function initInPostUXFix() {
        const { qa, closest } = window.BB_UTILS;

        // Znajdujemy wszystkie przyciski "Wybierz ten punkt"
        const selectPointButtons = qa('.carrier-extra-content .form-group.mb-0 button.btn-inpost, .carrier-extra-content button.select-point');

        selectPointButtons.forEach(btn => {
            if (btn.dataset.bbInpostFix) return; // Już poprawione
            btn.dataset.bbInpostFix = true;

            btn.addEventListener('click', function() {
                // POPRAWKA UX: Natychmiastowa informacja zwrotna
                btn.disabled = true;
                btn.innerHTML = '<span>✓ Punkt wybrany</span>';
                
                // Opcjonalnie: podświetlamy blok z danymi
                const wrapper = closest(btn, 'div.row');
                if (wrapper) {
                    const infoBlock = qa('div.form-group.mb-0', wrapper).find(el => el.querySelector('a'));
                    if (infoBlock) {
                        infoBlock.style.transition = 'all 0.3s ease';
                        infoBlock.style.borderColor = '#333';
                        infoBlock.style.boxShadow = '0 0 0 1px #333';
                    }
                }
            });
        });
    }

    // Uruchom przy ładowaniu strony
    document.addEventListener('DOMContentLoaded', initInPostUXFix);

    // Uruchom ponownie po AJAX PrestaShop (kluczowe!)
    if (typeof prestashop !== 'undefined' && prestashop.on) {
        const reInitInPost = () => {
            setTimeout(initInPostUXFix, 250); // Dajemy modułowi InPost chwilę na załadowanie
        };
        prestashop.on('updatedDeliveryForm', reInitInPost);
        prestashop.on('updateCheckout', reInitInPost);
    }
})();

// --- MODUŁ DYNAMICZNYCH DAT DOSTAWY (Krok 3 i Krok 4) ---
(function() {
    'use strict';

    /**
     * Dodaje dni robocze do daty, pomijając soboty i niedziele.
     */
    function addBusinessDays(startDate, daysToAdd) {
        if (daysToAdd === 0) {
             let checkDate = new Date(startDate.getTime());
             let dayOfWeek = checkDate.getDay();
             if (dayOfWeek === 0) { // Niedziela
                 checkDate.setDate(checkDate.getDate() + 1);
             } else if (dayOfWeek === 6) { // Sobota
                 checkDate.setDate(checkDate.getDate() + 2);
             }
             return checkDate;
        }

        let currentDate = new Date(startDate.getTime());
        let daysAdded = 0;
        
        while (daysAdded < daysToAdd) {
            currentDate.setDate(currentDate.getDate() + 1);
            let dayOfWeek = currentDate.getDay(); // 0 = Niedziela, 6 = Sobota
            if (dayOfWeek !== 0 && dayOfWeek !== 6) {
                daysAdded++;
            }
        }
        return currentDate;
    }

    /**
     * Formatuje datę do polskiego formatu, np. "18 listopada"
     */
    function formatDeliveryDate(date, options) {
        return new Intl.DateTimeFormat('pl-PL', options).format(date);
    }

    /**
     * Formatuje tekst daty na podstawie "min:max"
     */
    function getFormattedDateText(delayString) {
        if (!delayString || !delayString.match(/^\d+:\d+$/)) {
            return null; // Zły format, zwróć null
        }
        
        const [minDays, maxDays] = delayString.split(':').map(Number);
        const today = new Date();

        const minDate = addBusinessDays(today, minDays);
        const maxDate = addBusinessDays(today, maxDays);

        let newDelayText;
        
        if (minDays === maxDays) {
            // Format: "Dostawa: 18 listopada"
            newDelayText = `Dostawa: ${formatDeliveryDate(minDate, { day: 'numeric', month: 'long' })}`;
        
        } else if (minDate.getMonth() === maxDate.getMonth()) {
            // Format: "Dostawa: 18 – 19 listopada"
            const minDay = minDate.getDate();
            const maxDay = maxDate.getDate();
            const monthName = formatDeliveryDate(minDate, { month: 'long' });
            newDelayText = `Dostawa: ${minDay} – ${maxDay} ${monthName}`;
        
        } else {
            // Format: "Dostawa: 30 lis – 2 gru" (inne miesiące)
            const minStr = formatDeliveryDate(minDate, { day: 'numeric', month: 'short' });
            const maxStr = formatDeliveryDate(maxDate, { day: 'numeric', month: 'short' });
            newDelayText = `Dostawa: ${minStr} – ${maxStr}`;
        }
        
        return newDelayText;
    }

    /**
     * Główna funkcja inicjująca, która znajduje karty ORAZ podsumowanie i podmienia tekst.
     */
    function initDynamicDeliveryDates() {
        const { qa, q } = window.BB_UTILS;

        // --- 1. Logika dla KART DOSTAWY (Krok 3) ---
        const cards = qa('.delivery-option-card[data-delay-string]');
        cards.forEach(card => {
            try {
                const delayString = card.dataset.delayString;
                const newDelayText = getFormattedDateText(delayString); // Użyj nowej funkcji
                
                if (newDelayText) {
                    const delayTextElement = q('.card-delay', card);
                    if (delayTextElement) {
                        const textNode = delayTextElement.querySelector('span');
                        if (textNode) {
                            textNode.innerText = newDelayText;
                        } else {
                            delayTextElement.innerHTML = `<span>${newDelayText}</span>`;
                        }
                    }
                }
            } catch (e) {
                console.error('[BB_CHECKOUT] Błąd parsowania daty dostawy (Krok 3):', e);
            }
        });

        // --- 2. LOGIKA dla PODSUMOWANIA (Krok 4) ---
        const summaryDelayElement = q('.summary-selected-carrier .carrier-delay');
        if (summaryDelayElement) {
            try {
                const delayString = summaryDelayElement.innerText.trim();
                const newDelayText = getFormattedDateText(delayString); // Użyj tej samej funkcji
                
                if (newDelayText) {
                    summaryDelayElement.innerText = newDelayText;
                }
            } catch (e) {
                console.error('[BB_CHECKOUT] Błąd parsowania daty dostawy (Krok 4 Podsumowanie):', e);
            }
        }
    }

    // Uruchom przy ładowaniu strony
    document.addEventListener('DOMContentLoaded', initDynamicDeliveryDates);

    // Uruchom ponownie po AJAX PrestaShop
    if (typeof prestashop !== 'undefined' && prestashop.on) {
        const reInitDates = () => {
            setTimeout(initDynamicDeliveryDates, 100);
        };
        prestashop.on('updatedDeliveryForm', reInitDates);
        prestashop.on('updateCheckout', reInitDates);
    }
})();

/* ========================================================================== */
/* == KROK 4: PŁATNOŚĆ (Wersja Kompletna - Bez Ramki Warunków) == */
/* ========================================================================== */

// --- MODUŁ KART PŁATNOŚCI I WALIDACJI ---
(function() {
    'use strict';
    
    // Funkcja do aktualizacji klas .selected na wrapperze
    function updatePaymentCardSelection() {
        const { qa, closest } = window.BB_UTILS;
        const wrappers = qa('.payment-option-card-wrapper');
        
        wrappers.forEach(wrapper => {
            const radio = wrapper.querySelector('input[type="radio"]');
            if (radio && radio.checked) {
                wrapper.classList.add('is-selected');
            } else {
                wrapper.classList.remove('is-selected');
            }
        });
    }

    // Funkcja do pokazywania/ukrywania dodatkowych informacji (np. listy banków)
    function updatePaymentExtraContent() {
        const { qa, q } = window.BB_UTILS;
        
        // 1. Ukryj wszystkie bloki "extra"
        const allExtraContents = qa('.js-additional-information, .js-payment-option-form');
        allExtraContents.forEach(content => {
            if (!content.classList.contains('ps-hidden')) {
                content.classList.add('ps-hidden');
            }
        });

        // 2. Znajdź wybrany radio button
        const checkedRadio = q('input[name="payment-option"]:checked');
        if (!checkedRadio) return;

        const selectedId = checkedRadio.id;

        // 3. Pokaż bloki "extra" powiązane z wybranym ID
        const associatedInfo = q(`#${selectedId}-additional-information`);
        const associatedForm = q(`#pay-with-${selectedId}-form`);

        if (associatedInfo) {
            associatedInfo.classList.remove('ps-hidden');
        }
        if (associatedForm) {
            associatedForm.classList.remove('ps-hidden');
        }
    }


    // Funkcja inicjująca
    function initPaymentCardLogic() {
        const { qa, closest } = window.BB_UTILS;
        const cards = qa('.payment-option-card'); // Celujemy w karty
        const wrappers = qa('.payment-option-card-wrapper');
        
        cards.forEach(card => {
            if (card.dataset.bbPaymentLogic) return;
            card.dataset.bbPaymentLogic = true;
            
            card.addEventListener('click', function(e) {
                
                // 1. Zaktualizuj klasy .is-selected na WRAPPERACH
                const parentWrapper = closest(this, '.payment-option-card-wrapper');
                if (!parentWrapper) return;
                
                wrappers.forEach(w => w.classList.remove('is-selected'));
                parentWrapper.classList.add('is-selected');
                
                // 2. Wymuś zaznaczenie radio (label już to robi, ale dla pewności)
                const radio = parentWrapper.querySelector('input[type="radio"]');
                if (radio) {
                    radio.checked = true;
                    // Wywołaj ręcznie event 'change', na który PrestaShop może nasłuchiwać
                    radio.dispatchEvent(new Event('change', { bubbles: true }));
                }
                
                // 3. Zaktualizuj widoczność bloków "extra"
                updatePaymentExtraContent();
            });
        });

        // Upewnij się, że stan jest poprawny przy załadowaniu
        updatePaymentCardSelection();
        updatePaymentExtraContent();
    }
    
    
    // --- NOWA LOGIKA WALIDACJI (Bez Ramki) ---
    function initPaymentValidation() {
        const { q, qa, closest } = window.BB_UTILS;
        
        const finalButton = q('#payment-confirmation button[type="submit"]');
        if (!finalButton || finalButton.dataset.bbButtonLogic) return;
        finalButton.dataset.bbButtonLogic = true;

        // Nasłuchujemy na przycisk w fazie 'capture' (true), aby wyprzedzić PrestaShop
        finalButton.addEventListener('click', function(e) {
            const conditionsContainer = q('.conditions-to-approve-clean');
            if (!conditionsContainer) return; // Brak warunków, kontynuuj

            const checkboxes = qa('input[type="checkbox"][required]', conditionsContainer);
            let allChecked = true;
            let firstErrorBox = null;

            checkboxes.forEach(box => {
                const parentCheckbox = closest(box, '.custom-checkbox');
                
                if (!box.checked) {
                    allChecked = false;
                    if (!firstErrorBox) firstErrorBox = parentCheckbox; // Znajdź pierwszy błąd
                    if (parentCheckbox) parentCheckbox.classList.add('has-error-highlight');
                } else {
                    if (parentCheckbox) parentCheckbox.classList.remove('has-error-highlight');
                }
            });

            if (!allChecked) {
                // BŁĄD: Nie zaznaczono
                e.preventDefault(); // Zablokuj kliknięcie
                e.stopImmediatePropagation(); // Zablokuj inne skrypty
                
                // Przesuń widok do pierwszego błędu
                if (firstErrorBox) {
                   firstErrorBox.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            }
        }, true);
    }
    

    // Uruchom przy ładowaniu strony
    document.addEventListener('DOMContentLoaded', () => {
        initPaymentCardLogic();
        initPaymentValidation();
    });

    // Uruchom ponownie po AJAX PrestaShop
    if (typeof prestashop !== 'undefined' && prestashop.on) {
        
        const reInitPayment = () => {
            setTimeout(() => {
                initPaymentCardLogic();
                initPaymentValidation();
            }, 100);
        };

        // Nasłuchujemy na eventy, które mogą odświeżyć ten krok
        prestashop.on('updatedPaymentForm', reInitPayment);
        prestashop.on('updateCheckout', reInitPayment);
    }
})();