<?php
class StrefaczystosciAjaxModuleFrontController extends ModuleFrontController
{
    public function initContent()
    {
        $this->ajax = true;
        parent::initContent();
        $module = Module::getInstanceByName('strefaczystosci');
        if ($module && Module::isInstalled('strefaczystosci')) {
            echo $module->renderAjaxContent();
        }
        die();
    }
}