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

namespace InPost\Shipping\Helper;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ServerException;
use Module;

class CoordinatesExtractor
{
    protected $module;

    public function __construct(Module $module)
    {
        $this->module = $module;
    }

    public function getCoordinates(string $address, int $idCountry, string $googleApiKey): ?array
    {
        if (!$googleApiKey || !$address || !$idCountry) {
            return null;
        }

        $client = new Client();

        try {
            $response = $client->request(
                'GET',
                'https://maps.google.com/maps/api/geocode/json',
                [
                    'query' => [
                        'address' => $address,
                        'region' => strtolower((new \Country($idCountry))->iso_code),
                        'key' => $googleApiKey,
                    ],
                ]
            );

            if ($response->getStatusCode() == 200) {
                $data = json_decode($response->getBody());
                if ($data->status == 'OK') {
                    $results = $data->results;

                    return ['lat' => $results[0]->geometry->location->lat, 'lng' => $results[0]->geometry->location->lng];
                } elseif ($data->status != 'ZERO_RESULTS') {
                    $errorMessage = $this->module->l('Google Maps API returned ' . $data->status . ' for address ' . $address);
                } else {
                    return null;
                }
            } else {
                $errorMessage = $this->module->l('Google Maps API returned status code ' . $response->getStatusCode() . ' for address ' . $address);
            }
        } catch (ClientException $e) {
            $errorMessage = $this->module->l('Google Maps API Client error: ' . $e->getMessage());
        } catch (ServerException $e) {
            $errorMessage = $this->module->l('Google Maps API Server error: ' . $e->getMessage());
        } catch (RequestException $e) {
            $errorMessage = $this->module->l('Google Maps API Request error: ' . $e->getMessage());
        } catch (ConnectException $e) {
            $errorMessage = $this->module->l('Google Maps API Connection error: ' . $e->getMessage());
        } catch (\Exception $e) {
            $errorMessage = $this->module->l('Google Maps API error: ' . $e->getMessage());
        }

        return ['error' => $errorMessage];
    }
}
