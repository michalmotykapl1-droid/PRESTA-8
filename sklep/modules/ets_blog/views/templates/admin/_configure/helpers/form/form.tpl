{*
 * Copyright ETS Software Technology Co., Ltd
 *
 * NOTICE OF LICENSE
 *
 * This file is not open source! Each license that you purchased is only available for 1 website only.
 * If you want to use this file on more websites (or projects), you need to purchase additional licenses.
 * You are not allowed to redistribute, resell, lease, license, sub-license or offer our resources to any third party.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future.
 *
 * @author ETS Software Technology Co., Ltd
 * @copyright  ETS Software Technology Co., Ltd
 * @license    Valid for 1 website (or project) for each purchase of license
*}
{extends file="helpers/form/form.tpl"}

{block name="input"}
    {if $input.type == 'switch'}
    	<span class="switch prestashop-switch fixed-width-lg">
    		{foreach $input.values as $value}
    		<input type="radio" name="{$input.name|escape:'html':'UTF-8'}"{if $value.value == 1} id="{$input.name|escape:'html':'UTF-8'}_on"{else} id="{$input.name|escape:'html':'UTF-8'}_off"{/if} value="{$value.value|escape:'html':'UTF-8'}"{if $fields_value[$input.name] == $value.value} checked="checked"{/if}{if isset($input.disabled) && $input.disabled} disabled="disabled"{/if}/>
    		{strip}
    		<label {if $value.value == 1} for="{$input.name|escape:'html':'UTF-8'}_on"{else} for="{$input.name|escape:'html':'UTF-8'}_off"{/if}>
    			{$value.label|escape:'html':'UTF-8'}
    		</label>
    		{/strip}
    		{/foreach}
    		<a class="slide-button btn"></a>
    	</span>
    {elseif $input.type == 'checkbox'}
            {if isset($input.values.query) && $input.values.query}
                {assign var=id_checkbox value=$input.name|cat:'_'|cat:'all'}
                {assign var=checkall value=true}
				{foreach $input.values.query as $value}
    				{if !(isset($fields_value[$input.name]) && is_array($fields_value[$input.name]) && $fields_value[$input.name] && in_array($value.value,$fields_value[$input.name]))} 
                        {assign var=checkall value=false}
                    {/if}
    			{/foreach} 
                <div class="checkbox_all checkbox">
					{strip}
						<label for="{$id_checkbox|escape:'html':'UTF-8'}">                                
							<input type="checkbox" name="{$input.name|escape:'html':'UTF-8'}[]" id="{$id_checkbox|escape:'html':'UTF-8'}" {if isset($value.value)} value="0"{/if}{if $checkall} checked="checked"{/if} />
							{l s='Select/Unselect all' mod='ets_blog'}
						</label>
					{/strip}
				</div>
                {foreach $input.values.query as $value}
    				{assign var=id_checkbox value=$input.name|cat:'_'|cat:$value[$input.values.id]|escape:'html':'UTF-8'}
    				<div class="checkbox{if isset($input.expand) && strtolower($input.expand.default) == 'show'} hidden{/if}">
    					{strip}
    						<label for="{$id_checkbox|escape:'html':'UTF-8'}">                                
    							<input type="checkbox" name="{$input.name|escape:'html':'UTF-8'}[]" id="{$id_checkbox|escape:'html':'UTF-8'}" {if isset($value.value)} value="{$value.value|escape:'html':'UTF-8'}"{/if}{if isset($fields_value[$input.name]) && is_array($fields_value[$input.name]) && $fields_value[$input.name] && in_array($value.value,$fields_value[$input.name])} checked="checked"{/if} />
    							{$value[$input.values.name]|escape:'html':'UTF-8'}
    						</label>
    					{/strip}
    				</div>
    			{/foreach} 
            {/if}
    {elseif $input.type == 'file_lang'}
		{if $languages|count > 1}
		  <div class="form-group">
		{/if}
			{foreach from=$languages item=language}
				{if $languages|count > 1}
					<div class="translatable-field lang-{$language.id_lang|intval}" {if $language.id_lang != $defaultFormLanguage}style="display:none"{/if}>
				    <div class="col-lg-9">
                {else}
                    <div class="col-lg-12">
                {/if}
					   <div class="dummyfile input-group sass">
							<input id="{$input.name|escape:'html':'UTF-8'}_{$language.id_lang|intval}" type="file" name="{$input.name|escape:'html':'UTF-8'}_{$language.id_lang|intval}" class="hide-file-upload" />
							<span class="input-group-addon"><i class="icon-file"></i></span>
							<input id="{$input.name|escape:'html':'UTF-8'}_{$language.id_lang|intval}-name" type="text" class="disabled" name="filename" readonly />
							<span class="input-group-btn">
								<button id="{$input.name|escape:'html':'UTF-8'}_{$language.id_lang|intval}-selectbutton" type="button" name="submitAddAttachments" class="btn btn-default">
									<i class="icon-folder-open"></i> {l s='Choose a file' mod='ets_blog'}
								</button>
							</span>
						</div>
                        {if isset($fields_value[$input.name]) && $fields_value[$input.name] && $fields_value[$input.name][$language.id_lang]}
                            <div class="clearfix"></div>
                            <p>&nbsp;</p>
                            <div class="uploaded_img_wrapper">
                        		<a target="_blank" class="ets_fancy" href="{$image_baseurl|escape:'html':'UTF-8'}{$fields_value[$input.name][$language.id_lang]|escape:'html':'UTF-8'}">
                                    <img title="{l s='Click to see full size image' mod='ets_blog'}" style="display: inline-block; max-width: 200px;" src="{$image_baseurl|escape:'html':'UTF-8'}{$fields_value[$input.name][$language.id_lang]|escape:'html':'UTF-8'}" />
                                </a>
                                {if $input.name=='thumb' &&  isset($thumb_del_link) && $thumb_del_link && !(isset($input.required) && $input.required)}
                                    <a class="delete_url"  style="display: inline-block; text-decoration: none!important;" href="{$thumb_del_link|escape:'html':'UTF-8'}&id_lang={$language.id_lang|intval}"><span style="color: #666"><i style="font-size: 20px;" class="process-icon-delete"></i></span></a>
                                {/if}
                                {if $input.name=='image' &&  isset($img_del_link) && $img_del_link && !(isset($input.required) && $input.required)}
                                    <a class="delete_url"  style="display: inline-block; text-decoration: none!important;" href="{$img_del_link|escape:'html':'UTF-8'}&id_lang={$language.id_lang|intval}"><span style="color: #666"><i style="font-size: 20px;" class="process-icon-delete"></i></span></a>
                                {/if}
                            </div>

                        {/if}
					</div>
				{if $languages|count > 1}
					<div class="col-lg-2">
						<button type="button" class="btn btn-default dropdown-toggle" tabindex="-1" data-toggle="dropdown">
							{$language.iso_code|escape:'html':'UTF-8'}
							<span class="caret"></span>
						</button>
						<ul class="dropdown-menu">
							{foreach from=$languages item=lang}
							<li><a href="javascript:hideOtherLanguage({$lang.id_lang|intval});" tabindex="-1">{$lang.name|escape:'html':'UTF-8'}</a></li>
							{/foreach}
						</ul>
					</div>
				{/if}
				{if $languages|count > 1}
					</div>
				{/if}
				<script>
				$(document).ready(function(){
					$("#{$input.name|escape:'html':'UTF-8'}_{$language.id_lang|intval}-selectbutton").click(function(e){
						$("#{$input.name|escape:'html':'UTF-8'}_{$language.id_lang|intval}").trigger('click');
					});
                    $("#{$input.name|escape:'html':'UTF-8'}_{$language.id_lang|intval}-name").click(function(e){
                        $("#{$input.name|escape:'html':'UTF-8'}_{$language.id_lang|intval}").trigger('click');
                    });
					$("#{$input.name|escape:'html':'UTF-8'}_{$language.id_lang|intval}").change(function(e){
						var val = $(this).val();
						var file = val.split(/[\\/]/);
						$("#{$input.name|escape:'html':'UTF-8'}_{$language.id_lang|intval}-name").val(file[file.length-1]);
					});
				});
			</script>
			{/foreach}
		{if $languages|count > 1}
		  </div>
		{/if}
    {else}
        {$smarty.block.parent}               
    {/if}            
{/block}
{block name="field"}
    {if $input.type!='select_category' &&  $input.type != 'profile_employee' && $input.type != 'blog_categories' && $input.type != 'products_search' && $input.name !='url_alias'}
        {$smarty.block.parent}
    	{if $input.type == 'file' && (!isset($input.imageType) || isset($input.imageType) && $input.imageType!='thumb')&&  isset($display_img) && $display_img}
            <label class="control-label col-lg-3 uploaded_image_label" style="font-style: italic;">{l s='Uploaded image: ' mod='ets_blog'}</label>
            <div class="col-lg-9 uploaded_img_wrapper">
        		<a target="_blank" class="ets_fancy" href="{$display_img|escape:'html':'UTF-8'}"><img title="{l s='Click to see full size image' mod='ets_blog'}" style="display: inline-block; max-width: 200px;" src="{$display_img|escape:'html':'UTF-8'}" /></a>
                {if isset($img_del_link) && $img_del_link && !(isset($input.required) && $input.required)}
                    <a class="delete_url" style="display: inline-block; text-decoration: none!important;" href="{$img_del_link|escape:'html':'UTF-8'}"><span style="color: #666"><i style="font-size: 20px;" class="process-icon-delete"></i></span></a>
                {/if}
            </div>
        {elseif $input.type == 'file' && isset($input.imageType) && $input.imageType=='thumb' &&  isset($display_thumb) && $display_thumb}
    	    <label class="control-label col-lg-3 uploaded_image_label" style="font-style: italic;">{l s='Uploaded image: ' mod='ets_blog'}</label>
            <div class="col-lg-9 uploaded_img_wrapper">
        		<a target="_blank" class="ets_fancy" href="{$display_thumb|escape:'html':'UTF-8'}"><img title="{l s='Click to see full size image' mod='ets_blog'}" style="display: inline-block; max-width: 200px;" src="{$display_thumb|escape:'html':'UTF-8'}" /></a>
                {if isset($thumb_del_link) && $thumb_del_link && !(isset($input.required) && $input.required)}
                    <a class="delete_url"  style="display: inline-block; text-decoration: none!important;" href="{$thumb_del_link|escape:'html':'UTF-8'}"><span style="color: #666"><i style="font-size: 20px;" class="process-icon-delete"></i></span></a>
                {/if}
            </div>
        {/if}
        {if $input.name=='YBC_BLOG_ENABLE_RSS'}
            <span class="link_rss rss_setting">
                <label class="link-rss-label">{l s='RSS feed URLs' mod='ets_blog'}:</label>
                <span class="link-rss-lang">
                    {foreach from=$urls_rss item='url_rss'}
                        <span>
                            <img src="{$url_rss.img|escape:'html':'UTF-8'}"/><a href="{$url_rss.link|escape:'html':'UTF-8'}" target="_blank">&nbsp;{$url_rss.link|escape:'html':'UTF-8'}</a>
                        </span>
                    {/foreach}
                </span>
            </span>
        {/if}
        {if $input.name=='YBC_BLOG_ENABLE_SITEMAP'}
            <span class="link_sitemap sitemap_setting">
                <label class="link-sitemap-label">{l s='Sitemap URLs' mod='ets_blog'}</label>
                <br />
                <span class="main-sitemap">{l s='Main sitemap' mod='ets_blog'}:&nbsp;<a href="{$url_sitemap|escape:'html':'UTF-8'}" target="_blank">{$url_sitemap|escape:'html':'UTF-8'}</a></span>
                <span class="link-sitemap-lang">
                    {foreach from=$urls_sitemap item='url_sitemap'}
                        <span>
                            <img src="{$url_sitemap.img|escape:'html':'UTF-8'}"/><a href="{$url_sitemap.link|escape:'html':'UTF-8'}" target="_blank">&nbsp;{$url_sitemap.link|escape:'html':'UTF-8'}</a><br />
                        </span>
                    {/foreach}
                </span>
            </span>
        {/if}
    {else}
        {if $input.type=='select_category'}
            <div class="col-lg-9">
                <select id="{$input.name|escape:'html':'UTF-8'}" class=" fixed-width-xl" name="{$input.name|escape:'html':'UTF-8'}">
                    {$input.blogCategoryotpionsHtml nofilter}
                </select>
            </div>
        {/if}
        {if $input.type == 'blog_categories'}
            <div class="col-lg-9">
                <ul style="float: left; padding: 0; margin-top: 5px;">
                    {$input.html_content nofilter}
                </ul>
                {if isset($input.desc) && $input.desc}
                    <p class="help-block">{$input.desc|escape:'html':'UTF-8'} </p>
                {/if}
            </div>
        {/if}
        {if $input.type == 'profile_employee'}
            <div class="col-lg-9">
                <ul style="float: left; padding: 0; margin-top: 5px">
                    {if $input.profiles}
                        {foreach from=$input.profiles item='profile'}
                            {if $profile.title}
                                <li style="list-style: none;"><input {if in_array($profile.id, $input.selected_profile)} checked="checked" {/if} style="margin: 2px 7px 0 5px; float: left;" type="checkbox" value="{$profile.id|escape:'html':'UTF-8'}" name="profile_employee[]" id="ets_input_profile_employee_{str_replace(' ','_',$profile.id)|escape:'html':'UTF-8'}" /><label for="ets_input_profile_employee_{$profile.id|escape:'html':'UTF-8'}">{$profile.title|escape:'html':'UTF-8'}</label></li>
                            {/if}                            
                        {/foreach}
                    {/if}
                </ul>
            </div>
        {/if}
        {if $input.name == "url_alias"}
    		<script type="text/javascript">
        		{if isset($PS_ALLOW_ACCENTED_CHARS_URL) && $PS_ALLOW_ACCENTED_CHARS_URL}
        			var PS_ALLOW_ACCENTED_CHARS_URL = 1;
        		{else}
        			var PS_ALLOW_ACCENTED_CHARS_URL = 0;
        		{/if}
            </script>
            {$smarty.block.parent}
 	    {/if}
        {if $input.type == 'products_search'}
            <div class="col-lg-9">
                <div id="ajax_choose_product">
                    <input type="hidden" name="inputAccessories" id="inputAccessories" value="{if $input.selected_products}{foreach from=$input.selected_products item=accessory}{$accessory.id_product|intval}-{/foreach}{/if}" />
			        <input type="hidden" name="nameAccessories" id="nameAccessories" value="{if $input.selected_products}{foreach from=$input.selected_products item=accessory}{$accessory.name|escape:'html':'UTF-8'}¤{/foreach}{/if}" />
			
    				<div class="input-group">
    					<input type="text" id="product_autocomplete_input" name="product_autocomplete_input" placeholder="{l s='Search product by name, reference or ID' mod='ets_blog'}" />
    					<span class="input-group-addon"><i class="icon-search"></i></span>
    				</div>
                    <div id="divAccessories">
                        {if $input.selected_products}    
                            {foreach from=$input.selected_products item=accessory}
                    			<div class="form-control-static form-control-static_{$accessory.id_product|intval}">
                    				<button type="button" class="btn btn-default" onclick="ybcDelAccessory({$accessory.id_product|intval});" name="{$accessory.id_product|intval}">
                    					<i class="icon-remove text-danger"></i>
                    				</button>
                                    <img src="{$accessory.link_image|escape:'html':'UTF-8'}" style="width:32px;" />
                    				{$accessory.name|escape:'html':'UTF-8'}{if !empty($accessory.reference)}{$accessory.reference|escape:'html':'UTF-8'}{/if}
                    			</div>
                			{/foreach}    		     	
                        {/if}		
        			</div>
    			</div>
                <p class="help-block">{l s='Related products are displayed on the post detail page' mod='ets_blog'}</p>
            </div>
        {/if}
    {/if}
{/block}

