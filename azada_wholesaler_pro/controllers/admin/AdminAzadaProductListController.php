<?php

require_once(dirname(__FILE__) . '/../../classes/AzadaRawData.php');

class AdminAzadaProductListController extends ModuleAdminController
{
    private $availableRawTables = [];
    private $selectedWholesalers = [];
    private $globalSearchQuery = '';
    private $onlyMinimalQty = false;
    private $onlyInStock = false;
    private $productCreatedFilter = 'all';
    private $hasProductOriginTable = false;

    public function __construct()
    {
        $this->bootstrap = true;
        $this->context = Context::getContext();

        $this->availableRawTables = $this->getAvailableRawTables();
        $this->selectedWholesalers = $this->normalizeSelectedWholesalers(Tools::getValue('azada_wholesalers', []));
        $this->globalSearchQuery = trim((string)Tools::getValue('azada_q', ''));
        $this->onlyMinimalQty = (Tools::getValue('azada_only_min_qty', '0') === '1');
        $this->onlyInStock = (Tools::getValue('azada_only_in_stock', '0') === '1');
        $this->productCreatedFilter = $this->normalizeCreatedFilter(Tools::getValue('azada_product_created', 'all'));

        $this->buildGlobalSearchIndexTable();
        $this->hasProductOriginTable = $this->tableExists(_DB_PREFIX_ . 'azada_wholesaler_pro_product_origin');

        // Źródłem listy jest indeks z wybranych hurtowni (lub wszystkich)
        $this->table = 'azada_raw_search_index';
        $this->identifier = 'id_raw';
        $this->className = 'AzadaRawData';
        $this->list_no_link = true;

        parent::__construct();

        $this->buildReadableList();
    }

    private function normalizeSelectedWholesalers($raw)
    {
        $selected = [];
        if (!is_array($raw)) {
            $raw = [$raw];
        }

        foreach ($raw as $table) {
            $table = trim((string)$table);
            if ($table === '') {
                continue;
            }

            if (isset($this->availableRawTables[$table])) {
                $selected[$table] = $table;
            }
        }

        return array_values($selected);
    }

    private function getAvailableRawTables()
    {
        $tables = [];
        $db = Db::getInstance();
        $prefix = pSQL(_DB_PREFIX_);
        $rows = $db->executeS("SHOW TABLES LIKE '".$prefix."azada_raw_%'");

        if (empty($rows)) {
            return $tables;
        }

        foreach ($rows as $row) {
            $fullName = (string)reset($row);
            $table = preg_replace('/^'.preg_quote(_DB_PREFIX_, '/').'/', '', $fullName);

            if ($table === 'azada_raw_search_index') {
                continue;
            }

            if (preg_match('/_(source|conversion)$/', $table)) {
                continue;
            }

            $tables[$table] = $table;
        }

        ksort($tables);
        return $tables;
    }

    private function getTablesForIndex()
    {
        // Wyszukiwarka globalna ma być niezależna od filtrów hurtowni.
        if ($this->globalSearchQuery !== '') {
            return array_values($this->availableRawTables);
        }

        if (!empty($this->selectedWholesalers)) {
            return $this->selectedWholesalers;
        }

        return array_values($this->availableRawTables);
    }

    private function tableExists($fullTableName)
    {
        return (bool)Db::getInstance()->getValue(
            "SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME='" . pSQL($fullTableName) . "'"
        );
    }

    private function normalizeCreatedFilter($raw)
    {
        $value = strtolower(trim((string)$raw));
        if (!in_array($value, ['all', 'created', 'created_module', 'created_other', 'missing'], true)) {
            return 'all';
        }

        return $value;
    }

