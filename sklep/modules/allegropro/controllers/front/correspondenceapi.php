<?php
/**
 * ALLEGRO PRO - Korespondencja (API dla front-app)
 *
 * Endpoint do synchronizacji i listowania danych z bazy:
 * - threads (Message Center): /messaging/threads
 * - issues (Dyskusje/Reklamacje): /sale/issues (beta)
 *
 * Dostęp chroniony tym samym bridge podpisem co app (eid/ts/ttl/sig).
 */

use AllegroPro\Repository\AccountRepository;
use AllegroPro\Repository\MsgThreadRepository;
use AllegroPro\Repository\MsgMessageRepository;
use AllegroPro\Repository\IssueRepository;
use AllegroPro\Repository\IssueChatRepository;
use AllegroPro\Service\AllegroApiClient;
use AllegroPro\Service\HttpClient;

class AllegroproCorrespondenceapiModuleFrontController extends ModuleFrontController
{
    /** @var bool */
    public $ssl = true;

    public function postProcess()
    {
        $this->display_header = false;
        $this->display_footer = false;

        $eid = (int)Tools::getValue('eid');
        $ts = (int)Tools::getValue('ts');
        $ttl = (int)Tools::getValue('ttl');
        $sig = (string)Tools::getValue('sig');

        if (!isset($this->module) || !method_exists($this->module, 'validateBoBridgeParams')) {
            return $this->jsonError('bridge', 'Missing validateBoBridgeParams');
        }

        if (!$this->module->validateBoBridgeParams($eid, $ts, $ttl, $sig)) {
            return $this->jsonError('bridge', 'Invalid bridge signature');
        }

        $employee = new Employee($eid);
        if (!Validate::isLoadedObject($employee) || !(int)$employee->active) {
            return $this->jsonError('bridge', 'Employee not active');
        }

        // Ensure DB schema (bez reinstalacji)
        if (method_exists($this->module, 'ensureDbSchema')) {
            $this->module->ensureDbSchema();
        }

        $action = (string)Tools::getValue('action');
        if ($action === '') {
            return $this->jsonError('request', 'Missing action');
        }

        try {
            switch ($action) {
                case 'dashboard.stats':
                    return $this->handleStats($action);

                // Synchronizacja ręczna (przycisk)
                case 'threads.sync':
                    return $this->handleThreadsSync($action);

                // Uzupełnianie/segregacja wątków (pola pochodne) – osobny krok,
                // żeby nie trzeba było "wchodzić" w wątki, a jednocześnie nie ryzykować timeoutu w 1 request.
                case 'threads.enrich':
                    return $this->handleThreadsEnrich($action);

                case 'threads.reseg.start':
                    return $this->handleThreadsResegStart($action);

                case 'issues.sync':
                    return $this->handleIssuesSync($action);

                // Synchronizacja automatyczna przy wejściu w Korespondencję
                case 'auto.sync':
                    return $this->handleAutoSync($action);

                // Listy
                case 'threads.list':
                    return $this->handleThreadsList($action);

                case 'issues.list':
                    return $this->handleIssuesList($action);

                // Message Center - rozmowy
                case 'thread.open':
                    return $this->handleThreadOpen($action);

                case 'thread.send':
                    return $this->handleThreadSend($action);

                case 'thread.read':
                    return $this->handleThreadMarkRead($action);

                // Issues - chat
                case 'issue.open':
                    return $this->handleIssueOpen($action);

                case 'issue.send':
                    return $this->handleIssueSend($action);

                default:
                    return $this->jsonError('request', 'Unknown action: ' . $action);
            }
        } catch (\Throwable $e) {
            return $this->jsonError('server', 'Exception: ' . $e->getMessage());
        }
    }

    private function handleStats(string $action)
    {
        $threadsRepo = new MsgThreadRepository();
        $issuesRepo = new IssueRepository();

        $t = $threadsRepo->counts();
        $i = $issuesRepo->counts();

        $data = [
            'badges' => array_merge($t, $i),
        ];
        return $this->jsonOk($action, $data);
    }

    private function handleAutoSync(string $action)
    {
        // scope: all | messages | issues
        $scope = (string)Tools::getValue('scope');
        if (!in_array($scope, ['all', 'messages', 'issues'], true)) {
            $scope = 'all';
        }

        $data = [
            'scope' => $scope,
            'threads' => null,
            'issues' => null,
        ];

        if ($scope === 'all' || $scope === 'messages') {
            $data['threads'] = $this->syncThreadsDelta();
        }
        if ($scope === 'all' || $scope === 'issues') {
            $data['issues'] = $this->syncIssuesDelta();
        }

        return $this->jsonOk($action, $data);
    }

    private function handleThreadsSync(string $action)
    {
        $data = $this->syncThreadsDelta();
        return $this->jsonOk($action, $data);
    }

    /**
     * Dociąga metadane wiadomości dla wątków, aby filtry były gotowe "od razu".
     * Ten endpoint można odpalić wielokrotnie (batch), aż pending=0.
     */
    private function handleThreadsEnrich(string $action)
    {
        $accountsRepo = new AccountRepository();
        $accounts = $accountsRepo->all();

        // Zakres miesięcy (konfiguracja modułu)
        $months = (int)Configuration::get('ALLEGROPRO_CORR_MSG_MONTHS');
        if ($months < 1) $months = 6;
        if ($months > 60) $months = 60;
        $cutoffTs = strtotime('-' . $months . ' months');
        $cutoffMysql = $cutoffTs ? date('Y-m-d H:i:s', $cutoffTs) : null;

        // Ile wątków przetwarzać w 1 wywołaniu
        $limit = (int)Tools::getValue('limit');
        if ($limit <= 0) {
            $limit = (int)Configuration::get('ALLEGROPRO_CORR_PREFETCH_THREADS');
        }
        if ($limit < 0) $limit = 0;
        if ($limit > 5000) $limit = 5000;

        // Tryb serwisowy: re-segregacja już pobranych wątków (naprawa / migracja).
        // Używamy tego tylko na żądanie (przycisk "Segreguj"), żeby nie obciążać API przy każdym wejściu.
        $force = (int)Tools::getValue('force') === 1;

        // Pobieramy paczkę wiadomości na wątek (domyślnie 20 ostatnich),
        // żeby zbudować poprawne filtry: wymaga odpowiedzi / dot. zamówienia / dot. oferty / załączniki.
        $msgLimit = 20;

        $data = $this->enrichThreadsBatch($accounts, $cutoffMysql, $limit, $msgLimit, $force);
        $data['months'] = $months;
        $data['limit'] = $limit;
        $data['force'] = $force ? 1 : 0;

        return $this->jsonOk($action, $data);
    }

    

