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
{extends file='checkout/_partials/steps/checkout-step.tpl'}

{block name='step_content'}
  {if $customer.is_logged && !$customer.is_guest}

    <div class="connected-customer">
        <p class="identity">
          {l s='Connected as [1]%firstname% %lastname%[/1].'
            d='Shop.Theme.Customeraccount'
            sprintf=[
              '[1]' => "<a href='{$urls.pages.identity}'>",
              '[/1]' => "</a>",
              '%firstname%' => $customer.firstname,
              '%lastname%' => $customer.lastname
            ]
          }
        </p>
        <p>
          {l s='Not you? [1]Log out[/1]' d='Shop.Theme.Customeraccount' sprintf=['[1]' => "<a href='{$urls.actions.logout}'>", '[/1]' => "</a>"]}
        </p>
        {if !isset($empty_cart_on_logout) || $empty_cart_on_logout}
          <p><small>{l s='If you sign out now, your cart will be emptied.' d='Shop.Theme.Checkout'}</small></p>
        {/if}
    </div>

  {else}

    <div class="checkout-auth-tabs">
        {* Używamy standardowych klas bootstrap nav-pills, stylujemy je w CSS *}
        <ul class="nav nav-pills" role="tablist">
          <li class="nav-item">
            <a
              class="nav-link {if !$show_login_form}active{/if}"
              data-toggle="tab"
              href="#checkout-guest-form"
              role="tab"
              aria-controls="checkout-guest-form"
              {if !$show_login_form} aria-selected="true"{/if}
            >
              {if $guest_allowed}
                {l s='Szybkie zakupy' d='Shop.Theme.Checkout'}
              {else}
                {l s='Create an account' d='Shop.Theme.Customeraccount'}
              {/if}
            </a>
          </li>

          <li class="nav-item">
            <a
              class="nav-link {if $show_login_form}active{/if}"
              data-link-action="show-login-form"
              data-toggle="tab"
              href="#checkout-login-form"
              role="tab"
              aria-controls="checkout-login-form"
              {if $show_login_form} aria-selected="true"{/if}
            >
              {l s='Mam już konto' d='Shop.Theme.Actions'}
            </a>
          </li>
        </ul>
    </div>

    <div class="tab-content checkout-auth-content">
      <div class="tab-pane {if !$show_login_form}active{/if}" id="checkout-guest-form" role="tabpanel" {if $show_login_form}aria-hidden="true"{/if}>
        {render file='checkout/_partials/customer-form.tpl' ui=$register_form guest_allowed=$guest_allowed}
      </div>
      <div class="tab-pane {if $show_login_form}active{/if}" id="checkout-login-form" role="tabpanel" {if !$show_login_form}aria-hidden="true"{/if}>
        {render file='checkout/_partials/login-form.tpl' ui=$login_form}
      </div>
    </div>

  {/if}
{/block}
{/strip}