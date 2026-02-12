<?php
/**
 * ALLEGRO PRO - Back Office controller
 */

class AdminAllegroProController extends ModuleAdminController
{
    public function initContent()
    {
        parent::initContent();
        
        if (isset($this->module) && method_exists($this->module, 'ensureTabs')) {
            $this->module->ensureTabs();
        }
Tools::redirectAdmin($this->context->link->getAdminLink('AdminAllegroProAccounts'));
    }
}
