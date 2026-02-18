<?php
/**
 * KONTROLER ZAMÓWIEŃ - Wersja PRO (Smart Skip & Incremental Fetch)
 */
use AllegroPro\Repository\OrderRepository;
use AllegroPro\Repository\AccountRepository;
use AllegroPro\Repository\DeliveryServiceRepository;
use AllegroPro\Repository\ShipmentRepository;
use AllegroPro\Service\HttpClient;
use AllegroPro\Service\AllegroApiClient;
use AllegroPro\Service\OrderImporter;
use AllegroPro\Service\OrderFetcher;
use AllegroPro\Service\OrderProcessor;
use AllegroPro\Service\ShipmentManager;
use AllegroPro\Service\LabelConfig;
use AllegroPro\Service\LabelStorage;
use AllegroPro\Service\AllegroCarrierTrackingService;

class AdminAllegroProOrdersController extends ModuleAdminController
{
    private $repo;

    public function __construct()
    {
        $this->bootstrap = true;
        $this->table = 'allegropro_order';
        $this->className = 'Order';
        $this->identifier = 'id_allegropro_order';
        $this->default_order_by = 'updated_at_allegro';
        $this->default_order_way = 'DESC';

        parent::__construct();
        $this->repo = new OrderRepository();

        $this->fields_list = [
            'id_allegropro_order' => ['title' => 'ID', 'width' => 30],
            'checkout_form_id' => ['title' => 'Allegro ID'],
            'total_amount' => ['title' => 'Kwota'],
            'shipping_method_name' => ['title' => 'Metoda Dostawy', 'havingFilter' => true],
        ];
    }

    // --- HELPER DI ---
    private function getServices() {
        $accRepo = new AccountRepository();
        $http = new HttpClient();
        $api = new AllegroApiClient($http, $accRepo);
        $fetcher = new OrderFetcher($api, $this->repo);
        $processor = new OrderProcessor($this->repo);

        return [$accRepo, $fetcher, $processor];
    }

    private function getShipmentManager() {
        $accRepo = new AccountRepository();
        $http = new HttpClient();
        $api = new AllegroApiClient($http, $accRepo);

        return new ShipmentManager(
            $api,
            new LabelConfig(),
            new LabelStorage(),
            new OrderRepository(),
            new DeliveryServiceRepository(),
            new ShipmentRepository()
        );
    }

    private function getAccount($id) {
        return (new AccountRepository())->get((int)$id);
    }

    private function getValidAccountFromRequest(): ?array
    {
        $accId = (int)Tools::getValue('id_allegropro_account');
        if ($accId <= 0) {
            return null;
        }

        $account = $this->getAccount($accId);
        if (!is_array($account)) {
            return null;
        }

        return $account;
    }

    private function getImportLimit(string $scope): int
    {
        $default = ($scope === 'history') ? 100 : 50;
        $limit = (int)Tools::getValue('fetch_limit');

        if ($limit <= 0) {
            return $default;
        }

        if ($limit > 1000) {
            return 1000;
        }

        return $limit;
    }


    private function getCheckoutIdCandidatesForPaymentLookup(string $checkoutId): array
    {
        $checkoutId = trim($checkoutId);
        if ($checkoutId === '') {
            return [];
        }

        $candidates = [$checkoutId];

        if (preg_match('/^\d+\s+([0-9a-f-]{20,})$/i', $checkoutId, $m)) {
            $candidates[] = (string)$m[1];
        }

        return array_values(array_unique(array_filter(array_map('strval', $candidates))));
    }

    // ============================================================
    // AJAX: KONSOLA IMPORTU
    // ============================================================

    // Krok 1: Pobieranie z Allegro (INCREMENTAL FETCH / HISTORY)
    public function displayAjaxImportFetch() {
        list(, $fetcher, ) = $this->getServices();
        $scope = (string)Tools::getValue('scope');

        $account = $this->getValidAccountFromRequest();
        if (!$account) {
            $this->ajaxDie(json_encode(['success' => false, 'message' => 'Nieprawidłowe konto Allegro.']));
        }
        if ((int)$account['active'] !== 1) {
            $this->ajaxDie(json_encode(['success' => false, 'message' => 'Wybrane konto Allegro jest nieaktywne.']));
        }

        $limit = $this->getImportLimit($scope);

        $includeOlderRaw = Tools::getValue('include_older', Tools::getValue('include_backfill', 1));
        $includeOlder = !in_array((string)$includeOlderRaw, ['0', 'false', 'off', 'no'], true);

        try {
            if ($scope === 'history') {
                $dateFrom = (string)Tools::getValue('date_from');
                $dateTo = (string)Tools::getValue('date_to');

                if (!$dateFrom || !$dateTo) {
                    $this->ajaxDie(json_encode(['success' => false, 'message' => 'Dla trybu historii podaj date_from i date_to.']));
                }

                $fromTs = strtotime($dateFrom);
                $toTs = strtotime($dateTo);
                if ($fromTs === false || $toTs === false || $fromTs > $toTs) {
                    $this->ajaxDie(json_encode(['success' => false, 'message' => 'Nieprawidłowy zakres dat historii.']));
                }

                $res = $fetcher->fetchHistory($account, $dateFrom, $dateTo, $limit);
            } else {
                $res = $fetcher->fetchRecent($account, $limit, $includeOlder);
            }

            $this->ajaxDie(json_encode([
                'success' => true,
                'count' => (int)$res['fetched_count'],
                'account_id' => (int)$account['id_allegropro_account'],
                'fetched_ids' => $res['fetched_ids'] ?? [],
                'limit' => $limit,
            ]));
        } catch (\Throwable $e) {
            $this->ajaxDie(json_encode(['success' => false, 'message' => $e->getMessage()]));
        }
    }

