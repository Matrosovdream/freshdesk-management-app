<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SpaShellController extends Controller
{
    public function root(Request $request): RedirectResponse
    {
        $user = $request->user();
        if (! $user) {
            return redirect('/portal/login');
        }

        return method_exists($user, 'hasRole') && $user->hasRole('customer')
            ? redirect('/portal')
            : redirect('/dashboard');
    }

    public function dashboard(): View
    {
        return view('apps.dashboard');
    }

    public function portal(): View
    {
        return view('apps.portal');
    }
}
