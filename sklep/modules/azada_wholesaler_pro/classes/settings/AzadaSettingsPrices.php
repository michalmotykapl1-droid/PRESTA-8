<?php

class AzadaSettingsPrices
{
    public static function getForm()
    {
        return [
            'form' => [
                'legend' => ['title' => 'Ustawienia cen', 'icon' => 'icon-money'],
                'input' => [
                    [
                        'type' => 'switch', 'label' => 'Aktualizuj: Cenę Sprzedaży (Netto/Brutto)',
                        'name' => 'AZADA_UPD_PRICE',
                        'is_bool' => true,
                        'values' => [['id' => 'on', 'value' => 1, 'label' => 'Tak'], ['id' => 'off', 'value' => 0, 'label' => 'Nie']]
                    ],
                    [
                        'type' => 'switch', 'label' => 'Aktualizuj: Cenę Zakupu (Hurtową)',
                        'name' => 'AZADA_UPD_WHOLESALE_PRICE',
                        'is_bool' => true,
                        'values' => [['id' => 'on', 'value' => 1, 'label' => 'Tak'], ['id' => 'off', 'value' => 0, 'label' => 'Nie']],
                        'desc' => 'Tylko dla Twojej informacji w panelu (nie widoczne dla klienta).'
                    ],
                    [
                        'type' => 'switch', 'label' => 'Aktualizuj: Cenę Jednostkową (za litr/kg)',
                        'name' => 'AZADA_UPD_UNIT_PRICE',
                        'is_bool' => true,
                        'values' => [['id' => 'on', 'value' => 1, 'label' => 'Tak'], ['id' => 'off', 'value' => 0, 'label' => 'Nie']]
                    ],
                    [
                        'type' => 'switch', 'label' => 'Aktualizuj: Stawkę VAT',
                        'name' => 'AZADA_UPD_TAX',
                        'is_bool' => true,
                        'values' => [['id' => 'on', 'value' => 1, 'label' => 'Tak'], ['id' => 'off', 'value' => 0, 'label' => 'Nie']],
                        'desc' => 'Ustawia ID reguły podatkowej.'
                    ],
                    [
                        'type' => 'select', 'label' => 'Zaokrąglanie cen',
                        'name' => 'AZADA_PRICE_ROUNDING',
                        'options' => [
                            'query' => [
                                ['id' => 0, 'name' => 'Brak (dokładna cena)'],
                                ['id' => 1, 'name' => 'Do 99 groszy (np. 12.99)'],
                                ['id' => 2, 'name' => 'Do pełnych złotych (np. 13.00)'],
                            ],
                            'id' => 'id', 'name' => 'name'
                        ]
                    ],
                    [
                        'type' => 'switch', 'label' => 'Nie importuj nowych, gdy Cena = 0',
                        'name' => 'AZADA_SKIP_NO_PRICE', 'is_bool' => true,
                        'values' => [['id' => 'on', 'value' => 1, 'label' => 'Tak'], ['id' => 'off', 'value' => 0, 'label' => 'Nie']],
                    ],
                ],
                'submit' => ['title' => 'Zapisz', 'class' => 'btn btn-primary pull-right']
            ]
        ];
    }
}