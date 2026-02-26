{**
* 2007-2025 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License 3.0 (AFL-3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* https://opensource.org/licenses/AFL-3.0
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future.
* If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
* @author PrestaShop SA <contact@prestashop.com>
* @copyright 2007-2025 PrestaShop SA
* @license https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
* International Registered Trademark & Property of PrestaShop SA
*}
{strip}
    {extends file=$layout}
    {block name='head_seo' prepend}
    <link rel="canonical" href="{$product.canonical_url}">
    {/block}
    {block name='head' append}
    <meta content="width=device-width, initial-scale=1" name="viewport">
    <meta property="og:type" content="product">
    <meta property="og:url" content="{$urls.current_url}">
    <meta property="og:title" content="{$page.meta.title}">
    <meta property="og:site_name" content="{$shop.name}">
    <meta property="og:description" content="{$page.meta.description}">
    <meta property="og:image" content="{$product.cover.large.url}">
    <meta property="product:pretax_price:amount" content="{$product.price_tax_exc}">
    <meta property="product:pretax_price:currency" content="{$currency.iso_code}">
    <meta property="product:price:amount" content="{$product.price_amount}">
    <meta property="product:price:currency" content="{$currency.iso_code}">
    {if isset($product.weight) && ($product.weight != 0)}
    <meta property="product:weight:value" content="{$product.weight}">
    <meta property="product:weight:units" content="{$product.weight_unit}">
    {/if}
    
    {* --- STYLE: BIAŁE BOXY, PRZYCISK OPINII + FIX PRZYCISKU KOSZYKA (RWD) --- *}
    <style>
        /* 1. FIX WARSTW: Modal musi być absolutnie na wierzchu */
        #fbt-modal, #blockcart-modal {
            z-index: 99999 !important;
        }
        .modal-backdrop {
            z-index: 99990 !important;
        }

        /* 2. GŁÓWNY KONTENER KARTY */
        .tv-product-card-unified {
            background: #ffffff;
            padding: 40px; 
            margin-top: 30px;
            margin-bottom: 30px;
            border-radius: 8px;
            border: 1px solid #ebebeb;
            box-shadow: 0 5px 20px rgba(0,0,0,0.03);
        }

        /* 3. TYTUŁY SEKCJI */
        .tv-section-title {
            margin: 0;
            font-size: 20px;
            font-weight: 700;
            text-transform: uppercase;
            color: #222;
            letter-spacing: 0.5px;
            position: relative;
            display: inline-block;
            padding-bottom: 10px;
        }

        .tv-section-title::after {
            content: '';
            display: block;
            width: 50px;
            height: 3px;
            background: #ea7404;
            position: absolute;
            bottom: 0;
            left: 0;
        }

        .tv-section-separator {
            border: 0;
            border-top: 1px solid #f0f0f0;
            margin: 40px 0; 
        }
        
        .product-diet-info {
            background: transparent !important;
            padding: 0 !important;
            box-shadow: none !important;
            border: none !important;
        }

        /* --- 4. CSS DLA SEKCJI OPINII --- */
        
        #product-reviews-container .tab-pane, 
        #tvcmsproductCommentsBlock {
            display: block !important;
            opacity: 1 !important;
            visibility: visible !important;
        }

        #tvcmsproductCommentsBlock .tabs, 
        #tvcmsproductCommentsBlock h3,
        #tvcmsproductCommentsBlock h4 {
            display: none !important;
        }
        
        /* Ukrywamy tylko przycisk otwierania, NIE przycisk "Wyślij" */
        #tvcmsproductCommentsBlock .open-comment-form {
            display: none !important;
        }
        .tvall-inner-btn {
            display: inline-block !important; 
        }

        .product-reviews-block {
            position: relative;
            overflow: visible !important;
            min-height: 50px; 
        }
        
        /* Styl przycisku opinii */
        .custom-write-review-btn {
            background: transparent;
            border: none;
            color: #888888;
            font-weight: 600;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 5px 0;
            cursor: pointer;
            transition: color 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .custom-write-review-btn i {
            font-size: 14px;
            color: #999999;
        }
        .custom-write-review-btn:hover {
            color: #ea7404;
            text-decoration: none;
        }
        .custom-write-review-btn:hover i {
            color: #ea7404;
        }

        .no-reviews-fallback {
            display: none;
            text-align: center;
            padding: 30px 0;
            color: #777;
            font-size: 14px;
            background: #f9f9f9;
            border-radius: 4px;
            margin-top: 15px;
            border: 1px dashed #e0e0e0;
        }
        .no-reviews-fallback strong {
            display: block;
            margin-top: 5px;
            color: #555;
        }

        /* --- 5. FIX PRZYCISKU DODAJ DO KOSZYKA (Twoja Klasa z Screena) --- */
        
        /* 1. Wrapper nadrzędny (widoczny w inspektorze) */
        .tvwishlist-compare-wrapper-page,
        .tv-product-page-add-to-cart-wrapper {
            width: 100% !important;
            display: block !important;
            float: none !important;
        }

        /* 2. Bezpośredni kontener przycisku (tvcart-btn-model) */
        .tvcart-btn-model {
            width: 100% !important;
            display: flex !important; /* Flex żeby guzik wypełnił */
            flex: 1 1 100% !important;
            float: none !important;
            margin: 0 !important;
        }

        /* 3. Sam przycisk (button.add-to-cart) */
        button.add-to-cart {
            width: 100% !important;
            min-width: 100% !important;
            display: flex !important;
            justify-content: center !important;
            align-items: center !important;
            margin: 0 !important;
        }

    </style>
    {/block}

    {block name='head_microdata_special'}
        {include file='_partials/microdata/product-jsonld.tpl'}
    {/block}
    
    {block name='content'}
    <div id="main" itemscope itemtype="https://schema.org/Product">
        <meta itemprop="url" content="{$product.url}">
        <div class="tvproduct-page-wrapper">
            {assign var="prod_layout" value="../catalog/tv-{$TVCMSPRODUCTCUSTOM_LAYOUT}.tpl"}
            {if isset($product.id_category_list)}
              {assign var=catlist value=$product.id_category_list}
            {else}
              {assign var=catlist value=[$product.id_category_default]}
            {/if}

            {* ETYKIETY *}
            {if isset($catlist)}
              <div class="product-label-inline" style="display:inline-block;margin-left:10px;">
                {if in_array(180, $catlist)}
                  <span class="label-shortdate">KRÓTKA DATA</span>
                {elseif in_array(45, $catlist)}
                  <span class="label-sale">ŁAP OKAZJE</span>
                {/if}
{* 2. NOWA LOGIKA FRESH + KOLOR CYJAN (Z Twoich grafik) *}
    {if Module::isEnabled('freshlabel') && Module::getInstanceByName('freshlabel')->isFresh($product.id_product)}
        <span class="badge-fresh-separate" style="
            background-color: #009fe3 !important;
            color: #ffffff !important;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 3px 8px;
            margin-top: 4px;
            border-radius: 3px;
            font-family: 'Roboto', sans-serif;
            font-size: 10px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            line-height: 1.1;
            box-shadow: 0 1px 3px rgba(0,0,0,0.2);
            white-space: nowrap;
        ">STREFA FRESH</span>
    {/if}

              </div>
            {/if}
            
            {* GÓRNA SEKCJA PRODUKTU (Z-INDEX: 50) *}
            <div style="position: relative; z-index: 50;">
                {include file="$prod_layout"}
            </div>

            {* MODUŁ FBT *}
            <div style="position: relative;">
                {hook h='displayBBFrequentlyBoughtTogether' product=$product}
            </div>

            {* --- DÓŁ STRONY: JEDEN DUŻY BIAŁY KAFEL (UNIFIED) --- *}
            {block name='product_tabs'}
            
            <div class="tv-product-card-unified">
                
                {* 1. PARAMETRY DIETETYCZNE *}
                <div class="product-diet-info">
                      <div style="margin-bottom: 25px;">
                          <h3 class="tv-section-title">PARAMETRY DIETETYCZNE</h3>
                      </div>
                      
                      <ul class="diet-features-list">
                          {if isset($product.features)}
                            {foreach from=$product.features item=feature}
                            {assign var="icon_data" value=''}
                            {if $feature.name == 'Dieta: Wegańska'}
                              {assign var="icon_data" value=['class' => 'veg-desc', 'text' => 'VEG']}
                            {elseif $feature.name == 'Dieta: Bez glutenu'}
                              {assign var="icon_data" value=['class' => 'gf-desc', 'text' => 'GF']}
                            {elseif $feature.name == 'Dieta: Keto / Low-Carb'}
                              {assign var="icon_data" value=['class' => 'keto-desc', 'text' => 'KETO']}
                            {elseif $feature.name == 'Certyfikat: BIO'}
                              {assign var="icon_data" value=['class' => 'bio-desc', 'text' => 'BIO']}
                            {elseif $feature.name == 'Bez: Laktozy'}
                              {assign var="icon_data" value=['class' => 'nl-desc', 'text' => 'NL']}
                            {elseif $feature.name == 'Bez: Cukru'}
                              {assign var="icon_data" value=['class' => 'ns-desc', 'text' => 'NS']}
                            {elseif $feature.name == 'Dieta: Wegetariańska'}
                              {assign var="icon_data" value=['class' => 'vege-desc', 'text' => 'VEGE']}
                            {elseif $feature.name == 'Dieta: Niski Indeks Glikemiczny'}
                              {assign var="icon_data" value=['class' => 'ig-desc', 'text' => 'IG']}
                            {/if}

                            {if $icon_data && $feature.name != 'Rodzaj produktu'}
                              <li class="diet-feature-item">
                                <span class="diet-badge-desc {$icon_data.class|escape:'htmlall':'UTF-8'}">{$icon_data.text|escape:'htmlall':'UTF-8'}</span>
                                <span class="diet-feature-text">{$feature.name}</span>
                              </li>
                            {/if}
                          {/foreach}
                        {/if}
                        
                        {if isset($product.product_specific_references)}
                          {foreach from=$product.product_specific_references item=reference}
                            {assign var="icon_data_ref" value=''}
                            {if $reference.name == 'Dieta: Wegańska'}
                              {assign var="icon_data_ref" value=['class' => 'veg-desc', 'text' => 'VEG']}
                            {elseif $reference.name == 'Dieta: Bez glutenu'}
                              {assign var="icon_data_ref" value=['class' => 'gf-desc', 'text' => 'GF']}
                            {elseif $reference.name == 'Dieta: Keto / Low-Carb'}
                              {assign var="icon_data_ref" value=['class' => 'keto-desc', 'text' => 'KETO']}
                            {elseif $reference.name == 'Certyfikat: BIO'}
                              {assign var="icon_data_ref" value=['class' => 'bio-desc', 'text' => 'BIO']}
                            {elseif $reference.name == 'Bez: Laktozy'}
                              {assign var="icon_data_ref" value=['class' => 'nl-desc', 'text' => 'NL']}
                            {elseif $reference.name == 'Bez: Cukru'}
                              {assign var="icon_data_ref" value=['class' => 'ns-desc', 'text' => 'NS']}
                            {elseif $reference.name == 'Dieta: Wegetariańska'}
                              {assign var="icon_data_ref" value=['class' => 'vege-desc', 'text' => 'VEGE']}
                            {elseif $reference.name == 'Dieta: Niski Indeks Glikemiczny'}
                              {assign var="icon_data_ref" value=['class' => 'ig-desc', 'text' => 'IG']}
                            {/if}

                            {if $icon_data_ref && $reference.name != 'Rodzaj produktu'}
                              <li class="diet-feature-item">
                                <span class="diet-badge-desc {$icon_data_ref.class|escape:'htmlall':'UTF-8'}">{$icon_data_ref.text|escape:'htmlall':'UTF-8'}</span>
                                <span class="diet-feature-text">{$reference.name}</span>
                              </li>
                            {/if}
                          {/foreach}
                        {/if}
                      </ul>
                      
                      <button type="button"
                              class="diet-info-trigger diet-info-trigger-link"
                              aria-label="Co oznaczają parametry dietetyczne?"
                              title="Co oznaczają parametry dietetyczne?">
                        Co oznaczają parametry dietetyczne?
                      </button>
                </div>

                {* MODAL Z PEŁNYMI DEFINICJAMI *}
                <div id="diet-info-modal" class="diet-modal" aria-hidden="true">
                      <div class="diet-modal__backdrop"></div>
                      <div class="diet-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="diet-modal-title">
                        <button type="button" class="diet-modal__close" aria-label="Zamknij">&times;</button>

                        <h3 id="diet-modal-title">Co oznaczają „Parametry dietetyczne”?</h3>

                        <p>Znaczki przy parametrach dietetycznych nadajemy na podstawie składu produktu oraz deklaracji producenta. Poniżej znajdziesz wyjaśnienie poszczególnych oznaczeń:</p>

                        <h4><span class="diet-badge-desc bio-desc">BIO</span> Certyfikat: BIO</h4>
                        <p>Produkt posiada certyfikat rolnictwa ekologicznego. Oznacza to, że co najmniej 95% składników pochodzenia rolniczego powstało metodami ekologicznymi, bez użycia syntetycznych nawozów i pestycydów.</p>

                        <h4><span class="diet-badge-desc veg-desc">VEG</span> Dieta: Wegańska</h4>
                        <p>Produkt w 100% roślinny. Nie zawiera mięsa, ryb, nabiału, jaj, miodu ani żadnych innych składników pochodzenia zwierzęcego.</p>

                        <h4><span class="diet-badge-desc vege-desc">VEGE</span> Dieta: Wegetariańska</h4>
                        <p>Produkt bezmięsny. Nie zawiera mięsa, ryb ani owoców morza. Może zawierać produkty odzwierzęce takie jak mleko, jaja czy miód.</p>

                        <h4><span class="diet-badge-desc ns-desc">NS</span> Bez cukru</h4>
                        <p>Produkt bez dodatku cukru (sacharozy). Słodycz wynika z naturalnie występujących cukrów w owocach/warzywach lub zastosowania bezpiecznych zamienników cukru (słodzików).</p>

                        <h4><span class="diet-badge-desc nl-desc">NL</span> Bez laktozy</h4>
                        <p>Produkt nie zawiera laktozy (cukru mlecznego). Odpowiedni dla osób z nietolerancją laktozy. Może to być produkt naturalnie bezmleczny lub poddany procesowi usunięcia laktozy.</p>

                        <h4><span class="diet-badge-desc gf-desc">GF</span> Bez glutenu</h4>
                        <p>Produkt bezpieczny dla osób unikających glutenu. Nie zawiera pszenicy, żyta, jęczmienia ani owsa (chyba że certyfikowanego). Odpowiedni dla osób z celiakią i nietolerancją glutenu.</p>

                        <h4><span class="diet-badge-desc keto-desc">KETO</span> Dieta: Keto / Low-Carb</h4>
                        <p>Produkt o obniżonej zawartości węglowodanów netto. Jest odpowiedni dla osób stosujących dietę ketogeniczną (Keto) oraz diety niskowęglowodanowe (Low-Carb).</p>

                        <h4><span class="diet-badge-desc ig-desc">IG</span> Dieta: Niski indeks glikemiczny</h4>
                        <p>Produkt charakteryzujący się niskim indeksem glikemicznym. Powoduje powolny i stabilny wzrost poziomu glukozy we krwi, dzięki czemu jest przyjazny dla diabetyków i osób z insulinoopornością.</p>
                        
                        <p class="diet-modal__disclaimer"><strong>Uwaga:</strong> parametry dietetyczne mają charakter informacyjny.</p>
                      </div>
                </div>

                {* SEPARATOR *}
                <hr class="tv-section-separator">

                {* 2. OPIS PRODUKTU *}
                {if $product.description}
                  <div class="product-description-block">
                        <div style="margin-bottom: 25px;">
                            <h3 class="tv-section-title">
                                {l s='Description' d='Shop.Theme.Catalog'}
                            </h3>
                        </div>
                        <div class="product-description cms-description">
                            {$product.description nofilter}
                        </div>
                    </div>
                    
                    {* SEPARATOR *}
                    <hr class="tv-section-separator">
                {/if}

                {* 3. RECENZJE (MODUŁ) *}
                <div class="product-reviews-block" id="product-reviews-container">
                    
                    {* 1. TYTUŁ *}
                    <div style="margin-bottom: 5px;">
                        <h3 class="tv-section-title">
                            OPINIE O PRODUKCIE
                        </h3>
                    </div>

                    {* 2. PRZYCISK *}
                    <div style="text-align: right; margin-bottom: 10px;">
                        <button id="btn-trigger-review" class="custom-write-review-btn">
                            <i class="fa-solid fa-pen"></i> DODAJ OPINIĘ
                        </button>
                    </div>

                    {* 3. TREŚĆ MODUŁU *}
                    <div class="reviews-content-wrapper">
                        {hook h='displayProductListReviewsTabContent' product=$product}
                    </div>

                    {* 4. KOMUNIKAT "BRAK OPINII" *}
                    <div id="no-reviews-fallback" class="no-reviews-fallback">
                        Brak opinii o tym produkcie.<br>
                        <strong>Bądź pierwszy i wystaw opinię!</strong>
                    </div>

                </div>

            </div>
            {* KONIEC BIAŁEGO KAFELKA *}
            
            {/block}
        </div>

        {block name='product_accessories'}
        {if $accessories}
        <div class="tvcmslike-product container-fluid">
            <div class='tvlike-product-wrapper-box container'>
                <div class='tvcmsmain-title-wrapper'>
                    <div class="tvcms-main-title">
                        <div class='tvmain-title'>
                            <h2>{l s='You might also like' d='Shop.Theme.Catalog'}</h2>
                        </div>
                    </div>
                </div>
                <div class="tvlike-product">
                    <div class="products owl-theme owl-carousel tvlike-product-wrapper tvproduct-wrapper-content-box">
                        {foreach $accessories as $product}
                        {include file="catalog/_partials/miniatures/product.tpl" product=$product tv_product_type="like_product"}
                        {/foreach}
                    </div>
                </div>
                <div class='tvlike-pagination-wrapper tv-pagination-wrapper'>
                    <div class="tvcmslike-next-pre-btn tvcms-next-pre-btn">
                        <div class="tvcmslike-prev tvcmsprev-btn" data-parent="tvcmslike-product"><i class='material-icons'>&#xe317;</i></div>
                        <div class="tvcmslike-next tvcmsnext-btn" data-parent="tvcmslike-product"><i class='material-icons'>&#xe317;</i></div>
                    </div>
                </div>
            </div>
        </div>
        {/if}
        {/block}
        
        {block name='product_footer'}
            {hook h='displayBBProductCategorySlider' product=$product}
            {hook h='displayFooterProduct' product=$product category=$category}
            
            {* --- NOWY MODUŁ: NIESKOŃCZONE PRODUKTY --- *}
            {hook h='displayBBUnfinishedProducts' product=$product}
        {/block}
        
        {block name='product_images_modal'}
        {include file='catalog/_partials/product-images-modal.tpl'}
        {/block}
        {block name='page_footer_container'}
        {if Configuration::get('TVCMSCUSTOMSETTING_PRODUCT_PAGE_BOTTOM_STICKY_STATUS')}
        <div class="tvfooter-product-sticky-bottom">
            <div class="container">
                <div class="tvflex-items">
                    <div class="tvproduct-image-title-price">
                        {if $product.cover}
                        <div class="product-image">
                            <img src="{$product.cover.bySize.large_default.url}" alt="{$product.cover.legend}" title="{$product.cover.legend}" itemprop="image" width="{$product.cover.bySize.large_default.width}" height="{$product.cover.bySize.large_default.height}" loading="lazy">
                        </div>
                        <div class="tvtitle-price">
                            {block name='page_header'}
                             <h1 class="h1" itemprop="name">{block name='page_title'}{$product.name}{/block}</h1>
                            {/block}
                            {block name='product_prices'}
                             {include file='catalog/_partials/product-prices.tpl'}
                            {/block}
                        </div>
                        {/if}
                    </div>
                    <div>
                        <div class="product-actions" id="bottom_sticky_data"></div>
                    </div>
                </div>
            </div>
        </div>
        {/if}
        <footer class="page-footer">
            {block name='page_footer'}
            {/block}
        
        {* --- SKRYPTY JS --- *}
        {literal}
        <script>
        document.addEventListener('DOMContentLoaded', function () {
          // 1. DIETY - MODAL
          var trigger = document.querySelector('.diet-info-trigger');
          var modal   = document.getElementById('diet-info-modal');
          if (trigger && modal) {
              var backdrop = modal.querySelector('.diet-modal__backdrop');
              var closeBtn = modal.querySelector('.diet-modal__close');
              function openModal() { modal.classList.add('is-open'); document.body.classList.add('diet-modal-open'); }
              function closeModal() { modal.classList.remove('is-open'); document.body.classList.remove('diet-modal-open'); }
              trigger.addEventListener('click', openModal);
              [backdrop, closeBtn].forEach(function (el) { if (el) el.addEventListener('click', closeModal); });
          }

          // 2. DETEKCJA BRAKU OPINII
          setTimeout(function() {
              var container = document.getElementById('product-reviews-container');
              if (!container) return;

              var fallbackMsg = document.getElementById('no-reviews-fallback');
              var contentWrapper = container.querySelector('.reviews-content-wrapper');

              if (contentWrapper) {
                  var textContent = contentWrapper.innerText.trim();
                  if (textContent.length < 20 || textContent.includes('Brak recenzji') || textContent.includes('No reviews')) {
                      if(fallbackMsg) fallbackMsg.style.display = 'block';
                      contentWrapper.style.display = 'none';
                  }
              } else {
                  if(fallbackMsg) fallbackMsg.style.display = 'block';
              }
          }, 800);

          // 3. PROXY CLICK
          var myBtn = document.getElementById('btn-trigger-review');
          if(myBtn) {
              myBtn.addEventListener('click', function(e) {
                  e.preventDefault();
                  var originalBtn = document.querySelector('#tvcmsproductCommentsBlock .open-comment-form');
                  if(!originalBtn) {
                       originalBtn = document.querySelector('#tvcmsproductCommentsBlock .tvall-inner-btn');
                  }
                  if(originalBtn) {
                      originalBtn.click();
                  } else {
                      console.error('Nie znaleziono oryginalnego przycisku.');
                  }
              });
          }
        });
        </script>
        {/literal}

        </footer>
        {/block}
    </div>
    {/block}
{/strip}