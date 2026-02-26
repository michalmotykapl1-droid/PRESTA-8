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
use InPost\Shipping\ChoiceProvider\ShipmentLabelFormatChoiceProvider;
use InPost\Shipping\ChoiceProvider\ShipmentLabelTypeChoiceProvider;
use InPost\Shipping\Configuration\SendingConfiguration;
use InPostShipping;

class PrintShipmentLabelModal extends AbstractModal
{
    const TRANSLATION_SOURCE = 'PrintShipmentLabelModal';

    const MODAL_ID = 'inpost-print-shipment-label-modal';

    protected $labelFormatChoiceProvider;
    protected $labelTypeChoiceProvider;
    private $sendingConfiguration;

    public function __construct(
        InPostShipping $module,
        Context $context,
        ShipmentLabelFormatChoiceProvider $labelFormatChoiceProvider,
        ShipmentLabelTypeChoiceProvider $labelTypeChoiceProvider,
        SendingConfiguration $sendingConfiguration
    ) {
        parent::__construct($module, $context);

        $this->labelFormatChoiceProvider = $labelFormatChoiceProvider;
        $this->labelTypeChoiceProvider = $labelTypeChoiceProvider;
        $this->sendingConfiguration = $sendingConfiguration;

        $this->setTemplate('views/templates/hook/modal/print-shipment-label.tpl');
    }

    protected function getTitle(): string
    {
        return $this->module->l('Print shipment labels', self::TRANSLATION_SOURCE);
    }

    protected function assignContentTemplateVariables()
    {
        $this->context->smarty->assign([
            'labelFormatChoices' => $this->labelFormatChoiceProvider->getChoices(),
            'defaultLabelFormat' => $this->sendingConfiguration->getDefaultLabelFormat(),
            'labelTypeChoices' => $this->labelTypeChoiceProvider->getChoices(),
            'defaultLabelType' => $this->sendingConfiguration->getDefaultLabelType(),
        ]);
    }

    protected function getActions(): array
    {
        return [
            [
                'type' => 'button',
                'value' => 'submitPrintLabel',
                'class' => 'js-submit-print-label-form btn-primary',
                'label' => $this->module->l('Print', self::TRANSLATION_SOURCE),
            ],
        ];
    }
}
