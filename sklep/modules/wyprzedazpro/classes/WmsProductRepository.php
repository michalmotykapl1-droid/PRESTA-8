<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

require_once _PS_MODULE_DIR_ . 'wyprzedazpro/classes/WmsCalculator.php';

class WmsProductRepository
{
    const TABLE_DUPES = 'wyprzedazpro_csv_duplikaty';
    const TABLE_DETAILS = 'wyprzedazpro_product_details';
    const TABLE_NOTF = 'wyprzedazpro_not_found_products';
    const TABLE_HISTORY = 'wyprzedazpro_import_history';

    public function getCounters($id_shop)
    {
        $baseCounterQuery = function() use ($id_shop) {
            $q = new DbQuery();
            $q->select('COUNT(DISTINCT p.id_product)');
            $q->from('product', 'p');
            $q->leftJoin('wyprzedazpro_product_details', 'wpd', 'p.id_product = wpd.id_product');
            $q->leftJoin('stock_available', 'sa', 'sa.id_product = p.id_product AND sa.id_product_attribute = 0 AND sa.id_shop = '.$id_shop);
            $q->where("(p.reference LIKE 'A_MAG_%' OR p.reference LIKE '0_MAG_%')"); 
            $q->where('p.active = 1');
            $q->where('(sa.quantity > 0 OR wpd.quantity_wms > 0)');
            return $q;
        };

        $counters = [];

        // Expired
        $sql_expired = $baseCounterQuery();
        if (!Configuration::get('WYPRZEDAZPRO_IGNORE_BIN_EXPIRY')) {
            $sql_expired->where('wpd.expiry_date < CURDATE()');
        } else {
             $sql_expired->where('(wpd.expiry_date < CURDATE() AND wpd.regal != "KOSZ")');
        }
        $counters['expired'] = (int)Db::getInstance()->getValue($sql_expired);

        // Short Date
        $shortDateThreshold = (int)Configuration::get('WYPRZEDAZPRO_SHORT_DATE_DAYS', 14);
        $sql_short = $baseCounterQuery();
        $sql_short->where('wpd.expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL ' . $shortDateThreshold . ' DAY)');
        $counters['short'] = (int)Db::getInstance()->getValue($sql_short);
        
        // Days ranges
        $sql_30 = $baseCounterQuery();
        $sql_30->where('DATEDIFF(CURDATE(), wpd.receipt_date) <= 30');
        $counters['30_days'] = (int)Db::getInstance()->getValue($sql_30);

        $sql_90 = $baseCounterQuery();
        $sql_90->where('DATEDIFF(CURDATE(), wpd.receipt_date) BETWEEN 31 AND 90');
        $counters['31_90_days'] = (int)Db::getInstance()->getValue($sql_90);

        $sql_over90 = $baseCounterQuery();
        $sql_over90->where('DATEDIFF(CURDATE(), wpd.receipt_date) > 90');
        $counters['over_90_days'] = (int)Db::getInstance()->getValue($sql_over90);

        return $counters;
    }

