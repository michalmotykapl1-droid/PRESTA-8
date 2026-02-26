<?php

namespace TvcmsSearch\Services;

use Context;
use Product;
use Feature;

class DietFeatureService
{
    private $context;

    public function __construct(Context $context)
    {
        $this->context = $context;
    }

    public function getFeaturesForProducts(array $products): array
    {
        if (empty($products)) {
            return [
                'unique_features' => [],
                'product_features_map' => [],
            ];
        }

        $id_lang = $this->context->language->id;
        $unique_features = [];
        $product_features_map = [];
        $feature_group_ids_found = [];

        // ====================================================================
        // START POPRAWKI: Dodanie ID 22 (Dieta: Niski Indeks Glikemiczny)
        // ====================================================================
        
        // Tablica z ID cech, które mają być użyte jako filtry dietetyczne
        $dietary_feature_ids = [13, 14, 15, 16, 17, 18, 20, 22]; // DODANO ID 22

        // ====================================================================
        // KONIEC POPRAWKI
        // ====================================================================

        foreach ($products as $product) {
            $id_product = (int)$product['id_product'];
            $features = Product::getFeaturesStatic($id_product);
            
            $product_features_map[$id_product] = [];

            if (!empty($features)) {
                foreach ($features as $feature) {
                    $id_feature_group = (int)$feature['id_feature'];
                    
                    if (in_array($id_feature_group, $dietary_feature_ids, true)) {
                        if (!in_array($id_feature_group, $product_features_map[$id_product])) {
                           $product_features_map[$id_product][] = $id_feature_group;
                        }
                        
                        if (!in_array($id_feature_group, $feature_group_ids_found, true)) {
                            $feature_group_ids_found[] = $id_feature_group;
                            $feature_group = new Feature($id_feature_group, $id_lang);
                            $unique_features[$id_feature_group] = [
                                'id_feature' => $id_feature_group,
                                'name' => $feature_group->name,
                            ];
                        }
                    }
                }
            }
            // USUNIĘTO BŁĘDNĄ LINIĘ, KTÓRA BYŁA TUTAJ
        }
        
        uasort($unique_features, function ($a, $b) {
            return strcoll($a['name'], $b['name']);
        });

        return [
            'unique_features' => $unique_features,
            'product_features_map' => $product_features_map,
        ];
    }
}