
<?php
namespace TvCmsMegaMenu\Presenter;

use Context;
use Language;
use Configuration;
use PrestaShop\PrestaShop\Adapter\Presenter\Product\ProductAssembler;
use PrestaShop\PrestaShop\Adapter\Presenter\Product\ProductPresenterFactory;
use PrestaShop\PrestaShop\Core\Product\ProductPresentationSettings;
use PrestaShop\PrestaShop\Core\Product\ProductInterface;

class DynProductPresenter
{
    /** @var Context */
    private $context;

    public function __construct(Context $context)
    {
        $this->context = $context;
    }

    /**
     * Ensure essential context pieces exist in BO (currency/shop can be null on some pages)
     */
    private function ensureContextDefaults(): void
    {
        if (!$this->context->shop || !$this->context->shop->id) {
            $this->context->shop = \Shop::getContextShopID() ? \Shop::getContextShop() : new \Shop(Configuration::get('PS_SHOP_DEFAULT'));
        }
        if (!$this->context->currency) {
            $this->context->currency = \Currency::getCurrencyInstance((int)Configuration::get('PS_CURRENCY_DEFAULT'));
        }
        if (!$this->context->language) {
            $this->context->language = new \Language((int)Configuration::get('PS_LANG_DEFAULT'));
        }
        if (!$this->context->customer) {
            $this->context->customer = new \Customer();
        }
    }

    /**
     * Build array of products based on config
     * $cfg: ['source'=> 'category'|'tag'|'new'|'best'|'special', 'refid'=>int|null, 'limit'=>int, 'sort'=>..., 'layout'=>..., ...]
     */
    public function getProducts(array $cfg): array
    {
        $this->ensureContextDefaults();

        $limit = max(1, (int)($cfg['limit'] ?? 8));
        $idLang = (int)$this->context->language->id;

        $raw = [];
        switch ($cfg['source'] ?? 'category') {
            case 'new':
                $raw = \Product::getNewProducts($idLang, 0, $limit);
                break;
            case 'best':
                $raw = \ProductSale::getBestSalesLight($idLang, 0, $limit);
                break;
            case 'special':
                $raw = \Product::getPricesDrop($idLang, 0, $limit);
                break;
            case 'tag':
                $idTag = (int)($cfg['refid'] ?? 0);
                if ($idTag > 0) {
                    $raw = \Product::getProductsByTag($idLang, (string)$idTag);
                    if (is_array($raw)) {
                        $raw = array_slice($raw, 0, $limit);
                    } else {
                        $raw = [];
                    }
                }
                break;
            case 'category':
            default:
                $idCategory = (int)($cfg['refid'] ?? 0);
                if ($idCategory > 0) {
                    $cat = new \Category($idCategory, $idLang);
                    if (\Validate::isLoadedObject($cat)) {
                        $raw = $cat->getProducts($idLang, 1, $limit);
                    }
                }
                break;
        }

        // Present products
        $factory = new ProductPresenterFactory($this->context);
        $presenter = $factory->getPresenter();
        /** @var ProductPresentationSettings $settings */
        $settings = $factory->getPresentationSettings();

        $out = [];
        foreach ($raw as $productArray) {
            $out[] = $presenter->present(
                $settings,
                $productArray,
                $this->context->language,
                (new ProductAssembler($this->context))->assembleProduct($productArray)
            );
        }
        return $out;
    }
}
