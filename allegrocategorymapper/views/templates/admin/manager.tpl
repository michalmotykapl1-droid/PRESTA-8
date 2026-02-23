{* /modules/allegrocategorymapper/views/templates/admin/manager.tpl *}

{if isset($operation_summary)}
<div class="panel">
    <div class="panel-heading">
        <i class="icon-info-circle"></i> Podsumowanie ostatniej operacji
    </div>
    <div class="panel-body">
        <p><strong>Przypisano produktów:</strong> {$operation_summary.assigned_count|intval}</p>
        {if !empty($operation_summary.created)}
            <strong>Utworzono nowe kategorie:</strong>
            <ul>
                {foreach from=$operation_summary.created item=catName}
                    <li>{$catName|escape:'html'}</li>
                {/foreach}
            </ul>
        {/if}
        {if !empty($operation_summary.reused)}
            <strong>Użyto istniejących kategorii:</strong>
            <ul>
                {foreach from=$operation_summary.reused item=catName}
                    <li>{$catName|escape:'html'}</li>
                {/foreach}
            </ul>
        {/if}
    </div>
</div>
{/if}

{* Zmieniony panel *}
<div class="panel">
    <div class="panel-heading">
        <i class="icon-cogs"></i> Zarządzanie i Skanowanie
    </div>
    <div class="panel-body">
        <div class="row">
            <div class="col-lg-6">
                <h4>Lokalna baza kategorii Allegro</h4>
                <p>Aby uniknąć problemów z API, moduł korzysta z lokalnej kopii drzewa kategorii.</p>
                <div id="acm-category-status">
                    {if $categoriesFileInfo && $categoriesFileInfo.exists}
                        <div class="alert alert-success">Plik istnieje. Ostatnia aktualizacja: <strong>{$categoriesFileInfo.date}</strong></div>
                    {else}
                        <div class="alert alert-warning">Brak lokalnej bazy kategorii. Pobierz ją przed rozpoczęciem pracy.</div>
                    {/if}
                </div>
                <button type="button" class="btn btn-primary" id="acm-download-categories"><i class="icon-download"></i> Pobierz/Odśwież kategorie Allegro</button>
                <span id="acm-download-status" style="margin-left: 10px;"></span>
            </div>
            <div class="col-lg-6">
                 <h4>Statystyki sklepu</h4>
                 <p><strong>Produkty przetworzone (ZROBIONE):</strong> <span class="badge" style="background-color: #5cb85c;">{$countDone|intval}</span></p>
                 <p><strong>Produkty do przetworzenia:</strong> <span class="badge" style="background-color: #d9534f;">{$countToDo|intval}</span></p>
            </div>
        </div>
    </div>
</div>


<div class="panel"><h3><i class="icon-search"></i> Skanuj produkty po EAN (AJAX)</h3>
    <div class="form-group"><label>Wybierz kategorie sklepu do skanowania:</label>
        <div class="acm-tree-wrap">
            {include file="{$smarty.const._PS_MODULE_DIR_}allegrocategorymapper/views/templates/admin/partials/category_tree.tpl" categoriesTree=$categoriesTree}
        </div>
        <div class="acm-tree-actions">
             <button type="button" class="btn btn-default btn-xs" id="acm-expand-all"><i class="icon-plus"></i> Rozwiń</button>
             <button type="button" class="btn btn-default btn-xs" id="acm-collapse-all"><i class="icon-minus"></i> Zwiń</button>
        
             <button type="button" class="btn btn-danger btn-xs" id="acm-delete-cats" style="margin-left:8px;"><i class="icon-trash"></i> Usuń zaznaczone kategorie</button>

             <button type="button" class="btn btn-warning btn-xs" id="acm-reset-to-new" style="margin-left:8px;">
               <i class="icon-undo"></i> Cofnij powiązania → NEW (507)
             </button>
             <button type="button" class="btn btn-info btn-xs" id="acm-rescan-selected" style="margin-left:8px;">
               <i class="icon-refresh"></i> Przeskanuj ponownie zaznaczone
             </button>
        </div>
    </div>
    <div class="well" id="acm-scan-controls">
        <div class="checkbox" style="margin-right:12px;"><label><input type="checkbox" id="acm-debug" {if $debug_enabled}checked{/if}> Tryb debug</label></div>
        <button class="btn btn-primary" id="acm-start-scan" data-chunk="{$scan_chunk|intval}"><i class="icon-play"></i> Rozpocznij skan</button>
        <span id="acm-progress-text" style="margin-left:8px;"></span>
        <div class="progress" style="margin-top:10px; display:none;">
            <div class="progress-bar" role="progressbar" style="width:0%"></div>
        </div>
    </div>
