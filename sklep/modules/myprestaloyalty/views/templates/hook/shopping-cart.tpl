{*
* Custom Loyalty View for Cart - BigBio Style (Spójny z kartą produktu)
*}

<div id="loyalty_cart_box" class="loyalty-box">
    
    {* GÓRNA CZĘŚĆ *}
    <div class="loyalty-top-row">
        
        {* LEWA STRONA: Info *}
        <div class="loyalty-left-col">
            <div class="loyalty-header-flex">
                <i class="fa-solid fa-piggy-bank loyalty-icon"></i>
                <div>
                    <span class="loyalty-title">Zbieraj punkty i wymieniaj na rabaty!</span>
                    <div class="loyalty-rule">
                        Zasada: 1 pkt za każde wydane 20 zł
                    </div>
                </div>
            </div>
        </div>

        {* PRAWA STRONA: Wynik (Podsumowanie koszyka) - STYL KPI *}
        <div class="loyalty-right-col">
            {if $points > 0}
                <span class="loyalty-stat-label">Razem w koszyku</span>
                <span class="loyalty-stat-value">{$points} pkt</span>
                <span class="loyalty-stat-sub">Rabat: <strong>{Tools::displayPrice($voucher)}</strong></span>
            {else}
                <span class="loyalty-stat-sub" style="color:#777; text-align:right;">Dodaj więcej produktów,<br>aby zdobyć punkty.</span>
            {/if}
        </div>
    </div>
    
    {* LISTA DOSTĘPNYCH KODÓW *}
    {if isset($active_vouchers) && $active_vouchers|@count > 0}
        <div class="lv-container" style="border-top: 1px dashed #e0e0e0; padding-top: 10px; margin-top: 10px;">
            <div class="lv-header">Dostępne kody rabatowe:</div>
            {foreach from=$active_vouchers item=voucher}
                
                {* Sprawdzamy czy kod jest używalny (czy spełnia minimum) *}
                {if $voucher.is_usable}
                    {* WARIANT 1: KOD DOSTĘPNY - LŻEJSZY STYL *}
                    <a href="#" 
                       class="lv-item js-auto-use-code" 
                       data-code="{$voucher.code}"
                       title="Kliknij, aby użyć tego kodu w koszyku"
                       rel="nofollow">
                        
                        <div class="lv-icon"><i class="fa-solid fa-ticket"></i></div>
                        
                        <div class="lv-content">
                            <div class="lv-code-text">{$voucher.code}</div>
                            <div class="lv-subtext">Wartość: <strong>{$voucher.value}</strong> • Ważny: {$voucher.days_left} dni</div>
                        </div>
                        
                        {* ZMIANA: Lekki styl - sam tekst z małym plusikiem *}
                        <div class="lv-action-light">
                            Użyj <i class="fa-solid fa-plus" style="font-size: 10px; margin-left: 2px;"></i>
                        </div>
                    </a>
                {else}
                    {* WARIANT 2: KOD ZABLOKOWANY *}
                    <div class="lv-item lv-item-disabled" style="cursor: not-allowed; background-color: #f9f9f9; border-color: #eee; opacity: 0.8;">
                        <div class="lv-icon" style="color: #ccc;"><i class="fa-solid fa-ticket"></i></div>
                        
                        <div class="lv-content">
                            <div class="lv-code-text" style="color: #999;">{$voucher.code}</div>
                            <div class="lv-subtext" style="color: #999;">Wartość: <strong>{$voucher.value}</strong></div>
                            {* Wyświetlenie powodu blokady *}
                            <div style="font-size: 10px; color: #e04f5f; font-weight: 600; margin-top: 2px;">
                                <i class="fa-solid fa-circle-info"></i> {$voucher.reason} 
                                (brakuje {Tools::displayPrice($voucher.missing_amount)})
                            </div>
                        </div>
                        
                        <div class="lv-action-light" style="color: #ccc;">
                            <i class="fa-solid fa-lock"></i>
                        </div>
                    </div>
                {/if}

            {/foreach}
        </div>
    {/if}

    {* DOLNA CZĘŚĆ: Status (TYLKO dla NIEZALOGOWANYCH) *}
    {if !Context::getContext()->customer->isLogged()} 
        <div class="loyalty-bottom-row">
            <span style="color:#555;">Punkty są dostępne dla zarejestrowanych klientów.</span> 
            <a href="{$link->getPageLink('my-account', true)}" class="loyalty-login-link">Zaloguj się lub zarejestruj</a>, aby zbierać punkty.
        </div>
    {/if}
