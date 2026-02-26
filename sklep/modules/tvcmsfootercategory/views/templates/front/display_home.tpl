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
    {if Configuration::get('TVCMSFOOTERCATEGORY_STATUS')}
    <div class='col-xl-2 col-lg-2 col-md-12 links tvfooter-category-block tvfooter-all-block tvfooter-all-part'>
        {if $show_fields['title']}
        <div class="tvdekstop-footer-all-title-wrapper tvfooter-title-wrapper" data-target="#footer_sub_menu_tvfooter_category" data-toggle="collapse">
            <div class='tvfooter-title'>{Configuration::get('TVCMSFOOTERCATEGORY_TITLE', $id_lang)}</div>
            <span class="float-xs-right tvfooter-toggle-icon-wrapper navbar-toggler collapse-icons tvfooter-toggle-icon">
                <i class="material-icons add">&#xE313;</i>
                <i class="material-icons remove">&#xE316;</i>
            </span>
        </div>
        {* <div class="title clearfix hidden-md-up tvfooter-mobile-product-title" data-target="#footer_sub_menu_tvfooter_category" data-toggle="collapse">
            <div class="tvdekstop-footer-all-title-wrapper">
                <div class='tvfooter-title'>
                    {Configuration::get('TVCMSFOOTERCATEGORY_TITLE', $id_lang)}
                </div>
            </div>
            <span class="float-xs-right tvfooter-mobile-dropdown">
                <span class="navbar-toggler collapse-icons">
                    <i class='material-icons add'>&#xe313;</i>
                    <i class='material-icons remove'>&#xe316;</i>
                </span>
            </span>
        </div> *}
        {/if}
        <ul id="footer_sub_menu_tvfooter_category" class="collapse">
            {foreach $category_list as $category}
            <li>
                <i class='material-icons'>&#xe39e;</i>
                <a href='{$category["link"]}' title='{$category["name"][$id_lang]}'>{$category["name"][$id_lang]}</a>
            </li>
            {/foreach}
        </ul>
    </div>
    {/if}
    {/strip}