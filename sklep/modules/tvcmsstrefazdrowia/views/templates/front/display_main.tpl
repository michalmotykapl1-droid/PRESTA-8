{extends file='page.tpl'}

{block name='page_title'}
    <span class="strefa-page-header">Strefa Zdrowia</span>
{/block}

{block name='page_content'}
<div class="strefa-landing-wrapper">
    
    {* ==============================================
       SEKCJA 1: HERO BANNER (ORBITA)
       ============================================== *}
    <div class="strefa-hero-section">
        <div class="hero-text-content">
            <span class="hero-subtitle">FIZJOTERAPIA • KOSMETOLOGIA • NATUROPATIA</span>
            <h1>Twoje centrum<br>zdrowia i równowagi</h1>
            <p>
                Oferujemy kompleksowe wsparcie Twojego organizmu w jednym miejscu.
                Niezależnie od tego, czy potrzebujesz skutecznej <strong>fizjoterapii</strong>, 
                profesjonalnej <strong>kosmetologii</strong> czy naturalnego wsparcia <strong>naturopaty</strong>.
            </p>
            <div class="hero-cta-wrapper">
                <a href="#nasze-uslugi" class="btn-hero-action">
                    SPRAWDŹ OFERTĘ <i class='fa-solid fa-arrow-down'></i>
                </a>
            </div>
        </div>
        
        <div class="hero-visual-decoration">
            <div class="liquid-blob"></div>
            <div class="orbit-system">
                <div class="orbit-center"><i class='fa-solid fa-heart-pulse'></i></div>
                <div class="orbit-ring">
                    
                    {* --- IKONA FIZJO: person-walking --- *}
                    <a href="{$link->getModuleLink('tvcmsstrefazdrowia', 'display', ['strona' => 'fizjoterapia'])}" class="orbit-item item-physio" style="text-decoration: none;">
                        <div class="orbit-icon"><i class='fa-solid fa-person-walking'></i></div>
                        <span class="orbit-label">FIZJO</span>
                    </a>

                    {* --- IKONA URODA: spa (Lotos) --- *}
                    <a href="{$link->getModuleLink('tvcmsstrefazdrowia', 'display', ['strona' => 'uroda'])}" class="orbit-item item-beauty" style="text-decoration: none;">
                        <div class="orbit-icon"><i class='fa-solid fa-spa'></i></div>
                        <span class="orbit-label">URODA</span>
                    </a>

                    {* --- IKONA NATURO: leaf (Liść) --- *}
                    <a href="{$link->getModuleLink('tvcmsstrefazdrowia', 'display', ['strona' => 'naturopatia'])}" class="orbit-item item-naturo" style="text-decoration: none;">
                        <div class="orbit-icon"><i class='fa-solid fa-leaf'></i></div>
                        <span class="orbit-label">NATURO</span>
                    </a>

                </div>
            </div>
        </div>
    </div>

    {* ==============================================
       SEKCJA 2: WARTOŚCI
       ============================================== *}
    <div class="strefa-values-row">
        <div class="container">
            <div class="values-header">
                <h2>Dlaczego warto nam zaufać?</h2>
                <div class="header-divider"></div>
            </div>
            <div class="row">
                <div class="col-md-4 col-sm-12">
                    <div class="modern-value-card card-1">
                        <div class="icon-glow-wrapper organic-shape">
                            <i class='fa-solid fa-user-doctor'></i> 
                        </div>
                        <h3>Ekspercka Wiedza</h3>
                        <p>Zespół dyplomowanych specjalistów z wieloletnim doświadczeniem.</p>
                    </div>
                </div>
                <div class="col-md-4 col-sm-12">
                    <div class="modern-value-card card-2">
                        <div class="icon-glow-wrapper organic-shape">
                            <i class='fa-solid fa-house-medical'></i> 
                        </div>
                        <h3>Pełna Dostępność</h3>
                        <p>Wizyta w gabinecie, dojazd do domu lub konsultacja online.</p>
                    </div>
                </div>
                <div class="col-md-4 col-sm-12">
                    <div class="modern-value-card card-3">
                        <div class="icon-glow-wrapper organic-shape">
                            <i class='fa-solid fa-clipboard-user'></i> 
                        </div>
                        <h3>Indywidualny Plan</h3>
                        <p>Każda terapia jest precyzyjnie dopasowana do Twoich potrzeb.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {* ==============================================
       SEKCJA 3: SZCZEGÓŁOWE USŁUGI
       ============================================== *}
    <div id="nasze-uslugi" class="strefa-services-section">
        <div class="services-header-center">
            <h2>NASZE OBSZARY DZIAŁANIA</h2>
            <div class="header-divider"></div>
            <p class="services-header-intro">Wybierz dziedzinę, w której możemy Ci pomóc.</p>
        </div>

        <div class="services-list-container">
            
            {* FIZJOTERAPIA - Ikona: person-walking *}
            <div class="deep-service-row">
                <div class="ds-content">
                    <span class="ds-tag physio-tag">REHABILITACJA & RUCH</span>
                    <h2>Fizjoterapia Mobilna i Stacjonarna</h2>
                    <p class="ds-intro">
                        Skuteczna walka z bólem.
                        Nasi specjaliści diagnozują źródło problemu i dobierają terapię, która przywraca sprawność na długo.
                    </p>
                    <div class="ds-treatments-grid">
                        <span class="treatment-tag">Terapia manualna</span>
                        <span class="treatment-tag">Masaż leczniczy</span>
                        <span class="treatment-tag">Suche igłowanie</span>
                        <span class="treatment-tag">Pinoterapia</span>
                        <span class="treatment-tag">Kinesiotaping</span>
                        <span class="treatment-tag">Wizyty domowe</span>
                    </div>
                    <a href="{$link->getModuleLink('tvcmsstrefazdrowia', 'display', ['strona' => 'fizjoterapia'])}" class="btn-ds-action physio-btn">ZOBACZ PEŁNĄ OFERTĘ I CENNIK</a>
                </div>
                <div class="ds-visual-col">
                    <div class="visual-circle physio-circle">
                        <i class='fa-solid fa-person-walking'></i>
                        <div class="circle-pulse"></div>
                    </div>
                </div>
            </div>

            {* URODA - Ikona: spa *}
            <div class="deep-service-row reverse">
                <div class="ds-content">
                    <span class="ds-tag beauty-tag">PIĘKNO & RELAKS</span>
                    <h2>Strefa Urody i Kosmetologia</h2>
                    <p class="ds-intro">
                        Połączenie zaawansowanej kosmetologii z głębokim relaksem.
                        Naturalne metody odmładzania i profesjonalna pielęgnacja skóry.
                    </p>
                    <div class="ds-treatments-grid">
                        <span class="treatment-tag">Masaż Kobido</span>
                        <span class="treatment-tag">Facemodeling</span>
                        <span class="treatment-tag">Zoga Face</span>
                        <span class="treatment-tag">Oczyszczanie wodorowe</span>
                        <span class="treatment-tag">Mezoterapia</span>
                        <span class="treatment-tag">Rytuały SPA</span>
                    </div>
                    <a href="{$link->getModuleLink('tvcmsstrefazdrowia', 'display', ['strona' => 'uroda'])}" class="btn-ds-action beauty-btn">ZOBACZ PEŁNĄ OFERTĘ I CENNIK</a>
                </div>
                <div class="ds-visual-col">
                    <div class="visual-circle beauty-circle">
                        <i class='fa-solid fa-spa'></i>
                        <div class="circle-pulse"></div>
                    </div>
                </div>
            </div>

            {* NATUROPATIA - Ikona: leaf *}
            <div class="deep-service-row">
                <div class="ds-content">
                    <span class="ds-tag naturo-tag">HOLISTYKA & ZDROWIE</span>
                    <h2>Naturopatia i Suplementacja</h2>
                    <p class="ds-intro">
                        Szukamy przyczyn dolegliwości, analizując cały organizm.
                        Wspieramy leczenie chorób przewlekłych naturalnymi metodami.
                    </p>
                    <div class="ds-treatments-grid">
                        <span class="treatment-tag">Konsultacje holistyczne</span>
                        <span class="treatment-tag">Analiza pierwiastkowa włosa</span>
                        <span class="treatment-tag">Dobór suplementacji</span>
                        <span class="treatment-tag">Ziołolecznictwo</span>
                        <span class="treatment-tag">Dietoterapia</span>
                    </div>
                    <a href="{$link->getModuleLink('tvcmsstrefazdrowia', 'display', ['strona' => 'naturopatia'])}" class="btn-ds-action naturo-btn">ZOBACZ PEŁNĄ OFERTĘ I CENNIK</a>
                </div>
                <div class="ds-visual-col">
                    <div class="visual-circle naturo-circle">
                        <i class='fa-solid fa-leaf'></i>
                        <div class="circle-pulse"></div>
                    </div>
                </div>
            </div>

        </div>
    </div>
    
    {* ==============================================
       SEKCJA 4: CENTRUM SZKOLENIOWE
       ============================================== *}
    <div class="strefa-training-section">
        <div class="container">
            <div class="training-header">
                <span class="training-subtitle">DLA PROFESJONALISTÓW</span>
                <h2>Centrum Szkoleniowe</h2>
                <div class="header-divider white-divider"></div>
                <p>Podnieś swoje kwalifikacje pod okiem naszych ekspertów.
                Oferujemy autorskie szkolenia z zakresu podologii, kosmetologii i fizjoterapii.</p>
            </div>

            <div class="training-grid">
                <div class="training-card">
                    <div class="training-icon"><i class='fa-solid fa-graduation-cap'></i></div>
                    <h3>Szkolenia Podologiczne</h3>
                    <p>Kompleksowe kursy z zakresu ortonyksji, hiperkeratoz i zaawansowanych terapii stóp.</p>
                    <a href="#" class="link-training">Zobacz program <i class='fa-solid fa-arrow-right'></i></a>
                </div>
                <div class="training-card">
                    <div class="training-icon"><i class='fa-solid fa-spa'></i></div>
                    <h3>Warsztaty Kosmetologiczne</h3>
                    <p>Nowoczesne techniki zabiegowe, masaże twarzy, Kobido i terapie łączone.</p>
                    <a href="#" class="link-training">Zobacz program <i class='fa-solid fa-arrow-right'></i></a>
                </div>
                <div class="training-card">
                    <div class="training-icon"><i class='fa-solid fa-bone'></i></div>
                    <h3>Kursy dla Fizjoterapeutów</h3>
                    <p>Praktyczne szkolenia z metod manualnych, tapingu, pinoterapii i rehabilitacji.</p>
                    <a href="#" class="link-training">Zobacz program <i class='fa-solid fa-arrow-right'></i></a>
                </div>
            </div>
            
            <div class="training-cta-center">
                <a href="#" class="btn-training-main">SPRAWDŹ HARMONOGRAM SZKOLEŃ</a>
            </div>
        </div>
    </div>

    {* ==============================================
       SEKCJA 5: ZESPÓŁ
       ============================================== *}
    <div id="kontakt-zespol" class="strefa-team-section">
        <div class="team-header">
            <h2>Poznaj Nasz Zespół</h2>
            <div class="header-divider"></div>
            <p>Ludzie z pasją i dyplomami, którym możesz zaufać.</p>
        </div>
        <div class="container">
            <div class="row team-row-center">
                <div class="col-md-4 col-sm-12">
                    <div class="team-card">
                        <div class="team-photo">
                            <img src="{$urls.base_url}modules/tvcmsstrefazdrowia/views/img/fizjo.webp" alt="Ewelina Szabłowska" loading="lazy">
                        </div>
                        <div class="team-info">
                            <h3>mgr Ewelina Szabłowska</h3>
                            <span class="team-role">FIZJOTERAPEUTA & KOSMETOLOG</span>
                            <div class="trainer-badge">
                                <i class='fa-solid fa-user-graduate'></i> GŁÓWNY SZKOLENIOWIEC
                            </div>
                            <p>Absolwentka AWF w Krakowie.
                            Specjalizuje się w terapii manualnej kręgosłupa oraz zaawansowanych zabiegach odmładzających.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 col-sm-12">
                    <div class="team-card">
                        <div class="team-photo">
                            <img src="{$urls.base_url}modules/tvcmsstrefazdrowia/views/img/natur.webp" alt="Piotr Górszczak" loading="lazy">
                        </div>
                        <div class="team-info">
                            <h3>mgr Piotr Górszczak</h3>
                            <span class="team-role">NATUROPATA & DIETETYK</span>
                            <div class="trainer-badge">
                                <i class='fa-solid fa-chalkboard-user'></i> SZKOLENIOWIEC
                            </div>
                            <p>Specjalista medycyny naturalnej. Pomaga przywrócić równowagę organizmu poprzez dietoterapię, ziołolecznictwo i świadomy styl życia.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>
{/block}