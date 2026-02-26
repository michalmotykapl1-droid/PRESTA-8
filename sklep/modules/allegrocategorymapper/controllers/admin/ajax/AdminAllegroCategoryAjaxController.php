<?php
/**
 * AdminAllegroCategoryAjaxController
 * Extends module AJAX with category search from local JSON.
 * Route: index.php?controller=AdminAllegroCategoryAjax&ajax=1&action=searchAllegroCategory&q=makar&limit=20
 */
class AdminAllegroCategoryAjaxController extends ModuleAdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->ajax = true;
        $this->display = 'json';
    }

    public function initContent()
    {
        // Do not call parent::initContent() to avoid rendering HTML
        $action = Tools::getValue('action');
        if ($action === 'searchAllegroCategory') {
            $this->searchAllegroCategory();
            return;
        }

        // Unknown or legacy actions can be handled here if needed.
        $this->jsonResponse(['ok' => true, 'info' => 'AJAX endpoint alive']);
    }

    /**
     * Search Allegro category tree stored in module cache file.
     * Expected JSON structure (flat):
     * [
     *   {"id":"261417","name":"Produkty sypkie","path":"Supermarket > Produkty spożywcze > ... > Produkty sypkie"},
     *   ...
     * ]
     * If your JSON is nested, pre-process into a flat list in PHP.
     */
    protected function searchAllegroCategory()
    {
        $q = Tools::getValue('q', '');
        $q = trim($q);
        $limit = (int)Tools::getValue('limit', 20);
        if ($limit <= 0) { $limit = 20; }
        if ($q === '') {
            return $this->jsonResponse(['ok' => true, 'data' => []]);
        }

        $path = $this->locateCategoriesFile();
        if (!$path) {
            return $this->jsonError('Categories file not found', 404);
        }

        $json = @file_get_contents($path);
        if ($json === false) {
            return $this->jsonError('Categories file read error', 500);
        }

        $data = json_decode($json, true);
        if (!is_array($data)) {
            return $this->jsonError('Bad categories JSON', 500);
        }

        $needle = Tools::strtolower($q);
        $out = [];

        // Try flat first
        if (isset($data[0]) && is_array($data[0]) && (isset($data[0]['id']) || isset($data[0]['name']) || isset($data[0]['path']))) {
            foreach ($data as $row) {
                $id = isset($row['id']) ? (string)$row['id'] : '';
                $label = '';
                if (!empty($row['path'])) {
                    $label = $row['path'];
                } elseif (!empty($row['name'])) {
                    $label = $row['name'];
                }
                if ($label === '') { continue; }
                $hay = Tools::strtolower($label);
                if (strpos($hay, $needle) !== false) {
                    $out[] = ['id' => $id, 'label' => $label];
                    if (count($out) >= $limit) break;
                }
            }
        } else {
            // Nested structure fallback
            $this->walkCategoriesNested($data, function($id, $name, $path) use (&$out, $needle, $limit) {
                $label = $path ? $path : $name;
                $hay = Tools::strtolower($label);
                if (strpos($hay, $needle) !== false) {
                    $out[] = ['id' => (string)$id, 'label' => $label];
                }
                return (count($out) >= $limit);
            });
            if (count($out) > $limit) { $out = array_slice($out, 0, $limit); }
        }

        return $this->jsonResponse(['ok' => true, 'data' => $out]);
    }

    protected function locateCategoriesFile()
    {
        $moduleDir = _PS_MODULE_DIR_.'allegrocategorymapper/';
        $candidates = [
            $moduleDir.'cache/allegro_categories.json',
            $moduleDir.'data/allegro_categories.json',
            $moduleDir.'data/allegro_categories.cache.json',
            $moduleDir.'var/allegro_categories.json',
        ];
        foreach ($candidates as $p) {
            if (file_exists($p)) return $p;
        }
        return null;
    }

    protected function walkCategoriesNested($node, $callback, $path = [])
    {
        if (!is_array($node)) return false;
        // Accept structures like {"id":"...","name":"...","children":[...]}
        $id = isset($node['id']) ? $node['id'] : null;
        $name = isset($node['name']) ? $node['name'] : null;
        $children = isset($node['children']) && is_array($node['children']) ? $node['children'] : null;

        if ($name !== null) {
            $fullPath = implode(' > ', array_merge($path, [$name]));
            if ($id !== null) {
                if (call_user_func($callback, $id, $name, $fullPath)) {
                    return true; // stop
                }
            }
        }

        if ($children) {
            foreach ($children as $child) {
                if ($this->walkCategoriesNested($child, $callback, $name !== null ? array_merge($path, [$name]) : $path)) {
                    return true;
                }
            }
        } else {
            // If it's a pure array (list), iterate
            foreach ($node as $child) {
                if ($this->walkCategoriesNested($child, $callback, $path)) {
                    return true;
                }
            }
        }
        return false;
    }

    protected function jsonResponse($payload)
    {
        header('Content-Type: application/json; charset=utf-8');
        die(Tools::jsonEncode($payload));
    }

    protected function jsonError($message, $code = 400)
    {
        header('Content-Type: application/json; charset=utf-8', true, $code);
        die(Tools::jsonEncode(['ok' => false, 'error' => $message]));
    }
}
