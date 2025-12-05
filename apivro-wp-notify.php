<?php
/**
 * Plugin Name: NgirimWA WP Notify
 * Plugin URI: https://yudhavro.com
 * Description: Sends WhatsApp notification using NgirimWA when a post is published.
 * Version: 2.1.0
 * Author: Yudhavro
 * Author URI: https://yudhavro.com
 * Text Domain: apivro-wp-notify
 * Domain Path: /languages
 * Requires at least: 5.0
 * Requires PHP: 7.4
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

if (!defined('ABSPATH')) {
    exit;
}

define('APIVRO_WP_NOTIFY_VERSION', '2.1.0');
define('APIVRO_WP_NOTIFY_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('APIVRO_WP_NOTIFY_PLUGIN_URL', plugin_dir_url(__FILE__));

if (!function_exists('apivro_wp_notify_load_textdomain')) {
    function apivro_wp_notify_load_textdomain() {
        $plugin_dir = dirname(plugin_basename(__FILE__));
        $domain = 'apivro-wp-notify';
        $languages_dir = $plugin_dir . '/languages';
        
        load_plugin_textdomain($domain, false, $languages_dir);
    }
    add_action('plugins_loaded', 'apivro_wp_notify_load_textdomain', 1);
}

require_once APIVRO_WP_NOTIFY_PLUGIN_DIR . 'includes/class-apivro-wp-notify.php';

if (is_admin()) {
    require_once APIVRO_WP_NOTIFY_PLUGIN_DIR . 'includes/class-apivro-wp-notify-admin.php';
}

function apivro_wp_notify() {
    return APIvro_WP_Notify::get_instance();
}

apivro_wp_notify();