</div>

<div class="panel">
    <h3><i class="icon-list-ul"></i> Wyniki skanowania (Batch: {$latestBatchId})</h3>
    {if $latestBatchId && !empty($results_by_product)}
        <form method="post" class="form-horizontal">
            <input type="hidden" name="batch_id" value="{$latestBatchId|intval}">
            
            <table class="table" id="acm-results-table">
                <thead>
                    <tr>
                        <th style="width: 20px;"><input type="checkbox" id="acm-check-all-products" checked></th>
                        <th>Produkt w sklepie (ID)</th>
                        <th>EAN</th>
                        <th>Wybierz kategorie Allegro do utworzenia</th>
                    </tr>
                </thead>
                <tbody>
                    {foreach from=$results_by_product item=product}
                        <tr>
                            <td style="vertical-align: top; padding-top: 15px;"><input type="checkbox" name="product_selection[{$product.id_product|intval}]" class="acm-product-chk" checked></td>
                            <td style="vertical-align: top; padding-top: 15px;">{$product.product_name|escape:'html'} ({$product.id_product|intval})</td>
                            <td style="vertical-align: top; padding-top: 15px;">{$product.ean|escape:'html'}</td>
                            <td>
                                {if !empty($product.options) && $product.options[0].allegro_category_id != 'NO_MATCH'}
                                    {foreach from=$product.options item=option}
                                        <div class="checkbox">
                                            <label>
                                                <input type="checkbox" name="mappings[{$product.id_product|intval}][]" value="{urlencode($option|@json_encode)}">
                                                {$option.allegro_category_path_formatted|escape:'html'}
                                                <small class="text-muted">[{$option.allegro_category_id}]</small>
                                            </label>
                                        </div>
                                    {/foreach}
                                {else}
                                    <span class="text-muted">Nie znaleziono dopasowania (EAN+Nazwa)</span>
                                {/if}
                                <hr style="margin-top: 10px; margin-bottom: 10px;">
                                <div class="form-group form-inline" style="margin-bottom: 0;">
                                    <div class="checkbox" style="display: inline-block; margin-right: 10px; vertical-align: middle;">
                                        <label>
                                            <input type="checkbox" name="manual_selection[{$product.id_product|intval}]" value="1" class="acm-manual-chk">
                                            Użyj ręcznie ID:
                                        </label>
                                    </div>
                                    <input type="text" name="manual_mapping[{$product.id_product|intval}]" class="form-control acm-manual-input" style="width: 150px; display: inline-block;" placeholder="np. 123456">
                                    <div class="acm-category-search" style="position: relative; display: inline-block; margin-left: 8px; width: 380px;">
                                        <input type="text" class="form-control acm-cat-search-input" placeholder="Szukaj kategorii Allegro (np. makarony)" autocomplete="off">
                                        <div class="acm-cat-search-results panel" style="display:none; position:absolute; z-index:1000; max-height:260px; overflow:auto; width:100%;"></div>
                                        <small class="text-muted">Wybierz z listy</small>
                                    </div>

                                    <span class="acm-manual-path-preview" style="margin-left: 10px; color: #777;"></span>
                                </div>
                            </td>
                        </tr>
                    {/foreach}
                </tbody>
            </table>

            <div class="panel-footer">
                <div class="form-group" style="margin-right:12px; display: inline-block;">
                    <label>ID rodzica (PS):</label>
                    <input type="number" name="root_category_id" value="{$root_category_id|intval}" class="form-control" style="width: 100px; display: inline-block;">
                </div>
                <div class="checkbox" style="margin-right:12px; display: inline-block;">
                    <label><input type="checkbox" name="build_full_path" value="1" {if $build_full_path}checked{/if}> Twórz pełną ścieżkę kategorii</label>
                </div>
                <div class="checkbox" style="margin-right:12px; display: inline-block;">
                    <label><input type="checkbox" name="change_default_category" value="1" checked> <strong>Zmień kategorię domyślną</strong></label>
                </div>
                <div class="checkbox" style="margin-right:12px; display: inline-block;">
                    <label><input type="checkbox" name="mark_done" value="1" checked> Po przypisaniu oznacz jako <strong>ZROBIONE</strong></label>
                </div>
                <button type="submit" name="submitApplyMapping" class="btn btn-success pull-right">
                    <i class="icon-ok"></i> Zastosuj **zaznaczone** mapowania
                </button>
            </div>
        </form>

        <script>
            document.addEventListener('DOMContentLoaded', function() {
                // Nowy kod do pobierania kategorii
                const downloadBtn = document.getElementById('acm-download-categories');
                const downloadStatus = document.getElementById('acm-download-status');
                const categoryStatusDiv = document.getElementById('acm-category-status');

                if (downloadBtn) {
                    downloadBtn.addEventListener('click', function() {
                        downloadBtn.disabled = true;
                        downloadStatus.innerHTML = '<i class="icon-spinner icon-spin"></i> Trwa pobieranie... To może zająć kilka minut.';
                        
                        const formData = new URLSearchParams();
                        formData.append('ajax', 1);
                        formData.append('action', 'downloadCategories');

                        fetch(acmAjaxUrl, {
                            method: 'POST',
                            body: formData
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.ok) {
                                downloadStatus.textContent = 'Sukces! ' + data.message;
                                categoryStatusDiv.innerHTML = '<div class="alert alert-success">Plik istnieje. Ostatnia aktualizacja: <strong>' + data.date + '</strong></div>';
                            } else {
                                downloadStatus.textContent = 'Błąd: ' + data.error;
                            }
                        })
                        .catch(error => {
                            downloadStatus.textContent = 'Błąd sieciowy.';
                        })
                        .finally(() => {
                             downloadBtn.disabled = false;
                        });
                    });
                }


                const checkAll = document.getElementById('acm-check-all-products');
                const checkboxes = document.querySelectorAll('.acm-product-chk');
                if (checkAll && checkboxes.length) {
                    checkAll.addEventListener('change', function() {
                        checkboxes.forEach(function(chk) {
                            chk.checked = checkAll.checked;
                        });
                    });
                }

                document.querySelectorAll('.acm-manual-input').forEach(function(input) {
                    input.addEventListener('blur', function() {
                        const categoryId = this.value.trim();
                        const previewSpan = this.parentElement.querySelector('.acm-manual-path-preview');
                        if (!categoryId) {
                            previewSpan.textContent = '';
                            return;
                        }

                        previewSpan.textContent = 'Szukam w pliku...';
                        const formData = new URLSearchParams();
                        formData.append('ajax', 1);
                        formData.append('action', 'fetchCategoryPath');
                        formData.append('categoryId', categoryId);
                        fetch(acmAjaxUrl, {
                            method: 'POST',
                            body: formData
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.ok) {
                                previewSpan.textContent = data.path;
                                previewSpan.style.color = '#3c763d';
                            } else {
                                previewSpan.textContent = 'Błąd: ' + data.error;
                                previewSpan.style.color = '#a94442';
                            }
                        })
                        .catch(error => {
                            previewSpan.textContent = 'Błąd sieciowy.';
                            previewSpan.style.color = '#a94442';
                        });
                    });
                });
            
                // === Autocomplete wyszukiwarki kategorii z lokalnego JSON ===
                function acmDebounce(fn, wait){ let t; return function(){ clearTimeout(t); const a=arguments,c=this; t=setTimeout(function(){ fn.apply(c,a); }, wait); }; }
                function acmRenderList(box, rows){
                    if (!rows || !rows.length){ box.style.display='none'; box.innerHTML=''; return; }
                    let html = '<ul class="list-group" style="margin:0;">';
                    rows.forEach(function(r){
                        html += '<li class="list-group-item acm-cat-res" data-id="'+ String(r.id).replace(/"/g,'&quot;') +'">'+ (r.label ? r.label : '') + (r.id ? ' ['+r.id+']' : '') +'</li>';
                    });
                    html += '</ul>';
                    box.innerHTML = html;
                    box.style.display = 'block';
                }
                document.querySelectorAll('.acm-category-search').forEach(function(wrapper){
                    const input = wrapper.querySelector('.acm-cat-search-input');
                    const results = wrapper.querySelector('.acm-cat-search-results');
                    const row = wrapper.closest('tr');
                    const manualInput = row ? row.querySelector('.acm-manual-input') : null;
                    if (!input || !results || !manualInput) return;
                    const run = acmDebounce(function(){
                        const q = input.value.trim();
                        if (q.length < 2){ results.style.display='none'; results.innerHTML=''; return; }
                        const fd = new URLSearchParams();
                        fd.append('ajax', '1');
                        fd.append('action', 'searchAllegroCategory');
                        fd.append('q', q);
                        fd.append('limit', '20');
                        fetch(acmAjaxUrl, { method:'POST', body: fd })
                          .then(r => r.json())
                          .then(data => acmRenderList(results, (data && data.data) ? data.data : []))
                          .catch(() => { results.style.display='none'; results.innerHTML=''; });
                    }, 220);
                    input.addEventListener('input', run);
                    results.addEventListener('click', function(e){
                        const el = e.target.closest('.acm-cat-res');
                        if (!el) return;
                        const id = String(el.getAttribute('data-id') || '');
                        if (id) {
                            manualInput.value = id;
                            manualInput.dispatchEvent(new Event('blur')); // wyświetl ścieżkę obok
                        }
                        results.style.display='none'; results.innerHTML='';
                    });
                    document.addEventListener('click', function(e){
                        if (!wrapper.contains(e.target)){ results.style.display='none'; results.innerHTML=''; }
                    });
                });
});
        </script>

    {else}
        <p style="padding: 15px;">Brak wyników do wyświetlenia. Najpierw wykonaj skanowanie.</p>
    {/if}
