<?php
/**
 * Main plugin class.
 *
 * Flow: a post is published → one WP-Cron event per recipient is scheduled (spaced by the
 * configured delay) → each event sends one message through the NgirimWA API. Nothing is sent
 * inside the publish request, so publishing never waits on WhatsApp.
 *
 * Errors:
 *  - temporary (network, rate limit, device briefly offline, 5xx) → retried up to MAX_ATTEMPTS;
 *  - permanent (invalid/inactive API key, device logged out, quota exhausted, plan) → sending is
 *    PAUSED and an admin notice explains how to fix it. Paused sends are not retried, so a broken
 *    setup never floods the NgirimWA API.
 *
 * @package NgirimWA_WP_Notify
 */

if (!defined('ABSPATH')) {
    exit;
}

class APIvro_WP_Notify {

    const SEND_HOOK = 'apivro_wp_notify_send_one';
    const LEGACY_HOOK = 'apivro_wp_notify_send_scheduled_message';
    const MAX_ATTEMPTS = 3;
    const LOG_OPTION = 'apivro_wp_notify_log';
    const LOG_MAX = 50;
    const PAUSE_OPTION = 'apivro_wp_notify_paused';

    private static $instance = null;

    private function __construct() {
        add_action('transition_post_status', array($this, 'handle_post_status_transition'), 10, 3);
        add_action(self::SEND_HOOK, array($this, 'send_one'), 10, 3);
        // Events scheduled by version 2.x (they carry the API key + message). Delivered once with the
        // current settings, then gone.
        add_action(self::LEGACY_HOOK, array($this, 'send_legacy'), 10, 4);
    }

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function handle_post_status_transition($new_status, $old_status, $post) {
        if ($new_status !== 'publish' || $old_status === 'publish') {
            return;
        }
        $post = get_post($post);
        if (!$post || wp_is_post_revision($post->ID) || wp_is_post_autosave($post->ID)) {
            return;
        }
        $allowed_post_types = apply_filters('apivro_wp_notify_post_types', array('post'));
        if (!in_array($post->post_type, $allowed_post_types, true)) {
            return;
        }
        if (!apply_filters('apivro_wp_notify_should_send', true, $post)) {
            return;
        }
        $this->queue_post($post->ID);
    }

    /** Schedule one send per recipient. Returns the number of scheduled sends. */
    public function queue_post($post_id) {
        if (get_post_meta($post_id, '_apivro_wp_notify_sent', true)) {
            return 0;
        }
        $recipients = self::parse_recipients(get_option('apivro_wp_notify_recipients', ''));
        if (empty($recipients) || get_option('apivro_wp_notify_api_key', '') === '') {
            return 0;
        }
        if (self::paused()) {
            self::log($post_id, '-', 'skipped', __('Sending is paused. Fix the problem shown in the settings page.', 'apivro-wp-notify'));
            return 0;
        }

        // Mark first so a second publish hook (e.g. block editor + REST) never queues twice.
        update_post_meta($post_id, '_apivro_wp_notify_sent', current_time('mysql'));
        update_post_meta($post_id, '_apivro_wp_notify_sent_count', 0);

        $at = time() + 5;
        foreach (array_values($recipients) as $index => $recipient) {
            if ($index > 0) {
                $at += $this->delay_seconds();
            }
            wp_schedule_single_event($at, self::SEND_HOOK, array((int) $post_id, $recipient, 1));
        }
        spawn_cron();
        return count($recipients);
    }

    public function send_one($post_id, $recipient, $attempt = 1) {
        $post = get_post($post_id);
        if (!$post || $post->post_status !== 'publish') {
            return;
        }
        if (self::paused()) {
            self::log($post_id, $recipient, 'skipped', __('Sending is paused.', 'apivro-wp-notify'));
            return;
        }

        $api = new APIvro_WP_Notify_API(get_option('apivro_wp_notify_api_key', ''));
        $result = $api->send(
            $recipient,
            $this->build_message($post),
            $this->image_url($post),
            'wpnotify-' . md5(home_url() . '|' . $post->ID . '|' . $recipient),
            'WP-' . $post->ID
        );

        if ($result['ok']) {
            $count = (int) get_post_meta($post->ID, '_apivro_wp_notify_sent_count', true);
            update_post_meta($post->ID, '_apivro_wp_notify_sent_count', $count + 1);
            self::log($post->ID, $recipient, 'sent', __('Accepted by NgirimWA.', 'apivro-wp-notify'));
            return;
        }

        if (APIvro_WP_Notify_API::should_pause($result)) {
            self::pause($result);
            self::log($post->ID, $recipient, 'paused', self::describe($result));
            return;
        }

        $attempt = max(1, (int) $attempt);
        if (APIvro_WP_Notify_API::should_retry($result) && $attempt < self::MAX_ATTEMPTS) {
            $wait = max($result['retry_after'], 120 * $attempt);
            wp_schedule_single_event(time() + $wait, self::SEND_HOOK, array((int) $post->ID, $recipient, $attempt + 1));
            self::log($post->ID, $recipient, 'retry', sprintf(
                /* translators: 1: error, 2: minutes */
                __('%1$s — retrying in %2$d minute(s).', 'apivro-wp-notify'),
                self::describe($result),
                (int) ceil($wait / 60)
            ));
            return;
        }

        self::log($post->ID, $recipient, 'failed', self::describe($result));
    }