    // Krok 2: Pobranie listy ID (SMART SKIP, per konto, spójność batcha)
    public function displayAjaxImportGetPending() {
        $account = $this->getValidAccountFromRequest();
        if (!$account) {
            $this->ajaxDie(json_encode(['success' => false, 'message' => 'Nieprawidłowe konto Allegro.']));
        }

        $onlyFetchedRaw = Tools::getValue('only_fetched', 0);
        $onlyFetched = in_array((string)$onlyFetchedRaw, ['1', 'true', 'on', 'yes'], true);

        $limit = (int)Tools::getValue('limit');
        if ($limit <= 0) {
            $limit = 50;
        }

        $rawFetchedIds = Tools::getValue('fetched_ids');
        $fetchedIds = [];
        if (is_array($rawFetchedIds)) {
            $fetchedIds = $rawFetchedIds;
        } elseif (is_string($rawFetchedIds) && $rawFetchedIds !== '') {
            $decoded = json_decode($rawFetchedIds, true);
            if (is_array($decoded)) {
                $fetchedIds = $decoded;
            }
        }

        if (!empty($fetchedIds)) {
            $ids = $this->repo->filterPendingIdsForAccount((int)$account['id_allegropro_account'], $fetchedIds);
        } elseif ($onlyFetched) {
            // Tryb "tylko nowe": jeżeli nic nie pobrano w fetchu, nie schodzimy do starszych pendingów.
            $ids = [];
        } else {
            $ids = $this->repo->getPendingIdsForAccount((int)$account['id_allegropro_account'], $limit);
        }

        $this->ajaxDie(json_encode(['success' => true, 'ids' => $ids]));
    }


    // Krok R-1: Reasocjacja rekordów ze starych/nieużywanych ID kont Allegro
    public function displayAjaxRefreshReassignLegacyAccountOrders() {
        $onlyLegacyRaw = Tools::getValue('only_legacy_mode', 0);
        $onlyLegacyMode = in_array((string)$onlyLegacyRaw, ['1', 'true', 'on', 'yes'], true);

        $account = null;
        $targetAccountId = 0;
        if (!$onlyLegacyMode) {
            $account = $this->getValidAccountFromRequest();
            if (!$account) {
                $this->ajaxDie(json_encode(['success' => false, 'message' => 'Nieprawidłowe konto Allegro.']));
            }

            $targetAccountId = (int)$account['id_allegropro_account'];
        }

        $accountRepo = new AccountRepository();
        $allAccounts = $accountRepo->all();
        $allActiveAccounts = [];
        foreach ($allAccounts as $acc) {
            if ((int)($acc['active'] ?? 0) !== 1) {
                continue;
            }

            $allActiveAccounts[(int)$acc['id_allegropro_account']] = $acc;
        }

        if (empty($allActiveAccounts)) {
            $this->ajaxDie(json_encode(['success' => false, 'message' => 'Brak aktywnych kont Allegro do reasocjacji.']));
        }

        $accountsToCheck = $allActiveAccounts;
        if (!$onlyLegacyMode) {
            if (!isset($allActiveAccounts[$targetAccountId])) {
                $this->ajaxDie(json_encode(['success' => false, 'message' => 'Wybrane konto Allegro nie jest aktywne.']));
            }

            $accountsToCheck = [$targetAccountId => $allActiveAccounts[$targetAccountId]];
        }

        $allActiveAccountIds = array_values(array_filter(array_map('intval', array_keys($allActiveAccounts)), function ($id) {
            return $id > 0;
        }));
        if (empty($allActiveAccountIds)) {
            $this->ajaxDie(json_encode(['success' => false, 'message' => 'Brak poprawnych ID aktywnych kont Allegro do reasocjacji.']));
        }

        $inActiveIds = implode(',', $allActiveAccountIds);
        $db = Db::getInstance();

        $legacyRowsQuery = new DbQuery();
        $legacyRowsQuery
            ->select('o.checkout_form_id, o.id_allegropro_account')
            ->from('allegropro_order', 'o')
            ->where('o.id_allegropro_account NOT IN (' . $inActiveIds . ')')
            ->orderBy('o.updated_at_allegro DESC')
            ->limit(1000);

        $rows = $db->executeS($legacyRowsQuery) ?: [];

        if (empty($rows)) {
            $this->ajaxDie(json_encode([
                'success' => true,
                'checked' => 0,
                'reassigned_count' => 0,
                'presta_linked_count' => 0,
                'reassigned_ids' => [],
                'presta_linked_ids' => [],
                'message' => 'Brak rekordów z nieużywanych ID kont.',
            ]));
        }

        $http = new HttpClient();
        $api = new AllegroApiClient($http, $accountRepo);

        $checked = 0;
        $reassigned = 0;
        $prestaLinked = 0;
        $unresolved = 0;
        $reassignedIds = [];
        $prestaLinkedIds = [];

        foreach ($rows as $row) {
            $checkoutId = isset($row['checkout_form_id']) ? (string)$row['checkout_form_id'] : '';
            if ($checkoutId === '') {
                continue;
            }

            $checked++;

            $matchedAccountId = 0;
            foreach ($accountsToCheck as $activeAccount) {
                $resp = $api->get($activeAccount, '/order/checkout-forms/' . rawurlencode($checkoutId));
                if (empty($resp['ok'])) {
                    continue;
                }

                $remoteId = isset($resp['json']['id']) ? (string)$resp['json']['id'] : '';
                if ($remoteId !== $checkoutId) {
                    continue;
                }

                $matchedAccountId = (int)$activeAccount['id_allegropro_account'];
                break;
            }

            if ($matchedAccountId <= 0) {
                $unresolved++;
                continue;
            }

            $db->update(
                'allegropro_order',
                ['id_allegropro_account' => $matchedAccountId],
                "checkout_form_id = '" . pSQL($checkoutId) . "'"
            );

            $db->update(
                'allegropro_shipment',
                ['id_allegropro_account' => $matchedAccountId],
                "checkout_form_id = '" . pSQL($checkoutId) . "'"
            );

            $reassigned++;
            $reassignedIds[] = $checkoutId;

            $existingPsRow = $db->getRow(
                'SELECT id_order_prestashop
'
                . 'FROM `' . _DB_PREFIX_ . 'allegropro_order`
'
                . "WHERE checkout_form_id = '" . pSQL($checkoutId) . "'
"
                . 'ORDER BY id_allegropro_order DESC'
            );
            $existingPsId = (int)($existingPsRow['id_order_prestashop'] ?? 0);

            if ($existingPsId > 0) {
                continue;
            }

            $paymentLookupIds = $this->getCheckoutIdCandidatesForPaymentLookup($checkoutId);
            $quotedPaymentLookupIds = [];
            foreach ($paymentLookupIds as $lookupId) {
                $quotedPaymentLookupIds[] = "'" . pSQL($lookupId) . "'";
            }

            $psOrderWhere = '';
            if (!empty($quotedPaymentLookupIds)) {
                $psOrderWhere = 'WHERE op.transaction_id IN (' . implode(',', $quotedPaymentLookupIds) . ')
';
            }

            $psOrderRow = [];
            if ($psOrderWhere !== '') {
                $psOrderRow = $db->getRow(
                    'SELECT o.id_order
'
                    . 'FROM `' . _DB_PREFIX_ . 'orders` o
'
                    . 'INNER JOIN `' . _DB_PREFIX_ . 'order_payment` op ON o.reference = op.order_reference
'
                    . $psOrderWhere
                    . 'ORDER BY o.id_order DESC'
                );
            }
            $psOrderId = (int)($psOrderRow['id_order'] ?? 0);

            if ($psOrderId > 0) {
                $db->update(
                    'allegropro_order',
                    ['id_order_prestashop' => $psOrderId],
                    "checkout_form_id = '" . pSQL($checkoutId) . "'"
                );
                $prestaLinked++;
                $prestaLinkedIds[] = $checkoutId . ' => PS#' . $psOrderId;
            }
        }

        $this->ajaxDie(json_encode([
            'success' => true,
            'checked' => $checked,
            'reassigned_count' => $reassigned,
            'presta_linked_count' => $prestaLinked,
            'unresolved_count' => $unresolved,
            'legacy_mode' => $onlyLegacyMode,
            'reassigned_ids' => $reassignedIds,
            'presta_linked_ids' => $prestaLinkedIds,
            'message' => 'Zakończono reasocjację rekordów legacy.',
        ]));
    }


