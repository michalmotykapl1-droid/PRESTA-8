<?php
namespace AllegroPro\Service;

use AllegroPro\Repository\BillingEntryRepository;
use Db;

/**
 * Raport rozliczeń Allegro (billing-entries) + mapowanie do zamówień (checkout-form).
 *
 * Dwa tryby:
 * - billing: zakres dat dotyczy księgowania operacji (occurred_at) — zgodnie z Sales Center.
 * - orders: zakres dat dotyczy daty złożenia zamówień (created_at_allegro), a opłaty liczymy dla tych zamówień (bez filtra daty opłat).
 */
class SettlementsReportService
{
    private BillingEntryRepository $billing;

    public function __construct(BillingEntryRepository $billing)
    {
        $this->billing = $billing;
    }

    /**
     * @param int|int[] $accountIds
     * @return int[]
     */
    private function normalizeAccountIds($accountIds): array
    {
        $ids = is_array($accountIds) ? $accountIds : [$accountIds];
        $out = [];
        foreach ($ids as $id) {
            $id = (int)$id;
            if ($id > 0) {
                $out[$id] = $id;
            }
        }
        return array_values($out);
    }

    /**
     * Buduje bezpieczny fragment "IN (...)".
     * @param int[] $ids
     */
    private function buildIn(array $ids): string
    {
        $ids = $this->normalizeAccountIds($ids);
        if (empty($ids)) {
            return '(0)';
        }
        return '(' . implode(',', array_map('intval', $ids)) . ')';
    }

    /**
     * Filtr "opłat" zgodny z BillingEntryRepository::buildFeeWhereSql().
     */
    private function feeWhere(string $valueExpr, string $typeNameExpr): string
    {
        $include = "({$valueExpr} < 0 OR {$typeNameExpr} LIKE '%zwrot%' OR {$typeNameExpr} LIKE '%rabat%' OR {$typeNameExpr} LIKE '%korekt%' OR {$typeNameExpr} LIKE '%rekompens%')";
        $exclude = "({$typeNameExpr} LIKE '%wypł%' OR {$typeNameExpr} LIKE '%wypl%' OR {$typeNameExpr} LIKE '%wpł%' OR {$typeNameExpr} LIKE '%wpl%' OR {$typeNameExpr} LIKE '%przelew%' OR {$typeNameExpr} LIKE '%środk%' OR {$typeNameExpr} LIKE '%srodk%')";
        return "({$include} AND NOT {$exclude})";
    }

    /**
     * TRYB A: Księgowanie opłat (billing entries w okresie).
     */
    public function getPeriodSummaryBilling($accountIds, string $dateFrom, string $dateTo): array
    {
        $ids = $this->normalizeAccountIds($accountIds);
        $cats = $this->billing->getCategorySumsMulti($ids, $dateFrom, $dateTo);
        $unassigned = $this->billing->countUnassignedMulti($ids, $dateFrom, $dateTo);

        // Sprzedaż brutto: suma zamówień, które mają naliczone opłaty w wybranym okresie księgowania.
        $sales = $this->sumOrdersTotalForBillingMulti($ids, $dateFrom, $dateTo);

        $netAfterFees = (float)$sales + (float)($cats['total'] ?? 0);

        return [
            'sales_total' => (float)$sales,
            'fees_total' => (float)($cats['total'] ?? 0),
            'fees_commission' => (float)($cats['commission'] ?? 0),
            'fees_smart' => (float)($cats['smart'] ?? 0),
            'fees_delivery' => (float)($cats['delivery'] ?? 0),
            'fees_promotion' => (float)($cats['promotion'] ?? 0),
            'fees_refunds' => (float)($cats['refunds'] ?? 0),
            'unassigned_count' => (int)$unassigned,
            'net_after_fees' => (float)$netAfterFees,
        ];
    }

