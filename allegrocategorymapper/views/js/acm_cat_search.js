(function($){
  // Inject a search box next to each "Użyj ręcznie ID" input and wire it to AJAX.
  function debounce(fn, wait){ let t; return function(){ clearTimeout(t); const a=arguments, c=this; t=setTimeout(function(){ fn.apply(c,a); }, wait); }; }
  function renderList($box, rows){
    if (!rows || !rows.length){ $box.hide().empty(); return; }
    var html = '<ul class="list-group" style="margin:0;">';
    rows.forEach(function(r){
      html += '<li class="list-group-item acm-cat-res" data-id="'+ String(r.id).replace(/"/g,'&quot;') +'">'
           +  $('<div>').text(r.label + (r.id ? ' ['+r.id+']' : '')).html()
           +  '</li>';
    });
    html += '</ul>';
    $box.html(html).show();
  }

  function enhanceRow($row){
    if ($row.data('acm-enhanced')) return;
    var $manual = $row.find('input[name^="manual_allegro_id"]');
    if (!$manual.length) return;
    $row.css('position','relative');
    var $wrap = $('<div class="acm-category-search" style="position:relative; display:inline-block; margin-left:8px; width:420px;"></div>');
    var $input = $('<input type="text" class="form-control acm-cat-search-input" autocomplete="off" placeholder="Szukaj kategorii Allegro (np. makarony)">');
    var $res   = $('<div class="acm-cat-search-results panel" style="display:none; position:absolute; z-index:1000; max-height:260px; overflow:auto; width:100%;"></div>');
    $wrap.append($input).append($res);
    $manual.after($wrap);

    var ajaxUrl = $('body').data('acm-ajax-url') || window.acmAjaxUrl || (window.location.pathname + '?controller=AdminAllegroCategoryAjax&ajax=1');
    var run = debounce(function(){
      var q = $.trim($input.val());
      if (q.length < 2){ $res.hide().empty(); return; }
      $.ajax({ url: ajaxUrl, data: {action:'searchAllegroCategory', q:q, limit:20}, dataType:'json', method:'GET' })
        .done(function(resp){ renderList($res, resp && resp.data ? resp.data : (resp && resp.ok && resp.data ? resp.data : [])); })
        .fail(function(){ renderList($res, []); });
    }, 200);
    $input.on('input', run);
    $res.on('click', '.acm-cat-res', function(){
      var id = $(this).data('id') + '';
      $manual.val(id).trigger('change');
      // try to check the related manual checkbox if present
      $row.find('input[type="checkbox"][name^="use_manual_id"]').prop('checked', true);
      $res.hide().empty();
    });
    $(document).on('click', function(e){ if (!$.contains($wrap[0], e.target)) $res.hide().empty(); });
    $row.data('acm-enhanced', true);
  }

  $(function(){
    // mark ajax url from Smarty if assigned
    try{ if (typeof acmAjaxUrl !== 'undefined'){ $('body').attr('data-acm-ajax-url', acmAjaxUrl); } }catch(e){}
    // enhance existing rows
    $('tr').each(function(){ enhanceRow($(this)); });
    // if rows are loaded via AJAX later, you can call window.acmEnhanceRows()
    window.acmEnhanceRows = function(){ $('tr').each(function(){ enhanceRow($(this)); }); };
  });
})(jQuery);
