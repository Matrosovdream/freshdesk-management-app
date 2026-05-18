<?php

namespace App\Notifications;

use App\Models\Contact;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ContactInviteNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private Contact $contact, private string $token) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = rtrim((string) config('app.url'), '/').'/portal/invite/'.$this->token;

        return (new MailMessage)
            ->subject('You have been invited to the customer portal')
            ->greeting('Hello '.($this->contact->name ?: 'there').',')
            ->line('You have been invited to access the customer portal.')
            ->action('Accept invite', $url)
            ->line('If you did not expect this invitation, you can ignore this email.');
    }
}
