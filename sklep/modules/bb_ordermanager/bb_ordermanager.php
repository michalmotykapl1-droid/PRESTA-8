<?php
/**
 * MANAGER PRO - Main Module File
 * Wersja 2.9.0 - Fix CSS (Flexbox) + Ikona Dashboard + Kolor Furgonetka
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class Bb_OrderManager extends Module
{
    public function __construct()
    {
        $this->name = 'bb_ordermanager';
        $this->tab = 'administration';
        $this->version = '2.9.0';
        $this->author = 'BigBio';
        $this->need_instance = 0;
        $this->bootstrap = true;
        $this->ps_versions_compliancy = ['min' => '1.7', 'max' => _PS_VERSION_];

        parent::__construct();

        $this->displayName = $this->l('MANAGER PRO');
        $this->description = $this->l('Kompleksowy system: Zarządzanie zamówieniami (Kanban) + System Pakowania (Skaner) + Integracje.');
    }

    public function install()
    {
        if (!parent::install() ||
            !$this->registerHook('displayAdminOrderMain') ||
            !$this->registerHook('displayBackOfficeHeader') ||
            !$this->installDb() ||           
            !$this->installDefaultFolders() ||
            !$this->installTabs()
        ) {
            return false;
        }

        // DOMYŚLNA KONFIGURACJA
        Configuration::updateValue('BB_MANAGER_LABEL_FORMAT', 'PDF');
        Configuration::updateValue('BB_MANAGER_LABEL_SIZE', 'A4');
        Configuration::updateValue('BB_MANAGER_PKG_LEN', 10);
        Configuration::updateValue('BB_MANAGER_PKG_WID', 10);
        Configuration::updateValue('BB_MANAGER_PKG_HEI', 10);

        // Foldery / statusy (Kanban): autowykrycie / utworzenie brakujących statusów
        require_once _PS_MODULE_DIR_ . 'bb_ordermanager/classes/BbOrderManagerFolderStates.php';
        BbOrderManagerFolderStates::getMap((int) Configuration::get('PS_LANG_DEFAULT'));

        return true;
    }

    public function uninstall()
    {
        return parent::uninstall() && 
               $this->uninstallDb() &&
               $this->uninstallTabs();
    }

    // --- INSTALACJA STRUKTURY MENU ---

    private function installTabs()
    {
        // Podpinamy pod sekcję "SPRZEDAŻ" (SELL)
        $parentId = (int) Tab::getIdFromClassName('SELL');
        if (!$parentId) $parentId = 2; 

        // 1. Główna zakładka (Nadrzędna) -> Ikona "KWADRACIK" (Dashboard)
        $tab = new Tab();
        $tab->active = 1;
        $tab->class_name = 'AdminManagerProParent';
        $tab->name = array_fill_keys(Language::getIDs(false), 'MANAGER PRO');
        $tab->id_parent = $parentId;
        $tab->module = $this->name;
        $tab->icon = 'dashboard'; // <--- TU JEST TWÓJ KWADRACIK
        $tab->add();
        $idParent = (int)$tab->id;

        // 2. Podmenu: Panel Zarządzania (Przycisk)
        $tab = new Tab();
        $tab->active = 1;
        $tab->class_name = 'AdminManagerProPanel';
        $tab->name = array_fill_keys(Language::getIDs(false), 'Panel Zarządzania');
        $tab->id_parent = $idParent;
        $tab->module = $this->name;
        $tab->icon = 'desktop_windows';
        $tab->add();

        // 3. Podmenu: Konfiguracja
        $tab = new Tab();
        $tab->active = 1;
        $tab->class_name = 'AdminManagerProConfig';
        $tab->name = array_fill_keys(Language::getIDs(false), 'Konfiguracja');
        $tab->id_parent = $idParent;
        $tab->module = $this->name;
        $tab->icon = 'settings';
        $tab->add();

        // 3b. Podmenu: Logi (Audyt)
        $tab = new Tab();
        $tab->active = 1;
        $tab->class_name = 'AdminManagerProLogs';
        $tab->name = array_fill_keys(Language::getIDs(false), 'Logi');
        $tab->id_parent = $idParent;
        $tab->module = $this->name;
        $tab->icon = 'history';
        $tab->add();

        // 4. Podmenu: Pakowanie (Ukryte)
        $tabId = (int) Tab::getIdFromClassName('AdminBbPacking');
        if (!$tabId) {
            $tab = new Tab();
            $tab->active = 1;
            $tab->class_name = 'AdminBbPacking';
            $tab->name = array_fill_keys(Language::getIDs(false), 'Pakowanie');
            $tab->id_parent = -1; 
            $tab->module = $this->name;
            $tab->add();
        }

        return true;
    }

    private function uninstallTabs()
    {
        $tabs = ['AdminManagerProParent', 'AdminManagerProPanel', 'AdminManagerProConfig', 'AdminManagerProLogs', 'AdminBbPacking', 'AdminBbManager'];
        foreach ($tabs as $className) {
            $id = (int)Tab::getIdFromClassName($className);
            if ($id) {
                $tab = new Tab($id);
                $tab->delete();
            }
        }
        return true;
    }

    // --- HOOKI ---

    public function hookDisplayAdminOrderMain($params)
    {
        $id_order = (int)$params['id_order'];
        $link = $this->context->link->getAdminLink('AdminBbPacking') . '&id_order=' . $id_order;

        return '
        <div class="panel" style="border: 2px solid #3498db; background: #eaf2f8; margin-bottom: 20px;">
            <div class="panel-heading">
                <i class="icon-box"></i> Asystent Pakowania (Skaner)
            </div>
            <div class="row">
                <div class="col-lg-12 text-center">
                    <p style="font-size: 14px; margin-bottom: 15px; color: #333;">
                        Użyj skanera kodów kreskowych, aby skompletować i zweryfikować to zamówienie.
                    </p>
                    <a href="' . $link . '" target="_blank" rel="noopener" class="btn btn-primary btn-lg" style="text-transform: uppercase; font-weight: bold; padding: 15px 30px; font-size: 16px;">
                        <i class="icon-barcode"></i> ROZPOCZNIJ PAKOWANIE
                    </a>
                </div>
            </div>
        </div>';
    }

    /**
     * STYL I JS DO PRZYCISKU W MENU
     */
    public function hookDisplayBackOfficeHeader()
    {
        // Upewnij się, że zakładka LOGI istnieje (dla instalacji z aktualizacją plików bez reinstalacji modułu)
        $this->ensureLogsTabExists();

        if (Tools::getValue('controller') == 'AdminManagerProConfig') {
            $this->context->controller->addCSS($this->_path . 'views/css/admin.css');
        }
        
        return '
        <style>
            /* STYL PRZYCISKU "PANEL ZARZĄDZANIA" - FURGONETKA STYLE */
            #subtab-AdminManagerProPanel > a {
                background-color: #00b297 !important; /* Oryginalny kolor Furgonetka (Morski) */
                color: #ffffff !important;            
                font-weight: 700 !important;          
                text-transform: uppercase;
                letter-spacing: 0.5px;
                border-radius: 3px;
                margin: 6px 10px 6px 0 !important;    
                box-shadow: 0 1px 3px rgba(0,0,0,0.1);
                transition: all 0.2s ease;
                
                /* FIX WYŚRODKOWANIA TEKSTU */
                display: flex !important;
                align-items: center !important;
                justify-content: center !important;
                height: 36px !important;
                padding: 0 10px !important;
                line-height: normal !important; /* Resetuje dziwne dziedziczenie */
            }
            
            /* Hover efekt */
            #subtab-AdminManagerProPanel > a:hover {
                background-color: #009e86 !important; /* Ciemniejszy odcień po najechaniu */
                box-shadow: 0 2px 5px rgba(0,0,0,0.2);
            }

            /* Kolor ikony wewnątrz przycisku */
            #subtab-AdminManagerProPanel > a i {
                color: #ffffff !important;
                margin-right: 5px !important; /* Odsunięcie ikony od tekstu */
                font-size: 18px !important;
                vertical-align: middle !important;
            }
            
            /* Poprawka tekstu w linku */
            #subtab-AdminManagerProPanel > a span {
                vertical-align: middle !important;
                display: inline-block !important;
                margin-top: 2px !important; /* Mikrokorekta optyczna */
            }
        </style>

        <script type="text/javascript">
            document.addEventListener("DOMContentLoaded", function() {
                // Funkcja dodająca target="_blank"
                function forceNewTabLink() {
                    var links = document.querySelectorAll("a[href*=\'controller=AdminManagerProPanel\']");
                    
                    if (links.length > 0) {
                        links.forEach(function(link) {
                            if (link.getAttribute("target") !== "_blank") {
                                link.setAttribute("target", "_blank");
                                link.setAttribute("rel", "noopener noreferrer");
                                link.onclick = function(e) {
                                    e.stopPropagation(); 
                                };
                            }
                        });
                    }
                }
                forceNewTabLink();
                setInterval(forceNewTabLink, 800);
            });
        </script>
        ';
    }

    /**
     * Dla istniejących instalacji: jeśli nowa zakładka nie istnieje, doinstaluj ją.
     */
    private function ensureLogsTabExists()
    {
        try {
            $tabId = (int) Tab::getIdFromClassName('AdminManagerProLogs');
            if ($tabId > 0) {
                return;
            }

            $parentId = (int) Tab::getIdFromClassName('AdminManagerProParent');
            if ($parentId <= 0) {
                return;
            }

            $tab = new Tab();
            $tab->active = 1;
            $tab->class_name = 'AdminManagerProLogs';
            $tab->name = array_fill_keys(Language::getIDs(false), 'Logi');
            $tab->id_parent = $parentId;
            $tab->module = $this->name;
            $tab->icon = 'history';
            $tab->add();
        } catch (Exception $e) {
            // ignore
        }
    }

    // --- BAZA DANYCH ---

    protected function installDb()
    {
        $sql = [];
        $sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'bb_ordermanager_folders` (
            `id_folder` int(11) NOT NULL AUTO_INCREMENT,
            `name` varchar(64) NOT NULL,
            `section` varchar(32) NOT NULL,
            `color_class` varchar(32) DEFAULT "bg-gray-500",
            `position` int(11) DEFAULT 0,
            PRIMARY KEY (`id_folder`)
        ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

        $sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'bb_ordermanager_map` (
            `id_order` int(11) NOT NULL,
            `id_folder` int(11) NOT NULL,
            `date_assigned` datetime DEFAULT CURRENT_TIMESTAMP,
            `is_locked` tinyint(1) DEFAULT 0,
            PRIMARY KEY (`id_order`),
            KEY `idx_folder` (`id_folder`)
        ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

        $sql[] = "CREATE TABLE IF NOT EXISTS `" . _DB_PREFIX_ . "bb_ordermanager_packing` (
            `id_packing` INT(11) NOT NULL AUTO_INCREMENT,
            `id_order` INT(11) NOT NULL,
            `id_order_detail` INT(11) NOT NULL, 
            `product_id` INT(11) NOT NULL,
            `product_attribute_id` INT(11) NOT NULL DEFAULT '0',
            `quantity_packed` INT(11) NOT NULL DEFAULT '0',
            `date_upd` DATETIME NOT NULL,
            PRIMARY KEY (`id_packing`),
            UNIQUE KEY `order_detail_unique` (`id_order_detail`)
        ) ENGINE=" . _MYSQL_ENGINE_ . " DEFAULT CHARSET=utf8;";

        $sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'bb_ordermanager_logs` (
            `id_log` int(11) NOT NULL AUTO_INCREMENT,
            `id_order` int(11) NOT NULL,
            `message` text NOT NULL,
            `date_add` datetime NOT NULL,
            PRIMARY KEY (`id_log`),
            KEY `idx_order_log` (`id_order`)
        ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

        foreach ($sql as $query) {
            if (Db::getInstance()->execute($query) == false) return false;
        }
        return true;
    }

    protected function uninstallDb()
    {
        return true;
    }

    protected function installDefaultFolders()
    {
        $count = (int)Db::getInstance()->getValue('SELECT COUNT(*) FROM `' . _DB_PREFIX_ . 'bb_ordermanager_folders`');
        if ($count > 0) return true;

        $folders = [
            ['name' => 'Nowe (Do zamówienia)', 'section' => 'INBOX', 'color' => 'bg-blue-600', 'pos' => 1],
            ['name' => 'Nieopłacone', 'section' => 'INBOX', 'color' => 'bg-slate-400', 'pos' => 2],
            ['name' => 'Do wyjaśnienia', 'section' => 'INBOX', 'color' => 'bg-red-500', 'pos' => 3],
            ['name' => 'MAGAZYN (Własne)', 'section' => 'TODAY', 'color' => 'bg-indigo-600', 'pos' => 9],
            ['name' => 'BP', 'section' => 'TODAY', 'color' => 'bg-emerald-500', 'pos' => 10],
            ['name' => 'BP(1 poz) - Szybkie', 'section' => 'TODAY', 'color' => 'bg-emerald-400', 'pos' => 11],
            ['name' => 'EKOWITAL', 'section' => 'TODAY', 'color' => 'bg-emerald-500', 'pos' => 12],
            ['name' => 'EKOWITAL(1 poz)', 'section' => 'TODAY', 'color' => 'bg-emerald-400', 'pos' => 13],
            ['name' => 'BP + EKOWITAL', 'section' => 'TODAY', 'color' => 'bg-emerald-500', 'pos' => 14],
            ['name' => 'BP + EKO <10', 'section' => 'TODAY', 'color' => 'bg-emerald-400', 'pos' => 15],
            ['name' => 'NATURA', 'section' => 'TODAY', 'color' => 'bg-emerald-500', 'pos' => 16],
            ['name' => 'STEWIARNIA', 'section' => 'TODAY', 'color' => 'bg-emerald-500', 'pos' => 17],
            ['name' => 'MIX', 'section' => 'TODAY', 'color' => 'bg-orange-400', 'pos' => 18],
            ['name' => 'MIX < 10', 'section' => 'TODAY', 'color' => 'bg-orange-300', 'pos' => 19],
            ['name' => 'Dostawa: JUTRO', 'section' => 'TOMORROW', 'color' => 'bg-yellow-400', 'pos' => 30],
            ['name' => 'Dostawa: POJUTRZE', 'section' => 'TOMORROW', 'color' => 'bg-yellow-200', 'pos' => 31],
            ['name' => 'Czeka na brakujący towar', 'section' => 'TOMORROW', 'color' => 'bg-purple-500', 'pos' => 32],
            ['name' => 'Zwroty (Do obsługi)', 'section' => 'RETURNS', 'color' => 'bg-pink-500', 'pos' => 40],
            ['name' => 'Reklamacje', 'section' => 'RETURNS', 'color' => 'bg-pink-600', 'pos' => 41],
            ['name' => 'Anulowane (Klient)', 'section' => 'ARCHIVE', 'color' => 'bg-gray-800', 'pos' => 50],
            ['name' => 'Anulowane (Sklep)', 'section' => 'ARCHIVE', 'color' => 'bg-gray-800', 'pos' => 51],
            ['name' => 'Spakowane / Gotowe', 'section' => 'ARCHIVE', 'color' => 'bg-teal-600', 'pos' => 52],
        ];

        foreach ($folders as $f) {
            Db::getInstance()->insert('bb_ordermanager_folders', [
                'name' => pSQL($f['name']),
                'section' => pSQL($f['section']),
                'color_class' => pSQL($f['color']),
                'position' => (int)$f['pos']
            ]);
        }

        return true;
    }
    
    public function getContent()
    {
        Tools::redirectAdmin($this->context->link->getAdminLink('AdminManagerProConfig'));
    }
}