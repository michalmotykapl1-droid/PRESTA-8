<div class="panel">
  <h3><i class="icon-link"></i> Mapy kategorii Allegro → Presta</h3>
  <table class="table">
    <thead><tr><th>ID Allegro</th><th>Nazwa</th><th>ID kategorii PS</th><th>Data</th></tr></thead>
    <tbody>
      {foreach from=$maps item=row}
        <tr>
          <td>{$row.allegro_category_id|escape:'html'}</td>
          <td>{$row.allegro_category_name|escape:'html'}</td>
          <td>{$row.ps_id_category|intval}</td>
          <td>{$row.created_at|escape:'html'}</td>
        </tr>
      {/foreach}
      {if !$maps}<tr><td colspan="4" class="text-muted">Brak danych</td></tr>{/if}
    </tbody>
  </table>
</div>
