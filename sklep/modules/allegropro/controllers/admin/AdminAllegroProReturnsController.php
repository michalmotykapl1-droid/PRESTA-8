<?php
/**
 * ALLEGRO PRO - Back Office controller
 *
 * ZWROTY:
 *  1) Zwroty produktów (zwroty klienckie) – Allegro API: GET /order/customer-returns
 *  2) Zwroty przesyłek nieodebranych / zwróconych do nadawcy – na podstawie statusów trackingu przesyłek
 */

use AllegroPro\Repository\AccountRepository;
use AllegroPro\Service\HttpClient;
use AllegroPro\Service\AllegroApiClient;

class AdminAllegroProReturnsController extends ModuleAdminController
{
    private AccountRepository $accounts;

    public function __construct()
    {
        parent::__construct();
        $this->bootstrap = true;
        $this->accounts = new AccountRepository();
    }

    public function setMedia($isNewTheme = false)
    {
        parent::setMedia($isNewTheme);
        if (!empty($this->module)) {
            $cssLocal = $this->module->getLocalPath() . 'views/css/returns.css';
            $jsLocal = $this->module->getLocalPath() . 'views/js/returns.js';

            if (is_file($cssLocal)) {
                $this->addCSS($this->module->getPathUri() . 'views/css/returns.css');
            }
            if (is_file($jsLocal)) {
                $this->addJS($this->module->getPathUri() . 'views/js/returns.js');
            }
        }
    }

