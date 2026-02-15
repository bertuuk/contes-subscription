<?php

function contes_subscription_get_locked_template_slug() {
    $theme_slug = wp_get_theme()->get_stylesheet();
    return $theme_slug . '//contes-locked';
}

function contes_subscription_get_locked_template_id() {
    return absint(get_option('contes_locked_template_id', 0));
}

function contes_subscription_get_locked_template_by_slug($slug) {
    if (empty($slug)) {
        return null;
    }

    $templates = get_posts([
        'post_type' => 'wp_template',
        'name' => $slug,
        'post_status' => 'publish',
        'numberposts' => 1,
    ]);

    return !empty($templates) ? $templates[0] : null;
}

function contes_subscription_get_locked_template_by_meta() {
    $templates = get_posts([
        'post_type' => 'wp_template',
        'post_status' => 'publish',
        'numberposts' => 1,
        'meta_key' => '_contes_subscription_template',
        'meta_value' => '1',
    ]);

    return !empty($templates) ? $templates[0] : null;
}

function contes_subscription_locked_template_has_expected_structure($content) {
    if ($content === '') {
        return false;
    }

    return (strpos($content, 'wp:template-part') !== false && strpos($content, 'contes_locked_message') !== false);
}

function contes_subscription_ensure_locked_template_content($template) {
    if (!$template) {
        return;
    }

    if (contes_subscription_locked_template_has_expected_structure($template->post_content)) {
        return;
    }

    wp_update_post([
        'ID' => $template->ID,
        'post_content' => contes_subscription_get_locked_template_default_content(),
    ]);
}

function contes_subscription_get_locked_template() {
    if (!function_exists('wp_is_block_theme') || !wp_is_block_theme()) {
        return null;
    }

    $template_id = contes_subscription_get_locked_template_id();
    if (!$template_id) {
        return null;
    }

    $template = get_post($template_id);
    if ($template && $template->post_type === 'wp_template' && $template->post_status === 'publish') {
        return $template;
    }

    return null;
}

function contes_subscription_get_locked_template_default_content() {
    return implode("\n", [
        '<!-- wp:template-part {"slug":"header","tagName":"header"} /-->',
        '<!-- wp:group {"tagName":"main","className":"contes-locked-template"} -->',
        '<!-- wp:shortcode -->[contes_locked_message]<!-- /wp:shortcode -->',
        '<!-- /wp:group -->',
        '<!-- wp:template-part {"slug":"footer","tagName":"footer"} /-->',
    ]);
}

function contes_subscription_create_locked_template() {
    if (!function_exists('wp_is_block_theme') || !wp_is_block_theme()) {
        return null;
    }

    $slug = contes_subscription_get_locked_template_slug();
    $existing = contes_subscription_get_locked_template_by_meta();
    if ($existing) {
        if ($existing->post_name !== $slug) {
            wp_update_post([
                'ID' => $existing->ID,
                'post_name' => $slug,
            ]);
        }
        update_post_meta($existing->ID, '_contes_subscription_template', '1');
        update_option('contes_locked_template_id', (int) $existing->ID);
        contes_subscription_ensure_locked_template_content($existing);
        return $existing;
    }

    $existing = contes_subscription_get_locked_template_by_slug($slug);
    if ($existing) {
        update_post_meta($existing->ID, '_contes_subscription_template', '1');
        update_option('contes_locked_template_id', (int) $existing->ID);
        contes_subscription_ensure_locked_template_content($existing);
        return $existing;
    }

    $template_id = wp_insert_post([
        'post_type' => 'wp_template',
        'post_status' => 'publish',
        'post_title' => __('Contingut bloquejat (Contes Subscription)', 'contes-subscription'),
        'post_name' => $slug,
        'post_content' => contes_subscription_get_locked_template_default_content(),
    ]);

    if (is_wp_error($template_id)) {
        return null;
    }

    update_post_meta($template_id, '_contes_subscription_template', '1');
    if (taxonomy_exists('wp_theme')) {
        $theme_slug = wp_get_theme()->get_stylesheet();
        wp_set_object_terms($template_id, $theme_slug, 'wp_theme');
    }

    update_option('contes_locked_template_id', (int) $template_id);

    $template = get_post($template_id);
    contes_subscription_ensure_locked_template_content($template);
    return $template;
}

