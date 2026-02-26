<?php

require_once(dirname(__FILE__) . '/../../classes/services/AzadaInstaller.php');
require_once(dirname(__FILE__) . '/../../classes/services/AzadaManufacturerImportMatcher.php');

class AdminAzadaManufacturerMapController extends ModuleAdminController
{
    private $filterWholesaler = '';
    private $filterQuery = '';
    private $filterStatus = '';
    private $sortBy = 'id';
    private $sortDir = 'desc';
    private $page = 1;
    private $perPage = 50;
    private $showSuggestions = false;

    public function __construct()
    {
        $this->bootstrap = true;
        parent::__construct();

        // Ensure DB table exists.
        if (method_exists('AzadaInstaller', 'ensureManufacturerMapTables')) {
            AzadaInstaller::ensureManufacturerMapTables();
        }

        $this->filterWholesaler = trim((string) Tools::getValue('azada_wholesaler', ''));
        $this->filterQuery = trim((string) Tools::getValue('azada_q', ''));
        $this->filterStatus = trim((string) Tools::getValue('azada_status', ''));

        $this->sortBy = trim((string) Tools::getValue('azada_sort', 'id'));
        $this->sortDir = Tools::strtolower(trim((string) Tools::getValue('azada_dir', 'desc')));
        if (!in_array($this->sortDir, ['asc', 'desc'], true)) {
            $this->sortDir = 'desc';
        }

        $this->page = (int) Tools::getValue('azada_page', 1);
        if ($this->page < 1) {
            $this->page = 1;
        }

        $this->perPage = (int) Tools::getValue('azada_per_page', 50);
        if (!in_array($this->perPage, [50, 100, 200, 500], true)) {
            $this->perPage = 50;
        }

        $this->showSuggestions = ((int) Tools::getValue('show_suggestions', 0) === 1);
    }

    public function initContent()
    {
        // AJAX: modal content
        if (Tools::getValue('ajax') && Tools::getValue('action') === 'getEditModal') {
            $id = (int) Tools::getValue('id_manufacturer_map');
            header('Content-Type: text/html; charset=utf-8');
            die($this->renderEditModalContent($id));
        }

        parent::initContent();

        $html = '';
        $html .= $this->renderTopPanel();
        if ($this->showSuggestions) {
            $html .= $this->renderSuggestionsPanel();
        }
        $html .= $this->renderMappingsPanel();
        $html .= $this->renderEditModalShell();

        $this->context->smarty->assign('content', $html);
    }

    public function postProcess()
    {
        if (Tools::isSubmit('submitAzadaManufacturerMapSuggest')) {
            $this->showSuggestions = true;
        }

        if (Tools::isSubmit('submitAzadaManufacturerMapSync')) {
            $added = (int) $this->syncSourceManufacturers();
            $matched = (int) $this->autoMapExactMatches();

            $this->confirmations[] = sprintf($this->l('Zsynchronizowano producentów z hurtowni. Dodano: %d.'), $added);
            if ($matched > 0) {
                $this->confirmations[] = sprintf($this->l('Automatycznie dopasowano producentów (1:1): %d.'), $matched);
            }
        }

        // Akceptacja pojedynczej propozycji z listy dopasowań.
        if (Tools::isSubmit('submitAzadaManufacturerMapAcceptOne')) {
            $this->showSuggestions = true;

            $idMap = (int) Tools::getValue('submitAzadaManufacturerMapAcceptOne');
            $bulkManufacturers = Tools::getValue('bulk_manufacturer');
            $idManufacturer = 0;
            if (is_array($bulkManufacturers) && isset($bulkManufacturers[$idMap])) {
                $idManufacturer = (int) $bulkManufacturers[$idMap];
            }

            if ($idMap <= 0 || $idManufacturer <= 0) {
                $this->errors[] = $this->l('Nie wybrano producenta do przypisania.');
            } else {
                $ok = $this->updateManufacturerMap((int) $idMap, (int) $idManufacturer);
                if ($ok) {
                    $this->confirmations[] = $this->l('Zapisano mapowanie producenta.');
                }
            }
        }

        // Akceptacja masowa propozycji z listy dopasowań.
        if (Tools::isSubmit('submitAzadaManufacturerMapAcceptBulk')) {
            $this->showSuggestions = true;

            $ids = Tools::getValue('bulk_ids');
            $bulkManufacturers = Tools::getValue('bulk_manufacturer');

            $okCount = 0;
            $skipCount = 0;

            if (is_array($ids) && !empty($ids)) {
                foreach ($ids as $idMapRaw) {
                    $idMap = (int) $idMapRaw;
                    if ($idMap <= 0) {
                        $skipCount++;
                        continue;
                    }

                    $idManufacturer = 0;
                    if (is_array($bulkManufacturers) && isset($bulkManufacturers[$idMap])) {
                        $idManufacturer = (int) $bulkManufacturers[$idMap];
                    }

                    if ($idManufacturer <= 0) {
                        $skipCount++;
                        continue;
                    }

                    if ($this->updateManufacturerMap((int) $idMap, (int) $idManufacturer)) {
                        $okCount++;
                    } else {
                        $skipCount++;
                    }
                }
            }

            $this->confirmations[] = sprintf($this->l('Zapisano mapowania: %d. Pominięto: %d.'), (int) $okCount, (int) $skipCount);
        }

        if (Tools::isSubmit('submitAzadaManufacturerMapSave')) {
            $ok = $this->saveMapping();
            if ($ok) {
                $this->confirmations[] = $this->l('Zapisano mapowanie producenta.');
            }
        }

        if (Tools::isSubmit('submitAzadaManufacturerMapCreateAndAssign')) {
            $ok = $this->createAndAssignManufacturer();
            if ($ok) {
                $this->confirmations[] = $this->l('Utworzono / przypisano producenta w PrestaShop.');
            }
        }

        parent::postProcess();
    }

    private function renderTopPanel()
    {
        $syncUrl = $this->buildFilterAwareUrl();

        $html = '<div class="panel">';
        $html .= '<h3><i class="icon-industry"></i> ' . $this->l('Mapowanie Producentów') . '</h3>';
        $html .= '<p class="help-block" style="margin-bottom:10px;">' . $this->l('Tutaj przypisujesz producentów z hurtowni (kolumna „marka”) do producentów (Manufacturer) w PrestaShop. Dzięki temu unikasz duplikatów i masz spójnych producentów w sklepie.') . '</p>';

        $html .= '<form method="post" action="' . htmlspecialchars($syncUrl, ENT_QUOTES, 'UTF-8') . '" style="margin:0; display:flex; align-items:center; flex-wrap:wrap; gap:8px;">';
        $html .= '<button type="submit" name="submitAzadaManufacturerMapSync" class="btn btn-primary">';
        $html .= '<i class="icon-refresh"></i> ' . $this->l('Pobierz / odśwież producentów z hurtowni');
        $html .= '</button>';

        $html .= '<button type="submit" name="submitAzadaManufacturerMapSuggest" class="btn btn-default">';
        $html .= '<i class="icon-search"></i> ' . $this->l('Znajdź propozycje dopasowań');
        $html .= '</button>';

        $html .= '<span class="text-muted" style="margin-left:10px;">' . $this->l('Użyj po imporcie feedu lub gdy chcesz szybko dopasować niezmapowane marki (np. gdy w nazwie są dopiski w nawiasach).') . '</span>';
        $html .= '</form>';

        $html .= '</div>';

        return $html;
    }

