{**
* 2007-2025 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
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
    * @license http://opensource.org/licenses/afl-3.0.php Academic Free License (AFL 3.0)
    * International Registered Trademark & Property of PrestaShop SA
    *}
    {strip}
    {if Configuration::get('TVCMSFOOTERLOGO_IMG')}
    {* <div class="tvfooter-title-wrapper" data-target="#footer_sub_menu_{$_expand_id}" data-toggle="collapse">
        <span class="tvfooter-title">{l s='About Store' d='Shop.Theme.Customeraccount'}</span>
        <span class="float-xs-right tvfooter-toggle-icon-wrapper">
            <span class="navbar-toggler collapse-icons tvfooter-toggle-icon">
                <i class="material-icons add">&#xE313;</i>
                <i class="material-icons remove">&#xE316;</i>
            </span>
        </span>
    </div> *}
    <div class='tvfooter-about-logo-wrapper tvfooter-logo-block'>
        {if $show_fields['main_image']}
        <div class='tvfooter-img-block'>
            <img src="{$path}{Configuration::get('TVCMSFOOTERLOGO_IMG')}" 
     alt="" 
     class="tv-img-responsive tv-footer-logo" 
     loading="lazy"/>
        </div>
        {/if}
        {if $show_fields['main_title']}
        <div class='tvfooter-logo-title'>
            {Configuration::get('TVCMSFOOTERLOGO_TITLE', $id_lang)}
        </div>
        {/if}
        {if $show_fields['main_short_description']}
        <div class='tvfooter-logo-short-title'>
           {Configuration::get('TVCMSFOOTERLOGO_SUB_DESCRIPTION', $id_lang)}
        </div>
        {/if}
        {if $show_fields['main_description']}
        <div class='tvfooter-logo-desc'>
           Kupuj świadomie i naturalnie! W BIGBIO znajdziesz wyselekcjonowaną żywność, sprawdzone kosmetyki oraz produkty dla domu i rodziny. <BR>Dbamy o jakość, skład i Twój czas – wszystko w jednym miejscu.
        </div>
        {/if}
    </div>
    {/if}
    {/strip}