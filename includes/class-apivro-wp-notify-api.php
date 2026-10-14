<?php
/**
 * NgirimWA API client (API v1).
 *
 * Every call returns a normalized result array:
 *   ok        bool    2xx + success:true
 *   http      int     HTTP status (0 = network error)
 *   code      string  details.code from NgirimWA (or 'network_error' / 'invalid_response')
 *   message   string  human readable message
 *   data      array   response "data"
 *   retry_after int   seconds from the Retry-After header (0 when absent)
 *
 * @package NgirimWA_WP_Notify
 */

if (!defined('ABSPATH')) {
    exit;
}

class APIvro_WP_Notify_API {

    const BASE_URL = 'https://dash.ngirimwa.com/api/v1';

    /**
     * Error codes that will not succeed by retrying. Sending is paused until the
     * site admin fixes the cause (re-link the device, replace the key, upgrade, ...).
     */
    const PAUSE_CODES = array(
        'api_key_missing',
        'invalid_api_key',
        'api_key_inactive',
        'subscription_inactive',
        'account_suspended',
        'device_logged_out',
        'device_required',
        'quota_exceeded',
        'plan_feature_required',
    );

    /** Temporary problems worth a delayed retry. */
    const RETRY_CODES = array(
        'network_error',
        'rate_limited',
        'auth_blocked',
        'auth_unavailable',
        'session_service_unavailable',
        'session_service_timeout',
        'session_service_unreachable',
        'device_not_connected',
        'idempotency_in_progress',
        'internal_error',
    );

    private $api_key;

    public function __construct($api_key) {
        $this->api_key = trim((string) $api_key);
    }

    public static function base_url() {
        return apply_filters('apivro_wp_notify_api_base_url', self::BASE_URL);
    }

    /** Recipient type from its ID: channel (…@newsletter), group (…@g.us) or number. */
    public static function recipient_type($recipient) {
        if (substr($recipient, -11) === '@newsletter') {
            return 'channel';
        }
        if (substr($recipient, -5) === '@g.us') {
            return 'group';
        }
        return 'number';
    }

    public static function should_pause($result) {
        return in_array($result['code'], self::PAUSE_CODES, true);
    }

    public static function should_retry($result) {
        return in_array($result['code'], self::RETRY_CODES, true) || $result['http'] >= 500;
    }

    /** GET /devices/status */
    public function device_status() {
        return $this->request('GET', '/devices/status');
    }

    /** GET /channels — channels managed by the device of this API key. */
    public function channels() {
        return $this->request('GET', '/channels?refresh=true');
    }

    /**
     * Send to a number/group (POST /messages/send) or a channel (POST /channels/posts).
     *
     * @param string      $recipient  Number, group ID (…@g.us) or channel ID (…@newsletter).
     * @param string      $message    Text, or caption when $image_url is set.
     * @param string|null $image_url  Optional public HTTPS image URL.
     * @param string      $idempotency_key Same key = the request is accepted only once.
     * @param string      $ref_id     Your reference (numbers/groups only), e.g. WP-123.
     */
    public function send($recipient, $message, $image_url, $idempotency_key, $ref_id = '') {
        $headers = array('Idempotency-Key' => substr($idempotency_key, 0, 64));

        if (self::recipient_type($recipient) === 'channel') {
            $body = array(
                'channel_id' => $recipient,
                'message'    => $message,
            );
            if ($image_url) {
                $body['media'] = $image_url;
                $body['media_type'] = 'image';
            }
            return $this->request('POST', '/channels/posts', $body, $headers);
        }

        $body = array(
            'to'      => $recipient,
            'message' => $message,
        );
        if ($image_url) {
            $body['media'] = $image_url;
            $body['media_type'] = 'image';
        }
        if ($ref_id !== '') {
            $body['ref_id'] = substr($ref_id, 0, 100);
        }
        return $this->request('POST', '/messages/send', $body, $headers);
    }

    private function request($method, $path, $body = null, $headers = array()) {
        if ($this->api_key === '') {
            return self::result(false, 0, 'api_key_missing', __('API Key is not set.', 'apivro-wp-notify'));
        }

        $args = array(
            'method'  => $method,
            'timeout' => 20,
            'headers' => array_merge(
                array(
                    'x-api-key' => $this->api_key,
                    'Accept'    => 'application/json',
                ),
                $headers
            ),
        );
        if ($body !== null) {
            $args['headers']['Content-Type'] = 'application/json';
            $args['body'] = wp_json_encode($body);
            $args['data_format'] = 'body';
        }

        $response = wp_remote_request(self::base_url() . $path, $args);

        if (is_wp_error($response)) {
            return self::result(false, 0, 'network_error', $response->get_error_message());
        }

        $http = (int) wp_remote_retrieve_response_code($response);
        $json = json_decode(wp_remote_retrieve_body($response), true);
        $retry_after = absint(wp_remote_retrieve_header($response, 'retry-after'));

        if (!is_array($json)) {
            return self::result(false, $http, $http >= 500 ? 'internal_error' : 'invalid_response', sprintf(
                /* translators: %d: HTTP status code */
                __('Unexpected response from NgirimWA (HTTP %d).', 'apivro-wp-notify'),
                $http
            ), array(), $retry_after);
        }

        $ok = $http >= 200 && $http < 300 && !empty($json['success']);
        $code = isset($json['details']['code']) ? (string) $json['details']['code'] : ($ok ? 'ok' : 'http_' . $http);
        $message = isset($json['message']) ? (string) $json['message'] : '';
        $data = isset($json['data']) && is_array($json['data']) ? $json['data'] : array();

        return self::result($ok, $http, $code, $message, $data, $retry_after);
    }

    private static function result($ok, $http, $code, $message, $data = array(), $retry_after = 0) {
        return array(
            'ok'          => (bool) $ok,
            'http'        => (int) $http,
            'code'        => (string) $code,
            'message'     => (string) $message,
            'data'        => $data,
            'retry_after' => (int) $retry_after,
        );
    }
}
