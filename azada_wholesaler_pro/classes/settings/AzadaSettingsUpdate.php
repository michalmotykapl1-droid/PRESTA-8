<?php

class AzadaSettingsUpdate
{
    public static function getForm()
    {
        return [
            'form' => [
                'legend' => ['title' => '3. Strategia: Aktualizacja (Istniejące)', 'icon' => 'icon-refresh'],
                'input' => [
                    [
                        'type' => 'checkbox', 'label' => 'Zaznacz co aktualizować',
                        'desc' => 'Zaznaczone pola będą nadpisywane przez hurtownię.',
                        'name' => 'AZADA_UPDATE_FIELDS',
                        'values' => [
                            'query' => [
                                ['id' => 'name', 'name' => 'Nazwa produktu', 'val' => 1],
                                ['id' => 'desc', 'name' => 'Opisy (Długi i Krótki)', 'val' => 1],
                                ['id' => 'meta', 'name' => 'SEO (Meta Title, Desc, URL)', 'val' => 1],
                                ['id' => 'ean', 'name' => 'Kody (EAN, ISBN, UPC)', 'val' => 1],
                                ['id' => 'stock', 'name' => 'Stany magazynowe', 'val' => 1],
                                ['id' => 'features', 'name' => 'Cechy / Atrybuty', 'val' => 1],
                                ['id' => 'dimensions', 'name' => 'Waga i Wymiary', 'val' => 1],
                                ['id' => 'images', 'name' => 'Zdjęcia (Dodawanie nowych)', 'val' => 1],
                            ],
                            'id' => 'id', 'name' => 'name'
                        ]
                    ]
                ],
                'submit' => ['title' => 'Zapisz sekcję Aktualizacji', 'class' => 'btn btn-primary pull-right']
            ]
        ];
    }
}