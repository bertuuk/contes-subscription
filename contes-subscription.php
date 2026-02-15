<?php
/**
 * Plugin Name: Contes Subscription
 * Description: Site-specific subscription logic for managing roles, memberships, and integrations with other plugins.
 * Version: 1.2.1
 * Author: Berta Nicolau
 * Text Domain: contes-subscription
 * Domain Path: /languages
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Define plugin constants
define('CONTES_SUBSCRIPTION_VERSION', '1.1.7');
define('CONTES_SUBSCRIPTION_PATH', plugin_dir_path(__FILE__));
define('CONTES_SUBSCRIPTION_URL', plugin_dir_url(__FILE__));

// Load text domain for translations
function contes_subscription_load_textdomain() {
    load_plugin_textdomain('contes-subscription', false, dirname(plugin_basename(__FILE__)) . '/languages');
}
add_action('plugins_loaded', 'contes_subscription_load_textdomain');

// Include required files
require_once CONTES_SUBSCRIPTION_PATH . 'includes/helpers.php';
require_once CONTES_SUBSCRIPTION_PATH . 'includes/settings.php';
require_once CONTES_SUBSCRIPTION_PATH . 'includes/roles.php';
require_once CONTES_SUBSCRIPTION_PATH . 'includes/favorites.php';
require_once CONTES_SUBSCRIPTION_PATH . 'includes/post-views.php';
require_once CONTES_SUBSCRIPTION_PATH . 'includes/month-post-views.php';
require_once CONTES_SUBSCRIPTION_PATH . 'includes/story-values.php';
require_once CONTES_SUBSCRIPTION_PATH . 'includes/visibility-teaser-block.php';
require_once CONTES_SUBSCRIPTION_PATH . 'includes/post-access.php';
require_once CONTES_SUBSCRIPTION_PATH . 'includes/post-grid-access.php';
require_once CONTES_SUBSCRIPTION_PATH . 'includes/locked-template.php';

// Initialize components
add_action('plugins_loaded', function () {
    new Contes_Subscription_Settings();
    new Contes_Subscription_Roles();
});

function contes_subscription_activate() {
    contes_subscription_create_locked_template();
}
register_activation_hook(__FILE__, 'contes_subscription_activate');

function contes_subscription_deactivate() {
    contes_subscription_delete_locked_templates();
}
register_deactivation_hook(__FILE__, 'contes_subscription_deactivate');

function contes_subscription_assets() {
    $handle = 'contes-subscription-icon-styles';
    $src = plugins_url('build/icons.bundle.css', __FILE__);
    $ver = filemtime(plugin_dir_path(__FILE__) . 'build/icons.bundle.css');

    wp_register_style($handle, $src, [], $ver);
    wp_enqueue_style($handle);

    add_filter('style_loader_tag', function ($html, $style_handle, $href, $media) use ($handle) {
        if ($style_handle !== $handle) {
            return $html;
        }

        $preload = sprintf(
            '<link rel="preload" as="style" href="%s" />',
            esc_url($href)
        );
        $stylesheet = sprintf(
            '<link rel="stylesheet" href="%s" media="print" onload="this.media=\'all\'" />',
            esc_url($href)
        );
        $noscript = sprintf(
            '<noscript><link rel="stylesheet" href="%s" /></noscript>',
            esc_url($href)
        );

        return $preload . $stylesheet . $noscript;
    }, 10, 4);
}
add_action('wp_enqueue_scripts', 'contes_subscription_assets');

// Encolar scripts y estilos para el área de administración
function contes_subscription_enqueue_scripts($hook) {
    // Limitar la carga solo a ciertas páginas del área de administración si es necesario
    if (!in_array($hook, ['term.php', 'edit-tags.php'])) {
        return;
    }

    // Cargar el archivo JavaScript (React)
    wp_enqueue_script(
        'contes-subscription-main',
        plugins_url('build/main.bundle.js', __FILE__),
        array('wp-element', 'wp-components', 'wp-data', 'wp-i18n'), // Dependencias de WordPress
        filemtime(plugin_dir_path(__FILE__) . 'build/main.bundle.js'),
        true
    );
    

    // Cargar el archivo CSS
    wp_enqueue_style(
        'contes-subscription-main-styles',
        plugins_url('build/style.bundle.css', __FILE__),
        array(),
        filemtime(plugin_dir_path(__FILE__) . 'build/style.bundle.css')
    );
}
add_action('admin_enqueue_scripts', 'contes_subscription_enqueue_scripts');

function contes_subscription_admin_assets($hook) {
    if ($hook !== 'toplevel_page_contes-subscription') {
        return;
    }

    $css_path = plugin_dir_path(__FILE__) . 'assets/css/admin.css';
    wp_enqueue_style(
        'contes-subscription-admin',
        plugins_url('assets/css/admin.css', __FILE__),
        [],
        file_exists($css_path) ? filemtime($css_path) : CONTES_SUBSCRIPTION_VERSION
    );
}
add_action('admin_enqueue_scripts', 'contes_subscription_admin_assets');

function contes_subscription_enqueue_block_editor_assets() {
    $post_access_path = plugin_dir_path(__FILE__) . 'build/post-access.bundle.js';
    if (file_exists($post_access_path)) {
        wp_enqueue_script(
            'contes-subscription-post-access',
            plugins_url('build/post-access.bundle.js', __FILE__),
            ['wp-element', 'wp-components', 'wp-data', 'wp-i18n', 'wp-edit-post'],
            filemtime($post_access_path),
            true
        );

        $roles = [];
        foreach (get_editable_roles() as $key => $role) {
            $roles[] = [
                'value' => $key,
                'label' => $role['name'],
            ];
        }

        $levels = [];
        if (function_exists('pmpro_getAllLevels')) {
            $pmpro_levels = pmpro_getAllLevels(true, false);
            if (is_array($pmpro_levels)) {
                foreach ($pmpro_levels as $level) {
                    $levels[] = [
                        'value' => (int) $level->id,
                        'label' => $level->name,
                    ];
                }
            }
        }

        wp_localize_script(
            'contes-subscription-post-access',
            'contesPostAccessData',
            [
                'roles' => $roles,
                'levels' => $levels,
                'postTypes' => contes_subscription_get_access_post_types(),
                'defaultSalesUrl' => contes_subscription_get_global_sales_url(),
            ]
        );
    }

    $grid_controls_path = plugin_dir_path(__FILE__) . 'build/post-grid-controls.bundle.js';
    if (file_exists($grid_controls_path)) {
        wp_enqueue_script(
            'contes-subscription-post-grid-controls',
            plugins_url('build/post-grid-controls.bundle.js', __FILE__),
            ['wp-blocks', 'wp-element', 'wp-hooks', 'wp-compose', 'wp-block-editor', 'wp-components', 'wp-i18n'],
            filemtime($grid_controls_path),
            true
        );

        wp_localize_script(
            'contes-subscription-post-grid-controls',
            'contesPostGridData',
            [
                'defaultMode' => get_option('contes_post_grid_default_mode', 'locked'),
                'defaultUrl' => contes_subscription_get_global_sales_url(),
            ]
        );
    }
}
add_action('enqueue_block_editor_assets', 'contes_subscription_enqueue_block_editor_assets');
