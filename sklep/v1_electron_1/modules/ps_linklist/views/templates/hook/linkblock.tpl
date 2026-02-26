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
<div class="col-xl-2 col-lg-2 col-md-12 tvfooter-account-link">
  <div class="tvfooter-account-wrapper">
    {foreach $linkBlocks as $linkBlock}{* 
    <div class="tvfooter-title">{$linkBlock.title}</div> 
    *}{assign var=_expand_id value=10|mt_rand:100000}
      <div class="tvfooter-title-wrapper" data-target="#footer_sub_menu_link" data-toggle="collapse">
        <span class="tvfooter-title">{$linkBlock.title}</span>
        <span class="float-xs-right tvfooter-toggle-icon-wrapper navbar-toggler collapse-icons tvfooter-toggle-icon">
            <i class="material-icons add">&#xE313;</i>
            <i class="material-icons remove">&#xE316;</i>
        </span>
      </div>
      <ul id="footer_sub_menu_link" class="collapse tvfooter-link-wrapper">
        {foreach $linkBlock.links as $link}
          <li>
            <a id="{$link.id}-{$linkBlock.id}" class="{$link.class}" href="{$link.url}" title="{$link.description}" {if !empty($link.target)} target="{$link.target}" {/if}>
              {$link.title}
            </a>
          </li>
        {/foreach}
      </ul>
  {/foreach}
  </div>
</div>
{/strip}