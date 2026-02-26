<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

class Bbcatsearch extends Module
{
    public function __construct()
    {
        $this->name = 'bbcatsearch';
        $this->tab = 'front_office_features';
        $this->version = '1.0.4';
        $this->author = 'BigBio';
        $this->need_instance = 0;
        $this->bootstrap = true;
        $this->ps_versions_compliancy = ['min' => '1.7.0.0', 'max' => _PS_VERSION_];
        $this->controllers = ['ajax'];

        parent::__construct();

        $this->displayName = $this->l('BB Category Search');
        $this->description = $this->l('AJAX search in current category with diet filters (features).');
    }

    public function install()
    {
        return parent::install();
    }

    public function uninstall()
    {
        return parent::uninstall();
    }
}