    /**
     * Panel podpowiedzi dopasowań dla producentów, którzy nie mają mapowania.
     *
     * Zasada: nie mapujemy automatycznie – pokazujemy propozycje do zaakceptowania.
     * Możesz zaakceptować pojedynczo lub masowo.
     */
    private function renderSuggestionsPanel()
    {
        $result = $this->getMappingsResult();
        $rows = isset($result['rows']) && is_array($result['rows']) ? $result['rows'] : [];

        $unmapped = [];
        foreach ($rows as $r) {
            $idManufacturer = isset($r['id_manufacturer']) ? (int) $r['id_manufacturer'] : 0;
            if ($idManufacturer <= 0) {
                $unmapped[] = $r;
            }
        }

        $html = '<div class="panel">';
        $html .= '<h3><i class="icon-search"></i> ' . $this->l('Propozycje dopasowań dla niezmapowanych producentów') . '</h3>';
        $html .= '<p class="help-block" style="margin-bottom:10px;">'
            . $this->l('Moduł spróbuje znaleźć pasujących producentów w PrestaShop na podstawie fragmentów nazwy (np. część przed nawiasem lub w nawiasie). Następnie możesz zaakceptować dopasowania pojedynczo lub masowo.')
            . '</p>';

        if (empty($unmapped)) {
            $html .= '<div class="alert alert-info">' . $this->l('Na tej stronie nie ma niezmapowanych producentów (lub filtr pokazuje tylko zmapowane).') . '</div>';
            $html .= '</div>';
            return $html;
        }

        $index = $this->buildManufacturerIndex();

        $suggestedCount = 0;
        $suggestRows = [];
        foreach ($unmapped as $row) {
            $brand = isset($row['source_manufacturer']) ? (string) $row['source_manufacturer'] : '';
            $suggestions = $this->getManufacturerSuggestions($brand, $index);
            if (!empty($suggestions)) {
                $suggestedCount++;
            }
            $suggestRows[] = [
                'row' => $row,
                'suggestions' => $suggestions,
            ];
        }

        $html .= '<div class="alert alert-info" style="margin-bottom:10px;">'
            . sprintf($this->l('Podpowiedzi znalezione dla: %d / %d producentów na tej stronie.'), (int) $suggestedCount, (int) count($unmapped))
            . '</div>';

        $actionUrl = $this->buildFilterAwareUrl(['show_suggestions' => 1]);

        $html .= '<form method="post" action="' . htmlspecialchars($actionUrl, ENT_QUOTES, 'UTF-8') . '">';
        $html .= '<input type="hidden" name="show_suggestions" value="1" />';

        $html .= '<div style="display:flex; align-items:center; flex-wrap:wrap; gap:8px; margin-bottom:10px;">';
        $html .= '<button type="submit" name="submitAzadaManufacturerMapAcceptBulk" class="btn btn-primary">'
            . '<i class="icon-check"></i> ' . $this->l('Akceptuj zaznaczone')
            . '</button>';
        $html .= '<button type="submit" name="submitAzadaManufacturerMapSuggest" class="btn btn-default">'
            . '<i class="icon-refresh"></i> ' . $this->l('Odśwież propozycje')
            . '</button>';
        $html .= '<span class="text-muted" style="margin-left:10px;">'
            . $this->l('Wskazówka: ustaw filtr „Tylko niezmapowane” i zwiększ „/ strona” (np. 200/500), aby szybciej mapować większe partie.')
            . '</span>';
        $html .= '</div>';

        $html .= '<div class="table-responsive-row clearfix">';
        $html .= '<table class="table">';
        $html .= '<thead><tr>';
        $html .= '<th style="width:30px;"><input type="checkbox" id="azada_suggest_check_all" /></th>';
        $html .= '<th style="width:80px;">' . $this->l('ID') . '</th>';
        $html .= '<th>' . $this->l('Producent (hurtownia)') . '</th>';
        $html .= '<th style="width:220px;">' . $this->l('Hurtownia') . '</th>';
        $html .= '<th>' . $this->l('Propozycja (PrestaShop)') . '</th>';
        $html .= '<th style="width:140px;">' . $this->l('Akcja') . '</th>';
        $html .= '</tr></thead><tbody>';

        foreach ($suggestRows as $item) {
            $row = $item['row'];
            $suggestions = $item['suggestions'];

            $idMap = isset($row['id_manufacturer_map']) ? (int) $row['id_manufacturer_map'] : 0;
            $brand = isset($row['source_manufacturer']) ? (string) $row['source_manufacturer'] : '';
            $sourceTable = isset($row['source_table']) ? (string) $row['source_table'] : '';

            $hasSuggestions = !empty($suggestions);
            $defaultId = $hasSuggestions ? (int) $suggestions[0]['id'] : 0;

            // Bezpieczniej: domyślnie zaznaczamy tylko propozycje oparte o fragment 1:1 / normalizację (najmniej ryzykowne).
            $precheck = false;
            if ($hasSuggestions && count($suggestions) === 1) {
                $topReason = isset($suggestions[0]['reason']) ? (string) $suggestions[0]['reason'] : '';
                if (in_array($topReason, ['exact', 'normalized'], true)) {
                    $precheck = true;
                }
            }
            $checked = $precheck ? ' checked="checked"' : '';
            $disabled = $hasSuggestions ? '' : ' disabled="disabled"';

            $html .= '<tr>';
            $html .= '<td><input type="checkbox" class="azada_suggest_row" name="bulk_ids[]" value="' . (int) $idMap . '"' . $checked . $disabled . ' /></td>';
            $html .= '<td>' . (int) $idMap . '</td>';
            $html .= '<td>' . htmlspecialchars($brand, ENT_QUOTES, 'UTF-8') . '</td>';
            $html .= '<td>' . htmlspecialchars($this->prettyWholesalerName($sourceTable), ENT_QUOTES, 'UTF-8') . '</td>';
            $html .= '<td>';

            if ($hasSuggestions) {
                $html .= '<select class="form-control" name="bulk_manufacturer[' . (int) $idMap . ']" style="max-width:520px;">';
                foreach ($suggestions as $idx => $sug) {
                    $idM = (int) $sug['id'];
                    $name = (string) $sug['name'];
                    $selected = ($idM === $defaultId) ? ' selected="selected"' : '';
                    $html .= '<option value="' . (int) $idM . '"' . $selected . '>' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . ' (#' . (int) $idM . ')</option>';
                }
                $html .= '</select>';

                $top = $suggestions[0];
                $reason = isset($top['reason']) ? (string) $top['reason'] : '';
                $match = isset($top['match']) ? (string) $top['match'] : '';
                $reasonLabel = '';
                if ($reason === 'exact') {
                    $reasonLabel = $this->l('fragment 1:1');
                } elseif ($reason === 'normalized') {
                    $reasonLabel = $this->l('fragment (normalizacja)');
                } elseif ($reason === 'contains') {
                    $reasonLabel = $this->l('zawieranie');
                } elseif ($reason === 'contained_by') {
                    $reasonLabel = $this->l('zawieranie (odwrotnie)');
                }
                if ($match !== '') {
                    $html .= '<div class="text-muted" style="margin-top:4px; font-size:12px;">'
                        . htmlspecialchars($this->l('Dopasowanie:'), ENT_QUOTES, 'UTF-8') . ' '
                        . htmlspecialchars($match, ENT_QUOTES, 'UTF-8')
                        . ($reasonLabel !== '' ? ' <span style="opacity:0.7;">(' . htmlspecialchars($reasonLabel, ENT_QUOTES, 'UTF-8') . ')</span>' : '')
                        . '</div>';
                }
            } else {
                $html .= '<span class="text-muted">' . $this->l('Brak propozycji') . '</span>';
                $html .= '<input type="hidden" name="bulk_manufacturer[' . (int) $idMap . ']" value="0" />';
            }

            $html .= '</td>';
            $html .= '<td>';

            if ($hasSuggestions) {
                $html .= '<button type="submit" name="submitAzadaManufacturerMapAcceptOne" value="' . (int) $idMap . '" class="btn btn-default btn-xs">'
                    . '<i class="icon-check"></i> ' . $this->l('Akceptuj')
                    . '</button>';
            } else {
                $html .= '<button type="button" class="btn btn-default btn-xs" disabled="disabled">' . $this->l('Akceptuj') . '</button>';
            }

            // Link do ręcznej edycji (modal)
            $editUrl = $this->buildFilterAwareUrl(['edit_mapping' => $idMap, 'show_suggestions' => 1]);
            $html .= ' <a href="' . htmlspecialchars($editUrl, ENT_QUOTES, 'UTF-8') . '" class="btn btn-default btn-xs js-azada-manufact-edit" data-edit-url="' . htmlspecialchars($editUrl, ENT_QUOTES, 'UTF-8') . '"><i class="icon-edit"></i> ' . $this->l('Edytuj') . '</a>';

            $html .= '</td>';
            $html .= '</tr>';
        }

        $html .= '</tbody></table></div>';
        $html .= '</form>';

        // JS: zaznacz wszystkie
        $html .= '<script>(function($){'
            . '$(document).off("change.azadaSuggestAll", "#azada_suggest_check_all").on("change.azadaSuggestAll", "#azada_suggest_check_all", function(){'
            . 'var checked = $(this).is(":checked");'
            . '$(".azada_suggest_row").each(function(){ if (!$(this).is(":disabled")) { $(this).prop("checked", checked); } });'
            . '});'
            . '})(jQuery);</script>';

        $html .= '</div>';

        return $html;
    }

