{* 
  BIGBIO – Desktop OPC partial with 6 blocks.
  Blocks:
   1 – Cart products table
   2 – Cart summary / loyalty / free shipping
   3 – Account + delivery address (+ invoice details toggle)
   4 – Shipping methods
   5 – Payment methods (methods only; conditions/button move to block 6 via JS)
   6 – Message to seller + conditions + main CTA
*}

{* Pre-capture steps so we can place them where we want *}
{assign var=__personal value=null}
{assign var=__addresses value=null}
{assign var=__delivery value=null}
{assign var=__payment value=null}

{foreach from=$steps item=step key=idx}
  {if $step.identifier == 'checkout-personal-information-step'}
    {capture name='bb_step_personal'}{render identifier=$step.identifier position=($idx+1) ui=$step.ui}{/capture}
    {assign var=__personal value=$smarty.capture.bb_step_personal}
  {/if}
  {if $step.identifier == 'checkout-addresses-step'}
    {capture name='bb_step_addresses'}{render identifier=$step.identifier position=($idx+1) ui=$step.ui}{/capture}
    {assign var=__addresses value=$smarty.capture.bb_step_addresses}
  {/if}
  {if $step.identifier == 'checkout-delivery-step'}
    {capture name='bb_step_delivery'}{render identifier=$step.identifier position=($idx+1) ui=$step.ui}{/capture}
    {assign var=__delivery value=$smarty.capture.bb_step_delivery}
  {/if}
  {if $step.identifier == 'checkout-payment-step'}
    {capture name='bb_step_payment'}{render identifier=$step.identifier position=($idx+1) ui=$step.ui}{/capture}
    {assign var=__payment value=$smarty.capture.bb_step_payment}
  {/if}
{/foreach}

<section id="main" class="bb-opc">
  <div class="container bb-opc-container">

    {* Row 1: Block 1 (cart products) + Block 2 (summary box) *}
    <div class="row bb-opc-row bb-opc-row-top">
      <div class="col-lg-8 bb-opc-block bb-opc-block-1">
        {include file='checkout/_partials/cart-detailed.tpl' cart=$cart}
      </div>
      <div class="col-lg-4 bb-opc-block bb-opc-block-2">
        {include file='checkout/_partials/cart-summary.tpl' cart=$cart}
      </div>
    </div>

    {* Row 2: three columns with blocks 3,4,5 *}
    <div class="row bb-opc-row bb-opc-row-mid">
      <div class="col-lg-4 bb-opc-block bb-opc-block-3">
        <h3 class="bb-opc-title"><span>1</span> Konto i adres</h3>
        {$__personal nofilter}
        {$__addresses nofilter}

        {* Invoice toggle placeholder – markup only, JS handles show/hide *}
        <div id="bb-invoice-toggle" class="card bb-card mt-3">
          <div class="card-header bb-toggle-header">
            <label class="mb-0"><input type="checkbox" id="bb-invoice-different"> Inne dane na fakturze</label>
          </div>
          <div id="bb-invoice-box" class="card-body" style="display:none;">
            <div class="bb-copy-row">
              <button type="button" class="btn btn-outline-secondary btn-sm" id="bb-invoice-copy">Kopiuj dane z dostawy</button>
            </div>
            <div class="bb-type-row mt-2">
              <label class="mr-3"><input type="radio" name="bb_invoice_type" value="company" checked> na firmę</label>
              <label><input type="radio" name="bb_invoice_type" value="private"> Osobę prywatną</label>
            </div>
            {* Placeholder for invoice address fields – we rely on Presta's second address slot.
               If you already have a second address form, keep it hidden by default and JS will toggle it.
            *}
            <div id="bb-invoice-fields" class="mt-3">
              {* You can inject your existing invoice form via hook or include if available *}
              {hook h='displayCheckoutAdditionalInformation' mod='bb'}
            </div>
          </div>
        </div>
      </div>

      <div class="col-lg-4 bb-opc-block bb-opc-block-4">
        <h3 class="bb-opc-title"><span>2</span> Metody dostawy</h3>
        {$__delivery nofilter}
        <div class="bb-message card mt-3">
          <div class="card-header">Zostaw wiadomość</div>
          <div class="card-body">
            <textarea class="form-control" id="delivery_message" name="delivery_message" rows="3" placeholder="Jeżeli chcesz dodać komentarz do zamówienia, wpisz go poniżej.">{$delivery_message|default:''}</textarea>
          </div>
        </div>
      </div>

      <div class="col-lg-4 bb-opc-block bb-opc-block-5">
        <h3 class="bb-opc-title"><span>3</span> Wybierz metodę płatności</h3>
        <div class="bb-payment-only">
          {$__payment nofilter}
        </div>
      </div>
    </div>

    {* Row 3: Block 6 – conditions + CTA (we move elements here by JS) *}
    <div class="row bb-opc-row bb-opc-row-bottom">
      <div class="col-lg-12 bb-opc-block bb-opc-block-6">
        <div id="bb-conditions-slot"></div>
        <div id="bb-payment-confirmation-slot" class="mt-3"></div>
      </div>
    </div>
  </div>
</section>