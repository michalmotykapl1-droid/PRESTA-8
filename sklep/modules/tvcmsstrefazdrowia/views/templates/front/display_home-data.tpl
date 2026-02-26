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
    
    {* 1. KONTENER FLUID (PEŁNA SZEROKOŚĆ) *}
    <div class='tvcmsnew-product-wrapper-box container-fluid bb-new-section'>
        
        <div class="tvcmsnew-product-all-box">
            
            <div class='tvtab-main-title-wrapper'>
                <div class="tv-allegro-title">
                    <h2>
                        NOWOŚCI W OFERCIE 
                        <i class='material-icons' style="color: #ff5a00; font-size: 28px; display: inline-block; vertical-align: middle; transform: translateY(-4px); margin-left: 6px;">star</i>
                    </h2>
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
                        
                        {* 2. NOWA NAZWA KLASY - to klucz do sukcesu *}
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
            
            {* 3. PAGINACJA *}
            <div class='tvtab-pagination-wrapper tv-pagination-wrapper'>
                 <div class="tvcmsnew-pagination">
                     <div class="tvcmsnew-pagination-wrapper">
                        <div class="tvcmsnew-next-pre-btn tvcms-next-pre-btn">
                            {* Data-parent musi pasować do nowej klasy *}
                            <div class="tvcmsnew-prev tvcmsprev-btn" data-parent="tvcmsnew-product-fix">
                                <i class='material-icons'>&#xe314;</i>
                            </div>
                             <div class="tvcmsnew-next tvcmsnext-btn" data-parent="tvcmsnew-product-fix">
                                <i class='material-icons'>&#xe315;</i>
                             </div>
                         </div>
                    </div>
                </div>
             </div>

        </div>
    </div>
{/if}
{/strip}