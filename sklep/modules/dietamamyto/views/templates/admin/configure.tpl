{* /modules/dietamamyto/views/templates/admin/configure.tpl *}

<link rel="stylesheet" href="{$module_uri}views/templates/admin/steps/styles.css" />

{if isset($stats)}
  {include file="module:dietamamyto/views/templates/admin/steps/top_stats.tpl"}
{/if}

{assign var="__form_action" value=$form_action_link|default:$smarty.server.REQUEST_URI}


<!-- DMTO SUPER CLEANUP SELECTIVE -->
<form method="post" action="{$smarty.server.REQUEST_URI|escape:'html':'UTF-8'}"
      onsubmit="var a=this.querySelector('[name=dmto_cleanup_features]').checked;
                var b=this.querySelector('[name=dmto_cleanup_categories]').checked;
                if(!a && !b){ alert('Zaznacz co oczyścić: CECHY i/lub KATEGORIE.'); return false; }
                var ops=[]; if(a) ops.push('CECHY'); if(b) ops.push('KATEGORIE');
                return confirm('Potwierdź czyszczenie: '+ops.join(' + ')+'. Operacja jest nieodwracalna.');"
      style="margin-bottom:15px;">
  <div class="alert alert-warning" style="margin-bottom:10px;">
    <strong>{l s='Uwaga' mod='dietamamyto'}:</strong>
    {l s='Operacja usuwa przypisania cech modułu i/lub kategorie wygenerowane w Kroku 3. Nie usuwa cudzych cech ani wartości cech.' mod='dietamamyto'}
  </div>

  <div class="checkbox">
    <label>
      <input type="checkbox" name="dmto_cleanup_features" value="1">
      {l s='Usuń CECHY modułu z kart produktów (Rodzaj produktu, Dieta:*, Certyfikat:*)' mod='dietamamyto'}
    </label>
  </div>

  <div class="checkbox" style="margin-top:6px;">
    <label>
      <input type="checkbox" name="dmto_cleanup_categories" value="1">
      {l s='Usuń KATEGORIE z Kroku 3 i ich powiązania (według rootów w konfiguracji)' mod='dietamamyto'}
    </label>
  </div>

  <button type="submit" name="dmto_super_cleanup" value="1" class="btn btn-danger" style="margin-top:10px;">
    <i class="icon-trash"></i> {l s='Wykonaj wybrane czyszczenia' mod='dietamamyto'}
  </button>
</form>

