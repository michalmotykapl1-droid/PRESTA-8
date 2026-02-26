<?php
/**
 * 2007-2025 PrestaShop.
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
if (!defined('_PS_VERSION_')) {
    exit;
}

class TvcmsCustomSettingStatus extends Module
{
    public function fieldStatusInformation()
    {
        $result = [];
        // Theme Configuration
        $result['form_1'] = true;

        // TVCMSCUSTOMSETTING_THEME_OPTION = Theme Option
        // TVCMSCUSTOMSETTING_THEME_COLOR_1 = Custom Theme Color
        // TVCMSCUSTOMSETTING_ADD_CONTAINER = Box Layout
        // TVCMSCUSTOMSETTING_BACKGROUND_IMAGE_PATTERN_STATUS = Background Theme
        // TVCMSCUSTOMSETTING_BACKGROUND_COLOR = Back Ground Theme Color
        // TVCMSCUSTOMSETTING_BACKGROUND_PATTERN = BackGround Pattern
        // TVCMSFRONTSIDE_THEME_SETTING_SHOW = Theme Option Status
        $result['all_theme_option_info'] = true;

        $result['theme_background_design'] = true;
        $result['theme_font_design'] = true;

        $result['page_loader'] = true;
        $result['wow_js'] = false;
        $result['animation_css'] = true;
        $result['mouse_hover_image'] = true;
        $result['tab_product_double_row'] = true;
        $result['product_color'] = true;
        $result['product_list_view'] = true;
        $result['main_menu_sticky'] = true;
        $result['show_all'] = true;
        $result['show_all_brand'] = true;
        $result['bottom_sticky'] = false;
        $result['prod_bottom_sticky'] = true;
        $result['support_link'] = true;

        $result['vertical_menu_open'] = false;

        $result['home_pagecategory_show'] = true;
        // App Link
        $result['form_2'] = true;
        $result['app_main_image'] = true;
        $result['app_title'] = true;
        $result['app_sub_title'] = false;
        $result['app_desc'] = true;
        $result['apple_app_link'] = true;
        $result['google_app_link'] = true;
        $result['microsoft_app_link'] = true;
        $result['app_link_status'] = true;

        // Custom Titles
        $result['form_3'] = true;
        $result['copy_right_info'] = true;

        $result['custom_text'] = true;
        $result['custom_text_status'] = true;
        $result['copy_right_text'] = true;
        $result['place_holder_search'] = true;
        $result['copy_right_link'] = true;
        $result['copy_right_text_status'] = true;
        $result['size_guide'] = true;

        $result['form_4'] = true;
        $result['main_form'] = true;
        $result['header_layout'] = true;
        $result['header_layout_mobile'] = true;
        $result['footer_layout_radio'] = true;
        $result['header_product_layout_radio'] = true;
        $result['mobile_search'] = true;

        $result['header_layout_default'] = '1';
        $result['header_layout_list'] = ['1', '2', '3', '4', '5'];

        $result['footer_layout_default'] = '1';
        $result['footer_layout_list'] = ['1', '2', '3', '4'];

        $result['product_layout_default'] = '1';
        $result['product_layout_list'] = ['1', '2', '3', '4', '5', '6'];

        $result['mob_header_layout_default'] = '1';
        $result['mob_header_layout_list'] = ['1', '2', '3'];

        // If You want to off Footer tab product Then off footer_tab_product = false
        // Otherwise off Other Option
        $result['footer_tab_product_info'] = false;

        $result['footer_tab_featured_prod_title'] = false;
        $result['footer_tab_new_prod_title'] = false;
        $result['footer_tab_best_seller_prod_title'] = false;
        $result['footer_tab_num_prod'] = false;
        $result['footer_tab_prod_status'] = false;

        $result['news_letter_title'] = true;
        $result['news_letter_short_desc'] = true;
        $result['footer_img'] = true;
        $result['footer_img_status'] = true;
        $result['rtl_text_status'] = true;
        $result['right_sticky'] = true;
        $result['left_sticky'] = true;
        $result['float_left_column'] = true;

        $result['social_icon_title'] = false;
        $result['social_icon_short_desc'] = false;
        $result['dark_mode'] = true;
        $result['cart_view'] = true;
        $result['tvmain_smart_speed'] = true;

        $result['form_5'] = true;

        $result['import_data_module_list'] = [
        'tvcmsblog',
        'tvcmsbrandlist',
        'tvcmscategorychainslider',
        'tvcmscategoryproduct',
        'tvcmscategoryslider',
        'tvcmsfooterproduct',
        'tvcmscookiesnotice',
        'tvcmscustomerservices',
        'tvcmscustomsetting',
        'tvcmsfooterlogo',
        'tvcmsleftsideofferbanner',
        'tvcmsmegamenu',
        'tvcmsmultibanner1',
        'tvcmsnewsletterpopup',
        'tvcmsofferbanner',
        'tvcmspaymenticon',
        'tvcmssingleblock',
        'tvcmssizechart',
        'tvcmsslider',
        'tvcmssliderofferbanner',
        'tvcmstabproducts',
        'tvcmstestimonial',
        'tvcmstwoofferbanner',
        'tvcmsverticalmenu', ];

        return $result;
    }
}
