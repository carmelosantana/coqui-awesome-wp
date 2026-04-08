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

final readonly class CommentTool
{
    public function __construct(
        private SiteManager $siteManager,
        private ?HttpClientInterface $httpClient = null,
    ) {}

    public function build(): ToolInterface
    {
        return new Tool(
            name: 'awp_comments',
            description: 'Manage WordPress comments — list, create, get, update, and delete comments.',
            parameters: [
                new EnumParameter(
                    'action',
                    'Operation to perform.',
                    values: ['list', 'create', 'get', 'update', 'delete'],
                    required: true,
                ),
                new NumberParameter(
                    'id',
                    'Comment ID. Required for get, update, delete.',
                    required: false,
                    integer: true,
                ),
                new NumberParameter(
                    'post',
                    'Post ID to associate with comment or filter by.',
                    required: false,
                    integer: true,
                ),
                new StringParameter(
                    'content',
                    'Comment content.',
                    required: false,
                ),
                new StringParameter(
                    'author_name',
                    'Comment author display name.',
                    required: false,
                ),
                new StringParameter(
                    'author_email',
                    'Comment author email.',
                    required: false,
                ),
                new EnumParameter(
                    'status',
                    'Comment status.',
                    values: ['approved', 'hold', 'spam', 'trash'],
                    required: false,
                ),
                new NumberParameter(
                    'parent',
                    'Parent comment ID for threaded replies.',
                    required: false,
                    integer: true,
                ),
                new StringParameter(
                    'search',
                    'Search term for list action.',
                    required: false,
                ),
                new EnumParameter(
                    'orderby',
                    'Sort field for list action.',
                    values: ['date', 'date_gmt', 'id', 'include', 'post', 'parent', 'type'],
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
            'list' => $this->listComments($client, $args),
            'create' => $this->createComment($client, $args),
            'get' => $this->getComment($client, $args),
            'update' => $this->updateComment($client, $args),
            'delete' => $this->deleteComment($client, $args),
            default => ToolResult::error(sprintf('Unknown action: %s', $action)),
        };
    }

    private function listComments(WpRestClient $client, array $args): ToolResult
    {
        $params = [];

        if (isset($args['post'])) {
            $params['post'] = (int) $args['post'];
        }
        if (isset($args['status'])) {
            $params['status'] = $args['status'];
        }
        if (isset($args['search']) && $args['search'] !== '') {
            $params['search'] = $args['search'];
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

        return $client->get('/wp/v2/comments', $params)->toToolResult();
    }

    private function createComment(WpRestClient $client, array $args): ToolResult
    {
        $content = trim((string) ($args['content'] ?? ''));
        if ($content === '') {
            return ToolResult::error('The "content" parameter is required to create a comment.');
        }

        $body = ['content' => $content];

        if (isset($args['post'])) {
            $body['post'] = (int) $args['post'];
        }
        if (isset($args['author_name'])) {
            $body['author_name'] = $args['author_name'];
        }
        if (isset($args['author_email'])) {
            $body['author_email'] = $args['author_email'];
        }
        if (isset($args['status'])) {
            $body['status'] = $args['status'];
        }
        if (isset($args['parent'])) {
            $body['parent'] = (int) $args['parent'];
        }

        return $client->post('/wp/v2/comments', $body)->toToolResult();
    }

    private function getComment(WpRestClient $client, array $args): ToolResult
    {
        $id = $args['id'] ?? null;
        if ($id === null) {
            return ToolResult::error('The "id" parameter is required for get.');
        }

        return $client->get(sprintf('/wp/v2/comments/%d', (int) $id))->toToolResult();
    }

    private function updateComment(WpRestClient $client, array $args): ToolResult
    {
        $id = $args['id'] ?? null;
        if ($id === null) {
            return ToolResult::error('The "id" parameter is required for update.');
        }

        $body = [];

        if (isset($args['content'])) {
            $body['content'] = $args['content'];
        }
        if (isset($args['status'])) {
            $body['status'] = $args['status'];
        }
        if (isset($args['author_name'])) {
            $body['author_name'] = $args['author_name'];
        }
        if (isset($args['author_email'])) {
            $body['author_email'] = $args['author_email'];
        }

        if ($body === []) {
            return ToolResult::error('No fields to update.');
        }

        return $client->post(sprintf('/wp/v2/comments/%d', (int) $id), $body)->toToolResult();
    }

    private function deleteComment(WpRestClient $client, array $args): ToolResult
    {
        $id = $args['id'] ?? null;
        if ($id === null) {
            return ToolResult::error('The "id" parameter is required for delete.');
        }

        return $client->delete(sprintf('/wp/v2/comments/%d', (int) $id))->toToolResult();
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
