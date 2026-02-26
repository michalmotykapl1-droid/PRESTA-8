<?php

class AzadaSettingsAdvanced
{
    public static function getForm()
    {
        return [
            'form' => [
                'legend' => ['title' => 'Ustawienia zaawansowane', 'icon' => 'icon-wrench'],
                'input' => [
                    [
                        'type' => 'switch', 'label' => 'Wyczyść cache po imporcie',
                        'name' => 'AZADA_CLEAR_CACHE', // Placeholder
                        'is_bool' => true,
                        'values' => [['id' => 'on', 'value' => 1, 'label' => 'Tak'], ['id' => 'off', 'value' => 0, 'label' => 'Nie']],
                        'desc' => 'Opcja zalecana, jeśli zmiany nie są widoczne od razu na sklepie.'
                    ],
                    [
                        'type' => 'switch',
                        'label' => 'Pozwalaj łączyć z istniejącymi produktami (EAN/SKU)',
                        'name' => 'AZADA_LINK_EXISTING_PRODUCTS',
                        'is_bool' => true,
                        'values' => [['id' => 'on', 'value' => 1, 'label' => 'Tak'], ['id' => 'off', 'value' => 0, 'label' => 'Nie']],
                        'desc' => 'Gdy WŁĄCZONE: w Poczekalni pojawi się przycisk „Połącz” dla produktów wykrytych w PrestaShop (utworzonych poza modułem). ' .
                            'Po połączeniu produkt będzie aktualizowany przez ten moduł (stany/ceny itp.). ' .
                            'Gdy WYŁĄCZONE: moduł nie łączy się z istniejącymi produktami i nie pokazuje edycji – zamiast tego tworzy nowy produkt (z automatycznym sufiksem _2/_3 dla SKU, jeśli trzeba).'
                    ]
                ],
                'submit' => ['title' => 'Zapisz', 'class' => 'btn btn-primary pull-right']
            ]
        ];
    }
}