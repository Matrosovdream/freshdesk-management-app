<?php

namespace App\Services\DummyData\Importers;

use App\Models\Contact;
use App\Models\Group;
use App\Models\User;
use App\Repositories\Group\ManagerGroupScopeRepo;
use App\Services\DummyData\AssignmentPicker;

class UserLinkImporter
{
    public function __construct(
        private ManagerGroupScopeRepo $scopeRepo,
    ) {}

    /**
     * @return array{users_linked:int, managers_scoped:int}
     */
    public function import(AssignmentPicker $picker): array
    {
        return [
            'users_linked'     => $this->linkUsersToContacts(),
            'managers_scoped'  => $this->scopeManagersToGroups($picker),
        ];
    }

    private function linkUsersToContacts(): int
    {
        $count = 0;

        User::query()
            ->whereNull('freshdesk_contact_id')
            ->orderBy('id')
            ->get()
            ->each(function (User $user) use (&$count) {
                $contact = Contact::query()->where('email', $user->email)->first();

                if ($contact === null) {
                    $contact = Contact::query()
                        ->whereNotIn('id', function ($q) {
                            $q->from('users')
                                ->select('users.freshdesk_contact_id')
                                ->whereNotNull('users.freshdesk_contact_id');
                        })
                        ->orderBy('id')
                        ->first();
                }

                if ($contact === null) {
                    return;
                }

                $user->forceFill(['freshdesk_contact_id' => $contact->freshdesk_id])->save();
                $count++;
            });

        return $count;
    }

    private function scopeManagersToGroups(AssignmentPicker $picker): int
    {
        $managerIds = $picker->managerUserIds();
        if (empty($managerIds)) {
            return 0;
        }

        $groupIds = Group::query()->orderBy('id')->pluck('id')->all();
        if (empty($groupIds)) {
            return 0;
        }

        $count = 0;
        $groupChunks = array_chunk($groupIds, max(1, (int) ceil(count($groupIds) / count($managerIds))));

        foreach ($managerIds as $i => $userId) {
            $assigned = $groupChunks[$i] ?? [$groupIds[array_rand($groupIds)]];
            $this->scopeRepo->sync($userId, $assigned);
            $count += count($assigned);
        }

        return $count;
    }
}
