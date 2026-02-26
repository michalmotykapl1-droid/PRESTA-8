{* Plik: modules/wyprzedaz/views/templates/admin/configure.tpl *}
{assign var=sort value=$sort|default:''}
{assign var=way value=$way|default:''}
{assign var=sort_not_found value=$sort_not_found|default:''}
{assign var=way_not_found value=$way_not_found|default:''}
{assign var=sort_duplicates value=$sort_duplicates|default:''}
{assign var=way_duplicates value=$way_duplicates|default:''}

<div class="panel">
    <div class="panel-heading">
        <i class="icon-cogs"></i> {l s='Panel zarządzania modułem Wyprzedaż' mod='wyprzedaz'}
    </div>

    {* Ustawienia rabatów *}
    <form action="{$current|escape:'html':'UTF-8'}&token={$token|escape:'html':'UTF-8'}" method="post" class="form-horizontal">
        <fieldset>
            <legend>{l s='Ustawienia rabatów i dat' mod='wyprzedaz'}</legend>

            <div class="form-group">
                <label class="control-label col-lg-3">{l s='Liczba dni dla "Krótkiej daty"' mod='wyprzedaz'}</label>
                <div class="col-lg-9">
                    <input type="text" name="WYPRZEDAZ_SHORT_DATE_DAYS" value="{$WYPRZEDAZ_SHORT_DATE_DAYS|default:14}" class="form-control" style="width: 80px; display: inline-block;" />
                    <p class="help-block">{l s='Poniżej tej liczby dni produkt trafi do kategorii "Krótka data" i otrzyma dedykowany rabat.' mod='wyprzedaz'}</p>
                </div>
            </div>

            <div class="form-group">
                <label class="control-label col-lg-3">{l s='Rabat dla grupy "Krótka data" (%)' mod='wyprzedaz'}</label>
                <div class="col-lg-9">
                    <input type="text" name="WYPRZEDAZ_DISCOUNT_SHORT" value="{$WYPRZEDAZ_DISCOUNT_SHORT|default:0}" class="form-control" style="width: 80px; display: inline-block;" /> %
                </div>
            </div>
            <div class="form-group">
                <label class="control-label col-lg-3">{l s='Rabat dla daty < 7 dni (%)' mod='wyprzedaz'}</label>
                <div class="col-lg-9">
                    <input type="text" name="WYPRZEDAZ_DISCOUNT_VERY_SHORT" value="{$WYPRZEDAZ_DISCOUNT_VERY_SHORT|default:50}" class="form-control" style="width: 80px; display: inline-block;" /> %
                    <p class="help-block">{l s='Ten rabat nadpisze wartość "Krótkiej daty", jeśli do terminu ważności pozostaje mniej niż 7 dni.' mod='wyprzedaz'}</p>
                </div>
            </div>
            
            <div class="form-group" id="group_discount_bin">
                <label class="control-label col-lg-3">{l s='Rabat dla regału "KOSZ" (%)' mod='wyprzedaz'}</label>
                <div class="col-lg-9">
                    <input type="text" name="WYPRZEDAZ_DISCOUNT_BIN" value="{$WYPRZEDAZ_DISCOUNT_BIN|default:0}" class="form-control" style="width: 80px; display: inline-block;" /> %
                    <p class="help-block">{l s='Ten rabat jest nadrzędny i nadpisze wszystkie inne rabaty, jeśli produkt znajduje się na regale "KOSZ".' mod='wyprzedaz'}</p>
                </div>
            </div>

            <div class="form-group">
                <label class="control-label col-lg-3">{l s='Ignoruj datę ważności dla regału "KOSZ"' mod='wyprzedaz'}</label>
                <div class="col-lg-9">
                    <span class="switch prestashop-switch fixed-width-lg">
                        <input type="radio" name="WYPRZEDAZ_IGNORE_BIN_EXPIRY" id="WYPRZEDAZ_IGNORE_BIN_EXPIRY_on" value="1" {if $WYPRZEDAZ_IGNORE_BIN_EXPIRY}checked="checked"{/if}>
                        <label for="WYPRZEDAZ_IGNORE_BIN_EXPIRY_on" class="radio-label">{l s='Tak' mod='wyprzedaz'}</label>
                        <input type="radio" name="WYPRZEDAZ_IGNORE_BIN_EXPIRY" id="WYPRZEDAZ_IGNORE_BIN_EXPIRY_off" value="0" {if !$WYPRZEDAZ_IGNORE_BIN_EXPIRY}checked="checked"{/if}>
                        <label for="WYPRZEDAZ_IGNORE_BIN_EXPIRY_off" class="radio-label">{l s='Nie' mod='wyprzedaz'}</label>
                        <a class="slide-button btn"></a>
                    </span>
                    <p class="help-block">{l s='Jeśli włączone, produkty z regału "KOSZ" nie będą oznaczane jako "Po terminie" i trafią do kategorii Wyprzedaż.' mod='wyprzedaz'}</p>
                </div>
            </div>
            
            <div class="form-group">
                <label class="control-label col-lg-3">{l s='Rabat WYPRZEDAŻ – do 30 dni od przyjęcia (%)' mod='wyprzedaz'}</label>
                <div class="col-lg-9">
                    <input type="text" name="WYPRZEDAZ_DISCOUNT_30" value="{$WYPRZEDAZ_DISCOUNT_30|default:0}" class="form-control" style="width: 80px; display: inline-block;" /> %
                </div>
            </div>
            <div class="form-group">
                <label class="control-label col-lg-3">{l s='Rabat WYPRZEDAŻ – od 31 do 90 dni od przyjęcia (%)' mod='wyprzedaz'}</label>
                <div class="col-lg-9">
                    <input type="text" name="WYPRZEDAZ_DISCOUNT_90" value="{$WYPRZEDAZ_DISCOUNT_90|default:0}" class="form-control" style="width: 80px; display: inline-block;" /> %
                </div>
            </div>
            <div class="form-group">
                <label class="control-label col-lg-3">{l s='Rabat WYPRZEDAŻ – powyżej 90 dni od przyjęcia (%)' mod='wyprzedaz'}</label>
                <div class="col-lg-9">
                    <input type="text" name="WYPRZEDAZ_DISCOUNT_OVER" value="{$WYPRZEDAZ_DISCOUNT_OVER|default:0}" class="form-control" style="width: 80px; display: inline-block;" /> %
                </div>
            </div>

            <div class="form-group">
                <label class="control-label col-lg-3">{l s='Włącz regułę: >90 dni od przyjęcia i ważność ≥ 6 miesięcy' mod='wyprzedaz'}</label>
                <div class="col-lg-9">
                    <span class="switch prestashop-switch fixed-width-lg">
                        <input type="radio" name="WYPRZEDAZ_ENABLE_OVER90_LONGEXP" id="enable_over90_1" value="1" {if $WYPRZEDAZ_ENABLE_OVER90_LONGEXP}checked="checked"{/if} />
                        <label for="enable_over90_1">{l s='Tak' mod='wyprzedaz'}</label>
                        <input type="radio" name="WYPRZEDAZ_ENABLE_OVER90_LONGEXP" id="enable_over90_0" value="0" {if !$WYPRZEDAZ_ENABLE_OVER90_LONGEXP}checked="checked"{/if} />
                        <label for="enable_over90_0">{l s='Nie' mod='wyprzedaz'}</label>
                        <a class="slide-button btn"></a>
                    </span>
                    <p class="help-block">{l s='Jeśli włączone: gdy minęło >90 dni od przyjęcia, a do daty ważności jest ≥ 6 miesięcy, zastosuj osobny rabat.' mod='wyprzedaz'}</p>
                </div>
            </div>

            <div class="form-group" id="group_over90_longexp" {if !$WYPRZEDAZ_ENABLE_OVER90_LONGEXP}style="display:none"{/if}>
                <label class="control-label col-lg-3">{l s='Rabat dla >90 dni i ważność ≥ 6 m-cy (%)' mod='wyprzedaz'}</label>
                <div class="col-lg-9">
                    <input type="text" name="WYPRZEDAZ_DISCOUNT_OVER90_LONGEXP" value="{$WYPRZEDAZ_DISCOUNT_OVER90_LONGEXP|escape:'html':'UTF-8'}" class="form-control" style="width: 80px; display: inline-block;" /> %
                </div>
            </div>

            <div class="panel-footer">
                <button type="submit" name="submitWyprzedazSettings" class="btn btn-default pull-right">
                    <i class="process-icon-save"></i> {l s='Zapisz ustawienia' mod='wyprzedaz'}
                </button>
            </div>
        </fieldset>
    </form>
