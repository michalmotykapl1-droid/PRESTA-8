{**
* ThemeVolty - Delivery Banner (Full Width Fix)
*}
{strip}
<div class="bb-delivery-container">
    
    {* --- LEWA STRONA (GŁÓWNY KAFEL) --- *}
    <div class="bb-del-main-card">
        
        {* Nagłówek z Ikoną i Tytułem *}
        <div class="bb-del-header">
            <div class="bb-del-icon-box">
                <i class="fa-solid fa-wallet"></i>
            </div>
            <div class="bb-del-titles">
                <span class="bb-subtitle">OSZCZĘDZAJ NA WYSYŁCE</span>
                <h2>DARMOWA DOSTAWA <span class="bb-price-accent">OD {$free_shipping_price}</span></h2>
            </div>
        </div>
        
        {* Opis *}
        <p class="bb-del-desc">
            Zrób zapas ulubionych produktów i nie płać za kuriera.
            Oszczędzasz przy każdym większym zamówieniu, a my dbamy o szybką realizację.
        </p>
        
        {* Tagi metod dostawy *}
        <div class="bb-methods-row">
            <div class="bb-method-tag"><i class="fa-solid fa-truck"></i> Kurier</div>
            <div class="bb-method-tag"><i class="fa-solid fa-box-open"></i> Paczkomaty</div>
            <div class="bb-method-tag"><i class="fa-solid fa-warehouse"></i> Odbiór Osobisty</div>
        </div>

        {* Link Details *}
        <a href="/index.php?id_cms=1&controller=cms&id_lang=2" class="bb-link-arrow">
            SZCZEGÓŁY DOSTAWY <i class="fa-solid fa-arrow-right"></i>
        </a>
    </div>

    {* --- PRAWA STRONA (SIDEBAR) --- *}
    <div class="bb-del-sidebar">
        
        {* 1. EKO PAKOWANIE *}
        <div class="bb-del-small-card">
            <div class="bb-small-header">
                <div class="bb-small-header-left">
                    <div class="bb-small-icon icon-eco">
                        <i class="fa-solid fa-leaf"></i>
                    </div>
                    <h4>EKO PAKOWANIE</h4>
                </div>
                <div class="bb-badge badge-eco">ZERO PLASTIKU</div>
            </div>
            <div class="bb-small-content">
                <p>Używamy kartonów z recyklingu i papierowych wypełniaczy.
                Twoja paczka jest bezpieczna dla Ziemi.</p>
            </div>
        </div>

        {* 2. CZAS DOSTAWY *}
        <div class="bb-del-small-card">
            <div class="bb-small-header">
                <div class="bb-small-header-left">
                    <div class="bb-small-icon">
                        <i class="fa-solid fa-stopwatch"></i>
                    </div>
                    <h4>SZYBKA WYSYŁKA</h4>
                </div>
                <div class="bb-badge badge-time">
                    <span class="bb-pulse-dot"></span> Czas ucieka
                </div>
            </div>
            <div class="bb-small-content">
                <p>
                    {* TU ZMIANA: Zmienna zamiast twardego tekstu *}
                    {$delivery_prefix_text}
                    <strong class="bb-date-highlight">{$delivery_date}</strong>
                </p>
            </div>
        </div>

    </div>

</div>
{/strip}