    private function buildManufacturerIndex()
    {
        $rows = Db::getInstance()->executeS(
            'SELECT id_manufacturer, name FROM `' . bqSQL(_DB_PREFIX_ . 'manufacturer') . '` WHERE TRIM(IFNULL(name, "")) <> "" ORDER BY name ASC'
        );
        if (!is_array($rows)) {
            $rows = [];
        }

        $idToName = [];
        $byExact = [];
        $byKey = [];
        $items = [];

        foreach ($rows as $r) {
            $id = isset($r['id_manufacturer']) ? (int) $r['id_manufacturer'] : 0;
            $name = isset($r['name']) ? trim((string) $r['name']) : '';
            if ($id <= 0 || $name === '') {
                continue;
            }

            $idToName[$id] = $name;

            $exact = Tools::strtolower($name);
            if (!isset($byExact[$exact])) {
                $byExact[$exact] = [];
            }
            $byExact[$exact][] = $id;

            $key = AzadaManufacturerImportMatcher::normalizeKey($name);
            if ($key !== '') {
                if (!isset($byKey[$key])) {
                    $byKey[$key] = [];
                }
                $byKey[$key][] = $id;

                $items[] = [
                    'id' => $id,
                    'name' => $name,
                    'key' => $key,
                    'key_len' => Tools::strlen($key),
                ];
            }
        }

        return [
            'id_to_name' => $idToName,
            'by_exact' => $byExact,
            'by_key' => $byKey,
            'items' => $items,
        ];
    }

    private function extractBrandParts($brand)
    {
        $brand = trim((string) $brand);
        if ($brand === '') {
            return [];
        }

        $parts = [];
        $parts[] = $brand;

        // Fragment przed nawiasem
        $pos = strpos($brand, '(');
        if ($pos !== false) {
            $before = trim(substr($brand, 0, $pos));
            if ($before !== '') {
                $parts[] = $before;
            }
        }

        // Fragmenty w nawiasach
        if (preg_match_all('/\(([^\)]+)\)/u', $brand, $m) && isset($m[1]) && is_array($m[1])) {
            foreach ($m[1] as $inside) {
                $inside = trim((string) $inside);
                if ($inside !== '') {
                    $parts[] = $inside;
                }
            }
        }

        // Podział po separatorach
        $split = preg_split('/[\(\)\[\]\{\}\,\;\|\/\\\-\–\—\+]+/u', $brand);
        if (is_array($split)) {
            foreach ($split as $s) {
                $s = trim((string) $s);
                if ($s !== '' && Tools::strlen($s) >= 3) {
                    $parts[] = $s;
                }
            }
        }

        // Unique
        $uniq = [];
        $out = [];
        foreach ($parts as $p) {
            $pTrim = trim((string) $p);
            if ($pTrim === '') {
                continue;
            }
            $k = Tools::strtolower($pTrim);
            if (isset($uniq[$k])) {
                continue;
            }
            $uniq[$k] = 1;
            $out[] = $pTrim;
        }

        return $out;
    }

