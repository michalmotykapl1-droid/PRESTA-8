<?php
class StrefaprotuktowaAjaxModuleFrontController extends ModuleFrontController {
    public function initContent() {
        $this->ajax = true;
        parent::initContent();
        $module = Module::getInstanceByName('strefaprotuktowa');
        if ($module && Module::isInstalled('strefaprotuktowa')) {
            echo $module->renderAjaxContent();
        }
        die();
    }
}