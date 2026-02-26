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
{extends file=$layout}
{block name='content'}
  <div id="main">

    {* START: DODANIE POLA WYSZUKIWANIA PRODUCENTÓW *}
    <div class="manufacturer-search-wrapper" style="margin-bottom: 20px;">
      <input type="search" id="manufacturer-search-input" class="form-control" placeholder="{l s='Wyszukaj producenta...' d='Shop.Theme.Catalog'}">
    </div>
    {* KONIEC: DODANIE POLA WYSZUKIWANIA PRODUCENTÓW *}

    {block name='brand_miniature'}
    <div class="card card-block">
      {* Dodajemy ID do listy UL dla łatwiejszego targetowania w JS *}
      <ul id="manufacturer-list" class="tvbrand-page-block row"> {* Dodano ID i klasę row *}
        {foreach from=$brands item=brand}
          {include file='catalog/_partials/miniatures/brand.tpl' brand=$brand}
        {/foreach}
      </ul>
    </div>
    {/block}

  </div>

{/block}
{/strip}