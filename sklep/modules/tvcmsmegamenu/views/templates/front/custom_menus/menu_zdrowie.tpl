{* * TPL dla Menu "ZDROWIE" - FINALNA WERSJA DESKTOP
 * * Poprawiony Grid (brak ujemnych marginesów).
 * * Zmieniony nagłówek na profesjonalny.
 * * ID #mz-custom-health zapewnia priorytet stylów.
 *}

<style>
    /* --- RESET KONTENERA --- */
    #mz-custom-health {
        width: 100%;
        display: flex;
        flex-wrap: wrap;
        background: #fff;
        min-height: 420px;
        position: relative;
        box-sizing: border-box;
        font-family: 'Open Sans', sans-serif;
        text-align: left;
        color: #333;
    }
    
    #mz-custom-health * {
        box-sizing: border-box;
    }

    /* --- LEWA KOLUMNA (LISTA) --- */
    #mz-custom-health .mz-left {
        flex: 0 0 32%; /* Szerokość lewej kolumny */
        max-width: 32%;
        padding: 35px 30px;
        border-right: 1px solid #f5f5f5;
        background: #ffffff;
        display: flex;
        flex-direction: column;
    }

    /* NAGŁÓWEK LISTY - ZMIENIONY */
    #mz-custom-health .mz-header {
        font-size: 13px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: #222;
        margin-bottom: 25px;
        padding-bottom: 12px;
        border-bottom: 2px solid #007F73; /* Linia w kolorze morskim */
        display: inline-block;
        width: 100%;
        line-height: 1.4;
    }

    #mz-custom-health ul.mz-list {
        list-style: none !important;
        padding: 0 !important;
        margin: 0 !important;
    }

    #mz-custom-health ul.mz-list li {
        margin-bottom: 6px;
        list-style: none !important;
    }

    /* Linki w lewej kolumnie */
    #mz-custom-health ul.mz-list li a {
        font-size: 14px;
        color: #555 !important; 
        text-decoration: none !important;
        transition: all 0.2s ease;
        display: flex;
        align-items: center;
        padding: 8px 0; 
        background: transparent !important;
        font-weight: 500 !important;
    }

    /* Hover linku */
    #mz-custom-health ul.mz-list li a:hover {
        color: #007F73 !important;
        padding-left: 5px; /* Animacja przesunięcia */
    }
    
    /* Ikony przy linkach */
    #mz-custom-health ul.mz-list li a i {
        margin-right: 12px;
        color: #ccc; /* Jasne ikony */
        font-size: 14px;
        width: 20px;
        text-align: center;
        transition: color 0.2s;
    }
    
    #mz-custom-health ul.mz-list li a:hover i {
        color: #007F73; /* Ikona zmienia kolor na hover */
    }

    /* Link "Zobacz wszystkie" na dole lewej kolumny */
    #mz-custom-health .mz-more-link {
        margin-top: auto;
        padding-top: 20px;
        display: inline-flex;
        align-items: center;
        font-size: 13px;
        font-weight: 700;
        text-decoration: none !important;
        color: #007F73 !important;
        transition: opacity 0.2s;
    }
    #mz-custom-health .mz-more-link:hover {
        opacity: 0.8;
        text-decoration: underline !important;
    }
    #mz-custom-health .mz-more-link i {
        margin-left: 8px;
        font-size: 12px;
    }

    /* --- PRAWA KOLUMNA (KAFELKI) --- */
    #mz-custom-health .mz-right {
        flex: 0 0 68%;
        max-width: 68%;
        padding: 35px 40px; /* Duże odstępy = czysty wygląd */
        background: #fbfbfb; 
        display: flex;
        flex-direction: column;
    }

    #mz-custom-health .mz-top-content {
        margin-bottom: 30px;
    }

    /* Tag nad tytułem */
    #mz-custom-health .mz-tag {
        background: #e0f2f1;
        color: #007F73;
        font-size: 10px;
        font-weight: 800;
        padding: 5px 10px;
        border-radius: 4px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        display: inline-block;
        margin-bottom: 12px;
    }

    #mz-custom-health .mz-title {
        font-size: 22px;
        font-weight: 800;
        color: #1a1a1a;
        margin: 0 0 8px 0;
        line-height: 1.2;
    }

    #mz-custom-health .mz-desc {
        font-size: 13px;
        color: #666;
        max-width: 85%;
        line-height: 1.6;
        margin: 0;
    }

    /* --- GRID (SIATKA KAFELKÓW) - POPRAWIONA --- */
    #mz-custom-health .mz-grid {
        display: grid;
        grid-template-columns: 1fr 1fr; /* Dwie kolumny */
        gap: 20px; /* Odstępy między kafelkami */
        width: 100%;
        margin-top: 10px;
        /* Usunięte ujemne marginesy, które psuły układ */
    }

    /* --- KAFELEK (CARD) --- */
    #mz-custom-health a.mz-card {
        display: flex !important;
        flex-direction: row !important;
        align-items: center !important;
        justify-content: flex-start !important;
        
        width: 100% !important;
        padding: 15px 20px !important;
        background: #fff !important;
        border: 1px solid #eaeaea !important; 
        border-radius: 8px !important;
        text-decoration: none !important;
        
        transition: all 0.2s ease-in-out;
    }

    #mz-custom-health a.mz-card:hover {
        border-color: #007F73 !important;
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(0,127,115, 0.1) !important;
    }
    
    #mz-custom-health .mz-icon {
        width: 45px !important;
        height: 45px !important;
        min-width: 45px !important;
        background: #f0fdfc;
        border-radius: 50%;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        margin-right: 15px !important;
        color: #007F73;
        font-size: 20px !important;
    }

    #mz-custom-health .mz-info {
        display: flex !important;
        flex-direction: column !important;
        justify-content: center !important;
        text-align: left !important;
    }

    #mz-custom-health .mz-info h5 {
        font-size: 14px !important;
        font-weight: 700 !important;
        color: #333 !important;
        margin: 0 0 3px 0 !important;
        text-transform: uppercase !important;
        letter-spacing: 0.5px;
    }

    #mz-custom-health .mz-info p {
        font-size: 11px !important;
        color: #888 !important;
        margin: 0 !important;
        font-weight: 500;
    }

    /* --- BUTTON DOLNY --- */
    #mz-custom-health a.mz-btn {
        display: flex !important;
        justify-content: center !important;
        align-items: center !important;
        text-align: center !important;
        
        width: 100%;
        margin-top: 25px;
        padding: 12px;
        
        background-color: transparent !important;
        border: 1px solid #007F73 !important;
        color: #007F73 !important;
        
        border-radius: 6px;
        font-weight: 700;
        text-transform: uppercase;
        font-size: 12px;
        letter-spacing: 1px;
        text-decoration: none !important;
        
        transition: all 0.3s ease;
    }

    #mz-custom-health a.mz-btn:hover {
        background-color: #007F73 !important;
        color: #ffffff !important;
    }

