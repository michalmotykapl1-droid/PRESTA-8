<?php

require_once(dirname(__FILE__) . '/../../classes/AzadaRawData.php');

class AdminAzadaProductListController extends ModuleAdminController
{
    public function __construct()
    {
        $this->bootstrap = true;
        $this->context = Context::getContext();

        // Domyślna tabela źródłowa dla poczekalni
        $this->table = 'azada_raw_bioplanet';
        $this->identifier = 'id_raw';
        $this->className = 'AzadaRawData';
        $this->list_no_link = true;

        parent::__construct();

        $this->buildReadableList();
    }

    /**
     * Budowa czytelnej listy: pokazujemy tylko najważniejsze kolumny
     * w stałej kolejności. Jeśli tabela nie istnieje -> pusta lista.
     */
    private function buildReadableList()
    {
        $db = Db::getInstance();
        $fullTableName = _DB_PREFIX_ . $this->table;

        try {
            $columns = $db->executeS("SHOW COLUMNS FROM `$fullTableName`");
        } catch (Exception $e) {
            return;
        }

        if (empty($columns)) {
            return;
        }

        $existing = [];
        foreach ($columns as $col) {
            if (!empty($col['Field'])) {
                $existing[$col['Field']] = true;
            }
        }

        $preferredOrder = [
            'zdjecieglownelinkurl',
            'nazwa',
            'kod_kreskowy',
            'produkt_id',
            'marka',
            'kategoria',
            'jednostkapodstawowa',
            'ilosc',
            'NaStanie',
            'cenaporabacienetto',
            'vat',
            'LinkDoProduktu',
            'data_aktualizacji',
        ];

        $this->fields_list = [];

        foreach ($preferredOrder as $field) {
            if (!isset($existing[$field])) {
                continue;
            }

            $params = $this->getReadableFieldParams($field);
            $this->fields_list[$field] = $params;
        }

        // Fallback: jeżeli żadna preferowana kolumna nie istnieje,
        // pokazujemy dynamicznie wszystkie (jak dawniej).
        if (empty($this->fields_list)) {
            foreach ($columns as $col) {
                $field = $col['Field'];
                if ($field === 'id_raw') {
                    continue;
                }

                $params = [
                    'title' => $this->humanize($field),
                    'align' => 'center',
                    'havingFilter' => true,
                ];

                if (strpos($field, 'zdjecie') !== false || strpos($field, 'foto') !== false) {
                    $params['title'] = 'FOTO';
                    $params['callback'] = 'displayImageThumb';
                    $params['search'] = false;
                    $params['havingFilter'] = false;
                    $params['width'] = 70;
                } elseif ($field === 'opis' || strpos($field, 'nazwa') !== false) {
                    $params['callback'] = 'displayShortText';
                    $params['width'] = 250;
                } elseif (strpos($field, 'cena') !== false || strpos($field, 'netto') !== false || strpos($field, 'brutto') !== false || strpos($field, 'vat') !== false) {
                    $params['callback'] = 'displayRawNumber';
                    $params['align'] = 'right';
                    $params['width'] = 80;
                } elseif (strpos($field, 'stan') !== false || strpos($field, 'ilosc') !== false || strpos($field, 'minimum') !== false) {
                    $params['type'] = 'int';
                    $params['callback'] = 'displayStockColor';
                    $params['width'] = 60;
                }

                $this->fields_list[$field] = $params;
            }
        }
    }

