<?php
namespace AllegroPro\Service;

use AllegroPro\Repository\BillingEntryRepository;
use Db;

/**
 * Chunked synchronizacja /billing/billing-entries z paskiem postępu (modal).
 *
 * Tryby:
 *  - fill: NOWE + uzupełnij brakujące dni (gdy cache ma "dziury")
 *  - full: PEŁNE pobranie zakresu + wymuszenie aktualizacji istniejących wpisów (wolniej)
 */
class BillingEntryChunkSyncService
{
    private AllegroApiClient $api;
    private BillingEntryRepository $repo;

    public function __construct(AllegroApiClient $api, BillingEntryRepository $repo)
    {
        $this->api = $api;
        $this->repo = $repo;
    }

    /**
     * Jeden krok synchronizacji. Zwraca state (base64 JSON) do kolejnego wywołania.
     */
    public function syncChunk(array $account, string $dateFrom, string $dateTo, string $mode, string $stateB64 = '', int $chunkLimit = 100): array
    {
        $accountId = (int)($account['id_allegropro_account'] ?? 0);
        if ($accountId <= 0) {
            return ['ok' => false, 'error' => 'Brak id konta.'];
        }
        if (empty($account['access_token'])) {
            return ['ok' => false, 'error' => 'Brak tokena konta (access_token).'];
        }

        $mode = in_array($mode, ['fill', 'full'], true) ? $mode : 'fill';
        // billing-entries: trzymaj bezpiecznie 100
        $chunkLimit = max(20, min(100, (int)$chunkLimit));

        // Ensure cache table exists.
        try {
            $this->repo->ensureSchema();
        } catch (\Throwable $e) {
            return ['ok' => false, 'error' => 'Nie udało się utworzyć tabeli cache (allegropro_billing_entry).'];
        }

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

        $resp = $this->api->get($account, '/billing/billing-entries', $query);

        if (empty($resp['ok']) || !is_array($resp['json'])) {
            $code = (int)($resp['code'] ?? 0);
            // 422: offset poza zakresem → traktuj jako koniec listy dla tego range.
            if ($code === 422 && $offset > 0) {
                $state['idx'] = $idx + 1;
                $state['offset'] = 0;
                $state['current_total'] = 0;

                $idx2 = (int)($state['idx'] ?? 0);
                $done = ($idx2 >= $totalRanges);
                $percent = $done ? 100 : (int)max(0, min(99, floor((($idx2) / max(1, $totalRanges)) * 100)));

                $r = $this->buildChunkResponse($state, $done, $percent);
                $r['chunk'] = [
                    'fetched' => 0,
                    'stored' => 0,
                    'range_from' => $from,
                    'range_to' => $to,
                    'offset' => 0,
                    'totalCount' => 0,
                ];
                return $r;
            }

            return [
                'ok' => false,
                'error' => 'Błąd Allegro API podczas synchronizacji billing-entries.',
                'http' => $code,
                'raw' => (string)($resp['raw'] ?? ''),
            ];
        }

        $list = $resp['json']['billingEntries'] ?? [];
        if (!is_array($list)) {
            $list = [];
        }

        // totalCount może mieć różne nazwy
        $totalCount = null;
        foreach (['totalCount', 'total', 'totalElements', 'total_count'] as $k) {
            if (isset($resp['json'][$k])) {
                $totalCount = (int)$resp['json'][$k];
                break;
            }
        }

        $storedNow = 0;
        if (!empty($list)) {
            $forceUpdateAll = ($mode === 'full');
            $res = $this->repo->upsertEntries($accountId, $list, $forceUpdateAll);
            $storedNow = (int)($res['inserted'] ?? 0) + (int)($res['updated'] ?? 0);
        }

        $countNow = count($list);

        $state['fetched'] = (int)($state['fetched'] ?? 0) + $countNow;
        $state['stored'] = (int)($state['stored'] ?? 0) + $storedNow;
        $state['pages'] = (int)($state['pages'] ?? 0) + 1;
        $state['current_total'] = ($totalCount !== null) ? (int)$totalCount : 0;
        $state['offset'] = $offset + $countNow;

        $rangeDone = false;
        if ($countNow <= 0) {
            $rangeDone = true;
        } elseif ($totalCount !== null && $totalCount > 0 && (int)$state['offset'] >= $totalCount) {
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
            'offset' => (int)($state['offset'] ?? 0),
            'totalCount' => ($totalCount !== null) ? (int)$totalCount : 0,
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
            $max = $this->getMaxOccurredAt($accountId);
            $newFrom = $dateFrom;
            if ($max) {
                $maxYmd = substr((string)$max, 0, 10);
                $bufYmd = date('Y-m-d', strtotime($maxYmd . ' -1 day'));
                if (strtotime($bufYmd) > strtotime($newFrom)) {
                    $newFrom = $bufYmd;
                }
            }
            if (strtotime($newFrom) <= strtotime($dateTo)) {
                $ranges[] = ['from' => $newFrom, 'to' => $dateTo, 'kind' => 'new_range'];
            }

            // UZUPEŁNIJ BRAKI (dni bez żadnego wpisu)
            foreach ($this->iterateDays($dateFrom, $dateTo) as $ymd) {
                if ($this->countForDay($accountId, $ymd) > 0) {
                    continue;
                }
                $ranges[] = ['from' => $ymd, 'to' => $ymd, 'kind' => 'fill_day'];
            }

            if (empty($ranges)) {
                $ranges[] = ['from' => $dateFrom, 'to' => $dateTo, 'kind' => 'range'];
            }
        }

        return [
            't0' => microtime(true),
            'mode' => $mode,
            'range_from' => $dateFrom,
            'range_to' => $dateTo,
            'ranges' => $ranges,
            'idx' => 0,
            'offset' => 0,
            'fetched' => 0,
            'stored' => 0,
            'pages' => 0,
            'filled_days' => 0,
            'current_total' => 0,
        ];
    }

