<?php

class AzadaSettingsQty
{
    public static function getForm()
    {
        return [
            'form' => [
                'legend' => ['title' => 'Ustawienia ilości i dostępności', 'icon' => 'icon-archive'],
                'input' => [
                    [
                        'type' => 'switch', 'label' => 'Aktualizuj: Stany magazynowe',
                        'name' => 'AZADA_UPD_QTY', 
                        'is_bool' => true,
                        'values' => [['id' => 'on', 'value' => 1, 'label' => 'Tak'], ['id' => 'off', 'value' => 0, 'label' => 'Nie']],
                    ],
                    [
                        'type' => 'switch', 'label' => 'Aktualizuj: Minimalną ilość zamówienia',
                        'name' => 'AZADA_UPD_MIN_QTY', 
                        'is_bool' => true,
                        'values' => [['id' => 'on', 'value' => 1, 'label' => 'Tak'], ['id' => 'off', 'value' => 0, 'label' => 'Nie']],
                        'desc' => 'Przydatne, jeśli hurtownia sprzedaje tylko w "zgrzewkach".'
                    ],
                    [
                        'type' => 'select', 'label' => 'Gdy ilość spadnie do 0',
                        'name' => 'AZADA_STOCK_ZERO_ACTION',
                        'options' => [
                            'query' => [
                                ['id' => 0, 'name' => 'Wyłącz produkt (Niedostępny)'],
                                ['id' => 1, 'name' => 'Zostaw włączony (Pozwól zamawiać)'],
                                ['id' => 2, 'name' => 'Zmień tekst na "Na zamówienie"'],
                            ],
                            'id' => 'id', 'name' => 'name'
                        ]
                    ],
                    [
                        'type' => 'switch', 'label' => 'Zeruj stany brakujących produktów',
                        'name' => 'AZADA_STOCK_MISSING_ZERO',
                        'is_bool' => true,
                        'values' => [['id' => 'on', 'value' => 1, 'label' => 'Tak'], ['id' => 'off', 'value' => 0, 'label' => 'Nie']],
                    ],
                    [
                        'type' => 'switch', 'label' => 'Nie importuj nowych, gdy Ilość = 0',
                        'name' => 'AZADA_SKIP_NO_QTY', 'is_bool' => true,
                        'values' => [['id' => 'on', 'value' => 1, 'label' => 'Tak'], ['id' => 'off', 'value' => 0, 'label' => 'Nie']],
                    ]
                ],
                'submit' => ['title' => 'Zapisz', 'class' => 'btn btn-primary pull-right']
            ]
        ];
    }
}