<?php
class ProductcategorysliderAjaxModuleFrontController extends ModuleFrontController
{
    public function initContent()
    {
        $this->ajax = true;
        parent::initContent();
        $module = Module::getInstanceByName('productcategoryslider');
        if ($module && Module::isInstalled('productcategoryslider')) {
            echo $module->renderAjaxContent();
        }
        die();
    }
}