</div>

{* Formularz do wysyłania pliku CSV *}
<div class="panel">
    <form action="{$current|escape:'html':'UTF-8'}&token={$token|escape:'html':'UTF-8'}" method="post" enctype="multipart/form-data" class="form-horizontal">
        <fieldset>
            <legend>{l s='Import danych z pliku CSV' mod='wyprzedaz'}</legend>
            <div class="panel-body">
                <div class="alert alert-info">
                    {l s='Wybierz plik CSV z danymi do importu. Plik powinien zawierać kolumny: EAN, STAN, DATA PRZYJĘCIA, DATA WAŻNOŚCI, Regał, Półka.' mod='wyprzedaz'}
                </div>
                <div class="form-group">
                    <label class="control-label col-lg-3">{l s='Plik CSV' mod='wyprzedaz'}</label>
                    <div class="col-lg-9">
                        <input type="file" name="csv_file" id="csv_file" class="form-control" />
                    </div>
                </div>
            </div>
            <div class="panel-footer">
                <a href="{$base_dir|escape:'html':'UTF-8'}modules/wyprzedaz/views/templates/admin/wyprzedaz.csv" class="btn btn-default pull-left" download="wyprzedaz.csv">
                    <i class="process-icon-download"></i> {l s='Pobierz przykładowy plik' mod='wyprzedaz'}
                </a>

                <button type="button" id="wyprzedaz_start" class="btn btn-primary pull-right" style="margin-left:8px">
                    <i class="process-icon-upload"></i> {l s='Import (bez timeoutu)' mod='wyprzedaz'}
                </button>
                <button type="button" id="wyprzedaz_cancel" class="btn btn-warning pull-right" style="display:none">
                    <i class="icon-ban-circle"></i> {l s='Przerwij' mod='wyprzedaz'}
                </button>

                <div class="clearfix"></div>
            </div>
        </fieldset>
    </form>
