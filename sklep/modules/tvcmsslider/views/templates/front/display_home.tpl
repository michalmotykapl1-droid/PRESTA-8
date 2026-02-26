{strip}
    {if !empty($data)}
        <div class="tvcms-slider-offerbanner-wrapper">
            <div class="container-fluid" style="padding: 0;">
                <div class="row no-gutters" style="margin: 0;">
                    
                    <div class="col-12 tvcmsmain-slider-wrapper" data-speed='{$main_slider_js.speed}' data-pause-hover='{$main_slider_js.pause}'>
                         <div class='tvcms-main-slider'>
                            <div class='tv-main-slider'>
                                
                                <div id='tvmain-slider' class="owl-theme owl-carousel tvcms-hide-owl">
                                    {foreach $data as $slide}
                                        <div class="item">
                                            
                                            {* 1. WŁASNY PLIK TPL *}
                                            {if isset($slide.ivr_value) && $slide.ivr_value == 'custom_tpl'}
                                                {if $slide.class_name && $slide.class_name != ''}
                                                     <div class="tv-custom-tpl-wrapper">
                                                         {include file="module:tvcmsslider/views/templates/front/custom_slides/{$slide.class_name}" slide=$slide}
                                                     </div>
                                                {/if}

                                            {* 2. HTML ONLY *}
                                            {elseif isset($slide.ivr_value) && $slide.ivr_value == 'html_only'}
                                                <div class="tv-slide-custom-html tv-no-bg">
                                                    <div class="tv-custom-content-container">
                                                        {$slide.description nofilter}
                                                    </div>
                                                </div>

                                            {* 3. STANDARD (Obrazek/Wideo) *}
                                            {else}
                                                
                                                {* Styl Allegro (Domyślny) *}
                                                {* Jeśli ktoś wybrał opcję 'right', dodajemy klasę reverse *}
                                                {if $slide.class_name == 'tvmain-slider-contant-right'}
                                                    {$layout_class = 'tv-reverse'}
                                                {else}
                                                    {$layout_class = ''}
                                                {/if}

                                                <div class="tv-slide-standard {$layout_class}" style="background-image: url('{$slide.image_url}');">
                                                    
                                                    {* Tekst *}
                                                    <div class="tv-std-content">
                                                        {if isset($slide.legend) && $slide.legend}<span class="tv-std-subtitle">{$slide.legend}</span>{/if}
                                                        {if isset($slide.title) && $slide.title}<h2 class="tv-std-title">{$slide.title}</h2>{/if}
                                                        {if isset($slide.description) && $slide.description}<div class="tv-std-desc">{$slide.description nofilter}</div>{/if}
                                                        {if isset($slide.url) && $slide.url}
                                                            <a href="{$slide.url}" class="tv-btn-std">
                                                                {if isset($slide.btn_caption) && $slide.btn_caption}{$slide.btn_caption}{else}SPRAWDŹ{/if}
                                                            </a>
                                                        {/if}
                                                    </div>

                                                    {* Obrazek / Wideo *}
                                                    <div class="tv-std-image">
                                                        <a href="{$slide.url}">
                                                            {if isset($slide.ivr_value) && $slide.ivr_value == 'video_upload'}
                                                                <video width="{$slide.video_width}" height="{$slide.video_height}" controls>
                                                                    <source src="{$slide.image_url}" type="video/mp4">
                                                                </video>
                                                            {else}
                                                                {* TU BYŁ PROBLEM - Upewniamy się, że IMG jest renderowane *}
                                                                <img class="tv-main-img" src='{$slide["image_url"]}' alt='{$slide.title}' loading="lazy" />
                                                            {/if}
                                                        </a>
                                                    </div>
                                                </div>
                                            {/if}

                                        </div>
                                    {/foreach}
                                </div>

                                <div class="tvmain-slider-next-pre-btn" style="display: none;">
                                    <div class="tvcmsmain-prev tvcmsprev-btn"></div>
                                    <div class="tvcmsmain-next tvcmsnext-btn"></div>
                                </div>

                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    {/if}
{/strip}