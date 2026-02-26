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
{if Configuration::get('TVCMSNEWSLETTERPOPUP_POPUP_STATUS')}
<div id='tvcmsNewsLetterPopup' class='modal fade' tabindex='-1' role='dialog'>
    <div class='tvcmsNewsLetterPopup-i modal-dialog' role='document'>
        <button type='button' class='close tvnewsletterpopup-button-icon' data-dismiss='modal' aria-label='Close'>
                <i class='material-icons'>&#xe5cd;</i>
        </button>
        <div class='tvcmsnewsletterpopup' style='{if $show_fields["bg_image"] && Configuration::get("TVCMSNEWSLETTERPOPUP_BG_IMG_STATUS") && Configuration::get("TVCMSNEWSLETTERPOPUP_BG_IMG", $id_lang)}background-image: url({$path}{(Configuration::get("TVCMSNEWSLETTERPOPUP_BG_IMG", $id_lang))});{else}{* background-color:#fff; *}{/if}'>
            <div class='tvnewslatter-popup-img-wrapper'>
                {if $show_fields['image'] && Configuration::get(TVCMSNEWSLETTERPOPUP_IMG_STATUS) && Configuration::get('TVCMSNEWSLETTERPOPUP_IMG', $id_lang)}
                <img src="{$path}{Configuration::get('TVCMSNEWSLETTERPOPUP_IMG', $id_lang)}" alt="" class="tv-img-responsive" loading="lazy">
                {/if}
            </div>
            <div id='newsletter_block_popup' class='block d-flex'>
                <div class='block_content'>
                    {if isset($msg) && $msg}
                    <p class='{if $nw_error}warning_inline{else}success_inline{/if}'>{$msg}</p>
                    {/if}
                    <form method='post'>
                        {if $show_fields['title'] && Configuration::get('TVCMSNEWSLETTERPOPUP_TITLE', $id_lang)}
                        <div class='newsletter_title'>
                            <h3 class='h3'>{Configuration::get('TVCMSNEWSLETTERPOPUP_TITLE', $id_lang)}</h3>
                        </div>
                        {/if}
                        {if $show_fields['sub_title'] && Configuration::get('TVCMSNEWSLETTERPOPUP_SUB_DESCRIPTION', $id_lang)}
                        <div class='tvcmsnewsletterpopupContent'>
                            {Configuration::get('TVCMSNEWSLETTERPOPUP_SUB_DESCRIPTION', $id_lang)}
                        </div>
                        {/if}
                        {if $show_fields['description'] && Configuration::get('TVCMSNEWSLETTERPOPUP_DESCRIPTION', $id_lang)}
                        <div class='tvcmsnewsletterpopupContent'>
                            {Configuration::get('TVCMSNEWSLETTERPOPUP_DESCRIPTION', $id_lang)}
                        </div>
                        {/if}
                        <div class='tvcmsnewsletterpopupAlert'></div>
                        <div class="tvnewsletterpopup-input">
                            <input class='inputNew' id='tvcmsnewsletterpopupnewsletter-input' type='text' name='email' placeholder="{l s='Twój adres e-mail...' mod='tvcmsnewsletterpopup'}" />
                        </div>
                        <button id='tvnewsletter-email-subscribe' class='send-reqest button_unique tvall-inner-btn'>
                            <span>{l s='Zapisuję się' mod='tvcmsnewsletterpopup'}</span>
                        </button>
                    </form>
                    <div class='newsletter_block_popup-bottom d-flex justify-content-center'>
                        {* <div class='subscribe-bottom'>
                            <input id='tvcmsnewsletterpopup_newsletter_dont_show_again' type='checkbox'>
                        </div> *}
                        <label class='tvcmsnewsletterpopup_newsletter_dont_show_again' for='tvcmsnewsletterpopup_newsletter_dont_show_again'>{l s='Nie pokazuj ponownie tego okna' mod='tvcmsnewsletterpopup'}</label>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
{/if}
{/strip}