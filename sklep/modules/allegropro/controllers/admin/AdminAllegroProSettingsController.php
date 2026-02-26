<?php
/**
 * ALLEGRO PRO - Back Office controller
 */

use AllegroPro\Service\Config;
use AllegroPro\Service\LabelConfig;
use AllegroPro\Repository\AccountRepository;

class AdminAllegroProSettingsController extends ModuleAdminController
{
    private function getCorrMonths(string $key, int $default, int $min = 1, int $max = 60): int
    {
        $v = (int)Configuration::get($key);
        if ($v < $min) {
            return $default;
        }
        if ($v > $max) {
            return $max;
        }
        return $v;
    }

    public function __construct()
    {
        parent::__construct();
        $this->bootstrap = true;
    }

    public function initContent()
    {
        parent::initContent();

        if (isset($this->module) && method_exists($this->module, 'ensureDbSchema')) {
            $this->module->ensureDbSchema();
        }

        if (isset($this->module) && method_exists($this->module, 'ensureTabs')) {
            $this->module->ensureTabs();
        }
        
        $this->handlePost();

        $redirectUri = $this->context->link->getModuleLink($this->module->name, 'oauthcallback', [], true);
        
        // Pobieramy instancję configu etykiet, by znać aktualne ustawienia do widoku
        $labelConfig = new LabelConfig();

        // Korespondencja - zakres synchronizacji
        $corrMsgMonths = $this->getCorrMonths('ALLEGROPRO_CORR_MSG_MONTHS', 6);
        $corrIssueMonths = $this->getCorrMonths('ALLEGROPRO_CORR_ISSUE_MONTHS', 12);
        // Ile wątków "segregować" (uzupełnić pola pochodne) w 1 synchronizacji.
        // W praktyce warto ustawić 200–500 przy większych kontach.
        $corrPrefetchThreads = (int)Configuration::get('ALLEGROPRO_CORR_PREFETCH_THREADS');
        if ($corrPrefetchThreads < 0) {
            $corrPrefetchThreads = 0;
        }
        if ($corrPrefetchThreads > 5000) {
            $corrPrefetchThreads = 5000;
        }
        $corrMonthsOptions = [1, 2, 3, 6, 12, 18, 24, 36, 48, 60];

        $accRepo = new AccountRepository();
        $accounts = $accRepo->all();
        foreach ($accounts as &$a) {
            $a['shipx_token_set'] = !empty($a['shipx_token']);
        }
        unset($a);

        $this->context->smarty->assign([
            'allegropro_redirect_uri' => $redirectUri,
            'allegropro_env' => Configuration::get('ALLEGROPRO_ENV') ?: 'prod',
            'allegropro_client_id' => Configuration::get('ALLEGROPRO_CLIENT_ID') ?: '',
            'allegropro_client_secret_set' => (bool) Configuration::get('ALLEGROPRO_CLIENT_SECRET'),
            
            // Nowe ustawienia etykiet przekazywane do szablonu
            'allegropro_label_format' => $labelConfig->getFileFormat(),
            'allegropro_label_size' => $labelConfig->getPageSize(),
            
            'allegropro_pkg' => Config::pkgDefaults(),
            'shop_defaults' => [
                'name' => Configuration::get('PS_SHOP_NAME'),
                'addr1' => Configuration::get('PS_SHOP_ADDR1'),
                'code' => Configuration::get('PS_SHOP_CODE'),
                'city' => Configuration::get('PS_SHOP_CITY'),
                'email' => Configuration::get('PS_SHOP_EMAIL'),
                'phone' => Configuration::get('PS_SHOP_PHONE'),
            ],

            // InPost ShipX token (pomocniczo w module)
            'allegropro_shipx_accounts' => $accounts,

            // Korespondencja
            'allegropro_corr_msg_months' => $corrMsgMonths,
            'allegropro_corr_issue_months' => $corrIssueMonths,
            'allegropro_corr_prefetch_threads' => $corrPrefetchThreads,
            'allegropro_corr_months_options' => $corrMonthsOptions,
        ]);

        $this->setTemplate('settings.tpl');
    }

