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
 *  @author PrestaShop SA <contact@prestashop.com>
 *  @copyright  2007-2025 PrestaShop SA
 *  @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *  International Registered Trademark & Property of PrestaShop SA
 */
require_once dirname(__FILE__) . '/../../config/config.inc.php';
require_once dirname(__FILE__) . '/../../init.php';
require_once dirname(__FILE__) . '/tvcmsproductcompare.php';

$context = Context::getContext();

// Instance of module class for translations
$module = new TvcmsProductCompare();
$tmp = Tools::getToken(false);
$tmp_2 = Tools::getValue('token');

if (1 == Configuration::get('PS_TOKEN_ENABLE')
    and strcmp($tmp, $tmp_2)) {
    exit($module->l('invalid token', 'ajax'));
}

$context = Context::getContext();

$return_data = [];

if (Tools::getValue('id_product')) {
    if ('add' == Tools::getValue('comp_val')) {
        if ('' == $context->cookie->__get('tvcmscompare_product_1')) {
            $context->cookie->__set('tvcmscompare_product_1', Tools::getValue('id_product'));
            $return_data['notice'] = 'add_compare_prod';
            $return_data['full_notic'] = $module->l('The product has been added to product comparison', 'ajax');
        } elseif ('' == $context->cookie->__get('tvcmscompare_product_2')) {
            $context->cookie->__set('tvcmscompare_product_2', Tools::getValue('id_product'));
            $return_data['notice'] = 'add_compare_prod';
            $return_data['full_notic'] = $module->l('The product has been added to product comparison', 'ajax');
        } elseif ('' == $context->cookie->__get('tvcmscompare_product_3')) {
            $context->cookie->__set('tvcmscompare_product_3', Tools::getValue('id_product'));
            $return_data['notice'] = 'add_compare_prod';
            $return_data['full_notic'] = $module->l('The product has been added to product comparison', 'ajax');
        } elseif ('' == $context->cookie->__get('tvcmscompare_product_4')) {
            $context->cookie->__set('tvcmscompare_product_4', Tools::getValue('id_product'));
            $return_data['notice'] = 'add_compare_prod';
            $return_data['full_notic'] = $module->l('The product has been added to product comparison', 'ajax');
        } else {
            $return_data['notice'] = 'full_compare_prod';
            $obj = $module->l('You cannot add more than 4 product(s) to the product comparison', 'ajax');
            $return_data['full_notic'] = $obj;
        }
    } else {
        if (Tools::getValue('id_product') == $context->cookie->__get('tvcmscompare_product_1')) {
            $context->cookie->__unset('tvcmscompare_product_1');
            $context->cookie->__set('tvcmscompare_product_1', '');
            $return_data['notice'] = 'product_remove';
            $obj = $module->l('The product has been removed from the product comparison.', 'ajax');
            $return_data['full_notice'] = $obj;
        }
        if (Tools::getValue('id_product') == $context->cookie->__get('tvcmscompare_product_2')) {
            $context->cookie->__unset('tvcmscompare_product_2');
            $context->cookie->__set('tvcmscompare_product_2', '');
            $return_data['notice'] = 'product_remove';
            $obj = $module->l('The product has been removed from the product comparison.', 'ajax');
            $return_data['full_notice'] = $obj;
        }
        if (Tools::getValue('id_product') == $context->cookie->__get('tvcmscompare_product_3')) {
            $context->cookie->__unset('tvcmscompare_product_3');
            $context->cookie->__set('tvcmscompare_product_3', '');
            $return_data['notice'] = 'product_remove';
            $obj = $module->l('The product has been removed from the product comparison.', 'ajax');
            $return_data['full_notice'] = $obj;
        }
        if (Tools::getValue('id_product') == $context->cookie->__get('tvcmscompare_product_4')) {
            $context->cookie->__unset('tvcmscompare_product_4');
            $context->cookie->__set('tvcmscompare_product_4', '');
            $return_data['notice'] = 'product_remove';
            $obj = $module->l('The product has been removed from the product comparison.', 'ajax');
            $return_data['full_notice'] = $obj;
        }
    }

    $tmp = 0;
    if ('' != $context->cookie->__get('tvcmscompare_product_1')) {
        ++$tmp;
    }

    if ('' != $context->cookie->__get('tvcmscompare_product_2')) {
        ++$tmp;
    }

    if ('' != $context->cookie->__get('tvcmscompare_product_3')) {
        ++$tmp;
    }

    if ('' != $context->cookie->__get('tvcmscompare_product_4')) {
        ++$tmp;
    }
    $return_data['tot_comp_prod'] = $tmp;
}
echo $text = implode('##', $return_data);
