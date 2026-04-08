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

final readonly class MenuTool
{
    public function __construct(
        private SiteManager $siteManager,
        private ?HttpClientInterface $httpClient = null,
    ) {}

    public function build(): ToolInterface
    {
        return new Tool(
            name: 'awp_menus',
            description: 'Manage WordPress navigation menus and menu items — list menus, get locations, create/update/delete menu items.',
            parameters: [
                new EnumParameter(
                    'action',
                    'Operation to perform.',
                    values: ['list_menus', 'get_menu', 'list_items', 'create_item', 'update_item', 'delete_item', 'list_locations'],
                    required: true,
                ),
                new NumberParameter(
                    'id',
                    'Menu ID (for get_menu, list_items) or menu item ID (for update_item, delete_item).',
                    required: false,
                    integer: true,
                ),
                new NumberParameter(
                    'menu_id',
                    'Parent menu ID for creating menu items.',
                    required: false,
                    integer: true,
                ),
                new StringParameter(
                    'title',
                    'Menu item title.',
                    required: false,
                ),
                new StringParameter(
                    'url',
                    'Menu item URL (for "custom" type).',
                    required: false,
                ),
                new EnumParameter(
                    'type',
                    'Menu item type.',
                    values: ['custom', 'post_type', 'taxonomy'],
                    required: false,
                ),
                new NumberParameter(
                    'object_id',
                    'The ID of the object (post, page, term) the menu item points to.',
                    required: false,
                    integer: true,
                ),
                new StringParameter(
                    'object',
                    'Object type slug (e.g. "post", "page", "category").',
                    required: false,
                ),
                new NumberParameter(
                    'parent',
                    'Parent menu item ID for nesting.',
                    required: false,
                    integer: true,
                ),
                new NumberParameter(
                    'menu_order',
                    'Position in the menu.',
                    required: false,
                    integer: true,
                ),
                new EnumParameter(
                    'target',
                    'Link target.',
                    values: ['_blank', '_self'],
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
            'list_menus' => $client->get('/wp/v2/menus')->toToolResult(),
            'get_menu' => $this->getMenu($client, $args),
            'list_items' => $this->listItems($client, $args),
            'create_item' => $this->createItem($client, $args),
            'update_item' => $this->updateItem($client, $args),
            'delete_item' => $this->deleteItem($client, $args),
            'list_locations' => $client->get('/wp/v2/menu-locations')->toToolResult(),
            default => ToolResult::error(sprintf('Unknown action: %s', $action)),
        };
    }

    private function getMenu(WpRestClient $client, array $args): ToolResult
    {
        $id = $args['id'] ?? null;
        if ($id === null) {
            return ToolResult::error('The "id" parameter is required for get_menu.');
        }

        return $client->get(sprintf('/wp/v2/menus/%d', (int) $id))->toToolResult();
    }

    private function listItems(WpRestClient $client, array $args): ToolResult
    {
        $params = [];

        if (isset($args['id'])) {
            $params['menus'] = (int) $args['id'];
        }

        return $client->get('/wp/v2/menu-items', $params)->toToolResult();
    }

    private function createItem(WpRestClient $client, array $args): ToolResult
    {
        $title = trim((string) ($args['title'] ?? ''));
        if ($title === '') {
            return ToolResult::error('The "title" parameter is required to create a menu item.');
        }

        $body = ['title' => $title];

        if (isset($args['menu_id'])) {
            $body['menus'] = (int) $args['menu_id'];
        }
        if (isset($args['url'])) {
            $body['url'] = $args['url'];
        }
        if (isset($args['type'])) {
            $body['type'] = $args['type'];
        }
        if (isset($args['object_id'])) {
            $body['object_id'] = (int) $args['object_id'];
        }
        if (isset($args['object'])) {
            $body['object'] = $args['object'];
        }
        if (isset($args['parent'])) {
            $body['parent'] = (int) $args['parent'];
        }
        if (isset($args['menu_order'])) {
            $body['menu_order'] = (int) $args['menu_order'];
        }
        if (isset($args['target'])) {
            $body['target'] = $args['target'];
        }

        return $client->post('/wp/v2/menu-items', $body)->toToolResult();
    }

    private function updateItem(WpRestClient $client, array $args): ToolResult
    {
        $id = $args['id'] ?? null;
        if ($id === null) {
            return ToolResult::error('The "id" parameter is required for update_item.');
        }

        $body = [];

        foreach (['title', 'url', 'type', 'object'] as $field) {
            if (isset($args[$field])) {
                $body[$field] = $args[$field];
            }
        }
        if (isset($args['object_id'])) {
            $body['object_id'] = (int) $args['object_id'];
        }
        if (isset($args['parent'])) {
            $body['parent'] = (int) $args['parent'];
        }
        if (isset($args['menu_order'])) {
            $body['menu_order'] = (int) $args['menu_order'];
        }
        if (isset($args['target'])) {
            $body['target'] = $args['target'];
        }

        if ($body === []) {
            return ToolResult::error('No fields to update.');
        }

        return $client->post(sprintf('/wp/v2/menu-items/%d', (int) $id), $body)->toToolResult();
    }

    private function deleteItem(WpRestClient $client, array $args): ToolResult
    {
        $id = $args['id'] ?? null;
        if ($id === null) {
            return ToolResult::error('The "id" parameter is required for delete_item.');
        }

        return $client->delete(sprintf('/wp/v2/menu-items/%d', (int) $id))->toToolResult();
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