    private function handlePost()
    {
        // Obsługa OAuth
        if (Tools::isSubmit('submitAllegroProOauth')) {
            $env = Tools::getValue('ALLEGROPRO_ENV');
            $clientId = trim((string)Tools::getValue('ALLEGROPRO_CLIENT_ID'));
            $clientSecret = (string)Tools::getValue('ALLEGROPRO_CLIENT_SECRET');

            if (!in_array($env, ['prod', 'sandbox'], true)) {
                $env = 'prod';
            }

            if ($clientId === '') {
                $this->errors[] = $this->l('Client ID jest wymagany.');
                return;
            }

            Configuration::updateValue('ALLEGROPRO_ENV', $env);
            Configuration::updateValue('ALLEGROPRO_CLIENT_ID', $clientId);

            if ($clientSecret !== '') {
                Configuration::updateValue('ALLEGROPRO_CLIENT_SECRET', $clientSecret);
            }

            $this->confirmations[] = $this->l('Ustawienia OAuth zapisane.');
        }


        // Obsługa InPost ShipX token (informacyjnie / do szybkiego wklejenia)
        if (Tools::isSubmit('submitAllegroProShipX')) {
            $token = trim((string)Tools::getValue('ALLEGROPRO_SHIPX_TOKEN'));
            $accountIds = Tools::getValue('ALLEGROPRO_SHIPX_ACCOUNTS');
            $action = Tools::getValue('ALLEGROPRO_SHIPX_ACTION');

            if (!is_array($accountIds)) {
                $accountIds = $accountIds ? [$accountIds] : [];
            }
            $accountIds = array_values(array_filter(array_map('intval', $accountIds)));

            if (empty($accountIds)) {
                $this->errors[] = $this->l('Wybierz co najmniej jedno konto Allegro.');
                return;
            }

            $repo = new AccountRepository();

            if ($action === 'clear') {
                foreach ($accountIds as $idAcc) {
                    $repo->setShipXToken($idAcc, null);
                }
                $this->confirmations[] = $this->l('Token ShipX usunięty z zaznaczonych kont.');
                return;
            }

            if ($token === '') {
                $this->errors[] = $this->l('Wklej token API ShipX (InPost).');
                return;
            }

            foreach ($accountIds as $idAcc) {
                $repo->setShipXToken($idAcc, $token);
            }
            $this->confirmations[] = $this->l('Token ShipX zapisany dla zaznaczonych kont.');
        }

        // Obsługa Ustawień Domyślnych i Etykiet
        if (Tools::isSubmit('submitAllegroProDefaults')) {
            // Zapis ustawień etykiet (Nowe pola)
            Configuration::updateValue('ALLEGROPRO_LABEL_FORMAT', Tools::getValue('ALLEGROPRO_LABEL_FORMAT'));
            Configuration::updateValue('ALLEGROPRO_LABEL_SIZE', Tools::getValue('ALLEGROPRO_LABEL_SIZE'));

            // Zapis domyślnych parametrów paczki
            Configuration::updateValue('ALLEGROPRO_PKG_TYPE', Tools::getValue('ALLEGROPRO_PKG_TYPE') ?: 'OTHER');
            Configuration::updateValue('ALLEGROPRO_PKG_LEN', (int)Tools::getValue('ALLEGROPRO_PKG_LEN'));
            Configuration::updateValue('ALLEGROPRO_PKG_WID', (int)Tools::getValue('ALLEGROPRO_PKG_WID'));
            Configuration::updateValue('ALLEGROPRO_PKG_HEI', (int)Tools::getValue('ALLEGROPRO_PKG_HEI'));
            Configuration::updateValue('ALLEGROPRO_PKG_WGT', (float)Tools::getValue('ALLEGROPRO_PKG_WGT'));
            Configuration::updateValue('ALLEGROPRO_PKG_TEXT', (string)Tools::getValue('ALLEGROPRO_PKG_TEXT'));

            $this->confirmations[] = $this->l('Domyślne parametry przesyłek i etykiet zapisane.');
        }

        // Korespondencja - zakres synchronizacji
        if (Tools::isSubmit('submitAllegroProCorrespondence')) {
            $msgMonths = (int)Tools::getValue('ALLEGROPRO_CORR_MSG_MONTHS');
            $issueMonths = (int)Tools::getValue('ALLEGROPRO_CORR_ISSUE_MONTHS');
            $prefetchThreads = (int)Tools::getValue('ALLEGROPRO_CORR_PREFETCH_THREADS');

            $msgMonths = max(1, min(60, $msgMonths));
            $issueMonths = max(1, min(60, $issueMonths));
            $prefetchThreads = max(0, min(5000, $prefetchThreads));

            Configuration::updateValue('ALLEGROPRO_CORR_MSG_MONTHS', $msgMonths);
            Configuration::updateValue('ALLEGROPRO_CORR_ISSUE_MONTHS', $issueMonths);
            Configuration::updateValue('ALLEGROPRO_CORR_PREFETCH_THREADS', $prefetchThreads);

            $this->confirmations[] = $this->l('Ustawienia korespondencji zapisane.');
        }


        // Korespondencja - czyszczenie danych starszych niż wybrany okres
        if (Tools::isSubmit('submitAllegroProCorrespondencePurge')) {
            $msgMonths = $this->getCorrMonths('ALLEGROPRO_CORR_MSG_MONTHS', 6);
            $issueMonths = $this->getCorrMonths('ALLEGROPRO_CORR_ISSUE_MONTHS', 12);

            $res = $this->purgeCorrespondenceData($msgMonths, $issueMonths);

            $this->confirmations[] = sprintf(
                $this->l('Wyczyszczono dane korespondencji: wątki=%d, wiadomości=%d, zgłoszenia=%d, chat=%d.'),
                (int)$res['threads'],
                (int)$res['messages'],
                (int)$res['issues'],
                (int)$res['issue_chat']
            );
        }
    }

