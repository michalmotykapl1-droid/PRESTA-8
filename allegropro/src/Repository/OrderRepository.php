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
        $q = new DbQuery();
        $q->select('o.*, a.label as account_label, s.method_name as shipping_method_name');
        $q->from('allegropro_order', 'o');
        $q->leftJoin('allegropro_account', 'a', 'a.id_allegropro_account = o.id_allegropro_account');
        $q->leftJoin('allegropro_order_shipping', 's', 's.checkout_form_id = o.checkout_form_id');
        $q->orderBy('o.updated_at_allegro DESC');
        $q->limit($limit, $offset);
        return Db::getInstance()->executeS($q);
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

    public function updatePsOrderId($checkoutFormId, $psOrderId)
    {
        Db::getInstance()->update('allegropro_order', ['id_order_prestashop' => (int)$psOrderId], "checkout_form_id = '" . pSQL($checkoutFormId) . "'");
    }

    public function markShipment(int $accountId, string $checkoutFormId, ?string $shipmentId, string $commandId)
    {
        // Placeholder
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
                'pickup_point_id' => pSQL($del['pickupPoint']['id'] ?? null),
                'pickup_point_name' => pSQL($del['pickupPoint']['name'] ?? null),
            ]);
        }

        // 4. PAYMENT
        if (!empty($data['payment'])) {
            $pay = $data['payment'];
            $db->insert('allegropro_order_payment', [
                'checkout_form_id' => $cfIdEsc,
                'payment_id' => pSQL($pay['id'] ?? ''),
                'paid_amount' => (float)($pay['paidAmount']['amount'] ?? 0),
            ]);
        }
    }
}
