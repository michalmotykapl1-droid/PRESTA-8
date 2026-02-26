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

use InPost\Shipping\Adapter\AssetsManager;
use InPost\Shipping\Handler\DeliveryOption\CheckDeliveryOptionHandler;
use InPost\Shipping\HookDispatcher;
use InPost\Shipping\Install\Installer;
use InPost\Shipping\Presenter\Store\StorePresenter;
use PrestaShop\ModuleLibServiceContainer\DependencyInjection\ServiceContainer;

if (!defined('_PS_VERSION_')) {
    exit;
}

if (file_exists($autoloadPath = dirname(__FILE__) . '/vendor/autoload.php')) {
    require_once $autoloadPath;
}

class InPostShipping extends CarrierModule
{
    public $confirmUninstall;

    public $id_carrier;

    /** @var HookDispatcher */
    protected $hookDispatcher;

    /** @var Installer */
    protected $installer;

    /** @var AssetsManager */
    protected $assetsManager;

    protected $serviceContainer;

    private $upgradeInProgress = false;
    private $cacheCleared = false;

    public function __construct()
    {
        $this->name = 'inpostshipping';
        $this->tab = 'shipping_logistics';
        $this->version = '2.12.1';
        $this->author = 'InPost S.A.';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = ['min' => '8.0.0', 'max' => '8.2.999'];

        parent::__construct();

        $this->displayName = $this->l('InPost Shipping');
        $this->description = $this->l('Official InPost integration module for PrestaShop');
        $this->confirmUninstall = $this->l('Are you sure you want to uninstall this module?');

        $this->limited_countries = ['pl'];
    }

    public function install(): bool
    {
        if (!parent::install()) {
            return false;
        }

        $this->clearCache();

        return $this->getInstaller()->install();
    }

    public function uninstall(): bool
    {
        $installer = $this->getInstaller();
        if (!$installer->uninstall() || !parent::uninstall()) {
            return false;
        }

        register_shutdown_function(function () {
            $this->clearCache();
        });

        return true;
    }

    protected function getInstaller(): Installer
    {
        if (!isset($this->installer)) {
            $this->installer = $this->getService('inpost.shipping.install.installer');
        }

        return $this->installer;
    }

    public function getContent(): string
    {
        /** @var StorePresenter $storePresenter */
        $storePresenter = $this->getService('inpost.shipping.store.presenter');
        Media::addJsDef([
            'store' => $store = $storePresenter->present(),
        ]);

        $sandbox = $store['config']['api']['sandbox']['enabled'];

        $this
            ->getAssetsManager()
            ->registerGeoWidgetAssets($sandbox)
            ->registerJavaScripts([
                'app.js',
            ]);

        return $this->display(__FILE__, 'views/templates/admin/configuration.tpl');
    }

    /**
     * @template T
     *
     * @param class-string<T>|string $serviceName
     *
     * @return T|object
     */
    public function getService(string $serviceName)
    {
        if ($this->active && !$this->upgradeInProgress) {
            return $this->getContainer()->get($serviceName);
        }

        if (!isset($this->serviceContainer)) {
            $this->serviceContainer = new ServiceContainer(
                $this->name,
                $this->getLocalPath()
            );
        }

        return $this->serviceContainer->getService($serviceName);
    }

    protected function getHookDispatcher(): HookDispatcher
    {
        if (!isset($this->hookDispatcher)) {
            $this->hookDispatcher = $this->getService('inpost.shipping.hook_dispatcher');
        }

        return $this->hookDispatcher;
    }

    public function __call(string $methodName, array $arguments)
    {
        return $this->getHookDispatcher()->dispatch($methodName, $arguments[0] ?? []);
    }

    public function getAssetsManager(): AssetsManager
    {
        if (!isset($this->assetsManager)) {
            $this->assetsManager = $this->getService('inpost.shipping.adapter.assets_manager');
        }

        return $this->assetsManager;
    }

    public function l($string, $specific = false, $locale = null): string
    {
        if ($specific) {
            $specific = Tools::strtolower($specific);
        }

        return parent::l($string, $specific, $locale);
    }

    public function isUsingNewTranslationSystem(): bool
    {
        return false;
    }

    /**
     * @param Cart $params
     * @param float $shipping_cost
     */
    public function getOrderShippingCost($params, $shipping_cost)
    {
        if (!$this->context->controller instanceof AdminController) {
            /** @var CheckDeliveryOptionHandler $checker */
            $checker = $this->getService('inpost.shipping.handler.check_delivery_option');

            if (!$checker->check($params, $this->id_carrier)) {
                return false; // disable delivery option
            }
        }

        return $shipping_cost;
    }

    /** @param Cart $params */
    public function getOrderShippingCostExternal($params): bool
    {
        return false;
    }

    public function runUpgradeModule()
    {
        $this->upgradeInProgress = true;

        try {
            parent::runUpgradeModule();
        } finally {
            $this->upgradeInProgress = false;
        }
    }

    public function clearCache(bool $force = false)
    {
        if ($this->cacheCleared && !$force) {
            return;
        }

        (new \InPost\Shipping\Cache\CacheClearer($this))->clear();
        $this->serviceContainer = null;

        $this->cacheCleared = true;
    }
}