<div class="panel">
    <div class="panel-heading">
        <i class="icon-cogs"></i> {l s='Ustawienia Globalne Modułu' mod='dietamamyto'}
    </div>
    <form method="post" action="{$__form_action|escape:'html':'UTF-8'}" class="form-horizontal">
        <div class="form-group">
            <label class="control-label col-lg-3">{l s='Przetwarzaj tylko aktywne produkty' mod='dietamamyto'}</label>
            <div class="col-lg-9">
                <span class="switch prestashop-switch fixed-width-lg">
                    <input type="radio" name="dmto_process_only_active" id="dmto_process_only_active_on" value="1" {if $dmto_process_only_active}checked="checked"{/if} />
                    <label for="dmto_process_only_active_on">{l s='Tak' mod='dietamamyto'}</label>
                    <input type="radio" name="dmto_process_only_active" id="dmto_process_only_active_off" value="0" {if !$dmto_process_only_active}checked="checked"{/if} />
                    <label for="dmto_process_only_active_off">{l s='Nie' mod='dietamamyto'}</label>
                    <a class="slide-button btn"></a>
                </span>
                <p class="help-block">{l s='Jeśli "Tak", wszystkie kroki modułu będą dotyczyć tylko aktywnych produktów. Jeśli "Nie", moduł będzie przetwarzał wszystkie produkty (aktywne i nieaktywne).' mod='dietamamyto'}</p>
            </div>
        </div>

        <div class="form-group">
            <label class="control-label col-lg-3">{l s='Ignoruj SKU z prefiksem' mod='dietamamyto'}</label>
            <div class="col-lg-9">
                <span class="switch prestashop-switch fixed-width-lg">
                    <input type="radio" name="dmto_sku_ignore_enabled" id="dmto_sku_ignore_enabled_on" value="1" {if $dmto_sku_ignore_enabled}checked="checked"{/if} />
                    <label for="dmto_sku_ignore_enabled_on">{l s='Tak' mod='dietamamyto'}</label>
                    <input type="radio" name="dmto_sku_ignore_enabled" id="dmto_sku_ignore_enabled_off" value="0" {if !$dmto_sku_ignore_enabled}checked="checked"{/if} />
                    <label for="dmto_sku_ignore_enabled_off">{l s='Nie' mod='dietamamyto'}</label>
                    <a class="slide-button btn"></a>
                </span>
            </div>
        </div>
        <div id="dmto_sku_prefix_group" class="form-group">
            <label class="control-label col-lg-3">{l s='Prefiks SKU do ignorowania' mod='dietamamyto'}</label>
            <div class="col-lg-3">
                <input type="text" name="dmto_sku_ignore_prefix" value="{$dmto_sku_ignore_prefix|escape:'html':'UTF-8'}" class="form-control" />
                <p class="help-block">{l s='Np. "bp_". Wielkość liter nie ma znaczenia.' mod='dietamamyto'}</p>
            </div>
        </div>

        <hr>
        <h4><i class="icon-beaker"></i> {l s='Tryb testowy' mod='dietamamyto'}</h4>
        <div class="form-group">
            <label class="control-label col-lg-3">{l s='Włącz tryb testowy' mod='dietamamyto'}</label>
            <div class="col-lg-9">
                <span class="switch prestashop-switch fixed-width-lg">
                    <input type="radio" name="dmto_test_mode_enabled" id="dmto_test_mode_enabled_on" value="1" {if $dmto_test_mode_enabled}checked="checked"{/if} />
                    <label for="dmto_test_mode_enabled_on">{l s='Tak' mod='dietamamyto'}</label>
                    <input type="radio" name="dmto_test_mode_enabled" id="dmto_test_mode_enabled_off" value="0" {if !$dmto_test_mode_enabled}checked="checked"{/if} />
                    <label for="dmto_test_mode_enabled_off">{l s='Nie' mod='dietamamyto'}</label>
                    <a class="slide-button btn"></a>
                </span>
                 <p class="help-block">{l s='Ogranicza liczbę przetwarzanych produktów we wszystkich krokach do podanej niżej wartości.' mod='dietamamyto'}</p>
            </div>
        </div>
        <div id="dmto_test_limit_group" class="form-group">
            <label class="control-label col-lg-3">{l s='Limit produktów w trybie testowym' mod='dietamamyto'}</label>
            <div class="col-lg-3">
                <input type="number" name="dmto_test_mode_limit" value="{$dmto_test_mode_limit|intval}" class="form-control" />
            </div>
        </div>

        
        <div class="form-group">
            <label class="control-label col-lg-3">{l s='Ogranicz działanie do kategorii' mod='dietamamyto'}</label>
            <div class="col-lg-9">
                {$dmto_category_tree_html nofilter}
                <p class="help-block">{l s='Zaznacz kategorie (i ich podkategorie), w których moduł ma działać.' mod='dietamamyto'}</p>
            </div>
        </div>
    
        <div class="panel-footer">
            <button type="submit" name="submitGlobalSettings" class="btn btn-default">
                <i class="icon-save"></i> {l s='Zapisz ustawienia globalne' mod='dietamamyto'}
            </button>
        </div>
    </form>
</div>

<div class="panel dmto">
  <div class="panel-heading"><i class="icon-cogs"></i> DIETA? MAMY TO</div>
</div>

{include file=$module_path|cat:'views/templates/admin/steps/step1.tpl'}
{include file=$module_path|cat:'views/templates/admin/steps/step2.tpl'}
{include file=$module_path|cat:'views/templates/admin/steps/step3.tpl'}

<script>
document.addEventListener('DOMContentLoaded', function() {
    var toggleSku = document.querySelectorAll('input[name="dmto_sku_ignore_enabled"]');
    var prefixGroup = document.getElementById('dmto_sku_prefix_group');
    function checkSkuToggle() {
        prefixGroup.style.display = document.getElementById('dmto_sku_ignore_enabled_on').checked ? 'block' : 'none';
    }
    toggleSku.forEach(function(radio) { radio.addEventListener('change', checkSkuToggle); });
    checkSkuToggle();

    var toggleTest = document.querySelectorAll('input[name="dmto_test_mode_enabled"]');
    var limitGroup = document.getElementById('dmto_test_limit_group');
    function checkTestToggle() {
        limitGroup.style.display = document.getElementById('dmto_test_mode_enabled_on').checked ? 'block' : 'none';
    }
    toggleTest.forEach(function(radio) { radio.addEventListener('change', checkTestToggle); });
    checkTestToggle();
});
</script>