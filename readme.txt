=== APIvro WP Notify ===
Contributors: yudhavro
Tags: whatsapp, notification, apivro, messaging, automation
Requires at least: 5.0
Tested up to: 6.4
Requires PHP: 7.4
Stable tag: 2.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Automatically send WhatsApp notifications via APIvro when a post is published.

== Description ==

APIvro WP Notify is a WordPress plugin that automatically sends WhatsApp notifications to your contacts or groups when you publish a new post. The plugin uses the APIvro API to deliver messages directly to WhatsApp.

== Features ==

* Automatically sends WhatsApp notifications when posts are published
* Supports both personal WhatsApp numbers and group IDs
* Multiple recipients support (comma-separated, without spaces)
* Smart delay system to prevent WhatsApp account bans
* Customizable body text (excerpt or custom text)
* Customizable footer text with line break support
* Secure API key storage using WordPress options API
* API key masking for security
* Background processing for large recipient lists
* Easy-to-use admin settings page
* Built following WordPress coding standards
* Compatible with WordPress 5.0+ and PHP 7.4+
* Non-blocking design to avoid slowing down website

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/apivro-wp-notify` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Go to Settings > APIvro WP Notify to configure your API key and recipients.
4. Enter your APIvro API Key and WhatsApp numbers/group IDs.
5. Optionally, add custom footer text for your messages.

== Frequently Asked Questions ==

= How do I get my APIvro API Key? =

You can get your API Key from your APIvro dashboard at https://api.yudhavro.com

= How do I find my WhatsApp Group ID? =

Group IDs can be obtained from your APIvro dashboard or by checking your APIvro account settings.

= Can I send to multiple recipients? =

Yes, you can add multiple WhatsApp numbers or group IDs, separated by commas without spaces. Example: 6285361405924,120363280289524319

= Can I customize the message format? =

The message includes the post title, excerpt, and permalink. You can add custom footer text in the settings page. Line breaks in the footer text will be preserved in the WhatsApp message.

== Changelog ==

= 2.0.0 =
* Complete plugin restructure following WordPress standards
* Added admin settings page with WordPress Settings API
* Added API Key configuration with masking
* Added multiple recipients support
* Added smart delay system (2-5 seconds random) to prevent bans
* Added custom delay option
* Added customizable body text (excerpt or custom)
* Added customizable footer text with line break support
* Improved code organization with class-based structure
* Enhanced security with proper sanitization and validation
* Improved performance with background processing
* Enhanced error handling and logging
* Updated to use transition_post_status hook for better compatibility
* Replaced cURL with wp_remote_post for better WordPress integration
* Added filter for custom post types support
* Improved compatibility with WordPress 5.0+ and future versions

= 1.0.0 =
* Initial release

== Upgrade Notice ==

= 2.0.0 =
Major update: Complete restructure with admin dashboard. Please configure your API key and recipients in Settings > APIvro WP Notify after updating.

== Screenshots ==

1. Admin settings page for configuring API key, recipients, and footer text

== Developer Notes ==

This plugin follows WordPress coding standards and best practices. The plugin uses:
* WordPress Settings API for secure option storage
* Singleton pattern for main plugin class
* Proper sanitization and validation of user input
* Nonce verification for form security
* API key masking for security
* wp_remote_post for HTTP requests (WordPress native)
* Background processing with wp_schedule_single_event
* Filter hooks for extensibility (apivro_wp_notify_post_types)
* Full internationalization support (i18n) with Indonesian and English translations
* Automatic language detection based on WordPress locale settings
* WP_DEBUG aware logging
* No blocking operations on frontend

