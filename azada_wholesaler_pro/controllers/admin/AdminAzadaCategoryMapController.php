<?php

require_once(dirname(__FILE__) . '/../../classes/services/AzadaInstaller.php');
require_once(dirname(__FILE__) . '/../../classes/services/AzadaCategoryMapEditModalRenderer.php');

class AdminAzadaCategoryMapController extends ModuleAdminController
{
    private $filterWholesaler = '';
    private $filterQuery = '';
    private $filterStatus = 'all';
    private $sortBy = 'source_category';
    private $sortDir = 'asc';
    private $page = 1;
    private $perPage = 100;

    private $producerFilterWholesaler = '';
    private $producerFilterQuery = '';
    private $producerSortBy = 'source_category';
    private $producerSortDir = 'asc';
    private $producerPage = 1;
    private $producerPerPage = 100;

    private $activeTab = 'categories';

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

        $this->producerFilterWholesaler = trim((string)Tools::getValue('azada_p_wholesaler', $this->filterWholesaler));
        $this->producerFilterQuery = trim((string)Tools::getValue('azada_p_q', ''));

        $allowedProducerSortBy = ['id', 'source_category', 'source_table'];
        $this->producerSortBy = trim((string)Tools::getValue('azada_p_sort', 'source_category'));
        if (!in_array($this->producerSortBy, $allowedProducerSortBy, true)) {
            $this->producerSortBy = 'source_category';
        }

        $this->producerSortDir = strtolower(trim((string)Tools::getValue('azada_p_dir', 'asc')));
        if (!in_array($this->producerSortDir, ['asc', 'desc'], true)) {
            $this->producerSortDir = 'asc';
        }

        $this->producerPage = max(1, (int)Tools::getValue('azada_p_page', 1));
        $this->producerPerPage = (int)Tools::getValue('azada_p_per_page', 100);
        if (!in_array($this->producerPerPage, [50, 100, 200, 500], true)) {
            $this->producerPerPage = 100;
        }

