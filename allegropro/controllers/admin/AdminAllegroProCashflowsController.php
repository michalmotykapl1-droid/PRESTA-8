<?php
/**
 * ALLEGRO PRO - Przepływy środków (payment-operations)
 *
 * Etap 3 (fundament): podgląd operacji płatniczych Allegro (Allegro Finanse)
 * Źródło: GET /payments/payment-operations
 */

use AllegroPro\Repository\AccountRepository;
use AllegroPro\Repository\CashflowsTransactionsRepository;
use AllegroPro\Repository\PaymentOperationRepository;
use AllegroPro\Repository\PaymentOperationAggregateRepository;
use AllegroPro\Service\HttpClient;
use AllegroPro\Service\AllegroApiClient;
use AllegroPro\Service\PaymentOperationSyncService;

class AdminAllegroProCashflowsController extends ModuleAdminController
{
    private AccountRepository $accounts;
    private PaymentOperationRepository $ops;
    private CashflowsTransactionsRepository $tx;
    private PaymentOperationAggregateRepository $opAgg;

    public function __construct()
    {
        parent::__construct();
        $this->bootstrap = true;
        $this->accounts = new AccountRepository();
        $this->ops = new PaymentOperationRepository();
        $this->tx = new CashflowsTransactionsRepository();
        $this->opAgg = new PaymentOperationAggregateRepository();
    }

    public function setMedia($isNewTheme = false)
    {
        parent::setMedia($isNewTheme);
        if (!empty($this->module)) {
            $cssLocal = $this->module->getLocalPath() . 'views/css/cashflows.css';
            $jsLocal = $this->module->getLocalPath() . 'views/js/cashflows.js';
            if (is_file($cssLocal)) {
                $this->addCSS($this->module->getPathUri() . 'views/css/cashflows.css');
            }
            if (is_file($jsLocal)) {
                $this->addJS($this->module->getPathUri() . 'views/js/cashflows.js');
            }
        }
    }

