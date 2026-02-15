<?php

function contes_subscription_register_access_meta() {
    $auth_callback = function() {
        return current_user_can('edit_posts');
    };

    $bool_sanitize = function($value) {
        return (bool) $value;
    };

    register_post_meta('', 'contes_access_enabled', [
        'type' => 'boolean',
        'single' => true,
        'show_in_rest' => true,
        'sanitize_callback' => $bool_sanitize,
        'auth_callback' => $auth_callback,
    ]);

    register_post_meta('', 'contes_access_roles', [
        'type' => 'array',
        'single' => true,
        'show_in_rest' => [
            'schema' => [
                'type' => 'array',
                'items' => [
                    'type' => 'string',
                ],
            ],
        ],
        'sanitize_callback' => 'contes_subscription_sanitize_role_list',
        'auth_callback' => $auth_callback,
    ]);

    register_post_meta('', 'contes_access_levels', [
        'type' => 'array',
        'single' => true,
        'show_in_rest' => [
            'schema' => [
                'type' => 'array',
                'items' => [
                    'type' => 'number',
                ],
            ],
        ],
        'sanitize_callback' => 'contes_subscription_sanitize_level_list',
        'auth_callback' => $auth_callback,
    ]);

    register_post_meta('', 'contes_access_redirect', [
        'type' => 'boolean',
        'single' => true,
        'show_in_rest' => true,
        'sanitize_callback' => $bool_sanitize,
        'auth_callback' => $auth_callback,
    ]);

    register_post_meta('', 'contes_access_redirect_url', [
        'type' => 'string',
        'single' => true,
        'show_in_rest' => true,
        'sanitize_callback' => 'esc_url_raw',
        'auth_callback' => $auth_callback,
    ]);
}
add_action('init', 'contes_subscription_register_access_meta');

function contes_subscription_sanitize_role_list($value) {
    $roles = array_keys(wp_roles()->roles);
    $sanitized = array_map('sanitize_key', (array)$value);
    return array_values(array_intersect($sanitized, $roles));
}

function contes_subscription_sanitize_level_list($value) {
    $sanitized = array_map('absint', (array)$value);
    $sanitized = array_filter($sanitized);
    return array_values(array_unique($sanitized));
}

function contes_subscription_get_access_post_types() {
    $post_types = get_option('contes_access_post_types', ['post', 'page']);
    return array_values(array_map('sanitize_key', (array)$post_types));
}

function contes_subscription_is_access_post_type($post_type) {
    $post_types = contes_subscription_get_access_post_types();
    return in_array($post_type, $post_types, true);
}

function contes_subscription_get_global_sales_url() {
    $default_url = function_exists('pmpro_url') ? pmpro_url('levels') : '';
    $page_id = absint(get_option('contes_sales_page_id', 0));
    if ($page_id) {
        $page_url = get_permalink($page_id);
        if (!empty($page_url)) {
            return $page_url;
        }
    }
    $value = get_option('contes_sales_page_url', $default_url);
    return $value ?: $default_url;
}

function contes_subscription_get_sales_page_url($post_id = 0) {
    $global = contes_subscription_get_global_sales_url();
    if ($post_id) {
        $override = get_post_meta($post_id, 'contes_access_redirect_url', true);
        if (!empty($override)) {
            return $override;
        }
    }
    return $global;
}

function contes_subscription_get_admin_membership_preview() {
    if (!current_user_can('manage_options')) {
        return 'current';
    }

    $value = get_user_meta(get_current_user_id(), 'pmpro_admin_membership_access', true);
    if ($value === 'no' || $value === 'yes') {
        return $value;
    }

    return 'current';
}

function contes_subscription_user_has_post_access($post_id, $user_id = null) {
    if (!$post_id) {
        return true;
    }

    $enabled = (bool) get_post_meta($post_id, 'contes_access_enabled', true);
    if (!$enabled) {
        return true;
    }

    $preview = contes_subscription_get_admin_membership_preview();
    if ($preview === 'no') {
        return false;
    }
    if ($preview === 'yes') {
        return true;
    }

    $levels = get_post_meta($post_id, 'contes_access_levels', true);
    $levels = array_filter(array_map('absint', (array)$levels));

    if (!empty($levels) && function_exists('pmpro_getMembershipLevelsForUser')) {
        $current_user_id = $user_id ?: get_current_user_id();
        if (empty($current_user_id)) {
            return false;
        }
        $user_levels = pmpro_getMembershipLevelsForUser($current_user_id);
        $user_level_ids = array_map(function ($level) {
            return (int) $level->id;
        }, is_array($user_levels) ? $user_levels : []);

        if (empty(array_intersect($levels, $user_level_ids))) {
            return false;
        }

        return true;
    }

    $roles = get_post_meta($post_id, 'contes_access_roles', true);
    $roles = array_map('sanitize_key', (array)$roles);

    if (empty($roles)) {
        return false;
    }

    if ($user_id) {
        $user = get_userdata($user_id);
    } else {
        $user = wp_get_current_user();
    }

    if (empty($user) || empty($user->roles)) {
        return false;
    }

    if (!array_intersect($roles, $user->roles)) {
        return false;
    }

    return true;
}

function contes_subscription_should_redirect_post($post_id) {
    if (!$post_id) {
        return false;
    }
    $enabled = (bool) get_post_meta($post_id, 'contes_access_enabled', true);
    if (!$enabled) {
        return false;
    }
    $redirect = get_post_meta($post_id, 'contes_access_redirect', true);
    return (bool) $redirect;
}

function contes_subscription_handle_post_redirect() {
    if (is_admin() || !is_singular()) {
        return;
    }

    $post_id = get_queried_object_id();
    if (!$post_id) {
        return;
    }

    $post_type = get_post_type($post_id);
    if (!contes_subscription_is_access_post_type($post_type)) {
        return;
    }

    if (!contes_subscription_should_redirect_post($post_id)) {
        return;
    }

    if (contes_subscription_user_has_post_access($post_id)) {
        return;
    }

    $sales_url = contes_subscription_get_sales_page_url($post_id);
    if (empty($sales_url)) {
        return;
    }

    if (trailingslashit($sales_url) === trailingslashit(get_permalink($post_id))) {
        return;
    }

    wp_redirect($sales_url);
    exit;
}
add_action('template_redirect', 'contes_subscription_handle_post_redirect');
