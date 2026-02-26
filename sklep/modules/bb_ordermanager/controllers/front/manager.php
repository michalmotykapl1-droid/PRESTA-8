<?php
/**
 * Manager Controller - Standalone rendering (jak Packing)
 *
 * Powód:
 * - manager.tpl zawiera pełny dokument HTML (<!DOCTYPE html> ...), więc nie może być wstrzykiwany
 *   w layout FO PrestaShop (HTML w HTML, konflikty CSS/JS).
 *
 * Rozwiązanie:
 * - renderujemy szablon bez header/footer theme i bez layoutu Presty (echo + exit).
 */

class Bb_ordermanagerManagerModuleFrontController extends ModuleFrontController
{
    public $auth = false;

    // Standalone (bez layoutu motywu)
    public $display_header = false;
    public $display_footer = false;
    public $content_only = true;

    public function setMedia()
    {
        // Zostawiamy dla kompatybilności, ale manager.tpl i tak ładuje swoje CDN-y.
        parent::setMedia();

        $this->registerJavascript(
            'vue3-cdn',
            'https://unpkg.com/vue@3/dist/vue.global.js',
            ['server' => 'remote', 'position' => 'head', 'priority' => 1]
        );

        $this->registerStylesheet(
            'fontawesome-cdn',
            'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css',
            ['server' => 'remote', 'media' => 'all']
        );
    }

    public function initContent()
    {
        // Nie ustawiamy setTemplate(), bo to by wchodziło w layout motywu.
        parent::initContent();

        // Panel pracowniczy: musi zawsze ładować świeżą konfigurację (menu Kanban)
        // z bazy. Przy włączonym cache Smarty / reverse-proxy potrafiła wracać
        // stara wersja strony -> zmiany z zaplecza nie były widoczne na froncie.
        // Wymuszamy więc brak cache po stronie Smarty + nagłówki HTTP.
        try {
            if (isset($this->context) && isset($this->context->smarty)) {
                // Smarty 3
                $this->context->smarty->caching = defined('Smarty::CACHING_OFF') ? Smarty::CACHING_OFF : 0;
                $this->context->smarty->cache_lifetime = 0;
            }
        } catch (Exception $e) {
            // ignore
        }

        if (!headers_sent()) {
            header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
            header('Cache-Control: post-check=0, pre-check=0', false);
            header('Pragma: no-cache');
            header('Expires: 0');
        }

        require_once _PS_MODULE_DIR_ . 'bb_ordermanager/classes/BbOrderManagerFolders.php';

        $geowidgetToken = $this->getInpostGeowidgetToken();

        // Dynamiczne menu (kolejność/widoczność z konfiguracji)
        $bbomMenu = BbOrderManagerFolders::buildMenu();
        // JSON w <script> - używamy JSON_HEX_* żeby nie dało się „wyjść” ze skryptu przez etykiety folderów
        $bbomMenuJson = json_encode(
            $bbomMenu,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
        );
        if ($bbomMenuJson === false) {
            $bbomMenuJson = '[]';
        }

        $this->context->smarty->assign([
            'module_dir' => _MODULE_DIR_ . $this->module->name,
            'api_url' => $this->context->link->getModuleLink('bb_ordermanager', 'api'),
            'auth_url' => $this->context->link->getModuleLink('bb_ordermanager', 'auth'),

            // LINK DO KONTROLERA 'api' W MODULE DXFAKTUROWNIA
            'fv_api_url' => $this->context->link->getModuleLink('dxfakturownia', 'api'),

            'is_employee' => (bool) $this->context->employee,
            'geowidget_token' => $geowidgetToken,

            'bbom_menu_json' => $bbomMenuJson,
        ]);

        echo $this->context->smarty->fetch(_PS_MODULE_DIR_ . 'bb_ordermanager/views/templates/front/manager.tpl');
        exit;
    }

    private function getInpostGeowidgetToken(): string
    {
        try {
            $sql = 'SELECT `value`
                    FROM `' . _DB_PREFIX_ . 'configuration`
                    WHERE `name` LIKE "%GEOWIDGET%" AND `name` LIKE "%TOKEN%"
                    ORDER BY `id_configuration` DESC';
            $val = (string) Db::getInstance()->getValue($sql);
            return trim($val);
        } catch (Exception $e) {
            return '';
        }
    }
}
