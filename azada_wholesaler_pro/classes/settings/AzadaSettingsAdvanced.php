<?php

class AzadaSettingsAdvanced
{
    public static function getForm()
    {
        return [
            'form' => [
                'legend' => ['title' => 'Ustawienia zaawansowane', 'icon' => 'icon-wrench'],
                'input' => [
                    [
                        'type' => 'switch', 'label' => 'Wyczyść cache po imporcie',
                        'name' => 'AZADA_CLEAR_CACHE', // Placeholder
                        'is_bool' => true,
                        'values' => [['id' => 'on', 'value' => 1, 'label' => 'Tak'], ['id' => 'off', 'value' => 0, 'label' => 'Nie']],
                        'desc' => 'Opcja zalecana, jeśli zmiany nie są widoczne od razu na sklepie.'
                    ]
                ],
                'submit' => ['title' => 'Zapisz', 'class' => 'btn btn-primary pull-right']
            ]
        ];
    }
}