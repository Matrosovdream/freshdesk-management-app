<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Support\AuditWriter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();
        return response()->json(['data' => [
            'id'          => $user->id,
            'name'        => $user->name,
            'email'       => $user->email,
            'phone'       => $user->phone,
            'avatar'      => $user->avatar,
            'is_active'   => (bool) $user->is_active,
            'preferences' => $user->payload['preferences'] ?? null,
        ]]);
    }

    public function update(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'                  => ['sometimes', 'string', 'max:120'],
            'phone'                 => ['sometimes', 'nullable', 'string', 'max:40'],
            'avatar'                => ['sometimes', 'nullable', 'string'],
            'timezone'              => ['sometimes', 'nullable', 'string', 'max:60'],
            'password'              => ['sometimes', 'nullable', 'string', 'min:8', 'confirmed'],
            'browser_notifications' => ['sometimes', 'boolean'],
            'email_digest'          => ['sometimes', 'boolean'],
        ]);

        $user = $request->user();
        $before = $user->only(['name', 'phone', 'avatar']);

        $fill = array_intersect_key($data, array_flip(['name', 'phone', 'avatar']));
        if (! empty($data['password'])) $fill['password'] = Hash::make($data['password']);
        $user->fill($fill);
        $user->save();

        AuditWriter::log('profile.updated', 'User', $user->id, $before, $user->only(['name', 'phone', 'avatar']));

        return $this->show($request);
    }

    public function logoutOthers(Request $request): JsonResponse
    {
        $user = $request->user();

        // For cookie-based SPA auth we rotate the password-remember token +
        // regenerate the session — equivalent to logging out other sessions.
        if (method_exists($user, 'setRememberToken')) {
            $user->setRememberToken(null);
            $user->save();
        }
        $request->session()->regenerate();

        AuditWriter::log('profile.logout_others', 'User', $user->id);
        return response()->json(['data' => ['ok' => true]]);
    }
}
