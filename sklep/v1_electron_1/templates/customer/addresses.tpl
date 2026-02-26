{**
 * 2007-2025 PrestaShop
 * addresses.tpl - MERGED: Menu 1:1 + Modern Tiles
 *}
{extends file='customer/page.tpl'}

{block name='page_title'}
  {l s='Your addresses' d='Shop.Theme.Customeraccount'}
{/block}

{block name='page_content'}

{literal}
<style>
    :root {
        --brand-color: #d01662; /* Twój kolor malinowy */
        --text-dark: #222;
        --text-light: #666;
        --bg-light: #f8f9fa;
        --border-color: #eee;
        --success-green: #28a745;
    }

    /* --- NOWOCZESNY ALERT SUKCESU --- */
    .alert-success {
        background-color: #fff !important; 
        border: 1px solid #f1f1f1 !important; 
        border-left: 4px solid var(--success-green) !important; 
        color: #444 !important; 
        padding: 15px 20px !important; 
        border-radius: 6px !important;
        box-shadow: 0 4px 15px rgba(0,0,0,0.05) !important; 
        font-weight: 600; font-size: 13px;
        display: flex; align-items: center; margin-bottom: 30px; position: relative;
    }
    .alert-success ul { margin-bottom: 0; padding-left: 0; list-style: none; }
    .alert-success::before {
        content: "\f00c"; font-family: "Font Awesome 6 Free"; font-weight: 900;
        color: var(--success-green); font-size: 14px; margin-right: 15px;
        background: #ecf9f1; width: 28px; height: 28px;
        display: flex; align-items: center; justify-content: center;
        border-radius: 50%; flex-shrink: 0;
    }

    /* --- UKŁAD STRONY --- */
    .account-sidebar-layout {
        display: grid; grid-template-columns: 300px 1fr; gap: 40px;
        margin-bottom: 60px; align-items: start;
    }
    @media (max-width: 991px) { .account-sidebar-layout { grid-template-columns: 1fr; gap: 30px; } }

    /* --- MENU BOCZNE (IDENTYCZNE JAK NA DASHBOARD) --- */
    .account-menu-column {
        display: flex; flex-direction: column; gap: 8px;
        background: #fff; padding: 25px 20px; border-radius: 8px;
        border: 1px solid #f0f0f0;
        box-shadow: 0 2px 15px rgba(0,0,0,0.03);
    }
    .menu-header {
        font-size: 11px; text-transform: uppercase; letter-spacing: 1.5px;
        color: #aaa; font-weight: 800; margin-bottom: 10px; padding-left: 15px;
    }
    
    /* Linki systemowe */
    .sidebar-link {
        display: flex; align-items: center; padding: 12px 15px;
        background: transparent; border-radius: 6px; text-decoration: none !important;
        transition: all 0.2s ease; color: var(--text-dark); font-weight: 600;
        border: 1px solid transparent; gap: 15px;
    }
    .sidebar-link i {
        font-size: 18px; width: 25px; text-align: center;
        color: #ccc; transition: all 0.2s; flex-shrink: 0;
        display: flex; justify-content: center; align-items: center;
    }
    .sidebar-link:hover, .sidebar-link.active-page { background: var(--bg-light); color: var(--brand-color); }
    .sidebar-link:hover i, .sidebar-link.active-page i { color: var(--brand-color); transform: translateX(3px); }

    /* --- NAPRAWA MODUŁÓW (DÓŁ) --- */
    .account-modules-sidebar { display: flex !important; flex-direction: column !important; gap: 8px !important; }
    .account-modules-sidebar ul, .account-modules-sidebar li { display: contents !important; list-style: none !important; margin: 0 !important; padding: 0 !important; }

    .account-modules-sidebar a {
        display: flex !important; align-items: center !important; flex-direction: row !important; justify-content: flex-start !important;
        padding: 12px 15px !important; margin: 0 !important; background: transparent !important; 
        border-radius: 6px !important; text-decoration: none !important; color: var(--text-dark) !important; 
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

    .account-modules-sidebar a span, .account-modules-sidebar a div {
        font-size: 13px !important; font-weight: 600 !important; color: var(--text-dark) !important;
        text-align: left !important; width: auto !important; flex: 1 !important;
        margin: 0 !important; padding: 0 !important; position: static !important; display: block !important; line-height: 1.2 !important;
    }
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
    .page-footer { display: none !important; }


    /* --- SIATKA ADRESÓW --- */
    .addresses-grid {
        display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
        gap: 25px; margin-bottom: 40px;
    }

    /* KAFELKI */
    .address-tile {
        background: #fff; border-radius: 16px; box-shadow: 0 5px 20px rgba(0,0,0,0.05); 
        border: 1px solid transparent; display: flex; flex-direction: column;
        transition: border-color 0.3s ease; position: relative; overflow: hidden; height: 100%;
    }
    .address-tile:hover { box-shadow: 0 5px 20px rgba(0,0,0,0.05); border-color: var(--brand-color); }
    .address-tile.is-company { background-color: #fffcfd; }

    .address-header {
        padding: 18px 25px; border-bottom: 1px solid #f5f5f5;
        display: flex; justify-content: space-between; align-items: center; background: #fff;
    }
    .address-alias {
        font-weight: 800; text-transform: uppercase; font-size: 14px;
        color: var(--text-dark); display: flex; align-items: center; gap: 8px;
    }
    .address-alias i { color: var(--brand-color); font-size: 18px; }

    .address-body { padding: 25px; flex-grow: 1; font-size: 14px; line-height: 1.7; color: var(--text-light); }
    .address-body address { font-style: normal; }

    /* Stopka kafelka */
    .address-footer {
        padding: 15px 25px; border-top: 1px solid #f5f5f5;
        display: flex; gap: 15px; background: #fff;
    }
    .btn-action {
        flex: 1; display: flex; align-items: center; justify-content: center;
        padding: 10px; font-size: 12px; font-weight: 700; text-transform: uppercase;
        border-radius: 8px; text-decoration: none !important; transition: 0.2s; cursor: pointer;
        border: 1px solid #eee; color: var(--text-light); background: #fff;
    }
    .btn-action i { font-size: 14px; margin-right: 6px; }
    .btn-edit:hover { border-color: var(--brand-color); color: var(--brand-color); background: #fff5f8; }
    .btn-delete:hover { border-color: #e74c3c; color: #e74c3c; background: #fff5f5; }

    /* HEADER SEKCJI */
    .section-header {
        display: flex; justify-content: space-between; align-items: center;
        margin-bottom: 25px; border-bottom: 1px solid #eee; padding-bottom: 15px;
    }
    .section-title {
        font-size: 18px; font-weight: 700; text-transform: uppercase; color: var(--text-dark); margin: 0;
    }
    
    /* Przycisk dodawania */
    .btn-add-text {
        color: var(--brand-color); font-weight: 800; text-transform: uppercase;
        font-size: 13px; text-decoration: none !important;
        display: flex; align-items: center; gap: 8px; transition: all 0.2s; cursor: pointer;
    }
    .btn-add-text i { font-size: 16px; transition: transform 0.2s; }
    .btn-add-text:hover { color: #b01252; }
    .btn-add-text:hover i { transform: rotate(90deg); }
</style>
{/literal}

<div class="account-sidebar-layout">
    
    {* --- MENU BOCZNE (IDENTYCZNE JAK NA DASHBOARD) --- *}
    <div class="account-menu-column">
        <div class="menu-header">MOJE KONTO</div>
        
        <a class="sidebar-link" href="{$urls.pages.identity}">
            <i class="fa-regular fa-user"></i>
            <span>Dane konta</span>
        </a>
        
        <a class="sidebar-link active-page" href="{$urls.pages.addresses}">
            <i class="fa-regular fa-map"></i>
            <span>Adresy</span>
        </a>

        {if $customer.addresses|count == 0}
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

        {* --- MODUŁY (WYGENEROWANE DYNAMICZNIE) --- *}
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

    {* --- TREŚĆ ADRESÓW --- *}
    <div class="account-form-content">
        
        {* Header z tytułem i przyciskiem po prawej *}
        <div class="section-header">
            <h1 class="section-title">Twoje Adresy</h1>
            
            <a href="{$urls.pages.address}" data-link-action="add-address" class="btn-add-text">
              <i class="fa-solid fa-plus"></i>
              <span>NOWY ADRES</span>
            </a>
        </div>

        {* Lista Kafelków *}
        <div class="addresses-grid">
            {foreach $customer.addresses as $address}
              {block name='customer_address'}
                {include file='customer/_partials/block-address.tpl' address=$address}
              {/block}
            {/foreach}
        </div>
        
    </div>
</div>
{/block}