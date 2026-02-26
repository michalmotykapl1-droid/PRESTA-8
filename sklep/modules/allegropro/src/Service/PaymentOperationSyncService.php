<?php
namespace AllegroPro\Service;

use AllegroPro\Repository\PaymentOperationRepository;

/**
 * Synchronizacja cache dla Allegro Finanse (payments/payment-operations).
 *
 * Tryby:
 *  - fill: NOWE + uzupełnij brakujące dni (gdy cache ma „dziury”)
 *  - full: PEŁNE pobranie zakresu dat (wolniej)
 */
class PaymentOperationSyncService
{
    private AllegroApiClient $api;
    private PaymentOperationRepository $repo;

    public function __construct(AllegroApiClient $api, PaymentOperationRepository $repo)
    {
        $this->api = $api;
        $this->repo = $repo;
    }

    public function sync(array $account, string $dateFrom, string $dateTo, string $mode = 'fill', bool $debug = false): array
    {
        $t0 = microtime(true);

        $accountId = (int)($account['id_allegropro_account'] ?? 0);
        if ($accountId <= 0) {
            return ['ok' => false, 'error' => 'Brak id konta.'];
        }
        if (empty($account['access_token'])) {
            return ['ok' => false, 'error' => 'Brak tokena konta (access_token).'];
        }
        if (!$this->repo->ensureSchema()) {
            return ['ok' => false, 'error' => 'Nie udało się utworzyć tabeli cache (allegropro_payment_operation).'];
        }

        $mode = in_array($mode, ['fill', 'full'], true) ? $mode : 'fill';

        $stats = [
            'ok' => true,
            'mode' => $mode,
            'range_from' => $dateFrom,
            'range_to' => $dateTo,
            'fetched' => 0,
            'stored' => 0,
            'pages' => 0,
            'filled_days' => 0,
            'debug_lines' => [],
        ];

        if ($mode === 'full') {
            $r = $this->fetchAndStoreRange($account, $dateFrom, $dateTo, $debug);
            if (empty($r['ok'])) {
                return $r;
            }
            $stats['fetched'] += (int)$r['fetched'];
            $stats['stored'] += (int)$r['stored'];
            $stats['pages'] += (int)$r['pages'];
            $stats['debug_lines'] = array_merge($stats['debug_lines'], $r['debug_lines'] ?? []);
        } else {
            // 1) NOWE: pobierz od ostatniego occurred_at (z buforem -1 dzień) do dateTo
            $max = $this->repo->getMaxOccurredAt($accountId);
            $newFrom = $dateFrom;
            if ($max) {
                $maxYmd = substr((string)$max, 0, 10);
                $bufYmd = date('Y-m-d', strtotime($maxYmd . ' -1 day'));
                // startujemy od max(dateFrom, bufor)
                if (strtotime($bufYmd) > strtotime($newFrom)) {
                    $newFrom = $bufYmd;
                }
            }
            if (strtotime($newFrom) <= strtotime($dateTo)) {
                $r = $this->fetchAndStoreRange($account, $newFrom, $dateTo, $debug);
                if (empty($r['ok'])) {
                    return $r;
                }
                $stats['fetched'] += (int)$r['fetched'];
                $stats['stored'] += (int)$r['stored'];
                $stats['pages'] += (int)$r['pages'];
                $stats['debug_lines'] = array_merge($stats['debug_lines'], $r['debug_lines'] ?? []);
            }

            // 2) UZUPEŁNIJ BRAKI: jeśli są dni bez żadnego wpisu w cache
            foreach ($this->iterateDays($dateFrom, $dateTo) as $ymd) {
                if ($this->repo->countForDay($accountId, $ymd) > 0) {
                    continue;
                }
                $r = $this->fetchAndStoreRange($account, $ymd, $ymd, $debug);
                if (empty($r['ok'])) {
                    return $r;
                }
                if ((int)$r['fetched'] > 0) {
                    $stats['filled_days']++;
                }
                $stats['fetched'] += (int)$r['fetched'];
                $stats['stored'] += (int)$r['stored'];
                $stats['pages'] += (int)$r['pages'];
                $stats['debug_lines'] = array_merge($stats['debug_lines'], $r['debug_lines'] ?? []);
            }
        }

        $stats['duration_ms'] = (int)round((microtime(true) - $t0) * 1000);
        return $stats;
    }