</div>

<div id="acm-delete-modal" class="modal fade" tabindex="-1" role="dialog">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title">Usuń kategorie</h4>
      </div>
      <div class="modal-body">
        <p><strong>Co chcesz zrobić z produktami przypisanymi do tych kategorii?</strong></p>
        <div class="radio">
          <label><input type="radio" name="acm-del-mode" value="move_hide" checked> Jeśli nie mają innych kategorii chcę je przypisać do kategorii nadrzędnej i ukryć ich widoczność. (rekomendowany)</label>
        </div>
        <div class="radio">
          <label><input type="radio" name="acm-del-mode" value="move"> Jeśli nie mają innych kategorii chcę je przypisać do kategorii nadrzędnej.</label>
        </div>
        <div class="radio">
          <label><input type="radio" name="acm-del-mode" value="delete_products"> Jeśli nie mają innych kategorii je również chcę usunąć.</label>
        </div>
        <p class="help-block">Zwróć uwagę, że jeśli mają inną kategorię, Twoje produkty będą logicznie z nią powiązane.</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Anuluj</button>
        <button type="button" class="btn btn-danger" id="acm-delete-confirm">Usuń</button>
      </div>
    </div>
  </div>
</div>


<script>
function acmGetSelectedCategoryIds(){
  var ids = [];
  $('.acm-tree-wrap input[type="checkbox"]:checked').each(function(){
    var v = $(this).val();
    if (v !== undefined && /^\d+$/.test(String(v))) {
      ids.push(parseInt(v, 10));
    }
  });
  return ids;
}
</script>


