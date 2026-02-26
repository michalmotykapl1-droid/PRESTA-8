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
include_once 'tvcmscustomsetting_status.class.php';
class TvcmsCustomSettingDbUpgrade
{
    public function __construct()
    {
        $this->TVDEBUG_DB = false;
        $this->message = '';
    }

    public function DBUpgrade360($pthis)
    {
        // v.4.0.0 Upgraded
        // tvcmsslider
        try {
            $sql = 'select 1 from `' . _DB_PREFIX_ . 'tvcmsslider_slides_lang` LIMIT 1';
            if (Db::getInstance()->Execute($sql)) {
                $check = 'DESCRIBE `' . _DB_PREFIX_ . 'tvcmsslider_slides_lang`';
                $columns = Db::getInstance()->ExecuteS($check);
                $foundA = false;
                foreach ($columns as $col) {
                    if ('width' != $col['Field'] && 'height' != $col['Field']) {
                        $foundA = true;
                    }
                }
                if ($foundA) {
                    $sql =
                        'ALTER TABLE `' .
                        _DB_PREFIX_ .
                        'tvcmsslider_slides_lang`
                              ADD COLUMN width int(10) unsigned NULL,
                              ADD COLUMN height int(10) unsigned NULL';
                    Db::getInstance()->Execute($sql);
                }

                if (Db::getInstance()->Execute($sql)) {
                    $sql =
                        'SELECT 
                    id_tvcmsslider_slides,
                    id_lang,
                    image
                    FROM `' .
                        _DB_PREFIX_ .
                        'tvcmsslider_slides_lang`';

                    $data = Db::getInstance()->ExecuteS($sql);
                    $ImageSizePath = _PS_MODULE_DIR_ . 'tvcmsslider/views/img/';
                    foreach ($data as $value) {
                        $id_tvcmsslider_slides = $value['id_tvcmsslider_slides'];
                        $id_lang = $value['id_lang'];
                        $ImageName = $value['image'];
                        if (file_exists($ImageSizePath . $ImageName)) {
                            $imagedata = getimagesize($ImageSizePath . $ImageName);
                            $width = $imagedata[0];
                            $height = $imagedata[1];

                            $sql =
                                'UPDATE `' .
                                _DB_PREFIX_ .
                                'tvcmsslider_slides_lang`
                             SET width = ' .
                                (int) $width .
                                ',
                             height = ' .
                                (int) $height .
                                '
                             WHERE
                             id_tvcmsslider_slides =' .
                                (int) $id_tvcmsslider_slides .
                                '
                             AND
                             id_lang = ' .
                                (int) $id_lang;

                            // if (!Db::getInstance()->Execute($sql)) {
                            //     if ($this->TVDEBUG_DB) {
                            //         $this->message .= $pthis->displayError($pthis->l("TVDEBUG_DB : tvcmsslider_slides_lang Upgradation Failed"));
                            //     }
                            // }
                        }
                    }
                }
            }
        } catch (Exception $e) {
            if ($this->TVDEBUG_DB) {
                $this->message .= $pthis->displayError($pthis->l('TVDEBUG_DB : 1tvcmsslider_slides_lang table not Found!!!'));
            }
        }

        try {
            $sql = 'select 1 from `' . _DB_PREFIX_ . 'tvcmsslider_slides_lang` LIMIT 1';
            if (Db::getInstance()->Execute($sql)) {
                $check = 'DESCRIBE `' . _DB_PREFIX_ . 'tvcmsslider_slides_lang`';
                $columns = Db::getInstance()->ExecuteS($check);
                $foundB = false;
                foreach ($columns as $col) {
                    if ('video_width' != $col['Field'] && 'video_height' != $col['Field'] && 'ivr_value' != $col['Field']) {
                        $foundB = true;
                    }
                }
                if ($foundB) {
                    $sql =
                        'ALTER TABLE `' .
                        _DB_PREFIX_ .
                        'tvcmsslider_slides_lang`
                   ADD COLUMN video_width int(10) unsigned NULL DEFAULT  \'1011\',
                   ADD COLUMN video_height int(10) unsigned NULL DEFAULT \'427\',
                   ADD COLUMN ivr_value varchar(255) NOT NULL DEFAULT \'image_upload\'';
                    Db::getInstance()->Execute($sql);
                }
            }
        } catch (Exception $e) {
            if ($this->TVDEBUG_DB) {
                $this->message .= $pthis->displayError($pthis->l('TVDEBUG_DB : 2tvcmsslider_slides_lang table not Found!!!'));
            }
        }
        // tvcmscategory_chain_slider

        // Mega Menu
        try {
            $sql = 'select 1 from `' . _DB_PREFIX_ . 'tvcmsmegamenu` LIMIT 1';
            if (Db::getInstance()->Execute($sql)) {
                $check = 'DESCRIBE `' . _DB_PREFIX_ . 'tvcmsmegamenu`';
                $columns = Db::getInstance()->ExecuteS($check);
                $found = false;
                foreach ($columns as $col) {
                    if ('active_title' != $col['Field'] || 'width_icon' != $col['Field'] || 'height_icon' != $col['Field']) {
                        $found = true;
                    }
                }
                if ($found) {
                    $sql =
                        'ALTER TABLE `' .
                        _DB_PREFIX_ .
                        'tvcmsmegamenu`
                   ADD COLUMN active_title tinyint(1) unsigned NULL DEFAULT  \'1\',
                   ADD COLUMN width_icon varchar(255) NOT NULL DEFAULT \'126\',
                   ADD COLUMN height_icon varchar(255) NOT NULL DEFAULT \'30\'';
                    Db::getInstance()->Execute($sql);
                }
            }
        } catch (Exception $e) {
            if ($this->TVDEBUG_DB) {
                $this->message .= $pthis->displayError($pthis->l('TVDEBUG_DB : tvcmsmegamenu table not Found!!!'));
            }
        }

        try {
            $sql = 'select 1 from `' . _DB_PREFIX_ . 'tvcmsmegamenu_shop` LIMIT 1';
            if (Db::getInstance()->Execute($sql)) {
                $check = 'DESCRIBE `' . _DB_PREFIX_ . 'tvcmsmegamenu_shop`';
                $columns = Db::getInstance()->ExecuteS($check);
                $foundC = false;
                foreach ($columns as $col) {
                    if ('active_title' != $col['Field'] || 'width_icon' != $col['Field'] || 'height_icon' != $col['Field']) {
                        $foundC = true;
                    }
                }

                if ($foundC) {
                    $sql =
                        'ALTER TABLE `' .
                        _DB_PREFIX_ .
                        'tvcmsmegamenu_shop`
                   ADD COLUMN active_title tinyint(1) unsigned NULL DEFAULT  \'1\',
                   ADD COLUMN width_icon varchar(255) NOT NULL DEFAULT \'126\',
                   ADD COLUMN height_icon varchar(255) NOT NULL DEFAULT \'30\'';
                    Db::getInstance()->Execute($sql);
                }
            }
        } catch (Exception $e) {
            if ($this->TVDEBUG_DB) {
                $this->message .= $pthis->displayError($pthis->l('TVDEBUG_DB : tvcmsmegamenu_shop table not Found!!!'));
            }
        }
        // Mega Menu
        // category chain slider
        try {
            $sql = 'select 1 from `' . _DB_PREFIX_ . 'tvcmscategory_chain_slider` LIMIT 1';
            if (Db::getInstance()->Execute($sql)) {
                $sql =
                    'ALTER TABLE `' .
                    _DB_PREFIX_ .
                    'tvcmscategory_chain_slider`
                       ADD COLUMN width int(10) unsigned NULL,
                       ADD COLUMN height int(10) unsigned NULL';
                if (Db::getInstance()->Execute($sql)) {
                    $sql =
                        'SELECT 
                    id_tvcmscategory_chain_slider,
                    image
                    FROM `' .
                        _DB_PREFIX_ .
                        'tvcmscategory_chain_slider`';

                    $data = Db::getInstance()->ExecuteS($sql);
                    $ImageSizePath = _PS_MODULE_DIR_ . 'tvcmscategorychainslider/views/img/';
                    foreach ($data as $value) {
                        $id_tvcmscategory_chain_slider = $value['id_tvcmscategory_chain_slider'];
                        $ImageName = $value['image'];
                        if (file_exists($ImageSizePath . $ImageName)) {
                            $imagedata = getimagesize($ImageSizePath . $ImageName);
                            $width = $imagedata[0];
                            $height = $imagedata[1];

                            $sql =
                                'UPDATE `' .
                                _DB_PREFIX_ .
                                'tvcmscategory_chain_slider`
                             SET width = ' .
                                (int) $width .
                                ',
                             height = ' .
                                (int) $height .
                                '
                             WHERE
                             id_tvcmscategory_chain_slider =' .
                                (int) $id_tvcmscategory_chain_slider;

                            // if (!Db::getInstance()->Execute($sql)) {
                            //     if ($this->TVDEBUG_DB) {
                            //         $this->message .= $pthis->displayError($pthis->l("TVDEBUG_DB : tvcmscategory_chain_slider Upgradation Failed"));
                            //     }
                            // }
                        }
                    }
                }
            }
        } catch (Exception $e) {
            if ($this->TVDEBUG_DB) {
                $this->message .= $pthis->displayError($pthis->l('TVDEBUG_DB : 2tvcmscategory_chain_slider table not Found!!!'));
            }
        }
        // tvcmscategory_slider
        try {
            $sql = 'select 1 from `' . _DB_PREFIX_ . 'tvcmscategory_slider` LIMIT 1';
            if (Db::getInstance()->Execute($sql)) {
                $sql =
                    'ALTER TABLE `' .
                    _DB_PREFIX_ .
                    'tvcmscategory_slider`
                       ADD COLUMN width int(10) unsigned NULL,
                       ADD COLUMN height int(10) unsigned NULL';
                if (Db::getInstance()->Execute($sql)) {
                    $sql =
                        'SELECT 
                    id_tvcmscategory_slider,
                    image
                    FROM `' .
                        _DB_PREFIX_ .
                        'tvcmscategory_slider`';

                    $data = Db::getInstance()->ExecuteS($sql);
                    $ImageSizePath = _PS_MODULE_DIR_ . 'tvcmscategoryslider/views/img/';
                    foreach ($data as $value) {
                        $id_tvcmscategory_slider = $value['id_tvcmscategory_slider'];
                        $ImageName = $value['image'];
                        if (file_exists($ImageSizePath . $ImageName)) {
                            $imagedata = getimagesize($ImageSizePath . $ImageName);
                            $width = $imagedata[0];
                            $height = $imagedata[1];

                            $sql =
                                'UPDATE `' .
                                _DB_PREFIX_ .
                                'tvcmscategory_slider`
                             SET width = ' .
                                (int) $width .
                                ',
                             height = ' .
                                (int) $height .
                                '
                             WHERE
                             id_tvcmscategory_slider =' .
                                (int) $id_tvcmscategory_slider;

                            // if (!Db::getInstance()->Execute($sql)) {
                            //     if ($this->TVDEBUG_DB) {
                            //         $this->message .= $pthis->displayError($pthis->l("TVDEBUG_DB : tvcmscategory_slider Upgradation Failed"));
                            //     }
                            // }
                        }
                    }
                }
            }
        } catch (Exception $e) {
            if ($this->TVDEBUG_DB) {
                $this->message .= $pthis->displayError($pthis->l('TVDEBUG_DB : tvcmscategory_slider table not Found!!!'));
            }
        }

        // tvcmsleftsideofferbanner
        try {
            if (!Configuration::get('TVCMSLEFTSIDEOFFERBANNER_IMAGE_WIDTH') || !Configuration::get('TVCMSLEFTSIDEOFFERBANNER_IMAGE_HEIGHT')) {
                $ImageName = Configuration::get('TVCMSLEFTSIDEOFFERBANNER_IMAGE_NAME');
                $ImageSizePath = _PS_MODULE_DIR_ . 'tvcmsleftsideofferbanner/views/img/';
                if (file_exists($ImageSizePath . $ImageName)) {
                    $imagedata = getimagesize($ImageSizePath . $ImageName);
                    $width = $imagedata[0];
                    $height = $imagedata[1];
                    Configuration::updateValue('TVCMSLEFTSIDEOFFERBANNER_IMAGE_WIDTH', $width);
                    Configuration::updateValue('TVCMSLEFTSIDEOFFERBANNER_IMAGE_HEIGHT', $height);
                }
            }
        } catch (Exception $e) {
            if ($this->TVDEBUG_DB) {
                $this->message .= $pthis->displayError($pthis->l('TVDEBUG_DB : TVCMSLEFTSIDEOFFERBANNER_IMAGE_NAME Configuration issue.'));
            }
        }
        // tvcmsmultibanner1
        try {
            $languages = Language::getLanguages();
            for ($i = 1; $i <= 3; ++$i) {
                $ImageSizePath = _PS_MODULE_DIR_ . 'tvcmsmultibanner1/views/img/';
                $result = [];
                foreach ($languages as $lang) {
                    $ImageName = Configuration::get('TVCMSMULTIBANNER1_IMAGE_NAME_' . $i, $lang['id_lang']);
                    if (file_exists($ImageSizePath . $ImageName)) {
                        $imagedata = getimagesize($ImageSizePath . $ImageName);
                        $width = $imagedata[0];
                        $height = $imagedata[1];
                        $result['TVCMSMULTIBANNER1_IMAGE_WIDTH_' . $i][$lang['id_lang']] = $width;
                        $result['TVCMSMULTIBANNER1_IMAGE_HEIGHT_' . $i][$lang['id_lang']] = $height;
                    }
                }
                $width = $result['TVCMSMULTIBANNER1_IMAGE_WIDTH_' . $i];
                Configuration::updateValue('TVCMSMULTIBANNER1_IMAGE_WIDTH_' . $i, $width);
                $height = $result['TVCMSMULTIBANNER1_IMAGE_HEIGHT_' . $i];
                Configuration::updateValue('TVCMSMULTIBANNER1_IMAGE_HEIGHT_' . $i, $height);
            }
        } catch (Exception $e) {
            if ($this->TVDEBUG_DB) {
                $this->message .= $pthis->displayError($pthis->l('TVDEBUG_DB : TVCMSMULTIBANNER1_IMAGE_NAME Configuration issue.'));
            }
        }

        // tvcmsofferbanner
        try {
            if (!Configuration::get('TVCMSOFFERBANNER_IMAGE_WIDTH') || !Configuration::get('TVCMSOFFERBANNER_IMAGE_HEIGHT')) {
                $ImageName = Configuration::get('TVCMSOFFERBANNER_IMAGE_NAME');
                $ImageSizePath = _PS_MODULE_DIR_ . 'tvcmsofferbanner/views/img/';
                if (file_exists($ImageSizePath . $ImageName)) {
                    $imagedata = getimagesize($ImageSizePath . $ImageName);
                    $width = $imagedata[0];
                    $height = $imagedata[1];
                    Configuration::updateValue('TVCMSOFFERBANNER_IMAGE_WIDTH', $width);
                    Configuration::updateValue('TVCMSOFFERBANNER_IMAGE_HEIGHT', $height);
                }
            }
        } catch (Exception $e) {
            if ($this->TVDEBUG_DB) {
                $this->message .= $pthis->displayError($pthis->l('TVDEBUG_DB : TVCMSOFFERBANNER_IMAGE_NAME Configuration issue.'));
            }
        }
        // Vertical menu
        try {
            if (!Configuration::get('TVCMSVERTICALMENU_LEFT_COLUMN') || !Configuration::get('TVCMSVERTICALMENU_MENU_ALL_PAGES')) {
                Configuration::updateValue('TVCMSVERTICALMENU_MENU_ALL_PAGES', 1);
                Configuration::updateValue('TVCMSVERTICALMENU_LEFT_COLUMN', 0);
            }
        } catch (Exception $e) {
            if ($this->TVDEBUG_DB) {
                $this->message .= $pthis->displayError($pthis->l('TVDEBUG_DB : TVCMSVERTICALMENU_LEFT_COLUMN Configuration issue.'));
            }
        }
        // Vertical menu

        // Custom setting - Custom Layout Configuration
        try {
            if (!Configuration::get('TVCMSHEADERCUSTOMLAYOUT') || !Configuration::get('TVCMSHEADERCUSTOMLAYOUT_MOBILE') || !Configuration::get('TVCMSFOOTERCUSTOMLAYOUT') || !Configuration::get('TVCMSPRODUCTCUSTOMLAYOUT') || !Configuration::get('TVCMSCAT_BANNER_STATUS')) {
                $tvcms_obj = new TvcmsCustomSettingStatus();
                $setting_var = $tvcms_obj->fieldStatusInformation();
                Configuration::updateValue('TVCMSHEADERCUSTOMLAYOUT', 'desk-header-' . $setting_var['header_layout_default']);
                Configuration::updateValue('TVCMSHEADERCUSTOMLAYOUT_MOBILE', 'mobile-header-' . $setting_var['mob_header_layout_default']);
                Configuration::updateValue('TVCMSFOOTERCUSTOMLAYOUT', 'footer-' . $setting_var['footer_layout_default']);
                Configuration::updateValue('TVCMSPRODUCTCUSTOM_LAYOUT', 'product-' . $setting_var['product_layout_default']);
                Configuration::updateValue('TVCMSCAT_BANNER_STATUS', 1);
            }
            $result = [];
            $languages = Language::getLanguages();
            foreach ($languages as $lang) {
                $tmp = 'ex : ski, shoes, Snowboard, pole, Rossigno';
                $result['TVCMSCUSTOMSETTING_SEARCH_PLACEHOLDER_TEXT'][$lang['id_lang']] = $tmp;
            }
            $tmp = $result['TVCMSCUSTOMSETTING_SEARCH_PLACEHOLDER_TEXT'];
            Configuration::updateValue('TVCMSCUSTOMSETTING_SEARCH_PLACEHOLDER_TEXT', $tmp);
        } catch (Exception $e) {
            if ($this->TVDEBUG_DB) {
                $this->message .= $pthis->displayError($pthis->l('TVDEBUG_DB : TVCMSVERTICALMENU_LEFT_COLUMN Configuration issue.'));
            }
        }

        try {
            if (!Configuration::get('TVCMSCUSTOMSETTING_CATEGORY_TREE_STATUS')) {
                Configuration::updateValue('TVCMSCUSTOMSETTING_CATEGORY_TREE_STATUS', 0);
            }
        } catch (Exception $e) {
            if ($this->TVDEBUG_DB) {
                $this->message .= $pthis->displayError($pthis->l('TVDEBUG_DB : TVCMSCUSTOMSETTING_CATEGORY_TREE_STATUS Configuration issue.'));
            }
        }

        try {
            $result = [];
            $languages = Language::getLanguages();
            foreach ($languages as $lang) {
                $tmp = 'ex : ski, shoes, Snowboard, pole, Rossigno';
                $result['TVCMSCUSTOMSETTING_SEARCH_PLACEHOLDER_TEXT'][$lang['id_lang']] = $tmp;
            }
            $tmp = $result['TVCMSCUSTOMSETTING_SEARCH_PLACEHOLDER_TEXT'];
            Configuration::updateValue('TVCMSCUSTOMSETTING_SEARCH_PLACEHOLDER_TEXT', $tmp);
        } catch (Exception $e) {
            if ($this->TVDEBUG_DB) {
                $this->message .= $pthis->displayError($pthis->l('TVDEBUG_DB : TVCMSVERTICALMENU_LEFT_COLUMN Configuration issue.'));
            }
        }
        // Custom setting - Custom Layout Configuration
        // tvcmssingleblock
        try {
            $languages = Language::getLanguages();
            $ImageSizePath = _PS_MODULE_DIR_ . 'tvcmssingleblock/views/img/';
            $result = [];
            foreach ($languages as $lang) {
                $ImageName = Configuration::get('TVCMSSINGLEBLOCK_SINGLE_BLOCK_IMG', $lang['id_lang']);
                if (file_exists($ImageSizePath . $ImageName)) {
                    $imagedata = getimagesize($ImageSizePath . $ImageName);
                    $width = $imagedata[0];
                    $height = $imagedata[1];
                    $result['TVCMSSINGLEBLOCK_IMG_WIDTH'][$lang['id_lang']] = $width;
                    $result['TVCMSSINGLEBLOCK_IMG_HEIGHT'][$lang['id_lang']] = $height;
                }
            }
            $width = $result['TVCMSSINGLEBLOCK_IMG_WIDTH'];
            Configuration::updateValue('TVCMSSINGLEBLOCK_IMG_WIDTH', $width);
            $height = $result['TVCMSSINGLEBLOCK_IMG_HEIGHT'];
            Configuration::updateValue('TVCMSSINGLEBLOCK_IMG_HEIGHT', $height);
        } catch (Exception $e) {
            if ($this->TVDEBUG_DB) {
                $this->message .= $pthis->displayError($pthis->l('TVDEBUG_DB : TVCMSSINGLEBLOCK_SINGLE_BLOCK_IMG Configuration issue.'));
            }
        }
        // tvcmssliderofferbanner
        try {
            $ImageName = Configuration::get('TVCMSSLIDEROFFERBANNER_IMAGE_NAME');
            $ImageName_1 = Configuration::get('TVCMSSLIDEROFFERBANNER_IMAGE_NAME_2');
            $ImageSizePath = _PS_MODULE_DIR_ . 'tvcmssliderofferbanner/views/img/';
            if (file_exists($ImageSizePath . $ImageName)) {
                $imagedata = getimagesize($ImageSizePath . $ImageName);
                $width = $imagedata[0];
                $height = $imagedata[1];
                Configuration::updateValue('TVCMSSLIDEROFFERBANNER_IMAGE_NAME_WIDTH_1', $width);
                Configuration::updateValue('TVCMSSLIDEROFFERBANNER_IMAGE_NAME_HEIGHT_1', $height);
            }
            if (file_exists($ImageSizePath . $ImageName_1)) {
                $imagedata = getimagesize($ImageSizePath . $ImageName_1);
                $width = $imagedata[0];
                $height = $imagedata[1];
                Configuration::updateValue('TVCMSSLIDEROFFERBANNER_IMAGE_NAME_2_WIDTH', $width);
                Configuration::updateValue('TVCMSSLIDEROFFERBANNER_IMAGE_NAME_2_HEIGHT', $height);
            }
        } catch (Exception $e) {
            if ($this->TVDEBUG_DB) {
                $this->message .= $pthis->displayError($pthis->l('TVDEBUG_DB : TVCMSSLIDEROFFERBANNER_IMAGE_NAME Configuration issue.'));
            }
        }
        // tvcmstwoofferbanner
        try {
            $ImageName = Configuration::get('TVCMSTWOOFFERBANNER_IMAGE_NAME');
            $ImageName_1 = Configuration::get('TVCMSTWOOFFERBANNER_IMAGE_NAME_2');
            $ImageSizePath = _PS_MODULE_DIR_ . 'tvcmstwoofferbanner/views/img/';
            if (file_exists($ImageSizePath . $ImageName)) {
                $imagedata = getimagesize($ImageSizePath . $ImageName);
                $width = $imagedata[0];
                $height = $imagedata[1];
                Configuration::updateValue('TVCMSTWOOFFERBANNER_IMAGE_WIDTH_1', $width);
                Configuration::updateValue('TVCMSTWOOFFERBANNER_IMAGE_HEIGHT_1', $height);
            }
            if (file_exists($ImageSizePath . $ImageName_1)) {
                $imagedata = getimagesize($ImageSizePath . $ImageName_1);
                $width = $imagedata[0];
                $height = $imagedata[1];
                Configuration::updateValue('TVCMSTWOOFFERBANNER_IMAGE_WIDTH_2', $width);
                Configuration::updateValue('TVCMSTWOOFFERBANNER_IMAGE_HEIGHT_2', $height);
            }
        } catch (Exception $e) {
            if ($this->TVDEBUG_DB) {
                $this->message .= $pthis->displayError($pthis->l('TVDEBUG_DB : TVCMSTWOOFFERBANNER_IMAGE_NAME Configuration issue.'));
            }
        }

        // tvcmstabproducts - Latest products
        try {
            $languages = Language::getLanguages();
            $ImageSizePath = _PS_MODULE_DIR_ . 'tvcmstabproducts/views/img/';
            $result = [];
            foreach ($languages as $lang) {
                $ImageName = Configuration::get('TVCMSTABPRODUCTS_NEW_PROD_IMAGE', [$lang['id_lang']]);
                if (file_exists($ImageSizePath . $ImageName)) {
                    $imagedata = getimagesize($ImageSizePath . $ImageName);
                    $width = $imagedata[0];
                    $height = $imagedata[1];
                    $result['TVCMSTABPRODUCTS_NEW_PROD_IMAGE_WIDTH'][$lang['id_lang']] = $width;
                    $result['TVCMSTABPRODUCTS_NEW_PROD_IMAGE_HEIGHT'][$lang['id_lang']] = $height;
                }
            }
            $width = $result['TVCMSTABPRODUCTS_NEW_PROD_IMAGE_WIDTH'];
            Configuration::updateValue('TVCMSTABPRODUCTS_NEW_PROD_IMAGE_WIDTH', $width);
            $height = $result['TVCMSTABPRODUCTS_NEW_PROD_IMAGE_HEIGHT'];
            Configuration::updateValue('TVCMSTABPRODUCTS_NEW_PROD_IMAGE_HEIGHT', $height);
        } catch (Exception $e) {
            if ($this->TVDEBUG_DB) {
                $this->message .= $pthis->displayError($pthis->l('TVDEBUG_DB : TVCMSTABPRODUCTS_NEW_PROD_IMAGE Configuration issue.'));
            }
        }
        // tvcmstabproducts - Special products
        try {
            $languages = Language::getLanguages();
            $ImageSizePath = _PS_MODULE_DIR_ . 'tvcmstabproducts/views/img/';
            $result = [];
            foreach ($languages as $lang) {
                $ImageName = Configuration::get('TVCMSTABPRODUCTS_SPECIAL_PROD_IMAGE', [$lang['id_lang']]);
                if (file_exists($ImageSizePath . $ImageName)) {
                    $imagedata = getimagesize($ImageSizePath . $ImageName);
                    $width = $imagedata[0];
                    $height = $imagedata[1];
                    $result['TVCMSTABPRODUCTS_SPECIAL_PROD_IMAGE_WIDTH'][$lang['id_lang']] = $width;
                    $result['TVCMSTABPRODUCTS_SPECIAL_PROD_IMAGE_HEIGHT'][$lang['id_lang']] = $height;
                }
            }
            $width = $result['TVCMSTABPRODUCTS_SPECIAL_PROD_IMAGE_WIDTH'];
            Configuration::updateValue('TVCMSTABPRODUCTS_SPECIAL_PROD_IMAGE_WIDTH', $width);
            $height = $result['TVCMSTABPRODUCTS_SPECIAL_PROD_IMAGE_HEIGHT'];
            Configuration::updateValue('TVCMSTABPRODUCTS_SPECIAL_PROD_IMAGE_HEIGHT', $height);
        } catch (Exception $e) {
            if ($this->TVDEBUG_DB) {
                $this->message .= $pthis->displayError($pthis->l('TVDEBUG_DB : TVCMSTABPRODUCTS_SPECIAL_PROD_IMAGE Configuration issue.'));
            }
        }

        // tvcmstabproducts - Feature products
        try {
            $languages = Language::getLanguages();
            $ImageSizePath = _PS_MODULE_DIR_ . 'tvcmstabproducts/views/img/';
            $result = [];
            foreach ($languages as $lang) {
                $ImageName = Configuration::get('TVCMSTABPRODUCTS_FEATURED_PROD_IMAGE', [$lang['id_lang']]);
                if (file_exists($ImageSizePath . $ImageName)) {
                    $imagedata = getimagesize($ImageSizePath . $ImageName);
                    $width = $imagedata[0];
                    $height = $imagedata[1];
                    $result['TVCMSTABPRODUCTS_FEATURED_PROD_IMAGE_WIDTH'][$lang['id_lang']] = $width;
                    $result['TVCMSTABPRODUCTS_FEATURED_PROD_IMAGE_HEIGHT'][$lang['id_lang']] = $height;
                }
            }
            $width = $result['TVCMSTABPRODUCTS_FEATURED_PROD_IMAGE_WIDTH'];
            Configuration::updateValue('TVCMSTABPRODUCTS_FEATURED_PROD_IMAGE_WIDTH', $width);
            $height = $result['TVCMSTABPRODUCTS_FEATURED_PROD_IMAGE_HEIGHT'];
            Configuration::updateValue('TVCMSTABPRODUCTS_FEATURED_PROD_IMAGE_HEIGHT', $height);
        } catch (Exception $e) {
            if ($this->TVDEBUG_DB) {
                $this->message .= $pthis->displayError($pthis->l('TVDEBUG_DB : TVCMSTABPRODUCTS_FEATURED_PROD_IMAGE Configuration issue.'));
            }
        }

        // tvcmstabproducts - Bestseller products
        try {
            $languages = Language::getLanguages();
            $ImageSizePath = _PS_MODULE_DIR_ . 'tvcmstabproducts/views/img/';
            $result = [];
            foreach ($languages as $lang) {
                $ImageName = Configuration::get('TVCMSTABPRODUCTS_BEST_SELLER_PROD_IMAGE', [$lang['id_lang']]);
                if (file_exists($ImageSizePath . $ImageName)) {
                    $imagedata = getimagesize($ImageSizePath . $ImageName);
                    $width = $imagedata[0];
                    $height = $imagedata[1];
                    $result['TVCMSTABPRODUCTS_BEST_SELLER_PROD_IMAGE_WIDTH'][$lang['id_lang']] = $width;
                    $result['TVCMSTABPRODUCTS_BEST_SELLER_PROD_IMAGE_HEIGHT'][$lang['id_lang']] = $height;
                }
            }
            $width = $result['TVCMSTABPRODUCTS_BEST_SELLER_PROD_IMAGE_WIDTH'];
            Configuration::updateValue('TVCMSTABPRODUCTS_BEST_SELLER_PROD_IMAGE_WIDTH', $width);
            $height = $result['TVCMSTABPRODUCTS_BEST_SELLER_PROD_IMAGE_HEIGHT'];
            Configuration::updateValue('TVCMSTABPRODUCTS_BEST_SELLER_PROD_IMAGE_HEIGHT', $height);
        } catch (Exception $e) {
            if ($this->TVDEBUG_DB) {
                $this->message .= $pthis->displayError($pthis->l('TVDEBUG_DB : TVCMSTABPRODUCTS_BEST_SELLER_PROD_IMAGE Configuration issue.'));
            }
        }

        // tvcmstabproducts - Main Tab products
        try {
            $languages = Language::getLanguages();
            $ImageSizePath = _PS_MODULE_DIR_ . 'tvcmstabproducts/views/img/';
            $result = [];
            foreach ($languages as $lang) {
                $ImageName = Configuration::get('TVCMSTABPRODUCTS_MAIN_IMAGE', [$lang['id_lang']]);
                if (file_exists($ImageSizePath . $ImageName)) {
                    $imagedata = getimagesize($ImageSizePath . $ImageName);
                    $width = $imagedata[0];
                    $height = $imagedata[1];
                    $result['TVCMSTABPRODUCTS_MAIN_IMAGE_WIDTH'][$lang['id_lang']] = $width;
                    $result['TVCMSTABPRODUCTS_MAIN_IMAGE_HEIGHT'][$lang['id_lang']] = $height;
                }
            }
            $width = $result['TVCMSTABPRODUCTS_MAIN_IMAGE_WIDTH'];
            Configuration::updateValue('TVCMSTABPRODUCTS_MAIN_IMAGE_WIDTH', $width);
            $height = $result['TVCMSTABPRODUCTS_MAIN_IMAGE_HEIGHT'];
            Configuration::updateValue('TVCMSTABPRODUCTS_MAIN_IMAGE_HEIGHT', $height);
        } catch (Exception $e) {
            if ($this->TVDEBUG_DB) {
                $this->message .= $pthis->displayError($pthis->l('TVDEBUG_DB : TVCMSTABPRODUCTS_MAIN_IMAGE Configuration issue.'));
            }
        }

        // tvcmsblog
        try {
            $sql = 'SELECT 1 FROM `' . _DB_PREFIX_ . 'tvcms_image_type` LIMIT 1';
            if (Db::getInstance()->Execute($sql)) {
                $sql = 'SELECT name FROM `' . _DB_PREFIX_ . 'tvcms_image_type` WHERE name = "blog_left"';
                $sqlr = Db::getInstance()->ExecuteS($sql);
                if (0 == count($sqlr)) {
                    $id_shop = (int) Context::getContext()->shop->id;
                    $sql =
                        'INSERT INTO `' .
                        _DB_PREFIX_ .
                        'tvcms_image_type` (`name`,`width`,`height`,`id_shop`,`active`)
                       VALUES("blog_left",294,307,' .
                        (int) $id_shop .
                        ',1)';
                    if (!Db::getInstance()->Execute($sql)) {
                        if ($this->TVDEBUG_DB) {
                            $this->message .= $pthis->displayError($pthis->l('TVDEBUG_DB : tvcms_image_type Upgradation Failed'));
                        }
                    }
                }
            } else {
                $this->message .= $pthis->displayError($pthis->l('tvcms_image_type Upgradation Failed'));
            }
        } catch (Exception $e) {
            if ($this->TVDEBUG_DB) {
                $this->message .= $pthis->displayError($pthis->l('TVDEBUG_DB : tvcms_image_type table not Found!!!'));
            }
        }

        try {
            $sql = 'SELECT 1 FROM `' . _DB_PREFIX_ . 'tvcms_image_type` LIMIT 1';
            if (Db::getInstance()->Execute($sql)) {
                $sql = 'SELECT name FROM `' . _DB_PREFIX_ . 'tvcms_image_type` WHERE name = "small_res"';
                $sqlr = Db::getInstance()->ExecuteS($sql);
                if (0 == count($sqlr)) {
                    $id_shop = (int) Context::getContext()->shop->id;
                    $sql =
                        'INSERT INTO `' .
                        _DB_PREFIX_ .
                        'tvcms_image_type` (`name`,`width`,`height`,`id_shop`,`active`)
                       VALUES("small_res",735,352,' .
                        (int) $id_shop .
                        ',1)';
                    if (!Db::getInstance()->Execute($sql)) {
                        if ($this->TVDEBUG_DB) {
                            $this->message .= $pthis->displayError($pthis->l('TVDEBUG_DB : tvcms_image_type Upgradation Failed'));
                        }
                    }
                }
            }
        } catch (Exception $e) {
            if ($this->TVDEBUG_DB) {
                $this->message .= $pthis->displayError($pthis->l('TVDEBUG_DB : tvcms_image_type table not Found!!!'));
            }
        }

        try {
            $sql = 'SELECT 1 FROM `' . _DB_PREFIX_ . 'tvcms_image_type` LIMIT 1';
            if (Db::getInstance()->Execute($sql)) {
                $sql = 'SELECT name FROM `' . _DB_PREFIX_ . 'tvcms_image_type` WHERE name = "large_res"';
                $sqlr = Db::getInstance()->ExecuteS($sql);
                if (0 == count($sqlr)) {
                    $id_shop = (int) Context::getContext()->shop->id;
                    $sql =
                        'INSERT INTO `' .
                        _DB_PREFIX_ .
                        'tvcms_image_type` (`name`,`width`,`height`,`id_shop`,`active`)
                       VALUES("large_res",666,325,' .
                        (int) $id_shop .
                        ',1)';
                    if (!Db::getInstance()->Execute($sql)) {
                        if ($this->TVDEBUG_DB) {
                            $this->message .= $pthis->displayError($pthis->l('TVDEBUG_DB : tvcms_image_type Upgradation Failed'));
                        }
                    }
                }
            }
        } catch (Exception $e) {
            if ($this->TVDEBUG_DB) {
                $this->message .= $pthis->displayError($pthis->l('TVDEBUG_DB : tvcms_image_type table not Found!!!'));
            }
        }

        try {
            $sql = 'SELECT 1 FROM `' . _DB_PREFIX_ . 'tvcms_image_type` LIMIT 1';
            if (Db::getInstance()->Execute($sql)) {
                $sql = 'SELECT name FROM `' . _DB_PREFIX_ . 'tvcms_image_type` WHERE name = "medium_res"';
                $sqlr = Db::getInstance()->ExecuteS($sql);
                if (0 == count($sqlr)) {
                    $id_shop = (int) Context::getContext()->shop->id;
                    $sql =
                        'INSERT INTO `' .
                        _DB_PREFIX_ .
                        'tvcms_image_type` (`name`,`width`,`height`,`id_shop`,`active`)
                       VALUES("medium_res",696,340,' .
                        (int) $id_shop .
                        ',1)';
                    if (!Db::getInstance()->Execute($sql)) {
                        if ($this->TVDEBUG_DB) {
                            $this->message .= $pthis->displayError($pthis->l('TVDEBUG_DB : tvcms_image_type Upgradation Failed'));
                        }
                    }
                }
            }
        } catch (Exception $e) {
            if ($this->TVDEBUG_DB) {
                $this->message .= $pthis->displayError($pthis->l('TVDEBUG_DB : tvcms_image_type table not Found!!!'));
            }
        }

        try {
            $sql = 'CREATE TABLE  IF NOT EXISTS `' . _DB_PREFIX_ . "tvcmsposts_view` (
           `id_tvcmsposts` int(10) unsigned NOT NULL,
           `ipadress` varchar(350) NOT NULL DEFAULT '',
           `id_view` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            PRIMARY KEY (`id_view`)
            ) ENGINE=" . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8';
            Db::getInstance()->Execute($sql);
        } catch (Exception $e) {
            if ($this->TVDEBUG_DB) {
                $this->message .= $pthis->displayError($pthis->l('TVDEBUG_DB : tvcmsmegamenu table not Found!!!'));
            }
        }
        // PRODUCT IMAGES TYPES
        try {
            $sql = 'SELECT 1 FROM `' . _DB_PREFIX_ . 'image_type` LIMIT 1';
            if (Db::getInstance()->Execute($sql)) {
                $sql = 'SELECT name FROM `' . _DB_PREFIX_ . 'image_type` WHERE name = "sub_cat_def"';
                $sqlr = Db::getInstance()->ExecuteS($sql);
                if (0 == count($sqlr)) {
                    $sql =
                    'INSERT INTO `' .
                    _DB_PREFIX_ .
                    'image_type` (`name`,`width`,`height`,`products`,`categories`,`manufacturers`,`suppliers`,`stores`)
                    VALUES("sub_cat_def",150,150,0,1,0,0,0)';
                    if (!Db::getInstance()->Execute($sql)) {
                        if ($this->TVDEBUG_DB) {
                            $this->message .= $pthis->displayError($pthis->l('TVDEBUG_DB : image_type Upgradation Failed'));
                        }
                    }
                }
            }
        } catch (Exception $e) {
            if ($this->TVDEBUG_DB) {
                $this->message .= $pthis->displayError($pthis->l('TVDEBUG_DB : image_type table not Found!!!'));
            }
        }

        try {
            $sql = 'SELECT 1 FROM `' . _DB_PREFIX_ . 'image_type` LIMIT 1';
            if (Db::getInstance()->Execute($sql)) {
                $sql = 'SELECT name FROM `' . _DB_PREFIX_ . 'image_type` WHERE name = "pd_custom"';
                $sqlr = Db::getInstance()->ExecuteS($sql);
                if (0 == count($sqlr)) {
                    $sql =
                    'INSERT INTO `' .
                    _DB_PREFIX_ .
                    'image_type` (`name`,`width`,`height`,`products`,`categories`,`manufacturers`,`suppliers`,`stores`)
                    VALUES("pd_custom",452,452,1,0,0,0,0)';
                    if (!Db::getInstance()->Execute($sql)) {
                        if ($this->TVDEBUG_DB) {
                            $this->message .= $pthis->displayError($pthis->l('TVDEBUG_DB : image_type Upgradation Failed'));
                        }
                    }
                }
            }
        } catch (Exception $e) {
            if ($this->TVDEBUG_DB) {
                $this->message .= $pthis->displayError($pthis->l('TVDEBUG_DB : image_type table not Found!!!'));
            }
        }

        try {
            $sql = 'SELECT 1 FROM `' . _DB_PREFIX_ . 'image_type` LIMIT 1';
            if (Db::getInstance()->Execute($sql)) {
                $sql = 'SELECT name FROM `' . _DB_PREFIX_ . 'image_type` WHERE name = "pd4_def"';
                $sqlr = Db::getInstance()->ExecuteS($sql);
                if (0 == count($sqlr)) {
                    $sql =
                    'INSERT INTO `' .
                    _DB_PREFIX_ .
                    'image_type` (`name`,`width`,`height`,`products`,`categories`,`manufacturers`,`suppliers`,`stores`)
                    VALUES("pd4_def",724,724,1,0,0,0,0)';
                    if (!Db::getInstance()->Execute($sql)) {
                        if ($this->TVDEBUG_DB) {
                            $this->message .= $pthis->displayError($pthis->l('TVDEBUG_DB : image_type Upgradation Failed'));
                        }
                    }
                }
            }
        } catch (Exception $e) {
            if ($this->TVDEBUG_DB) {
                $this->message .= $pthis->displayError($pthis->l('TVDEBUG_DB : image_type table not Found!!!'));
            }
        }

        try {
            $sql = 'SELECT 1 FROM `' . _DB_PREFIX_ . 'image_type` LIMIT 1';
            if (Db::getInstance()->Execute($sql)) {
                $sql = 'SELECT name FROM `' . _DB_PREFIX_ . 'image_type` WHERE name = "add_cart_def"';
                $sqlr = Db::getInstance()->ExecuteS($sql);
                if (0 == count($sqlr)) {
                    $sql =
                    'INSERT INTO `' .
                    _DB_PREFIX_ .
                    'image_type` (`name`,`width`,`height`,`products`,`categories`,`manufacturers`,`suppliers`,`stores`)
                    VALUES("add_cart_def",200,200,1,0,0,0,0)';
                    if (!Db::getInstance()->Execute($sql)) {
                        if ($this->TVDEBUG_DB) {
                            $this->message .= $pthis->displayError($pthis->l('TVDEBUG_DB : image_type Upgradation Failed'));
                        }
                    }
                }
            }
        } catch (Exception $e) {
            if ($this->TVDEBUG_DB) {
                $this->message .= $pthis->displayError($pthis->l('TVDEBUG_DB : image_type table not Found!!!'));
            }
        }

        // try {
        //     $sql = 'SELECT 1 FROM `' . _DB_PREFIX_ . 'image_type` LIMIT 1';
        //     if (Db::getInstance()->Execute($sql)) {
        //         $sql = 'SELECT name FROM `' . _DB_PREFIX_ . 'image_type` WHERE name = "sp_pd_main_img"';
        //         $sqlr = Db::getInstance()->ExecuteS($sql);
        //         if (count($sqlr) == 0) {
        //             $sql =
        //             'INSERT INTO `' .
        //             _DB_PREFIX_ .
        //             'image_type` (`name`,`width`,`height`,`products`,`categories`,`manufacturers`,`suppliers`,`stores`)
        //             VALUES("sp_pd_main_img",277,299,1,0,0,0,0)';
        //             if (!Db::getInstance()->Execute($sql)) {
        //                 if ($this->TVDEBUG_DB) {
        //                     $this->message .= $pthis->displayError($pthis->l("TVDEBUG_DB : image_type Upgradation Failed"));
        //                 }
        //             }
        //         }
        //     }
        // } catch (Exception $e) {
        //     if ($this->TVDEBUG_DB) {
        //         $this->message .= $pthis->displayError($pthis->l("TVDEBUG_DB : image_type table not Found!!!"));
        //     }
        // }
        // PRODUCT IMAGES TYPES
        // $this->message .= $pthis->displayConfirmation($pthis->l("DB Upgradation Process Done."));
        return $this->message;
    }
}
