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

use Psr\Http\Message\ResponseInterface;
use RuntimeException;
use stdClass;

/** @mixin ResponseInterface */
class Response
{
    protected $response;

    public function __construct(ResponseInterface $response)
    {
        $this->response = $response;
    }

    /**
     * Get response contents as string.
     */
    public function getContents(): string
    {
        $body = $this->response->getBody();
        if ($body->isSeekable()) {
            $body->rewind();
        }

        return $body->getContents();
    }

    /**
     * Decode response JSON.
     *
     * @param bool $associative
     *
     * @return array|stdClass|null
     */
    public function json(bool $associative = true)
    {
        return json_decode($this->getContents(), $associative);
    }

    public function __call(string $methodName, array $arguments)
    {
        if (!method_exists(ResponseInterface::class, $methodName)) {
            throw new RuntimeException(sprintf('Method "%s" does not exist in %s', $methodName, ResponseInterface::class));
        }

        return $this->response->{$methodName}(...$arguments);
    }
}
