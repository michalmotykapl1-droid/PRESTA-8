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
{include file='_partials/helpers.tpl'}
<!doctype html>
<html lang="{$language.iso_code}">

  <head>
    {block name='head'}
      {include file='_partials/head.tpl'}
    {/block}

    {* Start TVCMS file of custom setting css *}
      {include file="_partials/tvcms-javascript-files.tpl"}
    {* End TVCMS file of custom setting css *}

  </head>

  <body id="{$page.page_name}" class="{$page.body_classes|classnames} {if $res_1}{$res_1}{/if}  {if $res_2}{$res_2}{/if}" {if Configuration::get('TVCMSCUSTOMSETTING_ADD_CONTAINER')} style='{hook h="displayBackgroundBody"};background-repeat:{Configuration::get("TVCMSCUSTOMSETTING_BACKGROUND_IMAGE_REPEAT")};background-attachment:{Configuration::get("TVCMSCUSTOMSETTING_BACKGROUND_IMAGE_ATTACHMENT")};' {/if} data-mouse-hover-img='{Configuration::get("TVCMSCUSTOMSETTING_HOVER_IMG")}' data-menu-sticky='{Configuration::get("TVCMSCUSTOMSETTING_MAIN_MENU_STICKY")}'>

    {block name='hook_after_body_opening_tag'}
      {hook h='displayAfterBodyOpeningTag'}
    {/block}
    {hook h='displayThemeOptions'}
    <main>
    {include file='_partials/tvcms-page-loader.tpl'}
      <div class="tv-main-div {if Configuration::get('TVCMSCUSTOMSETTING_ADD_CONTAINER')}tv-box-layout container{/if}" {if Configuration::get('TVCMSCUSTOMSETTING_BODY_BACKGROUND_COLOR_STATUS') == '1'}style='{hook h="displayBodyBackgroundBody"};background-repeat:{Configuration::get("TVCMSCUSTOMSETTING_BACKGROUND_IMAGE_REPEAT")};background-attachment:{Configuration::get("TVCMSCUSTOMSETTING_BACKGROUND_IMAGE_ATTACHMENT")};'{/if}>

        {block name='product_activation'}
          {include file='catalog/_partials/product-activation.tpl'}
        {/block}

        <header id="header">
          {block name='header'}
            {include file='_partials/header.tpl'}
          {/block}
          
        </header>

        {block name='notifications'}
          {include file='_partials/notifications.tpl'}
        {/block}

        <div id="wrapper">
          {hook h="displayWrapperTop"}
          <div class="container {if $page.page_name != 'index' || isset($page.body_classes['layout-left-column'])} tv-left-layout{else}tv-full-layout {if isset($page.body_classes['layout-full-width'])}tvcms-full-layout{elseif isset($page.body_classes['layout-both-columns'])}tvcms-left-right-layout{elseif isset($page.body_classes['layout-left-column'])}tvcms-left-layout{elseif isset($page.body_classes['layout-right-column'])}tvcms-right-layout{/if}{/if}">
            {if $page.page_name != 'index'}
            {block name='breadcrumb'}
              {include file='_partials/breadcrumb.tpl'}
            {/block}
            {/if}
            <div class="row">
            {if !Context::getContext()->isMobile() && !Context::getContext()->isTablet()}
            {block name="left_column"}
              <div id="left-column" class="col-xl-2 col-lg-12 col-md-12 col-sm-12 col-xs-12">
                <div class="theiaStickySidebar">
                  {if Configuration::get('TVCMSCUSTOMSETTING_FILTER_LEFT_PANEL') == 1}
                      {hook h='displayFacetedSearchBlock'}
                  {/if}
                  <div class='tvleft-column-remove'>
                    <div class="tvleft-column-close-btn"></div>
                  </div>
                  {if $page.page_name == 'product'}
                    {hook h='displayLeftColumnProduct'}
                  {else}
                    {hook h="displayLeftColumn"}
                  {/if}
                </div>
              </div>
            {/block}
            {/if}

            {block name="content_wrapper"}
              <div id="content-wrapper" class="left-column right-column col-xl-8 col-lg-8 col-md-8 col-sm-8 col-xs-12{* {if isset($page.body_classes['layout-both-columns'])}col-xl-8 col-lg-8 col-md-8 col-sm-8 col-xs-12{elseif isset($page.body_classes['layout-left-column']) || isset($page.body_classes['layout-right-column'])}col-xl-9 col-lg-9 col-md-9 col-sm-9 col-xs-12{/if} *}">
                <div class="theiaStickySidebar">
                  {hook h="displayContentWrapperTop"}
                  {block name="content"}
                    <p>Hello world! This is HTML5 Boilerplate.</p>
                  {/block}
                  {hook h="displayContentWrapperBottom"}
                </div>
              </div>
            {/block}

            {if Context::getContext()->isMobile() || Context::getContext()->isTablet()}
            {block name="left_column"}
              <div id="left-column" class="col-xl-2 col-lg-12 col-md-12 col-sm-12 col-xs-12">
                <div class="theiaStickySidebar">
                  {if Configuration::get('TVCMSCUSTOMSETTING_FILTER_LEFT_PANEL') == 1}
                      {hook h='displayFacetedSearchBlock'}
                  {/if}
                  <div class='tvleft-column-remove'>
                    <div class="tvleft-column-close-btn"></div>
                  </div>
                  {if $page.page_name == 'product'}
                    {hook h='displayLeftColumnProduct'}
                  {else}
                    {hook h="displayLeftColumn"}
                  {/if}
                </div>
              </div>
            {/block}
            {/if}

            {block name="right_column"}
              <div id="right-column" class="col-xl-2 col-lg-12 col-md-12 col-sm-12 col-xs-12">
                <div class="theiaStickySidebar">
                  <div class='tvright-column-remove'>
                    <div class="tvright-column-close-btn"></div>
                  </div>
                  {if $page.page_name == 'product'}
                    {hook h='displayRightColumnProduct'}
                  {else}
                    {hook h="displayRightColumn"}
                  {/if}
                </div>
              </div>
            {/block}

            </div>
          </div>
        <div class="half-wrapper-backdrop"></div>
        </div>

        <footer id="footer">
          {if $page.page_name == 'index'}
            {hook h="displayNewsLetterPopup"}
          {/if}
          
          {block name="footer"}
            {include file="_partials/footer.tpl"}
          {/block}
        </footer>
      </div>
    </main>
    <div class="full-wrapper-backdrop"></div>

    {block name='javascript_bottom'}
      {include file="_partials/password-policy-template.tpl"}
      {include file="_partials/javascript.tpl" javascript=$javascript.bottom}
    {/block}


    {block name='hook_before_body_closing_tag'}
      {hook h='displayBeforeBodyClosingTag'}
    {/block}
  </body>
</html>
{/strip}