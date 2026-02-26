<?php
/**
 * 2007-2023 PrestaShop
 *
 * Kontroler front-end dla zadań CRON modułu Historia Ceny Omnibus.
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

// Dołączamy serwis, który zawiera logikę aktualizacji cen.
require_once _PS_MODULE_DIR_ . 'omnibuspricehistory/services/OmnibusPriceHistoryService.php';

class OmnibuspricehistoryCronModuleFrontController extends ModuleFrontController
{
    public function initContent()
    {
        parent::initContent();

        $token = Tools::getValue('token');
        // Generujemy oczekiwane tokeny, używając tego samego szyfrowania co w ConfigController
        $expectedToken = Tools::substr(Tools::encrypt('omnibuspricehistory/cron'), 0, 10);
        $expectedPromo = Tools::substr(Tools::encrypt('omnibuspricehistory/cron_promo'), 0, 10);
        $isPromo       = (bool)Tools::getValue('promo');

        // Sprawdzamy, czy token jest poprawny dla danego typu CRONa
        if ((!$isPromo && $token !== $expectedToken) ||
            ($isPromo && $token !== $expectedPromo)
        ) {
            header('HTTP/1.1 403 Forbidden');
            die('Access denied');
        }

        // Wybieramy odpowiedni rozmiar paczki z konfiguracji, w zależności od tego, czy to CRON promocyjny
        $batchSize = Configuration::get(
            $isPromo
              ? 'OMNIBUS_PROMO_CRON_BATCH_SIZE'
              : 'OMNIBUS_CRON_BATCH_SIZE'
        );

        // Jeśli rozmiar paczki to 0, traktujemy to jako "wszystkie" (brak limitu)
        // W tym przypadku przekazujemy null do serwisu, zakładając, że serwis to obsłuży
        if ((int)$batchSize === 0) {
            $batchSize = null;
        }

        /** @var OmnibusPriceHistoryService $service */
        // Tworzymy instancję serwisu, który wykona faktyczną logikę aktualizacji cen
        $service = new OmnibusPriceHistoryService($this->module);

        // Wywołujemy odpowiednią metodę serwisu w zależności od typu CRONa
        if ($isPromo) {
            $service->updatePromoPrices($batchSize);
        } else {
            $service->updateAllPrices($batchSize);
        }

        // Zwracamy "OK" po pomyślnym wykonaniu zadania
        echo 'OK';
        exit;
    }
}
