<?php
namespace AllegroPro\Service;

use AllegroPro\Repository\AccountRepository;
use AllegroPro\Repository\BillingEntryRepository;

/**
 * Ręczna synchronizacja billing-entries (do użycia w zakładce "Rozliczenia Allegro" w zamówieniu).
 *
 * Uwaga: Allegro billing-entries są filtrowane po dacie (occurredAt), nie po orderId.
 * Dlatego ręczne pobieranie działa na zakresie dat, dobranym pod to zamówienie.
 */
class OrderBillingManualSyncService
{
    /**
     * @return array{ok:bool,code:int,total:int,inserted:int,updated:int,debug?:array<string>}
     */
    public function syncRangeForAccount(int $accountId, string $occurredAtGteIso, string $occurredAtLteIso, bool $forceUpdateAll = false): array
    {
        $accounts = new AccountRepository();
        $account = $accounts->get($accountId);

        if (!is_array($account) || empty($account['access_token'])) {
            return ['ok' => false, 'code' => 0, 'total' => 0, 'inserted' => 0, 'updated' => 0];
        }

        $billingRepo = new BillingEntryRepository();
        $billingRepo->ensureSchema();

        $api = new AllegroApiClient(new HttpClient(), $accounts);
        $sync = new BillingSyncService($api, $billingRepo);

        $debug = [];
        $res = $sync->syncRange($account, $occurredAtGteIso, $occurredAtLteIso, $debug, $forceUpdateAll);
        $res['debug'] = $debug;
        return $res;
    }


    /**
     * Celowane pobranie billing-entries dla konkretnego zamówienia.
     * Używa filtra Allegro: order.id (checkoutFormId).
     * To jest kluczowe, bo globalny sync po dacie czasem zwraca wpisy bez pola order/payment w payloadzie.
     *
     * @param int $accountId
     * @param string $occurredAtGteIso ISO8601 Z
     * @param string $occurredAtLteIso ISO8601 Z
     * @param string $checkoutFormId Allegro checkoutFormId (order.id)
     * @param string $paymentId Allegro payment.id (obecnie ignorowane – używamy tylko order.id)
     * @param array $debug
     * @param bool $forceUpdateAll zawsze TRUE dla celowanego (mała paczka, chcemy uzupełnić braki)
     * @return array{ok:bool,code:int,got:int,inserted:int,updated:int}
     */
    public function syncTargetedForOrder(
        int $accountId,
        string $occurredAtGteIso,
        string $occurredAtLteIso,
        string $checkoutFormId,
        string $paymentId = '',
        array &$debug = [],
        bool $forceUpdateAll = true
    ): array {
        $accounts = new AccountRepository();
        $account = $accounts->get($accountId);

        if (!is_array($account) || empty($account['access_token'])) {
            return ['ok' => false, 'code' => 0, 'got' => 0, 'inserted' => 0, 'updated' => 0];
        }

        $billingRepo = new BillingEntryRepository();
        $billingRepo->ensureSchema();

        $api = new AllegroApiClient(new HttpClient(), $accounts);

        $got = 0;
        $inserted = 0;
        $updated = 0;

        $checkoutFormId = trim((string)$checkoutFormId);
        $paymentId = trim((string)$paymentId);        if ($checkoutFormId !== '') {
            $r = $this->fetchAndUpsert($api, $billingRepo, $account, $accountId, $occurredAtGteIso, $occurredAtLteIso, ['order.id' => $checkoutFormId], $debug, $forceUpdateAll, 'order.id');
            if (empty($r['ok'])) {
                return $r;
            }
            $got += (int)($r['got'] ?? 0);
            $inserted += (int)($r['inserted'] ?? 0);
            $updated += (int)($r['updated'] ?? 0);
        }

        // payment.id disabled – order.id is enough for current needs

        return ['ok' => true, 'code' => 200, 'got' => $got, 'inserted' => $inserted, 'updated' => $updated];
    }

