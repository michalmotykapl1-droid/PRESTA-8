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

namespace InPost\Shipping\Traits;

use InPost\Shipping\Exception\InvalidPhoneFormatException;
use InPost\Shipping\Exception\NotPolishPhonePrefixException;
use InPostCartChoiceModel;

trait PhoneValidatorTrait
{
    protected function validatePhone($phone)
    {
        $phone = InPostCartChoiceModel::formatPhone($phone);

        return !empty($phone) && preg_match('/^[1-9]\d{8}$/', $phone);
    }

    /**
     * @param string $phone
     *
     * @throws NotPolishPhonePrefixException
     * @throws InvalidPhoneFormatException
     */
    protected function validatePhoneFromUserInput($phone)
    {
        $phone = InPostCartChoiceModel::formatPhone($phone);

        if (strpos($phone, '+') === 0) {
            throw new NotPolishPhonePrefixException();
        }

        if (!preg_match('/^[1-9]\d{8}$/', $phone)) {
            throw new InvalidPhoneFormatException();
        }
    }
}
