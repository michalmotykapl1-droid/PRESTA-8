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
<link rel="stylesheet" href="{$urls.base_url}modules/ps_categorytree/views/css/ps_categorytree.custom.css?v=2">
{strip}
{if $CategoryListHomePageStatus == 1}
{function name="categories" nodes=[] depth=0}
    {if $nodes|count}
      <ul class="category-sub-menu">
        {foreach from=$nodes item=node}
          <li data-depth="{$depth}" class="{if !empty($node.in_path)} is-in-path open{/if}{if !empty($node.current)} current{/if}">
            {if $depth===0}
              <a href="{$node.link}"
                 class="{if !empty($node.current)} current{/if}"
                 title="{$node.name|escape:'html':'UTF-8'}">{$node.name}</a>
              {if $node.children}
                <div class="navbar-toggler collapse-icons"
                     data-toggle="collapse"
                     data-target="#exCollapsingNavbar{$node.id}"
                     aria-controls="exCollapsingNavbar{$node.id}"
                     aria-expanded="{if !empty($node.in_path)}true{else}false{/if}">
                  <i class="material-icons add">&#xE145;</i>
                  <i class="material-icons remove">&#xE15B;</i>
                </div>
                <div class="collapse{if !empty($node.in_path)} show in open{/if}"
                     id="exCollapsingNavbar{$node.id}"
                     aria-expanded="{if !empty($node.in_path)}true{else}false{/if}"
                     {if !empty($node.in_path)}style="display:block; height:auto;" data-state="open"{/if}>
                  {categories nodes=$node.children depth=$depth+1}
                </div>
              {/if}
            {else}
              <a class="category-sub-link{if !empty($node.current)} current{/if}"
                 href="{$node.link}"
                 title="{$node.name|escape:'html':'UTF-8'}">{$node.name}</a>
              {if $node.children}
                <span class="navbar-toggler collapse-icons"
                      data-toggle="collapse"
                      data-target="#exCollapsingNavbar{$node.id}"
                      aria-controls="exCollapsingNavbar{$node.id}"
                      aria-expanded="{if !empty($node.in_path)}true{else}false{/if}">
                  <i class="material-icons add">&#xE145;</i>
                  <i class="material-icons remove">&#xE15B;</i>
                </span>
                <div class="collapse{if !empty($node.in_path)} show in open{/if}"
                     id="exCollapsingNavbar{$node.id}"
                     aria-expanded="{if !empty($node.in_path)}true{else}false{/if}"
                     {if !empty($node.in_path)}style="display:block; height:auto;" data-state="open"{/if}>
                  {categories nodes=$node.children depth=$depth+1}
                </div>
              {/if}
            {/if}
          </li>
        {/foreach}
      </ul>
    {/if}
  {/function}

<div class="block-categories">
  <ul class="category-top-menu tvside-panel-dropdown open">
    <li class="tv-category-title-wrapper">
      <a class="tv-main-category-title" href="{$categories.link nofilter}">{$categories.name}</a>
      <div class='tvleft-right-title-toggle'>
        <i class='material-icons'>&#xe313;</i>
      </div>
    </li>
    <li class="tv-leftcategory-wrapper">{categories nodes=$categories.children}</li>
  </ul>
</div>
{else if $CategoryListHomePageStatus == 0 && $page.page_name != 'index' && $page.page_name == 'category'}
{function name="categories" nodes=[] depth=0}
    {if $nodes|count}
      <ul class="category-sub-menu">
        {foreach from=$nodes item=node}
          <li data-depth="{$depth}" class="{if !empty($node.in_path)} is-in-path open{/if}{if !empty($node.current)} current{/if}">
            {if $depth===0}
              <a href="{$node.link}"
                 class="{if !empty($node.current)} current{/if}"
                 title="{$node.name|escape:'html':'UTF-8'}">{$node.name}</a>
              {if $node.children}
                <div class="navbar-toggler collapse-icons"
                     data-toggle="collapse"
                     data-target="#exCollapsingNavbar{$node.id}"
                     aria-controls="exCollapsingNavbar{$node.id}"
                     aria-expanded="{if !empty($node.in_path)}true{else}false{/if}">
                  <i class="material-icons add">&#xE145;</i>
                  <i class="material-icons remove">&#xE15B;</i>
                </div>
                <div class="collapse{if !empty($node.in_path)} show in open{/if}"
                     id="exCollapsingNavbar{$node.id}"
                     aria-expanded="{if !empty($node.in_path)}true{else}false{/if}"
                     {if !empty($node.in_path)}style="display:block; height:auto;" data-state="open"{/if}>
                  {categories nodes=$node.children depth=$depth+1}
                </div>
              {/if}
            {else}
              <a class="category-sub-link{if !empty($node.current)} current{/if}"
                 href="{$node.link}"
                 title="{$node.name|escape:'html':'UTF-8'}">{$node.name}</a>
              {if $node.children}
                <span class="navbar-toggler collapse-icons"
                      data-toggle="collapse"
                      data-target="#exCollapsingNavbar{$node.id}"
                      aria-controls="exCollapsingNavbar{$node.id}"
                      aria-expanded="{if !empty($node.in_path)}true{else}false{/if}">
                  <i class="material-icons add">&#xE145;</i>
                  <i class="material-icons remove">&#xE15B;</i>
                </span>
                <div class="collapse{if !empty($node.in_path)} show in open{/if}"
                     id="exCollapsingNavbar{$node.id}"
                     aria-expanded="{if !empty($node.in_path)}true{else}false{/if}"
                     {if !empty($node.in_path)}style="display:block; height:auto;" data-state="open"{/if}>
                  {categories nodes=$node.children depth=$depth+1}
                </div>
              {/if}
            {/if}
          </li>
        {/foreach}
      </ul>
    {/if}
  {/function}

<div class="block-categories">
  <ul class="category-top-menu tvside-panel-dropdown open">
    <li class="tv-category-title-wrapper">
      <a class="tv-main-category-title" href="{$categories.link nofilter}">{$categories.name}</a>
      <div class='tvleft-right-title-toggle'>
        <i class='material-icons'>&#xe313;</i>
      </div>
    </li>
    <li class="tv-leftcategory-wrapper">{categories nodes=$categories.children}</li>
  </ul>
</div>
{/if}
{/strip}