{block name="footer"}
    {capture name='form_submit_btn'}{counter name='form_submit_btn'}{/capture}
	{if isset($fieldset['form']['submit']) || isset($fieldset['form']['buttons'])}
		{if isset($blog_object)}
            <input type="hidden" name="blog_object" value="{$blog_object|escape:'html':'UTF-8'}" />
            <input type="hidden" name="itemId" value="{$item_id|intval}" />
            <input type="hidden" name="blog_form_submitted" value="1" />
        {/if}
        <div class="panel-footer">
            {if isset($cancel_url) && $cancel_url}
                <a class="btn btn-default" href="{$cancel_url|escape:'html':'UTF-8'}"><i class="process-icon-cancel"></i>{l s='Back' mod='ets_blog'}</a>
            {/if}
            {if isset($fieldset['form']['submit']) && !empty($fieldset['form']['submit'])}
                <div class="img_loading_wrapper">
                    {*
                    <img src="{$image_baseurl|escape:'html':'UTF-8'}img/loading-admin.gif" title="{l s='Loading' mod='ets_blog'}" class="ets_blog_loading" />
                    *}
                </div>
    			<button type="submit" value="1"	id="{if isset($fieldset['form']['submit']['id'])}{$fieldset['form']['submit']['id']|escape:'html':'UTF-8'}{else}{$table|escape:'html':'UTF-8'}_form_submit_btn{/if}{if $smarty.capture.form_submit_btn > 1}_{($smarty.capture.form_submit_btn - 1)|intval}{/if}" name="{if isset($fieldset['form']['submit']['name'])}{$fieldset['form']['submit']['name']|escape:'html':'UTF-8'}{else}{$submit_action|escape:'html':'UTF-8'}{/if}{if isset($fieldset['form']['submit']['stay']) && $fieldset['form']['submit']['stay']}AndStay{/if}" class="{if isset($fieldset['form']['submit']['class'])}{$fieldset['form']['submit']['class']|escape:'html':'UTF-8'}{else}btn btn-default pull-right{/if}">
    				<i class="{if isset($fieldset['form']['submit']['icon'])}{$fieldset['form']['submit']['icon']|escape:'html':'UTF-8'}{else}process-icon-save{/if}"></i> {$fieldset['form']['submit']['title']|escape:'html':'UTF-8'}
    			</button>
			{/if}
            {if isset($fieldset['form']['buttons'])}
                <a class="link_preview_post" href="http://localhost/ps1760/en/blog/post/2-test.html" target="_blank">&nbsp;&nbsp;</a>
    			{foreach from=$fieldset['form']['buttons'] item=btn key=k}
    				{if isset($btn.href) && trim($btn.href) != ''}
    					<a href="{$btn.href|escape:'html':'UTF-8'}" {if isset($btn['id'])}id="{$btn['id']|escape:'html':'UTF-8'}"{/if} class="btn btn-default{if isset($btn['class'])} {$btn['class']|escape:'html':'UTF-8'}{/if}" {if isset($btn.js) && $btn.js} onclick="{$btn.js|escape:'html':'UTF-8'}"{/if}>{if isset($btn['icon'])}<i class="{$btn['icon']|escape:'html':'UTF-8'}" ></i> {/if}{$btn.title|escape:'html':'UTF-8'}</a>
    				{else}
    					<button type="{if isset($btn['type'])}{$btn['type']|escape:'html':'UTF-8'}{else}button{/if}" {if isset($btn['id'])}id="{$btn['id']|escape:'html':'UTF-8'}"{/if} class="btn btn-default{if isset($btn['class'])} {$btn['class']|escape:'html':'UTF-8'}{/if}" name="{if isset($btn['name'])}{$btn['name']|escape:'html':'UTF-8'}{else}submitOptions{$table|escape:'html':'UTF-8'}{/if}"{if isset($btn.js) && $btn.js} onclick="{$btn.js|escape:'html':'UTF-8'}"{/if}>{if isset($btn['icon'])}<i class="{$btn['icon']|escape:'html':'UTF-8'}" ></i> {/if}{$btn.title|escape:'html':'UTF-8'}</button>
    				{/if}
    			{/foreach}
			{/if}
		</div>
	{/if}
{/block}
{block name="legend"}
	<div class="panel-heading">
		{if isset($field.image) && isset($field.title)}<img src="{$field.image|escape:'html':'UTF-8'}" alt="{$field.title|escape:'html':'UTF-8'}" />{/if}
		{if isset($field.icon)}<i class="{$field.icon|escape:'html':'UTF-8'}"></i>{/if}
		{$field.title|escape:'html':'UTF-8'}
        {if isset($addNewUrl) || (isset($preview_link) && $preview_link)}
            <span class="panel-heading-action">  
                {if isset($preview_link) && $preview_link}            
                    <a target="_blank" class="list-toolbar-btn" href="{$preview_link|escape:'html':'UTF-8'}">
                        <span data-placement="top" data-html="true" data-original-title="{l s='View post ' mod='ets_blog'}" class="label-tooltip" data-toggle="tooltip" title="">
            				<i style="margin-left: 5px;" class="icon-search-plus"></i>
                        </span>
                    </a>            
                {/if}   
                {if isset($addNewUrl)}                     
                    <a class="list-toolbar-btn ets-blog-add-new" href="{$addNewUrl|escape:'html':'UTF-8'}">
                        <span data-placement="top" data-html="true" data-original-title="{l s='Add new item ' mod='ets_blog'}" class="label-tooltip" data-toggle="tooltip" title="">
            				<i class="process-icon-new"></i>
                        </span>
                    </a> 
                {/if}
                         
            </span>
        {/if}
        
         {if isset($post_key) && $post_key}<input type="hidden" name="post_key" value="{$post_key|escape:'html':'UTF-8'}" />{/if}
	</div>
    {if isset($configTabs) && $configTabs}
        <ul>
            {foreach from=$configTabs item='tab' key='tabId'}
                <li class="confi_tab config_tab_{$tabId|escape:'html':'UTF-8'}" data-tab-id="{$tabId|escape:'html':'UTF-8'}">{$tab|escape:'html':'UTF-8'}</li>
            {/foreach}
        </ul>
    {/if}
    {if isset($tab_post) && $tab_post}
        <ul class="tab_post">
            <li class="confi_tab config_tab_basic active" data-tab-id="basic">{l s='Basic content' mod='ets_blog'}</li>
            <li class="confi_tab config_tab_seo" data-tab-id="seo">{l s='Seo' mod='ets_blog'}</li>
            
        </ul>
    {/if}
    {if isset($tab_category) && $tab_category}
        <ul class="tab_post">
            <li class="confi_tab config_tab_basic active" data-tab-id="basic">{l s='Basic info' mod='ets_blog'}</li>
            <li class="confi_tab config_tab_seo" data-tab-id="seo">{l s='Seo' mod='ets_blog'}</li>
        </ul>
    {/if}
{/block}

