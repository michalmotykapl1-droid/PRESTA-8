{strip}
{extends file="helpers/form/form.tpl"}
{block name="field"}
    {if $input.type == 'custom_radio_btn'}
        <div class="col-lg-8">
            {foreach from=$languages item=language}
                {if $languages|count > 1}
                    <div class="translatable-field lang-{$language.id_lang|escape:'htmlall':'UTF-8'}" {if $language.id_lang != $defaultFormLanguage}style="display:none"{/if}>
                {/if}
                    <div class="col-lg-12">
                        <label class="control-label" style="text-align: left; margin-bottom: 10px; font-weight: bold;">1. Wybierz Rodzaj Mediów:</label>
                        <div class="dummyfile input-group tvmain-slider-flex" style="display:flex; gap:20px; align-items:center; flex-wrap: wrap;">
                            
                            <label style="cursor:pointer; display:flex; align-items:center; gap:5px;">
                                <input type="radio" class="tv-type-selector" data-lang="{$language.id_lang}" name="{$input.name|escape:'htmlall':'UTF-8'}_{$language.id_lang|escape:'htmlall':'UTF-8'}" value="image_upload" {if $fields_value['ivr_value'][$language.id_lang] == 'image_upload'} checked {else}checked{/if}/> 
                                <span>Obrazek (Image)</span>
                            </label>

                            <label style="cursor:pointer; display:flex; align-items:center; gap:5px;">
                                <input type="radio" class="tv-type-selector" data-lang="{$language.id_lang}" name="{$input.name|escape:'htmlall':'UTF-8'}_{$language.id_lang|escape:'htmlall':'UTF-8'}" value="video_upload" {if $fields_value['ivr_value'][$language.id_lang] == 'video_upload'} checked {/if}/> 
                                <span>Wideo (MP4)</span>
                            </label>
                            
                            {* NOWA OPCJA: HTML ONLY *}
                            <label style="cursor:pointer; display:flex; align-items:center; gap:5px; font-weight:bold; color:#d32f2f; border: 1px dashed #d32f2f; padding: 5px 10px; border-radius: 4px;">
                                <input type="radio" class="tv-type-selector" data-lang="{$language.id_lang}" name="{$input.name|escape:'htmlall':'UTF-8'}_{$language.id_lang|escape:'htmlall':'UTF-8'}" value="html_only" {if $fields_value['ivr_value'][$language.id_lang] == 'html_only'} checked {/if}/> 
                                <span>BRAK OBRAZKA (Tylko Kod HTML)</span>
                            </label>

                        </div>
                    </div>
                {if $languages|count > 1}
                    <div class="col-lg-2">
                        <button type="button" class="btn btn-default dropdown-toggle" tabindex="-1" data-toggle="dropdown">
                            {$language.iso_code|escape:'htmlall':'UTF-8'}
                            <span class="caret"></span>
                        </button>
                        <ul class="dropdown-menu">
                            {foreach from=$languages item=lang}
                            <li><a href="javascript:hideOtherLanguage({$lang.id_lang|escape:'htmlall':'UTF-8'});" tabindex="-1">{$lang.name|escape:'htmlall':'UTF-8'}</a></li>
                            {/foreach}
                        </ul>
                    </div>
                {/if}
                {if $languages|count > 1}
                    </div>
                {/if}
            {/foreach}          
        </div>
    {/if}


    {if $input.type == 'file_lang'}
        <div class="col-lg-8">
             {foreach from=$languages item=language}
                {if $languages|count > 1}
                    <div class="translatable-field lang-{$language.id_lang|escape:'htmlall':'UTF-8'}" {if $language.id_lang != $defaultFormLanguage}style="display:none"{/if}>
                {/if}
                    {* Kontener uploadu - dodajemy ID i klasę do ukrywania *}
                    <div class="col-lg-6 tv-upload-wrapper" id="tv-upload-wrapper-{$language.id_lang}">
                         {if $fields_value['ivr_value'][$language.id_lang] == 'image_upload'}
                            <img src="{$image_baseurl|escape:'htmlall':'UTF-8'}{$fields[0]['form']['images'][$language.id_lang]|escape:'htmlall':'UTF-8'}" class="img-thumbnail" />
                        {elseif $fields_value['ivr_value'][$language.id_lang] == 'video_upload'}
                            <video width="490" height="280" controls="controls" autoplay>
                              <source src="{$image_baseurl|escape:'htmlall':'UTF-8'}{$fields[0]['form']['images'][$language.id_lang]|escape:'htmlall':'UTF-8'}" type="video/mp4">
                            </video>
                         {/if}
                            
                        <div class="dummyfile input-group">
                            <input id="{$input.name|escape:'htmlall':'UTF-8'}_{$language.id_lang|escape:'htmlall':'UTF-8'}" type="file" name="{$input.name|escape:'htmlall':'UTF-8'}_{$language.id_lang|escape:'htmlall':'UTF-8'}" class="hide-file-upload" />
                            <span class="input-group-addon"><i class="icon-file"></i></span>
                            <input id="{$input.name|escape:'htmlall':'UTF-8'}_{$language.id_lang|escape:'htmlall':'UTF-8'}-name" type="text" class="disabled" name="filename" readonly />
                            <span class="input-group-btn">
                                <button id="{$input.name|escape:'htmlall':'UTF-8'}_{$language.id_lang|escape:'htmlall':'UTF-8'}-selectbutton" type="button" name="submitAddAttachments" class="btn btn-default">
                                    <i class="icon-folder-open"></i> {l s='Choose a file' mod='tvcmsslider'}
                                </button>
                            </span>
                        </div>
                        <p class="help-block">Zalecany rozmiar: <b>1920 x 600 px</b> (PNG/JPG)</p>
                     </div>
                {if $languages|count > 1}
                    <div class="col-lg-2">
                        <button type="button" class="btn btn-default dropdown-toggle" tabindex="-1" data-toggle="dropdown">
                            {$language.iso_code|escape:'htmlall':'UTF-8'}
                            <span class="caret"></span>
                        </button>
                        <ul class="dropdown-menu">
                             {foreach from=$languages item=lang}
                            <li><a href="javascript:hideOtherLanguage({$lang.id_lang|escape:'htmlall':'UTF-8'});" tabindex="-1">{$lang.name|escape:'htmlall':'UTF-8'}</a></li>
                            {/foreach}
                        </ul>
                    </div>
                {/if}
                {if $languages|count > 1}
                    </div>
                {/if}
                <script>
                $(document).ready(function(){
                    $('#{$input.name|escape:"htmlall":"UTF-8"}_{$language.id_lang|escape:"htmlall":"UTF-8"}-selectbutton').click(function(e){
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

    {if $input.type == 'radio_btn'}
        <div class="col-lg-8">
            {foreach from=$languages item=language}
                {if $languages|count > 1}
                    <div class="translatable-field lang-{$language.id_lang|escape:'htmlall':'UTF-8'}" {if $language.id_lang != $defaultFormLanguage}style="display:none"{/if}>
                {/if}
                    {if $fields_value[$input.name][$language.id_lang]}
                        {$name = $fields_value[$input.name][$language.id_lang]}
                    {else}
                        {$name = 'tvmain-slider-contant-none'}
                    {/if}
                    
                    <div class="col-lg-12">
                        <label class="control-label" style="text-align: left; margin-bottom: 10px; font-weight: bold; font-size: 14px;">2. Wybierz Układ Treści (Layout):</label>
                        <div class="dummyfile input-group tvmain-slider-flex" style="display: flex; flex-direction: column; gap: 15px; align-items: flex-start;">
                            
                            {* OPCJA 1: STANDARD *}
                            <div style="border: 1px solid #cce5ff; padding: 15px; border-radius: 5px; background: #e8f4ff; width: 100%;">
                                <label style="font-weight:bold; color:#0056b3; font-size: 14px; cursor: pointer; display: flex; align-items: center;">
                                    <input type="radio" name="{$input.name|escape:'htmlall':'UTF-8'}_{$language.id_lang|escape:'htmlall':'UTF-8'}" value="tvmain-slider-contant-left" {if $name == 'tvmain-slider-contant-left'} checked {/if} style="margin-right: 10px;"/> 
                                    TRYB STANDARD (Styl Allegro)
                                </label>
                                <p style="margin:5px 0 0 25px; font-size:12px; color:#555;">
                                    Wymaga obrazka.<br>
                                    Układ: <b>Tekst po lewej + Obrazek po prawej</b>.
                                </p>
                            </div>

                            {* OPCJA 2: ODWRÓCONY *}
                            <div style="border: 1px solid #d6d8db; padding: 15px; border-radius: 5px; background: #f8f9fa; width: 100%;">
                                <label style="font-weight:bold; color:#383d41; font-size: 14px; cursor: pointer; display: flex; align-items: center;">
                                    <input type="radio" name="{$input.name|escape:'htmlall':'UTF-8'}_{$language.id_lang|escape:'htmlall':'UTF-8'}" value='tvmain-slider-contant-right' {if $name == 'tvmain-slider-contant-right'} checked {/if} style="margin-right: 10px;"/> 
                                    TRYB ODWRÓCONY
                                </label>
                                <p style="margin:5px 0 0 25px; font-size:12px; color:#555;">
                                    Wymaga obrazka.<br>
                                    Układ: <b>Obrazek po lewej + Tekst po prawej</b>.
                                </p>
                            </div>

                            {* OPCJA 3: CUSTOM HTML *}
                            <div style="border: 2px solid #ffc107; padding: 15px; border-radius: 5px; background: #fff3cd; width: 100%;">
                                <label style="font-weight:bold; color:#856404; font-size: 14px; cursor: pointer; display: flex; align-items: center;">
                                    <input type="radio" name="{$input.name|escape:'htmlall':'UTF-8'}_{$language.id_lang|escape:'htmlall':'UTF-8'}" value="tvmain-slider-contant-center" {if $name == 'tvmain-slider-contant-center'} checked {/if} style="margin-right: 10px;"/> 
                                    ★ TRYB "PEŁNY HTML" (Dla Zaawansowanych)
                                </label>
                                <p style="margin:5px 0 0 25px; font-size:12px; color:#856404;">
                                    <b>Działa z obrazkiem i bez.</b><br>
                                    W polu <b>OPIS (Description)</b> wklejasz swój własny kod HTML.<br>
                                    Moduł wyświetli Twój kod na 100% szerokości.
                                </p>
                            </div>

                        </div>
                    </div>
            
                {if $languages|count > 1}
                    <div class="col-lg-2">
                        <button type="button" class="btn btn-default dropdown-toggle" tabindex="-1" data-toggle="dropdown">
                            {$language.iso_code|escape:'htmlall':'UTF-8'}
                            <span class="caret"></span>
                        </button>
                        <ul class="dropdown-menu">
                            {foreach from=$languages item=lang}
                                <li><a href="javascript:hideOtherLanguage({$lang.id_lang|escape:'htmlall':'UTF-8'});" tabindex="-1">{$lang.name|escape:'htmlall':'UTF-8'}</a></li>
                            {/foreach}
                        </ul>
                    </div>
                {/if}
            
                {if $languages|count > 1}
                    </div>
                {/if}
            {/foreach}          
        </div>

        {* SCRIPT: Ukrywanie pola uploadu przy wyborze BRAK OBRAZKA *}
        <script>
        $(document).ready(function(){
            function toggleUploadField(langId) {
                var selectedType = $('input[name="ivr_value_'+langId+'"]:checked').val();
                if(selectedType === 'html_only') {
                    $('#tv-upload-wrapper-'+langId).slideUp();
                } else {
                    $('#tv-upload-wrapper-'+langId).slideDown();
                }
            }

            $('.tv-type-selector').change(function(){
                var langId = $(this).data('lang');
                toggleUploadField(langId);
            });

            // Init na starcie (dla wszystkich języków)
            $('.tv-type-selector:checked').each(function(){
                var langId = $(this).data('lang');
                toggleUploadField(langId);
            });
        });
        </script>

    {/if}

    {$smarty.block.parent}
{/block}
{/strip}