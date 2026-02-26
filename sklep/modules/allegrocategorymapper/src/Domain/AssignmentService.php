<?php
namespace ACM\Domain;
use Product;
use Search;
use Db;
use Context;

class AssignmentService
{
    public function moveProductToCategories($id_product, array $categoryIds, $changeDefault = false)
    {
        $id_product = (int)$id_product;
        if (empty($categoryIds)) {
            return 0;
        }
        $product = new Product($id_product);
        if (!$product->id) {
            return 0;
        }
        
        $product->updateCategories($categoryIds);

        if ($changeDefault) {
            $newDefaultCategoryId = (int)reset($categoryIds);
            Db::getInstance()->update(
                'product',
                ['id_category_default' => $newDefaultCategoryId],
                'id_product = ' . $id_product
            );
            Db::getInstance()->update(
                'product_shop',
                ['id_category_default' => $newDefaultCategoryId],
                'id_product = ' . $id_product . ' AND id_shop = ' . (int)Context::getContext()->shop->id
            );
        }
        
        Search::indexation(false, $product->id);
        return 1;
    }
}