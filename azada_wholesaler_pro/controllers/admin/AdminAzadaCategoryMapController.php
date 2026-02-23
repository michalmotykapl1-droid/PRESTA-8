<?php

require_once(dirname(__FILE__) . '/../../classes/services/AzadaInstaller.php');

class AdminAzadaCategoryMapController extends ModuleAdminController
{
    private $filterWholesaler = '';
    private $filterQuery = '';
    private $filterStatus = 'all';
    private $sortBy = 'source_category';
    private $sortDir = 'asc';
    private $page = 1;
    private $perPage = 100;

    public function __construct()
    {
        $this->bootstrap = true;
        parent::__construct();

        AzadaInstaller::ensureCategoryMapTables();

        $this->filterWholesaler = trim((string)Tools::getValue('azada_wholesaler', ''));
        $this->filterQuery = trim((string)Tools::getValue('azada_q', ''));
        $this->filterStatus = trim((string)Tools::getValue('azada_status', 'all'));
        if (!in_array($this->filterStatus, ['all', 'mapped', 'unmapped', 'import_on', 'import_off'], true)) {
            $this->filterStatus = 'all';
        }

        $allowedSortBy = ['id', 'source_category', 'default_category_name', 'source_table', 'import'];
        $this->sortBy = trim((string)Tools::getValue('azada_sort', 'source_category'));
        if (!in_array($this->sortBy, $allowedSortBy, true)) {
            $this->sortBy = 'source_category';
        }

        $this->sortDir = strtolower(trim((string)Tools::getValue('azada_dir', 'asc')));
        if (!in_array($this->sortDir, ['asc', 'desc'], true)) {
            $this->sortDir = 'asc';
        }

        $this->page = max(1, (int)Tools::getValue('azada_page', 1));
        $this->perPage = (int)Tools::getValue('azada_per_page', 100);
        if (!in_array($this->perPage, [50, 100, 200, 500], true)) {
            $this->perPage = 100;
        }
    }

    public function initContent()
    {
        parent::initContent();

        $this->postProcess();

        $content = '';
        $content .= $this->renderToolbarPanel();
        $content .= $this->renderMappingsList();

        if (Tools::getIsset('edit_mapping')) {
            $content .= $this->renderEditForm((int)Tools::getValue('edit_mapping'));
        }

        $this->context->smarty->assign('content', $content);
    }

    public function postProcess()
    {
        if (Tools::isSubmit('submitAzadaCategoryMapSync')) {
            $created = $this->syncSourceCategories();
            $this->confirmations[] = sprintf($this->l('Zsynchronizowano kategorie z hurtowni. Dodano: %d.'), (int)$created);
        }

        if (Tools::isSubmit('submitAzadaCategoryMapSave')) {
            $this->saveMapping();
        }
    }

    private function renderToolbarPanel()
    {
        $url = self::$currentIndex . '&token=' . $this->token;

        $html = '<div class="panel">';
        $html .= '<h3><i class="icon-sitemap"></i> ' . $this->l('Mapowanie kategorii') . '</h3>';
        $html .= '<p class="help-block" style="margin-bottom:12px;">' . $this->l('Przypisz kategorię hurtowni do kategorii sklepu (drzewo PrestaShop).') . '</p>';
        $html .= '<form method="post" action="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '" style="display:inline-block; margin-right:10px;">';
        $html .= '<button type="submit" name="submitAzadaCategoryMapSync" class="btn btn-primary">';
        $html .= '<i class="icon-download"></i> ' . $this->l('Pobierz / odśwież kategorie z hurtowni');
        $html .= '</button>';
        $html .= '</form>';
        $html .= '</div>';

        return $html;
    }

