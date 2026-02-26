{**
 * 2007-2025 PrestaShop
 * ...
 *}
{strip}
{block name='address_selector_blocks'}
  {foreach $addresses as $address}
    
    {* === POCZĄTEK POPRAWKI FILTROWANIA (Rozwiązanie 1) === *}
    
    {if $name == 'id_address_delivery'}
        {* Jesteśmy w bloku DOSTAWY - Pokaż tylko adresy dostawy *}
        {if $address.alias == 'Dane do faktury'}{continue}{/if}
    {/if}
    
    {if $name == 'id_address_invoice'}
        {* Jesteśmy w bloku FAKTURY - Zgodnie z prośbą, pokazujemy WSZYSTKIE adresy *}
        {* (Usunięto poprzednią logikę filtrującą) *}
    {/if}
    {* === KONIEC POPRAWKI FILTROWANIA === *}

    <article
      class="address-item{if $address.id == $selected} selected{/if}"
      id="{$name|classname}-address-{$address.id}"
    >
      <header class="h4">
        <label class="radio-block">
         
          <span class="custom-radio">
            <input
              type="radio"
              name="{$name}"
              value="{$address.id}"
              {if $address.id == $selected}checked{/if}
            >
            <span></span>
     
          </span>
          
          {* Poprawka wyświetlania "Twoja nazwa" (zostaje) *}
          <span class="address-alias h4">
            {if !empty($address.other)}
                {$address.other}
            {else}
                 {$address.alias}
            {/if}
          </span>
          
          <div class="address">{$address.formatted nofilter}</div>
          
        </label>
      </header>
      <hr>
      
      <footer class="address-footer">
        {if $interactive}
           <a
            class="edit-address tvedit-btn text-muted"
            data-link-action="edit-address"
            href="{url entity='order' params=['id_address' => $address.id, 'editAddress' => $type, 'token' => $token]}"
          >
            <i class="material-icons edit">&#xE254;</i>
            <span>{l s='Edytuj' d='Shop.Theme.Actions'}</span>
       
           </a>
          <a
            class="delete-address tvremove-btn text-muted"
            data-link-action="delete-address"
            href="{url entity='order' params=['id_address' => $address.id, 'deleteAddress' => true, 'token' => $token]}"
          >
            <i class="material-icons delete">&#xE872;</i>
            <span>{l s='Usuń' d='Shop.Theme.Actions'}</span>
 
          </a>
        {/if}
      </footer>
    </article>
  {/foreach}
  {if $interactive}
    <p>
      <button class="ps-hidden-by-js form-control-submit center-block" type="submit">{l s='Save' d='Shop.Theme.Actions'}</button>
    </p>
  {/if}
{/block}
{/strip}