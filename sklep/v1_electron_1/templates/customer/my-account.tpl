{**
 * 2007-2025 PrestaShop
 * my-account.tpl - FINAL VERSION + PRO DASHBOARD
 *}
{extends file='customer/page.tpl'}

{block name='page_title'}
  {l s='Your account' d='Shop.Theme.Customeraccount'}
{/block}

{block name='page_content'}

{literal}
<style>
    :root {
        --brand-color: #d01662; /* Twoj kolor glowny */
        --brand-light: #fce4ec; /* Bardzo jasna wersja koloru paska */
        --text-color: #333333;
        --bg-light: #f8f9fa;
        --border-color: #eee;
    }

    /* GŁÓWNY UKŁAD */
    .account-sidebar-layout {
        display: grid;
        grid-template-columns: 300px 1fr;
        gap: 35px;
        margin-bottom: 60px;
        align-items: stretch; /* Wyrownanie wysokosci */
    }
    @media (max-width: 991px) {
        .account-sidebar-layout { grid-template-columns: 1fr; gap: 30px; }
    }

    /* --- LEWA STRONA: MENU --- */
    .account-menu-column {
        display: flex; flex-direction: column; gap: 8px;
        background: #fff; padding: 25px 20px; border-radius: 12px;
        border: 1px solid #f0f0f0;
        box-shadow: 0 5px 20px rgba(0,0,0,0.03);
    }
    .menu-header {
        font-size: 11px; text-transform: uppercase; letter-spacing: 1.5px;
        color: #aaa; font-weight: 800; margin-bottom: 15px; padding-left: 15px;
    }
    
    /* Stylizacja Linku Systemowego */
    .sidebar-link {
        display: flex; align-items: center; padding: 12px 15px;
        background: transparent; border-radius: 8px; text-decoration: none !important;
        transition: all 0.2s ease; color: var(--text-color); font-weight: 600;
        border: 1px solid transparent; gap: 15px;
    }
    .sidebar-link i {
        font-size: 18px; width: 25px; text-align: center;
        color: #ccc; transition: all 0.2s; flex-shrink: 0;
        display: flex; justify-content: center; align-items: center;
    }
    .sidebar-link:hover { background: var(--bg-light); color: var(--brand-color); }
    .sidebar-link:hover i { color: var(--brand-color); transform: translateX(3px); }
    
    
    /* --- LEWA STRONA: MODUŁY (FIX) --- */
    .account-modules-sidebar { display: flex !important; flex-direction: column !important; gap: 8px !important; }
    .account-modules-sidebar ul, .account-modules-sidebar li { display: contents !important; list-style: none !important; margin: 0 !important; padding: 0 !important; }

    .account-modules-sidebar a {
        display: flex !important; align-items: center !important; flex-direction: row !important; justify-content: flex-start !important;
        padding: 12px 15px !important; margin: 0 !important; background: transparent !important; 
        border-radius: 8px !important; text-decoration: none !important; color: var(--text-color) !important; 
        width: 100% !important; min-height: 44px; position: relative !important; box-sizing: border-box !important; gap: 15px !important;
    }

    /* Ukrywamy stare ikony */
    .account-modules-sidebar a i, .account-modules-sidebar a svg, .account-modules-sidebar a img, .account-modules-sidebar a .material-icons { display: none !important; }

    /* Wstawiamy nowe równe ikony */
    .account-modules-sidebar a::before {
        font-family: "Font Awesome 6 Free"; font-weight: 900; font-size: 18px; 
        width: 25px; min-width: 25px; text-align: center; color: #ccc;
        display: flex; align-items: center; justify-content: center;
        transition: all 0.2s; flex-shrink: 0; content: "\f0da";
    }
    /* Ikony dedykowane */
    .account-modules-sidebar a[href*="favorite"]::before, .account-modules-sidebar a[href*="wishlist"]::before, .account-modules-sidebar a[href*="ulubione"]::before { content: "\f004" !important; }
    .account-modules-sidebar a[href*="loyalty"]::before, .account-modules-sidebar a[href*="punkty"]::before, .account-modules-sidebar a[href*="rewards"]::before { content: "\f4d3" !important; }
    .account-modules-sidebar a[href*="blog"]::before, .account-modules-sidebar a[href*="comment"]::before, .account-modules-sidebar a[href*="komentarze"]::before { content: "\f086" !important; }

    /* Tekst modułów */
    .account-modules-sidebar a span, .account-modules-sidebar a div {
        font-size: 13px !important; font-weight: 600 !important; color: var(--text-color) !important;
        text-align: left !important; width: auto !important; flex: 1 !important;
        margin: 0 !important; padding: 0 !important; position: static !important; display: block !important; line-height: 1.2 !important;
    }

    /* Hover modułów */
    .account-modules-sidebar a:hover { background: var(--bg-light) !important; color: var(--brand-color) !important; }
    .account-modules-sidebar a:hover::before { color: var(--brand-color) !important; transform: translateX(3px); }
    /* Ukrywanie śmieci */
    .account-modules-sidebar a[href*="mailalerts"], .account-modules-sidebar a[href*="alerts"], .account-modules-sidebar a[href*="gdpr"] { display: none !important; }


    /* Przycisk wylogowania */
    .logout-btn-sidebar { margin-top: 20px; padding-top: 20px; border-top: 1px solid #eee; text-align: center; }
    .btn-logout-modern { 
        display: flex; align-items: center; justify-content: center; gap: 10px;
        color: #999; font-size: 12px; font-weight: 700; text-transform: uppercase; transition: 0.2s; 
    }
    .btn-logout-modern:hover { color: #d9534f; text-decoration: none; }

    /* --- PRAWA STRONA: DASHBOARD "PRO" --- */
    .account-dashboard-content {
        display: flex;
        justify-content: center;
        align-items: center;
    }
    .dashboard-welcome-pro {
        background: #fff;
        /* Delikatny gradient tła */
        background: linear-gradient(145deg, #ffffff 0%, #fffcfd 100%);
        border: 1px solid #f0f0f0;
        border-radius: 16px; /* Bardziej zaokrąglone rogi */
        padding: 60px 40px;
        text-align: center;
        display: flex; flex-direction: column; align-items: center;
        box-shadow: 0 10px 30px rgba(0,0,0,0.04); /* Głębszy cień */
        width: 100%;
        height: 100%; /* Rozciągnij na wysokość */
        justify-content: center;
    }
    
    /* Nowy wrapper na ikonę */
    .welcome-icon-wrapper {
        width: 100px;
        height: 100px;
        background: var(--brand-light); /* Jasny róż */
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 30px;
        box-shadow: 0 5px 15px rgba(208, 22, 98, 0.15); /* Różowa poświata */
    }
    
    .welcome-icon-pro {
        font-size: 45px;
        color: var(--brand-color); /* Różowa ikona */
    }

    .welcome-title-pro {
        font-size: 32px; /* Większy tytuł */
        font-weight: 900;
        color: #222;
        margin: 0 0 15px 0;
        letter-spacing: -0.5px;
    }
    
    .welcome-text-pro {
        font-size: 16px;
        color: #666; /* Ciemniejszy tekst dla lepszego kontrastu */
        max-width: 550px;
        line-height: 1.7;
        margin: 0 auto;
    }
    
    /* Wyróżnienie w tekście */
    .welcome-highlight {
        color: var(--brand-color);
        font-weight: 700;
    }

    .page-footer { display: none !important; }
</style>
{/literal}

<div class="account-sidebar-layout">

    {* --- MENU BOCZNE (LEWA) --- *}
    <div class="account-menu-column">
        <div class="menu-header">MOJE KONTO</div>
        
        <a class="sidebar-link" href="{$urls.pages.identity}">
            <i class="fa-regular fa-user"></i>
            <span>Dane konta</span>
        </a>
        
        {if $customer.addresses|count}
            <a class="sidebar-link" href="{$urls.pages.addresses}">
                <i class="fa-regular fa-map"></i>
                <span>Adresy</span>
            </a>
        {else}
            <a class="sidebar-link" href="{$urls.pages.address}">
                <i class="fa-solid fa-location-dot"></i>
                <span>Dodaj pierwszy adres</span>
            </a>
        {/if}

        {if !$configuration.is_catalog}
            <a class="sidebar-link" href="{$urls.pages.history}">
                <i class="fa-regular fa-folder-open"></i>
                <span>Historia zamówień</span>
            </a>
        {/if}

        {if !$configuration.is_catalog}
            <a class="sidebar-link" href="{$urls.pages.order_slip}">
                <i class="fa-regular fa-file-lines"></i>
                <span>Moje korekty</span>
            </a>
        {/if}

        {if $configuration.voucher_enabled && !$configuration.is_catalog}
            <a class="sidebar-link" href="{$urls.pages.discount}">
                <i class="fa-regular fa-credit-card"></i>
                <span>Kupony rabatowe</span>
            </a>
        {/if}

        {if $configuration.return_enabled && !$configuration.is_catalog}
            <a class="sidebar-link" href="{$urls.pages.order_follow}">
                <i class="fa-solid fa-rotate-left"></i>
                <span>Zwroty towarów</span>
            </a>
        {/if}

        {* --- MODUŁY (DÓŁ) --- *}
        <div class="account-modules-sidebar">
            {block name='display_customer_account'}
                {capture name="modules_content"}{hook h='displayCustomerAccount'}{/capture}
                {$smarty.capture.modules_content|replace:'MOJE PUNKTY LOJALNOŚCIOWE':'Program Lojalnościowy'|replace:'Moje punkty lojalnościowe':'Program Lojalnościowy'|replace:'MOJE KOMENTARZE NA BLOGU':'Komentarze na blogu'|replace:'Moje komentarze na blogu':'Komentarze na blogu'|replace:'MOJE ULUBIONE':'Moje ulubione'|replace:'Moje ulubione':'Moje ulubione'|replace:'MOJE POWIADOMIENIA':''|replace:'MOJE ALERTY':'' nofilter}
            {/block}
        </div>

        <div class="logout-btn-sidebar">
            <a href="{$urls.actions.logout}" class="btn-logout-modern">
                <i class="fa-solid fa-power-off"></i>
                {l s='Sign out' d='Shop.Theme.Actions'}
            </a>
        </div>
    </div>

    {* --- PRAWA STRONA (Dashboard PRO) --- *}
    <div class="account-dashboard-content">
        <div class="dashboard-welcome-pro">
            
            {* Nowa, kolorowa ikona w kółku *}
            <div class="welcome-icon-wrapper">
                <i class="fa-solid fa-hand-sparkles welcome-icon-pro"></i>
            </div>

            <h1 class="welcome-title-pro">Cześć, {$customer.firstname}!</h1>
            
            {* Nowy, bardziej angażujący opis *}
            <p class="welcome-text-pro">
                Witaj w Twoim centrum dowodzenia. To miejsce, gdzie masz pełną kontrolę nad swoimi zakupami. Śledź przesyłki, sprawdzaj zgromadzone <span class="welcome-highlight">punkty lojalnościowe</span> i zarządzaj listą ulubionych produktów.
                <br><br>
                Wszystkie opcje znajdziesz w menu po lewej stronie.
            </p>
        </div>
    </div>

</div>
{/block}

{block name='page_footer'}{/block}