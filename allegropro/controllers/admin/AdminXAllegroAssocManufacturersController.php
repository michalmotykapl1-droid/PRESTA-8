<?php

require_once (dirname(__FILE__) . '/../../x13allegro.php');

final class AdminXAllegroAssocManufacturersController extends XAllegroController
{
    /** @var XAllegroManufacturer */
    public $object;

    public function __construct()
    {
        $this->table = 'xallegro_manufacturer';
        $this->identifier = 'id_xallegro_manufacturer';
        $this->className = 'XAllegroManufacturer';
        $this->multiple_fieldsets = true;

        parent::__construct();

        $this->tabAccess = Profile::getProfileAccess($this->context->employee->id_profile, Tab::getIdFromClassName('AdminXAllegroAssocManufacturers'));
        $this->tpl_folder = 'x_allegro_manufacturers/';

        $this->bulk_actions = array(
            'delete' => array(
                'text' => $this->l('Delete selected'),
                'confirm' => $this->l('Delete selected items?'),
                'icon' => 'icon-trash'
            )
        );
    }

    public function init()
    {
        parent::init();

        $this->loadObject(true);
    }

    public function initToolbar()
    {
        if ($this->display == 'add' || $this->display == 'edit') {
            $this->toolbar_btn['save_and_stay'] = array(
                'href' => self::$currentIndex . '&token=' . $this->token,
                'desc' => $this->l('Zapisz i zostań'),
                'class' => 'process-icon-save-and-stay '
            );
        }

        parent::initToolbar();
    }

    public function renderList()
    {
        if (Tools::getValue('controller') == 'AdminXAllegroAssocManufacturers' && empty($this->errors)) {
            Tools::redirectAdmin($this->context->link->getAdminLink('AdminXAllegroAssoc'));
        }

        $this->initToolbar();

        if (method_exists($this, 'initPageHeaderToolbar')) {
            $this->initPageHeaderToolbar();
        }

        $this->addRowAction('edit');
        $this->addRowAction('delete');

        $this->_select = 'm.`name` as id_manufacturer';
        $this->_join = 'JOIN `' . _DB_PREFIX_ . 'manufacturer` m ON (a.`id_manufacturer` = m.`id_manufacturer`)';

        $this->fields_list = array(
            'id_manufacturer' => array(
                'title' => $this->l('Producent'),
                'search' => false,
                'filter' => false
            )
        );

        return parent::renderList();
    }

    public function renderForm()
    {
        if (!Validate::isLoadedObject($this->object)) {
            $this->warnings[] = $this->l('Musisz zapisać tego producenta przed mapowaniem tagów.');
        }

        $manufacturers = array_merge(
            array(
                array(
                    'id_manufacturer' => 0,
                    'name' => $this->l('-- Wybierz --')
                )
            ),
            Manufacturer::getManufacturers()
        );

        $this->fields_form[]['form'] = array(
            'legend' => array(
                'title' => $this->l('Producent')
            ),
            'input' => array(
                array(
                    'type' => 'select',
                    'label' => $this->l('Producent'),
                    'name' => 'id_manufacturer',
                    'required' => true,
                    'options' => array(
                        'query' => $manufacturers,
                        'id' => 'id_manufacturer',
                        'name' => 'name'
                    )
                )
            ),
            'submit' => array(
                'title' => $this->l('Zapisz'),
            ),
            'buttons' => array(
                'save-and-stay' => array(
                    'title' => $this->l('Zapisz i zostań'),
                    'name' => 'submitAdd' . $this->table . 'AndStay',
                    'type' => 'submit',
                    'class' => 'btn btn-default pull-right',
                    'icon' => 'process-icon-save'
                )
            )
        );

        $tagManager = new XAllegroHelperTagManager();
        $tagManager->setMapType(XAllegroTagManager::MAP_MANUFACTURER);
        $tagManager->setContainer('xallegro_manufacturer_form');

        $this->fields_form[]['form'] = array(
            'legend' => array(
                'title' => $this->l('Tagi producenta'),
            ),
            'input' => array(
                array(
                    'type' => 'tag-manager',
                    'name' => 'tag-manager',
                    'content' => (Validate::isLoadedObject($this->object) ? $tagManager->renderTagManager($this->object->tags) : '')
                )
            ),
            'submit' => array(
                'title' => $this->l('Zapisz'),
            ),
            'buttons' => array(
                'save-and-stay' => array(
                    'title' => $this->l('Zapisz i zostań'),
                    'name' => 'submitAdd' . $this->table . 'AndStay',
                    'type' => 'submit',
                    'class' => 'btn btn-default pull-right',
                    'icon' => 'process-icon-save'
                )
            )
        );

        return parent::renderForm();
    }

