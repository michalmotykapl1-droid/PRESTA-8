{extends file='page.tpl'}

{block name='page_title'}
    <span class="strefa-page-header">Naturopatia i Suplementacja</span>
{/block}

{block name='page_content'}
<div class="strefa-landing-wrapper strefa-naturo-page">
    
    <script>
        var strefaTravelFee = {$strefa_travel_fee|default:200};
    </script>

    <div class="strefa-hero-section sub-hero">
        <div class="hero-text-content">
            <span class="hero-subtitle naturo-subtitle">HOLISTYKA & ZDROWIE</span>
            <h1>Strefa Naturopaty:<br>Równowaga</h1>
            <p>
                Przywracamy zdrowie, szukając przyczyn, a nie tylko łagodząc objawy.
                Oferujemy profesjonalną analizę pierwiastkową, urządzenia wspierające zdrowie oraz wiedzę ekspertów.
            </p>
            <a href="{$link->getModuleLink('tvcmsstrefazdrowia', 'display')}" class="btn-back">
                <i class='fa-solid fa-arrow-left'></i> WRÓĆ DO MENU
            </a>
        </div>
        <div class="hero-visual-decoration">
            <div class="visual-circle naturo-circle small-circle">
                {* SPÓJNA IKONA NATURO *}
                <i class='fa-solid fa-leaf'></i>
                <div class="circle-pulse naturo-pulse"></div>
            </div>
        </div>
    </div>

    <div class="visit-selector-strip">
        <div class="container">
            <div class="selector-content-centered">
                <div class="selector-title naturo-selector-title">
                    <i class='fa-solid fa-sliders'></i>
                    <span>WYBIERZ FORMĘ WIZYTY:</span>
                </div>
                <div class="visit-type-toggle-wrapper">
                    <span class="toggle-label label-stationary active">GABINET / ONLINE</span>
                    <label class="switch naturo-switch">
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
            <h2>Diagnostyka i Wiedza</h2>
            <div class="header-divider naturo-divider"></div>
            <p class="category-intro">Indywidualne plany działania oraz materiały edukacyjne.</p>
        </div>

        <div class="services-grid-row">
            {* Rozpisanie Suplementacji *}
            <div class="service-product-card naturo-card">
                <div class="sp-content">
                    <h3>Rozpisanie Suplementacji / Diety</h3>
                    <div class="sp-meta">
                        <span class="sp-time"><i class='fa-solid fa-file-pen'></i> Plan Indywidualny</span>
                        <span class="sp-price naturo-price dynamic-price" data-base="99">99 zł</span>
                    </div>
                    <p>Spersonalizowany plan kuracji lub diety.</p>
                </div>
                <a href="{$link->getPageLink('cart', true, null, "add=1&id_product=70&token={$static_token}")}" class="btn-add-cart naturo-cart-btn">
                    DO KOSZYKA <i class='fa-solid fa-cart-shopping'></i>
                </a>
            </div>

            {* Książka *}
            <div class="service-product-card naturo-highlight">
                <div class="sp-content">
                    <div class="packet-label naturo-label">BESTSELLER</div>
                    <h3>Książka: "Poznaj Swojego Wroga"</h3>
                    <div class="sp-meta">
                        <span class="sp-time"><i class='fa-solid fa-book-open'></i> Książka / E-book</span>
                        <span class="sp-price naturo-price">49 - 99 zł</span>
                    </div>
                    <p>Kompendium wiedzy Piotra Górszczaka.</p>
                </div>
                <a href="{$link->getPageLink('cart', true, null, "add=1&id_product=71&token={$static_token}")}" class="btn-add-cart naturo-cart-btn filled">
                    DO KOSZYKA <i class='fa-solid fa-cart-shopping'></i>
                </a>
            </div>

            {* Oczyszczanie w 90 dni *}
            <div class="service-product-card naturo-card">
                <div class="sp-content">
                    <h3>Oczyszczanie w 90 Dni (Kurs)</h3>
                    <div class="sp-meta">
                        <span class="sp-time"><i class='fa-solid fa-graduation-cap'></i> Program Online</span>
                        <span class="sp-price naturo-price dynamic-price" data-base="400">400 zł</span>
                    </div>
                    <p>Pełna kuracja z zaleceniami: Detox.</p>
                </div>
                <a href="{$link->getPageLink('cart', true, null, "add=1&id_product=72&token={$static_token}")}" class="btn-add-cart naturo-cart-btn">
                    ZOBACZ OPCJE <i class='fa-solid fa-arrow-right'></i>
                </a>
            </div>
        </div>

        <div class="services-grid-row">
            {* Konsultacja Wstępna *}
            <div class="service-product-card naturo-card">
                <div class="sp-content">
                    <h3>Konsultacja Naturopatyczna</h3>
                    <div class="sp-meta">
                        <span class="sp-time"><i class='fa-regular fa-clock'></i> 60 min</span>
                        <span class="sp-price naturo-price dynamic-price" data-base="250">250 zł</span>
                    </div>
                    <p>Pełny wywiad zdrowotny i analiza wyników.</p>
                </div>
                <a href="{$link->getPageLink('cart', true, null, "add=1&id_product=50&token={$static_token}")}" class="btn-add-cart naturo-cart-btn">
                    DO KOSZYKA <i class='fa-solid fa-cart-shopping'></i>
                </a>
            </div>

            {* Konsultacja Online Video *}
            <div class="service-product-card naturo-card">
                <div class="sp-content">
                    <h3>Konsultacja Online (Video)</h3>
                    <div class="sp-meta">
                        <span class="sp-time"><i class='fa-solid fa-video'></i> 30 min</span>
                        <span class="sp-price naturo-price dynamic-price" data-base="200">200 zł</span>
                    </div>
                    <p>Wygodna e-wizyta bez wychodzenia z domu.</p>
                </div>
                <a href="{$link->getPageLink('cart', true, null, "add=1&id_product=56&token={$static_token}")}" class="btn-add-cart naturo-cart-btn">
                    DO KOSZYKA <i class='fa-solid fa-cart-shopping'></i>
                </a>
            </div>

            {* Analiza EHA *}
            <div class="service-product-card naturo-highlight">
                <div class="sp-content">
                    <div class="packet-label naturo-label">DIAGNOSTYKA PRECYZYJNA</div>
                    <h3>Analiza Pierwiastkowa Włosa (EHA)</h3>
                    <div class="sp-meta">
                        <span class="sp-time"><i class='fa-solid fa-microscope'></i> Badanie + Opis</span>
                        <span class="sp-price naturo-price dynamic-price" data-base="450">450 zł</span>
                    </div>
                    <p>Najdokładniejsze badanie poziomu minerałów.</p>
                </div>
                <a href="{$link->getPageLink('cart', true, null, "add=1&id_product=51&token={$static_token}")}" class="btn-add-cart naturo-cart-btn filled">
                    DO KOSZYKA <i class='fa-solid fa-cart-shopping'></i>
                </a>
            </div>

            {* Konsultacja Kontrolna *}
            <div class="service-product-card naturo-card">
                <div class="sp-content">
                    <h3>Wizyta Kontrolna / Korekta Planu</h3>
                    <div class="sp-meta">
                        <span class="sp-time"><i class='fa-regular fa-clock'></i> 30 min</span>
                        <span class="sp-price naturo-price dynamic-price" data-base="150">150 zł</span>
                    </div>
                    <p>Monitorowanie postępów.</p>
                </div>
                <a href="{$link->getPageLink('cart', true, null, "add=1&id_product=52&token={$static_token}")}" class="btn-add-cart naturo-cart-btn">
                    DO KOSZYKA <i class='fa-solid fa-cart-shopping'></i>
                </a>
            </div>
        </div>

        <div class="category-header">
            <h2>Dietoterapia i Programy Zdrowotne</h2>
            <div class="header-divider naturo-divider"></div>
        </div>

        <div class="services-grid-row">
            {* Plan Żywieniowy 7 dni *}
            <div class="service-product-card naturo-card">
                <div class="sp-content">
                    <h3>Indywidualny Plan Żywieniowy (7 dni)</h3>
                    <div class="sp-meta">
                        <span class="sp-time"><i class='fa-solid fa-utensils'></i> Jadłospis</span>
                        <span class="sp-price naturo-price dynamic-price" data-base="300">300 zł</span>
                    </div>
                    <p>Jadłospis ułożony pod Twoje wyniki badań.</p>
                </div>
                <a href="{$link->getPageLink('cart', true, null, "add=1&id_product=53&token={$static_token}")}" class="btn-add-cart naturo-cart-btn">
                    DO KOSZYKA <i class='fa-solid fa-cart-shopping'></i>
                </a>
            </div>

            {* Pakiet Jelita *}
            <div class="service-product-card naturo-highlight">
                <div class="sp-content">
                    <div class="packet-label naturo-label">PROGRAM JELITOWY</div>
                    <h3>Pakiet "Zdrowe Jelita"</h3>
                    <div class="sp-meta">
                        <span class="sp-time"><i class='fa-solid fa-repeat'></i> 3 mies. prowadzenia</span>
                        <span class="sp-price naturo-price dynamic-price" data-base="1200">1 200 zł</span>
                    </div>
                    <p>Kompleksowy program naprawczy dla jelit.</p>
                </div>
                <a href="{$link->getPageLink('cart', true, null, "add=1&id_product=54&token={$static_token}")}" class="btn-add-cart naturo-cart-btn filled">
                    DO KOSZYKA <i class='fa-solid fa-cart-shopping'></i>
                </a>
            </div>

             {* Ziołolecznictwo *}
            <div class="service-product-card naturo-card">
                <div class="sp-content">
                    <h3>Dobór Mieszanek Ziołowych</h3>
                    <div class="sp-meta">
                        <span class="sp-time"><i class='fa-solid fa-mortar-pestle'></i> Receptura</span>
                        <span class="sp-price naturo-price dynamic-price" data-base="200">200 zł</span>
                    </div>
                    <p>Opracowanie indywidualnej receptury ziołowej.</p>
                </div>
                <a href="{$link->getPageLink('cart', true, null, "add=1&id_product=55&token={$static_token}")}" class="btn-add-cart naturo-cart-btn">
                    DO KOSZYKA <i class='fa-solid fa-cart-shopping'></i>
                </a>
            </div>
        </div>

        <div class="category-header" style="margin-top: 60px;">
            <h2>Urządzenia i Preparaty</h2>
            <div class="header-divider naturo-divider"></div>
        </div>

        <div class="services-grid-row">
             {* Pranax10 *}
             <div class="service-product-card naturo-card">
                <div class="product-icon-placeholder">
                    <i class='fa-solid fa-droplet'></i>
                </div>
                <div class="sp-content">
                    <h3>Pranax10 QuantumFlow®</h3>
                    <div class="sp-meta">
                        <span class="sp-time"><i class='fa-solid fa-screwdriver-wrench'></i> Z montażem</span>
                        <span class="sp-price naturo-price">od 5 500 zł</span>
                    </div>
                    <p>Holistyczny system transformacji wody.</p>
                </div>
                <a href="{$link->getPageLink('cart', true, null, "add=1&id_product=73&token={$static_token}")}" class="btn-add-cart naturo-cart-btn">
                    ZOBACZ OPCJE <i class='fa-solid fa-eye'></i>
                </a>
            </div>

            {* Dr Optimus *}
             <div class="service-product-card naturo-card">
                <div class="product-icon-placeholder">
                    <i class='fa-solid fa-satellite-dish'></i>
                </div>
                <div class="sp-content">
                    <h3>Dr Optimus Bioresonance®</h3>
                    <div class="sp-meta">
                        <span class="sp-time"><i class='fa-solid fa-wave-square'></i> Generator 1 MHz</span>
                        <span class="sp-price naturo-price">6 900 zł</span>
                    </div>
                    <p>Zaawansowany generator biorezonansowy.</p>
                </div>
                <a href="{$link->getPageLink('cart', true, null, "add=1&id_product=74&token={$static_token}")}" class="btn-add-cart naturo-cart-btn">
                    DO KOSZYKA <i class='fa-solid fa-cart-shopping'></i>
                </a>
            </div>

            {* Zeolit *}
             <div class="service-product-card naturo-card">
                <div class="product-icon-placeholder">
                    <i class='fa-solid fa-prescription-bottle-medical'></i>
                </div>
                <div class="sp-content">
                    <h3>Zeolit Klinoptylolit Medyczny</h3>
                    <div class="sp-meta">
                        <span class="sp-time"><i class='fa-solid fa-box-open'></i> 200g PMA</span>
                        <span class="sp-price naturo-price">195 zł</span>
                    </div>
                    <p>Skuteczne usuwanie toksyn.</p>
                </div>
                <a href="{$link->getPageLink('cart', true, null, "add=1&id_product=75&token={$static_token}")}" class="btn-add-cart naturo-cart-btn">
                    DO KOSZYKA <i class='fa-solid fa-cart-shopping'></i>
                </a>
            </div>

            {* Strukturyzator *}
             <div class="service-product-card naturo-card">
                <div class="product-icon-placeholder">
                    <i class='fa-solid fa-bottle-water'></i>
                </div>
                <div class="sp-content">
                    <h3>Przenośny Strukturyzator Wody</h3>
                    <div class="sp-meta">
                        <span class="sp-time"><i class='fa-solid fa-star'></i> Woda ożywiona</span>
                        <span class="sp-price naturo-price">269,99 zł</span>
                    </div>
                    <p>Zmienia strukturę wody.</p>
                </div>
                <a href="{$link->getPageLink('cart', true, null, "add=1&id_product=76&token={$static_token}")}" class="btn-add-cart naturo-cart-btn">
                    DO KOSZYKA <i class='fa-solid fa-cart-shopping'></i>
                </a>
            </div>

            {* Podpiętki *}
             <div class="service-product-card naturo-card">
                <div class="product-icon-placeholder">
                    <i class='fa-solid fa-shoe-prints'></i>
                </div>
                <div class="sp-content">
                    <h3>Podpiętki Magnetyczne</h3>
                    <div class="sp-meta">
                        <span class="sp-time"><i class='fa-solid fa-wheelchair'></i> Ortopedyczne</span>
                        <span class="sp-price naturo-price">155 zł</span>
                    </div>
                    <p>Wsparcie dla stóp.</p>
                </div>
                <a href="{$link->getPageLink('cart', true, null, "add=1&id_product=77&token={$static_token}")}" class="btn-add-cart naturo-cart-btn">
                    DO KOSZYKA <i class='fa-solid fa-cart-shopping'></i>
                </a>
            </div>

            {* Biżuteria *}
             <div class="service-product-card naturo-card">
                <div class="product-icon-placeholder">
                    <i class='fa-solid fa-gem'></i>
                </div>
                <div class="sp-content">
                    <h3>Broszka / Bransoletka Magnetyczna</h3>
                    <div class="sp-meta">
                        <span class="sp-time"><i class='fa-solid fa-wand-magic-sparkles'></i> Pozłacana/Miedź</span>
                        <span class="sp-price naturo-price">od 255 zł</span>
                    </div>
                    <p>Elegancka biżuteria wspierająca zdrowie.</p>
                </div>
                <a href="{$link->getPageLink('cart', true, null, "add=1&id_product=78&token={$static_token}")}" class="btn-add-cart naturo-cart-btn">
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
                        <img src="{$urls.base_url}modules/tvcmsstrefazdrowia/views/img/natur.webp" alt="Piotr Górszczak">
                    </div>
                </div>
                <div class="col-md-7">
                    <div class="ds-content" style="padding-left: 40px; align-items: flex-start; text-align: left;">
                        <span class="ds-tag naturo-tag">TWÓJ NATUROPATA</span>
                        <h2 style="margin-bottom: 15px;">mgr Piotr Górszczak</h2>
                        <p class="ds-intro">
                            Specjalista medycyny naturalnej i dietetyki klinicznej.
                            Pomagam pacjentom odzyskać energię życiową poprzez przywrócenie równowagi biochemicznej organizmu.
                        </p>
                        <div class="ds-features">
                            <div class="feature-item"><i class='fa-solid fa-microscope' style="color: #43a047;"></i> <span>Specjalista Analizy Pierwiastkowej</span></div>
                            <div class="feature-item"><i class='fa-solid fa-leaf' style="color: #43a047;"></i> <span>Ekspert Ziołolecznictwa</span></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>
{/block}