<?php
namespace AllegroPro\Service;

use AllegroPro\Repository\OrderRepository;
use AllegroPro\Service\AllegroApiClient;
use Db;
use Product;
use Validate;

class OrderFetcher
{
    private $api;
    private $repo;
    private const PARAM_EAN_ID = '225693';

    public function __construct(AllegroApiClient $api, OrderRepository $repo)
    {
        $this->api = $api;
        $this->repo = $repo;
    }

    /**
     * INTELIGENTNE POBIERANIE (INCREMENTAL FETCH) PER KONTO
     * 1) Pobiera najnowsze (od ostatniej daty w bazie)
     * 2) Robi backfill starszych luk (zamówień niepobranych wcześniej)
     */
    public function fetchRecent(array $account, int $limit = 50): array
    {
        $limit = max(1, (int)$limit);

        $params = [
            'limit' => $limit,
            'sort' => '-updatedAt',
        ];

        $accountId = (int)($account['id_allegropro_account'] ?? 0);
        $lastDate = $accountId > 0 ? $this->repo->getLastFetchedDateForAccount($accountId) : null;

        if ($lastDate) {
            $isoDate = date('Y-m-d\TH:i:s\Z', strtotime($lastDate));
            $params['updatedAt.gte'] = $isoDate;
        }

        $recentResult = $this->performFetch($account, $params, true);

        // Backfill: sprawdź starsze strony i uzupełnij brakujące zamówienia
        // (nie nadpisujemy hurtowo już pobranych, szukamy realnych luk)
        $backfillResult = $this->performBackfillFetch($account, $limit);

        $allIds = array_values(array_unique(array_merge(
            $recentResult['fetched_ids'] ?? [],
            $backfillResult['fetched_ids'] ?? []
        )));

        return [
            'fetched_count' => (int)$recentResult['fetched_count'] + (int)$backfillResult['fetched_count'],
            'raw_count' => (int)$recentResult['raw_count'] + (int)$backfillResult['raw_count'],
            'fetched_ids' => $allIds,
            'recent_fetched_count' => (int)$recentResult['fetched_count'],
            'backfill_fetched_count' => (int)$backfillResult['fetched_count'],
        ];
    }

    /**
     * Prawdziwe pobieranie historyczne (wg zakresu dat)
     */
    public function fetchHistory(array $account, string $dateFrom, string $dateTo, int $limit = 100): array
    {
        return $this->performFetch($account, [
            'limit' => $limit,
            'sort' => '-updatedAt',
            'updatedAt.gte' => $dateFrom . 'T00:00:00Z',
            'updatedAt.lte' => $dateTo . 'T23:59:59Z',
        ], false);
    }

    private function performFetch(array $account, array $params, bool $skipExisting = false): array
    {
        $resp = $this->api->get($account, '/order/checkout-forms', $params);

        if (!$resp['ok']) {
            throw new \Exception("Błąd API Allegro: " . $resp['code']);
        }

        $orders = $resp['json']['checkoutForms'] ?? [];
        $count = 0;
        $fetchedIds = [];

        foreach ($orders as $order) {
            if (!is_array($order) || empty($order['id'])) {
                continue;
            }

            if ($skipExisting && $this->repo->existsForAccount((int)$account['id_allegropro_account'], (string)$order['id'])) {
                continue;
            }

            $prepared = $this->prepareOrder($account, $order);
            $this->repo->saveFullOrder($prepared);

            $fetchedIds[] = (string)$prepared['id'];
            $count++;
        }

        return [
            'fetched_count' => $count,
            'raw_count' => count($orders),
            'fetched_ids' => $fetchedIds,
        ];
    }

