<?php
/**
 * BB Order Manager - Simple Employee Auth (Front)
 *
 * Cel:
 * - umożliwić logowanie do panelu Manager/Packing przez konto pracownika PrestaShop
 * - utrzymywać osobną sesję modułu (nie modyfikujemy psAdmin)
 * - zapewnić prostą ochronę CSRF dla endpointów API (nagłówek X-BBOM-CSRF)
 *
 * Uwaga dot. SSO (psAdmin):
 * - Moduł potrafi automatycznie utworzyć sesję, jeśli pracownik jest zalogowany do BO.
 * - Jeżeli użytkownik kliknie "Wyloguj" w module, to ustawiamy flagę blokującą auto-SSO,
 *   aby odświeżenie strony nie logowało go z powrotem.
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class BbOrderManagerAuth
{
    /**
     * Konfiguracja dostępu (Presta Configuration)
     *
     * Tryby dostępu (BB_OM_ACCESS_MODE):
     * - all       -> dostęp dla wszystkich aktywnych pracowników
     * - profiles  -> dostęp tylko dla pracowników z wybranych profili
     * - employees -> dostęp tylko dla wybranych pracowników
     *
     * (Kompatybilność wsteczna):
     * Jeśli BB_OM_ACCESS_MODE nie istnieje, wnioskujemy tryb z list (employees > profiles > all).
     */
    const CONF_ACCESS_MODE = 'BB_OM_ACCESS_MODE';
    const CONF_ALLOWED_PROFILES  = 'BB_OM_ALLOWED_PROFILES';
    const CONF_ALLOWED_EMPLOYEES = 'BB_OM_ALLOWED_EMPLOYEES';

    /**
     * Nazwa ciasteczka sesji modułu.
     * Uwaga: to nie jest cookie FO/BO Prestashop. To osobna, zaszyfrowana Cookie(…)
     */
    const COOKIE_NAME = 'bbomAdmin';

    const FIELD_EMPLOYEE_ID = 'id_employee';
    const FIELD_TOKEN = 'token';
    const FIELD_EXPIRES = 'exp';

    /**
     * Flaga blokująca auto-logowanie z psAdmin po ręcznym wylogowaniu z modułu.
     */
    const FIELD_SSO_BLOCK = 'sso_block';

    /**
     * Ile sekund ma trwać sesja po zalogowaniu
     */
    const SESSION_TTL = 43200; // 12h

    /**
     * Cache obiektu Cookie w obrębie jednego requestu.
     *
     * Dlaczego:
     * - Cookie::write() ustawia nagłówek Set-Cookie, ale nowa instancja Cookie utworzona w tym samym
     *   requestcie może nie widzieć jeszcze świeżo zapisanych pól (bo $_COOKIE nie zmienia się automatycznie).
     * - Przy auto-logowaniu (psAdmin) musimy zwrócić poprawny CSRF już w odpowiedzi /auth?action=me.
     */
    private static $cookieObject = null;

    /**
     * Ostatni powód odmowy w bieżącym requestcie (np. FORBIDDEN).
     * Pomaga UI odróżnić "sesja wygasła" od "brak uprawnień".
     */
    private static $lastDenyCode = '';

    /**
     * Zwraca ostatni powód odmowy (w obrębie requestu).
     */
    public static function getLastDenyCode()
    {
        return (string) self::$lastDenyCode;
    }

    /**
     * Zamień CSV/JSON na tablicę intów.
     */
    private static function parseIds($value)
    {
        if (is_array($value)) {
            $arr = $value;
        } else {
            $value = (string) $value;
            $value = trim($value);
            if ($value === '') {
                return [];
            }

            // JSON
            if ($value !== '' && $value[0] === '[') {
                $decoded = json_decode($value, true);
                if (is_array($decoded)) {
                    $arr = $decoded;
                } else {
                    $arr = explode(',', $value);
                }
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

        // unique + reindex
        $out = array_values(array_unique($out));
        return $out;
    }

    /**
     * Lista dozwolonych profili (pusta = wszyscy).
     */
    public static function getAllowedProfiles()
    {
        try {
            $v = Configuration::get(self::CONF_ALLOWED_PROFILES);
        } catch (Exception $e) {
            $v = '';
        }
        return self::parseIds($v);
    }

    /**
     * Lista dozwolonych pracowników (pusta = nie ograniczaj po pracownikach).
     */
    public static function getAllowedEmployees()
    {
        try {
            $v = Configuration::get(self::CONF_ALLOWED_EMPLOYEES);
        } catch (Exception $e) {
            $v = '';
        }
        return self::parseIds($v);
    }

    /**
     * Zwraca tryb dostępu (all / profiles / employees).
     */
    public static function getAccessMode()
    {
        try {
            $v = (string) Configuration::get(self::CONF_ACCESS_MODE);
        } catch (Exception $e) {
            $v = '';
        }

        $v = strtolower(trim($v));
        if (in_array($v, ['all', 'profiles', 'employees'], true)) {
            return $v;
        }

        // kompatybilność wsteczna
        $allowedEmployees = self::getAllowedEmployees();
        if (!empty($allowedEmployees)) {
            return 'employees';
        }
        $allowedProfiles = self::getAllowedProfiles();
        if (!empty($allowedProfiles)) {
            return 'profiles';
        }
        return 'all';
    }

    /**
     * Czy dany pracownik ma dostęp do modułu.
     */
    public static function isEmployeeAllowed(Employee $employee)
    {
        if (!Validate::isLoadedObject($employee) || !(int) $employee->active) {
            return false;
        }

        $mode = self::getAccessMode();

        if ($mode === 'employees') {
            $allowedEmployees = self::getAllowedEmployees();
            if (empty($allowedEmployees)) {
                // fail-safe: brak listy w trybie "employees" = nikt
                return false;
            }
            return in_array((int) $employee->id, $allowedEmployees, true);
        }

        if ($mode === 'profiles') {
            $allowedProfiles = self::getAllowedProfiles();
            if (empty($allowedProfiles)) {
                // fail-safe: brak listy w trybie "profiles" = nikt
                return false;
            }
            return in_array((int) $employee->id_profile, $allowedProfiles, true);
        }

        // all
        return true;
    }

    /**
     * Pobierz obiekt Cookie modułu.
     */
    public static function getCookie()
    {
        if (self::$cookieObject instanceof Cookie) {
            return self::$cookieObject;
        }

        self::$cookieObject = new Cookie(self::COOKIE_NAME);
        return self::$cookieObject;
    }

    /**
     * Czy auto-logowanie z BO jest zablokowane (po ręcznym wylogowaniu z modułu)
     */
    private static function isSsoBlocked()
    {
        try {
            $cookie = self::getCookie();
            return !empty($cookie->{self::FIELD_SSO_BLOCK});
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Zwraca ID pracownika jeżeli sesja jest ważna.
     */
    public static function getEmployeeId()
    {
        self::$lastDenyCode = '';
        try {
            $cookie = self::getCookie();
            $id = isset($cookie->{self::FIELD_EMPLOYEE_ID}) ? (int)$cookie->{self::FIELD_EMPLOYEE_ID} : 0;
            $token = isset($cookie->{self::FIELD_TOKEN}) ? (string)$cookie->{self::FIELD_TOKEN} : '';
            $exp = isset($cookie->{self::FIELD_EXPIRES}) ? (int)$cookie->{self::FIELD_EXPIRES} : 0;

            if ($id <= 0 || $token === '' || $exp <= 0) {
                // SSO: jeśli pracownik jest zalogowany do BO, tworzymy sesję modułu automatycznie
                // Ale: po ręcznym wylogowaniu z modułu blokujemy SSO, żeby Ctrl+F5 nie logował z powrotem.
                if (!self::isSsoBlocked()) {
                    $autoId = (int) self::tryAutoLoginFromPsAdmin();
                    return $autoId;
                }
                return 0;
            }

            if (time() > $exp) {
                // sesja wygasła (czyścimy sesję, ale NIE blokujemy SSO)
                self::logout(false);
                return 0;
            }

            // dodatkowo: sprawdź czy pracownik jest aktywny
            $employee = new Employee($id);
            if (!Validate::isLoadedObject($employee) || !(int)$employee->active) {
                // czyścimy sesję, ale nie blokujemy SSO
                self::logout(false);
                return 0;
            }

            // uprawnienia: sprawdź czy pracownik ma dostęp do modułu
            if (!self::isEmployeeAllowed($employee)) {
                self::$lastDenyCode = 'FORBIDDEN';
                // czyścimy sesję (nie blokujemy SSO, bo to może być np. po zmianie konfiguracji)
                self::logout(false);
                return 0;
            }

            return $id;
        } catch (Exception $e) {
            return 0;
        }
    }

    /**
     * Jeśli istnieje sesja BO (psAdmin), tworzymy sesję modułu.
     */
    private static function tryAutoLoginFromPsAdmin()
    {
        try {
            $boCookie = new Cookie('psAdmin');
            $id = isset($boCookie->id_employee) ? (int)$boCookie->id_employee : 0;
            if ($id <= 0) {
                return 0;
            }

            $emp = new Employee($id);
            if (!Validate::isLoadedObject($emp) || !(int)$emp->active) {
                return 0;
            }

            // uprawnienia
            if (!self::isEmployeeAllowed($emp)) {
                return 0;
            }

            $ok = self::login($emp);
            return $ok ? (int) $emp->id : 0;
        } catch (Exception $e) {
            return 0;
        }
    }

    /**
     * Czy zalogowany
     */
    public static function isLogged()
    {
        return self::getEmployeeId() > 0;
    }

    /**
     * Zwraca obiekt Employee albo null
     */
    public static function getEmployee()
    {
        $id = (int) self::getEmployeeId();
        if ($id <= 0) {
            return null;
        }
        $emp = new Employee($id);
        if (!Validate::isLoadedObject($emp)) {
            return null;
        }
        return $emp;
    }

    /**
     * Ustaw sesję modułu dla danego pracownika
     */
    public static function login(Employee $employee)
    {
        // uprawnienia
        if (!self::isEmployeeAllowed($employee)) {
            self::$lastDenyCode = 'FORBIDDEN';
            return false;
        }

        $token = self::generateToken();
        $exp = time() + self::SESSION_TTL;

        $cookie = self::getCookie();
        $cookie->{self::FIELD_EMPLOYEE_ID} = (int)$employee->id;
        $cookie->{self::FIELD_TOKEN} = (string)$token;
        $cookie->{self::FIELD_EXPIRES} = (int)$exp;
        // po udanym logowaniu odblokuj SSO
        $cookie->{self::FIELD_SSO_BLOCK} = 0;

        // zapis cookie
        $cookie->write();

        return [
            'token' => $token,
            'expires' => $exp,
        ];
    }

    /**
     * Wyloguj (usuń sesję)
     *
     * @param bool $blockSso Jeśli true, to po wylogowaniu blokujemy auto-SSO z psAdmin.
     *                       To jest pożądane dla akcji "Wyloguj" w module.
     *                       Jeśli false, czyścimy sesję (np. wygasła), ale nie blokujemy SSO.
     */
    public static function logout($blockSso = true)
    {
        try {
            $cookie = self::getCookie();

            // Czyścimy sesję modułu, ale NIE niszczymy ciasteczka całkowicie,
            // bo chcemy zapamiętać blokadę SSO.
            $cookie->{self::FIELD_EMPLOYEE_ID} = 0;
            $cookie->{self::FIELD_TOKEN} = '';
            $cookie->{self::FIELD_EXPIRES} = 0;
            $cookie->{self::FIELD_SSO_BLOCK} = $blockSso ? 1 : 0;

            $cookie->write();
        } catch (Exception $e) {
            // ignore
        }
    }

    /**
     * Token CSRF wyliczany deterministycznie na podstawie tokenu sesji.
     */
    public static function getCsrfToken()
    {
        try {
            $cookie = self::getCookie();
            $token = isset($cookie->{self::FIELD_TOKEN}) ? (string)$cookie->{self::FIELD_TOKEN} : '';
            if ($token === '') {
                return '';
            }
            return hash_hmac('sha256', $token, _COOKIE_KEY_);
        } catch (Exception $e) {
            return '';
        }
    }

    /**
     * Pobierz token CSRF z żądania.
     */
    public static function getRequestCsrfToken()
    {
        // nagłówek: X-BBOM-CSRF
        if (!empty($_SERVER['HTTP_X_BBOM_CSRF'])) {
            return (string) $_SERVER['HTTP_X_BBOM_CSRF'];
        }
        // fallback: parametr
        $v = Tools::getValue('bbom_csrf');
        if ($v) {
            return (string) $v;
        }
        return '';
    }

    /**
     * Sprawdź CSRF.
     */
    public static function validateCsrf()
    {
        $expected = (string) self::getCsrfToken();
        $given = (string) self::getRequestCsrfToken();

        if ($expected === '' || $given === '') {
            return false;
        }

        return hash_equals($expected, $given);
    }

    /**
     * Wymuś autoryzację JSON (401)
     */
    public static function denyJson($httpCode, $message, $code)
    {
        if (ob_get_length()) {
            @ob_clean();
        }
        http_response_code((int)$httpCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => false,
            'error' => $message,
            'error_code' => $code,
        ]);
        die();
    }

    /**
     * Wymuś autoryzację dla API (sesja + CSRF)
     */
    public static function enforceApiAuth()
    {
        if (!self::isLogged()) {
            self::denyJson(401, 'AUTH_REQUIRED', 'AUTH_REQUIRED');
        }
        if (!self::validateCsrf()) {
            self::denyJson(403, 'CSRF_INVALID', 'CSRF_INVALID');
        }
    }

    /**
     * Losowy token sesji
     */
    private static function generateToken()
    {
        try {
            return bin2hex(random_bytes(32));
        } catch (Exception $e) {
            // fallback
            return sha1(uniqid('bbom', true) . mt_rand());
        }
    }
}
