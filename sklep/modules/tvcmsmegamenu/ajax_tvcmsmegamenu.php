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
include_once '../../init.php';
include_once 'tvcmsmegamenu.php';

$home_menu = new TvcmsMegaMenu();
$menus = [];
$id_shop = (int) Context::getContext()->shop->id;

if (!Tools::isSubmit('secure_key')
    || Tools::getValue('secure_key') != $home_menu->secure_key
    || !Tools::getValue('action')) {
    exit(1);
}

if ('updateMenusPosition' == Tools::getValue('action') && Tools::getValue('menu')) {
    $menus = Tools::getValue('menu');
    foreach ($menus as $position => $id_menu) {
        Db::getInstance()->execute('
            UPDATE `' . _DB_PREFIX_ . 'tvcmsmegamenu_shop` SET `position` = ' . (int) $position . '
            WHERE `id_tvcmsmegamenu` = ' . (int) $id_menu . ' AND id_shop = ' . (int) $id_shop);
    }
    $home_menu->clearCache();
}
if ('updateMenusItemPosition' == Tools::getValue('action') && Tools::getValue('menuitem')) {
    $menu_items = Tools::getValue('menuitem');
    foreach ($menu_items as $position => $id_menuitem) {
        Db::getInstance()->execute('
            UPDATE `' . _DB_PREFIX_ . 'tvcmsmegamenu_item_shop` SET `position` = ' . (int) $position . '
            WHERE `id_item` = ' . (int) $id_menuitem . ' AND id_shop = ' . (int) $id_shop);
    }
    $home_menu->clearCache();
}

if ('updateColumnsPosition' == Tools::getValue('action') && Tools::getValue('col')) {
    $col_items = Tools::getValue('col');
    foreach ($col_items as $position => $id_col) {
        Db::getInstance()->execute('
            UPDATE `' . _DB_PREFIX_ . 'tvcmsmegamenu_column_shop` SET `position` = ' . (int) $position . '
            WHERE `id_column` = ' . (int) $id_col . ' AND id_shop = ' . (int) $id_shop);
    }
    $home_menu->clearCache();
}
