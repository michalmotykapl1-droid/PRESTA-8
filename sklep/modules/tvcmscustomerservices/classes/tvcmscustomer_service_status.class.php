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

class TvcmsCustomerServicesStatus extends Module
{
    public function fieldStatusInformation()
    {
        $result = [];
        $result['main_status'] = true;
        $result['main_title'] = true;
        $result['main_title_left'] = true;
        $result['main_sub_title'] = false;
        $result['main_description'] = false;
        $result['main_image'] = false;
        $result['show_all'] = true;

        $result['record_form'] = true;
        // Number of Services 3 ,4 or 5
        $result['num_services'] = 4;

        // Images is Updload or Not
        // if 1 (one) then Image Upload Field is Show Otherwise 0 (zero) is not;
        $result['image_upload'] = 1;

        return $result;
    }
}
