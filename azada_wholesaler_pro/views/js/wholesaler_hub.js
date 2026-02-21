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
        var saveBtn = document.getElementById('azadaHubModalSave');

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
                }

                if (syncModeInput) {
                    syncModeInput.value = button.getAttribute('data-sync-mode') || 'api';
                    syncModeInput.disabled = !isBioPlanet;
                }

                if (priceFieldInput) {
                    priceFieldInput.value = button.getAttribute('data-price-field') || 'CenaPoRabacieNetto';
                    priceFieldInput.disabled = !isBioPlanet;
                }

                if (notesInput) {
                    notesInput.value = button.getAttribute('data-notes') || '';
                    notesInput.disabled = !isBioPlanet;
                }

                if (modalOnlyBio) {
                    modalOnlyBio.style.display = isBioPlanet ? 'none' : 'block';
                }

                if (saveBtn) {
                    saveBtn.disabled = !isBioPlanet;
                }

                activateTab('tab-basic');
                showModal();
            });
        });
    });
})();
