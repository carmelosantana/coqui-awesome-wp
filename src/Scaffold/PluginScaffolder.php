<?php

declare(strict_types=1);

namespace CarmeloSantana\CoquiAwesomeWp\Scaffold;

final readonly class PluginScaffolder
{
    public function generate(
        string $name,
        string $slug,
        string $description = '',
        string $author = '',
        string $version = '1.0.0',
        string $phpMinVersion = '8.0',
        string $wpMinVersion = '6.0',
        bool $includeUninstall = true,
    ): array {
        $className = $this->toClassName($slug);

        $files = [];

        $files["{$slug}/{$slug}.php"] = $this->mainPluginFile(
            $name,
            $slug,
            $description,
            $author,
            $version,
            $phpMinVersion,
            $wpMinVersion,
            $className,
        );

        $files["{$slug}/readme.txt"] = $this->readmeTxt(
            $name,
            $description,
            $author,
            $version,
            $phpMinVersion,
            $wpMinVersion,
        );

        if ($includeUninstall) {
            $files["{$slug}/uninstall.php"] = $this->uninstallFile($slug);
        }

        $files["{$slug}/includes/.gitkeep"] = '';
        $files["{$slug}/assets/css/.gitkeep"] = '';
        $files["{$slug}/assets/js/.gitkeep"] = '';
        $files["{$slug}/languages/.gitkeep"] = '';

        return $files;
    }

    public function generateTheme(
        string $name,
        string $slug,
        string $description = '',
        string $author = '',
        string $version = '1.0.0',
        string $phpMinVersion = '8.0',
        string $wpMinVersion = '6.0',
    ): array {
        $files = [];

        $files["{$slug}/style.css"] = $this->themeStyleCss(
            $name, $slug, $description, $author, $version, $phpMinVersion, $wpMinVersion,
        );

        $files["{$slug}/functions.php"] = $this->themeFunctionsPhp($name, $slug);
        $files["{$slug}/index.php"] = $this->themeIndexPhp($name);
        $files["{$slug}/templates/.gitkeep"] = '';
        $files["{$slug}/parts/.gitkeep"] = '';
        $files["{$slug}/assets/css/.gitkeep"] = '';
        $files["{$slug}/assets/js/.gitkeep"] = '';

        return $files;
    }

    private function mainPluginFile(
        string $name,
        string $slug,
        string $description,
        string $author,
        string $version,
        string $phpMinVersion,
        string $wpMinVersion,
        string $className,
    ): string {
        $header = <<<PHP
<?php
/**
 * Plugin Name: {$name}
 * Plugin URI:
 * Description: {$description}
 * Version: {$version}
 * Author: {$author}
 * Author URI:
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: {$slug}
 * Domain Path: /languages
 * Requires at least: {$wpMinVersion}
 * Requires PHP: {$phpMinVersion}
 */

declare(strict_types=1);

// Prevent direct access.
if (!defined('ABSPATH')) {
    exit;
}

// Plugin constants.
define('{$this->toConstantPrefix($slug)}_VERSION', '{$version}');
define('{$this->toConstantPrefix($slug)}_PATH', plugin_dir_path(__FILE__));
define('{$this->toConstantPrefix($slug)}_URL', plugin_dir_url(__FILE__));

/**
 * Main plugin class.
 */
final class {$className} {
    private static ?self \$instance = null;

    public static function instance(): self {
        if (self::\$instance === null) {
            self::\$instance = new self();
        }
        return self::\$instance;
    }

    private function __construct() {
        add_action('init', [\$this, 'init']);
        add_action('admin_init', [\$this, 'adminInit']);
    }

    public function init(): void {
        load_plugin_textdomain('{$slug}', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    public function adminInit(): void {
        // Admin initialization hooks.
    }
}

// Boot the plugin.
add_action('plugins_loaded', function (): void {
    {$className}::instance();
});

// Activation hook.
register_activation_hook(__FILE__, function (): void {
    // Run on plugin activation.
    flush_rewrite_rules();
});

// Deactivation hook.
register_deactivation_hook(__FILE__, function (): void {
    // Run on plugin deactivation.
    flush_rewrite_rules();
});
PHP;

        return $header;
    }

    private function readmeTxt(
        string $name,
        string $description,
        string $author,
        string $version,
        string $phpMinVersion,
        string $wpMinVersion,
    ): string {
        return <<<TXT
=== {$name} ===
Contributors: {$author}
Tags:
Requires at least: {$wpMinVersion}
Tested up to: 6.7
Stable tag: {$version}
Requires PHP: {$phpMinVersion}
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

{$description}

== Description ==

{$description}

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' screen in WordPress.

== Changelog ==

= {$version} =
* Initial release.
TXT;
    }

    private function uninstallFile(string $slug): string
    {
        return <<<PHP
<?php
/**
 * Uninstall script for {$slug}.
 *
 * Runs when the plugin is deleted via the WordPress admin.
 */

// Prevent direct access.
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Clean up plugin options, transients, custom tables, etc.
// delete_option('{$slug}_settings');
PHP;
    }

    private function themeStyleCss(
        string $name,
        string $slug,
        string $description,
        string $author,
        string $version,
        string $phpMinVersion,
        string $wpMinVersion,
    ): string {
        return <<<CSS
/*
Theme Name: {$name}
Theme URI:
Author: {$author}
Author URI:
Description: {$description}
Version: {$version}
Requires at least: {$wpMinVersion}
Tested up to: 6.7
Requires PHP: {$phpMinVersion}
License: GNU General Public License v2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: {$slug}
Tags:
*/
CSS;
    }

    private function themeFunctionsPhp(string $name, string $slug): string
    {
        return <<<PHP
<?php
/**
 * {$name} theme functions.
 */

declare(strict_types=1);

// Prevent direct access.
if (!defined('ABSPATH')) {
    exit;
}

add_action('after_setup_theme', function (): void {
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('automatic-feed-links');
    add_theme_support('html5', [
        'search-form',
        'comment-form',
        'comment-list',
        'gallery',
        'caption',
        'style',
        'script',
    ]);
    add_theme_support('wp-block-template-parts');

    register_nav_menus([
        'primary' => esc_html__('{$name} Primary Menu', '{$slug}'),
    ]);
});

add_action('wp_enqueue_scripts', function (): void {
    wp_enqueue_style('{$slug}-style', get_stylesheet_uri(), [], wp_get_theme()->get('Version'));
});
PHP;
    }

    private function themeIndexPhp(string $name): string
    {
        return <<<'PHP'
<?php
/**
 * Main template file.
 */

get_header();
?>

<main id="primary" class="site-main">
    <?php
    if (have_posts()) {
        while (have_posts()) {
            the_post();
            the_title('<h2>', '</h2>');
            the_content();
        }
        the_posts_navigation();
    } else {
        echo '<p>No content found.</p>';
    }
    ?>
</main>

<?php
get_footer();
PHP;
    }

    private function toClassName(string $slug): string
    {
        return str_replace(' ', '', ucwords(str_replace('-', ' ', $slug)));
    }

    private function toConstantPrefix(string $slug): string
    {
        return strtoupper(str_replace('-', '_', $slug));
    }
}
