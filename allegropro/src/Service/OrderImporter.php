<?php
namespace AllegroPro\Service;

use AllegroPro\Repository\OrderRepository;
use AllegroPro\Repository\AccountRepository;

class OrderImporter
{
    private AllegroApiClient $api;
    private OrderRepository $repo;
    private AccountRepository $accounts;

    public function __construct(AllegroApiClient $api, OrderRepository $repo, AccountRepository $accounts)
    {
        $this->api = $api;
        $this->repo = $repo;
        $this->accounts = $accounts;
    }

    public function runImport(): string
    {
        $activeAccounts = $this->accounts->all();
        $report = [];

        $fetcher = new OrderFetcher($this->api, $this->repo);
        $processor = new OrderProcessor($this->repo);

        foreach ($activeAccounts as $account) {
            if (empty($account['active'])) {
                continue;
            }

            $label = (string)($account['label'] ?? 'Konto');
            $accId = (int)($account['id_allegropro_account'] ?? 0);

            // 1) FETCH (używa istniejącej metody)
            try {
                $fetchStats = $fetcher->fetchRecent($account, 50);
                $report[] = "<div style='color:blue; font-weight:bold;'>⬇️ {$label}: Pobrano/Aktualizowano: " . (int)($fetchStats['fetched_count'] ?? 0) . "</div>";
            } catch (\Exception $e) {
                $report[] = "<div style='color:red;'>❌ {$label}: Błąd pobierania: " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . "</div>";
                continue;
            }

            // 2) PROCESS (create + fix) dla zamówień oczekujących
            $pendingIds = $this->repo->getPendingIds(50, $accId > 0 ? $accId : null);
            if (empty($pendingIds)) {
                $report[] = "<div>ℹ️ {$label}: Brak zamówień do przetworzenia.</div>";
                continue;
            }

            foreach ($pendingIds as $cfId) {
                $cfId = (string)$cfId;

                $create = $processor->processSingleOrder($cfId, 'create', $accId > 0 ? $accId : null);
                if (!empty($create['success'])) {
                    $report[] = "<div>✅ {$label} [{$cfId}] create: " . htmlspecialchars((string)($create['action'] ?? 'ok'), ENT_QUOTES, 'UTF-8') . "</div>";
                } else {
                    $report[] = "<div style='color:#a94442;'>⚠️ {$label} [{$cfId}] create: " . htmlspecialchars((string)($create['message'] ?? 'Błąd'), ENT_QUOTES, 'UTF-8') . "</div>";
                    continue;
                }

                $fix = $processor->processSingleOrder($cfId, 'fix', $accId > 0 ? $accId : null);
                if (!empty($fix['success'])) {
                    $report[] = "<div>✅ {$label} [{$cfId}] fix: " . htmlspecialchars((string)($fix['action'] ?? 'ok'), ENT_QUOTES, 'UTF-8') . "</div>";
                } else {
                    $report[] = "<div style='color:#a94442;'>⚠️ {$label} [{$cfId}] fix: " . htmlspecialchars((string)($fix['message'] ?? 'Błąd'), ENT_QUOTES, 'UTF-8') . "</div>";
                }
            }
        }

        return implode('', $report);
    }
}
