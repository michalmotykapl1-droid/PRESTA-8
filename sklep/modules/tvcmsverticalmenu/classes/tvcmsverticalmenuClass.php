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
class TvcmsVerticalMenuClass extends ObjectModel
{
    public $type_link;

    public $dropdown;

    public $type_icon;

    public $icon;

    public $class;

    public $align_sub;

    public $width_sub;

    public $title;

    public $link;

    public $subtitle;

    public $position;

    public $active;

    public static $definition = [
        'table' => 'tvcmsverticalmenu',
        'primary' => 'id_tvcmsverticalmenu',
        'multilang' => true,
        'multilang_shop' => true,
        'fields' => [
            'type_link' => [
                'type' => self::TYPE_BOOL,
                'shop' => true,
                'validate' => 'isunsignedInt',
                'required' => true,
                'size' => 255,
            ],
            'dropdown' => [
                'type' => self::TYPE_BOOL,
                'shop' => true,
                'validate' => 'isunsignedInt',
                'required' => true,
            ],
            'type_icon' => [
                'type' => self::TYPE_BOOL,
                'shop' => true,
                'validate' => 'isunsignedInt',
                'required' => true,
            ],
            'icon' => ['type' => self::TYPE_STRING, 'shop' => true, 'validate' => 'isCleanHtml'],
            'align_sub' => ['type' => self::TYPE_STRING, 'shop' => true, 'validate' => 'isCleanHtml'],
            'width_sub' => ['type' => self::TYPE_STRING, 'shop' => true, 'validate' => 'isCleanHtml'],
            'class' => ['type' => self::TYPE_STRING, 'shop' => true, 'validate' => 'isCleanHtml'],
            'title' => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isCleanHtml'],
            'link' => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isCleanHtml'],
            'subtitle' => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isCleanHtml'],
            'position' => [
                'type' => self::TYPE_INT,
                'shop' => true,
                'validate' => 'isunsignedInt',
                'required' => true,
            ],
            'active' => ['type' => self::TYPE_BOOL, 'shop' => true, 'validate' => 'isBool', 'required' => true],
        ],
    ];

    public function __construct(
        $id_tvcmsverticalmenu = null,
        $id_lang = null,
        $id_shop = null,
        Context $context = null
    ) {
        parent::__construct($id_tvcmsverticalmenu, $id_lang, $id_shop);
        Shop::addTableAssociation('tvcmsverticalmenu', ['type' => 'shop']);
        Shop::addTableAssociation('tvcmsverticalmenu_lang', ['type' => 'fk_shop']);
        if ($this->id) {
            $this->active = $this->getFieldShop('active');
        }
    }

    public function getFieldShop($field)
    {
        $id_shop = (int) Context::getContext()->shop->id;
        $sql = 'SELECT wms.' . pSQL($field) . ' FROM ' . _DB_PREFIX_ . 'tvcmsverticalmenu wm
        LEFT JOIN ' . _DB_PREFIX_ . 'tvcmsverticalmenu_shop wms ON (wm.id_tvcmsverticalmenu = wms.id_tvcmsverticalmenu)
        WHERE wm.id_tvcmsverticalmenu = ' . (int) $this->id . ' AND wms.id_shop = ' . (int) $id_shop . '';
        $result = Db::getInstance()->getValue($sql);

        return $result;
    }

    public function add($autodate = true, $null_values = false)
    {
        $res = parent::add($autodate, $null_values);

        return $res;
    }

    public function delete()
    {
        $res = true;
        $icon = $this->icon;
        if (0 === preg_match('/sample/', $icon)) {
            if ($icon && file_exists(_PS_MODULE_DIR_ . 'tvcmsverticalmenu/views/img/icons/' . $icon)) {
                $res &= @unlink(_PS_MODULE_DIR_ . 'tvcmsverticalmenu/views/img/icons/' . $icon);
            }
        }

        $row_items = $this->getRowByMenu($this->id);
        if (count($row_items) > 0) {
            foreach ($row_items as $row_item) {
                $column_items = $this->getColumByRow($row_item['id_row']);
                if (count($column_items) > 0) {
                    foreach ($column_items as $column_item) {
                        $res &= $this->deleteMenuItem($column_item['id_column']);
                        $res &= $this->deleteColumnItem($column_item['id_column']);
                    }
                }
                $res &= $this->deleteRowItem($row_item['id_row']);
            }
        }
        $res &= parent::delete();

        return $res;
    }

