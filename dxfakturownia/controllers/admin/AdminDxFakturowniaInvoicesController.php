<?php

class AdminDxFakturowniaInvoicesController extends ModuleAdminController
{
    public function __construct()
    {
        $this->bootstrap = true;
        $this->table = 'dxfakturownia_invoices';
        $this->className = 'DxFakturowniaInvoice';
        $this->identifier = 'id_dxfakturownia_invoice';
        
        // Domyślne sortowanie po ID malejąco (Najnowsze na górze)
        $this->default_order_by = 'id_dxfakturownia_invoice';
        $this->default_order_way = 'DESC';
        
        $this->lang = false;
        
        parent::__construct();

        $this->displayName = $this->l('Lista Faktur i Korekt');

        // STATUSY
        $status_list = [
            'issued' => $this->l('Wystawiona (Brak wpłaty)'),
            'paid' => $this->l('Opłacona'),
            'partial' => $this->l('Częściowo opłacona'),
            'overdue' => $this->l('Przeterminowana'),
        ];

        // TYPY (Dropdown w filtrze)
        $kind_list = [
            'vat' => $this->l('Faktura VAT'),
            'correction' => $this->l('Korekta'),
            'proforma' => $this->l('Proforma'),
            'receipt' => $this->l('Paragon'),
        ];

        $this->fields_list = [
            'id_dxfakturownia_invoice' => [
                'title' => 'ID',
                'align' => 'center',
                'width' => '30',
                'class' => 'fixed-width-xs',
                'search' => true, 
            ],
            'kind' => [
                'title' => $this->l('Typ'),
                'type' => 'select',
                'list' => $kind_list,
                'filter_key' => 'a!kind',
                'callback' => 'displayKindLabel', // Tu zmieniają się napisy na FV VAT / FV KOR
                'width' => '70',
                'align' => 'center',
                'search' => true,
            ],
            'number' => [
                'title' => $this->l('Numer Dokumentu'),
                'width' => 'auto',
                'callback' => 'displayInvoiceLayout', 
                'search' => true, 
            ],
            'buyer_name' => [
                'title' => $this->l('Klient'),
                'width' => '200',
                'search' => true, 
            ],
            'price_gross' => [
                'title' => $this->l('Kwota'),
                'type' => 'price',
                'currency' => true,
                'width' => '90',
                'align' => 'right',
                'search' => true,
            ],
            'sell_date' => [
                'title' => $this->l('Data sprzedaży'),
                'type' => 'date',
                'width' => '130',
                'align' => 'center',
                'search' => true,
            ],
            'status' => [
                'title' => $this->l('STATUS'),
                'width' => '180',
                'align' => 'center',
                'type' => 'select',
                'list' => $status_list,
                'filter_key' => 'a!status',
                'callback' => 'displayStatusLabel',
                'search' => true,
                'orderby' => false,
            ],
        ];
    }
    
    public function renderList()
    {
        $sync_url = $this->context->link->getAdminLink('AdminDxFakturowniaInvoices');
        
        $this->context->smarty->assign([
            'sync_url' => $sync_url,
            'module_dir' => _MODULE_DIR_ . 'dxfakturownia/',
        ]);

        $header_tpl = _PS_MODULE_DIR_ . 'dxfakturownia/views/templates/admin/invoice_list_header.tpl';
        $header_content = file_exists($header_tpl) ? $this->context->smarty->fetch($header_tpl) : '';

        return $header_content . parent::renderList();
    }

