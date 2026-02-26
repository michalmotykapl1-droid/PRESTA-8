{* KROK 1 *}
<div class="panel dmto">
  <div class="panel-heading"><i class="icon-magic"></i> Krok 1: Wstępna analiza i dodawanie cech (dla produktów non‑BP)</div>
  <div class="panel-body">
    <p class="alert alert-info">
      Skrypt analizuje nazwy i opisy we <b>wszystkich językach</b>, dziedziczy diety po <b>EAN</b>/<b>rdzeniu SKU</b>,
      a gdy trzeba – dopisuje je na podstawie <b>kategorii/tagów</b> oraz <b>słów kluczowych</b> (np. „bez glutenu”, „wegańskie”, „bio”).
    </p>

    <form method="post" class="form-horizontal">
      <input type="hidden" name="token" value="{$token}">

      <div class="form-group">
        <label class="control-label col-lg-3"></label>
        <div class="col-lg-9">
          <div class="checkbox">
            <label><input type="checkbox" name="dmto_step1_force" value="1"> Przelicz wszystko (zamiast tylko brakujących)</label>
          </div>
        </div>
      </div>

      <div class="form-group">
        <label class="control-label col-lg-3"></label>
        <div class="col-lg-9">
          <button class="btn btn-primary" name="submitAnalyzeAddFeatures">
            <i class="icon-play"></i> Uruchom analizę i dodawanie cech
          </button>
        </div>
      </div>
    </form>
  </div>
</div>
