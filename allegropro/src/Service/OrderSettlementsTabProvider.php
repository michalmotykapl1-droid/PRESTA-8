<?php
namespace AllegroPro\Service;

use AllegroPro\Repository\BillingEntryRepository;
use AllegroPro\Repository\OrderPaymentRepository;
use Db;

/**
 * Buduje dane do zakładki "Rozliczenia Allegro" w szczegółach zamówienia.
 *
 * Zasada: domyślnie czytamy z DB (to co już zostało zsynchronizowane w module).
 * API tylko przy ręcznym pobraniu (oddzielny endpoint AJAX).
 */
class OrderSettlementsTabProvider
{
    private BillingEntryRepository $billingRepo;
    private SettlementsReportService $report;
    private OrderPaymentRepository $paymentRepo;

    public function __construct()
    {
        $this->billingRepo = new BillingEntryRepository();
        // zapewnij tabelę billing dla instalacji bez reinstalacji
        $this->billingRepo->ensureSchema();

        $this->report = new SettlementsReportService($this->billingRepo);
        $this->paymentRepo = new OrderPaymentRepository();
    }

    /**
     * @param array $orderRow row z {prefix}_allegropro_order
     * @return array
     */
    public function buildForOrderRow(array $orderRow): array
    {
        $accountId = (int)($orderRow['id_allegropro_account'] ?? 0);
        $checkoutFormId = trim((string)($orderRow['checkout_form_id'] ?? ''));

        if ($accountId <= 0 || $checkoutFormId === '') {
            return [
                'ok' => 0,
                'error' => 'Brak powiązania z Allegro (konto lub checkoutFormId).',
            ];
        }

        // Zakresy do ręcznej synchronizacji (domyślne)
        // UWAGA: created_at_allegro bywa niepewne (np. import do Presty później),
        // dlatego jako bazę do zakresu "wąskiego" preferujemy datę płatności z tabeli payment.
        $today = date('Y-m-d');

        // Dane płatności kupującego (z DB) – to jest jedyny "status" sensowny w tej zakładce.
        // NIE mylimy go z checkoutForm.status (np. READY_FOR_PROCESSING), bo to jest status techniczny zamówienia.
        $payment = $this->paymentRepo->getByCheckoutFormId($checkoutFormId);
        if (!is_array($payment)) {
            $payment = [];
        }


        // 0) Synchronizuj datę płatności w PrestaShop (ps_order_payment.date_add) na podstawie Allegro finished_at.
        $psOrderId = (int)($orderRow['id_order_prestashop'] ?? 0);
        $finishedAt = (string)($payment['finished_at'] ?? '');
        if ($psOrderId > 0 && $finishedAt !== '') {
            $this->syncPrestashopPaymentDateAdd($psOrderId, $finishedAt);
        }

        // 0b) Jeśli billing-entries mają payment_id, a nie mają order_id, dopnij je do checkoutFormId.
        $payId = (string)($payment['payment_id'] ?? '');
        if ($payId !== '') {
            try { $this->billingRepo->attachOrderIdByPayment($accountId, $payId, $checkoutFormId); } catch (\Throwable $e) {}
        }

        // Uzgodnij datę płatności w PrestaShop (ps_order_payment.date_add) z Allegro payment.finished_at.
        // Starsze zamówienia mogły mieć date_add ustawione na czas importu w Preście.
        $this->syncPrestashopPaymentDateFromAllegro($orderRow, $payment, $checkoutFormId);

        $createdAt = (string)($orderRow['created_at_allegro'] ?? '');
        $createdYmd = '';
        if ($createdAt) {
            // created_at_allegro jest DATETIME; bierzemy YYYY-mm-dd
            $createdYmd = substr($createdAt, 0, 10);
            if (!preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/', $createdYmd)) {
                $createdYmd = '';
            }
        }

        // Preferuj datę zakończenia płatności (Allegro) jako bazę dla "wąskiego" zakresu.
        $paidAt = (string)($payment['finished_at'] ?? ($payment['paid_at'] ?? ''));
        if ($paidAt) {
            $paidYmd = substr($paidAt, 0, 10);
            if (preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/', $paidYmd)) {
                $createdYmd = $paidYmd;
            }
        }
        if (!$createdYmd) {
            $createdYmd = $today;
        }

        $narrowFrom = date('Y-m-d', strtotime($createdYmd . ' -3 days'));
        $narrowTo = $today;
        $wideFrom = date('Y-m-d', strtotime($today . ' -180 days'));
        $wideTo = $today;

        // Dane rozliczeń (z DB) — ignorujemy zakres dat, aby zebrać wszystkie wpisy billing dla tego zamówienia.
        try { $this->billingRepo->attachOrderIdByRawJsonCandidates($accountId, $checkoutFormId, [$checkoutFormId], $wideFrom, $wideTo); } catch (\Throwable $e) {}

        $details = $this->report->getOrderDetails($accountId, $checkoutFormId, $wideFrom, $wideTo, true);

        $payStatusRaw = strtoupper((string)($payment['status'] ?? ''));
        $paidAmount = isset($payment['paid_amount']) ? (float)$payment['paid_amount'] : 0.0;

        // Prosta interpretacja (do szybkiego oka):
        // - anulowane: gdy payment.status = CANCELLED
        // - opłacone: gdy zapłacona kwota > 0
        // - nieopłacone: w pozostałych przypadkach
        $payStatusLabel = empty($payment) ? 'Brak danych' : 'Nieopłacone';
        if ($payStatusRaw === 'CANCELLED') {
            $payStatusLabel = 'Anulowane';
        } elseif ($paidAmount > 0) {
            $payStatusLabel = 'Opłacone';
        }

        // Uzupełnij koszt dostawy + SMART (fallback gdy order_shipping nie ma kosztu)
        $shipRow = null;
        try {
            $cfEsc = pSQL($checkoutFormId);
            $shipRow = \Db::getInstance()->getRow("SELECT cost_amount, is_smart FROM " . _DB_PREFIX_ . "allegropro_order_shipping WHERE checkout_form_id='" . $cfEsc . "' ORDER BY id_allegropro_shipping DESC");
        } catch (\Exception $e) {
            $shipRow = null;
        }

        $isSmart = (int)($shipRow['is_smart'] ?? 0);
        $shipCostDb = isset($shipRow['cost_amount']) ? (float)$shipRow['cost_amount'] : 0.0;

        $order = is_array($details['order'] ?? null) ? $details['order'] : [];
        $shipCostReported = isset($order['shipping_amount']) ? (float)$order['shipping_amount'] : 0.0;

        $shipDisplay = ($shipCostDb > 0.0) ? $shipCostDb : $shipCostReported;

        // Jeśli nadal 0, a nie SMART, spróbuj różnicy total - sales (często order_shipping bywa puste dla starszych zamówień)
        if ($shipDisplay <= 0.0) {
            if ($isSmart) {
                $shipDisplay = 0.0;
            } else {
                $total = (float)($order['order_total_amount'] ?? $order['total_amount'] ?? 0);
                $sales = (float)($order['sales_amount'] ?? 0);
                $diff = $total - $sales;
                if ($diff > 0.009) {
                    $shipDisplay = $diff;
                }
            }
        }

        if (is_array($details['order'] ?? null)) {
            $details['order']['shipping_amount_display'] = (float)$shipDisplay;
            $details['order']['shipping_is_smart'] = $isSmart ? 1 : 0;
            $details['order']['shipping_smart_badge'] = ($isSmart && (float)$shipDisplay <= 0.0) ? 1 : 0;
        }

        // Ostatnia data wpisu billing dla zamówienia (proxy "ostatniej synchronizacji")
        $lastAt = '';
        if (!empty($details['items']) && is_array($details['items'])) {
            foreach ($details['items'] as $it) {
                $d = (string)($it['occurred_at'] ?? '');
                if ($d && ($lastAt === '' || $d > $lastAt)) {
                    $lastAt = $d;
                }
            }
        }

        return [
            'ok' => 1,
            'account_id' => $accountId,
            'checkout_form_id' => $checkoutFormId,

            // status płatności (Allegro payment)
            'pay_status_raw' => $payStatusRaw,
            'pay_status_label' => $payStatusLabel,

            'payment' => $payment,
            'details' => $details,
            'last_billing_at' => $lastAt,

            'ranges' => [
                'narrow_from' => $narrowFrom,
                'narrow_to' => $narrowTo,
                'wide_from' => $wideFrom,
                'wide_to' => $wideTo,
            ],
        ];
    }


