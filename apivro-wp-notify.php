<?php
/**
 * Plugin Name: NgirimWA WP Notify
 * Plugin URI: https://github.com/yudhavro/ngirimwa-wp-notify
 * Description: Sends a WhatsApp notification through NgirimWA to numbers, groups, and WhatsApp Channels when a post is published.
 * Version: 3.0.0
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

define('APIVRO_WP_NOTIFY_VERSION', '3.0.0');
define('APIVRO_WP_NOTIFY_PLUGIN_FILE', __FILE__);
define('APIVRO_WP_NOTIFY_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('APIVRO_WP_NOTIFY_PLUGIN_URL', plugin_dir_url(__FILE__));

if (!function_exists('apivro_wp_notify_load_textdomain')) {
    function apivro_wp_notify_load_textdomain() {
        load_plugin_textdomain('apivro-wp-notify', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }
    add_action('plugins_loaded', 'apivro_wp_notify_load_textdomain', 1);
}

require_once APIVRO_WP_NOTIFY_PLUGIN_DIR . 'includes/class-apivro-wp-notify-api.php';
require_once APIVRO_WP_NOTIFY_PLUGIN_DIR . 'includes/class-apivro-wp-notify.php';

if (is_admin()) {
    require_once APIVRO_WP_NOTIFY_PLUGIN_DIR . 'includes/class-apivro-wp-notify-admin.php';
}

function apivro_wp_notify() {
    return APIvro_WP_Notify::get_instance();
}

apivro_wp_notify();

/** Deactivation: drop queued sends (including 2.x events that stored the API key). */
function apivro_wp_notify_deactivate() {
    wp_unschedule_hook(APIvro_WP_Notify::SEND_HOOK);
    wp_unschedule_hook(APIvro_WP_Notify::LEGACY_HOOK);
}
register_deactivation_hook(__FILE__, 'apivro_wp_notify_deactivate');
