<?php

require_once(dirname(__FILE__) . '/../../classes/AzadaWholesaler.php');
require_once(dirname(__FILE__) . '/../../classes/AzadaMapping.php');
require_once(dirname(__FILE__) . '/../../classes/AzadaImportEngine.php');
require_once(dirname(__FILE__) . '/../../classes/helpers/AzadaFileHelper.php');
require_once(dirname(__FILE__) . '/../../classes/services/AzadaRawSchema.php');

// Załączamy plik główny modułu (dla funkcji naprawczej)
require_once(dirname(__FILE__) . '/../../azada_wholesaler_pro.php');

// Załączamy nowe silniki
if (file_exists(dirname(__FILE__) . '/../../classes/services/AzadaVerificationEngine.php')) {
    require_once(dirname(__FILE__) . '/../../classes/services/AzadaVerificationEngine.php');
}
if (file_exists(dirname(__FILE__) . '/../../classes/integrations/AzadaBioPlanetB2B.php')) {
    require_once(dirname(__FILE__) . '/../../classes/integrations/AzadaBioPlanetB2B.php');
}
if (file_exists(dirname(__FILE__) . '/../../classes/integrations/AzadaEkoWital.php')) {
    require_once(dirname(__FILE__) . '/../../classes/integrations/AzadaEkoWital.php');
}

class AdminAzadaWholesalerController extends ModuleAdminController
{
    public function __construct()
    {
        $this->table = 'azada_wholesaler_pro_integration';
        $this->className = 'AzadaWholesaler';
        $this->identifier = 'id_wholesaler';
        $this->bootstrap = true;

        parent::__construct();

        // --- AUTOMATYCZNA NAPRAWA STRUKTURY (BEZ REINSTALACJI) ---
        // 1. Sprawdzamy stare tabele
        AzadaWholesaler::ensureDatabaseStructure();
        
        // 2. Sprawdzamy NOWE tabele (Weryfikacja FV)
        if (class_exists('AzadaVerificationEngine')) {
            AzadaVerificationEngine::ensureDatabase();
        }

        // 3. Sprawdzamy czy istnieje NOWA zakładka w menu
        if (class_exists('Azada_Wholesaler_Pro')) {
            Azada_Wholesaler_Pro::ensureVerificationTab();
        }
        // ---------------------------------------------------------

        $this->fields_list = [
            'id_wholesaler' => ['title' => 'ID', 'align' => 'center', 'class' => 'fixed-width-xs'],
            'name' => [
                'title' => 'Nazwa Integracji', 
                'width' => 150, 
                'style' => 'font-weight:bold; font-size:14px;'
            ],
            'raw_table_name' => [ 
                'title' => 'Tabela Danych (SQL)',
                'width' => 150,
                'color' => '#777',
                'align' => 'center'
            ],
            'diagnostic_result' => [
                'title' => 'Statusy Połączeń (Auto-Check)',
                'align' => 'left',
                'callback' => 'displayDiagnosticGrid', 
                'width' => 'auto'
            ],
            'active' => [
                'title' => 'Konfiguracja B2B', 
                'align' => 'center', 
                'callback' => 'displayB2BButton', 
                'search' => false,
                'orderby' => false
            ],
            'last_import' => ['title' => 'Ostatnie Pobranie', 'type' => 'datetime', 'align' => 'center'],
        ];

        $this->addRowAction('import');
        $this->addRowAction('test');
        $this->addRowAction('edit');
        $this->addRowAction('delete');
    }

    public function displayB2BButton($value, $row)
    {
        $hasCreds = (!empty($row['b2b_login']) && !empty($row['b2b_password']));
        $btnClass = $hasCreds ? 'btn-success' : 'btn-default';
        $icon = $hasCreds ? 'icon-check' : 'icon-key';
        $text = $hasCreds ? 'Zalogowany' : 'Logowanie';
        
        $login = isset($row['b2b_login']) ? $row['b2b_login'] : '';
        $pass = isset($row['b2b_password']) ? base64_decode($row['b2b_password']) : '';
        $idElem = $row['id_wholesaler'];

        return '<a href="javascript:void(0);" class="btn btn-sm '.$btnClass.'" 
                onclick="openB2BModal(event, '.$idElem.', \''.$login.'\', \''.$pass.'\'); return false;"
                title="Skonfiguruj dostęp do panelu zamowienia"
                style="display:inline-block; margin-top:2px; position:relative; z-index:999;">
                <i class="'.$icon.'"></i> '.$text.'
                </a>';
    }