    // Krok R0: Czyszczenie rekordów osieroconych (id_order_prestashop = 0)
    public function displayAjaxRefreshCleanupOrphans() {
        $account = $this->getValidAccountFromRequest();
        if (!$account) {
            $this->ajaxDie(json_encode(['success' => false, 'message' => 'Nieprawidłowe konto Allegro.']));
        }

        $accountId = (int)$account['id_allegropro_account'];
        $db = Db::getInstance();

        $rows = $db->executeS(
            'SELECT checkout_form_id
'
            . 'FROM `' . _DB_PREFIX_ . 'allegropro_order`
'
            . 'WHERE id_allegropro_account = ' . $accountId . '
'
            . '  AND id_order_prestashop = 0'
        ) ?: [];

        $ids = [];
        foreach ($rows as $r) {
            $id = isset($r['checkout_form_id']) ? (string)$r['checkout_form_id'] : '';
            if ($id !== '') {
                $ids[] = $id;
            }
        }

        $ids = array_values(array_unique($ids));
        if (empty($ids)) {
            $this->ajaxDie(json_encode([
                'success' => true,
                'deleted_count' => 0,
                'ids' => [],
                'message' => 'Brak osieroconych rekordów do usunięcia.',
            ]));
        }

        $quoted = [];
        foreach ($ids as $id) {
            $quoted[] = "'" . pSQL($id) . "'";
        }

        $inList = implode(',', $quoted);

        $db->delete('allegropro_order_item', 'checkout_form_id IN (' . $inList . ')');
        $db->delete('allegropro_order_shipping', 'checkout_form_id IN (' . $inList . ')');
        $db->delete('allegropro_order_payment', 'checkout_form_id IN (' . $inList . ')');
        $db->delete('allegropro_order_invoice', 'checkout_form_id IN (' . $inList . ')');
        $db->delete('allegropro_order_buyer', 'checkout_form_id IN (' . $inList . ')');
        $db->delete(
            'allegropro_order',
            'id_allegropro_account = ' . $accountId . ' AND checkout_form_id IN (' . $inList . ') AND id_order_prestashop = 0'
        );

        $this->ajaxDie(json_encode([
            'success' => true,
            'deleted_count' => count($ids),
            'ids' => $ids,
            'message' => 'Usunięto osierocone rekordy (id_order_prestashop=0).',
        ]));
    }

