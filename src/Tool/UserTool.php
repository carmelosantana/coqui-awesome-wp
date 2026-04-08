<?php

declare(strict_types=1);

namespace CarmeloSantana\CoquiAwesomeWp\Tool;

use CarmeloSantana\CoquiAwesomeWp\Client\WpRestClient;
use CarmeloSantana\CoquiAwesomeWp\Storage\SiteManager;
use CarmeloSantana\PHPAgents\Contract\ToolInterface;
use CarmeloSantana\PHPAgents\Tool\Tool;
use CarmeloSantana\PHPAgents\Tool\ToolResult;
use CarmeloSantana\PHPAgents\Tool\Parameter\EnumParameter;
use CarmeloSantana\PHPAgents\Tool\Parameter\NumberParameter;
use CarmeloSantana\PHPAgents\Tool\Parameter\StringParameter;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final readonly class UserTool
{
    public function __construct(
        private SiteManager $siteManager,
        private ?HttpClientInterface $httpClient = null,
    ) {}

    public function build(): ToolInterface
    {
        return new Tool(
            name: 'awp_users',
            description: 'Manage WordPress users — list, create, get, update, and delete users.',
            parameters: [
                new EnumParameter(
                    'action',
                    'Operation to perform.',
                    values: ['list', 'create', 'get', 'update', 'delete', 'me'],
                    required: true,
                ),
                new NumberParameter(
                    'id',
                    'User ID. Required for get, update, delete.',
                    required: false,
                    integer: true,
                ),
                new StringParameter(
                    'username',
                    'Login name for new user. Required for create.',
                    required: false,
                ),
                new StringParameter(
                    'email',
                    'User email. Required for create.',
                    required: false,
                ),
                new StringParameter(
                    'password',
                    'User password. Required for create.',
                    required: false,
                ),
                new StringParameter(
                    'first_name',
                    'User first name.',
                    required: false,
                ),
                new StringParameter(
                    'last_name',
                    'User last name.',
                    required: false,
                ),
                new StringParameter(
                    'nickname',
                    'User nickname.',
                    required: false,
                ),
                new StringParameter(
                    'description',
                    'User bio/description.',
                    required: false,
                ),
                new StringParameter(
                    'url',
                    'User website URL.',
                    required: false,
                ),
                new EnumParameter(
                    'roles',
                    'User role.',
                    values: ['administrator', 'editor', 'author', 'contributor', 'subscriber'],
                    required: false,
                ),
                new StringParameter(
                    'search',
                    'Search term for list action.',
                    required: false,
                ),
                new EnumParameter(
                    'orderby',
                    'Sort field for list action.',
                    values: ['id', 'include', 'name', 'registered_date', 'slug', 'email', 'url'],
                    required: false,
                ),
                new EnumParameter(
                    'order',
                    'Sort direction.',
                    values: ['asc', 'desc'],
                    required: false,
                ),
                new NumberParameter(
                    'per_page',
                    'Results per page (1-100). Default: 10.',
                    required: false,
                    integer: true,
                    minimum: 1,
                    maximum: 100,
                ),
                new NumberParameter(
                    'page',
                    'Page number for pagination.',
                    required: false,
                    integer: true,
                    minimum: 1,
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
            'list' => $this->listUsers($client, $args),
            'create' => $this->createUser($client, $args),
            'get' => $this->getUser($client, $args),
            'update' => $this->updateUser($client, $args),
            'delete' => $this->deleteUser($client, $args),
            'me' => $client->get('/wp/v2/users/me')->toToolResult(),
            default => ToolResult::error(sprintf('Unknown action: %s', $action)),
        };
    }

    private function listUsers(WpRestClient $client, array $args): ToolResult
    {
        $params = [];

        if (isset($args['search']) && $args['search'] !== '') {
            $params['search'] = $args['search'];
        }
        if (isset($args['roles'])) {
            $params['roles'] = $args['roles'];
        }
        if (isset($args['orderby'])) {
            $params['orderby'] = $args['orderby'];
        }
        if (isset($args['order'])) {
            $params['order'] = $args['order'];
        }
        if (isset($args['per_page'])) {
            $params['per_page'] = (int) $args['per_page'];
        }
        if (isset($args['page'])) {
            $params['page'] = (int) $args['page'];
        }

        return $client->get('/wp/v2/users', $params)->toToolResult();
    }

    private function createUser(WpRestClient $client, array $args): ToolResult
    {
        $username = trim((string) ($args['username'] ?? ''));
        $email = trim((string) ($args['email'] ?? ''));
        $password = $args['password'] ?? null;

        if ($username === '' || $email === '') {
            return ToolResult::error('Both "username" and "email" are required to create a user.');
        }

        $body = [
            'username' => $username,
            'email' => $email,
        ];

        if ($password !== null && $password !== '') {
            $body['password'] = $password;
        }

        foreach (['first_name', 'last_name', 'nickname', 'description', 'url'] as $field) {
            if (isset($args[$field])) {
                $body[$field] = $args[$field];
            }
        }

        if (isset($args['roles'])) {
            $body['roles'] = [$args['roles']];
        }

        return $client->post('/wp/v2/users', $body)->toToolResult();
    }

    private function getUser(WpRestClient $client, array $args): ToolResult
    {
        $id = $args['id'] ?? null;
        if ($id === null) {
            return ToolResult::error('The "id" parameter is required for get.');
        }

        return $client->get(sprintf('/wp/v2/users/%d', (int) $id))->toToolResult();
    }

    private function updateUser(WpRestClient $client, array $args): ToolResult
    {
        $id = $args['id'] ?? null;
        if ($id === null) {
            return ToolResult::error('The "id" parameter is required for update.');
        }

        $body = [];

        foreach (['email', 'first_name', 'last_name', 'nickname', 'description', 'url', 'password'] as $field) {
            if (isset($args[$field])) {
                $body[$field] = $args[$field];
            }
        }

        if (isset($args['roles'])) {
            $body['roles'] = [$args['roles']];
        }

        if ($body === []) {
            return ToolResult::error('No fields to update.');
        }

        return $client->post(sprintf('/wp/v2/users/%d', (int) $id), $body)->toToolResult();
    }

    private function deleteUser(WpRestClient $client, array $args): ToolResult
    {
        $id = $args['id'] ?? null;
        if ($id === null) {
            return ToolResult::error('The "id" parameter is required for delete.');
        }

        return $client->delete(sprintf('/wp/v2/users/%d', (int) $id), ['reassign' => 1])->toToolResult();
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
