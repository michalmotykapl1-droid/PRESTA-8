{* BB Category Search – Wyniki + Filtry Diety (Nowoczesny Design) *}

<div class="bbcatsearch-panel">
  
  {* --- NAGŁÓWEK PANELU --- *}
  <div class="bbcatsearch-panel__head">
      <div class="bb-head-info">
        <span class="bb-label">{l s='Przeszukiwana kategoria:' mod='bbcatsearch'}</span>
        <strong class="bb-cat-name">{$category_name|escape:'html':'UTF-8'}</strong>
        
        <span class="bb-sep">•</span>
        
        {if $scope_children}
            <span class="bb-scope">{l s='Szukam też w podkategoriach' mod='bbcatsearch'}</span>
        {else}
            <span class="bb-scope">{l s='Tylko ta kategoria' mod='bbcatsearch'}</span>
        {/if}

        <span class="bb-sep">•</span>
        <span class="bb-count">{l s='Znaleziono:' mod='bbcatsearch'} <strong>{$total_all}</strong></span>

        {if $searchTerm && $searchTerm|trim ne ''}
          <span class="bb-sep">•</span>
          <span class="bb-query">{l s='Fraza:' mod='bbcatsearch'} "<strong>{$searchTerm|escape:'html':'UTF-8'}</strong>"</span>
        {/if}
      </div>
      <button class="bbcatsearch-close" type="button" aria-label="Zamknij">✕</button>
  </div>

  {* --- FILTRY DIET (CHIPS) --- *}
  <div class="bbcatsearch-filters" id="bbcat-filters">
      <div class="bbcat-filters__title">{l s='Filtruj wg diety:' mod='bbcatsearch'}</div>
      <div class="bbcat-filters__body">
        {foreach from=$filters item=f}
          <label class="bb-fcheck
                        {if $f.checked} bb-fcheck--on{/if}
                        {if isset($f.enabled) && not $f.enabled} bb-fcheck--disabled{/if}">
            <input class="bb-fcheck__input"
                   type="checkbox"
                   value="{$f.id_feature|intval}"
                   {if $f.checked}checked="checked"{/if}
                   {if isset($f.enabled) && not $f.enabled}disabled="disabled"{/if} />
            <span class="bb-fcheck__label">{$f.name|escape:'html':'UTF-8'}</span>
          </label>
        {/foreach}
      </div>
  </div>

  {* --- SIATKA PRODUKTÓW --- *}
  {if isset($bb_items) && $bb_items && count($bb_items)}
    <div class="bbcatsearch-grid">
      {foreach from=$bb_items item=it}
        <div class="bbcatsearch-card">
          <a class="bbcatsearch-card__img" href="{$it.url|escape:'html':'UTF-8'}" title="{$it.name|escape:'html':'UTF-8'}">
            {if $it.img}
              <img src="{$it.img|escape:'html':'UTF-8'}" alt="{$it.name|escape:'html':'UTF-8'}" loading="lazy" />
            {else}
              <span class="bb-noimg">IMG</span>
            {/if}
            
            <div class="bbcatsearch-flags">
              {if $it.flags.sale}
                <span class="bbcatsearch-flag flag-sale">{l s='Promocja' mod='bbcatsearch'}</span>
              {/if}
              {if $it.flags.short_date}
                <span class="bbcatsearch-flag flag-shortdate">{l s='Krótka data' mod='bbcatsearch'}</span>
              {/if}
            </div>
          </a>

          <div class="bbcatsearch-card__content">
            <div class="bbcatsearch-card__name">
                <a href="{$it.url|escape:'html':'UTF-8'}">{$it.name|escape:'html':'UTF-8'}</a>
            </div>
            <div class="bb-pricewrap">
                <span class="bb-price-current">{$it.price nofilter}</span>
                {if $it.old_price}
                <span class="bb-price-old">{$it.old_price nofilter}</span>
                {/if}
            </div>
          </div>
        </div>
      {/foreach}
    </div>
  {else}
    {* --- BRAK WYNIKÓW --- *}
    <div class="bbcatsearch-empty">
      <i class="material-icons">search_off</i>
      <p>{l s='Nie znaleziono produktów spełniających kryteria.' mod='bbcatsearch'}</p>
      <span>{l s='Spróbuj zmienić frazę lub odznaczyć filtry diety.' mod='bbcatsearch'}</span>
    </div>
  {/if}
</div>

