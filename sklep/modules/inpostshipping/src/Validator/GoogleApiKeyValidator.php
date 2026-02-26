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

namespace InPost\Shipping\Validator;

use Configuration;
use InPost\Shipping\Helper\CoordinatesExtractor;
use InPost\Shipping\Translations\ValidationErrorTranslator;
use InPostShipping;

class GoogleApiKeyValidator extends AbstractValidator
{
    const TRANSLATION_SOURCE = 'GoogleApiKeyValidator';

    protected $errorTranslator;
    protected $extractor;

    public function __construct(
        InPostShipping $module,
        ValidationErrorTranslator $errorTranslator,
        CoordinatesExtractor $extractor
    ) {
        parent::__construct($module);

        $this->errorTranslator = $errorTranslator;
        $this->extractor = $extractor;
    }

    public function validate(array $data)
    {
        $this->resetErrors();

        if (!empty($data['google_api_key'])) {
            $result = $this->validateGoogleApiKey($data['google_api_key']);
            if (isset($result['error'])) {
                $this->errors['google_api_key'] = $this->errorTranslator->translate($result['error']);
            }
        }

        return !$this->hasErrors();
    }

    protected function validateGoogleApiKey(string $googleApiKey)
    {
        $result = $this->extractor->getCoordinates('test', Configuration::get('PS_COUNTRY_DEFAULT'), $googleApiKey);

        if (isset($result['error'])) {
            return $result;
        }
    }
}
