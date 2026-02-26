{* SLIDER USŁUGI - WERSJA MOBILE CENTERED (W PEŁNI WYŚRODKOWANA) *}
<style>
    /* --- GŁÓWNY KONTENER --- */
    .slide-local-services {
        width: 100%; height: 100%;
        /* Tło: Spokojny, medyczny, ale naturalny gradient (Morski/Mięta) */
        background: linear-gradient(120deg, #e0f7fa 0%, #ffffff 100%);
        
        display: flex; align-items: center; justify-content: center;
        font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; 
        color: #333;
        position: relative; overflow: hidden;
        
        -webkit-backface-visibility: hidden;
        -webkit-transform: translate3d(0, 0, 0);
    }

    /* Dekoracja tła - Mapa */
    .bg-map-lines {
        position: absolute; right: 0; top: 0; width: 60%; height: 100%;
        background-image: repeating-linear-gradient(45deg, #00acc1 0, #00acc1 1px, transparent 0, transparent 50%);
        background-size: 10px 10px; opacity: 0.03;
        mask-image: linear-gradient(to left, rgba(0,0,0,1), rgba(0,0,0,0));
        -webkit-mask-image: linear-gradient(to left, rgba(0,0,0,1), rgba(0,0,0,0));
    }

    /* --- UKŁAD --- */
    .local-content {
        width: 100%; max-width: 1200px; padding: 0 30px; 
        display: flex; align-items: center; justify-content: space-between;
        z-index: 2;
        padding-top: 20px; padding-bottom: 20px;
    }

    /* --- LEWA STRONA --- */
    .local-left { 
        flex: 0 0 50%; padding-right: 20px; z-index: 10; 
        display: flex; flex-direction: column; justify-content: center;
    }

    .local-badge {
        background: #00838f; color: #fff;
        padding: 6px 14px; font-size: 11px; font-weight: 800; 
        text-transform: uppercase; letter-spacing: 1px;
        display: inline-block; margin-bottom: 15px; 
        border-radius: 4px; width: fit-content;
        box-shadow: 0 4px 10px rgba(0, 131, 143, 0.2);
    }

    .local-title {
        font-size: 48px; line-height: 1.05; font-weight: 900; 
        text-transform: uppercase; margin-bottom: 10px;
        color: #111;
    }
    .local-title span { color: #00acc1; display: block; }

    .local-desc {
        font-size: 16px; color: #555; margin-bottom: 25px;
        font-weight: 400; max-width: 480px; line-height: 1.5;
    }

    /* Ikony usług */
    .services-icons {
        display: flex; gap: 20px; margin-bottom: 30px;
    }
    .service-item {
        display: flex; flex-direction: column; align-items: center; gap: 5px;
        font-size: 12px; font-weight: 700; color: #444; text-transform: uppercase;
    }
    .s-icon-circle {
        width: 50px; height: 50px; background: #fff; border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        border: 1px solid #b2ebf2;
        color: #00838f; transition: transform 0.2s;
    }
    .service-item:hover .s-icon-circle { transform: translateY(-3px); background: #00acc1; color: #fff; }

    .local-btn {
        background: #111; color: white !important;
        padding: 15px 40px; font-size: 14px; font-weight: 700; 
        text-transform: uppercase; text-decoration: none; display: inline-block;
        border-radius: 50px; transition: all 0.3s ease;
        box-shadow: 0 10px 20px rgba(0,0,0,0.1); width: fit-content; letter-spacing: 1px;
    }
    .local-btn:hover {
        background: #00838f; transform: translateY(-3px);
        box-shadow: 0 15px 30px rgba(0, 131, 143, 0.3);
    }

    /* --- PRAWA STRONA (DESKTOP) --- */
    .local-right {
        flex: 1; position: relative; height: 400px;
        display: flex; align-items: center; justify-content: center;
    }

    .location-pin {
        position: relative; width: 80px; height: 80px;
        background: #00acc1; border-radius: 50% 50% 50% 0;
        transform: rotate(-45deg);
        display: flex; align-items: center; justify-content: center;
        box-shadow: 10px 10px 30px rgba(0, 172, 193, 0.4);
        z-index: 5;
        animation: bouncePin 2s ease-in-out infinite;
    }
    .location-pin::after {
        content: ''; width: 40px; height: 40px; background: #fff; border-radius: 50%;
    }

    @keyframes bouncePin {
        0%, 100% { transform: rotate(-45deg) translateY(0); }
        50% { transform: rotate(-45deg) translateY(-10px); }
    }

    .pin-shadow {
        width: 60px; height: 20px; background: rgba(0,0,0,0.1);
        border-radius: 50%; position: absolute; bottom: 100px;
        animation: scaleShadow 2s ease-in-out infinite;
    }
    @keyframes scaleShadow {
        0%, 100% { transform: scale(1); opacity: 0.2; }
        50% { transform: scale(0.7); opacity: 0.1; }
    }

    .info-card {
        position: absolute; background: #fff; padding: 12px 20px;
        border-radius: 10px; box-shadow: 0 10px 25px rgba(0,0,0,0.08);
        font-weight: 700; color: #333; font-size: 14px;
        display: flex; align-items: center; gap: 10px;
        border-left: 4px solid #00acc1;
    }
    
    .ic-1 { top: 25%; right: 20%; animation: floatCard 4s ease-in-out infinite; }
    .ic-2 { bottom: 25%; left: 20%; animation: floatCard 4s ease-in-out 2s infinite; }
    
    @keyframes floatCard {
        0%, 100% { transform: translateY(0); }
        50% { transform: translateY(-5px); }
    }

    /* --- MOBILE FIX (WYŚRODKOWANIE WSZYSTKIEGO) --- */
    @media (max-width: 767px) {
        .slide-local-services { background: #f0fdff; height: auto; min-height: 550px; }
        
        .local-content { 
            flex-direction: column; text-align: center; 
            /* Flex pozwala wyśrodkować elementy w pionie */
            align-items: center; 
            justify-content: flex-start; 
            padding-top: 25px; padding-bottom: 20px; 
        }
        
        .local-left { 
            padding-right: 0; flex: auto; width: 100%; 
            align-items: center; /* Centrowanie wewnątrz kontenera */
        }
        
        .local-title { font-size: 34px; margin-bottom: 5px; }
        .local-desc { font-size: 13px; margin-bottom: 15px; }
        .local-badge { margin-bottom: 10px; margin-left: auto; margin-right: auto; }
        
        .services-icons { justify-content: center; margin-bottom: 20px; gap: 15px; }
        .s-icon-circle { width: 45px; height: 45px; }
        
        /* WYŚRODKOWANIE PRZYCISKU */
        .local-btn { 
            padding: 12px 40px; font-size: 13px; 
            margin: 0 auto; /* Kluczowe dla wyśrodkowania */
        }
        
        .local-right { 
            height: auto; width: 100%; 
            display: flex; flex-direction: column; 
            justify-content: center; align-items: center;
            margin-top: 30px; padding-bottom: 10px;
        }

        .location-pin { 
            margin-bottom: 20px; 
            width: 50px; height: 50px; 
            top: auto; animation: none; transform: rotate(-45deg); 
        }
        .location-pin::after { width: 25px; height: 25px; }
        .pin-shadow { display: none; }
        
        .info-card { 
            position: relative; top: auto; right: auto; bottom: auto; left: auto;
            margin-bottom: 10px;
            animation: none; transform: none;
            width: 200px; justify-content: center;
            font-size: 11px; padding: 10px;
        }
    }
</style>

<div class="slide-local-services">
    <div class="bg-map-lines"></div>

    <div class="local-content">
        <div class="local-left">
            <div class="local-badge">GABINET PARTNERSKI</div>
            
            <div class="local-title">
                ZADBAJ O SIEBIE<br>
                <span>KOMPLEKSOWO</span>
            </div>
            
            <div class="local-desc">
                Łączymy naturalną suplementację z profesjonalną terapią.
                Skonsultuj się z naszymi ekspertami.
            </div>
            
            <div class="services-icons">
                <div class="service-item"><div class="s-icon-circle"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg></div>Naturopata</div>
                <div class="service-item"><div class="s-icon-circle"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.24 12.24a6 6 0 0 0-8.49-8.49L5 10.5V19h8.5zM16 8L2 22"/></svg></div>Fizjoterapia</div>
                <div class="service-item"><div class="s-icon-circle"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12" y2="17"/></svg></div>Porady</div>
            </div>

            <a href="index.php?controller=contact" class="local-btn">
                UMÓW WIZYTĘ
            </a>
        </div>

        <div class="local-right">
            <div class="location-pin"></div>
            <div class="pin-shadow"></div>
            
            <div class="info-card ic-1">
                KRAKÓW
            </div>
            
            <div class="info-card ic-2">
                WSPARCIE STACJONARNE
            </div>
        </div>
    </div>
</div>