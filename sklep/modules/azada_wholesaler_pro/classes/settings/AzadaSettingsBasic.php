<?php

class AzadaSettingsBasic
{
    public static function getForm()
    {
        return [
            'form' => [
                'legend' => ['title' => 'Ustawienia podstawowe i tekstowe', 'icon' => 'icon-cogs'],
                'input' => [
                    ['type' => 'html', 'name' => 'hr_new', 'html_content' => '<h4>Tworzenie nowych produktów</h4>'],
                    [
                        'type' => 'switch', 'label' => 'Nowe produkty: Włączaj automatycznie',
                        'name' => 'AZADA_NEW_PROD_ACTIVE', 'is_bool' => true,
                        'values' => [['id' => 'on', 'value' => 1, 'label' => 'Tak'], ['id' => 'off', 'value' => 0, 'label' => 'Nie']]
                    ],
                    [
                        'type' => 'select', 'label' => 'Nowe produkty: Widoczność',
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

                    // --- ZMIANA: CZYSTSZE ETYKIETY ---
                    ['type' => 'html', 'name' => 'hr_upd', 'html_content' => '<hr><h4>Aktualizacja danych (Zaznacz co moduł ma nadpisywać)</h4>'],
                    
                    [
                        'type' => 'switch', 'label' => 'Nazwa produktu',
                        'name' => 'AZADA_UPD_NAME', 'is_bool' => true,
                        'values' => [['id' => 'on', 'value' => 1, 'label' => 'Tak'], ['id' => 'off', 'value' => 0, 'label' => 'Nie']]
                    ],
                    [
                        'type' => 'switch', 'label' => 'Długi opis',
                        'name' => 'AZADA_UPD_DESC_LONG', 'is_bool' => true,
                        'values' => [['id' => 'on', 'value' => 1, 'label' => 'Tak'], ['id' => 'off', 'value' => 0, 'label' => 'Nie']]
                    ],
                    [
                        'type' => 'switch', 'label' => 'Krótki opis',
                        'name' => 'AZADA_UPD_DESC_SHORT', 'is_bool' => true,
                        'values' => [['id' => 'on', 'value' => 1, 'label' => 'Tak'], ['id' => 'off', 'value' => 0, 'label' => 'Nie']]
                    ],
                    [
                        'type' => 'switch', 'label' => 'Meta SEO (Title, Url)',
                        'name' => 'AZADA_UPD_META', 'is_bool' => true,
                        'values' => [['id' => 'on', 'value' => 1, 'label' => 'Tak'], ['id' => 'off', 'value' => 0, 'label' => 'Nie']]
                    ],
                    [
                        'type' => 'switch', 'label' => 'Indeks (Reference)',
                        'name' => 'AZADA_UPD_REFERENCE', 'is_bool' => true,
                        'values' => [['id' => 'on', 'value' => 1, 'label' => 'Tak'], ['id' => 'off', 'value' => 0, 'label' => 'Nie']]
                    ],
                    [
                        'type' => 'switch', 'label' => 'Kod EAN / ISBN',
                        'name' => 'AZADA_UPD_EAN', 'is_bool' => true,
                        'values' => [['id' => 'on', 'value' => 1, 'label' => 'Tak'], ['id' => 'off', 'value' => 0, 'label' => 'Nie']]
                    ],
                    [
                        'type' => 'switch', 'label' => 'Cechy / Atrybuty',
                        'name' => 'AZADA_UPD_FEATURES', 'is_bool' => true,
                        'values' => [['id' => 'on', 'value' => 1, 'label' => 'Tak'], ['id' => 'off', 'value' => 0, 'label' => 'Nie']]
                    ],
                    [
                        'type' => 'switch', 'label' => 'Producent',
                        'name' => 'AZADA_UPD_MANUFACTURER', 'is_bool' => true,
                        'values' => [['id' => 'on', 'value' => 1, 'label' => 'Tak'], ['id' => 'off', 'value' => 0, 'label' => 'Nie']]
                    ],
                    [
                        'type' => 'switch', 'label' => 'Kategoria domyślna',
                        'name' => 'AZADA_UPD_CATEGORY', 'is_bool' => true,
                        'values' => [['id' => 'on', 'value' => 1, 'label' => 'Tak'], ['id' => 'off', 'value' => 0, 'label' => 'Nie']],
                        'desc' => 'Uwaga: Może przenieść produkt, jeśli zmieniłeś mu kategorię ręcznie.'
                    ],
                    [
                        'type' => 'switch', 'label' => 'Status (Aktywny/Nieaktywny)',
                        'name' => 'AZADA_UPD_ACTIVE', 'is_bool' => true,
                        'values' => [['id' => 'on', 'value' => 1, 'label' => 'Tak'], ['id' => 'off', 'value' => 0, 'label' => 'Nie']],
                        'desc' => 'Włącza produkt, jeśli hurtownia ponownie wyśle go w pliku.'
                    ],
                    
                    ['type' => 'html', 'name' => 'hr_del', 'html_content' => '<hr><h4>Usuwanie / Braki w pliku</h4>'],
                    [
                        'type' => 'switch', 'label' => 'Wyłącz produkty znikające z XML',
                        'name' => 'AZADA_DISABLE_MISSING_PROD', 'is_bool' => true,
                        'values' => [['id' => 'on', 'value' => 1, 'label' => 'Tak'], ['id' => 'off', 'value' => 0, 'label' => 'Nie']],
                        'desc' => 'Jeśli produkt zniknie z oferty hurtowni, zostanie wyłączony w sklepie.'
                    ],
                ],
                'submit' => ['title' => 'Zapisz', 'class' => 'btn btn-primary pull-right']
            ]
        ];
    }
}