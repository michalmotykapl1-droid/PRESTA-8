<?php
class Ps_categoryproductsAjaxModuleFrontController extends ModuleFrontController
{
    public function initContent()
    {
        $this->ajax = true;
        parent::initContent();
        $module = Module::getInstanceByName('ps_categoryproducts');
        if ($module && Module::isInstalled('ps_categoryproducts')) {
            echo $module->renderAjaxContent();
        }
        die();
    }
}