{literal}
<style>
    /* 1. KONTENER WYNIKÓW */
    .bbcatsearch-panel {
        position: relative;
        z-index: 30;
        background: #fff;
        /* Brak ramek, bo jest wewnątrz category.tpl który ma border, lub dodajemy własny */
        /* Tutaj dajemy delikatny reset, żeby pasowało do białego tła */
        padding: 10px 0 0 0; 
        margin-top: 20px;
        border-top: 1px solid #f0f0f0;
    }

    /* 2. NAGŁÓWEK PASKOWY */
    .bbcatsearch-panel__head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 10px 15px;
        background: #f9f9f9;
        border-radius: 6px;
        margin-bottom: 20px;
        font-size: 13px;
        color: #555;
    }
    .bb-head-info { display: flex; flex-wrap: wrap; align-items: center; gap: 5px; }
    .bb-cat-name { color: #000; font-weight: 600; }
    .bb-sep { color: #ccc; margin: 0 3px; }
    .bbcatsearch-close {
        background: transparent;
        border: none;
        font-size: 24px;
        line-height: 1;
        color: #999;
        cursor: pointer;
        padding: 0 5px;
        transition: color 0.2s;
    }
    .bbcatsearch-close:hover { color: #ea7404; }

    /* 3. FILTRY DIET (Chips Style) */
    .bbcatsearch-filters {
        margin-bottom: 25px;
    }
    .bbcat-filters__title {
        font-size: 12px;
        font-weight: 700;
        text-transform: uppercase;
        color: #999;
        margin-bottom: 10px;
        letter-spacing: 0.5px;
    }
    .bbcat-filters__body {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
    }
    
    /* Styl "Pastylki" dla filtrów */
    .bb-fcheck {
        display: inline-flex;
        align-items: center;
        padding: 6px 12px;
        background: #fff;
        border: 1px solid #e0e0e0;
        border-radius: 20px;
        cursor: pointer;
        transition: all 0.2s ease;
        user-select: none;
    }
    .bb-fcheck:hover {
        border-color: #ccc;
        background: #fcfcfc;
    }
    /* Stan Aktywny */
    .bb-fcheck.bb-fcheck--on {
        background-color: #fff4e5; /* Jasny pomarańcz */
        border-color: #ea7404;
        color: #ea7404;
    }
    
    .bb-fcheck__input { display: none; } /* Ukrywamy standardowy checkbox */
    
    .bb-fcheck__label {
        font-size: 13px;
        font-weight: 500;
    }
    /* Ikona "ptaszka" przy aktywnym (opcjonalnie w CSS) */
    .bb-fcheck.bb-fcheck--on .bb-fcheck__label::before {
        content: '✓';
        margin-right: 5px;
        font-weight: bold;
    }

    /* Stan Nieaktywny */
    .bb-fcheck--disabled {
        opacity: 0.5;
        cursor: not-allowed;
        background: #f5f5f5;
        border-color: #eee;
    }

    /* 4. SIATKA PRODUKTÓW */
    .bbcatsearch-grid {
        display: grid;
        grid-template-columns: repeat(5, 1fr); /* 5 kolumn na dużych */
        gap: 15px;
    }
    @media (max-width: 1200px) { .bbcatsearch-grid { grid-template-columns: repeat(4, 1fr); } }
    @media (max-width: 992px) { .bbcatsearch-grid { grid-template-columns: repeat(3, 1fr); } }
    @media (max-width: 768px) { .bbcatsearch-grid { grid-template-columns: repeat(2, 1fr); } }

    /* 5. KARTA PRODUKTU (Minimalizm) */
    .bbcatsearch-card {
        background: #fff;
        border: 1px solid transparent; /* Bez ramki domyślnie */
        border-radius: 8px;
        overflow: hidden;
        transition: all 0.2s ease;
        padding-bottom: 10px;
    }
    .bbcatsearch-card:hover {
        box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        transform: translateY(-2px);
        border-color: #f0f0f0;
    }

    /* Zdjęcie */
    .bbcatsearch-card__img {
        display: block;
        position: relative;
        padding-bottom: 100%; /* Kwadrat */
        overflow: hidden;
        border-radius: 6px;
        margin-bottom: 10px;
    }
    .bbcatsearch-card__img img {
        position: absolute;
        top: 0; left: 0;
        width: 100%; height: 100%;
        object-fit: contain;
        padding: 5px;
        transition: transform 0.3s ease;
    }
    .bbcatsearch-card:hover .bbcatsearch-card__img img {
        transform: scale(1.05);
    }

    /* Flagi (Promocja etc.) */
    .bbcatsearch-flags {
        position: absolute;
        top: 5px; left: 5px;
        display: flex; flex-direction: column; gap: 4px;
    }
    .bbcatsearch-flag {
        font-size: 10px;
        font-weight: 700;
        text-transform: uppercase;
        color: #fff;
        padding: 3px 6px;
        border-radius: 3px;
    }
    .flag-sale { background: #d90244; } /* Różowy wyprzedaży */
    .flag-shortdate { background: #ef6c00; }

    /* Treść karty */
    .bbcatsearch-card__content {
        padding: 0 5px;
        text-align: center;
    }
    .bbcatsearch-card__name {
        height: 36px; /* 2 linie tekstu */
        overflow: hidden;
        margin-bottom: 5px;
    }
    .bbcatsearch-card__name a {
        font-size: 13px;
        color: #333;
        line-height: 1.4;
        text-decoration: none;
        font-weight: 400;
    }
    .bbcatsearch-card__name a:hover { color: #ea7404; }

    /* Cena */
    .bb-pricewrap {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
    }
    .bb-price-current {
        font-size: 16px;
        font-weight: 700;
        color: #ea7404; /* Pomarańcz */
    }
    .bb-price-old {
        font-size: 12px;
        text-decoration: line-through;
        color: #999;
    }

    /* Pusty stan */
    .bbcatsearch-empty {
        text-align: center;
        padding: 40px 20px;
        color: #777;
    }
    .bbcatsearch-empty i { font-size: 48px; color: #ddd; margin-bottom: 10px; display: block; }
    .bbcatsearch-empty p { font-size: 16px; font-weight: 600; margin-bottom: 5px; color: #333; }
</style>
{/literal}