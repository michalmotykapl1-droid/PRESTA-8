<?php
class AdminAzadaParentController extends ModuleAdminController {
    public function __construct() {
        $this->bootstrap = true;
        parent::__construct();
        // Przekierowanie do pierwszej zakładki po kliknięciu w nagłówek
        Tools::redirectAdmin($this->context->link->getAdminLink('AdminAzadaWholesaler'));
    }
}