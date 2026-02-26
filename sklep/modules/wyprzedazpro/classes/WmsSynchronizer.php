<?php
/**
 * Klasa: WmsSynchronizer
 * Lokalizacja: /modules/wyprzedazpro/classes/WmsSynchronizer.php
 * Rola: Logika synchronizacji stanów magazynowych (WMS -> PrestaShop StockAvailable)
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class WmsSynchronizer
{
    /**
     * Główna metoda synchronizacji
     * Pobiera sumy z tabeli WMS i nadpisuje stany w Preście.
     * * @param int $id_shop ID sklepu
     * @return array ['success' => bool, 'count' => int]
     */
    public function synchronizeAll($id_shop)
    {
        $id_shop = (int)$id_shop;
        
        // 1. Grupujemy produkty z WMS po ID i sumujemy ich stany
        // (Sumujemy quantity_wms dla każdego id_product, niezależnie od lokalizacji)
        // Ignorujemy rekordy, które nie mają przypisanego ID produktu (np. same EANy bez powiązania)
        $sql = "SELECT id_product, SUM(quantity_wms) as total_wms 
                FROM `" . _DB_PREFIX_ . "wyprzedazpro_product_details` 
                WHERE id_product > 0 
                GROUP BY id_product";
                
        $wmsItems = Db::getInstance()->executeS($sql);
        $updatedCount = 0;

        if ($wmsItems) {
            foreach ($wmsItems as $item) {
                $id_product = (int)$item['id_product'];
                $qty_wms = (int)$item['total_wms'];
                
                // Zabezpieczenie: Nie pozwalamy na ujemne stany w Preście przy synchronizacji,
                // chyba że Twoja polityka magazynowa na to pozwala. Tutaj zerujemy ujemne.
                if ($qty_wms < 0) {
                    $qty_wms = 0;
                }

                // 2. Aktualizacja tabeli stock_available (Główny magazyn Presty)
                // Ustawiamy quantity, id_product_attribute = 0 (dla prostych produktów/głównego stanu)
                // Funkcja setQuantity automatycznie obsłuży odpowiedni sklep i grupę sklepów
                StockAvailable::setQuantity($id_product, 0, $qty_wms, $id_shop);
                
                $updatedCount++;
            }
        }
        
        return [
            'success' => true,
            'count' => $updatedCount
        ];
    }
}