    private function addManufacturerCandidate(&$candidates, $idManufacturer, $score, $reason, $match)
    {
        $idManufacturer = (int) $idManufacturer;
        if ($idManufacturer <= 0) {
            return;
        }
        $score = (int) $score;
        $reason = (string) $reason;
        $match = (string) $match;

        if (!isset($candidates[$idManufacturer]) || $score > (int) $candidates[$idManufacturer]['score']) {
            $candidates[$idManufacturer] = [
                'id' => $idManufacturer,
                'score' => $score,
                'reason' => $reason,
                'match' => $match,
            ];
        }
    }

    private function getManufacturerSuggestions($brand, $index)
    {
        $brand = trim((string) $brand);
        if ($brand === '') {
            return [];
        }

        $brandKey = AzadaManufacturerImportMatcher::normalizeKey($brand);
        $brandKeyLen = Tools::strlen($brandKey);

        $candidates = [];

        $parts = $this->extractBrandParts($brand);
        foreach ($parts as $part) {
            $part = trim((string) $part);
            if ($part === '') {
                continue;
            }

            $exact = Tools::strtolower($part);
            if ($exact !== '' && isset($index['by_exact'][$exact]) && is_array($index['by_exact'][$exact])) {
                foreach ($index['by_exact'][$exact] as $id) {
                    $this->addManufacturerCandidate($candidates, (int) $id, 10000 + Tools::strlen($exact), 'exact', $part);
                }
            }

            $key = AzadaManufacturerImportMatcher::normalizeKey($part);
            if ($key !== '' && isset($index['by_key'][$key]) && is_array($index['by_key'][$key])) {
                foreach ($index['by_key'][$key] as $id) {
                    $this->addManufacturerCandidate($candidates, (int) $id, 9000 + Tools::strlen($key), 'normalized', $part);
                }
            }
        }

        // Jeśli brak jednoznacznych dopasowań po fragmentach – spróbuj dopasowania po zawieraniu (po normalizacji).
        if (empty($candidates) && $brandKey !== '' && isset($index['items']) && is_array($index['items'])) {
            foreach ($index['items'] as $it) {
                $nameKey = isset($it['key']) ? (string) $it['key'] : '';
                $nameKeyLen = isset($it['key_len']) ? (int) $it['key_len'] : 0;
                if ($nameKeyLen < 5) {
                    continue;
                }

                if (strpos($brandKey, $nameKey) !== false) {
                    $this->addManufacturerCandidate($candidates, (int) $it['id'], 5000 + $nameKeyLen, 'contains', (string) $it['name']);
                } elseif ($brandKeyLen >= 5 && strpos($nameKey, $brandKey) !== false) {
                    $this->addManufacturerCandidate($candidates, (int) $it['id'], 4000 + $brandKeyLen, 'contained_by', (string) $it['name']);
                }
            }
        }

        if (empty($candidates)) {
            return [];
        }

        // Sort by score desc
        uasort($candidates, function ($a, $b) {
            $sa = isset($a['score']) ? (int) $a['score'] : 0;
            $sb = isset($b['score']) ? (int) $b['score'] : 0;
            if ($sa === $sb) {
                return 0;
            }
            return ($sa > $sb) ? -1 : 1;
        });

        $out = [];
        $idToName = isset($index['id_to_name']) && is_array($index['id_to_name']) ? $index['id_to_name'] : [];

        $i = 0;
        foreach ($candidates as $id => $cand) {
            $id = (int) $id;
            if ($id <= 0) {
                continue;
            }
            $name = isset($idToName[$id]) ? (string) $idToName[$id] : ('#' . $id);
            $out[] = [
                'id' => $id,
                'name' => $name,
                'score' => isset($cand['score']) ? (int) $cand['score'] : 0,
                'reason' => isset($cand['reason']) ? (string) $cand['reason'] : '',
                'match' => isset($cand['match']) ? (string) $cand['match'] : '',
            ];
            $i++;
            if ($i >= 5) {
                break;
            }
        }

        return $out;
    }

    private function updateManufacturerMap($idManufacturerMap, $idManufacturer)
    {
        $idManufacturerMap = (int) $idManufacturerMap;
        $idManufacturer = (int) $idManufacturer;

        if ($idManufacturerMap <= 0) {
            $this->errors[] = $this->l('Brak ID mapowania.');
            return false;
        }

        if ($idManufacturer > 0) {
            $exists = (int) Db::getInstance()->getValue(
                'SELECT id_manufacturer FROM `' . bqSQL(_DB_PREFIX_ . 'manufacturer') . '` WHERE id_manufacturer=' . (int) $idManufacturer
            );
            if ($exists <= 0) {
                $this->errors[] = $this->l('Wybrany producent nie istnieje w PrestaShop.');
                return false;
            }
        }

        return (bool) Db::getInstance()->update('azada_wholesaler_pro_manufacturer_map', [
            'id_manufacturer' => (int) $idManufacturer,
            'date_upd' => date('Y-m-d H:i:s'),
        ], 'id_manufacturer_map=' . (int) $idManufacturerMap);
    }

