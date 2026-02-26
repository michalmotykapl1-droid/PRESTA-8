<?php

require_once _PS_MODULE_DIR_ . 'bb_ordermanager/classes/BbOrderManagerFolderStates.php';
require_once _PS_MODULE_DIR_ . 'bb_ordermanager/classes/BbOrderManagerFolders.php';

/**
 * Controller for Manager Pro Configuration
 */
class AdminManagerProConfigController extends ModuleAdminController
{
    public function __construct()
    {
        $this->bootstrap = true;
        parent::__construct();
    }

    public function initContent()
    {
        parent::initContent();

        if (Tools::isSubmit('submitManagerProConfig')) {
            $this->postProcessConfig();
        }

        $idLang = (int) $this->context->language->id;

        // --- KANBAN (etapy + foldery) ---
        $bb_folder_defs = BbOrderManagerFolders::getDefinitions();
        $bb_folder_groups = BbOrderManagerFolders::buildGroupsForConfig($bb_folder_defs);
        $bb_group_defs = BbOrderManagerFolders::getGroups();

        // mapowanie folder_id => id_order_state
        $bb_folder_state_map_by_id = BbOrderManagerFolderStates::getMapByFolderId($idLang);
        $bb_order_states = OrderState::getOrderStates($idLang);

        // --- UPRAWNIENIA ---
        $profiles = [];
        if (class_exists('Profile') && method_exists('Profile', 'getProfiles')) {
            $profiles = Profile::getProfiles($idLang);
        }

        $employees = Db::getInstance()->executeS(
            'SELECT e.id_employee, e.firstname, e.lastname, e.email, e.id_profile, e.active, pl.name AS profile_name
             FROM `' . _DB_PREFIX_ . 'employee` e
             LEFT JOIN `' . _DB_PREFIX_ . 'profile_lang` pl
               ON (pl.id_profile = e.id_profile AND pl.id_lang = ' . (int) $idLang . ')
             ORDER BY e.lastname ASC, e.firstname ASC'
        );

        $allowedProfiles  = $this->parseIds(Configuration::get('BB_OM_ALLOWED_PROFILES'));
        $allowedEmployees = $this->parseIds(Configuration::get('BB_OM_ALLOWED_EMPLOYEES'));

        $accessMode = (string) Configuration::get('BB_OM_ACCESS_MODE');
        $accessMode = strtolower(trim($accessMode));
        if (!in_array($accessMode, ['all', 'profiles', 'employees'], true)) {
            if (!empty($allowedEmployees)) {
                $accessMode = 'employees';
            } elseif (!empty($allowedProfiles)) {
                $accessMode = 'profiles';
            } else {
                $accessMode = 'all';
            }
        }

        $allowedProfilesMap = [];
        foreach ($allowedProfiles as $pid) {
            $allowedProfilesMap[(int)$pid] = true;
        }
        $allowedEmployeesMap = [];
        foreach ($allowedEmployees as $eid) {
            $allowedEmployeesMap[(int)$eid] = true;
        }

        $this->context->smarty->assign([
            // Ustawienia Druku
            'bb_label_format' => Configuration::get('BB_MANAGER_LABEL_FORMAT'),
            'bb_label_size'   => Configuration::get('BB_MANAGER_LABEL_SIZE'),

            // Domyślne Parametry Paczki
            'bb_pkg_type'     => Configuration::get('BB_MANAGER_PKG_TYPE'),
            'bb_def_weight'   => Configuration::get('BB_MANAGER_DEF_WEIGHT'),
            'bb_content'      => Configuration::get('BB_MANAGER_CONTENT'),

            // Wymiary
            'bb_pkg_len'      => (int) Configuration::get('BB_MANAGER_PKG_LEN'),
            'bb_pkg_wid'      => (int) Configuration::get('BB_MANAGER_PKG_WID'),
            'bb_pkg_hei'      => (int) Configuration::get('BB_MANAGER_PKG_HEI'),

            // Kanban
            'bb_folder_defs' => $bb_folder_defs,
            'bb_folder_groups' => $bb_folder_groups,
            'bb_group_defs' => $bb_group_defs,
            'bb_folder_state_map_by_id' => $bb_folder_state_map_by_id,
            'bb_order_states' => $bb_order_states,

            // Uprawnienia
            'bb_profiles' => $profiles,
            'bb_employees' => $employees,
            'bb_allowed_profiles' => $allowedProfiles,
            'bb_allowed_employees' => $allowedEmployees,
            'bb_allowed_profiles_map' => $allowedProfilesMap,
            'bb_allowed_employees_map' => $allowedEmployeesMap,
            'bb_access_mode' => $accessMode,

            'action_url' => self::$currentIndex . '&token=' . $this->token,
        ]);

        $this->setTemplate('config.tpl');
    }

