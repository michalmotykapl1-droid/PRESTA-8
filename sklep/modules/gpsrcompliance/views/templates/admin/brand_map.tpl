<div class="panel">
  <h3><i class="icon-tags"></i> Mapowanie marek → producent/osoba (GPSR)</h3>
  <form method="post" class="form-inline">
    <select name="id_manufacturer" class="form-control">
      {foreach from=$brands item=b}
        <option value="{$b.id_manufacturer|intval}">{$b.name|escape}</option>
      {/foreach}
    </select>
    <select name="id_gpsr_producer" class="form-control">
      {foreach from=$producers item=o}
        <option value="{$o.id|intval}">{$o.name|escape}</option>
      {/foreach}
    </select>
    <select name="id_gpsr_person" class="form-control">
      {foreach from=$persons item=o}
        <option value="{$o.id|intval}">{$o.name|escape}</option>
      {/foreach}
    </select>
    <button class="btn btn-primary" name="save_map" value="1"><i class="icon-save"></i> Zapisz</button>
  </form>
  <hr/>
  <table class="table">
    <thead><tr><th>Marka</th><th>Producent</th><th>Osoba</th></tr></thead>
    <tbody>
      {foreach from=$rows item=r}
        <tr>
          <td>{$r.name|escape}</td>
          <td>{if $r.id_gpsr_producer}{$r.id_gpsr_producer|intval}{else}-{/if}</td>
          <td>{if $r.id_gpsr_person}{$r.id_gpsr_person|intval}{else}-{/if}</td>
        </tr>
      {/foreach}
    </tbody>
  </table>
</div>
