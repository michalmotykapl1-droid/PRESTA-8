<?php

require_once(dirname(__FILE__) . '/../../classes/settings/AzadaSettingsTechnical.php');
require_once(dirname(__FILE__) . '/../../classes/settings/AzadaSettingsBasic.php');
require_once(dirname(__FILE__) . '/../../classes/settings/AzadaSettingsQty.php');
require_once(dirname(__FILE__) . '/../../classes/settings/AzadaSettingsPrices.php');
require_once(dirname(__FILE__) . '/../../classes/settings/AzadaSettingsImages.php');
require_once(dirname(__FILE__) . '/../../classes/settings/AzadaSettingsAdvanced.php');
require_once(dirname(__FILE__) . '/../../classes/settings/AzadaSettingsB2B.php');

class AdminAzadaSettingsController extends ModuleAdminController
{
    public function __construct()
    {
        $this->bootstrap = true;
        parent::__construct();
        $this->fields_options = []; 
    }

    public function initContent()
    {
        parent::initContent();
        $this->context->smarty->assign('content', $this->renderForm());
    }

    public function renderForm()
    {
        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->module = $this->module;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);
        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitAzadaGlobalConfig';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminAzadaSettings', false);
        $helper->token = Tools::getAdminTokenLite('AdminAzadaSettings');

        $helper->fields_value = $this->getConfigFormValues();
        $baseUrl = Tools::getShopDomainSsl(true) . __PS_BASE_URI__ . 'modules/azada_wholesaler_pro/cron.php';

        $forms = [
            AzadaSettingsTechnical::getForm($this->module, $baseUrl),
            AzadaSettingsB2B::getForm(),
            AzadaSettingsBasic::getForm(),
            AzadaSettingsQty::getForm(),
            AzadaSettingsPrices::getForm(),
            AzadaSettingsImages::getForm(),
            AzadaSettingsAdvanced::getForm()
        ];

        $html = $helper->generateForm($forms);
        $html .= $this->getRetentionAutoFixScript();

        return $html;
    }

    private function getRetentionAutoFixScript()
    {
        return <<<'HTML'
<script>
            (function(){
                function toInt(v){
                    var n = parseInt(v, 10);
                    return isNaN(n) ? 0 : n;
                }

                function normalizePair(rangeName, deleteName, label){
                    var rangeInput = document.querySelector("input[name='" + rangeName + "']");
                    var deleteInput = document.querySelector("input[name='" + deleteName + "']");
                    if (!rangeInput || !deleteInput) {
                        return null;
                    }

                    var rangeVal = toInt(rangeInput.value);
                    if (rangeVal < 1) {
                        rangeVal = 1;
                    }

                    var deleteVal = toInt(deleteInput.value);
                    if (deleteVal < 0) {
                        deleteVal = 0;
                    }

                    var minDelete = rangeVal + 1;
                    if (deleteVal > 0 && deleteVal < minDelete) {
                        deleteInput.value = String(minDelete);
                        return label + ": automatycznie ustawiono " + minDelete + " (zakres " + rangeVal + " + 1 dzień).";
                    }

                    return null;
                }

                function bindOnSave(){
                    var btn = document.querySelector("button[name='submitAzadaGlobalConfig']");
                    if (!btn) {
                        return;
                    }

                    btn.addEventListener("click", function(){
                        var fixes = [];
                        var ordersFix = normalizePair("AZADA_B2B_DAYS_RANGE", "AZADA_B2B_DELETE_DAYS", "Pliki zamówień");
                        if (ordersFix) {
                            fixes.push(ordersFix);
                        }

                        var invoicesFix = normalizePair("AZADA_FV_DAYS_RANGE", "AZADA_FV_DELETE_DAYS", "Pliki faktur");
                        if (invoicesFix) {
                            fixes.push(invoicesFix);
                        }

                        if (fixes.length > 0) {
                            alert("Wykryto zbyt niską wartość dni kasowania.\n\n" + fixes.join("\n") + "\n\nZmiany zostały poprawione przed zapisem.");
                        }
                    });
                }

                if (document.readyState === "loading") {
                    document.addEventListener("DOMContentLoaded", bindOnSave);
                } else {
                    bindOnSave();
                }
            })();
        </script>
HTML;
    }

    public function postProcess()
    {
        if (Tools::isSubmit('submitAzadaGlobalConfig')) {
            $b2bDaysRange = (int)Tools::getValue('AZADA_B2B_DAYS_RANGE');
            if ($b2bDaysRange < 1) {
                $b2bDaysRange = 1;
            }

            $b2bDeleteDaysRaw = (int)Tools::getValue('AZADA_B2B_DELETE_DAYS');
            $b2bDeleteDays = $b2bDeleteDaysRaw < 0 ? 0 : $b2bDeleteDaysRaw;

            $fvDaysRange = (int)Tools::getValue('AZADA_FV_DAYS_RANGE');
            if ($fvDaysRange < 1) {
                $fvDaysRange = 1;
            }

            $fvDeleteDaysRaw = (int)Tools::getValue('AZADA_FV_DELETE_DAYS');
            $fvDeleteDays = $fvDeleteDaysRaw < 0 ? 0 : $fvDeleteDaysRaw;

            $autoFixMessages = [];
            if ($b2bDeleteDays > 0 && $b2bDeleteDays < ($b2bDaysRange + 1)) {
                $b2bDeleteDays = $b2bDaysRange + 1;
                $autoFixMessages[] = 'Pliki zamówień: automatycznie ustawiono „Usuwaj stare pliki zamówień po” na ' . (int)$b2bDeleteDays . ' (zakres ' . (int)$b2bDaysRange . ' + 1 dzień).';
            }

            if ($fvDeleteDays > 0 && $fvDeleteDays < ($fvDaysRange + 1)) {
                $fvDeleteDays = $fvDaysRange + 1;
                $autoFixMessages[] = 'Pliki faktur: automatycznie ustawiono „Usuwaj stare pliki faktur po” na ' . (int)$fvDeleteDays . ' (zakres ' . (int)$fvDaysRange . ' + 1 dzień).';
            }

            $keys = [
                'AZADA_CRON_KEY', 'AZADA_USE_SECURE_TOKEN',
                
                // B2B ZAMÓWIENIA
                'AZADA_B2B_DAYS_RANGE', 'AZADA_B2B_AUTO_FMT_ACTIVE', 'AZADA_B2B_PREF_FORMAT', 
                'AZADA_B2B_AUTO_DOWNLOAD', 'AZADA_B2B_DELETE_DAYS',
                'AZADA_B2B_FETCH_STRATEGY',
                
                // B2B FAKTURY (NOWE - TO NAPRAWIA BŁĄD)
                'AZADA_FV_DAYS_RANGE', 'AZADA_FV_AUTO_DOWNLOAD',
                'AZADA_FV_PREF_FORMAT', 'AZADA_FV_DELETE_DAYS',

                // SYSTEMOWE
                'AZADA_LOGS_RETENTION',

                // RESZTA MODUŁU
                'AZADA_NEW_PROD_ACTIVE', 'AZADA_NEW_PROD_VISIBILITY',
                'AZADA_UPD_NAME', 'AZADA_UPD_DESC_LONG', 'AZADA_UPD_DESC_SHORT',
                'AZADA_UPD_META', 'AZADA_UPD_REFERENCE', 'AZADA_UPD_EAN', 
                'AZADA_UPD_FEATURES', 'AZADA_UPD_MANUFACTURER', 'AZADA_UPD_CATEGORY',
                'AZADA_UPD_ACTIVE',
                'AZADA_UPD_QTY', 'AZADA_UPD_MIN_QTY',
                'AZADA_STOCK_ZERO_ACTION', 'AZADA_STOCK_MISSING_ZERO', 'AZADA_SKIP_NO_QTY',
                'AZADA_UPD_PRICE', 'AZADA_UPD_WHOLESALE_PRICE', 'AZADA_UPD_UNIT_PRICE', 'AZADA_UPD_TAX',
                'AZADA_PRICE_ROUNDING', 'AZADA_SKIP_NO_PRICE',
                'AZADA_UPD_IMAGES', 'AZADA_DELETE_OLD_IMAGES', 'AZADA_SKIP_NO_IMAGE', 'AZADA_SKIP_NO_DESC',
                'AZADA_CLEAR_CACHE', 'AZADA_DISABLE_MISSING_PROD'
            ];

            foreach ($keys as $key) {
                if ($key === 'AZADA_B2B_DELETE_DAYS') {
                    Configuration::updateValue($key, (int)$b2bDeleteDays);
                    continue;
                }

                if ($key === 'AZADA_FV_DELETE_DAYS') {
                    Configuration::updateValue($key, (int)$fvDeleteDays);
                    continue;
                }

                Configuration::updateValue($key, Tools::getValue($key));
            }

            foreach ($autoFixMessages as $msg) {
                $this->confirmations[] = $msg;
            }

            $this->confirmations[] = 'Ustawienia zostały zapisane.';
        }
    }

    protected function getConfigFormValues()
    {
        $values = [];
        $defaults = [
            'AZADA_CRON_KEY' => Tools::passwdGen(16), 'AZADA_USE_SECURE_TOKEN' => 1,
            
            // B2B Orders Defaults
            'AZADA_B2B_DAYS_RANGE' => 7,
            'AZADA_B2B_AUTO_FMT_ACTIVE' => 0,
            'AZADA_B2B_PREF_FORMAT' => 'utf8',
            'AZADA_B2B_AUTO_DOWNLOAD' => 0,
            'AZADA_B2B_DELETE_DAYS' => 7,
            'AZADA_B2B_FETCH_STRATEGY' => 'strict',
            
            // B2B Invoices Defaults (NOWE - WARTOŚCI DOMYŚLNE)
            'AZADA_FV_DAYS_RANGE' => 30, 
            'AZADA_FV_AUTO_DOWNLOAD' => 0,
            'AZADA_FV_PREF_FORMAT' => 'csv',
            'AZADA_FV_DELETE_DAYS' => 365,
            
            'AZADA_LOGS_RETENTION' => 30,

            // Reszta
            'AZADA_NEW_PROD_ACTIVE' => 0, 'AZADA_NEW_PROD_VISIBILITY' => 'both',
            'AZADA_UPD_NAME' => 0, 'AZADA_UPD_DESC_LONG' => 0, 'AZADA_UPD_DESC_SHORT' => 0,
            'AZADA_UPD_META' => 1, 'AZADA_UPD_REFERENCE' => 0, 'AZADA_UPD_EAN' => 1,
            'AZADA_UPD_FEATURES' => 1, 'AZADA_UPD_MANUFACTURER' => 1, 'AZADA_UPD_CATEGORY' => 0,
            'AZADA_UPD_ACTIVE' => 0,
            'AZADA_UPD_QTY' => 1, 'AZADA_UPD_MIN_QTY' => 0,
            'AZADA_STOCK_ZERO_ACTION' => 0, 'AZADA_STOCK_MISSING_ZERO' => 1, 'AZADA_SKIP_NO_QTY' => 0,
            'AZADA_UPD_PRICE' => 1, 'AZADA_UPD_WHOLESALE_PRICE' => 0, 'AZADA_UPD_UNIT_PRICE' => 0, 'AZADA_UPD_TAX' => 0,
            'AZADA_PRICE_ROUNDING' => 0, 'AZADA_SKIP_NO_PRICE' => 1,
            'AZADA_UPD_IMAGES' => 1, 'AZADA_DELETE_OLD_IMAGES' => 0, 
            'AZADA_SKIP_NO_IMAGE' => 0, 'AZADA_SKIP_NO_DESC' => 0,
            'AZADA_CLEAR_CACHE' => 0, 'AZADA_DISABLE_MISSING_PROD' => 1
        ];

        foreach ($defaults as $key => $default) {
            $values[$key] = Configuration::get($key, $default);
            if ($key == 'AZADA_CRON_KEY' && !Configuration::get($key)) {
                Configuration::updateValue($key, $default);
            }
        }
        $values['VIEW_CRON_IMPORT'] = '';
        $values['VIEW_CRON_UPDATE'] = '';

        return $values;
    }
}
