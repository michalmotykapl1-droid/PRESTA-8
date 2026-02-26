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

namespace InPost\Shipping\Adapter;

use AdminController;
use Context;
use InPostShipping;
use Validate;

class AssetsManager
{
    private const GEO_WIDGET_V5_URL = 'https://geowidget.inpost.pl';
    private const SANDBOX_GEO_WIDGET_V5_URL = 'https://sandbox-easy-geowidget-sdk.easypack24.net';

    protected $module;
    protected $context;

    public function __construct(
        InPostShipping $module,
        Context $context
    ) {
        $this->module = $module;
        $this->context = $context;
    }

    public function registerGeoWidgetAssets(bool $sandbox = false): self
    {
        return $this
            ->registerJavaScripts([$this->getGeoWidgetJsUrl($sandbox)], [
                'position' => 'head',
                'attributes' => 'defer',
            ])->registerStyleSheets([
                $this->getGeoWidgetCssUrl($sandbox),
            ]);
    }

    public function getGeoWidgetJsUrl(bool $sandbox = false): string
    {
        return sprintf('%s/%s', $this->getGeoWidgetUrl($sandbox), 'inpost-geowidget.js');
    }

    public function getGeoWidgetCssUrl(bool $sandbox = false): string
    {
        return sprintf('%s/%s', $this->getGeoWidgetUrl($sandbox), 'inpost-geowidget.css');
    }

    protected function getGeoWidgetUrl(bool $sandbox): string
    {
        return $sandbox
            ? self::SANDBOX_GEO_WIDGET_V5_URL
            : self::GEO_WIDGET_V5_URL;
    }

    public function registerJavaScripts(array $javaScripts, array $params = []): self
    {
        $uris = array_map([$this, 'getJavaScriptUri'], $javaScripts);

        if ($this->context->controller instanceof AdminController) {
            $this->context->controller->addJS($uris, false);
        } else {
            $params['server'] = 'remote';

            foreach ($uris as $uri) {
                $this->context->controller->registerJavascript(
                    $this->getMediaId($uri),
                    $uri,
                    $params
                );
            }
        }

        return $this;
    }

    public function registerStyleSheets(array $styleSheets, array $params = []): self
    {
        $uris = array_map([$this, 'getStyleSheetUri'], $styleSheets);

        if ($this->context->controller instanceof AdminController) {
            $this->context->controller->addCSS(
                $uris,
                'all',
                null,
                false
            );
        } else {
            $params['server'] = 'remote';

            foreach ($uris as $uri) {
                $this->context->controller->registerStylesheet(
                    $this->getMediaId($uri),
                    $uri,
                    $params
                );
            }
        }

        return $this;
    }

    protected function getStyleSheetUri(string $path): string
    {
        return $this->isModuleMedia($path)
            ? $this->getModuleMediaUri('views/css/' . $path)
            : $path;
    }

    protected function getJavaScriptUri(string $path): string
    {
        return $this->isModuleMedia($path)
            ? $this->getModuleMediaUri('views/js/' . $path)
            : $path;
    }

    protected function getModuleMediaUri(string $path): string
    {
        return $this->module->getPathUri() . $path . '?version=' . $this->module->version;
    }

    protected function getMediaId(string $uri): string
    {
        return 'inpost-' . sha1($uri);
    }

    protected function isModuleMedia(string $path): bool
    {
        return !Validate::isAbsoluteUrl($path)
            && strpos($path, _THEME_DIR_) === false;
    }
}
