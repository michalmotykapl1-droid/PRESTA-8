{**
 * 2007-2025 PrestaShop
 * discount.tpl - MERGED: Menu 1:1 + Premium Cards
 *}
{extends file='customer/page.tpl'}

{block name='page_title'}
  {l s='Your vouchers' d='Shop.Theme.Customeraccount'}
{/block}

{block name='page_content'}

{literal}
<style>
    /* --- ZMIENNE --- */
    :root { 
        --c-text: #232323; 
        --c-text-light: #666; 
        --c-brand: #d01662; 
        --brand-color: #d01662; /* Dubel dla spójności nazewnictwa */
        --c-brand-dark: #b01252;
        --c-brand-bg: #fff0f6;
        --c-border: #eaeaea; 
        --bg-light: #f8f9fa;
        --font-base: 'Open Sans', Helvetica, Arial, sans-serif;
    }

    #content .alert-warning { display: none !important; }
    .page-wrapper { color: var(--c-text); font-family: var(--font-base); }

    /* UKŁAD STRONY */
    .account-sidebar-layout { display: grid; grid-template-columns: 300px 1fr; gap: 35px; margin-bottom: 60px; align-items: start; }
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
        transition: all 0.2s ease; color: var(--c-text); font-weight: 600;
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
        border-radius: 6px !important; text-decoration: none !important; color: var(--c-text) !important; 
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
        font-size: 13px !important; font-weight: 600 !important; color: var(--c-text) !important;
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


    /* --- STYLE TREŚCI (PREMIUM CARDS) --- */
    .white-box { background: #fff; border: 1px solid var(--c-border); margin-bottom: 25px; border-radius: 8px; }
    .header-box { display: flex; align-items: center; gap: 15px; padding: 20px 25px; }
    .header-box h1 { font-size: 18px; font-weight: 800; text-transform: uppercase; margin: 0; color: var(--c-text); letter-spacing: 0.5px; }

    /* GRID */
    .coupons-grid {
        display: grid; grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
        gap: 30px;
    }

    /* KARTA */
    .coupon-card {
        background: #fff; border-radius: 16px; overflow: hidden;
        border: 1px solid #eee; box-shadow: 0 5px 15px rgba(0,0,0,0.03);
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        display: flex; flex-direction: column;
    }
    .coupon-card:hover { transform: translateY(-5px); box-shadow: 0 15px 35px rgba(0,0,0,0.08); border-color: #fce4ec; }

    /* GÓRA KARTY */
    .c-header {
        background: linear-gradient(135deg, #fff5f9 0%, #fff0f6 100%);
        padding: 30px 25px; position: relative; border-bottom: 2px dashed #fce4ec;
    }
    .c-bg-icon {
        position: absolute; right: -15px; bottom: -10px; font-size: 120px;
        color: var(--c-brand); opacity: 0.05; transform: rotate(-15deg); pointer-events: none;
    }
    .c-value {
        font-size: 40px; font-weight: 900; color: var(--c-brand); line-height: 1;
        margin-bottom: 10px; display: block; letter-spacing: -1px; position: relative; z-index: 2;
    }
    .c-min-tag {
        display: inline-block; background: #fff; padding: 6px 12px; border-radius: 20px;
        font-size: 11px; font-weight: 700; color: var(--c-text-light); text-transform: uppercase;
        box-shadow: 0 2px 5px rgba(0,0,0,0.03); position: relative; z-index: 2;
    }
    .c-min-tag i { color: var(--c-brand); margin-right: 5px; }

    /* DÓŁ KARTY */
    .c-body {
        padding: 25px; background: #fff; flex-grow: 1;
        display: flex; flex-direction: column; justify-content: space-between;
    }
    .c-name { font-size: 14px; font-weight: 700; color: #222; margin-bottom: 5px; line-height: 1.4; }
    .c-date { font-size: 12px; color: #999; margin-bottom: 20px; display: block; }
    .c-date strong { color: #555; }

    /* BOX KODU */
    .c-code-container {
        background: #fbfbfb; border: 2px dashed #e0e0e0; border-radius: 8px;
        padding: 15px; display: flex; justify-content: space-between; align-items: center;
        cursor: pointer; transition: 0.2s; position: relative;
    }
    .c-code-container:hover { background: #fff; border-color: var(--c-brand); }
    .c-code { font-family: 'Courier New', monospace; font-size: 18px; font-weight: 800; color: #333; letter-spacing: 1px; }
    .c-copy-icon { font-size: 18px; color: #ccc; transition: 0.2s; }
    .c-code-container:hover .c-copy-icon { color: var(--c-brand); }

    /* TOAST */
    .copy-toast {
        position: absolute; bottom: 15px; left: 50%; transform: translateX(-50%) translateY(20px);
        background: #333; color: #fff; padding: 6px 14px; border-radius: 20px;
        font-size: 11px; font-weight: 600; opacity: 0; pointer-events: none; 
        transition: 0.3s; z-index: 10; white-space: nowrap;
    }
    .copy-toast.show { opacity: 1; transform: translateX(-50%) translateY(0); bottom: 70px; }

    /* EMPTY */
    .empty-state { text-align: center; padding: 80px 20px; }
    .empty-state i { font-size: 50px; color: #eee; margin-bottom: 20px; display: block; }
    .empty-state p { font-size: 16px; color: #999; margin: 0; }
</style>
{/literal}

<div class="account-sidebar-layout">

    {* MENU BOCZNE (FIXED) *}
    <div class="account-menu-column">
        <div class="menu-header">MOJE KONTO</div> 
        <a class="sidebar-link" href="{$urls.pages.identity}"><i class="fa-regular fa-user"></i><span>Dane konta</span></a>
        <a class="sidebar-link" href="{$urls.pages.addresses}"><i class="fa-regular fa-map"></i><span>Adresy</span></a>
        <a class="sidebar-link" href="{$urls.pages.history}"><i class="fa-regular fa-folder-open"></i><span>Historia zamówień</span></a>
        
        {if !$configuration.is_catalog}
            <a class="sidebar-link" href="{$urls.pages.order_slip}">
                <i class="fa-regular fa-file-lines"></i><span>Moje korekty</span>
            </a>
        {/if}
        
        {if $configuration.voucher_enabled && !$configuration.is_catalog}
            <a class="sidebar-link active-page" href="{$urls.pages.discount}">
                <i class="fa-regular fa-credit-card"></i><span>Kupony rabatowe</span>
            </a>
        {/if}
        
        {if $configuration.return_enabled && !$configuration.is_catalog}<a class="sidebar-link" href="{$urls.pages.order_follow}"><i class="fa-solid fa-rotate-left"></i><span>Zwroty towarów</span></a>{/if}
        
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

    {* TREŚĆ *}
    <div class="account-form-content page-wrapper">
        
        <div class="white-box header-box">
            <h1>MOJE KUPONY</h1>
        </div>

        {if $cart_rules}
            
            <div class="coupons-grid">
                {foreach from=$cart_rules item=cart_rule}
                    <div class="coupon-card">
                        
                        {* GÓRA KUPONU *}
                        <div class="c-header">
                            <i class="fa-solid fa-ticket c-bg-icon"></i>
                            <span class="c-value">
                                {$cart_rule.value|replace:' Brutto':''}
                            </span>
                            
                            {if isset($cart_rule.minimum_amount) && $cart_rule.minimum_amount > 0}
                                <div class="c-min-tag">
                                    <i class="fa-solid fa-basket-shopping"></i>
                                    Minimalne zamówienie: {$cart_rule.minimum_amount|string_format:"%.2f"|replace:'.':','} zł
                                </div>
                            {/if}
                        </div>

                        {* DÓŁ KUPONU *}
                        <div class="c-body">
                            <div>
                                <div class="c-name">{$cart_rule.name}</div>
                                <span class="c-date">
                                    Ważny do: <strong>{$cart_rule.voucher_date}</strong>
                                </span>
                            </div>
                            
                            <div class="c-code-container" onclick="copyCoupon(this, '{$cart_rule.code}')" title="Kliknij, aby skopiować">
                                <span class="c-code">{$cart_rule.code}</span>
                                <i class="fa-regular fa-copy c-copy-icon"></i>
                                <div class="copy-toast">Skopiowano!</div>
                            </div>
                        </div>

                    </div>
                {/foreach}
            </div>

        {else}
            
            <div class="white-box empty-state">
                <i class="fa-solid fa-ticket-simple"></i>
                <p>{l s='You have no vouchers.' d='Shop.Theme.Customeraccount'}</p>
            </div>

        {/if}

    </div>

</div>

{* SKRYPT KOPIOWANIA *}
<script>
    function copyCoupon(element, code) {
        navigator.clipboard.writeText(code).then(() => {
            const toast = element.querySelector('.copy-toast');
            const icon = element.querySelector('.c-copy-icon');
            
            toast.classList.add('show');
            
            icon.classList.remove('fa-copy', 'fa-regular');
            icon.classList.add('fa-check', 'fa-solid');
            icon.style.color = '#28a745';

            setTimeout(() => {
                toast.classList.remove('show');
                icon.classList.add('fa-copy', 'fa-regular');
                icon.classList.remove('fa-check', 'fa-solid');
                icon.style.color = ''; 
            }, 2000);
        });
    }
</script>
{/block}