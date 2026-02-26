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

class TvcmsProductCompare extends Module
{
    public function __construct()
    {
        $this->name = 'tvcmsproductcompare';
        $this->tab = 'front_office_features';
        $this->version = '4.0.0';
        $this->author = 'ThemeVolty';
        $this->need_instance = 0;

        /*
         * Set $this->bootstrap to true if your module is compliant with bootstrap (PrestaShop 1.6)
         */
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('ThemeVolty - Product Compare');
        $this->description = $this->l('Compare Product in Front Side');
        $this->ps_versions_compliancy = ['min' => '1.7', 'max' => _PS_VERSION_];
        $this->module_key = '';

        $this->confirmUninstall = $this->l('Warning: all the data saved in your database will be deleted.' .
            ' Are you sure you want uninstall this module?');
    }

    public function install()
    {
        $context = Context::getContext();
        if (!$context->cookie->__isset('tvcmscompare_product_1')) {
            $context->cookie->__set('tvcmscompare_product_1', '');
        }

        if (!$context->cookie->__isset('tvcmscompare_product_2')) {
            $context->cookie->__set('tvcmscompare_product_2', '');
        }

        if (!$context->cookie->__isset('tvcmscompare_product_3')) {
            $context->cookie->__set('tvcmscompare_product_3', '');
        }

        if (!$context->cookie->__isset('tvcmscompare_product_4')) {
            $context->cookie->__set('tvcmscompare_product_4', '');
        }

        return parent::install()
            && $this->registerHook('displayHeader')
            && $this->registerHook('displayNavProductCompareBlock')
            && $this->registerHook('displayCompareBtnSticky')
            && $this->registerHook('displayProductCompareProductPage')
            && $this->registerHook('displayProductComparesticky')
            && $this->registerHook('displayNavProductCompareMobileBlock')
            && $this->registerHook('displayProductCompareProductList');
    }

    public function uninstall()
    {
        $context = Context::getContext();
        if ($context->cookie->__isset('tvcmscompare_product_1')) {
            $context->cookie->__unset('tvcmscompare_product_1', '');
        }

        if ($context->cookie->__isset('tvcmscompare_product_2')) {
            $context->cookie->__unset('tvcmscompare_product_2', '');
        }

        if ($context->cookie->__isset('tvcmscompare_product_3')) {
            $context->cookie->__unset('tvcmscompare_product_3', '');
        }

        if ($context->cookie->__isset('tvcmscompare_product_4')) {
            $context->cookie->__unset('tvcmscompare_product_4', '');
        }

        return parent::uninstall();
    }

    public function hookdisplayHeader($params)
    {
        $this->context->controller->addJS($this->_path . 'views/js/ajax-product-compare.js');
        $this->context->controller->addCss($this->_path . 'views/css/front.css');
    }

    public function hookdisplayNavProductCompareMobileBlock($params)
    {
        $this->countCompareProductCookie();

        return $this->display(__FILE__, 'views/templates/front/display_mobile_top.tpl');
    }

    public function hookdisplayCompareBtnSticky($params)
    {
        $this->countCompareProductCookie();

        return $this->display(__FILE__, 'views/templates/front/display_sticky.tpl');
    }

    // show in Product Details pages
    public function hookdisplayProductAdditionalInfo($params)
    {
        $this->getAllCompareProductAssignInSmarty($params);

        return $this->display(__FILE__, 'views/templates/front/display_product_details.tpl');
    }

    public function hookdisplayProductCompareProductPage($params)
    {
        return $this->hookdisplayProductAdditionalInfo($params);
    }

    public function hookdisplayNavProductCompareBlock($params)
    {
        $this->countCompareProductCookie();

        return $this->display(__FILE__, 'views/templates/front/display_nav.tpl');
    }

    public function hookdisplayProductComparesticky($params)
    {
        $this->countCompareProductCookie();

        return $this->display(__FILE__, 'views/templates/front/display_right_sticky.tpl');
    }

    public function hookDisplayProductCompareProductList($params)
    {
        $this->getAllCompareProductAssignInSmarty($params);

        return $this->display(__FILE__, 'views/templates/front/display_compare_product.tpl');
    }

    public function getAllCompareProductAssignInSmarty($params)
    {
        $context = Context::getContext();
        $this->smarty->assign('prod_1', $context->cookie->__get('tvcmscompare_product_1'));
        $this->smarty->assign('prod_2', $context->cookie->__get('tvcmscompare_product_2'));
        $this->smarty->assign('prod_3', $context->cookie->__get('tvcmscompare_product_3'));
        $this->smarty->assign('prod_4', $context->cookie->__get('tvcmscompare_product_4'));

        $this->smarty->assign('product', $params['product']);
    }

    public function countCompareProductCookie()
    {
        $context = Context::getContext();
        $tmp = 0;
        if ('' != $context->cookie->__get('tvcmscompare_product_1')) {
            ++$tmp;
        }

        if ('' != $context->cookie->__get('tvcmscompare_product_2')) {
            ++$tmp;
        }

        if ('' != $context->cookie->__get('tvcmscompare_product_3')) {
            ++$tmp;
        }

        if ('' != $context->cookie->__get('tvcmscompare_product_4')) {
            ++$tmp;
        }
        $this->smarty->assign('tot_comp_prod', $tmp);
    }

