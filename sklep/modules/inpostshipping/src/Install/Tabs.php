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

namespace InPost\Shipping\Install;

use InPost\Shipping\DataProvider\LanguageDataProvider;
use InPostShipping;
use Tab;

class Tabs implements InstallerInterface
{
    public const TRANSLATION_SOURCE = 'Tabs';

    public const AJAX_CONTROLLER_NAME = 'AdminInPostAjax';
    public const DISPATCH_POINT_CONTROLLER_NAME = 'AdminInPostDispatchPoints';

    public const SHIPMENTS_CONTROLLER_NAME = 'AdminInPostConfirmedShipments';
    public const DISPATCH_ORDERS_CONTROLLER_NAME = 'AdminInPostDispatchOrders';
    public const SENT_SHIPMENTS_CONTROLLER_NAME = 'AdminInPostSentShipments';

    private const PARENT_SHIPMENTS_TAB_NAME = 'AdminParentInPostShipments';

    private const SHIPMENTS_TAB_NAMES = [
        self::SHIPMENTS_CONTROLLER_NAME,
        self::DISPATCH_ORDERS_CONTROLLER_NAME,
        self::SENT_SHIPMENTS_CONTROLLER_NAME,
    ];

    protected $module;
    protected $languageDataProvider;

    public function __construct(
        InPostShipping $module,
        LanguageDataProvider $languageDataProvider
    ) {
        $this->module = $module;
        $this->languageDataProvider = $languageDataProvider;
    }

    public function install(): bool
    {
        $result = true;

        $parentTab = self::PARENT_SHIPMENTS_TAB_NAME;

        if ($parentId = $this->installTab($parentTab, Tab::getIdFromClassName('AdminParentShipping'))) {
            foreach (self::SHIPMENTS_TAB_NAMES as $className) {
                $result &= (bool) $this->installTab($className, $parentId);
            }
        } else {
            $result = false;
        }

        $result &= (bool) $this->installTab(self::AJAX_CONTROLLER_NAME, -1);
        $result &= (bool) $this->installTab(self::DISPATCH_POINT_CONTROLLER_NAME, -1);

        return (bool) $result;
    }

    public function uninstall()
    {
        $result = true;

        /** @var Tab $tab */
        foreach (Tab::getCollectionFromModule($this->module->name) as $tab) {
            $result &= $tab->delete();
        }

        return $result;
    }

    protected function installTab(string $className, int $parentId): ?int
    {
        if ($id_tab = Tab::getIdFromClassName($className)) {
            return $id_tab;
        }

        $tab = new Tab();
        $tab->module = $this->module->name;
        $tab->class_name = $className;
        $tab->id_parent = $parentId;
        $tab->name = $this->getTabName($className);

        if ($tab->add()) {
            return $tab->id;
        }

        return null;
    }

    protected function getTabName(string $className): array
    {
        $name = [];

        foreach ($this->languageDataProvider->getLanguages() as $id_lang => $language) {
            switch ($className) {
                case self::PARENT_SHIPMENTS_TAB_NAME:
                    $name[$id_lang] = $this->module->l('InPost shipments', self::TRANSLATION_SOURCE, $language['locale']);
                    break;
                case self::SHIPMENTS_CONTROLLER_NAME:
                    $name[$id_lang] = $this->module->l('Confirmed shipments', self::TRANSLATION_SOURCE, $language['locale']);
                    break;
                case self::DISPATCH_ORDERS_CONTROLLER_NAME:
                    $name[$id_lang] = $this->module->l('Dispatch orders', self::TRANSLATION_SOURCE, $language['locale']);
                    break;
                case self::SENT_SHIPMENTS_CONTROLLER_NAME:
                    $name[$id_lang] = $this->module->l('Sent shipments', self::TRANSLATION_SOURCE, $language['locale']);
                    break;
                case self::DISPATCH_POINT_CONTROLLER_NAME:
                    $name[$id_lang] = $this->module->l('InPost Dispatch Points', self::TRANSLATION_SOURCE, $language['locale']);
                    break;
                default:
                    $name[$id_lang] = $this->module->name;
                    break;
            }
        }

        return $name;
    }
}
