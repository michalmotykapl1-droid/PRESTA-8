// /modules/allegrocategorymapper/views/js/scan.ajax.js

(function() {
    function getSelectedCategoryIds() {
        let ids = [];
        document.querySelectorAll('.acm-chk:checked').forEach(function(checkbox) {
            ids.push(checkbox.value);
        });
        return ids;
    }

    function postRequest(url, data) {
        return fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
            body: new URLSearchParams(data)
        }).then(function(response) { return response.json(); });
    }

    document.addEventListener('DOMContentLoaded', function() {
        const startButton = document.getElementById('acm-start-scan');
        if (!startButton) return;

        const progressWrapper = document.querySelector('#acm-scan-controls .progress');
        const progressBar = progressWrapper.querySelector('.progress-bar');
        const progressText = document.getElementById('acm-progress-text');
        const chunkSize = parseInt(startButton.dataset.chunk || '200', 10);

        startButton.addEventListener('click', function() {
            const categoryIds = getSelectedCategoryIds();
            if (!categoryIds.length) {
                alert('Zaznacz przynajmniej jedną kategorię.');
                return;
            }

            const isDebug = document.getElementById('acm-debug').checked ? 1 : 0;
            
            postRequest(acmAjaxUrl + '&ajax=1&action=prepareScan', { 'category_ids[]': categoryIds, debug: isDebug })
                .then(function(prepareResponse) {
                    if (!prepareResponse.ok) {
                        return alert(prepareResponse.error || 'Błąd prepareScan');
                    }

                    const allProductIds = prepareResponse.ids;
                    const totalProducts = prepareResponse.total;
                    const batchId = prepareResponse.batch_id;
                    let processedCount = 0;
                    let savedCount = 0;
                    let noEanCount = 0;
                    let errorCount = 0;
                    
                    progressWrapper.style.display = 'block';

                    function processChunk() {
                        const chunk = allProductIds.slice(processedCount, processedCount + chunkSize);
                        
                        // --- POPRAWKA ---
                        // W tym bloku dodajemy automatyczne odświeżenie strony
                        if (!chunk.length) {
                            progressBar.style.width = '100%';
                            progressText.textContent = 'Zakończono. Zapisano: ' + savedCount + ', pominięte (brak EAN): ' + noEanCount + ', błędy: ' + errorCount + '. Batch: ' + batchId + '. Zaraz nastąpi odświeżenie...';
                            
                            // Czekamy 1.5 sekundy i odświeżamy stronę, aby pokazać wyniki
                            setTimeout(function() {
                                location.reload();
                            }, 1500);

                            return; // Zakończ pętlę
                        }
                        // --- KONIEC POPRAWKI ---

                        postRequest(acmAjaxUrl + '&ajax=1&action=scanChunk', { 'ids[]': chunk, batch_id: batchId })
                            .then(function(chunkResponse) {
                                if (chunkResponse && chunkResponse.ok) {
                                    savedCount += chunkResponse.saved || 0;
                                    noEanCount += chunkResponse.noean || 0;
                                    errorCount += chunkResponse.errors || 0;
                                }

                                const itemsInChunk = chunkResponse.processed_count || chunkSize;
                                processedCount += itemsInChunk;
                                
                                const percentage = Math.round((100 * processedCount) / totalProducts);
                                progressBar.style.width = percentage + '%';
                                progressText.textContent = percentage + '% (' + processedCount + '/' + totalProducts + ')';
                                
                                setTimeout(processChunk, 30);
                            })
                            .catch(function() {
                                errorCount++;
                                processedCount += chunk.length;
                                setTimeout(processChunk, 50);
                            });
                    }
                    
                    processChunk();
                });
        });
    });
})();