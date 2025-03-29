<?php

class Contes_Subscription_Roles {

    public function __construct() {
        add_action('init', [$this, 'add_roles']);
        register_deactivation_hook(__FILE__, [$this, 'remove_roles']);
    }

    public function add_roles() {
        add_role('premium_member', __('Premium Member', 'contes-subscription'), [
            'read' => true,
        ]);
    }

    public function remove_roles() {
        remove_role('premium_member');
    }
}
