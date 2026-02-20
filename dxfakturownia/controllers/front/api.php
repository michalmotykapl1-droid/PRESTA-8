<?php
/**
 * API Controller dla DX Fakturownia - Wersja FINALNA (Z usuwaniem skasowanych faktur)
 */

class DxfakturowniaApiModuleFrontController extends ModuleFrontController
{
    public $auth = false;
    public $ajax = true;

    public function initContent()
    {
        parent::initContent();
        header('Content-Type: application/json');

        if (!$this->validateFakturowniaAdmin()) {
            echo json_encode(['success' => false, 'error' => 'Brak uprawnień (Wymagane zalogowanie do panelu admina).']);
            die();
        }

        try {
            $action = Tools::getValue('action');

            switch ($action) {
                case 'create_document':
                    $this->createDocument();
                    break;
                case 'get_documents':
                    $this->getDocuments();
                    break;
                default:
                    throw new Exception('Nieznana akcja API Fakturowni.');
            }

        } catch (Exception $e) {
            $msg = $e->getMessage();
            if (is_array($msg) || is_object($msg)) {
                $msg = json_encode($msg, JSON_UNESCAPED_UNICODE);
            }
            echo json_encode(['success' => false, 'error' => $msg]);
            die();
        }
    }

    private function validateFakturowniaAdmin()
    {
        try {
            $cookie = new Cookie('psAdmin');
            return ($cookie->id_employee && $cookie->id_employee > 0);
        } catch (Exception $e) {
            return false;
        }
    }

    // --- 1. POBIERANIE DOKUMENTÓW ---
    private function getDocuments()
    {
        $id_order = (int) Tools::getValue('id_order');
        if (!$id_order) throw new Exception('Brak ID zamówienia');

        // 1. Synchronizacja (Pobierz nowe, zaktualizuj statusy, USUŃ SKASOWANE)
        $this->syncOrderDocuments($id_order);

        // 2. Pobranie z bazy lokalnej (już po czyszczeniu)
        $sql = 'SELECT * FROM `' . _DB_PREFIX_ . 'dxfakturownia_invoices` WHERE `id_order` = ' . $id_order . ' ORDER BY `id_dxfakturownia_invoice` DESC';
        $rows = Db::getInstance()->executeS($sql);

        require_once _PS_MODULE_DIR_ . 'dxfakturownia/classes/FakturowniaAccount.php';
        $account = FakturowniaAccount::getDefaultAccount();
        $domain = $account ? rtrim($account->domain, '/') : '';

        $documents = [];
        if ($rows) {
            foreach ($rows as $row) {
                // Link do ręcznej korekty (from=ID)
                $correctionUrl = '';
                if ($domain && $row['kind'] == 'vat') {
                    $correctionUrl = $domain . '/invoices/new?kind=correction&from=' . $row['remote_id'];
                }

                $documents[] = [
                    'id' => $row['remote_id'],
                    'number' => $row['number'],
                    'kind' => $row['kind'], 
                    'view_url' => $row['view_url'],
                    'pdf_url' => $row['view_url'] . '.pdf',
                    'status' => $row['status'],
                    'correction_url' => $correctionUrl
                ];
            }
        }

        echo json_encode(['success' => true, 'documents' => $documents]);
        die();
    }

