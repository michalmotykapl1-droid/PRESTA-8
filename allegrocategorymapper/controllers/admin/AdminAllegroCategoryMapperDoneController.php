<?php
class AdminAllegroCategoryMapperDoneController extends ModuleAdminController
{
    public function __construct()
    {
        $this->bootstrap = true;
        parent::__construct();
    }

    public function initContent()
    {
        parent::initContent();

        if (Tools::isSubmit('submitUndone')) {
            $this->processUndone();
        }

        $sql = 'SELECT d.*, pl.name as product_name
            FROM '._DB_PREFIX_.'allegro_ean_done d
            LEFT JOIN '._DB_PREFIX_.'product_lang pl
              ON (pl.id_product = d.id_product AND pl.id_lang='.(int)$this->context->language->id.')
            ORDER BY d.done_at DESC LIMIT 500';

        $list = Db::getInstance()->executeS($sql);
        $this->context->smarty->assign([ 'done_list' => $list ]);

        // Rely on ModuleAdminController to resolve module template path
        $this->setTemplate('done.tpl');
    }

    protected function processUndone()
    {
        $ids = Tools::getValue('done_ids', []);
        if (is_array($ids) && !empty($ids)) {
            $ids = array_map('intval', $ids);
            $ids_str = implode(',', $ids);
            Db::getInstance()->execute('DELETE FROM '._DB_PREFIX_.'allegro_ean_done WHERE id_product IN ('.$ids_str.')');
            $this->confirmations[] = sprintf($this->l('Usunięto %d wpisów z listy ZROBIONE.'), count($ids));
        } else {
            $this->errors[] = $this->l('Nie wybrano żadnych pozycji.');
        }
    }
}