        $this->activeTab = trim((string)Tools::getValue('azada_tab', 'categories'));
        if (!in_array($this->activeTab, ['categories', 'producers'], true)) {
            $this->activeTab = 'categories';
        }
    }

    public function initContent()
    {
        if ($this->isEditModalAjaxRequest()) {
            $this->ajaxRenderEditModal();
            return;
        }

        parent::initContent();

        $this->postProcess();

        $content = '';
        $content .= $this->renderToolbarPanel();
        $content .= $this->renderMappingsList();

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

    private function isEditModalAjaxRequest()
    {
        return (bool)Tools::getValue('ajax') && Tools::getValue('action') === 'getEditModal';
    }

    private function ajaxRenderEditModal()
    {
        $idMapping = (int)Tools::getValue('id_category_map', 0);
        if ($idMapping <= 0) {
            $idMapping = (int)Tools::getValue('edit_mapping', 0);
        }

        if ($idMapping <= 0) {
            die('<div class="alert alert-danger">' . $this->l('Nieprawidłowe ID mapowania.') . '</div>');
        }

        die($this->renderEditModalContent($idMapping));
    }

    public function ajaxProcessGetEditModal()
    {
        $this->ajaxRenderEditModal();
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

        $categoriesHtml = '<div class="panel">';
        $categoriesHtml .= '<h3><i class="icon-list"></i> ' . $this->l('Mapa kategorii') . ' <span class="badge">' . $total . '</span></h3>';
        $categoriesHtml .= $this->renderFiltersForm();

        if (empty($rows)) {
            $categoriesHtml .= '<div class="alert alert-info">' . $this->l('Brak pozycji dla wybranych filtrów.') . '</div>';
        } else {
            $categoriesHtml .= '<div class="table-responsive-row clearfix">';
            $categoriesHtml .= '<table class="table">';
            $categoriesHtml .= '<thead><tr>';
            $categoriesHtml .= $this->renderSortableHeader($this->l('ID'), 'id', 'width:70px;');
            $categoriesHtml .= $this->renderSortableHeader($this->l('Kategoria hurtowni'), 'source_category');
            $categoriesHtml .= $this->renderSortableHeader($this->l('Kategoria w sklepie'), 'default_category_name', 'width:220px;');
            $categoriesHtml .= $this->renderSortableHeader($this->l('Nazwa hurtowni'), 'source_table', 'width:190px;');
            $categoriesHtml .= $this->renderSortableHeader($this->l('Import'), 'import', 'width:90px; text-align:center;');
            $categoriesHtml .= '<th style="width:170px; text-align:center;">' . $this->l('Akcja') . '</th>';
            $categoriesHtml .= '</tr></thead><tbody>';

            foreach ($rows as $row) {
                $id = (int)$row['id_category_map'];
                $editUrl = $this->buildFilterAwareUrl(['edit_mapping' => $id, 'azada_tab' => 'categories']);
                $isMapped = ((int)$row['id_category_default'] > 0);
                $mappedName = $isMapped ? (string)$row['default_category_name'] : '--';
                $isImportEnabled = ((int)$row['is_active'] === 1) && $isMapped;
                $statusHtml = $isImportEnabled
                    ? '<span style="color:#72C279; font-weight:700;">✓</span>'
                    : '<span style="color:#D9534F; font-weight:700;">✕</span>';

                $categoriesHtml .= '<tr>';
                $categoriesHtml .= '<td>' . $id . '</td>';
                $categoriesHtml .= '<td>' . htmlspecialchars($row['source_category'], ENT_QUOTES, 'UTF-8') . '</td>';
                $categoriesHtml .= '<td>' . htmlspecialchars($mappedName, ENT_QUOTES, 'UTF-8') . '</td>';
                $categoriesHtml .= '<td>' . htmlspecialchars($this->prettyWholesalerName($row['source_table']), ENT_QUOTES, 'UTF-8') . '</td>';
                $categoriesHtml .= '<td style="text-align:center;">' . $statusHtml . '</td>';
                $categoriesHtml .= '<td style="text-align:center;">';
                $categoriesHtml .= '<a class="btn btn-default js-azada-edit" href="' . htmlspecialchars($editUrl, ENT_QUOTES, 'UTF-8') . '" data-edit-url="' . htmlspecialchars($editUrl, ENT_QUOTES, 'UTF-8') . '">';
                $categoriesHtml .= '<i class="icon-pencil"></i> ' . $this->l('Edytuj');
                $categoriesHtml .= '</a>';
                $categoriesHtml .= '</td>';
                $categoriesHtml .= '</tr>';
            }

            $categoriesHtml .= '</tbody></table></div>';

            if ($totalPages > 1) {
                $categoriesHtml .= $this->renderPagination($totalPages);
            }
        }

        $categoriesHtml .= '</div>';
        $producersHtml = $this->renderProducersPanel();

        $categoriesActive = $this->activeTab === 'categories' ? ' active' : '';
        $producersActive = $this->activeTab === 'producers' ? ' active' : '';
        $categoriesPaneActive = $this->activeTab === 'categories' ? ' tab-pane active' : ' tab-pane';
        $producersPaneActive = $this->activeTab === 'producers' ? ' tab-pane active' : ' tab-pane';

        $html = '<div class="panel">';
        $html .= '<ul class="nav nav-tabs" style="margin-bottom:12px;">';
        $html .= '<li class="' . trim($categoriesActive) . '"><a href="' . htmlspecialchars($this->buildFilterAwareUrl(['azada_tab' => 'categories']), ENT_QUOTES, 'UTF-8') . '">' . $this->l('MAPA KATEGORII') . '</a></li>';
        $html .= '<li class="' . trim($producersActive) . '"><a href="' . htmlspecialchars($this->buildProducerFilterAwareUrl(['azada_tab' => 'producers']), ENT_QUOTES, 'UTF-8') . '">' . $this->l('PRODUCENCI') . '</a></li>';
        $html .= '</ul>';

        $html .= '<div class="tab-content">';
        $html .= '<div class="' . $categoriesPaneActive . '">' . $categoriesHtml . '</div>';
        $html .= '<div class="' . $producersPaneActive . '">' . $producersHtml . '</div>';
        $html .= '</div>';
        $html .= '</div>';

        $html .= $this->renderEditModalShell();

        return $html;
    }

    private function renderFiltersForm()
    {
        $wholesalers = $this->getWholesalerOptions();

        $html = '<form method="get" action="' . htmlspecialchars(self::$currentIndex, ENT_QUOTES, 'UTF-8') . '" class="form-inline" style="margin:0 0 12px;">';
        $html .= '<input type="hidden" name="controller" value="' . htmlspecialchars($this->controller_name, ENT_QUOTES, 'UTF-8') . '" />';
        $html .= '<input type="hidden" name="token" value="' . htmlspecialchars($this->token, ENT_QUOTES, 'UTF-8') . '" />';
        $html .= '<input type="hidden" name="azada_tab" value="categories" />';

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

    private function renderProducersPanel()
    {
        $result = $this->getProducerMappingsResult();
        $rows = $result['rows'];
        $total = (int)$result['total'];
        $totalPages = (int)$result['total_pages'];

        $html = '<div class="panel">';
        $html .= '<h3><i class="icon-tags"></i> ' . $this->l('Producenci') . ' <span class="badge">' . $total . '</span></h3>';
        $html .= '<p class="help-block" style="margin-bottom:8px;">' . $this->l('Lista etykiet producentów wykrytych w feedzie (oddzielona od kategorii produktowych).') . '</p>';
        $html .= $this->renderProducerFiltersForm();

        if (empty($rows)) {
            $html .= '<div class="alert alert-info">' . $this->l('Brak producentów dla wybranych filtrów.') . '</div>';
            $html .= '</div>';

            return $html;
        }

        $html .= '<div class="table-responsive-row clearfix">';
        $html .= '<table class="table">';
        $html .= '<thead><tr>';
        $html .= $this->renderProducerSortableHeader($this->l('ID'), 'id', 'width:80px;');
        $html .= $this->renderProducerSortableHeader($this->l('Producent'), 'source_category');
        $html .= $this->renderProducerSortableHeader($this->l('Nazwa hurtowni'), 'source_table', 'width:220px;');
        $html .= '</tr></thead><tbody>';

        foreach ($rows as $row) {
            $html .= '<tr>';
            $html .= '<td>' . (int)$row['id_category_map'] . '</td>';
            $html .= '<td>' . htmlspecialchars($row['source_category'], ENT_QUOTES, 'UTF-8') . '</td>';
            $html .= '<td>' . htmlspecialchars($this->prettyWholesalerName($row['source_table']), ENT_QUOTES, 'UTF-8') . '</td>';
            $html .= '</tr>';
        }

        $html .= '</tbody></table></div>';

        if ($totalPages > 1) {
            $html .= $this->renderProducerPagination($totalPages);
        }

        $html .= '</div>';

        return $html;
    }

    private function renderProducerFiltersForm()
    {
        $wholesalers = $this->getWholesalerOptions();

        $html = '<form method="get" action="' . htmlspecialchars(self::$currentIndex, ENT_QUOTES, 'UTF-8') . '" class="form-inline" style="margin:0 0 12px;">';
        $html .= '<input type="hidden" name="controller" value="' . htmlspecialchars($this->controller_name, ENT_QUOTES, 'UTF-8') . '" />';
        $html .= '<input type="hidden" name="token" value="' . htmlspecialchars($this->token, ENT_QUOTES, 'UTF-8') . '" />';
        $html .= '<input type="hidden" name="azada_tab" value="producers" />';

        $html .= '<input type="hidden" name="azada_q" value="' . htmlspecialchars($this->filterQuery, ENT_QUOTES, 'UTF-8') . '" />';
        $html .= '<input type="hidden" name="azada_wholesaler" value="' . htmlspecialchars($this->filterWholesaler, ENT_QUOTES, 'UTF-8') . '" />';
        $html .= '<input type="hidden" name="azada_status" value="' . htmlspecialchars($this->filterStatus, ENT_QUOTES, 'UTF-8') . '" />';
        $html .= '<input type="hidden" name="azada_sort" value="' . htmlspecialchars($this->sortBy, ENT_QUOTES, 'UTF-8') . '" />';
        $html .= '<input type="hidden" name="azada_dir" value="' . htmlspecialchars($this->sortDir, ENT_QUOTES, 'UTF-8') . '" />';
        $html .= '<input type="hidden" name="azada_per_page" value="' . (int)$this->perPage . '" />';
        $html .= '<input type="hidden" name="azada_page" value="' . (int)$this->page . '" />';

        $html .= '<div class="form-group" style="margin-right:8px;">';
        $html .= '<input type="text" class="form-control" name="azada_p_q" value="' . htmlspecialchars($this->producerFilterQuery, ENT_QUOTES, 'UTF-8') . '" placeholder="Szukaj producenta..." style="min-width:270px;" />';
        $html .= '</div>';

        $html .= '<div class="form-group" style="margin-right:8px;">';
        $html .= '<select name="azada_p_wholesaler" class="form-control">';
        $html .= '<option value="">' . $this->l('Wszystkie hurtownie') . '</option>';
        foreach ($wholesalers as $sourceTable => $label) {
            $selected = ($this->producerFilterWholesaler === $sourceTable) ? ' selected="selected"' : '';
            $html .= '<option value="' . htmlspecialchars($sourceTable, ENT_QUOTES, 'UTF-8') . '"' . $selected . '>' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</option>';
        }
        $html .= '</select>';
        $html .= '</div>';

        $html .= '<div class="form-group" style="margin-right:8px;">';
        $html .= '<select name="azada_p_sort" class="form-control">';
        $sortOptions = [
            'source_category' => $this->l('Sortuj: producent'),
            'source_table' => $this->l('Sortuj: hurtownia'),
            'id' => $this->l('Sortuj: ID'),
        ];
        foreach ($sortOptions as $value => $label) {
            $selected = ($this->producerSortBy === $value) ? ' selected="selected"' : '';
            $html .= '<option value="' . $value . '"' . $selected . '>' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</option>';
        }
        $html .= '</select>';
        $html .= '</div>';

        $html .= '<div class="form-group" style="margin-right:8px;">';
        $html .= '<select name="azada_p_dir" class="form-control">';
        $html .= '<option value="asc"' . ($this->producerSortDir === 'asc' ? ' selected="selected"' : '') . '>' . $this->l('Rosnąco') . '</option>';
        $html .= '<option value="desc"' . ($this->producerSortDir === 'desc' ? ' selected="selected"' : '') . '>' . $this->l('Malejąco') . '</option>';
        $html .= '</select>';
        $html .= '</div>';

        $html .= '<div class="form-group" style="margin-right:8px;">';
        $html .= '<select name="azada_p_per_page" class="form-control">';
        foreach ([50, 100, 200, 500] as $size) {
            $selected = ($this->producerPerPage === $size) ? ' selected="selected"' : '';
            $html .= '<option value="' . (int)$size . '"' . $selected . '>' . (int)$size . ' / strona</option>';
        }
        $html .= '</select>';
        $html .= '</div>';

        $html .= '<button type="submit" class="btn btn-primary" style="margin-right:6px;"><i class="icon-search"></i> ' . $this->l('Szukaj producentów') . '</button>';
        $html .= '<a href="' . htmlspecialchars($this->buildProducerFilterAwareUrl(['azada_p_q' => '', 'azada_p_wholesaler' => '', 'azada_p_page' => 1]), ENT_QUOTES, 'UTF-8') . '" class="btn btn-default"><i class="icon-eraser"></i> ' . $this->l('Wyczyść') . '</a>';

        $html .= '</form>';

        return $html;
    }

    private function renderProducerPagination($totalPages)
    {
        $html = '<div class="pagination" style="margin:10px 0 0;">';

        $prevPage = max(1, $this->producerPage - 1);
        $nextPage = min($totalPages, $this->producerPage + 1);

        $html .= '<a class="btn btn-default btn-sm" ' . ($this->producerPage <= 1 ? 'disabled="disabled"' : '') . ' href="' . htmlspecialchars($this->buildProducerFilterAwareUrl(['azada_p_page' => $prevPage]), ENT_QUOTES, 'UTF-8') . '">&laquo;</a> ';

        $start = max(1, $this->producerPage - 3);
        $end = min($totalPages, $this->producerPage + 3);

        for ($i = $start; $i <= $end; $i++) {
            if ($i === $this->producerPage) {
                $html .= '<span class="btn btn-primary btn-sm" style="margin:0 2px;">' . $i . '</span>';
            } else {
                $html .= '<a class="btn btn-default btn-sm" style="margin:0 2px;" href="' . htmlspecialchars($this->buildProducerFilterAwareUrl(['azada_p_page' => $i]), ENT_QUOTES, 'UTF-8') . '">' . $i . '</a>';
            }
        }

        $html .= ' <a class="btn btn-default btn-sm" ' . ($this->producerPage >= $totalPages ? 'disabled="disabled"' : '') . ' href="' . htmlspecialchars($this->buildProducerFilterAwareUrl(['azada_p_page' => $nextPage]), ENT_QUOTES, 'UTF-8') . '">&raquo;</a>';
        $html .= '</div>';

        return $html;
    }

    private function renderProducerSortableHeader($label, $sortBy, $style = '')
    {
        $thStyle = $style !== '' ? ' style="' . $style . '"' : '';
        $sortAscUrl = $this->buildProducerFilterAwareUrl([
            'azada_p_sort' => $sortBy,
            'azada_p_dir' => 'asc',
            'azada_p_page' => 1,
        ]);
        $sortDescUrl = $this->buildProducerFilterAwareUrl([
            'azada_p_sort' => $sortBy,
            'azada_p_dir' => 'desc',
            'azada_p_page' => 1,
        ]);

        $ascColor = ($this->producerSortBy === $sortBy && $this->producerSortDir === 'asc') ? '#25b9d7' : '#6c868e';
        $descColor = ($this->producerSortBy === $sortBy && $this->producerSortDir === 'desc') ? '#25b9d7' : '#6c868e';

        $html = '<th' . $thStyle . '>';
        $html .= '<span style="display:inline-flex; align-items:center; gap:4px;">';
        $html .= htmlspecialchars($label, ENT_QUOTES, 'UTF-8');
        $html .= '<span style="display:inline-flex; gap:2px;">';
        $html .= '<a href="' . htmlspecialchars($sortAscUrl, ENT_QUOTES, 'UTF-8') . '" title="' . htmlspecialchars($this->l('Sortuj rosnąco'), ENT_QUOTES, 'UTF-8') . '" style="text-decoration:none; color:' . $ascColor . ';">&#9650;</a>';
        $html .= '<a href="' . htmlspecialchars($sortDescUrl, ENT_QUOTES, 'UTF-8') . '" title="' . htmlspecialchars($this->l('Sortuj malejąco'), ENT_QUOTES, 'UTF-8') . '" style="text-decoration:none; color:' . $descColor . ';">&#9660;</a>';
        $html .= '</span>';
        $html .= '</span>';
        $html .= '</th>';

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

    private function renderSortableHeader($label, $sortBy, $style = '')
    {
        $thStyle = $style !== '' ? ' style="' . $style . '"' : '';
        $sortAscUrl = $this->buildFilterAwareUrl([
            'azada_sort' => $sortBy,
            'azada_dir' => 'asc',
            'azada_page' => 1,
        ]);
        $sortDescUrl = $this->buildFilterAwareUrl([
            'azada_sort' => $sortBy,
            'azada_dir' => 'desc',
            'azada_page' => 1,
        ]);

        $ascColor = ($this->sortBy === $sortBy && $this->sortDir === 'asc') ? '#25b9d7' : '#6c868e';
        $descColor = ($this->sortBy === $sortBy && $this->sortDir === 'desc') ? '#25b9d7' : '#6c868e';

        $html = '<th' . $thStyle . '>';
        $html .= '<span style="display:inline-flex; align-items:center; gap:4px;">';
        $html .= htmlspecialchars($label, ENT_QUOTES, 'UTF-8');
        $html .= '<span style="display:inline-flex; gap:2px;">';
        $html .= '<a href="' . htmlspecialchars($sortAscUrl, ENT_QUOTES, 'UTF-8') . '" title="' . htmlspecialchars($this->l('Sortuj rosnąco'), ENT_QUOTES, 'UTF-8') . '" style="text-decoration:none; color:' . $ascColor . ';">&#9650;</a>';
        $html .= '<a href="' . htmlspecialchars($sortDescUrl, ENT_QUOTES, 'UTF-8') . '" title="' . htmlspecialchars($this->l('Sortuj malejąco'), ENT_QUOTES, 'UTF-8') . '" style="text-decoration:none; color:' . $descColor . ';">&#9660;</a>';
        $html .= '</span>';
        $html .= '</span>';
        $html .= '</th>';

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
            'azada_p_q' => $this->producerFilterQuery,
            'azada_p_wholesaler' => $this->producerFilterWholesaler,
            'azada_p_sort' => $this->producerSortBy,
            'azada_p_dir' => $this->producerSortDir,
            'azada_p_per_page' => $this->producerPerPage,
            'azada_p_page' => $this->producerPage,
            'azada_tab' => $this->activeTab,
        ];

        foreach ($extra as $k => $v) {
            $params[$k] = $v;
        }

        return self::$currentIndex . '&' . http_build_query($params);
    }


    private function buildProducerFilterAwareUrl(array $extra = [])
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
            'azada_p_q' => $this->producerFilterQuery,
            'azada_p_wholesaler' => $this->producerFilterWholesaler,
            'azada_p_sort' => $this->producerSortBy,
            'azada_p_dir' => $this->producerSortDir,
            'azada_p_per_page' => $this->producerPerPage,
            'azada_p_page' => $this->producerPage,
            'azada_tab' => $this->activeTab,
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

        $where = ["m.source_type = 'category'"];
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


    private function getProducerMappingsResult()
    {
        $where = ["m.source_type = 'producer'"];

        if ($this->producerFilterWholesaler !== '') {
            $where[] = "m.source_table = '" . pSQL($this->producerFilterWholesaler) . "'";
        }

        if ($this->producerFilterQuery !== '') {
            $q = pSQL($this->producerFilterQuery);
            $where[] = "m.source_category LIKE '%" . $q . "%'";
        }

        $whereSql = 'WHERE ' . implode(' AND ', $where);

        $sortMap = [
            'id' => 'm.id_category_map',
            'source_category' => 'm.source_category',
            'source_table' => 'm.source_table',
        ];

        $orderBy = isset($sortMap[$this->producerSortBy]) ? $sortMap[$this->producerSortBy] : 'm.source_category';
        $orderDir = strtoupper($this->producerSortDir) === 'DESC' ? 'DESC' : 'ASC';

        $countSql = 'SELECT COUNT(*)
                     FROM `' . _DB_PREFIX_ . 'azada_wholesaler_pro_category_map` m
                     ' . $whereSql;

        $total = (int)Db::getInstance()->getValue($countSql);
        $totalPages = max(1, (int)ceil($total / max(1, $this->producerPerPage)));
        if ($this->producerPage > $totalPages) {
            $this->producerPage = $totalPages;
        }

        $offset = ($this->producerPage - 1) * $this->producerPerPage;

        $sql = 'SELECT m.id_category_map, m.source_table, m.source_category
                FROM `' . _DB_PREFIX_ . 'azada_wholesaler_pro_category_map` m
                ' . $whereSql . '
                ORDER BY ' . $orderBy . ' ' . $orderDir . '
                LIMIT ' . (int)$offset . ', ' . (int)$this->producerPerPage;

        $rows = Db::getInstance()->executeS($sql);
        if (!is_array($rows)) {
            $rows = [];
        }

        return [
            'rows' => $rows,
            'total' => $total,
            'total_pages' => $totalPages,
        ];
    }

    private function renderEditModalShell()
    {
        $renderer = new AzadaCategoryMapEditModalRenderer($this);

        return $renderer->renderShell($this->token, (int)Tools::getValue('edit_mapping'), [
            'loading' => $this->l('Ładowanie formularza...'),
            'error' => $this->l('Nie udało się pobrać formularza edycji.'),
            'close' => $this->l('Zamknij'),
            'title' => $this->l('Edycja mapowania kategorii'),
        ]);
    }

    private function renderEditModalContent($idMapping)
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

            ->setSelectedCategories($selectedCategories)
            ->setInputName('ps_categories[]');
  if (method_exists($tree, 'setUseToolbar')) {
            $tree->setUseToolbar(true);
        } elseif (method_exists($tree, 'useToolbar')) {
            $tree->useToolbar(true);
        } elseif (method_exists($tree, 'setToolbar')) {
            $tree->setToolbar(true);
        }


        $treeHtml = $tree->render();

        $categoryOptions = '<option value="0">-</option>';
        foreach ($this->getAllCategoriesForSelect() as $category) {
            $selected = ((int)$category['id_category'] === $idDefault) ? ' selected="selected"' : '';
            $categoryOptions .= '<option value="' . (int)$category['id_category'] . '"' . $selected . '>'
                . htmlspecialchars($category['name_with_path'], ENT_QUOTES, 'UTF-8')
                . '</option>';
        }

        $renderer = new AzadaCategoryMapEditModalRenderer($this);

        return $renderer->renderContent(
            (int)$idMapping,
            (string)$row['source_category'],
            $this->prettyWholesalerName($row['source_table']),
            $treeHtml,
            $categoryOptions,
            ((int)$row['is_active'] === 1),
            $this->buildFilterAwareUrl(['azada_tab' => 'categories']),
            [
                'source_category' => $this->l('Kategoria hurtowni'),
                'wholesaler' => $this->l('Hurtownia'),
                'shop_categories_tree' => $this->l('Kategorie w sklepie (drzewo)'),
                'tree_help' => $this->l('Możesz zaznaczyć wiele kategorii sklepu. Przypisanie zostanie zapisane w bazie i użyte w kolejnych synchronizacjach.'),
                'default_category' => $this->l('Kategoria domyślna'),
                'import_active' => $this->l('Import aktywny'),
                'yes' => $this->l('Tak'),
                'no' => $this->l('Nie'),
                'cancel' => $this->l('Anuluj'),
                'save' => $this->l('Zapisz przypisanie'),
            ]
        );
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

            $knownProducers = $this->getKnownSourceProducers($fullTable);
            $this->cleanupMalformedCombinedMappings($sourceTable);

            foreach ($categories as $categoryRow) {
                $rawCategory = isset($categoryRow['category_name']) ? (string)$categoryRow['category_name'] : '';
                $sourceEntries = $this->extractSourceEntries($rawCategory, $knownProducers);
                if (empty($sourceEntries)) {
                    continue;
                }

                foreach ($sourceEntries as $sourceEntry) {
                    $sourceCategory = (string)$sourceEntry['name'];
                    $sourceType = (string)$sourceEntry['type'];

                    $existing = $db->getRow(
                        "SELECT id_category_map, source_type, id_category_default, ps_category_ids
                         FROM `" . _DB_PREFIX_ . "azada_wholesaler_pro_category_map`
                         WHERE source_table='" . pSQL($sourceTable) . "' AND source_category='" . pSQL($sourceCategory) . "'"
                    );

                    if (is_array($existing) && !empty($existing)) {
                        $existingType = isset($existing['source_type']) ? trim((string)$existing['source_type']) : 'category';

                        if ($existingType !== $sourceType) {
                            $hasMappedDefault = isset($existing['id_category_default']) && (int)$existing['id_category_default'] > 0;
                            $psCategoryIds = isset($existing['ps_category_ids']) ? trim((string)$existing['ps_category_ids']) : '';
                            $hasMappedList = ($psCategoryIds !== '' && $psCategoryIds !== '[]');

                            // Jeśli rekord nie jest jeszcze realnie zmapowany, możemy bezpiecznie zaktualizować jego typ.
                            // Gdy wykryliśmy kategorię (nie producenta), typ kategorii ma priorytet.
                            if (!$hasMappedDefault && !$hasMappedList || $sourceType === 'category') {
                                $db->update('azada_wholesaler_pro_category_map', [
                                    'source_type' => pSQL($sourceType),
                                    'date_upd' => date('Y-m-d H:i:s'),
                                ], 'id_category_map=' . (int)$existing['id_category_map']);
                            }
                        }

                        continue;
                    }

                    $ok = $db->insert('azada_wholesaler_pro_category_map', [
                        'source_table' => pSQL($sourceTable),
                        'source_category' => pSQL($sourceCategory),
                        'source_type' => pSQL($sourceType),
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
        }

        return $rowsAdded;
    }



    private function extractSourceEntries($rawCategory, array $knownProducers)
    {
        $rawCategory = trim((string)$rawCategory);

        if ($rawCategory === '') {
            return [];
        }

        $rawCategory = preg_replace('/\x{00A0}/u', ' ', $rawCategory);
        if ($rawCategory === null) {
            $rawCategory = '';
        }

        $parts = array_map('trim', explode(';', $rawCategory));
        $parts = array_values(array_filter($parts, function ($item) {
            return trim((string)$item) !== '';
        }));

        if (!empty($parts) && strpos($parts[0], '*') === 0) {
            array_shift($parts);
        }

        $result = [];
        foreach ($parts as $part) {
            $part = ltrim($part, "* \t\n\r\0\x0B");
            $normalized = $this->normalizeSourceCategory($part);
            if ($normalized === '') {
                continue;
            }

            if (mb_strtoupper($normalized, 'UTF-8') === 'WSZYSTKIE') {
                continue;
            }

            $type = $this->resolveSourceType($normalized, $knownProducers);
            if (isset($result[$normalized]) && $result[$normalized]['type'] === 'category') {
                continue;
            }

            $result[$normalized] = [
                'name' => $normalized,
                'type' => $type,
            ];
        }

        return array_values($result);
    }

    private function cleanupMalformedCombinedMappings($sourceTable)
    {
        Db::getInstance()->execute(
            "DELETE FROM `" . _DB_PREFIX_ . "azada_wholesaler_pro_category_map`
             WHERE source_table='" . pSQL($sourceTable) . "'
               AND (source_category LIKE '%;%' OR source_category LIKE '*%')"
        );
    }

    private function resolveSourceType($sourceCategory, array $knownProducers)
    {
        $normalizedKey = $this->buildSourceLabelKey($sourceCategory);
        if ($normalizedKey !== '' && isset($knownProducers[$normalizedKey])) {
            return 'producer';
        }

        return 'category';
    }

    private function getKnownSourceProducers($fullTable)
    {
        $db = Db::getInstance();
        $columns = $db->executeS("SHOW COLUMNS FROM `" . bqSQL($fullTable) . "`");
        if (!is_array($columns)) {
            return [];
        }

        $availableColumns = [];
        foreach ($columns as $column) {
            if (!isset($column['Field'])) {
                continue;
            }

            $columnName = trim((string)$column['Field']);
            if ($columnName === '') {
                continue;
            }

            $availableColumns[Tools::strtolower($columnName)] = $columnName;
        }

        $candidates = [
            'producent',
            'producer',
            'brand',
            'marka',
            'manufacturer',
            'nazwa_producenta',
            'producent_nazwa',
        ];

        $knownProducers = [];
        foreach ($candidates as $candidate) {
            if (!isset($availableColumns[$candidate])) {
                continue;
            }

            $columnName = $availableColumns[$candidate];
            $rows = $db->executeS(
                "SELECT DISTINCT TRIM(`" . bqSQL($columnName) . "`) AS producer_name
                 FROM `" . bqSQL($fullTable) . "`
                 WHERE TRIM(IFNULL(`" . bqSQL($columnName) . "`, '')) <> ''"
            );

            if (!is_array($rows)) {
                continue;
            }

            foreach ($rows as $row) {
                $producer = $this->normalizeSourceCategory(isset($row['producer_name']) ? $row['producer_name'] : '');
                if ($producer === '') {
                    continue;
                }

                $knownProducers[$this->buildSourceLabelKey($producer)] = $producer;
            }
        }

        return $knownProducers;
    }

    private function buildSourceLabelKey($value)
    {
        $value = Tools::strtolower(trim((string)$value));
        if ($value === '') {
            return '';
        }

        $value = preg_replace('/\s+/u', ' ', $value);
        if ($value === null) {
            return '';
        }

        return trim($value);
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