    /**
     * TRYB B: Koszt zamówień złożonych w okresie (opłaty liczone dla tych zamówień).
     */
    public function getPeriodSummaryOrders($accountIds, string $dateFrom, string $dateTo): array
    {
        $ids = $this->normalizeAccountIds($accountIds);

        // Sprzedaż brutto: zamówienia złożone w okresie.
        $sales = $this->sumOrdersTotalMulti($ids, $dateFrom, $dateTo);

        // Opłaty: wszystkie billing-entries przypięte do tych zamówień (bez filtra daty).
        $orderIds = $this->listOrderIdsInRangeMulti($ids, $dateFrom, $dateTo);
        $candidates = $this->buildOrderIdCandidates($orderIds);

        $cats = $this->billing->getCategorySumsForOrderIdsMultiNoDate($ids, $candidates);

        $netAfterFees = (float)$sales + (float)($cats['total'] ?? 0);

        return [
            'sales_total' => (float)$sales,
            'fees_total' => (float)($cats['total'] ?? 0),
            'fees_commission' => (float)($cats['commission'] ?? 0),
            'fees_smart' => (float)($cats['smart'] ?? 0),
            'fees_delivery' => (float)($cats['delivery'] ?? 0),
            'fees_promotion' => (float)($cats['promotion'] ?? 0),
            'fees_refunds' => (float)($cats['refunds'] ?? 0),
            'unassigned_count' => 0,
            'net_after_fees' => (float)$netAfterFees,
        ];
    }

    /**
     * Backward compatible alias (domyślnie: billing).
     */
    public function getPeriodSummary($accountIds, string $dateFrom, string $dateTo): array
    {
        return $this->getPeriodSummaryBilling($accountIds, $dateFrom, $dateTo);
    }

