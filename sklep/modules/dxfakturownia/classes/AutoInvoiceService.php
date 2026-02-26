<?php
/**
 * AutoInvoiceService.php
 * Writes logs to bb_ordermanager_logs
 */

if (!defined('_PS_VERSION_')) exit;

require_once _PS_MODULE_DIR_ . 'dxfakturownia/classes/FakturowniaClient.php';
require_once _PS_MODULE_DIR_ . 'dxfakturownia/classes/FakturowniaAccount.php';
require_once _PS_MODULE_DIR_ . 'dxfakturownia/classes/DxFakturowniaInvoice.php';

class AutoInvoiceService
{
    public static function processOrder($id_order)
    {
        $order = new Order((int)$id_order);
        if (!Validate::isLoadedObject($order)) return ['success' => false, 'message' => 'Błąd zamówienia'];

        $existing = Db::getInstance()->getValue('SELECT id_dxfakturownia_invoice FROM `' . _DB_PREFIX_ . 'dxfakturownia_invoices` WHERE id_order = ' . (int)$id_order);
        if ($existing) return ['success' => true, 'message' => 'Dokument już istnieje'];

        $account = FakturowniaAccount::getDefaultAccount();
        if (!$account) return ['success' => false, 'message' => 'Brak aktywnego konta Fakturowni'];

        $invoiceAddress = new Address((int)$order->id_address_invoice);
        $kind = 'receipt'; 
        
        if (!empty($invoiceAddress->vat_number)) {
            $kind = 'vat';
        } else {
            $configType = Configuration::get('DX_B2C_DOC_TYPE');
            if ($configType === 'vat') $kind = 'vat';
        }

        return self::createDocument($order, $kind, $account);
    }

    private static function createDocument($order, $kind, $account)
    {
        try {
            $client = new FakturowniaClient($account->api_token, $account->domain);
            $customer = new Customer($order->id_customer);
            
            $response = $client->createInvoice($order, $customer, $kind);
            
            if (isset($response['id'])) {
                $invoiceObj = new DxFakturowniaInvoice();
                $invoiceObj->remote_id = (int)$response['id'];
                $invoiceObj->id_dxfakturownia_account = (int)$account->id;
                $invoiceObj->id_order = (int)$order->id;
                $invoiceObj->kind = pSQL($response['kind']);
                $invoiceObj->number = pSQL($response['number']);
                $invoiceObj->buyer_name = isset($response['buyer_name']) ? pSQL($response['buyer_name']) : '';
                $invoiceObj->sell_date = pSQL($response['sell_date']);
                $invoiceObj->price_gross = (float)$response['price_gross'];
                $invoiceObj->status = pSQL($response['status']);
                $invoiceObj->view_url = rtrim($account->domain, '/') . '/invoices/' . $response['id'];
                $invoiceObj->add();

                // --- ZAPIS DO NOWEJ TABELI ---
                self::addSystemLog($order, 'DX Fakturownia: Automatycznie wystawiono dokument: ' . $response['number']);
                
                return ['success' => true, 'message' => 'Wystawiono: ' . $response['number']];
            }
            return ['success' => false, 'message' => 'Błąd API Fakturowni (brak ID)'];

        } catch (Exception $e) {
            self::addSystemLog($order, 'DX BŁĄD: ' . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    private static function addSystemLog($order, $text)
    {
        try {
            // Zapis do nowej tabeli logów
            Db::getInstance()->insert('bb_ordermanager_logs', [
                'id_order' => (int)$order->id,
                'message' => pSQL($text),
                'date_add' => date('Y-m-d H:i:s')
            ]);
        } catch (Exception $e) {
            // Ignoruj błąd zapisu logu (żeby nie przerwać procesu, jeśli tabela nie istnieje)
        }
    }
}