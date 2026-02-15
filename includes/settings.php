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
    }

    public function register_settings()
    {
        register_setting('contes_subscription_settings', 'contes_allowed_roles', [
            'type' => 'array',
            'sanitize_callback' => [$this, 'sanitize_roles'],
            'default' => ['premium_member'],
        ]);

        register_setting('contes_subscription_settings', 'contes_visibility_teaser_message', [
            'type' => 'string',
            'sanitize_callback' => [$this, 'sanitize_message'],
            'default' => __('Subscriu-te per accedir a aquest contingut', 'contes-subscription'),
        ]);

        register_setting('contes_subscription_settings', 'contes_visibility_teaser_button_label', [
            'type' => 'string',
            'sanitize_callback' => [$this, 'sanitize_text'],
            'default' => __('Subscriu-te', 'contes-subscription'),
        ]);

        register_setting('contes_subscription_settings', 'contes_visibility_teaser_button_url', [
            'type' => 'string',
            'sanitize_callback' => 'esc_url_raw',
            'default' => function_exists('pmpro_url') ? pmpro_url('levels') : '',
        ]);

        register_setting('contes_subscription_settings', 'contes_visibility_teaser_button_page_id', [
            'type' => 'integer',
            'sanitize_callback' => 'absint',
            'default' => 0,
        ]);

        register_setting('contes_subscription_settings', 'contes_visibility_teaser_height', [
            'type' => 'integer',
            'sanitize_callback' => 'absint',
            'default' => 120,
        ]);

        register_setting('contes_subscription_settings', 'contes_visibility_teaser_block_count', [
            'type' => 'integer',
            'sanitize_callback' => 'absint',
            'default' => 2,
        ]);

        register_setting('contes_subscription_settings', 'contes_visibility_teaser_background_color', [
            'type' => 'string',
            'sanitize_callback' => 'sanitize_hex_color',
            'default' => '#ffffff',
        ]);

        register_setting('contes_subscription_settings', 'contes_post_grid_default_mode', [
            'type' => 'string',
            'sanitize_callback' => [$this, 'sanitize_post_grid_mode'],
            'default' => 'locked',
        ]);

        register_setting('contes_subscription_settings', 'contes_sales_page_url', [
            'type' => 'string',
            'sanitize_callback' => 'esc_url_raw',
            'default' => function_exists('pmpro_url') ? pmpro_url('levels') : '',
        ]);

        register_setting('contes_subscription_settings', 'contes_sales_page_id', [
            'type' => 'integer',
            'sanitize_callback' => 'absint',
            'default' => 0,
        ]);

        register_setting('contes_subscription_settings', 'contes_post_grid_default_destination', [
            'type' => 'string',
            'sanitize_callback' => [$this, 'sanitize_text'],
            'default' => 'post',
        ]);

        register_setting('contes_subscription_settings', 'contes_access_post_types', [
            'type' => 'array',
            'sanitize_callback' => [$this, 'sanitize_post_types'],
            'default' => ['post', 'page'],
        ]);

        register_setting('contes_subscription_settings', 'contes_locked_template_id', [
            'type' => 'integer',
            'sanitize_callback' => 'absint',
            'default' => 0,
        ]);

        register_setting('contes_subscription_settings', 'contes_locked_title', [
            'type' => 'string',
            'sanitize_callback' => [$this, 'sanitize_text'],
            'default' => __('Contingut restringit', 'contes-subscription'),
        ]);

        register_setting('contes_subscription_settings', 'contes_locked_description', [
            'type' => 'string',
            'sanitize_callback' => [$this, 'sanitize_kses'],
            'default' => __('No tens accés a aquest contingut. Subscriu-te per veure\'l.', 'contes-subscription'),
        ]);

        register_setting('contes_subscription_settings', 'contes_locked_button_label', [
            'type' => 'string',
            'sanitize_callback' => [$this, 'sanitize_text'],
            'default' => __('Subscriu-te', 'contes-subscription'),
        ]);

        register_setting('contes_subscription_settings', 'contes_locked_button_label_logged_in', [
            'type' => 'string',
            'sanitize_callback' => [$this, 'sanitize_text'],
            'default' => __('Renova la subscripció', 'contes-subscription'),
        ]);

        register_setting('contes_subscription_settings', 'contes_locked_button_url', [
            'type' => 'string',
            'sanitize_callback' => 'esc_url_raw',
            'default' => function_exists('pmpro_url') ? pmpro_url('levels') : '',
        ]);

        register_setting('contes_subscription_settings', 'contes_locked_button_page_id', [
            'type' => 'integer',
            'sanitize_callback' => 'absint',
            'default' => 0,
        ]);

        register_setting('contes_subscription_settings', 'contes_locked_login_label', [
            'type' => 'string',
            'sanitize_callback' => [$this, 'sanitize_text'],
            'default' => __('Inicia sessió', 'contes-subscription'),
        ]);

        register_setting('contes_subscription_settings', 'contes_locked_login_url', [
            'type' => 'string',
            'sanitize_callback' => 'esc_url_raw',
            'default' => '',
        ]);

        register_setting('contes_subscription_settings', 'contes_locked_login_page_id', [
            'type' => 'integer',
            'sanitize_callback' => 'absint',
            'default' => 0,
        ]);

        add_settings_section(
            'contes_subscription_general',
            __('General Settings', 'contes-subscription'),
            [$this, 'settings_section_callback'],
            'contes-subscription-general'
        );

        add_settings_field(
            'contes_allowed_roles',
            __('Allowed Roles', 'contes-subscription'),
            [$this, 'allowed_roles_callback'],
            'contes-subscription-general',
            'contes_subscription_general'
        );

        add_settings_section(
            'contes_subscription_visibility_teaser',
            __('Valors per defecte del teaser', 'contes-subscription'),
            [$this, 'visibility_teaser_section_callback'],
            'contes-subscription-visibility'
        );

        add_settings_field(
            'contes_visibility_teaser_message',
            __('Default Message', 'contes-subscription'),
            [$this, 'visibility_teaser_message_callback'],
            'contes-subscription-visibility',
            'contes_subscription_visibility_teaser'
        );

        add_settings_field(
            'contes_visibility_teaser_button_label',
            __('Default Button Label', 'contes-subscription'),
            [$this, 'visibility_teaser_button_label_callback'],
            'contes-subscription-visibility',
            'contes_subscription_visibility_teaser'
        );

        add_settings_field(
            'contes_visibility_teaser_button_url',
            __('Default Button URL', 'contes-subscription'),
            [$this, 'visibility_teaser_button_url_callback'],
            'contes-subscription-visibility',
            'contes_subscription_visibility_teaser'
        );

        add_settings_field(
            'contes_visibility_teaser_block_count',
            __('Blocs visibles per defecte', 'contes-subscription'),
            [$this, 'visibility_teaser_block_count_callback'],
            'contes-subscription-visibility',
            'contes_subscription_visibility_teaser'
        );

        add_settings_field(
            'contes_visibility_teaser_height',
            __('Default Visible Height (px)', 'contes-subscription'),
            [$this, 'visibility_teaser_height_callback'],
            'contes-subscription-visibility',
            'contes_subscription_visibility_teaser'
        );

        add_settings_field(
            'contes_visibility_teaser_background_color',
            __('Default Background Color', 'contes-subscription'),
            [$this, 'visibility_teaser_background_color_callback'],
            'contes-subscription-visibility',
            'contes_subscription_visibility_teaser'
        );

        if ($this->is_post_grid_available()) {
            add_settings_section(
                'contes_subscription_post_grid_access',
                __('Post Grid i Accés', 'contes-subscription'),
                [$this, 'post_grid_section_callback'],
                'contes-subscription-post-grid'
            );

            add_settings_field(
                'contes_post_grid_default_mode',
                __('Mode per defecte del grid', 'contes-subscription'),
                [$this, 'post_grid_default_mode_callback'],
                'contes-subscription-post-grid',
                'contes_subscription_post_grid_access'
            );

            add_settings_field(
                'contes_sales_page_url',
                __('URL de destí global', 'contes-subscription'),
                [$this, 'post_grid_sales_url_callback'],
                'contes-subscription-post-grid',
                'contes_subscription_post_grid_access'
            );

            add_settings_field(
                'contes_post_grid_default_destination',
                __('Destí per defecte en blocatge', 'contes-subscription'),
                [$this, 'post_grid_default_destination_callback'],
                'contes-subscription-post-grid',
                'contes_subscription_post_grid_access'
            );

            add_settings_field(
                'contes_access_post_types',
                __('Post types afectats', 'contes-subscription'),
                [$this, 'post_grid_post_types_callback'],
                'contes-subscription-post-grid',
                'contes_subscription_post_grid_access'
            );
        }

        add_settings_section(
            'contes_subscription_locked_template',
            __('Plantilla de contingut bloquejat', 'contes-subscription'),
            [$this, 'locked_template_section_callback'],
            'contes-subscription-post-grid'
        );

        add_settings_field(
            'contes_locked_template_id',
            __('Plantilla de bloc', 'contes-subscription'),
            [$this, 'locked_template_callback'],
            'contes-subscription-post-grid',
            'contes_subscription_locked_template'
        );

        add_settings_field(
            'contes_locked_title',
            __('Títol per defecte', 'contes-subscription'),
            [$this, 'locked_template_title_callback'],
            'contes-subscription-post-grid',
            'contes_subscription_locked_template'
        );

        add_settings_field(
            'contes_locked_description',
            __('Descripció per defecte', 'contes-subscription'),
            [$this, 'locked_template_description_callback'],
            'contes-subscription-post-grid',
            'contes_subscription_locked_template'
        );

        add_settings_field(
            'contes_locked_button_label',
            __('Text botó (no loguejat)', 'contes-subscription'),
            [$this, 'locked_template_button_label_callback'],
            'contes-subscription-post-grid',
            'contes_subscription_locked_template'
        );

        add_settings_field(
            'contes_locked_button_label_logged_in',
            __('Text botó (loguejat)', 'contes-subscription'),
            [$this, 'locked_template_button_label_logged_in_callback'],
            'contes-subscription-post-grid',
            'contes_subscription_locked_template'
        );

        add_settings_field(
            'contes_locked_button_url',
            __('URL botó subscripció', 'contes-subscription'),
            [$this, 'locked_template_button_url_callback'],
            'contes-subscription-post-grid',
            'contes_subscription_locked_template'
        );

        add_settings_field(
            'contes_locked_login_label',
            __('Text botó iniciar sessió', 'contes-subscription'),
            [$this, 'locked_template_login_label_callback'],
            'contes-subscription-post-grid',
            'contes_subscription_locked_template'
        );

        add_settings_field(
            'contes_locked_login_url',
            __('URL iniciar sessió (override)', 'contes-subscription'),
            [$this, 'locked_template_login_url_callback'],
            'contes-subscription-post-grid',
            'contes_subscription_locked_template'
        );
    }

    public function sanitize_roles($input)
    {
        return array_map('sanitize_text_field', (array)$input);
    }

    public function sanitize_message($input)
    {
        return wp_kses_post((string) $input);
    }

    public function sanitize_text($input)
    {
        return sanitize_text_field((string) $input);
    }

    public function sanitize_kses($input)
    {
        return wp_kses_post((string) $input);
    }

    public function sanitize_post_grid_mode($input)
    {
        $allowed = ['locked', 'hidden', 'off'];
        $input = sanitize_text_field((string) $input);
        return in_array($input, $allowed, true) ? $input : 'locked';
    }

    public function sanitize_post_types($input)
    {
        $post_types = get_post_types(['public' => true], 'names');
        $sanitized = array_map('sanitize_key', (array)$input);
        return array_values(array_intersect($sanitized, $post_types));
    }

    public function settings_section_callback()
    {
        echo '<p>' . __('Configure subscription-related settings.', 'contes-subscription') . '</p>';
    }

    public function visibility_teaser_section_callback()
    {
        echo '<p>' . __("Defineix el missatge, el botó i l'estil per defecte del teaser de contingut.", 'contes-subscription') . '</p>';
    }

    public function post_grid_section_callback()
    {
        echo '<p>' . __("Configura el bloqueig del Post Grid i les opcions d'accés per post.", 'contes-subscription') . '</p>';
    }

    private function is_post_grid_available()
    {
        if (!class_exists('WP_Block_Type_Registry')) {
            return false;
        }

        return WP_Block_Type_Registry::get_instance()->is_registered('dahlia-blocks/post-grid');
    }

    public function locked_template_section_callback()
    {
        echo '<p>' . __("Defineix la plantilla de contingut bloquejat. Pots inserir el shortcode [contes_locked_message] dins la plantilla del Site Editor.", 'contes-subscription') . '</p>';
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

    public function visibility_teaser_message_callback()
    {
        $value = get_option('contes_visibility_teaser_message', __('Subscriu-te per accedir a aquest contingut', 'contes-subscription'));
        printf(
            '<textarea name="contes_visibility_teaser_message" rows="4" class="large-text">%s</textarea>',
            esc_textarea($value)
        );
    }

    public function visibility_teaser_button_label_callback()
    {
        $value = get_option('contes_visibility_teaser_button_label', __('Subscriu-te', 'contes-subscription'));
        printf(
            '<input type="text" name="contes_visibility_teaser_button_label" class="regular-text" value="%s" />',
            esc_attr($value)
        );
    }

    public function visibility_teaser_button_url_callback()
    {
        $default_url = function_exists('pmpro_url') ? pmpro_url('levels') : '';
        $value = get_option('contes_visibility_teaser_button_url', $default_url);
        printf(
            '<input type="url" name="contes_visibility_teaser_button_url" class="regular-text ltr" value="%s" />',
            esc_attr($value)
        );
        $page_id = (int) get_option('contes_visibility_teaser_button_page_id', 0);
        wp_dropdown_pages([
            'name' => 'contes_visibility_teaser_button_page_id',
            'show_option_none' => __('— Selecciona una pàgina —', 'contes-subscription'),
            'option_none_value' => 0,
            'selected' => $page_id,
        ]);
        echo '<p class="description">' . esc_html__('Si selecciones una pàgina, la URL s\'agafa automàticament.', 'contes-subscription') . '</p>';
    }

    public function visibility_teaser_block_count_callback()
    {
        $value = get_option('contes_visibility_teaser_block_count', 2);
        printf(
            '<input type="number" name="contes_visibility_teaser_block_count" min="1" step="1" class="small-text" value="%s" />',
            esc_attr($value)
        );
    }

    public function visibility_teaser_height_callback()
    {
        $value = get_option('contes_visibility_teaser_height', 120);
        printf(
            '<input type="number" name="contes_visibility_teaser_height" min="60" step="1" class="small-text" value="%s" />',
            esc_attr($value)
        );
    }

    public function visibility_teaser_background_color_callback()
    {
        $value = get_option('contes_visibility_teaser_background_color', '#ffffff');
        printf(
            '<input type="text" name="contes_visibility_teaser_background_color" class="regular-text" value="%s" placeholder="#ffffff" />',
            esc_attr($value)
        );
    }

    public function post_grid_default_mode_callback()
    {
        $value = get_option('contes_post_grid_default_mode', 'locked');
        $options = [
            'locked' => __('Bloquejat (blur + candau)', 'contes-subscription'),
            'hidden' => __('Ocult (no mostrar)', 'contes-subscription'),
            'off' => __('Desactivat', 'contes-subscription'),
        ];
        echo '<select name="contes_post_grid_default_mode">';
        foreach ($options as $key => $label) {
            printf(
                '<option value="%s" %s>%s</option>',
                esc_attr($key),
                selected($value, $key, false),
                esc_html($label)
            );
        }
        echo '</select>';
    }

    public function post_grid_sales_url_callback()
    {
        $default_url = function_exists('pmpro_url') ? pmpro_url('levels') : '';
        $value = get_option('contes_sales_page_url', $default_url);
        printf(
            '<input type="url" name="contes_sales_page_url" class="regular-text ltr" value="%s" />',
            esc_attr($value)
        );
        $page_id = (int) get_option('contes_sales_page_id', 0);
        wp_dropdown_pages([
            'name' => 'contes_sales_page_id',
            'show_option_none' => __('— Selecciona una pàgina —', 'contes-subscription'),
            'option_none_value' => 0,
            'selected' => $page_id,
        ]);
        echo '<p class="description">' . esc_html__('Si selecciones una pàgina, la URL global s\'agafa automàticament.', 'contes-subscription') . '</p>';
    }

    public function post_grid_default_destination_callback()
    {
        $value = get_option('contes_post_grid_default_destination', 'post');
        $options = [
            'post' => __('Anar a la pàgina bloquejada (recomanat)', 'contes-subscription'),
            'sales' => __('Anar a una pàgina específica', 'contes-subscription'),
        ];
        echo '<select name="contes_post_grid_default_destination">';
        foreach ($options as $key => $label) {
            printf(
                '<option value="%s" %s>%s</option>',
                esc_attr($key),
                selected($value, $key, false),
                esc_html($label)
            );
        }
        echo '</select>';
        echo '<p class="description">' . esc_html__('Tria el destí per defecte quan una targeta està bloquejada al post grid.', 'contes-subscription') . '</p>';
    }

    public function post_grid_post_types_callback()
    {
        $post_types = get_post_types(['public' => true], 'objects');
        $selected = get_option('contes_access_post_types', ['post', 'page']);

        foreach ($post_types as $post_type => $obj) {
            if ($post_type === 'attachment') {
                continue;
            }
            $checked = in_array($post_type, $selected, true) ? 'checked' : '';
            printf(
                '<label><input type="checkbox" name="contes_access_post_types[]" value="%s" %s> %s</label><br>',
                esc_attr($post_type),
                $checked,
                esc_html($obj->labels->singular_name)
            );
        }
    }

    public function locked_template_callback()
    {
        if (!function_exists('wp_is_block_theme') || !wp_is_block_theme()) {
            echo '<p class="description">' . esc_html__('Aquesta opció només està disponible amb temes de blocs (Site Editor).', 'contes-subscription') . '</p>';
            return;
        }

        $value = (int) get_option('contes_locked_template_id', 0);
        if ($value) {
            $selected = get_post($value);
            if (!$selected || $selected->post_type !== 'wp_template') {
                update_option('contes_locked_template_id', 0);
                $value = 0;
            }
        }
        $templates = contes_subscription_get_block_templates_for_theme();

        echo '<select name="contes_locked_template_id">';
        echo '<option value="0">' . esc_html__('Sense plantilla (usa el missatge per defecte)', 'contes-subscription') . '</option>';

        foreach ($templates as $template) {
            $is_plugin_template = get_post_meta($template->ID, '_contes_subscription_template', true) === '1';
            $label = $template->post_title ?: $template->post_name;
            if ($is_plugin_template) {
                $label .= ' — ' . __('Contes Subscription', 'contes-subscription');
            }
            printf(
                '<option value="%s" %s>%s</option>',
                esc_attr($template->ID),
                selected($value, $template->ID, false),
                esc_html($label)
            );
        }

        echo '</select>';

        if ($value) {
            $selected = get_post($value);
            if ($selected) {
                $edit_url = get_edit_post_link($selected->ID, '');
                if (!$edit_url) {
                    $edit_url = admin_url('site-editor.php?postType=wp_template&postId=' . rawurlencode($selected->post_name));
                }
                echo '<p class="description"><a href="' . esc_url($edit_url) . '">' . esc_html__('Editar plantilla al Site Editor', 'contes-subscription') . '</a></p>';
            }
        }

        $create_url = wp_nonce_url(
            admin_url('admin-post.php?action=contes_create_locked_template'),
            'contes_create_locked_template'
        );
        echo '<p class="description"><a href="' . esc_url($create_url) . '">' . esc_html__('Crear plantilla de Contes Subscription', 'contes-subscription') . '</a></p>';
    }

    public function locked_template_title_callback()
    {
        $value = get_option('contes_locked_title', __('Contingut restringit', 'contes-subscription'));
        printf(
            '<input type="text" name="contes_locked_title" class="regular-text" value="%s" />',
            esc_attr($value)
        );
        echo '<p class="description">' . esc_html__('Pots utilitzar {title} per inserir el títol del contingut bloquejat.', 'contes-subscription') . '</p>';
    }

    public function locked_template_description_callback()
    {
        $value = get_option('contes_locked_description', __('No tens accés a aquest contingut. Subscriu-te per veure\'l.', 'contes-subscription'));
        printf(
            '<textarea name="contes_locked_description" rows="4" class="large-text">%s</textarea>',
            esc_textarea($value)
        );
        echo '<p class="description">' . esc_html__('Pots utilitzar {title} per inserir el títol del contingut bloquejat.', 'contes-subscription') . '</p>';
    }

    public function locked_template_button_label_callback()
    {
        $value = get_option('contes_locked_button_label', __('Subscriu-te', 'contes-subscription'));
        printf(
            '<input type="text" name="contes_locked_button_label" class="regular-text" value="%s" />',
            esc_attr($value)
        );
    }

    public function locked_template_button_label_logged_in_callback()
    {
        $value = get_option('contes_locked_button_label_logged_in', __('Renova la subscripció', 'contes-subscription'));
        printf(
            '<input type="text" name="contes_locked_button_label_logged_in" class="regular-text" value="%s" />',
            esc_attr($value)
        );
    }

    public function locked_template_button_url_callback()
    {
        $default_url = function_exists('pmpro_url') ? pmpro_url('levels') : '';
        $value = get_option('contes_locked_button_url', $default_url);
        printf(
            '<input type="url" name="contes_locked_button_url" class="regular-text ltr" value="%s" />',
            esc_attr($value)
        );
        $page_id = (int) get_option('contes_locked_button_page_id', 0);
        wp_dropdown_pages([
            'name' => 'contes_locked_button_page_id',
            'show_option_none' => __('— Selecciona una pàgina —', 'contes-subscription'),
            'option_none_value' => 0,
            'selected' => $page_id,
        ]);
        echo '<p class="description">' . esc_html__('Si selecciones una pàgina, la URL del botó s\'agafa automàticament.', 'contes-subscription') . '</p>';
    }

    public function locked_template_login_label_callback()
    {
        $value = get_option('contes_locked_login_label', __('Inicia sessió', 'contes-subscription'));
        printf(
            '<input type="text" name="contes_locked_login_label" class="regular-text" value="%s" />',
            esc_attr($value)
        );
    }

    public function locked_template_login_url_callback()
    {
        $value = get_option('contes_locked_login_url', '');
        printf(
            '<input type="url" name="contes_locked_login_url" class="regular-text ltr" value="%s" placeholder="%s" />',
            esc_attr($value),
            esc_attr__('(usa la URL de login per defecte)', 'contes-subscription')
        );
        $page_id = (int) get_option('contes_locked_login_page_id', 0);
        wp_dropdown_pages([
            'name' => 'contes_locked_login_page_id',
            'show_option_none' => __('— Selecciona una pàgina —', 'contes-subscription'),
            'option_none_value' => 0,
            'selected' => $page_id,
        ]);
        echo '<p class="description">' . esc_html__('Si selecciones una pàgina, s\'usarà com a URL d\'inici de sessió.', 'contes-subscription') . '</p>';
    }

    private function get_visibility_teaser_usage()
    {
        $post_types = get_post_types(['public' => true], 'names');
        $query = new WP_Query([
            'post_type' => $post_types,
            'post_status' => ['publish', 'draft', 'private', 'future'],
            'posts_per_page' => 200,
            'has_block' => 'contes/visibility-teaser',
        ]);

        if (!$query->have_posts()) {
            return [];
        }

        $rows = [];

        foreach ($query->posts as $post) {
            $blocks = parse_blocks($post->post_content);
            $count = 0;
            $has_custom_text = false;

            foreach ($blocks as $block) {
                if (empty($block['blockName']) || $block['blockName'] !== 'contes/visibility-teaser') {
                    continue;
                }

                $count++;
                $attrs = $block['attrs'] ?? [];
                if (array_key_exists('useDefaultContent', $attrs) && $attrs['useDefaultContent'] === false) {
                    $has_custom_text = true;
                }
            }

            if ($count > 0) {
                $rows[] = [
                    'id' => $post->ID,
                    'title' => get_the_title($post->ID) ?: __('(sense títol)', 'contes-subscription'),
                    'type' => $post->post_type,
                    'count' => $count,
                    'custom_text' => $has_custom_text,
                ];
            }
        }

        wp_reset_postdata();

        return $rows;
    }

    private function render_visibility_teaser_usage_table()
    {
        $rows = $this->get_visibility_teaser_usage();

        echo '<h2>' . esc_html__('Blocs en ús', 'contes-subscription') . '</h2>';

        if (empty($rows)) {
            echo '<p>' . esc_html__("No s'han trobat blocs de teaser.", 'contes-subscription') . '</p>';
            return;
        }

        echo '<table class="widefat striped">';
        echo '<thead><tr>';
        echo '<th>' . esc_html__('Pàgina', 'contes-subscription') . '</th>';
        echo '<th>' . esc_html__('Tipus', 'contes-subscription') . '</th>';
        echo '<th>' . esc_html__('Nombre de blocs', 'contes-subscription') . '</th>';
        echo '<th>' . esc_html__('Text personalitzat', 'contes-subscription') . '</th>';
        echo '</tr></thead><tbody>';

        foreach ($rows as $row) {
            $edit_link = get_edit_post_link($row['id']);
            $type_obj = get_post_type_object($row['type']);
            $type_label = $type_obj ? $type_obj->labels->singular_name : $row['type'];
            $custom_text_label = $row['custom_text'] ? __('Sí', 'contes-subscription') : __('No', 'contes-subscription');

            echo '<tr>';
            echo '<td><a href="' . esc_url($edit_link) . '">' . esc_html($row['title']) . '</a></td>';
            echo '<td>' . esc_html($type_label) . '</td>';
            echo '<td>' . esc_html($row['count']) . '</td>';
            echo '<td>' . esc_html($custom_text_label) . '</td>';
            echo '</tr>';
        }

        echo '</tbody></table>';
    }

    private function render_general_tab()
    {
        echo '<form method="post" action="options.php">';
        settings_fields('contes_subscription_settings');
        do_settings_sections('contes-subscription-general');
        submit_button();
        echo '</form>';
    }

    private function render_visibility_teaser_tab()
    {
        echo '<form method="post" action="options.php">';
        settings_fields('contes_subscription_settings');
        do_settings_sections('contes-subscription-visibility');
        submit_button();
        echo '</form>';

        $this->render_visibility_teaser_usage_table();
    }

    private function render_post_grid_access_tab()
    {
        echo '<form method="post" action="options.php">';
        settings_fields('contes_subscription_settings');
        do_settings_sections('contes-subscription-post-grid');
        submit_button();
        echo '</form>';
    }

    private function render_tools_tab()
    {
        echo '<h2>' . esc_html__('Reset Post Views Counters', 'contes-subscription') . '</h2>';
        echo '<p>' . esc_html__('You can reset either the general views count or the monthly views count for all posts.', 'contes-subscription') . '</p>';

        echo '<form id="reset-general-views-form" method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
        echo '<input type="hidden" name="action" value="reset_post_views_counters">';
        echo '<input type="hidden" name="counter_type" value="general">';
        wp_nonce_field('reset_post_views_nonce', 'reset_post_views_nonce_field');
        submit_button(__('Reset General Views', 'contes-subscription'), 'delete');
        echo '</form>';

        echo '<form id="reset-monthly-views-form" method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
        echo '<input type="hidden" name="action" value="reset_post_views_counters">';
        echo '<input type="hidden" name="counter_type" value="monthly">';
        wp_nonce_field('reset_post_views_nonce', 'reset_post_views_nonce_field');
        submit_button(__('Reset Monthly Views', 'contes-subscription'), 'delete');
        echo '</form>';

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

    public function render_settings_page()
    {
        $current_tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'general';
        $tabs = [
            'general' => __('General', 'contes-subscription'),
            'visibility' => __('Teaser de contingut', 'contes-subscription'),
            'post-grid-access' => __('Post Grid / Accés', 'contes-subscription'),
            'tools' => __('Eines', 'contes-subscription'),
        ];

        if (isset($_GET['reset_success']) && $_GET['reset_success'] == '1') {
            echo '<div class="notice notice-success is-dismissible">';
            echo '<p>' . esc_html__('The selected post views counters have been successfully reset.', 'contes-subscription') . '</p>';
            echo '</div>';
        }

        echo '<div class="wrap">';
        echo '<h1>' . esc_html__('Subscription Settings', 'contes-subscription') . '</h1>';
        echo '<nav class="contes-tabs">';

        foreach ($tabs as $slug => $label) {
            $active = $current_tab === $slug ? ' nav-tab-active' : '';
            $url = admin_url('admin.php?page=contes-subscription&tab=' . $slug);
            echo '<a href="' . esc_url($url) . '" class="nav-tab' . esc_attr($active) . '">' . esc_html($label) . '</a>';
        }

        echo '</nav>';
        echo '<div class="contes-tab-content">';

        switch ($current_tab) {
            case 'visibility':
                $this->render_visibility_teaser_tab();
                break;
            case 'post-grid-access':
                $this->render_post_grid_access_tab();
                break;
            case 'tools':
                $this->render_tools_tab();
                break;
            case 'general':
            default:
                $this->render_general_tab();
                break;
        }

        echo '</div>';
        echo '</div>';
    }
}
