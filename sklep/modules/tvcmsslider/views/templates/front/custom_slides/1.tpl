{* BLACK WEEKS PRO - MOBILE FIX (CZYTELNOŚĆ + CENTROWANIE) *}
<style>
    /* --- GŁÓWNY KONTENER (DESKTOP) --- */
    .slide-bw-pro {
        width: 100%; height: 100%;
        /* Tło: Paski + Gradient pod kątem (Desktop) */
        background: 
            repeating-linear-gradient(45deg, rgba(255,255,255,0.03) 0px, rgba(255,255,255,0.03) 2px, transparent 2px, transparent 10px),
            linear-gradient(105deg, #0a0a0a 0%, #1a1a1a 55%, #ff5a00 55.1%, #ff6b1a 100%);
        display: flex; align-items: center; justify-content: center;
        font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; 
        color: white; 
        position: relative; overflow: hidden;
        -webkit-font-smoothing: antialiased;
        -webkit-backface-visibility: hidden;
        -webkit-transform: translate3d(0, 0, 0);
    }

    .bg-watermark {
        position: absolute; right: -5%; top: 50%; transform: translateY(-50%);
        font-size: 300px; font-weight: 900; color: rgba(255,255,255,0.1); 
        z-index: 1; line-height: 1; pointer-events: none; font-family: Impact, sans-serif;
    }

    .bw-content {
        width: 100%; max-width: 1200px; padding: 0 30px; 
        display: flex; align-items: center; justify-content: space-between;
        z-index: 2;
    }

    .bw-left { flex: 1; padding-right: 20px; }

    .bw-top-badge {
        background: #fff; color: #000; padding: 6px 15px; font-size: 14px; font-weight: 800; 
        text-transform: uppercase; letter-spacing: 2px; display: inline-block; 
        margin-bottom: 25px; border-left: 5px solid #ff5a00;
    }

    .bw-main-title {
        font-size: 80px; line-height: 0.85; font-weight: 900; 
        text-transform: uppercase; letter-spacing: -3px; margin-bottom: 20px;
    }
    
    /* Styl Desktopowy - Obrys */
    .bw-main-title span { color: transparent; -webkit-text-stroke: 2px #ff5a00; }

    .bw-sub-text {
        font-size: 18px; font-weight: 400; letter-spacing: 1px; 
        text-transform: uppercase; opacity: 0.8; margin-bottom: 40px;
    }

    .bw-btn-pro {
        background: #ff5a00; color: white; padding: 18px 45px; font-size: 16px; font-weight: 800; 
        text-transform: uppercase; text-decoration: none; display: inline-block; 
        transition: all 0.3s ease; border: 1px solid #ff5a00; 
        box-shadow: 0 10px 30px rgba(255, 90, 0, 0.25);
    }
    
    .bw-btn-pro:hover {
        background: transparent; color: #ff5a00; 
        box-shadow: 0 5px 15px rgba(255, 90, 0, 0.4);
    }

    .bw-right {
        flex: 1; position: relative; height: 300px; 
        display: flex; justify-content: center; align-items: center;
    }

    .discount-seal {
        width: 220px; height: 220px; background: #fff; border-radius: 50%; 
        display: flex; flex-direction: column; align-items: center; justify-content: center; 
        position: relative; box-shadow: 0 20px 60px rgba(0,0,0,0.3); 
        transform: rotate(-5deg); transition: transform 0.3s ease;
    }
    
    .slide-bw-pro:hover .discount-seal { transform: rotate(0deg) scale(1.05); }

    .discount-seal::before {
        content: ''; position: absolute; top: -15px; left: -15px; right: -15px; bottom: -15px; 
        border: 2px dashed rgba(255,255,255,0.6); border-radius: 50%; 
        animation: spinSlow 20s linear infinite;
    }

    @keyframes spinSlow { 100% { transform: rotate(360deg); } }

    .ds-top { font-size: 18px; font-weight: 700; color: #888; letter-spacing: 1px; margin-bottom: -5px; }
    .ds-big { font-size: 85px; font-weight: 900; color: #ff5a00; line-height: 0.9; letter-spacing: -4px; }
    .ds-unit { font-size: 40px; vertical-align: top; margin-left: 2px; }
    
    .ds-badge {
        position: absolute; bottom: -20px; right: -10px; 
        background: #000; color: #fff; padding: 10px 25px; 
        font-size: 20px; font-weight: 800; text-transform: uppercase; 
        transform: rotate(-10deg); box-shadow: 0 5px 15px rgba(0,0,0,0.3);
    }

    /* --- MOBILE FIX (POPRAWA CZYTELNOŚCI I UKŁADU) --- */
    @media (max-width: 767px) {
        .slide-bw-pro { 
            /* Podział: 50% Czarny (Góra), 50% Pomarańcz (Dół) */
            background: linear-gradient(180deg, #0a0a0a 0%, #1a1a1a 50%, #ff5a00 50.1%, #ff5a00 100%); 
        }
        
        .bg-watermark { display: none; }
        
        .bw-content { 
            flex-direction: column; 
            text-align: center; 
            justify-content: flex-start; 
            padding: 0; /* Reset paddingu kontenera */
            height: 100%;
        }
        
        /* GÓRNA CZĘŚĆ (Czarna) - TEKSTY */
        .bw-left { 
            padding: 20px 15px 0 15px; 
            margin-bottom: 0; 
            flex: 0 0 50%; /* Zajmuje idealnie połowę wysokości */
            display: flex; 
            flex-direction: column; 
            align-items: center; 
            justify-content: center;
            width: 100%;
        }
        
        .bw-top-badge { 
            margin-bottom: 10px; border-left: none; border-bottom: 3px solid #ff5a00; 
            font-size: 11px; padding: 4px 10px; 
        }
        
        .bw-main-title { 
            font-size: 46px; margin-bottom: 5px; letter-spacing: -1px;
        }
        
        /* POPRAWKA CZYTELNOŚCI: Pełny kolor zamiast obrysu */
        .bw-main-title span { 
            color: #ff5a00 !important; 
            -webkit-text-stroke: 0 !important;
            text-shadow: 0 0 20px rgba(255, 90, 0, 0.4);
        }
        
        .bw-sub-text { 
            font-size: 11px; margin-bottom: 15px; display: block; 
            line-height: 1.4; opacity: 0.8; letter-spacing: 1px;
        }
        
        /* Przycisk Biały na Czarnym */
        .bw-btn-pro { 
            padding: 10px 30px; font-size: 12px; margin-bottom: 0; 
            background: white; color: #ff5a00; border-color: white; 
            box-shadow: 0 5px 15px rgba(255,255,255,0.1);
        }

        /* DOLNA CZĘŚĆ (Pomarańczowa) - SEAL */
        .bw-right { 
            flex: 0 0 50%; /* Druga połowa wysokości */
            width: 100%; 
            display: flex; 
            align-items: center; 
            justify-content: center;
            padding-bottom: 20px; /* Lekkie uniesienie od samego dołu */
        }
        
        .discount-seal { 
            width: 150px; height: 150px; 
            box-shadow: 0 10px 30px rgba(0,0,0,0.2); 
            margin-top: 0; /* Reset marginesów */
        }
        .discount-seal::before { top: -6px; left: -6px; right: -6px; bottom: -6px; }
        
        .ds-top { font-size: 11px; }
        .ds-big { font-size: 55px; }
        .ds-unit { font-size: 26px; }
        .ds-badge { font-size: 11px; padding: 6px 15px; bottom: -10px; right: -10px; }
    }
</style>

<div class="slide-bw-pro">
    <div class="bg-watermark">-50%</div>

    <div class="bw-content">
        <div class="bw-left">
            <div class="bw-top-badge">U NAS CAŁY ROK</div>
            
            <div class="bw-main-title">
                BLACK<br><span>WEEKS</span>
            </div>
            
            <div class="bw-sub-text">
                SETKI PRODUKTÓW W SUPER CENACH
            </div>

            <a href="index.php?id_category=45&controller=category&id_lang=2" class="bw-btn-pro">
                ZOBACZ OFERTĘ
            </a>
        </div>

        <div class="bw-right">
            <div class="discount-seal">
                <span class="ds-top">RABATY DO</span>
                <span class="ds-big">50<span class="ds-unit">%</span></span>
                
                <div class="ds-badge">HIT</div>
            </div>
        </div>
    </div>
</div>