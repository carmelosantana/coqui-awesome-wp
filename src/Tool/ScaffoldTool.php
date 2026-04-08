<?php

declare(strict_types=1);

namespace CarmeloSantana\CoquiAwesomeWp\Tool;

use CarmeloSantana\CoquiAwesomeWp\Scaffold\PluginScaffolder;
use CarmeloSantana\PHPAgents\Contract\ToolInterface;
use CarmeloSantana\PHPAgents\Tool\Tool;
use CarmeloSantana\PHPAgents\Tool\ToolResult;
use CarmeloSantana\PHPAgents\Tool\Parameter\BoolParameter;
use CarmeloSantana\PHPAgents\Tool\Parameter\EnumParameter;
use CarmeloSantana\PHPAgents\Tool\Parameter\StringParameter;

final readonly class ScaffoldTool
{
    public function __construct(
        private string $outputBasePath,
    ) {}

    public function build(): ToolInterface
    {
        return new Tool(
            name: 'awp_scaffold',
            description: 'Scaffold WordPress plugins and themes — generate boilerplate with proper headers, activation hooks, and standard directory structure. Use "generate" to create files locally, "deploy" to deploy via WP-CLI (if available).',
            parameters: [
                new EnumParameter(
                    'action',
                    'Operation to perform.',
                    values: ['generate', 'deploy'],
                    required: true,
                ),
                new EnumParameter(
                    'type',
                    'What to scaffold.',
                    values: ['plugin', 'theme'],
                    required: true,
                ),
                new StringParameter(
                    'name',
                    'Human-readable name (e.g. "My Custom Plugin").',
                    required: true,
                ),
                new StringParameter(
                    'slug',
                    'URL/directory slug (e.g. "my-custom-plugin"). Auto-generated from name if omitted.',
                    required: false,
                ),
                new StringParameter(
                    'description',
                    'Short description.',
                    required: false,
                ),
                new StringParameter(
                    'author',
                    'Author name.',
                    required: false,
                ),
                new StringParameter(
                    'version',
                    'Initial version. Default: 1.0.0.',
                    required: false,
                ),
                new StringParameter(
                    'php_min',
                    'Minimum PHP version. Default: 8.0.',
                    required: false,
                ),
                new StringParameter(
                    'wp_min',
                    'Minimum WordPress version. Default: 6.0.',
                    required: false,
                ),
                new BoolParameter(
                    'include_uninstall',
                    'Include uninstall.php (plugins only). Default: true.',
                    required: false,
                ),
            ],
            callback: fn(array $args) => $this->execute($args),
        );
    }

    private function execute(array $args): ToolResult
    {
        $action = trim((string) ($args['action'] ?? ''));
        $type = trim((string) ($args['type'] ?? ''));
        $name = trim((string) ($args['name'] ?? ''));

        if ($name === '') {
            return ToolResult::error('The "name" parameter is required.');
        }

        $slug = trim((string) ($args['slug'] ?? ''));
        if ($slug === '') {
            $slug = $this->slugify($name);
        }

        return match ($action) {
            'generate' => $this->generate($type, $name, $slug, $args),
            'deploy' => ToolResult::error('Deploy requires WP-CLI access. Use the wp_cli toolkit with: wp plugin install <path> or manually upload the generated files.'),
            default => ToolResult::error(sprintf('Unknown action: %s', $action)),
        };
    }

    private function generate(string $type, string $name, string $slug, array $args): ToolResult
    {
        $scaffolder = new PluginScaffolder();
        $description = trim((string) ($args['description'] ?? ''));
        $author = trim((string) ($args['author'] ?? ''));
        $version = trim((string) ($args['version'] ?? '1.0.0'));
        $phpMin = trim((string) ($args['php_min'] ?? '8.0'));
        $wpMin = trim((string) ($args['wp_min'] ?? '6.0'));

        $files = match ($type) {
            'plugin' => $scaffolder->generate(
                name: $name,
                slug: $slug,
                description: $description,
                author: $author,
                version: $version,
                phpMinVersion: $phpMin,
                wpMinVersion: $wpMin,
                includeUninstall: (bool) ($args['include_uninstall'] ?? true),
            ),
            'theme' => $scaffolder->generateTheme(
                name: $name,
                slug: $slug,
                description: $description,
                author: $author,
                version: $version,
                phpMinVersion: $phpMin,
                wpMinVersion: $wpMin,
            ),
            default => null,
        };

        if ($files === null) {
            return ToolResult::error(sprintf('Unknown type: %s. Use "plugin" or "theme".', $type));
        }

        $created = [];
        $basePath = rtrim($this->outputBasePath, '/');

        foreach ($files as $relativePath => $content) {
            $fullPath = $basePath . '/' . $relativePath;
            $dir = dirname($fullPath);

            if (!is_dir($dir) && !mkdir($dir, 0755, true)) {
                return ToolResult::error(sprintf('Failed to create directory: %s', $dir));
            }

            if (file_put_contents($fullPath, $content) === false) {
                return ToolResult::error(sprintf('Failed to write file: %s', $fullPath));
            }

            $created[] = $relativePath;
        }

        return ToolResult::success(sprintf(
            "Scaffolded %s \"%s\" with %d files at: %s/%s\n\nFiles created:\n%s",
            $type,
            $name,
            count($created),
            $basePath,
            $slug,
            implode("\n", array_map(fn(string $f) => "  - {$f}", $created)),
        ));
    }

    private function slugify(string $text): string
    {
        $slug = strtolower(trim($text));
        $slug = preg_replace('/[^a-z0-9\-]/', '-', $slug) ?? $slug;
        $slug = preg_replace('/-+/', '-', $slug) ?? $slug;

        return trim($slug, '-');
    }
}
