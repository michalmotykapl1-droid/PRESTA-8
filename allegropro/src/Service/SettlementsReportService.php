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

    public function getPeriodSummary(int $accountId, string $dateFrom, string $dateTo): array
    {
        $sales = $this->sumOrdersTotal($accountId, $dateFrom, $dateTo);
        $cats = $this->billing->getCategorySums($accountId, $dateFrom, $dateTo);
        $unassigned = $this->billing->countUnassigned($accountId, $dateFrom, $dateTo);

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
            'net_after_fees' => $netAfterFees,
        ];
    }

    public function getOrdersWithFees(int $accountId, string $dateFrom, string $dateTo): array
    {
        $orders = $this->listOrders($accountId, $dateFrom, $dateTo);
        $feeMap = $this->billing->sumByOrder($accountId, $dateFrom, $dateTo);

        foreach ($orders as &$o) {
            $cf = (string)$o['checkout_form_id'];
            $fees = (float)($feeMap[$cf] ?? 0);
            $o['fees_total'] = $fees;
            $o['net_after_fees'] = (float)$o['total_amount'] + $fees;
        }
        unset($o);

        return $orders;
    }

    public function getOrderDetails(int $accountId, string $checkoutFormId, string $dateFrom, string $dateTo): array
    {
        $order = $this->getOrderRow($accountId, $checkoutFormId);
        $items = $this->billing->listForOrder($accountId, $checkoutFormId, $dateFrom, $dateTo);

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
            $cat = $this->classify((string)$it['type_id'], (string)$it['type_name'], $amount);
            $it['category'] = $cat;
            $cats[$cat] += $amount;
        }
        unset($it);

        $netAfterFees = $order ? ((float)$order['total_amount'] + (float)$cats['total']) : (float)$cats['total'];

        return [
            'order' => $order,
            'items' => $items,
            'cats' => $cats,
            'net_after_fees' => $netAfterFees,
        ];
    }

    private function classify(string $typeId, string $typeName, float $amount): string
    {
        $n = mb_strtolower($typeName);
        if ($typeId === 'SUC' || str_contains($n, 'prowiz')) {
            return 'commission';
        }
        if (str_contains($n, 'smart')) {
            return 'smart';
        }
        if (str_contains($n, 'dostaw') || str_contains($n, 'przesy')) {
            return 'delivery';
        }
        if (str_contains($n, 'promow') || str_contains($n, 'reklam')) {
            return 'promotion';
        }
        if ($amount > 0 || str_contains($n, 'zwrot')) {
            return 'refunds';
        }
        return 'other';
    }

    private function sumOrdersTotal(int $accountId, string $dateFrom, string $dateTo): float
    {
        $from = pSQL($dateFrom . ' 00:00:00');
        $to = pSQL($dateTo . ' 23:59:59');
        $sql = "SELECT SUM(total_amount) FROM `" . _DB_PREFIX_ . "allegropro_order`
                WHERE id_allegropro_account=" . (int)$accountId . "
                  AND created_at_allegro BETWEEN '" . $from . "' AND '" . $to . "'";
        return (float)Db::getInstance()->getValue($sql);
    }

    private function listOrders(int $accountId, string $dateFrom, string $dateTo): array
    {
        $from = pSQL($dateFrom . ' 00:00:00');
        $to = pSQL($dateTo . ' 23:59:59');

        $sql = "SELECT checkout_form_id, buyer_login, total_amount, currency, created_at_allegro
                FROM `" . _DB_PREFIX_ . "allegropro_order`
                WHERE id_allegropro_account=" . (int)$accountId . "
                  AND created_at_allegro BETWEEN '" . $from . "' AND '" . $to . "'
                ORDER BY created_at_allegro DESC
                LIMIT 500";

        $rows = Db::getInstance()->executeS($sql) ?: [];
        foreach ($rows as &$r) {
            $r['total_amount'] = (float)$r['total_amount'];
        }
        unset($r);
        return $rows;
    }

    private function getOrderRow(int $accountId, string $checkoutFormId): ?array
    {
        $cf = pSQL($checkoutFormId);
        $sql = "SELECT checkout_form_id, buyer_login, total_amount, currency, created_at_allegro
                FROM `" . _DB_PREFIX_ . "allegropro_order`
                WHERE id_allegropro_account=" . (int)$accountId . "
                  AND checkout_form_id='" . $cf . "'
                LIMIT 1";
        $row = Db::getInstance()->getRow($sql);
        if (!$row) {
            return null;
        }
        $row['total_amount'] = (float)$row['total_amount'];
        return $row;
    }
}
