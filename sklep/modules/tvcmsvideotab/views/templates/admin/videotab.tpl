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
<div class="panel product-tab">
{if $editfrommodule == 1}
    <script src="{$url|escape:'htmlall':'UTF-8'}modules/tvcmsvideotab/views/js/showhidetab.js" type="text/javascript" charset="utf-8" async defer></script>
{/if}
<script src="{$url|escape:'htmlall':'UTF-8'}modules/tvcmsvideotab/views/js/videotab.js" type="text/javascript" charset="utf-8" async defer></script>
<script type="text/javascript">
    var url = "{$url|escape:'htmlall':'UTF-8'}";
    var url2 = "{$url2}";{*Escape is unnecessary*}
    var id_product = "{$id_product|escape:'htmlall':'UTF-8'}";
	var update_successful = "{$update_successful|escape:'htmlall':'UTF-8'}";
	var are_you_sure = "{$are_you_sure|escape:'htmlall':'UTF-8'}";
	var video_removed = "{$video_removed|escape:'htmlall':'UTF-8'}";
</script>

<input value="{$id_product|escape:'htmlall':'UTF-8'}" class="hidden hiddeninput" id="product" name="id_product" />
<div class="alert alert-warning alert_video" style="display: none;" >
    <button type="button" class="close" data-dismiss="alert">×</button>
            {l s='There is 1' mod='tvcmsvideotab'} <warning class=""></warning>
        <ul  id="seeMore">
            <li>{l s='You must save this product before adding specific videos' mod='tvcmsvideotab'}</li>
        </ul>
</div>
<div class="alert alert-danger alert_embed" style="display: none;">
    <button type="button" class="close" data-dismiss="alert">×</button>
    <br>
    <ol>
        <li>{l s='You can not leave the Embed field blank ' mod='tvcmsvideotab'}</li>
    </ol>
</div>
<div class="alert alert-danger alert_primary" style="display: none;">
    <button type="button" class="close" data-dismiss="alert">×</button>
    <br>
    <ol>
        <li>{l s='You must upload at least a file for default language' mod='tvcmsvideotab'}</li>
    </ol>
</div>
<div class="alert alert-danger alert_size" style="display: none;">
    <button type="button" class="close" data-dismiss="alert">×</button>
    <br>
    <ol>
        <li>{l s='Upload files are too large' mod='tvcmsvideotab'}</li>
    </ol>
</div>
<div class="alert alert-danger alert_select" style="display: none;">
    <button type="button" class="close" data-dismiss="alert">×</button>
    <br>
    <ol>
        <li>{l s='You need to select the file' mod='tvcmsvideotab'}</li>
    </ol>
</div>
<div class="alert alert-danger alert_type" style="display: none;">
    <button type="button" class="close" data-dismiss="alert">×</button>
    <br>
    <ol>
        <li>{l s='File is invalid' mod='tvcmsvideotab'}</li>
    </ol>