    public function getAllCompareProduct()
    {
        $context = Context::getContext();
        $cookie = Context::getContext()->cookie;
        $id_lang = $cookie->id_lang;
        $id_currency = $cookie->id_currency;
        $id_group = $context->customer->id_default_group;
        $id_shop = $context->shop->id;
        $id_country = Configuration::get('PS_COUNTRY_DEFAULT');

        $prod_info_1 = [];
        $prod_info_2 = [];
        $prod_info_3 = [];
        $prod_info_4 = [];
        $prod_feature = [];

        if ('' != $context->cookie->__get('tvcmscompare_product_1')) {
            $prod_info_1['prod'] = new Product($context->cookie->__get('tvcmscompare_product_1'));
            $prod_info_1['all_img'] = Image::getImages($id_lang, $context->cookie->__get('tvcmscompare_product_1'));
            $prod_info_1['prod_attr'] = Product::getFrontFeaturesStatic(
                $id_lang,
                $context->cookie->__get('tvcmscompare_product_1')
            );
            $prod_info_1['prod_price'] = Tools::displayPrice($prod_info_1['prod']->price);
            $prod_info_1['special_price'] = SpecificPrice::getSpecificPrice(
                $context->cookie->__get('tvcmscompare_product_1'),
                $id_shop,
                $id_currency,
                $id_country,
                $id_group,
                1,
                null,
                0,
                0,
                1
            );

            if ($prod_info_1['special_price']) {
                if ('percentage' == $prod_info_1['special_price']['reduction_type']) {
                    $prod_info_1['special_price']['reduction'] = $prod_info_1['special_price']['reduction'] * 100;
                    $discount = $prod_info_1['prod']->price * $prod_info_1['special_price']['reduction'] / 100;
                    $discount_after_price = $prod_info_1['prod']->price - $discount;

                    $prod_info_1['special_price']['reduction'] = '-' . $prod_info_1['special_price']['reduction'] . '%';
                    $prod_info_1['special_price']['discount_after_price'] = Tools::displayPrice($discount_after_price);
                } else {
                    // $prod_info_1['special_price']['reduction'];
                    $discount_after_price = $prod_info_1['prod']->price - $prod_info_1['special_price']['reduction'];

                    $prod_info_1['special_price']['reduction'] = Tools::displayPrice(
                        $prod_info_1['special_price']['reduction']
                    );
                    $prod_info_1['special_price']['discount_after_price'] = Tools::displayPrice($discount_after_price);
                }
            }

            foreach ($prod_info_1['prod_attr'] as $feature) {
                if (!in_array($feature['name'], $prod_feature)) {
                    $prod_feature[] = $feature['name'];
                }
            }
        }

        if ('' != $context->cookie->__get('tvcmscompare_product_2')) {
            $prod_info_2['prod'] = new Product($context->cookie->__get('tvcmscompare_product_2'));
            $prod_info_2['all_img'] = Image::getImages($id_lang, $context->cookie->__get('tvcmscompare_product_2'));
            $prod_info_2['prod_attr'] = Product::getFrontFeaturesStatic(
                $id_lang,
                $context->cookie->__get('tvcmscompare_product_2')
            );
            $prod_info_2['prod_price'] = Tools::displayPrice($prod_info_2['prod']->price);
            $prod_info_2['special_price'] = SpecificPrice::getSpecificPrice(
                $context->cookie->__get('tvcmscompare_product_2'),
                $id_shop,
                $id_currency,
                $id_country,
                $id_group,
                1,
                null,
                0,
                0,
                1
            );

            if ($prod_info_2['special_price']) {
                if ('percentage' == $prod_info_2['special_price']['reduction_type']) {
                    $prod_info_2['special_price']['reduction'] = $prod_info_2['special_price']['reduction'] * 100;
                    $discount = $prod_info_2['prod']->price * $prod_info_2['special_price']['reduction'] / 100;
                    $discount_after_price = $prod_info_2['prod']->price - $discount;
                    $prod_info_2['special_price']['reduction'] = '-' . $prod_info_2['special_price']['reduction'] . '%';
                    $prod_info_2['special_price']['discount_after_price'] = Tools::displayPrice($discount_after_price);
                } else {
                    // $prod_info_2['special_price']['reduction'];
                    $discount_after_price = $prod_info_2['prod']->price - $prod_info_2['special_price']['reduction'];

                    $prod_info_2['special_price']['reduction'] = Tools::displayPrice(
                        $prod_info_2['special_price']['reduction']
                    );
                    $prod_info_2['special_price']['discount_after_price'] = Tools::displayPrice($discount_after_price);
                }
            }

            foreach ($prod_info_2['prod_attr'] as $feature) {
                if (!in_array($feature['name'], $prod_feature)) {
                    $prod_feature[] = $feature['name'];
                }
            }
        }

        if ('' != $context->cookie->__get('tvcmscompare_product_3')) {
            $prod_info_3['prod'] = new Product($context->cookie->__get('tvcmscompare_product_3'));
            $prod_info_3['all_img'] = Image::getImages($id_lang, $context->cookie->__get('tvcmscompare_product_3'));
            $prod_info_3['prod_attr'] = Product::getFrontFeaturesStatic(
                $id_lang,
                $context->cookie->__get('tvcmscompare_product_3')
            );
            $prod_info_3['prod_attr'] = Product::getFrontFeaturesStatic(
                $id_lang,
                $context->cookie->__get('tvcmscompare_product_3')
            );
            $prod_info_3['prod_price'] = Tools::displayPrice($prod_info_3['prod']->price);
            $prod_info_3['special_price'] = SpecificPrice::getSpecificPrice(
                $context->cookie->__get('tvcmscompare_product_3'),
                $id_shop,
                $id_currency,
                $id_country,
                $id_group,
                1,
                null,
                0,
                0,
                1
            );

            if ($prod_info_3['special_price']) {
                if ('percentage' == $prod_info_3['special_price']['reduction_type']) {
                    $prod_info_3['special_price']['reduction'] = $prod_info_3['special_price']['reduction'] * 100;
                    $discount = $prod_info_3['prod']->price * $prod_info_3['special_price']['reduction'] / 100;
                    $discount_after_price = $prod_info_3['prod']->price - $discount;

                    $prod_info_3['special_price']['reduction'] = '-' . $prod_info_3['special_price']['reduction'] . '%';
                    $prod_info_3['special_price']['discount_after_price'] = Tools::displayPrice($discount_after_price);
                } else {
                    // $prod_info_3['special_price']['reduction'];
                    $discount_after_price = $prod_info_3['prod']->price - $prod_info_3['special_price']['reduction'];

                    $prod_info_3['special_price']['reduction'] = Tools::displayPrice(
                        $prod_info_3['special_price']['reduction']
                    );
                    $prod_info_3['special_price']['discount_after_price'] = Tools::displayPrice($discount_after_price);
                }
            }

            foreach ($prod_info_3['prod_attr'] as $feature) {
                if (!in_array($feature['name'], $prod_feature)) {
                    $prod_feature[] = $feature['name'];
                }
            }
        }

        if ('' != $context->cookie->__get('tvcmscompare_product_4')) {
            $prod_info_4['prod'] = new Product($context->cookie->__get('tvcmscompare_product_4'));
            $prod_info_4['all_img'] = Image::getImages($id_lang, $context->cookie->__get('tvcmscompare_product_4'));
            $prod_info_4['prod_attr'] = Product::getFrontFeaturesStatic(
                $id_lang,
                $context->cookie->__get('tvcmscompare_product_4')
            );
            $prod_info_4['prod_attr'] = Product::getFrontFeaturesStatic(
                $id_lang,
                $context->cookie->__get('tvcmscompare_product_4')
            );
            $prod_info_4['prod_price'] = Tools::displayPrice($prod_info_4['prod']->price);
            $prod_info_4['special_price'] = SpecificPrice::getSpecificPrice(
                $context->cookie->__get('tvcmscompare_product_4'),
                $id_shop,
                $id_currency,
                $id_country,
                $id_group,
                1,
                null,
                0,
                0,
                1
            );

            if ($prod_info_4['special_price']) {
                if ('percentage' == $prod_info_4['special_price']['reduction_type']) {
                    $prod_info_4['special_price']['reduction'] = $prod_info_4['special_price']['reduction'] * 100;
                    $discount = $prod_info_4['prod']->price * $prod_info_4['special_price']['reduction'] / 100;
                    $discount_after_price = $prod_info_4['prod']->price - $discount;

                    $prod_info_4['special_price']['reduction'] = '-' . $prod_info_4['special_price']['reduction'] . '%';
                    $prod_info_4['special_price']['discount_after_price'] = Tools::displayPrice($discount_after_price);
                } else {
                    // $prod_info_4['special_price']['reduction'];
                    $discount_after_price = $prod_info_4['prod']->price - $prod_info_4['special_price']['reduction'];

                    $prod_info_4['special_price']['reduction'] = Tools::displayPrice(
                        $prod_info_4['special_price']['reduction']
                    );
                    $prod_info_4['special_price']['discount_after_price'] = Tools::displayPrice($discount_after_price);
                }
            }

            foreach ($prod_info_4['prod_attr'] as $feature) {
                if (!in_array($feature['name'], $prod_feature)) {
                    $prod_feature[] = $feature['name'];
                }
            }
        }
        $prod_list = [];
        $prod_list['id_lang'] = $id_lang;
        $prod_list['id_currency'] = $id_currency;
        $prod_list['prod_1'] = $prod_info_1;
        $prod_list['prod_2'] = $prod_info_2;
        $prod_list['prod_3'] = $prod_info_3;
        $prod_list['prod_4'] = $prod_info_4;
        $prod_list['all_feature'] = $prod_feature;

        return $prod_list;
    }
}
