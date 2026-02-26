<?php
/**
 * ALLEGRO PRO - Przepływy środków (payment-operations)
 *
 * Etap 3 (fundament): podgląd operacji płatniczych Allegro (Allegro Finanse)
 * Źródło: GET /payments/payment-operations
 */

use AllegroPro\Repository\AccountRepository;
use AllegroPro\Repository\CashflowsTransactionsRepository;
use AllegroPro\Repository\CashflowsReconciliationRepository;
use AllegroPro\Repository\BillingEntryRepository;
use AllegroPro\Repository\CashflowsBillingRepository;
use AllegroPro\Repository\PaymentOperationRepository;
use AllegroPro\Repository\PaymentOperationAggregateRepository;
use AllegroPro\Repository\PaymentOperationReconciliationAggregateRepository;
use AllegroPro\Repository\OrderRepository;
use AllegroPro\Service\HttpClient;
use AllegroPro\Service\AllegroApiClient;
use AllegroPro\Service\PaymentOperationSyncService;
use AllegroPro\Service\BillingEntrySyncService;
use AllegroPro\Service\OrderEnrichSkipService;

class AdminAllegroProCashflowsController extends ModuleAdminController
{
    private AccountRepository $accounts;
    private PaymentOperationRepository $ops;
    private BillingEntryRepository $billingRepo;
    private CashflowsTransactionsRepository $tx;
    private CashflowsReconciliationRepository $recon;
    private CashflowsBillingRepository $billing;
    private PaymentOperationAggregateRepository $opAgg;
    private PaymentOperationReconciliationAggregateRepository $opReconAgg;

