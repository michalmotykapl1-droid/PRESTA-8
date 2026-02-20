<?php
class AdminDxFakturowniaParentController extends ModuleAdminController
{
    public function __construct()
    {
        parent::__construct();
        Tools::redirectAdmin($this->context->link->getAdminLink('AdminDxFakturowniaAccounts'));
    }
}