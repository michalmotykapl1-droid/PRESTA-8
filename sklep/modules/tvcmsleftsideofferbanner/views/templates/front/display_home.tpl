{**
* ThemeVolty - Left Side Offer Banner (Modern CSS Version - Fixed Variables)
*}
{strip}
    {* Sprawdzamy czy baner ma być widoczny (zgodnie z ustawieniami modułu) *}
    {if $AllPageShow == 1 || ($AllPageShow == 0 && $page.page_name == 'index')}
        
        <div class="tvcmsleftsideofferbanners-one container-fluid">
            <div class="tvleft-banner-wrapper">
                
                {* Link pobierany z ustawień modułu ($data.TVCMSLEFTSIDEOFFERBANNER_LINK) *}
                <a href="{$data.TVCMSLEFTSIDEOFFERBANNER_LINK}" class="tv-sale-card">
                    
                    {* GÓRNY AKCENT (Ikona) *}
                    <div class="tv-sale-icon">
                        <i class="fa-solid fa-fire-flame-curved"></i>
                    </div>

                    {* TREŚĆ *}
                    <div class="tv-sale-content">
                        <h3 class="tv-sale-title">ŁAP OKAZJE</h3>
                        
                        <div class="tv-sale-value-box">
                            <span class="tv-sale-small">DO</span>
                            <span class="tv-sale-big">-50%</span>
                            <span class="tv-sale-small">TANIEJ</span>
                        </div>

                        <p class="tv-sale-desc">Najlepsze produkty w super cenach</p>
                    </div>

                    {* PRZYCISK *}
                    <div class="tv-sale-btn">
                        SPRAWDŹ TERAZ <i class="fa-solid fa-arrow-right"></i>
                    </div>

                    {* DEKORACJA TŁA *}
                    <div class="tv-sale-decor"></div>
                </a>

            </div>
        </div>
    {/if}
{/strip}