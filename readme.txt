=== NgirimWA WP Notify ===
Contributors: yudhavro
Tags: whatsapp, notification, whatsapp channel, ngirimwa, automation
Requires at least: 5.0
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 3.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Send a WhatsApp notification through NgirimWA to numbers, groups, and WhatsApp Channels when a post is published.

== Description ==

NgirimWA WP Notify sends your new posts to WhatsApp automatically: to personal numbers, WhatsApp groups, and WhatsApp Channels managed by your NgirimWA device.

* Recipients: numbers, group IDs (…@g.us), channel IDs (…@newsletter)
* Optional featured image
* Excerpt, custom text, or title + link only, plus a footer
* Background sending (WP-Cron) with a delay between recipients
* Temporary errors are retried; permanent errors pause sending and show an admin notice
* Check Connection, Load My Channels, and Send Test tools
* Recent Activity log
* English and Indonesian

== Installation ==

1. Upload the plugin to `/wp-content/plugins/apivro-wp-notify` or install the zip from Plugins → Add New → Upload.
2. Activate the plugin.
3. Go to Settings → NgirimWA WP Notify, enter your API Key and recipients, then save.
4. Click Check Connection. For channels, click Load My Channels and add the channel to the recipients.

== Frequently Asked Questions ==

= Where do I get the API Key? =

In the NgirimWA dashboard (dash.ngirimwa.com) → Devices → API Keys. Messages are sent from the device linked to the key.

= How do I post to a WhatsApp Channel? =

Your plan must be Pro or Enterprise and the device number must be the owner or an admin of the channel. Click "Load My Channels" on the settings page and add the channel ID. Each channel post counts as 1 message.

= Why does it say "paused"? =

NgirimWA rejected the request for a reason that retrying cannot fix: the API Key was deleted or deactivated, the WhatsApp device logged out, the subscription is inactive, or the monthly quota is used up. Fix the cause, then click Check Connection or save the settings to resume.

= Notifications arrive late on my site =

Sending runs on WP-Cron, which runs when the site gets visits. Add a server cron job that requests wp-cron.php every minute.

== Changelog ==

= 3.0.0 =
* New: post to WhatsApp Channels (…@newsletter), with a "Load My Channels" tool.
* New: send the featured image with the notification.
* New: "Title and link only" body option.
* New: Check Connection and Send Test tools, Recent Activity log.
* Changed: messages are sent in the background with WP-Cron; publishing no longer waits (no more sleep in the publish request).
* Changed: permanent errors (invalid/deleted API key, device logged out, quota used up, inactive subscription) pause sending with an admin notice instead of retrying.
* Changed: temporary errors are retried up to 3 times, honoring Retry-After.
* Changed: requests carry an Idempotency-Key and ref_id WP-<post ID>.
* Changed: the API key is no longer stored in scheduled cron events.
* Changed: recipients can be separated by commas or new lines; duplicates are removed.
* Added uninstall cleanup and Settings link on the Plugins screen.

= 2.1.0 =
* NgirimWA branding and API endpoint.

= 2.0.0 =
* Admin settings page, multiple recipients, delays, custom body and footer.

= 1.0.0 =
* Initial release.

== Upgrade Notice ==

= 3.0.0 =
Adds WhatsApp Channel support and stops retrying requests that cannot succeed. After updating, open the settings page and click Check Connection.
