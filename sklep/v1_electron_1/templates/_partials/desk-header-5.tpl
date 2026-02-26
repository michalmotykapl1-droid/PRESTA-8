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
    <div class="tvcmsdesktop-top-header-wrapper header-5" data-header-layout="5">
        <div class='tvheader-offer-wrapper tvforce-hide'>
            {hook h='displayTopOfferText'}
        </div>
        <div class='container-fluid tvcmsdesktop-top-second hidden-md-down'>
            <div class="container">
                <div class="row tvcmsdesktop-top-header-box">
                    <div class='col-xl-4 col-lg-4 col-md-4 col-sm-12 tvheader-language-currency-wrapper'>
                        <div class="tvheader-language">{if $withData}{hook h='displayNavLanguageBlock'}{/if}</div>
                        <div class="tvheader-currency">{if $withData}{hook h='displayNavCurrencyBlock'}{/if}</div>
                    </div>
                    <div class='col-xl-4 col-lg-4 col-md-4 col-sm-12 tvcms-header-logo-wrapper'>
                        <div class="hidden-sm-down tvcms-header-logo" id="tvcmsdesktop-logo">
                            {if $withData}
                            <div class="tv-header-logo">
                                <a href="{$urls.base_url}">
                                    <img class="logo img-responsive" src="{$shop.logo}" alt="{$shop.name}" height="35" width="201">
                                </a>
                            </div>
                            {/if}
                        </div>
                    </div>
                    <div class="col-xl-4 col-lg-4 col-md-4 col-sm-12 tvcmsheader-nav-right">
                        <div class="tv-contact-account-cart-wrapper">
                            <div class='tvcmssearch-wrapper' id="_desktop_search">
                                {if $withData}
                                {hook h='displayNavSearchBlock'}
                                {/if}
                            </div>
                            <div id='tvcmsdesktop-account-button'>
                                {if $withData}
                                <div class="tv-header-account tv-account-wrapper tvcms-header-myaccount">
                                    <button class="btn-unstyle tv-myaccount-btn tv-myaccount-btn-desktop" name="User Icon" aria-label="User Icon">
                                        <svg version="1.1" id="Layer_1" x="0px" y="0px" width="31.377px" height="30.938px" viewBox="0 0 31.377 30.938" xml:space="preserve">
                                            <g>
                                                <path style="fill:none;stroke:#000000;stroke-width:0.6;stroke-miterlimit:10;" d="M15.666,17.321
        c7.626,0,13.904,5.812,14.837,13.316h0.525c-1.253-8.325-7.642-13.6-15.341-13.6c-7.698,0-14.088,5.274-15.339,13.6h0.48
        C1.764,23.134,8.041,17.321,15.666,17.321z"></path>
                                                <path style="fill:#FFD742;" d="M15.688,16.992c-4.494,0-8.15-3.654-8.15-8.148c0-4.497,3.656-8.152,8.15-8.152
        c4.497,0,8.15,3.655,8.15,8.152C23.839,13.338,20.186,16.992,15.688,16.992"></path>
                                                <circle style="fill:none;stroke:#000000;stroke-miterlimit:10;" cx="15.689" cy="8.838" r="8.338"></circle>
                                            </g>
                                        </svg>
                                    </button>
                                    <ul class="dropdown-menu tv-account-dropdown tv-dropdown">
                                        {if $customer.is_logged}
                                        <li><a href="{$urls.pages.my_account}" class="tvmyccount"><i class="material-icons">&#xe7fd;</i>{l s='My Account' d='Shop.Theme.Catalog'}</a></li>
                                        {/if}
                                        <li class="tvcms-signin">{hook h='displayNavCustomerSignInBlock'}</li>
                                        <li class="ttvcms-wishlist-icon">{hook h='displayNavWishlistBlock'}</li>
                                        <li class="tvheader-compare">{hook h='displayNavProductCompareBlock'}</li>
                                    </ul>
                                </div>
                                {/if}
                            </div>
                            <div id="_desktop_cart_manage" class="tvcms-header-cart">
                                {if $withData}
                                {hook h='displayNavShoppingCartBlock'}
                                {/if}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class=" tvcms-header-menu-offer-wrapper tvcmsheader-sticky">
            <div class="position-static tvcms-header-menu">
                <div class='tvcmsmain-menu-wrapper'>
                    <div id='tvdesktop-megamenu'>
                        {if $withData}
                        {hook h='displayMegamenu'}
                        {/if}
                    </div>
                </div>
                <div class="tvcmsdesktop-contact tvforce-hide">{if $withData}{hook h='displayNav1'}{/if}</div>
            </div>
        </div>
    </div>
    {/strip}