/**
 * ProductPro - Obsługa rozwijania wariantów (Inne rodzaje)
 * Logika odporna na AJAX i niezależna od szablonu.
 */
document.addEventListener('DOMContentLoaded', function() {
    // Używamy delegacji zdarzeń, aby działało też po odświeżeniu AJAX
    document.addEventListener('click', function(e) {
        // Sprawdź czy kliknięto w przycisk otwierania (lub jego element wewnętrzny)
        var toggle = e.target.closest('.js-bb-type-toggle');
        
        if (toggle) {
            e.preventDefault();
            e.stopPropagation(); 
            
            // Znajdź główny kontener tego konkretnego dropdowna
            var dropdown = toggle.closest('.js-bb-type-dropdown');
            if (dropdown) {
                // Sprawdź czy jest już otwarty
                var wasOpen = dropdown.classList.contains('is-open');
                
                // Najpierw zamknij wszystkie inne otwarte dropdowny
                var allOpen = document.querySelectorAll('.js-bb-type-dropdown.is-open');
                for (var i = 0; i < allOpen.length; i++) {
                    allOpen[i].classList.remove('is-open');
                }

                // Jeśli ten konkretny nie był otwarty, to go otwórz
                if (!wasOpen) {
                    dropdown.classList.add('is-open');
                }
            }
            return;
        }

        // Jeśli kliknięto gdziekolwiek indziej (poza dropdownem), zamknij wszystko
        if (!e.target.closest('.js-bb-type-dropdown')) {
            var allOpen = document.querySelectorAll('.js-bb-type-dropdown.is-open');
            for (var i = 0; i < allOpen.length; i++) {
                allOpen[i].classList.remove('is-open');
            }
        }
    });
});