    /**
     * Tryb serwisowy: pełna re-segregacja wszystkich już pobranych wątków (w ramach cutoff miesięcy).
     * Resetuje pola pochodne (need_reply/załączniki/last_*) aby threads.enrich mogło przeliczyć wszystko od nowa.
     *
     * UI odpala to raz, a potem wykonuje kolejne batch’e threads.enrich aż pending=0.
     */
    private function handleThreadsResegStart(string $action)
    {
        $accountsRepo = new AccountRepository();
        $accounts = $accountsRepo->all();

        $threadsRepo = new MsgThreadRepository();

        // Zakres miesięcy (konfiguracja modułu)
        $months = (int)Configuration::get('ALLEGROPRO_CORR_MSG_MONTHS');
        if ($months < 1) $months = 6;
        if ($months > 60) $months = 60;
        $cutoffTs = strtotime('-' . $months . ' months');
        $cutoffMysql = $cutoffTs ? date('Y-m-d H:i:s', $cutoffTs) : null;

        $total = 0;
        $reset = 0;
        $perAccount = [];

        foreach ($accounts as $acc) {
            if ((int)($acc['active'] ?? 0) !== 1) {
                continue;
            }
            $accId = (int)($acc['id_allegropro_account'] ?? 0);
            if ($accId <= 0) {
                continue;
            }

            $cnt = $threadsRepo->countSyncedInRange($accId, $cutoffMysql);
            $total += $cnt;

            $resetCnt = $threadsRepo->resetDerivedForAccount($accId, $cutoffMysql);
            $reset += $resetCnt;

            $perAccount[] = [
                'account' => $accId,
                'total' => $cnt,
                'reset' => $resetCnt,
            ];
        }

        // Po resecie pending == total (w ramach cutoff), ale liczymy realnie żeby UI miało pewność.
        $pending = 0;
        foreach ($accounts as $acc) {
            if ((int)($acc['active'] ?? 0) !== 1) {
                continue;
            }
            $accId = (int)($acc['id_allegropro_account'] ?? 0);
            if ($accId <= 0) {
                continue;
            }
            $pending += $threadsRepo->countPrefetchPending($accId, $cutoffMysql);
        }

        return $this->jsonOk($action, [
            'months' => $months,
            'total' => $total,
            'pending' => $pending,
            'reset' => $reset,
            'per_account' => $perAccount,
        ]);
    }

private function handleIssuesSync(string $action)
    {
        $data = $this->syncIssuesDelta();

        // Naprawczo uzupełniamy pola pochodne z payload_json (np. currentState.status),
        // bez dodatkowych requestów do Allegro. Dzięki temu filtry (W trakcie / Zamknięte / Do odpowiedzi)
        // działają poprawnie nawet dla rekordów zapisanych starszym kodem.
        try {
            $months = (int)Configuration::get('ALLEGROPRO_CORR_ISSUE_MONTHS');
            if ($months < 1) {
                $months = 12;
            }
            if ($months > 60) {
                $months = 60;
            }
            $cutoffTs = strtotime('-' . $months . ' months');
            $cutoffMysql = $cutoffTs ? date('Y-m-d H:i:s', $cutoffTs) : null;

            $issuesRepo = new IssueRepository();
            $data['enrich'] = $issuesRepo->enrichMissingFromPayload($cutoffMysql, 5000);
        } catch (\Throwable $e) {
            $data['enrich_error'] = $e->getMessage();
        }

        return $this->jsonOk($action, $data);
    }

