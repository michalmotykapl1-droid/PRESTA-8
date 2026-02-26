<?php
/**
 * Auth Controller (Front) - logowanie pracownika do modułu
 */

require_once _PS_MODULE_DIR_ . 'bb_ordermanager/classes/BbOrderManagerAuth.php';

class Bb_ordermanagerAuthModuleFrontController extends ModuleFrontController
{
    public $auth = false;
    public $ajax = true;

    public function init()
    {
        parent::init();
        $this->display_header = false;
        $this->display_footer = false;
        $this->content_only = true;
    }

    public function initContent()
    {
        @ini_set('display_errors', 'off');
        error_reporting(0);

        while (ob_get_level()) {
            ob_end_clean();
        }

        header('Content-Type: application/json; charset=utf-8');

        try {
            $action = (string) Tools::getValue('action');

            if ($action === 'me') {
                $this->processMe();
            } elseif ($action === 'login') {
                $this->processLogin();
            } elseif ($action === 'logout') {
                $this->processLogout();
            } else {
                throw new Exception('Nieznana akcja AUTH: ' . $action);
            }
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage(),
            ]);
            die();
        }

        die();
    }

    private function processMe()
    {
        $emp = BbOrderManagerAuth::getEmployee();
        if (!$emp) {
            $resp = [
                'success' => true,
                'logged' => false,
            ];

            $reason = BbOrderManagerAuth::getLastDenyCode();
            if ($reason) {
                $resp['reason'] = $reason;
            }

            echo json_encode($resp);
            return;
        }

        $this->runSecurityCleanup();

        echo json_encode([
            'success' => true,
            'logged' => true,
            'employee' => [
                'id' => (int) $emp->id,
                'firstname' => (string) $emp->firstname,
                'lastname' => (string) $emp->lastname,
                'email' => (string) $emp->email,
            ],
            'csrf_token' => BbOrderManagerAuth::getCsrfToken(),
        ]);
    }

    private function processLogout()
    {
        BbOrderManagerAuth::logout();
        echo json_encode([
            'success' => true,
        ]);
    }

    private function processLogin()
    {
        // akceptujemy POST/GET
        $email = trim((string) Tools::getValue('email'));
        $password = (string) Tools::getValue('password');

        if ($email === '' || $password === '') {
            throw new Exception('Podaj email i hasło.');
        }
        if (!Validate::isEmail($email)) {
            throw new Exception('Nieprawidłowy email.');
        }

        $employee = $this->authenticateEmployee($email, $password);
        if (!$employee) {
            // nie zdradzamy czy email istnieje
            throw new Exception('Nieprawidłowy login lub hasło.');
        }

        $login = BbOrderManagerAuth::login($employee);
        if (!$login) {
            http_response_code(403);
            echo json_encode([
                'success' => false,
                'error' => 'Brak uprawnień do BIGBIO Manager. Skontaktuj się z administratorem.',
                'error_code' => 'FORBIDDEN',
            ]);
            return;
        }

        $this->runSecurityCleanup();

        echo json_encode([
            'success' => true,
            'employee' => [
                'id' => (int) $employee->id,
                'firstname' => (string) $employee->firstname,
                'lastname' => (string) $employee->lastname,
                'email' => (string) $employee->email,
            ],
            'csrf_token' => BbOrderManagerAuth::getCsrfToken(),
        ]);
    }

    /**
     * Krytyczne zabezpieczenie: przenosi legacy pliki (etykiety, debug) z publicznego katalogu modułu
     * do katalogu tymczasowego serwera, aby nie dało się ich pobrać po URL.
     * Robimy to w tle przy każdym "me/login" (metoda i tak wykonuje się tylko raz na request).
     */
    private function runSecurityCleanup()
    {
        $path = _PS_MODULE_DIR_ . 'bb_ordermanager/integrations/BbAllegroProShipping.php';
        if (!file_exists($path)) {
            return;
        }

        require_once $path;

        if (class_exists('BbAllegroProShipping') && method_exists('BbAllegroProShipping', 'secureLegacyFiles')) {
            try {
                BbAllegroProShipping::secureLegacyFiles();
            } catch (Throwable $e) {
                // nie blokuj logowania przez cleanup
            }
        }
    }
    /**
     * Autoryzacja pracownika na bazie kont BO.
     */
    private function authenticateEmployee($email, $password)
    {
        // 1) spróbuj natywnej metody Prestashop (różne wersje mają różne sygnatury)
        if (class_exists('Employee') && method_exists('Employee', 'getByEmail')) {
            try {
                $res = Employee::getByEmail($email, $password);
                if ($res instanceof Employee && Validate::isLoadedObject($res) && (int)$res->active) {
                    return $res;
                }
                if (is_array($res) && isset($res['id_employee'])) {
                    $emp = new Employee((int)$res['id_employee']);
                    if (Validate::isLoadedObject($emp) && (int)$emp->active) {
                        return $emp;
                    }
                }
            } catch (Throwable $e) {
                // metoda mogła nie przyjmować hasła
                try {
                    $res = Employee::getByEmail($email);
                    if ($res instanceof Employee && Validate::isLoadedObject($res) && (int)$res->active) {
                        // zweryfikuj hasło ręcznie
                        if ($this->verifyPassword($password, (string)$res->passwd)) {
                            return $res;
                        }
                    }
                    if (is_array($res) && isset($res['id_employee'])) {
                        $emp = new Employee((int)$res['id_employee']);
                        if (Validate::isLoadedObject($emp) && (int)$emp->active) {
                            if ($this->verifyPassword($password, (string)$emp->passwd)) {
                                return $emp;
                            }
                        }
                    }
                } catch (Throwable $e2) {
                    // dalej fallback SQL
                }
            }
        }

        // 2) fallback: SQL + password_verify/Tools::encrypt
        $row = Db::getInstance()->getRow(
            'SELECT id_employee, passwd, active FROM `' . _DB_PREFIX_ . 'employee` WHERE email = \'' . pSQL($email) . '\''
        );

        if (!$row || !(int)$row['id_employee'] || !(int)$row['active']) {
            return null;
        }

        if (!$this->verifyPassword($password, (string)$row['passwd'])) {
            return null;
        }

        $emp = new Employee((int)$row['id_employee']);
        if (!Validate::isLoadedObject($emp) || !(int)$emp->active) {
            return null;
        }

        return $emp;
    }

    /**
     * Weryfikacja hasła w sposób kompatybilny.
     */
    private function verifyPassword($plain, $storedHash)
    {
        if ($storedHash === '') {
            return false;
        }

        // hash z password_hash()
        $info = password_get_info($storedHash);
        if (!empty($info['algo'])) {
            return password_verify($plain, $storedHash);
        }

        // starsze wersje (deterministyczne)
        if (method_exists('Tools', 'encrypt')) {
            $enc = Tools::encrypt($plain);
            if ($enc && hash_equals($storedHash, $enc)) {
                return true;
            }
        }

        // legacy md5
        $legacy = md5(_COOKIE_KEY_ . $plain);
        if (hash_equals($storedHash, $legacy)) {
            return true;
        }

        return false;
    }
}
