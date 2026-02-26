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

use Context;
use InPost\Shipping\Presenter\ShipmentPresenter;
use InPostShipmentModel;
use InPostShipping;

class ShipmentDetailsModal extends AbstractModal
{
    const TRANSLATION_SOURCE = 'ShipmentDetailsModal';

    const MODAL_ID = 'inpost-shipment-details-modal';

    protected $shipmentPresenter;

    protected $shipment;

    public function __construct(
        InPostShipping $module,
        Context $context,
        ShipmentPresenter $shipmentPresenter
    ) {
        parent::__construct($module, $context);

        $this->shipmentPresenter = $shipmentPresenter;
    }

    public function setShipment(InPostShipmentModel $shipmentModel): self
    {
        $this->shipment = $shipmentModel;

        return $this;
    }

    public function renderContent(): string
    {
        return isset($this->shipment)
            ? parent::renderContent()
            : '<div id="inpost-shipment-details-content-wrapper"></div>';
    }

    protected function getTitle(): string
    {
        return $this->module->l('Shipment details', self::TRANSLATION_SOURCE);
    }

    protected function assignContentTemplateVariables()
    {
        if (isset($this->shipment)) {
            $this->context->smarty->assign(
                'shipment',
                $this->shipmentPresenter->present($this->shipment)
            );
        }
    }

    protected function getActions(): array
    {
        return [];
    }
}
