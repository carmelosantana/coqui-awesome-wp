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

final readonly class BlockTool
{
    public function __construct(
        private SiteManager $siteManager,
        private ?HttpClientInterface $httpClient = null,
    ) {}

    public function build(): ToolInterface
    {
        return new Tool(
            name: 'awp_blocks',
            description: 'Manage WordPress block types, reusable blocks (wp_block), widget types, widgets, and sidebars.',
            parameters: [
                new EnumParameter(
                    'action',
                    'Operation to perform.',
                    values: [
                        'list_block_types', 'get_block_type',
                        'list_blocks', 'get_block', 'create_block', 'update_block', 'delete_block',
                        'list_widget_types', 'list_widgets', 'list_sidebars',
                    ],
                    required: true,
                ),
                new StringParameter(
                    'name',
                    'Block type name (namespace/name) for get_block_type.',
                    required: false,
                ),
                new NumberParameter(
                    'id',
                    'Reusable block ID for get/update/delete.',
                    required: false,
                    integer: true,
                ),
                new StringParameter(
                    'title',
                    'Block title for create/update.',
                    required: false,
                ),
                new StringParameter(
                    'content',
                    'Block content (HTML/block markup) for create/update.',
                    required: false,
                ),
                new EnumParameter(
                    'status',
                    'Block post status.',
                    values: ['publish', 'draft', 'private'],
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
            'list_block_types' => $client->get('/wp/v2/block-types')->toToolResult(),
            'get_block_type' => $this->getBlockType($client, $args),
            'list_blocks' => $client->get('/wp/v2/blocks')->toToolResult(),
            'get_block' => $this->getBlock($client, $args),
            'create_block' => $this->createBlock($client, $args),
            'update_block' => $this->updateBlock($client, $args),
            'delete_block' => $this->deleteBlock($client, $args),
            'list_widget_types' => $client->get('/wp/v2/widget-types')->toToolResult(),
            'list_widgets' => $client->get('/wp/v2/widgets')->toToolResult(),
            'list_sidebars' => $client->get('/wp/v2/sidebars')->toToolResult(),
            default => ToolResult::error(sprintf('Unknown action: %s', $action)),
        };
    }

    private function getBlockType(WpRestClient $client, array $args): ToolResult
    {
        $name = trim((string) ($args['name'] ?? ''));
        if ($name === '') {
            return ToolResult::error('The "name" parameter is required (e.g. "core/paragraph").');
        }

        return $client->get(sprintf('/wp/v2/block-types/%s', rawurlencode($name)))->toToolResult();
    }

    private function getBlock(WpRestClient $client, array $args): ToolResult
    {
        $id = $args['id'] ?? null;
        if ($id === null) {
            return ToolResult::error('The "id" parameter is required for get_block.');
        }

        return $client->get(sprintf('/wp/v2/blocks/%d', (int) $id))->toToolResult();
    }

    private function createBlock(WpRestClient $client, array $args): ToolResult
    {
        $title = trim((string) ($args['title'] ?? ''));
        if ($title === '') {
            return ToolResult::error('The "title" parameter is required to create a reusable block.');
        }

        $body = ['title' => $title];

        if (isset($args['content'])) {
            $body['content'] = $args['content'];
        }
        if (isset($args['status'])) {
            $body['status'] = $args['status'];
        }

        return $client->post('/wp/v2/blocks', $body)->toToolResult();
    }

    private function updateBlock(WpRestClient $client, array $args): ToolResult
    {
        $id = $args['id'] ?? null;
        if ($id === null) {
            return ToolResult::error('The "id" parameter is required for update_block.');
        }

        $body = [];

        foreach (['title', 'content', 'status'] as $field) {
            if (isset($args[$field])) {
                $body[$field] = $args[$field];
            }
        }

        if ($body === []) {
            return ToolResult::error('No fields to update.');
        }

        return $client->post(sprintf('/wp/v2/blocks/%d', (int) $id), $body)->toToolResult();
    }

    private function deleteBlock(WpRestClient $client, array $args): ToolResult
    {
        $id = $args['id'] ?? null;
        if ($id === null) {
            return ToolResult::error('The "id" parameter is required for delete_block.');
        }

        return $client->delete(sprintf('/wp/v2/blocks/%d', (int) $id))->toToolResult();
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
