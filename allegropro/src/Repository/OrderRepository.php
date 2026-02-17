<?php
namespace AllegroPro\Repository;

use Db;
use DbQuery;

class OrderRepository
{
    /**
     * Pobiera datę ostatniej aktualizacji zamówienia w bazie (globalnie).
     */
    public function getLastFetchedDate()
    {
        $count = (int)Db::getInstance()->getValue('SELECT COUNT(*) FROM `' . _DB_PREFIX_ . 'allegropro_order`');
        if ($count === 0) {
            return null;
        }

        $sql = 'SELECT `updated_at_allegro`
                FROM `' . _DB_PREFIX_ . 'allegropro_order`
                ORDER BY `updated_at_allegro` DESC
                LIMIT 1';

        $result = Db::getInstance()->executeS($sql);
        if (!empty($result) && isset($result[0]['updated_at_allegro'])) {
            return $result[0]['updated_at_allegro'];
        }

        return null;
    }

    /**
     * Pobiera datę ostatniej aktualizacji zamówienia dla konkretnego konta.
     */
    public function getLastFetchedDateForAccount(int $accountId)
    {
        $sql = 'SELECT `updated_at_allegro`
                FROM `' . _DB_PREFIX_ . 'allegropro_order`
                WHERE `id_allegropro_account` = ' . (int)$accountId . '
                ORDER BY `updated_at_allegro` DESC
                LIMIT 1';

        $result = Db::getInstance()->executeS($sql);
        if (!empty($result) && isset($result[0]['updated_at_allegro'])) {
            return $result[0]['updated_at_allegro'];
        }

        return null;
    }

    public function getPaginated($limit = 20, $offset = 0)
    {
        return $this->getPaginatedFiltered([], (int)$limit, (int)$offset);
    }

    public function getPaginatedFiltered(array $filters, int $limit = 20, int $offset = 0): array
    {
        $q = new DbQuery();
        $q->select('o.*, a.label as account_label, s.method_name as shipping_method_name, b.firstname as buyer_firstname, b.lastname as buyer_lastname, b.phone_number as buyer_phone');
        $q->from('allegropro_order', 'o');
        $q->leftJoin('allegropro_account', 'a', 'a.id_allegropro_account = o.id_allegropro_account');
        $q->leftJoin('allegropro_order_shipping', 's', 's.checkout_form_id = o.checkout_form_id');
        $q->leftJoin('allegropro_order_buyer', 'b', 'b.checkout_form_id = o.checkout_form_id');

        $this->applyFilters($q, $filters);

        $q->orderBy('o.updated_at_allegro DESC');
        $q->limit($limit, $offset);

        return Db::getInstance()->executeS($q) ?: [];
    }

    public function countFiltered(array $filters): int
    {
        $q = new DbQuery();
        $q->select('COUNT(*)');
        $q->from('allegropro_order', 'o');
        $q->leftJoin('allegropro_order_shipping', 's', 's.checkout_form_id = o.checkout_form_id');

        $this->applyFilters($q, $filters);

        return (int)Db::getInstance()->getValue($q);
    }

    private function applyFilters(DbQuery $q, array $filters): void
    {
        if (!empty($filters['id_allegropro_account'])) {
            $q->where('o.id_allegropro_account = ' . (int)$filters['id_allegropro_account']);
        }

        if (!empty($filters['date_from'])) {
            $q->where("o.updated_at_allegro >= '" . pSQL($filters['date_from'] . ' 00:00:00') . "'");
        }

        if (!empty($filters['date_to'])) {
            $q->where("o.updated_at_allegro <= '" . pSQL($filters['date_to'] . ' 23:59:59') . "'");
        }

        if (!empty($filters['delivery_methods']) && is_array($filters['delivery_methods'])) {
            $vals = [];
            foreach ($filters['delivery_methods'] as $method) {
                $method = trim((string)$method);
                if ($method === '') {
                    continue;
                }
                $vals[] = "'" . pSQL($method) . "'";
            }
            if (!empty($vals)) {
                $q->where('s.method_name IN (' . implode(',', $vals) . ')');
            }
        }

        if (!empty($filters['statuses']) && is_array($filters['statuses'])) {
            $vals = [];
            foreach ($filters['statuses'] as $status) {
                $status = trim((string)$status);
                if ($status === '') {
                    continue;
                }
                $vals[] = "'" . pSQL($status) . "'";
            }
            if (!empty($vals)) {
                $q->where('o.status IN (' . implode(',', $vals) . ')');
            }
        }

        if (!empty($filters['checkout_form_id'])) {
            $q->where("o.checkout_form_id LIKE '%" . pSQL($filters['checkout_form_id']) . "%'");
        }
    }

