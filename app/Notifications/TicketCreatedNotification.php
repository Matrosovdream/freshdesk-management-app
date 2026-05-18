<?php

namespace App\Notifications;

use App\Models\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TicketCreatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private Ticket $ticket) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = rtrim((string) config('app.url'), '/').'/portal/tickets/'.$this->ticket->id;
        $subject = $this->ticket->subject ?: 'New ticket';

        return (new MailMessage)
            ->subject('Ticket #'.$this->ticket->display_id.': '.$subject)
            ->greeting('Hello,')
            ->line('A new ticket has been created on your behalf.')
            ->line('Subject: '.$subject)
            ->action('View ticket', $url)
            ->line('We will follow up shortly.');
    }
}
