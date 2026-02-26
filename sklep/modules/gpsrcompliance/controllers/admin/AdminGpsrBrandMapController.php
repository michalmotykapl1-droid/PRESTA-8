<?php
if (!defined('_PS_VERSION_')) { exit; }

require_once _PS_MODULE_DIR_.'gpsrcompliance/classes/GpsrService.php';

class AdminGpsrBrandMapController extends ModuleAdminController
{
    public function __construct()
    {
        $this->bootstrap = true;
        parent::__construct();
    }

    public function postProcess()
    {
        if (Tools::isSubmit('save_map')) {
            $id_manufacturer = (int)Tools::getValue('id_manufacturer');
            $id_prod = Tools::getValue('id_gpsr_producer') ? (int)Tools::getValue('id_gpsr_producer') : null;
            $id_pers = Tools::getValue('id_gpsr_person') ? (int)Tools::getValue('id_gpsr_person') : null;
            GpsrService::saveBrandRecord($id_manufacturer, $id_prod, $id_pers);
        }
    }

    public function initContent()
    {
        parent::initContent();
        $brands = Db::getInstance()->executeS('SELECT id_manufacturer, name FROM '._DB_PREFIX_.'manufacturer ORDER BY name ASC') ?: [];
        $rows = Db::getInstance()->executeS('
            SELECT m.id_manufacturer, m.name, bm.id_gpsr_producer, bm.id_gpsr_person
            FROM '._DB_PREFIX_.'manufacturer m
            LEFT JOIN '._DB_PREFIX_.'gpsr_brand_map bm ON (bm.id_manufacturer=m.id_manufacturer)
            ORDER BY m.name ASC
        ') ?: [];
        $svc = new GpsrService();
        $this->context->smarty->assign([
            'rows' => $rows,
            'brands' => $brands,
            'producers' => $svc->getProducersOptions(),
            'persons' => $svc->getPersonsOptions(),
            'self_link' => self::$currentIndex.'&token='.$this->token,
        ]);
        $this->setTemplate('brand_map.tpl');
    }
}
