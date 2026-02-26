{* /modules/dietamamyto/views/templates/admin/steps/step2.tpl *}
<div class="panel" id="dmto-step2">
  <div class="panel-heading">
    <i class="icon-sitemap"></i> {l s='Krok 2: Automatyczna klasyfikacja typów produktów' mod='dietamamyto'}
  </div>

  <div class="alert alert-info">
    <p>{l s='Przypisuje cechę "Rodzaj produktu" na podstawie ścieżki kategorii (wg trybu i głębokości).' mod='dietamamyto'}</p>
    <p>{l s='Pominięte będą produkty, które już mają przypisaną tę cechę, chyba że włączysz tryb force.' mod='dietamamyto'}</p>
  </div>

  <form method="post" action="{$__form_action|escape:'html':'UTF-8'}" class="form-horizontal" id="dmto-step2-form">
    <input type="hidden" name="saveTypeSettings" value="1" />

    <div class="form-group">
      <label class="control-label col-lg-3" for="dmto_type_depth">{l s='Głębokość wyboru kategorii' mod='dietamamyto'}</label>
      <div class="col-lg-9">
        <div class="row">
          <div class="col-lg-3">
            <select name="dmto_type_depth" id="dmto_type_depth" class="form-control fixed-width-sm">
              {for $i=1 to 6}
                <option value="{$i}" {if $dmto_type_depth|intval == $i || ($dmto_type_depth|intval < 1 && $i == 1)}selected="selected"{/if}>{$i}</option>
              {/for}
            </select>
          </div>
          <div class="col-lg-9">
            <p class="help-block">{l s='Tryb "liść": 1 = liść; Tryb "root": 1 = najbliżej roota (bez Home).' mod='dietamamyto'}</p>
          </div>
        </div>
      </div>
    </div>

    <div class="form-group">
      <label class="control-label col-lg-3" for="dmto_depth_mode">{l s='Tryb' mod='dietamamyto'}</label>
      <div class="col-lg-9">
        <select name="dmto_depth_mode" id="dmto_depth_mode" class="form-control">
          <option value="leaf" {if $dmto_depth_mode == 'leaf'}selected="selected"{/if}>{l s='liść (od dołu)' mod='dietamamyto'}</option>
          <option value="root" {if $dmto_depth_mode == 'root'}selected="selected"{/if}>{l s='root (od góry)' mod='dietamamyto'}</option>
        </select>
      </div>
    </div>

    <div class="form-group">
      <label class="control-label col-lg-3" for="dmto_auto_reindex">{l s='Automatyczny reindeks ps_facetedsearch' mod='dietamamyto'}</label>
      <div class="col-lg-9">
        <span class="switch prestashop-switch fixed-width-lg">
          <input type="radio" name="dmto_auto_reindex" id="dmto_auto_reindex_on" value="1" {if $dmto_auto_reindex}checked="checked"{/if} />
          <label for="dmto_auto_reindex_on">{l s='Tak' mod='dietamamyto'}</label>
          <input type="radio" name="dmto_auto_reindex" id="dmto_auto_reindex_off" value="0" {if !$dmto_auto_reindex}checked="checked"{/if} />
          <label for="dmto_auto_reindex_off">{l s='Nie' mod='dietamamyto'}</label>
          <a class="slide-button btn"></a>
        </span>
      </div>
    </div>

    <div class="form-group">
      <label class="control-label col-lg-3" for="dmto_force_rebuild">{l s='Przelicz wszystkie produkty (force)' mod='dietamamyto'}</label>
      <div class="col-lg-9">
        <span class="switch prestashop-switch fixed-width-lg">
          <input type="radio" name="dmto_force_rebuild" id="dmto_force_rebuild_on" value="1" {if $dmto_force_rebuild}checked="checked"{/if} />
          <label for="dmto_force_rebuild_on">{l s='Tak' mod='dietamamyto'}</label>
          <input type="radio" name="dmto_force_rebuild" id="dmto_force_rebuild_off" value="0" {if !$dmto_force_rebuild}checked="checked"{/if} />
          <label for="dmto_force_rebuild_off">{l s='Nie (tylko brakujące)' mod='dietamamyto'}</label>
          <a class="slide-button btn"></a>
        </span>
      </div>
    </div>
    
    <div class="form-group">
      <label class="control-label col-lg-3" for="dmto_cleanup_unused">{l s='Kasuj nieużywane wartości' mod='dietamamyto'}</label>
      <div class="col-lg-9">
        <span class="switch prestashop-switch fixed-width-lg">
          <input type="radio" name="dmto_cleanup_unused" id="dmto_cleanup_unused_on" value="1" {if $dmto_cleanup_unused}checked="checked"{/if} />
          <label for="dmto_cleanup_unused_on">{l s='Tak' mod='dietamamyto'}</label>
          <input type="radio" name="dmto_cleanup_unused" id="dmto_cleanup_unused_off" value="0" {if !$dmto_cleanup_unused}checked="checked"{/if} />
          <label for="dmto_cleanup_unused_off">{l s='Nie' mod='dietamamyto'}</label>
          <a class="slide-button btn"></a>
        </span>
        <p class="help-block">{l s='Po zakończeniu klasyfikacji usuwa wartości cechy "Rodzaj produktu", które nie są przypisane do żadnego produktu.' mod='dietamamyto'}</p>
      </div>
    </div>

    <div class="panel-footer">
      <button type="button" id="dmto-step2-start-batch" class="btn btn-warning">
        <i class="icon-play"></i> {l s='Uruchom klasyfikację (partiami z paskiem postępu)' mod='dietamamyto'}
      </button>

      <button type="submit" name="submitAssignProductTypes" class="btn btn-primary pull-right">
        <i class="process-icon-cogs"></i> {l s='Uruchom klasyfikację (pełny bieg)' mod='dietamamyto'}
      </button>

      <button type="submit" name="saveTypeSettings" class="btn btn-default">
        <i class="icon-save"></i> {l s='Zapisz ustawienia' mod='dietamamyto'}
      </button>
    </div>
  </form>

  <div id="dmto-step2-progress" style="display:none;margin-top:10px; padding: 0 15px 15px 15px;">
    <div class="progress">
      <div id="dmto-step2-bar" class="progress-bar progress-bar-striped active" role="progressbar" style="width:0%">0%</div>
    </div>
    <p id="dmto-step2-status" class="help-block"></p>
    <div id="dmto-step2-details" class="help-block" style="font-size: 12px; color: #555;"></div>
    <div id="dmto-step2-errors" class="help-block" style="font-size: 12px; color: #c7254e; margin-top: 10px;"></div>
  </div>

  <div class="panel-footer">
    <h4><i class="icon-list-ul"></i> {l s='Istniejące wartości cechy "Rodzaj produktu"' mod='dietamamyto'}</h4>
    {if isset($product_type_summary) && $product_type_summary|@count > 0}
      <div style="max-height: 400px; overflow-y: auto;">
        <table class="table table-bordered table-striped">
          <thead>
            <tr>
              <th>{l s='Nazwa wartości (Rodzaj produktu)' mod='dietamamyto'}</th>
              <th class="text-center" style="width: 150px;">{l s='Liczba produktów' mod='dietamamyto'}</th>
            </tr>
          </thead>
          <tbody>
            {foreach from=$product_type_summary item=row}
              <tr>
                <td>{$row.value|escape:'html':'UTF-8'}</td>
                <td class="text-center">{$row.product_count|intval}</td>
              </tr>
            {/foreach}
          </tbody>
        </table>
      </div>
    {else}
      <div class="alert alert-info">
        {l s='Brak utworzonych wartości dla cechy "Rodzaj produktu" lub cecha nie została jeszcze utworzona.' mod='dietamamyto'}
      </div>
    {/if}
  </div>
</div>

<script src="{$module_uri}views/js/dmto_step2_progress.js"></script>
<script>
window.DMTO_STEP2 = {
  ajax: '{$ajax_link|escape:'html':'UTF-8'}',
  token: '{$token|escape:'html':'UTF-8'}',
  cleanupEnabled: {if $dmto_cleanup_unused}1{else}0{/if}
};
</script>