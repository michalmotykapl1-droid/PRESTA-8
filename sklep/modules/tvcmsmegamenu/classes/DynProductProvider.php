<?php
namespace TvCmsMegaMenu;

if (!defined('_PS_VERSION_')) {
    exit;
}

class DynProductProvider
{
    /**
     * Fetch products for given source.
     * Returns a light array with fields needed by the preview template.
     */
    public static function get($source, $limit = 8, $refid = 0, $sort = 'position', $withPrice = true)
    {
        $ctx = \Context::getContext();
        $idLang = (int)$ctx->language->id;
        $idShop = (int)$ctx->shop->id;

        // make sure there is a customer in context to avoid Product price warnings
        if (!$ctx->customer || !$ctx->customer->id) {
            $ctx->customer = new \Customer(); // anonymous
            $ctx->customer->id_default_group = (int)\Configuration::get('PS_UNIDENTIFIED_GROUP');
        }

        $products = [];
        try {
            switch ($source) {
                case 'new':
                    $products = \Product::getNewProducts($idLang, 0, $limit);
                    break;
                case 'best':
                    if (class_exists('\ProductSale')) {
                        $products = \ProductSale::getBestSalesLight($idLang, 0, $limit);
                    }
                    break;
                case 'special':
                    $products = \Product::getPricesDrop($idLang, 0, $limit, false, 'date_add', 'DESC');
                    break;
                case 'category':
                    if ($refid > 0) {
                        $cat = new \Category((int)$refid, $idLang, $idShop);
                        $products = $cat->getProducts($idLang, 1, $limit, $sort, 'ASC');
                    }
                    break;
                case 'tag':
                    if ($refid > 0 && class_exists('\Tag')) {
                        $tag = new \Tag();
                        $products = $tag->getProductsByTag($idLang, $refid);
                        if (is_array($products)) {
                            $products = array_slice($products, 0, $limit);
                        } else {
                            $products = [];
                        }
                    }
                    break;
            }
        } catch (\Exception $e) {
            $products = [];
        }

        // Normalize fields used by template
        $out = [];
        foreach ((array)$products as $p) {
            $id = (int)($p['id_product'] ?? $p['id'] ?? 0);
            if (!$id) { continue; }
            $link = $ctx->link->getProductLink($id);
            $name = (string)($p['name'] ?? $p['legend'] ?? '');
            $cover = null;
            try {
                $img = \Product::getCover($id);
                if ($img && isset($img['id_image'])) {
                    $cover = $ctx->link->getImageLink($id.'-'.$img['id_image'], $id.'-'.$img['id_image'].'/home_default');
                }
            } catch (\Exception $e) {}
            $price = null;
            if ($withPrice) {
                try {
                    $price = \Tools::displayPrice(\Product::getPriceStatic($id));
                } catch (\Exception $e) { $price = null; }
            }
            $onSale = !empty($p['on_sale']) || (!empty($p['reduction']) || !empty($p['specific_prices']));
            $out[] = [
                'id' => $id,
                'name' => $name,
                'url' => $link,
                'image' => $cover,
                'price' => $price,
                'on_sale' => $onSale,
            ];
        }
        return $out;
    }
}
