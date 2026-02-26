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
{$display_prod = Configuration::get('TVCMSFOOTERPRODUCT_NUM_OF_PRODUCT')}
{if Configuration::get('TVCMSFOOTERPRODUCT_STATUS')}
    <div class="tvcmsmain-all-product tvmain-box-layout-content-wrapper container">
        <div class="tvmain-all-product">
            <div class="tvmain-product-all-wrapper-box row">

                <div class="col-xl-4 col-lg-4 col-md-12 col-sm-12 col-xs-12 tvcmsfooter-featured-product">
                    {if !empty($footer_product_list.featured_product)}
                    <div class="tvfooter-product-title-product tvall-block-box-shadows"> 
                        {if Configuration::get('TVCMSFOOTERPRODUCT_FEATURED_TITLE', $id_lang)}
                        <div class="tvcms-main-title tvcmsmain-title-wrapper">
                            <div class="tv-main-title tvcms-main-title">
                                <div class="tvtitle tvmain-title">
                                    <h2>{Configuration::get('TVCMSFOOTERPRODUCT_FEATURED_TITLE', $id_lang)}</h2>
                                </div>
                            </div>
                            <div class='tvfooter-featured-product-pagination-wrapper'>
                                <div class="tvfooter-featured-product-pagination-pagination">
                                    <div class="tvfooter-featured-product-pagination-next-pre-btn">
                                        <div class="tvfooter-featured-product-prev tvcmsprev-btn" data-parent='tvcmsfooter-featured-product'><i class='material-icons'>&#xe5cb;</i></div>
                                        <div class="tvfooter-featured-product-next tvcmsnext-btn" data-parent='tvcmsfooter-featured-product'><i class='material-icons'>&#xe5cc;</i></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        {/if}

                        <div class="tvmain-all-product-wrapper tvfooter-featured-prod-slider">
                            <div class="tvmain-featured-product-wrapper">
                                <div class="tvmain-featured-product-wrapper-info-box owl-theme owl-carousel">
                                    {$count = 1}
                                    {foreach from=$footer_product_list.featured_product.product_list item="product"}
                                        {if $count == 1}
                                            <div class="item tvmain-footer-tab-prod-slider col-xl-12 col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                        {/if}

                                        {include file="catalog/_partials/miniatures/footer-product.tpl" product=$product}

                                        {if $count == $display_prod}
                                            </div>
                                            {$count = 0}
                                        {/if}
                                        {$count = $count + 1}
                                    {/foreach}

                                    {if $count != '1'}
                                        </div>
                                    {/if}
                                </div>
                            </div>
                        </div>
                        <div class="tvfooter-view-link">
                            <a href='{$footer_product_list.featured_link}'>
                                <span>{l s='All Featured Products' mod='tvcmsfooterproduct'}</span>
                                <i class='material-icons'>&#xe315;</i>
                            </a>
                        </div>
                    </div>
                    {/if}
                </div>
                <div class="col-xl-4 col-lg-4 col-md-12 col-sm-12 col-xs-12 tvcmsfooter-new-product">
                    {if !empty($footer_product_list.new_product.product_list)}
                    <div class="tvfooter-product-title-product tvall-block-box-shadows"> 
                        {if Configuration::get('TVCMSFOOTERPRODUCT_NEW_TITLE', $id_lang)}
                        <div class="tvcms-main-title tvcmsmain-title-wrapper">
                            <div class="tv-main-title tvcms-main-title">
                                <div class="tvtitle tvmain-title">
                                    <h2>{Configuration::get('TVCMSFOOTERPRODUCT_NEW_TITLE', $id_lang)}</h2>
                                </div>
                            </div>
                            
                            <div class='tvfooter-new-product-pagination-wrapper'>
                                <div class="tvfooter-new-product-pagination-pagination">
                                    <div class="tvfooter-new-product-pagination-next-pre-btn">
                                        <div class="tvfooter-new-product-prev tvcmsprev-btn" data-parent='tvcmsfooter-new-product'><i class='material-icons'>&#xe5cb;</i></div>
                                        <div class="tvfooter-new-product-next tvcmsnext-btn" data-parent='tvcmsfooter-new-product'><i class='material-icons'>&#xe5cc;</i></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        {/if}
                        <div class="tvmain-all-product-wrapper tvfooter-new-prod-slider">
                            <div class="tvmain-new-product-wrapper">
                                <div class="tvmain-new-product-wrapper-info-box owl-theme owl-carousel">
                                    {$count = 1}
                                    {foreach from=$footer_product_list.new_product.product_list item="product"}
                                        {if $count == 1}
                                            <div class="item tvmain-footer-tab-prod-slider col-xl-12 col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                        {/if}

                                        {include file="catalog/_partials/miniatures/footer-product.tpl" product=$product }

                                        {if $count == $display_prod}
                                            </div>
                                            {$count = 0}
                                        {/if}
                                        {$count = $count + 1}
                                    {/foreach}

                                    {if $count != '1'}
                                        </div>
                                    {/if}
                                </div>
                            </div>
                        </div>
                        <div class="tvfooter-view-link">
                            <a href='{$footer_product_list.new_link}'>
                                <span>{l s=' All New Products' mod='tvcmsfooterproduct'}</span>
                                <i class='material-icons'>&#xe315;</i>
                            </a>
                        </div>
                    </div>
                    {/if}
                </div>

                <div class="col-xl-4 col-lg-4 col-md-12 col-sm-12 col-xs-12 tvcmsfooter-best-seller-product">
                    {if !empty($footer_product_list.best_seller_product.product_list)}
                    <div class="tvfooter-product-title-product tvall-block-box-shadows"> 
                        {if Configuration::get('TVCMSFOOTERPRODUCT_BEST_SELLER_TITLE', $id_lang)}
                        <div class="tvcms-main-title tvcmsmain-title-wrapper">
                            <div class="tv-main-title tvcms-main-title">
                                <div class="tvtitle tvmain-title">
                                    <h2>{Configuration::get('TVCMSFOOTERPRODUCT_BEST_SELLER_TITLE', $id_lang)}</h2>
                                </div>
                            </div>
                            <div class='tvfooter-best-seller-product-pagination-wrapper'>
                                <div class="tvfooter-best-seller-product-pagination-pagination">
                                    <div class="tvfooter-best-seller-product-pagination-next-pre-btn">
                                        <div class="tvfooter-best-seller-product-prev tvcmsprev-btn" data-parent="tvcmsfooter-best-seller-product"><i class='material-icons'>&#xe5cb;</i></div>
                                        <div class="tvfooter-best-seller-product-next tvcmsnext-btn" data-parent="tvcmsfooter-best-seller-product"><i class='material-icons'>&#xe5cc;</i></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        {/if}
                        <div class="tvmain-all-product-wrapper tvfooter-besr-prod-slider">
                            <div class="tvmain-best-product-wrapper">
                                <div class="tvmain-new-product-wrapper-info-box owl-theme owl-carousel">
                                {$count = 1}
                                {foreach from=$footer_product_list.best_seller_product.product_list item="product"}    
                                    {if $count == 1}
                                        <div class="item tvmain-footer-tab-prod-slider col-xl-12 col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                    {/if}

                                    {include file="catalog/_partials/miniatures/footer-product.tpl" product=$product }

                                    {if $count == $display_prod}
                                        </div>
                                        {$count = 0}
                                    {/if}
                                    {$count = $count + 1}
                                {/foreach}
                                {if $count != '1'}
                                    </div>
                                {/if}
                                </div>
                            </div>
                        </div>
                        <div class="tvfooter-view-link">
                            <a href='{$footer_product_list.best_seller_link}'>
                                <span>{l s=' All Best Products' mod='tvcmsfooterproduct'}</span>
                                <i class='material-icons'>&#xe315;</i>
                            </a>
                        </div>
                    </div>
                    {/if}   
                </div>
            </div>
        </div>
    </div>
{/if}
{/strip}