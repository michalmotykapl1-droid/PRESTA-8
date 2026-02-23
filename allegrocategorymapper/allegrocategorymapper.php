<?php
// /modules/allegrocategorymapper/allegrocategorymapper.php

if (!defined('_PS_VERSION_')) { exit; }

spl_autoload_register(function($class){
    if (strpos($class, 'ACM\\') === 0) {
        $path = __DIR__ . '/src/' . str_replace('ACM\\', '', $class) . '.php';
        $path = str_replace('\\', '/', $path);
        if (file_exists($path)) { require_once $path; }
    }
});

class Allegrocategorymapper extends Module
{
    public function __construct()
    {
        $this->name = 'allegrocategorymapper';
        $this->tab = 'administration';
        $this->version = '0.4.3'; // Zwiększamy wersję
        $this->author = 'DXNA';
        $this->need_instance = 0;
        $this->bootstrap = true;
        parent::__construct();

        $this->displayName = $this->l('Allegro Category Mapper');
        $this->description = $this->l('OAuth2 przez front controller + mapowanie kategorii.');
    }

    public function install()
    {
        $this->installDb();
        $defaults = array(
            'ACM_API_URL'                    => 'https://api.allegro.pl',
            'ACM_CLIENT_ID'                  => '',
            'ACM_CLIENT_SECRET'              => '',
            'ACM_ACCESS_TOKEN'               => '',
            'ACM_ROOT_CATEGORY_ID'           => (int)Configuration::get('PS_HOME_CATEGORY', 2),
            'ACM_BUILD_FULL_PATH'            => 1,
            'ACM_SKIP_DONE'                  => 1,
            'ACM_MARK_DONE_AFTER_ASSIGN'     => 1,
            'ACM_CHANGE_DEFAULT_CATEGORY'    => 0,
            'ACM_SCAN_CHUNK_SIZE'            => 200,
            'ACM_MAX_RESULTS_PER_PRODUCT'    => 5,
            'ACM_DEFAULT_SELECTION_STRATEGY' => 'score',
            'ACM_SELECT_MODE'                => 'best',
            'ACM_DEBUG'                      => 0,
            'ACM_ADMIN_URL'                  => '',
            'ACM_MIN_SEARCH_WORDS'           => 3,
            'ACM_USE_NAME_SEARCH'            => 1,
            'ACM_NAME_SEARCH_ENDPOINT'       => '/sale/products',
            'ACM_SCAN_INACTIVE_PRODUCTS'     => 0, // Nowa opcja
        );
        foreach ($defaults as $k=>$v) {
            if (Configuration::get($k) === false) Configuration::updateValue($k, $v);
        }
        Configuration::deleteByName('ACM_OAUTH_STATE');
        Configuration::deleteByName('ACM_OAUTH_REDIRECT');
        
        return parent::install()
            && $this->registerHook('actionAdminControllerSetMedia')
            && $this->installTabs();
    }

    public function uninstall()
    {
        $this->uninstallTabs();
        $this->uninstallDb();
        // Dodana nowa opcja do listy usuwanych
        foreach (['ACM_API_URL','ACM_CLIENT_ID','ACM_CLIENT_SECRET','ACM_ACCESS_TOKEN','ACM_REFRESH_TOKEN','ACM_TOKEN_EXPIRES', 'ACM_ROOT_CATEGORY_ID','ACM_BUILD_FULL_PATH','ACM_SKIP_DONE','ACM_MARK_DONE_AFTER_ASSIGN','ACM_CHANGE_DEFAULT_CATEGORY', 'ACM_MAX_RESULTS_PER_PRODUCT','ACM_SCAN_CHUNK_SIZE','ACM_DEFAULT_SELECTION_STRATEGY','ACM_SELECT_MODE','ACM_DEBUG', 'ACM_ADMIN_URL', 'ACM_OAUTH_REDIRECT', 'ACM_OAUTH_STATE', 'ACM_MIN_SEARCH_WORDS', 'ACM_USE_NAME_SEARCH', 'ACM_NAME_SEARCH_ENDPOINT', 'ACM_SCAN_INACTIVE_PRODUCTS'] as $k) Configuration::deleteByName($k);
        return parent::uninstall();
    }
    
