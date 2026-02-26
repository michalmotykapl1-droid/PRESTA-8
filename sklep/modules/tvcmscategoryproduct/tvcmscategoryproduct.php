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

// use PrestaShop\PrestaShop\Core\Module\WidgetInterface;
use PrestaShop\PrestaShop\Adapter\Category\CategoryProductSearchProvider;
use PrestaShop\PrestaShop\Adapter\Image\ImageRetriever;
use PrestaShop\PrestaShop\Adapter\Product\PriceFormatter;
use PrestaShop\PrestaShop\Adapter\Product\ProductColorsRetriever;
use PrestaShop\PrestaShop\Core\Product\ProductListingPresenter;
use PrestaShop\PrestaShop\Core\Product\Search\ProductSearchContext;
use PrestaShop\PrestaShop\Core\Product\Search\ProductSearchQuery;
use PrestaShop\PrestaShop\Core\Product\Search\SortOrder;

include_once 'classes/tvcmscategoryproduct_image_upload.class.php';
include_once 'classes/tvcmscategoryproduct_status.class.php';

class TvcmsCategoryProduct extends Module
{
    private $html = '';

    private $category_list = [];

    public $id_shop_group = '';

    public $id_shop = '';

    public function __construct()
    {
        $this->name = 'tvcmscategoryproduct';
        $this->tab = 'front_office_features';
        $this->version = '4.0.2';
        $this->author = 'ThemeVolty';
        $this->need_instance = 0;
        $this->secure_key = Tools::encrypt($this->name);
        $this->bootstrap = true;

        parent::__construct();
        $this->displayName = $this->l('ThemeVolty - Tab Categroy Product Slider');
        $this->description = $this->l('Display Category Slider in Front Side');

        $this->ps_versions_compliancy = ['min' => '1.7', 'max' => _PS_VERSION_];
        $this->module_key = '';

        $this->confirmUninstall = $this->l('Warning: all the data saved in your database will be deleted.'
            . ' Are you sure you want uninstall this module?');

        $this->id_shop_group = (int) Shop::getContextShopGroupID();
        $this->id_shop = (int) Context::getContext()->shop->id;
    }

    public function install()
    {
        // $this->createDefaultData();
        $this->createTable();
        $this->installTab();

        return parent::install()
            && $this->registerHook('displayBackOfficeHeader')
            && $this->registerHook('header')
            && $this->registerHook('displayHome');
        // && $this->registerHook('displayNavFullWidth');
    }

    public function installTab()
    {
        $response = true;
        // First check for parent tab
        $parentTabID = Tab::getIdFromClassName('AdminThemeVolty');

        if ($parentTabID) {
            $parentTab = new Tab($parentTabID);
        } else {
            $parentTab = new Tab();
            $parentTab->active = 1;
            $parentTab->name = [];
            $parentTab->class_name = 'AdminThemeVolty';
            foreach (Language::getLanguages() as $lang) {
                $parentTab->name[$lang['id_lang']] = 'ThemeVolty Extension';
            }
            $parentTab->id_parent = 0;
            $parentTab->module = $this->name;
            $response &= $parentTab->add();
        }

        // Check for parent tab2
        $parentTab_2ID = Tab::getIdFromClassName('AdminThemeVoltyModules');
        if ($parentTab_2ID) {
            $parentTab_2 = new Tab($parentTab_2ID);
        } else {
            $parentTab_2 = new Tab();
            $parentTab_2->active = 1;
            $parentTab_2->name = [];
            $parentTab_2->class_name = 'AdminThemeVoltyModules';
            foreach (Language::getLanguages() as $lang) {
                $parentTab_2->name[$lang['id_lang']] = 'ThemeVolty Configure';
            }
            $parentTab_2->id_parent = $parentTab->id;
            $parentTab_2->module = $this->name;
            $response &= $parentTab_2->add();
        }
        // Created tab
        $tab = new Tab();
        $tab->active = 1;
        $tab->class_name = 'Admin' . $this->name;
        $tab->name = [];
        foreach (Language::getLanguages() as $lang) {
            $tab->name[$lang['id_lang']] = 'Tab Category Product Slider';
        }
        $tab->id_parent = $parentTab_2->id;
        $tab->module = $this->name;
        $response &= $tab->add();

        return $response;
    }

    // Store Default Data Such As CreateVariable, CreateTable & Insert Data
    public function createDefaultData()
    {
        $this->reset();
        $num_of_data = 7;
        $this->createVariable();
        $this->createTable();
        $this->insertSmapleData($num_of_data);
    }

    // Create Default Variable form Frist Form
    public function createVariable()
    {
        $result = [];
        $languages = Language::getLanguages();

        foreach ($languages as $lang) {
            $result['TVCMSCATEGORYPRODUCT_TITLE'][$lang['id_lang']] = 'Categories Products';
            $result['TVCMSCATEGORYPRODUCT_PRODUCT_TITLE'][$lang['id_lang']] = 'Offer Zone Category';
            $result['TVCMSCATEGORYPRODUCT_SUB_DESCRIPTION'][$lang['id_lang']] = 'This is Show Short Description';
            $result['TVCMSCATEGORYPRODUCT_DESCRIPTION'][$lang['id_lang']] = 'Description';
            $result['TVCMSCATEGORYPRODUCT_IMG'][$lang['id_lang']] = 'demo_title.jpg';
        }
        $tmp = $result['TVCMSCATEGORYPRODUCT_TITLE'];
        Configuration::updateValue('TVCMSCATEGORYPRODUCT_TITLE', $tmp);
        $tmp = $result['TVCMSCATEGORYPRODUCT_PRODUCT_TITLE'];
        Configuration::updateValue('TVCMSCATEGORYPRODUCT_PRODUCT_TITLE', $tmp);
        $tmp = $result['TVCMSCATEGORYPRODUCT_SUB_DESCRIPTION'];
        Configuration::updateValue('TVCMSCATEGORYPRODUCT_SUB_DESCRIPTION', $tmp);
        $tmp = $result['TVCMSCATEGORYPRODUCT_DESCRIPTION'];
        Configuration::updateValue('TVCMSCATEGORYPRODUCT_DESCRIPTION', $tmp);
        $tmp = $result['TVCMSCATEGORYPRODUCT_IMG'];
        Configuration::updateValue('TVCMSCATEGORYPRODUCT_IMG', $tmp);
    }

