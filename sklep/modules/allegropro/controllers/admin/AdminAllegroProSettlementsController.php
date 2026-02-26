<?php
/**
 * ALLEGRO PRO - Rozliczenia (Billing)
 */

use AllegroPro\Repository\AccountRepository;
use AllegroPro\Repository\BillingEntryRepository;
use AllegroPro\Repository\OrderRepository;
use AllegroPro\Service\HttpClient;
use AllegroPro\Service\AllegroApiClient;
use AllegroPro\Service\BillingSyncService;
use AllegroPro\Service\OrderFetcher;
use AllegroPro\Service\SettlementsReportService;
use AllegroPro\Service\IssuesReportService;
use AllegroPro\Service\UnassignedBillingReportService;
use AllegroPro\Service\BillingRawReportService;
use AllegroPro\Service\OrderEnrichSkipService;
use AllegroPro\Service\OrderBillingManualSyncService;

class AdminAllegroProSettlementsController extends ModuleAdminController
{
    private AccountRepository $accounts;
    private BillingEntryRepository $billingRepo;
    private OrderRepository $orderRepo;
    private OrderEnrichSkipService $enrichSkip;

    public function setMedia($isNewTheme = false)
    {
        parent::setMedia($isNewTheme);
        if (!empty($this->module)) {
            $cssLocal = $this->module->getLocalPath() . 'views/css/settlements.css';
            $jsLocal = $this->module->getLocalPath() . 'views/js/settlements.js';

            $cssVer = @filemtime($cssLocal);
            $jsVer = @filemtime($jsLocal);

            if (!$cssVer) { $cssVer = time(); }
            if (!$jsVer) { $jsVer = time(); }

            $this->addCSS($this->module->getPathUri() . 'views/css/settlements.css?v=' . (int)$cssVer);
            $this->addJS($this->module->getPathUri() . 'views/js/settlements.js?v=' . (int)$jsVer);
        }
    }

    public function __construct()
    {
        parent::__construct();
        $this->bootstrap = true;
        $this->accounts = new AccountRepository();
        $this->billingRepo = new BillingEntryRepository();
        $this->orderRepo = new OrderRepository();
        $this->enrichSkip = new OrderEnrichSkipService();
    }

    public function postProcess()
    {
        // Eksport CSV: operacje billing (RAW)
        if ((int)Tools::getValue('export_raw_billing', 0) === 1) {
            $this->exportRawBillingCsv();
            exit;
        }

        parent::postProcess();
    }

