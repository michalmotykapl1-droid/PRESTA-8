{**
* 2007-2025 PrestaShop
*}
{strip}
{if $main_heading['main_image_status']}
    {$col = 'col-xl-10 col-lg-10 col-md-10 col-sm-12 col-xs-12 tvimage-true'}
    {$image = true}
    {if $main_heading['main_image_side'] == 'left'}
        {$image_side = 'left'}
    {else}
        {$image_side = 'right'}
    {/if}
{else}
    {$col = ''}
    {$image = ''}
    {$image_side = ''}
{/if}

{if $dis_arr_result.status && $dis_arr_result.home_status && count($dis_arr_result.data.product_list) > 0}
    
    {* --- 1. KONTENER GŁÓWNY (Podniesiony wyżej: margin-top 30px zamiast 60px) --- *}
    <div class='tvcmsnew-product-wrapper-box container bb-new-section' style="margin-top: 0;">
        
        <div class="tvcmsnew-product-all-box">
            
            {* --- NAGŁÓWEK (Mniejszy odstęp od dołu: margin-bottom 20px) --- *}
            <div class='tvtab-main-title-wrapper' style="
                text-align: center; 
                margin-bottom: 30px; 
                position: relative;
                display: flex; 
                flex-direction: column; 
                align-items: center; 
                justify-content: center;">
                
                <h2 style="
                    font-family: inherit;
                    font-size: 24px !important;
                    font-weight: 700 !important;
                    color: #222222 !important;
                    text-transform: uppercase !important;
                    letter-spacing: -0.5px !important;
                    margin: 0 0 10px 0 !important; /* Mniejszy margines pod tekstem */
                    padding: 0 !important;
                    line-height: 1.2 !important;
                    display: block;
                    text-align: center;
                    float: none !important;
                    width: auto !important;">
                    
                    NOWOŚCI W OFERCIE 
                    <i class='material-icons' style="
                        color: #ff5a00 !important; 
                        font-size: 24px !important; 
                        vertical-align: middle !important;
                        margin-left: 6px;
                        margin-bottom: 4px;">star</i>
                </h2>

                {* Pomarańczowa linia *}
                <div style="
                    width: 60px;
                    height: 4px;
                    background-color: #ff5a00; 
                    margin: 0 auto !important;
                    border-radius: 2px;">
                </div>
            </div>

             <div class="tvall-product-offer-banner">
                {if $image == true && $image_side == 'left'}
                <div class="tvall-product-branner tvall-product-branner-left">
                    <div class=" tvall-block-box-shadows">
                         <div class="tvbanner-hover-wrapper">
                            <div class='tvbanner-hover'></div>
                            <img src="{$dis_arr_result.path}{$main_heading.data.image}" alt="Banner" width="{$main_heading.data.width}" height="{$main_heading.data.height}" class="tv-img-responsive" loading="lazy">
                             <div class='tvbanner-hover1'></div>
                        </div>
                    </div>
                </div>
                 {/if}
                
                <div class="tvcmsnew-product-content {$col}">
                     <div class="tvall-block-box-shadows">
                        
                        {* KLASA FIXUJĄCA DLA JS *}
                        <div class="tvcmsnew-product-fix">
                             <div class="products owl-theme owl-carousel tvnew-product-wrapper tvproduct-wrapper-content-box" data-has-image='{if $image == true}true{else}false{/if}'>
                                 {foreach $dis_arr_result.data.product_list as $product}
                                    {include file="catalog/_partials/miniatures/product.tpl" product=$product tv_product_type="new_product" }
                                {/foreach}
                            </div>
                        </div>

                    </div>
                </div>
                
                {if $image == true && $image_side == 'right'}
                 <div class="tvall-product-branner tvall-product-branner-right">
                    <div class=" tvall-block-box-shadows">
                        <div class="tvbanner-hover-wrapper">
                             <div class='tvbanner-hover'></div>
                           <img src="{$dis_arr_result.path}{$main_heading.data.image}" alt="Banner" width="{$main_heading.data.width}" height="{$main_heading.data.height}" class="tv-img-responsive" loading="lazy">
                             <div class='tvbanner-hover1'></div>
                         </div>
                     </div>
                </div>
                {/if}
            </div>
            
            {* STRZAŁKI NAWIGACJI *}
            <div class='tvtab-pagination-wrapper tv-pagination-wrapper' style="position: absolute; top: -10px; right: 0;">
                 <div class="tvcmsnew-pagination">
                     <div class="tvcmsnew-pagination-wrapper">
                        <div class="tvcmsnew-next-pre-btn tvcms-next-pre-btn" style="display:flex; gap:10px;">
                            <div class="tvcmsnew-prev tvcmsprev-btn" data-parent="tvcmsnew-product-fix" style="width:36px; height:36px; background:#fff; display:flex; align-items:center; justify-content:center; cursor:pointer; box-shadow:0 2px 8px rgba(0,0,0,0.1);">
                                <i class='material-icons' style="font-size:18px; color:#333;">&#xe314;</i>
                            </div>
                             <div class="tvcmsnew-next tvcmsnext-btn" data-parent="tvcmsnew-product-fix" style="width:36px; height:36px; background:#fff; display:flex; align-items:center; justify-content:center; cursor:pointer; box-shadow:0 2px 8px rgba(0,0,0,0.1);">
                                <i class='material-icons' style="font-size:18px; color:#333;">&#xe315;</i>
                             </div>
                         </div>
                     </div>
                </div>
             </div>

        </div>
    </div>
{/if}
{/strip}