    public function initContent()
    {
        parent::initContent();

        if (isset($this->module) && method_exists($this->module, 'ensureTabs')) {
            $this->module->ensureTabs();
        }

        $accounts = $this->accounts->all();
        $defaultAccountId = $this->resolveDefaultAccountId($accounts);

        $selectedAccountId = (int)Tools::getValue('id_allegropro_account', 0);
        if ($selectedAccountId <= 0) {
            $selectedAccountId = $defaultAccountId;
        }

        $selectedAccount = $this->findAccount($accounts, $selectedAccountId);

        $dateFrom = (string)Tools::getValue('date_from', '');
        $dateTo = (string)Tools::getValue('date_to', '');
        if ($dateFrom === '' || $dateTo === '') {
            $dateFrom = date('Y-m-d', strtotime('-30 days'));
            $dateTo = date('Y-m-d');
        }
        $dateFrom = $this->sanitizeYmd($dateFrom) ?: date('Y-m-d', strtotime('-30 days'));
        $dateTo = $this->sanitizeYmd($dateTo) ?: date('Y-m-d');

        // ---------- 1) Zwroty produktów (zwroty klienckie) ----------
        $crStatus = trim((string)Tools::getValue('cr_status', ''));
        $crOrderId = trim((string)Tools::getValue('cr_order_id', ''));
        $crBuyerLogin = trim((string)Tools::getValue('cr_buyer_login', ''));
        $crReference = trim((string)Tools::getValue('cr_reference', ''));

        $crPerPage = (int)Tools::getValue('cr_per_page', 50);
        if (!in_array($crPerPage, [25, 50, 100, 200, 500, 1000], true)) {
            $crPerPage = 50;
        }

        $crPage = max(1, (int)Tools::getValue('cr_page', 1));
        $crOffset = ($crPage - 1) * $crPerPage;

        $customerReturns = [];
        $customerReturnsCount = 0;
        $customerReturnsError = '';

        $customerReturnDetails = null;
        $customerReturnDetailsError = '';
        // $customerReturnDetailsJson (raw JSON) było używane wyłącznie do debugowania.
        $detailId = '';

        if ($selectedAccount && !empty($selectedAccount['access_token'])) {
            $api = new AllegroApiClient(new HttpClient(), $this->accounts);

            $query = [
                'limit' => $crPerPage,
                'offset' => $crOffset,
                'createdAt.gte' => $this->ymdToIsoStart($dateFrom),
                'createdAt.lte' => $this->ymdToIsoEnd($dateTo),
            ];

            if ($crStatus !== '') {
                $query['status'] = $this->sanitizeEnum($crStatus);
            }
            if ($crOrderId !== '') {
                $query['orderId'] = $this->sanitizeId($crOrderId);
            }
            if ($crBuyerLogin !== '') {
                $query['buyer.login'] = $this->sanitizeLogin($crBuyerLogin);
            }
            if ($crReference !== '') {
                $query['referenceNumber'] = $this->sanitizeReferenceNumber($crReference);
            }

            $resp = $api->getWithAcceptFallbacks(
                $selectedAccount,
                '/order/customer-returns',
                $query,
                ['application/vnd.allegro.beta.v1+json', 'application/vnd.allegro.public.v1+json', 'application/json', '*/*']
            );

            if (!empty($resp['ok']) && is_array($resp['json'])) {
                $customerReturnsCount = (int)($resp['json']['count'] ?? 0);
                $list = $resp['json']['customerReturns'] ?? [];
                if (!is_array($list)) {
                    $list = [];
                }

                foreach ($list as $row) {
                    if (!is_array($row)) {
                        continue;
                    }
                    $customerReturns[] = $this->mapCustomerReturnRow($row);
                }

                // Uzupełnienie listy o informacje o zwrotach pieniędzy po stronie sklepu (PrestaShop)
                // na podstawie mapowania: checkout_form_id -> id_order_prestashop.
                $this->attachAllegroRefundInfoToCustomerReturns((int)$selectedAccountId, $customerReturns);
            } else {
                $customerReturnsError = 'Nie udało się pobrać zwrotów klienckich z Allegro (HTTP ' . (int)($resp['code'] ?? 0) . ').';
            }

            // szczegóły konkretnego zwrotu
            $detailId = $this->sanitizeId((string)Tools::getValue('customer_return_id', ''));
            if ($detailId !== '') {
                $detailResp = $api->getWithAcceptFallbacks(
                    $selectedAccount,
                    '/order/customer-returns/' . rawurlencode($detailId),
                    [],
                    ['application/vnd.allegro.beta.v1+json', 'application/vnd.allegro.public.v1+json', 'application/json', '*/*']
                );

                if (!empty($detailResp['ok']) && is_array($detailResp['json'])) {
                    $customerReturnDetails = $this->mapCustomerReturnDetails($detailResp['json']);

                    // Informacje o zwrotach pieniędzy w PrestaShop dla tego zamówienia (jeśli jest sparowane).
                    $this->attachAllegroRefundInfoToCustomerReturnDetails((int)$selectedAccountId, $customerReturnDetails);
                } else {
                    $customerReturnDetailsError = 'Nie udało się pobrać szczegółów zwrotu (HTTP ' . (int)($detailResp['code'] ?? 0) . ').';
                }
            }
        } else {
            if (empty($accounts)) {
                $customerReturnsError = 'Brak kont Allegro (dodaj konto w zakładce Konta).';
            } else {
                $customerReturnsError = 'Wybrane konto nie ma tokena dostępu (połącz konto w zakładce Konta).';
            }
        }

        $customerReturnsTotalPages = max(1, (int)ceil($customerReturnsCount / max(1, $crPerPage)));
        if ($crPage > $customerReturnsTotalPages) {
            $crPage = $customerReturnsTotalPages;
        }

        // ---------- 2) Zwroty przesyłek (nieodebrane / zwrócone) ----------
        $srQuery = trim((string)Tools::getValue('sr_query', ''));

        $srPerPage = (int)Tools::getValue('sr_per_page', 25);
        if (!in_array($srPerPage, [10, 25, 50, 100], true)) {
            $srPerPage = 25;
        }

        $srPage = max(1, (int)Tools::getValue('sr_page', 1));
        $srOffset = ($srPage - 1) * $srPerPage;

        $returnedShipments = [];
        $returnedShipmentsCount = 0;

        if ($selectedAccountId > 0) {
            $returnedShipmentsCount = $this->countReturnedShipments($selectedAccountId, $dateFrom, $dateTo, $srQuery);
            $returnedShipments = $this->getReturnedShipments($selectedAccountId, $dateFrom, $dateTo, $srQuery, $srPerPage, $srOffset);
        }

        $returnedShipmentsTotalPages = max(1, (int)ceil($returnedShipmentsCount / max(1, $srPerPage)));
        if ($srPage > $returnedShipmentsTotalPages) {
            $srPage = $returnedShipmentsTotalPages;
        }

        // tracking details (dla wybranej przesyłki)
        $shipmentTracking = null;
        $shipmentTrackingError = '';

        $trackCarrier = $this->sanitizeEnum((string)Tools::getValue('sr_carrier_id', ''));
        $trackWaybill = $this->sanitizeId((string)Tools::getValue('sr_waybill', ''));

        if ($selectedAccount && $trackCarrier !== '' && $trackWaybill !== '') {
            $api = new AllegroApiClient(new HttpClient(), $this->accounts);
            $trackResp = $api->getWithAcceptFallbacks(
                $selectedAccount,
                '/order/carriers/' . rawurlencode($trackCarrier) . '/tracking',
                ['waybill' => $trackWaybill],
                ['application/vnd.allegro.public.v1+json', 'application/json', '*/*']
            );

            if (!empty($trackResp['ok']) && is_array($trackResp['json'])) {
                $shipmentTracking = $this->mapTrackingDetails($trackResp['json']);
            } else {
                $shipmentTrackingError = 'Nie udało się pobrać historii trackingu (HTTP ' . (int)($trackResp['code'] ?? 0) . ').';
            }
        }

        // URL builder (do paginacji + szczegółów)
        $baseParams = [
            'id_allegropro_account' => (int)$selectedAccountId,
            'date_from' => $dateFrom,
            'date_to' => $dateTo,

            'cr_status' => $crStatus,
            'cr_order_id' => $crOrderId,
            'cr_buyer_login' => $crBuyerLogin,
            'cr_reference' => $crReference,
            'cr_per_page' => $crPerPage,

            'sr_query' => $srQuery,
            'sr_per_page' => $srPerPage,
        ];

        $adminBase = $this->context->link->getAdminLink('AdminAllegroProReturns');
        $queryBase = http_build_query($baseParams);

        $this->context->smarty->assign([
            'admin_link' => $adminBase,

            'allegropro_accounts' => $accounts,
            'allegropro_selected_account_id' => (int)$selectedAccountId,
            'allegropro_date_from' => $dateFrom,
            'allegropro_date_to' => $dateTo,

            // customer returns
            'allegropro_cr_status' => $crStatus,
            'allegropro_cr_order_id' => $crOrderId,
            'allegropro_cr_buyer_login' => $crBuyerLogin,
            'allegropro_cr_reference' => $crReference,
            'allegropro_cr_per_page' => $crPerPage,
            'allegropro_cr_page' => $crPage,
            'allegropro_cr_total_pages' => $customerReturnsTotalPages,
            'allegropro_cr_total_rows' => $customerReturnsCount,
            'allegropro_customer_returns' => $customerReturns,
            'allegropro_customer_returns_error' => $customerReturnsError,
            'allegropro_customer_return_id' => $detailId,
            'allegropro_customer_return_details' => $customerReturnDetails,
            'allegropro_customer_return_details_error' => $customerReturnDetailsError,

            // returned shipments
            'allegropro_sr_query' => $srQuery,
            'allegropro_sr_per_page' => $srPerPage,
            'allegropro_sr_page' => $srPage,
            'allegropro_sr_total_pages' => $returnedShipmentsTotalPages,
            'allegropro_sr_total_rows' => $returnedShipmentsCount,
            'allegropro_returned_shipments' => $returnedShipments,

            // tracking
            'allegropro_sr_tracking' => $shipmentTracking,
            'allegropro_sr_tracking_error' => $shipmentTrackingError,
            'allegropro_sr_tracking_carrier' => $trackCarrier,
            'allegropro_sr_tracking_waybill' => $trackWaybill,

            // URL helper base params
            'allegropro_returns_base_params' => $baseParams,
            'allegropro_query_base' => $queryBase,
        ]);

        $this->setTemplate('returns.tpl');
    }

