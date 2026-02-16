<?php
namespace AllegroPro\Repository;

use Db;
use DbQuery;

/**
 * ShipmentRepository
 *
 * UWAGA (PrestaShop): Db::getValue()/getRow() potrafią dopinać własne LIMIT 1,
 * więc w zapytaniach przekazywanych do tych metod nie używamy ręcznego LIMIT 1.
 */
class ShipmentRepository
{
    private string $table;

    public function __construct()
    {
        $this->table = _DB_PREFIX_ . 'allegropro_shipment';
        // Bezpieczna migracja (jeśli już istnieje - nic nie robi)
        $this->ensureWzaColumns();
        $this->ensureStatusChangedAtColumn();
    }

    /** ---------------------------
     *  Helpers (DB schema)
     *  --------------------------- */

    private function columnExists(string $column): bool
    {
        // information_schema = stabilny SELECT (PS może dopinać LIMIT 1 bez psucia składni)
        $sql = "SELECT COUNT(*)
                FROM information_schema.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE()
                  AND TABLE_NAME = '" . pSQL($this->table) . "'
                  AND COLUMN_NAME = '" . pSQL($column) . "'";
        return (int) Db::getInstance()->getValue($sql) > 0;
    }

    /**
     * Dodaje kolumny potrzebne do Wysyłam z Allegro:
     * - wza_command_id (UUID komendy create-commands)
     * - wza_shipment_uuid (UUID shipmentId do /shipment-management/label)
     */
    public function ensureWzaColumns(): void
    {
        try {
            $hasCommand = $this->columnExists('wza_command_id');
            $hasUuid = $this->columnExists('wza_shipment_uuid');

            if ($hasCommand && $hasUuid) {
                return;
            }

            $alterParts = [];
            if (!$hasCommand) {
                $alterParts[] = "ADD COLUMN `wza_command_id` VARCHAR(64) NULL";
                $alterParts[] = "ADD INDEX `idx_wza_command_id` (`wza_command_id`)";
            }
            if (!$hasUuid) {
                $alterParts[] = "ADD COLUMN `wza_shipment_uuid` VARCHAR(64) NULL";
                $alterParts[] = "ADD INDEX `idx_wza_shipment_uuid` (`wza_shipment_uuid`)";
            }

            if (empty($alterParts)) {
                return;
            }

            $sql = "ALTER TABLE `{$this->table}` " . implode(', ', $alterParts);
            Db::getInstance()->execute($sql);
        } catch (\Exception $e) {
            // Nie wysypuj BO, jeśli np. brak uprawnień ALTER na środowisku.
        }
    }


    /**
     * Dodaje kolumnę status_changed_at (czas ostatniej zmiany statusu),
     * bezpiecznie dla istniejących instalacji.
     */
    public function ensureStatusChangedAtColumn(): void
    {
        try {
            if ($this->columnExists('status_changed_at')) {
                return;
            }

            $sql = "ALTER TABLE `{$this->table}` "
                . "ADD COLUMN `status_changed_at` DATETIME NULL, "
                . "ADD INDEX `idx_status_changed_at` (`status_changed_at`)";

            Db::getInstance()->execute($sql);
        } catch (\Exception $e) {
            // Nie wysypuj BO, jeśli np. brak uprawnień ALTER na środowisku.
        }
    }

    /** ---------------------------
     *  Core CRUD
     *  --------------------------- */