    /**
     * Zwraca listę ID tylko dla zamówień NIEZAKOŃCZONYCH (is_finished=0) - globalnie.
     */
    public function getPendingIds(int $limit = 50): array
    {
        $sql = 'SELECT `checkout_form_id`
                FROM `' . _DB_PREFIX_ . 'allegropro_order`
                WHERE `is_finished` = 0
                ORDER BY `updated_at_allegro` DESC
                LIMIT ' . (int)$limit;

        $rows = Db::getInstance()->executeS($sql);

        $ids = [];
        if ($rows) {
            foreach ($rows as $r) {
                $ids[] = $r['checkout_form_id'];
            }
        }
        return $ids;
    }

    /**
     * Zwraca listę ID tylko dla zamówień NIEZAKOŃCZONYCH (is_finished=0) dla danego konta.
     */
    public function getPendingIdsForAccount(int $accountId, int $limit = 50): array
    {
        $sql = 'SELECT `checkout_form_id`
                FROM `' . _DB_PREFIX_ . 'allegropro_order`
                WHERE `is_finished` = 0
                  AND `id_allegropro_account` = ' . (int)$accountId . '
                ORDER BY `updated_at_allegro` DESC
                LIMIT ' . (int)$limit;

        $rows = Db::getInstance()->executeS($sql);

        $ids = [];
        if ($rows) {
            foreach ($rows as $r) {
                $ids[] = $r['checkout_form_id'];
            }
        }

        return $ids;
    }

    /**
     * Filtruje przekazaną listę checkoutFormId do takich, które są pending dla konta.
     * Zachowuje kolejność z wejścia.
     */
    public function filterPendingIdsForAccount(int $accountId, array $checkoutIds): array
    {
        $checkoutIds = array_values(array_unique(array_filter(array_map('strval', $checkoutIds))));
        if (empty($checkoutIds)) {
            return [];
        }

        $quoted = [];
        foreach ($checkoutIds as $id) {
            $quoted[] = "'" . pSQL($id) . "'";
        }

        $sql = 'SELECT `checkout_form_id`
                FROM `' . _DB_PREFIX_ . 'allegropro_order`
                WHERE `is_finished` = 0
                  AND `id_allegropro_account` = ' . (int)$accountId . '
                  AND `checkout_form_id` IN (' . implode(',', $quoted) . ')';

        $rows = Db::getInstance()->executeS($sql) ?: [];
        $pendingMap = [];
        foreach ($rows as $r) {
            $pendingMap[(string)$r['checkout_form_id']] = true;
        }

        $result = [];
        foreach ($checkoutIds as $id) {
            if (isset($pendingMap[$id])) {
                $result[] = $id;
            }
        }

        return $result;
    }

    public function getDistinctDeliveryMethods(): array
    {
        $sql = 'SELECT DISTINCT s.method_name
                FROM `' . _DB_PREFIX_ . 'allegropro_order_shipping` s
                WHERE s.method_name IS NOT NULL AND s.method_name != ""
                ORDER BY s.method_name ASC';

        $rows = Db::getInstance()->executeS($sql) ?: [];
        $out = [];
        foreach ($rows as $r) {
            $out[] = (string)$r['method_name'];
        }

        return $out;
    }

    public function getDistinctStatuses(): array
    {
        $sql = 'SELECT DISTINCT o.status
                FROM `' . _DB_PREFIX_ . 'allegropro_order` o
                WHERE o.status IS NOT NULL AND o.status != ""
                ORDER BY o.status ASC';

        $rows = Db::getInstance()->executeS($sql) ?: [];
        $out = [];
        foreach ($rows as $r) {
            $out[] = (string)$r['status'];
        }

        return $out;
    }

    public function getDistinctStatusesForAccount(int $accountId): array
    {
        return $this->getDistinctStatusesForAccounts([(int)$accountId]);
    }

