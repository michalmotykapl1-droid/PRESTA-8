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
{extends file="helpers/form/form.tpl"}

{block name="input"}
{if $input.type == 'BtnInstallData'}
            <input type="hidden" name="tvinstalldata" id="tvinstalldata" value="0" \>
            <button class="btn btn-primary tvcms-sample-install-btn" type="submit" onclick="return confirmAction()" name="submitTvcmsSampleinstall">{l s='Install Sample Data' mod="tvcmsmultibanner1"}</button>
    {/if}
    {assign var="i" value='1'}
    {while $i <= $total_image}
        {assign var="tmp" value='file_upload_'|cat:$i}
        {if $input.type == $tmp}
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
                                    <i class="icon-folder-open"></i> {l s='Choose a file' mod='tvcmsmultibanner1'}
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

                {if $i==1}
                    {assign var='width' value='401'}
                    {assign var='height' value='232'}
                {elseif $i==2}
                    {assign var='width' value='401'}
                    {assign var='height' value='232'}
                {elseif $i==3 || $i==4}
                    {assign var='width' value='401'}
                    {assign var='height' value='232'}
                {/if}

                <div class="form-group">
                    {if isset($fields_value[$input.name][$language.id_lang]) && $fields_value[$input.name][$language.id_lang] != ''}
                    <div id="{$input.name|escape:'htmlall':'UTF-8'}-{$language.id_lang|escape:'htmlall':'UTF-8'|escape:'htmlall':'UTF-8'}-images-thumbnails" class="col-lg-12">
                        <img src="{$path|escape:'htmlall':'UTF-8'}{$fields_value[$input.name][$language.id_lang]|escape:'htmlall':'UTF-8'}" class="img-thumbnail" width='40%' />
                    </div>
                    {/if}
                    {if $width}
                        <p class="help-block">Please select image. (Size:- {$width|escape:'htmlall':'UTF-8'} X {$height|escape:'htmlall':'UTF-8'} )</p>
                    {/if}
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
        {assign var="i" value=$i+1}
    {/while}

    {if $input.type == 'multiple_select'}
        <div class="col-lg-9">
            {foreach from=$languages item=language}
                {if $languages|count > 1}
                    <div class="translatable-field lang-{$language.id_lang|escape:'htmlall':'UTF-8'}" {if $language.id_lang != $defaultFormLanguage}style="display:none"{/if}>
                {/if}
                <div class="form-group">
                    <div class="col-lg-3">
                            {* <input id="{$input.name|escape:'htmlall':'UTF-8'}_{$language.id_lang|escape:'htmlall':'UTF-8'}" type="file" name="{$input.name|escape:'htmlall':'UTF-8'}_{$language.id_lang|escape:'htmlall':'UTF-8'}" class="hide-file-upload" />
                            <span class="input-group-addon"><i class="icon-file"></i></span>
                            <input id="{$input.name|escape:'htmlall':'UTF-8'}_{$language.id_lang|escape:'htmlall':'UTF-8'}-name" type="text" class="disabled" name="filename" readonly />
                            <span class="input-group-btn">
                                <button id="{$input.name|escape:'htmlall':'UTF-8'}_{$language.id_lang|escape:'htmlall':'UTF-8'}-selectbutton" type="button" name="submitAddAttachments" class="btn btn-default">
                                   <i class="icon-folder-open"></i> {l s='Choose a file' mod='tvcmsmultibanner1'}
                                </button>
                            </span> *}

                        {if isset($fields_value[$input.name][$language.id_lang]) && !empty($fields_value[$input.name][$language.id_lang])}
                            {$value = $fields_value[$input.name][$language.id_lang]}
                        {else}
                            {$value = 'left'}
                        {/if}

                        <select name="{$input.name|escape:'htmlall':'UTF-8'}_{$language.id_lang|escape:'htmlall':'UTF-8'}">
                            <option value='left' {if $value == 'left'}selected="selected"{/if}>{l s='Left' mod='tvcmsmultibanner1'}</option>
                            <option value='top-left' {if $value == 'top-left'}selected="selected"{/if}>{l s='Top Left' mod='tvcmsmultibanner1'}</option>
                            <option value='bottom-left' {if $value == 'bottom-left'}selected="selected"{/if}>{l s='Bottom Left' mod='tvcmsmultibanner1'}</option>
                            <option value='center' {if $value == 'center'}selected="selected"{/if}>{l s='Center' mod='tvcmsmultibanner1'}</option>
                            <option value='top-center' {if $value == 'top-center'}selected="selected"{/if}>{l s='Top Center' mod='tvcmsmultibanner1'}</option>
                            <option value='bottom-center' {if $value == 'bottom-center'}selected="selected"{/if}>{l s='Bottom Center' mod='tvcmsmultibanner1'}</option>
                            <option value='right' {if $value == 'right'}selected="selected"{/if}>{l s='Right' mod='tvcmsmultibanner1'}</option>
                            <option value='top-right' {if $value == 'top-right'}selected="selected"{/if}>{l s='Top Right' mod='tvcmsmultibanner1'}</option>
                            <option value='bottom-right' {if $value == 'bottom-right'}selected="selected"{/if}>{l s='Bottom Right' mod='tvcmsmultibanner1'}</option>
                            <option value='none' {if $value == 'none'}selected="selected"{/if}>{l s='None' mod='tvcmsmultibanner1'}</option>
                        </select>
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

                <div class="form-group">
                    <div id="{$input.name|escape:'htmlall':'UTF-8'}-{$language.id_lang|escape:'htmlall':'UTF-8'|escape:'htmlall':'UTF-8'}-images-thumbnails" class="col-lg-12">
                        <p class="help-block">{l s='Select Caption Side' mod='tvcmsmultibanner1'}</p>
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
    {$smarty.block.parent}
{/block}

{/strip}