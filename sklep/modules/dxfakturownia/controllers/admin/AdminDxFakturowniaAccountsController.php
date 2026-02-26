<?php

class AdminDxFakturowniaAccountsController extends ModuleAdminController
{
    public function __construct()
    {
        $this->bootstrap = true;
        $this->table = 'dxfakturownia_accounts';
        $this->className = 'FakturowniaAccount';
        $this->identifier = 'id_dxfakturownia_account';
        $this->default_order_by = 'id_dxfakturownia_account';
        $this->default_order_way = 'ASC';
        $this->lang = false; 
        
        parent::__construct();

        $this->fields_list = [
            'id_dxfakturownia_account' => [
                'title' => 'ID',
                'align' => 'center',
                'class' => 'fixed-width-xs',
            ],
            'name' => ['title' => $this->l('Nazwa konta')],
            'domain' => ['title' => $this->l('Domena')],
            'connection_status' => [
                'title' => $this->l('Status API'),
                'align' => 'center',
                'callback' => 'displayApiStatus',
                'orderby' => false,
                'search' => false,
            ],
            'is_default' => [
                'title' => $this->l('Domyślne'),
                'active' => 'status_default',
                'type' => 'bool',
                'align' => 'center',
                'ajax' => true,
                'orderby' => false,
            ],
            'active' => [
                'title' => $this->trans('Enabled', [], 'Admin.Global'),
                'active' => 'status',
                'type' => 'bool',
                'align' => 'center',
                'ajax' => true,
            ],
        ];

        $this->addRowAction('edit');
        $this->addRowAction('delete');
    }

    public function displayApiStatus($status, $row)
    {
        if ($status == 1) {
            return '<span class="label label-success" title="Połączenie poprawne"><i class="icon-check"></i> Połączono</span>';
        } else {
            $errorMsg = isset($row['last_error']) && $row['last_error'] ? $row['last_error'] : 'Brak testu';
            return '<span class="label label-danger" title="'.$errorMsg.'"><i class="icon-remove"></i> Błąd</span><br><small class="text-muted">'.$errorMsg.'</small>';
        }
    }

    public function renderForm()
    {
        $this->fields_form = [
            'legend' => [
                'title' => $this->l('Konfiguracja Konta'),
                'icon' => 'icon-cogs'
            ],
            'input' => [
                [
                    'type' => 'text',
                    'label' => $this->l('Nazwa wewnętrzna'),
                    'name' => 'name',
                    'required' => true,
                ],
                [
                    'type' => 'text',
                    'label' => $this->l('API Token'),
                    'name' => 'api_token',
                    'required' => true,
                ],
                [
                    'type' => 'text',
                    'label' => $this->l('Domena (lub prefix)'),
                    'name' => 'domain',
                    'required' => true,
                    'desc' => $this->l('np. mojafirma.fakturownia.pl'),
                ],
                [
                    'type' => 'switch',
                    'label' => $this->l('Konto domyślne'),
                    'name' => 'is_default',
                    'is_bool' => true,
                    'values' => [
                        ['id' => 'on', 'value' => 1, 'label' => 'Tak'],
                        ['id' => 'off', 'value' => 0, 'label' => 'Nie']
                    ],
                ],
                [
                    'type' => 'switch',
                    'label' => $this->trans('Active', [], 'Admin.Global'),
                    'name' => 'active',
                    'is_bool' => true,
                    'values' => [
                        ['id' => 'active_on', 'value' => 1, 'label' => 'Włączone'],
                        ['id' => 'active_off', 'value' => 0, 'label' => 'Wyłączone']
                    ],
                ],
            ],
            'submit' => ['title' => $this->trans('Save', [], 'Admin.Actions')]
        ];

        return parent::renderForm();
    }