    // Krok R1: Pobranie partii lokalnie zapisanych zamówień do aktualizacji
    public function displayAjaxRefreshGetBatch() {
        $account = $this->getValidAccountFromRequest();
        if (!$account) {
            $this->ajaxDie(json_encode(['success' => false, 'message' => 'Nieprawidłowe konto Allegro.']));
        }

        $limit = (int)Tools::getValue('limit');
        if ($limit <= 0) {
            $limit = 25;
        }
        if ($limit > 200) {
            $limit = 200;
        }

        $offset = (int)Tools::getValue('offset');
        if ($offset < 0) {
            $offset = 0;
        }

        $accountId = (int)$account['id_allegropro_account'];
        $total = $this->repo->countStoredOrdersForAccount($accountId);
        $ids = $this->repo->getStoredOrderIdsForAccountBatch($accountId, $limit, $offset);
        $nextOffset = $offset + count($ids);

        $this->ajaxDie(json_encode([
            'success' => true,
            'ids' => $ids,
            'total' => $total,
            'offset' => $offset,
            'next_offset' => $nextOffset,
            'has_more' => $nextOffset < $total,
            'limit' => $limit,
            'account_id' => $accountId,
        ]));
    }

    // Krok 3 i 4: Przetwarzanie (Create lub Fix)
    public function displayAjaxImportProcessSingle() {
        $cfId = Tools::getValue('checkout_form_id');
        $step = Tools::getValue('step'); // 'create' lub 'fix'

        if (!$cfId) $this->ajaxDie(json_encode(['success' => false, 'message' => 'No ID']));
        if (!$step) $this->ajaxDie(json_encode(['success' => false, 'message' => 'No Step']));

        list(, , $processor) = $this->getServices();

        try {
            $result = $processor->processSingleOrder($cfId, $step);
            $this->ajaxDie(json_encode($result));
        } catch (\Throwable $e) {
            $this->ajaxDie(json_encode(['success' => false, 'message' => $e->getMessage()]));
        }
    }

    // ============================================================
    // AJAX: WYSYŁKA
    // ============================================================

    public function displayAjaxCreateShipment() {
        $cfId = (string)Tools::getValue('checkout_form_id');
        $sizeCode = (string)Tools::getValue('size_code');
        $weight = (string)Tools::getValue('weight');
        $isSmart = (int)Tools::getValue('is_smart');
        $debug = in_array((string)Tools::getValue('debug', '0'), ['1', 'true', 'on', 'yes'], true);

        if ($cfId === '') {
            $this->ajaxDie(json_encode([
                'success' => false,
                'message' => 'Brak checkout_form_id.',
                'debug_enabled' => $debug,
                'debug_lines' => $debug ? ['[CREATE] brak checkout_form_id'] : [],
            ]));
        }

        $account = $this->getValidAccountFromRequest();
        if (!$account) {
            $this->ajaxDie(json_encode([
                'success' => false,
                'message' => 'Nieprawidłowe konto Allegro.',
                'debug_enabled' => $debug,
                'debug_lines' => $debug ? ['[CREATE] nieprawidłowe konto Allegro'] : [],
            ]));
        }

        $manager = $this->getShipmentManager();
        $res = $manager->createShipment($account, $cfId, [
            'size_code' => $sizeCode,
            'weight' => $weight,
            'smart' => $isSmart,
            'debug' => $debug ? 1 : 0,
        ]);

        if (!empty($res['ok'])) {
            $this->ajaxDie(json_encode([
                'success' => true,
                'message' => (string)($res['message'] ?? 'Przesyłka została utworzona.'),
                'shipment_id' => (string)($res['shipmentId'] ?? ''),
                'debug_enabled' => $debug,
                'debug_lines' => is_array($res['debug_lines'] ?? null) ? array_values($res['debug_lines']) : [],
            ]));
        }

        $this->ajaxDie(json_encode([
            'success' => false,
            'message' => (string)($res['message'] ?? 'Nie udało się utworzyć przesyłki.'),
            'debug_enabled' => $debug,
            'debug_lines' => is_array($res['debug_lines'] ?? null) ? array_values($res['debug_lines']) : [],
        ]));
    }

    public function displayAjaxCancelShipment() {
        $shipmentId = (string)Tools::getValue('shipment_id');
        if ($shipmentId === '') {
            $this->ajaxDie(json_encode(['success' => false, 'message' => 'Brak shipment_id.']));
        }

        $account = $this->getValidAccountFromRequest();
        if (!$account) {
            $this->ajaxDie(json_encode(['success' => false, 'message' => 'Nieprawidłowe konto Allegro.']));
        }

        $manager = $this->getShipmentManager();
        $res = $manager->cancelShipment($account, $shipmentId);
        if ($res['ok']) $this->ajaxDie(json_encode(['success' => true]));
        else $this->ajaxDie(json_encode(['success' => false, 'message' => $res['message']]));
    }

    public function displayAjaxSyncShipments() {
        $cfId = (string)Tools::getValue('checkout_form_id');
        $debug = in_array((string)Tools::getValue('debug', '0'), ['1', 'true', 'on', 'yes'], true);

        if ($cfId === '') {
            $this->ajaxDie(json_encode(['success' => false, 'message' => 'Brak checkout_form_id.']));
        }

        $account = $this->getValidAccountFromRequest();
        if (!$account) {
            $this->ajaxDie(json_encode(['success' => false, 'message' => 'Nieprawidłowe konto Allegro.']));
        }

        $manager = $this->getShipmentManager();
        $res = $manager->syncOrderShipments($account, $cfId, 0, true, $debug);

        if (!empty($res['ok'])) {
            $this->ajaxDie(json_encode([
                'success' => true,
                'synced' => (int)($res['synced'] ?? 0),
                'skipped' => !empty($res['skipped']),
                'debug_enabled' => $debug,
                'debug_lines' => is_array($res['debug_lines'] ?? null) ? array_values($res['debug_lines']) : [],
            ]));
        }

        $this->ajaxDie(json_encode([
            'success' => false,
            'message' => (string)($res['message'] ?? 'Błąd synchronizacji przesyłek.'),
            'debug_enabled' => $debug,
            'debug_lines' => is_array($res['debug_lines'] ?? null) ? array_values($res['debug_lines']) : [],
        ]));
    }

