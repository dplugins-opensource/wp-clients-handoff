<?php
/*
Plugin Name: WP Clients Handoff
Plugin URI: https://example.com/
Description: Admin widget to manage client handoff tasks.
Version: 1.0
Author: Your Name
Author URI: https://yourwebsite.com/
License: GPL2
*/

// Include the website-todo file
include_once( plugin_dir_path( __FILE__ ) . 'website-todo.php' );

// Register the admin widget
function wch_register_admin_widget() {
    wp_add_dashboard_widget(
        'wp_clients_handoff_widget',  // Widget ID
        'WP Clients Handoff',  // Widget title
        'wtd_custom_admin_widget_content'  // Callback function to display widget content
    );
}
add_action( 'wp_dashboard_setup', 'wch_register_admin_widget' );
