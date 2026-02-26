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

namespace InPost\Shipping\Api;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\HttpFactory;
use Psr\Container\ContainerInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use RuntimeException;
use Symfony\Component\HttpClient\Psr18Client;
use Symfony\Contracts\Service\ServiceSubscriberInterface;

class PsrHttpImplementationFinder implements ServiceSubscriberInterface
{
    private $locator;

    private $client;
    private $requestFactory;
    private $streamFactory;

    public function __construct(ContainerInterface $locator)
    {
        $this->locator = $locator;
    }

    public static function getSubscribedServices(): array
    {
        return [
            '?' . ClientInterface::class,
            '?' . RequestFactoryInterface::class,
            '?' . StreamFactoryInterface::class,
        ];
    }

    public function getClient(): ClientInterface
    {
        if (!isset($this->client)) {
            $this->client = $this->findClient();
        }

        return $this->client;
    }

    public function getRequestFactory(): RequestFactoryInterface
    {
        if (!isset($this->requestFactory)) {
            $this->requestFactory = $this->findRequestFactory();
        }

        return $this->requestFactory;
    }

    public function getStreamFactory(): StreamFactoryInterface
    {
        if (!isset($this->streamFactory)) {
            $this->streamFactory = $this->findStreamFactory();
        }

        return $this->streamFactory;
    }

    private function findClient(): ClientInterface
    {
        $client = $this->getService(ClientInterface::class);

        if ($client instanceof ClientInterface) {
            return $client;
        }

        if (class_exists(Client::class) && is_subclass_of(Client::class, ClientInterface::class)) {
            return new Client();
        }

        throw new RuntimeException(sprintf('No %s implementation found', ClientInterface::class));
    }

    private function findRequestFactory(): RequestFactoryInterface
    {
        $factory = $this->getService(RequestFactoryInterface::class);

        if ($factory instanceof RequestFactoryInterface) {
            return $factory;
        }

        if ($factory = $this->getGuzzleFactory()) {
            return $factory;
        }

        throw new RuntimeException(sprintf('No %s implementation found', RequestFactoryInterface::class));
    }

    private function findStreamFactory(): StreamFactoryInterface
    {
        $factory = $this->getService(StreamFactoryInterface::class);

        if ($factory instanceof StreamFactoryInterface) {
            return $factory;
        }

        if ($factory = $this->getGuzzleFactory()) {
            return $factory;
        }

        throw new RuntimeException(sprintf('No %s implementation found', StreamFactoryInterface::class));
    }

    private function getGuzzleFactory(): ?HttpFactory
    {
        static $factory;

        if (!isset($factory)) {
            $factory = class_exists(HttpFactory::class) ? new HttpFactory() : false;
        }

        return $factory ?: null;
    }

    /**
     * @template T
     *
     * @param class-string<T> $name
     *
     * @return T|null
     */
    private function getService(string $name)
    {
        if (!$this->locator->has($name)) {
            return null;
        }

        try {
            return $this->locator->get($name);
        } catch (Exception $e) {
            /* @see Psr18Client::__construct */

            return null;
        }
    }
}