    public function displayImportLink($token = null, $id = null, $name = null)
    {
        $href = self::$currentIndex . '&action=importData&ajax=1&id_wholesaler=' . $id . '&token=' . ($token != null ? $token : $this->token);
        return '<a href="' . $href . '" class="btn btn-default" title="Pobierz dane 1:1" onclick="return runImport(this, \'' . $href . '\');" style="margin-right:5px; border-color:#ddd;">
            <i class="icon-cloud-download"></i> POBIERZ DANE
        </a>';
    }

    public function ajaxProcessSaveB2B()
    {
        $id = (int)Tools::getValue('id_wholesaler');
        $login = trim(Tools::getValue('b2b_login'));
        $pass = trim(Tools::getValue('b2b_password'));

        if (!$id) die(json_encode(['status'=>'error', 'msg'=>'Brak ID']));

        $obj = new AzadaWholesaler($id);
        if (!Validate::isLoadedObject($obj)) die(json_encode(['status'=>'error', 'msg'=>'Obiekt nie istnieje']));

        if (empty($login) || empty($pass)) {
            $obj->b2b_login = '';
            $obj->b2b_password = '';
            if ($obj->update()) {
                $cookieSlug = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $obj->name));
                $cookieFile = _PS_MODULE_DIR_ . 'azada_wholesaler_pro/cookies_' . $cookieSlug . '.txt';
                if (file_exists($cookieFile)) {
                    @unlink($cookieFile);
                }
                die(json_encode(['status'=>'success', 'msg'=>'Dane logowania B2B zostały usunięte.']));
            }
            die(json_encode(['status'=>'error', 'msg'=>'Błąd zapisu bazy danych.']));
        }

        if (class_exists('AzadaBioPlanetB2B')) {
            $verifier = new AzadaBioPlanetB2B();
            $isLogged = $verifier->checkLogin($login, $pass);
            
            if (!$isLogged) {
                die(json_encode(['status'=>'error', 'msg'=>'BŁĄD LOGOWANIA! Hurtownia odrzuciła podany login lub hasło. Sprawdź dane.']));
            }
        }

        $obj->b2b_login = $login;
        $obj->b2b_password = !empty($pass) ? base64_encode($pass) : ''; 
        
        if ($obj->update()) {
            die(json_encode(['status'=>'success', 'msg'=>'Weryfikacja pomyślna! Dane zapisane.']));
        } else {
            die(json_encode(['status'=>'error', 'msg'=>'Błąd zapisu bazy danych.']));
        }
    }

    public function ajaxProcessImportData()
    {
        @ini_set('max_execution_time', 1200);
        @ini_set('memory_limit', '512M');
        $id = (int)Tools::getValue('id_wholesaler');
        $engine = new AzadaImportEngine();
        $result = $engine->runFullImport($id);
        header('Content-Type: application/json');
        die(json_encode($result));
    }

    public function ajaxProcessTestConnection()
    {
        @ini_set('display_errors', 'off');
        $id = (int)Tools::getValue('id_wholesaler');
        $wholesaler = new AzadaWholesaler($id);

        header('Content-Type: application/json');

        if (!Validate::isLoadedObject($wholesaler)) die(json_encode(['status' => 'error']));

        if (stripos($wholesaler->name, 'Bio Planet') !== false && !empty($wholesaler->api_key)) {
            $result = AzadaBioPlanet::runDiagnostics($wholesaler->api_key);
        } elseif (stripos($wholesaler->name, 'EkoWital') !== false && !empty($wholesaler->api_key)) {
            $result = AzadaEkoWital::runDiagnostics($wholesaler->api_key);
        } else {
            $check = AzadaFileHelper::checkUrl($wholesaler->file_url);
            $result = ['success' => $check, 'details' => ['api' => ['status' => $check]]];
        }

        $b2bStatus = (!empty($wholesaler->b2b_login) && !empty($wholesaler->b2b_password));
        $result['details']['b2b'] = ['status' => $b2bStatus];

        $wholesaler->diagnostic_result = json_encode($result);
        $wholesaler->connection_status = $result['success'] ? 1 : 2;
        try { $wholesaler->update(); } catch (Exception $e) {}

        $html = $this->generateBadgesHtml($result['details']);

        die(json_encode([
            'status' => 'success',
            'html' => $html
        ]));
    }

    public function renderList()
    {
        $saveB2BUrl = self::$currentIndex . '&action=saveB2B&ajax=1&token=' . $this->token;

        $js = "
        <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
        <script>
            function openB2BModal(event, id, login, pass) {
                if(event) { event.preventDefault(); event.stopPropagation(); }
                $('#b2b_id_wholesaler').val(id);
                $('#b2b_login_input').val(login);
                $('#b2b_pass_input').val(pass);
                $('#b2bModal').modal('show');
                return false;
            }

            $(document).ready(function() {
                $('.diagnostic-container').each(function() {
                    var container = $(this);
                    $.ajax({ type: 'GET', url: container.attr('data-test-url'), dataType: 'json', 
                        success: function(data) { 
                            if(data.status=='success') { 
                                container.fadeOut(200, function() { $(this).html(data.html).fadeIn(300); }); 
                            } 
                        } 
                    });
                });

                $('#btn-save-b2b').on('click', function() {
                    var btn = $(this);
                    var originalText = btn.html();
                    btn.html('<i class=\"icon-refresh icon-spin\"></i> Weryfikacja...').prop('disabled', true);

                    $.ajax({
                        type: 'POST',
                        url: '$saveB2BUrl',
                        data: {
                            id_wholesaler: $('#b2b_id_wholesaler').val(),
                            b2b_login: $('#b2b_login_input').val(),
                            b2b_password: $('#b2b_pass_input').val()
                        },
                        dataType: 'json',
                        success: function(data) {
                            btn.html(originalText).prop('disabled', false);
                            if (data.status == 'success') {
                                $('#b2bModal').modal('hide');
                                Swal.fire('Sukces', data.msg, 'success').then(() => {
                                    location.reload(); 
                                });
                            } else {
                                Swal.fire('Błąd Weryfikacji', data.msg, 'error');
                            }
                        },
                        error: function() {
                            btn.html(originalText).prop('disabled', false);
                            Swal.fire('Błąd', 'Błąd połączenia z serwerem.', 'error');
                        }
                    });
                });
            });

function runImport(btn, url) {
                if(event) event.stopPropagation();
                Swal.fire({
                    title: 'Potwierdzenie importu',
                    text: 'Czy na pewno chcesz pobrać PEŁNE dane 1:1 z tej hurtowni?',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#27ae60',
                    confirmButtonText: 'Tak, pobierz!',
                    cancelButtonText: 'Anuluj'
                }).then((result) => {
                    if (result.isConfirmed) {
                        var originalHtml = $(btn).html();
                        $(btn).html('<i class=\"icon-refresh icon-spin\"></i> Pobieranie...').addClass('disabled');
                        $.ajax({
                            type: 'GET', url: url, dataType: 'json',
                            success: function(data) {
                                $(btn).html(originalHtml).removeClass('disabled');
                                if (data.debug) {
                                    console.group('EkoWital import debug');
                                    console.log(data.debug);
                                    console.groupEnd();
                                }
                                if (data.status == 'success') Swal.fire('Sukces!', data.msg, 'success');
                                else Swal.fire('Wystąpił błąd', data.msg, 'error');
                            },
                            error: function(jqXHR) {
                                $(btn).html(originalHtml).removeClass('disabled');
                                Swal.fire('Błąd Krytyczny', 'Timeout.', 'error');
                            }
                        });
                    }
                });
                return false;
            }
            function testConnection(btn, url) { location.reload(); return false; }
        </script>
        ";

        $total = Db::getInstance()->getValue('SELECT COUNT(*) FROM ' . _DB_PREFIX_ . $this->table);
        $active = Db::getInstance()->getValue('SELECT COUNT(*) FROM ' . _DB_PREFIX_ . $this->table . ' WHERE active = 1');
        
        $this->context->smarty->assign([
            'total_wholesalers' => (int)$total,
            'active_wholesalers' => (int)$active,
            'add_url' => self::$currentIndex . '&add' . $this->table . '&token=' . $this->token
        ]);
        
        $header = $this->context->smarty->fetch(_PS_MODULE_DIR_ . $this->module->name . '/views/templates/admin/wholesaler_header.tpl');
        return $js . $header . parent::renderList();
    }

    public function displayDiagnosticGrid($json, $row)
    {
        $rowId = 'diagnostic-row-' . $row['id_wholesaler'];
        $testUrl = self::$currentIndex . '&action=testConnection&ajax=1&id_wholesaler=' . $row['id_wholesaler'] . '&token=' . $this->token;
        return '<div id="'.$rowId.'" class="diagnostic-container" data-test-url="'.$testUrl.'"><span class="label label-info"><i class="icon-refresh icon-spin"></i> Weryfikacja...</span></div>';
    }

    public function generateBadgesHtml($details)
    {
        $items = [
            'api'      => ['label' => 'KLUCZ API'],
            'products' => ['label' => 'PRODUKTY'],
            'stocks'   => ['label' => 'STANY'],
            'weights'  => ['label' => 'WYMIARY'],
            'b2b'      => ['label' => 'DOSTĘP B2B'] 
        ];

        $html = '';
        foreach ($items as $key => $conf) {
            $status = isset($details[$key]['status']) && $details[$key]['status'];
            
            if ($status) {
                $icon = '<i class="icon-check-circle" style="color:#27ae60; font-size:15px; vertical-align:-2px; margin-left:4px;"></i>';
                $style = 'background-color:#fff; border:1px solid #e5e5e5; color:#444;';
            } else {
                $icon = '<i class="icon-times-circle" style="color:#c0392b; font-size:15px; vertical-align:-2px; margin-left:4px;"></i>';
                $style = 'background-color:#fff5f5; border:1px solid #f8d7da; color:#721c24; opacity:0.6;';
            }
            if ($key == 'b2b') $style .= ' border-left: 3px solid #3498db;';

            $css = 'display:inline-block; padding:4px 10px; margin-right:6px; border-radius:50px; font-weight:bold; font-size:11px; text-transform:uppercase; ' . $style;

            $html .= '<span style="'.$css.'">
                '.$conf['label'].' '.$icon.'
            </span>';
        }
        return $html;
    }

    public function displayTestLink($token = null, $id = null, $name = null) {
        $href = self::$currentIndex . '&action=testConnection&ajax=1&id_wholesaler=' . $id . '&token=' . ($token != null ? $token : $this->token);
        return '<a href="' . $href . '" class="btn btn-default" title="Odśwież" onclick="return testConnection(this, \'' . $href . '\');"><i class="icon-refresh"></i></a>';
    }
    public function displayUrlShort($url) { return ''; } 
    public function initToolbar() { parent::initToolbar(); unset($this->toolbar_btn['new']); }

    public function processDelete()
    {
        $id = (int)Tools::getValue('id_wholesaler');
        if ($id) {
            $obj = new AzadaWholesaler($id);
            if (Validate::isLoadedObject($obj) && !empty($obj->raw_table_name)) {
                $tableName = _DB_PREFIX_ . pSQL($obj->raw_table_name);
                Db::getInstance()->execute("DROP TABLE IF EXISTS `$tableName`");
            }
        }
        return parent::processDelete();
    }

    public function processBulkDelete()
    {
        $ids = Tools::getValue($this->table . 'Box');
        if (!empty($ids) && is_array($ids)) {
            foreach ($ids as $id) {
                $id = (int)$id;
                if (!$id) {
                    continue;
                }
                $obj = new AzadaWholesaler($id);
                if (Validate::isLoadedObject($obj) && !empty($obj->raw_table_name)) {
                    $tableName = _DB_PREFIX_ . pSQL($obj->raw_table_name);
                    Db::getInstance()->execute("DROP TABLE IF EXISTS `$tableName`");
                }
            }
        }
        return parent::processBulkDelete();
    }
    
    public function postProcess() {
        if (Tools::isSubmit('submitAdd' . $this->table)) {
            $preset = Tools::getValue('preset_integration');
            
            if ($preset === 'bioplanet') {
                $apiKey = Tools::getValue('api_key_input');
                if (empty($apiKey)) { $this->errors[] = $this->l('Podaj Klucz API.'); return; }
                
                $_POST['name'] = 'Bio Planet';
                $_POST['raw_table_name'] = 'azada_raw_bioplanet';
                
                $links = AzadaBioPlanet::generateLinks($apiKey);
                $settings = AzadaBioPlanet::getSettings();
                $_POST['file_url'] = $links['products'];
                $_POST['file_format'] = $settings['file_format'];
                $_POST['delimiter'] = $settings['delimiter'];
                $_POST['skip_header'] = $settings['skip_header'];
                $_POST['api_key'] = trim($apiKey);
            } elseif ($preset === 'ekowital') {
                $apiKey = Tools::getValue('api_key_input');
                if (empty($apiKey)) { $this->errors[] = $this->l('Podaj Klucz API.'); return; }

                $_POST['name'] = 'EkoWital';
                $_POST['raw_table_name'] = 'azada_raw_ekowital';

                $links = AzadaEkoWital::generateLinks($apiKey);
                $settings = AzadaEkoWital::getSettings();
                $_POST['file_url'] = $links['products'];
                $_POST['file_format'] = $settings['file_format'];
                $_POST['delimiter'] = $settings['delimiter'];
                $_POST['skip_header'] = $settings['skip_header'];
                $_POST['api_key'] = trim($apiKey);
            }
        }
        parent::postProcess();

        if (Tools::isSubmit('submitAdd' . $this->table) || Tools::isSubmit('submitUpdate' . $this->table)) {
            $rawTableName = Tools::getValue('raw_table_name');
            if ($rawTableName === 'azada_raw_bioplanet' || $rawTableName === 'azada_raw_ekowital') {
                AzadaRawSchema::createTable($rawTableName);
            }
        }
    }

    public function renderForm() {
        $js_logic = "<script>$(document).ready(function() { function toggleFields() { var val = $('#preset_integration').val(); if (val == 'bioplanet' || val == 'ekowital') { $('.custom-field').closest('.form-group').hide(); $('.bioplanet-field').closest('.form-group').show(); $('input[name=\"name\"]').closest('.form-group').hide(); $('input[name=\"raw_table_name\"]').closest('.form-group').hide(); } else { $('.custom-field').closest('.form-group').show(); $('.bioplanet-field').closest('.form-group').hide(); $('input[name=\"name\"]').closest('.form-group').show(); $('input[name=\"raw_table_name\"]').closest('.form-group').show(); } } $('#preset_integration').change(toggleFields); toggleFields(); });</script>";

        $this->fields_form = [
            'legend' => ['title' => $this->l('Nowa Integracja'), 'icon' => 'icon-cloud-upload'],
            'input' => [
                ['type' => 'html', 'name' => 'html_js', 'html_content' => $js_logic],
                ['type' => 'select', 'label' => 'Szablon', 'name' => 'preset_integration', 'id' => 'preset_integration', 'required' => true, 'options' => ['query' => [['id' => 'custom', 'name' => '-- Własna / Inna --'], ['id' => 'bioplanet', 'name' => 'BIO PLANET (Automatyczna)'], ['id' => 'ekowital', 'name' => 'EKOWITAL (Automatyczna)']], 'id' => 'id', 'name' => 'name']],
                
                ['type' => 'text', 'label' => 'Nazwa', 'name' => 'name', 'required' => true],
                ['type' => 'text', 'label' => 'Tabela Produktów (SQL)', 'name' => 'raw_table_name', 'class' => 'custom-field', 'desc' => 'Nazwa tabeli w bazie (np. azada_raw_bioplanet), w której znajdują się produkty tej hurtowni.'],

                ['type' => 'text', 'label' => 'Klucz API', 'name' => 'api_key_input', 'class' => 'bioplanet-field', 'desc' => 'Wklej klucz z panelu B2B.'],
                ['type' => 'text', 'label' => 'Link URL', 'name' => 'file_url', 'class' => 'custom-field'],
                ['type' => 'select', 'label' => 'Format', 'name' => 'file_format', 'class' => 'custom-field', 'options' => ['query' => [['id'=>'csv','name'=>'CSV']], 'id'=>'id', 'name'=>'name']],
                ['type' => 'select', 'label' => 'Separator', 'name' => 'delimiter', 'class' => 'custom-field', 'options' => ['query' => [['id'=>';','name'=>';'], ['id'=>',','name'=>',']], 'id'=>'id', 'name'=>'name']],
                ['type' => 'switch', 'label' => 'Pomiń nagłówek', 'name' => 'skip_header', 'class' => 'custom-field', 'is_bool' => true, 'values' => [['id'=>'on','value'=>1,'label'=>'Tak'],['id'=>'off','value'=>0,'label'=>'Nie']]],
                ['type' => 'switch', 'label' => 'Aktywna', 'name' => 'active', 'is_bool' => true, 'values' => [['id'=>'on','value'=>1,'label'=>'Tak'],['id'=>'off','value'=>0,'label'=>'Nie']]]
            ],
            'submit' => ['title' => 'Zapisz', 'class' => 'btn btn-primary']
        ];
        return parent::renderForm();
    }
}
