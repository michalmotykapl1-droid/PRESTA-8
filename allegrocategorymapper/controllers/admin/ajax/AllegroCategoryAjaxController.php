<?php
// /modules/allegrocategorymapper/controllers/admin/ajax/AllegroCategoryAjaxController.php
/**
 * Returns batches of product IDs to scan for selected category IDs.
 * Respects:
 * - ACM_SCAN_INCLUDE_INACTIVE (checkbox in config)
 * - ACM_SKIP_DONE (skip products already marked as done)
 * - ACM_SCAN_CHUNK_SIZE (limit per request)
 */
class AdminAllegroCategoryAjaxController extends ModuleAdminController
{
    public function __construct()
    {
        parent::__construct();
        header('Content-Type: application/json; charset=utf-8');
    }

    public function initContent()
    {
        // Security: POST only
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            die(json_encode(['ok' => false, 'error' => 'Invalid method']));
        }

        $ids = isset($_POST['category_ids']) ? $_POST['category_ids'] : [];
        if (!is_array($ids) || empty($ids)) {
            die(json_encode(['ok' => false, 'error' => 'No category ids']));
        }

        $ids = array_map('intval', $ids);
        $ids = array_values(array_unique($ids));

        $idShop          = (int)$this->context->shop->id;
        $includeInactive = (int)Configuration::get('ACM_SCAN_INCLUDE_INACTIVE') ? true : false;
        $skipDone        = (int)Configuration::get('ACM_SKIP_DONE') ? true : false;
        $chunk           = (int)Configuration::get('ACM_SCAN_CHUNK_SIZE');
        if ($chunk <= 0) { $chunk = 100; }

        $db = Db::getInstance();

        $activeWhere = $includeInactive ? '1=1' : 'ps.active = 1';

        $doneTable = _DB_PREFIX_."acm_done";
        $tableExists = (bool)$db->getValue("SHOW TABLES LIKE '".pSQL($doneTable)."'");
        $joinDone = $skipDone && $tableExists ? "LEFT JOIN $doneTable d ON (d.id_product = p.id_product)" : "";
        $whereDone = $skipDone && $tableExists ? "AND d.id_product IS NULL" : "";

        $sql = "
            SELECT DISTINCT p.id_product
            FROM "._DB_PREFIX_."category_product cp
            INNER JOIN "._DB_PREFIX_."product p ON (p.id_product = cp.id_product)
            INNER JOIN "._DB_PREFIX_."product_shop ps ON (ps.id_product = p.id_product AND ps.id_shop = ".(int)$idShop.")
            $joinDone
            WHERE $activeWhere
              AND cp.id_category IN (".implode(',', array_map('intval',$ids)).")
              $whereDone
            ORDER BY p.id_product ASC
            LIMIT ".(int)$chunk."
        ";

        $list = array_map(function($r){ return (int)$r['id_product']; }, $db->executeS($sql));

        die(json_encode(['ok' => true, 'ids' => $list]));
    }
}
