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
     * @return array{ok:bool,code:int,got:int,inserted:int,updated:int,next_offset:int,done:bool,totalCount?:int}
     */
    public function syncRangeStep(array $account, string $occurredAtGte, string $occurredAtLte, int $offset, int $limit = 100, array &$debug = [], bool $forceUpdateAll = false): array
    {
        // Allegro API: limit dla /billing/billing-entries ma max 100 (większe wartości kończą się 422).
        $limit = max(1, min(100, (int)$limit));
        $offset = max(0, (int)$offset);

        $accountId = (int)($account['id_allegropro_account'] ?? 0);
        if (!$accountId) {
            return ['ok' => false, 'code' => 0, 'got' => 0, 'inserted' => 0, 'updated' => 0, 'next_offset' => $offset, 'done' => true, 'totalCount' => 0];
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
            $code = (int)($resp['code'] ?? 0);
            // Allegro API potrafi zwrócić 422 gdy offset wychodzi poza zakres (np. offset == totalCount).
            // W takiej sytuacji traktujemy to jako koniec listy, a nie błąd synchronizacji.
            if ($code === 422 && $offset > 0) {
                $debug[] = sprintf('[BILLING] offset_out_of_range: offset=%d limit=%d (treat as done)', $offset, $limit);
                return ['ok' => true, 'code' => 200, 'got' => 0, 'inserted' => 0, 'updated' => 0, 'next_offset' => $offset, 'done' => true, 'totalCount' => 0, 'raw' => (string)($resp['raw'] ?? '')];
            }
            return ['ok' => false, 'code' => $code, 'got' => 0, 'inserted' => 0, 'updated' => 0, 'next_offset' => $offset, 'done' => true, 'totalCount' => 0, 'raw' => (string)($resp['raw'] ?? '')];
        }

        $list = $resp['json']['billingEntries'] ?? [];
        if (!is_array($list) || empty($list)) {
            return ['ok' => true, 'code' => 200, 'got' => 0, 'inserted' => 0, 'updated' => 0, 'next_offset' => $offset, 'done' => true, 'totalCount' => 0];
        }

        // totalCount/total z API pozwala nam nie wykonywać „pustego” wywołania z offset==totalCount (które może dać 422).
        $totalCount = null;
        foreach (['totalCount', 'total', 'totalElements', 'total_count'] as $k) {
            if (isset($resp['json'][$k])) {
                $totalCount = (int)$resp['json'][$k];
                break;
            }
        }

        $res = $this->repo->upsertEntries($accountId, $list, $forceUpdateAll);

        $got = count($list);
        $nextOffset = $offset + $got;
        $done = $got < $limit;
        if ($totalCount !== null && $totalCount >= 0 && $nextOffset >= $totalCount) {
            $done = true;
        }

        return [
            'ok' => true,
            'code' => 200,
            'got' => $got,
            'inserted' => (int)($res['inserted'] ?? 0),
            'updated' => (int)($res['updated'] ?? 0),
            'next_offset' => $nextOffset,
            'done' => $done,
            'totalCount' => ($totalCount !== null ? (int)$totalCount : 0),
        ];
    }
}