    private function renderMappingsList()
    {
        $result = $this->getMappingsResult();
        $rows = $result['rows'];
        $total = (int)$result['total'];
        $totalPages = (int)$result['total_pages'];

        $html = '<div class="panel">';
        $html .= '<h3><i class="icon-list"></i> ' . $this->l('Mapa kategorii') . ' <span class="badge">' . $total . '</span></h3>';
        $html .= $this->renderFiltersForm();

        if (empty($rows)) {
            $html .= '<div class="alert alert-info">' . $this->l('Brak pozycji dla wybranych filtrów.') . '</div>';
            $html .= '</div>';
            return $html;
        }

        $html .= '<div class="table-responsive-row clearfix">';
        $html .= '<table class="table">';
        $html .= '<thead><tr>';
        $html .= '<th style="width:70px;">ID</th>';
        $html .= '<th>' . $this->l('Kategoria hurtowni') . '</th>';
        $html .= '<th style="width:220px;">' . $this->l('Kategoria w sklepie') . '</th>';
        $html .= '<th style="width:190px;">' . $this->l('Nazwa hurtowni') . '</th>';
        $html .= '<th style="width:90px; text-align:center;">' . $this->l('Import') . '</th>';
        $html .= '<th style="width:170px; text-align:center;">' . $this->l('Akcja') . '</th>';
        $html .= '</tr></thead><tbody>';

        foreach ($rows as $row) {
            $id = (int)$row['id_category_map'];
            $editUrl = $this->buildFilterAwareUrl(['edit_mapping' => $id]);
            $isMapped = ((int)$row['id_category_default'] > 0);
            $mappedName = $isMapped ? (string)$row['default_category_name'] : '--';
            $isImportEnabled = ((int)$row['is_active'] === 1) && $isMapped;
            $statusHtml = $isImportEnabled
                ? '<span style="color:#72C279; font-weight:700;">✓</span>'
                : '<span style="color:#D9534F; font-weight:700;">✕</span>';

            $html .= '<tr>';
            $html .= '<td>' . $id . '</td>';
            $html .= '<td>' . htmlspecialchars($row['source_category'], ENT_QUOTES, 'UTF-8') . '</td>';
            $html .= '<td>' . htmlspecialchars($mappedName, ENT_QUOTES, 'UTF-8') . '</td>';
            $html .= '<td>' . htmlspecialchars($this->prettyWholesalerName($row['source_table']), ENT_QUOTES, 'UTF-8') . '</td>';
            $html .= '<td style="text-align:center;">' . $statusHtml . '</td>';
            $html .= '<td style="text-align:center;">';
            $html .= '<a class="btn btn-default" href="' . htmlspecialchars($editUrl, ENT_QUOTES, 'UTF-8') . '">';
            $html .= '<i class="icon-pencil"></i> ' . $this->l('Edytuj');
            $html .= '</a>';
            $html .= '</td>';
            $html .= '</tr>';
        }

        $html .= '</tbody></table></div>';

        if ($totalPages > 1) {
            $html .= $this->renderPagination($totalPages);
        }

        $html .= '</div>';

        return $html;
    }