    /** Version 2.x event: ignore the stored key/message, resend with current settings (no retry chain). */
    public function send_legacy($post_id, $api_key, $to, $message) {
        $this->send_one($post_id, $to, self::MAX_ATTEMPTS);
    }

    public function build_message($post) {
        $body_text_type = get_option('apivro_wp_notify_body_text_type', 'excerpt');
        $body_text_custom = get_option('apivro_wp_notify_body_text_custom', '');
        $footer_text = get_option('apivro_wp_notify_footer_text', '');

        $title = html_entity_decode(wp_strip_all_tags(get_the_title($post)), ENT_QUOTES, 'UTF-8');
        $message = '*' . $title . "*\n\n";

        if ($body_text_type === 'custom' && $body_text_custom !== '') {
            $message .= str_replace(array("\r\n", "\r"), "\n", $body_text_custom) . "\n";
        } elseif ($body_text_type === 'excerpt') {
            $excerpt = html_entity_decode(wp_strip_all_tags(get_the_excerpt($post)), ENT_QUOTES, 'UTF-8');
            if ($excerpt !== '') {
                $message .= $excerpt . "\n";
            }
        }

        $message .= esc_url_raw(get_permalink($post)) . "\n";

        if ($footer_text !== '') {
            $message .= "\n" . str_replace(array("\r\n", "\r"), "\n", $footer_text);
        }

        return apply_filters('apivro_wp_notify_message', $message, $post);
    }

    private function image_url($post) {
        if (get_option('apivro_wp_notify_include_image', '0') !== '1') {
            return null;
        }
        $url = get_the_post_thumbnail_url($post, 'large');
        return ($url && strpos($url, 'https://') === 0) ? $url : null;
    }

    private function delay_seconds() {
        if (get_option('apivro_wp_notify_delay_type', 'smart') === 'custom') {
            return max(1, min(300, absint(get_option('apivro_wp_notify_delay_custom', 3))));
        }
        return wp_rand(3, 8);
    }

    /** Comma or newline separated list → unique, trimmed recipients. */
    public static function parse_recipients($recipients) {
        $list = preg_split('/[\s,;]+/', (string) $recipients);
        $list = array_filter(array_map('trim', $list), 'strlen');
        return array_values(array_unique($list));
    }

    // ── Pause & log ─────────────────────────────────────────────────────────────

    public static function paused() {
        $paused = get_option(self::PAUSE_OPTION);
        return is_array($paused) ? $paused : null;
    }

    public static function pause($result) {
        update_option(self::PAUSE_OPTION, array(
            'code'    => $result['code'],
            'message' => self::describe($result),
            'time'    => current_time('mysql'),
        ), false);
    }

    public static function resume() {
        delete_option(self::PAUSE_OPTION);
    }

    public static function log($post_id, $recipient, $status, $detail) {
        $log = get_option(self::LOG_OPTION, array());
        if (!is_array($log)) {
            $log = array();
        }
        array_unshift($log, array(
            'time'      => current_time('mysql'),
            'post_id'   => (int) $post_id,
            'recipient' => (string) $recipient,
            'status'    => (string) $status,
            'detail'    => wp_strip_all_tags((string) $detail),
        ));
        update_option(self::LOG_OPTION, array_slice($log, 0, self::LOG_MAX), false);
    }

    /** Human readable explanation of an API result. */
    public static function describe($result) {
        $known = array(
            'api_key_missing'       => __('API Key is not set.', 'apivro-wp-notify'),
            'invalid_api_key'       => __('API Key is invalid or was deleted. Create a new key in the NgirimWA dashboard.', 'apivro-wp-notify'),
            'api_key_inactive'      => __('API Key is inactive. Activate it in the NgirimWA dashboard.', 'apivro-wp-notify'),
            'subscription_inactive' => __('Your NgirimWA subscription is not active.', 'apivro-wp-notify'),
            'account_suspended'     => __('Your NgirimWA account is suspended. Contact support.', 'apivro-wp-notify'),
            'device_logged_out'     => __('The WhatsApp device is logged out. Scan the QR code again in the NgirimWA dashboard.', 'apivro-wp-notify'),
            'device_required'       => __('This API Key has no device. Create the key from a device in the NgirimWA dashboard.', 'apivro-wp-notify'),
            'quota_exceeded'        => __('Monthly message quota is used up.', 'apivro-wp-notify'),
            'plan_feature_required' => __('Posting to WhatsApp Channel needs the Pro or Enterprise plan.', 'apivro-wp-notify'),
            'channel_not_found'     => __('Channel not found. The device number must be the owner or an admin of the channel.', 'apivro-wp-notify'),
            'device_not_connected'  => __('The WhatsApp device is temporarily disconnected.', 'apivro-wp-notify'),
            'rate_limited'          => __('Too many requests. NgirimWA asked to slow down.', 'apivro-wp-notify'),
            'network_error'         => __('Could not reach NgirimWA.', 'apivro-wp-notify'),
        );
        $text = isset($known[$result['code']]) ? $known[$result['code']] : ($result['message'] !== '' ? $result['message'] : $result['code']);
        return $result['code'] === 'network_error' && $result['message'] !== '' ? $text . ' (' . $result['message'] . ')' : $text;
    }
}
