<?php
use PrestaShop\PrestaShop\Adapter\Presenter\Product\ProductPresenterFactory;
if (!defined('_PS_VERSION_')) { exit; }
class TvcmsDynProductsProvider
{
    public static function fetchRaw(Context $context, array $cfg)
    {
        $idLang = (int) $context->language->id;
        $limit = max(1, (int)($cfg['limit'] ?? 8));
        $src   = (string)($cfg['source'] ?? 'category');
        $refid = isset($cfg['refid']) ? (int)$cfg['refid'] : 0;
        switch ($src) {
            case 'new':
                $rows = Product::getNewProducts($idLang, 0, $limit, false, 'date_add', 'DESC'); break;
            case 'best':
                $rows = ProductSale::getBestSalesLight($idLang, 0, $limit); break;
            case 'special':
                $rows = Product::getPricesDrop($idLang, 0, $limit, false, 'reduction', 'DESC'); break;
            case 'tag':
                $rows = [];
                if ($refid) {
                    $res = Db::getInstance()->executeS('
                        SELECT p.* FROM '._DB_PREFIX_.'product p
                        INNER JOIN '._DB_PREFIX_.'product_tag pt ON(pt.id_product=p.id_product)
                        WHERE pt.id_tag='.(int)$refid.'
                        ORDER BY p.id_product DESC
                        LIMIT '.(int)$limit);
                    $rows = $res ?: [];
                }
                break;
            case 'category':
            default:
                $rows = [];
                if ($refid) {
                    $category = new Category($refid, $idLang);
                    if (Validate::isLoadedObject($category)) {
                        $rows = $category->getProducts($idLang, 1, $limit, 'position', 'ASC');
                    }
                }
                break;
        }
        return $rows ?: [];
    }
    public static function present(Context $context, array $rows)
    {
        $out = [];
        $idLang = (int)$context->language->id;
        foreach ($rows as $r) {
            $out[] = Product::getProductProperties($idLang, $r);
        }
        // soften context to avoid warnings
        if (!$context->customer || !$context->customer->id) {
            $context->customer = new Customer();
            $context->customer->id = 0;
            $context->customer->id_default_group = (int)Configuration::get('PS_UNIDENTIFIED_GROUP');
        }
        if (!$context->country || !$context->country->id) {
            $context->country = new Country(Configuration::get('PS_COUNTRY_DEFAULT'));
        }
        if (!$context->cart) { $context->cart = new Cart(); }
        if (!$context->link) { $context->link = new Link(); }
        $factory   = new ProductPresenterFactory($context);
        $presenter = $factory->getPresenter();
        $settings  = $factory->getPresentationSettings();
        $final = [];
        foreach ($out as $row) {
            $final[] = $presenter->present($settings, $row, $context->language);
        }
        return $final;
    }
}