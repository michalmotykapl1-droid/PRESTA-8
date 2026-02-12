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