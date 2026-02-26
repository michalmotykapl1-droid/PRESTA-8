<?php
/**
 * acm_repair_categories.php (v5 – token OR BO login)
 * Run: /modules/allegrocategorymapper/tools/acm_repair_categories.php?root=2&dry=1&key=bigbio-temp-key-98273
 * If ?key=... matches $ACM_TOOL_KEY below, script runs even if not logged into BO.
 * Otherwise requires Back Office login.
 */

header('Content-Type: application/json; charset=utf-8');
@ini_set('display_errors', '1');
@error_reporting(E_ALL);

// ---- TOKEN ----
$ACM_TOOL_KEY = 'bigbio-temp-key-98273'; // change/delete after use

// Resolve PS root relative to module path
$rootDir = dirname(__FILE__, 4);
$config  = $rootDir . '/config/config.inc.php';
$init    = $rootDir . '/init.php';

if (!file_exists($config) || !file_exists($init)) {
    http_response_code(500);
    die(json_encode(['ok'=>false,'error'=>'Cannot locate PrestaShop core files']));
}
require_once $config;
require_once $init;

try {
    $ctx = Context::getContext();
    $token = (string)Tools::getValue('key', '');
    $hasToken = ($ACM_TOOL_KEY !== '' && hash_equals($ACM_TOOL_KEY, $token));
    $logged = ($ctx->employee && Validate::isLoadedObject($ctx->employee));

    if (!$hasToken && !$logged) {
        http_response_code(403);
        die(json_encode(['ok'=>false,'error'=>'Forbidden (login to Back Office first or provide ?key=...)']));
    }

    $idShop = (int)$ctx->shop->id ?: (int)Configuration::get('PS_SHOP_DEFAULT');
    $root   = (int)Tools::getValue('root', 2);
    $dry    = (int)Tools::getValue('dry', 0);

    $db = Db::getInstance();
    $pref = _DB_PREFIX_;

    $report = [
        'shop' => $idShop,
        'root' => $root,
        'ensured_category_shop' => 0,
        'reparented' => 0,
        'activated' => 0,
        'auth' => $hasToken ? 'token' : 'login'
    ];

    // 1) Ensure category_shop rows exist for this shop
    $missing = $db->executeS("
        SELECT c.id_category, c.id_parent
        FROM {pref}category c
        LEFT JOIN {pref}category_shop cs
          ON cs.id_category = c.id_category AND cs.id_shop = ".$idShop."
        WHERE cs.id_category IS NULL
    ");
    if (!$dry) {
        foreach ((array)$missing as $row) {
            $db->insert('category_shop', [
                'id_category' => (int)$row['id_category'],
                'id_shop'     => $idShop,
                'id_parent'   => (int)$row['id_parent'],
                'position'    => 0,
            ], false, true, Db::REPLACE);
            $report['ensured_category_shop']++;
        }
    } else {
        $report['ensured_category_shop'] = is_array($missing) ? count($missing) : 0;
    }

    // 2) Re-parent orphans
    $orphans = $db->executeS("
        SELECT c.id_category
        FROM {pref}category c
        LEFT JOIN {pref}category p ON p.id_category = c.id_parent
        WHERE p.id_category IS NULL AND c.id_parent NOT IN (0,".$root.")
    ");
    if (!$dry) {
        foreach ((array)$orphans as $row) {
            $db->update('category', ['id_parent' => $root], 'id_category='.(int)$row['id_category']);
            $db->update('category_shop', ['id_parent' => $root], 'id_category='.(int)$row['id_category'].' AND id_shop='.(int)$idShop);
            $report['reparented']++;
        }
    } else {
        $report['reparented'] = is_array($orphans) ? count($orphans) : 0;
    }

    // 3) Activate categories
    if (!$dry) {
        $db->execute("
            UPDATE {pref}category c
            INNER JOIN {pref}category_shop cs ON cs.id_category=c.id_category AND cs.id_shop=".$idShop."
            SET c.active=1
            WHERE c.active=0
        ");
        if (method_exists($db, 'Affected_Rows')) {
            $report['activated'] = (int)$db->Affected_Rows();
        }
    }

    // 4) Rebuild nested tree and clear caches
    if (!$dry) {
        Category::regenerateEntireNtree();
        try { Category::cleanPositions($root); } catch (Exception $e) {}
        try { Tools::clearAllCache(); } catch (Exception $e) {}
        try { Media::clearCache(); } catch (Exception $e) {}
        try { $ctx->smarty->clearCompiledTemplate(); } catch (Exception $e) {}
    }

    echo Tools::jsonEncode(['ok'=>true,'dry'=>(bool)$dry,'report'=>$report]);
} catch (Throwable $e) {
    http_response_code(500);
    die(json_encode(['ok'=>false,'error'=>$e->getMessage(),'trace'=>$e->getTraceAsString()]));
}
