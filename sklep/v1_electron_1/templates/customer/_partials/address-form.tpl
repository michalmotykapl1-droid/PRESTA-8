{**
 * 2007-2025 PrestaShop
 * customer/_partials/address-form.tpl - FINAL VERSION (Design + Validation Fix)
 *}
{block name='address_form'}
  
  {literal}
  <style>
      :root {
          --brand-color: #d01662;
          --text-dark: #222;
          --text-light: #555;
          --input-border: #e5e5e5;
          --bg-light: #f4f6f8;
      }

      /* --- NOWY STYL PRZEŁĄCZNIKA (SEGMENTED CONTROL) --- */
      .address-type-tabs .tabs-wrapper {
          display: flex;
          background: var(--bg-light);
          padding: 5px;
          border-radius: 8px;
          margin-bottom: 30px;
          border: 1px solid #eee;
      }
      .address-type-tabs .tab-item {
          flex: 1;
          text-align: center;
          padding: 12px 0;
          cursor: pointer;
          font-weight: 700;
          color: #777;
          position: relative;
          display: block;
          transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
          font-size: 13px;
          text-transform: uppercase;
          letter-spacing: 1px;
          border-radius: 6px;
          margin-bottom: 0;
      }
      .address-type-tabs .tab-item input { display: none; }
      
      /* Stan AKTYWNY (Wybrany) */
      .address-type-tabs .tab-item.active-tab {
          background-color: var(--brand-color);
          color: #fff;
          box-shadow: 0 4px 10px rgba(208, 22, 98, 0.25);
      }
      .address-type-tabs .tab-item.active-tab::after { display: none; }

      /* --- FIX NA PUSTE MIEJSCA --- */
      .form-group.row:empty { display: none; }
      .form-group.row input[type="hidden"] { display: none; }

      /* --- STYLE PÓL --- */
      .form-group.row { margin-bottom: 15px; }
      
      .form-group label {
          font-size: 11px; font-weight: 700; text-transform: uppercase;
          color: var(--text-light); margin-bottom: 6px; display: block; text-align: left; letter-spacing: 0.5px;
      }
      
      .form-control, .form-control-select {
          background: #fff; border: 1px solid var(--input-border);
          border-radius: 6px; padding: 10px 15px; height: 45px;
          font-size: 14px; color: var(--text-dark); width: 100%;
          transition: border-color 0.2s; outline: none; box-shadow: none !important;
      }
      .form-control:focus, .form-control-select:focus { border-color: var(--brand-color); }

      .red-star { color: var(--brand-color); margin-left: 2px; font-weight: bold; font-size: 12px; }
      .company-star { display: none; color: var(--brand-color); margin-left: 2px; font-weight: bold; font-size: 12px; }
      
      .address-fields-row { display: flex; gap: 20px; }
      .address-street-field { flex: 3; }
      .address-number-field { flex: 1; }
      .business-field-hidden { display: none !important; }

      .form-control-select { appearance: none; -webkit-appearance: none; background-image: url("data:image/svg+xml;charset=utf-8,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%23333' d='M6 8.825L1.175 4 2.238 2.938 6 6.7l3.763-3.762L10.825 4z'/%3E%3C/svg%3E"); background-repeat: no-repeat; background-position: right 15px center; }

      /* Ukrywanie labeli Presty */
      .select-field-wrapper .form-group label,
      .select-field-wrapper .form-control-label { display: none !important; }
      .select-field-wrapper .form-group { margin-bottom: 0 !important; }
      .select-field-wrapper .col-md-9, .select-field-wrapper .col-md-3 { padding: 0; flex: 0 0 100%; max-width: 100%; }

      /* --- PRZYCISK ZAPISZ (Elegancki, po prawej) --- */
      .form-footer { margin-top: 40px; padding-top: 20px; border-top: 1px solid #f5f5f5; display: flex; justify-content: flex-end; }
      .btn-save-custom {
          background-color: #fff;
          border: 1px solid var(--brand-color);
          color: var(--brand-color);
          border-radius: 4px;
          padding: 12px 40px;
          height: auto;
          min-width: 160px;
          font-weight: 800;
          text-transform: uppercase;
          letter-spacing: 1px;
          font-size: 13px;
          transition: all 0.3s ease;
          cursor: pointer;
          display: inline-flex;
          align-items: center;
          justify-content: center;
      }
      .btn-save-custom:hover { background-color: var(--brand-color); color: #fff; box-shadow: 0 5px 15px rgba(208, 22, 98, 0.2); }
  </style>

  <script>
      document.addEventListener("DOMContentLoaded", function() {
          
          // --- 1. LOGIKA PRZEŁĄCZANIA + OBOWIĄZKOWE POLA ---
          function toggleBusinessFields(isBusiness) {
              const businessFields = document.querySelectorAll('[data-js-type="business-field"]');
              const tabs = document.querySelectorAll('.tab-item');
              
              const companyInput = document.querySelector('input[name="company"]');
              const vatInput = document.querySelector('input[name="vat_number"]');
              const companyStars = document.querySelectorAll('.company-star');

              // Pokaż/Ukryj pola
              businessFields.forEach(field => {
                  if (isBusiness) { field.classList.remove('business-field-hidden'); } 
                  else { field.classList.add('business-field-hidden'); }
              });

              // Obsługa wymagalności (Required)
              if (isBusiness) {
                  if(companyInput) companyInput.setAttribute('required', 'required');
                  if(vatInput) vatInput.setAttribute('required', 'required');
                  companyStars.forEach(star => star.style.display = 'inline');
              } else {
                  if(companyInput) companyInput.removeAttribute('required');
                  if(vatInput) vatInput.removeAttribute('required');
                  companyStars.forEach(star => star.style.display = 'none');
              }

              // Przełączanie stylu przycisków
              tabs.forEach(tab => {
                  const input = tab.querySelector('input');
                  if ((input.value === 'business' && isBusiness) || (input.value === 'individual' && !isBusiness)) {
                      tab.classList.add('active-tab'); 
                      input.checked = true;
                  } else { 
                      tab.classList.remove('active-tab'); 
                  }
              });
          }

          // --- 2. INTELIGENTNE AUTO-WYKRYWANIE PRZY EDYCJI ---
          const companyField = document.querySelector('input[name="company"]');
          const vatField = document.querySelector('input[name="vat_number"]');
          let startAsBusiness = false;

          if (companyField && companyField.value.trim() !== "") { startAsBusiness = true; }
          if (vatField && vatField.value.trim() !== "") { startAsBusiness = true; }

          toggleBusinessFields(startAsBusiness);


          // --- 3. WALIDACJA NIP ---
          if (vatField) {
              vatField.setAttribute('maxlength', '10');
              vatField.setAttribute('minlength', '10');
              vatField.setAttribute('pattern', '[0-9]{10}');
              vatField.setAttribute('title', 'NIP musi składać się z 10 cyfr');
              vatField.addEventListener('input', function(e) {
                  this.value = this.value.replace(/[^0-9]/g, '').slice(0, 10);
              });
          }

          // --- 4. WALIDACJA TELEFONU (PRZYWRÓCONA) ---
          const phoneInput = document.querySelector('input[name="phone"]');
          if (phoneInput) {
              phoneInput.setAttribute('maxlength', '9');
              phoneInput.setAttribute('minlength', '9');
              phoneInput.setAttribute('pattern', '[0-9]{9}');
              phoneInput.setAttribute('title', 'Numer telefonu musi składać się z 9 cyfr');
              
              phoneInput.addEventListener('input', function(e) {
                  // Usuń wszystko co nie jest cyfrą
                  this.value = this.value.replace(/[^0-9]/g, '');
                  // Obetnij do 9 znaków
                  if (this.value.length > 9) {
                      this.value = this.value.slice(0, 9);
                  }
              });
          }

          // --- 5. OBSŁUGA KLIKNIĘĆ ---
          const radioInputs = document.querySelectorAll('.client-type-input');
          radioInputs.forEach(input => {
              input.addEventListener('change', function() { toggleBusinessFields(this.value === 'business'); });
              input.closest('.tab-item').addEventListener('click', function() {
                  this.querySelector('input').checked = true;
                  this.querySelector('input').dispatchEvent(new Event('change'));
              });
          });

          // --- 6. FIX ADRESU ---
          const fullAddressInput = document.querySelector('input[name="address1"]');
          const streetInput = document.querySelector('.address-field-part[data-is-street="true"]');
          const numberInput = document.querySelector('.address-field-part[placeholder="12/3A"]');

          if (fullAddressInput && fullAddressInput.value && streetInput && numberInput) {
              if (!streetInput.value && !numberInput.value) {
                  const val = fullAddressInput.value.trim();
                  const match = val.match(/^(.*)\s+(\d+[a-zA-Z]*\/?\d*[a-zA-Z]*)$/);
                  if (match) { streetInput.value = match[1]; numberInput.value = match[2]; } 
                  else { streetInput.value = val; }
              }
          }
          function updateHiddenAddress() {
              if (streetInput && numberInput && fullAddressInput) {
                  fullAddressInput.value = streetInput.value + ' ' + numberInput.value;
              }
          }
          if(streetInput) streetInput.addEventListener('input', updateHiddenAddress);
          if(numberInput) numberInput.addEventListener('input', updateHiddenAddress);
      });
  </script>
  {/literal}

  <div class="js-address-form">
    {include file='_partials/form-errors.tpl' errors=$errors['']}

    {if !isset($type)} {assign var="type" value="customer_address"} {/if}

    {block name='address_form_url'}
      <form
        method="POST"
        action="{url entity='address' params=['id_address' => $id_address]}"
        data-id-address="{$id_address}"
        data-refresh-url="{url entity='address' params=['ajax' => 1, 'action' => 'addressForm']}"
      >
    {/block}

      {block name='address_form_fields'}
        <section class="form-fields">
          {block name='form_fields'}
            
            {* NOWE MENU KAFELKOWE *}
            <div class="address-type-tabs">
                <div class="tabs-wrapper">
                    <label class="tab-item active-tab">
                        <input type="radio" name="client_type_{$type}" value="individual" class="client-type-input">
                        <i class="fa-regular fa-user" style="margin-right:8px;"></i> {l s='Osoba prywatna' d='Shop.Theme.Checkout'}
                    </label>
                    <label class="tab-item">
                        <input type="radio" name="client_type_{$type}" value="business" class="client-type-input">
                        <i class="fa-solid fa-briefcase" style="margin-right:8px;"></i> {l s='Firma' d='Shop.Theme.Checkout'}
                    </label>
                </div>
            </div>

            {* ALIAS *}
            {$current_alias_value = ''}
            {foreach from=$formFields item="field_check"}
                {if $field_check.name == 'alias'} {$current_alias_value = $field_check.value} {/if}
            {/foreach}
            
            <div class="form-group row">
                <label class="col-md-12 form-control-label">{l s='Nazwa adresu (opcjonalnie)' d='Shop.Theme.Checkout'}</label>
                <div class="col-md-12">
                     <input class="form-control" name="alias" type="text" value="{$current_alias_value|escape:'htmlall':'UTF-8'}" placeholder="{l s='Np. Dom, Praca' d='Shop.Theme.Checkout'}">
                </div>
            </div>

            {* PĘTLA PÓL *}
            {foreach from=$formFields item="field"}
                {if $field.type == 'hidden'}
                    <input type="hidden" name="{$field.name}" value="{$field.value}">
                
                {elseif $field.name eq "alias"}{* POMIŃ *}
                {elseif $field.name eq "address2"}{* POMIŃ *}
                {elseif $field.name eq "other"}{* POMIŃ *}

                {elseif $field.name eq "phone"}
                    <div class="form-group row">
                         {* Telefon OBOWIĄZKOWY - 9 CYFR *}
                         <label class="col-md-12 form-control-label">{$field.label} <span class="red-star">*</span></label>
                         <div class="col-md-12">
                            <input class="form-control" name="{$field.name}" type="tel" value="{$field.value}" maxlength="9" placeholder="123456789" required>
                        </div>
                   </div>

                {elseif $field.name eq "address1"}
                    <div class="form-group row address-split-wrapper" id="{$field.name}-container">
                        <label class="col-md-12 form-control-label">{l s='ADRES' d='Shop.Theme.Checkout'} <span class="red-star">*</span></label>
                         <div class="col-md-12 address-fields-row">
                           <input type="hidden" name="{$field.name}" id="input_{$field.name}_{$type}" value="{$field.value}" required>
                            <div class="address-street-field">
                                <label for="street_name_{$type}" style="font-size:10px; color:#999; margin-bottom:2px;">{l s='Ulica' d='Shop.Theme.Checkout'}</label>
                                 <input class="form-control address-field-part" type="text" id="street_name_{$type}" maxlength="{$field.maxLength}" placeholder="{l s='Np. Mickiewicza' d='Shop.Theme.Checkout'}" data-is-street="true">
                            </div>
                            <div class="address-number-field">
                                <label for="house_number_{$type}" style="font-size:10px; color:#999; margin-bottom:2px;">{l s='Nr domu / lokalu' d='Shop.Theme.Checkout'}</label>
                                <input class="form-control address-field-part" type="text" id="house_number_{$type}" maxlength="10" placeholder="12/3A" required>
                            </div>
                        </div>
                        {if isset($field.errors) && $field.errors}<div class="col-md-12">{include file='_partials/form-errors.tpl' errors=$field.errors}</div>{/if}
                    </div>

                {elseif $field.name eq "company"}
                    <div data-js-type="business-field" class="form-group row business-field-hidden">
                         <label class="col-md-12 form-control-label">{$field.label} <span class="company-star">*</span></label>
                         <div class="col-md-12 js-input-column">
                             <input class="form-control" name="{$field.name}" type="text" value="{$field.value}">
                         </div>
                     </div>
                {elseif $field.name eq "vat_number"}
                    <div data-js-type="business-field" class="form-group row business-field-hidden">
                         <label class="col-md-12 form-control-label">{$field.label} <span class="company-star">*</span></label>
                         <div class="col-md-12 js-input-column">
                           <input class="form-control" name="{$field.name}" type="text" value="{$field.value}" placeholder="10 cyfr">
                         </div>
                    </div>

                {else}
                    {* KRAJ - RĘCZNY SELECT *}
                    {if $field.name eq 'id_country'}
                        <div class="form-group row">
                            <label class="col-md-12 form-control-label">{$field.label} <span class="red-star">*</span></label>
                            <div class="col-md-12">
                                <select class="form-control form-control-select js-country" name="{$field.name}" required>
                                    <option value disabled selected>{l s='-- wybierz --' d='Shop.Theme.Actions'}</option>
                                    {foreach from=$field.availableValues item="label" key="value"}
                                        <option value="{$value}" {if $value eq $field.value} selected {/if}>{$label}</option>
                                    {/foreach}
                                </select>
                            </div>
                             {if isset($field.errors) && $field.errors}<div class="col-md-12">{include file='_partials/form-errors.tpl' errors=$field.errors}</div>{/if}
                        </div>
                    
                    {* WOJEWÓDZTWO *}
                    {elseif $field.name eq 'id_state'}
                         <div class="form-group row">
                            <label class="col-md-12 form-control-label">{$field.label} <span class="red-star">*</span></label>
                            <div class="col-md-12">
                                <select class="form-control form-control-select js-state" name="{$field.name}" required>
                                    <option value disabled selected>{l s='-- wybierz --' d='Shop.Theme.Actions'}</option>
                                    {foreach from=$field.availableValues item="label" key="value"}
                                        <option value="{$value}" {if $value eq $field.value} selected {/if}>{$label}</option>
                                    {/foreach}
                                </select>
                            </div>
                             {if isset($field.errors) && $field.errors}<div class="col-md-12">{include file='_partials/form-errors.tpl' errors=$field.errors}</div>{/if}
                        </div>

                    {* STANDARDOWE POLA *}
                    {else}
                        <div class="form-group row">
                            {if $field.type !== 'select' && $field.type !== 'radio-buttons'}
                                <label class="col-md-12 form-control-label">{$field.label} <span class="red-star">*</span></label>
                            {/if}
                            <div class="col-md-12">
                                <input class="form-control" name="{$field.name}" type="{$field.type}" value="{$field.value}" required>
                            </div>
                             {if isset($field.errors) && $field.errors}<div class="col-md-12">{include file='_partials/form-errors.tpl' errors=$field.errors}</div>{/if}
                        </div>
                    {/if}
                {/if}
            {/foreach}
            <input type="hidden" name="saveAddress" value="{$type}">
          {/block}
        </section>
      {/block}

      {block name='address_form_footer'}
        <footer class="form-footer clearfix">
          <input type="hidden" name="submitAddress" value="1">
          {block name='form_buttons'}
            <button class="btn-save-custom" type="submit">
              {l s='Zapisz' d='Shop.Theme.Actions'}
            </button>
          {/block}
        </footer>
      {/block}

    </form>
  </div>
{/block}