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

namespace InPost\Shipping\DataProvider;

use Context;
use Exception;
use FrontController;
use InPost\Shipping\Configuration\ShipXConfiguration;
use InPost\Shipping\GeoWidget\GeoWidgetTokenProvider;
use InPost\Shipping\ShipX\Exception\AccessForbiddenException;
use InPost\Shipping\ShipX\Exception\InternalServerErrorException;
use InPost\Shipping\ShipX\Exception\TokenInvalidException;
use InPost\Shipping\ShipX\Resource\NewApiPoint;
use InPost\Shipping\ShipX\Resource\Point;
use InPost\Shipping\Traits\ErrorsTrait;
use Psr\Http\Client\ClientExceptionInterface;

class ClosestPointDataProvider
{
    use ErrorsTrait;

    protected $shipXConfiguration;
    protected $tokenProvider;
    protected $context;

    protected $useNewApi = true;

    public function __construct(
        ShipXConfiguration $shipXConfiguration,
        GeoWidgetTokenProvider $tokenProvider,
        Context $context
    ) {
        $this->shipXConfiguration = $shipXConfiguration;
        $this->tokenProvider = $tokenProvider;
        $this->context = $context;
    }

    public function getClosestPointByPostCode(string $postCode, array $carrierData): ?Point
    {
        $searchParams = [
            'relative_post_code' => $postCode,
            'limit' => 1,
            'sort_order' => 'asc',
            'sort_by' => 'distance_to_relative_point',
        ];

        if ($carrierData['cashOnDelivery']) {
            $searchParams['payment_available'] = true;
        }
        if ($carrierData['weekendDelivery']) {
            $searchParams['location_247'] = true;
        }

        return $this->initPointData($searchParams);
    }

    public function getClosestPointByCoordinates(float $latitude, float $longitude, array $carrierData): ?Point
    {
        $searchParams = [
            'relative_point' => $latitude . ',' . $longitude,
            'limit' => 1,
            'sort_order' => 'asc',
            'sort_by' => 'distance_to_relative_point',
        ];

        if ($carrierData['cashOnDelivery']) {
            $searchParams['payment_available'] = true;
        }
        if ($carrierData['weekendDelivery']) {
            $searchParams['location_247'] = true;
        }

        return $this->initPointData($searchParams);
    }

    protected function initPointData(array $searchParams)
    {
        $useSandbox = $this->shipXConfiguration->useSandboxMode();

        if (
            $this->context->controller instanceof FrontController &&
            $token = $this->tokenProvider->getToken()
        ) {
            $this->shipXConfiguration->setSandboxMode($token->isSandbox());
        }

        try {
            return $this->searchPoint($searchParams);
        } catch (Exception $exception) {
            $this->addError($exception->getMessage());

            return null;
        } finally {
            $this->shipXConfiguration->setSandboxMode($useSandbox);
        }
    }

    protected function searchPoint($searchParams)
    {
        if (!$this->useNewApi) {
            return Point::getCollection($searchParams, 'distance_to_relative_point', 'asc')->current();
        }

        try {
            return NewApiPoint::getCollection($searchParams, 'distance_to_relative_point', 'asc')->current();
        } catch (Exception $exception) {
            if (
                $exception instanceof AccessForbiddenException ||
                $exception instanceof TokenInvalidException ||
                $exception instanceof InternalServerErrorException ||
                $exception instanceof ClientExceptionInterface
            ) {
                $this->useNewApi = false;

                return $this->searchPoint($searchParams);
            }

            throw $exception;
        }
    }
}
