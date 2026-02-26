<?php
namespace AllegroPro\Repository;

use Db;
use DbQuery;

/**
 * Widok "Wpłaty transakcji" (per checkoutFormId) – dane z lokalnych tabel:
 * - {prefix}_allegropro_order (konto + mapowanie do id_order_prestashop)
 * - {prefix}_allegropro_order_payment (payment_id, paid_amount, finished_at)
 */
class CashflowsTransactionsRepository
{
    public function countCheckoutForms(int $accountId, string $dateFrom, string $dateTo, array $filters = []): int
    {
        if ($accountId <= 0) {
            return 0;
        }

        $start = pSQL($dateFrom . ' 00:00:00');
        $end = pSQL($dateTo . ' 23:59:59');

        $buyerLogin = trim((string)($filters['buyer_login'] ?? ''));
        $paymentId = trim((string)($filters['payment_id'] ?? ''));

        $q = new DbQuery();
        $q->select('COUNT(DISTINCT o.checkout_form_id) AS c')
            ->from('allegropro_order', 'o')
            ->innerJoin('allegropro_order_payment', 'op', 'op.checkout_form_id = o.checkout_form_id')
            ->where('o.id_allegropro_account=' . (int)$accountId)
            ->where("op.finished_at >= '{$start}'")
            ->where("op.finished_at <= '{$end}'");

        if ($buyerLogin !== '') {
            $q->where("o.buyer_login LIKE '%" . pSQL($buyerLogin) . "%'");
        }
        if ($paymentId !== '') {
            $q->where("op.payment_id='" . pSQL($paymentId) . "'");
        }

        $row = Db::getInstance()->getRow($q);
        return (int)($row['c'] ?? 0);
    }

    /**
     * Zwraca stronę checkoutFormId (agregacja po CF).
     *
     * @return array<int,array<string,mixed>>
     */
    public function findCheckoutFormsPage(int $accountId, string $dateFrom, string $dateTo, array $filters, int $limit, int $offset): array
    {
        if ($accountId <= 0) {
            return [];
        }

        $start = pSQL($dateFrom . ' 00:00:00');
        $end = pSQL($dateTo . ' 23:59:59');

        $buyerLogin = trim((string)($filters['buyer_login'] ?? ''));
        $paymentId = trim((string)($filters['payment_id'] ?? ''));

        $q = new DbQuery();
        $q->select('o.checkout_form_id, o.id_order_prestashop, o.buyer_login, o.currency')
            ->select('MAX(op.finished_at) AS finished_at')
            ->select('SUM(op.paid_amount) AS paid_amount')
            ->from('allegropro_order', 'o')
            ->innerJoin('allegropro_order_payment', 'op', 'op.checkout_form_id = o.checkout_form_id')
            ->where('o.id_allegropro_account=' . (int)$accountId)
            ->where("op.finished_at >= '{$start}'")
            ->where("op.finished_at <= '{$end}'");

        if ($buyerLogin !== '') {
            $q->where("o.buyer_login LIKE '%" . pSQL($buyerLogin) . "%'");
        }
        if ($paymentId !== '') {
            $q->where("op.payment_id='" . pSQL($paymentId) . "'");
        }

        $q->groupBy('o.checkout_form_id');
        $q->orderBy('finished_at DESC');
        $q->limit((int)$limit, (int)$offset);

        return Db::getInstance()->executeS($q) ?: [];
    }

    /**
     * Zwraca szczegóły payment_id dla listy checkoutFormId.
     *
     * @param array<int,string> $checkoutFormIds
     * @return array<string,array<int,array<string,mixed>>>  map[checkout_form_id] => list(payments)
     */
    public function getPaymentsForCheckoutForms(array $checkoutFormIds): array
    {
        $checkoutFormIds = array_values(array_filter(array_map('strval', $checkoutFormIds)));
        if (empty($checkoutFormIds)) {
            return [];
        }

        $in = [];
        foreach ($checkoutFormIds as $cf) {
            $in[] = "'" . pSQL($cf) . "'";
        }
        $inSql = implode(',', $in);

        $q = new DbQuery();
        $q->select('op.checkout_form_id, op.payment_id, op.paid_amount, op.status AS payment_status, op.provider, op.finished_at')
            ->from('allegropro_order_payment', 'op')
            ->where("op.checkout_form_id IN ({$inSql})")
            ->orderBy('op.finished_at DESC, op.id_allegropro_payment DESC');

        $rows = Db::getInstance()->executeS($q) ?: [];
        $out = [];
        foreach ($rows as $r) {
            $cf = (string)($r['checkout_form_id'] ?? '');
            if ($cf === '') {
                continue;
            }
            if (!isset($out[$cf])) {
                $out[$cf] = [];
            }
            $out[$cf][] = $r;
        }
        return $out;
    }
}
