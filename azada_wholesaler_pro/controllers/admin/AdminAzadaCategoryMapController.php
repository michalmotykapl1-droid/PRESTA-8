<?php
class AdminAzadaCategoryMapController extends ModuleAdminController {
    public function __construct() {
        $this->bootstrap = true;
        parent::__construct();
    }
    public function initContent() {
        parent::initContent();
        $this->context->smarty->assign('content', '<div class="alert alert-info">Tutaj będzie zaawansowane mapowanie drzewa kategorii (Hurtownia -> Sklep).</div>');
    }
}