    public function displayAjaxGetLabel() {
        $shipmentId = (string)Tools::getValue('shipment_id');
        $cfId = (string)Tools::getValue('checkout_form_id');

        if ($shipmentId === '' || $cfId === '') {
            $this->ajaxDie(json_encode(['success' => false, 'message' => 'Brak shipment_id lub checkout_form_id.']));
        }

        $account = $this->getValidAccountFromRequest();
        if (!$account) {
            $this->ajaxDie(json_encode(['success' => false, 'message' => 'Nieprawidłowe konto Allegro.']));
        }

        $downloadUrl = $this->context->link->getAdminLink('AdminAllegroProOrders', true, [], [
            'action' => 'download_label',
            'id_allegropro_account' => (int)$account['id_allegropro_account'],
            'checkout_form_id' => $cfId,
            'shipment_id' => $shipmentId,
        ]);

        $this->ajaxDie(json_encode(['success' => true, 'url' => $downloadUrl]));
    }

    public function displayAjaxUpdateAllegroStatus() {
        $cfId = (string)Tools::getValue('checkout_form_id');
        $status = (string)Tools::getValue('new_status');

        if ($cfId === '' || $status === '') {
            $this->ajaxDie(json_encode(['success' => false, 'message' => 'Brak checkout_form_id lub statusu.']));
        }

        $account = $this->getValidAccountFromRequest();
        if (!$account) {
            $this->ajaxDie(json_encode(['success' => false, 'message' => 'Nieprawidłowe konto Allegro.']));
        }

        $http = new HttpClient();
        $api = new AllegroApiClient($http, new AccountRepository());
        $resp = $api->postJson($account, '/order/checkout-forms/' . $cfId . '/fulfillment', ['status' => $status]);
        if ($resp['ok']) $this->ajaxDie(json_encode(['success' => true]));
        else $this->ajaxDie(json_encode(['success' => false, 'message' => 'Błąd API']));
    }


        public function displayAjaxGetTracking()
    {
        header('Content-Type: application/json');

        $accountId = (int)Tools::getValue('id_allegropro_account');
        $carrierId = (string)Tools::getValue('carrier_id'); // opcjonalny (UI może nie wysyłać)
        $waybill = (string)Tools::getValue('tracking_number');

        $carrierId = strtoupper(trim($carrierId));
        $waybill = trim($waybill);

        if ($accountId <= 0 || $waybill === '') {
            $this->ajaxDie(json_encode([
                'success' => false,
                'message' => 'Brak danych: konto/numer przesyłki.',
            ]));
        }

        $accRepo = new AccountRepository();
        $http = new HttpClient();
        $api = new AllegroApiClient($http, $accRepo);

        $account = $accRepo->get($accountId);
        if (!is_array($account) || empty($account['access_token'])) {
            $this->ajaxDie(json_encode(['success' => false, 'message' => 'Brak autoryzacji Allegro dla konta.']));
        }
        if ((int)($account['active'] ?? 0) !== 1) {
            $this->ajaxDie(json_encode(['success' => false, 'message' => 'Konto Allegro jest nieaktywne.']));
        }

        // Fallback carrierIds (jeśli UI nie przekaże carrier_id)
        $deliveryRepo = new \AllegroPro\Repository\DeliveryServiceRepository();
        $fallbackCarrierIds = $deliveryRepo->listCarrierIdsForAccount($accountId);

        if ($carrierId === '' && empty($fallbackCarrierIds)) {
            $this->ajaxDie(json_encode([
                'success' => false,
                'message' => 'Brak carrier_id oraz brak listy przewoźników (delivery-services) do autodetekcji.',
                'number' => $waybill,
                'url' => 'https://allegro.pl/allegrodelivery/sledzenie-paczki?numer=' . rawurlencode($waybill),
            ]));
        }

        $svc = new AllegroCarrierTrackingService($api);
        $res = $svc->fetch($account, $carrierId, $waybill, $fallbackCarrierIds);

        if (empty($res['ok'])) {
            $this->ajaxDie(json_encode([
                'success' => false,
                'message' => (string)($res['message'] ?? 'Nie udało się pobrać trackingu.'),
                'carrier_id' => $carrierId,
                'number' => $waybill,
                'url' => 'https://allegro.pl/allegrodelivery/sledzenie-paczki?numer=' . rawurlencode($waybill),
            ]));
        }

        $statuses = $res['statuses'] ?? [];
        if (!is_array($statuses)) {
            $statuses = [];
        }

        // Sortujemy malejąco po occurredAt, żeby pierwszy event był "aktualny"
        usort($statuses, function ($a, $b) {
            $da = is_array($a) ? (string)($a['occurredAt'] ?? '') : '';
            $db = is_array($b) ? (string)($b['occurredAt'] ?? '') : '';
            return strcmp($db, $da);
        });

        // UI (admin_order_details.tpl) oczekuje: current + events[]
        $events = [];
        foreach ($statuses as $st) {
            if (!is_array($st)) {
                continue;
            }

            $occurredAt = (string)($st['occurredAt'] ?? '');
            $code = (string)($st['code'] ?? '');
            $desc = (string)($st['description'] ?? '');

            $ui = $this->mapTrackingCodeToUi($code);
            if ((($ui['label_pl'] ?? '') === '' || ($ui['label_pl'] ?? '') === $code) && trim($desc) !== '') {
                $ui['label_pl'] = trim($desc);
            }

            $events[] = [
                'status' => $code,
                'occurred_at' => $occurredAt,
                'occurred_at_formatted' => $this->formatTrackingDatePl($occurredAt),
                'severity' => $ui['severity'] ?? 'secondary',
                'short_pl' => $ui['short_pl'] ?? ($code ?: '—'),
                'label_pl' => $ui['label_pl'] ?? ($code ?: '—'),
            ];
        }

        $current = !empty($events) ? $events[0] : null;

        $this->ajaxDie(json_encode([
            'success' => true,
            'carrier_id' => (string)($res['carrierId'] ?? ($carrierId ?: ($fallbackCarrierIds[0] ?? ''))),
            'number' => (string)($res['waybill'] ?? $waybill),
            // format dla UI (modal)
            'current' => $current,
            'events' => $events,
            // surowe dane (debug / kompatybilność)
            'statuses' => $statuses,
            'message' => (string)($res['message'] ?? ''),
            'url' => 'https://allegro.pl/allegrodelivery/sledzenie-paczki?numer=' . rawurlencode($waybill),
        ]));
    }