    private function getReadableFieldParams($field)
    {
        $base = [
            'title' => $this->humanize($field),
            'align' => 'center',
            'havingFilter' => true,
        ];

        if ($field === 'zdjecieglownelinkurl') {
            return [
                'title' => 'FOTO',
                'align' => 'center',
                'callback' => 'displayImageThumb',
                'search' => false,
                'havingFilter' => false,
                'width' => 70,
            ];
        }

        if ($field === 'nazwa') {
            return [
                'title' => 'Nazwa',
                'align' => 'left',
                'callback' => 'displayShortText',
                'width' => 260,
                'havingFilter' => true,
            ];
        }

        if ($field === 'kategoria') {
            return [
                'title' => 'Kategoria',
                'align' => 'left',
                'callback' => 'displayShortText',
                'width' => 170,
                'havingFilter' => true,
            ];
        }

        if ($field === 'kod_kreskowy') {
            $base['title'] = 'EAN';
            $base['style'] = 'font-weight:bold;';
            $base['width'] = 130;
            return $base;
        }

        if ($field === 'produkt_id') {
            $base['title'] = 'SKU';
            $base['style'] = 'font-weight:bold;';
            $base['width'] = 110;
            return $base;
        }
        if ($field === 'marka') {
            $base['title'] = 'Marka';
            $base['width'] = 120;
            return $base;
        }

        if ($field === 'jednostkapodstawowa') {
            $base['title'] = 'Jedn.';
            $base['width'] = 65;
            return $base;
        }

        if ($field === 'ilosc') {
            return [
                'title' => 'Ilość',
                'align' => 'center',
                'type' => 'int',
                'callback' => 'displayStockColor',
                'width' => 75,
                'havingFilter' => true,
            ];
        }

        if ($field === 'NaStanie') {
            return [
                'title' => 'Na stanie',
                'align' => 'center',
                'callback' => 'displayAvailability',
                'width' => 85,
                'havingFilter' => true,
            ];
        }

        if ($field === 'cenaporabacienetto') {
            return [
                'title' => 'Cena netto',
                'align' => 'right',
                'callback' => 'displayRawNumber',
                'width' => 90,
                'havingFilter' => true,
            ];
        }

        if ($field === 'vat') {
            return [
                'title' => 'VAT',
                'align' => 'right',
                'callback' => 'displayRawNumber',
                'width' => 55,
                'havingFilter' => true,
            ];
        }

        if ($field === 'LinkDoProduktu') {
            return [
                'title' => 'Link',
                'align' => 'center',
                'callback' => 'displayProductLink',
                'search' => false,
                'havingFilter' => false,
                'width' => 75,
            ];
        }

        if ($field === 'data_aktualizacji') {
            return [
                'title' => 'Aktualizacja',
                'align' => 'center',
                'type' => 'datetime',
                'width' => 145,
                'havingFilter' => true,
            ];
        }

        return $base;
    }

    public function displayRawNumber($value, $row)
    {
        if ($value === '' || $value === null) {
            return '0.00';
        }

        return number_format((float)str_replace(',', '.', $value), 2, '.', '');
    }

    public function displayImageThumb($url, $row)
    {
        if (empty($url)) {
            return '<span class="text-muted">-</span>';
        }

        return '<a href="'.$url.'" target="_blank" class="btn btn-default btn-sm"><img src="'.$url.'" style="max-height:50px; max-width:50px; object-fit:contain;" /></a>';
    }

    public function displayStockColor($value, $row)
    {
        $val = (int)$value;
        if ($val > 20) return '<span class="badge badge-success" style="background:#2ecc71; color:white;">'.$val.'</span>';
        if ($val > 0) return '<span class="badge badge-warning" style="background:#f39c12; color:white;">'.$val.'</span>';
        return '<span class="badge badge-danger" style="background:#e74c3c; color:white;">0</span>';
    }

    public function displayAvailability($value, $row)
    {
        $v = strtolower(trim((string)$value));
        $isAvailable = in_array($v, ['1', 'true', 'tak', 'yes'], true);

        if ($isAvailable) {
            return '<span class="badge badge-success" style="background:#2ecc71; color:white;">True</span>';
        }

        return '<span class="badge badge-danger" style="background:#e74c3c; color:white;">False</span>';
    }

    public function displayProductLink($url, $row)
    {
        $url = trim((string)$url);
        if ($url === '') {
            return '<span class="text-muted">-</span>';
        }

        return '<a href="'.$url.'" target="_blank" class="btn btn-default btn-xs">Otwórz</a>';
    }

    public function displayShortText($text, $row)
    {
        $text = html_entity_decode((string)$text, ENT_QUOTES, 'UTF-8');
        $clean = strip_tags($text);

        if (mb_strlen($clean, 'UTF-8') > 100) {
            $short = mb_substr($clean, 0, 100, 'UTF-8');
            return '<span title="'.htmlspecialchars($clean).'">'.$short.'...</span>';
        }

        return $clean;
    }

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
