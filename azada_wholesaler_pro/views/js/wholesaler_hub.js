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

        var settingsButtons = document.querySelectorAll('.azada-hub-settings-btn');
        settingsButtons.forEach(function (button) {
            button.addEventListener('click', function () {
                if (window.alert) {
                    window.alert('Sekcja ustawień dla tej hurtowni będzie dodana w kolejnym etapie.');
                }
            });
        });
    });
})();