    private function formatTrackingDatePl(string $iso): string
    {
        $iso = trim($iso);
        if ($iso === '') {
            return '';
        }

        try {
            $dt = new \DateTime($iso);
            return $dt->format('d.m.Y (H:i)');
        } catch (\Exception $e) {
            return $iso;
        }
    }

    /**
     * Mapowanie kodów trackingu (API Allegro) do UI (PL + kolor/severity).
     * Nie jest kompletne dla wszystkich przewoźników, ale obejmuje najczęstsze przypadki.
     */
    private function mapTrackingCodeToUi(string $code): array
    {
        $c = strtoupper(trim($code));

        $map = [
            'DELIVERED' => ['severity' => 'success', 'short_pl' => 'Dostarczona', 'label_pl' => 'Przesyłka dostarczona'],
            'AVAILABLE_FOR_PICKUP' => ['severity' => 'warning', 'short_pl' => 'Do odbioru', 'label_pl' => 'Przesyłka gotowa do odbioru'],
            'READY_FOR_PICKUP' => ['severity' => 'warning', 'short_pl' => 'Do odbioru', 'label_pl' => 'Przesyłka gotowa do odbioru'],
            'OUT_FOR_DELIVERY' => ['severity' => 'info', 'short_pl' => 'W doręczeniu', 'label_pl' => 'Przesyłka w doręczeniu'],
            'RELEASED_FOR_DELIVERY' => ['severity' => 'info', 'short_pl' => 'W doręczeniu', 'label_pl' => 'Przesyłka w doręczeniu'],
            'IN_TRANSIT' => ['severity' => 'info', 'short_pl' => 'W drodze', 'label_pl' => 'Przesyłka w drodze'],
            'SENT' => ['severity' => 'info', 'short_pl' => 'W drodze', 'label_pl' => 'Przesyłka w drodze'],
            'CREATED' => ['severity' => 'secondary', 'short_pl' => 'Utworzona', 'label_pl' => 'Utworzono przesyłkę'],
            'PENDING' => ['severity' => 'secondary', 'short_pl' => 'Oczekuje', 'label_pl' => 'Oczekuje na nadanie'],
            'READY_FOR_PROCESSING' => ['severity' => 'secondary', 'short_pl' => 'Oczekuje', 'label_pl' => 'Oczekuje na nadanie'],
            'CANCELLED' => ['severity' => 'secondary', 'short_pl' => 'Anulowana', 'label_pl' => 'Przesyłka anulowana'],
            'RETURNED' => ['severity' => 'secondary', 'short_pl' => 'Zwrócona', 'label_pl' => 'Przesyłka zwrócona'],
            'RETURNED_TO_SENDER' => ['severity' => 'secondary', 'short_pl' => 'Zwrócona', 'label_pl' => 'Przesyłka zwrócona do nadawcy'],
            'DELIVERY_FAILED' => ['severity' => 'danger', 'short_pl' => 'Problem', 'label_pl' => 'Problem z doręczeniem'],
            'UNDELIVERED' => ['severity' => 'danger', 'short_pl' => 'Problem', 'label_pl' => 'Problem z doręczeniem'],
            'LOST' => ['severity' => 'danger', 'short_pl' => 'Zagubiona', 'label_pl' => 'Przesyłka zagubiona'],
        ];

        if (isset($map[$c])) {
            return $map[$c];
        }

        // Heurystyka dla nieznanych kodów
        if (strpos($c, 'DELIVER') !== false) {
            return ['severity' => 'info', 'short_pl' => 'Doręczenie', 'label_pl' => $code];
        }
        if (strpos($c, 'PICKUP') !== false) {
            return ['severity' => 'warning', 'short_pl' => 'Do odbioru', 'label_pl' => $code];
        }
        if (strpos($c, 'TRANSIT') !== false || strpos($c, 'SENT') !== false) {
            return ['severity' => 'info', 'short_pl' => 'W drodze', 'label_pl' => $code];
        }
        if (strpos($c, 'FAIL') !== false || strpos($c, 'ERROR') !== false || strpos($c, 'UNDEL') !== false || strpos($c, 'LOST') !== false) {
            return ['severity' => 'danger', 'short_pl' => 'Problem', 'label_pl' => $code];
        }

        return ['severity' => 'secondary', 'short_pl' => ($code ?: '—'), 'label_pl' => ($code ?: '—')];
    }


    public function initContent()
    {
        if (Tools::getValue('action') === 'get_order_details') {
            $this->ajaxGetOrderDetails();
            return;
        }

        if (Tools::getValue('action') === 'download_label') {
            $this->downloadLabelFile();
            return;
        }

        parent::initContent();
    }


    private function mapModuleStatusLabel(string $status): string
    {
        $status = strtoupper(trim($status));

        if (in_array($status, ['READY_FOR_PROCESSING', 'BOUGHT'], true)) {
            return 'ALLEGRO PRO - OPŁACONE';
        }

        if ($status === 'FILLED_IN') {
            return 'ALLEGRO PRO - BRAK WPŁATY';
        }

        if ($status === 'CANCELLED') {
            return 'ALLEGRO PRO - ANULOWANE';
        }

        return 'ALLEGRO PRO - PRZETWARZANIE';
    }



