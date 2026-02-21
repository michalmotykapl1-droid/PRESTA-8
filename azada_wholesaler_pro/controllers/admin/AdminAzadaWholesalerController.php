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

$integrationsDir = dirname(__FILE__) . '/../../classes/integrations';
if (is_dir($integrationsDir)) {
    foreach (glob($integrationsDir . '/*.php') as $integrationFile) {
        $baseName = basename($integrationFile);
        if ($baseName === 'index.php') {
            continue;
        }
        require_once($integrationFile);
    }
}

class AdminAzadaWholesalerController extends ModuleAdminController
{
    private function getHubCardsData()
    {
        $rows = Db::getInstance()->executeS('SELECT w.id_wholesaler, w.name, w.raw_table_name, w.active, w.last_import, w.connection_status, hs.hub_enabled, hs.sync_mode, hs.price_field, hs.notes, hs.use_local_cache, hs.cache_ttl_minutes, hs.price_multiplier, hs.price_markup_percent, hs.stock_buffer, hs.stock_min_limit, hs.stock_max_limit, hs.price_min_limit, hs.price_max_limit, hs.zero_below_stock, hs.seo_strip_style, hs.seo_strip_iframe, hs.seo_strip_links, hs.seo_short_desc_fallback, hs.seo_meta_title_template, hs.seo_meta_desc_template, hs.seo_description_prefix, hs.seo_description_suffix, hs.quality_require_ean, hs.quality_require_name, hs.quality_require_price, hs.quality_require_stock, hs.quality_reject_missing_data FROM `'._DB_PREFIX_.'azada_wholesaler_pro_integration` w LEFT JOIN `'._DB_PREFIX_.'azada_wholesaler_pro_hub_settings` hs ON hs.id_wholesaler = w.id_wholesaler ORDER BY w.name ASC');

        if (!is_array($rows)) {
            return [];
        }

        foreach ($rows as &$row) {
            $row['id_wholesaler'] = (int)$row['id_wholesaler'];
            $row['active'] = (int)$row['active'];
            $row['connection_status'] = isset($row['connection_status']) ? (int)$row['connection_status'] : 0;
            $row['hub_enabled'] = isset($row['hub_enabled']) ? (int)$row['hub_enabled'] : 1;
            $row['sync_mode'] = isset($row['sync_mode']) && $row['sync_mode'] !== '' ? (string)$row['sync_mode'] : 'api';
            $row['price_field'] = isset($row['price_field']) && $row['price_field'] !== '' ? (string)$row['price_field'] : 'CenaPoRabacieNetto';
            $row['notes'] = isset($row['notes']) ? (string)$row['notes'] : '';
            $row['use_local_cache'] = isset($row['use_local_cache']) ? (int)$row['use_local_cache'] : 1;
            $row['cache_ttl_minutes'] = isset($row['cache_ttl_minutes']) ? max(1, (int)$row['cache_ttl_minutes']) : 60;
            $row['price_multiplier'] = isset($row['price_multiplier']) ? (float)$row['price_multiplier'] : 1.0000;
            $row['price_markup_percent'] = isset($row['price_markup_percent']) ? (float)$row['price_markup_percent'] : 0.00;
            $row['stock_buffer'] = isset($row['stock_buffer']) ? (int)$row['stock_buffer'] : 0;
            $row['stock_min_limit'] = isset($row['stock_min_limit']) ? (int)$row['stock_min_limit'] : 0;
            $row['stock_max_limit'] = isset($row['stock_max_limit']) ? (int)$row['stock_max_limit'] : 0;
            $row['price_min_limit'] = isset($row['price_min_limit']) ? (float)$row['price_min_limit'] : 0.00;
            $row['price_max_limit'] = isset($row['price_max_limit']) ? (float)$row['price_max_limit'] : 0.00;
            $row['zero_below_stock'] = isset($row['zero_below_stock']) ? (int)$row['zero_below_stock'] : 0;
            $row['seo_strip_style'] = isset($row['seo_strip_style']) ? (int)$row['seo_strip_style'] : 1;
            $row['seo_strip_iframe'] = isset($row['seo_strip_iframe']) ? (int)$row['seo_strip_iframe'] : 1;
            $row['seo_strip_links'] = isset($row['seo_strip_links']) ? (int)$row['seo_strip_links'] : 0;
            $row['seo_short_desc_fallback'] = isset($row['seo_short_desc_fallback']) ? (int)$row['seo_short_desc_fallback'] : 1;
            $row['seo_meta_title_template'] = isset($row['seo_meta_title_template']) ? (string)$row['seo_meta_title_template'] : '';
            $row['seo_meta_desc_template'] = isset($row['seo_meta_desc_template']) ? (string)$row['seo_meta_desc_template'] : '';
            $row['seo_description_prefix'] = isset($row['seo_description_prefix']) ? (string)$row['seo_description_prefix'] : '';
            $row['seo_description_suffix'] = isset($row['seo_description_suffix']) ? (string)$row['seo_description_suffix'] : '';
            $row['quality_require_ean'] = isset($row['quality_require_ean']) ? (int)$row['quality_require_ean'] : 1;
            $row['quality_require_name'] = isset($row['quality_require_name']) ? (int)$row['quality_require_name'] : 1;
            $row['quality_require_price'] = isset($row['quality_require_price']) ? (int)$row['quality_require_price'] : 1;
            $row['quality_require_stock'] = isset($row['quality_require_stock']) ? (int)$row['quality_require_stock'] : 1;
            $row['quality_reject_missing_data'] = isset($row['quality_reject_missing_data']) ? (int)$row['quality_reject_missing_data'] : 1;
        }

        return $rows;
    }

