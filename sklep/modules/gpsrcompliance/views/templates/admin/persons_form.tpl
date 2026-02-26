<div class="panel">
  <h3><i class="icon-edit"></i> Osoba odpowiedzialna</h3>
  <form method="post">
    <input type="hidden" name="id_gpsr_person" value="{$row.id_gpsr_person|intval}"/>
    <div class="row">
      <div class="col-lg-6">
        <div class="form-group"><label>Imię/nazwa</label><input class="form-control" name="name" value="{$row.name|escape}"/></div>
        <div class="form-group"><label>Nazwa własna</label><input class="form-control" name="alias" value="{$row.alias|escape}"/></div>
        <div class="form-group"><label>Kraj</label><input class="form-control" name="country" value="{$row.country|escape}"/></div>
        <div class="form-group"><label>Adres</label><input class="form-control" name="address" value="{$row.address|escape}"/></div>
        <div class="form-group"><label>Kod pocztowy</label><input class="form-control" name="postcode" value="{$row.postcode|escape}"/></div>
        <div class="form-group"><label>Miasto</label><input class="form-control" name="city" value="{$row.city|escape}"/></div>
      </div>
      <div class="col-lg-6">
        <div class="form-group"><label>E-mail</label><input class="form-control" name="email" value="{$row.email|escape}"/></div>
        <div class="form-group"><label>Telefon</label><input class="form-control" name="phone" value="{$row.phone|escape}"/></div>
        <div class="form-group"><label>Dodatkowe informacje</label><textarea class="form-control" name="info" rows="6">{$row.info|escape}</textarea></div>
        <div class="form-group"><label>Aktywny</label><select name="active" class="form-control"><option value="1" {if $row.active}selected{/if}>Tak</option><option value="0" {if !$row.active}selected{/if}>Nie</option></select></div>
      </div>
    </div>
    <button class="btn btn-primary" name="save_person" value="1"><i class="icon-save"></i> Zapisz</button>
  </form>
</div>
