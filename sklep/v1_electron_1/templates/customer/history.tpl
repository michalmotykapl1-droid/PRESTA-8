{**
 * 2007-2025 PrestaShop
 * history.tpl - MERGED: Menu 1:1 + Modern Table
 *}
{extends file='customer/page.tpl'}

{block name='page_title'}
  {l s='Order history' d='Shop.Theme.Customeraccount'}
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
        --table-border: #f0f0f0;
    }

    /* --- UKŁAD STRONY --- */
    .account-sidebar-layout {
        display: grid; grid-template-columns: 300px 1fr; gap: 40px;
        margin-bottom: 60px; align-items: start;
    }
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


    /* --- STYLE TABELI (MODERN) --- */
    .section-header {
        display: flex; justify-content: space-between; align-items: center;
        margin-bottom: 25px; border-bottom: 1px solid #eee; padding-bottom: 15px;
    }
    .section-title { font-size: 18px; font-weight: 700; text-transform: uppercase; color: var(--text-dark); margin: 0; }

    .orders-container {
        background: #fff; border-radius: 12px; box-shadow: 0 5px 20px rgba(0,0,0,0.03);
        border: 1px solid var(--table-border); overflow: hidden;
    }
    .table { margin-bottom: 0; width: 100%; border-collapse: collapse; }
    
    .table thead th {
        background-color: #fcfcfc; border-bottom: 1px solid var(--table-border);
        text-transform: uppercase; font-size: 11px; color: #888; font-weight: 700;
        padding: 18px 20px; letter-spacing: 0.5px; border-top: none;
    }
    
    .table tbody tr { transition: background-color 0.2s; }
    .table tbody tr:hover { background-color: #fdfdfd; }
    .table tbody tr:last-child td { border-bottom: none; }

    .table tbody td {
        padding: 20px; vertical-align: middle; border-top: 1px solid #f9f9f9;
        font-size: 13px; color: var(--text-light);
    }
    
    .order-ref { font-weight: 700; color: var(--text-dark); }
    .order-price { font-weight: 700; color: var(--brand-color); }

    /* STATUS (BADGE) */
    .label-pill {
        padding: 6px 12px; border-radius: 50px; font-size: 10px; font-weight: 700;
        text-transform: uppercase; display: inline-block; letter-spacing: 0.5px;
    }

    /* ACTIONS */
    .btn-details {
        color: var(--text-light); border: 1px solid #eee; padding: 8px 15px;
        border-radius: 6px; font-size: 11px; font-weight: 700; text-transform: uppercase;
        text-decoration: none !important; transition: all 0.2s; display: inline-flex; align-items: center; gap: 5px;
    }
    .btn-details:hover { background: var(--brand-color); color: #fff; border-color: var(--brand-color); }
    .btn-reorder { color: var(--brand-color); font-weight: 600; font-size: 12px; margin-left: 10px; text-decoration: none !important; }
    .btn-reorder:hover { text-decoration: underline !important; }

    /* MOBILE */
    .orders-mobile .order {
        background: #fff; border: 1px solid #eee; padding: 20px;
        margin-bottom: 15px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.02);
    }
    .mobile-header { display: flex; justify-content: space-between; margin-bottom: 10px; padding-bottom: 10px; border-bottom: 1px solid #f5f5f5; }
    .mobile-ref { font-weight: 800; }
    .mobile-date { font-size: 12px; color: #999; }
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
        
        <a class="sidebar-link" href="{$urls.pages.addresses}">
            <i class="fa-regular fa-map"></i>
            <span>Adresy</span>
        </a>

        <a class="sidebar-link active-page" href="{$urls.pages.history}">
            <i class="fa-regular fa-folder-open"></i>
            <span>Historia zamówień</span>
        </a>

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

    {* --- TREŚĆ: TABELA ZAMÓWIEŃ --- *}
    <div class="account-form-content">
        
        <div class="section-header">
            <h1 class="section-title">Historia Zamówień</h1>
        </div>

        {if $orders}
            <div class="orders-container hidden-sm-down">
                <table class="table">
                  <thead>
                    <tr>
                      <th>{l s='Order reference' d='Shop.Theme.Checkout'}</th>
                      <th>{l s='Date' d='Shop.Theme.Checkout'}</th>
                      <th>{l s='Total price' d='Shop.Theme.Checkout'}</th>
                      <th class="hidden-md-down">{l s='Payment' d='Shop.Theme.Checkout'}</th>
                      <th class="hidden-md-down">{l s='Status' d='Shop.Theme.Checkout'}</th>
                      <th class="text-center">{l s='Invoice' d='Shop.Theme.Checkout'}</th>
                      <th class="text-right">Akcje</th>
                    </tr>
                  </thead>
                  <tbody>
                    {foreach from=$orders item=order}
                      <tr>
                        <td>
                            <a href="{$order.details.details_url}" class="order-ref">{$order.details.reference}</a>
                        </td>
                        <td>{$order.details.order_date}</td>
                        <td class="order-price">{$order.totals.total.value}</td>
                        <td class="hidden-md-down">{$order.details.payment}</td>
                        <td>
                          <span class="label-pill {$order.history.current.contrast}" style="background-color:{$order.history.current.color}">
                            {$order.history.current.ostate_name}
                          </span>
                        </td>
                        <td class="text-center hidden-md-down">
                          {if $order.details.invoice_url}
                            <a href="{$order.details.invoice_url}" title="Pobierz fakturę"><i class="fa-solid fa-file-pdf" style="font-size: 18px; color: #666;"></i></a>
                          {else}
                            <span style="color: #ccc;">-</span>
                          {/if}
                        </td>
                        <td class="text-right">
                          <a href="{$order.details.details_url}" class="btn-details" data-link-action="view-order-details">
                            Szczegóły
                          </a>
                          {if $order.details.reorder_url}
                            <a href="{$order.details.reorder_url}" class="btn-reorder" title="Zamów ponownie">
                                <i class="fa-solid fa-rotate-right"></i>
                            </a>
                          {/if}
                        </td>
                      </tr>
                    {/foreach}
                  </tbody>
                </table>
            </div>

            {* WERSJA MOBILNA *}
            <div class="orders-mobile hidden-md-up">
              {foreach from=$orders item=order}
                <div class="order">
                  <div class="mobile-header">
                    <div class="mobile-ref">{$order.details.reference}</div>
                    <div class="mobile-date">{$order.details.order_date}</div>
                  </div>
                  <div class="row" style="margin-bottom: 10px;">
                    <div class="col-xs-6">
                        <strong>{$order.totals.total.value}</strong>
                    </div>
                    <div class="col-xs-6 text-xs-right">
                        <span class="label-pill" style="background-color:{$order.history.current.color}; color: #fff;">
                          {$order.history.current.ostate_name}
                        </span>
                    </div>
                  </div>
                  <div class="text-xs-right">
                      <a href="{$order.details.details_url}" class="btn-details" style="width: 100%; display: block; text-align: center;">
                        SZCZEGÓŁY ZAMÓWIENIA
                      </a>
                  </div>
                </div>
              {/foreach}
            </div>

        {else}
            <div class="alert alert-info">
                Nie złożyłeś jeszcze żadnych zamówień.
            </div>
        {/if}
    </div>

</div>
{/block}