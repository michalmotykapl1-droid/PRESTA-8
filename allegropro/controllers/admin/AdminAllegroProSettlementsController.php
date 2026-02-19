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

class AdminAllegroProSettlementsController extends ModuleAdminController
{
    private AccountRepository $accounts;
    private BillingEntryRepository $billingRepo;
    private OrderRepository $orderRepo;

    public function setMedia($isNewTheme = false)
    {
        parent::setMedia($isNewTheme);
        if (!empty($this->module)) {
            $this->addCSS($this->module->getPathUri() . 'views/css/settlements.css');
            $this->addJS($this->module->getPathUri() . 'views/js/settlements.js');
        }
    }

    public function __construct()
    {
        parent::__construct();
        $this->bootstrap = true;
        $this->accounts = new AccountRepository();
        $this->billingRepo = new BillingEntryRepository();
        $this->orderRepo = new OrderRepository();
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



        $orderState = (string)Tools::getValue('order_state', 'all');
        $orderState = in_array($orderState, ['all', 'paid', 'unpaid', 'cancelled'], true) ? $orderState : 'all';

        $cancelledNoRefund = (int)Tools::getValue('cancelled_no_refund', 0) ? true : false;
        if ($cancelledNoRefund) {
            $orderState = 'cancelled';
        }

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
                ? $report->getPeriodSummaryOrders($selectedAccountIds, $dateFrom, $dateTo, $orderState, $cancelledNoRefund)
                : $report->getPeriodSummaryBilling($selectedAccountIds, $dateFrom, $dateTo, $orderState, $cancelledNoRefund);
        } else {
            $summary = [];
        }


        // Liczba zamówień, z których wynika kwota „Sprzedaż brutto” (bez wpływu pola wyszukiwania)
        if (!empty($selectedAccountIds) && is_array($summary)) {
            $ordersCountForSales = ($mode === 'billing')
                ? $report->countOrdersBilling($selectedAccountIds, $dateFrom, $dateTo, '', $orderState, $cancelledNoRefund)
                : $report->countOrders($selectedAccountIds, $dateFrom, $dateTo, '', $orderState, $cancelledNoRefund);
            $summary['orders_count'] = (int)$ordersCountForSales;
        }

        $ordersTotal = 0;
        if (!empty($selectedAccountIds)) {
            $ordersTotal = ($mode === 'billing')
                ? $report->countOrdersBilling($selectedAccountIds, $dateFrom, $dateTo, $q, $orderState, $cancelledNoRefund)
                : $report->countOrders($selectedAccountIds, $dateFrom, $dateTo, $q, $orderState, $cancelledNoRefund);
        }

        $pages = (int)max(1, (int)ceil(($ordersTotal ?: 0) / max(1, $perPage)));
        if ($page > $pages) {
            $page = $pages;
        }
        $offset = max(0, ($page - 1) * $perPage);

        $ordersRows = [];
        if (!empty($selectedAccountIds)) {
            $ordersRows = ($mode === 'billing')
                ? $report->getOrdersWithFeesBilling($selectedAccountIds, $dateFrom, $dateTo, $q, $perPage, $offset, $orderState, $cancelledNoRefund)
                : $report->getOrdersWithFeesOrders($selectedAccountIds, $dateFrom, $dateTo, $q, $perPage, $offset, $orderState, $cancelledNoRefund);
        }

        $billingCount = (!empty($selectedAccountIds) && $mode === 'billing')
            ? $this->billingRepo->countInRangeMulti($selectedAccountIds, $dateFrom, $dateTo)
            : 0;


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
            . '&per_page=' . (int)$perPage;

        $pageLinks = $this->buildPageLinks($base, $page, $pages);

        $ordersFrom = $ordersTotal > 0 ? ($offset + 1) : 0;
        $ordersTo = min($ordersTotal, $offset + count($ordersRows));

        $ajaxUrl = $this->context->link->getAdminLink('AdminAllegroProSettlements');

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
            'mode' => $mode,
            'sync_result' => $syncResult,
            'sync_debug' => $syncDebug,
            'summary' => $summary,
            'structure_chart_json' => $structureChartJson,
            'orders_rows' => $ordersRows,
            'orders_total' => (int)$ordersTotal,
            'orders_from' => (int)$ordersFrom,
            'orders_to' => (int)$ordersTo,
            'page' => (int)$page,
            'pages' => (int)$pages,
            'per_page' => (int)$perPage,
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

        $details = $report->getOrderDetails($accountId, $checkout, $dateFrom, $dateTo, $ignoreBillingDate);

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
        $accountId = (int)Tools::getValue('account_id', 0);
        $dateFrom = (string)Tools::getValue('date_from', '');
        $dateTo = (string)Tools::getValue('date_to', '');
        $offset = (int)Tools::getValue('offset', 0);
        $limit = (int)Tools::getValue('limit', 100);

        $acc = $this->accounts->get($accountId);
        if (!$accountId || !is_array($acc)) {
            $this->ajaxJson(['ok' => 0, 'error' => 'Brak konta.']);
            return;
        }

        $http = new HttpClient();
        $api = new AllegroApiClient($http, $this->accounts);
        $sync = new BillingSyncService($api, $this->billingRepo);

        $debug = [];
        $step = $sync->syncRangeStep(
            $acc,
            $this->toIsoStart($dateFrom),
            $this->toIsoEnd($dateTo),
            $offset,
            $limit,
            $debug
        );