    public function getSaleProducts($date_filter = 'all', $id_lang, $id_shop)
    {
        $sql = new DbQuery();
        $sql->select('p.id_product, p.reference, sa.quantity as quantity_presta, wpd.quantity_wms, pl.name, p.ean13, p.wholesale_price, sp.reduction, sp.reduction_type');
        $sql->select('wpd.expiry_date, wpd.receipt_date, wpd.regal, wpd.polka, wpd.sku, wpd.is_manual');
        
        $sql->from('product', 'p');
        $sql->leftJoin('product_lang', 'pl', 'pl.id_product = p.id_product AND pl.id_lang = '.(int)$id_lang);
        $sql->leftJoin('stock_available', 'sa', 'sa.id_product = p.id_product AND sa.id_product_attribute = 0 AND sa.id_shop = '.(int)$id_shop);
        $sql->leftJoin('category_product', 'cp', 'cp.id_product = p.id_product');
        $sql->leftJoin('specific_price', 'sp', 'sp.id_product = p.id_product');
        $sql->leftJoin(self::TABLE_DETAILS, 'wpd', 'p.id_product = wpd.id_product');

        $sql->where("(p.reference LIKE 'A_MAG_%' OR p.reference LIKE '0_MAG_%')");
        $sql->where("cp.id_category IN (45, 180)");
        
        // FILTRY CZYSTOŚCI
        $sql->where("p.active = 1");
        $sql->where("(sa.quantity > 0 OR wpd.quantity_wms > 0)");

        $shortDateThreshold = (int)Configuration::get('WYPRZEDAZPRO_SHORT_DATE_DAYS', 14);
        $ignoreBinExpiry = (bool)Configuration::get('WYPRZEDAZPRO_IGNORE_BIN_EXPIRY');
        
        switch ($date_filter) {
            case 'expired': if (!$ignoreBinExpiry) $sql->where('wpd.expiry_date < CURDATE()'); else $sql->where('(wpd.expiry_date < CURDATE() AND wpd.regal != "KOSZ")'); break;
            case 'short': $sql->where('wpd.expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL ' . $shortDateThreshold . ' DAY)'); break;
            case '30': $sql->where('DATEDIFF(CURDATE(), wpd.receipt_date) <= 30'); break;
            case '90': $sql->where('DATEDIFF(CURDATE(), wpd.receipt_date) BETWEEN 31 AND 90'); break;
            case 'over_90': $sql->where('DATEDIFF(CURDATE(), wpd.receipt_date) > 90'); break;
        }

        $allowedSortFields = ['ean' => 'p.ean13', 'reference' => 'p.reference', 'quantity' => 'sa.quantity', 'discount' => 'sp.reduction', 'expiry' => 'wpd.expiry_date', 'regal' => 'wpd.regal', 'polka' => 'wpd.polka', 'wms' => 'wpd.quantity_wms'];
        $sort = Tools::getValue('sort');
        $way = Tools::getValue('way') === 'desc' ? 'DESC' : 'ASC';
        if (isset($allowedSortFields[$sort])) $sql->orderBy($allowedSortFields[$sort] . ' ' . $way); else $sql->orderBy('p.reference ASC');

        $rows = Db::getInstance()->executeS($sql);
        $finalRows = []; $today = new DateTime(); $today->setTime(0, 0, 0);

        if ($rows) {
            foreach ($rows as $row) {
                $row['product_url'] = Context::getContext()->link->getProductLink((int)$row['id_product']);
                if (isset($row['regal']) && trim(mb_strtoupper($row['regal'], 'UTF-8')) === 'KOSZ') {
                    if ($ignoreBinExpiry) $row['status'] = 'ok';
                    else { $expiryDate = DateTime::createFromFormat('Y-m-d', $row['expiry_date']); if ($expiryDate && $expiryDate < $today) $row['status'] = 'expired'; elseif ($expiryDate && $today->diff($expiryDate)->days < $shortDateThreshold) $row['status'] = 'short_date'; else $row['status'] = 'ok'; }
                } else {
                    $expiryDate = DateTime::createFromFormat('Y-m-d', $row['expiry_date']);
                    if ($expiryDate && $expiryDate < $today) $row['status'] = 'expired'; elseif ($expiryDate && $today->diff($expiryDate)->days < $shortDateThreshold) $row['status'] = 'short_date'; else $row['status'] = 'ok';
                }
                $finalRows[] = $row;
            }
        }
        return $finalRows;
    }

    /**
     * ZAKTUALIZOWANA METODA: Pobiera produkty KOSZ + EAN z tabeli WMS (backup)
     */
    public function getBinProducts($id_lang, $id_shop)
    {
        $sql = new DbQuery();
        // DODANO: wpd.ean as wms_ean (pobieramy EAN zapisany w tabeli modułu na wypadek usunięcia produktu z bazy)
        $sql->select('p.id_product, p.reference, sa.quantity as quantity_presta, wpd.quantity_wms, pl.name, p.ean13');
        $sql->select('wpd.id_product as wms_id_product, wpd.ean as wms_ean, wpd.expiry_date, wpd.receipt_date, wpd.regal, wpd.polka, wpd.sku');
        
        $sql->from('wyprzedazpro_product_details', 'wpd');
        $sql->leftJoin('product', 'p', 'p.id_product = wpd.id_product');
        $sql->leftJoin('product_lang', 'pl', 'pl.id_product = p.id_product AND pl.id_lang = '.(int)$id_lang);
        $sql->leftJoin('stock_available', 'sa', 'sa.id_product = p.id_product AND sa.id_product_attribute = 0 AND sa.id_shop = '.(int)$id_shop);
        
        $sql->where("wpd.regal LIKE 'KOSZ'");
        $sql->orderBy('wpd.expiry_date ASC');

        $rows = Db::getInstance()->executeS($sql);
        
        $finalRows = [];
        if ($rows) {
            foreach ($rows as $row) {
                if ($row['id_product']) {
                    $row['product_url'] = Context::getContext()->link->getAdminLink('AdminProducts') . '&updateproduct&id_product=' . (int)$row['id_product'];
                } else {
                    $row['product_url'] = '#';
                    $row['name'] = '[Produkt usunięty z bazy sklepu]';
                }
                
                // LOGIKA EAN: Jeśli jest w Preście to weź z Presty, jak nie to z tabeli WMS
                $row['display_ean'] = (!empty($row['ean13']) && $row['ean13'] != '') ? $row['ean13'] : $row['wms_ean'];

                // ID do kasowania (bierzemy ID z tabeli WMS, bo w tabeli products może go nie być)
                $row['delete_id'] = $row['wms_id_product'];
                $finalRows[] = $row;
            }
        }
        return $finalRows;
    }