    public function initContent()
    {
        parent::initContent();

        // UWAGA: AJAX jest obsługiwany natywnie przez ModuleAdminController:
        // ?ajax=1&action=syncCashflowsChunk → ajaxProcessSyncCashflowsChunk()

        if (isset($this->module) && method_exists($this->module, 'ensureTabs')) {
            $this->module->ensureTabs();
        }

        $accounts = $this->accounts->all();
        $selectedAccountId = (int)Tools::getValue('id_allegropro_account', 0);
        if ($selectedAccountId <= 0 && !empty($accounts)) {
            $selectedAccountId = (int)($accounts[0]['id_allegropro_account'] ?? 0);
        }

        $dateFrom = (string)Tools::getValue('date_from', '');
        $dateTo = (string)Tools::getValue('date_to', '');
        if (!$dateFrom || !$dateTo) {
            $dateFrom = date('Y-m-01');
            $dateTo = date('Y-m-d');
        }
        $dateFrom = $this->sanitizeYmd($dateFrom) ?: date('Y-m-01');
        $dateTo = $this->sanitizeYmd($dateTo) ?: date('Y-m-d');

        $walletType = (string)Tools::getValue('wallet_type', '');
        $walletType = in_array($walletType, ['', 'AVAILABLE', 'WAITING'], true) ? $walletType : '';

        $walletOperator = (string)Tools::getValue('wallet_payment_operator', '');
        $allowedOps = ['', 'PAYU', 'P24', 'AF', 'AF_PAYU', 'AF_P24'];
        $walletOperator = in_array($walletOperator, $allowedOps, true) ? $walletOperator : '';

        $group = (string)Tools::getValue('group', '');
        $allowedGroups = ['', 'INCOME', 'OUTCOME', 'REFUND', 'BLOCKADES'];
        $group = in_array($group, $allowedGroups, true) ? $group : '';

        $paymentId = trim((string)Tools::getValue('payment_id', ''));
        $paymentId = $this->sanitizeId($paymentId);

        $participantLogin = trim((string)Tools::getValue('participant_login', ''));
        $participantLogin = $this->sanitizeLogin($participantLogin);

        $limit = (int)Tools::getValue('limit', 50);
        $limit = in_array($limit, [25, 50, 100, 200], true) ? $limit : 50;

        $view = (string)Tools::getValue('view', 'tx');
        $view = in_array($view, ['tx', 'raw', 'payout'], true) ? $view : 'tx';

        $syncMode = (string)Tools::getValue('sync_mode', 'fill');
        $syncMode = in_array($syncMode, ['fill', 'full'], true) ? $syncMode : 'fill';

        $page = (int)Tools::getValue('page', 1);
        $page = max(1, $page);
        $offset = ($page - 1) * $limit;

        $baseUrl = $this->context->link->getAdminLink('AdminAllegroProCashflows');
        $queryParams = [
            'id_allegropro_account' => $selectedAccountId,
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'wallet_type' => $walletType,
            'wallet_payment_operator' => $walletOperator,
            'group' => $group,
            'payment_id' => $paymentId,
            'participant_login' => $participantLogin,
            'limit' => $limit,
            'sync_mode' => $syncMode,
            'view' => $view,
        ];

        // Flash msg po synchronizacji (PRG)
        $syncFlash = null;
        try {
            $rawFlash = (string)($this->context->cookie->__get('alpro_cashflows_flash') ?? '');
            if ($rawFlash !== '') {
                $syncFlash = json_decode($rawFlash, true);
                $this->context->cookie->__set('alpro_cashflows_flash', '');
                $this->context->cookie->write();
            }
        } catch (\Throwable $e) {
            $syncFlash = null;
        }

        // Export CSV (z cache DB)
        if ((int)Tools::getValue('export_cashflows', 0) === 1) {
            $this->exportCashflowsCsvDb($selectedAccountId, $dateFrom, $dateTo, $walletType, $walletOperator, $group, $paymentId, $participantLogin);
            exit;
        }
        if ((int)Tools::getValue('export_tx', 0) === 1) {
            $this->exportTransactionsCsv($selectedAccountId, $dateFrom, $dateTo, $participantLogin, $paymentId);
            exit;
        }
        if ((int)Tools::getValue('export_payout', 0) === 1) {
            $this->exportPayoutsCsvDb($selectedAccountId, $dateFrom, $dateTo, $walletType, $walletOperator);
            exit;
        }

        $rows = [];
        $apiMeta = ['ok' => false, 'code' => 0, 'count' => 0, 'totalCount' => 0, 'error' => ''];
        $kpi = ['total' => 0.0, 'pos' => 0.0, 'neg' => 0.0, 'count' => 0];

        // Widok transakcyjny (per checkoutFormId)
        $txRows = [];
        $txMeta = ['ok' => false, 'count' => 0, 'totalCount' => 0, 'error' => ''];
        $txKpi = [
            'count' => 0,
            'sum_expected' => 0.0,
            'sum_waiting' => 0.0,
            'sum_available' => 0.0,
            'ok' => 0,
            'missing' => 0,
            'diff' => 0,
            'waiting_only' => 0,
        ];

        // Widok wypłat (PAYOUT)
        $payoutRows = [];
        $payoutMeta = ['ok' => false, 'count' => 0, 'totalCount' => 0, 'error' => ''];
        $payoutKpi = [
            'count' => 0,
            'sum' => 0.0,
            'sum_abs' => 0.0,
        ];
        $payoutDaily = [];

        $account = $this->accounts->get($selectedAccountId);

        // Synchronizacja cache (PRG)
        if ((int)Tools::getValue('sync_cashflows', 0) === 1) {
            if (is_array($account) && !empty($account['access_token'])) {
                $api = new AllegroApiClient(new HttpClient(), $this->accounts);
                $sync = new PaymentOperationSyncService($api, $this->ops);
                $stats = $sync->sync($account, $dateFrom, $dateTo, $syncMode, false);

                if (!empty($stats['ok'])) {
                    \Configuration::updateValue('ALLEGROPRO_CASHFLOWS_LASTSYNC_' . (int)$selectedAccountId, date('Y-m-d H:i:s'));
                }

                try {
                    $this->context->cookie->__set('alpro_cashflows_flash', json_encode($stats));
                    $this->context->cookie->write();
                } catch (\Throwable $e) {
                    // ignore
                }
            } else {
                try {
                    $this->context->cookie->__set('alpro_cashflows_flash', json_encode(['ok' => false, 'error' => 'Brak aktywnego konta / tokena.']));
                    $this->context->cookie->write();
                } catch (\Throwable $e) {
                    // ignore
                }
            }

            \Tools::redirectAdmin($baseUrl . '&' . http_build_query(array_merge($queryParams, ['page' => 1])));
            exit;
        }

        // Lista + KPI z DB cache
        if ($view === 'raw') {
            if ($this->ops->ensureSchema()) {
                $filters = [
                    'wallet_type' => $walletType,
                    'wallet_payment_operator' => $walletOperator,
                    'group' => $group,
                    'payment_id' => $paymentId,
                    'participant_login' => $participantLogin,
                ];

                $totalCount = $this->ops->countTotal($selectedAccountId, $dateFrom, $dateTo, $filters);
                $rows = $this->ops->findPage($selectedAccountId, $dateFrom, $dateTo, $filters, $limit, $offset);
                $kpi = $this->ops->kpiTotal($selectedAccountId, $dateFrom, $dateTo, $filters);

                $apiMeta['ok'] = true;
                $apiMeta['count'] = count($rows);
                $apiMeta['totalCount'] = (int)$totalCount;
            } else {
                $apiMeta['ok'] = false;
                $apiMeta['error'] = 'Nie udało się utworzyć / odczytać tabeli cache allegropro_payment_operation.';
            }
        } elseif ($view === 'payout') {
            if ($this->ops->ensureSchema()) {
                // Payout = OUTCOME/PAYOUT
                $filtersPayout = [
                    'wallet_type' => $walletType,
                    'wallet_payment_operator' => $walletOperator,
                    'group' => 'OUTCOME',
                    'type' => 'PAYOUT',
                ];

                $totalCount = $this->ops->countTotal($selectedAccountId, $dateFrom, $dateTo, $filtersPayout);
                $payoutRows = $this->ops->findPage($selectedAccountId, $dateFrom, $dateTo, $filtersPayout, $limit, $offset);
                $k = $this->ops->kpiTotal($selectedAccountId, $dateFrom, $dateTo, $filtersPayout);

                $payoutMeta['ok'] = true;
                $payoutMeta['count'] = count($payoutRows);
                $payoutMeta['totalCount'] = (int)$totalCount;
                $payoutKpi['count'] = (int)($k['count'] ?? 0);
                $payoutKpi['sum'] = (float)($k['total'] ?? 0);
                $payoutKpi['sum_abs'] = abs((float)($k['total'] ?? 0));
                $payoutDaily = $this->ops->dailySummary($selectedAccountId, $dateFrom, $dateTo, $filtersPayout);
            } else {
                $payoutMeta['ok'] = false;
                $payoutMeta['error'] = 'Nie udało się utworzyć / odczytać tabeli cache allegropro_payment_operation.';
            }
        } else {
            // Widok „Wpłaty transakcji” bazuje na: allegropro_order_payment + cashflow cache
            if ($this->ops->ensureSchema()) {
                $filtersTx = [
                    'buyer_login' => $participantLogin,
                    'payment_id' => $paymentId,
                ];
                $totalTx = $this->tx->countCheckoutForms($selectedAccountId, $dateFrom, $dateTo, $filtersTx);
                $pageTx = $this->tx->findCheckoutFormsPage($selectedAccountId, $dateFrom, $dateTo, $filtersTx, $limit, $offset);

                $cfIds = [];
                foreach ($pageTx as $r) {
                    if (!empty($r['checkout_form_id'])) {
                        $cfIds[] = (string)$r['checkout_form_id'];
                    }
                }

                $paymentsByCf = $this->tx->getPaymentsForCheckoutForms($cfIds);

                $allPaymentIds = [];
                foreach ($paymentsByCf as $cf => $plist) {
                    foreach ($plist as $p) {
                        $pid = (string)($p['payment_id'] ?? '');
                        if ($pid !== '') {
                            $allPaymentIds[$pid] = true;
                        }
                    }
                }
                $allPaymentIds = array_keys($allPaymentIds);

                $aggByPayment = $this->opAgg->sumContributionsByPaymentIds($selectedAccountId, $allPaymentIds);

                foreach ($pageTx as $r) {
                    $cf = (string)($r['checkout_form_id'] ?? '');
                    $payments = $paymentsByCf[$cf] ?? [];

                    $expected = (float)($r['paid_amount'] ?? 0);
                    $sumWaiting = 0.0;
                    $sumAvailable = 0.0;
                    $payDetails = [];

                    foreach ($payments as $p) {
                        $pid = (string)($p['payment_id'] ?? '');
                        $expP = (float)($p['paid_amount'] ?? 0);
                        $a = $aggByPayment[$pid] ?? ['available' => 0.0, 'waiting' => 0.0, 'total' => 0.0, 'first_at' => null, 'last_at' => null];
                        $sumWaiting += (float)$a['waiting'];
                        $sumAvailable += (float)$a['available'];

                        $payDetails[] = [
                            'payment_id' => $pid,
                            'expected' => $expP,
                            'status' => (string)($p['payment_status'] ?? ''),
                            'provider' => (string)($p['provider'] ?? ''),
                            'finished_at' => (string)($p['finished_at'] ?? ''),
                            'waiting' => (float)$a['waiting'],
                            'available' => (float)$a['available'],
                            'total' => (float)$a['total'],
                            'first_at' => $a['first_at'],
                            'last_at' => $a['last_at'],
                        ];
                    }

                    $gotTotal = $sumWaiting + $sumAvailable;
                    $status = 'diff';
                    $tol = 0.01;
                    if ($gotTotal <= $tol) {
                        $status = 'missing';
                    } elseif (abs($gotTotal - $expected) <= $tol) {
                        $status = 'ok';
                    } elseif ($sumAvailable <= $tol && $sumWaiting > $tol) {
                        $status = 'waiting_only';
                    } else {
                        $status = 'diff';
                    }

                    $txRows[] = [
                        'checkout_form_id' => $cf,
                        'id_order_prestashop' => (int)($r['id_order_prestashop'] ?? 0),
                        'buyer_login' => (string)($r['buyer_login'] ?? ''),
                        'finished_at' => (string)($r['finished_at'] ?? ''),
                        'expected' => $expected,
                        'waiting' => $sumWaiting,
                        'available' => $sumAvailable,
                        'total' => $gotTotal,
                        'currency' => (string)($r['currency'] ?? 'PLN'),
                        'status' => $status,
                        'payments' => $payDetails,
                    ];

                    $txKpi['count']++;
                    $txKpi['sum_expected'] += $expected;
                    $txKpi['sum_waiting'] += $sumWaiting;
                    $txKpi['sum_available'] += $sumAvailable;
                    if ($status === 'ok') $txKpi['ok']++;
                    elseif ($status === 'missing') $txKpi['missing']++;
                    elseif ($status === 'waiting_only') $txKpi['waiting_only']++;
                    else $txKpi['diff']++;
                }

                $txMeta['ok'] = true;
                $txMeta['count'] = count($txRows);
                $txMeta['totalCount'] = (int)$totalTx;
            } else {
                $txMeta['ok'] = false;
                $txMeta['error'] = 'Brak tabeli cache allegropro_payment_operation. Kliknij Synchronizuj.';
            }
        }

        
        $totalPages = 0;
        $totalCountForPager = ($view === 'raw') ? (int)$apiMeta['totalCount'] : (($view === 'payout') ? (int)$payoutMeta['totalCount'] : (int)$txMeta['totalCount']);
        if ($totalCountForPager > 0) {
            $totalPages = (int)ceil($totalCountForPager / $limit);
        }
        $prevPage = max(1, $page - 1);
        $nextPage = ($totalPages > 0) ? min($totalPages, $page + 1) : ($page + 1);

        $prevUrl = $baseUrl . '&' . http_build_query(array_merge($queryParams, ['page' => $prevPage]));
        $nextUrl = $baseUrl . '&' . http_build_query(array_merge($queryParams, ['page' => $nextPage]));
        $viewTxUrl = $baseUrl . '&' . http_build_query(array_merge($queryParams, ['view' => 'tx', 'page' => 1]));
        $viewRawUrl = $baseUrl . '&' . http_build_query(array_merge($queryParams, ['view' => 'raw', 'page' => 1]));
        $viewPayoutUrl = $baseUrl . '&' . http_build_query(array_merge($queryParams, ['view' => 'payout', 'page' => 1]));
        $lastSync = (string)\Configuration::get('ALLEGROPRO_CASHFLOWS_LASTSYNC_' . (int)$selectedAccountId);
        if ($lastSync === '') {
            $lastSync = null;
        }

        $syncUrl = $baseUrl . '&sync_cashflows=1&' . http_build_query(array_merge($queryParams, ['page' => 1]));

        // AJAX URL do chunk-sync (pobieranie partiami, bez limitu z UI)
        // Parametry (konto/datki/tryb) idą w POST z JS, żeby brać aktualne wartości z formularza.
        $ajaxSyncUrl = $baseUrl . '&ajax=1&action=syncCashflowsChunk';

        $adminOrdersLink = $this->context->link->getAdminLink('AdminOrders');

        $this->context->smarty->assign([
            'accounts' => $accounts,
            'selected_account_id' => $selectedAccountId,
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'wallet_type' => $walletType,
            'wallet_payment_operator' => $walletOperator,
            'group' => $group,
            'payment_id' => $paymentId,
            'participant_login' => $participantLogin,
            'limit' => $limit,
            'sync_mode' => $syncMode,
            'view' => $view,
            'page' => $page,
            'offset' => $offset,
            'rows' => $rows,
            'api' => $apiMeta,
            'kpi' => $kpi,
            'tx_rows' => $txRows,
            'tx_api' => $txMeta,
            'tx_kpi' => $txKpi,
            'base_url' => $baseUrl,
            'token' => $this->token,
            'query_params' => $queryParams,
            'export_url' => $baseUrl . '&export_cashflows=1&' . http_build_query($queryParams),
            'export_tx_url' => $baseUrl . '&export_tx=1&' . http_build_query($queryParams),
            'total_pages' => $totalPages,
            'prev_url' => $prevUrl,
            'next_url' => $nextUrl,
            'view_tx_url' => $viewTxUrl,
            'view_raw_url' => $viewRawUrl,
            'view_payout_url' => $viewPayoutUrl,
            'sync_url' => $syncUrl,
            'ajax_sync_url' => $ajaxSyncUrl,
            'sync_flash' => $syncFlash,
            'last_sync_at' => $lastSync,
            'admin_orders_link' => $adminOrdersLink,

            'payout_rows' => $payoutRows,
            'payout_api' => $payoutMeta,
            'payout_kpi' => $payoutKpi,
            'payout_daily' => $payoutDaily,
            'export_payout_url' => $baseUrl . '&export_payout=1&' . http_build_query($queryParams),
        ]);

        if ($view === 'raw') {
            $this->setTemplate('cashflows.tpl');
        } elseif ($view === 'payout') {
            $this->setTemplate('cashflows_payouts.tpl');
        } else {
            $this->setTemplate('cashflows_transactions.tpl');
        }
    }

