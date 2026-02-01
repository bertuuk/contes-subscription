<?php

if (!defined('ABSPATH')) {
    exit;
}

define('CONTES_STORY_VALUES_META_KEY', 'contes_story_values');
define('CONTES_STORY_VALUES_TAXONOMY', 'story_value');

function contes_subscription_register_story_values_taxonomy() {
    $labels = [
        'name'              => __('Story values', 'contes-subscription'),
        'singular_name'     => __('Story value', 'contes-subscription'),
        'search_items'      => __('Search story values', 'contes-subscription'),
        'all_items'         => __('All story values', 'contes-subscription'),
        'edit_item'         => __('Edit story value', 'contes-subscription'),
        'update_item'       => __('Update story value', 'contes-subscription'),
        'add_new_item'      => __('Add new story value', 'contes-subscription'),
        'new_item_name'     => __('New story value name', 'contes-subscription'),
        'menu_name'         => __('Story values', 'contes-subscription'),
    ];

    register_taxonomy(CONTES_STORY_VALUES_TAXONOMY, ['post'], [
        'labels' => $labels,
        'public' => true,
        'show_ui' => true,
        'show_in_rest' => true,
        'show_admin_column' => true,
        'hierarchical' => false,
        'rewrite' => ['slug' => 'story-value'],
    ]);
}
add_action('init', 'contes_subscription_register_story_values_taxonomy');

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

function contes_subscription_migrate_story_values_to_taxonomy() {
    if (get_option('contes_story_values_migrated')) {
        return;
    }

    $query = new WP_Query([
        'post_type' => 'post',
        'post_status' => 'any',
        'posts_per_page' => -1,
        'fields' => 'ids',
        'meta_key' => CONTES_STORY_VALUES_META_KEY,
    ]);

    if (empty($query->posts)) {
        update_option('contes_story_values_migrated', 1);
        return;
    }

    foreach ($query->posts as $post_id) {
        $raw_values = get_post_meta($post_id, CONTES_STORY_VALUES_META_KEY, true);
        $values = contes_subscription_sanitize_story_values(is_array($raw_values) ? $raw_values : []);
        if (empty($values)) {
            continue;
        }

        $term_ids = [];
        foreach ($values as $value) {
            $term = term_exists($value, CONTES_STORY_VALUES_TAXONOMY);
            if (!$term) {
                $term = wp_insert_term($value, CONTES_STORY_VALUES_TAXONOMY);
            }
            if (!is_wp_error($term)) {
                $term_ids[] = (int) $term['term_id'];
            }
        }

        if (!empty($term_ids)) {
            wp_set_object_terms($post_id, $term_ids, CONTES_STORY_VALUES_TAXONOMY, true);
        }

        // Clean up legacy meta after migration.
        delete_post_meta($post_id, CONTES_STORY_VALUES_META_KEY);
    }

    update_option('contes_story_values_migrated', 1);
}
add_action('admin_init', 'contes_subscription_migrate_story_values_to_taxonomy');
