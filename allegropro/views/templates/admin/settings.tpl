<div class="panel">
  <h3><i class="icon icon-cogs"></i> ALLEGRO PRO – OAuth (jedna aplikacja, wiele kont)</h3>

  <div class="alert alert-info">
    <strong>Redirect URI</strong> (wklej w Allegro Developer Apps):
    <div style="margin-top:6px;">
      <code style="display:block; white-space:normal; word-break:break-all;">{$allegropro_redirect_uri|escape:'htmlall':'UTF-8'}</code>
    </div>
    <div style="margin-top:8px;">
      Client ID / Client Secret zapisujesz <strong>raz</strong> dla aplikacji. Potem dodajesz dowolną liczbę kont (autoryzacje).
    </div>
  </div>

  <form method="post" action="">
    <input type="hidden" name="submitAllegroProOauth" value="1" />
    <div class="form-group">
      <label>Środowisko</label>
      <select class="form-control" name="ALLEGROPRO_ENV">
        <option value="prod" {if $allegropro_env=='prod'}selected{/if}>Produkcja</option>
        <option value="sandbox" {if $allegropro_env=='sandbox'}selected{/if}>Sandbox</option>
      </select>
      <p class="help-block">Jeśli chcesz obsługiwać sandbox, potrzebujesz aplikacji i danych OAuth z sandbox.</p>
    </div>

    <div class="form-group">
      <label>Client ID</label>
      <input class="form-control" type="text" name="ALLEGROPRO_CLIENT_ID" value="{$allegropro_client_id|escape:'htmlall':'UTF-8'}" />
    </div>

    <div class="form-group">
      <label>Client Secret</label>
      <input class="form-control" type="password" name="ALLEGROPRO_CLIENT_SECRET" value="" autocomplete="new-password" />
      <p class="help-block">
        {if $allegropro_client_secret_set}
          Secret jest już zapisany. Zostaw puste, aby nie nadpisywać.
        {else}
          Wprowadź secret (wymagany).
        {/if}
      </p>
    </div>

    <button class="btn btn-primary" type="submit"><i class="icon icon-save"></i> Zapisz ustawienia OAuth</button>
  </form>
</div>

<div class="panel">
  <h3><i class="icon icon-truck"></i> InPost ShipX (Wysyłam z Allegro)</h3>

  <div class="alert alert-warning" style="border-radius:12px;">
    <strong>Po co to jest?</strong> Token API ShipX (InPost) jest wymagany do tworzenia etykiet InPost w <em>Wysyłam z Allegro</em>.
    <br>
    <strong>Uwaga:</strong> Allegro pobiera ten token z ustawień w Allegro Sales Center → Wysyłam z Allegro → <em>Integracja z InPost</em>. Moduł nie ma publicznego endpointu do automatycznego wgrania tokenu do Allegro,
    ale przechowuje go pomocniczo, abyś mógł szybko skopiować / wkleić i mieć podgląd, dla których kont w module jest ustawiony.
  </div>

  <div class="alert alert-info" style="border-radius:12px;">
    <strong>Szybka instrukcja:</strong> InPost Manager Paczek → Moje Konto → API → API ShipX → <em>Generuj</em> / skopiuj token.
  </div>

  <form method="post" action="">
    <input type="hidden" name="submitAllegroProShipX" value="1" />

    <div class="row">
      <div class="col-md-6">
        <label>Token API ShipX (InPost)</label>
        <input class="form-control" type="password" name="ALLEGROPRO_SHIPX_TOKEN" value="" autocomplete="new-password" placeholder="Wklej token ShipX…" />
        <p class="help-block">Token jest zapisywany w bazie per konto (nie wyświetlamy go wprost). Jeśli chcesz go zmienić — wklej nowy i zapisz.</p>
      </div>

      <div class="col-md-6">
        <label>Przypisz do kont Allegro (możesz zaznaczyć kilka)</label>
        <div style="max-height:220px; overflow:auto; border:1px solid #d8e3ef; border-radius:10px; padding:10px; background:#fff;">
          {foreach from=$allegropro_shipx_accounts item=a}
            <label style="display:flex; align-items:center; gap:10px; margin:0 0 8px 0; padding:8px 10px; border:1px solid #eef2f7; border-radius:8px; cursor:pointer;">
              <input type="checkbox" name="ALLEGROPRO_SHIPX_ACCOUNTS[]" value="{$a.id_allegropro_account|intval}" style="width:16px;height:16px;" />
              <span>
                <strong>{$a.label|escape:'htmlall':'UTF-8'}</strong>
                {if $a.allegro_login}<span class="text-muted">({$a.allegro_login|escape:'htmlall':'UTF-8'})</span>{/if}
                {if $a.shipx_token_set}
                  <span class="label label-success" style="margin-left:8px;">token: OK</span>
                {else}
                  <span class="label label-default" style="margin-left:8px;">token: brak</span>
                {/if}
              </span>
            </label>
          {/foreach}
        </div>
        <p class="help-block">Jeśli ten sam NIP — możesz użyć jednego tokenu dla wielu kont.</p>
      </div>
    </div>

    <div style="margin-top:12px; display:flex; gap:10px; flex-wrap:wrap;">
      <button class="btn btn-primary" type="submit" name="ALLEGROPRO_SHIPX_ACTION" value="set">
        <i class="icon icon-save"></i> Zapisz token dla zaznaczonych kont
      </button>
      <button class="btn btn-danger" type="submit" name="ALLEGROPRO_SHIPX_ACTION" value="clear" onclick="return confirm('Usunąć token ShipX z zaznaczonych kont?');">
        <i class="icon icon-trash"></i> Usuń token z zaznaczonych kont
      </button>
    </div>
  </form>
