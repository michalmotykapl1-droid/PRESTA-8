<?php

// --- TU BYŁ BŁĄD: BRAKOWAŁO ZAŁĄCZENIA KLASY ---
require_once(dirname(__FILE__) . '/../../classes/AzadaRawData.php');
// ------------------------------------------------

class AdminAzadaProductListController extends ModuleAdminController
{
    public function __construct()
    {
        $this->bootstrap = true;
        $this->context = Context::getContext();
        
        // Konfiguracja pod naszą tabelę surową
        $this->table = 'azada_raw_bioplanet'; 
        $this->identifier = 'id_raw'; 
        $this->className = 'AzadaRawData'; // Teraz ta klasa już istnieje (plik wyżej)
        $this->list_no_link = true; 
        
        parent::__construct();

        // Budujemy kolumny dynamicznie
        $this->buildDynamicList();
    }

    /**
     * Budowa listy na podstawie kolumn w bazie danych
     */
    public function buildDynamicList()
    {
        $db = Db::getInstance();
        $fullTableName = _DB_PREFIX_ . $this->table;
        
        try {
            $columns = $db->executeS("SHOW COLUMNS FROM `$fullTableName`");
        } catch (Exception $e) {
            // Jeśli tabela nie istnieje, nie robimy nic (pusta lista)
            return;
        }

        if (empty($columns)) return;

        $this->fields_list = [];

        foreach ($columns as $col) {
            $field = $col['Field'];
            
            // Pomijamy ID techniczne
            if ($field == 'id_raw') continue;

            // Domyślne ustawienia kolumny
            $params = [
                'title' => $this->humanize($field),
                'align' => 'center',
                'havingFilter' => true, 
            ];

            // --- FORMATOWANIE ---

            // 1. ZDJĘCIA
            if (strpos($field, 'zdjecie') !== false || strpos($field, 'foto') !== false) {
                $params['title'] = 'FOTO';
                $params['callback'] = 'displayImageThumb';
                $params['search'] = false; 
                $params['width'] = 70;
            }
            // 2. ID PRODUKTU (BP_)
            elseif ($field == 'produkt_id' || $field == 'kod_kreskowy') {
                $params['width'] = 120;
                $params['style'] = 'font-weight:bold';
            }
            // 3. LICZBY (Ceny, Wagi, Wymiary - BEZ FORMATOWANIA WALUTOWEGO)
            // Zmieniono logikę: zamiast type='price', używamy callbacka displayRawNumber
            elseif (strpos($field, 'cena') !== false || strpos($field, 'netto') !== false || strpos($field, 'brutto') !== false || strpos($field, 'vat') !== false || strpos($field, 'waga') !== false || strpos($field, 'szerokosc') !== false || strpos($field, 'wysokosc') !== false || strpos($field, 'glebokosc') !== false) {
                $params['callback'] = 'displayRawNumber'; // Wyświetl czystą liczbę
                $params['align'] = 'right';
                $params['width'] = 80;
            }
            // 4. STANY MAGAZYNOWE (Liczby całkowite)
            elseif (strpos($field, 'stan') !== false || strpos($field, 'ilosc') !== false || strpos($field, 'minimum') !== false) {
                $params['type'] = 'int';
                $params['callback'] = 'displayStockColor'; 
                $params['width'] = 60;
            }
            // 5. OPISY I NAZWY (Poprawa polskich znaków)
            elseif ($field == 'opis' || strpos($field, 'nazwa') !== false) {
                $params['callback'] = 'displayShortText';
                $params['width'] = 250;
                $params['search'] = true; 
            }
            // 6. DATY
            elseif (strpos($field, 'data') !== false) {
                $params['type'] = 'datetime';
                $params['width'] = 150;
            }

            $this->fields_list[$field] = $params;
        }
    }

    /**
     * NOWA FUNKCJA: Wyświetla czystą liczbę (np. 31.69) bez "zł" i błędów
     */
    public function displayRawNumber($value, $row)
    {
        // Jeśli wartość jest pusta lub null, pokaż 0.00
        if ($value === '' || $value === null) return '0.00';
        
        // Upewniamy się, że to float i formatujemy z kropką (bezpieczne dla HTML)
        // number_format(liczba, miejsca_po_przecinku, separator_dziesiętny, separator_tysięcy)
        return number_format((float)str_replace(',', '.', $value), 2, '.', '');
    }

    /**
     * Miniaturka zdjęcia
     */
    public function displayImageThumb($url, $row)
    {
        if (empty($url)) return '<span class="text-muted">-</span>';
        return '<a href="'.$url.'" target="_blank" class="btn btn-default btn-sm"><img src="'.$url.'" style="max-height:50px; max-width:50px; object-fit:contain;" /></a>';
    }

    /**
     * Kolorowanie stanów
     */
    public function displayStockColor($value, $row)
    {
        $val = (int)$value;
        if ($val > 20) return '<span class="badge badge-success" style="background:#2ecc71; color:white;">'.$val.'</span>';
        if ($val > 0) return '<span class="badge badge-warning" style="background:#f39c12; color:white;">'.$val.'</span>';
        return '<span class="badge badge-danger" style="background:#e74c3c; color:white;">0</span>';
    }

    /**
     * Skracanie tekstu (Bezpieczne dla UTF-8 / Polskich znaków)
     */
    public function displayShortText($text, $row)
    {
        // Dekoduj encje HTML
        $text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');
        // Usuń HTML
        $clean = strip_tags($text);
        
        // Używamy mb_strlen i mb_substr dla poprawnej obsługi polskich liter
        if (mb_strlen($clean, 'UTF-8') > 100) {
            $short = mb_substr($clean, 0, 100, 'UTF-8');
            return '<span title="'.htmlspecialchars($clean).'">'.$short.'...</span>';
        }
        
        return $clean;
    }

    /**
     * Ładne nazwy kolumn (z underscore na tekst)
     */
    private function humanize($str)
    {
        return ucwords(str_replace('_', ' ', $str));
    }

    public function initToolbar()
    {
        $this->toolbar_btn['import'] = [
            'href' => $this->context->link->getAdminLink('AdminAzadaWholesaler'),
            'desc' => $this->l('Wróć do Importu'),
            'icon' => 'process-icon-back'
        ];
    }
}