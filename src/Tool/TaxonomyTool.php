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

final readonly class TaxonomyTool
{
    public function __construct(
        private SiteManager $siteManager,
        private ?HttpClientInterface $httpClient = null,
    ) {}

    public function build(): ToolInterface
    {
        return new Tool(
            name: 'awp_taxonomy',
            description: 'Manage WordPress taxonomies (categories, tags, custom) — list, create, get, update, and delete terms.',
            parameters: [
                new EnumParameter(
                    'action',
                    'Operation to perform.',
                    values: ['list', 'create', 'get', 'update', 'delete', 'list_taxonomies'],
                    required: true,
                ),
                new StringParameter(
                    'taxonomy',
                    'Taxonomy slug. "categories" or "tags" for built-in, or custom taxonomy slug. Default: categories.',
                    required: false,
                ),
                new NumberParameter(
                    'id',
                    'Term ID. Required for get, update, delete.',
                    required: false,
                    integer: true,
                ),
                new StringParameter(
                    'name',
                    'Term name. Required for create.',
                    required: false,
                ),
                new StringParameter(
                    'slug',
                    'Term slug.',
                    required: false,
                ),
                new StringParameter(
                    'description',
                    'Term description.',
                    required: false,
                ),
                new NumberParameter(
                    'parent',
                    'Parent term ID (for hierarchical taxonomies).',
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
                    values: ['id', 'include', 'name', 'slug', 'include_slugs', 'term_group', 'description', 'count'],
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

        if ($action === 'list_taxonomies') {
            return $client->get('/wp/v2/taxonomies')->toToolResult();
        }

        $taxonomy = $this->resolveTaxonomyEndpoint($args['taxonomy'] ?? 'categories');
        $endpoint = '/wp/v2/' . $taxonomy;

        return match ($action) {
            'list' => $this->listTerms($client, $endpoint, $args),
            'create' => $this->createTerm($client, $endpoint, $args),
            'get' => $this->getTerm($client, $endpoint, $args),
            'update' => $this->updateTerm($client, $endpoint, $args),
            'delete' => $this->deleteTerm($client, $endpoint, $args),
            default => ToolResult::error(sprintf('Unknown action: %s', $action)),
        };
    }

    private function resolveTaxonomyEndpoint(string $taxonomy): string
    {
        return match (strtolower(trim($taxonomy))) {
            'category', 'categories' => 'categories',
            'tag', 'tags', 'post_tag' => 'tags',
            default => $taxonomy,
        };
    }

    private function listTerms(WpRestClient $client, string $endpoint, array $args): ToolResult
    {
        $params = [];

        if (isset($args['search']) && $args['search'] !== '') {
            $params['search'] = $args['search'];
        }
        if (isset($args['parent'])) {
            $params['parent'] = (int) $args['parent'];
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

    private function createTerm(WpRestClient $client, string $endpoint, array $args): ToolResult
    {
        $name = trim((string) ($args['name'] ?? ''));
        if ($name === '') {
            return ToolResult::error('The "name" parameter is required to create a term.');
        }

        $body = ['name' => $name];

        if (isset($args['slug'])) {
            $body['slug'] = $args['slug'];
        }
        if (isset($args['description'])) {
            $body['description'] = $args['description'];
        }
        if (isset($args['parent'])) {
            $body['parent'] = (int) $args['parent'];
        }

        return $client->post($endpoint, $body)->toToolResult();
    }

    private function getTerm(WpRestClient $client, string $endpoint, array $args): ToolResult
    {
        $id = $args['id'] ?? null;
        if ($id === null) {
            return ToolResult::error('The "id" parameter is required for get.');
        }

        return $client->get(sprintf('%s/%d', $endpoint, (int) $id))->toToolResult();
    }

    private function updateTerm(WpRestClient $client, string $endpoint, array $args): ToolResult
    {
        $id = $args['id'] ?? null;
        if ($id === null) {
            return ToolResult::error('The "id" parameter is required for update.');
        }

        $body = [];

        foreach (['name', 'slug', 'description'] as $field) {
            if (isset($args[$field])) {
                $body[$field] = $args[$field];
            }
        }
        if (isset($args['parent'])) {
            $body['parent'] = (int) $args['parent'];
        }

        if ($body === []) {
            return ToolResult::error('No fields to update.');
        }

        return $client->post(sprintf('%s/%d', $endpoint, (int) $id), $body)->toToolResult();
    }

    private function deleteTerm(WpRestClient $client, string $endpoint, array $args): ToolResult
    {
        $id = $args['id'] ?? null;
        if ($id === null) {
            return ToolResult::error('The "id" parameter is required for delete.');
        }

        return $client->delete(sprintf('%s/%d', $endpoint, (int) $id))->toToolResult();
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