    private function resolveDefaultAccountId(array $accounts): int
    {
        $selected = (int)Tools::getValue('id_allegropro_account');
        if ($selected > 0) {
            return $selected;
        }

        foreach ($accounts as $a) {
            if ((int)($a['is_default'] ?? 0) === 1) {
                return (int)$a['id_allegropro_account'];
            }
        }

        if (!empty($accounts)) {
            return (int)$accounts[0]['id_allegropro_account'];
        }

        return 0;
    }

    private function findAccount(array $accounts, int $id): ?array
    {
        foreach ($accounts as $a) {
            if ((int)($a['id_allegropro_account'] ?? 0) === (int)$id) {
                return $a;
            }
        }
        return null;
    }

    private function mapCustomerReturnRow(array $row): array
    {
        $items = $row['items'] ?? [];
        if (!is_array($items)) {
            $items = [];
        }

        $itemsCount = 0;
        $itemsTotal = 0.0;
        $itemsCurrency = 'PLN';

        foreach ($items as $it) {
            if (!is_array($it)) {
                continue;
            }
            $qty = isset($it['quantity']) ? (int)$it['quantity'] : 0;
            $itemsCount += max(0, $qty);

            $price = $it['price'] ?? null;
            if (is_array($price)) {
                $amount = isset($price['amount']) ? (float)$price['amount'] : 0.0;
                $curr = isset($price['currency']) ? (string)$price['currency'] : '';
                if ($curr !== '') {
                    $itemsCurrency = $curr;
                }
                if ($qty > 0 && $amount > 0) {
                    $itemsTotal += ($amount * $qty);
                }
            }
        }

        $parcels = $row['parcels'] ?? [];
        if (!is_array($parcels)) {
            $parcels = [];
        }

        $firstWaybill = '';
        $firstCarrier = '';
        foreach ($parcels as $p) {
            if (!is_array($p)) {
                continue;
            }
            if ($firstWaybill === '' && !empty($p['waybill'])) {
                $firstWaybill = (string)$p['waybill'];
            }
            if ($firstCarrier === '' && !empty($p['carrierId'])) {
                $firstCarrier = (string)$p['carrierId'];
            }
        }

        $status = (string)($row['status'] ?? '');

        return [
            'id' => (string)($row['id'] ?? ''),
            'createdAt' => (string)($row['createdAt'] ?? ''),
            'referenceNumber' => (string)($row['referenceNumber'] ?? ''),
            'orderId' => (string)($row['orderId'] ?? ''),
            'buyer_login' => (string)($row['buyer']['login'] ?? ''),
            'buyer_email' => (string)($row['buyer']['email'] ?? ''),
            'status' => $status,
            'status_label' => $this->mapCustomerReturnStatusLabel($status),
            'status_class' => $this->mapCustomerReturnStatusClass($status),
            'items_count' => $itemsCount,
            'items_total' => $itemsTotal,
            'items_total_fmt' => number_format((float)$itemsTotal, 2, '.', ' '),
            'items_currency' => $itemsCurrency,
            'waybill' => $firstWaybill,
            'carrierId' => $firstCarrier,
        ];
    }

