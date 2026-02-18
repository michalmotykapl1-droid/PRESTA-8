<?php
/**
 * ALLEGRO PRO - Rozliczenia (Billing)
 *
 * Nowa funkcja w osobnych plikach: Controller + Services + Repository.
 */

use AllegroPro\Repository\AccountRepository;
use AllegroPro\Repository\BillingEntryRepository;
use AllegroPro\Service\HttpClient;
use AllegroPro\Service\AllegroApiClient;
use AllegroPro\Service\BillingSyncService;
use AllegroPro\Service\SettlementsReportService;

class AdminAllegroProSettlementsController extends ModuleAdminController
{
    private AccountRepository $accounts;
    private BillingEntryRepository $billingRepo;

    public function __construct()
    {
        parent::__construct();
        $this->bootstrap = true;
        $this->accounts = new AccountRepository();
        $this->billingRepo = new BillingEntryRepository();
    }

    public function initContent()
    {
        parent::initContent();

        if (isset($this->module) && method_exists($this->module, 'ensureTabs')) {
            $this->module->ensureTabs();
        }

        // zapewnij tabelę billing (dla istniejących instalacji bez reinstalacji)
        $this->billingRepo->ensureSchema();

        $accounts = $this->accounts->all();
        $selectedAccountId = (int)Tools::getValue('id_allegropro_account', 0);
        if (!$selectedAccountId && !empty($accounts)) {
            $selectedAccountId = (int)($accounts[0]['id_allegropro_account'] ?? 0);
        }
        $selectedAccount = null;
        foreach ($accounts as $a) {
            if ((int)$a['id_allegropro_account'] === $selectedAccountId) {
                $selectedAccount = $a;
                break;
            }
        }

        // daty
        $dateFrom = (string)Tools::getValue('date_from', '');
        $dateTo = (string)Tools::getValue('date_to', '');
        if (!$dateFrom || !$dateTo) {
            $dateFrom = date('Y-m-01');
            $dateTo = date('Y-m-d');
        }
        $dateFrom = $this->sanitizeYmd($dateFrom) ?: date('Y-m-01');
        $dateTo = $this->sanitizeYmd($dateTo) ?: date('Y-m-d');

        $q = trim((string)Tools::getValue('q', ''));
        $q = $this->sanitizeSearch($q);

        $viewOrderId = trim((string)Tools::getValue('view_order_id', ''));

        $syncResult = null;
        $syncDebug = [];
        if (Tools::isSubmit('submitAllegroProBillingSync') && $selectedAccount) {
            $http = new HttpClient();
            $api = new AllegroApiClient($http, $this->accounts);
            $sync = new BillingSyncService($api, $this->billingRepo);

            $syncResult = $sync->syncRange(
                $selectedAccount,
                $this->toIsoStart($dateFrom),
                $this->toIsoEnd($dateTo),
                $syncDebug
            );

            if (!empty($syncResult['ok'])) {
                $this->confirmations[] = sprintf('Synchronizacja zakończona — pobrano %d operacji (nowe: %d, zaktualizowane: %d).',
                    (int)($syncResult['total'] ?? 0),
                    (int)($syncResult['inserted'] ?? 0),
                    (int)($syncResult['updated'] ?? 0)
                );
            } else {
                $this->errors[] = 'Nie udało się zsynchronizować opłat (billing-entries). Sprawdź autoryzację konta i logi.';
            }
        }

        $report = new SettlementsReportService($this->billingRepo);
        $summary = $selectedAccount ? $report->getPeriodSummary($selectedAccountId, $dateFrom, $dateTo) : [];
        $ordersRows = $selectedAccount ? $report->getOrdersWithFees($selectedAccountId, $dateFrom, $dateTo, $q) : [];

        $billingCount = $selectedAccount ? $this->billingRepo->countInRange($selectedAccountId, $dateFrom, $dateTo) : 0;

        $orderDetails = null;
        if ($selectedAccount && $viewOrderId !== '') {
            $orderDetails = $report->getOrderDetails($selectedAccountId, $viewOrderId, $dateFrom, $dateTo);
        }

        $this->context->smarty->assign([
            'accounts' => $accounts,
            'selected_account_id' => $selectedAccountId,
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'q' => $q,
            'sync_result' => $syncResult,
            'sync_debug' => $syncDebug,
            'summary' => $summary,
            'orders_rows' => $ordersRows,
            'order_details' => $orderDetails,
            'view_order_id' => $viewOrderId,
            'billing_count' => (int)$billingCount,
            'settlements_link' => $this->context->link->getAdminLink('AdminAllegroProSettlements'),
            'current_index' => self::$currentIndex,
            'token' => $this->token,
        ]);

        $this->setTemplate('settlements.tpl');
    }

    private function toIsoStart(string $ymd): string
    {
        $ymd = preg_replace('/[^0-9-]/', '', $ymd);
        if (!$ymd) {
            $ymd = date('Y-m-d');
        }
        return $ymd . 'T00:00:00.000Z';
    }

    private function toIsoEnd(string $ymd): string
    {
        $ymd = preg_replace('/[^0-9-]/', '', $ymd);
        if (!$ymd) {
            $ymd = date('Y-m-d');
        }
        return $ymd . 'T23:59:59.999Z';
    }

    private function sanitizeYmd(string $ymd): string
    {
        $ymd = preg_replace('/[^0-9-]/', '', $ymd);
        if (!preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/', $ymd)) {
            return '';
        }
        return $ymd;
    }

    private function sanitizeSearch(string $q): string
    {
        $q = trim($q);
        if ($q === '') {
            return '';
        }
        // Keep it safe & readable (also prevents SQL syntax issues caused by exotic characters)
        $q = preg_replace('/[^A-Za-z0-9@._\-]/', '', $q);
        return (string)$q;
    }
}
