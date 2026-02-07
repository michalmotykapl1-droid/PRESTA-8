<?php

class AzadaSettingsB2BInvoices
{
    public static function getInputs()
    {
        return [
            [
                'type' => 'text',
                'label' => 'Zakres pobierania faktur (dni wstecz)',
                'name' => 'AZADA_FV_DAYS_RANGE',
                'class' => 'fixed-width-sm',
                'suffix' => 'dni',
                'desc' => 'Z ilu dni wstecz pobierać faktury? (Zalecane: 30 dni).',
                'required' => true
            ],
            [
                'type' => 'switch',
                'label' => 'Automatyczne pobieranie faktur (CRON)',
                'name' => 'AZADA_FV_AUTO_DOWNLOAD',
                'is_bool' => true,
                'values' => [['id' => 'on', 'value' => 1, 'label' => 'Tak'], ['id' => 'off', 'value' => 0, 'label' => 'Nie']],
                'desc' => 'Jeśli włączone, CRON automatycznie pobierze plik CSV (UTF-8) do bazy modułu.'
            ],
            [
                'type' => 'text',
                'label' => 'Usuwaj stare pliki faktur po',
                'name' => 'AZADA_FV_DELETE_DAYS',
                'class' => 'fixed-width-sm',
                'suffix' => 'dni',
                'desc' => 'Ile dni trzymać pliki CSV na serwerze? (0 = bez limitu).',
            ]
        ];
    }
}