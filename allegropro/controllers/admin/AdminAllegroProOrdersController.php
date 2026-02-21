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

    /**
     * PrestaShop 8 (widok zamówienia Symfony) potrafi wywołać ten kontroler w ścieżce typu
     * /sell/orders/{id}/index.php?... i wtedy standardowa ścieżka AJAX bywa pomijana.
     *
     * Żeby tracking / dokumenty nie kończyły się renderowaniem orders.tpl (HTML zamiast JSON),
     * robimy ręczne, bezpieczne rozpoznanie akcji AJAX.
     */
    public function postProcess()
    {
        $isAjax = (bool)Tools::getValue('ajax');
        $action = (string)Tools::getValue('action');

        if ($isAjax && $action !== '') {
            // 1) Standard: action=getTracking -> displayAjaxGetTracking
            $candidates = [];
            $candidates[] = Tools::ucfirst($action);

            // 2) Kompatybilność: action=update_status -> displayAjaxUpdateStatus
            if (strpos($action, '_') !== false) {
                $parts = array_filter(array_map('trim', explode('_', $action)), static function($v){ return $v !== ''; });
                if (!empty($parts)) {
                    $camel = '';
                    foreach ($parts as $p) {
                        $camel .= Tools::ucfirst($p);
                    }
                    $candidates[] = $camel;
                }
            }

            foreach ($candidates as $uc) {
                $m1 = 'displayAjax' . $uc;
                if (method_exists($this, $m1)) {
                    $this->$m1();
                    return;
                }

                $m2 = 'ajaxProcess' . $uc;
                if (method_exists($this, $m2)) {
                    $this->$m2();
                    return;
                }
            }
        }

        parent::postProcess();
    }

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

    private function sanitizeDimensionInput($value): string
    {
        $raw = trim((string)$value);
        if ($raw === '' || !is_numeric($raw)) {
            return '';
        }

        $asInt = (int)$raw;
        if ($asInt <= 0) {
            return '';
        }

        return (string)$asInt;
    }

    private function normalizeDimensionSource($value): string
    {
        $normalized = strtoupper(trim((string)$value));
        if (!in_array($normalized, ['MANUAL', 'CONFIG'], true)) {
            return 'MANUAL';
        }

        return $normalized;
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
            $resolvedAccountId = 0;

            foreach ($accountsToCheck as $candidateId => $candidateAccount) {
                if (empty($candidateAccount['access_token'])) {
                    continue;
                }

                try {
                    $resp = $api->get($candidateAccount, '/order/checkout-forms/' . rawurlencode($checkoutId));
                } catch (\Throwable $e) {
                    continue;
                }

                if (!empty($resp['ok'])) {
                    $resolvedAccountId = (int)$candidateId;
                    break;
                }
            }

            if ($resolvedAccountId <= 0) {
                $unresolved++;
                continue;
            }

            $updated = $this->repo->reassignCheckoutFormToAccount($checkoutId, (int)$resolvedAccountId);
            if ($updated <= 0) {
                continue;
            }

            $reassigned++;
            $reassignedIds[] = $checkoutId;

            $orderRow = $this->repo->findByCheckoutFormId($checkoutId);
            if (!$orderRow) {
                continue;
            }

            $idOrder = (int)($orderRow['id_order_prestashop'] ?? 0);
            if ($idOrder <= 0) {
                continue;
            }

            $psOrder = new Order($idOrder);
            if (!Validate::isLoadedObject($psOrder)) {
                continue;
            }

            if ((int)$psOrder->id_carrier > 0) {
                $prestaLinked++;
                $prestaLinkedIds[] = $checkoutId;
                continue;
            }

            $carrierId = (int)Configuration::get('ALLEGROPRO_CARRIER_ID');
            if ($carrierId <= 0) {
                continue;
            }

            $carrier = new Carrier($carrierId);
            if (!Validate::isLoadedObject($carrier) || $carrier->deleted) {
                continue;
            }

            $idCarrierShop = (int)$carrier->id;
            if (property_exists($carrier, 'id_reference') && (int)$carrier->id_reference > 0) {
                $carrierShopId = (int)Carrier::getCarrierByReference((int)$carrier->id_reference);
                if ($carrierShopId > 0) {
                    $idCarrierShop = $carrierShopId;
                }
            }

            $psOrder->id_carrier = $idCarrierShop;
            $psOrder->update();

            $prestaLinked++;
            $prestaLinkedIds[] = $checkoutId;
        }

        $messageParts = [];
        $messageParts[] = 'Sprawdzono: ' . $checked;
        $messageParts[] = 'Przypisano: ' . $reassigned;
        $messageParts[] = 'Powiązano z przewoźnikiem Presta: ' . $prestaLinked;
        if ($unresolved > 0) {
            $messageParts[] = 'Nierozpoznane: ' . $unresolved;
        }

        $this->ajaxDie(json_encode([
            'success' => true,
            'checked' => $checked,
            'reassigned_count' => $reassigned,
            'presta_linked_count' => $prestaLinked,
            'unresolved_count' => $unresolved,
            'reassigned_ids' => $reassignedIds,
            'presta_linked_ids' => $prestaLinkedIds,
            'message' => implode('. ', $messageParts) . '.',
        ]));
    }

    // Krok R0: Czyszczenie osieroconych rekordów (id_order_prestashop = 0)
    public function displayAjaxRefreshDeleteOrphans() {
        $account = $this->getValidAccountFromRequest();
        if (!$account) {
            $this->ajaxDie(json_encode(['success' => false, 'message' => 'Nieprawidłowe konto Allegro.']));
        }

        $accId = (int)$account['id_allegropro_account'];
        $ids = $this->repo->listOrphanCheckoutIdsForAccount($accId);

        if (empty($ids)) {
            $this->ajaxDie(json_encode([
                'success' => true,
                'deleted_count' => 0,
                'ids' => [],
                'message' => 'Brak osieroconych rekordów (id_order_prestashop=0).',
            ]));
        }

        $this->repo->deleteOrdersByCheckoutIdsForAccount($accId, $ids);

        $this->ajaxDie(json_encode([
            'success' => true,
            'deleted_count' => count($ids),
            'ids' => $ids,
            'message' => 'Usunięto osierocone rekordy (id_order_prestashop=0).',
        ]));
    }

    /**
     * Backward compatibility: starszy frontend używa akcji refresh_cleanup_orphans.
     */
    public function displayAjaxRefreshCleanupOrphans() {
        $this->displayAjaxRefreshDeleteOrphans();
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
        $cfId = trim((string)Tools::getValue('checkout_form_id'));
        $sizeCode = trim((string)Tools::getValue('size_code'));
        $weight = trim((string)Tools::getValue('weight'));
        $weightSource = strtoupper(trim((string)Tools::getValue('weight_source', 'MANUAL')));
        if (!in_array($weightSource, ['MANUAL', 'CONFIG', 'PRODUCTS'], true)) {
            $weightSource = 'MANUAL';
        }

        $isSmart = (int)Tools::getValue('is_smart');
        $length = $this->sanitizeDimensionInput(Tools::getValue('length'));
        $width = $this->sanitizeDimensionInput(Tools::getValue('width'));
        $height = $this->sanitizeDimensionInput(Tools::getValue('height'));
        $dimensionSource = $this->normalizeDimensionSource(Tools::getValue('dimension_source', 'MANUAL'));
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
            'weight_source' => $weightSource,
            'length' => $length,
            'width' => $width,
            'height' => $height,
            'dimension_source' => $dimensionSource,
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
            'message' => (string)($res['message'] ?? 'Nie udało się zsynchronizować przesyłek.'),
            'debug_enabled' => $debug,
            'debug_lines' => is_array($res['debug_lines'] ?? null) ? array_values($res['debug_lines']) : [],
        ]));
    }

    // ============================================================
    // AJAX: STATUS
    // ============================================================

    public 
