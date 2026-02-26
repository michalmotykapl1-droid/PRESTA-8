<?php
/*
 * Ajax Controller - Calls Helper
 */

require_once(dirname(__FILE__) . '/../../classes/NieskonczoneHelper.php');

class NieskonczoneProduktyAjaxModuleFrontController extends ModuleFrontController
{
    public function initContent()
    {
        parent::initContent();
        
        $page = (int)Tools::getValue('page');
        $id_product = (int)Tools::getValue('id_product');
        // To jest ID kategorii "szerokiej", które wyliczył moduł i przekazał do JS
        $id_category_tree = (int)Tools::getValue('id_category'); 
        
        // Pobieramy mix z drzewa via Helper (limit 12)
        $products = NieskonczoneHelper::getTreeProducts($id_category_tree, $id_product, $page, 12);
        
        if (!empty($products)) {
            $this->context->smarty->assign([
                'np_products' => $products
            ]);
            
            $html = $this->module->display($this->module->name, 'views/templates/front/product_list.tpl');
            
            die(json_encode([
                'has_more' => true,
                'html' => $html
            ]));
        } else {
            die(json_encode(['has_more' => false]));
        }
    }
}