<?php
class StrefasuplementowAjaxModuleFrontController extends ModuleFrontController
{
    public function initContent()
    {
        $this->ajax = true;
        parent::initContent();
        $module = Module::getInstanceByName('strefasuplementow');
        if ($module && Module::isInstalled('strefasuplementow')) {
            echo $module->renderAjaxContent();
        }
        die();
    }
}