    /**
     * TRYB A: liczba zamówień (unikalne order_id) mających opłaty zaksięgowane w okresie.
     */
    public function countOrdersBilling($accountIds, string $dateFrom, string $dateTo, string $q = ''): int
    {
        $ids = $this->normalizeAccountIds($accountIds);
        $in = $this->buildIn($ids);

        $from = pSQL($dateFrom . ' 00:00:00');
        $to = pSQL($dateTo . ' 23:59:59');

        $tn = "LOWER(IFNULL(b.type_name,''))";
        $feeWhere = $this->feeWhere('b.value_amount', $tn);

        $whereQ = '';
        if ($q !== '') {
            $qEsc = pSQL('%' . $q . '%');
            $whereQ = " AND (b.order_id LIKE '" . $qEsc . "' OR o.buyer_login LIKE '" . $qEsc . "')";
        }

        // Uwaga: Allegro potrafi zwracać order_id w billing-entries w różnych wariantach (z/bez myślników, różna wielkość liter).
        // Łączymy po znormalizowanym UUID (lower + bez myślników), aby nie gubić dopasowań do tabeli allegropro_order.
        $sql = "SELECT COUNT(*) FROM (
                    SELECT b.id_allegropro_account, b.order_id
                    FROM `" . _DB_PREFIX_ . "allegropro_billing_entry` b
                    LEFT JOIN `" . _DB_PREFIX_ . "allegropro_order` o
                      ON (
                        o.id_allegropro_account=b.id_allegropro_account
                        AND LOWER(REPLACE(REPLACE(IFNULL(o.checkout_form_id,''),'-',''),'_','')) = LOWER(REPLACE(REPLACE(IFNULL(b.order_id,''),'-',''),'_',''))
                      )
                    WHERE b.id_allegropro_account IN " . $in . "
                      AND b.order_id IS NOT NULL AND b.order_id <> ''
                      AND b.occurred_at BETWEEN '" . $from . "' AND '" . $to . "'
                      AND {$feeWhere}
                      {$whereQ}
                    GROUP BY b.id_allegropro_account, b.order_id
                ) t";
        return (int)Db::getInstance()->getValue($sql);
    }

    /**
     * TRYB B: liczba zamówień złożonych w okresie (filtr po created_at_allegro).
     */
    public function countOrders($accountIds, string $dateFrom, string $dateTo, string $q = ''): int
    {
        $from = pSQL($dateFrom . ' 00:00:00');
        $to = pSQL($dateTo . ' 23:59:59');

        $whereQ = '';
        if ($q !== '') {
            $qEsc = pSQL('%' . $q . '%');
            $whereQ = " AND (o.checkout_form_id LIKE '" . $qEsc . "' OR o.buyer_login LIKE '" . $qEsc . "')";
        }

        $ids = $this->normalizeAccountIds($accountIds);
        $in = $this->buildIn($ids);

        $sql = "SELECT COUNT(*) FROM `" . _DB_PREFIX_ . "allegropro_order` o
                WHERE o.id_allegropro_account IN " . $in . "
                  AND o.created_at_allegro BETWEEN '" . $from . "' AND '" . $to . "'" . $whereQ;
        return (int)Db::getInstance()->getValue($sql);
    }

    /**
     * TRYB A: lista zamówień (order_id) które mają opłaty zaksięgowane w okresie.
     */
    public function getOrdersWithFeesBilling($accountIds, string $dateFrom, string $dateTo, string $q = '', int $limit = 50, int $offset = 0): array
    {
        $ids = $this->normalizeAccountIds($accountIds);
        $in = $this->buildIn($ids);

        $from = pSQL($dateFrom . ' 00:00:00');
        $to = pSQL($dateTo . ' 23:59:59');

        $limit = max(1, min(500, (int)$limit));
        $offset = max(0, (int)$offset);

        $tn = "LOWER(IFNULL(b.type_name,''))";
        $feeWhere = $this->feeWhere('b.value_amount', $tn);

        $whereQ = '';
        if ($q !== '') {
            $qEsc = pSQL('%' . $q . '%');
            $whereQ = " AND (b.order_id LIKE '" . $qEsc . "' OR o.buyer_login LIKE '" . $qEsc . "')";
        }

        // Jak wyżej: dopasowanie po znormalizowanym UUID.
        $sql = "SELECT 
                    b.id_allegropro_account,
                    b.order_id AS checkout_form_id,
                    MAX(b.occurred_at) AS occurred_at_max,
                    SUM(b.value_amount) AS fees_total,
                    o.buyer_login,
                    o.total_amount,
                    o.currency,
                    o.created_at_allegro,
                    a.label AS account_label
                FROM `" . _DB_PREFIX_ . "allegropro_billing_entry` b
                LEFT JOIN `" . _DB_PREFIX_ . "allegropro_order` o
                  ON (
                    o.id_allegropro_account=b.id_allegropro_account
                    AND LOWER(REPLACE(REPLACE(IFNULL(o.checkout_form_id,''),'-',''),'_','')) = LOWER(REPLACE(REPLACE(IFNULL(b.order_id,''),'-',''),'_',''))
                  )
                LEFT JOIN `" . _DB_PREFIX_ . "allegropro_account` a
                  ON (a.id_allegropro_account=b.id_allegropro_account)
                WHERE b.id_allegropro_account IN " . $in . "
                  AND b.order_id IS NOT NULL AND b.order_id <> ''
                  AND b.occurred_at BETWEEN '" . $from . "' AND '" . $to . "'
                  AND {$feeWhere}
                  {$whereQ}
                GROUP BY b.id_allegropro_account, b.order_id
                ORDER BY occurred_at_max DESC
                LIMIT " . (int)$limit . " OFFSET " . (int)$offset;

        $rows = Db::getInstance()->executeS($sql) ?: [];
        foreach ($rows as &$r) {
            $r['id_allegropro_account'] = (int)($r['id_allegropro_account'] ?? 0);
            // Jeśli brak danych o zamówieniu (brak dopasowania do allegropro_order) — nie pokazuj sztucznego 0.00.
            $ta = isset($r['total_amount']) ? (float)$r['total_amount'] : 0.0;
            $r['total_amount'] = ($ta > 0.0) ? $ta : null;
            $r['fees_total'] = (float)($r['fees_total'] ?? 0);

            // date_display = data operacji billing
            $r['date_display'] = (string)($r['occurred_at_max'] ?? $r['created_at_allegro'] ?? '');
            $r['net_after_fees'] = ($r['total_amount'] !== null) ? ((float)$r['total_amount'] + (float)$r['fees_total']) : null;
        }
        unset($r);

        return $rows;
    }

    /**
     * TRYB B: lista zamówień złożonych w okresie + opłaty policzone dla tych zamówień (bez filtra daty opłat).
     */
    public function getOrdersWithFeesOrders($accountIds, string $dateFrom, string $dateTo, string $q = '', int $limit = 50, int $offset = 0): array
    {
        $ids = $this->normalizeAccountIds($accountIds);
        $orders = $this->listOrdersMulti($ids, $dateFrom, $dateTo, $q, $limit, $offset);

        // Zbuduj listę kandydatów order_id dla widocznych zamówień (dokładny + bez myślników + lower/upper).
        $candidates = [];
        foreach ($orders as $o) {
            $cf = (string)($o['checkout_form_id'] ?? '');
            if ($cf === '') continue;
            $candidates[] = $cf;
            $dash = str_replace('_', '-', $cf);
            if ($dash !== '' && $dash !== $cf) $candidates[] = $dash;
            $under = str_replace('-', '_', $cf);
            if ($under !== '' && $under !== $cf) $candidates[] = $under;
            $noSep = str_replace(['-','_'], '', $cf);
            if ($noSep !== '' && $noSep !== $cf) $candidates[] = $noSep;
            $lower = strtolower($cf);
            if ($lower !== $cf) $candidates[] = $lower;
            $upper = strtoupper($cf);
            if ($upper !== $cf) $candidates[] = $upper;
        }
        $candidates = array_values(array_unique($candidates));

        $feeMapRaw = $this->billing->sumByOrderIdsMultiNoDate($ids, $candidates);

        // Normalizacja pomocnicza (dla wariantów id bez myślników)
        $feeMapNorm = [];
        foreach ($feeMapRaw as $aid => $ordersMap) {
            if (!is_array($ordersMap)) continue;
            foreach ($ordersMap as $oid => $sum) {
                $k = $this->normalizeId((string)$oid);
                if ($k === '') continue;
                if (!isset($feeMapNorm[$aid])) $feeMapNorm[$aid] = [];
                if (!isset($feeMapNorm[$aid][$k])) $feeMapNorm[$aid][$k] = 0.0;
                $feeMapNorm[$aid][$k] += (float)$sum;
            }
        }

        foreach ($orders as &$o) {
            $cf = (string)$o['checkout_form_id'];
            $aid = (int)($o['id_allegropro_account'] ?? 0);
            $fees = 0.0;

            if ($aid > 0 && isset($feeMapRaw[$aid]) && is_array($feeMapRaw[$aid]) && isset($feeMapRaw[$aid][$cf])) {
                $fees = (float)$feeMapRaw[$aid][$cf];
            } else {
                $k = $this->normalizeId($cf);
                if ($aid > 0 && $k !== '' && isset($feeMapNorm[$aid]) && isset($feeMapNorm[$aid][$k])) {
                    $fees = (float)$feeMapNorm[$aid][$k];
                }
            }

            $o['fees_total'] = $fees;
            $o['net_after_fees'] = (float)$o['total_amount'] + $fees;
            $o['date_display'] = (string)($o['created_at_allegro'] ?? '');
        }
        unset($o);

        return $orders;
    }

    /**
     * Szczegóły zamówienia do modala.
     * @param bool $ignoreBillingDate Jeśli true: opłaty bez filtra daty (wszystkie dla zamówienia)
     */
    public function getOrderDetails(int $accountId, string $checkoutFormId, string $dateFrom, string $dateTo, bool $ignoreBillingDate = false): array
    {
        $order = $this->getOrderRow($accountId, $checkoutFormId);

        $cf = $this->sanitizeCheckoutFormId($checkoutFormId);
        $candidates = [];
        if ($cf !== '') {
            $candidates[] = $cf;
            $dash = str_replace('_', '-', $cf);
            if ($dash !== '' && $dash !== $cf) $candidates[] = $dash;
            $under = str_replace('-', '_', $cf);
            if ($under !== '' && $under !== $cf) $candidates[] = $under;
            $noSep = str_replace(['-','_'], '', $cf);
            if ($noSep !== '' && $noSep !== $cf) $candidates[] = $noSep;
            $lower = strtolower($cf);
            if ($lower !== $cf) $candidates[] = $lower;
            $upper = strtoupper($cf);
            if ($upper !== $cf) $candidates[] = $upper;
        }

        if ($ignoreBillingDate) {
            $items = $this->billing->listForOrderCandidatesNoDate($accountId, $candidates ?: [$checkoutFormId]);
        } else {
            $items = $this->billing->listForOrderCandidates($accountId, $candidates ?: [$checkoutFormId], $dateFrom, $dateTo);
        }

        $cats = [
            'commission' => 0.0,
            'smart' => 0.0,
            'delivery' => 0.0,
            'promotion' => 0.0,
            'refunds' => 0.0,
            'other' => 0.0,
            'total' => 0.0,
        ];

        foreach ($items as &$it) {
            $amount = (float)$it['value_amount'];
            $cats['total'] += $amount;
            $cat = $this->classify((string)($it['type_id'] ?? ''), (string)($it['type_name'] ?? ''), $amount);
            $it['category'] = $cat;
            if (isset($cats[$cat])) {
                $cats[$cat] += $amount;
            } else {
                $cats['other'] += $amount;
            }
        }
        unset($it);

        $netAfterFees = $order ? ((float)$order['total_amount'] + (float)$cats['total']) : (float)$cats['total'];

        return [
            'order' => $order,
            'items' => $items,
            'cats' => $cats,
            'net_after_fees' => (float)$netAfterFees,
        ];
    }

    private function contains(string $haystack, string $needle): bool
    {
        return mb_strpos($haystack, $needle) !== false;
    }

    private function classify(string $typeId, string $typeName, float $amount): string
    {
        $n = mb_strtolower($typeName);

        if ($typeId === 'SUC' || $this->contains($n, 'prowiz')) return 'commission';
        if ($this->contains($n, 'smart')) return 'smart';
        if ($this->contains($n, 'dostaw') || $this->contains($n, 'przesy')) return 'delivery';
        if ($this->contains($n, 'promow') || $this->contains($n, 'reklam')) return 'promotion';
        if ($this->contains($n, 'zwrot') || $this->contains($n, 'rabat') || $this->contains($n, 'korekt') || $this->contains($n, 'rekompens')) return 'refunds';
        return 'other';
    }

    /**
     * SUMA zamówień złożonych w okresie (created_at_allegro).
     */
    private function sumOrdersTotalMulti(array $accountIds, string $dateFrom, string $dateTo): float
    {
        $from = pSQL($dateFrom . ' 00:00:00');
        $to = pSQL($dateTo . ' 23:59:59');

        $in = $this->buildIn($accountIds);

        $sql = "SELECT SUM(total_amount) FROM `" . _DB_PREFIX_ . "allegropro_order`
                WHERE id_allegropro_account IN " . $in . "
                  AND created_at_allegro BETWEEN '" . $from . "' AND '" . $to . "'";
        return (float)Db::getInstance()->getValue($sql);
    }

    /**
     * Pobiera checkout_form_id zamówień złożonych w okresie (do obliczeń sum opłat w trybie orders).
     *
     * @return string[]
     */
    private function listOrderIdsInRangeMulti(array $accountIds, string $dateFrom, string $dateTo): array
    {
        $from = pSQL($dateFrom . ' 00:00:00');
        $to = pSQL($dateTo . ' 23:59:59');

        $in = $this->buildIn($accountIds);

        $sql = "SELECT checkout_form_id
                FROM `" . _DB_PREFIX_ . "allegropro_order`
                WHERE id_allegropro_account IN " . $in . "
                  AND created_at_allegro BETWEEN '" . $from . "' AND '" . $to . "'";
        $rows = Db::getInstance()->executeS($sql) ?: [];
        $out = [];
        foreach ($rows as $r) {
            $id = trim((string)($r['checkout_form_id'] ?? ''));
            if ($id !== '') $out[] = $id;
        }
        return array_values(array_unique($out));
    }

    /**
     * Buduje listę kandydatów order_id (dokładny + bez myślników + lower/upper).
     * @param string[] $orderIds
     * @return string[]
     */
    private function buildOrderIdCandidates(array $orderIds): array
    {
        $c = [];
        foreach ($orderIds as $id) {
            $id = trim((string)$id);
            if ($id === '') continue;
            $c[] = $id;
            $dash = str_replace('_', '-', $id);
            if ($dash !== '' && $dash !== $id) $c[] = $dash;
            $under = str_replace('-', '_', $id);
            if ($under !== '' && $under !== $id) $c[] = $under;
            $noSep = str_replace(['-','_'], '', $id);
            if ($noSep !== '' && $noSep !== $id) $c[] = $noSep;
            $lower = strtolower($id);
            if ($lower !== $id) $c[] = $lower;
            $upper = strtoupper($id);
            if ($upper !== $id) $c[] = $upper;
        }
        return array_values(array_unique($c));
    }

    /**
     * Suma zamówień które mają opłaty zaksięgowane w okresie (billing).
     */
    private function sumOrdersTotalForBillingMulti(array $accountIds, string $dateFrom, string $dateTo): float
    {
        $in = $this->buildIn($accountIds);
        $from = pSQL($dateFrom . ' 00:00:00');
        $to = pSQL($dateTo . ' 23:59:59');

        $tn = "LOWER(IFNULL(b.type_name,''))";
        $feeWhere = $this->feeWhere('b.value_amount', $tn);

        // Suma sprzedaży dla zamówień, do których naliczono opłaty w okresie (billing) — dopasowanie po znormalizowanym UUID.
        $sql = "SELECT SUM(o.total_amount) AS sales_total
                FROM `" . _DB_PREFIX_ . "allegropro_order` o
                INNER JOIN (
                    SELECT DISTINCT b.id_allegropro_account, LOWER(REPLACE(REPLACE(b.order_id,'-',''),'_','')) AS order_key
                    FROM `" . _DB_PREFIX_ . "allegropro_billing_entry` b
                    WHERE b.id_allegropro_account IN " . $in . "
                      AND b.order_id IS NOT NULL AND b.order_id <> ''
                      AND b.occurred_at BETWEEN '" . $from . "' AND '" . $to . "'
                      AND {$feeWhere}
                ) x ON (
                    x.id_allegropro_account=o.id_allegropro_account
                    AND x.order_key = LOWER(REPLACE(REPLACE(o.checkout_form_id,'-',''),'_',''))
                )";
        return (float)Db::getInstance()->getValue($sql);
    }

    /**
     * Lista zamówień (orders) w okresie (created_at_allegro) — baza dla trybu orders.
     */
    private function listOrdersMulti(array $accountIds, string $dateFrom, string $dateTo, string $q = '', int $limit = 50, int $offset = 0): array
    {
        $from = pSQL($dateFrom . ' 00:00:00');
        $to = pSQL($dateTo . ' 23:59:59');

        $in = $this->buildIn($accountIds);

        $limit = max(1, min(500, (int)$limit));
        $offset = max(0, (int)$offset);

        $whereQ = '';
        if ($q !== '') {
            $qEsc = pSQL('%' . $q . '%');
            $whereQ = " AND (o.checkout_form_id LIKE '" . $qEsc . "' OR o.buyer_login LIKE '" . $qEsc . "')";
        }

        $sql = "SELECT o.id_allegropro_account, o.checkout_form_id, o.buyer_login, o.total_amount, o.currency, o.created_at_allegro,
                       a.label AS account_label
                FROM `" . _DB_PREFIX_ . "allegropro_order` o
                LEFT JOIN `" . _DB_PREFIX_ . "allegropro_account` a ON (a.id_allegropro_account = o.id_allegropro_account)
                WHERE o.id_allegropro_account IN " . $in . "
                  AND o.created_at_allegro BETWEEN '" . $from . "' AND '" . $to . "'" . $whereQ . "
                ORDER BY o.created_at_allegro DESC
                LIMIT " . (int)$limit . " OFFSET " . (int)$offset;

        $rows = Db::getInstance()->executeS($sql) ?: [];
        foreach ($rows as &$r) {
            $ta = (float)$r['total_amount'];
            $r['total_amount'] = ($ta > 0.0) ? $ta : null;
            $r['id_allegropro_account'] = (int)($r['id_allegropro_account'] ?? 0);
        }
        unset($r);
        return $rows;
    }

    private function getOrderRow(int $accountId, string $checkoutFormId): ?array
    {
        $cf = $this->sanitizeCheckoutFormId($checkoutFormId);
        if ($cf === '') {
            return null;
        }

        $key = pSQL($this->normalizeId($cf));

        $sql = 'SELECT checkout_form_id, buyer_login, total_amount, currency, created_at_allegro '
            . 'FROM `' . _DB_PREFIX_ . 'allegropro_order` '
            . 'WHERE id_allegropro_account=' . (int)$accountId . ' '
            . "AND (checkout_form_id='" . pSQL($cf) . "' OR LOWER(REPLACE(REPLACE(checkout_form_id,'-',''),'_',''))='" . $key . "') "
            . 'ORDER BY created_at_allegro DESC LIMIT 1';

        $rows = Db::getInstance()->executeS($sql) ?: [];
        $row = !empty($rows[0]) ? $rows[0] : null;
        if (!$row) {
            return null;
        }
        $row['total_amount'] = (float)$row['total_amount'];
        return $row;
    }

    private function normalizeId(string $id): string
    {
        $id = trim($id);
        if ($id === '') {
            return '';
        }
        $id = strtolower($id);
        $id = preg_replace('/[^a-z0-9]/', '', $id);
        return (string)$id;
    }

    private function sanitizeCheckoutFormId(string $id): string
    {
        $id = trim($id);
        $id = preg_replace('/[^A-Za-z0-9\-_=:\.]/', '', $id);
        return (string)$id;
    }
}
