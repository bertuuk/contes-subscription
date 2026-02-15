<?php

function contes_subscription_get_post_grid_lock_defaults($attributes) {
    $default_mode = get_option('contes_post_grid_default_mode', 'locked');
    $default_destination = get_option('contes_post_grid_default_destination', 'post');
    $mode = $attributes['contesLockMode'] ?? 'default';
    $mode = $mode === 'default' ? $default_mode : $mode;
    if (!in_array($mode, ['locked', 'hidden', 'off'], true)) {
        $mode = $default_mode;
    }

    $use_default_url = !isset($attributes['contesLockUseDefaultUrl']) || !empty($attributes['contesLockUseDefaultUrl']);
    $cta_url = '';

    if (!$use_default_url) {
        $cta_url = $attributes['contesLockCtaUrl'] ?? '';
    } elseif ($default_destination === 'sales') {
        $cta_url = contes_subscription_get_global_sales_url();
    }

    return [
        'lock_mode' => $mode,
        'cta_url' => $cta_url,
    ];
}

function contes_subscription_post_grid_lock_defaults($defaults, $attributes) {
    return contes_subscription_get_post_grid_lock_defaults($attributes);
}
add_filter('dahlia_blocks_post_grid_lock_defaults', 'contes_subscription_post_grid_lock_defaults', 10, 2);

function contes_subscription_post_grid_post_access($has_access, $post_id, $attributes) {
    return contes_subscription_user_has_post_access($post_id);
}
add_filter('dahlia_blocks_post_grid_post_access', 'contes_subscription_post_grid_post_access', 10, 3);

function contes_subscription_post_grid_locked_url($url, $post_id, $attributes) {
    $defaults = contes_subscription_get_post_grid_lock_defaults($attributes);
    return $defaults['cta_url'] ?? $url;
}
add_filter('dahlia_blocks_post_grid_locked_url', 'contes_subscription_post_grid_locked_url', 10, 3);

function contes_subscription_post_grid_query_args($args, $attributes) {
    $lock_only = !empty($attributes['contesLockOnly']);
    if (!$lock_only) {
        return $args;
    }

    $meta_query = isset($args['meta_query']) && is_array($args['meta_query']) ? $args['meta_query'] : [];
    $meta_query[] = [
        'key' => 'contes_access_enabled',
        'value' => '1',
        'compare' => '=',
    ];
    $args['meta_query'] = $meta_query;

    return $args;
}
add_filter('dahlia_blocks_post_grid_query_args', 'contes_subscription_post_grid_query_args', 10, 2);
