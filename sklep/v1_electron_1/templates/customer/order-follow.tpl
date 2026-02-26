{**
 * 2007-2025 PrestaShop
 * order-follow.tpl - MERGED: Menu 1:1 + Modern Returns
 *}
{extends file='customer/page.tpl'}

{block name='page_title'}
  {l s='Merchandise returns' d='Shop.Theme.Customeraccount'}
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


    /* --- STYLE ZWROTÓW --- */
    .box-card {
        background: #fff; border-radius: 8px; padding: 0;
        margin-bottom: 25px; border: 1px solid var(--border-color);
        box-shadow: 0 4px 15px rgba(0,0,0,0.02); overflow: hidden;
    }
    
    .clean-table { width: 100%; border-collapse: collapse; }
    .clean-table th {
        text-align: left; padding: 18px 25px;
        font-size: 11px; text-transform: uppercase; color: #999; font-weight: 700; letter-spacing: 1px;
        border-bottom: 1px solid var(--border-color); background: #fcfcfc;
    }
    .clean-table td { padding: 20px 25px; vertical-align: middle; border-bottom: 1px solid var(--border-color); font-size: 14px; color: var(--text-dark); }
    .clean-table tr:last-child td { border-bottom: none; }
    .text-right { text-align: right; }

    .status-pill { display: inline-block; padding: 5px 12px; border-radius: 20px; font-size: 10px; font-weight: 700; text-transform: uppercase; background: #fff; color: #333; border: 1px solid #e0e0e0; }

    .return-ref-link { font-weight: 700; color: var(--brand-color); text-decoration: none; }
    .return-ref-link:hover { text-decoration: underline; }

    .btn-details-small {
        display: inline-flex; align-items: center; gap: 5px; color: #555; 
        font-size: 11px; font-weight: 700; text-decoration: none; text-transform: uppercase;
        border: 1px solid #eee; padding: 8px 15px; border-radius: 4px; transition: 0.2s;
    }
    .btn-details-small:hover { border-color: var(--brand-color); color: var(--brand-color); background: #fff; }

    .empty-state { text-align: center; padding: 40px; background: #fff; border-radius: 8px; border: 1px solid #eee; }
    .empty-state i { font-size: 40px; color: #eee; margin-bottom: 15px; display: block; }
    .empty-state p { color: #777; font-size: 14px; margin: 0; }
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
            <a class="sidebar-link" href="{$urls.pages.discount}">
                <i class="fa-regular fa-credit-card"></i><span>Kupony rabatowe</span>
            </a>
        {/if}
        
        {if $configuration.return_enabled && !$configuration.is_catalog}
            <a class="sidebar-link active-page" href="{$urls.pages.order_follow}">
                <i class="fa-solid fa-rotate-left"></i><span>Zwroty towarów</span>
            </a>
        {/if}
        
        {* MODUŁY *}
        <div class="account-modules-sidebar">
            {block name='display_customer_account'}
                {capture name="modules_content"}{hook h='displayCustomerAccount'}{/capture}
                {$smarty.capture.modules_content|replace:'MOJE PUNKTY LOJALNOŚCIOWE':'Program Lojalnościowy'|replace:'Moje punkty lojalnościowe':'Program Lojalnościowy'|replace:'MOJE KOMENTARZE NA BLOGU':'Komentarze na blogu'|replace:'Moje komentarze na blogu':'Komentarze na blogu'|replace:'MOJE ULUBIONE':'Moje ulubione'|replace:'MOJE ULUBIONE':'Moje ulubione'|replace:'Moje ulubione':'Moje ulubione'|replace:'MOJE POWIADOMIENIA':''|replace:'MOJE ALERTY':'' nofilter}
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
    <div class="account-form-content">
        
        <h1 class="h3" style="margin-bottom: 25px; font-weight: 800; text-transform: uppercase; font-size: 18px; color: #222;">
            {l s='Merchandise returns' d='Shop.Theme.Customeraccount'}
        </h1>

        {if $ordersReturn && count($ordersReturn)}
            
            <div class="box-card hidden-sm-down">
                <table class="clean-table">
                  <thead>
                    <tr>
                      <th>{l s='Return' d='Shop.Theme.Customeraccount'}</th>
                      <th>{l s='Order' d='Shop.Theme.Customeraccount'}</th>
                      <th>{l s='Package status' d='Shop.Theme.Customeraccount'}</th>
                      <th>Data zgłoszenia</th>
                      <th class="text-right">Akcja</th>
                    </tr>
                  </thead>
                  <tbody>
                    {foreach from=$ordersReturn item=return}
                      <tr>
                        <td>
                            <a href="{$return.return_url}" class="return-ref-link">{$return.return_number}</a>
                        </td>
                        <td>
                            <a href="{$return.details_url}" style="color:#555; text-decoration:underline;">{$return.reference}</a>
                        </td>
                        <td>
                            <span class="status-pill">{$return.state_name}</span>
                        </td>
                        <td>{$return.return_date}</td>
                        <td class="text-right">
                           <a href="{$return.return_url}" class="btn-details-small">
                                ZOBACZ SZCZEGÓŁY
                           </a>
                        </td>
                      </tr>
                    {/foreach}
                  </tbody>
                </table>
            </div>

            {* WERSJA MOBILNA *}
            <div class="hidden-md-up">
              {foreach from=$ordersReturn item=return}
                <div class="box-card" style="padding: 20px; margin-bottom: 15px;">
                  <div style="display:flex; justify-content:space-between; margin-bottom:10px;">
                      <div>
                          <div style="font-size:11px; color:#999; text-transform:uppercase;">Zwrot</div>
                          <a href="{$return.return_url}" class="return-ref-link">{$return.return_number}</a>
                      </div>
                      <div style="text-align:right;">
                          <div style="font-size:11px; color:#999; text-transform:uppercase;">Data</div>
                          <div>{$return.return_date}</div>
                      </div>
                  </div>
                  
                  <div style="margin-bottom:15px;">
                      <div style="font-size:11px; color:#999; text-transform:uppercase;">Status</div>
                      <span class="status-pill">{$return.state_name}</span>
                  </div>
                  
                  <div style="display:flex; justify-content:space-between; align-items:center; border-top:1px solid #f5f5f5; padding-top:15px; margin-top:10px;">
                      <a href="{$return.details_url}" style="font-size:13px; color:#555;">Zamówienie: <strong>{$return.reference}</strong></a>
                      
                      <a href="{$return.return_url}" class="btn-details-small">
                        ZOBACZ SZCZEGÓŁY
                      </a>
                  </div>
                </div>
              {/foreach}
            </div>

        {else}
            <div class="empty-state">
                <i class="fa-solid fa-box-open"></i>
                <p>{l s='You have no merchandise return authorizations.' d='Shop.Theme.Customeraccount'}</p>
            </div>
        {/if}

    </div>
</div>
{/block}