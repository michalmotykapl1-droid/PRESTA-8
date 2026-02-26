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
include_once '../../config/config.inc.php';
require_once dirname(__FILE__) . '/FunWishList.php';
require_once dirname(__FILE__) . '/tvcmswishlist.php';

$context = Context::getContext();
$action = Tools::getValue('action');
$add = (!strcmp($action, 'add') ? 1 : 0);
$delete = (!strcmp($action, 'delete') ? 1 : 0);
$id_wishlist = (int) Tools::getValue('id_wishlist');
$id_product = (int) Tools::getValue('id_product');
$quantity = (int) Tools::getValue('quantity');
if (Tools::getIsset('group')) {
    $id_product_attribute = (int) Product::getIdProductAttributesByIdAttributes($id_product, Tools::getValue('group'));
} else {
    $id_product_attribute = 0;
}

// Instance of module class for translations
$module = new TvcmsWishList();

if (1 == Configuration::get('PS_TOKEN_ENABLE')
    && strcmp(Tools::getToken(false), Tools::getValue('token'))
    && true === $context->customer->isLogged()
) {
    echo $module->l('Invalid token', 'cart');
}
if ($context->customer->isLogged()) {
    if ($id_wishlist && true === FunWishList::exists($id_wishlist, $context->customer->id)) {
        $context->cookie->id_wishlist = (int) $id_wishlist;
    }

    if ((int) $context->cookie->id_wishlist > 0
        && !FunWishList::exists($context->cookie->id_wishlist, $context->customer->id)) {
        $context->cookie->id_wishlist = '';
    }

    if (true === empty($context->cookie->id_wishlist) || false == $context->cookie->id_wishlist) {
        $context->smarty->assign('error', true);
    }

    if (($add || $delete) && false === empty($id_product)) {
        if (!isset($context->cookie->id_wishlist) || '' == $context->cookie->id_wishlist) {
            $wishlist = new FunWishList();
            $wishlist->id_shop = $context->shop->id;
            $wishlist->id_shop_group = $context->shop->id_shop_group;
            $wishlist->default = 1;

            $mod_wishlist = new TvcmsWishList();
            $wishlist->name = $mod_wishlist->default_wishlist_name;
            $wishlist->id_customer = (int) $context->customer->id;
            list($us, $s) = explode(' ', microtime());
            srand($s * $us);
            $wishlist->token = Tools::strtoupper(Tools::substr(sha1(uniqid(rand(), true)
                . _COOKIE_KEY_ . $context->customer->id), 0, 16));
            $wishlist->add();
            $context->cookie->id_wishlist = (int) $wishlist->id;
        }
        if ($add && $quantity) {
            FunWishList::addProduct(
                $context->cookie->id_wishlist,
                $context->customer->id,
                $id_product,
                $id_product_attribute,
                $quantity
            );
            echo (int) Db::getInstance()->getValue('SELECT count(id_wishlist_product) FROM ' . _DB_PREFIX_
                 . 'wishlist w, ' . _DB_PREFIX_ . 'wishlist_product wp where w.id_wishlist = wp.id_wishlist and '
                 . 'w.id_customer=' . (int) $context->customer->id);
            exit;
        } elseif ($delete) {
            FunWishList::removeProduct(
                $context->cookie->id_wishlist,
                $context->customer->id,
                $id_product,
                $id_product_attribute
            );
        }
    }
    $context->smarty->assign('products', FunWishList::getProductByIdCustomer(
        $context->cookie->id_wishlist,
        $context->customer->id,
        $context->language->id,
        null,
        true
    ));
    $context->smarty->assign('link', $context->link);
    $tmp = 'modules/tvcmswishlist/views/templates/front/tvcmswishlist-ajax.tpl';
    if (Tools::file_exists_cache(_PS_THEME_DIR_ . $tmp)) {
        $context->smarty->display(_PS_THEME_DIR_ . $tmp);
    } elseif (Tools::file_exists_cache(dirname(__FILE__) . '/views/templates/front/tvcmswishlist-ajax.tpl')) {
        $context->smarty->display(dirname(__FILE__) . '/views/templates/front/tvcmswishlist-ajax.tpl');
    } else {
        echo $module->l('No template found', 'cart');
    }
} else {
    echo $module->l('You must be logged in to manage your wishlist.', 'cart');
}
