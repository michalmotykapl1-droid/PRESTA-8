<?php

class AzadaCategoryMapEditModalRenderer
{
    /** @var AdminAzadaCategoryMapController */
    private $controller;

    public function __construct($controller)
    {
        $this->controller = $controller;
    }

    /**
     * Modal shell (ładowanie AJAX). Ten kod jest wstrzykiwany raz na stronę listy.
     */
    public function renderShell($token, $autoEditId)
    {
        $loadingLabel = $this->translate('Ładowanie formularza...');
        $errorLabel = $this->translate('Nie udało się pobrać formularza edycji.');
        $closeLabel = $this->translate('Zamknij');
        $modalAjaxEndpoint = AdminController::$currentIndex . '&token=' . $token . '&ajax=1&action=getEditModal';

        $html = '<div class="modal fade" id="azadaCategoryEditModal" tabindex="-1" role="dialog" aria-hidden="true">';
        $html .= '<div class="modal-dialog modal-lg" role="document" style="max-width:1180px; width:94%;">';
        $html .= '<div class="modal-content">';
        $html .= '<div class="modal-header">';
        $html .= '<button type="button" class="close" data-dismiss="modal" aria-label="' . $this->escape($closeLabel) . '"><span aria-hidden="true">&times;</span></button>';
        $html .= '<h4 class="modal-title"><i class="icon-edit"></i> ' . $this->translate('Edycja mapowania kategorii') . '</h4>';
        $html .= '</div>';
        $html .= '<div class="modal-body" id="azadaCategoryEditModalBody">';
        $html .= '<div class="text-center" style="padding:18px; color:#7f8c8d;"><i class="icon-refresh icon-spin"></i> ' . $loadingLabel . '</div>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';

        $html .= '<style>';
        $html .= '#azadaCategoryEditModal .modal-content { border-radius: 10px; }';
        $html .= '#azadaCategoryEditModal .modal-header { background: #f7f9fb; border-bottom: 1px solid #dfe6ec; }';
        $html .= '#azadaCategoryEditModal .modal-body { max-height: none; overflow: visible; }';
        $html .= '#azadaCategoryEditModal .azada-modal-actions { display:flex; justify-content:space-between; align-items:center; margin-top:16px; }';
        $html .= '#azadaCategoryEditModal .azada-top-controls { margin-bottom: 12px; }';
        $html .= '#azadaCategoryEditModal .azada-import-top { text-align:right; }';

        // Picker UI
        $html .= '#azadaCategoryEditModal .azada-picker-wrap { border:1px solid #d3d8db; background:#fff; border-radius:6px; padding:10px; }';
        $html .= '#azadaCategoryEditModal .azada-picker-search { position:relative; }';
        $html .= '#azadaCategoryEditModal .azada-picker-results { position:absolute; z-index:50; left:0; right:0; top:100%; margin-top:6px; max-height:260px; overflow:auto; border:1px solid #d3d8db; border-radius:6px; background:#fff; box-shadow:0 6px 18px rgba(0,0,0,.08); display:none; }';
        $html .= '#azadaCategoryEditModal .azada-picker-results .item { padding:8px 10px; cursor:pointer; border-bottom:1px solid #eef2f5; }';
        $html .= '#azadaCategoryEditModal .azada-picker-results .item:last-child { border-bottom:none; }';
        $html .= '#azadaCategoryEditModal .azada-picker-results .item:hover { background:#f7f9fb; }';
        $html .= '#azadaCategoryEditModal .azada-chip { display:inline-flex; align-items:center; gap:8px; padding:7px 10px; border:1px solid #cfe3ff; background:#f3f8ff; border-radius:999px; margin:6px 6px 0 0; }';
        $html .= '#azadaCategoryEditModal .azada-chip .x { display:inline-block; width:18px; height:18px; line-height:16px; text-align:center; border-radius:50%; border:1px solid #9ec3ff; color:#2c6cd6; font-weight:700; cursor:pointer; }';
        $html .= '#azadaCategoryEditModal .azada-chip .x:hover { background:#e8f1ff; }';

        // Custom tree
        $html .= '#azadaCategoryEditModal .azada-structure { margin-top:12px; }';
        $html .= '#azadaCategoryEditModal .azada-structure-box { max-height:420px; overflow:auto; border:1px solid #d3d8db; border-radius:6px; background:#fff; }';
        $html .= '#azadaCategoryEditModal ul.azada-cat-tree { list-style:none; margin:0; padding:8px 10px; }';
        $html .= '#azadaCategoryEditModal ul.azada-cat-tree ul { list-style:none; margin:0; padding-left:18px; display:none; }';
        $html .= '#azadaCategoryEditModal li.azada-node { margin:2px 0; }';
        $html .= '#azadaCategoryEditModal .azada-row { display:flex; align-items:center; gap:8px; padding:4px 6px; border-radius:6px; }';
        $html .= '#azadaCategoryEditModal .azada-row:hover { background:#f7f9fb; }';
        $html .= '#azadaCategoryEditModal .azada-toggle { width:18px; height:18px; border:1px solid #d3d8db; border-radius:4px; display:inline-flex; align-items:center; justify-content:center; cursor:pointer; user-select:none; }';
        $html .= '#azadaCategoryEditModal .azada-toggle.empty { border-color:transparent; cursor:default; }';
        $html .= '#azadaCategoryEditModal .azada-name { cursor:pointer; user-select:none; }';
        $html .= '#azadaCategoryEditModal li.azada-node.is-open > ul { display:block; }';
        $html .= '#azadaCategoryEditModal li.azada-node.is-selected > .azada-row { background:#eef7ff; border:1px solid #cfe3ff; }';
        $html .= '#azadaCategoryEditModal .azada-hint { color:#7f8c8d; margin-top:8px; }';
        $html .= '</style>';

        $html .= '<script>';
        $html .= '(function($){';
        $html .= 'var loadingHtml = ' . json_encode('<div class="text-center" style="padding:18px; color:#7f8c8d;"><i class="icon-refresh icon-spin"></i> ' . $loadingLabel . '</div>') . ';';
        $html .= 'var errorHtml = ' . json_encode('<div class="alert alert-danger">' . $errorLabel . '</div>') . ';';
        $html .= 'function loadEditModal(mapId){';
        $html .= 'if (!mapId) { return; }';
        $html .= 'var endpoint = ' . json_encode($modalAjaxEndpoint) . ' + "&id_category_map=" + encodeURIComponent(mapId);';
        $html .= 'var $modal = $("#azadaCategoryEditModal");';
        $html .= 'var $body = $("#azadaCategoryEditModalBody");';
        $html .= '$body.html(loadingHtml);';
        $html .= '$modal.modal("show");';
        $html .= '$.get(endpoint).done(function(markup){ $body.html(markup); }).fail(function(){ $body.html(errorHtml); });';
        $html .= '}';
        $html .= '$(document).off("click.azadaEdit", ".js-azada-edit").on("click.azadaEdit", ".js-azada-edit", function(e){';
        $html .= 'e.preventDefault();';
        $html .= 'var url = $(this).data("edit-url") || $(this).attr("href");';
        $html .= 'if (!url) { return; }';
        $html .= 'var idMatch = /[?&](?:id_category_map|edit_mapping)=([0-9]+)/.exec(url);';
        $html .= 'var mapId = idMatch ? idMatch[1] : "";';
        $html .= 'loadEditModal(mapId);';
        $html .= '});';
        $html .= 'var openId = ' . (int)$autoEditId . ';';
        $html .= 'if (openId > 0) { loadEditModal(openId); }';
        $html .= '})(jQuery);';
        $html .= '</script>';

        return $html;
    }

