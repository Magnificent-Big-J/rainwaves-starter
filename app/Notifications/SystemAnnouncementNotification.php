<?php

namespace App\Notifications;

/**
 * Reference AppNotification implementation; also used by the starter's
 * feature tests. Apps add their own notification types alongside it.
 */
class SystemAnnouncementNotification extends AppNotification
{
    public function __construct(
        private readonly string $announcementTitle,
        private readonly string $announcementBody,
    ) {}

    public function type(): string
    {
        return 'system.announcement';
    }

    public function title(object $notifiable): string
    {
        return $this->announcementTitle;
    }

    public function body(object $notifiable): string
    {
        return $this->announcementBody;
    }

    public function deepLink(object $notifiable): array
    {
        return ['route' => 'home', 'params' => []];
    }
}
