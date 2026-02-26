{**
 * 2007-2025 PrestaShop
 * order-return.tpl - MERGED: Menu 1:1 + Editable Return Form
 *}
{extends file='customer/page.tpl'}

{block name='page_title'}
  {l s='Return details' d='Shop.Theme.Customeraccount'}
{/block}

{block name='page_content'}

{literal}
<style>
    /* --- ZMIENNE --- */
    :root { 
        --c-text: #232323; --c-text-light: #777; 
        --c-brand: #d01662; --brand-color: #d01662;
        --c-brand-bg: #fff0f5; --bg-light: #f8f9fa;
        --c-border: #eaeaea; --font-base: 'Open Sans', Helvetica, Arial, sans-serif;
    }

    #content .alert-warning { display: none !important; }
    .return-page-wrapper { color: var(--c-text); font-family: var(--font-base); }

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


    /* --- STYLE SZCZEGÓŁÓW ZWROTU --- */
    .white-box { background: #fff; border: 1px solid var(--c-border); margin-bottom: 25px; border-radius: 8px; }
    .box-pad { padding: 25px; }

    /* NAGŁÓWKI */
    .section-head { padding: 20px 25px 5px 25px; font-size: 13px; font-weight: 800; text-transform: uppercase; color: var(--c-text); letter-spacing: 0.5px; }
    .header-box { display: flex; align-items: center; gap: 15px; padding: 20px 25px; }
    .header-box h1 { font-size: 16px; font-weight: 800; text-transform: uppercase; margin: 0; color: var(--c-text); }
    .header-box .date { font-size: 13px; color: #999; }

    /* GRID INFO */
    .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 25px; margin-bottom: 25px; }
    @media (max-width: 768px) { .info-grid { grid-template-columns: 1fr; gap:0; } .info-grid .white-box { margin-bottom:20px; } }
    .info-item { margin-bottom: 8px; font-size: 13px; line-height: 1.6; color: var(--c-text-light); }
    .info-item strong { color: var(--c-text); font-weight: 700; margin-right: 5px; }
    .btn-pdf { color: var(--c-brand); font-weight: 700; font-size: 13px; text-decoration: none !important; display: inline-flex; align-items: center; gap: 8px; margin-top: 5px; }
    
    /* STATUS */
    .status-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
    .status-table th { text-align: left; font-size: 10px; color: #999; text-transform: uppercase; font-weight: 800; letter-spacing: 1px; padding: 0 0 15px 25px; }
    .status-table td { padding: 20px 0 20px 25px; border-top: 1px solid #f6f6f6; vertical-align: middle; font-size: 14px; color: var(--c-text); }
    .status-pill { display: inline-block; background: #ffffff; border: 1px solid #ddd; border-radius: 50px; padding: 6px 18px; font-size: 11px; font-weight: 700; text-transform: uppercase; color: #333; letter-spacing: 0.5px; }

    /* ADRES & TIMER SPLIT */
    .address-split-container { display: grid; grid-template-columns: 1fr 1fr; border-top: 1px solid var(--c-border); }
    @media (max-width: 768px) { .address-split-container { grid-template-columns: 1fr; } }
    .split-col { padding: 25px; }
    .split-col:first-child { border-right: 1px solid var(--c-border); }
    @media (max-width: 768px) { .split-col:first-child { border-right: none; border-bottom: 1px solid var(--c-border); } }
    .addr-label { font-size: 10px; font-weight: 700; color: #999; text-transform: uppercase; margin-bottom: 10px; letter-spacing: 1px; }
    .addr-data strong { display: block; margin-bottom: 5px; font-size: 14px; font-weight: 800; text-transform: uppercase; }
    .addr-data { font-size: 14px; line-height: 1.6; }
    
    .timer-wrapper { margin-bottom: 15px; }
    .timer-text { font-size: 13px; font-weight: 700; color: var(--c-brand); margin-bottom: 8px; display: flex; justify-content: space-between; }
    .progress-track { background: #f0f0f0; height: 8px; border-radius: 10px; overflow: hidden; margin-bottom: 15px; }
    .progress-fill { background: var(--c-brand); height: 100%; border-radius: 10px; transition: width 1s ease-in-out; }
    .pack-instruction { font-size: 13px; color: var(--c-text-light); line-height: 1.6; }
    .pack-instruction ul { list-style: none; padding: 0; margin: 0; }
    .pack-instruction li { margin-bottom: 6px; display: flex; align-items: flex-start; gap: 8px; }
    .pack-instruction li i { color: var(--c-brand); margin-top: 4px; font-size: 12px; }
    
    /* PRODUKTY */
    .prod-head { display: grid; grid-template-columns: 1fr 120px 80px 120px 60px; padding: 15px 25px; border-bottom: 1px solid #f6f6f6; margin-top: 10px; }
    .ph-col { font-size: 10px; font-weight: 800; text-transform: uppercase; color: #999; letter-spacing: 0.5px; }
    .text-r { text-align: right; } .text-c { text-align: center; }
    .prod-row { display: grid; grid-template-columns: 1fr 120px 80px 120px 60px; padding: 20px 25px; border-bottom: 1px solid #f6f6f6; align-items: center; }
    .p-flex { display: flex; align-items: center; gap: 20px; }
    .p-img { width: 50px; height: 50px; display: flex; align-items: center; justify-content: center; padding: 2px; }
    .p-img img { max-width: 100%; max-height: 100%; object-fit: contain; }
    .p-info h4 { font-size: 13px; font-weight: 700; margin: 0 0 4px 0; color: var(--c-text); }
    .tag-ret { color: #e0a300; font-size: 10px; background: #fff8e1; padding: 2px 6px; margin-top: 5px; display: inline-block; font-weight: 700; }
    
    /* Summary */
    .total-summary-box { padding: 25px; text-align: right; background: #fafafa; border-top: 1px solid #eee; display: flex; justify-content: space-between; align-items: center; }
    .total-label { font-size: 11px; text-transform: uppercase; color: #999; font-weight: 700; margin-bottom: 5px; letter-spacing: 0.5px; }
    .total-value { font-size: 20px; font-weight: 800; color: var(--c-brand); }

    /* EDIT STYLES */
    .qty-select-clean { 
        border: 1px solid #e0e0e0; background: #fff; border-radius: 4px; padding: 6px 10px; 
        text-align: center; font-weight: 700; color: #333; cursor: pointer; min-width: 60px;
        appearance: none; -webkit-appearance: none; -moz-appearance: none;
        background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e");
        background-repeat: no-repeat; background-position: right 5px center; background-size: 12px;
        padding-right: 25px;
    }
    .qty-select-clean:focus { border-color: var(--c-brand); outline: none; }

    .btn-delete-clean { 
        background: none; border: none; color: #ccc; cursor: pointer; font-size: 18px; 
        transition: 0.2s; padding: 8px; display: flex; align-items: center; justify-content: center;
        margin: 0 auto;
    }
    .btn-delete-clean:hover { color: #d01662; transform: scale(1.1); }

    .btn-save-outline { 
        background: #fff; color: var(--c-brand); border: 1px solid var(--c-brand); 
        padding: 10px 25px; border-radius: 4px; font-weight: 600; text-transform: uppercase; 
        font-size: 12px; cursor: pointer; transition: 0.3s; display: inline-flex; align-items: center; gap: 8px; letter-spacing: 0.5px;
    }
    .btn-save-outline:hover { background: var(--c-brand); color: #fff; box-shadow: 0 4px 12px rgba(208, 22, 98, 0.2); }
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
        
        {if $configuration.voucher_enabled && !$configuration.is_catalog}<a class="sidebar-link" href="{$urls.pages.discount}"><i class="fa-regular fa-credit-card"></i><span>Kupony rabatowe</span></a>{/if}
        
        {if $configuration.return_enabled && !$configuration.is_catalog}
            <a class="sidebar-link active-page" href="{$urls.pages.order_follow}">
                <i class="fa-solid fa-rotate-left"></i><span>Zwroty towarów</span>
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

    {* TREŚĆ *}
    <div class="return-page-wrapper">
        
        {block name='order_return_infos'}
            
            {* 1. NAGŁÓWEK *}
            <div class="white-box header-box">
                <h1>{l s='ZWROT NR %number%' d='Shop.Theme.Customeraccount' sprintf=['%number%' => $return.return_number]}</h1>
                <span class="date">{$return.return_date}</span>
            </div>

            {* 2. INFO / DOKUMENTY *}
            <div class="info-grid">
                <div class="white-box">
                    <div class="section-head">INFORMACJE O ZWROCIE</div>
                    <div class="box-pad">
                        <div class="info-item"><strong>Przewoźnik:</strong> Wysyłka we własnym zakresie</div>
                        <div class="info-item"><strong>Płatność:</strong> Zwrot na konto / kartę</div>
                        <div class="info-item" style="margin-top:15px; color:#d01662; font-size:12px;">
                            <i class="fa-solid fa-circle-info"></i> Środki zwracamy w ciągu 7 dni od odbioru.
                        </div>
                    </div>
                </div>

                <div class="white-box">
                    <div class="section-head">DOKUMENTY</div>
                    <div class="box-pad">
                        {if $return.print_url}
                            <a href="{$return.print_url}" class="btn-pdf" target="_blank">
                                <i class="fa-solid fa-file-pdf"></i> Pobierz formularz zwrotu (PDF)
                            </a>
                        {else}
                            <div class="info-item">Brak dostępnych dokumentów PDF.</div>
                            <div class="info-item" style="margin-top:5px; font-size:12px; color:#999;">Użyj odręcznej notatki z numerem zwrotu.</div>
                        {/if}
                    </div>
                </div>
            </div>

            {* 3. STATUS *}
            <div class="white-box">
                <div class="section-head">STATUS</div>
                <table class="status-table">
                    <thead>
                        <tr>
                            <th width="30%">DATA</th>
                            <th>STATUS</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>{$return.return_date}</td>
                            <td><span class="status-pill">{$return.state_name}</span></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            {* 4. ADRES + TIMER *}
            <div class="white-box">
                <div class="section-head">ADRES ZWROTU I TERMIN</div>
                
                <div class="address-split-container">
                    {* LEWA *}
                    <div class="split-col">
                        <div class="addr-label">WYŚLIJ ZWROT DO:</div>
                        <div class="addr-data">
                            {if isset($shop_return_address)}
                                <strong>{$shop_return_address.name}</strong>
                                {$shop_return_address.address1}
                                {if $shop_return_address.address2}<br>{$shop_return_address.address2}{/if}
                                <br>{$shop_return_address.postcode} {$shop_return_address.city}
                                {if $shop_return_address.phone}
                                    <br><span style="color:#999; font-size:13px;">Tel: {$shop_return_address.phone}</span>
                                {/if}
                            {else}
                                <strong>Magazyn Zwrotów</strong><br>(Brak danych)
                            {/if}
                        </div>
                    </div>

                    {* PRAWA *}
                    <div class="split-col">
                        {if isset($return_timer) && $return_timer.show}
                            <div class="timer-wrapper">
                                <div class="timer-text">
                                    <span>Pozostało dni na zwrot (od zakupu):</span>
                                    <span>{$return_timer.days} dni</span>
                                </div>
                                <div class="progress-track">
                                    <div class="progress-fill" style="width: {$return_timer.percent}%"></div>
                                </div>
                            </div>
                        {/if}
                        
                        <div class="pack-instruction">
                            <div class="addr-label">CO WŁOŻYĆ DO PACZKI?</div>
                            <ul>
                                <li><i class="fa-solid fa-check"></i> Zwracane produkty</li>
                                {if $return.print_url}
                                     <li><i class="fa-solid fa-check"></i> Wydrukowany formularz zwrotu</li>
                                {else}
                                     <li><i class="fa-solid fa-pen"></i> Kartkę z numerem: <strong>{$return.return_number}</strong></li>
                                {/if}
                                <li><i class="fa-solid fa-box-open"></i> Odpowiednie zabezpieczenie</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            
            {* 5. PRODUKTY (EDYCJA) *}
            <div class="white-box">
                <div class="section-head" style="padding-bottom:5px;">ZWRACANE PRODUKTY</div>
                
                {* ACTION URL: Wysyła dane do modułu w celu aktualizacji *}
                {if isset($can_edit_return) && $can_edit_return}
                    <form action="{$urls.current_url}" method="post">
                    <input type="hidden" name="id_order_return" value="{$return.id}">
                    <input type="hidden" name="submitUpdateReturn" value="1">
                {/if}

                <div class="prod-head">
                    <div class="ph-col">PRODUKT</div>
                    <div class="ph-col text-r">CENA JEDN.</div>
                    <div class="ph-col text-c">ILOŚĆ</div>
                    <div class="ph-col text-r">RAZEM</div>
                    {if isset($can_edit_return) && $can_edit_return}
                        <div class="ph-col text-c" style="width:60px;">USUŃ</div>
                    {/if}
                </div>

                {foreach from=$products item=product}
                    {assign var="data_key" value=$product.id_order_detail}
                    {assign var="extra" value=[]}
                    {if isset($return_extra_data) && isset($return_extra_data[$data_key])}
                        {assign var="extra" value=$return_extra_data[$data_key]}
                    {/if}

                    <div class="prod-row">
                        <div class="p-flex">
                            <div class="p-img">
                                {if isset($extra.image_url) && $extra.image_url}
                                    <img src="{$extra.image_url}" alt="{$product.product_name}">
                                {else}
                                    <i class="fa-solid fa-box-open" style="color:#ddd;"></i>
                                {/if}
                            </div>
                            <div class="p-info">
                                <h4>{$product.product_name}</h4>
                                {if $product.product_reference}
                                    <div style="font-size:11px; color:#aaa;">Ref: {$product.product_reference}</div>
                                {/if}
                                <span class="tag-ret">Zwrócono</span>
                            </div>
                        </div>

                        <div class="text-r" style="font-size:13px;">
                            {if isset($extra.price_formatted)}{$extra.price_formatted}{else}-{/if}
                        </div>

                        <div class="text-c">
                            {if isset($can_edit_return) && $can_edit_return}
                                <select name="return_qty[{$product.id_order_detail}]" class="qty-select-clean">
                                    {if isset($extra.max_qty)}
                                        {section name=qty start=1 loop=$extra.max_qty+1}
                                            <option value="{$smarty.section.qty.index}" {if $product.product_quantity == $smarty.section.qty.index}selected{/if}>
                                                {$smarty.section.qty.index}
                                            </option>
                                        {/section}
                                    {else}
                                        <option value="{$product.product_quantity}">{$product.product_quantity}</option>
                                    {/if}
                                </select>
                            {else}
                                <span style="font-size:13px; font-weight:700;">{$product.product_quantity}</span>
                            {/if}
                        </div>

                        <div class="text-r" style="font-size:13px; font-weight:700;">
                            {if isset($extra.total_formatted)}{$extra.total_formatted}{else}-{/if}
                        </div>

                        {if isset($can_edit_return) && $can_edit_return}
                            <div class="text-c">
                                <button type="submit" name="delete_product[]" value="{$product.id_order_detail}" class="btn-delete-clean" title="Usuń z listy" onclick="return confirm('Czy na pewno chcesz usunąć ten produkt ze zwrotu?');">
                                    <i class="fa-regular fa-trash-can"></i>
                                </button>
                            </div>
                        {/if}
                    </div>
                {/foreach}
                
                {* Podsumowanie i przycisk Zapisz *}
                <div class="total-summary-box">
                    <div style="text-align:left;">
                        {if isset($can_edit_return) && $can_edit_return}
                            <button type="submit" name="submitUpdateReturn" value="1" class="btn-save-outline">
                                <i class="fa-solid fa-check"></i> Zapisz zmiany
                            </button>
                        {/if}
                    </div>
                    <div>
                        <div class="total-label">KWOTA TWOJEGO ZWROTU</div>
                        <div class="total-value">{if isset($total_return_sum)}{$total_return_sum}{else}-{/if}</div>
                    </div>
                </div>

                {if isset($can_edit_return) && $can_edit_return}
                    </form>
                {/if}
            </div>

        {/block}
        
    </div>
</div>
{/block}