    public function postProcessHubCards()
    {
        if (!Tools::isSubmit('submitAzadaHubCards')) {
            return;
        }

        $enabledRows = Tools::getValue('hub_enabled', []);

        $cards = $this->getHubCardsData();
        foreach ($cards as $card) {
            $idWholesaler = (int)$card['id_wholesaler'];
            if ($idWholesaler <= 0) {
                continue;
            }

            $enabled = (isset($enabledRows[$idWholesaler]) && (int)$enabledRows[$idWholesaler] === 1) ? 1 : 0;

            $exists = (int)Db::getInstance()->getValue('SELECT COUNT(*) FROM `'._DB_PREFIX_.'azada_wholesaler_pro_hub_settings` WHERE id_wholesaler='.(int)$idWholesaler);

            $payload = [
                'hub_enabled' => (int)$enabled,
                'wholesaler_name' => isset($card['name']) ? pSQL((string)$card['name']) : null,
                'date_upd' => date('Y-m-d H:i:s'),
            ];

            if ($exists > 0) {
                Db::getInstance()->update('azada_wholesaler_pro_hub_settings', $payload, 'id_wholesaler='.(int)$idWholesaler);
            } else {
                $payload['id_wholesaler'] = (int)$idWholesaler;
                $payload['wholesaler_name'] = isset($card['name']) ? pSQL((string)$card['name']) : null;
                $payload['sync_mode'] = 'api';
                $payload['price_field'] = 'CenaPoRabacieNetto';
                $payload['notes'] = null;
                $payload['use_local_cache'] = 1;
                $payload['cache_ttl_minutes'] = 60;
                $payload['price_multiplier'] = 1.0000;
                $payload['price_markup_percent'] = 0.00;
                $payload['stock_buffer'] = 0;
                $payload['stock_min_limit'] = 0;
                $payload['stock_max_limit'] = 0;
                $payload['price_min_limit'] = 0.00;
                $payload['price_max_limit'] = 0.00;
                $payload['zero_below_stock'] = 0;
                $payload['seo_strip_style'] = 1;
                $payload['seo_strip_iframe'] = 1;
                $payload['seo_strip_links'] = 0;
                $payload['seo_short_desc_fallback'] = 1;
                $payload['seo_meta_title_template'] = null;
                $payload['seo_meta_desc_template'] = null;
                $payload['seo_description_prefix'] = null;
                $payload['seo_description_suffix'] = null;
                $payload['quality_require_ean'] = 1;
                $payload['quality_require_name'] = 1;
                $payload['quality_require_price'] = 1;
                $payload['quality_require_stock'] = 1;
                $payload['quality_reject_missing_data'] = 1;
                $payload['date_add'] = date('Y-m-d H:i:s');
                Db::getInstance()->insert('azada_wholesaler_pro_hub_settings', $payload);
            }
        }

        $this->confirmations[] = $this->l('Zapisano ustawienia kafli hurtowni.');
    }