    public function ajaxProcessSyncProcess()
    {
        Db::getInstance()->disableCache();
        header('Content-Type: application/json');
        
        try {
            $page = (int)Tools::getValue('page', 1);
            $full_update = (int)Tools::getValue('full_update', 0); 
            $period = Tools::getValue('period', 'all'); 

            $sql = 'SELECT * FROM `' . _DB_PREFIX_ . 'dxfakturownia_accounts` WHERE active = 1 AND connection_status = 1';
            $accounts = Db::getInstance()->executeS($sql);
            $acc = is_array($accounts) && !empty($accounts) ? reset($accounts) : false;

            if (!$acc) {
                die(json_encode(['success' => false, 'message' => 'Brak aktywnego konta']));
            }

            $client = new FakturowniaClient($acc['api_token'], $acc['domain']);

            if ($period == 'this_month') {
                $all_current_invoices = [];
                $p = 1;
                $safety_limit = 20; 
                
                do {
                    $res = $client->getInvoices($p, 'this_month');
                    if ($res['code'] == 200 && !empty($res['response'])) {
                        $all_current_invoices = array_merge($all_current_invoices, $res['response']);
                        $p++;
                    } else {
                        break;
                    }
                } while ($p <= $safety_limit);

                $added = 0; $updated = 0;
                $remote_ids_found = [];

                foreach ($all_current_invoices as $inv_data) {
                    $remote_ids_found[] = (int)$inv_data['id'];
                    $result = $this->saveInvoiceData($inv_data, $acc['id_dxfakturownia_account'], true); 
                    if ($result == 'added') $added++;
                    if ($result == 'updated') $updated++;
                }

                $deleted_count = 0;
                $first_day = date('Y-m-01');
                $last_day = date('Y-m-t');

                if (!empty($remote_ids_found)) {
                    $sql_delete = 'DELETE FROM `' . _DB_PREFIX_ . 'dxfakturownia_invoices` 
                                   WHERE `sell_date` BETWEEN "'.$first_day.'" AND "'.$last_day.'"
                                   AND `remote_id` NOT IN ('.implode(',', $remote_ids_found).')';
                    if (Db::getInstance()->execute($sql_delete)) {
                        $deleted_count = Db::getInstance()->affected_rows();
                    }
                } elseif (empty($all_current_invoices)) {
                    $sql_delete = 'DELETE FROM `' . _DB_PREFIX_ . 'dxfakturownia_invoices` 
                                   WHERE `sell_date` BETWEEN "'.$first_day.'" AND "'.$last_day.'"';
                    Db::getInstance()->execute($sql_delete);
                }

                echo json_encode([
                    'success' => true,
                    'added' => $added,
                    'updated' => $updated + $deleted_count, 
                    'has_next' => false
                ]);
                die();
            }

            $result = $client->getInvoices($page, $period);
            if ($result['code'] != 200) {
                die(json_encode(['success' => false, 'message' => 'API Error: ' . $result['code']]));
            }

            $invoices_data = $result['response'];
            if (empty($invoices_data)) {
                die(json_encode(['success' => true, 'added' => 0, 'updated' => 0, 'has_next' => false]));
            }

            $added = 0; $updated = 0;
            foreach ($invoices_data as $inv_data) {
                $res = $this->saveInvoiceData($inv_data, $acc['id_dxfakturownia_account'], ($full_update == 1));
                if ($res == 'added') $added++;
                if ($res == 'updated') $updated++;
            }

            echo json_encode([
                'success' => true,
                'added' => $added,
                'updated' => $updated,
                'has_next' => (count($invoices_data) > 0)
            ]);
            die();

        } catch (Exception $e) {
            die(json_encode(['success' => false, 'message' => 'System Error: ' . $e->getMessage()]));
        }
    }

    protected function saveInvoiceData($inv_data, $id_account, $force_update = false)
    {
        $remote_id = (int)$inv_data['id'];
        $invoiceObj = DxFakturowniaInvoice::getByRemoteId($remote_id);
        
        if ($invoiceObj) {
            $status_changed = ($invoiceObj->status != $inv_data['status']);
            $price_changed = (abs($invoiceObj->price_gross - (float)$inv_data['price_gross']) > 0.01);

            if ($force_update || $status_changed || $price_changed) {
               $invoiceObj->status = pSQL($inv_data['status']);
               $invoiceObj->kind = pSQL($inv_data['kind']);
               $invoiceObj->number = pSQL($inv_data['number']);
               $invoiceObj->buyer_name = isset($inv_data['buyer_name']) ? pSQL($inv_data['buyer_name']) : '';
               $invoiceObj->price_gross = (float)$inv_data['price_gross'];
               $invoiceObj->update();
               return 'updated';
            }
            return 'skipped';
        }

        $invoiceObj = new DxFakturowniaInvoice();
        $invoiceObj->remote_id = $remote_id;
        $invoiceObj->id_dxfakturownia_account = (int)$id_account;
        $invoiceObj->kind = pSQL($inv_data['kind']);
        $invoiceObj->number = pSQL($inv_data['number']);
        $invoiceObj->buyer_name = isset($inv_data['buyer_name']) ? pSQL($inv_data['buyer_name']) : '';
        $invoiceObj->sell_date = pSQL($inv_data['sell_date']);
        $invoiceObj->price_gross = (float)$inv_data['price_gross'];
        $invoiceObj->status = pSQL($inv_data['status']);
        
        $acc = new FakturowniaAccount($id_account);
        $invoiceObj->view_url = rtrim($acc->domain, '/') . '/invoices/' . $inv_data['id'];

        $invoiceObj->parent_remote_id = 0;
        if (isset($inv_data['from_invoice_id']) && !empty($inv_data['from_invoice_id'])) {
            $invoiceObj->parent_remote_id = (int)$inv_data['from_invoice_id'];
        }
        
        if (isset($inv_data['oid']) && !empty($inv_data['oid'])) {
            $invoiceObj->id_order = (int)$inv_data['oid'];
        }

        if ($invoiceObj->add()) return 'added';
        return 'error';
    }

