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

final readonly class ContentTool
{
    public function __construct(
        private SiteManager $siteManager,
        private ?HttpClientInterface $httpClient = null,
    ) {}

    public function build(): ToolInterface
    {
        return new Tool(
            name: 'awp_content',
            description: 'Manage WordPress posts and pages via REST API — create, read, update, delete content, and view revisions.',
            parameters: [
                new EnumParameter(
                    'action',
                    'Operation to perform.',
                    values: ['list', 'create', 'update', 'delete', 'get', 'list_revisions', 'get_revision'],
                    required: true,
                ),
                new EnumParameter(
                    'post_type',
                    'Content type. Defaults to "post".',
                    values: ['post', 'page'],
                    required: false,
                ),
                new NumberParameter(
                    'id',
                    'Post/page ID. Required for get, update, delete, list_revisions.',
                    required: false,
                    integer: true,
                ),
                new NumberParameter(
                    'revision_id',
                    'Revision ID. Required for get_revision.',
                    required: false,
                    integer: true,
                ),
                new StringParameter(
                    'title',
                    'Post/page title.',
                    required: false,
                ),
                new StringParameter(
                    'content',
                    'Post/page content (HTML).',
                    required: false,
                ),
                new EnumParameter(
                    'status',
                    'Post status.',
                    values: ['draft', 'publish', 'pending', 'private', 'future', 'trash'],
                    required: false,
                ),
                new StringParameter(
                    'slug',
                    'URL slug.',
                    required: false,
                ),
                new StringParameter(
                    'excerpt',
                    'Post excerpt.',
                    required: false,
                ),
                new NumberParameter(
                    'author',
                    'Author user ID.',
                    required: false,
                    integer: true,
                ),
                new StringParameter(
                    'categories',
                    'Comma-separated category IDs (posts only).',
                    required: false,
                ),
                new StringParameter(
                    'tags',
                    'Comma-separated tag IDs (posts only).',
                    required: false,
                ),
                new NumberParameter(
                    'featured_media',
                    'Featured image media ID.',
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
                    values: ['date', 'relevance', 'id', 'include', 'title', 'slug', 'modified', 'author'],
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

        $type = ($args['post_type'] ?? 'post') === 'page' ? 'pages' : 'posts';
        $endpoint = '/wp/v2/' . $type;

        return match ($action) {
            'list' => $this->listContent($client, $endpoint, $args),
            'create' => $this->createContent($client, $endpoint, $args),
            'update' => $this->updateContent($client, $endpoint, $args),
            'delete' => $this->deleteContent($client, $endpoint, $args),
            'get' => $this->getContent($client, $endpoint, $args),
            'list_revisions' => $this->listRevisions($client, $endpoint, $args),
            'get_revision' => $this->getRevision($client, $endpoint, $args),
            default => ToolResult::error(sprintf('Unknown action: %s', $action)),
        };
    }

    private function listContent(WpRestClient $client, string $endpoint, array $args): ToolResult
    {
        $params = [];

        if (isset($args['search']) && $args['search'] !== '') {
            $params['search'] = $args['search'];
        }
        if (isset($args['status'])) {
            $params['status'] = $args['status'];
        }
        if (isset($args['author'])) {
            $params['author'] = (int) $args['author'];
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

        return $client->get($endpoint, $params)->toToolResult();
    }

    private function createContent(WpRestClient $client, string $endpoint, array $args): ToolResult
    {
        $body = [];

        if (isset($args['title'])) {
            $body['title'] = $args['title'];
        }
        if (isset($args['content'])) {
            $body['content'] = $args['content'];
        }
        if (isset($args['status'])) {
            $body['status'] = $args['status'];
        }
        if (isset($args['slug'])) {
            $body['slug'] = $args['slug'];
        }
        if (isset($args['excerpt'])) {
            $body['excerpt'] = $args['excerpt'];
        }
        if (isset($args['author'])) {
            $body['author'] = (int) $args['author'];
        }
        if (isset($args['featured_media'])) {
            $body['featured_media'] = (int) $args['featured_media'];
        }
        if (isset($args['categories'])) {
            $body['categories'] = array_map('intval', explode(',', (string) $args['categories']));
        }
        if (isset($args['tags'])) {
            $body['tags'] = array_map('intval', explode(',', (string) $args['tags']));
        }

        if ($body === []) {
            return ToolResult::error('At least "title" or "content" is required to create content.');
        }

        return $client->post($endpoint, $body)->toToolResult();
    }

    private function updateContent(WpRestClient $client, string $endpoint, array $args): ToolResult
    {
        $id = $args['id'] ?? null;
        if ($id === null) {
            return ToolResult::error('The "id" parameter is required for update.');
        }

        $body = [];

        foreach (['title', 'content', 'status', 'slug', 'excerpt'] as $field) {
            if (isset($args[$field])) {
                $body[$field] = $args[$field];
            }
        }
        if (isset($args['author'])) {
            $body['author'] = (int) $args['author'];
        }
        if (isset($args['featured_media'])) {
            $body['featured_media'] = (int) $args['featured_media'];
        }
        if (isset($args['categories'])) {
            $body['categories'] = array_map('intval', explode(',', (string) $args['categories']));
        }
        if (isset($args['tags'])) {
            $body['tags'] = array_map('intval', explode(',', (string) $args['tags']));
        }

        if ($body === []) {
            return ToolResult::error('No fields to update.');
        }

        return $client->post(sprintf('%s/%d', $endpoint, (int) $id), $body)->toToolResult();
    }

    private function deleteContent(WpRestClient $client, string $endpoint, array $args): ToolResult
    {
        $id = $args['id'] ?? null;
        if ($id === null) {
            return ToolResult::error('The "id" parameter is required for delete.');
        }

        return $client->delete(sprintf('%s/%d', $endpoint, (int) $id))->toToolResult();
    }

    private function getContent(WpRestClient $client, string $endpoint, array $args): ToolResult
    {
        $id = $args['id'] ?? null;
        if ($id === null) {
            return ToolResult::error('The "id" parameter is required for get.');
        }

        return $client->get(sprintf('%s/%d', $endpoint, (int) $id))->toToolResult();
    }

    private function listRevisions(WpRestClient $client, string $endpoint, array $args): ToolResult
    {
        $id = $args['id'] ?? null;
        if ($id === null) {
            return ToolResult::error('The "id" parameter is required for list_revisions.');
        }

        return $client->get(sprintf('%s/%d/revisions', $endpoint, (int) $id))->toToolResult();
    }

    private function getRevision(WpRestClient $client, string $endpoint, array $args): ToolResult
    {
        $id = $args['id'] ?? null;
        $revisionId = $args['revision_id'] ?? null;

        if ($id === null || $revisionId === null) {
            return ToolResult::error('Both "id" and "revision_id" are required for get_revision.');
        }

        return $client->get(sprintf('%s/%d/revisions/%d', $endpoint, (int) $id, (int) $revisionId))->toToolResult();
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
