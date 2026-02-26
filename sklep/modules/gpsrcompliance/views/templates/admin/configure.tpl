<div class="panel">
  <h3><i class="icon-shield"></i> GPSR – ustawienia globalne</h3>
  <form method="post">
    <div class="row">
      <div class="col-lg-6">
        <h4>Dane domyślne (fallback)</h4>
        <div class="form-group"><label>Nazwa podmiotu odpowiedzialnego</label><input class="form-control" type="text" name="GPSR_RESP_NAME" value="{$GPSR_RESP_NAME|escape:'htmlall':'UTF-8'}"></div>
        <div class="form-group"><label>Adres</label><input class="form-control" type="text" name="GPSR_RESP_ADDRESS" value="{$GPSR_RESP_ADDRESS|escape:'htmlall':'UTF-8'}"></div>
        <div class="form-group"><label>E-mail</label><input class="form-control" type="text" name="GPSR_RESP_EMAIL" value="{$GPSR_RESP_EMAIL|escape:'htmlall':'UTF-8'}"></div>
        <div class="form-group"><label>Telefon</label><input class="form-control" type="text" name="GPSR_RESP_PHONE" value="{$GPSR_RESP_PHONE|escape:'htmlall':'UTF-8'}"></div>
      </div>
      <div class="col-lg-6">
        <h4>Parametry Allegro (ID w kategorii)</h4>
        <div class="form-group"><label>ID parametru – wybór (select) „zawiera informacje o bezpieczeństwie”</label><input class="form-control" type="text" name="GPSR_SAFETY_SELECT_PARAM_ID" value="{$GPSR_SAFETY_SELECT_PARAM_ID|escape:'htmlall':'UTF-8'}"></div>
        <div class="form-group"><label>ID wartości „TAK” (valuesIds)</label><input class="form-control" type="text" name="GPSR_SAFETY_SELECT_VALUE_YES_ID" value="{$GPSR_SAFETY_SELECT_VALUE_YES_ID|escape:'htmlall':'UTF-8'}"></div>
        <div class="form-group"><label>ID parametru – tekst „Informacje o bezpieczeństwie”</label><input class="form-control" type="text" name="GPSR_SAFETY_TEXT_PARAM_ID" value="{$GPSR_SAFETY_TEXT_PARAM_ID|escape:'htmlall':'UTF-8'}"></div>
      </div>
    </div>

    <div class="row">
      <div class="col-lg-6">
        <h4>Automatyczne uzupełnianie kontaktów</h4>
        <div class="form-group"><label>Dostawca wyszukiwania</label>
          <select class="form-control" name="GPSR_LOOKUP_PROVIDER">
            <option value="">(wyłączone)</option>
            <option value="serpapi" {if $GPSR_LOOKUP_PROVIDER=='serpapi'}selected{/if}>SerpAPI (zalecane)</option>
            <option value="duckduckgo" {if $GPSR_LOOKUP_PROVIDER=='duckduckgo'}selected{/if}>DuckDuckGo (fallback)</option>
          </select>
        </div>
        <div class="form-group"><label>SerpAPI API key</label><input class="form-control" type="text" name="GPSR_SERPAPI_KEY" value="{$GPSR_SERPAPI_KEY|escape:'htmlall':'UTF-8'}"></div>
      </div>
    </div>

    <h4>Szablon tekstu GPSR</h4>
    <p class="help-block">Placeholdery: {ldelim}RESP_NAME{rdelim}, {ldelim}RESP_ADDRESS{rdelim}, {ldelim}RESP_EMAIL{rdelim}, {ldelim}RESP_PHONE{rdelim}, {ldelim}PRODUCT_NAME{rdelim}, {ldelim}REFERENCE{rdelim}, {ldelim}EAN{rdelim}, {ldelim}BRAND{rdelim}, {ldelim}DATE{rdelim}</p>
    <textarea class="form-control" name="GPSR_TEMPLATE" rows="8">{$GPSR_TEMPLATE|escape:'htmlall':'UTF-8'}</textarea>
    <br/>
    <button class="btn btn-primary" name="submitGpsr" value="1"><i class="icon-save"></i> Zapisz</button>
  </form>
</div>
