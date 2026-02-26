{strip}
{* * Sekcja Diet - WERSJA STRETCH FIX
 * Struktura przygotowana pod rozciąganie wysokości.
 *}
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

<div class="diet-premium-container">
    <div class="container">
        
        {* --- NAGŁÓWEK --- *}
        <div class="diet-header-section">
            <div class="diet-header-accent">ŚWIADOME ODŻYWIANIE</div>
            <h2 class="diet-header-title">STREFA DIETY</h2>
            <div class="diet-header-line"></div>
            <p class="diet-header-desc">
                Wybierz obszar żywienia, który Cię interesuje. Dbamy o szeroki wybór produktów, aby ułatwić Ci zakupy zgodne z Twoim stylem życia i potrzebami żywieniowymi.
            </p>
        </div>

        <div class="diet-premium-wrapper">

            {* --- LEWA KOLUMNA (PINK BOX) --- *}
            <div class="diet-left-column">
                <div class="diet-info-card-box">
                    <div class="diet-info-badge">TWOJE ZDROWIE</div>
                    <h3 class="diet-info-heading">Wspieramy Twój<br>styl odżywiania</h3>
                    <p class="diet-info-text">
                        Stworzyliśmy dedykowane kategorie, aby ułatwić Ci znalezienie produktów pasujących do Twoich potrzeb. Oszczędzaj czas i szybciej odkrywaj żywność zgodną z Twoją dietą.
                    </p>
                    
                    <div class="diet-desktop-only">
                        <a href="{$link->getCategoryLink(167)}" class="diet-card-btn">
                            Zobacz wszystkie diety <i class="fa-solid fa-arrow-right"></i>
                        </a>
                    </div>
                </div>
            </div>

            {* --- PRAWA KOLUMNA (GRID) --- *}
            <div class="diet-right-column">
                <div class="diet-premium-grid">

                    {* 1. BEZ GLUTENU *}
                    <a href="{$link->getCategoryLink(264208)}" class="diet-card">
                        <div class="diet-icon-box"><i class="fa-solid fa-bread-slice"></i></div>
                        <span class="diet-card-name">Bez Glutenu</span>
                    </a>

                    {* 2. WEGETARIAŃSKIE *}
                    <a href="{$link->getCategoryLink(264210)}" class="diet-card">
                        <div class="diet-icon-box"><i class="fa-solid fa-carrot"></i></div>
                        <span class="diet-card-name">Wegetariańskie</span>
                    </a>

                    {* 3. WEGAŃSKIE *}
                    <a href="{$link->getCategoryLink(264209)}" class="diet-card">
                        <div class="diet-icon-box"><i class="fa-solid fa-seedling"></i></div>
                        <span class="diet-card-name">Wegańskie</span>
                    </a>

                    {* 4. BEZ LAKTOZY *}
                    <a href="{$link->getCategoryLink(264207)}" class="diet-card">
                        <div class="diet-icon-box"><i class="fa-solid fa-glass-water"></i></div>
                        <span class="diet-card-name">Bez Laktozy</span>
                    </a>

                    {* 5. BIO / ORGANIC *}
                    <a href="{$link->getCategoryLink(264205)}" class="diet-card">
                        <div class="diet-icon-box"><i class="fa-solid fa-leaf"></i></div>
                        <span class="diet-card-name">Bio / Organic</span>
                    </a>

                    {* 6. KETO / LOW-CARB *}
                    <a href="{$link->getCategoryLink(264211)}" class="diet-card">
                        <div class="diet-icon-box"><i class="fa-solid fa-bolt"></i></div>
                        <span class="diet-card-name">Keto / Low-Carb</span>
                    </a>

                    {* 7. BEZ CUKRU *}
                    <a href="{$link->getCategoryLink(264206)}" class="diet-card">
                        <div class="diet-icon-box"><i class="fa-solid fa-cube"></i></div>
                        <span class="diet-card-name">Bez Cukru</span>
                    </a>

                    {* 8. NISKI INDEKS *}
                    <a href="{$link->getCategoryLink(264212)}" class="diet-card">
                        <div class="diet-icon-box"><i class="fa-solid fa-arrow-trend-down"></i></div>
                        <span class="diet-card-name">Niski Indeks Glikemiczny</span>
                    </a>

                </div>
            </div>
            
            {* --- MOBILE LINK --- *}
            <div class="diet-mobile-only-wrapper">
                <a href="{$link->getCategoryLink(167)}" class="diet-text-link mobile-forced-active">
                    <span class="link-text">ZOBACZ WSZYSTKIE DIETY</span>
                    <i class="fa-solid fa-arrow-right link-arrow"></i>
                </a>
            </div>

        </div>
    </div>
</div>
{/strip}