<?php
/**
 * Admin settings class
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
    }

    public function enqueue_admin_scripts($hook) {
        if ('settings_page_' . $this->settings_page_slug !== $hook) {
            return;
        }

        wp_enqueue_script('jquery');

        $script = "
            jQuery(document).ready(function($) {
                function toggleBodyTextCustom() {
                    if ($('input[name=\"apivro_wp_notify_body_text_type\"]:checked').val() === 'custom') {
                        $('#body-text-custom-wrapper').show();
                    } else {
                        $('#body-text-custom-wrapper').hide();
                    }
                }

                function toggleDelayCustom() {
                    if ($('input[name=\"apivro_wp_notify_delay_type\"]:checked').val() === 'custom') {
                        $('#delay-custom-wrapper').show();
                    } else {
                        $('#delay-custom-wrapper').hide();
                    }
                }

                $('.body-text-type-radio').on('change', toggleBodyTextCustom);
                $('.delay-type-radio').on('change', toggleDelayCustom);

                toggleBodyTextCustom();
                toggleDelayCustom();
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
        register_setting(
            'apivro_wp_notify_settings',
            'apivro_wp_notify_api_key',
            array(
                'type' => 'string',
                'sanitize_callback' => array($this, 'sanitize_api_key'),
                'default' => '',
            )
        );

        register_setting('apivro_wp_notify_settings', 'apivro_wp_notify_recipients', array(
            'type' => 'string',
            'sanitize_callback' => array($this, 'sanitize_recipients'),
            'default' => '',
        ));

        register_setting('apivro_wp_notify_settings', 'apivro_wp_notify_body_text_type', array(
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => 'excerpt',
        ));

        register_setting('apivro_wp_notify_settings', 'apivro_wp_notify_body_text_custom', array(
            'type' => 'string',
            'sanitize_callback' => 'sanitize_textarea_field',
            'default' => '',
        ));

        register_setting('apivro_wp_notify_settings', 'apivro_wp_notify_footer_text', array(
            'type' => 'string',
            'sanitize_callback' => 'sanitize_textarea_field',
            'default' => '',
        ));

        register_setting('apivro_wp_notify_settings', 'apivro_wp_notify_delay_type', array(
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => 'smart',
        ));

        register_setting('apivro_wp_notify_settings', 'apivro_wp_notify_delay_custom', array(
            'type' => 'integer',
            'sanitize_callback' => 'absint',
            'default' => 3,
        ));

        add_settings_section(
            'apivro_wp_notify_main_section',
            __('Configuration', 'apivro-wp-notify'),
            array($this, 'render_section_description'),
            $this->settings_page_slug
        );

        add_settings_field(
            'apivro_wp_notify_api_key',
            __('API Key', 'apivro-wp-notify'),
            array($this, 'render_api_key_field'),
            $this->settings_page_slug,
            'apivro_wp_notify_main_section'
        );

        add_settings_field(
            'apivro_wp_notify_recipients',
            __('WA Numbers / Group IDs', 'apivro-wp-notify'),
            array($this, 'render_recipients_field'),
            $this->settings_page_slug,
            'apivro_wp_notify_main_section'
        );

        add_settings_field(
            'apivro_wp_notify_body_text',
            __('Body Text', 'apivro-wp-notify'),
            array($this, 'render_body_text_field'),
            $this->settings_page_slug,
            'apivro_wp_notify_main_section'
        );

        add_settings_field(
            'apivro_wp_notify_footer_text',
            __('Footer Text', 'apivro-wp-notify'),
            array($this, 'render_footer_text_field'),
            $this->settings_page_slug,
            'apivro_wp_notify_main_section'
        );

        add_settings_field(
            'apivro_wp_notify_delay',
            __('Delay', 'apivro-wp-notify'),
            array($this, 'render_delay_field'),
            $this->settings_page_slug,
            'apivro_wp_notify_main_section'
        );
    }

    public function sanitize_api_key($input) {
        return sanitize_text_field($input);
    }

    public function sanitize_recipients($input) {
        $input = sanitize_textarea_field($input);
        $input = preg_replace('/\s*,\s*/', ',', $input);
        return $input;
    }

    public function render_section_description() {
        echo '<p>' . esc_html__('Configure your NgirimWA API settings below. After saving, notifications will be sent automatically when a post is published.', 'apivro-wp-notify') . '</p>';
    }

    public function render_api_key_field() {
        $value = get_option('apivro_wp_notify_api_key', '');
        $display_value = !empty($value) ? $this->mask_api_key($value) : '';
        ?>
        <input type="password" 
               id="apivro_wp_notify_api_key" 
               name="apivro_wp_notify_api_key" 
               value="<?php echo esc_attr($value); ?>" 
               class="regular-text code" 
               placeholder="<?php esc_attr_e('Enter your NgirimWA API Key', 'apivro-wp-notify'); ?>" 
               autocomplete="off" />
        <?php if (!empty($value)) : ?>
            <p class="description" style="color: #666;">
                <?php esc_html_e('Current key: ', 'apivro-wp-notify'); ?><code><?php echo esc_html($display_value); ?></code>
            </p>
        <?php endif; ?>
        <p class="description">
            <?php esc_html_e('Enter your NgirimWA API Key. You can get your API key from your NgirimWA dashboard at dash.ngirimwa.com', 'apivro-wp-notify'); ?>
        </p>
        <?php
    }

    private function mask_api_key($api_key) {
        if (strlen($api_key) <= 8) {
            return str_repeat('*', strlen($api_key));
        }
        return substr($api_key, 0, 4) . str_repeat('*', strlen($api_key) - 8) . substr($api_key, -4);
    }

    public function render_recipients_field() {
        $value = get_option('apivro_wp_notify_recipients', '');
        ?>
        <textarea id="apivro_wp_notify_recipients" 
                  name="apivro_wp_notify_recipients" 
                  rows="5" 
                  class="large-text code" 
                  placeholder="<?php esc_attr_e('628123456789,123456789289524319@g.us', 'apivro-wp-notify'); ?>"><?php echo esc_textarea($value); ?></textarea>
        <p class="description">
            <?php esc_html_e('Enter WhatsApp numbers or Group IDs, separated by commas (without spaces). Each recipient will receive the notification. Example: 628123456789,123456789289524319@g.us', 'apivro-wp-notify'); ?>
        </p>
        <?php
    }

    public function render_body_text_field() {
        $body_text_type = get_option('apivro_wp_notify_body_text_type', 'excerpt');
        $body_text_custom = get_option('apivro_wp_notify_body_text_custom', '');
        ?>
        <fieldset>
            <label>
                <input type="radio" 
                       name="apivro_wp_notify_body_text_type" 
                       value="excerpt" 
                       <?php checked($body_text_type, 'excerpt'); ?> 
                       class="body-text-type-radio" />
                <?php esc_html_e('Use Excerpt', 'apivro-wp-notify'); ?>
            </label>
            <br />
            <label>
                <input type="radio" 
                       name="apivro_wp_notify_body_text_type" 
                       value="custom" 
                       <?php checked($body_text_type, 'custom'); ?> 
                       class="body-text-type-radio" />
                <?php esc_html_e('Custom', 'apivro-wp-notify'); ?>
            </label>
        </fieldset>
        <div id="body-text-custom-wrapper" style="margin-top: 10px; <?php echo $body_text_type !== 'custom' ? 'display: none;' : ''; ?>">
            <textarea id="apivro_wp_notify_body_text_custom" 
                      name="apivro_wp_notify_body_text_custom" 
                      rows="5" 
                      class="large-text"><?php echo esc_textarea($body_text_custom); ?></textarea>
            <p class="description">
                <?php esc_html_e('Enter custom body text that will appear before the post link. This text will be used for all notifications. Leave empty to disable. Example: Baca selengkapnya 👇👇', 'apivro-wp-notify'); ?>
            </p>
        </div>
        <?php
    }

    public function render_footer_text_field() {
        $value = get_option('apivro_wp_notify_footer_text', '');
        ?>
        <textarea id="apivro_wp_notify_footer_text" 
                  name="apivro_wp_notify_footer_text" 
                  rows="5" 
                  class="large-text"><?php echo esc_textarea($value); ?></textarea>
        <p class="description">
            <?php esc_html_e('Enter custom footer text that will appear after the post link. Leave empty to disable. You can use line breaks - they will be preserved in the WhatsApp message.', 'apivro-wp-notify'); ?>
        </p>
        <?php
    }

    public function render_delay_field() {
        $delay_type = get_option('apivro_wp_notify_delay_type', 'smart');
        $delay_custom = get_option('apivro_wp_notify_delay_custom', 3);
        ?>
        <fieldset>
            <label>
                <input type="radio" 
                       name="apivro_wp_notify_delay_type" 
                       value="smart" 
                       <?php checked($delay_type, 'smart'); ?> 
                       class="delay-type-radio" />
                <?php esc_html_e('Smart Delay', 'apivro-wp-notify'); ?>
            </label>
            <br />
            <label>
                <input type="radio" 
                       name="apivro_wp_notify_delay_type" 
                       value="custom" 
                       <?php checked($delay_type, 'custom'); ?> 
                       class="delay-type-radio" />
                <?php esc_html_e('Custom', 'apivro-wp-notify'); ?>
            </label>
        </fieldset>
        <div id="delay-custom-wrapper" style="margin-top: 10px; <?php echo $delay_type !== 'custom' ? 'display: none;' : ''; ?>">
            <input type="number" 
                   id="apivro_wp_notify_delay_custom" 
                   name="apivro_wp_notify_delay_custom" 
                   value="<?php echo esc_attr($delay_custom); ?>" 
                   min="1" 
                   max="300" 
                   class="small-text" />
            <span><?php esc_html_e('seconds', 'apivro-wp-notify'); ?></span>
            <p class="description">
                <?php esc_html_e('Enter delay in seconds between each message send. Recommended: 2-5 seconds to avoid being banned.', 'apivro-wp-notify'); ?>
            </p>
        </div>
        <p class="description" style="margin-top: 10px;">
            <?php esc_html_e('Smart Delay: Random delay between 2-5 seconds. Custom: Fixed delay as specified.', 'apivro-wp-notify'); ?>
        </p>
        <?php
    }

    public function render_settings_page() {
        if (!current_user_can('manage_options')) {
            return;
        }

        if (isset($_GET['settings-updated'])) {
            add_settings_error(
                'apivro_wp_notify_messages',
                'apivro_wp_notify_message',
                __('Settings saved successfully.', 'apivro-wp-notify'),
                'success'
            );
        }

        settings_errors('apivro_wp_notify_messages');
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            <form action="options.php" method="post">
                <?php
                settings_fields('apivro_wp_notify_settings');
                do_settings_sections($this->settings_page_slug);
                submit_button(__('Save Settings', 'apivro-wp-notify'));
                ?>
            </form>
        </div>
        <?php
    }
}

if (is_admin()) {
    new APIvro_WP_Notify_Admin();
}

