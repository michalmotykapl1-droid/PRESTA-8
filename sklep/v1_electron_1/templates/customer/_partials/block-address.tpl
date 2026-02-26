{**
 * 2007-2025 PrestaShop
 * customer/_partials/block-address.tpl - WERSJA "EDYTUJ"
 *}
{block name='address_block_item'}
  <article id="address-{$address.id}" class="address-tile {if $address.company || $address.vat_number}is-company{/if}" data-id-address="{$address.id}">
    
    {* HEADER *}
    <div class="address-header">
        <div class="address-alias">
            <i class="material-icons" style="margin-right: 5px;">
                {if $address.alias|lower|strstr:'dom'}home
                {elseif $address.alias|lower|strstr:'prac'}work
                {elseif $address.alias|lower|strstr:'firm'}business
                {else}location_on{/if}
            </i>
            <span>{$address.alias}</span>
        </div>
        
        {if $address.company || $address.vat_number}
            <div class="address-badge badge-company">FIRMA</div>
        {else}
            <div class="address-badge badge-private">PRYWATNY</div>
        {/if}
    </div>

    {* BODY *}
    <div class="address-body">
      <address>{$address.formatted nofilter}</address>
    </div>

    {* FOOTER - TU ZMIANA NA EDYTUJ *}
    {block name='address_block_item_actions'}
      <div class="address-footer">
        <a href="{url entity=address id=$address.id}" data-link-action="edit-address" class="btn-action btn-edit">
          <i class="material-icons">&#xE254;</i>
          <span>Edytuj</span>
        </a>
        <a href="{url entity=address id=$address.id params=['delete' => 1, 'token' => $token]}" data-link-action="delete-address" class="btn-action btn-delete">
          <i class="material-icons">&#xE872;</i>
          <span>{l s='Delete' d='Shop.Theme.Actions'}</span>
        </a>
      </div>
    {/block}
  </article>
{/block}