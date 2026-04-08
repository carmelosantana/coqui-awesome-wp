<?php

declare(strict_types=1);

namespace CarmeloSantana\CoquiAwesomeWp\Tool;

use CarmeloSantana\CoquiAwesomeWp\Client\WpRestClient;
use CarmeloSantana\CoquiAwesomeWp\Storage\SiteManager;
use CarmeloSantana\PHPAgents\Contract\ToolInterface;
use CarmeloSantana\PHPAgents\Tool\Tool;
use CarmeloSantana\PHPAgents\Tool\ToolResult;
use CarmeloSantana\PHPAgents\Tool\Parameter\EnumParameter;
use CarmeloSantana\PHPAgents\Tool\Parameter\StringParameter;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final readonly class RestApiTool
{
    public function __construct(
        private SiteManager $siteManager,
        private ?HttpClientInterface $httpClient = null,
    ) {}

    public function build(): ToolInterface
    {
        return new Tool(
            name: 'awp_rest',
            description: 'Make raw WordPress REST API requests — for custom endpoints, custom post types, or any endpoint not covered by other awp_* tools.',
            parameters: [
                new EnumParameter(
                    'method',
                    'HTTP method.',
                    values: ['get', 'post', 'put', 'patch', 'delete'],
                    required: true,
                ),
                new StringParameter(
                    'endpoint',
                    'REST API endpoint path (e.g. "/wp/v2/posts" or "/wc/v3/products"). Must start with "/".',
                    required: true,
                ),
                new StringParameter(
                    'body',
                    'JSON-encoded request body for POST/PUT/PATCH requests.',
                    required: false,
                ),
                new StringParameter(
                    'query',
                    'JSON-encoded query parameters (e.g. {"per_page": 5, "status": "draft"}).',
                    required: false,
                ),
                new StringParameter(
                    'site',
                    'Site alias to target. Uses default site if omitted.',
                    required: false,
                ),
            ],
            callback: fn(array $args) => $this->execute($args),
        );
    }

    private function execute(array $args): ToolResult
    {
        $method = strtolower(trim((string) ($args['method'] ?? '')));
        $endpoint = trim((string) ($args['endpoint'] ?? ''));

        if ($endpoint === '') {
            return ToolResult::error('The "endpoint" parameter is required (e.g. "/wp/v2/posts").');
        }

        if (!str_starts_with($endpoint, '/')) {
            $endpoint = '/' . $endpoint;
        }

        $client = $this->resolveClient($args);
        if ($client instanceof ToolResult) {
            return $client;
        }

        $queryParams = $this->decodeJson($args['query'] ?? null);
        $bodyData = $this->decodeJson($args['body'] ?? null);

        return match ($method) {
            'get' => $client->get($endpoint, $queryParams)->toToolResult(),
            'post' => $client->post($endpoint, $bodyData)->toToolResult(),
            'put' => $client->put($endpoint, $bodyData)->toToolResult(),
            'patch' => $client->patch($endpoint, $bodyData)->toToolResult(),
            'delete' => $client->delete($endpoint, $queryParams)->toToolResult(),
            default => ToolResult::error(sprintf('Unknown method: %s', $method)),
        };
    }

    private function decodeJson(?string $json): array
    {
        if ($json === null || trim($json) === '') {
            return [];
        }

        $decoded = json_decode($json, true);

        if (!is_array($decoded)) {
            return [];
        }

        return $decoded;
    }

    private function resolveClient(array $args): WpRestClient|ToolResult
    {
        $site = trim((string) ($args['site'] ?? ''));
        $conn = $this->siteManager->resolveSiteOrDefault($site);

        if ($conn === null) {
            return ToolResult::error('No site configured. Add a site first with awp_sites(action: "add", ...).');
        }

        if (!$conn->hasRestAccess()) {
            return ToolResult::error(sprintf(
                'Site "%s" has no REST API credentials. Update with: awp_sites(action: "update", alias: "%s", username: "...", app_password: "...")',
                $conn->alias,
                $conn->alias,
            ));
        }

        return new WpRestClient($conn, ...(($this->httpClient !== null) ? [$this->httpClient] : []));
    }
}
