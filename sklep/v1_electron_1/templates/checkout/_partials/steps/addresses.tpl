{**
 * 2007-2025 PrestaShop
 * ...
 *}
{strip}
{extends file='checkout/_partials/steps/checkout-step.tpl'}

{block name='step_content'}
  <div class="js-address-form">
    <form
      method="POST"
      action="{$urls.pages.order}"
      data-refresh-url="{url entity='order' params=['ajax' => 1, 'action' => 'addressForm']}"
    >
<BR>
      
        <h2 class="h4">{l s='ADRES DOSTAWY' d='Shop.Theme.Checkout'}</h2>
 

    
  {* USUNIĘTO BLOK INFORMACYJNY "The selected address will be used both..." *}

      {if $show_delivery_address_form}
        <div id="delivery-address">
          {render file                      = 'checkout/_partials/address-form.tpl'
                  ui                   
     = $address_form
                  use_same_address          = $use_same_address
                  type                      = "delivery"
                  form_has_continue_button  = $form_has_continue_button
   
      
  }
        </div>
      {elseif $customer.addresses|count > 0}
        <div id="delivery-addresses" class="address-selector js-address-selector">
          {include  file        = 'checkout/_partials/address-selector-block.tpl'
                    addresses   = $customer.addresses
                    
name      
   = "id_address_delivery"
                    selected    = $id_address_delivery
                    type        = "delivery"
                    interactive = !$show_delivery_address_form and !$show_invoice_address_form
          }
   
     </div>

 
        {if isset($delivery_address_error)}
          <p class="alert alert-danger js-address-error" name="alert-delivery" id="id-failure-address-{$delivery_address_error.id_address}">{$delivery_address_error.exception}</p>
        {else}
          <p class="alert alert-danger js-address-error" name="alert-delivery" style="display: none">{l s="Your address is incomplete, please update it." d="Shop.Notifications.Error"}</p>
        {/if}

        <p class="add-address">
          <a href="{$new_address_delivery_url}"><i class="material-icons">&#xE145;</i>{l s='add new address' d='Shop.Theme.Actions'}</a>
        </p>

        {* --- POCZĄTEK POPRAWKI "KOPIUJ DANE" --- *}
        {foreach $customer.addresses as $address}
           
  {if $address.id == $id_address_delivery}
                <div id="js-delivery-address-data"
                     data-firstname="{$address.firstname}"
                     data-lastname="{$address.lastname}"
                     data-company="{$address.company}"
                
      data-vat-number="{$address.vat_number}"
                     data-address1="{$address.address1}"
                     data-postcode="{$address.postcode}"
                     data-city="{$address.city}"
                     data-phone="{$address.phone}"
           
           data-id-country="{$address.id_country}"
                     style="display:none;"
 ></div>
            {/if}
        {/foreach}
        {* --- KONIEC POPRAWKI "KOPIUJ DANE" --- *}

        {* USUNIĘTO LINK "Billing address differs from shipping address" *}

      {/if}


      {* --- CZYSTA LOGIKA BLOKU FAKTURY (BEZ OSZUKIWANIA) --- *}

      {if $show_invoice_address_form}
        {* 1. Jesteśmy w trybie EDYCJI FORMULARZA FAKTURY *}
        {if !$use_same_address}
          <h2 class="h4">{l s='DANE DO FAKTURY' d='Shop.Theme.Checkout'}</h2>
          <div id="invoice-address">
            {render file                      = 'checkout/_partials/address-form.tpl'
   
      
            ui                        = $address_form
                    use_same_address          = $use_same_address
                    type       
      
          = "invoice"
                    form_has_continue_button  = $form_has_continue_button
            }
          </div>
        {/if}
      {else}
        {* 2. Jesteśmy w trybie PODSUMOWANIA (nie edytujemy formularza faktury) *}
       
 {* Pokazuj ten blok ZAWSZE, o ile nie edytujemy formularza DOSTAWY *}
        {if !$show_delivery_address_form && $customer.addresses|count > 0}
          <BR><h2 class="h4">{l s='DANE DO FAKTURY' d='Shop.Theme.Checkout'}</h2>
          <div id="invoice-addresses" class="address-selector js-address-selector">
            
            {* === POCZĄTEK POPRAWKI BŁĘDU SKŁADNI (Wersja 17:52) === *}
            {* Najpierw przypisujemy wartość do zmiennej *}
            {if $use_same_address || $id_address_invoice == 0}
                {$invoice_id_to_show = $id_address_delivery}
            {else}
                {$invoice_id_to_show = $id_address_invoice}
            {/if}

            {include  file        = 'checkout/_partials/address-selector-block.tpl'
                      addresses   = $customer.addresses
                     
 name        = "id_address_invoice"
                      selected    = $invoice_id_to_show
                      type        = "invoice"
                      interactive = !$show_delivery_address_form and !$show_invoice_address_form
      }
            {* === KONIEC POPRAWKI BŁĘDU SKŁADNI === *}
          </div>

          {if isset($invoice_address_error)}
            <p class="alert alert-danger js-address-error" name="alert-invoice" id="id-failure-address-{$invoice_address_error.id_address}">{$invoice_address_error.exception}</p>
          {else}
            <p class="alert alert-danger js-address-error" name="alert-invoice" style="display: none">{l s="Your address is incomplete, please update it." d="Shop.Notifications.Error"}</p>
          {/if}

          <p class="add-address">
            <a href="{$new_address_invoice_url}"><i class="material-icons">&#xE145;</i>{l s='dodaj nowe dane do faktury' d='Shop.Theme.Actions'}</a>
          </p>
        {/if}
      {/if}
      
      {* --- KONIEC POPRAWKI --- *}


      {if !$form_has_continue_button}
        <div class="clearfix">
  
   
      <button type="submit" class="tvall-inner-btn float-xs-right" name="confirm-addresses" value="1">
              <span>{l s='Continue' d='Shop.Theme.Actions'}</span>
          </button>
          <input type="hidden" id="not-valid-addresses" value="{$not_valid_addresses}">
        </div>
      {/if}

    </form>
  </div>
{/block}
{/strip}