<?php
namespace ACM\Domain;
use Db; use Language; use Tools; use Category;

class CategoryPathBuilder
{
    protected $id_lang;
    public function __construct($id_lang) { $this->id_lang = (int)$id_lang; }

    public function ensureFullPath($rootId, array $allegroPath, $leafAllegroId, $leafName)
    {
        $parent = (int)$rootId;
        $summary = ['created' => [], 'reused' => []];
        if (!empty($allegroPath) && isset($allegroPath[0]['name']) && strtolower($allegroPath[0]['name']) === 'allegro') {
            array_shift($allegroPath);
        }
        foreach ($allegroPath as $node) {
            $name = (string)($node['name'] ?? '');
            $id = (string)($node['id'] ?? '');
            if (!$name) { continue; }
            $result = $this->ensureChild($parent, $id, $name);
            $parent = $result['id'];
            if ($result['status'] === 'created') $summary['created'][] = $result['name'];
            else $summary['reused'][] = $result['name'];
        }
        $summary['id'] = $parent;
        return $summary;
    }

    public function ensureLeaf($rootId, $leafAllegroId, $leafName)
    {
        return $this->ensureChild((int)$rootId, (string)$leafAllegroId, (string)$leafName);
    }

    protected function ensureChild($parentId, $allegroCatId, $name)
    {
        $psId = (int)Db::getInstance()->getValue('SELECT ps_id_category FROM '._DB_PREFIX_.'allegro_category_map WHERE allegro_category_id="'.pSQL($allegroCatId).'"');
        if ($psId > 0) return ['id' => $psId, 'status' => 'reused', 'name' => $name];

        $psId = (int)Db::getInstance()->getValue('SELECT c.id_category FROM '._DB_PREFIX_.'category c INNER JOIN '._DB_PREFIX_.'category_lang cl ON (c.id_category=cl.id_category AND cl.id_lang='.(int)$this->id_lang.') WHERE c.id_parent='.(int)$parentId.' AND cl.name="'.pSQL($name).'"');
        if ($psId > 0) {
            Db::getInstance()->insert('allegro_category_map', ['allegro_category_id' => pSQL($allegroCatId), 'allegro_category_name' => pSQL($name), 'ps_id_category' => (int)$psId, 'created_at' => date('Y-m-d H:i:s')], false, true, Db::REPLACE);
            return ['id' => $psId, 'status' => 'reused', 'name' => $name];
        }

        $cat = new Category();
        $cat->id_parent = (int)$parentId;
        $cat->active = 1;
        $cat->is_root_category = 0;
        foreach (Language::getLanguages(false) as $lang) {
            $cat->name[$lang['id_lang']] = $name;
            $cat->link_rewrite[$lang['id_lang']] = Tools::link_rewrite($name);
        }
        if ($cat->add()) {
            $psId = (int)$cat->id;
            Db::getInstance()->insert('allegro_category_map', ['allegro_category_id' => pSQL($allegroCatId), 'allegro_category_name' => pSQL($name), 'ps_id_category' => (int)$psId, 'created_at' => date('Y-m-d H:i:s')], false, true, Db::REPLACE);
            return ['id' => $psId, 'status' => 'created', 'name' => $name];
        }
        return ['id' => 0, 'status' => 'failed', 'name' => $name];
    }
}