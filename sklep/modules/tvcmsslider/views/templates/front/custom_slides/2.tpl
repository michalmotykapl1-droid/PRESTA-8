{* SLIDER DIETY - WERSJA FINALNA (BEZ PRZYCISKU NA MOBILE) *}
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

<style>
    /* --- GŁÓWNY KONTENER --- */
    .slide-diet-pro {
        width: 100%; 
        height: 100%;
        min-height: 380px; 
        background: #ffffff;
        display: flex; 
        align-items: center; 
        justify-content: center;
        font-family: inherit;
        color: #333;
        position: relative; 
        overflow: hidden;
        background-image: radial-gradient(#f0f0f0 1px, transparent 1px);
        background-size: 20px 20px;
    }

    /* --- UKŁAD GRID (DESKTOP) --- */
    .diet-content-pro {
        width: 100%; 
        max-width: 1350px; 
        padding: 0 30px; 
        display: grid;
        grid-template-columns: 1fr 1fr; 
        grid-template-areas: 
            "text icons"
            "btn icons";
        align-items: center;
        gap: 20px 40px; 
    }

    /* --- LEWA STRONA --- */
    .diet-left { 
        grid-area: text;
        display: flex; 
        flex-direction: column; 
        justify-content: center;
        align-items: flex-start;
        z-index: 10;
    }

    .diet-badge {
        color: #d90244; 
        font-size: 11px; 
        font-weight: 800; 
        text-transform: uppercase; 
        letter-spacing: 2px;
        margin-bottom: 15px;
        background: #fff0f3;
        padding: 5px 10px;
        border-radius: 4px;
    }

    .diet-title {
        font-size: 52px; 
        line-height: 0.95; 
        font-weight: 800; 
        text-transform: uppercase; 
        margin-bottom: 15px;
        color: #111;
        letter-spacing: -1.5px;
    }
    
    .diet-title span { color: #d90244; }

    .diet-desc {
        font-size: 15px; 
        color: #555; 
        margin-bottom: 25px;
        font-weight: 400; 
        line-height: 1.5;
        max-width: 450px;
    }

    /* Benefity */
    .diet-benefits {
        display: flex; flex-wrap: wrap; gap: 15px; margin-bottom: 10px;
    }
    .benefit-item {
        display: flex; align-items: center; gap: 6px;
        font-size: 12px; font-weight: 700; color: #222;
        background: #fff; padding: 6px 12px; border-radius: 4px; border: 1px solid #eee;
    }
    .benefit-item i { color: #d90244; font-size: 12px; }

    /* --- PRZYCISK (DESKTOP ONLY) --- */
    .diet-cta-wrapper {
        grid-area: btn;
        display: flex;
        align-items: flex-start;
    }

    .diet-btn-pro {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: transparent; 
        border: 2px solid #d90244; 
        color: #d90244 !important; 
        padding: 12px 35px;
        border-radius: 50px;
        font-weight: 700;
        font-size: 13px;
        text-transform: uppercase;
        letter-spacing: 1px;
        text-decoration: none !important;
        transition: all 0.3s ease;
    }

    .diet-btn-pro i { margin-left: 10px; transition: transform 0.3s; }

    .diet-btn-pro:hover {
        background: #d90244; 
        color: #fff !important;
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(217, 2, 68, 0.15);
    }
    .diet-btn-pro:hover i { transform: translateX(5px); }


    /* --- PRAWA STRONA (PANEL IKON) --- */
    .diet-right {
        grid-area: icons;
        display: flex; 
        justify-content: flex-end;
        align-items: center;
        height: 100%;
    }

    .diet-grid-panel {
        display: grid;
        grid-template-columns: repeat(4, 1fr); 
        gap: 10px;
        background: #fff;
        padding: 20px;
        border-radius: 12px;
        box-shadow: 0 15px 40px rgba(0,0,0,0.06);
        border: 1px solid #f0f0f0;
        width: 100%;
    }

    .diet-mini-card {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        text-align: center;
        background: #fbfbfb;
        padding: 15px 5px;
        border-radius: 8px;
        border: 1px solid transparent;
        transition: all 0.2s ease;
        text-decoration: none !important;
        min-width: 80px;
    }

    .dmc-icon {
        font-size: 22px; 
        color: #333;
        margin-bottom: 6px;
        transition: color 0.2s;
    }

    .dmc-text {
        font-size: 10px;
        font-weight: 700;
        color: #444;
        line-height: 1.2;
        white-space: nowrap;
    }

    .diet-mini-card:hover {
        background: #fff;
        border-color: #d90244;
        box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        transform: translateY(-3px);
    }
    .diet-mini-card:hover .dmc-icon, 
    .diet-mini-card:hover .dmc-text { color: #d90244; }

    /* --- MOBILE OPTIMIZATION (BEZ PRZYCISKU) --- */
    @media (max-width: 991px) {
        .slide-diet-pro { 
            padding: 20px 0; 
            height: auto;
            min-height: auto;
            align-items: flex-start;
        }

        .diet-content-pro { 
            grid-template-columns: 1fr; 
            /* USUNIĘTO PRZYCISK Z GRIDU */
            grid-template-areas: 
                "text"
                "icons";
            padding: 0 15px; 
            gap: 30px; 
            text-align: center;
        }
        
        .diet-left { align-items: center; margin-bottom: 0; }
        
        .diet-badge { margin-bottom: 10px; font-size: 10px; }
        .diet-title { font-size: 30px; margin-bottom: 10px; }
        .diet-desc { font-size: 14px; margin-bottom: 20px; line-height: 1.5; padding: 0 5px; }
        
        .diet-benefits { justify-content: center; gap: 10px; margin-bottom: 5px; }
        .benefit-item { padding: 6px 14px; }
        
        /* IKONY */
        .diet-right {
            display: flex;
            justify-content: center;
            width: 100%;
            /* Dodajemy lekki margines dolny dla bezpieczeństwa */
            margin-bottom: 15px; 
        }
        
        .diet-grid-panel {
            padding: 15px 10px;
            gap: 12px;
            grid-template-columns: repeat(4, 1fr); 
            width: 100%;
        }
        
        .diet-mini-card {
            padding: 15px 2px;
            min-width: auto; 
        }
        
        .dmc-icon { font-size: 22px; margin-bottom: 6px; }
        .dmc-text { font-size: 10px; }

        /* UKRYWAMY PRZYCISK NA MOBILE */
        .diet-cta-wrapper {
            display: none;
        }
    }
</style>

<div class="slide-diet-pro">
    <div class="diet-content-pro">
        
        <div class="diet-left">
            <div class="diet-badge">ŚWIADOME ODŻYWIANIE</div>
            
            <div class="diet-title">
                ZAKUPY<br>
                <span>IDEALNE</span>
            </div>
            
            <div class="diet-desc">
                Stworzyliśmy dedykowane strefy dla Twojego stylu odżywiania.
                Znajdź swoje ulubione produkty w jednym miejscu.
            </div>
            
            <div class="diet-benefits">
                <div class="benefit-item"><i class="fa-solid fa-check"></i> Precyzyjny Wybór</div>
                <div class="benefit-item"><i class="fa-solid fa-check"></i> Oszczędność Czasu</div>
                <div class="benefit-item"><i class="fa-solid fa-check"></i> Pełna Wygoda</div>
            </div>
        </div>

        <div class="diet-right">
            <div class="diet-grid-panel">
                <a href="index.php?id_category=264208&controller=category&id_lang=2" class="diet-mini-card">
                    <i class="fa-solid fa-bread-slice dmc-icon"></i>
                    <span class="dmc-text">Bez Glutenu</span>
                </a>
                <a href="index.php?id_category=264210&controller=category&id_lang=2" class="diet-mini-card">
                    <i class="fa-solid fa-carrot dmc-icon"></i>
                    <span class="dmc-text">Wege</span>
                </a>
                <a href="index.php?id_category=264209&controller=category&id_lang=2" class="diet-mini-card">
                    <i class="fa-solid fa-seedling dmc-icon"></i>
                    <span class="dmc-text">Wegańskie</span>
                </a>
                <a href="index.php?id_category=264207&controller=category&id_lang=2" class="diet-mini-card">
                    <i class="fa-solid fa-glass-water dmc-icon"></i>
                    <span class="dmc-text">Bez Laktozy</span>
                </a>

                <a href="index.php?id_category=264205&controller=category&id_lang=2" class="diet-mini-card">
                    <i class="fa-solid fa-leaf dmc-icon"></i>
                    <span class="dmc-text">Bio / Eco</span>
                </a>
                <a href="index.php?id_category=264211&controller=category&id_lang=2" class="diet-mini-card">
                    <i class="fa-solid fa-bolt dmc-icon"></i>
                    <span class="dmc-text">Keto</span>
                </a>
                <a href="index.php?id_category=264206&controller=category&id_lang=2" class="diet-mini-card">
                    <i class="fa-solid fa-cube dmc-icon"></i>
                    <span class="dmc-text">Bez Cukru</span>
                </a>
                <a href="index.php?id_category=264212&controller=category&id_lang=2" class="diet-mini-card">
                    <i class="fa-solid fa-arrow-trend-down dmc-icon"></i>
                    <span class="dmc-text">Niski Indeks</span>
                </a>
            </div>
        </div>

        <div class="diet-cta-wrapper">
            <a href="index.php?id_category=167&controller=category&id_lang=2" class="diet-btn-pro">
                ZOBACZ OFERTĘ <i class="fa-solid fa-arrow-right"></i>
            </a>
        </div>

    </div>
</div>