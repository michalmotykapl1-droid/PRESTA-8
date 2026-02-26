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
* @author PrestaShop SA <contact@prestashop.com>
* @copyright 2007-2025 PrestaShop SA
* @license https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
* International Registered Trademark & Property of PrestaShop SA
*}
{strip}
    {if $page.page_name == 'index'}
    {hook h="displayWrapperBottom"}
    {/if}
    {hook h='displayShowMap'}
    {* <div class="tvfooter-before">
        <div class="container-fluid">
            <div class="container">
                <div class="row">
                    {block name='hook_footer_before'}
                    {hook h='displayFooterBefore'}
                    {/block}
                </div>
            </div>
        </div>
    </div> *}
    <div class="footer-container tvcmsfooter-background-image footer-4" data-footer-layout="4" {if $FooterImageStatus}style="background-image: url('{$paths}{$FooterBackgroundImage}');" {/if}> <div class="container tvfooter-logo-link-wrapper">
        <div class='tvfooter-storelogo-imformation row'>
            <div class="tvfooter-storelogo-weapper col-xl-4 col-lg-4 col-md-12 links">
                <div class="tvfooter-storelogo-inner clearfix">
                    {block name='hook_footer_part_2'}
                    {hook h='displayFooterPart2'}
                    {hook h='displayContactCinfo'}
                    {/block}
                </div>
            </div>
            <div class="tvfooter-links-wrapper col-xl-8 col-lg-8 col-md-12 links">
                {block name='hook_footer_part_1'}
                {hook h='displayFooterCategory'}
                {hook h='displayFooterPart1'}
                {hook h='displayMyaccountcblock'}
                {hook h='displayFooterStoreTime'}
                {hook h='displayNewslettersubscription'}
                {/block}
            </div>
        </div>
        <div class="col-xl-3 col-lg-3 col-md-12 tvfooter-newletter-icon">
            {block name='hook_footer_part_3'}
            {hook h='displayFooterPart3'}
            {/block}
        </div>
        {block name='hook_footer'}
        {hook h='displayFooter'}
        {/block}
        {hook h='displayCustomsocialblock'}
        {* <div class="row">
            {block name='hook_footer_after'}
            {hook h='displayFooterAfter'}
            {/block}
        </div> *}
        <div class='tvbottom-to-top'>
            <a href="javascript:" class="tvbottom-to-top-icon">
                <span>{l s='Go Top' d='Shop.Theme.Catalog'}</span>
                <i class='material-icons'>&#xe316;</i>
            </a>
        </div>
    </div>
    <div class="tvfooter-copy-payment-icon-wrapper">
        <div class="container tvfooter-copy-payment-icon-content">
            <div class="row tvfooter-copy-payment-icon-inner">
                {hook h='displayPaymentIcon'}
                {hook h='displayCopyRightText'}
                {hook h='displaySocialMediaBlock'}
            </div>
        </div>
    </div>
    </div>
    {hook h='displayCustomJs'}
    {hook h='displayTvcmsFooter'}
{/strip}