    public function displayInvoiceLayout($number, $row)
    {
        $pdf_url = $row['view_url'] . '.pdf';
        $view_url = $row['view_url'];
        
        $parsed_url = parse_url($row['view_url']);
        $base_domain = $parsed_url['scheme'] . '://' . $parsed_url['host'];
        $correction_url = $base_domain . '/invoices/new?from=' . $row['remote_id'] . '&kind=correction';

        $html = '<div class="invoice-row" style="padding: 2px 0; display:flex; justify-content:space-between; align-items:center;">';
        
        $html .= '  <div>';
        $html .= '    <a href="'.$view_url.'" target="_blank" onclick="event.stopPropagation();" title="Otwórz w Fakturowni" style="font-size:14px; font-weight:bold; color:#333; text-decoration:none;" onmouseover="this.style.textDecoration=\'underline\'" onmouseout="this.style.textDecoration=\'none\'">'.$number.'</a>';
        $html .= '  </div>';

        $html .= '  <div style="display:flex; align-items:center;">';
        
        $html .= '    <div class="btn-group" style="margin-right: 0;">';
        $html .= '      <a href="'.$pdf_url.'" class="btn btn-default btn-xs" target="_blank" onclick="event.stopPropagation();" title="Pobierz PDF">
                          <i class="icon-file-pdf-o" style="color:#d9534f; font-size:14px;"></i> PDF
                        </a>';
        $html .= '      <a href="'.$view_url.'" target="_blank" class="btn btn-default btn-xs" onclick="event.stopPropagation();" title="Zobacz w Fakturowni">
                          <i class="icon-external-link" style="color:#666;"></i>
                        </a>';
        $html .= '    </div>';

        if ($row['kind'] == 'vat' || $row['kind'] == 'receipt') {
            $html .= '    <a href="'.$correction_url.'" target="_blank" class="btn btn-default btn-xs" onclick="event.stopPropagation();" title="Wystaw Korektę" style="margin-left: 15px; border-color:#ccc; background-color:#fcfcfc;">
                            <i class="icon-magic" style="color:#333;"></i> Wystaw korektę
                          </a>';
        }

        $html .= '  </div>';
        $html .= '</div>';

        if ($row['kind'] != 'correction' && method_exists('DxFakturowniaInvoice', 'getCorrectionsForInvoice')) {
            $corrections = DxFakturowniaInvoice::getCorrectionsForInvoice($row['remote_id']);

            if ($corrections && count($corrections) > 0) {
                foreach ($corrections as $cor) {
                    $kor_pdf = $cor['view_url'] . '.pdf';
                    $kor_view = $cor['view_url'];
                    $html .= '<div class="correction-row" style="padding: 4px 0 4px 20px; border-left: 2px solid #ccc; margin-top:5px; margin-left:5px; display:flex; justify-content:space-between; align-items:center;">';
                    
                    $html .= '  <div>';
                    $html .= '    <i class="icon-level-up icon-rotate-90" style="margin-right:5px; color:#ccc;"></i>';
                    // ZMIANA TUTAJ: FV KOR zamiast KOR
                    $html .= '    <span class="badge badge-danger" style="margin-right:5px; font-size:9px;">FV KOR</span>';
                    $html .= '    <a href="'.$kor_view.'" target="_blank" onclick="event.stopPropagation();" style="color:#333; text-decoration:none; font-weight:bold;" onmouseover="this.style.textDecoration=\'underline\'" onmouseout="this.style.textDecoration=\'none\'">'.$cor['number'].'</a>';
                    $html .= '  </div>';

                    $html .= '  <div>';
                    $html .= '    <a href="'.$kor_pdf.'" class="btn btn-default btn-xs" target="_blank" onclick="event.stopPropagation();" title="Pobierz Korektę">
                                    <i class="icon-file-pdf-o" style="color:#d9534f; font-size:14px;"></i> PDF
                                  </a>';
                    $html .= '  </div>';
                    
                    $html .= '</div>';
                }
            }
        }
        return $html;
    }

    public function displayStatusLabel($status, $row)
    {
        $html = '<div style="white-space:nowrap;">';
        $html .= '<span class="label label-info" style="margin-right: 5px;">Wystawiona</span>';

        if ($status == 'paid') {
            $html .= '<span class="label label-success">Opłacona</span>';
        } elseif ($status == 'issued') {
            $html .= '<span class="label label-danger">Brak wpłaty</span>';
        } elseif ($status == 'partial') {
            $html .= '<span class="label label-warning">Częściowo opłacona</span>';
        } elseif ($status == 'overdue') {
            $html .= '<span class="label label-danger">Przeterminowana</span>';
        }

        $html .= '</div>';
        return $html;
    }

    public function displayKindLabel($kind, $row)
    {
        // ZMIANA NAZEWNICTWA NA BADGE'ACH
        $labels = [
            'vat' => ['class' => 'label-success', 'text' => 'FV VAT'], // ZMIANA
            'correction' => ['class' => 'label-danger', 'text' => 'FV KOR'], // ZMIANA
            'proforma' => ['class' => 'label-info', 'text' => 'PRO'],
            'receipt' => ['class' => 'label-warning', 'text' => 'PAR'],
        ];

        $data = isset($labels[$kind]) ? $labels[$kind] : ['class' => 'label-default', 'text' => strtoupper($kind)];
        return '<span class="label ' . $data['class'] . '">' . $data['text'] . '</span>';
    }
}