    private function buildGlobalSearchIndexTable()
    {
        $db = Db::getInstance();
        $target = _DB_PREFIX_ . 'azada_raw_search_index';

        $db->execute("CREATE TABLE IF NOT EXISTS `$target` (
            `id_raw` INT(11) NOT NULL AUTO_INCREMENT,
            `source_table` VARCHAR(64) NULL,
            `zdjecieglownelinkurl` TEXT NULL,
            `nazwa` TEXT NULL,
            `kod_kreskowy` TEXT NULL,
            `produkt_id` TEXT NULL,
            `marka` TEXT NULL,
            `kategoria` TEXT NULL,
            `jednostkapodstawowa` TEXT NULL,
            `ilosc` TEXT NULL,
            `wymagane_oz` TEXT NULL,
            `ilosc_w_opakowaniu` TEXT NULL,
            `NaStanie` TEXT NULL,
            `cenaporabacienetto` TEXT NULL,
            `vat` TEXT NULL,
            `LinkDoProduktu` TEXT NULL,
            `data_aktualizacji` DATETIME NULL,
            PRIMARY KEY (`id_raw`)
        ) ENGINE="._MYSQL_ENGINE_." DEFAULT CHARSET=utf8");

        $db->execute("TRUNCATE TABLE `$target`");

        $tablesToIndex = $this->getTablesForIndex();
        if (empty($tablesToIndex)) {
            return;
        }

        $columnsToCopy = [
            'zdjecieglownelinkurl', 'nazwa', 'kod_kreskowy', 'produkt_id', 'marka', 'kategoria',
            'jednostkapodstawowa', 'ilosc', 'wymagane_oz', 'ilosc_w_opakowaniu', 'NaStanie',
            'cenaporabacienetto', 'vat', 'LinkDoProduktu', 'data_aktualizacji',
        ];

        foreach ($tablesToIndex as $table) {
            $full = _DB_PREFIX_ . $table;
            $cols = $db->executeS("SHOW COLUMNS FROM `$full`");
            if (empty($cols)) {
                continue;
            }

            $exists = [];
            foreach ($cols as $c) {
                $exists[$c['Field']] = true;
            }

            $selectParts = ["'".pSQL($table)."' AS source_table"];
            foreach ($columnsToCopy as $col) {
                if (isset($exists[$col])) {
                    $selectParts[] = "`$col`";
                } else {
                    if ($col === 'data_aktualizacji') {
                        $selectParts[] = 'NULL AS `data_aktualizacji`';
                    } else {
                        $selectParts[] = "'' AS `$col`";
                    }
                }
            }

            $db->execute("INSERT INTO `$target` (`source_table`, `".implode('`, `', $columnsToCopy)."`) SELECT ".implode(', ', $selectParts)." FROM `$full`");
        }
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

        $this->applyListFilters($existing);
        $this->prepareRequiredOzSupport($existing);
        $this->prepareProductCreationSupport($existing);
        $this->applyGlobalSearchFilter($existing);

        $preferredOrder = [
            'source_table',
            'zdjecieglownelinkurl',
            'nazwa',
            'kod_kreskowy',
            'produkt_id',
            'azada_ps_created',
            'marka',
            'kategoria',
            'jednostkapodstawowa',
            'ilosc',
            'wymagane_oz',
            'NaStanie',
            'cenaporabacienetto',
            'vat',
            'LinkDoProduktu',
            'azada_manual_create',
            'data_aktualizacji',
        ];

        $this->fields_list = [];
        $virtualFields = [
            'azada_ps_created' => true,
            'azada_manual_create' => true,
        ];

        foreach ($preferredOrder as $field) {
            if (!isset($existing[$field]) && !isset($virtualFields[$field])) {
                continue;
            }

            $params = $this->getReadableFieldParams($field);
            $this->fields_list[$field] = $params;
        }
    }

