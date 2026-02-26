<?php
/**
 * Copyright since 2021 InPost S.A.
 *
 * NOTICE OF LICENSE
 *
 * Licensed under the EUPL-1.2 or later.
 * You may not use this work except in compliance with the Licence.
 *
 * You may obtain a copy of the Licence at:
 * https://joinup.ec.europa.eu/software/page/eupl
 * It is also bundled with this package in the file LICENSE.txt
 *
 * Unless required by applicable law or agreed to in writing,
 * software distributed under the Licence is distributed on an AS IS basis,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the Licence for the specific language governing permissions
 * and limitations under the Licence.
 *
 * @author    InPost S.A.
 * @copyright Since 2021 InPost S.A.
 * @license   https://joinup.ec.europa.eu/software/page/eupl
 */

namespace InPost\Shipping\ChoiceProvider;

class ModulePageChoiceProvider implements ChoiceProviderInterface
{
    public function getChoices()
    {
        $choices = [];

        // Zamiast Meta::getPages() (które skanuje m.in. "override" ścieżką względną i potrafi wywalić BO),
        // bierzemy kontrolery modułów bezpiecznie, skanując katalog /modules.
        $modulesDir = defined('_PS_MODULE_DIR_') ? _PS_MODULE_DIR_ : null;
        if (!$modulesDir || !is_dir($modulesDir)) {
            return $choices;
        }

        try {
            $it = new \DirectoryIterator($modulesDir);
        } catch (\Exception $e) {
            return $choices;
        }

        foreach ($it as $moduleDir) {
            if ($moduleDir->isDot() || !$moduleDir->isDir()) {
                continue;
            }

            $moduleName = $moduleDir->getFilename();
            $modulePath = $moduleDir->getPathname();

            $controllers = $this->scanModuleControllers($modulePath);

            if (empty($controllers)) {
                continue;
            }

            sort($controllers, SORT_NATURAL | SORT_FLAG_CASE);

            foreach ($controllers as $controller) {
                $choices[$moduleName][] = [
                    'value' => $controller,
                    'label' => $controller,
                ];
            }
        }

        ksort($choices, SORT_NATURAL | SORT_FLAG_CASE);

        return $choices;
    }

    /**
     * Zwraca listę nazw kontrolerów modułu (na podstawie nazw plików PHP).
     * Skanujemy klasyczne ścieżki:
     *  - controllers/front
     *  - controllers/admin
     */
    protected function scanModuleControllers($modulePath)
    {
        $controllers = [];

        $paths = [
            rtrim($modulePath, '/\\') . DIRECTORY_SEPARATOR . 'controllers' . DIRECTORY_SEPARATOR . 'front',
            rtrim($modulePath, '/\\') . DIRECTORY_SEPARATOR . 'controllers' . DIRECTORY_SEPARATOR . 'admin',
        ];

        foreach ($paths as $path) {
            if (!is_dir($path)) {
                continue;
            }

            $files = glob($path . DIRECTORY_SEPARATOR . '*.php');
            if (!$files) {
                continue;
            }

            foreach ($files as $file) {
                if (!is_file($file)) {
                    continue;
                }

                $name = basename($file, '.php');

                // standardowe pliki techniczne – pomijamy
                if ($name === 'index' || $name === '.htaccess') {
                    continue;
                }

                $controllers[] = $name;
            }
        }

        // unikalne + reset indeksów
        $controllers = array_values(array_unique($controllers));

        return $controllers;
    }

    // Zostawiamy metodę dla kompatybilności (nie używana po zmianie logiki),
    // ale niczego nie psuje, gdyby ktoś kiedyś zewnętrznie na nią polegał.
    protected function isModulePage($page)
    {
        return 0 === strncmp($page, 'module-', 7);
    }
}
