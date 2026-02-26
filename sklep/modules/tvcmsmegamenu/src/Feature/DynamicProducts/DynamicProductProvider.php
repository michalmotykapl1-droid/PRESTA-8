<?php
namespace TvCmsMegaMenu\Feature\DynamicProducts;

use Context;
use Db;
use Product;
use Configuration;

class DynamicProductProvider
{
    private $context;
    public function __construct(Context $context) { $this->context = $context; }

    /**
     * @return array<int, array> products ready for ProductPresenter
     */
    public function fetch(Query $q): array
    {
        $idLang = (int)$this->context->language->id;
        $idShop = (int)$this->context->shop->id;

        switch ($q->source) {
            case 'category':
                if (!$q->categoryId) return [];
                $products = $this->getByCategory($q->categoryId, $idLang, $idShop, $q->sort, $q->limit);
                break;
            case 'tag':
                if (!$q->tagId) return [];
                $products = $this->getByTag($q->tagId, $idLang, $idShop, $q->limit);
                break;
            case 'new':
                $products = $this->getNew($idLang, $idShop, $q->limit);
                break;
            case 'on_sale':
                $products = $this->getOnSale($idLang, $idShop, $q->limit);
                break;
            case 'best_sellers':
                $products = $this->getBestSellers($idLang, $idShop, $q->limit);
                break;
            default:
                $products = [];
        }

        // Normalizacja przez Product::getProductsProperties
        return Product::getProductsProperties($idLang, $products);
    }

    private function getByCategory(int $idCategory, int $idLang, int $idShop, string $sort, int $limit): array
    {
        // Mapowanie sortowania
        $orderBy = 'p.date_add'; $orderWay = 'DESC';
        if ($sort === 'price_asc') { $orderBy = 'price'; $orderWay = 'ASC'; }
        elseif ($sort === 'price_desc') { $orderBy = 'price'; $orderWay = 'DESC'; }
        elseif ($sort === 'name_asc') { $orderBy = 'pl.name'; $orderWay = 'ASC'; }
        elseif ($sort === 'name_desc') { $orderBy = 'pl.name'; $orderWay = 'DESC'; }

        // Użyj istniejącej metody PS do pobierania z kategorii (szybciej/bez bugów)
        $cat = new \Category($idCategory, $idLang, $idShop);
        $raw = $cat->getProducts($idLang, 1, $limit, $orderBy, $orderWay, false, true, true, $limit);
        return $raw ?: [];
    }

    private function getNew(int $idLang, int $idShop, int $limit): array
    {
        $nbDaysNewProduct = (int)Configuration::get('PS_NB_DAYS_NEW_PRODUCT');
        $raw = Product::getNewProducts($idLang, 0, $limit, false, $idShop, $nbDaysNewProduct);
        return $raw ?: [];
    }

    private function getOnSale(int $idLang, int $idShop, int $limit): array
    {
        $raw = Product::getPricesDrop($idLang, 0, $limit, false, $idShop);
        return $raw ?: [];
    }

    private function getBestSellers(int $idLang, int $idShop, int $limit): array
    {
        $raw = Product::getBestSales($idLang, 0, $limit, $idShop);
        return $raw ?: [];
    }

    private function getByTag(int $idTag, int $idLang, int $idShop, int $limit): array
    {
        $sql = 'SELECT p.*, pl.*, p.id_product
                FROM '._DB_PREFIX_.'product p
                LEFT JOIN '._DB_PREFIX_.'product_lang pl ON (pl.id_product=p.id_product AND pl.id_lang='.(int)$idLang.' AND pl.id_shop='.(int)$idShop.')
                LEFT JOIN '._DB_PREFIX_.'product_tag pt ON (pt.id_product=p.id_product)
                WHERE pt.id_tag='.(int)$idTag.' AND p.active=1
                GROUP BY p.id_product
                ORDER BY p.date_add DESC
                LIMIT '.(int)$limit;
        $rows = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql);
        return $rows ?: [];
    }
}
