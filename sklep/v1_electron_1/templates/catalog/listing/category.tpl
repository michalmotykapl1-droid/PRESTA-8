{**
 * 2007-2025 PrestaShop
 * Category listing template - PRODUCT PAGE STYLE HEADERS + UNIFIED BOX
 *}
{strip}
{extends file='catalog/listing/product-list.tpl'}

{block name='product_list_header'}

{* ====================================================================
   STYLES (HARDCODED)
   ==================================================================== *}
{literal}
<style>
  /* 1. UKRYWANIE ELEMENTÓW */
  nav.breadcrumb, .breadcrumb { display: none !important; }
  #wrapper { padding-top: 20px !important; }

  /* 2. GÓRNY BLOK (Tytuł + Wyszukiwarka) */
  .tv-category-unified-box {
      background: #ffffff !important;
      padding: 40px 35px 40px 35px !important; 
      margin-bottom: 0 !important; 
      border-radius: 5px 5px 0 0 !important; 
      border: 1px solid #eaeaea !important;
      border-bottom: none !important; 
      position: relative;
      z-index: 2;
  }

  /* 3. NAZWA KATEGORII - STYL JAK W KARCIE PRODUKTU (OPINIE/OPIS) */
  .tv-category-header-clean {
      margin-bottom: 30px;
      text-align: left;
  }

  .tv-category-header-clean h1 {
      display: inline-block !important;
      font-size: 24px !important; /* Rozmiar jak w nagłówkach sekcji */
      font-weight: 700 !important;
      color: #1a1a1a !important;
      margin: 0 0 10px 0 !important;
      padding-bottom: 15px !important; /* Miejsce na kreskę */
      text-transform: uppercase !important; /* WIELKIE LITERY */
      line-height: 1.2 !important;
      position: relative !important;
      font-family: inherit !important; /* Dziedziczenie fontu ze sklepu */
  }

  /* POMARAŃCZOWA KRESKA POD TYTUŁEM */
  .tv-category-header-clean h1::after {
      content: "";
      position: absolute;
      left: 0;
      bottom: 0;
      width: 50px; /* Długość kreski */
      height: 3px; /* Grubość kreski */
      background-color: #ea7404; /* Twój pomarańcz */
  }
  
  /* Opis pod nazwą */
  .tv-category-desc-clean {
      font-size: 14px !important;
      color: #666 !important;
      line-height: 1.6 !important;
      margin-bottom: 25px;
      max-width: 900px;
      margin-top: 15px;
  }

  /* 4. DOLNY BLOK (Sortowanie) */
  #js-product-list-top {
      background: #ffffff !important;
      margin-top: 0 !important;
      padding: 25px 35px 30px 35px !important;
      border: 1px solid #eaeaea !important;
      border-top: 1px solid #f5f5f5 !important; 
      border-radius: 0 0 5px 5px !important; 
      box-shadow: 0 2px 5px rgba(0,0,0,0.03) !important;
  }

  /* 5. WYSZUKIWARKA */
  #bb-cat-search {
      margin: 0 !important;
      position: relative !important;
      width: 100% !important;
  }
  
  .bb-cat-search__wrapper {
      position: relative !important;
      width: 100% !important;
      display: flex;
      align-items: center;
  }

  /* INPUT */
  #bb-cat-search-input.bb-cat-search__input {
      width: 100% !important;
      height: 52px !important;
      line-height: 52px !important;
      padding: 0 50px 0 15px !important;
      
      background-color: #ffffff !important;
      border: 1px solid #e1e1e1 !important;
      border-radius: 4px !important;
      
      font-size: 15px !important;
      color: #333 !important;
      outline: none !important;
      transition: border-color 0.2s ease !important;
      box-shadow: none !important;
  }

  #bb-cat-search-input.bb-cat-search__input:focus {
      border-color: #ea7404 !important;
  }
  
  #bb-cat-search-input.bb-cat-search__input::placeholder {
      color: #999;
      font-weight: 400;
  }

  /* IKONA LUPKI */
  .bb-cat-search__btn {
      position: absolute !important;
      right: 0 !important;
      top: 0 !important;
      bottom: 0 !important;
      width: 50px !important;
      background: transparent !important;
      border: none !important;
      display: flex !important;
      align-items: center !important;
      justify-content: center !important;
      cursor: default !important;
      z-index: 5 !important;
  }

  .bb-cat-search__btn svg {
      width: 24px;
      height: 24px;
      fill: none !important;
  }
  
  .bb-cat-search__btn svg path {
      stroke: #ea7404 !important;
      stroke-width: 2;
  }

  /* Przycisk X */
  #bb-cat-search-clear.bb-cat-search__clear {
      position: absolute !important;
      right: 45px !important; 
      top: 50% !important;
      transform: translateY(-50%) !important;
      background: none !important;
      border: none !important;
      font-size: 18px !important;
      color: #ccc !important;
      cursor: pointer !important;
      padding: 0 5px !important;
      z-index: 6 !important;
      display: none;
  }
  #bb-cat-search-clear.bb-cat-search__clear:hover { color: #333 !important; }

  /* Wyniki */
  .bbcatsearch-result {
      position: absolute !important;
      z-index: 999 !important;
      background: #fff !important;
      width: 100% !important;
      left: 0 !important;
      border: 1px solid #e1e1e1 !important;
      border-top: none !important;
      box-shadow: 0 4px 15px rgba(0,0,0,0.08) !important; 
      border-radius: 0 0 4px 4px !important;
      margin-top: -1px !important;
      padding-top: 5px !important;
  }

  .bb-loading { padding: 20px; text-align: center; color: #777; font-size: 13px; }
  
  /* Tekst nad wyszukiwarką */
  .bb-search-text {
      margin-bottom: 10px; 
      font-size: 13px; 
      color: #555;
  }
</style>
{/literal}

  {* === STRUKTURA HTML === *}
  <div class="block-category clearfix tv-category-block-wrapper tv-category-unified-box">
    
    {* 1. NAGŁÓWEK KATEGORII (Z POMARAŃCZOWĄ KRESKĄ) *}
    <div class="tv-category-header-clean">
        <h1>{$category.name}</h1> 
        
        {if !empty($category.description)}
            <div class="tv-category-desc-clean">
                {$category.description nofilter}
            </div>
        {/if}
    </div>

    {* 2. WYSZUKIWARKA *}
    <div id="bb-cat-search"
       class="bb-cat-search"
       data-category-id="{$category.id|intval}"
       data-ajax-url="{$link->getModuleLink('bbcatsearch','ajax',[], true)|escape:'html':'UTF-8'}"
       data-limit="0"
       data-with-children="1"
       data-img-type="home_default">
      
      <div class="bb-search-text">
          Wpisz, czego szukasz, aby błyskawicznie zawęzić wyniki w tej kategorii:
      </div>

      <div class="bb-cat-search__wrapper">
        <input id="bb-cat-search-input"
               class="bb-cat-search__input"
               type="search"
               placeholder="{l s='Np. nazwa produktu, cecha, dieta...' d='Shop.Theme.Catalog'}"
               autocomplete="off"
               minlength="2" />
        
        <button id="bb-cat-search-clear" class="bb-cat-search__clear" type="button" aria-label="Wyczyść">✕</button>

        <div class="bb-cat-search__btn">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M11 19C15.4183 19 19 15.4183 19 11C19 6.58172 15.4183 3 11 3C6.58172 3 3 6.58172 3 11C3 15.4183 6.58172 19 11 19Z" stroke="#ea7404" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M21 21L16.65 16.65" stroke="#ea7404" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </div>
      </div>

      <div id="bb-cat-search-results" class="bbcatsearch-result" style="display:none;"></div>
    </div>

  </div> 

{* === SKRYPTY JS === *}
{literal}
<script>
(function(){
  var userActed = false;
  function markActed(){ userActed = true; enable(); }
  ['scroll','wheel','touchstart','keydown','pointerdown'].forEach(function(ev){
    window.addEventListener(ev, markActed, {passive:true, once:true});
  });
  var selectors = [
    '.js-infinite-scroll', '.js-infinite', '.js-infinite-loader',
    '.infinite-scroll', '.infinite-loader',
    'nav.pagination', '.pagination', '.tv-pagination',
    '#js-product-list-bottom', '.product_list_bottom'
  ];
  var sentinels = [];
  function hide(el){
    if(!el || el.dataset.bbHold === '1') return;
    el.dataset.bbHold = '1';
    el.style.visibility   = 'hidden';
    el.style.height       = '0';
    el.style.overflow     = 'hidden';
    el.style.pointerEvents= 'none';
    sentinels.push(el);
  }
  function findAll(){
    selectors.forEach(function(sel){
      document.querySelectorAll(sel).forEach(hide);
    });
  }
  function enable(){
    if(!userActed) return;
    sentinels.forEach(function(el){
      if(el && el.dataset && el.dataset.bbHold === '1'){
        el.style.visibility   = '';
        el.style.height       = '';
        el.style.overflow     = '';
        el.style.pointerEvents= '';
        delete el.dataset.bbHold;
      }
    });
    if(mo){ try{ mo.disconnect(); }catch(e){} }
  }
  findAll();
  var mo = new MutationObserver(function(){
    if(userActed) return;
    findAll();
  });
  try{
    mo.observe(document.documentElement, {childList:true, subtree:true});
  }catch(e){}
})();
</script>

<script>
(function(){
  var box = document.getElementById('bb-cat-search');
  if(!box) return;
  var DBG = !!window.localStorage && localStorage.getItem('BB_CAT_DEBUG') === '1';

  var input   = document.getElementById('bb-cat-search-input');
  var clearBt = document.getElementById('bb-cat-search-clear');
  var results = document.getElementById('bb-cat-search-results');

  var catId   = box.getAttribute('data-category-id');
  var ajaxUrl = box.getAttribute('data-ajax-url');

  var limit  = box.getAttribute('data-limit') || '0';
  var withCh = box.getAttribute('data-with-children') || '1';
  var imgType= box.getAttribute('data-img-type') || 'home_default';

  function pickContainer(){
    var cands = [
      '#js-product-list', '.products', '#products', '.product_list', '#category-products',
      '.tv-product-wrapper', '.tvcms-product-wrapper', '.tv-category-product-list',
      '.tvcms-product-list', '.tvcms-product', '.tvcmsproduct', '.tvproducts', '.tv-product-grid'
    ];
    for (var i=0;i<cands.length;i++){
      var el = document.querySelector(cands[i]);
      if(el){ return {el:el, sel:cands[i]};
      }
    }
    return {el:null, sel:null};
  }
  var picked = pickContainer();
  var productList = picked.el;
  var productListSelector = picked.sel;
  var replaceList = !!productList;
  var originalHTML = productList ? productList.innerHTML : '';
  if(DBG){
    console.log('[BB_CAT] container', {replaceList:replaceList, selector:productListSelector});
  }

  if(input && clearBt){
      input.addEventListener('input', function(){
          clearBt.style.display = this.value.length > 0 ? 'block' : 'none';
      });
      clearBt.addEventListener('click', function(){
          input.value='';
          this.style.display='none';
          if(results) results.style.display='none';
          restoreList();
      });
  }

  window.bbActiveFeatures = [];
  window.addEventListener('bbcat:filters', function(e){
    window.bbActiveFeatures = (e.detail && Array.isArray(e.detail.features)) ? e.detail.features : [];
    if(DBG){ console.log('[BB_CAT] filters change (customEvent)', window.bbActiveFeatures); }
    doSearch(true);
  });
  document.addEventListener('change', function(e){
    var t = e.target;
    if(!t || !t.classList || !t.classList.contains('bb-fcheck__input')) return;
    var checked = document.querySelectorAll('#bbcat-filters .bb-fcheck__input:checked');
    var arr = [];
    checked.forEach(function(i){ var v=parseInt(i.value,10); if(!isNaN(v)) arr.push(v); });
    window.bbActiveFeatures = arr;
    if(DBG){ console.log('[BB_CAT] filters change (delegated)', window.bbActiveFeatures); }
    doSearch(true);
  }, true);
  document.addEventListener('click', function(e){
    var btn = e.target.closest('.bbcatsearch-close');
    if(!btn) return;
    restoreList();
  }, true);
  function debounce(fn, ms){ var t; return function(){ clearTimeout(t); var a=arguments; t=setTimeout(function(){ fn.apply(null,a);}, ms||250); };
  }

  function hasActiveQuery(){
    var q = (input && input.value) ? input.value.trim() : '';
    return q.length >= 2 || (Array.isArray(window.bbActiveFeatures) && window.bbActiveFeatures.length > 0);
  }

  function renderLoading(){
    if(replaceList && productList){
      productList.innerHTML = '<div class="bb-loading">Ładowanie…</div>';
      try{ productList.scrollIntoView({behavior:'smooth', block:'start'}); }catch(e){}
    } else {
      if(results) {
          results.style.display='block';
          results.innerHTML = '<div class="bbcatsearch-panel"><div class="bbcatsearch-panel__body">Ładowanie…</div></div>';
      }
    }
  }

  function renderHtml(html){
    if(replaceList && productList){
      productList.innerHTML = html || '';
      try{ productList.scrollIntoView({behavior:'smooth', block:'start'}); }catch(e){}
    } else {
      if(results) {
          results.innerHTML = html || '';
          results.style.display = html ? 'block' : 'none';
      }
    }
  }

  function restoreList(){
    if(productList){ productList.innerHTML = originalHTML; }
    if(results){ results.style.display='none'; results.innerHTML=''; }
    window.bbActiveFeatures = [];
  }

  function doSearch(force){
    var q = (input && input.value) ? input.value.trim() : '';
    var shouldRun = force || hasActiveQuery();
    if(DBG){ console.log('[BB_CAT] doSearch', {q:q, features:window.bbActiveFeatures, shouldRun:shouldRun}); }
    if(!shouldRun){ restoreList(); return; }

    renderLoading();

    var xhr = new XMLHttpRequest();
    var data = new FormData();
    data.append('s', q);
    data.append('id_category', catId);
    data.append('limit', limit);
    data.append('with_children', withCh);
    data.append('img_type', imgType);
    if(Array.isArray(window.bbActiveFeatures)){
      window.bbActiveFeatures.forEach(function(fid){ data.append('features[]', fid); });
      if(window.bbActiveFeatures.length){ data.append('features', window.bbActiveFeatures.join(',')); }
    }

    xhr.open('POST', ajaxUrl, true);
    xhr.onreadystatechange = function(){
      if(xhr.readyState === 4){
        if(DBG){ console.log('[BB_CAT] xhr', xhr.status, xhr.responseText ? xhr.responseText.length : 0); }
        if(xhr.status === 200){
          renderHtml(xhr.responseText || '');
        } else {
          renderHtml('<div class="bbcatsearch-panel"><div class="bbcatsearch-panel__body">Błąd wyszukiwania.</div></div>');
        }
      }
    };
    xhr.send(data);
  }

  var run = debounce(doSearch, 300);
  if(input){
    input.addEventListener('input', run);
    input.addEventListener('keydown', function(e){
      if(e.key === 'Escape'){ restoreList(); }
    });
  }
  
  document.addEventListener('click', function(e){
    if(!box.contains(e.target) && results){ results.style.display='none'; }
  });
})();
</script>
{/literal}

{/block}
{/strip}