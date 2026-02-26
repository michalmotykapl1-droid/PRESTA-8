<?php
/**
 * Integracja z modułem AllegroPro (Twój moduł)
 * Pobiera dane bezpośrednio z tabel allegropro_order i allegropro_order_shipping
 */

class BbAllegroPro
{
    /**
     * Pobiera dane o punkcie i metodzie dostawy z Twoich tabel
     * @param int $id_order ID zamówienia w PrestaShop
     * @return array|null
     */
    public static function getDeliveryInfo($id_order)
    {
        $db = Db::getInstance();

        // Sprawdzenie czy tabela główna istnieje (zabezpieczenie)
        try {
            $sqlCheck = "SHOW TABLES LIKE '" . _DB_PREFIX_ . "allegropro_order'";
            if (!$db->executeS($sqlCheck)) {
                return null;
            }
        } catch (Exception $e) {
            return null;
        }

        // Łączymy zamówienie z wysyłką po checkout_form_id
        // Szukamy po id_order_prestashop
        $sql = 'SELECT 
                    s.method_name, 
                    s.pickup_point_id, 
                    s.pickup_point_name, 
                    s.addr_street, 
                    s.addr_city 
                FROM `' . _DB_PREFIX_ . 'allegropro_order` o
                LEFT JOIN `' . _DB_PREFIX_ . 'allegropro_order_shipping` s 
                    ON o.checkout_form_id = s.checkout_form_id
                WHERE o.id_order_prestashop = ' . (int)$id_order;
        
        $row = $db->getRow($sql);
        
        if ($row) {
            // Budujemy adres punktu
            $addr = trim($row['addr_street']);
            if (!empty($row['addr_city'])) {
                $addr .= ', ' . $row['addr_city'];
            }

            return [
                'method_name' => $row['method_name'],
                'point_id'    => $row['pickup_point_id'],
                'point_name'  => $row['pickup_point_name'],
                'point_addr'  => $addr
            ];
        }

        return null;
    }
}