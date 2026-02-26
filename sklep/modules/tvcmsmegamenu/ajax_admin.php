<?php
// modules/tvcmsmegamenu/ajax_admin.php
use PrestaShop\PrestaShop\Adapter\Image\ImageRetriever;
use PrestaShop\PrestaShop\Adapter\Presenter\Product\ProductLazyArray;
use PrestaShop\PrestaShop\Adapter\Presenter\Product\ProductPresenterFactory;

require_once dirname(__FILE__).'/../../config/config.inc.php';
require_once dirname(__FILE__).'/../../init.php';

$ctx = Context::getContext();
if (!$ctx->employee) { http_response_code(403); die(''); }

$config = Tools::getValue('config');
try { $cfg = json_decode($config, true); } catch (Exception $e){ $cfg = []; }
if (!is_array($cfg)) { $cfg = []; }

$limit  = isset($cfg['limit']) ? (int)$cfg['limit'] : 8;
$layout = isset($cfg['layout']) ? $cfg['layout'] : 'grid';
$src    = isset($cfg['source']) ? $cfg['source'] : 'new';
$refid  = isset($cfg['refid']) ? (int)$cfg['refid'] : 0;

$idLang = (int)$ctx->language->id;
$idShop = (int)$ctx->shop->id;

$products = [];

// Helpers
$link = new Link();

function loadBasic($rows, $idLang, $link){
  $out = [];
  foreach ($rows as $r) {
    $url = $link->getProductLink((int)$r['id_product']);
    $coverId = (int)Db::getInstance()->getValue('
      SELECT image_shop.id_image
      FROM '._DB_PREFIX_.'image i
      INNER JOIN '._DB_PREFIX_.'image_shop image_shop ON (image_shop.id_image = i.id_image AND image_shop.id_shop = '.(int)Context::getContext()->shop->id.')
      WHERE i.id_product = '.(int)$r['id_product'].' AND i.cover = 1
    ');
    $cover = null;
    if ($coverId) {
      $cover = [
        'bySize' => [
          'small_default' => [
            'url' => $link->getImageLink($r['link_rewrite'], $coverId, 'small_default')
          ]
        ]
      ];
    }
    $out[] = [
      'id_product' => (int)$r['id_product'],
      'name' => $r['name'],
      'url'  => $url,
      'price'=> Tools::displayPrice(Product::getPriceStatic((int)$r['id_product'])),
      'has_discount' => (bool)((float)$r['reduction'] > 0 || (int)$r['on_sale']),
      'cover' => $cover,
    ];
  }
  return $out;
}

// Source switch
if ($src === 'new') {
  $rows = Product::getNewProducts($idLang, 0, $limit);
  $products = loadBasic($rows ?: [], $idLang, $link);
} elseif ($src === 'best') {
  $rows = ProductSale::getBestSalesLight($idLang, 0, $limit);
  $products = loadBasic($rows ?: [], $idLang, $link);
} elseif ($src === 'special') {
  $rows = Product::getPricesDrop($idLang, 0, $limit, false);
  if (!$rows) { $rows = []; }
  // enrich with basic fields expected above
  foreach ($rows as &$r) {
    if (!isset($r['link_rewrite'])) {
      $r['link_rewrite'] = Db::getInstance()->getValue('SELECT link_rewrite FROM '._DB_PREFIX_.'product_lang WHERE id_product='.(int)$r['id_product'].' AND id_lang='.(int)$idLang);
    }
    if (!isset($r['name'])) {
      $r['name'] = Db::getInstance()->getValue('SELECT name FROM '._DB_PREFIX_.'product_lang WHERE id_product='.(int)$r['id_product'].' AND id_lang='.(int)$idLang);
    }
    $r['reduction'] = 1.0;
    $r['on_sale'] = 1;
  }
  $products = loadBasic($rows, $idLang, $link);
} elseif ($src === 'tag' && $refid) {
  $sql = 'SELECT p.id_product, pl.name, pl.link_rewrite, ps.on_sale, 0 AS reduction
          FROM '._DB_PREFIX_.'product p
          INNER JOIN '._DB_PREFIX_.'product_shop ps ON (ps.id_product=p.id_product AND ps.id_shop='.(int)$idShop.')
          INNER JOIN '._DB_PREFIX_.'product_lang pl ON (pl.id_product=p.id_product AND pl.id_lang='.(int)$idLang.' AND pl.id_shop='.(int)$idShop.')
          INNER JOIN '._DB_PREFIX_.'product_tag pt ON pt.id_product = p.id_product
          WHERE pt.id_tag = '.(int)$refid.'
          ORDER BY p.id_product DESC
          LIMIT '.(int)$limit;
  $rows = Db::getInstance()->executeS($sql);
  $products = loadBasic($rows ?: [], $idLang, $link);
} elseif ($src === 'category' && $refid) {
  $rows = Category::getProducts($refid, $idLang, 1, $limit, 'position');
  if (!$rows) $rows = [];
  foreach ($rows as &$r) { if (!isset($r['reduction'])) $r['reduction'] = 0; if(!isset($r['on_sale'])) $r['on_sale']=0; }
  $products = loadBasic($rows, $idLang, $link);
} else {
  $products = [];
}

// Render via Smarty
$smarty = Context::getContext()->smarty;
$smarty->assign([
  'products' => $products,
  'layout'   => $layout,
  'showPrice'=> !empty($cfg['show_price']),
  'showBadge'=> !empty($cfg['show_badge']),
  'showPromoBadge'=> !empty($cfg['show_badge']),
]);
$template = _PS_MODULE_DIR_.'tvcmsmegamenu/views/templates/hook/_dynproducts.tpl';
if (!file_exists($template)) { die(''); }
echo $smarty->fetch($template);
