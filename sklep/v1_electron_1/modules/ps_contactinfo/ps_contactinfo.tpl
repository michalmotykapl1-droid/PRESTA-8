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
    <div class="tvfooter-contact-link-wrapper links col-xl-3 col-lg-3 col-md-12">
        <div class="tvfooter-address">
            <div class="tvfooter-title-wrapper" data-target="#footer_sub_menu_store_info" data-toggle="collapse">
                <span class="tvfooter-title">{l s='Store information' d='Shop.Theme.Global'}</span>
                <span class="float-xs-right tvfooter-toggle-icon-wrapper navbar-toggler collapse-icons tvfooter-toggle-icon">
                    <i class="material-icons add">&#xE313;</i>
                    <i class="material-icons remove">&#xE316;</i>
                </span>
            </div>
            <div id="footer_sub_menu_store_info" class="collapse">
                <div class="tvfooter-addresses">
                    <i class="material-icons">location_on</i>
                    {* <div class="tvfooter-address-lable">{l s='Address:' d='Shop.Theme.Global'}</div> *}
                    {$contact_infos.address.formatted nofilter}
                </div>
                {if $contact_infos.email}
                <div class="tvfooter-store-link">
                    {* [1][/1] is for a HTML tag. *}
                    <i class="material-icons">email</i>
                    {l s='[1]%email%[/1]'
                    sprintf=[
                    '[1]' => '<a href="mailto:'|cat:$contact_infos.email|cat:'" class="dropdown">',
                        '[/1]' => '</a>',
                    '%email%' => $contact_infos.email
                    ]
                    d='Shop.Theme.Global'
                    }
                </div>
                {/if}
                {if $contact_infos.phone}
                <div class="tvfooter-store-link-content">
                    {* [1][/1] is for a HTML tag. *}
                    <i class="material-icons">call</i>
                    {l s='[1]%phone%[/1]'
                    sprintf=[
                    '[1]' => '<a href="tel:'|cat:$contact_infos.email|cat:'" class="dropdown">',
                        '[/1]' => '</a>',
                    '%phone%' => $contact_infos.phone
                    ]
                    d='Shop.Theme.Global'
                    }
                </div>
                {/if}
                {if $contact_infos.fax}
                <div class="tvfooter-store-link-fax">{* [1][/1] is for a HTML tag.
                    *}<i class="material-icons">print</i>{l
                    s='[1]%fax%[/1]'
                    sprintf=[
                    '[1]' => '<a href="fax:'|cat:$contact_infos.email|cat:'" class="dropdown">',
                        '[/1]' => '</a>',
                    '%fax%' => $contact_infos.fax
                    ]
                    d='Shop.Theme.Global'
                    }
                </div>
                {/if}
            </div>
        </div>
    </div>
{/strip}