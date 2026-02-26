/**
 * Ścieżka do pliku: /modules/wyprzedaz/views/js/back.js
 */
document.addEventListener('DOMContentLoaded', function() {
    console.log('Moduł Wyprzedaż: Skrypt back.js został poprawnie załadowany.');

    // Obsługa przełącznika typu "switch" w całym dokumencie
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

    // Inicjalizacja stanu przełączników po załadowaniu strony
    $('.prestashop-switch').each(function() {
        var $switch = $(this);
        var $checked = $switch.find('input:checked');
        if ($checked.length) {
            $checked.next('.radio-label').addClass('checked');
        }
    });

    // Warunkowe pokazywanie pól w ustawieniach
    function toggleConditionalFields() {
        var ignoreBinExpiryOn = document.querySelector('input[name="WYPRZEDAZ_IGNORE_BIN_EXPIRY"][value="1"]');
        var groupDiscountBin = document.getElementById('group_discount_bin');
        if (groupDiscountBin && ignoreBinExpiryOn) {
            groupDiscountBin.style.display = ignoreBinExpiryOn.checked ? '' : 'none';
        }

        var enableOver90On = document.querySelector('input[name="WYPRZEDAZ_ENABLE_OVER90_LONGEXP"][value="1"]');
        var groupOver90 = document.getElementById('group_over90_longexp');
        if (groupOver90 && enableOver90On) {
            groupOver90.style.display = enableOver90On.checked ? '' : 'none';
        }
    }

    $(document).on('change', 'input[name="WYPRZEDAZ_IGNORE_BIN_EXPIRY"], input[name="WYPRZEDAZ_ENABLE_OVER90_LONGEXP"]', toggleConditionalFields);
    toggleConditionalFields();

    // ================== NOWY IMPORT CHUNKOWY Z WIDOCZNYM POSTĘPEM ==================
    (function(){
      var $start = document.querySelector('#wyprzedaz_start');
      var $cancel = document.querySelector('#wyprzedaz_cancel');
      var $file = document.querySelector('input#csv_file[name="csv_file"]');
      var $wrap = document.querySelector('#wyprzedaz-progress-wrapper');
      var $bar = document.querySelector('#wyprzedaz-progress');
      var $stage = document.querySelector('#wyprzedaz-progress-stage');
      var $stats = document.querySelector('#wyprzedaz-progress-stats');

      var sessionId = null;
      var cancelFlag = false;

      function showWrap(){ if($wrap) $wrap.style.display='block'; }
      function hideWrap(){ if($wrap) $wrap.style.display='none'; }
      function setStage(t){ if($stage) $stage.innerHTML = '<strong>Etap:</strong> '+t; }
      function setBar(p){ if($bar){ $bar.style.width=p+'%'; $bar.textContent = Math.round(p)+'%'; } }
      function setStats(t){ if($stats) $stats.textContent = t; }
      function btnState(running){
        if($start){ $start.disabled = running; }
        if($cancel){ $cancel.style.display = running ? 'inline-block' : 'none'; $cancel.disabled = !running; }
        if($file){ $file.disabled = running; }
      }

      async function startImport(e){
        e.preventDefault();
        cancelFlag = false;
        if(!$file || !$file.files || !$file.files.length){ alert('Wybierz plik CSV.'); return; }
        btnState(true); showWrap(); setBar(0); setStats(''); setStage('Inicjalizacja...');

        var fd = new FormData();
        fd.append('csv_file', $file.files[0]);
        fd.append('ajax', '1');
        fd.append('action', 'csvImportStart'); 

        let res = await fetch(window.location.href, { method: 'POST', body: fd, credentials:'same-origin' });
        let json = await res.json().catch(()=>null);
        if(!json || !json.ok){ alert('Błąd startu importu: ' + ((json && json.error) || 'Nieznany błąd')); btnState(false); hideWrap(); return; }
        sessionId = json.session_id;
        var total = json.total_rows;
        var chunksTotal = json.chunks_total;

        if (total === 0) {
            setStage('Plik nie zawiera danych do importu.');
            btnState(false);
            return;
        }

        setStage('Wczytywanie danych (Staging)... (1/'+chunksTotal+')');

        // --- Pętla Stagingu ---
        while(!cancelFlag){
          let fd2 = new FormData();
          fd2.append('ajax','1');
          fd2.append('action','csvImportChunk');
          fd2.append('session_id', sessionId);
          let r2 = await fetch(window.location.href, { method:'POST', body: fd2, credentials:'same-origin' });
          let j2 = await r2.json().catch(()=>null);
          if(!j2 || !j2.ok){ alert('Błąd w trakcie wczytywania (chunk): ' + ((j2 && j2.error) || 'Nieznany błąd')); btnState(false); return; }
          var pct = total>0 ? Math.min(100, (j2.processed/total)*100) : 0;
          setBar(pct);
          setStats('Przetworzono: '+j2.processed+'/'+total+'  •  W sklepie: '+j2.in_db+'  •  Brak: '+j2.not_found+'  •  Chunk: '+j2.chunks_done+'/'+j2.chunks_total);
          setStage('Wczytywanie danych (Staging)... ('+Math.max(1,j2.chunks_done)+'/'+j2.chunks_total+')');
          if(j2.done){ break; }
          await new Promise(r=>setTimeout(r, 150));
        }
        if(cancelFlag){ setStage('Przerwano.'); btnState(false); return; }
        
        await runFinalize(sessionId);
      }

      // --- Proces Finalizacji ---
      async function runFinalize(sessionId){
        if (cancelFlag) return;
        setStage('Finalizacja: przygotowanie...');
        setBar(0);
        let s = new FormData();
        s.append('ajax','1'); s.append('action','csvImportFinalizeStart'); s.append('session_id', sessionId);
        let rs = await fetch(window.location.href, {method:'POST', body:s, credentials:'same-origin'});
        let js = await rs.json().catch(()=>null);
        if(!js || !js.ok){ alert('Błąd finalizacji (start): ' + ((js&&js.error)||'Nieznany błąd')); btnState(false); return; }
        let total = js.finalize_total;
        let done = 0;
        setStats('Zadań do wykonania: '+total+' • Brak EAN w bazie: '+(js.not_found||0));

        if (total === 0) {
            setStage('Finalizacja: brak zadań do wykonania.');
        } else {
            // Pętla finalizacji
            while(!cancelFlag){
              setStage('Finalizacja: przetwarzanie produktów...');
              let c = new FormData();
              c.append('ajax','1'); c.append('action','csvImportFinalizeChunk'); c.append('session_id', sessionId);
              let rc = await fetch(window.location.href, {method:'POST', body:c, credentials:'same-origin'});
              let jc = await rc.json().catch(()=>null);
              if(!jc || !jc.ok){ alert('Błąd finalizacji (chunk): ' + ((jc&&jc.error)||'Nieznany błąd')); btnState(false); return; }
              done = jc.finalize_done||done;
              let pct = total>0 ? Math.min(100, (done/total)*100) : 100;
              setBar(pct);
              setStats('Zakończono: '+done+'/'+total+' • Utworzono/zaktualizowano: '+(jc.created_or_updated||0)+' • Brak EAN w bazie: '+(jc.not_found||0));
              if(jc.done) break;
              await new Promise(r=>setTimeout(r, 150));
            }
        }
        if(cancelFlag){ setStage('Przerwano.'); btnState(false); return; }

        setStage('Finalizacja: generowanie podsumowania...');
        let f = new FormData();
        f.append('ajax','1'); f.append('action','csvImportFinalizeFinish'); f.append('session_id', sessionId);
        let rf = await fetch(window.location.href, {method:'POST', body:f, credentials:'same-origin'});
        let jf = await rf.json().catch(()=>null);
        if(!jf || !jf.ok){ alert('Błąd finalizacji (finish): ' + ((jf&&jf.error)||'Nieznany błąd')); btnState(false); return; }
        
        setBar(100);
        setStage('Gotowe!');
        btnState(false);

        var summaryMessage = "✅ Import zakończony pomyślnie!\n\n";
        summaryMessage += "=========================\n";
        summaryMessage += "Utworzono lub zaktualizowano: " + (jf.created_or_updated || 0) + " produktów\n";
        summaryMessage += "Liczba produktów zduplikowanych: " + (jf.duplicates_count || 0) + "\n";
        summaryMessage += "Nie znaleziono EAN w bazie dla: " + (jf.not_found || 0) + " pozycji\n";
        summaryMessage += "---------------------------------\n";
        summaryMessage += "Aktualna liczba produktów z krótką datą: " + (jf.short_date_total || 0) + "\n";
        
        alert(summaryMessage);
        window.location.reload();
      }

      function cancelImport(e){ e.preventDefault(); cancelFlag = true; setStage('Przerywanie...'); }

      if($start) $start.addEventListener('click', startImport);
      if($cancel) $cancel.addEventListener('click', cancelImport);
})();
});