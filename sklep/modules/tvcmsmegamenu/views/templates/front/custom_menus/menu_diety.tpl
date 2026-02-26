{* * TPL dla Menu "DIETY" - WERSJA FIX BUTTON FINAL (wzorowana na Zdrowiu)
 * * Naprawiony przycisk "Zobacz wszystkie" (wymuszone style borders).
 *}

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

<style>
    /* --- RESET KONTENERA --- */
    #mz-custom-diet {
        width: 100%;
        display: flex;
        flex-wrap: wrap;
        background: #fff;
        min-height: 380px; 
        position: relative;
        box-sizing: border-box;
        font-family: 'Open Sans', sans-serif;
        text-align: left;
        color: #333;
    }
    
    #mz-custom-diet * {
        box-sizing: border-box;
    }

    /* --- LEWA KOLUMNA (INTRO) --- */
    #mz-custom-diet .mz-left {
        flex: 0 0 30%;
        max-width: 30%;
        padding: 40px 35px;
        border-right: 1px solid #f5f5f5;
        background: #ffffff;
        display: flex;
        flex-direction: column;
        justify-content: center; 
    }

    /* Tag nad nagłówkiem */
    #mz-custom-diet .mz-tag {
        background: #fff0f3; 
        color: #D90244;
        font-size: 10px;
        font-weight: 800;
        padding: 5px 10px;
        border-radius: 4px;
        text-transform: uppercase;
        letter-spacing: 1px;
        display: inline-block;
        margin-bottom: 15px;
        align-self: flex-start;
    }

    /* Nagłówek lewej kolumny */
    #mz-custom-diet .mz-header {
        font-size: 24px;
        font-weight: 800;
        text-transform: none; 
        color: #1a1a1a;
        margin-bottom: 15px;
        line-height: 1.2;
    }

    /* Opis */
    #mz-custom-diet .mz-desc {
        font-size: 13px;
        color: #666;
        line-height: 1.6;
        margin-bottom: 30px;
    }

    /* --- LINK "ZOBACZ WSZYSTKIE" (NAPRAWIONY - METODA ZE ZDROWIA) --- */
    #mz-custom-diet .mz-btn {
        /* Wymuszamy display flex */
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        
        padding: 12px 25px !important;
        margin-top: 10px; /* Mały odstęp od tekstu */
        
        /* Styl obramowania rozbity na czynniki pierwsze dla pewności */
        border-width: 1px !important;
        border-style: solid !important;
        border-color: #D90244 !important;
        
        background-color: transparent !important;
        background-image: none !important; /* Usuwa gradienty z motywu */
        
        color: #D90244 !important;
        border-radius: 4px !important;
        
        font-weight: 700 !important;
        font-size: 12px !important;
        text-transform: uppercase !important;
        text-decoration: none !important;
        letter-spacing: 0.5px;
        
        transition: all 0.3s ease;
        align-self: flex-start;
        cursor: pointer;
        
        /* Reset cieni i innych efektów motywu */
        box-shadow: none !important;
        text-shadow: none !important;
    }

    #mz-custom-diet .mz-btn:hover {
        background-color: #D90244 !important;
        color: #fff !important;
        border-color: #D90244 !important;
    }
    
    /* Usuwamy ewentualne pseudo-elementy z motywu */
    #mz-custom-diet .mz-btn:before,
    #mz-custom-diet .mz-btn:after {
        display: none !important;
    }

    /* --- PRAWA KOLUMNA (GRID) --- */
    #mz-custom-diet .mz-right {
        flex: 0 0 70%;
        max-width: 70%;
        padding: 35px 40px;
        background: #fdfdfd; 
        display: flex;
        align-items: center; 
    }

    /* SIATKA 4 kolumny x 2 rzędy */
    #mz-custom-diet .mz-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr); 
        gap: 15px; 
        width: 100%;
    }

    /* --- KAFELEK (CARD) --- */
    #mz-custom-diet a.mz-card {
        display: flex !important;
        flex-direction: column !important; 
        align-items: center !important;
        justify-content: center !important;
        
        height: 110px; 
        padding: 10px !important;
        
        background: #fff !important;
        border: 1px solid #eaeaea !important; 
        border-radius: 8px !important;
        text-decoration: none !important;
        
        transition: all 0.2s ease-in-out;
    }

    #mz-custom-diet a.mz-card:hover {
        border-color: #D90244 !important;
        transform: translateY(-3px);
        box-shadow: 0 5px 15px rgba(217, 2, 68, 0.1) !important;
    }
    
    #mz-custom-diet .mz-icon {
        font-size: 28px !important;
        color: #333;
        margin-bottom: 10px !important;
        transition: color 0.2s;
    }
    
    #mz-custom-diet a.mz-card:hover .mz-icon {
        color: #D90244;
    }

    #mz-custom-diet .mz-text {
        font-size: 12px !important;
        font-weight: 700 !important;
        color: #333 !important;
        text-transform: uppercase !important;
        text-align: center;
        line-height: 1.2;
    }

    @media (max-width: 1200px) {
        #mz-custom-diet .mz-grid {
            grid-template-columns: repeat(3, 1fr); 
        }
    }

