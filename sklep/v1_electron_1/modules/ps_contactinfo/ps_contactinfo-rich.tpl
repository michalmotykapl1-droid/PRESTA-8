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
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information.
 *
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2007-2025 PrestaShop SA
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 * International Registered Trademark & Property of PrestaShop SA
 *}
{strip}
<div class="contact-rich">
  <h4 class="text-center">{l s='Store information' d='Shop.Theme.Global'}</h4>
  <div class="row">
  {if $contact_infos.address.formatted}
    <div class="block tvaddress col-xs-12 col-sm-6 col-md-3">
      <div class="icon"><i class="material-icons">&#xE55F;</i></div>
      <div class="data">
        <p>{$contact_infos.address.formatted nofilter}</p>
      </div>
    </div>
  {/if}
  {if $contact_infos.phone}
    <div class="block tvphone col-xs-12 col-sm-6 col-md-3">
      <div class="icon"><i class="material-icons">&#xE0CD;</i></div>
      <div class="data">
        <span>{l s='Call us:' d='Shop.Theme.Global'}</span>
        <a href="tel:{$contact_infos.phone}">{$contact_infos.phone}</a>
       </div>
    </div>
  {/if}
  {if $contact_infos.fax}
    <div class="block tvfax col-xs-12 col-sm-6 col-md-3">
      <div class="icon"><i class="material-icons">&#xE0DF;</i></div>
      <div class="data">
        <span>{l s='Fax:' d='Shop.Theme.Global'}</span>
        <a href="fax:{$contact_infos.phone}">{$contact_infos.fax}</a>
      </div>
    </div>
  {/if}
  {if $contact_infos.email}
    <div class="block tvemail col-xs-12 col-sm-6 col-md-3">
      <div class="icon"><i class="material-icons">&#xE158;</i></div>
      <div class="data">
        <span>{l s='Email us:' d='Shop.Theme.Global'}</span>
        <a href="mailto:{$contact_infos.email}">{$contact_infos.email}</a>
      </div>
    </div>
  {/if}
  </div>
</div>
{/strip}