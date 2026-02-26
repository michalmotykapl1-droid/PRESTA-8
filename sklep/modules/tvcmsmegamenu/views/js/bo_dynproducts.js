
/**
 * ThemeVolty MegaMenu - dynamic products (PrestaShop 8.x)
 * Back-office UI helper
 *
 * - Adds a configuration panel for 'Dynamic products' item type
 * - Serializes the panel state into the standard "link" input as JSON
 * - Renders live preview (layout only) without hitting the storefront
 *
 * This file is kept dependency-free (no jQuery).
 */
(function () {
  'use strict';

  var SELECT_TYPE = 'select[name="type_link"], #type_link';
  var INPUT_LINK  = 'input[name^="link_"], #link, input[name="link"]';
  var PANEL_ID    = 'tv-dynproducts-box';

  function $q(sel, ctx){ return (ctx||document).querySelector(sel); }
  function $$q(sel, ctx){ return Array.prototype.slice.call((ctx||document).querySelectorAll(sel)); }
  function onReady(fn){ if(document.readyState === 'loading'){ document.addEventListener('DOMContentLoaded', fn); } else { fn(); } }

  function ensureOption(select, value, label){
    if(!select) return;
    if(!select.querySelector('option[value="'+value+'"]')){
      var o = document.createElement('option');
      o.value = value; o.textContent = label; select.appendChild(o);
    }
  }

  function findTypeSelect(){ return $q(SELECT_TYPE); }
  function findLinkInput(){ return $q(INPUT_LINK); }

  function buildPanel(){
    var box = document.createElement('div');
    box.id = PANEL_ID;
    box.className = 'panel';
    box.innerHTML = [
      '<div class="panel-heading" style="font-weight:600;">Dynamiczne produkty – konfiguracja</div>',
      '<div class="panel-body">',
        '<div class="form-group">',
          '<label class="control-label">Źródło</label>',
          '<div>',
            '<label style="margin-right:10px;"><input type="radio" name="tvdp_source" value="category" checked> Kategoria</label>',
            '<label style="margin-right:10px;"><input type="radio" name="tvdp_source" value="tag"> Tag</label>',
            '<label style="margin-right:10px;"><input type="radio" name="tvdp_source" value="new"> Nowości</label>',
            '<label style="margin-right:10px;"><input type="radio" name="tvdp_source" value="best"> Bestsellery</label>',
            '<label style="margin-right:10px;"><input type="radio" name="tvdp_source" value="special"> Promocje</label>',
          '</div>',
        '</div>',
        '<div class="form-group tvdp-idline">',
          '<label class="control-label">ID kategorii / tagu</label>',
          '<input type="number" min="0" class="form-control" id="tvdp_refid" placeholder="np. 12">',
          '<p class="help-block">Wymagane dla źródła Kategoria / Tag.</p>',
        '</div>',
        '<div class="form-group">',
          '<label class="control-label">Limit produktów</label>',
          '<input type="number" min="1" class="form-control" id="tvdp_limit" value="8">',
        '</div>',
        '<div class="form-group">',
          '<label class="control-label">Sortowanie</label>',
          '<select id="tvdp_sort" class="form-control">',
            '<option value="position">Pozycja w kategorii</option>',
            '<option value="date_add_desc">Data dodania ↓</option>',
            '<option value="price_asc">Cena ↑</option>',
            '<option value="price_desc">Cena ↓</option>',
            '<option value="name_asc">Nazwa A→Z</option>',
            '<option value="name_desc">Nazwa Z→A</option>',
          '</select>',
        '</div>',
        '<div class="form-group">',
          '<label class="control-label">Układ</label>',
          '<select id="tvdp_layout" class="form-control">',
            '<option value="grid">Siatka</option>',
            '<option value="list">Lista</option>',
          '</select>',
        '</div>',
        '<div class="form-group">',
          '<label class="control-label"><input type="checkbox" id="tvdp_show_price" checked> Pokazuj cenę</label>',
        '</div>',
        '<div class="form-group">',
          '<label class="control-label"><input type="checkbox" id="tvdp_show_badge" checked> Znacznik promocji</label>',
        '</div>',
        '<hr>',
        '<div class="form-group">',
          '<label class="control-label">Podgląd</label>',
          '<div id="tvdp-preview-wrap" class="well" style="min-height:60px;padding:10px"></div>',
          '<p class="help-block" style="margin:6px 0 0;">Podgląd pokazuje tylko układ; rzeczywiste produkty pojawią się na froncie.</p>',
        '</div>',
      '</div>'
    ].join('');
    return box;
  }

  function toggleIdLine(box, show){
    var line = $q('.tvdp-idline', box);
    if(line) line.style.display = show ? '' : 'none';
  }

  function readState(box){
    function val(id){ var n=$q(id, box); return n ? n.value : ''; }
    function chk(id){ var n=$q(id, box); return !!(n && n.checked); }
    var srcEl = $q('input[name="tvdp_source"]:checked', box);
    var s = {
      type: 5,
      source: srcEl ? srcEl.value : 'category',
      refid: null,
      limit: Math.max(1, parseInt(val('#tvdp_limit') || '8', 10) || 8),
      sort: val('#tvdp_sort') || 'position',
      layout: val('#tvdp_layout') || 'grid',
      show_price: chk('#tvdp_show_price'),
      show_badge: chk('#tvdp_show_badge')
    };
    if(s.source === 'category' || s.source === 'tag'){
      var rid = parseInt(val('#tvdp_refid') || '0', 10);
      s.refid = isFinite(rid) ? rid : null;
    }
    return s;
  }

  function writeStateToInput(state, input){
    try{
      input.value = JSON.stringify(state);
      input.dispatchEvent(new Event('change', {bubbles:true}));
    }catch(e){ /* ignore */ }
  }

  function renderPreview(box, state){
    var wrap = $q('#tvdp-preview-wrap', box);
    if(!wrap) return;

    var items = state.limit;
    var html = '';
    if(state.layout === 'grid'){
      html += '<ul class="ul-column tv-megamenu-slider-wrapper" style="display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:10px;list-style:none;padding:0;margin:0;">';
      for(var i=0;i<items;i++){
        html += '<li style="border:1px solid #e5e5e5;padding:8px;border-radius:4px;">'+
          '<div style="height:60px;background:#f8f8f8;border-radius:4px;margin-bottom:6px"></div>'+
          '<div style="font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">Produkt '+(i+1)+'</div>'+
          (state.show_price?'<div style="color:#2f8a00;font-weight:600">99,99 zł</div>':'')+
          (state.show_badge?'<span class="label label-danger" style="display:inline-block;margin-top:4px">PROMO</span>':'')+
        '</li>';
      }
      html += '</ul>';
    } else {
      html += '<ul style="list-style:none;margin:0;padding:0">';
      for(var j=0;j<items;j++){
        html += '<li style="display:flex;align-items:center;gap:10px;padding:8px 0;border-bottom:1px dashed #e5e5e5">'+
          '<div style="width:60px;height:40px;background:#f3f3f3;border-radius:4px"></div>'+
          '<div style="flex:1 1 auto">Produkt '+(j+1)+'</div>'+
          (state.show_price?'<div style="color:#2f8a00;font-weight:600">99,99 zł</div>':'')+
          (state.show_badge?'<span class="label label-danger" style="margin-left:6px">PROMO</span>':'')+
        '</li>';
      }
      html += '</ul>';
    }
    wrap.innerHTML = html;
  }

  function hydrate(box, json){
    try{
      var cfg = JSON.parse(json || '{}');
      var map = {
        source: function(v){
          var el = $q('input[name="tvdp_source"][value="'+(v||'category')+'"]', box);
          if(el) el.checked = true;
          toggleIdLine(box, (v==='category'||v==='tag'));
        },
        refid: function(v){ var n=$q('#tvdp_refid',box); if(n&&v!=null) n.value=v; },
        limit: function(v){ var n=$q('#tvdp_limit',box); if(n&&v) n.value=v; },
        sort: function(v){ var n=$q('#tvdp_sort',box); if(n&&v) n.value=v; },
        layout: function(v){ var n=$q('#tvdp_layout',box); if(n&&v) n.value=v; },
        show_price: function(v){ var n=$q('#tvdp_show_price',box); if(n) n.checked=!!v; },
        show_badge: function(v){ var n=$q('#tvdp_show_badge',box); if(n) n.checked=!!v; }
      };
      Object.keys(map).forEach(function(k){ if(k in cfg){ map[k](cfg[k]); } });
    }catch(e){ /* ignore */ }
  }

  function init(){
    var typeSel = findTypeSelect();
    var link = findLinkInput();
    if(!typeSel || !link) return;

    // add option "Dynamic products"
    ensureOption(typeSel, '5', 'Dynamic products');

    // ensure single panel instance
    var existing = $q('#'+PANEL_ID);
    if(existing && existing.parentNode) existing.parentNode.removeChild(existing);

    // insert panel after type select row
    var panel = buildPanel();
    var anchor = typeSel.closest('.form-group') || typeSel.parentNode;
    if(anchor && anchor.parentNode){
      anchor.parentNode.insertBefore(panel, anchor.nextSibling);
    } else {
      (typeSel.parentNode || document.body).appendChild(panel);
    }

    // set ID line visibility
    var src = $q('input[name="tvdp_source"]:checked', panel);
    toggleIdLine(panel, !!src && (src.value==='category'||src.value==='tag'));

    // hydrate from existing JSON
    if(link.value && link.value.trim().charAt(0) === '{'){
      hydrate(panel, link.value);
    }

    // initial preview + persist
    var state = readState(panel);
    writeStateToInput(state, link);
    renderPreview(panel, state);

    // events
    panel.addEventListener('change', function(ev){
      if(ev.target && ev.target.name === 'tvdp_source'){
        toggleIdLine(panel, ev.target.value==='category' || ev.target.value==='tag');
      }
      var s = readState(panel);
      writeStateToInput(s, link);
      renderPreview(panel, s);
    });
    panel.addEventListener('keyup', function(){ 
      var s = readState(panel);
      writeStateToInput(s, link);
      renderPreview(panel, s);
    });

    // when the item type changes, show/hide our panel
    typeSel.addEventListener('change', function(){
      var show = (String(typeSel.value) === '5');
      panel.style.display = show ? '' : 'none';
    });
    panel.style.display = (String(typeSel.value) === '5') ? '' : 'none';
  }

  onReady(init);
})();
