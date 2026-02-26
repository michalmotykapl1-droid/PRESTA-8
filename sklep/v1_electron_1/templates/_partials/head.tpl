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
* versions in the future.
* If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
* @author PrestaShop SA <contact@prestashop.com>
    * @copyright 2007-2025 PrestaShop SA
    * @license https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
    * International Registered Trademark & Property of PrestaShop SA
    *}
    {strip}
    {block name='head_charset'}
    <meta charset="utf-8">
    {/block}
    {block name='head_ie_compatibility'}
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    {/block}
    {block name='head_seo'}
    {block name='head_microdata'}
    {include file="_partials/microdata/head-jsonld.tpl"}
    {/block}
    {block name='head_microdata_special'}{/block}
    
    {block name='head_pagination_seo'}
    {include file="_partials/pagination-seo.tpl"}
    {/block}
    <title>{block name='head_seo_title'}{$page.meta.title}{/block}</title>
    {block name='hook_after_title_tag'}
    {hook h='displayAfterTitleTag'}
    {/block}
    <meta name="description" content="{block name='head_seo_description'}{$page.meta.description}{/block}">
    <meta name="keywords" content="{block name='head_seo_keywords'}{$page.meta.keywords}{/block}">
    {if $page.meta.robots !== 'index'}
    <meta name="AdsBot-Google" content="{$page.meta.robots}">
    {/if}
    {if $page.canonical}
    <link rel="canonical" href="{$page.canonical}">
    {/if}
    {block name='head_hreflang'}
    {foreach from=$urls.alternative_langs item=pageUrl key=code}
    <link rel="alternate" href="{$pageUrl}" hreflang="{$code}">
    {/foreach}
  
    {/block}
    {block name='head_open_graph'}
    <meta property="og:title" content="{$page.meta.title}" />
    <meta property="og:description" content="{$page.meta.description}" />
    <meta property="og:url" content="{$urls.current_url}" />
    <meta property="og:site_name" content="{$shop.name}" />
    {if !isset($product) && $page.page_name != 'product'}
    <meta property="og:type" content="website" />{/if}
    {/block}
    {/block}
    {block name='head_viewport'}
    <meta name="viewport" content="width=device-width, initial-scale=1">
    {/block}
    {block name='head_icons'}
    <link rel="icon" type="image/vnd.microsoft.icon" href="{$shop.favicon}?{$shop.favicon_update_time}">
    <link rel="shortcut icon" type="image/x-icon" href="{$shop.favicon}?{$shop.favicon_update_time}">
    {/block}
 
    {block name='stylesheets'}
    {include file="_partials/stylesheets.tpl" stylesheets=$stylesheets}
    {/block}

    {* --- DODANO: MATERIAL ICONS (NAJNOWSZA WERSJA Z GOOGLE) --- *}
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    {* ---------------------------------------------------------- *}

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    {block name='javascript_head'}
    {include file="_partials/javascript.tpl" javascript=$javascript.head vars=$js_custom_vars}
    {/block}
    {block name='hook_header'}
    {$HOOK_HEADER nofilter}
    {/block}
    <link rel="dns-prefetch" href="{$urls.shop_domain_url}" />
    <link rel="preconnect" href="{$urls.shop_domain_url}" crossorigin />
    <link rel="preload" href="{$urls.css_url}570eb83859dc23dd0eec423a49e147fe.woff2" as="font" type="font/woff2" crossorigin />
    {*
    <link rel="preload" href="{$urls.css_url}../fonts/roboto/KFOiCnqEu92Fr1Mu51QrEz0dL_nz.woff2" as="font" type="font/woff2" crossorigin /> *}
    <link rel="preload" href="{$urls.css_url}../fonts/roboto/KFOlCnqEu92Fr1MmYUtfBBc4.woff2" as="font" type="font/woff2" crossorigin />
    
    <link rel="preload" href="{$urls.css_url}../fonts/roboto-condensed/ieVl2ZhZI2eCN5jzbjEETS9weq8-19K7DQ.woff2" as="font" type="font/woff2" crossorigin media='(min-width: 992px)' />
    <link as="style" rel="stylesheet preload" type="text/css" href="{$urls.css_url}material-fonts.css" />
    <link as="style" rel="stylesheet preload" type="text/css" href="{$urls.css_url}roboto.css" />
    <link as="style" rel="stylesheet preload" type="text/css" href="{$urls.css_url}roboto-condensed.css" media='(min-width: 992px)' />
    {*
    <link as="style" rel="stylesheet preload" type="text/css" href="{$urls.css_url}media-tablet.css" media='screen and (max-width: 1024px)' />
    <link as="style" rel="stylesheet preload" type="text/css" href="{$urls.css_url}media-mobile.css" media='screen and (max-width: 768px)' /> *}
    {if $ArabicFontStatus}
    <link type="text/css" rel="stylesheet" href="{$urls.css_url}iran-yekan.css" media="all">
    <style type="text/css">
    body.lang-rtl,
    body.lang-rtl 
    p,
    body.lang-rtl h1,
    body.lang-rtl h2,
    body.lang-rtl h3,
    body.lang-rtl h4,
    body.lang-rtl h5,
    body.lang-rtl h6,
    body.lang-rtl span,
    body.lang-rtl div {
        font-family: 'iran-yekan' !important;
    }
    </style>
    {/if}
    {if Configuration::get('TVCMSCUSTOMSETTING_DARK_MODE_INPUT')}
    <link rel="stylesheet" class="dark-theme-css-r" type="text/css" href="{$urls.css_url}dark-theme.css">
    {/if}
    {block name='hook_extra'}
    {/block}
{literal}
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "WebSite",
  "name": "{/literal}{$shop.name|escape:'html':'UTF-8'}{literal}",
  "url": "{/literal}{$urls.base_url|escape:'html':'UTF-8'}{literal}",
  "potentialAction": {
    "@type": "SearchAction",
    "target": "{/literal}{$urls.base_url|escape:'html':'UTF-8'}{literal}szukaj?controller=search&s={search_term_string}",
    "query-input": "required name=search_term_string"
  }
}
</script>
{/literal}


    {/strip}