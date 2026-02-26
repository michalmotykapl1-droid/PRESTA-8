<div class="panel">
  <div class="panel-heading">
    <i class="icon-time"></i> {l s='Historia importów' mod='wyprzedazpro'}
  </div>
  
  {* KONTENER PRZEWIJANIA: max-height ustawione na ok. 5 wierszy *}
  <div class="table-responsive" style="max-height: 250px; overflow-y: auto; border: 1px solid #eee;">
    <table class="table" style="margin-bottom: 0;">
      <thead>
        <tr>
          {* Sticky header - nagłówek przyklejony przy przewijaniu *}
          <th style="position: sticky; top: 0; background: #f8f8f8; z-index: 10;">{l s='Data' mod='wyprzedazpro'}</th>
          <th style="position: sticky; top: 0; background: #f8f8f8; z-index: 10;">{l s='Plik' mod='wyprzedazpro'}</th>
          <th style="position: sticky; top: 0; background: #f8f8f8; z-index: 10;">{l s='Wiersze' mod='wyprzedazpro'}</th>
          <th style="position: sticky; top: 0; background: #f8f8f8; z-index: 10;">{l s='W bazie' mod='wyprzedazpro'}</th>
          <th style="position: sticky; top: 0; background: #f8f8f8; z-index: 10;">{l s='Brak' mod='wyprzedazpro'}</th>
          <th style="position: sticky; top: 0; background: #f8f8f8; z-index: 10;">{l s='ID Prac.' mod='wyprzedazpro'}</th>
        </tr>
      </thead>
      <tbody>
        {if isset($import_history) && $import_history}
          {foreach from=$import_history item=it}
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
          <tr><td colspan="6" class="text-center text-muted">{l s='Brak historii.' mod='wyprzedazpro'}</td></tr>
        {/if}
      </tbody>
    </table>
  </div>
</div>