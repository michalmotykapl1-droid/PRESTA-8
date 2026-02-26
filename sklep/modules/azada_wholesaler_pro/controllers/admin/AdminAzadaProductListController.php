<?php

require_once(dirname(__FILE__) . '/../../classes/AzadaRawData.php');
require_once(dirname(__FILE__) . '/../../classes/services/AzadaInstaller.php');
require_once(dirname(__FILE__) . '/../../classes/services/AzadaCategoryImportMatcher.php');
require_once(dirname(__FILE__) . '/../../classes/services/AzadaManufacturerImportMatcher.php');

class AdminAzadaProductListController extends ModuleAdminController
{
    private $availableRawTables = [];
    private $selectedWholesalers = [];
    private $globalSearchQuery = '';
    private $onlyMinimalQty = false;
    private $onlyInStock = false;
    private $productCreatedFilter = 'all';
    private $hasProductOriginTable = false;
    private $hasCategoryMapTable = false;
    private $allowLinkExistingProducts = false;

    public function __construct()
    {
        $this->bootstrap = true;
        $this->context = Context::getContext();

        // Konfiguracja listy (legacy HelperList)
        // - bulk actions (checkboxy)
        // - paginacja / "pokaż na stronie"
        // Ustawiamy to na początku, aby zarówno lista jak i bulk actions
        // działały spójnie na tej samej tabeli/identyfikatorze.
        $this->table = 'azada_raw_search_index';
        $this->identifier = 'id_raw';
        $this->className = 'AzadaRawData';
        $this->list_no_link = true;

        // Standardowe miejsce w modułach do sterowania "Pokaż na stronie (x)".
        // W PS 1.7/8 legacy listy korzystają z tych pól.
        $this->_default_pagination = 50;
        $this->_pagination = [20, 50, 100, 300, 1000];

        $this->availableRawTables = $this->getAvailableRawTables();
        // Jeśli w bazie pojawiła się nowa tabela hurtowni (np. azada_raw_abro),
        // to automatycznie dodajemy ją do listy integracji modułu (Konfiguracja/HUB).
        // Dzięki temu nie trzeba ręcznie "dodawać hurtowni" – moduł ją wykrywa.
        $this->ensureIntegrationsForAvailableRawTables();
        $this->selectedWholesalers = $this->normalizeSelectedWholesalers(Tools::getValue('azada_wholesalers', []));
        $this->globalSearchQuery = trim((string)Tools::getValue('azada_q', ''));
        $this->onlyMinimalQty = (Tools::getValue('azada_only_min_qty', '0') === '1');
        $this->onlyInStock = (Tools::getValue('azada_only_in_stock', '0') === '1');
        $this->productCreatedFilter = $this->normalizeCreatedFilter(Tools::getValue('azada_product_created', 'all'));

        // Dla akcji utworzenia produktu nie musimy przebudowywać indeksu wyszukiwania.
        // To przyspiesza „Dodaj ręcznie” i unika zbędnego TRUNCATE.
        // WAŻNE: bulk actions działają na ID z tabeli indeksu – jeśli zrobimy TRUNCATE,
        // to zaznaczenia przestaną wskazywać właściwe rekordy.
        $requestedAction = trim((string)Tools::getValue('action', ''));
        $isBulkManualCreate = Tools::isSubmit('submitBulkmanualCreateProducts' . $this->table);
        if ($requestedAction !== 'manualCreateProduct' && !$isBulkManualCreate) {
            $this->buildGlobalSearchIndexTable();
        }
        $this->hasProductOriginTable = $this->tableExists(_DB_PREFIX_ . 'azada_wholesaler_pro_product_origin');
        $this->hasCategoryMapTable = $this->tableExists(_DB_PREFIX_ . 'azada_wholesaler_pro_category_map');
        $this->allowLinkExistingProducts = ((int)Configuration::get('AZADA_LINK_EXISTING_PRODUCTS', 0) === 1);

        // Jeśli produkt został usunięty w PrestaShop, wpis w tabeli product_origin zostaje.
        // To powoduje mylący status „Utw. moduł/Połączony” w Poczekalni oraz próby edycji
        // nieistniejącego ID produktu.
        //
        // Poprawka: automatycznie czyścimy osierocone wpisy origin (id_product nie istnieje już w ps_product).
        // Dzięki temu po usunięciu produktu w PrestaShop w Poczekalni wraca status „Brak”.
        if ($this->hasProductOriginTable) {
            $this->cleanupOrphanProductOriginRows();
        }

        parent::__construct();

        // Akcje masowe (checkboxy + dropdown) – standardowy mechanizm PrestaShop.
        // Użytkownik ma wtedy:
        // - checkboxy w wierszach (lewa strona)
        // - checkbox "zaznacz wszystkie" w nagłówku
        // - menu "Działania masowe" nad listą
        $this->bulk_actions = [
            'manualCreateProducts' => [
                'text' => 'Utwórz produkty w PrestaShop',
                'icon' => 'icon-plus',
                'confirm' => 'Utworzyć produkty w PrestaShop dla zaznaczonych pozycji? Produkty już istniejące zostaną pominięte (lub uzupełnione, jeśli brakuje opisu/zdjęć).',
            ],
        ];

        $this->buildReadableList();
    }

