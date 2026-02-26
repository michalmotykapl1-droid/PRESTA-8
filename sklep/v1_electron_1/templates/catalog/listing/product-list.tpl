{**
 * 2007-2025 PrestaShop
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License 3.0 (AFL-3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
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
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2007-2025 PrestaShop SA
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 * International Registered Trademark & Property of PrestaShop SA
 *}
{strip}
{extends file=$layout}

{block name='head_microdata_special'}
  {include file='_partials/microdata/product-list-jsonld.tpl' listing=$listing}
{/block}
{block name='content'}
  <div id="main">
    {block name='product_list_header'}
    {/block}

    <div id="products" class="{Configuration::get('TVCMSCUSTOMSETTING_PRODUCT_LIST_VIEW')}">
      {if $listing.products|count}
        <div>
          {block name='product_list_top'}
            {include file='catalog/_partials/products-top.tpl' listing=$listing}
          {/block}
        </div>
        {block name='product_list_active_filters'}
          <div {* class="hidden-sm-down" *}>
            {$listing.rendered_active_filters nofilter}
          </div>
        {/block}
        <div>
          {block name='product_list'}
            {include file='catalog/_partials/products.tpl' listing=$listing}
          {/block}
        </div>

        <div id="js-product-list-bottom">
          {block name='product_list_bottom'}
            {include file='catalog/_partials/products-bottom.tpl' listing=$listing}
          {/block}
        </div>

      {else}

        {include file='errors/not-found.tpl'}

      {/if}

    </div>
  </div>
{if Configuration::get('TVCMSCAT_BANNER_STATUS') == 0 && $page.page_name == 'category' && !empty($category.image.large.url)}
  <div class="block-category card card-block clearfix tv-category-block-wrapper">
      {if $category.image.large.url}
      <div class="tv-category-cover">
          <img src="{$category.image.large.url}" width="{$category.image.large.width}" height="{$category.image.large.height}" alt="{if !empty($category.image.legend)}{$category.image.legend}{else}{$category.name}{/if}" alt="{if !empty($category.image.legend)}{$category.image.legend}{else}{$category.name}{/if}" class="tv-img-responsive" loading="lazy"/>
      </div>
      {/if}
      {if !empty($category.image.large.url)}
      <div class="tv-all-page-main-title-wrapper">
          <div class="tv-all-page-main-title">{$category.name}</div>
      </div>
      {/if}
      {if $category.description}
      <div id="category-description" class="text-muted">{$category.description nofilter}</div>
      {/if}
 </div>
 {/if}
{/block}
{/strip}