<script type="text/javascript">
  (function($){
    $(document).on('click','#acm-delete-cats', function(){
      var ids = acmGetSelectedCategoryIds();
      if (!ids.length){ alert('Zaznacz co najmniej jedną kategorię.'); return; }
      $('#acm-delete-modal').modal('show');
    });
    $(document).on('click','#acm-delete-confirm', function(){
      var ids = acmGetSelectedCategoryIds();
      if (!ids.length){ return; }
      var mode = $('input[name="acm-del-mode"]:checked').val();
      $.ajax({ldelim}
        url: (typeof acmAjaxUrl !== 'undefined' ? acmAjaxUrl : window.location.href),
        method: 'POST',
        dataType: 'json',
        data: {ldelim} ajax:1, action:'deleteCategories', ids: JSON.stringify(ids), mode: mode {rdelim}
      {rdelim}).done(function(resp){
        if (resp && resp.ok){ location.reload(); }
        else { alert((resp && resp.error) ? resp.error : 'Operacja zakończona błędem.'); }
      }).fail(function(){ alert('Błąd połączenia podczas usuwania kategorii.'); })
        .always(function(){ $('#acm-delete-modal').modal('hide'); });
    });
  })(jQuery);
</script>



{literal}
<script>
(function($){
  "use strict";
  function getSelectedCategories(){
    var ids=[], names=[];
    $('.acm-tree-wrap input[type="checkbox"]:checked').each(function(){
      var v=$(this).val();
      if(v && /^\d+$/.test(String(v))){
        ids.push(String(v));
        var $li=$(this).closest('li');
        var label=$.trim($li.find('.category-name:first').text()) || $.trim($li.clone().children().remove().end().text());
        names.push({id:String(v), name:label || ('#'+v)});
      }
    });
    return {ids:ids, names:names};
  }
  function renderList(items){
    if(!items || !items.length) return '—';
    return items.map(function(it){
      var n=it.name || ('#'+it.id);
      return '• '+n+' ['+it.id+']'+(it.cnt!=null?(': '+it.cnt):'');
    }).join('\n');
  }
  function fetchImpact(ids){
    return fetch(acmAjaxUrl,{
      method:'POST',
      headers:{'Content-Type':'application/x-www-form-urlencoded; charset=UTF-8'},
      body: new URLSearchParams({ajax:1, action:'countSelectedImpact', category_ids: JSON.stringify(ids)})
    }).then(function(r){return r.json();}).catch(function(){return {ok:false};});
  }
  function postAction(action, ids){
    return fetch(acmAjaxUrl,{
      method:'POST',
      headers:{'Content-Type':'application/x-www-form-urlencoded; charset=UTF-8'},
      body: new URLSearchParams({ajax:1, action:action, category_ids: JSON.stringify(ids)})
    }).then(function(r){return r.json();});
  }
  function ok(msg){ if(window.showSuccessMessage){ showSuccessMessage(msg);} else { alert(msg);} }
  function err(msg){ if(window.showErrorMessage){ showErrorMessage(msg);} else { alert('Błąd: '+msg);} }

  $('#acm-reset-to-new').on('click', function(e){
    e.preventDefault();
    var sel=getSelectedCategories();
    if(!sel.ids.length){ return err('Zaznacz przynajmniej jedną kategorię.'); }
    fetchImpact(sel.ids).then(function(res){
      var items=(res&&res.items)?res.items:sel.names;
      var total=(res&&res.total!=null)?res.total:'nieznana';
      var msg='Cofnąć powiązania i przenieść produkty do NEW (507)?\n\nKategorie:\n'+renderList(items)+'\n\nŁącznie produktów do zmiany: '+total+'.';
      if(confirm(msg)){
        postAction('resetLinksToNew', sel.ids).then(function(r){
          if(r&&r.ok){ ok(r.message||'Zrobione.'); location.reload(); }
          else { err((r&&r.error)||'Nie udało się wykonać operacji.'); }
        }).catch(function(){ err('Błąd połączenia.'); });
      }
    });
  });

  $('#acm-rescan-selected').on('click', function(e){
    e.preventDefault();
    var sel=getSelectedCategories();
    if(!sel.ids.length){ return err('Zaznacz przynajmniej jedną kategorię.'); }
    fetchImpact(sel.ids).then(function(res){
      var items=(res&&res.items)?res.items:sel.names;
      var total=(res&&res.total!=null)?res.total:'nieznana';
      var msg='Przeskanować ponownie produkty w zaznaczonych kategoriach?\n\nKategorie:\n'+renderList(items)+'\n\nSzacowana liczba produktów do skanowania: '+total+'.';
      if(confirm(msg)){
        postAction('rescanSelected', sel.ids).then(function(r){
          if(r&&r.ok){
            ok(r.message||'Zadanie zainicjowane.');
            /* AUTO-START skanowania po potwierdzeniu */
            setTimeout(function(){
              var btn=document.getElementById('acm-start-scan');
              if(btn){ btn.click(); }
            }, 250);
          } else {
            err((r&&r.error)||'Nie udało się zainicjować.');
          }
        }).catch(function(){ err('Błąd połączenia.'); });
      }
    });
  });
})(jQuery);
</script>
{/literal}