    public function __construct()
    {
        parent::__construct();
        $this->bootstrap = true;
        $this->accounts = new AccountRepository();
        $this->ops = new PaymentOperationRepository();
        $this->billingRepo = new BillingEntryRepository();
        $this->tx = new CashflowsTransactionsRepository();
        $this->recon = new CashflowsReconciliationRepository();
        $this->billing = new CashflowsBillingRepository();
        $this->opAgg = new PaymentOperationAggregateRepository();
        $this->opReconAgg = new PaymentOperationReconciliationAggregateRepository();
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
        $view = in_array($view, ['tx', 'raw', 'recon', 'billing'], true) ? $view : 'tx';

        $orderStatus = (string)Tools::getValue('order_status', '');
        $allowedOrderStatuses = ['', 'BOUGHT', 'FILLED_IN', 'READY_FOR_PROCESSING', 'PROCESSING', 'SENT', 'CANCELLED'];
        $orderStatus = in_array($orderStatus, $allowedOrderStatuses, true) ? $orderStatus : '';

        $syncMode = (string)Tools::getValue('sync_mode', 'fill');
        $syncMode = in_array($syncMode, ['fill', 'full'], true) ? $syncMode : 'fill';

        $alert = (string)Tools::getValue('alert', '');
        $allowedAlerts = ['', 'issues', 'unpaid_fees', 'no_refund', 'partial_refund', 'api_error'];
        $alert = in_array($alert, $allowedAlerts, true) ? $alert : '';

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
            'order_status' => $orderStatus,
            'alert' => $alert,
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
        if ((int)Tools::getValue('export_recon', 0) === 1) {
            $this->exportReconciliationCsv($selectedAccountId, $dateFrom, $dateTo, $participantLogin, $paymentId, $orderStatus);
            exit;
        }
        if ((int)Tools::getValue('export_billing', 0) === 1) {
            $this->exportBillingCsv($selectedAccountId, $dateFrom, $dateTo, $participantLogin, $paymentId, $alert);
            exit;
        }

        $rows = [];
        $apiMeta = ['ok' => false, 'code' => 0, 'count' => 0, 'totalCount' => 0, 'page' => $page, 'limit' => $limit, 'offset' => $offset, 'totalPages' => 0, 'error' => ''];
        $kpi = ['total' => 0.0, 'pos' => 0.0, 'neg' => 0.0, 'count' => 0];

        // Widok transakcyjny (per checkoutFormId)
        $txRows = [];
        $txMeta = ['ok' => false, 'count' => 0, 'totalCount' => 0, 'page' => $page, 'limit' => $limit, 'offset' => $offset, 'totalPages' => 0, 'error' => ''];
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

        $account = $this->accounts->get($selectedAccountId);

        // Widok „Rozliczenie / kontrola opłat” (per checkoutFormId)
        $reconRows = [];
        $reconMeta = ['ok' => false, 'count' => 0, 'totalCount' => 0, 'page' => $page, 'limit' => $limit, 'offset' => $offset, 'totalPages' => 0, 'error' => ''];
        $reconKpi = [
            'count' => 0,
            'sum_paid' => 0.0,
            'sum_cashflow' => 0.0,
            'sum_fee' => 0.0,
            'sum_fee_refund' => 0.0,
            'sum_net' => 0.0,
            'issues' => 0,
            // Rozbicie problemów (żeby od razu było wiadomo, *co* się nie zgadza)
            'issues_missing_cashflow' => 0,
            'issues_cashflow_diff' => 0,
            'issues_missing_refund' => 0,
        ];


$reconPayouts = [
    'payout_out' => 0.0,
    'payout_cancel' => 0.0,
    'payout_net' => 0.0,
    'total_count' => 0,
    'shown' => 0,
    'rows' => [],
];

        // Widok „Opłaty (BILLING)” – billing-entries per order_id (checkoutFormId)
        $billingRows = [];
        // Podgląd problemów (max N wierszy) – pokazywany na górze, aby od razu było widać, co się nie zgadza.
        $billingIssueLimit = 20;
                $billingIssueRows = [];
        $billingMeta = ['ok' => false, 'count' => 0, 'totalCount' => 0, 'page' => $page, 'limit' => $limit, 'offset' => $offset, 'totalPages' => 0, 'error' => ''];
        $billingKpi = [
            'orders_count' => 0,
            'fees_abs' => 0.0,
            'refunds' => 0.0,
            'net' => 0.0,
            'issues' => 0,
            'issues_unpaid' => 0,
            'issues_no_refund' => 0,
            'issues_partial_refund' => 0,
            'issues_api_error' => 0,
        ];
        $billingCacheCount = 0;

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
        } elseif ($view === 'tx') {
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
        } elseif ($view === 'billing') {
            // Opłaty (BILLING): dane z billing-entries (cache allegropro_billing_entry)
            try {
                $this->billingRepo->ensureSchema();
                $billingCacheCount = (int)$this->billingRepo->countInRange($selectedAccountId, $dateFrom, $dateTo);
            } catch (\Throwable $e) {
                $billingCacheCount = 0;
            }

            // Jeśli nie ma cache — pokaż komunikat, ale pozwól filtrować (lista będzie pusta)
            $filtersBilling = [
                'buyer_login' => $participantLogin,
                'payment_id' => $paymentId,
                'alert' => $alert,
            ];

            if ($billingCacheCount > 0) {
                $totalBilling = $this->billing->countOrders($selectedAccountId, $dateFrom, $dateTo, $filtersBilling);
                $pageBilling = $this->billing->findOrdersPage($selectedAccountId, $dateFrom, $dateTo, $filtersBilling, $limit, $offset);
                $billingKpi = $this->billing->kpi($selectedAccountId, $dateFrom, $dateTo, $filtersBilling);

                $tol = 0.01;

                $humanizeApiError = function (string $msg, int $code = 0): string {
                    $m = trim(strip_tags($msg));
                    if ($m === '') {
                        return '';
                    }
                    $lower = Tools::strtolower($m);

                    if (preg_match('/checkout form\s+([a-z0-9\-]+)\s+not found/i', $m, $mm)) {
                        return 'Nie znaleziono zamówienia w Allegro (checkout form: ' . $mm[1] . ').';
                    }
                    if (strpos($lower, 'not found') !== false) {
                        return 'Nie znaleziono danych w Allegro (404). Szczegóły: ' . $m;
                    }
                    if (strpos($lower, 'unauthorized') !== false || strpos($lower, 'access denied') !== false || strpos($lower, 'invalid token') !== false) {
                        return 'Brak autoryzacji do Allegro API (token/konto). Szczegóły: ' . $m;
                    }
                    if (strpos($lower, 'unprocessable entity') !== false || strpos($lower, 'http 422') !== false) {
                        return 'Nieprawidłowe parametry zapytania do Allegro (HTTP 422). Szczegóły: ' . $m;
                    }
                    if (strpos($lower, 'too many requests') !== false || strpos($lower, 'http 429') !== false) {
                        return 'Limit zapytań do Allegro API (HTTP 429). Spróbuj ponownie za chwilę.';
                    }
                    if (strpos($lower, 'internal server error') !== false || strpos($lower, 'http 500') !== false) {
                        return 'Błąd serwera Allegro API (HTTP 500). Spróbuj ponownie później.';
                    }

                    if ($code > 0) {
                        return 'Błąd Allegro API (HTTP ' . (int)$code . '): ' . $m;
                    }
                    return 'Błąd Allegro API: ' . $m;
                };

                $mapBillingRow = function (array $r) use ($tol, $humanizeApiError): array {
                    $feesNeg = (float)($r['fees_neg'] ?? 0);
                    $refundsPos = (float)($r['refunds_pos'] ?? 0);
                    $paidAmount = (float)($r['paid_amount'] ?? 0);

                    $orderStatusRaw = (string)($r['order_status'] ?? '');
                    $orderStatus = strtoupper($orderStatusRaw);

                    $payStatusRaw = (string)($r['pay_status'] ?? '');
                    $payStatus = strtoupper($payStatusRaw);

                    $errCode = (int)($r['err_code'] ?? 0);
                    $errMsgRaw = (string)($r['err_msg'] ?? '');

                    $errLower = Tools::strtolower($errMsgRaw);
                    $errIsNotFound = ($errCode === 404) || ((strpos($errLower, 'checkout form') !== false || strpos($errLower, 'checkoutform') !== false) && strpos($errLower, 'not found') !== false);

                    // Status (Allegro): preferujemy status zamówienia, a jeśli go brak – status płatności (również z Allegro).
                    $orderStatusLabel = 'Brak danych';
                    $orderStatusHint = '';
                    $orderStatusClass = 'alpro-neutral';

                    if ($orderStatus !== '') {
                        switch ($orderStatus) {
                            case 'FILLED_IN':
                                $orderStatusLabel = 'Nieopłacone';
                                $orderStatusHint = 'Allegro: FILLED_IN';
                                $orderStatusClass = 'alpro-missing';
                                break;
                            case 'READY_FOR_PROCESSING':
                            case 'BOUGHT':
                                $orderStatusLabel = 'Opłacone';
                                $orderStatusHint = 'Allegro: ' . $orderStatus;
                                $orderStatusClass = 'alpro-ok';
                                break;
                            case 'PROCESSING':
                                $orderStatusLabel = 'W realizacji';
                                $orderStatusHint = 'Allegro: PROCESSING';
                                $orderStatusClass = 'alpro-waiting';
                                break;
                            case 'SENT':
                                $orderStatusLabel = 'Wysłane';
                                $orderStatusHint = 'Allegro: SENT';
                                $orderStatusClass = 'alpro-waiting';
                                break;
                            case 'CANCELLED':
                                $orderStatusLabel = 'Anulowane';
                                $orderStatusHint = 'Allegro: CANCELLED';
                                $orderStatusClass = 'alpro-neutral';
                                break;
                            default:
                                $orderStatusLabel = $orderStatus;
                                $orderStatusHint = 'Allegro: ' . $orderStatus;
                                $orderStatusClass = 'alpro-neutral';
                        }
                    } elseif ($payStatus !== '' || $paidAmount > 0) {
                        // Fallback na płatność: dzięki temu nie masz pustych statusów, nawet gdy zamówienie nie zostało zsynchronizowane do cache.
                        $isPaid = ($paidAmount > 0) || ($payStatus === 'PAID');
                        if ($isPaid) {
                            $orderStatusLabel = 'Opłacone';
                            $orderStatusClass = 'alpro-ok';
                        } else {
                            $orderStatusLabel = 'Nieopłacone';
                            $orderStatusClass = 'alpro-missing';
                        }

                        $hintPay = ($payStatusRaw !== '') ? $payStatusRaw : 'brak';
                        $orderStatusHint = 'Status na podstawie płatności Allegro: ' . $hintPay . '. (Brak statusu zamówienia w cache – uruchom synchronizację zamówień, aby widzieć etapy realizacji.)';
                    } else {
                        if ($errIsNotFound) {
                            $orderStatusLabel = 'Nie znaleziono';
                            $orderStatusClass = 'alpro-neutral';
                            $orderStatusHint = 'Allegro API nie zwraca szczegółów tego zamówienia (404: checkout form nie znaleziony). To może oznaczać zamówienie anulowane/nieopłacone, archiwalne lub niedostępne w API. Szczegóły w kolumnie „Problem”.';
                        } elseif ($errCode > 0) {
                            $orderStatusLabel = 'Nie pobrano';
                            $orderStatusClass = 'alpro-neutral';
                            $orderStatusHint = 'Nie udało się pobrać szczegółów zamówienia z Allegro API. Sprawdź kolumnę „Problem” (najechanie pokazuje szczegóły).';
                        } else {
                            $orderStatusLabel = 'Nie pobrano';
                            $orderStatusClass = 'alpro-neutral';
                            $orderStatusHint = 'Moduł nie ma jeszcze szczegółów tego zamówienia w cache (status / kupujący / płatność). Kliknij „Uzupełnij dane zamówień” w górnym pasku, aby pobrać dane z Allegro.';
                        }
                    }

                    // Alerty / problemy
                    $alertCode = '';
                    $alertLabel = '';
                    $alertHint = '';

                    $isPaid = ($paidAmount > 0) || ($payStatus === 'PAID');
                    $isUnpaid = (!$isPaid) && ($orderStatus === 'FILLED_IN' || ($orderStatus === '' && $payStatus !== ''));

                    $hasFees = ($feesNeg < -$tol);
                    $refundMissing = ($refundsPos <= $tol);
                    $refundPartial = ($refundsPos > $tol) && (($refundsPos + $tol) < abs($feesNeg));

                    // Najpierw klasyfikujemy problemy biznesowe (opłaty/zwroty), dopiero potem czysto techniczne błędy API.
                    if ($isUnpaid && $hasFees) {
                        $alertCode = 'unpaid_fees';
                        $alertLabel = 'Nieopłacone + opłaty';
                        $alertHint = 'Zamówienie jest nieopłacone, a Allegro naliczyło opłaty (ujemne billing-entries).';
                    } elseif (($orderStatus === 'CANCELLED' || $errIsNotFound) && $hasFees && $refundMissing) {
                        $alertCode = 'no_refund';
                        $alertLabel = 'Brak zwrotu opłat';
                        $alertHint = $errIsNotFound
                            ? 'Allegro naliczyło opłaty (np. prowizję), ale nie ma zwrotu opłat. Dodatkowo Allegro API nie zwraca szczegółów zamówienia (404 / brak zamówienia w API). Sprawdź w „Rozliczeniach” czy zwrot powinien zostać naliczony.'
                            : 'Zamówienie anulowane, są opłaty, ale brak zwrotu opłat.';
                    } elseif (($orderStatus === 'CANCELLED' || $errIsNotFound) && $hasFees && $refundPartial) {
                        $alertCode = 'partial_refund';
                        $alertLabel = 'Częściowy zwrot opłat';
                        $alertHint = $errIsNotFound
                            ? 'Zwrot opłat jest mniejszy niż naliczone opłaty. Dodatkowo Allegro API nie zwraca szczegółów zamówienia (404 / brak zamówienia w API). Sprawdź w „Rozliczeniach” czy zwrot powinien być pełny.'
                            : 'Zamówienie anulowane, zwrot opłat jest mniejszy niż naliczone opłaty.';
                    } elseif ($errCode > 0 && !$errIsNotFound) {
                        $alertCode = 'api_error';
                        $alertLabel = 'Błąd API';
                        $alertHint = 'Błąd z Allegro API podczas pobierania danych. Najedź na etykietę, aby zobaczyć szczegóły.';
                    }

                    // Szczegóły błędu (tooltip):
                    // - pokazujemy je wprost tylko dla „Błąd API”
                    // - dla przypadków „nie znaleziono zamówienia” (404) dokładamy opis do alertHint, żeby nie zaciemniać komunikatu
                    $errMsg = '';
                    if ($errCode > 0 && $errMsgRaw !== '') {
                        $human = $humanizeApiError($errMsgRaw, $errCode);
                        if ($alertCode === 'api_error') {
                            $errMsg = $human;
                        } elseif ($errIsNotFound && $human !== '' && $alertHint !== '') {
                            $alertHint .= ' ' . $human;
                        }
                    }

                    return [
                        'last_occurred_at' => (string)($r['last_occurred_at'] ?? ''),
                        'checkout_form_id' => (string)($r['checkout_form_id'] ?? ''),
                        'id_order_prestashop' => (int)($r['id_order_prestashop'] ?? 0),
                        'buyer_login' => (string)($r['buyer_login'] ?? ''),
                        'order_status' => $orderStatusRaw,
                        'order_status_label' => $orderStatusLabel,
                        'order_status_hint' => $orderStatusHint,
                        'order_status_class' => $orderStatusClass,
                        'paid_amount' => $paidAmount,
                        'fees_abs' => abs($feesNeg),
                        'refunds_pos' => $refundsPos,
                        'net' => (float)($r['net'] ?? ($feesNeg + $refundsPos)),
                        'alert_code' => $alertCode,
                        'alert_label' => $alertLabel,
                        'alert_hint' => $alertHint,
                        'billing_rows' => (int)($r['billing_rows'] ?? 0),
                        'err_code' => $errCode,
                        'err_msg' => $errMsg,
                    ];
                };

                foreach ($pageBilling as $r) {
                    $billingRows[] = $mapBillingRow((array)$r);
                }

                // Szybki podgląd: „najświeższe problemy” (żeby nie przeglądać setek wierszy).
                // Pokazujemy tylko w widoku „Wszystko”, bo gdy ktoś już filtruje alerty, to ma je na liście.
                $billingIssueLimit = 20;
                $billingIssueRows = [];
                if (($alert === '' || $alert === null) && (int)($billingKpi['issues'] ?? 0) > 0) {
                    $filtersIssues = $filtersBilling;
                    $filtersIssues['alert'] = 'issues';
                    $issuesPage = $this->billing->findOrdersPage($selectedAccountId, $dateFrom, $dateTo, $filtersIssues, $billingIssueLimit, 0);
                    foreach ($issuesPage as $ir) {
                        $billingIssueRows[] = $mapBillingRow((array)$ir);
                    }
                }

                $billingMeta['ok'] = true;
                $billingMeta['count'] = count($billingRows);
                $billingMeta['totalCount'] = (int)$totalBilling;
            } else {
                $billingMeta['ok'] = false;
                $billingMeta['error'] = 'Brak danych billing-entries w cache. Kliknij Synchronizuj.';
            }
        } else {
            // Rozliczenie / kontrola opłat (pobrane opłaty + zwroty opłat per checkoutFormId)
            if ($this->ops->ensureSchema()) {
                $filtersRecon = [
                    'buyer_login' => $participantLogin,
                    'payment_id' => $paymentId,
                    'order_status' => $orderStatus,
                ];
                // KPI dla CAŁEGO zakresu (nie tylko aktualnej strony listy)
                $reconKpi = $this->recon->kpiTotal($selectedAccountId, $dateFrom, $dateTo, $filtersRecon);
                $totalRecon = (int)($reconKpi['count'] ?? 0);

// Wypłaty na konto bankowe (OUTCOME/PAYOUT) - operacje niezależne od zamówień.
// Liczymy je wg daty operacji (occurred_at) z payment-operations, żeby dało się porównać z wyciągiem bankowym.
$payoutOutKpi = $this->ops->kpiTotal($selectedAccountId, $dateFrom, $dateTo, ['group' => 'OUTCOME', 'type' => 'PAYOUT']);
$payoutCancelKpi = $this->ops->kpiTotal($selectedAccountId, $dateFrom, $dateTo, ['group' => 'OUTCOME', 'type' => 'PAYOUT_CANCEL']);

$payoutOut = abs((float)($payoutOutKpi['neg'] ?? 0));
if ($payoutOut <= 0 && (float)($payoutOutKpi['total'] ?? 0) < 0) {
    $payoutOut = abs((float)$payoutOutKpi['total']);
}
$payoutCancel = abs((float)($payoutCancelKpi['total'] ?? 0));
$payoutNet = max(0.0, $payoutOut - $payoutCancel);

$payoutTotalCount = (int)$this->ops->countPayoutsTotal($selectedAccountId, $dateFrom, $dateTo);
$payoutRowsRaw = $this->ops->findPayouts($selectedAccountId, $dateFrom, $dateTo, 50, 0);

$payoutRows = [];
foreach ($payoutRowsRaw as $po) {
    $raw = (string)($po['raw_json'] ?? '');
    $payoutId = '';
    $walletBalanceAmount = null;
    $walletBalanceCurrency = '';

    if ($raw !== '') {
        $j = json_decode($raw, true);
        if (is_array($j)) {
            if (isset($j['payout']['id'])) {
                $payoutId = (string)$j['payout']['id'];
            }
            if (isset($j['wallet']['balance']['amount'])) {
                $walletBalanceAmount = (float)$j['wallet']['balance']['amount'];
                $walletBalanceCurrency = (string)($j['wallet']['balance']['currency'] ?? '');
            }
        }
    }

    $type = (string)($po['type'] ?? '');
    $payoutRows[] = [
        'occurred_at' => (string)($po['occurred_at'] ?? ''),
        'type' => $type,
        'type_label' => $type === 'PAYOUT_CANCEL' ? 'Anulowanie wypłaty' : 'Wypłata na konto',
        'amount' => (float)($po['amount'] ?? 0),
        'amount_abs' => abs((float)($po['amount'] ?? 0)),
        'currency' => (string)($po['currency'] ?? 'PLN'),
        'wallet_type' => (string)($po['wallet_type'] ?? ''),
        'wallet_operator' => (string)($po['wallet_operator'] ?? ''),
        'payout_id' => $payoutId,
        'wallet_balance_amount' => $walletBalanceAmount,
        'wallet_balance_currency' => $walletBalanceCurrency,
    ];
}

$reconPayouts = [
    'payout_out' => $payoutOut,
    'payout_cancel' => $payoutCancel,
    'payout_net' => $payoutNet,
    'total_count' => $payoutTotalCount,
    'shown' => count($payoutRows),
    'rows' => $payoutRows,
];

// Kontrola wypłat: bilans portfela AVAILABLE pomiędzy wypłatami.
// To jest szybka weryfikacja "czy Allegro wypłaciło tyle, ile powinno" (wg daty operacji).
$payoutChecks = $this->ops->payoutWindowsCheck($selectedAccountId, $dateFrom, $dateTo, 200);

                $pcSumPayout = 0.0;
                $pcSumExpectedTotal = 0.0;
                $pcSumExpectedOrders = 0.0;
                $pcSumOtherNet = 0.0;
                $pcSumPayments = 0.0;
                $pcSumFeeDeduction = 0.0;
                $pcSumFeeRefund = 0.0;
                $pcSumBalanceChange = 0.0;

                $pcOk = 0;
                $pcCarry = 0;
                $pcLeft = 0;
                $pcWarn = 0;

                foreach ($payoutChecks as $c) {
                    $pcSumPayout += (float)($c['payout'] ?? 0);
                    $pcSumExpectedTotal += (float)($c['expected_total'] ?? 0);
                    $pcSumExpectedOrders += (float)($c['expected_orders'] ?? 0);
                    $pcSumOtherNet += (float)($c['other_net'] ?? 0);
                    $pcSumPayments += (float)($c['payments_available'] ?? 0);
                    $pcSumFeeDeduction += (float)($c['fee_deduction'] ?? 0);
                    $pcSumFeeRefund += (float)($c['fee_refund'] ?? 0);
                    $pcSumBalanceChange += (float)($c['balance_change'] ?? 0);

                    $st = (string)($c['status'] ?? '');
                    $kind = (string)($c['status_kind'] ?? '');
                    if ($st === 'OK') {
                        $pcOk++;
                    } elseif ($st === 'SALDO_Z_POPRZEDNICH') {
                        $pcCarry++;
                    } elseif ($st === 'SALDO_POZOSTAJE') {
                        $pcLeft++;
                    }
                    if ($kind === 'warn') {
                        $pcWarn++;
                    }
                }

                $reconPayouts['checks'] = $payoutChecks;
                $reconPayouts['checks_meta'] = [
                    'count' => count($payoutChecks),
                    'ok' => $pcOk,
                    'changed' => max(0, count($payoutChecks) - $pcOk),
                    'carry' => $pcCarry,
                    'left' => $pcLeft,
                    'warn' => $pcWarn,
                    'sum_payout' => $pcSumPayout,
                    'sum_expected_total' => $pcSumExpectedTotal,
                    'sum_expected_orders' => $pcSumExpectedOrders,
                    'sum_other_net' => $pcSumOtherNet,
                    'sum_payments' => $pcSumPayments,
                    'sum_fee_deduction' => $pcSumFeeDeduction,
                    'sum_fee_refund' => $pcSumFeeRefund,
                    // Zmiana salda AVAILABLE w wybranym zakresie (saldo_start - saldo_koniec)
                    'sum_balance_change' => $pcSumBalanceChange,
                    'date_from' => $dateFrom,
                    'date_to' => $dateTo,
                ];


                $pageRecon = $this->recon->findCheckoutFormsPage($selectedAccountId, $dateFrom, $dateTo, $filtersRecon, $limit, $offset);

                $cfIds = [];
                foreach ($pageRecon as $r) {
                    if (!empty($r['checkout_form_id'])) {
                        $cfIds[] = (string)$r['checkout_form_id'];
                    }
                }
                $paymentsByCf = $this->tx->getPaymentsForCheckoutForms($cfIds);

                $allPaymentIds = [];
                foreach ($paymentsByCf as $plist) {
                    foreach ($plist as $p) {
                        $pid = (string)($p['payment_id'] ?? '');
                        if ($pid !== '') {
                            $allPaymentIds[$pid] = true;
                        }
                    }
                }
                $allPaymentIds = array_keys($allPaymentIds);

                $aggContrib = $this->opAgg->sumContributionsByPaymentIds($selectedAccountId, $allPaymentIds);
                $aggCharges = $this->opReconAgg->sumChargesByPaymentIds($selectedAccountId, $allPaymentIds);

                foreach ($pageRecon as $r) {
                    $cf = (string)($r['checkout_form_id'] ?? '');
                    $payments = $paymentsByCf[$cf] ?? [];

                    $paid = (float)($r['paid_amount'] ?? 0);
                    $currency = (string)($r['currency'] ?? 'PLN');

                    $sumWaiting = 0.0;
                    $sumAvailable = 0.0;
                    $sumDeduction = 0.0;
                    $sumRefundCharge = 0.0;

                    $payDetails = [];
                    foreach ($payments as $p) {
                        $pid = (string)($p['payment_id'] ?? '');
                        if ($pid === '') {
                            continue;
                        }
                        $expP = (float)($p['paid_amount'] ?? 0);

                        $c = $aggContrib[$pid] ?? ['available' => 0.0, 'waiting' => 0.0, 'total' => 0.0, 'first_at' => null, 'last_at' => null];
                        $ch = $aggCharges[$pid] ?? ['deduction' => 0.0, 'refund_charge' => 0.0, 'first_at' => null, 'last_at' => null];

                        $sumWaiting += (float)$c['waiting'];
                        $sumAvailable += (float)$c['available'];
                        $sumDeduction += (float)$ch['deduction'];
                        $sumRefundCharge += (float)$ch['refund_charge'];

                        $payDetails[] = [
                            'payment_id' => $pid,
                            'expected' => $expP,
                            'finished_at' => (string)($p['finished_at'] ?? ''),
                            'contrib_waiting' => (float)$c['waiting'],
                            'contrib_available' => (float)$c['available'],
                            'deduction' => (float)$ch['deduction'],
                            'refund_charge' => (float)$ch['refund_charge'],
                        ];
                    }

                    $cashflowTotal = $sumWaiting + $sumAvailable;
                    $net = $cashflowTotal - $sumDeduction + $sumRefundCharge;

                    // Status rozliczenia ma być czytelny i *problemowy*.
                    // Nie oznaczamy jako problemu tego, że "pobrano opłaty" – to jest norma.
                    $tol = 0.01;      // tolerancja groszowa
                    $tolDiff = 0.02;  // tolerancja różnicy (żeby nie łapać błędów zaokrągleń)
                    $isCancelled = ((string)($r['order_status'] ?? '') === 'CANCELLED');

                    $diffPaidCashflow = $cashflowTotal - $paid; // (+) więcej cashflow niż wpłata
                    $hasPaid = (abs($paid) > $tol);
                    $hasCashflow = (abs($cashflowTotal) > $tol);

                    $issue = false;
                    $issueType = '';
                    $status = 'ok';

                    // 1) Zapłacono, ale nie widać żadnego wpływu do portfeli (najczęściej brak danych w payment-operations dla tego payment_id)
                    if ($hasPaid && !$hasCashflow) {
                        $status = 'missing_cashflow';
                        $issue = true;
                        $issueType = 'missing_cashflow';
                    }
                    // 2) Jest cashflow, ale różni się od wpłaty (podejrzenie brakujących/skasowanych operacji)
                    elseif ($hasPaid && $hasCashflow && abs($diffPaidCashflow) > $tolDiff) {
                        $status = 'cashflow_diff';
                        $issue = true;
                        $issueType = 'cashflow_diff';
                    }
                    // 3) Zamówienie anulowane, a Allegro pobrało opłaty i nie oddało ich w payment-operations
                    elseif ($isCancelled && $sumDeduction > $tol && ($sumRefundCharge + $tol) < $sumDeduction) {
                        $status = 'missing_refund';
                        $issue = true;
                        $issueType = 'missing_refund';
                    }
                    // Pozostałe statusy są informacyjne (nie są "problemem")
                    elseif ($sumDeduction <= $tol && $sumRefundCharge <= $tol) {
                        $status = 'no_fees';
                    } elseif ($sumDeduction > $tol && !$isCancelled) {
                        $status = 'charged';
                    }

                    $reconRows[] = [
                        'checkout_form_id' => $cf,
                        'id_order_prestashop' => (int)($r['id_order_prestashop'] ?? 0),
                        'buyer_login' => (string)($r['buyer_login'] ?? ''),
                        'order_status' => (string)($r['order_status'] ?? ''),
                        'finished_at' => (string)($r['finished_at'] ?? ''),
                        'paid' => $paid,
                        'currency' => $currency,
                        'cashflow_waiting' => $sumWaiting,
                        'cashflow_available' => $sumAvailable,
                        'cashflow_total' => $cashflowTotal,
                        'diff_paid_cashflow' => $diffPaidCashflow,
                        'fee_deduction' => $sumDeduction,
                        'fee_refund' => $sumRefundCharge,
                        'fee_net' => ($sumDeduction - $sumRefundCharge),
                        'net' => $net,
                        'status' => $status,
                        'issue' => $issue,
                        'issue_type' => $issueType,
                        'payments' => $payDetails,
                    ];
                }

                $reconMeta['ok'] = true;
                $reconMeta['count'] = count($reconRows);
                $reconMeta['totalCount'] = (int)$totalRecon;

                // Szybki podgląd: najnowsze problemy w rozliczeniu (żeby nie przewijać całej tabeli).
                // Pobieramy większą paczkę ostatnich checkoutFormId (limit 200) i wyciągamy z nich max 20 problemów.
                $reconIssueLimit = 20;
                $reconIssueRows = [];
                if ((int)($reconKpi['issues'] ?? 0) > 0) {
                    $candidateLimit = 200;
                    $candidatePage = $this->recon->findCheckoutFormsPage($selectedAccountId, $dateFrom, $dateTo, $filtersRecon, $candidateLimit, 0);

                    $candCfIds = [];
                    foreach ($candidatePage as $cr) {
                        if (!empty($cr['checkout_form_id'])) {
                            $candCfIds[] = (string)$cr['checkout_form_id'];
                        }
                    }

                    $candPaymentsByCf = $this->tx->getPaymentsForCheckoutForms($candCfIds);
                    $candAllPaymentIds = [];
                    foreach ($candPaymentsByCf as $plist) {
                        foreach ($plist as $p) {
                            $pid = (string)($p['payment_id'] ?? '');
                            if ($pid !== '') {
                                $candAllPaymentIds[$pid] = true;
                            }
                        }
                    }
                    $candAllPaymentIds = array_keys($candAllPaymentIds);

                    $candAggContrib = $this->opAgg->sumContributionsByPaymentIds($selectedAccountId, $candAllPaymentIds);
                    $candAggCharges = $this->opReconAgg->sumChargesByPaymentIds($selectedAccountId, $candAllPaymentIds);

                    foreach ($candidatePage as $cr) {
                        $cf = (string)($cr['checkout_form_id'] ?? '');
                        if ($cf === '') {
                            continue;
                        }
                        $payments = $candPaymentsByCf[$cf] ?? [];

                        $paid = (float)($cr['paid_amount'] ?? 0);
                        $currency = (string)($cr['currency'] ?? 'PLN');

                        $sumWaiting = 0.0;
                        $sumAvailable = 0.0;
                        $sumDeduction = 0.0;
                        $sumRefundCharge = 0.0;
                        $payDetails = [];

                        foreach ($payments as $p) {
                            $pid = (string)($p['payment_id'] ?? '');
                            if ($pid === '') {
                                continue;
                            }
                            $expP = (float)($p['paid_amount'] ?? 0);
                            $c = $candAggContrib[$pid] ?? ['available' => 0.0, 'waiting' => 0.0, 'total' => 0.0, 'first_at' => null, 'last_at' => null];
                            $ch = $candAggCharges[$pid] ?? ['deduction' => 0.0, 'refund_charge' => 0.0, 'first_at' => null, 'last_at' => null];

                            $sumWaiting += (float)$c['waiting'];
                            $sumAvailable += (float)$c['available'];
                            $sumDeduction += (float)$ch['deduction'];
                            $sumRefundCharge += (float)$ch['refund_charge'];

                            $payDetails[] = [
                                'payment_id' => $pid,
                                'expected' => $expP,
                                'finished_at' => (string)($p['finished_at'] ?? ''),
                                'contrib_waiting' => (float)$c['waiting'],
                                'contrib_available' => (float)$c['available'],
                                'deduction' => (float)$ch['deduction'],
                                'refund_charge' => (float)$ch['refund_charge'],
                            ];
                        }

                        $cashflowTotal = $sumWaiting + $sumAvailable;
                        $net = $cashflowTotal - $sumDeduction + $sumRefundCharge;

                        $tol = 0.01;
                        $tolDiff = 0.02;
                        $isCancelled = ((string)($cr['order_status'] ?? '') === 'CANCELLED');
                        $diffPaidCashflow = $cashflowTotal - $paid;
                        $hasPaid = (abs($paid) > $tol);
                        $hasCashflow = (abs($cashflowTotal) > $tol);

                        $issue = false;
                        $issueType = '';
                        $status = 'ok';

                        if ($hasPaid && !$hasCashflow) {
                            $status = 'missing_cashflow';
                            $issue = true;
                            $issueType = 'missing_cashflow';
                        } elseif ($hasPaid && $hasCashflow && abs($diffPaidCashflow) > $tolDiff) {
                            $status = 'cashflow_diff';
                            $issue = true;
                            $issueType = 'cashflow_diff';
                        } elseif ($isCancelled && $sumDeduction > $tol && ($sumRefundCharge + $tol) < $sumDeduction) {
                            $status = 'missing_refund';
                            $issue = true;
                            $issueType = 'missing_refund';
                        } elseif ($sumDeduction <= $tol && $sumRefundCharge <= $tol) {
                            $status = 'no_fees';
                        } elseif ($sumDeduction > $tol && !$isCancelled) {
                            $status = 'charged';
                        }

                        if ($issue) {
                            $reconIssueRows[] = [
                                'checkout_form_id' => $cf,
                                'id_order_prestashop' => (int)($cr['id_order_prestashop'] ?? 0),
                                'buyer_login' => (string)($cr['buyer_login'] ?? ''),
                                'order_status' => (string)($cr['order_status'] ?? ''),
                                'finished_at' => (string)($cr['finished_at'] ?? ''),
                                'paid' => $paid,
                                'currency' => $currency,
                                'cashflow_waiting' => $sumWaiting,
                                'cashflow_available' => $sumAvailable,
                                'cashflow_total' => $cashflowTotal,
                                'diff_paid_cashflow' => $diffPaidCashflow,
                                'fee_deduction' => $sumDeduction,
                                'fee_refund' => $sumRefundCharge,
                                'fee_net' => ($sumDeduction - $sumRefundCharge),
                                'net' => $net,
                                'status' => $status,
                                'issue' => true,
                                'issue_type' => $issueType,
                                'payments' => $payDetails,
                            ];

                            if (count($reconIssueRows) >= $reconIssueLimit) {
                                break;
                            }
                        }
                    }
                }

                // Meta: przekazujemy do Smarty
                $reconMeta['issueLimit'] = $reconIssueLimit;
                $reconMeta['issueCount'] = count($reconIssueRows);
            
            } else {
                $reconMeta['ok'] = false;
                $reconMeta['error'] = 'Brak tabeli cache allegropro_payment_operation. Kliknij Synchronizuj.';
            }
        }

        
        $totalPages = 0;
        $totalCountForPager = ($view === 'raw')
            ? (int)$apiMeta['totalCount']
            : (($view === 'recon')
                ? (int)$reconMeta['totalCount']
                : (($view === 'billing')
                    ? (int)$billingMeta['totalCount']
                    : (int)$txMeta['totalCount']));
        if ($totalCountForPager > 0) {
            $totalPages = (int)ceil($totalCountForPager / $limit);
        }
        $prevPage = max(1, $page - 1);
        $nextPage = ($totalPages > 0) ? min($totalPages, $page + 1) : ($page + 1);

