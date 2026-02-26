{**
 * 2007-2025 PrestaShop
 * identity.tpl - MERGED: Pro Menu + Compact Form
 *}
{extends file='customer/page.tpl'}

{block name='page_title'}
  {l s='Your personal information' d='Shop.Theme.Customeraccount'}
{/block}

{block name='page_content'}

{literal}
<style>
    /* --- ZMIENNE I STYLE OGÓLNE (Z DASHBOARDA) --- */
    :root {
        --brand-color: #d01662;
        --text-color: #333333;
        --bg-light: #f8f9fa;
        --border-color: #eee;
    }

    /* UKŁAD STRONY */
    .account-sidebar-layout {
        display: grid; grid-template-columns: 300px 1fr; gap: 35px;
        margin-bottom: 60px; align-items: start;
    }
    @media (max-width: 991px) { .account-sidebar-layout { grid-template-columns: 1fr; gap: 30px; } }

    /* --- LEWA STRONA: MENU (1:1 Z DASHBOARDEM) --- */
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
        transition: all 0.2s ease; color: var(--text-color); font-weight: 600;
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
        border-radius: 6px !important; text-decoration: none !important; color: var(--text-color) !important; 
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
        font-size: 13px !important; font-weight: 600 !important; color: var(--text-color) !important;
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

    /* --- STYLE SPECYFICZNE DLA FORMULARZA IDENTITY --- */
    .white-box { background: #fff; border: 1px solid var(--border-color); margin-bottom: 25px; border-radius: 8px; }
    .box-pad { padding: 30px; }
    .header-box { display: flex; align-items: center; gap: 15px; padding: 20px 25px; }
    .header-box h1 { font-size: 16px; font-weight: 800; text-transform: uppercase; margin: 0; color: var(--text-color); letter-spacing: 0.5px; }

    /* Ukrywanie pól formularza */
    .form-group.row:has(input[name="id_gender"]), .radio-inline, .gender-field { display: none !important; }
    form#customer-form > section > .form-group:first-child { display: none !important; }
    .form-group.row:has(input[name="birthday"]), .form-group.row:has(select[name="birthday"]) { display: none !important; }
    .form-group.row:has(input[name="optin"]) { display: none !important; }
    #content .alert-warning { display: none !important; }

    /* Wiersz formularza */
    .form-group.row { display: flex; flex-wrap: wrap; margin-bottom: 12px; }
    
    /* Etykiety */
    .form-group.row .col-md-3 { 
        display: block !important; flex: 0 0 100%; max-width: 100%; text-align: left; 
        padding-bottom: 4px; font-size: 11px; font-weight: 700; text-transform: uppercase; color: #555;
    }
    .form-group.row .col-md-3 sup { color: var(--brand-color); }
    .form-group.row .col-md-6, .form-group.row .col-md-9 { flex: 0 0 100%; max-width: 100%; padding: 0; }

    /* Checkboxy */
    .form-group.row:has(input[type="checkbox"]) .col-md-3 { display: none !important; }
    .form-group.row:has(input[type="checkbox"]) { margin-top: 15px; }

    /* Inputy */
    .form-control {
        background: #fff; border: 1px solid #ddd; border-radius: 4px;
        padding: 8px 15px; height: 42px; font-size: 13px; color: #333; box-shadow: none !important;
        width: 100%; margin: 0;
    }
    .form-control:focus { border-color: var(--brand-color); outline: none; }
    
    /* Pomocnicze */
    .form-control-comment, .help-block { font-size: 10px !important; color: #999 !important; margin-top: 2px !important; display: block; }

    /* FIX OCZKA HASŁA */
    .password-input-wrapper { position: relative !important; width: 100%; display: block; }
    .password-input-wrapper input.form-control { padding-right: 45px !important; }
    .custom-eye-icon {
        position: absolute; right: 0; top: 50%; transform: translateY(-50%); 
        width: 45px; height: 40px; z-index: 10; display: flex; align-items: center; justify-content: center;
        cursor: pointer; color: #ccc; transition: 0.2s;
    }
    .custom-eye-icon:hover { color: var(--brand-color); }
    .input-group-btn, #customer-form .form-footer { display: none !important; }

    /* Przycisk zapisu */
    .custom-form-actions {
        display: flex; justify-content: space-between; align-items: center;
        margin-top: 25px; padding-top: 20px; border-top: 1px solid #eee;
    }
    .btn-save-custom {
        background: #fff; border: 1px solid var(--brand-color); color: var(--brand-color);
        border-radius: 4px; padding: 0 40px; height: 45px; min-width: 160px;
        font-weight: 700; text-transform: uppercase; letter-spacing: 1px; font-size: 12px;
        transition: all 0.3s ease; cursor: pointer; display: flex; align-items: center; justify-content: center;
    }
    .btn-save-custom:hover { background-color: var(--brand-color); color: #fff; box-shadow: 0 4px 12px rgba(208, 22, 98, 0.2); }
    .btn-back-custom { color: #777; font-size: 12px; font-weight: 600; text-transform: uppercase; display: flex; align-items: center; gap: 8px; transition: 0.3s; text-decoration: none; }
    .btn-back-custom:hover { color: var(--brand-color); text-decoration: none; }
    .page-footer { display: none !important; }
</style>
{/literal}

<div class="account-sidebar-layout">

    {* --- MENU BOCZNE (IDENTYCZNE JAK NA DASHBOARD) --- *}
    <div class="account-menu-column">
        <div class="menu-header">MOJE KONTO</div>
        
        <a class="sidebar-link active-page" href="{$urls.pages.identity}">
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

    {* --- PRAWA STRONA: FORMULARZ DANYCH --- *}
    <div class="account-form-content identity-page-wrapper">
        
        <div class="white-box header-box">
            <h1>MOJE DANE</h1>
        </div>

        <div class="white-box box-pad">
            
            {render file='customer/_partials/customer-form.tpl' ui=$customer_form}
            
            <div style="font-size: 11px; color: #666; margin-top: 15px; font-style: italic;">
                <span style="color: #d01662; font-weight: bold; font-size: 13px;">*</span> Pola wymagane
            </div>

            <div class="custom-form-actions">
                <a href="{$urls.pages.my_account}" class="btn-back-custom">
                    <i class="fa-solid fa-chevron-left"></i> Powrót do konta
                </a>
                
                {* Przycisk Save *}
                <div class="btn-save-custom" onclick="document.querySelector('#customer-form button[type=submit]').click();">
                    ZAPISZ ZMIANY
                </div>
            </div>
        </div>

    </div>

</div>

{* JS do Oczka *}
<script>
document.addEventListener("DOMContentLoaded", function() {
    const passInputs = document.querySelectorAll('input[type="password"]');
    passInputs.forEach(input => {
        if (input.parentElement.classList.contains('password-wrapper-fix')) return;
        const wrapper = document.createElement('div');
        wrapper.className = 'password-wrapper-fix';
        if (input.parentNode) {
            input.parentNode.insertBefore(wrapper, input);
            wrapper.appendChild(input);
            const eye = document.createElement('div');
            eye.className = 'custom-eye-icon';
            eye.innerHTML = '<i class="fa-solid fa-eye"></i>'; 
            eye.addEventListener('click', function() {
                if (input.type === "password") {
                    input.type = "text"; eye.innerHTML = '<i class="fa-solid fa-eye-slash"></i>';
                } else {
                    input.type = "password"; eye.innerHTML = '<i class="fa-solid fa-eye"></i>';
                }
            });
            wrapper.appendChild(eye);
        }
    });
});
</script>

{/block}

{block name='page_footer'}{/block}