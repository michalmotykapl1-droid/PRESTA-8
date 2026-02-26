/**
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
*/

$(document).ready(function(){

	$('.tvcmstestimonial .tvcmsdelete').click(function(e){
		e.preventDefault();
		if(confirm("Are you sure want to delete this record?")){
			$(this).parent('.actionDelete').trigger('submit');
		}
	});

	function removeTags(){
    	setTimeout(function() {
          $(".tvcmstestimonial-position-update").remove();
        }, 3000);
    }

	var url = baseDir + "modules/tvcmstestimonial/ajax.php";
    var obj = $(document).find('body');
    $(".tvcmstestimonial tbody").sortable({
        opacity: 0.6,
        cursor: 'move',
        update: function() {
            var order = $(this).sortable("serialize") + '&action=update_position';	            
            $.post(url, order, function(data) {

                var arr_data = data.split('##');

                // this is Show result
                var result = arr_data[0];

                var notic = 'Position is Updated.';
                if (result == 'right') {
                    var tags = '';
                    tags += '<div id="growls" class="default tvcmstestimonial-position-update"><div class="growl growl-notice growl-medium">';
                    tags += '<div class="growl-close">×</div>';
                    tags += '<div class="growl-title"></div>'
                    tags += '<div class="growl-message">'+ notic +'</div>';
                    tags += '</div></div>';

                    obj.find('#growls').html(tags);
                    removeTags();
                }
            });
        }
    });
});