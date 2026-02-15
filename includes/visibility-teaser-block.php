<?php

function contes_subscription_get_visibility_teaser_defaults() {
	$default_message = __( 'Subscriu-te per accedir a aquest contingut', 'contes-subscription' );
	$default_button_label = __( 'Subscriu-te', 'contes-subscription' );
	$default_button_url = function_exists( 'pmpro_url' ) ? pmpro_url( 'levels' ) : '';

	$message = get_option( 'contes_visibility_teaser_message', $default_message );
	$button_label = get_option( 'contes_visibility_teaser_button_label', $default_button_label );
	$button_url = get_option( 'contes_visibility_teaser_button_url', $default_button_url );
	$button_page_id = absint( get_option( 'contes_visibility_teaser_button_page_id', 0 ) );
	if ( $button_page_id ) {
		$button_url = get_permalink( $button_page_id );
	}
	$teaser_height = get_option( 'contes_visibility_teaser_height', 120 );
	$teaser_block_count = get_option( 'contes_visibility_teaser_block_count', 2 );
	$background_color = get_option( 'contes_visibility_teaser_background_color', '#ffffff' );

	$message = $message !== '' ? $message : $default_message;
	$button_label = $button_label !== '' ? $button_label : $default_button_label;
	$button_url = $button_url !== '' ? $button_url : $default_button_url;

	return array(
		'message' => wp_kses_post( $message ),
		'button_label' => sanitize_text_field( $button_label ),
		'button_url' => esc_url_raw( $button_url ),
		'teaser_height' => max( 60, absint( $teaser_height ) ),
		'teaser_block_count' => max( 1, absint( $teaser_block_count ) ),
		'background_color' => sanitize_hex_color( $background_color ) ?: '#ffffff',
	);
}

function contes_subscription_get_visibility_teaser_levels() {
	if ( ! function_exists( 'pmpro_getAllLevels' ) ) {
		return array();
	}

	$levels = pmpro_getAllLevels( true, true );
	$values = array();

	if ( empty( $levels ) ) {
		return $values;
	}

	foreach ( $levels as $level ) {
		$values[] = array(
			'value' => $level->id,
			'label' => $level->name,
		);
	}

	return $values;
}

function contes_subscription_user_has_visibility_access() {
	$allowed_roles = get_option( 'contes_allowed_roles', array() );
	if ( empty( $allowed_roles ) ) {
		return is_user_logged_in();
	}

	$user = wp_get_current_user();
	if ( empty( $user ) || empty( $user->roles ) ) {
		return false;
	}

	return (bool) array_intersect( $allowed_roles, $user->roles );
}

function contes_subscription_should_show_visibility_teaser_content( $attributes ) {
	$segment = $attributes['segment'] ?? 'all';
	$levels = $attributes['levels'] ?? array();
	$invert = ! empty( $attributes['invertRestrictions'] );

	if ( function_exists( 'pmpro_hasMembershipLevel' ) ) {
		switch ( $segment ) {
			case 'specific':
				$levels_to_check = $levels;
				break;
			case 'logged_in':
				$levels_to_check = 'L';
				break;
			case 'all':
			default:
				$levels_to_check = null;
				break;
		}

		$has_access = pmpro_hasMembershipLevel( $levels_to_check );
	} else {
		if ( 'logged_in' === $segment ) {
			$has_access = is_user_logged_in();
		} else {
			$has_access = contes_subscription_user_has_visibility_access();
		}
	}

	return $invert ? ! $has_access : (bool) $has_access;
}

function contes_subscription_render_visibility_teaser_inner_blocks( $content, $block_limit, $blocks = null ) {
	if ( ! is_array( $blocks ) ) {
		$blocks = parse_blocks( $content );
	}

	if ( empty( $blocks ) ) {
		return wp_kses_post( wp_trim_words( $content, 55, '...' ) );
	}

	$rendered = '';
	$count = 0;

	foreach ( $blocks as $block ) {
		$rendered .= render_block( $block );
		$count++;

		if ( $count >= $block_limit ) {
			break;
		}
	}

	return $rendered;
}

