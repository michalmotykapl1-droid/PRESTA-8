<?php

class AzadaSettingsFilters
{
    public static function getForm()
    {
        return [
            'form' => [
                'legend' => ['title' => '6. Filtry Importu (Czego NIE pobierać)', 'icon' => 'icon-filter'],
                'input' => [
                    [
                        'type' => 'switch', 'label' => 'Pomiń produkty BEZ ZDJĘCIA',
                        'name' => 'AZADA_SKIP_NO_IMAGE', 'is_bool' => true,
                        'values' => [['id' => 'on', 'value' => 1, 'label' => 'Tak'], ['id' => 'off', 'value' => 0, 'label' => 'Nie']],
                    ],
                    [
                        'type' => 'switch', 'label' => 'Pomiń produkty BEZ OPISU',
                        'name' => 'AZADA_SKIP_NO_DESC', 'is_bool' => true,
                        'values' => [['id' => 'on', 'value' => 1, 'label' => 'Tak'], ['id' => 'off', 'value' => 0, 'label' => 'Nie']],
                    ],
                    [
                        'type' => 'switch', 'label' => 'Pomiń produkty Z CENĄ 0',
                        'name' => 'AZADA_SKIP_NO_PRICE', 'is_bool' => true,
                        'values' => [['id' => 'on', 'value' => 1, 'label' => 'Tak'], ['id' => 'off', 'value' => 0, 'label' => 'Nie']],
                    ],
                    [
                        'type' => 'switch', 'label' => 'Pomiń produkty Z ILOŚCIĄ 0',
                        'name' => 'AZADA_SKIP_NO_QTY', 'is_bool' => true,
                        'values' => [['id' => 'on', 'value' => 1, 'label' => 'Tak'], ['id' => 'off', 'value' => 0, 'label' => 'Nie']],
                    ]
                ],
                'submit' => ['title' => 'Zapisz sekcję Filtrów', 'class' => 'btn btn-primary pull-right']
            ]
        ];
    }
}