    // --- ZMODYFIKOWANA METODA: Wyświetla listę kont ORAZ ustawienia globalne ---
    public function renderList()
    {
        $this->context->smarty->assign(array(
            'module_dir' => _MODULE_DIR_ . 'dxfakturownia/',
        ));

        $tpl = _PS_MODULE_DIR_ . 'dxfakturownia/views/templates/admin/info_header.tpl';
        $infoBlock = file_exists($tpl) ? $this->context->smarty->fetch($tpl) : '';
        
        // 1. Tabela kont (standardowa)
        $listHtml = parent::renderList();

        // 2. Formularz ustawień automatyzacji (DODANE)
        $configHtml = $this->renderGlobalConfigForm();

        return $infoBlock . $listHtml . $configHtml;
    }

    // --- NOWA METODA: Generuje formularz ustawień Paragon vs FV ---
    public function renderGlobalConfigForm()
    {
        $fields_form = [
            'form' => [
                'legend' => [
                    'title' => $this->l('Ustawienia Automatyzacji (Po spakowaniu)'),
                    'icon' => 'icon-settings'
                ],
                'input' => [
                    [
                        'type' => 'select',
                        'label' => $this->l('Domyślny dokument dla klienta detalicznego (Brak NIP)'),
                        'name' => 'DX_B2C_DOC_TYPE',
                        'desc' => $this->l('Wybierz, co wystawiać klientom, którzy nie podali NIP. Wymaga zapisu.'),
                        'options' => [
                            'query' => [
                                ['id' => 'receipt', 'name' => 'Paragon (Domyślnie)'],
                                ['id' => 'vat', 'name' => 'Faktura VAT (Imienna)'],
                            ],
                            'id' => 'id',
                            'name' => 'name'
                        ]
                    ]
                ],
                'submit' => [
                    'title' => $this->l('Zapisz ustawienia automatyzacji'),
                    'class' => 'btn btn-default pull-right',
                    'name' => 'submitDxConfig'
                ]
            ]
        ];

        $helper = new HelperForm();
        $helper->module = $this->module;
        $helper->token = Tools::getAdminTokenLite('AdminDxFakturowniaAccounts');
        $helper->currentIndex = AdminController::$currentIndex . '&configure=' . $this->module->name;
        $helper->default_form_language = (int)Configuration::get('PS_LANG_DEFAULT');
        $helper->title = $this->l('Konfiguracja');
        $helper->submit_action = 'submitDxConfig';

        // Pobierz aktualną wartość (domyślnie paragon)
        $helper->fields_value['DX_B2C_DOC_TYPE'] = Configuration::get('DX_B2C_DOC_TYPE', 'receipt');

        return $helper->generateForm([$fields_form]);
    }

    // --- ZMODYFIKOWANA METODA: Obsługa zapisu ustawień ---
    public function postProcess()
    {
        // Zapis konfiguracji automatyzacji
        if (Tools::isSubmit('submitDxConfig')) {
            $b2cType = Tools::getValue('DX_B2C_DOC_TYPE');
            Configuration::updateValue('DX_B2C_DOC_TYPE', $b2cType);
            $this->confirmations[] = $this->l('Zapisano ustawienia automatyzacji.');
        }

        parent::postProcess();
    }

    public function processSave()
    {
        $object = parent::processSave();

        if ($object && !empty($object->id)) {
            $client = new FakturowniaClient($object->api_token, $object->domain);
            $test = $client->testConnection();

            $object->connection_status = $test['success'] ? 1 : 0;
            $object->last_error = $test['success'] ? '' : $test['message'];
            $object->update(); 

            if ($test['success']) {
                $this->confirmations[] = "Połączenie z Fakturownia.pl nawiązane pomyślnie!";
            } else {
                $this->errors[] = "Zapisano, ale test połączenia nieudany: " . $test['message'];
            }

            if ($object->is_default) {
                Db::getInstance()->execute('
                    UPDATE `' . _DB_PREFIX_ . 'dxfakturownia_accounts`
                    SET `is_default` = 0
                    WHERE `id_dxfakturownia_account` != ' . (int)$object->id
                );
            }
        }
        return $object;
    }
}