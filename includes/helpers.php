<?php
// Handle the reset post views counters action
function contes_subscription_reset_post_views_counters() {
    // Verify nonce
    if (!isset($_POST['reset_post_views_nonce_field']) || 
        !wp_verify_nonce($_POST['reset_post_views_nonce_field'], 'reset_post_views_nonce')) {
        wp_die(__('Invalid request.', 'contes-subscription'));
    }

    // Verify user permissions
    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to perform this action.', 'contes-subscription'));
    }

    // Determine the counter type
    $counter_type = isset($_POST['counter_type']) ? sanitize_text_field($_POST['counter_type']) : '';

    if ($counter_type === 'general') {
        $meta_key = 'wpb_post_views_count';
        $success_message = __('General post views counters have been successfully reset.', 'contes-subscription');
    } elseif ($counter_type === 'monthly') {
        $meta_key = 'wpb_post_views_month_count';
        $success_message = __('Monthly post views counters have been successfully reset.', 'contes-subscription');

        // Clear the monthly history from wp_options
        delete_option('wpb_post_views_month_history');
    } else {
        wp_die(__('Invalid counter type.', 'contes-subscription'));
    }

    // Reset the specified counter for all posts
    $args = [
        'post_type'      => 'post',
        'posts_per_page' => -1,
        'fields'         => 'ids',
    ];
    $posts = get_posts($args);

    foreach ($posts as $post_id) {
        update_post_meta($post_id, $meta_key, '0');
    }

    // Redirect with a success message
    $redirect_url = add_query_arg('reset_success', '1', admin_url('admin.php?page=contes-subscription'));
    wp_redirect($redirect_url);
    exit;
}
add_action('admin_post_reset_post_views_counters', 'contes_subscription_reset_post_views_counters');
