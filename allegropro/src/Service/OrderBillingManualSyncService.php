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
     * Używa filtrów Allegro: order.id oraz payment.id (jeśli dostępny).
     * To jest kluczowe, bo globalny sync po dacie czasem zwraca wpisy bez pola order/payment w payloadzie.
     *
     * @param int $accountId
     * @param string $occurredAtGteIso ISO8601 Z
     * @param string $occurredAtLteIso ISO8601 Z
     * @param string $checkoutFormId Allegro checkoutFormId (order.id)
     * @param string $paymentId Allegro payment.id (opcjonalnie)
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
        $paymentId = trim((string)$paymentId);

        if ($checkoutFormId !== '') {
            $r = $this->fetchAndUpsert($api, $billingRepo, $account, $accountId, $occurredAtGteIso, $occurredAtLteIso, ['order.id' => $checkoutFormId], $debug, $forceUpdateAll, 'order.id');
            if (empty($r['ok'])) {
                return $r;
            }
            $got += (int)($r['got'] ?? 0);
            $inserted += (int)($r['inserted'] ?? 0);
            $updated += (int)($r['updated'] ?? 0);
        }

        if ($paymentId !== '') {
            $r = $this->fetchAndUpsert($api, $billingRepo, $account, $accountId, $occurredAtGteIso, $occurredAtLteIso, ['payment.id' => $paymentId], $debug, $forceUpdateAll, 'payment.id');
            if (empty($r['ok'])) {
                return $r;
            }
            $got += (int)($r['got'] ?? 0);
            $inserted += (int)($r['inserted'] ?? 0);
            $updated += (int)($r['updated'] ?? 0);
        }

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
        $gotTotal = 0;
        $inserted = 0;
        $updated = 0;

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

            $res = $repo->upsertEntries($accountId, $list, $forceUpdateAll);
            $got = count($list);

            $gotTotal += $got;
            $inserted += (int)($res['inserted'] ?? 0);
            $updated += (int)($res['updated'] ?? 0);

            // stop conditions
            $offset += $got;
            $done = ($got < $limit);

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

}