    private function getReadableFieldParams($field)
    {
        $base = [
            'title' => $this->humanize($field),
            'align' => 'center',
            'havingFilter' => true,
        ];

        if ($field === 'source_table') {
            return [
                'title' => 'Hurtownia',
                'align' => 'center',
                'width' => 130,
                'callback' => 'displayWholesalerName',
                'havingFilter' => true,
            ];
        }
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

        if ($field === 'azada_ps_created') {
            return [
                'title' => 'W Presta',
                'align' => 'center',
                'callback' => 'displayCreatedStatus',
                'width' => 95,
                'havingFilter' => true,
            ];
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

        if ($field === 'wymagane_oz') {
            return [
                'title' => 'Wymagane OŻ',
                'align' => 'center',
                'callback' => 'displayRequiredOzInfo',
                'width' => 120,
                'havingFilter' => false,
                'search' => false,
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

        if ($field === 'azada_manual_create') {
            return [
                'title' => 'Akcja',
                'align' => 'center',
                'callback' => 'displayManualCreateAction',
                'search' => false,
                'havingFilter' => false,
                'width' => 145,
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

    private function prepareRequiredOzSupport(array $existing)
    {
        if (!isset($existing['wymagane_oz']) || !isset($existing['ilosc_w_opakowaniu'])) {
            return;
        }

        if (trim((string)$this->_select) === '') {
            $this->_select = 'a.`ilosc_w_opakowaniu`';
            return;
        }

        $this->_select .= ', a.`ilosc_w_opakowaniu`';
    }

    private function prepareProductCreationSupport(array $existing)
    {
        if (!isset($existing['kod_kreskowy']) && !isset($existing['produkt_id'])) {
            return;
        }

        $joinSql = $this->buildProductMatchJoinSql();
        if (trim((string)$this->_join) === '') {
            $this->_join = $joinSql;
        } else {
            $this->_join .= ' ' . $joinSql;
        }

        $idSql = 'COALESCE(pe.`id_product`, pr.`id_product`)';
        $createdSql = '(CASE WHEN ' . $idSql . ' IS NOT NULL THEN 1 ELSE 0 END)';

        $createdByModuleSql = '0';
        if ($this->hasProductOriginTable) {
            $originJoin = "LEFT JOIN `" . bqSQL(_DB_PREFIX_ . 'azada_wholesaler_pro_product_origin') . "` apo ON (apo.`id_product` = " . $idSql . ")";
            $this->_join .= ' ' . $originJoin;
            $createdByModuleSql = 'IFNULL(apo.`created_by_module`, 0)';
        }

        if (trim((string)$this->_select) === '') {
            $this->_select = "$createdSql AS `azada_ps_created`, $idSql AS `azada_ps_id_product`, $createdByModuleSql AS `azada_ps_created_module`, '' AS `azada_manual_create`";
            return;
        }

        $this->_select .= ", $createdSql AS `azada_ps_created`, $idSql AS `azada_ps_id_product`, $createdByModuleSql AS `azada_ps_created_module`, '' AS `azada_manual_create`";
    }

    private function buildProductMatchJoinSql()
    {
        $productTable = _DB_PREFIX_ . 'product';
        $productTableEscaped = bqSQL($productTable);

        return "LEFT JOIN ("
            . "SELECT p.`ean13`, MIN(p.`id_product`) AS `id_product` "
            . "FROM `{$productTableEscaped}` p "
            . "WHERE TRIM(IFNULL(p.`ean13`, '')) <> '' "
            . "GROUP BY p.`ean13`"
            . ") pe ON pe.`ean13` = a.`kod_kreskowy` "
            . "LEFT JOIN ("
            . "SELECT p.`reference`, MIN(p.`id_product`) AS `id_product` "
            . "FROM `{$productTableEscaped}` p "
            . "WHERE TRIM(IFNULL(p.`reference`, '')) <> '' "
            . "GROUP BY p.`reference`"
            . ") pr ON pr.`reference` = a.`produkt_id`";
    }

    private function applyListFilters(array $existing)
    {
        $conditions = [];

        if (isset($existing['vat'])) {
            $conditions[] = "CAST(REPLACE(REPLACE(TRIM(vat), '%', ''), ',', '.') AS DECIMAL(10,2)) IN (5, 8, 23)";
        }

        if ($this->onlyMinimalQty && isset($existing['wymagane_oz'])) {
            $conditions[] = "LOWER(REPLACE(TRIM(wymagane_oz), ' ', '')) IN ('true', 'min')";

            if (isset($existing['ilosc_w_opakowaniu'])) {
                $conditions[] = "CAST(REPLACE(TRIM(ilosc_w_opakowaniu), ',', '.') AS DECIMAL(10,2)) > 0";
            }
        }

        if ($this->onlyInStock && isset($existing['NaStanie'])) {
            $conditions[] = "LOWER(REPLACE(TRIM(NaStanie), ' ', '')) = 'true'";
        }

        if ($this->productCreatedFilter === 'created') {
            $conditions[] = '(pe.`id_product` IS NOT NULL OR pr.`id_product` IS NOT NULL)';
        } elseif ($this->productCreatedFilter === 'created_module') {
            $conditions[] = '(pe.`id_product` IS NOT NULL OR pr.`id_product` IS NOT NULL)';
            $conditions[] = ($this->hasProductOriginTable ? 'IFNULL(apo.`created_by_module`, 0) = 1' : '1 = 0');
        } elseif ($this->productCreatedFilter === 'created_other') {
            $conditions[] = '(pe.`id_product` IS NOT NULL OR pr.`id_product` IS NOT NULL)';
            $conditions[] = ($this->hasProductOriginTable ? 'IFNULL(apo.`created_by_module`, 0) = 0' : '1 = 1');
        } elseif ($this->productCreatedFilter === 'missing') {
            $conditions[] = '(pe.`id_product` IS NULL AND pr.`id_product` IS NULL)';
        }

        if (!empty($conditions)) {
            $this->_where .= ' AND ' . implode(' AND ', $conditions);
        }
    }

    private function applyGlobalSearchFilter(array $existing)
    {
        if ($this->globalSearchQuery === '') {
            return;
        }

        $query = pSQL($this->globalSearchQuery);
        $searchable = ['nazwa', 'kod_kreskowy', 'produkt_id', 'marka', 'kategoria', 'LinkDoProduktu'];
        $conditions = [];

        foreach ($searchable as $col) {
            if (isset($existing[$col])) {
                $conditions[] = "`$col` LIKE '%$query%'";
            }
        }

        if (!empty($conditions)) {
            $this->_where .= ' AND (' . implode(' OR ', $conditions) . ')';
        }
    }
    public function displayWholesalerName($value, $row)
    {
        return $this->getWholesalerDisplayName((string)$value);
    }

    private function getWholesalerDisplayName($table)
    {
        $name = str_replace('azada_raw_', '', trim((string)$table));
        if ($name === '') {
            return '-';
        }

        return ucfirst($name);
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

    public function displayRequiredOzInfo($value, $row)
    {
        $requiredRaw = trim((string)$value);
        $requiredNormalized = preg_replace('/\s+/', '', $requiredRaw);
        $requiredLower = strtolower($requiredNormalized);

        $isRequired = in_array($requiredLower, ['true', 'min'], true);
        if (!$isRequired) {
            return '-';
        }

        $packQty = '';
        if (isset($row['ilosc_w_opakowaniu'])) {
            $packQty = trim((string)$row['ilosc_w_opakowaniu']);
        }

        if ($packQty === '') {
            return '-';
        }

        $packQty = str_replace(',', '.', $packQty);
        return 'min (' . rtrim(rtrim(number_format((float)$packQty, 2, '.', ''), '0'), '.') . ')';
    }

    public function displayCreatedStatus($value, $row)
    {
        $isCreated = (int)$value === 1;
        if (!$isCreated) {
            return '<span class="badge badge-warning" style="background:#f39c12; color:white;">Brak</span>';
        }

        $isCreatedByModule = isset($row['azada_ps_created_module']) && (int)$row['azada_ps_created_module'] === 1;
        if ($isCreatedByModule) {
            return '<span class="badge badge-success" style="background:#2ecc71; color:white;">Utw. moduł</span>';
        }

        return '<span class="badge badge-info" style="background:#5bc0de; color:white;">Utw. poza modułem</span>';
    }

    public function displayManualCreateAction($value, $row)
    {
        $idProduct = isset($row['azada_ps_id_product']) ? (int)$row['azada_ps_id_product'] : 0;
        if ($idProduct > 0) {
            $editUrl = $this->buildAdminProductEditUrl($idProduct);
            return '<a href="'.$editUrl.'" class="btn btn-default btn-xs" target="_blank">Edytuj</a>';
        }

        $createUrl = $this->buildAdminProductCreateUrl($row);
        return '<a href="'.$createUrl.'" class="btn btn-primary btn-xs" target="_blank">Dodaj ręcznie</a>';
    }

    private function buildAdminProductEditUrl($idProduct)
    {
        $idProduct = (int)$idProduct;
        if ($idProduct <= 0) {
            return $this->buildAdminProductCreateUrl([]);
        }

        $baseUrl = $this->context->link->getAdminLink('AdminProducts', true);
        $parts = parse_url($baseUrl);

        if (isset($parts['path']) && strpos($parts['path'], '/sell/catalog/products') !== false) {
            $path = rtrim($parts['path'], '/') . '/' . $idProduct;
            $query = isset($parts['query']) && $parts['query'] !== '' ? '?' . $parts['query'] : '';
            return $path . $query;
        }

        return $this->context->link->getAdminLink('AdminProducts', true, [], [
            'id_product' => $idProduct,
            'updateproduct' => 1,
        ]);
    }

    private function buildAdminProductCreateUrl(array $row)
    {
        $sourceTable = isset($row['source_table']) ? trim((string)$row['source_table']) : '';
        $ean = isset($row['kod_kreskowy']) ? trim((string)$row['kod_kreskowy']) : '';
        $sku = isset($row['produkt_id']) ? trim((string)$row['produkt_id']) : '';

        $trackingParams = [
            'azada_manual_create' => 1,
            'azada_source_table' => $sourceTable,
            'azada_ean' => $ean,
            'azada_sku' => $sku,
        ];

        $baseUrl = $this->context->link->getAdminLink('AdminProducts', true);
        $parts = parse_url($baseUrl);

        if (isset($parts['path']) && strpos($parts['path'], '/sell/catalog/products') !== false) {
            $path = rtrim($parts['path'], '/') . '/new';
            $queryParts = [];
            if (isset($parts['query']) && $parts['query'] !== '') {
                parse_str($parts['query'], $parsed);
                if (is_array($parsed)) {
                    $queryParts = $parsed;
                }
            }

            $queryParts = array_merge($queryParts, $trackingParams);
            $query = http_build_query($queryParts);

            return $path . ($query !== '' ? '?' . $query : '');
        }

        return $this->context->link->getAdminLink('AdminProducts', true, [], array_merge([
            'addproduct' => 1,
        ], $trackingParams));
    }

    public function renderList()
    {
        $baseAction = self::$currentIndex . '&token=' . $this->token;
        $action = $baseAction;
        $query = htmlspecialchars($this->globalSearchQuery, ENT_QUOTES, 'UTF-8');
        $isMinQtyChecked = $this->onlyMinimalQty ? ' checked="checked"' : '';
        $isInStockChecked = $this->onlyInStock ? ' checked="checked"' : '';
        $createdFilterEscaped = htmlspecialchars($this->productCreatedFilter, ENT_QUOTES, 'UTF-8');

        $html = '';

        // Niezależny pasek wyszukiwania na samej górze
        $html .= '<div class="panel"><h3><i class="icon-search"></i> Wyszukiwanie globalne</h3>';
        $html .= '<form method="get" action="'.$action.'" class="form-inline">';
        $html .= '<input type="hidden" name="controller" value="'.htmlspecialchars($this->controller_name, ENT_QUOTES, 'UTF-8').'" />';
        $html .= '<input type="hidden" name="token" value="'.htmlspecialchars($this->token, ENT_QUOTES, 'UTF-8').'" />';
        $html .= '<input type="hidden" name="azada_only_min_qty" value="'.($this->onlyMinimalQty ? '1' : '0').'" />';
        $html .= '<input type="hidden" name="azada_only_in_stock" value="'.($this->onlyInStock ? '1' : '0').'" />';
        $html .= '<input type="hidden" name="azada_product_created" value="'.$createdFilterEscaped.'" />';
        $html .= '<div class="form-group" style="margin-right:10px;">';
        $html .= '<input type="text" name="azada_q" value="'.$query.'" class="form-control" style="min-width:420px;" placeholder="Szukaj we wszystkich hurtowniach: nazwa, EAN, SKU, marka..." />';
        $html .= '</div>';
        $html .= '<button type="submit" class="btn btn-primary"><i class="icon-search"></i> Szukaj</button>';
        $html .= '</form></div>';

        // Multi-select hurtowni + przycisk Wybierz (styl dropdown z wyszukiwaniem)
        $selectedLabels = [];
        foreach ($this->selectedWholesalers as $selectedTable) {
            $selectedLabels[] = $this->getWholesalerDisplayName($selectedTable);
        }

        $selectedSummary = empty($selectedLabels)
            ? 'Wszystkie hurtownie'
            : implode(', ', $selectedLabels);

        $selectedSummaryEscaped = htmlspecialchars($selectedSummary, ENT_QUOTES, 'UTF-8');
        $html .= '<div class="panel"><h3><i class="icon-filter"></i> Filtry hurtowni</h3>';
        $html .= '<form method="get" action="'.$action.'">';
        $html .= '<input type="hidden" name="controller" value="'.htmlspecialchars($this->controller_name, ENT_QUOTES, 'UTF-8').'" />';
        $html .= '<input type="hidden" name="token" value="'.htmlspecialchars($this->token, ENT_QUOTES, 'UTF-8').'" />';
        if ($this->globalSearchQuery !== '') {
            $html .= '<input type="hidden" name="azada_q" value="'.$query.'" />';
        }
        $html .= '<div style="margin:8px 0 14px; padding-left:12px; display:flex; gap:34px; flex-wrap:wrap; align-items:center;">';
        $html .= '<label class="checkbox" style="font-weight:600; margin:0; padding-left:2px;">';
        $html .= '<input type="checkbox" name="azada_only_min_qty" value="1"'.$isMinQtyChecked.' style="margin-right:10px;" /> Tylko produkty z minimalną ilością';
        $html .= '</label>';
        $html .= '<label class="checkbox" style="font-weight:600; margin:0;">';
        $html .= '<input type="checkbox" name="azada_only_in_stock" value="1"'.$isInStockChecked.' style="margin-right:10px;" /> Tylko produkty dostępne Na stanie TRUE';
        $html .= '</label>';
        $html .= '<label style="font-weight:600; margin:0; display:flex; align-items:center; gap:10px;">';
        $html .= '<span>Status w Presta:</span>';
        $html .= '<select name="azada_product_created" class="form-control" style="min-width:170px;">';
        $html .= '<option value="all"'.($this->productCreatedFilter === 'all' ? ' selected="selected"' : '').'>Wszystkie</option>';
        $html .= '<option value="created"'.($this->productCreatedFilter === 'created' ? ' selected="selected"' : '').'>Utworzone (wszystkie)</option>';
        $html .= '<option value="created_module"'.($this->productCreatedFilter === 'created_module' ? ' selected="selected"' : '').'>Utworzone w module</option>';
        $html .= '<option value="created_other"'.($this->productCreatedFilter === 'created_other' ? ' selected="selected"' : '').'>Utworzone poza modułem</option>';
        $html .= '<option value="missing"'.($this->productCreatedFilter === 'missing' ? ' selected="selected"' : '').'>Brak</option>';
        $html .= '</select>';
        $html .= '</label>';
        $html .= '</div>';
        $html .= '<div class="form-group" style="margin-bottom:12px;">';
        $html .= '<label style="display:block; margin-bottom:8px; font-weight:600;">Hurtownie (możesz wybrać kilka):</label>';
        $html .= '<div style="display:flex; gap:10px; align-items:flex-start; flex-wrap:wrap;">';
        $html .= '<div class="azada-wholesaler-picker" style="position:relative; max-width:1000px; flex:1 1 650px; min-width:300px;">';
        $html .= '<button type="button" class="btn btn-default azada-picker-toggle" style="width:100%; text-align:left; display:flex; justify-content:space-between; align-items:center;">';
        $html .= '<span class="azada-picker-selected">'.$selectedSummaryEscaped.'</span>';
        $html .= '<i class="icon-caret-down"></i>';
        $html .= '</button>';
        $html .= '<div class="azada-picker-menu panel" style="display:none; position:absolute; left:0; right:0; top:100%; z-index:1000; margin-top:6px; padding:10px; background:#fff; border:1px solid #d3d8db; box-shadow:0 8px 20px rgba(0,0,0,.08);">';
        $html .= '<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px;">';
        $html .= '<strong>Wybierz hurtownie</strong>';
        $html .= '<div>';
        $html .= '<a href="#" class="azada-picker-select-all" style="margin-right:10px;">Wszystkie</a>';
        $html .= '<a href="#" class="azada-picker-clear">Wyczyść</a>';
        $html .= '</div></div>';
        $html .= '<input type="text" class="form-control azada-picker-search" placeholder="Szukaj hurtowni..." style="margin-bottom:10px;" />';
        $html .= '<div class="azada-picker-options" style="max-height:180px; overflow:auto; border:1px solid #e5e5e5; padding:8px;">';

        foreach ($this->availableRawTables as $table) {
            $isSelected = in_array($table, $this->selectedWholesalers, true) ? ' selected="selected"' : '';
            $isChecked = $isSelected !== '' ? ' checked="checked"' : '';
            $valueEscaped = htmlspecialchars($table, ENT_QUOTES, 'UTF-8');
            $label = htmlspecialchars($this->getWholesalerDisplayName($table), ENT_QUOTES, 'UTF-8');
            $html .= '<label class="azada-picker-option" data-label="'.strtolower($label).'" style="display:block; margin:0 0 6px; font-weight:400;">';
            $html .= '<input type="checkbox" class="azada-picker-checkbox" value="'.$valueEscaped.'"'.$isChecked.' /> '.$label;
            $html .= '</label>';
        }

        $html .= '</div>';
        $html .= '<div class="azada-picker-hidden-inputs"></div>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '<div style="display:flex; gap:10px; align-items:center;">';
        $html .= '<button type="submit" class="btn btn-primary" style="margin-top:0;"><i class="icon-check"></i> Wybierz</button>';
        $html .= '<a class="btn btn-default" href="'.$action.'" style="margin-top:0;"><i class="icon-eraser"></i> Wyczyść</a>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '<p class="help-block">Brak zaznaczenia = wszystkie hurtownie.</p>';
        $html .= '</div>';
        $html .= '</form></div>';

        $html .= '<script>
            (function() {
                var picker = document.querySelector(".azada-wholesaler-picker");
                if (!picker) return;

                var toggleBtn = picker.querySelector(".azada-picker-toggle");
                var menu = picker.querySelector(".azada-picker-menu");
                var search = picker.querySelector(".azada-picker-search");
                var options = picker.querySelectorAll(".azada-picker-option");
                var checkboxes = picker.querySelectorAll(".azada-picker-checkbox");
                var selectedLabel = picker.querySelector(".azada-picker-selected");
                var hiddenInputsWrap = picker.querySelector(".azada-picker-hidden-inputs");
                var selectAllLink = picker.querySelector(".azada-picker-select-all");
                var clearLink = picker.querySelector(".azada-picker-clear");

                function normalize(v) {
                    return (v || "").toLowerCase();
                }

                function checkedValues() {
                    var values = [];
                    checkboxes.forEach(function(cb) {
                        if (cb.checked) values.push(cb.value);
                    });
                    return values;
                }

                function checkedLabels() {
                    var labels = [];
                    checkboxes.forEach(function(cb) {
                        if (!cb.checked) return;
                        var lbl = cb.parentNode.textContent || "";
                        labels.push(lbl.trim());
                    });
                    return labels;
                }

                function syncHiddenInputs() {
                    hiddenInputsWrap.innerHTML = "";
                    checkedValues().forEach(function(value) {
                        var input = document.createElement("input");
                        input.type = "hidden";
                        input.name = "azada_wholesalers[]";
                        input.value = value;
                        hiddenInputsWrap.appendChild(input);
                    });
                }

                function syncSummary() {
                    var labels = checkedLabels();
                    selectedLabel.textContent = labels.length ? labels.join(", ") : "Wszystkie hurtownie";
                }

                function syncAll() {
                    syncSummary();
                    syncHiddenInputs();
                }

                toggleBtn.addEventListener("click", function() {
                    menu.style.display = menu.style.display === "none" ? "block" : "none";
                });

                document.addEventListener("click", function(event) {
                    if (!picker.contains(event.target)) {
                        menu.style.display = "none";
                    }
                });

                if (search) {
                    search.addEventListener("input", function() {
                        var q = normalize(search.value);
                        options.forEach(function(option) {
                            var label = option.getAttribute("data-label") || "";
                            option.style.display = label.indexOf(q) !== -1 ? "block" : "none";
                        });
                    });
                }

                checkboxes.forEach(function(cb) {
                    cb.addEventListener("change", syncAll);
                });

                selectAllLink.addEventListener("click", function(e) {
                    e.preventDefault();
                    checkboxes.forEach(function(cb) { cb.checked = true; });
                    syncAll();
                });

                clearLink.addEventListener("click", function(e) {
                    e.preventDefault();
                    checkboxes.forEach(function(cb) { cb.checked = false; });
                    syncAll();
                });

                syncAll();
            })();
        </script>';

        return $html . parent::renderList();
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