    public function getDistinctStatusesForAccounts(array $accountIds): array
    {
        $safeAccountIds = $this->normalizeAccountIds($accountIds);
        if (empty($safeAccountIds)) {
            return [];
        }

        $sql = 'SELECT DISTINCT o.status
'
            . 'FROM `' . _DB_PREFIX_ . 'allegropro_order` o
'
            . 'WHERE o.id_allegropro_account IN (' . implode(',', $safeAccountIds) . ')
'
            . '  AND o.status IS NOT NULL AND o.status != ""
'
            . 'ORDER BY o.status ASC';

        $rows = Db::getInstance()->executeS($sql) ?: [];
        $out = [];
        foreach ($rows as $r) {
            $out[] = (string)$r['status'];
        }

        return $out;
    }

    public function getDistinctShipmentStatusesForAccount(int $accountId): array
    {
        return $this->getDistinctShipmentStatusesForAccounts([(int)$accountId]);
    }

    public function getDistinctShipmentStatusesForAccounts(array $accountIds): array
    {
        $safeAccountIds = $this->normalizeAccountIds($accountIds);
        if (empty($safeAccountIds)) {
            return [];
        }

        $sql = 'SELECT DISTINCT sh.status
'
            . 'FROM `' . _DB_PREFIX_ . 'allegropro_shipment` sh
'
            . 'WHERE sh.id_allegropro_account IN (' . implode(',', $safeAccountIds) . ')
'
            . '  AND sh.status IS NOT NULL AND sh.status != ""
'
            . 'ORDER BY sh.status ASC';

        $rows = Db::getInstance()->executeS($sql) ?: [];
        $out = [];
        foreach ($rows as $r) {
            $out[] = (string)$r['status'];
        }

        return $out;
    }

    public function countShipmentListFiltered(array $accountIds, array $filters, bool $withShipment): int
    {
        $safeAccountIds = $this->normalizeAccountIds($accountIds);
        if (empty($safeAccountIds)) {
            return 0;
        }

        $where = $this->buildShipmentFilterWhere($filters);
        $shipmentConstraint = $withShipment ? 'shx.checkout_form_id IS NOT NULL' : 'shx.checkout_form_id IS NULL';

        $sql = 'SELECT COUNT(*) '
            . 'FROM `' . _DB_PREFIX_ . 'allegropro_order` o '
            . 'LEFT JOIN `' . _DB_PREFIX_ . 'allegropro_account` a ON a.id_allegropro_account = o.id_allegropro_account '
            . 'LEFT JOIN ( '
            . '    SELECT MAX(id_allegropro_shipment) AS id_allegropro_shipment, checkout_form_id, id_allegropro_account '
            . '    FROM `' . _DB_PREFIX_ . 'allegropro_shipment` '
            . '    GROUP BY checkout_form_id, id_allegropro_account '
            . ') shx ON shx.checkout_form_id = o.checkout_form_id AND shx.id_allegropro_account = o.id_allegropro_account '
            . 'LEFT JOIN `' . _DB_PREFIX_ . 'allegropro_shipment` sh ON sh.id_allegropro_shipment = shx.id_allegropro_shipment '
            . 'WHERE o.id_allegropro_account IN (' . implode(',', $safeAccountIds) . ') '
            . 'AND ' . $shipmentConstraint . ' '
            . $where;

        return (int)Db::getInstance()->getValue($sql);
    }

    public function getShipmentListFiltered(array $accountIds, array $filters, int $limit, int $offset, bool $withShipment): array
    {
        $safeAccountIds = $this->normalizeAccountIds($accountIds);
        if (empty($safeAccountIds)) {
            return [];
        }

        $limit = max(1, (int)$limit);
        $offset = max(0, (int)$offset);

        $where = $this->buildShipmentFilterWhere($filters);
        $shipmentConstraint = $withShipment ? 'shx.checkout_form_id IS NOT NULL' : 'shx.checkout_form_id IS NULL';

        $sql = 'SELECT o.id_allegropro_account, a.label AS account_label, a.allegro_login, '
            . 'o.checkout_form_id, o.updated_at_allegro AS updated_at, o.status, o.total_amount, o.currency, o.buyer_login, '
            . 's.method_name AS shipping_method_name, sh.shipment_id, sh.status AS shipment_status, sh.updated_at AS shipment_updated_at '
            . 'FROM `' . _DB_PREFIX_ . 'allegropro_order` o '
            . 'LEFT JOIN `' . _DB_PREFIX_ . 'allegropro_account` a ON a.id_allegropro_account = o.id_allegropro_account '
            . 'LEFT JOIN `' . _DB_PREFIX_ . 'allegropro_order_shipping` s ON s.checkout_form_id = o.checkout_form_id '
            . 'LEFT JOIN ( '
            . '    SELECT MAX(id_allegropro_shipment) AS id_allegropro_shipment, checkout_form_id, id_allegropro_account '
            . '    FROM `' . _DB_PREFIX_ . 'allegropro_shipment` '
            . '    GROUP BY checkout_form_id, id_allegropro_account '
            . ') shx ON shx.checkout_form_id = o.checkout_form_id AND shx.id_allegropro_account = o.id_allegropro_account '
            . 'LEFT JOIN `' . _DB_PREFIX_ . 'allegropro_shipment` sh ON sh.id_allegropro_shipment = shx.id_allegropro_shipment '
            . 'WHERE o.id_allegropro_account IN (' . implode(',', $safeAccountIds) . ') '
            . 'AND ' . $shipmentConstraint . ' '
            . $where
            . 'ORDER BY o.updated_at_allegro DESC '
            . 'LIMIT ' . $limit . ' OFFSET ' . $offset;

        return Db::getInstance()->executeS($sql) ?: [];
    }

