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
}
