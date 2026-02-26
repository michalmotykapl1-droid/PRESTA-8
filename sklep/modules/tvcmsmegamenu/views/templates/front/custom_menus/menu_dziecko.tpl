{* * TPL dla Menu "DZIECKO" - FULL WIDTH 4 COLUMNS
 * * 1. Brak paska bocznego i brak bloga.
 * * 2. 4 równe kolumny dla maksymalnej przejrzystości.
 * * 3. IDs zgodne z node(6).csv.
 * * 4. Izolowane klasy "baby-".
 *}

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

<style>
    /* --- GŁÓWNY KONTENER --- */
    #baby-menu-container {
        width: 100%;
        display: flex;
        background: #fff;
        min-height: 380px; /* Nieco niższe, bo szersze */
        font-family: 'Open Sans', sans-serif;
        color: #444;
        box-sizing: border-box;
        border: none;
        padding: 25px 30px; /* Padding dla całego kontenera */
    }
    
    #baby-menu-container * { box-sizing: border-box; }

    /* Kolor przewodni - Miętowy Turkus */
    :root { --baby-accent: #00BCD4; }

    /* --- UKŁAD SIATKI (4 KOLUMNY) --- */
    .baby-grid-layout {
        width: 100%;
        display: grid;
        grid-template-columns: repeat(4, 1fr); /* 4 równe kolumny */
        gap: 20px;
    }

    /* --- STYLISTYKA GRUP I NAGŁÓWKÓW --- */
    .baby-cat-group { margin-bottom: 25px; }

    .baby-cat-header {
        font-size: 13px;
        font-weight: 800;
        text-transform: uppercase;
        color: #333;
        margin-bottom: 10px;
        display: flex;
        align-items: flex-start; 
        padding-bottom: 6px;
        border-bottom: 2px solid #b2ebf2; /* Grubsza linia akcentu */
        line-height: 1.2;
    }
    
    .baby-cat-header i {
        color: var(--baby-accent);
        font-size: 16px;
        width: 24px; 
        text-align: left; 
        margin-right: 5px;
        margin-top: -1px; 
    }
    
    .baby-cat-header a { text-decoration: none; color: #333; transition: color 0.2s; }
    .baby-cat-header a:hover { color: var(--baby-accent); }

    /* --- LISTY LINKÓW --- */
    .baby-link-ul {
        list-style: none !important;
        padding: 0 !important;
        margin: 0 !important;
    }
    .baby-link-ul li { margin-bottom: 5px; }
    
    .baby-link-item {
        font-size: 13px !important; /* Nieco większa czcionka dla czytelności */
        font-weight: 400 !important;
        color: #555 !important;
        text-decoration: none !important;
        display: block;
        line-height: 1.4;
        transition: 0.2s;
    }
    .baby-link-item:hover {
        color: var(--baby-accent) !important;
        padding-left: 3px;
        font-weight: 600 !important;
    }
    
    /* Wytłuszczenie */
    .baby-bold { font-weight: 700 !important; color: #333 !important; }

</style>

<div id="baby-menu-container">

    <div class="baby-grid-layout">
        
        {* --- KOLUMNA 1: KARMIENIE I ŻYWNOŚĆ --- *}
        <div>
            {* ŻYWNOŚĆ (ID 520) *}
            <div class="baby-cat-group">
                <div class="baby-cat-header">
                    <i class="fa-solid fa-cookie-bite"></i> 
                    <a href="{$link->getCategoryLink(520)}">Żywność dla dzieci</a>
                </div>
                <ul class="baby-link-ul">
                    <li><a href="{$link->getCategoryLink(794)}" class="baby-link-item baby-bold">Mleka modyfikowane</a></li>
                    <li><a href="{$link->getCategoryLink(795)}" class="baby-link-item">- Początkowe</a></li>
                    <li><a href="{$link->getCategoryLink(796)}" class="baby-link-item">- Następne</a></li>
                    <li><a href="{$link->getCategoryLink(568)}" class="baby-link-item">Kaszki i kleiki</a></li>
                    <li><a href="{$link->getCategoryLink(761)}" class="baby-link-item">Obiadki i zupki</a></li>
                    <li><a href="{$link->getCategoryLink(530)}" class="baby-link-item">Deserki i musy</a></li>
                    <li><a href="{$link->getCategoryLink(536)}" class="baby-link-item">Ciasteczka i przekąski</a></li>
                    <li><a href="{$link->getCategoryLink(793)}" class="baby-link-item">Herbatki i soki</a></li>
                </ul>
            </div>

            {* AKCESORIA DO KARMIENIA (ID 519) *}
            <div class="baby-cat-group">
                <div class="baby-cat-header">
                    <i class="fa-solid fa-bottle-water"></i> 
                    <a href="{$link->getCategoryLink(519)}">Do karmienia</a>
                </div>
                <ul class="baby-link-ul">
                    <li><a href="{$link->getCategoryLink(270217)}" class="baby-link-item">Butelki i akcesoria</a></li>
                    <li><a href="{$link->getCategoryLink(696)}" class="baby-link-item">Naczynia i sztućce</a></li>
                    <li><a href="{$link->getCategoryLink(697)}" class="baby-link-item">Termosy</a></li>
                </ul>
            </div>
        </div>

        {* --- KOLUMNA 2: PIELĘGNACJA I HIGIENA --- *}
        <div>
            {* PIELUSZKI (ID 783) *}
            <div class="baby-cat-group">
                <div class="baby-cat-header">
                    <i class="fa-solid fa-baby"></i> 
                    <a href="{$link->getCategoryLink(783)}">Pieluszki i Przewijanie</a>
                </div>
                <ul class="baby-link-ul">
                    <li><a href="{$link->getCategoryLink(784)}" class="baby-link-item baby-bold">Pieluszki jednorazowe</a></li>
                    <li><a href="{$link->getCategoryLink(261956)}" class="baby-link-item">Chusteczki nawilżane</a></li>
                    <li><a href="{$link->getCategoryLink(270258)}" class="baby-link-item">Woreczki na pieluszki</a></li>
                </ul>
            </div>

            {* KOSMETYKI (ID 750) *}
            <div class="baby-cat-group">
                <div class="baby-cat-header">
                    <i class="fa-solid fa-bath"></i> 
                    <a href="{$link->getCategoryLink(750)}">Kąpiel i Pielęgnacja</a>
                </div>
                <ul class="baby-link-ul">
                    <li><a href="{$link->getCategoryLink(751)}" class="baby-link-item">Płyny do kąpieli</a></li>
                    <li><a href="{$link->getCategoryLink(752)}" class="baby-link-item">Szampony i żele</a></li>
                    <li><a href="{$link->getCategoryLink(261699)}" class="baby-link-item">Emulsje i mydła</a></li>
                    <li><a href="{$link->getCategoryLink(754)}" class="baby-link-item">Balsamy i oliwki</a></li>
                    <li><a href="{$link->getCategoryLink(753)}" class="baby-link-item">Pudry i zasypki</a></li>
                    <li><a href="{$link->getCategoryLink(782)}" class="baby-link-item">Kremy pielęgnacyjne</a></li>
                    <li><a href="{$link->getCategoryLink(857)}" class="baby-link-item">Ochrona przeciwsłoneczna</a></li>
                </ul>
            </div>
        </div>

        {* --- KOLUMNA 3: ZABAWKI --- *}
        <div>
            {* NIEMOWLĘTA (ID 270189) *}
            <div class="baby-cat-group">
                <div class="baby-cat-header">
                    <i class="fa-solid fa-shapes"></i> 
                    <a href="{$link->getCategoryLink(270189)}">Dla niemowląt</a>
                </div>
                <ul class="baby-link-ul">
                    <li><a href="{$link->getCategoryLink(270190)}" class="baby-link-item">Zabawki sensoryczne</a></li>
                    <li><a href="{$link->getCategoryLink(270199)}" class="baby-link-item">Grzechotki</a></li>
                    <li><a href="{$link->getCategoryLink(270191)}" class="baby-link-item">Usypiacze i uspokajacze</a></li>
                    <li><a href="{$link->getCategoryLink(270204)}" class="baby-link-item">Zabawki do wózka</a></li>
                    <li><a href="{$link->getCategoryLink(270201)}" class="baby-link-item">Do pchania i ciągnięcia</a></li>
                </ul>
            </div>

            {* ROZWÓJ I ZABAWA (ID 785) *}
            <div class="baby-cat-group">
                <div class="baby-cat-header">
                    <i class="fa-solid fa-puzzle-piece"></i> 
                    <a href="{$link->getCategoryLink(785)}">Rozwój i Zabawa</a>
                </div>
                <ul class="baby-link-ul">
                    <li><a href="{$link->getCategoryLink(786)}" class="baby-link-item">Zabawki edukacyjne</a></li>
                    <li><a href="{$link->getCategoryLink(270202)}" class="baby-link-item">Klocki (Drewniane)</a></li>
                    <li><a href="{$link->getCategoryLink(270194)}" class="baby-link-item">Zabawki drewniane</a></li>
                    <li><a href="{$link->getCategoryLink(270219)}" class="baby-link-item">Zabawki do kąpieli</a></li>
                    <li><a href="{$link->getCategoryLink(270195)}" class="baby-link-item">Tablice</a></li>
                    <li><a href="{$link->getCategoryLink(270198)}" class="baby-link-item">Instrumenty muzyczne</a></li>
                </ul>
            </div>
        </div>

        {* --- KOLUMNA 4: DOM, SZKOŁA, MAMA --- *}
        <div>
            {* POKÓJ I SZKOŁA *}
            <div class="baby-cat-group">
                <div class="baby-cat-header">
                    <i class="fa-solid fa-house-chimney-user"></i> 
                    <a href="{$link->getCategoryLink(270185)}">Dom i Szkoła</a>
                </div>
                <ul class="baby-link-ul">
                    <li><a href="{$link->getCategoryLink(270187)}" class="baby-link-item">Pościel i kocyki</a></li>
                    <li><a href="{$link->getCategoryLink(706)}" class="baby-link-item baby-bold">Artykuły szkolne</a></li>
                    <li><a href="{$link->getCategoryLink(707)}" class="baby-link-item">Śniadaniówki i bidony</a></li>
                    <li><a href="{$link->getCategoryLink(720)}" class="baby-link-item">Okazje i przyjęcia</a></li>
                </ul>
            </div>
            
            {* ZABAWKI OGRODOWE (ID 270208) *}
            <div class="baby-cat-group">
                 <div class="baby-cat-header">
                    <i class="fa-solid fa-sun"></i> 
                    <a href="{$link->getCategoryLink(270208)}">Zabawa w ogrodzie</a>
                </div>
                <ul class="baby-link-ul">
                    <li><a href="{$link->getCategoryLink(270209)}" class="baby-link-item">Piaskownice i piasek</a></li>
                </ul>
            </div>

            {* DLA MAMY I MALUCHA (ID 270212) *}
             <div class="baby-cat-group">
                <div class="baby-cat-header">
                    <i class="fa-regular fa-face-smile-wink"></i> 
                    <a href="{$link->getCategoryLink(270212)}">Strefa Malucha</a>
                </div>
                <ul class="baby-link-ul">
                    <li><a href="{$link->getCategoryLink(270215)}" class="baby-link-item baby-bold">Smoczki uspokajające</a></li>
                    <li><a href="{$link->getCategoryLink(270214)}" class="baby-link-item">Gryzaki</a></li>
                    <li><a href="{$link->getCategoryLink(791)}" class="baby-link-item">Płyny do prania (Dziecięce)</a></li>
                </ul>
            </div>
        </div>
        
    </div>

</div>