</div>
<div class="form-group {if $ver == 1}col-md-12{/if}">
    <h3 style="margin-top: 14px;">{l s='Video Tab' mod='tvcmsvideotab'}</h3> 

    <div class="form-group" style="width: 99%;border-bottom: 1px solid #d8c8c8;height: 100px;line-height: 20px;margin-left: 5px;">
        <label class="control-label col-md-3 text-right text17" for="simple_product">
            {l s='Type :' mod='tvcmsvideotab'}
        </label>
        <div class="col-md-6">
            <p class="radio col-md-3">
                <label id="embed" onclick="embed()">
                    <input type="radio"  name="type_video"  value="0" {if empty($total)} checked {else} checked {/if} {if empty($embed)} {else} checked{/if}>
                    {l s='Embed' mod='tvcmsvideotab'}
                </label>
        
            </p>
            <p class="radio col-md-3">
                <label id="upload" onclick="upload()">
                    <input type="radio"  name="type_video" value="1" {if empty($file)} {else} checked {/if}>
                    {l s='Upload Video' mod='tvcmsvideotab'}
                </label>
            </p>
        </div>
    </div>
    <div class="form-group" id="url_video" style=" {if empty($total)} display: block; {else}  {if empty($embed)} display: none; {else} display: block; checked {/if}{/if}">
        <div class="col-lg-1"><span class="pull-right">
        </span></div>
        <label class="control-label col-md-2 required text-right text17">
            <span class="label-tooltip" data-toggle="tooltip" title="">
                {l s='Embed Code:' mod='tvcmsvideotab'}
            </span>
        </label>
        {foreach $languages key =key_lang item=value}
        <div class="translatable-field1 lang1-{$value['id_lang']|escape:'htmlall':'UTF-8'}" style="{if $value['checked']==1}display: block;{/if}{if $value['checked']!=1}display: none;{/if}">
                <div class="col-lg-5" style="margin-bottom: 10px;">
                {if empty($embed)}
                <textarea name="name_url[{$value['id_lang']|escape:'htmlall':'UTF-8'}]" {if $ver == 1}style="width: -webkit-fill-available;"{/if}  id="note" cols="30" rows="10" onkeyup="autosize()"></textarea>
                {/if}
                {if !empty($embed)}
                    <textarea name="name_url[{$value['id_lang']|escape:'htmlall':'UTF-8'}]" {if $ver == 1}style="width: -webkit-fill-available;"{/if}  id="note" cols="30" rows="10" onkeyup="autosize()" >{foreach $embed key =key1 item=value1}{if $value1['id_lang'] == $value['id_lang']}{$value1['text_url']|escape:'htmlall':'UTF-8'}{/if}{/foreach}</textarea>
                {/if}
                    <span class="erro_url" style="color:red;font-size: 15px;"></span>    
                </div>
             <div class="col-lg-2">
              <button type="button" class="btn {if $ver == 1}btn-primary {else}btn-default{/if} dropdown-toggle" data-toggle="dropdown">
               {$value['iso_code']|escape:'htmlall':'UTF-8'}
               <span class="caret"></span>
              </button>
              <ul class="dropdown-menu">
              {foreach $languages key =key_lang item=value}
              <li>
              <a href="javascript:hideOtherLanguage1({$value['id_lang']|escape:'htmlall':'UTF-8'});">{$value['name']|escape:'htmlall':'UTF-8'}
              </a></li>
              {/foreach}
              </ul>
             </div>
        </div>
        {/foreach}
    </div>

    <div class="form-group" id="uploadvideo" style="{if empty($file)} display: none; {else} display: block; {/if}">
        <div class="col-lg-1"><span class="pull-right">
        </span></div>
        <label class="control-label col-lg-2 required text-right text17">
            <span class="label-tooltip" data-toggle="tooltip" title="">
                {l s='Video files :' mod='tvcmsvideotab'}
            </span>
        </label>
        {foreach $languages key =key_lang item=value}
        <div class="translatable-field2 lang2-{$value['id_lang']|escape:'htmlall':'UTF-8'}" style="{if $value['checked']==1}display: block;{/if}{if $value['checked']!=1}display: none;{/if}">
            <div class="col-lg-4">

                <button  class="btn {if $ver == 1}btn-primary {else}btn-default{/if}" data-style="expand-right" data-size="s" type="button" id="text_file" onclick="textfile({$value['id_lang']|escape:'htmlall':'UTF-8'})" ><span class="ladda-label">
                    <i class="icon-plus-sign"></i> {l s='Add file' mod='tvcmsvideotab'}    
                </span><span class="ladda-spinner"></span></button>
                <span class="test{$value['id_lang']|escape:'htmlall':'UTF-8'}" name="name_tam">{if !empty($file)|escape:'htmlall':'UTF-8'}
                {foreach $file key =key1 item =value1}
                    {if $value1['id_lang'] == $value['id_lang']}
                        {if $value1['type']|escape:'htmlall':'UTF-8'== 1}
                            {$value1['text_url']|escape:'htmlall':'UTF-8'}
                        {/if}
                    {/if}
                {/foreach}
            {/if}</span>
            <p style="font-style: italic;color: #959595;">{$videotype|escape:'htmlall':'UTF-8'}</p>
                <input id="fileToUpload{$value['id_lang']|escape:'htmlall':'UTF-8'}" type="file" name="fileToUpload[{$value['id_lang']|escape:'htmlall':'UTF-8'}]" class="hidden andi">
                <input type="text" id="text_file{$value['id_lang']|escape:'htmlall':'UTF-8'}" class="form-control hidden andi" name="name_file[{$value['id_lang']}|escape:'htmlall':'UTF-8']" value="">
            </div>
            <div class="col-lg-3">
                {if !empty($file)|escape:'htmlall':'UTF-8'}
                    {foreach $file key =key1 item =value1}
                        {if $value1['id_lang'] == $value['id_lang']}
                            {if $value1['type']|escape:'htmlall':'UTF-8'== 1}
                                {if $ver == 1}
                                    <a href="javascript:void(0);" onclick="ConfirmDelete({$value1['id_product']|escape:'htmlall':'UTF-8'},{$value1['type']|escape:'htmlall':'UTF-8'},{$value1['id_lang']|escape:'htmlall':'UTF-8'})" data-toggle="tooltip" class="btn btn-invisible delete" data-original-title="Video delete this product.">
                                      <i class="material-icons">{l s='delete' mod='tvcmsvideotab'}</i>
                                    </a>
                                    {else}
                                    <a href="javascript:void(0);" onclick="ConfirmDelete({$value1['id_product']|escape:'htmlall':'UTF-8'},{$value1['type']|escape:'htmlall':'UTF-8'},{$value1['id_lang']|escape:'htmlall':'UTF-8'})" title="Delete" class="delete" style="margin-left: 5px;">
                                    <i class="icon-trash "></i></a>
                                {/if}
                            {/if}
                        {/if}
                    {/foreach}
                {/if}
              <button type="button" class="btn {if $ver == 1}btn-primary {else}btn-default{/if} dropdown-toggle" data-toggle="dropdown" style="margin-left: 5px;">
               {$value['iso_code']|escape:'htmlall':'UTF-8'}
               <span class="caret"></span>
              </button>
              <ul class="dropdown-menu">
                {foreach $languages key =key_lang item=value}
                    <li>
                    <a href="javascript:hideOtherLanguage2({$value['id_lang']|escape:'htmlall':'UTF-8'});">{$value['name']|escape:'htmlall':'UTF-8'}
                    </a></li>
                {/foreach}
              </ul>
             </div>
        </div>
         {/foreach}
    </div>
    </div>
    <div class="panel-footer form-group col-md-12">
        {if $editfrommodule == 1}
            <button type="button" class="btn btn-primary pull-right" onclick="autoreload()">{l s='Cancel' mod='tvcmsvideotab'}</button>
            <button type="button" name="save" class="btn {if $ver == 1}btn-primary {else}btn-default{/if} pull-right" id="save_edit" onclick="savevideoedit()"> <i class="process-icon-save"></i> {l s='Save' mod='tvcmsvideotab'} </button>
        {else}
            <button type="button" class="btn btn-primary pull-right" onclick="autoreload()">{l s='Cancel' mod='tvcmsvideotab'}</button>
            <button type="button" name="save" class="btn {if $ver == 1}btn-primary {else}btn-default{/if}  pull-right" id="save" onclick="savevideo()"> <i class="process-icon-save"></i> {l s='Save' mod='tvcmsvideotab'}</button>    
        {/if}
    </div>
</div>