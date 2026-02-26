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

namespace InPost\Shipping\ShipX;

use InPost\Shipping\Api\Client;
use InPost\Shipping\Api\Request;
use InPost\Shipping\Api\Response;
use InPost\Shipping\ShipX\Exception\AccessForbiddenException;
use InPost\Shipping\ShipX\Exception\InternalServerErrorException;
use InPost\Shipping\ShipX\Exception\LabelNotFoundException;
use InPost\Shipping\ShipX\Exception\ResourceNotFoundException;
use InPost\Shipping\ShipX\Exception\ShipXException;
use InPost\Shipping\ShipX\Exception\TokenInvalidException;
use InPost\Shipping\ShipX\Exception\ValidationFailedException;

class ShipXClient extends Client
{
    public function send(Request $request): Response
    {
        $response = parent::send($request);
        $statusCode = $response->getStatusCode();

        if (200 > $statusCode || 300 <= $statusCode) {
            throw $this->getExceptionByResponse($response);
        }

        return $response;
    }

    private function getExceptionByResponse(Response $response): ShipXException
    {
        $contents = $response->json();

        if (isset($contents['error'])) {
            return $this->getExceptionByErrorCode($contents);
        }

        return new ShipXException($contents, new \Exception(sprintf('Response code: %d, response body: "%s"', $response->getStatusCode(), $response->getContents())));
    }

    protected function getExceptionByErrorCode(array $response)
    {
        switch ($response['error']) {
            case 'access_forbidden':
            case 'Forbidden':
                return new AccessForbiddenException($response);
            case 'resource_not_found':
                return new ResourceNotFoundException($response);
            case 'token_invalid':
                return new TokenInvalidException($response);
            case 'validation_failed':
                return new ValidationFailedException($response);
            case 'label_not_found':
                return new LabelNotFoundException($response);
            case 'Internal Server Error':
                return new InternalServerErrorException($response);
            default:
                return new ShipXException($response);
        }
    }
}
