<?php

class AzadaSettingsNew
{
    public static function getForm()
    {
        return [
            'form' => [
                'legend' => ['title' => '2. Strategia: Nowe Produkty', 'icon' => 'icon-plus-circle'],
                'input' => [
                    [
                        'type' => 'switch', 'label' => 'Czy włączać nowe produkty?',
                        'name' => 'AZADA_NEW_PROD_ACTIVE', 'is_bool' => true,
                        'values' => [['id' => 'on', 'value' => 1, 'label' => 'Tak'], ['id' => 'off', 'value' => 0, 'label' => 'Nie']]
                    ],
                    [
                        'type' => 'select', 'label' => 'Domyślna widoczność',
                        'name' => 'AZADA_NEW_PROD_VISIBILITY',
                        'options' => [
                            'query' => [
                                ['id' => 'both', 'name' => 'Katalog i Szukanie'],
                                ['id' => 'catalog', 'name' => 'Tylko Katalog'],
                                ['id' => 'search', 'name' => 'Tylko Szukanie'],
                                ['id' => 'none', 'name' => 'Ukryty']
                            ],
                            'id' => 'id', 'name' => 'name'
                        ]
                    ],
                    [
                        'type' => 'html', 'label' => 'Kategoria domyślna',
                        'name' => 'dummy_cat',
                        'html_content' => '<div class="alert alert-warning" style="margin-top:7px">Drzewo kategorii w przygotowaniu.</div>'
                    ]
                ],
                'submit' => ['title' => 'Zapisz sekcję Nowe Produkty', 'class' => 'btn btn-primary pull-right']
            ]
        ];
    }
}