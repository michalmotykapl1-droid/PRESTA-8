<?php

class AzadaImportCalculator
{
    /**
     * Oblicza cenę i logikę jednostek
     */
    public static function calculatePriceLogic($priceRaw, $unitRaw, $weightRaw, $settings = [])
    {
        $unit = strtolower(trim($unitRaw));
        $weight = (float)$weightRaw;
        $price = (float)$priceRaw;

        $result = [
            'final_price' => $price,
            'unit_price' => 0.0,
            'unity' => '',
            'min_qty' => 1
        ];

        // LOGIKA: Cena za kg, ale sprzedaż na opakowania
        if (($unit === 'kg' || $unit === 'l') && $weight > 0) {
            $result['final_price'] = $price * $weight;
            $result['unit_price'] = $price;
            $result['unity'] = $unit;
        }

        // LOGIKA: Minimalne zamówienie
        if (isset($settings['force_min_qty_from_weight']) && $settings['force_min_qty_from_weight']) {
            if ($weight > 1) {
                $result['min_qty'] = (int)$weight; 
            }
        }
        return $result;
    }
}