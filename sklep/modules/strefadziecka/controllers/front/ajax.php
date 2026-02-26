<?php
class StrefadzieckaAjaxModuleFrontController extends ModuleFrontController
{
    public function initContent()
    {
        $this->ajax = true;
        parent::initContent();
        $module = Module::getInstanceByName('strefadziecka');
        if ($module && Module::isInstalled('strefadziecka')) {
            echo $module->renderAjaxContent();
        }
        die();
    }
}