        $this->ajaxJson([
            'ok' => !empty($step['ok']) ? 1 : 0,
            'code' => (int)($step['code'] ?? 0),
            'got' => (int)($step['got'] ?? 0),
            'inserted' => (int)($step['inserted'] ?? 0),
            'updated' => (int)($step['updated'] ?? 0),
            'next_offset' => (int)($step['next_offset'] ?? $offset),
            'done' => !empty($step['done']) ? 1 : 0,
            'debug_tail' => array_slice($debug, -2),
        ]);
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

    private function countMissingOrders(int $accountId, string $dateFrom, string $dateTo): int
    {
        $from = pSQL($dateFrom . ' 00:00:00');
        $to = pSQL($dateTo . ' 23:59:59');

        $feeWhere = $this->feeWhereSql("LOWER(IFNULL(b.type_name,''))");

        $normO = $this->normalizeIdSql('o.checkout_form_id');
        $normB = $this->normalizeIdSql('b.order_id');

        $sql = "SELECT COUNT(*) FROM (
                  SELECT b.order_id
                  FROM `" . _DB_PREFIX_ . "allegropro_billing_entry` b
                  LEFT JOIN `" . _DB_PREFIX_ . "allegropro_order` o
                    ON (
                      o.id_allegropro_account=b.id_allegropro_account
                      AND {$normO} = {$normB}
                    )
                  WHERE b.id_allegropro_account=" . (int)$accountId . "
                    AND b.order_id IS NOT NULL AND b.order_id <> ''
                    AND b.occurred_at BETWEEN '" . $from . "' AND '" . $to . "'
                    AND {$feeWhere}
                  GROUP BY b.order_id
                  HAVING (MAX(o.id_allegropro_order) IS NULL OR MAX(IFNULL(o.total_amount,0))<=0 OR MAX(IFNULL(o.buyer_login,''))='')
                ) t";
        return (int)Db::getInstance()->getValue($sql);
    }

    private function listMissingOrderIds(int $accountId, string $dateFrom, string $dateTo, int $limit, int $offset): array
    {
        $from = pSQL($dateFrom . ' 00:00:00');
        $to = pSQL($dateTo . ' 23:59:59');

        $feeWhere = $this->feeWhereSql("LOWER(IFNULL(b.type_name,''))");

        $normO = $this->normalizeIdSql('o.checkout_form_id');
        $normB = $this->normalizeIdSql('b.order_id');

        $sql = "SELECT b.order_id, MAX(b.occurred_at) as last_at
                FROM `" . _DB_PREFIX_ . "allegropro_billing_entry` b
                LEFT JOIN `" . _DB_PREFIX_ . "allegropro_order` o
                  ON (
                    o.id_allegropro_account=b.id_allegropro_account
                    AND {$normO} = {$normB}
                  )
                WHERE b.id_allegropro_account=" . (int)$accountId . "
                  AND b.order_id IS NOT NULL AND b.order_id <> ''
                  AND b.occurred_at BETWEEN '" . $from . "' AND '" . $to . "'
                  AND {$feeWhere}
                GROUP BY b.order_id
                HAVING (MAX(o.id_allegropro_order) IS NULL OR MAX(IFNULL(o.total_amount,0))<=0 OR MAX(IFNULL(o.buyer_login,''))='')
                ORDER BY last_at DESC
                LIMIT " . (int)$offset . ", " . (int)$limit;
        $rows = Db::getInstance()->executeS($sql) ?: [];
        $ids = [];
        foreach ($rows as $r) {
            if (!empty($r['order_id'])) {
                $ids[] = (string)$r['order_id'];
            }
        }
        return $ids;
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

        try {
            $ids = $this->listMissingOrderIds($accountId, $dateFrom, $dateTo, $limit, $offset);
        } catch (\Throwable $e) {
            $this->ajaxJson(['ok' => 0, 'error' => 'SQL: ' . $e->getMessage()]);
            return;
        }
        if (empty($ids)) {
            $this->ajaxJson(['ok' => 1, 'processed' => 0, 'updated_orders' => 0, 'errors' => [], 'next_offset' => $offset, 'done' => 1]);
            return;
        }

        $http = new HttpClient();
        $api = new AllegroApiClient($http, $this->accounts);

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
            $errors[] = ['id' => $rawId, 'error' => 'API: ' . $e->getMessage()];
            continue;
        }
            if (empty($resp['ok']) || !is_array($resp['json'])) {
                $errors[] = ['id' => $rawId, 'code' => (int)($resp['code'] ?? 0)];
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
                $updated++;
            } catch (\Throwable $e) {
                $errors[] = ['id' => $rawId, 'error' => $e->getMessage()];
            }
        }

        $done = count($ids) < $limit;

        $this->ajaxJson([
            'ok' => 1,
            'processed' => count($ids),
            'updated_orders' => (int)$updated,
            'errors' => $errors,
            'next_offset' => $offset + $limit,
            'done' => $done ? 1 : 0,
        ]);
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
    private function buildPageLinks(string $baseUrl, int $page, int $pages): array
    {
        $links = [];

        $add = function (int $num) use (&$links, $baseUrl, $page) {
            $links[] = [
                'type' => 'page',
                'label' => (string)$num,
                'url' => $baseUrl . '&page=' . $num,
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
            'url' => $baseUrl . '&page=' . max(1, $page - 1),
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
            'url' => $baseUrl . '&page=' . min($pages, $page + 1),
            'disabled' => ($page >= $pages),
        ];

        return $links;
    }
}
