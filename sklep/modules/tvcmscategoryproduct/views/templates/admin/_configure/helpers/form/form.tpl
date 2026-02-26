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

{extends file="helpers/form/form.tpl"}

{block name="input"}
    {if $input.type == 'image_file'}
        <div class="col-lg-9">
            {foreach from=$languages item=language}
                {if $languages|count > 1}
                    <div class="translatable-field lang-{$language.id_lang|escape:'htmlall':'UTF-8'}" {if $language.id_lang != $defaultFormLanguage}style="display:none"{/if}>
                {/if}
                <div class="form-group">
                    <div class="col-lg-9">
                        <div class="dummyfile input-group">
                            <input id="{$input.name|escape:'htmlall':'UTF-8'}_{$language.id_lang|escape:'htmlall':'UTF-8'}" type="file" name="{$input.name|escape:'htmlall':'UTF-8'}_{$language.id_lang|escape:'htmlall':'UTF-8'}" class="hide-file-upload" />
                            <span class="input-group-addon"><i class="icon-file"></i></span>
                            <input id="{$input.name|escape:'htmlall':'UTF-8'}_{$language.id_lang|escape:'htmlall':'UTF-8'}-name" type="text" class="disabled" name="filename" readonly />
                            <span class="input-group-btn">
                                <button id="{$input.name|escape:'htmlall':'UTF-8'}_{$language.id_lang|escape:'htmlall':'UTF-8'}-selectbutton" type="button" name="submitAddAttachments" class="btn btn-default">
                                    <i class="icon-folder-open"></i> {l s='Choose a file' mod='tvcmscategoryproduct'}
                                </button>
                            </span>
                        </div>
                    </div>
                    {if $languages|count > 1}
                        <div class="col-lg-3">
                            <button type="button" class="btn btn-default dropdown-toggle" tabindex="-1" data-toggle="dropdown">
                                {$language.iso_code|escape:'htmlall':'UTF-8'}
                                <span class="caret"></span>
                            </button>
                            <ul class="dropdown-menu">
                                {foreach from=$languages item=lang}
                                <li><a href="javascript:hideOtherLanguage({$lang.id_lang|escape:'htmlall':'UTF-8'});" tabindex="-1">{$lang.name}</a></li>
                                {/foreach}
                            </ul>
                        </div>
                    {/if}
                </div>

                {assign var='width' value='978'}
                {assign var='height' value='300'}

                <div class="form-group">
                    <div id="{$input.name|escape:'htmlall':'UTF-8'}-{$language.id_lang|escape:'htmlall':'UTF-8'|escape:'htmlall':'UTF-8'}-images-thumbnails" class="col-lg-12">
                        <img src="{$path|escape:'htmlall':'UTF-8'}{$fields_value[$input.name][$language.id_lang]|escape:'htmlall':'UTF-8'}" class="img-thumbnail" width='50%'/>
                        <p class="help-block">{l s='Please Select Image.' mod='tvcmscategoryproduct'} (Size:- {$width|escape:'htmlall':'UTF-8'} X {$height|escape:'htmlall':'UTF-8'})</p>
                    </div>
                </div>
                    

                {if $languages|count > 1}
                    </div>
                {/if}
                <script>
                $(document).ready(function(){
                    $('#{$input.name|escape:"htmlall":"UTF-8"}_{$language.id_lang|escape:"htmlall":"UTF-8"}-selectbutton').click(function(e){
                        $('#{$input.name|escape:"htmlall":"UTF-8"}_{$language.id_lang|escape:"htmlall":"UTF-8"}').trigger('click');
                    });

                    $('#{$input.name|escape:"htmlall":"UTF-8"}_{$language.id_lang|escape:"htmlall":"UTF-8"}-name').click(function(e){
                        $('#{$input.name|escape:"htmlall":"UTF-8"}_{$language.id_lang|escape:"htmlall":"UTF-8"}').trigger('click');
                    });
                    
                    $('#{$input.name|escape:"htmlall":"UTF-8"}_{$language.id_lang|escape:"htmlall":"UTF-8"}').change(function(e){
                        var val = $(this).val();
                        var file = val.split(/[\\/]/);
                        $('#{$input.name|escape:"htmlall":"UTF-8"}_{$language.id_lang|escape:"htmlall":"UTF-8"}-name').val(file[file.length-1]);
                    });
                });
            </script>
            {/foreach}
        </div>
    {/if}
    
    {if $input.type == 'tvcmscategory_select'}
        <div class="col-lg-9">
            <div class="form-group">
                <div class="col-lg-9">
                    <div class="dummyfile input-group">
                        <select class='tvcmscategory-slider-name' name="{$input.name|escape:'htmlall':'UTF-8'}">
                            {if $all_category}
                                <option value="0">{l s='Select Category' mod='tvcmscategoryproduct'}</option>
                                {foreach $all_category as $category}
                                    <option value="{$category.id_category|escape:'htmlall':'UTF-8'}" {if $id_category_select == $category.id_category} selected {/if}> {$category.name|escape:'htmlall':'UTF-8'} </option>
                                {/foreach}
                            {else}
                                <option value="0">{l s='No Category Found' mod='tvcmscategoryproduct'}</option>
                            {/if}
                        </select>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <div id="{$input.name|escape:'htmlall':'UTF-8'}-images-thumbnails" class="col-lg-12">
                    <p class="help-block">{l s='Please select category.' mod='tvcmscategoryproduct'} </p>
                </div>
            </div>
        </div>
    {/if}

    {if $input.type == 'tvcmscategory_img'}
        <div class="col-lg-9">
                <div class="form-group">
                    <div class="col-lg-9">
                        <div class="dummyfile input-group">
                            <input id="{$input.name|escape:'htmlall':'UTF-8'}" type="file" name="{$input.name|escape:'htmlall':'UTF-8'}" class="hide-file-upload" />
                            <span class="input-group-addon"><i class="icon-file"></i></span>
                            <input id="{$input.name|escape:'htmlall':'UTF-8'}-name" type="text" class="disabled" name="filename" readonly />
                            <span class="input-group-btn">
                                <button id="{$input.name|escape:'htmlall':'UTF-8'}-selectbutton" type="button" name="submitAddAttachments" class="btn btn-default">
                                  <i class="icon-folder-open"></i> {l s='Choose a file' mod='tvcmscategoryproduct'}
                                </button>
                            </span>
                        </div>
                    </div>
                </div>

                {assign var='width' value='978'}
                {assign var='height' value='300'}

                <div class="form-group">
                    {if $fields_value[$input.name]}
                    <div id="{$input.name|escape:'htmlall':'UTF-8'}-images-thumbnails" class="col-lg-12">
                        <img src="{$path|escape:'htmlall':'UTF-8'}{$fields_value[$input.name]|escape:'htmlall':'UTF-8'}" class="img-thumbnail" width='50%'/>
                    </div>
                    {/if}
                    <p class="help-block">{l s='Please Select Image.' mod='tvcmscategoryproduct'} (Size:- {$width|escape:'htmlall':'UTF-8'} X {$height|escape:'htmlall':'UTF-8'})</p>
                </div>
                    

               
                <script>
                $(document).ready(function(){
                    $('#{$input.name|escape:"htmlall":"UTF-8"}-selectbutton').click(function(e){
                        $('#{$input.name|escape:"htmlall":"UTF-8"}').trigger('click');
                    });

                    $('#{$input.name|escape:"htmlall":"UTF-8"}-name').click(function(e){
                        $('#{$input.name|escape:"htmlall":"UTF-8"}').trigger('click');
                    });
                    
                    $('#{$input.name|escape:"htmlall":"UTF-8"}').change(function(e){
                        var val = $(this).val();
                        var file = val.split(/[\\/]/);
                        $('#{$input.name|escape:"htmlall":"UTF-8"}-name').val(file[file.length-1]);
                    });
                });
            </script>
        </div>
    {/if}
    {$smarty.block.parent}
{/block}