    public function getColumByRow($id_row)
    {
        return Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS('
            SELECT mc.*
            FROM ' . _DB_PREFIX_ . 'tvcmsverticalmenu_column mc
            WHERE mc.`id_row` = ' . (int) $id_row);
    }

    public function deleteMenuItem($id_col)
    {
        $res = true;
        $menu_items = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS('
            SELECT mi.*
            FROM ' . _DB_PREFIX_ . 'tvcmsverticalmenu_item mi
            WHERE mi.`id_column` = ' . (int) $id_col . ' ORDER BY mi.id_item');

        if (isset($menu_items) && count($menu_items) > 0) {
            foreach ($menu_items as $menu_item) {
                $res &= Db::getInstance()->execute('
                    DELETE FROM `' . _DB_PREFIX_ . 'tvcmsverticalmenu_item_lang`
                    WHERE `id_item` = ' . (int) $menu_item['id_item']);

                $res &= Db::getInstance()->execute('
                    DELETE FROM `' . _DB_PREFIX_ . 'tvcmsverticalmenu_item_shop`
                    WHERE `id_item` = ' . (int) $menu_item['id_item']);

                $res &= Db::getInstance()->execute('
                    DELETE FROM `' . _DB_PREFIX_ . 'tvcmsverticalmenu_item`
                    WHERE `id_item` = ' . (int) $menu_item['id_item']);
            }
        }

        return $res;
    }

    public function deleteColumnItem($id_col)
    {
        $res = true;
        $res &= Db::getInstance()->execute('
            DELETE FROM `' . _DB_PREFIX_ . 'tvcmsverticalmenu_column_shop`
            WHERE `id_column` = ' . (int) $id_col);
        $res &= Db::getInstance()->execute('
                DELETE FROM `' . _DB_PREFIX_ . 'tvcmsverticalmenu_column`
                WHERE `id_column` = ' . (int) $id_col);

        return $res;
    }

    public function deleteRowItem($id_row)
    {
        $res = true;
        $res &= Db::getInstance()->execute('
                DELETE FROM `' . _DB_PREFIX_ . 'tvcmsverticalmenu_row_shop`
                WHERE `id_row` = ' . (int) $id_row);

        $res &= Db::getInstance()->execute('
                DELETE FROM `' . _DB_PREFIX_ . 'tvcmsverticalmenu_row`
                WHERE `id_row` = ' . (int) $id_row);

        return $res;
    }

    public function getRowByMenu($id_menu)
    {
        return Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS('
            SELECT mr.*
            FROM ' . _DB_PREFIX_ . 'tvcmsverticalmenu_row mr
            WHERE mr.`id_tvcmsverticalmenu` = ' . (int) $id_menu);
    }

    public function getMenus()
    {
        $id_shop = (int) Context::getContext()->shop->id;
        $id_lang = (int) Context::getContext()->language->id;
        $kq = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS('
            SELECT wt.*, wl.*
            FROM ' . _DB_PREFIX_ . 'tvcmsverticalmenu_shop wt
            LEFT JOIN `' . _DB_PREFIX_ . 'tvcmsverticalmenu_lang` wl ON (wl.`id_tvcmsverticalmenu` = '
                 . 'wt.`id_tvcmsverticalmenu` AND wt.`id_shop` = wl.`id_shop`)
            WHERE wt.active = 1 AND wl.id_shop = ' . (int) $id_shop . ' AND wl.id_lang=' . (int) $id_lang
                 . ' ORDER BY wt.position ASC, wt.id_tvcmsverticalmenu ASC');

        return $kq;
    }

    public function uploadImage($feild, $folder)
    {
        $file_up = '';
        $type = Tools::strtolower(Tools::substr(strrchr($_FILES[$feild]['name'], ' . '), 1));
        if (!empty($_FILES[$feild]['tmp_name'])) {
            $imagesize = @getimagesize($_FILES[$feild]['tmp_name']);
            if (isset($_FILES[$feild])
                && isset($_FILES[$feild]['tmp_name'])
                && !empty($_FILES[$feild]['tmp_name'])
                && !empty($imagesize)
                && in_array(Tools::strtolower(Tools::substr(
                    strrchr($imagesize['mime'], '/'),
                    1
                )), ['jpg', 'gif', 'jpeg', 'png'])
                && in_array($type, ['jpg', 'gif', 'jpeg', 'png'])) {
                $temp_name = tempnam(_PS_TMP_IMG_DIR_, 'PS');
                $salt = sha1(microtime());
                if (ImageManager::validateUpload($_FILES[$feild])) {
                    return false;
                } elseif (!$temp_name || !move_uploaded_file($_FILES[$feild]['tmp_name'], $temp_name)) {
                    return false;
                } elseif (!ImageManager::resize($temp_name, _PS_MODULE_DIR_ . $folder . $salt . '_'
                  . $_FILES[$feild]['name'], null, null, $type)) {
                    return false;
                }
                if (isset($temp_name)) {
                    @unlink($temp_name);
                }
                $file_up = $salt . '_' . $_FILES[$feild]['name'];
            }
        }

        return $file_up;
    }
}