    /**
     * Tworzy nowy wpis lub aktualizuje istniejący.
     *
     * WERSJA: kompatybilna wstecznie
     * - nadal uzupełnia standardowe kolumny (shipment_id, tracking_number, itp.)
     * - jeśli w DB są kolumny wza_* to zapisuje też commandId + shipmentUuid.
     */
    public function upsert(int $accountId, string $checkoutFormId, string $commandId, array $payload): void
    {
        $hasCommand = $this->columnExists('wza_command_id');
        $hasUuid = $this->columnExists('wza_shipment_uuid');

        // Daty (opcjonalne) – żeby nie generować NOTICE dla niezdefiniowanych zmiennych
        $createdAt = '';
        if (!empty($payload['createdAt']) && is_string($payload['createdAt'])) {
            $createdAt = trim((string)$payload['createdAt']);
        } elseif (!empty($payload['created_at']) && is_string($payload['created_at'])) {
            $createdAt = trim((string)$payload['created_at']);
        }
        if ($createdAt === '') {
            $createdAt = date('Y-m-d H:i:s');
        }

        $statusChangedAt = '';
        if (!empty($payload['statusChangedAt']) && is_string($payload['statusChangedAt'])) {
            $statusChangedAt = trim((string)$payload['statusChangedAt']);
        } elseif (!empty($payload['status_changed_at']) && is_string($payload['status_changed_at'])) {
            $statusChangedAt = trim((string)$payload['status_changed_at']);
        } elseif (!empty($payload['updatedAt']) && is_string($payload['updatedAt'])) {
            $statusChangedAt = trim((string)$payload['updatedAt']);
        } elseif (!empty($payload['updated_at']) && is_string($payload['updated_at'])) {
            $statusChangedAt = trim((string)$payload['updated_at']);
        }
        if ($statusChangedAt === '') {
            $statusChangedAt = $createdAt;
        }

        $shipmentUuid = null;
        if (!empty($payload['shipmentId']) && is_string($payload['shipmentId'])) {
            $candidate = trim((string)$payload['shipmentId']);
            if ($candidate !== '' && preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $candidate)) {
                $shipmentUuid = $candidate;
            }
        }

        // zachowanie dotychczasowe: jeśli jest shipmentId -> użyj jako finalId, w przeciwnym razie commandId
        $finalId = $shipmentUuid ?: $commandId;

        // Szukamy po shipment_id (w tej tabeli to główne ID wiersza)
        $sql = 'SELECT id_allegropro_shipment FROM `' . $this->table . '`'
            . ' WHERE id_allegropro_account=' . (int)$accountId
            . " AND shipment_id='" . pSQL($finalId) . "'";

        $existing = (int) Db::getInstance()->getValue($sql);

        $row = [
            'id_allegropro_account' => (int)$accountId,
            'checkout_form_id' => pSQL($checkoutFormId),
            'shipment_id' => pSQL($finalId),
            'status' => isset($payload['status']) ? pSQL((string)$payload['status']) : 'NEW',
            'is_smart' => isset($payload['is_smart']) ? (int)$payload['is_smart'] : 0,
            'carrier_mode' => isset($payload['size_type']) && in_array($payload['size_type'], ['A','B','C'], true) ? 'BOX' : 'COURIER',
            'size_details' => isset($payload['size_type']) ? pSQL((string)$payload['size_type']) : 'CUSTOM',
            'updated_at' => pSQL(date('Y-m-d H:i:s')),
        ];

        if ($this->columnExists('status_changed_at')) {
            $sc = is_string($statusChangedAt) ? trim($statusChangedAt) : '';
            if ($sc === '') {
                $sc = $createdAt ?: date('Y-m-d H:i:s');
            }
            $row['status_changed_at'] = pSQL($sc);
        }

        if ($hasCommand) {
            $row['wza_command_id'] = pSQL($commandId);
        }
        if ($hasUuid && $shipmentUuid) {
            $row['wza_shipment_uuid'] = pSQL($shipmentUuid);
        }

        if ($existing > 0) {
            Db::getInstance()->update('allegropro_shipment', $row, 'id_allegropro_shipment=' . (int)$existing);
            return;
        }

        $row['created_at'] = pSQL(date('Y-m-d H:i:s'));
        $row['tracking_number'] = '';
        $row['label_path'] = null;

