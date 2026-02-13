{if isset($smarty.get.allegropro_ok)}
  <div class="alert alert-success">{$smarty.get.allegropro_ok|escape:'htmlall':'UTF-8'}</div>
{/if}
{if isset($smarty.get.allegropro_err)}
  <div class="alert alert-danger">{$smarty.get.allegropro_err|escape:'htmlall':'UTF-8'}</div>
{/if}

<div class="panel">
  <h3><i class="icon icon-link"></i> Autoryzacja OAuth – jedna aplikacja, wiele kont</h3>

  <div class="alert alert-info">
    <strong>Redirect URI</strong> (musi być dodane w Allegro Developer Apps):
    <div style="margin-top:6px;">
      <code style="display:block; white-space:normal; word-break:break-all;">{$allegropro_redirect_uri|escape:'htmlall':'UTF-8'}</code>
    </div>
    <div style="margin-top:8px;">
      Client ID / Client Secret ustawiasz tylko raz w zakładce <strong>Ustawienia</strong>.
      Każde konto Allegro to osobna autoryzacja (tokeny), ale NIE osobny Client ID/Secret.
    </div>
    <div style="margin-top:8px;">
      <a class="btn btn-default" href="{$link->getAdminLink('AdminAllegroProSettings')|escape:'htmlall':'UTF-8'}">
        <i class="icon icon-cogs"></i> Przejdź do ustawień OAuth
      </a>
    </div>
  </div>
</div>

<div class="panel">
  <h3><i class="icon icon-user"></i> Konta Allegro</h3>

  <form method="post" action="{$admin_link|escape:'htmlall':'UTF-8'}" class="form-horizontal" style="margin-bottom:20px;">
    <input type="hidden" name="allegropro_add_account" value="1" />
    <div class="row">
      <div class="col-md-4">
        <label>Nazwa konta (etykieta)</label>
        <input class="form-control" type="text" name="label" placeholder="np. bigbio / bp24h" required />
      </div>
      <div class="col-md-2">
        <label>Sandbox</label>
        <select class="form-control" name="sandbox">
          <option value="0" selected>Nie</option>
          <option value="1">Tak</option>
        </select>
      </div>
      <div class="col-md-2">
        <label>Aktywne</label>
        <select class="form-control" name="active">
          <option value="1" selected>Tak</option>
          <option value="0">Nie</option>
        </select>
      </div>
      <div class="col-md-2">
        <label>Domyślne</label>
        <select class="form-control" name="is_default">
          <option value="0" selected>Nie</option>
          <option value="1">Tak</option>
        </select>
      </div>
      <div class="col-md-2" style="margin-top:25px;">
        <button class="btn btn-primary" type="submit"><i class="icon icon-plus"></i> Dodaj konto</button>
      </div>
    </div>
  </form>

  <table class="table">
    <thead>
      <tr>
        <th>ID</th>
        <th>Etykieta</th>
        <th>Login Allegro</th>
        <th>Środowisko</th>
        <th>Aktywne</th>
        <th>Domyślne</th>
        <th>Autoryzacja</th>
        <th style="width:280px;">Akcje</th>
      </tr>
    </thead>
    <tbody>
      {if empty($allegropro_accounts)}
        <tr><td colspan="8" class="text-center text-muted">Brak kont. Dodaj pierwsze konto powyżej.</td></tr>
      {else}
        {foreach from=$allegropro_accounts item=a}
          <tr>
            <td>{$a.id_allegropro_account|intval}</td>
            <td><strong>{$a.label|escape:'htmlall':'UTF-8'}</strong></td>
            <td>
              {if $a.allegro_login}
                {$a.allegro_login|escape:'htmlall':'UTF-8'}
              {else}
                <span class="text-muted">—</span>
              {/if}
            </td>
            <td>{if $a.sandbox}Sandbox{else}Prod{/if}</td>
            <td>{if $a.active}<span class="label label-success">TAK</span>{else}<span class="label label-default">NIE</span>{/if}</td>
            <td>{if $a.is_default}<span class="label label-info">TAK</span>{else}<span class="text-muted">—</span>{/if}</td>
            <td>
              {if $a.access_token && $a.refresh_token}
                <span class="label label-success">OK</span>
              {else}
                <span class="label label-warning">BRAK</span>
              {/if}
            </td>
            <td>
              <a
                class="btn btn-default btn-xs js-allegropro-auth"
                href="{$admin_link|escape:'htmlall':'UTF-8'}&action=authorize&id_allegropro_account={$a.id_allegropro_account|intval}"
                data-auth-url="{$admin_link|escape:'htmlall':'UTF-8'}&action=authorize&id_allegropro_account={$a.id_allegropro_account|intval}"
              >
                <i class="icon icon-key"></i> Autoryzuj
              </a>

              <a class="btn btn-default btn-xs" href="{$admin_link|escape:'htmlall':'UTF-8'}&allegropro_toggle_active=1&id_allegropro_account={$a.id_allegropro_account|intval}">
                <i class="icon icon-refresh"></i> Aktywne
              </a>

              <a class="btn btn-default btn-xs" href="{$admin_link|escape:'htmlall':'UTF-8'}&allegropro_set_default=1&id_allegropro_account={$a.id_allegropro_account|intval}">
                <i class="icon icon-star"></i> Domyślne
              </a>

              <a class="btn btn-danger btn-xs" href="{$admin_link|escape:'htmlall':'UTF-8'}&allegropro_delete_account=1&id_allegropro_account={$a.id_allegropro_account|intval}" onclick="return confirm('Usunąć konto?');">
                <i class="icon icon-trash"></i> Usuń
              </a>
            </td>
          </tr>
        {/foreach}
      {/if}
    </tbody>
  </table>