    private function postProcessConfig()
    {
        // Zapis ustawień druku
        Configuration::updateValue('BB_MANAGER_LABEL_FORMAT', Tools::getValue('BB_MANAGER_LABEL_FORMAT'));
        Configuration::updateValue('BB_MANAGER_LABEL_SIZE', Tools::getValue('BB_MANAGER_LABEL_SIZE'));

        // Zapis parametrów paczki
        Configuration::updateValue('BB_MANAGER_PKG_TYPE', Tools::getValue('BB_MANAGER_PKG_TYPE'));
        $weight = str_replace(',', '.', Tools::getValue('BB_MANAGER_DEF_WEIGHT'));
        Configuration::updateValue('BB_MANAGER_DEF_WEIGHT', (float) $weight);
        Configuration::updateValue('BB_MANAGER_CONTENT', Tools::getValue('BB_MANAGER_CONTENT'));

        // Zapis wymiarów
        Configuration::updateValue('BB_MANAGER_PKG_LEN', (int) Tools::getValue('BB_MANAGER_PKG_LEN'));
        Configuration::updateValue('BB_MANAGER_PKG_WID', (int) Tools::getValue('BB_MANAGER_PKG_WID'));
        Configuration::updateValue('BB_MANAGER_PKG_HEI', (int) Tools::getValue('BB_MANAGER_PKG_HEI'));

        // --- KANBAN: grupy ---
        $groupTitles = Tools::getValue('BB_OM_GROUP_TITLE');
        $groupPos = Tools::getValue('BB_OM_GROUP_POS');

        if (is_array($groupTitles)) {
            $defaultGroups = BbOrderManagerFolders::getDefaultGroups();
            $defaultKeys = [];
            foreach ($defaultGroups as $dg) {
                $defaultKeys[(string) $dg['key']] = true;
            }

            $groupsToSave = [];
            foreach ($groupTitles as $gKey => $title) {
                $gKey = (string) $gKey;
                $title = trim((string) $title);
                if ($gKey === '' || $title === '') {
                    continue;
                }
                $pos = (is_array($groupPos) && isset($groupPos[$gKey])) ? (int) $groupPos[$gKey] : 999;
                $groupsToSave[] = [
                    'key' => $gKey,
                    'title' => $title,
                    'position' => $pos,
                    'system' => isset($defaultKeys[$gKey]) ? 1 : 0,
                ];
            }

            BbOrderManagerFolders::saveGroups($groupsToSave);
        }

        // --- KANBAN: foldery ---
        // zapamiętaj stare etykiety/kolory (do ewentualnego rename OrderState)
        $oldDefs = BbOrderManagerFolders::getDefinitions();
        $oldById = [];
        foreach ($oldDefs as $d) {
            if (!empty($d['id'])) {
                $oldById[(string) $d['id']] = [
                    'label' => (string) ($d['label'] ?? ''),
                    'color_hex' => (string) ($d['color_hex'] ?? ''),
                ];
            }
        }
        $labels = Tools::getValue('BB_OM_FOLDER_LABEL');
        $groups = Tools::getValue('BB_OM_FOLDER_GROUP');
        $positions = Tools::getValue('BB_OM_FOLDER_POS');
        $actives = Tools::getValue('BB_OM_FOLDER_ACTIVE');
        $colorHexes = Tools::getValue('BB_OM_FOLDER_COLOR_HEX');
        $isErrors = Tools::getValue('BB_OM_FOLDER_IS_ERROR');

        if (is_array($labels)) {
            $defs = [];
            $seen = [];

            foreach ($labels as $fid => $label) {
                $fid = trim((string) $fid);
                $label = trim((string) $label);
                if ($fid === '' || $label === '') {
                    continue;
                }

                $norm = function_exists('mb_strtolower') ? mb_strtolower($label, 'UTF-8') : strtolower($label);
                if (isset($seen[$norm])) {
                    $this->errors[] = sprintf('Zduplikowana nazwa folderu: "%s". Foldery muszą mieć unikalne nazwy.', $label);
                    continue;
                }
                $seen[$norm] = true;

                $group = (is_array($groups) && isset($groups[$fid])) ? (string) $groups[$fid] : 'stage1';
                if (!BbOrderManagerFolders::isValidGroup($group)) {
                    $group = 'stage1';
                }

                $pos = (is_array($positions) && isset($positions[$fid])) ? (int) $positions[$fid] : 0;
                if ($pos < 0) {
                    $pos = 0;
                }

                $active = (is_array($actives) && isset($actives[$fid])) ? 1 : 0;

                $hex = (is_array($colorHexes) && isset($colorHexes[$fid])) ? trim((string) $colorHexes[$fid]) : '#64748b';
                if ($hex === '' || $hex[0] !== '#') {
                    $hex = '#64748b';
                }

                $isError = (is_array($isErrors) && isset($isErrors[$fid])) ? (int) $isErrors[$fid] : 0;
                $isError = $isError ? 1 : 0;

                $defs[] = [
                    'id' => $fid,
                    'label' => $label,
                    'group' => $group,
                    'position' => $pos,
                    'active' => $active,
                    'color_hex' => $hex,
                    'isError' => $isError,
                    'system' => BbOrderManagerFolders::isSystemFolderId($fid) ? 1 : 0,
                ];
            }

            BbOrderManagerFolders::saveDefinitions($defs);
        }

        // --- Mapowanie folder_id => status ---
        $states = Tools::getValue('BB_OM_FOLDER_STATE');
        $mapById = [];
        if (is_array($states)) {
            foreach ($states as $fid => $sidRaw) {
                $fid = (string) $fid;
                $sid = (int) $sidRaw;
                if ($fid !== '' && $sid > 0) {
                    // walidacja istnienia statusu
                    $exists = (int) Db::getInstance()->getValue('SELECT COUNT(*) FROM `' . _DB_PREFIX_ . 'order_state` WHERE id_order_state = ' . (int) $sid);
                    if ($exists > 0) {
                        $mapById[$fid] = $sid;
                    }
                }
            }
        }
        BbOrderManagerFolderStates::saveMapByFolderId($mapById);
        $finalMapById = BbOrderManagerFolderStates::getMapByFolderId((int) $this->context->language->id);

        // Jeśli zmieniono nazwę folderu lub kolor, a status jest "nasz" (module_name=bb_ordermanager) –
        // synchronizujemy nazwę/kolor OrderState w PrestaShop.
        $newDefsNow = BbOrderManagerFolders::getDefinitions();
        foreach ($newDefsNow as $d) {
            $fid = (string) ($d['id'] ?? '');
            if ($fid === '' || !isset($finalMapById[$fid])) {
                continue;
            }
            $sid = (int) $finalMapById[$fid];
            if ($sid <= 0) {
                continue;
            }
            $old = isset($oldById[$fid]) ? $oldById[$fid] : null;
            $newLabel = trim((string) ($d['label'] ?? ''));
            $newHex = trim((string) ($d['color_hex'] ?? ''));
            if (!$old) {
                continue;
            }
            $oldLabel = trim((string) ($old['label'] ?? ''));
            $oldHex = trim((string) ($old['color_hex'] ?? ''));
            if ($newLabel !== '' && ($newLabel !== $oldLabel || ($newHex !== '' && $newHex !== $oldHex))) {
                $this->syncOrderStateIfOwned($sid, $newLabel, $newHex);
            }
        }

        // --- Usuwanie folderów + statusów ---
        $deleteKeys = Tools::getValue('BB_OM_FOLDER_DELETE');
        $deleteStates = Tools::getValue('BB_OM_FOLDER_DELETE_STATE');
        $deleteLabels = Tools::getValue('BB_OM_FOLDER_DELETE_LABEL');

        if (is_array($deleteKeys) && !empty($deleteKeys)) {
            // statusy nadal używane przez inne foldery
            $usedStateIds = [];
            foreach ($finalMapById as $fid => $sid) {
                $sid = (int) $sid;
                if ($sid > 0) {
                    $usedStateIds[$sid] = true;
                }
            }

            $uniqueToDelete = [];
            foreach ($deleteKeys as $fid) {
                $fid = (string) $fid;
                $sid = (is_array($deleteStates) && isset($deleteStates[$fid])) ? (int) $deleteStates[$fid] : 0;
                $labelForMsg = (is_array($deleteLabels) && isset($deleteLabels[$fid])) ? (string) $deleteLabels[$fid] : '';
                if ($sid > 0) {
                    $uniqueToDelete[$sid] = $labelForMsg;
                }
            }

            foreach ($uniqueToDelete as $sid => $labelForMsg) {
                if (isset($usedStateIds[(int) $sid])) {
                    $this->errors[] = sprintf('Nie można usunąć statusu (ID: %d), ponieważ jest nadal przypisany do innego folderu.', (int) $sid);
                    continue;
                }
                $this->deleteOrderStateIfPossible((int) $sid, (string) $labelForMsg);
            }
        }

        // --- UPRAWNIENIA ---
        $mode = strtolower(trim((string) Tools::getValue('BB_OM_ACCESS_MODE')));
        if (!in_array($mode, ['all', 'profiles', 'employees'], true)) {
            $mode = 'all';
        }

        $profiles = Tools::getValue('BB_OM_ALLOWED_PROFILES');
        $employees = Tools::getValue('BB_OM_ALLOWED_EMPLOYEES');
        $profilesIds = $this->sanitizeIds($profiles);
        $employeesIds = $this->sanitizeIds($employees);

        if ($mode === 'profiles' && empty($profilesIds)) {
            $this->errors[] = 'Wybierz przynajmniej jeden profil albo ustaw dostęp dla wszystkich pracowników.';
            return;
        }
        if ($mode === 'employees' && empty($employeesIds)) {
            $this->errors[] = 'Wybierz przynajmniej jednego pracownika albo ustaw dostęp dla wszystkich pracowników.';
            return;
        }

        Configuration::updateValue('BB_OM_ACCESS_MODE', $mode);
        Configuration::updateValue('BB_OM_ALLOWED_PROFILES', implode(',', $profilesIds));
        Configuration::updateValue('BB_OM_ALLOWED_EMPLOYEES', implode(',', $employeesIds));

        $this->confirmations[] = 'Ustawienia zostały zapisane.';
    }

