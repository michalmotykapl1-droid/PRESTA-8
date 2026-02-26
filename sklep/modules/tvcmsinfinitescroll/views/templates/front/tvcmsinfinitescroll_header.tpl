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
{if isset($prev_page_value) && $prev_page_value}
    <link rel="prev" href="{$prev_page_value|escape:'htmlall':'UTF-8'}">
{/if}
{if isset($next_page_value) && $next_page_value}
<link rel="next" href="{$next_page_value|escape:'htmlall':'UTF-8'}">
{/if}


<!-- Module TvcmsInfiniteScroll for PRODUCTS -->
{if isset($tv_options)}
<script>
var tv_params = {
    product_wrapper : "{$tv_options.product_wrapper|escape:'htmlall':'UTF-8'}",
    product_elem : "{$tv_options.product_elem|escape:'htmlall':'UTF-8'}",
    pagination_wrapper : "{$tv_options.pagination_wrapper|escape:'htmlall':'UTF-8'}",
    next_button : "{$tv_options.next_button|escape:'htmlall':'UTF-8'}",
    views_buttons : "{$tv_options.views_buttons|escape:'htmlall':'UTF-8'}",
    selected_view : "{$tv_options.selected_view|escape:'htmlall':'UTF-8'}",
    method : "{$tv_options.method|escape:'htmlall':'UTF-8'}",
    button_start_page : "{$tv_options.button_start_page|escape:'htmlall':'UTF-8'}",
    button_n_pages : "{$tv_options.button_n_pages|escape:'htmlall':'UTF-8'}",
    active_with_layered : "{$tv_options.active_with_layered|escape:'htmlall':'UTF-8'}",
    loader : "<div id=\"tv-loader\"><p>{$tv_texts.loading_text|escape:'htmlall':'UTF-8'}</p></div>",
    loader_prev : "<div id=\"tv-loader\"><p>{$tv_texts.loading_prev_text|escape:'htmlall':'UTF-8'}</p></div>",
    button : "<button id=\"tv-button-load-products\">{$tv_texts.button_text|escape:'htmlall':'UTF-8'}</button>",
    back_top_button : "<div id=\"tv-back-top-wrapper\"><p>{$tv_texts.end_text|escape:'htmlall':'UTF-8'} <a href=\"#\" class=\"tv-back-top-link\">{$tv_texts.go_top_text|escape:'htmlall':'UTF-8'}</a></p></div>",
    tvcmsinfinitescrollqv_enabled : "{$tv_options.tvcmsinfinitescrollqv_enabled|escape:'htmlall':'UTF-8'}",
    has_facetedSearch : "{$tv_options.has_facetedSearch|escape:'htmlall':'UTF-8'}",
    ps_16 : "{$tv_options.ps_16|escape:'htmlall':'UTF-8'}"
}

// -----------------------------------------------------------
// HOOK CUSTOM
// - After next products displayed
// function tv_hook_after_display_products() {

    // ---------------
    // CUSTOMIZE HERE
    // ---------------

// }
</script>
{/if}
{/strip}