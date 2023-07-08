<?php
/*
Plugin Name: WP Clients Handoff
Plugin URI: https://example.com/
Description: Admin widget to manage client handoff tasks.
Version: 0.1.1
Author: DPlugins
Author URI: https://dplugins.com/
Text Domain: wpch
License: GPL2
*/

define('WPCH_BASE', plugin_basename(__FILE__));
define('WPCH_URL',	plugin_dir_url(__FILE__));
define('WPCH_DIR',	plugin_dir_path(__FILE__));
define('WPCH_PLUGINVERSION',  '0.1.1');

// require_once WPCH_DIR . 'includes/todo-list.php';
require_once WPCH_DIR . 'includes/todo-list--alpine.php';
