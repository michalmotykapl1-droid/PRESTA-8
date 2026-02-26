{if isset($smarty.get.allegropro_ok)}
  <div class="alert alert-success">{$smarty.get.allegropro_ok|escape:'htmlall':'UTF-8'}</div>
{/if}
{if isset($smarty.get.allegropro_err)}
  <div class="alert alert-danger">{$smarty.get.allegropro_err|escape:'htmlall':'UTF-8'}</div>
{/if}

<style>
.allegropro-wrap { margin-top: 8px; }
.allegropro-hero {
  border: 1px solid #dbe8ff;
  border-radius: 12px;
  padding: 20px;
  background: linear-gradient(135deg, #f7fbff 0%, #eef5ff 100%);
  margin-bottom: 16px;
}
.allegropro-hero h3 { margin: 0 0 8px 0; font-size: 24px; font-weight: 700; }
.allegropro-hero p { margin: 0; color: #51606f; }
.allegropro-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px; }
.allegropro-card {
  border: 1px solid #e5eaf1;
  border-radius: 12px;
  background: #fff;
  overflow: hidden;
}
.allegropro-card-head {
  padding: 14px 16px;
  border-bottom: 1px solid #edf1f7;
  font-weight: 700;
  background: #fafcff;
}
.allegropro-card-body { padding: 16px; }
.allegropro-muted { color: #6e7b88; }
.allegropro-code {
  display: block;
  background: #f6f8fb;
  border: 1px solid #e8edf5;
  border-radius: 8px;
  padding: 10px;
  word-break: break-all;
  margin-bottom: 12px;
}
.allegropro-form-grid { display: grid; grid-template-columns: 2fr 1fr 1fr 1fr auto; gap: 10px; align-items: end; }
.allegropro-actions { display: flex; gap: 6px; flex-wrap: wrap; }
.allegropro-badge {
  display: inline-block;
  padding: 3px 8px;
  border-radius: 999px;
  font-size: 11px;
  font-weight: 700;
  text-transform: uppercase;
}
.allegropro-badge-ok { background: #dff7e8; color: #1f8f55; }
.allegropro-badge-no { background: #f3f5f8; color: #708090; }
.allegropro-badge-warn { background: #fff2d8; color: #9e6c00; }
.allegropro-table th { background: #f8fafc; }
@media (max-width: 1200px) {
  .allegropro-grid { grid-template-columns: 1fr; }
  .allegropro-form-grid { grid-template-columns: 1fr 1fr; }
}
</style>

<div class="allegropro-wrap">
  <div class="allegropro-hero">
    <h3>Allegro Pro • Konta</h3>
    <p>Nowoczesny i szybki proces autoryzacji: kliknij <strong>Autoryzuj</strong>, zaloguj się w popupie Allegro, a po zakończeniu wrócisz automatycznie do tej listy.</p>
  </div>

  <div class="allegropro-grid">
    <div class="allegropro-card">
      <div class="allegropro-card-head"><i class="icon icon-link"></i> OAuth i Redirect URI</div>
      <div class="allegropro-card-body">
        <div class="allegropro-muted" style="margin-bottom:6px;"><strong>Redirect URI</strong> (dodaj 1:1 w Allegro Developer Apps):</div>
        <code class="allegropro-code">{$allegropro_redirect_uri|escape:'htmlall':'UTF-8'}</code>
        <p class="allegropro-muted" style="margin-bottom:10px;">Client ID i Client Secret ustawiasz raz. Każde konto Allegro autoryzujesz osobno.</p>
        <a class="btn btn-default" href="{$link->getAdminLink('AdminAllegroProSettings')|escape:'htmlall':'UTF-8'}">
          <i class="icon icon-cogs"></i> Ustawienia OAuth
        </a>
      </div>
    </div>

    <div class="allegropro-card">
      <div class="allegropro-card-head"><i class="icon icon-info-circle"></i> Jak działa autoryzacja</div>
      <div class="allegropro-card-body">
        <ol style="padding-left:18px; margin-bottom:10px;">
          <li>Kliknij <strong>Autoryzuj</strong> przy koncie.</li>
          <li>Zaloguj się i potwierdź dostęp po stronie Allegro.</li>
          <li>Popup zamknie się sam, lista kont odświeży się automatycznie.</li>
        </ol>
        <p class="allegropro-muted" style="margin:0;">Jeśli popup jest blokowany, zezwól na wyskakujące okna dla panelu admina.</p>
      </div>
    </div>
  </div>

  <div class="allegropro-card" style="margin-bottom:16px;">
    <div class="allegropro-card-head"><i class="icon icon-plus-circle"></i> Dodaj konto Allegro</div>
    <div class="allegropro-card-body">
      <form method="post" action="{$admin_link|escape:'htmlall':'UTF-8'}">
        <input type="hidden" name="allegropro_add_account" value="1" />
        <div class="allegropro-form-grid">
          <div>
            <label>Nazwa konta (etykieta)</label>
            <input class="form-control" type="text" name="label" placeholder="np. bigbio / bp24h" required />
          </div>
          <div>
            <label>Sandbox</label>
            <select class="form-control" name="sandbox">
              <option value="0" selected>Nie</option>
              <option value="1">Tak</option>
            </select>
          </div>
          <div>
            <label>Aktywne</label>
            <select class="form-control" name="active">
              <option value="1" selected>Tak</option>
              <option value="0">Nie</option>
            </select>
          </div>
          <div>
            <label>Domyślne</label>
            <select class="form-control" name="is_default">
              <option value="0" selected>Nie</option>
              <option value="1">Tak</option>
            </select>
          </div>
          <div>
            <button class="btn btn-primary" type="submit"><i class="icon icon-plus"></i> Dodaj konto</button>
          </div>
        </div>
      </form>
    </div>
  </div>

  <div class="allegropro-card">
    <div class="allegropro-card-head"><i class="icon icon-user"></i> Lista kont Allegro</div>
    <div class="allegropro-card-body" style="padding-top:0;">
      <table class="table allegropro-table" style="margin-bottom:0;">
        <thead>
          <tr>
            <th>ID</th>
            <th>Etykieta</th>
            <th>Login Allegro</th>
            <th>Środowisko</th>
            <th>Aktywne</th>
            <th>Domyślne</th>
            <th>Autoryzacja</th>
            <th style="width:320px;">Akcje</th>
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
                <td>{if $a.allegro_login}{$a.allegro_login|escape:'htmlall':'UTF-8'}{else}<span class="text-muted">—</span>{/if}</td>
                <td>{if $a.sandbox}<span class="allegropro-badge allegropro-badge-warn">Sandbox</span>{else}<span class="allegropro-badge allegropro-badge-no">Prod</span>{/if}</td>
                <td>{if $a.active}<span class="allegropro-badge allegropro-badge-ok">Tak</span>{else}<span class="allegropro-badge allegropro-badge-no">Nie</span>{/if}</td>
                <td>{if $a.is_default}<span class="allegropro-badge allegropro-badge-ok">Tak</span>{else}<span class="text-muted">—</span>{/if}</td>
                <td>{if $a.access_token && $a.refresh_token}<span class="allegropro-badge allegropro-badge-ok">OK</span>{else}<span class="allegropro-badge allegropro-badge-warn">Brak</span>{/if}</td>
                <td>
                  <div class="allegropro-actions">
                    <a class="btn btn-default btn-xs js-allegropro-auth" href="{$admin_link|escape:'htmlall':'UTF-8'}&action=authorize&id_allegropro_account={$a.id_allegropro_account|intval}" data-auth-url="{$admin_link|escape:'htmlall':'UTF-8'}&action=authorize&id_allegropro_account={$a.id_allegropro_account|intval}">
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
                  </div>
                </td>
              </tr>
            {/foreach}
          {/if}
        </tbody>
      </table>
    </div>
  </div>
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