    public function postProcess()
    {
        if (Tools::isSubmit('submitAdd' . $this->table)
            || Tools::isSubmit('submitAdd' . $this->table . 'AndStay')
        ) {
            $id_manufacturer = Tools::getValue('id_manufacturer');

            if (!$id_manufacturer) {
                $this->errors[] = $this->l('Nie wybrano producenta.');
                return false;
            }
            else if ((Validate::isLoadedObject($this->object)
                    && $this->object->id_manufacturer != $id_manufacturer
                    && XAllegroManufacturer::isAssigned($id_manufacturer))
                || (!Validate::isLoadedObject($this->object)
                    && XAllegroManufacturer::isAssigned($id_manufacturer))
            ) {
                $this->errors[] = $this->l('Posiadasz już powiązanie tego producenta.');
                return false;
            }

            foreach (Tools::getValue('xallegro_tag', array()) as $user_id => $tags) {
                $this->object->tags[$user_id] = $tags;
            }

            $this->object->id_manufacturer = $id_manufacturer;
            $this->object->save();

            if (Tools::isSubmit('submitAdd' . $this->table . 'AndStay')) {
                Tools::redirectAdmin($this->context->link->getAdminLink('AdminXAllegroAssocManufacturers') .
                    '&conf=4&update' . $this->table . '&' . $this->identifier . '=' . $this->object->id);
            }

            Tools::redirectAdmin($this->context->link->getAdminLink('AdminXAllegroAssoc') . '&conf=4');
        }

        return parent::postProcess();
    }

    public function initContent()
    {
        if (!empty($this->errors)) {
            $this->display = 'edit';
        }

        parent::initContent();
    }
public function ajaxProcessAutoMapManufacturers()
{
    header('Content-Type: application/json; charset=utf-8');
    try {
        $threshold = (float)\Tools::getValue('threshold', 0.92);
        $save      = (bool)\Tools::getValue('save', false);
        $limit     = (int)\Tools::getValue('limit', 500);

        require_once _PS_MODULE_DIR_.'x13allegro/classes/php81/Service/ManufacturerAutoMapperService.php';
        $svc = new \x13allegro\Service\ManufacturerAutoMapperService($this->context->shop->id, null, 'allegro-pl');
        $res = $svc->autoMapAll($threshold, $save, $limit);

        die(json_encode(['success'=>true] + $res));
    } catch (\Throwable $e) {
        http_response_code(500);
        die(json_encode(['success'=>false, 'message'=>$e->getMessage()]));
    }
}

    
    /** Ensure our JS is loaded on this admin page (correct signature) */
    public function setMedia($isNewTheme = false)
    {
        parent::setMedia($isNewTheme);
        // Load producers toolbar sync JS on this page
        $this->addJS(_MODULE_DIR_.'x13allegro/views/js/producers_toolbar_sync.js');
    }

    /** AJAX: list Allegro accounts (discovered from X13 DB tables) */
    public function ajaxProcessListAccounts()
    {
        try {
            $accs = $this->discoverX13AllegroAccounts();
            die(json_encode(['success'=>true, 'accounts'=>$accs]));
        } catch (\Throwable $e) {
            http_response_code(500);
            die(json_encode(['success'=>false, 'error'=>$e->getMessage()]));
        }
    }