        // Dopisz totalPages do meta bieżącego widoku (żeby Smarty nie generował Warning: Undefined array key w PHP 8.2)
        if ($view === 'raw') {
            $apiMeta['totalPages'] = $totalPages;
        } elseif ($view === 'recon') {
            $reconMeta['totalPages'] = $totalPages;
        } elseif ($view === 'billing') {
            $billingMeta['totalPages'] = $totalPages;
        } else {
            // tx
            $txMeta['totalPages'] = $totalPages;
        }


        $prevUrl = $baseUrl . '&' . http_build_query(array_merge($queryParams, ['page' => $prevPage]));
        $nextUrl = $baseUrl . '&' . http_build_query(array_merge($queryParams, ['page' => $nextPage]));
        $viewTxUrl = $baseUrl . '&' . http_build_query(array_merge($queryParams, ['view' => 'tx', 'page' => 1]));
        $viewReconUrl = $baseUrl . '&' . http_build_query(array_merge($queryParams, ['view' => 'recon', 'page' => 1]));
        $viewBillingUrl = $baseUrl . '&' . http_build_query(array_merge($queryParams, ['view' => 'billing', 'page' => 1]));
        $viewRawUrl = $baseUrl . '&' . http_build_query(array_merge($queryParams, ['view' => 'raw', 'page' => 1]));

