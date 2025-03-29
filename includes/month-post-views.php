<?php
/**
 * Updates the daily view count for a post and maintains a 30-day history.
 *
 * @param int $postID The ID of the post being viewed.
 */
function wp_set_month_post_views($postID) {
    $history_key = 'wpb_post_views_month_history'; // Key for storing the 30-day history.
    $meta_key = 'wpb_post_views_month_count'; // Key for the 30-day total views.

    // Retrieve the existing history.
    $history = get_option($history_key, []);
    $today = date('Y-m-d'); // Current date.

    // Initialize the history for the post if it doesn't exist.
    if (!isset($history[$postID])) {
        $history[$postID] = [];
    }

    // Increment the view count for today.
    if (!isset($history[$postID][$today])) {
        $history[$postID][$today] = 0;
    }
    $history[$postID][$today]++;

    // Remove entries older than 30 days.
    foreach ($history[$postID] as $date => $count) {
        if (strtotime($date) < strtotime('-30 days')) {
            unset($history[$postID][$date]);
        }
    }

    // Save the updated history.
    update_option($history_key, $history);

    // Calculate the total views for the last 30 days.
    $total_views = array_sum($history[$postID]);

    // Store the total views in post meta.
    update_post_meta($postID, $meta_key, $total_views);
}
add_action('wp_head', function () {
    if (is_single()) {
        wp_set_month_post_views(get_the_ID());
    }
});

/**
 * Retrieves the most popular posts based on views in the last 30 days.
 *
 * @param int $limit The maximum number of posts to retrieve.
 * @return array List of post IDs sorted by views.
 */
function wp_get_popular_posts($limit = 5) {
    $history_key = 'wpb_post_views_month_history'; // Key for the 30-day history.
    $history = get_option($history_key, []);
    $popular_posts = [];

    foreach ($history as $postID => $daily_counts) {
        $total_views = array_sum($daily_counts); // Sum views for the last 30 days.
        $popular_posts[$postID] = $total_views;
    }

    // Sort posts by view count in descending order.
    arsort($popular_posts);

    // Return the top posts based on the limit.
    return array_slice(array_keys($popular_posts), 0, $limit, true);
}

/**
 * Adds a custom column for 30-day views in the post admin list.
 *
 * @param array $columns Existing columns in the admin list.
 * @return array Modified columns.
 */
function wp_posts_views_month_column($columns) {
    $columns['wpb_post_views_month'] = __('30-Day Views', 'text_domain');
    return $columns;
}
add_filter('manage_post_posts_columns', 'wp_posts_views_month_column');

/**
 * Populates the custom column with the 30-day view count.
 *
 * @param string $column The name of the column being rendered.
 * @param int $postID The ID of the post.
 */
function wp_posts_views_month_custom_column($column, $postID) {
    if ($column === 'wpb_post_views_month') {
        // Retrieve the total 30-day views from post meta
        $monthly_views = get_post_meta($postID, 'wpb_post_views_month_count', true);

        // Display the value or "0" if it doesn't exist
        echo esc_html($monthly_views ? $monthly_views : 0);
    }
}
add_action('manage_post_posts_custom_column', 'wp_posts_views_month_custom_column', 10, 2);

/**
 * Schedules a daily cron job to clean up old data from the 30-day view history.
 */
add_action('admin_init', function() {
    if (!wp_next_scheduled('cleanup_monthly_views')) {
        error_log('The cleanup_monthly_views event is not scheduled.');
    } else {
        error_log('The cleanup_monthly_views event is scheduled.');
    }
});

/**
 * Cleans up view data older than 30 days from the history.
 */
/**
 * Cleans up view data older than 30 days from the history.
 */
add_action('cleanup_monthly_views', 'wp_cleanup_monthly_views');

function wp_cleanup_monthly_views() {
    $history_key = 'wpb_post_views_month_history'; // Key for the 30-day history.
    $history = get_option($history_key, []);

    // Ensure the history is a valid array
    if (!is_array($history) || empty($history)) {
        return; // No data to clean up
    }

    foreach ($history as $postID => $daily_counts) {
        if (!is_array($daily_counts)) {
            continue; // Skip invalid data
        }

        foreach ($daily_counts as $date => $count) {
            // Remove entries older than 30 days
            if (strtotime($date) < strtotime('-30 days')) {
                unset($history[$postID][$date]);
            }
        }

        // Clean up empty history for the post
        if (empty($history[$postID])) {
            unset($history[$postID]);
        }
    }

    // Save the cleaned-up history
    update_option($history_key, $history);
}

/**
 * Makes the "30-Day Views" column sortable in the post admin list.
 *
 * @param array $sortable_columns List of sortable columns.
 * @return array Modified list of sortable columns.
 */
function wp_posts_views_month_sortable_column($sortable_columns) {
    $sortable_columns['wpb_post_views_month'] = 'wpb_post_views_month';
    return $sortable_columns;
}
add_filter('manage_edit-post_sortable_columns', 'wp_posts_views_month_sortable_column');

/**
 * Adjusts the query to sort posts by "30-Day Views" in the admin list.
 *
 * @param WP_Query $query The current query object.
 */
function wp_sortable_custom_column_post_views_month_query($query) {
    if (!is_admin() || !$query->is_main_query()) {
        return;
    }

    $orderby = $query->get('orderby');

    if ('wpb_post_views_month' === $orderby) {
        $query->set('meta_key', 'wpb_post_views_month_count');
        $query->set('orderby', 'meta_value_num');
    }
}
add_action('pre_get_posts', 'wp_sortable_custom_column_post_views_month_query');
