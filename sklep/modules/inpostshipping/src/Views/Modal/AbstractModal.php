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

use InPost\Shipping\Views\AbstractRenderable;

abstract class AbstractModal extends AbstractRenderable
{
    const MODAL_ID = '';

    public function getModalData(): array
    {
        return [
            'modal_id' => static::MODAL_ID,
            'modal_class' => $this->getClasses(),
            'modal_title' => $this->getTitle(),
            'modal_content' => $this->renderContent(),
            'modal_actions' => $this->getActions(),
        ];
    }

    protected function getClasses(): string
    {
        return 'modal-md';
    }

    abstract protected function getTitle(): string;

    public function renderContent(): string
    {
        $this->assignContentTemplateVariables();

        return $this->module->display($this->module->name, $this->template);
    }

    abstract protected function assignContentTemplateVariables();

    abstract protected function getActions(): array;

    public function assignTemplateVariables()
    {
        $this->context->smarty->assign($this->getModalData());
    }

    public function render(): string
    {
        $this->assignTemplateVariables();

        return $this->module->display($this->module->name, 'views/templates/hook/modal/modal.tpl');
    }
}
