{strip}
{* --- STREFA KOSZYKA: SLIDER + REFRESH (Button next to arrows) --- *}

{if !$is_strefa_ajax}
    <section class="strefa-section">
        <div class="container">
            <div id="strefa-content-area" class="strefa-lazy-skeleton">
                <div style="text-align:center; padding: 40px; color:#999;">
                    <i class="material-icons" style="font-size:24px; animation:spin 1s infinite linear;">refresh</i>
                    <br>Ładowanie okazji...
                </div>
            </div>
        </div>
    </section>
{else}
    {if isset($impulse_products) && $impulse_products}
        <div class="strefa-container">
            
            {* 1. NAGŁÓWEK *}
            <div class="strefa-top-bar">
                <div class="strefa-title-wrapper">
                    <h3 class="strefa-title">
                        <i class="material-icons" style="color:#ea7404; vertical-align:middle; margin-right:5px;">local_offer</i>
                        {$strefa_title}
                    </h3>
                    <div class="strefa-line"></div>
                </div>
                
                {* Przycisk został usunięty stąd i przeniesiony niżej do strzałek *}
            </div>

            {* 2. SLIDER *}
            <div class="strefa-slider-wrapper">
                <div class="strefa-slider" id="strefaSlider">
                    {foreach from=$impulse_products item="product"}
                        <div class="strefa-item">
                            {include file="catalog/_partials/miniatures/product.tpl" product=$product tv_product_type='tab_product' tab_slider=false}
                        </div>
                    {/foreach}
                </div>
            </div>

            {* 3. NAWIGACJA (STRZAŁKI + ODŚWIEŻANIE) *}
            <div class="strefa-arrows-wrapper">
                {* --- PRZYCISK ODŚWIEŻANIA (Teraz tutaj) --- *}
                <button type="button" class="strefa-refresh-btn" id="strefaRefreshBtn" title="Pobierz nowe propozycje">
                    <i class="material-icons refresh-icon">refresh</i> <span>LOSUJ INNE</span>
                </button>
                
                {* Separator pionowy (opcjonalny, zrobimy go w CSS) *}
                <div class="strefa-sep"></div>

                <button class="strefa-arrow strefa-prev" aria-label="Poprzednie"><i class="material-icons">chevron_left</i></button>
                <button class="strefa-arrow strefa-next" aria-label="Następne"><i class="material-icons">chevron_right</i></button>
            </div>

        </div>
    {/if}
{/if}
{/strip}