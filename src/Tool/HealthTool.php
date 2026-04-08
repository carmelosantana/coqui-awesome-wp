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

final readonly class HealthTool
{
    public function __construct(
        private SiteManager $siteManager,
        private ?HttpClientInterface $httpClient = null,
    ) {}

    public function build(): ToolInterface
    {
        return new Tool(
            name: 'awp_health',
            description: 'WordPress site health information — test diagnostics, directory sizes, status overview, and debug data.',
            parameters: [
                new EnumParameter(
                    'action',
                    'Operation to perform.',
                    values: ['status', 'tests', 'directory_sizes'],
                    required: true,
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
        $action = trim((string) ($args['action'] ?? ''));
        $client = $this->resolveClient($args);

        if ($client instanceof ToolResult) {
            return $client;
        }

        return match ($action) {
            'status' => $this->getStatus($client),
            'tests' => $client->get('/wp-site-health/v1/tests/background-updates')->toToolResult(),
            'directory_sizes' => $client->get('/wp-site-health/v1/directory-sizes')->toToolResult(),
            default => ToolResult::error(sprintf('Unknown action: %s', $action)),
        };
    }

    private function getStatus(WpRestClient $client): ToolResult
    {
        $discover = $client->discover();
        if (!$discover->succeeded()) {
            return $discover->toToolResult();
        }

        $info = [
            'name' => $discover->body['name'] ?? 'unknown',
            'description' => $discover->body['description'] ?? '',
            'url' => $discover->body['url'] ?? '',
            'home' => $discover->body['home'] ?? '',
            'gmt_offset' => $discover->body['gmt_offset'] ?? '',
            'timezone_string' => $discover->body['timezone_string'] ?? '',
            'namespaces' => $discover->body['namespaces'] ?? [],
        ];

        $healthResult = $client->get('/wp-site-health/v1/tests/authorization-header');

        if ($healthResult->succeeded()) {
            $info['auth_header_test'] = $healthResult->body;
        }

        return ToolResult::success(json_encode($info, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?: '{}');
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