    /**
     * Synchronizacja przyrostowa (delta) wątków /messaging/threads:
     * - ograniczamy po cutoff (ostatnie X miesięcy)
     * - nie ściągamy całego okresu przy każdym wejściu: zatrzymujemy paging, gdy lastMessageDateTime < MAX(last_message_at) z DB
     */
    private function syncThreadsDelta(): array
    {
        $accountsRepo = new AccountRepository();
        $http = new HttpClient(60, 20);
        $api = new AllegroApiClient($http, $accountsRepo);
        $threadsRepo = new MsgThreadRepository();

        $accounts = $accountsRepo->all();
        if (!is_array($accounts)) {
            $accounts = [];
        }
        $totalFetched = 0;
        $totalUpserted = 0;
        $errors = [];
        // Segregacja (pola pochodne) – wykonywana po synchronizacji listy wątków.
        // Dzięki temu filtry po lewej stronie działają "od razu" po wejściu.
        $enrichStats = null;

        // Ograniczenie synchronizacji do ostatnich X miesięcy (ustawienia modułu)
        $months = (int)Configuration::get('ALLEGROPRO_CORR_MSG_MONTHS');
        if ($months < 1) {
            $months = 6;
        }
        if ($months > 60) {
            $months = 60;
        }
        $cutoffTs = strtotime('-' . $months . ' months');
        $cutoffMysql = $cutoffTs ? date('Y-m-d H:i:s', $cutoffTs) : null;

        foreach ($accounts as $acc) {
            if ((int)($acc['active'] ?? 0) !== 1) {
                continue;
            }
            $accId = (int)($acc['id_allegropro_account'] ?? 0);
            if ($accId <= 0) {
                continue;
            }

            // Delta cursor: MAX(last_message_at) z DB (w ramach cutoff)
            $localMaxTs = $threadsRepo->getAccountMaxLastMessageTs($accId, $cutoffMysql);

            $offset = 0;
            // UWAGA: /messaging/threads obsługuje max limit=20
            $limit = 20;
            $pages = 0;

            while ($pages < 50) { // bezpiecznik (1000 wątków / konto)
                $resp = $api->get($acc, '/messaging/threads', [
                    'limit' => $limit,
                    'offset' => $offset,
                ]);

                if (empty($resp['ok']) || !is_array($resp['json'])) {
                    $msg = 'threads sync failed';
                    $userMsg = null;
                    if (is_array($resp['json']) && isset($resp['json']['errors'][0]) && is_array($resp['json']['errors'][0])) {
                        $msg = (string)($resp['json']['errors'][0]['message'] ?? $msg);
                        $userMsg = (string)($resp['json']['errors'][0]['userMessage'] ?? '');
                        if ($userMsg === '') {
                            $userMsg = null;
                        }
                    }
                    $errors[] = [
                        'account' => $accId,
                        'code' => (int)($resp['code'] ?? 0),
                        'error' => $msg,
                        'userMessage' => $userMsg,
                    ];
                    break;
                }

                $threads = $resp['json']['threads'] ?? [];
                if (!is_array($threads)) {
                    $threads = [];
                }

                $countThis = count($threads);
                if ($countThis === 0) {
                    break;
                }

                $totalFetched += $countThis;

                $stop = false;
                $stoppedBy = null;

                foreach ($threads as $thread) {
                    if (!is_array($thread)) {
                        continue;
                    }

                    $lastIso = (string)($thread['lastMessageDateTime'] ?? '');
                    $lastTs = $lastIso !== '' ? strtotime($lastIso) : 0;

                    // 1) cutoff (ustawienia miesięcy)
                    if ($lastTs > 0 && $cutoffTs && $lastTs < $cutoffTs) {
                        $stop = true;
                        $stoppedBy = 'cutoff';
                        break;
                    }

                    // 2) delta: jeśli zeszliśmy poniżej lokalnego MAX(last_message_at), kończymy paging
                    // (stosujemy <, żeby jeszcze przetworzyć ewentualne rekordy z tym samym timestampem)
                    if ($lastTs > 0 && $localMaxTs > 0 && $lastTs < $localMaxTs) {
                        $stop = true;
                        $stoppedBy = 'delta';
                        break;
                    }

                    if ($threadsRepo->upsertFromApi($accId, $thread)) {
                        $totalUpserted++;
                    }
                }

                if ($stop) {
                    break;
                }

                if ($countThis < $limit) {
                    break;
                }

                $offset += $limit;
                $pages++;
            }
        }

        // === Segregacja wątków (batch) ===
        $enrichLimit = (int)Configuration::get('ALLEGROPRO_CORR_PREFETCH_THREADS');
        if ($enrichLimit < 0) $enrichLimit = 0;
        if ($enrichLimit > 5000) $enrichLimit = 5000;
        if ($enrichLimit > 0) {
            // Pobieramy minimalną paczkę wiadomości na wątek (1 ostatnia wiadomość).
            // To wystarcza do: need_reply, relacje, załączniki.
            $enrichStats = $this->enrichThreadsBatch($accounts, $cutoffMysql, $enrichLimit, 20, false);
        } else {
            $enrichStats = [
                'processed_threads' => 0,
                'fetched' => 0,
                'upserted' => 0,
                'pending' => 0,
                'errors' => [],
            ];
        }

        return [
            'mode' => 'delta',
            'months' => $months,
            'fetched' => $totalFetched,
            'upserted' => $totalUpserted,
            'errors' => $errors,
            'enrich' => $enrichStats,
        ];
    }

    /**
     * Uzupełnia pola pochodne dla wątków (segregacja) na podstawie ostatnich wiadomości (paczka).
     *
     * Robimy to jako "batch" (limit), bo przy dużej liczbie wątków nie chcemy ryzykować timeoutu.
     * Metoda jest idempotentna w trybie standardowym: działa tylko na wątkach, gdzie derived_updated_at jest puste lub starsze niż last_message_at.
     * W trybie serwisowym (force=1) obejmuje też wątki bez relacji (checkout_form_id/offer_id), żeby można było naprawić/migrować dane.
     */
    private function enrichThreadsBatch(array $accounts, ?string $cutoffMysql, int $limit, int $messagesLimit = 20, bool $forceRebuild = false): array
    {
        $limit = max(0, min(5000, (int)$limit));
        $messagesLimit = max(1, min(20, (int)$messagesLimit));

        // Bezpiecznik czasowy – hosting potrafi mieć niski max_execution_time.
        // W praktyce wolimy przerwać batch i dokończyć kolejnym requestem, niż złapać 500.
        $startedAt = microtime(true);
        $timeBudgetSec = 8.0;

        $accountsRepo = new AccountRepository();
        $http = new HttpClient(60, 20);
        $api = new AllegroApiClient($http, $accountsRepo);
        $threadsRepo = new MsgThreadRepository();

        $processedThreads = 0;
        $totalFetched = 0;
        $totalUpserted = 0;
        $errors = [];

        // Kolejkujemy najnowsze wątki jako pierwsze (order by last_message_at desc w repo).
        $remaining = $limit;

        foreach ($accounts as $acc) {
            if ($remaining <= 0) {
                break;
            }
            if ((int)($acc['active'] ?? 0) !== 1) {
                continue;
            }
            $accId = (int)($acc['id_allegropro_account'] ?? 0);
            if ($accId <= 0) {
                continue;
            }

            $candidates = $forceRebuild
                ? $threadsRepo->listPrefetchCandidatesForce($accId, $cutoffMysql, $remaining)
                : $threadsRepo->listPrefetchCandidates($accId, $cutoffMysql, $remaining);
            if (!$candidates) {
                continue;
            }

            foreach ($candidates as $row) {
                if ($remaining <= 0) {
                    break;
                }

                if ((microtime(true) - $startedAt) > $timeBudgetSec) {
                    // kończymy batch – UI/JS może odpalić kolejny krok
                    $remaining = 0;
                    break;
                }

                $tid = trim((string)($row['thread_id'] ?? ''));
                if ($tid === '') {
                    continue;
                }

                // Pobieramy minimalny wycinek wiadomości (domyślnie 1 ostatnia),
                // zapisujemy do DB i przeliczamy pola pochodne wątku.
                // W trybie segregacji wymuszamy pobranie paczki wiadomości (forceFull=true),
                // aby nie zatrzymać się na lokalnym MAX(created_at) i nie przegapić wiadomości klienta.
                // maxPagesOverride=1 => tylko 1 request per wątek (ostatnie N wiadomości).
                $r = $this->syncThreadMessagesDelta($acc, $accId, $tid, true, 1, $messagesLimit);
                $totalFetched += (int)($r['fetched'] ?? 0);
                $totalUpserted += (int)($r['upserted'] ?? 0);
                $processedThreads++;
                $remaining--;

                if (!empty($r['errors']) && is_array($r['errors'])) {
                    foreach ($r['errors'] as $e) {
                        $errors[] = $e;
                    }
                }
            }
        }

        // pending – ile jeszcze zostało do segregacji
        $pending = 0;
        foreach ($accounts as $acc) {
            if ((int)($acc['active'] ?? 0) !== 1) {
                continue;
            }
            $accId = (int)($acc['id_allegropro_account'] ?? 0);
            if ($accId <= 0) {
                continue;
            }
            $pending += $forceRebuild
                ? $threadsRepo->countPrefetchPendingForce($accId, $cutoffMysql)
                : $threadsRepo->countPrefetchPending($accId, $cutoffMysql);
        }

        return [
            'processed_threads' => $processedThreads,
            'fetched' => $totalFetched,
            'upserted' => $totalUpserted,
            'pending' => $pending,
            'errors' => $errors,
        ];
    }

