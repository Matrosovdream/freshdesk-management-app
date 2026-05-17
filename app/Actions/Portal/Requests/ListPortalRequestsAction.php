<?php

namespace App\Actions\Portal\Requests;

use App\Models\Contact;
use App\Models\Ticket;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

final class ListPortalRequestsAction
{
    private const STATUS_MAP = [
        'open'          => 2,
        'pending'       => 3,
        'pending_reply' => 3,
        'resolved'      => 4,
        'closed'        => 5,
    ];

    public function handle(array $data = []): array
    {
        $user = Auth::user();
        if (! $user || ! $user->freshdesk_contact_id) {
            return [];
        }

        $contact = Contact::query()
            ->where('freshdesk_id', $user->freshdesk_contact_id)
            ->first();

        if (! $contact) {
            return [];
        }

        $query = Ticket::query()->with(['responder:id,name,email,freshdesk_id']);

        $scope = $data['scope'] ?? 'own';
        if ($scope === 'company' && $contact->company_id !== null) {
            $contactIds = Contact::query()
                ->where('company_id', $contact->company_id)
                ->pluck('id');
            $query->whereIn('requester_id', $contactIds);
        } else {
            $query->where('requester_id', $contact->id);
        }

        if (!empty($data['status']) && isset(self::STATUS_MAP[$data['status']])) {
            $query->where('status', self::STATUS_MAP[$data['status']]);
        }

        if (!empty($data['search'])) {
            $term = '%'.trim($data['search']).'%';
            $query->where(function (Builder $q) use ($term) {
                $q->where('subject', 'LIKE', $term)
                    ->orWhere('description_text', 'LIKE', $term);
            });
        }

        $tickets = $query
            ->orderByRaw('COALESCE(fd_updated_at, updated_at) DESC')
            ->limit(100)
            ->get();

        return $tickets->map(fn (Ticket $t) => $this->mapTicket($t))->all();
    }

    private function mapTicket(Ticket $t): array
    {
        $text = (string) ($t->description_text ?? strip_tags((string) $t->description));
        $preview = mb_substr(trim(preg_replace('/\s+/', ' ', $text)), 0, 200);

        return [
            'id'                  => $t->id,
            'subject'             => $t->subject,
            'status'              => $this->statusLabel((int) $t->status),
            'description_preview' => $preview,
            'created_at'          => optional($t->fd_created_at ?? $t->created_at)->toIso8601String(),
            'updated_at'          => optional($t->fd_updated_at ?? $t->updated_at)->toIso8601String(),
            'unread'              => false,
        ];
    }

    private function statusLabel(int $status): string
    {
        return match ($status) {
            2 => 'open',
            3 => 'pending',
            4 => 'resolved',
            5 => 'closed',
            default => 'open',
        };
    }
}
