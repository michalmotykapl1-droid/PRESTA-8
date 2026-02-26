<?php
/**
 * BB Order Manager - Logger / Audit
 *
 * Cel:
 * - jeden punkt zapisu logów audytowych dla akcji wykonywanych w Managerze/API/Pakowaniu
 * - dopisuje kto wykonał akcję (id_employee + nazwa)
 * - dopisuje typ akcji (action) oraz opcjonalne szczegóły (JSON)
 * - dba o migrację schematu tabeli bb_ordermanager_logs (dla istniejących instalacji)
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class BbOrderManagerLogger
{
    const TABLE = 'bb_ordermanager_logs';
    const CONF_SCHEMA_OK = 'BB_OM_LOGS_SCHEMA_OK';

    /**
     * Mapowanie kodów akcji -> czytelne nazwy (PL).
     * Używane w BO (LOGI) oraz jako fallback dla starych wpisów.
     */
    private static $actionLabels = [
        'INFO' => 'Informacja',

        // Zamówienie
        'ORDER_CREATE' => 'Utworzenie zamówienia',
        'ORDER_DELETE' => 'Usunięcie zamówienia',
        'STATUS_CHANGE' => 'Zmiana statusu',
        'STATUS_ARCHIVE' => 'Archiwizacja',

        // Produkty
        'PRODUCT_ADD' => 'Dodanie produktu',
        'PRODUCT_EDIT' => 'Edycja produktu',
        'PRODUCT_DELETE' => 'Usunięcie produktu',

        // Płatności
        'PAYMENT_UPDATE' => 'Zmiana płatności',
        'PAYMENT_LINK' => 'Wygenerowanie linku dopłaty',

        // Adres / logistyka
        'ADDRESS_UPDATE' => 'Zmiana adresu',
        'TRACKING_ADD' => 'Dodanie numeru śledzenia',

        // Wysyłka
        'SHIPMENT_CREATE' => 'Utworzenie przesyłki',
        'LABEL_DOWNLOAD' => 'Pobranie etykiety',

        // Pakowanie / faktury
        'PACKING_DONE' => 'Zakończenie pakowania',
        'INVOICE_AUTO' => 'Wystawienie faktury (automat)',
    ];

    /**
     * Upewnij się, że tabela logów ma dodatkowe kolumny audytowe.
     *
     * Nie używamy "ADD COLUMN IF NOT EXISTS", bo nie każdy MySQL/MariaDB to wspiera.
     */
    public static function ensureSchema()
    {
        try {
            if ((int) Configuration::get(self::CONF_SCHEMA_OK) === 1) {
                return;
            }
        } catch (Exception $e) {
            // ignore
        }

        $db = Db::getInstance();
        $table = _DB_PREFIX_ . self::TABLE;

        try {
            $cols = $db->executeS('SHOW COLUMNS FROM `' . bqSQL($table) . '`');
            if (!$cols || !is_array($cols)) {
                return;
            }

            $has = [];
            foreach ($cols as $c) {
                if (!empty($c['Field'])) {
                    $has[$c['Field']] = true;
                }
            }

            // id_employee
            if (empty($has['id_employee'])) {
                $db->execute('ALTER TABLE `' . bqSQL($table) . '` ADD COLUMN `id_employee` INT(11) NOT NULL DEFAULT 0 AFTER `id_order`');
            }

            // employee_name
            if (empty($has['employee_name'])) {
                $db->execute('ALTER TABLE `' . bqSQL($table) . '` ADD COLUMN `employee_name` VARCHAR(255) NOT NULL DEFAULT "" AFTER `id_employee`');
            }

            // action
            if (empty($has['action'])) {
                $db->execute('ALTER TABLE `' . bqSQL($table) . '` ADD COLUMN `action` VARCHAR(64) NOT NULL DEFAULT "" AFTER `employee_name`');
            }

            // details (json)
            if (empty($has['details'])) {
                $db->execute('ALTER TABLE `' . bqSQL($table) . '` ADD COLUMN `details` LONGTEXT NULL AFTER `action`');
            }

            // indeksy pomocnicze
            if (empty($has['idx_date_add'])) {
                // "SHOW COLUMNS" nie zwraca indeksów. Sprawdzamy osobno.
            }

            // Index on date_add
            $idx = $db->executeS('SHOW INDEX FROM `' . bqSQL($table) . '`');
            $idxNames = [];
            if (is_array($idx)) {
                foreach ($idx as $i) {
                    if (!empty($i['Key_name'])) {
                        $idxNames[$i['Key_name']] = true;
                    }
                }
            }

            if (empty($idxNames['idx_date_add'])) {
                $db->execute('ALTER TABLE `' . bqSQL($table) . '` ADD INDEX `idx_date_add` (`date_add`)');
            }
            if (empty($idxNames['idx_employee'])) {
                $db->execute('ALTER TABLE `' . bqSQL($table) . '` ADD INDEX `idx_employee` (`id_employee`)');
            }
            if (empty($idxNames['idx_action'])) {
                $db->execute('ALTER TABLE `' . bqSQL($table) . '` ADD INDEX `idx_action` (`action`)');
            }

            try {
                Configuration::updateValue(self::CONF_SCHEMA_OK, 1);
            } catch (Exception $e) {
                // ignore
            }
        } catch (Exception $e) {
            // Jeśli nie możemy migrować (np. brak uprawnień), nie wywalamy całej aplikacji.
            // Wtedy logowanie zadziała w trybie "starym" (tylko message+date_add).
        }
    }

    /**
     * Zapis logu audytowego.
     *
     * @param int $id_order
     * @param string $action
     * @param string $message
     * @param array|string|null $details
     * @param Employee|null $employee
     * @return bool
     */
    public static function log($id_order, $action, $message, $details = null, $employee = null)
    {
        $id_order = (int) $id_order;
        if ($id_order <= 0) {
            return false;
        }

        self::ensureSchema();

        $idEmployee = 0;
        $employeeName = 'System/Automat';

        try {
            if ($employee instanceof Employee && Validate::isLoadedObject($employee)) {
                $idEmployee = (int) $employee->id;
                $employeeName = trim($employee->firstname . ' ' . $employee->lastname);
            } else {
                // spróbuj z sesji modułu
                if (class_exists('BbOrderManagerAuth')) {
                    $emp = BbOrderManagerAuth::getEmployee();
                    if ($emp && Validate::isLoadedObject($emp)) {
                        $idEmployee = (int) $emp->id;
                        $employeeName = trim($emp->firstname . ' ' . $emp->lastname);
                    }
                }
            }
        } catch (Exception $e) {
            // ignore
        }

        $action = (string) $action;
        $action = strtoupper(trim($action));
        if ($action === '') {
            $action = 'INFO';
        }

        $detailsJson = null;
        if (is_array($details)) {
            $detailsJson = json_encode($details, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        } elseif (is_string($details) && trim($details) !== '') {
            $detailsJson = $details;
        }

        $db = Db::getInstance();

        // Spróbuj zapisać z nowymi polami (jeśli kolumny istnieją)
        try {
            return (bool) $db->insert(self::TABLE, [
                'id_order' => $id_order,
                'id_employee' => (int) $idEmployee,
                'employee_name' => pSQL($employeeName),
                'action' => pSQL($action),
                'details' => $detailsJson !== null ? pSQL($detailsJson, true) : null,
                'message' => pSQL((string) $message, true),
                'date_add' => date('Y-m-d H:i:s'),
            ]);
        } catch (Exception $e) {
            // Fallback: stara struktura tabeli
            try {
                return (bool) $db->insert(self::TABLE, [
                    'id_order' => $id_order,
                    'message' => pSQL((string) $message, true),
                    'date_add' => date('Y-m-d H:i:s'),
                ]);
            } catch (Exception $e2) {
                return false;
            }
        }
    }

    /**
     * Zwraca czytelną nazwę akcji (PL) na podstawie kodu.
     */
    public static function getActionLabel($action)
    {
        $code = strtoupper(trim((string) $action));
        if ($code === '') {
            $code = 'INFO';
        }
        if (isset(self::$actionLabels[$code])) {
            return self::$actionLabels[$code];
        }
        // fallback: pokaż kod, żeby było wiadomo co się stało
        return $code;
    }

    /**
     * Usuwa dopisek "(przez: ... )" z końca wiadomości.
     */
    public static function stripEmployeeSuffix($message)
    {
        $m = (string) $message;
        $m = preg_replace('/\s*\(przez:\s*[^\)]*\)\s*$/u', '', $m);
        return trim($m);
    }

    /**
     * Wyciąga nazwę pracownika z dopisku "(przez: ... )" jeśli istnieje.
     */
    public static function extractEmployeeFromMessage($message)
    {
        $m = (string) $message;
        if (preg_match('/\(przez:\s*([^\)]*)\)\s*$/u', $m, $mm)) {
            $name = trim($mm[1]);
            return $name;
        }
        return '';
    }

    /**
     * Fallback: dla starych wpisów (bez action) próbujemy rozpoznać akcję po treści.
     */
    public static function inferActionFromMessage($message)
    {
        $m = self::stripEmployeeSuffix($message);
        if ($m === '') {
            return 'INFO';
        }

        // Allegro
        if (preg_match('/^ALLEGRO:\s*Utworzono\s+przesy\w+/ui', $m)) {
            return 'SHIPMENT_CREATE';
        }
        if (preg_match('/^ALLEGRO:\s*Pobrano\s+etykiet/ui', $m)) {
            return 'LABEL_DOWNLOAD';
        }

        // Produkty
        if (preg_match('/^DODANO\s+PRODUKT:/ui', $m)) {
            return 'PRODUCT_ADD';
        }
        if (preg_match('/^EDYCJA\s+PRODUKTU:/ui', $m)) {
            return 'PRODUCT_EDIT';
        }
        if (preg_match('/^USUNI[ĘE]TO\s+PRODUKT:/ui', $m)) {
            return 'PRODUCT_DELETE';
        }

        // Statusy / archiwum
        if (preg_match('/^ZMIANA\s+STATUSU:/ui', $m)) {
            return 'STATUS_CHANGE';
        }
        if (preg_match('/^PRZENIESIONO\s+DO\s+ARCHIWUM/ui', $m)) {
            return 'STATUS_ARCHIVE';
        }
        if (preg_match('/^UTWORZONO\s+NOWE\s+ZAM[ÓO]WIENIE/ui', $m)) {
            return 'ORDER_CREATE';
        }

        // Adres / dostawa
        if (preg_match('/^ADRES:/ui', $m)) {
            return 'ADDRESS_UPDATE';
        }
        if (preg_match('/^DOSTAWA:\s*Dodano\s+numer\s+\x{015B}ledzenia/ui', $m) || preg_match('/^DOSTAWA:\s*Dodano\s+numer\s+sledzenia/ui', $m)) {
            return 'TRACKING_ADD';
        }

        // Płatności
        if (preg_match('/^P[ŁL]ATNO[ŚS][ĆC]:/ui', $m)) {
            if (preg_match('/Wygenerowano\s+link/ui', $m)) {
                return 'PAYMENT_LINK';
            }
            if (preg_match('/Zmieniono/ui', $m)) {
                return 'PAYMENT_UPDATE';
            }
            return 'PAYMENT_UPDATE';
        }

        // Pakowanie / faktury
        if (preg_match('/^PAKOWANIE:/ui', $m)) {
            return 'PACKING_DONE';
        }
        if (preg_match('/^DX\s*Fakturownia:/ui', $m)) {
            return 'INVOICE_AUTO';
        }

        return 'INFO';
    }
}
