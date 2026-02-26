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
    <div class="tvproduct-timer" data-end-time='{$timer}'>
        <div class='tvtimer-wrapper'>
            <div class="tvproduct-timer-icon">
                <i class='material-icons'>&#xe425;</i>
            </div>
            <div class='tvproduct-timer-wrapper tvproduct-timer-box tvproduct-time-days'>
                <div class="days">00</div>
                {* <div class='tvproduct-timer-line'></div> *}
                <div class="tvtimer-name">{l s='day' d='Shop.Theme.Actions'}</div>
            </div>
            <span class="tvtimer-dot">:</span>
            <div class='tvproduct-timer-wrapper tvproduct-timer-box tvproduct-time-hours'>
                <div class="hours">00</div>
                {* <div class='tvproduct-timer-line'></div> *}
                <div class="tvtimer-name">{l s='hour' d='Shop.Theme.Actions'}</div>
            </div>
            <span class="tvtimer-dot">:</span>
            <div class='tvproduct-timer-wrapper tvproduct-timer-box tvproduct-time-minutes'>
                <div class="minutes">00</div>
                {* <div class='tvproduct-timer-line'></div> *}
                <div class="tvtimer-name">{l s='min' d='Shop.Theme.Actions'}</div>
            </div>
            <span class="tvtimer-dot">:</span>
            <div class='tvproduct-timer-wrapper tvproduct-timer-box tvproduct-time-seconds'>
                <div class="seconds">00</div>
                {* <div class='tvproduct-timer-line'></div> *}
                <div class="tvtimer-name">{l s='sec' d='Shop.Theme.Actions'}</div>
            </div>
            {* <div class="tvproduct-timer-complated"></div> *}
        </div>
    </div>
{/strip}