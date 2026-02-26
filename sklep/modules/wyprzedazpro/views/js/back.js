/**
 * Ścieżka: /modules/wyprzedazpro/views/js/back.js
 * Pełna funkcjonalność: Import Fetch + Obsługa UI
 */
document.addEventListener("DOMContentLoaded", function() {
    
    // --- 1. OBSŁUGA UI (SWITCH, CONDITIONAL FIELDS) ---
    // (Kod zachowany z oryginalnego back_pro.js)
    
    // Logika dla switchy w PrestaShop (z jQuery bo Presta go używa do switchy)
    if (typeof $ !== 'undefined') {
        $(document).on('change', '.prestashop-switch input[type="checkbox"]', function() {
            var $checkbox = $(this);
            var switch_name = $checkbox.attr('name');
            var is_checked = $checkbox.prop('checked');
            var $form = $checkbox.closest('form');

            if (switch_name && $form.length) {
                var $other_checkbox = $form.find('input[name="' + switch_name + '"]').not($checkbox);
                if (is_checked) {
                    $other_checkbox.prop('checked', false);
                } else {
                    $other_checkbox.prop('checked', true);
                }
                $checkbox.closest('.prestashop-switch').find('.radio-label').removeClass('checked');
                if (is_checked) {
                    $checkbox.next('.radio-label').addClass('checked');
                } else {
                    $other_checkbox.next('.radio-label').addClass('checked');
                }
            }
        });

        // Inicjalizacja przy załadowaniu
        $('.prestashop-switch').each(function() {
            var $switch = $(this);
            var $checked = $switch.find('input:checked');
            if ($checked.length) {
                $checked.next('.radio-label').addClass('checked');
            }
        });
    }

    // Warunkowe pokazywanie pól (Czysty JS)
    function toggleConditionalFields() {
        var ignoreBinExpiryOn = document.querySelector('input[name="WYPRZEDAZPRO_IGNORE_BIN_EXPIRY"][value="1"]');
        var groupDiscountBin = document.getElementById('group_discount_bin');
        
        if (groupDiscountBin && ignoreBinExpiryOn) {
            groupDiscountBin.style.display = ignoreBinExpiryOn.checked ? '' : 'none';
        }

        var enableOver90On = document.querySelector('input[name="WYPRZEDAZPRO_ENABLE_OVER90_LONGEXP"][value="1"]');
        var groupOver90 = document.getElementById('group_over90_longexp');
        
        if (groupOver90 && enableOver90On) {
            groupOver90.style.display = enableOver90On.checked ? '' : 'none';
        }
    }

    // Podpięcie eventów dla pól warunkowych
    var condInputs = document.querySelectorAll('input[name="WYPRZEDAZPRO_IGNORE_BIN_EXPIRY"], input[name="WYPRZEDAZPRO_ENABLE_OVER90_LONGEXP"]');
    condInputs.forEach(function(input) {
        input.addEventListener('change', toggleConditionalFields);
    });
    toggleConditionalFields(); // Uruchomienie na starcie


    // --- 2. LOGIKA IMPORTU CSV (DZIAŁAJĄCA) ---
    // Pobieramy elementy po zaktualizowanych ID w configure.tpl
    var btnStart = document.getElementById('wyprzedazpro_start_btn');
    var fileInput = document.getElementById('csv_file_input');
    var progressBar = document.getElementById('wyprzedazpro-progress-bar');
    var progressStage = document.getElementById('wyprzedazpro-progress-stage');
    var progressWrapper = document.getElementById('wyprzedazpro-progress-wrapper');
    var progressStats = document.getElementById('wyprzedazpro-progress-stats');

    // DIAGNOSTYKA
    console.log('Wyprzedaż PRO: back.js załadowany.');
    if(btnStart) console.log('Przycisk start znaleziony.');

    if (btnStart) {
        btnStart.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Walidacja
            if(fileInput.files.length === 0) {
                alert('Proszę wybrać plik CSV!');
                return;
            }

            var file = fileInput.files[0];
            var url = btnStart.getAttribute('data-url');
            
            if(!url) {
                alert('BŁĄD: Brak adresu URL w data-url przycisku.');
                return;
            }

            if(!confirm('Czy na pewno chcesz rozpocząć import pliku: ' + file.name + '?')) {
                return;
            }

            // Start UI
            btnStart.disabled = true;
            progressWrapper.style.display = 'block';
            updateProgress(0, 'Inicjalizacja wysyłania...');

            // Wysłanie pliku (AJAX Start)
            var formData = new FormData();
            formData.append('csv_file', file);
            formData.append('ajax', '1');
            formData.append('action', 'csvImportStart');

            fetch(url, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if(data.ok) {
                    processChunks(url, data.session_id, data.total_rows);
                } else {
                    throw new Error(data.error || 'Nieznany błąd serwera');
                }
            })
            .catch(error => {
                alert('BŁĄD IMPORTU: ' + error.message);
                console.error(error);
                resetUI();
            });
        });
    }

    // Funkcje pomocnicze importu
    function processChunks(url, sessionId, totalRows) {
        updateProgress(5, 'Przetwarzanie danych (Staging)...');
        
        function nextChunk() {
            var fd = new FormData();
            fd.append('ajax', '1');
            fd.append('action', 'csvImportChunk');
            fd.append('session_id', sessionId);

            fetch(url, { method: 'POST', body: fd })
            .then(res => res.json())
            .then(data => {
                if(!data.ok) throw new Error(data.error);

                var percent = Math.round((data.processed / totalRows) * 50);
                updateProgress(percent, 'Analiza rekordu ' + data.processed + ' z ' + totalRows);
                
                if(progressStats) {
                    progressStats.innerHTML = 'Znaleziono w bazie: ' + data.in_db + ' | Nowe EAN: ' + data.not_found;
                }

                if(data.done) {
                    startFinalize(url, sessionId);
                } else {
                    nextChunk();
                }
            })
            .catch(err => {
                alert('Błąd podczas przetwarzania: ' + err.message);
                resetUI();
            });
        }
        nextChunk();
    }

    function startFinalize(url, sessionId) {
        updateProgress(50, 'Przygotowanie aktualizacji produktów...');
        
        var fd = new FormData();
        fd.append('ajax', '1');
        fd.append('action', 'csvImportFinalizeStart');
        fd.append('session_id', sessionId);

        fetch(url, { method: 'POST', body: fd })
        .then(res => res.json())
        .then(data => {
            if(!data.ok) throw new Error(data.error);
            
            var totalTasks = data.finalize_total;
            if(totalTasks == 0) {
                finishImport(url, sessionId);
            } else {
                processFinalizeChunks(url, sessionId, totalTasks);
            }
        })
        .catch(err => {
            alert('Błąd startu finalizacji: ' + err.message);
            resetUI();
        });
    }

    function processFinalizeChunks(url, sessionId, totalTasks) {
        function nextFinChunk() {
            var fd = new FormData();
            fd.append('ajax', '1');
            fd.append('action', 'csvImportFinalizeChunk');
            fd.append('session_id', sessionId);

            fetch(url, { method: 'POST', body: fd })
            .then(res => res.json())
            .then(data => {
                if(!data.ok) throw new Error(data.error);

                var done = data.finalize_done;
                var percent = 50 + Math.round((done / totalTasks) * 50);
                updateProgress(percent, 'Aktualizacja produktu ' + done + ' z ' + totalTasks);

                if(data.done) {
                    finishImport(url, sessionId);
                } else {
                    nextFinChunk();
                }
            })
            .catch(err => {
                alert('Błąd w trakcie aktualizacji: ' + err.message);
                resetUI();
            });
        }
        nextFinChunk();
    }

    function finishImport(url, sessionId) {
        updateProgress(99, 'Czyszczenie danych tymczasowych...');
        
        var fd = new FormData();
        fd.append('ajax', '1');
        fd.append('action', 'csvImportFinalizeFinish');
        fd.append('session_id', sessionId);

        fetch(url, { method: 'POST', body: fd })
        .then(res => res.json())
        .then(data => {
            updateProgress(100, 'Zakończono!');
            alert('SUKCES! Import zakończony.\n\nZaktualizowano: ' + (data.created_or_updated || 0));
            window.location.reload();
        })
        .catch(err => {
            alert('Błąd końcowy: ' + err.message);
            resetUI();
        });
    }

    function updateProgress(percent, text) {
        if(progressBar) {
            progressBar.style.width = percent + '%';
            progressBar.innerText = percent + '%';
        }
        if(progressStage) {
            progressStage.innerText = text;
        }
    }

    function resetUI() {
        if(btnStart) btnStart.disabled = false;
    }
});