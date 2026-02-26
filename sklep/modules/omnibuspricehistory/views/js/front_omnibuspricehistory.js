/**
 * Obsługa front-end dla modułu Historii Cen Omnibus.
 * Wersja zrefaktoryzowana z funkcją init.
 */

// rysuje wykres (line albo bar) we wskazanym kontenerze
function renderChart(historyData, chartType, productName, variantText) {
    const chartContainer = document.getElementById('omnibusHistoryChartContainer');
    if (!chartContainer) {
        console.error('Błąd: Kontener wykresu nie znaleziony.');
        return;
    }
    chartContainer.innerHTML = '<canvas id="omnibusHistoryChartCanvas"></canvas>';
    const ctx = document.getElementById('omnibusHistoryChartCanvas').getContext('2d');

    // 1) Labels i values
    const labels = historyData.map(h => new Date(h.date).toLocaleDateString('pl-PL')); // Używamy 'date' z zagregowanych danych
    const prices = historyData.map(h => parseFloat(h.price));

    // 2) Znajdź indeks najniższej ceny
    const minPrice = Math.min(...prices);
    const minIndex = prices.indexOf(minPrice);

    // 3) Gradient fill pod linią
    const gradient = ctx.createLinearGradient(0, 0, 0, chartContainer.clientHeight);
    gradient.addColorStop(0, (window.omnibusFullHistoryBarColor || '#007bff') + '33'); // półprzezroczysty
    gradient.addColorStop(1, (window.omnibusFullHistoryBarColor || '#007bff') + '05'); // bardziej przezroczysty

    // 4) Kolory punktów
    const pointBg = prices.map((p, i) =>
        i === minIndex
            ? (window.omnibusFullHistoryLowestBarColor || '#dc3545') // Domyślny czerwony dla najniższej
            : (window.omnibusFullHistoryBarColor || '#007bff') // Domyślny niebieski dla pozostałych
    );
    const pointBd = prices.map(() => window.omnibusFullHistoryBarColor || '#007bff'); // Domyślny niebieski dla obwódki

    // 5) Tworzenie wykresu
    new Chart(ctx, {
        type: chartType,
        data: {
            labels: labels,
            datasets: [{
                label: productName,
                data: prices,
                borderColor: window.omnibusFullHistoryBarColor || '#007bff',
                backgroundColor: chartType === 'bar' ? pointBg : gradient,
                fill: chartType === 'line',
                tension: 0.2,
                pointBackgroundColor: pointBg,
                pointBorderColor: pointBd,
                pointRadius: 4,
                pointHoverRadius: 6,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                title: {
                    display: false // Tytuł będzie ustawiany w HTML
                }
            },
            scales: {
                x: {
                    ticks: { color: window.omnibusFullHistoryTextColor || '#333' },
                    grid: { display: true }
                },
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: val => val + ' zł', // Dodanie waluty
                        color: window.omnibusFullHistoryTextColor || '#333'
                    },
                    grid: { display: true }
                }
            }
        }
    });

    // 6) Ustaw tytuł i podtytuł
    document.getElementById('omnibusChartTitle').textContent = productName;
    document.getElementById('omnibusChartSubtitle').textContent = variantText;

    // 7) Wyświetl tekst o najniższej cenie
    const lowestInfoElement = document.getElementById('omnibusLowestInfo');
    if (lowestInfoElement) {
        // Upewniamy się, że minIndex jest prawidłowy i historyData[minIndex] istnieje
        if (historyData[minIndex]) {
            const formattedDate = new Date(historyData[minIndex].date) // Używamy 'date' z zagregowanych danych
                .toLocaleDateString('pl-PL');
            const formattedPrice = minPrice.toFixed(2).replace('.', ','); // Zastąpienie kropki przecinkiem
            const currency = historyData[minIndex].currency_iso || 'zł'; // Pobierz symbol waluty
            lowestInfoElement.innerHTML =
                `Najniższa cena produktu <strong>${formattedPrice} ${currency}</strong> z dnia <strong>${formattedDate}</strong>`;
            lowestInfoElement.style.display = 'block'; // Upewnij się, że jest widoczny
        } else {
            lowestInfoElement.style.display = 'none'; // Ukryj, jeśli brak danych
        }
    }
}

