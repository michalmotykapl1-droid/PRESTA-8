{* front/modules/yourmodule/views/templates/hook/product_weights_selector.tpl *}
<div class="product-variant-selector variant-tile">
  {if !empty($variants) && $variants|@count > 1}
    <label for="weight_select" class="variant-label">Dostępne wagi:</label>
    <div class="variant-control">
      <select id="weight_select"
              class="variant-dropdown"
              onchange="location = this.value;">
        {foreach from=$variants item=rw}
          <option value="{$rw.link}"
                  {if $rw.id == $currentId}selected{/if}>
            {$rw.display_grams} g
          </option>
        {/foreach}
      </select>
    </div>
  {/if}
</div>
