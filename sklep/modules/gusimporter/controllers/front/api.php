<?php
/**
 * GUS Importer API Front Controller
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class GusImporterApiModuleFrontController extends ModuleFrontController
{
    /**
     * Disable page cache & layout
     */
    public $ssl = true;
    public $display_header = false;
    public $display_footer = false;
    public $display_header_javascript = false;

    public function initContent()
    {
        parent::initContent();

        header('Content-Type: application/json; charset=utf-8');

        $debug = (bool) Configuration::get(GusImporter::CONFIG_DEBUGMODE);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode([
                'success' => false,
                'error' => 'Metoda niedozwolona. Użyj żądania POST.',
                'debug'  => $debug ? 'Method: '.$_SERVER['REQUEST_METHOD'] : null,
            ]);
            exit;
        }

        $rawContent = file_get_contents('php://input');
        $payload = json_decode($rawContent, true);

        if (!is_array($payload) || empty($payload['nip'])) {
            echo json_encode([
                'success' => false,
                'error' => 'Brak numeru NIP w żądaniu.',
                'debug'  => $debug ? 'Payload: '.$rawContent : null,
            ]);
            exit;
        }

        $nip = preg_replace('/\D+/', '', (string) $payload['nip']);
        if (strlen($nip) !== 10) {
            echo json_encode([
                'success' => false,
                'error' => 'Nieprawidłowy format NIP. Wymagane jest 10 cyfr.',
                'debug'  => $debug ? 'NIP after cleaning: '.$nip : null,
            ]);
            exit;
        }

        // Autoloader composera dla rudashi/gusapi
        $moduleDir = _PS_MODULE_DIR_.$this->module->name.'/';
        $autoloader = $moduleDir.'vendor/autoload.php';

        if (!file_exists($autoloader)) {
            echo json_encode([
                'success' => false,
                'error' => 'Brak biblioteki GUS API (rudashi/gusapi). Uruchom "composer install" w katalogu modułu.',
                'debug'  => $debug ? 'Expected autoloader: '.$autoloader : null,
            ]);
            exit;
        }

        require_once $autoloader;

        // Konfiguracja klucza API
        $apiKey = (string) Configuration::get(GusImporter::CONFIG_API_KEY);
        $testMode = (bool) Configuration::get(GusImporter::CONFIG_TESTMODE);

        if ($testMode || $apiKey === '') {
            $apiKey = 'abcde12345abcde12345';
        }

        try {
            $api = new \Rudashi\GusApi\GusApi($apiKey);

            $api->login();
            $company = $api->getByNip($nip);
            $api->logout();

            if (!$company) {
                echo json_encode([
                    'success' => false,
                    'error' => 'Nie znaleziono firmy dla podanego NIP.',
                    'debug'  => $debug ? 'GUS API zwróciło pusty wynik.' : null,
                ]);
                exit;
            }

            // Bezpieczne pobieranie danych z obiektu Company
            $name = method_exists($company, 'getName') ? (string) $company->getName() : '';
            $street = method_exists($company, 'getStreet') ? (string) $company->getStreet() : '';
            $propertyNumber = method_exists($company, 'getPropertyNumber') ? (string) $company->getPropertyNumber() : '';
            $apartmentNumber = method_exists($company, 'getApartmentNumber') ? (string) $company->getApartmentNumber() : '';
            $zip = method_exists($company, 'getZipCode') ? (string) $company->getZipCode() : '';
            $city = method_exists($company, 'getCity') ? (string) $company->getCity() : '';

            echo json_encode([
                'success' => true,
                'name' => $name,
                'street' => $street,
                'propertyNumber' => $propertyNumber,
                'apartmentNumber' => $apartmentNumber,
                'zip' => $zip,
                'city' => $city,
                'debug' => $debug ? 'OK' : null,
            ]);
            exit;
        } catch (\Throwable $e) {
            $msg = 'Błąd komunikacji z GUS.';

            $response = [
                'success' => false,
                'error' => $msg,
            ];

            if ($debug) {
                $response['debug'] = $e->getMessage();
                if (class_exists('PrestaShopLogger')) {
                    PrestaShopLogger::addLog('[GUS Importer] '.$e->getMessage(), 3);
                }
            }

            echo json_encode($response);
            exit;
        }
    }
}
