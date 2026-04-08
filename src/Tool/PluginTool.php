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

final readonly class PluginTool
{
    public function __construct(
        private SiteManager $siteManager,
        private ?HttpClientInterface $httpClient = null,
    ) {}

    public function build(): ToolInterface
    {
        return new Tool(
            name: 'awp_plugins',
            description: 'Manage WordPress plugins — list, get details, install, activate, deactivate, update, and delete plugins via REST API.',
            parameters: [
                new EnumParameter(
                    'action',
                    'Operation to perform.',
                    values: ['list', 'get', 'install', 'activate', 'deactivate', 'delete', 'update'],
                    required: true,
                ),
                new StringParameter(
                    'plugin',
                    'Plugin identifier (directory/file format, e.g. "akismet/akismet"). Required for get, activate, deactivate, delete, update.',
                    required: false,
                ),
                new StringParameter(
                    'slug',
                    'Plugin slug from wordpress.org for install action (e.g. "akismet").',
                    required: false,
                ),
                new EnumParameter(
                    'status',
                    'Filter by plugin status for list action.',
                    values: ['active', 'inactive', 'network-active'],
                    required: false,
                ),
                new StringParameter(
                    'search',
                    'Search term for list action.',
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
        $action = trim((string) ($args['action'] ?? ''));
        $client = $this->resolveClient($args);

        if ($client instanceof ToolResult) {
            return $client;
        }

        return match ($action) {
            'list' => $this->listPlugins($client, $args),
            'get' => $this->getPlugin($client, $args),
            'install' => $this->installPlugin($client, $args),
            'activate' => $this->setPluginStatus($client, $args, 'active'),
            'deactivate' => $this->setPluginStatus($client, $args, 'inactive'),
            'delete' => $this->deletePlugin($client, $args),
            'update' => $this->updatePlugin($client, $args),
            default => ToolResult::error(sprintf('Unknown action: %s', $action)),
        };
    }

    private function listPlugins(WpRestClient $client, array $args): ToolResult
    {
        $params = [];

        if (isset($args['status'])) {
            $params['status'] = $args['status'];
        }
        if (isset($args['search']) && $args['search'] !== '') {
            $params['search'] = $args['search'];
        }

        return $client->get('/wp/v2/plugins', $params)->toToolResult();
    }

    private function getPlugin(WpRestClient $client, array $args): ToolResult
    {
        $plugin = $this->resolvePlugin($args);
        if ($plugin instanceof ToolResult) {
            return $plugin;
        }

        return $client->get(sprintf('/wp/v2/plugins/%s', $this->encodePluginPath($plugin)))->toToolResult();
    }

    private function installPlugin(WpRestClient $client, array $args): ToolResult
    {
        $slug = trim((string) ($args['slug'] ?? ''));
        if ($slug === '') {
            return ToolResult::error('The "slug" parameter is required for install (e.g. "akismet").');
        }

        return $client->post('/wp/v2/plugins', [
            'slug' => $slug,
            'status' => 'inactive',
        ])->toToolResult();
    }

    private function setPluginStatus(WpRestClient $client, array $args, string $status): ToolResult
    {
        $plugin = $this->resolvePlugin($args);
        if ($plugin instanceof ToolResult) {
            return $plugin;
        }

        return $client->put(
            sprintf('/wp/v2/plugins/%s', $this->encodePluginPath($plugin)),
            ['status' => $status],
        )->toToolResult();
    }

    private function deletePlugin(WpRestClient $client, array $args): ToolResult
    {
        $plugin = $this->resolvePlugin($args);
        if ($plugin instanceof ToolResult) {
            return $plugin;
        }

        $getResult = $client->get(sprintf('/wp/v2/plugins/%s', $this->encodePluginPath($plugin)));
        if ($getResult->succeeded()) {
            $body = $getResult->body;
            if (($body['status'] ?? '') === 'active') {
                $client->put(
                    sprintf('/wp/v2/plugins/%s', $this->encodePluginPath($plugin)),
                    ['status' => 'inactive'],
                );
            }
        }

        return $client->delete(sprintf('/wp/v2/plugins/%s', $this->encodePluginPath($plugin)))->toToolResult();
    }

    private function updatePlugin(WpRestClient $client, array $args): ToolResult
    {
        $plugin = $this->resolvePlugin($args);
        if ($plugin instanceof ToolResult) {
            return $plugin;
        }

        return $client->put(
            sprintf('/wp/v2/plugins/%s', $this->encodePluginPath($plugin)),
            [],
        )->toToolResult();
    }

    private function resolvePlugin(array $args): string|ToolResult
    {
        $plugin = trim((string) ($args['plugin'] ?? ''));
        if ($plugin === '') {
            return ToolResult::error('The "plugin" parameter is required (directory/file format, e.g. "akismet/akismet").');
        }

        return $plugin;
    }

    /**
     * WordPress REST API expects plugin path with directory separator encoded.
     * e.g. "akismet/akismet" → "akismet%2Fakismet"
     */
    private function encodePluginPath(string $plugin): string
    {
        return str_replace('/', '%2F', $plugin);
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
