<div class="panel">
  <h3><i class="icon-user"></i> Osoby odpowiedzialne</h3>
  <a class="btn btn-primary" href="{$self_link|escape}&edit=0"><i class="icon-plus"></i> Dodaj</a>
  <table class="table">
    <thead><tr><th>ID</th><th>Nazwa</th><th>E-mail</th><th>Telefon</th><th>Akcje</th></tr></thead>
    <tbody>
      {foreach from=$rows item=r}
        <tr>
          <td>{$r.id_gpsr_person|intval}</td>
          <td>{$r.name|escape}</td>
          <td>{$r.email|escape}</td>
          <td>{$r.phone|escape}</td>
          <td>
            <a class="btn btn-default" href="{$self_link|escape}&edit={$r.id_gpsr_person|intval}"><i class="icon-edit"></i></a>
            <a class="btn btn-danger" href="{$self_link|escape}&delete=1&id={$r.id_gpsr_person|intval}" onclick="return confirm('Usunąć rekord?')"><i class="icon-trash"></i></a>
          </td>
        </tr>
      {/foreach}
    </tbody>
  </table>
</div>
