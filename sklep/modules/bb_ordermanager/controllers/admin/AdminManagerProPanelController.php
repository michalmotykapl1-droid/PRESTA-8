<?php
/**
 * Controller for Main Manager Panel (Direct Redirect)
 */
class AdminManagerProPanelController extends ModuleAdminController
{
    public function __construct()
    {
        $this->bootstrap = true;
        parent::__construct();
    }

    public function initContent()
    {
        // Nie wołamy parent::initContent(), bo nie chcemy nagłówka/stopki admina
        // Chcemy czystego przekierowania
        
        $frontLink = $this->context->link->getModuleLink('bb_ordermanager', 'manager');
        Tools::redirect($frontLink);
    }
}