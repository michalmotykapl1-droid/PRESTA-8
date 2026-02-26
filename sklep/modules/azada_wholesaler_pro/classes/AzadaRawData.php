<?php

class AzadaRawData extends ObjectModel
{
    public $id_raw;
    
    // Pola dynamiczne nie muszą być tu deklarowane jako public $zmienna,
    // ponieważ używamy ich tylko do wyświetlania listy, a nie do edycji obiektu.

    public static $definition = [
        'table' => 'azada_raw_bioplanet',
        'primary' => 'id_raw',
        'fields' => [
            // Definicja jest pusta, ponieważ budujemy listę dynamicznie w kontrolerze.
            // Ta klasa służy tylko temu, żeby Presta wiedziała jak nazywa się tabela i klucz główny.
        ],
    ];
}