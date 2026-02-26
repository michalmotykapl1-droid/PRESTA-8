<?php
/**
 * Kontroler Admina - Launcher
 * Odpowiada za pozycję w menu bocznym i przekierowanie do Frontu.
 */

class AdminBbManagerController extends ModuleAdminController
{
    public function __construct()
    {
        $this->bootstrap = true;
        $this->display = 'view';
        parent::__construct();
    }

    public function initContent()
    {
        // Automatyczne przekierowanie do pełnoekranowego panelu (Front Controller)
        $link = $this->context->link->getModuleLink('bb_ordermanager', 'manager');
        Tools::redirect($link);
    }
}