<?php
/**
 * Increment or initialize the post views count.
 *
 * @param int $postID The ID of the post to increment views for.
 */
function wp_set_post_views($postID) {
    $count_key = 'wpb_post_views_count'; // Meta key for storing the views count
    $count = get_post_meta($postID, $count_key, true);

    // If the meta key does not exist, initialize it to 0
    if ($count === '') {
        $count = 1;
        delete_post_meta($postID, $count_key); // Ensure no old or corrupt data
        add_post_meta($postID, $count_key, '0'); // Add the initial value
    } else {
        // Increment the existing view count
        $count++;
        update_post_meta($postID, $count_key, $count);
    }
}

/**
 * Add a custom column to the posts table for displaying views count.
 *
 * @param array $defaults The default columns in the posts table.
 * @return array Modified columns with a "Post Views" column added.
 */
function wp_posts_columns($defaults) {
    $defaults['wpb_post_views_count'] = esc_html__('Post Views', 'text_domain'); // Add a column for post views
    return $defaults;
}
add_filter('manage_post_posts_columns', 'wp_posts_columns');

/**
 * Populate the custom column in the posts table with the views count.
 *
 * @param string $column_name The name of the column being rendered.
 */
function wp_post_custom_column($column_name) {
    if ($column_name === 'wpb_post_views_count') {
        $post_id = get_the_ID(); // Get the current post ID
        $count = get_post_meta($post_id, 'wpb_post_views_count', true); // Retrieve the views count
        echo esc_html($count ?: '0'); // Display the count, defaulting to 0 if not set
    }
}
add_action('manage_post_posts_custom_column', 'wp_post_custom_column', 10, 2);

/**
 * Make the "Post Views" column sortable in the posts table.
 *
 * @param array $columns The existing sortable columns.
 * @return array Modified sortable columns.
 */
function wp_post_column_sortable($columns) {
    $columns['wpb_post_views_count'] = 'wpb_post_views_count'; // Make the views column sortable
    return $columns;
}
add_filter('manage_edit-post_sortable_columns', 'wp_post_column_sortable');

/**
 * Modify the query to handle sorting by the "Post Views" column.
 *
 * @param WP_Query $query The current query object.
 */
function wp_sortable_custom_column_query($query) {
    if (!is_admin()) {
        return; // Only modify the query in the admin area
    }

    $orderby = $query->get('orderby');

    if ($orderby === 'wpb_post_views_count') {
        $query->set('meta_key', 'wpb_post_views_count'); // Sort by the post views meta key
        $query->set('orderby', 'meta_value_num'); // Ensure numerical sorting
    }
}
add_action('pre_get_posts', 'wp_sortable_custom_column_query');
// Increment the post views count when a single post is visited.
add_action('wp', function () {
    if (is_single() && !is_admin()) {
        wp_set_post_views(get_the_ID());
    }
});