    public function initContent()
    {
        parent::initContent();

        if (isset($this->module) && method_exists($this->module, 'ensureTabs')) {
            $this->module->ensureTabs();
        }

        // zapewnij tabelę billing (dla istniejących instalacji bez reinstalacji)
        $this->billingRepo->ensureSchema();

        $accounts = $this->accounts->all();

        // Multi-select kont: id_allegropro_account może być tablicą (id_allegropro_account[]) albo pojedynczą wartością.
        $rawAcc = Tools::getValue('id_allegropro_account', []);
        if (!is_array($rawAcc)) {
            $rawAcc = [$rawAcc];
        }
        $selectedAccountIds = [];
        foreach ($rawAcc as $v) {
            $id = (int)$v;
            if ($id > 0) {
                $selectedAccountIds[$id] = $id;
            }
        }
        $selectedAccountIds = array_values($selectedAccountIds);
        if (empty($selectedAccountIds) && !empty($accounts)) {
            $selectedAccountIds = [(int)($accounts[0]['id_allegropro_account'] ?? 0)];
        }

        // Etykiety wybranych kont (do nagłówka)
        $selectedAccountLabels = [];
        foreach ($accounts as $a) {
            $aid = (int)($a['id_allegropro_account'] ?? 0);
            if ($aid && in_array($aid, $selectedAccountIds, true)) {
                $selectedAccountLabels[] = (string)($a['label'] ?? $a['allegro_login'] ?? ('#' . $aid));
            }
        }
        $selectedAccountLabel = '';
        if (count($selectedAccountLabels) === 1) {
            $selectedAccountLabel = $selectedAccountLabels[0];
        } elseif (count($selectedAccountLabels) > 1) {
            $selectedAccountLabel = $selectedAccountLabels[0] . ' + ' . (count($selectedAccountLabels) - 1);
        }

        // Dla synchronizacji dopuszczamy tylko jedno konto.
        $selectedAccount = null;
        $selectedAccountIdForSync = (int)($selectedAccountIds[0] ?? 0);
        if (count($selectedAccountIds) === 1) {
            foreach ($accounts as $a) {
                if ((int)$a['id_allegropro_account'] === $selectedAccountIdForSync) {
                    $selectedAccount = $a;
                    break;
                }
            }
        }

        // daty
        $dateFrom = (string)Tools::getValue('date_from', '');
        $dateTo = (string)Tools::getValue('date_to', '');
        if (!$dateFrom || !$dateTo) {
            $dateFrom = date('Y-m-01');
            $dateTo = date('Y-m-d');
        }
        $dateFrom = $this->sanitizeYmd($dateFrom) ?: date('Y-m-01');
        $dateTo = $this->sanitizeYmd($dateTo) ?: date('Y-m-d');

        $q = trim((string)Tools::getValue('q', ''));
        $q = $this->sanitizeSearch($q);

        $mode = (string)Tools::getValue('mode', 'billing');
        $mode = in_array($mode, ['billing', 'orders'], true) ? $mode : 'billing';




        // Sortowanie listy zamówień (bezpieczna biała lista zależna od trybu)
        $sortBy = (string)Tools::getValue('sort_by', 'date');
        $sortDir = (string)Tools::getValue('sort_dir', 'desc');
        $sortDir = (strtolower($sortDir) === 'asc') ? 'asc' : 'desc';
        $allowedSort = ($mode === 'billing') ? ['date','sales','fees','net'] : ['date','sales'];
        if (!in_array($sortBy, $allowedSort, true)) {
            $sortBy = 'date';
        }

        $orderState = (string)Tools::getValue('order_state', 'all');
        $orderState = in_array($orderState, ['all', 'paid', 'unpaid', 'cancelled'], true) ? $orderState : 'all';

        $cancelledNoRefund = (int)Tools::getValue('cancelled_no_refund', 0) ? true : false;
        if ($cancelledNoRefund) {
            $orderState = 'cancelled';
        }
// koszt/typy operacji (UI jak w Allegro) — na tym etapie tylko pobranie listy i zapamiętywanie wyboru
$feeGroup = (string)Tools::getValue('fee_group', '');
$allowedFeeGroups = ['', 'commission', 'delivery', 'smart', 'promotion', 'refunds', 'other'];
if (!in_array($feeGroup, $allowedFeeGroups, true)) {
    $feeGroup = '';
}

$feeTypesSelected = Tools::getValue('fee_type', []);
if (!is_array($feeTypesSelected)) {
    $feeTypesSelected = [$feeTypesSelected];
}
$feeTypesSelectedClean = [];
foreach ($feeTypesSelected as $t) {
    $t = trim((string)$t);
    if ($t === '') {
        continue;
    }
    if (Tools::strlen($t) > 160) {
        $t = Tools::substr($t, 0, 160);
    }
    $feeTypesSelectedClean[$t] = $t;
    if (count($feeTypesSelectedClean) >= 80) { // limit bezpieczeństwa
        break;
    }
}
$feeTypesSelected = array_values($feeTypesSelectedClean);

// Lista typów z billing_entry w wybranym zakresie dat (1:1 nazwy jak w Allegro)
$feeTypesAvailable = $this->listFeeTypesInBillingRange($selectedAccountIds, $dateFrom, $dateTo);

        // pagination
        $allowedPerPage = [25, 50, 100, 200];
        $perPage = (int)Tools::getValue('per_page', 50);
        if (!in_array($perPage, $allowedPerPage, true)) {
            $perPage = 50;
        }
        $page = max(1, (int)Tools::getValue('page', 1));

        $syncResult = null;
        $syncDebug = [];
        if (Tools::isSubmit('submitAllegroProBillingSync')) {
            if (count($selectedAccountIds) !== 1 || !$selectedAccount) {
                $this->errors[] = 'Synchronizacja opłat jest dostępna dla jednego konta naraz. Wybierz jedno konto i spróbuj ponownie.';
            } else {
                $http = new HttpClient();
                $api = new AllegroApiClient($http, $this->accounts);
                $sync = new BillingSyncService($api, $this->billingRepo);

                $syncResult = $sync->syncRange(
                    $selectedAccount,
                    $this->toIsoStart($dateFrom),
                    $this->toIsoEnd($dateTo),
                    $syncDebug
                );

                if (!empty($syncResult['ok'])) {
                    $this->confirmations[] = sprintf(
                        'Synchronizacja zakończona — pobrano %d operacji (nowe: %d, zaktualizowane: %d).',
                        (int)($syncResult['total'] ?? 0),
                        (int)($syncResult['inserted'] ?? 0),
                        (int)($syncResult['updated'] ?? 0)
                    );
                } else {
                    $this->errors[] = 'Nie udało się zsynchronizować opłat (billing-entries). Sprawdź autoryzację konta i logi.';
                }
            }
        }

        $report = new SettlementsReportService($this->billingRepo);
        if (!empty($selectedAccountIds)) {
            $summary = ($mode === 'orders')
                ? $report->getPeriodSummaryOrders($selectedAccountIds, $dateFrom, $dateTo, $orderState, $cancelledNoRefund, $feeGroup, $feeTypesSelected)
                : $report->getPeriodSummaryBilling($selectedAccountIds, $dateFrom, $dateTo, $orderState, $cancelledNoRefund, $feeGroup, $feeTypesSelected);
        } else {
            $summary = [];
        }


        // Liczba zamówień, z których wynika kwota „Sprzedaż brutto” (bez wpływu pola wyszukiwania)
        if (!empty($selectedAccountIds) && is_array($summary)) {
            $ordersCountForSales = ($mode === 'billing')
                ? $report->countOrdersBilling($selectedAccountIds, $dateFrom, $dateTo, '', $orderState, $cancelledNoRefund, $feeGroup, $feeTypesSelected)
                : $report->countOrders($selectedAccountIds, $dateFrom, $dateTo, '', $orderState, $cancelledNoRefund, $feeGroup, $feeTypesSelected);
            $summary['orders_count'] = (int)$ordersCountForSales;
        }

        $ordersTotal = 0;
        if (!empty($selectedAccountIds)) {
            $ordersTotal = ($mode === 'billing')
                ? $report->countOrdersBilling($selectedAccountIds, $dateFrom, $dateTo, $q, $orderState, $cancelledNoRefund, $feeGroup, $feeTypesSelected)
                : $report->countOrders($selectedAccountIds, $dateFrom, $dateTo, $q, $orderState, $cancelledNoRefund, $feeGroup, $feeTypesSelected);
        }

        $pages = (int)max(1, (int)ceil(($ordersTotal ?: 0) / max(1, $perPage)));
        if ($page > $pages) {
            $page = $pages;
        }
        $offset = max(0, ($page - 1) * $perPage);

        $ordersRows = [];
        if (!empty($selectedAccountIds)) {
            $ordersRows = ($mode === 'billing')
                ? $report->getOrdersWithFeesBilling($selectedAccountIds, $dateFrom, $dateTo, $q, $perPage, $offset, $orderState, $cancelledNoRefund, $feeGroup, $feeTypesSelected, $sortBy, $sortDir)
                : $report->getOrdersWithFeesOrders($selectedAccountIds, $dateFrom, $dateTo, $q, $perPage, $offset, $orderState, $cancelledNoRefund, $feeGroup, $feeTypesSelected, $sortBy, $sortDir);
        }

        $billingCount = (!empty($selectedAccountIds) && $mode === 'billing')
            ? $this->billingRepo->countInRangeMulti($selectedAccountIds, $dateFrom, $dateTo)
            : 0;


        // Podsumowanie "do zwrotu" (anulowane/nieopłacone): pobrane vs zwrócone vs kwota do zwrotu.
        $refundSummary = [];
        if (!empty($selectedAccountIds)) {
            $refundSummary = ($mode === 'billing')
                ? $report->getRefundPendingSummaryBilling($selectedAccountIds, $dateFrom, $dateTo, $q, $orderState, $cancelledNoRefund, $feeGroup, $feeTypesSelected)
                : $report->getRefundPendingSummaryOrders($selectedAccountIds, $dateFrom, $dateTo, $q, $orderState, $cancelledNoRefund, $feeGroup, $feeTypesSelected);
        }


        // Dane do wykresu kołowego (struktura opłat) na górze.
        $structureChartJson = '';
        if (!empty($summary)) {
            $feesOther = (float)($summary['fees_total'] ?? 0)
                - (float)($summary['fees_commission'] ?? 0)
                - (float)($summary['fees_smart'] ?? 0)
                - (float)($summary['fees_delivery'] ?? 0)
                - (float)($summary['fees_promotion'] ?? 0)
                - (float)($summary['fees_refunds'] ?? 0);

            $salesTotal = (float)($summary['sales_total'] ?? 0);
            $feesTotal = (float)($summary['fees_total'] ?? 0);

            // Koszty do wykresu: tylko ujemne pozycje (koszty). Rabaty/zwroty pokażemy obok jako korekty.
            $labels = [
                'commission' => 'Prowizje',
                'delivery' => 'Dostawa',
                'smart' => 'Smart',
                'promotion' => 'Promocja',
                'other' => 'Pozostałe',
            ];
            $amounts = [
                'commission' => (float)($summary['fees_commission'] ?? 0),
                'delivery' => (float)($summary['fees_delivery'] ?? 0),
                'smart' => (float)($summary['fees_smart'] ?? 0),
                'promotion' => (float)($summary['fees_promotion'] ?? 0),
                'other' => (float)$feesOther,
            ];

            $slices = [];
            $absTotalCosts = 0.0;
            foreach ($amounts as $k => $amt) {
                if ($amt < 0) {
                    $absTotalCosts += abs($amt);
                }
            }
            if ($absTotalCosts < 0.0001) {
                $absTotalCosts = 0.0;
            }

            foreach ($amounts as $k => $amt) {
                if ($amt >= 0) {
                    continue;
                }
                $v = abs($amt);
                $share = $absTotalCosts > 0 ? round($v / $absTotalCosts * 100, 2) : 0.0;
                $pctSales = $salesTotal > 0 ? round($v / $salesTotal * 100, 2) : 0.0;
                $slices[] = [
                    'key' => $k,
                    'label' => $labels[$k] ?? $k,
                    'value' => $v,
                    'amount' => $amt,
                    'share' => $share,
                    'pct_sales' => $pctSales,
                ];
            }

            $feesRatePct = $salesTotal > 0 ? round(abs($feesTotal) / $salesTotal * 100, 2) : 0.0;

            $structureChartJson = json_encode([
                'sales_total' => $salesTotal,
                'fees_total' => $feesTotal,
                'fees_rate_pct' => $feesRatePct,
                'refunds' => (float)($summary['fees_refunds'] ?? 0),
                'other' => (float)$feesOther,
                'slices' => $slices,
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        // Pagination links
        $base = $this->context->link->getAdminLink('AdminAllegroProSettlements');
        foreach ($selectedAccountIds as $aid) {
            $base .= '&id_allegropro_account[]=' . (int)$aid;
        }
        $base .= '&mode=' . urlencode($mode)
            . '&date_from=' . urlencode($dateFrom)
            . '&date_to=' . urlencode($dateTo)
            . '&q=' . urlencode($q)
            . '&order_state=' . urlencode($orderState)
            . ($cancelledNoRefund ? '&cancelled_no_refund=1' : '')
            . '&fee_group=' . urlencode($feeGroup)
            . $this->buildFeeTypesQueryString($feeTypesSelected)
            . '&per_page=' . (int)$perPage;


        $baseNoSort = $base;
        $base .= '&sort_by=' . urlencode($sortBy) . '&sort_dir=' . urlencode($sortDir);

        $pageLinks = $this->buildPageLinks($base, $page, $pages);

        $ordersFrom = $ordersTotal > 0 ? ($offset + 1) : 0;
        $ordersTo = min($ordersTotal, $offset + count($ordersRows));

        $ajaxUrl = $this->context->link->getAdminLink('AdminAllegroProSettlements');

        $issuesAllHistory = (int)Tools::getValue('issues_all', 0) ? true : false;
        $issuesRefundMode = (string)Tools::getValue('issues_refund', 'any');
        $issuesRefundMode = in_array($issuesRefundMode, ['any','balance_neg','no_refund'], true) ? $issuesRefundMode : 'any';
        $issuesAllowedPerPage = [25, 50, 100, 200];
        $issuesPerPage = (int)Tools::getValue('issues_per_page', 50);
        if (!in_array($issuesPerPage, $issuesAllowedPerPage, true)) {
            $issuesPerPage = 50;
        }
        $issuesPage = max(1, (int)Tools::getValue('issues_page', 1));
        $issuesTotal = 0;
        $issuesSummary = ['orders_count' => 0, 'billing_rows' => 0, 'fees_neg' => 0.0, 'refunds_pos' => 0.0, 'balance' => 0.0];
        $issuesBreakdown = [
            'api' => ['orders' => 0, 'fees_neg' => 0.0, 'refunds_pos' => 0.0, 'balance' => 0.0],
            'unpaid' => ['orders' => 0, 'fees_neg' => 0.0, 'refunds_pos' => 0.0, 'balance' => 0.0],
            'cancelled' => ['orders' => 0, 'fees_neg' => 0.0, 'refunds_pos' => 0.0, 'balance' => 0.0],
        ];
        $issuesRows = [];
        $issuesPages = 1;
        $issuesOffset = 0;
        $issuesFrom = 0;
        $issuesTo = 0;
        $issuesPageLinks = [];
        if (!empty($selectedAccountIds)) {
            $issuesSvc = new IssuesReportService($this->billingRepo);
            $issuesTotal = $issuesSvc->countIssuesOrders($selectedAccountIds, $dateFrom, $dateTo, $q, $feeGroup, $feeTypesSelected, $issuesAllHistory, $issuesRefundMode);
            $issuesSummary = $issuesSvc->getIssuesSummary($selectedAccountIds, $dateFrom, $dateTo, $q, $feeGroup, $feeTypesSelected, $issuesAllHistory, $issuesRefundMode);
            $issuesBreakdown = $issuesSvc->getIssuesBreakdown($selectedAccountIds, $dateFrom, $dateTo, $q, $feeGroup, $feeTypesSelected, $issuesAllHistory, $issuesRefundMode);
            $issuesPages = (int)max(1, (int)ceil(($issuesTotal ?: 0) / max(1, $issuesPerPage)));
            if ($issuesPage > $issuesPages) { $issuesPage = $issuesPages; }
            $issuesOffset = max(0, ($issuesPage - 1) * $issuesPerPage);
            $issuesRows = $issuesSvc->getIssuesRows($selectedAccountIds, $dateFrom, $dateTo, $q, $issuesPerPage, $issuesOffset, $feeGroup, $feeTypesSelected, $issuesAllHistory, $issuesRefundMode);

            // Map account label onto rows
            $accMap = [];
            foreach ($accounts as $a) {
                $aid = (int)($a['id_allegropro_account'] ?? 0);
                if (!$aid) {
                    continue;
                }
                $accMap[$aid] = (string)($a['label'] ?? $a['allegro_login'] ?? ('#' . $aid));
            }
            foreach ($issuesRows as &$r) {
                $aid = (int)($r['id_allegropro_account'] ?? 0);
                $r['account_label'] = $accMap[$aid] ?? ('#' . $aid);
            }
            unset($r);
        }


        // Paginacja problemów (oddzielna od listy zamówień)
        $issuesFrom = $issuesTotal > 0 ? ($issuesOffset + 1) : 0;
        $issuesTo = min($issuesTotal, $issuesOffset + count($issuesRows));

        // base URL dla paginacji problemów (zachowuje wszystkie filtry)
        $issuesBase = $baseNoSort
            . '&sort_by=' . urlencode($sortBy) . '&sort_dir=' . urlencode($sortDir)
            . '&page=' . (int)$page
            . '&issues_per_page=' . (int)$issuesPerPage
            . ($issuesAllHistory ? '&issues_all=1' : '')
            . ($issuesRefundMode !== 'any' ? '&issues_refund=' . urlencode($issuesRefundMode) : '');

        $issuesPageLinks = $this->buildPageLinks($issuesBase, $issuesPage, $issuesPages, 'issues_page');

        $this->context->smarty->assign([
            'accounts' => $accounts,
            'selected_account_id' => (int)($selectedAccountIds[0] ?? 0),
            'selected_account_ids' => $selectedAccountIds,
            'selected_account_labels' => $selectedAccountLabels,
            'selected_account_label' => $selectedAccountLabel,
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'q' => $q,
            'order_state' => $orderState,
            'cancelled_no_refund' => $cancelledNoRefund ? 1 : 0,
            'fee_group' => $feeGroup,
            'fee_types_selected' => $feeTypesSelected,
            'fee_types_available' => $feeTypesAvailable,
            'mode' => $mode,
            'sync_result' => $syncResult,
            'sync_debug' => $syncDebug,
            'summary' => $summary,
            'refund_summary' => $refundSummary,
            'structure_chart_json' => $structureChartJson,
            'orders_rows' => $ordersRows,
            'orders_total' => (int)$ordersTotal,
            'orders_from' => (int)$ordersFrom,
            'orders_to' => (int)$ordersTo,
            'issues_total' => (int)$issuesTotal,
            'issues_per_page' => (int)$issuesPerPage,
            'issues_page' => (int)$issuesPage,
            'issues_pages' => (int)$issuesPages,
            'issues_from' => (int)$issuesFrom,
            'issues_to' => (int)$issuesTo,
            'issues_page_links' => $issuesPageLinks,
            'issues_limit' => (int)$issuesPerPage,
            'issues_summary' => $issuesSummary,
            'issues_breakdown' => $issuesBreakdown,
            'issues_rows' => $issuesRows,
            'issues_all_history' => $issuesAllHistory ? 1 : 0,
            'issues_refund_mode' => $issuesRefundMode,
            'page' => (int)$page,
            'pages' => (int)$pages,
            'per_page' => (int)$perPage,
            'sort_by' => $sortBy,
            'sort_dir' => $sortDir,
            'orders_base_nosort' => $baseNoSort,
            'admin_orders_link' => $this->context->link->getAdminLink('AdminOrders'),
            'page_links' => $pageLinks,
            'billing_count' => (int)$billingCount,
            'settlements_link' => $this->context->link->getAdminLink('AdminAllegroProSettlements'),
            'ajax_url' => $ajaxUrl,
            'current_index' => self::$currentIndex,
            'token' => $this->token,
        ]);

        $this->setTemplate('settlements.tpl');
    }

    /**
     * AJAX: szczegóły zamówienia do modala.
     * URL: ...&ajax=1&action=OrderDetails&checkoutFormId=...&id_allegropro_account=...&date_from=...&date_to=...
     */
    public function ajaxProcessOrderDetails()
    {
        header('Content-Type: application/json; charset=utf-8');

        $accountId = (int)Tools::getValue('id_allegropro_account', 0);
        $checkout = (string)Tools::getValue('checkoutFormId', '');
        $dateFrom = $this->sanitizeYmd((string)Tools::getValue('date_from', '')) ?: date('Y-m-01');
        $dateTo = $this->sanitizeYmd((string)Tools::getValue('date_to', '')) ?: date('Y-m-d');

        if ($accountId <= 0 || trim($checkout) === '') {
            $this->ajaxDie(json_encode(['ok' => 0, 'error' => 'Brak parametrów.'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        }

        // ensure schema
        $this->billingRepo->ensureSchema();

        $report = new SettlementsReportService($this->billingRepo);
        $mode = (string)Tools::getValue('mode', 'billing');
        $mode = in_array($mode, ['billing', 'orders'], true) ? $mode : 'billing';
        $ignoreBillingDate = ($mode === 'orders');

        // fee filters (z URL) — żeby modal był spójny z listą
        $feeGroup = (string)Tools::getValue('fee_group', '');
        $allowedFeeGroups = ['', 'commission', 'delivery', 'smart', 'promotion', 'refunds', 'other'];
        if (!in_array($feeGroup, $allowedFeeGroups, true)) {
            $feeGroup = '';
        }

        $feeTypesSelected = Tools::getValue('fee_type', []);
        if (!is_array($feeTypesSelected)) {
            $feeTypesSelected = [$feeTypesSelected];
        }
        $feeTypesSelectedClean = [];
        foreach ($feeTypesSelected as $t) {
            $t = trim((string)$t);
            if ($t === '') {
                continue;
            }
            if (Tools::strlen($t) > 160) {
                $t = Tools::substr($t, 0, 160);
            }
            $feeTypesSelectedClean[$t] = $t;
            if (count($feeTypesSelectedClean) >= 80) {
                break;
            }
        }
        $feeTypesSelected = array_values($feeTypesSelectedClean);

        $details = $report->getOrderDetails($accountId, $checkout, $dateFrom, $dateTo, $ignoreBillingDate, $feeGroup, $feeTypesSelected);

        $order = is_array($details['order'] ?? null) ? $details['order'] : [];
        $cats = is_array($details['cats'] ?? null) ? $details['cats'] : [];

        $orderTotal = (float)($order['total_amount'] ?? 0);
        $feesTotal = (float)($cats['total'] ?? 0);

        $labels = [
            'commission' => 'Prowizje',
            'delivery' => 'Dostawa',
            'smart' => 'Smart',
            'promotion' => 'Promocja',
            'refunds' => 'Rabaty/zwroty',
            'other' => 'Pozostałe',
        ];

        $pct = [];
        foreach (['commission', 'delivery', 'smart', 'promotion', 'refunds', 'other'] as $k) {
            $amt = (float)($cats[$k] ?? 0);
            $pct[$k] = $orderTotal > 0 ? round(abs($amt) / $orderTotal * 100, 2) : 0.0;
        }
        $pctTotal = $orderTotal > 0 ? round(abs($feesTotal) / $orderTotal * 100, 2) : 0.0;

        // Pie: tylko koszty (ujemne) — bez refunds (bo to korekty dodatnie)
        $pie = [];
        $pieTotal = 0.0;
        foreach (['commission', 'delivery', 'smart', 'promotion', 'other'] as $k) {
            $amt = (float)($cats[$k] ?? 0);
            $v = $amt < 0 ? abs($amt) : 0.0;
            if ($v > 0.0001) {
                $pie[] = [
                    'key' => $k,
                    'label' => $labels[$k] ?? $k,
                    'value' => $v,
                    'amount' => $amt,
                ];
                $pieTotal += $v;
            }
        }
        foreach ($pie as &$s) {
            $s['share'] = $pieTotal > 0 ? round(((float)$s['value']) / $pieTotal * 100, 2) : 0.0;
        }
        unset($s);

        $items = [];
        $rawItems = is_array($details['items'] ?? null) ? $details['items'] : [];
        foreach ($rawItems as $it) {
            if (!is_array($it)) {
                continue;
            }
            $items[] = [
                'occurred_at' => (string)($it['occurred_at'] ?? ''),
                'type_id' => (string)($it['type_id'] ?? ''),
                'type_name' => (string)($it['type_name'] ?? ''),
                'category' => (string)($it['category'] ?? ''),
                'value_amount' => (float)($it['value_amount'] ?? 0),
                'offer_name' => (string)($it['offer_name'] ?? ''),
                'offer_id' => (string)($it['offer_id'] ?? ''),
            ];
        }

        $acc = $this->accounts->get($accountId);
        $accountLabel = $acc['label'] ?? '';

        $orderStatus = (string)($order['status'] ?? '');
        $currency = (string)($order['currency'] ?? 'PLN');
        $orderTotalAmount = (float)($order['order_total_amount'] ?? $orderTotal);
        $salesAmount = (float)($order['sales_amount'] ?? 0);
        $shippingAmount = (float)($order['shipping_amount'] ?? 0);

        $feesCharged = (float)($details['fees_charged'] ?? 0);
        $feesRefunded = (float)($details['fees_refunded'] ?? 0);
        $feesPending = (float)($details['fees_pending'] ?? 0);

        $this->ajaxDie(json_encode([
            'ok' => 1,
            'account_label' => (string)$accountLabel,
            'checkout_form_id' => (string)($order['checkout_form_id'] ?? $checkout),
            'buyer_login' => (string)($order['buyer_login'] ?? ''),
            'order_total' => $orderTotalAmount,
            'currency' => $currency,
            'order_status' => $orderStatus,
            'sales_amount' => $salesAmount,
            'shipping_amount' => $shippingAmount,
            'fees_charged' => $feesCharged,
            'fees_refunded' => $feesRefunded,
            'fees_pending' => $feesPending,
            'fees_total' => $feesTotal,
            'fees_rate_pct' => $pctTotal,
            'net_after_fees' => (float)($details['net_after_fees'] ?? ($orderTotal + $feesTotal)),
            'cats' => [
                'commission' => (float)($cats['commission'] ?? 0),
                'delivery' => (float)($cats['delivery'] ?? 0),
                'smart' => (float)($cats['smart'] ?? 0),
                'promotion' => (float)($cats['promotion'] ?? 0),
                'refunds' => (float)($cats['refunds'] ?? 0),
                'other' => (float)($cats['other'] ?? 0),
            ],
            'cats_pct' => $pct,
            'pie' => $pie,
            'items' => $items,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    /**
     * AJAX: lista operacji nieprzypisanych do zamówienia (order_id NULL/empty).
     * URL: ...&ajax=1&action=UnassignedList&id_allegropro_account[]=...&date_from=...&date_to=...&q=...&sign=any|neg|pos&page=1&per_page=50
     */
    public function ajaxProcessUnassignedList(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        // multi-account
        $rawAcc = Tools::getValue('id_allegropro_account', []);
        if (!is_array($rawAcc)) {
            $rawAcc = [$rawAcc];
        }
        $accountIds = [];
        foreach ($rawAcc as $v) {
            $id = (int)$v;
            if ($id > 0) {
                $accountIds[$id] = $id;
            }
        }
        $accountIds = array_values($accountIds);

        $dateFrom = $this->sanitizeYmd((string)Tools::getValue('date_from', '')) ?: date('Y-m-01');
        $dateTo = $this->sanitizeYmd((string)Tools::getValue('date_to', '')) ?: date('Y-m-d');
        $q = trim((string)Tools::getValue('q', ''));
        $sign = (string)Tools::getValue('sign', 'any');

        $allowedPerPage = [25, 50, 100, 200];
        $perPage = (int)Tools::getValue('per_page', 50);
        if (!in_array($perPage, $allowedPerPage, true)) {
            $perPage = 50;
        }
        $page = max(1, (int)Tools::getValue('page', 1));

        if (empty($accountIds)) {
            $this->ajaxDie(json_encode(['ok' => 0, 'error' => 'Brak konta.'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        }

        // fee filters (z URL) — spójne z widokiem
        $feeGroup = (string)Tools::getValue('fee_group', '');
        $allowedFeeGroups = ['', 'commission', 'delivery', 'smart', 'promotion', 'refunds', 'other'];
        if (!in_array($feeGroup, $allowedFeeGroups, true)) {
            $feeGroup = '';
        }

        $feeTypesSelected = Tools::getValue('fee_type', []);
        if (!is_array($feeTypesSelected)) {
            $feeTypesSelected = [$feeTypesSelected];
        }
        $feeTypesSelectedClean = [];
        foreach ($feeTypesSelected as $t) {
            $t = trim((string)$t);
            if ($t === '') {
                continue;
            }
            if (Tools::strlen($t) > 160) {
                $t = Tools::substr($t, 0, 160);
            }
            $feeTypesSelectedClean[$t] = $t;
            if (count($feeTypesSelectedClean) >= 80) {
                break;
            }
        }
        $feeTypesSelected = array_values($feeTypesSelectedClean);

        // ensure schema
        $this->billingRepo->ensureSchema();

        $svc = new UnassignedBillingReportService($this->billingRepo);
        $data = $svc->getUnassignedPage($accountIds, $dateFrom, $dateTo, $q, $sign, $page, $perPage, $feeGroup, $feeTypesSelected);

        // Map account labels
        $accMap = [];
        foreach ($this->accounts->all() as $a) {
            $aid = (int)($a['id_allegropro_account'] ?? 0);
            if ($aid <= 0) {
                continue;
            }
            $accMap[$aid] = (string)($a['label'] ?? $a['allegro_login'] ?? ('#' . $aid));
        }
        foreach (($data['items'] ?? []) as &$r) {
            $aid = (int)($r['id_allegropro_account'] ?? 0);
            $r['account_label'] = $accMap[$aid] ?? ('#' . $aid);
        }
        unset($r);

        $this->ajaxDie(json_encode(['ok' => 1, 'data' => $data], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    /**
     * AJAX: pełna lista operacji billing (RAW).
     * URL: ...&ajax=1&action=BillingRawList
     */
    public function ajaxProcessBillingRawList(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        $rawAcc = Tools::getValue('id_allegropro_account', []);
        if (!is_array($rawAcc)) {
            $rawAcc = [$rawAcc];
        }
        $accountIds = [];
        foreach ($rawAcc as $v) {
            $id = (int)$v;
            if ($id > 0) {
                $accountIds[$id] = $id;
            }
        }
        $accountIds = array_values($accountIds);

        if (empty($accountIds)) {
            $this->ajaxDie(json_encode(['ok' => 0, 'error' => 'Brak konta.'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        }

        $dateFrom = $this->sanitizeYmd((string)Tools::getValue('date_from', '')) ?: date('Y-m-01');
        $dateTo = $this->sanitizeYmd((string)Tools::getValue('date_to', '')) ?: date('Y-m-d');

        $rawQ = trim((string)Tools::getValue('raw_q', ''));
        $rawQ = Tools::substr($rawQ, 0, 140);

        $sign = (string)Tools::getValue('raw_sign', 'any');
        $assigned = (string)Tools::getValue('raw_assigned', 'any');
        $orderState = (string)Tools::getValue('order_state', 'all');

        $sortBy = (string)Tools::getValue('raw_sort_by', 'date');
        $sortDir = (string)Tools::getValue('raw_sort_dir', 'desc');

        // wariant eksportu: raw (lista) lub summary (dzień/konto/typ)
        $variant = strtolower(trim((string)Tools::getValue('export_raw_variant', 'raw')));
        if (!in_array($variant, ['raw', 'summary'], true)) {
            $variant = 'raw';
        }

        $allowedPerPage = [25, 50, 100, 200, 500];
        $perPage = (int)Tools::getValue('raw_per_page', 50);
        if (!in_array($perPage, $allowedPerPage, true)) {
            $perPage = 50;
        }
        $page = max(1, (int)Tools::getValue('raw_page', 1));

        // fee filters
        $feeGroup = (string)Tools::getValue('fee_group', '');
        $allowedFeeGroups = ['', 'commission', 'delivery', 'smart', 'promotion', 'refunds', 'other'];
        if (!in_array($feeGroup, $allowedFeeGroups, true)) {
            $feeGroup = '';
        }

        $feeTypesSelected = Tools::getValue('fee_type', []);
        if (!is_array($feeTypesSelected)) {
            $feeTypesSelected = [$feeTypesSelected];
        }
        $feeTypesSelectedClean = [];
        foreach ($feeTypesSelected as $t) {
            $t = trim((string)$t);
            if ($t === '') {
                continue;
            }
            if (Tools::strlen($t) > 160) {
                $t = Tools::substr($t, 0, 160);
            }
            $feeTypesSelectedClean[$t] = $t;
            if (count($feeTypesSelectedClean) >= 120) {
                break;
            }
        }
        $feeTypesSelected = array_values($feeTypesSelectedClean);

        // ensure schema
        $this->billingRepo->ensureSchema();

        $svc = new BillingRawReportService($this->billingRepo);
        $data = $svc->getPage(
            $accountIds,
            $dateFrom,
            $dateTo,
            $rawQ,
            $sign,
            $assigned,
            $orderState,
            $page,
            $perPage,
            $sortBy,
            $sortDir,
            $feeGroup,
            $feeTypesSelected
        );

        // Map account labels
        $accMap = [];
        foreach ($this->accounts->all() as $a) {
            $aid = (int)($a['id_allegropro_account'] ?? 0);
            if ($aid <= 0) {
                continue;
            }
            $accMap[$aid] = (string)($a['label'] ?? $a['allegro_login'] ?? ('#' . $aid));
        }
        foreach (($data['items'] ?? []) as &$r) {
            $aid = (int)($r['id_allegropro_account'] ?? 0);
            $r['account_label'] = $accMap[$aid] ?? ('#' . $aid);
        }
        unset($r);

        $this->ajaxDie(json_encode([
            'ok' => 1,
            'data' => $data,
            'admin_orders_link' => $this->context->link->getAdminLink('AdminOrders'),
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    /**
     * AJAX: ręczne pobranie billing-entries pod zakładkę "Rozliczenia Allegro" w szczegółach zamówienia.
     * URL: ...&ajax=1&action=syncOrderBilling
     * POST: id_allegropro_account, checkout_form_id, date_from, date_to, force_update
     */
    public function ajaxProcessSyncOrderBilling(): void
    {
        // ensure schema also for AJAX
        $this->billingRepo->ensureSchema();

        $accountId = (int)Tools::getValue('id_allegropro_account', 0);
        $checkoutFormId = trim((string)Tools::getValue('checkout_form_id', ''));
        $dateFrom = $this->sanitizeYmd((string)Tools::getValue('date_from', ''));
        $dateTo = $this->sanitizeYmd((string)Tools::getValue('date_to', ''));
        $rangeKind = (string)Tools::getValue('range_kind', 'narrow');
        $rangeKind = in_array($rangeKind, ['narrow', 'wide'], true) ? $rangeKind : 'narrow';

        // Optional: both ranges for debug / diagnostics
        $dateFromNarrow = $this->sanitizeYmd((string)Tools::getValue('date_from_narrow', '')) ?: $dateFrom;
        $dateToNarrow = $this->sanitizeYmd((string)Tools::getValue('date_to_narrow', '')) ?: $dateTo;
        $dateFromWide = $this->sanitizeYmd((string)Tools::getValue('date_from_wide', '')) ?: $dateFrom;
        $dateToWide = $this->sanitizeYmd((string)Tools::getValue('date_to_wide', '')) ?: $dateTo;
        $forceUpdate = (int)Tools::getValue('force_update', 0) ? true : false;
        $debugMode = (int)Tools::getValue('debug', 0) ? true : false;

        // ISO (selected range)
        $isoFrom = $this->toIsoStart($dateFrom ?: date('Y-m-d'));
        $isoTo = $this->toIsoEnd($dateTo ?: date('Y-m-d'));

        // ISO for both ranges (for debug)
        $isoNarrowFrom = $this->toIsoStart($dateFromNarrow ?: $dateFrom ?: date('Y-m-d'));
        $isoNarrowTo = $this->toIsoEnd($dateToNarrow ?: $dateTo ?: date('Y-m-d'));
        $isoWideFrom = $this->toIsoStart($dateFromWide ?: $dateFrom ?: date('Y-m-d'));
        $isoWideTo = $this->toIsoEnd($dateToWide ?: $dateTo ?: date('Y-m-d'));

        // binding counters (after sync)
        $boundByPayment = 0;
        $boundByRaw = 0;

        if ($accountId <= 0 || $checkoutFormId === '' || $dateFrom === '' || $dateTo === '') {
            $this->ajaxJson(['ok' => 0, 'error' => 'Brak parametrów (konto / checkoutFormId / zakres dat).']);
            return;
        }

        $acc = $this->accounts->get($accountId);
        if (!$acc || empty($acc['access_token'])) {
            $this->ajaxJson(['ok' => 0, 'error' => 'Brak autoryzacji konta Allegro (access_token).']);
            return;
        }

        // IMPORTANT (P1 performance): domyślnie w szczegółach zamówienia NIE wykonujemy globalnego sync po dacie.
        // Globalny sync może przeglądać dziesiątki tysięcy billing-entries (offsety 0..10k) nawet gdy
        // użytkownik chce tylko koszty dla 1 transakcji. W order-view domyślnie korzystamy wyłącznie z TARGET (order.id).
        // Wyjątek: jeśli użytkownik zaznaczy "Pełna aktualizacja wszystkich pozycji" (force_update=1),
        // to uruchamiamy pełny/globalny sync w wybranym zakresie dat (świadomie wolniejsze).
        $svc = new OrderBillingManualSyncService();
        if ($forceUpdate) {
            $res = $svc->syncRangeForAccount($accountId, $isoFrom, $isoTo, true);
            if (!isset($res['debug']) || !is_array($res['debug'])) {
                $res['debug'] = [];
            }
            array_unshift($res['debug'], '[SYNC] full enabled: global range sync (force_update=1)');
        } else {
            $res = [
                'ok' => true,
                'code' => 200,
                'total' => 0,
                'inserted' => 0,
                'updated' => 0,
                'debug' => ['[SYNC] skipped: per-order mode (TARGET order.id only)'],
            ];
        }

        // TARGETED SYNC: dociągnij wpisy billing z Allegro filtrowane po order.id (pełny payload),
        // bo globalny sync po dacie czasem nie zawiera pól order dla części typów opłat.
        $target = ['ok' => true, 'code' => 200, 'got' => 0, 'inserted' => 0, 'updated' => 0];
        $targetDebug = [];
        $payIdTarget = ''; // payment.id disabled (order.id is enough)
        try {
            // Zawsze forceUpdateAll=TRUE dla target sync (mała paczka, chcemy uzupełnić braki w istniejących rekordach).
            $target = $svc->syncTargetedForOrder($accountId, $isoFrom, $isoTo, $checkoutFormId, $payIdTarget, $targetDebug, true);
        } catch (\Throwable $e) {
            $target = ['ok' => false, 'code' => 0, 'got' => 0, 'inserted' => 0, 'updated' => 0];
            $targetDebug[] = '[TARGET] exception: ' . $e->getMessage();
        }

        if (!empty($targetDebug)) {
            // dopnij do debug globalnego (niezależnie od trybu, ale przycinamy w payloadzie)
            $res['debug'] = array_merge((array)($res['debug'] ?? []), $targetDebug);
        }

        // W order-view (tryb domyślny) traktujemy wynik TARGET jako wynik "synchronizacji" (dla UX komunikatów w JS).
        // Dzięki temu komunikat "pobrano X" pokazuje faktyczną liczbę dopasowanych wpisów dla zamówienia,
        // a nie 0 (bo globalny sync po dacie jest tutaj wyłączony).
        // Jeśli force_update=1, wtedy w UX pokazujemy wynik pełnego/globalnego sync (res.total),
        // a TARGET pozostaje jako uzupełnienie (dla konkretnego zamówienia).
        if (!$forceUpdate) {
            $res['total'] = (int)($target['got'] ?? 0);
            $res['inserted'] = (int)($target['inserted'] ?? 0);
            $res['updated'] = (int)($target['updated'] ?? 0);
        }



        // Helper: build candidates for IN/LIKE
        $cf = preg_replace('/[^A-Za-z0-9_-]/', '', $checkoutFormId);
        $cf = $cf ?: $checkoutFormId;

        $candidates = [];
        $candidates[] = $cf;
        $candidates[] = str_replace('_', '-', $cf);
        $candidates[] = str_replace('-', '_', $cf);
        $candidates[] = str_replace(['-','_'], '', $cf);
        $candidates[] = strtolower($cf);
        $candidates[] = strtoupper($cf);
        $candidates = array_values(array_unique(array_filter(array_map('trim', $candidates))));

        $diag = null;

        if (empty($res['ok'])) {
            $payload = [
                'ok' => 0,
                'error' => 'Nie udało się pobrać billing-entries. Sprawdź autoryzację/logi.',
                'code' => (int)($res['code'] ?? 0),
                'total' => (int)($res['total'] ?? 0),
                'inserted' => (int)($res['inserted'] ?? 0),
                'updated' => (int)($res['updated'] ?? 0),
            'target_got' => (int)($target['got'] ?? 0),
            'target_inserted' => (int)($target['inserted'] ?? 0),
            'target_updated' => (int)($target['updated'] ?? 0),
                'iso_from' => $isoFrom,
                'iso_to' => $isoTo,
                'debug_tail' => array_slice((array)($res['debug'] ?? []), -6),
            ];

            if ($debugMode) {
                $payload['debug'] = array_slice((array)($res['debug'] ?? []), -120);
                $payload['diag'] = [
                    'account_id' => $accountId,
                    'checkout_form_id' => $checkoutFormId,
                    'date_from' => $dateFrom,
                    'date_to' => $dateTo,
                    'iso_from' => $isoFrom,
                    'iso_to' => $isoTo,
                    'ranges' => [
                        'selected' => $rangeKind,
                        'narrow' => [
                            'date_from' => $dateFromNarrow,
                            'date_to' => $dateToNarrow,
                            'iso_from' => $isoNarrowFrom,
                            'iso_to' => $isoNarrowTo,
                        ],
                        'wide' => [
                            'date_from' => $dateFromWide,
                            'date_to' => $dateToWide,
                            'iso_from' => $isoWideFrom,
                            'iso_to' => $isoWideTo,
                        ],
                    ],
                    'note' => 'Synchronizacja API zwróciła błąd — brak dalszej diagnostyki DB.',
                ];
            }

            $this->ajaxJson($payload);
            return;
        }

        // Po synchronizacji spróbuj dopiąć wpisy do tego zamówienia (część typów ma tylko payment_id albo order_id schowane w raw_json).
        try {
            $payRow = Db::getInstance()->getRow("SELECT payment_id, finished_at FROM `" . _DB_PREFIX_ . "allegropro_order_payment` WHERE checkout_form_id='" . pSQL($checkoutFormId) . "' ORDER BY id_allegropro_payment DESC");
            $payId = is_array($payRow) ? trim((string)($payRow['payment_id'] ?? '')) : '';
            if ($payId !== '') {
                try { $boundByPayment += (int)$this->billingRepo->attachOrderIdByPayment($accountId, $payId, $checkoutFormId); } catch (\Throwable $e) {}
            }
            try { $boundByRaw += (int)$this->billingRepo->attachOrderIdByRawJsonCandidates($accountId, $checkoutFormId, $candidates, $dateFrom, $dateTo); } catch (\Throwable $e) {}
        } catch (\Throwable $e) {
            // ignore
        }

        $bound = (int)$boundByPayment + (int)$boundByRaw;

        // Diagnostics (only when debug enabled)
        if ($debugMode) {
            // API probe: sprawdź, czy Allegro w ogóle zwraca wpisy dla order.id / payment.id w obu zakresach.
            $apiProbe = null;
            try {
                $http = new HttpClient();
                $api = new AllegroApiClient($http, $this->accounts);

                $probe = function (string $isoFromX, string $isoToX, array $extra) use ($api, $acc): array {
                    $q = array_merge([
                        'occurredAt.gte' => $isoFromX,
                        'occurredAt.lte' => $isoToX,
                        'limit' => 5,
                        'offset' => 0,
                    ], $extra);
                    $r = $api->get($acc, '/billing/billing-entries', $q);
                    $got = null;
                    $note = null;
                    if (!empty($r['ok']) && is_array($r['json'])) {
                        $list = $r['json']['billingEntries'] ?? [];
                        $got = is_array($list) ? count($list) : null;
                    } else {
                        // Try to extract error message
                        if (is_array($r['json'] ?? null) && !empty($r['json']['errors'][0]['message'])) {
                            $note = (string)$r['json']['errors'][0]['message'];
                        } elseif (!empty($r['error'])) {
                            $note = (string)$r['error'];
                        } else {
                            $note = 'API error';
                        }
                    }
                    return [
                        'http' => (int)($r['code'] ?? 0),
                        'ok' => !empty($r['ok']) ? 1 : 0,
                        'got' => $got,
                        'note' => $note,
                    ];
                };

                // get payment id (already fetched later as payIdDiag)
                $payIdProbe = ''; // payment.id disabled
                $apiProbe = [
                    'narrow' => [
                        'order' => $probe($isoNarrowFrom, $isoNarrowTo, ['order.id' => $checkoutFormId]),
                        'payment' => ['http' => 0, 'ok' => 0, 'got' => null, 'note' => 'payment.id disabled (order.id used)'],
                    ],
                    'wide' => [
                        'order' => $probe($isoWideFrom, $isoWideTo, ['order.id' => $checkoutFormId]),
                        'payment' => ['http' => 0, 'ok' => 0, 'got' => null, 'note' => 'payment.id disabled (order.id used)'],
                    ],
                ];
            } catch (\Throwable $e) {
                $apiProbe = [
                    'error' => $e->getMessage(),
                ];
            }

            $fromSql = pSQL($dateFrom . ' 00:00:00');
            $toSql = pSQL($dateTo . ' 23:59:59');

            $vals = [];
            foreach ($candidates as $c) {
                $vals[] = "'" . pSQL($c) . "'";
                if (count($vals) >= 20) break;
            }
            $inSql = $vals ? implode(',', $vals) : ("'" . pSQL($checkoutFormId) . "'");

            $likeParts = [];
            foreach ($candidates as $c) {
                $likeParts[] = "raw_json LIKE '%" . pSQL($c) . "%'";
                if (count($likeParts) >= 10) break;
            }
            $likeSql = $likeParts ? implode(' OR ', $likeParts) : "raw_json LIKE '%" . pSQL($checkoutFormId) . "%'";

            $table = _DB_PREFIX_ . "allegropro_billing_entry";

            $cntInRange = (int)Db::getInstance()->getValue("SELECT COUNT(*) FROM `{$table}` WHERE id_allegropro_account=" . (int)$accountId . " AND occurred_at BETWEEN '{$fromSql}' AND '{$toSql}'");
            $cntOrderInRange = (int)Db::getInstance()->getValue("SELECT COUNT(*) FROM `{$table}` WHERE id_allegropro_account=" . (int)$accountId . " AND order_id IN ({$inSql}) AND occurred_at BETWEEN '{$fromSql}' AND '{$toSql}'");
            $cntOrderAll = (int)Db::getInstance()->getValue("SELECT COUNT(*) FROM `{$table}` WHERE id_allegropro_account=" . (int)$accountId . " AND order_id IN ({$inSql})");
            $cntRawInRange = (int)Db::getInstance()->getValue("SELECT COUNT(*) FROM `{$table}` WHERE id_allegropro_account=" . (int)$accountId . " AND occurred_at BETWEEN '{$fromSql}' AND '{$toSql}' AND ({$likeSql})");

            // payment_id column? (robust: information_schema + fallback)
            $hasPaymentIdCol = false;
            try {
                $cntCol = Db::getInstance()->getValue(
                    "SELECT COUNT(*) FROM information_schema.COLUMNS "
                    . "WHERE TABLE_SCHEMA=DATABASE() "
                    . "AND TABLE_NAME='" . pSQL($table) . "' "
                    . "AND COLUMN_NAME='payment_id'"
                );
                $hasPaymentIdCol = ((int)$cntCol) > 0;
            } catch (\Throwable $e) {
                $hasPaymentIdCol = false;
            }
            if (!$hasPaymentIdCol) {
                try {
                    $col = Db::getInstance()->getValue("SHOW COLUMNS FROM `{$table}` LIKE 'payment_id'");
                    $hasPaymentIdCol = !empty($col);
                } catch (\Throwable $e) {
                    $hasPaymentIdCol = false;
                }
            }

            $cntPayInRange = null;
            $payIdDiag = '';
            try {
                $payRow2 = Db::getInstance()->getRow("SELECT payment_id, finished_at FROM `" . _DB_PREFIX_ . "allegropro_order_payment` WHERE checkout_form_id='" . pSQL($checkoutFormId) . "' ORDER BY id_allegropro_payment DESC");
                $payIdDiag = is_array($payRow2) ? trim((string)($payRow2['payment_id'] ?? '')) : '';
            } catch (\Throwable $e) {
                $payIdDiag = '';
            }
            if ($hasPaymentIdCol && $payIdDiag !== '') {
                $cntPayInRange = (int)Db::getInstance()->getValue("SELECT COUNT(*) FROM `{$table}` WHERE id_allegropro_account=" . (int)$accountId . " AND payment_id='" . pSQL($payIdDiag) . "' AND occurred_at BETWEEN '{$fromSql}' AND '{$toSql}'");
            }

            $selCols = "billing_entry_id, occurred_at, type_name, value_amount, order_id, offer_id";
            if ($hasPaymentIdCol) {
                $selCols .= ", payment_id";
            }

            $sampleOrder = Db::getInstance()->executeS("SELECT {$selCols} FROM `{$table}` WHERE id_allegropro_account=" . (int)$accountId . " AND order_id IN ({$inSql}) ORDER BY occurred_at DESC LIMIT 10") ?: [];
            $sampleRaw = Db::getInstance()->executeS("SELECT {$selCols} FROM `{$table}` WHERE id_allegropro_account=" . (int)$accountId . " AND occurred_at BETWEEN '{$fromSql}' AND '{$toSql}' AND ({$likeSql}) ORDER BY occurred_at DESC LIMIT 10") ?: [];

            $diag = [
                'account_id' => $accountId,
                'checkout_form_id' => $checkoutFormId,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'iso_from' => $isoFrom,
                'iso_to' => $isoTo,
                'force_update' => $forceUpdate ? 1 : 0,
                'ranges' => [
                    'selected' => $rangeKind,
                    'narrow' => [
                        'date_from' => $dateFromNarrow,
                        'date_to' => $dateToNarrow,
                        'iso_from' => $isoNarrowFrom,
                        'iso_to' => $isoNarrowTo,
                    ],
                    'wide' => [
                        'date_from' => $dateFromWide,
                        'date_to' => $dateToWide,
                        'iso_from' => $isoWideFrom,
                        'iso_to' => $isoWideTo,
                    ],
                ],
                'api_probe' => $apiProbe,
                'sync' => [
                    'total' => (int)($res['total'] ?? 0),
                    'inserted' => (int)($res['inserted'] ?? 0),
                    'updated' => (int)($res['updated'] ?? 0),
                ],
                'target' => [
                    'got' => (int)($target['got'] ?? 0),
                    'inserted' => (int)($target['inserted'] ?? 0),
                    'updated' => (int)($target['updated'] ?? 0),
                ],
                'bound' => [
                    'by_payment' => (int)$boundByPayment,
                    'by_raw_json' => (int)$boundByRaw,
                    'total' => (int)$bound,
                ],
                'db' => [
                    'account_entries_in_range' => $cntInRange,
                    'order_entries_in_range' => $cntOrderInRange,
                    'order_entries_all_time' => $cntOrderAll,
                    'raw_json_matches_in_range' => $cntRawInRange,
                    'payment_id_matches_in_range' => $cntPayInRange,
                    'payment_id' => $payIdDiag ?: null,
                    'candidates' => $candidates,
                ],
                'samples' => [
                    'by_order_id' => $sampleOrder,
                    'by_raw_json' => $sampleRaw,
                ],
                'hint' => ($cntOrderAll > 0)
                    ? 'Wpisy billing są w DB i mają order_id — jeśli nie widać w UI, problem leży w renderze/filtrach.'
                    : (($cntRawInRange > 0)
                        ? 'Wpisy w DB zawierają checkoutFormId w raw_json, ale nie mają order_id — powinny zostać przypięte po sync (sprawdź bound_by_raw_json).'
                        : 'Brak jakichkolwiek dopasowań checkoutFormId w DB (order_id i raw_json). To sugeruje: (a) wpisy nie zostały pobrane w tym zakresie, albo (b) Allegro API nie zwraca identyfikatora zamówienia w billing-entries dla tych operacji.'),
            ];
        }

        $payload = [
            'ok' => 1,
            'total' => (int)($res['total'] ?? 0),
            'inserted' => (int)($res['inserted'] ?? 0),
            'updated' => (int)($res['updated'] ?? 0),
            'bound' => (int)$bound,
            'bound_by_payment' => (int)$boundByPayment,
            'bound_by_raw' => (int)$boundByRaw,
            'iso_from' => $isoFrom,
            'iso_to' => $isoTo,
            'debug_tail' => array_slice((array)($res['debug'] ?? []), -4),
        ];

        if ($debugMode) {
            $payload['debug'] = array_slice((array)($res['debug'] ?? []), -120);
            $payload['diag'] = $diag;
        }

        $this->ajaxJson($payload);
    }



    /* ============================
     * AJAX: krokowa synchronizacja + uzupełnianie braków zamówień
     * ============================ */

    private function ajaxJson(array $payload): void
    {
        // Minimalna ochrona przed "zanieczyszczeniem" odpowiedzi (notice/warning/HTML),
        // które psuje JSON.parse po stronie przeglądarki.
        if (function_exists('ob_get_length') && ob_get_length()) {
            @ob_clean();
        }
        if (!headers_sent()) {
            header('Content-Type: application/json; charset=utf-8');
        }
        $this->ajaxDie(json_encode($payload, JSON_UNESCAPED_UNICODE));
    }

    public function ajaxProcessBillingSyncStep(): void
    {
        // ensure billing schema (columns/indexes) also for AJAX
        $this->billingRepo->ensureSchema();
        $accountId = (int)Tools::getValue('account_id', 0);
        $dateFrom = (string)Tools::getValue('date_from', '');
        $dateTo = (string)Tools::getValue('date_to', '');
        $offset = (int)Tools::getValue('offset', 0);
        $limit = (int)Tools::getValue('limit', 100);

        // Tryb synchronizacji:
        // - inc  => szybka: tylko nowe + uzupełnianie braków w istniejących wpisach
        // - full => pełna: wymuś aktualizację wszystkich wpisów w zakresie (wolniej)
        $syncMode = (string)Tools::getValue('sync_mode', 'inc');
        $syncMode = in_array($syncMode, ['inc', 'full'], true) ? $syncMode : 'inc';
        $effectiveDateFrom = (string)Tools::getValue('effective_date_from', '');
        $effectiveDateFrom = $this->sanitizeYmd($effectiveDateFrom) ?: '';

        $acc = $this->accounts->get($accountId);
        if (!$accountId || !is_array($acc)) {
            $this->ajaxJson(['ok' => 0, 'error' => 'Brak konta.']);
            return;
        }

        $http = new HttpClient();
        $api = new AllegroApiClient($http, $this->accounts);
        $sync = new BillingSyncService($api, $this->billingRepo);

        // W trybie "inc" przy pierwszym kroku zawężamy datę startową do "ostatnie 2 dni"
        // od ostatniego istniejącego wpisu w bazie (w ramach wybranego zakresu).
        // To znacząco ogranicza liczbę requestów i czas DB.
        $dateFromUse = $dateFrom;
        if ($syncMode === 'inc') {
            if ($effectiveDateFrom !== '') {
                $dateFromUse = $effectiveDateFrom;
            } elseif ($offset === 0) {
                $dateFromUse = $this->computeIncrementalDateFrom($accountId, $dateFrom, $dateTo);
                $effectiveDateFrom = $this->sanitizeYmd($dateFromUse) ?: $effectiveDateFrom;
            }
        }

        $debug = [];
        $step = $sync->syncRangeStep(
            $acc,
            $this->toIsoStart($dateFromUse),
            $this->toIsoEnd($dateTo),
            $offset,
            $limit,
            $debug,
            $syncMode === 'full'
        );

        $this->ajaxJson([
            'ok' => !empty($step['ok']) ? 1 : 0,
            'code' => (int)($step['code'] ?? 0),
            'got' => (int)($step['got'] ?? 0),
            'inserted' => (int)($step['inserted'] ?? 0),
            'updated' => (int)($step['updated'] ?? 0),
            'next_offset' => (int)($step['next_offset'] ?? $offset),
            'done' => !empty($step['done']) ? 1 : 0,
            'effective_date_from' => $effectiveDateFrom,
            'debug_tail' => array_slice($debug, -2),
        ]);
    }

    /**
     * Szybka synchronizacja: jeśli w DB mamy już dane w zakresie, pobieraj tylko "końcówkę".
     * Zostawiamy bufor 2 dni, bo korekty mogą się pojawić z lekkim opóźnieniem.
     */
    private function computeIncrementalDateFrom(int $accountId, string $dateFrom, string $dateTo): string
    {
        $dateFrom = $this->sanitizeYmd($dateFrom) ?: date('Y-m-01');
        $dateTo = $this->sanitizeYmd($dateTo) ?: date('Y-m-d');

        try {
            $max = $this->billingRepo->getMaxOccurredAtInRange($accountId, $dateFrom, $dateTo);
        } catch (\Throwable $e) {
            $max = null;
        }

        if (!$max) {
            return $dateFrom;
        }

        $ts = strtotime($max);
        if (!$ts) {
            return $dateFrom;
        }

        $buf = strtotime('-2 days', $ts);
        $bufDate = date('Y-m-d', $buf ?: $ts);
        // nie schodź poniżej wybranego date_from
        if ($bufDate < $dateFrom) {
            return $dateFrom;
        }
        return $bufDate;
    }

    private function normalizeIdSql(string $expr): string
    {
        // usuń "-" i "_" oraz ignoruj wielkość liter
        return "LOWER(REPLACE(REPLACE(IFNULL({$expr},''),'-',''),'_',''))";
    }

    private function feeWhereSql(string $typeNameExpr): string
    {
        // UWAGA: BillingEntryRepository::buildFeeWhereSql() bywa prywatne w części wdrożeń.
        // Nie możemy tego wołać bez ryzyka fatal error (co psuje AJAX i daje JSON.parse error po stronie UI).
        // Implementujemy tu tę samą logikę "opłat".

        // Zasada:
        // - bierzemy wszystkie ujemne operacje (opłaty)
        // - oraz dodatnie tylko wtedy, gdy są korektą/rabatem/zwrotem
        // - wykluczamy przepływy środków (wpłaty/wypłaty/przelewy/"środki")

        $include = "(b.value_amount < 0 OR {$typeNameExpr} LIKE '%zwrot%' OR {$typeNameExpr} LIKE '%rabat%' OR {$typeNameExpr} LIKE '%korekt%' OR {$typeNameExpr} LIKE '%rekompens%')";
        $exclude = "({$typeNameExpr} LIKE '%wypł%' OR {$typeNameExpr} LIKE '%wypl%' OR {$typeNameExpr} LIKE '%wpł%' OR {$typeNameExpr} LIKE '%wpl%' OR {$typeNameExpr} LIKE '%przelew%' OR {$typeNameExpr} LIKE '%środk%' OR {$typeNameExpr} LIKE '%srodk%')";
        return "({$include} AND NOT {$exclude})";
    }

    /**
     * Tabela "blacklist" dla ID, których Allegro konsekwentnie nie zwraca (HTTP 404).
     * Chroni przed zapętlaniem się enrichmentu i zaśmiecaniem logów.
     */
    private function ensureEnrichSkipSchema(): void
    {
        $this->enrichSkip->ensureSchema();
    }

    private function markEnrichSkip(int $accountId, string $orderId, int $code, string $error = ''): void
    {
        $this->enrichSkip->mark($accountId, $orderId, $code, $error);
        // persist into billing entries for fast UI filtering (Do wyjaśnienia)
        try {
            $this->billingRepo->setOrderError($accountId, $orderId, $code, $error);
        } catch (\Throwable $e) {
            // ignore
        }
    }


private function prepareOrderFilledRange(int $accountId, string $dateFrom, string $dateTo): void
{
    $p = _DB_PREFIX_;

    // 1) N/A rows (no order_id) => mark as filled
    try {
        Db::getInstance()->execute(
            "UPDATE `{$p}allegropro_billing_entry`
             SET order_filled=1,
                 order_error_code=NULL,
                 order_error=NULL,
                 order_error_at=NULL
             WHERE id_allegropro_account=" . (int)$accountId . "
               AND order_filled=0
               AND (order_id IS NULL OR order_id='')"
        );
    } catch (\Throwable $e) {
        // ignore
    }

    // 2) Orders that are already complete locally => mark as filled (use EXISTS to avoid join explosion)
    $normB = $this->normalizeIdSql('b.order_id');
    $normO = $this->normalizeIdSql('o.checkout_form_id');
    $normOi = $this->normalizeIdSql('oi.checkout_form_id');
    $normSh = $this->normalizeIdSql('sh.checkout_form_id');
    $normOb = $this->normalizeIdSql('ob.checkout_form_id');

    $sql = "UPDATE `{$p}allegropro_billing_entry` b
            SET b.order_filled=1,
                b.order_error_code=NULL,
                b.order_error=NULL,
                b.order_error_at=NULL
            WHERE b.id_allegropro_account=" . (int)$accountId . "
              AND b.order_filled=0
              AND b.order_id IS NOT NULL AND b.order_id <> ''
              AND EXISTS (
                    SELECT 1 FROM `{$p}allegropro_order` o
                    WHERE o.id_allegropro_account=b.id_allegropro_account
                      AND {$normO} = {$normB}
                      AND IFNULL(o.total_amount,0) > 0
                      AND IFNULL(o.buyer_login,'') <> ''
                      AND IFNULL(o.status,'') <> ''
              )
              AND EXISTS (SELECT 1 FROM `{$p}allegropro_order_item` oi WHERE {$normOi} = {$normB} LIMIT 1)
              AND EXISTS (SELECT 1 FROM `{$p}allegropro_order_shipping` sh WHERE {$normSh} = {$normB} LIMIT 1)
              AND EXISTS (SELECT 1 FROM `{$p}allegropro_order_buyer` ob WHERE {$normOb} = {$normB} LIMIT 1)";

    try {
        Db::getInstance()->execute($sql);
    } catch (\Throwable $e) {
        // ignore
    }
}


    private function markBillingOrderFilled(int $accountId, string $orderId): void
    {
        $orderId = trim($orderId);
        if (!$accountId || $orderId === '') {
            return;
        }
        $p = _DB_PREFIX_;
        $norm = preg_replace('/[^0-9a-z]/i', '', strtolower($orderId));
        if ($norm === '') {
            return;
        }
        $normEsc = pSQL($norm);
        $sql = "UPDATE `{$p}allegropro_billing_entry`
                SET order_filled=1,
                    order_error_code=NULL,
                    order_error=NULL,
                    order_error_at=NULL
                WHERE id_allegropro_account=" . (int)$accountId . "
                  AND order_filled=0
                  AND " . $this->normalizeIdSql('order_id') . " = '{$normEsc}'";
        try {
            Db::getInstance()->execute($sql);
        } catch (\Throwable $e) {
            // ignore
        }
    }


private function countMissingOrders(int $accountId, string $dateFrom, string $dateTo): int
{
    $this->ensureEnrichSkipSchema();
    $this->prepareOrderFilledRange($accountId, $dateFrom, $dateTo);

    // Backfill error markers from enrich-skip (older runs could have errors only in skip table).
    try { $this->billingRepo->backfillOrderErrorsFromSkip($accountId); } catch (\Throwable $e) {}

    $feeWhere = $this->feeWhereSql("LOWER(IFNULL(b.type_name,''))");

    $normS = $this->normalizeIdSql('s.order_id');
    $normB = $this->normalizeIdSql('b.order_id');

    $sql = "SELECT COUNT(DISTINCT b.order_id) AS cnt
            FROM `" . _DB_PREFIX_ . "allegropro_billing_entry` b
            LEFT JOIN `" . _DB_PREFIX_ . "allegropro_order_enrich_skip` s
              ON (s.id_allegropro_account=b.id_allegropro_account AND {$normS} = {$normB}
                  AND COALESCE(s.skip_until, DATE_ADD(s.last_attempt_at, INTERVAL 30 MINUTE)) > NOW())
            WHERE b.id_allegropro_account=" . (int)$accountId . "
              AND b.order_filled=0
              AND b.order_id IS NOT NULL AND b.order_id <> ''
              AND {$feeWhere}
              AND s.id_allegropro_order_enrich_skip IS NULL";

    $row = Db::getInstance()->getRow($sql) ?: [];
    return (int)($row['cnt'] ?? 0);
}


    /**
     * Zwraca brakujące ID zamówień w stabilny sposób.
     *
     * WAŻNE: nie używamy OFFSET na zmieniającym się zbiorze (po uzupełnieniu danych liczba braków spada,
     * a OFFSET powodował pomijanie części rekordów). Zamiast tego stosujemy kursor (last_at + order_id).
     */
    
private function listMissingOrderRows(int $accountId, string $dateFrom, string $dateTo, int $limit, int $offset = 0, string $cursorLastAt = '', string $cursorOrderId = ''): array
{
    $this->ensureEnrichSkipSchema();
    $this->prepareOrderFilledRange($accountId, $dateFrom, $dateTo);

    // Backfill error markers from enrich-skip (older runs could have errors only in skip table).
    try { $this->billingRepo->backfillOrderErrorsFromSkip($accountId); } catch (\Throwable $e) {}

    $feeWhere = $this->feeWhereSql("LOWER(IFNULL(b.type_name,''))");

    $normS = $this->normalizeIdSql('s.order_id');
    $normB = $this->normalizeIdSql('b.order_id');

    // Cursor-based pagination (stable on shrinking set)
    $cursorCond = '';
    $cursorLastAt = trim($cursorLastAt);
    $cursorOrderId = trim($cursorOrderId);
    if ($cursorLastAt !== '' && preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $cursorLastAt)) {
        $cAt = pSQL($cursorLastAt);
        if ($cursorOrderId !== '') {
            $cId = pSQL($cursorOrderId);
            $cursorCond = " AND (MAX(b.occurred_at) < '{$cAt}' OR (MAX(b.occurred_at) = '{$cAt}' AND b.order_id < '{$cId}'))";
        } else {
            $cursorCond = " AND MAX(b.occurred_at) < '{$cAt}'";
        }
    } else {
        $cursorLastAt = '';
    }

    $limit = max(1, min(50, (int)$limit));
    $offset = max(0, (int)$offset);

    $limitSql = ($cursorLastAt !== '')
        ? "LIMIT " . (int)$limit
        : "LIMIT " . (int)$offset . ", " . (int)$limit;

    $sql = "SELECT b.order_id, MAX(b.occurred_at) as last_at
            FROM `" . _DB_PREFIX_ . "allegropro_billing_entry` b
            LEFT JOIN `" . _DB_PREFIX_ . "allegropro_order_enrich_skip` s
              ON (s.id_allegropro_account=b.id_allegropro_account AND {$normS} = {$normB}
                  AND COALESCE(s.skip_until, DATE_ADD(s.last_attempt_at, INTERVAL 30 MINUTE)) > NOW())
            WHERE b.id_allegropro_account=" . (int)$accountId . "
              AND b.order_filled=0
              AND b.order_id IS NOT NULL AND b.order_id <> ''
              AND {$feeWhere}
              AND s.id_allegropro_order_enrich_skip IS NULL
            GROUP BY b.order_id
            HAVING 1=1{$cursorCond}
            ORDER BY last_at DESC, b.order_id DESC
            {$limitSql}";

    return Db::getInstance()->executeS($sql) ?: [];
}


    private function normalizeCheckoutFormIdForApi(string $id): string
    {
        $id = trim($id);
        if ($id === '') {
            return $id;
        }
        // wariant z podkreśleniami
        if (strpos($id, '_') !== false && strpos($id, '-') === false) {
            return str_replace('_', '-', $id);
        }
        // wariant 32-znakowy bez separatorów
        if (preg_match('/^[0-9a-f]{32}$/i', $id)) {
            return substr($id, 0, 8) . '-' . substr($id, 8, 4) . '-' . substr($id, 12, 4) . '-' . substr($id, 16, 4) . '-' . substr($id, 20);
        }
        return $id;
    }

    /**
     * Rekey (naprawa) checkout_form_id gdy lokalnie mamy ten sam UUID w innym formacie (np. z podkreśleniami),
     * a z API przychodzi w formacie z myślnikami.
     *
     * Poprzednia wersja robiła SELECT ... LIMIT 1 i na części konfiguracji MariaDB dawało to 1064.
     * Tu robimy bezpieczny UPDATE po znormalizowanym UUID (bez - i _), bez LIMIT.
     */
    
    private function extractApiErrorMessage(array $resp, int $code = 0): string
    {
        try {
            $json = $resp['json'] ?? null;
            if (is_array($json)) {
                if (!empty($json['errors']) && is_array($json['errors'])) {
                    $e0 = $json['errors'][0] ?? null;
                    if (is_array($e0)) {
                        $msg = (string)($e0['userMessage'] ?? $e0['message'] ?? '');
                        $msg = trim($msg);
                        if ($msg !== '') {
                            return $msg;
                        }
                    }
                }
                if (!empty($json['message'])) {
                    $msg = trim((string)$json['message']);
                    if ($msg !== '') {
                        return $msg;
                    }
                }
            }
            $raw = (string)($resp['raw'] ?? '');
            $raw = trim($raw);
            if ($raw !== '') {
                if (Tools::strlen($raw) > 255) {
                    $raw = Tools::substr($raw, 0, 255);
                }
                return $raw;
            }
        } catch (\Throwable $e) {
            // ignore
        }
        return $code ? ('HTTP ' . (int)$code) : 'unknown error';
    }

private function rekeyCheckoutFormIdByNorm(int $accountId, string $rawId, string $apiId): int
    {
        $rawId = trim($rawId);
        $apiId = trim($apiId);
        if ($rawId === '' || $apiId === '') {
            return 0;
        }

        $norm = preg_replace('/[^0-9a-f]/i', '', strtolower($rawId));
        if ($norm === '') {
            return 0;
        }

        $db = Db::getInstance();
        $p = _DB_PREFIX_;
        $newEsc = pSQL($apiId);
        $normEsc = pSQL($norm);

        $affected = 0;

        // główna tabela zamówienia (z filtrem konta)
        $sqlMain = "UPDATE `{$p}allegropro_order`
                    SET checkout_form_id='{$newEsc}'
                    WHERE id_allegropro_account=" . (int)$accountId . "
                      AND " . $this->normalizeIdSql('checkout_form_id') . " = '{$normEsc}'";
        $db->execute($sqlMain);
        $affected += (int)$db->Affected_Rows();

        // tabele szczegółów – bez account_id, ale po tym samym znormalizowanym UUID
        $tables = ['allegropro_order_item', 'allegropro_order_shipping', 'allegropro_order_payment', 'allegropro_order_invoice', 'allegropro_order_buyer'];
        foreach ($tables as $t) {
            $sql = "UPDATE `{$p}{$t}`
                    SET checkout_form_id='{$newEsc}'
                    WHERE " . $this->normalizeIdSql('checkout_form_id') . " = '{$normEsc}'";
            $db->execute($sql);
            $affected += (int)$db->Affected_Rows();
        }

        return $affected;
    }

    public function ajaxProcessEnrichMissingCount(): void
    {
        // ensure billing schema (columns/indexes) also for AJAX
        $this->billingRepo->ensureSchema();
        $accountId = (int)Tools::getValue('account_id', 0);
        $dateFrom = (string)Tools::getValue('date_from', '');
        $dateTo = (string)Tools::getValue('date_to', '');

        if (!$accountId) {
            $this->ajaxJson(['ok' => 0, 'error' => 'Brak konta.']);
            return;
        }

        try {
            $cnt = $this->countMissingOrders($accountId, $dateFrom, $dateTo);
        } catch (\Throwable $e) {
            $this->ajaxJson(['ok' => 0, 'error' => 'SQL: ' . $e->getMessage()]);
            return;
        }

        $this->ajaxJson(['ok' => 1, 'missing' => (int)$cnt]);
    }

    public function ajaxProcessEnrichMissingStep(): void
    {
        // ensure billing schema (columns/indexes) also for AJAX
        $this->billingRepo->ensureSchema();
        $accountId = (int)Tools::getValue('account_id', 0);
        $dateFrom = (string)Tools::getValue('date_from', '');
        $dateTo = (string)Tools::getValue('date_to', '');
        $offset = (int)Tools::getValue('offset', 0);
        $limit = (int)Tools::getValue('limit', 10);

        $acc = $this->accounts->get($accountId);
        if (!$accountId || !is_array($acc)) {
            $this->ajaxJson(['ok' => 0, 'error' => 'Brak konta.']);
            return;
        }

        $limit = max(1, min(25, (int)$limit));
        $offset = max(0, (int)$offset);

        $cursorLastAt = trim((string)Tools::getValue('cursor_last_at', ''));
        $cursorOrderId = trim((string)Tools::getValue('cursor_order_id', ''));
        if ($cursorLastAt !== '' && !preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $cursorLastAt)) {
            $cursorLastAt = '';
        }

        try {
            $rows = $this->listMissingOrderRows($accountId, $dateFrom, $dateTo, $limit, $offset, $cursorLastAt, $cursorOrderId);
        } catch (\Throwable $e) {
            $this->ajaxJson(['ok' => 0, 'error' => 'SQL: ' . $e->getMessage()]);
            return;
        }

        if (empty($rows)) {
            $this->ajaxJson([
                'ok' => 1,
                'processed' => 0,
                'updated_orders' => 0,
                'errors' => [],
                'next_offset' => $offset,
                'next_cursor_last_at' => $cursorLastAt,
                'next_cursor_order_id' => $cursorOrderId,
                'done' => 1
            ]);
            return;
        }

        $ids = [];
        foreach ($rows as $r) {
            if (!empty($r['order_id'])) {
                $ids[] = (string)$r['order_id'];
            }
        }
        $last = end($rows);
        $nextCursorLastAt = !empty($last['last_at']) ? (string)$last['last_at'] : '';
        $nextCursorOrderId = !empty($last['order_id']) ? (string)$last['order_id'] : '';

        $http = new HttpClient();
        $api = new AllegroApiClient($http, $this->accounts);

        $this->ensureEnrichSkipSchema();

        $updated = 0;
        $errors = [];

        foreach ($ids as $rawId) {
            $apiId = $this->normalizeCheckoutFormIdForApi($rawId);

            // Rekey po znormalizowanym UUID (bez SELECT/LIMIT)
            try {
                $this->rekeyCheckoutFormIdByNorm($accountId, $rawId, $apiId);
            } catch (\Throwable $e) {
                $errors[] = ['id' => $rawId, 'error' => 'SQL(rekey): ' . $e->getMessage()];
            }

            try {
                $resp = $api->get($acc, '/order/checkout-forms/' . rawurlencode($apiId));
            } catch (\Throwable $e) {
                $msg = 'API: ' . $e->getMessage();
                $this->markEnrichSkip($accountId, $rawId, 0, $msg);
                $errors[] = ['id' => $rawId, 'code' => 0, 'error' => $msg];
                continue;
            }
            if (empty($resp['ok']) || !is_array($resp['json'])) {
                $code = (int)($resp['code'] ?? 0);
                $msg = $this->extractApiErrorMessage($resp, $code);
                $this->markEnrichSkip($accountId, $rawId, $code, $msg);
                $errors[] = ['id' => $rawId, 'code' => $code, 'error' => $msg];
                continue;
            }
            $order = $resp['json'];

            if (!is_array($order) || empty($order['id'])) {
                $errors[] = ['id' => $rawId, 'code' => (int)($resp['code'] ?? 0), 'error' => 'Brak danych zamówienia'];
                continue;
            }

            // dopisz account_id aby repo potrafiło zapisać
            $order['account_id'] = $accountId;

            try {
                $this->orderRepo->saveFullOrder($order);
                $this->markBillingOrderFilled($accountId, $rawId);
                // clear throttle + error markers
                $this->enrichSkip->clear($accountId, $rawId);
                try { $this->billingRepo->clearOrderError($accountId, $rawId); } catch (\Throwable $e) {}
                $updated++;
            } catch (\Throwable $e) {
                $errors[] = ['id' => $rawId, 'error' => $e->getMessage()];
            }
        }

        $done = count($rows) < $limit;

        $this->ajaxJson([
            'ok' => 1,
            'processed' => count($ids),
            'updated_orders' => (int)$updated,
            'errors' => $errors,
            'next_offset' => $offset + $limit,
            'next_cursor_last_at' => $nextCursorLastAt,
            'next_cursor_order_id' => $nextCursorOrderId,
            'done' => $done ? 1 : 0,
        ]);
    }

    /**
     * Eksport CSV: operacje billing (RAW) dla bieżących filtrów.
     * Trigger: GET export_raw_billing=1
     */
    private function exportRawBillingCsv(): void
    {
        // multi-account
        $rawAcc = Tools::getValue('id_allegropro_account', []);
        if (!is_array($rawAcc)) {
            $rawAcc = [$rawAcc];
        }
        $accountIds = [];
        foreach ($rawAcc as $v) {
            $id = (int)$v;
            if ($id > 0) {
                $accountIds[$id] = $id;
            }
        }
        $accountIds = array_values($accountIds);
        if (empty($accountIds)) {
            // nic do eksportu
            header('Content-Type: text/plain; charset=utf-8');
            echo 'Brak konta.';
            return;
        }

        $dateFrom = $this->sanitizeYmd((string)Tools::getValue('date_from', '')) ?: date('Y-m-01');
        $dateTo = $this->sanitizeYmd((string)Tools::getValue('date_to', '')) ?: date('Y-m-d');

        $rawQ = trim((string)Tools::getValue('raw_q', ''));
        $rawQ = Tools::substr($rawQ, 0, 140);

        $sign = (string)Tools::getValue('raw_sign', 'any');
        $assigned = (string)Tools::getValue('raw_assigned', 'any');
        $orderState = (string)Tools::getValue('order_state', 'all');

        $sortBy = (string)Tools::getValue('raw_sort_by', 'date');
        $sortDir = (string)Tools::getValue('raw_sort_dir', 'desc');

        // fee filters
        $feeGroup = (string)Tools::getValue('fee_group', '');
        $allowedFeeGroups = ['', 'commission', 'delivery', 'smart', 'promotion', 'refunds', 'other'];
        if (!in_array($feeGroup, $allowedFeeGroups, true)) {
            $feeGroup = '';
        }

        $feeTypesSelected = Tools::getValue('fee_type', []);
        if (!is_array($feeTypesSelected)) {
            $feeTypesSelected = [$feeTypesSelected];
        }
        $feeTypesSelectedClean = [];
        foreach ($feeTypesSelected as $t) {
            $t = trim((string)$t);
            if ($t === '') {
                continue;
            }
            if (Tools::strlen($t) > 160) {
                $t = Tools::substr($t, 0, 160);
            }
            $feeTypesSelectedClean[$t] = $t;
            if (count($feeTypesSelectedClean) >= 200) {
                break;
            }
        }
        $feeTypesSelected = array_values($feeTypesSelectedClean);

        // export variant: raw (lista) / summary (podsumowanie)
        // provided by JS as export_raw_variant=raw|summary
        $variant = (string)Tools::getValue('export_raw_variant', 'raw');
        if (!in_array($variant, ['raw', 'summary'], true)) {
            $variant = 'raw';
        }

        $this->billingRepo->ensureSchema();

        $svc = new BillingRawReportService($this->billingRepo);

        // map account labels
        $accMap = [];
        foreach ($this->accounts->all() as $a) {
            $aid = (int)($a['id_allegropro_account'] ?? 0);
            if ($aid <= 0) continue;
            $accMap[$aid] = (string)($a['label'] ?? $a['allegro_login'] ?? ('#' . $aid));
        }

        if (function_exists('ob_get_length') && ob_get_length()) {
            @ob_clean();
        }

        $fname = ($variant === 'summary')
            ? ('allegro_billing_raw_summary_' . $dateFrom . '_' . $dateTo . '.csv')
            : ('allegro_billing_raw_' . $dateFrom . '_' . $dateTo . '.csv');
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $fname . '"');

        // UTF-8 BOM for Excel
        echo "\xEF\xBB\xBF";

        $out = fopen('php://output', 'w');
        if (!$out) {
            return;
        }

        $perChunk = 50000;

        if ($variant === 'summary') {
            // nagłówki (podsumowanie)
            fputcsv($out, [
                'day',
                'account',
                'type_name',
                'count',
                'sum_total',
                'sum_neg',
                'sum_pos',
            ], ';');

            $page = 1;
            $pages = 1;
            $agg = []; // key => row

            do {
                $chunk = $svc->getPage(
                    $accountIds,
                    $dateFrom,
                    $dateTo,
                    $rawQ,
                    $sign,
                    $assigned,
                    $orderState,
                    $page,
                    $perChunk,
                    $sortBy,
                    $sortDir,
                    $feeGroup,
                    $feeTypesSelected
                );
                $pages = (int)($chunk['pages'] ?? 1);

                foreach (($chunk['items'] ?? []) as $r) {
                    $aid = (int)($r['id_allegropro_account'] ?? 0);
                    $label = $accMap[$aid] ?? ('#' . $aid);
                    $type = (string)($r['type_name'] ?? '');
                    $occur = (string)($r['occurred_at'] ?? '');
                    $day = $occur !== '' ? substr($occur, 0, 10) : '';
                    $amount = (float)($r['value_amount'] ?? 0);

                    $key = $day . '|' . $aid . '|' . $type;
                    if (!isset($agg[$key])) {
                        $agg[$key] = [
                            'day' => $day,
                            'label' => $label,
                            'type' => $type,
                            'cnt' => 0,
                            'sum_total' => 0.0,
                            'sum_neg' => 0.0,
                            'sum_pos' => 0.0,
                        ];
                    }
                    $agg[$key]['cnt']++;
                    $agg[$key]['sum_total'] += $amount;
                    if ($amount < 0) {
                        $agg[$key]['sum_neg'] += $amount;
                    } elseif ($amount > 0) {
                        $agg[$key]['sum_pos'] += $amount;
                    }
                }

                $page++;
            } while ($page <= $pages);

            $rows = array_values($agg);
            usort($rows, function ($a, $b) {
                $d = strcmp((string)($a['day'] ?? ''), (string)($b['day'] ?? ''));
                if ($d !== 0) return $d;
                $l = strcmp((string)($a['label'] ?? ''), (string)($b['label'] ?? ''));
                if ($l !== 0) return $l;
                return strcmp((string)($a['type'] ?? ''), (string)($b['type'] ?? ''));
            });

            foreach ($rows as $r) {
                fputcsv($out, [
                    (string)($r['day'] ?? ''),
                    (string)($r['label'] ?? ''),
                    (string)($r['type'] ?? ''),
                    (int)($r['cnt'] ?? 0),
                    number_format((float)($r['sum_total'] ?? 0), 2, '.', ''),
                    number_format((float)($r['sum_neg'] ?? 0), 2, '.', ''),
                    number_format((float)($r['sum_pos'] ?? 0), 2, '.', ''),
                ], ';');
            }

        } else {
            // nagłówki (RAW)
            fputcsv($out, [
                'occurred_at',
                'account',
                'type_name',
                'offer_id',
                'offer_name',
                'order_id',
                'order_status',
                'id_order_prestashop',
                'value_amount',
                'currency',
                'billing_entry_id',
            ], ';');

            $page = 1;
            $pages = 1;
            do {
                $chunk = $svc->getPage(
                    $accountIds,
                    $dateFrom,
                    $dateTo,
                    $rawQ,
                    $sign,
                    $assigned,
                    $orderState,
                    $page,
                    $perChunk,
                    $sortBy,
                    $sortDir,
                    $feeGroup,
                    $feeTypesSelected
                );
                $pages = (int)($chunk['pages'] ?? 1);

                foreach (($chunk['items'] ?? []) as $r) {
                    $aid = (int)($r['id_allegropro_account'] ?? 0);
                    $label = $accMap[$aid] ?? ('#' . $aid);
                    $amount = (float)($r['value_amount'] ?? 0);
                    fputcsv($out, [
                        (string)($r['occurred_at'] ?? ''),
                        $label,
                        (string)($r['type_name'] ?? ''),
                        (string)($r['offer_id'] ?? ''),
                        (string)($r['offer_name'] ?? ''),
                        (string)($r['order_id'] ?? ''),
                        (string)($r['order_status'] ?? ''),
                        (int)($r['id_order_prestashop'] ?? 0),
                        number_format($amount, 2, '.', ''),
                        (string)($r['value_currency'] ?? 'PLN'),
                        (string)($r['billing_entry_id'] ?? ''),
                    ], ';');
                }

                $page++;
            } while ($page <= $pages);
        }
        fclose($out);
    }


private function toIsoStart(string $ymd): string
    {
        $ymd = preg_replace('/[^0-9-]/', '', $ymd);
        if (!$ymd) {
            $ymd = date('Y-m-d');
        }
        return $ymd . 'T00:00:00.000Z';
    }

    private function toIsoEnd(string $ymd): string
    {
        $ymd = preg_replace('/[^0-9-]/', '', $ymd);
        if (!$ymd) {
            $ymd = date('Y-m-d');
        }
        return $ymd . 'T23:59:59.999Z';
    }

    private function sanitizeYmd(string $ymd): string
    {
        $ymd = preg_replace('/[^0-9-]/', '', $ymd);
        if (!preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/', $ymd)) {
            return '';
        }
        return $ymd;
    }

    private function sanitizeSearch(string $q): string
    {
        $q = trim($q);
        if ($q === '') {
            return '';
        }
        // Keep it safe & readable
        $q = preg_replace('/[^A-Za-z0-9@._\-]/', '', $q);
        return (string)$q;
    }

    /**
     * @return array<int, array{type:string,label:string,url?:string,active?:bool,disabled?:bool}>
     */
    private function buildPageLinks(string $baseUrl, int $page, int $pages, string $param = 'page'): array
    {
        $links = [];

        $add = function (int $num) use (&$links, $baseUrl, $page, $param) {
            $links[] = [
                'type' => 'page',
                'label' => (string)$num,
                'url' => $baseUrl . '&' . $param . '=' . $num,
                'active' => ($num === $page),
            ];
        };

        if ($pages <= 1) {
            return $links;
        }

        // prev
        $links[] = [
            'type' => 'nav',
            'label' => '‹',
            'url' => $baseUrl . '&' . $param . '=' . max(1, $page - 1),
            'disabled' => ($page <= 1),
        ];

        if ($pages <= 9) {
            for ($i = 1; $i <= $pages; $i++) {
                $add($i);
            }
        } else {
            $add(1);

            $start = max(2, $page - 2);
            $end = min($pages - 1, $page + 2);

            if ($start > 2) {
                $links[] = ['type' => 'gap', 'label' => '…'];
            }

            for ($i = $start; $i <= $end; $i++) {
                $add($i);
            }

            if ($end < $pages - 1) {
                $links[] = ['type' => 'gap', 'label' => '…'];
            }

            $add($pages);
        }

        // next
        $links[] = [
            'type' => 'nav',
            'label' => '›',
            'url' => $baseUrl . '&' . $param . '=' . min($pages, $page + 1),
            'disabled' => ($page >= $pages),
        ];

        return $links;
    }

/**
 * Zwraca listę dostępnych typów operacji (type_name) z tabeli billing_entry w wybranym zakresie dat.
 * Nazwy są 1:1 jak w Allegro (Sales Center).
 *
 * @return array<int, array{type_name:string,cnt:int}>
 */
private function listFeeTypesInBillingRange(array $accountIds, string $dateFrom, string $dateTo): array
{
    $accountIds = array_values(array_filter(array_map('intval', $accountIds)));
    if (empty($accountIds)) {
        return [];
    }

    $from = pSQL($dateFrom . ' 00:00:00');
    $to = pSQL($dateTo . ' 23:59:59');

    $in = '(' . implode(',', $accountIds) . ')';

    $sql = "SELECT type_name, COUNT(*) AS cnt
            FROM `" . _DB_PREFIX_ . "allegropro_billing_entry`
            WHERE id_allegropro_account IN " . $in . "
              AND occurred_at BETWEEN '" . $from . "' AND '" . $to . "'
              AND type_name IS NOT NULL AND type_name <> ''
            GROUP BY type_name
            ORDER BY type_name ASC";

    $rows = Db::getInstance()->executeS($sql);
    if (!is_array($rows)) {
        return [];
    }

    $out = [];
    foreach ($rows as $r) {
        $name = (string)($r['type_name'] ?? '');
        if ($name === '') {
            continue;
        }
        $out[] = [
            'type_name' => $name,
            'cnt' => (int)($r['cnt'] ?? 0),
        ];
    }
    return $out;
}

/**
 * Buduje query string dla fee_type[] (zachowanie filtrów w paginacji/odświeżeniu).
 */
private function buildFeeTypesQueryString(array $feeTypesSelected): string
{
    if (empty($feeTypesSelected)) {
        return '';
    }
    $qs = '';
    foreach ($feeTypesSelected as $t) {
        $qs .= '&fee_type[]=' . urlencode((string)$t);
    }
    return $qs;
}

}
