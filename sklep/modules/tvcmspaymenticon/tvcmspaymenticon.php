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

include_once 'classes/tvcmspaymenticon_status.class.php';
include_once 'classes/tvcmspaymenticon_image_upload.class.php';

class TvcmsPaymentIcon extends Module
{
    public $id_shop_group = '';

    public $id_shop = '';

    public function __construct()
    {
        $this->name = 'tvcmspaymenticon';
        $this->tab = 'front_office_features';
        $this->version = '4.0.2';
        $this->author = 'ThemeVolty';
        $this->need_instance = 0;

        $this->bootstrap = true;
        parent::__construct();

        $this->displayName = $this->l('ThemeVolty - Payment Icon');
        $this->description = $this->l('Its Show Payment Icon on Front Side');

        $this->ps_versions_compliancy = ['min' => '1.7', 'max' => _PS_VERSION_];
        $this->module_key = '';

        $this->confirmUninstall = $this->l('Warning: all the data saved in your database will be deleted.' .
            ' Are you sure you want uninstall this module?');

        $this->id_shop_group = (int) Shop::getContextShopGroupID();
        $this->id_shop = (int) Context::getContext()->shop->id;
    }

    public function install()
    {
        $this->installTab();
        $this->createTable();
        // $this->createDefaultData();

        return parent::install()
            && $this->registerHook('displayBackOfficeHeader')
            && $this->registerHook('displayHeader')
            && $this->registerHook('displayPaymentIcon');
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
            $tab->name[$lang['id_lang']] = 'Payment Icon';
        }
        $tab->id_parent = $parentTab_2->id;
        $tab->module = $this->name;
        $response &= $tab->add();

