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

    /**
     * @param string $q Opcjonalny query (checkoutFormId / login kupującego) - oczekiwany już po sanityzacji.
     */
    public function getOrdersWithFees(int $accountId, string $dateFrom, string $dateTo, string $q = ''): array
    {
        $orders = $this->listOrders($accountId, $dateFrom, $dateTo, $q);

        // Mapuj opłaty do zamówień po order_id, ale toleruj różne formaty ID
        // (np. z / bez myślników) przez dodatkową mapę znormalizowaną.
        $feeMapRaw = $this->billing->sumByOrder($accountId, $dateFrom, $dateTo);
        $feeMapNorm = [];
        foreach ($feeMapRaw as $oid => $sum) {
            $k = $this->normalizeId((string)$oid);
            if ($k === '') {
                continue;
            }
            if (!isset($feeMapNorm[$k])) {
                $feeMapNorm[$k] = 0.0;
            }
            $feeMapNorm[$k] += (float)$sum;
        }

        foreach ($orders as &$o) {
            $cf = (string)$o['checkout_form_id'];
            $fees = 0.0;
            if (isset($feeMapRaw[$cf])) {
                $fees = (float)$feeMapRaw[$cf];
            } else {
                $k = $this->normalizeId($cf);
                if ($k !== '' && isset($feeMapNorm[$k])) {
                    $fees = (float)$feeMapNorm[$k];
                }
            }
            $o['fees_total'] = $fees;
            $o['net_after_fees'] = (float)$o['total_amount'] + $fees;
        }
        unset($o);

        return $orders;
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

        // prowizje
        if ($typeId === 'SUC' || str_contains($n, 'prowiz')) {
            return 'commission';
        }

        // smart
        if (str_contains($n, 'smart')) {
            return 'smart';
        }

        // dostawa
        if (str_contains($n, 'dostaw') || str_contains($n, 'przesy')) {
            return 'delivery';
        }

        // reklama / promowanie
        if (str_contains($n, 'promow') || str_contains($n, 'reklam')) {
            return 'promotion';
        }

        // zwroty / rabaty / korekty (często dodatnie kwoty)
        if (str_contains($n, 'zwrot') || str_contains($n, 'rabat') || str_contains($n, 'korekt') || str_contains($n, 'rekompens')) {
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

    private function listOrders(int $accountId, string $dateFrom, string $dateTo, string $q = ''): array
    {
        $from = pSQL($dateFrom . ' 00:00:00');
        $to = pSQL($dateTo . ' 23:59:59');

        $whereQ = '';
        if ($q !== '') {
            // allow searching by checkoutFormId or buyer login
            $qEsc = pSQL('%' . $q . '%');
            $whereQ = " AND (checkout_form_id LIKE '" . $qEsc . "' OR buyer_login LIKE '" . $qEsc . "')";
        }

        $sql = "SELECT checkout_form_id, buyer_login, total_amount, currency, created_at_allegro
                FROM `" . _DB_PREFIX_ . "allegropro_order`
                WHERE id_allegropro_account=" . (int)$accountId . "
                  AND created_at_allegro BETWEEN '" . $from . "' AND '" . $to . "'" . $whereQ . "
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
        $cf = $this->sanitizeCheckoutFormId($checkoutFormId);
        if ($cf === '') {
            return null;
        }

        // WAŻNE:
        // Db::getRow() w PrestaShop potrafi doklejać wewnętrznie "LIMIT 1".
        // W połączeniu z niektórymi SQL_MODE / wersjami MariaDB może to kończyć się
        // błędem 1064 "near 'LIMIT 1'".
        // Dlatego pobieramy pierwszy wiersz przez executeS() + własny LIMIT 1.
        $sql = 'SELECT checkout_form_id, buyer_login, total_amount, currency, created_at_allegro '
            . 'FROM `' . _DB_PREFIX_ . 'allegropro_order` '
            . 'WHERE id_allegropro_account=' . (int)$accountId . ' '
            . 'AND checkout_form_id=\'' . pSQL($cf) . '\' '
            . 'ORDER BY created_at_allegro DESC';

        $rows = Db::getInstance()->executeS($sql . ' LIMIT 1') ?: [];
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
        // allow UUID/base64-like ids: letters, digits, dash, underscore, colon, equals
        $id = preg_replace('/[^A-Za-z0-9\-_=:\.]/', '', $id);
        return (string)$id;
    }
}
