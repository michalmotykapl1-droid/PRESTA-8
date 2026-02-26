<?php
class AdminAllegroCategoryMapperMappingsController extends ModuleAdminController
{
    public function __construct()
    {
        $this->bootstrap = true;
        parent::__construct();
    }

    public function initContent()
    {
        parent::initContent();

        $maps = Db::getInstance()->executeS('SELECT * FROM '._DB_PREFIX_.'allegro_category_map ORDER BY id DESC LIMIT 1000');
        $this->context->smarty->assign([ 'maps' => $maps ]);

        // Rely on ModuleAdminController to resolve module template path
        $this->setTemplate('mappings.tpl');
    }
}
