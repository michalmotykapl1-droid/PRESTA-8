{**
 * 2007-2025 PrestaShop
 * order-slip.tpl - MERGED: Menu 1:1 + Clean Table
 *}
{extends file='customer/page.tpl'}

{block name='page_title'}
  {l s='Credit slips' d='Shop.Theme.Customeraccount'}
{/block}

{block name='page_content'}

{literal}
<style>
    :root {
        --brand-color: #d01662;
        --c-text: #232323;
        --c-text-light: #777;
        --c-brand: #d01662;
        --c-brand-light: #fff0f5;
        --c-border: #eaeaea;
        --bg-light: #f8f9fa;
        --font-base: 'Open Sans', Helvetica, Arial, sans-serif;
    }

    #content .alert-warning { display: none !important; }
    .page-wrapper { color: var(--c-text); font-family: var(--font-base); }

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


    /* --- STYLE TREŚCI --- */
    .white-box { background: #fff; border: 1px solid var(--c-border); margin-bottom: 25px; border-radius: 8px; overflow: hidden; }
    .box-pad { padding: 30px; }
    .header-box { display: flex; align-items: center; gap: 15px; padding: 20px 25px; }
    .header-box h1 { font-size: 16px; font-weight: 800; text-transform: uppercase; margin: 0; color: var(--c-text); letter-spacing: 0.5px; }

    /* TABELA */
    .clean-table { width: 100%; border-collapse: collapse; }
    .clean-table th { 
        text-align: left; padding: 18px 25px; 
        font-size: 11px; text-transform: uppercase; color: #999; font-weight: 700; letter-spacing: 1px; 
        border-bottom: 1px solid var(--c-border); background: #fcfcfc;
    }
    .clean-table td { padding: 20px 25px; vertical-align: middle; border-bottom: 1px solid var(--c-border); font-size: 13px; color: var(--c-text); }
    .clean-table tr:last-child td { border-bottom: none; }
    .text-right { text-align: right; } 
    .text-center { text-align: center; }

    /* PRZYCISK PDF */
    .btn-pdf { 
        display: inline-flex; align-items: center; gap: 8px; 
        color: var(--c-brand); border: 1px solid #fce4ec; 
        padding: 8px 15px; border-radius: 4px; font-size: 12px; font-weight: 700; 
        text-decoration: none !important; text-transform: uppercase; transition: 0.3s;
    }
    .btn-pdf:hover { background: var(--c-brand); color: #fff; border-color: var(--c-brand); }

    .order-link { color: #555; font-weight: 600; text-decoration: underline; }
    .order-link:hover { color: var(--c-brand); }

    /* MOBILE */
    .mobile-card { padding: 20px; border-bottom: 1px solid #eee; }
    .mobile-card:last-child { border-bottom: none; }
    .m-row { display: flex; justify-content: space-between; margin-bottom: 10px; font-size: 13px; }
    .m-label { font-size: 10px; text-transform: uppercase; color: #999; font-weight: 700; }

    /* EMPTY STATE */
    .empty-state { text-align: center; padding: 60px 20px; }
    .empty-state i { font-size: 50px; color: #eee; margin-bottom: 20px; display: block; }
    .empty-state p { font-size: 15px; color: #999; margin: 0; }
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
            <a class="sidebar-link active-page" href="{$urls.pages.order_slip}">
                <i class="fa-regular fa-file-lines"></i><span>Moje korekty</span>
            </a>
        {/if}
        
        {if $configuration.voucher_enabled && !$configuration.is_catalog}<a class="sidebar-link" href="{$urls.pages.discount}"><i class="fa-regular fa-credit-card"></i><span>Kupony rabatowe</span></a>{/if}
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
            <h1>MOJE KOREKTY</h1>
        </div>

        <div class="white-box">
            <div class="box-pad" style="padding-bottom:15px; border-bottom:1px solid #f9f9f9;">
                <h6 style="margin:0; font-size:13px; color:#666;">
                    {l s='Credit slips you have received after canceled orders.' d='Shop.Theme.Customeraccount'}
                </h6>
            </div>

            {if $credit_slips}
                
                {* TABELA DESKTOP *}
                <div class="hidden-sm-down">
                    <table class="clean-table">
                        <thead>
                            <tr>
                                <th>{l s='Date issued' d='Shop.Theme.Customeraccount'}</th>
                                <th>{l s='Credit slip' d='Shop.Theme.Customeraccount'}</th>
                                <th>{l s='Order' d='Shop.Theme.Customeraccount'}</th>
                                <th class="text-right">{l s='View credit slip' d='Shop.Theme.Customeraccount'}</th>
                            </tr>
                        </thead>
                        <tbody>
                            {foreach from=$credit_slips item=slip}
                                <tr>
                                    <td>{$slip.credit_slip_date}</td>
                                    <td><strong>{$slip.credit_slip_number}</strong></td>
                                    <td>
                                        <a href="{$slip.order_url_details}" class="order-link">
                                            {$slip.order_reference}
                                        </a>
                                    </td>
                                    <td class="text-right">
                                        <a href="{$slip.url}" class="btn-pdf" target="_blank">
                                            <i class="fa-solid fa-file-pdf"></i> Pobierz PDF
                                        </a>
                                    </td>
                                </tr>
                            {/foreach}
                        </tbody>
                    </table>
                </div>

                {* KAFELKI MOBILE *}
                <div class="hidden-md-up">
                    {foreach from=$credit_slips item=slip}
                        <div class="mobile-card">
                            <div class="m-row">
                                <div>
                                    <div class="m-label">{l s='Credit slip' d='Shop.Theme.Customeraccount'}</div>
                                    <strong>{$slip.credit_slip_number}</strong>
                                </div>
                                <div style="text-align:right;">
                                    <div class="m-label">{l s='Date issued' d='Shop.Theme.Customeraccount'}</div>
                                    {$slip.credit_slip_date}
                                </div>
                            </div>
                            <div class="m-row" style="align-items:center; margin-top:15px; margin-bottom:0;">
                                <div>
                                    <div class="m-label">{l s='Order' d='Shop.Theme.Customeraccount'}</div>
                                    <a href="{$slip.order_url_details}" class="order-link">{$slip.order_reference}</a>
                                </div>
                                <a href="{$slip.url}" class="btn-pdf" target="_blank">
                                    <i class="fa-solid fa-file-pdf"></i> PDF
                                </a>
                            </div>
                        </div>
                    {/foreach}
                </div>

            {else}
                
                {* PUSTY STAN *}
                <div class="empty-state">
                    <i class="fa-solid fa-file-invoice-dollar"></i>
                    <p>Nie masz żadnych korekt płatności.</p>
                </div>

            {/if}
        </div>

    </div>

</div>
{/block}