    /**
     * Chunked synchronizacja cashflows (payments/payment-operations)
     * Wywoływana AJAX-em w pętli, aby uniknąć limitu i timeoutów.
     */
    public function ajaxProcessSyncCashflowsChunk(): void
    {
        header('Content-Type: application/json; charset=UTF-8');

        $selectedAccountId = (int)Tools::getValue('id_allegropro_account', 0);
        $dateFrom = $this->sanitizeYmd((string)Tools::getValue('date_from', '')) ?: date('Y-m-01');
        $dateTo = $this->sanitizeYmd((string)Tools::getValue('date_to', '')) ?: date('Y-m-d');
        $syncMode = (string)Tools::getValue('sync_mode', 'fill');
        $syncMode = in_array($syncMode, ['fill', 'full'], true) ? $syncMode : 'fill';
        $state = (string)Tools::getValue('state', '');

        $account = $this->accounts->get($selectedAccountId);
        if (!is_array($account) || empty($account['access_token'])) {
            echo json_encode(['ok' => false, 'error' => 'Brak aktywnego konta / tokena.']);
            return;
        }

        $api = new AllegroApiClient(new HttpClient(), $this->accounts);
        $sync = new PaymentOperationSyncService($api, $this->ops);

        // Allegro API dla payment-operations ma restrykcyjny limit per page.
        // Trzymamy bezpiecznie 100, a widok "Na stronę" dotyczy tylko listy w BO.
        $r = $sync->syncChunk($account, $dateFrom, $dateTo, $syncMode, $state, 100);
        if (!empty($r['ok']) && !empty($r['done'])) {
            Configuration::updateValue('ALLEGROPRO_CASHFLOWS_LASTSYNC_' . (int)$selectedAccountId, date('Y-m-d H:i:s'));
            $r['last_sync_at'] = (string)Configuration::get('ALLEGROPRO_CASHFLOWS_LASTSYNC_' . (int)$selectedAccountId);
        }

        echo json_encode($r);
    }

