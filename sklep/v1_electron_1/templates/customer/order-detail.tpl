{**
 * 2007-2025 PrestaShop
 * order-detail.tpl - MERGED: Menu 1:1 + Auto Address + Progress Bar
 *}
{extends file='customer/page.tpl'}

{block name='page_title'}
  {l s='Order details' d='Shop.Theme.Customeraccount'}
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


    /* --- STYLE SZCZEGÓŁÓW ZAMÓWIENIA --- */
    
    /* KARTY (BOXES) */
    .box-card {
        background: #fff; border-radius: 8px; padding: 25px;
        margin-bottom: 25px; border: 1px solid var(--border-color);
        box-shadow: 0 4px 15px rgba(0,0,0,0.02); overflow: hidden;
    }

    /* TYTUŁ SEKCJI */
    .section-title-unified {
        font-size: 13px; font-weight: 800; text-transform: uppercase;
        color: var(--text-dark); letter-spacing: 0.5px;
        margin-bottom: 20px; padding-bottom: 15px; 
        border-bottom: 1px solid #f9f9f9; line-height: 1.2;
    }

    /* NAGŁÓWEK ZAMÓWIENIA */
    .order-header-card {
        background: #fff; border-radius: 8px; padding: 20px;
        margin-bottom: 25px; border: 1px solid var(--border-color);
        display: flex; justify-content: space-between; align-items: center;
        flex-wrap: wrap; gap: 15px;
    }
    .order-ref-title { font-size: 16px; font-weight: 800; color: var(--text-dark); margin: 0; text-transform: uppercase; }
    .order-date { font-size: 13px; color: #888; font-weight: 400; margin-left: 10px; }
    .btn-reorder { background-color: #fff; border: 1px solid var(--brand-color); color: var(--brand-color); border-radius: 4px; padding: 8px 20px; font-weight: 700; text-transform: uppercase; font-size: 12px; letter-spacing: 0.5px; transition: all 0.3s ease; text-decoration: none !important; }
    .btn-reorder:hover { background-color: var(--brand-color); color: #fff; }

    /* ADRESY */
    .addresses-wrapper { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px; }
    @media(max-width: 768px) { .addresses-wrapper { grid-template-columns: 1fr; } }
    .address-body { font-size: 13px; line-height: 1.6; color: var(--text-light); font-style: normal; }
    .address-alias-badge { font-size: 10px; background: #eee; color: #555; padding: 2px 6px; border-radius: 4px; margin-left: 10px; font-weight: 600; text-transform: uppercase; }

    /* --- STYL: SPLIT ZWROT I TERMIN --- */
    .return-split-grid {
        display: grid; grid-template-columns: 1fr 1fr; gap: 0;
        margin: -25px; /* Reset paddingu rodzica */
    }
    @media(max-width: 768px) { .return-split-grid { grid-template-columns: 1fr; } }

    .return-col { padding: 40px; }
    .return-col:first-child { border-right: 1px solid #f0f0f0; }
    @media(max-width: 768px) { .return-col:first-child { border-right: none; border-bottom: 1px solid #f0f0f0; } }

    .ret-addr-title { font-size: 11px; font-weight: 800; color: #999; text-transform: uppercase; margin-bottom: 15px; letter-spacing: 1px; }
    .ret-addr-data strong { display: block; margin-bottom: 8px; font-size: 15px; color: #222; text-transform: uppercase; font-weight: 800; }
    .ret-addr-data { font-size: 14px; color: #555; line-height: 1.7; }

    /* PASEK POSTĘPU */
    .timer-top-row { display: flex; justify-content: space-between; margin-bottom: 10px; font-size: 14px; font-weight: 600; color: #444; }
    .timer-days { font-weight: 800; color: var(--brand-color); font-size: 15px; }
    
    .progress-track {
        background: #f5f5f5; height: 10px; border-radius: 10px;
        overflow: hidden; border: 1px solid #eee; margin-bottom: 25px;
    }
    .progress-fill {
        background: var(--brand-color); height: 100%; border-radius: 10px;
        transition: width 1s ease-in-out;
    }

    .ret-instructions ul { list-style: none; padding: 0; margin: 0; font-size: 13px; color: #666; }
    .ret-instructions li { margin-bottom: 10px; display: flex; align-items: center; gap: 10px; }
    .ret-instructions i { color: #ccc; font-size: 12px; }

    /* TABELA */
    .clean-table { width: 100%; border-collapse: collapse; }
    .clean-table th { text-align: left; padding: 15px 10px; font-size: 11px; text-transform: uppercase; color: #999; font-weight: 700; letter-spacing: 1px; border-bottom: 1px solid var(--border-color); }
    .clean-table td { padding: 20px 10px; vertical-align: middle; border-bottom: 1px solid var(--border-color); font-size: 14px; color: var(--text-dark); }
    .clean-table tr:last-child td { border-bottom: none; }
    .text-right { text-align: right; } .text-center { text-align: center; }

    .product-row { display: flex; align-items: center; gap: 15px; }
    .product-img { width: 60px; height: 60px; object-fit: contain; border: 1px solid #f0f0f0; border-radius: 6px; padding: 2px; background: #fff; }
    .product-details strong { display: block; margin-bottom: 4px; font-size: 13px; }
    .product-details span { font-size: 11px; color: #999; }
    
    .return-checkbox { width: 18px; height: 18px; accent-color: var(--brand-color); cursor: pointer; }
    .return-qty-select { border: 1px solid #e0e0e0; background-color: #fff; border-radius: 4px; padding: 6px 10px; font-size: 13px; color: #333; min-width: 60px; text-align: center; cursor: pointer; outline: none; }
    .return-qty-select:focus { border-color: var(--brand-color); }

    /* FORMULARZ */
    .return-section-wrapper { background-color: #fafafa; border-top: 1px solid var(--border-color); padding: 30px 25px; margin: 0 -25px -25px -25px; }
    .return-form-flex { display: flex; flex-direction: column; gap: 20px; }
    .return-textarea { width: 100%; border: 1px solid #ddd; border-radius: 6px; padding: 12px; min-height: 80px; font-size: 13px; background: #fff; outline: none; transition: border-color 0.2s; }
    .return-textarea:focus { border-color: var(--brand-color); }
    
    .btn-submit-return { background-color: #fff; border: 1px solid var(--brand-color); color: var(--brand-color); padding: 12px 35px; font-weight: 700; text-transform: uppercase; border-radius: 4px; font-size: 12px; letter-spacing: 1px; cursor: pointer; transition: all 0.3s; float: right; }
    .btn-submit-return:hover { background-color: var(--brand-color); color: #fff; }

    .js-return-error { display: none; background-color: #fce4ec; color: #d01662; border: 1px solid #f5c6cb; padding: 15px; border-radius: 6px; font-size: 13px; font-weight: 600; margin-bottom: 20px; align-items: center; gap: 10px; }
    .js-return-error i { font-size: 16px; }

    .status-pill { display: inline-block; padding: 5px 12px; border-radius: 20px; font-size: 10px; font-weight: 700; text-transform: uppercase; background: #fff; color: #333; border: 1px solid #e0e0e0; }
    .doc-btn { display: inline-flex; align-items: center; gap: 8px; color: var(--text-dark); font-weight: 600; text-decoration: none; background: #fdfdfd; padding: 10px 15px; border: 1px solid #eee; border-radius: 6px; }
    .doc-btn:hover { border-color: var(--brand-color); color: var(--brand-color); }
    
    .fresh-badge { font-size: 10px; color: #d9534f; background: #fff5f5; border: 1px solid #f5c6cb; padding: 2px 6px; border-radius: 4px; display: inline-block; margin-top: 5px; font-weight: 700; }
    .returned-badge { font-size: 10px; color: #856404; background: #fff3cd; border: 1px solid #ffeeba; padding: 2px 6px; border-radius: 4px; display: inline-block; margin-top: 5px; font-weight: 700; margin-right: 5px; }
</style>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        const returnForm = document.getElementById('order-return-form');
        if (returnForm) {
            returnForm.addEventListener('submit', function(event) {
                const errorBox = document.getElementById('js-return-error-msg');
                const checkboxes = this.querySelectorAll('.return-checkbox:checked');
                
                if (checkboxes.length === 0) {
                    event.preventDefault();
                    if (errorBox) {
                        errorBox.style.display = 'flex';
                        errorBox.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }
                    return;
                }
                
                if (errorBox) errorBox.style.display = 'none';

                const textArea = this.querySelector('textarea[name="returnText"]');
                if (textArea && textArea.value.trim() === '') {
                    textArea.value = 'Zwrot bez dodatkowych uwag';
                }
            });
            
            const allCheckboxes = returnForm.querySelectorAll('.return-checkbox');
            allCheckboxes.forEach(cb => {
                cb.addEventListener('change', function() {
                    const errorBox = document.getElementById('js-return-error-msg');
                    if (errorBox) errorBox.style.display = 'none';
                });
            });
        }
    });
</script>
{/literal}

{* --- LOGIKA DOSTĘPNOŚCI ZWROTU --- *}
{assign var="can_return" value=false}
{if isset($fresh_return_data) && $fresh_return_data.is_returnable && ($order.details.is_returnable || (isset($order.history.current.id_order_state) && $order.history.current.id_order_state == 5))}
    {assign var="can_return" value=true}
{/if}

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

        {* Podświetlamy Historię, bo to szczegóły zamówienia *}
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

    {* --- TREŚĆ ZAMÓWIENIA --- *}
    <div class="account-form-content">
        
        {block name='order_infos'}
            <div class="order-header-card">
                <div>
                    <h1 class="order-ref-title">
                        {l s='Zamówienie nr %reference%' d='Shop.Theme.Customeraccount' sprintf=['%reference%' => $order.details.reference]}
                        <span class="order-date">{$order.details.order_date}</span>
                    </h1>
                </div>
                {if $order.details.reorder_url}
                    <a href="{$order.details.reorder_url}" class="btn-reorder">{l s='Reorder' d='Shop.Theme.Actions'}</a>
                {/if}
            </div>

            <div class="box-card">
                <div class="section-title-unified">Informacje o zamówieniu</div>
                <ul>
                    <li><strong>{l s='Carrier' d='Shop.Theme.Checkout'}:</strong> {$order.carrier.name}</li>
                    <li><strong>{l s='Payment method' d='Shop.Theme.Checkout'}:</strong> {$order.details.payment}</li>
                </ul>
            </div>

            {if $order.details.invoice_url}
                <div class="box-card">
                    <div class="section-title-unified">Dokumenty</div>
                    <div class="documents-list">
                        <a href="{$order.details.invoice_url}" target="_blank" class="doc-btn">
                            <i class="fa-solid fa-file-pdf" style="color:#d01662; font-size:18px;"></i>
                            <span>Pobierz Fakturę (PDF) {if $order.details.invoice_number}nr {$order.details.invoice_number}{/if}</span>
                        </a>
                    </div>
                </div>
            {/if}
        {/block}

        {block name='order_history'}
            <section class="box-card">
                <div class="section-title-unified">Status</div>
                <table class="clean-table">
                    <thead>
                        <tr>
                            <th>Data</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                    {foreach from=$order.history item=state}
                        <tr>
                            <td>{$state.history_date}</td>
                            <td><span class="status-pill">{$state.ostate_name}</span></td>
                        </tr>
                    {/foreach}
                    </tbody>
                </table>
            </section>
        {/block}

        {block name='addresses'}
            <div class="addresses-wrapper">
                {if $order.addresses.delivery}
                    <div class="address-box-wrapper">
                        <article class="box-card address-box" id="delivery-address">
                            <div class="section-title-unified">
                                {l s='Adres dostawy' d='Shop.Theme.Checkout'}
                                {if $order.addresses.delivery.alias}
                                    <span class="address-alias-badge">({$order.addresses.delivery.alias})</span>
                                {/if}
                            </div>
                            <address class="address-body">{$order.addresses.delivery.formatted nofilter}</address>
                        </article>
                    </div>
                {/if}

                <div class="address-box-wrapper">
                    <article class="box-card address-box" id="invoice-address">
                        <div class="section-title-unified">
                            {l s='Dane do faktury' d='Shop.Theme.Checkout'}
                            {if $order.addresses.invoice.alias}
                                <span class="address-alias-badge">({$order.addresses.invoice.alias})</span>
                            {/if}
                        </div>
                        <address class="address-body">{$order.addresses.invoice.formatted nofilter}</address>
                    </article>
                </div>
            </div>
        {/block}

        {$HOOK_DISPLAYORDERDETAIL nofilter}

        {block name='order_detail'}
          
          {if $can_return}
            <form id="order-return-form" action="{$urls.pages.order_follow}" method="post">
          {/if}

          <div class="box-card" style="padding: 0;">
            
            {* --- SPLIT BOX - ADRES I TERMIN ZWROTU --- *}
            {if $can_return}
                <div class="section-title-unified" style="margin:25px 25px 0 25px; border-bottom:none;">Zwrot i Termin</div>
                
                <div style="padding: 0 25px 25px 25px;">
                    <div class="box-card" style="margin-bottom: 0; padding: 0;">
                        <div class="return-split-grid">
                            {* LEWA KOLUMNA: AUTOMATYCZNY ADRES *}
                            <div class="return-col">
                                <div class="ret-addr-title">ADRES DO ZWROTU</div>
                                <div class="ret-addr-data">
                                    {if isset($fresh_return_data) && isset($fresh_return_data.shop_address)}
                                        <strong>{$fresh_return_data.shop_address.name}</strong>
                                        {$fresh_return_data.shop_address.address1}<br>
                                        {$fresh_return_data.shop_address.postcode} {$fresh_return_data.shop_address.city}<br>
                                        Tel: {$fresh_return_data.shop_address.phone}
                                    {else}
                                        <strong>Magazyn Zwrotów</strong><br>
                                        (Dane adresowe sklepu niedostępne)
                                    {/if}
                                </div>
                            </div>

                            {* PRAWA KOLUMNA: PASEK I INSTRUKCJA *}
                            <div class="return-col">
                                {if isset($fresh_return_data)}
                                    <div class="timer-top-row">
                                        <span>Czas na zwrot:</span>
                                        <span class="timer-days">Pozostało dni: {$fresh_return_data.days_remaining}</span>
                                    </div>
                                    
                                    {* Pasek Postępu *}
                                    <div class="progress-track">
                                        <div class="progress-fill" style="width: {$fresh_return_data.percent}%"></div>
                                    </div>
                                {/if}

                                <div class="ret-instructions">
                                    <ul>
                                        <li><i class="fa-solid fa-check"></i> Wybierz produkty z listy poniżej.</li>
                                        <li><i class="fa-solid fa-check"></i> Kliknij przycisk "Zrób zwrot".</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            {/if}

            {* TABELA PRODUKTÓW *}
            <div class="section-title-unified" style="margin: 0 25px; border-bottom:none; padding-top:10px;">Kupione produkty</div>
            
            <div class="hidden-sm-down" style="padding: 0 25px 25px 25px;">
                <table class="clean-table">
                  <thead>
                    <tr>
                      {if $can_return} <th class="text-center" style="width: 50px;">Zwrot</th> {/if}
                      <th style="padding-left:0;">Produkt</th>
                      <th class="text-right">Cena jedn.</th>
                      <th class="text-center">Ilość</th>
                      {if $can_return} <th class="text-center">Ilość do zwrotu</th> {/if}
                      <th class="text-right">Razem</th>
                    </tr>
                  </thead>
                  <tbody>
                  {foreach from=$order.products item=product}
                    
                    {* LOGIKA FRESH *}
                    {assign var="is_fresh_product" value=false}
                    {if isset($non_returnable_product_ids) && in_array($product.id_product, $non_returnable_product_ids)}
                        {assign var="is_fresh_product" value=true}
                    {/if}
                    {if $product.id_category_default == 270184}
                        {assign var="is_fresh_product" value=true}
                    {/if}
                    
                    {* MAX ZWROT *}
                    {assign var="max_returnable" value=$product.quantity}
                    {if isset($product.qty_returned) && $product.qty_returned > 0}
                        {assign var="max_returnable" value=$product.quantity-$product.qty_returned}
                    {/if}

                    <tr>
                      {if $can_return}
                        <td class="text-center">
                            {if $is_fresh_product || $max_returnable <= 0}
                                <span style="color:#ccc;"><i class="fa-solid fa-ban"></i></span>
                            {else}
                                <input type="checkbox" class="return-checkbox" name="ids_order_detail[{$product.id_order_detail}]" value="{$product.id_order_detail}">
                            {/if}
                        </td>
                      {/if}

                      <td {if !$can_return}style="padding-left:0;"{/if}>
                        <div class="product-row">
                            {if isset($product.cover) && $product.cover.small.url}
                                <img src="{$product.cover.small.url}" alt="{$product.name}" class="product-img" />
                            {else}
                                <img src="{$urls.no_picture_image.bySize.small_default.url}" class="product-img" />
                            {/if}
                            
                            <div class="product-details">
                                <strong>{$product.name}</strong>
                                {if $product.reference}<span>Ref: {$product.reference}</span>{/if}
                                {if $product.qty_returned > 0}
                                    <br><span class="returned-badge"><i class="fa-solid fa-rotate-left"></i> Zwrócono: {$product.qty_returned} szt.</span>
                                {/if}
                                {if $is_fresh_product}
                                    <br><span class="fresh-badge">Strefa FRESH - brak możliwości zwrotu</span>
                                {/if}
                            </div>
                        </div>
                      </td>
                      <td class="text-right">{$product.price}</td>
                      <td class="text-center">{$product.quantity}</td>
                      
                      {if $can_return}
                        <td class="text-center">
                            {if !$is_fresh_product && $max_returnable > 0}
                                <select name="order_qte_input[{$product.id_order_detail}]" class="return-qty-select">
                                    {section name=quantity start=1 loop=$max_returnable+1}
                                        <option value="{$smarty.section.quantity.index}">{$smarty.section.quantity.index}</option>
                                    {/section}
                                </select>
                            {else}
                                <span style="color:#ccc;">-</span>
                            {/if}
                        </td>
                      {/if}

                      <td class="text-right price-total">{$product.total}</td>
                    </tr>
                  {/foreach}
                  </tbody>
                </table>
                
                <div style="padding: 20px 0;">
                    {foreach $order.subtotals as $line}
                      {if $line.value}
                        <div class="cart-summary-line">
                          <span>{$line.label}</span>
                          <span>{$line.value}</span>
                        </div>
                      {/if}
                    {/foreach}
                    <div class="cart-summary-line total">
                      <span>{$order.totals.total.label}</span>
                      <span style="color:var(--brand-color);">{$order.totals.total.value}</span>
                    </div>
                </div>
            </div>
            
            {* WERSJA MOBILNA *}
            <div class="hidden-md-up" style="padding:15px 25px;">
                {foreach from=$order.products item=product}
                    <div style="display: flex; gap: 15px; margin-bottom: 20px; border-bottom: 1px solid #eee; padding-bottom: 20px;">
                        {if isset($product.cover) && $product.cover.small.url}
                            <img src="{$product.cover.small.url}" class="product-img" />
                        {/if}
                        <div>
                            <strong>{$product.name}</strong>
                            <div style="font-size:12px; margin-top:5px;">
                                {if $can_return && !$is_fresh_product}
                                    <label style="display:flex; align-items:center; gap:5px;">
                                        <input type="checkbox" class="return-checkbox" name="ids_order_detail[{$product.id_order_detail}]" value="{$product.id_order_detail}">
                                        Zwróć
                                    </label>
                                {/if}
                            </div>
                        </div>
                    </div>
                {/foreach}
            </div>

            {* --- SEKCJA ZWROTU (FORMULARZ) --- *}
            {if $can_return}
                <div class="return-section-wrapper">
                    
                    {* BŁĄD JS *}
                    <div id="js-return-error-msg" class="js-return-error">
                        <i class="fa-solid fa-circle-exclamation"></i> Musisz zaznaczyć przynajmniej jeden produkt do zwrotu.
                    </div>

                    <div class="return-form-flex">
                        <div class="return-textarea-col">
                            <div class="section-title-unified" style="border-bottom:none; margin-bottom:10px; padding-bottom:0;">Powód zwrotu (opcjonalnie)</div>
                            <textarea name="returnText" class="return-textarea" placeholder="Wpisz tutaj powód zwrotu lub dodatkowe uwagi..."></textarea>
                        </div>
                        <div class="return-btn-col">
                            <input type="hidden" name="id_order" value="{$order.details.id}">
                            <button type="submit" name="submitReturnMerchandise" class="btn-submit-return">
                                ZRÓB ZWROT
                            </button>
                        </div>
                    </div>
                </div>
            {/if}

          </div>
          
          {if $can_return}
            </form>
          {/if}

        {/block}

        {block name='order_carriers'}{/block}
        {block name='order_messages'}{/block}
        
    </div>
</div>
{/block}