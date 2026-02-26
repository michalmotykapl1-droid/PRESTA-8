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
*  @author    PrestaShop SA <contact@prestashop.com>
*  @copyright 2007-2025 PrestaShop SA
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*}
{strip}
{include file='../admin/promotional.tpl'}
<div class="tvadmincustom-setting">
	<div class="tvadmincustom-setting-all-tabs">
		{if $show_fields.form_1 == true}
			<div tab-number='#fieldset_0' class="tvadmincustom-setting-tab {if $tab_number == '#fieldset_0'}tvadmincustom-setting-active{/if}">{l s='Theme Configuration' mod='tvcmscustomsetting'}</div>
		{/if}

		{if $show_fields.form_2 == true}
			<div tab-number='#fieldset_1_1' class="tvadmincustom-setting-tab {if $tab_number == '#fieldset_1_1'}tvadmincustom-setting-active{/if}">{l s='App Link' mod='tvcmscustomsetting'}</div>
		{/if}

		{if $show_fields.form_3 == true}
			<div tab-number='#fieldset_2_2' class="tvadmincustom-setting-tab {if $tab_number == '#fieldset_2_2'}tvadmincustom-setting-active{/if}">{l s='Custom Titles' mod='tvcmscustomsetting'}</div>
		{/if}
		{if $show_fields.form_4 == true}
			<div tab-number='#fieldset_3_3' class="tvadmincustom-setting-tab {if $tab_number == '#fieldset_3_3'}tvadmincustom-setting-active{/if}">{l s='Layout Configuration' mod='tvcmscustomsetting'}</div>
		{/if}
		{if $show_fields.form_5 == true}
			<div tab-number='#fieldset_4_4' class="tvadmincustom-setting-tab {if $tab_number == '#fieldset_4_4'}tvadmincustom-setting-active{/if}">{l s='Import Sample Data' mod='tvcmscustomsetting'}</div>
		{/if}
	</div>
</div>

{/strip}