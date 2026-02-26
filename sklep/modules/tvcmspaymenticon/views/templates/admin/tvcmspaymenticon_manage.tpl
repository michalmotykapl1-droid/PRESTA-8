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
    <input type="hidden" name="tvcmsinstall_tab_number" id='tvcmsinstall-tab-number'>
    <form id="module_form" action="{$tvurlupgrade}" method="post" enctype="multipart/form-data" novalidate="">
        <button class="btn btn-primary tvcms-sample-install-btn" type="submit" onclick="return confirm('Are you sure want to install sample data?')" name="submitTvcmsSampleinstall">{l s='Install Sample Data' mod="tvcmspaymenticon"}</button>
    </form>
    <div class="seperation defaultHidden"> </div>
    <table id="table-data" class="table tvcmstable tvcmspaymenticon">
        <thead>
            <tr class="nodrag nodrop">
                {if $show_fields.image == true}
                <th class="fixed-width-xs center">
                    <span class="title_box active">{l s='Image' mod='tvcmspaymenticon'}</span>
                </th>
                {/if}
                {if $show_fields.title == true}
                <th class="fixed-width-xs center">
                    <span class="title_box active">{l s='Title' mod='tvcmspaymenticon'}</span>
                </th>
                {/if}
                {if $show_fields.link == true}
                <th class="fixed-width-xs center">
                    <span class="title_box active">{l s='Link' mod='tvcmspaymenticon'}</span>
                </th>
                {/if}
                {if $show_fields.status == true}
                <th class="fixed-width-xs center status">
                    <span class="title_box active">{l s='Status' mod='tvcmspaymenticon'}</span>
                </th>
                {/if}
                <th class="fixed-width-xs center action">
                    <span class="title_box active">{l s='Action' mod='tvcmspaymenticon'}</span>
                </th>
            </tr>
        </thead>
        <tbody>
            {foreach $array_list as $Data}
            <tr id="recordsArray_{$Data.id|escape:'htmlall':'UTF-8'}">
                {if $show_fields.image == true}
                <th class="fixed-width-xs center">
                    <img src='{$module_dir|escape:"html":"UTF-8"}views/img/{$Data.image}' width="100px" />
                </th>
                {/if}
                {if $show_fields.title == true}
                <td class="pointer fixed-width-xs center">
                    {$Data.title|escape:'htmlall':'UTF-8'}
                </td>
                {/if}
                {if $show_fields.link == true}
                <td class="pointer fixed-width-xs center">
                    {$Data.link|escape:'htmlall':'UTF-8'}
                </td>
                {/if}
                {if $show_fields.status == true}
                <td class="pointer fixed-width-xs center">
                    {if $Data.status == 1}
                    <i class="icon-check greenColor"></i>
                    {else}
                    <i class="icon-close redColor"></i>
                    {/if}
                </td>
                {/if}
                <td class="pointer fixed-width-xs center">
                    <form action="" class="actionEdit" name="actionEdit" method="post">
                        <input type="hidden" name="id" value="{$Data.id|escape:'htmlall':'UTF-8'}">
                        <input type="hidden" name="action" value="edit">
                        <button title="Edit" class="edit" onclick="$(this).parent('.actionEdit').trigger('submit');"><i class="icon-pencil"></i> {l s='Edit' mod='tvcmspaymenticon'} </button>
                    </form>
                    <form action="" class="actionDelete" name="actionDelete" method="post">
                        <input type="hidden" name="id" value="{$Data.id|escape:'htmlall':'UTF-8'}">
                        <input type="hidden" name="action" value="remove">
                        <button title="Delete" class="delete tvcmsdelete"><i class="icon-trash"></i> {l s='Delete' mod='tvcmspaymenticon'} </button>
                    </form>
                </td>
            </tr>
            {/foreach}
        </tbody>
    </table>
    <div id="growls" class="default"></div>
    {/strip}