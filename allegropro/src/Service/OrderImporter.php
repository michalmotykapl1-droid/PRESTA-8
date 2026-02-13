<?php
namespace AllegroPro\Service;

use AllegroPro\Repository\OrderRepository;
use AllegroPro\Repository\AccountRepository;
use AllegroPro\Service\AllegroApiClient;
use AllegroPro\Service\OrderFetcher;
use AllegroPro\Service\OrderProcessor;

class OrderImporter
{
    private $api;
    private $repo;
    private $accounts;

    public function __construct(AllegroApiClient $api, OrderRepository $repo, AccountRepository $accounts)
    {
        $this->api = $api;
        $this->repo = $repo;
        $this->accounts = $accounts;
    }

    public function runImport()
    {
        $activeAccounts = $this->accounts->all();
        $report = [];

        $fetcher = new OrderFetcher($this->api, $this->repo);
        $processor = new OrderProcessor($this->repo);

        foreach ($activeAccounts as $account) {
            if (!$account['active']) continue;
            $lbl = $account['label'];
            $accId = (int)$account['id_allegropro_account'];

            // 1. POBIERANIE (FETCH)
            try {
                // Limit 5 na start
                $fetchStats = $fetcher->fetchRecent($account, 5, true); 
                $report[] = "<div style='color:blue; font-weight:bold;'>⬇️ $lbl: Pobrano/Aktualizowano: {$fetchStats['fetched_count']}</div>";
            } catch (\Exception $e) {
                $report[] = "<div style='color:red;'>❌ $lbl: Błąd pobierania: " . $e->getMessage() . "</div>";
            }

            // 2. TWORZENIE (PROCESS) - TRYB DEBUG
            $report[] = "<div><strong>--- Próba tworzenia zamówień (DEBUG) ---</strong></div>";
            
            $procStats = $processor->processPendingOrders($accId);
            
            if (!empty($procStats['logs'])) {
                foreach ($procStats['logs'] as $log) {
                    $report[] = "<div>$log</div>";
                }
            } else {
                $report[] = "<div>Brak logów z procesu tworzenia (może brak nowych zamówień?)</div>";
            }
        }

        return implode('', $report);
    }
}
