{* * TPL dla Menu "UTRZYMANIE CZYSTOŚCI" - FINAL FIX (CSV IDs MATCH)
 * * 1. IDs kategorii w 100% zgodne z plikiem node(2).csv
 * * 2. Układ 3 kolumnowy zachowany.
 * * 3. Style (12px, font-weight 400) zachowane.
 *}

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

<style>
    /* --- GŁÓWNY KONTENER --- */
    #mz-custom-clean {
        width: 100%;
        display: flex;
        flex-wrap: wrap;
        background: #fff;
        min-height: 400px;
        position: relative;
        box-sizing: border-box;
        font-family: 'Open Sans', sans-serif;
        color: #333;
        padding: 0;
    }
    
    #mz-custom-clean * { box-sizing: border-box; }

    /* Kolor akcentu (Morski) */
    :root { --clean-accent: #00838F; }

    /* --- LEWA STRONA (KATEGORIE) --- */
    #mz-custom-clean .mz-categories {
        flex: 0 0 75%;
        max-width: 75%;
        padding: 35px 40px;
        display: grid;
        grid-template-columns: repeat(3, 1fr); 
        gap: 30px;
        border-right: 1px solid #f0f0f0;
    }

    /* Grupa kategorii */
    .mz-clean-group { margin-bottom: 20px; }

    /* --- NAGŁÓWEK KATEGORII (IKONA NA GÓRZE) --- */
    .mz-clean-header {
        font-size: 13px;
        font-weight: 800;
        text-transform: uppercase;
        color: #222;
        margin-bottom: 8px;
        
        /* KLUCZOWE: Wyrównanie do góry */
        display: flex;
        align-items: flex-start; 
        
        padding-bottom: 8px;
        border-bottom: 1px solid #e0f7fa;
        line-height: 1.3;
    }
    
    .mz-clean-header i {
        color: var(--clean-accent);
        font-size: 16px;
        
        /* Sztywna szerokość kontenera ikony */
        width: 24px; 
        min-width: 24px;
        text-align: center; 
        margin-right: 8px;
        
        /* Odsunięcie od góry o 1px, żeby zrównać się z tekstem */
        margin-top: 1px; 
        display: block; 
    }
    
    .mz-clean-header a {
        text-decoration: none;
        color: #222;
        transition: color 0.2s;
    }
    .mz-clean-header a:hover { color: var(--clean-accent); }

    /* --- LISTA PODKATEGORII (DELIKATNA) --- */
    .mz-clean-list {
        list-style: none !important;
        padding: 0 !important;
        margin: 0 !important;
    }
    
    .mz-clean-list li { margin-bottom: 3px; }
    
    .mz-clean-list a {
        /* Styl "Delikatny" - zgodny z prośbą */
        font-size: 12px !important; 
        font-weight: 400 !important; /* Cienka */
        color: #555 !important;      /* Szary */
        
        text-decoration: none !important;
        transition: all 0.2s;
        display: block;
        padding-left: 0;
        line-height: 1.4;
    }
    
    .mz-clean-list a:hover {
        color: var(--clean-accent) !important;
        padding-left: 4px;
    }

    /* --- PRAWA STRONA (BLOG) --- */
    #mz-custom-clean .mz-blog-col {
        flex: 0 0 25%;
        max-width: 25%;
        background: #f0fdfc; 
        padding: 35px 30px;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        text-align: center;
        position: relative;
        overflow: hidden;
    }

    /* Tło ozdobne */
    .mz-blog-col::before {
        content: '\f4d8';
        font-family: "Font Awesome 6 Free";
        font-weight: 900;
        position: absolute;
        top: -20px;
        right: -20px;
        font-size: 150px;
        color: var(--clean-accent);
        opacity: 0.05;
        z-index: 0;
    }

    .mz-blog-icon {
        font-size: 40px;
        color: var(--clean-accent);
        margin-bottom: 20px;
        background: #fff;
        width: 80px;
        height: 80px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 5px 15px rgba(0,131,143, 0.1);
        z-index: 1;
    }

    .mz-blog-title {
        font-size: 18px;
        font-weight: 800;
        color: #222;
        margin-bottom: 10px;
        z-index: 1;
        line-height: 1.3;
    }

    .mz-blog-desc {
        font-size: 12px;
        color: #666;
        line-height: 1.6;
        margin-bottom: 25px;
        z-index: 1;
        font-weight: 400;
    }

    .mz-blog-btn {
        background: var(--clean-accent);
        color: #fff !important;
        padding: 10px 25px;
        border-radius: 30px;
        text-decoration: none !important;
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 1px;
        transition: all 0.3s;
        z-index: 1;
        box-shadow: 0 4px 10px rgba(0,131,143, 0.2);
    }

    .mz-blog-btn:hover {
        background: #006064;
        transform: translateY(-2px);
    }

</style>