    /** AJAX: create missing Responsible Producers in Allegro for all PS manufacturers */
    public function ajaxProcessSyncMissingProducers()
    {
        try {
            $accountKey = (string)\Tools::getValue('account_key');
            $dry = (bool)\Tools::getValue('dry', false);
            if (!$accountKey) {
                throw new \RuntimeException('Missing account_key');
            }
            $accs = $this->discoverX13AllegroAccounts();
            $acc = null;
            foreach ($accs as $a) {
                if ($a['account_key'] === $accountKey) { $acc = $a; break; }
            }
            if (!$acc || empty($acc['access_token'])) {
                throw new \RuntimeException('Account not found or token missing');
            }

            $existing = $this->fetchAllegroProducers($acc['access_token'], (bool)$acc['is_sandbox']);

            $rows = [];
            $mans = \Db::getInstance()->executeS('SELECT id_manufacturer, name FROM '._DB_PREFIX_.'manufacturer ORDER BY name ASC');
            foreach ($mans as $m) {
                $name = trim((string)$m['name']);
                if ($name==='') { continue; }
                $norm = $this->normName($name);
                $id = isset($existing[$norm]) ? $existing[$norm] : null;

                if (!$id) {
                    if ($dry) {
                        $rows[] = ['m'=>$name, 'action'=>'CREATE'];
                        continue;
                    }
                    $payload = [
                        'name' => $name,
                        'producerData' => [
                            'contact' => new \stdClass(),
                            'address' => new \stdClass(),
                        ],
                    ];
                    $res = $this->allegroRequest($acc['access_token'], (bool)$acc['is_sandbox'], 'POST', '/sale/responsible-producers', $payload);
                    if ($res['status']>=200 && $res['status']<300 && !empty($res['body']['id'])) {
                        $id = $res['body']['id'];
                        $existing[$norm] = $id;
                        $rows[] = ['m'=>$name, 'action'=>'CREATED', 'id'=>$id];
                        $this->ensureX13ManufacturerMap((int)$m['id_manufacturer']);
                    } else {
                        $err = isset($res['body']['errors']) ? json_encode($res['body']['errors']) : ($res['error'] ?: ('HTTP '.$res['status']));
                        $rows[] = ['m'=>$name, 'action'=>'ERR', 'msg'=>$err];
                        continue;
                    }
                } else {
                    $rows[] = ['m'=>$name, 'action'=>'EXISTS', 'id'=>$id];
                $this->ensureX13ManufacturerMap((int)$m['id_manufacturer']);
                }
            }

            die(json_encode(['success'=>true, 'rows'=>$rows]));
        } catch (\Throwable $e) {
            http_response_code(500);
            die(json_encode(['success'=>false, 'error'=>$e->getMessage()]));
        }
    }

