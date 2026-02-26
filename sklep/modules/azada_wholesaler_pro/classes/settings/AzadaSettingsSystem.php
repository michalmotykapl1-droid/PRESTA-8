<?php

/**
 * Ustawienia systemowe (osobny panel na samym dole konfiguracji).
 */
class AzadaSettingsSystem
{
    public static function getForm()
    {
        return [
            'form' => [
                'legend' => [
                    'title' => 'Ustawienia systemowe',
                    'icon' => 'icon-cogs',
                ],
                'input' => [
                    [
                        'type' => 'text',
                        'label' => 'Okres przechowywania logów',
                        'name' => 'AZADA_LOGS_RETENTION',
                        'class' => 'fixed-width-sm',
                        'suffix' => 'dni',
                        'desc' => 'Po ilu dniach automatycznie usuwać logi modułu (CRON/B2B/FV). Zalecane: 30–90 dni.',
                    ],
                ],
                'submit' => [
                    'title' => 'Zapisz',
                    'class' => 'btn btn-primary pull-right',
                ],
            ],
        ];
    }
}
