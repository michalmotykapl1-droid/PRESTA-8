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

include_once 'classes/tvcmsnewsletterpopup_image_upload.class.php';
include_once 'classes/tvcmsnewsletterpopup_status.class.php';

class TvcmsNewsLetterPopup extends Module
{
    public const GUEST_NOT_REGISTERED = -1;

    public const CUSTOMER_NOT_REGISTERED = 0;

    public const GUEST_REGISTERED = 1;

    public const CUSTOMER_REGISTERED = 2;

    public function __construct()
    {
        $this->name = 'tvcmsnewsletterpopup';
        $this->tab = 'front_office_features';
        $this->version = '4.0.0';
        $this->author = 'ThemeVolty';

        $this->controllers = ['verification'];

        $this->bootstrap = true;
        parent::__construct();

        $this->displayName = $this->l('ThemeVolty - Newsletter Popup');
        $this->description = $this->l('Shows popup newsletter window with your message');
        $this->ps_versions_compliancy = ['min' => '1.7', 'max' => _PS_VERSION_];
    }

    public function install()
    {
        // $this->createDefaultData();
        $this->installTab();

        return parent::install()
            && $this->registerHook('displayHeader')
            && $this->registerHook('displayBackOfficeHeader')
            && $this->registerHook('displayNewsLetterPopup');
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
            $tab->name[$lang['id_lang']] = 'Newsletter Popup';
        }
        $tab->id_parent = $parentTab_2->id;
        $tab->module = $this->name;
        $response &= $tab->add();