    /** Discover X13 accounts/tokens (best-effort) */
    protected function discoverX13AllegroAccounts(): array
    {
        $db = \Db::getInstance();
        $prefix = _DB_PREFIX_;
        $tables = array_merge(
            $db->executeS('SHOW TABLES LIKE "'.pSQL($prefix).'%allegro%account%"'),
            $db->executeS('SHOW TABLES LIKE "'.pSQL($prefix).'%x13%allegro%"')
        );
        $flat = [];
        foreach ($tables as $row) { foreach ($row as $t) { $flat[$t] = $t; } }
        $out = [];
        foreach ($flat as $table) {
            try {
                $rows = $db->executeS('SELECT * FROM `'.$table.'` LIMIT 50');
                foreach ($rows as $r) {
                    $acc = ['table'=>$table,'id'=>null,'label'=>null,'access_token'=>null,'refresh_token'=>null,'expires_at'=>null,'is_sandbox'=>0,'account_key'=>null];
                    foreach (['id','id_account','id_allegro','id_x13account'] as $c) { if (isset($r[$c])) { $acc['id'] = $r[$c]; break; } }
                    foreach (['name','label','login','user_login','shop_name'] as $c) { if (isset($r[$c]) && $r[$c]) { $acc['label'] = $r[$c]; break; } }
                    if (!$acc['label']) { $acc['label'] = $table.'#'.$acc['id']; }
                    foreach ($r as $k=>$v) {
                        $lk = strtolower($k);
                        if (!$acc['access_token']  && strpos($lk,'access')!==false && strpos($lk,'token')!==false)  { $acc['access_token'] = $v; }
                        if (!$acc['refresh_token'] && strpos($lk,'refresh')!==false && strpos($lk,'token')!==false) { $acc['refresh_token'] = $v; }
                        if (!$acc['expires_at']    && (strpos($lk,'expire')!==false || strpos($lk,'valid')!==false) ) { $acc['expires_at'] = $v; }
                        if (strpos($lk,'sandbox')!==false || strpos($lk,'env')!==false) {
                            $acc['is_sandbox'] = (int)($v==1 || strtolower((string)$v)=='sandbox' || strtolower((string)$v)=='test');
                        }
                    }
                    if (!empty($acc['access_token'])) {
                        $acc['account_key'] = md5($acc['table'].'|'.$acc['id']);
                        $out[] = $acc;
                    }
                }
            } catch (\Throwable $e) {}
        }
        return $out;
    }

    /** Fetch map name_norm => id of Allegro producers */
    protected function fetchAllegroProducers(string $token, bool $sandbox): array
    {
        $map = []; $offset=0; $limit=100;
        for ($i=0;$i<50;$i++) {
            $res = $this->allegroRequest($token, $sandbox, 'GET', '/sale/responsible-producers?limit='.$limit.'&offset='.$offset, null);
            if ($res['status']<200 || $res['status']>=300) break;
            $items = isset($res['body']['responsibleProducers']) ? $res['body']['responsibleProducers'] : [];
            foreach ($items as $it) {
                if (!empty($it['name']) && !empty($it['id'])) {
                    $map[$this->normName($it['name'])] = $it['id'];
                }
            }
            if (count($items) < $limit) break;
            $offset += $limit;
        }
        return $map;
    }

    protected function allegroRequest(string $token, bool $sandbox, string $method, string $path, ?array $payload): array
    {
        $base = $sandbox ? 'https://api.allegro.pl.allegrosandbox.pl' : 'https://api.allegro.pl';
        $url = rtrim($base,'/').$path;
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer '.$token,
            'Accept: application/vnd.allegro.public.v1+json',
            'Accept-Language: pl-PL',
            'Content-Type: application/vnd.allegro.public.v1+json',
        ]);
        if ($payload !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        }
        $res = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);
        return ['status'=>$status, 'body'=>$res ? json_decode($res,true) : null, 'error'=>$err];
    }

    protected function normName(string $s): string
    {
        $s = strtolower(trim($s));
        $s = preg_replace('~\s+~', ' ', $s);
        return $s;
    }
    /** Insert row into xallegro_manufacturer (Powiązania producentów) if missing */
    protected function ensureX13ManufacturerMap(int $idManufacturer): void
    {
        try {
            $table = _DB_PREFIX_.'xallegro_manufacturer';
            $exists = (bool)\Db::getInstance()->getValue('SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name="'.pSQL($table).'"');
            if (!$exists) { return; }
            $cur = (int)\Db::getInstance()->getValue('SELECT COUNT(*) FROM `'.$table.'` WHERE id_manufacturer='.(int)$idManufacturer);
            if ($cur == 0) {
                \Db::getInstance()->execute('INSERT INTO `'.$table.'` (id_manufacturer) VALUES ('.(int)$idManufacturer.')');
            }
        } catch (\Throwable $e) { /* silent */ }
    }

}
