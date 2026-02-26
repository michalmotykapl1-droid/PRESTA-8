{* /modules/dietamamyto/views/templates/admin/steps/top_stats.tpl *}

<style>
{literal}
.dmto-stats .stat-box{border:1px solid #e5e5e5;border-radius:6px;padding:10px 12px;margin-bottom:12px;background:#fff}
.dmto-stats .stat-box .stat-value{font-size:20px;font-weight:700;line-height:1.1}
.dmto-stats .stat-box .stat-label{font-size:12px;color:#666; height: 30px;}
.dmto-stats .stat-box.stat-warn{background:#fff8e6;border-color:#f7dca7}
.dmto-stats .dmto-scroll{max-height:360px;overflow:auto}
.dmto-stats .m-b-10{margin-bottom:10px}
.dmto-stats h4 { margin-top: 20px; border-bottom: 1px solid #ddd; padding-bottom: 5px; }
{/literal}
</style>

<div class="panel dmto-stats">
  <div class="panel-heading"><i class="icon-bar-chart"></i> Statystyki</div>
  <div class="panel-body">

    {* SEKCJA 1: WSZYSTKIE PRODUKTY *}
    <h4>{l s='Statystyki dla WSZYSTKICH produktów' mod='dietamamyto'}</h4>
    <div class="row">
      <div class="col-lg-2 col-md-3 col-sm-4">
        <div class="stat-box">
          <div class="stat-value">{$stats.all_products.total|intval}</div>
          <div class="stat-label">{l s='Wszystkie produkty' mod='dietamamyto'}</div>
        </div>
      </div>
      {foreach from=$stats.all_products.per_diet item=diet}
        <div class="col-lg-2 col-md-3 col-sm-4">
          <div class="stat-box">
            <div class="stat-value">{$diet.count|intval}</div>
            <div class="stat-label">{$diet.label|escape:'html':'UTF-8'}</div>
          </div>
        </div>
      {/foreach}
      <div class="col-lg-2 col-md-3 col-sm-4">
        <div class="stat-box stat-warn">
          <div class="stat-value">{$stats.all_products.undieted_count|intval}</div>
          <div class="stat-label">{l s='Bez diety' mod='dietamamyto'}</div>
        </div>
      </div>
    </div>

    {* SEKCJA 2: AKTYWNE PRODUKTY W MAGAZYNIE *}
    <hr>
    <h4>{l s='Statystyki dla produktów AKTYWNYCH (stan > 0)' mod='dietamamyto'}</h4>
    <div class="row">
      <div class="col-lg-2 col-md-3 col-sm-4">
        <div class="stat-box">
          <div class="stat-value">{$stats.active_in_stock.total|intval}</div>
          <div class="stat-label">{l s='Aktywne w magazynie' mod='dietamamyto'}</div>
        </div>
      </div>
      {foreach from=$stats.active_in_stock.per_diet item=diet}
        <div class="col-lg-2 col-md-3 col-sm-4">
          <div class="stat-box">
            <div class="stat-value">{$diet.count|intval}</div>
            <div class="stat-label">{$diet.label|escape:'html':'UTF-8'}</div>
          </div>
        </div>
      {/foreach}
       <div class="col-lg-2 col-md-3 col-sm-4">
        <div class="stat-box stat-warn">
          <div class="stat-value">{$stats.active_in_stock.undieted_count|intval}</div>
          <div class="stat-label">{l s='Bez diety' mod='dietamamyto'}</div>
        </div>
      </div>
    </div>

    <hr>
    <h4 class="m-b-10"><i class="icon-warning-sign"></i> {l s='Aktywne produkty bez przypisanych diet (top 100)' mod='dietamamyto'}</h4>
    <div class="table-responsive dmto-scroll">
      <table class="table">
        <thead>
          <tr>
            <th style="width:90px">{l s='ID' mod='dietamamyto'}</th>
            <th>{l s='Nazwa' mod='dietamamyto'}</th>
            <th style="width:180px">{l s='SKU' mod='dietamamyto'}</th>
            <th style="width:120px"></th>
          </tr>
        </thead>
        <tbody>
          {if !empty($stats.undieted_list)}
            {foreach from=$stats.undieted_list item=row}
            <tr>
              <td>{$row.id_product|intval}</td>
              <td>{$row.name|escape:'html':'UTF-8'}</td>
              <td>{$row.reference|escape:'html':'UTF-8'}</td>
              <td class="text-right">
                {if isset($row.edit_url)}
                <a class="btn btn-xs btn-default" href="{$row.edit_url|escape:'html':'UTF-8'}" target="_blank">
                  <i class="icon-pencil"></i> {l s='Edytuj' mod='dietamamyto'}
                </a>
                {/if}
              </td>
            </tr>
            {/foreach}
          {else}
            <tr><td colspan="4" class="text-center text-muted">{l s='Wszystkie aktywne produkty mają przypisane diety.' mod='dietamamyto'}</td></tr>
          {/if}
        </tbody>
      </table>
    </div>
  </div>
</div>