    /**
     * Modal content (ładowane AJAX-em). Zamiast HelperTreeCategories (który w modalu AJAX miesza eventy),
     * używamy stabilnego pickera: wyszukiwarka + chipy + własna struktura (rozwiń/zwiń per węzeł).
     */
    public function renderContent($idMapping, $sourceCategory, $wholesalerName, $treeHtml, $categoryOptionsHtml, ...$extraArgs)
    {
        $categoryMarkupPercent = '0.00';
        $isEnabled = false;
        $actionUrl = '';
        $categoryLabelsById = [];
        $initialSelectedCategoryIds = [];

        // Obsługa różnych wersji wywołania renderContent (stare/nowe kontrolery).
        if (count($extraArgs) >= 4 && is_string($extraArgs[1])) {
            $isEnabled = ((int)$extraArgs[0] === 1);
            $actionUrl = (string)$extraArgs[1];
            $categoryLabelsById = is_array($extraArgs[2]) ? $extraArgs[2] : [];
            $initialSelectedCategoryIds = is_array($extraArgs[3]) ? $extraArgs[3] : [];
        } elseif (count($extraArgs) >= 5 && is_string($extraArgs[2])) {
            $categoryMarkupPercent = is_numeric($extraArgs[0]) ? number_format((float)$extraArgs[0], 2, '.', '') : '0.00';
            $isEnabled = ((int)$extraArgs[1] === 1);
            $actionUrl = (string)$extraArgs[2];
            $categoryLabelsById = is_array($extraArgs[3]) ? $extraArgs[3] : [];
            $initialSelectedCategoryIds = is_array($extraArgs[4]) ? $extraArgs[4] : [];
        }

        $idMapping = (int)$idMapping;
        $initialSelectedCategoryIds = array_values(array_filter(array_map('intval', (array)$initialSelectedCategoryIds), function ($id) {
            return $id > 1;
        }));

        $ctx = \Context::getContext();
        $idLang = isset($ctx->language) ? (int)$ctx->language->id : 1;
        $index = $this->buildCategoryIndex($idLang);
        $tree = $this->buildCustomTreeHtml($index, $this->getRootCategoryId());

        $html = '<form method="post" action="' . $this->escape($actionUrl) . '">';
        $html .= '<input type="hidden" name="id_category_map" value="' . (int)$idMapping . '" />';
        $html .= '<input type="hidden" name="azada_selected_categories_json" value="" />';

        $html .= '<div class="row">';
        $html .= '<div class="col-md-6">';
        $html .= '<div class="form-group">';
        $html .= '<label>' . $this->translate('Kategoria hurtowni') . '</label>';
        $html .= '<input type="text" class="form-control" disabled value="' . $this->escape($sourceCategory) . '" />';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '<div class="col-md-6">';
        $html .= '<div class="form-group">';
        $html .= '<label>' . $this->translate('Hurtownia') . '</label>';
        $html .= '<input type="text" class="form-control" disabled value="' . $this->escape($wholesalerName) . '" />';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';

        $html .= '<div class="row azada-top-controls">';
        $html .= '<div class="col-md-8">';
        $html .= '<div class="form-group">';
        $html .= '<label>' . $this->translate('Narzut kategorii (%)') . '</label>';
        $html .= '<input type="number" step="0.01" name="category_markup_percent" value="' . $this->escape($categoryMarkupPercent) . '" class="form-control" />';
        $html .= '<p class="help-block" style="margin:6px 0 0; font-weight:600;">' . $this->translate('Priorytet: narzut ustawiony tutaj ma najwyższy priorytet dla tej kategorii hurtowni i nadpisuje narzut z konfiguracji hurtowni.') . '</p>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '<div class="col-md-4 azada-import-top">';
        $html .= '<div class="form-group" style="display:inline-block; text-align:left;">';
        $html .= '<label class="control-label">' . $this->translate('Import aktywny') . '</label>';
        $html .= '<div class="switch prestashop-switch fixed-width-lg" style="margin-top:6px;">';
        $html .= '<input type="radio" name="is_active" id="is_active_on" value="1" ' . ($isEnabled ? 'checked="checked"' : '') . ' />';
        $html .= '<label for="is_active_on">' . $this->translate('Tak') . '</label>';
        $html .= '<input type="radio" name="is_active" id="is_active_off" value="0" ' . (!$isEnabled ? 'checked="checked"' : '') . ' />';
        $html .= '<label for="is_active_off">' . $this->translate('Nie') . '</label>';
        $html .= '<a class="slide-button btn"></a>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';

        // Picker
        $html .= '<div class="form-group">';
        $html .= '<label>' . $this->translate('Kategorie w sklepie') . '</label>';
        $html .= '<div class="azada-picker-wrap" data-map-id="' . (int)$idMapping . '">';
        $html .= '<div class="azada-picker-search">';
        $html .= '<input type="text" class="form-control js-azada-cat-search" placeholder="' . $this->escape($this->translate('Szukaj kategorii po nazwie / ścieżce...')) . '" autocomplete="off" />';
        $html .= '<div class="azada-picker-results js-azada-cat-results"></div>';
        $html .= '</div>';
        $html .= '<div class="js-azada-selected-chips" style="margin-top:8px;"></div>';
        $html .= '<div class="help-block text-primary js-azada-selected-count" style="font-weight:600; margin:8px 0 0;"></div>';
        $html .= '<div class="azada-hint">' . $this->translate('Dodaj/usuń kategorie klikając wynik wyszukiwania lub element w strukturze poniżej. Masowe akcje drzewa (Rozwiń/Zwiń/Wybierz/Odznacz) są wyłączone – w modalu AJAX powodowały konflikty i „znikanie” drzewa.') . '</div>';
        $html .= '</div>';
        $html .= '</div>';

        // Custom structure
        $html .= '<div class="form-group azada-structure">';
        $html .= '<label>' . $this->translate('Struktura kategorii') . '</label>';
        $html .= '<div class="azada-structure-box">' . $tree . '</div>';
        $html .= '</div>';

        // Default category
        $html .= '<div class="row">';
        $html .= '<div class="col-md-12">';
        $html .= '<div class="form-group">';
        $html .= '<label>' . $this->translate('Kategoria domyślna') . '</label>';
        $html .= '<select name="id_category_default" class="form-control">' . $categoryOptionsHtml . '</select>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';

        $html .= '<div class="azada-modal-actions">';
        $html .= '<button type="button" class="btn btn-default" data-dismiss="modal"><i class="icon-remove"></i> ' . $this->translate('Anuluj') . '</button>';
        $html .= '<button type="submit" name="submitAzadaCategoryMapSave" class="btn btn-primary"><i class="icon-save"></i> ' . $this->translate('Zapisz przypisanie') . '</button>';
        $html .= '</div>';
        $html .= '</form>';

        // Init script (odporne na wielokrotne otwarcia modala)
        $html .= '<script>';
        $html .= '(function($){';
        $html .= 'var $modal = $("#azadaCategoryEditModal");';
        $html .= 'var $form = $modal.find("form");';
        $html .= 'if (!$form.length) { return; }';
        $html .= 'var mapId = ' . (int)$idMapping . ';';
        $html .= 'var ns = ".azadaCatPicker" + String(mapId);';
        $html .= '$form.off(".azadaCatPicker"); $(document).off(".azadaCatPicker");';
        $html .= 'var categories = ' . json_encode(array_values($index['list'])) . ';';
        $html .= 'var byId = ' . json_encode($index['byId']) . ';';
        $html .= 'var parentById = ' . json_encode($index['parentById']) . ';';
        $html .= 'var initialSelected = ' . json_encode($initialSelectedCategoryIds) . ';';
        $html .= 'var countTpl = ' . json_encode($this->translate('Przypisano do %d kategorii sklepu.')) . ';';
        $html .= 'var $search = $form.find(".js-azada-cat-search");';
        $html .= 'var $results = $form.find(".js-azada-cat-results");';
        $html .= 'var $chips = $form.find(".js-azada-selected-chips");';
        $html .= 'var $count = $form.find(".js-azada-selected-count");';
        $html .= 'var $defaultSelect = $form.find("select[name=\\"id_category_default\\"]");';
        $html .= 'var $json = $form.find("input[name=\\"azada_selected_categories_json\\"]");';
        $html .= 'var selected = {};';
        $html .= '$.each(initialSelected, function(_, id){ id = parseInt(id,10)||0; if (id>1) selected[id]=true; });';
        $html .= 'function escapeHtml(s){ return $("<div>").text(String(s||"")).html(); }';
        $html .= 'function selectedIds(){ var ids=[]; $.each(selected,function(k){ var id=parseInt(k,10)||0; if(id>1) ids.push(id); }); ids.sort(function(a,b){return a-b;}); return ids; }';
        $html .= 'function selectedItems(){ var ids=selectedIds(); return $.map(ids,function(id){ var label=(byId[id]&&byId[id].label)?byId[id].label: ("ID: "+id); return {id:id,label:label}; }); }';
        $html .= 'function syncJson(){ if(!$json.length) return; $json.val(JSON.stringify(selectedIds())); }';
        $html .= 'function renderCount(){ var total=selectedIds().length; $count.text(countTpl.replace("%d", String(total))); }';
        $html .= 'function rebuildDefault(){ if(!$defaultSelect.length) return; var items=selectedItems(); var ids=$.map(items,function(it){return it.id;}); var current=parseInt($defaultSelect.val(),10)||0; var html="<option value=\"0\">-</option>"; $.each(items,function(_,it){ html += "<option value=\""+it.id+"\">"+escapeHtml(it.label)+"</option>"; }); $defaultSelect.html(html); if(ids.length===0){ $defaultSelect.val("0"); } else if(ids.indexOf(current)!==-1){ $defaultSelect.val(String(current)); } else { $defaultSelect.val(String(ids[0])); } }';
        $html .= 'function setTreeSelected(){ $form.find("li.azada-node").removeClass("is-selected"); $.each(selected,function(k){ var id=parseInt(k,10)||0; if(id>1){ $form.find("li.azada-node[data-id=\""+id+"\"]").addClass("is-selected"); } }); }';
        $html .= 'function expandAncestors(id){ var guard=0; var current=parseInt(id,10)||0; while(current>0 && guard<30){ var parent=parentById[current]; if(!parent || parent<=0) break; var $li=$form.find("li.azada-node[data-id=\""+parent+"\"]"); if($li.length){ $li.addClass("is-open"); } current=parent; guard++; } }';
        $html .= 'function renderChips(){ var items=selectedItems(); var html=""; $.each(items,function(_,it){ html += "<span class=\"azada-chip\" data-id=\""+it.id+"\"><span class=\"t\">"+escapeHtml(it.label)+"</span><span class=\"x js-azada-chip-remove\" title=\"Usuń\">×</span></span>"; }); $chips.html(html); }';
        $html .= 'function addId(id){ id=parseInt(id,10)||0; if(id<=1) return; if(selected[id]){ return; } selected[id]=true; expandAncestors(id); renderChips(); rebuildDefault(); renderCount(); setTreeSelected(); syncJson(); }';
        $html .= 'function removeId(id){ id=parseInt(id,10)||0; if(id<=1) return; if(!selected[id]){ return; } delete selected[id]; renderChips(); rebuildDefault(); renderCount(); setTreeSelected(); syncJson(); }';
        $html .= 'function toggleId(id){ id=parseInt(id,10)||0; if(id<=1) return; if(selected[id]) removeId(id); else addId(id); }';
        $html .= 'function showResults(items){ if(!items.length){ $results.hide().html(""); return; } var html=""; $.each(items,function(_,it){ html += "<div class=\"item\" data-id=\""+it.id+"\">"+escapeHtml(it.label)+"</div>"; }); $results.html(html).show(); }';
        $html .= 'function doSearch(q){ q=$.trim(String(q||"")); if(q.length<2){ showResults([]); return; } var nq=q.toLowerCase(); var matches=[]; for(var i=0;i<categories.length;i++){ var c=categories[i]; if(!c||!c.label) continue; if(String(c.label).toLowerCase().indexOf(nq)!==-1){ matches.push(c); if(matches.length>=25) break; } } showResults(matches); }';

        // Init
        $html .= 'renderChips(); rebuildDefault(); renderCount(); setTreeSelected(); syncJson();';
        $html .= '$.each(initialSelected,function(_,id){ expandAncestors(id); });';

        // Events
        $html .= '$form.on("input"+ns, ".js-azada-cat-search", function(){ doSearch($(this).val()); });';
        $html .= '$form.on("click"+ns, ".js-azada-cat-results .item", function(){ var id=$(this).data("id"); addId(id); $results.hide(); $search.val("").trigger("input"); });';
        $html .= '$form.on("click"+ns, ".js-azada-chip-remove", function(e){ e.preventDefault(); var id=$(this).closest(".azada-chip").data("id"); removeId(id); });';
        $html .= '$form.on("click"+ns, ".azada-toggle", function(e){ e.preventDefault(); var $li=$(this).closest("li.azada-node"); if($(this).hasClass("empty")) return; $li.toggleClass("is-open"); });';
        $html .= '$form.on("click"+ns, ".azada-name", function(e){ e.preventDefault(); var id=$(this).closest("li.azada-node").data("id"); toggleId(id); });';
        $html .= '$(document).on("click"+ns, function(e){ if(!$(e.target).closest(".azada-picker-search").length){ $results.hide(); } });';
        $html .= '$form.on("submit"+ns, function(){ syncJson(); });';
        $html .= '})(jQuery);';
        $html .= '</script>';

        return $html;
    }