    protected function installDb(){$sql[]='CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'allegro_ean_results` (`id_result` INT UNSIGNED NOT NULL AUTO_INCREMENT,`batch_id` INT UNSIGNED NOT NULL,`id_product` INT UNSIGNED NOT NULL,`ean` VARCHAR(32) NULL,`allegro_category_id` VARCHAR(64) NOT NULL,`allegro_category_name` VARCHAR(255) NOT NULL,`allegro_category_path` MEDIUMTEXT NULL,`offers_count` INT NULL,`score` DECIMAL(6,3) NULL,`found_at` DATETIME NOT NULL,PRIMARY KEY (`id_result`),KEY `idx_batch` (`batch_id`),KEY `idx_product` (`id_product`),KEY `idx_al_cat` (`allegro_category_id`)) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8mb4;';$sql[]='CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'allegro_category_map` (`id` INT UNSIGNED NOT NULL AUTO_INCREMENT,`allegro_category_id` VARCHAR(64) NOT NULL,`allegro_category_name` VARCHAR(255) NOT NULL,`ps_id_category` INT UNSIGNED NOT NULL,`created_at` DATETIME NOT NULL,UNIQUE KEY `uniq_al_cat` (`allegro_category_id`),KEY `idx_pscat` (`ps_id_category`),PRIMARY KEY (`id`)) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8mb4;';$sql[]='CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'allegro_ean_done` (`id_product` INT UNSIGNED NOT NULL,`done_at` DATETIME NOT NULL,`last_batch_id` INT UNSIGNED NULL,PRIMARY KEY (`id_product`)) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8mb4;';foreach($sql as $q)if(!Db::getInstance()->execute($q))return false;return true;}
    protected function uninstallDb(){foreach(['allegro_ean_results','allegro_category_map','allegro_ean_done'] as $t){if(!Db::getInstance()->execute('DROP TABLE IF EXISTS `'._DB_PREFIX_.$t.'`'))return false;}return true;}

    protected function installTabs()
    {
        $parentId = (int)Tab::getIdFromClassName('IMPROVE');
        if (!$parentId) $parentId = (int)Tab::getIdFromClassName('AdminCatalog');
        $parent = new Tab();
        $parent->active = 1;
        $parent->class_name = 'AdminAllegroCategoryMapperParent';
        $parent->id_parent = $parentId;
        $parent->module = $this->name;
        foreach (Language::getLanguages(false) as $lang) $parent->name[$lang['id_lang']] = 'Allegro Category Mapper';
        if (!$parent->add()) return false;
        $children = ['AdminAllegroCategoryMapperManager' => 'Skan i wyniki', 'AdminAllegroCategoryMapperTree' => 'Podgląd Drzewa', 'AdminAllegroCategoryMapperDone' => 'Zrobione', 'AdminAllegroCategoryMapperMappings' => 'Mapy i logi', 'AdminAllegroCategoryMapperConfig' => 'Konfiguracja',];
        foreach ($children as $class => $label) {
            $tab = new Tab();
            $tab->active = 1;
            $tab->class_name = $class;
            $tab->id_parent = (int)$parent->id;
            $tab->module = $this->name;
            foreach (Language::getLanguages(false) as $lang) $tab->name[$lang['id_lang']] = $label;
            if (!$tab->add()) return false;
        }
        return true;
    }

    protected function uninstallTabs()
    {
        $tabs = ['AdminAllegroCategoryMapperManager', 'AdminAllegroCategoryMapperTree', 'AdminAllegroCategoryMapperDone', 'AdminAllegroCategoryMapperMappings', 'AdminAllegroCategoryMapperConfig', 'AdminAllegroCategoryMapperParent'];
        foreach ($tabs as $class) {
            if ($id = (int)Tab::getIdFromClassName($class)) { $tab = new Tab($id); $tab->delete(); }
        }
        return true;
    }

