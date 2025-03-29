<?php

class Contes_Subscription_Favorites {

    public function __construct() {
        add_filter('favorites/button', [$this, 'restrict_favorites_button'], 10, 2);
        add_filter('favorites/user/favorites', [$this, 'restrict_favorites_save'], 10, 3);
    }

    public function restrict_favorites_button($args, $post_id) {
        $allowed_roles = get_option('contes_allowed_roles', []);
        $user = wp_get_current_user();

        if (array_intersect($allowed_roles, $user->roles)) {
            return $args;
        }

        return false;
    }

public function restrict_favorites_save($favorites, $post_id, $site_id) {
        $allowed_roles = get_option('contes_allowed_roles', []);
        $user = wp_get_current_user();

        if (!array_intersect($allowed_roles, $user->roles)) {
            return false;
        }

        return $favorites;
    }
}
