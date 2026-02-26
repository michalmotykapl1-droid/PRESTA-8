/**
 * /modules/dietamamyto/views/js/dmto_step1_progress.js
 */
(function() {
  function $(sel, ctx){return (ctx||document).querySelector(sel);}
  function setProgress(pct){
    var bar = $('#dmto-step1-bar');
    if(!bar) return;
    bar.style.width = pct+'%';
    bar.textContent = pct+'%';
  }
  function setStatus(txt){
    var el = $('#dmto-step1-status');
    if(el) el.textContent = txt || '';
  }
  function disableForm(disabled){
    var form = $('#dmto-step1-form');
    if(!form) return;
    Array.prototype.forEach.call(form.querySelectorAll('input,select,button'), function(el){
      el.disabled = !!disabled;
    });
  }

  function startBatch(){
    var box = $('#dmto-step1-progress');
    if(box){ box.style.display = 'block'; }
    var detailsEl = $('#dmto-step1-details');
    var errorsEl = $('#dmto-step1-errors');
    if (detailsEl) { detailsEl.innerHTML = ''; }
    if (errorsEl) { errorsEl.innerHTML = ''; }
    setProgress(0); setStatus('Inicjowanie...'); disableForm(true);
    var url = DMTO_STEP1.ajax + '&ajax=1&action=Step1Start&token=' + encodeURIComponent(DMTO_STEP1.token);
    fetch(url, {credentials:'same-origin'})
      .then(r => r.json())
      .then(json => {
        if(!json.ok){ throw new Error(json.error||'Start failed'); }
        setStatus('Przygotowano '+json.total+' produktów...');
        loop(json.job, 0, 0, [], {});
      }).catch(err => {
        setStatus('Błąd: '+err.message);
        disableForm(false);
      });
  }

  function loop(job, totalUpdated, totalCreated, createdNames, failedProducts){
    var url = DMTO_STEP1.ajax + '&ajax=1&action=Step1Chunk&job=' + encodeURIComponent(job) + '&token=' + encodeURIComponent(DMTO_STEP1.token);
    fetch(url, {credentials:'same-origin'})
      .then(r => r.json())
      .then(json => {
        if(!json.ok){ throw new Error(json.error||'Chunk failed'); }
        if (json.batch_stats) {
            totalUpdated += parseInt(json.batch_stats.products_updated, 10) || 0;
            totalCreated += parseInt(json.batch_stats.feature_values_created, 10) || 0;
            if (json.batch_stats.created_value_names && json.batch_stats.created_value_names.length > 0) {
                createdNames = Array.from(new Set(createdNames.concat(json.batch_stats.created_value_names)));
            }
            if (json.batch_stats.failed_products) {
                Object.assign(failedProducts, json.batch_stats.failed_products);
            }
        }
        setProgress(json.percent||0);
        setStatus('Przetworzono '+(json.done||0)+' / '+(json.total||0) + ' | Zaktualizowano: ' + totalUpdated);
        
        if(json.finished){
          var failedCount = Object.keys(failedProducts).length;
          var finalMessage = 'Zakończono. Zaktualizowano ' + totalUpdated + ' produktów, utworzono ' + totalCreated + ' nowych wartości cechy.';
          if (failedCount > 0) { finalMessage += ' Wystąpiło ' + failedCount + ' problemów.'; }
          setStatus(finalMessage);
          
          var detailsEl = $('#dmto-step1-details');
          if (detailsEl && createdNames.length > 0) {
              detailsEl.innerHTML = '<strong>Utworzone rodzaje produktów:</strong><br>' + createdNames.sort().join(', ');
          }
          var errorsEl = $('#dmto-step1-errors');
          if (errorsEl && failedCount > 0) {
              var errorHtml = '<strong>Szczegóły problemów:</strong><ul>';
              for (var productId in failedProducts) {
                  var safeReason = failedProducts[productId].toString().replace(/</g, "&lt;").replace(/>/g, "&gt;");
                  errorHtml += '<li>ID ' + productId + ': ' + safeReason + '</li>';
              }
              errorHtml += '</ul>';
              errorsEl.innerHTML = errorHtml;
          }

          if (DMTO_STEP1.cleanupEnabled) {
              runCleanup(finalMessage);
          } else {
              disableForm(false);
              var bar = $('#dmto-step1-bar'); if(bar){ bar.classList.remove('active'); }
          }
          return;
        }
        setTimeout(function(){ loop(job, totalUpdated, totalCreated, createdNames, failedProducts); }, 300);
      }).catch(err => {
        setStatus('Błąd: '+err.message);
        disableForm(false);
      });
  }

  function runCleanup(previousStatus) {
    setStatus(previousStatus + ' Uruchamiam czyszczenie nieużywanych wartości...');
    var cleanupUrl = DMTO_STEP1.ajax + '&ajax=1&action=CleanupUnusedValues&token=' + encodeURIComponent(DMTO_STEP1.token);
    fetch(cleanupUrl, {credentials:'same-origin'})
      .then(r => r.json())
      .then(json => {
        if (!json.ok) { throw new Error(json.error || 'Cleanup failed'); }
        
        // ⭐ POPRAWKA: Odczytujemy obiekt podsumowania `deleted_summary` zamiast `deleted_count`.
        var summary = json.deleted_summary;
        var finalMessage = previousStatus + ' Usunięto ' + (summary.count || 0) + ' nieużywanych wartości.';
        setStatus(finalMessage);
        
        // ⭐ POPRAWKA: Wyświetlamy listę usuniętych nazw.
        if (summary.names && summary.names.length > 0) {
            var detailsEl = $('#dmto-step1-details');
            if (detailsEl) {
                var deletedHtml = '<strong>Usunięte rodzaje produktów:</strong><br>' + summary.names.sort().join(', ');
                // Dodajemy odstęp, jeśli wcześniej były wyświetlane utworzone wartości
                if (detailsEl.innerHTML !== '') {
                    detailsEl.innerHTML += '<br><br>' + deletedHtml;
                } else {
                    detailsEl.innerHTML = deletedHtml;
                }
            }
        }
      })
      .catch(err => {
        var finalMessage = previousStatus + ' Błąd podczas czyszczenia: ' + err.message;
        setStatus(finalMessage);
      })
      .finally(() => {
        disableForm(false);
        var bar = $('#dmto-step1-bar'); if(bar){ bar.classList.remove('active'); }
        // Wydłużono czas, aby dać czas na przeczytanie podsumowania
        setTimeout(() => window.location.reload(), 4000);
      });
  }

  document.addEventListener('DOMContentLoaded', function(){
    var btn = document.getElementById('dmto-step1-start-batch');
    if(btn){ btn.addEventListener('click', startBatch); }
  });
})();