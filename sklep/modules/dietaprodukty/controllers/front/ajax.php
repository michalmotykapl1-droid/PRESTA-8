<?php
class DietaproduktyAjaxModuleFrontController extends ModuleFrontController
{
    public function initContent()
    {
        $this->ajax = true;
        parent::initContent();
        $module = Module::getInstanceByName('dietaprodukty');
        if ($module && Module::isInstalled('dietaprodukty')) {
            echo $module->renderAjaxContent();
        }
        die();
    }
}