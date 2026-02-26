<?php
/**
 * Moduł do sprawdzania czy produkt należy do Strefy FRESH (i podkategorii)
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class FreshLabel extends Module
{
    public function __construct()
    {
        $this->name = 'freshlabel';
        $this->tab = 'front_office_features';
        $this->version = '1.0.2'; // Nowa wersja
        $this->author = 'Twój Sklep';
        $this->need_instance = 0;
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Etykieta Strefa Fresh');
        $this->description = $this->l('Dodaje funkcję sprawdzania produktów ze Strefy Fresh.');
    }

    public function install()
    {
        return parent::install();
    }

    public function uninstall()
    {
        return parent::uninstall();
    }

    /**
     * Sprawdza czy produkt należy do drzewa kategorii Strefa FRESH (ID 270184)
     * Metoda "na dwa kroki" - bezpieczna dla SQL
     */
    public function isFresh($id_product)
    {
        // ID kategorii głównej "Strefa FRESH"
        $fresh_parent_id = 270184;

        // KROK 1: Pobieramy zakres (drzewo) kategorii Fresh
        // To proste zapytanie, które nie generuje błędów
        $range = Db::getInstance()->getRow('
            SELECT nleft, nright 
            FROM `'._DB_PREFIX_.'category` 
            WHERE `id_category` = '.(int)$fresh_parent_id
        );

        // Jeśli kategoria Fresh nie istnieje lub jest wyłączona, przerwij
        if (!$range) {
            return false;
        }

        // KROK 2: Sprawdzamy czy produkt ma jakąkolwiek kategorię w tym zakresie
        // Proste sprawdzenie liczb, bez skomplikowanych JOINów
        $sql = 'SELECT 1 
                FROM `'._DB_PREFIX_.'category_product` cp
                INNER JOIN `'._DB_PREFIX_.'category` c ON (c.id_category = cp.id_category)
                WHERE cp.`id_product` = '.(int)$id_product.'
                AND c.`nleft` >= '.(int)$range['nleft'].'
                AND c.`nright` <= '.(int)$range['nright'];

        // Używamy getValue, które samo pobiera pierwszy wynik (nie potrzebujemy LIMIT 1 ręcznie)
        return (bool)Db::getInstance()->getValue($sql);
    }
}