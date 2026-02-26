{**
 * Statyczny blok zaufania - zastępuje moduł blockreassurance
 *}
<div class="product-trust-badges">
    
    <div class="trust-item">
        <div class="trust-icon">
            {* Ikona kłódki / tarczy (SVG) *}
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#222" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
            </svg>
        </div>
        <div class="trust-content">
            <span class="trust-title">100% BEZPIECZNE ZAKUPY</span>
            <p class="trust-desc">Dane i płatności chronione SSL</p>
        </div>
    </div>

    <div class="trust-item">
        <div class="trust-icon">
            {* Ikona zegara / ciężarówki (SVG) *}
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#222" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"></circle>
                <polyline points="12 6 12 12 16 14"></polyline>
            </svg>
        </div>
        <div class="trust-content">
            <span class="trust-title">SZYBKA REALIZACJA</span>
            <p class="trust-desc">Wysyłka w 24–48 godzin</p>
        </div>
    </div>

    <div class="trust-item">
        <div class="trust-icon">
            {* Ikona pudełka / zwrotu (SVG) *}
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#222" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
                <polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline>
                <line x1="12" y1="22.08" x2="12" y2="12"></line>
            </svg>
        </div>
        <div class="trust-content">
            <span class="trust-title">30 DNI NA ZWROT</span>
            <p class="trust-desc">Wygodny zwrot bez formalności</p>
        </div>
    </div>

</div>

{* Style CSS wewnątrz TPL dla wygody (możesz przenieść do custom.css) *}
<style>
    .product-trust-badges {
        margin-top: 25px;
        border-top: 1px solid #f1f1f1;
        padding-top: 20px;
        display: flex;
        flex-wrap: wrap;
        gap: 20px;
    }

    .trust-item {
        flex: 1 1 100%; /* Na mobile jeden pod drugim */
        display: flex;
        align-items: center;
        gap: 15px;
        padding: 10px;
        background: #fff;
        /* Opcjonalnie: delikatna ramka lub tło */
        /* border: 1px solid #f5f5f5; */
        /* border-radius: 6px; */
    }

    .trust-icon {
        flex-shrink: 0;
        width: 40px;
        height: 40px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #f9f9f9;
        border-radius: 50%;
    }
    
    /* Kolor ikon (możesz zmienić na swój pomarańcz) */
    .trust-icon svg {
        stroke: #ea7404; 
    }

    .trust-content {
        display: flex;
        flex-direction: column;
    }

    .trust-title {
        font-size: 13px;
        font-weight: 700;
        color: #333;
        text-transform: uppercase;
        line-height: 1.2;
        margin-bottom: 2px;
    }

    .trust-desc {
        font-size: 12px;
        color: #777;
        margin: 0;
        line-height: 1.2;
    }

    /* Tablet i Desktop: Trzy obok siebie */
    @media (min-width: 768px) {
        .trust-item {
            flex: 1 1 calc(33.333% - 20px); /* Dzielimy na 3 kolumny */
        }
    }
</style>