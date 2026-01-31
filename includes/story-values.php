<?php

if (!defined('ABSPATH')) {
    exit;
}

define('CONTES_STORY_VALUES_META_KEY', 'contes_story_values');

function contes_subscription_sanitize_story_values($values) {
    if (!is_array($values)) {
        return [];
    }

    $values = array_map('sanitize_text_field', $values);
    $values = array_filter($values, function ($value) {
        return $value !== '';
    });

    return array_values(array_unique($values));
}

function contes_subscription_register_story_values_meta() {
    register_post_meta('post', CONTES_STORY_VALUES_META_KEY, [
        'type' => 'array',
        'single' => true,
        'default' => [],
        'show_in_rest' => [
            'schema' => [
                'type' => 'array',
                'items' => [
                    'type' => 'string',
                ],
            ],
        ],
        'sanitize_callback' => 'contes_subscription_sanitize_story_values',
        'auth_callback' => function () {
            return current_user_can('edit_posts');
        },
    ]);
}
add_action('init', 'contes_subscription_register_story_values_meta');

function contes_subscription_get_story_values() {
    global $wpdb;

    $raw_values = $wpdb->get_col(
        $wpdb->prepare(
            "SELECT meta_value FROM {$wpdb->postmeta} WHERE meta_key = %s",
            CONTES_STORY_VALUES_META_KEY
        )
    );

    $values = [];
    foreach ($raw_values as $raw_value) {
        $maybe_array = maybe_unserialize($raw_value);
        if (is_array($maybe_array)) {
            $values = array_merge($values, $maybe_array);
        } elseif (is_string($maybe_array)) {
            $values[] = $maybe_array;
        }
    }

    $values = contes_subscription_sanitize_story_values($values);

    return rest_ensure_response($values);
}

function contes_subscription_register_story_values_route() {
    register_rest_route('contes-subscription/v1', '/story-values', [
        'methods' => WP_REST_Server::READABLE,
        'callback' => 'contes_subscription_get_story_values',
        'permission_callback' => function () {
            return current_user_can('edit_posts');
        },
    ]);
}
add_action('rest_api_init', 'contes_subscription_register_story_values_route');

function contes_subscription_story_values_editor_assets() {
    $asset_path = CONTES_SUBSCRIPTION_PATH . 'build/story-values.bundle.js';

    wp_enqueue_script(
        'contes-subscription-story-values',
        CONTES_SUBSCRIPTION_URL . 'build/story-values.bundle.js',
        ['wp-element', 'wp-components', 'wp-data', 'wp-edit-post', 'wp-plugins', 'wp-i18n', 'wp-api-fetch'],
        file_exists($asset_path) ? filemtime($asset_path) : CONTES_SUBSCRIPTION_VERSION,
        true
    );

    wp_localize_script('contes-subscription-story-values', 'contesStoryValues', [
        'metaKey' => CONTES_STORY_VALUES_META_KEY,
        'nonce' => wp_create_nonce('wp_rest'),
        'restPath' => '/contes-subscription/v1/story-values',
    ]);

    wp_add_inline_script(
        'contes-subscription-story-values',
        'wp.apiFetch.use( wp.apiFetch.createNonceMiddleware( contesStoryValues.nonce ) );',
        'before'
    );
}
add_action('enqueue_block_editor_assets', 'contes_subscription_story_values_editor_assets');
