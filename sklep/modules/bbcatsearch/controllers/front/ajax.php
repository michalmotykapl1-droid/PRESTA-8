<?php
/**
 * BB Category Search – AJAX controller (PrestaShop 8.2.1)
 * Wersja: v1.0.10 (fix: poprawne zbieranie podkategorii dla with_children=1)
 * - Szukanie TYLKO po nazwie produktu (pl.name), case- i accent-insensitive
 * - Tokenizacja frazy (AND)
 * - Zakres: bieżąca kategoria + opcjonalnie całe poddrzewo (IN (...))
 * - Filtry diet: AND (produkt musi mieć WSZYSTKIE wybrane cechy)
 * - Logi: /modules/bbcatsearch/var/debug.log
 */
class BbcatsearchAjaxModuleFrontController extends ModuleFrontController
{
    /** Dozwolone ID cech (feature) do filtrowania */
    protected $allowedFeatures = [13,14,15,16,17,18,20,22];
    /** Plik logu */
    protected $logFile;

    public function init()
    {
        parent::init();
        $this->ajax = true;
        $this->logFile = _PS_MODULE_DIR_.'bbcatsearch/var/debug.log';
    }

    protected function logLine($msg)
    {
        try {
            $dir = dirname($this->logFile);
            if (!is_dir($dir)) {
                @mkdir($dir, 0775, true);
            }
            @file_put_contents($this->logFile, '['.date('Y-m-d H:i:s').'] '.$msg.PHP_EOL, FILE_APPEND);
        } catch (Exception $e) {
            // ignore
        }
    }

