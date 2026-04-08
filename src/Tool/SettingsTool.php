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

final readonly class SettingsTool
{
    public function __construct(
        private SiteManager $siteManager,
        private ?HttpClientInterface $httpClient = null,
    ) {}

    public function build(): ToolInterface
    {
        return new Tool(
            name: 'awp_settings',
            description: 'Read and update WordPress site settings (title, description, URL, date format, timezone, reading/writing options, etc.).',
            parameters: [
                new EnumParameter(
                    'action',
                    'Operation to perform.',
                    values: ['get', 'update'],
                    required: true,
                ),
                new StringParameter(
                    'title',
                    'Site title.',
                    required: false,
                ),
                new StringParameter(
                    'description',
                    'Site tagline/description.',
                    required: false,
                ),
                new StringParameter(
                    'url',
                    'Site URL.',
                    required: false,
                ),
                new StringParameter(
                    'email',
                    'Admin email address.',
                    required: false,
                ),
                new StringParameter(
                    'timezone',
                    'Timezone string (e.g. "America/New_York").',
                    required: false,
                ),
                new StringParameter(
                    'date_format',
                    'Date format string.',
                    required: false,
                ),
                new StringParameter(
                    'time_format',
                    'Time format string.',
                    required: false,
                ),
                new StringParameter(
                    'language',
                    'WordPress locale (e.g. "en_US").',
                    required: false,
                ),
                new StringParameter(
                    'posts_per_page',
                    'Number of posts per page.',
                    required: false,
                ),
                new StringParameter(
                    'default_comment_status',
                    'Default comment status for new posts ("open" or "closed").',
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
            'get' => $client->get('/wp/v2/settings')->toToolResult(),
            'update' => $this->updateSettings($client, $args),
            default => ToolResult::error(sprintf('Unknown action: %s', $action)),
        };
    }

    private function updateSettings(WpRestClient $client, array $args): ToolResult
    {
        $body = [];

        $fieldMap = [
            'title' => 'title',
            'description' => 'description',
            'url' => 'url',
            'email' => 'email',
            'timezone' => 'timezone_string',
            'date_format' => 'date_format',
            'time_format' => 'time_format',
            'language' => 'language',
            'posts_per_page' => 'posts_per_page',
            'default_comment_status' => 'default_comment_status',
        ];

        foreach ($fieldMap as $param => $wpField) {
            if (isset($args[$param])) {
                $body[$wpField] = $args[$param];
            }
        }

        if ($body === []) {
            return ToolResult::error('No settings to update. Provide at least one setting field.');
        }

        return $client->post('/wp/v2/settings', $body)->toToolResult();
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
