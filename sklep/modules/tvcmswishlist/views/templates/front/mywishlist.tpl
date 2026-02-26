{**
 * 2007-2025 PrestaShop
 * mywishlist.tpl - FINAL FIX: Syntax Error Removed
 *}
{strip}
{extends file='customer/page.tpl'}

{block name='page_title'}
  {l s='My wishlists' mod='tvcmswishlist'}
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
    .wishlist-page-wrapper { color: var(--c-text); font-family: var(--font-base); }

    /* UKŁAD STRONY */
    .account-sidebar-layout { display: grid; grid-template-columns: 300px 1fr; gap: 40px; margin-bottom: 60px; align-items: start; }
    @media (max-width: 991px) { .account-sidebar-layout { grid-template-columns: 1fr; gap: 30px; } }

    /* --- MENU BOCZNE --- */
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
    
    /* --- STYL AKTYWNY I HOVER (POPIELATY) --- */
    .sidebar-link:hover, .sidebar-link.active-page { 
        background: var(--bg-light); 
        color: var(--brand-color); 
        border-color: #eee;
    }
    .sidebar-link:hover i, .sidebar-link.active-page i { 
        color: var(--brand-color); 
        transform: translateX(3px); 
    }
    
    /* MODUŁY */
    .account-modules-sidebar { display: flex !important; flex-direction: column !important; gap: 8px !important; }
    .account-modules-sidebar ul, .account-modules-sidebar li { display: contents !important; list-style: none !important; margin: 0 !important; padding: 0 !important; }

    .account-modules-sidebar a {
        display: flex !important; align-items: center !important; flex-direction: row !important; justify-content: flex-start !important;
        padding: 12px 15px !important; margin: 0 !important; background: transparent !important; 
        border-radius: 6px !important; text-decoration: none !important; color: var(--c-text) !important; 
        width: 100% !important; min-height: 44px; position: relative !important; box-sizing: border-box !important; gap: 15px !important;
        border: 1px solid transparent !important;
    }
    /* Ikony */
    .account-modules-sidebar a i, .account-modules-sidebar a svg, .account-modules-sidebar a img, .account-modules-sidebar a .material-icons { display: none !important; }
    
    /* Globalne wstawianie ikon FontAwesome */
    .account-modules-sidebar a::before {
        font-family: "Font Awesome 6 Free"; font-weight: 900; font-size: 18px; 
        width: 25px; min-width: 25px; text-align: center; color: #ccc;
        display: flex; align-items: center; justify-content: center;
        transition: all 0.2s; flex-shrink: 0; content: "\f0da";
    }
    
    /* --- WYMUSZENIE AKTYWNEGO KOLORU DLA LISTY ŻYCZEŃ (POPIELATY) --- */
    .account-modules-sidebar a[href*="wishlist"], 
    .account-modules-sidebar a[href*="ulubione"] {
        background: #f8f9fa !important; 
        color: var(--brand-color) !important; 
        border: 1px solid #eee !important;
    }
    /* Zmieniamy też kolor ikony dla aktywnego elementu */
    .account-modules-sidebar a[href*="wishlist"]::before,
    .account-modules-sidebar a[href*="ulubione"]::before {
        content: "\f004" !important; 
        color: var(--brand-color) !important;
    }

    /* Ikony dla pozostałych modułów */
    .account-modules-sidebar a[href*="loyalty"]::before, .account-modules-sidebar a[href*="punkty"]::before { content: "\f4d3" !important; }
    .account-modules-sidebar a[href*="blog"]::before, .account-modules-sidebar a[href*="comment"]::before { content: "\f086" !important; }

    /* Ogólne style tekstu i hover */
    .account-modules-sidebar a span, .account-modules-sidebar a div {
        font-size: 13px !important; font-weight: 600 !important; color: inherit !important;
        text-align: left !important; width: auto !important; flex: 1 !important;
        margin: 0 !important; padding: 0 !important; position: static !important; display: block !important; line-height: 1.2 !important;
    }
    
    /* Hover dla wszystkich modułów (Popielaty) */
    .account-modules-sidebar a:hover { 
        background: #f8f9fa !important; 
        color: var(--brand-color) !important; 
        border-color: #eee !important;
    }
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


    /* --- STYLE CONTENTU WISHLIST --- */
    .white-box { background: #fff; border: 1px solid var(--c-border); margin-bottom: 25px; border-radius: 8px; overflow: hidden; }
    .box-pad { padding: 30px; }
    .header-box { display: flex; align-items: center; gap: 15px; padding: 20px 25px; }
    .header-box h1 { font-size: 16px; font-weight: 800; text-transform: uppercase; margin: 0; color: var(--c-text); letter-spacing: 0.5px; }

    /* Formularz */
    .wishlist-form-label { font-size: 11px; font-weight: 700; text-transform: uppercase; color: #555; margin-bottom: 8px; display: block; }
    .form-control-wishlist {
        background: #fff; border: 1px solid #ddd; border-radius: 4px;
        padding: 8px 15px; height: 42px; font-size: 13px; color: #333; width: 100%; outline: none;
    }
    .form-control-wishlist:focus { border-color: var(--brand-color); outline: none; }
    
    .btn-save-wishlist { 
        background: #fff; border: 1px solid var(--brand-color); color: var(--brand-color);
        padding: 10px 25px; font-weight: 700; text-transform: uppercase; border-radius: 4px; 
        font-size: 12px; cursor: pointer; transition: all 0.3s; margin-top: 15px;
    }
    .btn-save-wishlist:hover { background: var(--brand-color); color: #fff; }

    /* Tabela */
    .clean-table { width: 100%; border-collapse: collapse; }
    .clean-table th { 
        text-align: left; padding: 18px 20px; 
        font-size: 11px; text-transform: uppercase; color: #999; font-weight: 700; letter-spacing: 1px; 
        border-bottom: 1px solid var(--c-border); background: #fcfcfc;
    }
    .clean-table td { padding: 18px 20px; vertical-align: middle; border-bottom: 1px solid var(--c-border); font-size: 13px; color: var(--c-text); }
    .clean-table tr:last-child td { border-bottom: none; }
    .text-center { text-align: center; }
    
    .wishlist-link { font-weight: 700; color: #333; text-decoration: none; font-size: 14px; }
    .wishlist-link:hover { color: var(--brand-color); text-decoration: underline; }
    
    .action-icon i { font-size: 20px; color: #ccc; transition: 0.2s; }
    .action-icon:hover i { color: var(--brand-color); }
    
    /* Ukrycie starych elementów */
    .tvwishlist-back-btn { display: none !important; }
    .page-subheading { display: none !important; }
</style>
{/literal}

<div class="account-sidebar-layout">

    {* --- MENU BOCZNE --- *}
    <div class="account-menu-column">
        <div class="menu-header">MOJE KONTO</div> 
        <a class="sidebar-link" href="{$link->getPageLink('identity', true)}"><i class="fa-regular fa-user"></i><span>Dane konta</span></a>
        <a class="sidebar-link" href="{$link->getPageLink('addresses', true)}"><i class="fa-regular fa-map"></i><span>Adresy</span></a>
        <a class="sidebar-link" href="{$link->getPageLink('history', true)}"><i class="fa-regular fa-folder-open"></i><span>Historia zamówień</span></a>
        
        {if !$configuration.is_catalog}
            <a class="sidebar-link" href="{$link->getPageLink('order-slip', true)}">
                <i class="fa-regular fa-file-lines"></i><span>Moje korekty</span>
            </a>
        {/if}
        
        {if $configuration.voucher_enabled && !$configuration.is_catalog}
            <a class="sidebar-link" href="{$link->getPageLink('discount', true)}">
                <i class="fa-regular fa-credit-card"></i><span>Kupony rabatowe</span>
            </a>
        {/if}
        
        {if $configuration.return_enabled && !$configuration.is_catalog}
            <a class="sidebar-link" href="{$link->getPageLink('order-follow', true)}">
                <i class="fa-solid fa-rotate-left"></i><span>Zwroty towarów</span>
            </a>
        {/if}
        
        {* MODUŁY (TUTAJ JEST WISHLIST W GENEROWANYM KODZIE) *}
        <div class="account-modules-sidebar">
            {hook h='displayCustomerAccount'}
        </div>

        <div class="logout-btn-sidebar">
            <a href="{$link->getPageLink('index', true, NULL, 'mylogout')}" class="btn-logout-modern">
                <i class="fa-solid fa-power-off"></i>
                {l s='Sign out' d='Shop.Theme.Actions'}
            </a>
        </div>
    </div>

    {* --- TREŚĆ WISHLIST --- *}
    <div class="account-form-content wishlist-page-wrapper">
        
        {* Nagłówek *}
        <div class="white-box header-box">
            <h1>Moje listy życzeń</h1>
        </div>

        {* ID: mywishlist - wymagane przez JS modułu *}
        <div id="mywishlist">
            
            {if $id_customer|intval neq 0}
                
                {* FORMULARZ NOWEJ LISTY *}
                <div class="white-box box-pad">
                    <form method="post" class="std" id="form_wishlist">
                        <fieldset>
                            <input type="hidden" name="token" value="{$token|escape:'html':'UTF-8'}" />
                            <div class="form-group">
                                <label class="wishlist-form-label" for="name">
                                    {l s='Nazwa nowej listy' mod='tvcmswishlist'}
                                </label>
                                <input type="text" id="name" name="name" class="form-control-wishlist" placeholder="np. Pre prezenty, Do kuchni..." value="{if isset($smarty.post.name) and $errors|@count > 0}{$smarty.post.name|escape:'html':'UTF-8'}{/if}" />
                            </div>
                            <div style="text-align: right;">
                                <button id="submitWishlist" class="btn-save-wishlist" type="submit" name="submitWishlist">
                                    <span>{l s='Utwórz listę' mod='tvcmswishlist'}</span>
                                </button>
                            </div>
                        </fieldset>
                    </form>
                </div>

                {* LISTA ISTNIEJĄCYCH LIST *}
                {if $wishlists}
                    <div id="block-history" class="white-box">
                        <table class="clean-table">
                            <thead>
                                <tr>
                                    <th>{l s='Name' mod='tvcmswishlist'}</th>
                                    <th class="text-center">{l s='Qty' mod='tvcmswishlist'}</th>
                                    <th class="text-center">{l s='Viewed' mod='tvcmswishlist'}</th>
                                    <th>{l s='Created' mod='tvcmswishlist'}</th>
                                    <th class="text-center">{l s='Direct Link' mod='tvcmswishlist'}</th>
                                    <th class="text-center">{l s='Default' mod='tvcmswishlist'}</th>
                                    <th class="text-center">{l s='Delete' mod='tvcmswishlist'}</th>
                                </tr>
                            </thead>
                            <tbody>
                                {section name=i loop=$wishlists}
                                    <tr id="wishlist_{$wishlists[i].id_wishlist|intval}">
                                        <td>
                                            <a href="#" class="wishlist-link" onclick="javascript:event.preventDefault();WishlistManage('block-order-detail', '{$wishlists[i].id_wishlist|intval}');">
                                                {$wishlists[i].name|truncate:30:'...'|escape:'htmlall':'UTF-8'}
                                            </a>
                                        </td>
                                        <td class="text-center bold">
                                            {assign var=n value=0}
                                            {foreach from=$nbProducts item=nb name=i}
                                                {if $nb.id_wishlist eq $wishlists[i].id_wishlist}
                                                    {assign var=n value=$nb.nbProducts|intval}
                                                {/if}
                                            {/foreach}
                                            {if $n}
                                                {$n|intval|escape:'htmlall':'UTF-8'}
                                            {else}
                                                0
                                            {/if}
                                        </td>
                                        <td class="text-center">{$wishlists[i].counter|intval|escape:'htmlall':'UTF-8'}</td>
                                        <td>{$wishlists[i].date_add|date_format:"%Y-%m-%d"|escape:'htmlall':'UTF-8'}</td>
                                        <td class="text-center">
                                            <a href="#" class="action-icon" onclick="javascript:event.preventDefault();WishlistManage('block-order-detail', '{$wishlists[i].id_wishlist|intval|escape:"htmlall":"UTF-8"}');">
                                                <i class='material-icons'>&#xe8f4;</i> {* Eye icon *}
                                            </a>
                                        </td>
                                        <td class="text-center">
                                            {if isset($wishlists[i].default) && $wishlists[i].default == 1}
                                                <span style="color:#d01662;"><i class='material-icons'>&#xe834;</i></span>
                                            {else}
                                                <a href="#" class="action-icon" onclick="javascript:event.preventDefault();(WishlistDefault('wishlist_{$wishlists[i].id_wishlist|intval|escape:'htmlall':'UTF-8'}', '{$wishlists[i].id_wishlist|intval|escape:'htmlall':'UTF-8'}'));">
                                                    <i class='material-icons'>&#xe835;</i>
                                                </a>
                                            {/if}
                                        </td>
                                        <td class="text-center">
                                            <a class="action-icon" href="#" onclick="javascript:event.preventDefault();return (WishlistDelete('wishlist_{$wishlists[i].id_wishlist|intval|escape:'htmlall':'UTF-8'}', '{$wishlists[i].id_wishlist|intval|escape:'htmlall':'UTF-8'}', '{l s='Do you really want to delete this wishlist ?' mod='tvcmswishlist' js=1}'));">
                                                <i class='material-icons'>&#xe872;</i>
                                            </a>
                                        </td>
                                    </tr>
                                {/section}
                            </tbody>
                        </table>
                    </div>
                    
                    {* TU ŁADUJĄ SIĘ SZCZEGÓŁY PO KLIKNIĘCIU (AJAX) *}
                    <div id="block-order-detail">&nbsp;</div>
                {/if}
            {/if}
        </div>
    </div>
</div>
{/block}
{/strip}