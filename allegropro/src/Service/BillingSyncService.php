<?php
namespace AllegroPro\Service;

use AllegroPro\Repository\BillingEntryRepository;

class BillingSyncService
{
    private AllegroApiClient $api;
    private BillingEntryRepository $repo;

    public function __construct(AllegroApiClient $api, BillingEntryRepository $repo)
    {
        $this->api = $api;
        $this->repo = $repo;
    }

    /**
     * @param array $account row from allegropro_account
     * @param string $occurredAtGte ISO8601 Z
     * @param string $occurredAtLte ISO8601 Z
     * @param array $debug
     * @return array{ok:bool,code:int,total:int,inserted:int,updated:int}
     */
    public function syncRange(array $account, string $occurredAtGte, string $occurredAtLte, array &$debug = []): array
    {
        $limit = 100;
        $offset = 0;
        $total = 0;
        $inserted = 0;
        $updated = 0;

        $accountId = (int)($account['id_allegropro_account'] ?? 0);
        if (!$accountId) {
            return ['ok' => false, 'code' => 0, 'total' => 0, 'inserted' => 0, 'updated' => 0];
        }

        for ($page = 1; $page <= 200; $page++) {
            $query = [
                'occurredAt.gte' => $occurredAtGte,
                'occurredAt.lte' => $occurredAtLte,
                'limit' => $limit,
                'offset' => $offset,
            ];

            $resp = $this->api->get($account, '/billing/billing-entries', $query);
            $debug[] = sprintf('[BILLING] GET /billing/billing-entries page=%d offset=%d: HTTP %d ok=%d', $page, $offset, (int)$resp['code'], $resp['ok'] ? 1 : 0);

            if (empty($resp['ok']) || !is_array($resp['json'])) {
                return ['ok' => false, 'code' => (int)($resp['code'] ?? 0), 'total' => $total, 'inserted' => $inserted, 'updated' => $updated];
            }

            $list = $resp['json']['billingEntries'] ?? [];
            if (!is_array($list) || empty($list)) {
                break;
            }

            $total += count($list);
            $res = $this->repo->upsertEntries($accountId, $list);
            $inserted += (int)($res['inserted'] ?? 0);
            $updated += (int)($res['updated'] ?? 0);

            if (count($list) < $limit) {
                break;
            }
            $offset += $limit;
        }

        return ['ok' => true, 'code' => 200, 'total' => $total, 'inserted' => $inserted, 'updated' => $updated];
    }
}