    /**
     * Usuwa stare rekordy z DB (żeby baza nie rosła w nieskończoność).
     * Zakres wynika z ustawień: wiadomości X mies., dyskusje X mies.
     *
     * Zwraca liczbę usuniętych rekordów:
     * - threads: ps_allegropro_msg_thread
     * - messages: ps_allegropro_msg_message
     * - issues: ps_allegropro_issue
     * - issue_chat: ps_allegropro_issue_chat
     */
    private function purgeCorrespondenceData(int $msgMonths, int $issueMonths): array
    {
        $msgMonths = max(1, min(60, $msgMonths));
        $issueMonths = max(1, min(60, $issueMonths));

        $db = Db::getInstance();
        $p = _DB_PREFIX_;

        $cutoffMsg = date('Y-m-d H:i:s', strtotime('-' . $msgMonths . ' months'));
        $cutoffIssue = date('Y-m-d H:i:s', strtotime('-' . $issueMonths . ' months'));

        $deletedThreads = 0;
        $deletedMessages = 0;
        $deletedIssues = 0;
        $deletedIssueChat = 0;

        // 1) Message Center (wątki + wiadomości)
        if ($db->execute("DELETE FROM `{$p}allegropro_msg_thread` WHERE COALESCE(last_message_at, created_at) < '" . pSQL($cutoffMsg) . "'")) {
            $deletedThreads += (int)$db->Affected_Rows();
        }
        if ($db->execute("DELETE FROM `{$p}allegropro_msg_message` WHERE COALESCE(created_at_allegro, created_at) < '" . pSQL($cutoffMsg) . "'")) {
            $deletedMessages += (int)$db->Affected_Rows();
        }
        // Usuwamy osierocone wiadomości (gdy wątek został wyczyszczony)
        if ($db->execute("DELETE m FROM `{$p}allegropro_msg_message` m
            LEFT JOIN `{$p}allegropro_msg_thread` t
                ON t.id_allegropro_account = m.id_allegropro_account AND t.thread_id = m.thread_id
            WHERE t.id_allegropro_msg_thread IS NULL")) {
            $deletedMessages += (int)$db->Affected_Rows();
        }

        // 2) Issues + chat
        if ($db->execute("DELETE FROM `{$p}allegropro_issue` WHERE COALESCE(last_message_at, updated_at_allegro, created_at_allegro, created_at) < '" . pSQL($cutoffIssue) . "'")) {
            $deletedIssues += (int)$db->Affected_Rows();
        }
        // Usuwamy osierocone wpisy chatu (gdy issue zostało wyczyszczone)
        if ($db->execute("DELETE c FROM `{$p}allegropro_issue_chat` c
            LEFT JOIN `{$p}allegropro_issue` i
                ON i.id_allegropro_account = c.id_allegropro_account AND i.issue_id = c.issue_id
            WHERE i.id_allegropro_issue IS NULL")) {
            $deletedIssueChat += (int)$db->Affected_Rows();
        }
        // Dodatkowo czyścimy stare chaty po dacie (na wypadek braku issue_id w DB)
        if ($db->execute("DELETE FROM `{$p}allegropro_issue_chat` WHERE COALESCE(created_at_allegro, created_at) < '" . pSQL($cutoffIssue) . "'")) {
            $deletedIssueChat += (int)$db->Affected_Rows();
        }

        return [
            'threads' => $deletedThreads,
            'messages' => $deletedMessages,
            'issues' => $deletedIssues,
            'issue_chat' => $deletedIssueChat,
        ];
    }


}