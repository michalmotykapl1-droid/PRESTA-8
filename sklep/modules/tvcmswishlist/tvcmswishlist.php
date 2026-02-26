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
if (!defined('_PS_VERSION_')) {
    exit;
}
include_once dirname(__FILE__) . '/FunWishList.php';
class TvcmsWishList extends Module
{
    public const INSTALL_SQL_FILE = 'install.sql';

    private $html = '';

    public function __construct()
    {
        $this->name = 'tvcmswishlist';
        $this->tab = 'front_office_features';
        $this->version = '4.0.1';
        $this->author = 'ThemeVolty';
        $this->need_instance = 0;
        $this->controllers = ['mywishlist', 'view'];
        $this->bootstrap = true;
        parent::__construct();
        $this->displayName = $this->l('ThemeVolty - Wishlist block');
        $this->description = $this->l('Adds a block containing the customer\'s wishlists.');
        $this->default_wishlist_name = $this->l('My wishlist');
        $this->ps_versions_compliancy = ['min' => '1.7', 'max' => _PS_VERSION_];
        $this->module_key = '';

        $this->confirmUninstall = $this->l('Warning: all the data saved in your database will be deleted.' .
            ' Are you sure you want uninstall this module?');
        $this->html = '';
    }

    public function install($delete_params = true)
    {
        $this->installTab();
        if ($delete_params) {
            if (!file_exists(dirname(__FILE__) . '/' . self::INSTALL_SQL_FILE)) {
                return false;
            } elseif (!$sql = Tools::file_get_contents(dirname(__FILE__) . '/' . self::INSTALL_SQL_FILE)) {
                return false;
            }
            $sql = str_replace(['PREFIX_', 'ENGINE_TYPE'], [_DB_PREFIX_, _MYSQL_ENGINE_], $sql);
            $sql = preg_split("/;\s*[\r\n]+/", $sql);
            foreach ($sql as $query) {
                if ($query) {
                    if (!Db::getInstance()->execute(trim($query))) {
                        return false;
                    }
                }
            }
        }

        return parent::install()
        && $this->registerHook('displayWishlistProductPage')
        && $this->registerHook('customerAccount')
        && $this->registerHook('displayHeader')
        && $this->registerHook('displayNavWishlistBlock')
        && $this->registerHook('displayNavWishlistMobileBlock')
        && $this->registerHook('displayWishlistBtnProductList')
        && $this->registerHook('displayWishlistBtnSticky')
        && $this->registerHook('displayNavWishlistBlocksticky')
        && $this->registerHook('displayMyAccountBlock')
        && $this->registerHook('displayProductListFunctionalButtons');
        // This hook is optional

        return true;
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
            $tab->name[$lang['id_lang']] = "Customer's Wishlist";
        }
        $tab->id_parent = $parentTab_2->id;
        $tab->module = $this->name;
        $response &= $tab->add();

