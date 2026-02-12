<?php
namespace AllegroPro\Repository;

use Db;
use DbQuery;

class ShipmentRepository
{
    private string $table;

    public function __construct()
    {
        $this->table = _DB_PREFIX_ . 'allegropro_shipment';
    }

    /**
     * Tworzy nowy wpis lub aktualizuje istniejący
     * FIX: Dostosowano do istniejącej struktury tabeli (brak kolumny command_id)
     */
    public function upsert(int $accountId, string $checkoutFormId, string $commandId, array $payload): void
    {
        // Decydujemy, co jest naszym identyfikatorem. 
        // Jeśli mamy finalne shipmentId z API, używamy go. Jeśli nie, używamy commandId.
        $finalId = !empty($payload['shipmentId']) ? $payload['shipmentId'] : $commandId;

        // Sprawdzamy czy wpis już istnieje (szukamy po shipment_id)
        $sql = 'SELECT id_allegropro_shipment FROM `'.$this->table.'` 
                WHERE id_allegropro_account='.(int)$accountId." 
                AND shipment_id='".pSQL($finalId)."'";
                
        $existing = Db::getInstance()->getValue($sql);

        // Mapowanie danych na istniejące kolumny w Twojej bazie
        // Zgodnie ze zrzutem ekranu: tracking_number, carrier_mode, size_details, is_smart, status, created_at, updated_at
        $row = [
            'id_allegropro_account' => (int)$accountId,
            'checkout_form_id' => pSQL($checkoutFormId),
            
            // Zapisujemy ID (To jest kluczowe pole z Twojej tabeli)
            'shipment_id' => pSQL($finalId),
            
            'status' => isset($payload['status']) ? pSQL((string)$payload['status']) : 'NEW',
            'is_smart' => isset($payload['is_smart']) ? (int)$payload['is_smart'] : 0,
            
            // Mapowanie size_type (A/B/C) na odpowiednie kolumny
            'carrier_mode' => isset($payload['size_type']) && in_array($payload['size_type'], ['A','B','C']) ? 'BOX' : 'COURIER',
            'size_details' => isset($payload['size_type']) ? pSQL((string)$payload['size_type']) : 'CUSTOM',
            
            'updated_at' => pSQL(date('Y-m-d H:i:s')),
        ];

        if ($existing) {
            Db::getInstance()->update('allegropro_shipment', $row, 'id_allegropro_shipment='.(int)$existing);
        } else {
            $row['created_at'] = pSQL(date('Y-m-d H:i:s'));
            // Domyślne wartości dla kolumn, które mogą nie być w payloadzie
            $row['tracking_number'] = ''; 
            $row['label_path'] = null;
            
            Db::getInstance()->insert('allegropro_shipment', $row);
        }
    }

    /**
     * Pobiera pełną historię przesyłek dla danego zamówienia
     */
    public function findAllByOrder(string $checkoutFormId): array
    {
        $q = new DbQuery();
        $q->select('*');
        $q->from('allegropro_shipment');
        $q->where("checkout_form_id = '" . pSQL($checkoutFormId) . "'");
        $q->orderBy('created_at DESC');
        
        $results = Db::getInstance()->executeS($q);
        return $results ?: [];
    }

    /**
     * Aktualizuje status konkretnej przesyłki
     */
    public function updateStatus(string $shipmentId, string $newStatus): void
    {
        Db::getInstance()->update(
            'allegropro_shipment', 
            ['status' => pSQL($newStatus), 'updated_at' => date('Y-m-d H:i:s')], 
            "shipment_id = '" . pSQL($shipmentId) . "'"
        );
    }
}