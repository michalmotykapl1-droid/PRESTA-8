/**
 * /modules/dietamamyto/views/js/dmto_step3_progress.js
 */
(function() {
  function $(sel, ctx){return (ctx||document).querySelector(sel);}
  function setProgress(pct){
    var bar = $('#dmto-step3-bar');
    if(!bar) return;
    bar.style.width = pct+'%';
    bar.textContent = pct+'%';
  }
  function setStatus(txt){
    var el = $('#dmto-step3-status');
    if(el) el.textContent = txt || '';
  }
  function disableForm(disabled){
    var form = $('#dmto-step3-form');
    if(!form) return;
    Array.prototype.forEach.call(form.querySelectorAll('input,select,button'), function(el){
      el.disabled = !!disabled;
    });
  }

  function startBatch(){
    var box = $('#dmto-step3-progress');
    if(box){ box.style.display = 'block'; }
    var detailsEl = $('#dmto-step3-details');
    var errorsEl = $('#dmto-step3-errors');
    if (detailsEl) { detailsEl.innerHTML = ''; }
    if (errorsEl) { errorsEl.innerHTML = ''; }

    setProgress(0); setStatus('Inicjowanie...'); disableForm(true);

    var url = DMTO_STEP3.ajax + '&ajax=1&action=Step3Start&token=' + encodeURIComponent(DMTO_STEP3.token);

    fetch(url, {credentials:'same-origin'})
      .then(r => r.json())
      .then(json => {
        if(!json.ok){ throw new Error(json.error||'Start failed'); }
        setStatus('Przygotowano ' + json.total + ' produktów do przetworzenia w ' + json.roots + ' drzewach.');
        loop(json.job, 0, 0, 0, [], {}, []); // job, totalProcessed, totalCreated, totalLinked, createdNames, failedProducts, touchedCategoryIds
      }).catch(err => {
        setStatus('Błąd: '+err.message);
        disableForm(false);
      });
  }

  function loop(job, totalProcessed, totalCreated, totalLinked, createdNames, failedProducts, touchedCategoryIds){
    var url = DMTO_STEP3.ajax + '&ajax=1&action=Step3Chunk&job=' + encodeURIComponent(job) + '&token=' + encodeURIComponent(DMTO_STEP3.token);

    fetch(url, {credentials:'same-origin'})
      .then(r => r.json())
      .then(json => {
        if(!json.ok){ throw new Error(json.error||'Chunk failed'); }
        
        if (json.batch_stats) {
            totalProcessed += parseInt(json.batch_stats.processed, 10) || 0;
            totalCreated += parseInt(json.batch_stats.created_categories, 10) || 0;
            totalLinked += parseInt(json.batch_stats.linked_products, 10) || 0;
            if (json.batch_stats.created_category_names && json.batch_stats.created_category_names.length > 0) {
                createdNames = Array.from(new Set(createdNames.concat(json.batch_stats.created_category_names)));
            }
            if (json.batch_stats.failed_products) {
                Object.assign(failedProducts, json.batch_stats.failed_products);
            }
            if (json.batch_stats.touched_category_ids && json.batch_stats.touched_category_ids.length > 0) {
                touchedCategoryIds = Array.from(new Set(touchedCategoryIds.concat(json.batch_stats.touched_category_ids)));
            }
        }

        setProgress(json.percent||0);
        setStatus('Przetworzono '+(json.done||0)+' / '+(json.total||0) + '. Utworzono kat.: ' + totalCreated + ', Powiązano prod.: ' + totalLinked);
        
        if(json.finished){
          var failedCount = Object.keys(failedProducts).length;
          var finalMessage = 'Zakończono. Przetworzono ' + totalProcessed + ' produktów. Utworzono ' + totalCreated + ' nowych kategorii i powiązano ' + totalLinked + ' produktów.';
          if (failedCount > 0) {
              finalMessage += ' Wystąpiło ' + failedCount + ' problemów.';
          }
          setStatus(finalMessage);

          var errorsEl = $('#dmto-step3-errors');
          if (errorsEl && failedCount > 0) {
              var errorHtml = '<strong>Szczegóły problemów:</strong><ul>';
              for (var productId in failedProducts) {
                  var safeReason = failedProducts[productId].toString().replace(/</g, "&lt;").replace(/>/g, "&gt;");
                  errorHtml += '<li>ID ' + productId + ': ' + safeReason + '</li>';
              }
              errorHtml += '</ul>';
              errorsEl.innerHTML = errorHtml;
          }

          if (touchedCategoryIds.length > 0) {
              fetchCategorySummary(touchedCategoryIds);
          }

          disableForm(false);
          var bar = $('#dmto-step3-bar'); if(bar){ bar.classList.remove('active'); }
          setTimeout(() => window.location.reload(), 8000); // Wydłużamy czas na przeczytanie podsumowania
          return;
        }

        setTimeout(() => { loop(job, totalProcessed, totalCreated, totalLinked, createdNames, failedProducts, touchedCategoryIds); }, 300);

      }).catch(err => {
        setStatus('Błąd: '+err.message);
        disableForm(false);
      });
  }

  function fetchCategorySummary(categoryIds) {
      var detailsEl = $('#dmto-step3-details');
      if (detailsEl) detailsEl.innerHTML = 'Pobieranie podsumowania...';

      var url = DMTO_STEP3.ajax + '&ajax=1&action=GetDietCategorySummary&token=' + encodeURIComponent(DMTO_STEP3.token);
      
      fetch(url, {
          method: 'POST',
          headers: {
              'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8'
          },
          body: 'category_ids=' + JSON.stringify(categoryIds)
      })
      .then(r => r.json())
      .then(json => {
          if (json.ok && json.summary) {
              var tableHtml = '<br><strong>Podsumowanie zmodyfikowanych kategorii:</strong><table class="table"><thead><tr><th>Kategoria</th><th>Liczba produktów</th></tr></thead><tbody>';
              json.summary.forEach(function(row) {
                  tableHtml += '<tr><td>' + row.name + '</td><td>' + row.count + '</td></tr>';
              });
              tableHtml += '</tbody></table>';
              if (detailsEl) detailsEl.innerHTML = tableHtml;
          }
      })
      .catch(err => {
          if (detailsEl) detailsEl.innerHTML = '<div class="alert alert-warning">Nie udało się pobrać podsumowania kategorii.</div>';
      });
  }

  document.addEventListener('DOMContentLoaded', function(){
    var btn = document.getElementById('dmto-step3-start-batch');
    if(btn){ btn.addEventListener('click', startBatch); }
  });
})();