    private function buildChunkResponse(array $state, bool $done, int $percent): array
    {
        return [
            'ok' => true,
            'done' => $done,
            'percent' => (int)$percent,
            'mode' => (string)($state['mode'] ?? 'fill'),
            'range_from' => (string)($state['range_from'] ?? ''),
            'range_to' => (string)($state['range_to'] ?? ''),
            'range_index' => (int)($state['idx'] ?? 0),
            'range_total' => is_array($state['ranges'] ?? null) ? count($state['ranges']) : 0,
            'fetched' => (int)($state['fetched'] ?? 0),
            'stored' => (int)($state['stored'] ?? 0),
            'pages' => (int)($state['pages'] ?? 0),
            'filled_days' => (int)($state['filled_days'] ?? 0),
            'duration_ms' => (int)($state['duration_ms'] ?? 0),
            'state' => $this->encodeState($state),
        ];
    }

    private function encodeState(array $state): string
    {
        $json = json_encode($state, JSON_UNESCAPED_UNICODE);
        if (!is_string($json)) {
            $json = '{}';
        }
        return rtrim(strtr(base64_encode($json), '+/', '-_'), '=');
    }

    private function decodeState(string $b64): ?array
    {
        $b64 = trim((string)$b64);
        if ($b64 === '') {
            return null;
        }
        $pad = strlen($b64) % 4;
        if ($pad) {
            $b64 .= str_repeat('=', 4 - $pad);
        }
        $json = base64_decode(strtr($b64, '-_', '+/'), true);
        if (!is_string($json) || $json === '') {
            return null;
        }
        $arr = json_decode($json, true);
        return is_array($arr) ? $arr : null;
    }

    private function ymdToIsoStart(string $ymd): string
    {
        return $ymd . 'T00:00:00.000Z';
    }

    private function ymdToIsoEnd(string $ymd): string
    {
        return $ymd . 'T23:59:59.999Z';
    }

    private function getMaxOccurredAt(int $accountId): ?string
    {
        if ($accountId <= 0) {
            return null;
        }
        $sql = 'SELECT MAX(occurred_at) AS m FROM `' . _DB_PREFIX_ . 'allegropro_billing_entry` WHERE id_allegropro_account=' . (int)$accountId;
        $row = Db::getInstance()->getRow($sql);
        $m = $row['m'] ?? null;
        return $m ? (string)$m : null;
    }

    private function countForDay(int $accountId, string $ymd): int
    {
        $start = pSQL($ymd . ' 00:00:00');
        $end = pSQL(date('Y-m-d', strtotime($ymd . ' +1 day')) . ' 00:00:00');
        $sql = 'SELECT COUNT(*) AS c FROM `' . _DB_PREFIX_ . 'allegropro_billing_entry` WHERE id_allegropro_account=' . (int)$accountId
            . " AND occurred_at >= '{$start}' AND occurred_at < '{$end}'";
        $row = Db::getInstance()->getRow($sql);
        return (int)($row['c'] ?? 0);
    }

    private function iterateDays(string $from, string $to): \Generator
    {
        $t1 = strtotime($from);
        $t2 = strtotime($to);
        if ($t1 === false || $t2 === false) {
            return;
        }
        if ($t1 > $t2) {
            [$t1, $t2] = [$t2, $t1];
        }
        for ($t = $t1; $t <= $t2; $t += 86400) {
            yield date('Y-m-d', $t);
        }
    }
}
