# NgirimWA WP Notify

WordPress plugin that sends a WhatsApp notification through [NgirimWA](https://ngirimwa.com) when a post is published — to WhatsApp numbers, groups, and **WhatsApp Channels**.

## Features

- Recipients: numbers (`628123456789`), groups (`…@g.us`), and channels (`…@newsletter`)
- Optional featured image (the text becomes the caption)
- Body: excerpt, custom text, or title + link only; custom footer
- Background sending with WP-Cron and a delay between recipients — publishing is never slowed down
- Safe error handling:
  - temporary errors (network, rate limit, device briefly offline) are retried up to 3 times
  - permanent errors (invalid/deleted API key, device logged out, quota used up) **pause** sending and show an admin notice, so a broken setup never floods the API
- Tools: Check Connection, Load My Channels (one click to add a channel ID), Send Test
- Recent Activity log (last 50 sends)
- Idempotent requests (`Idempotency-Key`), so a retried request is never sent twice
- English and Indonesian

## Requirements

- WordPress 5.0+ and PHP 7.4+
- A NgirimWA API Key (dash.ngirimwa.com → Devices → API Keys)
- Channel posts: Pro or Enterprise plan, and the device number must be the owner or an admin of the channel

## Installation

1. Upload the folder to `/wp-content/plugins/apivro-wp-notify/` (or install the zip from Plugins → Add New → Upload).
2. Activate the plugin.
3. Open **Settings → NgirimWA WP Notify**, enter the API Key and recipients, then **Save Settings**.
4. Click **Check Connection**. For channels, click **Load My Channels** and **Add to Recipients**.

Sending runs on WP-Cron, which is triggered by site visits. On sites with very little traffic, set up a real cron job that calls `wp-cron.php` every minute.

## Developer hooks

```php
// Post types that trigger a notification (default: post).
add_filter('apivro_wp_notify_post_types', function ($types) {
    $types[] = 'product';
    return $types;
});

// Skip a specific post.
add_filter('apivro_wp_notify_should_send', function ($send, $post) {
    return has_category('internal', $post) ? false : $send;
}, 10, 2);

// Change the message text.
add_filter('apivro_wp_notify_message', function ($message, $post) {
    return $message . "\n#" . get_bloginfo('name');
}, 10, 2);
```

## API endpoints used

| Recipient | Endpoint |
| --- | --- |
| Number / group | `POST /api/v1/messages/send` |
| Channel | `POST /api/v1/channels/posts` |
| Check Connection | `GET /api/v1/devices/status` |
| Load My Channels | `GET /api/v1/channels` |

## License

GPL v2 or later.
