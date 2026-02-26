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
    
    <div class='tvbest-seller-product-wrapper-box container bb-bestseller-section'>
        
        <div class="tvbest-seller-product-all-box">
            
            {* TYTUŁ: STYL STREF (JESZCZE MNIEJSZY - 24px) *}
            <div class='tvtab-main-title-wrapper' style="text-align: center; margin-bottom: 40px; position: relative;">
                
                {* 1. SAM TEKST NAGŁÓWKA *}
                <h2 style="
                    font-family: inherit;
                    font-size: 24px !important;   /* ZMNIEJSZONO z 28px na 24px */
                    font-weight: 700 !important;
                    color: #222222 !important;
                    text-transform: uppercase !important;
                    letter-spacing: -0.5px !important;
                    margin: 0 0 15px 0 !important;
                    padding: 0 !important;
                    line-height: 1.2 !important;
                    display: block;
                    text-align: center;">
                    
                    NASZE GORĄCE TYPY 
                    <i class='material-icons' style="
                        color: #ff5a00 !important; 
                        font-size: 24px !important; /* Dopasowano do tekstu */
                        vertical-align: middle !important;
                        margin-left: 6px;
                        margin-bottom: 4px;">whatshot</i>
                </h2>

                {* 2. LINIA PODKREŚLAJĄCA (POMARAŃCZOWA) *}
                <div style="
                    width: 60px;
                    height: 4px;
                    background-color: #ff5a00; 
                    margin: 0 auto;
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
                
                <div class="tvbest-seller-product-content {$col}">
                     <div class="tvall-block-box-shadows">
                         <div class="tvbest-seller-product">
                                 {* PĘTLA 1-RZĘDOWA *}
                                 <div class="products owl-theme owl-carousel tvbest-seller-product-wrapper tvproduct-wrapper-content-box" data-has-image='{if $image == true}true{else}false{/if}'>
                                      {foreach $dis_arr_result.data.product_list as $product}
                                        {include file="catalog/_partials/miniatures/product.tpl" product=$product tv_product_type="best_seller_product" }
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
            <div class='tvtab-pagination-wrapper tv-pagination-wrapper' style="position: absolute; top: 0; right: 0;">
                 <div class="tvcmsbest-seller-pagination">
                     <div class="tvcmsbest-seller-pagination-wrapper">
                        <div class="tvcmsbest-seller-next-pre-btn tvcms-next-pre-btn" style="display:flex; gap:10px;">
                             <div class="tvcmsbest-seller-prev tvcmsprev-btn" data-parent="tvcmsbest-seller-product" style="width:36px; height:36px; background:#fff; display:flex; align-items:center; justify-content:center; cursor:pointer; box-shadow:0 2px 8px rgba(0,0,0,0.1);">
                                <i class='material-icons' style="font-size:18px; color:#333;">&#xe314;</i>
                            </div>
                            <div class="tvcmsbest-seller-next tvcmsnext-btn" data-parent="tvcmsbest-seller-product" style="width:36px; height:36px; background:#fff; display:flex; align-items:center; justify-content:center; cursor:pointer; box-shadow:0 2px 8px rgba(0,0,0,0.1);">
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