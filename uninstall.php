<?php
/**
 * Uninstall: remove the plugin's settings, log, and queued sends.
 *
 * @package NgirimWA_WP_Notify
 */

if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

$options = array(
    'apivro_wp_notify_api_key',
    'apivro_wp_notify_recipients',
    'apivro_wp_notify_include_image',
    'apivro_wp_notify_body_text_type',
    'apivro_wp_notify_body_text_custom',
    'apivro_wp_notify_footer_text',
    'apivro_wp_notify_delay_type',
    'apivro_wp_notify_delay_custom',
    'apivro_wp_notify_log',
    'apivro_wp_notify_paused',
);
foreach ($options as $option) {
    delete_option($option);
}

wp_unschedule_hook('apivro_wp_notify_send_one');
wp_unschedule_hook('apivro_wp_notify_send_scheduled_message');
