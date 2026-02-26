<?php

$integrationsDir = dirname(__FILE__) . '/integrations';
if (is_dir($integrationsDir)) {
    foreach (glob($integrationsDir . '/*.php') as $integrationFile) {
        if (basename($integrationFile) === 'index.php') {
            continue;
        }
        require_once($integrationFile);
    }
}

class AzadaImportEngine
{
    public function runFullImport($id_wholesaler)
    {
        $wholesaler = new AzadaWholesaler($id_wholesaler);

        if (!Validate::isLoadedObject($wholesaler)) {
            return ['status' => 'error', 'msg' => 'Błąd ID'];
        }

        $autoResult = $this->tryAutoIntegrationImport($wholesaler);
        if ($autoResult !== null) {
            return $autoResult;
        }

        $resolvedClass = $this->resolveIntegrationClassName($wholesaler);
        $rawTable = trim((string)$wholesaler->raw_table_name);

        return [
            'status' => 'error',
            'msg' => 'Brak metody importProducts dla integracji. raw_table_name: ' . $rawTable . '; klasa: ' . ($resolvedClass !== '' ? $resolvedClass : '(nie wykryto)'),
        ];
    }



    /**
     * Tryb lekki importu (delta) – jeśli integracja wspiera.
     *
     * Integracja może opcjonalnie implementować:
     * - public static function importProductsLight(AzadaWholesaler $wholesaler)
     *
     * Jeśli brak wsparcia, zwracamy status=skipped.
     */
    public function runLightImport($id_wholesaler)
    {
        $wholesaler = new AzadaWholesaler($id_wholesaler);

        if (!Validate::isLoadedObject($wholesaler)) {
            return ['status' => 'error', 'msg' => 'Błąd ID'];
        }

        $className = $this->resolveIntegrationClassName($wholesaler);
        if ($className === '' || !class_exists($className)) {
            return ['status' => 'error', 'msg' => 'Nie wykryto klasy integracji dla raw_table_name: ' . (string)$wholesaler->raw_table_name];
        }

        if (is_callable([$className, 'importProductsLight'])) {
            return call_user_func([$className, 'importProductsLight'], $wholesaler);
        }

        return ['status' => 'skipped', 'msg' => 'Integracja nie wspiera trybu LIGHT (brak metody importProductsLight).'];
    }

    private function tryAutoIntegrationImport($wholesaler)
    {
        $className = $this->resolveIntegrationClassName($wholesaler);
        if ($className === '' || !class_exists($className)) {
            return null;
        }

        if (!is_callable([$className, 'importProducts'])) {
            return null;
        }

        return call_user_func([$className, 'importProducts'], $wholesaler);
    }

    private function resolveIntegrationClassName($wholesaler)
    {
        $rawTable = strtolower(trim((string)$wholesaler->raw_table_name));
        if ($rawTable === '') {
            return '';
        }

        $markerPos = strpos($rawTable, 'azada_raw_');
        if ($markerPos === false) {
            return '';
        }

        $slug = substr($rawTable, $markerPos + strlen('azada_raw_'));
        $slug = preg_replace('/[^a-z0-9_]/', '', (string)$slug);
        $slug = trim((string)$slug, '_');
        if ($slug === '') {
            return '';
        }

        // 1) Próba konwencyjna: azada_raw_eko_wital => AzadaEkoWital
        $parts = array_filter(explode('_', $slug), function ($part) {
            return $part !== '';
        });

        $conventionClass = 'Azada';
        foreach ($parts as $part) {
            $conventionClass .= ucfirst(strtolower($part));
        }

        if (class_exists($conventionClass)) {
            return $conventionClass;
        }

        // 2) Próba bez podkreśleń: bioplanet => AzadaBioPlanet (różnice camelCase)
        $slugCompact = preg_replace('/[^a-z0-9]/', '', $slug);
        foreach (get_declared_classes() as $className) {
            if (strpos($className, 'Azada') !== 0) {
                continue;
            }

            $tail = substr($className, 5);
            $tailCompact = strtolower(preg_replace('/[^a-z0-9]/', '', $tail));
            if ($tailCompact === $slugCompact) {
                return $className;
            }
        }

        return '';
    }
}