    /**
     * Synchronizacja przyrostowa (delta) issues /sale/issues:
     * - ograniczamy po cutoff (ostatnie X miesięcy)
     * - kończymy paging gdy aktywność issue (chat.lastMessage.createdAt / updatedAt / openedDate) < MAX aktywności z DB
     */
    private function syncIssuesDelta(): array
    {
        $accountsRepo = new AccountRepository();
        $http = new HttpClient(60, 20);
        $api = new AllegroApiClient($http, $accountsRepo);
        $issuesRepo = new IssueRepository();

        $accounts = $accountsRepo->all();
        $totalFetched = 0;
        $totalUpserted = 0;
        $errors = [];

        // Ograniczenie synchronizacji do ostatnich X miesięcy (ustawienia modułu)
        $months = (int)Configuration::get('ALLEGROPRO_CORR_ISSUE_MONTHS');
        if ($months < 1) {
            $months = 12;
        }
        if ($months > 60) {
            $months = 60;
        }
        $cutoffTs = strtotime('-' . $months . ' months');
        $cutoffMysql = $cutoffTs ? date('Y-m-d H:i:s', $cutoffTs) : null;

        foreach ($accounts as $acc) {
            if ((int)($acc['active'] ?? 0) !== 1) {
                continue;
            }
            $accId = (int)($acc['id_allegropro_account'] ?? 0);
            if ($accId <= 0) {
                continue;
            }

            // Delta cursor: MAX(COALESCE(last_message_at, updated_at_allegro, created_at_allegro))
            $localMaxTs = $issuesRepo->getAccountMaxActivityTs($accId, $cutoffMysql);

            $offset = 0;
            $limit = 100;
            $pages = 0;

            while ($pages < 20) {
                $resp = $api->get($acc, '/sale/issues', [
                    'limit' => $limit,
                    'offset' => $offset,
                ], 'application/vnd.allegro.beta.v1+json');

                if (empty($resp['ok']) || !is_array($resp['json'])) {
                    $errors[] = [
                        'account' => $accId,
                        'code' => (int)($resp['code'] ?? 0),
                        'error' => 'issues sync failed',
                    ];
                    break;
                }

                $issues = $resp['json']['issues'] ?? [];
                if (!is_array($issues)) {
                    $issues = [];
                }

                $countThis = count($issues);
                if ($countThis === 0) {
                    break;
                }

                $totalFetched += $countThis;

                $stop = false;

                foreach ($issues as $issue) {
                    if (!is_array($issue)) {
                        continue;
                    }

                    // Aktywność: preferujemy chat.lastMessage.createdAt, potem updatedAt/createdAt/openedDate
                    $activityIso = '';
                    if (isset($issue['chat']['lastMessage']['createdAt'])) {
                        $activityIso = (string)$issue['chat']['lastMessage']['createdAt'];
                    }
                    if ($activityIso === '') {
                        $activityIso = (string)($issue['updatedAt'] ?? ($issue['createdAt'] ?? ($issue['openedDate'] ?? '')));
                    }

                    $activityTs = $activityIso !== '' ? strtotime($activityIso) : 0;

                    // 1) cutoff miesięcy
                    if ($activityTs > 0 && $cutoffTs && $activityTs < $cutoffTs) {
                        $stop = true;
                        break;
                    }

                    // 2) delta
                    if ($activityTs > 0 && $localMaxTs > 0 && $activityTs < $localMaxTs) {
                        $stop = true;
                        break;
                    }

                    if ($issuesRepo->upsertFromApi($accId, $issue)) {
                        $totalUpserted++;
                    }
                }

                if ($stop) {
                    break;
                }

                if ($countThis < $limit) {
                    break;
                }

                $offset += $limit;
                $pages++;
            }
        }

        return [
            'mode' => 'delta',
            'months' => $months,
            'fetched' => $totalFetched,
            'upserted' => $totalUpserted,
            'errors' => $errors,
        ];
    }

    private function handleThreadsList(string $action)
    {
        $filter = (string)Tools::getValue('filter');
        $q = (string)Tools::getValue('q');
        $limit = (int)Tools::getValue('limit');
        $offset = (int)Tools::getValue('offset');

        $repo = new MsgThreadRepository();
        $res = $repo->list($filter, $q, $limit ?: 50, $offset);

        return $this->jsonOk($action, $res['data'], $res['meta']);
    }

    private function handleIssuesList(string $action)
    {
        $filter = (string)Tools::getValue('filter');
        $q = (string)Tools::getValue('q');
        $limit = (int)Tools::getValue('limit');
        $offset = (int)Tools::getValue('offset');

        $repo = new IssueRepository();
        $res = $repo->list($filter, $q, $limit ?: 50, $offset);

        return $this->jsonOk($action, $res['data'], $res['meta']);
    }

