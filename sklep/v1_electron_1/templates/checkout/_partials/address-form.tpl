{**
 * 2007-2025 PrestaShop
 * ... (licencja) ...
 *}
{strip}
{extends file='customer/_partials/address-form.tpl'}

{block name='address_form_url'}
    {* Zostawiamy ten zagnieżdżony formularz - tak działa PrestaShop *}
    <form method="POST" action="{url entity='order' params=['id_address' => $id_address]}" data-id-address="{$id_address}" data-refresh-url="{url entity='order' params=['ajax' => 1, 'action' => 'addressForm']}">
{/block}

{block name='form_fields'}
    
    {* 1. PRZEŁĄCZNIK OSOBA / FIRMA (Twoja modyfikacja - Zostaje) *}
    <div class="address-type-tabs">
        <div class="tabs-wrapper">
            <label class="tab-item">
            
 
    <input type="radio" name="client_type_{$type}" value="individual" checked="checked" class="client-type-input">
                <span class="tab-text">{l s='Osoba prywatna' d='Shop.Theme.Checkout'}</span>
            </label>
            
            <label class="tab-item">
                <input type="radio" name="client_type_{$type}" value="business" class="client-type-input">
              
 
  <span class="tab-text">{l s='Firma' d='Shop.Theme.Checkout'}</span>
            </label>
        </div>
    </div>

    {* PRZYCISK KOPIOWANIA (Twoja modyfikacja - Zostaje) *}
    {if $type === "invoice"}
        <div class="form-group row">
            <div class="col-md-12">
                <div class="btn-copy-address js-copy-delivery-address" role="button">
           
 
         <i class="material-icons">content_copy</i>
                    <span>{l s='Skopiuj dane z adresu dostawy' d='Shop.Theme.Checkout'}</span>
                </div>
            </div>
        </div>
    {/if}

    {* === POCZĄTEK POPRAWKI "TWOJA NAZWA" (Rozwiązanie 2) === *}
    {* PROBLEM: $address_form.fields.other.value nie jest poprawnie wypełniane przy EDYCJI.
        ROZWIĄZANIE: Wyszukujemy ręcznie wartość 'other' z tablicy $customer.addresses,
                     która jest dostępna w nadrzędnym szablonie i zawiera aktualne dane.
    *}
    {$current_other_value = ''}
    {if isset($address_form.fields.other.value)}
        {$current_other_value = $address_form.fields.other.value}
    {/if}

    {if isset($id_address) && $id_address && isset($customer.addresses)}
        {foreach $customer.addresses as $cust_address}
            {if $cust_address.id == $id_address && !empty($cust_address.other)}
                {$current_other_value = $cust_address.other}
            {/if}
        {/foreach}
    {/if}

    <div class="form-group row">
        <label class="col-md-12 form-control-label">{l s='Twoja nazwa (opcjonalnie)' d='Shop.Theme.Checkout'}</label>
        <div class="col-md-12">
             <input class="form-control" name="other" type="text" value="{$current_other_value|escape:'htmlall':'UTF-8'}" placeholder="{l s='Np. Mój dom, Praca, Firma Żony' d='Shop.Theme.Checkout'}">
        </div>
    </div>
    {* === KONIEC POPRAWKI "TWOJA NAZWA" === *}
    
    {* --- POCZĄTEK KRYTYCZNEJ POPRAWKI --- *}
    {* Musimy ręcznie dodać ukryte pole ID, aby edycja działała poprawnie *}
    {if isset($id_address) && $id_address}
        <input type="hidden" name="id_address" value="{$id_address}">
    {/if}
    {* --- KONIEC KRYTYCZNEJ POPRAWKI --- *}

    {* 2. PĘTLA PÓL FORMULARZA (Twoje modyfikacje - Zostają) *}
    {foreach from=$formFields item="field"}
        
        {* Alias (Zostaje ukryty - kluczowe dla filtrowania) *}
        {if $field.name eq "alias"}
    
        {if $type === "invoice"}
                <input type="hidden" name="alias" value="{l s='Dane do faktury' d='Shop.Theme.Checkout'}">
            {else}
       
                 <input type="hidden" name="alias" value="{l s='Adres dostawy' d='Shop.Theme.Checkout'}">
            {/if}

        {* Ukryte Address2 *}
        {elseif $field.name eq "address2"}
            {* Celowe pominięcie *}

        {* Usunięcie 'other' z pętli, bo dodaliśmy je ręcznie powyżej *}
        {elseif $field.name eq "other"}
       
              {* Celowe pominięcie *}
            
        {* Telefon *}
        {elseif $field.name eq "phone"}
            {$field.required = true}
            {$field.maxLength = 9}
            {$field.minLength = 9}
        
    <div class="form-group row">
  
                 <label class="col-md-12 form-control-label required">{$field.label}</label>
                <div class="col-md-12">
                    <input class="form-control" name="{$field.name}" type="tel" value="{$field.value}" maxlength="9" placeholder="123456789" title="{l s='Proszę podać 9-cyfrowy numer telefonu' d='Shop.Theme.Checkout'}">
                </div>
         
   </div>

     
       {* Adres (Ulica + Numer) *}
        {elseif $field.name eq "address1"}
            {$field.required = true}
            <div class="form-group row address-split-wrapper" id="{$field.name}-container">
                <label class="col-md-12 form-control-label {if $field.required}required{/if}">{l s='ADRES' d='Shop.Theme.Checkout'}</label>
                 <div class="col-md-12 address-fields-row">
  
        
                   <input type="hidden" name="{$field.name}" id="input_{$field.name}_{$type}" value="{$field.value}" {if $field.required}required{/if}>
                    <div class="address-street-field">
                        <label for="street_name_{$type}" class="address-sub-label required-mark-sub">{l s='Ulica' d='Shop.Theme.Checkout'}</label>
                         <input class="form-control address-field-part" type="text" id="street_name_{$type}" maxlength="{$field.maxLength}" data-target-input="input_{$field.name}_{$type}" placeholder="{l s='Np. Mickiewicza' d='Shop.Theme.Checkout'}" data-is-street="true">
                    </div>
                    <div class="address-number-field">
                        <label for="house_number_{$type}" class="address-sub-label required-mark-sub">{l s='Nr domu / lokalu' d='Shop.Theme.Checkout'}</label>
                 
       <input class="form-control address-field-part" type="text" id="house_number_{$type}" maxlength="10" data-target-input="input_{$field.name}_{$type}" placeholder="12/3A" required>
                    </div>
                </div>
                {if isset($field.errors) && $field.errors}
                    <div class="col-md-12">{include file='_partials/form-errors.tpl' errors=$field.errors}</div>
       
        
             {/if}
            </div>

        {* Firma *}
        {elseif $field.name eq "company"}
            {$field.required = false}
            <div data-js-type="business-field" class="form-group row">
                 <label class="col-md-3 form-control-label {if $field.required}required{/if}">{$field.label}</label>
       
        
             <div class="col-md-9 js-input-column">
                     <input class="form-control" name="{$field.name}" type="text" value="{$field.value}">
                     {if !empty($field.errors)}{include file='_partials/form-errors.tpl' errors=$field.errors}{/if}
                 </div>
            </div>
        {* NIP *}
        
     {elseif $field.name eq "vat_number"}
             {$field.required = false}
            <div data-js-type="business-field" class="form-group row">
                 <label class="col-md-3 form-control-label {if $field.required}required{/if}">{$field.label}</label>
                 <div class="col-md-9 js-input-column">
                   
  <input class="form-control" name="{$field.name}" type="text" value="{$field.value}">
  
                       {if !empty($field.errors)}{include file='_partials/form-errors.tpl' errors=$field.errors}{/if}
                 </div>
            </div>
            
        {else}
            {form_field field=$field}
        