    /**
     * Aktualizuje datę płatności w PrestaShop (tabela {prefix}order_payment) wg Allegro payment.finished_at.
     * Wykonuje się przy wejściu w zamówienie (render zakładki Rozliczenia).
     */
    private function syncPrestashopPaymentDateFromAllegro(array $orderRow, array $payment, string $checkoutFormId): void
    {
        $psOrderId = (int)($orderRow['id_order_prestashop'] ?? 0);
        if ($psOrderId <= 0) {
            return;
        }

        $finishedAt = trim((string)($payment['finished_at'] ?? ''));
        if ($finishedAt === '') {
            return; // nieopłacone / brak danych
        }

        try {
            $order = new \Order($psOrderId);
        } catch (\Throwable $e) {
            return;
        }
        if (!\Validate::isLoadedObject($order)) {
            return;
        }

        $ref = trim((string)($order->reference ?? ''));
        if ($ref === '') {
            return;
        }

        $psDate = $this->toPrestashopTzFromUtc($finishedAt);

        // Aktualizujemy wszystkie płatności "Allegro" dla tego zamówienia.
        // Nie opieramy się o transaction_id, bo w części wdrożeń bywa to payment_id zamiast checkoutFormId.
        try {
            \Db::getInstance()->execute(
                "UPDATE " . _DB_PREFIX_ . "order_payment
                 SET date_add = '" . pSQL($psDate) . "'
                 WHERE order_reference = '" . pSQL($ref) . "'
                   AND payment_method = 'Allegro'"
            );
        } catch (\Throwable $e) {
            // brak twardego błędu — UI ma się wyrenderować
        }
    }