</div>

{* Pasek postępu importu AJAX *}
<div id="wyprzedaz-progress-wrapper" style="display:none;margin:10px 0 20px">
  <div id="wyprzedaz-progress-stage" style="margin-bottom:6px"><strong>{l s='Etap:' mod='wyprzedaz'}</strong> –</div>
  <div class="progress">
    <div id="wyprzedaz-progress" class="progress-bar" role="progressbar"
         aria-valuemin="0" aria-valuemax="100" style="width:0%">0%</div>
  </div>
  <div id="wyprzedaz-progress-stats" style="margin-top:6px;color:#444;font-size:12px"></div>
</div>

{* HISTORIA IMPORTÓW *}
<div class="panel">
  <div class="panel-heading">
    <i class="icon-time"></i> {l s='Historia importów CSV' mod='wyprzedaz'}
  </div>
  <div class="table-scrollable-container">
    <table class="table">
      <thead>
        <tr>
          <th>{l s='Data' mod='wyprzedaz'}</th>
          <th>{l s='Plik' mod='wyprzedaz'}</th>
          <th>{l s='Wiersze w pliku' mod='wyprzedaz'}</th>
          <th>{l s='Znaleziono w bazie' mod='wyprzedaz'}</th>
          <th>{l s='Nie znaleziono' mod='wyprzedaz'}</th>
          <th>{l s='Pracownik ID' mod='wyprzedaz'}</th>
        </tr>
      </thead>
      <tbody>
        {if isset($import_history) && $import_history}
          {foreach from=$import_history item=it}
            {* POPRAWKA: Zmieniono nazwy zmiennych, aby pasowały do danych z bazy *}
            <tr>
              <td>{$it.date_add|escape:'html':'UTF-8'}</td>
              <td>{$it.filename|escape:'html':'UTF-8'}</td>
              <td>{$it.rows_total|intval}</td>
              <td>{$it.rows_in_db|intval}</td>
              <td>{$it.rows_not_found|intval}</td>
              <td>{$it.id_employee|intval}</td>
            </tr>
          {/foreach}
        {else}
          <tr>
            <td colspan="6" class="text-center text-muted">{l s='Brak historii importów.' mod='wyprzedaz'}</td>
          </tr>
        {/if}
      </tbody>
    </table>
  </div>
