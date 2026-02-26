{*
* PrestaShop module created by VEKIA
* Redesigned for BigBio Style - FINAL VERSION
* Features:
* 1. Green badge for Orders
* 2. Blue badge for Voucher Exchange
* 3. Pink badge for Returns
* 4. Sidebar Link Active Color FIXED (Pink text forced)
*}

{extends file='customer/page.tpl'}

{block name='page_title'}
    {l s='Moje punkty lojalnościowe' mod='myprestaloyalty'}
{/block}

{* --- USUWAMY DOMYŚLNE PRZYCISKI SZABLONU --- *}
{block name='page_footer'}
{/block}

{block name="page_content"}

{* --- ZMIENNE KONFIGURACYJNE --- *}
{assign var="loyalty_rate" value=Configuration::get('PS_LOYALTY_POINT_RATE')}
{assign var="loyalty_validity" value=Configuration::get('PS_LOYALTY_VALIDITY_PERIOD')}
{assign var="loyalty_value" value=Configuration::get('PS_LOYALTY_POINT_VALUE')}
{assign var="example_points" value=20}
{assign var="example_discount" value=$loyalty_value * $example_points}

{literal}
<style>
    /* --- ZMIENNE KOLORYSTYCZNE --- */
    :root { 
        --c-text: #232323;
        --c-text-light: #777; 
        --c-brand: #d01662; --brand-color: #d01662; /* Różowy BigBio */
        --c-brand-bg: #fff0f5; --bg-light: #f8f9fa;
        --c-border: #eaeaea; --font-base: 'Open Sans', Helvetica, Arial, sans-serif;
        --blue-loyalty: #2fb5d2;
    }

    /* UKŁAD STRONY */
    .account-sidebar-layout { display: grid;
        grid-template-columns: 300px 1fr; gap: 40px; margin-bottom: 60px; align-items: start; }
    @media (max-width: 991px) { .account-sidebar-layout { grid-template-columns: 1fr;
        gap: 30px; } }

    /* --- MENU BOCZNE --- */
    .account-menu-column {
        display: flex;
        flex-direction: column; gap: 8px;
        background: #fff; padding: 25px 20px; border-radius: 8px;
        border: 1px solid #f0f0f0;
        box-shadow: 0 2px 15px rgba(0,0,0,0.03);
    }
    .menu-header {
        font-size: 11px; text-transform: uppercase; letter-spacing: 1.5px;
        color: #aaa; font-weight: 800; margin-bottom: 10px; padding-left: 15px;
    }
    .sidebar-link {
        display: flex;
        align-items: center; padding: 12px 15px;
        background: transparent; border-radius: 6px; text-decoration: none !important;
        transition: all 0.2s ease; color: var(--c-text); font-weight: 600;
        border: 1px solid transparent; gap: 15px;
    }
    .sidebar-link i {
        font-size: 18px;
        width: 25px; text-align: center;
        color: #ccc; transition: all 0.2s; flex-shrink: 0;
        display: flex; justify-content: center; align-items: center;
    }
    .sidebar-link:hover, .sidebar-link.active-page { background: var(--bg-light); color: var(--brand-color);
    }
    .sidebar-link:hover i, .sidebar-link.active-page i { color: var(--brand-color); transform: translateX(3px);
    }
    
    /* MODUŁY W MENU BOCZNYM (Dostosowanie) */
    .account-modules-sidebar { display: flex !important;
        flex-direction: column !important; gap: 8px !important; }
    .account-modules-sidebar ul, .account-modules-sidebar li { display: contents !important;
        list-style: none !important; margin: 0 !important; padding: 0 !important; }

    .account-modules-sidebar a {
        display: flex !important;
        align-items: center !important; flex-direction: row !important; justify-content: flex-start !important;
        padding: 12px 15px !important; margin: 0 !important; background: transparent !important;
        border-radius: 6px !important; text-decoration: none !important; color: var(--c-text) !important; 
        width: 100% !important; min-height: 44px; position: relative !important; box-sizing: border-box !important;
        gap: 15px !important;
    }
    
    /* IKONY FONT AWESOME ZAMIAST OBRAZKÓW */
    .account-modules-sidebar a i, .account-modules-sidebar a svg, .account-modules-sidebar a img, .account-modules-sidebar a .material-icons { display: none !important;
    }
    .account-modules-sidebar a::before {
        font-family: "Font Awesome 6 Free";
        font-weight: 900; font-size: 18px; 
        width: 25px; min-width: 25px; text-align: center; color: #ccc;
        display: flex; align-items: center; justify-content: center;
        transition: all 0.2s; flex-shrink: 0; content: "\f0da";
    }
    .account-modules-sidebar a[href*="favorite"]::before, .account-modules-sidebar a[href*="wishlist"]::before, .account-modules-sidebar a[href*="ulubione"]::before { content: "\f004" !important;
    }
    .account-modules-sidebar a[href*="loyalty"]::before, .account-modules-sidebar a[href*="punkty"]::before, .account-modules-sidebar a[href*="rewards"]::before { content: "\f4d3" !important;
    }
    .account-modules-sidebar a[href*="blog"]::before, .account-modules-sidebar a[href*="comment"]::before, .account-modules-sidebar a[href*="komentarze"]::before { content: "\f086" !important;
    }

    .account-modules-sidebar a span, .account-modules-sidebar a div {
        font-size: 13px !important;
        font-weight: 600 !important; color: var(--c-text) !important;
        text-align: left !important; width: auto !important; flex: 1 !important;
        margin: 0 !important;
        padding: 0 !important; position: static !important; display: block !important; line-height: 1.2 !important;
    }

    /* --- FIX: PODŚWIETLENIE AKTYWNEJ ZAKŁADKI (WYMUSZENIE RÓŻOWEGO) --- */
    .account-modules-sidebar a[href*="loyalty"], 
    .account-modules-sidebar a[href*="punkty"],
    .account-modules-sidebar a[href*="myprestaloyalty"] {
        background: var(--bg-light) !important;
        color: var(--brand-color) !important; /* Wymuszenie koloru linku */
        font-weight: 700 !important;
    }
    
    /* Wymuszenie koloru na tekście wewnątrz linku (span) */
    .account-modules-sidebar a[href*="loyalty"] span, 
    .account-modules-sidebar a[href*="punkty"] span,
    .account-modules-sidebar a[href*="myprestaloyalty"] span,
    .account-modules-sidebar a[href*="loyalty"] div, 
    .account-modules-sidebar a[href*="punkty"] div,
    .account-modules-sidebar a[href*="myprestaloyalty"] div {
        color: var(--brand-color) !important; 
    }

    /* Wymuszenie koloru ikony */
    .account-modules-sidebar a[href*="loyalty"]::before, 
    .account-modules-sidebar a[href*="punkty"]::before,
    .account-modules-sidebar a[href*="myprestaloyalty"]::before {
        color: var(--brand-color) !important;
        transform: translateX(3px);
    }
    
    /* HOVER */
    .account-modules-sidebar a:hover { background: var(--bg-light) !important; color: var(--brand-color) !important;
    }
    .account-modules-sidebar a:hover::before { color: var(--brand-color) !important; transform: translateX(3px);
    }
    .account-modules-sidebar a[href*="mailalerts"], .account-modules-sidebar a[href*="alerts"], .account-modules-sidebar a[href*="gdpr"] { display: none !important;
    }

    /* Logout */
    .logout-btn-sidebar { margin-top: 20px; padding-top: 20px; border-top: 1px solid #eee;
        text-align: center; }
    .btn-logout-modern { 
        display: flex; align-items: center;
        justify-content: center; gap: 10px;
        color: #999; font-size: 12px; font-weight: 700; text-transform: uppercase; transition: 0.2s;
    }
    .btn-logout-modern:hover { color: #d9534f; text-decoration: none; }
    .page-footer { display: none !important;
    }


    /* --- STYLE TREŚCI LOJALNOŚCIOWEJ --- */
    .loyalty-page-wrapper { font-family: inherit;
        color: #444; }

    /* Nagłówek */
    .loyalty-main-header {
        text-align: center;
        margin-bottom: 20px; background: #fff;
        padding: 30px; border-radius: 8px; border: 1px solid #f1f1f1;
    }
    .loyalty-piggy { font-size: 64px;
        color: var(--blue-loyalty); margin-bottom: 10px; }
    .loyalty-main-header h1 { font-size: 24px; font-weight: 700; color: #333;
        margin: 0 0 5px 0; text-transform: uppercase; }
    .loyalty-main-header p { color: #777; font-size: 14px; margin: 0;
    }

    /* BANNER KROKÓW */
    .loyalty-steps-banner { background: #fff; border: 1px solid #f1f1f1; border-radius: 8px;
        padding: 25px; margin-bottom: 30px; }
    .tv-loyalty-steps { display: flex; align-items: flex-start; justify-content: space-between;
    }
    .tv-step-item { display: flex; flex-direction: column; align-items: center; text-align: center; gap: 12px; flex: 1;
    }
    .tv-step-icon {
        width: 56px; height: 56px; background: #eaf8fa;
        border-radius: 50%;
        display: flex; align-items: center; justify-content: center; flex-shrink: 0;
        color: var(--blue-loyalty); margin-bottom: 5px; transition: transform 0.3s ease;
    }
    .tv-step-item:hover .tv-step-icon { transform: scale(1.1); background: var(--blue-loyalty); color: #fff;
    }
    .tv-step-icon i { font-size: 24px; }
    .tv-step-info h4 { font-size: 15px; font-weight: 700;
        color: #333; margin: 0 0 6px 0; text-transform: none; }
    .tv-step-info p { font-size: 13px; color: #666;
        margin: 0; line-height: 1.4; }
    .tv-step-info p strong { color: var(--blue-loyalty);
    }
    .tv-step-arrow { align-self: center; margin: 0 20px; color: #e0e0e0; padding-bottom: 30px;
    }
    .tv-step-arrow i { font-size: 24px; }

    /* SALDO */
    .loyalty-dashboard-card {
        display: flex;
        background: #fff; border: 1px solid #f1f1f1; border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.03); margin-bottom: 30px; overflow: hidden; flex-wrap: wrap;
    }
    .loyalty-balance-section {
        flex: 1; padding: 30px; display: flex;
        align-items: center; justify-content: center;
        border-right: 1px dashed #eee; min-width: 280px; text-align: center;
    }
    .loyalty-dashboard-card > div:only-child { border-right: none; }
    .lb-label { font-size: 11px; text-transform: uppercase;
        color: #888; font-weight: 700; display: block; margin-bottom: 5px; }
    .lb-value { font-size: 42px; font-weight: 800; line-height: 1;
        color: var(--blue-loyalty) !important; }
    .lb-unit { font-size: 18px; color: var(--blue-loyalty);
    }
    .lb-sub { font-size: 13px; color: #666; margin-top: 5px;
    }

    .loyalty-action-section {
        flex: 1; padding: 30px; background: #fdfdfd;
        display: flex; flex-direction: column;
        justify-content: center; align-items: center; min-width: 280px; text-align: center;
    }
    .la-text { margin-bottom: 15px;
        font-size: 14px; }
    .loyalty-convert-btn {
        background-color: var(--blue-loyalty) !important;
        border-color: var(--blue-loyalty) !important; color: #fff !important;
        font-weight: 700; text-transform: uppercase; font-size: 13px; padding: 10px 20px;
        display: inline-flex; align-items: center;
        gap: 8px; border-radius: 4px; transition: all 0.2s;
    }
    .loyalty-convert-btn:hover {
        background-color: #259bb5 !important;
        transform: translateY(-2px);
        box-shadow: 0 4px 10px rgba(47, 181, 210, 0.3);
    }

    /* KUPONY */
    .loyalty-section-title {
        font-size: 16px;
        font-weight: 700; margin-bottom: 15px; display: flex; align-items: center;
        gap: 10px; color: #333; border-bottom: 2px solid #f5f5f5; padding-bottom: 10px;
    }
    .loyalty-section-title i { color: #ccc; }
    .mt-4 { margin-top: 30px;
    }

    .loyalty-vouchers-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px;
    }
    .loyalty-voucher-ticket { display: flex; background: #fff; border: 1px solid #e0e0e0; border-radius: 6px; overflow: hidden; position: relative;
    }
    .loyalty-voucher-ticket.used { opacity: 0.6; filter: grayscale(1);
    }
    .lv-left {
        background: var(--blue-loyalty); color: #fff; padding: 15px;
        display: flex;
        flex-direction: column; justify-content: center; align-items: center; min-width: 100px; text-align: center;
    }
    .lv-value { font-size: 20px;
        font-weight: 800; display: block; line-height: 1; }
    .lv-label { font-size: 10px; text-transform: uppercase; opacity: 0.9; margin-top: 4px;
        font-weight: 700; }
    .lv-right { padding: 15px; flex: 1; display: flex; flex-direction: column; justify-content: center;
    }
    .lv-code {
        font-family: monospace; font-size: 16px; font-weight: 700;
        color: #333; letter-spacing: 1px;
        background: #f9f9f9; padding: 5px 10px; border-radius: 4px; border: 1px dashed #ccc;
        margin-bottom: 8px; display: inline-block;
        align-self: flex-start;
    }
    .lv-dates { font-size: 11px; color: #888; display: flex; flex-direction: column; gap: 2px;
    }
    .lv-status { margin-top: 8px; font-size: 11px; font-weight: 700; text-transform: uppercase;
    }
    .status-active { color: #72c114; }
    .status-used { color: #999;
    }

    /* HISTORIA */
    .loyalty-history-list { background: #fff; border: 1px solid #f1f1f1;
        border-radius: 6px; }
    .lhl-header {
        display: flex; background: #f9f9f9;
        padding: 12px 15px; font-weight: 700;
        font-size: 12px; color: #666; text-transform: uppercase; border-bottom: 1px solid #eee;
    }
    .lhl-item { display: flex; padding: 15px; border-bottom: 1px solid #f5f5f5; align-items: center; font-size: 13px;
    }
    .lhl-item:last-child { border-bottom: none; }
    .col-ref { flex: 2; color: #333;
    }
    .col-date { flex: 2; color: #666; }
    .col-points { flex: 1; text-align: right;
        padding-right: 20px; }
    .col-status { flex: 2; text-align: right;
    }
    
    .points-badge { background: #eefbfc; color: var(--blue-loyalty); padding: 3px 8px; border-radius: 10px;
        font-weight: 700; }
    .points-badge.negative { background: #fff5f5; color: #d01662;
    }

    .history-voucher-info {
        margin-top: 5px;
        font-size: 11px; background: #fdfdfd; border: 1px solid #eee;
        padding: 5px; border-radius: 4px; display: inline-block; text-align: left;
    }
    
    /* Etykieta ZWROTU / WYMIANY / ZAMÓWIENIA */
    .return-label { 
        display: inline-block;
        color: #d01662; font-weight: 800; font-size: 10px; 
        text-transform: uppercase; background: #fff0f5; padding: 2px 6px; 
        border-radius: 4px; margin-bottom: 2px;
    }

    .mobile-label { display: none; }
    .loyalty-pagination { margin-top: 15px; text-align: center;
    }
    .loyalty-back-footer { display: none;
    }

    /* RWD */
    @media (max-width: 991px) {
        .tv-loyalty-steps { flex-direction: column;
        gap: 20px; }
        .tv-step-arrow { display: none;
        }
        .tv-step-item { flex-direction: row; text-align: left; align-items: flex-start;
        }
        .tv-step-icon { margin-right: 15px; margin-bottom: 0;
        }
    }
    @media (max-width: 768px) {
        .loyalty-balance-section { border-right: none;
        border-bottom: 1px solid #eee; }
        .lhl-header { display: none;
        }
        .lhl-item { flex-direction: column; align-items: flex-start; gap: 8px;
        }
        .col-ref, .col-date, .col-points, .col-status { width: 100%; text-align: left; padding: 0;
        display: flex; justify-content: space-between; }
        .col-status { flex-direction: column; align-items: flex-start;
        }
        .col-status .mobile-label { margin-bottom: 5px;
        }
        .mobile-label { display: inline-block; font-weight: 600; color: #999; font-size: 11px; text-transform: uppercase;
        }
        .loyalty-vouchers-grid { grid-template-columns: 1fr;
        }
    }
</style>
{/literal}

<div class="account-sidebar-layout">

    {* --- MENU BOCZNE --- *}
    <div class="account-menu-column">
        <div class="menu-header">MOJE KONTO</div> 
        <a class="sidebar-link" href="{$link->getPageLink('identity', true)}"><i class="fa-regular fa-user"></i><span>Dane konta</span></a>
        <a class="sidebar-link" href="{$link->getPageLink('addresses', true)}"><i class="fa-regular fa-map"></i><span>Adresy</span></a>
        <a class="sidebar-link" href="{$link->getPageLink('history', true)}"><i class="fa-regular fa-folder-open"></i><span>Historia zamówień</span></a>
        
        {if !$configuration.is_catalog}
            <a class="sidebar-link" href="{$link->getPageLink('order-slip', true)}">
                <i class="fa-regular fa-file-lines"></i><span>Moje korekty</span>
            </a>
        {/if}
        
        {if $configuration.voucher_enabled && !$configuration.is_catalog}
            <a class="sidebar-link" href="{$link->getPageLink('discount', true)}">
                <i class="fa-regular fa-credit-card"></i><span>Kupony rabatowe</span>
            </a>
        {/if}
        
        {if $configuration.return_enabled && !$configuration.is_catalog}
            <a class="sidebar-link" href="{$link->getPageLink('order-follow', true)}">
                <i class="fa-solid fa-rotate-left"></i><span>Zwroty towarów</span>
            </a>
        {/if}
        
        {* MODUŁY *}
        <div class="account-modules-sidebar">
            {hook h='displayCustomerAccount'}
        </div>

        <div class="logout-btn-sidebar">
            <a href="{$link->getPageLink('index', true, NULL, 'mylogout')}" class="btn-logout-modern">
                <i class="fa-solid fa-power-off"></i>
                {l s='Sign out' d='Shop.Theme.Actions'}
            </a>
        </div>
    </div>

    {* --- PRAWA KOLUMNA: TREŚĆ --- *}
    <div class="account-form-content loyalty-page-wrapper">

        {* NAGŁÓWEK *}
        <div class="loyalty-main-header">
            <i class="fa-solid fa-piggy-bank loyalty-piggy"></i>
            <h1>Program Lojalnościowy</h1>
            <p>Zbieraj punkty za każde zakupy i wymieniaj je na rabaty!</p>
        </div>

        {* BANNER *}
        <div class="loyalty-steps-banner">
            <div class="tv-loyalty-steps">
                <div class="tv-step-item">
                    <div class="tv-step-icon"><i class="fa-solid fa-cart-shopping"></i></div>
                    <div class="tv-step-info">
                        <h4>Kupuj produkty</h4>
                        <p>Każde wydane <strong>{$loyalty_rate} zł</strong> to <strong>1 pkt</strong> na Twoim koncie</p>
                    </div>
                </div>
                <div class="tv-step-arrow"><i class="fa-solid fa-chevron-right"></i></div>
                <div class="tv-step-item">
                    <div class="tv-step-icon"><i class="fa-solid fa-piggy-bank"></i></div>
                    <div class="tv-step-info">
                        <h4>Zbieraj punkty</h4>
                        <p>Punkty sumują się automatycznie i są ważne <strong>{$loyalty_validity} dni</strong></p>
                    </div>
                </div>
                <div class="tv-step-arrow"><i class="fa-solid fa-chevron-right"></i></div>
                <div class="tv-step-item">
                    <div class="tv-step-icon"><i class="fa-solid fa-gift"></i></div>
                    <div class="tv-step-info">
                        <h4>Płać mniej</h4>
                        <p>Wymień punkty na bon: <strong>{$example_points} pkt = {Tools::displayPrice($example_discount)}</strong> rabatu</p>
                    </div>
                </div>
            </div>
        </div>

       
        {* SALDO *}
        <div class="loyalty-dashboard-card">
            <div class="loyalty-balance-section">
                <div class="lb-info">
                    <span class="lb-label">Suma dostępnych punktów:</span>
                    <div class="lb-value">{$totalPoints|intval} <span class="lb-unit">pkt</span></div>
                    <div class="lb-sub">
                        Wartość rabatu: <strong>{Tools::displayPrice($voucher)}</strong>
                    </div>
                </div>
            </div>

            {if $transformation_allowed}
                <div class="loyalty-action-section">
                    <div class="la-text">
                        Możesz wymienić punkty na bon o wartości <strong>{Tools::displayPrice($voucher)}</strong>.
                    </div>
                    <a href="{Context::getContext()->link->getModuleLink('myprestaloyalty', 'default', ['process' => 'transformpoints'])|escape:'html'}" 
                       onclick="return confirm('Czy na pewno chcesz wymienić punkty na bon rabatowy?');"
                       class="btn btn-primary loyalty-convert-btn">
                        <i class="fa-solid fa-arrows-rotate"></i> Wymień punkty na bon
                    </a>
                </div>
            {/if}
        </div>

        {* KUPONY *}
        <div class="loyalty-section-title">
            <i class="fa-solid fa-tag"></i> Moje kupony z punktów lojalnościowych
        </div>

        {if $nbDiscounts}
            <div class="loyalty-vouchers-grid">
                {foreach from=$discounts item=discount name=myLoop}
                    <div class="loyalty-voucher-ticket {if $discount->quantity == 0}used{/if}">
                        <div class="lv-left">
                            <span class="lv-value">
                                {if $discount->reduction_percent > 0}
                                    -{$discount->reduction_percent}%
                                {elseif $discount->reduction_amount}
                                    -{Tools::displayPrice($discount->reduction_amount)}
                                {else}
                                    Darmowa wysyłka
                                {/if}
                            </span>
                            <span class="lv-label">RABAT</span>
                        </div>
                    
                        <div class="lv-right">
                            <div class="lv-code">{$discount->code}</div>
                            <div class="lv-dates">
                                <span>Ważny od: {dateFormat date=$discount->date_from}</span>
                                <span>Ważny do: {dateFormat date=$discount->date_to}</span>
                            </div>
                            <div class="lv-status">
                                {if $discount->quantity > 0}
                                    <span class="status-active">DO WYKORZYSTANIA</span>
                                {else}
                                    <span class="status-used">WYKORZYSTANY</span>
                                {/if}
                            </div>
                        </div>
                    </div>
                {/foreach}
            </div>
        {else}
            <div class="alert alert-info">
                Nie masz jeszcze żadnych wygenerowanych kuponów.
            </div>
        {/if}

        {* HISTORIA *}
        <div class="loyalty-section-title mt-4">
            <i class="fa-solid fa-clock-rotate-left"></i> Historia punktów
        </div>

        {if $orders && count($orders)}
            <div class="loyalty-history-list">
                <div class="lhl-header">
                    <div class="col-ref">Zamówienie / Akcja</div>
                    <div class="col-date">Data</div>
                    <div class="col-points">Punkty</div>
                    <div class="col-status">Status / Szczegóły</div>
                </div>
                {foreach from=$displayorders item='order'}
                    <div class="lhl-item">
                        {* KOLUMNA AKCJA *}
                        <div class="col-ref">
                            <span class="mobile-label">Zamówienie:</span>
                            
                            {if $order.points < 0}
                                {if $order.id > 0}
                                    {* ZWROT *}
                                    <span class="return-label"><i class="fa-solid fa-rotate-left"></i> ZWROT PRODUKTÓW</span><br>
                                    <span style="font-size:11px; color:#999;">dot. zamówienia: #{$order.reference}</span>
                                {else}
                                    {* WYMIANA *}
                                    <span class="return-label" style="background-color: #2fb5d2; color: #fff;">
                                        <i class="fa-solid fa-gift"></i> WYMIANA
                                    </span><br>
                                    <span style="font-size:11px; color:#999;">Wygenerowano bon: <strong>{$order.voucher_code}</strong></span>
                                {/if}
                            {elseif $order.reference}
                                {* ZAMÓWIENIE *}
                                <span class="return-label" style="background-color: #e6fffa; color: #00b894;">
                                    <i class="fa-solid fa-cart-plus"></i> ZAMÓWIENIE
                                </span><br>
                                <span style="font-size:11px; color:#999;">Nr: <strong>#{$order.reference}</strong></span>
                            {else}
                                Wymiana punktów
                            {/if}
                        </div>
                        
                        <div class="col-date">
                            <span class="mobile-label">Data:</span>
                            {dateFormat date=$order.date full=0}
                        </div>
                        
                        <div class="col-points">
                            <span class="mobile-label">Punkty:</span>
                            {if $order.points < 0}
                                <span class="points-badge negative">{$order.points|intval} pkt</span>
                            {else}
                                <span class="points-badge">{$order.points|intval} pkt</span>
                            {/if}
                        </div>
                        
                        <div class="col-status">
                            <span class="mobile-label">Status:</span>
                            <div>
                                {if $order.points < 0}
                                    {if $order.id > 0}
                                        <span style="color:#d01662; font-weight:700; font-size:12px;">Korekta salda</span>
                                    {else}
                                        <span style="color:#2fb5d2; font-weight:700; font-size:12px;">Wykorzystano</span>
                                    {/if}
                                {elseif $order.state == 'Wykorzystane' || $order.state == 'Already converted'}
                                    <span style="color:#2fb5d2; font-weight:700;">Przyznano</span>
                                {else}
                                    {$order.state|escape:'html':'UTF-8'}
                                {/if}
                            </div>
                        </div>
                    </div>
                {/foreach}
            </div>

            <div class="loyalty-pagination">
                {if $orders|@count > $nbpagination}
                    <form class="showall" action="{$pagination_link}" method="get">
                        <button type="submit" class="btn btn-secondary">
                            <span>Pokaż wszystkie</span>
                        </button>
                        <input name="n" id="nb_item" class="hidden" value="{$orders|@count}"/>
                        <input name="process" class="hidden" value="summary"/>
                    </form>
                {/if}
            </div>
        {else}
            <div class="alert alert-warning">
                Brak historii punktów. Złóż pierwsze zamówienie!
            </div>
        {/if}

    </div>
</div>
{/block}