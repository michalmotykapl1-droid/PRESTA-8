<?php

class AzadaCategoryMapEditModalRenderer
{
    /** @var AdminAzadaCategoryMapController */
    private $controller;

    public function __construct($controller)
    {
        $this->controller = $controller;
    }

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
        $html .= '#azadaCategoryEditModal .azada-tree-wrap { max-height: 420px; overflow: auto; border: 1px solid #d3d8db; padding: 10px; background: #fff; border-radius: 6px; }';
        $html .= '#azadaCategoryEditModal .azada-modal-actions { display:flex; justify-content:space-between; align-items:center; margin-top:16px; }';
        $html .= '#azadaCategoryEditModal .azada-top-controls { margin-bottom: 12px; }';
        $html .= '#azadaCategoryEditModal .azada-import-top { text-align:right; }';
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

    public function renderContent($idMapping, $sourceCategory, $wholesalerName, $treeHtml, $categoryOptionsHtml, ...$extraArgs)
    {

        $categoryMarkupPercent = '0.00';
        $isEnabled = false;
        $actionUrl = '';
        $categoryLabelsById = [];
        $initialSelectedCategoryIds = [];

        // Obsługa różnych wersji wywołania renderContent (stare/nowe kontrolery).
        // Wersja standardowa:
        //   (..., $isEnabled, $actionUrl, $categoryLabelsById, $initialSelectedCategoryIds)
        // Wersja rozszerzona (z narzutem):
        //   (..., $categoryMarkupPercent, $isEnabled, $actionUrl, $categoryLabelsById, $initialSelectedCategoryIds)
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

        $treeSelector = $this->detectTreeSelector($treeHtml, (int)$idMapping);

        $html .= '<div class="form-group">';
        $html .= '<label>' . $this->translate('Kategorie w sklepie (drzewo)') . '</label>';
        $html .= '<div class="azada-tree-wrap" data-tree-selector="' . $this->escape($treeSelector) . '">' . $treeHtml . '</div>';
        $html .= '<p class="help-block">' . $this->translate('Możesz zaznaczyć wiele kategorii sklepu. Przypisanie zostanie zapisane w bazie i użyte w kolejnych synchronizacjach.') . '</p>';
        $html .= '<p class="help-block text-primary js-azada-selected-count" style="font-weight:600;"></p>';
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

        $html .= '<script>';
        $html .= '(function($){';
        $html .= 'var labelsById = ' . json_encode($categoryLabelsById) . ';';
        $html .= 'var initialSelectedIds = ' . json_encode(array_values(array_map('intval', $initialSelectedCategoryIds))) . ';';
        $html .= 'var countLabelTemplate = ' . json_encode($this->translate('Przypisano do %d kategorii sklepu.')) . ';';
        $html .= 'var $modal = $("#azadaCategoryEditModal");';
        $html .= 'var $form = $modal.find("form");';
        $html .= 'if (!$form.length) { return; }';
        $html .= 'var $defaultSelect = $form.find("select[name=\\"id_category_default\\"]");';
        $html .= 'var $countInfo = $form.find(".js-azada-selected-count");';
        $html .= 'var $treeWrap = $form.find(".azada-tree-wrap");';
        $html .= 'var checkboxSelector = ".azada-tree-wrap input[type=\\"checkbox\\"]";';
        $html .= 'var categoryCheckboxSelector = "input[name=\'ps_categories[]\'], input[name=\'categoryBox[]\'], input[name=\'checkBoxShopAsso_category[]\'], input[name=\'checkBoxShopAsso_categories[]\']";';
        $html .= 'var manualDefaultOverride = false;';
        $html .= 'var $selectedJson = $form.find("input[name=\"azada_selected_categories_json\"]");';
        $html .= 'function parseNumeric(value){';
        $html .= 'var text = String(value || "");';
        $html .= 'if (!text) { return 0; }';
        $html .= 'if (/^\\d+$/.test(text)) { return parseInt(text, 10); }';
        $html .= 'var bracketMatch = text.match(/\\[(\\d+)\\]/);';
        $html .= 'if (bracketMatch) { return parseInt(bracketMatch[1], 10); }';
        $html .= 'var matches = text.match(/(\\d+)/g);';
        $html .= 'if (!matches || !matches.length) { return 0; }';
        $html .= 'return parseInt(matches[matches.length - 1], 10) || 0;';
        $html .= '}';
        $html .= 'function extractNumericId($checkbox){';
        $html .= 'var candidates = [';
        $html .= '$checkbox.val(),';
        $html .= '$checkbox.attr("data-id-category"),';
        $html .= '$checkbox.attr("data-id_category"),';
        $html .= '$checkbox.attr("data-id"),';
        $html .= '$checkbox.attr("name"),';
        $html .= '$checkbox.attr("id")';
        $html .= '];';
        $html .= 'var $li = $checkbox.closest("li");';
        $html .= 'if ($li.length) {';
        $html .= 'candidates.push($li.attr("data-id-category"));';
        $html .= 'candidates.push($li.attr("data-id_category"));';
        $html .= 'candidates.push($li.attr("data-id"));';
        $html .= 'candidates.push($li.attr("id"));';
        $html .= '}';
        $html .= 'for (var i = 0; i < candidates.length; i++) {';
        $html .= 'var parsed = parseNumeric(candidates[i]);';
        $html .= 'if (parsed > 0) { return parsed; }';
        $html .= '}';
        $html .= 'return 0;';
        $html .= '}';
        $html .= 'function extractLabelFromCheckbox($checkbox, fallbackId){';
        $html .= 'if (labelsById[fallbackId]) { return labelsById[fallbackId]; }';
        $html .= 'var $li = $checkbox.closest("li");';
        $html .= 'if ($li.length) {';
        $html .= 'var text = $.trim($li.text());';
        $html .= 'if (text) { return text.replace(/^[\\s\\-–—•·]+/g, "").replace(/\\s+/g, " "); }';
        $html .= '}';
        $html .= 'return String(fallbackId);';
        $html .= '}';
        $html .= 'var useInitialFallback = true;';
        $html .= 'function getInitialSelectedItems(){';
        $html .= 'var items = [];';
        $html .= 'var seen = {};';
        $html .= '$.each(initialSelectedIds, function(_, rawId){';
        $html .= 'var id = parseInt(rawId, 10) || 0;';
        $html .= 'if (id <= 0 || seen[id]) { return; }';
        $html .= 'seen[id] = true;';
        $html .= 'items.push({ id: id, label: labelsById[id] ? labelsById[id] : ("ID: " + String(id)) });';
        $html .= '});';
        $html .= 'return items;';
        $html .= '}';
        $html .= 'function selectedCategoryData(){';
        $html .= 'var items = [];';
        $html .= 'var seen = {};';
        $html .= 'var $checked = $form.find(categoryCheckboxSelector + ":checked");';
        $html .= 'if (!$checked.length) {';
        $html .= '$checked = $form.find(checkboxSelector + ":checked");';
        $html .= '}';
        $html .= '$checked.each(function(){';
        $html .= 'var $cb = $(this);';
        $html .= 'var id = extractNumericId($cb);';
        $html .= 'if (id <= 0 || seen[id]) { return; }';
        $html .= 'seen[id] = true;';
        $html .= 'items.push({ id: id, label: extractLabelFromCheckbox($cb, id) });';
        $html .= '});';
        $html .= 'if (!items.length && useInitialFallback) {';
        $html .= 'return getInitialSelectedItems();';
        $html .= '}';
        $html .= 'return items;';
        $html .= '}';
        $html .= 'function syncSelectedJson(){';
        $html .= 'if (!$selectedJson.length) { return; }';
        $html .= 'var selectedItems = selectedCategoryData();';
        $html .= 'var selectedIds = $.map(selectedItems, function(item){ return parseInt(item.id, 10) || 0; });';
        $html .= 'selectedIds = $.grep(selectedIds, function(id){ return id > 1; });';
        $html .= '$selectedJson.val(JSON.stringify(selectedIds));';
        $html .= '}';
        $html .= 'function renderCountInfo(total){';
        $html .= 'if (!$countInfo.length) { return; }';
        $html .= '$countInfo.text(countLabelTemplate.replace("%d", String(total)));';
        $html .= '}';
        $html .= 'function normalizeLabel(text){';
        $html .= 'return $.trim(String(text || "")).toLowerCase().replace(/\\s+/g, " ");';
        $html .= '}';
        $html .= 'function findToolbarControl(type){';
        $html .= 'var labelMap = {';
        $html .= 'expand: ["rozwiń wszystkie", "rozwin wszystkie", "expand all", "open all"],';
        $html .= 'collapse: ["zwiń wszystkie", "zwin wszystkie", "collapse all", "close all"],';
        $html .= 'check: ["wybierz wszystkie", "zaznacz wszystko", "check all", "select all"],';
        $html .= 'uncheck: ["odznacz wszystko", "uncheck all", "deselect all"]';
        $html .= '};';
        $html .= 'var attrMap = {';
        $html .= 'expand: ["expand", "open", "show"],';
        $html .= 'collapse: ["collapse", "close", "hide"],';
        $html .= 'check: ["check", "select"],';
        $html .= 'uncheck: ["uncheck", "deselect"]';
        $html .= '};';
        $html .= 'var texts = labelMap[type] || [];';
        $html .= 'var attrs = attrMap[type] || [];';
        $html .= 'return $treeWrap.find("button, a, input[type=button], input[type=submit]").filter(function(){';
        $html .= 'var $el = $(this);';
        $html .= 'var haystack = [';
        $html .= '$el.attr("data-action"),';
        $html .= '$el.attr("data-role"),';
        $html .= '$el.attr("name"),';
        $html .= '$el.attr("id"),';
        $html .= '$el.attr("class"),';
        $html .= '$el.val(),';
        $html .= '$el.text()';
        $html .= '].join(" ").toLowerCase();';
        $html .= 'for (var i = 0; i < attrs.length; i++) {';
        $html .= 'if (haystack.indexOf(attrs[i]) !== -1) { return true; }';
        $html .= '}';
        $html .= 'var label = normalizeLabel($el.text() || $el.val());';
        $html .= 'for (var j = 0; j < texts.length; j++) {';
        $html .= 'if (label.indexOf(texts[j]) !== -1) { return true; }';
        $html .= '}';
        $html .= 'return false;';
        $html .= '});';
        $html .= '}';
        $html .= 'function treeRootSelector(){';
        $html .= 'var configuredSelector = String($treeWrap.attr("data-tree-selector") || "");';
        $html .= 'if (configuredSelector) { return configuredSelector; }';
        $html .= 'var $firstCheckbox = $form.find(checkboxSelector).first();';
        $html .= 'if (!$firstCheckbox.length) { return ""; }';
        $html .= 'var $treeRoot = $firstCheckbox.closest("ul[id^=\'azada_category_map_tree_\'], div[id^=\'azada_category_map_tree_\']");';
        $html .= 'if (!$treeRoot.length) {';
        $html .= '$treeRoot = $form.find("[id^=\'azada_category_map_tree_\']").first();';
        $html .= '}';
        $html .= 'if (!$treeRoot.length) { return ""; }';
        $html .= 'var id = $treeRoot.attr("id") || "";';
        $html .= 'if (!id) { return ""; }';
        $html .= 'return "#" + id;';
        $html .= '}';
        $html .= 'function runTreeAction(actionName){';
        $html .= 'var selector = treeRootSelector();';
        $html .= 'if (!selector || !$.fn || !$.fn.tree) { return; }';
        $html .= 'try { $(selector).tree(actionName); } catch (e) {}';
        $html .= '}';
        $html .= 'function setAllCheckboxesFallback(checked){';
        $html .= '$form.find(checkboxSelector).each(function(){';
        $html .= 'var $cb = $(this);';
        $html .= '$cb.prop("checked", !!checked);';
        $html .= 'if (checked) { $cb.attr("checked", "checked"); } else { $cb.removeAttr("checked"); }';
        $html .= '$cb.trigger("change");';
        $html .= '});';
        $html .= 'setTimeout(function(){ rebuildDefaultCategories(); syncSelectedJson(); }, 10);';
        $html .= '}';
        $html .= 'function bindToolbarFallback(){';
        $html .= 'var bindings = [';
        $html .= '{ type: "expand", handler: function(){ runTreeAction("expandAll"); } },';
        $html .= '{ type: "collapse", handler: function(){ runTreeAction("collapseAll"); } },';
        $html .= '{ type: "check", handler: function(){ setAllCheckboxesFallback(true); } },';
        $html .= '{ type: "uncheck", handler: function(){ setAllCheckboxesFallback(false); } }';
        $html .= '];';
        $html .= '$.each(bindings, function(_, item){';
        $html .= 'findToolbarControl(item.type).off("click.azadaToolbarFallback").on("click.azadaToolbarFallback", function(){';
        $html .= 'setTimeout(item.handler, 0);';
        $html .= '});';
        $html .= '});';
        $html .= '}';
        $html .= 'function rebuildDefaultCategories(){';
        $html .= 'if (!$defaultSelect.length) { return; }';
        $html .= 'var selectedItems = selectedCategoryData();';
        $html .= 'var selectedIds = $.map(selectedItems, function(item){ return item.id; });';
        $html .= 'selectedIds = $.grep(selectedIds, function(id){ return parseInt(id, 10) > 1; });';
        $html .= 'selectedItems = $.grep(selectedItems, function(item){ return parseInt(item.id, 10) > 1; });';
        $html .= 'var currentValue = parseInt($defaultSelect.val(), 10) || 0;';
        $html .= 'var currentStillValid = selectedIds.indexOf(currentValue) !== -1;';
        $html .= 'var html = "";';
        $html .= '$.each(selectedItems, function(_, item){';
        $html .= 'html += "<option value=\\"" + item.id + "\\">" + $("<div>").text(item.label).html() + "</option>";';
        $html .= '});';
        $html .= '$defaultSelect.html(html);';
        $html .= 'if (selectedIds.length === 0) {';
        $html .= '$defaultSelect.val("");';
        $html .= 'manualDefaultOverride = false;';
        $html .= '} else if (currentStillValid) {';
        $html .= '$defaultSelect.val(String(currentValue));';
        $html .= '} else {';
        $html .= '$defaultSelect.val(String(selectedIds[0]));';
        $html .= '}';
        $html .= 'renderCountInfo(selectedIds.length);';
        $html .= '}';
        $html .= '$defaultSelect.off("change.azadaDefaultManual").on("change.azadaDefaultManual", function(){';
        $html .= 'manualDefaultOverride = true;';
        $html .= '});';
        $html .= '$form.off("change.azadaDefaultSync", checkboxSelector).on("change.azadaDefaultSync", checkboxSelector, function(){';
        $html .= 'useInitialFallback = false;';
        $html .= 'if (!selectedCategoryData().length) {';
        $html .= 'manualDefaultOverride = false;';
        $html .= '}';
        $html .= 'setTimeout(function(){ rebuildDefaultCategories(); syncSelectedJson(); }, 25);';
        $html .= '});';
        $html .= 'bindToolbarFallback();';
        $html .= 'rebuildDefaultCategories();';
        $html .= 'syncSelectedJson();';
        $html .= '$form.off("submit.azadaSelectedSync").on("submit.azadaSelectedSync", function(){ syncSelectedJson(); });';
        $html .= '})(jQuery);';
        $html .= '</script>';

        return $html;
    }


    private function detectTreeSelector($treeHtml, $idMapping)
    {
        $fallbackSelector = '#azada_category_map_tree_' . (int)$idMapping;
        $html = (string)$treeHtml;

        if (preg_match("/\\$\\(\\s*[\\\"'](#?[a-zA-Z0-9_-]+)[\\\"']\\s*\\)\\.tree\\s*\\(/", $html, $matches)) {
            $selector = (string)$matches[1];
            if ($selector !== '') {
                return strpos($selector, '#') === 0 ? $selector : ('#' . $selector);
            }
        }

        if (preg_match("/\\bid=[\\\"']([^\\\"']*azada_category_map_tree_[^\\\"']*)[\\\"']/", $html, $matches)) {
            $id = trim((string)$matches[1]);
            if ($id !== '') {
                return '#' . $id;
            }
        }

        return $fallbackSelector;
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
