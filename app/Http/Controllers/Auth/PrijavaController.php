<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PrijavaController extends Controller
{
    public function create(): View
    {
        return view('auth.prijava');
    }

    public function store(Request $request): RedirectResponse
    {
        $podaci = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ], [
            'email.required' => 'Upiši e-mail.',
            'email.email' => 'E-mail nije ispravan.',
            'password.required' => 'Upiši lozinku.',
        ]);

        if (! Auth::attempt($podaci, $request->boolean('zapamti'))) {
            throw ValidationException::withMessages(['email' => 'Pogrešan e-mail ili lozinka.']);
        }

        $request->session()->regenerate();

        return redirect()->intended(route('obracuni.index'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
