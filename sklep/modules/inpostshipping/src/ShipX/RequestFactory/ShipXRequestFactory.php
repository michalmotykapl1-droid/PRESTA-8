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
use InPost\Shipping\Api\Query\PhpAggregator;
use InPost\Shipping\Api\Query\QueryBuilder;
use InPost\Shipping\Api\Request;
use InPost\Shipping\Api\RequestFactoryInterface;
use InPost\Shipping\Configuration\ShipXConfiguration;
use InPost\Shipping\ShipX\ShipXClient;
use Tools;

class ShipXRequestFactory implements RequestFactoryInterface
{
    const LIVE_URL = 'https://api-shipx-pl.easypack24.net';
    const SANDBOX_URL = 'https://sandbox-api-shipx-pl.easypack24.net';

    const ALLOWED_LANGUAGES = [
        'pl_PL',
        'en_GB',
        'keys',
    ];

    protected $configuration;
    protected $client;
    protected $queryBuilder;

    protected $language;

    public function __construct(ShipXConfiguration $configuration, ShipXClient $client, Context $context)
    {
        $this->configuration = $configuration;
        $this->client = $client;
        $this->queryBuilder = new QueryBuilder(new PhpAggregator(false));

        $language = Tools::strtolower($context->language->iso_code) === 'pl'
            ? 'pl_PL'
            : 'en_GB';

        $this->setLanguage($language);
    }

    protected function getBaseUrl()
    {
        return $this->configuration->useSandboxMode()
            ? static::SANDBOX_URL
            : static::LIVE_URL;
    }

    public function setLanguage($language)
    {
        if (in_array($language, self::ALLOWED_LANGUAGES)) {
            $this->language = $language;
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function createRequest(string $method, string $path, array $options = []): Request
    {
        return (new Request($this->client))
            ->setOptions($options)
            ->setMethod($method)
            ->setBaseUrl($this->getBaseUrl())
            ->setPath($path)
            ->setQueryBuilder($this->queryBuilder)
            ->setHeaders([
                'Content-Type' => 'application/json',
                'Accept-Language' => $this->language,
            ]);
    }
}
