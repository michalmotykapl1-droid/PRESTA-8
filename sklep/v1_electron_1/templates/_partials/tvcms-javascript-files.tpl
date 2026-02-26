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
{if Configuration::get('TVCMSFRONTSIDE_THEME_SETTING_SHOW')}
    <!-- START THEME_CONTROL -->
    <div class="tvcms-custom-theme"></div>
    <!-- END THEME_CONTROL -->
{/if}
{if Configuration::get('TVCMSCUSTOMSETTING_THEME_OPTION')}
    {if Configuration::get('TVCMSCUSTOMSETTING_ALL_THEME_CSS_PATH')}
      <link rel="stylesheet" type="text/css" href="{$urls.css_url}{Configuration::get('TVCMSCUSTOMSETTING_ALL_THEME_CSS_PATH')}"></link>
    {/if}
    {if Configuration::get('TVCMSCUSTOMSETTING_THEME_FONT_TYPE_LINK')}
     <link rel="stylesheet" type="text/css"  href="{Configuration::get('TVCMSCUSTOMSETTING_THEME_FONT_TYPE_LINK')}"></link>
	{/if}
	{if Configuration::get('TVCMSCUSTOMSETTING_THEME_FONT_TYPE_LINK_2')}
     <link rel="stylesheet" type="text/css"  href="{Configuration::get('TVCMSCUSTOMSETTING_THEME_FONT_TYPE_LINK_2')}"></link>
	{/if} 
      <!-- END THEME_CONTROL CUSTOM COLOR CSS -->
{/if}
{/strip}