    public function postProcessHubSettings()
    {
        if (!Tools::isSubmit('submitAzadaHubSettings')) {
            return;
        }

        $idWholesaler = (int)Tools::getValue('hub_settings_id_wholesaler');
        if ($idWholesaler <= 0) {
            $this->errors[] = $this->l('Nieprawidłowa hurtownia dla ustawień.');
            return;
        }

        $card = Db::getInstance()->getRow('SELECT id_wholesaler, name, raw_table_name FROM `'._DB_PREFIX_.'azada_wholesaler_pro_integration` WHERE id_wholesaler='.(int)$idWholesaler);
        if (!is_array($card) || empty($card['id_wholesaler'])) {
            $this->errors[] = $this->l('Nie znaleziono hurtowni dla ustawień.');
            return;
        }

        if ((string)$card['raw_table_name'] !== 'azada_raw_bioplanet') {
            $this->errors[] = $this->l('Na tym etapie ustawienia szczegółowe są dostępne tylko dla BioPlanet.');
            return;
        }

        $syncMode = trim((string)Tools::getValue('hub_settings_sync_mode', 'api'));
        if (!in_array($syncMode, ['api', 'file', 'hybrid'], true)) {
            $syncMode = 'api';
        }

        $priceField = trim((string)Tools::getValue('hub_settings_price_field', 'CenaPoRabacieNetto'));
        if ($priceField === '') {
            $priceField = 'CenaPoRabacieNetto';
        }

        $notes = trim((string)Tools::getValue('hub_settings_notes', ''));

        $useLocalCache = (int)Tools::getValue('hub_settings_use_local_cache', 1) === 1 ? 1 : 0;
        $cacheTtlMinutes = (int)Tools::getValue('hub_settings_cache_ttl_minutes', 60);
        if ($cacheTtlMinutes < 1) {
            $cacheTtlMinutes = 1;
        }
        if ($cacheTtlMinutes > 10080) {
            $cacheTtlMinutes = 10080;
        }

        $priceMultiplier = (float)Tools::getValue('hub_settings_price_multiplier', 1);
        if ($priceMultiplier <= 0) {
            $priceMultiplier = 1.0000;
        }

        $priceMarkupPercent = (float)Tools::getValue('hub_settings_price_markup_percent', 0);
        if ($priceMarkupPercent < -99.99) {
            $priceMarkupPercent = -99.99;
        }

        $stockBuffer = (int)Tools::getValue('hub_settings_stock_buffer', 0);
        $stockMinLimit = max(0, (int)Tools::getValue('hub_settings_stock_min_limit', 0));
        $stockMaxLimit = max(0, (int)Tools::getValue('hub_settings_stock_max_limit', 0));
        if ($stockMaxLimit > 0 && $stockMaxLimit < $stockMinLimit) {
            $stockMaxLimit = $stockMinLimit;
        }

        $priceMinLimit = max(0, (float)Tools::getValue('hub_settings_price_min_limit', 0));
        $priceMaxLimit = max(0, (float)Tools::getValue('hub_settings_price_max_limit', 0));
        if ($priceMaxLimit > 0 && $priceMaxLimit < $priceMinLimit) {
            $priceMaxLimit = $priceMinLimit;
        }

        $zeroBelowStock = max(0, (int)Tools::getValue('hub_settings_zero_below_stock', 0));

        $seoStripStyle = (int)Tools::getValue('hub_settings_seo_strip_style', 1) === 1 ? 1 : 0;
        $seoStripIframe = (int)Tools::getValue('hub_settings_seo_strip_iframe', 1) === 1 ? 1 : 0;
        $seoStripLinks = (int)Tools::getValue('hub_settings_seo_strip_links', 0) === 1 ? 1 : 0;
        $seoShortDescFallback = (int)Tools::getValue('hub_settings_seo_short_desc_fallback', 1) === 1 ? 1 : 0;

        $seoMetaTitleTemplate = trim((string)Tools::getValue('hub_settings_seo_meta_title_template', ''));
        $seoMetaDescTemplate = trim((string)Tools::getValue('hub_settings_seo_meta_desc_template', ''));
        $seoDescriptionPrefix = trim((string)Tools::getValue('hub_settings_seo_description_prefix', ''));
        $seoDescriptionSuffix = trim((string)Tools::getValue('hub_settings_seo_description_suffix', ''));

        if (Tools::strlen($seoMetaTitleTemplate) > 255) {
            $seoMetaTitleTemplate = Tools::substr($seoMetaTitleTemplate, 0, 255);
        }
        if (Tools::strlen($seoMetaDescTemplate) > 255) {
            $seoMetaDescTemplate = Tools::substr($seoMetaDescTemplate, 0, 255);
        }
        if (Tools::strlen($seoDescriptionPrefix) > 255) {
            $seoDescriptionPrefix = Tools::substr($seoDescriptionPrefix, 0, 255);
        }
        if (Tools::strlen($seoDescriptionSuffix) > 255) {
            $seoDescriptionSuffix = Tools::substr($seoDescriptionSuffix, 0, 255);
        }

        $qualityRequireEan = (int)Tools::getValue('hub_settings_quality_require_ean', 1) === 1 ? 1 : 0;
        $qualityRequireName = (int)Tools::getValue('hub_settings_quality_require_name', 1) === 1 ? 1 : 0;
        $qualityRequirePrice = (int)Tools::getValue('hub_settings_quality_require_price', 1) === 1 ? 1 : 0;
        $qualityRequireStock = (int)Tools::getValue('hub_settings_quality_require_stock', 1) === 1 ? 1 : 0;
        $qualityRejectMissingData = (int)Tools::getValue('hub_settings_quality_reject_missing_data', 1) === 1 ? 1 : 0;

        $exists = (int)Db::getInstance()->getValue('SELECT COUNT(*) FROM `'._DB_PREFIX_.'azada_wholesaler_pro_hub_settings` WHERE id_wholesaler='.(int)$idWholesaler);

        $payload = [
            'wholesaler_name' => isset($card['name']) ? pSQL((string)$card['name']) : null,
            'sync_mode' => pSQL($syncMode),
            'price_field' => pSQL($priceField),
            'notes' => pSQL($notes),
            'use_local_cache' => (int)$useLocalCache,
            'cache_ttl_minutes' => (int)$cacheTtlMinutes,
            'price_multiplier' => (float)$priceMultiplier,
            'price_markup_percent' => (float)$priceMarkupPercent,
            'stock_buffer' => (int)$stockBuffer,
            'stock_min_limit' => (int)$stockMinLimit,
            'stock_max_limit' => (int)$stockMaxLimit,
            'price_min_limit' => (float)$priceMinLimit,
            'price_max_limit' => (float)$priceMaxLimit,
            'zero_below_stock' => (int)$zeroBelowStock,
            'seo_strip_style' => (int)$seoStripStyle,
            'seo_strip_iframe' => (int)$seoStripIframe,
            'seo_strip_links' => (int)$seoStripLinks,
            'seo_short_desc_fallback' => (int)$seoShortDescFallback,
            'seo_meta_title_template' => $seoMetaTitleTemplate !== '' ? pSQL($seoMetaTitleTemplate) : null,
            'seo_meta_desc_template' => $seoMetaDescTemplate !== '' ? pSQL($seoMetaDescTemplate) : null,
            'seo_description_prefix' => $seoDescriptionPrefix !== '' ? pSQL($seoDescriptionPrefix) : null,
            'seo_description_suffix' => $seoDescriptionSuffix !== '' ? pSQL($seoDescriptionSuffix) : null,
            'quality_require_ean' => (int)$qualityRequireEan,
            'quality_require_name' => (int)$qualityRequireName,
            'quality_require_price' => (int)$qualityRequirePrice,
            'quality_require_stock' => (int)$qualityRequireStock,
            'quality_reject_missing_data' => (int)$qualityRejectMissingData,
            'date_upd' => date('Y-m-d H:i:s'),
        ];

        if ($exists > 0) {
            Db::getInstance()->update('azada_wholesaler_pro_hub_settings', $payload, 'id_wholesaler='.(int)$idWholesaler);
        } else {
            $payload['id_wholesaler'] = (int)$idWholesaler;
            $payload['hub_enabled'] = 1;
            $payload['date_add'] = date('Y-m-d H:i:s');
            Db::getInstance()->insert('azada_wholesaler_pro_hub_settings', $payload);
        }

        $this->confirmations[] = $this->l('Zapisano ustawienia szczegółowe dla BioPlanet.');
    }

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
        
