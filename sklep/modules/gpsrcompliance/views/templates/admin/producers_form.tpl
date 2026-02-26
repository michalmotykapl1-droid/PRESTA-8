
<div class="panel">
  <h3><i class="icon-edit"></i> Producent odpowiedzialny</h3>
  <form method="post">
    <input type="hidden" name="id_gpsr_producer" value="{$row.id_gpsr_producer|intval}"/>
    <div class="row">
      <div class="col-lg-6">
        <div class="form-group">
          <label>Nazwa</label>
          <input class="form-control" name="name" value="{$row.name|escape}"/>
        </div>
        <div class="form-group">
          <label>Nazwa własna</label>
          <input class="form-control" name="alias" value="{$row.alias|escape}"/>
        </div>
        <div class="form-group">
          <label>Kraj</label>
          <input class="form-control" name="country" value="{$row.country|escape}"/>
        </div>
        <div class="form-group">
          <label>Adres</label>
          <input class="form-control" name="address" value="{$row.address|escape}"/>
        </div>
        <div class="form-group">
          <label>Kod pocztowy</label>
          <input class="form-control" name="postcode" value="{$row.postcode|escape}"/>
        </div>
        <div class="form-group">
          <label>Miasto</label>
          <input class="form-control" name="city" value="{$row.city|escape}"/>
        </div>
      </div>

      <div class="col-lg-6">
        <div class="form-group">
          <label>E-mail</label>
          <input class="form-control" name="email" value="{$row.email|escape}"/>
          <button type="button" class="btn btn-default" id="x-gpsr-lookup" style="margin-top:8px">
            <i class="icon-search"></i> Szukaj w internecie
          </button>
          <small class="help-block">
            Moduł spróbuje znaleźć e-mail i telefon po nazwie producenta (SerpAPI/DuckDuckGo). Po znalezieniu zapyta, czy przepisać do pól.
          </small>
        </div>
        <div class="form-group">
          <label>Telefon</label>
          <input class="form-control" name="phone" value="{$row.phone|escape}"/>
        </div>
        <div class="form-group">
          <label>Dodatkowe informacje</label>
          <textarea class="form-control" name="info" rows="6">{$row.info|escape}</textarea>
        </div>
        <div class="form-group">
          <label>Aktywny</label>
          <select name="active" class="form-control">
            <option value="1" {if $row.active}selected{/if}>Tak</option>
            <option value="0" {if !$row.active}selected{/if}>Nie</option>
          </select>
        </div>
      </div>
    </div>

    <button class="btn btn-primary" name="save_producer" value="1">
      <i class="icon-save"></i> Zapisz
    </button>
  </form>
</div>

{literal}
<script>
(function(){
  var btn = document.getElementById('x-gpsr-lookup');
  if (!btn) return;

  function toJsonOrThrow(r){
    return r.text().then(function(txt){
      if (!txt) { throw new Error((r.status||'')+' (pusta odpowiedź)'); }
      try { return JSON.parse(txt); }
      catch(e){ throw new Error((r.status||'')+' '+txt.slice(0,200)); }
    });
  }

  btn.addEventListener('click', function(){
    var name = document.querySelector('input[name="name"]').value || '';
    if (!name.trim()) { alert('Podaj najpierw nazwę producenta.'); return; }
    btn.disabled = true; btn.innerText = 'Szukam...';

    var url = window.location.href.split('#')[0];
    // PS -> Tools::toCamelCase('lookup_contact') => LookupContact => ajaxProcessLookupContact()
    url += (url.indexOf('?')===-1 ? '?' : '&') + 'ajax=1&action=lookup_contact';

    fetch(url, {
      method: 'POST',
      headers: {'Content-Type': 'application/x-www-form-urlencoded'},
      credentials: 'same-origin',
      body: 'name=' + encodeURIComponent(name)
    })
    .then(toJsonOrThrow)
    .then(function(j){
      btn.disabled = false; btn.innerText = 'Szukaj w internecie';
      if (!j.ok) { alert(j.error || 'Brak wyników'); return; }
      var arr = j.results || [];
      var pick = arr.find(function(x){ return x.email && x.phone; }) || arr[0];
      if (!pick) { alert('Brak wyników'); return; }
      var msg = 'Znaleziono:\\nURL: ' + (pick.url||'') +
                '\\nE-mail: ' + (pick.email||'-') +
                '\\nTelefon: ' + (pick.phone||'-') +
                '\\n\\nPrzepisać do formularza?';
      if (confirm(msg)) {
        if (pick.email) document.querySelector('input[name="email"]').value = pick.email;
        if (pick.phone) document.querySelector('input[name="phone"]').value = pick.phone;
      }
    })
    .catch(function(err){
      btn.disabled = false; btn.innerText = 'Szukaj w internecie';
      alert('Błąd połączenia: ' + (err && err.message ? err.message : ''));
    });
  });
})();
</script>
{/literal}
