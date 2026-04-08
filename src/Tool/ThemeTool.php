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

final readonly class ThemeTool
{
    public function __construct(
        private SiteManager $siteManager,
        private ?HttpClientInterface $httpClient = null,
    ) {}

    public function build(): ToolInterface
    {
        return new Tool(
            name: 'awp_themes',
            description: 'Manage WordPress themes — list installed themes, get details, and activate a theme.',
            parameters: [
                new EnumParameter(
                    'action',
                    'Operation to perform.',
                    values: ['list', 'get', 'activate'],
                    required: true,
                ),
                new StringParameter(
                    'stylesheet',
                    'Theme stylesheet (slug). Required for get and activate (e.g. "twentytwentyfour").',
                    required: false,
                ),
                new EnumParameter(
                    'status',
                    'Filter by theme status for list action.',
                    values: ['active', 'inactive'],
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
            'list' => $this->listThemes($client, $args),
            'get' => $this->getTheme($client, $args),
            'activate' => $this->activateTheme($client, $args),
            default => ToolResult::error(sprintf('Unknown action: %s', $action)),
        };
    }

    private function listThemes(WpRestClient $client, array $args): ToolResult
    {
        $params = [];

        if (isset($args['status'])) {
            $params['status'] = $args['status'];
        }

        return $client->get('/wp/v2/themes', $params)->toToolResult();
    }

    private function getTheme(WpRestClient $client, array $args): ToolResult
    {
        $stylesheet = trim((string) ($args['stylesheet'] ?? ''));
        if ($stylesheet === '') {
            return ToolResult::error('The "stylesheet" parameter is required for get (e.g. "twentytwentyfour").');
        }

        return $client->get(sprintf('/wp/v2/themes/%s', rawurlencode($stylesheet)))->toToolResult();
    }

    private function activateTheme(WpRestClient $client, array $args): ToolResult
    {
        $stylesheet = trim((string) ($args['stylesheet'] ?? ''));
        if ($stylesheet === '') {
            return ToolResult::error('The "stylesheet" parameter is required for activate (e.g. "twentytwentyfour").');
        }

        return $client->post(sprintf('/wp/v2/themes/%s', rawurlencode($stylesheet)), [
            'status' => 'active',
        ])->toToolResult();
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
