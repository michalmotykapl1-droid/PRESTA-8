<?php
/**
 * Kontroler AJAX dla Strefy Koszyka
 */
class StrefaKoszykaAjaxModuleFrontController extends ModuleFrontController
{
    public function initContent()
    {
        $this->ajax = true;
        parent::initContent();
        
        $module = Module::getInstanceByName('strefakoszyka');
        if ($module && Module::isInstalled('strefakoszyka')) {
            echo $module->renderAjaxContent();
        }
        die();
    }
}