    private function renderFiltersForm()
    {
        $wholesalers = $this->getWholesalerOptions();

        $html = '<form method="get" action="' . htmlspecialchars(self::$currentIndex, ENT_QUOTES, 'UTF-8') . '" class="form-inline" style="margin:0 0 12px;">';
        $html .= '<input type="hidden" name="controller" value="' . htmlspecialchars($this->controller_name, ENT_QUOTES, 'UTF-8') . '" />';
        $html .= '<input type="hidden" name="token" value="' . htmlspecialchars($this->token, ENT_QUOTES, 'UTF-8') . '" />';

        $html .= '<div class="form-group" style="margin-right:8px;">';
        $html .= '<input type="text" class="form-control" name="azada_q" value="' . htmlspecialchars($this->filterQuery, ENT_QUOTES, 'UTF-8') . '" placeholder="Szukaj kategorii hurtowni..." style="min-width:270px;" />';
        $html .= '</div>';

        $html .= '<div class="form-group" style="margin-right:8px;">';
        $html .= '<select name="azada_wholesaler" class="form-control">';
        $html .= '<option value="">' . $this->l('Wszystkie hurtownie') . '</option>';
        foreach ($wholesalers as $sourceTable => $label) {
            $selected = ($this->filterWholesaler === $sourceTable) ? ' selected="selected"' : '';
            $html .= '<option value="' . htmlspecialchars($sourceTable, ENT_QUOTES, 'UTF-8') . '"' . $selected . '>' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</option>';
        }
        $html .= '</select>';
        $html .= '</div>';

        $html .= '<div class="form-group" style="margin-right:8px;">';
        $html .= '<select name="azada_status" class="form-control">';
        $statusOptions = [
            'all' => $this->l('Wszystkie statusy'),
            'mapped' => $this->l('Tylko przypisane'),
            'unmapped' => $this->l('Tylko nieprzypisane'),
            'import_on' => $this->l('Import ON'),
            'import_off' => $this->l('Import OFF'),
        ];
        foreach ($statusOptions as $value => $label) {
            $selected = ($this->filterStatus === $value) ? ' selected="selected"' : '';
            $html .= '<option value="' . $value . '"' . $selected . '>' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</option>';
        }
        $html .= '</select>';
        $html .= '</div>';

        $html .= '<div class="form-group" style="margin-right:8px;">';
        $html .= '<select name="azada_sort" class="form-control">';
        $sortOptions = [
            'source_category' => $this->l('Sortuj: kategoria hurtowni'),
            'source_table' => $this->l('Sortuj: hurtownia'),
            'default_category_name' => $this->l('Sortuj: kategoria sklepu'),
            'id' => $this->l('Sortuj: ID'),
            'import' => $this->l('Sortuj: import'),
        ];
        foreach ($sortOptions as $value => $label) {
            $selected = ($this->sortBy === $value) ? ' selected="selected"' : '';
            $html .= '<option value="' . $value . '"' . $selected . '>' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</option>';
        }
        $html .= '</select>';
        $html .= '</div>';

        $html .= '<div class="form-group" style="margin-right:8px;">';
        $html .= '<select name="azada_dir" class="form-control">';
        $html .= '<option value="asc"' . ($this->sortDir === 'asc' ? ' selected="selected"' : '') . '>' . $this->l('Rosnąco') . '</option>';
        $html .= '<option value="desc"' . ($this->sortDir === 'desc' ? ' selected="selected"' : '') . '>' . $this->l('Malejąco') . '</option>';
        $html .= '</select>';
        $html .= '</div>';

        $html .= '<div class="form-group" style="margin-right:8px;">';
        $html .= '<select name="azada_per_page" class="form-control">';
        foreach ([50, 100, 200, 500] as $size) {
            $selected = ($this->perPage === $size) ? ' selected="selected"' : '';
            $html .= '<option value="' . (int)$size . '"' . $selected . '>' . (int)$size . ' / strona</option>';
        }
        $html .= '</select>';
        $html .= '</div>';

        $html .= '<button type="submit" class="btn btn-primary" style="margin-right:6px;"><i class="icon-search"></i> ' . $this->l('Szukaj') . '</button>';
        $html .= '<a href="' . htmlspecialchars(self::$currentIndex . '&token=' . $this->token, ENT_QUOTES, 'UTF-8') . '" class="btn btn-default"><i class="icon-eraser"></i> ' . $this->l('Wyczyść') . '</a>';

        $html .= '</form>';

        return $html;
    }

    private function renderPagination($totalPages)
    {
        $html = '<div class="pagination" style="margin:10px 0 0;">';

        $prevPage = max(1, $this->page - 1);
        $nextPage = min($totalPages, $this->page + 1);

        $html .= '<a class="btn btn-default btn-sm" ' . ($this->page <= 1 ? 'disabled="disabled"' : '') . ' href="' . htmlspecialchars($this->buildFilterAwareUrl(['azada_page' => $prevPage]), ENT_QUOTES, 'UTF-8') . '">&laquo;</a> ';

        $start = max(1, $this->page - 3);
        $end = min($totalPages, $this->page + 3);

        for ($i = $start; $i <= $end; $i++) {
            if ($i === $this->page) {
                $html .= '<span class="btn btn-primary btn-sm" style="margin:0 2px;">' . $i . '</span>';
            } else {
                $html .= '<a class="btn btn-default btn-sm" style="margin:0 2px;" href="' . htmlspecialchars($this->buildFilterAwareUrl(['azada_page' => $i]), ENT_QUOTES, 'UTF-8') . '">' . $i . '</a>';
            }
        }

        $html .= ' <a class="btn btn-default btn-sm" ' . ($this->page >= $totalPages ? 'disabled="disabled"' : '') . ' href="' . htmlspecialchars($this->buildFilterAwareUrl(['azada_page' => $nextPage]), ENT_QUOTES, 'UTF-8') . '">&raquo;</a>';
        $html .= '</div>';

        return $html;
    }