function displayAjaxUpdateStatus() {
        $cfId = (string)Tools::getValue('checkout_form_id');
        $newStatus = (string)Tools::getValue('new_status');
        $revision = (string)Tools::getValue('checkout_form_revision');

        if ($cfId === '' || $newStatus === '') {
            $this->ajaxDie(json_encode(['success' => false, 'message' => 'Brak danych.']));
        }

        $account = $this->getValidAccountFromRequest();
        if (!$account) {
            $this->ajaxDie(json_encode(['success' => false, 'message' => 'Nieprawidłowe konto Allegro.']));
        }

        // jeśli nie mamy revision z widoku, dociągnij z API (bezpieczniej przy konflikcie wersji)
        if ($revision === '') {
            try {
                $http = new HttpClient(20, 8);
                $api = new AllegroApiClient($http, new AccountRepository());
                $cf = $api->get($account, '/order/checkout-forms/' . rawurlencode($cfId));
                if ($cf['ok'] && is_array($cf['json']) && isset($cf['json']['revision'])) {
                    $revision = (string)$cf['json']['revision'];
                }
            } catch (Exception $e) {
                // ignoruj – spróbujemy bez revision
            }
        }

        $http = new HttpClient(20, 8);
        $api = new AllegroApiClient($http, new AccountRepository());

        $path = '/order/checkout-forms/' . rawurlencode($cfId) . '/fulfillment';
        if ($revision !== '') {
            $path .= '?checkoutForm.revision=' . rawurlencode($revision);
        }

        $payload = ['status' => $newStatus];
        $res = $api->putJson($account, $path, $payload);

        if (!$res['ok']) {
            $msg = 'Błąd API Allegro (HTTP '.$res['code'].')';
            // Allegro zwraca errors[] z userMessage/message
            if (isset($res['json']['errors'][0]['userMessage']) && $res['json']['errors'][0]['userMessage']) {
                $msg = (string)$res['json']['errors'][0]['userMessage'];
            } elseif (isset($res['json']['errors'][0]['message']) && $res['json']['errors'][0]['message']) {
                $msg = (string)$res['json']['errors'][0]['message'];
            } elseif (is_string($res['raw']) && trim($res['raw']) !== '') {
                $msg = trim((string)$res['raw']);
            }
            $this->ajaxDie(json_encode(['success' => false, 'message' => $msg]));
        }

        // etykieta PL dla UI
        $labels = [
            'NEW' => 'Nowe',
            'PROCESSING' => 'W realizacji',
            'READY_FOR_SHIPMENT' => 'Do wysłania',
            'READY_FOR_PICKUP' => 'Do odbioru',
            'SENT' => 'Wysłane',
            'PICKED_UP' => 'Odebrane',
            'CANCELLED' => 'Anulowane',
            'RETURNED' => 'Zwrócone',
        ];
        $label = $labels[$newStatus] ?? $newStatus;

        $this->ajaxDie(json_encode([
            'success' => true,
            'message' => 'Status w Allegro zaktualizowany.',
            'current_status' => $newStatus,
            'current_status_label' => $label,
        ]));
    }

    // ============================================================
    // AJAX: POBIERANIE ETYKIETY / TRACKING
    // ============================================================

    public function displayAjaxGetLabel() {
        $shipmentId = (string)Tools::getValue('shipment_id');
        $cfId = (string)Tools::getValue('checkout_form_id');

        if ($shipmentId === '' || $cfId === '') {
            $this->ajaxDie(json_encode(['success' => false, 'message' => 'Brak danych.']));
        }

        $account = $this->getValidAccountFromRequest();
        if (!$account) {
            $this->ajaxDie(json_encode(['success' => false, 'message' => 'Nieprawidłowe konto Allegro.']));
        }

        // Link do streamu pliku (downloadLabelFile)
        $url = $this->context->link->getAdminLink('AdminAllegroProOrders')
            . '&ajax=1&action=downloadLabelFile'
            . '&checkout_form_id=' . rawurlencode($cfId)
            . '&shipment_id=' . rawurlencode($shipmentId)
            . '&id_allegropro_account=' . (int)$account['id_allegropro_account'];

        $this->ajaxDie(json_encode([
            'success' => true,
            'url' => $url
        ]));
    }

    public function displayAjaxDownloadLabelFile() {
        $this->downloadLabelFile();
    }

    public function displayAjaxGetOrderDocuments() {
        $cfId = trim((string)Tools::getValue('checkout_form_id'));
        $debug = in_array((string)Tools::getValue('debug', '0'), ['1', 'true', 'on', 'yes'], true);

        if ($cfId === '') {
            $this->ajaxDie(json_encode(['success' => false, 'message' => 'Brak checkout_form_id.']));
        }

        $account = $this->getValidAccountFromRequest();
        if (!$account) {
            $this->ajaxDie(json_encode(['success' => false, 'message' => 'Nieprawidłowe konto Allegro.']));
        }

        $api = new AllegroApiClient(new HttpClient(), new AccountRepository());
        $encodedCfId = rawurlencode($cfId);

        $endpoints = [
            '/order/checkout-forms/' . $encodedCfId . '/invoices',
            '/order/checkout-forms/' . $encodedCfId . '/documents',
        ];

        $debugLines = [];
        $documents = [];

        foreach ($endpoints as $ep) {
            $res = $api->getWithAcceptFallbacks($account, $ep, [], [
                'application/vnd.allegro.public.v1+json',
                'application/json',
            ]);

            $debugLines[] = sprintf('[DOCS] GET %s => HTTP %d ok=%d', $ep, (int)($res['code'] ?? 0), !empty($res['ok']) ? 1 : 0);
            if (!empty($res['raw'])) {
                $preview = preg_replace('/\s+/', ' ', trim((string)$res['raw']));
                if (strlen($preview) > 380) {
                    $preview = substr($preview, 0, 380) . '...';
                }
                $debugLines[] = '[DOCS] preview ' . $ep . '=' . $preview;
            }

            if (empty($res['ok']) || !is_array($res['json'])) {
                continue;
            }

            $parsed = $this->extractOrderDocumentsFromPayload($res['json'], $ep);
            $debugLines[] = '[DOCS] parsed from ' . $ep . ' => ' . count($parsed);

            $invoiceMetaById = [];
            if (strpos($ep, '/invoices') !== false && isset($res['json']['invoices']) && is_array($res['json']['invoices'])) {
                foreach ($res['json']['invoices'] as $invoiceItem) {
                    if (!is_array($invoiceItem)) {
                        continue;
                    }

                    $invoiceId = trim((string)($invoiceItem['id'] ?? ''));
                    if ($invoiceId === '') {
                        continue;
                    }

                    $invoiceMetaById[$invoiceId] = [
                        'invoiceNumber' => trim((string)($invoiceItem['invoiceNumber'] ?? '')),
                        'status' => trim((string)($invoiceItem['status'] ?? ($invoiceItem['file']['securityVerification']['status'] ?? ''))),
                    ];
                }
            }

            if (!empty($parsed)) {
                foreach ($parsed as $row) {
                    $row['endpoint'] = $ep;

                    if (strpos($ep, '/invoices') !== false) {
                        $row['type'] = trim((string)($row['type'] ?? ''));
                        if ($row['type'] === '' || strcasecmp($row['type'], 'DOKUMENT') === 0) {
                            $row['type'] = 'FAKTURA';
                        }

                        $rowId = trim((string)($row['id'] ?? ''));
                        if ($rowId !== '' && isset($invoiceMetaById[$rowId])) {
                            if (trim((string)($row['number'] ?? '')) === '' && $invoiceMetaById[$rowId]['invoiceNumber'] !== '') {
                                $row['number'] = $invoiceMetaById[$rowId]['invoiceNumber'];
                            }
                            if (trim((string)($row['status'] ?? '')) === '' && $invoiceMetaById[$rowId]['status'] !== '') {
                                $row['status'] = $invoiceMetaById[$rowId]['status'];
                            }
                        }
                    }

                    $documents[] = $row;
                }
            }
        }

        $documents = $this->normalizeOrderDocuments($documents, $cfId, (int)$account['id_allegropro_account']);
  if (!empty($documents)) {
            $this->persistOrderDocumentsSnapshot($documents, $cfId, (int)$account['id_allegropro_account']);
        }

        if (empty($documents)) {
            $payload = [
                'success' => false,
                'message' => 'Brak dokumentów sprzedażowych dostępnych w Allegro dla tego zamówienia.',
            ];
            if ($debug) {
                $payload['debug_lines'] = $debugLines;
            }
            $this->ajaxDie(json_encode($payload));
        }

        $payload = [
            'success' => true,
            'message' => 'Pobrano listę dokumentów: ' . count($documents),
            'documents' => $documents,
        ];

        if ($debug) {
            $payload['debug_lines'] = $debugLines;
        }

        $this->ajaxDie(json_encode($payload));
    }

    public function displayAjaxDownloadOrderDocumentFile() {
        $this->downloadOrderDocumentFile();
    }

    public function displayAjaxGetTracking() {
        $trackingNo = trim((string)Tools::getValue('tracking_number'));
        $cfId = trim((string)Tools::getValue('checkout_form_id'));

        if ($trackingNo === '') {
            $this->ajaxDie(json_encode([
                'success' => false,
                'message' => 'Brak numeru nadania (tracking_number).'
            ]));
        }

        $account = $this->getValidAccountFromRequest();
        if (!$account) {
            $this->ajaxDie(json_encode([
                'success' => false,
                'message' => 'Nieprawidłowe konto Allegro.'
            ]));
        }

        $accountId = (int)$account['id_allegropro_account'];
        $carrierId = '';

        // 1) Spróbuj z ostatniej przesyłki danego zamówienia i numeru
        if ($cfId !== '') {
            try {
                $carrierId = (new ShipmentRepository())->findCarrierIdForTracking($accountId, $cfId, $trackingNo);
            } catch (\Throwable $e) {
                $carrierId = '';
            }
        }

        // 2) Fallback po prefiksie numeru
        if ($carrierId === '') {
            if (preg_match('/^\d{24}$/', $trackingNo)) $carrierId = 'INPOST';
            elseif (preg_match('/^\d{14}$/', $trackingNo)) $carrierId = 'DPD';
            elseif (preg_match('/^[A-Z]{2}\d{9}PL$/i', $trackingNo)) $carrierId = 'POCZTA';
            elseif (preg_match('/^1Z[0-9A-Z]{16}$/i', $trackingNo)) $carrierId = 'UPS';
            elseif (preg_match('/^\d{10,20}$/', $trackingNo)) $carrierId = 'DHL';
        }

        // 3) Fallback po nazwie metody dostawy zamówienia
        if ($carrierId === '' && $cfId !== '') {
            $q = new DbQuery();
            $q->select('method_name');
            $q->from('allegropro_order_shipping');
            $q->where("checkout_form_id = '".pSQL($cfId)."'");
            $ship = Db::getInstance()->getRow($q);

            $mn = strtolower((string)($ship['method_name'] ?? ''));
            if (strpos($mn, 'inpost') !== false) $carrierId = 'INPOST';
            elseif (strpos($mn, 'dpd') !== false) $carrierId = 'DPD';
            elseif (strpos($mn, 'dhl') !== false) $carrierId = 'DHL';
            elseif (strpos($mn, 'orlen') !== false) $carrierId = 'ORLEN';
            elseif (strpos($mn, 'gls') !== false) $carrierId = 'GLS';
            elseif (strpos($mn, 'ups') !== false) $carrierId = 'UPS';
            elseif (strpos($mn, 'poczta') !== false) $carrierId = 'POCZTA';
            elseif (strpos($mn, 'one') !== false) $carrierId = 'ALLEGRO';
        }

        if ($carrierId === '') {
            $this->ajaxDie(json_encode([
                'success' => false,
                'message' => 'Nie udało się ustalić przewoźnika dla numeru: ' . $trackingNo
            ]));
        }

        try {
            $api = new AllegroApiClient(new HttpClient(), new AccountRepository());
            $svc = new AllegroCarrierTrackingService($api);

            // Spróbuj też ALLEGRO jako fallback (czasem tracking jest pod innym carrierId)
            $res = $svc->fetch($account, $carrierId, $trackingNo, ['ALLEGRO']);

            if (empty($res['ok'])) {
                $this->ajaxDie(json_encode([
                    'success' => false,
                    'message' => (string)($res['message'] ?? 'Nie udało się pobrać trackingu.'),
                    'carrier_id' => $carrierId,
                ], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE));
            }

            $statuses = $res['statuses'] ?? [];
            if (!is_array($statuses)) {
                $statuses = [];
            }

            // Sortuj od najnowszych
            usort($statuses, function ($a, $b) {
                $ta = is_array($a) ? (string)($a['occurredAt'] ?? '') : '';
                $tb = is_array($b) ? (string)($b['occurredAt'] ?? '') : '';
                return strcmp($tb, $ta);
            });

            $tz = Configuration::get('PS_TIMEZONE');
            if (!is_string($tz) || $tz === '') {
                $tz = 'Europe/Warsaw';
            }
            $fmtDate = static function (string $iso) use ($tz): string {
                if ($iso === '') {
                    return '';
                }
                try {
                    $dt = new DateTime($iso);
                    $dt->setTimezone(new DateTimeZone($tz));
                    return $dt->format('d.m.Y H:i');
                } catch (Exception $e) {
                    return $iso;
                }
            };

            $map = static function (string $code, string $desc): array {
                $c = strtoupper(trim($code));
                $d = trim($desc);
                $label = $d !== '' ? $d : ($c !== '' ? $c : '—');

                $severity = 'secondary';
                if (strpos($c, 'DELIVER') !== false || stripos($label, 'dostarcz') !== false) {
                    $severity = 'success';
                } elseif (preg_match('/FAILED|ERROR|EXCEPTION|LOST|DAMAGED|RETURN/', $c)) {
                    $severity = 'danger';
                } elseif (preg_match('/OUT_FOR_DELIVERY|READY|PICKUP|COLLECT|AWAIT|WAIT/', $c)) {
                    $severity = 'warning';
                } elseif ($c !== '') {
                    $severity = 'info';
                }

                // krótka etykieta do badge
                $short = $label;
                if (function_exists('mb_strlen') && mb_strlen($short) > 24) {
                    $short = mb_substr($short, 0, 23) . '…';
                } elseif (strlen($short) > 24) {
                    $short = substr($short, 0, 23) . '…';
                }

                return [$label, $short, $severity];
            };

            $events = [];
            foreach ($statuses as $st) {
                if (!is_array($st)) {
                    continue;
                }
                $occurredAt = (string)($st['occurredAt'] ?? '');
                $code = (string)($st['code'] ?? '');
                $desc = (string)($st['description'] ?? '');
                [$labelPl, $shortPl, $severity] = $map($code, $desc);

                $events[] = [
                    'occurred_at' => $occurredAt,
                    'occurred_at_formatted' => $fmtDate($occurredAt),
                    'status' => $code,
                    'label_pl' => $labelPl,
                    'short_pl' => $shortPl,
                    'severity' => $severity,
                ];
            }

            $current = null;
            if (!empty($events)) {
                $current = $events[0];
            } else {
                $msg = (string)($res['message'] ?? 'Brak szczegółowej historii trackingu.');
                $current = [
                    'occurred_at' => '',
                    'occurred_at_formatted' => '',
                    'status' => '',
                    'label_pl' => $msg,
                    'short_pl' => 'Brak danych',
                    'severity' => 'secondary',
                ];
            }

            $this->ajaxDie(json_encode([
                'success' => true,
                'carrier_id' => (string)($res['carrierId'] ?? $carrierId),
                'number' => $trackingNo,
                'url' => 'https://allegro.pl/allegrodelivery/sledzenie-paczki?numer=' . rawurlencode($trackingNo),
                'current' => $current,
                'events' => $events,
            ], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE));
        } catch (\Throwable $e) {
            $this->ajaxDie(json_encode([
                'success' => false,
                'message' => 'Błąd trackingu: ' . $e->getMessage(),
                'carrier_id' => $carrierId
            ]));
        }
    }
    // ============================================================
    // RENDER GŁÓWNEGO WIDOKU
    // ============================================================

    

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


    /**
     * Count problematic Allegro order_ids discovered during settlements enrichment.
     * Used only for the badge in AdminAllegroProOrders tabs.
     */
    private function getIssuesBadgeCount(int $accountId = 0): int
    {
        $db = Db::getInstance();
        $where = "order_error_code IS NOT NULL AND order_id IS NOT NULL AND order_id <> ''";
        if ($accountId > 0) {
            $where .= ' AND id_allegropro_account=' . (int)$accountId;
        }
        $sql = 'SELECT COUNT(DISTINCT order_id) FROM ' . _DB_PREFIX_ . 'allegropro_billing_entry WHERE ' . $where;
        return (int)$db->getValue($sql);
    }

    private function extractOrderDocumentsFromPayload(array $json, string $sourceEndpoint = ''): array
    {
        $containers = [];

        if (isset($json['invoices']) && is_array($json['invoices'])) {
            $containers[] = $json['invoices'];
        }
        if (isset($json['documents']) && is_array($json['documents'])) {
            $containers[] = $json['documents'];
        }
        if (isset($json['salesDocuments']) && is_array($json['salesDocuments'])) {
            $containers[] = $json['salesDocuments'];
        }
        if (isset($json['items']) && is_array($json['items'])) {
            $containers[] = $json['items'];
        }

        if (empty($containers) && array_keys($json) === range(0, count($json) - 1)) {
            $containers[] = $json;
        }

        $isInvoicesEndpoint = (strpos($sourceEndpoint, '/invoices') !== false);
        $out = [];
        foreach ($containers as $list) {
            foreach ((array)$list as $item) {
                if (!is_array($item)) {
                    continue;
                }

                $id = (string)($item['id'] ?? $item['documentId'] ?? $item['uuid'] ?? '');

                $isInvoiceLike = $isInvoicesEndpoint
                    || isset($item['invoiceNumber'])
                    || isset($item['invoiceType'])
                    || isset($item['file']['securityVerification']);

                $type = (string)($item['type'] ?? $item['kind'] ?? $item['documentType'] ?? ($isInvoiceLike ? 'FAKTURA' : 'DOKUMENT'));
                $number = (string)($item['invoiceNumber'] ?? $item['number'] ?? $item['name'] ?? $item['documentNumber'] ?? '');
                $status = (string)($item['status'] ?? ($item['file']['securityVerification']['status'] ?? ''));
                $issuedAt = (string)($item['issuedAt'] ?? $item['createdAt'] ?? $item['created_at'] ?? '');
                $downloadUrl = (string)($item['downloadUrl'] ?? $item['url'] ?? (($item['file']['url'] ?? '')));

                $out[] = [
                    'id' => $id,
                    'type' => $type,
                    'number' => $number,
                    'status' => $status,
                    'issued_at' => $issuedAt,
                    'direct_url' => $downloadUrl,
                ];
            }
        }

        // Dodatkowo: endpoint /invoices może zwracać "links" (linki do dokumentów rozliczeniowych dodanych przez sprzedawcę).
        // Jeżeli są dostępne, traktujemy je jako dokumenty do pobrania (otwarcie URL).
        if (isset($json['links']) && is_array($json['links'])) {
            foreach ($json['links'] as $link) {
                if (!is_array($link)) {
                    continue;
                }

                $url = (string)($link['url'] ?? ($link['href'] ?? ''));
                $url = trim($url);
                if ($url === '') {
                    continue;
                }

                $linkId = (string)($link['id'] ?? '');
                if (trim($linkId) === '') {
                    $linkId = md5($url);
                }

                $out[] = [
                    'id' => $linkId,
                    'type' => (string)($link['type'] ?? ($link['documentType'] ?? 'LINK')),
                    'number' => (string)($link['name'] ?? ($link['title'] ?? ($link['label'] ?? ''))),
                    'status' => (string)($link['status'] ?? ''),
                    'issued_at' => (string)($link['createdAt'] ?? ($link['created_at'] ?? ($link['issuedAt'] ?? ''))),
                    'direct_url' => $url,
                ];
            }
        }

        return $out;
    }

    private function normalizeOrderDocuments(array $docs, string $checkoutFormId, int $accountId): array
    {
        $out = [];
        $seen = [];

        foreach ($docs as $idx => $doc) {
            $id = trim((string)($doc['id'] ?? ''));
            $type = trim((string)($doc['type'] ?? 'Dokument'));
            $number = trim((string)($doc['number'] ?? ''));
            $status = trim((string)($doc['status'] ?? ''));
            $issuedAt = trim((string)($doc['issued_at'] ?? ''));
            $directUrl = trim((string)($doc['direct_url'] ?? ''));
            $sourceEndpoint = trim((string)($doc['source_endpoint'] ?? $doc['endpoint'] ?? ''));

            $key = strtolower(($id !== '' ? $id : ('row_' . $idx)) . '|' . $type . '|' . $number . '|' . $issuedAt);
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;

            // Allegro API często nie udostępnia linku do pobrania PDF (brak direct_url / links),
            // mimo że dokument jest widoczny w panelu Allegro. Wtedy nie pokazujemy akcji "Pobierz" w UI.
            $canDownload = ($directUrl !== '');

            $downloadUrl = $this->context->link->getAdminLink('AdminAllegroProOrders')
                . '&ajax=1&action=downloadOrderDocumentFile'
                . '&checkout_form_id=' . rawurlencode($checkoutFormId)
                . '&id_allegropro_account=' . (int)$accountId
                . '&document_id=' . rawurlencode($id)
                . '&document_type=' . rawurlencode($type)
                . '&document_number=' . rawurlencode($number)
                . '&direct_url=' . rawurlencode($directUrl);

            $out[] = [
                'id' => $id,
                'type' => $type,
                'number' => $number,
                'status' => $status,
                'issued_at' => $issuedAt,
                'direct_url' => $directUrl,
                'source_endpoint' => $sourceEndpoint,
                'download_url' => $downloadUrl,
                'can_download' => $canDownload,
            ];
        }

        return array_values($out);
    }

    private function ensureOrderDocumentsTableExists(): void
    {
        $sql = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'allegropro_order_document` ('
            . '`id_allegropro_order_document` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,'
            . '`id_allegropro_account` INT UNSIGNED NOT NULL,'
            . '`checkout_form_id` VARCHAR(64) NOT NULL,'
            . '`doc_key` CHAR(32) NOT NULL,'
            . '`document_id` VARCHAR(128) NULL,'
            . '`document_type` VARCHAR(128) NULL,'
            . '`document_number` VARCHAR(255) NULL,'
            . '`document_status` VARCHAR(64) NULL,'
            . '`issued_at` VARCHAR(64) NULL,'
            . '`direct_url` TEXT NULL,'
            . '`source_endpoint` VARCHAR(255) NULL,'
            . '`updated_at` DATETIME NOT NULL,'
            . 'PRIMARY KEY (`id_allegropro_order_document`),'
            . 'UNIQUE KEY `uniq_doc` (`id_allegropro_account`,`checkout_form_id`,`doc_key`),'
            . 'KEY `idx_cf` (`checkout_form_id`),'
            . 'KEY `idx_account_cf` (`id_allegropro_account`,`checkout_form_id`)'
            . ') ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8mb4';

        Db::getInstance()->execute($sql);
    }

    private function persistOrderDocumentsSnapshot(array $documents, string $checkoutFormId, int $accountId): void
    {
        if ($checkoutFormId === '' || $accountId <= 0 || empty($documents)) {
            return;
        }

        $this->ensureOrderDocumentsTableExists();

        $db = Db::getInstance();
        $now = date('Y-m-d H:i:s');

        foreach ($documents as $idx => $doc) {
            $id = trim((string)($doc['id'] ?? ''));
            $type = trim((string)($doc['type'] ?? ''));
            $number = trim((string)($doc['number'] ?? ''));
            $status = trim((string)($doc['status'] ?? ''));
            $issuedAt = trim((string)($doc['issued_at'] ?? ''));
            $directUrl = trim((string)($doc['direct_url'] ?? ''));
            $sourceEndpoint = trim((string)($doc['source_endpoint'] ?? ''));

            $docKeyBase = $id !== ''
                ? $id
                : ('idx:' . $idx . '|type:' . $type . '|number:' . $number . '|issued:' . $issuedAt);
            $docKey = md5($docKeyBase);

            $sql = 'INSERT INTO `' . _DB_PREFIX_ . 'allegropro_order_document` '
                . '(`id_allegropro_account`,`checkout_form_id`,`doc_key`,`document_id`,`document_type`,`document_number`,`document_status`,`issued_at`,`direct_url`,`source_endpoint`,`updated_at`) VALUES ('
                . (int)$accountId . ','
                . "'" . pSQL($checkoutFormId) . "',"
                . "'" . pSQL($docKey) . "',"
                . ($id !== '' ? "'" . pSQL($id) . "'" : 'NULL') . ','
                . ($type !== '' ? "'" . pSQL($type) . "'" : 'NULL') . ','
                . ($number !== '' ? "'" . pSQL($number) . "'" : 'NULL') . ','
                . ($status !== '' ? "'" . pSQL($status) . "'" : 'NULL') . ','
                . ($issuedAt !== '' ? "'" . pSQL($issuedAt) . "'" : 'NULL') . ','
                . ($directUrl !== '' ? "'" . pSQL($directUrl, true) . "'" : 'NULL') . ','
                . ($sourceEndpoint !== '' ? "'" . pSQL($sourceEndpoint) . "'" : 'NULL') . ','
                . "'" . pSQL($now) . "'"
                . ') ON DUPLICATE KEY UPDATE '
                . '`document_id`=VALUES(`document_id`),'
                . '`document_type`=VALUES(`document_type`),'
                . '`document_number`=VALUES(`document_number`),'
                . '`document_status`=VALUES(`document_status`),'
                . '`issued_at`=VALUES(`issued_at`),'
                . '`direct_url`=VALUES(`direct_url`),'
                . '`source_endpoint`=VALUES(`source_endpoint`),'
                . '`updated_at`=VALUES(`updated_at`)';

            $db->execute($sql);
        }
    }

    private function downloadOrderDocumentFile(): void
    {
        $cfId = trim((string)Tools::getValue('checkout_form_id'));
        $docId = trim((string)Tools::getValue('document_id'));
        $docType = trim((string)Tools::getValue('document_type', 'dokument'));
        $docNumber = trim((string)Tools::getValue('document_number', ''));
        $directUrl = trim((string)Tools::getValue('direct_url'));
        $debug = in_array((string)Tools::getValue('debug', '0'), ['1', 'true', 'on', 'yes'], true);

        if ($cfId === '') {
            header('HTTP/1.1 400 Bad Request');
            echo 'Brak checkout_form_id.';
            exit;
        }

        $account = $this->getValidAccountFromRequest();
        if (!$account) {
            header('HTTP/1.1 403 Forbidden');
            echo 'Nieprawidłowe konto Allegro.';
            exit;
        }

        $api = new AllegroApiClient(new HttpClient(), new AccountRepository());
        $debugLines = [];
        $attempts = [];
        $binary = '';
        $ok = false;
        $httpCode = 0;

        $shortRaw = static function (string $raw): string {
            $raw = trim($raw);
            if ($raw === '') {
                return '';
            }
            $raw = preg_replace('/\s+/', ' ', $raw);
            if (strlen($raw) > 450) {
                $raw = substr($raw, 0, 450) . '...';
            }
            return $raw;
        };

        $extractApiError = static function (string $raw): array {
            $parsed = json_decode($raw, true);
            if (!is_array($parsed) || empty($parsed['errors']) || !is_array($parsed['errors'])) {
                return [];
            }

            $first = $parsed['errors'][0] ?? null;
            if (!is_array($first)) {
                return [];
            }

            return [
                'code' => (string)($first['code'] ?? ''),
                'message' => (string)($first['message'] ?? ''),
                'userMessage' => (string)($first['userMessage'] ?? ''),
            ];
        };

        $extractUrlFromPayload = static function (?array $json): string {
            if (!is_array($json)) {
                return '';
            }

            $candidates = [
                $json['downloadUrl'] ?? null,
                $json['url'] ?? null,
                $json['file']['url'] ?? null,
                $json['invoiceFile']['url'] ?? null,
                $json['documentFile']['url'] ?? null,
                $json['links']['download'] ?? null,
                $json['links']['self'] ?? null,
            ];

            foreach ($candidates as $candidate) {
                if (is_string($candidate) && preg_match('#^https?://#i', $candidate)) {
                    return trim($candidate);
                }
            }

            $lists = [];
            foreach (['invoices', 'documents', 'salesDocuments', 'items'] as $k) {
                if (isset($json[$k]) && is_array($json[$k])) {
                    $lists[] = $json[$k];
                }
            }

            foreach ($lists as $list) {
                foreach ((array)$list as $item) {
                    if (!is_array($item)) {
                        continue;
                    }
                    foreach (['downloadUrl', 'url'] as $k) {
                        if (isset($item[$k]) && is_string($item[$k]) && preg_match('#^https?://#i', $item[$k])) {
                            return trim((string)$item[$k]);
                        }
                    }
                    foreach (['file', 'invoiceFile', 'documentFile'] as $fk) {
                        if (isset($item[$fk]['url']) && is_string($item[$fk]['url']) && preg_match('#^https?://#i', $item[$fk]['url'])) {
                            return trim((string)$item[$fk]['url']);
                        }
                    }
                }
            }

            return '';
        };

        $extractSellerFromCheckout = static function (?array $json): array {
            if (!is_array($json)) {
                return ['id' => '', 'login' => '', 'candidates' => []];
            }

            $candidates = [];
            $isSellerPath = static function (string $path): bool {
                if ($path === '') {
                    return false;
                }

                // Tylko ścieżki jednoznacznie związane ze sprzedawcą/wystawcą.
                if (preg_match('/(^|\.|\[)(seller|issuer|owner|merchant|invoiceIssuer)(\.|\]|$)/i', $path) !== 1) {
                    return false;
                }

                // Odrzuć sekcje kupującego, nawet jeśli zawierają podobne pola.
                if (preg_match('/(^|\.|\[)(buyer|billingAddress|delivery|recipient)(\.|\]|$)/i', $path) === 1) {
                    return false;
                }

                return true;
            };

            $pushCandidate = static function (array &$candidates, $id, $login, string $path): void {
                $id = is_scalar($id) ? trim((string)$id) : '';
                $login = is_scalar($login) ? trim((string)$login) : '';
                if ($id === '' && $login === '') {
                    return;
                }

                $key = strtolower($id . '|' . $login . '|' . $path);
                foreach ($candidates as $c) {
                    if (strtolower(($c['id'] ?? '') . '|' . ($c['login'] ?? '') . '|' . ($c['path'] ?? '')) === $key) {
                        return;
                    }
                }

                $candidates[] = ['id' => $id, 'login' => $login, 'path' => $path];
            };

            $walk = static function ($node, string $path, callable $walk, callable $pushCandidate, callable $isSellerPath): void {
                if (!is_array($node)) {
                    return;
                }

                if ($isSellerPath($path)) {
                    $pushCandidate($GLOBALS['__ap_seller_candidates'], $node['id'] ?? ($node['userId'] ?? null), $node['login'] ?? null, $path);
                }

                foreach ($node as $k => $v) {
                    $nextPath = $path === '' ? (string)$k : ($path . '.' . (string)$k);
                    if (is_array($v)) {
                        $walk($v, $nextPath, $walk, $pushCandidate, $isSellerPath);
                    }
                }
            };

            $GLOBALS['__ap_seller_candidates'] = [];
            $walk($json, '', $walk, $pushCandidate, $isSellerPath);
            $candidates = $GLOBALS['__ap_seller_candidates'];
            unset($GLOBALS['__ap_seller_candidates']);

            $sellerId = '';
            $sellerLogin = '';
            foreach ($candidates as $c) {
                if ($sellerId === '' && !empty($c['id'])) {
                    $sellerId = (string)$c['id'];
                }
                if ($sellerLogin === '' && !empty($c['login'])) {
                    $sellerLogin = (string)$c['login'];
                }
                if ($sellerId !== '' && $sellerLogin !== '') {
                    break;
                }
            }

            return ['id' => $sellerId, 'login' => $sellerLogin, 'candidates' => $candidates];
        };


        $extractBuyerFromCheckout = static function (?array $payload): array {
            if (!is_array($payload)) {
                return ['id' => '', 'login' => ''];
            }

            $buyer = isset($payload['buyer']) && is_array($payload['buyer']) ? $payload['buyer'] : [];

            return [
                'id' => trim((string)($buyer['id'] ?? '')),
                'login' => trim((string)($buyer['login'] ?? '')),
            ];
        };

        $recordAttempt = static function (array &$attempts, string $type, string $target, int $code, bool $okResp, int $bytes, string $errorCode = '', string $errorUserMessage = ''): void {
            $attempts[] = [
                'type' => $type,
                'target' => $target,
                'http_code' => $code,
                'ok' => $okResp,
                'bytes' => $bytes,
                'error_code' => $errorCode,
                'error_user_message' => $errorUserMessage,
            ];
        };

        $debugLines[] = '[DOC-DL] start checkout_form_id=' . $cfId . ', document_id=' . ($docId !== '' ? $docId : '-')
            . ', document_type=' . ($docType !== '' ? $docType : '-') . ', document_number=' . ($docNumber !== '' ? $docNumber : '-')
            . ', account_id=' . (int)$account['id_allegropro_account'] . ', sandbox=' . (int)($account['sandbox'] ?? 0);

        // 1) direct_url z listy dokumentów
        if ($directUrl !== '' && preg_match('#^https?://#i', $directUrl)) {
            $resp = $api->fetchPublicUrl($directUrl, ['Accept' => 'application/pdf,application/json,*/*']);
            $body = (string)($resp['body'] ?? '');
            $httpCode = (int)($resp['code'] ?? 0);
            $apiErr = $extractApiError($body);
            $recordAttempt($attempts, 'direct_url', $directUrl, $httpCode, !empty($resp['ok']), strlen($body), (string)($apiErr['code'] ?? ''), (string)($apiErr['userMessage'] ?? ''));

            $debugLines[] = sprintf('[DOC-DL] direct url GET %s => HTTP %d ok=%d bytes=%d', $directUrl, $httpCode, !empty($resp['ok']) ? 1 : 0, strlen($body));

            if ((string)($resp['error'] ?? '') !== '') {
                $debugLines[] = '[DOC-DL] direct url curl_error=' . (string)$resp['error'];
            }
            if (!empty($apiErr)) {
                $debugLines[] = '[DOC-DL] direct url api_error=' . ($apiErr['code'] ?? '-') . ' userMessage=' . ($apiErr['userMessage'] ?? '-');
            }

            if (!empty($resp['ok']) && $body !== '') {
                $ok = true;
                $binary = $body;
                $debugLines[] = '[DOC-DL] direct url accepted as binary payload.';
            } elseif ($body !== '') {
                $debugLines[] = '[DOC-DL] direct url body-preview=' . $shortRaw($body);
            }
        } elseif ($directUrl !== '') {
            $debugLines[] = '[DOC-DL] direct url skipped (invalid URL): ' . $directUrl;
        } else {
            $debugLines[] = '[DOC-DL] direct url not available in payload.';
        }

        $invoiceLookup = [
            'found_in_invoices_list' => false,
            'invoice_id' => '',
            'invoice_number' => '',
            'file_name' => '',
        ];

        // 2) jeśli brak direct_url, spróbuj znaleźć URL pobierania przez endpointy JSON
        if (!$ok && $docId !== '') {
            $encodedCfId = rawurlencode($cfId);
            $encodedDocId = rawurlencode($docId);

            $jsonEndpoints = [
                '/order/checkout-forms/' . $encodedCfId . '/invoices/' . $encodedDocId,
                '/order/checkout-forms/' . $encodedCfId . '/documents/' . $encodedDocId,
                '/order/checkout-forms/' . $encodedCfId . '/invoices',
                '/order/checkout-forms/' . $encodedCfId . '/documents',
            ];

            foreach ($jsonEndpoints as $ep) {
                $res = $api->getWithAcceptFallbacks($account, $ep, [], [
                    'application/vnd.allegro.public.v1+json',
                    'application/json',
                ]);

                $httpCode = (int)($res['code'] ?? 0);
                $rawBody = (string)($res['raw'] ?? '');
                $apiErr = $extractApiError($rawBody);
                $recordAttempt($attempts, 'json_lookup', $ep, $httpCode, !empty($res['ok']), strlen($rawBody), (string)($apiErr['code'] ?? ''), (string)($apiErr['userMessage'] ?? ''));

                $debugLines[] = sprintf('[DOC-DL] URL lookup %s => HTTP %d ok=%d', $ep, $httpCode, !empty($res['ok']) ? 1 : 0);

                if (!empty($res['raw'])) {
                    $debugLines[] = '[DOC-DL] URL lookup response-preview=' . $shortRaw((string)$res['raw']);
                }
                if (!empty($apiErr)) {
                    $debugLines[] = '[DOC-DL] URL lookup api_error=' . ($apiErr['code'] ?? '-') . ' userMessage=' . ($apiErr['userMessage'] ?? '-');
                }

                if ($ep === ('/order/checkout-forms/' . $encodedCfId . '/invoices') && is_array($res['json']) && isset($res['json']['invoices']) && is_array($res['json']['invoices'])) {
                    foreach ($res['json']['invoices'] as $invoiceItem) {
                        if (!is_array($invoiceItem)) {
                            continue;
                        }

                        $invoiceId = trim((string)($invoiceItem['id'] ?? ''));
                        $invoiceNumber = trim((string)($invoiceItem['invoiceNumber'] ?? ''));
                        $isMatch = false;

                        if ($docId !== '' && $invoiceId !== '' && strcasecmp($invoiceId, $docId) === 0) {
                            $isMatch = true;
                        }
                        if (!$isMatch && $docNumber !== '' && $invoiceNumber !== '' && strcasecmp($invoiceNumber, $docNumber) === 0) {
                            $isMatch = true;
                        }

                        if (!$isMatch) {
                            continue;
                        }

                        $invoiceLookup['found_in_invoices_list'] = true;
                        $invoiceLookup['invoice_id'] = $invoiceId;
                        $invoiceLookup['invoice_number'] = $invoiceNumber;
                        $invoiceLookup['file_name'] = trim((string)($invoiceItem['file']['name'] ?? ''));
                        $debugLines[] = '[DOC-DL] invoice list matched requested document: id=' . ($invoiceId !== '' ? $invoiceId : '-') . ', invoiceNumber=' . ($invoiceNumber !== '' ? $invoiceNumber : '-') . ', fileName=' . ($invoiceLookup['file_name'] !== '' ? $invoiceLookup['file_name'] : '-');
                        break;
                    }
                }

                $resolved = $extractUrlFromPayload($res['json'] ?? null);
                if ($resolved === '') {
                    continue;
                }

                $debugLines[] = '[DOC-DL] resolved direct_url from lookup=' . $resolved;
                $resp = $api->fetchPublicUrl($resolved, ['Accept' => 'application/pdf,application/json,*/*']);
                $body = (string)($resp['body'] ?? '');
                $resolvedCode = (int)($resp['code'] ?? 0);
                $resolvedErr = $extractApiError($body);
                $recordAttempt($attempts, 'resolved_url', $resolved, $resolvedCode, !empty($resp['ok']), strlen($body), (string)($resolvedErr['code'] ?? ''), (string)($resolvedErr['userMessage'] ?? ''));

                $debugLines[] = sprintf('[DOC-DL] resolved url GET => HTTP %d ok=%d bytes=%d', $resolvedCode, !empty($resp['ok']) ? 1 : 0, strlen($body));

                if (!empty($resolvedErr)) {
                    $debugLines[] = '[DOC-DL] resolved url api_error=' . ($resolvedErr['code'] ?? '-') . ' userMessage=' . ($resolvedErr['userMessage'] ?? '-');
                }

                if (!empty($resp['ok']) && $body !== '') {
                    $ok = true;
                    $binary = $body;
                    $httpCode = $resolvedCode;
                    $directUrl = $resolved;
                    break;
                }

                if ($body !== '') {
                    $debugLines[] = '[DOC-DL] resolved url body-preview=' . $shortRaw($body);
                }
            }
        }

        // 3) bezpośrednie endpointy /file
        if (!$ok && $docId !== '') {
            $encodedCfId = rawurlencode($cfId);
            $encodedDocId = rawurlencode($docId);
            $candidates = [
                '/order/checkout-forms/' . $encodedCfId . '/invoices/' . $encodedDocId . '/file',
                '/order/checkout-forms/' . $encodedCfId . '/documents/' . $encodedDocId . '/file',
            ];
            $acceptCandidates = ['application/pdf', 'application/octet-stream', 'application/json', '*/*'];

            foreach ($candidates as $ep) {
                foreach ($acceptCandidates as $accept) {
                    $resp = $api->get($account, $ep, [], $accept);
                    $raw = (string)($resp['raw'] ?? '');
                    $httpCode = (int)($resp['code'] ?? 0);
                    $apiErr = $extractApiError($raw);
                    $recordAttempt($attempts, 'file_endpoint', $ep . ' [Accept: ' . $accept . ']', $httpCode, !empty($resp['ok']), strlen($raw), (string)($apiErr['code'] ?? ''), (string)($apiErr['userMessage'] ?? ''));

                    $debugLines[] = sprintf('[DOC-DL] GET %s (Accept: %s) => HTTP %d ok=%d bytes=%d', $ep, $accept, $httpCode, !empty($resp['ok']) ? 1 : 0, strlen($raw));

                    if (!empty($apiErr)) {
                        $debugLines[] = '[DOC-DL] endpoint api_error=' . ($apiErr['code'] ?? '-') . ' userMessage=' . ($apiErr['userMessage'] ?? '-');
                    }

                    if (!empty($resp['ok']) && $raw !== '') {
                        $binary = $raw;
                        $ok = true;
                        $debugLines[] = '[DOC-DL] endpoint accepted as binary payload.';
                        break 2;
                    }

                    if ($raw !== '') {
                        $debugLines[] = '[DOC-DL] response-preview=' . $shortRaw($raw);
                    }

                    if ($httpCode === 401 || $httpCode === 403 || $httpCode === 404) {
                        if ($httpCode === 403) {
                            $debugLines[] = '[DOC-DL] hint: HTTP 403 AccessDenied - token nie ma dostępu do pliku dokumentu (zweryfikuj owner zasobu i uprawnienia po stronie Allegro).';
                        }
                        break;
                    }
                }
            }
        } elseif (!$ok) {
            $debugLines[] = '[DOC-DL] skipping endpoint calls - missing document_id.';
        }

        if (!$ok) {
            $diagnosis = [];
            $allErrorCodes = [];
            foreach ($attempts as $a) {
                $code = (string)($a['error_code'] ?? '');
                if ($code !== '') {
                    $allErrorCodes[$code] = true;
                }
            }

            $context = [
                'account' => [
                    'id_allegropro_account' => (int)$account['id_allegropro_account'],
                    'sandbox' => (int)($account['sandbox'] ?? 0),
                    'login' => (string)($account['login'] ?? ''),
                ],
                'me' => ['id' => '', 'login' => ''],
                'checkout_seller' => ['id' => '', 'login' => '', 'candidates' => []],
                'checkout_buyer' => ['id' => '', 'login' => ''],
            ];
            $meResp = $api->getWithAcceptFallbacks($account, '/me', [], ['application/vnd.allegro.public.v1+json', 'application/json']);
            $meCode = (int)($meResp['code'] ?? 0);
            $meRaw = (string)($meResp['raw'] ?? '');
            $meErr = $extractApiError($meRaw);
            $recordAttempt($attempts, 'context_lookup', '/me', $meCode, !empty($meResp['ok']), strlen($meRaw), (string)($meErr['code'] ?? ''), (string)($meErr['userMessage'] ?? ''));
            if (!empty($meRaw)) {
                $debugLines[] = '[DOC-DL] context /me preview=' . $shortRaw($meRaw);
            }
            if (is_array($meResp['json'])) {
                $context['me']['id'] = trim((string)($meResp['json']['id'] ?? ''));
                $context['me']['login'] = trim((string)($meResp['json']['login'] ?? ''));
            }

            $cfResp = $api->getWithAcceptFallbacks($account, '/order/checkout-forms/' . rawurlencode($cfId), [], ['application/vnd.allegro.public.v1+json', 'application/json']);
            $cfCode = (int)($cfResp['code'] ?? 0);
            $cfRaw = (string)($cfResp['raw'] ?? '');
            $cfErr = $extractApiError($cfRaw);
            $recordAttempt($attempts, 'context_lookup', '/order/checkout-forms/' . rawurlencode($cfId), $cfCode, !empty($cfResp['ok']), strlen($cfRaw), (string)($cfErr['code'] ?? ''), (string)($cfErr['userMessage'] ?? ''));
            if (!empty($cfRaw)) {
                $debugLines[] = '[DOC-DL] context checkout-form preview=' . $shortRaw($cfRaw);
            }
            if (is_array($cfResp['json'])) {
                $context['checkout_seller'] = $extractSellerFromCheckout($cfResp['json']);
                $context['checkout_buyer'] = $extractBuyerFromCheckout($cfResp['json']);
            }

            $sellerUnknown = ($context['checkout_seller']['id'] === '' && $context['checkout_seller']['login'] === '');

            if (isset($allErrorCodes['AccessDenied'])) {
                $diagnosis[] = 'Allegro zwraca AccessDenied: token nie ma prawa pobrać tego pliku.';
                if ($sellerUnknown && !empty($invoiceLookup['found_in_invoices_list'])) {
                    $diagnosis[] = 'Dokument jest widoczny na liście invoices, ale /file zwraca AccessDenied i checkout-form nie ujawnia seller. To częsty przypadek ograniczenia dostępu do zasobu po stronie Allegro (niekoniecznie błąd mapowania konta).';
                } else {
                    $diagnosis[] = 'Sprawdź czy id_allegropro_account odpowiada kontu, które wystawiło fakturę.';
                }
            }
            if (isset($allErrorCodes['NotFoundException'])) {
                $diagnosis[] = 'Allegro zwraca NotFoundException dla części endpointów: nie wszystkie ścieżki są dostępne dla tego typu dokumentu/konta.';
            }
            if ($directUrl === '') {
                $diagnosis[] = 'Brak direct_url w odpowiedzi listy dokumentów/invoices. API nie podało bezpośredniego linku do pliku.';
            }
            if ($context['me']['login'] !== '' && $context['checkout_seller']['login'] !== '' && $context['me']['login'] !== $context['checkout_seller']['login']) {
                $diagnosis[] = 'Niezgodność kont: token należy do loginu ' . $context['me']['login'] . ', a checkout-form wskazuje sprzedawcę ' . $context['checkout_seller']['login'] . '.';
            }

            if ($sellerUnknown) {
                $diagnosis[] = 'Nie udało się odczytać sprzedawcy z checkout-form (seller unknown). Sprawdź checkout_seller.candidates i surowy preview checkout-form.';
                if ($context['checkout_buyer']['id'] !== '' || $context['checkout_buyer']['login'] !== '') {
                    $diagnosis[] = 'Checkout-form ujawnia dane buyer (kupującego), ale nie ujawnia danych seller. To normalne dla części zamówień i nie oznacza automatycznie błędu mapowania.';
                }
            }

            if (empty($diagnosis)) {
                $diagnosis[] = 'Brak jednoznacznej przyczyny – sprawdź listę attempts/debug_lines.';
            }

            $nextSteps = [];
            if ($sellerUnknown) {
                $nextSteps[] = 'Checkout-form nie zwraca seller, więc nie porównasz loginów 1:1. Zweryfikuj właściciela faktury po numerze dokumentu bezpośrednio w panelu Allegro.';
                if (!empty($invoiceLookup['found_in_invoices_list'])) {
                    $nextSteps[] = 'Ponieważ invoice jest widoczny na /invoices, a /file zwraca AccessDenied, potraktuj to jako twardy dowód do zgłoszenia w Allegro Support (problem dostępu do pliku zasobu).';
                }
            } else {
                $nextSteps[] = 'Sprawdź w sekcji account/me/checkout_seller czy to dokładnie to samo konto sprzedawcy.';
                $nextSteps[] = 'Jeśli me.login różni się od checkout_seller.login, przepnij zamówienie na właściwe id_allegropro_account.';
            }
            if (empty($invoiceLookup['found_in_invoices_list'])) {
                $nextSteps[] = 'Jeśli jest AccessDenied mimo zgodnego konta, skontaktuj się z Allegro Support i przekaż attempts + checkout_form_id + document_id.';
            }
            $nextSteps[] = 'Jeśli pojawi się direct_url, przetestuj ręcznie pobranie przez przeglądarkę (powinno zwrócić PDF).';

            if (!empty($invoiceLookup['found_in_invoices_list'])) {
                $nextSteps[] = 'Do zgłoszenia do Allegro Support przekaż: attempts + checkout_form_id + document_id + invoice_id + invoice_number + odpowiedź AccessDenied z /file.';
            }

            if ($debug) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'message' => 'Nie udało się pobrać dokumentu z Allegro.',
                    'http_code' => $httpCode,
                    'document' => [
                        'checkout_form_id' => $cfId,
                        'document_id' => $docId,
                        'document_type' => $docType,
                        'document_number' => $docNumber,
                        'direct_url' => $directUrl,
                    ],
                    'account' => $context['account'],
                    'me' => $context['me'],
                    'checkout_seller' => $context['checkout_seller'],
                    'checkout_buyer' => $context['checkout_buyer'],
                    'invoice_lookup' => $invoiceLookup,
                    'diagnosis' => $diagnosis,
                    'next_steps' => $nextSteps,
                    'attempts' => $attempts,
                    'debug_lines' => $debugLines,
                ]);
                exit;
            }

            header('HTTP/1.1 502 Bad Gateway');
            echo 'Nie udało się pobrać dokumentu z Allegro.';
            exit;
        }

        $safeCf = preg_replace('/[^a-zA-Z0-9_-]/', '_', $cfId);
        $safeId = preg_replace('/[^a-zA-Z0-9_-]/', '_', $docId !== '' ? $docId : 'doc');
        $safeType = preg_replace('/[^a-zA-Z0-9_-]/', '_', $docType !== '' ? $docType : 'dokument');
        $fileName = 'allegro_document_' . $safeCf . '_' . $safeType . '_' . $safeId . '.pdf';

        if ($debug) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => 'Dokument pobrany (tryb debug).',
                'file_name' => $fileName,
                'size' => strlen($binary),
                'http_code' => $httpCode,
                'document' => [
                    'checkout_form_id' => $cfId,
                    'document_id' => $docId,
                    'document_type' => $docType,
                    'document_number' => $docNumber,
                    'direct_url' => $directUrl,
                ],
                'account' => [
                    'id_allegropro_account' => (int)$account['id_allegropro_account'],
                    'sandbox' => (int)($account['sandbox'] ?? 0),
                    'login' => (string)($account['login'] ?? ''),
                ],
                'attempts' => $attempts,
                'debug_lines' => $debugLines,
            ]);
            exit;
        }

        if (ob_get_level()) {
            ob_end_clean();
        }

        header('Content-Type: application/pdf');
        header('Content-Length: ' . (string)strlen($binary));
        header('Content-Disposition: attachment; filename="' . $fileName . '"');
        header('X-Content-Type-Options: nosniff');
        echo $binary;
        exit;
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
