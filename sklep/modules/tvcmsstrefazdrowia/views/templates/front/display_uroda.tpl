{extends file='page.tpl'}

{block name='page_title'}
    <span class="strefa-page-header">Strefa Urody i Kosmetologia</span>
{/block}

{block name='page_content'}
<div class="strefa-landing-wrapper strefa-beauty-page">
    
    <script>
        var strefaTravelFee = {$strefa_travel_fee|default:200};
    </script>

    <div class="strefa-hero-section sub-hero">
        <div class="hero-text-content">
            <span class="hero-subtitle beauty-subtitle">PIĘKNO & RELAKS</span>
            <h1>Strefa Urody:<br>Kosmetologia</h1>
            <p>
                Kompleksowe podejście do piękna. Łączymy zaawansowane technologie Hi-Tech (Laser, RF, Endermologia) z autorskimi terapiami manualnymi.
            </p>
            <a href="{$link->getModuleLink('tvcmsstrefazdrowia', 'display')}" class="btn-back">
                <i class='fa-solid fa-arrow-left'></i> WRÓĆ DO MENU
            </a>
        </div>
        <div class="hero-visual-decoration">
            <div class="visual-circle beauty-circle small-circle">
                {* SPÓJNA IKONA URODA *}
                <i class='fa-solid fa-spa'></i>
                <div class="circle-pulse beauty-pulse"></div>
            </div>
        </div>
    </div>

    <div class="visit-selector-strip">
        <div class="container">
            <div class="selector-content-centered">
                <div class="selector-title beauty-selector-title">
                    <i class='fa-solid fa-sliders'></i>
                    <span>WYBIERZ FORMĘ WIZYTY:</span>
                </div>
                <div class="visit-type-toggle-wrapper">
                    <span class="toggle-label label-stationary active">W GABINECIE</span>
                    <label class="switch beauty-switch">
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
            <h2>Pielęgnacja Twarzy i Anti-Aging</h2>
            <div class="header-divider beauty-divider"></div>
            <p class="category-intro">Zabiegi oczyszczające, rewitalizujące i przebudowujące skórę.</p>
        </div>

        <div class="services-grid-row">
            {* Oczyszczanie Wodorowe *}
            <div class="service-product-card beauty-card">
                <div class="sp-content">
                    <h3>Oczyszczanie Wodorowe</h3>
                    <div class="sp-meta">
                        <span class="sp-time"><i class='fa-regular fa-clock'></i> 50 min</span>
                        <span class="sp-price beauty-price dynamic-price" data-base="350">350 zł</span>
                    </div>
                    <p>Wieloetapowe oczyszczanie skóry aktywnym wodorem.</p>
                    <div class="ds-treatments-grid small-grid">
                         <span class="treatment-tag">Peeling</span>
                         <span class="treatment-tag">Infuzja</span>
                         <span class="treatment-tag">Maska</span>
                    </div>
                </div>
                <a href="{$link->getPageLink('cart', true, null, "add=1&id_product=30&token={$static_token}")}" class="btn-add-cart beauty-cart-btn">
                    DO KOSZYKA <i class='fa-solid fa-cart-shopping'></i>
                </a>
            </div>

            {* Dermapen *}
            <div class="service-product-card beauty-highlight">
                <div class="sp-content">
                    <div class="packet-label beauty-label">HIT ZABIEGOWY</div>
                    <h3>Mezoterapia Dermapen 4™</h3>
                    <div class="sp-meta">
                        <span class="sp-time"><i class='fa-regular fa-clock'></i> 40 min</span>
                        <span class="sp-price beauty-price dynamic-price" data-base="850">850 zł</span>
                    </div>
                    <p>Mikroigłowa przebudowa skóry. Redukcja blizn i zmarszczek.</p>
                </div>
                <a href="{$link->getPageLink('cart', true, null, "add=1&id_product=31&token={$static_token}")}" class="btn-add-cart beauty-cart-btn filled">
                    DO KOSZYKA <i class='fa-solid fa-cart-shopping'></i>
                </a>
            </div>

            {* Geneo *}
            <div class="service-product-card beauty-card">
                <div class="sp-content">
                    <h3>Geneo Pollogen (Twarz+Szyja)</h3>
                    <div class="sp-meta">
                        <span class="sp-time"><i class='fa-regular fa-clock'></i> 50 min</span>
                        <span class="sp-price beauty-price dynamic-price" data-base="400">400 zł</span>
                    </div>
                    <p>Zabieg 3w1: Złuszczanie, Wchłanianie i Dotlenianie.</p>
                </div>
                <a href="{$link->getPageLink('cart', true, null, "add=1&id_product=32&token={$static_token}")}" class="btn-add-cart beauty-cart-btn">
                    DO KOSZYKA <i class='fa-solid fa-cart-shopping'></i>
                </a>
            </div>

            {* Peelingi *}
            <div class="service-product-card beauty-card">
                <div class="sp-content">
                    <h3>Peelingi Chemiczne (Kwasy)</h3>
                    <div class="sp-meta">
                        <span class="sp-time"><i class='fa-regular fa-clock'></i> 45 min</span>
                        <span class="sp-price beauty-price dynamic-price" data-base="300">od 300 zł</span>
                    </div>
                    <p>Terapie kwasowe (Retix C, PQAge).</p>
                </div>
                <a href="{$link->getPageLink('cart', true, null, "add=1&id_product=33&token={$static_token}")}" class="btn-add-cart beauty-cart-btn">
                    DO KOSZYKA <i class='fa-solid fa-cart-shopping'></i>
                </a>
            </div>

             {* Fala Radiowa *}
             <div class="service-product-card beauty-card">
                <div class="sp-content">
                    <h3>Fala Radiowa RF (Twarz)</h3>
                    <div class="sp-meta">
                        <span class="sp-time"><i class='fa-regular fa-clock'></i> 60 min</span>
                        <span class="sp-price beauty-price dynamic-price" data-base="670">670 zł</span>
                    </div>
                    <p>Accent Prime. Termolifting skóry.</p>
                </div>
                <a href="{$link->getPageLink('cart', true, null, "add=1&id_product=34&token={$static_token}")}" class="btn-add-cart beauty-cart-btn">
                    DO KOSZYKA <i class='fa-solid fa-cart-shopping'></i>
                </a>
            </div>
        </div>

        <div class="category-header">
            <h2>Masaże Twarzy i Manualne Odmładzanie</h2>
            <div class="header-divider beauty-divider"></div>
        </div>

        <div class="services-grid-row">
            {* Kobido *}
            <div class="service-product-card beauty-highlight">
                <div class="sp-content">
                    <div class="packet-label beauty-label">LIFTING BEZ SKALPELA</div>
                    <h3>Autorski Masaż Kobido</h3>
                    <div class="sp-meta">
                        <span class="sp-time"><i class='fa-regular fa-clock'></i> 60 min</span>
                        <span class="sp-price beauty-price dynamic-price" data-base="270">270 zł</span>
                    </div>
                    <p>Intensywny masaż liftingujący.</p>
                </div>
                <a href="{$link->getPageLink('cart', true, null, "add=1&id_product=35&token={$static_token}")}" class="btn-add-cart beauty-cart-btn filled">
                    DO KOSZYKA <i class='fa-solid fa-cart-shopping'></i>
                </a>
            </div>

            {* Kobido z Maską *}
            <div class="service-product-card beauty-card">
                <div class="sp-content">
                    <h3>Kobido + Maska Pielęgnacyjna</h3>
                    <div class="sp-meta">
                        <span class="sp-time"><i class='fa-regular fa-clock'></i> 80 min</span>
                        <span class="sp-price beauty-price dynamic-price" data-base="380">380 zł</span>
                    </div>
                    <p>Rozszerzona wersja masażu z maską.</p>
                </div>
                <a href="{$link->getPageLink('cart', true, null, "add=1&id_product=36&token={$static_token}")}" class="btn-add-cart beauty-cart-btn">
                    DO KOSZYKA <i class='fa-solid fa-cart-shopping'></i>
                </a>
            </div>

            {* Japoński Lifting *}
            <div class="service-product-card beauty-card">
                <div class="sp-content">
                    <h3>Japoński Lifting Twarzy</h3>
                    <div class="sp-meta">
                        <span class="sp-time"><i class='fa-regular fa-clock'></i> 70 min</span>
                        <span class="sp-price beauty-price dynamic-price" data-base="250">250 zł</span>
                    </div>
                    <p>Głęboki relaks i drenaż limfatyczny.</p>
                </div>
                <a href="{$link->getPageLink('cart', true, null, "add=1&id_product=37&token={$static_token}")}" class="btn-add-cart beauty-cart-btn">
                    DO KOSZYKA <i class='fa-solid fa-cart-shopping'></i>
                </a>
            </div>

             {* Masaż Transbukalny *}
             <div class="service-product-card beauty-card">
                <div class="sp-content">
                    <h3>Masaż Transbukalny (Wewnątrz)</h3>
                    <div class="sp-meta">
                        <span class="sp-time"><i class='fa-regular fa-clock'></i> 60 min</span>
                        <span class="sp-price beauty-price dynamic-price" data-base="300">300 zł</span>
                    </div>
                    <p>Masaż wewnątrzustny (bruksizm).</p>
                </div>
                <a href="{$link->getPageLink('cart', true, null, "add=1&id_product=38&token={$static_token}")}" class="btn-add-cart beauty-cart-btn">
                    DO KOSZYKA <i class='fa-solid fa-cart-shopping'></i>
                </a>
            </div>
        </div>

        <div class="category-header">
            <h2>Modelowanie Sylwetki i Redukcja Cellulitu</h2>
            <div class="header-divider beauty-divider"></div>
        </div>

        <div class="services-grid-row">
            {* Endermologia *}
            <div class="service-product-card beauty-card">
                <div class="sp-content">
                    <h3>Endermologia LPG Alliance (40 min)</h3>
                    <div class="sp-meta">
                        <span class="sp-time"><i class='fa-regular fa-clock'></i> 40 min</span>
                        <span class="sp-price beauty-price dynamic-price" data-base="300">300 zł</span>
                    </div>
                    <p>Mechaniczna stymulacja tkanki. Redukcja cellulitu.</p>
                </div>
                <a href="{$link->getPageLink('cart', true, null, "add=1&id_product=39&token={$static_token}")}" class="btn-add-cart beauty-cart-btn">
                    DO KOSZYKA <i class='fa-solid fa-cart-shopping'></i>
                </a>
            </div>

            {* Pakiet LPG 10 *}
            <div class="service-product-card beauty-highlight">
                <div class="sp-content">
                    <div class="packet-label beauty-label">PAKIET LPG</div>
                    <h3>Pakiet 10 Zabiegów LPG</h3>
                    <div class="sp-meta">
                        <span class="sp-time"><i class='fa-solid fa-repeat'></i> 10x 40 min</span>
                        <span class="sp-price beauty-price dynamic-price" data-base="2200">2 200 zł</span>
                    </div>
                    <p>Pełna kuracja. Oszczędzasz 800 zł.</p>
                </div>
                <a href="{$link->getPageLink('cart', true, null, "add=1&id_product=40&token={$static_token}")}" class="btn-add-cart beauty-cart-btn filled">
                    DO KOSZYKA <i class='fa-solid fa-cart-shopping'></i>
                </a>
            </div>

            {* Fala Uderzeniowa Ciało *}
            <div class="service-product-card beauty-card">
                <div class="sp-content">
                    <h3>Fala Uderzeniowa Storz (Uda/Pośladki)</h3>
                    <div class="sp-meta">
                        <span class="sp-time"><i class='fa-regular fa-clock'></i> 30 min</span>
                        <span class="sp-price beauty-price dynamic-price" data-base="280">280 zł</span>
                    </div>
                    <p>Rozbijanie złogów tłuszczowych.</p>
                </div>
                <a href="{$link->getPageLink('cart', true, null, "add=1&id_product=41&token={$static_token}")}" class="btn-add-cart beauty-cart-btn">
                    DO KOSZYKA <i class='fa-solid fa-cart-shopping'></i>
                </a>
            </div>

             {* Fala Radiowa Ciało *}
            <div class="service-product-card beauty-card">
                <div class="sp-content">
                    <h3>Fala Radiowa RF (Brzuch)</h3>
                    <div class="sp-meta">
                        <span class="sp-time"><i class='fa-regular fa-clock'></i> 45 min</span>
                        <span class="sp-price beauty-price dynamic-price" data-base="700">700 zł</span>
                    </div>
                    <p>Silne ujędrnianie wiotkiej skóry.</p>
                </div>
                <a href="{$link->getPageLink('cart', true, null, "add=1&id_product=42&token={$static_token}")}" class="btn-add-cart beauty-cart-btn">
                    DO KOSZYKA <i class='fa-solid fa-cart-shopping'></i>
                </a>
            </div>
        </div>

        <div class="category-header">
            <h2>Laseroterapia i Epilacja</h2>
            <div class="header-divider beauty-divider"></div>
        </div>

        <div class="services-grid-row">
            {* Epilacja Pachy *}
             <div class="service-product-card beauty-card">
                <div class="sp-content">
                    <h3>Epilacja Laserowa - Pachy</h3>
                    <div class="sp-meta">
                        <span class="sp-time"><i class='fa-regular fa-clock'></i> 15 min</span>
                        <span class="sp-price beauty-price dynamic-price" data-base="300">300 zł</span>
                    </div>
                    <p>Laser Primelase Excellence.</p>
                </div>
                <a href="{$link->getPageLink('cart', true, null, "add=1&id_product=43&token={$static_token}")}" class="btn-add-cart beauty-cart-btn">
                    DO KOSZYKA <i class='fa-solid fa-cart-shopping'></i>
                </a>
            </div>

            {* Epilacja Bikini *}
             <div class="service-product-card beauty-card">
                <div class="sp-content">
                    <h3>Epilacja Laserowa - Bikini Głębokie</h3>
                    <div class="sp-meta">
                        <span class="sp-time"><i class='fa-regular fa-clock'></i> 45 min</span>
                        <span class="sp-price beauty-price dynamic-price" data-base="450">450 zł</span>
                    </div>
                    <p>Pełna epilacja okolic intymnych.</p>
                </div>
                <a href="{$link->getPageLink('cart', true, null, "add=1&id_product=44&token={$static_token}")}" class="btn-add-cart beauty-cart-btn">
                    DO KOSZYKA <i class='fa-solid fa-cart-shopping'></i>
                </a>
            </div>

             {* Zamykanie Naczynek *}
             <div class="service-product-card beauty-card">
                <div class="sp-content">
                    <h3>Zamykanie Naczynek (Twarz)</h3>
                    <div class="sp-meta">
                        <span class="sp-time"><i class='fa-regular fa-clock'></i> 45 min</span>
                        <span class="sp-price beauty-price dynamic-price" data-base="850">850 zł</span>
                    </div>
                    <p>Laser Alma Dye-VL.</p>
                </div>
                <a href="{$link->getPageLink('cart', true, null, "add=1&id_product=45&token={$static_token}")}" class="btn-add-cart beauty-cart-btn">
                    DO KOSZYKA <i class='fa-solid fa-cart-shopping'></i>
                </a>
            </div>
        </div>

        <div class="category-header" style="margin-top: 80px;">
            <h2>Kosmetyki i Pielęgnacja Domowa</h2>
            <div class="header-divider beauty-divider"></div>
        </div>

        <div class="services-grid-row">
             <div style="grid-column: 1 / -1; text-align: center; padding: 40px; background: #fff5f8; border: 1px dashed #f8bbd0; border-radius: 12px; color: #880e4f;">
                <i class='fa-solid fa-boxes-packing' style="font-size: 40px; color: #d81b60; margin-bottom: 15px;"></i>
                <h3 style="font-size: 18px; margin: 0;">Oferta profesjonalnych kosmetyków jest w przygotowaniu.</h3>
                <p style="margin-top: 10px; font-size: 13px;">Zapraszamy wkrótce!</p>
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
                        <span class="ds-tag beauty-tag">TWÓJ KOSMETOLOG</span>
                        <h2 style="margin-bottom: 15px;">mgr Ewelina Szabłowska</h2>
                        <p class="ds-intro">
                            Absolwentka AWF w Krakowie. Specjalistka w dziedzinie fizjoterapii i kosmetologii. 
                            W swojej pracy łączę wiedzę medyczną z holistycznym podejściem do ciała.
                        </p>
                        <div class="ds-features">
                            <div class="feature-item"><i class='fa-solid fa-user-graduate' style="color: #d81b60;"></i> <span>Dyplomowany Kosmetolog</span></div>
                            <div class="feature-item"><i class='fa-solid fa-certificate' style="color: #d81b60;"></i> <span>Ekspert terapii Anti-Aging</span></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>
{/block}