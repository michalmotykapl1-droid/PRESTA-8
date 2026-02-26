{* * TPL dla Menu "URODA" - FINAL CLEAN VERSION
 * * 1. Usunięto dolną różową linię.
 * * 2. Ikonki w nagłówkach wyrównane do góry (flex-start).
 * * 3. Klasy izolowane (prefiks "uroda-") - bezpieczne dla reszty sklepu.
 *}

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

<style>
    /* --- GŁÓWNY KONTENER --- */
    #uroda-menu-container {
        width: 100%;
        display: flex;
        background: #fff;
        min-height: 400px;
        font-family: 'Open Sans', sans-serif;
        color: #333;
        box-sizing: border-box;
        /* Usunięto border-bottom */
        border: none; 
    }
    
    #uroda-menu-container * { box-sizing: border-box; }

    /* --- LEWA STRONA (GŁÓWNE KATEGORIE) - 72% --- */
    .uroda-left-panel {
        flex: 0 0 72%;
        max-width: 72%;
        padding: 25px 30px;
        display: grid;
        grid-template-columns: repeat(3, 1fr); 
        gap: 20px;
        border-right: 1px solid #eee;
    }

    /* --- PRAWA STRONA (SIDEBAR) - 28% --- */
    .uroda-right-panel {
        flex: 0 0 28%;
        max-width: 28%;
        display: flex;
        flex-direction: column;
    }

    /* CZĘŚĆ 1: HIGIENA (Góra prawej kolumny) */
    .uroda-sidebar-top {
        padding: 25px 25px 15px 25px;
        flex-grow: 1; 
        background: #fff;
    }

    /* CZĘŚĆ 2: BLOG (Dół prawej kolumny) */
    .uroda-sidebar-bottom {
        background: #fff0f6;
        padding: 20px;
        text-align: center;
        border-top: 1px solid #fce4ec;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
    }

    /* --- STYLISTYKA GRUP I NAGŁÓWKÓW --- */
    .uroda-cat-group { margin-bottom: 20px; }

    .uroda-cat-header {
        font-size: 13px;
        font-weight: 800;
        text-transform: uppercase;
        color: #222;
        margin-bottom: 8px;
        
        /* Zmiana: Ikonki do góry */
        display: flex;
        align-items: flex-start; 
        
        padding-bottom: 5px;
        border-bottom: 1px solid #f8bbd0;
        line-height: 1.2;
    }
    
    .uroda-cat-header i {
        color: #D81B60;
        font-size: 14px;
        width: 22px; 
        text-align: center; 
        margin-right: 8px;
        /* Drobna korekta, żeby ikonka równała się z pierwszą linią tekstu */
        margin-top: 1px; 
    }
    
    .uroda-cat-header a { text-decoration: none; color: #222; transition: color 0.2s; }
    .uroda-cat-header a:hover { color: #D81B60; }

    /* --- LISTY LINKÓW --- */
    .uroda-link-ul {
        list-style: none !important;
        padding: 0 !important;
        margin: 0 !important;
    }
    .uroda-link-ul li { margin-bottom: 3px; }
    
    .uroda-link-item {
        font-size: 12px !important; 
        font-weight: 400 !important;
        color: #555 !important;
        text-decoration: none !important;
        display: block;
        line-height: 1.3;
        transition: 0.2s;
    }
    .uroda-link-item:hover {
        color: #D81B60 !important;
        padding-left: 3px;
    }
    
    /* Wytłuszczenie */
    .uroda-bold { font-weight: 600 !important; color: #333 !important; }

    /* --- STYL BLOGA --- */
    .uroda-promo-title {
        font-size: 14px;
        font-weight: 800;
        color: #D81B60;
        margin: 0 0 5px 0;
        text-transform: uppercase;
    }
    .uroda-promo-text {
        font-size: 11px;
        color: #666;
        margin-bottom: 10px;
        line-height: 1.3;
    }
    .uroda-promo-btn {
        background: #D81B60;
        color: #fff !important;
        padding: 6px 15px;
        border-radius: 20px;
        text-decoration: none !important;
        font-size: 10px;
        font-weight: 700;
        text-transform: uppercase;
        display: inline-block;
    }
    .uroda-promo-btn:hover { background: #ad1457; }

</style>

<div id="uroda-menu-container">

    {* --- CZĘŚĆ LEWA: 3 KOLUMNY --- *}
    <div class="uroda-left-panel">
        
        {* KOLUMNA 1: TWARZ *}
        <div>
            <div class="uroda-cat-group">
                <div class="uroda-cat-header">
                    <i class="fa-regular fa-face-smile"></i> 
                    <a href="{$link->getCategoryLink(818)}">Twarz</a>
                </div>
                <ul class="uroda-link-ul">
                    <li><a href="{$link->getCategoryLink(834)}" class="uroda-link-item">Kremy do twarzy</a></li>
                    <li><a href="{$link->getCategoryLink(842)}" class="uroda-link-item">Serum do twarzy</a></li>
                    <li><a href="{$link->getCategoryLink(819)}" class="uroda-link-item">Oczyszczanie i demakijaż</a></li>
                    <li><a href="{$link->getCategoryLink(861)}" class="uroda-link-item">Toniki i hydrolaty</a></li>
                    <li><a href="{$link->getCategoryLink(1000)}" class="uroda-link-item">Maseczki</a></li>
                    <li><a href="{$link->getCategoryLink(864)}" class="uroda-link-item">Kremy pod oczy</a></li>
                    <li><a href="{$link->getCategoryLink(841)}" class="uroda-link-item">Peelingi i scruby</a></li>
                    <li><a href="{$link->getCategoryLink(871)}" class="uroda-link-item">Olejki do twarzy</a></li>
                    <li><a href="{$link->getCategoryLink(867)}" class="uroda-link-item">Pielęgnacja ust</a></li>
                </ul>
            </div>
            
            {* DLA MĘŻCZYZN *}
            <div class="uroda-cat-group">
                <div class="uroda-cat-header">
                    <i class="fa-solid fa-user-tie"></i> 
                    <a href="{$link->getCategoryLink(863)}">Dla Mężczyzn</a>
                </div>
            </div>
        </div>

        {* KOLUMNA 2: WŁOSY + DŁONIE *}
        <div>
            <div class="uroda-cat-group">
                <div class="uroda-cat-header">
                    <i class="fa-solid fa-scissors"></i> 
                    <a href="{$link->getCategoryLink(759)}">Włosy</a>
                </div>
                <ul class="uroda-link-ul">
                    <li><a href="{$link->getCategoryLink(760)}" class="uroda-link-item">Szampony</a></li>
                    <li><a href="{$link->getCategoryLink(870)}" class="uroda-link-item">Odżywki i Maski</a></li>
                    <li><a href="{$link->getCategoryLink(839)}" class="uroda-link-item">Olejki i Wcierki</a></li>
                    <li><a href="{$link->getCategoryLink(868)}" class="uroda-link-item">Koloryzacja</a></li>
                    <li><a href="{$link->getCategoryLink(1003)}" class="uroda-link-item">Szampony suche</a></li>
                    <li><a href="{$link->getCategoryLink(869)}" class="uroda-link-item">Szczotki i grzebienie</a></li>
                </ul>
            </div>

            <div class="uroda-cat-group">
                <div class="uroda-cat-header">
                    <i class="fa-solid fa-hands"></i> 
                    <a href="{$link->getCategoryLink(835)}">Dłonie i Stopy</a>
                </div>
                <ul class="uroda-link-ul">
                    <li><a href="{$link->getCategoryLink(836)}" class="uroda-link-item">Kremy do rąk</a></li>
                    <li><a href="{$link->getCategoryLink(997)}" class="uroda-link-item">Kremy do stóp</a></li>
                    <li><a href="{$link->getCategoryLink(991)}" class="uroda-link-item">Dezodoranty do stóp</a></li>
                </ul>
            </div>
        </div>

        {* KOLUMNA 3: CIAŁO + PERFUMY *}
        <div>
            <div class="uroda-cat-group">
                <div class="uroda-cat-header">
                    <i class="fa-solid fa-child-reaching"></i> 
                    <a href="{$link->getCategoryLink(822)}">Ciało i Kąpiel</a>
                </div>
                <ul class="uroda-link-ul">
                    <li><a href="{$link->getCategoryLink(823)}" class="uroda-link-item">Kąpiel i prysznic</a></li>
                    <li><a href="{$link->getCategoryLink(830)}" class="uroda-link-item">Balsamy i masła</a></li>
                    <li><a href="{$link->getCategoryLink(840)}" class="uroda-link-item">Peelingi do ciała</a></li>
                    <li><a href="{$link->getCategoryLink(843)}" class="uroda-link-item">Antyperspiranty</a></li>
                    <li><a href="{$link->getCategoryLink(999)}" class="uroda-link-item">Wyszczuplające</a></li>
                    <li><a href="{$link->getCategoryLink(832)}" class="uroda-link-item">Opalanie i słońce</a></li>
                </ul>
            </div>

             <div class="uroda-cat-group">
                <div class="uroda-cat-header">
                    <i class="fa-solid fa-spray-can-sparkles"></i> 
                    <a href="{$link->getCategoryLink(844)}">Perfumy</a>
                </div>
                <ul class="uroda-link-ul">
                    <li><a href="{$link->getCategoryLink(846)}" class="uroda-link-item">Zapachy dla kobiet</a></li>
                    <li><a href="{$link->getCategoryLink(262526)}" class="uroda-link-item">Zapachy unisex</a></li>
                </ul>
            </div>
        </div>
        
    </div>

    {* --- CZĘŚĆ PRAWA: SIDEBAR (HIGIENA + BLOG) --- *}
    <div class="uroda-right-panel">
        
        {* GÓRA: HIGIENA OSOBISTA (ID 715) *}
        <div class="uroda-sidebar-top">
            <div class="uroda-cat-group">
                <div class="uroda-cat-header" style="border-color: #e91e63;">
                    <i class="fa-solid fa-soap"></i> 
                    <a href="{$link->getCategoryLink(715)}">Higiena Osobista</a>
                </div>
                <ul class="uroda-link-ul">
                    {* Sekcja Intymna *}
                    <li><a href="{$link->getCategoryLink(848)}" class="uroda-link-item uroda-bold">Podpaski i Tampony</a></li>
                    <li><a href="{$link->getCategoryLink(270253)}" class="uroda-link-item">Kubeczki menstruacyjne</a></li>
                    <li><a href="{$link->getCategoryLink(827)}" class="uroda-link-item">Płyny do higieny intymnej</a></li>
                    
                    <li style="margin-top:5px;"><a href="{$link->getCategoryLink(270206)}" class="uroda-link-item uroda-bold">Płatki i Waciki</a></li>
                    <li><a href="{$link->getCategoryLink(817)}" class="uroda-link-item">Patyczki higieniczne</a></li>
                    <li><a href="{$link->getCategoryLink(716)}" class="uroda-link-item">Artykuły higieniczne</a></li>
                    
                    <li style="margin-top:5px;"><a href="{$link->getCategoryLink(270250)}" class="uroda-link-item">Nietrzymanie moczu</a></li>
                </ul>
            </div>
        </div>

        {* DÓŁ: BLOG (ZMINIMALIZOWANY) *}
        <div class="uroda-sidebar-bottom">
            <h4 class="uroda-promo-title">Porady Beauty</h4>
            <p class="uroda-promo-text">Odkryj naturalne rytuały piękna.</p>
            <a href="https://www.2.bigbio.pl/index.php?id_category=7&fc=module&module=ets_blog&controller=blog&id_lang=2" class="uroda-promo-btn">
                Blog <i class="fa-solid fa-caret-right"></i>
            </a>
        </div>
        
    </div>

</div>