        $login = isset($row['b2b_login']) ? (string)$row['b2b_login'] : '';
        $pass = '';
        if (isset($row['b2b_password']) && $row['b2b_password'] !== '') {
            $decodedPass = base64_decode($row['b2b_password'], true);
            $pass = ($decodedPass !== false) ? $decodedPass : '';
        }

        $loginJs = $this->encodeJsValue($login);
        $passJs = $this->encodeJsValue($pass);
        $idElem = $row['id_wholesaler'];

        return '<a href="javascript:void(0);" class="btn btn-sm '.$btnClass.'" 
                onclick="openB2BModal(event, '.$idElem.', '.$loginJs.', '.$passJs.'); return false;"
                title="Skonfiguruj dostęp do panelu zamowienia"
                style="display:inline-block; margin-top:2px; position:relative; z-index:999;">
                <i class="'.$icon.'"></i> '.$text.'
                </a>';
    }


    private function encodeJsValue($value)
    {
        $encoded = json_encode($value, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
        if ($encoded === false) {
            return '&quot;&quot;';
        }

        return htmlspecialchars($encoded, ENT_QUOTES, 'UTF-8');
    }

    public function displayImportLink($token = null, $id = null, $name = null)
    {
        $href = self::$currentIndex . '&action=importData&ajax=1&id_wholesaler=' . $id . '&token=' . ($token != null ? $token : $this->token);
        return '<a href="' . $href . '" class="btn btn-default" title="Pobierz dane 1:1" onclick="return runImport(event, this, \'' . $href . '\');" style="margin-right:5px; border-color:#ddd;">
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

        $verifier = $this->getB2BVerifier($obj->name);
        if ($verifier) {
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

    private function getB2BVerifier($wholesalerName)
    {
        if (stripos($wholesalerName, 'Bio Planet') !== false && class_exists('AzadaBioPlanetB2B')) {
            return new AzadaBioPlanetB2B();
        }
        if (stripos($wholesalerName, 'EkoWital') !== false && class_exists('AzadaEkoWitalB2B')) {
            return new AzadaEkoWitalB2B();
        }
        if ((stripos($wholesalerName, 'NaturaMed') !== false || stripos($wholesalerName, 'Natura Med') !== false) && class_exists('AzadaNaturaMedB2B')) {
            return new AzadaNaturaMedB2B();
        }
        return null;
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
        } elseif ((stripos($wholesaler->name, 'NaturaMed') !== false || stripos($wholesaler->name, 'Natura Med') !== false) && !empty($wholesaler->api_key)) {
            $result = AzadaNaturaMed::runDiagnostics($wholesaler->api_key);
        } elseif (stripos($wholesaler->name, 'ABRO') !== false && !empty($wholesaler->api_key) && class_exists('AzadaAbro')) {
            $result = AzadaAbro::runDiagnostics($wholesaler->api_key);
        } else {
            $check = AzadaFileHelper::checkUrlDetailed($wholesaler->file_url);
            $status = !empty($check['status']);
            $msg = isset($check['msg']) ? $check['msg'] : '';
            $result = ['success' => $status, 'details' => ['api' => ['status' => $status, 'msg' => $msg]]];
        }

        $b2bStatus = (!empty($wholesaler->b2b_login) && !empty($wholesaler->b2b_password));
        $result['details']['b2b'] = ['status' => $b2bStatus];

        try {
            $stockCheck = $this->checkStockStatusFromRawTable($wholesaler);
            if ($stockCheck !== null) {
                $result['details']['stocks'] = $stockCheck;
            }
        } catch (Exception $e) {
            $result['details']['stocks'] = [
                'status' => false,
                'msg' => 'Błąd weryfikacji stanów: ' . $e->getMessage(),
            ];
        }

        try {
            $dimensionsCheck = $this->checkDimensionsStatusFromRawTable($wholesaler);
            if ($dimensionsCheck !== null) {
                $result['details']['weights'] = $dimensionsCheck;
            }
        } catch (Exception $e) {
            $result['details']['weights'] = [
                'status' => false,
                'msg' => 'Błąd weryfikacji wymiarów: ' . $e->getMessage(),
            ];
        }

        $wholesaler->diagnostic_result = json_encode($result);
        $wholesaler->connection_status = $result['success'] ? 1 : 2;
        try { $wholesaler->update(); } catch (Exception $e) {}

        $html = $this->generateBadgesHtml($result['details']);

        die(json_encode([
            'status' => 'success',
            'html' => $html
        ]));
    }

    private function checkStockStatusFromRawTable($wholesaler)
    {
        if (!Validate::isLoadedObject($wholesaler) || empty($wholesaler->raw_table_name)) {
            return null;
        }

        $db = Db::getInstance();
        $rawTable = (string)$wholesaler->raw_table_name;
        $fullTableName = _DB_PREFIX_ . pSQL($rawTable);
        $fullTableNameSql = bqSQL($fullTableName);

        $tableExists = (bool)$db->getValue(
            "SELECT 1 FROM INFORMATION_SCHEMA.TABLES
             WHERE TABLE_SCHEMA = DATABASE()
             AND TABLE_NAME = '" . pSQL($fullTableName) . "'"
        );
        if (!$tableExists) {
            return [
                'status' => false,
                'msg' => 'Brak tabeli danych hurtowni.',
            ];
        }

        $columnExists = (bool)$db->getValue(
            "SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
             AND TABLE_NAME = '" . pSQL($fullTableName) . "'
             AND COLUMN_NAME = 'ilosc'"
        );

        if (!$columnExists) {
            return [
                'status' => false,
                'msg' => 'Brak kolumny ilosc w tabeli danych.',
            ];
        }

        $rowsWithStock = (int)$db->getValue(
            "SELECT COUNT(*) FROM `" . $fullTableNameSql . "`
             WHERE `ilosc` IS NOT NULL
             AND TRIM(`ilosc`) <> ''"
        );

        if ($rowsWithStock > 0) {
            return [
                'status' => true,
                'msg' => 'Stany potwierdzone na podstawie kolumny ilosc w tabeli hurtowni (rekordy: ' . $rowsWithStock . ').',
            ];
        }

        return [
            'status' => false,
            'msg' => 'Brak wartości w kolumnie ilosc (stany nie zostały jeszcze zaimportowane).',
        ];
    }


    private function checkDimensionsStatusFromRawTable($wholesaler)
    {
        if (!Validate::isLoadedObject($wholesaler) || empty($wholesaler->raw_table_name)) {
            return null;
        }

        $db = Db::getInstance();
        $rawTable = (string)$wholesaler->raw_table_name;
        $fullTableName = _DB_PREFIX_ . pSQL($rawTable);
        $fullTableNameSql = bqSQL($fullTableName);

        $tableExists = (bool)$db->getValue(
            "SELECT 1 FROM INFORMATION_SCHEMA.TABLES
             WHERE TABLE_SCHEMA = DATABASE()
             AND TABLE_NAME = '" . pSQL($fullTableName) . "'"
        );

        if (!$tableExists) {
            return [
                'status' => false,
                'msg' => 'Brak tabeli danych hurtowni.',
            ];
        }

        $candidateColumns = ['glebokosc', 'szerokosc', 'wysokosc'];
        $quotedColumns = array_map('pSQL', $candidateColumns);
        $columnsSql = "'" . implode("','", $quotedColumns) . "'";

        $rows = $db->executeS(
            "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
             AND TABLE_NAME = '" . pSQL($fullTableName) . "'
             AND COLUMN_NAME IN (" . $columnsSql . ")"
        );

        $existingColumns = [];
        if (is_array($rows)) {
            foreach ($rows as $row) {
                if (!empty($row['COLUMN_NAME'])) {
                    $existingColumns[] = $row['COLUMN_NAME'];
                }
            }
        }

        if (empty($existingColumns)) {
            return [
                'status' => false,
                'msg' => 'Brak kolumn wymiarów (glebokosc/szerokosc/wysokosc) w tabeli danych.',
            ];
        }

        $conditions = [];
        foreach ($existingColumns as $column) {
            $columnSql = bqSQL($column);
            $conditions[] = "`" . $columnSql . "` IS NOT NULL AND `" . $columnSql . "` > 0";
        }

        $rowsWithDimensions = (int)$db->getValue(
            "SELECT COUNT(*) FROM `" . $fullTableNameSql . "`
             WHERE (" . implode(' OR ', $conditions) . ")"
        );

        if ($rowsWithDimensions > 0) {
            return [
                'status' => true,
                'msg' => 'Wymiary potwierdzone na podstawie danych > 0 (' . implode(', ', $existingColumns) . '), rekordy: ' . $rowsWithDimensions . '.',
            ];
        }

        return [
            'status' => false,
            'msg' => 'Kolumny wymiarów istnieją, ale brak wartości > 0 (same 0 lub brak danych).',
        ];
    }

    public function ajaxProcessClearHubCache()
    {
        $idWholesaler = (int)Tools::getValue('id_wholesaler');
        if ($idWholesaler <= 0) {
            die(json_encode(['status' => 'error', 'msg' => 'Brak ID hurtowni.']));
        }

        $deletedRows = Db::getInstance()->delete('azada_wholesaler_pro_cache', 'id_wholesaler='.(int)$idWholesaler);

        $debugFiles = [
            _PS_MODULE_DIR_ . 'azada_wholesaler_pro/downloads/debug.html',
            _PS_MODULE_DIR_ . 'azada_wholesaler_pro/downloads/debug_ekowital.html',
        ];

        $removedFiles = 0;
        foreach ($debugFiles as $debugFile) {
            if (file_exists($debugFile) && @unlink($debugFile)) {
                $removedFiles++;
            }
        }

        die(json_encode([
            'status' => 'success',
            'msg' => 'Wyczyszczono cache hurtowni. Rekordy cache: ' . ($deletedRows ? 'usunięte' : 'brak wpisów') . ', pliki debug: ' . (int)$removedFiles . '.',
        ]));
    }

    public function ajaxProcessForceHubSync()
    {
        @ini_set('max_execution_time', 1200);
        @ini_set('memory_limit', '512M');

        $idWholesaler = (int)Tools::getValue('id_wholesaler');
        if ($idWholesaler <= 0) {
            die(json_encode(['status' => 'error', 'msg' => 'Brak ID hurtowni.']));
        }

        $engine = new AzadaImportEngine();
        $result = $engine->runFullImport($idWholesaler);

        if (!is_array($result)) {
            $result = ['status' => 'error', 'msg' => 'Nieoczekiwana odpowiedź silnika importu.'];
        }

        die(json_encode($result));
    }

    private function getWholesalerProductIdsByRawTable($idWholesaler)
    {
        $idWholesaler = (int)$idWholesaler;
        if ($idWholesaler <= 0) {
            return [];
        }

        $row = Db::getInstance()->getRow(
            'SELECT raw_table_name FROM `'._DB_PREFIX_.'azada_wholesaler_pro_integration` WHERE id_wholesaler='.(int)$idWholesaler
        );

        if (!is_array($row) || empty($row['raw_table_name'])) {
            return [];
        }

        $rawTable = _DB_PREFIX_ . pSQL((string)$row['raw_table_name']);
        $tableExists = (bool)Db::getInstance()->getValue(
            "SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME='".pSQL($rawTable)."'"
        );

        if (!$tableExists) {
            return [];
        }

        $ids = Db::getInstance()->executeS(
            'SELECT p.id_product FROM `'._DB_PREFIX_.'product` p '
            .'INNER JOIN `'.bqSQL($rawTable).'` r ON r.produkt_id = p.reference '
            .'GROUP BY p.id_product'
        );

        if (!is_array($ids)) {
            return [];
        }

        $result = [];
        foreach ($ids as $item) {
            $pid = isset($item['id_product']) ? (int)$item['id_product'] : 0;
            if ($pid > 0) {
                $result[] = $pid;
            }
        }

        return array_values(array_unique($result));
    }

    public function ajaxProcessDisableWholesalerProducts()
    {
        $idWholesaler = (int)Tools::getValue('id_wholesaler');
        if ($idWholesaler <= 0) {
            die(json_encode(['status' => 'error', 'msg' => 'Brak ID hurtowni.']));
        }

        $ids = $this->getWholesalerProductIdsByRawTable($idWholesaler);
        if (empty($ids)) {
            die(json_encode(['status' => 'success', 'msg' => 'Nie znaleziono produktów do wyłączenia.']));
        }

        $chunks = array_chunk($ids, 300);
        foreach ($chunks as $chunk) {
            $in = implode(',', array_map('intval', $chunk));
            if ($in === '') {
                continue;
            }
            Db::getInstance()->execute('UPDATE `'._DB_PREFIX_."product` SET active=0, indexed=0 WHERE id_product IN ($in)");
            Db::getInstance()->execute('UPDATE `'._DB_PREFIX_."product_shop` SET active=0 WHERE id_product IN ($in)");
        }

        die(json_encode([
            'status' => 'success',
            'msg' => 'Wyłączono produkty hurtowni. Liczba produktów: '.count($ids).'.',
        ]));
    }

    public function ajaxProcessDeleteWholesalerProducts()
    {
        @ini_set('max_execution_time', 1200);
        @ini_set('memory_limit', '512M');

        $idWholesaler = (int)Tools::getValue('id_wholesaler');
        if ($idWholesaler <= 0) {
            die(json_encode(['status' => 'error', 'msg' => 'Brak ID hurtowni.']));
        }

        $ids = $this->getWholesalerProductIdsByRawTable($idWholesaler);
        if (empty($ids)) {
            die(json_encode(['status' => 'success', 'msg' => 'Nie znaleziono produktów do usunięcia.']));
        }

        $deleted = 0;
        $failed = 0;
        foreach ($ids as $idProduct) {
            $product = new Product((int)$idProduct);
            if (!Validate::isLoadedObject($product)) {
                $failed++;
                continue;
            }
            if ($product->delete()) {
                $deleted++;
            } else {
                $failed++;
            }
        }

        die(json_encode([
            'status' => 'success',
            'msg' => 'Usuwanie zakończone. Usunięte: '.$deleted.', błędy: '.$failed.'.',
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

function runImport(event, btn, url) {
                if(event) {
                    event.preventDefault();
                    event.stopPropagation();
                }
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
  if (data.status == 'success') {
                                    Swal.fire('Sukces!', data.msg, 'success').then(() => {
                                        location.reload();
                                    });
                                }
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
        
        $hubCards = $this->getHubCardsData();

        $this->context->smarty->assign([
            'total_wholesalers' => (int)$total,
            'active_wholesalers' => (int)$active,
            'add_url' => self::$currentIndex . '&add' . $this->table . '&token=' . $this->token,
            'azada_hub_cards' => $hubCards,
            'azada_hub_post_url' => self::$currentIndex . '&token=' . $this->token,
            'azada_hub_clear_cache_url' => self::$currentIndex . '&action=clearHubCache&ajax=1&token=' . $this->token,
            'azada_hub_force_sync_url' => self::$currentIndex . '&action=forceHubSync&ajax=1&token=' . $this->token,
            'azada_hub_disable_products_url' => self::$currentIndex . '&action=disableWholesalerProducts&ajax=1&token=' . $this->token,
            'azada_hub_delete_products_url' => self::$currentIndex . '&action=deleteWholesalerProducts&ajax=1&token=' . $this->token,
        ]);

        $cssPath = _PS_MODULE_DIR_ . $this->module->name . '/views/css/wholesaler_hub.css';
        $jsPath = _PS_MODULE_DIR_ . $this->module->name . '/views/js/wholesaler_hub.js';

        $cssVersion = file_exists($cssPath) ? (string)filemtime($cssPath) : '1';
        $jsVersion = file_exists($jsPath) ? (string)filemtime($jsPath) : '1';

        $this->addCSS($this->module->getPathUri() . 'views/css/wholesaler_hub.css?v=' . $cssVersion);
        $this->addJS($this->module->getPathUri() . 'views/js/wholesaler_hub.js?v=' . $jsVersion);

        $header = $this->context->smarty->fetch(_PS_MODULE_DIR_ . $this->module->name . '/views/templates/admin/wholesaler_header.tpl');
        $hubPanel = $this->context->smarty->fetch(_PS_MODULE_DIR_ . $this->module->name . '/views/templates/admin/wholesaler_hub/hub.tpl');

        return $js . $header . parent::renderList() . $hubPanel;
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
        $messages = [];

        foreach ($items as $key => $conf) {
            $status = isset($details[$key]['status']) && $details[$key]['status'];
            $msg = isset($details[$key]['msg']) ? trim((string)$details[$key]['msg']) : '';

            if ($status) {
                $icon = '<i class="icon-check-circle" style="color:#27ae60; font-size:15px; vertical-align:-2px; margin-left:4px;"></i>';
                $style = 'background-color:#fff; border:1px solid #e5e5e5; color:#444;';
            } else {
                $icon = '<i class="icon-times-circle" style="color:#c0392b; font-size:15px; vertical-align:-2px; margin-left:4px;"></i>';
                $style = 'background-color:#fff5f5; border:1px solid #f8d7da; color:#721c24; opacity:0.6;';

                if ($msg !== '') {
                    $messages[] = $conf['label'] . ': ' . $msg;
                }
            }

            if ($key == 'b2b') $style .= ' border-left: 3px solid #3498db;';

            $css = 'display:inline-block; padding:4px 10px; margin-right:6px; border-radius:50px; font-weight:bold; font-size:11px; text-transform:uppercase; ' . $style;

            $title = $msg !== '' ? ' title="' . htmlspecialchars($msg, ENT_QUOTES, 'UTF-8') . '"' : '';

            $html .= '<span style="'.$css.'"'.$title.'>'
                . $conf['label'] . ' ' . $icon .
            '</span>';
        }

        if (!empty($messages)) {
            $errorTxt = htmlspecialchars(implode(' | ', $messages), ENT_QUOTES, 'UTF-8');
            $html .= '<div style="margin-top:6px; color:#b94a48; font-size:11px; font-weight:bold;">'
                . '<i class="icon-warning-sign"></i> ' . $errorTxt .
            '</div>';
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
    
    private function getSubmittedApiKey()
    {
        $apiKey = trim((string)Tools::getValue('api_key'));
        if ($apiKey !== '') {
            return $apiKey;
        }

        // kompatybilność ze starszym formularzem
        return trim((string)Tools::getValue('api_key_input'));
    }

    private function applyPresetData($preset, $apiKey)
    {
        if ($preset === 'bioplanet') {
            $_POST['name'] = 'Bio Planet';
            $_POST['raw_table_name'] = 'azada_raw_bioplanet';
            $links = AzadaBioPlanet::generateLinks($apiKey);
            $settings = AzadaBioPlanet::getSettings();
        } elseif ($preset === 'ekowital') {
            $_POST['name'] = 'EkoWital';
            $_POST['raw_table_name'] = 'azada_raw_ekowital';
            $links = AzadaEkoWital::generateLinks($apiKey);
            $settings = AzadaEkoWital::getSettings();
        } elseif ($preset === 'naturamed') {
            $_POST['name'] = 'NaturaMed';
            $_POST['raw_table_name'] = 'azada_raw_naturamed';
            $links = AzadaNaturaMed::generateLinks($apiKey);
            $settings = AzadaNaturaMed::getSettings();
        } elseif ($preset === 'abro') {
            $_POST['name'] = 'ABRO';
            $_POST['raw_table_name'] = 'azada_raw_abro';
            $links = AzadaAbro::generateLinks($apiKey);
            $settings = AzadaAbro::getSettings();
        } else {
            return false;
        }

        $_POST['file_url'] = $links['products'];
        $_POST['file_format'] = $settings['file_format'];
        $_POST['delimiter'] = $settings['delimiter'];
        $_POST['skip_header'] = $settings['skip_header'];
        $_POST['api_key'] = trim($apiKey);

        return true;
    }

    public function postProcess() {
        $this->postProcessHubCards();
        $this->postProcessHubSettings();

        // DODAWANIE: preset + API
        if (Tools::isSubmit('submitAdd' . $this->table)) {
            $preset = Tools::getValue('preset_integration');
            $apiKey = $this->getSubmittedApiKey();

            if (in_array($preset, ['bioplanet', 'ekowital', 'naturamed', 'abro'], true)) {
                if (empty($apiKey)) {
                    $this->errors[] = $this->l('Podaj Klucz API.');
                    return;
                }
                $this->applyPresetData($preset, $apiKey);
            }
        }

        // EDYCJA: jeśli już istniejąca integracja presetowa ma API, odświeżamy URL i ustawienia
        if (Tools::isSubmit('submitUpdate' . $this->table)) {
            $apiKey = $this->getSubmittedApiKey();
            $rawTableName = (string)Tools::getValue('raw_table_name');

            $preset = '';
            if ($rawTableName === 'azada_raw_bioplanet') {
                $preset = 'bioplanet';
            } elseif ($rawTableName === 'azada_raw_ekowital') {
                $preset = 'ekowital';
            } elseif ($rawTableName === 'azada_raw_naturamed') {
                $preset = 'naturamed';
            } elseif ($rawTableName === 'azada_raw_abro') {
                $preset = 'abro';
            }

            if ($preset !== '' && $apiKey !== '') {
                $this->applyPresetData($preset, $apiKey);
            } elseif ($apiKey !== '') {
                $_POST['api_key'] = $apiKey;
            }
        }

        parent::postProcess();

        if (Tools::isSubmit('submitAdd' . $this->table) || Tools::isSubmit('submitUpdate' . $this->table)) {
            $rawTableName = Tools::getValue('raw_table_name');
            if ($rawTableName === 'azada_raw_bioplanet' || $rawTableName === 'azada_raw_ekowital' || $rawTableName === 'azada_raw_naturamed' || $rawTableName === 'azada_raw_abro') {
                AzadaRawSchema::createTable($rawTableName);
            }
        }
    }

    public function renderForm() {
        $js_logic = "<script>$(document).ready(function() { function toggleFields() { var val = $('#preset_integration').val(); if (val == 'bioplanet' || val == 'ekowital' || val == 'naturamed' || val == 'abro') { $('.custom-field').closest('.form-group').hide(); $('.bioplanet-field').closest('.form-group').show(); $('input[name=\"name\"]').closest('.form-group').hide(); $('input[name=\"raw_table_name\"]').closest('.form-group').hide(); } else { $('.custom-field').closest('.form-group').show(); $('.bioplanet-field').closest('.form-group').hide(); $('input[name=\"name\"]').closest('.form-group').show(); $('input[name=\"raw_table_name\"]').closest('.form-group').show(); } } $('#preset_integration').change(toggleFields); toggleFields(); });</script>";

        $this->fields_form = [
            'legend' => ['title' => $this->l('Nowa Integracja'), 'icon' => 'icon-cloud-upload'],
            'input' => [
                ['type' => 'html', 'name' => 'html_js', 'html_content' => $js_logic],
                ['type' => 'select', 'label' => 'Szablon', 'name' => 'preset_integration', 'id' => 'preset_integration', 'required' => true, 'options' => ['query' => [['id' => 'custom', 'name' => '-- Własna / Inna --'], ['id' => 'bioplanet', 'name' => 'BIO PLANET (Automatyczna)'], ['id' => 'ekowital', 'name' => 'EKOWITAL (Automatyczna)'], ['id' => 'naturamed', 'name' => 'NATURAMED (Automatyczna)'], ['id' => 'abro', 'name' => 'ABRO (Automatyczna XML)']], 'id' => 'id', 'name' => 'name']],
                
                ['type' => 'text', 'label' => 'Nazwa', 'name' => 'name', 'required' => true],
                ['type' => 'text', 'label' => 'Tabela Produktów (SQL)', 'name' => 'raw_table_name', 'class' => 'custom-field', 'desc' => 'Nazwa tabeli w bazie (np. azada_raw_bioplanet), w której znajdują się produkty tej hurtowni.'],

                ['type' => 'text', 'label' => 'Klucz API', 'name' => 'api_key', 'desc' => 'Klucz API hurtowni (widoczny i edytowalny także przy edycji).'],
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
