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
class SampleDataMenu
{
    // V4.0.0 Sample Data
    public function initData()
    {
        $return = true;
        $languages = Language::getLanguages(true);
        $id_shop = Configuration::get('PS_SHOP_DEFAULT');
        $imgpath = _MODULE_DIR_ . 'tvcmsmegamenu/views/img';

        $return &= Db::getInstance()->Execute('INSERT IGNORE INTO 
                `' . _DB_PREFIX_ . 'tvcmsmegamenu` 
                (`id_tvcmsmegamenu`, `type_link`, `dropdown`, `type_icon`, `icon`, '
                    . '`background_img`, `align_sub`, `width_sub`, `class`, `position`, `active`,'
                    . ' `active_title`, `width_icon`, `height_icon`) VALUES 
                (1, 1, 0, 0, "1.png", "", "tv-sub-auto", "col-sm-6", "", 0, 1, 0, "101", "23"),
                (2, 1, 0, 0, "2.png", "", "tv-sub-auto", "col-sm-9", "", 0, 1, 1, "20", "23"),
                (3, 1, 0, 0, "", "", "tv-sub-auto", "col-sm-2", "", 0, 1, 1, "", ""),
                (4, 1, 0, 0, "", "", "tv-sub-auto", "col-sm-6", "", 0, 1, 1, "", ""),
                (5, 1, 0, 0, "", "", "tv-sub-auto", "col-sm-5", "", 0, 1, 1, "", ""),
                (6, 1, 0, 0, "", "", "tv-sub-auto", "col-sm-5", "", 0, 1, 1, "", ""),
                (7, 1, 0, 0, "", "", "tv-sub-auto", "col-sm-2", "", 0, 1, 1, "", "");');

        $return &= Db::getInstance()->Execute('INSERT IGNORE INTO 
            `' . _DB_PREFIX_ . 'tvcmsmegamenu_shop` 
            (`id_tvcmsmegamenu`, `id_shop`, `type_link`, `dropdown`, `type_icon`, `icon`, '
                . '`background_img`, `align_sub`, `width_sub`, `class`, `position`, `active`, '
                . ' `active_title`, `width_icon`, `height_icon`) VALUES 
                (1, ' . (int) $id_shop . ', 1, 0, 0, "1.png", "", "tv-sub-auto", "col-sm-6", "", 0, 1, 0, "101", "23"),
                (2, ' . (int) $id_shop . ', 1, 0, 0, "2.png", "", "tv-sub-auto", "col-sm-9", "", 0, 1, 1, "20", "23"),
                (3, ' . (int) $id_shop . ', 1, 0, 0, "", "", "tv-sub-auto", "col-sm-2", "", 0, 1, 1, "", ""),
                (4, ' . (int) $id_shop . ', 1, 0, 0, "", "", "tv-sub-auto", "col-sm-6", "", 0, 1, 1, "", ""),
                (5, ' . (int) $id_shop . ', 1, 0, 0, "", "", "tv-sub-auto", "col-sm-5", "", 0, 1, 1, "", ""),
                (6, ' . (int) $id_shop . ', 1, 0, 0, "", "", "tv-sub-auto", "col-sm-5", "", 0, 1, 1, "", ""),
                (7, ' . (int) $id_shop . ', 1, 0, 0, "", "", "tv-sub-auto", "col-sm-2", "", 0, 1, 1, "", "");');

        $return &= Db::getInstance()->Execute('INSERT IGNORE INTO
            `' . _DB_PREFIX_ . 'tvcmsmegamenu_row` (`id_row`, `id_tvcmsmegamenu`, `class`, `active`) VALUES 
            (1, 1, "", 1),
            (2, 2, "", 1),
            (3, 3, "", 1),
            (4, 4, "", 1),
            (5, 5, "", 1),
            (6, 6, "", 1),
            (7, 7, "", 1),
            (8, 4, "", 1);');

        $return &= Db::getInstance()->Execute('INSERT IGNORE INTO 
            `' . _DB_PREFIX_ . 'tvcmsmegamenu_row_shop` (`id_row`, `id_tvcmsmegamenu`, `id_shop`, `class`,'
                 . ' `active`) VALUES 
            (1, 1, ' . (int) $id_shop . ', "", 1),
            (2, 2, ' . (int) $id_shop . ', "", 1),
            (3, 3, ' . (int) $id_shop . ', "", 1),
            (4, 4, ' . (int) $id_shop . ', "", 1),
            (5, 5, ' . (int) $id_shop . ', "", 1),
            (6, 6, ' . (int) $id_shop . ', "", 1),
            (7, 7, ' . (int) $id_shop . ', "", 1),
            (8, 4, ' . (int) $id_shop . ', "", 1);');

        $return &= Db::getInstance()->Execute('INSERT IGNORE INTO 
            `' . _DB_PREFIX_ . 'tvcmsmegamenu_column` (`id_column`, `id_row`, `width`, `class`,`position`, `active`) VALUES 
            (1, 1, "col-sm-12", "", 0, 1),
            (2, 2, "col-sm-2", "", 0, 1),
            (3, 2, "col-sm-2", "", 0, 1),
            (4, 2, "col-sm-8", "", 0, 1),
            (5, 3, "col-sm-12", "", 0, 1),
            (6, 4, "col-sm-4", "", 0, 1),
            (7, 4, "col-sm-4", "", 0, 1),
            (8, 4, "col-sm-4", "", 0, 1),
            (9, 4, "col-sm-12", "", 0, 1),
            (10, 5, "col-sm-12", "tv-mega-menu-slider", 0, 1),
            (11, 6, "col-sm-4", "", 0, 1),
            (12, 6, "col-sm-4", "", 0, 1),
            (13, 6, "col-sm-4", "", 0, 1),
            (14, 6, "col-sm-4", "", 0, 1),
            (15, 6, "col-sm-4", "", 0, 1),
            (16, 6, "col-sm-4", "", 0, 1),
            (17, 7, "col-sm-12", "", 0, 1),
            (18, 8, "col-sm-12", "", 0, 1);');

        $return &= Db::getInstance()->Execute('INSERT IGNORE INTO 
            `' . _DB_PREFIX_ . 'tvcmsmegamenu_column_shop` (`id_column`, `id_row`, `id_shop`, `width`, '
                 . '`class`,`position`, `active`) VALUES 
        (1, 1, ' . (int) $id_shop . ', "col-sm-12", "", 0, 1),
        (2, 2, ' . (int) $id_shop . ', "col-sm-2", "", 0, 1),
        (3, 2, ' . (int) $id_shop . ', "col-sm-2", "", 0, 1),
        (4, 2, ' . (int) $id_shop . ', "col-sm-8", "", 0, 1),
        (5, 3, ' . (int) $id_shop . ', "col-sm-12", "", 0, 1),
        (6, 4, ' . (int) $id_shop . ', "col-sm-4", "", 0, 1),
        (7, 4, ' . (int) $id_shop . ', "col-sm-4", "", 0, 1),
        (8, 4, ' . (int) $id_shop . ', "col-sm-4", "", 0, 1),
        (9, 4, ' . (int) $id_shop . ', "col-sm-12", "", 0, 1),
        (10, 5, ' . (int) $id_shop . ', "col-sm-12", "tv-mega-menu-slider", 0, 1),
        (11, 6, ' . (int) $id_shop . ', "col-sm-4", "", 0, 1),
        (12, 6, ' . (int) $id_shop . ', "col-sm-4", "", 0, 1),
        (13, 6, ' . (int) $id_shop . ', "col-sm-4", "", 0, 1),
        (14, 6, ' . (int) $id_shop . ', "col-sm-4", "", 0, 1),
        (15, 6, ' . (int) $id_shop . ', "col-sm-4", "", 0, 1),
        (16, 6, ' . (int) $id_shop . ', "col-sm-4", "", 0, 1),
        (17, 7, ' . (int) $id_shop . ', "col-sm-12", "", 0, 1),
        (18, 8, ' . (int) $id_shop . ', "col-sm-12", "", 0, 1);');

        $sql = 'SELECT `id_product` FROM `' . _DB_PREFIX_ . 'product` WHERE `active` = 1 LIMIT 1';
        $result = Db::getInstance()->ExecuteS($sql);
        $results = implode('|', $result['0']);
        $return &= Db::getInstance()->Execute('INSERT IGNORE INTO 
            `' . _DB_PREFIX_ . 'tvcmsmegamenu_item` (`id_item`, `id_column`, `type_link`, `type_item`, '
                 . '`id_product`,`position`, `active`) VALUES 

        (1, 1, 3, "1", 0, 0, 1),
        (2, 1, 3, "1", 0, 0, 1),
        (3, 2, 2, "1", 0, 0, 1),
        (4, 2, 2, "2", 0, 0, 1),
        (6, 2, 2, "2", 0, 0, 1),
        (7, 2, 2, "2", 0, 0, 1),
        (8, 2, 2, "2", 0, 0, 1),
        (9, 2, 2, "2", 0, 0, 1),
        (10, 2, 2, "2", 0, 0, 1),
        (11, 2, 2, "2", 0, 0, 1),
        (12, 3, 2, "1", 0, 0, 1),
        (13, 3, 2, "2", 0, 0, 1),
        (14, 3, 2, "2", 0, 0, 1),
        (15, 3, 2, "2", 0, 0, 1),
        (16, 3, 2, "2", 0, 0, 1),
        (17, 3, 2, "2", 0, 0, 1),
        (18, 3, 2, "2", 0, 0, 1),
        (19, 3, 2, "2", 0, 0, 1),
        (20, 3, 2, "2", 0, 0, 1),
        (21, 4, 3, "1", 0, 0, 1),
        (22, 5, 2, "2", 0, 0, 1),
        (23, 5, 2, "2", 0, 0, 1),
        (24, 5, 2, "2", 0, 0, 1),
        (25, 5, 2, "2", 0, 0, 1),
        (26, 5, 2, "2", 0, 0, 1),
        (27, 6, 2, "1", 0, 0, 1),
        (28, 6, 2, "2", 0, 0, 1),
        (29, 6, 2, "2", 0, 0, 1),
        (30, 6, 2, "2", 0, 0, 1),
        (31, 6, 2, "2", 0, 0, 1),
        (32, 6, 2, "2", 0, 0, 1),
        (33, 7, 2, "1", 0, 0, 1),
        (34, 7, 2, "2", 0, 0, 1),
        (35, 7, 2, "2", 0, 0, 1),
        (36, 7, 2, "2", 0, 0, 1),
        (37, 7, 2, "2", 0, 0, 1),
        (38, 7, 2, "2", 0, 0, 1),
        (39, 8, 2, "1", 0, 0, 1),
        (40, 8, 2, "2", 0, 0, 1),
        (41, 8, 2, "2", 0, 0, 1),
        (42, 8, 2, "2", 0, 0, 1),
        (43, 8, 2, "2", 0, 0, 1),
        (44, 8, 2, "2", 0, 0, 1),
        (46, 10, 4, "1", ' . (int) $results . ', 0, 1),
        (47, 10, 4, "1", ' . (int) $results . ', 0, 1),
        (48, 10, 4, "1", ' . (int) $results . ', 0, 1),
        (49, 10, 4, "1", ' . (int) $results . ', 0, 1),
        (50, 11, 3, "1", 0, 0, 1),
        (51, 12, 3, "1", 0, 0, 1),
        (52, 13, 3, "1", 0, 0, 1),
        (53, 14, 3, "1", 0, 0, 1),
        (54, 15, 3, "1", 0, 0, 1),
        (55, 16, 3, "1", 0, 0, 1),
        (56, 17, 2, "2", 0, 0, 1),
        (57, 17, 2, "2", 0, 0, 1),
        (58, 17, 2, "2", 0, 0, 1),
        (59, 17, 2, "2", 0, 0, 1),
        (60, 17, 2, "2", 0, 0, 1),
        (61, 17, 2, "2", 0, 0, 1),
        (62, 18, 3, "1", 0, 0, 1);');

        $return &= Db::getInstance()->Execute('INSERT IGNORE INTO 
            `' . _DB_PREFIX_ . 'tvcmsmegamenu_item_shop` (`id_item`, `id_column`, `id_shop`, `type_link`, '
                 . '`type_item`, `id_product`,`position`, `active`) VALUES 
            (1, 1, ' . (int) $id_shop . ', 3, "1", 0, 0, 1),
            (2, 1, ' . (int) $id_shop . ', 3, "1", 0, 0, 1),
            (3, 2, ' . (int) $id_shop . ', 2, "1", 0, 0, 1),
            (4, 2, ' . (int) $id_shop . ', 2, "2", 0, 0, 1),
            (6, 2, ' . (int) $id_shop . ', 2, "2", 0, 0, 1),
            (7, 2, ' . (int) $id_shop . ', 2, "2", 0, 0, 1),
            (8, 2, ' . (int) $id_shop . ', 2, "2", 0, 0, 1),
            (9, 2, ' . (int) $id_shop . ', 2, "2", 0, 0, 1),
            (10, 2, ' . (int) $id_shop . ', 2, "2", 0, 0, 1),
            (11, 2, ' . (int) $id_shop . ', 2, "2", 0, 0, 1),
            (12, 3, ' . (int) $id_shop . ', 2, "1", 0, 0, 1),
            (13, 3, ' . (int) $id_shop . ', 2, "2", 0, 0, 1),
            (14, 3, ' . (int) $id_shop . ', 2, "2", 0, 0, 1),
            (15, 3, ' . (int) $id_shop . ', 2, "2", 0, 0, 1),
            (16, 3, ' . (int) $id_shop . ', 2, "2", 0, 0, 1),
            (17, 3, ' . (int) $id_shop . ', 2, "2", 0, 0, 1),
            (18, 3, ' . (int) $id_shop . ', 2, "2", 0, 0, 1),
            (19, 3, ' . (int) $id_shop . ', 2, "2", 0, 0, 1),
            (20, 3, ' . (int) $id_shop . ', 2, "2", 0, 0, 1),
            (21, 4, ' . (int) $id_shop . ', 3, "1", 0, 0, 1),
            (22, 5, ' . (int) $id_shop . ', 2, "2", 0, 0, 1),
            (23, 5, ' . (int) $id_shop . ', 2, "2", 0, 0, 1),
            (24, 5, ' . (int) $id_shop . ', 2, "2", 0, 0, 1),
            (25, 5, ' . (int) $id_shop . ', 2, "2", 0, 0, 1),
            (26, 5, ' . (int) $id_shop . ', 2, "2", 0, 0, 1),
            (27, 6, ' . (int) $id_shop . ', 2, "1", 0, 0, 1),
            (28, 6, ' . (int) $id_shop . ', 2, "2", 0, 0, 1),
            (29, 6, ' . (int) $id_shop . ', 2, "2", 0, 0, 1),
            (30, 6, ' . (int) $id_shop . ', 2, "2", 0, 0, 1),
            (31, 6, ' . (int) $id_shop . ', 2, "2", 0, 0, 1),
            (32, 6, ' . (int) $id_shop . ', 2, "2", 0, 0, 1),
            (33, 7, ' . (int) $id_shop . ', 2, "1", 0, 0, 1),
            (34, 7, ' . (int) $id_shop . ', 2, "2", 0, 0, 1),
            (35, 7, ' . (int) $id_shop . ', 2, "2", 0, 0, 1),
            (36, 7, ' . (int) $id_shop . ', 2, "2", 0, 0, 1),
            (37, 7, ' . (int) $id_shop . ', 2, "2", 0, 0, 1),
            (38, 7, ' . (int) $id_shop . ', 2, "2", 0, 0, 1),
            (39, 8, ' . (int) $id_shop . ', 2, "1", 0, 0, 1),
            (40, 8, ' . (int) $id_shop . ', 2, "2", 0, 0, 1),
            (41, 8, ' . (int) $id_shop . ', 2, "2", 0, 0, 1),
            (42, 8, ' . (int) $id_shop . ', 2, "2", 0, 0, 1),
            (43, 8, ' . (int) $id_shop . ', 2, "2", 0, 0, 1),
            (44, 8, ' . (int) $id_shop . ', 2, "2", 0, 0, 1),
            (46, 10, ' . (int) $id_shop . ', 4, "1", ' . (int) $results . ', 0, 1),
            (47, 10, ' . (int) $id_shop . ', 4, "1", ' . (int) $results . ', 0, 1),
            (48, 10, ' . (int) $id_shop . ', 4, "1", ' . (int) $results . ', 0, 1),
            (49, 10, ' . (int) $id_shop . ', 4, "1", ' . (int) $results . ', 0, 1),
            (50, 11, ' . (int) $id_shop . ', 3, "1", 0, 0, 1),
            (51, 12, ' . (int) $id_shop . ', 3, "1", 0, 0, 1),
            (52, 13, ' . (int) $id_shop . ', 3, "1", 0, 0, 1),
            (53, 14, ' . (int) $id_shop . ', 3, "1", 0, 0, 1),
            (54, 15, ' . (int) $id_shop . ', 3, "1", 0, 0, 1),
            (55, 16, ' . (int) $id_shop . ', 3, "1", 0, 0, 1),
            (56, 17, ' . (int) $id_shop . ', 2, "2", 0, 0, 1),
            (57, 17, ' . (int) $id_shop . ', 2, "2", 0, 0, 1),
            (58, 17, ' . (int) $id_shop . ', 2, "2", 0, 0, 1),
            (59, 17, ' . (int) $id_shop . ', 2, "2", 0, 0, 1),
            (60, 17, ' . (int) $id_shop . ', 2, "2", 0, 0, 1),
            (61, 17, ' . (int) $id_shop . ', 2, "2", 0, 0, 1),
            (62, 18, ' . (int) $id_shop . ', 3, "1", 0, 0, 1);');

        foreach ($languages as $language) {
            $return &= Db::getInstance()->Execute('INSERT IGNORE INTO 
                `' . _DB_PREFIX_ . 'tvcmsmegamenu_lang` (`id_tvcmsmegamenu`, `id_shop`, `id_lang`, `title`, '
                     . '`link`, `subtitle`, `sub_title_stylesheet`) VALUES 
                    (1,' . (int) $id_shop . ', ' . (int) $language['id_lang'] . ' , "one plus", "#", "", ""),
                    (2,' . (int) $id_shop . ', ' . (int) $language['id_lang'] . ' , "iPhone", "#", "NEW", "background:#d90244;"),
                    (3,' . (int) $id_shop . ', ' . (int) $language['id_lang'] . ' , "Shop", "#", "", ""),
                    (4,' . (int) $id_shop . ', ' . (int) $language['id_lang'] . ' , "Electronic", "#", "", ""),
                    (5,' . (int) $id_shop . ', ' . (int) $language['id_lang'] . ' , "OFFERS", "#", "", ""),
                    (6, ' . (int) $id_shop . ', ' . (int) $language['id_lang'] . ', "BRANDS", "#", "", ""),
                    (7,' . (int) $id_shop . ', ' . (int) $language['id_lang'] . ' , "FEATURES", "#", "", "");');

            $return &= Db::getInstance()->Execute('INSERT IGNORE INTO
                `' . _DB_PREFIX_ . 'tvcmsmegamenu_item_lang` (`id_item`, `id_shop`, `id_lang`, `title`, `link`, `text`)
                VALUES 
                    (1, ' . (int) $id_shop . ',' . (int) $language['id_lang'] . ', "", "#", "<div class=\"col-xs-12 col-sm-4\"><a href=\"#\"><img src=\"' . pSQL($imgpath) . '/sample/2.jpg\" width=\"196\" height=\"177\" loading=\"lazy\" alt=\"OnePlus Warp Charge\" /><span>OnePlus Warp Charge</span></a></div>\r\n<div class=\"col-xs-12 col-sm-4\"><a href=\"#\"><img src=\"' . pSQL($imgpath) . '/sample/1.jpg\" width=\"196\" height=\"177\" loading=\"lazy\" alt=\" OnePlus Buds Pro\" /> <span>OnePlus Buds Pro</span></a></div>\r\n<div class=\"col-xs-12 col-sm-4\"><a href=\"#\"><img src=\"' . pSQL($imgpath) . '/sample/3.jpg\" width=\"196\" height=\"177\" loading=\"lazy\" alt=\" Type-C OTG Cable\" /><span>Type-C OTG Cable</span></a></div>"),
                    (2, ' . (int) $id_shop . ',' . (int) $language['id_lang'] . ', "", "#", "<div class=\"html-block\">\r\n<div class=\"col-xs-12 col-sm-4\"><a href=\"#\"><img src=\"' . pSQL($imgpath) . '/sample/4.jpg\" width=\"196\" height=\"177\" loading=\"lazy\" alt=\" OnePlus Gaming Triggers\" /><span>OnePlus Gaming Triggers</span></a></div>\r\n<div class=\"col-xs-12 col-sm-4\"><a href=\"#\"><img src=\"' . pSQL($imgpath) . '/sample/5.jpg\" width=\"196\" height=\"177\" loading=\"lazy\" alt=\"OnePlus Watch Midnight\" /><span>OnePlus Watch Midnight</span></a></div>\r\n<div class=\"col-xs-12 col-sm-4\"><a href=\"#\"><img src=\"' . pSQL($imgpath) . '/sample/6.jpg\" width=\"196\" height=\"177\" loading=\"lazy\" alt=\" OnePlus Nord Handy\" /><span>OnePlus Nord Handy</span></a></div>\r\n</div>"),
                    (3, ' . (int) $id_shop . ',' . (int) $language['id_lang'] . ', "Apple Store", "#", ""),
                    (4, ' . (int) $id_shop . ',' . (int) $language['id_lang'] . ', "iPhone 13 pro", "#", ""),
                    (6, ' . (int) $id_shop . ',' . (int) $language['id_lang'] . ', "iPhone 13 pro", "#", ""),
                    (7, ' . (int) $id_shop . ',' . (int) $language['id_lang'] . ', "iPhone 13 pro", "#", ""),
                    (8, ' . (int) $id_shop . ',' . (int) $language['id_lang'] . ', "iPhone 13 pro", "#", ""),
                    (9, ' . (int) $id_shop . ',' . (int) $language['id_lang'] . ', "iPhone 13 pro", "#", ""),
                    (10, ' . (int) $id_shop . ',' . (int) $language['id_lang'] . ', "iPhone 13 pro", "#", ""),
                    (11, ' . (int) $id_shop . ',' . (int) $language['id_lang'] . ', "iPhone 13 pro", "#", ""),
                    (12, ' . (int) $id_shop . ',' . (int) $language['id_lang'] . ', "Accessories", "#", ""),
                    (13, ' . (int) $id_shop . ',' . (int) $language['id_lang'] . ', "AirTag Loop", "#", ""),
                    (14, ' . (int) $id_shop . ',' . (int) $language['id_lang'] . ', "AirTag Loop", "#", ""),
                    (15, ' . (int) $id_shop . ',' . (int) $language['id_lang'] . ', "AirTag Loop", "#", ""),
                    (16, ' . (int) $id_shop . ',' . (int) $language['id_lang'] . ', "AirTag Loop", "#", ""),
                    (17, ' . (int) $id_shop . ',' . (int) $language['id_lang'] . ', "AirTag Loop", "#", ""),
                    (18, ' . (int) $id_shop . ',' . (int) $language['id_lang'] . ', "AirTag Loop", "#", ""),
                    (19, ' . (int) $id_shop . ',' . (int) $language['id_lang'] . ', "AirTag Loop", "#", ""),
                    (20, ' . (int) $id_shop . ',' . (int) $language['id_lang'] . ', "AirTag Loop", "#", ""),
                    (21, ' . (int) $id_shop . ',' . (int) $language['id_lang'] . ', "", "#", "<div class=\"col-sm-4 col-xs-12\"><a href=\"#\"><img src=\"' . pSQL($imgpath) . '/sample/1_1.jpg\" width=\"191\" height=\"285\" loading=\"lazy\" alt=\"AirPods Max\" /><span>AirPods Max<span></span></span></a></div>\r\n<div class=\"col-sm-4 col-xs-12\"><a href=\"#\"><img src=\"' . pSQL($imgpath) . '/sample/2_1.jpg\" width=\"191\" height=\"285\" loading=\"lazy\" alt=\"AirPods Max\" /><span>Air Tag</span></a></div>\r\n<div class=\"col-sm-4 col-xs-12\"><a href=\"#\"><img src=\"' . pSQL($imgpath) . '/sample/3_1.jpg\" width=\"191\" height=\"285\" loading=\"lazy\" alt=\"AirPods Max\" /><span>MagSafe</span></a></div>"),
                    (22, ' . (int) $id_shop . ',' . (int) $language['id_lang'] . ', "Computers & Laptops", "#", ""),
                    (23, ' . (int) $id_shop . ',' . (int) $language['id_lang'] . ', "Mobiles & Tablets", "#", ""),
                    (24, ' . (int) $id_shop . ',' . (int) $language['id_lang'] . ', "TV, Video & Audio", "#", ""),
                    (25, ' . (int) $id_shop . ',' . (int) $language['id_lang'] . ', "Mobile Accessories", "#", ""),
                    (26, ' . (int) $id_shop . ',' . (int) $language['id_lang'] . ', "Electronic Accessories", "#", ""),
                    (27, ' . (int) $id_shop . ',' . (int) $language['id_lang'] . ', "Computers & Laptops", "#", ""),
                    (28, ' . (int) $id_shop . ',' . (int) $language['id_lang'] . ', "Apple", "#", ""),
                    (29, ' . (int) $id_shop . ',' . (int) $language['id_lang'] . ', "Lenovo", "#", ""),
                    (30, ' . (int) $id_shop . ',' . (int) $language['id_lang'] . ', "Notebook Pro", "#", ""),
                    (31, ' . (int) $id_shop . ',' . (int) $language['id_lang'] . ', "Notebook Pro", "#", ""),
                    (32, ' . (int) $id_shop . ',' . (int) $language['id_lang'] . ', "MacBook", "#", ""),
                    (33, ' . (int) $id_shop . ',' . (int) $language['id_lang'] . ', "Mobile Accessories", "#", ""),
                    (34, ' . (int) $id_shop . ',' . (int) $language['id_lang'] . ', "Power Banks", "#", ""),
                    (35, ' . (int) $id_shop . ',' . (int) $language['id_lang'] . ', "Mobile Cases", "#", ""),
                    (36, ' . (int) $id_shop . ',' . (int) $language['id_lang'] . ', "Smart Headphones", "#", ""),
                    (37, ' . (int) $id_shop . ',' . (int) $language['id_lang'] . ', "Memory Cards", "#", ""),
                    (38, ' . (int) $id_shop . ',' . (int) $language['id_lang'] . ', "MacBook", "#", ""),
                    (39, ' . (int) $id_shop . ',' . (int) $language['id_lang'] . ', "Home & Appliances", "#", ""),
                    (40, ' . (int) $id_shop . ',' . (int) $language['id_lang'] . ', "Refrigerator", "#", ""),
                    (41, ' . (int) $id_shop . ',' . (int) $language['id_lang'] . ', "Refrigerator", "#", ""),
                    (42, ' . (int) $id_shop . ',' . (int) $language['id_lang'] . ', "Refrigerator", "#", ""),
                    (43, ' . (int) $id_shop . ',' . (int) $language['id_lang'] . ', "Refrigerator", "#", ""),
                    (44, ' . (int) $id_shop . ',' . (int) $language['id_lang'] . ', "Refrigerator", "#", ""),
                    (46, ' . (int) $id_shop . ',' . (int) $language['id_lang'] . ', "", "#", ""),
                    (47, ' . (int) $id_shop . ',' . (int) $language['id_lang'] . ', "", "#", ""),
                    (48, ' . (int) $id_shop . ',' . (int) $language['id_lang'] . ', "", "#", ""),
                    (49, ' . (int) $id_shop . ',' . (int) $language['id_lang'] . ', "", "#", ""),
                    (50, ' . (int) $id_shop . ',' . (int) $language['id_lang'] . ', "", "#", "<p><img src=\"' . pSQL($imgpath) . '/sample/demo_img_1.jpg\" alt=\"test 3\" title=\"test 3\" class=\"tv-img-responsive\" loading=\"lazy\" width=\"170\" height=\"75\" /></p>"),
                    (51, ' . (int) $id_shop . ',' . (int) $language['id_lang'] . ', "", "#", "<p><img src=\"' . pSQL($imgpath) . '/sample/demo_img_2.jpg\" alt=\"test 3\" title=\"test 3\" class=\"tv-img-responsive\" loading=\"lazy\" width=\"170\" height=\"75\" /></p>"),
                    (52, ' . (int) $id_shop . ',' . (int) $language['id_lang'] . ', "", "#", "<p><img src=\"' . pSQL($imgpath) . '/sample/demo_img_3.jpg\" alt=\"test 3\" title=\"test 3\" class=\"tv-img-responsive\" loading=\"lazy\" width=\"170\" height=\"75\" /></p>"),
                    (53, ' . (int) $id_shop . ',' . (int) $language['id_lang'] . ', "", "#", "<p><img src=\"' . pSQL($imgpath) . '/sample/demo_img_4.jpg\" alt=\"test 3\" title=\"test 3\" class=\"tv-img-responsive\" loading=\"lazy\" width=\"170\" height=\"75\" /></p>"),
                    (54, ' . (int) $id_shop . ',' . (int) $language['id_lang'] . ', "", "#", "<p><img src=\"' . pSQL($imgpath) . '/sample/demo_img_5.jpg\" alt=\"test 3\" title=\"test 3\" class=\"tv-img-responsive\" loading=\"lazy\" width=\"170\" height=\"75\" /></p>"),
                    (55, ' . (int) $id_shop . ',' . (int) $language['id_lang'] . ', "", "#", "<p><img src=\"' . pSQL($imgpath) . '/sample/demo_img_6.jpg\" alt=\"test 3\" title=\"test 3\" class=\"tv-img-responsive\" loading=\"lazy\" width=\"170\" height=\"75\" /></p>"),
                    (56, ' . (int) $id_shop . ',' . (int) $language['id_lang'] . ', "Theme Features", "#", ""),
                    (57, ' . (int) $id_shop . ',' . (int) $language['id_lang'] . ', "Typography", "#", ""),
                    (58, ' . (int) $id_shop . ',' . (int) $language['id_lang'] . ', "Typography", "#", ""),
                    (59, ' . (int) $id_shop . ',' . (int) $language['id_lang'] . ', "Typography", "#", ""),
                    (60, ' . (int) $id_shop . ',' . (int) $language['id_lang'] . ', "Typography", "#", ""),
                    (61, ' . (int) $id_shop . ',' . (int) $language['id_lang'] . ', "Typography", "#", ""),
                    (62, ' . (int) $id_shop . ',' . (int) $language['id_lang'] . ', "Banner", "#", "<p><img src=\"' . pSQL($imgpath) . '/sample/Menu_Banner.jpg\" loading=\"lazy\" width=\"643\" height=\"160\" /></p>");');
        }

        return $return;
    }
}
