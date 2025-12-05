# NgirimWA WP Notify

A WordPress plugin that automatically sends WhatsApp notifications via NgirimWA when a post is published.

## Features

- ✅ Automatically sends WhatsApp notifications when posts are published
- ✅ Supports both personal WhatsApp numbers and group IDs
- ✅ Multiple recipients support (comma-separated)
- ✅ Smart delay system to prevent WhatsApp account bans
- ✅ Customizable body text (excerpt or custom)
- ✅ Customizable footer text
- ✅ Secure API key storage
- ✅ Background processing for performance
- ✅ WordPress coding standards compliant
- ✅ Compatible with WordPress 5.0+ and PHP 7.4+

## Security

- ✅ API key stored securely using WordPress Options API
- ✅ API key masking in admin interface
- ✅ Proper sanitization and validation of all inputs
- ✅ Nonce verification for form submissions
- ✅ Capability checks for admin access
- ✅ No data exposed to frontend

## Performance

- ✅ Non-blocking design
- ✅ Background processing for large recipient lists
- ✅ Uses WordPress native HTTP API (wp_remote_post)
- ✅ Optimized delay system (smart delay: 2-5 seconds)
- ✅ Scheduled events for delays > 10 seconds

## Compatibility

- ✅ WordPress 6.0+ compatible
- ✅ PHP 8.0+ required
- ✅ Uses transition_post_status hook (modern approach)
- ✅ Filter hooks for extensibility
- ✅ No conflicts with other plugins (unique prefixes)

## Installation

1. Download or clone this repository
2. Upload to `/wp-content/plugins/apivro-wp-notify/`
3. Activate the plugin through WordPress admin
4. Configure in Settings > NgirimWA WP Notify

## Configuration

1. Enter your NgirimWA API Key
2. Add WhatsApp numbers/Group IDs (comma-separated, no spaces)
3. Configure body text (excerpt or custom)
4. Set footer text (optional)
5. Configure delay (smart or custom)

## Developer Hooks

### Filters

#### `apivro_wp_notify_post_types`
Allow custom post types to trigger notifications.

```php
add_filter('apivro_wp_notify_post_types', function($post_types) {
    $post_types[] = 'custom_post_type';
    return $post_types;
});
```

## File Structure

```
apivro-wp-notify/
├── apivro-wp-notify.php          # Main plugin file
├── readme.txt                     # WordPress.org readme
├── README.md                      # Developer documentation
├── LICENSE                        # GPL v2 License
├── .gitignore                     # Git ignore file
└── includes/
    ├── class-apivro-wp-notify.php        # Main plugin class
    └── class-apivro-wp-notify-admin.php  # Admin settings class
```

## Security Best Practices

- API keys are stored in WordPress database using Options API
- All user inputs are sanitized and validated
- Nonce verification prevents CSRF attacks
- Capability checks ensure only authorized users can configure
- API key is masked in admin interface
- No sensitive data exposed in frontend

## Performance Considerations

- Uses wp_remote_post (WordPress native HTTP API)
- Background processing for delays > 10 seconds
- Non-blocking design
- Only runs on post publish (not on every page load)
- Efficient database queries

## Contributing

Contributions are welcome! Please follow WordPress coding standards.

## License

GPL v2 or later

## Author

Yudhavro - https://yudhavro.com

## Support

For issues and feature requests, please open an issue on GitHub.


## Features

- ✅ Automatically sends WhatsApp notifications when posts are published
- ✅ Supports both personal WhatsApp numbers and group IDs
- ✅ Multiple recipients support (comma-separated)
- ✅ Smart delay system to prevent WhatsApp account bans
- ✅ Customizable body text (excerpt or custom)
- ✅ Customizable footer text
- ✅ Secure API key storage
- ✅ Background processing for performance
- ✅ WordPress coding standards compliant
- ✅ Compatible with WordPress 5.0+ and PHP 7.4+

## Security

- ✅ API key stored securely using WordPress Options API
- ✅ API key masking in admin interface
- ✅ Proper sanitization and validation of all inputs
- ✅ Nonce verification for form submissions
- ✅ Capability checks for admin access
- ✅ No data exposed to frontend

## Performance

- ✅ Non-blocking design
- ✅ Background processing for large recipient lists
- ✅ Uses WordPress native HTTP API (wp_remote_post)
- ✅ Optimized delay system (smart delay: 2-5 seconds)
- ✅ Scheduled events for delays > 10 seconds

## Compatibility

- ✅ WordPress 5.0+ compatible
- ✅ PHP 7.4+ required
- ✅ Uses transition_post_status hook (modern approach)
- ✅ Filter hooks for extensibility
- ✅ No conflicts with other plugins (unique prefixes)

## Installation

1. Download or clone this repository
2. Upload to `/wp-content/plugins/apivro-wp-notify/`
3. Activate the plugin through WordPress admin
4. Configure in Settings > APIvro WP Notify

## Configuration

1. Enter your APIvro API Key
2. Add WhatsApp numbers/Group IDs (comma-separated, no spaces)
3. Configure body text (excerpt or custom)
4. Set footer text (optional)
5. Configure delay (smart or custom)

## Developer Hooks

### Filters

#### `apivro_wp_notify_post_types`
Allow custom post types to trigger notifications.

```php
add_filter('apivro_wp_notify_post_types', function($post_types) {
    $post_types[] = 'custom_post_type';
    return $post_types;
});
```

## File Structure

```
apivro-wp-notify/
├── apivro-wp-notify.php          # Main plugin file
├── readme.txt                     # WordPress.org readme
├── README.md                      # Developer documentation
├── LICENSE                        # GPL v2 License
├── .gitignore                     # Git ignore file
└── includes/
    ├── class-apivro-wp-notify.php        # Main plugin class
    └── class-apivro-wp-notify-admin.php  # Admin settings class
```

## Security Best Practices

- API keys are stored in WordPress database using Options API
- All user inputs are sanitized and validated
- Nonce verification prevents CSRF attacks
- Capability checks ensure only authorized users can configure
- API key is masked in admin interface
- No sensitive data exposed in frontend

## Performance Considerations

- Uses wp_remote_post (WordPress native HTTP API)
- Background processing for delays > 10 seconds
- Non-blocking design
- Only runs on post publish (not on every page load)
- Efficient database queries

## Contributing

Contributions are welcome! Please follow WordPress coding standards.

## License

GPL v2 or later

## Author

Yudhavro - https://yudhavro.com

## Support

For issues and feature requests, please open an issue on GitHub.

