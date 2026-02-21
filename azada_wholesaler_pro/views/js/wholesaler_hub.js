(function () {
    document.addEventListener('DOMContentLoaded', function () {
        var cards = document.querySelectorAll('.azada-hub-card');

        cards.forEach(function (card) {
            var hiddenInput = card.querySelector('.azada-hub-enabled-input');
            var switchBtn = card.querySelector('.azada-switch');
            var stateText = card.querySelector('.azada-switch-state-text');

            if (!hiddenInput || !switchBtn) {
                return;
            }

            var isEnabled = hiddenInput.value === '1';

            var renderToggle = function () {
                switchBtn.classList.toggle('is-on', isEnabled);
                switchBtn.classList.toggle('is-off', !isEnabled);
                switchBtn.setAttribute('aria-pressed', isEnabled ? 'true' : 'false');

                hiddenInput.value = isEnabled ? '1' : '0';

                if (stateText) {
                    stateText.textContent = isEnabled ? 'Włączona' : 'Wyłączona';
                }

                if (isEnabled) {
                    card.classList.remove('azada-hub-card--disabled');
                } else {
                    card.classList.add('azada-hub-card--disabled');
                }
            };

            switchBtn.addEventListener('click', function () {
                isEnabled = !isEnabled;
                renderToggle();
            });

            renderToggle();
        });

        var modal = document.getElementById('azadaHubSettingsModal');
        var modalClose = document.getElementById('azadaHubModalClose');
        var modalCancel = document.getElementById('azadaHubModalCancel');
        var modalName = document.getElementById('azadaHubModalName');
        var modalOnlyBio = document.getElementById('azadaHubModalOnlyBio');
        var modalIdInput = document.getElementById('azada_hub_settings_id_wholesaler');

        var syncModeInput = document.getElementById('azada_hub_settings_sync_mode');
        var priceFieldInput = document.getElementById('azada_hub_settings_price_field');
        var notesInput = document.getElementById('azada_hub_settings_notes');

        var useLocalCacheInput = document.getElementById('azada_hub_settings_use_local_cache');
        var useLocalCacheSwitch = document.getElementById('azadaHubUseLocalCacheSwitch');
        var useLocalCacheLabel = document.getElementById('azadaHubUseLocalCacheLabel');

        var cacheTtlInput = document.getElementById('azada_hub_settings_cache_ttl_minutes');
        var priceMultiplierInput = document.getElementById('azada_hub_settings_price_multiplier');
        var priceMarkupInput = document.getElementById('azada_hub_settings_price_markup_percent');
        var stockBufferInput = document.getElementById('azada_hub_settings_stock_buffer');
        var stockMinInput = document.getElementById('azada_hub_settings_stock_min_limit');
        var stockMaxInput = document.getElementById('azada_hub_settings_stock_max_limit');
        var priceMinInput = document.getElementById('azada_hub_settings_price_min_limit');
        var priceMaxInput = document.getElementById('azada_hub_settings_price_max_limit');
        var zeroBelowStockInput = document.getElementById('azada_hub_settings_zero_below_stock');

        var saveBtn = document.getElementById('azadaHubModalSave');
        var clearCacheBtn = document.getElementById('azadaHubClearCacheBtn');
        var forceSyncBtn = document.getElementById('azadaHubForceSyncBtn');

        var clearCacheUrl = modal ? (modal.getAttribute('data-clear-cache-url') || '') : '';
        var forceSyncUrl = modal ? (modal.getAttribute('data-force-sync-url') || '') : '';

        if (!modal) {
            return;
        }

        var hideModal = function () {
            modal.style.display = 'none';
        };

        var showModal = function () {
            modal.style.display = 'flex';
        };

        if (modalClose) {
            modalClose.addEventListener('click', hideModal);
        }

        if (modalCancel) {
            modalCancel.addEventListener('click', hideModal);
        }

        modal.addEventListener('click', function (event) {
            if (event.target === modal) {
                hideModal();
            }
        });

        var tabButtons = document.querySelectorAll('.azada-tab-btn');
        var tabPanes = document.querySelectorAll('.azada-tab-pane');

        var activateTab = function (tabId) {
            tabButtons.forEach(function (btn) {
                btn.classList.toggle('is-active', btn.getAttribute('data-tab') === tabId);
            });

            tabPanes.forEach(function (pane) {
                pane.classList.toggle('is-active', pane.getAttribute('data-tab-pane') === tabId);
            });
        };

        tabButtons.forEach(function (btn) {
            btn.addEventListener('click', function () {
                activateTab(btn.getAttribute('data-tab'));
            });
        });

        var updateLocalCacheSwitch = function (enabled) {
            if (!useLocalCacheInput || !useLocalCacheSwitch) {
                return;
            }

            useLocalCacheInput.value = enabled ? '1' : '0';
            useLocalCacheSwitch.classList.toggle('is-on', enabled);
            useLocalCacheSwitch.classList.toggle('is-off', !enabled);
            useLocalCacheSwitch.setAttribute('aria-pressed', enabled ? 'true' : 'false');

            if (useLocalCacheLabel) {
                useLocalCacheLabel.textContent = enabled ? 'Włączone' : 'Wyłączone';
            }
        };

        if (useLocalCacheSwitch) {
            useLocalCacheSwitch.addEventListener('click', function () {
                var enabled = !useLocalCacheSwitch.classList.contains('is-on');
                updateLocalCacheSwitch(enabled);
            });
        }

        var setInputValue = function (input, value, fallback) {
            if (!input) {
                return;
            }

            if (value === null || value === undefined || value === '') {
                input.value = fallback;
            } else {
                input.value = value;
            }
        };

        var currentWholesalerId = '0';

        var runHubAction = function (url, button, confirmText) {
            if (!url || !currentWholesalerId || currentWholesalerId === '0') {
                return;
            }

            if (confirmText && !window.confirm(confirmText)) {
                return;
            }

            var previousHtml = button ? button.innerHTML : '';
            if (button) {
                button.disabled = true;
                button.innerHTML = '<i class="icon-refresh icon-spin"></i> Trwa...';
            }

            var requestUrl = url + '&id_wholesaler=' + encodeURIComponent(currentWholesalerId);

            fetch(requestUrl, {
                method: 'GET',
                credentials: 'same-origin'
            })
                .then(function (response) {
                    return response.json();
                })
                .then(function (data) {
                    var message = (data && data.msg) ? data.msg : 'Brak odpowiedzi.';
                    window.alert(message);
                })
                .catch(function () {
                    window.alert('Błąd połączenia z serwerem.');
                })
                .finally(function () {
                    if (button) {
                        button.disabled = false;
                        button.innerHTML = previousHtml;
                    }
                });
        };

        if (clearCacheBtn) {
            clearCacheBtn.addEventListener('click', function () {
                runHubAction(clearCacheUrl, clearCacheBtn, 'Czy na pewno wyczyścić cache hurtowni?');
            });
        }

        if (forceSyncBtn) {
            forceSyncBtn.addEventListener('click', function () {
                runHubAction(forceSyncUrl, forceSyncBtn, 'To uruchomi pełny import 1:1. Kontynuować?');
            });
        }

        var settingsButtons = document.querySelectorAll('.azada-hub-settings-btn');
        settingsButtons.forEach(function (button) {
            button.addEventListener('click', function () {
                var name = button.getAttribute('data-name') || '-';
                var table = button.getAttribute('data-table') || '';
                var isBioPlanet = table === 'azada_raw_bioplanet';

                if (modalName) {
                    modalName.textContent = name;
                }

                if (modalIdInput) {
                    modalIdInput.value = button.getAttribute('data-wholesaler') || '0';
                    currentWholesalerId = modalIdInput.value;
                } else {
                    currentWholesalerId = button.getAttribute('data-wholesaler') || '0';
                }

                setInputValue(syncModeInput, button.getAttribute('data-sync-mode'), 'api');
                setInputValue(priceFieldInput, button.getAttribute('data-price-field'), 'CenaPoRabacieNetto');
                setInputValue(notesInput, button.getAttribute('data-notes'), '');

                setInputValue(cacheTtlInput, button.getAttribute('data-cache-ttl-minutes'), '60');
                setInputValue(priceMultiplierInput, button.getAttribute('data-price-multiplier'), '1.0000');
                setInputValue(priceMarkupInput, button.getAttribute('data-price-markup-percent'), '0.00');
                setInputValue(stockBufferInput, button.getAttribute('data-stock-buffer'), '0');
                setInputValue(stockMinInput, button.getAttribute('data-stock-min-limit'), '0');
                setInputValue(stockMaxInput, button.getAttribute('data-stock-max-limit'), '0');
                setInputValue(priceMinInput, button.getAttribute('data-price-min-limit'), '0.00');
                setInputValue(priceMaxInput, button.getAttribute('data-price-max-limit'), '0.00');
                setInputValue(zeroBelowStockInput, button.getAttribute('data-zero-below-stock'), '0');

                var useLocalCache = button.getAttribute('data-use-local-cache') === '1';
                updateLocalCacheSwitch(useLocalCache);

                [
                    syncModeInput,
                    priceFieldInput,
                    notesInput,
                    useLocalCacheSwitch,
                    cacheTtlInput,
                    priceMultiplierInput,
                    priceMarkupInput,
                    stockBufferInput,
                    stockMinInput,
                    stockMaxInput,
                    priceMinInput,
                    priceMaxInput,
                    zeroBelowStockInput
                ].forEach(function (field) {
                    if (field) {
                        field.disabled = !isBioPlanet;
                    }
                });

                if (modalOnlyBio) {
                    modalOnlyBio.style.display = isBioPlanet ? 'none' : 'block';
                }

                if (saveBtn) {
                    saveBtn.disabled = !isBioPlanet;
                }

                if (clearCacheBtn) {
                    clearCacheBtn.disabled = !isBioPlanet;
                }

                if (forceSyncBtn) {
                    forceSyncBtn.disabled = !isBioPlanet;
                }

                activateTab('tab-start');
                showModal();
            });
        });
    });
})();