    /**
     * Otwiera wątek Message Center:
     * - przyrostowo synchronizuje wiadomości dla wątku
     * - zwraca listę wiadomości (do wyświetlenia w UI)
     */
    private function handleThreadOpen(string $action)
    {
        $accountId = (int)Tools::getValue('account_id');
        $threadId = (string)Tools::getValue('thread_id');
        $threadId = trim($threadId);

        if ($accountId <= 0 || $threadId === '') {
            return $this->jsonError('request', 'Missing account_id/thread_id');
        }

        $accountsRepo = new AccountRepository();
        $acc = $accountsRepo->get($accountId);
        if (!$acc || (int)($acc['active'] ?? 0) !== 1) {
            return $this->jsonError('request', 'Account not found/active');
        }

        $threadsRepo = new MsgThreadRepository();
        $thread = $threadsRepo->getOne($accountId, $threadId);

        // Zakres miesięcy (konfiguracja modułu)
        $months = (int)Configuration::get('ALLEGROPRO_CORR_MSG_MONTHS');
        if ($months < 1) {
            $months = 6;
        }
        if ($months > 60) {
            $months = 60;
        }

        // Jeśli wątek nie ma oznaczenia "pełna historia w zakresie" – robimy backfill.
        // To rozwiązuje sytuację, gdy w DB były tylko fragmenty (np. Twoje automatyczne wiadomości),
        // a brakowało wypowiedzi klienta / Twoich ręcznych odpowiedzi.
        $needsFull = true;
        if (is_array($thread)) {
            $isComplete = (int)($thread['messages_sync_complete'] ?? 0) === 1;
            $completeMonths = (int)($thread['messages_sync_months'] ?? 0);
            $needsFull = !($isComplete && $completeMonths === $months);
        }

        $sync = $this->syncThreadMessagesDelta($acc, $accountId, $threadId, $needsFull);

        // Jeśli wykonaliśmy backfill i dojechaliśmy do końca / cutoff – oznacz wątek jako kompletny.
        if ($needsFull && !empty($sync['complete'])) {
            $threadsRepo->setMessagesSyncComplete($accountId, $threadId, true, $months);
        }

        // odśwież dane wątku po sync
        $thread = $threadsRepo->getOne($accountId, $threadId);

        $msgRepo = new MsgMessageRepository();
        // zawsze pokazujemy "ogon" – najświeższe wiadomości (żeby nie wylądować na pierwszych z historii)
        $messages = $msgRepo->listTailByThread($accountId, $threadId, 2000);

        return $this->jsonOk($action, [
            'thread' => $thread,
            'messages' => $messages,
            'sync' => $sync,
        ]);
    }

    /**
     * Wysyła odpowiedź w wątku Message Center.
     */
    private function handleThreadSend(string $action)
    {
        $accountId = (int)Tools::getValue('account_id');
        $threadId = (string)Tools::getValue('thread_id');
        $text = (string)Tools::getValue('text');

        $threadId = trim($threadId);
        $text = trim($text);

        if ($accountId <= 0 || $threadId === '') {
            return $this->jsonError('request', 'Missing account_id/thread_id');
        }
        if ($text === '') {
            return $this->jsonError('request', 'Empty message');
        }
        // Allegro: limit tekstu w Message Center to 2000 znaków.
        if (Tools::strlen($text) > 2000) {
            return $this->jsonError('request', 'Message too long');
        }

        $accountsRepo = new AccountRepository();
        $acc = $accountsRepo->get($accountId);
        if (!$acc || (int)($acc['active'] ?? 0) !== 1) {
            return $this->jsonError('request', 'Account not found/active');
        }

        $http = new HttpClient(60, 20);
        $api = new AllegroApiClient($http, $accountsRepo);

        $resp = $api->postJson($acc, '/messaging/threads/' . rawurlencode($threadId) . '/messages', [
            'text' => $text,
        ]);

        if (empty($resp['ok'])) {
            $msg = 'Send failed';
            if (is_array($resp['json']) && isset($resp['json']['errors'][0]) && is_array($resp['json']['errors'][0])) {
                $msg = (string)($resp['json']['errors'][0]['message'] ?? $msg);
            }
            return $this->jsonError('allegro', $msg, [
                'code' => (int)($resp['code'] ?? 0),
            ]);
        }

        // Spróbuj zapisać zwróconą wiadomość (jeśli API zwraca message obiekt)
        $msgRepo = new MsgMessageRepository();
        if (is_array($resp['json']) && !empty($resp['json']['id'])) {
            $msgRepo->upsertFromApi($accountId, $threadId, $resp['json']);
        }

        // Po wysyłce dociągamy delta, żeby mieć pewność że DB ma aktualny stan
        $sync = $this->syncThreadMessagesDelta($acc, $accountId, $threadId, false);

        $messages = $msgRepo->listTailByThread($accountId, $threadId, 2000);

        // Po wysłaniu zazwyczaj wątek staje się "przeczytany" po stronie sprzedawcy
        $threadsRepo = new MsgThreadRepository();
        $threadsRepo->setRead($accountId, $threadId, true);

        return $this->jsonOk($action, [
            'sent' => true,
            'sync' => $sync,
            'messages' => $messages,
        ]);
    }

