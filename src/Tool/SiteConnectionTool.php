<?php

declare(strict_types=1);

namespace CarmeloSantana\CoquiAwesomeWp\Tool;

use CarmeloSantana\CoquiAwesomeWp\Client\WpRestClient;
use CarmeloSantana\CoquiAwesomeWp\Storage\SiteManager;
use CarmeloSantana\PHPAgents\Contract\ToolInterface;
use CarmeloSantana\PHPAgents\Tool\Tool;
use CarmeloSantana\PHPAgents\Tool\ToolResult;
use CarmeloSantana\PHPAgents\Tool\Parameter\BoolParameter;
use CarmeloSantana\PHPAgents\Tool\Parameter\EnumParameter;
use CarmeloSantana\PHPAgents\Tool\Parameter\StringParameter;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final readonly class SiteConnectionTool
{
    public function __construct(
        private SiteManager $siteManager,
        private ?HttpClientInterface $httpClient = null,
    ) {}

    public function build(): ToolInterface
    {
        return new Tool(
            name: 'awp_sites',
            description: 'Manage WordPress site connections — add, list, remove, test, and set default site for API operations. Sites store URL, credentials, SSH config, and WP-CLI settings.',
            parameters: [
                new EnumParameter(
                    'action',
                    'Operation to perform.',
                    values: ['add', 'list', 'get', 'remove', 'update', 'set_default', 'test'],
                    required: true,
                ),
                new StringParameter(
                    'alias',
                    'Short name for the site (kebab-case, e.g. "my-blog"). Required for add, get, remove, update, set_default, test.',
                    required: false,
                ),
                new StringParameter(
                    'url',
                    'WordPress site URL (e.g. "https://example.com"). Required for add.',
                    required: false,
                ),
                new StringParameter(
                    'username',
                    'WordPress username for REST API auth.',
                    required: false,
                ),
                new StringParameter(
                    'app_password',
                    'WordPress Application Password (generate at Users → Profile → Application Passwords).',
                    required: false,
                ),
                new StringParameter(
                    'ssh',
                    'SSH connection string for WP-CLI (e.g. "user@host:/var/www/html").',
                    required: false,
                ),
                new StringParameter(
                    'wp_path',
                    'Local WordPress installation path (e.g. "/var/www/html").',
                    required: false,
                ),
                new StringParameter(
                    'wp_cli_url',
                    'Site URL for WP-CLI multisite targeting.',
                    required: false,
                ),
                new BoolParameter(
                    'is_multisite',
                    'Whether this is a WordPress Multisite installation.',
                    required: false,
                ),
                new StringParameter(
                    'notes',
                    'Optional notes about this site.',
                    required: false,
                ),
            ],
            callback: fn(array $args) => $this->execute($args),
        );
    }

    private function execute(array $args): ToolResult
    {
        $action = trim((string) ($args['action'] ?? ''));

        return match ($action) {
            'add' => $this->addSite($args),
            'list' => $this->listSites(),
            'get' => $this->getSite($args),
            'remove' => $this->removeSite($args),
            'update' => $this->updateSite($args),
            'set_default' => $this->setDefault($args),
            'test' => $this->testConnection($args),
            default => ToolResult::error(sprintf('Unknown action: %s', $action)),
        };
    }

    private function addSite(array $args): ToolResult
    {
        $alias = trim((string) ($args['alias'] ?? ''));
        $url = trim((string) ($args['url'] ?? ''));

        if ($alias === '' || $url === '') {
            return ToolResult::error('Both "alias" and "url" are required to add a site.');
        }

        try {
            $connection = $this->siteManager->addSite(
                alias: $alias,
                url: $url,
                username: trim((string) ($args['username'] ?? '')),
                appPassword: trim((string) ($args['app_password'] ?? '')),
                ssh: trim((string) ($args['ssh'] ?? '')),
                wpPath: trim((string) ($args['wp_path'] ?? '')),
                wpCliUrl: trim((string) ($args['wp_cli_url'] ?? '')),
                isMultisite: (bool) ($args['is_multisite'] ?? false),
                notes: trim((string) ($args['notes'] ?? '')),
            );

            $features = [];
            if ($connection->hasRestAccess()) {
                $features[] = 'REST API';
            }
            if ($connection->hasCliAccess()) {
                $features[] = 'WP-CLI' . ($connection->ssh !== '' ? ' (SSH)' : ' (local)');
            }

            return ToolResult::success(sprintf(
                "Site \"%s\" added successfully.\n\nURL: %s\nFeatures: %s\nDefault: %s",
                $alias,
                $connection->url,
                $features !== [] ? implode(', ', $features) : 'None configured — add credentials for REST API access',
                $this->siteManager->getDefaultAlias() === $alias ? 'Yes' : 'No',
            ));
        } catch (\InvalidArgumentException $e) {
            return ToolResult::error($e->getMessage());
        }
    }

    private function listSites(): ToolResult
    {
        $sites = $this->siteManager->listSites();

        if ($sites === []) {
            return ToolResult::success("No sites configured.\n\nAdd a site with: awp_sites(action: \"add\", alias: \"my-blog\", url: \"https://example.com\", username: \"admin\", app_password: \"xxxx xxxx xxxx xxxx\")");
        }

        $defaultAlias = $this->siteManager->getDefaultAlias();
        $rows = [];

        foreach ($sites as $alias => $conn) {
            $channels = [];
            if ($conn->hasRestAccess()) {
                $channels[] = 'REST';
            }
            if ($conn->hasCliAccess()) {
                $channels[] = 'CLI';
            }

            $rows[] = sprintf(
                '| %s%s | %s | %s | %s |',
                $alias,
                $alias === $defaultAlias ? ' ★' : '',
                $conn->url,
                $channels !== [] ? implode('+', $channels) : '—',
                $conn->notes !== '' ? mb_substr($conn->notes, 0, 30) : '—',
            );
        }

        $header = "| Alias | URL | Access | Notes |\n| --- | --- | --- | --- |";

        return ToolResult::success(sprintf(
            "**%d site(s) configured** (★ = default)\n\n%s\n%s",
            count($sites),
            $header,
            implode("\n", $rows),
        ));
    }

    private function getSite(array $args): ToolResult
    {
        $alias = trim((string) ($args['alias'] ?? ''));
        if ($alias === '') {
            return ToolResult::error('The "alias" parameter is required.');
        }

        $conn = $this->siteManager->getSite($alias);
        if ($conn === null) {
            return ToolResult::error(sprintf('Site "%s" not found.', $alias));
        }

        return ToolResult::success((string) json_encode([
            'alias' => $conn->alias,
            'url' => $conn->url,
            'username' => $conn->username,
            'has_password' => $conn->appPassword !== '',
            'ssh' => $conn->ssh,
            'wp_path' => $conn->wpPath,
            'wp_cli_url' => $conn->wpCliUrl,
            'is_multisite' => $conn->isMultisite,
            'notes' => $conn->notes,
            'rest_access' => $conn->hasRestAccess(),
            'cli_access' => $conn->hasCliAccess(),
            'is_default' => $this->siteManager->getDefaultAlias() === $conn->alias,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    private function removeSite(array $args): ToolResult
    {
        $alias = trim((string) ($args['alias'] ?? ''));
        if ($alias === '') {
            return ToolResult::error('The "alias" parameter is required.');
        }

        if ($this->siteManager->removeSite($alias)) {
            return ToolResult::success(sprintf('Site "%s" removed.', $alias));
        }

        return ToolResult::error(sprintf('Site "%s" not found.', $alias));
    }

    private function updateSite(array $args): ToolResult
    {
        $alias = trim((string) ($args['alias'] ?? ''));
        if ($alias === '') {
            return ToolResult::error('The "alias" parameter is required.');
        }

        $updates = [];
        foreach (['url', 'username', 'app_password', 'ssh', 'wp_path', 'wp_cli_url', 'is_multisite', 'notes'] as $key) {
            if (isset($args[$key])) {
                $updates[$key] = $args[$key];
            }
        }

        if ($updates === []) {
            return ToolResult::error('No fields to update. Provide at least one of: url, username, app_password, ssh, wp_path, wp_cli_url, is_multisite, notes.');
        }

        $connection = $this->siteManager->updateSite($alias, $updates);
        if ($connection === null) {
            return ToolResult::error(sprintf('Site "%s" not found.', $alias));
        }

        return ToolResult::success(sprintf('Site "%s" updated successfully.', $alias));
    }

    private function setDefault(array $args): ToolResult
    {
        $alias = trim((string) ($args['alias'] ?? ''));
        if ($alias === '') {
            return ToolResult::error('The "alias" parameter is required.');
        }

        if ($this->siteManager->setDefault($alias)) {
            return ToolResult::success(sprintf('Default site set to "%s". All tools will target this site unless overridden with the "site" parameter.', $alias));
        }

        return ToolResult::error(sprintf('Site "%s" not found.', $alias));
    }

    private function testConnection(array $args): ToolResult
    {
        $alias = trim((string) ($args['alias'] ?? ''));
        $conn = $alias !== ''
            ? $this->siteManager->getSite($alias)
            : $this->siteManager->getDefault();

        if ($conn === null) {
            return ToolResult::error($alias !== ''
                ? sprintf('Site "%s" not found.', $alias)
                : 'No sites configured. Add a site first.');
        }

        $results = [];

        if ($conn->url !== '') {
            $client = new WpRestClient($conn, ...(($this->httpClient !== null) ? [$this->httpClient] : []));
            $testResult = $client->testConnection();
            $results[] = $testResult->succeeded()
                ? sprintf("✓ REST API: %s", $testResult->rawBody)
                : sprintf("✗ REST API: %s", $testResult->errorMessage());

            if ($conn->hasRestAccess()) {
                $authTest = $client->get('/wp/v2/users/me');
                $results[] = $authTest->succeeded()
                    ? '✓ Authentication: Valid credentials'
                    : sprintf('✗ Authentication: %s', $authTest->errorMessage());
            } else {
                $results[] = '○ Authentication: No credentials configured';
            }
        } else {
            $results[] = '○ REST API: No URL configured';
        }

        if ($conn->hasCliAccess()) {
            $results[] = sprintf('○ WP-CLI: %s (use wp_* tools to verify)', $conn->ssh !== '' ? 'SSH configured' : 'Local path configured');
        } else {
            $results[] = '○ WP-CLI: Not configured';
        }

        return ToolResult::success(sprintf(
            "**Connection test for \"%s\"**\n\n%s",
            $conn->alias,
            implode("\n", $results),
        ));
    }
}