    private function buildFilterAwareUrl(array $extra = [])
    {
        $params = [
            'token' => $this->token,
            'azada_q' => $this->filterQuery,
            'azada_wholesaler' => $this->filterWholesaler,
            'azada_status' => $this->filterStatus,
            'azada_sort' => $this->sortBy,
            'azada_dir' => $this->sortDir,
            'azada_per_page' => $this->perPage,
            'azada_page' => $this->page,
        ];

        foreach ($extra as $k => $v) {
            $params[$k] = $v;
        }

        return self::$currentIndex . '&' . http_build_query($params);
    }

    private function getWholesalerOptions()
    {
        $rows = Db::getInstance()->executeS('SELECT DISTINCT source_table FROM `' . _DB_PREFIX_ . 'azada_wholesaler_pro_category_map` ORDER BY source_table ASC');
        if (!is_array($rows)) {
            return [];
        }

        $options = [];
        foreach ($rows as $row) {
            $sourceTable = isset($row['source_table']) ? trim((string)$row['source_table']) : '';
            if ($sourceTable === '') {
                continue;
            }

            $options[$sourceTable] = $this->prettyWholesalerName($sourceTable);
        }

        return $options;
    }

    private function getMappingsResult()
    {
        $idLang = (int)$this->context->language->id;

        $where = [];
        if ($this->filterWholesaler !== '') {
            $where[] = "m.source_table = '" . pSQL($this->filterWholesaler) . "'";
        }

        if ($this->filterQuery !== '') {
            $q = pSQL($this->filterQuery);
            $where[] = "m.source_category LIKE '%" . $q . "%'";
        }

        if ($this->filterStatus === 'mapped') {
            $where[] = 'm.id_category_default > 0';
        } elseif ($this->filterStatus === 'unmapped') {
            $where[] = 'm.id_category_default = 0';
        } elseif ($this->filterStatus === 'import_on') {
            $where[] = 'm.id_category_default > 0';
            $where[] = 'm.is_active = 1';
        } elseif ($this->filterStatus === 'import_off') {
            $where[] = '(m.id_category_default = 0 OR m.is_active = 0)';
        }

        $whereSql = '';
        if (!empty($where)) {
            $whereSql = 'WHERE ' . implode(' AND ', $where);
        }

        $sortMap = [
            'id' => 'm.id_category_map',
            'source_category' => 'm.source_category',
            'default_category_name' => 'cl.name',
            'source_table' => 'm.source_table',
            'import' => 'm.is_active',
        ];

        $orderBy = isset($sortMap[$this->sortBy]) ? $sortMap[$this->sortBy] : 'm.source_category';
        $orderDir = strtoupper($this->sortDir) === 'DESC' ? 'DESC' : 'ASC';

        $countSql = 'SELECT COUNT(*)
                     FROM `' . _DB_PREFIX_ . 'azada_wholesaler_pro_category_map` m
                     LEFT JOIN `' . _DB_PREFIX_ . 'category_lang` cl
                        ON cl.id_category = m.id_category_default AND cl.id_lang = ' . (int)$idLang . '
                     ' . $whereSql;

        $total = (int)Db::getInstance()->getValue($countSql);
        $totalPages = max(1, (int)ceil($total / max(1, $this->perPage)));
        if ($this->page > $totalPages) {
            $this->page = $totalPages;
        }

        $offset = ($this->page - 1) * $this->perPage;

        $sql = 'SELECT m.id_category_map, m.source_table, m.source_category, m.id_category_default, m.is_active, cl.name AS default_category_name
                FROM `' . _DB_PREFIX_ . 'azada_wholesaler_pro_category_map` m
                LEFT JOIN `' . _DB_PREFIX_ . 'category_lang` cl
                    ON cl.id_category = m.id_category_default AND cl.id_lang = ' . (int)$idLang . '
                ' . $whereSql . '
                ORDER BY ' . $orderBy . ' ' . $orderDir . '
                LIMIT ' . (int)$offset . ', ' . (int)$this->perPage;

        $rows = Db::getInstance()->executeS($sql);
        if (!is_array($rows)) {
            $rows = [];
        }

        foreach ($rows as &$row) {
            if (!isset($row['default_category_name']) || trim((string)$row['default_category_name']) === '') {
                $row['default_category_name'] = '-';
            }
        }

        return [
            'rows' => $rows,
            'total' => $total,
            'total_pages' => $totalPages,
        ];
    }

