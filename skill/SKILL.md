# WordPress Management Skill

## When to Use

Use this skill when the user asks you to manage WordPress sites. This includes:
- Creating, editing, or deleting posts, pages, or other content
- Managing plugins (install, activate, deactivate, update, delete)
- Managing themes (list, activate)
- Managing users (create, update roles, delete)
- Uploading or managing media/images
- Configuring site settings
- Building custom plugins or themes
- Managing navigation menus
- Checking site health or debugging
- Working with custom post types or WooCommerce
- Managing taxonomy terms (categories, tags)
- Working with blocks and widgets

## Site Setup Workflow

Before performing any WordPress operations, ensure a site is connected:

1. **Check existing sites**: `awp_sites(action: "list")`
2. **If no sites exist**, ask the user for:
   - Site URL (e.g. `https://example.com`)
   - Username (WordPress admin username)
   - Application Password (generate in WordPress: Users → Profile → Application Passwords)
3. **Add the site**: `awp_sites(action: "add", alias: "mysite", url: "...", username: "...", app_password: "...")`
4. **Test the connection**: `awp_sites(action: "test", alias: "mysite")`

### Application Passwords

WordPress Application Passwords (built into WP since 5.6) are the authentication method:
- Generated at: Users → Profile → Application Passwords
- Format: `xxxx xxxx xxxx xxxx xxxx xxxx` (spaces are fine, they get stripped)
- Each password can be named for identification
- They can be revoked individually without changing the main password

## Decision Tree: REST API vs WP-CLI

```
Is this a content/data operation? (posts, pages, users, settings, etc.)
├── YES → Use awp_* REST API tools
└── NO → Is this a server-level operation?
    ├── Database operations → WP-CLI (wp db export/import/query)
    ├── Core updates → WP-CLI (wp core update)
    ├── Config file changes → WP-CLI (wp config set)
    ├── Search-replace → WP-CLI (wp search-replace)
    ├── Cache flush → WP-CLI (wp cache flush)
    ├── Cron management → WP-CLI (wp cron)
    ├── File checksum verification → WP-CLI (wp checksum)
    └── Scaffold deploy to remote → WP-CLI or SFTP
```

**REST API tools (awp_*)** are the primary channel — they work remotely without SSH and cover ~90% of WordPress management tasks. WP-CLI (`wp_*` tools from coqui-toolkit-wp-cli if available) is a power-user supplement for server-level operations.

## Common Workflows

### Create a Blog Post
```
1. awp_content(action: "create", title: "My Post", content: "<p>Post content...</p>", status: "draft")
2. [Optional] Upload featured image: awp_media(action: "upload", file_path: "/path/to/image.jpg")
3. [Optional] Set featured image: awp_content(action: "update", id: <post_id>, featured_media: <media_id>)
4. Publish: awp_content(action: "update", id: <post_id>, status: "publish")
```

### Install and Activate a Plugin
```
1. awp_plugins(action: "install", slug: "plugin-slug")
2. awp_plugins(action: "activate", plugin: "plugin-slug/plugin-slug")
```

### Create a Custom Plugin
When users need custom functionality, scaffold a plugin:
```
1. awp_scaffold(action: "generate", type: "plugin", name: "My Custom Feature", description: "...")
2. Edit the generated files to add the custom functionality
3. Test locally or deploy via WP-CLI/upload
```

### Bulk Content Operations
```
1. List existing content: awp_content(action: "list", per_page: 100, status: "draft")
2. Process each item as needed
3. Use awp_search to find specific content across the site
```

### Manage WooCommerce (or other plugins)
Use `awp_rest` for plugin-specific endpoints:
```
awp_rest(method: "get", endpoint: "/wc/v3/products", query: '{"per_page": 10}')
awp_rest(method: "post", endpoint: "/wc/v3/products", body: '{"name": "New Product", "regular_price": "19.99"}')
```

## Multi-Site Management

All `awp_*` tools accept an optional `site` parameter:
```
awp_content(action: "list", site: "production")
awp_content(action: "list", site: "staging")
```

When `site` is omitted, the default site is used. Change the default with:
```
awp_sites(action: "set_default", alias: "production")
```

## Important Notes

- **Always test connection** before performing operations on a new or updated site.
- **Application Passwords** are the only supported auth method — do not use regular WordPress passwords.
- **The REST API requires HTTPS** in production. HTTP works for local development.
- **Some operations require administrator role** (plugin management, user creation, settings changes).
- **The `awp_rest` tool** is your escape hatch for any endpoint not covered by specialized tools.
- **Encourage plugin-based solutions** — when users need custom behavior (custom post types, shortcodes, admin pages, etc.), scaffold a plugin rather than suggesting manual code edits.
