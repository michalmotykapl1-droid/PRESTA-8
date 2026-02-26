<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

class WmsExportManager
{
    const TABLE_DETAILS = 'wyprzedazpro_product_details';
    const TABLE_DUPES   = 'wyprzedazpro_csv_duplikaty';
    const TABLE_NOTF    = 'wyprzedazpro_not_found_products';

    public function exportCurrentWmsState()
    {
        $filename = 'WMS_stan_aktualny_' . date('Ymd_His') . '.csv';
        
        $sql = '
            SELECT 
                wpd.ean, 
                wpd.sku as wms_sku, 
                wpd.regal, 
                wpd.polka, 
                wpd.receipt_date, 
                wpd.expiry_date, 
                wpd.quantity_wms
            FROM ' . _DB_PREFIX_ . self::TABLE_DETAILS . ' wpd
            WHERE wpd.quantity_wms > 0
            ORDER BY wpd.regal ASC, wpd.polka ASC
        ';
        $mainRows = Db::getInstance()->executeS($sql);

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename=' . $filename);
        
        $out = fopen('php://output', 'w');
        fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF));
        fputcsv($out, ['EAN', 'KOD', 'Regał', 'Półka', 'DATA PRZYJĘCIA', 'DATA WAŻNOŚCI', 'STAN'], ';');
        
        if ($mainRows) {
            foreach ($mainRows as $r) {
                $ean = pSQL($r['ean']);
                $dupes = Db::getInstance()->executeS("
                    SELECT quantity, regal, polka, receipt_date, expiry_date 
                    FROM `" . _DB_PREFIX_ . self::TABLE_DUPES . "` 
                    WHERE ean = '$ean' AND quantity > 0
                ");

                if ($dupes && count($dupes) > 0) {
                    foreach ($dupes as $d) {
                        $dateForSku = '';
                        if ($d['expiry_date'] && $d['expiry_date'] != '0000-00-00') {
                            $dateForSku = date('dmY', strtotime($d['expiry_date']));
                        }
                        $skuGenerated = 'A_MAG_' . $ean . '_' . $dateForSku . '_(' . $d['regal'] . '_' . $d['polka'] . ')';

                        $this->writeCsvRow($out, $ean, $skuGenerated, $d['regal'], $d['polka'], $d['receipt_date'], $d['expiry_date'], $d['quantity']);
                    }
                } else {
                    $this->writeCsvRow($out, $r['ean'], $r['wms_sku'], $r['regal'], $r['polka'], $r['receipt_date'], $r['expiry_date'], $r['quantity_wms']);
                }
            }
        }
        fclose($out);
        exit;
    }

    public function exportNotFoundEansCsv()
    {
        $rows = Db::getInstance()->executeS('SELECT ean FROM `'._DB_PREFIX_.self::TABLE_NOTF.'` WHERE ean IS NOT NULL AND ean <> "" ORDER BY ean ASC');
        $filename = 'brakujace_eany_' . date('Ymd_His') . '.csv';
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename=' . $filename);
        $out = fopen('php://output', 'w');
        fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF));
        fputcsv($out, ['EAN'], ';');
        if ($rows) { foreach ($rows as $r) { fputcsv($out, [(string)$r['ean']], ';'); } }
        fclose($out);
        exit;
    }

    private function writeCsvRow($out, $ean, $sku, $regal, $polka, $receipt_date, $expiry_date, $qty) {
        $rd = ($receipt_date && $receipt_date != '0000-00-00') ? date('d.m.Y', strtotime($receipt_date)) : '';
        $ed = ($expiry_date && $expiry_date != '0000-00-00') ? date('d.m.Y', strtotime($expiry_date)) : '';
        fputcsv($out, [(string)$ean, (string)$sku, (string)$regal, (string)$polka, $rd, $ed, (int)$qty], ';');
    }
}