    /**
     * Oznacza wątek jako przeczytany po stronie Allegro oraz w DB.
     */
    private function handleThreadMarkRead(string $action)
    {
        $accountId = (int)Tools::getValue('account_id');
        $threadId = (string)Tools::getValue('thread_id');
        $threadId = trim($threadId);

        if ($accountId <= 0 || $threadId === '') {
            return $this->jsonError('request', 'Missing account_id/thread_id');
        }

        $accountsRepo = new AccountRepository();
        $acc = $accountsRepo->get($accountId);
        if (!$acc || (int)($acc['active'] ?? 0) !== 1) {
            return $this->jsonError('request', 'Account not found/active');
        }

        $http = new HttpClient(60, 20);
        $api = new AllegroApiClient($http, $accountsRepo);

        // Wg dokumentacji endpoint wymaga payloadu { read: true }
        $resp = $api->putJson($acc, '/messaging/threads/' . rawurlencode($threadId) . '/read', [
            'read' => true,
        ]);
        if (empty($resp['ok'])) {
            $msg = 'Mark read failed';
            if (is_array($resp['json']) && isset($resp['json']['errors'][0]) && is_array($resp['json']['errors'][0])) {
                $msg = (string)($resp['json']['errors'][0]['message'] ?? $msg);
            }
            return $this->jsonError('allegro', $msg, [
                'code' => (int)($resp['code'] ?? 0),
            ]);
        }

        $threadsRepo = new MsgThreadRepository();
        $threadsRepo->setRead($accountId, $threadId, true);

        return $this->jsonOk($action, [
            'read' => true,
        ]);
    }

    /**
     * Delta sync wiadomości w konkretnym wątku (Message Center).
     */
    

    /**
     * Otwiera zgłoszenie (issue):
     * - przyrostowo synchronizuje chat dla issue
     * - zwraca dane issue + wiadomości chatu
     */
    private function handleIssueOpen(string $action)
    {
        $accountId = (int)Tools::getValue('account_id');
        $issueId = trim((string)Tools::getValue('issue_id'));

        if ($accountId <= 0 || $issueId === '') {
            return $this->jsonError('request', 'Missing account_id/issue_id');
        }

        $accountsRepo = new AccountRepository();
        $acc = $accountsRepo->get($accountId);
        if (!$acc || (int)($acc['active'] ?? 0) !== 1) {
            return $this->jsonError('request', 'Account not found/active');
        }

        $sync = $this->syncIssueChatDelta($acc, $accountId, $issueId);

        // Status issue potrafi zmienić się "systemowo" (np. zamknięcie przez Kupującego)
        // bez jednoznacznej aktualizacji lastMessage. Dla pewności dociągamy szczegóły issue.
        $detailSync = $this->syncIssueDetails($acc, $accountId, $issueId);

        $issuesRepo = new IssueRepository();
        // getOne() dodajemy w repozytorium (w zipie). Dla bezpieczeństwa: fallback.
        $issue = null;
        if (method_exists($issuesRepo, 'getOne')) {
            $issue = $issuesRepo->getOne($accountId, $issueId);
        }

        $chatRepo = new IssueChatRepository();
        $chat = $chatRepo->listByIssue($accountId, $issueId, 500, 0);

        return $this->jsonOk($action, [
            'issue' => $issue ?: ['issue_id' => $issueId, 'id_allegropro_account' => $accountId],
            'chat' => $chat,
            'sync' => [
                'chat' => $sync,
                'issue' => $detailSync,
            ],
        ]);
    }

    /**
     * Dociąga szczegóły pojedynczego issue i aktualizuje rekord w bazie.
     * Używamy tego np. gdy status zmienił się bez "lastMessage".
     */
    private function syncIssueDetails(array $account, int $accountId, string $issueId): array
    {
        $accountsRepo = new AccountRepository();
        $http = new HttpClient(60, 20);
        $api = new AllegroApiClient($http, $accountsRepo);
        $issuesRepo = new IssueRepository();

        $resp = $api->get($account, '/sale/issues/' . rawurlencode($issueId), [], 'application/vnd.allegro.beta.v1+json');
        if (empty($resp['ok']) || !is_array($resp['json'])) {
            $msg = 'issue details sync failed';
            if (is_array($resp['json']) && isset($resp['json']['errors'][0]) && is_array($resp['json']['errors'][0])) {
                $msg = (string)($resp['json']['errors'][0]['message'] ?? $msg);
            }
            return [
                'ok' => false,
                'code' => (int)($resp['code'] ?? 0),
                'error' => $msg,
            ];
        }

        $payload = $resp['json'];
        if (isset($payload['issue']) && is_array($payload['issue'])) {
            $payload = $payload['issue'];
        }

        if (!is_array($payload) || empty($payload['id'])) {
            return [
                'ok' => false,
                'code' => (int)($resp['code'] ?? 0),
                'error' => 'issue details: invalid payload',
            ];
        }

        $issuesRepo->upsertFromApi($accountId, $payload);

        return [
            'ok' => true,
            'code' => (int)($resp['code'] ?? 200),
        ];
    }

    /**
     * Wysyła odpowiedź w issue (dyskusja/reklamacja).
     * Endpoint beta: POST /sale/issues/{issueId}/message
     */
    private function handleIssueSend(string $action)
    {
        $accountId = (int)Tools::getValue('account_id');
        $issueId = trim((string)Tools::getValue('issue_id'));
        $text = trim((string)Tools::getValue('text'));

        if ($accountId <= 0 || $issueId === '') {
            return $this->jsonError('request', 'Missing account_id/issue_id');
        }
        if ($text === '') {
            return $this->jsonError('request', 'Empty message');
        }
        if (Tools::strlen($text) > 8000) {
            return $this->jsonError('request', 'Message too long');
        }

        $accountsRepo = new AccountRepository();
        $acc = $accountsRepo->get($accountId);
        if (!$acc || (int)($acc['active'] ?? 0) !== 1) {
            return $this->jsonError('request', 'Account not found/active');
        }

        $http = new HttpClient(60, 20);
        $api = new AllegroApiClient($http, $accountsRepo);

        $resp = $api->postJson($acc, '/sale/issues/' . rawurlencode($issueId) . '/message', [
            'text' => $text,
            'attachments' => [],
            'type' => 'REGULAR',
        ], [
            'Accept' => 'application/vnd.allegro.beta.v1+json',
            'Content-Type' => 'application/vnd.allegro.beta.v1+json',
        ]);

        if (empty($resp['ok'])) {
            $msg = 'Send failed';
            if (is_array($resp['json']) && isset($resp['json']['errors'][0]) && is_array($resp['json']['errors'][0])) {
                $msg = (string)($resp['json']['errors'][0]['message'] ?? $msg);
            }
            return $this->jsonError('allegro', $msg, [
                'code' => (int)($resp['code'] ?? 0),
            ]);
        }

        $chatRepo = new IssueChatRepository();
        if (is_array($resp['json']) && !empty($resp['json']['id'])) {
            $chatRepo->upsertFromApi($accountId, $issueId, $resp['json']);
        }

        $sync = $this->syncIssueChatDelta($acc, $accountId, $issueId);
        $chat = $chatRepo->listByIssue($accountId, $issueId, 500, 0);

        return $this->jsonOk($action, [
            'sent' => true,
            'sync' => $sync,
            'chat' => $chat,
        ]);
    }