    /**
     * finished_at w naszej tabeli jest trzymane jako UTC (DATETIME bez strefy) -> konwersja do PS_TIMEZONE.
     */
    private function toPrestashopTzFromUtc(string $candidate): string
    {
        $candidate = trim($candidate);
        if ($candidate === '') {
            return date('Y-m-d H:i:s');
        }

        // Ucinamy mikrosekundy (.000), jeśli występują
        $candidateTrim = preg_replace('/\.\d+$/', '', $candidate);
        if (!is_string($candidateTrim)) {
            $candidateTrim = $candidate;
        }

        $psTz = (string)\Configuration::get('PS_TIMEZONE');
        if ($psTz === '') {
            $psTz = date_default_timezone_get() ?: 'UTC';
        }

        try {
            // ISO z T/Z albo klasyczny DATETIME bez Z
            if (strpos($candidate, 'T') !== false) {
                $dt = new \DateTimeImmutable($candidate);
            } else {
                $dt = new \DateTimeImmutable($candidateTrim, new \DateTimeZone('UTC'));
            }
            return $dt->setTimezone(new \DateTimeZone($psTz))->format('Y-m-d H:i:s');
        } catch (\Throwable $e) {
            // awaryjnie zwracamy to co mamy (jeśli wygląda jak DATETIME)
            if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $candidateTrim)) {
                return $candidateTrim;
            }
            return date('Y-m-d H:i:s');
        }
    }


    /**
     * Ustawia datę płatności w PrestaShop (ps_order_payment.date_add) na faktyczną datę płatności z Allegro (finished_at).
     * Presta wiąże płatności po order_reference (nie po id_order).
     */
    private function syncPrestashopPaymentDateAdd(int $psOrderId, string $finishedAt): void
    {
        $finishedAt = trim($finishedAt);
        if ($psOrderId <= 0 || $finishedAt === '') {
            return;
        }

        // Accept "YYYY-mm-dd HH:ii:ss" or ISO-8601; convert minimalnie.
        if (preg_match('/^([0-9]{4}-[0-9]{2}-[0-9]{2})T([0-9]{2}:[0-9]{2}:[0-9]{2})/', $finishedAt, $m)) {
            $finishedAt = $m[1] . ' ' . $m[2];
        } elseif (preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/', $finishedAt)) {
            $finishedAt .= ' 00:00:00';
        }

        if (!preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2}$/', $finishedAt)) {
            return;
        }

        $ref = Db::getInstance()->getValue('SELECT reference FROM `' . _DB_PREFIX_ . 'orders` WHERE id_order=' . (int)$psOrderId);
        $ref = is_string($ref) ? trim($ref) : '';
        if ($ref === '') {
            return;
        }

        $dt = pSQL($finishedAt);

        Db::getInstance()->execute(
            "UPDATE `" . _DB_PREFIX_ . "order_payment`
             SET date_add='" . $dt . "'
             WHERE order_reference='" . pSQL($ref) . "'
               AND payment_method='Allegro'
               AND (date_add IS NULL OR date_add <> '" . $dt . "')"
        );
    }

}
