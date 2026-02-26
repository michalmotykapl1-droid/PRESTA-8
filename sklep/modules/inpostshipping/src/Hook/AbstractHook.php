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

namespace InPost\Shipping\Hook;

use Context;
use InPost\Shipping\PrestaShopContext;
use InPostShipping;

abstract class AbstractHook
{
    protected const HOOK_LIST = [];
    protected const HOOK_LIST_16 = [];
    protected const HOOK_LIST_17 = [];

    protected $module;
    protected $context;
    protected $shopContext;

    public function __construct(
        InPostShipping $module,
        Context $context,
        PrestaShopContext $shopContext
    ) {
        $this->module = $module;
        $this->context = $context;
        $this->shopContext = $shopContext;
    }

    /**
     * @return array
     */
    public function getAvailableHooks(): array
    {
        return array_merge(
            static::HOOK_LIST,
            $this->shopContext->is17()
                ? static::HOOK_LIST_17
                : static::HOOK_LIST_16
        );
    }

    public function getOutdatedHooks(): array
    {
        return $this->shopContext->is17()
            ? static::HOOK_LIST_16
            : [];
    }
}
