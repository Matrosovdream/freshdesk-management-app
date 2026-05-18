<?php

namespace App\Services;

use App\Models\Contact;
use App\Models\Ticket;
use App\Models\User;
use App\Notifications\ContactInviteNotification;
use App\Notifications\EmailVerificationNotification;
use App\Notifications\PasswordResetNotification;
use App\Notifications\TicketCreatedNotification;
use Illuminate\Support\Facades\Notification;

class NotificationService
{
    public function sendContactInvite(Contact $contact, string $token): void
    {
        if (! $contact->email) {
            return;
        }

        Notification::route('mail', $contact->email)
            ->notify(new ContactInviteNotification($contact, $token));
    }

    public function sendEmailVerification(User $user, string $token): void
    {
        $user->notify(new EmailVerificationNotification($user, $token));
    }

    public function sendPasswordReset(User $user, string $token): void
    {
        $user->notify(new PasswordResetNotification($user, $token));
    }

    public function sendTicketCreated(Ticket $ticket): void
    {
        $email = $ticket->requester?->email;
        if (! $email) {
            return;
        }

        Notification::route('mail', $email)
            ->notify(new TicketCreatedNotification($ticket));
    }
}