    // Create Table For Second Form
    public function createTable()
    {
        $create_table = [];
        $create_table[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'tvcmscategoryproduct` (
                        `id_tvcmscategoryproduct` int(11) AUTO_INCREMENT PRIMARY KEY,
                        `id_category` int(11),
                        `position` int(11),
                        `id_shop_group` int(11),
                        `id_shop` int(11),
                        `image` VARCHAR(100),
                        `num_of_prod` int(11),
                        `status` varchar(3)
                    ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

        $create_table[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'tvcmscategoryproduct_lang` (
                        `id_tvcmscategoryproduct_lang` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
                        `id_tvcmscategoryproduct` INT NOT NULL,
                        `id_shop_group` int(11),
                        `id_shop` int(11),
                        `id_category` INT,
                        `id_lang` INT NOT NULL,
                        `title` VARCHAR(255)
                    ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

        foreach ($create_table as $table) {
            Db::getInstance()->execute($table);
        }
    }

    // Insert Semple Data Form Second Form
    public function insertSmapleData($num_of_data)
    {
        $data = [];
        $category = $this->getAllCategory();
        for ($i = 1; $i <= $num_of_data; ++$i) {
            if (isset($category[$i]['id_category'])) {
                $data[] = 'INSERT INTO `' . _DB_PREFIX_ . 'tvcmscategoryproduct`
                        SET 
                            `id_tvcmscategoryproduct` = \'' . $i . '\',
                            `position` = ' . $i . ',
                            `id_shop_group` = ' . (int) $this->id_shop_group . ',
                            `id_shop` = ' . (int) $this->id_shop . ',
                            `id_category` = \'' . (int) $category[$i]['id_category'] . '\',
                            `image` = \'Category_product_icon_' . $i . '.png\',
                            `num_of_prod` = 8,
                            `status` = \'1\'';

                $languages = Language::getLanguages();
                foreach ($languages as $lang) {
                    $data[] = 'INSERT INTO `' . _DB_PREFIX_ . 'tvcmscategoryproduct_lang`
                        SET 
                            `id_tvcmscategoryproduct_lang` = NULL,
                            `id_tvcmscategoryproduct` = \'' . $i . '\',
                            `id_shop_group` = ' . (int) $this->id_shop_group . ',
                            `id_shop` = ' . (int) $this->id_shop . ',
                            `id_category` = \'' . (int) $category[$i]['id_category'] . '\',
                            `id_lang` = \'' . (int) $lang['id_lang'] . '\',
                            `title` = \'Title ' . $i . '\'';
                }
            } else {
                $data[] = 'INSERT INTO `' . _DB_PREFIX_ . 'tvcmscategoryproduct`
                        SET 
                            `id_tvcmscategoryproduct` = \'' . $i . '\',
                            `position` = ' . $i . ',
                            `id_shop_group` = ' . (int) $this->id_shop_group . ',
                            `id_shop` = ' . (int) $this->id_shop . ',
                            `id_category` = \'1\',
                            `image` = \'Category_product_icon_' . $i . '.png\',
                            `num_of_prod` = 8,
                            `status` = \'0\'';

                $languages = Language::getLanguages();
                foreach ($languages as $lang) {
                    $data[] = 'INSERT INTO `' . _DB_PREFIX_ . 'tvcmscategoryproduct_lang`
                        SET 
                            `id_tvcmscategoryproduct_lang` = NULL,
                            `id_tvcmscategoryproduct` = \'' . $i . '\',
                            `id_shop_group` = ' . (int) $this->id_shop_group . ',
                            `id_shop` = ' . (int) $this->id_shop . ',
                            `id_category` = \'1\',
                            `id_lang` = \'' . (int) $lang['id_lang'] . '\',
                            `title` = \'Title ' . $i . '\'';
                }
            }
        }
        foreach ($data as $query) {
            Db::getInstance()->execute($query);
        }
    }

    public function maxId()
    {
        $select_data = 'SELECT MAX(id_tvcmscategoryproduct) as max_id FROM `' . _DB_PREFIX_ . 'tvcmscategoryproduct`';
        $ans = Db::getInstance()->executeS($select_data);

        return $ans[0]['max_id'];
    }

    // Select All Category id From Table
    public function selectAllIdFromTable()
    {
        $select_data = 'SELECT id_tvcmscategoryproduct FROM `' . _DB_PREFIX_ . 'tvcmscategoryproduct`';
        $ans = Db::getInstance()->executeS($select_data);
        $final_ans = [];
        foreach ($ans as $a) {
            $final_ans[] = $a['id_tvcmscategoryproduct'];
        }

        return $final_ans;
    }

    // Select All Language By id From Table
    public function selectAllLangIdById($id_tvcmscategoryproduct)
    {
        $select_data = 'SELECT 
                            id_lang 
                        FROM 
                            `' . _DB_PREFIX_ . 'tvcmscategoryproduct_lang` 
                        WHERE 
                            id_tvcmscategoryproduct = ' . (int) $id_tvcmscategoryproduct;
        $ans = Db::getInstance()->executeS($select_data);
        $return = [];
        foreach ($ans as $a) {
            $return[] = $a['id_lang'];
        }

        return $return;
    }

    // Insert & Update Data Which Customer Add.
    public function insertData($data)
    {
        $insert_data = [];
        if (isset($data['id']) && !empty($data['id'])) {
            $id = $data['id'];
            $insert_data[] = 'UPDATE `' . _DB_PREFIX_ . 'tvcmscategoryproduct`
                        SET 
                            `id_category` = \'' . (int) $data['id_category'] . '\',
                            `image` = \'' . pSQL($data['image']) . '\',
                            `num_of_prod` = \'' . (int) $data['num_of_prod'] . '\',
                            `status` = \'' . (int) $data['status'] . '\'
                        WHERE
                            `id_shop_group` = ' . (int) $this->id_shop_group . '
                        AND `id_shop` = ' . (int) $this->id_shop . '
                        AND `id_tvcmscategoryproduct` = ' . (int) $id . ';';

            $result = $this->selectAllLangIdById($id);

            $languages = Language::getLanguages();
            foreach ($languages as $lang) {
                if (in_array($lang['id_lang'], $result)) {
                    $insert_data[] = 'UPDATE `' . _DB_PREFIX_ . 'tvcmscategoryproduct_lang`
                            SET 
                                `id_category` = \'' . (int) $data['id_category'] . '\',
                                `id_lang` = \'' . (int) $lang['id_lang'] . '\',
                                `title` = \'' . pSQL($data['lang_info'][$lang['id_lang']]['custom_title']) . '\'
                            WHERE
                                `id_shop_group` = ' . (int) $this->id_shop_group . '
                            AND 
                                `id_shop` = ' . (int) $this->id_shop . '
                            AND
                                `id_tvcmscategoryproduct` = ' . (int) $id . '
                            AND 
                                `id_lang` = \'' . (int) $lang['id_lang'] . '\';';
                } else {
                    $insert_data[] = 'INSERT INTO `' . _DB_PREFIX_ . 'tvcmscategoryproduct_lang`
                        SET 
                            `id_tvcmscategoryproduct_lang` = NULL,
                            `id_tvcmscategoryproduct` = ' . $id . ',
                            `id_shop_group` = ' . (int) $this->id_shop_group . ',
                            `id_shop` = ' . (int) $this->id_shop . ',
                            `id_category` = \'' . (int) $data['id_category'] . '\',
                            `id_lang` = \'' . (int) $lang['id_lang'] . '\',
                            `title` = \'' . pSQL($data['lang_info'][$lang['id_lang']]['custom_title']) . '\';';
                }
            }
        } else {
            $max_id = $this->maxId();
            $new_id = $max_id + 1;

            $insert_data[] = 'INSERT INTO `' . _DB_PREFIX_ . 'tvcmscategoryproduct`
                        SET 
                            `id_tvcmscategoryproduct` = ' . (int) $new_id . ',
                            `id_category` = \'' . (int) $data['id_category'] . '\',
                            `position` = ' . (int) $new_id . ',
                            `id_shop_group` = ' . (int) $this->id_shop_group . ',
                            `id_shop` = ' . (int) $this->id_shop . ',
                            `image` = \'' . pSQL($data['image']) . '\',
                            `num_of_prod` = \'' . (int) $data['num_of_prod'] . '\',
                            `status` = \'' . (int) $data['status'] . '\';';

            $languages = Language::getLanguages();
            foreach ($languages as $lang) {
                $insert_data[] = 'INSERT INTO `' . _DB_PREFIX_ . 'tvcmscategoryproduct_lang`
                        SET 
                            `id_tvcmscategoryproduct_lang` = NULL,
                            `id_tvcmscategoryproduct` = ' . (int) $new_id . ',
                            `id_shop_group` = ' . (int) $this->id_shop_group . ',
                            `id_shop` = ' . (int) $this->id_shop . ',
                            `id_category` = \'' . (int) $data['id_category'] . '\',
                            `id_lang` = \'' . (int) $lang['id_lang'] . '\',
                            `title` = \'' . pSQL($data['lang_info'][$lang['id_lang']]['custom_title']) . '\';';
            }
        }

        foreach ($insert_data as $data) {
            Db::getInstance()->execute($data);
        }
    }

    // Get all Category Which Key is Id And Value is Category Name
    public function getAllCategory()
    {
        $category = Category::getAllCategoriesName();
        $all_category_id = [];
        $i = 1;
        unset($category[0]);
        unset($category[1]);
        foreach ($category as $cat) {
            $all_category_id[$i]['id_category'] = $cat['id_category'];
            $all_category_id[$i]['name'] = $cat['name'];
            ++$i;
        }

        return $all_category_id;
    }

    // Show Admin Data in Table
    public function showAdminData()
    {
        $result = [];
        $return_data = [];
        $default_lang_id = $this->context->language->id;

        $select_data = 'SELECT * FROM `' . _DB_PREFIX_ . 'tvcmscategoryproduct`'
             . ' WHERE `id_shop_group` = ' . (int) $this->id_shop_group . ' AND `id_shop` = ' . (int) $this->id_shop
             . ' ORDER BY `position`;';

        $result['tvcmscategoryproduct'] = Db::getInstance()->executeS($select_data);

        $select_data = 'SELECT * FROM `' . _DB_PREFIX_ . 'tvcmscategoryproduct_lang`'
             . ' WHERE `id_shop_group` = ' . (int) $this->id_shop_group . ' AND `id_shop` = ' . (int) $this->id_shop . ';';

        $result['tvcmscategoryproduct_lang'] = Db::getInstance()->executeS($select_data);

        foreach ($result['tvcmscategoryproduct'] as $key => $data) {
            $return_data[$key]['id'] = $data['id_tvcmscategoryproduct'];
            $id = $data['id_tvcmscategoryproduct'];

            foreach ($result['tvcmscategoryproduct_lang'] as $lang) {
                if ($default_lang_id == $lang['id_lang'] && $id == $lang['id_tvcmscategoryproduct']) {
                    // $lang_id = $lang['id_lang'];
                    $return_data[$key]['id_lang'] = $lang['id_lang'];
                    $return_data[$key]['title'] = $lang['title'];
                }
            }

            $return_data[$key]['id_category'] = $data['id_category'];
            $return_data[$key]['image'] = $data['image'];
            $return_data[$key]['num_of_prod'] = $data['num_of_prod'];
            $return_data[$key]['status'] = $data['status'];
        }

        return $return_data;
    }

    // Show Front Side Data
    public function showData($id = null)
    {
        $result = [];
        $return_data = [];

        $select_data = '';
        $select_data .= 'SELECT * FROM `' . _DB_PREFIX_ . 'tvcmscategoryproduct` 
                WHERE 
                `id_shop_group` = ' . (int) $this->id_shop_group
                 . ' AND `id_shop` = ' . (int) $this->id_shop;
        if ($id) {
            $select_data .= ' AND `id_tvcmscategoryproduct` = ' . (int) $id;
        } else {
            $select_data .= ' ORDER BY `position`';
        }

        $result['tvcmscategoryproduct'] = Db::getInstance()->executeS($select_data);

        $select_data = '';
        $select_data .= 'SELECT * FROM `' . _DB_PREFIX_ . 'tvcmscategoryproduct_lang`'
             . ' WHERE `id_shop_group` = ' . (int) $this->id_shop_group . ' AND `id_shop` = ' . (int) $this->id_shop;
        if ($id) {
            $select_data .= ' AND id_tvcmscategoryproduct = ' . (int) $id;
        }

        $result['tvcmscategoryproduct_lang'] = Db::getInstance()->executeS($select_data);

        foreach ($result['tvcmscategoryproduct'] as $key => $data) {
            $return_data[$key]['id'] = $data['id_tvcmscategoryproduct'];
            $id = $data['id_tvcmscategoryproduct'];
            foreach ($result['tvcmscategoryproduct_lang'] as $lang) {
                // $lang_id = $lang['id_lang'];
                if ($id == $lang['id_tvcmscategoryproduct']) {
                    $return_data[$key]['lang_info'][$lang['id_lang']]['id_lang'] = $lang['id_lang'];
                    $return_data[$key]['lang_info'][$lang['id_lang']]['title'] = $lang['title'];
                }
            }

            $return_data[$key]['id_category'] = $data['id_category'];
            $return_data[$key]['image'] = $data['image'];
            $return_data[$key]['num_of_prod'] = $data['num_of_prod'];
            $return_data[$key]['status'] = $data['status'];
        }

        return $return_data;
    }

    public function showFrontData()
    {
        $cookie = Context::getContext()->cookie;
        $id_lang = $cookie->id_lang;

        $select_data = '
            SELECT 
                mainTable.id_tvcmscategoryproduct AS id,
                mainTable.id_category AS id_category,
                mainTable.image,
                mainTable.num_of_prod,
                subTable.title
            FROM 
                `' . _DB_PREFIX_ . 'tvcmscategoryproduct` mainTable
            LEFT JOIN
                ' . _DB_PREFIX_ . 'tvcmscategoryproduct_lang subTable
            ON
                mainTable.id_tvcmscategoryproduct = subTable.id_tvcmscategoryproduct
            WHERE 
                mainTable.id_shop_group = ' . (int) $this->id_shop_group . ' 
            AND 
                mainTable.id_shop = ' . (int) $this->id_shop . '
            AND 
                mainTable.status = 1
            AND
                subTable.id_lang = ' . (int) $id_lang . '
            ORDER BY `position`';

        $result = Db::getInstance()->executeS($select_data);
        $result_data = [];
        if (!empty($result)) {
            $result_data = $result;
        }

        return $result_data;
    }

    public function getAllCategoryByIdsKey()
    {
        $category = Category::getAllCategoriesName();
        $all_category_id = [];
        foreach ($category as $cat) {
            $all_category_id[$cat['id_category']] = $cat['name'];
        }

        return $all_category_id;
    }

    public function uninstall()
    {
        $this->uninstallTab();
        $this->deleteVariable();
        $this->deleteTable();

        return parent::uninstall();
    }

    // Delete All Variable of Frist Form
    public function deleteVariable()
    {
        Configuration::deleteByName('TVCMSCATEGORYPRODUCT_TITLE');
        Configuration::deleteByName('TVCMSCATEGORYPRODUCT_PRODUCT_TITLE');
        Configuration::deleteByName('TVCMSCATEGORYPRODUCT_SUB_DESCRIPTION');
        Configuration::deleteByName('TVCMSCATEGORYPRODUCT_DESCRIPTION');
        Configuration::deleteByName('TVCMSCATEGORYPRODUCT_IMG');
    }

    // Delete Record by id Form Table
    public function deleteRecord($id)
    {
        $this->removeImage($id);

        $delete_data = [];
        $delete_data[] = 'DELETE FROM `' . _DB_PREFIX_ . 'tvcmscategoryproduct`
            WHERE 
                    `id_shop_group` = ' . (int) $this->id_shop_group . '
                AND 
                    `id_shop` = ' . (int) $this->id_shop . ' 
                AND 
                    `id_tvcmscategoryproduct` = ' . (int) $id;

        $delete_data[] = 'DELETE FROM `' . _DB_PREFIX_ . 'tvcmscategoryproduct_lang` 
            WHERE 
                    `id_shop_group` = ' . (int) $this->id_shop_group . '
                AND 
                    `id_shop` = ' . (int) $this->id_shop . ' 
                AND 
                    id_tvcmscategoryproduct = ' . (int) $id;

        foreach ($delete_data as $data) {
            Db::getInstance()->execute($data);
        }
    }

    // Delete All table
    public function deleteTable()
    {
        $delete_table = [];
        $delete_table[] = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'tvcmscategoryproduct`';
        $delete_table[] = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'tvcmscategoryproduct_lang`';

        foreach ($delete_table as $table) {
            Db::getInstance()->execute($table);
        }
    }

    public function uninstallTab()
    {
        $id_tab = Tab::getIdFromClassName('Admin' . $this->name);
        $tab = new Tab($id_tab);
        $tab->delete();

        return true;
    }

    public function removeImage($id)
    {
        $remove_images = [];
        $result = $this->showData($id);

        $remove_images[] = $result[0]['image'];

        foreach ($remove_images as $image) {
            // Match Pattern Which image you Don't want to delete.
            $res = preg_match('/^demo_img_.*$/', $image);
            if (file_exists(dirname(__FILE__) . './views/img/' . $image)
                && '1' != $res) {
                unlink(dirname(__FILE__) . './views/img/' . $image);
            }
        }
    }

    public function reset()
    {
        $trn_tbl = [];
        $trn_tbl[] = 'TRUNCATE `' . _DB_PREFIX_ . 'tvcmscategoryproduct`';
        $trn_tbl[] = 'TRUNCATE `' . _DB_PREFIX_ . 'tvcmscategoryproduct_lang`';
        foreach ($trn_tbl as $table) {
            Db::getInstance()->execute($table);
        }
    }

    public function getContent()
    {
        $useSSL = (isset($this->ssl) && $this->ssl && Configuration::get('PS_SSL_ENABLED')) || Tools::usingSecureMode() ? true : false;
        $protocol_content = $useSSL ? 'https://' : 'http://';
        $baseDir = $protocol_content . Tools::getHttpHost() . __PS_BASE_URI__;
        $link = PS_ADMIN_DIR;
        if (Tools::substr(strrchr($link, '/'), 1)) {
            $admin_folder = Tools::substr(strrchr($link, '/'), 1);
        } else {
            $admin_folder = Tools::substr(strrchr($link, "\'"), 1);
        }
        $static_token = Tools::getAdminToken('AdminModules' . (int) Tab::getIdFromClassName('AdminModules') . (int) $this->context->employee->id);
        $url_slidersampleupgrade = $baseDir . $admin_folder . '/index.php?controller=AdminModules&configure=' . $this->name . '&tab_module=front_office_features&module_name=' . $this->name . '&token=' . $static_token;
        $this->context->smarty->assign('tvurlupgrade', $url_slidersampleupgrade);

        if (Tools::isSubmit('submitTvcmsSampleinstall')) {
            $this->createDefaultData();
        }
        $message = $this->postProcess();
        $this->html .= $message;
        $this->html .= $this->renderForm();
        $this->html .= $this->showRecord();

        return $this->html;
    }

    public function postProcess()
    {
        $languages = Language::getLanguages();
        $message = '';
        $result = [];
        if (Tools::getValue('action')) {
            $action = Tools::getValue('action');
            $id = Tools::getValue('id');
            // print_r($_POST);
            if ('remove' == $action) {
                // remove record
                $this->deleteRecord($id);

                return $message .= $this->displayConfirmation($this->l('Record is Deleted . '));
            }
        }

        if (Tools::isSubmit('submitTvcmsCategoryForm')) {
            $old_file = 'demo_img_1.jpg';
            $no_image_selected = false;
            if (Tools::getValue('id')) {
                $id = Tools::getValue('id');
                $result['id'] = $id;
                $data = $this->showData($id);
                $old_file = $data[0]['image'];
            }

            $tvcms_obj = new TvcmsCategoryProductStatus();
            $show_fields = $tvcms_obj->fieldStatusInformation();

            if ($show_fields['image']) {
                $this->obj_image = new TvcmsCategoryProductImageUpload();
                if (!empty($_FILES['image']['name'])) {
                    $new_file = $_FILES['image'];
                    $ans = $this->obj_image->imageUploading($new_file, $old_file);
                    if ($ans['success']) {
                        $result['image'] = $ans['name'];
                    } else {
                        $message .= $ans['error'];
                        $result['image'] = $old_file;
                        if (!Tools::getValue('id')) {
                            $no_image_selected = true;
                        }
                    }
                } else {
                    $result['image'] = $old_file;
                    if (!Tools::getValue('id')) {
                        $message .= $this->displayError($this->l('Please Select Image . '));
                        $no_image_selected = true;
                    }
                }
            } else {
                $result['image'] = $old_file;
            }

            if (!$no_image_selected) {
                foreach ($languages as $lang) {
                    $tmp = Tools::getValue('custom_title_' . $lang['id_lang']);
                    $result['lang_info'][$lang['id_lang']]['custom_title'] = $tmp;
                }

                $result['id_category'] = Tools::getValue('id_category');
                $result['num_of_prod'] = Tools::getValue('num_of_prod');
                $result['status'] = Tools::getValue('status');

                if (0 == $result['id_category']) {
                    $message .= $this->displayError($this->l('Please select valid category.'));
                } else {
                    $this->insertData($result);
                    $message .= $this->displayConfirmation($this->l('Record is save successfully.'));
                }
                $this->clearCustomSmartyCache('tvcmscategoryproduct_display_home.tpl');
            }

            return $message;
        }

        if (Tools::isSubmit('submitTvcmsCategoryTitle')) {
            foreach ($languages as $lang) {
                $this->obj_image = new TvcmsCategoryProductImageUpload();
                if (!empty($_FILES['TVCMSCATEGORYPRODUCT_IMG_' . $lang['id_lang']]['name'])) {
                    $old_file = Configuration::get('TVCMSCATEGORYPRODUCT_IMG', $lang['id_lang']);
                    $new_file = $_FILES['TVCMSCATEGORYPRODUCT_IMG_' . $lang['id_lang']];
                    $ans = $this->obj_image->imageUploading($new_file, $old_file);
                    if ($ans['success']) {
                        $result['TVCMSCATEGORYPRODUCT_IMG'][$lang['id_lang']] = $ans['name'];
                    } else {
                        $message .= $ans['error'];
                        $result['TVCMSCATEGORYPRODUCT_IMG'][$lang['id_lang']] = $old_file;
                    }
                } else {
                    $old_file = Configuration::get('TVCMSCATEGORYPRODUCT_IMG', $lang['id_lang']);
                    $result['TVCMSCATEGORYPRODUCT_IMG'][$lang['id_lang']] = $old_file;
                }

                $tmp = Tools::getValue('TVCMSCATEGORYPRODUCT_TITLE_' . $lang['id_lang']);
                $result['TVCMSCATEGORYPRODUCT_TITLE'][$lang['id_lang']] = $tmp;

                $tmp = Tools::getValue('TVCMSCATEGORYPRODUCT_PRODUCT_TITLE_' . $lang['id_lang']);
                $result['TVCMSCATEGORYPRODUCT_PRODUCT_TITLE'][$lang['id_lang']] = $tmp;

                $tmp = Tools::getValue('TVCMSCATEGORYPRODUCT_SUB_DESCRIPTION_' . $lang['id_lang']);
                $result['TVCMSCATEGORYPRODUCT_SUB_DESCRIPTION'][$lang['id_lang']] = $tmp;

                $tmp = Tools::getValue('TVCMSCATEGORYPRODUCT_DESCRIPTION_' . $lang['id_lang']);
                $result['TVCMSCATEGORYPRODUCT_DESCRIPTION'][$lang['id_lang']] = $tmp;
            }

            $tmp = $result['TVCMSCATEGORYPRODUCT_TITLE'];
            Configuration::updateValue('TVCMSCATEGORYPRODUCT_TITLE', $tmp);
            $tmp = $result['TVCMSCATEGORYPRODUCT_PRODUCT_TITLE'];
            Configuration::updateValue('TVCMSCATEGORYPRODUCT_PRODUCT_TITLE', $tmp);
            $tmp = $result['TVCMSCATEGORYPRODUCT_SUB_DESCRIPTION'];
            Configuration::updateValue('TVCMSCATEGORYPRODUCT_SUB_DESCRIPTION', $tmp);
            $tmp = $result['TVCMSCATEGORYPRODUCT_DESCRIPTION'];
            Configuration::updateValue('TVCMSCATEGORYPRODUCT_DESCRIPTION', $tmp);
            $tmp = $result['TVCMSCATEGORYPRODUCT_IMG'];
            Configuration::updateValue('TVCMSCATEGORYPRODUCT_IMG', $tmp);

            return $message .= $this->displayConfirmation($this->l('Category Slider Title Updated.'));
        }
    }

    public function clearCustomSmartyCache($cache_id)
    {
        if (Cache::isStored($cache_id)) {
            Cache::clean($cache_id);
        }
    }

    // Show All Admin data in getContent Function
    public function showRecord()
    {
        $array_list = $this->showAdminData();
        $category_list = $this->getAllCategoryByIdsKey();

        $tvcms_obj = new TvcmsCategoryProductStatus();
        $show_fields = $tvcms_obj->fieldStatusInformation();
        $default_lang_id = $this->context->language->id;

        $this->context->smarty->assign('array_list', $array_list);
        $this->context->smarty->assign('category_list', $category_list);
        $this->context->smarty->assign('show_fields', $show_fields);
        $this->context->smarty->assign('default_lang_id', $default_lang_id);

        return $this->display(__FILE__, 'views/templates/admin/display_manage.tpl');
    }

    public function getConfigFormValues()
    {
        $cookie = Context::getContext()->cookie;
        $id_lang = $cookie->id_lang;
        $this->context->smarty->assign('id_lang', $id_lang);
        $fields = [];
        $languages = Language::getLanguages();

        // Frist Form Information
        foreach ($languages as $lang) {
            $a = Configuration::get('TVCMSCATEGORYPRODUCT_TITLE', $lang['id_lang']);
            $fields['TVCMSCATEGORYPRODUCT_TITLE'][$lang['id_lang']] = $a;

            $a = Configuration::get('TVCMSCATEGORYPRODUCT_PRODUCT_TITLE', $lang['id_lang']);
            $fields['TVCMSCATEGORYPRODUCT_PRODUCT_TITLE'][$lang['id_lang']] = $a;

            $a = Configuration::get('TVCMSCATEGORYPRODUCT_SUB_DESCRIPTION', $lang['id_lang']);
            $fields['TVCMSCATEGORYPRODUCT_SUB_DESCRIPTION'][$lang['id_lang']] = $a;

            $a = Configuration::get('TVCMSCATEGORYPRODUCT_DESCRIPTION', $lang['id_lang']);
            $fields['TVCMSCATEGORYPRODUCT_DESCRIPTION'][$lang['id_lang']] = $a;

            $a = Configuration::get('TVCMSCATEGORYPRODUCT_IMG', $lang['id_lang']);
            $fields['TVCMSCATEGORYPRODUCT_IMG'][$lang['id_lang']] = $a;
        }

        $path = _MODULE_DIR_ . $this->name . '/views/img/';
        $this->context->smarty->assign('path', $path);
        $all_category = $this->getAllCategory();
        $this->context->smarty->assign('all_category', $all_category);

        // Second Form Information
        $fields['id'] = '';
        foreach ($languages as $lang) {
            $fields['custom_title'][$lang['id_lang']] = '';
        }
        $fields['image'] = '';
        $fields['num_of_prod'] = '';
        $fields['status'] = 1;

        $this->context->smarty->assign('id_category_select', '0');

        if ('edit' == Tools::getValue('action')) {
            $id = Tools::getValue('id');
            $data = $this->showData($id);
            $data = $data[0];
            $fields['id'] = $id;

            foreach ($languages as $lang) {
                $fields['custom_title'][$lang['id_lang']] = $data['lang_info'][$lang['id_lang']]['title'];
            }
            $fields['image'] = $data['image'];
            $fields['num_of_prod'] = $data['num_of_prod'];
            $fields['status'] = $data['status'];

            $this->context->smarty->assign('id_category_select', $data['id_category']);
        }

        return $fields;
    }

    public function renderForm()
    {
        $helper = new HelperForm();

        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);

        $helper->identifier = $this->identifier;
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
             . '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->show_cancel_button = true;
        $module = 'tvcmscategoryproduct';
        $url = 'index.php?controller=AdminModules&configure=' . $module . '&token=' . Tools::getAdminTokenLite('AdminModules');

        $helper->back_url = $url;
        $helper->tpl_vars = [
            'fields_value' => $this->getConfigFormValues(), // Add values for your inputs
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        ];

        $form = [];
        $tvcms_obj = new TvcmsCategoryProductStatus();
        $show_fields = $tvcms_obj->fieldStatusInformation();

        if ($show_fields['main_status']) {
            $form[] = $this->tvcmsCategoryTitle();
        }

        if ($show_fields['record_form']) {
            $form[] = $this->tvcmsCategoryForm();
        }

        return $helper->generateForm($form);
    }

    protected function tvcmsCategoryForm()
    {
        $input = [];
        $tvcms_obj = new TvcmsCategoryProductStatus();
        $show_fields = $tvcms_obj->fieldStatusInformation();

        if (Tools::getValue('action')) {
            if ('edit' == Tools::getValue('action')) {
                $input[] = [
                        'type' => 'hidden',
                        'name' => 'id',
                    ];
            }
        }

        if ($show_fields['image']) {
            $input[] = [
                        'col' => 8,
                        'type' => 'tvcmscategory_img',
                        'name' => 'image',
                        'label' => $this->l('Category image'),
                    ];
        }

        $input[] = [
                        'col' => 8,
                        'type' => 'tvcmscategory_select',
                        'name' => 'id_category',
                        'label' => $this->l('Category'),
                        'lang' => true,
                    ];

        if ($show_fields['title']) {
            $input[] = [
                        'col' => 8,
                        'class' => 'tvcmsvategory-slider-custom-name',
                        'type' => 'text',
                        'name' => 'custom_title',
                        'label' => $this->l('Custom Name'),
                        'lang' => true,
                    ];
        }

        if ($show_fields['num_of_prod']) {
            $min = 1;
            $max = 12;
            $range = [];
            for ($i = $min; $i <= $max; ++$i) {
                $range[] = [
                    'id_option' => $i,
                    'name' => $i,
                    ];
            }
            $input[] = [
                        'col' => 8,
                        'type' => 'text',
                        'name' => 'num_of_prod',
                        'label' => $this->l('Number of Product'),

                        'type' => 'select',
                        'label' => $this->l('Number Of Product'),
                        'desc' => $this->l('Number of product which show in tab category products'),
                        'name' => 'num_of_prod',
                        'options' => [
                            'query' => $range,
                            'id' => 'id_option',
                            'name' => 'name',
                        ],
                    ];
        }

        $input[] = [
                        'col' => 8,
                        'type' => 'switch',
                        'name' => 'status',
                        'label' => $this->l('Status'),
                        'is_bool' => true,
                        'values' => [
                            [
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => $this->l('Show'),
                            ],
                            [
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->l('Hide'),
                            ],
                        ],
                    ];

        return [
            'form' => [
                'legend' => [
                'title' => $this->l('Category Slider'),
                'icon' => 'icon-image',
                ],
                'input' => $input,
                'submit' => [
                    'title' => $this->l('Save'),
                    'name' => 'submitTvcmsCategoryForm',
                ],
            ],
        ];
    }

    protected function tvcmsCategoryTitle()
    {
        $input = [];
        $tvcms_obj = new TvcmsCategoryProductStatus();
        $show_fields = $tvcms_obj->fieldStatusInformation();

        if ($show_fields['main_title']) {
            $input[] = [
                        'col' => 7,
                        'type' => 'text',
                        'name' => 'TVCMSCATEGORYPRODUCT_TITLE',
                        'label' => $this->l('Category Title'),
                        'lang' => true,
                    ];
        }

        if ($show_fields['main_product_title']) {
            $input[] = [
                        'col' => 7,
                        'type' => 'text',
                        'name' => 'TVCMSCATEGORYPRODUCT_PRODUCT_TITLE',
                        'label' => $this->l('Category Product Title'),
                        'lang' => true,
                    ];
        }

        if ($show_fields['main_sub_title']) {
            $input[] = [
                    'col' => 7,
                    'type' => 'text',
                    'name' => 'TVCMSCATEGORYPRODUCT_SUB_DESCRIPTION',
                    'label' => $this->l('Short Description'),
                    'lang' => true,
                ];
        }

        if ($show_fields['main_description']) {
            $input[] = [
                    'col' => 7,
                    'type' => 'text',
                    'name' => 'TVCMSCATEGORYPRODUCT_DESCRIPTION',
                    'label' => $this->l('Description'),
                    'lang' => true,
                ];
        }

        if ($show_fields['main_image']) {
            $input[] = [
                        'type' => 'image_file',
                        'name' => 'TVCMSCATEGORYPRODUCT_IMG',
                        'label' => $this->l('Title Image'),
                ];
        }

        return [
            'form' => [
                'legend' => [
                'title' => $this->l('Category Slider Title'),
                'icon' => 'icon-image',
                ],
                'input' => $input,
                'submit' => [
                    'title' => $this->l('Save'),
                    'name' => 'submitTvcmsCategoryTitle',
                ],
            ],
        ];
    }

    public function hookDisplayBackOfficeHeader()
    {
        $this->context->controller->addJqueryUI('ui.sortable');
        if ($this->name == Tools::getValue('configure')) {
            $this->context->controller->addJS($this->_path . 'views/js/back.js');
            $this->context->controller->addCSS($this->_path . 'views/css/back.css');
        }
    }

    public function hookHeader()
    {
        $tmp = $this->context->link->getModuleLink('tvcmscategoryproduct', 'default');
        Media::addJsDef(['gettvcmscategoryproductlink' => $tmp]);

        $useSSL = ((isset($this->ssl) && $this->ssl && Configuration::get('PS_SSL_ENABLED'))
            || Tools::usingSecureMode()) ? true : false;

        $protocol_content = ($useSSL) ? 'https://' : 'http://';

        $tmp = $protocol_content . Tools::getHttpHost() . __PS_BASE_URI__;

        Media::addJsDef(['baseDir' => $tmp]);

        // $link = $this->context->link->getModuleLink($tmp, 'frontajax', array(), null, null, null, true);
        // Media::addJsDef(array('front_ajax' => $link));

        $this->context->controller->addCSS($this->_path . 'views/css/front.css');
        $this->context->controller->addJS($this->_path . 'views/js/front.js');
    }

    public function getArrMainTitle($main_heading, $main_heading_data)
    {
        if (!$main_heading['main_title'] || empty($main_heading_data['title'])) {
            $main_heading['main_title'] = false;
        }
        if (!$main_heading['main_sub_title'] || empty($main_heading_data['short_desc'])) {
            $main_heading['main_sub_title'] = false;
        }
        if (!$main_heading['main_description'] || empty($main_heading_data['desc'])) {
            $main_heading['main_description'] = false;
        }
        if (!$main_heading['main_image'] || empty($main_heading_data['image'])) {
            $main_heading['main_image'] = false;
        }
        if (!$main_heading['main_title']
            && !$main_heading['main_sub_title']
            && !$main_heading['main_description']
            && !$main_heading['main_image']) {
            $main_heading['main_status'] = false;
        }

        return $main_heading;
    }

    public function showFrontSideResult()
    {
        $cookie = Context::getContext()->cookie;
        $id_lang = $cookie->id_lang;

        $tvcms_obj = new TvcmsCategoryProductStatus();
        $main_heading = $tvcms_obj->fieldStatusInformation();

        if ($main_heading['main_status']) {
            $main_heading_data = [];
            $main_heading_data['title'] = Configuration::get('TVCMSCATEGORYPRODUCT_TITLE', $id_lang);
            $main_heading_data['product_title'] = Configuration::get('TVCMSCATEGORYPRODUCT_PRODUCT_TITLE', $id_lang);
            $main_heading_data['short_desc'] = Configuration::get('TVCMSCATEGORYPRODUCT_SUB_DESCRIPTION', $id_lang);
            $main_heading_data['desc'] = Configuration::get('TVCMSCATEGORYPRODUCT_DESCRIPTION', $id_lang);
            $main_heading_data['image'] = Configuration::get('TVCMSCATEGORYPRODUCT_IMG', $id_lang);
            $main_heading = $this->getArrMainTitle($main_heading, $main_heading_data);
            $main_heading['data'] = $main_heading_data;
        }

        $disArrResult = [];
        $disArrResult['data'] = $this->showFrontData();
        $disArrResult['status'] = empty($disArrResult['data']) ? false : true;
        $disArrResult['path'] = _MODULE_DIR_ . $this->name . '/views/img/';
        $disArrResult['id_lang'] = $id_lang;
        $useSSL = ((isset($this->ssl) && $this->ssl && Configuration::get('PS_SSL_ENABLED'))
            || Tools::usingSecureMode()) ? true : false;
        $protocol_content = ($useSSL) ? 'https://' : 'http://';
        $baseurl = $protocol_content . Tools::getHttpHost() . __PS_BASE_URI__;
        $disArrResult['baseUrl'] = $baseurl;

        $this->context->smarty->assign('main_heading', $main_heading);
        $this->context->smarty->assign('dis_arr_result', $disArrResult);

        return $disArrResult['status'] ? true : false;
    }

    public function hookdisplayHome()
    {
        if (!Cache::isStored('tvcmscategoryproduct_display_home.tpl')) {
            $result = $this->showFrontSideResult();
            if ($result) {
                $output = $this->display(__FILE__, 'views/templates/front/display_home.tpl');
            } else {
                $output = '';
            }
            Cache::store('tvcmscategoryproduct_display_home.tpl', $output);
        }

        return Cache::retrieve('tvcmscategoryproduct_display_home.tpl');
    }

    // public function hookdisplayHome()
    // {
    //     $cookie = Context::getContext()->cookie;
    //     $id_lang = $cookie->id_lang;
    //     $result = array();

    //     if (!Cache::isStored('tvcmscategoryproduct_display_home.tpl')) {
    //         $result = $this->showData();

    //         $path = _MODULE_DIR_.$this->name."/views/img/";
    //         $this->context->smarty->assign("path", $path);

    //         $tvcms_obj = new TvcmsCategoryProductStatus();
    //         $show_fields = $tvcms_obj->fieldStatusInformation();

    //         $this->context->smarty->assign('arr_result', $result);
    //         $this->context->smarty->assign('show_fields', $show_fields);
    //         $this->context->smarty->assign('id_lang', $id_lang);
    //         $output = $this->display(__FILE__, 'views/templates/front/display_home.tpl');
    //         Cache::store('tvcmscategoryproduct_display_home.tpl', $output);
    //     }

    //     return Cache::retrieve('tvcmscategoryproduct_display_home.tpl');
    // }

    public function getProductsUsingCategory($category_id, $num_of_prod)
    {
        $category = new Category((int) $category_id);

        $searchProvider = new CategoryProductSearchProvider(
            $this->context->getTranslator(),
            $category
        );

        $context = new ProductSearchContext($this->context);

        $query = new ProductSearchQuery();

        $nProducts = $num_of_prod;

        $query
            ->setResultsPerPage($nProducts)
            ->setPage(1);

        $query->setSortOrder(new SortOrder('product', 'position', 'asc'));

        $result = $searchProvider->runQuery(
            $context,
            $query
        );

        $assembler = new ProductAssembler($this->context);

        $presenterFactory = new ProductPresenterFactory($this->context);
        $presentationSettings = $presenterFactory->getPresentationSettings();
        $presenter = new ProductListingPresenter(
            new ImageRetriever(
                $this->context->link
            ),
            $this->context->link,
            new PriceFormatter(),
            new ProductColorsRetriever(),
            $this->context->getTranslator()
        );

        $product_list = [];

        foreach ($result->getProducts() as $rawProduct) {
            $product_list[] = $presenter->present(
                $presentationSettings,
                $assembler->assembleProduct($rawProduct),
                $this->context->language
            );
        }

        $cart_page_url = $this->context->link->getPageLink(
            'cart',
            null,
            $this->context->language->id,
            null,
            false,
            null,
            true
        );

        $imageRetriever = new ImageRetriever($this->context->link);
        $no_picture_image = $imageRetriever->getNoPictureImage($this->context->language);

        $img = $no_picture_image['bySize'][ImageType::getFormattedName('home')]['url'];
        $this->context->smarty->assign('no_picture_image', $img);

        $static_token = Tools::getToken(false);
        $img_url = _THEME_IMG_DIR_;
        $this->context->smarty->assign('img_url', $img_url);
        $this->context->smarty->assign('cart_page_url', $cart_page_url);
        $this->context->smarty->assign('static_token', $static_token);
        $this->context->smarty->assign('product_list', $product_list);
        $this->context->smarty->assign('num_of_prod', $num_of_prod);

        return $this->display(__FILE__, './views/templates/front/show_product.tpl');
    }
}
