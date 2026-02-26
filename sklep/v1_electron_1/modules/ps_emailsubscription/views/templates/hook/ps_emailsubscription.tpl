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
<div class="tvcms-newsletter-wrapper col-xl-3 col-lg-3 col-md-12">
	<div class="tvcms-newsletter-inner">
		<div class="block_newsletter tv-newsletter-wrapeer">
			<div class="tvnewsletter-block">
				<div class="tvnewsletter-lable-wrapper">
					{if Configuration::get('TVCMSCUSTOMSETTING_NEWSLETTER_TITLE', $language.id)}
						<p id="block-newsletter-label" class="tvnewsletter-title">
							{Configuration::get('TVCMSCUSTOMSETTING_NEWSLETTER_TITLE', $language.id)}
						</p>
					{/if}

				</div>
				<div class="tvnewsletter-input">
					<form action="{$urls.pages.index}#footer" method="post">
						<div class="tvnewsleeter-input-button-wraper">
							<div class="input-wrapper">
								<input name="email" type="email" value="{$value}" placeholder="{l s='Your email address' d='Shop.Forms.Labels'}" aria-labelledby="block-newsletter-label">
							</div>
							<div class="tvnewsleteer-btn-wrapper">
								<button class='tvall-inner-btn' name="submitNewsletter" type="submit">
									<span class='tvnewslatter-btn-title hidden-lg-down'>{l s='Subscribe' d='Shop.Theme.Actions'}</span>
									<span class='tvnewslatter-btn-title hidden-xl-up'>{l s='OK' d='Shop.Theme.Actions'}</span>{*
									<i class='material-icons'>&#xe0be;</i>
								*}</button>
							</div>
						</div>
						<input type="hidden" name="action" value="0">
						<div class="tvnewsletter-description">
							{if $conditions}
								<p>{$conditions}</p>
							{/if}
							{if $msg}
								<span class="alert {if $nw_error}alert-danger{else}alert-success{/if}">
									{$msg}
								</span>
							{/if}
							{if isset($id_module)}
								{hook h='displayGDPRConsent' id_module=$id_module}
							{/if}
						</div>
					</form>
				</div>{* <!-- <div>
					{if Configuration::get('TVCMSCUSTOMSETTING_NEWSLETTER_SHORT_DESC', $language.id)}
					<p class="tvnewsletter-sub-title">
						{Configuration::get('TVCMSCUSTOMSETTING_NEWSLETTER_SHORT_DESC', $language.id)}
					</p>
					{/if}
				</div>
 -->*}</div>
		</div>
	</div>
</div>
{/strip}