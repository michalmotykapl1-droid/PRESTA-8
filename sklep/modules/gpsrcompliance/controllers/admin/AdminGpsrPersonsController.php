<?php
if (!defined('_PS_VERSION_')) { exit; }

class AdminGpsrPersonsController extends ModuleAdminController
{
    public function __construct()
    {
        $this->bootstrap = true;
        parent::__construct();
    }

    public function postProcess()
    {
        if (Tools::isSubmit('save_person')) {
            $id = (int)Tools::getValue('id_gpsr_person');
            $data = [
                'name'    => pSQL(Tools::getValue('name')),
                'alias'   => pSQL(Tools::getValue('alias')),
                'country' => pSQL(Tools::getValue('country')),
                'address' => pSQL(Tools::getValue('address')),
                'postcode'=> pSQL(Tools::getValue('postcode')),
                'city'    => pSQL(Tools::getValue('city')),
                'email'   => pSQL(Tools::getValue('email')),
                'phone'   => pSQL(Tools::getValue('phone')),
                'info'    => pSQL(Tools::getValue('info'), true),
                'active'  => (int)Tools::getValue('active', 1),
            ];
            if ($id) { Db::getInstance()->update('gpsr_person', $data, 'id_gpsr_person='.(int)$id); }
            else { Db::getInstance()->insert('gpsr_person', $data); }
        }
        if (Tools::isSubmit('delete') && ($id=(int)Tools::getValue('id'))) {
            Db::getInstance()->delete('gpsr_person', 'id_gpsr_person='.(int)$id);
        }
    }

    public function initContent()
    {
        parent::initContent();
        $editId = (int)Tools::getValue('edit');
        if ($editId) {
            $row = Db::getInstance()->getRow('SELECT * FROM '._DB_PREFIX_.'gpsr_person WHERE id_gpsr_person='.(int)$editId) ?: [];
            $this->context->smarty->assign(['row'=>$row]);
            $this->setTemplate('persons_form.tpl');
        } else {
            $rows = Db::getInstance()->executeS('SELECT * FROM '._DB_PREFIX_.'gpsr_person ORDER BY name ASC') ?: [];
            $this->context->smarty->assign(['rows'=>$rows, 'self_link'=>self::$currentIndex.'&token='.$this->token]);
            $this->setTemplate('persons_list.tpl');
        }
    }
}