    public function initContent()
    {
        parent::initContent();
        header('Content-Type: text/html; charset=utf-8');

        $ctx      = $this->context;
        $idShop   = (int) $ctx->shop->id;
        $idLang   = (int) $ctx->language->id;
        $link     = $ctx->link;

        $term         = trim((string) Tools::getValue('s', ''));
        $idCategory   = (int) Tools::getValue('id_category', 0);
        $limit        = (int) Tools::getValue('limit', 0);
        $withChildren = (int) Tools::getValue('with_children', 1);
        $imgType      = (string) Tools::getValue('img_type', 'home_default');

        // features jako tablica lub CSV
        $rawFeatures = Tools::getValue('features');
        $selected    = [];
        if (is_array($rawFeatures)) {
            $selected = $rawFeatures;
        } elseif (is_string($rawFeatures) && trim($rawFeatures) !== '') {
            $selected = explode(',', $rawFeatures);
        }
        $tmp = [];
        foreach ($selected as $idf) {
            $idf = (int) $idf;
            if ($idf && in_array($idf, $this->allowedFeatures, true)) {
                $tmp[] = $idf;
            }
        }
        $selected = array_values(array_unique($tmp));

        $this->logLine('REQ: cat='.$idCategory.' term="'.str_replace(["\n","\r"], ' ', $term).'" feats='.json_encode($selected).' withChildren='.$withChildren.' limit='.$limit);

        if ($idCategory <= 0) {
            die('');
        }

        // ===== KATEGORIE: bieżąca + całe poddrzewo (robust: obsługa obiektów Category i tablic)
        $catIds = [$idCategory];
        if ($withChildren) {
            try {
                $cat = new Category($idCategory, $idLang);
                // W PS8 getAllChildren może zwracać tablice Category lub tablice asocjacyjne.
                $children = $cat->getAllChildren($idLang);
                if (is_array($children)) {
                    foreach ($children as $c) {
                        if (is_object($c)) {
                            // Category ma zazwyczaj ->id (alias id_category)
                            if (isset($c->id) && (int)$c->id) {
                                $catIds[] = (int)$c->id;
                            } elseif (isset($c->id_category) && (int)$c->id_category) {
                                $catIds[] = (int)$c->id_category;
                            }
                        } elseif (is_array($c) && isset($c['id_category'])) {
                            $catIds[] = (int)$c['id_category'];
                        }
                    }
                }
                // Fallback ultra-bezpieczny: zapytanie po nleft/nright, jeśli dalej jest tylko 1 id
                if (count($catIds) === 1) {
                    $row = Db::getInstance()->getRow('SELECT nleft, nright FROM '._DB_PREFIX_.'category WHERE id_category='.(int)$idCategory);
                    if ($row && isset($row['nleft'], $row['nright'])) {
                        $rows = Db::getInstance()->executeS('SELECT id_category FROM '._DB_PREFIX_.'category WHERE nleft BETWEEN '.(int)$row['nleft'].' AND '.(int)$row['nright']);
                        if (is_array($rows)) {
                            foreach ($rows as $r) {
                                $cid = (int)$r['id_category'];
                                if ($cid) $catIds[] = $cid;
                            }
                        }
                    }
                }
            } catch (Exception $e) {
                // jeżeli coś pójdzie nie tak, co najmniej zostaje samo $idCategory
            }
        }
        $catIds = array_values(array_unique(array_filter($catIds)));
        $catIdsIn = implode(',', array_map('intval', $catIds));
        $this->logLine('CAT_IDS: '.$catIdsIn);

        // ===== Fraza → tokenizacja i LIKE AND
        $tokens = [];
        if ($term !== '') {
            foreach (preg_split('/\s+/', $term) as $t) {
                $t = trim($t);
                if ($t !== '') {
                    $tokens[] = pSQL($t, true);
                }
            }
        }

        // ===== FROM / WHERE
        $from = '
            FROM '._DB_PREFIX_.'product p
            INNER JOIN '._DB_PREFIX_.'product_shop ps
                ON (ps.id_product = p.id_product AND ps.id_shop = '.(int)$idShop.')
            INNER JOIN '._DB_PREFIX_.'product_lang pl
                ON (pl.id_product = p.id_product AND pl.id_lang = '.(int)$idLang.' AND pl.id_shop = '.(int)$idShop.')
            INNER JOIN '._DB_PREFIX_.'category_product cp
                ON (cp.id_product = p.id_product AND cp.id_category IN ('.$catIdsIn.'))
        ';

        $where = ' WHERE ps.active = 1 AND ps.visibility IN ("both","catalog") ';
        if (!empty($tokens)) {
            $likes = [];
            foreach ($tokens as $t) {
                $likes[] = 'pl.name LIKE "%'.$t.'%"';
            }
            $where .= ' AND ('.implode(' AND ', $likes).')';
        }

        // ===== Filtry diet (AND) — sub-join z cnt
        $join = '';
        if (!empty($selected)) {
            $in  = implode(',', array_map('intval', $selected));
            $cnt = (int) count($selected);
            $join = '
                INNER JOIN (
                    SELECT fp.id_product, COUNT(DISTINCT fv.id_feature) AS cnt
                    FROM '._DB_PREFIX_.'feature_product fp
                    INNER JOIN '._DB_PREFIX_.'feature_value fv
                        ON (fv.id_feature_value = fp.id_feature_value AND fv.id_feature IN ('.$in.'))
                    GROUP BY fp.id_product
                ) AS f ON (f.id_product = p.id_product)
            ';
            $where .= ' AND f.cnt = '.$cnt;
        }

        // ===== COUNT
        $sqlCount = 'SELECT COUNT(DISTINCT p.id_product) '.$from.$join.$where;
        $totalAll = (int) Db::getInstance()->getValue($sqlCount);
        $this->logLine('SQL COUNT: '.$sqlCount);
        $this->logLine('COUNT totalAll='.$totalAll);

        // ===== IDS
        $sqlIds = 'SELECT DISTINCT p.id_product '.$from.$join.$where.' ORDER BY pl.name ASC '.($limit > 0 ? 'LIMIT '.(int)$limit : '');
        $rowsIds = Db::getInstance()->executeS($sqlIds);
        $ids = [];
        if (is_array($rowsIds)) {
            foreach ($rowsIds as $r) {
                $ids[] = (int) $r['id_product'];
            }
        }
        $this->logLine('SQL IDS: '.$sqlIds);
        $this->logLine('IDS: ['.implode(',', $ids).']');

        // ===== Dalsze dane do kart
        $map = [];
        if (!empty($ids)) {
            $idsIn = implode(',', array_map('intval', $ids));
            $rows = Db::getInstance()->executeS('
                SELECT cp.id_product, cp.id_category
                FROM '._DB_PREFIX_.'category_product cp
                WHERE cp.id_product IN ('.$idsIn.')
            ');
            if (is_array($rows)) {
                foreach ($rows as $r) {
                    $pid = (int) $r['id_product'];
                    $cid = (int) $r['id_category'];
                    if (!isset($map[$pid])) $map[$pid] = [];
                    $map[$pid][$cid] = true;
                }
            }
        }

        $items = [];
        foreach ($ids as $idp) {
            $row = Db::getInstance()->getRow('
                SELECT p.id_product, pl.name, pl.link_rewrite
                FROM '._DB_PREFIX_.'product p
                INNER JOIN '._DB_PREFIX_.'product_lang pl
                    ON (pl.id_product = p.id_product AND pl.id_lang = '.(int)$idLang.' AND pl.id_shop = '.(int)$idShop.')
                WHERE p.id_product = '.(int)$idp.'
            ');
            if (!$row) continue;

            $url   = $link->getProductLink((int)$idp, $row['link_rewrite']);
            $cover = Product::getCover((int)$idp);
            $img   = '';
            if ($cover && !empty($cover['id_image'])) {
                $img = $link->getImageLink($row['link_rewrite'], (int)$cover['id_image'], $imgType);
            }
            $price = Product::getPriceStatic((int)$idp, true);
            $base  = Product::getPriceStatic((int)$idp, true, null, 6, null, false, false);
            $old   = ($base > $price + 0.001) ? $base : 0;

            $items[] = [
                'id_product' => (int) $idp,
                'name'       => $row['name'],
                'url'        => $url,
                'img'        => $img,
                'price'      => Tools::displayPrice($price),
                'old_price'  => $old ? Tools::displayPrice($old) : '',
                'flags'      => [
                    'sale'       => !empty($map[$idp][45]),
                    'short_date' => !empty($map[$idp][180]),
                ],
            ];
        }

        $catObj  = new Category($idCategory, $idLang);
        $available = $this->getAvailableFeaturesForProducts($ids);
        $filters   = $this->buildFilters($idLang, $selected, $available);

        $this->context->smarty->assign([
            'bb_items'      => $items,
            'shown'         => (int) count($items),
            'total_all'     => $totalAll,
            'searchTerm'    => $term,
            'id_category'   => $idCategory,
            'category_name' => isset($catObj->name) ? (string)$catObj->name : '',
            'scope_children'=> (int) $withChildren,
            'filters'       => $filters,
        ]);

        die($this->context->smarty->fetch('module:bbcatsearch/views/templates/front/results.tpl'));
    }


    /**
     * Zwraca listę cech (id_feature), które realnie występują
     * w produktach z wyników (tylko z allowedFeatures).
     */
    protected function getAvailableFeaturesForProducts(array $ids)
    {
        $out = [];

        if (empty($ids) || empty($this->allowedFeatures)) {
            return $out;
        }

        $idsIn  = implode(',', array_map('intval', $ids));
        $featIn = implode(',', array_map('intval', $this->allowedFeatures));

        $sql = 'SELECT DISTINCT fv.id_feature
                FROM '._DB_PREFIX_.'feature_product fp
                INNER JOIN '._DB_PREFIX_.'feature_value fv
                    ON (fv.id_feature_value = fp.id_feature_value)
                WHERE fp.id_product IN ('.$idsIn.')
                  AND fv.id_feature IN ('.$featIn.')';

        $rows = Db::getInstance()->executeS($sql);
        if (is_array($rows)) {
            foreach ($rows as $r) {
                $out[(int) $r['id_feature']] = true;
            }
        }

        return $out;
    }


    /** Zbiera etykiety filtrów */
    protected function buildFilters($idLang, array $selected, array $available = [])
    {
        $selected = array_map('intval', $selected);
        $availMap = [];
        if (!empty($available)) {
            foreach ($available as $k => $v) {
                $availMap[(int) $k] = (bool) $v;
            }
        }

        if (empty($this->allowedFeatures)) {
            return [];
        }

        $in   = implode(',', array_map('intval', $this->allowedFeatures));
        $rows = Db::getInstance()->executeS('
            SELECT f.id_feature, fl.name
            FROM '._DB_PREFIX_.'feature f
            INNER JOIN '._DB_PREFIX_.'feature_lang fl
                ON (fl.id_feature = f.id_feature AND fl.id_lang = '.(int)$idLang.')
            WHERE f.id_feature IN ('.$in.')
            ORDER BY fl.name ASC
        ');

        $out = [];
        if (is_array($rows)) {
            foreach ($rows as $r) {
                $idf     = (int) $r['id_feature'];
                $checked = in_array($idf, $selected, true);
                $isAv    = !empty($availMap[$idf]);

                $out[] = [
                    'id_feature' => $idf,
                    'name'       => $r['name'],
                    'checked'    => $checked,
                    // cecha aktywna jeśli jest w aktualnych wynikach
                    // lub jest zaznaczona (żeby dało się ją odkliknąć)
                    'enabled'    => ($isAv || $checked),
                ];
            }
        }
        return $out;
    }

}
