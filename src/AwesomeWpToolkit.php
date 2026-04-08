<?php

declare(strict_types=1);

namespace CarmeloSantana\CoquiAwesomeWp;

use CarmeloSantana\CoquiAwesomeWp\Storage\SiteManager;
use CarmeloSantana\CoquiAwesomeWp\Tool\BlockTool;
use CarmeloSantana\CoquiAwesomeWp\Tool\CommentTool;
use CarmeloSantana\CoquiAwesomeWp\Tool\ContentTool;
use CarmeloSantana\CoquiAwesomeWp\Tool\HealthTool;
use CarmeloSantana\CoquiAwesomeWp\Tool\MediaTool;
use CarmeloSantana\CoquiAwesomeWp\Tool\MenuTool;
use CarmeloSantana\CoquiAwesomeWp\Tool\PluginTool;
use CarmeloSantana\CoquiAwesomeWp\Tool\RestApiTool;
use CarmeloSantana\CoquiAwesomeWp\Tool\ScaffoldTool;
use CarmeloSantana\CoquiAwesomeWp\Tool\SearchTool;
use CarmeloSantana\CoquiAwesomeWp\Tool\SettingsTool;
use CarmeloSantana\CoquiAwesomeWp\Tool\SiteConnectionTool;
use CarmeloSantana\CoquiAwesomeWp\Tool\ThemeTool;
use CarmeloSantana\CoquiAwesomeWp\Tool\TaxonomyTool;
use CarmeloSantana\CoquiAwesomeWp\Tool\UserTool;
use CarmeloSantana\PHPAgents\Contract\ToolInterface;
use CarmeloSantana\PHPAgents\Contract\ToolkitInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class AwesomeWpToolkit implements ToolkitInterface
{
    private SiteManager $siteManager;

    public function __construct(
        private readonly string $storagePath,
        private readonly string $scaffoldOutputPath,
        private readonly ?HttpClientInterface $httpClient = null,
    ) {
        $this->siteManager = new SiteManager($this->storagePath);
    }

    public static function fromEnv(string $workspacePath): self
    {
        $dataPath = rtrim($workspacePath, '/') . '/data';
        $scaffoldPath = rtrim($workspacePath, '/') . '/scaffold';

        return new self(
            storagePath: $dataPath,
            scaffoldOutputPath: $scaffoldPath,
        );
    }

    /**
     * @return ToolInterface[]
     */
    public function tools(): array
    {
        return [
            (new SiteConnectionTool($this->siteManager, $this->httpClient))->build(),
            (new ContentTool($this->siteManager, $this->httpClient))->build(),
            (new MediaTool($this->siteManager, $this->httpClient))->build(),
            (new UserTool($this->siteManager, $this->httpClient))->build(),
            (new CommentTool($this->siteManager, $this->httpClient))->build(),
            (new TaxonomyTool($this->siteManager, $this->httpClient))->build(),
            (new PluginTool($this->siteManager, $this->httpClient))->build(),
            (new ThemeTool($this->siteManager, $this->httpClient))->build(),
            (new SettingsTool($this->siteManager, $this->httpClient))->build(),
            (new MenuTool($this->siteManager, $this->httpClient))->build(),
            (new BlockTool($this->siteManager, $this->httpClient))->build(),
            (new SearchTool($this->siteManager, $this->httpClient))->build(),
            (new HealthTool($this->siteManager, $this->httpClient))->build(),
            (new RestApiTool($this->siteManager, $this->httpClient))->build(),
            (new ScaffoldTool($this->scaffoldOutputPath))->build(),
        ];
    }

    public function guidelines(): string
    {
        $siteCount = $this->siteManager->count();
        $defaultAlias = $this->siteManager->getDefaultAlias();

        $header = "## WordPress Management (Awesome WP)\n\n";

        if ($siteCount === 0) {
            $header .= "**No WordPress sites configured.** Add a site first:\n";
            $header .= "```\nawp_sites(action: \"add\", alias: \"mysite\", url: \"https://example.com\", username: \"admin\", app_password: \"xxxx xxxx xxxx xxxx\")\n```\n\n";
        } else {
            $header .= sprintf("**%d site(s) configured.** Default: `%s`\n\n", $siteCount, $defaultAlias !== '' ? $defaultAlias : 'none');
        }

        $header .= <<<'GUIDE'
### Tool Selection Guide

| Task | Tool | Key Actions |
|------|------|-------------|
| Site connections | `awp_sites` | add, list, get, remove, test |
| Posts & pages | `awp_content` | list, create, update, delete, revisions |
| Images & files | `awp_media` | list, upload, get, update, delete |
| Users | `awp_users` | list, create, get, update, delete, me |
| Comments | `awp_comments` | list, create, get, update, delete |
| Categories & tags | `awp_taxonomy` | list, create, get, update, delete |
| Plugins | `awp_plugins` | list, get, install, activate, deactivate, delete |
| Themes | `awp_themes` | list, get, activate |
| Site settings | `awp_settings` | get, update |
| Navigation menus | `awp_menus` | list_menus, create_item, update_item, delete_item |
| Blocks & widgets | `awp_blocks` | list_block_types, list_blocks, create_block |
| Content search | `awp_search` | Full-text search across all content |
| Site health | `awp_health` | status, tests, directory_sizes |
| Custom endpoints | `awp_rest` | Raw GET/POST/PUT/PATCH/DELETE to any REST endpoint |
| Scaffolding | `awp_scaffold` | Generate plugin/theme boilerplate |

### Workflow Patterns

- **Always test connection first** with `awp_sites(action: "test")` before operations.
- **Use `awp_content` for posts/pages**, not `awp_rest` — it handles type routing automatically.
- **Use `awp_rest` for custom post types** or WooCommerce/third-party plugin endpoints.
- **Scaffold custom plugins** when the user needs custom functionality — encourage plugin-based solutions.
- **Multi-site**: Pass `site: "alias"` to any tool. Omit to use the default site.
GUIDE;

        return $header;
    }
}