    public function getDuplicates($id_lang)
    {
        $id_shop = (int)Context::getContext()->shop->id;

        $sql = "SELECT 
                    ean, 
                    expiry_date, 
                    receipt_date, 
                    regal, 
                    polka, 
                    SUM(quantity) as loc_qty
                FROM `" . _DB_PREFIX_ . self::TABLE_DUPES . "`
                GROUP BY ean, expiry_date, regal, polka
                ORDER BY ean ASC, expiry_date ASC, regal ASC";

        $rows = Db::getInstance()->executeS($sql);
        $grouped = [];

        if ($rows) {
            foreach ($rows as $r) {
                $key = $r['ean'] . '_' . $r['expiry_date'];

                if (!isset($grouped[$key])) {
                    $grouped[$key] = [
                        'ean' => $r['ean'],
                        'expiry_date' => $r['expiry_date'],
                        'receipt_date' => $r['receipt_date'], 
                        'total_quantity' => 0,
                        'locations_arr' => [],
                        'name' => '', 
                        'product_url' => '',
                        'id_product' => 0
                    ];

                    $id_product = (int)Product::getIdByEan13($r['ean']);

                    if ($id_product > 0) {
                        $name = Db::getInstance()->getValue('
                            SELECT name FROM `'._DB_PREFIX_.'product_lang`
                            WHERE id_product = ' . $id_product . '
                            AND id_lang = ' . (int)$id_lang . '
                            AND id_shop = ' . (int)$id_shop
                        );

                        $grouped[$key]['name'] = $name;
                        $grouped[$key]['id_product'] = $id_product;
                        $grouped[$key]['product_url'] = Context::getContext()->link->getAdminLink('AdminProducts') . '&updateproduct&id_product=' . $id_product;
                    }
                }

                $grouped[$key]['total_quantity'] += (int)$r['loc_qty'];
                $locString = $r['regal'] . '/' . $r['polka'] . ' <b>(' . (int)$r['loc_qty'] . ')</b>';
                $grouped[$key]['locations_arr'][] = $locString;
            }
        }

        $final_duplicates = [];
        foreach ($grouped as $g) {
            $g['locations'] = implode(', ', $g['locations_arr']);
            $g['reduction'] = WmsCalculator::getDiscountByDates($g['expiry_date'], $g['receipt_date'], 'MAG');
            $final_duplicates[] = $g;
        }

        return $final_duplicates;
    }

    public function getNotFoundProducts()
    {
        $sql = new DbQuery();
        $sql->select('*');
        $sql->from(self::TABLE_NOTF);
        $allowedSortFields = ['ean' => 'ean', 'quantity' => 'quantity', 'expiry_date' => 'expiry_date', 'receipt_date' => 'receipt_date'];
        $sort = Tools::getValue('sort_not_found', 'expiry_date');
        $way = Tools::getValue('way_not_found', 'ASC');
        if (isset($allowedSortFields[$sort])) {
            $sql->orderBy($allowedSortFields[$sort] . ' ' . pSQL($way));
        } else {
            $sql->orderBy('expiry_date ASC');
        }
        return Db::getInstance()->executeS($sql);
    }

    public function getImportHistory($limit = 100)
    {
        return Db::getInstance()->executeS('SELECT * FROM `'._DB_PREFIX_.self::TABLE_HISTORY.'` ORDER BY date_add DESC LIMIT '.(int)$limit);
    }

    /**
     * Usuwa pojedynczy rekord z KOSZA
     */
    public function deleteBinProduct($id_product)
    {
        return Db::getInstance()->execute('
            DELETE FROM `' . _DB_PREFIX_ . self::TABLE_DETAILS . '` 
            WHERE `id_product` = ' . (int)$id_product . ' 
            AND `regal` LIKE "KOSZ"
        ');
    }

    /**
     * NOWA METODA: MASOWE USUWANIE Z KOSZA
     * Usuwa WSZYSTKO co ma w regale słowo KOSZ
     */
    public function deleteAllBinProducts()
    {
        return Db::getInstance()->execute('
            DELETE FROM `' . _DB_PREFIX_ . self::TABLE_DETAILS . '` 
            WHERE `regal` LIKE "KOSZ"
        ');
    }
}