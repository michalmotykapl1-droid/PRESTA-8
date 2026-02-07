<?php
class AdminAzadaManufacturerMapController extends ModuleAdminController {
    public function __construct() {
        $this->bootstrap = true;
        parent::__construct();
    }
    public function initContent() {
        parent::initContent();
        $this->context->smarty->assign('content', '<div class="alert alert-info">Tutaj przypiszesz producentów z CSV do producentów w PrestaShop.</div>');
    }
}