    private function mapCustomerReturnDetails(array $json): array
    {
        // Maskujemy numer konta (bezpieczniej w BO).
        if (isset($json['refund']['bankAccount']['accountNumber'])) {
            $acc = (string)$json['refund']['bankAccount']['accountNumber'];
            $json['refund']['bankAccount']['accountNumber_masked'] = $this->maskAccountNumber($acc);
        }
        if (isset($json['refund']['bankAccount']['iban'])) {
            $iban = (string)$json['refund']['bankAccount']['iban'];
            $json['refund']['bankAccount']['iban_masked'] = $this->maskAccountNumber($iban);
        }

        $status = (string)($json['status'] ?? '');
        $json['_status_label'] = $this->mapCustomerReturnStatusLabel($status);
        $json['_status_class'] = $this->mapCustomerReturnStatusClass($status);

        // Tłumaczenie powodów zwrotu dla kilku najczęstszych (reszta zostaje jako kod).
        if (!empty($json['items']) && is_array($json['items'])) {
            foreach ($json['items'] as $k => $it) {
                if (!is_array($it)) {
                    continue;
                }
                $type = (string)($it['reason']['type'] ?? '');
                if ($type !== '') {
                    $json['items'][$k]['reason']['type_label'] = $this->mapReturnReasonLabel($type);
                }
            }
        }

        return $json;
    }

    private function maskAccountNumber(string $s): string
    {
        $s = trim($s);
        if ($s === '') {
            return '';
        }

        $plain = preg_replace('/\s+/', '', $s);
        $len = strlen($plain);

        if ($len <= 8) {
            return str_repeat('*', max(0, $len - 2)) . substr($plain, -2);
        }

        $start = substr($plain, 0, 2);
        $end = substr($plain, -4);
        return $start . str_repeat('*', max(0, $len - 6)) . $end;
    }

    private function mapCustomerReturnStatusLabel(string $status): string
    {
        $s = strtoupper(trim($status));
        $map = [
            'CREATED' => 'Utworzony',
            'IN_TRANSIT' => 'W drodze (zwrot w transporcie)',
            'DELIVERED' => 'Dostarczony',
            'FINISHED' => 'Zakończony (zwrot pieniędzy)',
            'FINISHED_APT' => 'Zakończony',
            'REJECTED' => 'Odrzucony',
            'COMMISSION_REFUND_CLAIMED' => 'Wniosek o zwrot prowizji złożony',
            'COMMISSION_REFUNDED' => 'Prowizja zwrócona',
            'WAREHOUSE_DELIVERED' => 'Dostarczony do Magazynu Allegro',
            'WAREHOUSE_VERIFICATION' => 'Weryfikacja w Magazynie Allegro',
        ];

        // Zamiast pokazywać surowy kod, zwracamy czytelny opis.
        return $map[$s] ?? ($s !== '' ? 'Inny status' : '—');
    }

    private function mapCustomerReturnStatusClass(string $status): string
    {
        $s = strtoupper(trim($status));
        $map = [
            'CREATED' => 'created',
            'IN_TRANSIT' => 'in-transit',
            'DELIVERED' => 'delivered',
            'FINISHED' => 'finished',
            'FINISHED_APT' => 'finished',
            'REJECTED' => 'rejected',
            'COMMISSION_REFUND_CLAIMED' => 'commission-claimed',
            'COMMISSION_REFUNDED' => 'commission-refunded',
            'WAREHOUSE_DELIVERED' => 'warehouse-delivered',
            'WAREHOUSE_VERIFICATION' => 'warehouse-verification',
        ];
        return $map[$s] ?? 'unknown';
    }

    private function mapReturnReasonLabel(string $reasonType): string
    {
        $s = strtoupper(trim($reasonType));
        if ($s === '') {
            return '—';
        }

        $map = [
            'MISTAKE' => 'Zakup przez pomyłkę',
            'NOT_AS_DESCRIBED' => 'Niezgodny z opisem',
            'DAMAGED' => 'Uszkodzony',
            'NO_LONGER_NEEDED' => 'Niepotrzebny / rezygnacja',
            'WRONG_ITEM' => 'Błędny towar',
            'DIFFERENT' => 'Inny niż zamówiony',
            'OTHER' => 'Inny powód',
            'NONE' => 'Brak (nie podano)',
        ];

        return $map[$s] ?? 'Inny powód';
    }

    private function mapTrackingDetails(array $json): array
    {
        $out = [
            'carrierId' => (string)($json['carrierId'] ?? ''),
            'waybills' => [],
        ];

        $waybills = $json['waybills'] ?? [];
        if (!is_array($waybills)) {
            $waybills = [];
        }

        foreach ($waybills as $wb) {
            if (!is_array($wb)) {
                continue;
            }
            $trackingDetails = $wb['trackingDetails'] ?? null;
            if (!is_array($trackingDetails)) {
                $trackingDetails = [];
            }

            $statuses = $trackingDetails['statuses'] ?? [];
            if (!is_array($statuses)) {
                $statuses = [];
            }

            $mapped = [];
            foreach ($statuses as $st) {
                if (!is_array($st)) {
                    continue;
                }
                $code = (string)($st['code'] ?? '');
                $mapped[] = [
                    'occurredAt' => (string)($st['occurredAt'] ?? ''),
                    'code' => $code,
                    'label' => $this->mapShipmentTrackingStatusLabel($code),
                    'class' => $this->mapShipmentTrackingStatusClass($code),
                    'description' => (string)($st['description'] ?? ''),
                ];
            }

            $out['waybills'][] = [
                'waybill' => (string)($wb['waybill'] ?? ''),
                'statuses' => $mapped,
                'createdAt' => (string)($trackingDetails['createdAt'] ?? ''),
                'updatedAt' => (string)($trackingDetails['updatedAt'] ?? ''),
            ];
        }

        return $out;
    }

