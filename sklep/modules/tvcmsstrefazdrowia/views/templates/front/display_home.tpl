{**
 * Strefa Zdrowia - WIDOK HOME (Z linkiem do Landing Page)
 *}
{strip}
<div class="strefa-services-wrapper">
    <div class="container">
        
        {* --- NAGŁÓWEK SEKCJI --- *}
        <div class="strefa-header">
            <span class="strefa-sub-tag">KOMPLEKSOWE PODEJŚCIE</span>
            <h2 class="bb-header-title">STREFA ZDROWIA & RÓWNOWAGI</h2>
            <div class="strefa-divider"></div>
            <p class="strefa-intro">
                Wybierz obszar, w którym potrzebujesz wsparcia.
                Łączymy profesjonalną fizjoterapię, naturopatię i nowoczesną kosmetologię.
            </p>
        </div>

        <div class="strefa-layout-container">
            
            {* LEWA KOLUMNA *}
            <div class="main-promo-col">
                <div class="promo-content">
                    <span class="promo-tag">DYPLOMOWANI SPECJALIŚCI</span>
                    
                    <h3>Twoje zdrowie<br>w dobrych rękach</h3>
                    
                    <div class="promo-desc">
                        <p style="margin-bottom: 15px;">
                            Odzyskaj sprawność dzięki fizjoterapii, zadbaj o wygląd w strefie beauty i osiągnij harmonię z naturopatą.
                        </p>
                        <p>
                            Łączymy skuteczną rehabilitację i zabiegi kosmetyczne z naturalnym wsparciem zdrowia.
                        </p>
                        
                        <div class="process-info">
                            <strong>JAK TO DZIAŁA?</strong>
                            <ol>
                                <li>Wybierz zabieg lub konsultację online.</li>
                                <li>Po zakupie odbierz e-mail z KODEM.</li>
                                <li>Zadzwoń do eksperta i umów termin wizyty.</li>
                            </ol>
                        </div>
                    </div>
                    
                    {* LOKALIZACJA *}
                    <div class="location-box">
                        <span class="loc-label"><i class='fa-solid fa-earth-europe'></i> DOSTĘPNOŚĆ USŁUG:</span>
                        <ul class="loc-list">
                            {* 1. ONLINE *}
                            <li class="active-loc online-mode">
                                <i class='fa-solid fa-laptop-medical'></i> 
                                <strong>KONSULTACJE ONLINE</strong> 
                                <span class="loc-fade">(Cała Polska)</span>
                            </li>
                            
                            {* 2. STACJONARNIE *}
                            <li class="active-loc">
                                <i class='fa-solid fa-location-dot'></i> 
                                <strong>Kraków</strong> 
                                <span class="loc-fade loc-small"> (Stacjonarnie / Dojazd)</span>
                            </li>
                        </ul>
                    </div>

                    {* LINK DO LANDING PAGE *}
                    <a href="{$link->getModuleLink('tvcmsstrefazdrowia', 'display')}" class="btn-medical-action">
                        ODKRYJ STREFĘ ZDROWIA
                    </a>
                </div>
            </div>

            {* PRAWA KOLUMNA - SIATKA USŁUG (TERAZ PODLINKOWANA) *}
            <div class="services-grid-col">
                
                {* FIZJOTERAPIA - LINK *}
                <a href="{$link->getModuleLink('tvcmsstrefazdrowia', 'display', ['strona' => 'fizjoterapia'])}" class="service-tile">
                    <div class="tile-icon"><i class='fa-solid fa-person-walking'></i></div>
                    <div class="tile-info">
                        <h3>Fizjoterapia</h3>
                        <span>Rehabilitacja i masaż</span>
                    </div>
                </a>

                {* NATUROPATIA - LINK *}
                <a href="{$link->getModuleLink('tvcmsstrefazdrowia', 'display', ['strona' => 'naturopatia'])}" class="service-tile">
                    <div class="tile-icon"><i class='fa-solid fa-leaf'></i></div>
                    <div class="tile-info">
                        <h3>Naturopatia</h3>
                        <span>Konsultacje holistyczne</span>
                    </div>
                </a>

                {* PORADY & URODA - LINK *}
                <a href="{$link->getModuleLink('tvcmsstrefazdrowia', 'display', ['strona' => 'uroda'])}" class="service-tile">
                    <div class="tile-icon"><i class='fa-solid fa-spa'></i></div>
                    <div class="tile-info">
                        <h3>Porady & Uroda</h3>
                        <span>Fachowa wiedza i zabiegi</span>
                    </div>
                </a>

                {* DIAGNOSTYKA - LINK (Zostawiamy do głównej lub innej podstrony) *}
                <a href="{$link->getModuleLink('tvcmsstrefazdrowia', 'display')}" class="service-tile">
                    <div class="tile-icon"><i class='fa-solid fa-stethoscope'></i></div>
                    <div class="tile-info">
                        <h3>Diagnostyka</h3>
                        <span>Terapie wsparcia</span>
                    </div>
                </a>

            </div>
        </div>

    </div>
</div>
{/strip}