        return $response;
    }

    public function createDefaultData()
    {
        $this->reset();
        $num_of_data = 1;
        $this->createVariable();
        $this->createTable();
        $this->insertSmapleData($num_of_data);
    }

    public function createVariable()
    {
        $languages = Language::getLanguages();
        $result = [];
        foreach ($languages as $lang) {
            $result['TVCMSPAYMENTICON_TITLE'][$lang['id_lang']] = 'Main Title';
            $result['TVCMSPAYMENTICON_SUB_DESCRIPTION'][$lang['id_lang']] = 'Sub Description';
            $result['TVCMSPAYMENTICON_DESCRIPTION'][$lang['id_lang']] = 'Description';
            $result['TVCMSPAYMENTICON_IMG'][$lang['id_lang']] = 'demo_main_img_1.jpg';
        }

        Configuration::updateValue('TVCMSPAYMENTICON_TITLE', $result['TVCMSPAYMENTICON_TITLE']);
        Configuration::updateValue('TVCMSPAYMENTICON_SUB_DESCRIPTION', $result['TVCMSPAYMENTICON_SUB_DESCRIPTION']);
        Configuration::updateValue('TVCMSPAYMENTICON_DESCRIPTION', $result['TVCMSPAYMENTICON_DESCRIPTION']);
        Configuration::updateValue('TVCMSPAYMENTICON_IMG', $result['TVCMSPAYMENTICON_IMG']);
    }

    public function createTable()
    {
        $create_table = [];
        $create_table[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'tvcmspaymenticon` (
                        `id_tvcmspaymenticon` int(11) AUTO_INCREMENT PRIMARY KEY,
                        `position` int(11),
                        `id_shop_group` int(11),
                        `id_shop` int(11),
                        `image` VARCHAR(100),
                        `link` VARCHAR(255),
                        `status` varchar(3)
                    ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

        $create_table[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'tvcmspaymenticon_lang` (
                        `id_tvcmspaymenticon_lang` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
                        `id_tvcmspaymenticon` INT NOT NULL,
                        `id_shop_group` int(11),
                        `id_shop` int(11),
                        `id_lang` INT NOT NULL,
                        `title` VARCHAR(255)
                    ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

        foreach ($create_table as $table) {
            Db::getInstance()->execute($table);
        }
    }

    public function insertSmapleData($num_of_data)
    {
        $demo_data = [];
        $languages = Language::getLanguages();

        for ($i = 1; $i <= $num_of_data; ++$i) {
            $demo_data[] = 'INSERT INTO 
                            `' . _DB_PREFIX_ . 'tvcmspaymenticon`
                        SET 
                            `id_tvcmspaymenticon` = ' . $i . ',
                            `position` = ' . $i . ',
                            `id_shop_group` = ' . (int) $this->id_shop_group . ',
                            `id_shop` = ' . (int) $this->id_shop . ',
                            `image` = \'demo_img_' . $i . '.png\',
                            `link` = \'#\',
                            `status` = \'1\';';
            foreach ($languages as $lang) {
                $demo_data[] = 'INSERT INTO
                                `' . _DB_PREFIX_ . 'tvcmspaymenticon_lang`
                            SET
                                `id_tvcmspaymenticon_lang` = NULL,
                                `id_tvcmspaymenticon` = ' . $i . ',
                                `id_shop_group` = ' . (int) $this->id_shop_group . ',
                                `id_shop` = ' . (int) $this->id_shop . ',
                                `id_lang` = ' . (int) $lang['id_lang'] . ',
                                `title` = \'test ' . $i . '\';';
            }
        }
        foreach ($demo_data as $data) {
            Db::getInstance()->execute($data);
        }
    }

    public function maxId()
    {
        $select_data = 'SELECT MAX(id_tvcmspaymenticon) as max_id FROM `' . _DB_PREFIX_ . 'tvcmspaymenticon`';

        $ans = Db::getInstance()->executeS($select_data);

        return $ans[0]['max_id'];
    }

    public function selectAllLangIdById($id_tvcmspaymenticon)
    {
        $select_data = 'SELECT 
                            id_lang 
                        FROM 
                            `' . _DB_PREFIX_ . 'tvcmspaymenticon_lang` 
                        WHERE 
                            `id_shop_group` = ' . (int) $this->id_shop_group . '
                            AND `id_shop` = ' . (int) $this->id_shop . '
                            AND id_tvcmspaymenticon = ' . (int) $id_tvcmspaymenticon;

        $ans = Db::getInstance()->executeS($select_data);
        $return = [];
        foreach ($ans as $a) {
            $return[] = $a['id_lang'];
        }

        return $return;
    }

    public function insertData($data)
    {
        $result = [];
        $insert_data = [];
        if ($data['id']) {
            $id = $data['id'];
            $insert_data[] = 'UPDATE 
                                `' . _DB_PREFIX_ . 'tvcmspaymenticon` 
                            SET
                                `image` = \'' . pSQL($data['image']) . '\',
                                `link` = \'' . pSQL($data['link']) . '\',
                                `status` = ' . (int) $data['status'] . '

                            WHERE
                                `id_shop_group` = ' . (int) $this->id_shop_group . '
                                AND `id_shop` = ' . (int) $this->id_shop . '
                                AND `id_tvcmspaymenticon` = ' . (int) $id . ';';
            $result = $this->selectAllLangIdById($id);

            $languages = Language::getLanguages();
            $i = 0;
            foreach ($languages as $lang) {
                if (in_array($lang['id_lang'], $result)) {
                    $insert_data[] = 'UPDATE
                                        `' . _DB_PREFIX_ . 'tvcmspaymenticon_lang`
                                    SET
                                        `title` = \'' . pSQL($data['lang_info'][$i]['title']) . '\'
                                    WHERE
                                            `id_shop_group` = ' . (int) $this->id_shop_group . '
                                        AND 
                                            `id_shop` = ' . (int) $this->id_shop . '
                                        AND
                                            `id_tvcmspaymenticon` = ' . (int) $id . '
                                        AND
                                            `id_lang` = ' . (int) $lang['id_lang'] . ';';
                } else {
                    $insert_data[] = 'INSERT INTO
                                        `' . _DB_PREFIX_ . 'tvcmspaymenticon_lang`
                                    SET
                                        `id_tvcmspaymenticon_lang` = NULL,
                                        `id_tvcmspaymenticon` = ' . (int) $id . ',
                                        `id_shop_group` = ' . (int) $this->id_shop_group . ',
                                        `id_shop` = ' . (int) $this->id_shop . ',
                                        `id_lang` = ' . (int) $lang['id_lang'] . ',
                                        `title` = \'' . pSQL($data['lang_info'][$i]['title']) . '\';';
                }
                ++$i;
            }
        } else {
            $max_id = $this->maxId();
            $new_id = $max_id + 1;
            $insert_data = [];

            $insert_data[] = 'INSERT INTO 
                                `' . _DB_PREFIX_ . 'tvcmspaymenticon` 
                            SET
                                `id_tvcmspaymenticon` = ' . (int) $new_id . ',
                                `position` = ' . (int) $new_id . ',
                                `id_shop_group` = ' . (int) $this->id_shop_group . ',
                                `id_shop` = ' . (int) $this->id_shop . ',
                                `image` = \'' . pSQL($data['image']) . '\',
                                `link` = \'' . pSQL($data['link']) . '\',
                                `status` = ' . (int) $data['status'] . ';';

            foreach ($data['lang_info'] as $lang) {
                $insert_data[] = 'INSERT INTO
                                    `' . _DB_PREFIX_ . 'tvcmspaymenticon_lang`
                                SET
                                    `id_tvcmspaymenticon_lang` = NULL,
                                    `id_tvcmspaymenticon` = ' . (int) $new_id . ',
                                    `id_shop_group` = ' . (int) $this->id_shop_group . ',
                                    `id_shop` = ' . (int) $this->id_shop . ',
                                    `id_lang` = ' . (int) $lang['id_lang'] . ',
                                    `title` = \'' . pSQL($lang['title']) . '\';';
            }
        }

        foreach ($insert_data as $data) {
            Db::getInstance()->execute($data);
        }
    }

    public function showAdminData()
    {
        $result = [];
        $return_data = [];
        $default_lang_id = $this->context->language->id;

        $select_data = 'SELECT * FROM `' . _DB_PREFIX_ . 'tvcmspaymenticon`'
             . ' WHERE `id_shop_group` = ' . (int) $this->id_shop_group . ' AND `id_shop` = ' . (int) $this->id_shop
             . ' ORDER BY `position`;';

        $result['tvcmspaymenticon'] = Db::getInstance()->executeS($select_data);

        $select_data = 'SELECT * FROM `' . _DB_PREFIX_ . 'tvcmspaymenticon_lang`'
             . ' WHERE `id_shop_group` = ' . (int) $this->id_shop_group . ' AND `id_shop` = ' . (int) $this->id_shop . ';';

        $result['tvcmspaymenticon_lang'] = Db::getInstance()->executeS($select_data);

        foreach ($result['tvcmspaymenticon'] as $key => $data) {
            $return_data[$key]['id'] = $data['id_tvcmspaymenticon'];
            $id = $data['id_tvcmspaymenticon'];

            foreach ($result['tvcmspaymenticon_lang'] as $lang) {
                if ($default_lang_id == $lang['id_lang'] && $id == $lang['id_tvcmspaymenticon']) {
                    // $lang_id = $lang['id_lang'];
                    $return_data[$key]['id_lang'] = $lang['id_lang'];
                    $return_data[$key]['title'] = $lang['title'];
                }
            }

            $return_data[$key]['image'] = $data['image'];
            $return_data[$key]['link'] = $data['link'];
            $return_data[$key]['status'] = $data['status'];
        }

        return $return_data;
    }

    public function showData($id = null)
    {
        $select_data = [];
        $result = [];
        $return_data = [];

        $select_data = '';
        $select_data .= 'SELECT * FROM `' . _DB_PREFIX_ . 'tvcmspaymenticon` 
                WHERE 
                `id_shop_group` = ' . (int) $this->id_shop_group
                 . ' AND `id_shop` = ' . (int) $this->id_shop;
        if ($id) {
            $select_data .= ' AND `id_tvcmspaymenticon` = ' . (int) $id;
        } else {
            $select_data .= ' ORDER BY `position`';
        }

        $result['tvcmspaymenticon'] = Db::getInstance()->executeS($select_data);

        $select_data = '';
        $select_data .= 'SELECT * FROM `' . _DB_PREFIX_ . 'tvcmspaymenticon_lang`'
             . ' WHERE `id_shop_group` = ' . (int) $this->id_shop_group . ' AND `id_shop` = ' . (int) $this->id_shop;
        if ($id) {
            $select_data .= ' AND id_tvcmspaymenticon = ' . (int) $id;
        }

        $result['tvcmspaymenticon_lang'] = Db::getInstance()->executeS($select_data);

        foreach ($result['tvcmspaymenticon'] as $key => $data) {
            $return_data[$key]['id'] = $data['id_tvcmspaymenticon'];
            $id = $data['id_tvcmspaymenticon'];
            foreach ($result['tvcmspaymenticon_lang'] as $lang) {
                if ($id == $lang['id_tvcmspaymenticon']) {
                    $lang_id = $lang['id_lang'];
                    $return_data[$key]['lang_info'][$lang_id]['id_lang'] = $lang['id_lang'];
                    $return_data[$key]['lang_info'][$lang_id]['title'] = $lang['title'];
                }
            }
            $return_data[$key]['image'] = $data['image'];
            $return_data[$key]['link'] = $data['link'];
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
                mainTable.id_tvcmspaymenticon AS code,
                mainTable.image,
                mainTable.link,
                subTable.title
            FROM 
                `' . _DB_PREFIX_ . 'tvcmspaymenticon` mainTable
            LEFT JOIN
                ' . _DB_PREFIX_ . 'tvcmspaymenticon_lang subTable
            ON
                mainTable.id_tvcmspaymenticon = subTable.id_tvcmspaymenticon
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

    public function uninstall()
    {
        $this->uninstallTab();
        $this->deleteVariable();
        $this->deleteTable();

        return parent::uninstall();
    }

    public function deleteVariable()
    {
        Configuration::deleteByName('TVCMSPAYMENTICON_TITLE');
        Configuration::deleteByName('TVCMSPAYMENTICON_SUB_DESCRIPTION');
        Configuration::deleteByName('TVCMSPAYMENTICON_DESCRIPTION');
        Configuration::deleteByName('TVCMSPAYMENTICON_IMG');
    }

    public function deleteRecord($id)
    {
        $this->removeImage($id);

        $delete_data = [];

        $delete_data[] = 'DELETE FROM `' . _DB_PREFIX_ . 'tvcmspaymenticon`
            WHERE 
                    `id_shop_group` = ' . (int) $this->id_shop_group . '
                AND 
                    `id_shop` = ' . (int) $this->id_shop . ' 
                AND 
                    `id_tvcmspaymenticon` = ' . (int) $id;

        $delete_data[] = 'DELETE FROM `' . _DB_PREFIX_ . 'tvcmspaymenticon_lang` 
            WHERE 
                    `id_shop_group` = ' . (int) $this->id_shop_group . '
                AND 
                    `id_shop` = ' . (int) $this->id_shop . ' 
                AND 
                    id_tvcmspaymenticon = ' . (int) $id;

        foreach ($delete_data as $data) {
            Db::getInstance()->execute($data);
        }
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

    public function deleteTable()
    {
        $delete_table = [];
        $delete_table[] = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'tvcmspaymenticon`';
        $delete_table[] = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'tvcmspaymenticon_lang`';

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

    public function reset()
    {
        $trn_tbl = [];
        $trn_tbl[] = 'TRUNCATE `' . _DB_PREFIX_ . 'tvcmspaymenticon`';
        $trn_tbl[] = 'TRUNCATE `' . _DB_PREFIX_ . 'tvcmspaymenticon_lang`';
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
        $output = $message . $this->renderForm() . $this->showAdminResult();

        return $output;
    }

    public function postProcess()
    {
        $message = '';
        $result = [];

        if (Tools::getValue('action') && '0' == Tools::getValue('tvinstalldata')) {
            if ('remove' == Tools::getValue('action')) {
                $id = Tools::getValue('id');
                $this->deleteRecord($id);
                $message .= $this->displayConfirmation($this->l('Record is Deleted.'));
            }
        }

        if (Tools::isSubmit('submittvcmsPaymentIconForm') && '0' == Tools::getValue('tvinstalldata')) {
            $old_file = '';
            $no_image_selected = false;
            $result['id'] = '';
            if (Tools::getValue('id')) {
                $result['id'] = Tools::getValue('id');
                $id = Tools::getValue('id');
                $res = $this->showData($id);
                $old_file = $res[0]['image'];
            }

            $tvcms_obj = new TvcmsPaymentIconStatus();
            $show_fields = $tvcms_obj->fieldStatusInformation();
            $result['image'] = '';
            if ($show_fields['image']) {
                if (!empty($_FILES['image']['name'])) {
                    $new_file = $_FILES['image'];
                    $obj_image = new TvcmsPaymentIconImageUpload();
                    $ans = $obj_image->imageUploading($new_file, $old_file);
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
                        $message .= $this->displayError($this->l('Please Select Image.'));
                        $no_image_selected = true;
                    }
                }
            }

            if (!$no_image_selected) {
                $result['link'] = Tools::getValue('link');
                $result['status'] = Tools::getValue('status');

                $languages = Language::getLanguages();
                $i = 0;
                foreach ($languages as $lang) {
                    $result['lang_info'][$i]['id_lang'] = $lang['id_lang'];

                    $tmp = Tools::getValue('title_' . $lang['id_lang']);
                    $result['lang_info'][$i]['title'] = addslashes($tmp);
                    ++$i;
                }

                if (empty($result['lang_info'][0]['title']) && empty($result['link'])) {
                    $message .= $this->displayError($this->l('Please enter title & link.'));
                } elseif (empty($result['link'])) {
                    $message .= $this->displayError($this->l('Please enter link.'));
                } elseif (empty($result['lang_info'][0]['title'])) {
                    $message .= $this->displayError($this->l('Please enter title.'));
                } else {
                    $this->insertData($result);
                    $message .= $this->displayConfirmation($this->l('Record save successfully.'));
                }
                $this->clearCustomSmartyCache('tvcmspaymenticon_display_home.tpl');
            }
        }

        if (Tools::isSubmit('submittvcmsPaymentIconMainTitleForm') && '0' == Tools::getValue('tvinstalldata')) {
            $languages = Language::getLanguages();
            $obj_image = new TvcmsPaymentIconImageUpload();
            foreach ($languages as $lang) {
                if (!empty($_FILES['TVCMSPAYMENTICON_IMG_' . $lang['id_lang']]['name'])) {
                    $old_file = Configuration::get('TVCMSPAYMENTICON_IMG', $lang['id_lang']);
                    $new_file = $_FILES['TVCMSPAYMENTICON_IMG_' . $lang['id_lang']];
                    $ans = $obj_image->imageUploading($new_file, $old_file);
                    if ($ans['success']) {
                        $result['TVCMSPAYMENTICON_IMG'][$lang['id_lang']] = $ans['name'];
                    } else {
                        $message .= $ans['error'];
                        $result['TVCMSPAYMENTICON_IMG'][$lang['id_lang']] = $old_file;
                    }
                } else {
                    $old_file = Configuration::get('TVCMSPAYMENTICON_IMG', $lang['id_lang']);
                    $result['TVCMSPAYMENTICON_IMG'][$lang['id_lang']] = $old_file;
                }

                $tmp = Tools::getValue('TVCMSPAYMENTICON_TITLE_' . $lang['id_lang']);
                $result['TVCMSPAYMENTICON_TITLE'][$lang['id_lang']] = $tmp;

                $tmp = Tools::getValue('TVCMSPAYMENTICON_SUB_DESCRIPTION_' . $lang['id_lang']);
                $result['TVCMSPAYMENTICON_SUB_DESCRIPTION'][$lang['id_lang']] = $tmp;

                $tmp = Tools::getValue('TVCMSPAYMENTICON_DESCRIPTION_' . $lang['id_lang']);
                $result['TVCMSPAYMENTICON_DESCRIPTION'][$lang['id_lang']] = $tmp;
            }
            Configuration::updateValue('TVCMSPAYMENTICON_IMG', $result['TVCMSPAYMENTICON_IMG']);
            Configuration::updateValue('TVCMSPAYMENTICON_TITLE', $result['TVCMSPAYMENTICON_TITLE']);
            $tmp = $result['TVCMSPAYMENTICON_SUB_DESCRIPTION'];
            Configuration::updateValue('TVCMSPAYMENTICON_SUB_DESCRIPTION', $tmp);

            $tmp = $result['TVCMSPAYMENTICON_DESCRIPTION'];
            Configuration::updateValue('TVCMSPAYMENTICON_DESCRIPTION', $tmp);
            $message .= $this->displayConfirmation($this->l('Main Title is Updated.'));
        }

        return $message;
    }

    public function clearCustomSmartyCache($cache_id)
    {
        if (Cache::isStored($cache_id)) {
            Cache::clean($cache_id);
        }
    }

    protected function renderForm()
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

        $helper->tpl_vars = [
            'fields_value' => $this->getConfigFormValues(), // Add values for your inputs
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        ];

        $form = [];

        $tvcms_obj = new TvcmsPaymentIconStatus();
        $show_fields = $tvcms_obj->fieldStatusInformation();
        if ($show_fields['main_form']) {
            $form[] = $this->tvcmsPaymentIconMainTitleForm();
        }

        if ($show_fields['record_form']) {
            $form[] = $this->tvcmsPaymentIconForm();
        }

        return $helper->generateForm($form);
    }

    protected function tvcmsPaymentIconMainTitleForm()
    {
        $tvcms_obj = new TvcmsPaymentIconStatus();
        $show_fields = $tvcms_obj->fieldStatusInformation();
        $input = [];

        $input[] = [
                'col' => 12,
                'type' => 'BtnInstallData',
                'name' => 'BtnInstallData',
                'label' => '',
            ];
        if ($show_fields['main_title']) {
            $input[] = [
                    'col' => 7,
                    'type' => 'text',
                    'name' => 'TVCMSPAYMENTICON_TITLE',
                    'label' => $this->l('Main Title'),
                    'lang' => true,
                ];
        }

        if ($show_fields['main_short_description']) {
            $input[] = [
                    'col' => 7,
                    'type' => 'text',
                    'name' => 'TVCMSPAYMENTICON_SUB_DESCRIPTION',
                    'label' => $this->l('Short Description'),
                    'lang' => true,
                ];
        }

        if ($show_fields['main_description']) {
            $input[] = [
                    'col' => 7,
                    'type' => 'text',
                    'name' => 'TVCMSPAYMENTICON_DESCRIPTION',
                    'label' => $this->l('Description'),
                    'lang' => true,
                ];
        }

        if ($show_fields['main_image']) {
            $input[] = [
                        'type' => 'image_file',
                        'name' => 'TVCMSPAYMENTICON_IMG',
                        'label' => $this->l('Image'),
                ];
        }

        return [
            'form' => [
                'legend' => [
                'title' => $this->l('Main Title'),
                'icon' => 'icon-support',
                ],
                'input' => $input,
                'submit' => [
                    'title' => $this->l('Save'),
                    'name' => 'submittvcmsPaymentIconMainTitleForm',
                ],
            ],
        ];
    }

    protected function tvcmsPaymentIconForm()
    {
        $tvcms_obj = new TvcmsPaymentIconStatus();
        $show_fields = $tvcms_obj->fieldStatusInformation();
        $input = [];

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
                        'type' => 'tvcmspaymenticon_img',
                        'name' => 'image',
                        'label' => $this->l('Image'),
                    ];
        }

        if ($show_fields['title']) {
            $input[] = [
                    'col' => 7,
                    'type' => 'text',
                    'name' => 'title',
                    'label' => $this->l('Title'),
                    'lang' => true,
                ];
        }

        if ($show_fields['link']) {
            $input[] = [
                    'col' => 7,
                    'type' => 'text',
                    'name' => 'link',
                    'label' => $this->l('Link'),
                    'desc' => $this->l('You must write full link. Ex:- https://www.demo.com/'),
                ];
        }

        if ($show_fields['status']) {
            $input[] = [
                        'type' => 'switch',
                        'label' => $this->l('Status'),
                        'name' => 'status',
                        'desc' => $this->l('Hide or Show Icons.'),
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
        }

        return [
            'form' => [
                'legend' => [
                'title' => $this->l('Payment Icon'),
                'icon' => 'icon-support',
                ],
                'input' => $input,
                'submit' => [
                    'title' => $this->l('Save'),
                    'name' => 'submittvcmsPaymentIconForm',
                ],
            ],
        ];
    }

    public function hookDisplayBackOfficeHeader()
    {
        if ($this->name == Tools::getValue('configure')) {
            $this->context->controller->addJS($this->_path . 'views/js/back.js');
            $this->context->controller->addCSS($this->_path . 'views/css/back.css');
        }
    }// hookDisplayBackOfficeHeader()

    protected function getConfigFormValues()
    {
        $fields = [];
        $languages = Language::getLanguages();
        $fields['id'] = '';
        foreach ($languages as $lang) {
            $fields['title'][$lang['id_lang']] = '';
        }
        $fields['image'] = '';
        $fields['link'] = '';
        $fields['status'] = '';

        if (Tools::getValue('action')) {
            if ('edit' == Tools::getValue('action')) {
                $id = Tools::getValue('id');
                $array_list = $this->showData($id);
                $array_list = $array_list[0];

                $fields['id'] = $id;
                foreach ($languages as $lang) {
                    if (!empty($array_list['lang_info'][$lang['id_lang']])) {
                        $fields['title'][$lang['id_lang']] = $array_list['lang_info'][$lang['id_lang']]['title'];
                    }
                }
                $fields['image'] = $array_list['image'];
                $fields['link'] = $array_list['link'];
                $fields['status'] = $array_list['status'];
            }
        }

        foreach ($languages as $lang) {
            $tmp = Configuration::get('TVCMSPAYMENTICON_TITLE', $lang['id_lang']);
            $fields['TVCMSPAYMENTICON_TITLE'][$lang['id_lang']] = $tmp;

            $tmp = Configuration::get('TVCMSPAYMENTICON_SUB_DESCRIPTION', $lang['id_lang']);
            $fields['TVCMSPAYMENTICON_SUB_DESCRIPTION'][$lang['id_lang']] = $tmp;

            $tmp = Configuration::get('TVCMSPAYMENTICON_DESCRIPTION', $lang['id_lang']);
            $fields['TVCMSPAYMENTICON_DESCRIPTION'][$lang['id_lang']] = $tmp;

            $tmp = Configuration::get('TVCMSPAYMENTICON_IMG', $lang['id_lang']);
            $fields['TVCMSPAYMENTICON_IMG'][$lang['id_lang']] = $tmp;
        }

        $path = _MODULE_DIR_ . $this->name . '/views/img/';
        $this->context->smarty->assign('path', $path);

        return $fields;
    }

    public function showAdminResult()
    {
        $tvcms_obj = new TvcmsPaymentIconStatus();
        $show_fields = $tvcms_obj->fieldStatusInformation();
        $default_lang_id = $this->context->language->id;

        $array_list = $this->showAdminData();

        // if (empty($array_list)) {
        //     return '';
        // }

        $this->context->smarty->assign('array_list', $array_list);
        $this->context->smarty->assign('show_fields', $show_fields);
        $this->context->smarty->assign('default_lang_id', $default_lang_id);

        return $this->display(__FILE__, 'views/templates/admin/tvcmspaymenticon_manage.tpl');
    }

    public function getArrMainTitle($main_heading, $main_heading_data)
    {
        if (!$main_heading['main_title'] || empty($main_heading_data['title'])) {
            $main_heading['main_title'] = false;
        }
        if (!$main_heading['main_short_description'] || empty($main_heading_data['short_desc'])) {
            $main_heading['main_short_description'] = false;
        }
        if (!$main_heading['main_description'] || empty($main_heading_data['desc'])) {
            $main_heading['main_description'] = false;
        }
        if (!$main_heading['main_image'] || empty($main_heading_data['image'])) {
            $main_heading['main_image'] = false;
        }
        if (!$main_heading['main_title']
            && !$main_heading['main_short_description']
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

        $tvcms_obj = new TvcmsPaymentIconStatus();
        $main_heading = $tvcms_obj->fieldStatusInformation();

        if ($main_heading['main_status']) {
            $main_heading_data = [];
            $main_heading_data['title'] = Configuration::get('TVCMSPAYMENTICON_TITLE', $id_lang);
            $main_heading_data['short_desc'] = Configuration::get('TVCMSPAYMENTICON_SUB_DESCRIPTION', $id_lang);
            $main_heading_data['desc'] = Configuration::get('TVCMSPAYMENTICON_DESCRIPTION', $id_lang);
            $main_heading_data['image'] = Configuration::get('TVCMSPAYMENTICON_IMG', $id_lang);
            $main_heading = $this->getArrMainTitle($main_heading, $main_heading_data);
            $main_heading['data'] = $main_heading_data;
        }

        $disArrResult = [];
        $disArrResult['data'] = $this->showFrontData();
        $disArrResult['status'] = empty($disArrResult['data']) ? false : true;
        $disArrResult['path'] = _MODULE_DIR_ . $this->name . '/views/img/';
        $disArrResult['id_lang'] = $id_lang;

        $this->context->smarty->assign('main_heading', $main_heading);
        $this->context->smarty->assign('dis_arr_result', $disArrResult);

        return $disArrResult['status'] ? true : false;
    }

    public function hookdisplayHeader()
    {
        // $this->context->controller->addJS($this->_path . 'views/js/front.js');
        // $this->context->controller->addCSS($this->_path . 'views/css/front.css');
    }

    public function hookDisplayTopColumn()
    {
        return $this->hookdisplayPaymentIcon();
    }

    public function hookDisplayFooterBefore()
    {
        return $this->hookdisplayPaymentIcon();
    }

    public function hookdisplayPaymentIcon()
    {
        if (!Cache::isStored('tvcmspaymenticon_display_home.tpl')) {
            $result = $this->showFrontSideResult();
            if ($result) {
                $output = $this->display(__FILE__, 'views/templates/front/display_home.tpl');
            } else {
                $output = '';
            }

            Cache::store('tvcmspaymenticon_display_home.tpl', $output);
        }

        return Cache::retrieve('tvcmspaymenticon_display_home.tpl');
    }
}
