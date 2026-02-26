<?php
class FreshproduktyAjaxModuleFrontController extends ModuleFrontController
{
    public function initContent()
    {
        $this->ajax = true;
        parent::initContent();
        $module = Module::getInstanceByName('freshprodukty');
        if ($module && Module::isInstalled('freshprodukty')) {
            echo $module->renderAjaxContent();
        }
        die();
    }
}