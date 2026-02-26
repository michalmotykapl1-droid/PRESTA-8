<div class="panel">
  <h3><i class="icon-check"></i> ZROBIONE — produkty pomijane w skanach</h3>
  <form method="post">
    <table class="table">
      <thead><tr><th></th><th>ID produktu</th><th>Nazwa</th><th>Oznaczono</th><th>Batch</th></tr></thead>
      <tbody>
      {foreach from=$done_list item=row}
        <tr>
          <td><input type="checkbox" name="done_ids[]" value="{$row.id_product|intval}"></td>
          <td>{$row.id_product|intval}</td>
          <td>{$row.product_name|escape:'html'}</td>
          <td>{$row.done_at|escape:'html'}</td>
          <td>{$row.last_batch_id|escape:'html'}</td>
        </tr>
      {/foreach}
      {if !$done_list}<tr><td colspan="5" class="text-muted">Brak danych</td></tr>{/if}
      </tbody>
    </table>
    <button type="submit" name="submitUndone" class="btn btn-warning"><i class="icon-undo"></i> Odznacz wybrane</button>
  </form>
</div>