    /**
     * Dodatkowe skanowanie starszych stron, aby uzupełnić luki
     * (zamówienia starsze, których nie ma lokalnie).
     */
    private function performBackfillFetch(array $account, int $limit): array
    {
        $pageLimit = min(100, max(20, $limit));
        $maxPages = 10;

        $fetchedCount = 0;
        $rawCount = 0;
        $fetchedIds = [];
        $consecutivePagesWithoutMissing = 0;

        for ($page = 0; $page < $maxPages; $page++) {
            $offset = $page * $pageLimit;
            $resp = $this->api->get($account, '/order/checkout-forms', [
                'limit' => $pageLimit,
                'offset' => $offset,
                'sort' => '-updatedAt',
            ]);

            if (!$resp['ok']) {
                break;
            }

            $orders = $resp['json']['checkoutForms'] ?? [];
            if (empty($orders) || !is_array($orders)) {
                break;
            }

            $rawCount += count($orders);
            $pageFetched = 0;

            foreach ($orders as $order) {
                if (!is_array($order) || empty($order['id'])) {
                    continue;
                }

                if ($this->repo->existsForAccount((int)$account['id_allegropro_account'], (string)$order['id'])) {
                    continue;
                }

                $prepared = $this->prepareOrder($account, $order);
                $this->repo->saveFullOrder($prepared);

                $fetchedIds[] = (string)$prepared['id'];
                $fetchedCount++;
                $pageFetched++;
            }

            if ($pageFetched === 0) {
                $consecutivePagesWithoutMissing++;
            } else {
                $consecutivePagesWithoutMissing = 0;
            }

            // Jeśli kolejne strony nie wnoszą braków, kończymy szybciej.
            if ($consecutivePagesWithoutMissing >= 2) {
                break;
            }
        }

        return [
            'fetched_count' => $fetchedCount,
            'raw_count' => $rawCount,
            'fetched_ids' => $fetchedIds,
        ];
    }

    private function prepareOrder(array $account, array $order): array
    {
        $order['account_id'] = $account['id_allegropro_account'];

        if (!empty($order['lineItems']) && is_array($order['lineItems'])) {
            foreach ($order['lineItems'] as &$item) {
                // EAN
                $ean = $item['offer']['ean'] ?? null;
                if (empty($ean) && !empty($item['offer']['id'])) {
                    $ean = $this->fetchEanFromOfferDetails($account, $item['offer']['id']);
                }
                if ($ean) {
                    $ean = preg_replace('/[^0-9]/', '', $ean);
                }
                $item['mapped_ean'] = $ean;

                // Match SKU
                $match = $this->matchProduct($account, $item);
                if ($match) {
                    $item['matched_id_product'] = (int)$match['id_product'];
                    $item['matched_id_attribute'] = (int)$match['id_product_attribute'];
                    $item['matched_tax_rate'] = $this->getTaxRate($match['id_product']);
                } else {
                    $item['matched_id_product'] = 0;
                    $item['matched_id_attribute'] = 0;
                    $item['matched_tax_rate'] = 0;
                }
            }
            unset($item);
        }

        return $order;
    }

    // --- Helpery ---
    private function fetchEanFromOfferDetails(array $account, string $offerId): ?string
    {
        $resp = $this->api->get($account, '/sale/product-offers/' . $offerId);
        if (!$resp['ok']) return null;
        $data = $resp['json'];
        if (!empty($data['productSet']) && is_array($data['productSet'])) {
            foreach ($data['productSet'] as $productEntry) {
                if (!empty($productEntry['product']['parameters'])) {
                    foreach ($productEntry['product']['parameters'] as $param) {
                        if (isset($param['id']) && (string)$param['id'] === self::PARAM_EAN_ID) {
                            if (!empty($param['values'][0])) return (string)$param['values'][0];
                        }
                    }
                }
            }
        }
        return null;
    }

    private function matchProduct($account, $item)
    {
        $sku = isset($item['offer']['external']['id']) ? trim($item['offer']['external']['id']) : null;
        if (empty($sku)) return null;
        $sql = "SELECT pa.id_product, pa.id_product_attribute FROM "._DB_PREFIX_."product_attribute pa LEFT JOIN "._DB_PREFIX_."product p ON p.id_product = pa.id_product WHERE pa.reference = '".pSQL($sku)."' ORDER BY p.active DESC";
        $row = Db::getInstance()->getRow($sql);
        if ($row) return $row;
        $sql = "SELECT p.id_product, 0 as id_product_attribute FROM "._DB_PREFIX_."product p WHERE p.reference = '".pSQL($sku)."' ORDER BY p.active DESC";
        $row = Db::getInstance()->getRow($sql);
        if ($row) return $row;
        return null;
    }

    private function getTaxRate($id_product)
    {
        $product = new Product($id_product);
        return (Validate::isLoadedObject($product)) ? $product->getTaxesRate() : 0.00;
    }
}
