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
class TvcmsVerticalMenuData
{
    public function initData()
    {
        $return = true;
        $languages = Language::getLanguages(true);
        $id_shop = Configuration::get('PS_SHOP_DEFAULT');

        $return &= Db::getInstance()->Execute('INSERT IGNORE INTO `' . _DB_PREFIX_ . 'tvcmsverticalmenu`
                (`id_tvcmsverticalmenu`, `type_link`, `dropdown`, `type_icon`, `icon`, `align_sub`, 
                    `width_sub`, `class`,`position`, `active`) VALUES
               (1, 0, 0, 0, "", "tvcmsvertical-sub-auto", "", "", 0, 1),
               (2, 1, 0, 0, "", "tvcmsvertical-sub-auto", "column-4", "", 0, 1),
               (3, 1, 0, 0, "", "tvcmsvertical-sub-auto", "column-2", "menu-comectic", 0, 1),
               (4, 1, 0, 0, "", "tvcmsvertical-sub-auto", "", "", 0, 1),
               (5, 1, 0, 0, "", "tvcmsvertical-sub-auto", "", "", 0, 1),
               (6, 1, 0, 0, "", "tvcmsvertical-sub-auto", "", "", 0, 1),
               (7, 1, 0, 0, "", "tvcmsvertical-sub-auto", "", "", 0, 1),
               (8, 1, 0, 0, "", "tvcmsvertical-sub-auto", "", "", 0, 1);');

        $return &= Db::getInstance()->Execute('INSERT IGNORE INTO `' . _DB_PREFIX_ . 'tvcmsverticalmenu_shop`
            (`id_tvcmsverticalmenu`, `id_shop`, `type_link`, `dropdown`, `type_icon`, `icon`, `align_sub`,
                `width_sub`, `class`,`position`, `active`) VALUES
        (1,' . (int) $id_shop . ', 0, 0, 0, "", "tvcmsvertical-sub-auto", "", "", 0, 1),
        (2,' . (int) $id_shop . ', 1, 0, 0, "", "tvcmsvertical-sub-auto", "column-4", "", 0, 1),
        (3,' . (int) $id_shop . ', 1, 0, 0, "", "tvcmsvertical-sub-auto", "column-2", "menu-comectic", 0, 1),
        (4,' . (int) $id_shop . ', 1, 0, 0, "", "tvcmsvertical-sub-auto", "", "", 0, 1),
        (5,' . (int) $id_shop . ', 1, 0, 0, "", "tvcmsvertical-sub-auto", "", "", 0, 1),
        (6,' . (int) $id_shop . ', 1, 0, 0, "", "tvcmsvertical-sub-auto", "", "", 0, 1),
        (7,' . (int) $id_shop . ', 1, 0, 0, "", "tvcmsvertical-sub-auto", "", "", 0, 1),
        (8,' . (int) $id_shop . ', 1, 0, 0, "", "tvcmsvertical-sub-auto", "", "", 0, 1);');

        $return &= Db::getInstance()->Execute('INSERT IGNORE INTO `' . _DB_PREFIX_ . 'tvcmsverticalmenu_row`
            (`id_row`, `id_tvcmsverticalmenu`, `class`, `active`) VALUES
        (1, 2, "", 1);');

        $return &= Db::getInstance()->Execute('INSERT IGNORE INTO `' . _DB_PREFIX_ . 'tvcmsverticalmenu_row_shop`
            (`id_row`, `id_tvcmsverticalmenu`, `id_shop`,`class`, `active`) VALUES
        (1, 2, ' . (int) $id_shop . ', "", 1);');

        $return &= Db::getInstance()->Execute('INSERT IGNORE INTO `' . _DB_PREFIX_ . 'tvcmsverticalmenu_column`
            (`id_column`, `id_row`, `width`, `class`,`position`, `active`) VALUES
        (1, 1, "col-sm-6", "", 0, 1),
        (2, 1, "col-sm-6", "", 0, 1),
        (3, 1, "col-sm-12", "tv-vertical-menu-slider", 0, 1);');

        $return &= Db::getInstance()->Execute('INSERT IGNORE INTO `' . _DB_PREFIX_ . 'tvcmsverticalmenu_column_shop`
            (`id_column`, `id_row`, `id_shop`, `width`, `class`,`position`, `active`) VALUES
        (1, 1, ' . (int) $id_shop . ', "col-sm-6", "", 0, 1),
        (2, 1, ' . (int) $id_shop . ', "col-sm-6", "", 0, 1),
        (3, 1, ' . (int) $id_shop . ', "col-sm-12", "tv-vertical-menu-slider", 0, 1);');

        $return &= Db::getInstance()->Execute('INSERT IGNORE INTO `' . _DB_PREFIX_ . 'tvcmsverticalmenu_item`
            (`id_item`, `id_column`, `type_link`, `type_item`, `id_product`,`position`, `active`) VALUES
        (1, 1, 2, "1", 0, 0, 1),
        (2, 1, 2, "2", 0, 0, 1),
        (3, 1, 2, "2", 0, 0, 1),
        (4, 1, 2, "2", 0, 0, 1),
        (5, 2, 2, "1", 0, 0, 1),
        (6, 2, 2, "2", 0, 0, 1),
        (7, 2, 2, "2", 0, 0, 1),
        (8, 2, 2, "2", 0, 0, 1),
        (9, 3, 4, "1", 1, 0, 1),
        (10, 3, 4, "1", 2, 0, 1),
        (11, 3, 4, "1", 3, 0, 1),
        (12, 3, 4, "1", 2, 0, 1);');

        $return &= Db::getInstance()->Execute('INSERT IGNORE INTO `' . _DB_PREFIX_ . 'tvcmsverticalmenu_item_shop`
            (`id_item`, `id_column`, `id_shop`, `type_link`, `type_item`, `id_product`,`position`, `active`) VALUES
        (1, 1,' . (int) $id_shop . ', 2, "1", 0, 0, 1),
        (2, 1,' . (int) $id_shop . ', 2, "2", 0, 0, 1),
        (3, 1,' . (int) $id_shop . ', 2, "2", 0, 0, 1),
        (4, 1,' . (int) $id_shop . ', 2, "2", 0, 0, 1),
        (5, 2,' . (int) $id_shop . ', 2, "1", 0, 0, 1),
        (6, 2,' . (int) $id_shop . ', 2, "2", 0, 0, 1),
        (7, 2,' . (int) $id_shop . ', 2, "2", 0, 0, 1),
        (8, 2,' . (int) $id_shop . ', 2, "2", 0, 0, 1),
        (9, 3,' . (int) $id_shop . ', 4, "1", 1, 0, 1),
        (10, 3,' . (int) $id_shop . ', 4, "1", 2, 0, 1),
        (11, 3,' . (int) $id_shop . ', 4, "1", 3, 0, 1),
        (12, 3,' . (int) $id_shop . ', 4, "1", 2, 0, 1);');

        foreach ($languages as $language) {
            $return &= Db::getInstance()->Execute('INSERT IGNORE INTO `' . _DB_PREFIX_ . 'tvcmsverticalmenu_lang`
                (`id_tvcmsverticalmenu`, `id_shop`, `id_lang`, `title`, `link`, `subtitle`) VALUES
            (1,' . (int) $id_shop . ',' . (int) $language['id_lang'] . ', "PAGindex", "PAGindex", ""),
            (2,' . (int) $id_shop . ',' . (int) $language['id_lang'] . ', "Fashion", "#", ""),
            (3,' . (int) $id_shop . ',' . (int) $language['id_lang'] . ', "Electronics", "#", "hot"),
            (4,' . (int) $id_shop . ',' . (int) $language['id_lang'] . ', "Computer Components", "#", ""),
            (5,' . (int) $id_shop . ',' . (int) $language['id_lang'] . ', "Travel Cameras", "#", ""),
            (6,' . (int) $id_shop . ',' . (int) $language['id_lang'] . ', "Printers & Accessories", "#", ""),
            (7,' . (int) $id_shop . ',' . (int) $language['id_lang'] . ', "Desktop Computers", "#", ""),
            (8,' . (int) $id_shop . ',' . (int) $language['id_lang'] . ', "Mobile Accessories", "#", "");');

            $return &= Db::getInstance()->Execute('INSERT IGNORE INTO `' . _DB_PREFIX_ . 'tvcmsverticalmenu_item_lang`
                (`id_item`, `id_shop`, `id_lang`, `title`, `link`, `text`) VALUES
            (1,' . (int) $id_shop . ',' . (int) $language['id_lang'] . ', "Fashion & accesories", "#", ""),
            (2,' . (int) $id_shop . ',' . (int) $language['id_lang'] . ', "Fashion & accesories", "#", ""),
            (3,' . (int) $id_shop . ',' . (int) $language['id_lang'] . ', "Fashion & accesories", "#", ""),
            (4,' . (int) $id_shop . ',' . (int) $language['id_lang'] . ', "Fashion & accesories", "#", ""),
            (5,' . (int) $id_shop . ',' . (int) $language['id_lang'] . ', "Fashion & accesories", "#", ""),
            (6,' . (int) $id_shop . ',' . (int) $language['id_lang'] . ', "Fashion & accesories", "#", ""),
            (7,' . (int) $id_shop . ',' . (int) $language['id_lang'] . ', "Fashion & accesories", "#", ""),
            (8,' . (int) $id_shop . ',' . (int) $language['id_lang'] . ', "Fashion & accesories", "#", ""),
            (9,' . (int) $id_shop . ',' . (int) $language['id_lang'] . ', "", "#", ""),
            (10,' . (int) $id_shop . ',' . (int) $language['id_lang'] . ', "", "#", ""),
            (11,' . (int) $id_shop . ',' . (int) $language['id_lang'] . ', "", "#", ""),
            (12,' . (int) $id_shop . ',' . (int) $language['id_lang'] . ', "", "#", "");');
        }

        return $return;
    }
}