    /**
     * Mapuje kody operacji finansowych Allegro (payment-operations) na czytelne etykiety PL.
     * W UI nie pokazujemy surowych kodów typu REFUND/REFUND_CHARGE.
     */
    private function mapPaymentOperationLabel(string $group, string $type): string
    {
        $g = strtoupper(trim($group));
        $t = strtoupper(trim($type));

        $key = ($g !== '' ? $g : '—') . '/' . ($t !== '' ? $t : '—');

        $map = [
            'REFUND/REFUND_CHARGE' => 'Zwrot płatności dla kupującego',
            'REFUND/REFUND_COMMISSION' => 'Zwrot prowizji',
            'PAYOUT/PAYOUT' => 'Wypłata środków',
            'CHARGE/CHARGE' => 'Pobranie opłaty',
        ];

        return $map[$key] ?? 'Inna operacja finansowa';
    }


    private function mapShipmentTrackingStatusLabel(string $code): string
    {
        $c = strtoupper(trim($code));
        if ($c === '') {
            return '—';
        }

        $map = [
            'PENDING' => 'Oczekuje na nadanie',
            'IN_TRANSIT' => 'W drodze',
            'RELEASED_FOR_DELIVERY' => 'W doręczeniu',
            'OUT_FOR_DELIVERY' => 'W doręczeniu',
            'AVAILABLE_FOR_PICKUP' => 'Do odbioru w punkcie',
            'READY_FOR_PICKUP' => 'Do odbioru w punkcie',
            'NOTICE_LEFT' => 'Awizo (pozostawiono powiadomienie)',
            'ISSUE' => 'Problem z przesyłką',
            'DELIVERED' => 'Doręczono / odebrano',
            'RETURNED' => 'Zwrócono do nadawcy',
            'RETURNED_TO_SENDER' => 'Zwrócono do nadawcy',
        ];

        return $map[$c] ?? 'Inny status';
    }

    private function mapShipmentTrackingStatusClass(string $code): string
    {
        $c = strtoupper(trim($code));
        $map = [
            'PENDING' => 'pending',
            'IN_TRANSIT' => 'in-transit',
            'RELEASED_FOR_DELIVERY' => 'out-for-delivery',
            'OUT_FOR_DELIVERY' => 'out-for-delivery',
            'AVAILABLE_FOR_PICKUP' => 'pickup',
            'READY_FOR_PICKUP' => 'pickup',
            'NOTICE_LEFT' => 'notice-left',
            'ISSUE' => 'issue',
            'DELIVERED' => 'delivered',
            'RETURNED' => 'returned',
            'RETURNED_TO_SENDER' => 'returned',
        ];
        return $map[$c] ?? 'unknown';
    }

    /**
     * Uzupełnia rekordy zwrotów klienckich o informację o ZWROCIE PŁATNOŚCI (refund dla kupującego)
     * na podstawie cache "Allegro Finanse" (payment-operations).
     *
     * Źródła danych:
     *  - `{prefix}allegropro_order_payment` (checkout_form_id -> payment_id)
     *  - `{prefix}allegropro_payment_operation` (op_type = REFUND_CHARGE)
     *
     * Jeśli nie ma danych w cache (brak synchronizacji finansów / brak payment_id),
     * ustawiamy stan "na" (brak danych).
     */
    private function attachAllegroRefundInfoToCustomerReturns(int $accountId, array &$customerReturns): void
    {
        if ($accountId <= 0 || empty($customerReturns)) {
            return;
        }

        // 1) checkout_form_id (orderId) -> payment_id
        $checkoutIds = [];
        foreach ($customerReturns as $r) {
            $oid = (string)($r['orderId'] ?? '');
            if ($oid !== '') {
                $checkoutIds[] = $oid;
            }
        }
        $checkoutIds = array_values(array_unique($checkoutIds));

        $paymentMap = $this->loadPaymentIdsByCheckoutFormIds($checkoutIds); // checkout_form_id => payment_id

        // 2) refund summary by payment_id (Allegro payment-operations)
        $paymentIds = [];
        foreach ($paymentMap as $pid) {
            if ($pid !== '') {
                $paymentIds[] = $pid;
            }
        }
        $paymentIds = array_values(array_unique($paymentIds));

        $refundCacheOk = true;

        $refundSummary = $this->loadRefundSummaryByPaymentIds($accountId, $paymentIds, $refundCacheOk); // payment_id => summary

        foreach ($customerReturns as &$r) {
            $oid = (string)($r['orderId'] ?? '');
            $paymentId = (string)($paymentMap[$oid] ?? '');

            $r['payment_id'] = $paymentId;

            // defaults
            $r['pay_refund_state'] = 'na';
            $r['pay_refund_count'] = 0;
            $r['pay_refund_total'] = 0.0;
            $r['pay_refund_total_fmt'] = '';
            $r['pay_refund_currency'] = (string)($r['items_currency'] ?? 'PLN');
            $r['pay_refund_last_at'] = '';

            if ($paymentId === '') {
                continue;
            }

            if (!$refundCacheOk) {
                // Brak cache finansów (tabela nie istnieje / błąd DB / brak synchronizacji)
                // – nie rozstrzygamy czy zwrot był wykonany.
                $r['pay_refund_state'] = 'na';
                continue;
            }

            $sum = $refundSummary[$paymentId] ?? null;
            if (!$sum) {
                // Mamy payment_id, ale brak refundów → "nie" (0.00)
                $r['pay_refund_state'] = 'no';
                $r['pay_refund_total_fmt'] = number_format(0, 2, '.', ' ');
                continue;
            }

            $count = (int)($sum['count'] ?? 0);
            $total = (float)($sum['total'] ?? 0.0);
            $currency = (string)($sum['currency'] ?? ($r['items_currency'] ?? 'PLN'));
            $lastAt = (string)($sum['last_at'] ?? '');

            $r['pay_refund_count'] = $count;
            $r['pay_refund_total'] = $total;
            $r['pay_refund_total_fmt'] = number_format((float)$total, 2, '.', ' ');
            $r['pay_refund_currency'] = $currency;
            $r['pay_refund_last_at'] = $lastAt;

            $r['pay_refund_state'] = ($total > 0.00001) ? 'yes' : 'no';
        }
        unset($r);
    }

