<?php
/**
 * ALLEGRO PRO - Korespondencja (APP na froncie)
 *
 * Ta strona ma wyglądać "jak aplikacja" (full screen), dlatego wyłączamy header/footer.
 * Dostęp jest chroniony krótkotrwałą sygnaturą (bridge) generowaną w BO.
 */

class AllegroproCorrespondenceappModuleFrontController extends ModuleFrontController
{
    /** @var bool */
    public $ssl = true;

    public function initContent()
    {
        parent::initContent();

        // Aplikacja ma być "czysta" (bez szablonu sklepu)
        $this->display_header = false;
        $this->display_footer = false;

        $eid = (int)Tools::getValue('eid');
        $ts = (int)Tools::getValue('ts');
        $ttl = (int)Tools::getValue('ttl');
        $sig = (string)Tools::getValue('sig');

        if (!isset($this->module) || !method_exists($this->module, 'validateBoBridgeParams')) {
            $this->deny('Missing validateBoBridgeParams');
            return;
        }

        if (!$this->module->validateBoBridgeParams($eid, $ts, $ttl, $sig)) {
            $this->deny('Invalid bridge signature');
            return;
        }

        // DB schema (tabele korespondencji) - bez reinstalacji modułu
        if (method_exists($this->module, 'ensureDbSchema')) {
            $this->module->ensureDbSchema();
        }

        // Dodatkowa walidacja: pracownik musi istnieć i być aktywny
        $employee = new Employee($eid);
        if (!Validate::isLoadedObject($employee) || !(int)$employee->active) {
            $this->deny('Employee not active');
            return;
        }

        $apiUrl = '';
        try {
            $apiUrl = $this->context->link->getModuleLink($this->module->name, 'correspondenceapi', [], true);
        } catch (\Throwable $e) {
            $apiUrl = '';
        }

        // Cache-buster dla assetów (CSS/JS) – żeby po podmianie plików nie trzeba było walczyć z cache przeglądarki
        $assetsB = (string)time();
        try {
            $base = _PS_MODULE_DIR_ . $this->module->name . '/';
            $css = $base . 'views/css/correspondenceapp.css';
            $js = $base . 'views/js/front/correspondenceapp.js';
            $t1 = is_file($css) ? (int)@filemtime($css) : 0;
            $t2 = is_file($js) ? (int)@filemtime($js) : 0;
            $assetsB = (string)max($t1, $t2, time());
        } catch (\Throwable $e) {
            // fallback: time()
        }

        $this->context->smarty->assign([
            'ap_app_title' => 'Korespondencja',
            'ap_employee_name' => trim($employee->firstname . ' ' . $employee->lastname),
            'ap_module_path' => $this->module->getPathUri(),
            'ap_module_version' => (string)$this->module->version,
            'ap_api_url' => (string)$apiUrl,
            'ap_assets_b' => $assetsB,
            'ap_bridge' => [
                'eid' => $eid,
                'ts' => $ts,
                'ttl' => $ttl,
                'sig' => $sig,
            ],
        ]);

        $this->setTemplate('module:allegropro/views/templates/front/correspondenceapp.tpl');
    }

    private function deny(string $reason = ''): void
    {
        header('HTTP/1.1 403 Forbidden');
        header('Content-Type: text/plain; charset=utf-8');
        echo "Forbidden";
        if ($reason) {
            echo "\n" . $reason;
        }
        exit;
    }
}
