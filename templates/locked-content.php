<?php
/**
 * Template for locked content.
 */

if (!defined('ABSPATH')) {
    exit;
}

$blocked_post = get_queried_object();
$blocked_post_id = $blocked_post && isset($blocked_post->ID) ? (int) $blocked_post->ID : 0;
$GLOBALS['contes_subscription_blocked_post_id'] = $blocked_post_id;

$locked_template = contes_subscription_get_locked_template();

if (function_exists('wp_is_block_theme') && wp_is_block_theme()) {
    ?>
    <!doctype html>
    <html <?php language_attributes(); ?>>
    <head>
        <meta charset="<?php bloginfo('charset'); ?>">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <?php wp_head(); ?>
    </head>
    <body <?php body_class('contes-locked-body'); ?>>
    <?php wp_body_open(); ?>
    <?php
    if ($locked_template) {
        echo apply_filters('the_content', $locked_template->post_content);
    } else {
        if (function_exists('block_template_part')) {
            block_template_part('header');
        }
        ?>
        <main id="primary" class="site-main contes-locked-template">
            <?php echo contes_subscription_render_locked_message($blocked_post_id); ?>
        </main>
        <?php
        if (function_exists('block_template_part')) {
            block_template_part('footer');
        }
    }
    wp_footer();
    ?>
    </body>
    </html>
    <?php
} else {
    get_header();
    ?>
    <main id="primary" class="site-main contes-locked-template">
        <?php
        if ($locked_template) {
            echo apply_filters('the_content', $locked_template->post_content);
        } else {
            echo contes_subscription_render_locked_message($blocked_post_id);
        }
        ?>
    </main>
    <?php
    get_footer();
}