    public function hookActionAdminControllerSetMedia($params)
    {
        $controllerName = Tools::getValue('controller');
        if (strpos($controllerName, 'AdminAllegroCategoryMapper') === 0) {
            $this->context->controller->addCSS($this->_path . 'views/css/admin.css');
            $this->context->controller->addCSS($this->_path . 'views/css/admin.acm-tree.css');
            $this->context->controller->addJS($this->_path . 'views/js/admin.js');
            $this->context->controller->addJS($this->_path . 'views/js/admin.acm-tree.js');
            
            if ($controllerName === 'AdminAllegroCategoryMapperManager') {
                $js_path = $this->_path . 'views/js/scan.ajax.js';
                $this->context->controller->addJS($js_path . '?v=' . $this->version);

                Media::addJsDef([
                    'acmAjaxUrl' => $this->context->link->getAdminLink('AdminAllegroCategoryMapperManager'),
                    'acmChunkSize' => (int)Configuration::get('ACM_SCAN_CHUNK_SIZE'),
                ]);
            }
        }
    }

    public function getContent()
    {
        if (Tools::isSubmit('submitAcmConfig')) {
            Configuration::updateValue('ACM_API_URL', Tools::getValue('ACM_API_URL'));
            Configuration::updateValue('ACM_CLIENT_ID', Tools::getValue('ACM_CLIENT_ID'));
            Configuration::updateValue('ACM_CLIENT_SECRET', Tools::getValue('ACM_CLIENT_SECRET'));
            Configuration::updateValue('ACM_ACCESS_TOKEN', Tools::getValue('ACM_ACCESS_TOKEN'));
            Configuration::updateValue('ACM_ROOT_CATEGORY_ID', (int)Tools::getValue('ACM_ROOT_CATEGORY_ID'));
            Configuration::updateValue('ACM_SCAN_CHUNK_SIZE', (int)Tools::getValue('ACM_SCAN_CHUNK_SIZE'));
            Configuration::updateValue('ACM_MAX_RESULTS_PER_PRODUCT', (int)Tools::getValue('ACM_MAX_RESULTS_PER_PRODUCT'));
            Configuration::updateValue('ACM_DEFAULT_SELECTION_STRATEGY', (string)Tools::getValue('ACM_DEFAULT_SELECTION_STRATEGY'));
            Configuration::updateValue('ACM_SELECT_MODE', (string)Tools::getValue('ACM_SELECT_MODE'));
            Configuration::updateValue('ACM_BUILD_FULL_PATH', (int)(bool)Tools::getValue('ACM_BUILD_FULL_PATH'));
            Configuration::updateValue('ACM_SKIP_DONE', (int)(bool)Tools::getValue('ACM_SKIP_DONE'));
            Configuration::updateValue('ACM_MARK_DONE_AFTER_ASSIGN', (int)(bool)Tools::getValue('ACM_MARK_DONE_AFTER_ASSIGN'));
            Configuration::updateValue('ACM_CHANGE_DEFAULT_CATEGORY', (int)(bool)Tools::getValue('ACM_CHANGE_DEFAULT_CATEGORY'));
            Configuration::updateValue('ACM_DEBUG', (int)(bool)Tools::getValue('ACM_DEBUG'));
            Configuration::updateValue('ACM_MIN_SEARCH_WORDS', (int)Tools::getValue('ACM_MIN_SEARCH_WORDS'));
            Configuration::updateValue('ACM_USE_NAME_SEARCH', (int)(bool)Tools::getValue('ACM_USE_NAME_SEARCH'));
            Configuration::updateValue('ACM_NAME_SEARCH_ENDPOINT', (string)Tools::getValue('ACM_NAME_SEARCH_ENDPOINT'));
            Configuration::updateValue('ACM_SCAN_INACTIVE_PRODUCTS', (int)(bool)Tools::getValue('ACM_SCAN_INACTIVE_PRODUCTS'));
            $this->context->controller->confirmations[] = $this->l('Zapisano ustawienia.');
        }
        $adminUrl = Tools::getShopDomainSsl(true, true).__PS_BASE_URI__.basename(_PS_ADMIN_DIR_).'/';
        Configuration::updateValue('ACM_ADMIN_URL', $adminUrl);
        $redirectUri = Tools::getShopDomainSsl(true, true). __PS_BASE_URI__ . 'index.php?fc=module&module='.$this->name.'&controller=oauthcallback';
        Configuration::updateValue('ACM_OAUTH_REDIRECT', $redirectUri);
        $state = Tools::substr(sha1(mt_rand().microtime(true)), 0, 16);
        Configuration::updateValue('ACM_OAUTH_STATE', $state);
        $apiUrl   = (string)Configuration::get('ACM_API_URL');
        $clientId = (string)Configuration::get('ACM_CLIENT_ID');
        $authorizeHost = (stripos($apiUrl, 'sandbox') !== false) ? 'https://allegro.pl.allegrosandbox.pl' : 'https://allegro.pl';
        $authUrl = sprintf('%s/auth/oauth/authorize?response_type=code&client_id=%s&redirect_uri=%s&state=%s', $authorizeHost, rawurlencode($clientId), rawurlencode($redirectUri), rawurlencode($state));
        
        $vals = [
            'ACM_API_URL'                    => $apiUrl ?: 'https://api.allegro.pl',
            'ACM_CLIENT_ID'                  => $clientId,
            'ACM_CLIENT_SECRET'              => (string)Configuration::get('ACM_CLIENT_SECRET'),
            'ACM_ACCESS_TOKEN'               => (string)Configuration::get('ACM_ACCESS_TOKEN'),
            'ACM_ROOT_CATEGORY_ID'           => (int)Configuration::get('ACM_ROOT_CATEGORY_ID'),
            'ACM_BUILD_FULL_PATH'            => (int)Configuration::get('ACM_BUILD_FULL_PATH'),
            'ACM_SKIP_DONE'                  => (int)Configuration::get('ACM_SKIP_DONE'),
            'ACM_MARK_DONE_AFTER_ASSIGN'     => (int)Configuration::get('ACM_MARK_DONE_AFTER_ASSIGN'),
            'ACM_CHANGE_DEFAULT_CATEGORY'    => (int)Configuration::get('ACM_CHANGE_DEFAULT_CATEGORY'),
            'ACM_SCAN_CHUNK_SIZE'            => (int)Configuration::get('ACM_SCAN_CHUNK_SIZE'),
            'ACM_MAX_RESULTS_PER_PRODUCT'    => (int)Configuration::get('ACM_MAX_RESULTS_PER_PRODUCT'),
            'ACM_DEFAULT_SELECTION_STRATEGY' => (string)Configuration::get('ACM_DEFAULT_SELECTION_STRATEGY'),
            'ACM_SELECT_MODE'                => (string)Configuration::get('ACM_SELECT_MODE'),
            'ACM_DEBUG'                      => (int)Configuration::get('ACM_DEBUG'),
            'ACM_MIN_SEARCH_WORDS'           => (int)Configuration::get('ACM_MIN_SEARCH_WORDS'),
            'ACM_USE_NAME_SEARCH'            => (int)Configuration::get('ACM_USE_NAME_SEARCH'),
            'ACM_NAME_SEARCH_ENDPOINT'       => (string)Configuration::get('ACM_NAME_SEARCH_ENDPOINT'),
            'ACM_SCAN_INACTIVE_PRODUCTS'     => (int)Configuration::get('ACM_SCAN_INACTIVE_PRODUCTS'),
        ];

        $status = $vals['ACM_ACCESS_TOKEN'] ? '<span style="color:#3c763d;font-weight:600">Połączono z Allegro (token zapisany)</span>' : '<span style="color:#a94442">Brak tokenu – wykonaj autoryzację</span>';
        $h  = '<div class="alert alert-info">Redirect URI: <code>' . htmlspecialchars($redirectUri, ENT_QUOTES, 'UTF-8') . '</code></div>';
        $h .= '<p>'.$status.'</p>';
        $h .= '<form method="post" class="panel" style="padding:12px">';
        $h .= '<h3>OAuth2 (Allegro)</h3>';
        $h .= input('API URL','ACM_API_URL',$vals['ACM_API_URL']);
        $h .= input('Client ID','ACM_CLIENT_ID',$vals['ACM_CLIENT_ID']);
        $h .= input('Client Secret','ACM_CLIENT_SECRET',$vals['ACM_CLIENT_SECRET']);
        $h .= input('Access Token (opcjonalnie ręcznie)','ACM_ACCESS_TOKEN',$vals['ACM_ACCESS_TOKEN']);
        $h .= '<hr><h3>Ogólne</h3>';
        $h .= number('ID kategorii-rodzica (PS)','ACM_ROOT_CATEGORY_ID',$vals['ACM_ROOT_CATEGORY_ID']);
        $h .= checkbox('Twórz pełną ścieżkę kategorii','ACM_BUILD_FULL_PATH',$vals['ACM_BUILD_FULL_PATH']);
        $h .= checkbox('Pomijaj ZROBIONE','ACM_SKIP_DONE',$vals['ACM_SKIP_DONE']);
        $h .= checkbox('Oznaczaj jako ZROBIONE po przypisaniu','ACM_MARK_DONE_AFTER_ASSIGN',$vals['ACM_MARK_DONE_AFTER_ASSIGN']);
        $h .= checkbox('Zmieniaj kategorię domyślną produktu','ACM_CHANGE_DEFAULT_CATEGORY',$vals['ACM_CHANGE_DEFAULT_CATEGORY']);
        $h .= checkbox('Tryb debug (logi w /modules/allegrocategorymapper/logs)','ACM_DEBUG',$vals['ACM_DEBUG']);
        $h .= '<hr><h3>Skanowanie (AJAX)</h3>';
        $h .= number('Maks. produktów na skan','ACM_SCAN_CHUNK_SIZE',$vals['ACM_SCAN_CHUNK_SIZE']);
        $h .= number('Maks. trafień (kategorii) na produkt','ACM_MAX_RESULTS_PER_PRODUCT',$vals['ACM_MAX_RESULTS_PER_PRODUCT']);
        $h .= checkbox('Skanuj również nieaktywne produkty','ACM_SCAN_INACTIVE_PRODUCTS',$vals['ACM_SCAN_INACTIVE_PRODUCTS']);
        $h .= checkbox('Używaj wyszukiwania po nazwie jako opcji rezerwowej', 'ACM_USE_NAME_SEARCH', $vals['ACM_USE_NAME_SEARCH']);
        $h .= number('Minimalna liczba słów w wyszukiwaniu po nazwie', 'ACM_MIN_SEARCH_WORDS', $vals['ACM_MIN_SEARCH_WORDS']);
        $h .= select('Endpoint wyszukiwania po nazwie', 'ACM_NAME_SEARCH_ENDPOINT', $vals['ACM_NAME_SEARCH_ENDPOINT'], [
            '/sale/products' => 'Wyszukiwanie w katalogu (mniej dokładne, nie wymaga weryfikacji)',
            '/offers/listing' => 'Wyszukiwanie ofert (zalecane, wymaga weryfikacji aplikacji)'
        ]);
        $h .= '<hr><h3>Strategia wyboru kategorii</h3>';
        $h .= select('Strategia domyślna','ACM_DEFAULT_SELECTION_STRATEGY',$vals['ACM_DEFAULT_SELECTION_STRATEGY'], array('score'=>'score','offers'=>'offers'));
        $h .= select('Tryb zapisu','ACM_SELECT_MODE',$vals['ACM_SELECT_MODE'], array('best'=>'Zapisuj jedną (najlepszą)','all'=>'Zapisuj wszystkie'));
        $h .= '<div style="margin-top:12px">';
        $h .= '<button type="submit" name="submitAcmConfig" class="btn btn-primary"><i class="icon-save"></i> Zapisz ustawienia</button> ';
        $h .= '<a class="btn btn-default" href="'.htmlspecialchars($authUrl, ENT_QUOTES, 'UTF-8').'"><i class="icon-key"></i> Autoryzuj z Allegro</a>';
        $h .= '</div></form>';
        return $h;
    }
}
function input($label,$name,$value){ return '<div class="form-group"><label>'.$label.'</label><input class="form-control" name="'.$name.'" value="'.htmlspecialchars($value, ENT_QUOTES, 'UTF-8').'" /></div>'; }
function number($label,$name,$value){ return '<div class="form-group"><label>'.$label.'</label><input type="number" class="form-control" name="'.$name.'" value="'.(int)$value.'" /></div>'; }
function checkbox($label,$name,$checked){ return '<div class="checkbox"><label><input type="checkbox" name="'.$name.'" value="1" '.($checked?'checked':'').' /> '.$label.'</label></div>'; }
function select($label,$name,$current,$opts){ $o=''; foreach($opts as $k=>$v){ $o.='<option value="'.$k.'" '.($current===$k?'selected':'').'>'.$v.'</option>'; } return '<div class="form-group"><label>'.$label.'</label><select name="'.$name.'" class="form-control">'.$o.'</select></div>'; }