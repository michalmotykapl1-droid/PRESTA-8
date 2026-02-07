<?php
class AdminAzadaPriceRulesController extends ModuleAdminController {
    public function __construct() {
        $this->bootstrap = true;
        parent::__construct();
    }
    public function initContent() {
        parent::initContent();
        $this->context->smarty->assign('content', '<div class="alert alert-info">Tutaj ustawisz masowe marże, narzuty i zaokrąglenia cen.</div>');
    }
}