// renderuje prosty tekst, np. "najniższa cena: X z dnia Y"
// Ta funkcja jest teraz mniej potrzebna, ponieważ renderChart obsługuje tekst info
function renderText(historyData) {
    const txtC = document.getElementById('omnibusHistoryTextContainer');
    if (!txtC) {
        console.error('Błąd: Kontener tekstu (omnibusHistoryTextContainer) nie znaleziony.');
        return;
    }

    if (!historyData || historyData.length === 0) {
        txtC.innerHTML = '<p>Brak danych.</p>';
        return;
    }

    // znajdź najniższy punkt
    let minPriceItem = historyData[0];
    historyData.forEach(h => {
        if (parseFloat(h.price) < parseFloat(minPriceItem.price)) {
            minPriceItem = h;
        }
    });

    const formattedPrice = parseFloat(minPriceItem.price).toFixed(2);
    const formattedDate = new Date(minPriceItem.date).toLocaleString('pl-PL'); // Używamy 'date'
    const currency = minPriceItem.currency_iso || 'PLN';

    txtC.innerHTML = `<p>Najniższa cena: <strong>${formattedPrice} ${currency}</strong> z dnia ${formattedDate}.</p>`;
}


function initOmnibus() {
    // Dodano flagę, aby upewnić się, że initOmnibus jest uruchamiane tylko raz
    if (window.omnibusInitialized) {
        console.warn('Omnibus: initOmnibus() zostało już zainicjowane. Pomijam ponowne uruchomienie.');
        return;
    }
    window.omnibusInitialized = true;


    /**
     * Renderuje dane historii cen w tabeli wewnątrz pop-upu.
     * @param {Array} historyData - Tablica obiektów z historią cen.
     */
    function renderOmnibusData(historyData) {
        const tableContainer = document.getElementById('omnibusHistoryTableContainer');
        if (!tableContainer) return;

        if (!historyData || historyData.length === 0) {
            tableContainer.innerHTML = '<p>Brak danych historii cen dla tego produktu.</p>';
            return;
        }

        let tableHtml = '<table class="table table-bordered table-striped" style="width: 100%;">';
        tableHtml += '<thead><tr><th>Data</th><th>Cena</th></tr></thead>';
        tableHtml += '<tbody>';

        historyData.forEach(item => {
            const date = new Date(item.date).toLocaleString('pl-PL'); // Używamy 'date'
            const price = parseFloat(item.price).toFixed(2);
            const currency = item.currency_iso || 'PLN';
            tableHtml += `<tr><td>${date}</td><td>${price} ${currency}</td></tr>`;
        });

        tableHtml += '</tbody></table>';
        tableContainer.innerHTML = tableHtml;
    }

    /**
     * Obsługuje błędy podczas pobierania danych AJAX.
     * @param {string} message - Komunikat błędu.
     */
    function handleFetchError(message) {
        const container = document.getElementById('omnibusHistoryTableContainer'); // Domyślny kontener dla błędów
        if (container) {
            container.innerHTML = `<p style="color: red;">${message}</p>`;
            // Dodatkowo, jeśli inne kontenery są widoczne, ukryj je
            const chartContainer = document.getElementById('omnibusHistoryChartContainer');
            if (chartContainer) chartContainer.style.display = 'none';

            const textContainer = document.getElementById('omnibusHistoryTextContainer');
            if (textContainer) textContainer.style.display = 'none';
            
            const lowestInfoElement = document.getElementById('omnibusLowestInfo');
            if (lowestInfoElement) lowestInfoElement.style.display = 'none';

            container.style.display = 'block'; // Upewnij się, że kontener błędu jest widoczny
        }
        console.error('Omnibus Price History Error:', message);
    }

    /**
     * Pobiera i wyświetla historię cen dla danego produktu.
     * @param {string} idProduct - ID produktu.
     * @param {string} idAttr - ID atrybutu produktu.
     */
    function fetchAndDisplayHistory(idProduct, idAttr) {
        // Ukryj wszystkie kontenery przed ładowaniem
        const tableContainer = document.getElementById('omnibusHistoryTableContainer');
        if (tableContainer) tableContainer.style.display = 'none';

        const chartContainer = document.getElementById('omnibusHistoryChartContainer');
        if (chartContainer) chartContainer.style.display = 'none';

        const textContainer = document.getElementById('omnibusHistoryTextContainer');
        if (textContainer) textContainer.style.display = 'none';

        const lowestInfoElement = document.getElementById('omnibusLowestInfo');
        if (lowestInfoElement) lowestInfoElement.style.display = 'none';

        // Pokaż komunikat ładowania w domyślnym kontenerze (tabeli)
        if (tableContainer) {
            tableContainer.innerHTML = '<p>Ładowanie historii cen...</p>';
            tableContainer.style.display = 'block';
        }


        // Sprawdzamy, czy window.omnibusAjaxUrl jest zdefiniowane i jest stringiem
        if (typeof window.omnibusAjaxUrl !== 'string' || !window.omnibusAjaxUrl) {
            handleFetchError('Błąd krytyczny: Brak zdefiniowanego adresu URL dla AJAX. Upewnij się, że moduł PHP poprawnie przekazuje zmienną omnibusAjaxUrl.');
            return;
        }

        // Dynamicznie dobieramy separator: '?' jeśli URL nie zawiera '?', '&' w przeciwnym razie
        let separator = window.omnibusAjaxUrl.includes('?') ? '&' : '?';

        // Budujemy URL do kontrolera AJAX modułu
        const ajaxUrl = window.omnibusAjaxUrl +
            separator + 'ajaxPriceHistory=1' + // Dodajemy parametr do identyfikacji żądania AJAX w kontrolerze
            '&id_product=' + encodeURIComponent(idProduct) +
            '&id_product_attribute=' + encodeURIComponent(idAttr);

        console.log('Omnibus AJAX URL:', ajaxUrl); // Logowanie dla łatwiejszego debugowania

        fetch(ajaxUrl)
            .then(response => {
                if (!response.ok) {
                    // Jeśli odpowiedź HTTP nie jest OK (np. 404, 500), rzuć błąd
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                const contentType = response.headers.get("content-type");
                if (contentType && contentType.includes("application/json")) {
                    return response.json();
                }
                // Jeśli typ zawartości nie jest JSON, to prawdopodobnie jest to błąd PHP (np. warning)
                // Spróbuj odczytać tekst odpowiedzi, aby zobaczyć warningi
                return response.text().then(text => {
                    console.error('Otrzymano nie-JSON odpowiedź:', text);
                    throw new TypeError("Otrzymano odpowiedź w nieprawidłowym formacie (oczekiwano JSON). Sprawdź logi serwera pod kątem błędów PHP.");
                });
            })
            .then(data => {
                // Sprawdzamy, czy w zwróconych danych jest flaga błędu
                if (data.error) {
                    handleFetchError(data.message || 'Wystąpił błąd serwera podczas pobierania danych.');
                } else {
                    // Pobierz nazwę produktu i atrybuty
                    // Zakładamy, że `data.product_name` i `data.product_attributes` będą dostępne z odpowiedzi AJAX
                    // Musisz upewnić się, że Twój kontroler AJAX w PHP zwraca te dane.
                    const productName = data.product_name || 'Historia cen produktu';
                    const variantText = data.product_attributes || ''; // Np. "Rozmiar: S · Kolor: Biały"

                    // Agregacja danych per dzień, tylko dla cen niepromocyjnych
                    const daily = {};
                    // Zakładamy, że data.history jest posortowana rosnąco po date_add
                    data.history.forEach(h => {
                        const day = new Date(h.date_add).toISOString().slice(0,10); // YYYY-MM-DD
                        // filtrujemy tylko wpisy nie‐promocyjne: h.change_type !== 'promotion'
                        if (h.change_type !== 'promotion') {
                            const price = parseFloat(h.price);
                            // Jeśli dla danego dnia nie ma jeszcze wpisu lub obecna cena jest niższa
                            if (!daily[day] || price < daily[day].price) {
                                daily[day] = { date: h.date_add, price: price, currency_iso: h.currency_iso };
                            }
                        }
                    });
                    const historyPerDay = Object.values(daily);

                    // Logika wyboru trybu wyświetlania
                    // ukryj wszystkie kontenery
                    if (tableContainer) tableContainer.style.display = 'none';
                    if (chartContainer) chartContainer.style.display = 'none';
                    if (textContainer) textContainer.style.display = 'none';
                    if (lowestInfoElement) lowestInfoElement.style.display = 'none';

                    switch (window.omnibusFullHistoryDisplayType) {
                        case 'popup_line_chart_modern':
                        case 'popup_line_chart':
                            if (chartContainer) chartContainer.style.display = 'block';
                            renderChart(historyPerDay, 'line', productName, variantText);
                            break;
                        case 'popup_bar_chart':
                            if (chartContainer) chartContainer.style.display = 'block';
                            renderChart(historyPerDay, 'bar', productName, variantText);
                            break;
                        case 'table_history':
                            if (tableContainer) tableContainer.style.display = 'block';
                            renderOmnibusData(historyPerDay); // Używamy zagregowanych danych do tabeli
                            break;
                        case 'text_info':
                            if (textContainer) textContainer.style.display = 'block';
                            renderText(historyPerDay); // Używamy zagregowanych danych do tekstu
                            break;
                        default:
                            // domyślnie tabela
                            if (tableContainer) tableContainer.style.display = 'block';
                            renderOmnibusData(historyPerDay); // Używamy zagregowanych danych do tabeli
                    }
                }
            })
            .catch(error => {
                // Obsługa błędów sieciowych lub błędów parsowania JSON
                handleFetchError('Nie udało się załadować historii cen. Sprawdź konsolę przeglądarki pod kątem szczegółów.');
                console.error('Fetch error:', error);
            });
    }

    // --- Event Listenery ---

    // Otwieranie pop-upu
    document.querySelectorAll('.omnibus-show-history-popup').forEach(btn => {
        btn.addEventListener('click', function() {
            const modal = document.getElementById('omnibusPriceHistoryPopup');
            if (modal) {
                modal.style.display = 'block';
                const idProduct = this.dataset.idProduct;
                const idAttr = this.dataset.idProductAttribute;
                fetchAndDisplayHistory(idProduct, idAttr);
            }
        });
    });

    // Zamykanie pop-upu (przycisk X i "Zamknij")
    document.querySelectorAll('.omnibus-modal-close').forEach(btn => {
        btn.addEventListener('click', function() {
            const modal = this.closest('.omnibus-modal-overlay');
            if (modal) modal.style.display = 'none';
        });
    });

    // Zamykanie pop-upu przez kliknięcie w tło
    const modalOverlay = document.getElementById('omnibusPriceHistoryPopup');
    if (modalOverlay) {
        modalOverlay.addEventListener('click', function(e) {
            if (e.target === modalOverlay) modalOverlay.style.display = 'none';
            // Stop propagation to prevent the click from closing the modal if it originated from within the content
            // e.stopPropagation(); // Uncomment if needed to prevent clicks inside modal from closing it
        });
    }
}

// Uruchomienie skryptu po załadowaniu DOM
if (document.readyState !== 'loading') {
    initOmnibus();
} else {
    document.addEventListener('DOMContentLoaded', initOmnibus);
}
