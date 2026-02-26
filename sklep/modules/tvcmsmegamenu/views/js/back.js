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
 *  @author PrestaShop SA <contact@prestashop.com>
 *  @copyright  2007-2025 PrestaShop SA
 *  @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *  International Registered Trademark & Property of PrestaShop SA
 */

$(document).ready(function() {
    $(document).on('click', '.tvcms-delete-menu', function() {
        if (confirm('Are you sure want to delete menu?')) {
            var url = $(this).attr('href');
            window.location.href = url;
        }
    });
    $("#type_link_custom").click(function() {
        $('.ps_link').css('display', 'none');
        $('.show_sub').css('display', 'none');
        $('.tv-menu-title').css('display', 'block');
        $('.tv-menu-link').css('display', 'block');
    });

    $("#type_link_ps").click(function() {
        $('.ps_link').css('display', 'block');
        $('.show_sub').css('display', 'block');
        $('.tv-menu-title').css('display', 'none');
        $('.tv-menu-link').css('display', 'none');
    });

    $("#type_icon_fw").click(function() {
        $('.tv-img-icon').css('display', 'none');
        $('.tv-fw-icon').css('display', 'block');
    });

    $("#type_icon_img").click(function() {
        $('.tv-img-icon').css('display', 'block');
        $('.tv-fw-icon').css('display', 'none');
    });

    $("#type_link").change(function() {
        var type_val = $(this).val();
        if (type_val == 2) {
            $('.tv-menu-title').parent('.form-group').show();
            $('.tv-menu-link').parent('.form-group').show();
            $('.ps_link').parent('.form-group').hide();
            $('.tv-menu-text').parent('.form-group').hide();
            $('.tv-menu-product').parent('.form-group').hide();
            $('.ps_link').parent('.form-group').removeClass('hide-ps-link');
        } else if (type_val == 3) {
            $('.tv-menu-title').parent('.form-group').show();
            $('.tv-menu-link').parent('.form-group').show();
            $('.ps_link').parent('.form-group').hide();
            $('.tv-menu-text').parent('.form-group').show();
            $('.tv-menu-product').parent('.form-group').hide();
            $('.ps_link').parent('.form-group').removeClass('hide-ps-link');
        } else if (type_val == 4) {
            $('.tv-menu-product').parent('.form-group').show();
            $('.tv-menu-title').parent('.form-group').hide();
            $('.tv-menu-link').parent('.form-group').hide();
            $('.tv-menu-text').parent('.form-group').hide();
            $('.ps_link').parent('.form-group').hide();
            $('.ps_link').parent('.form-group').addClass('hide-ps-link');
        } else {
            $('.tv-menu-title').parent('.form-group').hide();
            $('.tv-menu-link').parent('.form-group').hide();
            $('.ps_link').parent('.form-group').show();
            $('.tv-menu-text').parent('.form-group').hide();
            $('.tv-menu-product').parent('.form-group').hide();
            $('.ps_link').parent('.form-group').removeClass('hide-ps-link');
        }
    });
});