    private function getModuleStatusGroups(array $rawStatuses): array
    {
        $groups = [
            'PAID' => [
                'label' => 'ALLEGRO PRO - OPŁACONE',
                'raw' => [],
            ],
            'NO_PAYMENT' => [
                'label' => 'ALLEGRO PRO - BRAK WPŁATY',
                'raw' => [],
            ],
            'PROCESSING' => [
                'label' => 'ALLEGRO PRO - PRZETWARZANIE',
                'raw' => [],
            ],
            'CANCELLED' => [
                'label' => 'ALLEGRO PRO - ANULOWANE',
                'raw' => [],
            ],
        ];

        foreach ($rawStatuses as $raw) {
            $label = $this->mapModuleStatusLabel((string)$raw);
            if ($label === 'ALLEGRO PRO - OPŁACONE') {
                $groups['PAID']['raw'][] = (string)$raw;
            } elseif ($label === 'ALLEGRO PRO - BRAK WPŁATY') {
                $groups['NO_PAYMENT']['raw'][] = (string)$raw;
            } elseif ($label === 'ALLEGRO PRO - ANULOWANE') {
                $groups['CANCELLED']['raw'][] = (string)$raw;
            } else {
                $groups['PROCESSING']['raw'][] = (string)$raw;
            }
        }

        foreach ($groups as $key => $meta) {
            $groups[$key]['raw'] = array_values(array_unique($meta['raw']));
        }

        return $groups;
    }

    private function mapModuleStatusClass(string $label): string
    {
        if ($label === 'ALLEGRO PRO - OPŁACONE') {
            return 'success';
        }

        if ($label === 'ALLEGRO PRO - ANULOWANE') {
            return 'danger';
        }

        if ($label === 'ALLEGRO PRO - BRAK WPŁATY') {
            return 'default';
        }

        return 'warning';
    }

    public function renderList()
    {
        $accounts = (new AccountRepository())->all();

        $perPage = (int)Tools::getValue('per_page', 50);
        $allowedPerPage = [20, 50, 100, 200];
        if (!in_array($perPage, $allowedPerPage, true)) {
            $perPage = 50;
        }

        $page = (int)Tools::getValue('page', 1);
        if ($page < 1) {
            $page = 1;
        }

        $deliveryMethods = Tools::getValue('filter_delivery_methods', []);
        if (!is_array($deliveryMethods)) {
            $deliveryMethods = [$deliveryMethods];
        }
        $deliveryMethods = array_values(array_filter(array_map('trim', array_map('strval', $deliveryMethods))));

        $statusCodes = Tools::getValue('filter_statuses', []);
        if (!is_array($statusCodes)) {
            $statusCodes = [$statusCodes];
        }
        $statusCodes = array_values(array_filter(array_map('trim', array_map('strval', $statusCodes))));

        $statusGroups = $this->getModuleStatusGroups($this->repo->getDistinctStatuses());
        $statuses = [];
        foreach ($statusCodes as $code) {
            if (!isset($statusGroups[$code])) {
                continue;
            }
            foreach ($statusGroups[$code]['raw'] as $rawStatus) {
                $statuses[] = (string)$rawStatus;
            }
        }
        $statuses = array_values(array_unique($statuses));

        $filters = [
            'id_allegropro_account' => (int)Tools::getValue('filter_account'),
            'date_from' => (string)Tools::getValue('filter_date_from'),
            'date_to' => (string)Tools::getValue('filter_date_to'),
            'delivery_methods' => $deliveryMethods,
            'statuses' => $statuses,
            'checkout_form_id' => trim((string)Tools::getValue('filter_checkout_form_id')),
            // Globalne wyszukiwanie po całej bazie danych modułu (nie tylko po rekordach aktualnej strony).
            // Implementacja w OrderRepository::applyFilters() obejmuje też powiązane tabele (buyer/shipping/items/payments/shipments/invoice).
            'global_query' => trim((string)Tools::getValue('filter_global_query')),
        ];

        // Minimalna sanityzacja: jeśli użytkownik wklei same białe znaki, traktuj jako brak filtra.
        if (isset($filters['global_query']) && $filters['global_query'] === '') {
            unset($filters['global_query']);
        }

        if ($filters['id_allegropro_account'] <= 0) {
            $filters['id_allegropro_account'] = 0;
        }

        if ($filters['date_from'] !== '' && strtotime($filters['date_from']) === false) {
            $filters['date_from'] = '';
        }

        if ($filters['date_to'] !== '' && strtotime($filters['date_to']) === false) {
            $filters['date_to'] = '';
        }

        $totalRows = $this->repo->countFiltered($filters);
        $totalPages = max(1, (int)ceil($totalRows / $perPage));
        if ($page > $totalPages) {
            $page = $totalPages;
        }

        $offset = ($page - 1) * $perPage;
        $orders = $this->repo->getPaginatedFiltered($filters, $perPage, $offset);
        foreach ($orders as &$order) {
            $order['module_status_label'] = $this->mapModuleStatusLabel((string)($order['status'] ?? ''));
            $order['module_status_class'] = $this->mapModuleStatusClass((string)$order['module_status_label']);
        }
        unset($order);

        $selectedAccount = (int)Tools::getValue('id_allegropro_account');
        if (!$selectedAccount && !empty($accounts)) {
            $selectedAccount = (int)$accounts[0]['id_allegropro_account'];
        }

        $this->context->smarty->assign([
            'allegropro_orders' => $orders,
            'allegropro_accounts' => $accounts,
            'allegropro_selected_account' => $selectedAccount,
            'allegropro_filters' => $filters,
            'allegropro_pagination' => [
                'page' => $page,
                'per_page' => $perPage,
                'total_rows' => $totalRows,
                'total_pages' => $totalPages,
                'allowed_per_page' => $allowedPerPage,
            ],
            'allegropro_delivery_options' => $this->repo->getDistinctDeliveryMethods(),
            'allegropro_status_options' => $statusGroups,
            'allegropro_selected_status_codes' => $statusCodes,
            'admin_link' => $this->context->link->getAdminLink('AdminAllegroProOrders'),
        ]);

        $this->context->smarty->assign([
            'allegropro_refresh_orders_panel' => $this->context->smarty->fetch(_PS_MODULE_DIR_ . 'allegropro/views/templates/admin/orders_refresh_panel.tpl'),
            'allegropro_refresh_orders_script' => $this->context->smarty->fetch(_PS_MODULE_DIR_ . 'allegropro/views/templates/admin/orders_refresh_script.tpl'),
        ]);

        return $this->context->smarty->fetch(_PS_MODULE_DIR_ . 'allegropro/views/templates/admin/orders.tpl');
    }