    /**
     * Dodaje do szczegółów zwrotu (customerReturnDetails) dane o zwrocie płatności dla kupującego
     * (Allegro Finanse / payment-operations: REFUND_CHARGE).
     */
    private function attachAllegroRefundInfoToCustomerReturnDetails(int $accountId, ?array &$customerReturnDetails): void
    {
        if ($accountId <= 0 || empty($customerReturnDetails)) {
            return;
        }

        $orderId = (string)($customerReturnDetails['orderId'] ?? '');
        if ($orderId === '') {
            return;
        }

        $paymentMap = $this->loadPaymentIdsByCheckoutFormIds([$orderId]);
        $paymentId = (string)($paymentMap[$orderId] ?? '');

        $customerReturnDetails['_payment_id'] = $paymentId;
        $customerReturnDetails['_pay_refund_state'] = 'na';
        $customerReturnDetails['_pay_refund_count'] = 0;
        $customerReturnDetails['_pay_refund_total'] = 0.0;
        $customerReturnDetails['_pay_refund_total_fmt'] = '';
        $customerReturnDetails['_pay_refund_currency'] = 'PLN';
        $customerReturnDetails['_pay_refund_last_at'] = '';
        $customerReturnDetails['_pay_refund_ops'] = [];

        if ($paymentId === '') {
            return;
        }

        $refundCacheOk = true;

        $summary = $this->loadRefundSummaryByPaymentIds($accountId, [$paymentId], $refundCacheOk);
        if (!$refundCacheOk) {
            // Brak cache finansów – nie możemy potwierdzić zwrotu.
            return;
        }

        $sum = $summary[$paymentId] ?? null;

        if ($sum) {
            $count = (int)($sum['count'] ?? 0);
            $total = (float)($sum['total'] ?? 0.0);
            $currency = (string)($sum['currency'] ?? 'PLN');
            $lastAt = (string)($sum['last_at'] ?? '');

            $customerReturnDetails['_pay_refund_count'] = $count;
            $customerReturnDetails['_pay_refund_total'] = $total;
            $customerReturnDetails['_pay_refund_total_fmt'] = number_format((float)$total, 2, '.', ' ');
            $customerReturnDetails['_pay_refund_currency'] = $currency;
            $customerReturnDetails['_pay_refund_last_at'] = $lastAt;
            $customerReturnDetails['_pay_refund_state'] = ($total > 0.00001) ? 'yes' : 'no';

            $customerReturnDetails['_pay_refund_ops'] = $this->loadRefundOperationsByPaymentId($accountId, $paymentId, 50);
        } else {
            // Mamy payment_id, ale brak refundów w cache → "no"
            $customerReturnDetails['_pay_refund_state'] = 'no';
            $customerReturnDetails['_pay_refund_total_fmt'] = number_format(0, 2, '.', ' ');
            $customerReturnDetails['_pay_refund_currency'] = 'PLN';
        }
    }

    /**
     * checkout_form_id -> payment_id
     *
     * @param array<int,string> $checkoutFormIds
     * @return array<string,string>
     */
    private function loadPaymentIdsByCheckoutFormIds(array $checkoutFormIds): array
    {
        $checkoutFormIds = array_values(array_filter(array_unique(array_map('strval', $checkoutFormIds)), static function ($v) {
            return trim((string)$v) !== '';
        }));

        if (empty($checkoutFormIds)) {
            return [];
        }

        $in = [];
        foreach ($checkoutFormIds as $id) {
            $in[] = "'" . pSQL($id) . "'";
        }

        $sql = 'SELECT checkout_form_id, payment_id '
            . 'FROM `' . _DB_PREFIX_ . 'allegropro_order_payment` '
            . 'WHERE checkout_form_id IN (' . implode(',', $in) . ')';

        $rows = Db::getInstance()->executeS($sql);
        if ($rows === false) {
            return [];
        }

        $map = [];
        foreach ($rows as $r) {
            $cf = (string)($r['checkout_form_id'] ?? '');
            $pid = (string)($r['payment_id'] ?? '');
            if ($cf === '') {
                continue;
            }
            // prefer non-empty payment_id
            if ($pid !== '') {
                $map[$cf] = $pid;
            } elseif (!isset($map[$cf])) {
                $map[$cf] = '';
            }
        }

        return $map;
    }

