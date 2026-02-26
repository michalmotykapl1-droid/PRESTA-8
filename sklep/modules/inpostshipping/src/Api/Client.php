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

use Psr\Http\Client\ClientInterface as PsrClient;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;

class Client implements ClientInterface
{
    private $httpClient;
    private $streamFactory;
    private $requestFactory;

    public function __construct(
        PsrClient $httpClient,
        StreamFactoryInterface $streamFactory,
        RequestFactoryInterface $requestFactory
    ) {
        $this->httpClient = $httpClient;
        $this->streamFactory = $streamFactory;
        $this->requestFactory = $requestFactory;
    }

    public function send(Request $request): Response
    {
        $psrRequest = $this->requestFactory->createRequest(
            $request->getMethod(),
            $request->getUrl()
        );

        if ($headers = $request->getHeaders()) {
            foreach ($headers as $name => $value) {
                $psrRequest = $psrRequest->withAddedHeader($name, $value);
            }
        }

        if ($body = $request->getBody()) {
            if (is_string($body)) {
                $body = $this->streamFactory->createStream($body);
            } elseif (is_resource($body)) {
                $body = $this->streamFactory->createStreamFromResource($body);
            }

            $psrRequest = $psrRequest->withBody($body);
        }

        return new Response($this->httpClient->sendRequest($psrRequest));
    }
}
