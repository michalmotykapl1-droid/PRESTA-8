<div class="panel">
  <h3><i class="icon-info-circle"></i> GPSR – bezpieczeństwo produktu</h3>
  {if $gpsr_default}
    <div class="alert alert-info">
      <b>Domyślne (z marki/konfiguracji):</b>
      Producent: {$gpsr_default.producer.name|escape} • {$gpsr_default.producer.email|escape}<br/>
      Osoba: {$gpsr_default.person.name|escape}
    </div>
  {/if}
  <div class="form-group">
    <label>Producent odpowiedzialny (nadpisz):</label>
    <select name="gpsr_id_gpsr_producer" class="form-control">
      {foreach from=$gpsr_producers item=o}
        <option value="{$o.id|intval}" {if $gpsr_current.id_gpsr_producer == $o.id}selected{/if}>{$o.name|escape}</option>
      {/foreach}
    </select>
  </div>
  <div class="form-group">
    <label>Osoba odpowiedzialna (nadpisz):</label>
    <select name="gpsr_id_gpsr_person" class="form-control">
      {foreach from=$gpsr_persons item=o}
        <option value="{$o.id|intval}" {if $gpsr_current.id_gpsr_person == $o.id}selected{/if}>{$o.name|escape}</option>
      {/foreach}
    </select>
  </div>
  <div class="form-group">
    <label>Dodatkowe informacje dotyczące bezpieczeństwa:</label>
    <textarea name="gpsr_extra_info" class="form-control" rows="4">{$gpsr_current.extra_info|escape}</textarea>
  </div>
  <p class="help-block">Zapisz produkt, aby utrwalić zmiany.</p>
</div>