</div>

<div class="modal fade" id="allegroproAuthModal" tabindex="-1" role="dialog" aria-labelledby="allegroproAuthModalLabel">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h4 class="modal-title" id="allegroproAuthModalLabel"><i class="icon icon-key"></i> Trwa autoryzacja Allegro</h4>
      </div>
      <div class="modal-body">
        <p>Otworzyliśmy okno logowania Allegro.</p>
        <ol style="padding-left:20px; margin-bottom:10px;">
          <li>Zaloguj się i potwierdź dostęp.</li>
          <li>Po zakończeniu wrócisz automatycznie do tej listy kont.</li>
        </ol>
        <p class="text-muted" style="margin-bottom:0;">Nie zamykaj tego okna panelu admina do czasu zakończenia.</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Ukryj</button>
      </div>
    </div>
  </div>
</div>

<script>
{literal}
(function () {
  var popup = null;
  var timer = null;

  function showModal() {
    if (window.jQuery && typeof jQuery.fn.modal === 'function') {
      jQuery('#allegroproAuthModal').modal({backdrop: 'static', keyboard: false});
    }
  }

  function hideModal() {
    if (window.jQuery && typeof jQuery.fn.modal === 'function') {
      jQuery('#allegroproAuthModal').modal('hide');
    }
  }

  function watchPopup() {
    if (timer) {
      window.clearInterval(timer);
    }

    timer = window.setInterval(function () {
      if (!popup || popup.closed) {
        window.clearInterval(timer);
        hideModal();
      }
    }, 500);
  }

  function openAuthPopup(url) {
    var features = [
      'toolbar=no',
      'location=yes',
      'status=no',
      'menubar=no',
      'scrollbars=yes',
      'resizable=yes',
      'width=980',
      'height=780'
    ].join(',');

    popup = window.open(url, 'allegropro_oauth_popup', features);

    if (!popup) {
      alert('Przeglądarka zablokowała popup. Zezwól na wyskakujące okna dla panelu admina.');
      hideModal();
      return;
    }

    try {
      popup.focus();
    } catch (e) {
    }

    watchPopup();
  }

  var links = document.querySelectorAll('.js-allegropro-auth');
  for (var i = 0; i < links.length; i++) {
    links[i].addEventListener('click', function (e) {
      e.preventDefault();
      var url = this.getAttribute('data-auth-url') || this.getAttribute('href');
      showModal();
      openAuthPopup(url);
    });
  }
})();
{/literal}
</script>