    /**
     * Synchronizacja: Pobiera listę z Fakturowni.
     * Dodaje nowe, aktualizuje istniejące i USUWA te, których nie ma w API.
     */
    private function syncOrderDocuments($id_order)
    {
        require_once _PS_MODULE_DIR_ . 'dxfakturownia/classes/FakturowniaAccount.php';
        require_once _PS_MODULE_DIR_ . 'dxfakturownia/classes/DxFakturowniaInvoice.php';

        $account = FakturowniaAccount::getDefaultAccount();
        if (!$account) return;

        $endpoint = rtrim($account->domain, '/') . '/invoices.json?oid=' . $id_order . '&api_token=' . $account->api_token;
        
        $ch = curl_init($endpoint);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode >= 200 && $httpCode < 300) {
            $invoices = json_decode($result, true);
            $foundRemoteIds = []; // Lista ID znalezionych w Fakturowni

            if (is_array($invoices)) {
                foreach ($invoices as $inv) {
                    $foundRemoteIds[] = (int)$inv['id']; // Dodajemy do listy obecnych

                    $localInv = DxFakturowniaInvoice::getByRemoteId((int)$inv['id']);
                    
                    if (!$localInv) {
                        $localInv = new DxFakturowniaInvoice();
                        $localInv->remote_id = (int)$inv['id'];
                        $localInv->id_dxfakturownia_account = (int)$account->id;
                        $localInv->id_order = (int)$id_order;
                        $localInv->kind = pSQL($inv['kind']);
                        $localInv->number = pSQL($inv['number']);
                        $localInv->sell_date = pSQL($inv['sell_date']);
                        $localInv->price_gross = (float)$inv['price_gross'];
                        $localInv->view_url = rtrim($account->domain, '/') . '/invoices/' . $inv['id'];
                        $localInv->buyer_name = isset($inv['buyer_name']) ? pSQL($inv['buyer_name']) : '';
                    }
                    
                    // Aktualizacja statusu
                    $localInv->status = pSQL($inv['status']);
                    $localInv->save();
                }
            }

            // CZYSZCZENIE: Usuń z lokalnej bazy te, których nie zwróciło API
            $db = Db::getInstance();
            if (!empty($foundRemoteIds)) {
                // Usuń rekordy dla tego zamówienia, których remote_id NIE MA na liście z Fakturowni
                $sql = 'DELETE FROM `' . _DB_PREFIX_ . 'dxfakturownia_invoices` 
                        WHERE `id_order` = ' . (int)$id_order . ' 
                        AND `remote_id` NOT IN (' . implode(',', $foundRemoteIds) . ')';
                $db->execute($sql);
            } else {
                // Jeśli API zwróciło pustą listę (brak faktur dla tego OID), usuwamy wszystko lokalnie dla tego zamówienia
                $sql = 'DELETE FROM `' . _DB_PREFIX_ . 'dxfakturownia_invoices` 
                        WHERE `id_order` = ' . (int)$id_order;
                $db->execute($sql);
            }
        }
    }

    // --- 2. TWORZENIE DOKUMENTU ---
    private function createDocument()
    {
        $id_order = (int) Tools::getValue('id_order');
        $kind = Tools::getValue('kind'); 
        
        if (!$id_order) throw new Exception('Brak ID zamówienia');
        
        require_once _PS_MODULE_DIR_ . 'dxfakturownia/classes/FakturowniaAccount.php';
        require_once _PS_MODULE_DIR_ . 'dxfakturownia/classes/FakturowniaClient.php';
        require_once _PS_MODULE_DIR_ . 'dxfakturownia/classes/DxFakturowniaInvoice.php';

        $account = FakturowniaAccount::getDefaultAccount();
        if (!$account) throw new Exception('Brak skonfigurowanego konta Fakturowni.');

        $order = new Order($id_order);
        if (!Validate::isLoadedObject($order)) throw new Exception('Zamówienie nie istnieje');
        
        $customer = new Customer($order->id_customer);
        $invoiceAddress = new Address((int)$order->id_address_invoice);
        
        $buyer_name = $invoiceAddress->firstname . ' ' . $invoiceAddress->lastname;
        if (!empty($invoiceAddress->company)) {
            $buyer_name = $invoiceAddress->company;
        }

        $invoiceData = [
            'kind' => $kind,
            'sell_date' => date('Y-m-d', strtotime($order->date_add)),
            'issue_date' => date('Y-m-d'),
            'payment_to' => date('Y-m-d', strtotime($order->date_add . ' + 7 days')),
            'buyer_name' => $buyer_name,
            'buyer_email' => $customer->email,
            'buyer_tax_no' => $invoiceAddress->vat_number,
            'buyer_post_code' => $invoiceAddress->postcode,
            'buyer_city' => $invoiceAddress->city,
            'buyer_street' => $invoiceAddress->address1 . (!empty($invoiceAddress->address2) ? ' ' . $invoiceAddress->address2 : ''),
            'oid' => $id_order,
            'positions' => $this->mapPositions($order)
        ];

        $data = [
            'api_token' => $account->api_token,
            'invoice' => $invoiceData
        ];

        if ($kind === 'vat' || $kind === 'proforma') {
            if (!empty($invoiceAddress->vat_number)) {
                $data['invoice']['buyer_tax_no'] = $invoiceAddress->vat_number;
                if (!empty($invoiceAddress->company)) {
                    $data['invoice']['buyer_name'] = $invoiceAddress->company;
                }
            }
        }

        $endpoint = rtrim($account->domain, '/') . (strpos($account->domain, 'http') === false ? 'https://' : '') . '/invoices.json';
        
        $ch = curl_init($endpoint);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json', 'Content-Type: application/json']);
        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        $response = json_decode($result, true);

        if ($httpCode >= 200 && $httpCode < 300) {
            $inv_data = $response;
            $invoiceObj = new DxFakturowniaInvoice();
            $invoiceObj->remote_id = (int)$inv_data['id'];
            $invoiceObj->id_dxfakturownia_account = (int)$account->id;
            $invoiceObj->id_order = (int)$id_order;
            $invoiceObj->kind = pSQL($inv_data['kind']);
            $invoiceObj->number = pSQL($inv_data['number']);
            $invoiceObj->buyer_name = isset($inv_data['buyer_name']) ? pSQL($inv_data['buyer_name']) : '';
            $invoiceObj->sell_date = pSQL($inv_data['sell_date']);
            $invoiceObj->price_gross = (float)$inv_data['price_gross'];
            $invoiceObj->status = pSQL($inv_data['status']);
            $invoiceObj->view_url = rtrim($account->domain, '/') . '/invoices/' . $inv_data['id'];
            $invoiceObj->add();

            echo json_encode([
                'success' => true, 
                'message' => 'Dokument wystawiony: ' . $inv_data['number'],
                'document' => [
                    'id' => $inv_data['id'],
                    'number' => $inv_data['number'],
                    'kind' => $inv_data['kind'],
                    'view_url' => $invoiceObj->view_url,
                    'pdf_url' => $invoiceObj->view_url . '.pdf'
                ]
            ]);
        } else {
            $errorMsg = 'Nieznany błąd Fakturowni';
            if (isset($response['message'])) {
                $errorMsg = is_array($response['message']) ? json_encode($response['message'], JSON_UNESCAPED_UNICODE) : $response['message'];
            } elseif (isset($response['error'])) {
                $errorMsg = is_array($response['error']) ? json_encode($response['error'], JSON_UNESCAPED_UNICODE) : $response['error'];
            } elseif (is_array($response)) {
                $errorMsg = json_encode($response, JSON_UNESCAPED_UNICODE);
            }
            throw new Exception('Błąd API: ' . $errorMsg);
        }
        die();
    }

    private function mapPositions($order)
    {
        $products = $order->getProducts();
        $positions = [];
        foreach ($products as $product) {
            $positions[] = [
                'name' => $product['product_name'],
                'quantity' => $product['product_quantity'],
                'total_price_gross' => $product['total_wt'],
                'tax' => $product['tax_rate'],
            ];
        }
        if ($order->total_shipping > 0) {
             $positions[] = [
                'name' => 'Koszty wysyłki',
                'quantity' => 1,
                'total_price_gross' => $order->total_shipping,
                'tax' => $order->carrier_tax_rate, 
            ];
        }
        return $positions;
    }
}