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

final readonly class MediaTool
{
    public function __construct(
        private SiteManager $siteManager,
        private ?HttpClientInterface $httpClient = null,
    ) {}

    public function build(): ToolInterface
    {
        return new Tool(
            name: 'awp_media',
            description: 'Manage WordPress media library — upload, list, get details, update metadata, and delete media items.',
            parameters: [
                new EnumParameter(
                    'action',
                    'Operation to perform.',
                    values: ['list', 'upload', 'get', 'update', 'delete'],
                    required: true,
                ),
                new NumberParameter(
                    'id',
                    'Media item ID. Required for get, update, delete.',
                    required: false,
                    integer: true,
                ),
                new StringParameter(
                    'file_path',
                    'Absolute path to file for upload action.',
                    required: false,
                ),
                new StringParameter(
                    'title',
                    'Media title.',
                    required: false,
                ),
                new StringParameter(
                    'caption',
                    'Media caption.',
                    required: false,
                ),
                new StringParameter(
                    'alt_text',
                    'Alternative text for accessibility.',
                    required: false,
                ),
                new StringParameter(
                    'description',
                    'Media description.',
                    required: false,
                ),
                new EnumParameter(
                    'media_type',
                    'Filter by media type for list action.',
                    values: ['image', 'video', 'audio', 'application'],
                    required: false,
                ),
                new StringParameter(
                    'search',
                    'Search term for list action.',
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
            'list' => $this->listMedia($client, $args),
            'upload' => $this->uploadMedia($client, $args),
            'get' => $this->getMedia($client, $args),
            'update' => $this->updateMedia($client, $args),
            'delete' => $this->deleteMedia($client, $args),
            default => ToolResult::error(sprintf('Unknown action: %s', $action)),
        };
    }

    private function listMedia(WpRestClient $client, array $args): ToolResult
    {
        $params = [];

        if (isset($args['media_type'])) {
            $params['media_type'] = $args['media_type'];
        }
        if (isset($args['search']) && $args['search'] !== '') {
            $params['search'] = $args['search'];
        }
        if (isset($args['per_page'])) {
            $params['per_page'] = (int) $args['per_page'];
        }
        if (isset($args['page'])) {
            $params['page'] = (int) $args['page'];
        }

        return $client->get('/wp/v2/media', $params)->toToolResult();
    }

    private function uploadMedia(WpRestClient $client, array $args): ToolResult
    {
        $filePath = trim((string) ($args['file_path'] ?? ''));

        if ($filePath === '') {
            return ToolResult::error('The "file_path" parameter is required for upload.');
        }

        if (!file_exists($filePath) || !is_readable($filePath)) {
            return ToolResult::error(sprintf('File not found or not readable: %s', $filePath));
        }

        $result = $client->upload('/wp/v2/media', $filePath);

        if ($result->succeeded() && !empty($args['title'] ?? $args['caption'] ?? $args['alt_text'] ?? $args['description'])) {
            $body = $result->body;
            $mediaId = $body['id'] ?? null;

            if ($mediaId !== null) {
                $update = [];
                if (isset($args['title'])) {
                    $update['title'] = $args['title'];
                }
                if (isset($args['caption'])) {
                    $update['caption'] = $args['caption'];
                }
                if (isset($args['alt_text'])) {
                    $update['alt_text'] = $args['alt_text'];
                }
                if (isset($args['description'])) {
                    $update['description'] = $args['description'];
                }

                return $client->post(sprintf('/wp/v2/media/%d', (int) $mediaId), $update)->toToolResult();
            }
        }

        return $result->toToolResult();
    }

    private function getMedia(WpRestClient $client, array $args): ToolResult
    {
        $id = $args['id'] ?? null;
        if ($id === null) {
            return ToolResult::error('The "id" parameter is required for get.');
        }

        return $client->get(sprintf('/wp/v2/media/%d', (int) $id))->toToolResult();
    }

    private function updateMedia(WpRestClient $client, array $args): ToolResult
    {
        $id = $args['id'] ?? null;
        if ($id === null) {
            return ToolResult::error('The "id" parameter is required for update.');
        }

        $body = [];

        if (isset($args['title'])) {
            $body['title'] = $args['title'];
        }
        if (isset($args['caption'])) {
            $body['caption'] = $args['caption'];
        }
        if (isset($args['alt_text'])) {
            $body['alt_text'] = $args['alt_text'];
        }
        if (isset($args['description'])) {
            $body['description'] = $args['description'];
        }

        if ($body === []) {
            return ToolResult::error('No fields to update.');
        }

        return $client->post(sprintf('/wp/v2/media/%d', (int) $id), $body)->toToolResult();
    }

    private function deleteMedia(WpRestClient $client, array $args): ToolResult
    {
        $id = $args['id'] ?? null;
        if ($id === null) {
            return ToolResult::error('The "id" parameter is required for delete.');
        }

        return $client->delete(sprintf('/wp/v2/media/%d', (int) $id))->toToolResult();
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
