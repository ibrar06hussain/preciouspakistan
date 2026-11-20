<?php

namespace App\Modules\Identity\Notifications;

use App\Modules\Identity\Models\AdminInvitation;
use App\Shared\Support\DisplayTimezone;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AdminInvitationNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly AdminInvitation $invitation,
        public readonly string $plainToken,
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = route('admin.invitations.show', ['token' => $this->plainToken]);
        $expires = DisplayTimezone::format($this->invitation->expires_at);

        return (new MailMessage)
            ->subject('You are invited to Precious Pakistan Admin Portal')
            ->greeting("Hello {$this->invitation->name},")
            ->line("You have been invited to join Precious Pakistan as {$this->invitation->role_name}.")
            ->action('Accept Invitation & Set Password', $url)
            ->line("This invitation link expires on {$expires}.")
            ->line('If you did not expect this invitation, you can safely ignore this email.');
    }
}