        return $response;
    }

    public function uninstall($delete_params = true)
    {
        $this->uninstallTab();
        if (($delete_params && !$this->deleteTables()) || !parent::uninstall()) {
            return false;
        }

        return true;
    }

    public function uninstallTab()
    {
        $id_tab = Tab::getIdFromClassName('Admin' . $this->name);
        $tab = new Tab($id_tab);
        $tab->delete();

        return true;
    }

    private function deleteTables()
    {
        return Db::getInstance()->execute(
            'DROP TABLE IF EXISTS
            `' . _DB_PREFIX_ . 'wishlist`,
            `' . _DB_PREFIX_ . 'wishlist_email`,
            `' . _DB_PREFIX_ . 'wishlist_product`,
            `' . _DB_PREFIX_ . 'wishlist_product_cart`'
        );
    }

    public function reset()
    {
        if (!$this->uninstall(false)) {
            return false;
        }
        if (!$this->install(false)) {
            return false;
        }

        return true;
    }

    public function getContent()
    {
        if (Tools::isSubmit('viewtvcmswishlist') && $id = Tools::getValue('id_product')) {
            Tools::redirect($this->context->link->getProductLink($id));
        } elseif (Tools::isSubmit('submitSettings')) {
            $activated = Tools::getValue('activated');
            if (0 != $activated && 1 != $activated) {
                $this->html .= '<div class="alert error alert-danger">' . $this->l('Activate module : Invalid choice.')
                     . '</div>';
            }
            $this->html .= '<div class="conf confirm alert alert-success">' . $this->l('Settings updated') . '</div>';
        }

        $this->html .= $this->renderJS();
        $this->html .= $this->renderForm();
        if (Tools::getValue('id_customer') && Tools::getValue('id_wishlist')) {
            $this->html .= $this->renderList((int) Tools::getValue('id_wishlist'));
        }

        return $this->html;
    }

    public function hookdisplayProductListFunctionalButtons($params)
    {
        // TODO : Add cache
        if ($this->context->customer->isLogged()) {
            $this->smarty->assign('wishlists', FunWishlist::getByIdCustomer($this->context->customer->id));
        }

        $this->smarty->assign('product', $params['product']);

        return $this->display(__FILE__, 'views/templates/front/tvcmswishlist_button.tpl');
    }

    public function hookdisplayNavWishlistBlock($params)
    {
        $this->headerData();

        return $this->display(__FILE__, 'views/templates/front/tvcmswishlist_top.tpl');
    }

    public function hookdisplayNavWishlistBlocksticky($params)
    {
        $this->headerData();

        return $this->display(__FILE__, 'views/templates/front/tvcmswishlist_sticky.tpl');
    }

    public function headerData()
    {
        $useSSL = ((isset($this->ssl) && $this->ssl && Configuration::get('PS_SSL_ENABLED'))
            || Tools::usingSecureMode()) ? true : false;
        $protocol_content = ($useSSL) ? 'https://' : 'http://';
        if ($this->context->customer->isLogged()) {
            $wishlists = FunWishList::getByIdCustomer($this->context->customer->id);
            if (true === empty($this->context->cookie->id_wishlist)
                || false === FunWishList::exists($this->context->cookie->id_wishlist, $this->context->customer->id)) {
                if (!count($wishlists)) {
                    $id_wishlist = false;
                } else {
                    $id_wishlist = (int) $wishlists[0]['id_wishlist'];
                    $this->context->cookie->id_wishlist = (int) $id_wishlist;
                }
            } else {
                $id_wishlist = $this->context->cookie->id_wishlist;
            }

            $this->smarty->assign(
                [
                    'id_wishlist' => $id_wishlist,
                    'isLogged' => true,
                    'wishlist_products' => (false == $id_wishlist ? false : FunWishList::getProductByIdCustomer(
                        $id_wishlist,
                        $this->context->customer->id,
                        $this->context->language->id,
                        null,
                        true
                    )),
                    'wishlists' => $wishlists,
                    'ptoken' => Tools::getToken(false),
                ]
            );
        } else {
            $this->smarty->assign(['wishlist_products' => false, 'wishlists' => false]);
        }
        $this->context->smarty->assign(
            [
                'content_dir' => $protocol_content . Tools::getHttpHost() . __PS_BASE_URI__,
                'isLogged' => $this->context->customer->logged,
                'count_product' => (int) Db::getInstance()->getValue('SELECT count(id_wishlist_product) FROM '
                    . _DB_PREFIX_ . 'wishlist w, ' . _DB_PREFIX_ . 'wishlist_product wp where w.id_wishlist = wp.id_wishlist '
                     . 'and w.id_customer=' . (int) $this->context->customer->id),
            ]
        );
    }

    public function hookdisplayWishlistBtnSticky($params)
    {
        $useSSL = ((isset($this->ssl) && $this->ssl && Configuration::get('PS_SSL_ENABLED'))
            || Tools::usingSecureMode()) ? true : false;
        $protocol_content = ($useSSL) ? 'https://' : 'http://';
        if ($this->context->customer->isLogged()) {
            $wishlists = FunWishList::getByIdCustomer($this->context->customer->id);
            if (true === empty($this->context->cookie->id_wishlist)
                || false === FunWishList::exists($this->context->cookie->id_wishlist, $this->context->customer->id)) {
                if (!count($wishlists)) {
                    $id_wishlist = false;
                } else {
                    $id_wishlist = (int) $wishlists[0]['id_wishlist'];
                    $this->context->cookie->id_wishlist = (int) $id_wishlist;
                }
            } else {
                $id_wishlist = $this->context->cookie->id_wishlist;
            }

            $this->smarty->assign(
                [
                    'id_wishlist' => $id_wishlist,
                    'isLogged' => true,
                    'wishlist_products' => (false == $id_wishlist ? false : FunWishList::getProductByIdCustomer(
                        $id_wishlist,
                        $this->context->customer->id,
                        $this->context->language->id,
                        null,
                        true
                    )),
                    'wishlists' => $wishlists,
                    'ptoken' => Tools::getToken(false),
                ]
            );
        } else {
            $this->smarty->assign(['wishlist_products' => false, 'wishlists' => false]);
        }
        $this->context->smarty->assign(
            [
                'content_dir' => $protocol_content . Tools::getHttpHost() . __PS_BASE_URI__,
                'isLogged' => $this->context->customer->logged,
                'count_product' => (int) Db::getInstance()->getValue('SELECT count(id_wishlist_product) FROM '
                    . _DB_PREFIX_ . 'wishlist w, ' . _DB_PREFIX_ . 'wishlist_product wp where w.id_wishlist = wp.id_wishlist '
                     . 'and w.id_customer=' . (int) $this->context->customer->id),
            ]
        );

        return $this->display(__FILE__, 'views/templates/front/tvcmswishlist_top_3.tpl');
    }

    public function hookdisplayNavWishlistMobileBlock($params)
    {
        $useSSL = ((isset($this->ssl) && $this->ssl && Configuration::get('PS_SSL_ENABLED'))
            || Tools::usingSecureMode()) ? true : false;
        $protocol_content = ($useSSL) ? 'https://' : 'http://';
        if ($this->context->customer->isLogged()) {
            $wishlists = FunWishList::getByIdCustomer($this->context->customer->id);
            if (true === empty($this->context->cookie->id_wishlist)
                || false === FunWishList::exists($this->context->cookie->id_wishlist, $this->context->customer->id)) {
                if (!count($wishlists)) {
                    $id_wishlist = false;
                } else {
                    $id_wishlist = (int) $wishlists[0]['id_wishlist'];
                    $this->context->cookie->id_wishlist = (int) $id_wishlist;
                }
            } else {
                $id_wishlist = $this->context->cookie->id_wishlist;
            }

            $this->smarty->assign(
                [
                    'id_wishlist' => $id_wishlist,
                    'isLogged' => true,
                    'wishlist_products' => (false == $id_wishlist ? false : FunWishList::getProductByIdCustomer(
                        $id_wishlist,
                        $this->context->customer->id,
                        $this->context->language->id,
                        null,
                        true
                    )),
                    'wishlists' => $wishlists,
                    'ptoken' => Tools::getToken(false),
                ]
            );
        } else {
            $this->smarty->assign(['wishlist_products' => false, 'wishlists' => false]);
        }
        $this->context->smarty->assign(
            [
                'content_dir' => $protocol_content . Tools::getHttpHost() . __PS_BASE_URI__,
                'isLogged' => $this->context->customer->logged,
                'count_product' => (int) Db::getInstance()->getValue('SELECT count(id_wishlist_product) FROM '
                    . _DB_PREFIX_ . 'wishlist w, ' . _DB_PREFIX_ . 'wishlist_product wp where w.id_wishlist = wp.id_wishlist '
                     . 'and w.id_customer=' . (int) $this->context->customer->id),
            ]
        );

        return $this->display(__FILE__, 'views/templates/front/tvcmswishlist_top_2.tpl');
    }

    public function hookdisplayHeader($params)
    {
        $wish_link = $this->context->link->getModuleLink('tvcmswishlist', 'mywishlist');
        Media::addJsDef(['wish_link' => $wish_link]);
        $this->context->controller->addCSS($this->_path . 'views/css/tvcmswishlist.css', 'all');
        $this->context->controller->addJS($this->_path . 'views/js/ajax-wishlist.js');

        $this->smarty->assign([
            'wishlist_link' => $this->context->link->getModuleLink('tvcmswishlist', 'mywishlist'),
        ]);
    }

    public function hookrightColumn($params)
    {
        if ($this->context->customer->isLogged()) {
            $wishlists = FunWishList::getByIdCustomer($this->context->customer->id);
            if (true === empty($this->context->cookie->id_wishlist)
                || false === FunWishList::exists($this->context->cookie->id_wishlist, $this->context->customer->id)) {
                if (!count($wishlists)) {
                    $id_wishlist = false;
                } else {
                    $id_wishlist = (int) $wishlists[0]['id_wishlist'];
                    $this->context->cookie->id_wishlist = (int) $id_wishlist;
                }
            } else {
                $id_wishlist = $this->context->cookie->id_wishlist;
            }
            $this->smarty->assign(
                [
                    'id_wishlist' => $id_wishlist,
                    'isLogged' => true,
                    'wishlist_products' => (false == $id_wishlist ? false : FunWishList::getProductByIdCustomer(
                        $id_wishlist,
                        $this->context->customer->id,
                        $this->context->language->id,
                        null,
                        true
                    )),
                    'wishlists' => $wishlists,
                    'ptoken' => Tools::getToken(false),
                ]
            );
        } else {
            $this->smarty->assign(['wishlist_products' => false, 'wishlists' => false]);
        }

        return $this->display(__FILE__, 'views/templates/front/tvcmswishlist.tpl');
    }

    public function hookLeftColumn($params)
    {
        return $this->hookRightColumn($params);
    }

    public function hookProductActions($params)
    {
        $cookie = $params['cookie'];

        $this->smarty->assign([
            'id_product' => (int) Tools::getValue('id_product'),
        ]);

        if (isset($cookie->id_customer)) {
            $this->smarty->assign([
                'wishlists' => FunWishList::getByIdCustomer($cookie->id_customer),
            ]);
        }

        return $this->display(__FILE__, 'views/templates/front/tvcmswishlist-extra.tpl');
    }

    public function hookdisplayWishlistProductPage($params)
    {
        return $this->hookProductActions($params);
    }

    public function hookdisplayWishlistBtnProductList($params)
    {
        $cookie = $params['cookie'];

        $this->smarty->assign([
            'id_product' => (int) $params['product']['id_product'],
        ]);

        if (isset($cookie->id_customer)) {
            $this->smarty->assign([
                'wishlists' => FunWishList::getByIdCustomer($cookie->id_customer),
            ]);
        }

        return $this->display(__FILE__, 'views/templates/front/tvcmswishlist-extra.tpl');
    }

    public function hookCustomerAccount($params)
    {
        return $this->display(__FILE__, 'views/templates/front/my-account_2.tpl');
    }

    public function hookDisplayMyAccountBlock($params)
    {
        return $this->display(__FILE__, 'views/templates/front/my-account.tpl');
    }

    private function displayProducts($id_wishlist)
    {
        include dirname(__FILE__) . '/FunWishList.php';

        $wishlist = new FunWishList($id_wishlist);
        $products = FunWishList::getProductByIdCustomer(
            $id_wishlist,
            $wishlist->id_customer,
            $this->context->language->id
        );
        $nb_products = count($products);
        for ($i = 0; $i < $nb_products; ++$i) {
            $obj = new Product((int) $products[$i]['id_product'], false, $this->context->language->id);
            if (!Validate::isLoadedObject($obj)) {
                continue;
            } else {
                $images = $obj->getImages($this->context->language->id);
                foreach ($images as $image) {
                    if ($image['cover']) {
                        $products[$i]['cover'] = $obj->id . '-' . $image['id_image'];

                        break;
                    }
                }
                if (!isset($products[$i]['cover'])) {
                    $products[$i]['cover'] = $this->context->language->iso_code . '-default';
                }
            }
        }
        $this->html .= '
        <table class="table">
            <thead>
                <tr>
                    <th class="first_item" style="width:600px;">' . $this->l('Product') . '</th>
                    <th class="item" style="text-align:center;width:150px;">' . $this->l('Quantity') . '</th>
                    <th class="item" style="text-align:center;width:150px;">' . $this->l('Priority') . '</th>
                </tr>
            </thead>
            <tbody>';
        $priority = [$this->l('High'), $this->l('Medium'), $this->l('Low')];
        foreach ($products as $product) {
            $tmp = $this->context->link->getImageLink(
                $product['link_rewrite'],
                $product['cover'],
                ImageType::getFormattedName('small')
            );
            $this->html .= '
                <tr>
                    <td class="first_item">
                        <img src="' . $tmp . '" alt="' . htmlentities($product['name'], ENT_COMPAT, 'UTF-8')
                         . '" style="float:left;" />
                        ' . $product['name'];
            if (isset($product['attributes_small'])) {
                $this->html .= '<br /><i>' . htmlentities($product['attributes_small'], ENT_COMPAT, 'UTF-8') . '</i>';
            }
            $this->html .= '
                    </td>
                    <td class="item" style="text-align:center;">' . (int) $product['quantity'] . '</td>
                    <td class="item" style="text-align:center;">' . $priority[(int) $product['priority'] % 3] . '</td>
                </tr>';
        }
        $this->html .= '</tbody></table>';
    }

    public function hookAdminCustomers($params)
    {
        $customer = new Customer((int) $params['id_customer']);
        if (!Validate::isLoadedObject($customer)) {
            exit(Tools::displayError());
        }

        $this->html = '<h2>' . $this->l('Wishlists') . '</h2>';

        $wishlists = FunWishList::getByIdCustomer((int) $customer->id);
        if (!count($wishlists)) {
            $this->html .= $customer->lastname . ' ' . $customer->firstname . ' ' . $this->l('No wishlist . ');
        } else {
            $this->html .= '<form action="' . Tools::safeOutput($_SERVER['REQUEST_URI']) . '" method="post" id="listing">';

            $id_wishlist = (int) Tools::getValue('id_wishlist');
            if (!$id_wishlist) {
                $id_wishlist = $wishlists[0]['id_wishlist'];
            }

            $this->html .= '<span>' . $this->l('Wishlist') . ': </span> <select name="id_wishlist" '
                 . 'onchange="$(\'#listing\').submit();">';

            if (is_array($wishlists)) {
                foreach ($wishlists as $wishlist) {
                    $this->html .= '<option value="' . (int) $wishlist['id_wishlist'] . '"';
                    if ($wishlist['id_wishlist'] == $id_wishlist) {
                        $this->html .= ' selected="selected"';
                        // $counter = $wishlist['counter'];
                    }
                    $this->html .= '>' . htmlentities($wishlist['name'], ENT_COMPAT, 'UTF-8') . '</option>';
                }
            }
            $this->html .= '</select>';

            $this->displayProducts((int) $id_wishlist);

            $this->html .= '</form><br />';

            return $this->html;
        }
    }

    // Display Error from controler
    public function errorLogged()
    {
        return $this->l('You must be logged in to manage your wishlists . ');
    }

    public function renderJS()
    {
        return "<script>
            $(document).ready(function () { $('#id_customer, #id_wishlist').change( function () "
                . "{ $('#module_form').submit();}); });
        </script>";
    }

    public function renderForm()
    {
        $customers = [];
        foreach (FunWishList::getCustomers() as $c) {
            $customers[$c['id_customer']]['id_customer'] = $c['id_customer'];
            $customers[$c['id_customer']]['name'] = $c['firstname'] . ' ' . $c['lastname'];
        }

        $fields_form = [
            'form' => [
                'legend' => [
                    'title' => $this->l('Wishlist Data Listing'),
                    'icon' => 'icon-cogs',
                ],
                'input' => [
                    [
                        'type' => 'select',
                        'label' => $this->l('Customers :'),
                        'name' => 'id_customer',
                        'options' => [
                            'default' => ['value' => 0, 'label' => $this->l('Choose customer')],
                            'query' => $customers,
                            'id' => 'id_customer',
                            'name' => 'name',
                        ],
                    ],
                ],
            ],
        ];

        if ($id_customer = Tools::getValue('id_customer')) {
            $wishlists = FunWishList::getByIdCustomer($id_customer);
            $fields_form['form']['input'][] = [
                'type' => 'select',
                'label' => $this->l('Wishlist :'),
                'name' => 'id_wishlist',
                'options' => [
                    'default' => ['value' => 0, 'label' => $this->l('Choose wishlist')],
                    'query' => $wishlists,
                    'id' => 'id_wishlist',
                    'name' => 'name',
                ],
            ];
        }

        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $lang = new Language((int) Configuration::get('PS_LANG_DEFAULT'));
        $helper->default_form_language = $lang->id;
        $tmp = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG');
        $helper->allow_employee_form_lang = $tmp ? $tmp : 0;

        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitModule';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false) . '&configure=' . $this->name
             . '&tab_module=' . $this->tab . '&module_name=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->tpl_vars = [
            'fields_value' => $this->getConfigFieldsValues(),
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        ];

        return $helper->generateForm([$fields_form]);
    }

    public function getConfigFieldsValues()
    {
        return [
            'id_customer' => Tools::getValue('id_customer'),
            'id_wishlist' => Tools::getValue('id_wishlist'),
        ];
    }

    public function renderList($id_wishlist)
    {
        $wishlist = new FunWishList($id_wishlist);
        $products = FunWishList::getProductByIdCustomer(
            $id_wishlist,
            $wishlist->id_customer,
            $this->context->language->id
        );

        foreach ($products as $key => $val) {
            $image = Image::getCover($val['id_product']);
            $products[$key]['image'] = $this->context->link->getImageLink(
                $val['link_rewrite'],
                $image['id_image'],
                ImageType::getFormattedName('small')
            );
        }

        $fields_list = [
            'image' => [
                'title' => $this->l('Image'),
                'type' => 'image',
            ],
            'name' => [
                'title' => $this->l('Product'),
                'type' => 'text',
            ],
            'attributes_small' => [
                'title' => $this->l('Combination'),
                'type' => 'text',
            ],
            'quantity' => [
                'title' => $this->l('Quantity'),
                'type' => 'text',
            ],
            'priority' => [
                'title' => $this->l('Priority'),
                'type' => 'priority',
                'values' => [$this->l('High'), $this->l('Medium'), $this->l('Low')],
            ],
        ];

        $helper = new HelperList();
        $helper->shopLinkType = '';
        $helper->simple_header = true;
        $helper->no_link = true;
        $helper->actions = ['view'];
        $helper->show_toolbar = false;
        $helper->module = $this;
        $helper->identifier = 'id_product';
        $helper->title = $this->l('Product list');
        $helper->table = $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex . '&configure=' . $this->name;
        $helper->tpl_vars = ['priority' => [$this->l('High'), $this->l('Medium'), $this->l('Low')]];

        return $helper->generateList($products, $fields_list);
    }
}