</style>

<div id="mz-custom-health">

    {* --- LEWA STRONA (Lista kategorii) --- *}
    <div class="mz-left">
        {* ZMIANA: Profesjonalny nagłówek odnoszący się do zawartości *}
        <span class="mz-header">Wsparcie dla Twojego organizmu</span>
        
        <ul class="mz-list">
            <li><a href="{$link->getCategoryLink(589)}"><i class="fa-solid fa-shield-virus"></i> Odporność</a></li>
            <li><a href="{$link->getCategoryLink(684)}"><i class="fa-solid fa-pills"></i> Witaminy i Minerały</a></li>
            <li><a href="{$link->getCategoryLink(937)}"><i class="fa-solid fa-heart-pulse"></i> Serce i Krążenie</a></li>
            <li><a href="{$link->getCategoryLink(935)}"><i class="fa-solid fa-brain"></i> Pamięć i Koncentracja</a></li>
            <li><a href="{$link->getCategoryLink(681)}"><i class="fa-solid fa-person-running"></i> Stawy i Kości</a></li>
            <li><a href="{$link->getCategoryLink(632)}"><i class="fa-solid fa-seedling"></i> Zioła</a></li>
            <li><a href="{$link->getCategoryLink(942)}"><i class="fa-regular fa-gem"></i> Skóra, włosy, paznokcie</a></li>
            <li><a href="{$link->getCategoryLink(680)}"><i class="fa-solid fa-apple-whole"></i> Układ pokarmowy</a></li>
        </ul>

        <a href="{$link->getCategoryLink(586)}" class="mz-more-link">
            Zobacz wszystkie produkty <i class="fa-solid fa-arrow-right"></i>
        </a>
    </div>

    {* --- PRAWA STRONA (Kafelki) --- *}
    <div class="mz-right">
        
        <div class="mz-top-content">
            <span class="mz-tag">Dyplomowani Specjaliści</span>
            <h3 class="mz-title">Twoje zdrowie w dobrych rękach</h3>
            <p class="mz-desc">
                Odzyskaj sprawność dzięki fizjoterapii, zadbaj o wygląd w strefie beauty i osiągnij harmonię z naturopatą.
            </p>
        </div>

        <div class="mz-grid">
            
            {* FIZJOTERAPIA *}
            <a href="https://www.2.bigbio.pl/index.php?strona=fizjoterapia&fc=module&module=tvcmsstrefazdrowia&controller=display&id_lang=2" class="mz-card">
                <div class="mz-icon"><i class="fa-solid fa-person-walking"></i></div>
                <div class="mz-info">
                    <h5>Fizjoterapia</h5>
                    <p>Rehabilitacja i masaż</p>
                </div>
            </a>

            {* NATUROPATIA *}
            <a href="https://www.2.bigbio.pl/index.php?strona=naturopatia&fc=module&module=tvcmsstrefazdrowia&controller=display&id_lang=2" class="mz-card">
                <div class="mz-icon"><i class="fa-solid fa-leaf"></i></div>
                <div class="mz-info">
                    <h5>Naturopatia</h5>
                    <p>Konsultacje holistyczne</p>
                </div>
            </a>

            {* PORADY & URODA *}
            <a href="https://www.2.bigbio.pl/index.php?strona=uroda&fc=module&module=tvcmsstrefazdrowia&controller=display&id_lang=2" class="mz-card">
                <div class="mz-icon"><i class="fa-solid fa-spa"></i></div>
                <div class="mz-info">
                    <h5>Porady & Uroda</h5>
                    <p>Fachowa wiedza i zabiegi</p>
                </div>
            </a>

            {* DIAGNOSTYKA *}
            <a href="https://www.2.bigbio.pl/index.php?fc=module&module=tvcmsstrefazdrowia&controller=display&id_lang=2" class="mz-card">
                <div class="mz-icon"><i class="fa-solid fa-stethoscope"></i></div>
                <div class="mz-info">
                    <h5>Diagnostyka</h5>
                    <p>Terapie wsparcia</p>
                </div>
            </a>

        </div>

        {* BUTTON GŁÓWNY *}
        <a href="https://www.2.bigbio.pl/index.php?fc=module&module=tvcmsstrefazdrowia&controller=display&id_lang=2" class="mz-btn">
            Odkryj Strefę Zdrowia i Równowagi
        </a>

    </div>

</div>