    private function renderEditForm($idMapping)
    {
        $row = $this->getMappingById($idMapping);
        if (!$row) {
            return '<div class="alert alert-danger">' . $this->l('Nie znaleziono mapowania.') . '</div>';
        }

        $selectedCategories = $this->decodeCategoryIds($row['ps_category_ids']);
        $idDefault = (int)$row['id_category_default'];

        $tree = new HelperTreeCategories('azada_category_map_tree_' . $idMapping);
        $tree->setRootCategory((int)Category::getRootCategory()->id)
            ->setUseCheckBox(true)
            ->setUseSearch(true)
            ->setUseToolbar(true)
            ->setSelectedCategories($selectedCategories)
            ->setInputName('ps_categories[]');

        $treeHtml = $tree->render();

        $categoryOptions = '<option value="0">-</option>';
        foreach ($this->getAllCategoriesForSelect() as $category) {
            $selected = ((int)$category['id_category'] === $idDefault) ? ' selected="selected"' : '';
            $categoryOptions .= '<option value="' . (int)$category['id_category'] . '"' . $selected . '>'
                . htmlspecialchars($category['name_with_path'], ENT_QUOTES, 'UTF-8')
                . '</option>';
        }

        $actionUrl = $this->buildFilterAwareUrl(['edit_mapping' => (int)$idMapping]);

        $html = '<div class="panel">';
        $html .= '<h3><i class="icon-edit"></i> ' . $this->l('Edycja mapowania kategorii') . '</h3>';
        $html .= '<form method="post" action="' . htmlspecialchars($actionUrl, ENT_QUOTES, 'UTF-8') . '">';
        $html .= '<input type="hidden" name="id_category_map" value="' . (int)$idMapping . '" />';

        $html .= '<div class="form-group">';
        $html .= '<label>' . $this->l('Kategoria hurtowni') . '</label>';
        $html .= '<input type="text" class="form-control" disabled value="' . htmlspecialchars($row['source_category'], ENT_QUOTES, 'UTF-8') . '" />';
        $html .= '</div>';

        $html .= '<div class="form-group">';
        $html .= '<label>' . $this->l('Hurtownia') . '</label>';
        $html .= '<input type="text" class="form-control" disabled value="' . htmlspecialchars($this->prettyWholesalerName($row['source_table']), ENT_QUOTES, 'UTF-8') . '" />';
        $html .= '</div>';

        $html .= '<div class="form-group">';
        $html .= '<label>' . $this->l('Kategorie w sklepie (drzewo)') . '</label>';
        $html .= '<div style="max-height:430px; overflow:auto; border:1px solid #d3d8db; padding:10px; background:#fff;">' . $treeHtml . '</div>';
        $html .= '<p class="help-block">' . $this->l('Możesz zaznaczyć wiele kategorii sklepu.') . '</p>';
        $html .= '</div>';

        $html .= '<div class="form-group">';
        $html .= '<label>' . $this->l('Kategoria domyślna') . '</label>';
        $html .= '<select name="id_category_default" class="form-control">' . $categoryOptions . '</select>';
        $html .= '</div>';

        $isEnabled = (int)$row['is_active'] === 1;
        $html .= '<div class="form-group">';
        $html .= '<label class="control-label">' . $this->l('Import aktywny') . '</label>';
        $html .= '<div class="switch prestashop-switch fixed-width-lg">';
        $html .= '<input type="radio" name="is_active" id="is_active_on" value="1" ' . ($isEnabled ? 'checked="checked"' : '') . ' />';
        $html .= '<label for="is_active_on">' . $this->l('Tak') . '</label>';
        $html .= '<input type="radio" name="is_active" id="is_active_off" value="0" ' . (!$isEnabled ? 'checked="checked"' : '') . ' />';
        $html .= '<label for="is_active_off">' . $this->l('Nie') . '</label>';
        $html .= '<a class="slide-button btn"></a>';
        $html .= '</div>';
        $html .= '</div>';

        $html .= '<div class="panel-footer">';
        $html .= '<button type="submit" name="submitAzadaCategoryMapSave" class="btn btn-primary pull-right">';
        $html .= '<i class="process-icon-save"></i> ' . $this->l('Zapisz');
        $html .= '</button>';
        $html .= '<a href="' . htmlspecialchars($this->buildFilterAwareUrl(), ENT_QUOTES, 'UTF-8') . '" class="btn btn-default">';
        $html .= '<i class="process-icon-cancel"></i> ' . $this->l('Anuluj');
        $html .= '</a>';
        $html .= '</div>';

        $html .= '</form>';
        $html .= '</div>';

        return $html;
    }

