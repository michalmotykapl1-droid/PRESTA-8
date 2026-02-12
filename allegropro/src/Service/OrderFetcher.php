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
     * INTELIGENTNE POBIERANIE (INCREMENTAL FETCH)
     * Pobiera tylko zamówienia nowsze niż ostatnie w bazie.
     */
    public function fetchRecent(array $account, int $limit = 50): array
    {
        $params = [
            'limit' => $limit,
            'sort' => '-updatedAt' // Najnowsze na górze
        ];

        // 1. Sprawdź datę ostatniego zamówienia w bazie
        $lastDate = $this->repo->getLastFetchedDate((int)$account['id_allegropro_account']);

        if ($lastDate) {
            // Allegro API wymaga formatu ISO 8601 (np. 2024-01-20T10:00:00Z)
            // Zamieniamy datę z bazy na format akceptowalny przez API
            $isoDate = date('Y-m-d\TH:i:s\Z', strtotime($lastDate));
            
            // Dodajemy filtr: "Pobierz tylko zaktualizowane PO tej dacie"
            $params['updatedAt.gte'] = $isoDate;
        }

        return $this->performFetch($account, $params);
    }

    /**
     * Pobieranie historyczne (wg zakresu dat)
     */
    public function fetchHistory(array $account, string $dateFrom, string $dateTo, int $limit = 100): array
    {
        return $this->performFetch($account, [
            'limit' => $limit,
            'sort' => '-updatedAt',
            'updatedAt.gte' => $dateFrom . 'T00:00:00Z',
            'updatedAt.lte' => $dateTo . 'T23:59:59Z'
        ]);
    }

    private function performFetch(array $account, array $params): array
    {
        $resp = $this->api->get($account, '/order/checkout-forms', $params);

        if (!$resp['ok']) {
            throw new \Exception("Błąd API Allegro: " . $resp['code']);
        }

        $orders = $resp['json']['checkoutForms'] ?? [];
        $count = 0;

        foreach ($orders as $order) {
            $order['account_id'] = $account['id_allegropro_account'];
            
            foreach ($order['lineItems'] as &$item) {
                // EAN
                $ean = $item['offer']['ean'] ?? null;
                if (empty($ean) && !empty($item['offer']['id'])) {
                    $ean = $this->fetchEanFromOfferDetails($account, $item['offer']['id']);
                }
                if ($ean) $ean = preg_replace('/[^0-9]/', '', $ean);
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

            $this->repo->saveFullOrder($order);
            $count++;
        }

        return [
            'fetched_count' => $count,
            'raw_count' => count($orders)
        ];
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
