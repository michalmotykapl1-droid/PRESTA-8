<?php

class AzadaSettingsImages
{
    public static function getForm()
    {
        return [
            'form' => [
                'legend' => ['title' => 'Ustawienia zdjęć', 'icon' => 'icon-camera'],
                'input' => [
                    [
                        'type' => 'switch', 'label' => 'Aktualizuj: Pobieraj nowe zdjęcia',
                        'name' => 'AZADA_UPD_IMAGES',
                        'is_bool' => true,
                        'values' => [['id' => 'on', 'value' => 1, 'label' => 'Tak'], ['id' => 'off', 'value' => 0, 'label' => 'Nie']],
                        'desc' => 'Dodaje zdjęcia, których jeszcze nie ma w sklepie.'
                    ],
                    [
                        'type' => 'switch', 'label' => 'Usuwaj zdjęcia usunięte w hurtowni',
                        'name' => 'AZADA_DELETE_OLD_IMAGES',
                        'is_bool' => true,
                        'values' => [['id' => 'on', 'value' => 1, 'label' => 'Tak'], ['id' => 'off', 'value' => 0, 'label' => 'Nie']],
                        'desc' => 'UWAGA: Jeśli hurtownia usunie zdjęcie z XML, moduł usunie je też ze sklepu.'
                    ],
                    [
                        'type' => 'switch', 'label' => 'Nie importuj nowych BEZ ZDJĘCIA',
                        'name' => 'AZADA_SKIP_NO_IMAGE', 'is_bool' => true,
                        'values' => [['id' => 'on', 'value' => 1, 'label' => 'Tak'], ['id' => 'off', 'value' => 0, 'label' => 'Nie']],
                    ],
                ],
                'submit' => ['title' => 'Zapisz', 'class' => 'btn btn-primary pull-right']
            ]
        ];
    }
}