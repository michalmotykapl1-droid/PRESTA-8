<?php
// Simple ajax endpoint for admin preview of dynamic products
// URL: /modules/tvcmsmegamenu/ajax_tvdp.php?action=preview&conf=... JSON
if (!defined('_PS_VERSION_')) {
    require_once dirname(__FILE__, 3) . '/config/config.inc.php';
    require_once dirname(__FILE__, 3) . '/init.php';
}

header('Content-Type: text/html; charset=utf-8');

require_once __DIR__ . '/classes/DynProductProvider.php';

use TvCmsMegaMenu\DynProductProvider;

$action = Tools::getValue('action');
if ($action !== 'preview') {
    http_response_code(400);
    echo 'Bad request';
    exit;
}

$conf = Tools::getValue('conf'); // JSON with {type:5, source, limit, sort, layout, refid, show_price, show_badge}
if (!$conf) {
    echo '<div class="alert alert-warning">Brak konfiguracji podglądu.</div>';
    exit;
}

try {
    $cfg = json_decode($conf, true);
} catch (Exception $e) {
    $cfg = null;
}
if (!$cfg || !is_array($cfg)) {
    echo '<div class="alert alert-warning">Nieprawidłowa konfiguracja.</div>';
    exit;
}

$limit = (int)($cfg['limit'] ?? 8);
$source = (string)($cfg['source'] ?? 'new');
$refid = (int)($cfg['refid'] ?? 0);
$sort = (string)($cfg['sort'] ?? 'position');
$layout = (string)($cfg['layout'] ?? 'list');
$showPrice = (bool)($cfg['show_price'] ?? true);
$showBadge = (bool)($cfg['show_badge'] ?? true);

$products = DynProductProvider::get($source, $limit, $refid, $sort, $showPrice);

// Assign and render
$context = Context::getContext();
$context->smarty->assign([
    'tvdp_products' => $products,
    'tvdp_layout' => $layout,
    'tvdp_show_price' => $showPrice,
    'tvdp_show_badge' => $showBadge,
    'is_admin_preview' => true,
]);

echo $context->smarty->fetch(_PS_MODULE_DIR_ . 'tvcmsmegamenu/views/templates/admin/_dynproducts_preview.tpl');
