{*
 * Copyright ETS Software Technology Co., Ltd
 * Redesigned for BigBio - Standardized Account Layout + Table Beautification
*}
{extends file='customer/page.tpl'}

{block name='page_title'}
  {l s='My blog comments' mod='ets_blog'}
{/block}

{block name='page_content'}

{literal}
<script>
    /* --- FIX JS: Naprawa błędu "$ is not defined" --- */
    if (typeof $ === 'undefined') {
        window.$ = window.jQuery = function(selector) {
            return {
                ready: function(callback) {
                    document.addEventListener('DOMContentLoaded', callback);
                },
                on: function() {},
                click: function() {}
            };
        };
    }
</script>

<style>
    /* --- ZMIENNE --- */
    :root { 
        --c-text: #232323; --c-text-light: #777; 
        --c-brand: #d01662; --brand-color: #d01662;
        --c-brand-bg: #fff0f5; --bg-light: #f8f9fa;
        --c-border: #eaeaea; --font-base: 'Open Sans', Helvetica, Arial, sans-serif;
    }

    #content .alert-warning { display: none !important; }
    .blog-page-wrapper { color: var(--c-text); font-family: var(--font-base); }

    /* --- FIX UKŁADU --- */
    #left-column { display: none !important; }
    #content-wrapper, .container #content-wrapper, #main .page-content { 
        width: 100% !important; max-width: 100% !important; flex: 0 0 100% !important; 
        padding: 0 !important; margin: 0 !important; float: none !important;
    }

    /* UKŁAD GLÓWNY */
    .account-sidebar-layout { 
        display: grid; grid-template-columns: 300px 1fr; gap: 40px; 
        margin-bottom: 60px; align-items: start; width: 100%; 
    }
    @media (max-width: 991px) { .account-sidebar-layout { grid-template-columns: 1fr; gap: 30px; } }

    /* --- MENU BOCZNE --- */
    .account-menu-column { display: flex; flex-direction: column; gap: 8px; background: #fff; padding: 25px 20px; border-radius: 8px; border: 1px solid #f0f0f0; box-shadow: 0 2px 15px rgba(0,0,0,0.03); }
    .menu-header { font-size: 11px; text-transform: uppercase; letter-spacing: 1.5px; color: #aaa; font-weight: 800; margin-bottom: 10px; padding-left: 15px; }
    .sidebar-link { display: flex; align-items: center; padding: 12px 15px; background: transparent; border-radius: 6px; text-decoration: none !important; transition: all 0.2s ease; color: var(--c-text); font-weight: 600; border: 1px solid transparent; gap: 15px; }
    .sidebar-link i { font-size: 18px; width: 25px; text-align: center; color: #ccc; transition: all 0.2s; flex-shrink: 0; display: flex; justify-content: center; align-items: center; }
    
    /* Hover i Active */
    .sidebar-link:hover, .sidebar-link.active-page { background: var(--bg-light); color: var(--brand-color); border-color: #eee; }
    .sidebar-link:hover i, .sidebar-link.active-page i { color: var(--brand-color); transform: translateX(3px); }
    
    /* MODUŁY */
    .account-modules-sidebar { display: flex !important; flex-direction: column !important; gap: 8px !important; }
    .account-modules-sidebar ul, .account-modules-sidebar li { display: contents !important; list-style: none !important; margin: 0 !important; padding: 0 !important; }
    .account-modules-sidebar a { display: flex !important; align-items: center !important; flex-direction: row !important; justify-content: flex-start !important; padding: 12px 15px !important; margin: 0 !important; background: transparent !important; border-radius: 6px !important; text-decoration: none !important; color: var(--c-text) !important; width: 100% !important; min-height: 44px; position: relative !important; box-sizing: border-box !important; gap: 15px !important; border: 1px solid transparent !important; }
    .account-modules-sidebar a i, .account-modules-sidebar a svg, .account-modules-sidebar a img, .account-modules-sidebar a .material-icons { display: none !important; }
    .account-modules-sidebar a::before { font-family: "Font Awesome 6 Free"; font-weight: 900; font-size: 18px; width: 25px; min-width: 25px; text-align: center; color: #ccc; display: flex; align-items: center; justify-content: center; transition: all 0.2s; flex-shrink: 0; content: "\f0da"; }
    
    /* Active Blog */
    .account-modules-sidebar a[href*="blog"], .account-modules-sidebar a[href*="comment"] { background: #f8f9fa !important; color: var(--brand-color) !important; border: 1px solid #eee !important; }
    .account-modules-sidebar a[href*="blog"]::before, .account-modules-sidebar a[href*="comment"]::before { content: "\f086" !important; color: var(--brand-color) !important; }
    /* Other Icons */
    .account-modules-sidebar a[href*="wishlist"]::before, .account-modules-sidebar a[href*="ulubione"]::before { content: "\f004" !important; }
    .account-modules-sidebar a[href*="loyalty"]::before, .account-modules-sidebar a[href*="punkty"]::before { content: "\f4d3" !important; }
    .account-modules-sidebar a span, .account-modules-sidebar a div { font-size: 13px !important; font-weight: 600 !important; color: inherit !important; text-align: left !important; width: auto !important; flex: 1 !important; margin: 0 !important; padding: 0 !important; position: static !important; display: block !important; line-height: 1.2 !important; }
    .account-modules-sidebar a:hover { background: #f8f9fa !important; color: var(--brand-color) !important; border-color: #eee !important; }
    .account-modules-sidebar a:hover::before { color: var(--brand-color) !important; transform: translateX(3px); }
    .account-modules-sidebar a[href*="mailalerts"], .account-modules-sidebar a[href*="alerts"], .account-modules-sidebar a[href*="gdpr"] { display: none !important; }

    .logout-btn-sidebar { margin-top: 20px; padding-top: 20px; border-top: 1px solid #eee; text-align: center; }
    .btn-logout-modern { display: flex; align-items: center; justify-content: center; gap: 10px; color: #999; font-size: 12px; font-weight: 700; text-transform: uppercase; transition: 0.2s; }
    .btn-logout-modern:hover { color: #d9534f; text-decoration: none; }
    .page-footer { display: none !important; }


    /* --- STYLE CONTENTU BLOGA (HEADER) --- */
    .white-box { background: #fff; border: 1px solid var(--c-border); margin-bottom: 25px; border-radius: 8px; overflow: hidden; }
    .header-box { display: flex; align-items: center; gap: 15px; padding: 20px 25px; border-bottom: 1px solid #f9f9f9; }
    .header-box h1 { font-size: 16px; font-weight: 800; text-transform: uppercase; margin: 0; color: var(--c-text); letter-spacing: 0.5px; }

    /* ==========================================================================
       BEAUTIFICATION - STYLIZACJA TABELI MODUŁU ETS BLOG
       ========================================================================== */
    
    /* Kontener tabeli */
    .ets-blog-wrapper-detail {
        padding: 0 !important;
        margin: 0 !important;
        width: 100%;
        overflow-x: auto;
    }

    /* Reset tabeli */
    .ets-blog-wrapper-detail table { 
        width: 100%; 
        border-collapse: collapse; 
        margin-bottom: 0 !important; 
        border: none !important;
    }

    /* Nagłówek tabeli */
    .ets-blog-wrapper-detail thead tr {
        background-color: #fcfcfc !important;
        border-bottom: 1px solid #eaeaea !important;
    }
    
    .ets-blog-wrapper-detail th { 
        text-align: left !important; 
        padding: 18px 20px !important; 
        font-size: 11px !important; 
        text-transform: uppercase !important; 
        color: #999 !important; 
        font-weight: 800 !important; 
        letter-spacing: 0.5px !important; 
        border: none !important;
        background: transparent !important;
        vertical-align: middle !important;
    }

    /* Wiersze tabeli */
    .ets-blog-wrapper-detail tbody tr {
        transition: background-color 0.2s;
    }
    .ets-blog-wrapper-detail tbody tr:hover {
        background-color: #fdfdfd;
    }

    .ets-blog-wrapper-detail td { 
        padding: 18px 20px !important; 
        vertical-align: middle !important; 
        border-bottom: 1px solid #f0f0f0 !important; 
        font-size: 13px !important; 
        color: #333 !important; 
        background: transparent !important;
    }
    .ets-blog-wrapper-detail tr:last-child td { border-bottom: none !important; }

    /* --- FILTRY (INPUTY) --- */
    /* Inputy tekstowe i Selecty */
    .ets-blog-wrapper-detail input[type="text"], 
    .ets-blog-wrapper-detail select {
        border: 1px solid #e0e0e0 !important; 
        background-color: #fff !important;
        padding: 8px 12px !important; 
        border-radius: 6px !important; 
        font-size: 12px !important; 
        width: 100% !important; 
        max-width: 100% !important;
        height: 38px !important;
        box-shadow: none !important;
        transition: border-color 0.2s;
    }
    .ets-blog-wrapper-detail input[type="text"]:focus, 
    .ets-blog-wrapper-detail select:focus {
        border-color: var(--brand-color) !important;
        outline: none !important;
    }

    /* PRZYCISK FILTRUJ */
    .ets-blog-wrapper-detail .btn.btn-default,
    .ets-blog-wrapper-detail button[name="submitFilter"] {
        background: #fff !important; 
        border: 1px solid #d01662 !important; /* Różowa ramka */
        color: #d01662 !important; 
        font-weight: 700 !important; 
        text-transform: uppercase !important;
        font-size: 11px !important;
        padding: 0 20px !important; 
        border-radius: 6px !important;
        height: 38px !important;
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        cursor: pointer !important;
        transition: 0.2s !important;
    }
    .ets-blog-wrapper-detail .btn.btn-default:hover,
    .ets-blog-wrapper-detail button[name="submitFilter"]:hover { 
        background: #d01662 !important; 
        color: #fff !important; 
    }
    
    /* Ikona w przycisku (jeśli jest) */
    .ets-blog-wrapper-detail .icon-search { display: none; }
    .ets-blog-wrapper-detail button[name="submitFilter"]::before {
        content: "\f0b0"; /* Ikonka filtra FontAwesome */
        font-family: "Font Awesome 6 Free"; font-weight: 900;
        margin-right: 6px;
    }

    /* PRZYCISK RESET (jeśli istnieje) */
    .ets-blog-wrapper-detail button[name="submitReset"] {
        background: transparent !important;
        border: 1px solid #eee !important;
        color: #999 !important;
        border-radius: 6px !important;
        height: 38px !important;
        margin-left: 5px !important;
    }
    .ets-blog-wrapper-detail button[name="submitReset"]:hover {
        border-color: #999 !important;
        color: #333 !important;
    }

    /* --- EMPTY STATE (Brak wyników) --- */
    .ets-blog-wrapper-detail .list-empty,
    .ets-blog-wrapper-detail tr td[colspan] {
        text-align: center !important;
        padding: 40px !important;
        color: #999 !important;
        font-style: normal !important;
        background: #fff !important;
    }
    /* Dodajemy ikonę przez CSS do pustego stanu */
    .ets-blog-wrapper-detail tr td[colspan]:empty::before,
    .ets-blog-wrapper-detail tr td[colspan]::before {
        content: "\f0e5"; /* Ikonka komentarza */
        font-family: "Font Awesome 6 Free"; font-weight: 400;
        display: block; font-size: 30px; margin-bottom: 10px; color: #eee;
    }

    /* Ukrycie starych nagłówków modułu */
    .page-header { display: none !important; }
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

    {* --- TREŚĆ BLOGA --- *}
    <div class="account-form-content blog-page-wrapper">
        
        {* Nagłówek *}
        <div class="white-box header-box">
            <h1>Moje komentarze na blogu</h1>
        </div>

        {* Tabela z modułu (w białym kafelku) *}
        <div class="white-box">
            
            {if isset($sucsecfull_html) && $sucsecfull_html}
                <div style="padding: 20px;">{$sucsecfull_html nofilter}</div>
            {/if}
            
            {if isset($errors_html) && $errors_html}
                <div style="padding: 20px;">{$errors_html nofilter}</div>
            {/if}

            <div class="ets_blog_layout_list ets-blog-wrapper-form-managament ets-blog-wrapper-detail">
                {$html_content nofilter}
            </div>
        </div>

    </div>
</div>
{/block}