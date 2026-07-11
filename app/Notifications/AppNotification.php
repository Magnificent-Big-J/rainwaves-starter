<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

/**
 * Base class for user-facing notifications with the mobile payload contract:
 * { type, title, body, route, params }. `route` + `params` form the deep link
 * the app opens when the notification is tapped.
 *
 * Channels come from config/mobile.php so a push transport (FCM/APNs) can be
 * added platform-wide later without touching subclasses.
 */
abstract class AppNotification extends Notification
{
    /** Stable machine type, e.g. "system.announcement". */
    abstract public function type(): string;

    abstract public function title(object $notifiable): string;

    abstract public function body(object $notifiable): string;

    /** @return array{route: string, params: array} */
    abstract public function deepLink(object $notifiable): array;

    public function via(object $notifiable): array
    {
        return config('mobile.notification_channels', ['database']);
    }

    public function toDatabase(object $notifiable): array
    {
        $deepLink = $this->deepLink($notifiable);

        return [
            'type' => $this->type(),
            'title' => $this->title($notifiable),
            'body' => $this->body($notifiable),
            'route' => $deepLink['route'],
            'params' => $deepLink['params'],
        ];
    }
}
