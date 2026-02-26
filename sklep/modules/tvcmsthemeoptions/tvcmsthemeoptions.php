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
include_once _PS_MODULE_DIR_ . 'tvcmscustomsetting/classes/tvcustomsetting_common_list.class.php';
include_once _PS_MODULE_DIR_ . 'tvcmscustomsetting/classes/tvcmscustomsetting_status.class.php';
class TvcmsThemeOptions extends Module
{
    public function __construct()
    {
        $this->name = 'tvcmsthemeoptions';
        $this->tab = 'front_office_features';
        $this->version = '4.0.0';
        $this->author = 'ThemeVolty';
        $this->need_instance = 0;

        $this->bootstrap = true;
        parent::__construct();

        $this->displayName = $this->l('ThemeVolty - Theme Options');
        $this->description = $this->l('Its Show Theme Options on Front Side');

        $this->ps_versions_compliancy = ['min' => '1.7', 'max' => _PS_VERSION_];
        $this->module_key = '';

        $this->confirmUninstall = $this->l('Warning: all the data saved in your database will be deleted.' .
            ' Are you sure you want uninstall this module?');
    }

    public function install()
    {
        Configuration::updateValue('TVCMSFRONTSIDE_THEME_SETTING_SHOW', '1');

        return parent::install()
            && $this->registerHook('displayHeader');
        // && $this->registerHook('displayThemeOptions');
    }

    public function uninstall()
    {
        Configuration::updateValue('TVCMSFRONTSIDE_THEME_SETTING_SHOW', '0');

        return parent::uninstall();
    }

    public function registerJs()
    {
        if (isset($this->js_files) && !empty($this->js_files)) {
            foreach ($this->js_files as $js_file) {
                if (isset($js_file['key'])
                        && !empty($js_file['key'])
                        && isset($js_file['src'])
                        && !empty($js_file['src'])
                ) {
                    $tmp = $js_file['position'];
                    $position = (isset($js_file['position']) && !empty($tmp)) ? $js_file['position'] : 'bottom';
                    $tmp = $js_file['priority'];
                    $priority = (isset($js_file['priority']) && !empty($tmp)) ? $js_file['priority'] : 50;
                    $tmp = $js_file['attributes'];
                    $attributes = (isset($tmp) && !empty($tmp)) ? $tmp : '';

                    if (isset($js_file['load_theme']) && (true == $js_file['load_theme'])) {
                        $this->context->controller->registerJavascript($js_file['key'], _THEME_DIR_
                             . 'assets/js/' . $js_file['src'], ['position' => $position, 'priority' => $priority, 'attributes' => $attributes]);
                    } else {
                        $this->context->controller->registerJavascript($js_file['key'], 'modules/' . $this->name
                             . '/views/js/' . $js_file['src'], ['position' => $position, 'priority' => $priority, 'attributes' => $attributes]);
                    }
                }
            }
        }

        return true;
    }

    public $js_files = [
        // array(
        //     'key' => 'tvcmsthemeoptionsStorageapiJs',
        //     'src' => 'jquery.storageapi.min.js',
        //     'priority' => 250,
        //     'position' => 'bottom',
        //     'load_theme' => false,
        //     'attributes' => 'defer',
        // ),
        [
            'key' => 'tvcmsthemeoptionsmMinicolorsJs',
            'src' => 'jquery.minicolors.js',
            'priority' => 250,
            'position' => 'bottom',
            'load_theme' => false,
            'attributes' => 'defer',
        ],
        [
            'key' => 'tvcmsthemeoptionsToggleJs',
            'src' => 'bootstrap-toggle.min.js',
            'priority' => 250,
            'position' => 'bottom',
            'load_theme' => false,
            'attributes' => 'defer',
        ],
        [
            'key' => 'tvcmsthemeoptionsfrontJs',
            'src' => 'front.js',
            'priority' => 251,
            'position' => 'bottom',
            'load_theme' => false,
            'attributes' => 'defer',
        ],
    ];

    public function hookdisplayHeader()
    {
        $tmp = $this->context->link->getModuleLink('tvcmsthemeoptions', 'default');
        $arr = explode('/', _THEME_DIR_);
        $arr = array_reverse($arr);
        Media::addJsDef(['getThemeOptionsLink' => $tmp, 'tvthemename' => $arr[1]]);
        // $this->registerJs();
        // $this->context->controller->addJS($this->_path . 'views/js/jquery.storageapi.min.js');
        $this->context->controller->addJS($this->_path . 'views/js/jquery.minicolors.js');
        $this->context->controller->addJS($this->_path . 'views/js/bootstrap-toggle.min.js');
        $this->context->controller->addJS($this->_path . 'views/js/front.js');

        $this->context->controller->addCSS($this->_path . 'views/css/jquery.minicolors.css');
        $this->context->controller->addCSS($this->_path . 'views/css/bootstrap-toggle.min.css');
        $this->context->controller->addCSS($this->_path . 'views/css/front.css');
    }

    public function hookdisplayThemeOptions()
    {
        if (!Cache::isStored('tvcmsthemeoptions_display_index_ajax.tpl')) {
            $themeoptionsimagepath = _MODULE_DIR_ . $this->name . '/views/img/';

            $obj = new TvcmsCustomSettingCommonList();
            $title_font_list = $obj->titleFontList();
            $fields_data = [];
            $tvcms_obj = new TvcmsCustomSettingStatus();
            $show_fields = $tvcms_obj->fieldStatusInformation();
            $fields_data['header_layout_list'] = $show_fields['header_layout_list'];
            $fields_data['footer_layout_list'] = $show_fields['footer_layout_list'];
            $fields_data['product_layout_list'] = $show_fields['product_layout_list'];
            $fields_data['mob_header_layout_list'] = $show_fields['mob_header_layout_list'];
            $fields_data['layout_img_path'] = _THEME_IMG_DIR_;
            $this->context->smarty->assign('fields_data', $fields_data);
            $this->context->smarty->assign('title_font_list', $title_font_list);
            $this->context->smarty->assign('themeoptionsimagepath', $themeoptionsimagepath);

            $output = $this->display(__FILE__, 'views/templates/front/display_home.tpl');
            Cache::store('tvcmsthemeoptions_display_index_ajax.tpl', $output);
        }

        return Cache::retrieve('tvcmsthemeoptions_display_index_ajax.tpl');
    }

    public function hookdisplaylayout()
    {
        return $this->display(__FILE__, 'views/templates/front/display_layout.tpl');
    }
}
