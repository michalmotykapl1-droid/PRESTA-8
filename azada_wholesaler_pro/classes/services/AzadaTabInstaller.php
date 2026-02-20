<?php

class AzadaTabInstaller
{
    private static $tabs = [
        [
            'name' => 'INTEGRACJA HURTOWNI PRO',
            'class_name' => 'AdminAzadaParent',
            'parent_class' => 'SELL', // Podpinamy pod główne menu "Sprzedaż"
            'icon' => 'cloud_circle',
            'active' => true
        ],
        [
            'name' => 'Hurtownie',
            'class_name' => 'AdminAzadaWholesaler',
            'parent_class' => 'AdminAzadaParent',
            'active' => true
        ],
        [
            'name' => 'Lista Produktów (Poczekalnia)',
            'class_name' => 'AdminAzadaProductList',
            'parent_class' => 'AdminAzadaParent',
            'active' => true
        ],
        [
            'name' => 'Mapowanie Kategorii',
            'class_name' => 'AdminAzadaCategoryMap',
            'parent_class' => 'AdminAzadaParent',
            'active' => true
        ],
        [
            'name' => 'Mapowanie Producentów',
            'class_name' => 'AdminAzadaManufacturerMap',
            'parent_class' => 'AdminAzadaParent',
            'active' => true
        ],
        [
            'name' => 'Mapowanie Cech',
            'class_name' => 'AdminAzadaFeatureMap',
            'parent_class' => 'AdminAzadaParent',
            'active' => true
        ],
        [
            'name' => 'Zarządzanie Cenami',
            'class_name' => 'AdminAzadaPriceRules',
            'parent_class' => 'AdminAzadaParent',
            'active' => true
        ],
        [
            'name' => 'Konfiguracja',
            'class_name' => 'AdminAzadaSettings',
            'parent_class' => 'AdminAzadaParent',
            'active' => true
        ],
        [
            'name' => 'Weryfikacja FV',
            'class_name' => 'AdminAzadaVerification',
            'parent_class' => 'AdminAzadaParent',
            'active' => true
        ],
        // Ukryte kontrolery (bez menu, ale potrzebne dla linków)
        [
            'name' => 'Dokumenty CSV (B2B)',
            'class_name' => 'AdminAzadaOrders',
            'parent_class' => 'AdminAzadaParent',
            'active' => true
        ],
        [
            'name' => 'Faktury Zakupu',
            'class_name' => 'AdminAzadaInvoices',
            'parent_class' => 'AdminAzadaParent',
            'active' => true
        ],
        [
            'name' => 'Logi Systemowe',
            'class_name' => 'AdminAzadaLogs',
            'parent_class' => 'AdminAzadaParent',
            'active' => false
        ]
    ];

    public static function installTabs($moduleName)
    {
        foreach (self::$tabs as $tabData) {
            if (!self::installSingleTab($tabData, $moduleName)) {
                return false;
            }
        }
        return true;
    }

    private static function installSingleTab($tabData, $moduleName)
    {
        $idTab = (int)Tab::getIdFromClassName($tabData['class_name']);
        if ($idTab) {
            $tab = new Tab($idTab);
            $tab->active = $tabData['active'] ? 1 : 0;
            foreach (Language::getLanguages(true) as $lang) {
                $tab->name[$lang['id_lang']] = $tabData['name'];
            }

            if ($tabData['parent_class'] === 'SELL') {
                $sql = new DbQuery();
                $sql->select('id_tab')->from('tab')->where('class_name = "SELL"');
                $parentId = (int)Db::getInstance()->getValue($sql);
                if (!$parentId) {
                    $parentId = (int)Tab::getIdFromClassName('AdminParentOrders');
                }
                if (!$parentId) {
                    $parentId = 0;
                }
            } else {
                $parentId = (int)Tab::getIdFromClassName($tabData['parent_class']);
            }

            if ($parentId) {
                $tab->id_parent = $parentId;
            }
            if (isset($tabData['icon']) && $tabData['icon']) {
                $tab->icon = $tabData['icon'];
            }
            $tab->module = $moduleName;

            return $tab->update();
        }

        $tab = new Tab();
        $tab->active = $tabData['active'] ? 1 : 0;
        $tab->class_name = $tabData['class_name'];
        $tab->name = array();
        foreach (Language::getLanguages(true) as $lang) {
            $tab->name[$lang['id_lang']] = $tabData['name'];
        }

        // Ustalanie ID rodzica
        if ($tabData['parent_class'] === 'SELL') {
            // Specjalny przypadek dla głównego rodzica w sekcji SELL
            $parentId = (int)Tab::getIdFromClassName('AdminParentOrders'); // Często używany jako punkt odniesienia w sekcji Sprzedaż
            // Lepsze podejście: pobierz ID zakładki "Sprzedaż" (zazwyczaj ID 2 lub klasa 'SELL')
            // W Presta 1.7+ sekcje nadrzędne nie zawsze mają class_name.
            // Spróbujmy znaleźć ID sekcji SELL.
            $sql = new DbQuery();
            $sql->select('id_tab')->from('tab')->where('class_name = "SELL"'); // W PS 1.7 'SELL' to klasa nadrzędna
            $parentId = Db::getInstance()->getValue($sql);
            
            if (!$parentId) {
                // Fallback dla starszych PS lub innej struktury
                $parentId = 0; // Główny poziom menu
            }
        } else {
            $parentId = (int)Tab::getIdFromClassName($tabData['parent_class']);
        }

        $tab->id_parent = $parentId;
        $tab->module = $moduleName;
        
        if (isset($tabData['icon']) && $tabData['icon']) {
            $tab->icon = $tabData['icon'];
        }

        return $tab->add();
    }

    public static function uninstallTabs()
    {
        foreach (self::$tabs as $tabData) {
            $idTab = (int)Tab::getIdFromClassName($tabData['class_name']);
            if ($idTab) {
                $tab = new Tab($idTab);
                try {
                    $tab->delete();
                } catch (Exception $e) {
                    // Ignoruj błędy usuwania
                }
            }
        }
        return true;
    }
}