    public function postProcess()
    {
        $action = (string)Tools::getValue('action');

        if ($action === 'manualCreateProduct') {
            $this->processManualCreateProduct();
            // processManualCreateProduct w większości przypadków robi redirect.
            // Jeśli nie, lecimy dalej i pokażemy ewentualny komunikat/błąd.
        } elseif ($action === 'linkExistingProduct') {
            $this->processLinkExistingProduct();
        }

        parent::postProcess();
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

    /**
     * Automatycznie synchronizuje tabelę integracji modułu z wykrytymi tabelami RAW.
     *
     * Cel: jeśli w bazie pojawi się nowa hurtownia (np. azada_raw_abro),
     * to ma ona automatycznie pojawić się w konfiguracji modułu (HUB),
     * bez ręcznego dodawania rekordu.
     */
    private function ensureIntegrationsForAvailableRawTables()
    {
        if (empty($this->availableRawTables)) {
            return;
        }

        // Integracja jest tabelą modułu – jeśli jej nie ma, to i tak nic nie zrobimy.
        if (!$this->tableExists(_DB_PREFIX_ . 'azada_wholesaler_pro_integration')) {
            return;
        }

        foreach ($this->availableRawTables as $sourceTable) {
            $this->ensureWholesalerIntegrationRow((string)$sourceTable);
        }
    }

    /**
     * Zapewnia istnienie rekordu hurtowni w tabeli integracji (Konfiguracja/HUB).
     * Zwraca: ['id_wholesaler' => int, 'name' => string]
     */
    private function ensureWholesalerIntegrationRow($sourceTable)
    {
        $sourceTable = trim((string)$sourceTable);
        if ($sourceTable === '') {
            return null;
        }

        $tableIntegration = _DB_PREFIX_ . 'azada_wholesaler_pro_integration';

        try {
            $rows = Db::getInstance()->executeS(
                'SELECT id_wholesaler, name '
                . 'FROM `' . bqSQL($tableIntegration) . '` '
                . 'WHERE raw_table_name=\'' . pSQL($sourceTable) . '\' '
                . 'ORDER BY id_wholesaler ASC '
                . 'LIMIT 1'
            );
        } catch (Exception $e) {
            $rows = [];
        }

        if (is_array($rows) && !empty($rows) && isset($rows[0]) && is_array($rows[0])) {
            return [
                'id_wholesaler' => (int)$rows[0]['id_wholesaler'],
                'name' => isset($rows[0]['name']) ? (string)$rows[0]['name'] : $this->getWholesalerDisplayName($sourceTable),
            ];
        }

        // Brak rekordu integracji – tworzymy automatycznie.
        $name = $this->getWholesalerDisplayName($sourceTable);
        $now = date('Y-m-d H:i:s');

        // file_url jest NOT NULL w schemacie, więc dajemy pusty string.
        $payload = [
            'name' => $name,
            'active' => 1,
            'raw_table_name' => $sourceTable,
            'file_url' => '',
            'file_format' => 'csv',
            'delimiter' => ';',
            'encoding' => 'UTF-8',
            'skip_header' => 1,
            'api_key' => null,
            'b2b_login' => null,
            'b2b_password' => null,
            'connection_status' => 0,
            'diagnostic_result' => null,
            'last_import' => null,
            'date_add' => $now,
            'date_upd' => $now,
        ];

        try {
            Db::getInstance()->insert('azada_wholesaler_pro_integration', $payload, true);
            $id = (int)Db::getInstance()->Insert_ID();
        } catch (Exception $e) {
            $id = 0;
        }

        return [
            'id_wholesaler' => (int)$id,
            'name' => $name,
        ];
    }

    /**
     * Zapewnia istnienie dostawcy (Supplier) dla danej hurtowni.
     * Zwraca id_supplier.
     */
    private function ensureSupplierIdForWholesalerName($wholesalerName)
    {
        $name = trim((string)$wholesalerName);
        if ($name === '') {
            return 0;
        }

        $supplierTable = _DB_PREFIX_ . 'supplier';
        try {
            $rows = Db::getInstance()->executeS(
                'SELECT id_supplier FROM `' . bqSQL($supplierTable) . '` '
                . 'WHERE LOWER(name)=LOWER(\'' . pSQL($name) . '\') '
                . 'ORDER BY id_supplier ASC '
                . 'LIMIT 1'
            );
        } catch (Exception $e) {
            $rows = [];
        }

        if (is_array($rows) && !empty($rows) && isset($rows[0]['id_supplier'])) {
            return (int)$rows[0]['id_supplier'];
        }

        if (!class_exists('Supplier')) {
            return 0;
        }

        $supplier = new Supplier();
        $supplier->name = $name;
        $supplier->active = 1;

        if (!$supplier->add()) {
            return 0;
        }

        // MultiShop: skojarz dostawcę z aktualnym sklepem
        if (method_exists($supplier, 'associateTo')) {
            try {
                $supplier->associateTo((int)$this->context->shop->id);
            } catch (Exception $e) {
                // ignore
            }
        }

        return (int)$supplier->id;
    }

    /**
     * Przypisuje dostawcę do produktu (id_supplier + product_supplier) w sposób kompatybilny z PS 1.7.
     */
    private function ensureProductSupplierLink($idProduct, $idSupplier, $supplierReference = '', $supplierPriceNet = 0.0)
    {
        $idProduct = (int)$idProduct;
        $idSupplier = (int)$idSupplier;

        if ($idProduct <= 0 || $idSupplier <= 0) {
            return;
        }

        // Ustaw domyślnego dostawcę na produkcie (product + product_shop)
        try {
            Db::getInstance()->update('product', ['id_supplier' => (int)$idSupplier], 'id_product=' . (int)$idProduct);
        } catch (Exception $e) {
            // ignore
        }

        // product_shop istnieje w PS 1.7
        try {
            Db::getInstance()->update('product_shop', ['id_supplier' => (int)$idSupplier], 'id_product=' . (int)$idProduct . ' AND id_shop=' . (int)$this->context->shop->id);
        } catch (Exception $e) {
            // ignore
        }

        // Wpis w product_supplier (potrzebny, żeby dostawca był widoczny w zakładce "Dostawcy")
        $idCurrency = (int)Configuration::get('PS_CURRENCY_DEFAULT');
        if ($idCurrency <= 0 && isset($this->context->currency) && isset($this->context->currency->id)) {
            $idCurrency = (int)$this->context->currency->id;
        }
        if ($idCurrency <= 0) {
            $idCurrency = 1;
        }

        $ref = trim((string)$supplierReference);
        if ($ref !== '') {
            $ref = $this->truncateString($ref, 64);
        }

        $supplierPriceNet = (float)$supplierPriceNet;
        if ($supplierPriceNet < 0) {
            $supplierPriceNet = 0.0;
        }

        $tableProductSupplier = _DB_PREFIX_ . 'product_supplier';
        $existingId = 0;
        try {
            $rows = Db::getInstance()->executeS(
                'SELECT id_product_supplier FROM `' . bqSQL($tableProductSupplier) . '` '
                . 'WHERE id_product=' . (int)$idProduct . ' AND id_product_attribute=0 AND id_supplier=' . (int)$idSupplier . ' '
                . 'ORDER BY id_product_supplier ASC '
                . 'LIMIT 1'
            );
            if (is_array($rows) && !empty($rows) && isset($rows[0]['id_product_supplier'])) {
                $existingId = (int)$rows[0]['id_product_supplier'];
            }
        } catch (Exception $e) {
            $existingId = 0;
        }

        $payload = [
            'id_product' => (int)$idProduct,
            'id_product_attribute' => 0,
            'id_supplier' => (int)$idSupplier,
            'product_supplier_reference' => $ref,
            'product_supplier_price_te' => (float)$supplierPriceNet,
            'id_currency' => (int)$idCurrency,
        ];

        try {
            if ($existingId > 0) {
                Db::getInstance()->update('product_supplier', $payload, 'id_product_supplier=' . (int)$existingId);
            } else {
                Db::getInstance()->insert('product_supplier', $payload, true);
            }
        } catch (Exception $e) {
            // ignore
        }
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

    /**
     * Usuwa osierocone wpisy w tabeli product_origin, gdy produkt został skasowany z PrestaShop.
     *
     * Bez tego Poczekalnia dalej pokazuje status „Utw. moduł/Połączony”, mimo że produkt już nie istnieje.
     */
    private function cleanupOrphanProductOriginRows()
    {
        static $done = false;
        if ($done) {
            return;
        }
        $done = true;

        $originTable = _DB_PREFIX_ . 'azada_wholesaler_pro_product_origin';
        $productTable = _DB_PREFIX_ . 'product';

        try {
            Db::getInstance()->execute(
                'DELETE o FROM `' . bqSQL($originTable) . '` o '
                . 'LEFT JOIN `' . bqSQL($productTable) . '` p ON p.`id_product` = o.`id_product` '
                . 'WHERE p.`id_product` IS NULL'
            );
        } catch (Exception $e) {
            // ignore
        }
    }

    /**
     * Lekki check, czy produkt istnieje w tabeli ps_product.
     */
    private function productExists($idProduct)
    {
        $idProduct = (int)$idProduct;
        if ($idProduct <= 0) {
            return false;
        }

        static $cache = [];
        if (isset($cache[$idProduct])) {
            return (bool)$cache[$idProduct];
        }

        try {
            $exists = (int)Db::getInstance()->getValue(
                'SELECT 1 FROM `' . bqSQL(_DB_PREFIX_ . 'product') . '` WHERE `id_product`=' . (int)$idProduct
            );
        } catch (Exception $e) {
            $exists = 0;
        }

        $cache[$idProduct] = ($exists > 0);
        return (bool)$cache[$idProduct];
    }

    private function normalizeCreatedFilter($raw)
    {
        $value = strtolower(trim((string)$raw));
        if (!in_array($value, ['all', 'created', 'created_module', 'created_other', 'linked', 'missing'], true)) {
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
        $this->prepareCategoryImportSupport($existing);
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
            'azada_import_category',
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
            'azada_import_category' => true,
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

        if ($field === 'azada_import_category') {
            return [
                'title' => 'Import (kat.)',
                'align' => 'center',
                'callback' => 'displayImportEligibility',
                'search' => false,
                'havingFilter' => false,
                'width' => 110,
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

    private function prepareCategoryImportSupport(array $existing)
    {
        // Wskazanie importowalności po mapowaniu kategorii.
        // Dodajemy wirtualną kolumnę, a sama decyzja jest liczona w callbacku na podstawie:
        // - source_table
        // - kategoria
        if (!isset($existing['kategoria'])) {
            return;
        }

        $expr = "'' AS `azada_import_category`";
        if (trim((string)$this->_select) === '') {
            $this->_select = $expr;
            return;
        }

        $this->_select .= ', ' . $expr;
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

        // 1) Match po product_origin (czyli produkty już utworzone/połączone z modułem)
        $originJoinSql = $this->buildOriginMatchJoinSql();
        // 2) Match "luźny" po tabeli product (produkty istniejące w Presta, ale jeszcze niepołączone)
        $productJoinSql = $this->buildProductMatchJoinSql();

        $joinSql = trim($originJoinSql . ' ' . $productJoinSql);
        if (trim((string)$this->_join) === '') {
            $this->_join = $joinSql;
        } else {
            $this->_join .= ' ' . $joinSql;
        }

        $managedIdSql = 'COALESCE(aor.`id_product`, aoe.`id_product`)';
        $externalIdSql = 'COALESCE(pr.`id_product`, pe.`id_product`)';
        $idSql = 'COALESCE(' . $managedIdSql . ', ' . $externalIdSql . ')';

        $createdSql = '(CASE WHEN ' . $idSql . ' IS NOT NULL THEN 1 ELSE 0 END)';
        $managedSql = '(CASE WHEN ' . $managedIdSql . ' IS NOT NULL THEN 1 ELSE 0 END)';

        $createdByModuleSql = ($this->hasProductOriginTable ? 'IFNULL(apo.`created_by_module`, 0)' : '0');

        $selectChunk = "$createdSql AS `azada_ps_created`, "
            . "$managedSql AS `azada_ps_managed`, "
            . "$idSql AS `azada_ps_id_product`, "
            . "$managedIdSql AS `azada_ps_managed_id_product`, "
            . "$externalIdSql AS `azada_ps_external_id_product`, "
            . "(CASE WHEN pr.`id_product` IS NOT NULL THEN 1 ELSE 0 END) AS `azada_ps_external_match_sku`, "
            . "$createdByModuleSql AS `azada_ps_created_module`, "
            . "'' AS `azada_manual_create`";

        if (trim((string)$this->_select) === '') {
            $this->_select = $selectChunk;
            return;
        }

        $this->_select .= ', ' . $selectChunk;
    }

    private function buildOriginMatchJoinSql()
    {
        // Jeśli tabela nie istnieje, musimy i tak zdefiniować aliasy aor/aoe/apo,
        // bo część filtrów korzysta z tych aliasów.
        if (!$this->hasProductOriginTable) {
            return "LEFT JOIN (SELECT NULL AS `source_table`, NULL AS `reference`, NULL AS `id_product`) aor ON 1=0 "
                . "LEFT JOIN (SELECT NULL AS `source_table`, NULL AS `ean13`, NULL AS `id_product`) aoe ON 1=0 "
                . "LEFT JOIN (SELECT NULL AS `id_product`, 0 AS `created_by_module`, NULL AS `source_table`) apo ON 1=0";
        }

        $originTable = bqSQL(_DB_PREFIX_ . 'azada_wholesaler_pro_product_origin');
        $productTable = bqSQL(_DB_PREFIX_ . 'product');

        // Grupujemy po source_table, żeby EAN/SKU z różnych hurtowni nie mieszały produktów.
        // UWAGA:
        // - w origin mogą zostać wpisy po produktach skasowanych w PrestaShop.
        // - jeśli nie odfiltrujemy nieistniejących id_product, Poczekalnia będzie pokazywać „Utw. moduł/Połączony”
        //   i generować linki do edycji nieistniejących produktów.
        //
        // Dlatego w subquery dołączamy ps_product i bierzemy tylko istniejące produkty.
        return "LEFT JOIN ("
            . "SELECT o.`source_table`, o.`reference`, MIN(o.`id_product`) AS `id_product` "
            . "FROM `{$originTable}` o "
            . "INNER JOIN `{$productTable}` p ON p.`id_product` = o.`id_product` "
            . "WHERE TRIM(IFNULL(o.`reference`, '')) <> '' "
            . "GROUP BY o.`source_table`, o.`reference`"
            . ") aor ON (aor.`source_table` = a.`source_table` AND aor.`reference` = a.`produkt_id`) "
            . "LEFT JOIN ("
            . "SELECT o.`source_table`, o.`ean13`, MIN(o.`id_product`) AS `id_product` "
            . "FROM `{$originTable}` o "
            . "INNER JOIN `{$productTable}` p ON p.`id_product` = o.`id_product` "
            . "WHERE TRIM(IFNULL(o.`ean13`, '')) <> '' "
            . "GROUP BY o.`source_table`, o.`ean13`"
            . ") aoe ON (aoe.`source_table` = a.`source_table` AND aoe.`ean13` = a.`kod_kreskowy`) "
            . "LEFT JOIN `{$originTable}` apo ON (apo.`id_product` = COALESCE(aor.`id_product`, aoe.`id_product`) AND apo.`source_table` = a.`source_table`)";
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

        // Status w Presta (created/linked/missing)
        // Uwaga: od pewnego momentu priorytetem jest powiązanie z tabelą product_origin,
        // a dopiero potem "luźny" match po ean/reference w tabeli produktów.
        if ($this->productCreatedFilter === 'created') {
            $conditions[] = '(aor.`id_product` IS NOT NULL OR aoe.`id_product` IS NOT NULL OR pe.`id_product` IS NOT NULL OR pr.`id_product` IS NOT NULL)';
        } elseif ($this->productCreatedFilter === 'created_module') {
            $conditions[] = '(aor.`id_product` IS NOT NULL OR aoe.`id_product` IS NOT NULL)';
            $conditions[] = ($this->hasProductOriginTable ? 'IFNULL(apo.`created_by_module`, 0) = 1' : '1 = 0');
        } elseif ($this->productCreatedFilter === 'linked') {
            $conditions[] = '(aor.`id_product` IS NOT NULL OR aoe.`id_product` IS NOT NULL)';
            $conditions[] = ($this->hasProductOriginTable ? 'IFNULL(apo.`created_by_module`, 0) = 0' : '1 = 0');
        } elseif ($this->productCreatedFilter === 'created_other') {
            $conditions[] = '(aor.`id_product` IS NULL AND aoe.`id_product` IS NULL)';
            $conditions[] = '(pe.`id_product` IS NOT NULL OR pr.`id_product` IS NOT NULL)';
        } elseif ($this->productCreatedFilter === 'missing') {
            $conditions[] = '(aor.`id_product` IS NULL AND aoe.`id_product` IS NULL AND pe.`id_product` IS NULL AND pr.`id_product` IS NULL)';
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

        $isManaged = isset($row['azada_ps_managed']) && (int)$row['azada_ps_managed'] === 1;

        $isCreatedByModule = isset($row['azada_ps_created_module']) && (int)$row['azada_ps_created_module'] === 1;
        if ($isCreatedByModule) {
            return '<span class="badge badge-success" style="background:#2ecc71; color:white;">Utw. moduł</span>';
        }

        // Połączony (zarządzany przez moduł, ale utworzony poza modułem)
        if ($isManaged) {
            return '<span class="badge badge-primary" style="background:#8e44ad; color:white;">Połączony</span>';
        }

        return '<span class="badge badge-info" style="background:#5bc0de; color:white;">Utw. poza modułem</span>';
    }

    public function displayImportEligibility($value, $row)
    {
        if (!$this->hasCategoryMapTable) {
            return '<span class="text-muted">-</span>';
        }

        $sourceTable = isset($row['source_table']) ? (string)$row['source_table'] : '';
        $rawCategory = isset($row['kategoria']) ? (string)$row['kategoria'] : '';

        $match = AzadaCategoryImportMatcher::match($sourceTable, $rawCategory);
        $isOk = isset($match['is_importable']) && (bool)$match['is_importable'];

        if ($isOk) {
            $idDefault = isset($match['id_category_default']) ? (int)$match['id_category_default'] : 0;
            $matched = isset($match['matched_source_categories']) && is_array($match['matched_source_categories'])
                ? implode(', ', $match['matched_source_categories'])
                : '';

            $title = 'Import ON';
            if ($matched !== '') {
                $title .= ' | match: ' . $matched;
            }
            if ($idDefault > 0) {
                $title .= ' | domyślna ID: ' . $idDefault;
            }

            return '<span title="' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '" style="color:#72C279; font-weight:700;">✓</span>';
        }

        $reason = isset($match['reason']) ? (string)$match['reason'] : '';
        $title = 'Import OFF';
        if ($reason !== '') {
            $title .= ' | powód: ' . $reason;
        }

        return '<span title="' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '" style="color:#D9534F; font-weight:700;">✕</span>';
    }

    public function displayManualCreateAction($value, $row)
    {
        $idProduct = isset($row['azada_ps_id_product']) ? (int)$row['azada_ps_id_product'] : 0;
        $isManaged = isset($row['azada_ps_managed']) && (int)$row['azada_ps_managed'] === 1;
        $externalId = isset($row['azada_ps_external_id_product']) ? (int)$row['azada_ps_external_id_product'] : 0;

        // 1) Produkt zarządzany przez moduł → edycja
        if ($isManaged && $idProduct > 0) {
            $editUrl = $this->buildAdminProductEditUrl($idProduct);
            return '<a href="' . htmlspecialchars($editUrl, ENT_QUOTES, 'UTF-8') . '" class="btn btn-default btn-xs" target="_blank">Edytuj</a>';
        }

        // 2) Produkt istnieje w PrestaShop, ale NIE jest połączony z modułem ("poza modułem")
        if (!$isManaged && $externalId > 0) {
            $createUrl = $this->buildManualCreateProductUrl($row);

            if ($this->allowLinkExistingProducts) {
                $linkUrl = $this->buildLinkExistingProductUrl($row);
                $editUrl = $this->buildAdminProductEditUrl($externalId);
                $matchSku = isset($row['azada_ps_external_match_sku']) && (int)$row['azada_ps_external_match_sku'] === 1;

                $html = '<div style="display:flex; gap:6px; flex-wrap:wrap; justify-content:flex-end;">'
                    . '<a href="' . htmlspecialchars($editUrl, ENT_QUOTES, 'UTF-8') . '" class="btn btn-default btn-xs" target="_blank">Edytuj</a>';

                // Połączamy tylko, jeśli match był po SKU/reference (bezpieczniej niż po samym EAN).
                if ($matchSku) {
                    $html .= '<a href="' . htmlspecialchars($linkUrl, ENT_QUOTES, 'UTF-8') . '" class="btn btn-warning btn-xs" '
                        . 'onclick="return confirm(\'Połączyć istniejący produkt z modułem? Od tego momentu będzie aktualizowany przez ten moduł.\');">Połącz</a>';
                }

                $html .= '<a href="' . htmlspecialchars($createUrl, ENT_QUOTES, 'UTF-8') . '" class="btn btn-primary btn-xs" target="_blank">Utwórz nowy</a>'
                    . '</div>';

                return $html;
            }

            // Gdy WYŁĄCZONE: nie pokazujemy edycji ani połączenia – tylko tworzenie nowego produktu.
            return '<a href="' . htmlspecialchars($createUrl, ENT_QUOTES, 'UTF-8') . '" class="btn btn-primary btn-xs" target="_blank">Utwórz nowy</a>';
        }

        // 3) Brak produktu w PrestaShop → tworzenie
        $createUrl = $this->buildManualCreateProductUrl($row);
        return '<a href="' . htmlspecialchars($createUrl, ENT_QUOTES, 'UTF-8') . '" class="btn btn-primary btn-xs" target="_blank">Dodaj ręcznie</a>';
    }

    private function buildLinkExistingProductUrl(array $row)
    {
        $sourceTable = isset($row['source_table']) ? trim((string)$row['source_table']) : '';
        $ean = isset($row['kod_kreskowy']) ? trim((string)$row['kod_kreskowy']) : '';
        $sku = isset($row['produkt_id']) ? trim((string)$row['produkt_id']) : '';

        return $this->context->link->getAdminLink($this->controller_name, true, [], [
            'action' => 'linkExistingProduct',
            'azada_source_table' => $sourceTable,
            'azada_ean' => $ean,
            'azada_sku' => $sku,
        ]);
    }

    private function buildManualCreateProductUrl(array $row)
    {
        $sourceTable = isset($row['source_table']) ? trim((string)$row['source_table']) : '';
        $ean = isset($row['kod_kreskowy']) ? trim((string)$row['kod_kreskowy']) : '';
        $sku = isset($row['produkt_id']) ? trim((string)$row['produkt_id']) : '';

        return $this->context->link->getAdminLink($this->controller_name, true, [], [
            'action' => 'manualCreateProduct',
            'azada_source_table' => $sourceTable,
            'azada_ean' => $ean,
            'azada_sku' => $sku,
        ]);
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

    /**
     * Akcja: utwórz produkt w PrestaShop na podstawie RAW.
     *
     * To jest celowo "manual" (z Poczekalni) – ma dać pewność, że pobieramy dane poprawnie.
     * Na tej bazie będzie później można zrobić crona dla całych kategorii.
     */
    private function processManualCreateProduct()
    {
        if (!$this->access('add')) {
            $this->errors[] = 'Brak uprawnień do tworzenia produktów.';
            return;
        }

        $sourceTable = trim((string)Tools::getValue('azada_source_table', ''));
        $ean = trim((string)Tools::getValue('azada_ean', ''));
        $sku = trim((string)Tools::getValue('azada_sku', ''));

        $result = $this->upsertProductFromRawIdentifiers($sourceTable, $ean, $sku);
        if (is_array($result) && isset($result['id_product']) && (int)$result['id_product'] > 0) {
            Tools::redirectAdmin($this->buildAdminProductEditUrl((int)$result['id_product']));
        }
    }

    /**
     * Akcja: połącz (podłącz) istniejący produkt w PrestaShop (utworzony poza modułem)
     * do mechanizmów tego modułu.
     *
     * W praktyce oznacza to dopisanie wpisu do tabeli pochodzenia:
     * ps_azada_wholesaler_pro_product_origin
     *
     * Dzięki temu:
     * - CRON Update QTY / Update PRICE będzie go aktualizował,
     * - na liście Poczekalni status zmieni się na „Połączony”.
     */
    private function processLinkExistingProduct()
    {
        if (!$this->allowLinkExistingProducts) {
            $this->errors[] = 'Łączenie z istniejącymi produktami jest wyłączone w Konfiguracji (AZADA_LINK_EXISTING_PRODUCTS).';
            return;
        }

        if (!$this->access('edit')) {
            $this->errors[] = 'Brak uprawnień do edycji/łączenia produktów.';
            return;
        }

        $sourceTable = trim((string)Tools::getValue('azada_source_table', ''));
        $ean = trim((string)Tools::getValue('azada_ean', ''));
        $sku = trim((string)Tools::getValue('azada_sku', ''));

        if ($sourceTable === '' || !isset($this->availableRawTables[$sourceTable])) {
            $this->errors[] = 'Nieprawidłowa hurtownia (source_table).';
            return;
        }

        if ($ean === '' && $sku === '') {
            $this->errors[] = 'Brak identyfikatora produktu (EAN i/lub SKU).';
            return;
        }

        // Jeśli już jest połączony – po prostu przejdź do edycji.
        $managedId = $this->findManagedProductId($sourceTable, $ean, $sku);
        if ($managedId > 0) {
            Tools::redirectAdmin($this->buildAdminProductEditUrl((int)$managedId));
        }

        // Znajdź istniejący produkt w Presta.
        // BEZPIECZEŃSTWO: jeśli mamy SKU, to wymagamy matchu po reference (żeby nie połączyć
        // "byle czego" po samym EAN, gdy EAN się powtarza / jest pusty / jest w kilku produktach).
        $existingId = 0;
        $db = Db::getInstance();
        $productTable = bqSQL(_DB_PREFIX_ . 'product');

        if ($sku !== '') {
            $skuNorm = $this->normalizeReference($sku);
            $checkSku = ($skuNorm !== '' ? $skuNorm : $sku);
            $existingId = (int)$db->getValue(
                "SELECT MIN(id_product) FROM `{$productTable}` WHERE TRIM(IFNULL(reference,'')) <> '' AND reference='" . pSQL($checkSku) . "'"
            );

            if ($existingId <= 0) {
                $this->errors[] = 'Nie znaleziono produktu w PrestaShop o takim SKU/reference do połączenia.';
                return;
            }

            // Dodatkowa walidacja: jeśli EAN jest podany i produkt ma EAN, wymagamy zgodności.
            if ($ean !== '') {
                $eanNorm = $this->normalizeEan($ean);
                $checkEan = ($eanNorm !== '' ? $eanNorm : $ean);
                $productEan = trim((string)$db->getValue("SELECT ean13 FROM `{$productTable}` WHERE id_product=" . (int)$existingId));

                if ($productEan !== '' && $productEan !== $checkEan) {
                    $this->errors[] = 'Nie można połączyć: SKU pasuje, ale EAN w PrestaShop jest inny niż w RAW.';
                    return;
                }
            }
        } else {
            // Brak SKU → fallback po EAN
            $existingId = $this->findExistingProductId($ean, '');
            if ($existingId <= 0) {
                $this->errors[] = 'Nie znaleziono produktu w PrestaShop do połączenia (po EAN).';
                return;
            }
        }

        // Zapewnij wpis integracji + Supplier
        $wholesalerIntegration = $this->ensureWholesalerIntegrationRow($sourceTable);
        $wholesalerName = (is_array($wholesalerIntegration) && isset($wholesalerIntegration['name']))
            ? (string)$wholesalerIntegration['name']
            : $this->getWholesalerDisplayName($sourceTable);
        $idSupplier = $this->ensureSupplierIdForWholesalerName($wholesalerName);

        // Pobierz RAW, żeby mieć cenę zakupu do product_supplier
        $raw = $this->fetchRawProductRow($sourceTable, $ean, $sku);
        $purchaseNet = 0.0;
        if (is_array($raw) && isset($raw['cenaporabacienetto'])) {
            $purchaseNet = (float)$this->parseFloat((string)$raw['cenaporabacienetto']);
        }

        // Dopnij wpis w origin (created_by_module=0 → połączony, ale nie utworzony przez moduł)
        AzadaInstaller::ensureProductOriginTable();
        Db::getInstance()->execute('DELETE FROM `' . bqSQL(_DB_PREFIX_ . 'azada_wholesaler_pro_product_origin') . '` WHERE `id_product` = ' . (int)$existingId);
        Db::getInstance()->insert('azada_wholesaler_pro_product_origin', [
            'id_product' => (int)$existingId,
            'source_table' => pSQL($sourceTable),
            'ean13' => pSQL(($this->normalizeEan($ean) !== '' ? $this->normalizeEan($ean) : $ean)),
            // UWAGA: w origin.reference przechowujemy SKU z hurtowni (produkt_id z RAW),
            // a niekoniecznie reference z PrestaShop (bo ta może mieć sufiks _2/_3).
            'reference' => pSQL($sku),
            'created_by_module' => 0,
            'date_add' => date('Y-m-d H:i:s'),
        ]);

        // Supplier / product_supplier – dopinamy przy linkowaniu.
        if ($idSupplier > 0) {
            try {
                $product = new Product((int)$existingId);
                if (Validate::isLoadedObject($product)) {
                    // Przy linkowaniu ustawiamy dostawcę, żeby w Presta było widać hurtownię.
                    if ((int)$product->id_supplier !== (int)$idSupplier) {
                        $product->id_supplier = (int)$idSupplier;
                        $product->update();
                    }
                }
            } catch (Exception $e) {
                // ignore
            }

            $supplierRef = ($sku !== '' ? $this->normalizeReference($sku) : '');
            $this->ensureProductSupplierLink((int)$existingId, (int)$idSupplier, $supplierRef, (float)$purchaseNet);
        }

        Tools::redirectAdmin($this->buildAdminProductEditUrl((int)$existingId));
    }

    /**
     * Wspólny "konsument" danych RAW dla:
     * - pojedynczego przycisku "Dodaj ręcznie"
     * - akcji masowej (bulk) "Utwórz produkty w PrestaShop"
     *
     * Zwraca:
     * ['id_product' => int, 'created' => bool, 'updated' => bool]
     */
    private function upsertProductFromRawIdentifiers($sourceTable, $ean, $sku)
    {
        $sourceTable = trim((string)$sourceTable);
        $ean = trim((string)$ean);
        $sku = trim((string)$sku);

        $result = [
            'id_product' => 0,
            'created' => false,
            'updated' => false,
        ];

        if ($sourceTable === '' || !isset($this->availableRawTables[$sourceTable])) {
            $this->errors[] = 'Nieprawidłowa hurtownia (source_table).';
            return $result;
        }

        if ($ean === '' && $sku === '') {
            $this->errors[] = 'Brak identyfikatora produktu (EAN i/lub SKU).';
            return $result;
        }

        // Zapewnij, że hurtownia jest zarejestrowana w module (Konfiguracja/HUB)
        // oraz że istnieje odpowiadający dostawca (Supplier) w PrestaShop.
        $wholesalerIntegration = $this->ensureWholesalerIntegrationRow($sourceTable);
        $wholesalerName = (is_array($wholesalerIntegration) && isset($wholesalerIntegration['name']))
            ? (string)$wholesalerIntegration['name']
            : $this->getWholesalerDisplayName($sourceTable);
        $idSupplier = $this->ensureSupplierIdForWholesalerName($wholesalerName);

        // BEZPIECZNIK (na czas równoległej pracy z innym modułem integracji):
        // - aktualizujemy WYŁĄCZNIE produkty "zarządzane" przez ten moduł (wpis w product_origin)
        // - produkty wykryte w PrestaShop po EAN/SKU, ale NIEPOŁĄCZONE z modułem → traktujemy jako "obce"
        //   i NIE uzupełniamy ich danych automatycznie.
        $managedId = $this->findManagedProductId($sourceTable, $ean, $sku);
        if ($managedId > 0) {
            $rawExisting = $this->fetchRawProductRow($sourceTable, $ean, $sku);
            if (is_array($rawExisting) && !empty($rawExisting)) {
                $this->updateExistingProductFromRaw((int)$managedId, $rawExisting, $sourceTable);
            }

            // Dopnij dostawcę (Supplier) dla produktów zarządzanych przez moduł (utworzone + połączone).
            if ($idSupplier > 0) {
                $supplierRef = ($sku !== '' ? $this->normalizeReference($sku) : '');
                $supplierPriceNet = 0.0;
                if (is_array($rawExisting) && isset($rawExisting['cenaporabacienetto'])) {
                    $supplierPriceNet = (float)$this->parseFloat((string)$rawExisting['cenaporabacienetto']);
                }
                $this->ensureProductSupplierLink((int)$managedId, (int)$idSupplier, $supplierRef, (float)$supplierPriceNet);
            }

            $result['id_product'] = (int)$managedId;
            $result['updated'] = true;
            return $result;
        }

        $raw = $this->fetchRawProductRow($sourceTable, $ean, $sku);
        if (!is_array($raw) || empty($raw)) {
            $this->errors[] = 'Nie znaleziono rekordu w tabeli hurtowni dla podanego EAN/SKU.';
            return $result;
        }

        $name = isset($raw['nazwa']) ? trim((string)$raw['nazwa']) : '';
        if ($name === '') {
            $this->errors[] = 'Brak nazwy produktu w RAW (kolumna: nazwa).';
            return $result;
        }

        // Podstawowe pola z RAW
        $brand = isset($raw['marka']) ? trim((string)$raw['marka']) : '';
        $rawCategory = isset($raw['kategoria']) ? (string)$raw['kategoria'] : '';
        $rawVat = isset($raw['vat']) ? (string)$raw['vat'] : '';
        $rawPriceNet = isset($raw['cenaporabacienetto']) ? (string)$raw['cenaporabacienetto'] : '';
        $rawQty = isset($raw['ilosc']) ? (string)$raw['ilosc'] : '';
        $rawUnit = isset($raw['jednostkapodstawowa']) ? trim((string)$raw['jednostkapodstawowa']) : '';
        $rawRequiredOz = isset($raw['wymagane_oz']) ? (string)$raw['wymagane_oz'] : '';
        $rawPackQty = isset($raw['ilosc_w_opakowaniu']) ? (string)$raw['ilosc_w_opakowaniu'] : '';
        $rawDescription = isset($raw['opis']) ? (string)$raw['opis'] : '';

        $descHtml = $this->normalizeDescriptionHtml($rawDescription);
        $descShort = $this->buildShortDescription($descHtml, 400);

        $priceNet = $this->parseFloat($rawPriceNet);
        $qty = $this->parseInt($rawQty);
        $vatRate = $this->parseFloat($rawVat);

        // Kategoria z mapowania (jeśli jest)
        $idCategoryDefault = 0;
        $categoryIds = [];
        $catMatch = null;
        if ($this->hasCategoryMapTable) {
            $catMatch = AzadaCategoryImportMatcher::match($sourceTable, $rawCategory);
            if (is_array($catMatch)) {
                $idCategoryDefault = isset($catMatch['id_category_default']) ? (int)$catMatch['id_category_default'] : 0;
                $categoryIds = isset($catMatch['ps_category_ids']) && is_array($catMatch['ps_category_ids']) ? $catMatch['ps_category_ids'] : [];
            }
        }

        if ($idCategoryDefault <= 0) {
            // Fallback: kategoria główna sklepu
            $idCategoryDefault = (int)Configuration::get('PS_HOME_CATEGORY');
            if ($idCategoryDefault <= 0) {
                $idCategoryDefault = 2;
            }
        }

        $categoryIds = array_values(array_unique(array_filter(array_map('intval', (array)$categoryIds), function ($v) {
            return $v > 0;
        })));
        if (empty($categoryIds)) {
            $categoryIds = [$idCategoryDefault];
        }
        if (!in_array($idCategoryDefault, $categoryIds, true)) {
            $categoryIds[] = $idCategoryDefault;
        }

        // Producent (tworzymy jeśli nie istnieje)
        $idManufacturer = 0;
            if ($brand !== '') {
                if (class_exists('AzadaManufacturerImportMatcher')) {
                    $idManufacturer = (int) AzadaManufacturerImportMatcher::resolveManufacturerId($sourceTable, $brand, (int) $this->context->shop->id);
                } else {
                    // Fallback (stara logika) – na wypadek gdyby klasa nie była dostępna
                    $idManufacturer = $this->resolveManufacturerId($brand);
                }
            }

        // Tax rules group na podstawie VAT
        $idTaxRulesGroup = $this->resolveTaxRulesGroupId($vatRate);

        // Ceny: cena zakupu (netto) = cena z hurtowni (RAW),
        // a cena detaliczna (netto) = cena zakupu * mnożnik * (1 + narzut%).
        // WAŻNE (ustalenie projektowe): jeśli narzut kategorii jest ustawiony (różny od 0),
        // to ma on pełne pierwszeństwo i POMIJA ustawienia hurtowni (mnożnik + globalny narzut).
        $purchasePriceNet = (float)$priceNet;

        $pricing = $this->getWholesalerPricingSettings($sourceTable);
        $priceMultiplier = isset($pricing['price_multiplier']) ? (float)$pricing['price_multiplier'] : 1.0;
        if ($priceMultiplier <= 0) {
            $priceMultiplier = 1.0;
        }
        $globalMarkupPercent = isset($pricing['price_markup_percent']) ? (float)$pricing['price_markup_percent'] : 0.0;

        $categoryMarkupPercent = 0.0;
        if (is_array($catMatch) && isset($catMatch['category_markup_percent'])) {
            $categoryMarkupPercent = (float)$catMatch['category_markup_percent'];
        }

        $useCategoryOverride = ($categoryMarkupPercent != 0.0);
        if ($useCategoryOverride) {
            // Tylko narzut kategorii, bez mnożnika hurtowni i bez globalnego narzutu.
            $priceMultiplier = 1.0;
            $effectiveMarkupPercent = $categoryMarkupPercent;
        } else {
            $effectiveMarkupPercent = $globalMarkupPercent;
        }

        $salePriceNet = $this->applyMarkupToPrice($purchasePriceNet * $priceMultiplier, $effectiveMarkupPercent);

        // Minimalna ilość (jeśli wymagane OŻ = min/true oraz mamy ilość w opakowaniu)
        $minimalQty = 1;
        $pack = $this->parseFloat($rawPackQty);

        // Uwaga praktyczna:
        // - Jeśli jednostka sprzedaży jest "opak" (opakowanie zbiorcze), to minimalna ilość to z reguły 1 opak,
        //   nawet jeśli hurtownia podaje "min (100)" jako ilość w opakowaniu.
        // - Jeśli jednostka jest "szt" i hurtownia wymaga OŻ=min, wtedy minimalna ilość może wynosić np. 100 szt.
        $isPackUnit = $this->isPackUnit($rawUnit);

        if ($this->isTrueLike($rawRequiredOz) && $pack > 0) {
            if ($isPackUnit) {
                $minimalQty = 1;
            } else {
                $minimalQty = (int)ceil($pack);
            }
        }
        if ($minimalQty < 1) {
            $minimalQty = 1;
        }

        // Tworzymy produkt
        $product = new Product();
        // Dla manualnego "Dodaj ręcznie" produkt ma być od razu widoczny.
        $product->active = 1;
        $product->visibility = 'both';
        $product->available_for_order = 1;
        $product->show_price = 1;
        $product->indexed = 1;
        $product->id_shop_default = (int)$this->context->shop->id;
        $product->id_tax_rules_group = (int)$idTaxRulesGroup;
        $product->id_category_default = (int)$idCategoryDefault;
        $product->wholesale_price = (float)$purchasePriceNet;
        $product->price = (float)$salePriceNet;
        $product->minimal_quantity = (int)$minimalQty;

        // Dostawca (hurtownia)
        if ($idSupplier > 0) {
            $product->id_supplier = (int)$idSupplier;
        }

        // Cena za jednostkę (unit price) – wyłączone.
        $product->unit_price_ratio = 0;
        $product->unity = '';

        if ($idManufacturer > 0) {
            $product->id_manufacturer = (int)$idManufacturer;
        }

        // EAN i SKU (walidacja)
        $eanNorm = $this->normalizeEan($ean);
        if ($eanNorm !== '') {
            $product->ean13 = $eanNorm;
        }

        // SKU/reference w PrestaShop musi być unikalny (często jest już zajęty przez produkt
        // utworzony innym modułem). W takim przypadku dokładamy sufiks _2/_3 itd.
        $skuNorm = $this->normalizeReference($sku);
        $productReference = '';
        if ($skuNorm !== '') {
            $productReference = $this->generateUniqueProductReference($skuNorm);
            if ($productReference !== '') {
                $product->reference = $productReference;
            }
        }

        // Wielojęzyczne pola
        $languages = Language::getLanguages(false);
        foreach ($languages as $lang) {
            $idLang = (int)$lang['id_lang'];
            $product->name[$idLang] = $this->truncateString($name, 255);
            $product->link_rewrite[$idLang] = Tools::link_rewrite($name);

            if ($descHtml !== '') {
                $product->description[$idLang] = $descHtml;
            }
            if ($descShort !== '') {
                $product->description_short[$idLang] = $descShort;
            }
        }

        if (!$product->add()) {
            $this->errors[] = 'Nie udało się utworzyć produktu w PrestaShop. Sprawdź walidację pól (EAN/SKU/nazwa/cena).';
            return $result;
        }

        // Dopnij dostawcę w product_supplier (żeby hurtownia była widoczna w "Dostawcy")
        if ($idSupplier > 0) {
            // supplier_reference = kod z hurtowni (RAW produkt_id)
            $supplierRef = ($sku !== '' ? $this->normalizeReference($sku) : ($productReference !== '' ? $productReference : ''));
            $this->ensureProductSupplierLink((int)$product->id, (int)$idSupplier, $supplierRef, (float)$purchasePriceNet);
        }

        // Kategorie
        if (method_exists($product, 'updateCategories')) {
            $product->updateCategories($categoryIds);
        } elseif (method_exists($product, 'addToCategories')) {
            $product->addToCategories($categoryIds);
        }

        // Stan magazynu
        if (class_exists('StockAvailable')) {
            StockAvailable::setQuantity((int)$product->id, 0, (int)$qty, (int)$this->context->shop->id);
        }

        // Zdjęcia produktu (główne + dodatkowe)
        $this->addProductImagesFromRaw((int)$product->id, $raw, $name);

        // Zapis pochodzenia produktu
        AzadaInstaller::ensureProductOriginTable();
        Db::getInstance()->execute('DELETE FROM `' . bqSQL(_DB_PREFIX_ . 'azada_wholesaler_pro_product_origin') . '` WHERE `id_product` = ' . (int)$product->id);
        Db::getInstance()->insert('azada_wholesaler_pro_product_origin', [
            'id_product' => (int)$product->id,
            'source_table' => pSQL($sourceTable),
            'ean13' => pSQL($eanNorm !== '' ? $eanNorm : $ean),
            // W origin.reference przechowujemy SKU z hurtowni (RAW produkt_id),
            // niezależnie od tego, czy reference w PrestaShop ma sufiks _2/_3.
            'reference' => pSQL($sku),
            'created_by_module' => 1,
            'date_add' => date('Y-m-d H:i:s'),
        ]);

        // Informacyjnie: jeśli brak aktywnego mapowania kategorii, pokażemy ostrzeżenie.
        if (is_array($catMatch) && isset($catMatch['is_importable']) && !$catMatch['is_importable']) {
            $reason = isset($catMatch['reason']) ? (string)$catMatch['reason'] : '';
            $this->warnings[] = 'Utworzono produkt, ale kategoria nie była importowalna (Import OFF). Powód: ' . $reason;
        }

        $result['id_product'] = (int)$product->id;
        $result['created'] = true;

        return $result;
    }

    /**
     * Akcja masowa: utwórz produkty w PrestaShop dla zaznaczonych wierszy.
     */
    public function processBulkManualCreateProducts()
    {
        if (!$this->access('add')) {
            $this->errors[] = 'Brak uprawnień do tworzenia produktów.';
            return false;
        }

        $boxKey = $this->table . 'Box';
        $ids = Tools::getValue($boxKey);
        if (!is_array($ids) || empty($ids)) {
            $this->errors[] = 'Nie wybrano żadnych pozycji na liście.';
            return false;
        }

        $ids = array_values(array_unique(array_filter(array_map('intval', $ids), function ($v) {
            return $v > 0;
        })));

        if (empty($ids)) {
            $this->errors[] = 'Nie wybrano żadnych pozycji na liście.';
            return false;
        }

        // Pobierz podstawowe dane z indeksu (żeby nie polegać na kolejności/paginacji)
        $indexTable = _DB_PREFIX_ . 'azada_raw_search_index';
        $rows = [];
        try {
            $rows = Db::getInstance()->executeS(
                'SELECT id_raw, source_table, kod_kreskowy, produkt_id '
                . 'FROM `' . bqSQL($indexTable) . '` '
                . 'WHERE id_raw IN (' . implode(',', array_map('intval', $ids)) . ')'
            );
        } catch (Exception $e) {
            $rows = [];
        }

        $byId = [];
        foreach ((array)$rows as $r) {
            if (!isset($r['id_raw'])) {
                continue;
            }
            $byId[(int)$r['id_raw']] = $r;
        }

        $created = 0;
        $updated = 0;
        $skipped = 0;
        $errors = 0;

        foreach ($ids as $idRaw) {
            if (!isset($byId[(int)$idRaw])) {
                $skipped++;
                continue;
            }

            $r = $byId[(int)$idRaw];
            $sourceTable = isset($r['source_table']) ? (string)$r['source_table'] : '';
            $ean = isset($r['kod_kreskowy']) ? (string)$r['kod_kreskowy'] : '';
            $sku = isset($r['produkt_id']) ? (string)$r['produkt_id'] : '';

            $beforeErrorsCount = count($this->errors);
            $res = $this->upsertProductFromRawIdentifiers($sourceTable, $ean, $sku);

            if (!is_array($res) || (int)$res['id_product'] <= 0) {
                // Jeżeli metoda dołożyła błąd, to policzymy jako error.
                if (count($this->errors) > $beforeErrorsCount) {
                    $errors++;
                } else {
                    $skipped++;
                }
                continue;
            }

            if (!empty($res['created'])) {
                $created++;
            } else {
                $updated++;
            }
        }

        if ($created > 0 || $updated > 0) {
            $msg = 'Akcja masowa zakończona: utworzono ' . (int)$created . ', zaktualizowano ' . (int)$updated;
            if ($skipped > 0) {
                $msg .= ', pominięto ' . (int)$skipped;
            }
            if ($errors > 0) {
                $msg .= ', błędy: ' . (int)$errors;
            }
            $this->confirmations[] = $msg;
        }

        return true;
    }

    /**
     * Zwraca ID produktu zarządzanego przez moduł (czyli mającego wpis w tabeli product_origin)
     * dla podanych identyfikatorów RAW.
     *
     * Uwaga: origin.reference przechowuje SKU z hurtowni (RAW produkt_id), a niekoniecznie reference w Presta.
     */
    private function findManagedProductId($sourceTable, $ean, $sku)
    {
        if (!$this->hasProductOriginTable) {
            // Jeśli tabela nie istnieje, to nie mamy żadnych produktów "zarządzanych".
            return 0;
        }

        $sourceTable = trim((string)$sourceTable);
        $ean = trim((string)$ean);
        $sku = trim((string)$sku);

        if ($sourceTable === '') {
            return 0;
        }

        $conds = [];
        if ($sku !== '') {
            $conds[] = "reference='" . pSQL($sku) . "'";
        }

        if ($ean !== '') {
            $eanNorm = $this->normalizeEan($ean);
            $check = ($eanNorm !== '' ? $eanNorm : $ean);
            $conds[] = "ean13='" . pSQL($check) . "'";
        }

        if (empty($conds)) {
            return 0;
        }

        $sql = "SELECT id_product FROM `" . bqSQL(_DB_PREFIX_ . 'azada_wholesaler_pro_product_origin') . "`\n"
            . "WHERE source_table='" . pSQL($sourceTable) . "' AND (" . implode(' OR ', $conds) . ")\n"
            . "ORDER BY created_by_module DESC, date_add DESC";

        try {
            $id = (int)Db::getInstance()->getValue($sql);
        } catch (Exception $e) {
            $id = 0;
        }

        if ($id > 0 && !$this->productExists($id)) {
            // Produkt nie istnieje już w PrestaShop, ale wpis origin pozostał.
            // Czyścimy go, aby moduł nie traktował pozycji jako „utworzonej”.
            try {
                Db::getInstance()->execute(
                    'DELETE FROM `' . bqSQL(_DB_PREFIX_ . 'azada_wholesaler_pro_product_origin') . '` WHERE `id_product`=' . (int)$id
                );
            } catch (Exception $e) {
                // ignore
            }
            return 0;
        }

        return (int)$id;
    }

    /**
     * Generuje unikalny reference (SKU) dla produktu w PrestaShop.
     * Jeśli reference już istnieje, dokładamy sufiks _2/_3/... aż znajdziemy wolny.
     */
    private function generateUniqueProductReference($baseReference)
    {
        $baseReference = $this->normalizeReference($baseReference);
        if ($baseReference === '') {
            return '';
        }

        $productTable = bqSQL(_DB_PREFIX_ . 'product');
        $db = Db::getInstance();

        $exists = (int)$db->getValue(
            "SELECT 1 FROM `{$productTable}` WHERE TRIM(IFNULL(reference,'')) <> '' AND reference='" . pSQL($baseReference) . "'"
        );
        if ($exists <= 0) {
            return $baseReference;
        }

        // Maks. 32 znaki reference w Presta
        for ($i = 2; $i <= 99; $i++) {
            $suffix = '_' . (string)$i;
            $maxBase = 32 - strlen($suffix);
            if ($maxBase < 1) {
                $maxBase = 1;
            }

            $candidateBase = $this->truncateString($baseReference, $maxBase);
            $candidate = $candidateBase . $suffix;

            // Dodatkowa walidacja (na wypadek, gdyby ktoś miał nietypowe znaki)
            if (class_exists('Validate') && !Validate::isReference($candidate)) {
                $candidate = preg_replace('/[^A-Za-z0-9_\-\.]/', '-', (string)$candidate);
                if ($candidate === null) {
                    continue;
                }
                $candidate = $this->truncateString($candidate, 32);
            }

            $exists = (int)$db->getValue(
                "SELECT 1 FROM `{$productTable}` WHERE TRIM(IFNULL(reference,'')) <> '' AND reference='" . pSQL($candidate) . "'"
            );
            if ($exists <= 0) {
                return $candidate;
            }
        }

        // Awaryjnie – w praktyce nie powinno się zdarzyć.
        $tail = '_' . substr((string)time(), -4);
        $maxBase = 32 - strlen($tail);
        if ($maxBase < 1) {
            $maxBase = 1;
        }
        return $this->truncateString($baseReference, $maxBase) . $tail;
    }

    private function findExistingProductId($ean, $sku)
    {
        $ean = trim((string)$ean);
        $sku = trim((string)$sku);
        $db = Db::getInstance();
        $productTable = _DB_PREFIX_ . 'product';

        // 1) Preferujemy match po SKU (reference) – jest najbliższe temu, jak moduły integracji
        //    zwykle mapują produkty (np. EKOWIT_12345, ABRO_ABC...).
        if ($sku !== '') {
            $skuNorm = $this->normalizeReference($sku);
            $check = ($skuNorm !== '' ? $skuNorm : $sku);
            $id = (int)$db->getValue(
                "SELECT MIN(id_product) FROM `" . bqSQL($productTable) . "` WHERE TRIM(IFNULL(reference,'')) <> '' AND reference='" . pSQL($check) . "'"
            );
            if ($id > 0) {
                return $id;
            }
        }

        // 2) Fallback: match po EAN.
        if ($ean !== '') {
            $eanNorm = $this->normalizeEan($ean);
            $check = ($eanNorm !== '' ? $eanNorm : $ean);
            $id = (int)$db->getValue(
                "SELECT MIN(id_product) FROM `" . bqSQL($productTable) . "` WHERE TRIM(IFNULL(ean13,'')) <> '' AND ean13='" . pSQL($check) . "'"
            );
            if ($id > 0) {
                return $id;
            }
        }

        return 0;
    }

    private function fetchRawProductRow($sourceTable, $ean, $sku)
    {
        $sourceTable = trim((string)$sourceTable);
        $ean = trim((string)$ean);
        $sku = trim((string)$sku);

        if ($sourceTable === '' || !isset($this->availableRawTables[$sourceTable])) {
            return null;
        }

        $db = Db::getInstance();
        $table = bqSQL(_DB_PREFIX_ . $sourceTable);

        // UŻYWAMY executeS ZAMIAST getRow, ABY UNIKNĄĆ BŁĘDU "LIMIT 1"
        // (getRow czasem dopina swój LIMIT, co powoduje konflikt składni w niektórych środowiskach).
        // Kolejność match: EAN+SKU -> EAN -> SKU.
        if ($ean !== '' && $sku !== '') {
            $res = $db->executeS(
                "SELECT * FROM `{$table}` WHERE `kod_kreskowy`='" . pSQL($ean) . "' AND `produkt_id`='" . pSQL($sku) . "' LIMIT 1"
            );
            if ($res && isset($res[0]) && is_array($res[0])) {
                return $res[0];
            }
        }

        if ($ean !== '') {
            $res = $db->executeS(
                "SELECT * FROM `{$table}` WHERE `kod_kreskowy`='" . pSQL($ean) . "' LIMIT 1"
            );
            if ($res && isset($res[0]) && is_array($res[0])) {
                return $res[0];
            }
        }

        if ($sku !== '') {
            $res = $db->executeS(
                "SELECT * FROM `{$table}` WHERE `produkt_id`='" . pSQL($sku) . "' LIMIT 1"
            );
            if ($res && isset($res[0]) && is_array($res[0])) {
                return $res[0];
            }
        }

        return null;
    }

    private function getWholesalerPricingSettings($sourceTable)
    {
        $sourceTable = trim((string)$sourceTable);
        static $cache = [];

        if (isset($cache[$sourceTable])) {
            return $cache[$sourceTable];
        }

        $cache[$sourceTable] = [
            'price_multiplier' => 1.0000,
            'price_markup_percent' => 0.0,
        ];

        if ($sourceTable === '') {
            return $cache[$sourceTable];
        }

        $tableIntegration = _DB_PREFIX_ . 'azada_wholesaler_pro_integration';
        $tableHub = _DB_PREFIX_ . 'azada_wholesaler_pro_hub_settings';

        try {
            $rows = Db::getInstance()->executeS(
                'SELECT hs.price_multiplier, hs.price_markup_percent '
                . 'FROM `' . bqSQL($tableIntegration) . '` w '
                . 'LEFT JOIN `' . bqSQL($tableHub) . '` hs ON (hs.id_wholesaler = w.id_wholesaler) '
                . 'WHERE w.raw_table_name=\'' . pSQL($sourceTable) . '\' '
                . 'LIMIT 1'
            );
        } catch (Exception $e) {
            $rows = [];
        }

        if (is_array($rows) && !empty($rows) && isset($rows[0]) && is_array($rows[0])) {
            if (isset($rows[0]['price_multiplier'])) {
                $cache[$sourceTable]['price_multiplier'] = (float)$rows[0]['price_multiplier'];
            }
            if (isset($rows[0]['price_markup_percent'])) {
                $cache[$sourceTable]['price_markup_percent'] = (float)$rows[0]['price_markup_percent'];
            }
        }

        return $cache[$sourceTable];
    }

    private function applyMarkupToPrice($basePriceNet, $markupPercent)
    {
        $base = (float)$basePriceNet;
        $markup = (float)$markupPercent;

        $price = $base * (1.0 + ($markup / 100.0));
        if ($price < 0) {
            $price = 0.0;
        }

        // Presta zwykle trzyma ceny z dokładnością do 6 miejsc.
        if (class_exists('Tools') && method_exists('Tools', 'ps_round')) {
            $price = (float)Tools::ps_round($price, 6);
        } else {
            $price = (float)round($price, 6);
        }

        return $price;
    }

    private function parseFloat($value)
    {
        $value = trim((string)$value);
        if ($value === '') {
            return 0.0;
        }

        $value = str_replace('%', '', $value);
        $value = str_replace(' ', '', $value);
        $value = str_replace(',', '.', $value);
        $value = preg_replace('/[^0-9\.\-]/', '', $value);
        if ($value === null || $value === '' || $value === '-' || $value === '.') {
            return 0.0;
        }

        return (float)$value;
    }

    private function parseInt($value)
    {
        $value = trim((string)$value);
        if ($value === '') {
            return 0;
        }

        $value = str_replace(' ', '', $value);
        $value = str_replace(',', '.', $value);
        $value = preg_replace('/[^0-9\.\-]/', '', $value);
        if ($value === null || $value === '' || $value === '-') {
            return 0;
        }

        return (int)floor((float)$value);
    }

    private function isTrueLike($value)
    {
        $v = strtolower(trim((string)$value));
        $v = str_replace(' ', '', $v);
        return in_array($v, ['1', 'true', 'tak', 'yes', 'min'], true);
    }

    /**
     * Czy jednostka oznacza opakowanie zbiorcze.
     *
     * W Presta pole "unity" jest używane do wyświetlania "ceny za jednostkę".
     * Dla opakowań zbiorczych chcemy pokazać np. zł/szt, więc musimy rozpoznać,
     * kiedy RAW oznacza "opak".
     */
    private function isPackUnit($unit)
    {
        $u = strtolower(trim((string)$unit));
        if ($u === '') {
            return false;
        }

        $u = str_replace([' ', '.', ','], '', $u);

        if (strpos($u, 'opak') !== false) {
            return true;
        }

        return in_array($u, ['op', 'opk', 'pak', 'pakiet', 'zestaw', 'kpl', 'komplet'], true);
    }

    private function normalizeEan($ean)
    {
        $ean = trim((string)$ean);
        if ($ean === '') {
            return '';
        }

        $ean = preg_replace('/\D+/', '', $ean);
        if ($ean === null) {
            return '';
        }

        $ean = trim($ean);
        if ($ean === '') {
            return '';
        }

        // EAN13 w Presta = dokładnie 13 cyfr.
        if (strlen($ean) !== 13) {
            return '';
        }

        if (class_exists('Validate') && !Validate::isEan13($ean)) {
            return '';
        }

        return $ean;
    }

    private function normalizeReference($ref)
    {
        $ref = trim((string)$ref);
        if ($ref === '') {
            return '';
        }

        // Presta w wielu wersjach ma limit 32 znaki.
        $ref = $this->truncateString($ref, 32);

        if (class_exists('Validate') && !Validate::isReference($ref)) {
            // Jeśli walidacja nie przechodzi, spróbujmy „zmiękczyć” znaki.
            $ref = preg_replace('/[^A-Za-z0-9_\-\.]/', '-', $ref);
            if ($ref === null) {
                return '';
            }
            $ref = $this->truncateString($ref, 32);
        }

        return trim((string)$ref);
    }

    private function truncateString($value, $max)
    {
        $value = (string)$value;
        $max = (int)$max;
        if ($max <= 0) {
            return '';
        }

        if (function_exists('mb_strlen') && function_exists('mb_substr')) {
            if (mb_strlen($value, 'UTF-8') > $max) {
                return mb_substr($value, 0, $max, 'UTF-8');
            }
            return $value;
        }

        if (strlen($value) > $max) {
            return substr($value, 0, $max);
        }
        return $value;
    }

    private function normalizeDescriptionHtml($raw)
    {
        $raw = (string)$raw;
        $raw = trim($raw);
        if ($raw === '') {
            return '';
        }

        // Często opisy potrafią być zakodowane jako encje HTML.
        $decoded = html_entity_decode($raw, ENT_QUOTES, 'UTF-8');
        $decoded = trim((string)$decoded);

        $html = $decoded;

        // Jeśli to zwykły tekst (bez tagów) – zamieniamy nowe linie na <br> i opakowujemy w <p>.
        if (strpos($decoded, '<') === false) {
            $safe = htmlspecialchars($decoded, ENT_QUOTES, 'UTF-8');
            $safe = nl2br($safe);
            $html = '<p>' . $safe . '</p>';
        }

        // Dodatkowa sanitacja HTML (jeśli dostępna w danej wersji PS)
        if (class_exists('Tools') && method_exists('Tools', 'purifyHTML')) {
            $html = Tools::purifyHTML($html);
        }

        return trim((string)$html);
    }

    private function buildShortDescription($html, $maxChars = 400)
    {
        $maxChars = (int)$maxChars;
        if ($maxChars <= 0) {
            $maxChars = 400;
        }

        $text = strip_tags((string)$html);
        $text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');
        $text = preg_replace('/\s+/u', ' ', $text);
        if ($text === null) {
            $text = '';
        }
        $text = trim((string)$text);

        if ($text === '') {
            return '';
        }

        if (function_exists('mb_strlen') && function_exists('mb_substr')) {
            if (mb_strlen($text, 'UTF-8') > $maxChars) {
                $text = rtrim(mb_substr($text, 0, $maxChars, 'UTF-8')) . '...';
            }
        } else {
            if (strlen($text) > $maxChars) {
                $text = rtrim(substr($text, 0, $maxChars)) . '...';
            }
        }

        // Short description też może być HTML – zwracamy bezpieczny <p>...</p>
        return '<p>' . htmlspecialchars($text, ENT_QUOTES, 'UTF-8') . '</p>';
    }

    private function addProductImagesFromRaw($idProduct, array $raw, $productName = '')
    {
        $idProduct = (int)$idProduct;
        if ($idProduct <= 0) {
            return 0;
        }

        $keys = ['zdjecieglownelinkurl', 'zdjecie1linkurl', 'zdjecie2linkurl', 'zdjecie3linkurl'];
        $urls = [];
        foreach ($keys as $k) {
            if (!isset($raw[$k])) {
                continue;
            }
            $val = trim((string)$raw[$k]);
            if ($val === '') {
                continue;
            }
            $url = $this->normalizeImageUrl($val);
            if ($url === '') {
                continue;
            }
            $urls[] = $url;
        }

        // Usuń duplikaty, zachowując kolejność
        $unique = [];
        $seen = [];
        foreach ($urls as $u) {
            if (isset($seen[$u])) {
                continue;
            }
            $seen[$u] = true;
            $unique[] = $u;
        }
        $urls = $unique;

        if (empty($urls)) {
            return 0;
        }

        if (!class_exists('Image') || !class_exists('ImageManager')) {
            return 0;
        }

        $languages = Language::getLanguages(false);
        $count = 0;

        foreach ($urls as $i => $url) {
            $image = new Image();
            $image->id_product = $idProduct;
            $image->position = Image::getHighestPosition($idProduct) + 1;
            $image->cover = ($i === 0) ? 1 : 0;

            // Legenda zdjęcia (multi-lang)
            foreach ($languages as $lang) {
                $idLang = (int)$lang['id_lang'];
                $legend = trim((string)$productName);
                if ($legend === '') {
                    $legend = 'Product image';
                }
                if ($i > 0) {
                    $legend .= ' #' . ($i + 1);
                }
                $image->legend[$idLang] = $this->truncateString($legend, 128);
            }

            if (!$image->add()) {
                continue;
            }

            $tmp = $this->downloadRemoteFileToTmp($url);
            if ($tmp === '') {
                $image->delete();
                continue;
            }

            $ok = true;

            // Szybka walidacja: czy to faktycznie obraz
            $imgInfo = @getimagesize($tmp);
            if (!$imgInfo) {
                $ok = false;
            }

            if ($ok) {
                $path = $image->getPathForCreation();
                // Oryginał
                if (!ImageManager::resize($tmp, $path . '.jpg')) {
                    $ok = false;
                } else {
                    // Miniatury wg ImageType
                    if (class_exists('ImageType') && method_exists('ImageType', 'getImagesTypes')) {
                        $types = ImageType::getImagesTypes('products');
                        if (is_array($types)) {
                            foreach ($types as $type) {
                                if (!isset($type['name'], $type['width'], $type['height'])) {
                                    continue;
                                }
                                ImageManager::resize(
                                    $tmp,
                                    $path . '-' . $type['name'] . '.jpg',
                                    (int)$type['width'],
                                    (int)$type['height']
                                );
                            }
                        }
                    }

                    // Watermark (jeśli włączony)
                    if (class_exists('Hook') && method_exists('Hook', 'exec')) {
                        Hook::exec('actionWatermark', [
                            'id_image' => (int)$image->id,
                            'id_product' => $idProduct,
                        ]);
                    }
                }
            }

            @unlink($tmp);

            if (!$ok) {
                // Sprzątanie: usuń rekord i ewentualne pliki
                $image->delete();
                continue;
            }

            $count++;
        }

        return $count;
    }

    private function normalizeImageUrl($url)
    {
        $url = trim((string)$url);
        if ($url === '') {
            return '';
        }

        if (strpos($url, '//') === 0) {
            $url = 'https:' . $url;
        }

        // Podstawowa walidacja protokołu
        if (!preg_match('#^https?://#i', $url)) {
            return '';
        }

        // Prosty fix na spacje w URL
        $url = str_replace(' ', '%20', $url);

        return $url;
    }

    private function downloadRemoteFileToTmp($url)
    {
        $url = $this->normalizeImageUrl($url);
        if ($url === '') {
            return '';
        }

        $tmpDir = defined('_PS_TMP_IMG_DIR_') ? _PS_TMP_IMG_DIR_ : sys_get_temp_dir();
        if (!is_dir($tmpDir) || !is_writable($tmpDir)) {
            $tmpDir = sys_get_temp_dir();
        }

        $tmpFile = tempnam($tmpDir, 'azada');
        if ($tmpFile === false) {
            return '';
        }

        $content = false;
        if (class_exists('Tools') && method_exists('Tools', 'file_get_contents')) {
            $content = Tools::file_get_contents($url);
        } else {
            $content = @file_get_contents($url);
        }

        // Fallback: cURL (gdy allow_url_fopen wyłączone)
        if ($content === false && function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; AzadaWholesalerPro/1.0)');
            curl_setopt($ch, CURLOPT_ENCODING, '');
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            $content = curl_exec($ch);
            curl_close($ch);
        }

        if ($content === false || $content === '') {
            @unlink($tmpFile);
            return '';
        }

        $written = @file_put_contents($tmpFile, $content);
        if ($written === false || $written <= 0) {
            @unlink($tmpFile);
            return '';
        }

        return $tmpFile;
    }


    private function updateExistingProductFromRaw($idProduct, array $raw, $sourceTable = '')
    {
        $idProduct = (int)$idProduct;
        if ($idProduct <= 0) {
            return false;
        }

        if (!class_exists('Product')) {
            return false;
        }

        $product = new Product($idProduct);
        if (!Validate::isLoadedObject($product)) {
            return false;
        }

        // Czy produkt był utworzony przez moduł? (potrzebne m.in. do bezpiecznych migracji)
        $createdByModule = false;
        try {
            AzadaInstaller::ensureProductOriginTable();
            $flag = (int)Db::getInstance()->getValue(
                'SELECT created_by_module FROM `' . bqSQL(_DB_PREFIX_ . 'azada_wholesaler_pro_product_origin') . '` WHERE id_product=' . (int)$idProduct
            );
            $createdByModule = ($flag === 1);
        } catch (Exception $e) {
            $createdByModule = false;
        }

        $name = isset($raw['nazwa']) ? trim((string)$raw['nazwa']) : '';
        $rawDescription = isset($raw['opis']) ? (string)$raw['opis'] : '';
        $descHtml = $this->normalizeDescriptionHtml($rawDescription);
        $descShort = $this->buildShortDescription($descHtml, 400);

        // Dane opakowań / jednostek
        $rawUnit = isset($raw['jednostkapodstawowa']) ? trim((string)$raw['jednostkapodstawowa']) : '';
        $rawPackQty = isset($raw['ilosc_w_opakowaniu']) ? (string)$raw['ilosc_w_opakowaniu'] : '';
        $pack = $this->parseFloat($rawPackQty);
        $isPackUnit = $this->isPackUnit($rawUnit);

        $languages = Language::getLanguages(false);
        $changed = false;

        foreach ($languages as $lang) {
            $idLang = (int)$lang['id_lang'];

            // Uzupełniamy tylko, jeśli puste
            $currentDesc = isset($product->description[$idLang]) ? trim((string)$product->description[$idLang]) : '';
            if ($currentDesc === '' && $descHtml !== '') {
                $product->description[$idLang] = $descHtml;
                $changed = true;
            }

            $currentShort = isset($product->description_short[$idLang]) ? trim((string)$product->description_short[$idLang]) : '';
            if ($currentShort === '' && $descShort !== '') {
                $product->description_short[$idLang] = $descShort;
                $changed = true;
            }

            // Jeśli brakuje link_rewrite, też uzupełnijmy (bezpieczne)
            $currentRewrite = isset($product->link_rewrite[$idLang]) ? trim((string)$product->link_rewrite[$idLang]) : '';
            if ($currentRewrite === '' && $name !== '') {
                $product->link_rewrite[$idLang] = Tools::link_rewrite($name);
                $changed = true;
            }
        }

        // Opakowanie zbiorcze: nie ustawiamy "ceny za sztukę" (unit price) automatycznie.
        // Jeśli wcześniejsza wersja modułu coś uzupełniła (unit_price_ratio/unity) – czyścimy to,
        // ale tylko dla produktów utworzonych przez moduł.
        if ($createdByModule) {
            $curRatio = isset($product->unit_price_ratio) ? (float)$product->unit_price_ratio : 0.0;
            $curUnity = isset($product->unity) ? trim((string)$product->unity) : '';
            if ($curRatio > 0.00001 || $curUnity !== '') {
                $product->unit_price_ratio = 0;
                $product->unity = '';
                $changed = true;
            }
        }

        // Migracja minimalnej ilości (starsza wersja mogła ustawić min=ilość w opakowaniu)
        if ($pack > 1 && $isPackUnit) {
            $legacyMin = (int)ceil($pack);
            if ((int)$product->minimal_quantity === $legacyMin) {
                $product->minimal_quantity = 1;
                $changed = true;
            }
        }
        // Jeśli produkt był utworzony przez moduł i jest OFFLINE – w manualnym trybie włączamy go.
        if ((int)$product->active === 0 && $createdByModule) {
            $product->active = 1;
            $product->visibility = 'both';
            $product->available_for_order = 1;
            $product->show_price = 1;
            $product->indexed = 1;
            $changed = true;
        }
        // Uzupełnij ceny, jeśli jeszcze nie ustawione (np. produkt utworzony starszą wersją akcji).
        $sourceTable = trim((string)$sourceTable);
        $rawCategory = isset($raw['kategoria']) ? (string)$raw['kategoria'] : '';
        $rawPriceNet = isset($raw['cenaporabacienetto']) ? (string)$raw['cenaporabacienetto'] : '';
        $costNet = $this->parseFloat($rawPriceNet);

        // Dostawca: przypisz hurtownię jako Supplier (dla produktów utworzonych przez moduł).
        $idSupplierToEnsure = 0;
        $supplierRef = '';
        if ($createdByModule && $sourceTable !== '') {
            $wholesalerIntegration = $this->ensureWholesalerIntegrationRow($sourceTable);
            $wholesalerName = (is_array($wholesalerIntegration) && isset($wholesalerIntegration['name']))
                ? (string)$wholesalerIntegration['name']
                : $this->getWholesalerDisplayName($sourceTable);
            $idSupplierToEnsure = (int)$this->ensureSupplierIdForWholesalerName($wholesalerName);

            $rawSku = isset($raw['produkt_id']) ? (string)$raw['produkt_id'] : '';
            $supplierRef = $this->normalizeReference($rawSku);

            // Jeśli produkt nie ma jeszcze dostawcy – ustawiamy.
            if ($idSupplierToEnsure > 0 && (int)$product->id_supplier <= 0) {
                $product->id_supplier = (int)$idSupplierToEnsure;
                $changed = true;
            }
        }

        if ($costNet > 0) {
            $pricing = $this->getWholesalerPricingSettings($sourceTable);
            $priceMultiplier = isset($pricing['price_multiplier']) ? (float)$pricing['price_multiplier'] : 1.0;
            if ($priceMultiplier <= 0) {
                $priceMultiplier = 1.0;
            }
            // Zachowujemy mnożnik hurtowni do wyliczeń „legacy” (dla migracji cen).
            $hubPriceMultiplier = $priceMultiplier;

            $globalMarkupPercent = isset($pricing['price_markup_percent']) ? (float)$pricing['price_markup_percent'] : 0.0;

            $categoryMarkupPercent = 0.0;
            if ($this->hasCategoryMapTable && $sourceTable !== '') {
                $catMatch = AzadaCategoryImportMatcher::match($sourceTable, $rawCategory);
                if (is_array($catMatch) && isset($catMatch['category_markup_percent'])) {
                    $categoryMarkupPercent = (float)$catMatch['category_markup_percent'];
                }
            }

            $useCategoryOverride = ($categoryMarkupPercent != 0.0);
            if ($useCategoryOverride) {
                // Tylko narzut kategorii, bez mnożnika hurtowni i bez globalnego narzutu.
                $priceMultiplier = 1.0;
                $effectiveMarkupPercent = $categoryMarkupPercent;
            } else {
                $effectiveMarkupPercent = $globalMarkupPercent;
            }

            $saleNet = $this->applyMarkupToPrice($costNet * $priceMultiplier, $effectiveMarkupPercent);

            $eps = 0.00001;
            $oldWholesale = (float)$product->wholesale_price;
            $oldPrice = (float)$product->price;

            if ($oldWholesale < $eps) {
                $product->wholesale_price = (float)$costNet;
                $changed = true;
            }

            // Aktualizujemy cenę sprzedaży tylko jeśli wygląda na nieustawioną (0) albo była równa cenie z hurtowni,
            // albo była wyliczona starszą logiką (z mnożnikiem hurtowni), żeby łatwo „przemigrować” ceny po zmianie reguł.
            $legacyEffectiveMarkupPercent = ($categoryMarkupPercent != 0.0 ? $categoryMarkupPercent : $globalMarkupPercent);
            $legacySaleNet = $this->applyMarkupToPrice($costNet * $hubPriceMultiplier, $legacyEffectiveMarkupPercent);

            $priceTolerance = 0.01; // 1 grosz – tolerancja porównania, żeby nie złapać błędu na floatach/zaokrągleniach
            if (
                $oldPrice < $eps
                || abs($oldPrice - (float)$costNet) < $eps
                || abs($oldPrice - (float)$legacySaleNet) < $priceTolerance
            ) {
                $product->price = (float)$saleNet;
                $changed = true;
            }
        }


        if ($changed) {
            $product->update();
        }

        // Dopnij wpis w product_supplier (niezależnie od $changed) – żeby dostawca był widoczny w "Dostawcy".
        if ($createdByModule && $idSupplierToEnsure > 0) {
            $supplierPriceNet = ($costNet > 0 ? (float)$costNet : (float)$product->wholesale_price);
            $this->ensureProductSupplierLink((int)$idProduct, (int)$idSupplierToEnsure, $supplierRef, (float)$supplierPriceNet);
        }

        // Zdjęcia – tylko jeśli produkt nie ma żadnych
        if (class_exists('Image') && method_exists('Image', 'getImages')) {
            $idLangDefault = (int)Configuration::get('PS_LANG_DEFAULT');
            if ($idLangDefault <= 0) {
                $idLangDefault = (int)$this->context->language->id;
            }
            $images = Image::getImages($idLangDefault, $idProduct);
            if (empty($images)) {
                $this->addProductImagesFromRaw($idProduct, $raw, $name);
            }
        }

        return true;
    }


    private function resolveManufacturerId($brand)
    {
        $brand = trim((string)$brand);
        if ($brand === '') {
            return 0;
        }

        $brand = $this->truncateString($brand, 64);
        $db = Db::getInstance();
        $id = (int)$db->getValue(
            "SELECT id_manufacturer FROM `" . bqSQL(_DB_PREFIX_ . 'manufacturer') . "` WHERE name='" . pSQL($brand) . "'"
        );
        if ($id > 0) {
            return $id;
        }

        // Tworzymy nowego producenta
        if (!class_exists('Manufacturer')) {
            return 0;
        }

        $manufacturer = new Manufacturer();
        $manufacturer->name = $brand;
        $manufacturer->active = 1;

        if ($manufacturer->add()) {
            return (int)$manufacturer->id;
        }

        return 0;
    }

    private function resolveTaxRulesGroupId($vatRate)
    {
        $rate = (float)$vatRate;
        if ($rate <= 0) {
            return 0;
        }

        $db = Db::getInstance();
        $idTax = (int)$db->getValue(
            'SELECT id_tax FROM `' . bqSQL(_DB_PREFIX_ . 'tax') . '` WHERE active=1 AND ABS(rate - ' . (float)$rate . ') < 0.01 ORDER BY id_tax ASC'
        );
        if ($idTax <= 0) {
            return 0;
        }

        $idGroup = (int)$db->getValue(
            'SELECT trg.id_tax_rules_group '
            . 'FROM `' . bqSQL(_DB_PREFIX_ . 'tax_rule') . '` tr '
            . 'INNER JOIN `' . bqSQL(_DB_PREFIX_ . 'tax_rules_group') . '` trg ON (trg.id_tax_rules_group = tr.id_tax_rules_group) '
            . 'WHERE tr.id_tax=' . (int)$idTax . ' AND trg.active=1 '
            . 'ORDER BY trg.id_tax_rules_group ASC'
        );

        return ($idGroup > 0 ? $idGroup : 0);
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
        $html .= '<input type="checkbox" name="azada_only_in_stock" value="1"'.$isInStockChecked.' style="margin-right:10px;" /> Tylko produkty dostępne na stanie';
        $html .= '</label>';
        $html .= '<label style="font-weight:600; margin:0; display:flex; align-items:center; gap:10px;">';
        $html .= '<span>Status w Presta:</span>';
        $html .= '<select name="azada_product_created" class="form-control" style="min-width:170px;">';
        $html .= '<option value="all"'.($this->productCreatedFilter === 'all' ? ' selected="selected"' : '').'>Wszystkie</option>';
        $html .= '<option value="created"'.($this->productCreatedFilter === 'created' ? ' selected="selected"' : '').'>Utworzone (wszystkie)</option>';
        $html .= '<option value="created_module"'.($this->productCreatedFilter === 'created_module' ? ' selected="selected"' : '').'>Utworzone w module</option>';
        $html .= '<option value="created_other"'.($this->productCreatedFilter === 'created_other' ? ' selected="selected"' : '').'>Utworzone poza modułem</option>';
        $html .= '<option value="linked"'.($this->productCreatedFilter === 'linked' ? ' selected="selected"' : '').'>Połączone z modułem</option>';
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
