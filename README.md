# Advanced Notifications

An advanced notification system plugin for WordPress. It includes features for sending notifications to users, read receipts, and email integration.

## Features

### 🎯 Key Features
- **Header Dropdown**: Users can view their notifications in the header with a bell icon
- **Mark as Read**: Individual and bulk mark as read system
- **Email Integration**: Send notifications via email
- **Admin Panel**: Easy notification sending and management interface
- **My Notifications Page**: Page to view all notifications

### 🔧 Advanced Features
- **API Support**: API functions for other plugins
- **Notification Types**: Info, Success, Warning, Error types
- **AJAX Operations**: Dynamic updates and interaction
- **Responsive Design**: Mobile-friendly interface
- **Pagination**: Pagination for large notification lists

## Installation

1. Upload the plugin files to the `wp-content/plugins/pepech-notification-system/` folder
2. Activate the plugin from the WordPress admin panel
3. Configure the settings from the **Notifications** menu

## Usage

### Admin Panel

1. **Send Notification**: 
   - Select a user
   - Write a title and message
   - Specify the notification type
   - Set up email delivery

2. **Manage Notifications**:
   - View all notifications
   - Review details
   - Perform bulk actions

3. **Settings**:
   - Enable/disable email notifications
   - Set the number of notifications per page
   - Clear old notifications

### Frontend Integration

To display the notification dropdown in the header, add the following code to your theme file:

```php
<?php do_action(‘pepech_header_notifications’); ?>
```

### API Usage

To send notifications from other plugins:

```php
// Send a simple notification
pepech_send_notification($user_id, ‘Title’, ‘Message content’);

// Send an advanced notification
pepech_send_notification($user_id, ‘Title’, ‘Message’, ‘success’, false);

// Using a hook
do_action(‘pepech_send_notification’, $user_id, $title, $message, $type, $send_email);
```

### Retrieving User Notifications

```php
// Retrieve the last 10 notifications
$notifications = pepech_get_user_notifications($user_id, 10);

// Get only unread notifications
$unread_notifications = pepech_get_user_notifications($user_id, 10, true);

// Get the number of unread notifications
$unread_count = pepech_get_unread_count($user_id);
```

### Shortcode Usage

```php
// For the My Notifications page
[pepech_notifications limit="20" show_read="true"]

// Only unread notifications
[pepech_notifications limit="5" show_read="false"]
```

## Database Structure

The plugin creates the following table:

```sql
CREATE TABLE wp_pepech_notifications (
    id mediumint(9) NOT NULL AUTO_INCREMENT,
    user_id bigint(20) NOT NULL,
    title varchar(255) NOT NULL,
    message text NOT NULL,
    type varchar(50) DEFAULT ‘info’,
    is_read tinyint (1) DEFAULT 0,
    send_email tinyint(1) DEFAULT 1,
    created_at datetime DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY user_id (user_id),
    KEY is_read (is_read),
    KEY created_at (created_at)
);
```

## Customization

### CSS Customization

To customize notification styles, add the following to your theme's CSS:

```css
/* Notification badge color */
.pepech-notification-badge {
    background: #your-color !important;
}

/* Unread notification background */
.pepech-notification-item.unread {
    background-color: #your-color !important;
}
```

### JavaScript Customization

To customize notification behaviors:

```javascript
// Custom action when notification opens
jQuery(document).on(‘pepech_notification_opened’, function(event, notification) {
    console.log(‘Notification opened:’, notification);
});

// When the notification is marked as read
jQuery(document).on(‘pepech_notification_read’, function(event, notificationId) {
    console.log(‘Notification read:’, notificationId);
});
```

## Hooks and Filters

### Action Hooks

```php
// When a notification is sent
do_action(‘pepech_notification_sent’, $notification_id, $user_id);

// When a notification is read
do_action(‘pepech_notification_read’, $notification_id, $user_id);

// When all notifications are read
do_action(‘pepech_all_notifications_read’, $user_id);
```

### Filter Hooks

```php
// Customize email content
add_filter(‘pepech_email_content’, function($content, $title, $message) {
    return $custom_content;
}, 10, 3);

// Limit the number of notifications
add_filter(‘pepech_max_notifications’, function($limit) {
    return 20; // Maximum 20 notifications
});
```

## Troubleshooting

### Notifications Are Not Visible
- Make sure the user is logged in
- Check for JavaScript errors
- Clear cache plugins

### Emails Not Being Sent
- Check WordPress mail settings
- If using an SMTP plugin, check its settings
- Ensure email notifications are enabled

### AJAX Errors
- Ensure that the nonce values are correct
- Ensure that the JavaScript files are loaded
- Check the error messages in the Console

## Requirements

- WordPress 5.0+
- PHP 7.4+
- MySQL 5.6+

## License

GPL v2 or later

## Version History

### 1.0.0
- Initial release
- Basic notification system
- Email integration
- Admin panel
- API functions
