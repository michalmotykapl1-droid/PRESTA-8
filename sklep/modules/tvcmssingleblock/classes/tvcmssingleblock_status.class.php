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

class TvcmsSingleBlockStatus extends Module
{
    public function fieldStatusInformation()
    {
        $result = [];

        $result['main_status'] = false;
        $result['main_image'] = false;
        $result['main_title'] = false;
        $result['main_sub_title'] = false;
        $result['main_description'] = false;

        $result['record_form'] = true;
        $result['single_block_image'] = true;
        $result['single_block_image_2'] = true;
        $result['single_block_image_3'] = true;
        $result['single_block_image_4'] = true;
        $result['single_block_title'] = true;
        $result['single_block_sub_title'] = true;
        $result['single_block_description'] = true;
        $result['single_block_btn_caption'] = true;
        $result['single_block_link'] = true;
        $result['single_block_status'] = true;

        return $result;
    }
}
