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
var fnTvcmsNewsLetterPopupStatus = true;
jQuery(document).ready(function($){
	if ($.cookie("tvcmsnewsletterpopup") != "true") {
    	var obj = $(document).find('#tvnewsletter-email-subscribe');
    	var fnTvcmsNewsLetterPopup = function(){
    		var height = $(window).scrollTop();
            if(height > 49 && fnTvcmsNewsLetterPopupStatus){
            	fnTvcmsNewsLetterPopupStatus = false;
            	setTimeout(function(){
                	$("#tvcmsNewsLetterPopup").modal({show: true});
                }, 3000);
            }
    	}
    	$(window).scroll(function(){
    		fnTvcmsNewsLetterPopup();
    	});
    	themevoltyCallEventsPush(fnTvcmsNewsLetterPopup);
		$("#tvnewsletter-email-subscribe").click(function(){
			var email = $("#tvcmsnewsletterpopupnewsletter-input").val();
			$.ajax({
				type: "POST",
				headers: { "cache-control": "no-cache" },
				async: false,
				url: ajax_path,
				// data: "name=marek&email="+email,
				data: "name=marek&email="+email,
				success: function(data) {
					if (data) {
						$(".tvcmsnewsletterpopupAlert").text(data);
					}
				}
			});
		});
		$('#tvcmsnewsletterpopupnewsletter-input').keypress(function(event){
		  var keycode = (event.keyCode ? event.keyCode : event.which);
		  if (keycode == '13') {
					var email = $("#tvcmsnewsletterpopupnewsletter-input").val();
					$.ajax({
						type: "POST",
						headers: { "cache-control": "no-cache" },
						async: false,
						url: ajax_path,
						data: "name=marek&email="+email,
						success: function(data) {
							if (data) {
								$(".tvcmsnewsletterpopupAlert").text(data);
							}
						}
					});
					event.preventDefault();
		  }
		});
        $("#tvcmsnewsletterpopup_newsletter_dont_show_again").prop("checked") == false;
	}
	$(document).on('click','.tvcmsnewsletterpopup_newsletter_dont_show_again',function(){
		$.cookie("tvcmsnewsletterpopup", "true");
		$('#tvcmsNewsLetterPopup').css('display','none');
		$('#tvcmsNewsLetterPopup').removeClass('in');
		$('.modal-backdrop.fade.in').remove();
		$('body').removeClass('modal-open');
	});
	// $('#tvcmsnewsletterpopup_newsletter_dont_show_again').change(function(){
	//     if($(this).is(':checked')){
	// 		$.cookie("tvcmsnewsletterpopup", "true");
	//     }else{
	// 		$.cookie("tvcmsnewsletterpopup", "false");
	//     }
	// });
});