    private function normalizeAccountIds(array $accountIds): array
    {
        $safe = [];
        foreach ($accountIds as $id) {
            $id = (int)$id;
            if ($id > 0) {
                $safe[] = $id;
            }
        }

        $safe = array_values(array_unique($safe));
        sort($safe);

        return $safe;
    }

    private function buildShipmentFilterWhere(array $filters): string
    {
        $parts = [];

        if (!empty($filters['query'])) {
            $query = pSQL((string)$filters['query']);
            $parts[] = "(o.checkout_form_id LIKE '%" . $query . "%' OR o.buyer_login LIKE '%" . $query . "%' OR sh.shipment_id LIKE '%" . $query . "%' OR a.label LIKE '%" . $query . "%')";
        }

        if (!empty($filters['date_from'])) {
            $parts[] = "o.updated_at_allegro >= '" . pSQL((string)$filters['date_from'] . ' 00:00:00') . "'";
        }

        if (!empty($filters['date_to'])) {
            $parts[] = "o.updated_at_allegro <= '" . pSQL((string)$filters['date_to'] . ' 23:59:59') . "'";
        }

        if (!empty($filters['statuses']) && is_array($filters['statuses'])) {
            $safeStatuses = [];
            foreach ($filters['statuses'] as $status) {
                $status = trim((string)$status);
                if ($status === '') {
                    continue;
                }
                $safeStatuses[] = "'" . pSQL($status) . "'";
            }
            if (!empty($safeStatuses)) {
                $parts[] = 'o.status IN (' . implode(',', $safeStatuses) . ')';
            }
        }

        if (!empty($filters['shipment_statuses']) && is_array($filters['shipment_statuses'])) {
            $safeShipStatuses = [];
            foreach ($filters['shipment_statuses'] as $status) {
                $status = trim((string)$status);
                if ($status === '') {
                    continue;
                }
                $safeShipStatuses[] = "'" . pSQL($status) . "'";
            }
            if (!empty($safeShipStatuses)) {
                $parts[] = 'sh.status IN (' . implode(',', $safeShipStatuses) . ')';
            }
        }

        if (empty($parts)) {
            return '';
        }

        return ' AND ' . implode(' AND ', $parts) . ' ';
    }

    public function markAsFinished(string $checkoutFormId)
    {
        Db::getInstance()->update(
            'allegropro_order',
            ['is_finished' => 1],
            "checkout_form_id = '" . pSQL($checkoutFormId) . "'"
        );
    }

    public function exists(string $checkoutFormId)
    {
        $q = new DbQuery();
        $q->select('id_allegropro_order');
        $q->from('allegropro_order');
        $q->where("checkout_form_id = '" . pSQL($checkoutFormId) . "'");
        return (int) Db::getInstance()->getValue($q);
    }

    /**
     * Kompatybilna wersja sprawdzenia istnienia zamówienia per konto.
     *
     * Starsze wersje OrderFetcher wywołują właśnie tę metodę.
     */
    public function existsForAccount(int $accountId, string $checkoutFormId): int
    {
        $q = new DbQuery();
        $q->select('id_allegropro_order');
        $q->from('allegropro_order');
        $q->where('id_allegropro_account = ' . (int)$accountId);
        $q->where("checkout_form_id = '" . pSQL($checkoutFormId) . "'");

        return (int)Db::getInstance()->getValue($q);
    }

    public function updatePsOrderId($checkoutFormId, $psOrderId)
    {
        Db::getInstance()->update('allegropro_order', ['id_order_prestashop' => (int)$psOrderId], "checkout_form_id = '" . pSQL($checkoutFormId) . "'");
    }

