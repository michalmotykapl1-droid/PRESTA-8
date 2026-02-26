<?php

require_once(dirname(__FILE__) . '/../../classes/settings/AzadaSettingsTechnical.php');
require_once(dirname(__FILE__) . '/../../classes/settings/AzadaSettingsBasic.php');
require_once(dirname(__FILE__) . '/../../classes/settings/AzadaSettingsQty.php');
require_once(dirname(__FILE__) . '/../../classes/settings/AzadaSettingsPrices.php');
require_once(dirname(__FILE__) . '/../../classes/settings/AzadaSettingsImages.php');
require_once(dirname(__FILE__) . '/../../classes/settings/AzadaSettingsAdvanced.php');
require_once(dirname(__FILE__) . '/../../classes/settings/AzadaSettingsB2B.php');
require_once(dirname(__FILE__) . '/../../classes/settings/AzadaSettingsSystem.php');
require_once(dirname(__FILE__) . '/../../classes/services/AzadaConfig.php');


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
        $helper->allow_employee_form_lang = AzadaConfig::getInt('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);
        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitAzadaGlobalConfig';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminAzadaSettings', false);
        $helper->token = Tools::getAdminTokenLite('AdminAzadaSettings');

        $helper->fields_value = $this->getConfigFormValues();
        $baseUrl = Tools::getShopDomainSsl(true) . __PS_BASE_URI__ . 'modules/azada_wholesaler_pro/';

        $forms = [
            AzadaSettingsTechnical::getForm($this->module, $baseUrl),
            AzadaSettingsB2B::getForm(),
            AzadaSettingsBasic::getForm(),
            AzadaSettingsQty::getForm(),
            AzadaSettingsPrices::getForm(),
            AzadaSettingsImages::getForm(),
            AzadaSettingsAdvanced::getForm(),
            // Na samym dole – osobny panel
            AzadaSettingsSystem::getForm(),
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
            $autoFixMessages = [];

            // --- Auto-fix: ZAMÓWIENIA (B2B) ---
            $b2bDaysRange = null;
            $b2bDeleteDays = null;

            $b2bDaysRangeRaw = Tools::getValue('AZADA_B2B_DAYS_RANGE', null);
            $b2bDeleteDaysRaw = Tools::getValue('AZADA_B2B_DELETE_DAYS', null);

            if ($b2bDaysRangeRaw !== null || $b2bDeleteDaysRaw !== null) {
                $b2bDaysRange = (int)$b2bDaysRangeRaw;
                if ($b2bDaysRange < 1) {
                    $b2bDaysRange = 1;
                }

                $b2bDeleteDays = (int)$b2bDeleteDaysRaw;
                if ($b2bDeleteDays < 0) {
                    $b2bDeleteDays = 0;
                }

                if ($b2bDeleteDays > 0 && $b2bDeleteDays < ($b2bDaysRange + 1)) {
                    $b2bDeleteDays = $b2bDaysRange + 1;
                    $autoFixMessages[] = 'Pliki zamówień: automatycznie ustawiono „Usuwaj stare pliki zamówień po” na ' . (int)$b2bDeleteDays . ' (zakres ' . (int)$b2bDaysRange . ' + 1 dzień).';
                }
            }

            // --- Auto-fix: FAKTURY (FV) ---
            $fvDaysRange = null;
            $fvDeleteDays = null;

            $fvDaysRangeRaw = Tools::getValue('AZADA_FV_DAYS_RANGE', null);
            $fvDeleteDaysRaw = Tools::getValue('AZADA_FV_DELETE_DAYS', null);

            if ($fvDaysRangeRaw !== null || $fvDeleteDaysRaw !== null) {
                $fvDaysRange = (int)$fvDaysRangeRaw;
                if ($fvDaysRange < 1) {
                    $fvDaysRange = 1;
                }

                $fvDeleteDays = (int)$fvDeleteDaysRaw;
                if ($fvDeleteDays < 0) {
                    $fvDeleteDays = 0;
                }

                if ($fvDeleteDays > 0 && $fvDeleteDays < ($fvDaysRange + 1)) {
                    $fvDeleteDays = $fvDaysRange + 1;
                    $autoFixMessages[] = 'Pliki faktur: automatycznie ustawiono „Usuwaj stare pliki faktur po” na ' . (int)$fvDeleteDays . ' (zakres ' . (int)$fvDaysRange . ' + 1 dzień).';
                }
            }

            $keys = [
                'AZADA_CRON_KEY', 'AZADA_USE_SECURE_TOKEN',

                // B2B ZAMÓWIENIA
                'AZADA_B2B_DAYS_RANGE', 'AZADA_B2B_AUTO_FMT_ACTIVE', 'AZADA_B2B_PREF_FORMAT',
                'AZADA_B2B_AUTO_DOWNLOAD', 'AZADA_B2B_DELETE_DAYS',
                'AZADA_B2B_FETCH_STRATEGY',

                // B2B FAKTURY
                'AZADA_FV_DAYS_RANGE', 'AZADA_FV_AUTO_DOWNLOAD',
                'AZADA_FV_PREF_FORMAT', 'AZADA_FV_DELETE_DAYS',

                // SYSTEMOWE
                'AZADA_LOGS_RETENTION',

                // POWIĄZANIA Z ISTNIEJĄCYMI PRODUKTAMI
                'AZADA_LINK_EXISTING_PRODUCTS',

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
                $value = Tools::getValue($key, null);

                // Jeśli pole nie było w POST/GET (inna sekcja konfigu), nie nadpisujemy konfiguracji.
                if ($value === null) {
                    continue;
                }

                if ($key === 'AZADA_B2B_DAYS_RANGE' && $b2bDaysRange !== null) {
                    Configuration::updateValue($key, (int)$b2bDaysRange);
                    continue;
                }

                if ($key === 'AZADA_B2B_DELETE_DAYS' && $b2bDeleteDays !== null) {
                    Configuration::updateValue($key, (int)$b2bDeleteDays);
                    continue;
                }

                if ($key === 'AZADA_FV_DAYS_RANGE' && $fvDaysRange !== null) {
                    Configuration::updateValue($key, (int)$fvDaysRange);
                    continue;
                }

                if ($key === 'AZADA_FV_DELETE_DAYS' && $fvDeleteDays !== null) {
                    Configuration::updateValue($key, (int)$fvDeleteDays);
                    continue;
                }

                Configuration::updateValue($key, $value);
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
        // Upewniamy się, że konfiguracja modułu jest zainicjalizowana (szczególnie po świeżej instalacji).
        AzadaConfig::ensureDefaults();

        $defaults = AzadaConfig::getDefaults();
        foreach ($defaults as $key => $default) {
            $values[$key] = AzadaConfig::get($key, $default);
        }

        // miejsca na linki cron (w panelu technicznym)
        $values['VIEW_CRON_IMPORT'] = '';
        $values['VIEW_CRON_UPDATE'] = '';
        $values['VIEW_CRON_IMPORT_FULL'] = '';
        $values['VIEW_CRON_IMPORT_LIGHT'] = '';
        $values['VIEW_CRON_UPDATE_QTY'] = '';
        $values['VIEW_CRON_UPDATE_QTY_ABRO'] = '';
        $values['VIEW_CRON_UPDATE_PRICE'] = '';
        $values['VIEW_CRON_CREATE_PRODUCTS'] = '';
        $values['VIEW_CRON_REBUILD_INDEX'] = '';

        return $values;
    }
}