    /**
     * Delta sync chatu issues (/sale/issues/{issueId}/chat)
     */
    private function syncIssueChatDelta(array $account, int $accountId, string $issueId): array
    {
        $accountsRepo = new AccountRepository();
        $http = new HttpClient(60, 20);
        $api = new AllegroApiClient($http, $accountsRepo);
        $chatRepo = new IssueChatRepository();

        // Cutoff z ustawień (ostatnie X miesięcy)
        $months = (int)Configuration::get('ALLEGROPRO_CORR_ISSUE_MONTHS');
        if ($months < 1) {
            $months = 12;
        }
        if ($months > 60) {
            $months = 60;
        }
        $cutoffTs = strtotime('-' . $months . ' months');
        $cutoffMysql = $cutoffTs ? date('Y-m-d H:i:s', $cutoffTs) : null;

        $localMaxTs = $chatRepo->getIssueMaxCreatedTs($accountId, $issueId, $cutoffMysql);

        $offset = 0;
        $limit = 100;
        $pages = 0;
        $fetched = 0;
        $upserted = 0;
        $errors = [];

        $isDesc = null;

        while ($pages < 20) {
            $resp = $api->get($account, '/sale/issues/' . rawurlencode($issueId) . '/chat', [
                'limit' => $limit,
                'offset' => $offset,
            ], 'application/vnd.allegro.beta.v1+json');

            if (empty($resp['ok']) || !is_array($resp['json'])) {
                $msg = 'issue chat sync failed';
                if (is_array($resp['json']) && isset($resp['json']['errors'][0]) && is_array($resp['json']['errors'][0])) {
                    $msg = (string)($resp['json']['errors'][0]['message'] ?? $msg);
                }
                $errors[] = [
                    'account' => $accountId,
                    'issue_id' => $issueId,
                    'code' => (int)($resp['code'] ?? 0),
                    'error' => $msg,
                ];
                break;
            }

            // API /sale/issues/{issueId}/chat zwraca listę w kluczu "chat" (beta).
            $messages = $resp['json']['chat'] ?? $resp['json']['messages'] ?? $resp['json']['items'] ?? null;
            if (!is_array($messages)) {
                // fallback: czasem API może zwrócić tablicę bez klucza
                if (array_keys($resp['json']) === range(0, count($resp['json']) - 1)) {
                    $messages = $resp['json'];
                } else {
                    $messages = [];
                }
            }

            $countThis = count($messages);
            if ($countThis === 0) {
                break;
            }

            $fetched += $countThis;

            if ($isDesc === null && $countThis >= 2) {
                $firstIso = (string)($messages[0]['createdAt'] ?? ($messages[0]['createdAtDateTime'] ?? ''));
                $lastIso = (string)($messages[$countThis - 1]['createdAt'] ?? ($messages[$countThis - 1]['createdAtDateTime'] ?? ''));
                $firstTs = $firstIso !== '' ? strtotime($firstIso) : 0;
                $lastTs = $lastIso !== '' ? strtotime($lastIso) : 0;
                if ($firstTs > 0 && $lastTs > 0) {
                    $isDesc = ($firstTs >= $lastTs);
                }
            }

            $stop = false;

            foreach ($messages as $m) {
                if (!is_array($m)) {
                    continue;
                }

                $createdIso = (string)($m['createdAt'] ?? ($m['createdAtDateTime'] ?? ''));
                $createdTs = $createdIso !== '' ? strtotime($createdIso) : 0;

                // cutoff
                if ($createdTs > 0 && $cutoffTs && $createdTs < $cutoffTs) {
                    if ($isDesc === true) {
                        $stop = true;
                        break;
                    }
                    continue;
                }

                // delta (stop tylko przy desc)
                if ($createdTs > 0 && $localMaxTs > 0 && $createdTs < $localMaxTs) {
                    if ($isDesc === true) {
                        $stop = true;
                        break;
                    }
                    continue;
                }

                if ($chatRepo->upsertFromApi($accountId, $issueId, $m)) {
                    $upserted++;
                }
            }

            if ($stop) {
                break;
            }

            if ($countThis < $limit) {
                break;
            }

            $offset += $limit;
            $pages++;
        }

        return [
            'mode' => 'delta',
            'months' => $months,
            'fetched' => $fetched,
            'upserted' => $upserted,
            'errors' => $errors,
        ];
    }

