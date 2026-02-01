<?php
/**
 * Plugin Name: Contes Subscription
 * Description: Site-specific subscription logic for managing roles, memberships, and integrations with other plugins.
 * Version: 1.1.3
 * Author: Tu Nombre
 * Text Domain: contes-subscription
 * Domain Path: /languages
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Define plugin constants
define('CONTES_SUBSCRIPTION_VERSION', '1.1.3');
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

// Initialize components
add_action('plugins_loaded', function () {
    new Contes_Subscription_Settings();
    new Contes_Subscription_Roles();
});

function contes_subscription_assets() {
   
    wp_enqueue_style(
        'contes-subscription-icon-styles',
        plugins_url('build/icons.bundle.css', __FILE__),
        array(),
        filemtime(plugin_dir_path(__FILE__) . 'build/icons.bundle.css')
    );
}
add_action( 'wp_enqueue_scripts', 'contes_subscription_assets' );

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