</div>

<style>
    /* Style podstawowe */
    #loyalty_cart_box.loyalty-box {
        margin: 20px 0;
        padding: 15px 20px; 
        background-color: #ffffff; 
        border: 1px solid #f1f1f1; 
        border-radius: 6px; 
        clear: both;
        display: block;
    }

    .loyalty-top-row {
        display: flex;
        justify-content: space-between; 
        align-items: center; 
        gap: 20px; 
        margin-bottom: 5px; 
    }

    .loyalty-header-flex { display: flex; align-items: center; }
    .loyalty-icon { color: #2fb5d2; margin-right: 12px; font-size: 28px; }
    .loyalty-title { display: block; font-weight: 700; color: #333; font-size: 14px; line-height: 1.2; margin-bottom: 3px; }
    .loyalty-rule { font-size: 11px; color: #888; }

    .loyalty-right-col {
        display: flex;
        flex-direction: column;
        align-items: flex-end; 
        justify-content: center;
        flex-grow: 1;
        text-align: right;
    }

    .loyalty-stat-label { font-size: 10px; text-transform: uppercase; color: #999; font-weight: 600; display: block; margin-bottom: 2px; }
    .loyalty-stat-value { font-size: 22px; font-weight: 800; color: #2fb5d2; line-height: 1; }
    .loyalty-stat-sub { font-size: 11px; color: #555; display: block; margin-top: 2px; }
    
    /* Style kodów */
    .lv-container { margin-top: 12px; }
    .lv-header { font-size: 10px; text-transform: uppercase; color: #999; font-weight: 700; margin-bottom: 6px; letter-spacing: 0.5px; }
    
    .lv-item {
        display: flex;
        align-items: center;
        background: #fdfdfd;
        border: 1px solid #eee;
        border-radius: 4px;
        padding: 6px 10px;
        margin-bottom: 5px;
        cursor: pointer;
        text-decoration: none !important;
        transition: all 0.2s ease;
    }
    .lv-item:hover { border-color: #2fb5d2; background: #f0fbff; }
    
    /* Styl dla zablokowanego kodu */
    .lv-item-disabled:hover { border-color: #eee !important; background: #f9f9f9 !important; }

    .lv-icon { color: #2fb5d2; font-size: 14px; margin-right: 10px; }
    .lv-content { flex-grow: 1; }
    
    .lv-code-text { font-family: monospace; font-size: 13px; font-weight: 700; color: #333; letter-spacing: 1px; }
    .lv-subtext { font-size: 10px; color: #777; margin-top: 1px; }
    
    /* --- NOWY STYL AKCJI (LEKKI) --- */
    .lv-action-light {
        font-size: 12px;
        font-weight: 700;
        color: #2fb5d2;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        white-space: nowrap;
        transition: color 0.2s;
    }
    .lv-item:hover .lv-action-light {
        color: #259bb5;
        text-decoration: underline;
    }
    /* ------------------------------ */

    .loyalty-bottom-row {
        border-top: 1px dashed #e0e0e0;
        padding-top: 8px; 
        font-size: 11px; 
        color: #666;
        text-align: left;
        margin-top: 10px;
    }
    .loyalty-login-link { color: #2fb5d2; text-decoration: underline; font-weight: 600; margin-left: 3px; }

    @media (max-width: 768px) {
        .loyalty-top-row { flex-direction: column; align-items: flex-start; gap: 15px; }
        .loyalty-right-col { width: 100%; align-items: flex-start; text-align: left; padding-left: 40px; border-top: 1px solid #f5f5f5; padding-top: 10px; }
    }
</style>