</div>

{* Informacje o produktach *}
{if $expired_products_count > 0}
    <div class="alert alert-warning">
        <i class="icon-warning-sign"></i>
        {l s='Na magazynie znajduje się' mod='wyprzedaz'} <strong>{$expired_products_count}</strong> {l s='produkt(ów) po terminie ważności.' mod='wyprzedaz'}
        <a href="{$current|escape:'html':'UTF-8'}&token={$token|escape:'html':'UTF-8'}&controller=AdminWyprzedazManager&date_filter=expired" class="btn btn-warning btn-sm pull-right">{l s='Pokaż te produkty' mod='wyprzedaz'}</a>
        <div class="clearfix"></div>
    </div>
{/if}

{if $short_date_products_count > 0}
    <div class="alert alert-info">
        <i class="icon-info-sign"></i>
        {l s='Na magazynie znajduje się' mod='wyprzedaz'} <strong>{$short_date_products_count}</strong> {l s='produkt(ów) z krótką datą ważności.' mod='wyprzedaz'}
        <a href="{$current|escape:'html':'UTF-8'}&token={$token|escape:'html':'UTF-8'}&controller=AdminWyprzedazManager&date_filter=short" class="btn btn-info btn-sm pull-right">{l s='Pokaż te produkty' mod='wyprzedaz'}</a>
        <div class="clearfix"></div>
    </div>
{/if}


