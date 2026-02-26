{*
 * Copyright ETS Software Technology Co., Ltd
 *
 * NOTICE OF LICENSE
 *
 * This file is not open source! Each license that you purchased is only available for 1 website only.
 * If you want to use this file on more websites (or projects), you need to purchase additional licenses.
 * You are not allowed to redistribute, resell, lease, license, sub-license or offer our resources to any third party.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future.
 *
 * @author ETS Software Technology Co., Ltd
 * @copyright  ETS Software Technology Co., Ltd
 * @license    Valid for 1 website (or project) for each purchase of license
*}
{if isset($ets_blog_post_header)}
    <meta property="og:app_id"        content="id_app" />
    <meta property="og:type"          content="article" />
    <meta property="og:title"         content="{$ets_blog_post_header.title|escape:'html':'UTF-8'}" />
    <meta property="og:image"         content="{if $ets_blog_post_header.image}{$ets_blog_post_header.image|escape:'html':'UTF-8'}{else}{$ets_blog_post_header.thumb|escape:'html':'UTF-8'}{/if}" />
    <meta property="og:description"   content="{$ets_blog_post_header.short_description|strip_tags|escape:'html':'UTF-8'}" />
    <meta property="og:url"           content="{$ets_blog_post_header.link|escape:'quotes':'UTF-8'}" />
    <meta name="twitter:card"         content="summary_large_image" />
{/if}