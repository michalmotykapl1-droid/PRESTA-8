/*
* 2007-2025 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Open Software License (OSL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/osl-3.0.php
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
*  @version  Release: $Revision$
*  @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/
// JavaScript Document
(function(jQuery){ 
	jQuery.fn.balance = function (options) {
		var defaults =	{
							set_height : false,	// false will set 'min-height', true will set 'height',

							same_height : true,
							same_width : false,
							
							limit_height : false,
							limit_width : false,
					
							max_height : 100,
							max_width : 100
						}
		var options = jQuery.extend(defaults, options) ;				
		$maxheight = $maxwidth = 0 ;
		$class = jQuery(this) ;

		$class.each (function () {
			$maxheight = parseFloat(jQuery(this).height()) > $maxheight ? jQuery(this).height() : $maxheight ;
			$maxwidth = parseFloat(jQuery(this).width()) > $maxwidth ? jQuery(this).width() : $maxwidth ;
		});	// return this.each
		if(options.same_height) {
			$maxheight = options.limit_height ? (options.max_height <= $maxheight ? options.max_height : $maxheight) : $maxheight ;
			//$class.height($maxheight) ;	
			// console.log($class.html());
            if(options.set_height) {
            	$class.css({'height' : $maxheight+'px'}) ;	
            }
            else {
            	$class.css({'min-height' : $maxheight+'px'}) ;	
            }
		}

		if(options.same_width) {
			$maxwidth = options.limit_width ? (options.max_width <= $maxwidth? options.max_width : $maxwidth) : $maxwidth ;
			$class.width($maxwidth) ;	
		}

	}		// $.fn.validate = function (options) 
})(jQuery) ;