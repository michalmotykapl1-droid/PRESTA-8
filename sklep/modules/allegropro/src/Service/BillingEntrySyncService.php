<?php
namespace AllegroPro\Service;

use AllegroPro\Repository\BillingEntryRepository;

/**
 * Chunked synchronizacja cache billing-entries (GET /billing/billing-entries).
 *
 * Tryby:
 *  - fill: NOWE + uzupełnij brakujące dni (w obrębie wybranego zakresu)
 *  - full: pełne pobranie zakresu (wolniej)
 *
 * Zwraca "state" (base64 JSON) kompatybilny z JS cashflows.js (modal postępu).
 */
class BillingEntrySyncService
{
    private AllegroApiClient $api;
    private BillingEntryRepository $repo;
    private BillingSyncService $sync;

    public function __construct(AllegroApiClient $api, BillingEntryRepository $repo)
    {
        $this->api = $api;
        $this->repo = $repo;
        $this->sync = new BillingSyncService($api, $repo);
    }

    /**
     * Jeden krok synchronizacji (1 request do API).
     *
     * @return array<string,mixed>
     */
    public function syncChunk(
        array $account,
        string $dateFrom,
        string $dateTo,
        string $mode,
        string $stateB64 = '',
        int $chunkLimit = 100
    ): array {
        $accountId = (int)($account['id_allegropro_account'] ?? 0);
        if ($accountId <= 0) {
            return ['ok' => false, 'error' => 'Brak id konta.'];
        }
        if (empty($account['access_token'])) {
            return ['ok' => false, 'error' => 'Brak tokena konta (access_token).'];
        }

        // tabela cache
        $this->repo->ensureSchema();

        $mode = in_array($mode, ['fill', 'full'], true) ? $mode : 'fill';
        // Allegro API: /billing/billing-entries ma limit max 100.
        $chunkLimit = max(20, min(100, (int)$chunkLimit));

        $state = $this->decodeState($stateB64);
        if (!is_array($state) || empty($state['ranges']) || (string)($state['mode'] ?? '') !== $mode) {
            $state = $this->buildInitialState($accountId, $dateFrom, $dateTo, $mode);
        }

        $ranges = (array)($state['ranges'] ?? []);
        $idx = (int)($state['idx'] ?? 0);
        $offset = (int)($state['offset'] ?? 0);
        $totalRanges = count($ranges);

        if ($idx >= $totalRanges) {
            $state['done'] = true;
            return $this->buildChunkResponse($state, true, 100);
        }

        $range = (array)($ranges[$idx] ?? []);
        $from = (string)($range['from'] ?? $dateFrom);
        $to = (string)($range['to'] ?? $dateTo);
        $kind = (string)($range['kind'] ?? 'range');

        $debug = [];
        $step = $this->sync->syncRangeStep(
            $account,
            $this->ymdToIsoStart($from),
            $this->ymdToIsoEnd($to),
            $offset,
            $chunkLimit,
            $debug,
            false
        );

        if (empty($step['ok'])) {
            return [
                'ok' => false,
                'error' => 'Błąd Allegro API podczas synchronizacji billing-entries.',
                'http' => (int)($step['code'] ?? 0),
                // Ułatwia diagnostykę (JS cashflows.js spróbuje sparsować RFC7807 problem+json).
                'raw' => (string)($step['raw'] ?? ''),
                'debug_tail' => array_slice($debug, -8),
            ];
        }

        $gotNow = (int)($step['got'] ?? 0);
        $storedNow = (int)($step['inserted'] ?? 0) + (int)($step['updated'] ?? 0);
        $nextOffset = (int)($step['next_offset'] ?? ($offset + $gotNow));
        $rangeDone = !empty($step['done']);
        $totalCount = (int)($step['totalCount'] ?? 0);

        // update totals
        $state['fetched'] = (int)($state['fetched'] ?? 0) + $gotNow;
        $state['stored'] = (int)($state['stored'] ?? 0) + $storedNow;
        $state['pages'] = (int)($state['pages'] ?? 0) + 1;
        $state['current_total'] = $totalCount;
        $state['offset'] = $nextOffset;

        if ($rangeDone) {
            if ($kind === 'fill_day' && $gotNow > 0) {
                $state['filled_days'] = (int)($state['filled_days'] ?? 0) + 1;
            }
            $state['idx'] = $idx + 1;
            $state['offset'] = 0;
            $state['current_total'] = 0;
        }

        $idx2 = (int)($state['idx'] ?? 0);
        $done = ($idx2 >= $totalRanges);

        // progress (przybliżony)
        $part = 1.0;
        $curTotal = (int)($state['current_total'] ?? 0);
        if (!$done && $curTotal > 0) {
            $part = min(1.0, ((int)($state['offset'] ?? 0)) / $curTotal);
        } elseif (!$done) {
            $part = 0.0;
        }
        $progress = ($totalRanges > 0) ? (($idx2 + $part) / $totalRanges) : 1.0;
        $percent = (int)max(0, min(100, floor($progress * 100)));

        if ($done) {
            $state['done'] = true;
            $state['duration_ms'] = (int)round((microtime(true) - (float)($state['t0'] ?? microtime(true))) * 1000);
            $percent = 100;
        }

        $r = $this->buildChunkResponse($state, $done, $percent);
        $r['chunk'] = [
            'fetched' => $gotNow,
            'stored' => $storedNow,
            'range_from' => $from,
            'range_to' => $to,
            'offset' => (int)($state['offset'] ?? 0),
            'totalCount' => $totalCount,
        ];
        return $r;
    }

