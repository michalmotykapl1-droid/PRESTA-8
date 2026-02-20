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
     * Pełna synchronizacja (legacy) — używana jako fallback.
     *
     * @param array $account row from allegropro_account
     * @param string $occurredAtGte ISO8601 Z
     * @param string $occurredAtLte ISO8601 Z
     * @param array $debug
     * @param bool $forceUpdateAll Gdy TRUE: wymuś aktualizację wszystkich wpisów w DB (wolniej)
     * @return array{ok:bool,code:int,total:int,inserted:int,updated:int}
     */
    public function syncRange(array $account, string $occurredAtGte, string $occurredAtLte, array &$debug = [], bool $forceUpdateAll = false): array
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
            $step = $this->syncRangeStep($account, $occurredAtGte, $occurredAtLte, $offset, $limit, $debug, $forceUpdateAll);
            if (empty($step['ok'])) {
                return ['ok' => false, 'code' => (int)($step['code'] ?? 0), 'total' => $total, 'inserted' => $inserted, 'updated' => $updated];
            }

            $total += (int)($step['got'] ?? 0);
            $inserted += (int)($step['inserted'] ?? 0);
            $updated += (int)($step['updated'] ?? 0);

            if (!empty($step['done'])) {
                break;
            }
            $offset = (int)($step['next_offset'] ?? ($offset + $limit));
        }

        return ['ok' => true, 'code' => 200, 'total' => $total, 'inserted' => $inserted, 'updated' => $updated];
    }

    /**
     * Synchronizacja krokowa (1 zapytanie do API na wywołanie) — do paska postępu / throttlingu.
     *
     * @param array $account
     * @param string $occurredAtGte ISO8601 Z
     * @param string $occurredAtLte ISO8601 Z
     * @param int $offset
     * @param int $limit
     * @param array $debug
     * @param bool $forceUpdateAll Gdy TRUE: aktualizuje każdy istniejący wpis (wolniej). Gdy FALSE: dogrywa tylko braki.
     * @return array{ok:bool,code:int,got:int,inserted:int,updated:int,next_offset:int,done:bool}
     */
    public function syncRangeStep(array $account, string $occurredAtGte, string $occurredAtLte, int $offset, int $limit = 100, array &$debug = [], bool $forceUpdateAll = false): array
    {
        $limit = max(1, min(200, (int)$limit));
        $offset = max(0, (int)$offset);

        $accountId = (int)($account['id_allegropro_account'] ?? 0);
        if (!$accountId) {
            return ['ok' => false, 'code' => 0, 'got' => 0, 'inserted' => 0, 'updated' => 0, 'next_offset' => $offset, 'done' => true];
        }

        $query = [
            'occurredAt.gte' => $occurredAtGte,
            'occurredAt.lte' => $occurredAtLte,
            'limit' => $limit,
            'offset' => $offset,
        ];

        $resp = $this->api->get($account, '/billing/billing-entries', $query);
        $debug[] = sprintf('[BILLING] GET /billing/billing-entries offset=%d limit=%d: HTTP %d ok=%d', $offset, $limit, (int)$resp['code'], $resp['ok'] ? 1 : 0);

        if (empty($resp['ok']) || !is_array($resp['json'])) {
            return ['ok' => false, 'code' => (int)($resp['code'] ?? 0), 'got' => 0, 'inserted' => 0, 'updated' => 0, 'next_offset' => $offset, 'done' => true];
        }

        $list = $resp['json']['billingEntries'] ?? [];
        if (!is_array($list) || empty($list)) {
            return ['ok' => true, 'code' => 200, 'got' => 0, 'inserted' => 0, 'updated' => 0, 'next_offset' => $offset, 'done' => true];
        }

        $res = $this->repo->upsertEntries($accountId, $list, $forceUpdateAll);

        $got = count($list);
        $done = $got < $limit;

        return [
            'ok' => true,
            'code' => 200,
            'got' => $got,
            'inserted' => (int)($res['inserted'] ?? 0),
            'updated' => (int)($res['updated'] ?? 0),
            'next_offset' => $offset + $limit,
            'done' => $done,
        ];
    }
}
