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
* to license@buy-addons.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
* @author    Buy-addons    <contact@buy-addons.com>
* @copyright 2007-2025 Buy-addons
* @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

function autosize() {
	var textarea = document.querySelector('#note');
	textarea.addEventListener('keydown', autosize);      
	function autosize(){
		  var el = this;
		  setTimeout(function(){
			el.style.cssText = 'height:auto; padding:0';
			el.style.cssText = 'height:' + el.scrollHeight + 'px';
		  },0);
	}    
}
function embed() {
	$("#uploadvideo").css('display', 'none');
		$("#url_video").css('display', 'block');
}

function upload() {
	$("#uploadvideo").css('display', 'block');
	$("#url_video").css('display', 'none');
}

function autoreload(){
	location.reload();
}

function textfile(id_lang) {
	$('#fileToUpload'+id_lang).trigger('click');
		$('#fileToUpload'+id_lang).change(function (e) {
			var path_video = $(this).val();
			path_video = path_video.replace(/\\/g, "/");
			$('#text_file'+id_lang).attr('value', path_video);
			if ($(this)[0].files !== undefined)
			{
				var files = $(this)[0].files;
				var name = '';

				$.each(files, function (index, value) {
					name += value.name + ', ';
				});

				$('.test'+id_lang).html(name.slice(0, -2));
			} else {
				var name = $(this).val().split(/[\\/]/);
				$('.test'+id_lang).html(name[name.length - 1]);
			}
		});
}
function savevideo(){
	if (id_product == 0) {
		$(".alert_video").css('display', 'block');
	}
	else {
	var type_video='';
	var formData;
	if ($('#form').length > 0) {
		formData = new FormData($('#form')[0]);
	} else if ($('#product_form').length > 0) {
		formData = new FormData($('#product_form')[0]);
	} else {
		formData = new FormData($('.product-form')[0]);
	}

	var radio = document.getElementsByName("type_video");
	for (var i = 0; i < radio.length; i++){
		if (radio[i].checked === true){
			type_video = (radio[i].value);
		}
	}
	formData.append("id_shop", id_shop);
	formData.append("name_shop", name_shop);
	var text_file=$("#text_file").val();
	$.ajax({
		url: "" + url + "?fc=module&module=tvcmsvideotab&controller=savevideo",
		type: "POST",
		data: formData,
		async: false,
		success: function (data) {
			if (data == 0) {
				document.body.scrollTop = 0;
				document.documentElement.scrollTop = 0;
				$(".alert_embed").css('display', 'block');
			}
			if (data == 4) {
				document.body.scrollTop = 0;
				document.documentElement.scrollTop = 0;
				$(".alert_select").css('display', 'block');
			}
			if (data == 1) {
				document.body.scrollTop = 0;
				document.documentElement.scrollTop = 0;
				$(".alert_size").css('display', 'block');
			}
			if (data == 5) {
				document.body.scrollTop = 0;
				document.documentElement.scrollTop = 0;
				$(".alert_primary").css('display', 'block');
			}
			if (data == 2) {
				$(".alert_type").css('display', 'block');
			}

			if (data == 3) {
				alert(update_successful);
				$(".edittab").css('display', 'none');
				$(".momo").removeClass('showmo');
				setTimeout(function() {
				  location.reload();
				}, 0);
			}
		},
		
		cache: false,
		contentType: false,
		processData: false
	});            

	}
	
}

function savevideoedit(){
	if (id_product == 0) {
		$(".alert_video").css('display', 'block');
	}
	else {
	var type_video='';
	var formData;
	if ($('#form').length > 0) {
		formData = new FormData($('#form')[0]);
	} else {
		formData = new FormData($('#product_form')[0]);
	}
	var radio = document.getElementsByName("type_video");
	for (var i = 0; i < radio.length; i++){
		if (radio[i].checked === true){
			type_video = (radio[i].value);
		}
	}
	formData.append("id_shop", id_shop);
	formData.append("name_shop", name_shop);
	var text_file = $("#text_file").val();
	$.ajax({
		url: "" + url + "?fc=module&module=tvcmsvideotab&controller=savevideo",
		type: "POST",
		data: formData,
		async: false,
		success: function (data) {
			if (data == 0) {
				document.body.scrollTop = 0;
				document.documentElement.scrollTop = 0;
				$(".alert_embed").css('display', 'block');
			}
			if (data == 4) {
				document.body.scrollTop = 0;
				document.documentElement.scrollTop = 0;
				$(".alert_select").css('display', 'block');
			}
			if (data == 1) {
				document.body.scrollTop = 0;
				document.documentElement.scrollTop = 0;
				$(".alert_size").css('display', 'block');
			}
			if (data == 5) {
				document.body.scrollTop = 0;
				document.documentElement.scrollTop = 0;
				$(".alert_primary").css('display', 'block');
			}
			if (data == 2) {
				$(".alert_type").css('display', 'block');
			}
			if (data == 3) {
				alert(update_successful);
				window.location.href = url2;
			}
		},
		
		cache: false,
		contentType: false,
		processData: false
	});
	}
}

function Edit(id_product){
    $('.edittab').css('display', 'block');
    $(".momo").addClass('showmo');
    $.post("" + url + "?fc=module&module=tvcmsvideotab&controller=editvideo",
        {
            id: id_product
        },
        function (data, status) {
            $(".edittab").html(data);
            $(".edittab .videocancel").attr('onclick', 'cancel()');
            $(".edittab .videocancel").attr('href', '#');
        }
    );

    $(".momo").click(function() {
        $(".edittab").css('display', 'none');
        $(".momo").removeClass('showmo');
    });
}
function cancel(){
    $(".edittab").css('display', 'none');
    $(".momo").removeClass('showmo');
}
function ConfirmDelete(id_product,type,id_lang)
{
  var x = confirm(are_you_sure);
  if (x == true) {
	$.post(url + "?fc=module&module=tvcmsvideotab&controller=confirmdelete",
		{
			id: id_product,
			id_lang: id_lang,
			id_shop: id_shop_new,
			type: type
		},
		function (data, status) {
			if (data == 1) {
				var y = confirm(video_removed);
				if (y == true) {
					location.reload();
				}
			}
			if (data == 2) {
				document.body.scrollTop = 0;
				document.documentElement.scrollTop = 0;
				$(".alert_primary").css('display', 'block');
			}
			
		}
	);
  }
  else {
	   return false;
  }
   
}   
function hideOtherLanguage1(id)
{
    $('.translatable-field1').hide();
    $('.lang1-' + id).show();
    var id_old_language = id_language;
    id_language = id;

    if (id_old_language != id)
        changeEmployeeLanguage();

    updateCurrentText();
}
function hideOtherLanguage2(id)
{
    $('.translatable-field2').hide();
    $('.lang2-' + id).show();

    var id_old_language = id_language;
    id_language = id;

    if (id_old_language != id)
        changeEmployeeLanguage();

    updateCurrentText();
}

$(document).ready(function(){
	if($('#embed input[name="type_video"]').is(':checked')){
		$("#url_video").css('display', 'block');
		$("#uploadvideo").css('display', 'none');
	} else{
		$("#url_video").css('display', 'none');
		$("#uploadvideo").css('display', 'block');
	}
});