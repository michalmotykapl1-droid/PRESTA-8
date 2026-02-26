<?php
if (!defined('_PS_VERSION_')) { exit; }

class AdminGpsrProducersController extends ModuleAdminController
{
    public function __construct()
    {
        $this->bootstrap = true;
        parent::__construct();
    }

    public function postProcess()
    {
        if (Tools::isSubmit('save_producer')) {
            $id = (int)Tools::getValue('id_gpsr_producer');
            $data = [
                'name'    => pSQL(Tools::getValue('name')),
                'alias'   => pSQL(Tools::getValue('alias')),
                'country' => pSQL(Tools::getValue('country')),
                'address' => pSQL(Tools::getValue('address')),
                'postcode'=> pSQL(Tools::getValue('postcode')),
                'city'    => pSQL(Tools::getValue('city')),
                'email'   => pSQL(Tools::getValue('email')),
                'phone'   => pSQL(Tools::getValue('phone')),
                'info'    => pSQL(Tools::getValue('info'), true),
                'active'  => (int)Tools::getValue('active', 1),
            ];
            if ($id) { Db::getInstance()->update('gpsr_producer', $data, 'id_gpsr_producer='.(int)$id); }
            else { Db::getInstance()->insert('gpsr_producer', $data); }
        }
        if (Tools::isSubmit('delete') && ($id=(int)Tools::getValue('id'))) {
            Db::getInstance()->delete('gpsr_producer', 'id_gpsr_producer='.(int)$id);
        }
    }

    public function initContent()
    {
        parent::initContent();
        $editId = (int)Tools::getValue('edit');
        if ($editId) {
            $row = Db::getInstance()->getRow('SELECT * FROM '._DB_PREFIX_.'gpsr_producer WHERE id_gpsr_producer='.(int)$editId) ?: [];
            $this->context->smarty->assign(['row'=>$row]);
            $this->setTemplate('producers_form.tpl');
        } else {
            $rows = Db::getInstance()->executeS('SELECT * FROM '._DB_PREFIX_.'gpsr_producer ORDER BY name ASC') ?: [];
            $this->context->smarty->assign(['rows'=>$rows, 'self_link'=>self::$currentIndex.'&token='.$this->token]);
            $this->setTemplate('producers_list.tpl');
        }
    }

    public function ajaxProcessLookupContact()
    {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $name = trim((string)Tools::getValue('name'));
            if ($name === '') { echo json_encode(['ok'=>false,'error'=>'Brak nazwy producenta']); exit; }
            if ($name === '__ping__') { echo json_encode(['ok'=>true,'results'=>[['url'=>'/','email'=>'test@local','phone'=>'+48123456789']]]); exit; }

            $provider = (string)Configuration::get('GPSR_LOOKUP_PROVIDER');
            $serpKey  = (string)Configuration::get('GPSR_SERPAPI_KEY');

            $urls = [];

            if ($provider === 'serpapi' && $serpKey) {
                $q = rawurlencode($name.' kontakt producent email telefon');
                $url = "https://serpapi.com/search.json?engine=google&q={$q}&google_domain=google.pl&hl=pl&num=10&api_key=".rawurlencode($serpKey);
                $resp = $this->httpGet($url);
                if ($resp) {
                    $j = json_decode($resp, true);
                    if (!empty($j['organic_results'])) {
                        foreach ($j['organic_results'] as $r) {
                            if (!empty($r['link'])) { $urls[] = $r['link']; }
                        }
                    }
                }
            }

            if ((empty($urls)) && ($provider === 'duckduckgo' || $provider === '')) {
                $q = rawurlencode($name.' kontakt producent email telefon');
                $url = "https://duckduckgo.com/html/?q={$q}&kl=pl-pl";
                $html = $this->httpGet($url);
                if ($html && preg_match_all('#<a[^>]+class=\\"result__a\\"[^>]+href=\\"([^\\"]+)\\"#i', $html, $m)) {
                    foreach ($m[1] as $u) { $urls[] = html_entity_decode($u, ENT_QUOTES, 'UTF-8'); }
                }
            }

            $found = [];
            foreach (array_slice(array_unique($urls), 0, 5) as $u) {
                $html = $this->httpGet($u, 12);
                if (!$html) continue;
                $email = $this->firstEmail($html);
                $phone = $this->firstPhone($html);
                if ($email || $phone) { $found[] = ['url'=>$u,'email'=>$email,'phone'=>$phone]; }
            }

            if (empty($found)) { echo json_encode(['ok'=>false,'error'=>'Nie znaleziono kontaktu']); exit; }
            echo json_encode(['ok'=>true,'results'=>$found]); exit;
        } catch (Exception $e) {
            echo json_encode(['ok'=>false,'error'=>'Wyjątek: '.$e->getMessage()]); exit;
        }
    }

    protected function httpGet($url, $timeout=8)
    {
        if (function_exists('curl_init')) {
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_TIMEOUT => $timeout,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_USERAGENT => 'Mozilla/5.0 (X11; Linux x86_64) GPSRFetcher/1.0',
            ]);
            $resp = curl_exec($ch);
            curl_close($ch);
            if ($resp !== false) { return $resp; }
        }
        $ctx = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => "User-Agent: Mozilla/5.0 (X11; Linux x86_64) GPSRFetcher/1.0\r\n",
                'timeout' => $timeout,
            ],
            'ssl' => ['verify_peer'=>false,'verify_peer_name'=>false],
        ]);
        return @file_get_contents($url, false, $ctx);
    }

    protected function firstEmail($html)
    {
        if (preg_match('/mailto:([^"\\s]+)/i', $html, $m)) {
            return filter_var($m[1], FILTER_SANITIZE_EMAIL);
        }
        if (preg_match('/[A-Z0-9._%+-]+@[A-Z0-9.-]+\\.[A-Z]{2,}/i', $html, $m)) {
            return $m[0];
        }
        return null;
    }

    protected function firstPhone($html)
    {
        if (preg_match('/\\+?\\d[\\d\\s\\-()]{6,}/', strip_tags($html), $m)) {
            return trim($m[0]);
        }
        return null;
    }
}
