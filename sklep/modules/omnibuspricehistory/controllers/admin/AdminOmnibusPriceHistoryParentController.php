<?php
/**
 * 2007-2023 PrestaShop
 *
 * Kontroler dla nadrzędnej zakładki menu modułu Omnibus.
 * Jego jedynym zadaniem jest przekierowanie do pierwszej pod-zakładki.
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class AdminOmnibusPriceHistoryParentController extends ModuleAdminController
{
    public function __construct()
    {
        parent::__construct();
        // Przekieruj do pierwszej pod-zakładki (Konfiguracji)
        Tools::redirectAdmin(
            $this->context->link->getAdminLink('AdminOmnibusPriceHistoryConfig')
        );
    }
}