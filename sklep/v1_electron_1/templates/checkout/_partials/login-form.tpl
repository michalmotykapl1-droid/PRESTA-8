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
{extends file='customer/_partials/login-form.tpl'}

{block name='form_buttons'}
  <div class="footer-flex-wrapper">
      <div class="required-field-note">
        {* ZMIANA TEKSTU TUTAJ *}
        <span class="red-star">*</span> - {l s='pole obowiązkowe' d='Shop.Theme.Checkout'}
      </div>

      <button class="continue tvall-inner-btn" name="continue" data-link-action="sign-in" type="submit" value="1">
        <span>{l s='PRZEJDŹ DALEJ' d='Shop.Theme.Actions'}</span>
      </button>
  </div>
{/block}
{/strip}