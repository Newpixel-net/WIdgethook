<?php
/*
 * Copyright (c) 2025 AltumCode (https://altumcode.com/)
 *
 * This software is licensed exclusively by AltumCode and is sold only via https://altumcode.com/.
 * Unauthorized distribution, modification, or use of this software without a valid license is not permitted and may be subject to applicable legal actions.
 *
 * 🌍 View all other existing AltumCode projects via https://altumcode.com/
 * 📧 Get in touch for support or general queries via https://altumcode.com/contact
 * 📤 Download the latest version via https://altumcode.com/downloads
 *
 * 🐦 X/Twitter: https://x.com/AltumCode
 * 📘 Facebook: https://facebook.com/altumcode
 * 📸 Instagram: https://instagram.com/altumcode
 */

namespace Altum\Models;

defined('ALTUMCODE') || die();

class Notification extends Model {

    public function delete($notification_id) {

        $notification = db()->where('notification_id', $notification_id)->getOne('notifications', ['user_id', 'notification_id', 'settings']);

        if(!$notification) return;

        $notification->settings = json_decode($notification->settings ?? '');

        /* Delete uploaded files */
        \Altum\Uploads::delete_uploaded_file($notification->settings->image ?? null, 'notifications');
        \Altum\Uploads::delete_uploaded_file($notification->settings->audio ?? null, 'notifications');

        /* Delete the notification */
        db()->where('notification_id', $notification_id)->delete('notifications');

        /* Clear the cache */
        cache()->deleteItem('notifications_total?user_id=' . $notification->user_id);


    }
}
