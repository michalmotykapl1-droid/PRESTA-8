{* /modules/dietamamyto/views/templates/admin/steps/step3.tpl *}
<div class="panel">
  <div class="panel-heading">
    <i class="icon-tags"></i> {l s='Krok 3: Przypisanie do kategorii dietetycznych' mod='dietamamyto'}
  </div>

  <div class="alert alert-info">
    <p>{l s='Tworzy/uzupełnia gałęzie kategorii dietetycznych i przypina produkty na podstawie cech (Krok 1) oraz wybranego trybu.' mod='dietamamyto'}</p>
  </div>

  <form action="{$__form_action|escape:'html':'UTF-8'}" method="post" class="form-horizontal" id="dmto-step3-form">
    <input type="hidden" name="saveTreeSettings" value="1" />
    <div class="form-group">
      <label class="control-label col-lg-3" for="dmto_tree_use_type_cap">{l s='Użyj „Rodzaj produktu” jako gałęzi' mod='dietamamyto'}</label>
      <div class="col-lg-9">
        <span class="switch prestashop-switch fixed-width-lg">
          <input type="radio" name="dmto_tree_use_type_cap" id="dmto_tree_use_type_cap_on" value="1" {if $dmto_tree_use_type_cap}checked="checked"{/if} />
          <label for="dmto_tree_use_type_cap_on">{l s='Tak' mod='dietamamyto'}</label>
          <input type="radio" name="dmto_tree_use_type_cap" id="dmto_tree_use_type_cap_off" value="0" {if !$dmto_tree_use_type_cap}checked="checked"{/if} />
          <label for="dmto_tree_use_type_cap_off">{l s='Nie' mod='dietamamyto'}</label>
          <a class="slide-button btn"></a>
        </span>
        <p class="help-block">{l s='Jeśli "Tak", struktura drzewa będzie budowana na podstawie cechy "Rodzaj produktu" (Krok 2). Jeśli "Nie", na podstawie oryginalnej ścieżki kategorii.' mod='dietamamyto'}</p>
      </div>
    </div>
    <div class="form-group">
      <label class="control-label col-lg-3" for="dmto_tree_depth">{l s='Głębokość drzewa (dla trybu "Nie")' mod='dietamamyto'}</label>
      <div class="col-lg-9">
        <div class="row">
          <div class="col-lg-3">
            <select name="dmto_tree_depth" id="dmto_tree_depth" class="form-control fixed-width-sm">
              {for $i=1 to 6}
                 {* ⭐ POPRAWIONA LINIA - usunięto *}
                 <option value="{$i}" {if $tree_depth|intval == $i || ($tree_depth|intval < 1 && $i == 1)}selected="selected"{/if}>{$i}</option>
              {/for}
            </select>
          </div>
        </div>
      </div>
    </div>

    {* ⭐ POLE TRYBU DODANE WCZEŚNIEJ ⭐ *}
    <div class="form-group">
      <label class="control-label col-lg-3" for="dmto_tree_depth_mode">{l s='Tryb (dla trybu "Nie")' mod='dietamamyto'}</label>
      <div class="col-lg-9">
        <select name="dmto_tree_depth_mode" id="dmto_tree_depth_mode" class="form-control">
          <option value="root" {if $dmto_tree_depth_mode == 'root'}selected="selected"{/if}>{l s='root (od góry)' mod='dietamamyto'}</option>
          <option value="leaf" {if $dmto_tree_depth_mode == 'leaf'}selected="selected"{/if}>{l s='leaf (od dołu)' mod='dietamamyto'}</option>
        </select>
         <p class="help-block">
             <b>{l s='root (od góry)' mod='dietamamyto'}</b>: {l s='`Głębokość=2` dla ścieżki `Prod. spożywcze > Cukier > Stewia` da `... > Prod. spożywcze > Cukier`.' mod='dietamamyto'}<br>
             <b>{l s='leaf (od dołu)' mod='dietamamyto'}</b>: {l s='`Głębokość=1` dla tej samej ścieżki da `... > Stewia`.' mod='dietamamyto'}
         </p>
      </div>
    </div>
    {* ⭐ KONIEC POLA TRYBU ⭐ *}

    <div class="form-group">
      <label class="control-label col-lg-3" for="dmto_diet_root_ids">{l s='ID kategorii dietetycznych (rooty)' mod='dietamamyto'}</label>
      <div class="col-lg-9">
        <input type="text" name="dmto_diet_root_ids" id="dmto_diet_root_ids"
               value="{$diet_root_ids|escape:'html':'UTF-8'}" class="form-control" placeholder="{l s='np. 168,169,170 – lub puste (auto)' mod='dietamamyto'}" />
      </div>
    </div>
    <div class="form-group">
      <label class="control-label col-lg-3" for="dmto_tree_force">{l s='Wyczyść i przelicz od nowa (force)' mod='dietamamyto'}</label>
      <div class="col-lg-9">
        <span class="switch prestashop-switch fixed-width-lg">
          <input type="radio" name="dmto_tree_force" id="dmto_tree_force_on" value="1" {if $dmto_tree_force}checked="checked"{/if} />
          <label for="dmto_tree_force_on">{l s='Tak' mod='dietamamyto'}</label>
          <input type="radio" name="dmto_tree_force" id="dmto_tree_force_off" value="0" {if !$dmto_tree_force}checked="checked"{/if} />
          <label for="dmto_tree_force_off">{l s='Nie' mod='dietamamyto'}</label>
          <a class="slide-button btn"></a>
        </span>
      </div>
    </div>
    <div class="form-group">
      <label class="control-label col-lg-3">{l s='Kasuj puste kategorie' mod='dietamamyto'}</label>
      <div class="col-lg-9">
        <span class="switch prestashop-switch fixed-width-lg">
          <input type="radio" name="dmto_tree_cleanup_unused" id="dmto_tree_cleanup_unused_on" value="1" {if $dmto_tree_cleanup_unused}checked="checked"{/if} />
          <label for="dmto_tree_cleanup_unused_on">{l s='Tak' mod='dietamamyto'}</label>
          <input type="radio" name="dmto_tree_cleanup_unused" id="dmto_tree_cleanup_unused_off" value="0" {if !$dmto_tree_cleanup_unused}checked="checked"{/if} />
          <label for="dmto_tree_cleanup_unused_off">{l s='Nie' mod='dietamamyto'}</label>
          <a class="slide-button btn"></a>
        </span>
        <p class="help-block">{l s='Po zakończeniu synchronizacji, usuwa z drzew dietetycznych wszystkie kategorie, które są puste (nie mają produktów ani podkategorii).' mod='dietamamyto'}</p>
      </div>
    </div>
    <div class="panel-footer">
        <button type="button" id="dmto-step3-start-batch" class="btn btn-warning">
            <i class="icon-play"></i> {l s='Uruchom aktualizację (partiami z paskiem postępu)' mod='dietamamyto'}
        </button>
        <button type="submit" name="submitDietTreeSync" class="btn btn-primary pull-right">
            <i class="process-icon-refresh"></i> {l s='Uruchom aktualizację (pełny bieg)' mod='dietamamyto'}
        </button>
        <button type="submit" name="saveTreeSettings" class="btn btn-default">
            <i class="icon-save"></i> {l s='Zapisz ustawienia' mod='dietamamyto'}
        </button>
    </div>
  </form>

    <div id="dmto-step3-progress" style="display:none;margin-top:10px; padding: 0 15px 15px 15px;">
        <div class="progress">
            <div id="dmto-step3-bar" class="progress-bar progress-bar-info progress-bar-striped active" role="progressbar" style="width:0%">0%</div>
        </div>
        <p id="dmto-step3-status" class="help-block"></p>
        <div id="dmto-step3-details" class="help-block" style="font-size: 12px; color: #555;"></div>
        <div id="dmto-step3-errors" class="help-block" style="font-size: 12px; color: #c7254e; margin-top: 10px;"></div>
    </div>
</div>

<script src="{$module_uri}views/js/dmto_step3_progress.js"></script>
<script>
window.DMTO_STEP3 = {
  ajax: '{$ajax_link|escape:'html':'UTF-8'}',
  token: '{$token|escape:'html':'UTF-8'}'
};
</script>