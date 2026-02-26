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
{block name='social_sharing'}
  {if $social_share_links}
    <div class="social-icon">
      {* <span class="control-label">{l s='Share on : ' d='Shop.Theme.Actions'}</span> *}
      <ul>
        {if isset($social_share_links.facebook) && !empty($social_share_links.facebook)}
          <li class="{$social_share_links.facebook.class} icon-black"><a href="{$social_share_links.facebook.url}" class="text-hide" title="{l s='Facebook' d='Shop.Theme.Catalog'}" rel="noreferrer">{l s='Facebook' d='Shop.Theme.Catalog'}</a></li>
        {/if}

        {if isset($social_share_links.twitter) && !empty($social_share_links.twitter)}
          <li class="{$social_share_links.twitter.class} icon-black"><a href="{$social_share_links.twitter.url|replace:' ':'%20'}" class="text-hide" title="{l s='Twitter' d='Shop.Theme.Catalog'}" rel="noreferrer">{l s='Twitter' d='Shop.Theme.Catalog'}</a></li>
        {/if}

        {if isset($social_share_links.googleplus) && !empty($social_share_links.googleplus)}
          <li class="{$social_share_links.googleplus.class} icon-black"><a href="{$social_share_links.googleplus.url}" class="text-hide" title="{l s='Google+' d='Shop.Theme.Catalog'}" rel="noreferrer">{l s='Google+' d='Shop.Theme.Catalog'}</a></li>
        {/if}

        {if isset($social_share_links.pinterest) && !empty($social_share_links.pinterest)}
          <li class="{$social_share_links.pinterest.class} icon-black"><a href="{$social_share_links.pinterest.url}" class="text-hide" title="{l s='Pinterest' d='Shop.Theme.Catalog'}" rel="noreferrer">{l s='Pinterest' d='Shop.Theme.Catalog'}</a></li>
        {/if}
      </ul>
    </div>
  {/if}
{/block}
{/strip}