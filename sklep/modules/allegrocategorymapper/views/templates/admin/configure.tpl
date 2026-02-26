{if isset($confirmations)}{foreach from=$confirmations item=c}<div class="alert alert-success">{$c}</div>{/foreach}{/if}
{if isset($errors)}{foreach from=$errors item=e}<div class="alert alert-danger">{$e}</div>{/foreach}{/if}

<div class="panel">
  <h3><i class="icon-cogs"></i> Allegro Category Mapper — Ustawienia</h3>
  <form method="post">
    <div class="row">
      <div class="col-md-6">
        <div class="panel">
          <h4>OAuth2 (Allegro)</h4>
          <div class="form-group"><label>Client ID</label><input type="text" name="ACM_CLIENT_ID" class="form-control" value="{$ACM_CLIENT_ID|escape:'html'}"></div>
          <div class="form-group"><label>Client Secret</label><input type="text" name="ACM_CLIENT_SECRET" class="form-control" value="{$ACM_CLIENT_SECRET|escape:'html'}"></div>
          <p>Redirect URI: <code>{$oauth_url|escape:'html'}</code></p>
          <a class="btn btn-default" href="{$oauth_url|escape:'html'}"><i class="icon-key"></i> Autoryzuj z Allegro</a>
        </div>
      </div>
      <div class="col-md-6">
        <div class="panel">
          <h4>Ogólne</h4>
          <div class="form-group"><label>API URL</label><input type="text" name="ACM_API_URL" class="form-control" value="{$ACM_API_URL|escape:'html'}"></div>
          <div class="form-group"><label>ID kategorii-rodzica (PS)</label><input type="number" name="ACM_ROOT_CATEGORY_ID" class="form-control" value="{$ACM_ROOT_CATEGORY_ID|intval}"></div>
          <div class="checkbox"><label><input type="checkbox" name="ACM_BUILD_FULL_PATH" value="1" {if $ACM_BUILD_FULL_PATH}checked{/if}> Twórz pełną ścieżkę kategorii</label></div>
          <div class="checkbox"><label><input type="checkbox" name="ACM_SKIP_DONE" value="1" {if $ACM_SKIP_DONE}checked{/if}> Pomijaj ZROBIONE</label></div>
          <div class="checkbox"><label><input type="checkbox" name="ACM_MARK_DONE_AFTER_ASSIGN" value="1" {if $ACM_MARK_DONE_AFTER_ASSIGN}checked{/if}> Oznaczaj jako ZROBIONE po przypisaniu</label></div>
          <div class="checkbox"><label><input type="checkbox" name="ACM_CHANGE_DEFAULT_CATEGORY" value="1" {if $ACM_CHANGE_DEFAULT_CATEGORY}checked{/if}> Zmieniaj kategorię domyślną</label></div>
          <div class="checkbox"><label><input type="checkbox" name="ACM_DEBUG" value="1" {if $ACM_DEBUG}checked{/if}> Tryb debug</label></div>
        </div>
      </div>
    </div>

    <div class="row">
      <div class="col-md-6">
        <div class="panel">
          <h4>Skanowanie</h4>
          <div class="form-group"><label>Rozmiar partii (AJAX)</label><input type="number" name="ACM_SCAN_CHUNK_SIZE" class="form-control" value="{$ACM_SCAN_CHUNK_SIZE|intval}"></div>
          <div class="form-group"><label>Maks. trafień (kategorii) na produkt</label><input type="number" name="ACM_MAX_RESULTS_PER_PRODUCT" class="form-control" value="{$ACM_MAX_RESULTS_PER_PRODUCT|intval}"></div>
        </div>
      </div>
      <div class="col-md-6">
        <div class="panel">
          <h4>Strategia wyboru kategorii</h4>
          <div class="form-group"><label>Strategia domyślna</label>
            <select name="ACM_DEFAULT_SELECTION_STRATEGY" class="form-control">
              {foreach from=['score','offers'] item=opt}<option value="{$opt}" {if $ACM_DEFAULT_SELECTION_STRATEGY==$opt}selected{/if}>{$opt}</option>{/foreach}
            </select>
          </div>
          <div class="form-group"><label>Tryb zapisu</label>
            <select name="ACM_SELECT_MODE" class="form-control">
              <option value="best" {if $ACM_SELECT_MODE=='best'}selected{/if}>Zapisuj jedną (najlepszą)</option>
              <option value="all" {if $ACM_SELECT_MODE=='all'}selected{/if}>Zapisuj wszystkie</option>
            </select>
          </div>
        </div>
      </div>
    </div>

    <button type="submit" name="submitAcmConfig" class="btn btn-primary"><i class="icon-save"></i> Zapisz ustawienia</button>
    <a class="btn btn-default" href="{$admin_manager|escape:'html'}"><i class="icon-chevron-right"></i> Przejdź do panelu</a>
  </form>
</div>
