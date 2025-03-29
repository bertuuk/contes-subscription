<?php

class Contes_Subscription_Settings
{

    public function __construct()
    {
        add_action('admin_menu', [$this, 'add_settings_page']);
        add_action('admin_init', [$this, 'register_settings']);
    }

    public function add_settings_page()
    {
        add_menu_page(
            __('Subscription Settings', 'contes-subscription'),
            __('Subscription', 'contes-subscription'),
            'manage_options',
            'contes-subscription',
            [$this, 'render_settings_page']
        );
        if (isset($_GET['reset_success']) && $_GET['reset_success'] == '1') {
            echo '<div class="notice notice-success is-dismissible">';
            echo '<p>' . esc_html__('Post views counters have been successfully reset.', 'contes-subscription') . '</p>';
            echo '</div>';
        }
    }

    public function register_settings()
    {
        register_setting('contes_subscription_settings', 'contes_allowed_roles', [
            'type' => 'array',
            'sanitize_callback' => [$this, 'sanitize_roles'],
            'default' => ['premium_member'],
        ]);

        add_settings_section(
            'contes_subscription_general',
            __('General Settings', 'contes-subscription'),
            [$this, 'settings_section_callback'],
            'contes-subscription'
        );

        add_settings_field(
            'contes_allowed_roles',
            __('Allowed Roles', 'contes-subscription'),
            [$this, 'allowed_roles_callback'],
            'contes-subscription',
            'contes_subscription_general'
        );
    }

    public function sanitize_roles($input)
    {
        return array_map('sanitize_text_field', (array)$input);
    }

    public function settings_section_callback()
    {
        echo '<p>' . __('Configure subscription-related settings.', 'contes-subscription') . '</p>';
    }

    public function allowed_roles_callback()
    {
        $roles = wp_roles()->roles;
        $allowed_roles = get_option('contes_allowed_roles', []);

        foreach ($roles as $role_slug => $role) {
            $checked = in_array($role_slug, $allowed_roles) ? 'checked' : '';
            printf(
                '<label><input type="checkbox" name="contes_allowed_roles[]" value="%s" %s> %s</label><br>',
                esc_attr($role_slug),
                $checked,
                esc_html($role['name'])
            );
        }
    }

    public function render_settings_page()
    {
        if (isset($_GET['reset_success']) && $_GET['reset_success'] == '1') {
            echo '<div class="notice notice-success is-dismissible">';
            echo '<p>' . esc_html__('The selected post views counters have been successfully reset.', 'contes-subscription') . '</p>';
            echo '</div>';
        }
        
        echo '<div class="wrap">';
        echo '<h1>' . esc_html__('Subscription Settings', 'contes-subscription') . '</h1>';
        echo '<form method="post" action="options.php">';
        settings_fields('contes_subscription_settings');
        do_settings_sections('contes-subscription');
        submit_button();
        echo '</form>';
        echo '</div>';
        echo '<h2>' . esc_html__('Reset Post Views Counters', 'contes-subscription') . '</h2>';
        echo '<p>' . esc_html__('You can reset either the general views count or the monthly views count for all posts.', 'contes-subscription') . '</p>';

        // Form for resetting general views count
        echo '<form id="reset-general-views-form" method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
        echo '<input type="hidden" name="action" value="reset_post_views_counters">';
        echo '<input type="hidden" name="counter_type" value="general">';
        wp_nonce_field('reset_post_views_nonce', 'reset_post_views_nonce_field');
        submit_button(__('Reset General Views', 'contes-subscription'), 'delete');
        echo '</form>';

        // Form for resetting monthly views count
        echo '<form id="reset-monthly-views-form" method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
        echo '<input type="hidden" name="action" value="reset_post_views_counters">';
        echo '<input type="hidden" name="counter_type" value="monthly">';
        wp_nonce_field('reset_post_views_nonce', 'reset_post_views_nonce_field');
        submit_button(__('Reset Monthly Views', 'contes-subscription'), 'delete');
        echo '</form>';

        // Add inline JavaScript for confirmation
        echo '<script>
document.getElementById("reset-general-views-form").addEventListener("submit", function(event) {
    if (!confirm("' . esc_js(__('Are you sure you want to reset the general post views? This action cannot be undone.', 'contes-subscription')) . '")) {
        event.preventDefault();
    }
});
document.getElementById("reset-monthly-views-form").addEventListener("submit", function(event) {
    if (!confirm("' . esc_js(__('Are you sure you want to reset the monthly post views? This action cannot be undone.', 'contes-subscription')) . '")) {
        event.preventDefault();
    }
});
</script>';
    }
}
