<?php
class TvcmsspecialproductsAjaxModuleFrontController extends ModuleFrontController
{
    public function initContent()
    {
        $this->ajax = true;
        parent::initContent();
        $module = Module::getInstanceByName('tvcmsspecialproducts');
        if ($module && Module::isInstalled('tvcmsspecialproducts')) {
            // Wywołujemy naszą nową funkcję, która zwraca display_home-data.tpl
            echo $module->renderAjaxContent();
        }
        die();
    }
}