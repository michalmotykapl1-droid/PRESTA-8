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

namespace InPost\Shipping\Api;

use InPost\Shipping\Api\Query\QueryBuilder;
use Psr\Http\Message\StreamInterface;

class Request
{
    /**
     * @var ClientInterface
     */
    protected $client;

    /**
     * HTTP method
     *
     * @var string
     */
    protected $method;

    /**
     * @var string
     */
    protected $baseUrl;

    /**
     * URL path.
     *
     * @var string
     */
    protected $path;

    /**
     * URL path parameters.
     *
     * @var array
     */
    protected $pathParams = [];

    /**
     * Request options.
     *
     * @var array
     */
    protected $options = [];

    /**
     * Query builder.
     *
     * @var QueryBuilder
     */
    protected $queryBuilder;

    public function __construct(ClientInterface $client)
    {
        $this->client = $client;
    }

    /**
     * Set the HTTP method.
     *
     * @param string $method
     *
     * @return $this
     */
    public function setMethod(string $method): self
    {
        $this->method = $method;

        return $this;
    }

    public function setBaseUrl(string $url): self
    {
        $this->baseUrl = $url;

        return $this;
    }

    /**
     * Set the URL path.
     *
     * @param string $path
     *
     * @return $this
     */
    public function setPath(string $path): self
    {
        $this->path = $path;

        return $this;
    }

    /**
     * Add Path parameter values.
     * Path parameters encoded in the route URL as '{key}' will be replaced
     * with the appropriate value using the given key/value pairs.
     *
     * @param array $pathParams
     *
     * @return $this
     */
    public function setPathParams(array $pathParams): self
    {
        $this->pathParams = array_merge($this->pathParams, $pathParams);

        return $this;
    }

    public function getHeaders(): array
    {
        return $this->options['headers'] ?? [];
    }

    /**
     * Add request headers.
     *
     * @param array $headers
     *
     * @return $this
     */
    public function setHeaders(array $headers): self
    {
        $this->options['headers'] = isset($this->options['headers'])
            ? array_merge($this->options['headers'], $headers)
            : $headers;

        return $this;
    }

    /**
     * Add URL-encoded Query parameter values.
     *
     * @param array $queryParams
     *
     * @return $this
     */
    public function setQueryParams(array $queryParams): self
    {
        $this->options['query'] = isset($this->options['query'])
            ? array_merge($this->options['query'], $queryParams)
            : $queryParams;

        return $this;
    }

    public function getBody()
    {
        if (isset($this->options['json'])) {
            return json_encode($this->options['json']);
        }

        return $this->options['body'] ?? null;
    }

    /**
     * Set the body value.
     *
     * @param string|resource|StreamInterface $body
     *
     * @return $this
     */
    public function setBody($body): self
    {
        $this->options['body'] = $body;

        return $this;
    }

    /**
     * Add request JSON data.
     *
     * @param array $data
     *
     * @return $this
     */
    public function setJson(array $data): self
    {
        $this->options['json'] = isset($this->options['json'])
            ? array_merge($this->options['json'], $data)
            : $data;

        return $this;
    }

    /**
     * Set additional Request options.
     *
     * @param array $options
     *
     * @return $this
     */
    public function setOptions(array $options): self
    {
        $this->options = array_merge($this->options, $options);

        return $this;
    }

    public function setQueryBuilder(QueryBuilder $queryBuilder): self
    {
        $this->queryBuilder = $queryBuilder;

        return $this;
    }

    /**
     * Get the Request HTTP method.
     *
     * @return string
     */
    public function getMethod(): ?string
    {
        return $this->method;
    }

    /**
     * Get the Request URL.
     *
     * @return string
     */
    public function getUrl(): string
    {
        $url = $this->baseUrl . $this->path;

        foreach ($this->pathParams as $key => $value) {
            $url = str_replace("{{$key}}", $value, $url);
        }

        if ($query = $this->buildQuery()) {
            $url .= '?' . $query;
        }

        return $url;
    }

    /**
     * Get Request options.
     *
     * @return array
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    public function getQueryBuilder(): QueryBuilder
    {
        if (!isset($this->queryBuilder)) {
            $this->queryBuilder = $this->getDefaultQueryBuilder();
        }

        return $this->queryBuilder;
    }

    protected function getDefaultQueryBuilder(): QueryBuilder
    {
        static $builder;

        if (!isset($builder)) {
            $builder = new QueryBuilder();
        }

        return $builder;
    }

    /**
     * Send the API Request.
     *
     * @return Response
     */
    public function send(): Response
    {
        return $this->client->send($this);
    }

    protected function buildQuery(): string
    {
        if (empty($this->options['query'])) {
            return '';
        }

        return $this->getQueryBuilder()->build($this->options['query']);
    }
}
