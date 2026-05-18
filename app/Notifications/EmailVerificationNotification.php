<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EmailVerificationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private User $user, private string $token) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = rtrim((string) config('app.url'), '/').'/portal/verify-email/'.$this->token
            .'?email='.urlencode($this->user->email);

        return (new MailMessage)
            ->subject('Verify your email address')
            ->greeting('Hello '.($this->user->name ?: 'there').',')
            ->line('Please confirm your email address by clicking the button below.')
            ->action('Verify email', $url)
            ->line('If you did not create an account, no further action is required.');
    }
}