</div>

<div class="panel">
  <form method="post" action="">
      <input type="hidden" name="submitAllegroProDefaults" value="1" />

      <h3><i class="icon icon-print"></i> Format Etykiet</h3>
      <p class="help-block">Zdefiniuj, w jakim formacie API Allegro ma generować etykiety. Te ustawienia będą używane przy pobieraniu PDF/ZPL.</p>
      
      <div class="row">
        <div class="col-md-3">
            <label>Format pliku</label>
            <select class="form-control" name="ALLEGROPRO_LABEL_FORMAT">
            <option value="PDF" {if $allegropro_label_format=='PDF'}selected{/if}>PDF (Standard)</option>
            <option value="ZPL" {if $allegropro_label_format=='ZPL'}selected{/if}>ZPL (Drukarki przemysłowe)</option>
            </select>
        </div>
        <div class="col-md-3">
            <label>Rozmiar papieru</label>
            <select class="form-control" name="ALLEGROPRO_LABEL_SIZE">
            <option value="A4" {if $allegropro_label_size=='A4'}selected{/if}>A4 (Zwykła drukarka)</option>
            <option value="A6" {if $allegropro_label_size=='A6'}selected{/if}>A6 (Etykiety termiczne 10x15cm)</option>
            </select>
        </div>
      </div>

    <hr>
    
    <h3><i class="icon icon-truck"></i> Domyślne parametry paczki</h3>
    <p class="help-block">Wartości używane przy automatycznym tworzeniu przesyłki (gdy nie edytujesz ich ręcznie).</p>

    <div class="row">
      <div class="col-md-3">
        <label>Typ paczki</label>
        <select class="form-control" name="ALLEGROPRO_PKG_TYPE">
          <option value="BOX" {if $allegropro_pkg.type=='BOX'}selected{/if}>BOX (Paczka standardowa)</option>
          <option value="OTHER" {if $allegropro_pkg.type=='OTHER'}selected{/if}>OTHER (Niestandardowa)</option>
          <option value="PALETTE" {if $allegropro_pkg.type=='PALETTE'}selected{/if}>PALETTE (Paleta)</option>
        </select>
      </div>
      <div class="col-md-3">
        <label>Waga (kg)</label>
        <div class="input-group">
            <input class="form-control" type="text" name="ALLEGROPRO_PKG_WGT" value="{$allegropro_pkg.weight|escape:'htmlall':'UTF-8'}" />
            <span class="input-group-addon">kg</span>
        </div>
      </div>
      <div class="col-md-3">
        <label>Zawartość (opis)</label>
        <input class="form-control" type="text" name="ALLEGROPRO_PKG_TEXT" value="{$allegropro_pkg.text|escape:'htmlall':'UTF-8'}" />
      </div>
    </div>

    <div class="row" style="margin-top:15px;">
      <div class="col-md-2">
        <label>Długość (cm)</label>
        <input class="form-control" type="number" name="ALLEGROPRO_PKG_LEN" value="{$allegropro_pkg.length|escape:'htmlall':'UTF-8'}" />
      </div>
      <div class="col-md-2">
         <label>Szerokość (cm)</label>
        <input class="form-control" type="number" name="ALLEGROPRO_PKG_WID" value="{$allegropro_pkg.width|escape:'htmlall':'UTF-8'}" />
      </div>
      <div class="col-md-2">
        <label>Wysokość (cm)</label>
        <input class="form-control" type="number" name="ALLEGROPRO_PKG_HEI" value="{$allegropro_pkg.height|escape:'htmlall':'UTF-8'}" />
      </div>
    </div>
    
    <div class="row" style="margin-top:20px;">
        <div class="col-md-12">
            <div class="alert alert-info">
                Dane nadawcy są pobierane automatycznie z globalnych ustawień sklepu (Kontakt > Sklepy).
            </div>
        </div>
    </div>

    <button class="btn btn-primary" type="submit" style="margin-top:15px;"><i class="icon icon-save"></i> Zapisz wszystkie ustawienia</button>
  </form>
</div>