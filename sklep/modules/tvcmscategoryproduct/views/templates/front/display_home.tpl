{**
* 2007-2025 PrestaShop
* STREFA FRESH - FINAL (LINKI BEZPOŚREDNIE)
*}
{strip}
{if $dis_arr_result['status']}
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

<div class='container-fluid tvcmstabcategory-product-slider fresh-wrapper'>
    <div class='container'>
        
        {* --- NAGŁÓWEK --- *}
        <div class="fresh-header-section">
            <div class="fresh-header-accent">ŚWIEŻOŚĆ, KTÓRĄ POKOCHASZ</div>
            <h2 class="fresh-header-title">STREFA FRESH</h2>
            <div class="fresh-header-line"></div>
            <p class="fresh-header-desc">
                Poczuj różnicę w smaku prawdziwego jedzenia. Wybieramy dla Ciebie tylko to, co najlepsze.<br>
                Gwarantujemy, że dotrą do Ciebie w idealnej temperaturze.
            </p>
        </div>

        {* --- UKŁAD GŁÓWNY --- *}
        <div class="fresh-mosaic-layout">
            
            {* --- LEWA KOLUMNA (INFO) --- *}
            <div class="fresh-info-panel">
                <div class="fresh-info-badge">JAK DOSTARCZAMY?</div>
                <h3 class="fresh-info-title">Gwarancja zimnego łańcucha dostaw</h3>
                <p class="fresh-info-desc">
                    Produkty chłodzone wymagają specjalnego traktowania. Aby zachować ich 100% jakości, pomijamy zwykłych kurierów. Wybierz najwygodniejszą opcję dla siebie:
                </p>

                <div class="fresh-delivery-options">
                    {* OPCJA 1: ODBIÓR *}
                    <div class="delivery-option-item">
                        <div class="del-icon"><i class="fa-solid fa-shop"></i></div>
                        <div class="del-content">
                            <h4 class="del-title">Odbiór Osobisty</h4>
                            <p class="del-text">
                                Zamawiasz <strong>bez limitu</strong>. Odbierz w ciągu <strong>24h</strong> od powiadomienia SMS/e-mail, aby zachować świeżość.
                            </p>
                        </div>
                    </div>

                    {* OPCJA 2: TRANSPORT *}
                    <div class="delivery-option-item">
                        <div class="del-icon"><i class="fa-solid fa-truck-fast"></i></div>
                        <div class="del-content">
                            <h4 class="del-title">Transport BigBio</h4>
                            <p class="del-text">
                                Auto chłodnicze pod Twoje drzwi (Kraków + 20km).<br>
                                Opcja dostępna od <strong>350 zł</strong>.
                            </p>
                        </div>
                    </div>
                </div>

                {* --- PRZYCISK (LINK) --- *}
                <div class="fresh-info-footer">
                    {* WPISZ SWÓJ LINK W MIEJSCE # PONIŻEJ *}
                    <a href="#" class="btn-fresh-more">
                        ZOBACZ WARUNKI ZAMÓWIENIA <i class="fa-solid fa-arrow-right"></i>
                    </a>
                </div>
            </div>

            {* --- PRAWA KOLUMNA (SIATKA 4 KATEGORII) --- *}
            <div class="fresh-categories-grid-4">
                {foreach $dis_arr_result['data'] as $data name=cats}
                    {if $smarty.foreach.cats.iteration <= 4}
                        
                        {* 1. USTALAMY ID NA SZTYWNO DLA KAŻDEJ POZYCJI *}
                        {assign var='target_id' value='0'}
                        
                        {if $smarty.foreach.cats.iteration == 1}
                            {assign var='target_id' value='261677'} {* Warzywa i owoce *}
                        {elseif $smarty.foreach.cats.iteration == 2}
                            {assign var='target_id' value='270238'} {* Nabiał *}
                        {elseif $smarty.foreach.cats.iteration == 3}
                            {assign var='target_id' value='270225'} {* Mięso wedliny ryby *}
                        {elseif $smarty.foreach.cats.iteration == 4}
                            {assign var='target_id' value='270233'} {* Mrożonki *}
                        {/if}

                        {* 2. BUDUJEMY PEŁNY LINK ANALOGICZNIE DO TWOJEGO SCRREENA *}
                        {* index.php?id_category=XX&controller=category&id_lang=YY *}
                        
                        <a href="{$dis_arr_result.baseUrl}index.php?id_category={$target_id}&controller=category&id_lang={$dis_arr_result.id_lang}" class="fresh-cat-card">
                            
                            <div class="cat-main-icon">
                                {if $smarty.foreach.cats.iteration == 1}<i class="fa-solid fa-carrot"></i>{/if}
                                {if $smarty.foreach.cats.iteration == 2}<i class="fa-solid fa-cheese"></i>{/if}
                                {if $smarty.foreach.cats.iteration == 3}<i class="fa-solid fa-drumstick-bite"></i>{/if}
                                {if $smarty.foreach.cats.iteration == 4}<i class="fa-solid fa-snowflake"></i>{/if}
                            </div>
                            
                            <div class="cat-content-simple">
                                <h4 class="cat-title-simple">
                                    {if $smarty.foreach.cats.iteration == 1}Świeże (owoce i warzywa){/if}
                                    {if $smarty.foreach.cats.iteration == 2}Nabiał{/if}
                                    {if $smarty.foreach.cats.iteration == 3}Mięso, wędliny, ryby{/if}
                                    {if $smarty.foreach.cats.iteration == 4}Mrożonki{/if}
                                </h4>
                                <span class="cat-btn-simple">ZOBACZ PRODUKTY <i class="fa-solid fa-arrow-right"></i></span>
                            </div>
                        </a>
                    {/if}
                {/foreach}
            </div>
        </div>

    </div>
</div>
{/if}
{/strip}