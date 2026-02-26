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

$( document ).ready( function() {

    $('.tv-header-tabs .tab-link').click(function() {
        $('.tv-settings .tv-settings-content').hide();
        $('.tv-header-tabs .tab-link').removeClass('active');
        $( $(this).attr('href') ).show();
        $(this).addClass('active');
        return false;
    });
    $('.tv-header-tabs li:first a').click();


    $('#tv-settings .tv-options-title').click(function() {

        $(this).toggleClass('up');
        $(this).next('form').slideToggle();
        return false;

    });


    if (!tv_ps_16)
        $('#tv-settings .tv-input-submit').parent().addClass('panel-footer');

    tvUpdateViewsButtons();
    $("#TV_METHOD_on").click(function() {
        tvUpdateViewsButtons();
    });
    $("#TV_METHOD_off").click(function() {
        tvUpdateViewsButtons();
    });

    function tvUpdateViewsButtons() {
        if( $("#TV_METHOD_on").attr("checked") )
        {
            if (tv_ps_16)
            {
                $("#TV_BUTTON_START_N_PAGE").parent().parent().show();
                $("#TV_BUTTON_N_PAGES").parent().parent().show();
            }
            else
            {
                $("#TV_BUTTON_START_N_PAGE").parent().show().prev('label').show();
                $("#TV_BUTTON_N_PAGES").parent().show().prev('label').show();
            }
        }
        else
        {
            if (tv_ps_16)
            {
                $("#TV_BUTTON_START_N_PAGE").parent().parent().hide();
                $("#TV_BUTTON_N_PAGES").parent().parent().hide();
            }
            else
            {
                $("#TV_BUTTON_START_N_PAGE").parent().hide().prev('label').hide();
                $("#TV_BUTTON_N_PAGES").parent().hide().prev('label').hide();
            }
        }
    }

    updateViewsSelector();
    $("#TV_VIEWS_BUTTONS_CHECK_on").click(function() {
        updateViewsSelector();
    });
    $("#TV_VIEWS_BUTTONS_CHECK_off").click(function() {
        updateViewsSelector();
    });

    function updateViewsSelector() {
        if( $("#TV_VIEWS_BUTTONS_CHECK_on").attr("checked") )
        {
            if (tv_ps_16)
            {
                $("#TV_VIEWS_BUTTONS").parent().parent().show();
                $("#TV_SELECTED_VIEW").parent().parent().show();
            }
            else
            {
                $("#TV_VIEWS_BUTTONS").parent().show().prev('label').show();
                $("#TV_SELECTED_VIEW").parent().show().prev('label').show();
            }
        }
        else
        {
            if (tv_ps_16)
            {
                $("#TV_VIEWS_BUTTONS").parent().parent().hide();
                $("#TV_SELECTED_VIEW").parent().parent().hide();
            }
            else
            {
                $("#TV_VIEWS_BUTTONS").parent().hide().prev('label').hide();
                $("#TV_SELECTED_VIEW").parent().hide().prev('label').hide();
            }
        }
    }
});