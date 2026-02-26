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
*  @author PrestaShop SA <contact@prestashop.com>
*  @copyright  2007-2025 PrestaShop SA
*  @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*}
{strip}
<div class="panel"><h3><i class="icon-list-ul"></i> {l s='Slides list' mod='tvcmsslider'}
	<span class="panel-heading-action">
	<input type="hidden" name="tvcmsinstall_tab_number" id='tvcmsinstall-tab-number'>
            <form id="module_form" action="{$tvurlupgrade}" method="post" enctype="multipart/form-data" novalidate="">
            <button class="btn btn-primary tvcms-sample-install-btn" type="submit" onclick="return confirm('Are you sure want to install sample data?')" name="submitTvcmsSampleinstall">{l s='Install Sample Data' mod="tvcmsslider"}</button>
        </form>
		<a id="desc-product-new" class="list-toolbar-btn" href="{$link->getAdminLink('AdminModules')|escape:'htmlall':'UTF-8'}&configure=tvcmsslider&addSlide=1">
			<span title="" data-toggle="tooltip" class="label-tooltip" data-original-title="{l s='Add new' mod='tvcmsslider'}" data-html="true">
				<i class="process-icon-new "></i>
			</span>
		</a>
	</span>
	</h3>
	<div id="slidesContent">
		<div id="slides">
			{foreach from=$slides item=slide}
				<div id="slides_{$slide.id_slide|escape:'htmlall':'UTF-8'}" class="panel">
					<div class="row">
						<div class="col-lg-1">
							<span><i class="icon-arrows "></i></span>
						</div>
						<div class="col-md-3">
							{if $slide.ivr_value == 'image_upload'}
								<img src="{$image_baseurl|escape:'htmlall':'UTF-8'}{$slide.image|escape:'htmlall':'UTF-8'}" alt="{$slide.title|escape:'htmlall':'UTF-8'}" class="img-thumbnail" />
							{else}
								<video class="img-thumbnail" controls="controls">
	                              <source src="{$image_baseurl|escape:'htmlall':'UTF-8'}{$slide.image|escape:'htmlall':'UTF-8'}" type="video/mp4">
	                            </video>
							{/if}
						</div>
						<div class="col-md-8">
							<h4 class="pull-left">
								#{$slide.id_slide|escape:'htmlall':'UTF-8'} - {$slide.title|escape:'htmlall':'UTF-8'}
								{if $slide.is_shared}
									<div>
										<span class="label color_field pull-left" style="background-color:#108510;color:white;margin-top:5px;">
											{l s='Shared slide' mod='tvcmsslider'}
										</span>
									</div>
								{/if}
							</h4>
							<div class="btn-group-action pull-right">
								{html_entity_decode($slide.status|escape:'html':'UTF-8')}

								<a class="btn btn-default"
									href="{$link->getAdminLink('AdminModules')|escape:'htmlall':'UTF-8'}&configure=tvcmsslider&id_slide={$slide.id_slide|escape:'htmlall':'UTF-8'}">
									<i class="icon-edit"></i>
									{l s='Edit' mod='tvcmsslider'}
								</a>
								<a class="btn btn-default" onclick="return confirm('Are you sure want to delete slider item?')"
									href="{$link->getAdminLink('AdminModules')|escape:'htmlall':'UTF-8'}&configure=tvcmsslider&delete_id_slide={$slide.id_slide|escape:'htmlall':'UTF-8'}">
									<i class="icon-trash"></i>
									{l s='Delete' mod='tvcmsslider'}
								</a>
							</div>
						</div>
					</div>
				</div>
			{/foreach}
		</div>
	</div>
</div>
{/strip}