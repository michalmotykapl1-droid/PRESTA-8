<?php
namespace TvCmsMegaMenu\Presenter;

class SimplePresenter {
    public static function presentMany($idLang, $context, array $products) {
        $out = [];
        foreach ($products as $row) {
            if (is_array($row)) {
                $out[] = \Product::getProductProperties((int)$idLang, $row, $context);
            }
        }
        return $out;
    }
}