    /**
     * Usuń status zamówienia (OrderState), jeśli to możliwe.
     */
    private function deleteOrderStateIfPossible($idOrderState, $labelForMsg = '')
    {
        $idOrderState = (int) $idOrderState;
        if ($idOrderState <= 0) {
            return;
        }

        // nie usuwamy jeśli status nie jest z naszego modułu
        $moduleName = (string) Db::getInstance()->getValue('SELECT module_name FROM `' . _DB_PREFIX_ . 'order_state` WHERE id_order_state = ' . (int) $idOrderState);
        if ($moduleName !== '' && $moduleName !== 'bb_ordermanager') {
            $this->errors[] = sprintf('Nie można usunąć statusu (ID: %d), ponieważ nie został utworzony przez moduł bb_ordermanager.', (int) $idOrderState);
            return;
        }

        $cntCurrent = (int) Db::getInstance()->getValue('SELECT COUNT(*) FROM `' . _DB_PREFIX_ . 'orders` WHERE current_state = ' . (int) $idOrderState);
        $cntHistory = (int) Db::getInstance()->getValue('SELECT COUNT(*) FROM `' . _DB_PREFIX_ . 'order_history` WHERE id_order_state = ' . (int) $idOrderState);
        if ($cntCurrent > 0 || $cntHistory > 0) {
            $this->errors[] = sprintf(
                'Nie można usunąć statusu (ID: %d), ponieważ jest używany przez zamówienia (aktualnie: %d, historia: %d).',
                (int) $idOrderState,
                (int) $cntCurrent,
                (int) $cntHistory
            );
            return;
        }

        $os = new OrderState((int) $idOrderState);
        if (!Validate::isLoadedObject($os)) {
            $this->errors[] = sprintf('Nie znaleziono statusu do usunięcia (ID: %d).', (int) $idOrderState);
            return;
        }

        if (property_exists($os, 'unremovable') && (int) $os->unremovable === 1) {
            $this->errors[] = sprintf('Nie można usunąć statusu systemowego (ID: %d).', (int) $idOrderState);
            return;
        }

        $ok = false;
        try {
            $ok = (bool) $os->delete();
        } catch (Exception $e) {
            $ok = false;
        }

        if (!$ok) {
            $this->errors[] = sprintf('Nie udało się usunąć statusu (ID: %d).', (int) $idOrderState);
            return;
        }

        $icon = _PS_IMG_DIR_ . 'os/' . (int) $idOrderState . '.gif';
        if (file_exists($icon)) {
            @unlink($icon);
        }

        if ($labelForMsg !== '') {
            $this->confirmations[] = sprintf('Usunięto status zamówienia "%s" (ID: %d) w PrestaShop.', $labelForMsg, (int) $idOrderState);
        } else {
            $this->confirmations[] = sprintf('Usunięto status zamówienia (ID: %d) w PrestaShop.', (int) $idOrderState);
        }
    }

