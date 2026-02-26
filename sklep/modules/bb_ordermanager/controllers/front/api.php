<?php
/**
 * API Controller - FINAL VERSION (FULL CODE)
 */

require_once dirname(__FILE__) . '/traits/ApiProducts.php';
require_once dirname(__FILE__) . '/traits/ApiOrders.php';
require_once dirname(__FILE__) . '/traits/ApiPayments.php';
require_once dirname(__FILE__) . '/traits/ApiLogistics.php';
require_once _PS_MODULE_DIR_ . 'bb_ordermanager/classes/BbOrderManagerAuth.php';
require_once _PS_MODULE_DIR_ . 'bb_ordermanager/classes/BbOrderManagerLogger.php';

class Bb_ordermanagerApiModuleFrontController extends ModuleFrontController
{
    use ApiProducts;
    use ApiOrders;
    use ApiPayments;
    use ApiLogistics;

    public $auth = false;
    public $ajax = true;

    public function init()
    {
        parent::init();
        $this->display_header = false;
        $this->display_footer = false;
        $this->content_only = true;
    }

    public function initContent()
    {
        @ini_set('display_errors', 'off');
        error_reporting(0);

        while (ob_get_level()) {
            ob_end_clean();
        }

        header('Content-Type: application/json; charset=utf-8');

        // --- AUTH (sesja pracownika + CSRF) ---
        BbOrderManagerAuth::enforceApiAuth();

        try {
            $action = Tools::getValue('action');
            
            // Produkty
            if ($action === 'search_products') $this->searchProducts();
            elseif ($action === 'add_product_to_order') $this->addProductToOrder();
            elseif ($action === 'update_order_product') $this->updateOrderProduct();
            elseif ($action === 'delete_order_product') $this->deleteOrderProduct();
            
            // Zamówienia
            elseif ($action === 'get_orders') $this->getOrders();
            elseif ($action === 'get_order_details') $this->getOrderDetails();
            elseif ($action === 'update_folder') $this->updateOrderFolder();
            elseif ($action === 'delete_order') $this->deleteOrder();      
            elseif ($action === 'archive_order') $this->archiveOrder();
            elseif ($action === 'clone_order') $this->cloneOrderEmpty();    
            
            // Płatności
            elseif ($action === 'update_payment') $this->updatePayment();
            elseif ($action === 'generate_p24_link') $this->generatePaymentLink();
            elseif ($action === 'get_public_link') $this->getPublicLink();
            
            // Logistyka
            elseif ($action === 'update_address_data') $this->updateAddressData();
            elseif ($action === 'search_lockers') $this->searchLockers();
            elseif ($action === 'add_tracking') $this->addTrackingNumber();
            
            // NOWE AKCJE: WYSYŁKA ALLEGRO PRO
            elseif ($action === 'create_allegro_shipment') $this->createAllegroShipment();
            elseif ($action === 'get_allegro_label') $this->getAllegroLabel();
            
            else {
                throw new Exception("Nieznana akcja API: " . $action);
            }

        } catch (Exception $e) {
            if (ob_get_length()) ob_clean();
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            die(); 
        }
        
        die();
    }

    /**
     * Zapis logu audytowego.
     *
     * Uwaga: dla kompatybilności UI zostawiamy dopisek "(przez: ...)" w treści message,
     * ale równolegle zapisujemy też id_employee/employee_name w osobnych kolumnach (jeśli dostępne).
     */
    private function addSystemLog($id_order, $message, $action = 'INFO', $details = null) {
        $employeeId = $this->getAdminIdFromCookie();
        $employeeName = '';
        $empObj = null;

        if ($employeeId > 0) {
            $empObj = new Employee($employeeId);
            if (Validate::isLoadedObject($empObj)) {
                $employeeName = trim($empObj->firstname . ' ' . $empObj->lastname);
            } else {
                $empObj = null;
            }
        }

        // Uwaga: nie dopisujemy "(przez: System/Automat)".
        // Dopisek w treści dodajemy tylko wtedy, gdy mamy realnego pracownika.
        $fullMessage = trim((string)$message);
        if ($employeeName !== '') {
            $fullMessage .= ' (przez: ' . $employeeName . ')';
        }

        BbOrderManagerLogger::log((int)$id_order, (string)$action, $fullMessage, $details, $empObj);
    }

    private function getAdminIdFromCookie() {
        // sesja modułu
        $mid = (int) BbOrderManagerAuth::getEmployeeId();
        if ($mid > 0) { return $mid; }
        if ($this->context->employee && $this->context->employee->id) { return (int)$this->context->employee->id; }
        try { 
            $cookie = new Cookie('psAdmin'); 
            if ($cookie->id_employee) { return (int)$cookie->id_employee; } 
        } catch (Exception $e) {} 
        return 0; 
    }
}