{* TABELA GŁÓWNA - POKAZUJE ZSUMOWANE PRODUKTY *}
<div class="panel">
    <div class="panel-heading">
        <i class="icon-list"></i> {l s='Produkty wyprzedażowe' mod='wyprzedaz'}
        <div class="btn-group pull-right">
            {if $date_filter && $date_filter != 'all'}
                <a href="{$current|escape:'html':'UTF-8'}&token={$token|escape:'html':'UTF-8'}&controller=AdminWyprzedazManager&date_filter=all" class="btn btn-default btn-sm wyprzedaz-btn-clear">
                    <i class="icon-th-list"></i> {l s='Pokaż wszystko' mod='wyprzedaz'}
                </a>
            {/if}
            <a href="{$current|escape:'html':'UTF-8'}&token={$token|escape:'html':'UTF-8'}&controller=AdminWyprzedazManager&date_filter=expired" class="btn btn-sm wyprzedaz-btn-expired {if $date_filter == 'expired'}active{/if}">
                <i class="icon-calendar-times-o"></i> {l s='Po terminie' mod='wyprzedaz'} ({$expired_products_count})
            </a>
            <a href="{$current|escape:'html':'UTF-8'}&token={$token|escape:'html':'UTF-8'}&controller=AdminWyprzedazManager&date_filter=short" class="btn btn-sm wyprzedaz-btn-short {if $date_filter == 'short'}active{/if}">
                <i class="icon-calendar"></i> {l s='Krótka data' mod='wyprzedaz'} ({$short_date_products_count})
            </a>
            <a href="{$current|escape:'html':'UTF-8'}&token={$token|escape:'html':'UTF-8'}&controller=AdminWyprzedazManager&date_filter=30" class="btn btn-sm wyprzedaz-btn-30 {if $date_filter == '30'}active{/if}">
                 <i class="icon-calendar-check-o"></i> {l s='< 30 dni od przyjęcia' mod='wyprzedaz'} ({$products_30_days_count})
            </a>
            <a href="{$current|escape:'html':'UTF-8'}&token={$token|escape:'html':'UTF-8'}&controller=AdminWyprzedazManager&date_filter=90" class="btn btn-sm wyprzedaz-btn-90 {if $date_filter == '90'}active{/if}">
                <i class="icon-calendar-o"></i> {l s='31-90 dni od przyjęcia' mod='wyprzedaz'} ({$products_31_90_days_count})
            </a>
            <a href="{$current|escape:'html':'UTF-8'}&token={$token|escape:'html':'UTF-8'}&controller=AdminWyprzedazManager&date_filter=over_90" class="btn btn-sm wyprzedaz-btn-over-90 {if $date_filter == 'over_90'}active{/if}">
                <i class="icon-calendar-minus-o"></i> {l s='> 90 dni od przyjęcia' mod='wyprzedaz'} ({$products_over_90_days_count})
            </a>
        </div>
        <div class="clearfix"></div>
    </div>
    <div class="table-scrollable-container">
    <table class="table">
        <thead>
            <tr>
                <th>{l s='Nazwa produktu' mod='wyprzedaz'}</th>
                <th><a href="{$current}&token={$token}&sort=ean&way={if $sort == 'ean' && $way == 'asc'}desc{else}asc{/if}">{l s='EAN' mod='wyprzedaz'}</a></th>
                <th><a href="{$current}&token={$token}&sort=reference&way={if $sort == 'reference' && $way == 'asc'}desc{else}asc{/if}">{l s='SKU' mod='wyprzedaz'}</a></th>
                <th><a href="{$current}&token={$token}&sort=quantity&way={if $sort == 'quantity' && $way == 'asc'}desc{else}asc{/if}">{l s='Stan' mod='wyprzedaz'}</a></th>
                <th><a href="{$current}&token={$token}&sort=discount&way={if $sort == 'discount' && $way == 'asc'}desc{else}asc{/if}">{l s='Rabat %' mod='wyprzedaz'}</a></th>
                <th><a href="{$current}&token={$token}&sort=regal&way={if $sort == 'regal' && $way == 'asc'}desc{else}asc{/if}">{l s='Regał' mod='wyprzedaz'}</a></th>
                <th><a href="{$current}&token={$token}&sort=polka&way={if $sort == 'polka' && $way == 'asc'}desc{else}asc{/if}">{l s='Półka' mod='wyprzedaz'}</a></th>
                <th>{l s='Data przyjęcia' mod='wyprzedaz'}</th>
                <th><a href="{$current}&token={$token}&sort=expiry&way={if $sort == 'expiry' && $way == 'asc'}desc{else}asc{/if}">{l s='Data ważności' mod='wyprzedaz'}</a></th>
                <th>{l s='Akcje' mod='wyprzedaz'}</th>
            </tr>
        </thead>
        <tbody>
            {foreach from=$sale_products item=product}
                <tr {if $product.status == 'expired'}class="danger"{elseif $product.status == 'short_date'}class="warning"{/if}>
                    <td>{$product.name|truncate:50:'...'}</td>
                    <td>{if isset($product.ean13)}{$product.ean13}{else}–{/if}</td>
                    <td>{$product.reference}</td>
                    <td>{$product.quantity}</td>
                    <td>{if $product.reduction > 0}{($product.reduction * 100)|round:0}%{else}–{/if}</td>
                    <td>{$product.regal|default:'–'}</td>
                    <td>{$product.polka|default:'–'}</td>
                    <td>{if $product.receipt_date && $product.receipt_date != '0000-00-00'}{$product.receipt_date|date_format:"%d.%m.%Y"}{else}–{/if}</td>
                    <td>{if $product.expiry_date && $product.expiry_date != '0000-00-00'}{$product.expiry_date|date_format:"%d.%m.%Y"}{else}–{/if}</td>
                    <td><a href="{$product.product_url}" target="_blank" class="btn btn-sm btn-default"><i class="icon-eye"></i> {l s='Zobacz' mod='wyprzedaz'}</a></td>
                </tr>
            {foreachelse}
                <tr>
                    <td colspan="10" class="text-center">{l s='Brak produktów w wyprzedaży.' mod='wyprzedaz'}</td>
                </tr>
            {/foreach}
        </tbody>
    </table>
    </div>
