{* * TPL dla Menu "SPOŻYWCZE" - WERSJA FINALNA Z HOOKIEM "HIT DNIA"
 * * 1. Strzałka > ustawiona absolutnie.
 * * 2. Łap okazje zmienione na ID 45.
 * * 3. Zintegrowany Hook modułu Strefa Produktowa w pustej przestrzeni.
 *}

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

<style>
    /* --- KONTENER GŁÓWNY --- */
    #mz-grocery-premium {
        width: 100%;
        background: #fff;
        min-height: 620px;
        font-family: 'Open Sans', sans-serif, Arial; 
        box-sizing: border-box;
        border-top: none; 
        color: #333;
        position: relative;
    }
    
    #mz-grocery-premium * { box-sizing: border-box; }

    /* Paleta Kolorów */
    :root { 
        --gro-accent: #F57C00;       /* Główny pomarańcz */
        --gro-accent-soft: #fff3e0;  
        --gro-blue: #0288D1;         /* Niebieski */
        --gro-red: #d32f2f;          /* Czerwony */
        --gro-text-dark: #222;       
        --gro-text-body: #555;       
        --gro-border: #f0f0f0;
    }

    /* ICONS FIX */
    #mz-grocery-premium i, 
    #mz-grocery-premium .fa-solid {
        font-family: "Font Awesome 6 Free" !important;
        font-weight: 900 !important;
        display: inline-block;
        font-style: normal;
        font-variant: normal;
        text-rendering: auto;
        line-height: 1;
    }

    /* --- GÓRNY PASEK (TOP BAR) --- */
    .mz-top-bar {
        width: 100%;
        height: 50px;
        background: #ffffff;
        border-bottom: 1px solid var(--gro-border);
        display: flex;
        align-items: center;
        padding: 0 50px 0 0; 
        justify-content: flex-end;
        gap: 20px;
        position: relative;
        z-index: 30; /* Wyżej niż panele */
    }
    
    .mz-top-link {
        font-size: 11px;
        font-weight: 700 !important; 
        text-transform: uppercase;
        text-decoration: none !important;
        display: flex;
        align-items: center;
        transition: all 0.2s;
        letter-spacing: 0.8px;
        padding: 8px 12px;
        border-radius: 8px;
        background-color: transparent !important;
        border: none !important; 
    }
    .mz-top-link i { margin-right: 8px; font-size: 13px; }

    .mz-top-link.promo { color: var(--gro-accent) !important; }
    .mz-top-link.promo:hover { background-color: #fff3e0 !important; }

    .mz-top-link.outlet { color: var(--gro-red) !important; }
    .mz-top-link.outlet:hover { background-color: #ffebee !important; }

    .mz-top-link.new-items { color: var(--gro-blue) !important; }
    .mz-top-link.new-items:hover { background-color: #e1f5fe !important; }


    /* --- UKŁAD KOLUMNOWY --- */
    .mz-main-container {
        display: flex;
        position: relative;
        min-height: 575px;
    }

    /* --- LEWY PASEK --- */
    .mz-left-nav {
        width: 27%;
        background: #fff;
        border-right: 1px solid var(--gro-border);
        padding: 20px 0px; 
        display: flex;
        flex-direction: column;
        position: relative;
        z-index: 25; /* Musi być wyżej niż domyślny panel */
    }

    .mz-nav-item {
        position: static;
        width: 100%;
        margin-bottom: 2px;
        padding: 0 5px; 
    }

    .mz-nav-link {
        display: flex;
        align-items: center; 
        width: 100%;
        padding: 12px 35px 12px 12px;
        min-height: 45px;
        font-size: 14px;
        font-weight: 500 !important; 
        color: var(--gro-text-body) !important;
        text-decoration: none !important;
        border-radius: 8px;
        transition: background-color 0.2s ease, color 0.2s ease;
        cursor: pointer;
        position: relative;
    }

    /* Fix: Mostek hover */
    .mz-nav-link::after {
        content: '';
        position: absolute;
        top: 0;
        right: -30px; 
        width: 40px;
        height: 100%;
        background: transparent;
        z-index: 10;
    }

    .mz-link-content {
        display: flex;
        align-items: center;
        width: 100%;
    }

    .mz-link-content i.icon-main {
        width: 24px;
        color: #bbb !important; 
        font-size: 18px;
        text-align: left;
        margin-right: 10px;
        transition: color 0.2s;
        flex-shrink: 0;
    }

    .mz-link-content span {
        white-space: normal;
        line-height: 1.25;   
        display: block;
    }
    
    .mz-nav-link i.arrow { 
        position: absolute;
        right: 12px;
        top: 50%;
        transform: translateY(-50%);
        font-size: 11px;
        color: #eee;
        transition: all 0.2s;
    }

    .mz-nav-item:hover .mz-nav-link {
        background-color: var(--gro-accent-soft);
        color: var(--gro-accent) !important;
        font-weight: 600 !important;
    }
    .mz-nav-item:hover .mz-nav-link i.icon-main { color: var(--gro-accent) !important; }
    
    .mz-nav-item:hover .mz-nav-link i.arrow { 
        color: var(--gro-accent);
        transform: translateY(-50%) translateX(3px);
        opacity: 0.7;
    }

    /* --- PRAWA STRONA (SUBKATEGORIE) --- */
    .mz-content-panel {
        display: none;
        position: absolute;
        left: 27%;
        top: 0;
        width: 73%; 
        height: 100%;
        background: #fff;
        padding: 40px 50px; 
        z-index: 20; /* Przykrywa domyślny panel */
        overflow-y: auto;
        border-left: 1px solid #f9f9f9;
    }

    .mz-nav-item:hover .mz-content-panel {
        display: block;
        animation: simpleFade 0.2s ease-out; 
    }
    
    /* --- NOWE: DOMYŚLNY PANEL (HOOK) --- */
    .mz-default-placeholder {
        position: absolute;
        left: 27%;
        top: 0;
        width: 73%;
        height: 100%;
        padding: 40px 50px;
        z-index: 1; /* Pod spodem */
        display: flex;
        flex-direction: column;
        justify-content: flex-start; /* Lub center, zależnie jak wolisz */
    }

    /* Stylizacja nagłówka dla sekcji Hooka (opcjonalne dopasowanie) */
    .mz-hook-wrapper {
        max-width: 400px; /* Ograniczenie szerokości żeby ładnie wyglądało */
    }
    
    @keyframes simpleFade {
        from { opacity: 0; }
        to { opacity: 1; }
    }

    /* Grid 4 kolumny */
    .mz-inner-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr); 
        gap: 40px 25px;
        align-items: start;
    }

    .mz-sub-group { margin-bottom: 10px; }

    .mz-sub-header {
        margin-bottom: 12px;
        padding-bottom: 8px;
        border-bottom: 2px solid #f5f5f5;
        display: flex;
        align-items: flex-start; 
        line-height: 1.3;
    }
    
    .mz-sub-header i {
        font-size: 14px;
        color: var(--gro-accent) !important; 
        opacity: 0.8;
        margin-right: 10px;
        margin-top: 2px;
        line-height: 1;
        flex-shrink: 0;
    }
    
    .mz-sub-header a {
        font-size: 12px;
        font-weight: 800 !important;
        text-transform: uppercase;
        letter-spacing: 1px;
        color: var(--gro-text-dark) !important;
        text-decoration: none !important;
        transition: color 0.2s;
    }
    .mz-sub-header a:hover { color: var(--gro-accent) !important; }

    .mz-deep-list {
        list-style: none !important;
        padding: 0 !important;
        margin: 0 !important;
    }
    .mz-deep-list li { margin-bottom: 5px; }
    
    .mz-deep-list a {
        font-size: 13px;
        color: #666 !important;
        text-decoration: none !important;
        transition: color 0.2s;
        display: block;
        line-height: 1.5;
        font-weight: 400 !important;
    }
    
    .mz-deep-list a:hover {
        color: var(--gro-accent) !important;
        padding-left: 4px;
    }
</style>

<div id="mz-grocery-premium">

    {* GÓRNY PASEK *}
    <div class="mz-top-bar">
        <a href="{$link->getCategoryLink(45)}" class="mz-top-link promo">
            <i class="fa-solid fa-tag"></i> ŁAP OKAZJE
        </a>
        <a href="{$link->getCategoryLink(180)}" class="mz-top-link outlet">
            <i class="fa-solid fa-clock"></i> Krótka Data
        </a>
        
    </div>

    <div class="mz-main-container">

        {* --- LEWA KOLUMNA (NAV) --- *}
        <div class="mz-left-nav">
    
            {* 1. SŁODYCZE I PRZEKĄSKI (ID 565) *}
            <div class="mz-nav-item">
                <a href="{$link->getCategoryLink(565)}" class="mz-nav-link">
                    <div class="mz-link-content">
                        <i class="fa-solid fa-cookie-bite icon-main"></i>
                        <span>Słodycze i Przekąski</span>
                    </div>
                    <i class="fa-solid fa-chevron-right arrow"></i>
                </a>
                <div class="mz-content-panel">
                    <div class="mz-inner-grid">
                        
                        {* Batony i Wafle *}
                        <div class="mz-sub-group">
                            <div class="mz-sub-header"><i class="fa-solid fa-candy-cane"></i> <a href="{$link->getCategoryLink(594)}">Batony i Wafle</a></div>
                            <ul class="mz-deep-list">
                                <li><a href="{$link->getCategoryLink(594)}">Batony</a></li>
                                <li><a href="{$link->getCategoryLink(607)}">Chałwa i sezamki</a></li>
                                <li><a href="{$link->getCategoryLink(604)}">Wafelki</a></li>
                            </ul>
                        </div>
                       
                        {* Ciastka *}
                        <div class="mz-sub-group">
                            <div class="mz-sub-header"><i class="fa-solid fa-cookie"></i> <a href="{$link->getCategoryLink(600)}">Ciastka</a></div>
                            <ul class="mz-deep-list">
                                <li><a href="{$link->getCategoryLink(603)}">Biszkopty</a></li>
                                <li><a href="{$link->getCategoryLink(601)}">Ciastka kruche</a></li>
                                <li><a href="{$link->getCategoryLink(602)}">Markizy</a></li>
                                <li><a href="{$link->getCategoryLink(600)}">Pozostałe ciastka</a></li>
                            </ul>
                        </div>

                        {* Cukierki i Inne *}
                        <div class="mz-sub-group">
                            <div class="mz-sub-header"><i class="fa-solid fa-gift"></i> <a href="{$link->getCategoryLink(608)}">Cukierki i Desery</a></div>
                            <ul class="mz-deep-list">
                                <li><a href="{$link->getCategoryLink(608)}">Cukierki i draże</a></li>
                                <li><a href="{$link->getCategoryLink(622)}">Desery (Budyń, Kisiel)</a></li>
                                <li><a href="{$link->getCategoryLink(610)}">Gumy do żucia</a></li>
                                <li><a href="{$link->getCategoryLink(612)}">Lizaki</a></li>
                                <li><a href="{$link->getCategoryLink(609)}">Toffi i Krówki</a></li>
                                <li><a href="{$link->getCategoryLink(611)}">Żelki i pianki</a></li>
                            </ul>
                        </div>

                        {* Czekolady *}
                        <div class="mz-sub-group">
                            <div class="mz-sub-header"><i class="fa-solid fa-table-cells"></i> <a href="{$link->getCategoryLink(566)}">Czekolady</a></div>
                            <ul class="mz-deep-list">
                                <li><a href="{$link->getCategoryLink(574)}">Bakalie w czekoladzie</a></li>
                                <li><a href="{$link->getCategoryLink(567)}">Czekolady (Tabliczki)</a></li>
                                <li><a href="{$link->getCategoryLink(614)}">Czekoladki i Bombonierki</a></li>
                                <li><a href="{$link->getCategoryLink(919)}">Figurki czekoladowe</a></li>
                            </ul>
                        </div>
      
                        {* Słone *}
                        <div class="mz-sub-group">
                            <div class="mz-sub-header"><i class="fa-solid fa-bowl-food"></i> <a href="{$link->getCategoryLink(570)}">Słone Przekąski</a></div>
                            <ul class="mz-deep-list">
                                <li><a href="{$link->getCategoryLink(596)}">Chipsy i nachosy</a></li>
                                <li><a href="{$link->getCategoryLink(915)}">Dipy</a></li>
                                <li><a href="{$link->getCategoryLink(618)}">Krakersy</a></li>
                                <li><a href="{$link->getCategoryLink(978)}">Orzeszki</a></li>
                                <li><a href="{$link->getCategoryLink(571)}">Paluszki i precelki</a></li>
                                <li><a href="{$link->getCategoryLink(572)}">Popcorn</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            {* 2. SPIŻARNIA (Mąki, Kasze, Tłuszcze, Przyprawy) *}
            <div class="mz-nav-item">
                <a href="{$link->getCategoryLink(579)}" class="mz-nav-link">
                    <div class="mz-link-content">
                        <i class="fa-solid fa-jar icon-main"></i>
                        <span>Spiżarnia, Mąki, Przyprawy</span>
                    </div>
                    <i class="fa-solid fa-chevron-right arrow"></i>
                </a>
                <div class="mz-content-panel">
                    <div class="mz-inner-grid">
                        
                        {* Dodatki *}
                        <div class="mz-sub-group">
                            <div class="mz-sub-header"><i class="fa-solid fa-spoon"></i> <a href="{$link->getCategoryLink(550)}">Dodatki</a></div>
                            <ul class="mz-deep-list">
                                <li><a href="{$link->getCategoryLink(550)}">Dodatki spożywcze</a></li>
                                <li><a href="{$link->getCategoryLink(552)}">Żelatyny i zagęstniki</a></li>
                            </ul>
                        </div>

                        {* Mąki i Sypkie *}
                        <div class="mz-sub-group">
                            <div class="mz-sub-header"><i class="fa-solid fa-wheat-awn"></i> <a href="{$link->getCategoryLink(579)}">Produkty sypkie</a></div>
                            <ul class="mz-deep-list">
                                <li><a href="{$link->getCategoryLink(898)}">Bułka tarta</a></li>
                                <li><a href="{$link->getCategoryLink(580)}">Kasza</a></li>
                                <li><a href="{$link->getCategoryLink(591)}">Mąka i mieszanki</a></li>
                                <li><a href="{$link->getCategoryLink(926)}">Rośliny strączkowe</a></li>
                                <li><a href="{$link->getCategoryLink(910)}">Ryż</a></li>
                            </ul>
                        </div>

                        {* Przyprawy *}
                        <div class="mz-sub-group">
                            <div class="mz-sub-header"><i class="fa-solid fa-pepper-hot"></i> <a href="{$link->getCategoryLink(592)}">Przyprawy i zioła</a></div>
                            <ul class="mz-deep-list">
                                <li><a href="{$link->getCategoryLink(620)}">Kostki rosołowe</a></li>
                                <li><a href="{$link->getCategoryLink(662)}">Mieszanki przyprawowe</a></li>
                                <li><a href="{$link->getCategoryLink(593)}">Panierki</a></li>
                                <li><a href="{$link->getCategoryLink(908)}">Pieprz</a></li>
                                <li><a href="{$link->getCategoryLink(626)}">Przyprawy jednoskładnikowe</a></li>
                                <li><a href="{$link->getCategoryLink(909)}">Sól</a></li>
                            </ul>
                        </div>

                        {* Przetwory *}
                        <div class="mz-sub-group">
                            <div class="mz-sub-header"><i class="fa-solid fa-utensils"></i> <a href="{$link->getCategoryLink(537)}">Przetwory</a></div>
                            <ul class="mz-deep-list">
                                <li><a href="{$link->getCategoryLink(539)}">Dżemy i powidła</a></li>
                                <li><a href="{$link->getCategoryLink(875)}">Kiszonki</a></li>
                                <li><a href="{$link->getCategoryLink(907)}">Ogórki i pikle</a></li>
                                <li><a href="{$link->getCategoryLink(538)}">Przetwory owocowe</a></li>
                                <li><a href="{$link->getCategoryLink(667)}">Przetwory warzywne</a></li>
                            </ul>
                        </div>

                        {* Tłuszcze *}
                        <div class="mz-sub-group">
                            <div class="mz-sub-header"><i class="fa-solid fa-bottle-droplet"></i> <a href="{$link->getCategoryLink(261675)}">Tłuszcze i Ocet</a></div>
                            <ul class="mz-deep-list">
                                <li><a href="{$link->getCategoryLink(261676)}">Ocet</a></li>
                                <li><a href="{$link->getCategoryLink(261684)}">Olej</a></li>
                                <li><a href="{$link->getCategoryLink(261685)}">Oliwa</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            {* 3. NAPOJE, KAWA, HERBATA *}
            <div class="mz-nav-item">
                <a href="{$link->getCategoryLink(517)}" class="mz-nav-link">
                    <div class="mz-link-content">
                        <i class="fa-solid fa-mug-hot icon-main"></i>
                        <span>Napoje, Kawa, Herbata</span>
                    </div>
                    <i class="fa-solid fa-chevron-right arrow"></i>
                </a>
                <div class="mz-content-panel">
                    <div class="mz-inner-grid">

                        {* Bezalkoholowe *}
                        <div class="mz-sub-group">
                            <div class="mz-sub-header"><i class="fa-solid fa-ban"></i> <a href="{$link->getCategoryLink(883)}">Alkohol Free</a></div>
                            <ul class="mz-deep-list">
                                <li><a href="{$link->getCategoryLink(888)}">Dodatki do alkoholu</a></li>
                                <li><a href="{$link->getCategoryLink(889)}">Piwo bezalkoholowe</a></li>
                                <li><a href="{$link->getCategoryLink(1040)}">Wino bezalkoholowe</a></li>
                            </ul>
                        </div>

                        {* Gorące Inne *}
                        <div class="mz-sub-group">
                            <div class="mz-sub-header"><i class="fa-solid fa-fire"></i> <a href="{$link->getCategoryLink(553)}">Gorące inne</a></div>
                            <ul class="mz-deep-list">
                                <li><a href="{$link->getCategoryLink(554)}">Czekolada do picia</a></li>
                                <li><a href="{$link->getCategoryLink(633)}">Kakao</a></li>
                            </ul>
                        </div>

                        {* Herbata *}
                        <div class="mz-sub-group">
                            <div class="mz-sub-header"><i class="fa-solid fa-leaf"></i> <a href="{$link->getCategoryLink(627)}">Herbata</a></div>
                            <ul class="mz-deep-list">
                                <li><a href="{$link->getCategoryLink(653)}">Biała</a></li>
                                <li><a href="{$link->getCategoryLink(654)}">Czarna</a></li>
                                <li><a href="{$link->getCategoryLink(798)}">Czerwona i Pu-erh</a></li>
                                <li><a href="{$link->getCategoryLink(642)}">Owocowa</a></li>
                                <li><a href="{$link->getCategoryLink(799)}">Yerba Mate</a></li>
                                <li><a href="{$link->getCategoryLink(656)}">Zielona</a></li>
                                <li><a href="{$link->getCategoryLink(628)}">Ziołowa</a></li>
                            </ul>
                        </div>

                        {* Kawa *}
                        <div class="mz-sub-group">
                            <div class="mz-sub-header"><i class="fa-solid fa-mug-saucer"></i> <a href="{$link->getCategoryLink(634)}">Kawa</a></div>
                            <ul class="mz-deep-list">
                                <li><a href="{$link->getCategoryLink(670)}">Akcesoria kawowe</a></li>
                                <li><a href="{$link->getCategoryLink(809)}">Kawa bezkofeinowa</a></li>
                                <li><a href="{$link->getCategoryLink(808)}">Kawa mielona</a></li>
                                <li><a href="{$link->getCategoryLink(657)}">Kawa rozpuszczalna</a></li>
                                <li><a href="{$link->getCategoryLink(811)}">Kawa zbożowa</a></li>
                                <li><a href="{$link->getCategoryLink(635)}">Kawa ziarnista</a></li>
                                <li><a href="{$link->getCategoryLink(810)}">Kawa zielona</a></li>
                            </ul>
                        </div>

                        {* Zimne Napoje *}
                        <div class="mz-sub-group">
                            <div class="mz-sub-header"><i class="fa-solid fa-glass-water"></i> <a href="{$link->getCategoryLink(517)}">Napoje Zimne</a></div>
                            <ul class="mz-deep-list">
                                <li><a href="{$link->getCategoryLink(261694)}">Energetyczne</a></li>
                                <li><a href="{$link->getCategoryLink(261693)}">Gazowane</a></li>
                                <li><a href="{$link->getCategoryLink(261690)}">Napoje funkcjonalne</a></li>
                                <li><a href="{$link->getCategoryLink(969)}">Napoje w proszku</a></li>
                                <li><a href="{$link->getCategoryLink(261679)}">Soki i nektary</a></li>
                                <li><a href="{$link->getCategoryLink(525)}">Syropy</a></li>
                                <li><a href="{$link->getCategoryLink(890)}">Woda</a></li>
                                <li><a href="{$link->getCategoryLink(887)}">Woda kokosowa</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            {* 4. OBIAD I DANIA GOTOWE *}
            <div class="mz-nav-item">
                <a href="{$link->getCategoryLink(581)}" class="mz-nav-link">
                    <div class="mz-link-content">
                        <i class="fa-solid fa-utensils icon-main"></i>
                        <span>Obiad i Dania Gotowe</span>
                    </div>
                    <i class="fa-solid fa-chevron-right arrow"></i>
                </a>
                <div class="mz-content-panel">
                    <div class="mz-inner-grid">
                        
                        {* Gotowe *}
                        <div class="mz-sub-group">
                            <div class="mz-sub-header"><i class="fa-solid fa-clock"></i> <a href="{$link->getCategoryLink(581)}">Dania gotowe</a></div>
                            <ul class="mz-deep-list">
                                <li><a href="{$link->getCategoryLink(687)}">Dania mięsne</a></li>
                                <li><a href="{$link->getCategoryLink(685)}">Dania warzywne</a></li>
                                <li><a href="{$link->getCategoryLink(651)}">Makarony i kluski</a></li>
                                <li><a href="{$link->getCategoryLink(650)}">Zupy</a></li>
                            </ul>
                        </div>
                         
                         {* Kuchnie Świata *}
                        <div class="mz-sub-group">
                            <div class="mz-sub-header"><i class="fa-solid fa-earth-americas"></i> <a href="{$link->getCategoryLink(261680)}">Świat</a></div>
                            <ul class="mz-deep-list">
                                <li><a href="{$link->getCategoryLink(261680)}">Kuchnie świata</a></li>
                            </ul>
                        </div>

                        {* Sosy *}
                        <div class="mz-sub-group">
                            <div class="mz-sub-header"><i class="fa-solid fa-bottle-water"></i> <a href="{$link->getCategoryLink(532)}">Sosy i dodatki</a></div>
                            <ul class="mz-deep-list">
                                <li><a href="{$link->getCategoryLink(557)}">Chrzany</a></li>
                                <li><a href="{$link->getCategoryLink(980)}">Dressingi</a></li>
                                <li><a href="{$link->getCategoryLink(913)}">Ketchupy</a></li>
                                <li><a href="{$link->getCategoryLink(914)}">Koncentraty warzywne</a></li>
                                <li><a href="{$link->getCategoryLink(533)}">Majonezy</a></li>
                                <li><a href="{$link->getCategoryLink(649)}">Musztardy</a></li>
                                <li><a href="{$link->getCategoryLink(912)}">Pesto</a></li>
                                <li><a href="{$link->getCategoryLink(671)}">Sosy</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            {* 5. PIECZYWO I ŚNIADANIE *}
            <div class="mz-nav-item">
                <a href="{$link->getCategoryLink(616)}" class="mz-nav-link">
                    <div class="mz-link-content">
                        <i class="fa-solid fa-bread-slice icon-main"></i>
                        <span>Pieczywo i Śniadanie</span>
                    </div>
                    <i class="fa-solid fa-chevron-right arrow"></i>
                </a>
                <div class="mz-content-panel">
                    <div class="mz-inner-grid">
                        
                        {* Pieczywo *}
                        <div class="mz-sub-group">
                            <div class="mz-sub-header"><i class="fa-solid fa-wheat-awn"></i> <a href="{$link->getCategoryLink(616)}">Pieczywo</a></div>
                            <ul class="mz-deep-list">
                                <li><a href="{$link->getCategoryLink(1042)}">Bułki</a></li>
                                <li><a href="{$link->getCategoryLink(897)}">Chleby</a></li>
                                <li><a href="{$link->getCategoryLink(617)}">Pieczywo chrupkie</a></li>
                            </ul>
                        </div>
                        
                        {* Płatki *}
                        <div class="mz-sub-group">
                            <div class="mz-sub-header"><i class="fa-solid fa-sun"></i> <a href="{$link->getCategoryLink(583)}">Płatki i Musli</a></div>
                            <ul class="mz-deep-list">
                                <li><a href="{$link->getCategoryLink(582)}">Dania śniadaniowe</a></li>
                                <li><a href="{$link->getCategoryLink(636)}">Granola</a></li>
                                <li><a href="{$link->getCategoryLink(606)}">Musli</a></li>
                                <li><a href="{$link->getCategoryLink(911)}">Otręby</a></li>
                                <li><a href="{$link->getCategoryLink(584)}">Płatki</a></li>
                            </ul>
                        </div>

                        {* Smarowanie *}
                        <div class="mz-sub-group">
                            <div class="mz-sub-header"><i class="fa-solid fa-jar"></i> <a href="{$link->getCategoryLink(877)}">Smarowanie</a></div>
                            <ul class="mz-deep-list">
                                <li><a href="{$link->getCategoryLink(615)}">Kremy i smarowidła</a></li>
                                <li><a href="{$link->getCategoryLink(878)}">Miód</a></li>
                                <li><a href="{$link->getCategoryLink(879)}">Produkty pszczele</a></li>
                                <li><a href="{$link->getCategoryLink(556)}">Słodkie kremy</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            {* 6. ŚWIEŻE I LODÓWKA *}
            <div class="mz-nav-item">
                <a href="{$link->getCategoryLink(526)}" class="mz-nav-link">
                    <div class="mz-link-content">
                        <i class="fa-solid fa-leaf icon-main"></i>
                        <span>Świeże i Lodówka</span>
                    </div>
                    <i class="fa-solid fa-chevron-right arrow"></i>
                </a>
                <div class="mz-content-panel">
                    <div class="mz-inner-grid">
                        
                        {* Mięso *}
                        <div class="mz-sub-group">
                            <div class="mz-sub-header"><i class="fa-solid fa-drumstick-bite"></i> <a href="{$link->getCategoryLink(598)}">Mięso i wędliny</a></div>
                            <ul class="mz-deep-list">
                                <li><a href="{$link->getCategoryLink(929)}">Konserwy mięsne</a></li>
                                <li><a href="{$link->getCategoryLink(678)}">Pasztety</a></li>
                                <li><a href="{$link->getCategoryLink(679)}">Smalec</a></li>
                                <li><a href="{$link->getCategoryLink(599)}">Suszone mięso</a></li>
                                <li><a href="{$link->getCategoryLink(677)}">Wędliny, szynki, polędwice</a></li>
                            </ul>
                        </div>

                        {* Nabiał *}
                        <div class="mz-sub-group">
                            <div class="mz-sub-header"><i class="fa-solid fa-cheese"></i> <a href="{$link->getCategoryLink(526)}">Nabiał i Jaja</a></div>
                            <ul class="mz-deep-list">
                                <li><a href="{$link->getCategoryLink(880)}">Jaja i zamienniki (559)</a></li>
                                <li><a href="{$link->getCategoryLink(658)}">Masło i Margaryny</a></li>
                                <li><a href="{$link->getCategoryLink(527)}">Mleko</a></li>
                                <li><a href="{$link->getCategoryLink(621)}">Sery</a></li>
                                <li><a href="{$link->getCategoryLink(881)}">Śmietana i śmietanki</a></li>
                            </ul>
                        </div>

                        {* Ryby *}
                        <div class="mz-sub-group">
                            <div class="mz-sub-header"><i class="fa-solid fa-fish"></i> <a href="{$link->getCategoryLink(541)}">Ryby</a></div>
                            <ul class="mz-deep-list">
                                <li><a href="{$link->getCategoryLink(1043)}">Kawior</a></li>
                                <li><a href="{$link->getCategoryLink(270254)}">Mięczaki i skorupiaki</a></li>
                                <li><a href="{$link->getCategoryLink(543)}">Przetwory rybne</a></li>
                                <li><a href="{$link->getCategoryLink(542)}">Ryby</a></li>
                                <li><a href="{$link->getCategoryLink(676)}">Ryby wędzone</a></li>
                            </ul>
                        </div>

                        {* Wege i Warzywa *}
                        <div class="mz-sub-group">
                            <div class="mz-sub-header"><i class="fa-solid fa-carrot"></i> <a href="{$link->getCategoryLink(534)}">Wege i Warzywa</a></div>
                            <ul class="mz-deep-list">
                                <li><a href="{$link->getCategoryLink(261681)}">Grzyby</a></li>
                                <li><a href="{$link->getCategoryLink(797)}">Napoje roślinne</a></li>
                                <li><a href="{$link->getCategoryLink(540)}">Pasty i pasztety wege</a></li>
                                <li><a href="{$link->getCategoryLink(558)}">Roślinny nabiał</a></li>
                                <li><a href="{$link->getCategoryLink(638)}">Roślinne źródła białka</a></li>
                                <li><a href="{$link->getCategoryLink(261683)}">Świeże owoce</a></li>
                                <li><a href="{$link->getCategoryLink(261678)}">Świeże warzywa</a></li>
                                <li><a href="{$link->getCategoryLink(652)}">Tofu</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            {* 7. DO PIECZENIA *}
            <div class="mz-nav-item">
                <a href="{$link->getCategoryLink(546)}" class="mz-nav-link">
                    <div class="mz-link-content">
                        <i class="fa-solid fa-kitchen-set icon-main"></i>
                        <span>Do Pieczenia</span>
                    </div>
                    <i class="fa-solid fa-chevron-right arrow"></i>
                </a>
                <div class="mz-content-panel">
                    <div class="mz-inner-grid">
                        
                        {* Bakalie *}
                        <div class="mz-sub-group">
                             <div class="mz-sub-header"><i class="fa-solid fa-seedling"></i> <a href="{$link->getCategoryLink(560)}">Bakalie</a></div>
                             <ul class="mz-deep-list">
                                <li><a href="{$link->getCategoryLink(683)}">Kandyzowane owoce</a></li>
                                <li><a href="{$link->getCategoryLink(895)}">Mieszanki bakaliowe</a></li>
                                <li><a href="{$link->getCategoryLink(637)}">Nasiona i pestki</a></li>
                                <li><a href="{$link->getCategoryLink(573)}">Orzechy</a></li>
                                <li><a href="{$link->getCategoryLink(597)}">Suszone owoce</a></li>
                             </ul>
                        </div>

                        {* Cukier *}
                        <div class="mz-sub-group">
                            <div class="mz-sub-header"><i class="fa-solid fa-cube"></i> <a href="{$link->getCategoryLink(548)}">Cukier</a></div>
                            <ul class="mz-deep-list">
                                <li><a href="{$link->getCategoryLink(549)}">Cukier</a></li>
                                <li><a href="{$link->getCategoryLink(562)}">Erytrol</a></li>
                                <li><a href="{$link->getCategoryLink(981)}">Fruktoza i glukoza</a></li>
                                <li><a href="{$link->getCategoryLink(922)}">Ksylitol</a></li>
                                <li><a href="{$link->getCategoryLink(564)}">Słodziki</a></li>
                                <li><a href="{$link->getCategoryLink(563)}">Stewia</a></li>
                                <li><a href="{$link->getCategoryLink(924)}">Syrop klonowy</a></li>
                            </ul>
                        </div>

                        {* Dodatki do ciast *}
                        <div class="mz-sub-group">
                            <div class="mz-sub-header"><i class="fa-solid fa-cake-candles"></i> <a href="{$link->getCategoryLink(546)}">Dodatki do ciast</a></div>
                            <ul class="mz-deep-list">
                                <li><a href="{$link->getCategoryLink(692)}">Aromaty</a></li>
                                <li><a href="{$link->getCategoryLink(688)}">Barwniki</a></li>
                                <li><a href="{$link->getCategoryLink(569)}">Ciasta w proszku</a></li>
                                <li><a href="{$link->getCategoryLink(690)}">Dekoracje tortów</a></li>
                                <li><a href="{$link->getCategoryLink(691)}">Drożdże</a></li>
                                <li><a href="{$link->getCategoryLink(547)}">Kremy i masy</a></li>
                                <li><a href="{$link->getCategoryLink(693)}">Przyprawy do ciast</a></li>
                                <li><a href="{$link->getCategoryLink(917)}">Spody do ciast</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            {* 8. ZDROWIE I DIETA *}
            <div class="mz-nav-item">
                <a href="{$link->getCategoryLink(523)}" class="mz-nav-link">
                    <div class="mz-link-content">
                        <i class="fa-solid fa-heart-pulse icon-main"></i>
                        <span>Zdrowie i Dieta</span>
                    </div>
                    <i class="fa-solid fa-chevron-right arrow"></i>
                </a>
                <div class="mz-content-panel">
                    <div class="mz-inner-grid">
                        
                        {* Diabetycy *}
                        <div class="mz-sub-group">
                            <div class="mz-sub-header"><i class="fa-solid fa-person-cane"></i> <a href="{$link->getCategoryLink(966)}">Dla diabetyków</a></div>
                            <ul class="mz-deep-list">
                                <li><a href="{$link->getCategoryLink(967)}">Herbata dla diabetyków</a></li>
                                <li><a href="{$link->getCategoryLink(966)}">Produkty dla diabetyków</a></li>
                            </ul>
                        </div>

                         {* Superfood *}
                         <div class="mz-sub-group">
                            <div class="mz-sub-header"><i class="fa-solid fa-bolt"></i> <a href="{$link->getCategoryLink(805)}">Superfood</a></div>
                            <ul class="mz-deep-list">
                                <li><a href="{$link->getCategoryLink(901)}">Białko konopne</a></li>
                                <li><a href="{$link->getCategoryLink(1019)}">Camu Camu</a></li>
                                <li><a href="{$link->getCategoryLink(977)}">Chia</a></li>
                                <li><a href="{$link->getCategoryLink(988)}">Guarana</a></li>
                                <li><a href="{$link->getCategoryLink(261697)}">Jagody Acai</a></li>
                                <li><a href="{$link->getCategoryLink(806)}">Nasiona Kakao</a></li>
                                <li><a href="{$link->getCategoryLink(947)}">Spirulina</a></li>
                                <li><a href="{$link->getCategoryLink(946)}">Zielony Jęczmień</a></li>
                            </ul>
                        </div>

                        {* Zdrowa Żywność *}
                        <div class="mz-sub-group">
                            <div class="mz-sub-header"><i class="fa-solid fa-apple-whole"></i> <a href="{$link->getCategoryLink(523)}">Zdrowa żywność</a></div>
                            <ul class="mz-deep-list">
                                <li><a href="{$link->getCategoryLink(531)}">Fit słodycze</a></li>
                                <li><a href="{$link->getCategoryLink(694)}">Płatki drożdżowe</a></li>
                                <li><a href="{$link->getCategoryLink(805)}">Superfood</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

        </div> {* Koniec .mz-left-nav *}

        {* --- PRAWA KOLUMNA: DOMYŚLNY WIDOK (HOOK Z PRODUKTEM) --- *}
        {* To pole jest widoczne, gdy nie najedziesz na żadną kategorię *}
        <div class="mz-default-placeholder">
             <div class="mz-hook-wrapper">
                 {hook h='displayMenuSpozywczeDeal'}
             </div>
        </div>

    </div> {* Koniec .mz-main-container *}
</div>