function contes_subscription_get_locked_template_defaults() {
    $default_title = __('Contingut restringit', 'contes-subscription');
    $default_description = __('No tens accés a aquest contingut. Subscriu-te per veure\'l.', 'contes-subscription');
    $default_button_label = __('Subscriu-te', 'contes-subscription');
    $default_button_label_logged_in = __('Renova la subscripció', 'contes-subscription');
    $default_login_label = __('Inicia sessió', 'contes-subscription');

    $title = get_option('contes_locked_title', $default_title);
    $description = get_option('contes_locked_description', $default_description);
    $button_label = get_option('contes_locked_button_label', $default_button_label);
    $button_label_logged_in = get_option('contes_locked_button_label_logged_in', $default_button_label_logged_in);
    $button_url = get_option('contes_locked_button_url', contes_subscription_get_global_sales_url());
    $login_label = get_option('contes_locked_login_label', $default_login_label);
    $login_url = get_option('contes_locked_login_url', '');

    $button_page_id = absint(get_option('contes_locked_button_page_id', 0));
    if ($button_page_id) {
        $button_url = get_permalink($button_page_id);
    }

    $login_page_id = absint(get_option('contes_locked_login_page_id', 0));
    if ($login_page_id) {
        $login_url = get_permalink($login_page_id);
    }

    return [
        'title' => sanitize_text_field($title !== '' ? $title : $default_title),
        'description' => wp_kses_post($description !== '' ? $description : $default_description),
        'button_label' => sanitize_text_field($button_label !== '' ? $button_label : $default_button_label),
        'button_label_logged_in' => sanitize_text_field($button_label_logged_in !== '' ? $button_label_logged_in : $default_button_label_logged_in),
        'button_url' => esc_url_raw($button_url !== '' ? $button_url : contes_subscription_get_global_sales_url()),
        'login_label' => sanitize_text_field($login_label !== '' ? $login_label : $default_login_label),
        'login_url' => esc_url_raw($login_url),
    ];
}

function contes_subscription_get_blocked_post_id() {
    if (isset($GLOBALS['contes_subscription_blocked_post_id'])) {
        return (int) $GLOBALS['contes_subscription_blocked_post_id'];
    }

    return (int) get_queried_object_id();
}

function contes_subscription_replace_locked_placeholders($text, $post_id) {
    if (!$post_id || $text === '') {
        return $text;
    }

    $replacements = [
        '{title}' => get_the_title($post_id),
        '{url}' => get_permalink($post_id),
    ];

    return str_replace(array_keys($replacements), array_values($replacements), $text);
}

function contes_subscription_get_locked_login_url($post_id = 0) {
    $defaults = contes_subscription_get_locked_template_defaults();
    if (!empty($defaults['login_url'])) {
        return $defaults['login_url'];
    }

    $redirect = $post_id ? get_permalink($post_id) : home_url('/');
    return wp_login_url($redirect);
}

function contes_subscription_render_locked_message($post_id = 0) {
    $post_id = $post_id ?: contes_subscription_get_blocked_post_id();
    $defaults = contes_subscription_get_locked_template_defaults();

    $title = contes_subscription_replace_locked_placeholders($defaults['title'], $post_id);
    $description = contes_subscription_replace_locked_placeholders($defaults['description'], $post_id);

    $is_logged_in = is_user_logged_in();
    $button_label = $is_logged_in ? $defaults['button_label_logged_in'] : $defaults['button_label'];
    $button_label = $button_label !== '' ? $button_label : $defaults['button_label'];
    $button_url = $defaults['button_url'];

    $login_label = $defaults['login_label'];
    $login_url = contes_subscription_get_locked_login_url($post_id);

    $icon_html = '<div class="contes-locked__icon-wrap"><span class="contes-locked__icon dahlia-icon dahlia-fi-rr-lock" aria-hidden="true"></span></div>';
    $title_html = $title !== '' ? '<h1 class="contes-locked__title">' . esc_html($title) . '</h1>' : '';
    $description_html = $description !== '' ? '<p class="contes-locked__description">' . wp_kses_post($description) . '</p>' : '';

    $buttons = '';
    if ($button_label !== '' && $button_url !== '') {
        $buttons .= sprintf(
            '<a class="contes-locked__button contes-locked__button--primary" href="%s">%s</a>',
            esc_url($button_url),
            esc_html($button_label)
        );
    }

    if (!$is_logged_in && $login_label !== '' && $login_url !== '') {
        $buttons .= sprintf(
            '<a class="contes-locked__button contes-locked__button--secondary" href="%s">%s</a>',
            esc_url($login_url),
            esc_html($login_label)
        );
    }

    $buttons_html = $buttons !== '' ? '<div class="contes-locked__actions">' . $buttons . '</div>' : '';

    return '<section class="contes-locked">' .
        '<div class="contes-locked__inner">' .
        $icon_html .
        $title_html .
        $description_html .
        $buttons_html .
        '</div>' .
        '</section>';
}

function contes_subscription_locked_message_shortcode() {
    return contes_subscription_render_locked_message();
}
add_shortcode('contes_locked_message', 'contes_subscription_locked_message_shortcode');

function contes_subscription_should_use_locked_template() {
    if (is_admin() || wp_doing_ajax() || (defined('REST_REQUEST') && REST_REQUEST)) {
        return false;
    }

    if (!is_singular()) {
        return false;
    }

    $post_id = get_queried_object_id();
    if (!$post_id) {
        return false;
    }

    if (!contes_subscription_is_access_post_type(get_post_type($post_id))) {
        return false;
    }

    $enabled = (bool) get_post_meta($post_id, 'contes_access_enabled', true);
    if (!$enabled) {
        return false;
    }

    if (contes_subscription_user_has_post_access($post_id)) {
        return false;
    }

    return true;
}

