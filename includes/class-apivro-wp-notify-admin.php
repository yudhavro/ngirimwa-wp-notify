<?php
/**
 * Admin settings page: configuration, connection check, channel list, test send, activity log.
 *
 * @package NgirimWA_WP_Notify
 */

if (!defined('ABSPATH')) {
    exit;
}

class APIvro_WP_Notify_Admin {

    private $settings_page_slug = 'apivro-wp-notify';

    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('admin_notices', array($this, 'paused_notice'));
        add_action('admin_post_apivro_wp_notify_check', array($this, 'handle_check'));
        add_action('admin_post_apivro_wp_notify_channels', array($this, 'handle_channels'));
        add_action('admin_post_apivro_wp_notify_test', array($this, 'handle_test'));
        // A new key or recipient list is the usual fix for a paused setup.
        add_action('update_option_apivro_wp_notify_api_key', array('APIvro_WP_Notify', 'resume'));
        add_action('update_option_apivro_wp_notify_recipients', array('APIvro_WP_Notify', 'resume'));
        add_filter('plugin_action_links_' . plugin_basename(APIVRO_WP_NOTIFY_PLUGIN_FILE), array($this, 'action_links'));
    }

    public function action_links($links) {
        array_unshift($links, '<a href="' . esc_url($this->page_url()) . '">' . esc_html__('Settings', 'apivro-wp-notify') . '</a>');
        return $links;
    }

    private function page_url($args = array()) {
        return add_query_arg(array_merge(array('page' => $this->settings_page_slug), $args), admin_url('options-general.php'));
    }

    public function enqueue_admin_scripts($hook) {
        if ('settings_page_' . $this->settings_page_slug !== $hook) {
            return;
        }
        wp_enqueue_script('jquery');
        $script = "
            jQuery(function($) {
                function toggle(name, wrapper) {
                    $(wrapper).toggle($('input[name=\"' + name + '\"]:checked').val() === 'custom');
                }
                $('.body-text-type-radio').on('change', function() { toggle('apivro_wp_notify_body_text_type', '#body-text-custom-wrapper'); });
                $('.delay-type-radio').on('change', function() { toggle('apivro_wp_notify_delay_type', '#delay-custom-wrapper'); });
                $('.apivro-copy').on('click', function() {
                    var id = $(this).data('id');
                    var box = $('#apivro_wp_notify_recipients');
                    var cur = $.trim(box.val());
                    if ((',' + cur.replace(/\\s+/g, '') + ',').indexOf(',' + id + ',') === -1) {
                        box.val(cur ? cur + ',' + id : id);
                    }
                    $(this).text('" . esc_js(__('Added', 'apivro-wp-notify')) . "');
                });
            });
        ";
        wp_add_inline_script('jquery', $script);
    }

    public function add_admin_menu() {
        add_options_page(
            __('NgirimWA WP Notify Settings', 'apivro-wp-notify'),
            __('NgirimWA WP Notify', 'apivro-wp-notify'),
            'manage_options',
            $this->settings_page_slug,
            array($this, 'render_settings_page')
        );
    }

    public function register_settings() {
        $group = 'apivro_wp_notify_settings';
        register_setting($group, 'apivro_wp_notify_api_key', array('type' => 'string', 'sanitize_callback' => array($this, 'sanitize_api_key'), 'default' => ''));
        register_setting($group, 'apivro_wp_notify_recipients', array('type' => 'string', 'sanitize_callback' => array($this, 'sanitize_recipients'), 'default' => ''));
        register_setting($group, 'apivro_wp_notify_include_image', array('type' => 'string', 'sanitize_callback' => array($this, 'sanitize_checkbox'), 'default' => '0'));
        register_setting($group, 'apivro_wp_notify_body_text_type', array('type' => 'string', 'sanitize_callback' => array($this, 'sanitize_choice'), 'default' => 'excerpt'));
        register_setting($group, 'apivro_wp_notify_body_text_custom', array('type' => 'string', 'sanitize_callback' => 'sanitize_textarea_field', 'default' => ''));
        register_setting($group, 'apivro_wp_notify_footer_text', array('type' => 'string', 'sanitize_callback' => 'sanitize_textarea_field', 'default' => ''));
        register_setting($group, 'apivro_wp_notify_delay_type', array('type' => 'string', 'sanitize_callback' => array($this, 'sanitize_delay_type'), 'default' => 'smart'));
        register_setting($group, 'apivro_wp_notify_delay_custom', array('type' => 'integer', 'sanitize_callback' => 'absint', 'default' => 3));

        $page = $this->settings_page_slug;
        add_settings_section('apivro_wp_notify_main_section', __('Configuration', 'apivro-wp-notify'), array($this, 'render_section_description'), $page);
        add_settings_field('apivro_wp_notify_api_key', __('API Key', 'apivro-wp-notify'), array($this, 'render_api_key_field'), $page, 'apivro_wp_notify_main_section');
        add_settings_field('apivro_wp_notify_recipients', __('Recipients', 'apivro-wp-notify'), array($this, 'render_recipients_field'), $page, 'apivro_wp_notify_main_section');
        add_settings_field('apivro_wp_notify_include_image', __('Featured Image', 'apivro-wp-notify'), array($this, 'render_include_image_field'), $page, 'apivro_wp_notify_main_section');
        add_settings_field('apivro_wp_notify_body_text', __('Body Text', 'apivro-wp-notify'), array($this, 'render_body_text_field'), $page, 'apivro_wp_notify_main_section');
        add_settings_field('apivro_wp_notify_footer_text', __('Footer Text', 'apivro-wp-notify'), array($this, 'render_footer_text_field'), $page, 'apivro_wp_notify_main_section');
        add_settings_field('apivro_wp_notify_delay', __('Delay', 'apivro-wp-notify'), array($this, 'render_delay_field'), $page, 'apivro_wp_notify_main_section');
    }

    public function sanitize_api_key($input) {
        return sanitize_text_field($input);
    }

    public function sanitize_recipients($input) {
        return implode(',', APIvro_WP_Notify::parse_recipients(sanitize_textarea_field($input)));
    }

    public function sanitize_checkbox($input) {
        return $input === '1' ? '1' : '0';
    }

    public function sanitize_choice($input) {
        return in_array($input, array('excerpt', 'custom', 'none'), true) ? $input : 'excerpt';
    }

    public function sanitize_delay_type($input) {
        return $input === 'custom' ? 'custom' : 'smart';
    }

    // ── Notices & actions ───────────────────────────────────────────────────────

    public function paused_notice() {
        if (!current_user_can('manage_options')) {
            return;
        }
        $paused = APIvro_WP_Notify::paused();
        if (!$paused) {
            return;
        }
        printf(
            '<div class="notice notice-error"><p><strong>%1$s</strong> %2$s <a href="%3$s">%4$s</a></p></div>',
            esc_html__('NgirimWA WP Notify is paused:', 'apivro-wp-notify'),
            esc_html($paused['message']),
            esc_url($this->page_url()),
            esc_html__('Open settings', 'apivro-wp-notify')
        );
    }

    private function flash($type, $message, $extra = array()) {
        set_transient('apivro_wp_notify_flash_' . get_current_user_id(), array_merge(array('type' => $type, 'message' => $message), $extra), 120);
    }

    private function guard($action) {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You are not allowed to do this.', 'apivro-wp-notify'));
        }
        check_admin_referer($action);
        return new APIvro_WP_Notify_API(get_option('apivro_wp_notify_api_key', ''));
    }

    public function handle_check() {
        $api = $this->guard('apivro_wp_notify_check');
        $result = $api->device_status();
        if ($result['ok']) {
            $status = isset($result['data']['status']) ? $result['data']['status'] : '';
            $phone = isset($result['data']['phone_number']) ? $result['data']['phone_number'] : '';
            if ($status === 'connected') {
                APIvro_WP_Notify::resume();
                /* translators: %s: phone number */
                $this->flash('success', sprintf(__('Connected. WhatsApp device %s is ready.', 'apivro-wp-notify'), $phone));
            } else {
                /* translators: %s: device status */
                $this->flash('warning', sprintf(__('API Key is valid, but the device status is "%s". Reconnect it in the NgirimWA dashboard.', 'apivro-wp-notify'), $status));
            }
        } else {
            if (APIvro_WP_Notify_API::should_pause($result)) {
                APIvro_WP_Notify::pause($result);
            }
            $this->flash('error', APIvro_WP_Notify::describe($result));
        }
        wp_safe_redirect($this->page_url());
        exit;
    }

    public function handle_channels() {
        $api = $this->guard('apivro_wp_notify_channels');
        $result = $api->channels();
        if ($result['ok']) {
            $channels = isset($result['data']['channels']) && is_array($result['data']['channels']) ? $result['data']['channels'] : array();
            $this->flash(
                $channels ? 'success' : 'warning',
                $channels ? __('Channels managed by your device:', 'apivro-wp-notify') : __('This device does not own or manage any channel yet.', 'apivro-wp-notify'),
                array('channels' => $channels)
            );
        } else {
            $this->flash('error', APIvro_WP_Notify::describe($result));
        }
        wp_safe_redirect($this->page_url());
        exit;
    }

    public function handle_test() {
        $api = $this->guard('apivro_wp_notify_test');
        $recipients = array_slice(APIvro_WP_Notify::parse_recipients(get_option('apivro_wp_notify_recipients', '')), 0, 5);
        if (!$recipients) {
            $this->flash('error', __('Add at least one recipient and save the settings first.', 'apivro-wp-notify'));
            wp_safe_redirect($this->page_url());
            exit;
        }
        $message = sprintf(
            /* translators: %s: site name */
            __('Test notification from %s via NgirimWA WP Notify.', 'apivro-wp-notify'),
            wp_specialchars_decode(get_bloginfo('name'), ENT_QUOTES)
        );
        $lines = array();
        $ok = 0;
        foreach ($recipients as $recipient) {
            $result = $api->send($recipient, $message, null, 'wpnotify-test-' . wp_generate_uuid4(), 'WP-TEST');
            if ($result['ok']) {
                $ok++;
                $lines[] = $recipient . ': ' . __('accepted', 'apivro-wp-notify');
                APIvro_WP_Notify::log(0, $recipient, 'test', __('Accepted by NgirimWA.', 'apivro-wp-notify'));
            } else {
                $lines[] = $recipient . ': ' . APIvro_WP_Notify::describe($result);
                APIvro_WP_Notify::log(0, $recipient, 'failed', APIvro_WP_Notify::describe($result));
                if (APIvro_WP_Notify_API::should_pause($result)) {
                    APIvro_WP_Notify::pause($result);
                    break;
                }
            }
        }
        if ($ok === count($recipients)) {
            APIvro_WP_Notify::resume();
        }
        $this->flash($ok === count($recipients) ? 'success' : 'error', implode(' · ', $lines));
        wp_safe_redirect($this->page_url());
        exit;
    }

    // ── Fields ──────────────────────────────────────────────────────────────────

    public function render_section_description() {
        echo '<p>' . esc_html__('Notifications are sent automatically in the background when a post is published.', 'apivro-wp-notify') . '</p>';
    }

    public function render_api_key_field() {
        $value = get_option('apivro_wp_notify_api_key', '');
        ?>
        <input type="password" id="apivro_wp_notify_api_key" name="apivro_wp_notify_api_key" value="<?php echo esc_attr($value); ?>"
               class="regular-text code" placeholder="<?php esc_attr_e('Enter your NgirimWA API Key', 'apivro-wp-notify'); ?>" autocomplete="off" />
        <?php if ($value !== '') : ?>
            <p class="description"><?php esc_html_e('Current key:', 'apivro-wp-notify'); ?> <code><?php echo esc_html($this->mask_api_key($value)); ?></code></p>
        <?php endif; ?>
        <p class="description"><?php esc_html_e('Get it from the NgirimWA dashboard (dash.ngirimwa.com) → Devices → API Keys. Messages are sent from the device linked to this key.', 'apivro-wp-notify'); ?></p>
        <?php
    }

    private function mask_api_key($api_key) {
        if (strlen($api_key) <= 8) {
            return str_repeat('*', strlen($api_key));
        }
        return substr($api_key, 0, 4) . str_repeat('*', 8) . substr($api_key, -4);
    }

    public function render_recipients_field() {
        $value = get_option('apivro_wp_notify_recipients', '');
        ?>
        <textarea id="apivro_wp_notify_recipients" name="apivro_wp_notify_recipients" rows="4" class="large-text code"
                  placeholder="628123456789,120363000000000000@g.us,120363000000000001@newsletter"><?php echo esc_textarea($value); ?></textarea>
        <p class="description">
            <?php esc_html_e('Separate with commas or new lines. WhatsApp number: 628123456789 · Group: …@g.us · Channel: …@newsletter (use "Load My Channels" below).', 'apivro-wp-notify'); ?>
        </p>
        <p class="description">
            <?php esc_html_e('Channel posts need the Pro or Enterprise plan, and the device number must be the owner or an admin of the channel. Each post counts as 1 message.', 'apivro-wp-notify'); ?>
        </p>
        <?php
    }

    public function render_include_image_field() {
        $value = get_option('apivro_wp_notify_include_image', '0');
        ?>
        <label>
            <input type="hidden" name="apivro_wp_notify_include_image" value="0" />
            <input type="checkbox" name="apivro_wp_notify_include_image" value="1" <?php checked($value, '1'); ?> />
            <?php esc_html_e('Send the featured image with the message (the text becomes the caption)', 'apivro-wp-notify'); ?>
        </label>
        <p class="description"><?php esc_html_e('The image must be publicly reachable over HTTPS.', 'apivro-wp-notify'); ?></p>
        <?php
    }

    public function render_body_text_field() {
        $type = get_option('apivro_wp_notify_body_text_type', 'excerpt');
        $custom = get_option('apivro_wp_notify_body_text_custom', '');
        ?>
        <fieldset>
            <label><input type="radio" name="apivro_wp_notify_body_text_type" value="excerpt" <?php checked($type, 'excerpt'); ?> class="body-text-type-radio" /> <?php esc_html_e('Use Excerpt', 'apivro-wp-notify'); ?></label><br />
            <label><input type="radio" name="apivro_wp_notify_body_text_type" value="custom" <?php checked($type, 'custom'); ?> class="body-text-type-radio" /> <?php esc_html_e('Custom', 'apivro-wp-notify'); ?></label><br />
            <label><input type="radio" name="apivro_wp_notify_body_text_type" value="none" <?php checked($type, 'none'); ?> class="body-text-type-radio" /> <?php esc_html_e('Title and link only', 'apivro-wp-notify'); ?></label>
        </fieldset>
        <div id="body-text-custom-wrapper" style="margin-top: 10px; <?php echo $type !== 'custom' ? 'display: none;' : ''; ?>">
            <textarea id="apivro_wp_notify_body_text_custom" name="apivro_wp_notify_body_text_custom" rows="4" class="large-text"><?php echo esc_textarea($custom); ?></textarea>
            <p class="description"><?php esc_html_e('Shown before the post link. Example: Baca selengkapnya 👇', 'apivro-wp-notify'); ?></p>
        </div>
        <?php
    }

    public function render_footer_text_field() {
        $value = get_option('apivro_wp_notify_footer_text', '');
        ?>
        <textarea id="apivro_wp_notify_footer_text" name="apivro_wp_notify_footer_text" rows="4" class="large-text"><?php echo esc_textarea($value); ?></textarea>
        <p class="description"><?php esc_html_e('Shown after the post link. Line breaks are kept. Leave empty to disable.', 'apivro-wp-notify'); ?></p>
        <?php
    }

    public function render_delay_field() {
        $type = get_option('apivro_wp_notify_delay_type', 'smart');
        $custom = get_option('apivro_wp_notify_delay_custom', 3);
        ?>
        <fieldset>
            <label><input type="radio" name="apivro_wp_notify_delay_type" value="smart" <?php checked($type, 'smart'); ?> class="delay-type-radio" /> <?php esc_html_e('Smart Delay (random 3–8 seconds)', 'apivro-wp-notify'); ?></label><br />
            <label><input type="radio" name="apivro_wp_notify_delay_type" value="custom" <?php checked($type, 'custom'); ?> class="delay-type-radio" /> <?php esc_html_e('Custom', 'apivro-wp-notify'); ?></label>
        </fieldset>
        <div id="delay-custom-wrapper" style="margin-top: 10px; <?php echo $type !== 'custom' ? 'display: none;' : ''; ?>">
            <input type="number" id="apivro_wp_notify_delay_custom" name="apivro_wp_notify_delay_custom" value="<?php echo esc_attr($custom); ?>" min="1" max="300" class="small-text" />
            <span><?php esc_html_e('seconds', 'apivro-wp-notify'); ?></span>
        </div>
        <p class="description"><?php esc_html_e('Gap between recipients. Sending runs in the background (WP-Cron), so publishing is never slowed down.', 'apivro-wp-notify'); ?></p>
        <?php
    }

    // ── Page ────────────────────────────────────────────────────────────────────

    private function action_button($action, $label, $primary = false) {
        ?>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="display:inline-block;margin:0 8px 8px 0;">
            <input type="hidden" name="action" value="<?php echo esc_attr($action); ?>" />
            <?php wp_nonce_field($action); ?>
            <button type="submit" class="button <?php echo $primary ? 'button-primary' : ''; ?>"><?php echo esc_html($label); ?></button>
        </form>
        <?php
    }

    private function render_flash() {
        $key = 'apivro_wp_notify_flash_' . get_current_user_id();
        $flash = get_transient($key);
        if (!$flash) {
            return;
        }
        delete_transient($key);
        $class = array('success' => 'notice-success', 'warning' => 'notice-warning', 'error' => 'notice-error')[$flash['type']] ?? 'notice-info';
        echo '<div class="notice ' . esc_attr($class) . ' is-dismissible"><p>' . esc_html($flash['message']) . '</p>';
        if (!empty($flash['channels'])) {
            echo '<table class="widefat striped" style="max-width:720px;margin:0 0 10px;"><thead><tr><th>' . esc_html__('Channel', 'apivro-wp-notify') . '</th><th>' . esc_html__('Channel ID', 'apivro-wp-notify') . '</th><th></th></tr></thead><tbody>';
            foreach ($flash['channels'] as $ch) {
                $id = isset($ch['id']) ? (string) $ch['id'] : '';
                echo '<tr><td>' . esc_html(isset($ch['name']) ? $ch['name'] : '') . '</td><td><code>' . esc_html($id) . '</code></td>';
                echo '<td><button type="button" class="button button-small apivro-copy" data-id="' . esc_attr($id) . '">' . esc_html__('Add to Recipients', 'apivro-wp-notify') . '</button></td></tr>';
            }
            echo '</tbody></table><p>' . esc_html__('Click "Add to Recipients", then Save Settings.', 'apivro-wp-notify') . '</p>';
        }
        echo '</div>';
    }

    private function render_log() {
        $log = get_option(APIvro_WP_Notify::LOG_OPTION, array());
        $labels = array(
            'sent'    => __('Sent', 'apivro-wp-notify'),
            'test'    => __('Test', 'apivro-wp-notify'),
            'retry'   => __('Retrying', 'apivro-wp-notify'),
            'failed'  => __('Failed', 'apivro-wp-notify'),
            'paused'  => __('Paused', 'apivro-wp-notify'),
            'skipped' => __('Skipped', 'apivro-wp-notify'),
        );
        ?>
        <h2><?php esc_html_e('Recent Activity', 'apivro-wp-notify'); ?></h2>
        <?php if (empty($log)) : ?>
            <p><?php esc_html_e('Nothing sent yet.', 'apivro-wp-notify'); ?></p>
            <?php return; ?>
        <?php endif; ?>
        <table class="widefat striped" style="max-width:960px;">
            <thead><tr>
                <th><?php esc_html_e('Time', 'apivro-wp-notify'); ?></th>
                <th><?php esc_html_e('Post', 'apivro-wp-notify'); ?></th>
                <th><?php esc_html_e('Recipient', 'apivro-wp-notify'); ?></th>
                <th><?php esc_html_e('Status', 'apivro-wp-notify'); ?></th>
                <th><?php esc_html_e('Detail', 'apivro-wp-notify'); ?></th>
            </tr></thead>
            <tbody>
            <?php foreach (array_slice($log, 0, 20) as $row) : ?>
                <tr>
                    <td><?php echo esc_html($row['time']); ?></td>
                    <td><?php echo $row['post_id'] ? '<a href="' . esc_url(get_edit_post_link($row['post_id'])) . '">#' . (int) $row['post_id'] . '</a>' : '—'; ?></td>
                    <td><code><?php echo esc_html($row['recipient']); ?></code></td>
                    <td><?php echo esc_html(isset($labels[$row['status']]) ? $labels[$row['status']] : $row['status']); ?></td>
                    <td><?php echo esc_html($row['detail']); ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php
    }

    public function render_settings_page() {
        if (!current_user_can('manage_options')) {
            return;
        }
        if (isset($_GET['settings-updated'])) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            add_settings_error('apivro_wp_notify_messages', 'apivro_wp_notify_message', __('Settings saved.', 'apivro-wp-notify'), 'success');
        }
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            <?php settings_errors('apivro_wp_notify_messages'); ?>
            <?php $this->render_flash(); ?>

            <form action="options.php" method="post">
                <?php
                settings_fields('apivro_wp_notify_settings');
                do_settings_sections($this->settings_page_slug);
                submit_button(__('Save Settings', 'apivro-wp-notify'));
                ?>
            </form>

            <h2><?php esc_html_e('Tools', 'apivro-wp-notify'); ?></h2>
            <p class="description"><?php esc_html_e('Save the settings before using these tools.', 'apivro-wp-notify'); ?></p>
            <?php
            $this->action_button('apivro_wp_notify_check', __('Check Connection', 'apivro-wp-notify'));
            $this->action_button('apivro_wp_notify_channels', __('Load My Channels', 'apivro-wp-notify'));
            $this->action_button('apivro_wp_notify_test', __('Send Test', 'apivro-wp-notify'));
            ?>

            <?php $this->render_log(); ?>
        </div>
        <?php
    }
}

if (is_admin()) {
    new APIvro_WP_Notify_Admin();
}