{/if}

    {/foreach}

    
 <input type="hidden" name="saveAddress" value="{$type}">
    
    {* --- POPRAWKA: Usunięcie "TAK/NIE" i wstawienie oryginalnego checkboxa Presty --- *}
    {if $type === "delivery"}
        <div class="form-group row" style="margin-top: 30px;">
            <div class="col-md-12">
                <span class="custom-checkbox">
                    <input name="use_same_address" id="use_same_address_checkbox" type="checkbox" value="1" {if $use_same_address}checked{/if}>
   
                      <span><i class="material-icons rtl-no-flip checkbox-checked">&#xE5CA;</i></span>
                    <label for="use_same_address_checkbox">{l s='Użyj tych danych również do wystawienia faktury' d='Shop.Theme.Checkout'}</label>
                </span>
            </div>
        </div>
    {/if}
  
  {* --- KONIEC POPRAWKI --- *}

{/block}

{block name='form_buttons'}
    {if !$form_has_continue_button}
        <div class="footer-flex-wrapper">
            <div class="required-field-note">
                <span class="red-star">*</span> - {l s='pole obowiązkowe' d='Shop.Theme.Checkout'}
            </div>
            
            {* --- POPRAWKA KRYTYCZNA: Dodanie name="submitAddress" --- *}
        
             <button type="submit" class="tvall-inner-btn float-xs-right" name="submitAddress" value="1">
                <span>{l s='ZAPISZ DANE' d='Shop.Theme.Actions'}</span>
            </button>
        </div>
        {if $id_address}
        <a class="js-cancel-address cancel-address float-xs-right text-muted" style="margin-right: 20px; margin-top: 15px;" href="{url entity='order' params=['cancelAddress' => {$type}]}">
            {l s='Anuluj' d='Shop.Theme.Actions'}
        </a>
        {/if}
    {else}
        <div class="footer-flex-wrapper">
            <div class="required-field-note">
                <span class="red-star">*</span> - {l s='pole obowiązkowe' d='Shop.Theme.Checkout'}
            </div>
    
 

            {* --- POPRAWKA KRYTYCZNA: Zmiana "confirm-addresses" na "submitAddress" --- *}
            <button type="submit" class="continue tvall-inner-btn float-xs-right" name="submitAddress" value="1">
                <span>{l s='PRZEJDŹ DALEJ' d='Shop.Theme.Actions'}</span>
            </button>
            
            {if $customer.addresses|count > 0}
   
 
              <a class="js-cancel-address cancel-address float-xs-right text-muted" style="margin-right: 20px;" href="{url entity='order' params=['cancelAddress' => {$type}]}">
                     {l s='Anuluj' d='Shop.Theme.Actions'}
                </a>
            {/if}
        </div>
    {/if}
{/block}
{/strip}