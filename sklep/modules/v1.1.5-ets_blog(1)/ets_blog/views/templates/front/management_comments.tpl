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
{extends file="page.tpl"}
{block name="content"}
    <div id="content-wrapper">
        <div id="main">
            <header class="page-header">
                <h1>{l s='My blog comments' mod='ets_blog'}</h1>
            </header>
            {if $sucsecfull_html}
                {$sucsecfull_html nofilter}
            {/if}
            {if isset($errors_html) && $errors_html}
                {$errors_html nofilter}
            {/if}
            <section id="content">
                <div class="ets_blog_layout_list ets-blog-wrapper-form-managament ets-blog-wrapper-detail">
                    {$html_content nofilter}
                </div>
            </section>
            {hook h='displayFooterYourAccount'}
        </div>
    </div>
{/block}