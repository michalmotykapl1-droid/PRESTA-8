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

use InPost\Shipping\Api\Response;
use InPost\Shipping\PrestaShopContext;
use InPost\Shipping\ShipX\Exception\ShipXException;

abstract class InPostShippingAdminController extends ModuleAdminController
{
    const TRANSLATION_SOURCE = 'InPostShippingAdminController';

    /** @var InPostShipping */
    public $module;

    public $override_folder;

    /** @var Link */
    protected $link;

    /** @var PrestaShopContext */
    protected $shopContext;

    protected $response = [
        'status' => true,
    ];

    public function __construct()
    {
        parent::__construct();

        $this->override_folder = '';

        $this->link = $this->context->link;
    }

    public function initPageHeaderToolbar()
    {
        $this->page_header_toolbar_btn['configuration'] = [
            'href' => $this->link->getAdminLink('AdminModules', true, [], [
                'configure' => $this->module->name,
            ]),
            'desc' => $this->module->l('Module configuration', self::TRANSLATION_SOURCE),
            'icon' => 'process-icon-cogs',
        ];

        parent::initPageHeaderToolbar();
    }

    public function postProcess()
    {
        try {
            $result = parent::postProcess();
        } catch (Exception $exception) {
            $result = false;
            $this->handleException($exception);
        }

        if ($this->ajax) {
            $this->ajaxResponse();
        }

        return $result;
    }

    protected function handleException(Exception $exception)
    {
        $message = $exception->getMessage();

        if ($exception instanceof ShipXException &&
            ($details = $exception->getDetails()) &&
            is_string($details)
        ) {
            $message .= " $details";
        }

        $this->errors[] = $message;
    }

    protected function ajaxResponse()
    {
        if (!empty($this->errors)) {
            $this->response['status'] = false;
            $this->response['errors'] = $this->errors;
        }

        header('Content-type: application/json');
        $this->ajaxDie(json_encode($this->response));
    }

    protected function offerDownload(Response $response, $filename = null)
    {
        $contentDisposition = $response->getHeaderLine('Content-Disposition');

        if ($filename) {
            $contentDisposition = sprintf(
                'attachment; filename="%s_%s%s"',
                $filename,
                date('Y-m-d_H-i-s'),
                $this->getExtensionFromHeader($contentDisposition)
            );
        }

        header('Content-type: ' . $response->getHeaderLine('Content-Type'));
        header('Content-Disposition: ' . $contentDisposition);
        echo $response->getContents();

        exit;
    }

    protected function getExtensionFromHeader(string $contentDisposition): string
    {
        if (preg_match('/filename[^;=\n]*=(([\'"]).*?\2|[^;\n]*)/', $contentDisposition, $matches)) {
            $filename = str_replace($matches[2], '', $matches[1]);

            return '.' . pathinfo($filename, PATHINFO_EXTENSION);
        }

        return '';
    }

    protected function displayLink($token, $id, $action, $icon = 'print', $href = null)
    {
        $tpl = $this->createTemplate('list-action.tpl');

        $tpl->assign([
            'id' => $id,
            'href' => $href ?: $this->link->getAdminLink($this->controller_name, false, [], [
                'action' => $action,
                $this->identifier => $id,
                'ajax' => true,
                'token' => $token,
            ]),
            'class' => !$href ? 'js-' . $action : '',
            'action' => static::$cache_lang[$action],
            'icon' => $icon,
        ]);

        return $tpl->fetch();
    }

    protected function smartyOutputContent($templates)
    {
        if ($this->shouldRenderNavTabs($templates) &&
            $navTabs = $this->renderNavTabs()
        ) {
            $this->context->smarty->assign('navTabs', $navTabs);
            $this->context->smarty->assign(
                'page_header_toolbar',
                $this->createTemplate('page_header_toolbar.tpl')->fetch()
            );
            $this->context->smarty->assign(
                'header',
                $this->createTemplate('header.tpl')->fetch()
            );
        }

        parent::smartyOutputContent($templates);
    }

    protected function shouldRenderNavTabs($templates): bool
    {
        return false;
    }

    protected function renderNavTabs(): string
    {
        return '';
    }

    protected function trans($id, array $parameters = [], $domain = null, $locale = null): string
    {
        if (is_callable(['parent', 'trans'])) {
            return parent::trans($id, $parameters, $domain, $locale);
        }

        return $this->l($id);
    }
}