</style>

<div id="mz-custom-diet">

    {* --- LEWA STRONA (Informacyjna) --- *}
    <div class="mz-left">
        <span class="mz-tag">Twoje Zdrowie</span>
        <h2 class="mz-header">Wspieramy Twój styl odżywiania</h2>
        
        <p class="mz-desc">
            Stworzyliśmy dedykowane kategorie, aby ułatwić Ci znalezienie produktów pasujących do Twoich potrzeb. Oszczędzaj czas i szybciej odkrywaj żywność zgodną z Twoją dietą.
        </p>

        <a href="{$link->getCategoryLink(2)}" class="mz-btn">
            Zobacz wszystkie diety <i class="fa-solid fa-arrow-right" style="margin-left:8px;"></i>
        </a>
    </div>

    {* --- PRAWA STRONA (Siatka ikon) --- *}
    <div class="mz-right">
        <div class="mz-grid">
            
            <a href="index.php?id_category=264208&controller=category&id_lang=2" class="mz-card">
                <div class="mz-icon"><i class="fa-solid fa-bread-slice"></i></div>
                <span class="mz-text">Bez Glutenu</span>
            </a>

            <a href="index.php?id_category=264210&controller=category&id_lang=2" class="mz-card">
                <div class="mz-icon"><i class="fa-solid fa-carrot"></i></div>
                <span class="mz-text">Wegetariańskie</span>
            </a>

            <a href="index.php?id_category=264209&controller=category&id_lang=2" class="mz-card">
                <div class="mz-icon"><i class="fa-solid fa-seedling"></i></div>
                <span class="mz-text">Wegańskie</span>
            </a>

            <a href="index.php?id_category=264207&controller=category&id_lang=2" class="mz-card">
                <div class="mz-icon"><i class="fa-solid fa-glass-water"></i></div>
                <span class="mz-text">Bez Laktozy</span>
            </a>

            <a href="index.php?id_category=264205&controller=category&id_lang=2" class="mz-card">
                <div class="mz-icon"><i class="fa-solid fa-leaf"></i></div>
                <span class="mz-text">Bio / Organic</span>
            </a>

            <a href="index.php?id_category=264211&controller=category&id_lang=2" class="mz-card">
                <div class="mz-icon"><i class="fa-solid fa-bolt"></i></div>
                <span class="mz-text">Keto / Low-Carb</span>
            </a>

            <a href="index.php?id_category=264206&controller=category&id_lang=2" class="mz-card">
                <div class="mz-icon"><i class="fa-solid fa-cube"></i></div>
                <span class="mz-text">Bez Cukru</span>
            </a>

            <a href="index.php?id_category=264212&controller=category&id_lang=2" class="mz-card">
                <div class="mz-icon"><i class="fa-solid fa-arrow-trend-down"></i></div>
                <span class="mz-text">Niski Indeks</span>
            </a>

        </div>
    </div>

</div>