        Db::getInstance()->insert('allegropro_shipment', $row);
    }

    /**
     * Pobiera pełną historię przesyłek dla danego zamówienia.
     */
    public function findAllByOrder(string $checkoutFormId): array
    {
        $q = new DbQuery();
        $q->select('*');
        $q->from('allegropro_shipment');
        $q->where("checkout_form_id = '" . pSQL($checkoutFormId) . "'");
        $q->orderBy('created_at DESC');

        $results = Db::getInstance()->executeS($q);
        return $results ?: [];
    }

    /**
     * Pobiera historię przesyłek dla zamówienia i konta.
     */
    public function findAllByOrderForAccount(int $accountId, string $checkoutFormId): array
    {
        $q = new DbQuery();
        $q->select('*');
        $q->from('allegropro_shipment');
        $q->where('id_allegropro_account = ' . (int)$accountId);
        $q->where("checkout_form_id = '" . pSQL($checkoutFormId) . "'");
        $q->orderBy('created_at DESC');

        $results = Db::getInstance()->executeS($q);
        return $results ?: [];
    }

    /**
     * Aktualizuje status konkretnej przesyłki.
     */
    public function updateStatus(string $shipmentId, string $newStatus): void
    {
        Db::getInstance()->update(
            'allegropro_shipment',
            ['status' => pSQL($newStatus), 'updated_at' => date('Y-m-d H:i:s')],
            "shipment_id = '" . pSQL($shipmentId) . "'"
        );
    }

    /**
     * Zwraca unikalne ID przesyłek zapisanych lokalnie dla zamówienia.
     */
    public function getOrderShipmentIds(int $accountId, string $checkoutFormId): array
    {
        $rows = Db::getInstance()->executeS(
            'SELECT shipment_id FROM `' . $this->table . '` '
            . 'WHERE id_allegropro_account=' . (int)$accountId
            . " AND checkout_form_id='" . pSQL($checkoutFormId) . "'"
            . ' AND shipment_id IS NOT NULL AND shipment_id != ""'
        ) ?: [];

        $out = [];
        foreach ($rows as $r) {
            $id = (string)($r['shipment_id'] ?? '');
            if ($id !== '') {
                $out[$id] = true;
            }
        }

        return array_keys($out);
    }

    /**
     * TTL guard - ogranicza zbyt częste synchronizacje.
     */
    public function shouldSyncOrder(int $accountId, string $checkoutFormId, int $ttlSeconds): bool
    {
        $ttlSeconds = max(0, (int)$ttlSeconds);
        if ($ttlSeconds === 0) {
            return true;
        }

        $last = Db::getInstance()->getValue(
            'SELECT MAX(updated_at) FROM `' . $this->table . '` '
            . 'WHERE id_allegropro_account=' . (int)$accountId
            . " AND checkout_form_id='" . pSQL($checkoutFormId) . "'"
        );

        if (!$last) {
            return true;
        }

        $lastTs = strtotime((string)$last);
        if ($lastTs === false) {
            return true;
        }

        return (time() - $lastTs) >= $ttlSeconds;
    }

    /**
     * Upsert danych przesyłki pobranych z Allegro (status/tracking/smart).
     * Nie nadpisuje pól wza_*.
     */
    public function upsertFromAllegro(
        int $accountId,
        string $checkoutFormId,
        string $shipmentId,
        ?string $status,
        ?string $trackingNumber,
        ?int $isSmart,
        ?string $carrierMode,
        ?string $sizeDetails,
        ?string $createdAt = null,
        ?string $statusChangedAt = null
    ): void {
        $shipmentId = trim($shipmentId);
        if ($shipmentId === '') {
            return;
        }

        $q = new DbQuery();
        $q->select('id_allegropro_shipment');
        $q->from('allegropro_shipment');
        $q->where('id_allegropro_account=' . (int)$accountId);
        $q->where("shipment_id='" . pSQL($shipmentId) . "'");
        $existingId = (int) Db::getInstance()->getValue($q);

        $row = [
            'id_allegropro_account' => (int)$accountId,
            'checkout_form_id' => pSQL($checkoutFormId),
            'shipment_id' => pSQL($shipmentId),
            'status' => pSQL((string)($status ?: 'NEW')),
            'tracking_number' => pSQL((string)($trackingNumber ?: '')),
            'is_smart' => $isSmart === null ? 0 : (int)$isSmart,
            'carrier_mode' => pSQL((string)($carrierMode ?: 'COURIER')),
            'size_details' => pSQL((string)($sizeDetails ?: 'CUSTOM')),
            'updated_at' => pSQL(date('Y-m-d H:i:s')),
        ];

        // Utrwalamy czas ostatniej zmiany statusu (jeśli kolumna istnieje – jest migrowana w __construct)
        if ($this->columnExists('status_changed_at')) {
            $sc = is_string($statusChangedAt) ? trim($statusChangedAt) : '';
            if ($sc === '') {
                $sc = is_string($createdAt) ? trim($createdAt) : '';
            }
            if ($sc === '') {
                $sc = date('Y-m-d H:i:s');
            }
            $row['status_changed_at'] = pSQL($sc);
        }

        if ($existingId > 0) {
            Db::getInstance()->update('allegropro_shipment', $row, 'id_allegropro_shipment=' . $existingId);
            return;
        }

        $createdAt = is_string($createdAt) ? trim($createdAt) : '';
        if ($createdAt === '') {
            $createdAt = date('Y-m-d H:i:s');
        }

        $row['created_at'] = pSQL($createdAt);
        $row['label_path'] = null;
        Db::getInstance()->insert('allegropro_shipment', $row);
    }

    /**
     * Usuwa duplikaty przesyłek dla zamówienia.
     */
    public function removeDuplicatesForOrder(int $accountId, string $checkoutFormId): int
    {
        $rows = Db::getInstance()->executeS(
            'SELECT id_allegropro_shipment, shipment_id, tracking_number, updated_at, created_at '
            . 'FROM `' . $this->table . '` '
            . 'WHERE id_allegropro_account=' . (int)$accountId
            . " AND checkout_form_id='" . pSQL($checkoutFormId) . "'"
            . ' ORDER BY id_allegropro_shipment DESC'
        ) ?: [];

        if (count($rows) <= 1) {
            return 0;
        }

        $seenShipment = [];
        $seenTracking = [];
        $toDelete = [];

        // Preferuj lepsze identyfikatory
        usort($rows, function (array $a, array $b): int {
            $score = function (array $row): int {
                $sid = trim((string)($row['shipment_id'] ?? ''));
                if ($sid === '') {
                    return 0;
                }

                $isCommand = (strpos($sid, ':') !== false)
                    || ((bool)preg_match('/^[A-Za-z0-9+\/]+=*$/', $sid) && strlen($sid) >= 16 && (strlen($sid) % 4 === 0));
                if ($isCommand) {
                    return 1;
                }

                $isUuid = (bool)preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i', $sid);
                if ($isUuid) {
                    return 3;
                }

                return 2;
            };

            $sa = $score($a);
            $sb = $score($b);
            if ($sa !== $sb) {
                return $sb <=> $sa;
            }

            $ta = strtotime((string)($a['updated_at'] ?? $a['created_at'] ?? '')) ?: 0;
            $tb = strtotime((string)($b['updated_at'] ?? $b['created_at'] ?? '')) ?: 0;
            if ($ta !== $tb) {
                return $tb <=> $ta;
            }

            return ((int)($b['id_allegropro_shipment'] ?? 0)) <=> ((int)($a['id_allegropro_shipment'] ?? 0));
        });

        foreach ($rows as $row) {
            $id = (int)($row['id_allegropro_shipment'] ?? 0);
            if ($id <= 0) {
                continue;
            }

            $shipmentId = trim((string)($row['shipment_id'] ?? ''));
            $tracking = strtoupper(trim((string)($row['tracking_number'] ?? '')));

            $duplicate = false;
            if ($shipmentId !== '' && isset($seenShipment[$shipmentId])) {
                $duplicate = true;
            }
            if ($tracking !== '' && isset($seenTracking[$tracking])) {
                $duplicate = true;
            }

            if ($duplicate) {
                $toDelete[] = $id;
                continue;
            }

            if ($shipmentId !== '') {
                $seenShipment[$shipmentId] = true;
            }
            if ($tracking !== '') {
                $seenTracking[$tracking] = true;
            }
        }

        if (empty($toDelete)) {
            return 0;
        }

        Db::getInstance()->delete(
            'allegropro_shipment',
            'id_allegropro_shipment IN (' . implode(',', array_map('intval', $toDelete)) . ')'
        );

        return count($toDelete);
    }

    /** ---------------------------
     *  WZA helpers (UUID/command/waybill)
     *  --------------------------- */

    public function getWzaShipmentUuidForOrder(int $accountId, string $checkoutFormId): ?string
    {
        if (!$this->columnExists('wza_shipment_uuid')) {
            return null;
        }

        $q = new DbQuery();
        $q->select('wza_shipment_uuid');
        $q->from('allegropro_shipment');
        $q->where('id_allegropro_account=' . (int)$accountId);
        $q->where("checkout_form_id='" . pSQL($checkoutFormId) . "'");
        $q->where("wza_shipment_uuid IS NOT NULL AND wza_shipment_uuid != ''");
        $q->orderBy('id_allegropro_shipment DESC');

        $val = Db::getInstance()->getValue($q);
        $val = is_string($val) ? trim($val) : '';
        return $val !== '' ? $val : null;
    }

    public function getWzaCommandIdForOrder(int $accountId, string $checkoutFormId): ?string
    {
        if (!$this->columnExists('wza_command_id')) {
            return null;
        }

        $q = new DbQuery();
        $q->select('wza_command_id');
        $q->from('allegropro_shipment');
        $q->where('id_allegropro_account=' . (int)$accountId);
        $q->where("checkout_form_id='" . pSQL($checkoutFormId) . "'");
        $q->where("wza_command_id IS NOT NULL AND wza_command_id != ''");
        $q->orderBy('id_allegropro_shipment DESC');

        $val = Db::getInstance()->getValue($q);
        $val = is_string($val) ? trim($val) : '';
        return $val !== '' ? $val : null;
    }

    /**
     * Uzupełnia tracking_number (waybill) dla konkretnej paczki (shipmentUuid).
     * W pierwszej kolejności po wza_shipment_uuid (jeśli kolumna istnieje), w przeciwnym razie po shipment_id.
     */
    public function updateTrackingForShipmentUuid(int $accountId, string $checkoutFormId, string $shipmentUuid, string $trackingNumber): void
    {
        $trackingNumber = trim($trackingNumber);
        $shipmentUuid = trim($shipmentUuid);
        if ($trackingNumber === '' || $shipmentUuid === '') {
            return;
        }

        $where = 'id_allegropro_account=' . (int)$accountId
            . " AND checkout_form_id='" . pSQL($checkoutFormId) . "'";

        if ($this->columnExists('wza_shipment_uuid')) {
            $where .= " AND (wza_shipment_uuid='" . pSQL($shipmentUuid) . "' OR shipment_id='" . pSQL($shipmentUuid) . "')";
        } else {
            $where .= " AND shipment_id='" . pSQL($shipmentUuid) . "'";
        }

        Db::getInstance()->update(
            'allegropro_shipment',
            [
                'tracking_number' => pSQL($trackingNumber),
                'updated_at' => pSQL(date('Y-m-d H:i:s')),
            ],
            $where
        );
    }

    /**
     * Liczy aktywne przesyłki/paczki dla zamówienia.
     * Używane do blokady tworzenia > limit paczek (delivery.calculatedNumberOfPackages).
     */
    public function countActiveShipmentsForOrder(int $accountId, string $checkoutFormId): int
    {
        $accountId = (int)$accountId;
        $checkoutFormId = trim($checkoutFormId);
        if ($accountId <= 0 || $checkoutFormId === '') {
            return 0;
        }

        $statuses = ["CANCELLED", "CANCELED", "DELETED"];
        $statusSql = "'" . implode("','", array_map('pSQL', $statuses)) . "'";

        $expr = 'shipment_id';
        if ($this->columnExists('wza_shipment_uuid')) {
            $expr = "IF(wza_shipment_uuid IS NOT NULL AND wza_shipment_uuid != '', wza_shipment_uuid, shipment_id)";
        }

        $sql = "SELECT COUNT(DISTINCT {$expr})
                FROM `{$this->table}`
                WHERE id_allegropro_account = {$accountId}
                  AND checkout_form_id = '" . pSQL($checkoutFormId) . "'
                  AND (status IS NULL OR UPPER(status) NOT IN ({$statusSql}))";

        return (int)Db::getInstance()->getValue($sql);
    }

    /**
     * Zwraca wza_shipment_uuid przypięty do konkretnego wiersza (shipment_id) w historii zamówienia.
     */
    public function getWzaShipmentUuidForShipmentRow(int $accountId, string $checkoutFormId, string $shipmentId): ?string
    {
        if (!$this->columnExists('wza_shipment_uuid')) {
            return null;
        }

        $accountId = (int)$accountId;
        $checkoutFormId = trim($checkoutFormId);
        $shipmentId = trim($shipmentId);

        if ($accountId <= 0 || $checkoutFormId === '' || $shipmentId === '') {
            return null;
        }

        $sql = "SELECT wza_shipment_uuid
                FROM `{$this->table}`
                WHERE id_allegropro_account = {$accountId}
                  AND checkout_form_id = '" . pSQL($checkoutFormId) . "'
                  AND shipment_id = '" . pSQL($shipmentId) . "'";
        $val = Db::getInstance()->getValue($sql);
        $val = is_string($val) ? trim($val) : '';
        return $val !== '' ? $val : null;
    }

    /**
     * Uzupełnia wza_command_id / wza_shipment_uuid dla rekordów z danym tracking_number (waybill).
     * To pozwala "przypiąć" UUID do wiersza z order API (base64 shipment_id).
     */
    public function backfillWzaForTrackingNumber(int $accountId, string $checkoutFormId, string $trackingNumber, ?string $wzaCommandId, ?string $wzaShipmentUuid): int
    {
        $accountId = (int)$accountId;
        $checkoutFormId = trim($checkoutFormId);
        $trackingNumber = trim($trackingNumber);
        $wzaCommandId = is_string($wzaCommandId) ? trim($wzaCommandId) : '';
        $wzaShipmentUuid = is_string($wzaShipmentUuid) ? trim($wzaShipmentUuid) : '';

        if ($accountId <= 0 || $checkoutFormId === '' || $trackingNumber === '') {
            return 0;
        }

        if (!$this->columnExists('wza_command_id') && !$this->columnExists('wza_shipment_uuid')) {
            return 0;
        }

        $q = new DbQuery();
        $q->select('id_allegropro_shipment, wza_command_id, wza_shipment_uuid');
        $q->from('allegropro_shipment');
        $q->where('id_allegropro_account=' . $accountId);
        $q->where("checkout_form_id='" . pSQL($checkoutFormId) . "'");
        $q->where("tracking_number='" . pSQL($trackingNumber) . "'");

        $rows = Db::getInstance()->executeS($q) ?: [];
        if (empty($rows)) {
            return 0;
        }

        $updated = 0;
        foreach ($rows as $row) {
            $id = (int)($row['id_allegropro_shipment'] ?? 0);
            if ($id <= 0) {
                continue;
            }

            $upd = [];
            if ($this->columnExists('wza_command_id')) {
                $cur = trim((string)($row['wza_command_id'] ?? ''));
                if ($cur === '' && $wzaCommandId !== '') {
                    $upd['wza_command_id'] = pSQL($wzaCommandId);
                }
            }
            if ($this->columnExists('wza_shipment_uuid')) {
                $cur = trim((string)($row['wza_shipment_uuid'] ?? ''));
                if ($cur === '' && $wzaShipmentUuid !== '') {
                    $upd['wza_shipment_uuid'] = pSQL($wzaShipmentUuid);
                }
            }

            if (!empty($upd)) {
                $upd['updated_at'] = pSQL(date('Y-m-d H:i:s'));
                Db::getInstance()->update('allegropro_shipment', $upd, 'id_allegropro_shipment=' . $id);
                $updated++;
            }
        }

        return $updated;
    }

    /**
     * Próbuje skopiować WZA dane (commandId/UUID) do wierszy historii utworzonych z order API (base64),
     * na podstawie wspólnego tracking_number (waybill).
     */
    public function mergeWzaFieldsForOrder(int $accountId, string $checkoutFormId): int
    {
        $accountId = (int)$accountId;
        $checkoutFormId = trim($checkoutFormId);
        if ($accountId <= 0 || $checkoutFormId === '') {
            return 0;
        }

        if (!$this->columnExists('wza_shipment_uuid') && !$this->columnExists('wza_command_id')) {
            return 0;
        }

        $q = new DbQuery();
        $q->select('id_allegropro_shipment, shipment_id, tracking_number, wza_command_id, wza_shipment_uuid');
        $q->from('allegropro_shipment');
        $q->where('id_allegropro_account=' . $accountId);
        $q->where("checkout_form_id='" . pSQL($checkoutFormId) . "'");
        $q->where("tracking_number IS NOT NULL AND tracking_number != ''");
        $q->orderBy('id_allegropro_shipment DESC');

        $rows = Db::getInstance()->executeS($q) ?: [];
        if (empty($rows)) {
            return 0;
        }

        $updatedTotal = 0;
        foreach ($rows as $row) {
            $tracking = trim((string)($row['tracking_number'] ?? ''));
            if ($tracking === '') {
                continue;
            }

            $cmd = trim((string)($row['wza_command_id'] ?? ''));
            $uuid = trim((string)($row['wza_shipment_uuid'] ?? ''));

            // jeśli brak uuid w kolumnie, ale shipment_id wygląda jak UUID, potraktuj jako uuid WZA
            $sid = trim((string)($row['shipment_id'] ?? ''));
            if ($uuid === '' && preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $sid)) {
                $uuid = $sid;
            }

            if ($cmd === '' && $uuid === '') {
                continue;
            }

            $updatedTotal += $this->backfillWzaForTrackingNumber($accountId, $checkoutFormId, $tracking, $cmd ?: null, $uuid ?: null);
        }

        return $updatedTotal;
    }


    /**
     * Koryguje flagi SMART tak, aby nie oznaczać większej liczby przesyłek niż limit paczek.
     * Zwraca liczbę zaktualizowanych rekordów.
     */
    public function rebalanceSmartFlagsForOrder(int $accountId, string $checkoutFormId, ?int $smartLimit, ?int $orderIsSmart): int
    {
        $accountId = (int)$accountId;
        $checkoutFormId = trim($checkoutFormId);
        if ($accountId <= 0 || $checkoutFormId === '') {
            return 0;
        }

        $smartLimit = $smartLimit === null ? null : max(0, (int)$smartLimit);
        $orderIsSmart = $orderIsSmart === null ? null : (int)$orderIsSmart;

        // Gdy zamówienie nie jest SMART lub nie ma limitu paczek, wszystkie przesyłki powinny mieć 0.
        if ($orderIsSmart !== 1 || $smartLimit === null || $smartLimit <= 0) {
            Db::getInstance()->update(
                'allegropro_shipment',
                [
                    'is_smart' => 0,
                    'updated_at' => pSQL(date('Y-m-d H:i:s')),
                ],
                'id_allegropro_account=' . $accountId
                    . " AND checkout_form_id='" . pSQL($checkoutFormId) . "'"
                    . " AND is_smart != 0"
            );
            return (int)Db::getInstance()->Affected_Rows();
        }

        $statuses = ["CANCELLED", "CANCELED", "DELETED"];
        $statusSql = "'" . implode("','", array_map('pSQL', $statuses)) . "'";

        $rows = Db::getInstance()->executeS(
            'SELECT id_allegropro_shipment, is_smart, created_at, updated_at '
            . 'FROM `' . $this->table . '` '
            . 'WHERE id_allegropro_account=' . $accountId
            . " AND checkout_form_id='" . pSQL($checkoutFormId) . "'"
            . ' AND (status IS NULL OR UPPER(status) NOT IN (' . $statusSql . ')) '
            . 'ORDER BY COALESCE(created_at, updated_at) ASC, id_allegropro_shipment ASC'
        ) ?: [];

        if (empty($rows)) {
            return 0;
        }

        $updated = 0;
        $allowed = $smartLimit;
        foreach ($rows as $idx => $row) {
            $id = (int)($row['id_allegropro_shipment'] ?? 0);
            if ($id <= 0) {
                continue;
            }

            $expected = $idx < $allowed ? 1 : 0;
            $current = (int)($row['is_smart'] ?? 0);
            if ($current === $expected) {
                continue;
            }

            Db::getInstance()->update(
                'allegropro_shipment',
                [
                    'is_smart' => $expected,
                    'updated_at' => pSQL(date('Y-m-d H:i:s')),
                ],
                'id_allegropro_shipment=' . $id
            );
            $updated++;
        }

        return $updated;
    }

}