<div id="mz-custom-clean">

    {* --- LEWA STRONA: KATEGORIE --- *}
    <div class="mz-categories">
        
        {* --- KOLUMNA 1: PRANIE i ZAPACH --- *}
        <div class="mz-col">
            
            {* ŚRODKI DO PRANIA (ID 742 z pliku CSV) *}
            <div class="mz-clean-group">
                <div class="mz-clean-header">
                    <i class="fa-solid fa-shirt"></i> 
                    <a href="{$link->getCategoryLink(742)}">Środki do prania</a>
                </div>
                <ul class="mz-clean-list">
                    <li><a href="{$link->getCategoryLink(748)}">Płyny i żele do prania</a></li>
                    <li><a href="{$link->getCategoryLink(747)}">Proszki do prania</a></li>
                    <li><a href="{$link->getCategoryLink(743)}">Kapsułki i tabletki</a></li>
                    <li><a href="{$link->getCategoryLink(746)}">Płyny do płukania</a></li>
                    <li><a href="{$link->getCategoryLink(744)}">Odplamiacze</a></li>
                    <li><a href="{$link->getCategoryLink(745)}">Orzechy do prania</a></li>
                </ul>
            </div>

            {* ZAPACH W DOMU / ODŚWIEŻACZE (ID 737 z pliku CSV) *}
            <div class="mz-clean-group">
                <div class="mz-clean-header">
                    <i class="fa-solid fa-wind"></i> 
                    <a href="{$link->getCategoryLink(737)}">Zapach w domu</a>
                </div>
                <ul class="mz-clean-list">
                    <li><a href="{$link->getCategoryLink(737)}">Odświeżacze powietrza</a></li>
                    {* Brak świec w CSV, kierujemy do odświeżaczy lub usuwamy linię *}
                </ul>
            </div>

        </div>

        {* --- KOLUMNA 2: ZMYWANIE --- *}
        <div class="mz-col">
            
            {* ŚRODKI DO ZMYWANIA (ID 776 z pliku CSV) *}
            <div class="mz-clean-group">
                <div class="mz-clean-header">
                    <i class="fa-solid fa-sink"></i> 
                    <a href="{$link->getCategoryLink(776)}">Zmywanie</a>
                </div>
                <ul class="mz-clean-list">
                    <li><a href="{$link->getCategoryLink(778)}">Płyny do naczyń</a></li>
                    <li><a href="{$link->getCategoryLink(781)}">Tabletki do zmywarki</a></li>
                    <li><a href="{$link->getCategoryLink(780)}">Sole do zmywarki</a></li>
                    <li><a href="{$link->getCategoryLink(779)}">Nabłyszczacze do zmywarki</a></li>
                    <li><a href="{$link->getCategoryLink(777)}">Czyściki do zmywarki</a></li>
                </ul>
            </div>

             {* POZOSTAŁA CHEMIA (Mydła, Chemia Prof) *}
             <div class="mz-clean-group">
                <div class="mz-clean-header">
                    <i class="fa-solid fa-pump-soap"></i> 
                    <a href="{$link->getCategoryLink(736)}">Inne środki</a>
                </div>
                <ul class="mz-clean-list">
                    <li><a href="{$link->getCategoryLink(821)}">Mydła</a></li>
                    <li><a href="{$link->getCategoryLink(764)}">Chemia profesjonalna</a></li>
                </ul>
            </div>
        </div>

        {* --- KOLUMNA 3: SPRZĄTANIE i AKCESORIA --- *}
        <div class="mz-col">
            
            {* ŚRODKI CZYSZCZĄCE (ID 765 z pliku CSV) *}
            <div class="mz-clean-group">
                <div class="mz-clean-header">
                    <i class="fa-solid fa-broom"></i> 
                    <a href="{$link->getCategoryLink(765)}">Środki Czystości</a>
                </div>
                <ul class="mz-clean-list">
                    <li><a href="{$link->getCategoryLink(765)}">Uniwersalne środki czyszczące</a></li>
                    <li><a href="{$link->getCategoryLink(270259)}">Odkamieniacze</a></li>
                </ul>
            </div>

            {* AKCESORIA DO PRANIA I SPRZĄTANIA (ID 727 z pliku CSV) *}
            <div class="mz-clean-group">
                <div class="mz-clean-header">
                    <i class="fa-solid fa-hand-sparkles"></i> 
                    <a href="{$link->getCategoryLink(727)}">Akcesoria</a>
                </div>
                <ul class="mz-clean-list">
                    <li><a href="{$link->getCategoryLink(270257)}">Gąbki, zmywaki, druciaki</a></li>
                    <li><a href="{$link->getCategoryLink(766)}">Worki na śmieci</a></li>
                    <li><a href="{$link->getCategoryLink(270207)}">Ręczniki papierowe</a></li>
                    <li><a href="{$link->getCategoryLink(728)}">Szczotki</a></li>
                </ul>
            </div>
        </div>

    </div>

    {* --- PRAWA STRONA: BLOG LINK --- *}
    <div class="mz-blog-col">
        <div class="mz-blog-icon">
            <i class="fa-solid fa-book-open-reader"></i>
        </div>
        <h4 class="mz-blog-title">Eko Porady<br>dla Twojego Domu</h4>
        <p class="mz-blog-desc">
            Sprawdź nasze artykuły o tym, jak sprzątać skutecznie i bezpiecznie dla środowiska.
        </p>
        
        <a href="https://www.2.bigbio.pl/index.php?id_category=6&fc=module&module=ets_blog&controller=blog&id_lang=2" class="mz-blog-btn">
            Czytaj Bloga <i class="fa-solid fa-arrow-right" style="margin-left:5px;"></i>
        </a>
    </div>

</div>