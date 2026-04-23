<?php

namespace App\Actions\Contacts;

use App\Models\Contact;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class GetContactAction
{
    public function handle(array $data = []): array
    {
        $id = (int) ($data['id'] ?? 0);
        $c = Contact::with('company')->withTrashed()->find($id);
        if (! $c) throw new NotFoundHttpException('Contact not found.');
        return $c->toArray();
    }
}
