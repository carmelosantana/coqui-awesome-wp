# Awesome WP — WordPress Management Toolkit for Coqui

A comprehensive WordPress management toolkit for [Coqui Bot](https://github.com/carmelosantana/coqui) that provides full REST API coverage, multi-site connection management, and plugin/theme scaffolding.

## Features

- **15 specialized tools** covering the full WordPress REST API surface
- **Multi-site management** — add, switch between, and manage multiple WordPress sites
- **Plugin & theme scaffolding** — generate boilerplate with proper headers and hooks
- **Raw REST API access** — escape hatch for custom post types, WooCommerce, and third-party plugins
- **Secure credential handling** — application passwords stored in `.env` with hot-reload
- **WP-CLI integration** — optional power-user features via `coqui-toolkit-wp-cli`

## Requirements

- PHP 8.4+
- Coqui Bot with `carmelosantana/php-agents` ^0.7
- WordPress 5.6+ (for Application Passwords support)

## Installation

Install via Composer in your Coqui workspace:

```bash
composer require carmelosantana/coqui-awesome-wp
```

The toolkit is auto-discovered by Coqui on next boot — no manual registration needed.

## Authentication

This toolkit uses **WordPress Application Passwords** for authentication:

1. Log into your WordPress admin
2. Go to **Users → Profile**
3. Scroll to **Application Passwords**
4. Enter a name (e.g., "Coqui Bot") and click **Add New Application Password**
5. Copy the generated password

Then add the site to Coqui:

```
awp_sites(action: "add", alias: "mysite", url: "https://example.com", username: "admin", app_password: "xxxx xxxx xxxx xxxx xxxx xxxx")
```

## Tools Reference

| Tool | Description | Key Actions |
|------|-------------|-------------|
| `awp_sites` | Site connection management | add, list, get, remove, test, set_default |
| `awp_content` | Posts & pages CRUD | list, create, update, delete, revisions |
| `awp_media` | Media library management | list, upload, get, update, delete |
| `awp_users` | User management | list, create, get, update, delete, me |
| `awp_comments` | Comment management | list, create, get, update, delete |
| `awp_taxonomy` | Categories, tags, custom taxonomies | list, create, get, update, delete |
| `awp_plugins` | Plugin management | list, get, install, activate, deactivate, delete |
| `awp_themes` | Theme management | list, get, activate |
| `awp_settings` | Site settings | get, update |
| `awp_menus` | Navigation menus | list_menus, create_item, update_item, delete_item |
| `awp_blocks` | Blocks, widgets, sidebars | list_block_types, create_block, list_widgets |
| `awp_search` | Cross-content search | Full-text search across posts, pages, terms |
| `awp_health` | Site health diagnostics | status, tests, directory_sizes |
| `awp_rest` | Raw REST API requests | Any HTTP method to any endpoint |
| `awp_scaffold` | Plugin/theme scaffolding | generate, deploy |

## Multi-Site Usage

Add multiple sites and target any of them:

```
awp_sites(action: "add", alias: "production", url: "https://example.com", ...)
awp_sites(action: "add", alias: "staging", url: "https://staging.example.com", ...)

# Target production
awp_content(action: "list", site: "production")

# Target staging
awp_content(action: "list", site: "staging")

# Change default
awp_sites(action: "set_default", alias: "staging")
```

## Scaffolding

Generate WordPress plugin or theme boilerplate:

```
awp_scaffold(action: "generate", type: "plugin", name: "My Custom Plugin", description: "Does amazing things")
awp_scaffold(action: "generate", type: "theme", name: "My Custom Theme", author: "Carmelo")
```

Generated files include proper headers, activation hooks, uninstall scripts, and standard directory structure.

## Gated Operations

The following destructive operations require user confirmation (unless `--auto-approve` is set):

- `awp_plugins`: install, activate, deactivate, delete, update
- `awp_themes`: activate
- `awp_users`: create, delete
- `awp_settings`: update
- `awp_comments`: delete
- `awp_rest`: post, put, patch, delete
- `awp_scaffold`: deploy
- `awp_sites`: remove

## Credential Storage

Site credentials are managed automatically:
- **Site metadata** (URL, alias, SSH config) stored in `workspace/data/wp-sites.json`
- **Application passwords** stored as `AWP_{ALIAS}_APP_PASSWORD` in workspace `.env`
- Hot-reload via `putenv()` — no restart needed after adding credentials

Optional default credentials in `composer.json`:
- `AWP_DEFAULT_URL` — default site URL
- `AWP_DEFAULT_USERNAME` — default username
- `AWP_DEFAULT_APP_PASSWORD` — default application password

## WP-CLI Integration

When `coquibot/coqui-toolkit-wp-cli` is installed, additional server-level operations become available:
- Database export/import
- Core updates and checksum verification
- wp-config.php management
- Search-replace across the database
- Cache and cron management
- Scaffold deployment to remote servers

WP-CLI is a `suggest` dependency — the toolkit works fully without it.

## License

MIT