        // „Ostatnia synchronizacja” zależy od widoku (cashflows vs billing-entries).
        $lastSyncKey = ($view === 'billing') ? 'ALLEGROPRO_BILLING_LASTSYNC_' : 'ALLEGROPRO_CASHFLOWS_LASTSYNC_';
        $lastSync = (string)\Configuration::get($lastSyncKey . (int)$selectedAccountId);
        if ($lastSync === '') {
            $lastSync = null;
        }

        // Szybkie linki do filtrów alertów w BILLING (żeby jednym kliknięciem wyświetlić tylko to, co nie gra).
        $billingFilterUrls = [
            'all' => $baseUrl . '&' . http_build_query(array_merge($queryParams, ['view' => 'billing', 'alert' => '', 'page' => 1])),
            'issues' => $baseUrl . '&' . http_build_query(array_merge($queryParams, ['view' => 'billing', 'alert' => 'issues', 'page' => 1])),
            'unpaid_fees' => $baseUrl . '&' . http_build_query(array_merge($queryParams, ['view' => 'billing', 'alert' => 'unpaid_fees', 'page' => 1])),
            'no_refund' => $baseUrl . '&' . http_build_query(array_merge($queryParams, ['view' => 'billing', 'alert' => 'no_refund', 'page' => 1])),
            'partial_refund' => $baseUrl . '&' . http_build_query(array_merge($queryParams, ['view' => 'billing', 'alert' => 'partial_refund', 'page' => 1])),
            'api_error' => $baseUrl . '&' . http_build_query(array_merge($queryParams, ['view' => 'billing', 'alert' => 'api_error', 'page' => 1])),
        ];

