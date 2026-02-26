<?php

class TvcmsCategoryService
{
    private function getCategoryConfig()
    {
        return [
            585 => 'fa-solid fa-wheat-awn', 
            634 => 'fa-solid fa-mug-hot',
            627 => 'fa-solid fa-mug-saucer',
            560 => 'fa-solid fa-seedling',
            261675 => 'fa-solid fa-bottle-droplet',
            548 => 'fa-solid fa-cubes-stacked',
            581 => 'fa-solid fa-utensils',
            592 => 'fa-solid fa-leaf',
            546 => 'fa-solid fa-cake-candles',
            740 => 'fa-solid fa-droplet',
        ];
    }

    public function getCategoriesData($id_lang, $id_shop)
    {
        $config = $this->getCategoryConfig();
        $categories_data = [];
        $link = Context::getContext()->link;

        foreach ($config as $id_category => $icon_name) {
            $category = new Category((int)$id_category, $id_lang, $id_shop);

            if (Validate::isLoadedObject($category) && $category->active) {
                $categories_data[] = [
                    'id'   => $category->id,
                    'name' => $category->name,
                    'link' => $link->getCategoryLink($category),
                    'icon' => $icon_name 
                ];
            }
        }

        return $categories_data;
    }
}