function contes_subscription_locked_template_include($template) {
    if (!contes_subscription_should_use_locked_template()) {
        return $template;
    }

    $locked_template = CONTES_SUBSCRIPTION_PATH . 'templates/locked-content.php';
    if (file_exists($locked_template)) {
        return $locked_template;
    }

    return $template;
}
add_filter('template_include', 'contes_subscription_locked_template_include');

function contes_subscription_get_block_templates_for_theme() {
    if (!function_exists('wp_is_block_theme') || !wp_is_block_theme()) {
        return [];
    }

    $theme_slug = wp_get_theme()->get_stylesheet();
    $templates = get_posts([
        'post_type' => 'wp_template',
        'post_status' => 'publish',
        'numberposts' => -1,
    ]);

    $filtered = [];
    foreach ($templates as $template) {
        $is_plugin_template = get_post_meta($template->ID, '_contes_subscription_template', true) === '1';
        $matches_theme = false;

        if (taxonomy_exists('wp_theme')) {
            $terms = wp_get_object_terms($template->ID, 'wp_theme', ['fields' => 'slugs']);
            $matches_theme = is_array($terms) && in_array($theme_slug, $terms, true);
        } else {
            $matches_theme = strpos($template->post_name, $theme_slug . '//') === 0;
        }

        if ($matches_theme || $is_plugin_template) {
            $filtered[] = $template;
        }
    }

    return $filtered;
}

function contes_subscription_locked_template_admin_notice() {
    if (!current_user_can('manage_options')) {
        return;
    }

    if (!function_exists('wp_is_block_theme') || !wp_is_block_theme()) {
        return;
    }

    if (contes_subscription_get_locked_template_by_meta()) {
        return;
    }

    $action_url = wp_nonce_url(
        admin_url('admin-post.php?action=contes_create_locked_template'),
        'contes_create_locked_template'
    );

    echo '<div class="notice notice-error"><p><strong>' .
        esc_html__('Contes Subscription: falta la plantilla de contingut bloquejat.', 'contes-subscription') .
        '</strong> ' .
        esc_html__('Crea-la per personalitzar la vista quan un usuari no té accés.', 'contes-subscription') .
        '</p><p><a class="button button-primary" href="' . esc_url($action_url) . '">' .
        esc_html__('Crear plantilla', 'contes-subscription') .
        '</a></p></div>';
}
add_action('admin_notices', 'contes_subscription_locked_template_admin_notice');

function contes_subscription_handle_create_locked_template() {
    if (!current_user_can('manage_options')) {
        wp_die(__('No tens permisos per fer aquesta acció.', 'contes-subscription'));
    }

    check_admin_referer('contes_create_locked_template');

    $template = contes_subscription_create_locked_template();
    if (!$template) {
        wp_safe_redirect(admin_url('admin.php?page=contes-subscription&tab=post-grid-access'));
        exit;
    }

    $edit_link = get_edit_post_link($template->ID, '');
    if (!$edit_link) {
        $post_id = rawurlencode($template->post_name);
        $edit_link = admin_url('site-editor.php?postType=wp_template&postId=' . $post_id);
    }

    wp_safe_redirect($edit_link);
    exit;
}
add_action('admin_post_contes_create_locked_template', 'contes_subscription_handle_create_locked_template');

function contes_subscription_delete_locked_templates() {
    $templates = get_posts([
        'post_type' => 'wp_template',
        'post_status' => ['publish', 'draft', 'trash'],
        'numberposts' => -1,
        'meta_key' => '_contes_subscription_template',
        'meta_value' => '1',
    ]);

    foreach ($templates as $template) {
        wp_delete_post($template->ID, true);
    }
}

function contes_subscription_locked_template_robots($robots) {
    if (!contes_subscription_should_use_locked_template()) {
        return $robots;
    }

    $robots['noindex'] = true;
    $robots['nofollow'] = true;
    $robots['noarchive'] = true;
    $robots['nosnippet'] = true;

    return $robots;
}
add_filter('wp_robots', 'contes_subscription_locked_template_robots');

function contes_subscription_enqueue_locked_template_assets() {
    if (!contes_subscription_should_use_locked_template()) {
        return;
    }

    $style_path = CONTES_SUBSCRIPTION_PATH . 'build/style.bundle.css';
    $style_url = CONTES_SUBSCRIPTION_URL . 'build/style.bundle.css';
    $style_version = file_exists($style_path) ? filemtime($style_path) : CONTES_SUBSCRIPTION_VERSION;

    wp_enqueue_style(
        'contes-subscription-locked-template',
        $style_url,
        [],
        $style_version
    );
}
add_action('wp_enqueue_scripts', 'contes_subscription_enqueue_locked_template_assets');