    public function markShipment(int $accountId, string $checkoutFormId, ?string $shipmentId, string $commandId): bool
    {
        $db = Db::getInstance();
        $accountId = (int)$accountId;
        $checkoutFormIdEsc = pSQL($checkoutFormId);

        $orderExists = (int)$db->getValue(
            'SELECT id_allegropro_order FROM `' . _DB_PREFIX_ . 'allegropro_order` WHERE id_allegropro_account = ' . $accountId . " AND checkout_form_id = '" . $checkoutFormIdEsc . "'"
        );

        if ($orderExists <= 0) {
            return false;
        }

        $targetShipmentId = (string)($shipmentId ?: $commandId);
        if ($targetShipmentId === '') {
            return false;
        }

        $targetShipmentIdEsc = pSQL($targetShipmentId);
        $commandIdEsc = pSQL($commandId);
        $now = date('Y-m-d H:i:s');

        $existingId = (int)$db->getValue(
            'SELECT id_allegropro_shipment FROM `' . _DB_PREFIX_ . 'allegropro_shipment` '
            . 'WHERE id_allegropro_account = ' . $accountId . " AND checkout_form_id = '" . $checkoutFormIdEsc . "' "
            . "AND (shipment_id = '" . $targetShipmentIdEsc . "' OR shipment_id = '" . $commandIdEsc . "') "
            . 'ORDER BY id_allegropro_shipment DESC'
        );

        $status = $shipmentId ? 'CREATED' : 'NEW';
        $row = [
            'id_allegropro_account' => $accountId,
            'checkout_form_id' => $checkoutFormIdEsc,
            'shipment_id' => $targetShipmentIdEsc,
            'status' => pSQL($status),
            'updated_at' => pSQL($now),
        ];

        if ($existingId > 0) {
            return $db->update('allegropro_shipment', $row, 'id_allegropro_shipment=' . $existingId);
        }

        $row['tracking_number'] = '';
        $row['carrier_mode'] = 'BOX';
        $row['size_details'] = 'CUSTOM';
        $row['is_smart'] = 0;
        $row['label_path'] = null;
        $row['created_at'] = pSQL($now);

        return $db->insert('allegropro_shipment', $row);
    }

    public function list(int $accountId, int $limit = 50): array
    {
        $accountId = (int)$accountId;
        $limit = max(1, (int)$limit);

        $sql = 'SELECT o.checkout_form_id, o.updated_at_allegro AS updated_at, o.status, o.total_amount, o.currency, o.buyer_login, '
            . 's.method_name AS shipping_method_name, sh.shipment_id, sh.status AS shipment_status, sh.updated_at AS shipment_updated_at '
            . 'FROM `' . _DB_PREFIX_ . 'allegropro_order` o '
            . 'LEFT JOIN `' . _DB_PREFIX_ . 'allegropro_order_shipping` s ON s.checkout_form_id = o.checkout_form_id '
            . 'LEFT JOIN ( '
            . '    SELECT MAX(id_allegropro_shipment) AS id_allegropro_shipment, checkout_form_id '
            . '    FROM `' . _DB_PREFIX_ . 'allegropro_shipment` '
            . '    WHERE id_allegropro_account = ' . $accountId . ' '
            . '    GROUP BY checkout_form_id '
            . ') shx ON shx.checkout_form_id = o.checkout_form_id '
            . 'LEFT JOIN `' . _DB_PREFIX_ . 'allegropro_shipment` sh ON sh.id_allegropro_shipment = shx.id_allegropro_shipment '
            . 'WHERE o.id_allegropro_account = ' . $accountId . ' '
            . 'ORDER BY o.updated_at_allegro DESC '
            . 'LIMIT ' . $limit;

        return Db::getInstance()->executeS($sql) ?: [];
    }

