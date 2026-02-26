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
*  @author PrestaShop SA <contact@prestashop.com>
*  @copyright  2007-2025 PrestaShop SA
*  @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*}
{strip}
{if isset($productType) && $productType=='grid'}
    <div itemprop="aggregateRating" itemscope itemtype="http://schema.org/AggregateRating">
    <div class="tvall-product-star-icon" itemprop="reviewCount" content='{if $total_comments > 0}{$total_comments}{else}1{/if}'>
        <div class="star_content" itemprop="ratingValue" content='{if $averageTotal > 0}{$averageTotal}{else}1{/if}'>
            {$count_review = 0}
            {section name="i" start=0 loop=5 step=1}
                {if $averageTotal le $smarty.section.i.index}
                    <div class="star"><i class='material-icons'>&#xe838;</i> {*<img src="{$path}star-no.png">*}</div>
                {else}
                    <div class="star star_on"><i class='material-icons'>&#xe838;</i> {* <img src="{$path}star.png">*}</div>
                    {$count_review = $count_review + 1}
                {/if}
            {/section}
        </div>
        {if $page.page_name == 'product'}
            <div class='tvall-product-review'>
                {if $total_comments == 0 || $total_comments < 1}
                    {l s='Review' mod='tvcmsproductcomments' mod='tvcmsproductcomments'}&nbsp;
                    {l s='(%s)'|sprintf:$total_comments mod='tvcmsproductcomments'}
                
                {else}
                    {l s='Reviews' mod='tvcmsproductcomments' mod='tvcmsproductcomments'}&nbsp;
                    {l s='(%s)'|sprintf:$total_comments mod='tvcmsproductcomments'}
                {/if}
            </div>
        {/if}    
    </div> 
    </div> 
{else}
    <div class="tvall-product-star-icon">
        <div class="star_content">
            {$count_review = 0}
            {section name="i" start=0 loop=5 step=1}
                {if $averageTotal le $smarty.section.i.index}
                    <div class="star"><i class='material-icons'>&#xe838;</i> {*<img src="{$path}star-no.png">*}</div>
                {else}
                    <div class="star star_on"><i class='material-icons'>&#xe838;</i> {* <img src="{$path}star.png">*}</div>
                    {$count_review = $count_review + 1}
                {/if}
            {/section}
        </div>
        {if $page.page_name == 'product'}
            <div class='tvall-product-review'>
                {if $total_comments == 0 || $total_comments < 1}
                    {l s='Review' mod='tvcmsproductcomments' mod='tvcmsproductcomments'}&nbsp;
                    {l s='(%s)'|sprintf:$total_comments mod='tvcmsproductcomments'}
                
                {else}
                    {l s='Reviews' mod='tvcmsproductcomments'}&nbsp;
                    {l s='(%s)'|sprintf:$total_comments mod='tvcmsproductcomments'}
                {/if}
            </div>
        {/if}    
    </div> 
{/if}
{/strip}