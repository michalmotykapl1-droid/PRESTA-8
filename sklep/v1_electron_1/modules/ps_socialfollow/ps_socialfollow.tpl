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
* @author PrestaShop SA <contact@prestashop.com>
* @copyright 2007-2025 PrestaShop SA
* @license https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
* International Registered Trademark & Property of PrestaShop SA
*}
{strip}
    {block name='block_social'}
    <div class="block-social tvcmsfooter-social-icon col-xl-4 col-md-12 col-sm-12">
        {* <div class="tvfooter-title-wrapper" data-target="#footer_sub_menu_social_icon">
            <span class="tvfooter-title">
                {if Configuration::get('TVCMSCUSTOMSETTING_SOCIAL_ICON_TITLE', $language.id)}
                {Configuration::get('TVCMSCUSTOMSETTING_SOCIAL_ICON_TITLE', $language.id)}
                {/if}
            </span>
            <span class="float-xs-right tvfooter-toggle-icon-wrapper">
                <span class="navbar-toggler collapse-icons tvfooter-toggle-icon">
                    <i class="material-icons add">&#xE313;</i>
                    <i class="material-icons remove">&#xE316;</i>
                </span>
            </span>
        </div> *}
        <ul id="footer_sub_menu_social_icon" class="tvfooter-social-icon-wrapper">{*
            {foreach from=$social_links item='social_link'}
            <li class="{$social_link.class}"><a href="{$social_link.url}">{$social_link.label}</a></li>
            {/foreach}
            *}{if !empty(Configuration::get('BLOCKSOCIAL_FACEBOOK'))}
            <li class="facebook">
                <a href="{Configuration::get('BLOCKSOCIAL_FACEBOOK')}" rel="noreferrer" title="{l s='Facebook' d='Shop.Theme.Catalog'}">
                    <span class="facebook-icon"></span>
                    {*<span class="tvsocial-title">{l s='Facebook' d='Shop.Theme.Catalog'}</span> *}
                </a>
            </li>
            {/if}
            {if !empty(Configuration::get('BLOCKSOCIAL_TWITTER'))}
            <li class="twitter">
                <a href="{Configuration::get('BLOCKSOCIAL_TWITTER')}" rel="noreferrer" title="{l s='Twitter' d='Shop.Theme.Catalog'}">
                    <span class="twitter-icon"></span>{*
                    <span class="tvsocial-title">{l s='Twitter' d='Shop.Theme.Catalog'}</span>
                    *}</a>
            </li>
            {/if}
            {if !empty(Configuration::get('BLOCKSOCIAL_RSS'))}
            <li class="rss">
                <a href="{Configuration::get('BLOCKSOCIAL_RSS')}" rel="noreferrer" title="{l s='Rss' d='Shop.Theme.Catalog'}">
                    <span class="rss-icon"></span>{*
                    <span class="tvsocial-title">{l s='Rss' d='Shop.Theme.Catalog'}</span>
                    *}</a>
            </li>
            {/if}
            {if !empty(Configuration::get('BLOCKSOCIAL_YOUTUBE'))}
            <li class="youtube">
                <a href="{Configuration::get('BLOCKSOCIAL_YOUTUBE')}" rel="noreferrer" title="{l s='Youtube' d='Shop.Theme.Catalog'}">
                    <span class="youtube-icon"></span>{*
                    <span class="tvsocial-title">{l s='Youtube' d='Shop.Theme.Catalog'}</span>
                    *}</a>
            </li>
            {/if}
            {if !empty(Configuration::get('BLOCKSOCIAL_GOOGLE_PLUS'))}
            <li class="googleplus">
                <a href="{Configuration::get('BLOCKSOCIAL_GOOGLE_PLUS')}" rel="noreferrer" title="{l s='Google plus' d='Shop.Theme.Catalog'}">
                    <span class="googleplus-icon"></span>{*
                    <span class="tvsocial-title">{l s='Google plus' d='Shop.Theme.Catalog'}</span>
                    *}</a>
            </li>
            {/if}
            {if !empty(Configuration::get('BLOCKSOCIAL_PINTEREST'))}
            <li class="pinterest">
                <a href="{Configuration::get('BLOCKSOCIAL_PINTEREST')}" rel="noreferrer" title="{l s='Pinterest' d='Shop.Theme.Catalog'}">
                    <span class="pinterest-icon"></span>{*
                    <span class="tvsocial-title">{l s='Pinterest' d='Shop.Theme.Catalog'}</span>
                    *}</a>
            </li>
            {/if}
            {if !empty(Configuration::get('BLOCKSOCIAL_VIMEO'))}
            <li class="vimeo">
                <a href="{Configuration::get('BLOCKSOCIAL_VIMEO')}" rel="noreferrer" title="{l s='Vimeo' d='Shop.Theme.Catalog'}">
                    <span class="vimeo-icon"></span>{*
                    <span class="tvsocial-title">{l s='Vimeo' d='Shop.Theme.Catalog'}</span>
                    *}</a>
            </li>
            {/if}
            {if !empty(Configuration::get('BLOCKSOCIAL_INSTAGRAM'))}
            <li class="instagram">
                <a href="{Configuration::get('BLOCKSOCIAL_INSTAGRAM')}" rel="noreferrer" title="{l s='Instagram' d='Shop.Theme.Catalog'}">
                    <span class="instagram-icon"></span>{*
                    <span class="tvsocial-title">{l s='Instagram' d='Shop.Theme.Catalog'}</span>
                    *}</a>
            </li>
            {/if}
        </ul>
    </div>
    {/block}
{/strip}