<?php
/**
 * Integracja z modułem X13 Allegro
 * Odpowiada za wyciąganie danych z tabeli xallegro_order (JSON)
 */

class BbAllegroX13
{
    /**
     * Pobiera dane o punkcie i metodzie dostawy
     * @param int $id_order
     * @return array|null
     */
    public static function getDeliveryInfo($id_order)
    {
        $db = Db::getInstance();
        
        // Sprawdzenie czy tabela istnieje
        try {
            $sqlCheck = "SHOW TABLES LIKE '" . _DB_PREFIX_ . "xallegro_order'";
            if (!$db->executeS($sqlCheck)) {
                return null;
            }
        } catch (Exception $e) {
            return null;
        }

        // Pobieramy JSON z danymi
        $sql = 'SELECT checkout_form_content FROM `' . _DB_PREFIX_ . 'xallegro_order` WHERE id_order = ' . (int)$id_order;
        $json = $db->getValue($sql);

        if (!$json) {
            return null;
        }

        $data = json_decode($json, true);
        if (!$data) {
            return null;
        }

        $result = [
            'method_name' => null,
            'point_id' => null,
            'point_name' => null,
            'point_addr' => null
        ];

        // Wyciąganie danych ze struktury JSON X13
        if (isset($data['delivery'])) {
            if (isset($data['delivery']['method']['name'])) {
                $result['method_name'] = $data['delivery']['method']['name'];
            }
            if (isset($data['delivery']['pickupPoint']['id'])) {
                $result['point_id'] = $data['delivery']['pickupPoint']['id'];
            }
            if (isset($data['delivery']['pickupPoint']['name'])) {
                $result['point_name'] = $data['delivery']['pickupPoint']['name'];
            }
            if (isset($data['delivery']['pickupPoint']['address']['street'])) {
                $result['point_addr'] = $data['delivery']['pickupPoint']['address']['street'] . ', ' . ($data['delivery']['pickupPoint']['address']['city'] ?? '');
            }
        }

        // Zwracamy tylko jeśli znaleziono cokolwiek istotnego
        if ($result['method_name'] || $result['point_id']) {
            return $result;
        }

        return null;
    }
}