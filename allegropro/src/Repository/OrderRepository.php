<?php
namespace AllegroPro\Repository;

use Db;
use DbQuery;

class OrderRepository
{
    /**
     * Cache dostępnych kolumn tabeli allegropro_order (dla zgodności ze starszymi instalacjami).
     *
     * @var array<string, bool>|null
     */
    private $orderTableColumns = null;

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
        $q->select('COUNT(DISTINCT o.id_allegropro_order)');
        $q->from('allegropro_order', 'o');
        $q->leftJoin('allegropro_order_shipping', 's', 's.checkout_form_id = o.checkout_form_id');
        $q->leftJoin('allegropro_order_buyer', 'b', 'b.checkout_form_id = o.checkout_form_id');
        $q->leftJoin('allegropro_account', 'a', 'a.id_allegropro_account = o.id_allegropro_account');

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

        if (!empty($filters['global_query'])) {
            $raw = (string)$filters['global_query'];
            $search = pSQL($raw);
            $digits = preg_replace('/\D+/', '', $raw);
            $globalConditions = [
                "o.checkout_form_id LIKE '%" . $search . "%'",
                "o.status LIKE '%" . $search . "%'",
                "s.method_name LIKE '%" . $search . "%'",
                "b.firstname LIKE '%" . $search . "%'",
                "b.lastname LIKE '%" . $search . "%'",
                "b.email LIKE '%" . $search . "%'",
                "b.login LIKE '%" . $search . "%'",
                "b.phone_number LIKE '%" . $search . "%'",
                "a.label LIKE '%" . $search . "%'",
                "EXISTS (SELECT 1 FROM `" . _DB_PREFIX_ . "allegropro_shipment` sh WHERE sh.checkout_form_id = o.checkout_form_id AND ("
                    . "sh.shipment_id LIKE '%" . $search . "%' OR sh.tracking_number LIKE '%" . $search . "%' OR sh.wza_command_id LIKE '%" . $search . "%' OR sh.wza_shipment_uuid LIKE '%" . $search . "%' OR sh.wza_label_shipment_id LIKE '%" . $search . "%'"
                . "))",
                "EXISTS (SELECT 1 FROM `" . _DB_PREFIX_ . "allegropro_order_item` oi WHERE oi.checkout_form_id = o.checkout_form_id AND ("
                    . "oi.name LIKE '%" . $search . "%' OR oi.offer_id LIKE '%" . $search . "%' OR oi.ean LIKE '%" . $search . "%' OR oi.reference_number LIKE '%" . $search . "%'"
                . "))",
                "EXISTS (SELECT 1 FROM `" . _DB_PREFIX_ . "allegropro_order_payment` op WHERE op.checkout_form_id = o.checkout_form_id AND ("
                    . "op.payment_id LIKE '%" . $search . "%' OR op.status LIKE '%" . $search . "%' OR op.provider LIKE '%" . $search . "%' OR CAST(op.paid_amount AS CHAR) LIKE '%" . $search . "%'"
                . "))",
                "EXISTS (SELECT 1 FROM `" . _DB_PREFIX_ . "allegropro_order_invoice` inv WHERE inv.checkout_form_id = o.checkout_form_id AND ("
                    . "inv.company_name LIKE '%" . $search . "%' OR inv.tax_id LIKE '%" . $search . "%' OR inv.street LIKE '%" . $search . "%' OR inv.city LIKE '%" . $search . "%' OR inv.zip_code LIKE '%" . $search . "%'"
                . "))",
            ];
            // Phone normalization: allow searching phone without spaces/+48 etc.
            if ($digits !== '' && strlen($digits) >= 5) {
                $digitsSql = pSQL($digits);
                $digits9 = (strlen($digits) > 9) ? substr($digits, -9) : $digits;
                $phoneExpr = "REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(b.phone_number,' ',''),'+',''),'-',''),'(',''),')','')";
                $globalConditions[] = $phoneExpr . " LIKE '%" . $digitsSql . "%'";
                if ($digits9 !== $digits) {
                    $globalConditions[] = $phoneExpr . " LIKE '%" . pSQL($digits9) . "%'";
                }
            }



            if ($this->hasOrderTableColumn('buyer_login')) {
                $globalConditions[] = "o.buyer_login LIKE '%" . $search . "%'";
            }

            if ($this->hasOrderTableColumn('buyer_email')) {
                $globalConditions[] = "o.buyer_email LIKE '%" . $search . "%'";
            }

            if ($this->hasOrderTableColumn('currency')) {
                $globalConditions[] = "o.currency LIKE '%" . $search . "%'";
            }

            if ($this->hasOrderTableColumn('total_amount')) {
                $globalConditions[] = "CAST(o.total_amount AS CHAR) LIKE '%" . $search . "%'";
            }

            $q->where('(' . implode(' OR ', $globalConditions) . ')');
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
     * Liczba wszystkich zamówień zapisanych lokalnie dla konta.
     */
    public function countStoredOrdersForAccount(int $accountId): int
    {
        $sql = 'SELECT COUNT(*)
                FROM `' . _DB_PREFIX_ . 'allegropro_order`
                WHERE `id_allegropro_account` = ' . (int)$accountId;

        return (int)Db::getInstance()->getValue($sql);
    }

    /**
     * Zwraca partię checkout_form_id lokalnie zapisanych zamówień dla konta.
     */
    public function getStoredOrderIdsForAccountBatch(int $accountId, int $limit = 50, int $offset = 0): array
    {
        if ($limit <= 0) {
            $limit = 50;
        }

        if ($offset < 0) {
            $offset = 0;
        }

        $sql = 'SELECT `checkout_form_id`
                FROM `' . _DB_PREFIX_ . 'allegropro_order`
                WHERE `id_allegropro_account` = ' . (int)$accountId . '
                ORDER BY `id_allegropro_order` DESC
                LIMIT ' . (int)$offset . ', ' . (int)$limit;

        $rows = Db::getInstance()->executeS($sql) ?: [];
        $ids = [];
        foreach ($rows as $r) {
            $ids[] = (string)$r['checkout_form_id'];
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

    /**
     * Sprawdza, czy lokalny rekord zamówienia ma komplet danych potrzebnych do importu/naprawy.
     *
     * Zamówienie uznajemy za kompletne, gdy:
     * - istnieje rekord główny w allegropro_order,
     * - istnieje co najmniej 1 pozycja w allegropro_order_item,
     * - istnieje rekord buyer,
     * - istnieje rekord shipping.
     */
    public function isOrderDataCompleteForAccount(int $accountId, string $checkoutFormId): bool
    {
        $accountId = (int)$accountId;
        $checkoutFormIdEsc = pSQL($checkoutFormId);
        $db = Db::getInstance();

        $orderExists = (int)$db->getValue(
            'SELECT id_allegropro_order FROM `' . _DB_PREFIX_ . 'allegropro_order` '
            . 'WHERE id_allegropro_account = ' . $accountId . " AND checkout_form_id = '" . $checkoutFormIdEsc . "'"
        );

        if ($orderExists <= 0) {
            return false;
        }

        $itemsCount = (int)$db->getValue(
            'SELECT COUNT(*) FROM `' . _DB_PREFIX_ . 'allegropro_order_item` '
            . "WHERE checkout_form_id = '" . $checkoutFormIdEsc . "'"
        );

        $buyerCount = (int)$db->getValue(
            'SELECT COUNT(*) FROM `' . _DB_PREFIX_ . 'allegropro_order_buyer` '
            . "WHERE checkout_form_id = '" . $checkoutFormIdEsc . "'"
        );

        $shippingCount = (int)$db->getValue(
            'SELECT COUNT(*) FROM `' . _DB_PREFIX_ . 'allegropro_order_shipping` '
            . "WHERE checkout_form_id = '" . $checkoutFormIdEsc . "'"
        );

        return $itemsCount > 0 && $buyerCount > 0 && $shippingCount > 0;
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

        $now = date('Y-m-d H:i:s');
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
        ];

        // Zgodność schematu: starsze wdrożenia mogą mieć created_at/updated_at zamiast date_add/date_upd.
        if ($this->hasOrderTableColumn('date_upd')) {
            $orderData['date_upd'] = $now;
        } elseif ($this->hasOrderTableColumn('updated_at')) {
            $orderData['updated_at'] = $now;
        }

        $existingId = $this->exists($cfId);

        if ($existingId) {
            $oldStatus = $db->getValue("SELECT status FROM "._DB_PREFIX_."allegropro_order WHERE id_allegropro_order = ".(int)$existingId);

            if ($oldStatus !== $newStatus) {
                $orderData['is_finished'] = 0;
            }

            unset($orderData['date_add'], $orderData['created_at']);
            $db->update('allegropro_order', $orderData, "id_allegropro_order = $existingId");
        } else {
            if ($this->hasOrderTableColumn('date_add')) {
                $orderData['date_add'] = $now;
            } elseif ($this->hasOrderTableColumn('created_at')) {
                $orderData['created_at'] = $now;
            }

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
            $bCompany = $inv['company']['name'] ?? $bCompany;
            $bTaxId = $inv['company']['taxId'] ?? '';
        } elseif (!empty($data['delivery']['address'])) {
            $delAddr = $data['delivery']['address'];
            $bStreet = $delAddr['street'];
            $bCity = $delAddr['city'];
            $bZip = $delAddr['zipCode'];
            $bCountry = $delAddr['countryCode'];
            if (empty($bFirst)) $bFirst = $delAddr['firstName'] ?? '';
            if (empty($bLast)) $bLast = $delAddr['lastName'] ?? '';
            if (empty($bCompany)) $bCompany = $delAddr['companyName'] ?? '';
            if (empty($bPhone)) $bPhone = $delAddr['phoneNumber'] ?? '';
        }

        $db->insert('allegropro_order_buyer', [
            'checkout_form_id' => $cfIdEsc,
            'email' => pSQL($bEmail),
            'login' => pSQL($bLogin),
            'firstname' => pSQL($bFirst),
            'lastname' => pSQL($bLast),
            'company_name' => pSQL($bCompany),
            'street' => pSQL($bStreet),
            'city' => pSQL($bCity),
            'zip_code' => pSQL($bZip),
            'country_code' => pSQL($bCountry),
            'phone_number' => pSQL($bPhone),
            'tax_id' => pSQL($bTaxId)
        ]);

        // 2. ITEMS
        if (!empty($data['lineItems'])) {
            foreach ($data['lineItems'] as $item) {
                $db->insert('allegropro_order_item', [
                    'checkout_form_id' => $cfIdEsc,
                    'offer_id' => pSQL($item['offer']['id']),
                    'name' => pSQL($item['offer']['name']),
                    'quantity' => (int)$item['quantity'],
                    'price' => (float)$item['price']['amount'],
                    'ean' => pSQL($item['mapped_ean'] ?? null),
                    'reference_number' => pSQL($item['offer']['external']['id'] ?? null),
                    'id_product' => (int)($item['matched_id_product'] ?? 0),
                    'id_product_attribute' => (int)($item['matched_id_attribute'] ?? 0),
                    'tax_rate' => (float)($item['matched_tax_rate'] ?? 0.00)
                ]);
            }
        }

        // 3. SHIPPING
        if (!empty($data['delivery'])) {
            $del = $data['delivery'];
            $addr = $del['address'] ?? [];
            $db->insert('allegropro_order_shipping', [
                'checkout_form_id' => $cfIdEsc,
                'method_id' => pSQL($del['method']['id'] ?? ''),
                'method_name' => pSQL($del['method']['name'] ?? ''),
                'cost_amount' => (float)($del['cost']['gross']['amount'] ?? 0),
                'is_smart' => isset($del['smart']) ? (int)$del['smart'] : 0,
                'package_count' => isset($del['calculatedNumberOfPackages']) ? (int)$del['calculatedNumberOfPackages'] : 1,
                'addr_name' => pSQL(($addr['firstName'] ?? '') . ' ' . ($addr['lastName'] ?? '')),
                'addr_street' => pSQL($addr['street'] ?? ''),
                'addr_city' => pSQL($addr['city'] ?? ''),
                'addr_zip' => pSQL($addr['zipCode'] ?? ''),
                'addr_country' => pSQL($addr['countryCode'] ?? 'PL'),
                'addr_phone' => pSQL($addr['phoneNumber'] ?? ''),
                'pickup_point_id' => pSQL($del['pickupPoint']['id'] ?? ''),
                'pickup_point_name' => pSQL($del['pickupPoint']['name'] ?? '')
            ]);
        }

        // 4. PAYMENT
        if (!empty($data['payment'])) {
            $pay = $data['payment'];
            $db->insert('allegropro_order_payment', [
                'checkout_form_id' => $cfIdEsc,
                'payment_id' => pSQL($pay['id'] ?? ''),
                'paid_amount' => (float)($pay['paidAmount']['amount'] ?? 0.00),
                'status' => pSQL($pay['status'] ?? ''),
                'provider' => pSQL($pay['provider'] ?? ''),
                'finished_at' => !empty($pay['finishedAt']) ? pSQL(str_replace(['T','Z'], [' ',''], $pay['finishedAt'])) : null
            ]);
        }

        // 5. INVOICE
        if (!empty($data['invoice'])) {
            $inv = $data['invoice'];
            $addr = $inv['address'] ?? [];
            $company = $inv['company'] ?? [];
            $db->insert('allegropro_order_invoice', [
                'checkout_form_id' => $cfIdEsc,
                'company_name' => pSQL($company['name'] ?? ''),
                'tax_id' => pSQL($company['taxId'] ?? ''),
                'street' => pSQL($addr['street'] ?? ''),
                'city' => pSQL($addr['city'] ?? ''),
                'zip_code' => pSQL($addr['zipCode'] ?? ''),
                'country_code' => pSQL($addr['countryCode'] ?? 'PL'),
                'natural_person' => !empty($inv['naturalPerson']) ? 1 : 0
            ]);
        }
    }

    private function hasOrderTableColumn(string $column): bool
    {
        $columns = $this->getOrderTableColumns();
        return isset($columns[$column]);
    }

    /**
     * @return array<string, bool>
     */
    private function getOrderTableColumns(): array
    {
        if (is_array($this->orderTableColumns)) {
            return $this->orderTableColumns;
        }

        $this->orderTableColumns = [];
        $rows = Db::getInstance()->executeS('SHOW COLUMNS FROM `' . _DB_PREFIX_ . 'allegropro_order`');
        if (!is_array($rows)) {
            return $this->orderTableColumns;
        }

        foreach ($rows as $row) {
            $field = isset($row['Field']) ? (string)$row['Field'] : '';
            if ($field !== '') {
                $this->orderTableColumns[$field] = true;
            }
        }

        return $this->orderTableColumns;
    }

}
