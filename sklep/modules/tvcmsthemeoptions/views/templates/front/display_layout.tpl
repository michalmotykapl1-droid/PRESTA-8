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
    {if Configuration::get('TVCMSFRONTSIDE_THEME_SETTING_SHOW')}
    <div class="tvcmstheme-layout">
        <div class="tvtheme-layout">
            <form>
                <div class="tvthemelayout-heading">
                    {* <div class="tvtheme-layout-title-name">
                        {hook h='displayFrontSetting'}
                    </div>
                    <div class="tvtheme-layout-title-name-reset-btn">
                        <p>{l s='LAYOUT OPTION' d='Shop.Theme.Global'}</p>
                    </div> *}
                </div>
                <div class="tvtheme-layout-wrapper">
                    <table>
                        <tr class="tvselect-layout tvall-theme-content">
                            <td>
                                <div class="accordion" id="accordionExample">
                                    <div class="card">
                                        <div class="card-header" id="headerWrapper">
                                            <h2 class="mb-0">
                                                <button class="tvtheme-layout-btn btn btn-block collapsed" type="button" data-toggle="collapse" data-target="#headerLayout" aria-expanded="true" aria-controls="headerLayout">
                                                    {l s='Header Layouts' mod='tvcmsthemeoptions'}
                                                    <i class="material-icons tvlayout-dropdown">&#xe5cf;</i>
                                                </button>
                                            </h2>
                                        </div>
                                        <div id="headerLayout" class="collapse show in" aria-labelledby="headerWrapper" data-parent="#accordionExample">
                                            <div class="card-body">
                                                <div class="tvtheme-layout-radio">
                                                    {assign var=arr_desk_name value=$fields_data['header_layout_list']}
                                                    {foreach from=$arr_desk_name item=i name=index}
                                                    {assign var="header_name" value="desk-header-{$i}"}
                                                    <div class="col-md-6">
                                                        <label class="tvlayout-radio-img">
                                                            <input type="radio" class="header-layout" name="header-layout" value="{$i}" {if $smarty.foreach.index.index+1==1}checked{/if} />
                                                            <img class="tvlayout-image" src="{$fields_data['layout_img_path']}hlayouts/{$header_name}.jpg" alt="header-layout" data-layout="{$header_name}" />
                                                            <p class="tvlayout-title">{l s='Header' mod='tvcmsthemeoptions'} {$smarty.foreach.index.index+1}</p>
                                                        </label>
                                                    </div>
                                                    {/foreach}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="card">
                                        <div class="card-header" id="headermobileWrapper">
                                            <h2 class="mb-0">
                                                <button class="tvtheme-layout-btn btn btn-block collapsed" type="button" data-toggle="collapse" data-target="#headerMobileLayout" aria-expanded="true" aria-controls="headerMobileLayout">
                                                    {l s='Mobile Header Layouts' mod='tvcmsthemeoptions'}
                                                    <i class="material-icons tvlayout-dropdown">&#xe5cf;</i>
                                                </button>
                                            </h2>
                                        </div>
                                        <div id="headerMobileLayout" class="collapse" aria-labelledby="headermobileWrapper" data-parent="#accordionExample">
                                            <div class="card-body">
                                                <div class="tvtheme-layout-radio">
                                                    {assign var=arr_desk_name value=$fields_data['mob_header_layout_list']}
                                                    {foreach from=$arr_desk_name item=i name=index}
                                                    {assign var="header_mobile_name" value="mobile-header-{$i}"}
                                                    <div class="col-md-6">
                                                        <label class="tvlayout-radio-img">
                                                            <input type="radio" class="header-mobile-layout" name="header-mobile-layout" value="{$smarty.foreach.index.index+1}" {if $smarty.foreach.index.index+1==1}checked{/if} />
                                                            <img class="tvlayout-image" src="{$fields_data['layout_img_path']}hlayouts/{$header_mobile_name}.jpg" alt="header-mobile-layout" data-layout="{$header_mobile_name}"/>
                                                            <p class="tvlayout-title">{l s='Header' mod='tvcmsthemeoptions'} {$smarty.foreach.index.index+1}</p>
                                                        </label>
                                                    </div>
                                                    {/foreach}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="card">
                                        <div class="card-header" id="footerWrapper">
                                            <h2 class="mb-0">
                                                <button class="tvtheme-layout-btn btn btn-block collapsed" type="button" data-toggle="collapse" data-target="#footerLayout" aria-expanded="false" aria-controls="footerLayout">
                                                    {l s='Footer Layouts' mod='tvcmsthemeoptions'}
                                                    <i class="material-icons tvlayout-dropdown">&#xe5cf;</i>
                                                </button>
                                            </h2>
                                        </div>
                                        <div id="footerLayout" class="collapse" aria-labelledby="footerWrapper" data-parent="#accordionExample">
                                            <div class="card-body">
                                                <div class="tvtheme-layout-radio">
                                                    {assign var=arr_desk_name value=$fields_data['footer_layout_list']}
                                                    {foreach from=$arr_desk_name item=i name=index}
                                                    {assign var="footer_name" value="footer-{$i}"}
                                                    <div class="col-md-6">
                                                        <label class="tvlayout-radio-img">
                                                            <input type="radio" class="footer-layout" name="footer-layout" value="{$smarty.foreach.index.index+1}" {if $smarty.foreach.index.index+1==1}checked{/if} />
                                                            <img class="tvlayout-image" src="{$fields_data['layout_img_path']}flayouts/{$footer_name}.jpg" alt="footer-layout" data-layout="{$footer_name}"/>
                                                            <p class="tvlayout-title">{l s='Footer' mod='tvcmsthemeoptions'} {$smarty.foreach.index.index+1}</p>
                                                        </label>
                                                    </div>
                                                    {/foreach}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="card">
                                        <div class="card-header" id="productWrapper">
                                            <h2 class="mb-0">
                                                <button class="tvtheme-layout-btn btn btn-block collapsed" type="button" data-toggle="collapse" data-target="#productLayout" aria-expanded="false" aria-controls="productLayout">
                                                    {l s='Product Page Layouts' mod='tvcmsthemeoptions'}
                                                    <i class="material-icons tvlayout-dropdown">&#xe5cf;</i>
                                                </button>
                                            </h2>
                                        </div>
                                        <div id="productLayout" class="collapse" aria-labelledby="productWrapper" data-parent="#accordionExample">
                                            <div class="card-body">
                                                <div class="tvtheme-layout-radio">
                                                    {assign var=arr_desk_name value=$fields_data['product_layout_list']}
                                                    {foreach from=$arr_desk_name item=i name=index}
                                                    {assign var="prod_name" value="product-{$i}"}
                                                    <div class="col-md-6">
                                                        <label class="tvlayout-radio-img">
                                                            <input type="radio" name="product-layout" name="product-layout" value="{$smarty.foreach.index.index+1}" {if $smarty.foreach.index.index+1==1}checked{/if} />
                                                            <img class="tvlayout-image" src="{$fields_data['layout_img_path']}pdlayouts/{$prod_name}.png" alt="Product-layout" data-layout="{$prod_name}"/>
                                                            <p class="tvlayout-title">{l s='Product Layout' mod='tvcmsthemeoptions'} {$smarty.foreach.index.index+1}</p>
                                                        </label>
                                                    </div>
                                                    {/foreach}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    </table>
                    <button class="tvtheme-layout-reset tvall-inner-btn">
                        <span>{l s='Reset' d='Shop.Theme.Global'}</span>
                    </button>
                </div>
                <div class="tvtheme-layout-icon">
                     {* <i class='material-icons'>&#xe99b;</i> *}
                     <i class='material-icons'>dashboard_customize</i> 
                </div>
            </form>
        </div>
    </div>
    {/if}
    {/strip}