    private function syncThreadMessagesDelta(array $account, int $accountId, string $threadId, bool $forceFull = false, int $maxPagesOverride = 0, ?int $messagesLimitOverride = null): array
    {
        $accountsRepo = new AccountRepository();
        $http = new HttpClient(60, 20);
        $api = new AllegroApiClient($http, $accountsRepo);
        $msgRepo = new MsgMessageRepository();
        $threadsRepo = new MsgThreadRepository();

        // Cutoff z ustawień (ostatnie X miesięcy)
        $months = (int)Configuration::get('ALLEGROPRO_CORR_MSG_MONTHS');
        if ($months < 1) {
            $months = 6;
        }
        if ($months > 60) {
            $months = 60;
        }
        $cutoffTs = strtotime('-' . $months . ' months');
        $cutoffMysql = $cutoffTs ? date('Y-m-d H:i:s', $cutoffTs) : null;

        $localMaxTs = $forceFull ? 0 : $msgRepo->getThreadMaxCreatedTs($accountId, $threadId, $cutoffMysql);

        $offset = 0;
        // /messaging/threads/{threadId}/messages obsługuje max limit=20
        $limit = 20;
        if ($messagesLimitOverride !== null) {
            $limit = (int)$messagesLimitOverride;
            if ($limit < 1) {
                $limit = 1;
            }
            if ($limit > 20) {
                $limit = 20;
            }
        }
        $pages = 0;
        $maxPages = $forceFull ? 200 : 30;
        if ($maxPagesOverride > 0) {
            $maxPages = max(1, min(200, (int)$maxPagesOverride));
        }
        $fetched = 0;
        $upserted = 0;
        $errors = [];

        $complete = false;
        $stopReason = '';

        // Heurystyka: czy API zwraca messages od najnowszych? (jeśli nie, delta stop nie może działać agresywnie)
        $isDesc = null;

        // relacje (wyciągamy po drodze, a potem uzupełniamy wątek)
        $foundCheckout = null;
        $foundOffer = null;

        while ($pages < $maxPages) {
            $resp = $api->get($account, '/messaging/threads/' . rawurlencode($threadId) . '/messages', [
                'limit' => $limit,
                'offset' => $offset,
            ]);

            if (empty($resp['ok']) || !is_array($resp['json'])) {
                $msg = 'thread messages sync failed';
                if (is_array($resp['json']) && isset($resp['json']['errors'][0]) && is_array($resp['json']['errors'][0])) {
                    $msg = (string)($resp['json']['errors'][0]['message'] ?? $msg);
                }
                $errors[] = [
                    'account' => $accountId,
                    'thread_id' => $threadId,
                    'code' => (int)($resp['code'] ?? 0),
                    'error' => $msg,
                ];
                break;
            }

            $messages = $resp['json']['messages'] ?? $resp['json']['items'] ?? null;
            if (!is_array($messages)) {
                // czasem API może zwrócić tablicę bez klucza (fallback)
                if (array_keys($resp['json']) === range(0, count($resp['json']) - 1)) {
                    $messages = $resp['json'];
                } else {
                    $messages = [];
                }
            }

            $countThis = count($messages);
            if ($countThis === 0) {
                break;
            }

            $fetched += $countThis;

            if ($isDesc === null && $countThis >= 2) {
                $firstIso = (string)($messages[0]['createdAt'] ?? ($messages[0]['createdAtDateTime'] ?? ''));
                $lastIso = (string)($messages[$countThis - 1]['createdAt'] ?? ($messages[$countThis - 1]['createdAtDateTime'] ?? ''));
                $firstTs = $firstIso !== '' ? strtotime($firstIso) : 0;
                $lastTs = $lastIso !== '' ? strtotime($lastIso) : 0;
                if ($firstTs > 0 && $lastTs > 0) {
                    $isDesc = ($firstTs >= $lastTs);
                }
            }

            $stop = false;

            foreach ($messages as $m) {
                if (!is_array($m)) {
                    continue;
                }

                $createdIso = (string)($m['createdAt'] ?? ($m['createdAtDateTime'] ?? ''));
                $createdTs = $createdIso !== '' ? strtotime($createdIso) : 0;

                // cutoff (ostatnie X miesięcy)
                if ($createdTs > 0 && $cutoffTs && $createdTs < $cutoffTs) {
                    if ($isDesc === true) {
                        $stop = true;
                        $stopReason = 'cutoff';
                        $complete = $forceFull ? true : false;
                        break;
                    }
                    // jeśli order nie jest "desc", nie stopujemy (żeby nie uciąć nowszych stron)
                    continue;
                }

                // delta - stop tylko przy desc i tylko jeśli NIE robimy pełnego backfill.
                // Uwaga: używamy < (a nie <=), żeby nie ucinać strony na pierwszym rekordzie o tej samej dacie.
                if (!$forceFull && $createdTs > 0 && $localMaxTs > 0 && $createdTs < $localMaxTs) {
                    if ($isDesc === true) {
                        $stop = true;
                        $stopReason = 'delta';
                        break;
                    }
                    continue;
                }

                if ($msgRepo->upsertFromApi($accountId, $threadId, $m)) {
                    $upserted++;
                }

                // relacje do zamówienia/oferty (z messages)
                if (!$foundCheckout && isset($m['relatesTo']['order']['id'])) {
                    $foundCheckout = (string)$m['relatesTo']['order']['id'];
                }
                if (!$foundOffer && isset($m['relatesTo']['offer']['id'])) {
                    $foundOffer = (string)$m['relatesTo']['offer']['id'];
                }
                // fallbacki
                if (!$foundCheckout && isset($m['checkoutForm']['id'])) {
                    $foundCheckout = (string)$m['checkoutForm']['id'];
                }
                if (!$foundOffer && isset($m['offer']['id'])) {
                    $foundOffer = (string)$m['offer']['id'];
                }
            }

            if ($foundCheckout || $foundOffer) {
                $threadsRepo->setRelationsIfEmpty($accountId, $threadId, $foundCheckout, $foundOffer);
            }

            if ($stop) {
                break;
            }

            if ($countThis < $limit) {
                if ($forceFull) {
                    $complete = true;
                    $stopReason = 'end';
                }
                break;
            }

            $offset += $limit;
            $pages++;
        }

        // Aktualizacja pól pochodnych (need_reply / has_attachments / last_*).
        // Robimy tylko jeśli mamy jakiekolwiek wiadomości w DB dla wątku.
        $maxAfter = $msgRepo->getThreadMaxCreatedTs($accountId, $threadId, $cutoffMysql);
        if ($maxAfter > 0) {
            $threadsRepo->recomputeDerivedStats($accountId, $threadId, $cutoffMysql);
        }

        return [
            'mode' => $forceFull ? 'full' : 'delta',
            'months' => $months,
            'fetched' => $fetched,
            'upserted' => $upserted,
            'errors' => $errors,
            'complete' => $complete,
            'stopReason' => $stopReason,
            'pageLimit' => $maxPages,
        ];
    }

    private function jsonOk(string $action, array $data = [], array $meta = [])
    {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'ok' => true,
            'action' => $action,
            'data' => $data,
            'meta' => $meta,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    private function jsonError(string $type, string $message, array $extra = [])
    {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code(400);
        echo json_encode([
            'ok' => false,
            'error' => [
                'type' => $type,
                'message' => $message,
            ] + $extra,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
}
