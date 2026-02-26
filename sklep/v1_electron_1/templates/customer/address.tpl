{**
 * 2007-2025 PrestaShop
 * address.tpl - MERGED: Menu 1:1 + White Box Form
 *}
{extends file='customer/page.tpl'}

{block name='page_title'}
  {if $editing}
    {l s='Update your address' d='Shop.Theme.Customeraccount'}
  {else}
    {l s='New address' d='Shop.Theme.Customeraccount'}
  {/if}
{/block}

{block name='page_content'}

{literal}
<style>
    :root {
        --brand-color: #d01662;
        --text-dark: #222;
        --text-light: #555;
        --bg-light: #f8f9fa;
        --border-color: #eee;
    }

    /* UKŁAD STRONY */
    .account-sidebar-layout { display: grid; grid-template-columns: 300px 1fr; gap: 40px; margin-bottom: 60px; align-items: start; }
    @media (max-width: 991px) { .account-sidebar-layout { grid-template-columns: 1fr; gap: 30px; } }

    /* --- MENU BOCZNE (IDENTYCZNE JAK WSZĘDZIE) --- */
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
    
    /* NAPRAWA MODUŁÓW (DÓŁ) */
    .account-modules-sidebar { display: flex !important; flex-direction: column !important; gap: 8px !important; }
    .account-modules-sidebar ul, .account-modules-sidebar li { display: contents !important; list-style: none !important; margin: 0 !important; padding: 0 !important; }

    .account-modules-sidebar a {
        display: flex !important; align-items: center !important; flex-direction: row !important; justify-content: flex-start !important;
        padding: 12px 15px !important; margin: 0 !important; background: transparent !important; 
        border-radius: 6px !important; text-decoration: none !important; color: var(--text-dark) !important; 
        width: 100% !important; min-height: 44px; position: relative !important; box-sizing: border-box !important; gap: 15px !important;
    }
    /* Nowe ikony */
    .account-modules-sidebar a i, .account-modules-sidebar a svg, .account-modules-sidebar a img, .account-modules-sidebar a .material-icons { display: none !important; }
    .account-modules-sidebar a::before {
        font-family: "Font Awesome 6 Free"; font-weight: 900; font-size: 18px; 
        width: 25px; min-width: 25px; text-align: center; color: #ccc;
        display: flex; align-items: center; justify-content: center;
        transition: all 0.2s; flex-shrink: 0; content: "\f0da";
    }
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
    .account-modules-sidebar a[href*="mailalerts"], .account-modules-sidebar a[href*="alerts"], .account-modules-sidebar a[href*="gdpr"] { display: none !important; }

    /* Logout */
    .logout-btn-sidebar { margin-top: 20px; padding-top: 20px; border-top: 1px solid #eee; text-align: center; }
    .btn-logout-modern { 
        display: flex; align-items: center; justify-content: center; gap: 10px;
        color: #999; font-size: 12px; font-weight: 700; text-transform: uppercase; transition: 0.2s; 
    }
    .btn-logout-modern:hover { color: #d9534f; text-decoration: none; }
    .page-footer { display: none !important; }


    /* --- STYLE FORMULARZA (WHITE BOX) --- */
    .white-box { background: #fff; border: 1px solid var(--border-color); margin-bottom: 25px; border-radius: 8px; }
    .box-pad { padding: 30px; }
    .header-box { display: flex; align-items: center; gap: 15px; padding: 20px 25px; }
    .header-box h1 { font-size: 16px; font-weight: 800; text-transform: uppercase; margin: 0; color: var(--text-dark); letter-spacing: 0.5px; }

    /* Style Inputów (dla spójności z identity) */
    .form-control {
        background: #fff; border: 1px solid #ddd; border-radius: 4px;
        padding: 8px 15px; height: 42px; font-size: 13px; color: #333; box-shadow: none !important;
    }
    .form-control:focus { border-color: var(--brand-color); outline: none; }
    
    /* Etykiety */
    .form-group label { font-size: 11px; font-weight: 700; text-transform: uppercase; color: #555; }
    
    /* Przycisk Powrót */
    .btn-back-custom {
        color: #777; font-size: 12px; font-weight: 600; text-transform: uppercase; text-decoration: none;
        display: inline-flex; align-items: center; gap: 8px; transition: 0.3s;
    }
    .btn-back-custom:hover { color: var(--brand-color); text-decoration: none; }
    
    /* Przycisk Zapisz (Domyślny presty, ale poprawiony) */
    .btn-primary { 
        background-color: var(--brand-color) !important; border-color: var(--brand-color) !important; 
        font-weight: 700; text-transform: uppercase; font-size: 12px; padding: 10px 25px;
    }
    .btn-primary:hover { opacity: 0.9; }
</style>
{/literal}

<div class="account-sidebar-layout">
    
    {* --- MENU BOCZNE (FIXED) --- *}
    <div class="account-menu-column">
        <div class="menu-header">MOJE KONTO</div>
        
        <a class="sidebar-link" href="{$urls.pages.identity}">
            <i class="fa-regular fa-user"></i>
            <span>Dane konta</span>
        </a>
        
        {* Podświetlamy Adresy, bo jesteśmy w edycji adresu *}
        <a class="sidebar-link active-page" href="{$urls.pages.addresses}">
            <i class="fa-regular fa-map"></i>
            <span>Adresy</span>
        </a>

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

        {* MODUŁY *}
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

    {* --- TREŚĆ FORMULARZA --- *}
    <div class="account-form-content">
        
        {* Nagłówek w ramce *}
        <div class="white-box header-box">
            <h1>
                {if $editing}
                    Edytuj adres
                {else}
                    Nowy adres
                {/if}
            </h1>
        </div>

        {* Formularz w ramce *}
        <div class="white-box box-pad">
            {render template="customer/_partials/address-form.tpl" ui=$address_form}
        </div>
        
        <a href="{$urls.pages.addresses}" class="btn-back-custom">
            <i class="fa-solid fa-chevron-left"></i> Powrót do listy adresów
        </a>
    </div>
</div>

{/block}
{block name='page_footer'}{/block}