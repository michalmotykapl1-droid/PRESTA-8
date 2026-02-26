<?php
/**
 * Admin Controller for Packing
 *
 * FIX (2026): In the current module version the Admin packing template is not present.
 * To keep packing working from the Back Office order view, we redirect to the
 * front packing screen (standalone), passing id_order.
 */
class AdminBbPackingController extends ModuleAdminController
{
    public function __construct()
    {
        $this->bootstrap = true;
        parent::__construct();
    }

    public function initContent()
    {
        $id_order = (int) Tools::getValue('id_order');

        // Redirect to the standalone packing screen (front controller)
        if ($id_order > 0) {
            $params = [
                'id_order' => $id_order,
            ];

            // Preserve optional queue parameter if provided
            $orderList = (string) Tools::getValue('order_list');
            if ($orderList !== '') {
                $params['order_list'] = $orderList;
            }

            $url = $this->context->link->getModuleLink('bb_ordermanager', 'packing', $params);
            Tools::redirect($url);
        }

        parent::initContent();

        // Fallback view when id_order is missing
        $this->errors[] = $this->l('Brak ID zamówienia.');
    }
}
