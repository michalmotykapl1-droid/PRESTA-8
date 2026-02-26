{*
 * Często kupowane razem – PREMIUM STYLE (Karta Produktu)
 * Hook: displayBBFrequentlyBoughtTogether
 *}
{if isset($fbt_products) && $fbt_products}
<section class="fbt-box-container">
    
    <div class="fbt-header-strip">
        <h3 class="fbt-title">{$fbt_title|escape:'html':'UTF-8'}</h3>
    </div>

    <div class="fbt-grid-wrapper">
      
      {* 1. GŁÓWNY PRODUKT (OGLĄDASZ) *}
      <div class="fbt-product-cell" data-price="{$product.price_amount}">
        <div class="fbt-badge" style="background:#eee; color:#777; border:1px solid #ddd; padding:2px 5px; border-radius:2px;">OGLĄDASZ</div>
        
        <div class="fbt-img-wrap">
           <img src="{$product.cover.bySize.home_default.url}" 
                alt="{$product.name|escape:'html':'UTF-8'}" 
                loading="lazy">
        </div>
        
        <h4 class="fbt-name">
            <a href="{$product.url}" title="{$product.name}">{$product.name|truncate:50:'...'}</a>
        </h4>
        <div class="fbt-price">{$product.price}</div>

        {* Ukryty formularz dla GŁÓWNEGO produktu (Tu dodajemy WSZYSTKO) *}
        <form action="{$urls.pages.cart}" method="post" class="fbt-hidden-form">
            <input type="hidden" name="token" value="{$static_token}">
            <input type="hidden" name="id_product" value="{$product.id_product}">
            <input type="hidden" name="qty" value="1">
        </form>

        <div class="fbt-plus-absolute">+</div>
      </div>

      {* PĘTLA PO DODATKOWYCH PRODUKTACH *}
      {foreach from=$fbt_products item="acc" name=fbtLoop}
        
        <div class="fbt-product-cell" data-id-product="{$acc.id_product}" data-price="{$acc.price_amount}">
            <div class="fbt-badge">POLECAMY</div>
            
            <div class="fbt-img-wrap">
                <a href="{$acc.url}">
                    <img src="{$acc.cover.bySize.home_default.url}" 
                         alt="{$acc.name|escape:'html':'UTF-8'}" 
                         loading="lazy">
                </a>
            </div>
            
            <h4 class="fbt-name">
                <a href="{$acc.url}" title="{$acc.name}">{$acc.name|truncate:50:'...'}</a>
            </h4>
            <div class="fbt-price">{$acc.price}</div>
            
            {* Ukryty formularz dla DODATKU *}
            <form action="{$urls.pages.cart}" method="post" class="fbt-hidden-form">
                <input type="hidden" name="token" value="{$static_token}">
                <input type="hidden" name="id_product" value="{$acc.id_product}">
                <input type="hidden" name="qty" value="1">
            </form>

            {if !$smarty.foreach.fbtLoop.last}
                <div class="fbt-plus-absolute">+</div>
            {/if}
        </div>
      {/foreach}

      {* PODSUMOWANIE *}
      <div class="fbt-summary-cell">
             {* ZMIANA: Tekst spójny z modalem *}
             <span class="fbt-total-label">RAZEM TYLKO:</span>
             <div class="fbt-total-val" id="fbt-total-price">...</div>
             
             {* ZMIANA: Tekst przycisku i wyśrodkowanie (flex) *}
             <button class="btn btn-primary fbt-btn-action" id="fbt-add-all-btn" style="display:flex; justify-content:center; align-items:center; width:100%;">
                DODAJ KOMPLET
             </button>
      </div>

    </div>
</section>

{* --- MODAL POTWIERDZENIA (PREMIUM STYLE - UKRYTY) --- *}
{* Ten kod musi tu zostać, aby modal się w ogóle pojawiał po kliknięciu na karcie produktu *}
<div id="fbt-modal" class="modal fade" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document"> 
    <div class="modal-content">
      
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Zamknij">&times;</button>
        <div class="modal-title">
            <div class="fbt-success-icon-large">
                <i class="material-icons">&#xE876;</i>
            </div>
            <span>Produkty zostały poprawnie dodane do koszyka</span>
        </div>
      </div>

      <div class="modal-body">
         <div class="fbt-modal-products">
             {* Tu JS wstrzyknie listę *}
         </div>
         
         <div class="fbt-modal-total-info">
             {* Tu JS wstrzyknie podsumowanie *}
         </div>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">
            Kontynuuj zakupy
        </button>
        <a href="{$urls.pages.cart}" class="btn btn-primary">
            Przejdź do realizacji zamówienia <i class="material-icons">&#xE315;</i>
        </a>
      </div>

    </div>
  </div>
</div>
{/if}