    public function listWithoutShipment(int $accountId, int $limit = 50): array
    {
        $accountId = (int)$accountId;
        $limit = max(1, (int)$limit);

        $sql = 'SELECT o.checkout_form_id, o.updated_at_allegro AS updated_at, o.status, o.total_amount, o.currency, o.buyer_login, '
            . 's.method_name AS shipping_method_name '
            . 'FROM `' . _DB_PREFIX_ . 'allegropro_order` o '
            . 'LEFT JOIN `' . _DB_PREFIX_ . 'allegropro_order_shipping` s ON s.checkout_form_id = o.checkout_form_id '
            . 'LEFT JOIN ( '
            . '    SELECT checkout_form_id '
            . '    FROM `' . _DB_PREFIX_ . 'allegropro_shipment` '
            . '    WHERE id_allegropro_account = ' . $accountId . ' '
            . '    GROUP BY checkout_form_id '
            . ') shx ON shx.checkout_form_id = o.checkout_form_id '
            . 'WHERE o.id_allegropro_account = ' . $accountId . ' AND shx.checkout_form_id IS NULL '
            . 'ORDER BY o.updated_at_allegro DESC '
            . 'LIMIT ' . $limit;

        return Db::getInstance()->executeS($sql) ?: [];
    }

    public function getRaw(int $accountId, string $checkoutFormId): ?array
    {
        $accountId = (int)$accountId;
        $checkoutFormIdEsc = pSQL($checkoutFormId);

        $sql = 'SELECT o.*, sh.shipment_id, sh.status AS shipment_status, sh.updated_at AS shipment_updated_at '
            . 'FROM `' . _DB_PREFIX_ . 'allegropro_order` o '
            . 'LEFT JOIN ( '
            . '    SELECT MAX(id_allegropro_shipment) AS id_allegropro_shipment, checkout_form_id '
            . '    FROM `' . _DB_PREFIX_ . 'allegropro_shipment` '
            . '    WHERE id_allegropro_account = ' . $accountId . ' '
            . '    GROUP BY checkout_form_id '
            . ') shx ON shx.checkout_form_id = o.checkout_form_id '
            . 'LEFT JOIN `' . _DB_PREFIX_ . 'allegropro_shipment` sh ON sh.id_allegropro_shipment = shx.id_allegropro_shipment '
            . 'WHERE o.id_allegropro_account = ' . $accountId . " AND o.checkout_form_id = '" . $checkoutFormIdEsc . "' "
            ;

        $row = Db::getInstance()->getRow($sql);
        return $row ?: null;
    }

    public function getDecodedOrder(int $accountId, string $checkoutFormId): ?array
    {
        $q = new DbQuery();
        $q->select('*');
        $q->from('allegropro_order');
        $q->where("checkout_form_id = '".pSQL($checkoutFormId)."' AND id_allegropro_account = ".(int)$accountId);
        $order = Db::getInstance()->getRow($q);

        if (!$order) return null;

        $buyer = Db::getInstance()->getRow("SELECT * FROM "._DB_PREFIX_."allegropro_order_buyer WHERE checkout_form_id = '".pSQL($checkoutFormId)."'");
        $shipping = Db::getInstance()->getRow("SELECT * FROM "._DB_PREFIX_."allegropro_order_shipping WHERE checkout_form_id = '".pSQL($checkoutFormId)."'");

        $delivery = [
            'method' => ['id' => $shipping['method_id'] ?? null],
            'pickupPoint' => ['id' => $shipping['pickup_point_id'] ?? null],
            'address' => [
                'firstName' => '',
                'lastName' => $shipping['addr_name'] ?? '',
                'street' => $shipping['addr_street'] ?? '',
                'city' => $shipping['addr_city'] ?? '',
                'zipCode' => $shipping['addr_zip'] ?? '',
                'countryCode' => $shipping['addr_country'] ?? 'PL',
                'phoneNumber' => $shipping['addr_phone'] ?? '',
            ]
        ];

        return [
            'buyer' => [
                'email' => $buyer['email'] ?? '',
                'phoneNumber' => $buyer['phone_number'] ?? '',
            ],
            'delivery' => $delivery
        ];
    }

    public function getPendingCheckoutFormIdsForAccount(int $accountId, int $limit = 50, bool $onlyFetched = false): array
    {
        $accountId = (int)$accountId;
        $limit = max(1, (int)$limit);

        $where = 'WHERE id_allegropro_account = ' . $accountId . ' AND is_finished = 0';
        if ($onlyFetched) {
            $where .= " AND fetched_at IS NOT NULL";
        }

        $sql = 'SELECT checkout_form_id
                FROM `' . _DB_PREFIX_ . 'allegropro_order`
                ' . $where . '
                ORDER BY updated_at_allegro DESC
                LIMIT ' . $limit;

        $rows = Db::getInstance()->executeS($sql) ?: [];
        $out = [];
        foreach ($rows as $r) {
            $out[] = (string)$r['checkout_form_id'];
        }

        return $out;
    }