function contes_subscription_render_visibility_teaser_block( $attributes, $content, $block = null ) {
	$defaults = contes_subscription_get_visibility_teaser_defaults();

	$use_default_content = ! isset( $attributes['useDefaultContent'] ) || ! empty( $attributes['useDefaultContent'] );
	$use_default_style = ! isset( $attributes['useDefaultStyle'] ) || ! empty( $attributes['useDefaultStyle'] );
	$use_default_limit = ! isset( $attributes['useDefaultLimit'] ) || ! empty( $attributes['useDefaultLimit'] );

	$message = $use_default_content ? $defaults['message'] : ( $attributes['message'] ?? '' );
	$button_label = $use_default_content ? $defaults['button_label'] : ( $attributes['buttonLabel'] ?? '' );
	$button_url = $use_default_content ? $defaults['button_url'] : ( $attributes['buttonUrl'] ?? '' );

	$message = $message !== '' ? $message : $defaults['message'];
	$button_label = $button_label !== '' ? $button_label : $defaults['button_label'];
	$button_url = $button_url !== '' ? $button_url : $defaults['button_url'];

	$teaser_height = $use_default_style ? $defaults['teaser_height'] : ( $attributes['teaserHeight'] ?? 0 );
	$teaser_height = max( 60, absint( $teaser_height ?: $defaults['teaser_height'] ) );

	$teaser_block_count = $use_default_limit ? $defaults['teaser_block_count'] : ( $attributes['teaserBlockCount'] ?? 0 );
	$teaser_block_count = max( 1, absint( $teaser_block_count ?: $defaults['teaser_block_count'] ) );

	$background_color = $use_default_style ? $defaults['background_color'] : ( $attributes['backgroundColor'] ?? '' );
	$background_color = sanitize_hex_color( $background_color ) ?: $defaults['background_color'];

	if ( contes_subscription_should_show_visibility_teaser_content( $attributes ) ) {
		return do_blocks( $content );
	}

	$style = sprintf(
		'--contes-teaser-height:%dpx; --contes-teaser-bg:%s;',
		$teaser_height,
		$background_color
	);

	$message_html = $message !== '' ? '<p class="contes-visibility-teaser__message">' . wp_kses_post( $message ) . '</p>' : '';
	$button_html = '';
	$icon_html = '<div class="contes-visibility-teaser__icon-wrap"><span class="contes-visibility-teaser__icon dahlia-icon dahlia-fi-rr-lock" aria-hidden="true"></span></div>';

	if ( $button_label !== '' && $button_url !== '' ) {
		$button_html = sprintf(
			'<a class="contes-visibility-teaser__button" href="%s">%s</a>',
			esc_url( $button_url ),
			esc_html( $button_label )
		);
	}

	$inner_blocks = null;
	if ( is_object( $block ) && ! empty( $block->parsed_block['innerBlocks'] ) ) {
		$inner_blocks = $block->parsed_block['innerBlocks'];
	}

	$teaser_content = contes_subscription_render_visibility_teaser_inner_blocks( $content, $teaser_block_count, $inner_blocks );

	return sprintf(
		'<div class="contes-visibility-teaser" style="%s"><div class="contes-visibility-teaser__content-wrap"><div class="contes-visibility-teaser__content">%s</div><div class="contes-visibility-teaser__fade" aria-hidden="true"></div></div><div class="contes-visibility-teaser__cta">%s%s%s</div></div>',
		esc_attr( $style ),
		$teaser_content,
		$icon_html,
		$message_html,
		$button_html
	);
}

function contes_subscription_register_visibility_teaser_block() {
	$script_path = CONTES_SUBSCRIPTION_PATH . 'assets/js/visibility-teaser-block.js';
	$style_path = CONTES_SUBSCRIPTION_PATH . 'build/style.bundle.css';
	$script_url = CONTES_SUBSCRIPTION_URL . 'assets/js/visibility-teaser-block.js';
	$style_url = CONTES_SUBSCRIPTION_URL . 'build/style.bundle.css';
	$script_version = file_exists( $script_path ) ? filemtime( $script_path ) : CONTES_SUBSCRIPTION_VERSION;
	$style_version = file_exists( $style_path ) ? filemtime( $style_path ) : CONTES_SUBSCRIPTION_VERSION;

	wp_register_script(
		'contes-subscription-visibility-teaser-block',
		$script_url,
		array( 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-i18n' ),
		$script_version,
		true
	);

	wp_localize_script(
		'contes-subscription-visibility-teaser-block',
		'contesVisibilityTeaserData',
		array(
			'defaults' => contes_subscription_get_visibility_teaser_defaults(),
			'levels' => contes_subscription_get_visibility_teaser_levels(),
			'hasPmpro' => function_exists( 'pmpro_hasMembershipLevel' ),
		)
	);

	wp_register_style(
		'contes-subscription-visibility-teaser',
		$style_url,
		array(),
		$style_version
	);

	register_block_type(
		'contes/visibility-teaser',
		array(
			'editor_script' => 'contes-subscription-visibility-teaser-block',
			'editor_style' => 'contes-subscription-visibility-teaser',
			'style' => 'contes-subscription-visibility-teaser',
			'render_callback' => 'contes_subscription_render_visibility_teaser_block',
			'attributes' => array(
				'invertRestrictions' => array(
					'type' => 'boolean',
					'default' => false,
				),
				'segment' => array(
					'type' => 'string',
					'default' => 'all',
				),
				'levels' => array(
					'type' => 'array',
					'default' => array(),
				),
				'useDefaultContent' => array(
					'type' => 'boolean',
					'default' => true,
				),
				'useDefaultStyle' => array(
					'type' => 'boolean',
					'default' => true,
				),
				'useDefaultLimit' => array(
					'type' => 'boolean',
					'default' => true,
				),
				'message' => array(
					'type' => 'string',
					'default' => '',
				),
				'buttonLabel' => array(
					'type' => 'string',
					'default' => '',
				),
				'buttonUrl' => array(
					'type' => 'string',
					'default' => '',
				),
				'teaserHeight' => array(
					'type' => 'number',
					'default' => 120,
				),
				'teaserBlockCount' => array(
					'type' => 'number',
					'default' => 2,
				),
				'backgroundColor' => array(
					'type' => 'string',
					'default' => '',
				),
			),
		)
	);
}
add_action( 'init', 'contes_subscription_register_visibility_teaser_block' );