    /**
     * Podsumowanie refundów (zwrotów dla kupującego) po payment_id.
     *
     * @param array<int,string> $paymentIds
     * @return array<string,array{count:int,total:float,currency:string,last_at:string}>
     */
    private function loadRefundSummaryByPaymentIds(int $accountId, array $paymentIds, ?bool &$available = null): array
    {
        $accountId = (int)$accountId;
        $paymentIds = array_values(array_filter(array_unique(array_map('strval', $paymentIds)), static function ($v) {
            return trim((string)$v) !== '';
        }));

        if ($available !== null) {
            $available = true;
        }

        if ($accountId <= 0 || empty($paymentIds)) {
            return [];
        }

        // Czy tabela payment-operations istnieje?
        if (!$this->dbHasTable(_DB_PREFIX_ . 'allegropro_payment_operation')) {
            if ($available !== null) {
                $available = false;
            }
            return [];
        }

        $in = [];
        foreach ($paymentIds as $pid) {
            $in[] = "'" . pSQL($pid) . "'";
        }

        $sql = "SELECT payment_id,
                       COUNT(*) AS cnt,
                       SUM(ABS(amount)) AS total,
                       MIN(currency) AS currency,
                       MAX(occurred_at) AS last_at
                FROM `" . _DB_PREFIX_ . "allegropro_payment_operation`
                WHERE id_allegropro_account=" . (int)$accountId . "
                  AND payment_id IN (" . implode(',', $in) . ")
                  AND op_type='REFUND_CHARGE'
                GROUP BY payment_id";

        $rows = Db::getInstance()->executeS($sql);
        if ($rows === false) {
            if ($available !== null) {
                $available = false;
            }
            return [];
        }

        $out = [];
        foreach ($rows as $r) {
            $pid = (string)($r['payment_id'] ?? '');
            if ($pid === '') {
                continue;
            }
            $out[$pid] = [
                'count' => (int)($r['cnt'] ?? 0),
                'total' => (float)($r['total'] ?? 0.0),
                'currency' => (string)($r['currency'] ?? 'PLN'),
                'last_at' => (string)($r['last_at'] ?? ''),
            ];
        }

        return $out;
    }

    
    private function dbHasTable(string $tableWithPrefix): bool
    {
        $tableWithPrefix = trim($tableWithPrefix);
        if ($tableWithPrefix === '') {
            return false;
        }

        $sql = "SHOW TABLES LIKE '" . pSQL($tableWithPrefix) . "'";
        $rows = Db::getInstance()->executeS($sql);
        return !empty($rows);
    }

/**
     * Lista operacji refundów (REFUND_CHARGE) dla payment_id.
     *
     * @return array<int,array{occurred_at:string,amount:float,amount_fmt:string,currency:string,operation_id:string,group:string,type:string,label:string}>
     */
    private function loadRefundOperationsByPaymentId(int $accountId, string $paymentId, int $limit = 50): array
    {
        $accountId = (int)$accountId;
        $paymentId = trim((string)$paymentId);
        $limit = max(1, (int)$limit);

        if ($accountId <= 0 || $paymentId === '') {
            return [];
        }

        $sql = "SELECT operation_id, occurred_at, op_group, op_type, amount, currency
                FROM `" . _DB_PREFIX_ . "allegropro_payment_operation`
                WHERE id_allegropro_account=" . (int)$accountId . "
                  AND payment_id='" . pSQL($paymentId) . "'
                  AND op_type='REFUND_CHARGE'
                ORDER BY occurred_at DESC
                LIMIT " . (int)$limit;

        $rows = Db::getInstance()->executeS($sql);
        if ($rows === false) {
            return [];
        }

        $out = [];
        foreach ($rows as $r) {
            $amount = (float)($r['amount'] ?? 0.0);
            $group = (string)($r['op_group'] ?? '');
            $type = (string)($r['op_type'] ?? '');

            $out[] = [
                'occurred_at' => (string)($r['occurred_at'] ?? ''),
                'amount' => abs($amount),
                'amount_fmt' => number_format(abs($amount), 2, '.', ' '),
                'currency' => (string)($r['currency'] ?? 'PLN'),
                'operation_id' => (string)($r['operation_id'] ?? ''),
                'group' => $group,
                'type' => $type,
                'label' => $this->mapPaymentOperationLabel($group, $type),
            ];
        }

        return $out;
    }

        private function countReturnedShipments(int $accountId, string $dateFrom, string $dateTo, string $query): int
    {
        $where = $this->buildReturnedShipmentsWhere($accountId, $dateFrom, $dateTo, $query);

        $sql = 'SELECT COUNT(*) '
            . 'FROM `' . _DB_PREFIX_ . 'allegropro_shipment` sh '
            . 'LEFT JOIN `' . _DB_PREFIX_ . 'allegropro_order` o ON o.checkout_form_id = sh.checkout_form_id AND o.id_allegropro_account = sh.id_allegropro_account '
            . 'LEFT JOIN `' . _DB_PREFIX_ . 'allegropro_account` a ON a.id_allegropro_account = sh.id_allegropro_account '
            . 'LEFT JOIN `' . _DB_PREFIX_ . 'allegropro_order_shipping` s ON s.checkout_form_id = sh.checkout_form_id '
            . 'LEFT JOIN `' . _DB_PREFIX_ . 'allegropro_delivery_service` ds ON ds.delivery_method_id = s.method_id AND ds.id_allegropro_account = sh.id_allegropro_account '
            . $where;

        return (int)Db::getInstance()->getValue($sql);
    }