    private function exportTransactionsCsv(int $accountId, string $dateFrom, string $dateTo, string $buyerLogin, string $paymentId): void
    {
        if (!$this->ops->ensureSchema()) {
            header('HTTP/1.1 500 Internal Server Error');
            echo "Brak tabeli cache allegropro_payment_operation.\n";
            return;
        }

        $fname = 'allegro_transactions_' . $dateFrom . '_' . $dateTo . '.csv';
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename=\"' . $fname . '\"');
        echo "\xEF\xBB\xBF";

        $out = fopen('php://output', 'w');
        fputcsv($out, ['finished_at','checkout_form_id','id_order_prestashop','buyer_login','expected_paid','cashflow_waiting','cashflow_available','cashflow_total','status','payment_ids'], ';');

        $filtersTx = ['buyer_login' => $buyerLogin, 'payment_id' => $paymentId];
        $offset = 0;
        $chunk = 500;

        while (true) {
            $pageTx = $this->tx->findCheckoutFormsPage($accountId, $dateFrom, $dateTo, $filtersTx, $chunk, $offset);
            if (empty($pageTx)) {
                break;
            }

            $cfIds = [];
            foreach ($pageTx as $r) {
                if (!empty($r['checkout_form_id'])) {
                    $cfIds[] = (string)$r['checkout_form_id'];
                }
            }
            $paymentsByCf = $this->tx->getPaymentsForCheckoutForms($cfIds);
            $allPaymentIds = [];
            foreach ($paymentsByCf as $plist) {
                foreach ($plist as $p) {
                    $pid = (string)($p['payment_id'] ?? '');
                    if ($pid !== '') $allPaymentIds[$pid] = true;
                }
            }
            $allPaymentIds = array_keys($allPaymentIds);
            $aggByPayment = $this->opAgg->sumContributionsByPaymentIds($accountId, $allPaymentIds);

            foreach ($pageTx as $r) {
                $cf = (string)($r['checkout_form_id'] ?? '');
                $expected = (float)($r['paid_amount'] ?? 0);
                $payments = $paymentsByCf[$cf] ?? [];

                $sumWaiting = 0.0;
                $sumAvailable = 0.0;
                $ids = [];
                foreach ($payments as $p) {
                    $pid = (string)($p['payment_id'] ?? '');
                    if ($pid !== '') {
                        $ids[] = $pid;
                        $a = $aggByPayment[$pid] ?? ['available' => 0.0, 'waiting' => 0.0];
                        $sumWaiting += (float)$a['waiting'];
                        $sumAvailable += (float)$a['available'];
                    }
                }
                $total = $sumWaiting + $sumAvailable;
                $tol = 0.01;
                $status = 'diff';
                if ($total <= $tol) $status = 'missing';
                elseif (abs($total - $expected) <= $tol) $status = 'ok';
                elseif ($sumAvailable <= $tol && $sumWaiting > $tol) $status = 'waiting_only';

                fputcsv($out, [
                    (string)($r['finished_at'] ?? ''),
                    $cf,
                    (string)(int)($r['id_order_prestashop'] ?? 0),
                    (string)($r['buyer_login'] ?? ''),
                    number_format($expected, 2, '.', ''),
                    number_format($sumWaiting, 2, '.', ''),
                    number_format($sumAvailable, 2, '.', ''),
                    number_format($total, 2, '.', ''),
                    $status,
                    implode(',', $ids),
                ], ';');
            }

            $offset += count($pageTx);
            if (count($pageTx) < $chunk) {
                break;
            }
        }

        fclose($out);
    }