    private function saveMapping()
    {
        $id = (int)Tools::getValue('id_category_map');
        if ($id <= 0) {
            $this->errors[] = $this->l('Nieprawidłowe ID mapowania.');
            return;
        }

        $selected = Tools::getValue('ps_categories', []);
        if (!is_array($selected)) {
            $selected = [];
        }

        $selectedClean = [];
        foreach ($selected as $categoryId) {
            $categoryId = (int)$categoryId;
            if ($categoryId > 0) {
                $selectedClean[$categoryId] = $categoryId;
            }
        }
        $selectedClean = array_values($selectedClean);

        $idDefault = (int)Tools::getValue('id_category_default', 0);
        $isActive = (int)Tools::getValue('is_active', 1) === 1 ? 1 : 0;

        if ($idDefault > 0 && !in_array($idDefault, $selectedClean, true)) {
            $selectedClean[] = $idDefault;
        }

        if (empty($selectedClean)) {
            $idDefault = 0;
            $isActive = 0;
        } elseif ($idDefault <= 0) {
            $idDefault = (int)$selectedClean[0];
        }

        $ok = Db::getInstance()->update('azada_wholesaler_pro_category_map', [
            'ps_category_ids' => pSQL(json_encode($selectedClean)),
            'id_category_default' => (int)$idDefault,
            'is_active' => (int)$isActive,
            'date_upd' => date('Y-m-d H:i:s'),
        ], 'id_category_map=' . (int)$id);

        if ($ok) {
            $this->confirmations[] = $this->l('Zapisano mapowanie kategorii.');
        } else {
            $this->errors[] = $this->l('Nie udało się zapisać mapowania kategorii.');
        }
    }

    private function getMappingById($idMapping)
    {
        $sql = 'SELECT * FROM `' . _DB_PREFIX_ . 'azada_wholesaler_pro_category_map` WHERE id_category_map=' . (int)$idMapping;
        $row = Db::getInstance()->getRow($sql);

        return is_array($row) ? $row : null;
    }

    private function decodeCategoryIds($raw)
    {
        $raw = trim((string)$raw);
        if ($raw === '') {
            return [];
        }

        $data = json_decode($raw, true);
        if (!is_array($data)) {
            return [];
        }

        $ids = [];
        foreach ($data as $idCategory) {
            $idCategory = (int)$idCategory;
            if ($idCategory > 0) {
                $ids[] = $idCategory;
            }
        }

        return $ids;
    }

    private function getAllCategoriesForSelect()
    {
        $idLang = (int)$this->context->language->id;

        $sql = 'SELECT c.id_category, c.level_depth, cl.name
                FROM `' . _DB_PREFIX_ . 'category` c
                INNER JOIN `' . _DB_PREFIX_ . 'category_lang` cl
                    ON cl.id_category = c.id_category AND cl.id_lang = ' . (int)$idLang . '
                ORDER BY c.nleft ASC';

        $rows = Db::getInstance()->executeS($sql);
        if (!is_array($rows)) {
            return [];
        }

        $result = [];
        foreach ($rows as $row) {
            $depth = max(0, ((int)$row['level_depth']) - 1);
            $prefix = str_repeat('— ', $depth);
            $result[] = [
                'id_category' => (int)$row['id_category'],
                'name_with_path' => $prefix . (string)$row['name'],
            ];
        }

        return $result;
    }

