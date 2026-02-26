{extends file='page.tpl'}

{block name='page_title'}
    <span class="strefa-page-header">Fizjoterapia i Rehabilitacja</span>
{/block}

{block name='page_content'}
<div class="strefa-landing-wrapper strefa-physio-page">
    
    <script>
        var strefaTravelFee = {$strefa_travel_fee|default:200};
    </script>

    <div class="strefa-hero-section sub-hero">
        <div class="hero-text-content">
            <span class="hero-subtitle">REHABILITACJA & RUCH</span>
            <h1>Centrum Zdrowia:<br>Fizjoterapia</h1>
            
            <p style="margin-top: 25px;">
                Profesjonalna diagnostyka i leczenie bólu.
                Specjalizujemy się w terapii manualnej, rehabilitacji ortopedycznej, fali uderzeniowej oraz masażach leczniczych.
            </p>
            
            <a href="{$link->getModuleLink('tvcmsstrefazdrowia', 'display')}" class="btn-back">
                <i class='fa-solid fa-arrow-left'></i> WRÓĆ DO MENU
            </a>
        </div>
        <div class="hero-visual-decoration">
            <div class="visual-circle physio-circle small-circle">
                {* SPÓJNA IKONA FIZJO *}
                <i class='fa-solid fa-person-walking'></i>
                <div class="circle-pulse"></div>
            </div>
        </div>
    </div>

    {* PASEK WYBORU WIZYTY *}
    <div class="visit-selector-strip">
        <div class="container">
            <div class="selector-content-centered">
                <div class="selector-title">
                    <i class='fa-solid fa-sliders'></i>
                    <span>WYBIERZ FORMĘ WIZYTY:</span>
                </div>
                <div class="visit-type-toggle-wrapper">
                    <span class="toggle-label label-stationary active">W GABINECIE</span>
                    <label class="switch">
                        <input type="checkbox" id="visitTypeToggle">
                        <span class="slider round"></span>
                    </label>
                    <span class="toggle-label label-mobile">DOJAZD DO DOMU</span>
                </div>
            </div>
            <p class="selector-info">Ceny zostaną automatycznie zaktualizowane po zmianie opcji.</p>
        </div>
    </div>

    <div class="container">
        
        <div class="category-header">
            <h2>Diagnostyka i Konsultacje</h2>
            <div class="header-divider"></div>
            <p class="category-intro">Pierwszy krok do zdrowia.</p>
        </div>

        <div class="services-grid-row">
            {* Konsultacja FIZJO 60 min *}
            <div class="service-product-card physio-card">
                <div class="sp-content">
                    <h3>Konsultacja Fizjoterapeutyczna</h3>
                    <div class="sp-meta">
                        <span class="sp-time"><i class='fa-regular fa-clock'></i> 60 min</span>
                        <span class="sp-price physio-price dynamic-price" data-base="250">250 zł</span>
                    </div>
                    <p>Szczegółowy wywiad i badanie funkcjonalne.</p>
                </div>
                <a href="{$link->getPageLink('cart', true, null, "add=1&id_product=1&token={$static_token}")}" class="btn-add-cart physio-cart-btn">
                    DO KOSZYKA <i class='fa-solid fa-cart-shopping'></i>
                </a>
            </div>

            {* Konsultacja 30 min *}
            <div class="service-product-card physio-card">
                <div class="sp-content">
                    <h3>Konsultacja / Wizyta Krótka</h3>
                    <div class="sp-meta">
                        <span class="sp-time"><i class='fa-regular fa-clock'></i> 30 min</span>
                        <span class="sp-price physio-price dynamic-price" data-base="180">180 zł</span>
                    </div>
                    <p>Szybka interwencja bólowa.</p>
                </div>
                <a href="{$link->getPageLink('cart', true, null, "add=1&id_product=2&token={$static_token}")}" class="btn-add-cart physio-cart-btn">
                    DO KOSZYKA <i class='fa-solid fa-cart-shopping'></i>
                </a>
            </div>

            {* Konsultacja z Podobarografią *}
            <div class="service-product-card physio-highlight">
                <div class="sp-content">
                    <div class="packet-label physio-label">DIAGNOSTYKA STÓP</div>
                    <h3>Konsultacja z Podobarografią</h3>
                    <div class="sp-meta">
                        <span class="sp-time"><i class='fa-regular fa-clock'></i> 60 min</span>
                        <span class="sp-price physio-price dynamic-price" data-base="250">250 zł</span>
                    </div>
                    <p>Komputerowe badanie stóp i chodu.</p>
                </div>
                <a href="{$link->getPageLink('cart', true, null, "add=1&id_product=3&token={$static_token}")}" class="btn-add-cart physio-cart-btn filled">
                    DO KOSZYKA <i class='fa-solid fa-cart-shopping'></i>
                </a>
            </div>

            {* Konsultacja Online Video *}
            <div class="service-product-card physio-card">
                <div class="sp-content">
                    <h3>Konsultacja Online Video</h3>
                    <div class="sp-meta">
                        <span class="sp-time"><i class='fa-solid fa-video'></i> 30 min</span>
                        <span class="sp-price physio-price dynamic-price" data-base="200">200 zł</span>
                    </div>
                    <p>Wygodna e-wizyta bez wychodzenia z domu.</p>
                </div>
                <a href="{$link->getPageLink('cart', true, null, "add=1&id_product=17&token={$static_token}")}" class="btn-add-cart physio-cart-btn">
                    DO KOSZYKA <i class='fa-solid fa-cart-shopping'></i>
                </a>
            </div>
        </div>

        <div class="category-header">
            <h2>Terapie Manualne i Specjalistyczne</h2>
            <div class="header-divider"></div>
        </div>

        <div class="services-grid-row">
            {* Konsultacja z masażem 55min *}
            <div class="service-product-card physio-card">
                <div class="sp-content">
                    <h3>Fizjoterapia z Masażem Leczniczym</h3>
                    <div class="sp-meta">
                        <span class="sp-time"><i class='fa-regular fa-clock'></i> 55 min</span>
                        <span class="sp-price physio-price dynamic-price" data-base="250">250 zł</span>
                    </div>
                    <p>Połączenie terapii manualnej z intensywnym masażem.</p>
                </div>
                <a href="{$link->getPageLink('cart', true, null, "add=1&id_product=6&token={$static_token}")}" class="btn-add-cart physio-cart-btn">
                    DO KOSZYKA <i class='fa-solid fa-cart-shopping'></i>
                </a>
            </div>

            {* Rozszerzona Terapia *}
            <div class="service-product-card physio-highlight">
                <div class="sp-content">
                    <div class="packet-label physio-label">ROZSZERZONA WIZYTA</div>
                    <h3>Rozszerzona Terapia z Masażem</h3>
                    <div class="sp-meta">
                        <span class="sp-time"><i class='fa-regular fa-clock'></i> 80 min</span>
                        <span class="sp-price physio-price dynamic-price" data-base="320">320 zł</span>
                    </div>
                    <p>Długa sesja terapeutyczna.</p>
                </div>
                <a href="{$link->getPageLink('cart', true, null, "add=1&id_product=7&token={$static_token}")}" class="btn-add-cart physio-cart-btn filled">
                    DO KOSZYKA <i class='fa-solid fa-cart-shopping'></i>
                </a>
            </div>

            {* Fala uderzeniowa (Zabieg) *}
            <div class="service-product-card physio-card">
                <div class="sp-content">
                    <h3>Zabieg Falą Uderzeniową</h3>
                    <div class="sp-meta">
                        <span class="sp-time"><i class='fa-regular fa-clock'></i> 15 min</span>
                        <span class="sp-price physio-price dynamic-price" data-base="150">150 zł</span>
                    </div>
                    <p>Skuteczna terapia na ostrogi i zwapnienia.</p>
                </div>
                <a href="{$link->getPageLink('cart', true, null, "add=1&id_product=8&token={$static_token}")}" class="btn-add-cart physio-cart-btn">
                    DO KOSZYKA <i class='fa-solid fa-cart-shopping'></i>
                </a>
            </div>

            {* Fala uderzeniowa + Wizyta *}
            <div class="service-product-card physio-card">
                <div class="sp-content">
                    <h3>Wizyta + Fala Uderzeniowa</h3>
                    <div class="sp-meta">
                        <span class="sp-time"><i class='fa-regular fa-clock'></i> 55 min</span>
                        <span class="sp-price physio-price dynamic-price" data-base="300">300 zł</span>
                    </div>
                    <p>Kompleksowa wizyta fizjoterapeutyczna + fala.</p>
                </div>
                <a href="{$link->getPageLink('cart', true, null, "add=1&id_product=9&token={$static_token}")}" class="btn-add-cart physio-cart-btn">
                    DO KOSZYKA <i class='fa-solid fa-cart-shopping'></i>
                </a>
            </div>

            {* Terapia Czaszkowo-Krzyżowa *}
            <div class="service-product-card physio-card">
                <div class="sp-content">
                    <h3>Terapia Kranio-Sakralna</h3>
                    <div class="sp-meta">
                        <span class="sp-time"><i class='fa-regular fa-clock'></i> 50 min</span>
                        <span class="sp-price physio-price dynamic-price" data-base="250">250 zł</span>
                    </div>
                    <p>Delikatna terapia osteopatyczna.</p>
                </div>
                <a href="{$link->getPageLink('cart', true, null, "add=1&id_product=10&token={$static_token}")}" class="btn-add-cart physio-cart-btn">
                    DO KOSZYKA <i class='fa-solid fa-cart-shopping'></i>
                </a>
            </div>
        </div>

        <div class="category-header">
            <h2>Masaże Lecznicze i Relaksacyjne</h2>
            <div class="header-divider"></div>
        </div>

        <div class="services-grid-row">
            {* Masaż Leczniczy *}
            <div class="service-product-card physio-card">
                <div class="sp-content">
                    <h3>Masaż Leczniczy</h3>
                    <div class="sp-meta">
                        <span class="sp-time"><i class='fa-regular fa-clock'></i> 45 min</span>
                        <span class="sp-price physio-price dynamic-price" data-base="250">250 zł</span>
                    </div>
                    <p>Intensywny masaż problematycznych partii.</p>
                </div>
                <a href="{$link->getPageLink('cart', true, null, "add=1&id_product=11&token={$static_token}")}" class="btn-add-cart physio-cart-btn">
                    DO KOSZYKA <i class='fa-solid fa-cart-shopping'></i>
                </a>
            </div>

            {* Masaż Fizjoterapeutyczny *}
            <div class="service-product-card physio-card">
                <div class="sp-content">
                    <h3>Masaż Fizjoterapeutyczny</h3>
                    <div class="sp-meta">
                        <span class="sp-time"><i class='fa-regular fa-clock'></i> 50 min</span>
                        <span class="sp-price physio-price dynamic-price" data-base="250">250 zł</span>
                    </div>
                    <p>Manualne opracowanie tkanek miękkich.</p>
                </div>
                <a href="{$link->getPageLink('cart', true, null, "add=1&id_product=12&token={$static_token}")}" class="btn-add-cart physio-cart-btn">
                    DO KOSZYKA <i class='fa-solid fa-cart-shopping'></i>
                </a>
            </div>

            {* Masaż Sportowy *}
            <div class="service-product-card physio-card">
                <div class="sp-content">
                    <h3>Masaż Sportowy</h3>
                    <div class="sp-meta">
                        <span class="sp-time"><i class='fa-regular fa-clock'></i> 50 min</span>
                        <span class="sp-price physio-price dynamic-price" data-base="280">280 zł</span>
                    </div>
                    <p>Mocny masaż regeneracyjny.</p>
                </div>
                <a href="{$link->getPageLink('cart', true, null, "add=1&id_product=13&token={$static_token}")}" class="btn-add-cart physio-cart-btn">
                    DO KOSZYKA <i class='fa-solid fa-cart-shopping'></i>
                </a>
            </div>

            {* Masaż Powięziowy *}
            <div class="service-product-card physio-card">
                <div class="sp-content">
                    <h3>Masaż Powięziowy</h3>
                    <div class="sp-meta">
                        <span class="sp-time"><i class='fa-regular fa-clock'></i> 60 min</span>
                        <span class="sp-price physio-price dynamic-price" data-base="200">200 zł</span>
                    </div>
                    <p>Głęboka praca na powięziach.</p>
                </div>
                <a href="{$link->getPageLink('cart', true, null, "add=1&id_product=14&token={$static_token}")}" class="btn-add-cart physio-cart-btn">
                    DO KOSZYKA <i class='fa-solid fa-cart-shopping'></i>
                </a>
            </div>

            {* Masaż Relaksacyjny *}
            <div class="service-product-card physio-card">
                <div class="sp-content">
                    <h3>Masaż Relaksacyjny</h3>
                    <div class="sp-meta">
                        <span class="sp-time"><i class='fa-regular fa-clock'></i> 60 min</span>
                        <span class="sp-price physio-price dynamic-price" data-base="230">230 zł</span>
                    </div>
                    <p>Odprężający masaż całego ciała.</p>
                </div>
                <a href="{$link->getPageLink('cart', true, null, "add=1&id_product=15&token={$static_token}")}" class="btn-add-cart physio-cart-btn">
                    DO KOSZYKA <i class='fa-solid fa-cart-shopping'></i>
                </a>
            </div>

            {* Masaż Kobido *}
            <div class="service-product-card physio-card">
                <div class="sp-content">
                    <h3>Autorski Masaż Kobido</h3>
                    <div class="sp-meta">
                        <span class="sp-time"><i class='fa-regular fa-clock'></i> 60 min</span>
                        <span class="sp-price physio-price dynamic-price" data-base="300">300 zł</span>
                    </div>
                    <p>Japoński lifting twarzy bez skalpela.</p>
                </div>
                <a href="{$link->getPageLink('cart', true, null, "add=1&id_product=16&token={$static_token}")}" class="btn-add-cart physio-cart-btn">
                    DO KOSZYKA <i class='fa-solid fa-cart-shopping'></i>
                </a>
            </div>
        </div>

        <div class="category-header">
            <h2>Pakiety i Serie Zabiegowe</h2>
            <div class="header-divider"></div>
        </div>

        <div class="services-grid-row">
            {* PAKIET 5 *}
            <div class="service-product-card physio-highlight">
                <div class="sp-content">
                    <div class="packet-label physio-label">FIZJOTERAPIA</div>
                    <h3>Pakiet 5 Wizyt (50 min)</h3>
                    <div class="sp-meta">
                        <span class="sp-time"><i class='fa-solid fa-repeat'></i> 5 zabiegów</span>
                        <span class="sp-price physio-price dynamic-price" data-base="1150">1 150 zł</span>
                    </div>
                    <p>Cykl terapeutyczny. Oszczędzasz 100 zł.</p>
                </div>
                <a href="{$link->getPageLink('cart', true, null, "add=1&id_product=4&token={$static_token}")}" class="btn-add-cart physio-cart-btn filled">
                    DO KOSZYKA <i class='fa-solid fa-cart-shopping'></i>
                </a>
            </div>

            {* PAKIET 10 *}
            <div class="service-product-card physio-highlight">
                <div class="sp-content">
                    <div class="packet-label physio-label">FIZJOTERAPIA</div>
                    <h3>Pakiet 10 Wizyt (50 min)</h3>
                    <div class="sp-meta">
                        <span class="sp-time"><i class='fa-solid fa-repeat'></i> 10 zabiegów</span>
                        <span class="sp-price physio-price dynamic-price" data-base="2200">2 200 zł</span>
                    </div>
                    <p>Pełna rehabilitacja. Oszczędzasz 300 zł.</p>
                </div>
                <a href="{$link->getPageLink('cart', true, null, "add=1&id_product=5&token={$static_token}")}" class="btn-add-cart physio-cart-btn filled">
                    DO KOSZYKA <i class='fa-solid fa-cart-shopping'></i>
                </a>
            </div>

            {* PAKIET FALA 4 *}
            <div class="service-product-card physio-highlight">
                <div class="sp-content">
                    <div class="packet-label physio-label">FALA UDERZENIOWA</div>
                    <h3>Pakiet 4 Zabiegów (Brzuch)</h3>
                    <div class="sp-meta">
                        <span class="sp-time"><i class='fa-solid fa-repeat'></i> 4 zabiegi</span>
                        <span class="sp-price physio-price dynamic-price" data-base="900">900 zł</span>
                    </div>
                    <p>Oszczędzasz 100 zł.</p>
                </div>
                <a href="{$link->getPageLink('cart', true, null, "add=1&id_product=18&token={$static_token}")}" class="btn-add-cart physio-cart-btn filled">
                    DO KOSZYKA <i class='fa-solid fa-cart-shopping'></i>
                </a>
            </div>

            {* PAKIET FALA 6 *}
            <div class="service-product-card physio-highlight">
                <div class="sp-content">
                    <div class="packet-label physio-label">FALA UDERZENIOWA</div>
                    <h3>Pakiet 6 Zabiegów (Brzuch)</h3>
                    <div class="sp-meta">
                        <span class="sp-time"><i class='fa-solid fa-repeat'></i> 6 zabiegów</span>
                        <span class="sp-price physio-price dynamic-price" data-base="1280">1 280 zł</span>
                    </div>
                    <p>Oszczędzasz 220 zł.</p>
                </div>
                <a href="{$link->getPageLink('cart', true, null, "add=1&id_product=19&token={$static_token}")}" class="btn-add-cart physio-cart-btn filled">
                    DO KOSZYKA <i class='fa-solid fa-cart-shopping'></i>
                </a>
            </div>
        </div>
    </div>

    <div class="strefa-team-section" style="background: #fff; border-top: 1px solid #eee; margin-top: 60px;">
        <div class="container">
            <div class="row" style="align-items: center;">
                <div class="col-md-5">
                    <div class="team-photo" style="border-radius: 20px;">
                        <img src="{$urls.base_url}modules/tvcmsstrefazdrowia/views/img/fizjo.webp" alt="Ewelina Szabłowska">
                    </div>
                </div>
                <div class="col-md-7">
                    <div class="ds-content" style="padding-left: 40px; align-items: flex-start; text-align: left;">
                        <span class="ds-tag physio-tag">TWOJA FIZJOTERAPEUTKA</span>
                        <h2 style="margin-bottom: 15px;">mgr Ewelina Szabłowska</h2>
                        <p class="ds-intro">
                            Absolwentka AWF w Krakowie. Specjalistka w dziedzinie fizjoterapii i kosmetologii. 
                            W swojej pracy łączę wiedzę medyczną z holistycznym podejściem do ciała.
                        </p>
                        <div class="ds-features">
                            <div class="feature-item"><i class='fa-solid fa-user-graduate' style="color: #008b8b;"></i> <span>Dyplomowany Magister Fizjoterapii</span></div>
                            <div class="feature-item"><i class='fa-solid fa-certificate' style="color: #008b8b;"></i> <span>Specjalista Kosmetologii Estetycznej</span></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>
{/block}