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
    <div class="contact-form">
        <form action="{$urls.pages.contact}" method="post" {if $contact.allow_file_upload}enctype="multipart/form-data" {/if}> {if $notifications} <div class="col-xs-12 alert {if $notifications.nw_error}alert-danger{else}alert-success{/if}">
            <ul>
                {foreach $notifications.messages as $notif}
                <li>{$notif}</li>
                {/foreach}
            </ul>
    </div>
    {/if}
    {if !$notifications || $notifications.nw_error}
    <div class="form-fields">
        <div class="form-group row">
            <div class="col-md-12 col-xs-12 text-center">
                <h3>{l s='Contact us' d='Shop.Theme.Global'}</h3>
            </div>
        </div>
        <div class="row">
        <div class="col-md-1 col-lg-2"></div>
        <div class="col-md-10 col-lg-8 ">
          <div class="row">
            <div class="col-md-6">
                <div class="form-group row">
                    <label class="col-xs-12 form-control-label">{l s='Subject' d='Shop.Forms.Labels'}</label>
                    <div class="col-xs-12 col-md-12">
                        <select name="id_contact" class="form-control form-control-select">
                            {foreach from=$contact.contacts item=contact_elt}
                            <option value="{$contact_elt.id_contact}">{$contact_elt.name}</option>
                            {/foreach}
                        </select>
                    </div>
                </div>
                <div class="form-group row">
                    <label class="col-xs-12 form-control-label">{l s='Email address' d='Shop.Forms.Labels'}</label>
                    <div class="col-xs-12 col-md-12">
                        <input class="form-control" name="from" type="email" value="{$contact.email}" placeholder="{l s='your@email.com' d='Shop.Forms.Help'}">
                    </div>
                </div>
                {if $contact.orders}
                <div class="form-group row">
                    <label class="col-xs-12 form-control-label">{l s='Order reference' d='Shop.Forms.Labels'}</label>
                    <div class="col-xs-12 col-md-12">
                        <select name="id_order" class="form-control form-control-select">
                            <option value="">{l s='Select reference' d='Shop.Forms.Help'}</option>
                            {foreach from=$contact.orders item=order}
                            <option value="{$order.id_order}">{$order.reference}</option>
                            {/foreach}
                        </select>
                    </div>
                    <span class="col-md-3 form-control-comment">
                        {l s='optional' d='Shop.Forms.Help'}
                    </span>
                </div>
                {/if}
                {if $contact.allow_file_upload}
                <div class="form-group row">
                    <label class="col-xs-12 form-control-label">
                        {l s='Attachment' d='Shop.Forms.Labels'} &nbsp; ( {l s='Optional' d='Shop.Forms.Help'} )
                    </label>
                    <div class="col-xs-12 col-md-12">
                        <input type="file" name="fileUpload" class="filestyle" data-buttonText="{l s='Choose file' d='Shop.Theme.Actions'}">
                    </div>
                </div>
                {/if}
            </div>
            <div class="col-md-6 col-xs-12">
                <div class="form-group row">
                    <label class="col-xs-12 form-control-label">{l s='Message' d='Shop.Forms.Labels'}</label>
                    <div class="col-xs-12 col-md-12">
                        <textarea class="form-control tvcontact-area-text" name="message" placeholder="{l s='How can we help?' d='Shop.Forms.Help'}" rows="3">
                    {if $contact.message}{$contact.message}{/if}
                  </textarea>
                    </div>
                </div>
                {if isset($id_module)}
                <div class="form-group row">
                    <div class="offset-md-3">
                        {hook h='displayGDPRConsent' id_module=$id_module}
                    </div>
                </div>
                {/if}
                <footer class="form-footer text-sm-right">
                    <input type="text" name="url" value="" />
                    <input type="hidden" name="token" value="{$token}" />
                    <button class="tvall-inner-btn" type="submit" name="submitMessage" value="">
                        <span>{l s='Send' d='Shop.Theme.Actions'}</span>
                    </button>
                </footer>
            </div>
            </div>
        </div>
        <div class="col-md-1 col-lg-2"></div>
        </div>
    </div>
    {/if}
    </form>
    </div>
{/strip}