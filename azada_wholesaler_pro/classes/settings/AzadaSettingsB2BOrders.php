<?php

class AzadaSettingsB2BOrders
{
    public static function getInputs()
    {
        return [
            [
                'type' => 'text',
                'label' => 'Dni wstecz do sprawdzania zamówień',
                'name' => 'AZADA_B2B_DAYS_RANGE',
                'class' => 'fixed-width-sm',
                'suffix' => 'dni',
                'desc' => 'Ile dni wstecz moduł ma sprawdzać historię zamówień? (Zalecane: 14 dni).',
                'required' => true
            ],
            [
                'type' => 'switch',
                'label' => 'Automatyczne pobieranie zamówień (CRON)',
                'name' => 'AZADA_B2B_AUTO_DOWNLOAD',
                'is_bool' => true,
                'values' => [['id' => 'on', 'value' => 1, 'label' => 'Tak'], ['id' => 'off', 'value' => 0, 'label' => 'Nie']],
                'desc' => 'Jeśli włączone, CRON automatycznie pobierze plik CSV (UTF-8) do bazy modułu.'
            ],
            [
                'type' => 'text',
                'label' => 'Usuwaj stare pliki zamówień po',
                'name' => 'AZADA_B2B_DELETE_DAYS',
                'class' => 'fixed-width-sm',
                'suffix' => 'dni',
                'desc' => 'Ile dni trzymać pliki zamówień na serwerze? (0 = bez limitu). Wartość musi być >= „Dni wstecz do sprawdzania zamówień” + 1.',
            ]
        ];
    }
}