    /**
     * Wykonuje paginowane pobranie z API /billing/billing-entries + upsert do DB.
     *
     * @return array{ok:bool,code:int,got:int,inserted:int,updated:int}
     */
    private function fetchAndUpsert(
        AllegroApiClient $api,
        BillingEntryRepository $repo,
        array $account,
        int $accountId,
        string $occurredAtGteIso,
        string $occurredAtLteIso,
        array $extraQuery,
        array &$debug,
        bool $forceUpdateAll,
        string $label
    ): array {
        $limit = 100;
        $offset = 0;

        // "got" oznacza liczbę rekordów, które faktycznie pasują do filtra (order.id / payment.id),
        // a nie liczbę rekordów zwróconych przez API (API czasem ignoruje filtr i zwraca listę globalną).
        $gotTotal = 0;
        $inserted = 0;
        $updated = 0;

        $zeroMatchStreak = 0;

        for ($page = 1; $page <= 20; $page++) { // bezpieczny limit (dla jednego order/payment zwykle 1-2 strony)
            $query = array_merge([
                'occurredAt.gte' => $occurredAtGteIso,
                'occurredAt.lte' => $occurredAtLteIso,
                'limit' => $limit,
                'offset' => $offset,
            ], $extraQuery);

            $resp = $api->get($account, '/billing/billing-entries', $query);

            $debug[] = sprintf('[TARGET %s] GET /billing/billing-entries offset=%d limit=%d: HTTP %d ok=%d', $label, $offset, $limit, (int)($resp['code'] ?? 0), !empty($resp['ok']) ? 1 : 0);

            if (empty($resp['ok']) || !is_array($resp['json'])) {
                return ['ok' => false, 'code' => (int)($resp['code'] ?? 0), 'got' => $gotTotal, 'inserted' => $inserted, 'updated' => $updated];
            }

            $list = $resp['json']['billingEntries'] ?? [];
            if (!is_array($list) || empty($list)) {
                break;
            }

            $fetched = count($list);
            $matched = $list;

            // Filtruj po stronie modułu, jeśli API ignoruje filtr "order.id"/"payment.id"
            if ($label === 'order.id') {
                $expected = trim((string)($extraQuery['order.id'] ?? ''));
                if ($expected !== '') {
                    $matched = array_values(array_filter($list, function ($e) use ($expected) {
                        return is_array($e) && $this->entryHasOrderId($e, $expected);
                    }));
                }
            } elseif ($label === 'payment.id') {
                $expected = trim((string)($extraQuery['payment.id'] ?? ''));
                if ($expected !== '') {
                    $matched = array_values(array_filter($list, function ($e) use ($expected) {
                        return is_array($e) && $this->entryHasPaymentId($e, $expected);
                    }));
                }
            }

            $debug[] = sprintf('[TARGET %s] fetched=%d matched=%d', $label, $fetched, is_array($matched) ? count($matched) : 0);

            // Jeżeli filtr jest ignorowany, nie skanuj 2000 rekordów w ciemno.
            if (($label === 'order.id' || $label === 'payment.id') && empty($matched)) {
                $zeroMatchStreak++;
                if ($zeroMatchStreak >= 3) {
                    $debug[] = sprintf('[TARGET %s] stop: 3 pages with 0 matches (API likely ignores filter)', $label);
                    break;
                }
            } else {
                $zeroMatchStreak = 0;
            }

            // Upsert tylko rekordów dopasowanych do filtra.
            $res = $repo->upsertEntries($accountId, $matched, $forceUpdateAll);
            $got = is_array($matched) ? count($matched) : 0;

            $gotTotal += $got;
            $inserted += (int)($res['inserted'] ?? 0);
            $updated += (int)($res['updated'] ?? 0);

            // stop conditions (paginacja po API oparta jest o ilość rekordów zwróconych, nie dopasowanych)
            $offset += $fetched;
            $done = ($fetched < $limit);

            $totalCount = null;
            foreach (['totalCount', 'total', 'totalElements', 'total_count'] as $k) {
                if (isset($resp['json'][$k])) {
                    $totalCount = (int)$resp['json'][$k];
                    break;
                }
            }
            if ($totalCount !== null && $totalCount >= 0 && $offset >= $totalCount) {
                $done = true;
            }

            if ($done) {
                break;
            }
        }

        return ['ok' => true, 'code' => 200, 'got' => $gotTotal, 'inserted' => $inserted, 'updated' => $updated];
    }

    /**
     * Best-effort: czy billing-entry dotyczy danego order.id (checkoutFormId).
     */
    private function entryHasOrderId(array $e, string $expected): bool
    {
        $expected = trim($expected);
        if ($expected === '') {
            return false;
        }

        // Common shapes: order.id / orderId / checkoutFormId
        if (!empty($e['order']) && is_array($e['order'])) {
            $id = (string)($e['order']['id'] ?? '');
            if ($id !== '' && strcasecmp($id, $expected) === 0) {
                return true;
            }
            $id = (string)($e['order']['checkoutFormId'] ?? $e['order']['checkout_form_id'] ?? '');
            if ($id !== '' && strcasecmp($id, $expected) === 0) {
                return true;
            }
        }
        $id = (string)($e['orderId'] ?? $e['order_id'] ?? $e['checkoutFormId'] ?? $e['checkout_form_id'] ?? '');
        if ($id !== '' && strcasecmp($id, $expected) === 0) {
            return true;
        }

        // Fallback: sometimes inside details
        if (!empty($e['details']) && is_array($e['details'])) {
            $d = $e['details'];
            if (!empty($d['order']) && is_array($d['order'])) {
                $id = (string)($d['order']['id'] ?? '');
                if ($id !== '' && strcasecmp($id, $expected) === 0) {
                    return true;
                }
            }
            $id = (string)($d['orderId'] ?? $d['order_id'] ?? $d['checkoutFormId'] ?? $d['checkout_form_id'] ?? '');
            if ($id !== '' && strcasecmp($id, $expected) === 0) {
                return true;
            }
        }

        // Last resort: raw search
        $raw = json_encode($e);
        return is_string($raw) && stripos($raw, $expected) !== false;
    }

    /**
     * Best-effort: czy billing-entry dotyczy danego payment.id.
     */
    private function entryHasPaymentId(array $e, string $expected): bool
    {
        $expected = trim($expected);
        if ($expected === '') {
            return false;
        }

        if (!empty($e['payment']) && is_array($e['payment'])) {
            $id = (string)($e['payment']['id'] ?? '');
            if ($id !== '' && strcasecmp($id, $expected) === 0) {
                return true;
            }
        }

        $id = (string)($e['paymentId'] ?? $e['payment_id'] ?? '');
        if ($id !== '' && strcasecmp($id, $expected) === 0) {
            return true;
        }

        if (!empty($e['details']) && is_array($e['details'])) {
            $d = $e['details'];
            if (!empty($d['payment']) && is_array($d['payment'])) {
                $id = (string)($d['payment']['id'] ?? '');
                if ($id !== '' && strcasecmp($id, $expected) === 0) {
                    return true;
                }
            }
            $id = (string)($d['paymentId'] ?? $d['payment_id'] ?? '');
            if ($id !== '' && strcasecmp($id, $expected) === 0) {
                return true;
            }
        }

        $raw = json_encode($e);
        return is_string($raw) && stripos($raw, $expected) !== false;
    }

}