    private function getRootCategoryId()
    {
        try {
            $root = Category::getRootCategory();
            return (int)$root->id;
        } catch (Exception $e) {
            return 1;
        }
    }

    /**
     * Buduje indeks kategorii do wyszukiwania oraz mapę rodziców.
     * Zwraca:
     *  - list: [{id,label}]
     *  - byId: {id:{id,parent,label}}
     *  - parentById: {id:parent}
     */
    private function buildCategoryIndex($idLang)
    {
        $idLang = (int)$idLang;
        // UWAGA: nie używamy literalnych "\\n" w SQL (w PHP w stringu pojedynczym to nie jest znak nowej linii,
        // tylko dwa znaki: backslash+n, co powoduje błąd składni SQL w MySQL).
        $sql = 'SELECT c.id_category, c.id_parent, c.level_depth, c.nleft, cl.name '
            . 'FROM `' . _DB_PREFIX_ . 'category` c '
            . 'INNER JOIN `' . _DB_PREFIX_ . 'category_lang` cl ON (cl.id_category=c.id_category AND cl.id_lang=' . (int)$idLang . ') '
            . 'ORDER BY c.nleft ASC';

        $rows = Db::getInstance()->executeS($sql);
        if (!is_array($rows)) {
            $rows = [];
        }

        // Wyliczamy pełną ścieżkę na podstawie level_depth (kolejność nleft daje poprawny stack).
        $stack = [];
        $byId = [];
        $parentById = [];
        $list = [];

        foreach ($rows as $row) {
            $id = (int)$row['id_category'];
            $parent = (int)$row['id_parent'];
            $depth = (int)$row['level_depth'];
            $name = (string)$row['name'];

            // Utrzymujemy stack długości = depth
            while (count($stack) > $depth) {
                array_pop($stack);
            }

            $stack[$depth] = $name;
            $path = array_slice($stack, 0, $depth + 1);

            // Nie pokazujemy surowego "Root" jako pierwszego elementu ścieżki.
            if (count($path) > 1 && (int)$row['level_depth'] === 0) {
                // noop
            }
            if (count($path) > 0 && $id === 1) {
                // Root
                $label = $name;
            } else {
                // Usuń pierwszy element jeśli wygląda na "Root" (id=1)
                // (nie mamy tu pewności nazwy, więc usuwamy tylko, gdy depth==0 i id==1 powyżej)
                $label = implode(' > ', $path);
            }

            $byId[$id] = [
                'id' => $id,
                'parent' => $parent,
                'name' => $name,
                'label' => $label,
            ];
            $parentById[$id] = $parent;

            if ($id > 1) {
                $list[] = [
                    'id' => $id,
                    'label' => $label,
                ];
            }
        }

        return [
            'list' => $list,
            'byId' => $byId,
            'parentById' => $parentById,
        ];
    }

