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
 *
 * Don't forget to prefix your containers with your own identifier
 * to avoid any conflicts with others containers.
 */
function confirmAction() {
    let confirmAction = confirm("Are you sure to execute this action?");
    if (confirmAction) {
        document.getElementById("tvinstalldata").value = "1";
    } else {
        document.getElementById("tvinstalldata").value = "0";
    }
    return confirmAction;
}

$(document).ready(function() {
    $(".tvall-pattern-show").click(function() {
        $('.tvall-pattern-show').removeClass('tvcms_custom_setting_active');
        $(this).addClass('tvcms_custom_setting_active');
        var pattern = $(this).attr('id');
        $(document).find('#tvcmscustomsetting_pattern').val(pattern);
    });

    $(".tvall-body-pattern-show").click(function() {
        $('.tvall-body-pattern-show').removeClass('tvcms_custom_setting_body_active');
        $(this).addClass('tvcms_custom_setting_body_active');
        var pattern = $(this).attr('id');
        $(document).find('#tvcmscustomsetting_body_pattern').val(pattern);
    });

    var tab_number = $('#tvcmscustom-setting-tab-number').val();
    $('.tvcmsadmincustom-setting').find('.panel').hide();
    $(tab_number).show();


    $('.tvadmincustom-setting-tab').click(function(event) {
        var tab_number = $(this).attr('tab-number');
        $('.tvadmincustom-setting-tab').removeClass('tvadmincustom-setting-active');
        $(this).addClass('tvadmincustom-setting-active');
        $('#tvcmscustom-setting-tab-number').val(tab_number);
        $('.tvcmsadmincustom-setting').find('.panel').hide();
        $(tab_number).show();
    });

    $('input[type=radio][name=TVCMSCUSTOMSETTING_BACKGROUND_IMAGE_PATTERN_STATUS]').on('change', function() {

        if ($('.tvcmsbackground-type input#active_on[type=radio][name=TVCMSCUSTOMSETTING_BACKGROUND_IMAGE_PATTERN_STATUS]').is(':checked')) {
            $(this).closest('.form-group').next().show();
            $(this).closest('.form-group').next().next().hide();
            $(this).closest('.form-group').next().next().next().hide();
            $(this).closest('.form-group').next().next().next().next().hide();
        } else {
            $(this).closest('.form-group').next().hide();
            $(this).closest('.form-group').next().next().show();
            $(this).closest('.form-group').next().next().next().show();
            $(this).closest('.form-group').next().next().next().next().show();
        }
    });

    $('input[type=radio][name=TVCMSCUSTOMSETTING_ADD_CONTAINER]').on('change', function() {
        if ($('#TVCMSCUSTOMSETTING_ADD_CONTAINER_on').is(':checked')) {
            $(this).closest('.form-group').next().show();

            if ($('.tvcmsbackground-type input#active_on[type=radio][name=TVCMSCUSTOMSETTING_BACKGROUND_IMAGE_PATTERN_STATUS]').is(':checked')) {
                $(this).closest('.form-group').next().next().show();
            } else {
                $(this).closest('.form-group').next().next().next().show();
                $(this).closest('.form-group').next().next().next().next().show();
                $(this).closest('.form-group').next().next().next().next().next().show();
            }
        } else {
            $(this).closest('.form-group').next().hide();
            $(this).closest('.form-group').next().next().hide();
            $(this).closest('.form-group').next().next().next().hide();
            $(this).closest('.form-group').next().next().next().next().hide();
            $(this).closest('.form-group').next().next().next().next().next().hide();
        }
    });

    $('input[type="file"][name=tvcmscustomsetting_custom_pattern]').change(function() {
        $('#tvcmscustomsetting_pattern').val("custombodypattern");
    });

    $('input[type=radio][name=TVCMSCUSTOMSETTING_BODY_BACKGROUND_COLOR_STATUS]').on('change', function() {
        if ($('#TVCMSCUSTOMSETTING_BODY_BACKGROUND_COLOR_STATUS_on').is(':checked')) {
            $(this).closest('.form-group').next().show();

            if ($('.tvcmsbody-background-type  input#active_on[type=radio][name=TVCMSCUSTOMSETTING_BODY_BACKGROUND_IMAGE_PATTERN_STATUS]').is(':checked')) {
                $(this).closest('.form-group').next().next().show();
                $(this).closest('.form-group').next().next().next().hide();
                $(this).closest('.form-group').next().next().next().next().hide();
                $(this).closest('.form-group').next().next().next().next().next().hide();
            } else {
                $(this).closest('.form-group').next().next().hide();
                $(this).closest('.form-group').next().next().next().show();
                $(this).closest('.form-group').next().next().next().next().show();
                $(this).closest('.form-group').next().next().next().next().next().show();
            }
        } else {
            $(this).closest('.form-group').next().hide();
            $(this).closest('.form-group').next().next().hide();
            $(this).closest('.form-group').next().next().next().hide();
            $(this).closest('.form-group').next().next().next().next().hide();
            $(this).closest('.form-group').next().next().next().next().next().hide();
        }
    });


    $('input[type=radio][name=TVCMSCUSTOMSETTING_BODY_BACKGROUND_IMAGE_PATTERN_STATUS]').on('change', function() {
        if ($('input#active_on[type=radio][name=TVCMSCUSTOMSETTING_BODY_BACKGROUND_IMAGE_PATTERN_STATUS]').is(':checked')) {
            $(this).closest('.form-group').next().show();
            $(this).closest('.form-group').next().next().hide();
            $(this).closest('.form-group').next().next().next().hide();
            $(this).closest('.form-group').next().next().next().next().hide();
        } else {
            $(this).closest('.form-group').next().hide();
            $(this).closest('.form-group').next().next().show();
            $(this).closest('.form-group').next().next().next().show();
            $(this).closest('.form-group').next().next().next().next().show();
        }
    });

    $('input[type="file"][name=tvcmscustomsetting_custom_body_pattern]').change(function() {
        $('#tvcmscustomsetting_body_pattern').val("custombodypattern");
    });


    $('input[type=radio][name=TVCMSCUSTOMSETTING_CUSTOM_FONT_TITLE_COLOR_STATUS]').on('change', function() {
        if ($('#TVCMSCUSTOMSETTING_CUSTOM_FONT_TITLE_COLOR_STATUS_on:checked').is(':checked')) {
            $(this).closest('.form-group').next().show();
            $(this).closest('.form-group').next().next().show();
            $(this).closest('.form-group').next().next().next().show();
        } else {
            $(this).closest('.form-group').next().hide();
            $(this).closest('.form-group').next().next().hide();
            $(this).closest('.form-group').next().next().next().hide();
        }
    });

    $('input[type=radio][name=TVCMSCUSTOMSETTING_THEME_OPTION]').on('change', function() {
        var val = $(this).val();
        if (val.match(/theme_custom/g)) {
            $(this).closest('.form-group').parent().parent().parent().next().show();
            $(this).closest('.form-group').parent().parent().parent().next().next().show();
        } else {
            $(this).closest('.form-group').parent().parent().parent().next().hide();
            $(this).closest('.form-group').parent().parent().parent().next().next().hide();
        }
    });
    $(document).on('click', '#tvcmsInstallDataForm', function() {
        if ($("input[type=checkbox]").is(":checked")) {
            $('#module_form_submit_btn_4').text('Install Sample Data');
        } else {
            $('#module_form_submit_btn_4').text('Upgrade Database');
        }
    });


});
window.onload = function() {
    //theme option
    var val = $('input[type=radio][name=TVCMSCUSTOMSETTING_THEME_OPTION]:checked').val();
    if (typeof val != "undefined") {
        if (val.match(/theme_custom/g)) {
            $('input[type=radio][name=TVCMSCUSTOMSETTING_THEME_OPTION]').closest('.form-group').parent().parent().parent().next().show();
            $('input[type=radio][name=TVCMSCUSTOMSETTING_THEME_OPTION]').closest('.form-group').parent().parent().parent().next().next().show();
        } else {
            $('input[type=radio][name=TVCMSCUSTOMSETTING_THEME_OPTION]').closest('.form-group').parent().parent().parent().next().hide();
            $('input[type=radio][name=TVCMSCUSTOMSETTING_THEME_OPTION]').closest('.form-group').parent().parent().parent().next().next().hide();
        }

        //box layout and full layout
        if ($('#TVCMSCUSTOMSETTING_ADD_CONTAINER_off').is(':checked')) {
            $('#TVCMSCUSTOMSETTING_ADD_CONTAINER_off').closest('.form-group').next().hide();
            $('#TVCMSCUSTOMSETTING_ADD_CONTAINER_off').closest('.form-group').next().next().hide();
            $('#TVCMSCUSTOMSETTING_ADD_CONTAINER_off').closest('.form-group').next().next().next().hide();
            $('#TVCMSCUSTOMSETTING_ADD_CONTAINER_off').closest('.form-group').next().next().next().next().hide();
            $('#TVCMSCUSTOMSETTING_ADD_CONTAINER_off').closest('.form-group').next().next().next().next().next().hide();
        } else {
            //bacground color or patten
            if ($('.tvcmsbackground-type input#active_on[type=radio][name=TVCMSCUSTOMSETTING_BACKGROUND_IMAGE_PATTERN_STATUS]').is(':checked')) {
                $('#TVCMSCUSTOMSETTING_ADD_CONTAINER_off').closest('.form-group').next().next().show();
                $('#TVCMSCUSTOMSETTING_ADD_CONTAINER_off').closest('.form-group').next().next().next().hide();
                $('#TVCMSCUSTOMSETTING_ADD_CONTAINER_off').closest('.form-group').next().next().next().next().hide();
                $('#TVCMSCUSTOMSETTING_ADD_CONTAINER_off').closest('.form-group').next().next().next().next().next().hide();
            } else {
                $('#TVCMSCUSTOMSETTING_ADD_CONTAINER_off').closest('.form-group').next().next().hide();
                $('#TVCMSCUSTOMSETTING_ADD_CONTAINER_off').closest('.form-group').next().next().next().show();
                $('#TVCMSCUSTOMSETTING_ADD_CONTAINER_off').closest('.form-group').next().next().next().next().show();
                $('#TVCMSCUSTOMSETTING_ADD_CONTAINER_off').closest('.form-group').next().next().next().next().next().show();
            }
        }
    }

    if ($('#TVCMSCUSTOMSETTING_BODY_BACKGROUND_COLOR_STATUS_off').is(':checked')) {
        $('#TVCMSCUSTOMSETTING_BODY_BACKGROUND_COLOR_STATUS_off').closest('.form-group').next().hide();
        $('#TVCMSCUSTOMSETTING_BODY_BACKGROUND_COLOR_STATUS_off').closest('.form-group').next().next().hide();
        $('#TVCMSCUSTOMSETTING_BODY_BACKGROUND_COLOR_STATUS_off').closest('.form-group').next().next().next().hide();
        $('#TVCMSCUSTOMSETTING_BODY_BACKGROUND_COLOR_STATUS_off').closest('.form-group').next().next().next().next().hide();
        $('#TVCMSCUSTOMSETTING_BODY_BACKGROUND_COLOR_STATUS_off').closest('.form-group').next().next().next().next().next().hide();
    } else {
        if ($('.tvcmsbody-background-type input#active_on[type=radio][name=TVCMSCUSTOMSETTING_BODY_BACKGROUND_IMAGE_PATTERN_STATUS]').is(':checked')) {
            $('#TVCMSCUSTOMSETTING_BODY_BACKGROUND_COLOR_STATUS_off').closest('.form-group').next().show();
            $('#TVCMSCUSTOMSETTING_BODY_BACKGROUND_COLOR_STATUS_off').closest('.form-group').next().next().show();
            $('#TVCMSCUSTOMSETTING_BODY_BACKGROUND_COLOR_STATUS_off').closest('.form-group').next().next().next().hide();
            $('#TVCMSCUSTOMSETTING_BODY_BACKGROUND_COLOR_STATUS_off').closest('.form-group').next().next().next().next().hide();
            $('#TVCMSCUSTOMSETTING_BODY_BACKGROUND_COLOR_STATUS_off').closest('.form-group').next().next().next().next().next().hide();
        } else {
            $('#TVCMSCUSTOMSETTING_BODY_BACKGROUND_COLOR_STATUS_off').closest('.form-group').next().show();
            $('#TVCMSCUSTOMSETTING_BODY_BACKGROUND_COLOR_STATUS_off').closest('.form-group').next().next().hide();
            $('#TVCMSCUSTOMSETTING_BODY_BACKGROUND_COLOR_STATUS_off').closest('.form-group').next().next().next().show();
            $('#TVCMSCUSTOMSETTING_BODY_BACKGROUND_COLOR_STATUS_off').closest('.form-group').next().next().next().next().show();
            $('#TVCMSCUSTOMSETTING_BODY_BACKGROUND_COLOR_STATUS_off').closest('.form-group').next().next().next().next().next().show();
        }
    }



    if ($('#TVCMSCUSTOMSETTING_CUSTOM_FONT_TITLE_COLOR_STATUS_on:checked').is(':checked')) {
        $('#TVCMSCUSTOMSETTING_CUSTOM_FONT_TITLE_COLOR_STATUS_on').closest('.form-group').next().show();
        $('#TVCMSCUSTOMSETTING_CUSTOM_FONT_TITLE_COLOR_STATUS_on').closest('.form-group').next().next().show();
        $('#TVCMSCUSTOMSETTING_CUSTOM_FONT_TITLE_COLOR_STATUS_on').closest('.form-group').next().next().next().show();
    } else {
        $('#TVCMSCUSTOMSETTING_CUSTOM_FONT_TITLE_COLOR_STATUS_on').closest('.form-group').next().hide();
        $('#TVCMSCUSTOMSETTING_CUSTOM_FONT_TITLE_COLOR_STATUS_on').closest('.form-group').next().next().hide();
        $('#TVCMSCUSTOMSETTING_CUSTOM_FONT_TITLE_COLOR_STATUS_on').closest('.form-group').next().next().next().hide();
    }
}