    private function downloadLabelFile(): void
    {
        $shipmentId = (string)Tools::getValue('shipment_id');
        $cfId = (string)Tools::getValue('checkout_form_id');
        $debug = in_array((string)Tools::getValue('debug', '0'), ['1', 'true', 'on', 'yes'], true);
        $debugLines = [];

        if ($shipmentId === '' || $cfId === '') {
            if ($debug) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'message' => 'Brak shipment_id lub checkout_form_id.',
                    'debug_lines' => ['[LABEL] brak shipment_id lub checkout_form_id'],
                ]);
                exit;
            }

            header('HTTP/1.1 400 Bad Request');
            echo 'Brak shipment_id lub checkout_form_id.';
            exit;
        }

        $debugLines[] = '[LABEL] start checkout_form_id=' . $cfId . ', shipment_id=' . $shipmentId;

        $account = $this->getValidAccountFromRequest();
        if (!$account) {
            if ($debug) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'message' => 'Nieprawidłowe konto Allegro.',
                    'debug_lines' => array_merge($debugLines, ['[LABEL] nieprawidłowe konto Allegro']),
                ]);
                exit;
            }

            header('HTTP/1.1 403 Forbidden');
            echo 'Nieprawidłowe konto Allegro.';
            exit;
        }

        $debugLines[] = '[LABEL] account_id=' . (int)$account['id_allegropro_account'];

        $exists = (int)Db::getInstance()->getValue(
            'SELECT id_allegropro_shipment FROM `' . _DB_PREFIX_ . 'allegropro_shipment` '
            . 'WHERE id_allegropro_account = ' . (int)$account['id_allegropro_account']
            . " AND checkout_form_id = '" . pSQL($cfId) . "'"
            . " AND shipment_id = '" . pSQL($shipmentId) . "'"
        );

        $debugLines[] = '[LABEL] local shipment row exists=' . ($exists > 0 ? '1' : '0');

        if ($exists <= 0) {
            if ($debug) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'message' => 'Nie znaleziono przesyłki dla wskazanego zamówienia.',
                    'debug_lines' => array_merge($debugLines, ['[LABEL] brak rekordu w tabeli allegropro_shipment']),
                ]);
                exit;
            }

            header('HTTP/1.1 404 Not Found');
            echo 'Nie znaleziono przesyłki dla wskazanego zamówienia.';
            exit;
        }

        $manager = $this->getShipmentManager();
        $res = $manager->downloadLabel($account, $cfId, $shipmentId);

        if (!empty($res['debug_lines']) && is_array($res['debug_lines'])) {
            $debugLines = array_merge($debugLines, array_values($res['debug_lines']));
        }

        if (empty($res['ok']) || empty($res['path']) || !is_file($res['path'])) {
            if ($debug) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'message' => (string)($res['message'] ?? 'Błąd pobierania etykiety.'),
                    'http_code' => (int)($res['http_code'] ?? 0),
                    'debug_lines' => $debugLines,
                ]);
                exit;
            }

            header('HTTP/1.1 502 Bad Gateway');
            echo 'Błąd pobierania etykiety.';
            exit;
        }

        $format = (string)($res['format'] ?? 'PDF');
        $mime = (new LabelStorage())->getMimeType($format);
        $ext = (strtoupper($format) === 'ZPL') ? 'zpl' : 'pdf';
        $fileName = 'allegro_label_' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $cfId) . '_' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $shipmentId) . '.' . $ext;

        $debugLines[] = '[LABEL] success format=' . $format . ', mime=' . $mime . ', file=' . $res['path'];

        if ($debug) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => 'Etykieta pobrana poprawnie (tryb debug - bez streamu pliku).',
                'file_name' => $fileName,
                'file_path' => $res['path'],
                'file_size' => (int)filesize($res['path']),
                'mime' => $mime,
                'debug_lines' => $debugLines,
            ]);
            exit;
        }

        if (ob_get_level()) {
            ob_end_clean();
        }

        header('Content-Type: ' . $mime);
        header('Content-Length: ' . (string)filesize($res['path']));
        header('Content-Disposition: inline; filename="' . $fileName . '"');
        header('X-Content-Type-Options: nosniff');

        readfile($res['path']);
        exit;
    }


    private function ajaxGetOrderDetails()
    {
        $cfId = Tools::getValue('checkout_form_id');
        $items = Db::getInstance()->executeS("SELECT * FROM "._DB_PREFIX_."allegropro_order_item WHERE checkout_form_id = '".pSQL($cfId)."'");
        header('Content-Type: application/json');
        echo json_encode($items);
        exit;
    }
}
