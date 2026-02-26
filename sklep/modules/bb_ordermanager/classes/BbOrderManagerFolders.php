<?php
/**
 * BbOrderManagerFolders
 *
 * Konfiguracja Kanban (Etapy/Zakładki + Foldery/Statusy) dla BIGBIO Manager.
 *
 * Dane są przechowywane w Configuration (JSON) – bez migracji DB.
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class BbOrderManagerFolders
{
    /** Foldery (JSON array) */
    const CONFIG_FOLDERS = 'BB_OM_FOLDER_DEFS';

    /** Etapy/Zakładki (JSON array) */
    const CONFIG_GROUPS = 'BB_OM_FOLDER_GROUPS';

    /**
     * Domyślne etapy.
     * @return array<int,array{key:string,title:string,position:int,system:int}>
     */
    public static function getDefaultGroups()
    {
        return [
            ['key' => 'stage1', 'title' => '1. ETAP: ZAMAWIANIE', 'position' => 1, 'system' => 1],
            ['key' => 'local', 'title' => '⭐ PRIORYTETY LOKALNE', 'position' => 2, 'system' => 1],
            ['key' => 'today', 'title' => '2. ETAP: PAKOWANIE (DZIŚ)', 'position' => 3, 'system' => 1],
            ['key' => 'tomorrow', 'title' => '3. ETAP: OCZEKUJE NA DOSTAWĘ', 'position' => 4, 'system' => 1],
            ['key' => 'returns', 'title' => '↩️ ZWROTY I REKLAMACJE', 'position' => 5, 'system' => 1],
            ['key' => 'archive', 'title' => '🚫 ANULOWANE / ARCHIWUM', 'position' => 6, 'system' => 1],
        ];
    }

    /**
     * Domyślne foldery.
     * @return array<int,array<string,mixed>>
     */
    public static function getDefaultDefinitions()
    {
        $mk = function ($label) {
            return 'sys_' . md5((string) $label);
        };

        return [
            // 1. ETAP: ZAMAWIANIE
            ['id' => $mk('Nowe (Do zamówienia)'), 'label' => 'Nowe (Do zamówienia)', 'group' => 'stage1', 'position' => 1, 'active' => 1, 'color_hex' => '#2563eb', 'isError' => 0, 'system' => 1],
            ['id' => $mk('Nieopłacone'), 'label' => 'Nieopłacone', 'group' => 'stage1', 'position' => 2, 'active' => 1, 'color_hex' => '#94a3b8', 'isError' => 0, 'system' => 1],
            ['id' => $mk('Do wyjaśnienia'), 'label' => 'Do wyjaśnienia', 'group' => 'stage1', 'position' => 3, 'active' => 1, 'color_hex' => '#ef4444', 'isError' => 1, 'system' => 1],

            // ⭐ PRIORYTETY LOKALNE
            ['id' => $mk('Odbiór osobisty'), 'label' => 'Odbiór osobisty', 'group' => 'local', 'position' => 1, 'active' => 1, 'color_hex' => '#8b5cf6', 'isError' => 0, 'system' => 1],
            ['id' => $mk('Dostawa do klienta'), 'label' => 'Dostawa do klienta', 'group' => 'local', 'position' => 2, 'active' => 1, 'color_hex' => '#0ea5e9', 'isError' => 0, 'system' => 1],

            // 2. ETAP: PAKOWANIE (DZIŚ)
            ['id' => $mk('MAGAZYN (Własne)'), 'label' => 'MAGAZYN (Własne)', 'group' => 'today', 'position' => 1, 'active' => 1, 'color_hex' => '#4f46e5', 'isError' => 0, 'system' => 1],
            ['id' => $mk('BP'), 'label' => 'BP', 'group' => 'today', 'position' => 2, 'active' => 1, 'color_hex' => '#10b981', 'isError' => 0, 'system' => 1],
            ['id' => $mk('BP(1 poz) - Szybkie'), 'label' => 'BP(1 poz) - Szybkie', 'group' => 'today', 'position' => 3, 'active' => 1, 'color_hex' => '#34d399', 'isError' => 0, 'system' => 1],
            ['id' => $mk('EKOWITAL'), 'label' => 'EKOWITAL', 'group' => 'today', 'position' => 4, 'active' => 1, 'color_hex' => '#10b981', 'isError' => 0, 'system' => 1],
            ['id' => $mk('EKOWITAL(1 poz)'), 'label' => 'EKOWITAL(1 poz)', 'group' => 'today', 'position' => 5, 'active' => 1, 'color_hex' => '#34d399', 'isError' => 0, 'system' => 1],
            ['id' => $mk('BP + EKOWITAL'), 'label' => 'BP + EKOWITAL', 'group' => 'today', 'position' => 6, 'active' => 1, 'color_hex' => '#10b981', 'isError' => 0, 'system' => 1],
            ['id' => $mk('BP + EKO <10'), 'label' => 'BP + EKO <10', 'group' => 'today', 'position' => 7, 'active' => 1, 'color_hex' => '#34d399', 'isError' => 0, 'system' => 1],
            ['id' => $mk('NATURA'), 'label' => 'NATURA', 'group' => 'today', 'position' => 8, 'active' => 1, 'color_hex' => '#10b981', 'isError' => 0, 'system' => 1],
            ['id' => $mk('STEWIARNIA'), 'label' => 'STEWIARNIA', 'group' => 'today', 'position' => 9, 'active' => 1, 'color_hex' => '#10b981', 'isError' => 0, 'system' => 1],
            ['id' => $mk('MIX'), 'label' => 'MIX', 'group' => 'today', 'position' => 10, 'active' => 1, 'color_hex' => '#fb923c', 'isError' => 0, 'system' => 1],
            ['id' => $mk('MIX < 10'), 'label' => 'MIX < 10', 'group' => 'today', 'position' => 11, 'active' => 1, 'color_hex' => '#fdba74', 'isError' => 0, 'system' => 1],

            // 3. ETAP: OCZEKUJE NA DOSTAWĘ
            ['id' => $mk('Dostawa: JUTRO'), 'label' => 'Dostawa: JUTRO', 'group' => 'tomorrow', 'position' => 1, 'active' => 1, 'color_hex' => '#facc15', 'isError' => 0, 'system' => 1],
            ['id' => $mk('Dostawa: POJUTRZE'), 'label' => 'Dostawa: POJUTRZE', 'group' => 'tomorrow', 'position' => 2, 'active' => 1, 'color_hex' => '#fde68a', 'isError' => 0, 'system' => 1],
            ['id' => $mk('Czeka na brakujący towar'), 'label' => 'Czeka na brakujący towar', 'group' => 'tomorrow', 'position' => 3, 'active' => 1, 'color_hex' => '#a855f7', 'isError' => 0, 'system' => 1],

            // ↩️ ZWROTY I REKLAMACJE
            ['id' => $mk('Zwroty (Do obsługi)'), 'label' => 'Zwroty (Do obsługi)', 'group' => 'returns', 'position' => 1, 'active' => 1, 'color_hex' => '#ec4899', 'isError' => 1, 'system' => 1],
            ['id' => $mk('Reklamacje (Uszkodzenia)'), 'label' => 'Reklamacje (Uszkodzenia)', 'group' => 'returns', 'position' => 2, 'active' => 1, 'color_hex' => '#db2777', 'isError' => 0, 'system' => 1],

            // 🚫 ANULOWANE / ARCHIWUM
            ['id' => $mk('Anulowane (Klient)'), 'label' => 'Anulowane (Klient)', 'group' => 'archive', 'position' => 1, 'active' => 1, 'color_hex' => '#111827', 'isError' => 0, 'system' => 1],
            ['id' => $mk('Anulowane (Sklep)'), 'label' => 'Anulowane (Sklep)', 'group' => 'archive', 'position' => 2, 'active' => 1, 'color_hex' => '#111827', 'isError' => 0, 'system' => 1],
            ['id' => $mk('Spakowane / Gotowe'), 'label' => 'Spakowane / Gotowe', 'group' => 'archive', 'position' => 3, 'active' => 1, 'color_hex' => '#0d9488', 'isError' => 0, 'system' => 1],
            ['id' => $mk('Wysłane (Historia)'), 'label' => 'Wysłane (Historia)', 'group' => 'archive', 'position' => 4, 'active' => 1, 'color_hex' => '#cbd5e1', 'isError' => 0, 'system' => 1],
            ['id' => $mk('Archiwum'), 'label' => 'Archiwum', 'group' => 'archive', 'position' => 5, 'active' => 1, 'color_hex' => '#64748b', 'isError' => 0, 'system' => 1],
        ];
    }

    /**
     * Wczytaj grupy/etapy z konfiguracji.
     * @return array<int,array{key:string,title:string,position:int,system:int}>
     */
    public static function getGroups()
    {
        $raw = trim((string) Configuration::get(self::CONFIG_GROUPS));
        $decoded = $raw !== '' ? json_decode($raw, true) : null;
        if (!is_array($decoded) || empty($decoded)) {
            $defaults = self::getDefaultGroups();
            self::saveGroups($defaults);
            return $defaults;
        }

        $normalized = self::normalizeGroups($decoded);
        // jeśli normalizacja zmieniła dane, zapisz
        if (json_encode($decoded) !== json_encode($normalized)) {
            self::saveGroups($normalized);
        }
        return $normalized;
    }

    /**
     * Zapisz grupy/etapy.
     * @param array<int,array<string,mixed>> $groups
     */
    public static function saveGroups($groups)
    {
        if (!is_array($groups)) {
            $groups = [];
        }
        $groups = self::normalizeGroups($groups);
        $json = json_encode(array_values($groups), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($json === false) {
            $json = '[]';
        }
        // W praktyce (zwłaszcza przy włączonym multistore / różnych kontekstach sklepu)
        // konfiguracja może zostać zapisana w innym scope niż ten, z którego czyta FO.
        // Skutkuje to tym, że BO pokazuje nowe wartości, a FO dalej widzi stare.
        // Dlatego zapisujemy wartość „bezpiecznie” w kilku scope naraz.
        self::updateConfigValueAllScopes(self::CONFIG_GROUPS, $json);
    }

    /**
     * Kolejność grup.
     * @return string[]
     */
    public static function getGroupOrder()
    {
        $groups = self::getGroups();
        usort($groups, function ($a, $b) {
            return ((int) ($a['position'] ?? 0)) <=> ((int) ($b['position'] ?? 0));
        });
        $out = [];
        foreach ($groups as $g) {
            $k = (string) ($g['key'] ?? '');
            if ($k !== '') {
                $out[] = $k;
            }
        }
        return $out;
    }

    /**
     * Wczytaj definicje folderów.
     * @return array<int,array<string,mixed>>
     */
    public static function getDefinitions()
    {
        $raw = trim((string) Configuration::get(self::CONFIG_FOLDERS));
        $decoded = $raw !== '' ? json_decode($raw, true) : null;

        if (!is_array($decoded) || empty($decoded)) {
            $defaults = self::getDefaultDefinitions();
            self::saveDefinitions($defaults);
            return $defaults;
        }

        $normalized = self::normalizeDefinitions($decoded);
        // jeśli normalizacja dopisała ID lub systemowe foldery, zapisz
        if (json_encode($decoded) !== json_encode($normalized)) {
            self::saveDefinitions($normalized);
        }
        return $normalized;
    }

    /**
     * Zapisz definicje folderów.
     * @param array<int,array<string,mixed>> $defs
     */
    public static function saveDefinitions($defs)
    {
        if (!is_array($defs)) {
            $defs = [];
        }
        $defs = self::normalizeDefinitions($defs);
        $json = json_encode(array_values($defs), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($json === false) {
            $json = '[]';
        }
        // Patrz komentarz w saveGroups() – zapisujemy w wielu scope,
        // aby FO zawsze widział tę samą konfigurację co BO.
        self::updateConfigValueAllScopes(self::CONFIG_FOLDERS, $json);
    }

    /**
     * Zapisz Configuration w sposób odporny na rozjazd kontekstu sklepu (multistore).
     *
     * Problem praktyczny:
     * - BO bywa w kontekście „Wszystkie sklepy” i zapis trafia do globalnego scope,
     * - a FO czyta z konkretnego id_shop (lub odwrotnie), więc widzi starą wartość.
     *
     * Rozwiązanie:
     * - aktualizujemy wartość w aktualnym kontekście,
     * - oraz dodatkowo globalnie,
     * - oraz (jeśli możliwe) dla bieżącego id_shop.
     */
    private static function updateConfigValueAllScopes($key, $value)
    {
        // 1) Aktualny kontekst (Presta sama zdecyduje scope)
        try {
            Configuration::updateValue((string) $key, $value);
        } catch (Exception $e) {
            // ignore
        }

        // 2) Globalnie (id_shop = NULL, id_shop_group = NULL)
        try {
            if (method_exists('Configuration', 'updateGlobalValue')) {
                Configuration::updateGlobalValue((string) $key, $value);
            }
        } catch (Exception $e) {
            // ignore
        }

        // 3) Dla konkretnego sklepu (jeśli dostępne)
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
     * Czy folder jest systemowy (domyślny)?
     */
    public static function isSystemFolderId($id)
    {
        $id = (string) $id;
        if ($id === '') {
            return false;
        }
        foreach (self::getDefaultDefinitions() as $d) {
            if ((string) $d['id'] === $id) {
                return true;
            }
        }
        return false;
    }

    /**
     * Walidacja group key.
     */
    public static function isValidGroup($group)
    {
        $group = (string) $group;
        if ($group === '') {
            return false;
        }
        foreach (self::getGroups() as $g) {
            if ((string) $g['key'] === $group) {
                return true;
            }
        }
        return false;
    }

    /**
     * Zbuduj menu dla Vue (dynamiczne, tylko aktywne foldery).
     * @return array<int,array<string,mixed>>
     */
    public static function buildMenu($defs = null)
    {
        if (!is_array($defs)) {
            $defs = self::getDefinitions();
        }

        $groups = self::indexGroupsByKey(self::getGroups());
        $order = self::getGroupOrder();

        $byGroup = [];
        foreach ($defs as $d) {
            if (empty($d['active'])) {
                continue;
            }
            $g = (string) ($d['group'] ?? 'stage1');
            if (!isset($groups[$g])) {
                $g = 'stage1';
            }
            if (!isset($byGroup[$g])) {
                $byGroup[$g] = [];
            }
            $byGroup[$g][] = [
                'id' => (string) ($d['id'] ?? ''),
                'label' => (string) ($d['label'] ?? ''),
                'position' => (int) ($d['position'] ?? 0),
                'color_hex' => (string) ($d['color_hex'] ?? '#64748b'),
                // legacy fallback
                'color' => 'bg-slate-300',
                'isError' => !empty($d['isError']),
            ];
        }

        // sortuj foldery po pozycji
        foreach ($byGroup as $gKey => $items) {
            usort($items, function ($a, $b) {
                $pa = (int) ($a['position'] ?? 0);
                $pb = (int) ($b['position'] ?? 0);
                if ($pa !== $pb) {
                    return $pa <=> $pb;
                }
                return strcmp((string) ($a['label'] ?? ''), (string) ($b['label'] ?? ''));
            });
            $byGroup[$gKey] = $items;
        }

        $menu = [];
        foreach ($order as $gKey) {
            if (empty($byGroup[$gKey])) {
                continue;
            }
            $menu[] = [
                'key' => $gKey,
                'title' => (string) ($groups[$gKey]['title'] ?? $gKey),
                'total' => 0,
                'items' => array_values($byGroup[$gKey]),
            ];
        }
        return $menu;
    }

    /**
     * Przygotuj dane do konfiguracji (grupy + elementy).
     * @return array<int,array<string,mixed>>
     */
    public static function buildGroupsForConfig($defs = null)
    {
        if (!is_array($defs)) {
            $defs = self::getDefinitions();
        }

        $groups = self::getGroups();
        usort($groups, function ($a, $b) {
            return ((int) ($a['position'] ?? 0)) <=> ((int) ($b['position'] ?? 0));
        });

        $byGroup = [];
        foreach ($groups as $g) {
            $byGroup[(string) $g['key']] = [];
        }

        foreach ($defs as $d) {
            $g = (string) ($d['group'] ?? 'stage1');
            if (!isset($byGroup[$g])) {
                $g = 'stage1';
            }
            $id = (string) ($d['id'] ?? '');
            if ($id === '') {
                continue;
            }
            $byGroup[$g][] = [
                'id' => $id,
                'label' => (string) ($d['label'] ?? ''),
                'position' => (int) ($d['position'] ?? 0),
                'active' => !empty($d['active']),
                'color_hex' => (string) ($d['color_hex'] ?? '#64748b'),
                'is_error' => !empty($d['isError']),
                'is_custom' => !self::isSystemFolderId($id),
            ];
        }

        // sort per grupa po position
        foreach ($byGroup as $gKey => $items) {
            usort($items, function ($a, $b) {
                return ((int) ($a['position'] ?? 0)) <=> ((int) ($b['position'] ?? 0));
            });
            $byGroup[$gKey] = array_values($items);
        }

        $out = [];
        foreach ($groups as $g) {
            $gKey = (string) ($g['key'] ?? '');
            if ($gKey === '') {
                continue;
            }
            $out[] = [
                'key' => $gKey,
                'title' => (string) ($g['title'] ?? $gKey),
                'position' => (int) ($g['position'] ?? 0),
                'is_custom' => empty($g['system']),
                'items' => isset($byGroup[$gKey]) ? $byGroup[$gKey] : [],
            ];
        }
        return $out;
    }

    /**
     * Zwróć folder po ID.
     * @return array<string,mixed>|null
     */
    public static function findFolderById($folderId)
    {
        $folderId = (string) $folderId;
        if ($folderId === '') {
            return null;
        }
        foreach (self::getDefinitions() as $d) {
            if ((string) ($d['id'] ?? '') === $folderId) {
                return $d;
            }
        }
        return null;
    }

    /**
     * Zwróć folder po label (do kompatybilności).
     * @return array<string,mixed>|null
     */
    public static function findFolderByLabel($label)
    {
        $label = self::normalizeLabel($label);
        if ($label === '') {
            return null;
        }
        foreach (self::getDefinitions() as $d) {
            if (self::normalizeLabel((string) ($d['label'] ?? '')) === $label) {
                return $d;
            }
        }
        return null;
    }

    /**
     * Normalizacja grup.
     * @param array<int,array<string,mixed>> $groups
     * @return array<int,array{key:string,title:string,position:int,system:int}>
     */
    private static function normalizeGroups($groups)
    {
        $defaults = self::getDefaultGroups();
        $defaultsByKey = self::indexGroupsByKey($defaults);

        $seen = [];
        $out = [];
        foreach ($groups as $g) {
            if (!is_array($g)) {
                continue;
            }
            $key = trim((string) ($g['key'] ?? ''));
            $title = trim((string) ($g['title'] ?? ''));
            if ($key === '' || $title === '') {
                continue;
            }

            // zabezpieczenie klucza
            $key = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $key);
            if ($key === '') {
                continue;
            }
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;

            $pos = (int) ($g['position'] ?? 0);
            if ($pos <= 0) {
                $pos = 999;
            }
            $system = isset($defaultsByKey[$key]) ? 1 : (int) (!empty($g['system']) ? 1 : 0);
            $out[] = [
                'key' => $key,
                'title' => $title,
                'position' => $pos,
                'system' => $system,
            ];
        }

        // uzupełnij brakujące systemowe
        foreach ($defaults as $dg) {
            $k = (string) $dg['key'];
            if (!isset($seen[$k])) {
                $seen[$k] = true;
                $out[] = $dg;
            }
        }

        // sort + normalizacja pozycji
        usort($out, function ($a, $b) {
            return ((int) ($a['position'] ?? 0)) <=> ((int) ($b['position'] ?? 0));
        });

        $i = 0;
        foreach ($out as &$g) {
            $i++;
            $g['position'] = $i;
        }
        unset($g);

        return array_values($out);
    }

    /**
     * Normalizacja folderów.
     * @param array<int,array<string,mixed>> $defs
     * @return array<int,array<string,mixed>>
     */
    public static function normalizeDefinitions($defs)
    {
        if (!is_array($defs)) {
            $defs = [];
        }

        $groups = self::indexGroupsByKey(self::getGroups());
        $groupOrder = self::getGroupOrder();
        $groupPos = [];
        foreach ($groupOrder as $idx => $gKey) {
            $groupPos[$gKey] = $idx;
        }

        $defaults = self::getDefaultDefinitions();
        $defaultsById = [];
        foreach ($defaults as $d) {
            $defaultsById[(string) $d['id']] = $d;
        }

        $seenId = [];
        $seenLabel = [];
        $out = [];

        foreach ($defs as $d) {
            if (!is_array($d)) {
                continue;
            }
            $label = trim((string) ($d['label'] ?? ''));
            if ($label === '') {
                continue;
            }

            $id = trim((string) ($d['id'] ?? ''));
            if ($id === '') {
                // migracja ze starego formatu – deterministyczny ID
                $id = (self::isSystemLabel($label) ? 'sys_' : 'c_') . md5($label);
            }
            $id = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $id);
            if ($id === '') {
                continue;
            }
            if (isset($seenId[$id])) {
                continue;
            }
            $seenId[$id] = true;

            // unikalność label (case-insensitive)
            $normLabel = self::normalizeLabel($label);
            if ($normLabel !== '' && isset($seenLabel[$normLabel])) {
                // jeśli duplikat – dodaj sufiks
                $label .= ' (' . substr($id, -4) . ')';
                $normLabel = self::normalizeLabel($label);
            }
            $seenLabel[$normLabel] = true;

            $group = (string) ($d['group'] ?? 'stage1');
            if (!isset($groups[$group])) {
                $group = 'stage1';
            }

            $pos = (int) ($d['position'] ?? 0);
            if ($pos < 0) {
                $pos = 0;
            }

            $active = (int) ($d['active'] ?? 1);
            $active = $active ? 1 : 0;

            $hex = trim((string) ($d['color_hex'] ?? ''));
            if ($hex === '' || $hex[0] !== '#') {
                $hex = '#64748b';
            }

            $isError = !empty($d['isError']) ? 1 : 0;
            if (isset($d['is_error'])) {
                $isError = !empty($d['is_error']) ? 1 : 0;
            }

            $system = isset($defaultsById[$id]) ? 1 : (int) (!empty($d['system']) ? 1 : 0);

            $out[] = [
                'id' => $id,
                'label' => $label,
                'group' => $group,
                'position' => $pos,
                'active' => $active,
                'color_hex' => $hex,
                'isError' => $isError,
                'system' => $system,
            ];
        }

        // uzupełnij brakujące systemowe foldery po ID
        foreach ($defaults as $sys) {
            $sid = (string) $sys['id'];
            if (!isset($seenId[$sid])) {
                $seenId[$sid] = true;
                $out[] = $sys;
            }
        }

        // sort wg etapów i pozycji
        usort($out, function ($a, $b) use ($groupPos) {
            $ga = (string) ($a['group'] ?? 'stage1');
            $gb = (string) ($b['group'] ?? 'stage1');
            $gpa = isset($groupPos[$ga]) ? (int) $groupPos[$ga] : 999;
            $gpb = isset($groupPos[$gb]) ? (int) $groupPos[$gb] : 999;
            if ($gpa !== $gpb) {
                return $gpa <=> $gpb;
            }
            $pa = (int) ($a['position'] ?? 0);
            $pb = (int) ($b['position'] ?? 0);
            if ($pa !== $pb) {
                return $pa <=> $pb;
            }
            return strcmp((string) ($a['label'] ?? ''), (string) ($b['label'] ?? ''));
        });

        // normalizacja pozycji per grupa
        $counters = [];
        foreach ($out as &$d) {
            $g = (string) ($d['group'] ?? 'stage1');
            if (!isset($counters[$g])) {
                $counters[$g] = 0;
            }
            $counters[$g]++;
            $d['position'] = (int) $counters[$g];
        }
        unset($d);

        // Nie pozwól zostawić 0 aktywnych folderów
        $activeCount = 0;
        foreach ($out as $d) {
            if (!empty($d['active'])) {
                $activeCount++;
            }
        }
        if ($activeCount === 0 && !empty($out)) {
            $out[0]['active'] = 1;
        }

        return array_values($out);
    }

    /**
     * Czy label jest wśród systemowych (domyślnych).
     */
    private static function isSystemLabel($label)
    {
        $label = self::normalizeLabel($label);
        if ($label === '') {
            return false;
        }
        foreach (self::getDefaultDefinitions() as $d) {
            if (self::normalizeLabel((string) $d['label']) === $label) {
                return true;
            }
        }
        return false;
    }

    /**
     * Normalizacja label.
     */
    private static function normalizeLabel($label)
    {
        $label = trim((string) $label);
        if ($label === '') {
            return '';
        }
        if (function_exists('mb_strtolower')) {
            return mb_strtolower($label, 'UTF-8');
        }
        return strtolower($label);
    }

    /**
     * Index groups by key.
     * @param array<int,array<string,mixed>> $groups
     * @return array<string,array<string,mixed>>
     */
    private static function indexGroupsByKey($groups)
    {
        $out = [];
        foreach ($groups as $g) {
            if (!is_array($g)) {
                continue;
            }
            $k = (string) ($g['key'] ?? '');
            if ($k === '') {
                continue;
            }
            $out[$k] = $g;
        }
        return $out;
    }
}