    /**
     * Chunked sync: jeden krok (jedna partia) do użycia w UI z paskiem postępu.
     * Zwraca "state" (base64 JSON), który należy przesyłać w kolejnych wywołaniach.
     */
    public function syncChunk(array $account, string $dateFrom, string $dateTo, string $mode, string $stateB64 = '', int $chunkLimit = 200): array
    {
        $accountId = (int)($account['id_allegropro_account'] ?? 0);
        if ($accountId <= 0) {
            return ['ok' => false, 'error' => 'Brak id konta.'];
        }
        if (empty($account['access_token'])) {
            return ['ok' => false, 'error' => 'Brak tokena konta (access_token).'];
        }
        if (!$this->repo->ensureSchema()) {
            return ['ok' => false, 'error' => 'Nie udało się utworzyć tabeli cache (allegropro_payment_operation).'];
        }

        $mode = in_array($mode, ['fill', 'full'], true) ? $mode : 'fill';
        // Allegro payment-operations: bezpieczny limit per page.
        // (W praktyce API potrafi zwracać 400 gdy limit jest zbyt wysoki.)
        $chunkLimit = max(20, min(100, (int)$chunkLimit));

        $state = $this->decodeState($stateB64);
        if (!is_array($state) || empty($state['ranges']) || (string)($state['mode'] ?? '') !== $mode) {
            $state = $this->buildInitialState($accountId, $dateFrom, $dateTo, $mode);
        }

        $ranges = $state['ranges'];
        $idx = (int)($state['idx'] ?? 0);
        $offset = (int)($state['offset'] ?? 0);
        $totalRanges = count($ranges);

        if ($idx >= $totalRanges) {
            $state['done'] = true;
            return $this->buildChunkResponse($state, true, 100);
        }

        $range = $ranges[$idx];
        $from = (string)($range['from'] ?? $dateFrom);
        $to = (string)($range['to'] ?? $dateTo);
        $kind = (string)($range['kind'] ?? 'range');

        $query = [
            'occurredAt.gte' => $this->ymdToIsoStart($from),
            'occurredAt.lte' => $this->ymdToIsoEnd($to),
            'limit' => $chunkLimit,
            'offset' => $offset,
        ];

        $resp = $this->api->get($account, '/payments/payment-operations', $query);
        if (empty($resp['ok']) || !is_array($resp['json'])) {
            return [
                'ok' => false,
                'error' => 'Błąd Allegro API podczas synchronizacji payment-operations.',
                'http' => (int)($resp['code'] ?? 0),
                'raw' => (string)($resp['raw'] ?? ''),
            ];
        }

        $list = $resp['json']['paymentOperations'] ?? [];
        if (!is_array($list)) {
            $list = [];
        }

        $norm = [];
        foreach ($list as $op) {
            if (!is_array($op)) {
                continue;
            }
            $norm[] = $this->normalizeApiOp($op);
        }

        $storedNow = 0;
        if (!empty($norm)) {
            $storedNow = (int)$this->repo->upsertMany($accountId, $norm);
        }

        $countNow = count($list);
        $totalCount = (int)($resp['json']['totalCount'] ?? 0);

        // update totals
        $state['fetched'] = (int)($state['fetched'] ?? 0) + $countNow;
        $state['stored'] = (int)($state['stored'] ?? 0) + $storedNow;
        $state['pages'] = (int)($state['pages'] ?? 0) + 1;
        $state['current_total'] = $totalCount;
        $state['offset'] = $offset + $countNow;

        // koniec range?
        $rangeDone = false;

        // Jeżeli Allegro zwróci pustą stronę zanim dojdziemy do totalCount, nie kończymy cicho — to może oznaczać,
        // że coś poszło nie tak (przeskoczony offset / błąd filtrowania) i moglibyśmy pominąć operacje.
        if ($countNow <= 0) {
            if ($totalCount > 0 && $offset < $totalCount) {
                return [
                    'ok' => false,
                    'error' => 'Allegro API zwróciło pustą stronę przed końcem zakresu (offset ' . (int)$offset . ' z ' . (int)$totalCount . ').',
                ];
            }
            $rangeDone = true;
        } elseif ($totalCount > 0 && (int)$state['offset'] >= $totalCount) {
            $rangeDone = true;
        } elseif ($countNow < $chunkLimit) {
            $rangeDone = true;
        }

        if ($rangeDone) {
            if ($kind === 'fill_day' && $countNow > 0) {
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
            'fetched' => $countNow,
            'stored' => $storedNow,
            'range_from' => $from,
            'range_to' => $to,
            // Dla UI chcemy pokazać postęp w aktualnym zakresie (np. 699/699), nawet jeśli wewnętrzny stan
            // zresetował offset do 0 na potrzeby kolejnego zakresu.
            'offset' => ($rangeDone ? $totalCount : (int)($state['offset'] ?? 0)),
            'totalCount' => $totalCount,
            'rangeDone' => (bool)$rangeDone,
        ];
        return $r;
    }

    private function buildInitialState(int $accountId, string $dateFrom, string $dateTo, string $mode): array
    {
        $ranges = [];
        if ($mode === 'full') {
            $ranges[] = ['from' => $dateFrom, 'to' => $dateTo, 'kind' => 'range'];
        } else {
            // NOWE: od ostatniego occurred_at (z buforem -1 dzień) do dateTo
            $max = $this->repo->getMaxOccurredAt($accountId);
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

            // UZUPEŁNIJ BRAKI (dni bez żadnego wpisu)
            foreach ($this->iterateDays($dateFrom, $dateTo) as $ymd) {
                if ($this->repo->countForDay($accountId, $ymd) > 0) {
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

    private function fetchAndStoreRange(array $account, string $dateFrom, string $dateTo, bool $debug): array
    {
        $accountId = (int)($account['id_allegropro_account'] ?? 0);
        $limit = 50;
        $offset = 0;
        $maxPages = 600; // 30k rekordów w zakresie (bezpiecznik)

        $stats = ['ok' => true, 'fetched' => 0, 'stored' => 0, 'pages' => 0, 'debug_lines' => []];

        for ($p = 0; $p < $maxPages; $p++) {
            $query = [
                'occurredAt.gte' => $this->ymdToIsoStart($dateFrom),
                'occurredAt.lte' => $this->ymdToIsoEnd($dateTo),
                'limit' => $limit,
                'offset' => $offset,
            ];

            $resp = $this->api->get($account, '/payments/payment-operations', $query);
            if (empty($resp['ok']) || !is_array($resp['json'])) {
                return [
                    'ok' => false,
                    'error' => 'Błąd Allegro API podczas synchronizacji payment-operations.',
                    'http' => (int)($resp['code'] ?? 0),
                    'raw' => (string)($resp['raw'] ?? ''),
                ];
            }

            $list = $resp['json']['paymentOperations'] ?? [];
            if (!is_array($list) || empty($list)) {
                break;
            }

            $norm = [];
            foreach ($list as $op) {
                if (!is_array($op)) {
                    continue;
                }
                $norm[] = $this->normalizeApiOp($op);
            }

            if (!empty($norm)) {
                $stats['stored'] += $this->repo->upsertMany($accountId, $norm);
            }

            $count = count($list);
            $stats['fetched'] += $count;
            $stats['pages']++;
            $offset += $count;

            $totalCount = (int)($resp['json']['totalCount'] ?? 0);
            if ($totalCount > 0 && $offset >= $totalCount) {
                break;
            }
            if ($count < $limit) {
                break;
            }
        }

        if ($debug) {
            $stats['debug_lines'][] = '[SYNC] range ' . $dateFrom . ' -> ' . $dateTo . ', fetched=' . (int)$stats['fetched'] . ', pages=' . (int)$stats['pages'];
        }

        return $stats;
    }

    private function normalizeApiOp(array $op): array
    {
        $occurredIso = (string)($op['occurredAt'] ?? '');
        $occurredDb = $this->isoToDbDateTime($occurredIso) ?: date('Y-m-d H:i:s');
        $amount = (string)($op['value']['amount'] ?? '0.00');
        // normalize decimal
        $amount = str_replace(',', '.', trim($amount));
        if (!preg_match('/^-?\d+(?:\.\d+)?$/', $amount)) {
            $amount = '0.00';
        }

        $operationId = (string)($op['id'] ?? '');
        if ($operationId === '') {
            // fallback id - stabilny hash (dla przypadków gdy Allegro nie zwróci id)
            $operationId = sha1(json_encode([
                $occurredIso,
                (string)($op['group'] ?? ''),
                (string)($op['type'] ?? ''),
                (string)($op['wallet']['type'] ?? ''),
                (string)($op['wallet']['paymentOperator'] ?? ''),
                $amount,
                (string)($op['payment']['id'] ?? ''),
                (string)($op['participant']['login'] ?? ''),
            ], JSON_UNESCAPED_UNICODE));
        }

        return [
            'operation_id' => $operationId,
            'occurred_at' => $occurredDb,
            'occurred_at_iso' => $occurredIso,
            'op_group' => (string)($op['group'] ?? ''),
            'op_type' => (string)($op['type'] ?? ''),
            'wallet_type' => (string)($op['wallet']['type'] ?? ''),
            'wallet_payment_operator' => (string)($op['wallet']['paymentOperator'] ?? ''),
            'amount' => $amount,
            'currency' => (string)($op['value']['currency'] ?? 'PLN'),
            'payment_id' => (string)($op['payment']['id'] ?? ''),
            'participant_login' => (string)($op['participant']['login'] ?? ''),
            'raw_json' => json_encode($op, JSON_UNESCAPED_UNICODE),
        ];
    }

    private function isoToDbDateTime(string $iso): ?string
    {
        $iso = trim($iso);
        if ($iso === '') {
            return null;
        }
        try {
            $dt = new \DateTime($iso);
            // zapisujemy UTC (DateTime z Z wejdzie jako UTC)
            return $dt->format('Y-m-d H:i:s');
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function ymdToIsoStart(string $ymd): string
    {
        return $ymd . 'T00:00:00.000Z';
    }

    private function ymdToIsoEnd(string $ymd): string
    {
        return $ymd . 'T23:59:59.999Z';
    }

    /** @return \Generator<int,string> */
    private function iterateDays(string $fromYmd, string $toYmd): \Generator
    {
        $start = strtotime($fromYmd . ' 00:00:00');
        $end = strtotime($toYmd . ' 00:00:00');
        if ($start === false || $end === false) {
            return;
        }
        for ($t = $start; $t <= $end; $t += 86400) {
            yield date('Y-m-d', $t);
        }
    }
}