    /**
     * Buduje własne, stabilne drzewo HTML (bez toolbaru i masowych akcji).
     */
    private function buildCustomTreeHtml(array $index, $rootId)
    {
        $rootId = (int)$rootId;
        $byId = isset($index['byId']) && is_array($index['byId']) ? $index['byId'] : [];

        // Budujemy listę dzieci.
        $children = [];
        foreach ($byId as $id => $row) {
            $id = (int)$id;
            $parent = isset($row['parent']) ? (int)$row['parent'] : 0;
            if (!isset($children[$parent])) {
                $children[$parent] = [];
            }
            $children[$parent][] = $id;
        }

        $renderNode = function ($id) use (&$renderNode, &$children, &$byId) {
            $id = (int)$id;
            $name = isset($byId[$id]['name']) ? (string)$byId[$id]['name'] : ('ID: ' . $id);
            $pathLabel = isset($byId[$id]['label']) ? (string)$byId[$id]['label'] : $name;
            $hasChildren = !empty($children[$id]);

            $toggle = $hasChildren
                ? '<span class="azada-toggle" title="Rozwiń/zwiń">+</span>'
                : '<span class="azada-toggle empty"></span>';

            $html = '<li class="azada-node" data-id="' . (int)$id . '">';
            $html .= '<div class="azada-row">' . $toggle . '<span class="azada-name" title="' . htmlspecialchars($pathLabel, ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '</span></div>';
            if ($hasChildren) {
                $html .= '<ul>';
                foreach ($children[$id] as $childId) {
                    // Root (id=1) i tak nie jest wybierany – ale renderujemy dzieci normalnie.
                    $html .= $renderNode($childId);
                }
                $html .= '</ul>';
            }
            $html .= '</li>';
            return $html;
        };

        $html = '<ul class="azada-cat-tree">';

        // Zazwyczaj root ma dziecko "Strona główna" (home). Renderujemy dzieci roota.
        $rootChildren = isset($children[$rootId]) ? $children[$rootId] : [];
        foreach ($rootChildren as $id) {
            // Nie pokazuj samego roota.
            $html .= $renderNode($id);
        }

        $html .= '</ul>';
        return $html;
    }

    private function translate($message)
    {
        if (isset($this->controller->module) && is_object($this->controller->module) && method_exists($this->controller->module, 'l')) {
            return (string)$this->controller->module->l($message);
        }
        return (string)$message;
    }

    private function escape($value)
    {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }
}
