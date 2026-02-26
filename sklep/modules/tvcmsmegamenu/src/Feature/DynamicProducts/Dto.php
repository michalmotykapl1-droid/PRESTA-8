<?php
namespace TvCmsMegaMenu\Feature\DynamicProducts;

class Query
{
    /** @var string enum: category, tag, new, on_sale, best_sellers */
    public $source = 'category';
    /** @var int|null */
    public $categoryId = null;
    /** @var int|null */
    public $tagId = null;
    /** @var int limit produktów */
    public $limit = 6;
    /** @var string enum: newest, price_asc, price_desc, name_asc, name_desc */
    public $sort = 'newest';
    /** @var string enum: grid, list */
    public $layout = 'grid';
    /** @var bool */
    public $showPrice = true;
    /** @var bool */
    public $showPromoBadge = true;

    public static function fromArray(array $data): self
    {
        $q = new self();
        foreach ($data as $k => $v) {
            if (property_exists($q, $k)) {
                $q->$k = $v;
            }
        }
        return $q;
    }
}
