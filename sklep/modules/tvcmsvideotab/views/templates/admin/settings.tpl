{*
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
* to license@buy-addons.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
* @author    Buy-addons    <contact@buy-addons.com>
* @copyright 2007-2025 Buy-addons
* @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*}

<form  method="post" accept-charset="utf-8" action="">
    <div class="panel" style="border-radius: 0px;">
        <div class="col-lg-1">
            <span class="pull-right">
            </span>
        </div>
        <div class="form-group row">
        <div class="col-lg-2" style="text-align: right;margin-top: 9px;">
            <label class="control-label" for="available_for_order">
                {l s='Video Placements:' mod='tvcmsvideotab'}
            </label>
        </div>
        <div class="col-lg-5">
            <div class="checkbox">
                <label for="available_for_order">
                <input type="checkbox" name="position_tab" id="position_tab" value="1" 
                {if $position_tab == 1} checked="checked"{/if}>
                {l s='Product Tab' mod='tvcmsvideotab'}</label>
            </div>
            <div class="checkbox">
                <label for="show_price">
                <input type="checkbox" name="position_popup" id="position_popup" value="1" 
                {if $position_popup == 1} checked="checked"{/if}>
                {l s='Popup' mod='tvcmsvideotab'}</label>
            </div>
        </div>
        </div>

        <div class="col-lg-1">
            <span class="pull-right">
            </span>
        </div>
        <div class="form-group row">
        <div class="col-lg-2" style="text-align: right;">
            <label class="control-label" for="available_for_order">
                {l s='Video Extension:' mod='tvcmsvideotab'}
            </label>
        </div>
        <div class="col-lg-5">
            <input type="text" id="videoextension" name="videoextension" value="{$videoextension|escape:'htmlall':'UTF-8'}">
        </div>
        </div>
        <div class="panel-footer" style="clear: both;">
            <button type="submit" name="saveposition" class="btn btn-default pull-right " id="save"> <i class="process-icon-save"></i>{l s='Save' mod='tvcmsvideotab'}</button>
        </div>        
    </div>
</form>
