<?php

declare(strict_types=1);

namespace CarmeloSantana\CoquiAwesomeWp\Client;

use Symfony\Component\HttpClient\CurlHttpClient;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class WpRestClient
{
    private const int DEFAULT_TIMEOUT = 30;
    private const int MAX_PAGES = 50;
    private const int DEFAULT_PER_PAGE = 10;

    public function __construct(
        private readonly SiteConnection $connection,
        private readonly HttpClientInterface $httpClient = new CurlHttpClient(),
    ) {}

    public function get(string $endpoint, array $params = [], int $timeout = self::DEFAULT_TIMEOUT): WpRestResult
    {
        return $this->request('GET', $endpoint, params: $params, timeout: $timeout);
    }

    public function post(string $endpoint, array $body = [], int $timeout = self::DEFAULT_TIMEOUT): WpRestResult
    {
        return $this->request('POST', $endpoint, body: $body, timeout: $timeout);
    }

    public function put(string $endpoint, array $body = [], int $timeout = self::DEFAULT_TIMEOUT): WpRestResult
    {
        return $this->request('PUT', $endpoint, body: $body, timeout: $timeout);
    }

    public function patch(string $endpoint, array $body = [], int $timeout = self::DEFAULT_TIMEOUT): WpRestResult
    {
        return $this->request('PATCH', $endpoint, body: $body, timeout: $timeout);
    }

    public function delete(string $endpoint, array $params = [], int $timeout = self::DEFAULT_TIMEOUT): WpRestResult
    {
        return $this->request('DELETE', $endpoint, params: $params, timeout: $timeout);
    }

    public function upload(string $endpoint, string $filePath, array $params = [], int $timeout = 60): WpRestResult
    {
        if (!file_exists($filePath) || !is_readable($filePath)) {
            return new WpRestResult(
                statusCode: 0,
                body: null,
                rawBody: sprintf('File not found or not readable: %s', $filePath),
            );
        }

        $filename = basename($filePath);
        $mimeType = mime_content_type($filePath) ?: 'application/octet-stream';
        $fileContents = file_get_contents($filePath);

        if ($fileContents === false) {
            return new WpRestResult(
                statusCode: 0,
                body: null,
                rawBody: sprintf('Failed to read file: %s', $filePath),
            );
        }

        $url = $this->buildUrl($endpoint, $params);

        try {
            $response = $this->httpClient->request('POST', $url, [
                'headers' => [
                    'Authorization' => $this->authHeader(),
                    'Content-Disposition' => sprintf('attachment; filename="%s"', $filename),
                    'Content-Type' => $mimeType,
                ],
                'body' => $fileContents,
                'timeout' => $timeout,
            ]);

            return $this->buildResult($response);
        } catch (TransportExceptionInterface $e) {
            return new WpRestResult(
                statusCode: 0,
                body: null,
                rawBody: sprintf('Transport error: %s', $e->getMessage()),
            );
        }
    }

    public function paginate(string $endpoint, array $params = [], int $maxPages = self::MAX_PAGES): WpRestResult
    {
        $params['per_page'] ??= self::DEFAULT_PER_PAGE;
        $params['page'] = 1;

        $allItems = [];
        $totalItems = 0;
        $lastHeaders = [];

        for ($page = 1; $page <= $maxPages; $page++) {
            $params['page'] = $page;
            $result = $this->get($endpoint, $params);

            if (!$result->succeeded()) {
                if ($page === 1) {
                    return $result;
                }
                break;
            }

            $items = is_array($result->body) ? $result->body : [$result->body];
            $allItems = array_merge($allItems, $items);
            $totalItems = $result->totalItems();
            $lastHeaders = $result->headers;

            if ($result->totalPages() <= 1 || $page >= $result->totalPages()) {
                break;
            }
        }

        return new WpRestResult(
            statusCode: 200,
            body: $allItems,
            headers: $lastHeaders,
            rawBody: json_encode($allItems, JSON_UNESCAPED_SLASHES) ?: '[]',
        );
    }

    public function discover(): WpRestResult
    {
        $url = $this->connection->restBaseUrl();

        try {
            $response = $this->httpClient->request('GET', $url, [
                'headers' => $this->defaultHeaders(),
                'timeout' => self::DEFAULT_TIMEOUT,
            ]);

            return $this->buildResult($response);
        } catch (TransportExceptionInterface $e) {
            return new WpRestResult(
                statusCode: 0,
                body: null,
                rawBody: sprintf('Transport error: %s', $e->getMessage()),
            );
        }
    }

    public function testConnection(): WpRestResult
    {
        $url = $this->connection->restBaseUrl();

        try {
            $response = $this->httpClient->request('GET', $url, [
                'headers' => $this->defaultHeaders(),
                'timeout' => 10,
            ]);

            $result = $this->buildResult($response);

            if ($result->succeeded() && is_array($result->body)) {
                $name = $result->body['name'] ?? 'Unknown';
                $description = $result->body['description'] ?? '';
                $namespaces = $result->body['namespaces'] ?? [];

                return new WpRestResult(
                    statusCode: 200,
                    body: [
                        'connected' => true,
                        'site_name' => $name,
                        'site_description' => $description,
                        'namespaces' => $namespaces,
                        'authenticated' => $this->connection->hasRestAccess(),
                    ],
                    rawBody: sprintf('Connected to "%s" — %s (%d namespaces available)', $name, $description, count($namespaces)),
                );
            }

            return $result;
        } catch (TransportExceptionInterface $e) {
            return new WpRestResult(
                statusCode: 0,
                body: ['connected' => false, 'error' => $e->getMessage()],
                rawBody: sprintf('Connection failed: %s', $e->getMessage()),
            );
        }
    }

    public function getConnection(): SiteConnection
    {
        return $this->connection;
    }

    private function request(
        string $method,
        string $endpoint,
        array $params = [],
        array $body = [],
        int $timeout = self::DEFAULT_TIMEOUT,
    ): WpRestResult {
        $url = $this->buildUrl($endpoint, $params);

        $options = [
            'headers' => $this->defaultHeaders(),
            'timeout' => $timeout,
        ];

        if ($body !== [] && in_array($method, ['POST', 'PUT', 'PATCH'], true)) {
            $options['json'] = $body;
        }

        if ($method === 'DELETE') {
            $options['query'] = ['force' => true];
        }

        try {
            $response = $this->httpClient->request($method, $url, $options);

            return $this->buildResult($response);
        } catch (TransportExceptionInterface $e) {
            return new WpRestResult(
                statusCode: 0,
                body: null,
                rawBody: sprintf('Transport error: %s', $e->getMessage()),
            );
        }
    }

    private function buildUrl(string $endpoint, array $params = []): string
    {
        $base = $this->connection->restBaseUrl();
        $endpoint = '/' . ltrim($endpoint, '/');

        $url = $base . $endpoint;

        if ($params !== []) {
            $url .= '?' . http_build_query($params);
        }

        return $url;
    }

    private function buildResult(\Symfony\Contracts\HttpClient\ResponseInterface $response): WpRestResult
    {
        try {
            $statusCode = $response->getStatusCode();
            $rawBody = $response->getContent(false);
            $headers = $response->getHeaders(false);
            $body = json_decode($rawBody, true);

            if ($body === null && $rawBody !== '' && $rawBody !== 'null') {
                $body = $rawBody;
            }

            return new WpRestResult(
                statusCode: $statusCode,
                body: $body,
                headers: $headers,
                rawBody: $rawBody,
            );
        } catch (\Throwable $e) {
            return new WpRestResult(
                statusCode: 0,
                body: null,
                rawBody: sprintf('Response error: %s', $e->getMessage()),
            );
        }
    }

    private function authHeader(): string
    {
        return 'Basic ' . base64_encode($this->connection->username . ':' . $this->connection->appPassword);
    }

    private function defaultHeaders(): array
    {
        $headers = [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ];

        if ($this->connection->hasRestAccess()) {
            $headers['Authorization'] = $this->authHeader();
        }

        return $headers;
    }
}
