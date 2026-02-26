{**
 * 2007-2025 PrestaShop
 * ... (licencja) ...
 *}
{strip}
{extends file='checkout/_partials/steps/checkout-step.tpl'}

{block name='step_content'}
  <div id="hook-display-before-carrier">
    {$hookDisplayBeforeCarrier nofilter}
  </div>

  <div class="delivery-options-list">
    {if $delivery_options|count}
      <form
        class="clearfix"
        id="js-delivery"
        data-url-update="{url entity='order' params=['ajax' => 1, 'action' => 'selectDeliveryOption']}"
        method="post"
      >
        <div class="form-fields">
          
          {block name='delivery_options'}
            <div class="delivery-options-cards">
              {foreach from=$delivery_options item=carrier key=carrier_id}
                
                <div class="delivery-option-card-wrapper">
                  
                  <label class="delivery-option-card {if $delivery_option == $carrier_id}selected{/if}" data-delay-string="{$carrier.delay|escape:'htmlall':'UTF-8'}">
                    
                    <span class="carrier-radio-input">
                      <input type="radio" name="delivery_option[{$id_address}]" value="{$carrier_id}"{if $delivery_option == $carrier_id} checked{/if}>
                    </span>

                    <div class="card-radio-button"></div>

                    {if $carrier.logo}
                      <div class="card-logo">
                        <img src="{$carrier.logo}" alt="{$carrier.name}" />
                      </div>
                    {/if}

                    <div class="card-content">
                      <span class="card-title">{$carrier.name}</span>
                      <span class="card-delay">{$carrier.delay}</span>
                    </div>

                    <div class="card-price">
                      <span>{$carrier.price}</span>
                    </div>

                  </label> {* --- Koniec klikalnej karty --- *}

                  <div class="row carrier-extra-content"{if $delivery_option != $carrier_id} style="display:none;"{/if}>
                    
                    {* 1. Bloki modułów (np. InPost) *}
                    {$carrier.extraContent nofilter}

                    {* 2. POPRAWKA WARUNKU: Wracamy do sprawdzania nazwy (strstr szuka fragmentu tekstu) *}
                    {if $carrier.name|strstr:'Odbiór osobisty'}
                      <div class="pickup-store-details">
                        
                        <div class="pickup-map-container">
                          <iframe
                            src="https://maps.google.com/maps?q=Magazyn%20BIGBIO%2C%20Kopernika%209b%2C%2032-050%20Skawina&t=m&z=15&output=embed&iwloc=near"
                            width="100%"
                            height="150"
                            style="border:0;"
                            allowfullscreen=""
                            loading="lazy"
                          ></iframe>
                        </div>

                        <h6>Magazyn BIGBIO</h6>
                        
                        <div class="pickup-info-block pickup-address">
                          <i class="material-icons">place</i>
                          <span>Kopernika 9b, 32-050 Skawina</span>
                        </div>
                        
                        <div class="pickup-info-block pickup-hours">
                          <i class="material-icons">schedule</i>
                          <span>Pn-Pt: 8:00 - 15:00</span>
                        </div>

                        <div class="pickup-info-block pickup-info">
                          <i class="material-icons">info_outline</i>
                          <span>Po skompletowaniu zamówienia otrzymają Państwo sms/email z informacją o możliwości odbioru zamówienia.</span>
                        </div>

                      </div>
                    {/if}
                    {* --- KONIEC POPRAWKI --- *}

                  </div>

                </div>
              {/foreach}
            </div>
          {/block}

          <div class="order-options">
            <div id="delivery">
              <label for="delivery_message">{l s='Jeśli chcesz dodać komentarz do swojego zamówienia, zapisz go poniżej.' d='Shop.Theme.Checkout'}</label>
              <textarea rows="2" cols="120" id="delivery_message" name="delivery_message">{$delivery_message}</textarea>
            </div>

            {if $recyclablePackAllowed}
              <span class="custom-checkbox">
                <input type="checkbox" id="input_recyclable" name="recyclable" value="1" {if $recyclable} checked {/if}>
                <span><i class="material-icons rtl-no-flip checkbox-checked"></i></span>
                <label for="input_recyclable">{l s='I would like to receive my order in recycled packaging.' d='Shop.Theme.Checkout'}</label>
              </span>
            {/if}

            {if $gift.allowed}
              <span class="custom-checkbox">
                <input class="js-gift-checkbox" id="input_gift" name="gift" type="checkbox" value="1" {if $gift.isGift}checked="checked"{/if}>
                <span><i class="material-icons rtl-no-flip checkbox-checked"></i></span>
                <label for="input_gift">{$gift.label}</label >
              </span>

              <div id="gift" class="collapse{if $gift.isGift} in{/if}">
                <label for="gift_message">{l s='If you\'d like, you can add a note to the gift:' d='Shop.Theme.Checkout'}</label>
                <textarea rows="2" cols="120" id="gift_message" name="gift_message">{$gift.message}</textarea>
              </div>
            {/if}

          </div>
        </div>
        
        <button type="submit" class="continue tvall-inner-btn float-xs-right" name="confirmDeliveryOption" value="1">
          <span>{l s='WYBIERZ PŁATNOŚĆ' d='Shop.Theme.Actions'}</span>
        </button>
      </form>
    {else}
      <p class="alert alert-danger">{l s='Unfortunately, there are no carriers available for your delivery address.' d='Shop.Theme.Checkout'}</p>
    {/if}
  </div>

  <div id="hook-display-after-carrier">
    {$hookDisplayAfterCarrier nofilter}
  </div>

  <div id="extra_carrier"></div>
{/block}
{/strip}