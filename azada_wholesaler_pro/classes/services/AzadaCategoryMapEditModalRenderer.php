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
        $loadingLabel = $this->controller->l('Ładowanie formularza...');
        $errorLabel = $this->controller->l('Nie udało się pobrać formularza edycji.');
        $closeLabel = $this->controller->l('Zamknij');
        $modalAjaxEndpoint = AdminController::$currentIndex . '&token=' . $token . '&ajax=1&action=getEditModal';

        $html = '<div class="modal fade" id="azadaCategoryEditModal" tabindex="-1" role="dialog" aria-hidden="true">';
        $html .= '<div class="modal-dialog modal-lg" role="document" style="max-width:1180px; width:94%;">';
        $html .= '<div class="modal-content">';
        $html .= '<div class="modal-header">';
        $html .= '<button type="button" class="close" data-dismiss="modal" aria-label="' . $this->escape($closeLabel) . '"><span aria-hidden="true">&times;</span></button>';
        $html .= '<h4 class="modal-title"><i class="icon-edit"></i> ' . $this->controller->l('Edycja mapowania kategorii') . '</h4>';
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
        $html .= '#azadaCategoryEditModal .modal-body { max-height: 78vh; overflow: auto; }';
        $html .= '#azadaCategoryEditModal .azada-tree-wrap { max-height: 420px; overflow: auto; border: 1px solid #d3d8db; padding: 10px; background: #fff; border-radius: 6px; }';
        $html .= '#azadaCategoryEditModal .azada-modal-actions { display:flex; justify-content:space-between; align-items:center; margin-top:16px; }';
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

    public function renderContent($idMapping, $sourceCategory, $wholesalerName, $treeHtml, $categoryOptionsHtml, $isEnabled, $actionUrl)
    {
        $html = '<form method="post" action="' . $this->escape($actionUrl) . '">';
        $html .= '<input type="hidden" name="id_category_map" value="' . (int)$idMapping . '" />';

        $html .= '<div class="row">';
        $html .= '<div class="col-md-6">';
        $html .= '<div class="form-group">';
        $html .= '<label>' . $this->controller->l('Kategoria hurtowni') . '</label>';
        $html .= '<input type="text" class="form-control" disabled value="' . $this->escape($sourceCategory) . '" />';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '<div class="col-md-6">';
        $html .= '<div class="form-group">';
        $html .= '<label>' . $this->controller->l('Hurtownia') . '</label>';
        $html .= '<input type="text" class="form-control" disabled value="' . $this->escape($wholesalerName) . '" />';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';

        $html .= '<div class="form-group">';
        $html .= '<label>' . $this->controller->l('Kategorie w sklepie (drzewo)') . '</label>';
        $html .= '<div class="azada-tree-wrap">' . $treeHtml . '</div>';
        $html .= '<p class="help-block">' . $this->controller->l('Możesz zaznaczyć wiele kategorii sklepu. Przypisanie zostanie zapisane w bazie i użyte w kolejnych synchronizacjach.') . '</p>';
        $html .= '</div>';

        $html .= '<div class="row">';
        $html .= '<div class="col-md-6">';
        $html .= '<div class="form-group">';
        $html .= '<label>' . $this->controller->l('Kategoria domyślna') . '</label>';
        $html .= '<select name="id_category_default" class="form-control">' . $categoryOptionsHtml . '</select>';
        $html .= '</div>';
        $html .= '</div>';

        $html .= '<div class="col-md-6">';
        $html .= '<div class="form-group">';
        $html .= '<label class="control-label">' . $this->controller->l('Import aktywny') . '</label>';
        $html .= '<div class="switch prestashop-switch fixed-width-lg" style="margin-top:6px;">';
        $html .= '<input type="radio" name="is_active" id="is_active_on" value="1" ' . ($isEnabled ? 'checked="checked"' : '') . ' />';
        $html .= '<label for="is_active_on">' . $this->controller->l('Tak') . '</label>';
        $html .= '<input type="radio" name="is_active" id="is_active_off" value="0" ' . (!$isEnabled ? 'checked="checked"' : '') . ' />';
        $html .= '<label for="is_active_off">' . $this->controller->l('Nie') . '</label>';
        $html .= '<a class="slide-button btn"></a>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';

        $html .= '<div class="azada-modal-actions">';
        $html .= '<button type="button" class="btn btn-default" data-dismiss="modal"><i class="icon-remove"></i> ' . $this->controller->l('Anuluj') . '</button>';
        $html .= '<button type="submit" name="submitAzadaCategoryMapSave" class="btn btn-primary"><i class="icon-save"></i> ' . $this->controller->l('Zapisz przypisanie') . '</button>';
        $html .= '</div>';

        $html .= '</form>';

        return $html;
    }

    private function escape($value)
    {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }
}
