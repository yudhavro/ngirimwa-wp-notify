<?php
/**
 * Main plugin class
 *
 * @package NgirimWA_WP_Notify
 */

if (!defined('ABSPATH')) {
    exit;
}

class APIvro_WP_Notify {

    private static $instance = null;

    private function __construct() {
        $this->init_hooks();
    }

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function init_hooks() {
        add_action('transition_post_status', array($this, 'handle_post_status_transition'), 10, 3);
        add_action('apivro_wp_notify_send_scheduled_message', array($this, 'send_scheduled_message'), 10, 4);
    }

    public function send_scheduled_message($post_id, $api_key, $to, $message) {
        $this->send_message($api_key, $to, $message);
    }

    public function handle_post_status_transition($new_status, $old_status, $post) {
        if ($new_status !== 'publish' || $old_status === 'publish') {
            return;
        }

        if (!is_object($post)) {
            $post = get_post($post);
        }

        if (!$post) {
            return;
        }

        $allowed_post_types = apply_filters('apivro_wp_notify_post_types', array('post'));
        if (!in_array($post->post_type, $allowed_post_types, true)) {
            return;
        }

        $this->send_notification($post->ID);
    }

    public function send_notification($post_id) {
        if (wp_is_post_revision($post_id) || wp_is_post_autosave($post_id)) {
            return;
        }

        if (get_post_meta($post_id, '_apivro_wp_notify_sent', true)) {
            return;
        }

        $post = get_post($post_id);
        if (!$post || $post->post_status !== 'publish') {
            return;
        }

        $api_key = get_option('apivro_wp_notify_api_key', '');
        $recipients = get_option('apivro_wp_notify_recipients', '');

        if (empty($api_key) || empty($recipients)) {
            return;
        }

        $body_text_type = get_option('apivro_wp_notify_body_text_type', 'excerpt');
        $body_text_custom = get_option('apivro_wp_notify_body_text_custom', '');
        $footer_text = get_option('apivro_wp_notify_footer_text', '');
        $delay_type = get_option('apivro_wp_notify_delay_type', 'smart');
        $delay_custom = absint(get_option('apivro_wp_notify_delay_custom', 3));

        $permalink = esc_url_raw(get_permalink($post_id));
        $post_title = sanitize_text_field($post->post_title);

        $message = "*{$post_title}*\n\n";

        if ($body_text_type === 'custom' && !empty($body_text_custom)) {
            $body_text = str_replace(array("\r\n", "\r"), "\n", $body_text_custom);
            $message .= "{$body_text}\n";
        } elseif ($body_text_type === 'excerpt') {
            $excerpt = get_the_excerpt($post_id);
            if (!empty($excerpt)) {
                $message .= sanitize_text_field($excerpt) . "\n";
            }
        }

        $message .= "{$permalink}\n\n\n";

        if (!empty($footer_text)) {
            $footer_text = str_replace(array("\r\n", "\r"), "\n", $footer_text);
            $message .= $footer_text;
        }

        $recipient_array = $this->parse_recipients($recipients);
        if (empty($recipient_array)) {
            return;
        }

        $this->send_messages_async($post_id, $api_key, $recipient_array, $message, $delay_type, $delay_custom);
    }

    private function send_messages_async($post_id, $api_key, $recipients, $message, $delay_type, $delay_custom) {
        $total_recipients = count($recipients);
        $sent_count = 0;

        foreach ($recipients as $index => $to) {
            $to = trim($to);
            if (empty($to)) {
                continue;
            }

            if ($index > 0) {
                $delay = $this->calculate_delay($delay_type, $delay_custom);
                if ($delay > 0 && $delay <= 10) {
                    sleep($delay);
                } elseif ($delay > 10) {
                    wp_schedule_single_event(
                        time() + $delay,
                        'apivro_wp_notify_send_scheduled_message',
                        array($post_id, $api_key, $to, $message)
                    );
                    continue;
                }
            }

            if ($this->send_message($api_key, $to, $message)) {
                $sent_count++;
            }
        }

        update_post_meta($post_id, '_apivro_wp_notify_sent', current_time('mysql'));
        update_post_meta($post_id, '_apivro_wp_notify_sent_count', $sent_count);
    }

    private function calculate_delay($delay_type, $delay_custom) {
        if ($delay_type === 'smart') {
            return rand(2, 5);
        } else {
            return max(1, absint($delay_custom));
        }
    }

    private function parse_recipients($recipients) {
        if (empty($recipients)) {
            return array();
        }

        $recipients = preg_replace('/\s*,\s*/', ',', $recipients);
        $recipient_list = explode(',', $recipients);
        return array_filter(array_map('trim', $recipient_list));
    }

    private function send_message($api_key, $to, $message) {
        if (empty($api_key) || empty($to) || empty($message)) {
            return false;
        }

        $url = 'https://dash.ngirimwa.com/api/v1/messages/send';
        $to = sanitize_text_field($to);

        $body = array(
            'to' => $to,
            'message' => $message,
        );

        $args = array(
            'timeout' => 30,
            'headers' => array(
                'x-api-key' => sanitize_text_field($api_key),
                'Content-Type' => 'application/json',
            ),
            'body' => wp_json_encode($body),
            'data_format' => 'body',
        );

        $response = wp_remote_post($url, $args);

        if (is_wp_error($response)) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('NgirimWA WP Notify Error: ' . $response->get_error_message());
            }
            return false;
        }

        $http_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        $response_data = json_decode($response_body, true);

        if ($http_code === 200 && isset($response_data['success']) && $response_data['success']) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                $message_id = isset($response_data['message_id']['id']) ? $response_data['message_id']['id'] : 'N/A';
                error_log('NgirimWA WP Notify: Message sent successfully to ' . esc_html($to) . '. Message ID: ' . esc_html($message_id));
            }
            return true;
        } else {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('NgirimWA WP Notify: Failed to send to ' . esc_html($to) . '. Response: ' . esc_html($response_body));
            }
            return false;
        }
    }
}