    private function exportCashflowsCsvDb(int $accountId, string $dateFrom, string $dateTo, string $walletType, string $walletOperator, string $group, string $paymentId, string $participantLogin): void
    {
        if (!$this->ops->ensureSchema()) {
            header('HTTP/1.1 500 Internal Server Error');
            echo "Brak tabeli cache allegropro_payment_operation.\n";
            return;
        }

        $fname = 'allegro_cashflows_cache_' . $dateFrom . '_' . $dateTo . '.csv';
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $fname . '"');

        // BOM for Excel
        echo "\xEF\xBB\xBF";

        $out = fopen('php://output', 'w');
        fputcsv($out, ['occurredAt','group','type','amount','currency','wallet_type','wallet_payment_operator','participant_login','payment_id'], ';');

        $filters = [
            'wallet_type' => $walletType,
            'wallet_payment_operator' => $walletOperator,
            'group' => $group,
            'payment_id' => $paymentId,
            'participant_login' => $participantLogin,
        ];

        foreach ($this->ops->iterateForCsv($accountId, $dateFrom, $dateTo, $filters) as $r) {
            $row = [
                (string)($r['occurredAt'] ?? ''),
                (string)($r['group'] ?? ''),
                (string)($r['type'] ?? ''),
                (string)($r['amount'] ?? ''),
                (string)($r['currency'] ?? 'PLN'),
                (string)($r['wallet_type'] ?? ''),
                (string)($r['wallet_operator'] ?? ''),
                (string)($r['participant_login'] ?? ''),
                (string)($r['payment_id'] ?? ''),
            ];
            fputcsv($out, $row, ';');
        }
        fclose($out);
    }

    private function exportPayoutsCsvDb(int $accountId, string $dateFrom, string $dateTo, string $walletType, string $walletOperator): void
    {
        if (!$this->ops->ensureSchema()) {
            header('HTTP/1.1 500 Internal Server Error');
            echo "Brak tabeli cache allegropro_payment_operation.\n";
            return;
        }

        $fname = 'allegro_payouts_' . $dateFrom . '_' . $dateTo . '.csv';
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename=\"' . $fname . '\"');
        echo "\xEF\xBB\xBF";

        $out = fopen('php://output', 'w');
        fputcsv($out, ['occurredAt','amount','currency','wallet_type','wallet_payment_operator'], ';');

        $filters = [
            'wallet_type' => $walletType,
            'wallet_payment_operator' => $walletOperator,
            'group' => 'OUTCOME',
            'type' => 'PAYOUT',
        ];

        foreach ($this->ops->iterateForCsv($accountId, $dateFrom, $dateTo, $filters) as $r) {
            fputcsv($out, [
                (string)($r['occurredAt'] ?? ''),
                (string)($r['amount'] ?? ''),
                (string)($r['currency'] ?? 'PLN'),
                (string)($r['wallet_type'] ?? ''),
                (string)($r['wallet_operator'] ?? ''),
            ], ';');
        }
        fclose($out);
    }

    private function sanitizeYmd(string $s): ?string
    {
        $s = trim($s);
        if (preg_match('/^(20\d\d)\-(0[1-9]|1[0-2])\-(0[1-9]|[12]\d|3[01])$/', $s)) {
            return $s;
        }
        return null;
    }

    private function ymdToIsoStart(string $ymd): string
    {
        return $ymd . 'T00:00:00.000Z';
    }

    private function ymdToIsoEnd(string $ymd): string
    {
        return $ymd . 'T23:59:59.999Z';
    }

    private function sanitizeId(string $s): string
    {
        $s = trim($s);
        if ($s === '') return '';
        // allow UUID-like + base64 ids
        if (preg_match('/^[a-zA-Z0-9\-\_\.=]{8,}$/', $s)) {
            return $s;
        }
        return '';
    }

    private function sanitizeLogin(string $s): string
    {
        $s = trim($s);
        if ($s === '') return '';
        $s = preg_replace('/[^a-zA-Z0-9\-\_\.]/', '', $s);
        return (string)$s;
    }
}
