<?php
namespace AllegroPro\Service;

use AllegroPro\Repository\BillingEntryRepository;
use Db;

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
        if (is_array($accountIds)) {
            $ids = $accountIds;
        } else {
            $ids = [$accountIds];
        }
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
    public function getPeriodSummary($accountIds, string $dateFrom, string $dateTo): array
    {
        $ids = $this->normalizeAccountIds($accountIds);
        $sales = $this->sumOrdersTotalMulti($ids, $dateFrom, $dateTo);
        $cats = $this->billing->getCategorySumsMulti($ids, $dateFrom, $dateTo);
        $unassigned = $this->billing->countUnassignedMulti($ids, $dateFrom, $dateTo);

        // saldo po opłatach Allegro (nie uwzględnia kosztu towaru)
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
     * @param string $q Opcjonalny query (checkoutFormId / login kupującego) - oczekiwany już po sanityzacji.
     */
    public function getOrdersWithFees($accountIds, string $dateFrom, string $dateTo, string $q = '', int $limit = 50, int $offset = 0): array
    {
        $ids = $this->normalizeAccountIds($accountIds);
        $orders = $this->listOrdersMulti($ids, $dateFrom, $dateTo, $q, $limit, $offset);

        // Mapuj opłaty do zamówień po order_id, ale toleruj różne formaty ID
        // (np. z / bez myślników) przez dodatkową mapę znormalizowaną.
        $feeMapRaw = $this->billing->sumByOrderMulti($ids, $dateFrom, $dateTo);
        $feeMapNorm = [];
        foreach ($feeMapRaw as $aid => $ordersMap) {
            if (!is_array($ordersMap)) {
                continue;
            }
            foreach ($ordersMap as $oid => $sum) {
                $k = $this->normalizeId((string)$oid);
                if ($k === '') {
                    continue;
                }
                if (!isset($feeMapNorm[$aid])) {
                    $feeMapNorm[$aid] = [];
                }
                if (!isset($feeMapNorm[$aid][$k])) {
                    $feeMapNorm[$aid][$k] = 0.0;
                }
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
        }
        unset($o);

        return $orders;
    }

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

    public function getOrderDetails(int $accountId, string $checkoutFormId, string $dateFrom, string $dateTo): array
    {
        $order = $this->getOrderRow($accountId, $checkoutFormId);

        // Szukaj wpisów billingowych po kilku wariantach order_id.
        $cf = $this->sanitizeCheckoutFormId($checkoutFormId);
        $candidates = [];
        if ($cf !== '') {
            $candidates[] = $cf;
            $noDash = str_replace('-', '', $cf);
            if ($noDash !== '' && $noDash !== $cf) {
                $candidates[] = $noDash;
            }
            $lower = strtolower($cf);
            if ($lower !== $cf) {
                $candidates[] = $lower;
            }
            $upper = strtoupper($cf);
            if ($upper !== $cf) {
                $candidates[] = $upper;
            }
        }

        $items = $this->billing->listForOrderCandidates($accountId, $candidates ?: [$checkoutFormId], $dateFrom, $dateTo);

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

        // prowizje
        if ($typeId === 'SUC' || $this->contains($n, 'prowiz')) {
            return 'commission';
        }

        // smart
        if ($this->contains($n, 'smart')) {
            return 'smart';
        }

        // dostawa
        if ($this->contains($n, 'dostaw') || $this->contains($n, 'przesy')) {
            return 'delivery';
        }

        // reklama / promowanie
        if ($this->contains($n, 'promow') || $this->contains($n, 'reklam')) {
            return 'promotion';
        }

        // zwroty / rabaty / korekty
        if ($this->contains($n, 'zwrot') || $this->contains($n, 'rabat') || $this->contains($n, 'korekt') || $this->contains($n, 'rekompens')) {
            return 'refunds';
        }

        return 'other';
    }

    private function sumOrdersTotalMulti(array $accountIds, string $dateFrom, string $dateTo): float
    {
        $from = pSQL($dateFrom . ' 00:00:00');
        $to = pSQL($dateTo . ' 23:59:59');

        $ids = $this->normalizeAccountIds($accountIds);
        $in = $this->buildIn($ids);

        $sql = "SELECT SUM(total_amount) FROM `" . _DB_PREFIX_ . "allegropro_order`
                WHERE id_allegropro_account IN " . $in . "
                  AND created_at_allegro BETWEEN '" . $from . "' AND '" . $to . "'";
        return (float)Db::getInstance()->getValue($sql);
    }

    private function listOrdersMulti(array $accountIds, string $dateFrom, string $dateTo, string $q = '', int $limit = 50, int $offset = 0): array
    {
        $from = pSQL($dateFrom . ' 00:00:00');
        $to = pSQL($dateTo . ' 23:59:59');

        $ids = $this->normalizeAccountIds($accountIds);
        $in = $this->buildIn($ids);

        $limit = max(1, min(500, (int)$limit));
        $offset = max(0, (int)$offset);

        $whereQ = '';
        if ($q !== '') {
            // allow searching by checkoutFormId or buyer login
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
            $r['total_amount'] = (float)$r['total_amount'];
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

        // Nie używamy Db::getRow(), bo potrafi doklejać LIMIT 1.
        // Dla bezpieczeństwa pobieramy listę i bierzemy pierwszy rekord.
        $sql = 'SELECT checkout_form_id, buyer_login, total_amount, currency, created_at_allegro '
            . 'FROM `' . _DB_PREFIX_ . 'allegropro_order` '
            . 'WHERE id_allegropro_account=' . (int)$accountId . ' '
            . "AND checkout_form_id='" . pSQL($cf) . "' "
            . 'ORDER BY created_at_allegro DESC';

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
        // allow UUID/base64-like ids: letters, digits, dash, underscore, colon, equals, dot
        $id = preg_replace('/[^A-Za-z0-9\-_=:\.]/', '', $id);
        return (string)$id;
    }
}
