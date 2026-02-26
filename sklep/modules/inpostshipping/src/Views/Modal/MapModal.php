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

namespace InPost\Shipping\Views\Modal;

class MapModal extends AbstractModal
{
    const MODAL_ID = 'inpost-shipping-map-modal';

    public function renderContent(): string
    {
        return '<div class="inpost-shipping-map-modal-content js-inpost-shipping-map-modal-content"></div>';
    }

    protected function getTitle(): string
    {
        return '';
    }

    protected function getClasses(): string
    {
        return static::MODAL_ID;
    }

    protected function assignContentTemplateVariables()
    {
    }

    protected function getActions(): array
    {
        return [];
    }
}
