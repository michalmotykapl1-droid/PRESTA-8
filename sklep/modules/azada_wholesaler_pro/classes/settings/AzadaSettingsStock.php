<?php

class AzadaSettingsStock
{
    public static function getForm()
    {
        return [
            'form' => [
                'legend' => ['title' => '4. Magazyn i Dostępność', 'icon' => 'icon-archive'],
                'input' => [
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
                        'type' => 'switch', 'label' => 'Zeruj stany brakujących',
                        'name' => 'AZADA_STOCK_MISSING_ZERO',
                        'is_bool' => true,
                        'values' => [['id' => 'on', 'value' => 1, 'label' => 'Tak'], ['id' => 'off', 'value' => 0, 'label' => 'Nie']]
                    ]
                ],
                'submit' => ['title' => 'Zapisz sekcję Magazyn', 'class' => 'btn btn-primary pull-right']
            ]
        ];
    }
}