</div>

{* DODATKOWA TABELA DLA DUPLIKATÓW *}
<div class="panel">
    <div class="panel-heading">
        <i class="icon-warning"></i> {l s='Produkty zduplikowane na magazynie' mod='wyprzedaz'} ({$duplicate_products_count})
        <p class="help-block">{l s='Poniżej znajdują się produkty o tym samym EAN i dacie ważności, ale w różnych lokalizacjach. Wiersze o tym samym kolorze tła stanowią duplikaty.' mod='wyprzedaz'}</p>
    </div>
    <div class="table-scrollable-container">
    <table class="table">
        <thead>
            <tr>
                <th>{l s='Nazwa produktu' mod='wyprzedaz'}</th>
                <th><a href="{$current}&token={$token}&sort_duplicates=ean&way_duplicates={if $sort_duplicates == 'ean' && $way_duplicates == 'asc'}desc{else}asc{/if}">{l s='EAN' mod='wyprzedaz'}</a></th>
                <th><a href="{$current}&token={$token}&sort_duplicates=reference&way_duplicates={if $sort_duplicates == 'reference' && $way_duplicates == 'asc'}desc{else}asc{/if}">{l s='SKU hurt' mod='wyprzedaz'}</a></th>
                <th><a href="{$current}&token={$token}&sort_duplicates=quantity&way_duplicates={if $sort_duplicates == 'quantity' && $way_duplicates == 'asc'}desc{else}asc{/if}">{l s='Stan' mod='wyprzedaz'}</a></th>
                <th>{l s='Rabat %' mod='wyprzedaz'}</th>
                <th><a href="{$current}&token={$token}&sort_duplicates=regal&way_duplicates={if $sort_duplicates == 'regal' && $way_duplicates == 'asc'}desc{else}asc{/if}">{l s='Regał' mod='wyprzedaz'}</a></th>
                <th><a href="{$current}&token={$token}&sort_duplicates=polka&way_duplicates={if $sort_duplicates == 'polka' && $way_duplicates == 'asc'}desc{else}asc{/if}">{l s='Półka' mod='wyprzedaz'}</a></th>
                <th><a href="{$current}&token={$token}&sort_duplicates=receipt_date&way_duplicates={if $sort_duplicates == 'receipt_date' && $way_duplicates == 'asc'}desc{else}asc{/if}">{l s='Data przyjęcia' mod='wyprzedaz'}</a></th>
                <th><a href="{$current}&token={$token}&sort_duplicates=expiry_date&way_duplicates={if $sort_duplicates == 'expiry_date' && $way_duplicates == 'asc'}desc{else}asc{/if}">{l s='Data ważności' mod='wyprzedaz'}</a></th>
                <th>{l s='Akcje' mod='wyprzedaz'}</th>
            </tr>
        </thead>
        <tbody>
            {foreach from=$duplicated_products item=product}
                <tr class="{$product.row_class|default:''}">
                    <td>{$product.name|truncate:50:'...'}</td>
                    <td>{if isset($product.ean)}{$product.ean}{else}–{/if}</td>
                    <td>{$product.reference}</td>
                    <td>{$product.quantity}</td>
                    <td>{if $product.reduction > 0}{($product.reduction * 100)|round:0}%{else}–{/if}</td>
                    <td>{$product.regal|default:'–'}</td>
                    <td>{$product.polka|default:'–'}</td>
                    <td>{if $product.receipt_date && $product.receipt_date != '0000-00-00'}{$product.receipt_date|date_format:"%d.%m.%Y"}{else}–{/if}</td>
                    <td>{if $product.expiry_date && $product.expiry_date != '0000-00-00'}{$product.expiry_date|date_format:"%d.%m.%Y"}{else}–{/if}</td>
                    <td><a href="{$product.product_url}" target="_blank" class="btn btn-sm btn-default"><i class="icon-eye"></i> {l s='Zobacz' mod='wyprzedaz'}</a></td>
                </tr>
            {foreachelse}
                <tr>
                    <td colspan="10" class="text-center">{l s='Brak duplikatów.' mod='wyprzedaz'}</td>
                </tr>
            {/foreach}
        </tbody>
    </table>
    </div>
