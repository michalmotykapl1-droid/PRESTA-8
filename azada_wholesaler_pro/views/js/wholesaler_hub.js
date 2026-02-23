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

        var seoStripStyleInput = document.getElementById('azada_hub_settings_seo_strip_style');
        var seoStripIframeInput = document.getElementById('azada_hub_settings_seo_strip_iframe');
        var seoStripLinksInput = document.getElementById('azada_hub_settings_seo_strip_links');
        var seoShortDescFallbackInput = document.getElementById('azada_hub_settings_seo_short_desc_fallback');
        var seoMetaTitleTemplateInput = document.getElementById('azada_hub_settings_seo_meta_title_template');
        var seoMetaDescTemplateInput = document.getElementById('azada_hub_settings_seo_meta_desc_template');
        var seoDescriptionPrefixInput = document.getElementById('azada_hub_settings_seo_description_prefix');
        var seoDescriptionSuffixInput = document.getElementById('azada_hub_settings_seo_description_suffix');

        var qualityRequireEanInput = document.getElementById('azada_hub_settings_quality_require_ean');
        var qualityRequireNameInput = document.getElementById('azada_hub_settings_quality_require_name');
        var qualityRequirePriceInput = document.getElementById('azada_hub_settings_quality_require_price');
        var qualityRequireStockInput = document.getElementById('azada_hub_settings_quality_require_stock');
        var qualityRejectMissingDataInput = document.getElementById('azada_hub_settings_quality_reject_missing_data');

        var saveBtn = document.getElementById('azadaHubModalSave');
        var clearCacheBtn = document.getElementById('azadaHubClearCacheBtn');
        var forceSyncBtn = document.getElementById('azadaHubForceSyncBtn');
        var disableProductsBtn = document.getElementById('azadaHubDisableProductsBtn');
        var deleteProductsBtn = document.getElementById('azadaHubDeleteProductsBtn');
        var maintenanceHint = document.getElementById('azadaHubMaintenanceHint');

        var clearCacheUrl = modal ? (modal.getAttribute('data-clear-cache-url') || '') : '';
        var forceSyncUrl = modal ? (modal.getAttribute('data-force-sync-url') || '') : '';
        var disableProductsUrl = modal ? (modal.getAttribute('data-disable-products-url') || '') : '';
        var deleteProductsUrl = modal ? (modal.getAttribute('data-delete-products-url') || '') : '';

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

        var setCheckboxValue = function (input, enabled) {
            if (!input) {
                return;
            }

            input.checked = enabled;
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

        if (disableProductsBtn) {
            disableProductsBtn.addEventListener('click', function () {
                runHubAction(disableProductsUrl, disableProductsBtn, 'Wyłączyć wszystkie produkty tej hurtowni w katalogu sklepu?');
            });
        }

        if (deleteProductsBtn) {
            deleteProductsBtn.addEventListener('click', function () {
                runHubAction(deleteProductsUrl, deleteProductsBtn, 'To trwale usunie produkty tej hurtowni z katalogu. Kontynuować?');
            });
        }

        var settingsButtons = document.querySelectorAll('.azada-hub-settings-btn');
        settingsButtons.forEach(function (button) {
            button.addEventListener('click', function () {
                var name = button.getAttribute('data-name') || '-';

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

                setCheckboxValue(seoStripStyleInput, button.getAttribute('data-seo-strip-style') !== '0');
                setCheckboxValue(seoStripIframeInput, button.getAttribute('data-seo-strip-iframe') !== '0');
                setCheckboxValue(seoStripLinksInput, button.getAttribute('data-seo-strip-links') === '1');
                setCheckboxValue(seoShortDescFallbackInput, button.getAttribute('data-seo-short-desc-fallback') !== '0');
                setInputValue(seoMetaTitleTemplateInput, button.getAttribute('data-seo-meta-title-template'), '');
                setInputValue(seoMetaDescTemplateInput, button.getAttribute('data-seo-meta-desc-template'), '');
                setInputValue(seoDescriptionPrefixInput, button.getAttribute('data-seo-description-prefix'), '');
                setInputValue(seoDescriptionSuffixInput, button.getAttribute('data-seo-description-suffix'), '');

                setCheckboxValue(qualityRequireEanInput, button.getAttribute('data-quality-require-ean') !== '0');
                setCheckboxValue(qualityRequireNameInput, button.getAttribute('data-quality-require-name') !== '0');
                setCheckboxValue(qualityRequirePriceInput, button.getAttribute('data-quality-require-price') !== '0');
                setCheckboxValue(qualityRequireStockInput, button.getAttribute('data-quality-require-stock') !== '0');
                setCheckboxValue(qualityRejectMissingDataInput, button.getAttribute('data-quality-reject-missing-data') !== '0');

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
                    zeroBelowStockInput,
                    seoStripStyleInput,
                    seoStripIframeInput,
                    seoStripLinksInput,
                    seoShortDescFallbackInput,
                    seoMetaTitleTemplateInput,
                    seoMetaDescTemplateInput,
                    seoDescriptionPrefixInput,
                    seoDescriptionSuffixInput,
                    qualityRequireEanInput,
                    qualityRequireNameInput,
                    qualityRequirePriceInput,
                    qualityRequireStockInput,
                    qualityRejectMissingDataInput,
                    saveBtn
                ].forEach(function (field) {
                    if (field) {
                        field.disabled = false;
                    }
                });

                var canClearCache = button.getAttribute('data-can-clear-cache') !== '0';
                var canForceSync = button.getAttribute('data-can-force-sync') !== '0';
                var canDisableProducts = button.getAttribute('data-can-disable-products') !== '0';
                var canDeleteProducts = button.getAttribute('data-can-delete-products') !== '0';

                if (clearCacheBtn) {
                    clearCacheBtn.disabled = !canClearCache;
                }
                if (forceSyncBtn) {
                    forceSyncBtn.disabled = !canForceSync;
                }
                if (disableProductsBtn) {
                    disableProductsBtn.disabled = !canDisableProducts;
                }
                if (deleteProductsBtn) {
                    deleteProductsBtn.disabled = !canDeleteProducts;
                }

                if (maintenanceHint) {
                    if (canClearCache && canForceSync && canDisableProducts && canDeleteProducts) {
                        maintenanceHint.textContent = 'Po wykonaniu akcji odśwież stronę, aby zobaczyć najnowsze statusy.';
                    } else {
                        maintenanceHint.textContent = 'Część akcji jest niedostępna dla tej hurtowni (np. brak tabeli RAW lub kolumny produkt_id).';
                    }
                }

                activateTab('tab-start');
                showModal();
            });
        });
    });
})();
