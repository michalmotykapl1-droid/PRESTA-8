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

namespace InPost\Shipping\ShipX\RequestFactory;

use Context;
use InPost\Shipping\Api\Request;
use InPost\Shipping\Configuration\ShipXConfiguration;
use InPost\Shipping\ShipX\ShipXClient;
use Tools;

class PointsApiRequestFactory extends AuthorizedRequestFactory
{
    // TODO this URL has not been tested...
    const LIVE_URL = 'https://api.inpost.pl';
    // TODO returns 500 for specific point endpoint, works fine for points list...
    const SANDBOX_URL = 'https://sandbox-api-gateway-pl.easypack24.net';

    protected $shopDomain;

    public function __construct(ShipXConfiguration $configuration, ShipXClient $client, Context $context)
    {
        parent::__construct($configuration, $client, $context);

        $this->shopDomain = Tools::usingSecureMode()
            ? $context->shop->domain_ssl
            : $context->shop->domain;
    }

    public function createRequest(string $method, string $path, array $options = []): Request
    {
        return parent::createRequest($method, $path, $options)
            ->setHeaders([
                'app-referrer' => $this->shopDomain,
            ]);
    }
}