    private function getReturnedShipments(int $accountId, string $dateFrom, string $dateTo, string $query, int $limit, int $offset): array
    {
        $limit = max(1, (int)$limit);
        $offset = max(0, (int)$offset);

        $where = $this->buildReturnedShipmentsWhere($accountId, $dateFrom, $dateTo, $query);

        $sql = 'SELECT '
            . 'sh.id_allegropro_account, a.label AS account_label, a.allegro_login, '
            . 'sh.checkout_form_id, sh.shipment_id, sh.tracking_number, sh.status, sh.status_changed_at, sh.updated_at, '
            . 'o.buyer_login, o.total_amount, o.currency, '
            . 's.method_name AS shipping_method_name, s.method_id AS delivery_method_id, '
            . 'ds.carrier_id '
            . 'FROM `' . _DB_PREFIX_ . 'allegropro_shipment` sh '
            . 'LEFT JOIN `' . _DB_PREFIX_ . 'allegropro_account` a ON a.id_allegropro_account = sh.id_allegropro_account '
            . 'LEFT JOIN `' . _DB_PREFIX_ . 'allegropro_order` o ON o.checkout_form_id = sh.checkout_form_id AND o.id_allegropro_account = sh.id_allegropro_account '
            . 'LEFT JOIN `' . _DB_PREFIX_ . 'allegropro_order_shipping` s ON s.checkout_form_id = sh.checkout_form_id '
            . 'LEFT JOIN `' . _DB_PREFIX_ . 'allegropro_delivery_service` ds ON ds.delivery_method_id = s.method_id AND ds.id_allegropro_account = sh.id_allegropro_account '
            . $where
            . 'ORDER BY IFNULL(sh.status_changed_at, sh.updated_at) DESC '
            . 'LIMIT ' . (int)$limit . ' OFFSET ' . (int)$offset;

        $rows = Db::getInstance()->executeS($sql) ?: [];

        foreach ($rows as &$r) {
            $st = (string)($r['status'] ?? '');
            $r['status_label'] = $this->mapShipmentTrackingStatusLabel($st);
            $r['status_class'] = $this->mapShipmentTrackingStatusClass($st);
            $r['tracking_number'] = (string)($r['tracking_number'] ?? '');
            $r['carrier_id'] = (string)($r['carrier_id'] ?? '');
            $r['status_changed_at'] = (string)($r['status_changed_at'] ?? '');
            if ($r['status_changed_at'] === '' && !empty($r['updated_at'])) {
                $r['status_changed_at'] = (string)$r['updated_at'];
            }
        }
        unset($r);

        return $rows;
    }

    private function buildReturnedShipmentsWhere(int $accountId, string $dateFrom, string $dateTo, string $query): string
    {
        $parts = [];

        $parts[] = 'sh.id_allegropro_account=' . (int)$accountId;
        $parts[] = "sh.status IN ('RETURNED','RETURNED_TO_SENDER')";

        $dateFrom = $this->sanitizeYmd($dateFrom);
        $dateTo = $this->sanitizeYmd($dateTo);

        if ($dateFrom) {
            $parts[] = "IFNULL(sh.status_changed_at, sh.updated_at) >= '" . pSQL($dateFrom . ' 00:00:00') . "'";
        }
        if ($dateTo) {
            $parts[] = "IFNULL(sh.status_changed_at, sh.updated_at) <= '" . pSQL($dateTo . ' 23:59:59') . "'";
        }

        $query = trim($query);
        if ($query !== '') {
            $q = pSQL($query);
            $parts[] = "(sh.checkout_form_id LIKE '%{$q}%' OR sh.shipment_id LIKE '%{$q}%' OR sh.tracking_number LIKE '%{$q}%' OR o.buyer_login LIKE '%{$q}%' OR a.label LIKE '%{$q}%')";
        }

        return ' WHERE ' . implode(' AND ', $parts) . ' ';
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
        if ($s === '') {
            return '';
        }
        // UUID / base64 / identyfikatory Allegro
        if (preg_match('/^[a-zA-Z0-9\-\_\.=]{8,}$/', $s)) {
            return $s;
        }
        return '';
    }

    private function sanitizeEnum(string $s): string
    {
        $s = trim($s);
        if ($s === '') {
            return '';
        }
        // bardzo konserwatywnie: litery/cyfry/_/-
        $s = preg_replace('/[^a-zA-Z0-9\_\-]/', '', $s);
        return (string)$s;
    }

    private function sanitizeLogin(string $s): string
    {
        $s = trim($s);
        if ($s === '') {
            return '';
        }
        $s = preg_replace('/[^a-zA-Z0-9\-\_\.\:]/', '', $s);
        return (string)$s;
    }

    private function sanitizeReferenceNumber(string $s): string
    {
        $s = trim($s);
        if ($s === '') {
            return '';
        }
        // numer zwrotu typu "XGQX/2021" – pozwól na / i -
        $s = preg_replace('/[^a-zA-Z0-9\-\_\.\/]/', '', $s);
        return (string)$s;
    }
}
