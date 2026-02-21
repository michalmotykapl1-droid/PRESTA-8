<?php
namespace AllegroPro\Service;

use AllegroPro\Repository\BillingEntryRepository;
use AllegroPro\Repository\OrderPaymentRepository;

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
        $today = date('Y-m-d');

        $createdAt = (string)($orderRow['created_at_allegro'] ?? '');
        $createdYmd = '';
        if ($createdAt) {
            // created_at_allegro jest DATETIME; bierzemy YYYY-mm-dd
            $createdYmd = substr($createdAt, 0, 10);
            if (!preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/', $createdYmd)) {
                $createdYmd = '';
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
        $details = $this->report->getOrderDetails($accountId, $checkoutFormId, $wideFrom, $wideTo, true);

        // Dane płatności kupującego (z DB) – to jest jedyny "status" sensowny w tej zakładce.
        // NIE mylimy go z checkoutForm.status (np. READY_FOR_PROCESSING), bo to jest status techniczny zamówienia.
        $payment = $this->paymentRepo->getByCheckoutFormId($checkoutFormId);
        if (!is_array($payment)) {
            $payment = [];
        }

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
}
