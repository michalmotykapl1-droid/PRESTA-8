<?php
if (!defined('_PS_VERSION_')) { exit; }

class AdminGpsrConfigController extends ModuleAdminController
{
    public function __construct()
    {
        $this->bootstrap = true;
        parent::__construct();
        $this->meta_title = $this->l('GPSR – Ustawienia');
    }

    public function initContent()
    {
        $this->show_toolbar = false;
        parent::initContent();
        $module = Module::getInstanceByName('gpsrcompliance');
        $this->content = $module->getContent();
        $this->context->smarty->assign('content', $this->content);
    }
}
