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

<form id="module_form" class="defaultForm form-horizontal" action="index.php?controller=AdminModules&amp;configure=tvcmsmegamenu&amp;token={Tools::getAdminTokenLite('AdminModules')|escape:'html':'UTF-8'}" method="post" enctype="multipart/form-data" novalidate="">
<div class="panel">
{* Force-load dynamic products assets with absolute module URL *}
{assign var=modurl value=$smarty.const._MODULE_DIR_|cat:"tvcmsmegamenu/"}
<link rel="stylesheet" href="{$modurl}views/css/bo_dynproducts.css" />
<script src="{$modurl}views/js/bo_dynproducts.js"></script>
<h3><i class="icon-list-ul"></i> {l s='Menu Item' mod='tvcmsmegamenu'}</h3>
	<div class="form-wrapper" id="menuContent" >
		<div class="form-group lab-type-link">
			<label class="control-label col-lg-3">{l s='Type link' mod='tvcmsmegamenu'}</label>
			<div class="col-lg-9">
				<div class="radio tv-radio">
					<label><input type="radio" name="type_link" id="type_link_custom" value="1" {if $menu->type_link == 1}checked="checked" {/if}>Custom Link</label>
				</div>
				<div class="radio tv-radio">
					<label><input type="radio" name="type_link" id="type_link_ps" value="0" {if $menu->type_link == 0}checked="checked" {/if}>PrestaShop link</label>
				</div>
			</div>
		</div>
		
		<div class="form-group tv-menu-title" {if $menu->type_link == 0}style="display:none"{/if}>
			<label class="control-label col-lg-3">{l s='Title' mod='tvcmsmegamenu'}</label>
			<div class="col-lg-9">
				{foreach from=$languages item=language}
					{if $languages|count > 1}
						<div class="translatable-field lang-{$language.id_lang|intval}" {if $language.id_lang != $id_language}style="display:none"{/if}>
					{/if}
					<div class="col-lg-10">
					<input type="text" class="title" id="title_{$language.id_lang|intval}" name="title_{$language.id_lang|intval}" value="{if isset($menu->title[$language.id_lang|intval])}{$menu->title[$language.id_lang|intval]}{else}menu title{/if}"/>
					</div>
					{if $languages|count > 1}
						<div class="col-lg-2">
							<button type="button" class="btn btn-default dropdown-toggle" tabindex="-1" data-toggle="dropdown">
								{$language.iso_code|escape:'html':'UTF-8'}
								<span class="caret"></span>
							</button>
							<ul class="dropdown-menu">
								{foreach from=$languages item=lang}
								<li><a href="javascript:hideOtherLanguage({$lang.id_lang|intval});javascript:changeLangInfor({$lang.id_lang|intval});" tabindex="-1">{$lang.name|escape:'html':'UTF-8'}</a></li>
								{/foreach}
							</ul>
						</div>
					{/if}
					{if $languages|count > 1}
						</div>
					{/if}
				{/foreach}
			</div>
		</div>
		
		<div class="form-group tv-menu-link" {if $menu->type_link == 0}style="display:none"{/if}>
			<label class="control-label col-lg-3">{l s='Link' mod='tvcmsmegamenu'}</label>
			<div class="col-lg-9">
				{foreach from=$languages item=language}
					{if $languages|count > 1}
						<div class="translatable-field lang-{$language.id_lang|intval}" {if $language.id_lang != $id_language}style="display:none"{/if}>
					{/if}
					<div class="col-lg-10">
					<input type="text" id="link_{$language.id_lang|intval}" name="link_{$language.id_lang|intval}" value="{if isset($menu->link[$language.id_lang|intval])}{$menu->link[$language.id_lang|intval]}{else}#{/if}"/>
					</div>
					{if $languages|count > 1}
						<div class="col-lg-2">
							<button type="button" class="btn btn-default dropdown-toggle" tabindex="-1" data-toggle="dropdown">
								{$language.iso_code|escape:'html':'UTF-8'}
								<span class="caret"></span>
							</button>
							<ul class="dropdown-menu">
								{foreach from=$languages item=lang}
								<li><a href="javascript:hideOtherLanguage({$lang.id_lang|intval});javascript:changeLangInfor({$lang.id_lang|intval});" tabindex="-1">{$lang.name|escape:'html':'UTF-8'}</a></li>
								{/foreach}
							</ul>
						</div>
					{/if}
					{if $languages|count > 1}
						</div>
					{/if}
				{/foreach}
			</div>
		</div>
		<div class="form-group ps_link" {if $menu->type_link == 1}style="display:none"{/if}>
			<label class="control-label col-lg-3">{l s='PrestaShop Link' mod='tvcmsmegamenu'}</label>
			<div class="col-lg-9">
				<select class="form-control fixed-width-lg" name="ps_link" id="ps_link">
					{$all_options|escape:'quotes':'UTF-8'}
				</select>
			</div>
			<script type="text/javascript">
				{if $menu->type_link == 0}
				$(document).ready(function(){
					{if isset($menu->link[$id_language]) &&  $menu->link[$id_language] != ''}
						var ps_link_val = '{$menu->link[$id_language]|escape:"html":"UTF-8"}';
					{else}
						var ps_link_val = 'CAT3';
					{/if}
					$("#ps_link").val(ps_link_val);
				});
				{/if}
			</script>
		</div>
		
		<div class="form-group show_sub" {if $menu->type_link == 1}style="display:none"{/if}>
			<label class="control-label col-lg-3">{l s='Show Sub Categories' mod='tvcmsmegamenu'}</label>
			<div class="col-lg-9">
				<span class="switch prestashop-switch fixed-width-lg">
					<input type="radio" name="dropdown" id="dropdown_on" value="1" {if (isset($menu->dropdown) && $menu->dropdown == 1)}checked="checked"{/if}>
					<label for="dropdown_on">Yes</label>
					<input type="radio" name="dropdown" id="dropdown_off" value="0" {if (isset($menu->dropdown) && $menu->dropdown == 0) || !$menu->dropdown}checked="checked"{/if}>
					<label for="dropdown_off">No</label>
					<a class="slide-button btn"></a>
				</span>	
			</div>
		</div>
		
		<div class="form-group">
			<label class="control-label col-lg-3">{l s='Sub Title' mod='tvcmsmegamenu'}</label>
			<div class="col-lg-9">
				{foreach from=$languages item=language}
					{if $languages|count > 1}
						<div class="translatable-field lang-{$language.id_lang|intval}" {if $language.id_lang != $id_language}style="display:none"{/if}>
					{/if}
					<div class="col-lg-7">
						<input type="text" class="subtitle" id="subtitle_{$language.id_lang|intval}" name="subtitle_{$language.id_lang|intval}" value="{if $menu->subtitle[$language.id_lang|intval]}{$menu->subtitle[$language.id_lang|intval]}{/if}"/>
					</div>
					{if $languages|count > 1}
						<div class="col-lg-2">
							<button type="button" class="btn btn-default dropdown-toggle" tabindex="-1" data-toggle="dropdown">
								{$language.iso_code|escape:'html':'UTF-8'}
								<span class="caret"></span>
							</button>
							<ul class="dropdown-menu">
								{foreach from=$languages item=lang}
								<li>
									<a href="javascript:hideOtherLanguage({$lang.id_lang|intval});javascript:changeLangInfor({$lang.id_lang|intval});" tabindex="-1">{$lang.name|escape:'html':'UTF-8'}</a>
								</li>
								{/foreach}
							</ul>
						</div>
					{/if}
					{if $languages|count > 1}
						</div>
					{/if}
				{/foreach}
			</div>
		</div>

		<div class="form-group tv-menu-sub-title-stylesheet">
			<label class="control-label col-lg-3">{l s='Sub Title Stylesheet' mod='tvcmsmegamenu'}</label>
			<div class="col-lg-9">
				{foreach from=$languages item=language}
					{if $languages|count > 1}
						<div class="translatable-field lang-{$language.id_lang|intval}" {if $language.id_lang != $id_language}style="display:none"{/if}>
					{/if}
					<div class="col-lg-7">

						{* <input type="text" class="sub-title-stylesheet" id="sub_title_stylesheet_{$language.id_lang|intval}" name="sub_title_stylesheet_{$language.id_lang|intval}" value="{if !empty($menu->sub_title_stylesheet[$language.id_lang|intval])}{$menu->sub_title_stylesheet[$language.id_lang|intval]}{/if}" /> *}
						
						<textarea class="sub-title-stylesheet" id="sub_title_stylesheet_{$language.id_lang|intval}" name="sub_title_stylesheet_{$language.id_lang|intval}">{if !empty($menu->sub_title_stylesheet[$language.id_lang|intval])}{$menu->sub_title_stylesheet[$language.id_lang|intval]}{/if}</textarea>
						
						<p>{l s='You have to pur inline css. example:- (color:red;font-size:12px)' mod='tvcmsmegamenu'}</p>
						<p>{l s='This css is only use for sub title. if you do not want then keep it blank.' mod='tvcmsmegamenu'}</p>
					</div>
					{if $languages|count > 1}
						<div class="col-lg-2">
							<button type="button" class="btn btn-default dropdown-toggle" tabindex="-1" data-toggle="dropdown">
								{$language.iso_code|escape:'html':'UTF-8'}
								<span class="caret"></span>
							</button>
							<ul class="dropdown-menu">
								{foreach from=$languages item=lang}
								<li><a href="javascript:hideOtherLanguage({$lang.id_lang|intval});javascript:changeLangInfor({$lang.id_lang|intval});" tabindex="-1">{$lang.name|escape:'html':'UTF-8'}</a></li>
								{/foreach}
							</ul>
						</div>
					{/if}
					{if $languages|count > 1}
						</div>
					{/if}
				{/foreach}
			</div>
		</div>
			<div class="form-group">
		<label class="control-label col-lg-3">{l s='Active Title' mod='tvcmsmegamenu'}</label>
		<div class="col-lg-9">
			<span class="switch prestashop-switch fixed-width-lg">
				<input type="radio" name="active_title" id="active_title_on" value="1" {if (isset($menu->active_title) &&  $menu->active_title != 0) || !$menu->active_title}checked="checked"{/if}>
				<label for="active_title_on">Yes</label>
				<input type="radio" name="active_title" id="active_title_off" value="0" {if isset($menu->active_title) && $menu->active_title == 0}checked="checked"{/if}>
				<label for="active_title_off">No</label>
				<a class="slide-button btn"></a>
			</span>	
		</div>
	</div>
		<div class="form-group tv-type-icon">
			<label class="control-label col-lg-3">{l s='Type Icon' mod='tvcmsmegamenu'}</label>
			<div class="col-lg-9">
				<div class="radio tv-radio">
					<label><input type="radio" name="type_icon" id="type_icon_fw" value="1" {if $menu->type_icon == 1}checked="checked"{/if}>Font-Awesome Icon</label>
				</div>
				<div class="radio tv-radio">
					<label><input type="radio" name="type_icon" id="type_icon_img" value="0" {if $menu->type_icon == 0}checked="checked"{/if}>Image icon</label>
				</div>
			</div>
		</div>
		
		<div class="form-group tv-fw-icon" {if $menu->type_icon == 0}style="display:none"{/if}>
			<label class="control-label col-lg-3">{l s='Font-Awesome Icon' mod='tvcmsmegamenu'}</label>
			<div class="col-lg-9">
				<input type="text" class="icon_font" id="icon_font" name="icon_font" value="{if $menu->icon && $menu->type_icon == 1}{$menu->icon|escape:'html':'UTF-8'}{/if}"/>
				<p>{l s='Put class icon of Font-Awesome at :' mod='tvcmsmegamenu'} <a href="http://fortawesome.github.io/Font-Awesome/3.2.1/icons/">http://fortawesome.github.io/Font-Awesome/3.2.1/icons/.</a> ex: icon-sun</p>
			</div>
		</div>
		<div class="form-group tv-img-icon" {if $menu->type_icon == 1}style="display:none"{/if}>
			<label class="control-label col-lg-3">{l s='Image Icon' mod='tvcmsmegamenu'}</label>
			<div class="col-lg-9">
				<div class="col-lg-6">
					<input id="icon_img" type="file" name="icon_img" class="hide">
					<div class="dummyfile input-group">
						<span class="input-group-addon"><i class="icon-file"></i></span>
						<input id="icon_img-name" type="text" name="filename" readonly="">
						<span class="input-group-btn">
							<button id="icon_img-selectbutton" type="button" name="submitAddAttachments" class="btn btn-default">
								<i class="icon-folder-open"></i> {l s='Add file' mod='tvcmsmegamenu'}
							</button>
						</span>
					</div>
					{if $menu->type_icon == 0 && isset($menu->icon) && $menu->icon != ''}
						<img src="{$image_baseurl|escape:'html':'UTF-8'}{$menu->icon|escape:'html':'UTF-8'}" class="img-thumbnail"/>
						{if isset($menu->id)}
							<a href="index.php?controller=AdminModules&amp;configure=tvcmsmegamenu&amp;tab_module=front_office_features&amp;module_name=tvcmsmegamenu&amp;token={Tools::getAdminTokenLite('AdminModules')|escape:'html':'UTF-8'}&amp;removeIcon=1&amp;id_tvcmsmegamenu={$menu->id|intval}" id="del_icon">Remove</a>
						{/if}
					{/if}
					<script type="text/javascript">
						$(document).ready(function(){
							$('#icon_img-selectbutton').click(function(e) {
								$('#icon_img').trigger('click');
							});

							$('#icon_img-name').click(function(e) {
								$('#icon_img').trigger('click');
							});

							$('#icon_img-name').on('dragenter', function(e) {
								e.stopPropagation();
								e.preventDefault();
							});

							$('#icon_img-name').on('dragover', function(e) {
								e.stopPropagation();
								e.preventDefault();
							});

							$('#icon_img-name').on('drop', function(e) {
								e.preventDefault();
								var files = e.originalEvent.dataTransfer.files;
								$('#icon_img')[0].files = files;
								$(this).val(files[0].name);
							});

							$('#icon_img').change(function(e) {
								if ($(this)[0].files !== undefined)
								{
									var files = $(this)[0].files;
									var name  = '';

									$.each(files, function(index, value) {
										name += value.name+', ';
									});

									$('#icon_img-name').val(name.slice(0, -2));
								}
								else // Internet Explorer 9 Compatibility
								{
									var name = $(this).val().split(/[\\/]/);
									$('#icon_img-name').val(name[name.length-1]);
								}
							});

							if (typeof icon_img_max_files !== 'undefined')
							{
								$('#icon_img').closest('form').on('submit', function(e) {
									if ($('#icon_img')[0].files.length > icon_img_max_files) {
										e.preventDefault();
										alert('You can upload a maximum of  files');
									}
								});
							}
						});
					</script>
				</div>
			</div>
		</div>
		
		<div class="form-group tv-img-icon">
			<label class="control-label col-lg-3">{l s='Background Image' mod='tvcmsmegamenu'}</label>
			<div class="col-lg-9">
				<div class="col-lg-6">
					<input id="background_img" type="file" name="background_img" class="hide">
					<div class="dummyfile input-group">
						<span class="input-group-addon"><i class="icon-file"></i></span>
						<input id="background_img-name" type="text" name="filename" readonly="">
						<span class="input-group-btn">
							<button id="background_img-selectbutton" type="button" name="submitAddAttachments" class="btn btn-default">
								<i class="icon-folder-open"></i> {l s='Add file' mod='tvcmsmegamenu'}
							</button>
						</span>
					</div>
					{if isset($menu->background_img) && $menu->background_img != ''}
						<img src="{$image_background_baseurl|escape:'html':'UTF-8'}{$menu->background_img|escape:'html':'UTF-8'}" class="img-thumbnail"/>
						{if isset($menu->id)}
							<a href="index.php?controller=AdminModules&amp;configure=tvcmsmegamenu&amp;tab_module=front_office_features&amp;module_name=tvcmsmegamenu&amp;token={Tools::getAdminTokenLite('AdminModules')|escape:'html':'UTF-8'}&amp;removeBackgroundImg=1&amp;id_tvcmsmegamenu={$menu->id|intval}" id="del_bg_img">Remove</a>
						{/if}
					{/if}
						<script type="text/javascript">
						$(document).ready(function(){
							$('#background_img-selectbutton').click(function(e) {
								$('#background_img').trigger('click');
							});

							$('#background_img-name').click(function(e) {
								$('#background_img').trigger('click');
							});

							$('#background_img-name').on('dragenter', function(e) {
								e.stopPropagation();
								e.preventDefault();
							});

							$('#background_img-name').on('dragover', function(e) {
								e.stopPropagation();
								e.preventDefault();
							});

							$('#background_img-name').on('drop', function(e) {
								e.preventDefault();
								var files = e.originalEvent.dataTransfer.files;
								$('#background_img')[0].files = files;
								$(this).val(files[0].name);
							});

							$('#background_img').change(function(e) {
								if ($(this)[0].files !== undefined)
								{
									var files = $(this)[0].files;
									var name  = '';

									$.each(files, function(index, value) {
										name += value.name+', ';
									});

									$('#background_img-name').val(name.slice(0, -2));
								}
								else // Internet Explorer 9 Compatibility
								{
									var name = $(this).val().split(/[\\/]/);
									$('#background_img-name').val(name[name.length-1]);
								}
							});

							if (typeof background_img_max_files !== 'undefined')
							{
								$('#background_img').closest('form').on('submit', function(e) {
									if ($('#background_img')[0].files.length > background_img_max_files) {
										e.preventDefault();
										alert('You can upload a maximum of  files');
									}
								});
							}
						});
					</script>
			</div>
		</div>
	</div>



	<div class="form-group">
		<label class="control-label col-lg-3">{l s='Align Of Sub-Menu' mod='tvcmsmegamenu'}</label>
		<div class="col-lg-9">
			<select class="form-control fixed-width-lg" name="align_sub" id="align_sub">
				<option value="tv-sub-left">{l s='Left' mod='tvcmsmegamenu'}</option>
				<option value="tv-sub-right">{l s='Right' mod='tvcmsmegamenu'}</option>
				<option value="tv-sub-center">{l s='Center' mod='tvcmsmegamenu'}</option>
				<option value="tv-sub-auto">{l s='Auto' mod='tvcmsmegamenu'}</option>
			</select>
			<script type="text/javascript">
				$(document).ready(function(){
					{if isset($menu->align_sub) && $menu->align_sub != ''}
						var align_sub_val = '{$menu->align_sub|escape:"html":"UTF-8"}';
					{else}
						var align_sub_val = 'tv-sub-auto';
					{/if}
					$('#align_sub').val(align_sub_val);
				});
			</script>
		</div>
	</div>
	<div class="form-group">
		<label class="control-label col-lg-3">{l s='Width Of Sub-Menu' mod='tvcmsmegamenu'}</label>
		<div class="col-lg-9">
			<select class="form-control fixed-width-lg" name="width_sub" id="width_sub">
				<option value="col-sm-2">col-sm-2</option>
				<option value="col-sm-3">col-sm-3</option>
				<option value="col-sm-4">col-sm-4</option>
				<option value="col-sm-5">col-sm-5</option>
				<option value="col-sm-6">col-sm-6</option>
				<option value="col-sm-7">col-sm-7</option>
				<option value="col-sm-8">col-sm-8</option>
				<option value="col-sm-9">col-sm-9</option>
				<option value="col-sm-10">col-sm-10</option>
				<option value="col-sm-11">col-sm-11</option>
				<option value="col-sm-12">col-sm-12</option>
			</select>
			<script type="text/javascript">
				$(document).ready(function() {
					{if isset($menu->width_sub) &&  $menu->width_sub != ''}
						var width_sub_val = '{$menu->width_sub|escape:"html":"UTF-8"}';
					{else}
						var width_sub_val = 'col-sm-12';
					{/if}
					$("#width_sub").val(width_sub_val);
				});
			</script>
		</div>
	</div>
	<div class="form-group">
		<label class="control-label col-lg-3">{l s='Class' mod='tvcmsmegamenu'}</label>
		<div class="col-lg-5">
			<input type="text" class="class" id="class" name="class" value="{if $menu->class}{$menu->class|escape:'html':'UTF-8'}{/if}"/>
		</div>
	</div>
	<div class="form-group">
		<label class="control-label col-lg-3">{l s='Active' mod='tvcmsmegamenu'}</label>
		<div class="col-lg-9">
			<span class="switch prestashop-switch fixed-width-lg">
				<input type="radio" name="active" id="active_on" value="1" {if (isset($menu->active) &&  $menu->active != 0) || !$menu->active}checked="checked"{/if}>
				<label for="active_on">Yes</label>
				<input type="radio" name="active" id="active_off" value="0" {if isset($menu->active) && $menu->active == 0}checked="checked"{/if}>
				<label for="active_off">No</label>
				<a class="slide-button btn"></a>
			</span>	
		</div>
	</div>


	
	<div class="panel-footer" style="margin-bottom:10px;">
		<input type="hidden" name="id_tvcmsmegamenu" id="id_tvcmsmegamenu" value="{if isset($menu->id)}{$menu->id|intval}{/if}"/>
		<button type="submit" value="1" id="module_form_submit_btn" name="submitMenuItem" class="btn btn-default pull-right">
			<i class="process-icon-save"></i> Save
		</button>
		<a href="index.php?controller=AdminModules&amp;configure=tvcmsmegamenu&amp;token={$token|escape:'html':'UTF-8'}" class="btn btn-default">
		<i class="process-icon-back"></i> Back to list</a>
	</div>
	
</div>
</form>