    private function renderMappingsPanel()
    {
        $result = $this->getMappingsResult();
        $rows = $result['rows'];
        $total = (int) $result['total'];
        $totalPages = (int) $result['total_pages'];

        $html = '<div class="panel">';
        $html .= '<h3><i class="icon-list"></i> ' . $this->l('Lista producentów z hurtowni') . ' <span class="badge">' . $total . '</span></h3>';
        $html .= $this->renderFiltersForm();

        if (empty($rows)) {
            $html .= '<div class="alert alert-info">' . $this->l('Brak wyników dla wybranych filtrów.') . '</div>';
            $html .= '</div>';
            return $html;
        }

        $html .= '<div class="table-responsive-row clearfix">';
        $html .= '<table class="table">';
        $html .= '<thead><tr>';
        $html .= $this->renderSortableHeader($this->l('ID'), 'id', 'width:80px;');
        $html .= $this->renderSortableHeader($this->l('Producent (hurtownia)'), 'source_manufacturer');
        $html .= $this->renderSortableHeader($this->l('Hurtownia'), 'source_table', 'width:220px;');
        $html .= $this->renderSortableHeader($this->l('Producent (PrestaShop)'), 'manufacturer_name');
        $html .= '<th style="width:120px;">' . $this->l('Akcje') . '</th>';
        $html .= '</tr></thead><tbody>';

        foreach ($rows as $row) {
            $id = (int) $row['id_manufacturer_map'];
            $sourceManufacturer = (string) $row['source_manufacturer'];
            $sourceTable = (string) $row['source_table'];
            $manufacturerName = (string) $row['manufacturer_name'];
            $idManufacturer = (int) $row['id_manufacturer'];

            $badge = $idManufacturer > 0 ? '<span class="label label-success">' . $this->l('Zmapowane') . '</span>' : '<span class="label label-warning">' . $this->l('Brak') . '</span>';

            $editUrl = $this->buildFilterAwareUrl(['edit_mapping' => $id]);

            $html .= '<tr>';
            $html .= '<td>' . $id . '</td>';
            $html .= '<td>' . htmlspecialchars($sourceManufacturer, ENT_QUOTES, 'UTF-8') . '</td>';
            $html .= '<td>' . htmlspecialchars($this->prettyWholesalerName($sourceTable), ENT_QUOTES, 'UTF-8') . '</td>';
            $html .= '<td>';
            if ($idManufacturer > 0) {
                $html .= htmlspecialchars($manufacturerName !== '' ? $manufacturerName : ('#' . $idManufacturer), ENT_QUOTES, 'UTF-8') . ' ' . $badge;
            } else {
                $html .= $badge;
            }
            $html .= '</td>';
            $html .= '<td>';
            $html .= '<a href="' . htmlspecialchars($editUrl, ENT_QUOTES, 'UTF-8') . '" class="btn btn-default btn-xs js-azada-manufact-edit" data-edit-url="' . htmlspecialchars($editUrl, ENT_QUOTES, 'UTF-8') . '">';
            $html .= '<i class="icon-edit"></i> ' . $this->l('Edytuj');
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
        $html .= '<input type="text" class="form-control" name="azada_q" value="' . htmlspecialchars($this->filterQuery, ENT_QUOTES, 'UTF-8') . '" placeholder="' . htmlspecialchars($this->l('Szukaj producenta...'), ENT_QUOTES, 'UTF-8') . '" style="min-width:320px;" />';
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
            '' => $this->l('Status: wszystkie'),
            'mapped' => $this->l('Tylko zmapowane'),
            'unmapped' => $this->l('Tylko niezmapowane'),
        ];
        foreach ($statusOptions as $value => $label) {
            $selected = ($this->filterStatus === $value) ? ' selected="selected"' : '';
            $html .= '<option value="' . htmlspecialchars($value, ENT_QUOTES, 'UTF-8') . '"' . $selected . '>' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</option>';
        }
        $html .= '</select>';
        $html .= '</div>';

        $html .= '<div class="form-group" style="margin-right:8px;">';
        $html .= '<select name="azada_per_page" class="form-control">';
        foreach ([50, 100, 200, 500] as $size) {
            $selected = ($this->perPage === $size) ? ' selected="selected"' : '';
            $html .= '<option value="' . (int) $size . '"' . $selected . '>' . (int) $size . ' / ' . $this->l('strona') . '</option>';
        }
        $html .= '</select>';
        $html .= '</div>';

        $html .= '<button type="submit" class="btn btn-primary" style="margin-right:6px;"><i class="icon-search"></i> ' . $this->l('Szukaj') . '</button>';
        $html .= '<a href="' . htmlspecialchars($this->buildFilterAwareUrl(['azada_q' => '', 'azada_wholesaler' => '', 'azada_status' => '', 'azada_page' => 1]), ENT_QUOTES, 'UTF-8') . '" class="btn btn-default"><i class="icon-eraser"></i> ' . $this->l('Wyczyść') . '</a>';

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

    private function buildFilterAwareUrl($override = [])
    {
        $params = [
            'controller' => $this->controller_name,
            'token' => $this->token,
            'azada_q' => $this->filterQuery,
            'azada_wholesaler' => $this->filterWholesaler,
            'azada_status' => $this->filterStatus,
            'azada_sort' => $this->sortBy,
            'azada_dir' => $this->sortDir,
            'azada_page' => $this->page,
            'azada_per_page' => $this->perPage,
        ];

        foreach ((array) $override as $k => $v) {
            $params[$k] = $v;
        }

        return self::$currentIndex . '&' . http_build_query($params);
    }

    private function getWholesalerOptions()
    {
        $rows = Db::getInstance()->executeS('SELECT DISTINCT source_table FROM `' . _DB_PREFIX_ . 'azada_wholesaler_pro_manufacturer_map` ORDER BY source_table ASC');
        if (!is_array($rows)) {
            return [];
        }

        $options = [];
        foreach ($rows as $row) {
            $sourceTable = isset($row['source_table']) ? trim((string) $row['source_table']) : '';
            if ($sourceTable === '') {
                continue;
            }
            $options[$sourceTable] = $this->prettyWholesalerName($sourceTable);
        }
        return $options;
    }

    private function getMappingsResult()
    {
        $where = ['1'];

        if ($this->filterWholesaler !== '') {
            $where[] = "mm.source_table='" . pSQL($this->filterWholesaler) . "'";
        }

        if ($this->filterQuery !== '') {
            $q = pSQL($this->filterQuery);
            $where[] = "(mm.source_manufacturer LIKE '%" . $q . "%' OR m.name LIKE '%" . $q . "%')";
        }

        if ($this->filterStatus === 'mapped') {
            $where[] = 'mm.id_manufacturer > 0';
        } elseif ($this->filterStatus === 'unmapped') {
            $where[] = '(mm.id_manufacturer = 0 OR mm.id_manufacturer IS NULL)';
        }

        $whereSql = 'WHERE ' . implode(' AND ', $where);

        $sortMap = [
            'id' => 'mm.id_manufacturer_map',
            'source_manufacturer' => 'mm.source_manufacturer',
            'source_table' => 'mm.source_table',
            'manufacturer_name' => 'm.name',
        ];

        $orderBy = isset($sortMap[$this->sortBy]) ? $sortMap[$this->sortBy] : $sortMap['id'];
        $orderWay = $this->sortDir === 'asc' ? 'ASC' : 'DESC';

        $total = (int) Db::getInstance()->getValue(
            'SELECT COUNT(*) FROM `' . _DB_PREFIX_ . 'azada_wholesaler_pro_manufacturer_map` mm '
            . 'LEFT JOIN `' . _DB_PREFIX_ . 'manufacturer` m ON (m.id_manufacturer = mm.id_manufacturer) '
            . $whereSql
        );

        $totalPages = (int) ceil($total / $this->perPage);
        if ($totalPages < 1) {
            $totalPages = 1;
        }
        if ($this->page > $totalPages) {
            $this->page = $totalPages;
        }

        $offset = ($this->page - 1) * $this->perPage;

        $rows = Db::getInstance()->executeS(
            'SELECT mm.id_manufacturer_map, mm.source_table, mm.source_manufacturer, mm.id_manufacturer, IFNULL(m.name, "") AS manufacturer_name '
            . 'FROM `' . _DB_PREFIX_ . 'azada_wholesaler_pro_manufacturer_map` mm '
            . 'LEFT JOIN `' . _DB_PREFIX_ . 'manufacturer` m ON (m.id_manufacturer = mm.id_manufacturer) '
            . $whereSql .
            ' ORDER BY ' . $orderBy . ' ' . $orderWay .
            ' LIMIT ' . (int) $offset . ', ' . (int) $this->perPage
        );

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
        $autoEditId = (int) Tools::getValue('edit_mapping', 0);
        $modalAjaxEndpoint = AdminController::$currentIndex . '&token=' . $this->token . '&ajax=1&action=getEditModal';

        $loadingLabel = $this->l('Ładowanie formularza...');
        $errorLabel = $this->l('Nie udało się pobrać formularza edycji.');
        $closeLabel = $this->l('Zamknij');

        $html = '<div class="modal fade" id="azadaManufacturerEditModal" tabindex="-1" role="dialog" aria-hidden="true">';
        $html .= '<div class="modal-dialog modal-lg" role="document" style="max-width:920px; width:92%;">';
        $html .= '<div class="modal-content">';
        $html .= '<div class="modal-header">';
        $html .= '<button type="button" class="close" data-dismiss="modal" aria-label="' . htmlspecialchars($closeLabel, ENT_QUOTES, 'UTF-8') . '"><span aria-hidden="true">&times;</span></button>';
        $html .= '<h4 class="modal-title"><i class="icon-edit"></i> ' . $this->l('Edycja mapowania producenta') . '</h4>';
        $html .= '</div>';
        $html .= '<div class="modal-body" id="azadaManufacturerEditModalBody">';
        $html .= '<div class="text-center" style="padding:18px; color:#7f8c8d;"><i class="icon-refresh icon-spin"></i> ' . htmlspecialchars($loadingLabel, ENT_QUOTES, 'UTF-8') . '</div>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';

        $html .= '<script>';
        $html .= '(function($){';
        $html .= 'var loadingHtml = ' . json_encode('<div class="text-center" style="padding:18px; color:#7f8c8d;"><i class="icon-refresh icon-spin"></i> ' . htmlspecialchars($loadingLabel, ENT_QUOTES, 'UTF-8') . '</div>') . ';';
        $html .= 'var errorHtml = ' . json_encode('<div class="alert alert-danger">' . htmlspecialchars($errorLabel, ENT_QUOTES, 'UTF-8') . '</div>') . ';';
        $html .= 'function initManufacturerSelect($modal){';
        $html .= 'var $body = $("#azadaManufacturerEditModalBody");';
        $html .= 'var $select = $body.find("select.js-azada-manufacturer-select");';
        $html .= 'if (!$select.length) { return; }';
        $html .= 'if ($.fn && $.fn.select2) {';
        $html .= 'try { if ($select.data("select2")) { $select.select2("destroy"); } } catch(e) {}';
        $html .= '$select.select2({width:"100%", dropdownParent: $modal, minimumResultsForSearch:0});';
        $html .= '} else {';
        $html .= 'if ($body.find(".js-azada-manufacturer-filter").length) { return; }';
        $html .= 'var $filter = $("<input/>",{type:"text","class":"form-control js-azada-manufacturer-filter",placeholder:"Szukaj producenta w PrestaShop...",style:"margin-bottom:8px;"});';
        $html .= '$select.before($filter);';
        $html .= 'var $list = $("<div/>",{class:"list-group js-azada-manufacturer-suggest",style:"display:none; max-height:240px; overflow:auto; border:1px solid #d3d8db; border-radius:4px; margin-bottom:8px;"});';
        $html .= '$filter.after($list);';
        $html .= 'var all = []; $select.find("option").each(function(){ all.push({v:this.value,t:$(this).text()}); });';
        $html .= '$select.data("azadaAllOptions", all);';
        $html .= 'function renderList(items, term){';
        $html .= '$list.empty();';
        $html .= 'if (!term) { $list.hide(); return; }';
        $html .= 'if (!items || !items.length) {';
        $html .= '$list.append($("<div/>",{class:"list-group-item disabled",text:"Brak wyników"}));';
        $html .= '$list.show();';
        $html .= 'return;';
        $html .= '}';
        $html .= 'for (var i=0;i<items.length;i++){' ;
        $html .= 'var it = items[i];';
        $html .= 'var $a = $("<a/>",{href:"#",class:"list-group-item js-azada-manufacturer-pick"}).text(it.t).attr("data-val", it.v);';
        $html .= '$list.append($a);';
        $html .= '}';
        $html .= '$list.show();';
        $html .= '}';
        $html .= 'function applyAzadaFilter(){';
        $html .= 'var term = $.trim($filter.val()).toLowerCase();';
        $html .= 'var opts = $select.data("azadaAllOptions") || [];';
        $html .= 'if (term === "") { renderList([], ""); return; }';
        $html .= 'var items = [];';
        $html .= 'for (var i=0;i<opts.length;i++){' ;
        $html .= 'var o = opts[i];';
        $html .= 'if (!o || !o.v || o.v==="0") { continue; }';
        $html .= 'var txt = (o.t || "").toLowerCase();';
        $html .= 'if (txt.indexOf(term)!==-1) { items.push(o); if (items.length>=20) { break; } }';
        $html .= '}';
        $html .= 'renderList(items, term);';
        $html .= '}';
        $html .= '$filter.on("input", function(){ applyAzadaFilter(); });';
        $html .= '$filter.on("keydown", function(e){ if (e.which===13) { var $first = $list.find(".js-azada-manufacturer-pick").first(); if ($first.length) { $first.trigger("click"); e.preventDefault(); } } if (e.which===27) { $list.hide(); } });';
        $html .= '$list.on("click", ".js-azada-manufacturer-pick", function(e){ e.preventDefault(); var v=$(this).attr("data-val")||""; if (v==="") { return; } $select.val(v); $select.trigger("change"); $list.hide(); });';
        $html .= '$(document).off("click.azadaManufSuggest").on("click.azadaManufSuggest", function(e){ if ($(e.target).closest(".js-azada-manufacturer-filter, .js-azada-manufacturer-suggest").length===0) { $list.hide(); } });';
        $html .= 'applyAzadaFilter();';
        $html .= '}';
        $html .= '}';
        $html .= 'function loadEditModal(mapId){';
        $html .= 'if (!mapId) { return; }';
        $html .= 'var endpoint = ' . json_encode($modalAjaxEndpoint) . ' + "&id_manufacturer_map=" + encodeURIComponent(mapId);';
        $html .= 'var $modal = $("#azadaManufacturerEditModal");';
        $html .= 'var $body = $("#azadaManufacturerEditModalBody");';
        $html .= '$body.html(loadingHtml);';
        $html .= '$modal.modal("show");';
        $html .= '$.get(endpoint).done(function(markup){ $body.html(markup); initManufacturerSelect($modal); }).fail(function(){ $body.html(errorHtml); });';
        $html .= '}';
        $html .= '$(document).off("click.azadaManufactEdit", ".js-azada-manufact-edit").on("click.azadaManufactEdit", ".js-azada-manufact-edit", function(e){';
        $html .= 'e.preventDefault();';
        $html .= 'var url = $(this).data("edit-url") || $(this).attr("href");';
        $html .= 'if (!url) { return; }';
        $html .= 'var idMatch = /[?&](?:id_manufacturer_map|edit_mapping)=([0-9]+)/.exec(url);';
        $html .= 'var mapId = idMatch ? idMatch[1] : "";';
        $html .= 'loadEditModal(mapId);';
        $html .= '});';
        $html .= 'var openId = ' . (int) $autoEditId . ';';
        $html .= 'if (openId > 0) { loadEditModal(openId); }';
        $html .= '})(jQuery);';
        $html .= '</script>';

        return $html;
    }

    private function renderEditModalContent($idManufacturerMap)
    {
        $idManufacturerMap = (int) $idManufacturerMap;
        if ($idManufacturerMap <= 0) {
            return '<div class="alert alert-danger">' . $this->l('Brak ID mapowania.') . '</div>';
        }

        $row = Db::getInstance()->getRow(
            'SELECT id_manufacturer_map, source_table, source_manufacturer, id_manufacturer '
            . 'FROM `' . _DB_PREFIX_ . 'azada_wholesaler_pro_manufacturer_map` '
            . 'WHERE id_manufacturer_map=' . (int) $idManufacturerMap
        );
        if (!is_array($row) || empty($row)) {
            return '<div class="alert alert-danger">' . $this->l('Nie znaleziono rekordu mapowania.') . '</div>';
        }

        $sourceTable = (string) $row['source_table'];
        $sourceManufacturer = (string) $row['source_manufacturer'];
        $selectedIdManufacturer = (int) $row['id_manufacturer'];

        $manufacturers = Db::getInstance()->executeS('SELECT id_manufacturer, name FROM `' . _DB_PREFIX_ . 'manufacturer` ORDER BY name ASC');
        if (!is_array($manufacturers)) {
            $manufacturers = [];
        }

        $actionUrl = $this->buildFilterAwareUrl(['edit_mapping' => $idManufacturerMap]);

        $html = '<form method="post" action="' . htmlspecialchars($actionUrl, ENT_QUOTES, 'UTF-8') . '">';
        $html .= '<input type="hidden" name="id_manufacturer_map" value="' . (int) $idManufacturerMap . '" />';

        $html .= '<div class="row">';
        $html .= '<div class="col-md-6">';
        $html .= '<div class="form-group">';
        $html .= '<label>' . $this->l('Producent (hurtownia)') . '</label>';
        $html .= '<input type="text" class="form-control" disabled value="' . htmlspecialchars($sourceManufacturer, ENT_QUOTES, 'UTF-8') . '" />';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '<div class="col-md-6">';
        $html .= '<div class="form-group">';
        $html .= '<label>' . $this->l('Hurtownia') . '</label>';
        $html .= '<input type="text" class="form-control" disabled value="' . htmlspecialchars($this->prettyWholesalerName($sourceTable), ENT_QUOTES, 'UTF-8') . '" />';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';

        $html .= '<div class="form-group">';
        $html .= '<label>' . $this->l('Producent w PrestaShop') . '</label>';
        $html .= '<select name="id_manufacturer" class="form-control js-azada-manufacturer-select">';
        $html .= '<option value="0">' . $this->l('-- Brak przypisania --') . '</option>';
        foreach ($manufacturers as $m) {
            $idM = isset($m['id_manufacturer']) ? (int) $m['id_manufacturer'] : 0;
            $name = isset($m['name']) ? (string) $m['name'] : '';
            if ($idM <= 0 || $name === '') {
                continue;
            }
            $selected = ($idM === $selectedIdManufacturer) ? ' selected="selected"' : '';
            $html .= '<option value="' . (int) $idM . '"' . $selected . '>' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . ' (#' . (int) $idM . ')</option>';
        }
        $html .= '</select>';
        $html .= '<p class="help-block" style="margin:6px 0 0;">' . $this->l('Jeśli wybierzesz „Brak przypisania”, moduł może utworzyć producenta automatycznie podczas tworzenia produktu (tak jak wcześniej).') . '</p>';
        $html .= '</div>';

        $html .= '<div style="display:flex; justify-content:space-between; align-items:center; gap:10px; margin-top:14px;">';
        $html .= '<button type="button" class="btn btn-default" data-dismiss="modal"><i class="icon-remove"></i> ' . $this->l('Anuluj') . '</button>';
        $html .= '<div style="display:flex; gap:8px;">';
        $html .= '<button type="submit" name="submitAzadaManufacturerMapCreateAndAssign" class="btn btn-default"><i class="icon-plus"></i> ' . $this->l('Utwórz w PrestaShop i przypisz') . '</button>';
        $html .= '<button type="submit" name="submitAzadaManufacturerMapSave" class="btn btn-primary"><i class="icon-save"></i> ' . $this->l('Zapisz przypisanie') . '</button>';
        $html .= '</div>';
        $html .= '</div>';

        $html .= '</form>';

        return $html;
    }

    private function saveMapping()
    {
        $idManufacturerMap = (int) Tools::getValue('id_manufacturer_map');
        $idManufacturer = (int) Tools::getValue('id_manufacturer');

        if ($idManufacturerMap <= 0) {
            $this->errors[] = $this->l('Brak ID mapowania.');
            return false;
        }

        if ($idManufacturer > 0) {
            $exists = (int) Db::getInstance()->getValue(
                'SELECT id_manufacturer FROM `' . _DB_PREFIX_ . 'manufacturer` WHERE id_manufacturer=' . (int) $idManufacturer
            );
            if ($exists <= 0) {
                $this->errors[] = $this->l('Wybrany producent nie istnieje w PrestaShop.');
                return false;
            }
        }

        return (bool) Db::getInstance()->update('azada_wholesaler_pro_manufacturer_map', [
            'id_manufacturer' => (int) $idManufacturer,
            'date_upd' => date('Y-m-d H:i:s'),
        ], 'id_manufacturer_map=' . (int) $idManufacturerMap);
    }

    private function createAndAssignManufacturer()
    {
        $idManufacturerMap = (int) Tools::getValue('id_manufacturer_map');
        if ($idManufacturerMap <= 0) {
            $this->errors[] = $this->l('Brak ID mapowania.');
            return false;
        }

        $row = Db::getInstance()->getRow(
            'SELECT source_table, source_manufacturer FROM `' . _DB_PREFIX_ . 'azada_wholesaler_pro_manufacturer_map` '
            . 'WHERE id_manufacturer_map=' . (int) $idManufacturerMap
        );
        if (!is_array($row) || empty($row)) {
            $this->errors[] = $this->l('Nie znaleziono rekordu mapowania.');
            return false;
        }

        $sourceTable = isset($row['source_table']) ? (string) $row['source_table'] : '';
        $brand = isset($row['source_manufacturer']) ? (string) $row['source_manufacturer'] : '';
        $idShop = (int) $this->context->shop->id;

        $idManufacturer = (int) AzadaManufacturerImportMatcher::resolveManufacturerId($sourceTable, $brand, $idShop);
        if ($idManufacturer <= 0) {
            $this->errors[] = $this->l('Nie udało się utworzyć / przypisać producenta.');
            return false;
        }

        // Ensure mapping points to the resolved manufacturer
        return (bool) Db::getInstance()->update('azada_wholesaler_pro_manufacturer_map', [
            'id_manufacturer' => (int) $idManufacturer,
            'date_upd' => date('Y-m-d H:i:s'),
        ], 'id_manufacturer_map=' . (int) $idManufacturerMap);
    }

    private function syncSourceManufacturers()
    {
        // Count before
        $before = (int) Db::getInstance()->getValue('SELECT COUNT(*) FROM `' . _DB_PREFIX_ . 'azada_wholesaler_pro_manufacturer_map`');

        $tables = Db::getInstance()->executeS("SHOW TABLES LIKE '" . pSQL(_DB_PREFIX_) . "azada_raw_%'");
        if (!is_array($tables)) {
            return 0;
        }

        foreach ($tables as $tableRow) {
            $fullTable = (string) reset($tableRow);
            $sourceTable = preg_replace('/^' . preg_quote(_DB_PREFIX_, '/') . '/', '', $fullTable);

            if ($sourceTable === 'azada_raw_search_index' || preg_match('/_(source|conversion)$/', $sourceTable)) {
                continue;
            }

            $brandColumn = $this->detectBrandColumn($fullTable);
            if ($brandColumn === '') {
                continue;
            }

            $brands = Db::getInstance()->executeS(
                "SELECT DISTINCT TRIM(`" . bqSQL($brandColumn) . "`) AS brand_name\n                 FROM `" . bqSQL($fullTable) . "`\n                 WHERE TRIM(IFNULL(`" . bqSQL($brandColumn) . "`, '')) <> ''"
            );
            if (!is_array($brands) || empty($brands)) {
                continue;
            }

            foreach ($brands as $b) {
                $brand = isset($b['brand_name']) ? trim((string) $b['brand_name']) : '';
                if ($brand === '') {
                    continue;
                }
                $brand = Tools::substr($brand, 0, 255);
                $key = AzadaManufacturerImportMatcher::normalizeKey($brand);
                if ($key === '') {
                    continue;
                }

                // Unique key prevents duplicates
                Db::getInstance()->execute(
                    "INSERT IGNORE INTO `" . bqSQL(_DB_PREFIX_ . 'azada_wholesaler_pro_manufacturer_map') . "`
                        (`source_table`, `source_manufacturer`, `source_manufacturer_key`, `id_manufacturer`, `date_add`, `date_upd`)
                     VALUES (
                        '" . pSQL($sourceTable) . "',
                        '" . pSQL($brand) . "',
                        '" . pSQL($key) . "',
                        0,
                        '" . pSQL(date('Y-m-d H:i:s')) . "',
                        '" . pSQL(date('Y-m-d H:i:s')) . "'
                     )"
                );
            }
        }

        $after = (int) Db::getInstance()->getValue('SELECT COUNT(*) FROM `' . _DB_PREFIX_ . 'azada_wholesaler_pro_manufacturer_map`');
        $added = $after - $before;
        if ($added < 0) {
            $added = 0;
        }
        return (int) $added;
    }

    /**
     * Automatyczne dopasowanie producentów (1:1) do istniejących producentów w PrestaShop.
     *
     * Zasada: dopasowujemy TYLKO po pełnej nazwie (po TRIM), bez tworzenia nowych producentów.
     * Jeśli producent nie istnieje lub nazwa nie jest jednoznaczna (duplikaty) – pomijamy.
     *
     * @return int liczba zmapowanych rekordów
     */
    private function autoMapExactMatches()
    {
        $now = date('Y-m-d H:i:s');

        // Map only when the manufacturer exists in PrestaShop AND the name is unique (1:1).
        // Matching is done by TRIM(name) without creating new manufacturers.
        $mapTable = bqSQL(_DB_PREFIX_ . 'azada_wholesaler_pro_manufacturer_map');
        $manufacturerTable = bqSQL(_DB_PREFIX_ . 'manufacturer');
        $nowSql = pSQL($now);

        $sql = "UPDATE `{$mapTable}` mm
            INNER JOIN (
                SELECT TRIM(name) AS name_trim, MIN(id_manufacturer) AS id_manufacturer
                FROM `{$manufacturerTable}`
                WHERE TRIM(name) <> ''
                GROUP BY TRIM(name)
                HAVING COUNT(*) = 1
            ) m ON (TRIM(mm.source_manufacturer) = m.name_trim)
            SET mm.id_manufacturer = m.id_manufacturer,
                mm.date_upd = '{$nowSql}'
            WHERE (mm.id_manufacturer = 0 OR mm.id_manufacturer IS NULL)
              AND TRIM(IFNULL(mm.source_manufacturer, '')) <> ''";

        Db::getInstance()->execute($sql);

        // Ilość rekordów, które zostały uzupełnione (zmapowane).
        if (method_exists(Db::getInstance(), 'Affected_Rows')) {
            return (int) Db::getInstance()->Affected_Rows();
        }

        return 0;
    }

    private function detectBrandColumn($fullTable)
    {
        $db = Db::getInstance();
        $columns = $db->executeS("SHOW COLUMNS FROM `" . bqSQL($fullTable) . "`");
        if (!is_array($columns)) {
            return '';
        }

        $available = [];
        foreach ($columns as $c) {
            if (!isset($c['Field'])) {
                continue;
            }
            $field = trim((string) $c['Field']);
            if ($field === '') {
                continue;
            }
            $available[Tools::strtolower($field)] = $field;
        }

        $candidates = [
            'marka',
            'brand',
            'producent',
            'producer',
            'manufacturer',
            'nazwa_producenta',
            'producent_nazwa',
        ];

        foreach ($candidates as $cand) {
            if (isset($available[$cand])) {
                return $available[$cand];
            }
        }

        return '';
    }

    private function prettyWholesalerName($sourceTable)
    {
        $name = str_replace('azada_raw_', '', (string) $sourceTable);
        if ($name === '') {
            return '-';
        }

        return ucfirst($name);
    }
}
