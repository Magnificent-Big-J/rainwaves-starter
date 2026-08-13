<?php

namespace App\Modules\Teams\Notifications;

use App\Models\User;
use App\Modules\Teams\Models\Team;
use App\Modules\Teams\Models\TeamInvite;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Deliberately does not extend App\Notifications\AppNotification — that base class is
 * the mobile/in-app database-feed contract for an existing Notifiable user. An invite
 * is sent to an email address that may not have a User row yet (routed via
 * Notification::route('mail', $email)->notify(...)), so it needs mail, not a database
 * channel tied to an existing user.
 */
class TeamInvitationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly TeamInvite $invite,
        private readonly Team $team,
        private readonly User $inviter,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $acceptUrl = rtrim(config('app.url'), '/')
            .'/team-invites/'.$this->invite->token
            .'?email='.urlencode($this->invite->email);

        return (new MailMessage)
            ->subject("You've been invited to join {$this->team->name}")
            ->greeting('Hi there,')
            ->line("{$this->inviter->name} has invited you to join \"{$this->team->name}\" on ".config('app.name').'.')
            ->action('Accept invitation', $acceptUrl)
            ->line('This invitation expires on '.$this->invite->expires_at->toFormattedDateString().'.');
    }
}