        $syncUrl = $baseUrl . '&sync_cashflows=1&' . http_build_query(array_merge($queryParams, ['page' => 1]));

        // AJAX URL do chunk-sync (pobieranie partiami, bez limitu z UI)
        // Parametry (konto/datki/tryb) idą w POST z JS, żeby brać aktualne wartości z formularza.
        $ajaxSyncUrl = ($view === 'billing')
            ? ($baseUrl . '&ajax=1&action=syncBillingChunk')
            : ($baseUrl . '&ajax=1&action=syncCashflowsChunk');

        // AJAX: szczegóły opłat/zwrotów dla jednego checkoutFormId (order_id)
        $ajaxBillingDetailsUrl = ($view === 'billing')
            ? ($baseUrl . '&ajax=1&action=billingOrderDetails')
            : '';

        // AJAX: surowe payment-operations dla konkretnego payment_id (widok Rozliczenie → Szczegóły operacji).
        $ajaxReconOpsUrl = ($view === 'recon')
            ? ($baseUrl . '&ajax=1&action=reconPaymentOperations')
            : '';

        // Uzupełnianie brakujących danych zamówień (buyer/status/płatność) dla billing-entries.
        // Wykorzystujemy istniejący, sprawdzony mechanizm z zakładki "Rozliczenie" (AdminAllegroProSettlements),
        // który pobiera checkout-forms po ID i zapisuje je w cache zamówień.
        $enrichCountUrl = '';
        $enrichStepUrl = '';
        if ($view === 'billing') {
            $settlementsBase = $this->context->link->getAdminLink('AdminAllegroProSettlements');
            $enrichCountUrl = $settlementsBase . '&ajax=1&action=enrichMissingCount';
            $enrichStepUrl = $settlementsBase . '&ajax=1&action=enrichMissingStep';
        } elseif ($view === 'recon') {
            // Po synchronizacji payment-operations odświeżamy cache zamówień z Allegro (status/buyer itd.),
            // żeby widok "Rozliczenie" pokazywał aktualny status (np. WYSŁANE zamiast GOTOWE DO REALIZACJI).
            $enrichCountUrl = $baseUrl . '&ajax=1&action=reconOrdersRefreshCount';
            $enrichStepUrl = $baseUrl . '&ajax=1&action=reconOrdersRefreshStep';
        }

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
            'order_status' => $orderStatus,
            'alert' => $alert,
            'page' => $page,
            'offset' => $offset,
            'rows' => $rows,
            'api' => $apiMeta,
            'kpi' => $kpi,
            'tx_rows' => $txRows,
            'tx_api' => $txMeta,
            'tx_kpi' => $txKpi,
            'recon_rows' => $reconRows,
            'recon_issue_rows' => isset($reconIssueRows) ? $reconIssueRows : [],
            'recon_api' => $reconMeta,
            'recon_kpi' => $reconKpi,
            'recon_payouts' => $reconPayouts,
            'billing_rows' => $billingRows,
            'billing_issue_rows' => $billingIssueRows,
            'billing_issue_limit' => $billingIssueLimit,
            'billing_api' => $billingMeta,
            'billing_kpi' => $billingKpi,
            'billing_cache_count' => $billingCacheCount,
            'billing_filter_urls' => $billingFilterUrls,
            'base_url' => $baseUrl,
            'token' => $this->token,
            'query_params' => $queryParams,
            'export_url' => $baseUrl . '&export_cashflows=1&' . http_build_query($queryParams),
            'export_tx_url' => $baseUrl . '&export_tx=1&' . http_build_query($queryParams),
            'export_recon_url' => $baseUrl . '&export_recon=1&' . http_build_query($queryParams),
            'export_billing_url' => $baseUrl . '&export_billing=1&' . http_build_query($queryParams),
            'export_billing_issues_url' => $baseUrl . '&export_billing=1&' . http_build_query(array_merge($queryParams, ['view' => 'billing', 'alert' => 'issues'])),
            'total_pages' => $totalPages,
            'prev_url' => $prevUrl,
            'next_url' => $nextUrl,
            'view_tx_url' => $viewTxUrl,
            'view_recon_url' => $viewReconUrl,
            'view_billing_url' => $viewBillingUrl,
            'view_raw_url' => $viewRawUrl,
            'sync_url' => $syncUrl,
            'ajax_sync_url' => $ajaxSyncUrl,
            'ajax_billing_details_url' => $ajaxBillingDetailsUrl,
            'ajax_recon_ops_url' => $ajaxReconOpsUrl,
            'enrich_missing_count_url' => $enrichCountUrl,
            'enrich_missing_step_url' => $enrichStepUrl,
            'sync_flash' => $syncFlash,
            'last_sync_at' => $lastSync,
            'admin_orders_link' => $adminOrdersLink,
        ]);

        if ($view === 'raw') {
            $this->setTemplate('cashflows.tpl');
        } elseif ($view === 'recon') {
            $this->setTemplate('cashflows_recon.tpl');
        } elseif ($view === 'billing') {
            $this->setTemplate('cashflows_billing.tpl');
        } else {
            $this->setTemplate('cashflows_transactions.tpl');
        }
    }

    /**
     * Chunked synchronizacja billing-entries (GET /billing/billing-entries)
     * Wywoływana AJAX-em w pętli, aby uniknąć limitu i timeoutów.
     */
    public function ajaxProcessSyncBillingChunk(): void
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
        $sync = new BillingEntrySyncService($api, $this->billingRepo);

        $r = $sync->syncChunk($account, $dateFrom, $dateTo, $syncMode, $state, 200);
        if (!empty($r['ok']) && !empty($r['done'])) {
            // post-processing: dopnij order_id do wpisów billing na podstawie payment_id
            try {
                $bound = (int)$this->billingRepo->attachMissingOrderIdsFromPayments($selectedAccountId, $dateFrom, $dateTo);
                $r['bound_by_payment'] = $bound;
            } catch (\Throwable $e) {
                // ignore
            }

            Configuration::updateValue('ALLEGROPRO_BILLING_LASTSYNC_' . (int)$selectedAccountId, date('Y-m-d H:i:s'));
            $r['last_sync_at'] = (string)Configuration::get('ALLEGROPRO_BILLING_LASTSYNC_' . (int)$selectedAccountId);
        }

        echo json_encode($r);
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

    /**
     * Widok "Rozliczenie": po synchronizacji payment-operations odświeżamy cache zamówień z Allegro,
     * żeby status/buyer itp. były aktualne (np. WYSŁANE zamiast GOTOWE DO REALIZACJI).
     *
     * Endpointy są używane przez ten sam mechanizm "after-enrich", który w zakładce BILLING
     * uzupełnia brakujące dane zamówień.
     *
     * ?ajax=1&action=reconOrdersRefreshCount
     * POST: account_id, date_from, date_to
     */
    public function ajaxProcessReconOrdersRefreshCount(): void
    {
        header('Content-Type: application/json; charset=UTF-8');

        $accountId = (int)Tools::getValue('account_id', 0);
        $dateFrom = $this->sanitizeYmd((string)Tools::getValue('date_from', '')) ?: date('Y-m-01');
        $dateTo = $this->sanitizeYmd((string)Tools::getValue('date_to', '')) ?: date('Y-m-d');

        if ($accountId <= 0) {
            echo json_encode(['ok' => false, 'error' => 'Brak konta.']);
            return;
        }

        // Throttling: nie wchodź w błędne checkoutFormId w kółko (np. 404/403/429)
        $skipSvc = new OrderEnrichSkipService();
        $skipSvc->ensureSchema();

        $fromTs = pSQL($dateFrom . ' 00:00:00');
        $toTs = pSQL($dateTo . ' 23:59:59');
        $nowTs = pSQL(date('Y-m-d H:i:s'));

        $tPay = _DB_PREFIX_ . 'allegropro_order_payment';
        $tOrder = _DB_PREFIX_ . 'allegropro_order';
        $tSkip = _DB_PREFIX_ . 'allegropro_order_enrich_skip';

        // UWAGA: {prefix}_allegropro_order_payment nie ma id_allegropro_account,
        // dlatego zawężamy listę przez join do {prefix}_allegropro_order.
        // Bez tego refresh próbowałby pobierać zamówienia z innych kont i dostawałby masowo 404.

        $missing = (int)Db::getInstance()->getValue(
            "SELECT COUNT(DISTINCT op.checkout_form_id)
             FROM `{$tPay}` op
             INNER JOIN `{$tOrder}` o ON o.checkout_form_id = op.checkout_form_id
             LEFT JOIN `{$tSkip}` s
                ON s.id_allegropro_account=" . (int)$accountId . "
               AND s.order_id = op.checkout_form_id
               AND s.skip_until IS NOT NULL
               AND s.skip_until > '{$nowTs}'
             WHERE o.id_allegropro_account=" . (int)$accountId . "
               AND op.finished_at BETWEEN '{$fromTs}' AND '{$toTs}'
               AND s.id_allegropro_order_enrich_skip IS NULL"
        );

        $throttled = (int)Db::getInstance()->getValue(
            "SELECT COUNT(DISTINCT op.checkout_form_id)
             FROM `{$tPay}` op
             INNER JOIN `{$tOrder}` o ON o.checkout_form_id = op.checkout_form_id
             INNER JOIN `{$tSkip}` s
                ON s.id_allegropro_account=" . (int)$accountId . "
               AND s.order_id = op.checkout_form_id
               AND s.skip_until IS NOT NULL
               AND s.skip_until > '{$nowTs}'
             WHERE o.id_allegropro_account=" . (int)$accountId . "
               AND op.finished_at BETWEEN '{$fromTs}' AND '{$toTs}'"
        );

        // Zwracamy pole 'missing', bo JS od dawna używa tej nazwy w progresie.
        echo json_encode([
            'ok' => true,
            'missing' => $missing,
            'throttled' => $throttled,
        ]);
    }

    /**
     * ?ajax=1&action=reconOrdersRefreshStep
     * POST: account_id, date_from, date_to, cursor_last_at, cursor_order_id, limit
     */
    public function ajaxProcessReconOrdersRefreshStep(): void
    {
        header('Content-Type: application/json; charset=UTF-8');

        $accountId = (int)Tools::getValue('account_id', 0);
        $dateFrom = $this->sanitizeYmd((string)Tools::getValue('date_from', '')) ?: date('Y-m-01');
        $dateTo = $this->sanitizeYmd((string)Tools::getValue('date_to', '')) ?: date('Y-m-d');
        $cursorLastAt = (string)Tools::getValue('cursor_last_at', '');
        $cursorOrderId = (string)Tools::getValue('cursor_order_id', '');
        $limit = (int)Tools::getValue('limit', 10);

        if ($accountId <= 0) {
            echo json_encode(['ok' => false, 'error' => 'Brak konta.']);
            return;
        }

        $account = $this->accounts->get($accountId);
        if (!is_array($account) || empty($account['access_token'])) {
            echo json_encode(['ok' => false, 'error' => 'Brak aktywnego konta / tokena.']);
            return;
        }

        $limit = max(1, min(25, $limit));

        // Cursor start ("od najwyższej")
        $cursorLastAt = $cursorLastAt !== '' ? pSQL($cursorLastAt) : '9999-12-31 23:59:59';
        $cursorOrderId = $cursorOrderId !== '' ? pSQL($cursorOrderId) : str_repeat('z', 64);

        $fromTs = pSQL($dateFrom . ' 00:00:00');
        $toTs = pSQL($dateTo . ' 23:59:59');
        $nowTs = pSQL(date('Y-m-d H:i:s'));

        $tPay = _DB_PREFIX_ . 'allegropro_order_payment';
        $tOrder = _DB_PREFIX_ . 'allegropro_order';
        $tSkip = _DB_PREFIX_ . 'allegropro_order_enrich_skip';

        // Throttling błędów (404/403/429 itd.)
        $skipSvc = new OrderEnrichSkipService();
        $skipSvc->ensureSchema();

        // UWAGA: order_payment nie ma id_allegropro_account, więc zawężamy listę przez JOIN do tabeli zamówień.
        // Bez tego moduł próbowałby pobierać zamówienia z innych kont i dostawałby masowo 404.
        $sql = "SELECT
                    op.checkout_form_id,
                    MAX(op.finished_at) AS last_at
                FROM `{$tPay}` op
                INNER JOIN `{$tOrder}` o ON o.checkout_form_id = op.checkout_form_id
                LEFT JOIN `{$tSkip}` s
                   ON s.id_allegropro_account=" . (int)$accountId . "
                  AND s.order_id = op.checkout_form_id
                  AND s.skip_until IS NOT NULL
                  AND s.skip_until > '{$nowTs}'
                WHERE o.id_allegropro_account=" . (int)$accountId . "
                  AND op.finished_at BETWEEN '{$fromTs}' AND '{$toTs}'
                  AND s.id_allegropro_order_enrich_skip IS NULL
                GROUP BY op.checkout_form_id
                HAVING (last_at < '{$cursorLastAt}' OR (last_at = '{$cursorLastAt}' AND op.checkout_form_id < '{$cursorOrderId}'))
                ORDER BY last_at DESC, op.checkout_form_id DESC
                LIMIT {$limit}";

        $rows = Db::getInstance()->executeS($sql);
        if (!is_array($rows)) {
            $rows = [];
        }

        $api = new AllegroApiClient(new HttpClient(), $this->accounts);
        $orderRepo = new OrderRepository();
        $orderRepo->ensureSchema();

        $processed = 0;
        $updated = 0;
        $notFound = 0;
        $errors = [];

        $nextCursorLastAt = $cursorLastAt;
        $nextCursorOrderId = $cursorOrderId;

        foreach ($rows as $r) {
            $processed++;

            $checkoutFormId = (string)($r['checkout_form_id'] ?? '');
            $lastAt = (string)($r['last_at'] ?? '');
            if ($checkoutFormId === '' || $lastAt === '') {
                continue;
            }

            // Cursor przesuwamy zawsze na ostatni przetworzony rekord
            $nextCursorLastAt = $lastAt;
            $nextCursorOrderId = $checkoutFormId;

            $resp = $api->getWithAcceptFallbacks($account, '/order/checkout-forms/' . rawurlencode($checkoutFormId));
            if (empty($resp['ok']) || empty($resp['json']) || !is_array($resp['json'])) {
                $code = (int)($resp['code'] ?? 0);

                // zapisz throttling (żeby nie mielić w kółko tych samych 404/403/429)
                $skipSvc->mark($accountId, $checkoutFormId, $code, (string)($resp['raw'] ?? ''));

                if ($code === 404) {
                    // 404 nie traktujemy jako "błąd" aplikacji – Allegro po prostu nie zwraca tego checkoutFormId
                    // (np. zamówienie zniknęło / jest na innym koncie).
                    $notFound++;
                    continue;
                }

                $errors[] = [
                    'order_id' => $checkoutFormId,
                    'code' => (string)($resp['code'] ?? ''),
                    'msg' => 'Nie udało się pobrać danych zamówienia z Allegro.',
                ];
                continue;
            }

            try {
                $orderRepo->saveFullOrder($accountId, $resp['json']);
                $updated++;
                // jeśli wcześniej było odroczone – usuń throttling
                $skipSvc->clear($accountId, $checkoutFormId);
            } catch (\Throwable $e) {
                $errors[] = [
                    'order_id' => $checkoutFormId,
                    'code' => 'DB',
                    'msg' => 'Błąd zapisu do bazy: ' . $e->getMessage(),
                ];
            }
        }

        $done = count($rows) < $limit;

        echo json_encode([
            'ok' => true,
            'processed' => $processed,
            'updated_orders' => $updated,
            'not_found' => $notFound,
            // "errors" zawiera tylko realne błędy (inne niż 404)
            'errors' => $errors,
            'errors_count' => count($errors),
            'next_cursor_last_at' => $nextCursorLastAt,
            'next_cursor_order_id' => $nextCursorOrderId,
            'done' => $done,
        ]);
    }

    
    /**
     * AJAX: szczegóły billing-entries dla jednego order_id (checkoutFormId),
     * aby w widoku BILLING można było szybko zobaczyć "za co" Allegro naliczyło opłaty / zwroty opłat.
     *
     * ?ajax=1&action=billingOrderDetails
     * POST: id_allegropro_account, order_id, date_from, date_to
     */
    public function ajaxProcessBillingOrderDetails(): void
    {
        header('Content-Type: application/json; charset=UTF-8');

        $accountId = (int)Tools::getValue('id_allegropro_account', 0);
        $orderId = $this->sanitizeId((string)Tools::getValue('order_id', ''));
        $dateFrom = $this->sanitizeYmd((string)Tools::getValue('date_from', '')) ?: date('Y-m-01');
        $dateTo = $this->sanitizeYmd((string)Tools::getValue('date_to', '')) ?: date('Y-m-d');

        if ($accountId <= 0 || $orderId === '') {
            echo json_encode(['ok' => false, 'error' => 'Brak order_id lub konta.']);
            return;
        }

        try {
            $rows = $this->billingRepo->listForOrder($accountId, $orderId, $dateFrom, $dateTo);
        } catch (\Throwable $e) {
            echo json_encode(['ok' => false, 'error' => 'Błąd odczytu cache billing-entries: ' . $e->getMessage()]);
            return;
        }

        $feesAbs = 0.0;
        $refundsPos = 0.0;
        $entries = [];

        foreach ($rows as $r) {
            $amt = isset($r['value_amount']) ? (float)$r['value_amount'] : 0.0;
            if ($amt < 0) {
                $feesAbs += abs($amt);
            } elseif ($amt > 0) {
                $refundsPos += $amt;
            }

            $entries[] = [
                'occurred_at' => (string)($r['occurred_at'] ?? ''),
                'type_name' => (string)($r['type_name'] ?? ''),
                'type_id' => (string)($r['type_id'] ?? ''),
                'offer_id' => (string)($r['offer_id'] ?? ''),
                'offer_name' => (string)($r['offer_name'] ?? ''),
                'amount' => $amt,
                'currency' => (string)($r['value_currency'] ?? 'PLN'),
                'tax_percentage' => ($r['tax_percentage'] !== null && $r['tax_percentage'] !== '') ? (float)$r['tax_percentage'] : null,
                'tax_annotation' => (string)($r['tax_annotation'] ?? ''),
                'payment_id' => (string)($r['payment_id'] ?? ''),
                'billing_entry_id' => (string)($r['billing_entry_id'] ?? ''),
            ];
        }

        $net = $refundsPos - $feesAbs;

        echo json_encode([
            'ok' => true,
            'account_id' => $accountId,
            'order_id' => $orderId,
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'fees_abs' => round($feesAbs, 2),
            'refunds_pos' => round($refundsPos, 2),
            'net' => round($net, 2),
            'entries' => $entries,
        ]);
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

    private function exportReconciliationCsv(int $accountId, string $dateFrom, string $dateTo, string $buyerLogin, string $paymentId, string $orderStatus): void
    {
        if (!$this->ops->ensureSchema()) {
            header('HTTP/1.1 500 Internal Server Error');
            echo "Brak tabeli cache allegropro_payment_operation.\n";
            return;
        }

        $fname = 'allegro_reconciliation_' . $dateFrom . '_' . $dateTo . '.csv';
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename=\"' . $fname . '\"');
        echo "\xEF\xBB\xBF";

        $out = fopen('php://output', 'w');
        fputcsv($out, ['finished_at','checkout_form_id','order_status','id_order_prestashop','buyer_login','paid','cashflow_total','fee_deduction','fee_refund','net','status','payment_ids'], ';');

        $filtersRecon = ['buyer_login' => $buyerLogin, 'payment_id' => $paymentId, 'order_status' => $orderStatus];
        $offset = 0;
        $chunk = 300;

        while (true) {
            $pageRecon = $this->recon->findCheckoutFormsPage($accountId, $dateFrom, $dateTo, $filtersRecon, $chunk, $offset);
            if (empty($pageRecon)) {
                break;
            }

            $cfIds = [];
            foreach ($pageRecon as $r) {
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
            $aggContrib = $this->opAgg->sumContributionsByPaymentIds($accountId, $allPaymentIds);
            $aggCharges = $this->opReconAgg->sumChargesByPaymentIds($accountId, $allPaymentIds);

            foreach ($pageRecon as $r) {
                $cf = (string)($r['checkout_form_id'] ?? '');
                $paid = (float)($r['paid_amount'] ?? 0);
                $payments = $paymentsByCf[$cf] ?? [];

                $sumWaiting = 0.0;
                $sumAvailable = 0.0;
                $sumDeduction = 0.0;
                $sumRefund = 0.0;
                $ids = [];

                foreach ($payments as $p) {
                    $pid = (string)($p['payment_id'] ?? '');
                    if ($pid === '') continue;
                    $ids[] = $pid;
                    $c = $aggContrib[$pid] ?? ['available' => 0.0, 'waiting' => 0.0];
                    $ch = $aggCharges[$pid] ?? ['deduction' => 0.0, 'refund_charge' => 0.0];
                    $sumWaiting += (float)$c['waiting'];
                    $sumAvailable += (float)$c['available'];
                    $sumDeduction += (float)$ch['deduction'];
                    $sumRefund += (float)$ch['refund_charge'];
                }

                $cashflowTotal = $sumWaiting + $sumAvailable;
                $net = $cashflowTotal - $sumDeduction + $sumRefund;

                $tol = 0.01;
                $isCancelled = ((string)($r['order_status'] ?? '') === 'CANCELLED');
                $status = 'ok';
                if ($isCancelled && $sumDeduction > $tol && ($sumRefund + $tol) < $sumDeduction) {
                    $status = 'missing_refund';
                } elseif ($sumDeduction <= $tol && $sumRefund <= $tol) {
                    $status = 'no_fees';
                } elseif ($sumDeduction > $tol && !$isCancelled) {
                    $status = 'charged';
                }

                fputcsv($out, [
                    (string)($r['finished_at'] ?? ''),
                    $cf,
                    (string)($r['order_status'] ?? ''),
                    (string)(int)($r['id_order_prestashop'] ?? 0),
                    (string)($r['buyer_login'] ?? ''),
                    number_format($paid, 2, '.', ''),
                    number_format($cashflowTotal, 2, '.', ''),
                    number_format($sumDeduction, 2, '.', ''),
                    number_format($sumRefund, 2, '.', ''),
                    number_format($net, 2, '.', ''),
                    $status,
                    implode(',', $ids),
                ], ';');
            }

            $offset += count($pageRecon);
            if (count($pageRecon) < $chunk) {
                break;
            }
        }

        fclose($out);
    }

    private function exportBillingCsv(int $accountId, string $dateFrom, string $dateTo, string $buyerLogin, string $paymentId, string $alert): void
    {
        try {
            $this->billingRepo->ensureSchema();
        } catch (\Throwable $e) {
            header('HTTP/1.1 500 Internal Server Error');
            echo "Brak tabeli cache allegropro_billing_entry.\n";
            return;
        }

        $fname = 'allegro_billing_audit_' . $dateFrom . '_' . $dateTo . '.csv';
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $fname . '"');
        echo "\xEF\xBB\xBF";

        $out = fopen('php://output', 'w');
        fputcsv($out, ['last_occurred_at','checkoutFormId','order_status','id_order_prestashop','buyer_login','paid_amount','fees','refunds','net','alert','billing_entries','err_code','err_msg'], ';');

        $filters = [
            'buyer_login' => $buyerLogin,
            'payment_id' => $paymentId,
            'alert' => $alert,
        ];

        $offset = 0;
        $chunk = 500;
        $tol = 0.01;
        while (true) {
            $page = $this->billing->findOrdersPage($accountId, $dateFrom, $dateTo, $filters, $chunk, $offset);
            if (empty($page)) {
                break;
            }

            foreach ($page as $r) {
                $feesNeg = (float)($r['fees_neg'] ?? 0);
                $refundsPos = (float)($r['refunds_pos'] ?? 0);
                $paidAmount = (float)($r['paid_amount'] ?? 0);
                $orderStatus2 = strtoupper((string)($r['order_status'] ?? ''));
                $errCode = (int)($r['err_code'] ?? 0);

                $alertLabel = '';
                if ($errCode > 0) {
                    $alertLabel = 'Błąd API';
                } elseif ($orderStatus2 === 'FILLED_IN' && $paidAmount <= 0 && $feesNeg < -$tol) {
                    $alertLabel = 'Nieopłacone + opłaty';
                } elseif ($orderStatus2 === 'CANCELLED' && $feesNeg < -$tol && $refundsPos <= $tol) {
                    $alertLabel = 'Brak zwrotu opłat';
                } elseif ($orderStatus2 === 'CANCELLED' && $feesNeg < -$tol && $refundsPos > $tol && ($refundsPos + $tol) < abs($feesNeg)) {
                    $alertLabel = 'Częściowy zwrot opłat';
                }

                fputcsv($out, [
                    (string)($r['last_occurred_at'] ?? ''),
                    (string)($r['checkout_form_id'] ?? ''),
                    (string)($r['order_status'] ?? ''),
                    (string)(int)($r['id_order_prestashop'] ?? 0),
                    (string)($r['buyer_login'] ?? ''),
                    number_format($paidAmount, 2, '.', ''),
                    number_format(abs($feesNeg), 2, '.', ''),
                    number_format($refundsPos, 2, '.', ''),
                    number_format((float)($r['net'] ?? ($feesNeg + $refundsPos)), 2, '.', ''),
                    $alertLabel,
                    (string)(int)($r['billing_rows'] ?? 0),
                    (string)$errCode,
                    (string)($r['err_msg'] ?? ''),
                ], ';');
            }

            $offset += count($page);
            if (count($page) < $chunk) {
                break;
            }
        }

        fclose($out);
    }



    /**
     * AJAX: zwraca surowe payment-operations dla konkretnego payment_id.
     * Używane w zakładce Rozliczenie → „Szczegóły operacji”.
     */
    public function ajaxProcessReconPaymentOperations(): void
    {
        header('Content-Type: application/json; charset=UTF-8');

        $accountId = (int)Tools::getValue('id_allegropro_account', 0);
        $paymentId = $this->sanitizeId((string)Tools::getValue('payment_id', ''));

        if ($accountId <= 0 || $paymentId === '') {
            die(json_encode([
                'ok' => false,
                'error' => 'Brak wymaganych parametrów (konto / payment_id).',
            ]));
        }

        if (!$this->ops->ensureSchema()) {
            die(json_encode([
                'ok' => false,
                'error' => 'Brak tabeli cache payment-operations. Kliknij Synchronizuj.',
            ]));
        }

        $tPay = _DB_PREFIX_ . 'allegropro_payment_operation';

        $sql = "SELECT occurred_at, op_group, op_type, wallet_type, amount, currency, order_id\n"
            . "FROM `" . bqSQL($tPay) . "`\n"
            . "WHERE id_allegropro_account=" . (int)$accountId . " AND payment_id='" . pSQL($paymentId) . "'\n"
            . "ORDER BY occurred_at ASC";

        $rows = Db::getInstance()->executeS($sql);
        if (!is_array($rows)) {
            $rows = [];
        }

        // Podsumowanie – identyczna logika jak w agregatach (użytkownik widzi, co dokładnie liczymy).
        $sumWaiting = 0.0;
        $sumAvailable = 0.0;
        $sumDeduction = 0.0;
        $sumRefund = 0.0;

        foreach ($rows as $r) {
            $grp = (string)($r['op_group'] ?? '');
            $type = (string)($r['op_type'] ?? '');
            $wallet = (string)($r['wallet_type'] ?? '');
            $amt = (float)($r['amount'] ?? 0);

            if ($grp === 'INCOME' && $type === 'CONTRIBUTION') {
                if ($wallet === 'WAITING') {
                    $sumWaiting += $amt;
                } elseif ($wallet === 'AVAILABLE') {
                    $sumAvailable += $amt;
                }
            }

            // Opłaty i zwroty opłat Allegro (CHARGE). Allegro zwykle używa kwot ujemnych dla potrąceń,
            // ale dodatkowo zabezpieczamy się op_group = OUTCOME/REFUND.
            if (substr($type, -6) === 'CHARGE' || stripos($type, 'CHARGE') !== false) {
                if ($amt < 0 || $grp === 'OUTCOME') {
                    $sumDeduction += abs($amt);
                } elseif ($amt > 0 || $grp === 'REFUND') {
                    $sumRefund += $amt;
                }
            }
        }

        die(json_encode([
            'ok' => true,
            'payment_id' => $paymentId,
            'rows' => $rows,
            'sums' => [
                'cashflow_waiting' => $sumWaiting,
                'cashflow_available' => $sumAvailable,
                'cashflow_total' => ($sumWaiting + $sumAvailable),
                'fee_deduction' => $sumDeduction,
                'fee_refund' => $sumRefund,
                'fee_net' => ($sumDeduction - $sumRefund),
                'net' => (($sumWaiting + $sumAvailable) - $sumDeduction + $sumRefund),
            ],
        ]));
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