    private function buildInitialState(int $accountId, string $dateFrom, string $dateTo, string $mode): array
    {
        $ranges = [];

        if ($mode === 'full') {
            $ranges[] = ['from' => $dateFrom, 'to' => $dateTo, 'kind' => 'range'];
        } else {
            // "NOWE": od ostatniego occurred_at w tym zakresie (z buforem -1 dzień) do dateTo
            $max = $this->repo->getMaxOccurredAtInRange($accountId, $dateFrom, $dateTo);
            $newFrom = $dateFrom;
            if ($max) {
                $maxYmd = substr((string)$max, 0, 10);
                $bufYmd = date('Y-m-d', strtotime($maxYmd . ' -1 day'));
                if (strtotime($bufYmd) > strtotime($newFrom)) {
                    $newFrom = $bufYmd;
                }
            }
            if (strtotime($newFrom) <= strtotime($dateTo)) {
                $ranges[] = ['from' => $newFrom, 'to' => $dateTo, 'kind' => 'range'];
            }

            // "UZUPEŁNIJ BRAKI": dni bez żadnego wpisu w cache
            foreach ($this->iterateDays($dateFrom, $dateTo) as $ymd) {
                if ($this->repo->countInRange($accountId, $ymd, $ymd) > 0) {
                    continue;
                }
                $ranges[] = ['from' => $ymd, 'to' => $ymd, 'kind' => 'fill_day'];
            }
        }

        return [
            'ok' => true,
            'mode' => $mode,
            'ranges' => $ranges,
            'idx' => 0,
            'offset' => 0,
            'current_total' => 0,
            'fetched' => 0,
            'stored' => 0,
            'pages' => 0,
            'filled_days' => 0,
            't0' => microtime(true),
            'done' => false,
        ];
    }

    private function buildChunkResponse(array $state, bool $done, int $percent): array
    {
        $ranges = (array)($state['ranges'] ?? []);
        $idx = (int)($state['idx'] ?? 0);
        $totalRanges = count($ranges);
        $stateB64 = base64_encode(json_encode($state, JSON_UNESCAPED_UNICODE));

        return [
            'ok' => true,
            'done' => $done,
            'percent' => $percent,
            'mode' => (string)($state['mode'] ?? ''),
            'fetched' => (int)($state['fetched'] ?? 0),
            'stored' => (int)($state['stored'] ?? 0),
            'pages' => (int)($state['pages'] ?? 0),
            'filled_days' => (int)($state['filled_days'] ?? 0),
            'range_index' => $idx,
            'range_total' => $totalRanges,
            'state' => $stateB64,
            'duration_ms' => (int)($state['duration_ms'] ?? 0),
        ];
    }

    private function decodeState(string $stateB64): ?array
    {
        $stateB64 = trim($stateB64);
        if ($stateB64 === '') {
            return null;
        }
        $raw = base64_decode($stateB64, true);
        if ($raw === false || $raw === '') {
            return null;
        }
        $j = json_decode($raw, true);
        return is_array($j) ? $j : null;
    }

    /**
     * @return array<int,string>
     */
    private function iterateDays(string $from, string $to): array
    {
        $out = [];
        $tsFrom = strtotime($from);
        $tsTo = strtotime($to);
        if ($tsFrom === false || $tsTo === false) {
            return $out;
        }
        if ($tsFrom > $tsTo) {
            [$tsFrom, $tsTo] = [$tsTo, $tsFrom];
        }
        for ($t = $tsFrom; $t <= $tsTo; $t = strtotime('+1 day', $t)) {
            $out[] = date('Y-m-d', $t);
            if (count($out) > 400) {
                break; // bezpiecznik
            }
        }
        return $out;
    }

    private function ymdToIsoStart(string $ymd): string
    {
        return $ymd . 'T00:00:00.000Z';
    }

    private function ymdToIsoEnd(string $ymd): string
    {
        return $ymd . 'T23:59:59.999Z';
    }
}