    private function syncSourceCategories()
    {
        $db = Db::getInstance();
        $rowsAdded = 0;

        $tables = $db->executeS("SHOW TABLES LIKE '" . pSQL(_DB_PREFIX_) . "azada_raw_%'");
        if (!is_array($tables)) {
            return 0;
        }

        foreach ($tables as $tableRow) {
            $fullTable = (string)reset($tableRow);
            $sourceTable = preg_replace('/^' . preg_quote(_DB_PREFIX_, '/') . '/', '', $fullTable);

            if ($sourceTable === 'azada_raw_search_index' || preg_match('/_(source|conversion)$/', $sourceTable)) {
                continue;
            }

            $hasCategory = (bool)$db->getValue("SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME='" . pSQL($fullTable) . "' AND COLUMN_NAME='kategoria'");
            if (!$hasCategory) {
                continue;
            }

            $hasVat = (bool)$db->getValue("SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME='" . pSQL($fullTable) . "' AND COLUMN_NAME='vat'");

            $categoriesSql = "SELECT DISTINCT TRIM(`kategoria`) AS category_name FROM `" . bqSQL($fullTable) . "` WHERE TRIM(IFNULL(`kategoria`, '')) <> ''";
            if ($hasVat) {
                $categoriesSql .= " AND CAST(REPLACE(REPLACE(TRIM(`vat`), '%', ''), ',', '.') AS DECIMAL(10,2)) IN (5, 8, 23)";
            }

            $categories = $db->executeS($categoriesSql);
            if (!is_array($categories)) {
                continue;
            }

            foreach ($categories as $categoryRow) {
                $sourceCategory = $this->normalizeSourceCategory(isset($categoryRow['category_name']) ? (string)$categoryRow['category_name'] : '');
                if ($sourceCategory === '') {
                    continue;
                }

                $exists = (int)$db->getValue(
                    "SELECT id_category_map FROM `" . _DB_PREFIX_ . "azada_wholesaler_pro_category_map` WHERE source_table='" . pSQL($sourceTable) . "' AND source_category='" . pSQL($sourceCategory) . "'"
                );

                if ($exists > 0) {
                    continue;
                }

                $ok = $db->insert('azada_wholesaler_pro_category_map', [
                    'source_table' => pSQL($sourceTable),
                    'source_category' => pSQL($sourceCategory),
                    'ps_category_ids' => pSQL('[]'),
                    'id_category_default' => 0,
                    'is_active' => 0,
                    'date_add' => date('Y-m-d H:i:s'),
                    'date_upd' => date('Y-m-d H:i:s'),
                ]);

                if ($ok) {
                    $rowsAdded++;
                }
            }
        }

        return $rowsAdded;
    }


    private function normalizeSourceCategory($raw)
    {
        $value = trim((string)$raw);
        if ($value === '') {
            return '';
        }

        $value = preg_replace('/\s+/u', ' ', $value);
        if ($value === null) {
            return '';
        }

        // Odrzucamy oczywiste śmieci: same liczby / znaki oraz wartości wyglądające na pola adresowe.
        $onlyNumericLike = preg_match('/^[0-9\s,\.-]+$/u', $value);
        if ($onlyNumericLike) {
            return '';
        }

        if (preg_match('/\b(ul\.|street|hamburg|gmbh)\b/i', $value)) {
            return '';
        }

        if (mb_strlen($value, 'UTF-8') < 3) {
            return '';
        }

        return $value;
    }

    private function prettyWholesalerName($sourceTable)
    {
        $name = str_replace('azada_raw_', '', (string)$sourceTable);
        if ($name === '') {
            return '-';
        }

        return ucfirst($name);
    }
}
