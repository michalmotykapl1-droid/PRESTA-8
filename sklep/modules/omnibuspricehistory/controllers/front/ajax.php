<?php
/**
 * Ajax front controller for Omnibus Price History module
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

class OmnibuspricehistoryAjaxModuleFrontController extends ModuleFrontController
{
    // Ustawienie flagi AJAX, aby dać sygnał, że to kontroler wyłącznie do AJAXa.
    public $ajax = true;

    /**
     * Główny punkt wejścia dla kontrolera frontowego.
     * Przeniesiono tutaj logikę sprawdzania parametru AJAX,
     * aby uniknąć zbędnego ładowania całej logiki frontowej.
     */
    public function init()
    {
        parent::init(); // Wywołujemy init z klasy nadrzędnej

        // Sprawdzamy, czy żądanie zawiera parametr ajaxPriceHistory
        // Jeśli tak, od razu wywołujemy metodę displayAjax() i przerywamy dalsze wykonanie skryptu.
        if (Tools::isSubmit('ajaxPriceHistory')) {
            $this->displayAjax(); // Ta metoda zwraca JSON i kończy skrypt za pomocą ajaxDie()
            exit; // KLUCZOWA ZMIANA: Dodano exit; aby mieć pewność, że żadna dalsza logika PrestaShop nie będzie wykonywana.
        }
        // Jeśli nie jest to żądanie AJAX, lub po obsłużeniu displayAjax(),
        // przerywamy dalsze wykonywanie skryptu, aby uniknąć renderowania szablonu.
    }

    /**
     * To, co zostanie odpalone przy ?controller=ajax&…&ajaxPriceHistory=1
     */
    public function displayAjax()
    {
        // KLUCZOWA ZMIANA: Ustaw odpowiedni nagłówek, ZANIM cokolwiek wyślesz.
        // Zapewnia, że przeglądarka i JavaScript poprawnie rozpoznają odpowiedź jako JSON.
        header('Content-Type: application/json; charset=UTF-8');

        // 1) Sprawdź, czy moduł został poprawnie zainicjalizowany NA POCZĄTKU
        //    (Main class to: class Omnibuspricehistory extends Module)
        if (!isset($this->module) || !$this->module instanceof Omnibuspricehistory) {
            // W tym przypadku nie możemy użyć $this->module->l(), więc używamy stałego tekstu
            $this->ajaxDie(json_encode([
                'error'   => true,
                'message' => 'Internal server error: Module instance not available.', // Stały tekst błędu
            ], JSON_UNESCAPED_UNICODE));
        }

        // 2) Pobierz parametry
        $idProduct = (int) Tools::getValue('id_product');
        $idAttr    = (int) Tools::getValue('id_product_attribute', 0);

        // 3) Walidacja parametru id_product
        if ($idProduct <= 0) {
            // Użycie ajaxDie() do zwrócenia JSON i zakończenia skryptu
            $this->ajaxDie(json_encode([
                'error'   => true,
                'message' => $this->module->l('Nieprawidłowy identyfikator produktu.'),
            ], JSON_UNESCAPED_UNICODE));
        }

        try {
            // KLUCZOWA ZMIANA: Aby uzyskać dostęp do omnibusPriceHistoryService, musi być ono publiczne w klasie modułu.
            // Upewnij się, że w pliku omnibuspricehistory.php zmieniono:
            // protected $omnibusPriceHistoryService;
            // na:
            // public $omnibusPriceHistoryService;
            $history = $this->module
                            ->omnibusPriceHistoryService
                            ->getProductPriceHistory($idProduct, $idAttr);

            // 5) Zwróć czysty JSON za pomocą ajaxDie()
            $this->ajaxDie(json_encode([
                'error'   => false,
                'history' => $history,
            ], JSON_UNESCAPED_UNICODE));
        } catch (\Exception $e) {
            // 6) W razie wyjątku też zwracamy JSON za pomocą ajaxDie()
            $this->ajaxDie(json_encode([
                'error'   => true,
                'message' => $e->getMessage(),
            ], JSON_UNESCAPED_UNICODE));
        }
    }
}
