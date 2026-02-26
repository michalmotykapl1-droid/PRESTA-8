<?php
/**
 * BigBio Allegro Shipping - Multi-Account Version
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class Bb_allegroshipping extends Module
{
    public function __construct()
    {
        $this->name = 'bb_allegroshipping';
        $this->tab = 'shipping_logistics';
        $this->version = '2.0.0'; // Wersja 2.0 z obsługą wielu kont
        $this->author = 'BigBio Dev';
        $this->need_instance = 0;
        $this->bootstrap = true;
        $this->ps_versions_compliancy = ['min' => '1.7', 'max' => _PS_VERSION_];

        parent::__construct();

        $this->displayName = $this->l('BigBio: Wysyłam z Allegro (Multi-Konto)');
        $this->description = $this->l('Obsługa wielu kont Allegro. Integracja API dla Managera Zamówień.');
    }

    public function install()
    {
        return parent::install() &&
            $this->installDb() &&
            $this->registerHook('actionCarrierProcess');
    }

    public function uninstall()
    {
        return parent::uninstall() && $this->uninstallDb();
    }

    /**
     * Tworzenie tabeli dla kont Allegro
     */
    protected function installDb()
    {
        $sql = "CREATE TABLE IF NOT EXISTS `" . _DB_PREFIX_ . "bb_allegro_accounts` (
            `id_account` INT(11) NOT NULL AUTO_INCREMENT,
            `name` VARCHAR(64) NOT NULL,
            `client_id` VARCHAR(255) NOT NULL,
            `client_secret` VARCHAR(255) NOT NULL,
            `sandbox` TINYINT(1) DEFAULT 0,
            `access_token` TEXT,
            `refresh_token` TEXT,
            `token_expires` DATETIME,
            `date_add` DATETIME NOT NULL,
            `date_upd` DATETIME NOT NULL,
            PRIMARY KEY (`id_account`)
        ) ENGINE=" . _MYSQL_ENGINE_ . " DEFAULT CHARSET=utf8;";

        return Db::getInstance()->execute($sql);
    }

    protected function uninstallDb()
    {
        return Db::getInstance()->execute("DROP TABLE IF EXISTS `" . _DB_PREFIX_ . "bb_allegro_accounts`");
    }

    /**
     * Panel Konfiguracji - CRUD (Lista / Dodawanie / Edycja / Usuwanie)
     */
    public function getContent()
    {
        $output = '';

        // 1. Zapisywanie formularza
        if (Tools::isSubmit('saveAccount')) {
            $id_account = (int) Tools::getValue('id_account');
            $name = pSQL(Tools::getValue('name'));
            $client_id = pSQL(Tools::getValue('client_id'));
            $client_secret = pSQL(Tools::getValue('client_secret')); // Secret trzymamy jawnie w bazie dla uproszczenia, w produkcji warto szyfrować
            $sandbox = (int) Tools::getValue('sandbox');

            if (empty($name) || empty($client_id) || empty($client_secret)) {
                $output .= $this->displayError($this->l('Wypełnij wszystkie pola wymagane.'));
            } else {
                $data = [
                    'name' => $name,
                    'client_id' => $client_id,
                    'client_secret' => $client_secret,
                    'sandbox' => $sandbox,
                    'date_upd' => date('Y-m-d H:i:s')
                ];

                if ($id_account) {
                    Db::getInstance()->update('bb_allegro_accounts', $data, 'id_account = ' . $id_account);
                    $output .= $this->displayConfirmation($this->l('Konto zaktualizowane.'));
                } else {
                    $data['date_add'] = date('Y-m-d H:i:s');
                    Db::getInstance()->insert('bb_allegro_accounts', $data);
                    $output .= $this->displayConfirmation($this->l('Nowe konto dodane.'));
                }
            }
        }
        // 2. Usuwanie
        elseif (Tools::isSubmit('deletebb_allegroshipping') && Tools::getValue('id_account')) {
            $id = (int) Tools::getValue('id_account');
            Db::getInstance()->delete('bb_allegro_accounts', 'id_account = ' . $id);
            $output .= $this->displayConfirmation($this->l('Konto usunięte.'));
        }

        // 3. Widok Formularza (Edycja/Dodawanie)
        if (Tools::isSubmit('addAccount') || (Tools::isSubmit('updatebb_allegroshipping') && Tools::getValue('id_account'))) {
            return $output . $this->renderForm();
        }

        // 4. Widok Listy (Domyślny)
        return $output . $this->renderList();
    }

    /**
     * Generuje listę kont
     */
    protected function renderList()
    {
        $fields_list = [
            'id_account' => ['title' => $this->l('ID'), 'width' => 20],
            'name' => ['title' => $this->l('Nazwa Konta'), 'width' => 'auto'],
            'client_id' => ['title' => $this->l('Client ID'), 'width' => 'auto'],
            'sandbox' => ['title' => $this->l('Sandbox'), 'active' => 'status', 'type' => 'bool', 'align' => 'center'],
        ];

        $helper = new HelperList();
        $helper->shopLinkType = '';
        $helper->simple_header = false;
        $helper->actions = ['edit', 'delete'];
        $helper->identifier = 'id_account';
        $helper->show_toolbar = true;
        $helper->title = $this->l('Twoje Konta Allegro');
        $helper->table = $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex . '&configure=' . $this->name;

        // Przycisk "Dodaj nowe"
        $helper->toolbar_btn['new'] = [
            'href' => $helper->currentIndex . '&addAccount&token=' . $helper->token,
            'desc' => $this->l('Dodaj nowe konto')
        ];

        $accounts = Db::getInstance()->executeS("SELECT * FROM `" . _DB_PREFIX_ . "bb_allegro_accounts` ORDER BY id_account ASC");

        return $helper->generateList($accounts, $fields_list);
    }

    /**
     * Generuje formularz dodawania/edycji
     */
    protected function renderForm()
    {
        $defaultLang = (int) Configuration::get('PS_LANG_DEFAULT');
        $id_account = (int) Tools::getValue('id_account');
        $account = [];

        if ($id_account) {
            $account = Db::getInstance()->getRow("SELECT * FROM `" . _DB_PREFIX_ . "bb_allegro_accounts` WHERE id_account = " . $id_account);
        }

        $fields_form = [
            'form' => [
                'legend' => [
                    'title' => $id_account ? $this->l('Edytuj konto') : $this->l('Dodaj nowe konto'),
                    'icon' => 'icon-user'
                ],
                'input' => [
                    [
                        'type' => 'hidden',
                        'name' => 'id_account'
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->l('Nazwa własna konta'),
                        'name' => 'name',
                        'desc' => $this->l('Np. MojeKonto1, SklepWędkarski itp.'),
                        'required' => true
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->l('Client ID'),
                        'name' => 'client_id',
                        'required' => true
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->l('Client Secret'),
                        'name' => 'client_secret',
                        'required' => true
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->l('Tryb Sandbox'),
                        'name' => 'sandbox',
                        'is_bool' => true,
                        'values' => [
                            ['id' => 'active_on', 'value' => 1, 'label' => $this->l('Tak')],
                            ['id' => 'active_off', 'value' => 0, 'label' => $this->l('Nie')],
                        ],
                    ],
                ],
                'submit' => [
                    'title' => $this->l('Zapisz'),
                    'name' => 'saveAccount'
                ]
            ]
        ];

        $helper = new HelperForm();
        $helper->module = $this;
        $helper->name_controller = $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex . '&configure=' . $this->name;
        $helper->default_form_language = $defaultLang;
        
        $helper->fields_value['id_account'] = $id_account;
        $helper->fields_value['name'] = isset($account['name']) ? $account['name'] : '';
        $helper->fields_value['client_id'] = isset($account['client_id']) ? $account['client_id'] : '';
        $helper->fields_value['client_secret'] = isset($account['client_secret']) ? $account['client_secret'] : '';
        $helper->fields_value['sandbox'] = isset($account['sandbox']) ? $account['sandbox'] : 0;

        return $helper->generateForm([$fields_form]);
    }
}