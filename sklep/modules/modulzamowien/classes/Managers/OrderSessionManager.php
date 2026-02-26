<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

require_once _PS_MODULE_DIR_ . 'modulzamowien/classes/Repositories/PickingSessionRepository.php';
require_once _PS_MODULE_DIR_ . 'modulzamowien/classes/Repositories/OrdersSessionRepository.php';
require_once _PS_MODULE_DIR_ . 'modulzamowien/classes/Repositories/AlternativeOrdersRepository.php';

class OrderSessionManager
{
    private $repository;
    private $ordersRepo;
    private $altRepo;

    public function __construct()
    {
        $this->repository = new PickingSessionRepository();
        $this->ordersRepo = new OrdersSessionRepository();
        $this->altRepo = new AlternativeOrdersRepository();
    }

    public function loadPickingFromFile()
    {
        return $this->repository->getAllItems();
    }

    public function addPickingToSession($data)
    {
        if (is_array($data) && count($data) > 0) {
            $this->repository->addItemsToSession($data);
        }
    }
    
    public function savePickingToFile($data)
    {
        $this->addPickingToSession($data);
    }

    public function getSessionPickedQty($sku, $key = 'user_picked_qty')
    {
        return $this->repository->getPickedQty($sku);
    }

    public function updateReportSession($sku, $qty, $isCollected, $keyQty, $saveToFile = true)
    {
        $this->repository->updatePickedQty($sku, $qty, $isCollected);
        
        if (session_status() == PHP_SESSION_NONE) session_start();
        $updateArray = function(&$array) use ($sku, $qty, $isCollected) {
            if (!is_array($array)) return;
            foreach ($array as &$row) {
                if ((isset($row['sku']) && $row['sku'] == $sku) || (isset($row['ean']) && $row['ean'] == $sku)) {
                    $row['user_picked_qty'] = $qty;
                    $row['is_collected'] = $isCollected;
                    break;
                }
            }
        };
        if (isset($_SESSION['mz_picking_data'])) $updateArray($_SESSION['mz_picking_data']);
        if (isset($_SESSION['mz_report_data'])) $updateArray($_SESSION['mz_report_data']);
    }
    
    /**
     * NOWOŚĆ: Aktualizacja ilości do kupienia w Zakładce 3
     * Wywoływana przy skanowaniu w Zakładce 2.
     * $delta: ujemna jeśli zebrano (zmniejszamy zakup), dodatnia jeśli cofnięto.
     */
    public function updateOrderSessionQty($skuOrEan, $delta)
    {
        // Standardowa lista zamówień
        $this->ordersRepo->updateQtyBuy($skuOrEan, $delta);

        // Strategia alternatywna (żeby oba widoki trzymały się w sync po kompletacji)
        if ($this->altRepo) {
            $this->altRepo->updateQtyBuy($skuOrEan, $delta);
        }
    }


    /**
     * RESET (TEST): czyści dane sesji analizy (Zakładka 1-3)
     * - picking_session + picking_files
     * - orders_session (std) + orders_session_alt
     * Nie dotyka Pick Stołu (Wirtualnego Magazynu).
     */
    public function clearAnalysisSessions()
    {
        $this->repository->clearSession();
        $this->ordersRepo->clearSession();
        if ($this->altRepo) {
            $this->altRepo->clearSession();
        }
        return true;
    }

    public function initMobileSession()
    {
        $pickingData = $this->repository->getAllItems();
        if (empty($pickingData)) {
            return ['success' => false, 'msg' => 'Brak danych w bazie (Zakładka 2)! Najpierw wgraj plik CSV.'];
        }
        return ['success' => true, 'data' => $pickingData];
    }
}
?>