    /**
     * Jeśli status należy do modułu (module_name=bb_ordermanager),
     * aktualizuje nazwę (wszystkie języki) i kolor.
     */
    private function syncOrderStateIfOwned($idOrderState, $newName, $newHex)
    {
        $idOrderState = (int) $idOrderState;
        $newName = trim((string) $newName);
        $newHex = trim((string) $newHex);
        if ($idOrderState <= 0 || $newName === '') {
            return;
        }

        try {
            $os = new OrderState((int) $idOrderState);
            if (!Validate::isLoadedObject($os)) {
                return;
            }

            // tylko jeśli status jest "nasz"
            if (property_exists($os, 'module_name') && (string) $os->module_name !== '' && (string) $os->module_name !== 'bb_ordermanager') {
                return;
            }

            $langs = Language::getLanguages(false);
            $names = [];
            foreach ($langs as $lang) {
                $lid = (int) $lang['id_lang'];
                $names[$lid] = $newName;
            }
            $os->name = $names;

            if ($newHex !== '' && $newHex[0] === '#' && property_exists($os, 'color')) {
                $os->color = $newHex;
            }

            // nie ruszamy flag typu paid/shipped itd.
            $os->update();
        } catch (Exception $e) {
            return;
        }
    }

    private function parseIds($value)
    {
        if (is_array($value)) {
            $arr = $value;
        } else {
            $value = trim((string) $value);
            if ($value === '') {
                return [];
            }
            if ($value !== '' && $value[0] === '[') {
                $decoded = json_decode($value, true);
                $arr = is_array($decoded) ? $decoded : explode(',', $value);
            } else {
                $arr = explode(',', $value);
            }
        }

        $out = [];
        foreach ($arr as $v) {
            $id = (int) $v;
            if ($id > 0) {
                $out[] = $id;
            }
        }
        return array_values(array_unique($out));
    }

    private function sanitizeIds($value)
    {
        if ($value === null || $value === false) {
            return [];
        }
        if (!is_array($value)) {
            $value = [$value];
        }
        $out = [];
        foreach ($value as $v) {
            $id = (int) $v;
            if ($id > 0) {
                $out[] = $id;
            }
        }
        return array_values(array_unique($out));
    }
}
