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

        return ['status' => 'error', 'msg' => 'Brak obsługi importu dla tej integracji (brak metody importProducts).'];
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
        $rawTable = trim((string)$wholesaler->raw_table_name);
        if ($rawTable === '' || strpos($rawTable, 'azada_raw_') !== 0) {
            return '';
        }

        $slug = strtolower(substr($rawTable, strlen('azada_raw_')));
        $slug = preg_replace('/[^a-z0-9]/', '', (string)$slug);
        if ($slug === '') {
            return '';
        }

        foreach (get_declared_classes() as $className) {
            if (strpos($className, 'Azada') !== 0) {
                continue;
            }

            $normalized = strtolower(preg_replace('/[^a-z0-9]/', '', preg_replace('/([a-z])([A-Z])/', '$1_$2', substr($className, 5))));
            if ($normalized === $slug) {
                return $className;
            }
        }

        return '';
    }
}
