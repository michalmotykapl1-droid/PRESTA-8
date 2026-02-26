{**
 * 2007-2025 PrestaShop
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License 3.0 (AFL-3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future.
 * If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information.
 *
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2007-2025 PrestaShop SA
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 * International Registered Trademark & Property of PrestaShop SA
 *}
{strip}
{block name='customer_form'}
  {block name='customer_form_errors'}
    {include file='_partials/form-errors.tpl' errors=$errors['']}
  {/block}

<form action="{block name='customer_form_actionurl'}{$action}{/block}" id="customer-form" class="js-customer-form" method="post">
  <section>
    {block "form_fields"}
      {foreach from=$formFields item="field"}
        
        {if $field.name === 'optin' || $field.name === 'birthday' || $field.name === 'id_gender'}
            {continue}
        {/if}
        {if $field.name === 'password'}
            {continue}
        {/if}

        {block "form_field"}
          {form_field field=$field}
        {/block}
      {/foreach}

      {* CHECKBOX: ZAŁÓŻ KONTO *}
      {if $guest_allowed}
          <div class="form-group row">
             <div class="col-md-12">
                <span class="custom-checkbox create-account-box">
                  <input name="create_account" id="create_account_checkbox" type="checkbox" value="1">
                  <span><i class="material-icons rtl-no-flip checkbox-checked">&#xE5CA;</i></span>
                  <label for="create_account_checkbox">
                      <div class="checkbox-text-container">
                          <div class="checkbox-title">{l s='Chcę założyć konto' d='Shop.Theme.Checkout'}</div>
                          <div class="checkbox-desc">{l s='Zyskaj dostęp do historii zamówień i szybszych zakupów.' d='Shop.Theme.Checkout'}</div>
                      </div>
                  </label>
                </span>
             </div>
          </div>
          
          <div id="password-container" style="display: none;">
             {foreach from=$formFields item="field"}
                {if $field.name === 'password'}
                    {form_field field=$field}
                {/if}
             {/foreach}
          </div>
      {/if}

      {if !$guest_allowed}
         {foreach from=$formFields item="field"}
            {if $field.name === 'password'}
                {form_field field=$field}
            {/if}
         {/foreach}
      {/if}

    {/block}
  </section>

  {block name='customer_form_footer'}
    <footer class="form-footer clearfix">
      <div class="footer-flex-wrapper">
          <div class="required-field-note">
            {* ZMIANA TEKSTU TUTAJ *}
            <span class="red-star">*</span> - {l s='pole obowiązkowe' d='Shop.Theme.Checkout'}
          </div>
          
          <input type="hidden" name="submitCreate" value="1">
          {block "form_buttons"}
            <button class="continue tvall-inner-btn" name="continue" data-link-action="register-new-customer" type="submit" value="1">
                <span>{l s='PRZEJDŹ DALEJ' d='Shop.Theme.Actions'}</span>
            </button>
          {/block}
      </div>
    </footer>
  {/block}

</form>
{/block}
{/strip}