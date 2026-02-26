<?php
/**
 * Logika pobierania produktów dla modułu FrequentlyBoughtTogether
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

use PrestaShop\PrestaShop\Adapter\Image\ImageRetriever;
use PrestaShop\PrestaShop\Adapter\Product\PriceFormatter;
use PrestaShop\PrestaShop\Core\Product\ProductListingPresenter;
use PrestaShop\PrestaShop\Adapter\Product\ProductColorsRetriever;

class FbtProductProvider
{
    private $context;
    private $db;

    public function __construct($context)
    {
        $this->context = $context;
        $this->db = Db::getInstance();
    }

    /**
     * Pobiera losowe produkty z tej samej kategorii co produkt bieżący.
     *
     * @param int $id_product ID aktualnie oglądanego produktu (aby go wykluczyć)
     * @param int $id_category ID kategorii domyślnej
     * @param int $limit Ilość produktów do pobrania
     * @return array Tablica gotowych produktów do wyświetlenia w Smarty
     */
    public function getRelatedProducts(int $id_product, int $id_category, int $limit = 2): array
    {
        // 1. Pobierz ID losowych produktów z bazy
        $sql = 'SELECT p.id_product
                FROM `' . _DB_PREFIX_ . 'product` p
                INNER JOIN `' . _DB_PREFIX_ . 'product_shop` ps ON (p.id_product = ps.id_product AND ps.id_shop = ' . (int)$this->context->shop->id . ')
                WHERE p.id_category_default = ' . (int)$id_category . '
                AND p.id_product != ' . (int)$id_product . '
                AND ps.active = 1
                AND ps.visibility IN ("both", "catalog")
                ORDER BY RAND()
                LIMIT ' . (int)$limit;

        $result = $this->db->executeS($sql);

        if (!$result) {
            return [];
        }

        // 2. Przygotuj fabryki PrestaShop do "ubrania" produktów w ceny, zdjęcia i linki
        $assembler = new ProductAssembler($this->context);
        $presenterFactory = new ProductPresenterFactory($this->context);
        $presentationSettings = $presenterFactory->getPresentationSettings();
        $presenter = new ProductListingPresenter(
            new ImageRetriever($this->context->link),
            $this->context->link,
            new PriceFormatter(),
            new ProductColorsRetriever($this->context),
            $this->context->getTranslator()
        );

        $products_for_template = [];

        foreach ($result as $row) {
            $rawProduct = $assembler->assembleProduct(['id_product' => $row['id_product']]);
            
            if ($rawProduct) {
                $products_for_template[] = $presenter->present(
                    $presentationSettings,
                    $rawProduct,
                    $this->context->language
                );
            }
        }

        return $products_for_template;
    }
}