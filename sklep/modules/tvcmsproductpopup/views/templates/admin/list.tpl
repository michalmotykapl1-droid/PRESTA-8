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

<div class="panel"><h3><i class="icon-list-ul"></i> {l s='Product Filter Group' mod='tvcmsproductpopup'}
	<span class="panel-heading-action">
		<a id="desc-product-new" class="list-toolbar-btn" href="{$link->getAdminLink('AdminModules')|escape:'html':'UTF-8'}&configure=tvcmsproductpopup&addCat=1">
			<span title="" data-toggle="tooltip" class="label-tooltip" data-original-title="Add new" data-html="true">
				<i class="process-icon-new "></i>
			</span>
		</a>
	</span>
	</h3>
	<div id="slidesContent">
		<div id="slides">
			{foreach from=$info_category item=info_cat}
				<div id="slides_{$info_cat.id_tvcmsproductpopup|intval}" class="panel">
					<div class="row">
						<div class="col-md-2">
								<h4 class="pull-left">{l s='Block' mod='tvcmsproductpopup'} #{$info_cat.id_tvcmsproductpopup|escape:'html':'UTF-8'}</h4>
						</div>
						<div class="col-md-2">
							<h4 class="pull-left">{l s='Hook' mod='tvcmsproductpopup'}: {$info_cat.id_hook|escape:'html':'UTF-8'}</h4>
						</div>
						<div class="col-md-2">
							<h4 class="pull-left">{l s='Type Display' mod='tvcmsproductpopup'}: {$info_cat.type_display|escape:'html':'UTF-8'}</h4>
						</div>
						<div class="col-md-6">	
							<div class="btn-group-action pull-right">
								<a class="btn btn-default"
									href="{$link->getAdminLink('AdminModules')|escape:'html':'UTF-8'}&configure=tvcmsproductpopup&id_tvcmsproductpopup={$info_cat.id_tvcmsproductpopup|intval}">
									<i class="icon-edit"></i>
									{l s='Edit' mod='tvcmsproductpopup'}
								</a>
								<a class="btn btn-default"
									href="{$link->getAdminLink('AdminModules')|escape:'html':'UTF-8'}&configure=tvcmsproductpopup&delete_id_group_cat={$info_cat.id_tvcmsproductpopup|intval}">
									<i class="icon-trash"></i>
									{l s='Delete' mod='tvcmsproductpopup'}
								</a>
								<a class="btn {$info_cat.status.class|escape:'html':'UTF-8'}" href="{$info_cat.status.s_href|escape:'html':'UTF-8'}" title="{$info_cat.status.title|escape:'html':'UTF-8'}">
									<i class="{$info_cat.status.icon|escape:'html':'UTF-8'}"></i>
									{$info_cat.status.title|escape:'html':'UTF-8'}
								</a>
							</div>
						</div>
					</div>
				</div>
			{/foreach}
		</div>
	</div>
</div>