</div>

{* NOWA TABELA DLA BRAKUJĄCYCH PRODUKTÓW *}
<div class="panel">
    <div class="panel-heading">
        <i class="icon-list-alt"></i> {l s='Brakujące produkty w bazie' mod='wyprzedaz'} ({$not_found_products_count})
    <a href="{$current}&token={$token}&export_not_found=1" class="btn btn-default btn-sm pull-right">
      <i class="icon-download"></i> {l s='Pobierz EAN-y (CSV)' mod='wyprzedaz'}
    </a>
         <p class="help-block">{l s='Poniżej znajduje się lista produktów z pliku CSV, których nie ma w bazie danych. Możesz je utworzyć, aby później uzupełnić ich dane.' mod='wyprzedaz'}</p>
    </div>
    <div class="table-scrollable-container">
    <table class="table">
        <thead>
            <tr>
                <th><a href="{$current}&token={$token}&sort_not_found=ean&way_not_found={if $sort_not_found == 'ean' && $way_not_found == 'asc'}desc{else}asc{/if}">{l s='EAN' mod='wyprzedaz'}</a></th>
                <th><a href="{$current}&token={$token}&sort_not_found=quantity&way_not_found={if $sort_not_found == 'quantity' && $way_not_found == 'asc'}desc{else}asc{/if}">{l s='Stan' mod='wyprzedaz'}</a></th>
                <th><a href="{$current}&token={$token}&sort_not_found=regal&way_not_found={if $sort_not_found == 'regal' && $way_not_found == 'asc'}desc{else}asc{/if}">{l s='Regał' mod='wyprzedaz'}</a></th>
                <th><a href="{$current}&token={$token}&sort_not_found=polka&way_not_found={if $sort_not_found == 'polka' && $way_not_found == 'asc'}desc{else}asc{/if}">{l s='Półka' mod='wyprzedaz'}</a></th>
                <th><a href="{$current}&token={$token}&sort_not_found=receipt_date&way_not_found={if $sort_not_found == 'receipt_date' && $way_not_found == 'asc'}desc{else}asc{/if}">{l s='Data przyjęcia' mod='wyprzedaz'}</a></th>
                <th><a href="{$current}&token={$token}&sort_not_found=expiry_date&way_not_found={if $sort_not_found == 'expiry_date' && $way_not_found == 'asc'}desc{else}asc{/if}">{l s='Data ważności' mod='wyprzedaz'}</a></th>
                <th>{l s='Akcje' mod='wyprzedaz'}</th>
            </tr>
        </thead>
        <tbody>
            {foreach from=$not_found_products item=product}
                <tr>
                    <td>{$product.ean|default:'–'}</td>
                    <td>{$product.quantity|default:'–'}</td>
                    <td>{$product.regal|default:'–'}</td>
                    <td>{$product.polka|default:'–'}</td>
                    <td>{if $product.receipt_date && $product.receipt_date != '0000-00-00'}{$product.receipt_date|date_format:"%d.%m.%Y"}{else}–{/if}</td>
                    <td>{if $product.expiry_date && $product.expiry_date != '0000-00-00'}{$product.expiry_date|date_format:"%d.%m.%Y"}{else}–{/if}</td>
                    <td><a href="{$link->getAdminLink('AdminProducts')|escape:'htmlall':'UTF-8'}&addproduct&ean13={$product.ean|escape:'htmlall':'UTF-8'}" class="btn btn-sm btn-primary"><i class="icon-plus"></i> {l s='Utwórz produkt' mod='wyprzedaz'}</a></td>
                </tr>
            {foreachelse}
                <tr>
                    <td colspan="7" class="text-center">{l s='Brak produktów, których nie znaleziono w bazie.' mod='wyprzedaz'}</td>
                </tr>
            {/foreach}
        </tbody>
    </table>
    </div>
</div>