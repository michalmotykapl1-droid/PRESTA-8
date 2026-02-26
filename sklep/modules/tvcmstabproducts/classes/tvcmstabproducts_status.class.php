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

class TvcmsTabProductsStatus extends Module
{
    public function fieldStatusInformation()
    {
        $result = [];

        // Main Title Information
        $result['main_status'] = true;
        $result['main_title'] = true;
        $result['main_sub_title'] = false;
        $result['main_description'] = true;
        $result['main_image'] = true;
        $result['main_image_side'] = true;
        $result['main_image_status'] = true;

        // Featured Product Form
        $result['featured_prod']['main_status'] = true;
        $result['featured_prod']['tab_title'] = true;
        $result['featured_prod']['home_title'] = true;
        $result['featured_prod']['display_all'] = true;
        $result['featured_prod']['home_sub_title'] = false;
        $result['featured_prod']['home_description'] = true;
        $result['featured_prod']['left_title'] = true;
        $result['featured_prod']['right_title'] = true;

        $result['featured_prod']['home_image'] = true;
        $result['featured_prod']['home_image_side'] = true;
        $result['featured_prod']['home_image_status'] = true;

        $result['featured_prod']['num_of_prod'] = true;
        $result['featured_prod']['prod_cat'] = true;
        $result['featured_prod']['random_prod'] = true;
        $result['featured_prod']['display_in_tab'] = true;
        $result['featured_prod']['display_in_home'] = true;
        $result['featured_prod']['display_in_left'] = true;
        $result['featured_prod']['display_in_right'] = true;

        // New Product form
        $result['new_prod']['main_status'] = true;
        $result['new_prod']['tab_title'] = true;
        $result['new_prod']['home_title'] = true;
        $result['new_prod']['home_sub_title'] = false;
        $result['new_prod']['home_description'] = true;
        $result['new_prod']['left_title'] = true;
        $result['new_prod']['right_title'] = true;

        $result['new_prod']['home_image'] = true;
        $result['new_prod']['home_image_side'] = true;
        $result['new_prod']['home_image_status'] = true;

        $result['new_prod']['num_of_prod'] = true;
        $result['new_prod']['num_of_days'] = true;
        $result['new_prod']['display_in_tab'] = true;
        $result['new_prod']['display_in_all'] = true;
        $result['new_prod']['display_in_home'] = true;
        $result['new_prod']['display_in_left'] = true;
        $result['new_prod']['display_in_right'] = true;

        // Best Seller Product Form
        $result['best_seller_prod']['main_status'] = true;
        $result['best_seller_prod']['tab_title'] = true;
        $result['best_seller_prod']['home_title'] = true;
        $result['best_seller_prod']['home_sub_title'] = false;
        $result['best_seller_prod']['home_description'] = true;
        $result['best_seller_prod']['left_title'] = true;
        $result['best_seller_prod']['right_title'] = true;

        $result['best_seller_prod']['display_in_all'] = true;

        $result['best_seller_prod']['home_image'] = true;
        $result['best_seller_prod']['home_image_side'] = true;
        $result['best_seller_prod']['home_image_status'] = true;

        $result['best_seller_prod']['num_of_prod'] = true;
        $result['best_seller_prod']['display_in_tab'] = true;
        $result['best_seller_prod']['display_in_home'] = true;
        $result['best_seller_prod']['display_in_left'] = true;
        $result['best_seller_prod']['display_in_right'] = true;

        // Special Product Form
        $result['special_prod']['main_status'] = true;
        $result['special_prod']['tab_title'] = true;
        $result['special_prod']['home_title'] = true;
        $result['special_prod']['home_sub_title'] = false;
        $result['special_prod']['home_description'] = true;
        $result['special_prod']['left_title'] = true;
        $result['special_prod']['right_title'] = true;

        $result['special_prod']['home_image'] = true;
        $result['special_prod']['home_image_side'] = true;
        $result['special_prod']['home_image_status'] = true;

        $result['special_prod']['num_of_prod'] = true;
        $result['special_prod']['display_in_tab'] = true;
        $result['special_prod']['display_all'] = true;
        $result['special_prod']['display_in_home'] = true;
        $result['special_prod']['display_in_left'] = true;
        $result['special_prod']['display_in_right'] = true;

        return $result;
    }
}
