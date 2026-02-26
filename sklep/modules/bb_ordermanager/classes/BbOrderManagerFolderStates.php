<?php
/**
 * BbOrderManagerFolderStates
 *
 * Mapowanie: Folder (BIGBIO Manager) -> OrderState (PrestaShop)
 *
 * Wersja v2:
 * - mapowanie w Configuration jest kluczem po ID folderu (stabilne, niezależne od nazwy)
 * - dla kompatybilności z istniejącym kodem, getMap() zwraca nadal mapę label => id_order_state
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once _PS_MODULE_DIR_ . 'bb_ordermanager/classes/BbOrderManagerFolders.php';

class BbOrderManagerFolderStates
{
    /**
     * JSON: { "folder_id": 123, ... }
     * (legacy: { "Nazwa folderu": 123, ... } – migrowane automatycznie)
     */
    const CONFIG_KEY = 'BB_OM_FOLDER_STATE_MAP';

    /**
     * Definicje folderów (id, label, color).
     * @return array<int,array{id:string,label:string,color:string}>
     */
    public static function getFolderDefinitions()
    {
        $defs = BbOrderManagerFolders::getDefinitions();
        $out = [];
        foreach ($defs as $d) {
            $id = isset($d['id']) ? (string) $d['id'] : '';
            $label = isset($d['label']) ? (string) $d['label'] : '';
            if ($id === '' || $label === '') {
                continue;
            }
            $hex = isset($d['color_hex']) ? (string) $d['color_hex'] : '#64748b';
            if ($hex === '' || $hex[0] !== '#') {
                $hex = '#64748b';
            }
            $out[] = ['id' => $id, 'label' => $label, 'color' => $hex];
        }
        return $out;
    }

    /**
     * Mapa folder_id => id_order_state (po ensure).
     * @return array<string,int>
     */
    public static function getMapByFolderId($idLang = null)
    {
        $map = self::readMapFromConfig();
        return self::ensureByFolderId($map, $idLang);
    }

    /**
     * Mapa label => id_order_state (kompatybilność).
     * @return array<string,int>
     */
    public static function getMap($idLang = null)
    {
        $byId = self::getMapByFolderId($idLang);
        $out = [];
        foreach (self::getFolderDefinitions() as $def) {
            $fid = (string) $def['id'];
            $label = (string) $def['label'];
            if ($fid !== '' && $label !== '' && isset($byId[$fid])) {
                $out[$label] = (int) $byId[$fid];
            }
        }
        return $out;
    }

    /**
     * ID statusu dla folderu (po label – kompatybilność).
     */
    public static function getStateId($folderName, $idLang = null)
    {
        $folderName = (string) $folderName;
        $map = self::getMap($idLang);
        return isset($map[$folderName]) ? (int) $map[$folderName] : 0;
    }

    /**
     * Reverse lookup: label folderu po id_order_state.
     */
    public static function getFolderByStateId($idOrderState, $idLang = null)
    {
        $idOrderState = (int) $idOrderState;
        if ($idOrderState <= 0) {
            return '';
        }
        $map = self::getMap($idLang);
        foreach ($map as $folder => $sid) {
            if ((int) $sid === $idOrderState) {
                return (string) $folder;
            }
        }
        return '';
    }

    /**
     * Zapis mapy folder_id => id_order_state.
     */
    public static function saveMapByFolderId($map)
    {
        if (!is_array($map)) {
            $map = [];
        }
        $clean = [];
        foreach ($map as $k => $v) {
            $k = trim((string) $k);
            $id = (int) $v;
            if ($k !== '' && $id > 0) {
                $clean[$k] = $id;
            }
        }
        $json = json_encode($clean);
        if ($json === false) {
            $json = '{}';
        }

        // Zapis odporny na rozjazd kontekstu multistore (analogicznie jak w BbOrderManagerFolders).
        self::updateConfigValueAllScopes(self::CONFIG_KEY, $json);
    }

    /**
     * Zapisz Configuration w wielu scope (aktualny + globalny + bieżący shop),
     * żeby FO/BO nie rozjeżdżały się na innych wartościach przy multistore.
     */
    private static function updateConfigValueAllScopes($key, $value)
    {
        // 1) Aktualny kontekst
        try {
            Configuration::updateValue((string) $key, $value);
        } catch (Exception $e) {
            // ignore
        }

        // 2) Global
        try {
            if (method_exists('Configuration', 'updateGlobalValue')) {
                Configuration::updateGlobalValue((string) $key, $value);
            }
        } catch (Exception $e) {
            // ignore
        }

        // 3) Konkretna instancja sklepu
        try {
            if (class_exists('Shop') && method_exists('Shop', 'isFeatureActive') && Shop::isFeatureActive()) {
                $ctx = Context::getContext();
                if ($ctx && isset($ctx->shop) && (int) $ctx->shop->id > 0) {
                    $idShop = (int) $ctx->shop->id;
                    $idShopGroup = isset($ctx->shop->id_shop_group) ? (int) $ctx->shop->id_shop_group : null;
                    Configuration::updateValue((string) $key, $value, false, $idShopGroup, $idShop);
                }
            }
        } catch (Exception $e) {
            // ignore
        }
    }

    /**
     * Upewnij się, że mapa folder_id => id_order_state jest kompletna.
     * - wykrywa status po nazwie (label)
     * - jeśli nie istnieje, tworzy nowy OrderState
     *
     * @param array<string,int>|null $map
     * @return array<string,int>
     */
    public static function ensureByFolderId($map = null, $idLang = null)
    {
        if (!is_array($map)) {
            $map = [];
        }

        $idLang = (int) $idLang;
        if ($idLang <= 0) {
            $ctx = Context::getContext();
            $idLang = ($ctx && $ctx->language && (int) $ctx->language->id > 0)
                ? (int) $ctx->language->id
                : (int) Configuration::get('PS_LANG_DEFAULT');
            if ($idLang <= 0) {
                $idLang = 1;
            }
        }

        $changed = false;

        foreach (self::getFolderDefinitions() as $def) {
            $fid = (string) $def['id'];
            $label = (string) $def['label'];
            $currentId = isset($map[$fid]) ? (int) $map[$fid] : 0;

            if ($currentId > 0 && self::orderStateExists($currentId)) {
                continue;
            }

            // 1) znajdź po nazwie
            $foundId = self::findOrderStateIdByName($label);
            if ($foundId > 0) {
                $map[$fid] = (int) $foundId;
                $changed = true;
                continue;
            }

            // 2) utwórz nowy
            $createdId = self::createOrderState($label, isset($def['color']) ? (string) $def['color'] : '#64748b');
            if ($createdId > 0) {
                $map[$fid] = (int) $createdId;
                $changed = true;
            }
        }

        if ($changed) {
            self::saveMapByFolderId($map);
        }

        return $map;
    }

    /**
     * Wczytaj mapę z Configuration + migracja legacy (klucze po label).
     * @return array<string,int>
     */
    private static function readMapFromConfig()
    {
        $raw = trim((string) Configuration::get(self::CONFIG_KEY));
        if ($raw === '') {
            return [];
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            return [];
        }

        $out = [];
        $hasLegacyKeys = false;

        foreach ($decoded as $k => $id) {
            $id = (int) $id;
            if ($id <= 0) {
                continue;
            }

            $key = (string) $k;
            // Heurystyka: jeśli klucz zawiera spacje/nawiasy, to raczej label (legacy)
            if (preg_match('/\s|\(|\)|\./', $key)) {
                $hasLegacyKeys = true;
            }

            $out[$key] = $id;
        }

        if ($hasLegacyKeys) {
            // migracja: label -> folder_id
            $migrated = [];
            foreach (self::getFolderDefinitions() as $def) {
                $fid = (string) $def['id'];
                $label = (string) $def['label'];
                if ($fid === '' || $label === '') {
                    continue;
                }
                if (isset($out[$fid])) {
                    $migrated[$fid] = (int) $out[$fid];
                    continue;
                }
                if (isset($out[$label])) {
                    $migrated[$fid] = (int) $out[$label];
                }
            }
            self::saveMapByFolderId($migrated);
            return $migrated;
        }

        return $out;
    }

    private static function orderStateExists($id)
    {
        $id = (int) $id;
        if ($id <= 0) {
            return false;
        }
        try {
            $exists = (int) Db::getInstance()->getValue('SELECT COUNT(*) FROM `' . _DB_PREFIX_ . 'order_state` WHERE id_order_state = ' . (int) $id);
            return $exists > 0;
        } catch (Exception $e) {
            return false;
        }
    }

    private static function findOrderStateIdByName($name)
    {
        $name = trim((string) $name);
        if ($name === '') {
            return 0;
        }
        try {
            $sql = 'SELECT osl.id_order_state
                    FROM `' . _DB_PREFIX_ . 'order_state_lang` osl
                    WHERE osl.name = "' . pSQL($name) . '"
                    ORDER BY osl.id_order_state ASC';
            $id = (int) Db::getInstance()->getValue($sql);
            return $id > 0 ? $id : 0;
        } catch (Exception $e) {
            return 0;
        }
    }

    private static function createOrderState($name, $hexColor = '#64748b')
    {
        $name = trim((string) $name);
        if ($name === '') {
            return 0;
        }
        try {
            $os = new OrderState();

            // Bez maili, bez faktur - tylko stan workflow
            if (property_exists($os, 'send_email')) {
                $os->send_email = 0;
            }
            if (property_exists($os, 'invoice')) {
                $os->invoice = 0;
            }
            if (property_exists($os, 'logable')) {
                $os->logable = 0;
            }
            if (property_exists($os, 'delivery')) {
                $os->delivery = 0;
            }
            if (property_exists($os, 'shipped')) {
                $os->shipped = 0;
            }
            if (property_exists($os, 'paid')) {
                $os->paid = 0;
            }
            if (property_exists($os, 'hidden')) {
                $os->hidden = 0;
            }
            if (property_exists($os, 'unremovable')) {
                $os->unremovable = 0;
            }
            if (property_exists($os, 'pdf_invoice')) {
                $os->pdf_invoice = 0;
            }
            if (property_exists($os, 'pdf_delivery')) {
                $os->pdf_delivery = 0;
            }
            if (property_exists($os, 'module_name')) {
                $os->module_name = 'bb_ordermanager';
            }

            if (property_exists($os, 'color')) {
                $os->color = (string) $hexColor;
            }

            $langs = Language::getLanguages(false);
            $names = [];
            $templates = [];
            foreach ($langs as $lang) {
                $lid = (int) $lang['id_lang'];
                $names[$lid] = $name;
                $templates[$lid] = '';
            }
            $os->name = $names;
            if (property_exists($os, 'template')) {
                $os->template = $templates;
            }

            if (!$os->add()) {
                return 0;
            }

            // Ikona
            $dst = _PS_IMG_DIR_ . 'os/' . (int) $os->id . '.gif';
            if (!file_exists($dst)) {
                $src = _PS_IMG_DIR_ . 'os/1.gif';
                if (file_exists($src)) {
                    @copy($src, $dst);
                }
            }
            return (int) $os->id;
        } catch (Exception $e) {
            return 0;
        }
    }
}