    public function markImportedByCheckoutIdsForAccount(array $ids, int $accountId): int
    {
        $ids = array_values(array_unique(array_filter(array_map('strval', $ids))));
        if (empty($ids)) {
            return 0;
        }

        $quoted = [];
        foreach ($ids as $id) {
            $quoted[] = "'" . pSQL($id) . "'";
        }

        $sql = 'UPDATE `' . _DB_PREFIX_ . 'allegropro_order`
                SET imported_at = NOW()
                WHERE id_allegropro_account = ' . (int)$accountId . '
                  AND checkout_form_id IN (' . implode(',', $quoted) . ')';

        Db::getInstance()->execute($sql);
        return (int)Db::getInstance()->Affected_Rows();
    }

    public function saveOrderData(array $data): void
    {
        $this->saveFullOrder($data);
    }

    public function saveOrder(array $data): void
    {
        $this->saveFullOrder($data);
    }

    public function upsertOrder(array $data): void
    {
        $this->saveFullOrder($data);
    }

    public function saveFullOrder(array $data)
    {
        $db = Db::getInstance();
        $cfId = $data['id'];
        $cfIdEsc = pSQL($cfId);

        $amount = $data['totalToPay']['amount'] ?? ($data['summary']['totalToPay']['amount'] ?? 0.00);
        $currency = $data['totalToPay']['currency'] ?? 'PLN';
        $newStatus = pSQL($data['status']);

        $orderData = [
            'id_allegropro_account' => (int)$data['account_id'],
            'checkout_form_id' => $cfIdEsc,
            'status' => $newStatus,
            'buyer_login' => pSQL($data['buyer']['login']),
            'buyer_email' => pSQL($data['buyer']['email']),
            'total_amount' => (float)$amount,
            'currency' => pSQL($currency),
            'created_at_allegro' => pSQL(str_replace(['T','Z'], [' ',''], $data['boughtAt'] ?? $data['updatedAt'])),
            'updated_at_allegro' => pSQL(str_replace(['T','Z'], [' ',''], $data['updatedAt'])),
            'date_upd' => date('Y-m-d H:i:s'),
        ];

        $existingId = $this->exists($cfId);

        if ($existingId) {
            $oldStatus = $db->getValue("SELECT status FROM "._DB_PREFIX_."allegropro_order WHERE id_allegropro_order = ".(int)$existingId);

            if ($oldStatus !== $newStatus) {
                $orderData['is_finished'] = 0;
            }

            unset($orderData['date_add']);
            $db->update('allegropro_order', $orderData, "id_allegropro_order = $existingId");
        } else {
            $orderData['date_add'] = date('Y-m-d H:i:s');
            $orderData['is_finished'] = 0;
            $db->insert('allegropro_order', $orderData);
        }

        $tables = ['allegropro_order_item', 'allegropro_order_shipping', 'allegropro_order_payment', 'allegropro_order_invoice', 'allegropro_order_buyer'];
        foreach ($tables as $t) {
            $db->delete($t, "checkout_form_id = '$cfIdEsc'");
        }

        // 1. BUYER
        $bLogin = $data['buyer']['login'];
        $bEmail = $data['buyer']['email'];
        $bFirst = $data['buyer']['firstName'] ?? '';
        $bLast  = $data['buyer']['lastName'] ?? '';
        $bCompany = $data['buyer']['companyName'] ?? '';
        $bPhone = $data['buyer']['phoneNumber'] ?? '';
        $bStreet = ''; $bCity = ''; $bZip = ''; $bCountry = 'PL'; $bTaxId = '';

        if (!empty($data['invoice']['address'])) {
            $inv = $data['invoice'];
            $bStreet = $inv['address']['street'] ?? '';
            $bCity = $inv['address']['city'] ?? '';
            $bZip = $inv['address']['zipCode'] ?? '';
            $bCountry = $inv['address']['countryCode'] ?? 'PL';
            $bTaxId = $inv['taxId'] ?? '';
            $bCompany = $inv['companyName'] ?? $bCompany;
        }

        $buyerData = [
            'checkout_form_id' => $cfIdEsc,
            'login' => pSQL($bLogin),
            'email' => pSQL($bEmail),
            'firstname' => pSQL($bFirst),
            'lastname' => pSQL($bLast),
            'company_name' => pSQL($bCompany),
            'phone_number' => pSQL($bPhone),
            'street' => pSQL($bStreet),
            'city' => pSQL($bCity),
            'zip_code' => pSQL($bZip),
            'country_code' => pSQL($bCountry),
            'tax_id' => pSQL($bTaxId),
            'date_add' => date('Y-m-d H:i:s')
        ];
        $db->insert('allegropro_order_buyer', $buyerData);

        // 2. SHIPPING
        $shipping = $data['delivery'] ?? [];
        $method = $shipping['method'] ?? [];
        $addr = $shipping['address'] ?? [];

        $shipData = [
            'checkout_form_id' => $cfIdEsc,
            'method_id' => pSQL($method['id'] ?? ''),
            'method_name' => pSQL($method['name'] ?? ''),
            'pickup_point_id' => pSQL($shipping['pickupPoint']['id'] ?? ''),
            'pickup_point_name' => pSQL($shipping['pickupPoint']['name'] ?? ''),
            'cost_amount' => (float)($shipping['cost']['amount'] ?? 0),
            'cost_currency' => pSQL($shipping['cost']['currency'] ?? 'PLN'),
            'addr_name' => pSQL(trim(($addr['firstName'] ?? '') . ' ' . ($addr['lastName'] ?? ''))),
            'addr_street' => pSQL($addr['street'] ?? ''),
            'addr_post_code' => pSQL($addr['zipCode'] ?? ''),
            'addr_city' => pSQL($addr['city'] ?? ''),
            'addr_country' => pSQL($addr['countryCode'] ?? 'PL'),
            'addr_phone' => pSQL($addr['phoneNumber'] ?? ''),
            'date_add' => date('Y-m-d H:i:s')
        ];
        $db->insert('allegropro_order_shipping', $shipData);

        // 3. PAYMENT
        if (!empty($data['payment'])) {
            $pay = $data['payment'];
            $payData = [
                'checkout_form_id' => $cfIdEsc,
                'type' => pSQL($pay['type'] ?? ''),
                'provider' => pSQL($pay['provider'] ?? ''),
                'finished_at' => pSQL(str_replace(['T','Z'], [' ',''], $pay['finishedAt'] ?? '')),
                'paid_amount' => (float)($pay['paidAmount']['amount'] ?? 0),
                'paid_currency' => pSQL($pay['paidAmount']['currency'] ?? 'PLN'),
                'reconciliation_amount' => (float)($pay['reconciliation']['amount']['amount'] ?? 0),
                'reconciliation_currency' => pSQL($pay['reconciliation']['amount']['currency'] ?? 'PLN'),
                'status' => pSQL($pay['status'] ?? ''),
                'date_add' => date('Y-m-d H:i:s')
            ];
            $db->insert('allegropro_order_payment', $payData);
        }

        // 4. ITEMS
        if (!empty($data['lineItems']) && is_array($data['lineItems'])) {
            foreach ($data['lineItems'] as $item) {
                $itemData = [
                    'checkout_form_id' => $cfIdEsc,
                    'offer_id' => pSQL($item['offer']['id'] ?? ''),
                    'offer_name' => pSQL($item['offer']['name'] ?? ''),
                    'external_id' => pSQL($item['offer']['external']['id'] ?? ''),
                    'quantity' => (int)($item['quantity'] ?? 1),
                    'price_amount' => (float)($item['price']['amount'] ?? 0),
                    'price_currency' => pSQL($item['price']['currency'] ?? 'PLN'),
                    'bought_at' => pSQL(str_replace(['T','Z'], [' ',''], $item['boughtAt'] ?? '')),
                    'date_add' => date('Y-m-d H:i:s')
                ];
                $db->insert('allegropro_order_item', $itemData);
            }
        }

        // 5. INVOICE
        if (!empty($data['invoice'])) {
            $inv = $data['invoice'];
            $invData = [
                'checkout_form_id' => $cfIdEsc,
                'required' => (int)($inv['required'] ?? 0),
                'address_street' => pSQL($inv['address']['street'] ?? ''),
                'address_city' => pSQL($inv['address']['city'] ?? ''),
                'address_zip_code' => pSQL($inv['address']['zipCode'] ?? ''),
                'address_country_code' => pSQL($inv['address']['countryCode'] ?? 'PL'),
                'company_name' => pSQL($inv['companyName'] ?? ''),
                'tax_id' => pSQL($inv['taxId'] ?? ''),
                'natural_person' => (int)($inv['naturalPerson'] ?? 0),
                'date_add' => date('Y-m-d H:i:s')
            ];
            $db->insert('allegropro_order_invoice', $invData);
        }
    }
}