        return $response;
    }

    public function createDefaultData()
    {
        $result = [];
        $languages = Language::getLanguages();

        foreach ($languages as $lang) {
            $result['TVCMSNEWSLETTERPOPUP_TITLE'][$lang['id_lang']] = 'Subscribe To Our Newsletter';
            $result['TVCMSNEWSLETTERPOPUP_SUB_DESCRIPTION'][$lang['id_lang']] = 'Subscribe to our email newsletter'
                . ' today to receive update on the latest news';
            $result['TVCMSNEWSLETTERPOPUP_DESCRIPTION'][$lang['id_lang']] = 'Subscribe to our email newsletter today '
                . 'to receive update on the latest news';
            $result['TVCMSNEWSLETTERPOPUP_IMG'][$lang['id_lang']] = 'demo_img.jpg';
            $result['TVCMSNEWSLETTERPOPUP_BG_IMG'][$lang['id_lang']] = 'demo_bg_img.jpg';
        }

        Configuration::updateValue('TVCMSNEWSLETTERPOPUP_TITLE', $result['TVCMSNEWSLETTERPOPUP_TITLE']);
        $tmp = $result['TVCMSNEWSLETTERPOPUP_SUB_DESCRIPTION'];
        Configuration::updateValue('TVCMSNEWSLETTERPOPUP_SUB_DESCRIPTION', $tmp);
        Configuration::updateValue('TVCMSNEWSLETTERPOPUP_DESCRIPTION', $result['TVCMSNEWSLETTERPOPUP_DESCRIPTION']);
        Configuration::updateValue('TVCMSNEWSLETTERPOPUP_IMG', $result['TVCMSNEWSLETTERPOPUP_IMG']);
        Configuration::updateValue('TVCMSNEWSLETTERPOPUP_BG_IMG', $result['TVCMSNEWSLETTERPOPUP_BG_IMG']);

        Configuration::updateValue('TVCMSNEWSLETTERPOPUP_IMG_STATUS', 1);
        Configuration::updateValue('TVCMSNEWSLETTERPOPUP_BG_IMG_STATUS', 0);
        Configuration::updateValue('TVCMSNEWSLETTERPOPUP_POPUP_STATUS', 1);
    }

    public function uninstall()
    {
        $this->deleteDefaultData();
        $this->uninstallTab();

        return parent::uninstall();
    }

    public function uninstallTab()
    {
        $id_tab = Tab::getIdFromClassName('Admin' . $this->name);
        $tab = new Tab($id_tab);
        $tab->delete();

        return true;
    }

    public function deleteDefaultData()
    {
        Configuration::deleteByName('TVCMSNEWSLETTERPOPUP_TITLE');
        Configuration::deleteByName('TVCMSNEWSLETTERPOPUP_SUB_DESCRIPTION');
        Configuration::deleteByName('TVCMSNEWSLETTERPOPUP_DESCRIPTION');
        Configuration::deleteByName('TVCMSNEWSLETTERPOPUP_IMG');
        Configuration::deleteByName('TVCMSNEWSLETTERPOPUP_IMG_STATUS');
        Configuration::deleteByName('TVCMSNEWSLETTERPOPUP_BG_IMG');
        Configuration::deleteByName('TVCMSNEWSLETTERPOPUP_BG_IMG_STATUS');
        Configuration::deleteByName('TVCMSNEWSLETTERPOPUP_POPUP_STATUS');
    }

    public function getContent()
    {
        if (Tools::isSubmit('submitTvcmsSampleinstall') && '1' == Tools::getValue('tvinstalldata')) {
            $this->createDefaultData();
        }
        $message = $this->postProcess();

        return $message . $this->renderForm();
    }

    public function postProcess()
    {
        $message = '';
        $languages = Language::getLanguages();
        $result = [];

        if (Tools::isSubmit('submitTvcmsNewsLetterPopupForm') && '0' == Tools::getValue('tvinstalldata')) {
            $obj_image = new TvcmsNewsLetterPopupImageUpload();
            foreach ($languages as $lang) {
                if (!empty($_FILES['TVCMSNEWSLETTERPOPUP_IMG_' . $lang['id_lang']]['name'])) {
                    $old_file = Configuration::get('TVCMSNEWSLETTERPOPUP_IMG', $lang['id_lang']);
                    $new_file = $_FILES['TVCMSNEWSLETTERPOPUP_IMG_' . $lang['id_lang']];
                    $ans = $obj_image->imageUploading($new_file, $old_file);
                    if ($ans['success']) {
                        $result['TVCMSNEWSLETTERPOPUP_IMG'][$lang['id_lang']] = $ans['name'];
                        $message .= $this->displayConfirmation($this->l('Newsletter Popup is Updated'));
                    } else {
                        $message .= $ans['error'];
                        $result['TVCMSNEWSLETTERPOPUP_IMG'][$lang['id_lang']] = $old_file;
                    }
                } else {
                    $old_file = Configuration::get('TVCMSNEWSLETTERPOPUP_IMG', $lang['id_lang']);
                    $result['TVCMSNEWSLETTERPOPUP_IMG'][$lang['id_lang']] = $old_file;
                }

                if (!empty($_FILES['TVCMSNEWSLETTERPOPUP_BG_IMG_' . $lang['id_lang']]['name'])) {
                    $old_file = Configuration::get('TVCMSNEWSLETTERPOPUP_BG_IMG', $lang['id_lang']);
                    $new_file = $_FILES['TVCMSNEWSLETTERPOPUP_BG_IMG_' . $lang['id_lang']];
                    $ans = $obj_image->imageUploading($new_file, $old_file);
                    if ($ans['success']) {
                        $result['TVCMSNEWSLETTERPOPUP_BG_IMG'][$lang['id_lang']] = $ans['name'];
                        $message .= $this->displayConfirmation($this->l('Newsletter Popup is Updated'));
                    } else {
                        $message .= $ans['error'];
                        $result['TVCMSNEWSLETTERPOPUP_BG_IMG'][$lang['id_lang']] = $old_file;
                    }
                } else {
                    $old_file = Configuration::get('TVCMSNEWSLETTERPOPUP_BG_IMG', $lang['id_lang']);
                    $result['TVCMSNEWSLETTERPOPUP_BG_IMG'][$lang['id_lang']] = $old_file;
                }

                $tmp = Tools::getValue('TVCMSNEWSLETTERPOPUP_TITLE_' . $lang['id_lang']);
                $result['TVCMSNEWSLETTERPOPUP_TITLE'][$lang['id_lang']] = $tmp;
                $tmp = Tools::getValue('TVCMSNEWSLETTERPOPUP_SUB_DESCRIPTION_' . $lang['id_lang']);
                $result['TVCMSNEWSLETTERPOPUP_SUB_DESCRIPTION'][$lang['id_lang']] = $tmp;
                $tmp = Tools::getValue('TVCMSNEWSLETTERPOPUP_DESCRIPTION_' . $lang['id_lang']);
                $result['TVCMSNEWSLETTERPOPUP_DESCRIPTION'][$lang['id_lang']] = $tmp;
            }

            $tmp = $result['TVCMSNEWSLETTERPOPUP_IMG'];
            Configuration::updateValue('TVCMSNEWSLETTERPOPUP_IMG', $tmp);
            $tmp = Tools::getValue('TVCMSNEWSLETTERPOPUP_IMG_STATUS');
            Configuration::updateValue('TVCMSNEWSLETTERPOPUP_IMG_STATUS', $tmp);

            $tmp = $result['TVCMSNEWSLETTERPOPUP_BG_IMG'];
            Configuration::updateValue('TVCMSNEWSLETTERPOPUP_BG_IMG', $tmp);
            $tmp = Tools::getValue('TVCMSNEWSLETTERPOPUP_BG_IMG_STATUS');
            Configuration::updateValue('TVCMSNEWSLETTERPOPUP_BG_IMG_STATUS', $tmp);

            $tmp = $result['TVCMSNEWSLETTERPOPUP_TITLE'];
            Configuration::updateValue('TVCMSNEWSLETTERPOPUP_TITLE', $tmp);
            $tmp = $result['TVCMSNEWSLETTERPOPUP_SUB_DESCRIPTION'];
            Configuration::updateValue('TVCMSNEWSLETTERPOPUP_SUB_DESCRIPTION', $tmp);
            $tmp = $result['TVCMSNEWSLETTERPOPUP_DESCRIPTION'];
            Configuration::updateValue('TVCMSNEWSLETTERPOPUP_DESCRIPTION', $tmp);

            $tmp = Tools::getValue('TVCMSNEWSLETTERPOPUP_POPUP_STATUS');
            Configuration::updateValue('TVCMSNEWSLETTERPOPUP_POPUP_STATUS', $tmp);

            $this->clearCustomSmartyCache('tvcmsnewsletterpopup_tvcmsnewsletterpopup.tpl');

            return $message;
        }
    }

    public function clearCustomSmartyCache($cache_id)
    {
        if (Cache::isStored($cache_id)) {
            Cache::clean($cache_id);
        }
    }

    public function hookdisplayNewsLetterPopup($params)
    {
        if (!Cache::isStored('tvcmsnewsletterpopup_tvcmsnewsletterpopup.tpl')) {
            $cookie = Context::getContext()->cookie;
            $id_lang = $cookie->id_lang;
            $this->context->smarty->assign('id_lang', $id_lang);

            $tvcms_obj = new TvcmsNewsLetterPopupStatus();
            $show_fields = $tvcms_obj->fieldStatusInformation();
            $this->context->smarty->assign('show_fields', $show_fields);

            $social_module_enable = false;
            if (Module::isEnabled('ps_socialfollow')) {
                $social_module_enable = true;
            }

            $path = _MODULE_DIR_ . $this->name . '/views/img/';
            $this->context->smarty->assign('path', $path);
            $this->context->smarty->assign('social_module_enable', $social_module_enable);

            $output = $this->display(__FILE__, 'views/templates/front/' . $this->name . '.tpl');
            Cache::store('tvcmsnewsletterpopup_tvcmsnewsletterpopup.tpl', $output);
        }

        return Cache::retrieve('tvcmsnewsletterpopup_tvcmsnewsletterpopup.tpl');
    }

    public function hookdisplayHeader($params)
    {
        $this->context->controller->addjqueryPlugin('cooki-plugin');

        $this->context->controller->addJS($this->_path . 'views/js/front.js');

        $this->context->controller->addCSS($this->_path . 'views/css/front.css', 'all');

        $ajax_path = Tools::getShopProtocol() . Context::getContext()->shop->domain
            . Context::getContext()->shop->physical_uri . 'modules/tvcmsnewsletterpopup/ajax.php';

        Media::addJsDef(['ajax_path' => $ajax_path]);
    }

    public function hookDisplayBackOfficeHeader()
    {
        if ($this->name == Tools::getValue('configure')) {
            $this->context->controller->addJS($this->_path . 'views/js/back.js');
            $this->context->controller->addCSS($this->_path . 'views/css/back.css');
        }
    }// hookDisplayBackOfficeHeader()

    public function setMedia()
    {
        parent::setMedia();
        $this->addJqueryUI('ui.datepicker');
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

        $form[] = $this->tvcmsNewsLetterPopupForm();

        return $helper->generateForm($form);
    }

    protected function tvcmsNewsLetterPopupForm()
    {
        $tvcms_obj = new TvcmsNewsLetterPopupStatus();
        $show_fields = $tvcms_obj->fieldStatusInformation();
        $input = [];

        $input[] = [
                'col' => 12,
                'type' => 'BtnInstallData',
                'name' => 'BtnInstallData',
                'label' => '',
            ];
        if (true == $show_fields['image']) {
            $input[] = [
                    'type' => 'image_file',
                    'name' => 'TVCMSNEWSLETTERPOPUP_IMG',
                    'label' => $this->l('Image'),
                    'lang' => true,
                ];

            $input[] = [
                        'type' => 'switch',
                        'label' => $this->l('Image status'),
                        'name' => 'TVCMSNEWSLETTERPOPUP_IMG_STATUS',
                        'desc' => $this->l('Hide or Show Image'),
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

        if (true == $show_fields['bg_image']) {
            $input[] = [
                    'type' => 'background_image_file',
                    'name' => 'TVCMSNEWSLETTERPOPUP_BG_IMG',
                    'label' => $this->l('Background Image'),
                    'lang' => true,
                ];

            $input[] = [
                        'type' => 'switch',
                        'label' => $this->l('Background Image'),
                        'name' => 'TVCMSNEWSLETTERPOPUP_BG_IMG_STATUS',
                        'desc' => $this->l('Hide or show background image'),
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

        if (true == $show_fields['title']) {
            $input[] = [
                        'col' => 8,
                        'type' => 'text',
                        'name' => 'TVCMSNEWSLETTERPOPUP_TITLE',
                        'label' => $this->l('Title'),
                        'lang' => true,
                    ];
        }

        if (true == $show_fields['sub_title']) {
            $input[] = [
                        'col' => 8,
                        'type' => 'text',
                        'name' => 'TVCMSNEWSLETTERPOPUP_SUB_DESCRIPTION',
                        'label' => $this->l('Sub Title'),
                        'lang' => true,
                    ];
        }

        if (true == $show_fields['description']) {
            $input[] = [
                        'col' => 8,
                        'type' => 'text',
                        'name' => 'TVCMSNEWSLETTERPOPUP_DESCRIPTION',
                        'label' => $this->l('Description'),
                        'lang' => true,
                    ];
        }

        if (true == $show_fields['popup_status']) {
            $input[] = [
                        'type' => 'switch',
                        'label' => $this->l('Newsletter Pop-Up'),
                        'name' => 'TVCMSNEWSLETTERPOPUP_POPUP_STATUS',
                        'desc' => $this->l('Hide or show newsletter pop-up'),
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
                'title' => $this->l('Newsletter Popup'),
                'icon' => 'icon-support',
                ],
                'input' => $input,
                'submit' => [
                    'title' => $this->l('Save'),
                    'name' => 'submitTvcmsNewsLetterPopupForm',
                ],
            ],
        ];
    }

    private function initToolbar()
    {
        $this->toolbar_btn['save'] = [
            'href' => '#',
            'desc' => $this->l('Save'),
        ];

        return $this->toolbar_btn;
    }

    public function getConfigFormValues()
    {
        $result = [];
        $languages = Language::getLanguages();

        foreach ($languages as $lang) {
            $tmp = Configuration::get('TVCMSNEWSLETTERPOPUP_TITLE', $lang['id_lang']);
            $result['TVCMSNEWSLETTERPOPUP_TITLE'][$lang['id_lang']] = $tmp;

            $tmp = Configuration::get('TVCMSNEWSLETTERPOPUP_SUB_DESCRIPTION', $lang['id_lang']);
            $result['TVCMSNEWSLETTERPOPUP_SUB_DESCRIPTION'][$lang['id_lang']] = $tmp;

            $tmp = Configuration::get('TVCMSNEWSLETTERPOPUP_DESCRIPTION', $lang['id_lang']);
            $result['TVCMSNEWSLETTERPOPUP_DESCRIPTION'][$lang['id_lang']] = $tmp;

            $tmp = Configuration::get('TVCMSNEWSLETTERPOPUP_IMG', $lang['id_lang']);
            $result['TVCMSNEWSLETTERPOPUP_IMG'][$lang['id_lang']] = $tmp;

            $tmp = Configuration::get('TVCMSNEWSLETTERPOPUP_BG_IMG', $lang['id_lang']);
            $result['TVCMSNEWSLETTERPOPUP_BG_IMG'][$lang['id_lang']] = $tmp;
        }

        $path = _MODULE_DIR_ . $this->name . '/views/img/';
        $this->context->smarty->assign('path', $path);

        $result['TVCMSNEWSLETTERPOPUP_IMG_STATUS'] = Configuration::get('TVCMSNEWSLETTERPOPUP_IMG_STATUS');
        $result['TVCMSNEWSLETTERPOPUP_BG_IMG_STATUS'] = Configuration::get('TVCMSNEWSLETTERPOPUP_BG_IMG_STATUS');
        $result['TVCMSNEWSLETTERPOPUP_POPUP_STATUS'] = Configuration::get('TVCMSNEWSLETTERPOPUP_POPUP_STATUS');

        return $result;
    }

    /**
     * Check if this mail is registered for newsletters.
     *
     * @param string $customer_email
     *
     * @return int -1 = not a customer and not registered
     *             0 = customer not registered
     *             1 = registered in block
     *             2 = registered in customer
     */
    private function isNewsletterRegistered($customer_email)
    {
        $sql = 'SELECT `email`
                FROM ' . _DB_PREFIX_ . 'emailsubscription
                WHERE `email` = \'' . pSQL($customer_email) . '\'
                AND id_shop = ' . $this->context->shop->id;

        if (Db::getInstance()->getRow($sql)) {
            return self::GUEST_REGISTERED;
        }

        $sql = 'SELECT `newsletter`
                FROM ' . _DB_PREFIX_ . 'customer
                WHERE `email` = \'' . pSQL($customer_email) . '\'
                AND id_shop = ' . $this->context->shop->id;

        if (!$registered = Db::getInstance()->getRow($sql)) {
            return self::GUEST_NOT_REGISTERED;
        }

        if ('1' == $registered['newsletter']) {
            return self::CUSTOMER_REGISTERED;
        }

        return self::CUSTOMER_NOT_REGISTERED;
    }

    /**
     * Return true if the registered status correspond to a registered user.
     *
     * @param int $register_status
     *
     * @return bool
     */
    protected function isRegistered($register_status)
    {
        return in_array(
            $register_status,
            [self::GUEST_REGISTERED, self::CUSTOMER_REGISTERED]
        );
    }

    public function activateGuest($email)
    {
        return Db::getInstance()->execute(
            'UPDATE `' . _DB_PREFIX_ . 'emailsubscription`
                        SET `active` = 1
                        WHERE `email` = \'' . pSQL($email) . '\''
        );
    }

    /**
     * Returns a guest email by token.
     *
     * @param string $token
     *
     * @return string email
     */
    protected function getGuestEmailByToken($token)
    {
        $sql = 'SELECT `email`
                FROM `' . _DB_PREFIX_ . 'emailsubscription`
                WHERE MD5(CONCAT( `email` , `newsletter_date_add`, \''
                    . pSQL(Configuration::get('NW_SALT')) . '\')) = \'' . pSQL($token) . '\'
                AND `active` = 0';

        return Db::getInstance()->getValue($sql);
    }

    /**
     * Returns a customer email by token.
     *
     * @param string $token
     *
     * @return string email
     */
    protected function getUserEmailByToken($token)
    {
        $sql = 'SELECT `email`
                FROM `' . _DB_PREFIX_ . 'customer`
                WHERE MD5(CONCAT( `email` , `date_add`, \''
                    . pSQL(Configuration::get('NW_SALT')) . '\')) = \'' . pSQL($token) . '\'
                AND `newsletter` = 0';

        return Db::getInstance()->getValue($sql);
    }

    protected function registerGuest($email, $active = true)
    {
        $sql = 'INSERT INTO ' . _DB_PREFIX_ . 'emailsubscription (id_shop, id_shop_group, email,'
                 . ' newsletter_date_add, ip_registration_newsletter, http_referer, active)
                VALUES
                (' . $this->context->shop->id . ',
                ' . $this->context->shop->id_shop_group . ',
                \'' . pSQL($email) . '\',
                NOW(),
                \'' . pSQL(Tools::getRemoteAddr()) . '\',
                (
                    SELECT c.http_referer
                    FROM ' . _DB_PREFIX_ . 'connections c
                    WHERE c.id_guest = ' . (int) $this->context->customer->id . '
                    ORDER BY c.date_add DESC LIMIT 1
                ),
                ' . (int) $active . '
                )';

        return Db::getInstance()->execute($sql);
    }

    /**
     * Return a token associated to an user.
     *
     * @param string $email
     * @param string $register_status
     */
    protected function getToken($email, $register_status)
    {
        if (in_array($register_status, [self::GUEST_NOT_REGISTERED, self::GUEST_REGISTERED])) {
            $sql = 'SELECT MD5(CONCAT( `email` , `newsletter_date_add`, \''
                    . pSQL(Configuration::get('NW_SALT')) . '\')) as token
                    FROM `' . _DB_PREFIX_ . 'emailsubscription`
                    WHERE `active` = 0
                    AND `email` = \'' . pSQL($email) . '\'';
        } elseif (self::CUSTOMER_NOT_REGISTERED == $register_status) {
            $sql = 'SELECT MD5(CONCAT( `email` , `date_add`, \'' . pSQL(Configuration::get('NW_SALT')) . '\' )) as token
                    FROM `' . _DB_PREFIX_ . 'customer`
                    WHERE `newsletter` = 0
                    AND `email` = \'' . pSQL($email) . '\'';
        }

        return Db::getInstance()->getValue($sql);
    }

    /**
     * Ends the registration process to the newsletter.
     *
     * @param string $token
     *
     * @return string
     */
    public function confirmEmail($token)
    {
        $activated = false;

        if ($email = $this->getGuestEmailByToken($token)) {
            $activated = $this->activateGuest($email);
        } elseif ($email = $this->getUserEmailByToken($token)) {
            $activated = $this->registerUser($email);
        }

        if (!$activated) {
            return $this->l('This email is already registered and/or invalid.');
        }

        if ($discount = Configuration::get('NW_VOUCHER_CODE')) {
            $this->sendVoucher($email, $discount);
        }

        if (Configuration::get('NW_CONFIRMATION_EMAIL')) {
            $this->sendConfirmationEmail($email);
        }

        return $this->l('Thank you for subscribing to our newsletter.');
    }

    /**
     * Send a verification email.
     *
     * @param string $email
     * @param string $token
     *
     * @return bool
     */
    protected function sendVerificationEmail($email, $token)
    {
        $verif_url = Context::getContext()->link->getModuleLink(
            'tvcmsnewsletterpopup',
            'verification',
            ['token' => $token]
        );

        return Mail::Send(
            $this->context->language->id,
            'fieldnewsletter_verif',
            Mail::l('Email verification', $this->context->language->id),
            ['{verif_url}' => $verif_url],
            $email,
            null,
            null,
            null,
            null,
            null,
            dirname(__FILE__) . '/mails/',
            false,
            $this->context->shop->id
        );
    }

    protected function register($email, $register_status)
    {
        if (self::GUEST_NOT_REGISTERED == $register_status) {
            return $this->registerGuest($email);
        }

        if (self::CUSTOMER_NOT_REGISTERED == $register_status) {
            return $this->registerUser($email);
        }

        return false;
    }

    /**
     * Subscribe a customer to the newsletter.
     *
     * @param string $email
     *
     * @return bool
     */
    protected function registerUser($email)
    {
        $sql = 'UPDATE ' . _DB_PREFIX_ . 'customer
                SET `newsletter` = 1, newsletter_date_add = NOW(), `ip_registration_newsletter` = \''
                    . pSQL(Tools::getRemoteAddr()) . '\'
                WHERE `email` = \'' . pSQL($email) . '\'
                AND id_shop = ' . $this->context->shop->id;

        return Db::getInstance()->execute($sql);
    }

    /**
     * Send an email containing a voucher code.
     *
     * @return bool|int
     */
    protected function sendVoucher($email, $code)
    {
        return Mail::Send(
            $this->context->language->id,
            'fieldnewsletter_voucher',
            Mail::l('Newsletter voucher', $this->context->language->id),
            ['{discount}' => $code],
            $email,
            null,
            null,
            null,
            null,
            null,
            dirname(__FILE__) . '/mails/',
            false,
            $this->context->shop->id
        );
    }

    /**
     * Send a confirmation email.
     *
     * @param string $email
     *
     * @return bool
     */
    protected function sendConfirmationEmail($email)
    {
        return Mail::Send(
            $this->context->language->id,
            'fieldnewsletter_conf',
            Mail::l('Newsletter confirmation', $this->context->language->id),
            [],
            pSQL($email),
            null,
            null,
            null,
            null,
            null,
            dirname(__FILE__) . '/mails/',
            false,
            $this->context->shop->id
        );
    }

    /**
     * Register in block newsletter.
     */
    public function newsletterRegistration($email)
    {
        if (empty($email) || !Validate::isEmail($email)) {
            echo $this->l('Invalid email address.');

            return;
        }

        $register_status = $this->isNewsletterRegistered($email);
        if ($register_status > 0) {
            echo $this->l('This email address is already registered.');

            return;
        }

        $email = pSQL($email);
        if (!$this->isRegistered($register_status)) {
            if (Configuration::get('NW_VERIFICATION_EMAIL')) {
                // create an unactive entry in the newsletter database
                if (self::GUEST_NOT_REGISTERED == $register_status) {
                    $this->registerGuest($email, false);
                }

                if (!$token = $this->getToken($email, $register_status)) {
                    echo $this->l('An error occurred during the subscription process.');

                    return;
                }

                $this->sendVerificationEmail($email, $token);

                echo $this->l('A verification email has been sent. Please check your inbox.');

                return;
            } else {
                if ($resp = $this->register($email, $register_status)) {
                    if ($code = Configuration::get('NW_VOUCHER_CODE')) {
                        $resp = $this->sendVoucher($email, $code);
                    }

                    if (Configuration::get('NW_CONFIRMATION_EMAIL')) {
                        $resp = $this->sendConfirmationEmail($email);
                    }

                    if (true == $resp) {
                        echo $this->l('You have successfully subscribed to this newsletter.');
                    } else {
                        echo $resp;
                    }

                    return;
                } else {
                    echo $this->l('An error occurred during the subscription process.');

                    return;
                }
            }
        }
    }
}