{block name="input_row"}
    {if $input.name=='YBC_BLOG_SIDEBAR_POSITION' || $input.name=='YBC_BLOG_HOME_POST_TYPE'}
        <h3 class="title-elements">{l s='Configuration' mod='ets_blog'}</h3>
    {/if}
    {if $input.name=='YBC_BLOG_SHOW_LATEST_NEWS_BLOCK'}
        <ul id="sidebar-positions" class="sidebar-positions">
            {foreach from =$position_sidebar key='key' item='position'}
                {assign var ='sidebar' value=$sidebars.$position}
                {if isset($fields_value[$sidebar.name])}
                    {assign var='value_text' value=$fields_value[$sidebar.name]}
                {else}
                    {assign var='value_text' value=0}
                {/if}
                <li id="sidebar-position-{$position|escape:'html':'UTF-8'}">
                    <div>
                        <div class="title-sidebar"> 
                        <span class="position_number" >
                            <span>
                            {$key+1|intval}
                            </span>
                        </span>
                        {$sidebar.title|escape:'html':'UTF-8'} 
                        </div>
                        {*{if $position!='sidebar_rss'}*}
                            <div class="setting" data-setting="{$position|escape:'html':'UTF-8'}"><i class="icon-AdminAdmin"></i>{l s='Setting' mod='ets_blog'}</div>
                            <label class="ets_sl_switch{if $value_text} active{/if}">
                                <input class="ets_sl_slider" type="checkbox" {if $value_text}checked ="checked"{/if} value="1" data-field="{$sidebar.name|escape:'html':'UTF-8'}"/>
                                <span class="ets_sl_slider_label on">{l s='On' mod='ets_blog'}</span>
                                <span class="ets_sl_slider_label off">{l s='Off' mod='ets_blog'}</span>
                            </label>
                        {*{/if}*}
                        
                    </div>
                </li>
            {/foreach}
        </ul>
    {/if}
    {if $input.name=='YBC_BLOG_SHOW_LATEST_BLOCK_HOME'}
        <ul id="sidebar-positions" class="sidebar-positions">
            {foreach from =$position_homepages key='key' item='position'}
                {assign var ='homepage' value=$homepages.$position}
                {assign var='value_text' value=$fields_value[$homepage.name]}
                <li id="sidebar-position-{$position|escape:'html':'UTF-8'}">
                    <div>
                        <div class="title-sidebar">
                            <span class="position_number" >
                                <span>
                                    {$key+1|intval}
                                </span>
                            </span>
                            {$homepage.title|escape:'html':'UTF-8'}
                        </div>
                        <div class="setting" data-setting="{$position|escape:'html':'UTF-8'}"><i class="icon-AdminAdmin"></i>{l s='Setting' mod='ets_blog'}</div>
                        <label class="ets_sl_switch{if $value_text} active{/if}">
                            <input class="ets_sl_slider" type="checkbox" {if $value_text} checked ="checked"{/if} value="1" data-field="{$homepage.name|escape:'html':'UTF-8'}" />
                            <span class="ets_sl_slider_label on">{l s='On' mod='ets_blog'}</span>
                            <span class="ets_sl_slider_label off">{l s='Off' mod='ets_blog'}</span>
                        </label>
                    </div>
                </li>
            {/foreach}
        </ul>
    {/if}
    {if $input.name=='YBC_BLOG_SHOW_LATEST_BLOCK_HOME' || $input.name=='YBC_BLOG_SHOW_POPULAR_BLOCK_HOME' || $input.name=='YBC_BLOG_SHOW_FEATURED_BLOCK_HOME' || $input.name=="YBC_BLOG_SHOW_CATEGORY_BLOCK_HOME" || $input.name=="YBC_BLOG_SHOW_GALLERY_BLOCK_HOME" ||  $input.name=="YBC_BLOG_SHOW_LATEST_NEWS_BLOCK" ||$input.name=='YBC_BLOG_SHOW_FEATURED_BLOCK' || $input.name=='YBC_BLOG_SHOW_POPULAR_POST_BLOCK' || $input.name=='YBC_BLOG_SHOW_GALLERY_BLOCK' || $input.name=='YBC_BLOG_SHOW_ARCHIVES_BLOCK' || $input.name=='YBC_BLOG_SHOW_CATEGORIES_BLOCK' || $input.name=='YBC_BLOG_SHOW_SEARCH_BLOCK'|| $input.name=='YBC_BLOG_SHOW_TAGS_BLOCK' || $input.name=='YBC_BLOG_SHOW_COMMENT_BLOCK' || $input.name=='YBC_BLOG_SHOW_AUTHOR_BLOCK' || $input.name=='YBC_BLOG_ENABLE_RSS_SIDEBAR' || $input.name=='YBC_BLOG_SHOW_HTML_BOX'}
        <div class="ets-form-group-sidebar{if isset($input.form_group_class)} {$input.form_group_class|escape:'html':'UTF-8'}{/if}">
            <div class="ets_table">
            <div class="ets_table-cell">
            <div class="ets-form-group-sidebar-wapper">
            <span class="close-setting-sidebar">{l s='Close' mod='ets_blog'}</span>
            <div class="setting-title" ><i class="icon-AdminAdmin"></i>{l s='Setting' mod='ets_blog'}</div>
    {/if}
    {if $input.name=='YBC_BLOG_ENABLE_MAIL'}
        <p><strong>{l s='Send email to Administrator and Authors: ' mod='ets_blog'}</strong></p>
    {/if}
    {if $input.name=='YBC_BLOG_ENABLE_MAIL_NEW_COMMENT'}
        <p><strong>{l s='Send email to Users/Customers:' mod='ets_blog'}</strong></p>
    {/if}
    {if $input.type=='image'}
        <div class="form-group">
            <div class="label-text">{$input.label|escape:'html':'UTF-8'}</div> 
            <label class="control-label col-lg-3 required">{l s='Width' mod='ets_blog'}</label>
            <div class="col-lg-9">
                <div class="input-group ">
                    <input id="{$input.name|escape:'html':'UTF-8'}_WIDTH" class="" type="text" required="required" value="{$fields_value[$input.name]['width']|intval}" name="{$input.name|escape:'html':'UTF-8'}_WIDTH" />
                    <span class="input-group-addon"> px </span>
                </div>
                <p class="help-block">{l s='Valid values: 50 - 3000' mod='ets_blog'} </p>
            </div>
            <label class="control-label col-lg-3 required">{l s='Height' mod='ets_blog'}</label>
            <div class="col-lg-9">
                <div class="input-group ">
                    <input id="{$input.name|escape:'html':'UTF-8'}_HEIGHT" class="" type="text" required="required" value="{$fields_value[$input.name]['height']|intval}" name="{$input.name|escape:'html':'UTF-8'}_HEIGHT" />
                    <span class="input-group-addon"> px </span>
                </div>
                <p class="help-block">{l s='Valid values: 50 - 3000' mod='ets_blog'} </p>
            </div>
        </div>
    {elseif (isset($isConfigForm) && $isConfigForm) || (isset($tab_post) && $tab_post) || isset($tab_category) && $tab_category}
    <div class="ets-form-group{if isset($input.tab) && $input.tab} ets-blog-tab-{$input.tab|escape:'html':'UTF-8'}{/if}">            
        {$smarty.block.parent}
        {if isset($input.info) && $input.info}
            <div class="ets_tc_info alert alert-warning">{$input.info|escape:'html':'UTF-8'}</div>
        {/if}
    </div>
    {else}
        {$smarty.block.parent}
    {/if}
    {if $input.name=='YBC_BLOG_CUSTOMER_EMAIL_APPROVED_POST'}
        <div class="alert alert-info">
            {l s='You can edit or translate all email templates using a text editor such as Notepad, PhpStorm, etc. All email template files are located in this folder: ' mod='ets_blog'} <span class="ets_folder"><strong>modules/ets_blog/mails/</strong></span>
        </div>
    {/if}
    {if $input.name=='datetime_active'}
        {if isset($form_author_post)}
            <div class="ets_form_author_post">
            {if $form_author_post}
                {$form_author_post nofilter}
            {/if}
            </div>
        {/if}
        {if isset($check_suspend) && $check_suspend}
            <div class="ets-form-group ets-blog-tab-basic">
                <div class="alert alert-warning">
                    {l s='This post is hidden on the front office because its author is suspended' mod='ets_blog'}
                </div>
            </div>
        {/if}
    {/if}
    {if $input.name=="YBC_BLOG_LATEST_POST_NUMBER_HOME" || $input.name=='YBC_BLOG_POPULAR_POST_NUMBER_HOME' || $input.name=='YBC_BLOG_FEATURED_POST_NUMBER_HOME' || $input.name=='YBC_BLOG_CATEGORY_POST_NUMBER_HOME' || $input.name=='YBC_BLOG_GALLERY_POST_NUMBER_HOME' || $input.name=='YBC_BLOG_LATES_POST_NUMBER' || $input.name=='YBC_BLOG_PUPULAR_POST_NUMBER' || $input.name=='YBC_BLOG_FEATURED_POST_NUMBER' || $input.name=='YBC_BLOG_GALLERY_POST_NUMBER' || $input.name=='YBC_BLOG_EXPAND_ARCHIVES_BLOCK' || $input.name=='YBC_BLOG_SHOW_CATEGORIES_BLOCK' || $input.name=='YBC_BLOG_SHOW_SEARCH_BLOCK' || $input.name=='YBC_BLOG_TAGS_NUMBER' || $input.name=='YBC_BLOG_COMMENT_LENGTH' || $input.name=='YBC_BLOG_AUTHOR_NUMBER' || $input.name=='YBC_BLOG_ENABLE_RSS_SIDEBAR' || $input.name=='YBC_BLOG_CONTENT_HTML_BOX'}
            <div class="popup_footer">
                <button class="module_form_submit_btn_sidebar btn btn-default pull-right" type="button">
                    <i class="process-icon-save"></i>
                    {l s='Save' mod='ets_blog'}
                </button>
            </div>
        </div>
        </div>
        </div>
        </div>
    {/if}
{/block}
{block name="label"}
	{if isset($input.label)}
		<label class="control-label col-lg-3{if ((isset($input.required) && $input.required) || (isset($input.required2) && $input.required2) ) && $input.type != 'radio'} required{/if}">
			{if isset($input.hint)}
			<span class="label-tooltip" data-toggle="tooltip" data-html="true" title="{if is_array($input.hint)}
						{foreach $input.hint as $hint}
							{if is_array($hint)}
								{$hint.text|escape:'html':'UTF-8'}
							{else}
								{$hint|escape:'html':'UTF-8'}
							{/if}
						{/foreach}
					{else}
						{$input.hint|escape:'html':'UTF-8'}
					{/if}">
			{/if}
			{$input.label|escape:'html':'UTF-8'}
			{if isset($input.hint)}
			</span>
			{/if}
		</label>
	{/if}
{/block}
